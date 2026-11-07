<?php
/**
 * MODELO — Reenvio SUNAT (cola de reintentos automaticos del modulo Facturacion).
 * Encapsula: consulta de la cola, estadisticas, y el reenvio real a SUNAT reutilizando
 * enviar_sunat() de config/sunat.php. Autonomo: replica la carga de comprobante/cliente/items.
 * Ver ARQUITECTURA_MVC.md
 */
class Reenvio
{
    /** Minutos entre reintentos (atado a la cadencia del worker: cada 5 min). */
    public const RETRY_MINUTES = 5;
    /** Tipos que se envian a SUNAT. */
    private const TIPOS_SUNAT = ['01', '03', '07'];

    private static function sunatCargado(): void
    {
        require_once dirname(__DIR__, 4) . '/config/sunat.php';
    }

    // ───────────────────────────────────────────── Cola / estadisticas ──

    /** Condicion base: comprobante que SI va a SUNAT y aun NO esta aceptado. */
    private static function condPendiente(): string
    {
        $tipos = "'" . implode("','", self::TIPOS_SUNAT) . "'";
        return "ce.codigo_tipo_documento IN ($tipos)
                AND ce.anulado = FALSE
                AND COALESCE(ce.reenvio_estado,'') <> 'aceptado'
                AND COALESCE(ce.cdr_path,'') = ''
                AND COALESCE(ce.codigo_respuesta,'') <> '0'";
    }

    /** Estado mostrado en la cola, derivado del registro. */
    public static function estadoCola(array $c): string
    {
        $e = (string)($c['reenvio_estado'] ?? '');
        if (in_array($e, ['rechazado', 'agotado', 'programado'], true)) return $e;
        return ((int)($c['intentos'] ?? 0) === 0) ? 'nunca_enviado' : 'programado';
    }

    /** Lista de comprobantes en cola, con filtros (desde, hasta, tipo, estado, q). */
    public static function pendientes(PDO $db, array $f): array
    {
        $params = [];
        $cond = [self::condPendiente()];
        $desde = trim((string)($f['desde'] ?? ''));
        $hasta = trim((string)($f['hasta'] ?? ''));
        $tipo  = trim((string)($f['tipo'] ?? ''));
        $estado = trim((string)($f['estado'] ?? ''));
        $q     = trim((string)($f['q'] ?? ''));
        if ($desde !== '') { $cond[] = 'ce.fecha_emision >= :desde'; $params[':desde'] = $desde; }
        if ($hasta !== '') { $cond[] = 'ce.fecha_emision <= :hasta'; $params[':hasta'] = $hasta; }
        if ($tipo !== '')  { $cond[] = 'ce.codigo_tipo_documento = :tipo'; $params[':tipo'] = $tipo; }
        if ($q !== '') {
            $cond[] = '(ce.numero ILIKE :q OR cl.razon_social ILIKE :q OR cl.nombre_completo ILIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }
        $sql = "
            SELECT ce.id, ce.numero, ce.serie, ce.correlativo, ce.codigo_tipo_documento,
                   ce.fecha_emision, ce.hora_emision, ce.total,
                   ce.intentos, ce.max_intentos, ce.proximo_intento, ce.reenvio_estado, ce.ultimo_error,
                   COALESCE(NULLIF(cl.razon_social,''), NULLIF(cl.nombre_completo,''), 'Cliente General') AS cliente_nombre,
                   COALESCE(NULLIF(cl.numero_documento,''), NULLIF(cl.ruc_dni,''), '-') AS cliente_doc
            FROM comprobantes_electronicos ce
            LEFT JOIN clientes cl ON cl.id = ce.cliente_id
            WHERE " . implode(' AND ', $cond) . "
            ORDER BY ce.proximo_intento ASC NULLS FIRST, ce.id DESC
            LIMIT 500";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$r) { $r['estado_cola'] = self::estadoCola($r); }
        unset($r);

        if ($estado !== '') {
            $rows = array_values(array_filter($rows, fn($r) => $r['estado_cola'] === $estado));
        }
        return $rows;
    }

    /** KPIs de la cabecera: programados, agotados, rechazados, proximo intento, total. */
    public static function estadisticas(PDO $db): array
    {
        $base = self::condPendiente();
        $sql = "
            SELECT
                COUNT(*) FILTER (WHERE COALESCE(reenvio_estado,'') NOT IN ('rechazado','agotado')) AS programados,
                COUNT(*) FILTER (WHERE reenvio_estado = 'agotado')   AS agotados,
                COUNT(*) FILTER (WHERE reenvio_estado = 'rechazado')  AS rechazados,
                COUNT(*)                                              AS total,
                MIN(proximo_intento) FILTER (WHERE COALESCE(reenvio_estado,'') NOT IN ('rechazado','agotado')) AS proximo
            FROM comprobantes_electronicos ce
            WHERE $base";
        $stmt = $db->query($sql);
        $r = $stmt->fetch() ?: [];
        return [
            'programados' => (int)($r['programados'] ?? 0),
            'agotados'    => (int)($r['agotados'] ?? 0),
            'rechazados'  => (int)($r['rechazados'] ?? 0),
            'total'       => (int)($r['total'] ?? 0),
            'proximo'     => $r['proximo'] ?? null,
            'retry_min'   => self::RETRY_MINUTES,
        ];
    }

    // ───────────────────────────────────────────── Envio a SUNAT ──

    /** Reenvio manual de un comprobante (ignora proximo_intento). */
    public static function procesarUno(PDO $db, int $id): array
    {
        self::sunatCargado();
        $comp = self::cargarComprobante($db, $id);
        if (!in_array((string)$comp['codigo_tipo_documento'], self::TIPOS_SUNAT, true)) {
            throw new Exception('Solo factura, boleta o nota de credito se envian a SUNAT.');
        }
        if (self::yaEnviado($comp)) {
            throw new Exception('Este comprobante ya tiene CDR o respuesta de SUNAT.');
        }
        $resultado = self::enviar($db, $comp);
        $estado = self::aplicarResultado($db, $comp, $resultado);
        return ['exito' => empty($resultado['error_nubefact']), 'estado' => $estado,
                'mensaje' => $resultado['mensaje'] ?? $resultado['estado_sunat'] ?? null];
    }

    /** Worker: procesa los comprobantes cuyo proximo_intento ya vencio. Devuelve resumen. */
    public static function procesarDebidos(PDO $db, int $limite = 50): array
    {
        self::sunatCargado();
        $base = self::condPendiente();
        $sql = "
            SELECT ce.id
            FROM comprobantes_electronicos ce
            WHERE $base
              AND COALESCE(ce.reenvio_estado,'') NOT IN ('rechazado','agotado')
              AND ce.intentos < ce.max_intentos
              AND (ce.proximo_intento IS NULL OR ce.proximo_intento <= NOW())
            ORDER BY ce.proximo_intento ASC NULLS FIRST, ce.id ASC
            LIMIT :lim";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
        $stmt->execute();
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $res = ['procesados' => 0, 'aceptados' => 0, 'reprogramados' => 0, 'rechazados' => 0, 'agotados' => 0, 'errores' => 0];
        foreach ($ids as $id) {
            try {
                $comp = self::cargarComprobante($db, (int)$id);
                if (self::yaEnviado($comp)) continue;
                $resultado = self::enviar($db, $comp);
                $estado = self::aplicarResultado($db, $comp, $resultado);
                $res['procesados']++;
                $map = ['aceptado' => 'aceptados', 'programado' => 'reprogramados', 'rechazado' => 'rechazados', 'agotado' => 'agotados'];
                if (isset($map[$estado])) $res[$map[$estado]]++;
            } catch (Throwable $e) {
                $res['errores']++;
            }
        }
        return $res;
    }

    /** Actualiza los campos de reintento segun el resultado. Devuelve el nuevo reenvio_estado. */
    private static function aplicarResultado(PDO $db, array $comp, array $resultado): string
    {
        $id = (int)$comp['id'];
        $intentos = (int)($comp['intentos'] ?? 0) + 1;
        $max = (int)($comp['max_intentos'] ?? 20) ?: 20;

        if (empty($resultado['error_nubefact'])) {
            // Tiene CDR (aceptado / observado). enviar_sunat ya guardo codigo_respuesta y estado.
            $db->prepare("UPDATE comprobantes_electronicos
                          SET intentos=:i, reenvio_estado='aceptado', ultimo_error=NULL,
                              proximo_intento=NULL, updated_at=NOW() WHERE id=:id")
               ->execute([':i' => $intentos, ':id' => $id]);
            return 'aceptado';
        }

        $msg = (string)($resultado['mensaje'] ?? $resultado['estado_sunat'] ?? 'Error al enviar a SUNAT.');
        $permanente = self::esPermanente(self::extraerCodigo($msg));

        if ($permanente) {
            $estado = 'rechazado';
            $proximo = null;
        } elseif ($intentos >= $max) {
            $estado = 'agotado';
            $proximo = null;
        } else {
            $estado = 'programado';
            $proximo = date('Y-m-d H:i:s', time() + self::RETRY_MINUTES * 60);
        }
        $db->prepare("UPDATE comprobantes_electronicos
                      SET intentos=:i, reenvio_estado=:e, ultimo_error=:err,
                          proximo_intento=:p, updated_at=NOW() WHERE id=:id")
           ->execute([':i' => $intentos, ':e' => $estado, ':err' => mb_substr($msg, 0, 500), ':p' => $proximo, ':id' => $id]);
        return $estado;
    }

    /** Codigos 2000-3999 = rechazo del documento (permanente). Resto = transitorio (reintentable). */
    private static function esPermanente(?int $codigo): bool
    {
        return $codigo !== null && $codigo >= 2000 && $codigo <= 3999;
    }

    private static function extraerCodigo(string $msg): ?int
    {
        if (preg_match('/\b(\d{3,4})\b/', $msg, $m)) return (int)$m[1];
        return null;
    }

    public static function yaEnviado(array $comp): bool
    {
        $estado = strtolower(trim((string)($comp['estado'] ?? '')));
        return trim((string)($comp['cdr_path'] ?? '')) !== ''
            || trim((string)($comp['codigo_respuesta'] ?? '')) === '0'
            || str_contains($estado, 'acept')
            || str_contains($estado, 'observ');
    }

    // ───────────────────────── Carga de datos (replicado para autonomia) ──

    private static function cargarComprobante(PDO $db, int $id): array
    {
        $stmt = $db->prepare("
            SELECT ce.*, ov.numero AS orden_numero
            FROM comprobantes_electronicos ce
            LEFT JOIN ordenes_venta ov ON ov.id = ce.orden_venta_id
            WHERE ce.id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) throw new RuntimeException('No se encontro el comprobante.');
        return $row;
    }

    private static function cargarCliente(PDO $db, ?int $clienteId): ?array
    {
        if (!$clienteId) return null;
        $stmt = $db->prepare("
            SELECT id, razon_social, nombre_completo, numero_documento, ruc_dni,
                   tipo_documento_codigo, telefono, direccion
            FROM clientes WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $clienteId]);
        $cliente = $stmt->fetch();
        if (!$cliente) return null;
        $doc = preg_replace('/\D+/', '', (string)($cliente['numero_documento'] ?: $cliente['ruc_dni'] ?: ''));
        $cliente['documento'] = $doc;
        $cliente['ruc'] = strlen($doc) === 11 ? $doc : '';
        $cliente['dni'] = strlen($doc) === 8 ? $doc : '';
        return $cliente;
    }

    private static function cargarItems(PDO $db, int $id): array
    {
        $stmt = $db->prepare("
            SELECT cd.producto_id, cd.descripcion, cd.cantidad, cd.unidad_codigo,
                   cd.codigo_sunat, cd.codigo_interno, cd.valor_unitario, cd.precio_unitario,
                   cd.descuento, cd.valor_total, cd.afectacion_igv_codigo, cd.igv, cd.subtotal, cd.total,
                   p.codigo
            FROM comprobante_detalles cd
            LEFT JOIN productos p ON p.id = cd.producto_id
            WHERE cd.comprobante_electronico_id = :id ORDER BY cd.id");
        $stmt->execute([':id' => $id]);
        $items = $stmt->fetchAll();
        if (!$items) throw new RuntimeException('El comprobante no tiene detalle para enviar a SUNAT.');
        return $items;
    }

    /** Arma los parametros y llama a enviar_sunat() (config/sunat.php). */
    private static function enviar(PDO $db, array $comp): array
    {
        $tipo = match (trim((string)$comp['codigo_tipo_documento'])) {
            '01' => 'factura',
            '07' => 'nota_credito',
            default => 'boleta',
        };
        $cliente = self::cargarCliente($db, !empty($comp['cliente_id']) ? (int)$comp['cliente_id'] : null);
        $items = self::cargarItems($db, (int)$comp['id']);
        $numeroEntero = (int)preg_replace('/\D+/', '', (string)$comp['correlativo']);

        return enviar_sunat($db, [
            'id' => (int)$comp['id'],
            'tipo' => $tipo,
            'serie' => (string)$comp['serie'],
            'numero' => $numeroEntero,
            'numero_completo' => (string)$comp['numero'],
            'codigo_tipo_documento' => (string)$comp['codigo_tipo_documento'],
        ], $cliente, $items, [
            'numero_venta' => (string)($comp['orden_numero'] ?? ''),
            'tipo_pago' => ((float)($comp['monto_credito'] ?? 0) > 0) ? 'credito' : 'efectivo',
            'sunat_forma_pago' => ((float)($comp['monto_credito'] ?? 0) > 0) ? 'Credito' : 'Contado',
            'fecha_emision' => (string)($comp['fecha_emision'] ?? date('Y-m-d')),
            'hora_emision' => (string)($comp['hora_emision'] ?? date('H:i:s')),
            'moneda' => (string)($comp['moneda_codigo'] ?? 'PEN'),
            'totales' => ['total' => (float)($comp['total'] ?? 0)],
            'documento_modificado_tipo_documento_codigo' => (string)($comp['documento_modificado_tipo_documento_codigo'] ?? ''),
            'documento_modificado_numero_completo' => (string)($comp['documento_modificado_numero_completo'] ?? ''),
            'motivo_codigo' => (string)($comp['codigo_tipo_nota_credito'] ?? ''),
            'motivo_descripcion' => (string)($comp['motivo_nota_credito'] ?? ''),
        ]);
    }
}

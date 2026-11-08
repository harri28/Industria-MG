<?php
/**
 * MODELO — Comprobante (lectura de comprobantes_electronicos para el modulo Facturacion).
 * Solo SQL/PDO + reglas de lectura. Sin HTML ni routing.
 * Portatil: depende unicamente del esquema comprobantes_electronicos / comprobante_detalles.
 * Ver ARQUITECTURA_MVC.md
 */
class Comprobante
{
    /** Tipos de documento que se consideran "venta" (boleta, factura, nota de venta). */
    private const TIPOS_VENTA = ['01', '03', '02'];
    /** Tipos nota de credito / debito. */
    private const TIPOS_NOTA  = ['07', '08'];

    /** Construye WHERE por rango de fechas + busqueda libre. */
    private static function filtros(array $f, array &$params): array
    {
        $cond = [];
        $desde = trim((string)($f['desde'] ?? ''));
        $hasta = trim((string)($f['hasta'] ?? ''));
        $q     = trim((string)($f['q'] ?? ''));
        if ($desde !== '') { $cond[] = 'ce.fecha_emision >= :desde'; $params[':desde'] = $desde; }
        if ($hasta !== '') { $cond[] = 'ce.fecha_emision <= :hasta'; $params[':hasta'] = $hasta; }
        if ($q !== '') {
            $cond[] = '(ce.numero ILIKE :q OR cl.razon_social ILIKE :q OR cl.nombre_completo ILIKE :q OR cl.numero_documento ILIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }
        return $cond;
    }

    /** SELECT base con el nombre y documento del cliente. */
    private static function baseSelect(): string
    {
        return "
            SELECT ce.id, ce.serie, ce.correlativo, ce.numero, ce.codigo_tipo_documento,
                   ce.fecha_emision, ce.hora_emision, ce.total, ce.moneda_codigo,
                   ce.estado, ce.codigo_respuesta, ce.mensaje_respuesta,
                   ce.xml_path, ce.cdr_path, ce.pdf_path, ce.qr, ce.anulado,
                   COALESCE(NULLIF(cl.razon_social,''), NULLIF(cl.nombre_completo,''), 'Cliente General') AS cliente_nombre,
                   COALESCE(NULLIF(cl.numero_documento,''), NULLIF(cl.ruc_dni,''), '-') AS cliente_doc
            FROM comprobantes_electronicos ce
            LEFT JOIN clientes cl ON cl.id = ce.cliente_id
        ";
    }

    /** Reporte de Ventas: boletas, facturas y notas de venta. */
    public static function reporteVentas(PDO $db, array $f): array
    {
        $params = [];
        $cond = self::filtros($f, $params);
        $tipos = "'" . implode("','", self::TIPOS_VENTA) . "'";
        $cond[] = "ce.codigo_tipo_documento IN ($tipos)";
        $sql = self::baseSelect() . ' WHERE ' . implode(' AND ', $cond)
             . ' ORDER BY ce.fecha_emision DESC, ce.id DESC LIMIT 2000';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Notas de credito / debito. */
    public static function notasCredito(PDO $db, array $f): array
    {
        $params = [];
        $cond = self::filtros($f, $params);
        $tipos = "'" . implode("','", self::TIPOS_NOTA) . "'";
        $cond[] = "ce.codigo_tipo_documento IN ($tipos)";
        $sql = "
            SELECT ce.id, ce.serie, ce.correlativo, ce.numero, ce.codigo_tipo_documento,
                   ce.fecha_emision, ce.total, ce.estado, ce.codigo_respuesta, ce.mensaje_respuesta,
                   ce.xml_path, ce.cdr_path, ce.pdf_path, ce.anulado,
                   ce.motivo_nota_credito, ce.motivo_nota_debito,
                   ce.documento_modificado_numero_completo AS doc_modificado,
                   COALESCE(NULLIF(cl.razon_social,''), NULLIF(cl.nombre_completo,''), 'Cliente General') AS cliente_nombre
            FROM comprobantes_electronicos ce
            LEFT JOIN clientes cl ON cl.id = ce.cliente_id
            WHERE " . implode(' AND ', $cond) . "
            ORDER BY ce.fecha_emision DESC, ce.id DESC LIMIT 2000";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Rentabilidad: ingreso neto (valor_total de las lineas) vs costo (cantidad * precio_promedio
     * del producto) por comprobante de venta aceptado/emitido. Devuelve totales + detalle por dia.
     */
    public static function rentabilidad(PDO $db, array $f): array
    {
        $params = [];
        $cond = self::filtros($f, $params);
        $tipos = "'" . implode("','", self::TIPOS_VENTA) . "'";
        $cond[] = "ce.codigo_tipo_documento IN ($tipos)";
        $cond[] = "ce.anulado = FALSE";
        $where = implode(' AND ', $cond);

        // Totales globales
        $sqlTot = "
            SELECT
                COALESCE(SUM(cd.valor_total), 0)                      AS ingreso,
                COALESCE(SUM(cd.cantidad * COALESCE(p.precio_promedio,0)), 0) AS costo,
                COUNT(DISTINCT ce.id)                                 AS comprobantes
            FROM comprobantes_electronicos ce
            JOIN comprobante_detalles cd ON cd.comprobante_electronico_id = ce.id
            LEFT JOIN productos p        ON p.id = cd.producto_id
            LEFT JOIN clientes cl        ON cl.id = ce.cliente_id
            WHERE $where";
        $stmt = $db->prepare($sqlTot);
        $stmt->execute($params);
        $tot = $stmt->fetch() ?: ['ingreso' => 0, 'costo' => 0, 'comprobantes' => 0];

        $ingreso = (float)$tot['ingreso'];
        $costo   = (float)$tot['costo'];
        $margen  = round($ingreso - $costo, 2);
        $margenPct = $ingreso > 0 ? round(($margen / $ingreso) * 100, 2) : 0;

        // Detalle por dia
        $sqlDia = "
            SELECT ce.fecha_emision AS fecha,
                   COALESCE(SUM(cd.valor_total), 0) AS ingreso,
                   COALESCE(SUM(cd.cantidad * COALESCE(p.precio_promedio,0)), 0) AS costo
            FROM comprobantes_electronicos ce
            JOIN comprobante_detalles cd ON cd.comprobante_electronico_id = ce.id
            LEFT JOIN productos p        ON p.id = cd.producto_id
            LEFT JOIN clientes cl        ON cl.id = ce.cliente_id
            WHERE $where
            GROUP BY ce.fecha_emision
            ORDER BY ce.fecha_emision DESC
            LIMIT 120";
        $stmt = $db->prepare($sqlDia);
        $stmt->execute($params);
        $dias = array_map(function ($r) {
            $ing = (float)$r['ingreso'];
            $cos = (float)$r['costo'];
            return [
                'fecha'   => $r['fecha'],
                'ingreso' => round($ing, 2),
                'costo'   => round($cos, 2),
                'margen'  => round($ing - $cos, 2),
                'margen_pct' => $ing > 0 ? round((($ing - $cos) / $ing) * 100, 2) : 0,
            ];
        }, $stmt->fetchAll());

        return [
            'ingreso'      => round($ingreso, 2),
            'costo'        => round($costo, 2),
            'margen'       => $margen,
            'margen_pct'   => $margenPct,
            'comprobantes' => (int)$tot['comprobantes'],
            'dias'         => $dias,
        ];
    }

    /**
     * Comprobante + items + empresa emisora (para impresión).
     * Incluye el logo en base64 si existe en disco.
     */
    public static function detalleConItems(PDO $db, int $id): ?array
    {
        $stmt = $db->prepare("
            SELECT ce.*,
                   COALESCE(NULLIF(cl.razon_social,''), NULLIF(cl.nombre_completo,''), 'Cliente General') AS cliente_nombre,
                   COALESCE(NULLIF(cl.numero_documento,''), NULLIF(cl.ruc_dni,''), '-') AS cliente_doc
            FROM comprobantes_electronicos ce
            LEFT JOIN clientes cl ON cl.id = ce.cliente_id
            WHERE ce.id = :id");
        $stmt->execute([':id' => $id]);
        $cmp = $stmt->fetch();
        if (!$cmp) return null;

        $stmt = $db->prepare("SELECT * FROM comprobante_detalles WHERE comprobante_electronico_id = :id ORDER BY id");
        $stmt->execute([':id' => $id]);
        $items = $stmt->fetchAll();

        $emp = $db->query("SELECT razon_social, nombre_comercial, ruc, direccion_fiscal, telefono, logo_path FROM empresa_emisora WHERE activo ORDER BY id LIMIT 1")->fetch() ?: [];

        $logoB64 = null;
        $logoCandidates = [];
        if (!empty($emp['logo_path'])) {
            $logoCandidates[] = __DIR__ . '/../../../../' . ltrim($emp['logo_path'], '/');
        }
        $logoCandidates[] = __DIR__ . '/../../../../assets/iconos/logo.jpeg';
        foreach ($logoCandidates as $lf) {
            if (file_exists($lf)) {
                $ext = strtolower(pathinfo($lf, PATHINFO_EXTENSION));
                $mime = match($ext) { 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp', 'svg' => 'image/svg+xml', default => 'image/jpeg' };
                $logoB64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($lf));
                break;
            }
        }

        return [
            'comprobante' => $cmp,
            'items'       => $items,
            'empresa'     => [
                'nombre'       => $emp['nombre_comercial'] ?: ($emp['razon_social'] ?? 'IndustriaMG'),
                'razon_social' => $emp['razon_social'] ?? '',
                'ruc'          => $emp['ruc'] ?? '',
                'direccion'    => $emp['direccion_fiscal'] ?? '',
                'telefono'     => $emp['telefono'] ?? '',
                'logo'         => $logoB64,
            ],
        ];
    }
}

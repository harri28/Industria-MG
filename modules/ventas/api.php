<?php
ini_set('display_errors', 0);
error_reporting(0);
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
startAppSession();
require_once __DIR__ . '/../../config/sunat.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? $action;
} else {
    $input = $_GET;
}

$db = getDB();

function ventaDocumentoMeta(string $tipo): array
{
    $map = [
        'factura' => ['codigo' => '01', 'descripcion' => 'Factura electronica'],
        'boleta' => ['codigo' => '03', 'descripcion' => 'Boleta electronica'],
        'nota_venta' => ['codigo' => '02', 'descripcion' => 'Nota de venta'],
    ];
    return $map[$tipo] ?? $map['nota_venta'];
}

function ventaCalcularLinea(array $producto, float $cantidad, float $precioUnitario, float $descuentoPct): array
{
    $afectacion = trim((string)($producto['afectacion_igv_codigo'] ?? '10')) ?: '10';
    $porcentajeIgv = $afectacion === '10' ? max(0, (float)($producto['porcentaje_igv'] ?? 18)) : 0;
    $incluyeIgv = filter_var($producto['incluye_igv'] ?? true, FILTER_VALIDATE_BOOLEAN);
    $totalBruto = round(max(0, $cantidad) * max(0, $precioUnitario), 2);
    $descuento = round($totalBruto * (max(0, min(100, $descuentoPct)) / 100), 2);
    $totalLinea = round($totalBruto - $descuento, 2);

    if ($porcentajeIgv > 0 && $incluyeIgv) {
        $valorTotal = round($totalLinea / (1 + ($porcentajeIgv / 100)), 2);
        $igv = round($totalLinea - $valorTotal, 2);
    } elseif ($porcentajeIgv > 0) {
        $valorTotal = $totalLinea;
        $igv = round($valorTotal * ($porcentajeIgv / 100), 2);
        $totalLinea = round($valorTotal + $igv, 2);
    } else {
        $valorTotal = $totalLinea;
        $igv = 0.00;
    }

    return [
        'valor_unitario' => $cantidad > 0 ? round($valorTotal / $cantidad, 10) : 0,
        'precio_unitario' => $precioUnitario,
        'valor_total' => $valorTotal,
        'igv' => $igv,
        'descuento' => $descuento,
        'subtotal' => $valorTotal,
        'total' => $totalLinea,
        'afectacion_igv_codigo' => $afectacion,
        'unidad_codigo' => trim((string)($producto['unidad_codigo'] ?? 'NIU')) ?: 'NIU',
        'codigo_sunat' => trim((string)($producto['codigo_sunat'] ?? '00000000')) ?: '00000000',
        'codigo_interno' => trim((string)($producto['codigo_interno'] ?? $producto['codigo'] ?? '')),
    ];
}

function ventaSincronizarProductoMaquinaria(PDO $db, int $maquinariaId): ?int
{
    $stmt = $db->prepare("SELECT * FROM maquinarias WHERE id = :id");
    $stmt->execute([':id' => $maquinariaId]);
    $maq = $stmt->fetch();
    if (!$maq) return null;

    $categoriaId = $db->query("
        SELECT id FROM categorias_producto
        WHERE LOWER(nombre) = 'maquinarias'
        ORDER BY id LIMIT 1
    ")->fetchColumn();
    if (!$categoriaId) {
        $stmtCat = $db->query("INSERT INTO categorias_producto (nombre, descripcion, activo) VALUES ('Maquinarias', 'Maquinas fabricadas listas para venta', TRUE) RETURNING id");
        $categoriaId = $stmtCat->fetchColumn();
    }

    $codigo = trim((string)($maq['codigo'] ?? '')) ?: ('MQ-' . str_pad((string)$maquinariaId, 6, '0', STR_PAD_LEFT));
    $nombre = trim((string)($maq['modelo'] ?? 'Maquinaria')) ?: 'Maquinaria';
    $descripcion = trim((string)($maq['descripcion'] ?? '')) ?: 'Maquinaria fabricada lista para venta';
    $costo = round((float)($maq['costo_total'] ?? 0), 2);
    $precio = round((float)($maq['precio_venta'] ?? 0), 2);
    $stock = ($maq['estado_venta'] ?? 'disponible') === 'vendida' ? 0 : 1;

    if (!empty($maq['producto_id'])) {
        $stmt = $db->prepare("
            UPDATE productos
            SET codigo = :codigo,
                nombre = :nombre,
                descripcion = :descripcion,
                categoria_id = :categoria_id,
                precio_promedio = :costo,
                precio_venta = :precio,
                codigo_interno = :codigo,
                unidad = 'unidad',
                unidad_codigo = 'NIU',
                afectacion_igv_codigo = '10',
                porcentaje_igv = 18.00,
                incluye_igv = TRUE,
                product_type = 'machine',
                stock_actual = CASE WHEN stock_actual < 0 THEN 0 ELSE stock_actual END,
                updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            ':id' => (int)$maq['producto_id'],
            ':codigo' => $codigo,
            ':nombre' => $nombre,
            ':descripcion' => $descripcion,
            ':categoria_id' => $categoriaId ?: null,
            ':costo' => $costo,
            ':precio' => $precio,
        ]);
        return (int)$maq['producto_id'];
    }

    $codigoProducto = $codigo;
    $existe = $db->prepare("SELECT id FROM productos WHERE codigo = :codigo LIMIT 1");
    $existe->execute([':codigo' => $codigoProducto]);
    if ($existe->fetchColumn()) {
        $codigoProducto = $codigo . '-' . $maquinariaId;
    }

    $stmt = $db->prepare("
        INSERT INTO productos
        (codigo, nombre, descripcion, categoria_id, unidad, stock_actual, stock_minimo, stock_maximo,
         precio_promedio, precio_venta, metodo_valuacion, es_repuesto, codigo_interno, codigo_sunat,
         unidad_codigo, afectacion_igv_codigo, porcentaje_igv, incluye_igv, product_type, activo)
        VALUES
        (:codigo, :nombre, :descripcion, :categoria_id, 'unidad', :stock, 0, 1,
         :costo, :precio, 'promedio', FALSE, :codigo_interno, '00000000',
         'NIU', '10', 18.00, TRUE, 'machine', TRUE)
        RETURNING id
    ");
    $stmt->execute([
        ':codigo' => $codigoProducto,
        ':nombre' => $nombre,
        ':descripcion' => $descripcion,
        ':categoria_id' => $categoriaId ?: null,
        ':stock' => $stock,
        ':costo' => $costo,
        ':precio' => $precio,
        ':codigo_interno' => $codigo,
    ]);
    $productoId = (int)$stmt->fetchColumn();
    $db->prepare("UPDATE maquinarias SET producto_id = :producto_id WHERE id = :id")
       ->execute([':producto_id' => $productoId, ':id' => $maquinariaId]);

    return $productoId;
}

function ventaAplicarDescuentoGlobal(array $lineas, float $descuento): array
{
    $descuento = round(max(0, $descuento), 2);
    if ($descuento <= 0 || empty($lineas)) {
        return [$lineas, 0.00];
    }

    $totalBruto = round(array_sum(array_map(fn($l) => (float)$l['total'], $lineas)), 2);
    if ($descuento > $totalBruto + 0.01) {
        throw new Exception('El descuento no puede ser mayor al total de la venta.');
    }

    $restante = $descuento;
    $ultimo = count($lineas) - 1;
    foreach ($lineas as $i => &$linea) {
        $lineTotal = (float)$linea['total'];
        $dtoLinea = $i === $ultimo ? $restante : round(($lineTotal / $totalBruto) * $descuento, 2);
        $restante = round($restante - $dtoLinea, 2);
        $nuevoTotal = round(max(0, $lineTotal - $dtoLinea), 2);
        $ratio = $lineTotal > 0 ? $nuevoTotal / $lineTotal : 0;

        $linea['descuento'] = round((float)$linea['descuento'] + $dtoLinea, 2);
        $linea['valor_total'] = round((float)$linea['valor_total'] * $ratio, 2);
        $linea['igv'] = round((float)$linea['igv'] * $ratio, 2);
        $linea['subtotal'] = $linea['valor_total'];
        $linea['total'] = $nuevoTotal;
        $linea['valor_unitario'] = (float)$linea['cantidad'] > 0 ? round($linea['valor_total'] / (float)$linea['cantidad'], 10) : 0;
    }
    unset($linea);

    return [$lineas, $descuento];
}

function ventaResponseCode(?string $json): ?string
{
    $data = json_decode(trim((string)$json), true);
    if (!is_array($data)) return null;
    return isset($data['cdr']['response_code']) ? trim((string)$data['cdr']['response_code']) : null;
}

function ventaComprobanteYaEnviado(array $comp): bool
{
    $estado = strtolower(trim((string)($comp['estado'] ?? '')));
    return trim((string)($comp['cdr_path'] ?? '')) !== ''
        || ventaResponseCode($comp['sunat_response_json'] ?? null) !== null
        || str_contains($estado, 'acept')
        || str_contains($estado, 'observ');
}

function ventaSerieNotaCredito(array $origen): string
{
    return trim((string)($origen['codigo_tipo_documento'] ?? '')) === '01' ? 'FC01' : 'BC01';
}

function ventaCargarComprobante(PDO $db, int $comprobanteId): array
{
    $stmt = $db->prepare("
        SELECT ce.*,
               ov.numero AS orden_numero,
               ov.estado AS orden_estado,
               ov.fecha_emision AS orden_fecha_emision,
               ov.created_at AS orden_created_at,
               ov.total AS orden_total
        FROM comprobantes_electronicos ce
        LEFT JOIN ordenes_venta ov ON ov.id = ce.orden_venta_id
        WHERE ce.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $comprobanteId]);
    $row = $stmt->fetch();
    if (!$row) {
        throw new RuntimeException('No se encontro el comprobante seleccionado.');
    }
    return $row;
}

function ventaCargarCliente(PDO $db, ?int $clienteId): ?array
{
    if (!$clienteId) return null;
    $stmt = $db->prepare("
        SELECT id, razon_social, nombre_completo, numero_documento, ruc_dni,
               tipo_documento_codigo, telefono, direccion
        FROM clientes
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $clienteId]);
    $cliente = $stmt->fetch();
    if (!$cliente) return null;

    $doc = preg_replace('/\D+/', '', (string)($cliente['numero_documento'] ?: $cliente['ruc_dni'] ?: ''));
    $cliente['documento'] = $doc;
    $cliente['ruc'] = strlen($doc) === 11 ? $doc : '';
    $cliente['dni'] = strlen($doc) === 8 ? $doc : '';
    return $cliente;
}

function ventaCargarItemsComprobante(PDO $db, int $comprobanteId): array
{
    $stmt = $db->prepare("
        SELECT cd.producto_id, cd.descripcion, cd.cantidad, cd.unidad_codigo,
               cd.codigo_sunat, cd.codigo_interno, cd.valor_unitario, cd.precio_unitario,
               cd.descuento, cd.valor_total, cd.afectacion_igv_codigo, cd.igv, cd.subtotal, cd.total,
               p.codigo
        FROM comprobante_detalles cd
        LEFT JOIN productos p ON p.id = cd.producto_id
        WHERE cd.comprobante_electronico_id = :id
        ORDER BY cd.id
    ");
    $stmt->execute([':id' => $comprobanteId]);
    $items = $stmt->fetchAll();
    if (!$items) {
        throw new RuntimeException('El comprobante no tiene detalle para enviar a SUNAT.');
    }
    return $items;
}

function ventaEnviarComprobanteSunat(PDO $db, array $comp): array
{
    $tipo = match (trim((string)$comp['codigo_tipo_documento'])) {
        '01' => 'factura',
        '07' => 'nota_credito',
        default => 'boleta',
    };
    $cliente = ventaCargarCliente($db, !empty($comp['cliente_id']) ? (int)$comp['cliente_id'] : null);
    $items = ventaCargarItemsComprobante($db, (int)$comp['id']);
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

try { switch ($action) {

    // ================================================================
    // DASHBOARD
    // ================================================================
    case 'dashboard':
        $mes_inicio = date('Y-m-01');
        $mes_fin    = date('Y-m-t');
        $stmt = $db->prepare("
            SELECT
                (SELECT COUNT(*) FROM clientes WHERE activo) AS total_clientes,
                (SELECT COUNT(*) FROM clientes WHERE activo AND estado_crm = 'prospecto') AS prospectos,
                (SELECT COUNT(*) FROM clientes WHERE activo AND estado_crm = 'activo') AS clientes_activos,
                (SELECT COUNT(*) FROM cotizaciones WHERE fecha_emision BETWEEN :mes_inicio AND :mes_fin) AS cotizaciones_mes,
                (SELECT COUNT(*) FROM cotizaciones WHERE estado = 'aprobada') AS cotizaciones_aprobadas,
                (SELECT COALESCE(SUM(total),0) FROM cotizaciones WHERE estado = 'aprobada') AS revenue_total,
                (SELECT COALESCE(SUM(total),0) FROM cotizaciones WHERE estado = 'aprobada'
                    AND fecha_emision BETWEEN :mes_inicio2 AND :mes_fin2) AS revenue_mes,
                (SELECT COUNT(*) FROM garantias WHERE estado = 'activa' AND fecha_fin < CURRENT_DATE + 30) AS garantias_por_vencer
        ");
        $stmt->execute([':mes_inicio' => $mes_inicio, ':mes_fin' => $mes_fin, ':mes_inicio2' => $mes_inicio, ':mes_fin2' => $mes_fin]);
        $kpis = $stmt->fetch();

        $pipeline = $db->query("
            SELECT estado, COUNT(*) AS total, COALESCE(SUM(total),0) AS monto
            FROM cotizaciones GROUP BY estado ORDER BY estado")->fetchAll();

        $ultimas = $db->query("
            SELECT c.numero, c.estado, c.total, c.created_at,
                   cl.razon_social AS cliente
            FROM cotizaciones c
            LEFT JOIN clientes cl ON cl.id = c.cliente_id
            ORDER BY c.created_at DESC LIMIT 8")->fetchAll();

        jsonResponse(['ok'=>true, 'kpis'=>$kpis, 'pipeline'=>$pipeline, 'ultimas'=>$ultimas]);

    // ================================================================
    // CLIENTES
    // ================================================================
    case 'clientes_listar':
        $where = ['c.activo = TRUE'];
        $params = [];
        if (!empty($input['buscar'])) {
            $where[] = "(c.razon_social ILIKE :q OR c.ruc_dni ILIKE :q OR c.email ILIKE :q OR c.telefono ILIKE :q)";
            $params[':q'] = '%'.$input['buscar'].'%';
        }
        if (!empty($input['estado'])) { $where[] = "c.estado_crm = :estado"; $params[':estado'] = $input['estado']; }
        $stmt = $db->prepare("
            SELECT c.*,
                COUNT(DISTINCT co.id) AS total_cotizaciones,
                COALESCE(SUM(co.total) FILTER (WHERE co.estado='aprobada'),0) AS revenue
            FROM clientes c
            LEFT JOIN cotizaciones co ON co.cliente_id = c.id
            WHERE ".implode(' AND ',$where)."
            GROUP BY c.id ORDER BY c.razon_social");
        $stmt->execute($params);
        jsonResponse(['ok'=>true,'clientes'=>$stmt->fetchAll()]);

    case 'cliente_obtener':
        $stmt = $db->prepare("SELECT * FROM clientes WHERE id = :id");
        $stmt->execute([':id'=>(int)$input['id']]);
        $cliente = $stmt->fetch();

        $maquinarias = $db->prepare("SELECT m.*, p.codigo AS pry_codigo FROM maquinarias m LEFT JOIN proyectos p ON p.id = m.proyecto_id WHERE m.cliente_id = :id AND m.activo ORDER BY m.created_at DESC");
        $maquinarias->execute([':id'=>(int)$input['id']]);

        $cotizaciones = $db->prepare("SELECT * FROM cotizaciones WHERE cliente_id = :id ORDER BY created_at DESC LIMIT 10");
        $cotizaciones->execute([':id'=>(int)$input['id']]);

        jsonResponse(['ok'=>true,'cliente'=>$cliente,'maquinarias'=>$maquinarias->fetchAll(),'cotizaciones'=>$cotizaciones->fetchAll()]);

    case 'cliente_guardar':
        if (empty($input['razon_social'])) jsonResponse(['error'=>'La razon social es obligatoria.'],400);
        if (!empty($input['id'])) {
            $db->prepare("UPDATE clientes SET tipo=:tipo, razon_social=:rs, ruc_dni=:ruc, contacto_nombre=:cn,
                email=:email, telefono=:tel, whatsapp=:wa, direccion=:dir, estado_crm=:estado, notas=:notas
                WHERE id=:id")
               ->execute([':id'=>(int)$input['id'], ':tipo'=>$input['tipo']??'empresa',
                ':rs'=>trim($input['razon_social']), ':ruc'=>($input['ruc_dni']??null)?:null,
                ':cn'=>($input['contacto_nombre']??null)?:null, ':email'=>($input['email']??null)?:null,
                ':tel'=>($input['telefono']??null)?:null, ':wa'=>($input['whatsapp']??null)?:null,
                ':dir'=>($input['direccion']??null)?:null, ':estado'=>$input['estado_crm']??'prospecto',
                ':notas'=>($input['notas']??null)?:null]);
        } else {
            $stmt = $db->prepare("INSERT INTO clientes (tipo,razon_social,ruc_dni,contacto_nombre,email,telefono,whatsapp,direccion,estado_crm,notas)
                VALUES (:tipo,:rs,:ruc,:cn,:email,:tel,:wa,:dir,:estado,:notas) RETURNING id");
            $stmt->execute([':tipo'=>$input['tipo']??'empresa', ':rs'=>trim($input['razon_social']),
                ':ruc'=>($input['ruc_dni']??null)?:null, ':cn'=>($input['contacto_nombre']??null)?:null,
                ':email'=>($input['email']??null)?:null, ':tel'=>($input['telefono']??null)?:null,
                ':wa'=>($input['whatsapp']??null)?:null, ':dir'=>($input['direccion']??null)?:null,
                ':estado'=>$input['estado_crm']??'prospecto', ':notas'=>($input['notas']??null)?:null]);
        }
        jsonResponse(['ok'=>true]);

    case 'cliente_eliminar':
        $db->prepare("UPDATE clientes SET activo=FALSE WHERE id=:id")->execute([':id'=>(int)($input['id']??0)]);
        jsonResponse(['ok'=>true]);

    // ================================================================
    // COTIZACIONES
    // ================================================================
    case 'cotizaciones_listar':
        $where = ['1=1'];
        $params = [];
        if (!empty($input['buscar'])) {
            $where[] = "(c.numero ILIKE :q OR cl.razon_social ILIKE :q)";
            $params[':q'] = '%'.$input['buscar'].'%';
        }
        if (!empty($input['estado'])) { $where[] = "c.estado=:estado"; $params[':estado']=$input['estado']; }
        if (!empty($input['cliente_id'])) { $where[] = "c.cliente_id=:cid"; $params[':cid']=(int)$input['cliente_id']; }
        $stmt = $db->prepare("
            SELECT c.*, cl.razon_social AS cliente_nombre, u.nombre AS usuario_nombre
            FROM cotizaciones c
            LEFT JOIN clientes cl ON cl.id = c.cliente_id
            LEFT JOIN usuarios u  ON u.id  = c.usuario_id
            WHERE ".implode(' AND ',$where)."
            ORDER BY c.created_at DESC LIMIT 100");
        $stmt->execute($params);
        jsonResponse(['ok'=>true,'cotizaciones'=>$stmt->fetchAll()]);

    case 'cotizacion_obtener':
        $id = (int)$input['id'];
        $stmt = $db->prepare("SELECT c.*, cl.razon_social AS cliente_nombre FROM cotizaciones c LEFT JOIN clientes cl ON cl.id=c.cliente_id WHERE c.id=:id");
        $stmt->execute([':id'=>$id]);
        $cot = $stmt->fetch();

        $stmt = $db->prepare("SELECT * FROM cotizacion_detalles WHERE cotizacion_id=:id ORDER BY id");
        $stmt->execute([':id'=>$id]);
        $detalles = $stmt->fetchAll();

        jsonResponse(['ok'=>true,'cotizacion'=>$cot,'detalles'=>$detalles]);

    case 'cotizacion_guardar':
        $db->beginTransaction();
        $detalles = $input['detalles'] ?? [];
        $subtotal = array_sum(array_column($detalles,'subtotal'));
        $igv      = round($subtotal * IGV, 2);
        $total    = $subtotal + $igv;

        $fecha_vigencia = !empty($input['fecha_emision'])
            ? date('Y-m-d', strtotime($input['fecha_emision'] . ' + '.((int)($input['vigencia_dias']??30)).' days'))
            : date('Y-m-d', strtotime('+'.((int)($input['vigencia_dias']??30)).' days'));

        if (!empty($input['id'])) {
            $db->prepare("UPDATE cotizaciones SET cliente_id=:cid, estado=:estado, vigencia_dias=:vd,
                fecha_emision=:fe, fecha_vigencia=:fv, observaciones=:obs, subtotal=:sub, igv=:igv, total=:total
                WHERE id=:id")
               ->execute([':id'=>(int)$input['id'], ':cid'=>($input['cliente_id']??null)?:null,
                ':estado'=>$input['estado']??'borrador', ':vd'=>(int)($input['vigencia_dias']??30),
                ':fe'=>($input['fecha_emision']??null)?:date('Y-m-d'), ':fv'=>$fecha_vigencia,
                ':obs'=>($input['observaciones']??null)?:null,
                ':sub'=>$subtotal, ':igv'=>$igv, ':total'=>$total]);
            $db->prepare("DELETE FROM cotizacion_detalles WHERE cotizacion_id=:id")->execute([':id'=>(int)$input['id']]);
            $cot_id = (int)$input['id'];
        } else {
            $numero = generarCodigo('COT-', 'cotizaciones', 'numero');
            $stmt = $db->prepare("INSERT INTO cotizaciones (numero,cliente_id,estado,vigencia_dias,fecha_emision,fecha_vigencia,observaciones,subtotal,igv,total)
                VALUES (:num,:cid,:estado,:vd,:fe,:fv,:obs,:sub,:igv,:total) RETURNING id");
            $stmt->execute([':num'=>$numero, ':cid'=>($input['cliente_id']??null)?:null,
                ':estado'=>$input['estado']??'borrador', ':vd'=>(int)($input['vigencia_dias']??30),
                ':fe'=>($input['fecha_emision']??null)?:date('Y-m-d'), ':fv'=>$fecha_vigencia,
                ':obs'=>($input['observaciones']??null)?:null,
                ':sub'=>$subtotal, ':igv'=>$igv, ':total'=>$total]);
            $cot_id = (int)$stmt->fetchColumn();
        }

        $ins = $db->prepare("INSERT INTO cotizacion_detalles (cotizacion_id,descripcion,cantidad,precio_unitario,descuento,subtotal) VALUES (:cid,:desc,:qty,:pu,:dto,:sub)");
        foreach ($detalles as $d) {
            if (empty($d['descripcion'])) continue;
            $ins->execute([':cid'=>$cot_id, ':desc'=>$d['descripcion'], ':qty'=>(float)$d['cantidad'],
                ':pu'=>(float)$d['precio_unitario'], ':dto'=>(float)($d['descuento']??0), ':sub'=>(float)$d['subtotal']]);
        }
        $db->commit();
        jsonResponse(['ok'=>true,'id'=>$cot_id]);

    case 'cotizacion_estado':
        $allowed = ['borrador','enviada','aprobada','rechazada','vencida'];
        if (!in_array($input['estado']??'',$allowed)) jsonResponse(['error'=>'Estado invalido.'],400);
        $db->prepare("UPDATE cotizaciones SET estado=:estado WHERE id=:id")
           ->execute([':estado'=>$input['estado'],':id'=>(int)($input['id']??0)]);
        // Si se aprueba, actualizar cliente a activo
        if ($input['estado'] === 'aprobada') {
            $row = $db->prepare("SELECT cliente_id FROM cotizaciones WHERE id=:id");
            $row->execute([':id'=>(int)$input['id']]);
            $cid = $row->fetchColumn();
            if ($cid) $db->prepare("UPDATE clientes SET estado_crm='activo' WHERE id=:id AND estado_crm='prospecto'")->execute([':id'=>$cid]);
        }
        jsonResponse(['ok'=>true]);

    case 'cotizacion_eliminar':
        $db->prepare("DELETE FROM cotizaciones WHERE id=:id")->execute([':id'=>(int)($input['id']??0)]);
        jsonResponse(['ok'=>true]);

    // ================================================================
    // MAQUINARIAS
    // ================================================================
    case 'maquinarias_listar':
        $where = ['m.activo=TRUE'];
        $params = [];
        if (!empty($input['cliente_id'])) { $where[]="m.cliente_id=:cid"; $params[':cid']=(int)$input['cliente_id']; }
        if (!empty($input['buscar'])) {
            $where[]="(m.modelo ILIKE :q OR m.numero_serie ILIKE :q OR m.codigo ILIKE :q OR cl.razon_social ILIKE :q OR p.nombre ILIKE :q)";
            $params[':q']='%'.$input['buscar'].'%';
        }
        $stmt = $db->prepare("
            SELECT m.*, cl.razon_social AS cliente_nombre,
                   p.codigo AS producto_codigo,
                   p.nombre AS producto_nombre,
                   p.stock_actual,
                   COALESCE(NULLIF(m.precio_venta, 0), p.precio_venta, 0) AS precio_venta_calc,
                   COALESCE(NULLIF(m.costo_total, 0), p.precio_promedio, 0) AS costo_total_calc,
                   CASE WHEN m.garantia_hasta >= CURRENT_DATE THEN 'vigente'
                        WHEN m.garantia_hasta IS NULL THEN 'sin_garantia'
                        ELSE 'vencida' END AS estado_garantia,
                   (m.garantia_hasta - CURRENT_DATE) AS dias_garantia
            FROM maquinarias m
            LEFT JOIN clientes cl ON cl.id=m.cliente_id
            LEFT JOIN productos p ON p.id=m.producto_id
            WHERE ".implode(' AND ',$where)."
            ORDER BY m.created_at DESC");
        $stmt->execute($params);
        jsonResponse(['ok'=>true,'maquinarias'=>$stmt->fetchAll()]);

    case 'maquinaria_guardar':
        $garantia_hasta = null;
        if (!empty($input['fecha_venta']) && !empty($input['garantia_meses'])) {
            $garantia_hasta = date('Y-m-d', strtotime($input['fecha_venta'].' +'.(int)$input['garantia_meses'].' months'));
        }
        $estadoVenta = trim((string)($input['estado_venta'] ?? ''));
        if ($estadoVenta === '') {
            $estadoVenta = !empty($input['fecha_venta']) || !empty($input['cliente_id']) ? 'vendida' : 'disponible';
        }
        $costoTotal = round((float)($input['costo_total'] ?? 0), 2);
        $precioVenta = round((float)($input['precio_venta'] ?? 0), 2);
        if (!empty($input['id'])) {
            $db->prepare("UPDATE maquinarias SET cliente_id=:cid,codigo=:cod,modelo=:mod,numero_serie=:ns,
                descripcion=:desc,fecha_venta=:fv,garantia_meses=:gm,garantia_hasta=:gh,
                costo_total=:costo,precio_venta=:precio,estado_venta=:estado WHERE id=:id")
               ->execute([':id'=>(int)$input['id'],':cid'=>($input['cliente_id']??null)?:null,
                ':cod'=>($input['codigo']??null)?:null,':mod'=>($input['modelo']??null)?:null,
                ':ns'=>($input['numero_serie']??null)?:null,':desc'=>($input['descripcion']??null)?:null,
                ':fv'=>($input['fecha_venta']??null)?:null,':gm'=>(int)($input['garantia_meses']??12),':gh'=>$garantia_hasta,
                ':costo'=>$costoTotal,':precio'=>$precioVenta,':estado'=>$estadoVenta]);
            $maquinariaId = (int)$input['id'];
        } else {
            $stmt=$db->prepare("INSERT INTO maquinarias (cliente_id,codigo,modelo,numero_serie,descripcion,fecha_venta,garantia_meses,garantia_hasta,costo_total,precio_venta,estado_venta)
                VALUES (:cid,:cod,:mod,:ns,:desc,:fv,:gm,:gh,:costo,:precio,:estado) RETURNING id");
            $stmt->execute([':cid'=>($input['cliente_id']??null)?:null,':cod'=>($input['codigo']??null)?:null,
                ':mod'=>($input['modelo']??null)?:null,':ns'=>($input['numero_serie']??null)?:null,
                ':desc'=>($input['descripcion']??null)?:null,':fv'=>($input['fecha_venta']??null)?:null,
                ':gm'=>(int)($input['garantia_meses']??12),':gh'=>$garantia_hasta,
                ':costo'=>$costoTotal,':precio'=>$precioVenta,':estado'=>$estadoVenta]);
            $maquinariaId = (int)$stmt->fetchColumn();
        }
        ventaSincronizarProductoMaquinaria($db, $maquinariaId);
        jsonResponse(['ok'=>true]);

    // ================================================================
    // GARANTIAS Y SERVICIO TECNICO
    // ================================================================
    case 'servicios_listar':
        $where = ['1=1'];
        $params = [];
        if (!empty($input['estado'])) { $where[]="s.estado=:estado"; $params[':estado']=$input['estado']; }
        $stmt = $db->prepare("
            SELECT s.*, m.modelo, m.numero_serie, cl.razon_social AS cliente_nombre, u.nombre AS tecnico_nombre
            FROM servicios_tecnicos s
            LEFT JOIN maquinarias m  ON m.id=s.maquinaria_id
            LEFT JOIN clientes cl    ON cl.id=m.cliente_id
            LEFT JOIN usuarios u     ON u.id=s.tecnico_id
            WHERE ".implode(' AND ',$where)."
            ORDER BY s.created_at DESC LIMIT 100");
        $stmt->execute($params);
        jsonResponse(['ok'=>true,'servicios'=>$stmt->fetchAll()]);

    case 'servicio_guardar':
        if (!empty($input['id'])) {
            $db->prepare("UPDATE servicios_tecnicos SET tipo=:tipo,descripcion=:desc,diagnostico=:diag,
                solucion=:sol,estado=:estado,tecnico_id=:tid,fecha_inicio=:fi,fecha_fin=:ff,costo=:costo
                WHERE id=:id")
               ->execute([':id'=>(int)$input['id'],':tipo'=>$input['tipo']??'correctivo',
                ':desc'=>$input['descripcion']??'',':diag'=>($input['diagnostico']??null)?:null,
                ':sol'=>($input['solucion']??null)?:null,':estado'=>$input['estado']??'pendiente',
                ':tid'=>($input['tecnico_id']??null)?:null,':fi'=>($input['fecha_inicio']??null)?:null,
                ':ff'=>($input['fecha_fin']??null)?:null,':costo'=>(float)($input['costo']??0)]);
        } else {
            $stmt=$db->prepare("INSERT INTO servicios_tecnicos (maquinaria_id,tipo,descripcion,estado,tecnico_id,fecha_solicitud)
                VALUES (:mid,:tipo,:desc,:estado,:tid,CURRENT_DATE) RETURNING id");
            $stmt->execute([':mid'=>($input['maquinaria_id']??null)?:null,':tipo'=>$input['tipo']??'correctivo',
                ':desc'=>$input['descripcion']??'',':estado'=>'pendiente',':tid'=>($input['tecnico_id']??null)?:null]);
        }
        jsonResponse(['ok'=>true]);

    case 'servicio_estado':
        $db->prepare("UPDATE servicios_tecnicos SET estado=:estado,
            fecha_fin = CASE WHEN :estado='completado' THEN CURRENT_DATE ELSE fecha_fin END
            WHERE id=:id")->execute([':estado'=>$input['estado'],':id'=>(int)($input['id']??0)]);
        jsonResponse(['ok'=>true]);

    // ================================================================
    // ORDENES DE VENTA
    // ================================================================
    case 'ordenes_listar':
        $where = ['1=1'];
        $params = [];
        if (!empty($input['buscar'])) {
            $where[] = "(o.numero ILIKE :q OR cl.razon_social ILIKE :q OR p.codigo ILIKE :q)";
            $params[':q'] = '%'.$input['buscar'].'%';
        }
        if (!empty($input['estado'])) { $where[] = "o.estado=:estado"; $params[':estado']=$input['estado']; }
        $stmt = $db->prepare("
            SELECT o.*, cl.razon_social AS cliente_nombre,
                   p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre
            FROM ordenes_venta o
            LEFT JOIN clientes cl ON cl.id = o.cliente_id
            LEFT JOIN proyectos p  ON p.id  = o.proyecto_id
            WHERE ".implode(' AND ',$where)."
            ORDER BY o.created_at DESC LIMIT 100");
        $stmt->execute($params);
        jsonResponse(['ok'=>true,'ordenes'=>$stmt->fetchAll()]);

    case 'orden_obtener':
        $id = (int)$input['id'];
        $stmt = $db->prepare("
            SELECT o.*, cl.razon_social AS cliente_nombre, p.codigo AS proyecto_codigo
            FROM ordenes_venta o
            LEFT JOIN clientes cl ON cl.id = o.cliente_id
            LEFT JOIN proyectos p  ON p.id  = o.proyecto_id
            WHERE o.id = :id");
        $stmt->execute([':id'=>$id]);
        $orden = $stmt->fetch();
        $stmt = $db->prepare("
            SELECT i.*, pr.codigo AS producto_codigo, pr.unidad
            FROM orden_venta_items i
            LEFT JOIN productos pr ON pr.id = i.producto_id
            WHERE i.orden_venta_id = :id ORDER BY i.id");
        $stmt->execute([':id'=>$id]);
        $items = $stmt->fetchAll();
        jsonResponse(['ok'=>true,'orden'=>$orden,'items'=>$items]);

    case 'orden_guardar':
        $db->beginTransaction();
        $items    = $input['items'] ?? [];
        $subtotal = array_sum(array_column($items, 'subtotal'));
        $igv      = round($subtotal * IGV, 2);
        $total    = $subtotal + $igv;
        if (!empty($input['id'])) {
            $db->prepare("UPDATE ordenes_venta SET cliente_id=:cid, proyecto_id=:pid, cotizacion_id=:cotid,
                estado=:estado, fecha_emision=:fe, fecha_entrega=:fd, observaciones=:obs,
                subtotal=:sub, igv=:igv, total=:total, updated_at=NOW() WHERE id=:id")
               ->execute([':id'=>(int)$input['id'], ':cid'=>($input['cliente_id']??null)?:null,
                ':pid'=>($input['proyecto_id']??null)?:null, ':cotid'=>($input['cotizacion_id']??null)?:null,
                ':estado'=>$input['estado']??'borrador', ':fe'=>($input['fecha_emision']??null)?:date('Y-m-d'),
                ':fd'=>($input['fecha_entrega']??null)?:null, ':obs'=>($input['observaciones']??null)?:null,
                ':sub'=>$subtotal, ':igv'=>$igv, ':total'=>$total]);
            $db->prepare("DELETE FROM orden_venta_items WHERE orden_venta_id=:id")->execute([':id'=>(int)$input['id']]);
            $ov_id = (int)$input['id'];
        } else {
            $numero = generarCodigo('OV-', 'ordenes_venta', 'numero');
            $stmt = $db->prepare("INSERT INTO ordenes_venta
                (numero,cliente_id,proyecto_id,cotizacion_id,estado,fecha_emision,fecha_entrega,observaciones,subtotal,igv,total)
                VALUES (:num,:cid,:pid,:cotid,:estado,:fe,:fd,:obs,:sub,:igv,:total) RETURNING id");
            $stmt->execute([':num'=>$numero, ':cid'=>($input['cliente_id']??null)?:null,
                ':pid'=>($input['proyecto_id']??null)?:null, ':cotid'=>($input['cotizacion_id']??null)?:null,
                ':estado'=>$input['estado']??'borrador', ':fe'=>($input['fecha_emision']??null)?:date('Y-m-d'),
                ':fd'=>($input['fecha_entrega']??null)?:null, ':obs'=>($input['observaciones']??null)?:null,
                ':sub'=>$subtotal, ':igv'=>$igv, ':total'=>$total]);
            $ov_id = (int)$stmt->fetchColumn();
        }
        $ins = $db->prepare("INSERT INTO orden_venta_items
            (orden_venta_id,producto_id,descripcion,cantidad,precio_unitario,descuento,subtotal)
            VALUES (:oid,:prod,:desc,:qty,:pu,:dto,:sub)");
        foreach ($items as $it) {
            if (empty($it['producto_id'])) continue;
            $ins->execute([':oid'=>$ov_id, ':prod'=>(int)$it['producto_id'],
                ':desc'=>($it['descripcion']??null)?:null,
                ':qty'=>(float)$it['cantidad'], ':pu'=>(float)$it['precio_unitario'],
                ':dto'=>(float)($it['descuento']??0), ':sub'=>(float)$it['subtotal']]);
        }
        $db->commit();
        jsonResponse(['ok'=>true,'id'=>$ov_id]);

    case 'orden_estado':
        $allowed = ['borrador','confirmada','facturada','cobrada','cancelada'];
        if (!in_array($input['estado']??'',$allowed)) jsonResponse(['error'=>'Estado invalido.'],400);
        $db->prepare("UPDATE ordenes_venta SET estado=:estado, updated_at=NOW() WHERE id=:id")
           ->execute([':estado'=>$input['estado'],':id'=>(int)($input['id']??0)]);
        jsonResponse(['ok'=>true]);

    case 'orden_eliminar':
        $db->prepare("DELETE FROM ordenes_venta WHERE id=:id")->execute([':id'=>(int)($input['id']??0)]);
        jsonResponse(['ok'=>true]);

    // ================================================================
    // PRODUCTOS PARA VENTA DIRECTA
    // ================================================================
    case 'productos_listar_venta':
        $q = $input['q'] ?? '';
        $where  = ['activo = TRUE'];
        $params = [];
        if ($q !== '') {
            $where[]    = "(nombre ILIKE :q OR codigo ILIKE :q)";
            $params[':q'] = '%' . $q . '%';
        }
        $stmt = $db->prepare("
            SELECT id, codigo, nombre, unidad, unidad_id, unidad_codigo,
                   afectacion_igv_id, afectacion_igv_codigo, porcentaje_igv, incluye_igv,
                   codigo_interno, codigo_sunat, product_type,
                   ROUND(stock_actual::numeric, 3) AS stock_actual,
                   ROUND(precio_venta::numeric, 2)  AS precio_venta,
                   es_repuesto
            FROM productos
            WHERE " . implode(' AND ', $where) . "
            ORDER BY nombre");
        $stmt->execute($params);
        jsonResponse(['ok' => true, 'productos' => $stmt->fetchAll()]);

    // ================================================================
    // VENTA DIRECTA
    // ================================================================
    case 'venta_directa':
        $items = $input['items'] ?? [];
        if (empty($items)) jsonResponse(['error' => 'Sin items en la venta.'], 400);

        $tipoComprobante = $input['tipo_comprobante'] ?? 'boleta';
        if (!in_array($tipoComprobante, ['nota_venta', 'boleta', 'factura'], true)) {
            jsonResponse(['error' => 'Tipo de comprobante no valido.'], 400);
        }
        $meta = ventaDocumentoMeta($tipoComprobante);

        $db->beginTransaction();
        $subtotal = 0.00;
        $igv = 0.00;
        $total = 0.00;
        $gravada = 0.00;
        $exonerada = 0.00;
        $inafecta = 0.00;
        $descuentoGlobal = round(max(0, (float)($input['descuento'] ?? 0)), 2);
        $esCredito = !empty($input['es_credito']);
        $tipoPago = $esCredito ? 'credito' : (trim((string)($input['tipo_pago'] ?? 'efectivo')) ?: 'efectivo');
        $paymentBreakdown = [];
        $cuotas = [];
        $maquinariaId = (int)($input['maquinaria_id'] ?? 0);
        $maquinariaVenta = null;

        if ($maquinariaId > 0) {
            $stmtMaq = $db->prepare("
                SELECT m.id, m.producto_id, m.estado_venta, m.garantia_meses, m.modelo, m.numero_serie,
                       p.stock_actual
                FROM maquinarias m
                LEFT JOIN productos p ON p.id = m.producto_id
                WHERE m.id = :id AND m.activo = TRUE
                FOR UPDATE OF m
            ");
            $stmtMaq->execute([':id' => $maquinariaId]);
            $maquinariaVenta = $stmtMaq->fetch();
            if (!$maquinariaVenta) {
                $db->rollBack();
                jsonResponse(['error' => 'La maquinaria seleccionada no existe o no esta activa.'], 400);
            }
            if (($maquinariaVenta['estado_venta'] ?? 'disponible') === 'vendida') {
                $db->rollBack();
                jsonResponse(['error' => 'Esta maquinaria ya figura como vendida.'], 400);
            }
            if (empty($maquinariaVenta['producto_id'])) {
                $productoMaq = ventaSincronizarProductoMaquinaria($db, $maquinariaId);
                $maquinariaVenta['producto_id'] = $productoMaq;
            }
            $productoIds = array_map(fn($it) => (int)($it['producto_id'] ?? 0), $items);
            if (!in_array((int)$maquinariaVenta['producto_id'], $productoIds, true)) {
                $db->rollBack();
                jsonResponse(['error' => 'La venta no contiene el producto asociado a la maquinaria.'], 400);
            }
        }

        $clienteId = ($input['cliente_id'] ?? null) ?: null;
        if (!$clienteId) {
            $clienteId = $db->query("
                SELECT id FROM clientes
                WHERE activo AND COALESCE(numero_documento, ruc_dni, '') = '00000000'
                ORDER BY id LIMIT 1
            ")->fetchColumn() ?: null;
        }

        $cliente = null;
        if ($clienteId) {
            $stmtCliente = $db->prepare("
                SELECT id, COALESCE(NULLIF(numero_documento,''), NULLIF(ruc_dni,''), '') AS documento,
                       COALESCE(NULLIF(razon_social,''), NULLIF(nombre_completo,''), 'Cliente') AS nombre,
                       COALESCE(NULLIF(razon_social,''), NULLIF(nombre_completo,''), 'Cliente') AS razon_social,
                       COALESCE(NULLIF(nombre_completo,''), NULLIF(razon_social,''), 'Cliente') AS nombre_completo,
                       CASE WHEN tipo_documento_codigo = '6' THEN COALESCE(NULLIF(numero_documento,''), NULLIF(ruc_dni,''), '') ELSE '' END AS ruc,
                       CASE WHEN tipo_documento_codigo = '1' THEN COALESCE(NULLIF(numero_documento,''), NULLIF(ruc_dni,''), '') ELSE '' END AS dni,
                       direccion
                FROM clientes WHERE id=:id AND activo
            ");
            $stmtCliente->execute([':id' => $clienteId]);
            $cliente = $stmtCliente->fetch();
        }

        if ($tipoComprobante === 'factura' && (!$cliente || strlen(preg_replace('/\D+/', '', $cliente['documento'])) !== 11)) {
            $db->rollBack();
            jsonResponse(['error' => 'Para emitir factura selecciona un cliente con RUC de 11 digitos.'], 400);
        }

        if ($esCredito) {
            $docCliente = preg_replace('/\D+/', '', (string)($cliente['documento'] ?? ''));
            $nombreCliente = strtoupper((string)($cliente['nombre'] ?? ''));
            if (!$cliente || $docCliente === '00000000' || str_contains($nombreCliente, 'CLIENTES VARIOS')) {
                $db->rollBack();
                jsonResponse(['error' => 'Para venta a credito selecciona un cliente real.'], 400);
            }
        }

        $seriePreferida = trim((string)($input['serie'] ?? ''));
        $serieSql = "
            UPDATE series_comprobantes
            SET ultimo_numero = ultimo_numero + 1, updated_at = NOW()
            WHERE tipo = :tipo
              AND codigo_tipo_documento = :codigo
              AND activo = TRUE
        ";
        $serieParams = [':tipo' => $tipoComprobante, ':codigo' => $meta['codigo']];
        if ($seriePreferida !== '') {
            $serieSql .= " AND serie = :serie";
            $serieParams[':serie'] = $seriePreferida;
        }
        $serieSql .= " RETURNING id, serie, ultimo_numero, codigo_tipo_documento, tipo_documento_id";
        $stmtSerie = $db->prepare($serieSql);
        $stmtSerie->execute($serieParams);
        $serieRow = $stmtSerie->fetch();
        if (!$serieRow) {
            $db->rollBack();
            jsonResponse(['error' => 'No hay serie activa configurada para este comprobante.'], 400);
        }

        $numero = generarCodigo('VD-', 'ordenes_venta', 'numero');
        $stmt = $db->prepare("INSERT INTO ordenes_venta
            (numero, cliente_id, estado, fecha_emision, observaciones, subtotal, igv, total)
            VALUES (:num, :cid, 'cobrada', CURRENT_DATE, :obs, :sub, :igv, :total)
            RETURNING id");
        $stmt->execute([
            ':num' => $numero,
            ':cid' => $clienteId,
            ':obs' => ($input['observaciones'] ?? null) ?: null,
            ':sub' => 0,
            ':igv' => 0,
            ':total' => 0,
        ]);
        $ov_id = (int)$stmt->fetchColumn();

        $ins_item = $db->prepare("INSERT INTO orden_venta_items
            (orden_venta_id, producto_id, cantidad, precio_unitario, descuento, subtotal)
            VALUES (:oid, :pid, :qty, :pu, :dto, :sub)");

        $ins_kard = $db->prepare("INSERT INTO kardex
            (producto_id, tipo, referencia_tipo, referencia_id,
             cantidad, precio_unitario, saldo_cantidad, saldo_valor, observaciones)
            VALUES (:pid, 'salida', 'venta_directa', :ref,
                    :qty, :pu, :sq, :sv, :obs)");

        $lineas = [];
        foreach ($items as $it) {
            $prod_id = (int)($it['producto_id'] ?? 0);
            $qty = (float)($it['cantidad'] ?? 0);
            $pu = (float)($it['precio_unitario'] ?? 0);
            $dto = (float)($it['descuento'] ?? 0);
            if (!$prod_id || $qty <= 0) continue;

            $ps = $db->prepare("
                SELECT id, codigo, nombre, unidad, stock_actual, precio_promedio,
                       unidad_id, unidad_codigo, afectacion_igv_id, afectacion_igv_codigo,
                       porcentaje_igv, incluye_igv, codigo_interno, codigo_sunat, product_type
                FROM productos WHERE id=:id FOR UPDATE
            ");
            $ps->execute([':id' => $prod_id]);
            $pr = $ps->fetch();
            if (!$pr) {
                $db->rollBack();
                jsonResponse(['error' => "Producto ID {$prod_id} no encontrado."], 400);
            }
            $esServicio = strtolower((string)($pr['product_type'] ?? 'product')) === 'service'
                || strtolower((string)($pr['unidad_codigo'] ?? '')) === 'zz'
                || strtolower((string)($pr['unidad'] ?? '')) === 'servicio';

            if (!$esServicio && (float)$pr['stock_actual'] < $qty) {
                $db->rollBack();
                jsonResponse(['error' => "Stock insuficiente para el producto ID {$prod_id}."], 400);
            }

            $linea = ventaCalcularLinea($pr, $qty, $pu, $dto);
            $subtotal += $linea['subtotal'];
            $igv += $linea['igv'];
            $total += $linea['total'];

            if ($linea['afectacion_igv_codigo'] === '10') {
                $gravada += $linea['valor_total'];
            } elseif ($linea['afectacion_igv_codigo'] === '20') {
                $exonerada += $linea['valor_total'];
            } elseif ($linea['afectacion_igv_codigo'] === '30') {
                $inafecta += $linea['valor_total'];
            }

            $nuevo_stock = (float)$pr['stock_actual'];
            if (!$esServicio) {
                $nuevo_stock -= $qty;
                $db->prepare("UPDATE productos SET stock_actual=:sa, updated_at=NOW() WHERE id=:id")
                   ->execute([':sa' => $nuevo_stock, ':id' => $prod_id]);
            }

            $ins_item->execute([
                ':oid' => $ov_id,
                ':pid' => $prod_id,
                ':qty' => $qty,
                ':pu' => $pu,
                ':dto' => $dto,
                ':sub' => $linea['subtotal'],
            ]);

            if (!$esServicio) {
                $ins_kard->execute([
                    ':pid' => $prod_id,
                    ':ref' => $ov_id,
                    ':qty' => $qty,
                    ':pu' => $pu,
                    ':sq' => $nuevo_stock,
                    ':sv' => $nuevo_stock * (float)$pr['precio_promedio'],
                    ':obs' => 'Venta directa ' . $numero,
                ]);
            }

            $lineas[] = $linea + [
                'producto_id' => $prod_id,
                'cantidad' => $qty,
                'descripcion' => $pr['nombre'],
            ];
        }

        if (!$lineas) {
            $db->rollBack();
            jsonResponse(['error' => 'Sin items validos para registrar.'], 400);
        }

        try {
            [$lineas, $descuentoGlobal] = ventaAplicarDescuentoGlobal($lineas, $descuentoGlobal);
        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(['error' => $e->getMessage()], 400);
        }

        $subtotal = 0.00;
        $igv = 0.00;
        $total = 0.00;
        $gravada = 0.00;
        $exonerada = 0.00;
        $inafecta = 0.00;
        foreach ($lineas as $linea) {
            $subtotal += $linea['subtotal'];
            $igv += $linea['igv'];
            $total += $linea['total'];
            if ($linea['afectacion_igv_codigo'] === '10') {
                $gravada += $linea['valor_total'];
            } elseif ($linea['afectacion_igv_codigo'] === '20') {
                $exonerada += $linea['valor_total'];
            } elseif ($linea['afectacion_igv_codigo'] === '30') {
                $inafecta += $linea['valor_total'];
            }
        }

        $subtotal = round($subtotal, 2);
        $igv = round($igv, 2);
        $total = round($total, 2);
        $gravada = round($gravada, 2);
        $exonerada = round($exonerada, 2);
        $inafecta = round($inafecta, 2);

        if ($esCredito) {
            foreach (($input['cuotas'] ?? []) as $cuota) {
                $amount = round((float)($cuota['amount'] ?? 0), 2);
                $due = trim((string)($cuota['due_date'] ?? ''));
                if ($amount > 0 && $due !== '') {
                    $cuotas[] = ['amount' => $amount, 'due_date' => $due];
                }
            }
            if (!$cuotas) {
                $db->rollBack();
                jsonResponse(['error' => 'Agrega al menos una cuota para el pago a credito.'], 400);
            }
            $cuotasTotal = round(array_sum(array_column($cuotas, 'amount')), 2);
            if (abs($cuotasTotal - $total) > 0.01) {
                $db->rollBack();
                jsonResponse(['error' => 'La suma de cuotas debe coincidir con el total.'], 400);
            }
        } else {
            foreach (($input['payment_breakdown'] ?? []) as $pago) {
                $method = strtolower(trim((string)($pago['method'] ?? '')));
                $amount = round((float)($pago['amount'] ?? 0), 2);
                if ($method !== '' && $amount > 0) {
                    $paymentBreakdown[] = ['method' => $method, 'amount' => $amount];
                }
            }
            if ($paymentBreakdown) {
                $pagosTotal = round(array_sum(array_column($paymentBreakdown, 'amount')), 2);
                if (abs($pagosTotal - $total) > 0.01) {
                    $db->rollBack();
                    jsonResponse(['error' => 'La suma del pago mixto debe coincidir con el total.'], 400);
                }
            } else {
                $paymentBreakdown[] = ['method' => $tipoPago, 'amount' => $total];
            }
        }

        $montoRecibido = $esCredito ? 0.00 : round((float)($input['monto_recibido'] ?? $total), 2);
        $vuelto = (!$esCredito && $tipoPago === 'efectivo') ? round(max(0, $montoRecibido - $total), 2) : 0.00;

        $db->prepare("UPDATE ordenes_venta SET subtotal=:sub, igv=:igv, total=:total WHERE id=:id")
           ->execute([':sub' => $subtotal, ':igv' => $igv, ':total' => $total, ':id' => $ov_id]);

        $correlativo = str_pad((string)$serieRow['ultimo_numero'], 8, '0', STR_PAD_LEFT);
        $numeroComprobante = $serieRow['serie'] . '-' . $correlativo;
        $empresaId = $db->query("SELECT id FROM empresa_emisora WHERE activo ORDER BY id LIMIT 1")->fetchColumn() ?: null;
        $monedaId = $db->query("SELECT id FROM fe_monedas WHERE codigo='PEN' LIMIT 1")->fetchColumn() ?: null;
        $formaPagoId = $db->query("SELECT id FROM fe_formas_pago WHERE descripcion ILIKE '%contado%' ORDER BY id LIMIT 1")->fetchColumn() ?: null;
        $operacionId = $db->query("SELECT id FROM fe_tipos_operacion WHERE codigo='0101' ORDER BY id LIMIT 1")->fetchColumn() ?: null;

        $payload = json_encode([
            'tipo_comprobante' => $tipoComprobante,
            'numero' => $numeroComprobante,
            'orden_venta' => $numero,
            'cliente' => $cliente,
            'items' => $lineas,
        ], JSON_UNESCAPED_UNICODE);

        $stmtComp = $db->prepare("
            INSERT INTO comprobantes_electronicos
            (empresa_emisora_id, cliente_id, orden_venta_id, serie_id, tipo_documento_id,
             codigo_tipo_documento, tipo_operacion_id, operacion_tipo_codigo, moneda_id,
             moneda_codigo, forma_pago_id, sunat_forma_pago, numero, serie, correlativo,
             fecha_emision, observaciones, subtotal, descuento_global, gravada, exonerada, inafecta, igv, total,
             monto_credito, vuelto, cuotas, payment_breakdown, payload_json, estado)
            VALUES
            (:empresa_id, :cliente_id, :orden_id, :serie_id, :tipo_documento_id,
             :codigo_tipo_documento, :tipo_operacion_id, '0101', :moneda_id,
             'PEN', :forma_pago_id, 'Contado', :numero, :serie, :correlativo,
             CURRENT_DATE, :observaciones, :subtotal, :descuento_global, :gravada, :exonerada, :inafecta,
             :igv, :total, :monto_credito, :vuelto, :cuotas, :payment_breakdown, :payload_json, :estado)
            RETURNING id
        ");
        $stmtComp->execute([
            ':empresa_id' => $empresaId,
            ':cliente_id' => $clienteId,
            ':orden_id' => $ov_id,
            ':serie_id' => (int)$serieRow['id'],
            ':tipo_documento_id' => $serieRow['tipo_documento_id'] ?: null,
            ':codigo_tipo_documento' => $meta['codigo'],
            ':tipo_operacion_id' => $operacionId,
            ':moneda_id' => $monedaId,
            ':forma_pago_id' => $formaPagoId,
            ':numero' => $numeroComprobante,
            ':serie' => $serieRow['serie'],
            ':correlativo' => $correlativo,
            ':observaciones' => ($input['observaciones'] ?? null) ?: null,
            ':subtotal' => $subtotal,
            ':descuento_global' => $descuentoGlobal,
            ':gravada' => $gravada,
            ':exonerada' => $exonerada,
            ':inafecta' => $inafecta,
            ':igv' => $igv,
            ':total' => $total,
            ':monto_credito' => $esCredito ? $total : 0,
            ':vuelto' => $vuelto,
            ':cuotas' => json_encode($cuotas, JSON_UNESCAPED_UNICODE),
            ':payment_breakdown' => json_encode($paymentBreakdown, JSON_UNESCAPED_UNICODE),
            ':payload_json' => $payload,
            ':estado' => $tipoComprobante === 'nota_venta' ? 'registrado' : 'pendiente',
        ]);
        $comprobanteId = (int)$stmtComp->fetchColumn();

        $insDetalle = $db->prepare("
            INSERT INTO comprobante_detalles
            (comprobante_electronico_id, producto_id, descripcion, cantidad, unidad_codigo,
             codigo_sunat, codigo_interno, valor_unitario, precio_unitario,
             descuento, valor_total, afectacion_igv_codigo, igv, subtotal, total)
            VALUES
            (:comprobante_id, :producto_id, :descripcion, :cantidad, :unidad_codigo,
             :codigo_sunat, :codigo_interno, :valor_unitario, :precio_unitario,
             :descuento, :valor_total, :afectacion_igv_codigo, :igv, :subtotal, :total)
        ");
        foreach ($lineas as $linea) {
            $insDetalle->execute([
                ':comprobante_id' => $comprobanteId,
                ':producto_id' => $linea['producto_id'],
                ':descripcion' => $linea['descripcion'],
                ':cantidad' => $linea['cantidad'],
                ':unidad_codigo' => $linea['unidad_codigo'],
                ':codigo_sunat' => $linea['codigo_sunat'],
                ':codigo_interno' => $linea['codigo_interno'],
                ':valor_unitario' => $linea['valor_unitario'],
                ':precio_unitario' => $linea['precio_unitario'],
                ':descuento' => $linea['descuento'],
                ':valor_total' => $linea['valor_total'],
                ':afectacion_igv_codigo' => $linea['afectacion_igv_codigo'],
                ':igv' => $linea['igv'],
                ':subtotal' => $linea['subtotal'],
                ':total' => $linea['total'],
            ]);
        }

        $db->commit();

        $comprobanteResult = null;
        if (in_array($tipoComprobante, ['boleta', 'factura'], true)) {
            $itemsSunat = array_map(function ($linea) {
                return [
                    'producto_id' => $linea['producto_id'],
                    'cantidad' => $linea['cantidad'],
                    'descripcion' => $linea['descripcion'],
                    'nombre' => $linea['descripcion'],
                    'codigo_interno' => $linea['codigo_interno'],
                    'codigo_sunat' => $linea['codigo_sunat'],
                    'unidad_codigo' => $linea['unidad_codigo'],
                    'afectacion_igv_codigo' => $linea['afectacion_igv_codigo'],
                    'valor_unitario' => $linea['valor_unitario'],
                    'precio_unitario' => $linea['precio_unitario'],
                    'valor_total' => $linea['valor_total'],
                    'igv' => $linea['igv'],
                    'descuento' => $linea['descuento'],
                ];
            }, $lineas);

            $comprobanteResult = enviar_sunat($db, [
                'id' => $comprobanteId,
                'tipo' => $tipoComprobante,
                'serie' => $serieRow['serie'],
                'numero' => (int)$serieRow['ultimo_numero'],
                'numero_completo' => $numeroComprobante,
            ], $cliente, $itemsSunat, [
                'numero_venta' => $numero,
                'tipo_pago' => 'efectivo',
                'sunat_forma_pago' => $esCredito ? 'Credito' : 'Contado',
                'fecha_emision' => date('Y-m-d'),
                'hora_emision' => date('H:i:s'),
                'moneda' => 'PEN',
                'totales' => [
                    'subtotal' => $subtotal,
                    'gravada' => $gravada,
                    'exonerada' => $exonerada,
                    'inafecta' => $inafecta,
                    'igv' => $igv,
                    'total' => $total,
                ],
            ]);
        }

        jsonResponse([
            'ok' => true,
            'id' => $ov_id,
            'numero' => $numero,
            'comprobante_id' => $comprobanteId,
            'comprobante_numero' => $numeroComprobante,
            'tipo_comprobante' => $tipoComprobante,
            'serie' => $serieRow['serie'],
            'correlativo' => $correlativo,
            'subtotal' => $subtotal,
            'gravada' => $gravada,
            'exonerada' => $exonerada,
            'inafecta' => $inafecta,
            'igv' => $igv,
            'total' => $total,
            'descuento' => $descuentoGlobal,
            'tipo_pago' => $tipoPago,
            'monto_recibido' => $montoRecibido,
            'vuelto' => $vuelto,
            'payment_breakdown' => $paymentBreakdown,
            'cuotas' => $cuotas,
            'cliente' => $cliente,
            'items' => $lineas,
            'comprobante' => $comprobanteResult,
        ]);

    // ================================================================
    // HISTORIAL / LISTADO DE VENTAS DIRECTAS
    // ================================================================
    case 'ventas_stats':
        $stmt = $db->query("
            SELECT
                COUNT(*) FILTER (WHERE ov.estado = 'cobrada') AS total_ventas,
                COALESCE(SUM(ov.total) FILTER (WHERE ov.estado = 'cobrada'), 0) AS ingresos,
                COALESCE(AVG(ov.total) FILTER (WHERE ov.estado = 'cobrada'), 0) AS ticket_promedio,
                COUNT(*) FILTER (WHERE ov.estado = 'cancelada') AS anuladas
            FROM ordenes_venta ov
            WHERE ov.fecha_emision = CURRENT_DATE
        ");
        jsonResponse(['ok' => true, 'stats' => $stmt->fetch()]);

    case 'ventas_historial':
        $desde = $input['desde'] ?: date('Y-m-d', strtotime('-30 days'));
        $hasta = $input['hasta'] ?: date('Y-m-d');
        $where = ['ov.fecha_emision BETWEEN :desde AND :hasta'];
        $params = [':desde' => $desde, ':hasta' => $hasta];

        if (!empty($input['estado'])) {
            $where[] = 'ov.estado = :estado';
            $params[':estado'] = $input['estado'];
        }
        if (!empty($input['tipo'])) {
            $tipoCodigo = ['factura' => '01', 'boleta' => '03', 'nota_venta' => '02'][$input['tipo']] ?? '';
            if ($tipoCodigo !== '') {
                $where[] = 'ce.codigo_tipo_documento = :tipo_codigo';
                $params[':tipo_codigo'] = $tipoCodigo;
            }
        }
        if (!empty($input['q'])) {
            $where[] = "(
                ov.numero ILIKE :q
                OR ce.numero ILIKE :q
                OR COALESCE(cl.razon_social, cl.nombre_completo, '') ILIKE :q
                OR COALESCE(cl.numero_documento, cl.ruc_dni, '') ILIKE :q
            )";
            $params[':q'] = '%' . trim((string)$input['q']) . '%';
        }

        $stmt = $db->prepare("
            SELECT
                ov.id,
                ov.numero,
                ov.estado,
                ov.fecha_emision,
                ov.created_at,
                ov.subtotal,
                ov.igv,
                ov.total,
                COALESCE(NULLIF(cl.razon_social, ''), NULLIF(cl.nombre_completo, ''), 'CLIENTES VARIOS') AS cliente,
                COALESCE(NULLIF(cl.numero_documento, ''), NULLIF(cl.ruc_dni, ''), '00000000') AS documento,
                ce.id AS comprobante_id,
                ce.numero AS comprobante_numero,
                ce.codigo_tipo_documento,
                CASE ce.codigo_tipo_documento
                    WHEN '01' THEN 'factura'
                    WHEN '03' THEN 'boleta'
                    WHEN '02' THEN 'nota_venta'
                    ELSE 'nota_venta'
                END AS tipo_comprobante,
                ce.estado AS estado_cpe,
                ce.anulado,
                ce.xml_path,
                ce.cdr_path,
                ce.descuento_global,
                ce.vuelto,
                ce.monto_credito,
                COALESCE(jsonb_array_length(COALESCE(ce.payment_breakdown, '[]'::jsonb)), 0) AS pagos_count,
                COALESCE(ce.payment_breakdown->0->>'method', CASE WHEN ce.monto_credito > 0 THEN 'credito' ELSE 'efectivo' END) AS tipo_pago,
                COUNT(ovi.id) AS items_count
            FROM ordenes_venta ov
            LEFT JOIN clientes cl ON cl.id = ov.cliente_id
            LEFT JOIN LATERAL (
                SELECT *
                FROM comprobantes_electronicos ce2
                WHERE ce2.orden_venta_id = ov.id
                  AND ce2.codigo_tipo_documento IN ('01','02','03')
                ORDER BY ce2.id DESC
                LIMIT 1
            ) ce ON TRUE
            LEFT JOIN orden_venta_items ovi ON ovi.orden_venta_id = ov.id
            WHERE " . implode(' AND ', $where) . "
            GROUP BY ov.id, cl.id, ce.id, ce.numero, ce.codigo_tipo_documento, ce.estado, ce.anulado,
                ce.descuento_global, ce.vuelto, ce.monto_credito, ce.payment_breakdown, ce.xml_path, ce.cdr_path
            ORDER BY ov.created_at DESC, ov.id DESC
            LIMIT 250
        ");
        $stmt->execute($params);
        jsonResponse(['ok' => true, 'ventas' => $stmt->fetchAll()]);

    case 'notas_credito_historial':
        $desde = $input['desde'] ?: date('Y-m-d', strtotime('-30 days'));
        $hasta = $input['hasta'] ?: date('Y-m-d');
        $where = ['ce.fecha_emision BETWEEN :desde AND :hasta', "ce.codigo_tipo_documento = '07'"];
        $params = [':desde' => $desde, ':hasta' => $hasta];

        if (!empty($input['q'])) {
            $where[] = "(
                ce.numero ILIKE :q
                OR ref.numero ILIKE :q
                OR ov.numero ILIKE :q
                OR COALESCE(cl.razon_social, cl.nombre_completo, '') ILIKE :q
                OR COALESCE(cl.numero_documento, cl.ruc_dni, '') ILIKE :q
            )";
            $params[':q'] = '%' . trim((string)$input['q']) . '%';
        }

        $stmt = $db->prepare("
            SELECT
                ce.id,
                ce.orden_venta_id AS venta_id,
                ov.numero AS venta_numero,
                ce.numero,
                ce.serie,
                ce.correlativo,
                ce.fecha_emision,
                ce.created_at,
                ce.estado,
                ce.codigo_respuesta,
                ce.mensaje_respuesta,
                ce.xml_path,
                ce.cdr_path,
                ce.total,
                ce.codigo_tipo_nota_credito,
                ce.motivo_nota_credito,
                ce.descripcion_nota_credito,
                ref.id AS referencia_id,
                ref.numero AS referencia_numero,
                ref.codigo_tipo_documento AS referencia_tipo_codigo,
                COALESCE(NULLIF(cl.razon_social, ''), NULLIF(cl.nombre_completo, ''), 'CLIENTES VARIOS') AS cliente,
                COALESCE(NULLIF(cl.numero_documento, ''), NULLIF(cl.ruc_dni, ''), '00000000') AS documento,
                COUNT(cd.id) AS items_count
            FROM comprobantes_electronicos ce
            LEFT JOIN comprobantes_electronicos ref ON ref.id = ce.referencia_comprobante_id
            LEFT JOIN ordenes_venta ov ON ov.id = ce.orden_venta_id
            LEFT JOIN clientes cl ON cl.id = ce.cliente_id
            LEFT JOIN comprobante_detalles cd ON cd.comprobante_electronico_id = ce.id
            WHERE " . implode(' AND ', $where) . "
            GROUP BY ce.id, ref.id, ov.id, cl.id
            ORDER BY ce.created_at DESC, ce.id DESC
            LIMIT 200
        ");
        $stmt->execute($params);
        jsonResponse(['ok' => true, 'notas' => $stmt->fetchAll()]);

    case 'nota_credito_detalle':
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) jsonResponse(['error' => 'Nota de credito invalida.'], 400);

        $nota = ventaCargarComprobante($db, $id);
        if (($nota['codigo_tipo_documento'] ?? '') !== '07') {
            jsonResponse(['error' => 'El comprobante seleccionado no es nota de credito.'], 400);
        }
        $cliente = ventaCargarCliente($db, !empty($nota['cliente_id']) ? (int)$nota['cliente_id'] : null) ?: [];
        $items = ventaCargarItemsComprobante($db, $id);

        jsonResponse([
            'ok' => true,
            'nota' => [
                'id' => (int)$nota['id'],
                'numero' => $nota['orden_numero'] ?? '',
                'created_at' => $nota['created_at'],
                'fecha_emision' => $nota['fecha_emision'],
                'tipo_comprobante' => 'nota_credito',
                'comprobante_numero' => $nota['numero'],
                'comprobante_id' => (int)$nota['id'],
                'estado_cpe' => $nota['estado'],
                'xml_path' => $nota['xml_path'],
                'cdr_path' => $nota['cdr_path'],
                'codigo_respuesta' => $nota['codigo_respuesta'],
                'mensaje_respuesta' => $nota['mensaje_respuesta'],
                'gravada' => (float)($nota['gravada'] ?? 0),
                'exonerada' => (float)($nota['exonerada'] ?? 0),
                'inafecta' => (float)($nota['inafecta'] ?? 0),
                'igv' => (float)($nota['igv'] ?? 0),
                'total' => (float)($nota['total'] ?? 0),
                'descuento' => (float)($nota['descuento_global'] ?? 0),
                'tipo_pago' => 'nota_credito',
                'payment_breakdown' => [],
                'cuotas' => [],
                'motivo_nota_credito' => $nota['motivo_nota_credito'],
                'descripcion_nota_credito' => $nota['descripcion_nota_credito'],
                'documento_modificado_numero_completo' => $nota['documento_modificado_numero_completo'],
                'cliente' => [
                    'id' => $cliente['id'] ?? null,
                    'nombre' => $cliente['razon_social'] ?? $cliente['nombre_completo'] ?? 'CLIENTES VARIOS',
                    'documento' => $cliente['documento'] ?? $cliente['numero_documento'] ?? $cliente['ruc_dni'] ?? '00000000',
                    'direccion' => $cliente['direccion'] ?? '',
                ],
                'items' => $items,
            ],
        ]);

    case 'venta_detalle':
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) jsonResponse(['error' => 'Venta invalida.'], 400);

        $stmt = $db->prepare("
            SELECT
                ov.id,
                ov.numero,
                ov.estado,
                ov.fecha_emision,
                ov.created_at,
                ov.observaciones,
                ov.subtotal,
                ov.igv,
                ov.total,
                cl.id AS cliente_id,
                COALESCE(NULLIF(cl.razon_social, ''), NULLIF(cl.nombre_completo, ''), 'CLIENTES VARIOS') AS cliente_nombre,
                COALESCE(NULLIF(cl.numero_documento, ''), NULLIF(cl.ruc_dni, ''), '00000000') AS cliente_documento,
                cl.direccion AS cliente_direccion,
                ce.id AS comprobante_id,
                ce.numero AS comprobante_numero,
                ce.codigo_tipo_documento,
                ce.serie,
                ce.correlativo,
                ce.estado AS estado_cpe,
                ce.anulado,
                ce.xml_path,
                ce.cdr_path,
                ce.descuento_global,
                ce.gravada,
                ce.exonerada,
                ce.inafecta,
                ce.igv AS comprobante_igv,
                ce.total AS comprobante_total,
                ce.vuelto,
                ce.monto_credito,
                ce.cuotas,
                ce.payment_breakdown,
                ce.mensaje_respuesta,
                ce.codigo_respuesta
            FROM ordenes_venta ov
            LEFT JOIN clientes cl ON cl.id = ov.cliente_id
            LEFT JOIN LATERAL (
                SELECT *
                FROM comprobantes_electronicos ce2
                WHERE ce2.orden_venta_id = ov.id
                  AND ce2.codigo_tipo_documento IN ('01','02','03')
                ORDER BY ce2.id DESC
                LIMIT 1
            ) ce ON TRUE
            WHERE ov.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $venta = $stmt->fetch();
        if (!$venta) jsonResponse(['error' => 'Venta no encontrada.'], 404);

        if (!empty($venta['comprobante_id'])) {
            $stmtItems = $db->prepare("
                SELECT cd.producto_id, cd.descripcion, cd.cantidad, cd.unidad_codigo,
                       cd.precio_unitario, cd.valor_unitario, cd.descuento,
                       cd.subtotal, cd.igv, cd.total, p.codigo
                FROM comprobante_detalles cd
                LEFT JOIN productos p ON p.id = cd.producto_id
                WHERE cd.comprobante_electronico_id = :comprobante_id
                ORDER BY cd.id
            ");
            $stmtItems->execute([':comprobante_id' => $venta['comprobante_id']]);
        } else {
            $stmtItems = $db->prepare("
                SELECT ovi.producto_id, COALESCE(ovi.descripcion, p.nombre) AS descripcion,
                       ovi.cantidad, COALESCE(p.unidad_codigo, p.unidad, 'NIU') AS unidad_codigo,
                       ovi.precio_unitario, ovi.precio_unitario AS valor_unitario,
                       ovi.descuento, ovi.subtotal, 0 AS igv, ovi.subtotal AS total, p.codigo
                FROM orden_venta_items ovi
                LEFT JOIN productos p ON p.id = ovi.producto_id
                WHERE ovi.orden_venta_id = :id
                ORDER BY ovi.id
            ");
            $stmtItems->execute([':id' => $id]);
        }

        $tipoComprobante = match ($venta['codigo_tipo_documento'] ?? '') {
            '01' => 'factura',
            '03' => 'boleta',
            '02' => 'nota_venta',
            default => 'nota_venta',
        };
        $paymentBreakdown = json_decode((string)($venta['payment_breakdown'] ?? '[]'), true);
        $cuotas = json_decode((string)($venta['cuotas'] ?? '[]'), true);
        if (!is_array($paymentBreakdown)) $paymentBreakdown = [];
        if (!is_array($cuotas)) $cuotas = [];

        jsonResponse([
            'ok' => true,
            'venta' => [
                'id' => (int)$venta['id'],
                'numero' => $venta['numero'],
                'estado' => $venta['estado'],
                'fecha_emision' => $venta['fecha_emision'],
                'created_at' => $venta['created_at'],
                'observaciones' => $venta['observaciones'],
                'tipo_comprobante' => $tipoComprobante,
                'comprobante_numero' => $venta['comprobante_numero'],
                'comprobante_id' => $venta['comprobante_id'],
                'estado_cpe' => $venta['estado_cpe'],
                'xml_path' => $venta['xml_path'],
                'cdr_path' => $venta['cdr_path'],
                'codigo_respuesta' => $venta['codigo_respuesta'],
                'mensaje_respuesta' => $venta['mensaje_respuesta'],
                'anulado' => filter_var($venta['anulado'], FILTER_VALIDATE_BOOLEAN),
                'subtotal' => (float)($venta['subtotal'] ?? 0),
                'gravada' => (float)($venta['gravada'] ?? $venta['subtotal'] ?? 0),
                'exonerada' => (float)($venta['exonerada'] ?? 0),
                'inafecta' => (float)($venta['inafecta'] ?? 0),
                'igv' => (float)($venta['comprobante_igv'] ?? $venta['igv'] ?? 0),
                'total' => (float)($venta['comprobante_total'] ?? $venta['total'] ?? 0),
                'descuento' => (float)($venta['descuento_global'] ?? 0),
                'vuelto' => (float)($venta['vuelto'] ?? 0),
                'tipo_pago' => ((float)($venta['monto_credito'] ?? 0) > 0) ? 'credito' : ($paymentBreakdown[0]['method'] ?? 'efectivo'),
                'payment_breakdown' => $paymentBreakdown,
                'cuotas' => $cuotas,
                'cliente' => [
                    'id' => $venta['cliente_id'],
                    'nombre' => $venta['cliente_nombre'],
                    'documento' => $venta['cliente_documento'],
                    'direccion' => $venta['cliente_direccion'],
                ],
                'items' => $stmtItems->fetchAll(),
            ],
        ]);

    case 'tipos_nota_credito':
        $stmt = $db->query("
            SELECT id, codigo, descripcion
            FROM fe_tipos_nota_credito
            WHERE COALESCE(estado, TRUE) = TRUE
            ORDER BY codigo
        ");
        jsonResponse(['ok' => true, 'tipos' => $stmt->fetchAll()]);

    case 'reenviar_sunat':
        $comprobanteId = (int)($input['comprobante_id'] ?? 0);
        if ($comprobanteId <= 0) jsonResponse(['error' => 'Comprobante no valido.'], 400);

        $comp = ventaCargarComprobante($db, $comprobanteId);
        if (!in_array($comp['codigo_tipo_documento'], ['01', '03', '07'], true)) {
            jsonResponse(['error' => 'Solo se puede enviar factura, boleta o nota de credito a SUNAT.'], 400);
        }
        if (ventaComprobanteYaEnviado($comp)) {
            jsonResponse(['error' => 'Este comprobante ya tiene CDR o respuesta SUNAT y no se puede reenviar.'], 400);
        }

        $resultado = ventaEnviarComprobanteSunat($db, $comp);
        if (!empty($resultado['error_nubefact'])) {
            jsonResponse(['ok' => false, 'error' => $resultado['mensaje'] ?? 'SUNAT rechazo el comprobante.', 'comprobante' => $resultado], 422);
        }
        jsonResponse(['ok' => true, 'mensaje' => 'Comprobante reenviado a SUNAT.', 'comprobante' => $resultado]);

    case 'crear_nota_credito':
        $origenId = (int)($input['comprobante_origen_id'] ?? 0);
        $tipoNotaId = (int)($input['tipo_nota_credito_id'] ?? 0);
        $descripcion = trim((string)($input['descripcion'] ?? ''));
        if ($origenId <= 0 || $tipoNotaId <= 0 || $descripcion === '') {
            jsonResponse(['error' => 'Selecciona comprobante, motivo y descripcion para la nota de credito.'], 400);
        }

        $origen = ventaCargarComprobante($db, $origenId);
        if (!in_array($origen['codigo_tipo_documento'], ['01', '03'], true)) {
            jsonResponse(['error' => 'Solo se puede generar nota de credito desde boleta o factura.'], 400);
        }
        if (!ventaComprobanteYaEnviado($origen)) {
            jsonResponse(['error' => 'El comprobante origen debe estar enviado y con CDR/respuesta SUNAT.'], 400);
        }

        $stmtTipo = $db->prepare("SELECT id, codigo, descripcion FROM fe_tipos_nota_credito WHERE id=:id AND COALESCE(estado, TRUE)=TRUE");
        $stmtTipo->execute([':id' => $tipoNotaId]);
        $tipoNota = $stmtTipo->fetch();
        if (!$tipoNota) jsonResponse(['error' => 'Motivo de nota de credito no valido.'], 400);

        $itemsOrigen = ventaCargarItemsComprobante($db, (int)$origen['id']);
        $serieNc = ventaSerieNotaCredito($origen);

        $db->beginTransaction();
        $stmtSerie = $db->prepare("
            UPDATE series_comprobantes
            SET ultimo_numero = ultimo_numero + 1, updated_at = NOW()
            WHERE tipo = 'nota_credito'
              AND serie = :serie
              AND codigo_tipo_documento = '07'
              AND activo = TRUE
            RETURNING id, serie, ultimo_numero, codigo_tipo_documento, tipo_documento_id
        ");
        $stmtSerie->execute([':serie' => $serieNc]);
        $serieRow = $stmtSerie->fetch();
        if (!$serieRow) {
            $db->rollBack();
            jsonResponse(['error' => 'No hay serie activa de nota de credito para este comprobante.'], 400);
        }

        $correlativo = str_pad((string)$serieRow['ultimo_numero'], 8, '0', STR_PAD_LEFT);
        $numeroNc = $serieRow['serie'] . '-' . $correlativo;
        $empresaId = $origen['empresa_emisora_id'] ?: ($db->query("SELECT id FROM empresa_emisora WHERE activo ORDER BY id LIMIT 1")->fetchColumn() ?: null);
        $monedaId = $origen['moneda_id'] ?: ($db->query("SELECT id FROM fe_monedas WHERE codigo='PEN' LIMIT 1")->fetchColumn() ?: null);
        $formaPagoId = $origen['forma_pago_id'] ?: ($db->query("SELECT id FROM fe_formas_pago WHERE descripcion ILIKE '%contado%' ORDER BY id LIMIT 1")->fetchColumn() ?: null);
        $operacionId = $origen['tipo_operacion_id'] ?: ($db->query("SELECT id FROM fe_tipos_operacion WHERE codigo='0101' ORDER BY id LIMIT 1")->fetchColumn() ?: null);

        $stmtNc = $db->prepare("
            INSERT INTO comprobantes_electronicos
            (empresa_emisora_id, cliente_id, orden_venta_id, serie_id, tipo_documento_id,
             codigo_tipo_documento, tipo_operacion_id, operacion_tipo_codigo, moneda_id,
             moneda_codigo, forma_pago_id, sunat_forma_pago, numero, serie, correlativo,
             fecha_emision, observaciones, subtotal, descuento_global, gravada, exonerada, inafecta, igv, total,
             monto_credito, vuelto, cuotas, payment_breakdown, payload_json, estado,
             referencia_comprobante_id, tipo_nota_credito_id, codigo_tipo_nota_credito,
             motivo_nota_credito, descripcion_nota_credito,
             documento_modificado_tipo_documento_codigo, documento_modificado_serie,
             documento_modificado_numero, documento_modificado_numero_completo, documento_modificado_fecha)
            VALUES
            (:empresa_id, :cliente_id, :orden_id, :serie_id, :tipo_documento_id,
             '07', :tipo_operacion_id, '0101', :moneda_id,
             'PEN', :forma_pago_id, 'Contado', :numero, :serie, :correlativo,
             CURRENT_DATE, :observaciones, :subtotal, 0, :gravada, :exonerada, :inafecta, :igv, :total,
             0, 0, '[]'::jsonb, '[]'::jsonb, :payload_json, 'pendiente',
             :referencia_id, :tipo_nota_credito_id, :codigo_tipo_nota_credito,
             :motivo_nota_credito, :descripcion_nota_credito,
             :doc_mod_tipo, :doc_mod_serie, :doc_mod_numero, :doc_mod_numero_completo, :doc_mod_fecha)
            RETURNING id
        ");
        $stmtNc->execute([
            ':empresa_id' => $empresaId,
            ':cliente_id' => $origen['cliente_id'],
            ':orden_id' => $origen['orden_venta_id'],
            ':serie_id' => (int)$serieRow['id'],
            ':tipo_documento_id' => $serieRow['tipo_documento_id'] ?: null,
            ':tipo_operacion_id' => $operacionId,
            ':moneda_id' => $monedaId,
            ':forma_pago_id' => $formaPagoId,
            ':numero' => $numeroNc,
            ':serie' => $serieRow['serie'],
            ':correlativo' => $correlativo,
            ':observaciones' => $descripcion,
            ':subtotal' => $origen['subtotal'],
            ':gravada' => $origen['gravada'],
            ':exonerada' => $origen['exonerada'],
            ':inafecta' => $origen['inafecta'],
            ':igv' => $origen['igv'],
            ':total' => $origen['total'],
            ':payload_json' => json_encode(['referencia' => $origen['numero'], 'motivo' => $tipoNota, 'descripcion' => $descripcion], JSON_UNESCAPED_UNICODE),
            ':referencia_id' => (int)$origen['id'],
            ':tipo_nota_credito_id' => $tipoNotaId,
            ':codigo_tipo_nota_credito' => $tipoNota['codigo'],
            ':motivo_nota_credito' => $tipoNota['descripcion'],
            ':descripcion_nota_credito' => $descripcion,
            ':doc_mod_tipo' => $origen['codigo_tipo_documento'],
            ':doc_mod_serie' => $origen['serie'],
            ':doc_mod_numero' => $origen['correlativo'],
            ':doc_mod_numero_completo' => $origen['numero'],
            ':doc_mod_fecha' => $origen['fecha_emision'],
        ]);
        $notaId = (int)$stmtNc->fetchColumn();

        $stmtDet = $db->prepare("
            INSERT INTO comprobante_detalles
            (comprobante_electronico_id, producto_id, descripcion, cantidad, unidad_codigo,
             codigo_sunat, codigo_interno, valor_unitario, precio_unitario,
             descuento, valor_total, afectacion_igv_codigo, igv, subtotal, total)
            VALUES
            (:comprobante_id, :producto_id, :descripcion, :cantidad, :unidad_codigo,
             :codigo_sunat, :codigo_interno, :valor_unitario, :precio_unitario,
             :descuento, :valor_total, :afectacion_igv_codigo, :igv, :subtotal, :total)
        ");
        foreach ($itemsOrigen as $item) {
            $stmtDet->execute([
                ':comprobante_id' => $notaId,
                ':producto_id' => $item['producto_id'],
                ':descripcion' => $item['descripcion'],
                ':cantidad' => $item['cantidad'],
                ':unidad_codigo' => $item['unidad_codigo'],
                ':codigo_sunat' => $item['codigo_sunat'],
                ':codigo_interno' => $item['codigo_interno'],
                ':valor_unitario' => $item['valor_unitario'],
                ':precio_unitario' => $item['precio_unitario'],
                ':descuento' => $item['descuento'],
                ':valor_total' => $item['valor_total'],
                ':afectacion_igv_codigo' => $item['afectacion_igv_codigo'],
                ':igv' => $item['igv'],
                ':subtotal' => $item['subtotal'],
                ':total' => $item['total'],
            ]);
        }

        if ($maquinariaId > 0) {
            $db->prepare("
                UPDATE maquinarias
                SET cliente_id = :cliente_id,
                    fecha_venta = CURRENT_DATE,
                    garantia_hasta = (CURRENT_DATE + (COALESCE(garantia_meses, 0) || ' months')::INTERVAL)::DATE,
                    estado_venta = 'vendida',
                    precio_venta = :precio_venta,
                    orden_venta_id = :orden_venta_id,
                    comprobante_electronico_id = :comprobante_id
                WHERE id = :id
            ")->execute([
                ':cliente_id' => $clienteId,
                ':precio_venta' => $total,
                ':orden_venta_id' => $ov_id,
                ':comprobante_id' => $comprobanteId,
                ':id' => $maquinariaId,
            ]);
        }

        $db->commit();

        $nota = ventaCargarComprobante($db, $notaId);
        $resultado = ventaEnviarComprobanteSunat($db, $nota);
        if (empty($resultado['error_nubefact']) && (string)($resultado['response_code'] ?? '') === '0') {
            $db->prepare("UPDATE comprobantes_electronicos SET anulado = TRUE, estado = 'Anulado con nota de credito', updated_at = NOW() WHERE id = :id")
               ->execute([':id' => (int)$origen['id']]);
            if (!empty($origen['orden_venta_id'])) {
                $db->prepare("UPDATE ordenes_venta SET estado = 'cancelada', updated_at = NOW() WHERE id = :id")
                   ->execute([':id' => (int)$origen['orden_venta_id']]);
            }
        }

        jsonResponse([
            'ok' => true,
            'mensaje' => 'Nota de credito generada.',
            'nota_id' => $notaId,
            'numero' => $numeroNc,
            'comprobante' => $resultado,
        ]);

    // Combos auxiliares
    case 'series_disponibles':
        $stmt = $db->query("
            SELECT tipo, serie, ultimo_numero, codigo_tipo_documento, descripcion
            FROM series_comprobantes
            WHERE activo = TRUE
              AND tipo IN ('nota_venta', 'boleta', 'factura')
            ORDER BY CASE tipo WHEN 'nota_venta' THEN 1 WHEN 'boleta' THEN 2 WHEN 'factura' THEN 3 ELSE 9 END, serie
        ");
        jsonResponse(['ok' => true, 'series' => $stmt->fetchAll()]);

    case 'default_cliente':
        $stmt = $db->query("
            SELECT id, razon_social, nombre_completo, numero_documento, ruc_dni,
                   tipo_documento_codigo, telefono, direccion
            FROM clientes
            WHERE activo = TRUE
              AND (
                    COALESCE(numero_documento, ruc_dni, '') = '00000000'
                    OR UPPER(COALESCE(razon_social, nombre_completo, '')) = 'CLIENTES VARIOS'
                  )
            ORDER BY CASE WHEN COALESCE(numero_documento, ruc_dni, '') = '00000000' THEN 0 ELSE 1 END, id
            LIMIT 1
        ");
        jsonResponse(['ok' => true, 'cliente' => $stmt->fetch() ?: null]);

    case 'clientes_combo':
        $tipo = $input['tipo_comprobante'] ?? '';
        $where = ['activo = TRUE'];
        if ($tipo === 'factura') {
            $where[] = "(tipo_documento_codigo = '6' OR LENGTH(REGEXP_REPLACE(COALESCE(numero_documento, ruc_dni, ''), '\\D', '', 'g')) = 11)";
        } elseif ($tipo === 'boleta') {
            $where[] = "(COALESCE(tipo_documento_codigo, '') <> '6' AND LENGTH(REGEXP_REPLACE(COALESCE(numero_documento, ruc_dni, ''), '\\D', '', 'g')) <> 11)";
        }
        $stmt = $db->query("
            SELECT id, razon_social, nombre_completo, numero_documento, ruc_dni,
                   tipo_documento_codigo, telefono, direccion
            FROM clientes
            WHERE " . implode(' AND ', $where) . "
            ORDER BY CASE WHEN UPPER(COALESCE(razon_social, nombre_completo, '')) = 'CLIENTES VARIOS' THEN 0 ELSE 1 END,
                     COALESCE(razon_social, nombre_completo)
        ");
        jsonResponse(['ok'=>true,'clientes'=>$stmt->fetchAll()]);

    case 'maquinarias_combo':
        $where = '1=1';
        $params = [];
        if (!empty($input['cliente_id'])) { $where="cliente_id=:cid"; $params[':cid']=(int)$input['cliente_id']; }
        $stmt=$db->prepare("SELECT m.id, CONCAT(m.modelo,' - S/N:',m.numero_serie) AS label, m.cliente_id FROM maquinarias m WHERE m.activo AND $where ORDER BY m.modelo");
        $stmt->execute($params);
        jsonResponse(['ok'=>true,'maquinarias'=>$stmt->fetchAll()]);

    case 'tecnicos_combo':
        $stmt=$db->query("SELECT id, nombre FROM usuarios WHERE activo ORDER BY nombre");
        jsonResponse(['ok'=>true,'tecnicos'=>$stmt->fetchAll()]);

    case 'proyectos_combo':
        $stmt = $db->query("SELECT id, codigo, nombre FROM proyectos WHERE activo ORDER BY codigo");
        jsonResponse(['ok'=>true,'proyectos'=>$stmt->fetchAll()]);

    case 'productos_combo':
        $stmt = $db->query("SELECT id, codigo, nombre, unidad, precio_venta FROM productos WHERE activo ORDER BY nombre");
        jsonResponse(['ok'=>true,'productos'=>$stmt->fetchAll()]);

    default:
        jsonResponse(['error'=>'Accion no reconocida.'],400);

}} catch (PDOException $e) {
    if ($db->inTransaction()) $db->rollBack();
    jsonResponse(['error'=>'Error BD: '.$e->getMessage()],500);
}

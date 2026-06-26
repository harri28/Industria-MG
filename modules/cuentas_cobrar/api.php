<?php
require_once __DIR__ . '/../../config/session.php';
startAppSession();
require_once __DIR__ . '/../../config/database.php';

$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? $action;
} else {
    $input = $_GET;
}

$db   = getDB();
$user = getUsuarioActual();

// ── helpers ──────────────────────────────────────────────────────────────────

function recalcularFactura(PDO $db, int $factura_id): void {
    $db->prepare("
        UPDATE facturas
        SET total_cobrado = COALESCE((
                SELECT SUM(monto) FROM cobros WHERE factura_id = :fid
            ), 0),
            estado = CASE
                WHEN estado = 'anulada' THEN 'anulada'
                WHEN COALESCE((SELECT SUM(monto) FROM cobros WHERE factura_id = :fid2), 0) >= total
                    THEN 'cobrada'
                WHEN COALESCE((SELECT SUM(monto) FROM cobros WHERE factura_id = :fid3), 0) > 0
                    THEN 'parcial'
                WHEN fecha_vencimiento < CURRENT_DATE
                    THEN 'vencida'
                ELSE 'pendiente'
            END
        WHERE id = :fid4
    ")->execute([':fid' => $factura_id, ':fid2' => $factura_id,
                 ':fid3' => $factura_id, ':fid4' => $factura_id]);
}

// ── routing ───────────────────────────────────────────────────────────────────

switch ($action) {

// ── dashboard ─────────────────────────────────────────────────────────────────
case 'dashboard':
    // auto-mark overdue
    $db->exec("
        UPDATE facturas
        SET estado = 'vencida'
        WHERE estado IN ('pendiente','parcial')
          AND fecha_vencimiento < CURRENT_DATE
    ");

    $kpi = $db->query("
        SELECT
            COALESCE(SUM(CASE WHEN estado IN ('pendiente','parcial') THEN total - total_cobrado END), 0) AS total_pendiente,
            COALESCE(SUM(CASE WHEN estado = 'vencida'               THEN total - total_cobrado END), 0) AS total_vencido,
            COALESCE(SUM(CASE WHEN DATE_TRUNC('month', c.fecha) = DATE_TRUNC('month', CURRENT_DATE) THEN c.monto END), 0) AS cobrado_mes
        FROM facturas f
        LEFT JOIN cobros c ON c.factura_id = f.id
    ")->fetch(PDO::FETCH_ASSOC);

    $activas = $db->query("
        SELECT COUNT(*) FROM facturas WHERE estado NOT IN ('cobrada','anulada')
    ")->fetchColumn();

    $aging = $db->query("
        SELECT
            COALESCE(SUM(CASE WHEN fecha_vencimiento >= CURRENT_DATE AND estado IN ('pendiente','parcial') THEN total - total_cobrado END), 0) AS al_dia,
            COALESCE(SUM(CASE WHEN CURRENT_DATE - fecha_vencimiento BETWEEN 1  AND 30 THEN total - total_cobrado END), 0) AS d30,
            COALESCE(SUM(CASE WHEN CURRENT_DATE - fecha_vencimiento BETWEEN 31 AND 60 THEN total - total_cobrado END), 0) AS d60,
            COALESCE(SUM(CASE WHEN CURRENT_DATE - fecha_vencimiento BETWEEN 61 AND 90 THEN total - total_cobrado END), 0) AS d90,
            COALESCE(SUM(CASE WHEN CURRENT_DATE - fecha_vencimiento > 90              THEN total - total_cobrado END), 0) AS d90plus,
            COUNT(CASE WHEN fecha_vencimiento >= CURRENT_DATE AND estado IN ('pendiente','parcial') THEN 1 END) AS cnt_al_dia,
            COUNT(CASE WHEN CURRENT_DATE - fecha_vencimiento BETWEEN 1  AND 30 THEN 1 END) AS cnt_d30,
            COUNT(CASE WHEN CURRENT_DATE - fecha_vencimiento BETWEEN 31 AND 60 THEN 1 END) AS cnt_d60,
            COUNT(CASE WHEN CURRENT_DATE - fecha_vencimiento BETWEEN 61 AND 90 THEN 1 END) AS cnt_d90,
            COUNT(CASE WHEN CURRENT_DATE - fecha_vencimiento > 90              THEN 1 END) AS cnt_d90plus
        FROM facturas
        WHERE estado NOT IN ('cobrada','anulada')
    ")->fetch(PDO::FETCH_ASSOC);

    $topClientes = $db->query("
        SELECT c.razon_social AS cliente,
               COUNT(f.id) AS facturas,
               SUM(f.total - f.total_cobrado) AS pendiente,
               MAX(CASE WHEN f.fecha_vencimiento < CURRENT_DATE THEN (CURRENT_DATE - f.fecha_vencimiento) END) AS max_dias_vencido
        FROM facturas f
        JOIN clientes c ON c.id = f.cliente_id
        WHERE f.estado NOT IN ('cobrada','anulada')
        GROUP BY c.id, c.razon_social
        ORDER BY pendiente DESC
        LIMIT 8
    ")->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse(['ok' => true, 'kpi' => $kpi, 'activas' => (int)$activas, 'aging' => $aging, 'top_clientes' => $topClientes]);

// ── facturas_listar ───────────────────────────────────────────────────────────
case 'facturas_listar':
    $db->exec("
        UPDATE facturas
        SET estado = 'vencida'
        WHERE estado IN ('pendiente','parcial')
          AND fecha_vencimiento < CURRENT_DATE
    ");

    $conds = [];
    $params = [];
    if (!empty($input['estado'])) {
        $conds[]  = "f.estado = :estado";
        $params[':estado'] = $input['estado'];
    }
    if (!empty($input['cliente_id'])) {
        $conds[]  = "f.cliente_id = :cid";
        $params[':cid'] = (int)$input['cliente_id'];
    }
    if (!empty($input['q'])) {
        $conds[]  = "(f.numero ILIKE :q OR c.razon_social ILIKE :q OR f.concepto ILIKE :q)";
        $params[':q'] = '%' . $input['q'] . '%';
    }
    $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

    $rows = $db->prepare("
        SELECT f.id, f.numero, f.concepto,
               c.razon_social AS cliente,
               f.fecha_emision, f.fecha_vencimiento,
               f.subtotal, f.igv, f.total, f.total_cobrado,
               f.total - f.total_cobrado AS pendiente,
               f.estado, f.observaciones
        FROM facturas f
        JOIN clientes c ON c.id = f.cliente_id
        $where
        ORDER BY f.fecha_emision DESC, f.id DESC
        LIMIT 200
    ");
    $rows->execute($params);
    jsonResponse(['ok' => true, 'data' => $rows->fetchAll(PDO::FETCH_ASSOC)]);

// ── factura_obtener ───────────────────────────────────────────────────────────
case 'factura_obtener':
    $id = (int)($input['id'] ?? 0);
    $f  = $db->prepare("
        SELECT f.*, c.razon_social AS cliente
        FROM facturas f JOIN clientes c ON c.id = f.cliente_id
        WHERE f.id = :id
    ");
    $f->execute([':id' => $id]);
    $factura = $f->fetch(PDO::FETCH_ASSOC);
    if (!$factura) jsonResponse(['error' => 'Factura no encontrada'], 404);

    $cobros = $db->prepare("
        SELECT cb.id, cb.fecha, cb.monto, cb.metodo_pago, cb.referencia, cb.notas,
               u.nombre AS usuario
        FROM cobros cb
        LEFT JOIN usuarios u ON u.id = cb.usuario_id
        WHERE cb.factura_id = :id
        ORDER BY cb.fecha, cb.id
    ");
    $cobros->execute([':id' => $id]);
    $factura['cobros'] = $cobros->fetchAll(PDO::FETCH_ASSOC);
    jsonResponse(['ok' => true, 'data' => $factura]);

// ── factura_guardar ───────────────────────────────────────────────────────────
case 'factura_guardar':
    $id          = (int)($input['id'] ?? 0);
    $cliente_id  = (int)($input['cliente_id'] ?? 0);
    $concepto    = trim($input['concepto'] ?? '');
    $cotizacion_id = $input['cotizacion_id'] ? (int)$input['cotizacion_id'] : null;
    $fecha_emision    = $input['fecha_emision']    ?? date('Y-m-d');
    $fecha_vencimiento = $input['fecha_vencimiento'] ?? '';
    $subtotal    = (float)($input['subtotal'] ?? 0);
    $aplica_igv  = (bool)($input['aplica_igv'] ?? false);
    $igv         = $aplica_igv ? round($subtotal * IGV, 2) : 0;
    $total       = round($subtotal + $igv, 2);
    $observaciones = trim($input['observaciones'] ?? '');

    if (!$cliente_id || !$fecha_vencimiento || $subtotal <= 0)
        jsonResponse(['error' => 'Datos incompletos'], 400);

    if ($id) {
        $s = $db->prepare("
            UPDATE facturas SET
                cliente_id = :cid, concepto = :concepto,
                cotizacion_id = :cot_id,
                fecha_emision = :fem, fecha_vencimiento = :fvenc,
                subtotal = :sub, igv = :igv, total = :total,
                observaciones = :obs
            WHERE id = :id AND estado NOT IN ('cobrada','anulada')
        ");
        $s->execute([
            ':cid' => $cliente_id, ':concepto' => $concepto ?: null,
            ':cot_id' => $cotizacion_id,
            ':fem' => $fecha_emision, ':fvenc' => $fecha_vencimiento,
            ':sub' => $subtotal, ':igv' => $igv, ':total' => $total,
            ':obs' => $observaciones ?: null, ':id' => $id,
        ]);
        recalcularFactura($db, $id);
        jsonResponse(['ok' => true, 'id' => $id]);
    } else {
        $numero = generarCodigo('FAC-', 'facturas', 'numero');
        $s = $db->prepare("
            INSERT INTO facturas
                (numero, cliente_id, concepto, cotizacion_id,
                 fecha_emision, fecha_vencimiento,
                 subtotal, igv, total, estado, observaciones, usuario_id)
            VALUES
                (:num, :cid, :concepto, :cot_id,
                 :fem, :fvenc,
                 :sub, :igv, :total, 'pendiente', :obs, :uid)
            RETURNING id
        ");
        $s->execute([
            ':num' => $numero, ':cid' => $cliente_id,
            ':concepto' => $concepto ?: null, ':cot_id' => $cotizacion_id,
            ':fem' => $fecha_emision, ':fvenc' => $fecha_vencimiento,
            ':sub' => $subtotal, ':igv' => $igv, ':total' => $total,
            ':obs' => $observaciones ?: null, ':uid' => $user['id'],
        ]);
        $new_id = $s->fetchColumn();
        jsonResponse(['ok' => true, 'id' => $new_id, 'numero' => $numero]);
    }

// ── factura_anular ────────────────────────────────────────────────────────────
case 'factura_anular':
    $id = (int)($input['id'] ?? 0);
    $db->prepare("UPDATE facturas SET estado='anulada' WHERE id=:id AND estado NOT IN ('cobrada','anulada')")
       ->execute([':id' => $id]);
    jsonResponse(['ok' => true]);

// ── cobro_guardar ─────────────────────────────────────────────────────────────
case 'cobro_guardar':
    $factura_id  = (int)($input['factura_id'] ?? 0);
    $monto       = (float)($input['monto'] ?? 0);
    $fecha       = $input['fecha'] ?? date('Y-m-d');
    $metodo_pago = $input['metodo_pago'] ?? 'efectivo';
    $referencia  = trim($input['referencia'] ?? '');
    $notas       = trim($input['notas'] ?? '');

    if (!$factura_id || $monto <= 0)
        jsonResponse(['error' => 'Datos incompletos'], 400);

    $db->beginTransaction();
    try {
    $fac = $db->prepare("SELECT total, total_cobrado, estado FROM facturas WHERE id=:id FOR UPDATE");
    $fac->execute([':id' => $factura_id]);
    $f = $fac->fetch(PDO::FETCH_ASSOC);
    if (!$f) { $db->rollBack(); jsonResponse(['error' => 'Factura no encontrada'], 404); }
    if ($f['estado'] === 'anulada') { $db->rollBack(); jsonResponse(['error' => 'Factura anulada'], 400); }
    if ($f['estado'] === 'cobrada') { $db->rollBack(); jsonResponse(['error' => 'Factura ya cobrada'], 400); }
    if (round($monto, 2) > round($f['total'] - $f['total_cobrado'], 2))
        { $db->rollBack(); jsonResponse(['error' => 'El monto supera el saldo pendiente'], 400); }
        $db->prepare("
            INSERT INTO cobros (factura_id, fecha, monto, metodo_pago, referencia, notas, usuario_id)
            VALUES (:fid, :fecha, :monto, :met, :ref, :notas, :uid)
        ")->execute([
            ':fid' => $factura_id, ':fecha' => $fecha, ':monto' => $monto,
            ':met' => $metodo_pago, ':ref' => $referencia ?: null,
            ':notas' => $notas ?: null, ':uid' => $user['id'],
        ]);
        recalcularFactura($db, $factura_id);
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['error' => $e->getMessage()], 500);
    }
    jsonResponse(['ok' => true]);

// ── cobro_eliminar ────────────────────────────────────────────────────────────
case 'cobro_eliminar':
    $id = (int)($input['id'] ?? 0);
    $row = $db->prepare("SELECT factura_id FROM cobros WHERE id=:id");
    $row->execute([':id' => $id]);
    $cobro = $row->fetch(PDO::FETCH_ASSOC);
    if (!$cobro) jsonResponse(['error' => 'Cobro no encontrado'], 404);

    $db->beginTransaction();
    try {
        $db->prepare("DELETE FROM cobros WHERE id=:id")->execute([':id' => $id]);
        recalcularFactura($db, (int)$cobro['factura_id']);
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        jsonResponse(['error' => $e->getMessage()], 500);
    }
    jsonResponse(['ok' => true]);

// ── cobros_listar ─────────────────────────────────────────────────────────────
case 'cobros_listar':
    $conds  = [];
    $params = [];
    if (!empty($input['factura_id'])) {
        $conds[]  = "cb.factura_id = :fid";
        $params[':fid'] = (int)$input['factura_id'];
    }
    if (!empty($input['q'])) {
        $conds[]  = "(f.numero ILIKE :q OR cl.razon_social ILIKE :q)";
        $params[':q'] = '%' . $input['q'] . '%';
    }
    $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

    $rows = $db->prepare("
        SELECT cb.id, cb.fecha, cb.monto, cb.metodo_pago, cb.referencia,
               f.numero AS factura_numero, f.total AS factura_total,
               cl.razon_social AS cliente
        FROM cobros cb
        JOIN facturas f  ON f.id  = cb.factura_id
        JOIN clientes cl ON cl.id = f.cliente_id
        $where
        ORDER BY cb.fecha DESC, cb.id DESC
        LIMIT 200
    ");
    $rows->execute($params);
    jsonResponse(['ok' => true, 'data' => $rows->fetchAll(PDO::FETCH_ASSOC)]);

// ── clientes_combo ────────────────────────────────────────────────────────────
case 'clientes_combo':
    $rows = $db->query("SELECT id, razon_social AS nombre FROM clientes WHERE activo = TRUE ORDER BY razon_social")->fetchAll(PDO::FETCH_ASSOC);
    jsonResponse(['ok' => true, 'data' => $rows]);

// ── cotizaciones_combo ────────────────────────────────────────────────────────
case 'cotizaciones_combo':
    $cid  = (int)($input['cliente_id'] ?? 0);
    $rows = $db->prepare("
        SELECT id, numero,
               numero || COALESCE(' — ' || observaciones, '') AS label
        FROM cotizaciones
        WHERE cliente_id = :cid
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $rows->execute([':cid' => $cid]);
    jsonResponse(['ok' => true, 'data' => $rows->fetchAll(PDO::FETCH_ASSOC)]);

default:
    jsonResponse(['error' => 'Acción no válida'], 400);
}

<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
startAppSession();

$db     = getDB();
$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? $action;
} else {
    $input = $_GET;
}

switch ($action) {

    // ── LISTAR ────────────────────────────────────────────────
    case 'listar':
        $conditions = ["cp.activo = TRUE"];
        $params     = [];

        $estado = trim($input['estado'] ?? '');
        if ($estado && $estado !== 'todas') {
            if ($estado === 'vencida') {
                $conditions[] = "cp.fecha_vencimiento < CURRENT_DATE AND cp.estado NOT IN ('pagada','anulada')";
            } else {
                $conditions[] = "cp.estado = :estado";
                $params[':estado'] = $estado;
            }
        }

        $q = trim($input['q'] ?? '');
        if ($q) {
            $conditions[] = "(cp.proveedor_nombre ILIKE :q OR cp.numero ILIKE :q OR cp.numero_doc ILIKE :q OR cp.concepto ILIKE :q)";
            $params[':q'] = "%$q%";
        }

        $where = implode(' AND ', $conditions);
        $stmt  = $db->prepare("
            SELECT cp.id, cp.numero, cp.proveedor_nombre, cp.numero_doc, cp.concepto,
                   cp.fecha_emision, cp.fecha_vencimiento,
                   cp.total, cp.total_pagado,
                   (cp.total - cp.total_pagado) AS pendiente,
                   cp.estado,
                   CASE WHEN cp.fecha_vencimiento < CURRENT_DATE AND cp.estado NOT IN ('pagada','anulada')
                        THEN TRUE ELSE FALSE END AS vencida,
                   (CURRENT_DATE - cp.fecha_vencimiento) AS dias_vencida
            FROM cuentas_pagar cp
            WHERE $where
            ORDER BY
                CASE cp.estado
                    WHEN 'pendiente' THEN 1
                    WHEN 'parcial'   THEN 2
                    WHEN 'pagada'    THEN 4
                    WHEN 'anulada'   THEN 5
                    ELSE 3
                END,
                cp.fecha_vencimiento ASC");
        $stmt->execute($params);
        jsonResponse(['ok' => true, 'cuentas' => $stmt->fetchAll()]);

    // ── OBTENER UNA (con pagos) ────────────────────────────────
    case 'obtener':
        $id   = (int)($input['id'] ?? 0);
        $stmt = $db->prepare("
            SELECT cp.id, cp.numero, cp.proveedor_id, cp.proveedor_nombre,
                   cp.numero_doc, cp.concepto,
                   cp.fecha_emision, cp.fecha_vencimiento,
                   cp.total, cp.total_pagado,
                   (cp.total - cp.total_pagado) AS pendiente,
                   cp.estado, cp.orden_compra_id,
                   oc.numero AS oc_numero,
                   CASE WHEN cp.fecha_vencimiento < CURRENT_DATE AND cp.estado NOT IN ('pagada','anulada')
                        THEN TRUE ELSE FALSE END AS vencida,
                   (CURRENT_DATE - cp.fecha_vencimiento) AS dias_vencida
            FROM cuentas_pagar cp
            LEFT JOIN ordenes_compra oc ON oc.id = cp.orden_compra_id
            WHERE cp.id = :id");
        $stmt->execute([':id' => $id]);
        $cuenta = $stmt->fetch();
        if (!$cuenta) jsonResponse(['error' => 'No encontrado'], 404);

        $stmtP = $db->prepare("
            SELECT id, fecha, monto, metodo_pago, referencia, observaciones
            FROM cuentas_pagar_pagos
            WHERE cuenta_id = :id
            ORDER BY fecha DESC, id DESC");
        $stmtP->execute([':id' => $id]);
        $cuenta['pagos'] = $stmtP->fetchAll();

        jsonResponse(['ok' => true, 'data' => $cuenta]);

    // ── CREAR ──────────────────────────────────────────────────
    case 'crear':
        $db->beginTransaction();
        try {
            $numero = generarCodigo('CP-', 'cuentas_pagar', 'numero');

            // Resolver nombre del proveedor
            $provNombre = trim($input['proveedor_nombre'] ?? '');
            $provId     = (int)($input['proveedor_id'] ?? 0);
            if ($provId && !$provNombre) {
                $r = $db->prepare("SELECT razon_social FROM proveedores WHERE id = :id");
                $r->execute([':id' => $provId]);
                $row = $r->fetch();
                $provNombre = $row['razon_social'] ?? '';
            }

            $stmt = $db->prepare("
                INSERT INTO cuentas_pagar
                    (numero, proveedor_id, proveedor_nombre, numero_doc, concepto,
                     fecha_emision, fecha_vencimiento, total, estado, orden_compra_id)
                VALUES
                    (:num, :prov_id, :prov_nom, :num_doc, :concepto,
                     :fecha_em, :fecha_ve, :total, 'pendiente', :oc_id)
                RETURNING id");
            $stmt->execute([
                ':num'      => $numero,
                ':prov_id'  => $provId ?: null,
                ':prov_nom' => $provNombre,
                ':num_doc'  => trim($input['numero_doc']        ?? ''),
                ':concepto' => trim($input['concepto']          ?? ''),
                ':fecha_em' => $input['fecha_emision']          ?: date('Y-m-d'),
                ':fecha_ve' => $input['fecha_vencimiento'],
                ':total'    => (float)($input['total']          ?? 0),
                ':oc_id'    => (int)($input['orden_compra_id']  ?? 0) ?: null,
            ]);
            $id = $stmt->fetchColumn();
            $db->commit();
            jsonResponse(['ok' => true, 'id' => $id, 'numero' => $numero]);
        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(['error' => $e->getMessage()], 500);
        }

    // ── EDITAR ─────────────────────────────────────────────────
    case 'editar':
        $id = (int)($input['id'] ?? 0);
        $stmt = $db->prepare("
            UPDATE cuentas_pagar SET
                proveedor_nombre  = :prov_nom,
                numero_doc        = :num_doc,
                concepto          = :concepto,
                fecha_emision     = :fecha_em,
                fecha_vencimiento = :fecha_ve,
                total             = :total
            WHERE id = :id AND estado NOT IN ('pagada','anulada')");
        $stmt->execute([
            ':prov_nom' => trim($input['proveedor_nombre']  ?? ''),
            ':num_doc'  => trim($input['numero_doc']        ?? ''),
            ':concepto' => trim($input['concepto']          ?? ''),
            ':fecha_em' => $input['fecha_emision']          ?: date('Y-m-d'),
            ':fecha_ve' => $input['fecha_vencimiento'],
            ':total'    => (float)($input['total']          ?? 0),
            ':id'       => $id,
        ]);
        jsonResponse(['ok' => true]);

    // ── ANULAR ─────────────────────────────────────────────────
    case 'anular':
        $id = (int)($input['id'] ?? 0);
        $db->prepare("UPDATE cuentas_pagar SET estado = 'anulada' WHERE id = :id AND estado != 'pagada'")
           ->execute([':id' => $id]);
        jsonResponse(['ok' => true]);

    // ── REGISTRAR PAGO ─────────────────────────────────────────
    case 'pago_registrar':
        $db->beginTransaction();
        try {
            $cuentaId = (int)($input['cuenta_id'] ?? 0);
            $monto    = (float)($input['monto'] ?? 0);
            if ($monto <= 0) jsonResponse(['error' => 'Monto debe ser mayor a 0'], 400);

            // Verificar pendiente
            $stmt = $db->prepare("SELECT total, total_pagado, estado FROM cuentas_pagar WHERE id = :id FOR UPDATE");
            $stmt->execute([':id' => $cuentaId]);
            $cuenta = $stmt->fetch();
            if (!$cuenta) jsonResponse(['error' => 'Cuenta no encontrada'], 404);
            if ($cuenta['estado'] === 'anulada') jsonResponse(['error' => 'Cuenta anulada'], 400);
            if ($cuenta['estado'] === 'pagada')  jsonResponse(['error' => 'Cuenta ya pagada'], 400);

            $pendiente = (float)$cuenta['total'] - (float)$cuenta['total_pagado'];
            if ($monto > $pendiente + 0.01) {
                jsonResponse(['error' => "Monto excede pendiente (S/ " . number_format($pendiente, 2) . ")"], 400);
            }

            // Insertar pago
            $db->prepare("
                INSERT INTO cuentas_pagar_pagos (cuenta_id, fecha, monto, metodo_pago, referencia, observaciones)
                VALUES (:cid, :fecha, :monto, :metodo, :ref, :obs)")
               ->execute([
                    ':cid'    => $cuentaId,
                    ':fecha'  => $input['fecha']        ?: date('Y-m-d'),
                    ':monto'  => $monto,
                    ':metodo' => $input['metodo_pago']  ?? 'efectivo',
                    ':ref'    => trim($input['referencia']    ?? ''),
                    ':obs'    => trim($input['observaciones'] ?? ''),
               ]);

            // Actualizar total_pagado y estado
            $nuevoPagado = (float)$cuenta['total_pagado'] + $monto;
            $nuevoEstado = ($nuevoPagado >= (float)$cuenta['total'] - 0.01) ? 'pagada' : 'parcial';
            $db->prepare("UPDATE cuentas_pagar SET total_pagado = :tp, estado = :est WHERE id = :id")
               ->execute([':tp' => $nuevoPagado, ':est' => $nuevoEstado, ':id' => $cuentaId]);

            $db->commit();
            jsonResponse(['ok' => true, 'estado_nuevo' => $nuevoEstado]);
        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(['error' => $e->getMessage()], 500);
        }

    // ── ELIMINAR PAGO ──────────────────────────────────────────
    case 'pago_eliminar':
        $db->beginTransaction();
        try {
            $pagoId = (int)($input['id'] ?? 0);
            $stmt   = $db->prepare("SELECT cuenta_id, monto FROM cuentas_pagar_pagos WHERE id = :id");
            $stmt->execute([':id' => $pagoId]);
            $pago = $stmt->fetch();
            if (!$pago) jsonResponse(['error' => 'Pago no encontrado'], 404);

            $db->prepare("DELETE FROM cuentas_pagar_pagos WHERE id = :id")->execute([':id' => $pagoId]);

            // Recalcular total_pagado y estado
            $stmtSum = $db->prepare("SELECT COALESCE(SUM(monto),0) FROM cuentas_pagar_pagos WHERE cuenta_id = :cid");
            $stmtSum->execute([':cid' => $pago['cuenta_id']]);
            $nuevoPagado = (float)$stmtSum->fetchColumn();

            $stmtT = $db->prepare("SELECT total FROM cuentas_pagar WHERE id = :cid");
            $stmtT->execute([':cid' => $pago['cuenta_id']]);
            $total = (float)$stmtT->fetchColumn();

            $nuevoEstado = $nuevoPagado <= 0 ? 'pendiente' : ($nuevoPagado >= $total - 0.01 ? 'pagada' : 'parcial');
            $db->prepare("UPDATE cuentas_pagar SET total_pagado = :tp, estado = :est WHERE id = :cid")
               ->execute([':tp' => $nuevoPagado, ':est' => $nuevoEstado, ':cid' => $pago['cuenta_id']]);

            $db->commit();
            jsonResponse(['ok' => true]);
        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(['error' => $e->getMessage()], 500);
        }

    // ── RESUMEN / AGING ────────────────────────────────────────
    case 'resumen':
        // Totales por estado
        $stmtE = $db->query("
            SELECT estado,
                   COUNT(*) AS cantidad,
                   SUM(total) AS total,
                   SUM(total_pagado) AS pagado,
                   SUM(total - total_pagado) AS pendiente
            FROM cuentas_pagar
            WHERE activo = TRUE AND estado != 'anulada'
            GROUP BY estado");
        $porEstado = $stmtE->fetchAll();

        // Aging de vencidas (solo las no pagadas/anuladas)
        $stmtA = $db->query("
            SELECT
                COUNT(CASE WHEN fecha_vencimiento >= CURRENT_DATE THEN 1 END) AS al_dia,
                SUM(CASE WHEN fecha_vencimiento >= CURRENT_DATE THEN (total-total_pagado) ELSE 0 END) AS monto_al_dia,
                COUNT(CASE WHEN fecha_vencimiento < CURRENT_DATE AND (CURRENT_DATE - fecha_vencimiento) BETWEEN 1  AND 30  THEN 1 END) AS v_1_30,
                SUM(CASE  WHEN fecha_vencimiento < CURRENT_DATE AND (CURRENT_DATE - fecha_vencimiento) BETWEEN 1  AND 30  THEN (total-total_pagado) ELSE 0 END) AS monto_1_30,
                COUNT(CASE WHEN fecha_vencimiento < CURRENT_DATE AND (CURRENT_DATE - fecha_vencimiento) BETWEEN 31 AND 60  THEN 1 END) AS v_31_60,
                SUM(CASE  WHEN fecha_vencimiento < CURRENT_DATE AND (CURRENT_DATE - fecha_vencimiento) BETWEEN 31 AND 60  THEN (total-total_pagado) ELSE 0 END) AS monto_31_60,
                COUNT(CASE WHEN fecha_vencimiento < CURRENT_DATE AND (CURRENT_DATE - fecha_vencimiento) BETWEEN 61 AND 90  THEN 1 END) AS v_61_90,
                SUM(CASE  WHEN fecha_vencimiento < CURRENT_DATE AND (CURRENT_DATE - fecha_vencimiento) BETWEEN 61 AND 90  THEN (total-total_pagado) ELSE 0 END) AS monto_61_90,
                COUNT(CASE WHEN fecha_vencimiento < CURRENT_DATE AND (CURRENT_DATE - fecha_vencimiento) > 90 THEN 1 END) AS v_90mas,
                SUM(CASE  WHEN fecha_vencimiento < CURRENT_DATE AND (CURRENT_DATE - fecha_vencimiento) > 90 THEN (total-total_pagado) ELSE 0 END) AS monto_90mas
            FROM cuentas_pagar
            WHERE activo = TRUE AND estado NOT IN ('pagada','anulada')");
        $aging = $stmtA->fetch();

        jsonResponse(['ok' => true, 'por_estado' => $porEstado, 'aging' => $aging]);

    // ── PROVEEDORES PARA SELECT ────────────────────────────────
    case 'proveedores_listar':
        $stmt = $db->query("SELECT id, razon_social, ruc FROM proveedores WHERE activo = TRUE ORDER BY razon_social");
        jsonResponse(['ok' => true, 'proveedores' => $stmt->fetchAll()]);

    default:
        jsonResponse(['error' => 'Acción no reconocida'], 400);
}

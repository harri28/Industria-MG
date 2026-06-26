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
        $conditions = ["1=1"];
        $params     = [];

        $estado = trim($input['estado'] ?? '');
        if ($estado && $estado !== 'todas') {
            if ($estado === 'vencida') {
                $conditions[] = "fecha_vencimiento < CURRENT_DATE AND estado NOT IN ('pagada','anulada')";
            } else {
                $conditions[] = "estado = :estado";
                $params[':estado'] = $estado;
            }
        }

        $q = trim($input['q'] ?? '');
        if ($q) {
            $conditions[] = "(proveedor ILIKE :q OR codigo ILIKE :q OR concepto ILIKE :q)";
            $params[':q'] = "%$q%";
        }

        $where = implode(' AND ', $conditions);
        $stmt  = $db->prepare("
            SELECT id, codigo, proveedor, concepto,
                   fecha_emision, fecha_vencimiento,
                   monto_total AS total, monto_pagado AS pagado,
                   (monto_total - monto_pagado) AS pendiente,
                   estado,
                   CASE WHEN fecha_vencimiento IS NOT NULL
                             AND fecha_vencimiento < CURRENT_DATE
                             AND estado NOT IN ('pagada','anulada')
                        THEN TRUE ELSE FALSE END AS vencida,
                   GREATEST(0, CURRENT_DATE - fecha_vencimiento) AS dias_vencida
            FROM cuentas_pagar
            WHERE $where
            ORDER BY
                CASE estado
                    WHEN 'pendiente' THEN 1
                    WHEN 'parcial'   THEN 2
                    WHEN 'pagada'    THEN 4
                    WHEN 'anulada'   THEN 5
                    ELSE 3
                END,
                fecha_vencimiento ASC NULLS LAST");
        $stmt->execute($params);
        jsonResponse(['ok' => true, 'cuentas' => $stmt->fetchAll()]);

    // ── OBTENER UNA (con pagos) ────────────────────────────────
    case 'obtener':
        $id   = (int)($input['id'] ?? 0);
        $stmt = $db->prepare("
            SELECT cp.id, cp.codigo, cp.proveedor, cp.concepto, cp.tipo,
                   cp.fecha_emision, cp.fecha_vencimiento,
                   cp.monto_total AS total, cp.monto_pagado AS pagado,
                   (cp.monto_total - cp.monto_pagado) AS pendiente,
                   cp.estado, cp.observaciones, cp.orden_compra_id,
                   oc.numero AS oc_numero,
                   CASE WHEN cp.fecha_vencimiento IS NOT NULL
                             AND cp.fecha_vencimiento < CURRENT_DATE
                             AND cp.estado NOT IN ('pagada','anulada')
                        THEN TRUE ELSE FALSE END AS vencida,
                   GREATEST(0, CURRENT_DATE - cp.fecha_vencimiento) AS dias_vencida
            FROM cuentas_pagar cp
            LEFT JOIN ordenes_compra oc ON oc.id = cp.orden_compra_id
            WHERE cp.id = :id");
        $stmt->execute([':id' => $id]);
        $cuenta = $stmt->fetch();
        if (!$cuenta) jsonResponse(['error' => 'No encontrado'], 404);

        $stmtP = $db->prepare("
            SELECT id, fecha, monto, metodo_pago, referencia, notas AS observaciones
            FROM cuentas_pagar_pagos
            WHERE cuenta_pagar_id = :id
            ORDER BY fecha DESC, id DESC");
        $stmtP->execute([':id' => $id]);
        $cuenta['pagos'] = $stmtP->fetchAll();

        jsonResponse(['ok' => true, 'data' => $cuenta]);

    // ── CREAR ──────────────────────────────────────────────────
    case 'crear':
        $db->beginTransaction();
        try {
            $codigo = generarCodigo('CP-', 'cuentas_pagar', 'codigo');
            $stmt   = $db->prepare("
                INSERT INTO cuentas_pagar
                    (codigo, proveedor, concepto, fecha_emision, fecha_vencimiento,
                     monto_total, estado, tipo, observaciones, orden_compra_id)
                VALUES
                    (:cod, :prov, :conc, :fem, :fve,
                     :total, 'pendiente', 'proveedor', :obs, :oc)
                RETURNING id");
            $stmt->execute([
                ':cod'   => $codigo,
                ':prov'  => trim($input['proveedor']          ?? ''),
                ':conc'  => trim($input['concepto']           ?? ''),
                ':fem'   => $input['fecha_emision']           ?: date('Y-m-d'),
                ':fve'   => $input['fecha_vencimiento']       ?? null,
                ':total' => (float)($input['total']           ?? 0),
                ':obs'   => trim($input['observaciones']      ?? ''),
                ':oc'    => (int)($input['orden_compra_id']   ?? 0) ?: null,
            ]);
            $id = $stmt->fetchColumn();
            $db->commit();
            jsonResponse(['ok' => true, 'id' => $id, 'codigo' => $codigo]);
        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(['error' => $e->getMessage()], 500);
        }

    // ── EDITAR ─────────────────────────────────────────────────
    case 'editar':
        $id   = (int)($input['id'] ?? 0);
        $stmt = $db->prepare("
            UPDATE cuentas_pagar SET
                proveedor         = :prov,
                concepto          = :conc,
                fecha_emision     = :fem,
                fecha_vencimiento = :fve,
                monto_total       = :total,
                observaciones     = :obs
            WHERE id = :id AND estado NOT IN ('pagada','anulada')");
        $stmt->execute([
            ':prov'  => trim($input['proveedor']        ?? ''),
            ':conc'  => trim($input['concepto']         ?? ''),
            ':fem'   => $input['fecha_emision']         ?: date('Y-m-d'),
            ':fve'   => $input['fecha_vencimiento']     ?? null,
            ':total' => (float)($input['total']         ?? 0),
            ':obs'   => trim($input['observaciones']    ?? ''),
            ':id'    => $id,
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

            $stmt = $db->prepare("SELECT monto_total, monto_pagado, estado FROM cuentas_pagar WHERE id = :id FOR UPDATE");
            $stmt->execute([':id' => $cuentaId]);
            $cuenta = $stmt->fetch();
            if (!$cuenta) jsonResponse(['error' => 'Cuenta no encontrada'], 404);
            if ($cuenta['estado'] === 'anulada') jsonResponse(['error' => 'Cuenta anulada'], 400);
            if ($cuenta['estado'] === 'pagada')  jsonResponse(['error' => 'Cuenta ya pagada'], 400);

            $pendiente = (float)$cuenta['monto_total'] - (float)$cuenta['monto_pagado'];
            if ($monto > $pendiente + 0.01) {
                jsonResponse(['error' => 'Monto excede pendiente (S/ ' . number_format($pendiente, 2) . ')'], 400);
            }

            $db->prepare("
                INSERT INTO cuentas_pagar_pagos
                    (cuenta_pagar_id, fecha, monto, metodo_pago, referencia, notas)
                VALUES (:cid, :fecha, :monto, :metodo, :ref, :obs)")
               ->execute([
                    ':cid'    => $cuentaId,
                    ':fecha'  => $input['fecha']        ?: date('Y-m-d'),
                    ':monto'  => $monto,
                    ':metodo' => $input['metodo_pago']  ?? 'efectivo',
                    ':ref'    => trim($input['referencia']    ?? ''),
                    ':obs'    => trim($input['observaciones'] ?? ''),
               ]);

            $nuevoPagado = (float)$cuenta['monto_pagado'] + $monto;
            $nuevoEstado = ($nuevoPagado >= (float)$cuenta['monto_total'] - 0.01) ? 'pagada' : 'parcial';
            $db->prepare("UPDATE cuentas_pagar SET monto_pagado = :mp, estado = :est WHERE id = :id")
               ->execute([':mp' => $nuevoPagado, ':est' => $nuevoEstado, ':id' => $cuentaId]);

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
            $stmt   = $db->prepare("SELECT cuenta_pagar_id, monto FROM cuentas_pagar_pagos WHERE id = :id");
            $stmt->execute([':id' => $pagoId]);
            $pago = $stmt->fetch();
            if (!$pago) jsonResponse(['error' => 'Pago no encontrado'], 404);

            $db->prepare("DELETE FROM cuentas_pagar_pagos WHERE id = :id")->execute([':id' => $pagoId]);

            $stmtSum = $db->prepare("SELECT COALESCE(SUM(monto),0) FROM cuentas_pagar_pagos WHERE cuenta_pagar_id = :cid");
            $stmtSum->execute([':cid' => $pago['cuenta_pagar_id']]);
            $nuevoPagado = (float)$stmtSum->fetchColumn();

            $stmtT = $db->prepare("SELECT monto_total FROM cuentas_pagar WHERE id = :cid");
            $stmtT->execute([':cid' => $pago['cuenta_pagar_id']]);
            $total = (float)$stmtT->fetchColumn();

            $nuevoEstado = $nuevoPagado <= 0 ? 'pendiente' : ($nuevoPagado >= $total - 0.01 ? 'pagada' : 'parcial');
            $db->prepare("UPDATE cuentas_pagar SET monto_pagado = :mp, estado = :est WHERE id = :cid")
               ->execute([':mp' => $nuevoPagado, ':est' => $nuevoEstado, ':cid' => $pago['cuenta_pagar_id']]);

            $db->commit();
            jsonResponse(['ok' => true]);
        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(['error' => $e->getMessage()], 500);
        }

    // ── RESUMEN / AGING ────────────────────────────────────────
    case 'resumen':
        $stmtE = $db->query("
            SELECT estado,
                   COUNT(*) AS cantidad,
                   SUM(monto_total) AS total,
                   SUM(monto_pagado) AS pagado,
                   SUM(monto_total - monto_pagado) AS pendiente
            FROM cuentas_pagar
            WHERE estado != 'anulada'
            GROUP BY estado");
        $porEstado = $stmtE->fetchAll();

        $stmtA = $db->query("
            SELECT
                COUNT(CASE WHEN fecha_vencimiento >= CURRENT_DATE THEN 1 END) AS al_dia,
                COALESCE(SUM(CASE WHEN fecha_vencimiento >= CURRENT_DATE THEN (monto_total-monto_pagado) END),0) AS monto_al_dia,
                COUNT(CASE WHEN fecha_vencimiento < CURRENT_DATE AND (CURRENT_DATE-fecha_vencimiento) BETWEEN 1  AND 30  THEN 1 END) AS v_1_30,
                COALESCE(SUM(CASE WHEN fecha_vencimiento < CURRENT_DATE AND (CURRENT_DATE-fecha_vencimiento) BETWEEN 1  AND 30  THEN (monto_total-monto_pagado) END),0) AS monto_1_30,
                COUNT(CASE WHEN fecha_vencimiento < CURRENT_DATE AND (CURRENT_DATE-fecha_vencimiento) BETWEEN 31 AND 60  THEN 1 END) AS v_31_60,
                COALESCE(SUM(CASE WHEN fecha_vencimiento < CURRENT_DATE AND (CURRENT_DATE-fecha_vencimiento) BETWEEN 31 AND 60  THEN (monto_total-monto_pagado) END),0) AS monto_31_60,
                COUNT(CASE WHEN fecha_vencimiento < CURRENT_DATE AND (CURRENT_DATE-fecha_vencimiento) BETWEEN 61 AND 90  THEN 1 END) AS v_61_90,
                COALESCE(SUM(CASE WHEN fecha_vencimiento < CURRENT_DATE AND (CURRENT_DATE-fecha_vencimiento) BETWEEN 61 AND 90  THEN (monto_total-monto_pagado) END),0) AS monto_61_90,
                COUNT(CASE WHEN fecha_vencimiento < CURRENT_DATE AND (CURRENT_DATE-fecha_vencimiento) > 90 THEN 1 END) AS v_90mas,
                COALESCE(SUM(CASE WHEN fecha_vencimiento < CURRENT_DATE AND (CURRENT_DATE-fecha_vencimiento) > 90 THEN (monto_total-monto_pagado) END),0) AS monto_90mas
            FROM cuentas_pagar
            WHERE estado NOT IN ('pagada','anulada')");
        $aging = $stmtA->fetch();

        jsonResponse(['ok' => true, 'por_estado' => $porEstado, 'aging' => $aging]);

    default:
        jsonResponse(['error' => 'Acción no reconocida'], 400);
}

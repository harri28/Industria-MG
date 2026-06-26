<?php
ini_set('display_errors', 0);
error_reporting(0);
require_once __DIR__ . '/../../config/session.php';
startAppSession();
require_once __DIR__ . '/../../config/database.php';

$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input  = !empty($_POST)
              ? $_POST
              : (json_decode(file_get_contents('php://input'), true) ?? []);
    $action = $input['action'] ?? $action;
} else {
    $input = $_GET;
}

$db         = getDB();
$usuario_id = $_SESSION['usuario']['id'] ?? null;

switch ($action) {

    // -------------------------------------------------------
    // GET: listar préstamos con totales de cuotas
    // -------------------------------------------------------
    case 'listar_prestamos':
        $tipo   = $input['tipo']   ?? '';
        $estado = $input['estado'] ?? '';
        $conds  = [];
        $params = [];
        if ($tipo)   { $conds[] = 'p.tipo = :tipo';     $params[':tipo']   = $tipo; }
        if ($estado) { $conds[] = 'p.estado = :estado'; $params[':estado'] = $estado; }
        $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

        $stmt = $db->prepare("
            SELECT p.*,
                COUNT(c.id)                                                       AS total_cuotas,
                COUNT(CASE WHEN c.estado='pagado'   THEN 1 END)                   AS cuotas_pagadas,
                COUNT(CASE WHEN c.estado='pendiente' OR c.estado='vencido' THEN 1 END) AS cuotas_pendientes,
                COALESCE(SUM(CASE WHEN c.estado='pagado' THEN c.monto_total END), 0) AS monto_pagado,
                COALESCE(SUM(CASE WHEN c.estado<>'pagado' THEN c.monto_total END), 0) AS monto_pendiente
            FROM prestamos p
            LEFT JOIN prestamo_cuotas c ON c.prestamo_id = p.id
            $where
            GROUP BY p.id
            ORDER BY p.created_at DESC
        ");
        $stmt->execute($params);
        jsonResponse(['ok' => true, 'prestamos' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    // -------------------------------------------------------
    // GET: detalle de un préstamo + cuotas
    // -------------------------------------------------------
    case 'detalle_prestamo':
        $id = intval($input['id'] ?? 0);
        $stmt = $db->prepare("SELECT * FROM prestamos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $prestamo = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$prestamo) jsonResponse(['error' => 'Préstamo no encontrado']);

        $stmt = $db->prepare("SELECT * FROM prestamo_cuotas WHERE prestamo_id = :id ORDER BY numero_cuota");
        $stmt->execute([':id' => $id]);
        $cuotas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        jsonResponse(['ok' => true, 'prestamo' => $prestamo, 'cuotas' => $cuotas]);
        break;

    // -------------------------------------------------------
    // POST: crear préstamo y generar cuotas
    // -------------------------------------------------------
    case 'guardar_prestamo':
        $tipo     = $input['tipo']        ?? '';
        $entidad  = trim($input['entidad'] ?? '');
        $monto    = floatval($input['monto_total']   ?? 0);
        $tasa     = floatval($input['tasa_interes']  ?? 0) / 100;
        $plazo    = intval($input['plazo_meses']     ?? 1);
        $fini     = $input['fecha_inicio'] ?? '';
        $desc     = trim($input['descripcion'] ?? '');

        if (!in_array($tipo, ['recibido','otorgado'])) jsonResponse(['error' => 'Tipo inválido']);
        if (!$entidad) jsonResponse(['error' => 'La entidad es requerida']);
        if ($monto <= 0) jsonResponse(['error' => 'El monto debe ser mayor a 0']);
        if ($plazo < 1)  jsonResponse(['error' => 'El plazo debe ser al menos 1 mes']);
        if (!$fini)      jsonResponse(['error' => 'La fecha de inicio es requerida']);

        $codigo   = generarCodigo('PREST-', 'prestamos', 'codigo');
        $fvenc    = date('Y-m-d', strtotime($fini . " +{$plazo} months"));

        // Cuota mensual (sistema francés)
        if ($tasa > 0) {
            $cuota_total = $monto * ($tasa / 12) / (1 - pow(1 + $tasa / 12, -$plazo));
        } else {
            $cuota_total = $monto / $plazo;
        }

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("
                INSERT INTO prestamos
                    (codigo, tipo, entidad, monto_total, tasa_interes, plazo_meses,
                     fecha_inicio, fecha_vencimiento, descripcion, usuario_id)
                VALUES (:cod, :tipo, :ent, :monto, :tasa, :plazo, :fini, :fvenc, :desc, :uid)
                RETURNING id
            ");
            $stmt->execute([
                ':cod'   => $codigo, ':tipo' => $tipo,  ':ent'  => $entidad,
                ':monto' => $monto,  ':tasa' => $tasa,  ':plazo'=> $plazo,
                ':fini'  => $fini,   ':fvenc'=> $fvenc, ':desc' => $desc ?: null,
                ':uid'   => $usuario_id,
            ]);
            $prestamo_id = $stmt->fetchColumn();

            // Generar cuotas (francés)
            $saldo = $monto;
            $ins   = $db->prepare("
                INSERT INTO prestamo_cuotas
                    (prestamo_id, numero_cuota, fecha_vencimiento, monto_capital, monto_interes, monto_total)
                VALUES (:pid, :num, :fv, :cap, :int, :tot)
            ");
            for ($i = 1; $i <= $plazo; $i++) {
                $fv      = date('Y-m-d', strtotime($fini . " +{$i} months"));
                $interes = $saldo * ($tasa / 12);
                $capital = $tasa > 0 ? $cuota_total - $interes : $monto / $plazo;
                if ($i === $plazo) $capital = $saldo; // ajuste última cuota
                $saldo  -= $capital;
                $ins->execute([
                    ':pid' => $prestamo_id, ':num' => $i,  ':fv'  => $fv,
                    ':cap' => round($capital, 2), ':int' => round($interes, 2),
                    ':tot' => round($capital + $interes, 2),
                ]);
            }
            $db->commit();
            jsonResponse(['ok' => true, 'id' => $prestamo_id, 'codigo' => $codigo]);
        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(['error' => 'Error al guardar el préstamo']);
        }
        break;

    // -------------------------------------------------------
    // POST: registrar pago de una cuota
    // -------------------------------------------------------
    case 'pagar_cuota':
        $cuota_id = intval($input['cuota_id'] ?? 0);
        $fecha    = $input['fecha_pago']    ?? date('Y-m-d');
        $obs      = trim($input['observaciones'] ?? '');

        $stmt = $db->prepare("
            UPDATE prestamo_cuotas
            SET estado='pagado', fecha_pago=:fp, observaciones=COALESCE(NULLIF(:obs,''), observaciones)
            WHERE id=:id AND estado<>'pagado'
        ");
        $stmt->execute([':fp' => $fecha, ':obs' => $obs, ':id' => $cuota_id]);
        if (!$stmt->rowCount()) jsonResponse(['error' => 'Cuota no encontrada o ya pagada']);

        // ¿Todas las cuotas pagadas? → marcar préstamo como pagado
        $stmt2 = $db->prepare("
            SELECT prestamo_id FROM prestamo_cuotas WHERE id = :id
        ");
        $stmt2->execute([':id' => $cuota_id]);
        $pid = $stmt2->fetchColumn();

        $pendientes = $db->prepare("
            SELECT COUNT(*) FROM prestamo_cuotas WHERE prestamo_id=:pid AND estado<>'pagado'
        ");
        $pendientes->execute([':pid' => $pid]);
        if ((int)$pendientes->fetchColumn() === 0) {
            $db->prepare("UPDATE prestamos SET estado='pagado' WHERE id=:pid")
               ->execute([':pid' => $pid]);
        }

        jsonResponse(['ok' => true]);
        break;

    // -------------------------------------------------------
    // POST: cancelar préstamo
    // -------------------------------------------------------
    case 'cancelar_prestamo':
        $id = intval($input['id'] ?? 0);
        $db->prepare("UPDATE prestamos SET estado='cancelado' WHERE id=:id AND estado='activo'")
           ->execute([':id' => $id]);
        jsonResponse(['ok' => true]);
        break;

    default:
        jsonResponse(['error' => 'Acción no reconocida']);
}

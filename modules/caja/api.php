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
    // GET: caja abierta actual con totales
    // -------------------------------------------------------
    case 'estado_actual':
        $caja = $db->query("
            SELECT c.*,
                   u1.nombre AS usuario_apertura_nombre,
                   u2.nombre AS usuario_cierre_nombre,
                   COALESCE(SUM(CASE WHEN m.tipo='ingreso' THEN m.monto ELSE 0 END),0) AS total_ingresos,
                   COALESCE(SUM(CASE WHEN m.tipo='egreso'  THEN m.monto ELSE 0 END),0) AS total_egresos
            FROM cajas c
            LEFT JOIN usuarios u1 ON u1.id = c.usuario_apertura_id
            LEFT JOIN usuarios u2 ON u2.id = c.usuario_cierre_id
            LEFT JOIN caja_movimientos m ON m.caja_id = c.id
            WHERE c.estado = 'abierta'
            GROUP BY c.id, u1.nombre, u2.nombre
            ORDER BY c.hora_apertura DESC
            LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);

        jsonResponse(['ok' => true, 'caja' => $caja ?: null]);
        break;

    // -------------------------------------------------------
    // POST: abrir caja
    // -------------------------------------------------------
    case 'abrir_caja':
        $saldo = floatval($input['saldo_inicial'] ?? 0);
        $obs   = trim($input['observaciones'] ?? '');

        $abierta = $db->query("SELECT id FROM cajas WHERE estado='abierta' LIMIT 1")->fetch();
        if ($abierta) {
            jsonResponse(['error' => 'Ya existe una caja abierta']);
        }

        $stmt = $db->prepare("
            INSERT INTO cajas (fecha, usuario_apertura_id, hora_apertura, saldo_inicial, estado, observaciones)
            VALUES (CURRENT_DATE, :uid, NOW(), :saldo, 'abierta', :obs)
            RETURNING id
        ");
        $stmt->execute([':uid' => $usuario_id, ':saldo' => $saldo, ':obs' => $obs ?: null]);
        jsonResponse(['ok' => true, 'id' => $stmt->fetchColumn()]);
        break;

    // -------------------------------------------------------
    // POST: cerrar caja (incluye arqueo final)
    // -------------------------------------------------------
    case 'cerrar_caja':
        $caja_id      = intval($input['caja_id'] ?? 0);
        $saldo_fisico = floatval($input['saldo_fisico'] ?? 0);
        $obs          = trim($input['observaciones'] ?? '');

        $stmt = $db->prepare("
            SELECT c.saldo_inicial,
                   COALESCE(SUM(CASE WHEN m.tipo='ingreso' THEN m.monto ELSE 0 END),0) AS total_ingresos,
                   COALESCE(SUM(CASE WHEN m.tipo='egreso'  THEN m.monto ELSE 0 END),0) AS total_egresos
            FROM cajas c
            LEFT JOIN caja_movimientos m ON m.caja_id = c.id
            WHERE c.id = :id AND c.estado = 'abierta'
            GROUP BY c.id
        ");
        $stmt->execute([':id' => $caja_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) {
            jsonResponse(['error' => 'Caja no encontrada o ya cerrada']);
        }

        $saldo_sistema = floatval($data['saldo_inicial'])
                       + floatval($data['total_ingresos'])
                       - floatval($data['total_egresos']);
        $diferencia    = $saldo_fisico - $saldo_sistema;

        $stmt = $db->prepare("
            UPDATE cajas SET
                estado              = 'cerrada',
                usuario_cierre_id   = :uid,
                hora_cierre         = NOW(),
                saldo_final_sistema = :sist,
                saldo_final_fisico  = :fis,
                diferencia          = :dif,
                observaciones       = COALESCE(NULLIF(:obs,''), observaciones)
            WHERE id = :id
        ");
        $stmt->execute([
            ':uid'  => $usuario_id,
            ':sist' => $saldo_sistema,
            ':fis'  => $saldo_fisico,
            ':dif'  => $diferencia,
            ':obs'  => $obs,
            ':id'   => $caja_id,
        ]);

        jsonResponse(['ok' => true, 'saldo_sistema' => $saldo_sistema, 'diferencia' => $diferencia]);
        break;

    // -------------------------------------------------------
    // POST: registrar movimiento (ingreso o egreso)
    // -------------------------------------------------------
    case 'registrar_movimiento':
        $caja_id    = intval($input['caja_id'] ?? 0);
        $tipo       = $input['tipo'] ?? '';
        $concepto   = trim($input['concepto'] ?? '');
        $descripcion= trim($input['descripcion'] ?? '');
        $monto      = floatval($input['monto'] ?? 0);
        $banco_id   = intval($input['banco_id'] ?? 0) ?: null;

        if (!in_array($tipo, ['ingreso','egreso'])) jsonResponse(['error' => 'Tipo inválido']);
        if ($monto <= 0)  jsonResponse(['error' => 'El monto debe ser mayor a 0']);
        if (!$concepto)   jsonResponse(['error' => 'Concepto requerido']);

        $stmt = $db->prepare("SELECT id FROM cajas WHERE id=:id AND estado='abierta'");
        $stmt->execute([':id' => $caja_id]);
        if (!$stmt->fetch()) jsonResponse(['error' => 'Caja no encontrada o cerrada']);

        $stmt = $db->prepare("
            INSERT INTO caja_movimientos (caja_id, tipo, concepto, descripcion, monto, banco_id, usuario_id, fecha)
            VALUES (:cid, :tipo, :con, :desc, :monto, :bid, :uid, NOW())
            RETURNING id
        ");
        $stmt->execute([
            ':cid'  => $caja_id,
            ':tipo' => $tipo,
            ':con'  => $concepto,
            ':desc' => $descripcion ?: null,
            ':monto'=> $monto,
            ':bid'  => $banco_id,
            ':uid'  => $usuario_id,
        ]);
        jsonResponse(['ok' => true, 'id' => $stmt->fetchColumn()]);
        break;

    // -------------------------------------------------------
    // GET: movimientos de una sesión
    // -------------------------------------------------------
    case 'listar_movimientos':
        $caja_id = intval($input['caja_id'] ?? 0);
        $stmt    = $db->prepare("
            SELECT m.*, u.nombre AS usuario_nombre
            FROM caja_movimientos m
            LEFT JOIN usuarios u ON u.id = m.usuario_id
            WHERE m.caja_id = :id
            ORDER BY m.fecha DESC
        ");
        $stmt->execute([':id' => $caja_id]);
        jsonResponse(['ok' => true, 'movimientos' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    // -------------------------------------------------------
    // GET: historial de sesiones
    // -------------------------------------------------------
    case 'historial_cajas':
        $limit  = max(1, intval($input['limit']  ?? 30));
        $offset = max(0, intval($input['offset'] ?? 0));
        $stmt   = $db->prepare("
            SELECT c.*,
                   u1.nombre AS usuario_apertura_nombre,
                   u2.nombre AS usuario_cierre_nombre,
                   COALESCE(SUM(CASE WHEN m.tipo='ingreso' THEN m.monto ELSE 0 END),0) AS total_ingresos,
                   COALESCE(SUM(CASE WHEN m.tipo='egreso'  THEN m.monto ELSE 0 END),0) AS total_egresos,
                   COUNT(m.id) AS total_movimientos
            FROM cajas c
            LEFT JOIN usuarios u1 ON u1.id = c.usuario_apertura_id
            LEFT JOIN usuarios u2 ON u2.id = c.usuario_cierre_id
            LEFT JOIN caja_movimientos m ON m.caja_id = c.id
            GROUP BY c.id, u1.nombre, u2.nombre
            ORDER BY c.hora_apertura DESC
            LIMIT :lim OFFSET :off
        ");
        $stmt->bindValue(':lim', $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        jsonResponse(['ok' => true, 'cajas' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    // -------------------------------------------------------
    // GET: listar cuentas bancarias activas
    // -------------------------------------------------------
    case 'listar_bancos':
        $stmt = $db->query("
            SELECT b.*,
                COALESCE(SUM(CASE WHEN m.tipo='ingreso' THEN m.monto ELSE 0 END), 0) AS total_ingresos,
                COALESCE(SUM(CASE WHEN m.tipo='egreso'  THEN m.monto ELSE 0 END), 0) AS total_egresos
            FROM bancos b
            LEFT JOIN caja_movimientos m ON m.banco_id = b.id
            WHERE b.activo = true
            GROUP BY b.id
            ORDER BY b.nombre
        ");
        jsonResponse(['ok' => true, 'bancos' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    // -------------------------------------------------------
    // POST: crear o actualizar banco
    // -------------------------------------------------------
    case 'guardar_banco':
        $id     = intval($input['id'] ?? 0);
        $nombre = trim($input['nombre'] ?? '');
        $banco  = trim($input['banco']  ?? '');
        $numero = trim($input['numero_cuenta'] ?? '');
        $tipo   = $input['tipo_cuenta']  ?? 'corriente';
        $saldo  = floatval($input['saldo_actual'] ?? 0);

        if (!$nombre) jsonResponse(['error' => 'El nombre es requerido']);
        if (!$banco)  jsonResponse(['error' => 'El banco es requerido']);
        if (!in_array($tipo, ['corriente','ahorro','recaudadora'])) $tipo = 'corriente';

        // Procesar logo si se subió imagen
        $logo_nuevo = null;
        if (!empty($_FILES['logo']['tmp_name'])) {
            $file    = $_FILES['logo'];
            $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
            if (!in_array($file['type'], $allowed)) {
                jsonResponse(['error' => 'Formato de imagen no válido (JPG, PNG, WEBP, GIF)']);
            }
            if ($file['size'] > 1024 * 1024) {
                jsonResponse(['error' => 'La imagen no debe superar 1 MB']);
            }
            $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'banco_' . time() . '_' . mt_rand(100,999) . '.' . strtolower($ext);
            $dest     = __DIR__ . '/../../assets/uploads/bancos/' . $filename;
            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                jsonResponse(['error' => 'No se pudo guardar la imagen']);
            }
            $logo_nuevo = $filename;
        }

        if ($id) {
            // Si hay logo nuevo, eliminar el anterior
            if ($logo_nuevo) {
                $viejo = $db->prepare("SELECT logo FROM bancos WHERE id=:id");
                $viejo->execute([':id'=>$id]);
                $row = $viejo->fetch();
                if ($row && $row['logo']) {
                    $ruta = __DIR__ . '/../../assets/uploads/bancos/' . $row['logo'];
                    if (file_exists($ruta)) unlink($ruta);
                }
                $stmt = $db->prepare("
                    UPDATE bancos SET nombre=:nom, banco=:ban, numero_cuenta=:num,
                        tipo_cuenta=:tipo, saldo_actual=:sal, logo=:logo
                    WHERE id=:id AND activo=true
                ");
                $stmt->execute([':nom'=>$nombre,':ban'=>$banco,':num'=>$numero?:null,
                                ':tipo'=>$tipo,':sal'=>$saldo,':logo'=>$logo_nuevo,':id'=>$id]);
            } else {
                $stmt = $db->prepare("
                    UPDATE bancos SET nombre=:nom, banco=:ban, numero_cuenta=:num,
                        tipo_cuenta=:tipo, saldo_actual=:sal
                    WHERE id=:id AND activo=true
                ");
                $stmt->execute([':nom'=>$nombre,':ban'=>$banco,':num'=>$numero?:null,
                                ':tipo'=>$tipo,':sal'=>$saldo,':id'=>$id]);
            }
            jsonResponse(['ok' => true, 'id' => $id]);
        } else {
            $stmt = $db->prepare("
                INSERT INTO bancos (nombre, banco, numero_cuenta, tipo_cuenta, saldo_actual, logo)
                VALUES (:nom, :ban, :num, :tipo, :sal, :logo)
                RETURNING id
            ");
            $stmt->execute([':nom'=>$nombre,':ban'=>$banco,':num'=>$numero?:null,
                            ':tipo'=>$tipo,':sal'=>$saldo,':logo'=>$logo_nuevo]);
            jsonResponse(['ok' => true, 'id' => $stmt->fetchColumn()]);
        }
        break;

    // -------------------------------------------------------
    // POST: eliminar banco (soft delete)
    // -------------------------------------------------------
    case 'eliminar_banco':
        $id = intval($input['id'] ?? 0);
        $db->prepare("UPDATE bancos SET activo=false WHERE id=:id")->execute([':id'=>$id]);
        jsonResponse(['ok' => true]);
        break;

    // -------------------------------------------------------
    // POST: transferir entre bancos
    // -------------------------------------------------------
    case 'transferir_banco':
        $origen_id  = intval($input['banco_origen_id']  ?? 0);
        $destino_id = intval($input['banco_destino_id'] ?? 0);
        $monto      = floatval($input['monto'] ?? 0);
        $concepto   = trim($input['concepto'] ?? '');

        if (!$origen_id || !$destino_id)  jsonResponse(['error' => 'Seleccione ambos bancos']);
        if ($origen_id === $destino_id)   jsonResponse(['error' => 'El banco origen y destino deben ser diferentes']);
        if ($monto <= 0)                  jsonResponse(['error' => 'El monto debe ser mayor a 0']);

        $chk = $db->prepare("SELECT id, nombre, saldo_actual FROM bancos WHERE id = :id AND activo = true");

        $chk->execute([':id' => $origen_id]);
        $origen = $chk->fetch(PDO::FETCH_ASSOC);
        if (!$origen) jsonResponse(['error' => 'Banco origen no encontrado']);
        if ((float)$origen['saldo_actual'] < $monto) jsonResponse(['error' => 'Saldo insuficiente en banco origen']);

        $chk->execute([':id' => $destino_id]);
        $destino = $chk->fetch(PDO::FETCH_ASSOC);
        if (!$destino) jsonResponse(['error' => 'Banco destino no encontrado']);

        $db->beginTransaction();
        try {
            $db->prepare("UPDATE bancos SET saldo_actual = saldo_actual - :m WHERE id = :id")
               ->execute([':m' => $monto, ':id' => $origen_id]);
            $db->prepare("UPDATE bancos SET saldo_actual = saldo_actual + :m WHERE id = :id")
               ->execute([':m' => $monto, ':id' => $destino_id]);
            $ins = $db->prepare("
                INSERT INTO banco_transferencias (banco_origen_id, banco_destino_id, monto, concepto, usuario_id)
                VALUES (:orig, :dest, :monto, :con, :uid)
                RETURNING id
            ");
            $ins->execute([
                ':orig'  => $origen_id,
                ':dest'  => $destino_id,
                ':monto' => $monto,
                ':con'   => $concepto ?: null,
                ':uid'   => $usuario_id,
            ]);
            $db->commit();
            jsonResponse(['ok' => true, 'id' => $ins->fetchColumn()]);
        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(['error' => 'Error al procesar la transferencia']);
        }
        break;

    // -------------------------------------------------------
    // GET: movimientos de caja asociados a un banco
    // -------------------------------------------------------
    case 'movimientos_banco':
        $banco_id = intval($input['banco_id'] ?? 0);
        $stmt = $db->prepare("
            SELECT m.tipo, m.concepto, m.descripcion, m.monto, m.fecha,
                   u.nombre AS usuario_nombre
            FROM caja_movimientos m
            LEFT JOIN usuarios u ON u.id = m.usuario_id
            WHERE m.banco_id = :id
            ORDER BY m.fecha DESC
            LIMIT 100
        ");
        $stmt->execute([':id' => $banco_id]);
        jsonResponse(['ok' => true, 'movimientos' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    // -------------------------------------------------------
    // GET: historial de transferencias de un banco
    // -------------------------------------------------------
    case 'historial_transferencias':
        $banco_id = intval($input['banco_id'] ?? 0);
        $stmt = $db->prepare("
            SELECT t.*,
                   bo.nombre AS origen_nombre,
                   bd.nombre AS destino_nombre
            FROM banco_transferencias t
            JOIN bancos bo ON bo.id = t.banco_origen_id
            JOIN bancos bd ON bd.id = t.banco_destino_id
            WHERE t.banco_origen_id = :id OR t.banco_destino_id = :id
            ORDER BY t.fecha DESC
            LIMIT 50
        ");
        $stmt->execute([':id' => $banco_id]);
        jsonResponse(['ok' => true, 'transferencias' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    default:
        jsonResponse(['error' => 'Acción no reconocida']);
}

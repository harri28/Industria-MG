<?php
ini_set('display_errors', 0);
error_reporting(0);
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? $action;
} else {
    $input = $_GET;
}

$db = getDB();

try { switch ($action) {

    // ================================================================
    // DASHBOARD
    // ================================================================
    case 'dashboard':
        $sc_pendientes  = $db->query("SELECT COUNT(*) FROM solicitudes_compra WHERE estado='pendiente'")->fetchColumn();
        $oc_abiertas    = $db->query("SELECT COUNT(*) FROM ordenes_compra WHERE estado IN ('emitida','enviada','recibida_parcial')")->fetchColumn();
        $total_oc_mes   = $db->query("SELECT COALESCE(SUM(total),0) FROM ordenes_compra WHERE DATE_TRUNC('month',created_at)=DATE_TRUNC('month',NOW())")->fetchColumn();
        $proveedores_act = $db->query("SELECT COUNT(*) FROM proveedores WHERE activo")->fetchColumn();
        $recepciones_hoy = $db->query("SELECT COUNT(*) FROM recepciones WHERE fecha=CURRENT_DATE")->fetchColumn();

        // OCs por estado
        $stmt = $db->query("SELECT estado, COUNT(*) AS cnt FROM ordenes_compra GROUP BY estado ORDER BY estado");
        $estados_oc = $stmt->fetchAll();

        // Últimas OCs
        $stmt = $db->query("
            SELECT oc.numero, oc.estado, oc.total, oc.fecha_emision,
                   p.razon_social AS proveedor
            FROM ordenes_compra oc
            LEFT JOIN proveedores p ON p.id = oc.proveedor_id
            ORDER BY oc.created_at DESC LIMIT 8");
        $ultimas_oc = $stmt->fetchAll();

        jsonResponse([
            'ok'              => true,
            'sc_pendientes'   => (int)$sc_pendientes,
            'oc_abiertas'     => (int)$oc_abiertas,
            'total_oc_mes'    => (float)$total_oc_mes,
            'proveedores_act' => (int)$proveedores_act,
            'recepciones_hoy' => (int)$recepciones_hoy,
            'estados_oc'      => $estados_oc,
            'ultimas_oc'      => $ultimas_oc,
        ]);

    // ================================================================
    // PROVEEDORES
    // ================================================================
    case 'proveedores_listar':
        $where  = ['1=1'];
        $params = [];
        if (!empty($input['q'])) {
            $where[] = "(p.razon_social ILIKE :q OR p.ruc ILIKE :q OR p.contacto_nombre ILIKE :q)";
            $params[':q'] = '%' . $input['q'] . '%';
        }
        if (isset($input['activo']) && $input['activo'] !== '') {
            $where[] = "p.activo = :activo";
            $params[':activo'] = filter_var($input['activo'], FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
        }
        $stmt = $db->prepare("
            SELECT p.*,
                   COUNT(DISTINCT oc.id) AS total_oc,
                   COALESCE(SUM(oc.total),0) AS monto_total
            FROM proveedores p
            LEFT JOIN ordenes_compra oc ON oc.proveedor_id = p.id
            WHERE " . implode(' AND ', $where) . "
            GROUP BY p.id ORDER BY p.razon_social");
        $stmt->execute($params);
        jsonResponse(['ok' => true, 'proveedores' => $stmt->fetchAll()]);

    case 'proveedor_obtener':
        $stmt = $db->prepare("SELECT * FROM proveedores WHERE id = :id");
        $stmt->execute([':id' => (int)$input['id']]);
        $prov = $stmt->fetch();
        // Productos asociados
        $stmt2 = $db->prepare("
            SELECT pp.*, pr.codigo, pr.nombre AS producto_nombre, pr.unidad
            FROM proveedor_productos pp
            JOIN productos pr ON pr.id = pp.producto_id
            WHERE pp.proveedor_id = :id ORDER BY pr.nombre");
        $stmt2->execute([':id' => (int)$input['id']]);
        $prov['productos'] = $stmt2->fetchAll();
        jsonResponse(['ok' => true, 'proveedor' => $prov]);

    case 'proveedor_guardar':
        if (empty($input['razon_social']))
            jsonResponse(['error' => 'La razón social es obligatoria.'], 400);
        if (!empty($input['id'])) {
            $db->prepare("UPDATE proveedores SET
                razon_social=:rs, ruc=:ruc, contacto_nombre=:cn,
                email=:email, telefono=:tel, direccion=:dir, activo=:activo
                WHERE id=:id")->execute([
                ':id'     => (int)$input['id'],
                ':rs'     => trim($input['razon_social']),
                ':ruc'    => ($input['ruc'] ?? null) ?: null,
                ':cn'     => ($input['contacto_nombre'] ?? null) ?: null,
                ':email'  => ($input['email'] ?? null) ?: null,
                ':tel'    => ($input['telefono'] ?? null) ?: null,
                ':dir'    => ($input['direccion'] ?? null) ?: null,
                ':activo' => filter_var($input['activo'] ?? true, FILTER_VALIDATE_BOOLEAN),
            ]);
            $id = (int)$input['id'];
        } else {
            $stmt = $db->prepare("INSERT INTO proveedores
                (razon_social, ruc, contacto_nombre, email, telefono, direccion, activo)
                VALUES (:rs, :ruc, :cn, :email, :tel, :dir, :activo) RETURNING id");
            $stmt->execute([
                ':rs'     => trim($input['razon_social']),
                ':ruc'    => ($input['ruc'] ?? null) ?: null,
                ':cn'     => ($input['contacto_nombre'] ?? null) ?: null,
                ':email'  => ($input['email'] ?? null) ?: null,
                ':tel'    => ($input['telefono'] ?? null) ?: null,
                ':dir'    => ($input['direccion'] ?? null) ?: null,
                ':activo' => true,
            ]);
            $id = (int)$stmt->fetchColumn();
        }
        jsonResponse(['ok' => true, 'id' => $id]);

    case 'proveedor_toggle':
        $db->prepare("UPDATE proveedores SET activo = NOT activo WHERE id = :id")
           ->execute([':id' => (int)($input['id'] ?? 0)]);
        jsonResponse(['ok' => true]);

    case 'proveedor_eliminar':
        $id = (int)($input['id'] ?? 0);
        $uso = $db->prepare("SELECT COUNT(*) FROM ordenes_compra WHERE proveedor_id=:id");
        $uso->execute([':id' => $id]);
        if ($uso->fetchColumn() > 0)
            jsonResponse(['error' => 'No se puede eliminar: tiene órdenes de compra asociadas.'], 400);
        $db->prepare("DELETE FROM proveedores WHERE id=:id")->execute([':id' => $id]);
        jsonResponse(['ok' => true]);

    // ================================================================
    // SOLICITUDES DE COMPRA
    // ================================================================
    case 'solicitudes_listar':
        $where  = ['1=1'];
        $params = [];
        if (!empty($input['estado'])) { $where[] = "sc.estado=:estado"; $params[':estado'] = $input['estado']; }
        if (!empty($input['q'])) {
            $where[] = "(sc.numero ILIKE :q OR sc.observaciones ILIKE :q)";
            $params[':q'] = '%' . $input['q'] . '%';
        }
        $stmt = $db->prepare("
            SELECT sc.*, u.nombre AS solicitante_nombre, p.nombre AS proyecto_nombre
            FROM solicitudes_compra sc
            LEFT JOIN usuarios u ON u.id = sc.solicitante_id
            LEFT JOIN proyectos p ON p.id = sc.proyecto_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY sc.created_at DESC");
        $stmt->execute($params);
        jsonResponse(['ok' => true, 'solicitudes' => $stmt->fetchAll()]);

    case 'solicitud_obtener':
        $stmt = $db->prepare("
            SELECT sc.*, u.nombre AS solicitante_nombre, p.nombre AS proyecto_nombre
            FROM solicitudes_compra sc
            LEFT JOIN usuarios u ON u.id = sc.solicitante_id
            LEFT JOIN proyectos p ON p.id = sc.proyecto_id
            WHERE sc.id=:id");
        $stmt->execute([':id' => (int)$input['id']]);
        $sc = $stmt->fetch();
        // Detalles
        $stmt2 = $db->prepare("
            SELECT sd.*, pr.codigo, pr.nombre AS producto_nombre, pr.unidad
            FROM solicitud_detalles sd
            JOIN productos pr ON pr.id = sd.producto_id
            WHERE sd.solicitud_id=:id ORDER BY sd.id");
        $stmt2->execute([':id' => (int)$input['id']]);
        $sc['detalles'] = $stmt2->fetchAll();
        jsonResponse(['ok' => true, 'solicitud' => $sc]);

    case 'solicitud_guardar':
        $detalles = $input['detalles'] ?? [];
        if (empty($detalles)) jsonResponse(['error' => 'Debe agregar al menos un ítem.'], 400);

        $db->beginTransaction();
        if (!empty($input['id'])) {
            $id = (int)$input['id'];
            $db->prepare("UPDATE solicitudes_compra SET
                proyecto_id=:pid, observaciones=:obs
                WHERE id=:id")->execute([
                ':id'  => $id,
                ':pid' => ($input['proyecto_id'] ?? null) ?: null,
                ':obs' => ($input['observaciones'] ?? null) ?: null,
            ]);
            $db->prepare("DELETE FROM solicitud_detalles WHERE solicitud_id=:id")->execute([':id' => $id]);
        } else {
            $numero = generarCodigo('SC-', 'solicitudes_compra', 'numero');
            $stmt = $db->prepare("INSERT INTO solicitudes_compra
                (numero, proyecto_id, tipo, solicitante_id, estado, observaciones)
                VALUES (:num, :pid, :tipo, :uid, 'pendiente', :obs) RETURNING id");
            $stmt->execute([
                ':num'  => $numero,
                ':pid'  => ($input['proyecto_id'] ?? null) ?: null,
                ':tipo' => ($input['tipo'] ?? 'manual') ?: 'manual',
                ':uid'  => null,
                ':obs'  => ($input['observaciones'] ?? null) ?: null,
            ]);
            $id = (int)$stmt->fetchColumn();
        }
        foreach ($detalles as $d) {
            $db->prepare("INSERT INTO solicitud_detalles
                (solicitud_id, producto_id, cantidad, precio_referencial, justificacion)
                VALUES (:sid, :pid, :qty, :precio, :just)")->execute([
                ':sid'    => $id,
                ':pid'    => (int)$d['producto_id'],
                ':qty'    => (float)$d['cantidad'],
                ':precio' => ($d['precio_referencial'] ?? null) ?: null,
                ':just'   => ($d['justificacion'] ?? null) ?: null,
            ]);
        }
        $db->commit();
        jsonResponse(['ok' => true, 'id' => $id]);

    case 'solicitud_estado':
        $id     = (int)($input['id'] ?? 0);
        $estado = $input['estado'] ?? '';
        $estados_validos = ['pendiente', 'aprobada', 'rechazada', 'procesada'];
        if (!in_array($estado, $estados_validos))
            jsonResponse(['error' => 'Estado inválido.'], 400);
        $db->prepare("UPDATE solicitudes_compra SET estado=:estado WHERE id=:id")
           ->execute([':estado' => $estado, ':id' => $id]);
        jsonResponse(['ok' => true]);

    // ================================================================
    // ÓRDENES DE COMPRA
    // ================================================================
    case 'oc_listar':
        $where  = ['1=1'];
        $params = [];
        if (!empty($input['estado'])) { $where[] = "oc.estado=:estado"; $params[':estado'] = $input['estado']; }
        if (!empty($input['proveedor_id'])) { $where[] = "oc.proveedor_id=:prov"; $params[':prov'] = (int)$input['proveedor_id']; }
        if (!empty($input['q'])) {
            $where[] = "(oc.numero ILIKE :q OR p.razon_social ILIKE :q)";
            $params[':q'] = '%' . $input['q'] . '%';
        }
        $stmt = $db->prepare("
            SELECT oc.*, p.razon_social AS proveedor_nombre
            FROM ordenes_compra oc
            LEFT JOIN proveedores p ON p.id = oc.proveedor_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY oc.created_at DESC");
        $stmt->execute($params);
        jsonResponse(['ok' => true, 'ordenes' => $stmt->fetchAll()]);

    case 'oc_obtener':
        $stmt = $db->prepare("
            SELECT oc.*, p.razon_social AS proveedor_nombre, p.email AS proveedor_email,
                   sc.numero AS sc_numero
            FROM ordenes_compra oc
            LEFT JOIN proveedores p ON p.id = oc.proveedor_id
            LEFT JOIN solicitudes_compra sc ON sc.id = oc.solicitud_id
            WHERE oc.id=:id");
        $stmt->execute([':id' => (int)$input['id']]);
        $oc = $stmt->fetch();
        $stmt2 = $db->prepare("
            SELECT od.*, pr.codigo, pr.nombre AS producto_nombre, pr.unidad
            FROM oc_detalles od
            JOIN productos pr ON pr.id = od.producto_id
            WHERE od.oc_id=:id ORDER BY od.id");
        $stmt2->execute([':id' => (int)$input['id']]);
        $oc['detalles'] = $stmt2->fetchAll();
        jsonResponse(['ok' => true, 'oc' => $oc]);

    case 'oc_guardar':
        $detalles = $input['detalles'] ?? [];
        if (empty($detalles)) jsonResponse(['error' => 'Debe agregar al menos un ítem.'], 400);
        if (empty($input['proveedor_id'])) jsonResponse(['error' => 'Debe seleccionar un proveedor.'], 400);

        // Calcular totales
        $subtotal = 0;
        foreach ($detalles as $d) {
            $qty = (float)$d['cantidad'];
            $pu  = (float)$d['precio_unitario'];
            $dto = (float)($d['descuento'] ?? 0);
            $subtotal += $qty * $pu * (1 - $dto / 100);
        }
        $igv   = round($subtotal * IGV, 2);
        $total = $subtotal + $igv;

        $db->beginTransaction();
        if (!empty($input['id'])) {
            $id = (int)$input['id'];
            $db->prepare("UPDATE ordenes_compra SET
                proveedor_id=:prov, fecha_entrega_estimada=:fed,
                subtotal=:sub, igv=:igv, total=:total, observaciones=:obs
                WHERE id=:id")->execute([
                ':id'   => $id,
                ':prov' => (int)$input['proveedor_id'],
                ':fed'  => ($input['fecha_entrega_estimada'] ?? null) ?: null,
                ':sub'  => $subtotal,
                ':igv'  => $igv,
                ':total'=> $total,
                ':obs'  => ($input['observaciones'] ?? null) ?: null,
            ]);
            $db->prepare("DELETE FROM oc_detalles WHERE oc_id=:id")->execute([':id' => $id]);
        } else {
            $numero = generarCodigo('OC-', 'ordenes_compra', 'numero');
            $stmt = $db->prepare("INSERT INTO ordenes_compra
                (numero, proveedor_id, solicitud_id, estado, fecha_entrega_estimada,
                 subtotal, igv, total, observaciones, usuario_id)
                VALUES (:num, :prov, :sc, 'emitida', :fed, :sub, :igv, :total, :obs, :uid)
                RETURNING id");
            $stmt->execute([
                ':num'  => $numero,
                ':prov' => (int)$input['proveedor_id'],
                ':sc'   => ($input['solicitud_id'] ?? null) ?: null,
                ':fed'  => ($input['fecha_entrega_estimada'] ?? null) ?: null,
                ':sub'  => $subtotal,
                ':igv'  => $igv,
                ':total'=> $total,
                ':obs'  => ($input['observaciones'] ?? null) ?: null,
                ':uid'  => null,
            ]);
            $id = (int)$stmt->fetchColumn();
        }
        foreach ($detalles as $d) {
            $qty = (float)$d['cantidad'];
            $pu  = (float)$d['precio_unitario'];
            $dto = (float)($d['descuento'] ?? 0);
            $sub = $qty * $pu * (1 - $dto / 100);
            $db->prepare("INSERT INTO oc_detalles
                (oc_id, producto_id, cantidad, precio_unitario, descuento, subtotal)
                VALUES (:oc, :pid, :qty, :pu, :dto, :sub)")->execute([
                ':oc'  => $id,
                ':pid' => (int)$d['producto_id'],
                ':qty' => $qty,
                ':pu'  => $pu,
                ':dto' => $dto,
                ':sub' => $sub,
            ]);
        }
        // Marcar SC como procesada si viene de SC
        if (!empty($input['solicitud_id'])) {
            $db->prepare("UPDATE solicitudes_compra SET estado='procesada' WHERE id=:id")
               ->execute([':id' => (int)$input['solicitud_id']]);
        }
        $db->commit();
        jsonResponse(['ok' => true, 'id' => $id, 'numero' => $numero ?? null]);

    case 'oc_estado':
        $id     = (int)($input['id'] ?? 0);
        $estado = $input['estado'] ?? '';
        $validos = ['emitida', 'enviada', 'recibida_parcial', 'recibida', 'anulada'];
        if (!in_array($estado, $validos)) jsonResponse(['error' => 'Estado inválido.'], 400);
        $db->prepare("UPDATE ordenes_compra SET estado=:estado WHERE id=:id")
           ->execute([':estado' => $estado, ':id' => $id]);
        jsonResponse(['ok' => true]);

    case 'oc_eliminar':
        $id = (int)($input['id'] ?? 0);
        // Solo se puede eliminar si está en borrador/emitida
        $stmt = $db->prepare("SELECT estado FROM ordenes_compra WHERE id=:id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) jsonResponse(['error' => 'OC no encontrada.'], 404);
        if (!in_array($row['estado'], ['emitida'])) jsonResponse(['error' => 'Solo se pueden eliminar OCs emitidas.'], 400);
        $db->prepare("DELETE FROM ordenes_compra WHERE id=:id")->execute([':id' => $id]);
        jsonResponse(['ok' => true]);

    // ================================================================
    // RECEPCIONES
    // ================================================================
    case 'recepciones_listar':
        $where  = ['1=1'];
        $params = [];
        if (!empty($input['q'])) {
            $where[] = "(r.numero ILIKE :q OR p.razon_social ILIKE :q OR oc.numero ILIKE :q)";
            $params[':q'] = '%' . $input['q'] . '%';
        }
        $stmt = $db->prepare("
            SELECT r.*, oc.numero AS oc_numero, p.razon_social AS proveedor_nombre
            FROM recepciones r
            LEFT JOIN ordenes_compra oc ON oc.id = r.oc_id
            LEFT JOIN proveedores p ON p.id = oc.proveedor_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY r.created_at DESC");
        $stmt->execute($params);
        jsonResponse(['ok' => true, 'recepciones' => $stmt->fetchAll()]);

    case 'recepcion_obtener':
        $stmt = $db->prepare("
            SELECT r.*, oc.numero AS oc_numero, p.razon_social AS proveedor_nombre
            FROM recepciones r
            LEFT JOIN ordenes_compra oc ON oc.id = r.oc_id
            LEFT JOIN proveedores p ON p.id = oc.proveedor_id
            WHERE r.id=:id");
        $stmt->execute([':id' => (int)$input['id']]);
        $rec = $stmt->fetch();
        $stmt2 = $db->prepare("
            SELECT rd.*, od.producto_id, od.cantidad AS cantidad_pedida, od.precio_unitario,
                   pr.codigo, pr.nombre AS producto_nombre, pr.unidad
            FROM recepcion_detalles rd
            JOIN oc_detalles od ON od.id = rd.oc_detalle_id
            JOIN productos pr ON pr.id = od.producto_id
            WHERE rd.recepcion_id=:id ORDER BY rd.id");
        $stmt2->execute([':id' => (int)$input['id']]);
        $rec['detalles'] = $stmt2->fetchAll();
        jsonResponse(['ok' => true, 'recepcion' => $rec]);

    case 'recepcion_crear':
        $oc_id    = (int)($input['oc_id'] ?? 0);
        $detalles = $input['detalles'] ?? [];
        if (!$oc_id) jsonResponse(['error' => 'OC requerida.'], 400);
        if (empty($detalles)) jsonResponse(['error' => 'Debe incluir al menos un ítem.'], 400);

        $db->beginTransaction();
        $numero = generarCodigo('REC-', 'recepciones', 'numero');
        $stmt = $db->prepare("INSERT INTO recepciones (numero, oc_id, fecha, observaciones, usuario_id)
            VALUES (:num, :oc, :fecha, :obs, :uid) RETURNING id");
        $stmt->execute([
            ':num'   => $numero,
            ':oc'    => $oc_id,
            ':fecha' => ($input['fecha'] ?? null) ?: date('Y-m-d'),
            ':obs'   => ($input['observaciones'] ?? null) ?: null,
            ':uid'   => null,
        ]);
        $rec_id = (int)$stmt->fetchColumn();

        foreach ($detalles as $d) {
            $db->prepare("INSERT INTO recepcion_detalles
                (recepcion_id, oc_detalle_id, cantidad_recibida, estado)
                VALUES (:rid, :odid, :qty, :est)")->execute([
                ':rid'  => $rec_id,
                ':odid' => (int)$d['oc_detalle_id'],
                ':qty'  => (float)$d['cantidad_recibida'],
                ':est'  => ($d['estado'] ?? 'conforme') ?: 'conforme',
            ]);
            // Actualizar stock del producto (entrada Kardex)
            $od = $db->prepare("SELECT od.producto_id, od.precio_unitario FROM oc_detalles od WHERE od.id=:id");
            $od->execute([':id' => (int)$d['oc_detalle_id']]);
            $odRow = $od->fetch();
            if ($odRow) {
                $prod_id = (int)$odRow['producto_id'];
                $pu      = (float)$odRow['precio_unitario'];
                $qty_rec = (float)$d['cantidad_recibida'];

                // Obtener stock actual
                $saldo = $db->prepare("SELECT stock_actual, precio_promedio FROM productos WHERE id=:id");
                $saldo->execute([':id' => $prod_id]);
                $pr = $saldo->fetch();
                $stock_ant = (float)$pr['stock_actual'];
                $pp_ant    = (float)$pr['precio_promedio'];

                // Precio promedio ponderado
                $nuevo_stock = $stock_ant + $qty_rec;
                $nuevo_pp    = $nuevo_stock > 0
                    ? (($stock_ant * $pp_ant) + ($qty_rec * $pu)) / $nuevo_stock
                    : $pu;

                $db->prepare("UPDATE productos SET
                    stock_actual=:sa, precio_promedio=:pp, updated_at=NOW()
                    WHERE id=:id")->execute([
                    ':sa' => $nuevo_stock,
                    ':pp' => $nuevo_pp,
                    ':id' => $prod_id,
                ]);

                // Kardex
                $db->prepare("INSERT INTO kardex
                    (producto_id, tipo, referencia_tipo, referencia_id,
                     cantidad, precio_unitario, saldo_cantidad, saldo_valor, usuario_id, observaciones)
                    VALUES (:pid,'entrada','recepcion',:ref,:qty,:pu,:sq,:sv,:uid,:obs)")->execute([
                    ':pid' => $prod_id,
                    ':ref' => $rec_id,
                    ':qty' => $qty_rec,
                    ':pu'  => $pu,
                    ':sq'  => $nuevo_stock,
                    ':sv'  => $nuevo_stock * $nuevo_pp,
                    ':uid' => null,
                    ':obs' => "Recepción $numero",
                ]);
            }
        }

        // Actualizar estado OC
        $db->prepare("UPDATE ordenes_compra SET estado='recibida_parcial' WHERE id=:id AND estado='enviada'")
           ->execute([':id' => $oc_id]);

        $db->commit();
        jsonResponse(['ok' => true, 'id' => $rec_id, 'numero' => $numero]);

    // ================================================================
    // EVALUACIONES PROVEEDOR
    // ================================================================
    case 'evaluaciones_listar':
        $where  = ['1=1'];
        $params = [];
        if (!empty($input['proveedor_id'])) {
            $where[] = "ev.proveedor_id=:pid";
            $params[':pid'] = (int)$input['proveedor_id'];
        }
        $stmt = $db->prepare("
            SELECT ev.*, p.razon_social AS proveedor_nombre,
                   oc.numero AS oc_numero, u.nombre AS evaluador_nombre,
                   ROUND((ev.calidad+ev.tiempo_entrega+ev.precio+ev.servicio)::numeric/4, 1) AS promedio
            FROM evaluaciones_proveedor ev
            LEFT JOIN proveedores p ON p.id = ev.proveedor_id
            LEFT JOIN ordenes_compra oc ON oc.id = ev.oc_id
            LEFT JOIN usuarios u ON u.id = ev.evaluador_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY ev.created_at DESC LIMIT 100");
        $stmt->execute($params);
        jsonResponse(['ok' => true, 'evaluaciones' => $stmt->fetchAll()]);

    case 'evaluacion_guardar':
        $prov_id = (int)($input['proveedor_id'] ?? 0);
        if (!$prov_id) jsonResponse(['error' => 'Proveedor requerido.'], 400);

        $stmt = $db->prepare("INSERT INTO evaluaciones_proveedor
            (proveedor_id, oc_id, calidad, tiempo_entrega, precio, servicio, comentario, evaluador_id)
            VALUES (:pid, :oc, :cal, :te, :pre, :ser, :com, :uid) RETURNING id");
        $stmt->execute([
            ':pid' => $prov_id,
            ':oc'  => ($input['oc_id'] ?? null) ?: null,
            ':cal' => min(5, max(1, (int)($input['calidad'] ?? 3))),
            ':te'  => min(5, max(1, (int)($input['tiempo_entrega'] ?? 3))),
            ':pre' => min(5, max(1, (int)($input['precio'] ?? 3))),
            ':ser' => min(5, max(1, (int)($input['servicio'] ?? 3))),
            ':com' => ($input['comentario'] ?? null) ?: null,
            ':uid' => null,
        ]);
        $id = (int)$stmt->fetchColumn();

        // Recalcular calificación promedio del proveedor
        $db->prepare("UPDATE proveedores SET calificacion=(
            SELECT ROUND(AVG((calidad+tiempo_entrega+precio+servicio)::numeric/4),1)
            FROM evaluaciones_proveedor WHERE proveedor_id=:pid
        ) WHERE id=:pid")->execute([':pid' => $prov_id]);

        jsonResponse(['ok' => true, 'id' => $id]);

    // ================================================================
    // COMBOS / AUXILIARES
    // ================================================================
    case 'combos':
        $proveedores = $db->query("SELECT id, razon_social FROM proveedores WHERE activo ORDER BY razon_social")->fetchAll();
        $productos   = $db->query("SELECT id, codigo, nombre, unidad, precio_promedio FROM productos WHERE activo ORDER BY nombre")->fetchAll();
        $proyectos   = $db->query("SELECT id, codigo, nombre FROM proyectos WHERE activo ORDER BY nombre")->fetchAll();
        $ocs_abiertas = $db->query("
            SELECT oc.id, oc.numero, p.razon_social AS proveedor_nombre
            FROM ordenes_compra oc
            LEFT JOIN proveedores p ON p.id = oc.proveedor_id
            WHERE oc.estado IN ('emitida','enviada','recibida_parcial')
            ORDER BY oc.numero DESC")->fetchAll();
        jsonResponse(['ok' => true,
            'proveedores'  => $proveedores,
            'productos'    => $productos,
            'proyectos'    => $proyectos,
            'ocs_abiertas' => $ocs_abiertas,
        ]);

    case 'oc_detalles_para_recepcion':
        $oc_id = (int)($input['oc_id'] ?? 0);
        $stmt = $db->prepare("
            SELECT od.id AS oc_detalle_id, od.cantidad, od.precio_unitario,
                   pr.codigo, pr.nombre AS producto_nombre, pr.unidad,
                   COALESCE(SUM(rd.cantidad_recibida), 0) AS recibido
            FROM oc_detalles od
            JOIN productos pr ON pr.id = od.producto_id
            LEFT JOIN recepcion_detalles rd ON rd.oc_detalle_id = od.id
            WHERE od.oc_id=:oc
            GROUP BY od.id, od.cantidad, od.precio_unitario, pr.codigo, pr.nombre, pr.unidad
            ORDER BY pr.nombre");
        $stmt->execute([':oc' => $oc_id]);
        jsonResponse(['ok' => true, 'detalles' => $stmt->fetchAll()]);

    default:
        jsonResponse(['error' => 'Acción no reconocida.'], 400);

}} catch (PDOException $e) {
    jsonResponse(['error' => 'Error BD: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}

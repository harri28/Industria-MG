<?php
ini_set('display_errors', 0);
error_reporting(0);
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
startAppSession();
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
    // CATÁLOGO
    // ================================================================
    case 'catalogo_listar':
        $where  = ['1=1'];
        $params = [];
        if (!empty($input['q'])) {
            $where[] = "(nombre ILIKE :q OR descripcion_corta ILIKE :q OR categoria ILIKE :q)";
            $params[':q'] = '%' . $input['q'] . '%';
        }
        if (!empty($input['categoria'])) {
            $where[] = "categoria = :cat";
            $params[':cat'] = $input['categoria'];
        }
        if (isset($input['publicado']) && $input['publicado'] !== '') {
            $where[] = "publicado = :pub";
            $params[':pub'] = filter_var($input['publicado'], FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
        }
        $stmt = $db->prepare("SELECT * FROM catalogo_productos WHERE " . implode(' AND ', $where) . " ORDER BY orden, nombre");
        $stmt->execute($params);
        jsonResponse(['ok' => true, 'productos' => $stmt->fetchAll()]);

    case 'catalogo_obtener':
        $stmt = $db->prepare("SELECT * FROM catalogo_productos WHERE id=:id");
        $stmt->execute([':id' => (int)$input['id']]);
        jsonResponse(['ok' => true, 'producto' => $stmt->fetch()]);

    case 'catalogo_guardar':
        if (empty($input['nombre'])) jsonResponse(['error' => 'El nombre es obligatorio.'], 400);
        // Normaliza la lista de colores: [{nombre, imagen}] (solo entradas con nombre).
        $coloresRaw = $input['colores'] ?? [];
        if (is_string($coloresRaw)) $coloresRaw = json_decode($coloresRaw, true) ?: [];
        $colores = [];
        foreach ((is_array($coloresRaw) ? $coloresRaw : []) as $c) {
            $cn = trim((string)($c['nombre'] ?? ''));
            if ($cn === '') continue;
            $colores[] = ['nombre' => $cn, 'imagen' => ($c['imagen'] ?? null) ?: null];
        }
        $coloresJson = json_encode($colores, JSON_UNESCAPED_UNICODE);
        $catDir = __DIR__ . '/../../assets/uploads/catalogo/';
        $id = 0;
        if (!empty($input['id'])) {
            $id = (int)$input['id'];
            // Borra del disco las imagenes de colores que se quitaron.
            $prev = $db->prepare("SELECT colores FROM catalogo_productos WHERE id=:id");
            $prev->execute([':id' => $id]);
            $prevColores = json_decode((string)$prev->fetchColumn(), true) ?: [];
            $vigentes = array_filter(array_column($colores, 'imagen'));
            foreach ($prevColores as $pc) {
                $img = $pc['imagen'] ?? null;
                if ($img && !in_array($img, $vigentes, true) && file_exists($catDir . $img)) @unlink($catDir . $img);
            }
            $db->prepare("UPDATE catalogo_productos SET
                nombre=:nom, descripcion=:desc, descripcion_corta=:dcorta,
                categoria=:cat, precio_referencial=:precio, colores=:colores,
                publicado=:pub, destacado=:dest, orden=:orden
                WHERE id=:id")->execute([
                ':id'      => $id,
                ':nom'     => trim($input['nombre']),
                ':desc'    => ($input['descripcion'] ?? null) ?: null,
                ':dcorta'  => ($input['descripcion_corta'] ?? null) ?: null,
                ':cat'     => ($input['categoria'] ?? null) ?: null,
                ':precio'  => ($input['precio_referencial'] ?? null) ?: null,
                ':colores' => $coloresJson,
                ':pub'     => filter_var($input['publicado'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false',
                ':dest'    => filter_var($input['destacado'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false',
                ':orden'   => (int)($input['orden'] ?? 0),
            ]);
        } else {
            $stmt = $db->prepare("INSERT INTO catalogo_productos
                (nombre, descripcion, descripcion_corta, categoria, precio_referencial, colores, publicado, destacado, orden)
                VALUES (:nom, :desc, :dcorta, :cat, :precio, :colores, :pub, :dest, :orden) RETURNING id");
            $stmt->execute([
                ':nom'     => trim($input['nombre']),
                ':desc'    => ($input['descripcion'] ?? null) ?: null,
                ':dcorta'  => ($input['descripcion_corta'] ?? null) ?: null,
                ':cat'     => ($input['categoria'] ?? null) ?: null,
                ':precio'  => ($input['precio_referencial'] ?? null) ?: null,
                ':colores' => $coloresJson,
                ':pub'     => filter_var($input['publicado'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false',
                ':dest'    => filter_var($input['destacado'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false',
                ':orden'   => (int)($input['orden'] ?? 0),
            ]);
            $id = (int)$stmt->fetchColumn();
        }
        jsonResponse(['ok' => true, 'id' => $id]);

    case 'catalogo_color_img':
        // Sube una imagen de color y devuelve su nombre de archivo (sin tocar la BD).
        if (empty($_FILES['imagen']['tmp_name'])) jsonResponse(['error' => 'Imagen requerida.'], 400);
        $file = $_FILES['imagen'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp'])) jsonResponse(['error' => 'Formato no válido (jpg, png, webp).'], 400);
        if ($file['size'] > 5 * 1024 * 1024) jsonResponse(['error' => 'Máximo 5MB.'], 400);
        $catDir = __DIR__ . '/../../assets/uploads/catalogo/';
        if (!is_dir($catDir)) mkdir($catDir, 0755, true);
        $colorFilename = 'catcolor_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $catDir . $colorFilename)) jsonResponse(['error' => 'Error al guardar imagen.'], 500);
        jsonResponse(['ok' => true, 'filename' => $colorFilename]);

    case 'catalogo_toggle_publicado':
        $db->prepare("UPDATE catalogo_productos SET publicado=NOT publicado WHERE id=:id")
           ->execute([':id' => (int)($input['id'] ?? 0)]);
        jsonResponse(['ok' => true]);

    case 'catalogo_toggle_destacado':
        $db->prepare("UPDATE catalogo_productos SET destacado=NOT destacado WHERE id=:id")
           ->execute([':id' => (int)($input['id'] ?? 0)]);
        jsonResponse(['ok' => true]);

    case 'catalogo_eliminar':
        $db->prepare("DELETE FROM catalogo_productos WHERE id=:id")->execute([':id' => (int)($input['id'] ?? 0)]);
        jsonResponse(['ok' => true]);

    case 'categorias_catalogo':
        $stmt = $db->query("SELECT DISTINCT categoria FROM catalogo_productos WHERE categoria IS NOT NULL ORDER BY categoria");
        jsonResponse(['ok' => true, 'categorias' => array_column($stmt->fetchAll(), 'categoria')]);

    case 'catalogo_img_guardar':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) jsonResponse(['error' => 'ID requerido.'], 400);
        if (empty($_FILES['imagen']['tmp_name'])) jsonResponse(['error' => 'Imagen requerida.'], 400);
        $file = $_FILES['imagen'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp'])) jsonResponse(['error' => 'Formato no válido (jpg, png, webp).'], 400);
        if ($file['size'] > 5 * 1024 * 1024) jsonResponse(['error' => 'Máximo 5MB.'], 400);
        $catDir = __DIR__ . '/../../assets/uploads/catalogo/';
        if (!is_dir($catDir)) mkdir($catDir, 0755, true);
        $oldRow = $db->prepare("SELECT imagen_principal FROM catalogo_productos WHERE id=:id");
        $oldRow->execute([':id' => $id]);
        $oldImg = $oldRow->fetchColumn();
        if ($oldImg && file_exists($catDir . $oldImg)) unlink($catDir . $oldImg);
        $catFilename = 'cat_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $catDir . $catFilename)) jsonResponse(['error' => 'Error al guardar imagen.'], 500);
        $db->prepare("UPDATE catalogo_productos SET imagen_principal=:img WHERE id=:id")
           ->execute([':img' => $catFilename, ':id' => $id]);
        jsonResponse(['ok' => true, 'filename' => $catFilename]);

    case 'catalogo_img_eliminar':
        $id = (int)($input['id'] ?? 0);
        $oldRow = $db->prepare("SELECT imagen_principal FROM catalogo_productos WHERE id=:id");
        $oldRow->execute([':id' => $id]);
        $oldImg = $oldRow->fetchColumn();
        $catDir = __DIR__ . '/../../assets/uploads/catalogo/';
        if ($oldImg && file_exists($catDir . $oldImg)) unlink($catDir . $oldImg);
        $db->prepare("UPDATE catalogo_productos SET imagen_principal=NULL WHERE id=:id")
           ->execute([':id' => $id]);
        jsonResponse(['ok' => true]);

    // ================================================================
    // LEADS
    // ================================================================
    case 'leads_listar':
        $where  = ['1=1'];
        $params = [];
        if (!empty($input['estado'])) { $where[] = "estado=:est"; $params[':est'] = $input['estado']; }
        if (!empty($input['origen'])) { $where[] = "origen=:ori"; $params[':ori'] = $input['origen']; }
        if (!empty($input['q'])) {
            $where[] = "(nombre ILIKE :q OR empresa ILIKE :q OR email ILIKE :q)";
            $params[':q'] = '%' . $input['q'] . '%';
        }
        $stmt = $db->prepare("SELECT l.*, c.razon_social AS cliente_nombre,
                u.nombre AS responsable_nombre,
                (SELECT COUNT(*) FROM lead_interacciones li WHERE li.lead_id = l.id) AS num_interacciones
            FROM leads l
            LEFT JOIN clientes c ON c.id=l.cliente_id
            LEFT JOIN usuarios u ON u.id=l.responsable_id
            WHERE " . implode(' AND ', $where) . " ORDER BY l.created_at DESC");
        $stmt->execute($params);
        jsonResponse(['ok' => true, 'leads' => $stmt->fetchAll()]);

    case 'lead_guardar':
        if (!empty($input['id'])) {
            $db->prepare("UPDATE leads SET
                nombre=:nom, email=:email, telefono=:tel, empresa=:emp,
                origen=:ori, mensaje=:msg, estado=:est, cliente_id=:cid,
                responsable_id=:resp, proxima_accion=:paccion, proxima_fecha=:pfecha
                WHERE id=:id")->execute([
                ':id'      => (int)$input['id'],
                ':nom'     => ($input['nombre'] ?? null) ?: null,
                ':email'   => ($input['email'] ?? null) ?: null,
                ':tel'     => ($input['telefono'] ?? null) ?: null,
                ':emp'     => ($input['empresa'] ?? null) ?: null,
                ':ori'     => ($input['origen'] ?? null) ?: null,
                ':msg'     => ($input['mensaje'] ?? null) ?: null,
                ':est'     => $input['estado'] ?? 'nuevo',
                ':cid'     => ($input['cliente_id'] ?? null) ?: null,
                ':resp'    => ($input['responsable_id'] ?? null) ?: null,
                ':paccion' => ($input['proxima_accion'] ?? null) ?: null,
                ':pfecha'  => ($input['proxima_fecha'] ?? null) ?: null,
            ]);
        } else {
            $db->prepare("INSERT INTO leads (nombre, email, telefono, empresa, origen, mensaje, estado, responsable_id, proxima_accion, proxima_fecha)
                VALUES (:nom, :email, :tel, :emp, :ori, :msg, :est, :resp, :paccion, :pfecha)")->execute([
                ':nom'     => ($input['nombre'] ?? null) ?: null,
                ':email'   => ($input['email'] ?? null) ?: null,
                ':tel'     => ($input['telefono'] ?? null) ?: null,
                ':emp'     => ($input['empresa'] ?? null) ?: null,
                ':ori'     => $input['origen'] ?? 'directo',
                ':msg'     => ($input['mensaje'] ?? null) ?: null,
                ':est'     => 'nuevo',
                ':resp'    => ($input['responsable_id'] ?? null) ?: null,
                ':paccion' => ($input['proxima_accion'] ?? null) ?: null,
                ':pfecha'  => ($input['proxima_fecha'] ?? null) ?: null,
            ]);
        }
        jsonResponse(['ok' => true]);

    case 'lead_estado':
        $db->prepare("UPDATE leads SET estado=:est WHERE id=:id")
           ->execute([':est' => $input['estado'], ':id' => (int)($input['id'] ?? 0)]);
        jsonResponse(['ok' => true]);

    case 'lead_convertir':
        // Convierte lead en cliente
        $id = (int)($input['id'] ?? 0);
        $stmt = $db->prepare("SELECT * FROM leads WHERE id=:id");
        $stmt->execute([':id' => $id]);
        $lead = $stmt->fetch();
        if (!$lead) jsonResponse(['error' => 'Lead no encontrado.'], 404);

        $stmt = $db->prepare("INSERT INTO clientes
            (razon_social, contacto_nombre, email, telefono, notas, estado_crm)
            VALUES (:rs, :cn, :email, :tel, :notas, 'prospecto') RETURNING id");
        $stmt->execute([
            ':rs'    => $lead['empresa'] ?: $lead['nombre'],
            ':cn'    => $lead['nombre'],
            ':email' => $lead['email'],
            ':tel'   => $lead['telefono'],
            ':notas' => "Convertido desde lead (origen: {$lead['origen']})\n{$lead['mensaje']}",
        ]);
        $cliente_id = (int)$stmt->fetchColumn();
        $db->prepare("UPDATE leads SET estado='convertido', cliente_id=:cid WHERE id=:id")
           ->execute([':cid' => $cliente_id, ':id' => $id]);
        jsonResponse(['ok' => true, 'cliente_id' => $cliente_id]);

    case 'lead_eliminar':
        $db->prepare("DELETE FROM leads WHERE id=:id")->execute([':id' => (int)($input['id'] ?? 0)]);
        jsonResponse(['ok' => true]);

    case 'lead_obtener':
        $lid = (int)($input['id'] ?? 0);
        $stmt = $db->prepare("SELECT l.*, u.nombre AS responsable_nombre
            FROM leads l LEFT JOIN usuarios u ON u.id=l.responsable_id WHERE l.id=:id");
        $stmt->execute([':id' => $lid]);
        $lead = $stmt->fetch();
        $stmtI = $db->prepare("SELECT li.*, u.nombre AS usuario_nombre
            FROM lead_interacciones li LEFT JOIN usuarios u ON u.id=li.usuario_id
            WHERE li.lead_id=:id ORDER BY li.created_at DESC, li.id DESC");
        $stmtI->execute([':id' => $lid]);
        jsonResponse(['ok' => true, 'lead' => $lead, 'interacciones' => $stmtI->fetchAll()]);

    case 'lead_interaccion_guardar':
        $leadId = (int)($input['lead_id'] ?? 0);
        $tipo   = trim((string)($input['tipo'] ?? 'nota')) ?: 'nota';
        $nota   = trim((string)($input['nota'] ?? ''));
        if (!$leadId) jsonResponse(['error' => 'Lead requerido.'], 400);
        if ($nota === '') jsonResponse(['error' => 'La nota es obligatoria.'], 400);
        $uid = $_SESSION['usuario']['id'] ?? null;
        $db->prepare("INSERT INTO lead_interacciones (lead_id, tipo, nota, usuario_id)
            VALUES (:lid, :tipo, :nota, :uid)")
           ->execute([':lid' => $leadId, ':tipo' => $tipo, ':nota' => $nota, ':uid' => $uid]);
        jsonResponse(['ok' => true]);

    case 'lead_interaccion_eliminar':
        $db->prepare("DELETE FROM lead_interacciones WHERE id=:id")->execute([':id' => (int)($input['id'] ?? 0)]);
        jsonResponse(['ok' => true]);

    case 'usuarios_combo':
        $stmt = $db->query("SELECT id, nombre FROM usuarios WHERE activo ORDER BY nombre");
        jsonResponse(['ok' => true, 'usuarios' => $stmt->fetchAll()]);

    case 'dashboard':
        $total_catalogo    = $db->query("SELECT COUNT(*) FROM catalogo_productos")->fetchColumn();
        $publicados        = $db->query("SELECT COUNT(*) FROM catalogo_productos WHERE publicado")->fetchColumn();
        $leads_nuevos      = $db->query("SELECT COUNT(*) FROM leads WHERE estado='nuevo'")->fetchColumn();
        $leads_mes         = $db->query("SELECT COUNT(*) FROM leads WHERE DATE_TRUNC('month',created_at)=DATE_TRUNC('month',NOW())")->fetchColumn();
        $leads_convertidos = $db->query("SELECT COUNT(*) FROM leads WHERE estado='convertido'")->fetchColumn();

        $stmt = $db->query("SELECT origen, COUNT(*) AS cnt FROM leads GROUP BY origen ORDER BY cnt DESC");
        $por_origen = $stmt->fetchAll();

        $stmt2 = $db->query("SELECT estado, COUNT(*) AS cnt FROM leads GROUP BY estado ORDER BY estado");
        $por_estado = $stmt2->fetchAll();

        jsonResponse(['ok' => true,
            'total_catalogo'    => (int)$total_catalogo,
            'publicados'        => (int)$publicados,
            'leads_nuevos'      => (int)$leads_nuevos,
            'leads_mes'         => (int)$leads_mes,
            'leads_convertidos' => (int)$leads_convertidos,
            'por_origen'        => $por_origen,
            'por_estado'        => $por_estado,
        ]);

    default:
        jsonResponse(['error' => 'Acción no reconocida.'], 400);

}} catch (PDOException $e) {
    jsonResponse(['error' => 'Error BD: ' . $e->getMessage()], 500);
}

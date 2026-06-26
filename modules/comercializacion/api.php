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
        $id = 0;
        if (!empty($input['id'])) {
            $id = (int)$input['id'];
            $db->prepare("UPDATE catalogo_productos SET
                nombre=:nom, descripcion=:desc, descripcion_corta=:dcorta,
                categoria=:cat, precio_referencial=:precio,
                publicado=:pub, destacado=:dest, orden=:orden
                WHERE id=:id")->execute([
                ':id'     => $id,
                ':nom'    => trim($input['nombre']),
                ':desc'   => ($input['descripcion'] ?? null) ?: null,
                ':dcorta' => ($input['descripcion_corta'] ?? null) ?: null,
                ':cat'    => ($input['categoria'] ?? null) ?: null,
                ':precio' => ($input['precio_referencial'] ?? null) ?: null,
                ':pub'    => filter_var($input['publicado'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ':dest'   => filter_var($input['destacado'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ':orden'  => (int)($input['orden'] ?? 0),
            ]);
        } else {
            $stmt = $db->prepare("INSERT INTO catalogo_productos
                (nombre, descripcion, descripcion_corta, categoria, precio_referencial, publicado, destacado, orden)
                VALUES (:nom, :desc, :dcorta, :cat, :precio, :pub, :dest, :orden) RETURNING id");
            $stmt->execute([
                ':nom'    => trim($input['nombre']),
                ':desc'   => ($input['descripcion'] ?? null) ?: null,
                ':dcorta' => ($input['descripcion_corta'] ?? null) ?: null,
                ':cat'    => ($input['categoria'] ?? null) ?: null,
                ':precio' => ($input['precio_referencial'] ?? null) ?: null,
                ':pub'    => filter_var($input['publicado'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ':dest'   => filter_var($input['destacado'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ':orden'  => (int)($input['orden'] ?? 0),
            ]);
            $id = (int)$stmt->fetchColumn();
        }
        jsonResponse(['ok' => true, 'id' => $id]);

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
        $stmt = $db->prepare("SELECT l.*, c.razon_social AS cliente_nombre
            FROM leads l LEFT JOIN clientes c ON c.id=l.cliente_id
            WHERE " . implode(' AND ', $where) . " ORDER BY l.created_at DESC");
        $stmt->execute($params);
        jsonResponse(['ok' => true, 'leads' => $stmt->fetchAll()]);

    case 'lead_guardar':
        if (!empty($input['id'])) {
            $db->prepare("UPDATE leads SET
                nombre=:nom, email=:email, telefono=:tel, empresa=:emp,
                origen=:ori, mensaje=:msg, estado=:est, cliente_id=:cid
                WHERE id=:id")->execute([
                ':id'    => (int)$input['id'],
                ':nom'   => ($input['nombre'] ?? null) ?: null,
                ':email' => ($input['email'] ?? null) ?: null,
                ':tel'   => ($input['telefono'] ?? null) ?: null,
                ':emp'   => ($input['empresa'] ?? null) ?: null,
                ':ori'   => ($input['origen'] ?? null) ?: null,
                ':msg'   => ($input['mensaje'] ?? null) ?: null,
                ':est'   => $input['estado'] ?? 'nuevo',
                ':cid'   => ($input['cliente_id'] ?? null) ?: null,
            ]);
        } else {
            $db->prepare("INSERT INTO leads (nombre, email, telefono, empresa, origen, mensaje, estado)
                VALUES (:nom, :email, :tel, :emp, :ori, :msg, :est)")->execute([
                ':nom'   => ($input['nombre'] ?? null) ?: null,
                ':email' => ($input['email'] ?? null) ?: null,
                ':tel'   => ($input['telefono'] ?? null) ?: null,
                ':emp'   => ($input['empresa'] ?? null) ?: null,
                ':ori'   => $input['origen'] ?? 'directo',
                ':msg'   => ($input['mensaje'] ?? null) ?: null,
                ':est'   => 'nuevo',
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
        $stmt = $db->prepare("SELECT * FROM leads WHERE id=:id");
        $stmt->execute([':id' => (int)($input['id'] ?? 0)]);
        jsonResponse(['ok' => true, 'lead' => $stmt->fetch()]);

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

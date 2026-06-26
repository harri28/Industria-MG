<?php
// modules/activos/api.php
ini_set('display_errors', 0);
error_reporting(0);
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // FormData (multipart) populates $_POST; JSON body leaves it empty
    $input  = !empty($_POST) ? $_POST : (json_decode(file_get_contents('php://input'), true) ?? []);
    $action = $input['action'] ?? $action;
} else {
    $input = $_GET;
}

$db = getDB();

// ── Image helpers ──────────────────────────────────────────
function uploadActivoFile(string $fileKey, string $prefix): ?string {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) return null;
    $file = $_FILES[$fileKey];
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','gif','webp']))
        jsonResponse(['error' => 'Formato no válido (jpg, png, gif, webp).'], 400);
    if ($file['size'] > 2 * 1024 * 1024)
        jsonResponse(['error' => 'La imagen no puede superar 2 MB.'], 400);
    $dir = dirname(__DIR__, 2) . '/assets/uploads/activos/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . $name))
        jsonResponse(['error' => 'Error al guardar la imagen.'], 500);
    return $name;
}

function uploadActivo(string $prefix): ?string {
    return uploadActivoFile('imagen', $prefix);
}

function deleteActivo(?string $filename): void {
    if ($filename) @unlink(dirname(__DIR__, 2) . '/assets/uploads/activos/' . $filename);
}

try {
    switch ($action) {

        // ── DASHBOARD ─────────────────────────────────────────
        case 'dashboard':
            $kpis = $db->query("
                SELECT
                    (SELECT COUNT(*) FROM activos WHERE activo) AS total,
                    (SELECT COUNT(*) FROM activos a WHERE activo
                        AND EXISTS (
                            SELECT 1 FROM activo_asignaciones
                            WHERE activo_id = a.id AND devuelto = FALSE AND fecha_fin >= CURRENT_DATE
                        )
                    ) AS asignados,
                    (SELECT COUNT(*) FROM activos a WHERE activo AND estado = 'dañado') AS dañados,
                    (SELECT COUNT(*) FROM activo_asignaciones
                        WHERE devuelto = FALSE AND fecha_fin < CURRENT_DATE
                    ) AS vencidos
            ")->fetch();
            $kpis['disponibles'] = $kpis['total'] - $kpis['asignados'];

            $asignadas = $db->query("
                SELECT a.codigo, a.nombre AS activo_nombre, a.estado,
                    u.nombre AS usuario_nombre,
                    aa.periodo_tipo, aa.fecha_inicio, aa.fecha_fin,
                    CASE WHEN aa.fecha_fin < CURRENT_DATE THEN TRUE ELSE FALSE END AS vencida
                FROM activo_asignaciones aa
                JOIN activos  a ON a.id = aa.activo_id
                JOIN usuarios u ON u.id = aa.usuario_id
                WHERE aa.devuelto = FALSE
                ORDER BY aa.fecha_fin ASC
                LIMIT 20
            ")->fetchAll();

            jsonResponse(['ok' => true, 'kpis' => $kpis, 'asignadas' => $asignadas]);

        // ── LISTAR ACTIVOS (herramientas) ─────────────────────
        case 'listar':
            $where  = ['a.activo = TRUE'];
            $params = [];
            if (!empty($input['buscar'])) {
                $where[]           = "(a.codigo ILIKE :buscar OR a.nombre ILIKE :buscar)";
                $params[':buscar'] = '%' . $input['buscar'] . '%';
            }
            if (!empty($input['categoria_id'])) {
                $where[]        = "a.categoria_id = :cat";
                $params[':cat'] = (int)$input['categoria_id'];
            }
            if (!empty($input['estado'])) {
                $where[]           = "a.estado = :estado";
                $params[':estado'] = $input['estado'];
            }
            if (isset($input['solo_disponibles']) && $input['solo_disponibles']) {
                $where[] = "NOT EXISTS (
                    SELECT 1 FROM activo_asignaciones
                    WHERE activo_id = a.id AND devuelto = FALSE AND fecha_fin >= CURRENT_DATE
                )";
            }

            $stmt = $db->prepare("
                SELECT a.*,
                    ac.nombre AS categoria_nombre,
                    aa.id     AS asig_id,
                    u.nombre  AS asig_usuario,
                    aa.periodo_tipo, aa.fecha_inicio, aa.fecha_fin,
                    CASE
                        WHEN aa.id IS NULL               THEN 'disponible'
                        WHEN aa.fecha_fin < CURRENT_DATE THEN 'vencida'
                        ELSE 'asignado'
                    END AS estado_asig
                FROM activos a
                LEFT JOIN activo_categorias ac ON ac.id = a.categoria_id
                LEFT JOIN LATERAL (
                    SELECT * FROM activo_asignaciones
                    WHERE activo_id = a.id AND devuelto = FALSE
                    ORDER BY fecha_fin DESC LIMIT 1
                ) aa ON TRUE
                LEFT JOIN usuarios u ON u.id = aa.usuario_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY a.nombre");
            $stmt->execute($params);
            jsonResponse(['ok' => true, 'activos' => $stmt->fetchAll()]);

        // ── OBTENER ACTIVO ────────────────────────────────────
        case 'obtener':
            $stmt = $db->prepare("
                SELECT a.*, ac.nombre AS categoria_nombre
                FROM activos a
                LEFT JOIN activo_categorias ac ON ac.id = a.categoria_id
                WHERE a.id = :id");
            $stmt->execute([':id' => (int)$input['id']]);
            $activo = $stmt->fetch();
            if (!$activo) jsonResponse(['error' => 'No encontrado.'], 404);
            jsonResponse(['ok' => true, 'activo' => $activo]);

        // ── GUARDAR ACTIVO (herramienta) ──────────────────────
        case 'guardar':
            $id          = (int)($input['id'] ?? 0);
            $imagen_new  = uploadActivo('act');
            $remove_img  = !empty($input['remove_imagen']);

            if ($id) {
                $img_sql    = '';
                $img_params = [];
                if ($imagen_new) {
                    $old = $db->prepare("SELECT imagen FROM activos WHERE id=:id");
                    $old->execute([':id' => $id]);
                    deleteActivo($old->fetchColumn());
                    $img_sql           = ', imagen=:img';
                    $img_params[':img'] = $imagen_new;
                } elseif ($remove_img) {
                    $old = $db->prepare("SELECT imagen FROM activos WHERE id=:id");
                    $old->execute([':id' => $id]);
                    deleteActivo($old->fetchColumn());
                    $img_sql = ', imagen=NULL';
                }
                $db->prepare("UPDATE activos SET
                    nombre=:nombre, descripcion=:desc, categoria_id=:cat,
                    numero_serie=:ns, fecha_adquisicion=:fadq, estado=:estado,
                    observaciones=:obs {$img_sql}, updated_at=NOW()
                    WHERE id=:id")->execute(array_merge([
                    ':id'     => $id,
                    ':nombre' => $input['nombre'],
                    ':desc'   => ($input['descripcion']       ?? null) ?: null,
                    ':cat'    => ($input['categoria_id']      ?? null) ?: null,
                    ':ns'     => ($input['numero_serie']      ?? null) ?: null,
                    ':fadq'   => ($input['fecha_adquisicion'] ?? null) ?: null,
                    ':estado' => $input['estado'] ?? 'bueno',
                    ':obs'    => ($input['observaciones']     ?? null) ?: null,
                ], $img_params));
            } else {
                if (empty($input['nombre'])) jsonResponse(['error' => 'El nombre es obligatorio.'], 400);
                $codigo = generarCodigo('ACT-', 'activos', 'codigo');
                $stmt   = $db->prepare("INSERT INTO activos
                    (codigo, nombre, descripcion, categoria_id, numero_serie,
                     fecha_adquisicion, estado, observaciones, imagen)
                    VALUES (:cod,:nombre,:desc,:cat,:ns,:fadq,:estado,:obs,:img) RETURNING id");
                $stmt->execute([
                    ':cod'    => $codigo,
                    ':nombre' => $input['nombre'],
                    ':desc'   => ($input['descripcion']       ?? null) ?: null,
                    ':cat'    => ($input['categoria_id']      ?? null) ?: null,
                    ':ns'     => ($input['numero_serie']      ?? null) ?: null,
                    ':fadq'   => ($input['fecha_adquisicion'] ?? null) ?: null,
                    ':estado' => $input['estado'] ?? 'bueno',
                    ':obs'    => ($input['observaciones']     ?? null) ?: null,
                    ':img'    => $imagen_new,
                ]);
                $id = $stmt->fetchColumn();
            }
            jsonResponse(['ok' => true, 'id' => $id]);

        // ── TOGGLE ACTIVO ─────────────────────────────────────
        case 'toggle':
            $db->prepare("UPDATE activos SET activo = NOT activo WHERE id = :id")
               ->execute([':id' => (int)$input['id']]);
            jsonResponse(['ok' => true]);

        // ── ASIGNAR ───────────────────────────────────────────
        case 'asignar':
            $activo_id  = (int)$input['activo_id'];
            $usuario_id = (int)$input['usuario_id'];
            $periodo    = $input['periodo_tipo'] ?? 'dia';
            $fecha_ini  = $input['fecha_inicio'] ?: date('Y-m-d');
            $obs        = ($input['observaciones'] ?? null) ?: null;

            switch ($periodo) {
                case 'semana': $fecha_fin = date('Y-m-d', strtotime($fecha_ini . ' +6 days')); break;
                case 'mes':    $fecha_fin = date('Y-m-d', strtotime($fecha_ini . ' +29 days')); break;
                default:       $fecha_fin = $fecha_ini;
            }

            $chk = $db->prepare("SELECT COUNT(*) FROM activo_asignaciones
                WHERE activo_id = :aid AND devuelto = FALSE AND fecha_fin >= CURRENT_DATE");
            $chk->execute([':aid' => $activo_id]);
            if ($chk->fetchColumn() > 0)
                jsonResponse(['error' => 'El activo ya está asignado. Primero regístralo como devuelto.'], 409);

            $db->prepare("INSERT INTO activo_asignaciones
                (activo_id, usuario_id, periodo_tipo, fecha_inicio, fecha_fin, observaciones)
                VALUES (:aid, :uid, :periodo, :fi, :ff, :obs) RETURNING id")
               ->execute([':aid'=>$activo_id,':uid'=>$usuario_id,':periodo'=>$periodo,
                          ':fi'=>$fecha_ini,':ff'=>$fecha_fin,':obs'=>$obs]);
            jsonResponse(['ok' => true, 'fecha_fin' => $fecha_fin]);

        // ── DEVOLVER ──────────────────────────────────────────
        case 'devolver':
            $db->prepare("UPDATE activo_asignaciones
                SET devuelto = TRUE, fecha_devolucion = CURRENT_TIMESTAMP
                WHERE activo_id = :aid AND devuelto = FALSE")
               ->execute([':aid' => (int)$input['activo_id']]);
            jsonResponse(['ok' => true]);

        // ── HISTORIAL ─────────────────────────────────────────
        case 'historial':
            $where  = ['1=1'];
            $params = [];
            if (!empty($input['activo_id']))  { $where[] = "aa.activo_id = :aid";  $params[':aid'] = (int)$input['activo_id']; }
            if (!empty($input['usuario_id'])) { $where[] = "aa.usuario_id = :uid"; $params[':uid'] = (int)$input['usuario_id']; }
            $stmt = $db->prepare("
                SELECT aa.*, a.codigo AS activo_codigo, a.nombre AS activo_nombre, u.nombre AS usuario_nombre
                FROM activo_asignaciones aa
                JOIN activos  a ON a.id = aa.activo_id
                JOIN usuarios u ON u.id = aa.usuario_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY aa.created_at DESC LIMIT 100");
            $stmt->execute($params);
            jsonResponse(['ok' => true, 'historial' => $stmt->fetchAll()]);

        // ── CATEGORÍAS ────────────────────────────────────────
        case 'categorias_listar':
            $stmt = $db->query("
                SELECT ac.*, COUNT(a.id) AS total_activos
                FROM activo_categorias ac
                LEFT JOIN activos a ON a.categoria_id = ac.id AND a.activo
                WHERE ac.activo
                GROUP BY ac.id ORDER BY ac.nombre");
            jsonResponse(['ok' => true, 'categorias' => $stmt->fetchAll()]);

        case 'categoria_guardar':
            $id = (int)($input['id'] ?? 0);
            if ($id) {
                $db->prepare("UPDATE activo_categorias SET nombre=:n, descripcion=:d WHERE id=:id")
                   ->execute([':n'=>$input['nombre'],':d'=>($input['descripcion']??null)?:null,':id'=>$id]);
            } else {
                $db->prepare("INSERT INTO activo_categorias (nombre, descripcion) VALUES (:n, :d)")
                   ->execute([':n'=>$input['nombre'],':d'=>($input['descripcion']??null)?:null]);
            }
            jsonResponse(['ok' => true]);

        case 'categoria_eliminar':
            $chk = $db->prepare("SELECT COUNT(*) FROM activos WHERE categoria_id=:id AND activo");
            $chk->execute([':id' => (int)$input['id']]);
            if ($chk->fetchColumn() > 0) jsonResponse(['error' => 'Tiene activos asociados.'], 409);
            $db->prepare("DELETE FROM activo_categorias WHERE id=:id")->execute([':id' => (int)$input['id']]);
            jsonResponse(['ok' => true]);

        // ── COMBOS ────────────────────────────────────────────
        case 'combos':
            $cats  = $db->query("SELECT id, nombre FROM activo_categorias WHERE activo ORDER BY nombre")->fetchAll();
            $users = $db->query("SELECT id, nombre FROM usuarios WHERE activo ORDER BY nombre")->fetchAll();
            jsonResponse(['ok' => true, 'categorias' => $cats, 'usuarios' => $users]);

        // ══════════════════════════════════════════════════════
        // TRANSPORTE
        // ══════════════════════════════════════════════════════
        case 'trans_listar':
            $where  = ['activo = TRUE'];
            $params = [];
            if (!empty($input['q'])) {
                $where[]    = "(nombre ILIKE :q OR codigo ILIKE :q OR placa ILIKE :q OR marca ILIKE :q)";
                $params[':q'] = '%' . $input['q'] . '%';
            }
            if (!empty($input['estado'])) {
                $where[]           = "estado = :estado";
                $params[':estado'] = $input['estado'];
            }
            $stmt = $db->prepare("SELECT * FROM transporte WHERE " . implode(' AND ', $where) . " ORDER BY nombre");
            $stmt->execute($params);
            jsonResponse(['ok' => true, 'transporte' => $stmt->fetchAll()]);

        case 'trans_obtener':
            $stmt = $db->prepare("SELECT * FROM transporte WHERE id = :id");
            $stmt->execute([':id' => (int)$input['id']]);
            $vehiculo = $stmt->fetch();
            if (!$vehiculo) jsonResponse(['error' => 'No encontrado.'], 404);
            $imgs = $db->prepare("SELECT id, filename, orden FROM transporte_imagenes WHERE transporte_id=:id ORDER BY orden ASC");
            $imgs->execute([':id' => (int)$input['id']]);
            $vehiculo['imagenes'] = $imgs->fetchAll();
            jsonResponse(['ok' => true, 'vehiculo' => $vehiculo]);

        case 'trans_guardar':
            if (empty($input['nombre'])) jsonResponse(['error' => 'El nombre es obligatorio.'], 400);
            $id = (int)($input['id'] ?? 0);

            if ($id) {
                $db->prepare("UPDATE transporte SET
                    nombre=:nom, placa=:placa, marca=:marca, modelo=:modelo,
                    anio=:anio, color=:color, estado=:estado,
                    descripcion=:desc, observaciones=:obs, updated_at=NOW()
                    WHERE id=:id")->execute([
                    ':id'     => $id,
                    ':nom'    => $input['nombre'],
                    ':placa'  => ($input['placa']         ?? null) ?: null,
                    ':marca'  => ($input['marca']         ?? null) ?: null,
                    ':modelo' => ($input['modelo']        ?? null) ?: null,
                    ':anio'   => ($input['anio']          ?? null) ?: null,
                    ':color'  => ($input['color']         ?? null) ?: null,
                    ':estado' => $input['estado'] ?? 'operativo',
                    ':desc'   => ($input['descripcion']   ?? null) ?: null,
                    ':obs'    => ($input['observaciones'] ?? null) ?: null,
                ]);
            } else {
                $codigo = generarCodigo('TRA-', 'transporte', 'codigo');
                $stmt   = $db->prepare("INSERT INTO transporte
                    (codigo, nombre, placa, marca, modelo, anio, color, estado, descripcion, observaciones)
                    VALUES (:cod,:nom,:placa,:marca,:modelo,:anio,:color,:estado,:desc,:obs) RETURNING id");
                $stmt->execute([
                    ':cod'    => $codigo,
                    ':nom'    => $input['nombre'],
                    ':placa'  => ($input['placa']         ?? null) ?: null,
                    ':marca'  => ($input['marca']         ?? null) ?: null,
                    ':modelo' => ($input['modelo']        ?? null) ?: null,
                    ':anio'   => ($input['anio']          ?? null) ?: null,
                    ':color'  => ($input['color']         ?? null) ?: null,
                    ':estado' => $input['estado'] ?? 'operativo',
                    ':desc'   => ($input['descripcion']   ?? null) ?: null,
                    ':obs'    => ($input['observaciones'] ?? null) ?: null,
                ]);
                $id = (int)$stmt->fetchColumn();
            }

            // Process up to 4 image slots
            for ($i = 0; $i < 4; $i++) {
                // Remove an existing image
                if (!empty($input["img_remove_$i"])) {
                    $img_id = (int)$input["img_remove_$i"];
                    $row    = $db->prepare("SELECT filename FROM transporte_imagenes WHERE id=:id AND transporte_id=:tid");
                    $row->execute([':id' => $img_id, ':tid' => $id]);
                    deleteActivo($row->fetchColumn() ?: '');
                    $db->prepare("DELETE FROM transporte_imagenes WHERE id=:id")->execute([':id' => $img_id]);
                }
                // Update order of kept image
                if (!empty($input["img_keep_$i"])) {
                    $db->prepare("UPDATE transporte_imagenes SET orden=:ord WHERE id=:id AND transporte_id=:tid")
                       ->execute([':ord' => $i, ':id' => (int)$input["img_keep_$i"], ':tid' => $id]);
                }
                // Upload new image for this slot
                $fn = uploadActivoFile("imagen_$i", 'tra');
                if ($fn) {
                    $db->prepare("INSERT INTO transporte_imagenes (transporte_id, filename, orden) VALUES (:tid,:fn,:ord)")
                       ->execute([':tid' => $id, ':fn' => $fn, ':ord' => $i]);
                }
            }

            // Keep imagen column in sync with the first image (for quick thumbnail access)
            $first = $db->prepare("SELECT filename FROM transporte_imagenes WHERE transporte_id=:id ORDER BY orden ASC LIMIT 1");
            $first->execute([':id' => $id]);
            $db->prepare("UPDATE transporte SET imagen=:img WHERE id=:id")
               ->execute([':img' => $first->fetchColumn() ?: null, ':id' => $id]);

            jsonResponse(['ok' => true, 'id' => $id]);

        case 'trans_toggle':
            $db->prepare("UPDATE transporte SET activo = NOT activo WHERE id = :id")
               ->execute([':id' => (int)$input['id']]);
            jsonResponse(['ok' => true]);

        case 'trans_eliminar':
            $id = (int)($input['id'] ?? 0);
            if (!$id) jsonResponse(['error' => 'ID requerido.'], 400);
            // Delete gallery images from filesystem
            $imgs = $db->prepare("SELECT filename FROM transporte_imagenes WHERE transporte_id = :id");
            $imgs->execute([':id' => $id]);
            $upload_dir = dirname(__DIR__, 2) . '/assets/uploads/activos/';
            foreach ($imgs->fetchAll(PDO::FETCH_COLUMN) as $fn) @unlink($upload_dir . $fn);
            // Also delete main imagen if set
            $main = $db->prepare("SELECT imagen FROM transporte WHERE id = :id");
            $main->execute([':id' => $id]);
            if ($fn = $main->fetchColumn()) @unlink($upload_dir . $fn);
            // Delete record (CASCADE removes transporte_imagenes rows)
            $db->prepare("DELETE FROM transporte WHERE id = :id")->execute([':id' => $id]);
            jsonResponse(['ok' => true]);

        // ══════════════════════════════════════════════════════
        // MOBILIARIA Y EQUIPOS
        // ══════════════════════════════════════════════════════
        case 'mob_listar':
            $where  = ['activo = TRUE'];
            $params = [];
            if (!empty($input['q'])) {
                $where[]      = "(nombre ILIKE :q OR codigo ILIKE :q OR marca ILIKE :q OR ubicacion ILIKE :q)";
                $params[':q'] = '%' . $input['q'] . '%';
            }
            if (!empty($input['tipo']))   { $where[] = "tipo = :tipo";     $params[':tipo']   = $input['tipo']; }
            if (!empty($input['estado'])) { $where[] = "estado = :estado"; $params[':estado'] = $input['estado']; }
            $stmt = $db->prepare("SELECT * FROM mobiliaria_equipos WHERE " . implode(' AND ', $where) . " ORDER BY nombre");
            $stmt->execute($params);
            jsonResponse(['ok' => true, 'items' => $stmt->fetchAll()]);

        case 'mob_obtener':
            $stmt = $db->prepare("SELECT * FROM mobiliaria_equipos WHERE id = :id");
            $stmt->execute([':id' => (int)$input['id']]);
            jsonResponse(['ok' => true, 'item' => $stmt->fetch()]);

        case 'mob_guardar':
            if (empty($input['nombre'])) jsonResponse(['error' => 'El nombre es obligatorio.'], 400);
            $id         = (int)($input['id'] ?? 0);
            $imagen_new = uploadActivo('mob');
            $remove_img = !empty($input['remove_imagen']);

            if ($id) {
                $img_sql    = '';
                $img_params = [];
                if ($imagen_new) {
                    $old = $db->prepare("SELECT imagen FROM mobiliaria_equipos WHERE id=:id");
                    $old->execute([':id' => $id]);
                    deleteActivo($old->fetchColumn());
                    $img_sql           = ', imagen=:img';
                    $img_params[':img'] = $imagen_new;
                } elseif ($remove_img) {
                    $old = $db->prepare("SELECT imagen FROM mobiliaria_equipos WHERE id=:id");
                    $old->execute([':id' => $id]);
                    deleteActivo($old->fetchColumn());
                    $img_sql = ', imagen=NULL';
                }
                $db->prepare("UPDATE mobiliaria_equipos SET
                    nombre=:nom, tipo=:tipo, ubicacion=:ubi, marca=:marca, modelo=:modelo,
                    num_serie=:ns, num_inventario=:ni, fecha_adquisicion=:fadq,
                    estado=:estado, descripcion=:desc, observaciones=:obs
                    {$img_sql}, updated_at=NOW()
                    WHERE id=:id")->execute(array_merge([
                    ':id'    => $id,
                    ':nom'   => $input['nombre'],
                    ':tipo'  => $input['tipo']   ?? 'equipo',
                    ':ubi'   => ($input['ubicacion']         ?? null) ?: null,
                    ':marca' => ($input['marca']             ?? null) ?: null,
                    ':modelo'=> ($input['modelo']            ?? null) ?: null,
                    ':ns'    => ($input['num_serie']         ?? null) ?: null,
                    ':ni'    => ($input['num_inventario']    ?? null) ?: null,
                    ':fadq'  => ($input['fecha_adquisicion'] ?? null) ?: null,
                    ':estado'=> $input['estado'] ?? 'bueno',
                    ':desc'  => ($input['descripcion']       ?? null) ?: null,
                    ':obs'   => ($input['observaciones']     ?? null) ?: null,
                ], $img_params));
            } else {
                $codigo = generarCodigo('MOB-', 'mobiliaria_equipos', 'codigo');
                $stmt   = $db->prepare("INSERT INTO mobiliaria_equipos
                    (codigo, nombre, tipo, ubicacion, marca, modelo, num_serie, num_inventario,
                     fecha_adquisicion, estado, descripcion, observaciones, imagen)
                    VALUES (:cod,:nom,:tipo,:ubi,:marca,:modelo,:ns,:ni,:fadq,:estado,:desc,:obs,:img) RETURNING id");
                $stmt->execute([
                    ':cod'   => $codigo,
                    ':nom'   => $input['nombre'],
                    ':tipo'  => $input['tipo']   ?? 'equipo',
                    ':ubi'   => ($input['ubicacion']         ?? null) ?: null,
                    ':marca' => ($input['marca']             ?? null) ?: null,
                    ':modelo'=> ($input['modelo']            ?? null) ?: null,
                    ':ns'    => ($input['num_serie']         ?? null) ?: null,
                    ':ni'    => ($input['num_inventario']    ?? null) ?: null,
                    ':fadq'  => ($input['fecha_adquisicion'] ?? null) ?: null,
                    ':estado'=> $input['estado'] ?? 'bueno',
                    ':desc'  => ($input['descripcion']       ?? null) ?: null,
                    ':obs'   => ($input['observaciones']     ?? null) ?: null,
                    ':img'   => $imagen_new,
                ]);
                $id = (int)$stmt->fetchColumn();
            }
            jsonResponse(['ok' => true, 'id' => $id]);

        case 'mob_toggle':
            $db->prepare("UPDATE mobiliaria_equipos SET activo = NOT activo WHERE id = :id")
               ->execute([':id' => (int)$input['id']]);
            jsonResponse(['ok' => true]);

        default:
            jsonResponse(['error' => 'Acción no reconocida.'], 400);
    }
} catch (PDOException $e) {
    if ($db->inTransaction()) $db->rollBack();
    jsonResponse(['error' => 'Error BD: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}

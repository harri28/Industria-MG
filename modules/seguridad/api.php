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
    // USUARIOS
    // ================================================================
    case 'usuarios_listar':
        $stmt = $db->query("
            SELECT u.id, u.nombre, u.email, u.activo, u.ultimo_acceso,
                   r.nombre AS rol_nombre, a.nombre AS area_nombre
            FROM usuarios u
            LEFT JOIN roles  r ON r.id = u.rol_id
            LEFT JOIN areas  a ON a.id = u.area_id
            ORDER BY u.nombre");
        jsonResponse(['ok' => true, 'usuarios' => $stmt->fetchAll()]);

    case 'usuario_obtener':
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = :id");
        $stmt->execute([':id' => (int)$input['id']]);
        jsonResponse(['ok' => true, 'usuario' => $stmt->fetch()]);

    case 'usuario_crear':
        if (empty($input['nombre']) || empty($input['email']) || empty($input['password']))
            jsonResponse(['error' => 'Nombre, email y contraseña son obligatorios.'], 400);
        // Verificar email único
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = :email");
        $stmt->execute([':email' => trim($input['email'])]);
        if ($stmt->fetch()) jsonResponse(['error' => 'El email ya está registrado.'], 400);

        $hash = password_hash($input['password'], PASSWORD_BCRYPT);
        $stmt = $db->prepare("
            INSERT INTO usuarios (nombre, email, password, rol_id, area_id, activo)
            VALUES (:nombre, :email, :password, :rol_id, :area_id, :activo)
            RETURNING id");
        $stmt->execute([
            ':nombre'   => trim($input['nombre']),
            ':email'    => trim($input['email']),
            ':password' => $hash,
            ':rol_id'   => ($input['rol_id']  ?? null) ?: null,
            ':area_id'  => ($input['area_id'] ?? null) ?: null,
            ':activo'   => isset($input['activo']) ? (bool)$input['activo'] : true,
        ]);
        $id = $stmt->fetchColumn();
        auditoria($db, 'crear', 'seguridad', 'usuarios', $id, null, ['nombre' => $input['nombre'], 'email' => $input['email']]);
        jsonResponse(['ok' => true, 'id' => $id]);

    case 'usuario_actualizar':
        $id = (int)($input['id'] ?? 0);
        // Verificar email único (excepto el propio)
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = :email AND id != :id");
        $stmt->execute([':email' => trim($input['email']), ':id' => $id]);
        if ($stmt->fetch()) jsonResponse(['error' => 'El email ya está en uso.'], 400);

        $sql = "UPDATE usuarios SET
                    nombre  = :nombre,
                    email   = :email,
                    rol_id  = :rol_id,
                    area_id = :area_id,
                    activo  = :activo";
        $params = [
            ':id'      => $id,
            ':nombre'  => trim($input['nombre']  ?? ''),
            ':email'   => trim($input['email']   ?? ''),
            ':rol_id'  => ($input['rol_id']  ?? null) ?: null,
            ':area_id' => ($input['area_id'] ?? null) ?: null,
            ':activo'  => filter_var($input['activo'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ];
        // Cambiar contraseña solo si se envía
        if (!empty($input['password'])) {
            $sql .= ", password = :password";
            $params[':password'] = password_hash($input['password'], PASSWORD_BCRYPT);
        }
        $sql .= " WHERE id = :id";
        $db->prepare($sql)->execute($params);
        auditoria($db, 'editar', 'seguridad', 'usuarios', $id, null, ['nombre' => $input['nombre']]);
        jsonResponse(['ok' => true]);

    case 'usuario_toggle':
        $id = (int)($input['id'] ?? 0);
        $db->prepare("UPDATE usuarios SET activo = NOT activo WHERE id = :id")->execute([':id' => $id]);
        jsonResponse(['ok' => true]);

    // ================================================================
    // ROLES
    // ================================================================
    case 'roles_listar':
        $stmt = $db->query("
            SELECT r.*, COUNT(u.id) AS total_usuarios
            FROM roles r
            LEFT JOIN usuarios u ON u.rol_id = r.id AND u.activo
            GROUP BY r.id ORDER BY r.nombre");
        jsonResponse(['ok' => true, 'roles' => $stmt->fetchAll()]);

    case 'rol_obtener':
        $stmt = $db->prepare("SELECT * FROM roles WHERE id = :id");
        $stmt->execute([':id' => (int)$input['id']]);
        $rol = $stmt->fetch();
        if ($rol) $rol['permisos'] = is_string($rol['permisos']) ? json_decode($rol['permisos'], true) : $rol['permisos'];
        jsonResponse(['ok' => true, 'rol' => $rol]);

    case 'rol_guardar':
        $permisos = $input['permisos'] ?? [];
        $permisos_json = json_encode($permisos);
        if (!empty($input['id'])) {
            $db->prepare("UPDATE roles SET nombre = :nombre, descripcion = :desc, permisos = :permisos WHERE id = :id")
               ->execute([':id' => (int)$input['id'], ':nombre' => trim($input['nombre']), ':desc' => $input['descripcion'] ?? null, ':permisos' => $permisos_json]);
            auditoria($db, 'editar', 'seguridad', 'roles', (int)$input['id'], null, ['nombre' => $input['nombre']]);
        } else {
            $stmt = $db->prepare("INSERT INTO roles (nombre, descripcion, permisos) VALUES (:nombre, :desc, :permisos) RETURNING id");
            $stmt->execute([':nombre' => trim($input['nombre']), ':desc' => $input['descripcion'] ?? null, ':permisos' => $permisos_json]);
            $id = $stmt->fetchColumn();
            auditoria($db, 'crear', 'seguridad', 'roles', $id, null, ['nombre' => $input['nombre']]);
        }
        jsonResponse(['ok' => true]);

    case 'rol_eliminar':
        $id = (int)($input['id'] ?? 0);
        $uso = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE rol_id = :id");
        $uso->execute([':id' => $id]);
        if ($uso->fetchColumn() > 0) jsonResponse(['error' => 'No se puede eliminar: hay usuarios asignados a este rol.'], 400);
        $db->prepare("DELETE FROM roles WHERE id = :id")->execute([':id' => $id]);
        jsonResponse(['ok' => true]);

    // ================================================================
    // ÁREAS
    // ================================================================
    case 'areas_listar':
        $stmt = $db->query("
            SELECT a.*, COUNT(u.id) AS total_usuarios
            FROM areas a
            LEFT JOIN usuarios u ON u.area_id = a.id AND u.activo
            GROUP BY a.id ORDER BY a.nombre");
        jsonResponse(['ok' => true, 'areas' => $stmt->fetchAll()]);

    case 'area_guardar':
        if (!empty($input['id'])) {
            $db->prepare("UPDATE areas SET nombre = :nombre, descripcion = :desc WHERE id = :id")
               ->execute([':id' => (int)$input['id'], ':nombre' => trim($input['nombre']), ':desc' => $input['descripcion'] ?? null]);
        } else {
            $stmt = $db->prepare("INSERT INTO areas (nombre, descripcion) VALUES (:nombre, :desc) RETURNING id");
            $stmt->execute([':nombre' => trim($input['nombre']), ':desc' => $input['descripcion'] ?? null]);
        }
        jsonResponse(['ok' => true]);

    case 'area_toggle':
        $db->prepare("UPDATE areas SET activo = NOT activo WHERE id = :id")->execute([':id' => (int)($input['id'] ?? 0)]);
        jsonResponse(['ok' => true]);

    case 'area_eliminar':
        $id = (int)($input['id'] ?? 0);
        $uso = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE area_id = :id");
        $uso->execute([':id' => $id]);
        if ($uso->fetchColumn() > 0) jsonResponse(['error' => 'No se puede eliminar: hay usuarios en esta área.'], 400);
        $db->prepare("DELETE FROM areas WHERE id = :id")->execute([':id' => $id]);
        jsonResponse(['ok' => true]);

    // ================================================================
    // AUDITORÍA
    // ================================================================
    case 'auditoria_listar':
        $where = ['1=1'];
        $params = [];
        if (!empty($input['modulo'])) { $where[] = "a.modulo = :modulo"; $params[':modulo'] = $input['modulo']; }
        if (!empty($input['accion']))  { $where[] = "a.accion = :accion";  $params[':accion']  = $input['accion']; }
        if (!empty($input['desde']))   { $where[] = "a.created_at >= :desde"; $params[':desde'] = $input['desde'] . ' 00:00:00'; }
        if (!empty($input['hasta']))   { $where[] = "a.created_at <= :hasta"; $params[':hasta'] = $input['hasta'] . ' 23:59:59'; }
        $stmt = $db->prepare("
            SELECT a.*, u.nombre AS usuario_nombre
            FROM auditoria a
            LEFT JOIN usuarios u ON u.id = a.usuario_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY a.created_at DESC LIMIT 200");
        $stmt->execute($params);
        jsonResponse(['ok' => true, 'registros' => $stmt->fetchAll()]);

    // ================================================================
    // BACKUPS
    // ================================================================
    case 'backup_listar':
        $stmt = $db->query("SELECT b.*, u.nombre AS usuario_nombre FROM backups b LEFT JOIN usuarios u ON u.id = b.usuario_id ORDER BY b.created_at DESC LIMIT 50");
        jsonResponse(['ok' => true, 'backups' => $stmt->fetchAll()]);

    case 'backup_crear':
        $pgdump  = 'C:/Program Files/PostgreSQL/17/bin/pg_dump.exe';
        $dir     = __DIR__ . '/../../backups/';
        $nombre  = 'backup_' . date('Ymd_His') . '.sql';
        $archivo = $dir . $nombre;

        putenv('PGPASSWORD=' . DB_PASS);
        $cmd = "\"$pgdump\" -U " . DB_USER . " -h " . DB_HOST . " -p " . DB_PORT . " " . DB_NAME . " > \"$archivo\" 2>&1";
        exec($cmd, $output, $code);

        if ($code !== 0 || !file_exists($archivo)) {
            jsonResponse(['error' => 'Error al generar el backup: ' . implode(' ', $output)], 500);
        }

        $tamanio = filesize($archivo);
        $stmt = $db->prepare("INSERT INTO backups (nombre, archivo, tamanio, estado) VALUES (:nombre, :archivo, :tamanio, 'completado') RETURNING id");
        $stmt->execute([':nombre' => $nombre, ':archivo' => $archivo, ':tamanio' => $tamanio]);
        auditoria($db, 'crear', 'seguridad', 'backups', (int)$stmt->fetchColumn(), null, ['archivo' => $nombre]);
        jsonResponse(['ok' => true, 'nombre' => $nombre, 'tamanio' => $tamanio]);

    case 'backup_eliminar':
        $id = (int)($input['id'] ?? 0);
        $stmt = $db->prepare("SELECT archivo FROM backups WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if ($row && file_exists($row['archivo'])) unlink($row['archivo']);
        $db->prepare("DELETE FROM backups WHERE id = :id")->execute([':id' => $id]);
        jsonResponse(['ok' => true]);

    // Datos auxiliares
    case 'combos':
        $roles  = $db->query("SELECT id, nombre FROM roles  WHERE activo ORDER BY nombre")->fetchAll();
        $areas  = $db->query("SELECT id, nombre FROM areas  WHERE activo ORDER BY nombre")->fetchAll();
        jsonResponse(['ok' => true, 'roles' => $roles, 'areas' => $areas]);

    case 'logout':
        session_destroy();
        jsonResponse(['ok' => true]);

    default:
        jsonResponse(['error' => 'Acción no reconocida.'], 400);

}} catch (PDOException $e) {
    jsonResponse(['error' => 'Error BD: ' . $e->getMessage()], 500);
}

// ── Helper auditoría ──────────────────────────────────────
function auditoria(PDO $db, string $accion, string $modulo, string $tabla, int $registro_id, ?array $anterior, ?array $nuevo): void {
    try {
        $db->prepare("
            INSERT INTO auditoria (usuario_id, accion, modulo, tabla, registro_id, datos_anteriores, datos_nuevos, ip)
            VALUES (:uid, :accion, :modulo, :tabla, :rid, :ant, :nvo, :ip)")
        ->execute([
            ':uid'    => null,
            ':accion' => $accion,
            ':modulo' => $modulo,
            ':tabla'  => $tabla,
            ':rid'    => $registro_id,
            ':ant'    => $anterior ? json_encode($anterior) : null,
            ':nvo'    => $nuevo    ? json_encode($nuevo)    : null,
            ':ip'     => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Exception $e) {}
}

<?php
ini_set('display_errors', 0);
error_reporting(0);
require_once __DIR__ . '/../../config/session.php';
startAppSession();
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = empty($input) ? ($_POST['action'] ?? $action) : ($input['action'] ?? $action);
    if (empty($input)) $input = $_POST;
} else {
    $input = $_GET;
}

$db = getDB();

try { switch ($action) {

    // ── DASHBOARD ────────────────────────────────────────────────────────
    case 'dashboard':
        $total     = $db->query("SELECT COUNT(*) FROM materiales_capacitacion WHERE activo")->fetchColumn();
        $por_tipo  = $db->query("SELECT tipo, COUNT(*) AS cnt FROM materiales_capacitacion WHERE activo GROUP BY tipo ORDER BY cnt DESC")->fetchAll();
        $usuarios  = $db->query("SELECT COUNT(DISTINCT usuario_id) FROM progreso_capacitacion WHERE completado")->fetchColumn();
        $completados_total = $db->query("SELECT COUNT(*) FROM progreso_capacitacion WHERE completado")->fetchColumn();
        $con_examen = $db->query("SELECT COUNT(DISTINCT material_id) FROM examenes WHERE activo")->fetchColumn();
        jsonResponse(['ok' => true,
            'total'                 => (int)$total,
            'por_tipo'              => $por_tipo,
            'usuarios_con_progreso' => (int)$usuarios,
            'completados_total'     => (int)$completados_total,
            'materiales_con_examen' => (int)$con_examen,
        ]);

    // ── MATERIALES ───────────────────────────────────────────────────────
    case 'materiales_listar':
        $where  = ['m.activo = TRUE'];
        $params = [];
        if (!empty($input['q'])) {
            $where[] = "(m.titulo ILIKE :q OR m.descripcion ILIKE :q OR m.categoria ILIKE :q)";
            $params[':q'] = '%' . $input['q'] . '%';
        }
        if (!empty($input['tipo']))      { $where[] = "m.tipo=:tipo";      $params[':tipo'] = $input['tipo']; }
        if (!empty($input['categoria'])) { $where[] = "m.categoria=:cat";  $params[':cat']  = $input['categoria']; }
        if (!empty($input['rol_id']))    { $where[] = "(m.rol_id IS NULL OR m.rol_id=:rid)";  $params[':rid'] = (int)$input['rol_id']; }
        if (!empty($input['area_id']))   { $where[] = "(m.area_id IS NULL OR m.area_id=:aid)"; $params[':aid'] = (int)$input['area_id']; }

        $stmt = $db->prepare("
            SELECT m.*, r.nombre AS rol_nombre, a.nombre AS area_nombre,
                   COUNT(p.id) AS total_progreso,
                   COUNT(p.id) FILTER (WHERE p.completado) AS total_completados,
                   e.id AS examen_id
            FROM materiales_capacitacion m
            LEFT JOIN roles r ON r.id = m.rol_id
            LEFT JOIN areas a ON a.id = m.area_id
            LEFT JOIN progreso_capacitacion p ON p.material_id = m.id
            LEFT JOIN (
                SELECT DISTINCT ON (material_id) id, material_id
                FROM examenes WHERE activo ORDER BY material_id, id
            ) e ON e.material_id = m.id
            WHERE " . implode(' AND ', $where) . "
            GROUP BY m.id, r.nombre, a.nombre, e.id
            ORDER BY m.orden, m.titulo");
        $stmt->execute($params);
        jsonResponse(['ok' => true, 'materiales' => $stmt->fetchAll()]);

    case 'material_obtener':
        $stmt = $db->prepare("
            SELECT m.*, r.nombre AS rol_nombre, a.nombre AS area_nombre, e.id AS examen_id
            FROM materiales_capacitacion m
            LEFT JOIN roles r ON r.id = m.rol_id
            LEFT JOIN areas a ON a.id = m.area_id
            LEFT JOIN (
                SELECT DISTINCT ON (material_id) id, material_id
                FROM examenes WHERE activo ORDER BY material_id, id
            ) e ON e.material_id = m.id
            WHERE m.id = :id");
        $stmt->execute([':id' => (int)$input['id']]);
        jsonResponse(['ok' => true, 'material' => $stmt->fetch()]);

    case 'material_guardar':
        if (empty($input['titulo'])) jsonResponse(['error' => 'El título es obligatorio.'], 400);
        $id = 0;
        if (!empty($input['id'])) {
            $id = (int)$input['id'];
            $db->prepare("UPDATE materiales_capacitacion SET
                titulo=:tit, descripcion=:desc, tipo=:tipo, archivo=:arch,
                url=:url, categoria=:cat, rol_id=:rid, area_id=:aid, orden=:orden
                WHERE id=:id")->execute([
                ':id'    => $id,
                ':tit'   => trim($input['titulo']),
                ':desc'  => ($input['descripcion'] ?? null) ?: null,
                ':tipo'  => $input['tipo'] ?? 'texto',
                ':arch'  => ($input['archivo'] ?? null) ?: null,
                ':url'   => ($input['url'] ?? null) ?: null,
                ':cat'   => ($input['categoria'] ?? null) ?: null,
                ':rid'   => ($input['rol_id'] ?? null) ?: null,
                ':aid'   => ($input['area_id'] ?? null) ?: null,
                ':orden' => (int)($input['orden'] ?? 0),
            ]);
        } else {
            $stmt2 = $db->prepare("INSERT INTO materiales_capacitacion
                (titulo, descripcion, tipo, archivo, url, categoria, rol_id, area_id, orden)
                VALUES (:tit,:desc,:tipo,:arch,:url,:cat,:rid,:aid,:orden) RETURNING id");
            $stmt2->execute([
                ':tit'   => trim($input['titulo']),
                ':desc'  => ($input['descripcion'] ?? null) ?: null,
                ':tipo'  => $input['tipo'] ?? 'texto',
                ':arch'  => ($input['archivo'] ?? null) ?: null,
                ':url'   => ($input['url'] ?? null) ?: null,
                ':cat'   => ($input['categoria'] ?? null) ?: null,
                ':rid'   => ($input['rol_id'] ?? null) ?: null,
                ':aid'   => ($input['area_id'] ?? null) ?: null,
                ':orden' => (int)($input['orden'] ?? 0),
            ]);
            $id = (int)$stmt2->fetchColumn();
        }
        jsonResponse(['ok' => true, 'id' => $id]);

    case 'material_toggle':
        $db->prepare("UPDATE materiales_capacitacion SET activo=NOT activo WHERE id=:id")
           ->execute([':id' => (int)($input['id'] ?? 0)]);
        jsonResponse(['ok' => true]);

    case 'material_eliminar':
        $db->prepare("DELETE FROM materiales_capacitacion WHERE id=:id")
           ->execute([':id' => (int)($input['id'] ?? 0)]);
        jsonResponse(['ok' => true]);

    case 'mat_pdf_upload':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) jsonResponse(['error' => 'ID requerido.'], 400);
        if (empty($_FILES['pdf']['tmp_name'])) jsonResponse(['error' => 'Archivo requerido.'], 400);
        $file = $_FILES['pdf'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'pdf') jsonResponse(['error' => 'Solo archivos PDF.'], 400);
        if ($file['size'] > 20 * 1024 * 1024) jsonResponse(['error' => 'Máximo 20MB.'], 400);
        $capDir = __DIR__ . '/../../assets/uploads/capacitacion/';
        if (!is_dir($capDir)) mkdir($capDir, 0755, true);
        $oldRow = $db->prepare("SELECT archivo FROM materiales_capacitacion WHERE id=:id");
        $oldRow->execute([':id' => $id]);
        $oldArch = $oldRow->fetchColumn();
        if ($oldArch && !str_starts_with($oldArch, 'http') && file_exists($capDir . $oldArch)) {
            unlink($capDir . $oldArch);
        }
        $pdfFilename = 'mat_' . time() . '_' . bin2hex(random_bytes(4)) . '.pdf';
        if (!move_uploaded_file($file['tmp_name'], $capDir . $pdfFilename)) {
            jsonResponse(['error' => 'Error al guardar archivo.'], 500);
        }
        $db->prepare("UPDATE materiales_capacitacion SET archivo=:arch, url=NULL WHERE id=:id")
           ->execute([':arch' => $pdfFilename, ':id' => $id]);
        jsonResponse(['ok' => true, 'filename' => $pdfFilename]);

    // ── PROGRESO ─────────────────────────────────────────────────────────
    case 'progreso_listar':
        $mat_id = (int)($input['material_id'] ?? 0);
        if (!$mat_id) jsonResponse(['error' => 'material_id requerido.'], 400);
        $stmt = $db->prepare("
            SELECT u.id AS usuario_id, u.nombre AS usuario_nombre, u.email,
                   p.id,
                   COALESCE(p.completado, FALSE) AS completado,
                   TO_CHAR(p.fecha_completado, 'YYYY-MM-DD') AS fecha_completado,
                   ie.puntaje AS examen_puntaje,
                   ie.aprobado AS examen_aprobado
            FROM usuarios u
            LEFT JOIN progreso_capacitacion p ON p.usuario_id = u.id AND p.material_id = :mid
            LEFT JOIN (
                SELECT DISTINCT ON (usuario_id) usuario_id, puntaje, aprobado
                FROM intentos_examen
                WHERE examen_id IN (SELECT id FROM examenes WHERE material_id = :mid2 AND activo)
                ORDER BY usuario_id, puntaje DESC NULLS LAST
            ) ie ON ie.usuario_id = u.id
            WHERE u.activo = TRUE
            ORDER BY u.nombre");
        $stmt->execute([':mid' => $mat_id, ':mid2' => $mat_id]);
        jsonResponse(['ok' => true, 'progresos' => $stmt->fetchAll()]);

    case 'progreso_marcar':
        $uid    = (int)($input['usuario_id'] ?? null) ?: null;
        $mat_id = (int)($input['material_id'] ?? 0);
        $comp   = filter_var($input['completado'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $db->prepare("
            INSERT INTO progreso_capacitacion (usuario_id, material_id, completado, fecha_completado)
            VALUES (:uid, :mid, :comp, :fecha)
            ON CONFLICT (usuario_id, material_id)
            DO UPDATE SET completado=EXCLUDED.completado, fecha_completado=EXCLUDED.fecha_completado"
        )->execute([
            ':uid'   => $uid,
            ':mid'   => $mat_id,
            ':comp'  => $comp,
            ':fecha' => $comp ? date('Y-m-d H:i:s') : null,
        ]);
        jsonResponse(['ok' => true]);

    // ── COMBOS ───────────────────────────────────────────────────────────
    case 'categorias_lista':
        $stmt = $db->query("SELECT DISTINCT categoria FROM materiales_capacitacion WHERE activo AND categoria IS NOT NULL ORDER BY categoria");
        jsonResponse(['ok' => true, 'categorias' => array_column($stmt->fetchAll(), 'categoria')]);

    case 'combos':
        $roles    = $db->query("SELECT id, nombre FROM roles WHERE activo ORDER BY nombre")->fetchAll();
        $areas    = $db->query("SELECT id, nombre FROM areas WHERE activo ORDER BY nombre")->fetchAll();
        $usuarios = $db->query("SELECT id, nombre FROM usuarios WHERE activo ORDER BY nombre")->fetchAll();
        jsonResponse(['ok' => true, 'roles' => $roles, 'areas' => $areas, 'usuarios' => $usuarios]);

    // ── EXÁMENES: GESTIÓN (ADMIN) ─────────────────────────────────────────
    case 'examen_admin_obtener':
        $mat_id = (int)($input['material_id'] ?? 0);
        if (!$mat_id) jsonResponse(['error' => 'material_id requerido.'], 400);
        $stmt = $db->prepare("SELECT * FROM examenes WHERE material_id=:mid AND activo LIMIT 1");
        $stmt->execute([':mid' => $mat_id]);
        $examen = $stmt->fetch();
        if (!$examen) { jsonResponse(['ok' => true, 'examen' => null]); break; }

        $stmt2 = $db->prepare("SELECT id, texto, orden FROM preguntas_examen WHERE examen_id=:eid ORDER BY orden, id");
        $stmt2->execute([':eid' => $examen['id']]);
        $preguntas = $stmt2->fetchAll();
        foreach ($preguntas as &$pq) {
            $stmt3 = $db->prepare("SELECT id, texto, es_correcta, orden FROM opciones_pregunta WHERE pregunta_id=:pid ORDER BY orden, id");
            $stmt3->execute([':pid' => $pq['id']]);
            $pq['opciones'] = $stmt3->fetchAll();
        }
        $examen['preguntas'] = $preguntas;
        jsonResponse(['ok' => true, 'examen' => $examen]);

    case 'examen_guardar':
        $mat_id = (int)($input['material_id'] ?? 0);
        $titulo = trim($input['titulo'] ?? '');
        if (!$mat_id || !$titulo) jsonResponse(['error' => 'Datos incompletos.'], 400);
        $pm = max(0, min(100, (int)($input['puntaje_minimo'] ?? 70)));

        $stmt = $db->prepare("SELECT id FROM examenes WHERE material_id=:mid LIMIT 1");
        $stmt->execute([':mid' => $mat_id]);
        $existing = $stmt->fetchColumn();
        if ($existing) {
            $db->prepare("UPDATE examenes SET titulo=:tit, puntaje_minimo=:pm, activo=TRUE WHERE id=:id")
               ->execute([':tit' => $titulo, ':pm' => $pm, ':id' => $existing]);
            $eid = (int)$existing;
        } else {
            $s2 = $db->prepare("INSERT INTO examenes (material_id, titulo, puntaje_minimo) VALUES (:mid,:tit,:pm) RETURNING id");
            $s2->execute([':mid' => $mat_id, ':tit' => $titulo, ':pm' => $pm]);
            $eid = (int)$s2->fetchColumn();
        }
        jsonResponse(['ok' => true, 'id' => $eid]);

    case 'examen_eliminar':
        $mat_id = (int)($input['material_id'] ?? 0);
        if (!$mat_id) jsonResponse(['error' => 'material_id requerido.'], 400);
        $db->prepare("DELETE FROM examenes WHERE material_id=:mid")->execute([':mid' => $mat_id]);
        jsonResponse(['ok' => true]);

    case 'pregunta_guardar':
        $eid     = (int)($input['examen_id'] ?? 0);
        $pid     = (int)($input['id'] ?? 0);
        $texto   = trim($input['texto'] ?? '');
        $opciones = $input['opciones'] ?? [];
        if (!$eid || !$texto) jsonResponse(['error' => 'Datos incompletos.'], 400);
        $opciones = array_filter($opciones, fn($o) => !empty(trim($o['texto'] ?? '')));
        if (count($opciones) < 2) jsonResponse(['error' => 'Se necesitan al menos 2 opciones.'], 400);
        if (!array_filter($opciones, fn($o) => !empty($o['es_correcta']))) jsonResponse(['error' => 'Marque una respuesta correcta.'], 400);

        $db->beginTransaction();
        if ($pid) {
            $db->prepare("UPDATE preguntas_examen SET texto=:txt, orden=:ord WHERE id=:id")
               ->execute([':txt' => $texto, ':ord' => (int)($input['orden'] ?? 0), ':id' => $pid]);
            $db->prepare("DELETE FROM opciones_pregunta WHERE pregunta_id=:pid")->execute([':pid' => $pid]);
        } else {
            $s = $db->prepare("INSERT INTO preguntas_examen (examen_id, texto, orden) VALUES (:eid,:txt,:ord) RETURNING id");
            $s->execute([':eid' => $eid, ':txt' => $texto, ':ord' => (int)($input['orden'] ?? 0)]);
            $pid = (int)$s->fetchColumn();
        }
        foreach (array_values($opciones) as $i => $opt) {
            $db->prepare("INSERT INTO opciones_pregunta (pregunta_id, texto, es_correcta, orden) VALUES (:pid,:txt,:cor,:ord)")
               ->execute([':pid' => $pid, ':txt' => trim($opt['texto']), ':cor' => !empty($opt['es_correcta']), ':ord' => $i]);
        }
        $db->commit();
        jsonResponse(['ok' => true, 'id' => $pid]);

    case 'pregunta_eliminar':
        $pid = (int)($input['id'] ?? 0);
        if (!$pid) jsonResponse(['error' => 'id requerido.'], 400);
        $db->prepare("DELETE FROM preguntas_examen WHERE id=:id")->execute([':id' => $pid]);
        jsonResponse(['ok' => true]);

    // ── EXÁMENES: RENDIR (ALUMNO) ─────────────────────────────────────────
    case 'examen_tomar':
        $mat_id = (int)($input['material_id'] ?? 0);
        if (!$mat_id) jsonResponse(['error' => 'material_id requerido.'], 400);
        $stmt = $db->prepare("SELECT id, titulo, puntaje_minimo FROM examenes WHERE material_id=:mid AND activo LIMIT 1");
        $stmt->execute([':mid' => $mat_id]);
        $examen = $stmt->fetch();
        if (!$examen) { jsonResponse(['ok' => true, 'examen' => null]); break; }

        $stmt2 = $db->prepare("SELECT id, texto, orden FROM preguntas_examen WHERE examen_id=:eid ORDER BY orden, id");
        $stmt2->execute([':eid' => $examen['id']]);
        $preguntas = $stmt2->fetchAll();
        foreach ($preguntas as &$pq) {
            // NO devuelve es_correcta al alumno
            $stmt3 = $db->prepare("SELECT id, texto, orden FROM opciones_pregunta WHERE pregunta_id=:pid ORDER BY orden, id");
            $stmt3->execute([':pid' => $pq['id']]);
            $pq['opciones'] = $stmt3->fetchAll();
        }
        $examen['preguntas'] = $preguntas;

        // Mejor intento del usuario actual
        $uid = getUsuarioActual()['id'];
        $mejor = null;
        if ($uid) {
            $sm = $db->prepare("SELECT puntaje, aprobado FROM intentos_examen WHERE examen_id=:eid AND usuario_id=:uid ORDER BY puntaje DESC NULLS LAST LIMIT 1");
            $sm->execute([':eid' => $examen['id'], ':uid' => $uid]);
            $mejor = $sm->fetch() ?: null;
        }
        jsonResponse(['ok' => true, 'examen' => $examen, 'mejor_intento' => $mejor]);

    case 'intento_guardar':
        $uid = getUsuarioActual()['id'];
        if (!$uid) jsonResponse(['error' => 'Sesión requerida.'], 401);
        $eid       = (int)($input['examen_id'] ?? 0);
        $respuestas = $input['respuestas'] ?? [];
        if (!$eid || empty($respuestas)) jsonResponse(['error' => 'Datos incompletos.'], 400);

        // Cargar respuestas correctas
        $sCorr = $db->prepare("
            SELECT p.id AS pregunta_id, o.id AS opcion_id
            FROM preguntas_examen p
            JOIN opciones_pregunta o ON o.pregunta_id = p.id AND o.es_correcta = TRUE
            WHERE p.examen_id = :eid");
        $sCorr->execute([':eid' => $eid]);
        $correctas = [];
        foreach ($sCorr->fetchAll() as $row) {
            $correctas[$row['pregunta_id']] = (int)$row['opcion_id'];
        }

        $total = count($correctas);
        $aciertos = 0;
        foreach ($respuestas as $r) {
            $pId = (int)$r['pregunta_id'];
            $oId = (int)$r['opcion_id'];
            if (isset($correctas[$pId]) && $correctas[$pId] === $oId) $aciertos++;
        }

        $puntaje = $total > 0 ? (int)round($aciertos / $total * 100) : 0;

        $sExamen = $db->prepare("SELECT puntaje_minimo, material_id FROM examenes WHERE id=:eid");
        $sExamen->execute([':eid' => $eid]);
        $examen = $sExamen->fetch();
        $aprobado = $puntaje >= $examen['puntaje_minimo'];

        $db->beginTransaction();
        $sIntento = $db->prepare("INSERT INTO intentos_examen (examen_id, usuario_id, puntaje, aprobado, fecha_fin) VALUES (:eid,:uid,:pts,:apr,NOW()) RETURNING id");
        $sIntento->execute([':eid' => $eid, ':uid' => $uid, ':pts' => $puntaje, ':apr' => $aprobado]);
        $intentoId = (int)$sIntento->fetchColumn();

        foreach ($respuestas as $r) {
            $db->prepare("INSERT INTO respuestas_intento (intento_id, pregunta_id, opcion_id) VALUES (:iid,:pid,:oid)")
               ->execute([':iid' => $intentoId, ':pid' => (int)$r['pregunta_id'], ':oid' => ($r['opcion_id'] ? (int)$r['opcion_id'] : null)]);
        }

        // Si aprobó: marcar progreso automáticamente
        if ($aprobado && $examen['material_id']) {
            $db->prepare("
                INSERT INTO progreso_capacitacion (usuario_id, material_id, completado, fecha_completado)
                VALUES (:uid, :mid, TRUE, NOW())
                ON CONFLICT (usuario_id, material_id)
                DO UPDATE SET completado=TRUE, fecha_completado=NOW()"
            )->execute([':uid' => $uid, ':mid' => $examen['material_id']]);
        }
        $db->commit();

        jsonResponse([
            'ok'           => true,
            'puntaje'      => $puntaje,
            'aprobado'     => $aprobado,
            'correctas'    => $aciertos,
            'total'        => $total,
            'correctas_map'=> $correctas,   // { pregunta_id: opcion_id_correcta }
        ]);

    default:
        jsonResponse(['error' => 'Acción no reconocida.'], 400);

}} catch (PDOException $e) {
    jsonResponse(['error' => 'Error BD: ' . $e->getMessage()], 500);
}

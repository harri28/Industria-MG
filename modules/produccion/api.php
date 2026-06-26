<?php
// modules/produccion/api.php
ini_set('display_errors', 0);
error_reporting(0);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/events.php';
header('Content-Type: application/json; charset=utf-8');

set_exception_handler(function(Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
});

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST)) {
    $input  = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? $action;
} else {
    $input = $_POST;
}

$db = getDB();

// ── LISTENERS DE EVENTOS ──────────────────────────────────────────────────
// Al crear un proyecto se generan automáticamente las áreas del workflow (desde la tabla areas).
listen('proyecto.creado', function($payload) use ($db) {
    $areas = $db->query("SELECT id, orden_workflow FROM areas WHERE activo = TRUE ORDER BY orden_workflow, nombre")->fetchAll();
    $stmtIns = $db->prepare("
        INSERT INTO proyecto_areas (proyecto_id, area_id, estado, orden)
        VALUES (:pid, :aid, 'pendiente', :orden)
        ON CONFLICT (proyecto_id, area_id) DO NOTHING
    ");
    foreach ($areas as $area) {
        $stmtIns->execute([
            ':pid'   => $payload['proyecto_id'],
            ':aid'   => $area['id'],
            ':orden' => $area['orden_workflow'],
        ]);
    }
});

try {
    switch ($action) {

        // ── LISTAR PROYECTOS ──────────────────────────────
        case 'listar':
            $where = ['p.activo = TRUE'];
            $params = [];
            if (!empty($_GET['buscar'])) {
                $where[] = "(p.codigo ILIKE :buscar OR p.nombre ILIKE :buscar OR c.razon_social ILIKE :buscar)";
                $params[':buscar'] = '%' . $_GET['buscar'] . '%';
            }
            if (!empty($_GET['estado'])) {
                $where[] = "p.estado = :estado";
                $params[':estado'] = $_GET['estado'];
            }
            if (!empty($_GET['prioridad'])) {
                $where[] = "p.prioridad = :prioridad";
                $params[':prioridad'] = $_GET['prioridad'];
            }
            $sql = "
                SELECT p.*,
                    c.razon_social  AS cliente_nombre,
                    u.nombre        AS responsable_nombre
                FROM proyectos p
                LEFT JOIN clientes  c ON c.id = p.cliente_id
                LEFT JOIN usuarios  u ON u.id = p.responsable_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY
                    CASE p.prioridad WHEN 'urgente' THEN 1 WHEN 'alta' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END,
                    p.fecha_entrega_estimada ASC NULLS LAST,
                    p.created_at DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            jsonResponse(['ok' => true, 'proyectos' => $stmt->fetchAll()]);

        // ── OBTENER UN PROYECTO ───────────────────────────
        case 'obtener':
            $stmt = $db->prepare("
                SELECT p.*,
                    c.razon_social AS cliente_nombre,
                    u.nombre       AS responsable_nombre
                FROM proyectos p
                LEFT JOIN clientes c ON c.id = p.cliente_id
                LEFT JOIN usuarios u ON u.id = p.responsable_id
                WHERE p.id = :id");
            $stmt->execute([':id' => (int)$_GET['id']]);
            $p = $stmt->fetch();
            if (!$p) jsonResponse(['error' => 'Proyecto no encontrado.'], 404);
            jsonResponse(['ok' => true, 'proyecto' => $p]);

        // ── CREAR ─────────────────────────────────────────
        case 'crear':
            $db->beginTransaction();
            $stmt = $db->prepare("
                INSERT INTO proyectos
                    (codigo, nombre, descripcion, cliente_id, estado, prioridad,
                     fecha_inicio, fecha_entrega_estimada, costo_estimado, porcentaje_avance,
                     responsable_id, observaciones)
                VALUES
                    (:codigo, :nombre, :descripcion, :cliente_id, :estado, :prioridad,
                     :fecha_inicio, :fecha_entrega_estimada, :costo_estimado, :porcentaje_avance,
                     :responsable_id, :observaciones)
                RETURNING id");
            $stmt->execute([
                ':codigo'                 => trim($input['codigo']                ?? ''),
                ':nombre'                 => trim($input['nombre']                ?? ''),
                ':descripcion'            => ($input['descripcion']               ?? null) ?: null,
                ':cliente_id'             => ($input['cliente_id']                ?? null) ?: null,
                ':estado'                 => $input['estado']                     ?? 'fabricacion',
                ':prioridad'              => $input['prioridad']                  ?? 'normal',
                ':fecha_inicio'           => ($input['fecha_inicio']              ?? null) ?: null,
                ':fecha_entrega_estimada' => ($input['fecha_entrega_estimada']    ?? null) ?: null,
                ':costo_estimado'         => (float)($input['costo_estimado']     ?? 0),
                ':porcentaje_avance'      => (int)($input['porcentaje_avance']    ?? 0),
                ':responsable_id'         => ($input['responsable_id']            ?? null) ?: null,
                ':observaciones'          => ($input['observaciones']             ?? null) ?: null,
            ]);
            $id = $stmt->fetchColumn();
            // Evento: inicializa las 5 áreas del workflow automáticamente
            dispatch('proyecto.creado', ['proyecto_id' => $id]);
            $db->commit();
            jsonResponse(['ok' => true, 'id' => $id]);

        // ── ACTUALIZAR ────────────────────────────────────
        case 'actualizar':
            $stmt = $db->prepare("
                UPDATE proyectos SET
                    codigo = :codigo, nombre = :nombre, descripcion = :descripcion,
                    cliente_id = :cliente_id, estado = :estado, prioridad = :prioridad,
                    fecha_inicio = :fecha_inicio, fecha_entrega_estimada = :fecha_entrega_estimada,
                    fecha_entrega_real = :fecha_entrega_real,
                    costo_estimado = :costo_estimado, porcentaje_avance = :porcentaje_avance,
                    responsable_id = :responsable_id, observaciones = :observaciones,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id");
            $stmt->execute([
                ':id'                     => (int)($input['id']                   ?? 0),
                ':codigo'                 => trim($input['codigo']                ?? ''),
                ':nombre'                 => trim($input['nombre']                ?? ''),
                ':descripcion'            => ($input['descripcion']               ?? null) ?: null,
                ':cliente_id'             => ($input['cliente_id']                ?? null) ?: null,
                ':estado'                 => $input['estado']                     ?? 'fabricacion',
                ':prioridad'              => $input['prioridad']                  ?? 'normal',
                ':fecha_inicio'           => ($input['fecha_inicio']              ?? null) ?: null,
                ':fecha_entrega_estimada' => ($input['fecha_entrega_estimada']    ?? null) ?: null,
                ':fecha_entrega_real'     => ($input['fecha_entrega_real']        ?? null) ?: null,
                ':costo_estimado'         => (float)($input['costo_estimado']     ?? 0),
                ':porcentaje_avance'      => (int)($input['porcentaje_avance']    ?? 0),
                ':responsable_id'         => ($input['responsable_id']            ?? null) ?: null,
                ':observaciones'          => ($input['observaciones']             ?? null) ?: null,
            ]);
            jsonResponse(['ok' => true]);

        // ── CAMBIAR ESTADO ────────────────────────────────
        case 'cambiar_estado':
            $allowed = ['fabricacion','pruebas','entrega','completado','cancelado'];
            if (!in_array($input['estado'], $allowed)) jsonResponse(['error' => 'Estado inválido.'], 400);
            $extra = '';
            if ($input['estado'] === 'completado') $extra = ", fecha_entrega_real = CURRENT_DATE";
            $stmt = $db->prepare("UPDATE proyectos SET estado = :estado{$extra}, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
            $stmt->execute([':estado' => $input['estado'], ':id' => (int)$input['id']]);
            jsonResponse(['ok' => true]);

        // ── DETALLE COMPLETO (para proyecto.php) ──────────
        case 'detalle':
            $id = (int)$_GET['id'];
            // Proyecto
            $stmt = $db->prepare("
                SELECT p.*, c.razon_social AS cliente_nombre, u.nombre AS responsable_nombre
                FROM proyectos p
                LEFT JOIN clientes c ON c.id = p.cliente_id
                LEFT JOIN usuarios u ON u.id = p.responsable_id
                WHERE p.id = :id");
            $stmt->execute([':id' => $id]);
            $proyecto = $stmt->fetch();
            if (!$proyecto) jsonResponse(['error' => 'No encontrado.'], 404);

            // BOM
            $stmt = $db->prepare("
                SELECT pb.*,
                    pr.codigo AS prod_codigo,
                    pr.nombre AS prod_nombre,
                    pr.precio_venta,
                    COALESCE(pr.unidad, pb.unidad_libre) AS unidad
                FROM proyecto_bom pb
                LEFT JOIN productos pr ON pr.id = pb.producto_id
                WHERE pb.proyecto_id = :id
                ORDER BY pb.orden, pb.id");
            $stmt->execute([':id' => $id]);
            $bom = $stmt->fetchAll();

            // Costos adicionales
            $stmt = $db->prepare("
                SELECT pc.*, u.nombre AS usuario_nombre
                FROM proyecto_costos pc
                LEFT JOIN usuarios u ON u.id = pc.usuario_id
                WHERE pc.proyecto_id = :id
                ORDER BY pc.fecha DESC");
            $stmt->execute([':id' => $id]);
            $costos = $stmt->fetchAll();

            // Avances
            $stmt = $db->prepare("
                SELECT pa.*, a.nombre AS area_nombre, u.nombre AS usuario_nombre
                FROM proyecto_avances pa
                LEFT JOIN areas    a ON a.id = pa.area_id
                LEFT JOIN usuarios u ON u.id = pa.usuario_id
                WHERE pa.proyecto_id = :id
                ORDER BY pa.fecha DESC");
            $stmt->execute([':id' => $id]);
            $avances = $stmt->fetchAll();

            // Piezas
            $stmt = $db->prepare("SELECT * FROM proyecto_piezas WHERE proyecto_id = :id ORDER BY orden, id");
            $stmt->execute([':id' => $id]);
            $piezas = array_map(function($p) {
                $p['propiedades_tecnicas'] = !empty($p['propiedades_tecnicas'])
                    ? json_decode($p['propiedades_tecnicas'], true)
                    : [];
                return $p;
            }, $stmt->fetchAll());

            // Partes
            $stmt = $db->prepare("
                SELECT pp.*
                FROM proyecto_partes pp
                JOIN proyecto_piezas pz ON pz.id = pp.pieza_id
                WHERE pz.proyecto_id = :id
                ORDER BY pp.pieza_id, pp.orden, pp.id");
            $stmt->execute([':id' => $id]);
            $partes = $stmt->fetchAll();

            // Áreas activas (para los dropdowns de asignación)
            $areas = $db->query("
                SELECT id, nombre, es_externa, orden_workflow
                FROM areas
                WHERE activo = TRUE
                ORDER BY orden_workflow, nombre")->fetchAll();

            jsonResponse(['ok' => true, 'proyecto' => $proyecto, 'piezas' => $piezas, 'partes' => $partes, 'bom' => $bom, 'costos' => $costos, 'avances' => $avances, 'areas' => $areas]);

        // ── BOM: AGREGAR ÍTEM ─────────────────────────────
        case 'bom_agregar':
            $stmt = $db->prepare("
                INSERT INTO proyecto_bom (proyecto_id, pieza_id, parte_id, producto_id, cantidad_planificada, costo_unitario_estimado, observaciones, medida, medida_unidad, pieza)
                VALUES (:pid, :pieza_id, :parte_id, :prod_id, :cantidad, :costo, :obs, :medida, :medida_unidad, :pieza)");
            $stmt->execute([
                ':pid'           => (int)$input['proyecto_id'],
                ':pieza_id'      => ($input['pieza_id'] ?? null) ?: null,
                ':parte_id'      => ($input['parte_id'] ?? null) ?: null,
                ':prod_id'       => (int)$input['producto_id'],
                ':cantidad'      => (int)$input['cantidad_planificada'],
                ':costo'         => (float)($input['costo_unitario_estimado'] ?? 0),
                ':obs'           => $input['observaciones'] ?? null,
                ':medida'        => isset($input['medida']) && $input['medida'] !== '' ? (int)$input['medida'] : null,
                ':medida_unidad' => $input['medida_unidad'] ?? 'cm',
                ':pieza'         => ($input['pieza']  ?? null) ?: null,
            ]);
            jsonResponse(['ok' => true]);

        // ── BOM: ACTUALIZAR REAL ──────────────────────────
        case 'bom_actualizar':
            $whitelist = ['cantidad_planificada','costo_unitario_estimado','cantidad_real','costo_unitario_real'];
            $campo = $input['campo'] ?? '';
            if (!in_array($campo, $whitelist)) jsonResponse(['error' => 'Campo no permitido.'], 400);
            $db->prepare("UPDATE proyecto_bom SET {$campo} = :v WHERE id = :id")
               ->execute([':v' => (float)$input['valor'], ':id' => (int)$input['id']]);
            $pid = (int)$input['proyecto_id'];
            // Recalcular costo estimado del proyecto
            $db->prepare("UPDATE proyectos SET costo_estimado = (
                    SELECT COALESCE(SUM(cantidad_planificada * costo_unitario_estimado),0)
                    FROM proyecto_bom WHERE proyecto_id = :pid
                ), updated_at = CURRENT_TIMESTAMP WHERE id = :pid")
               ->execute([':pid' => $pid]);
            // Recalcular costo real del proyecto
            $db->prepare("UPDATE proyectos SET costo_real = (
                    SELECT COALESCE(SUM(cantidad_real * costo_unitario_real),0)
                    FROM proyecto_bom WHERE proyecto_id = :pid
                ) + (
                    SELECT COALESCE(SUM(monto),0) FROM proyecto_costos WHERE proyecto_id = :pid
                ), updated_at = CURRENT_TIMESTAMP WHERE id = :pid")
               ->execute([':pid' => $pid]);
            jsonResponse(['ok' => true]);

        // ── BOM: ACTUALIZAR TEXTO ─────────────────────────
        case 'bom_actualizar_texto':
            $txt_whitelist = ['unidad', 'descripcion_libre', 'operacion', 'observaciones', 'pieza'];
            $int_whitelist = ['medida'];
            $campo = $input['campo'] ?? '';
            if (!in_array($campo, $txt_whitelist) && !in_array($campo, $int_whitelist))
                jsonResponse(['error' => 'Campo no permitido.'], 400);
            $val = in_array($campo, $int_whitelist)
                ? (isset($input['valor']) && $input['valor'] !== '' ? (int)$input['valor'] : null)
                : (($input['valor'] ?? null) ?: null);
            $db->prepare("UPDATE proyecto_bom SET {$campo} = :v WHERE id = :id")
               ->execute([':v' => $val, ':id' => (int)$input['id']]);
            jsonResponse(['ok' => true]);

        // ── PARTES ────────────────────────────────────────
        case 'parte_crear':
            $stmt = $db->prepare("INSERT INTO proyecto_partes (pieza_id, nombre) VALUES (:pieza_id, :nombre)");
            $stmt->execute([':pieza_id' => (int)$input['pieza_id'], ':nombre' => trim($input['nombre'] ?? '')]);
            jsonResponse(['ok' => true, 'id' => $db->lastInsertId()]);

        case 'parte_renombrar':
            $db->prepare("UPDATE proyecto_partes SET nombre = :nombre WHERE id = :id")
               ->execute([':nombre' => trim($input['nombre'] ?? ''), ':id' => (int)$input['id']]);
            jsonResponse(['ok' => true]);

        case 'parte_eliminar':
            $db->prepare("DELETE FROM proyecto_partes WHERE id = :id")->execute([':id' => (int)$input['id']]);
            jsonResponse(['ok' => true]);

        // ── PARTE: ASIGNAR ÁREA ──────────────────────────
        // El operador asigna manualmente a qué área va una parte. Sin asignación,
        // la parte permanece en estado_fabricacion = 'sin_asignar' y no aparece en
        // ninguna bandeja de área.
        case 'parte_asignar_area':
            $parteId = (int)($input['id'] ?? 0);
            $areaId  = !empty($input['area_id']) ? (int)$input['area_id'] : null;
            if (!$parteId)            jsonResponse(['error' => 'parte id requerido'], 400);
            if ($areaId === null)     jsonResponse(['error' => 'area_id requerido (use parte_quitar_area para limpiar)'], 400);

            // Solo se puede reasignar cuando aún no se ha iniciado el trabajo.
            $current = $db->prepare("SELECT estado_fabricacion FROM proyecto_partes WHERE id = :id");
            $current->execute([':id' => $parteId]);
            $row = $current->fetch();
            if (!$row) jsonResponse(['error' => 'Parte no encontrada'], 404);
            if (!in_array($row['estado_fabricacion'], ['sin_asignar', 'pendiente'])) {
                jsonResponse(['error' => 'No se puede cambiar el área: la parte ya está en proceso o completada.'], 409);
            }

            $db->prepare("
                UPDATE proyecto_partes
                SET area_id = :aid,
                    estado_fabricacion = 'pendiente'
                WHERE id = :id")
               ->execute([':aid' => $areaId, ':id' => $parteId]);
            jsonResponse(['ok' => true]);

        // ── PARTE: QUITAR ÁREA ───────────────────────────
        case 'parte_quitar_area':
            $parteId = (int)($input['id'] ?? 0);
            if (!$parteId) jsonResponse(['error' => 'parte id requerido'], 400);

            $current = $db->prepare("SELECT estado_fabricacion FROM proyecto_partes WHERE id = :id");
            $current->execute([':id' => $parteId]);
            $row = $current->fetch();
            if (!$row) jsonResponse(['error' => 'Parte no encontrada'], 404);
            if (!in_array($row['estado_fabricacion'], ['sin_asignar', 'pendiente'])) {
                jsonResponse(['error' => 'No se puede quitar el área: la parte ya está en proceso o completada.'], 409);
            }

            $db->prepare("
                UPDATE proyecto_partes
                SET area_id = NULL,
                    estado_fabricacion = 'sin_asignar'
                WHERE id = :id")
               ->execute([':id' => $parteId]);
            jsonResponse(['ok' => true]);

        // ── BANDEJA: RESUMEN POR ÁREA ────────────────────
        // Lista todas las áreas activas con contadores de partes pendientes/en proceso
        // y piezas en procesamiento.
        case 'bandeja_areas_resumen':
            $stmt = $db->query("
                SELECT a.id, a.nombre, a.es_externa, a.orden_workflow,
                       (SELECT COUNT(*) FROM proyecto_partes pp
                        JOIN proyecto_piezas pz ON pz.id = pp.pieza_id
                        JOIN proyectos       pr ON pr.id = pz.proyecto_id AND pr.activo = TRUE
                        WHERE pp.area_id = a.id AND pp.estado_fabricacion = 'pendiente')   AS pendientes,
                       (SELECT COUNT(*) FROM proyecto_partes pp
                        JOIN proyecto_piezas pz ON pz.id = pp.pieza_id
                        JOIN proyectos       pr ON pr.id = pz.proyecto_id AND pr.activo = TRUE
                        WHERE pp.area_id = a.id AND pp.estado_fabricacion = 'en_proceso')  AS en_proceso,
                       (SELECT COUNT(*) FROM proyecto_piezas pz
                        JOIN proyectos pr ON pr.id = pz.proyecto_id AND pr.activo = TRUE
                        WHERE pz.area_actual_id = a.id AND pz.estado = 'en_proceso')       AS procesamiento
                FROM areas a
                WHERE a.activo = TRUE
                ORDER BY a.orden_workflow, a.nombre");
            jsonResponse(['ok' => true, 'areas' => $stmt->fetchAll()]);

        // ── BANDEJA: PARTES DE UNA ÁREA ──────────────────
        // Lista partes pendientes y en proceso asignadas al área dada, con sus materiales.
        case 'bandeja_listar':
            $areaId = (int)($_GET['area_id'] ?? $input['area_id'] ?? 0);
            if (!$areaId) jsonResponse(['error' => 'area_id requerido'], 400);

            $area = $db->prepare("SELECT id, nombre, es_externa FROM areas WHERE id = :id");
            $area->execute([':id' => $areaId]);
            $areaInfo = $area->fetch();
            if (!$areaInfo) jsonResponse(['error' => 'Área no encontrada'], 404);

            $stmt = $db->prepare("
                SELECT pp.id, pp.nombre, pp.estado_fabricacion, pp.area_id,
                       pp.fecha_inicio, pp.fecha_completada, pp.orden,
                       pz.id     AS pieza_id,    pz.nombre AS pieza_nombre,
                       pr.id     AS proyecto_id, pr.codigo AS proy_codigo, pr.nombre AS proy_nombre,
                       pr.fecha_entrega_estimada
                FROM proyecto_partes pp
                JOIN proyecto_piezas pz ON pz.id = pp.pieza_id
                JOIN proyectos       pr ON pr.id = pz.proyecto_id
                WHERE pp.area_id              = :aid
                  AND pp.estado_fabricacion IN ('pendiente','en_proceso')
                  AND pr.activo               = TRUE
                ORDER BY
                    CASE pp.estado_fabricacion WHEN 'en_proceso' THEN 1 ELSE 2 END,
                    pr.fecha_entrega_estimada NULLS LAST,
                    pp.id");
            $stmt->execute([':aid' => $areaId]);
            $partes = $stmt->fetchAll();

            // Materiales de cada parte
            if ($partes) {
                $ids = array_column($partes, 'id');
                $place = implode(',', array_fill(0, count($ids), '?'));
                $stmtMat = $db->prepare("
                    SELECT b.id, b.parte_id, b.cantidad_planificada, b.cantidad_real,
                           b.costo_unitario_real, b.medida, b.medida_unidad,
                           b.operacion, b.descripcion_libre,
                           p.codigo AS prod_codigo, p.nombre AS prod_nombre
                    FROM proyecto_bom b
                    LEFT JOIN productos p ON p.id = b.producto_id
                    WHERE b.parte_id IN ($place)
                    ORDER BY b.parte_id, b.orden, b.id");
                $stmtMat->execute($ids);
                $matsByParte = [];
                foreach ($stmtMat->fetchAll() as $m) {
                    $matsByParte[$m['parte_id']][] = $m;
                }
                foreach ($partes as &$pp) {
                    $pp['materiales'] = $matsByParte[$pp['id']] ?? [];
                }
                unset($pp);
            }

            // Piezas físicas enviadas a esta área para procesamiento
            $stmtPz = $db->prepare("
                SELECT pz.id, pz.nombre, pz.estado, pz.area_actual_id,
                       pr.id     AS proyecto_id, pr.codigo AS proy_codigo, pr.nombre AS proy_nombre,
                       pr.fecha_entrega_estimada,
                       (SELECT MAX(t.fecha)
                        FROM transformaciones t
                        JOIN transformacion_outputs o ON o.transformacion_id = t.id
                        WHERE o.proyecto_pieza_id = pz.id) AS ultima_transformacion
                FROM proyecto_piezas pz
                JOIN proyectos pr ON pr.id = pz.proyecto_id
                WHERE pz.area_actual_id = :aid
                  AND pz.estado         = 'en_proceso'
                  AND pr.activo         = TRUE
                ORDER BY pr.fecha_entrega_estimada NULLS LAST, pz.id");
            $stmtPz->execute([':aid' => $areaId]);
            $piezasProcesamiento = $stmtPz->fetchAll();

            jsonResponse(['ok' => true, 'area' => $areaInfo, 'partes' => $partes, 'piezas_procesamiento' => $piezasProcesamiento]);

        // ── PARTE: INICIAR (responsable del área) ────────
        case 'parte_iniciar':
            $parteId = (int)($input['id'] ?? 0);
            if (!$parteId) jsonResponse(['error' => 'parte id requerido'], 400);

            $r = $db->prepare("SELECT estado_fabricacion, area_id FROM proyecto_partes WHERE id = :id");
            $r->execute([':id' => $parteId]);
            $row = $r->fetch();
            if (!$row)                                jsonResponse(['error' => 'Parte no encontrada'], 404);
            if (!$row['area_id'])                     jsonResponse(['error' => 'La parte no tiene área asignada.'], 409);
            if ($row['estado_fabricacion'] !== 'pendiente')
                jsonResponse(['error' => 'Solo se pueden iniciar partes en estado pendiente.'], 409);

            $db->prepare("
                UPDATE proyecto_partes
                SET estado_fabricacion = 'en_proceso',
                    fecha_inicio       = CURRENT_TIMESTAMP
                WHERE id = :id")
               ->execute([':id' => $parteId]);
            jsonResponse(['ok' => true]);

        // ── PARTE: COMPLETAR (responsable del área) ──────
        // Crea la transformación combinacion + registra materiales reales consumidos
        // + piezas físicas adicionales consumidas + genera la pieza resultante disponible.
        case 'parte_completar':
            $parteId = (int)($input['id'] ?? 0);
            if (!$parteId) jsonResponse(['error' => 'parte id requerido'], 400);

            $costo         = (float)($input['costo_proceso'] ?? 0);
            $observaciones = trim($input['observaciones'] ?? '') ?: null;
            $proveedor     = trim($input['proveedor']     ?? '') ?: null;
            $fechaEnvio    = !empty($input['fecha_envio_externo'])     ? $input['fecha_envio_externo']     : null;
            $fechaRecep    = !empty($input['fecha_recepcion_externo']) ? $input['fecha_recepcion_externo'] : null;

            // Cantidades reales por ítem BOM: [{bom_id, cantidad_real, costo_unitario_real}]
            $cantidadesReales = [];
            if (!empty($input['cantidades_reales'])) {
                $parsed = is_array($input['cantidades_reales'])
                    ? $input['cantidades_reales']
                    : (json_decode($input['cantidades_reales'], true) ?? []);
                foreach ($parsed as $item) {
                    $bomId = (int)($item['bom_id'] ?? 0);
                    if ($bomId > 0) {
                        $cantidadesReales[$bomId] = [
                            'cantidad_real'       => max(0, (float)($item['cantidad_real']       ?? 0)),
                            'costo_unitario_real' => max(0, (float)($item['costo_unitario_real'] ?? 0)),
                        ];
                    }
                }
            }

            // IDs de piezas físicas disponibles a consumir como inputs adicionales
            $piezaInputIds = [];
            if (!empty($input['pieza_inputs'])) {
                $parsed = is_array($input['pieza_inputs'])
                    ? $input['pieza_inputs']
                    : (json_decode($input['pieza_inputs'], true) ?? []);
                $piezaInputIds = array_values(array_filter(array_map('intval', $parsed)));
            }

            // Nombre opcional para la pieza resultante (default: parte.nombre)
            $nombrePieza = trim($input['nombre_pieza'] ?? '');

            $r = $db->prepare("
                SELECT pp.id, pp.nombre, pp.estado_fabricacion, pp.area_id, pp.pieza_id,
                       pz.proyecto_id, a.es_externa
                FROM proyecto_partes pp
                JOIN proyecto_piezas pz ON pz.id = pp.pieza_id
                LEFT JOIN areas a       ON a.id  = pp.area_id
                WHERE pp.id = :id");
            $r->execute([':id' => $parteId]);
            $parte = $r->fetch();
            if (!$parte)                                       jsonResponse(['error' => 'Parte no encontrada'], 404);
            if (!$parte['area_id'])                            jsonResponse(['error' => 'La parte no tiene área asignada.'], 409);
            if ($parte['estado_fabricacion'] !== 'en_proceso') jsonResponse(['error' => 'Solo se pueden completar partes en proceso.'], 409);

            $usuarioActual = (int)($input['usuario_id'] ?? 0) ?: null;
            $outputNombre  = $nombrePieza !== '' ? $nombrePieza : $parte['nombre'];

            try {
                $db->beginTransaction();

                // 1. Marcar la parte como completada
                $db->prepare("
                    UPDATE proyecto_partes
                    SET estado_fabricacion   = 'completada',
                        fecha_completada     = CURRENT_TIMESTAMP,
                        usuario_completo_id  = :uid
                    WHERE id = :id")
                   ->execute([':uid' => $usuarioActual, ':id' => $parteId]);

                // 2. Crear pieza física resultante (estado disponible)
                $stmtP = $db->prepare("
                    INSERT INTO proyecto_piezas (proyecto_id, nombre, estado, orden)
                    VALUES (:pid, :nom, 'disponible', 9999)
                    RETURNING id");
                $stmtP->execute([':pid' => $parte['proyecto_id'], ':nom' => $outputNombre]);
                $newPiezaId = (int)$stmtP->fetchColumn();

                // 3. Crear transformación (combinacion)
                $stmtT = $db->prepare("
                    INSERT INTO transformaciones
                        (proyecto_id, area_id, tipo, usuario_id, costo_proceso, observaciones,
                         fecha_envio_externo, fecha_recepcion_externo, proveedor)
                    VALUES (:pid, :aid, 'combinacion', :uid, :costo, :obs, :fenv, :frec, :prov)
                    RETURNING id");
                $stmtT->execute([
                    ':pid'   => $parte['proyecto_id'],
                    ':aid'   => $parte['area_id'],
                    ':uid'   => $usuarioActual,
                    ':costo' => $costo,
                    ':obs'   => $observaciones,
                    ':fenv'  => $parte['es_externa'] ? $fechaEnvio : null,
                    ':frec'  => $parte['es_externa'] ? $fechaRecep : null,
                    ':prov'  => $parte['es_externa'] ? $proveedor  : null,
                ]);
                $transId = (int)$stmtT->fetchColumn();

                // 4a. Inputs: materiales del BOM + actualizar cantidad/costo real
                $bomItems = $db->prepare("
                    SELECT id, cantidad_planificada, costo_unitario_estimado
                    FROM proyecto_bom WHERE parte_id = :pid");
                $bomItems->execute([':pid' => $parteId]);
                $stmtIn  = $db->prepare("
                    INSERT INTO transformacion_inputs (transformacion_id, proyecto_bom_id, cantidad)
                    VALUES (:tid, :bid, :cant)");
                $stmtUpd = $db->prepare("
                    UPDATE proyecto_bom
                    SET cantidad_real = :cr, costo_unitario_real = :cur
                    WHERE id = :id");
                foreach ($bomItems->fetchAll() as $b) {
                    $bomId    = (int)$b['id'];
                    $realData = $cantidadesReales[$bomId] ?? null;
                    $cantReal  = $realData
                        ? $realData['cantidad_real']
                        : (float)($b['cantidad_planificada'] ?: 1);
                    $costoReal = $realData
                        ? $realData['costo_unitario_real']
                        : (float)($b['costo_unitario_estimado'] ?? 0);

                    $stmtUpd->execute([':cr' => $cantReal, ':cur' => $costoReal, ':id' => $bomId]);
                    $stmtIn->execute([':tid' => $transId, ':bid' => $bomId, ':cant' => $cantReal]);
                }

                // 4b. Inputs: piezas físicas adicionales (validadas: mismo proyecto, estado disponible)
                if ($piezaInputIds) {
                    $placeholders = implode(',', array_fill(0, count($piezaInputIds), '?'));
                    $stmtChk = $db->prepare("
                        SELECT id FROM proyecto_piezas
                        WHERE id IN ($placeholders) AND estado = 'disponible' AND proyecto_id = ?");
                    $stmtChk->execute([...$piezaInputIds, $parte['proyecto_id']]);
                    $validIds = array_column($stmtChk->fetchAll(), 'id');

                    $stmtInPz  = $db->prepare("
                        INSERT INTO transformacion_inputs (transformacion_id, proyecto_pieza_id, cantidad)
                        VALUES (:tid, :pzid, 1)");
                    $stmtConsm = $db->prepare("
                        UPDATE proyecto_piezas SET estado = 'consumida' WHERE id = :id");
                    foreach ($validIds as $pzId) {
                        $stmtInPz->execute([':tid' => $transId, ':pzid' => $pzId]);
                        $stmtConsm->execute([':id' => $pzId]);
                    }
                }

                // 5. Output: la pieza recién creada
                $db->prepare("
                    INSERT INTO transformacion_outputs (transformacion_id, proyecto_pieza_id, cantidad, nombre)
                    VALUES (:tid, :pzid, 1, :nom)")
                   ->execute([':tid' => $transId, ':pzid' => $newPiezaId, ':nom' => $outputNombre]);

                // 6. Recalcular costo_real del proyecto (BOM real + costos extra)
                $db->prepare("
                    UPDATE proyectos SET
                        costo_real = (
                            SELECT COALESCE(SUM(cantidad_real * costo_unitario_real), 0)
                            FROM proyecto_bom WHERE proyecto_id = :pid
                        ) + (
                            SELECT COALESCE(SUM(monto), 0)
                            FROM proyecto_costos WHERE proyecto_id = :pid
                        ),
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :pid")
                   ->execute([':pid' => $parte['proyecto_id']]);

                $db->commit();
                jsonResponse(['ok' => true, 'transformacion_id' => $transId, 'pieza_resultante_id' => $newPiezaId]);
            } catch (Exception $e) {
                $db->rollBack();
                jsonResponse(['error' => 'Error al completar: ' . $e->getMessage()], 500);
            }

        // ── PIEZA: ENVIAR A ÁREA (procesamiento) ─────────
        // Una pieza física disponible se envía a un área para ser procesada (Pintura, etc.).
        case 'pieza_enviar_area':
            $piezaId = (int)($input['id'] ?? 0);
            $areaId  = (int)($input['area_id'] ?? 0);
            if (!$piezaId || !$areaId) jsonResponse(['error' => 'id y area_id requeridos'], 400);

            $r = $db->prepare("SELECT estado FROM proyecto_piezas WHERE id = :id");
            $r->execute([':id' => $piezaId]);
            $row = $r->fetch();
            if (!$row)                       jsonResponse(['error' => 'Pieza no encontrada'], 404);
            if ($row['estado'] !== 'disponible')
                jsonResponse(['error' => 'Solo piezas en estado "disponible" pueden enviarse a un área.'], 409);

            $db->prepare("
                UPDATE proyecto_piezas
                SET estado = 'en_proceso', area_actual_id = :aid
                WHERE id = :id")
               ->execute([':aid' => $areaId, ':id' => $piezaId]);
            jsonResponse(['ok' => true]);

        // ── PIEZA: CANCELAR ENVÍO ────────────────────────
        // Devuelve una pieza enviada al área (sin procesarla) a estado disponible.
        case 'pieza_cancelar_envio':
            $piezaId = (int)($input['id'] ?? 0);
            if (!$piezaId) jsonResponse(['error' => 'id requerido'], 400);

            $r = $db->prepare("SELECT estado FROM proyecto_piezas WHERE id = :id");
            $r->execute([':id' => $piezaId]);
            $row = $r->fetch();
            if (!$row)                          jsonResponse(['error' => 'Pieza no encontrada'], 404);
            if ($row['estado'] !== 'en_proceso')
                jsonResponse(['error' => 'Solo piezas en proceso pueden cancelarse.'], 409);

            $db->prepare("
                UPDATE proyecto_piezas
                SET estado = 'disponible', area_actual_id = NULL
                WHERE id = :id")
               ->execute([':id' => $piezaId]);
            jsonResponse(['ok' => true]);

        // ── PIEZA: COMPLETAR PROCESAMIENTO ───────────────
        // El responsable del área marca como hecho un procesamiento (Pintura, etc.).
        // La identidad de la pieza persiste; solo se le agrega costo y se la devuelve disponible.
        case 'pieza_completar_procesamiento':
            $piezaId = (int)($input['id'] ?? 0);
            if (!$piezaId) jsonResponse(['error' => 'id requerido'], 400);

            $costo         = (float)($input['costo_proceso'] ?? 0);
            $observaciones = trim($input['observaciones'] ?? '') ?: null;
            $proveedor     = trim($input['proveedor']     ?? '') ?: null;
            $fechaEnvio    = !empty($input['fecha_envio_externo'])     ? $input['fecha_envio_externo']     : null;
            $fechaRecep    = !empty($input['fecha_recepcion_externo']) ? $input['fecha_recepcion_externo'] : null;

            $r = $db->prepare("
                SELECT pz.id, pz.proyecto_id, pz.area_actual_id, pz.estado, a.es_externa
                FROM proyecto_piezas pz
                LEFT JOIN areas a ON a.id = pz.area_actual_id
                WHERE pz.id = :id");
            $r->execute([':id' => $piezaId]);
            $pz = $r->fetch();
            if (!$pz)                              jsonResponse(['error' => 'Pieza no encontrada'], 404);
            if ($pz['estado'] !== 'en_proceso')    jsonResponse(['error' => 'La pieza no está en proceso.'], 409);
            if (!$pz['area_actual_id'])            jsonResponse(['error' => 'La pieza no tiene área asignada.'], 409);

            $usuarioActual = (int)($input['usuario_id'] ?? 0) ?: null;

            try {
                $db->beginTransaction();

                // 1. Crear transformación procesamiento
                $stmtT = $db->prepare("
                    INSERT INTO transformaciones
                        (proyecto_id, area_id, tipo, usuario_id, costo_proceso, observaciones,
                         fecha_envio_externo, fecha_recepcion_externo, proveedor)
                    VALUES (:pid, :aid, 'procesamiento', :uid, :costo, :obs, :fenv, :frec, :prov)
                    RETURNING id");
                $stmtT->execute([
                    ':pid'   => $pz['proyecto_id'],
                    ':aid'   => $pz['area_actual_id'],
                    ':uid'   => $usuarioActual,
                    ':costo' => $costo,
                    ':obs'   => $observaciones,
                    ':fenv'  => $pz['es_externa'] ? $fechaEnvio : null,
                    ':frec'  => $pz['es_externa'] ? $fechaRecep : null,
                    ':prov'  => $pz['es_externa'] ? $proveedor  : null,
                ]);
                $transId = (int)$stmtT->fetchColumn();

                // 2. Input: la misma pieza (consumo lógico para trazabilidad)
                $db->prepare("
                    INSERT INTO transformacion_inputs (transformacion_id, proyecto_pieza_id, cantidad)
                    VALUES (:tid, :pzid, 1)")
                   ->execute([':tid' => $transId, ':pzid' => $piezaId]);

                // 3. Output: la misma pieza (refinada)
                $db->prepare("
                    INSERT INTO transformacion_outputs (transformacion_id, proyecto_pieza_id, cantidad)
                    VALUES (:tid, :pzid, 1)")
                   ->execute([':tid' => $transId, ':pzid' => $piezaId]);

                // 4. Pieza vuelve a disponible
                $db->prepare("
                    UPDATE proyecto_piezas
                    SET estado = 'disponible', area_actual_id = NULL
                    WHERE id = :id")
                   ->execute([':id' => $piezaId]);

                $db->commit();
                jsonResponse(['ok' => true, 'transformacion_id' => $transId]);
            } catch (Exception $e) {
                $db->rollBack();
                jsonResponse(['error' => 'Error: ' . $e->getMessage()], 500);
            }

        // ── PIEZA: FINALIZAR ─────────────────────────────
        // El operador decide el destino de una pieza disponible:
        //   - 'proyecto': se integra a la máquina (estado=integrada)
        //   - 'stock'   : pasa a inventario, genera entrada en kardex y actualiza precio_promedio
        case 'pieza_finalizar':
            $piezaId = (int)($input['id'] ?? 0);
            $destino = $input['destino'] ?? '';
            if (!$piezaId)                                          jsonResponse(['error' => 'id requerido'], 400);
            if (!in_array($destino, ['proyecto', 'stock']))         jsonResponse(['error' => 'destino debe ser "proyecto" o "stock"'], 400);

            $r = $db->prepare("SELECT id, nombre, estado FROM proyecto_piezas WHERE id = :id");
            $r->execute([':id' => $piezaId]);
            $pz = $r->fetch();
            if (!$pz)                          jsonResponse(['error' => 'Pieza no encontrada'], 404);
            if ($pz['estado'] !== 'disponible') jsonResponse(['error' => 'Solo piezas disponibles pueden finalizarse.'], 409);

            // ── Destino: integrada a proyecto ──
            if ($destino === 'proyecto') {
                $db->prepare("UPDATE proyecto_piezas SET estado = 'integrada' WHERE id = :id")
                   ->execute([':id' => $piezaId]);
                jsonResponse(['ok' => true, 'destino' => 'proyecto']);
            }

            // ── Destino: stock ──
            $productoId    = (int)($input['producto_id'] ?? 0) ?: null;
            $nuevoNombre   = trim($input['nuevo_producto_nombre'] ?? '');
            $costoUnitario = (float)($input['costo_unitario'] ?? 0);
            $cantidad      = (float)($input['cantidad'] ?? 1);
            $observaciones = trim($input['observaciones'] ?? '') ?: null;
            $usuarioActual = (int)($input['usuario_id'] ?? 0) ?: null;

            if ($cantidad <= 0)      jsonResponse(['error' => 'Cantidad debe ser positiva.'], 400);
            if ($costoUnitario < 0)  jsonResponse(['error' => 'Costo unitario inválido.'], 400);

            try {
                $db->beginTransaction();

                // Resolver producto: usar existente o crear nuevo
                if (!$productoId) {
                    if ($nuevoNombre === '') jsonResponse(['error' => 'Debe seleccionar un producto o ingresar nombre nuevo.'], 400);
                    $codigo = generarCodigo('PRD', 'productos', 'codigo');
                    $stmtNew = $db->prepare("
                        INSERT INTO productos (codigo, nombre, unidad, stock_actual, precio_promedio)
                        VALUES (:cod, :nom, 'unidad', 0, 0)
                        RETURNING id");
                    $stmtNew->execute([':cod' => $codigo, ':nom' => $nuevoNombre]);
                    $productoId = (int)$stmtNew->fetchColumn();
                }

                // Bloquear y leer estado actual del producto
                $stmtP = $db->prepare("SELECT stock_actual, precio_promedio FROM productos WHERE id = :id FOR UPDATE");
                $stmtP->execute([':id' => $productoId]);
                $prod = $stmtP->fetch();
                if (!$prod) jsonResponse(['error' => 'Producto no encontrado.'], 404);

                $stockAnt = (float)$prod['stock_actual'];
                $ppAnt    = (float)$prod['precio_promedio'];
                $nuevoStock = $stockAnt + $cantidad;
                $nuevoPp    = $nuevoStock > 0
                    ? (($stockAnt * $ppAnt) + ($cantidad * $costoUnitario)) / $nuevoStock
                    : $costoUnitario;

                // Actualizar producto
                $db->prepare("
                    UPDATE productos
                    SET stock_actual = :sa, precio_promedio = :pp, updated_at = NOW()
                    WHERE id = :id")
                   ->execute([':sa' => $nuevoStock, ':pp' => $nuevoPp, ':id' => $productoId]);

                // Obtener proyecto_id de la pieza
                $stmtPid = $db->prepare("SELECT proyecto_id FROM proyecto_piezas WHERE id = :id");
                $stmtPid->execute([':id' => $piezaId]);
                $proyectoIdPieza = (int)$stmtPid->fetchColumn();

                // Insertar kardex (entrada)
                $stmtK = $db->prepare("
                    INSERT INTO kardex
                        (producto_id, tipo, referencia_tipo, referencia_id, proyecto_id,
                         cantidad, precio_unitario, saldo_cantidad, saldo_valor,
                         usuario_id, observaciones)
                    VALUES (:pid, 'entrada', 'pieza_proyecto', :ref, :proy,
                            :cant, :pu, :sq, :sv, :uid, :obs)
                    RETURNING id");
                $stmtK->execute([
                    ':pid'  => $productoId,
                    ':ref'  => $piezaId,
                    ':proy' => $proyectoIdPieza,
                    ':cant' => $cantidad,
                    ':pu'   => $costoUnitario,
                    ':sq'   => $nuevoStock,
                    ':sv'   => $nuevoStock * $nuevoPp,
                    ':uid'  => $usuarioActual,
                    ':obs'  => $observaciones,
                ]);
                $kardexId = (int)$stmtK->fetchColumn();

                // Actualizar pieza
                $db->prepare("UPDATE proyecto_piezas SET estado = 'stock' WHERE id = :id")
                   ->execute([':id' => $piezaId]);

                $db->commit();
                jsonResponse(['ok' => true, 'destino' => 'stock', 'producto_id' => $productoId, 'kardex_id' => $kardexId]);
            } catch (Exception $e) {
                $db->rollBack();
                jsonResponse(['error' => 'Error al finalizar: ' . $e->getMessage()], 500);
            }

        // ── PIEZAS FÍSICAS DEL PROYECTO ──────────────────
        // Lista todas las piezas físicas (no planificadas) generadas durante la fabricación,
        // con su estado y la última transformación que las afectó.
        case 'piezas_producidas':
            $proyectoId = (int)($_GET['proyecto_id'] ?? $input['proyecto_id'] ?? 0);
            if (!$proyectoId) jsonResponse(['error' => 'proyecto_id requerido'], 400);

            $stmt = $db->prepare("
                SELECT pz.id, pz.nombre, pz.estado, pz.area_actual_id, pz.created_at,
                       a.nombre AS area_actual_nombre, a.es_externa AS area_actual_externa,
                       (SELECT MAX(t.fecha)
                        FROM transformaciones t
                        JOIN transformacion_outputs o ON o.transformacion_id = t.id
                        WHERE o.proyecto_pieza_id = pz.id) AS ultima_transformacion
                FROM proyecto_piezas pz
                LEFT JOIN areas a ON a.id = pz.area_actual_id
                WHERE pz.proyecto_id = :pid
                  AND pz.estado IN ('en_proceso','disponible','consumida','integrada','stock')
                ORDER BY
                    CASE pz.estado
                        WHEN 'en_proceso' THEN 1
                        WHEN 'disponible' THEN 2
                        WHEN 'integrada'  THEN 3
                        WHEN 'stock'      THEN 4
                        ELSE 5
                    END,
                    pz.created_at DESC");
            $stmt->execute([':pid' => $proyectoId]);
            jsonResponse(['ok' => true, 'piezas' => $stmt->fetchAll()]);

        // ── BOM: ELIMINAR ─────────────────────────────────
        case 'bom_eliminar':
            $db->prepare("DELETE FROM proyecto_bom WHERE id = :id")->execute([':id' => (int)$input['id']]);
            jsonResponse(['ok' => true]);

        // ── COSTO ADICIONAL: AGREGAR ──────────────────────
        case 'costo_agregar':
            $stmt = $db->prepare("
                INSERT INTO proyecto_costos (proyecto_id, tipo, descripcion, monto, fecha)
                VALUES (:pid, :tipo, :desc, :monto, :fecha)");
            $stmt->execute([
                ':pid'   => (int)$input['proyecto_id'],
                ':tipo'  => $input['tipo']         ?? 'otros',
                ':desc'  => $input['descripcion']  ?? '',
                ':monto' => (float)$input['monto'],
                ':fecha' => $input['fecha']         ?: date('Y-m-d'),
            ]);
            // Recalcular costo real
            $db->prepare("
                UPDATE proyectos SET
                    costo_real = (
                        SELECT COALESCE(SUM(cantidad_real * costo_unitario_real), 0)
                        FROM proyecto_bom WHERE proyecto_id = :pid
                    ) + (
                        SELECT COALESCE(SUM(monto), 0)
                        FROM proyecto_costos WHERE proyecto_id = :pid
                    ),
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :pid")
                ->execute([':pid' => (int)$input['proyecto_id']]);
            jsonResponse(['ok' => true]);

        // ── AVANCE: REGISTRAR ─────────────────────────────
        case 'avance_registrar':
            $db->beginTransaction();
            $stmt = $db->prepare("
                INSERT INTO proyecto_avances (proyecto_id, area_id, usuario_id, descripcion, porcentaje)
                VALUES (:pid, :area, :uid, :desc, :pct)");
            $stmt->execute([
                ':pid'  => (int)$input['proyecto_id'],
                ':area' => $input['area_id']      ?: null,
                ':uid'  => $input['usuario_id']   ?: null,
                ':desc' => $input['descripcion'],
                ':pct'  => (int)$input['porcentaje'],
            ]);
            // Actualizar % del proyecto si es mayor al actual
            $db->prepare("
                UPDATE proyectos SET porcentaje_avance = :pct, updated_at = CURRENT_TIMESTAMP
                WHERE id = :pid AND porcentaje_avance < :pct")
                ->execute([':pct' => (int)$input['porcentaje'], ':pid' => (int)$input['proyecto_id']]);
            $db->commit();
            jsonResponse(['ok' => true]);

        // ── BUSCAR PRODUCTOS (para BOM) ───────────────────
        case 'buscar_productos':
            $stmt = $db->prepare("
                SELECT id, codigo, nombre, unidad, precio_promedio
                FROM productos
                WHERE activo AND (codigo ILIKE :q OR nombre ILIKE :q)
                LIMIT 20");
            $stmt->execute([':q' => '%' . $_GET['q'] . '%']);
            jsonResponse(['ok' => true, 'productos' => $stmt->fetchAll()]);

        // ── SIGUIENTE CÓDIGO ──────────────────────────────
        case 'siguiente_codigo':
            $codigo = generarCodigo('PRY-', 'proyectos', 'codigo');
            jsonResponse(['ok' => true, 'codigo' => $codigo]);

        // ── BOM: LISTAR PLANTILLAS ────────────────────────
        case 'plantillas_listar':
            $stmt = $db->query("
                SELECT p.id, p.codigo, p.nombre, p.descripcion,
                    COUNT(i.id) AS total_items
                FROM bom_plantillas p
                LEFT JOIN bom_plantilla_items i ON i.plantilla_id = p.id
                WHERE p.activo
                GROUP BY p.id
                ORDER BY p.nombre");
            jsonResponse(['ok' => true, 'plantillas' => $stmt->fetchAll()]);

        // ── BOM: DETALLE DE PLANTILLA (ítems) ─────────────
        case 'plantilla_detalle':
            $pid = (int)$_GET['id'];
            $stmt = $db->prepare("
                SELECT bi.id, bi.plantilla_id, bi.seccion, bi.parte, bi.producto_id,
                       bi.descripcion_material, bi.dimension, bi.unidad, bi.cantidad_por_unidad,
                       bi.operacion, bi.orden,
                       pr.codigo AS prod_codigo, pr.nombre AS prod_nombre,
                       pr.precio_promedio
                FROM bom_plantilla_items bi
                LEFT JOIN productos pr ON pr.id = bi.producto_id
                WHERE bi.plantilla_id = :pid
                ORDER BY bi.seccion NULLS LAST, bi.parte NULLS LAST, bi.orden, bi.id");
            $stmt->execute([':pid' => $pid]);
            jsonResponse(['ok' => true, 'items' => $stmt->fetchAll()]);

        // ── BOM: GUARDAR PLANTILLA (crear / editar) ────────
        case 'plantilla_guardar':
            $nombre = trim($input['nombre'] ?? '');
            if (!$nombre) jsonResponse(['error' => 'El nombre es requerido.'], 400);
            $desc = trim($input['descripcion'] ?? '');
            $id   = (int)($input['id'] ?? 0) ?: null;
            if ($id) {
                $db->prepare("UPDATE bom_plantillas SET nombre=:n, descripcion=:d WHERE id=:id")
                   ->execute([':n' => $nombre, ':d' => $desc, ':id' => $id]);
            } else {
                $codigo = generarCodigo('FRM-', 'bom_plantillas', 'codigo');
                $stmt = $db->prepare("INSERT INTO bom_plantillas (codigo, nombre, descripcion) VALUES (:c,:n,:d) RETURNING id");
                $stmt->execute([':c' => $codigo, ':n' => $nombre, ':d' => $desc]);
                $id = (int)$stmt->fetchColumn();
            }
            jsonResponse(['ok' => true, 'id' => $id]);

        // ── BOM: ELIMINAR PLANTILLA ────────────────────────
        case 'plantilla_eliminar':
            $id = (int)($input['id'] ?? 0);
            $db->prepare("UPDATE bom_plantillas SET activo=FALSE WHERE id=:id")->execute([':id' => $id]);
            jsonResponse(['ok' => true]);

        // ── BOM: GUARDAR ÍTEM DE PLANTILLA ────────────────
        case 'plantilla_item_guardar':
            $pltId  = (int)($input['plantilla_id'] ?? 0);
            $itemId = (int)($input['id'] ?? 0) ?: null;
            $mat    = trim($input['descripcion_material'] ?? '');
            if (!$pltId || !$mat) jsonResponse(['error' => 'Datos incompletos.'], 400);
            $prodId = (int)($input['producto_id'] ?? 0) ?: null;
            $unidad = trim($input['unidad'] ?? 'unidad');
            $cant   = max(0.001, (float)($input['cantidad_por_unidad'] ?? 1));
            $sec    = trim($input['seccion']   ?? '') ?: null;
            $parte  = trim($input['parte']     ?? '') ?: null;
            $dim    = trim($input['dimension'] ?? '') ?: null;
            $op     = trim($input['operacion'] ?? '') ?: null;
            if ($itemId) {
                $db->prepare("UPDATE bom_plantilla_items
                    SET seccion=:sec, parte=:parte, producto_id=:prod, descripcion_material=:mat,
                        dimension=:dim, unidad=:uni, cantidad_por_unidad=:cant, operacion=:op
                    WHERE id=:id")
                   ->execute([':sec'=>$sec,':parte'=>$parte,':prod'=>$prodId,':mat'=>$mat,
                              ':dim'=>$dim,':uni'=>$unidad,':cant'=>$cant,':op'=>$op,':id'=>$itemId]);
            } else {
                $oStmt = $db->prepare("SELECT COALESCE(MAX(orden),0)+1 FROM bom_plantilla_items WHERE plantilla_id=:pid");
                $oStmt->execute([':pid' => $pltId]);
                $orden = (int)$oStmt->fetchColumn();
                $db->prepare("INSERT INTO bom_plantilla_items
                    (plantilla_id, seccion, parte, producto_id, descripcion_material,
                     dimension, unidad, cantidad_por_unidad, operacion, orden)
                    VALUES (:pid,:sec,:parte,:prod,:mat,:dim,:uni,:cant,:op,:ord)")
                   ->execute([':pid'=>$pltId,':sec'=>$sec,':parte'=>$parte,':prod'=>$prodId,
                              ':mat'=>$mat,':dim'=>$dim,':uni'=>$unidad,':cant'=>$cant,
                              ':op'=>$op,':ord'=>$orden]);
            }
            jsonResponse(['ok' => true]);

        // ── BOM: ELIMINAR ÍTEM DE PLANTILLA ───────────────
        case 'plantilla_item_eliminar':
            $id = (int)($input['id'] ?? 0);
            $db->prepare("DELETE FROM bom_plantilla_items WHERE id=:id")->execute([':id' => $id]);
            jsonResponse(['ok' => true]);

        // ── BOM: CONFIGURAR / RENOMBRAR PIEZA EN PLANTILLA ─
        case 'plantilla_cfg_pieza':
            $pltId  = (int)($input['plantilla_id'] ?? 0);
            $viejo  = trim($input['seccion_viejo'] ?? '');
            $nuevo  = trim($input['seccion_nuevo'] ?? '');
            if (!$pltId || !$nuevo) jsonResponse(['error' => 'Datos incompletos.'], 400);
            // Renombrar sección en todos los ítems
            $db->prepare("UPDATE bom_plantilla_items SET seccion=:nuevo WHERE plantilla_id=:pid AND seccion=:viejo")
               ->execute([':nuevo' => $nuevo, ':pid' => $pltId, ':viejo' => $viejo]);
            // Guardar propiedades técnicas en la plantilla (JSON en columna notas_tecnicas si existe)
            if (!empty($input['propiedades'])) {
                $props = json_encode($input['propiedades']);
                // Intentar guardar; si la columna no existe lo ignoramos silenciosamente
                try {
                    $db->prepare("UPDATE bom_plantillas SET notas_tecnicas = COALESCE(notas_tecnicas::jsonb, '{}'::jsonb) || :patch::jsonb WHERE id=:pid")
                       ->execute([':patch' => json_encode([$nuevo => $input['propiedades']]), ':pid' => $pltId]);
                } catch (\Exception $e) { /* columna no existe aún */ }
            }
            jsonResponse(['ok' => true]);

        // ── BOM: ELIMINAR PIEZA (sección) DE PLANTILLA ────
        case 'plantilla_eliminar_pieza':
            $pltId  = (int)($input['plantilla_id'] ?? 0);
            $seccion = trim($input['seccion'] ?? '');
            if (!$pltId || !$seccion) jsonResponse(['error' => 'Datos incompletos.'], 400);
            $db->prepare("DELETE FROM bom_plantilla_items WHERE plantilla_id=:pid AND seccion=:sec")
               ->execute([':pid' => $pltId, ':sec' => $seccion]);
            jsonResponse(['ok' => true]);

        // ── BOM: CARGAR DESDE PLANTILLA ───────────────────
        case 'bom_cargar_plantilla':
            $plantilla_id = (int)$input['plantilla_id'];
            $proyecto_id  = (int)$input['proyecto_id'];
            $cantidad_maq = max(1, (int)($input['cantidad_maquinas'] ?? 1));

            $stmt = $db->prepare("
                SELECT bi.*, pr.precio_promedio
                FROM bom_plantilla_items bi
                LEFT JOIN productos pr ON pr.id = bi.producto_id
                WHERE bi.plantilla_id = :pid
                ORDER BY bi.orden");
            $stmt->execute([':pid' => $plantilla_id]);
            $items = $stmt->fetchAll();
            if (!$items) jsonResponse(['error' => 'Plantilla sin ítems.'], 404);

            $db->beginTransaction();

            // Crear una pieza por cada sección única de la plantilla
            $piezaIds  = [];
            $ordenPieza = 0;
            // 1. Crear proyecto_piezas por sección única
            $piezaIds   = [];
            $ordenPieza = 0;
            $stmtPieza  = $db->prepare("
                INSERT INTO proyecto_piezas (proyecto_id, nombre, orden)
                VALUES (:pid, :nombre, :orden) RETURNING id");
            foreach ($items as $item) {
                $sec = $item['seccion'] ?? null;
                if ($sec && !isset($piezaIds[$sec])) {
                    $stmtPieza->execute([':pid' => $proyecto_id, ':nombre' => $sec, ':orden' => $ordenPieza]);
                    $piezaIds[$sec] = (int)$stmtPieza->fetchColumn();
                    $ordenPieza++;
                }
            }

            // 2. Crear proyecto_partes por combinación única (seccion, parte)
            $parteIds   = []; // key: "seccion|parte"
            $ordenParte = 0;
            $stmtParte  = $db->prepare("
                INSERT INTO proyecto_partes (pieza_id, nombre, orden)
                VALUES (:pieza_id, :nombre, :orden) RETURNING id");
            foreach ($items as $item) {
                $sec   = $item['seccion'] ?? null;
                $parte = $item['parte']   ?? null;
                if ($sec && $parte) {
                    $key = $sec . '|' . $parte;
                    if (!isset($parteIds[$key]) && isset($piezaIds[$sec])) {
                        $stmtParte->execute([
                            ':pieza_id' => $piezaIds[$sec],
                            ':nombre'   => $parte,
                            ':orden'    => $ordenParte,
                        ]);
                        $parteIds[$key] = (int)$stmtParte->fetchColumn();
                        $ordenParte++;
                    }
                }
            }

            // 3. Insertar BOM con pieza_id y parte_id
            $ins = $db->prepare("
                INSERT INTO proyecto_bom
                    (proyecto_id, pieza_id, parte_id, producto_id, descripcion_libre, unidad_libre,
                     cantidad_planificada, costo_unitario_estimado, seccion, operacion, orden)
                VALUES
                    (:pid, :pieza_id, :parte_id, :prod_id, :desc_libre, :unidad,
                     :cantidad, :costo, :seccion, :operacion, :orden)");
            foreach ($items as $item) {
                $sec   = $item['seccion'] ?? null;
                $parte = $item['parte']   ?? null;
                $key   = $sec && $parte ? $sec . '|' . $parte : null;
                $ins->execute([
                    ':pid'        => $proyecto_id,
                    ':pieza_id'   => $sec ? ($piezaIds[$sec] ?? null) : null,
                    ':parte_id'   => $key ? ($parteIds[$key] ?? null) : null,
                    ':prod_id'    => $item['producto_id'] ?: null,
                    ':desc_libre' => $item['descripcion_material'],
                    ':unidad'     => $item['unidad'],
                    ':cantidad'   => round($item['cantidad_por_unidad'] * $cantidad_maq, 3),
                    ':costo'      => $item['precio_promedio'] ?? 0,
                    ':seccion'    => $sec,
                    ':operacion'  => $item['operacion'],
                    ':orden'      => (int)$item['orden'],
                ]);
            }
            $db->commit();
            jsonResponse(['ok' => true, 'cargados' => count($items)]);

        // ── ÁREA: DETALLE COMPLETO (para area.php) ───────────
        case 'area_detalle':
            $wa_id = (int)$_GET['id'];
            $wa = $db->prepare("
                SELECT pa.*, a.nombre AS area_nombre,
                    u.nombre AS responsable_nombre,
                    p.id AS proyecto_id, p.codigo AS proy_codigo,
                    p.nombre AS proy_nombre, p.estado AS proy_estado,
                    p.porcentaje_avance AS proy_pct
                FROM proyecto_areas pa
                JOIN areas    a ON a.id = pa.area_id
                JOIN proyectos p ON p.id = pa.proyecto_id
                LEFT JOIN usuarios u ON u.id = pa.responsable_id
                WHERE pa.id = :id");
            $wa->execute([':id' => $wa_id]);
            $area = $wa->fetch();
            if (!$area) jsonResponse(['error' => 'Área no encontrada.'], 404);

            // Keywords por área para filtrar BOM
            $kw_map = [
                'Soldadura'          => ['SOLDAR', 'TAPAR'],
                'Corte'              => ['CORTAR', 'TRONZADORA', 'AMOLADORA', 'ESCUADRA', 'INCLINACION'],
                'Torno'              => ['TORNO', 'ROLAR'],
                'Taladro'            => ['TALADRO', 'BROCA', 'MACHO', 'HUECOS', 'MARCAR'],
                'Rolado'             => ['ROLAR', 'ROLADO'],
                'Ensamble'           => ['ENUMERAR', 'ENSAMBLE'],
                'Pintura'            => ['PINTURA', 'PINTAR'],
                'Control de Calidad' => [],
                'Electricidad'       => [],
            ];
            $kws = $kw_map[$area['area_nombre']] ?? [];
            // Keyword del área usada al asignar desde el modal
            $kw_primary_map = [
                'Soldadura'          => 'SOLDAR',
                'Corte'              => 'CORTAR',
                'Torno'              => 'TORNO',
                'Taladro'            => 'TALADRO',
                'Rolado'             => 'ROLAR',
                'Ensamble'           => 'ENSAMBLE',
                'Pintura'            => 'PINTAR',
                'Control de Calidad' => 'CALIDAD',
                'Electricidad'       => 'ELECTRICIDAD',
            ];
            $area_kw_primary = $kw_primary_map[$area['area_nombre']] ?? strtoupper($area['area_nombre']);

            // Combinar keywords del mapa + keyword primaria para capturar ítems asignados vía modal
            $all_kws = array_unique(array_filter(array_merge($kws, [$area_kw_primary])));
            $bom = [];
            if ($all_kws) {
                $conds = implode(' OR ', array_map(fn($k) => "pb.operacion ILIKE '%$k%'", $all_kws));
                $stmt  = $db->prepare("
                    SELECT pb.*,
                        pr.nombre AS prod_nombre, pr.codigo AS prod_codigo,
                        COALESCE(pr.unidad, pb.unidad_libre) AS unidad
                    FROM proyecto_bom pb
                    LEFT JOIN productos pr ON pr.id = pb.producto_id
                    WHERE pb.proyecto_id = :pid AND ($conds)
                    ORDER BY pb.seccion NULLS LAST, pb.orden, pb.id");
                $stmt->execute([':pid' => $area['proyecto_id']]);
                $bom = $stmt->fetchAll();
            }

            // Avances registrados para esta área
            $avs = $db->prepare("
                SELECT pa.*, u.nombre AS usuario_nombre
                FROM proyecto_avances pa
                LEFT JOIN usuarios u ON u.id = pa.usuario_id
                WHERE pa.proyecto_id = :pid AND pa.area_id = :aid
                ORDER BY pa.fecha DESC
                LIMIT 50");
            $avs->execute([':pid' => $area['proyecto_id'], ':aid' => $area['area_id']]);
            $avances = $avs->fetchAll();

            // Diseños y planos de esta área
            $dis = $db->prepare("
                SELECT * FROM area_disenos
                WHERE proyecto_area_id = :wa_id
                ORDER BY created_at DESC");
            $dis->execute([':wa_id' => $wa_id]);
            $disenos = $dis->fetchAll();

            jsonResponse(['ok' => true, 'area' => $area, 'bom' => $bom, 'avances' => $avances, 'disenos' => $disenos]);

        // ── PIEZA: MARCAR COMO TERMINADA ──────────────────────
        case 'pieza_terminar':
            $bom_id  = (int)($input['bom_id']      ?? 0);
            $pid_pt  = (int)($input['proyecto_id'] ?? 0);
            $aid_pt  = (int)($input['area_id']     ?? 0);
            $desc_pt = trim($input['descripcion']  ?? '');
            if (!$bom_id || !$pid_pt || !$desc_pt) jsonResponse(['error' => 'Faltan datos.'], 400);
            $db->beginTransaction();
            // Igualar cantidad_real a cantidad_planificada
            $db->prepare("UPDATE proyecto_bom SET cantidad_real = cantidad_planificada WHERE id = :id")
               ->execute([':id' => $bom_id]);
            // Registrar en el historial de avances
            $db->prepare("INSERT INTO proyecto_avances (proyecto_id, area_id, usuario_id, descripcion, porcentaje)
                          VALUES (:pid, :aid, NULL, :desc, 0)")
               ->execute([':pid' => $pid_pt, ':aid' => $aid_pt ?: null, ':desc' => $desc_pt]);
            $db->commit();
            jsonResponse(['ok' => true]);

        // ── BOM DISPONIBLE PARA EL ÁREA (ítems no asignados) ──
        case 'bom_disponible':
            $wa_id_q = (int)($_GET['proyecto_area_id'] ?? 0);
            $pid_q   = (int)($_GET['proyecto_id'] ?? 0);
            $wa2 = $db->prepare("SELECT a.nombre AS area_nombre FROM proyecto_areas pa JOIN areas a ON a.id = pa.area_id WHERE pa.id = :id");
            $wa2->execute([':id' => $wa_id_q]);
            $ar2 = $wa2->fetch();
            if (!$ar2) jsonResponse(['error' => 'Área no encontrada.'], 404);

            $kw_map2 = [
                'Soldadura'          => ['SOLDAR', 'TAPAR'],
                'Corte'              => ['CORTAR', 'TRONZADORA', 'AMOLADORA', 'ESCUADRA', 'INCLINACION'],
                'Torno'              => ['TORNO', 'ROLAR'],
                'Taladro'            => ['TALADRO', 'BROCA', 'MACHO', 'HUECOS', 'MARCAR'],
                'Rolado'             => ['ROLAR', 'ROLADO'],
                'Ensamble'           => ['ENUMERAR', 'ENSAMBLE'],
                'Pintura'            => ['PINTURA', 'PINTAR'],
                'Control de Calidad' => [],
                'Electricidad'       => [],
            ];
            $kws2 = $kw_map2[$ar2['area_nombre']] ?? [];
            if ($kws2) {
                $not_conds = implode(' AND ', array_map(fn($k) => "COALESCE(pb.operacion,'') NOT ILIKE '%$k%'", $kws2));
                $where_op  = "($not_conds)";
            } else {
                $where_op = '1=1';
            }
            $stmt2 = $db->prepare("
                SELECT pb.*,
                    pr.nombre AS prod_nombre, pr.codigo AS prod_codigo,
                    COALESCE(pr.unidad, pb.unidad_libre) AS unidad
                FROM proyecto_bom pb
                LEFT JOIN productos pr ON pr.id = pb.producto_id
                WHERE pb.proyecto_id = :pid AND $where_op
                ORDER BY pb.seccion NULLS LAST, pb.orden, pb.id");
            $stmt2->execute([':pid' => $pid_q]);
            $bom_disp = $stmt2->fetchAll();

            $pzStmt = $db->prepare("
                SELECT pp.id, pp.nombre, pp.orden,
                       COUNT(pb.id) AS total_items
                FROM proyecto_piezas pp
                LEFT JOIN proyecto_bom pb ON pb.pieza_id = pp.id
                WHERE pp.proyecto_id = :pid
                GROUP BY pp.id, pp.nombre, pp.orden
                ORDER BY pp.orden, pp.id");
            $pzStmt->execute([':pid' => $pid_q]);
            $piezas_disp = $pzStmt->fetchAll();

            jsonResponse(['ok' => true, 'bom' => $bom_disp, 'piezas' => $piezas_disp]);

        // ── BOM: ASIGNAR ÍTEMS A UN ÁREA ──────────────────────
        case 'bom_asignar_area':
            $bom_ids = array_map('intval', $input['bom_ids'] ?? []);
            $keyword = trim($input['keyword'] ?? '');
            if (!$bom_ids || !$keyword) jsonResponse(['error' => 'Faltan datos.'], 400);
            $placeholders = implode(',', array_fill(0, count($bom_ids), '?'));
            $stmt3 = $db->prepare("UPDATE proyecto_bom SET operacion = ? WHERE id IN ($placeholders)");
            $stmt3->execute(array_merge([$keyword], $bom_ids));
            jsonResponse(['ok' => true, 'actualizados' => count($bom_ids)]);

        // ── SOLICITUDES DE MATERIAL: CREAR ───────────────────
        case 'solicitud_crear':
            $stmt = $db->prepare("
                INSERT INTO solicitudes_material
                    (proyecto_id, area_id, proyecto_area_id, usuario_id,
                     producto_id, descripcion, cantidad, unidad)
                VALUES (:pid, :aid, :waid, :uid, :prod, :desc, :cant, :unidad)
                RETURNING id");
            $stmt->execute([
                ':pid'   => (int)$input['proyecto_id'],
                ':aid'   => (int)$input['area_id'],
                ':waid'  => (int)($input['proyecto_area_id'] ?? 0) ?: null,
                ':uid'   => (int)($input['usuario_id']  ?? 0) ?: null,
                ':prod'  => (int)($input['producto_id'] ?? 0) ?: null,
                ':desc'  => $input['descripcion'],
                ':cant'  => (float)$input['cantidad'],
                ':unidad'=> $input['unidad'] ?? 'unidad',
            ]);
            $sol_id = $stmt->fetchColumn();

            // Insertar alerta para el administrador
            $area_row = $db->prepare("SELECT a.nombre FROM areas a JOIN proyecto_areas pa ON pa.area_id = a.id WHERE pa.id = :waid");
            $area_row->execute([':waid' => (int)($input['proyecto_area_id'] ?? 0)]);
            $area_nombre = $area_row->fetchColumn() ?: 'Área';
            $proy_row = $db->prepare("SELECT codigo FROM proyectos WHERE id = :pid");
            $proy_row->execute([':pid' => (int)$input['proyecto_id']]);
            $proy_cod = $proy_row->fetchColumn();

            $db->prepare("
                INSERT INTO alertas (tipo, referencia_tipo, referencia_id, mensaje)
                VALUES ('solicitud_material', 'solicitudes_material', :sid, :msg)")
               ->execute([
                   ':sid' => $sol_id,
                   ':msg' => "$area_nombre solicita: {$input['descripcion']} × {$input['cantidad']} {$input['unidad']} — Proyecto $proy_cod",
               ]);
            jsonResponse(['ok' => true, 'id' => $sol_id]);

        // ── SOLICITUDES DE MATERIAL: GESTIONAR (admin) ────────
        case 'solicitud_gestionar':
            $allowed = ['aprobada', 'rechazada', 'entregada'];
            if (!in_array($input['estado'] ?? '', $allowed)) jsonResponse(['error' => 'Estado inválido.'], 400);
            $db->prepare("
                UPDATE solicitudes_material
                SET estado = :estado, respuesta = :resp, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id")
               ->execute([
                   ':estado' => $input['estado'],
                   ':resp'   => $input['respuesta'] ?? null,
                   ':id'     => (int)$input['id'],
               ]);
            // Marcar la alerta original como leída
            $db->prepare("UPDATE alertas SET leida = TRUE WHERE referencia_tipo='solicitudes_material' AND referencia_id=:id")
               ->execute([':id' => (int)$input['id']]);
            jsonResponse(['ok' => true]);

        // ── DISEÑOS Y PLANOS: GUARDAR ─────────────────────────
        case 'diseno_guardar':
            if (empty($input['nombre']))           jsonResponse(['error' => 'El nombre es obligatorio.'], 400);
            if (empty($input['proyecto_area_id'])) jsonResponse(['error' => 'Área no identificada.'], 400);
            if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK)
                jsonResponse(['error' => 'Archivo requerido.'], 400);

            $file     = $_FILES['archivo'];
            $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed  = ['jpg', 'jpeg', 'png', 'pdf'];
            if (!in_array($ext, $allowed))
                jsonResponse(['error' => 'Formato no válido. Se aceptan JPG, PNG y PDF.'], 400);
            if ($file['size'] > 8 * 1024 * 1024)
                jsonResponse(['error' => 'El archivo no puede superar 8 MB.'], 400);

            $upload_dir = dirname(__DIR__, 2) . '/assets/uploads/disenos/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $filename = 'dis_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (!move_uploaded_file($file['tmp_name'], $upload_dir . $filename))
                jsonResponse(['error' => 'Error al guardar el archivo.'], 500);

            $stmt = $db->prepare("
                INSERT INTO area_disenos (proyecto_area_id, proyecto_id, area_id, nombre, descripcion, filename)
                VALUES (:wa_id, :pid, :aid, :nom, :desc, :fn) RETURNING id");
            $stmt->execute([
                ':wa_id' => (int)$input['proyecto_area_id'],
                ':pid'   => (int)$input['proyecto_id'],
                ':aid'   => (int)$input['area_id'],
                ':nom'   => trim($input['nombre']),
                ':desc'  => ($input['descripcion'] ?? null) ?: null,
                ':fn'    => $filename,
            ]);
            jsonResponse(['ok' => true, 'id' => (int)$stmt->fetchColumn()]);

        // ── DISEÑOS Y PLANOS: ELIMINAR ────────────────────────
        case 'diseno_eliminar':
            $id = (int)($input['id'] ?? 0);
            if (!$id) jsonResponse(['error' => 'ID requerido.'], 400);
            $row = $db->prepare("SELECT filename FROM area_disenos WHERE id = :id");
            $row->execute([':id' => $id]);
            if ($fn = $row->fetchColumn()) {
                @unlink(dirname(__DIR__, 2) . '/assets/uploads/disenos/' . $fn);
            }
            $db->prepare("DELETE FROM area_disenos WHERE id = :id")->execute([':id' => $id]);
            jsonResponse(['ok' => true]);

        // ── SOLICITUDES PENDIENTES (para badges en proyecto.php) ─
        case 'solicitudes_pendientes_proyecto':
            $stmt = $db->prepare("
                SELECT area_id, COUNT(*) AS total
                FROM solicitudes_material
                WHERE proyecto_id = :pid AND estado = 'pendiente'
                GROUP BY area_id");
            $stmt->execute([':pid' => (int)$_GET['id']]);
            $rows = $stmt->fetchAll();
            $mapa = [];
            foreach ($rows as $r) $mapa[(int)$r['area_id']] = (int)$r['total'];
            jsonResponse(['ok' => true, 'pendientes' => $mapa]);

        // ── WORKFLOW: LISTAR ÁREAS DEL PROYECTO ───────────
        case 'workflow_listar':
            $stmt = $db->prepare("
                SELECT pa.*, a.nombre AS area_nombre, u.nombre AS responsable_nombre
                FROM proyecto_areas pa
                JOIN areas a ON a.id = pa.area_id
                LEFT JOIN usuarios u ON u.id = pa.responsable_id
                WHERE pa.proyecto_id = :pid
                ORDER BY pa.orden, a.nombre");
            $stmt->execute([':pid' => (int)$_GET['id']]);
            jsonResponse(['ok' => true, 'areas' => $stmt->fetchAll()]);

        // ── WORKFLOW: ACTUALIZAR ESTADO DE UN ÁREA ────────
        case 'workflow_actualizar':
            $allowed = ['pendiente', 'en_proceso', 'completado', 'bloqueado'];
            if (!in_array($input['estado'] ?? '', $allowed)) {
                jsonResponse(['error' => 'Estado de área inválido.'], 400);
            }
            $db->beginTransaction();
            $db->prepare("
                UPDATE proyecto_areas
                SET estado = :estado, porcentaje = :pct, responsable_id = :resp,
                    observaciones = :obs, updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ")->execute([
                ':id'     => (int)$input['id'],
                ':estado' => $input['estado'],
                ':pct'    => min(100, max(0, (int)($input['porcentaje'] ?? 0))),
                ':resp'   => ($input['responsable_id'] ?? null) ?: null,
                ':obs'    => ($input['observaciones']  ?? null) ?: null,
            ]);
            // Recalcular porcentaje global — excluye áreas externas
            $db->prepare("
                UPDATE proyectos
                SET porcentaje_avance = (
                    SELECT COALESCE(ROUND(AVG(pa.porcentaje)), 0)
                    FROM proyecto_areas pa
                    JOIN areas a ON a.id = pa.area_id
                    WHERE pa.proyecto_id = :pid
                    AND a.es_externa = FALSE
                ),
                updated_at = CURRENT_TIMESTAMP
                WHERE id = :pid
            ")->execute([':pid' => (int)$input['proyecto_id']]);
            $db->commit();
            jsonResponse(['ok' => true]);

        // ── WORKFLOW SETTINGS: LISTAR ÁREAS ──────────────────
        case 'wf_areas_listar':
            $stmt = $db->query("
                SELECT a.*,
                    (SELECT COUNT(*) FROM proyecto_areas pa WHERE pa.area_id = a.id) AS total_proyectos
                FROM areas a
                ORDER BY a.orden_workflow, a.nombre");
            jsonResponse(['ok' => true, 'areas' => $stmt->fetchAll()]);

        // ── WORKFLOW SETTINGS: GUARDAR ÁREA ──────────────────
        case 'wf_area_guardar':
            $nombre = trim($input['nombre'] ?? '');
            if (!$nombre) jsonResponse(['error' => 'El nombre es obligatorio.'], 400);
            $es_externa    = filter_var($input['es_externa']    ?? false, FILTER_VALIDATE_BOOLEAN);
            $orden         = (int)($input['orden_workflow'] ?? 99);
            $descripcion   = trim($input['descripcion'] ?? '') ?: null;

            if (!empty($input['id'])) {
                $db->prepare("
                    UPDATE areas SET nombre=:n, descripcion=:d, es_externa=:ext, orden_workflow=:ord
                    WHERE id=:id")
                   ->execute([':id'=>(int)$input['id'],':n'=>$nombre,':d'=>$descripcion,':ext'=>$es_externa,':ord'=>$orden]);
                jsonResponse(['ok' => true]);
            } else {
                $stmt = $db->prepare("
                    INSERT INTO areas (nombre, descripcion, es_externa, orden_workflow)
                    VALUES (:n, :d, :ext, :ord) RETURNING id");
                $stmt->execute([':n'=>$nombre,':d'=>$descripcion,':ext'=>$es_externa,':ord'=>$orden]);
                jsonResponse(['ok' => true, 'id' => $stmt->fetchColumn()]);
            }

        // ── WORKFLOW SETTINGS: TOGGLE ACTIVO ─────────────────
        case 'wf_area_toggle':
            $db->prepare("UPDATE areas SET activo = NOT activo WHERE id = :id")
               ->execute([':id' => (int)($input['id'] ?? 0)]);
            jsonResponse(['ok' => true]);

        // ── WORKFLOW SETTINGS: ELIMINAR ÁREA ─────────────────
        case 'wf_area_eliminar':
            $areaId = (int)($input['id'] ?? 0);
            $stmt = $db->prepare("SELECT COUNT(*) FROM proyecto_areas WHERE area_id = :id");
            $stmt->execute([':id' => $areaId]);
            if ((int)$stmt->fetchColumn() > 0) {
                jsonResponse(['error' => 'No se puede eliminar: el área tiene proyectos asignados. Desactívala en su lugar.'], 409);
            }
            $db->prepare("DELETE FROM areas WHERE id = :id")->execute([':id' => $areaId]);
            jsonResponse(['ok' => true]);

        // ── PIEZA: CREAR ──────────────────────────────────────
        case 'pieza_crear':
            $nombre = trim($input['nombre'] ?? '');
            if (!$nombre) jsonResponse(['error' => 'El nombre es obligatorio.'], 400);
            $stmt = $db->prepare("
                SELECT COALESCE(MAX(orden), -1) + 1 FROM proyecto_piezas WHERE proyecto_id = :pid");
            $stmt->execute([':pid' => (int)$input['proyecto_id']]);
            $orden = (int)$stmt->fetchColumn();
            $stmt = $db->prepare("
                INSERT INTO proyecto_piezas (proyecto_id, nombre, orden)
                VALUES (:pid, :nombre, :orden) RETURNING id");
            $stmt->execute([':pid' => (int)$input['proyecto_id'], ':nombre' => $nombre, ':orden' => $orden]);
            $piezaId = (int)$stmt->fetchColumn();
            $db->prepare("INSERT INTO proyecto_partes (pieza_id, nombre, orden) VALUES (:pieza_id, 'Parte 1', 0)")
               ->execute([':pieza_id' => $piezaId]);
            jsonResponse(['ok' => true, 'id' => $piezaId]);

        // ── PIEZA: RENOMBRAR ──────────────────────────────────
        case 'pieza_renombrar':
            $nombre = trim($input['nombre'] ?? '');
            if (!$nombre) jsonResponse(['error' => 'El nombre es obligatorio.'], 400);
            $db->prepare("UPDATE proyecto_piezas SET nombre = :nombre WHERE id = :id")
               ->execute([':nombre' => $nombre, ':id' => (int)($input['id'] ?? 0)]);
            jsonResponse(['ok' => true]);

        // ── PIEZA: ELIMINAR ───────────────────────────────────
        case 'pieza_eliminar':
            $db->prepare("DELETE FROM proyecto_piezas WHERE id = :id")
               ->execute([':id' => (int)($input['id'] ?? 0)]);
            jsonResponse(['ok' => true]);

        // ── PIEZA: GUARDAR PROPIEDADES TÉCNICAS ───────────────
        case 'pieza_propiedades_guardar':
            $id    = (int)($input['id'] ?? 0);
            $props = $input['propiedades'] ?? [];
            if (!$id) jsonResponse(['error' => 'ID inválido.'], 400);
            $db->prepare("UPDATE proyecto_piezas SET propiedades_tecnicas = :props WHERE id = :id")
               ->execute([':props' => json_encode($props), ':id' => $id]);
            jsonResponse(['ok' => true]);

        // ── WORKFLOW: AGREGAR ÁREA INTERNA AL PROYECTO ───────
        case 'workflow_area_agregar':
            $nombre = trim($input['nombre'] ?? '');
            if (!$nombre) jsonResponse(['error' => 'El nombre es obligatorio.'], 400);
            $pid = (int)$input['proyecto_id'];

            // Buscar área interna existente con ese nombre o crearla
            $stmt = $db->prepare("SELECT id FROM areas WHERE LOWER(nombre) = LOWER(:n) AND es_externa = FALSE LIMIT 1");
            $stmt->execute([':n' => $nombre]);
            $area_id = $stmt->fetchColumn();
            if (!$area_id) {
                $stmt = $db->prepare("INSERT INTO areas (nombre, es_externa, activo) VALUES (:n, FALSE, TRUE) RETURNING id");
                $stmt->execute([':n' => $nombre]);
                $area_id = (int)$stmt->fetchColumn();
            }

            // Verificar que no esté ya asignada al proyecto
            $stmt = $db->prepare("SELECT id FROM proyecto_areas WHERE proyecto_id = :pid AND area_id = :aid");
            $stmt->execute([':pid' => $pid, ':aid' => $area_id]);
            if ($stmt->fetch()) jsonResponse(['error' => 'Esta área ya está asignada al proyecto.'], 409);

            $stmt = $db->prepare("SELECT COALESCE(MAX(orden), 0) + 1 FROM proyecto_areas WHERE proyecto_id = :pid");
            $stmt->execute([':pid' => $pid]);
            $orden = (int)$stmt->fetchColumn();

            $stmt = $db->prepare("
                INSERT INTO proyecto_areas (proyecto_id, area_id, estado, porcentaje, responsable_id, observaciones, orden)
                VALUES (:pid, :aid, :estado, :pct, :resp, :obs, :orden) RETURNING id");
            $stmt->execute([
                ':pid'    => $pid,
                ':aid'    => $area_id,
                ':estado' => $input['estado']          ?? 'pendiente',
                ':pct'    => min(100, max(0, (int)($input['porcentaje'] ?? 0))),
                ':resp'   => ($input['responsable_id'] ?? null) ?: null,
                ':obs'    => ($input['observaciones']  ?? null) ?: null,
                ':orden'  => $orden,
            ]);
            jsonResponse(['ok' => true, 'id' => (int)$stmt->fetchColumn()]);

        // ── SERVICIO EXTERNO: AGREGAR ─────────────────────────
        case 'servicio_agregar':
            $nombre = trim($input['nombre'] ?? '');
            if (!$nombre) jsonResponse(['error' => 'El nombre es obligatorio.'], 400);
            $pid = (int)$input['proyecto_id'];

            // Buscar área externa existente con ese nombre o crearla
            $stmt = $db->prepare("SELECT id FROM areas WHERE LOWER(nombre) = LOWER(:n) AND es_externa = TRUE LIMIT 1");
            $stmt->execute([':n' => $nombre]);
            $area_id = $stmt->fetchColumn();
            if (!$area_id) {
                $stmt = $db->prepare("INSERT INTO areas (nombre, es_externa, activo) VALUES (:n, TRUE, TRUE) RETURNING id");
                $stmt->execute([':n' => $nombre]);
                $area_id = (int)$stmt->fetchColumn();
            }

            // Verificar que no esté ya asignada al proyecto
            $stmt = $db->prepare("SELECT id FROM proyecto_areas WHERE proyecto_id = :pid AND area_id = :aid");
            $stmt->execute([':pid' => $pid, ':aid' => $area_id]);
            if ($stmt->fetch()) jsonResponse(['error' => 'Este servicio ya está asignado al proyecto.'], 409);

            $stmt = $db->prepare("SELECT COALESCE(MAX(orden), 0) + 1 FROM proyecto_areas WHERE proyecto_id = :pid");
            $stmt->execute([':pid' => $pid]);
            $orden = (int)$stmt->fetchColumn();

            $stmt = $db->prepare("
                INSERT INTO proyecto_areas (proyecto_id, area_id, estado, porcentaje, responsable_id, observaciones, orden)
                VALUES (:pid, :aid, :estado, :pct, :resp, :obs, :orden) RETURNING id");
            $stmt->execute([
                ':pid'    => $pid,
                ':aid'    => $area_id,
                ':estado' => $input['estado']          ?? 'pendiente',
                ':pct'    => min(100, max(0, (int)($input['porcentaje'] ?? 0))),
                ':resp'   => ($input['responsable_id'] ?? null) ?: null,
                ':obs'    => ($input['observaciones']  ?? null) ?: null,
                ':orden'  => $orden,
            ]);
            jsonResponse(['ok' => true, 'id' => (int)$stmt->fetchColumn()]);

        // ── SERVICIO EXTERNO: ELIMINAR ────────────────────────
        case 'servicio_eliminar':
            $db->prepare("DELETE FROM proyecto_areas WHERE id = :id")
               ->execute([':id' => (int)($input['id'] ?? 0)]);
            jsonResponse(['ok' => true]);

        // ── FORMULACIÓN: TODOS LOS BOM DE PROYECTOS ACTIVOS ────────
        // ── PIEZAS DISPONIBLES DE UN PROYECTO ────────────
        // Retorna piezas físicas en estado 'disponible' para que el operador
        // pueda seleccionarlas como inputs adicionales al completar una parte.
        case 'piezas_disponibles_proyecto':
            $proyectoId = (int)($_GET['proyecto_id'] ?? $input['proyecto_id'] ?? 0);
            if (!$proyectoId) jsonResponse(['error' => 'proyecto_id requerido'], 400);
            $stmt = $db->prepare("
                SELECT id, nombre
                FROM proyecto_piezas
                WHERE proyecto_id = :pid AND estado = 'disponible'
                ORDER BY nombre, id");
            $stmt->execute([':pid' => $proyectoId]);
            jsonResponse(['ok' => true, 'piezas' => $stmt->fetchAll()]);

        case 'formulacion_listar':
            $stmtP = $db->query("
                SELECT p.id, p.codigo, p.nombre, p.estado, p.prioridad,
                       c.razon_social AS cliente_nombre
                FROM proyectos p
                LEFT JOIN clientes c ON c.id = p.cliente_id
                WHERE p.activo = TRUE AND p.estado NOT IN ('completado','cancelado')
                ORDER BY
                    CASE p.prioridad WHEN 'urgente' THEN 1 WHEN 'alta' THEN 2
                                     WHEN 'normal'  THEN 3 ELSE 4 END,
                    p.fecha_entrega_estimada NULLS LAST,
                    p.created_at DESC");
            $proyectosFrm = $stmtP->fetchAll();
            if (!$proyectosFrm) { jsonResponse(['ok' => true, 'proyectos' => []]); }

            $pidsFrm  = array_column($proyectosFrm, 'id');
            $pidPhFrm = implode(',', array_fill(0, count($pidsFrm), '?'));

            // Solo piezas estructurales (no producidas por transformación)
            $stmtPz = $db->prepare("
                SELECT id, proyecto_id, nombre, orden, propiedades_tecnicas
                FROM proyecto_piezas
                WHERE proyecto_id IN ($pidPhFrm)
                  AND (estado IS NULL OR estado NOT IN ('disponible','en_proceso','consumida','integrada','stock'))
                ORDER BY proyecto_id, orden, id");
            $stmtPz->execute($pidsFrm);
            $piezasListFrm = array_map(function($p) {
                $p['propiedades_tecnicas'] = !empty($p['propiedades_tecnicas'])
                    ? json_decode($p['propiedades_tecnicas'], true) : [];
                return $p;
            }, $stmtPz->fetchAll());

            // Partes
            $piezaIdsFrm = array_column($piezasListFrm, 'id');
            $partesListFrm = [];
            if ($piezaIdsFrm) {
                $pzPhFrm = implode(',', array_fill(0, count($piezaIdsFrm), '?'));
                $stmtPt = $db->prepare("
                    SELECT id, pieza_id, nombre, orden
                    FROM proyecto_partes
                    WHERE pieza_id IN ($pzPhFrm)
                    ORDER BY pieza_id, orden, id");
                $stmtPt->execute($piezaIdsFrm);
                $partesListFrm = $stmtPt->fetchAll();
            }

            // BOM
            $stmtB = $db->prepare("
                SELECT pb.*,
                       pr.codigo AS prod_codigo, pr.nombre AS prod_nombre,
                       pr.precio_venta,
                       COALESCE(pr.unidad, pb.unidad_libre) AS unidad
                FROM proyecto_bom pb
                LEFT JOIN productos pr ON pr.id = pb.producto_id
                WHERE pb.proyecto_id IN ($pidPhFrm)
                ORDER BY pb.proyecto_id, pb.orden, pb.id");
            $stmtB->execute($pidsFrm);
            $bomListFrm = $stmtB->fetchAll();

            // Agrupar por proyecto
            $piezasByProyFrm = [];
            foreach ($piezasListFrm as $pz) $piezasByProyFrm[$pz['proyecto_id']][] = $pz;
            $partesByPzFrm = [];
            foreach ($partesListFrm as $pt) $partesByPzFrm[$pt['pieza_id']][] = $pt;
            $bomByProyFrm = [];
            foreach ($bomListFrm as $b) $bomByProyFrm[$b['proyecto_id']][] = $b;

            foreach ($proyectosFrm as &$proyFrm) {
                $proyFrm['piezas'] = $piezasByProyFrm[$proyFrm['id']] ?? [];
                $proyFrm['bom']    = $bomByProyFrm[$proyFrm['id']]    ?? [];
                $partesFrm = [];
                foreach ($proyFrm['piezas'] as $pz) {
                    foreach ($partesByPzFrm[$pz['id']] ?? [] as $pt) $partesFrm[] = $pt;
                }
                $proyFrm['partes'] = $partesFrm;
            }
            unset($proyFrm);
            jsonResponse(['ok' => true, 'proyectos' => $proyectosFrm]);

        default:
            jsonResponse(['error' => 'Acción no reconocida.'], 400);
    }
} catch (PDOException $e) {
    if ($db->inTransaction()) $db->rollBack();
    jsonResponse(['error' => 'Error de base de datos: ' . $e->getMessage()], 500);
}

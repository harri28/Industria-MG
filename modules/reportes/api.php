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
    // KPIs GENERALES
    // ================================================================
    case 'kpis':
        $mes_actual = date('Y-m');
        $mes_ant    = date('Y-m', strtotime('-1 month'));

        // Producción
        $proy_activos    = $db->query("SELECT COUNT(*) FROM proyectos WHERE activo AND estado NOT IN ('completado','cancelado')")->fetchColumn();
        $proy_retrasados = $db->query("SELECT COUNT(*) FROM proyectos WHERE activo AND estado NOT IN ('completado','cancelado') AND fecha_entrega_estimada < CURRENT_DATE")->fetchColumn();
        $proy_completados_mes = $db->query("SELECT COUNT(*) FROM proyectos WHERE DATE_TRUNC('month',updated_at)=DATE_TRUNC('month',NOW()) AND estado='completado'")->fetchColumn();

        // Ventas
        $cot_mes    = $db->query("SELECT COUNT(*) FROM cotizaciones WHERE DATE_TRUNC('month',fecha_emision)=DATE_TRUNC('month',NOW())")->fetchColumn();
        $ingresos_mes = $db->query("SELECT COALESCE(SUM(total),0) FROM cotizaciones WHERE estado='aprobada' AND DATE_TRUNC('month',fecha_emision)=DATE_TRUNC('month',NOW())")->fetchColumn();
        $ingresos_ant = $db->query("SELECT COALESCE(SUM(total),0) FROM cotizaciones WHERE estado='aprobada' AND DATE_TRUNC('month',fecha_emision)=DATE_TRUNC('month',NOW()-INTERVAL '1 month')")->fetchColumn();

        // Compras
        $compras_mes  = $db->query("SELECT COALESCE(SUM(total),0) FROM ordenes_compra WHERE DATE_TRUNC('month',fecha_emision)=DATE_TRUNC('month',NOW())")->fetchColumn();
        $compras_ant  = $db->query("SELECT COALESCE(SUM(total),0) FROM ordenes_compra WHERE DATE_TRUNC('month',fecha_emision)=DATE_TRUNC('month',NOW()-INTERVAL '1 month')")->fetchColumn();

        // Inventarios
        $valor_inv    = $db->query("SELECT COALESCE(SUM(stock_actual*precio_promedio),0) FROM productos WHERE activo")->fetchColumn();
        $stock_critico = $db->query("SELECT COUNT(*) FROM productos WHERE activo AND stock_actual<=stock_minimo AND stock_minimo>0")->fetchColumn();

        // Alertas
        $alertas_no_leidas = $db->query("SELECT COUNT(*) FROM alertas WHERE leida=FALSE")->fetchColumn();

        jsonResponse([
            'ok'                  => true,
            'proy_activos'        => (int)$proy_activos,
            'proy_retrasados'     => (int)$proy_retrasados,
            'proy_completados_mes'=> (int)$proy_completados_mes,
            'cot_mes'             => (int)$cot_mes,
            'ingresos_mes'        => (float)$ingresos_mes,
            'ingresos_ant'        => (float)$ingresos_ant,
            'compras_mes'         => (float)$compras_mes,
            'compras_ant'         => (float)$compras_ant,
            'valor_inv'           => (float)$valor_inv,
            'stock_critico'       => (int)$stock_critico,
            'alertas_no_leidas'   => (int)$alertas_no_leidas,
        ]);

    // ================================================================
    // REPORTE DE PRODUCCIÓN
    // ================================================================
    case 'produccion':
        $desde = $input['desde'] ?? date('Y-m-01');
        $hasta = $input['hasta'] ?? date('Y-m-d');

        // Proyectos por estado
        $stmt = $db->query("SELECT estado, COUNT(*) AS cnt FROM proyectos WHERE activo GROUP BY estado ORDER BY estado");
        $por_estado = $stmt->fetchAll();

        // Proyectos por mes (últimos 6 meses)
        $stmt = $db->query("
            SELECT TO_CHAR(created_at,'YYYY-MM') AS mes, COUNT(*) AS total,
                   COUNT(*) FILTER (WHERE estado='completado') AS completados
            FROM proyectos
            WHERE created_at >= NOW() - INTERVAL '6 months'
            GROUP BY mes ORDER BY mes");
        $por_mes = $stmt->fetchAll();

        // Costo planificado vs real
        $stmt = $db->prepare("
            SELECT p.codigo, p.nombre, p.costo_estimado, p.costo_real,
                   p.costo_real - p.costo_estimado AS desviacion,
                   p.porcentaje_avance, p.estado
            FROM proyectos p
            WHERE p.activo AND p.created_at >= :desde AND p.created_at <= :hasta
            ORDER BY p.created_at DESC LIMIT 20");
        $stmt->execute([':desde' => $desde . ' 00:00:00', ':hasta' => $hasta . ' 23:59:59']);
        $proyectos = $stmt->fetchAll();

        // Retrasados
        $stmt = $db->query("
            SELECT p.codigo, p.nombre, p.fecha_entrega_estimada,
                   (CURRENT_DATE - p.fecha_entrega_estimada) AS dias_retraso,
                   u.nombre AS responsable
            FROM proyectos p
            LEFT JOIN usuarios u ON u.id=p.responsable_id
            WHERE p.activo AND p.estado NOT IN ('completado','cancelado')
              AND p.fecha_entrega_estimada < CURRENT_DATE
            ORDER BY dias_retraso DESC");
        $retrasados = $stmt->fetchAll();

        jsonResponse(['ok' => true, 'por_estado' => $por_estado, 'por_mes' => $por_mes,
                      'proyectos' => $proyectos, 'retrasados' => $retrasados]);

    // ================================================================
    // RENTABILIDAD POR PROYECTO
    // ================================================================
    case 'rentabilidad':
        $desde  = $input['desde']  ?? date('Y-01-01');
        $hasta  = $input['hasta']  ?? date('Y-m-d');
        $estado = $input['estado'] ?? 'todos';

        $conditions = ["p.activo = TRUE", "p.created_at BETWEEN :desde AND :hasta"];
        $params = [':desde' => $desde . ' 00:00:00', ':hasta' => $hasta . ' 23:59:59'];
        if ($estado !== 'todos') {
            $conditions[] = "p.estado = :estado";
            $params[':estado'] = $estado;
        }
        $where = implode(' AND ', $conditions);

        $stmt = $db->prepare("
            SELECT p.id, p.codigo, p.nombre, p.estado,
                   p.costo_estimado,
                   p.costo_real,
                   COALESCE(c.razon_social, '—') AS cliente,
                   COALESCE(ov.ingreso, 0)       AS ingreso,
                   COALESCE(ov.ingreso, 0) - p.costo_real AS margen,
                   CASE WHEN COALESCE(ov.ingreso, 0) > 0
                        THEN ROUND(((COALESCE(ov.ingreso, 0) - p.costo_real) / COALESCE(ov.ingreso, 0)) * 100, 1)
                        ELSE NULL END AS margen_pct,
                   p.porcentaje_avance,
                   p.fecha_entrega_estimada
            FROM proyectos p
            LEFT JOIN clientes c ON c.id = p.cliente_id
            LEFT JOIN (
                SELECT proyecto_id, SUM(total) AS ingreso
                FROM ordenes_venta
                WHERE estado NOT IN ('borrador','cancelada')
                GROUP BY proyecto_id
            ) ov ON ov.proyecto_id = p.id
            WHERE $where
            ORDER BY margen DESC NULLS LAST, p.created_at DESC");
        $stmt->execute($params);
        $proyectos = $stmt->fetchAll();

        // Totales
        $totales = [
            'ingreso_total'       => 0,
            'costo_total'         => 0,
            'margen_total'        => 0,
            'con_venta'           => 0,
            'sin_venta'           => 0,
            'rentables'           => 0,
            'en_perdida'          => 0,
        ];
        foreach ($proyectos as $p) {
            $totales['ingreso_total'] += (float)$p['ingreso'];
            $totales['costo_total']   += (float)$p['costo_real'];
            $totales['margen_total']  += (float)$p['margen'];
            if ((float)$p['ingreso'] > 0) $totales['con_venta']++;
            else $totales['sin_venta']++;
            if ((float)$p['margen'] > 0) $totales['rentables']++;
            elseif ((float)$p['costo_real'] > 0) $totales['en_perdida']++;
        }
        $totales['margen_pct_total'] = $totales['ingreso_total'] > 0
            ? round(($totales['margen_total'] / $totales['ingreso_total']) * 100, 1)
            : null;

        jsonResponse(['ok' => true, 'proyectos' => $proyectos, 'totales' => $totales]);

    // ================================================================
    // REPORTE DE VENTAS
    // ================================================================
    case 'ventas':
        $desde = $input['desde'] ?? date('Y-01-01');
        $hasta = $input['hasta'] ?? date('Y-m-d');

        // Por mes
        $stmt = $db->prepare("
            SELECT TO_CHAR(fecha_emision,'YYYY-MM') AS mes,
                   COUNT(*) AS cotizaciones,
                   COUNT(*) FILTER (WHERE estado='aprobada') AS aprobadas,
                   COALESCE(SUM(total) FILTER (WHERE estado='aprobada'),0) AS ingresos
            FROM cotizaciones
            WHERE fecha_emision BETWEEN :desde AND :hasta
            GROUP BY mes ORDER BY mes");
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
        $por_mes = $stmt->fetchAll();

        // Por cliente (top 10)
        $stmt = $db->prepare("
            SELECT cl.razon_social, COUNT(co.id) AS cotizaciones,
                   COALESCE(SUM(co.total) FILTER (WHERE co.estado='aprobada'),0) AS ingresos
            FROM cotizaciones co
            JOIN clientes cl ON cl.id=co.cliente_id
            WHERE co.fecha_emision BETWEEN :desde AND :hasta
            GROUP BY cl.id, cl.razon_social
            ORDER BY ingresos DESC LIMIT 10");
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
        $top_clientes = $stmt->fetchAll();

        // Pipeline
        $stmt = $db->query("SELECT estado, COUNT(*) AS cnt, COALESCE(SUM(total),0) AS monto FROM cotizaciones GROUP BY estado ORDER BY estado");
        $pipeline = $stmt->fetchAll();

        jsonResponse(['ok' => true, 'por_mes' => $por_mes, 'top_clientes' => $top_clientes, 'pipeline' => $pipeline]);

    // ================================================================
    // REPORTE DE COMPRAS
    // ================================================================
    case 'compras':
        $desde = $input['desde'] ?? date('Y-01-01');
        $hasta = $input['hasta'] ?? date('Y-m-d');

        // Por mes
        $stmt = $db->prepare("
            SELECT TO_CHAR(fecha_emision,'YYYY-MM') AS mes,
                   COUNT(*) AS ordenes,
                   COALESCE(SUM(total),0) AS monto
            FROM ordenes_compra
            WHERE fecha_emision BETWEEN :desde AND :hasta
            GROUP BY mes ORDER BY mes");
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
        $por_mes = $stmt->fetchAll();

        // Top proveedores
        $stmt = $db->prepare("
            SELECT p.razon_social, COUNT(oc.id) AS ordenes,
                   COALESCE(SUM(oc.total),0) AS monto_total, p.calificacion
            FROM ordenes_compra oc
            JOIN proveedores p ON p.id=oc.proveedor_id
            WHERE oc.fecha_emision BETWEEN :desde AND :hasta
            GROUP BY p.id, p.razon_social, p.calificacion
            ORDER BY monto_total DESC LIMIT 10");
        $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
        $top_proveedores = $stmt->fetchAll();

        jsonResponse(['ok' => true, 'por_mes' => $por_mes, 'top_proveedores' => $top_proveedores]);

    // ================================================================
    // REPORTE INVENTARIO
    // ================================================================
    case 'inventario':
        // Stock crítico
        $stmt = $db->query("
            SELECT p.codigo, p.nombre, p.stock_actual, p.stock_minimo, p.unidad,
                   c.nombre AS categoria,
                   p.stock_actual * p.precio_promedio AS valor
            FROM productos p
            LEFT JOIN categorias_producto c ON c.id=p.categoria_id
            WHERE p.activo AND p.stock_actual <= p.stock_minimo AND p.stock_minimo > 0
            ORDER BY (p.stock_actual::float/NULLIF(p.stock_minimo,0)) ASC LIMIT 30");
        $criticos = $stmt->fetchAll();

        // Por categoría
        $stmt = $db->query("
            SELECT c.nombre AS categoria,
                   COUNT(p.id) AS productos,
                   COALESCE(SUM(p.stock_actual * p.precio_promedio),0) AS valor_total
            FROM productos p
            LEFT JOIN categorias_producto c ON c.id=p.categoria_id
            WHERE p.activo
            GROUP BY c.nombre ORDER BY valor_total DESC");
        $por_categoria = $stmt->fetchAll();

        // Sin movimiento hace +30 días
        $stmt = $db->query("
            SELECT p.codigo, p.nombre, p.stock_actual, p.unidad,
                   MAX(k.fecha) AS ultimo_movimiento
            FROM productos p
            LEFT JOIN kardex k ON k.producto_id=p.id
            WHERE p.activo
            GROUP BY p.id, p.codigo, p.nombre, p.stock_actual, p.unidad
            HAVING MAX(k.fecha) < NOW() - INTERVAL '30 days' OR MAX(k.fecha) IS NULL
            ORDER BY ultimo_movimiento ASC NULLS FIRST LIMIT 20");
        $sin_movimiento = $stmt->fetchAll();

        jsonResponse(['ok' => true, 'criticos' => $criticos, 'por_categoria' => $por_categoria, 'sin_movimiento' => $sin_movimiento]);

    // ================================================================
    // ALERTAS
    // ================================================================
    case 'alertas_listar':
        $where  = ['1=1'];
        $params = [];
        if (isset($input['leida']) && $input['leida'] !== '') {
            $where[] = "a.leida = :leida";
            $params[':leida'] = filter_var($input['leida'], FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
        }
        $stmt = $db->prepare("
            SELECT a.*, u.nombre AS usuario_nombre
            FROM alertas a LEFT JOIN usuarios u ON u.id=a.usuario_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY a.created_at DESC LIMIT 100");
        $stmt->execute($params);
        jsonResponse(['ok' => true, 'alertas' => $stmt->fetchAll()]);

    case 'alerta_marcar_leida':
        $id = (int)($input['id'] ?? 0);
        if ($id) {
            $db->prepare("UPDATE alertas SET leida=TRUE WHERE id=:id")->execute([':id' => $id]);
        } else {
            $db->query("UPDATE alertas SET leida=TRUE");
        }
        jsonResponse(['ok' => true]);

    // ================================================================
    // REPORTE DE COMPROBANTES ELECTRÓNICOS
    // ================================================================
    case 'comprobantes':
        $desde  = $input['desde']  ?? date('Y-01-01');
        $hasta  = $input['hasta']  ?? date('Y-m-d');
        $tipo   = trim($input['tipo']   ?? '');
        $estado = trim($input['estado'] ?? '');
        $q      = trim($input['q']      ?? '');

        $conditions = ["ce.fecha_emision BETWEEN :desde AND :hasta"];
        $params     = [':desde' => $desde, ':hasta' => $hasta];

        if ($tipo)   { $conditions[] = "td.codigo = :tipo";    $params[':tipo']   = $tipo; }
        if ($estado) { $conditions[] = "ce.estado = :estado";  $params[':estado'] = $estado; }
        if ($q) {
            $conditions[] = "(ce.serie || '-' || ce.numero ILIKE :q OR cl.razon_social ILIKE :q OR cl.ruc ILIKE :q)";
            $params[':q'] = "%$q%";
        }

        $where = implode(' AND ', $conditions);

        $stmt = $db->prepare("
            SELECT ce.id,
                   ce.serie || '-' || ce.numero AS numero_completo,
                   ce.serie, ce.numero,
                   td.codigo  AS tipo_doc,
                   td.nombre  AS tipo_doc_nombre,
                   ce.fecha_emision,
                   cl.razon_social AS cliente,
                   cl.ruc          AS cliente_ruc,
                   ce.subtotal, ce.igv, ce.total,
                   ce.estado,
                   ce.hash_cdr,
                   ce.estado_cpe,
                   ce.orden_venta_id
            FROM comprobantes_electronicos ce
            JOIN clientes cl                 ON cl.id  = ce.cliente_id
            LEFT JOIN fe_tipos_documento td  ON td.id  = ce.tipo_documento_id
            WHERE $where
            ORDER BY ce.fecha_emision DESC, ce.id DESC
            LIMIT 500");
        $stmt->execute($params);
        $comprobantes = $stmt->fetchAll();

        // Resumen por tipo
        $stmtR = $db->prepare("
            SELECT td.codigo AS tipo_doc,
                   td.nombre AS tipo_doc_nombre,
                   COUNT(*)  AS cantidad,
                   SUM(ce.subtotal) AS subtotal,
                   SUM(ce.igv)      AS igv,
                   SUM(ce.total)    AS total
            FROM comprobantes_electronicos ce
            LEFT JOIN fe_tipos_documento td ON td.id = ce.tipo_documento_id
            WHERE ce.fecha_emision BETWEEN :desde AND :hasta
            GROUP BY td.codigo, td.nombre
            ORDER BY total DESC");
        $stmtR->execute([':desde' => $desde, ':hasta' => $hasta]);
        $resumen = $stmtR->fetchAll();

        jsonResponse(['ok' => true, 'comprobantes' => $comprobantes, 'resumen' => $resumen]);

    // ================================================================
    // PRODUCTIVIDAD POR ÁREA
    // ================================================================
    case 'productividad':
        $desde = $input['desde'] ?? date('Y-01-01');
        $hasta = $input['hasta'] ?? date('Y-m-d');

        // Métricas por área: partes completadas, tiempo promedio, backlog
        $stmt = $db->prepare("
            SELECT a.id, a.nombre AS area, a.es_externa,
                   COUNT(pt.id) FILTER (WHERE pt.estado_fabricacion = 'completada'
                                          AND pt.fecha_completada BETWEEN :desde AND :hasta)
                       AS completadas,
                   COUNT(pt.id) FILTER (WHERE pt.estado_fabricacion IN ('pendiente','en_proceso'))
                       AS backlog,
                   ROUND(AVG(
                       EXTRACT(EPOCH FROM (pt.fecha_completada - pt.fecha_inicio)) / 3600
                   ) FILTER (WHERE pt.estado_fabricacion = 'completada'
                               AND pt.fecha_inicio IS NOT NULL
                               AND pt.fecha_completada IS NOT NULL
                               AND pt.fecha_completada BETWEEN :desde2 AND :hasta2), 1)
                       AS tiempo_promedio_h
            FROM areas a
            LEFT JOIN proyecto_partes pt ON pt.area_id = a.id
            WHERE a.activo = TRUE
            GROUP BY a.id, a.nombre, a.es_externa
            ORDER BY completadas DESC NULLS LAST, a.nombre");
        $stmt->execute([':desde' => $desde . ' 00:00:00', ':hasta' => $hasta . ' 23:59:59',
                        ':desde2'=> $desde . ' 00:00:00', ':hasta2'=> $hasta . ' 23:59:59']);
        $areas = $stmt->fetchAll();

        // Top operarios por partes completadas en el período
        $stmtU = $db->prepare("
            SELECT u.nombre AS usuario,
                   a.nombre AS area,
                   COUNT(*) AS completadas,
                   ROUND(AVG(EXTRACT(EPOCH FROM (pt.fecha_completada - pt.fecha_inicio)) / 3600)
                         FILTER (WHERE pt.fecha_inicio IS NOT NULL), 1) AS tiempo_prom_h
            FROM proyecto_partes pt
            JOIN usuarios u ON u.id = pt.usuario_completo_id
            JOIN areas   a ON a.id  = pt.area_id
            WHERE pt.estado_fabricacion = 'completada'
              AND pt.fecha_completada BETWEEN :desde AND :hasta
            GROUP BY u.id, u.nombre, a.id, a.nombre
            ORDER BY completadas DESC
            LIMIT 15");
        $stmtU->execute([':desde' => $desde . ' 00:00:00', ':hasta' => $hasta . ' 23:59:59']);
        $operarios = $stmtU->fetchAll();

        // Partes completadas por día (últimos 30 días fijo, para tendencia)
        $stmtD = $db->query("
            SELECT DATE(fecha_completada) AS dia,
                   COUNT(*) AS completadas
            FROM proyecto_partes
            WHERE estado_fabricacion = 'completada'
              AND fecha_completada >= CURRENT_DATE - INTERVAL '30 days'
            GROUP BY DATE(fecha_completada)
            ORDER BY dia");
        $porDia = $stmtD->fetchAll();

        jsonResponse(['ok' => true, 'areas' => $areas, 'operarios' => $operarios, 'por_dia' => $porDia]);

    case 'alertas_generar':
        // Generar alertas automáticas
        $generadas = 0;

        // 1. Proyectos retrasados
        $stmt = $db->query("
            SELECT id, nombre FROM proyectos
            WHERE activo AND estado NOT IN ('completado','cancelado')
              AND fecha_entrega_estimada < CURRENT_DATE");
        foreach ($stmt->fetchAll() as $p) {
            $existe = $db->prepare("SELECT id FROM alertas WHERE referencia_tipo='proyecto' AND referencia_id=:id AND tipo='retraso_proyecto' AND leida=FALSE");
            $existe->execute([':id' => $p['id']]);
            if (!$existe->fetch()) {
                $db->prepare("INSERT INTO alertas (tipo, referencia_tipo, referencia_id, mensaje)
                    VALUES ('retraso_proyecto','proyecto',:id,:msg)")->execute([
                    ':id'  => $p['id'],
                    ':msg' => "Proyecto {$p['nombre']} tiene retraso en la entrega",
                ]);
                $generadas++;
            }
        }

        // 2. Stock crítico
        $stmt = $db->query("SELECT id, nombre FROM productos WHERE activo AND stock_actual<=stock_minimo AND stock_minimo>0");
        foreach ($stmt->fetchAll() as $p) {
            $existe = $db->prepare("SELECT id FROM alertas WHERE referencia_tipo='producto' AND referencia_id=:id AND tipo='stock_minimo' AND leida=FALSE");
            $existe->execute([':id' => $p['id']]);
            if (!$existe->fetch()) {
                $db->prepare("INSERT INTO alertas (tipo, referencia_tipo, referencia_id, mensaje)
                    VALUES ('stock_minimo','producto',:id,:msg)")->execute([
                    ':id'  => $p['id'],
                    ':msg' => "Producto {$p['nombre']} está en stock crítico",
                ]);
                $generadas++;
            }
        }

        // 3. Garantías por vencer (próximos 30 días)
        $stmt = $db->query("
            SELECT m.id, m.modelo, cl.razon_social
            FROM maquinarias m JOIN clientes cl ON cl.id=m.cliente_id
            WHERE m.garantia_hasta BETWEEN CURRENT_DATE AND CURRENT_DATE + INTERVAL '30 days'");
        foreach ($stmt->fetchAll() as $m) {
            $existe = $db->prepare("SELECT id FROM alertas WHERE referencia_tipo='maquinaria' AND referencia_id=:id AND tipo='garantia_vence' AND leida=FALSE");
            $existe->execute([':id' => $m['id']]);
            if (!$existe->fetch()) {
                $db->prepare("INSERT INTO alertas (tipo, referencia_tipo, referencia_id, mensaje)
                    VALUES ('garantia_vence','maquinaria',:id,:msg)")->execute([
                    ':id'  => $m['id'],
                    ':msg' => "Garantía de {$m['modelo']} ({$m['razon_social']}) vence en los próximos 30 días",
                ]);
                $generadas++;
            }
        }

        jsonResponse(['ok' => true, 'generadas' => $generadas]);

    default:
        jsonResponse(['error' => 'Acción no reconocida.'], 400);

}} catch (PDOException $e) {
    jsonResponse(['error' => 'Error BD: ' . $e->getMessage()], 500);
}

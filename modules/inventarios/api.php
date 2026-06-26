<?php
ini_set('display_errors', 0);
error_reporting(0);
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
startAppSession();
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

try { switch ($action) {

    // ================================================================
    // DASHBOARD
    // ================================================================
    case 'dashboard':
        $total_productos   = $db->query("SELECT COUNT(*) FROM productos WHERE activo")->fetchColumn();
        $stock_critico     = $db->query("SELECT COUNT(*) FROM productos WHERE activo AND stock_actual <= stock_minimo AND stock_minimo > 0")->fetchColumn();
        $valor_inventario  = $db->query("SELECT COALESCE(SUM(stock_actual * precio_promedio),0) FROM productos WHERE activo")->fetchColumn();
        $sin_stock         = $db->query("SELECT COUNT(*) FROM productos WHERE activo AND stock_actual <= 0")->fetchColumn();
        $movimientos_hoy   = $db->query("SELECT COUNT(*) FROM kardex WHERE DATE(fecha)=CURRENT_DATE")->fetchColumn();

        $stmt = $db->query("
            SELECT p.codigo, p.nombre, p.stock_actual, p.stock_minimo, p.unidad,
                   c.nombre AS categoria
            FROM productos p
            LEFT JOIN categorias_producto c ON c.id = p.categoria_id
            WHERE p.activo AND p.stock_actual <= p.stock_minimo AND p.stock_minimo > 0
            ORDER BY (p.stock_actual::float / NULLIF(p.stock_minimo,0)) ASC LIMIT 10");
        $criticos = $stmt->fetchAll();

        $stmt2 = $db->query("
            SELECT k.tipo, COUNT(*) AS cnt, COALESCE(SUM(k.cantidad * k.precio_unitario),0) AS valor
            FROM kardex k WHERE DATE(k.fecha)>=CURRENT_DATE - INTERVAL '30 days'
            GROUP BY k.tipo");
        $movs_30d = $stmt2->fetchAll();

        jsonResponse([
            'ok'               => true,
            'total_productos'  => (int)$total_productos,
            'stock_critico'    => (int)$stock_critico,
            'valor_inventario' => (float)$valor_inventario,
            'sin_stock'        => (int)$sin_stock,
            'movimientos_hoy'  => (int)$movimientos_hoy,
            'criticos'         => $criticos,
            'movs_30d'         => $movs_30d,
        ]);

    // ================================================================
    // PRODUCTOS
    // ================================================================
    case 'productos_listar':
        $where  = ['p.activo = TRUE'];
        $params = [];
        if (!empty($input['q'])) {
            $where[] = "(p.codigo ILIKE :q OR p.nombre ILIKE :q OR p.descripcion ILIKE :q)";
            $params[':q'] = '%' . $input['q'] . '%';
        }
        if (!empty($input['categoria_id'])) {
            $where[] = "p.categoria_id = :cat";
            $params[':cat'] = (int)$input['categoria_id'];
        }
        if (isset($input['es_repuesto']) && $input['es_repuesto'] !== '') {
            $where[] = "p.es_repuesto = :rep";
            $params[':rep'] = filter_var($input['es_repuesto'], FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
        }
        if (!empty($input['stock_critico'])) {
            $where[] = "p.stock_actual <= p.stock_minimo AND p.stock_minimo > 0";
        }
        $stmt = $db->prepare("
            SELECT p.*, c.nombre AS categoria_nombre
            FROM productos p
            LEFT JOIN categorias_producto c ON c.id = p.categoria_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY p.nombre");
        $stmt->execute($params);
        jsonResponse(['ok' => true, 'productos' => $stmt->fetchAll()]);

    case 'producto_obtener':
        $stmt = $db->prepare("
            SELECT p.*, c.nombre AS categoria_nombre
            FROM productos p
            LEFT JOIN categorias_producto c ON c.id = p.categoria_id
            WHERE p.id=:id");
        $stmt->execute([':id' => (int)$input['id']]);
        jsonResponse(['ok' => true, 'producto' => $stmt->fetch()]);

    case 'producto_guardar':
        if (empty($input['nombre'])) jsonResponse(['error' => 'El nombre es obligatorio.'], 400);

        // Handle image upload
        $imagen_nueva   = null;
        $remove_imagen  = !empty($input['remove_imagen']);
        $upload_dir     = dirname(__DIR__, 2) . '/assets/uploads/materiales/';

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['imagen'];
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','gif','webp']))
                jsonResponse(['error' => 'Formato de imagen no valido (jpg, png, gif, webp).'], 400);
            if ($file['size'] > 2 * 1024 * 1024)
                jsonResponse(['error' => 'La imagen no puede superar 2 MB.'], 400);
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $filename = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (!move_uploaded_file($file['tmp_name'], $upload_dir . $filename))
                jsonResponse(['error' => 'Error al guardar la imagen.'], 500);
            $imagen_nueva = $filename;
        }

        if (!empty($input['id'])) {
            $id = (int)$input['id'];
            // Verificar codigo unico
            $stmt = $db->prepare("SELECT id FROM productos WHERE codigo=:cod AND id!=:id");
            $stmt->execute([':cod' => trim($input['codigo']), ':id' => $id]);
            if ($stmt->fetch()) jsonResponse(['error' => 'El codigo ya existe.'], 400);

            // Determine imagen SQL fragment
            $imagen_sql    = '';
            $imagen_params = [];
            if ($imagen_nueva) {
                // Delete old image file
                $old = $db->prepare("SELECT imagen FROM productos WHERE id=:id");
                $old->execute([':id' => $id]);
                $old_img = $old->fetchColumn();
                if ($old_img) @unlink($upload_dir . $old_img);
                $imagen_sql           = ', imagen=:img';
                $imagen_params[':img'] = $imagen_nueva;
            } elseif ($remove_imagen) {
                $old = $db->prepare("SELECT imagen FROM productos WHERE id=:id");
                $old->execute([':id' => $id]);
                $old_img = $old->fetchColumn();
                if ($old_img) @unlink($upload_dir . $old_img);
                $imagen_sql = ', imagen=NULL';
            }

            $db->prepare("UPDATE productos SET
                codigo=:cod, nombre=:nom, descripcion=:desc,
                categoria_id=:cat, unidad=:uni,
                stock_minimo=:smin, stock_maximo=:smax,
                precio_venta=:pv,
                metodo_valuacion=:met, es_repuesto=:rep,
                codigo_interno=:codigo_interno,
                codigo_barras=:codigo_barras,
                codigo_sunat=:codigo_sunat,
                unidad_codigo=:unidad_codigo,
                afectacion_igv_codigo=:afectacion_igv_codigo,
                porcentaje_igv=:porcentaje_igv,
                incluye_igv=:incluye_igv,
                product_type=:product_type
                {$imagen_sql},
                updated_at=NOW()
                WHERE id=:id")->execute(array_merge([
                ':id'   => $id,
                ':cod'  => trim($input['codigo']),
                ':nom'  => trim($input['nombre']),
                ':desc' => ($input['descripcion'] ?? null) ?: null,
                ':cat'  => ($input['categoria_id'] ?? null) ?: null,
                ':uni'  => $input['unidad'] ?? 'unidad',
                ':smin' => (float)($input['stock_minimo'] ?? 0),
                ':smax' => (float)($input['stock_maximo'] ?? 0),
                ':pv'   => (float)($input['precio_venta'] ?? 0),
                ':met'  => $input['metodo_valuacion'] ?? 'promedio',
                ':rep'  => (int)filter_var($input['es_repuesto'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ':codigo_interno' => ($input['codigo_interno'] ?? null) ?: trim($input['codigo']),
                ':codigo_barras' => ($input['codigo_barras'] ?? null) ?: null,
                ':codigo_sunat' => ($input['codigo_sunat'] ?? null) ?: '00000000',
                ':unidad_codigo' => ($input['unidad_codigo'] ?? null) ?: 'NIU',
                ':afectacion_igv_codigo' => ($input['afectacion_igv_codigo'] ?? null) ?: '10',
                ':porcentaje_igv' => (float)($input['porcentaje_igv'] ?? 18),
                ':incluye_igv' => (int)filter_var($input['incluye_igv'] ?? true, FILTER_VALIDATE_BOOLEAN),
                ':product_type' => ($input['product_type'] ?? null) ?: 'product',
            ], $imagen_params));

            $db->prepare("
                UPDATE productos p
                SET unidad_id = u.id
                FROM fe_unidades u
                WHERE p.id = :id
                  AND u.codigo = COALESCE(NULLIF(:unidad_codigo, ''), 'NIU')
            ")->execute([
                ':id' => $id,
                ':unidad_codigo' => ($input['unidad_codigo'] ?? null) ?: 'NIU',
            ]);

            $db->prepare("
                UPDATE productos p
                SET afectacion_igv_id = a.id
                FROM fe_tipos_afectacion_igv a
                WHERE p.id = :id
                  AND a.codigo = COALESCE(NULLIF(:afectacion, ''), '10')
            ")->execute([
                ':id' => $id,
                ':afectacion' => ($input['afectacion_igv_codigo'] ?? null) ?: '10',
            ]);
        } else {
            $codigo = trim($input['codigo'] ?? '');
            if (!$codigo) $codigo = generarCodigo('MAT', 'productos', 'codigo');
            $stmt = $db->prepare("SELECT id FROM productos WHERE codigo=:cod");
            $stmt->execute([':cod' => $codigo]);
            if ($stmt->fetch()) $codigo = generarCodigo('MAT', 'productos', 'codigo');
            $input['codigo'] = $codigo;

            $stmt = $db->prepare("INSERT INTO productos
                (codigo, nombre, descripcion, categoria_id, unidad,
                 stock_minimo, stock_maximo, precio_venta, metodo_valuacion, es_repuesto, imagen,
                 codigo_interno, codigo_barras, codigo_sunat, unidad_codigo,
                 afectacion_igv_codigo, porcentaje_igv, incluye_igv, product_type)
                VALUES (:cod,:nom,:desc,:cat,:uni,:smin,:smax,:pv,:met,:rep,:img,
                        :codigo_interno,:codigo_barras,:codigo_sunat,:unidad_codigo,
                        :afectacion_igv_codigo,:porcentaje_igv,:incluye_igv,:product_type) RETURNING id");
            $stmt->execute([
                ':cod'  => trim($input['codigo']),
                ':nom'  => trim($input['nombre']),
                ':desc' => ($input['descripcion'] ?? null) ?: null,
                ':cat'  => ($input['categoria_id'] ?? null) ?: null,
                ':uni'  => $input['unidad'] ?? 'unidad',
                ':smin' => (float)($input['stock_minimo'] ?? 0),
                ':smax' => (float)($input['stock_maximo'] ?? 0),
                ':pv'   => (float)($input['precio_venta'] ?? 0),
                ':met'  => $input['metodo_valuacion'] ?? 'promedio',
                ':rep'  => (int)filter_var($input['es_repuesto'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ':img'  => $imagen_nueva,
                ':codigo_interno' => ($input['codigo_interno'] ?? null) ?: trim($input['codigo']),
                ':codigo_barras' => ($input['codigo_barras'] ?? null) ?: null,
                ':codigo_sunat' => ($input['codigo_sunat'] ?? null) ?: '00000000',
                ':unidad_codigo' => ($input['unidad_codigo'] ?? null) ?: 'NIU',
                ':afectacion_igv_codigo' => ($input['afectacion_igv_codigo'] ?? null) ?: '10',
                ':porcentaje_igv' => (float)($input['porcentaje_igv'] ?? 18),
                ':incluye_igv' => (int)filter_var($input['incluye_igv'] ?? true, FILTER_VALIDATE_BOOLEAN),
                ':product_type' => ($input['product_type'] ?? null) ?: 'product',
            ]);
            $id = (int)$stmt->fetchColumn();

            $db->prepare("
                UPDATE productos p
                SET unidad_id = u.id
                FROM fe_unidades u
                WHERE p.id = :id
                  AND u.codigo = COALESCE(NULLIF(:unidad_codigo, ''), 'NIU')
            ")->execute([
                ':id' => $id,
                ':unidad_codigo' => ($input['unidad_codigo'] ?? null) ?: 'NIU',
            ]);

            $db->prepare("
                UPDATE productos p
                SET afectacion_igv_id = a.id
                FROM fe_tipos_afectacion_igv a
                WHERE p.id = :id
                  AND a.codigo = COALESCE(NULLIF(:afectacion, ''), '10')
            ")->execute([
                ':id' => $id,
                ':afectacion' => ($input['afectacion_igv_codigo'] ?? null) ?: '10',
            ]);
        }
        jsonResponse(['ok' => true, 'id' => $id]);

    case 'producto_toggle':
        $db->prepare("UPDATE productos SET activo=NOT activo WHERE id=:id")
           ->execute([':id' => (int)($input['id'] ?? 0)]);
        jsonResponse(['ok' => true]);

    // ================================================================
    // AJUSTE MANUAL DE STOCK (Kardex)
    // ================================================================
    case 'ajuste_stock':
        $prod_id = (int)($input['producto_id'] ?? 0);
        $tipo    = $input['tipo'] ?? 'entrada'; // entrada, salida, ajuste
        $qty     = (float)($input['cantidad'] ?? 0);
        $obs     = ($input['observaciones'] ?? null) ?: null;

        if (!$prod_id) jsonResponse(['error' => 'Producto requerido.'], 400);
        if ($qty <= 0)  jsonResponse(['error' => 'Cantidad debe ser positiva.'], 400);
        if (!in_array($tipo, ['entrada', 'salida', 'ajuste'])) jsonResponse(['error' => 'Tipo invalido.'], 400);

        $db->beginTransaction();
        $stmt = $db->prepare("SELECT stock_actual, stock_minimo, precio_promedio FROM productos WHERE id=:id FOR UPDATE");
        $stmt->execute([':id' => $prod_id]);
        $pr = $stmt->fetch();
        $stock_ant = (float)$pr['stock_actual'];
        $pp_ant    = (float)$pr['precio_promedio'];
        $pu        = (float)($input['precio_unitario'] ?? $pp_ant);

        if ($tipo === 'salida') {
            if ($stock_ant < $qty) jsonResponse(['error' => 'Stock insuficiente.'], 400);
            $nuevo_stock = $stock_ant - $qty;
            $nuevo_pp    = $pp_ant; // precio promedio no cambia en salida
        } elseif ($tipo === 'ajuste') {
            // ajuste: se establece el stock en qty
            $nuevo_stock = $qty;
            $nuevo_pp    = $pu ?: $pp_ant;
        } else {
            // entrada
            $nuevo_stock = $stock_ant + $qty;
            $nuevo_pp    = $nuevo_stock > 0
                ? (($stock_ant * $pp_ant) + ($qty * $pu)) / $nuevo_stock
                : $pu;
        }

        $db->prepare("UPDATE productos SET stock_actual=:sa, precio_promedio=:pp, updated_at=NOW() WHERE id=:id")
           ->execute([':sa' => $nuevo_stock, ':pp' => $nuevo_pp, ':id' => $prod_id]);

        $db->prepare("INSERT INTO kardex
            (producto_id, tipo, referencia_tipo, cantidad, precio_unitario,
             saldo_cantidad, saldo_valor, observaciones, usuario_id)
            VALUES (:pid, :tipo, 'ajuste_manual', :qty, :pu, :sq, :sv, :obs, :uid)")->execute([
            ':pid'  => $prod_id,
            ':tipo' => $tipo,
            ':qty'  => $qty,
            ':pu'   => $pu,
            ':sq'   => $nuevo_stock,
            ':sv'   => $nuevo_stock * $nuevo_pp,
            ':obs'  => $obs,
            ':uid'  => null,
        ]);

        // Eliminar alerta de stock critico si se resolvio.
        if ($nuevo_stock > (float)$pr['stock_minimo']) {
            $db->prepare("UPDATE alertas SET leida=TRUE WHERE referencia_tipo='producto' AND referencia_id=:id AND tipo='stock_minimo'")
               ->execute([':id' => $prod_id]);
        }

        $db->commit();
        jsonResponse(['ok' => true, 'nuevo_stock' => $nuevo_stock, 'precio_promedio' => $nuevo_pp]);

    // ================================================================
    // KARDEX
    // ================================================================
    case 'kardex_listar':
        $prod_id = (int)($input['producto_id'] ?? 0);
        if (!$prod_id) jsonResponse(['error' => 'Producto requerido.'], 400);

        $where  = ['k.producto_id = :pid'];
        $params = [':pid' => $prod_id];
        if (!empty($input['desde'])) { $where[] = "k.fecha >= :desde"; $params[':desde'] = $input['desde'] . ' 00:00:00'; }
        if (!empty($input['hasta'])) { $where[] = "k.fecha <= :hasta"; $params[':hasta'] = $input['hasta'] . ' 23:59:59'; }
        if (!empty($input['tipo']))  { $where[] = "k.tipo = :tipo";    $params[':tipo']  = $input['tipo']; }

        $wClause = implode(' AND ', $where);

        $stmtStats = $db->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN k.tipo='entrada' THEN k.cantidad ELSE 0 END), 0) AS ent_qty,
                COALESCE(SUM(CASE WHEN k.tipo='entrada' THEN k.cantidad * k.precio_unitario ELSE 0 END), 0) AS ent_val,
                COALESCE(SUM(CASE WHEN k.tipo='salida'  THEN k.cantidad ELSE 0 END), 0) AS sal_qty,
                COALESCE(SUM(CASE WHEN k.tipo='salida'  THEN k.cantidad * k.precio_unitario ELSE 0 END), 0) AS sal_val,
                COALESCE(SUM(CASE WHEN k.tipo='ajuste'  THEN ABS(k.cantidad) ELSE 0 END), 0) AS adj_qty,
                COUNT(*) AS total
            FROM kardex k WHERE $wClause");
        $stmtStats->execute($params);
        $stats = $stmtStats->fetch();

        $stmt = $db->prepare("
            SELECT k.*, u.nombre AS usuario_nombre
            FROM kardex k
            LEFT JOIN usuarios u ON u.id = k.usuario_id
            WHERE $wClause
            ORDER BY k.fecha DESC LIMIT 200");
        $stmt->execute($params);
        jsonResponse(['ok' => true, 'movimientos' => $stmt->fetchAll(), 'stats' => $stats]);

    // ================================================================
    // CATEGORÃAS
    // ================================================================
    case 'categorias_listar':
        $stmt = $db->query("
            SELECT c.*, COUNT(p.id) AS total_productos
            FROM categorias_producto c
            LEFT JOIN productos p ON p.categoria_id = c.id AND p.activo
            WHERE c.activo
            GROUP BY c.id ORDER BY c.nombre");
        jsonResponse(['ok' => true, 'categorias' => $stmt->fetchAll()]);

    case 'categoria_guardar':
        if (empty($input['nombre'])) jsonResponse(['error' => 'El nombre es obligatorio.'], 400);
        if (!empty($input['id'])) {
            $db->prepare("UPDATE categorias_producto SET nombre=:nom, descripcion=:desc WHERE id=:id")
               ->execute([':id' => (int)$input['id'], ':nom' => trim($input['nombre']), ':desc' => ($input['descripcion'] ?? null) ?: null]);
        } else {
            $db->prepare("INSERT INTO categorias_producto (nombre, descripcion) VALUES (:nom, :desc)")
               ->execute([':nom' => trim($input['nombre']), ':desc' => ($input['descripcion'] ?? null) ?: null]);
        }
        jsonResponse(['ok' => true]);

    case 'categoria_eliminar':
        $id = (int)($input['id'] ?? 0);
        $uso = $db->prepare("SELECT COUNT(*) FROM productos WHERE categoria_id=:id AND activo");
        $uso->execute([':id' => $id]);
        if ($uso->fetchColumn() > 0) jsonResponse(['error' => 'No se puede eliminar: tiene productos activos.'], 400);
        $db->prepare("DELETE FROM categorias_producto WHERE id=:id")->execute([':id' => $id]);
        jsonResponse(['ok' => true]);

    // ================================================================
    // COMBOS
    // ================================================================
    case 'generar_codigo':
        jsonResponse(['ok' => true, 'codigo' => generarCodigo('MAT', 'productos', 'codigo')]);

    case 'combos':
        $cats = $db->query("SELECT id, nombre FROM categorias_producto WHERE activo ORDER BY nombre")->fetchAll();
        $unidades = $db->query("SELECT id, codigo, descripcion FROM fe_unidades WHERE estado ORDER BY codigo")->fetchAll();
        $afectaciones = $db->query("SELECT id, codigo, descripcion FROM fe_tipos_afectacion_igv WHERE estado ORDER BY codigo")->fetchAll();
        jsonResponse(['ok' => true, 'categorias' => $cats, 'unidades' => $unidades, 'afectaciones' => $afectaciones]);

    default:
        jsonResponse(['error' => 'Accion no reconocida.'], 400);

}} catch (PDOException $e) {
    jsonResponse(['error' => 'Error BD: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}

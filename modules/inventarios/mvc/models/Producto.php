<?php
/**
 * MODELO — Producto (materiales)
 * Acceso a datos y reglas de negocio de productos/materiales y el dashboard de inventario.
 * No imprime HTML ni maneja HTTP. Ver ARQUITECTURA_MVC.md
 */
require_once __DIR__ . '/../../estante/models/Estante.php';

class Producto
{
    /** Métricas y listas del dashboard de inventario. */
    public static function dashboard(PDO $db): array
    {
        $total_productos  = (int)$db->query("SELECT COUNT(*) FROM productos WHERE activo")->fetchColumn();
        $stock_critico    = (int)$db->query("SELECT COUNT(*) FROM productos WHERE activo AND stock_actual <= stock_minimo AND stock_minimo > 0")->fetchColumn();
        $valor_inventario = (float)$db->query("SELECT COALESCE(SUM(stock_actual * precio_promedio),0) FROM productos WHERE activo")->fetchColumn();
        $sin_stock        = (int)$db->query("SELECT COUNT(*) FROM productos WHERE activo AND stock_actual <= 0")->fetchColumn();
        $movimientos_hoy  = (int)$db->query("SELECT COUNT(*) FROM kardex WHERE DATE(fecha)=CURRENT_DATE")->fetchColumn();

        $criticos = $db->query("
            SELECT p.codigo, p.nombre, p.stock_actual, p.stock_minimo, p.unidad, c.nombre AS categoria
            FROM productos p
            LEFT JOIN categorias_producto c ON c.id = p.categoria_id
            WHERE p.activo AND p.stock_actual <= p.stock_minimo AND p.stock_minimo > 0
            ORDER BY (p.stock_actual::float / NULLIF(p.stock_minimo,0)) ASC LIMIT 10")->fetchAll();

        $movs_30d = $db->query("
            SELECT k.tipo, COUNT(*) AS cnt, COALESCE(SUM(k.cantidad * k.precio_unitario),0) AS valor
            FROM kardex k WHERE DATE(k.fecha) >= CURRENT_DATE - INTERVAL '30 days'
            GROUP BY k.tipo")->fetchAll();

        return [
            'total_productos'  => $total_productos,
            'stock_critico'    => $stock_critico,
            'valor_inventario' => $valor_inventario,
            'sin_stock'        => $sin_stock,
            'movimientos_hoy'  => $movimientos_hoy,
            'criticos'         => $criticos,
            'movs_30d'         => $movs_30d,
        ];
    }

    /** Lista productos activos con filtros opcionales (q, categoria_id, es_repuesto, stock_critico). */
    public static function listar(PDO $db, array $f): array
    {
        $where  = ['p.activo = TRUE'];
        $params = [];
        if (!empty($f['q'])) {
            $where[] = "(p.codigo ILIKE :q OR p.nombre ILIKE :q OR p.descripcion ILIKE :q OR p.codigo_barras ILIKE :q)";
            $params[':q'] = '%' . $f['q'] . '%';
        }
        if (!empty($f['categoria_id'])) {
            $where[] = "p.categoria_id = :cat";
            $params[':cat'] = (int)$f['categoria_id'];
        }
        if (isset($f['es_repuesto']) && $f['es_repuesto'] !== '') {
            $where[] = "p.es_repuesto = :rep";
            $params[':rep'] = filter_var($f['es_repuesto'], FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
        }
        if (!empty($f['stock_critico'])) {
            $where[] = "p.stock_actual <= p.stock_minimo AND p.stock_minimo > 0";
        }
        $stmt = $db->prepare("
            SELECT p.*, c.nombre AS categoria_nombre
            FROM productos p
            LEFT JOIN categorias_producto c ON c.id = p.categoria_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY p.nombre");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Un producto por id con el nombre de su categoría. */
    public static function obtener(PDO $db, int $id)
    {
        $stmt = $db->prepare("
            SELECT p.*, c.nombre AS categoria_nombre
            FROM productos p
            LEFT JOIN categorias_producto c ON c.id = p.categoria_id
            WHERE p.id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /** True si existe el código (opcionalmente excluyendo un id). */
    public static function codigoExiste(PDO $db, string $codigo, int $exceptId = 0): bool
    {
        $sql = "SELECT id FROM productos WHERE codigo = :cod" . ($exceptId ? " AND id != :id" : "");
        $stmt = $db->prepare($sql);
        $p = [':cod' => $codigo];
        if ($exceptId) $p[':id'] = $exceptId;
        $stmt->execute($p);
        return (bool)$stmt->fetch();
    }

    /** Nombre del archivo de imagen actual de un producto (o null). */
    public static function imagen(PDO $db, int $id): ?string
    {
        $stmt = $db->prepare("SELECT imagen FROM productos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $v = $stmt->fetchColumn();
        return $v !== false && $v !== null && $v !== '' ? (string)$v : null;
    }

    /**
     * Inserta o actualiza un producto. Devuelve el id.
     * $imagenNueva: nombre de archivo ya movido (o null). $removeImagen: quitar imagen (solo update).
     */
    public static function guardar(PDO $db, array $in, ?string $imagenNueva, bool $removeImagen): int
    {
        $afect = ($in['afectacion_igv_codigo'] ?? null) ?: '20';
        $pct   = $afect === '10' ? 18 : 0; // exonerado/inafecto/exportacion => 0%

        if (!empty($in['id'])) {
            $id = (int)$in['id'];
            $imagen_sql = ''; $imagen_params = [];
            if ($imagenNueva) {
                $imagen_sql = ', imagen=:img'; $imagen_params[':img'] = $imagenNueva;
            } elseif ($removeImagen) {
                $imagen_sql = ', imagen=NULL';
            }

            $db->prepare("UPDATE productos SET
                codigo=:cod, nombre=:nom, descripcion=:desc,
                categoria_id=:cat, unidad=:uni,
                stock_minimo=:smin,
                precio_venta=:pv,
                metodo_valuacion=:met, es_repuesto=:rep,
                codigo_interno=:codigo_interno,
                codigo_barras=:codigo_barras,
                codigo_sunat=:codigo_sunat,
                unidad_codigo=:unidad_codigo,
                afectacion_igv_codigo=:afectacion_igv_codigo,
                porcentaje_igv=:porcentaje_igv,
                incluye_igv=:incluye_igv,
                product_type=:product_type,
                precio_compra=:pc,
                celda_estante=:celda_estante
                {$imagen_sql},
                updated_at=NOW()
                WHERE id=:id")->execute(array_merge([
                ':id'   => $id,
                ':cod'  => trim($in['codigo']),
                ':nom'  => trim($in['nombre']),
                ':desc' => ($in['descripcion'] ?? null) ?: null,
                ':cat'  => ($in['categoria_id'] ?? null) ?: null,
                ':uni'  => $in['unidad'] ?? 'unidad',
                ':smin' => (int)round((float)($in['stock_minimo'] ?? 0)),
                ':pv'   => (float)($in['precio_venta'] ?? 0),
                ':met'  => $in['metodo_valuacion'] ?? 'promedio',
                ':rep'  => (int)filter_var($in['es_repuesto'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ':codigo_interno' => ($in['codigo_interno'] ?? null) ?: trim($in['codigo']),
                ':codigo_barras' => ($in['codigo_barras'] ?? null) ?: null,
                ':codigo_sunat' => ($in['codigo_sunat'] ?? null) ?: '00000000',
                ':unidad_codigo' => ($in['unidad_codigo'] ?? null) ?: 'NIU',
                ':afectacion_igv_codigo' => $afect,
                ':porcentaje_igv' => $pct,
                ':incluye_igv' => (int)filter_var($in['incluye_igv'] ?? true, FILTER_VALIDATE_BOOLEAN),
                ':product_type' => ($in['product_type'] ?? null) ?: 'product',
                ':pc' => (float)($in['precio_compra'] ?? 0),
                ':celda_estante' => Estante::normalizarCelda($in['celda_estante'] ?? null),
            ], $imagen_params));
        } else {
            $codigo = trim($in['codigo'] ?? '');
            if (!$codigo) $codigo = generarCodigo('MAT', 'productos', 'codigo');
            if (self::codigoExiste($db, $codigo)) $codigo = generarCodigo('MAT', 'productos', 'codigo');
            $in['codigo'] = $codigo;

            $stmt = $db->prepare("INSERT INTO productos
                (codigo, nombre, descripcion, categoria_id, unidad,
                 stock_minimo, precio_venta, precio_compra, metodo_valuacion, es_repuesto, imagen,
                 codigo_interno, codigo_barras, codigo_sunat, unidad_codigo,
                 afectacion_igv_codigo, porcentaje_igv, incluye_igv, product_type, celda_estante)
                VALUES (:cod,:nom,:desc,:cat,:uni,:smin,:pv,:pc,:met,:rep,:img,
                        :codigo_interno,:codigo_barras,:codigo_sunat,:unidad_codigo,
                        :afectacion_igv_codigo,:porcentaje_igv,:incluye_igv,:product_type,:celda_estante) RETURNING id");
            $stmt->execute([
                ':cod'  => trim($in['codigo']),
                ':nom'  => trim($in['nombre']),
                ':desc' => ($in['descripcion'] ?? null) ?: null,
                ':cat'  => ($in['categoria_id'] ?? null) ?: null,
                ':uni'  => $in['unidad'] ?? 'unidad',
                ':smin' => (int)round((float)($in['stock_minimo'] ?? 0)),
                ':pv'   => (float)($in['precio_venta'] ?? 0),
                ':met'  => $in['metodo_valuacion'] ?? 'promedio',
                ':rep'  => (int)filter_var($in['es_repuesto'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ':img'  => $imagenNueva,
                ':codigo_interno' => ($in['codigo_interno'] ?? null) ?: trim($in['codigo']),
                ':codigo_barras' => ($in['codigo_barras'] ?? null) ?: null,
                ':codigo_sunat' => ($in['codigo_sunat'] ?? null) ?: '00000000',
                ':unidad_codigo' => ($in['unidad_codigo'] ?? null) ?: 'NIU',
                ':afectacion_igv_codigo' => $afect,
                ':porcentaje_igv' => $pct,
                ':incluye_igv' => (int)filter_var($in['incluye_igv'] ?? true, FILTER_VALIDATE_BOOLEAN),
                ':product_type' => ($in['product_type'] ?? null) ?: 'product',
                ':pc' => (float)($in['precio_compra'] ?? 0),
                ':celda_estante' => Estante::normalizarCelda($in['celda_estante'] ?? null),
            ]);
            $id = (int)$stmt->fetchColumn();
        }

        // Vincular IDs FE (unidad / afectación) a partir de sus códigos.
        $unidad_codigo = ($in['unidad_codigo'] ?? null) ?: 'NIU';
        $db->prepare("
            UPDATE productos p SET unidad_id = u.id
            FROM fe_unidades u
            WHERE p.id = :id AND u.codigo = COALESCE(NULLIF(:unidad_codigo, ''), 'NIU')
        ")->execute([':id' => $id, ':unidad_codigo' => $unidad_codigo]);

        $db->prepare("
            UPDATE productos p SET afectacion_igv_id = a.id
            FROM fe_tipos_afectacion_igv a
            WHERE p.id = :id AND a.codigo = COALESCE(NULLIF(:afectacion, ''), '20')
        ")->execute([':id' => $id, ':afectacion' => $afect]);

        return $id;
    }

    /** Activa/desactiva (borrado lógico) un producto. */
    public static function toggle(PDO $db, int $id): void
    {
        $db->prepare("UPDATE productos SET activo = NOT activo WHERE id = :id")->execute([':id' => $id]);
    }

    /** Código automático: abreviado del nombre si viene, si no el secuencial MAT-AAAA-NNNN. */
    public static function generarCodigoAuto(PDO $db, string $nombre = ''): string
    {
        $nombre = trim($nombre);
        return $nombre !== ''
            ? self::generarCodigoAbreviado($db, $nombre)
            : generarCodigo('MAT', 'productos', 'codigo');
    }

    /**
     * Código interno abreviado a partir del nombre (ej. "Carro" -> "CRRO"):
     * conserva primera y última letra, descarta vocales intermedias.
     */
    public static function generarCodigoAbreviado(PDO $db, string $nombre): string
    {
        $nombre = trim($nombre);
        if ($nombre === '') return generarCodigo('MAT', 'productos', 'codigo');

        $limpio = strtoupper($nombre);
        $translit = iconv('UTF-8', 'ASCII//TRANSLIT', $limpio);
        if ($translit !== false) $limpio = $translit;
        $limpio = preg_replace('/[^A-Z0-9]/', '', $limpio);
        if ($limpio === '') return generarCodigo('MAT', 'productos', 'codigo');

        $vocales = ['A', 'E', 'I', 'O', 'U'];
        $len = strlen($limpio);
        $abrev = '';
        for ($i = 0; $i < $len; $i++) {
            $ch = $limpio[$i];
            $esVocal = in_array($ch, $vocales, true);
            if (!$esVocal || $i === 0 || $i === $len - 1) $abrev .= $ch;
        }
        $abrev = substr($abrev, 0, 6);
        if (strlen($abrev) < 3) $abrev = substr($limpio, 0, 6);

        $codigo = $abrev;
        $stmt = $db->prepare("SELECT 1 FROM productos WHERE codigo = :cod");
        $i = 2;
        while (true) {
            $stmt->execute([':cod' => $codigo]);
            if (!$stmt->fetch()) break;
            $codigo = $abrev . $i;
            $i++;
        }
        return $codigo;
    }
}

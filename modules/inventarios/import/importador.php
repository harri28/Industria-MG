<?php
/**
 * Importación de materiales desde Excel (.xlsx) — módulo Inventario.
 * Lee el archivo con ZipArchive + SimpleXML (sin librerías externas) e inserta
 * categorías (desde Grupo), unidades de medida y productos.
 *
 * Columnas esperadas (fila 1 = encabezados):
 *   A Código · B SKU · C Grupo · D Material/Insumo · E Unidad
 *   F Stock Actual · G Stock Mínimo · H Precio Compra · I Precio Venta
 */

/** Texto de un nodo <si>/<is> de xlsx (puede venir en <t> o en varios <r><t>). */
function _xlsxTexto($si): string
{
    if ($si === null) return '';
    if (isset($si->t)) return (string)$si->t;
    $s = '';
    if (isset($si->r)) foreach ($si->r as $r) $s .= (string)$r->t;
    return $s;
}

/** Letra(s) de columna -> índice 0-based ('A'->0, 'B'->1, 'AA'->26). */
function _xlsxColIdx(string $ref): int
{
    $letters = strtoupper(preg_replace('/[0-9]+/', '', $ref));
    $n = 0;
    for ($i = 0, $len = strlen($letters); $i < $len; $i++) $n = $n * 26 + (ord($letters[$i]) - 64);
    return $n - 1;
}

/** Lee la primera hoja de un .xlsx y devuelve un array de filas (cada fila: array por índice de columna). */
function leerXlsx(string $path): array
{
    if (!class_exists('ZipArchive')) throw new Exception('La extensión ZIP de PHP no está disponible.');
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) throw new Exception('No se pudo abrir el archivo Excel.');

    // Cadenas compartidas
    $shared = [];
    $ssXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($ssXml !== false) {
        $xml = @simplexml_load_string($ssXml);
        if ($xml) foreach ($xml->si as $si) $shared[] = _xlsxTexto($si);
    }

    // Primera hoja (xl/worksheets/sheetN.xml)
    $sheetName = null;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $n = $zip->getNameIndex($i);
        if (preg_match('#^xl/worksheets/sheet\d+\.xml$#', $n)) { $sheetName = $n; break; }
    }
    if ($sheetName === null) { $zip->close(); throw new Exception('No se encontró ninguna hoja en el Excel.'); }
    $sheetXml = $zip->getFromName($sheetName);
    $zip->close();

    $xml = @simplexml_load_string($sheetXml);
    if (!$xml) throw new Exception('No se pudo leer el contenido del Excel.');

    $filas = [];
    foreach ($xml->sheetData->row as $row) {
        $cells = [];
        foreach ($row->c as $c) {
            $idx = _xlsxColIdx((string)$c['r']);
            $t = (string)$c['t'];
            if ($t === 's')              $val = $shared[(int)$c->v] ?? '';
            elseif ($t === 'inlineStr')  $val = _xlsxTexto($c->is);
            else                         $val = (string)$c->v;
            $cells[$idx] = $val;
        }
        $filas[] = $cells;
    }
    return $filas;
}

/** Convierte un valor de celda a número (float). */
function _num($v): float
{
    if ($v === null) return 0.0;
    $v = str_replace([',', ' '], ['', ''], trim((string)$v));
    return is_numeric($v) ? (float)$v : 0.0;
}

/** Normaliza el código de unidad del Excel al catálogo interno. */
function _normalizarUnidad(string $u): string
{
    $u = mb_strtolower(trim($u));
    $map = ['mt' => 'm', 'mts' => 'm', 'gl' => 'gln', 'gal' => 'gln', 'unidad' => 'und', 'uni' => 'und'];
    if ($u === '' || $u === '—' || $u === '-') return 'und';
    return $map[$u] ?? $u;
}

/** Nombre legible para una unidad nueva. */
function _nombreUnidad(string $cod): string
{
    $nombres = ['und' => 'Unidad', 'm' => 'Metro', 'cm' => 'Centímetro', 'kg' => 'Kilogramo',
                'gln' => 'Galón', 'lt' => 'Litro', 'pza' => 'Pieza'];
    return $nombres[$cod] ?? mb_strtoupper($cod);
}

/**
 * Importa los materiales del .xlsx a la BD.
 * Devuelve: ['creados','omitidos','cat_nuevas','uni_nuevas','errores','total'].
 */
function importarMateriales(PDO $db, string $path): array
{
    $filas = leerXlsx($path);
    if (count($filas) < 2) throw new Exception('El Excel no tiene filas de datos.');

    // Caches
    $catCache = [];
    foreach ($db->query("SELECT id, nombre FROM categorias_producto") as $c) $catCache[mb_strtolower($c['nombre'])] = (int)$c['id'];
    $uniCache = [];
    foreach ($db->query("SELECT codigo FROM unidades_medida") as $u) $uniCache[$u['codigo']] = true;

    $creados = 0; $omitidos = 0; $catNuevas = 0; $uniNuevas = 0; $errores = []; $total = 0;

    $sqlProd = $db->prepare("INSERT INTO productos
        (codigo, nombre, categoria_id, unidad, stock_actual, stock_minimo,
         precio_venta, precio_compra, precio_promedio, metodo_valuacion, es_repuesto,
         codigo_interno, codigo_barras, codigo_sunat, unidad_codigo,
         afectacion_igv_codigo, porcentaje_igv, incluye_igv, product_type)
        VALUES (:cod,:nom,:cat,:uni,:sa,:smin,:pv,:pc,:pp,'promedio',false,
                :ci,:cb,'00000000','NIU','20',0,true,'product') RETURNING id");
    $sqlKardex = $db->prepare("INSERT INTO kardex
        (producto_id, tipo, referencia_tipo, cantidad, precio_unitario, saldo_cantidad, saldo_valor, observaciones)
        VALUES (:pid,'ajuste','importacion',:q,:pu,:sq,:sv,'Carga inicial por importación')");

    $db->beginTransaction();
    try {
        $primera = true;
        foreach ($filas as $r) {
            if ($primera) { $primera = false; continue; } // encabezado
            $codigo    = trim((string)($r[0] ?? ''));
            $sku       = trim((string)($r[1] ?? ''));
            $grupo     = trim((string)($r[2] ?? ''));
            $nombre    = trim((string)($r[3] ?? ''));
            $unidadRaw = (string)($r[4] ?? '');
            $stock     = round(_num($r[5] ?? 0), 3);
            $smin      = round(_num($r[6] ?? 0), 3);
            $pcompra   = round(_num($r[7] ?? 0), 2);
            $pventa    = round(_num($r[8] ?? 0), 2);

            if ($codigo === '' && $nombre === '') continue;         // fila vacía
            if ($nombre === '') { $errores[] = "Fila sin nombre (código '$codigo')"; continue; }
            if ($codigo === '') { $errores[] = "Material '$nombre' sin código"; continue; }
            $total++;

            // Categoría (desde Grupo)
            $catId = null;
            if ($grupo !== '') {
                $k = mb_strtolower($grupo);
                if (!isset($catCache[$k])) {
                    $st = $db->prepare("INSERT INTO categorias_producto (nombre) VALUES (:n) RETURNING id");
                    $st->execute([':n' => $grupo]);
                    $catId = (int)$st->fetchColumn();
                    $catCache[$k] = $catId; $catNuevas++;
                } else $catId = $catCache[$k];
            }

            // Unidad de medida
            $uni = _normalizarUnidad($unidadRaw);
            if (!isset($uniCache[$uni])) {
                $db->prepare("INSERT INTO unidades_medida (codigo, nombre) VALUES (:c,:n) ON CONFLICT (codigo) DO NOTHING")
                   ->execute([':c' => $uni, ':n' => _nombreUnidad($uni)]);
                $uniCache[$uni] = true; $uniNuevas++;
            }

            // ¿Ya existe el código? -> omitir
            $ex = $db->prepare("SELECT 1 FROM productos WHERE codigo = :c");
            $ex->execute([':c' => $codigo]);
            if ($ex->fetchColumn()) { $omitidos++; continue; }

            $sqlProd->execute([
                ':cod' => $codigo, ':nom' => $nombre, ':cat' => $catId, ':uni' => $uni,
                ':sa' => $stock, ':smin' => $smin, ':pv' => $pventa, ':pc' => $pcompra, ':pp' => $pcompra,
                ':ci' => $codigo, ':cb' => ($sku !== '' ? $sku : null),
            ]);
            $pid = (int)$sqlProd->fetchColumn();

            if ($stock > 0) {
                $sqlKardex->execute([
                    ':pid' => $pid, ':q' => $stock, ':pu' => $pcompra,
                    ':sq' => $stock, ':sv' => round($stock * $pcompra, 2),
                ]);
            }
            $creados++;
        }

        // Vincular IDs FE (unidad / afectación) como hace producto_guardar
        $db->exec("UPDATE productos p SET afectacion_igv_id = a.id
                   FROM fe_tipos_afectacion_igv a WHERE a.codigo = p.afectacion_igv_codigo AND p.afectacion_igv_id IS NULL");
        $db->exec("UPDATE productos p SET unidad_id = u.id
                   FROM fe_unidades u WHERE u.codigo = p.unidad_codigo AND p.unidad_id IS NULL");

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

    return ['creados' => $creados, 'omitidos' => $omitidos, 'cat_nuevas' => $catNuevas,
            'uni_nuevas' => $uniNuevas, 'errores' => $errores, 'total' => $total];
}

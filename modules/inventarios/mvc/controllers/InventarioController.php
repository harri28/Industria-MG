<?php
/**
 * CONTROLADOR — Inventario
 * Enruta las acciones del módulo, invoca los Models y responde JSON.
 * No contiene SQL; sí maneja el borde HTTP (subida de archivos).
 * Ver ARQUITECTURA_MVC.md
 */
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../models/Kardex.php';
require_once __DIR__ . '/../models/Categoria.php';
require_once __DIR__ . '/../models/UnidadMedida.php';
require_once __DIR__ . '/../models/Catalogo.php';

class InventarioController
{
    /** Despacha una acción. Devuelve true si la manejó, false si no le corresponde. */
    public static function handle(string $action, array $input, PDO $db): bool
    {
        switch ($action) {
            case 'dashboard':             self::dashboard($db);               return true;
            case 'productos_listar':      self::productosListar($input, $db); return true;
            case 'producto_obtener':      self::productoObtener($input, $db); return true;
            case 'producto_guardar':      self::productoGuardar($input, $db); return true;
            case 'producto_toggle':       self::productoToggle($input, $db);  return true;
            case 'ajuste_stock':          self::ajusteStock($input, $db);     return true;
            case 'kardex_listar':         self::kardexListar($input, $db);    return true;
            case 'kardex_global':         self::kardexGlobal($input, $db);    return true;
            case 'categorias_listar':     self::categoriasListar($db);        return true;
            case 'categoria_guardar':     self::categoriaGuardar($input, $db); return true;
            case 'categoria_eliminar':    self::categoriaEliminar($input, $db); return true;
            case 'generar_codigo':        self::generarCodigo($input, $db);   return true;
            case 'combos':                self::combos($db);                  return true;
            case 'unidad_medida_guardar': self::unidadGuardar($input, $db);   return true;
            case 'unidad_medida_eliminar':self::unidadEliminar($input, $db);  return true;
            case 'importar_materiales':   self::importar($db);                return true;
            default:                      return false;
        }
    }

    public static function dashboard(PDO $db): void
    {
        jsonResponse(['ok' => true] + Producto::dashboard($db));
    }

    public static function productosListar(array $in, PDO $db): void
    {
        jsonResponse(['ok' => true, 'productos' => Producto::listar($db, $in)]);
    }

    public static function productoObtener(array $in, PDO $db): void
    {
        jsonResponse(['ok' => true, 'producto' => Producto::obtener($db, (int)($in['id'] ?? 0))]);
    }

    public static function productoGuardar(array $in, PDO $db): void
    {
        if (empty($in['nombre'])) jsonResponse(['error' => 'El nombre es obligatorio.'], 400);

        $imagen_nueva  = null;
        $remove_imagen = !empty($in['remove_imagen']);
        $upload_dir    = dirname(__DIR__, 4) . '/assets/uploads/materiales/';

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

        if (!empty($in['id'])) {
            $id = (int)$in['id'];
            if (Producto::codigoExiste($db, trim($in['codigo']), $id))
                jsonResponse(['error' => 'El codigo ya existe.'], 400);
            // Borrar la imagen anterior si se reemplaza o se quita.
            if ($imagen_nueva || $remove_imagen) {
                $old = Producto::imagen($db, $id);
                if ($old) @unlink($upload_dir . $old);
            }
        }

        $id = Producto::guardar($db, $in, $imagen_nueva, $remove_imagen);
        jsonResponse(['ok' => true, 'id' => $id]);
    }

    public static function productoToggle(array $in, PDO $db): void
    {
        Producto::toggle($db, (int)($in['id'] ?? 0));
        jsonResponse(['ok' => true]);
    }

    public static function ajusteStock(array $in, PDO $db): void
    {
        $prodId = (int)($in['producto_id'] ?? 0);
        $tipo   = $in['tipo'] ?? 'entrada';
        $qty    = (float)($in['cantidad'] ?? 0);
        $obs    = ($in['observaciones'] ?? null) ?: null;

        if (!$prodId) jsonResponse(['error' => 'Producto requerido.'], 400);
        if ($qty <= 0) jsonResponse(['error' => 'Cantidad debe ser positiva.'], 400);
        if (!in_array($tipo, ['entrada', 'salida', 'ajuste'])) jsonResponse(['error' => 'Tipo invalido.'], 400);

        try {
            $r = Kardex::ajustar($db, $prodId, $tipo, $qty, $in['precio_unitario'] ?? null, $obs);
        } catch (PDOException $e) {
            throw $e; // error de BD => 500 (manejador global)
        } catch (Exception $e) {
            jsonResponse(['error' => $e->getMessage()], 400);
        }
        jsonResponse(['ok' => true, 'nuevo_stock' => $r['nuevo_stock'], 'precio_promedio' => $r['precio_promedio']]);
    }

    public static function kardexListar(array $in, PDO $db): void
    {
        $prodId = (int)($in['producto_id'] ?? 0);
        if (!$prodId) jsonResponse(['error' => 'Producto requerido.'], 400);
        $r = Kardex::listar($db, $prodId, $in);
        jsonResponse(['ok' => true, 'movimientos' => $r['movimientos'], 'stats' => $r['stats']]);
    }

    public static function kardexGlobal(array $in, PDO $db): void
    {
        $tipo = $in['tipo'] ?? '';
        if (!in_array($tipo, ['entrada', 'salida'])) jsonResponse(['error' => 'Tipo invalido.'], 400);
        jsonResponse(['ok' => true, 'movimientos' => Kardex::global($db, $tipo, $in)]);
    }

    public static function categoriasListar(PDO $db): void
    {
        jsonResponse(['ok' => true, 'categorias' => Categoria::listar($db)]);
    }

    public static function categoriaGuardar(array $in, PDO $db): void
    {
        if (empty($in['nombre'])) jsonResponse(['error' => 'El nombre es obligatorio.'], 400);
        Categoria::guardar($db, $in);
        jsonResponse(['ok' => true]);
    }

    public static function categoriaEliminar(array $in, PDO $db): void
    {
        $id = (int)($in['id'] ?? 0);
        if (Categoria::productosActivos($db, $id) > 0)
            jsonResponse(['error' => 'No se puede eliminar: tiene productos activos.'], 400);
        Categoria::eliminar($db, $id);
        jsonResponse(['ok' => true]);
    }

    public static function generarCodigo(array $in, PDO $db): void
    {
        $nombreRef = trim((string)($in['nombre'] ?? ''));
        jsonResponse(['ok' => true, 'codigo' => Producto::generarCodigoAuto($db, $nombreRef)]);
    }

    public static function combos(PDO $db): void
    {
        jsonResponse([
            'ok'              => true,
            'categorias'      => Categoria::combo($db),
            'unidades'        => Catalogo::feUnidades($db),
            'afectaciones'    => Catalogo::feAfectaciones($db),
            'unidades_medida' => UnidadMedida::listar($db),
        ]);
    }

    public static function unidadGuardar(array $in, PDO $db): void
    {
        $codigo = trim((string)($in['codigo'] ?? ''));
        $nombre = trim((string)($in['nombre'] ?? ''));
        if ($codigo === '' || $nombre === '') jsonResponse(['error' => 'Codigo y nombre son obligatorios.'], 400);
        if (UnidadMedida::existe($db, $codigo)) jsonResponse(['error' => 'Ya existe una unidad con ese codigo.'], 400);
        $id = UnidadMedida::crear($db, $codigo, $nombre);
        jsonResponse(['ok' => true, 'id' => $id, 'codigo' => $codigo, 'nombre' => $nombre]);
    }

    public static function unidadEliminar(array $in, PDO $db): void
    {
        $uid = (int)($in['id'] ?? 0);
        if ($uid < 1) jsonResponse(['error' => 'Unidad invalida.'], 400);
        UnidadMedida::eliminar($db, $uid);
        jsonResponse(['ok' => true]);
    }

    public static function importar(PDO $db): void
    {
        if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK)
            jsonResponse(['error' => 'No se recibió el archivo Excel.'], 400);
        $ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'xlsx') jsonResponse(['error' => 'El archivo debe ser .xlsx (Excel).'], 400);
        if ($_FILES['archivo']['size'] > 10 * 1024 * 1024)
            jsonResponse(['error' => 'El archivo no puede superar 10 MB.'], 400);
        require_once __DIR__ . '/../../import/importador.php';
        jsonResponse(['ok' => true] + importarMateriales($db, $_FILES['archivo']['tmp_name']));
    }
}

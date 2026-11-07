<?php
/**
 * API Inventario — Router delgado (MVC).
 * Responsabilidad: arrancar sesión, parsear la acción, aplicar permisos y
 * delegar al controlador. La lógica vive en mvc/controllers y mvc/models.
 * Ver ARQUITECTURA_MVC.md
 */
ini_set('display_errors', 0);
error_reporting(0);
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/estante/controllers/EstanteController.php';
require_once __DIR__ . '/mvc/controllers/InventarioController.php';
startAppSession();
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // FormData (multipart) llena $_POST; un body JSON lo deja vacío.
    $input  = !empty($_POST) ? $_POST : (json_decode(file_get_contents('php://input'), true) ?? []);
    $action = $input['action'] ?? $action;
} else {
    $input = $_GET;
}

$nivelAlmacen = nivelPermiso('inventarios');
if ($nivelAlmacen === 'ninguno') {
    jsonResponse(['error' => 'No tienes permisos para acceder al módulo de Inventario.'], 403);
}
$accionesEscrituraAlmacen = ['producto_guardar', 'producto_toggle', 'ajuste_stock', 'categoria_guardar', 'categoria_eliminar', 'unidad_medida_guardar', 'unidad_medida_eliminar', 'estante_config_guardar', 'estante_celda_guardar', 'importar_materiales'];
if ($nivelAlmacen === 'lectura' && in_array($action, $accionesEscrituraAlmacen)) {
    jsonResponse(['error' => 'Tu rol tiene acceso de solo lectura a Inventario.'], 403);
}

$db = getDB();

// Feature Estante (MVC): maneja estante_* y corta con jsonResponse; valida con error 400.
try {
    if (EstanteController::handle($action, $input, $db)) exit;
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 400);
}

// Resto del módulo (MVC).
try {
    if (InventarioController::handle($action, $input, $db)) exit;
    jsonResponse(['error' => 'Accion no reconocida.'], 400);
} catch (PDOException $e) {
    jsonResponse(['error' => 'Error BD: ' . $e->getMessage()], 500);
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}

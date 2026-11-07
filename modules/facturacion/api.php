<?php
/**
 * API Facturacion — Router delgado (MVC).
 * Arranca sesion, parsea la accion, aplica permisos y delega al controlador.
 * La logica vive en mvc/controllers y mvc/models. Ver ARQUITECTURA_MVC.md
 */
ini_set('display_errors', 0);
error_reporting(0);
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/sunat.php';
require_once __DIR__ . '/mvc/controllers/FacturacionController.php';
startAppSession();
header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input  = !empty($_POST) ? $_POST : (json_decode(file_get_contents('php://input'), true) ?? []);
    $action = $input['action'] ?? $action;
} else {
    $input = $_GET;
}

$nivel = nivelPermiso('facturacion');
if ($nivel === 'ninguno') {
    jsonResponse(['error' => 'No tienes permisos para acceder al modulo de Facturacion.'], 403);
}
$accionesEscritura = ['fact_reenviar', 'fact_procesar_cola'];
if ($nivel === 'lectura' && in_array($action, $accionesEscritura, true)) {
    jsonResponse(['error' => 'Tu rol tiene acceso de solo lectura a Facturacion.'], 403);
}

$db = getDB();

try {
    if (FacturacionController::handle($action, $input, $db)) exit;
    jsonResponse(['error' => 'Accion no reconocida.'], 400);
} catch (PDOException $e) {
    jsonResponse(['error' => 'Error BD: ' . $e->getMessage()], 500);
} catch (Throwable $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}

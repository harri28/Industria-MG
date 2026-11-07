<?php
/**
 * CONTROLADOR — Estante
 * Recibe la accion, invoca el Modelo y responde JSON via jsonResponse().
 * No contiene SQL ni HTML.
 * Ver ARQUITECTURA_MVC.md
 */
require_once __DIR__ . '/../models/Estante.php';

class EstanteController
{
    /** Enruta una accion 'estante_*'. Devuelve true si la manejo, false si no le corresponde. */
    public static function handle(string $action, array $input, PDO $db): bool
    {
        switch ($action) {
            case 'estante_config':         self::configGet($db);            return true;
            case 'estante_config_guardar': self::configGuardar($input, $db); return true;
            case 'estante_celda_guardar':  self::celdaGuardar($input, $db);  return true;
            default:                       return false;
        }
    }

    /** GET: configuracion global actual del estante. */
    public static function configGet(PDO $db): void
    {
        $cfg = Estante::obtenerConfig($db);
        jsonResponse(['ok' => true] + $cfg);
    }

    /** POST: guarda la configuracion global del estante. */
    public static function configGuardar(array $input, PDO $db): void
    {
        Estante::guardarConfig($db, [
            'columnas' => $input['columnas'] ?? 0,
            'filas'    => $input['filas'] ?? 0,
            'nombre'   => $input['nombre'] ?? '',
            'grosor'   => $input['grosor'] ?? null,
            'ancho'    => $input['ancho'] ?? null,
        ]);
        jsonResponse(['ok' => true] + Estante::obtenerConfig($db));
    }

    /** POST: asigna (o desasigna) la celda de un producto. */
    public static function celdaGuardar(array $input, PDO $db): void
    {
        $productoId = (int)($input['producto_id'] ?? 0);
        if ($productoId < 1) jsonResponse(['error' => 'Producto invalido.'], 400);
        $celda = isset($input['celda']) ? (string)$input['celda'] : null;
        Estante::guardarCeldaProducto($db, $productoId, $celda);
        jsonResponse(['ok' => true, 'celda' => Estante::normalizarCelda($celda)]);
    }
}

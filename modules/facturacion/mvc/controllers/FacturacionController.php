<?php
/**
 * CONTROLADOR — Facturacion
 * Enruta las acciones del modulo, invoca los Models y responde JSON.
 * No contiene SQL. Ver ARQUITECTURA_MVC.md
 */
require_once __DIR__ . '/../models/Comprobante.php';
require_once __DIR__ . '/../models/Reenvio.php';
require_once __DIR__ . '/../models/SunatDoc.php';

class FacturacionController
{
    /** Despacha una accion. Devuelve true si la manejo, false si no le corresponde. */
    public static function handle(string $action, array $input, PDO $db): bool
    {
        switch ($action) {
            case 'fact_reporte':             self::reporte($input, $db);        return true;
            case 'fact_notas_credito':       self::notasCredito($input, $db);   return true;
            case 'fact_rentabilidad':        self::rentabilidad($input, $db);   return true;
            case 'fact_reenvios':            self::reenvios($input, $db);       return true;
            case 'fact_reenviar':            self::reenviar($input, $db);       return true;
            case 'fact_procesar_cola':       self::procesarCola($db);           return true;
            case 'fact_documentacion':       self::documentacion();             return true;
            case 'fact_comprobante_detalle': self::comprobanteDetalle($input, $db); return true;
            default:                         return false;
        }
    }

    public static function reporte(array $in, PDO $db): void
    {
        jsonResponse(['ok' => true, 'comprobantes' => Comprobante::reporteVentas($db, $in)]);
    }

    public static function notasCredito(array $in, PDO $db): void
    {
        jsonResponse(['ok' => true, 'comprobantes' => Comprobante::notasCredito($db, $in)]);
    }

    public static function rentabilidad(array $in, PDO $db): void
    {
        jsonResponse(['ok' => true, 'rentabilidad' => Comprobante::rentabilidad($db, $in)]);
    }

    public static function reenvios(array $in, PDO $db): void
    {
        jsonResponse([
            'ok'            => true,
            'estadisticas'  => Reenvio::estadisticas($db),
            'comprobantes'  => Reenvio::pendientes($db, $in),
        ]);
    }

    public static function reenviar(array $in, PDO $db): void
    {
        $id = (int)($in['comprobante_id'] ?? 0);
        if ($id <= 0) jsonResponse(['error' => 'Comprobante no valido.'], 400);
        try {
            $res = Reenvio::procesarUno($db, $id);
        } catch (Throwable $e) {
            jsonResponse(['error' => $e->getMessage()], 400);
        }
        if (empty($res['exito'])) {
            jsonResponse(['ok' => false, 'estado' => $res['estado'], 'mensaje' => $res['mensaje'] ?? 'SUNAT no acepto el comprobante.'], 422);
        }
        jsonResponse(['ok' => true, 'estado' => $res['estado'], 'mensaje' => 'Comprobante enviado a SUNAT.']);
    }

    public static function procesarCola(PDO $db): void
    {
        $res = Reenvio::procesarDebidos($db);
        jsonResponse(['ok' => true, 'resumen' => $res]);
    }

    public static function documentacion(): void
    {
        jsonResponse(['ok' => true, 'doc' => SunatDoc::todo()]);
    }

    public static function comprobanteDetalle(array $in, PDO $db): void
    {
        $id = (int)($in['id'] ?? 0);
        if ($id <= 0) jsonResponse(['error' => 'Comprobante no valido.'], 400);
        $data = Comprobante::detalleConItems($db, $id);
        if (!$data) jsonResponse(['error' => 'Comprobante no encontrado.'], 404);
        jsonResponse(['ok' => true] + $data);
    }
}

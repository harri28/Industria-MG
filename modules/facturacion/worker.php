<?php
/**
 * WORKER — Reenvio automatico de comprobantes a SUNAT (modulo Facturacion).
 * Pensado para correr por CRON cada 5 minutos:
 *   * /5 * * * * php /var/www/industria_mg/modules/facturacion/worker.php >> /var/log/reenvio_sunat.log 2>&1
 * Toma los comprobantes cuyo proximo_intento ya vencio y los reintenta.
 * Ver ARQUITECTURA_MVC.md
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este worker solo se ejecuta por linea de comandos (cron).\n");
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/sunat.php';
require_once __DIR__ . '/mvc/models/Reenvio.php';

$inicio = microtime(true);
$db = getDB();

$limite = isset($argv[1]) ? max(1, (int)$argv[1]) : 50;

try {
    $r = Reenvio::procesarDebidos($db, $limite);
    $seg = round(microtime(true) - $inicio, 2);
    printf(
        "[%s] reenvio_sunat: procesados=%d aceptados=%d reprogramados=%d rechazados=%d agotados=%d errores=%d (%ss)\n",
        date('Y-m-d H:i:s'),
        $r['procesados'], $r['aceptados'], $r['reprogramados'], $r['rechazados'], $r['agotados'], $r['errores'], $seg
    );
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, '[' . date('Y-m-d H:i:s') . "] reenvio_sunat ERROR: " . $e->getMessage() . "\n");
    exit(1);
}

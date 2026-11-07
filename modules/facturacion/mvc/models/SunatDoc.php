<?php
/**
 * MODELO — Documentacion SUNAT (catalogo de codigos de respuesta/error).
 * Datos estaticos de referencia para la pestaña "Documentacion" del modulo Facturacion.
 * Ver ARQUITECTURA_MVC.md
 */
class SunatDoc
{
    /** Rangos de codigos de respuesta de SUNAT y su significado / accion. */
    public static function rangos(): array
    {
        return [
            ['rango' => '0',          'tipo' => 'aceptado',    'titulo' => 'Aceptado',
             'detalle' => 'El comprobante fue aceptado por SUNAT. Se genera el CDR (Constancia de Recepcion).',
             'accion' => 'Ninguna. El documento es valido.'],
            ['rango' => '100 – 999',  'tipo' => 'transitorio', 'titulo' => 'Excepciones del sistema',
             'detalle' => 'Errores del servicio de SUNAT (servidor no disponible, mantenimiento, saturacion). No es culpa del documento.',
             'accion' => 'Reintentar el envio mas tarde. El worker lo reprograma automaticamente.'],
            ['rango' => '1000 – 1999','tipo' => 'contribuyente','titulo' => 'Errores del contribuyente / autenticacion',
             'detalle' => 'Problemas con el emisor: credenciales SOL, RUC, certificado o estructura basica.',
             'accion' => 'Revisar credenciales/certificado en Configuracion. Suele requerir correccion manual.'],
            ['rango' => '2000 – 3999','tipo' => 'rechazado',   'titulo' => 'Comprobante rechazado',
             'detalle' => 'El documento tiene errores de contenido o validacion (montos, cliente, afectacion IGV, duplicado). SUNAT lo rechaza de forma permanente.',
             'accion' => 'No se reintenta solo. Corregir el documento y volver a emitir. El worker lo marca como "Rechazado".'],
            ['rango' => '4000 +',     'tipo' => 'observado',   'titulo' => 'Aceptado con observaciones',
             'detalle' => 'El comprobante fue aceptado pero con observaciones (reparos no bloqueantes).',
             'accion' => 'El documento es valido; conviene revisar la observacion para futuros envios.'],
        ];
    }

    /** Codigos especificos frecuentes. */
    public static function codigos(): array
    {
        return [
            ['codigo' => '0100', 'tipo' => 'transitorio', 'mensaje' => 'El sistema no puede responder su solicitud. (SUNAT no disponible)'],
            ['codigo' => '0101', 'tipo' => 'transitorio', 'mensaje' => 'Encabezado del mensaje con version invalida o servicio no disponible.'],
            ['codigo' => '0102', 'tipo' => 'transitorio', 'mensaje' => 'Servicio de detalle no disponible temporalmente.'],
            ['codigo' => '0103', 'tipo' => 'transitorio', 'mensaje' => 'Servicio de resumen no disponible temporalmente.'],
            ['codigo' => '0109', 'tipo' => 'transitorio', 'mensaje' => 'El servicio de SUNAT no esta disponible. Reintentar.'],
            ['codigo' => '1032', 'tipo' => 'contribuyente','mensaje' => 'El contribuyente del comprobante no esta registrado como emisor electronico.'],
            ['codigo' => '1033', 'tipo' => 'aceptado',    'mensaje' => 'El comprobante fue registrado previamente con otros datos (ya existe en SUNAT).'],
            ['codigo' => '2027', 'tipo' => 'rechazado',   'mensaje' => 'El numero de RUC del adquiriente no existe.'],
            ['codigo' => '2335', 'tipo' => 'rechazado',   'mensaje' => 'El dato ingresado en un campo del detalle no cumple con el formato requerido.'],
            ['codigo' => '2800', 'tipo' => 'rechazado',   'mensaje' => 'El comprobante contiene un valor no permitido en afectacion del IGV.'],
            ['codigo' => '3105', 'tipo' => 'rechazado',   'mensaje' => 'El emisor electronico no se encuentra activo o habido.'],
            ['codigo' => '3206', 'tipo' => 'rechazado',   'mensaje' => 'El comprobante fue informado como dado de baja.'],
            ['codigo' => '4000', 'tipo' => 'observado',   'mensaje' => 'El comprobante numerado fue aceptado con observaciones.'],
        ];
    }

    public static function todo(): array
    {
        return ['rangos' => self::rangos(), 'codigos' => self::codigos(), 'retry_min' => Reenvio::RETRY_MINUTES];
    }
}

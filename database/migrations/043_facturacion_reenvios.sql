-- Migration 043: Cola de reenvios SUNAT (modulo Facturacion).
-- Agrega el control de reintentos automaticos a comprobantes_electronicos.
--   intentos        : nro de envios realizados por el worker
--   max_intentos    : tope de reintentos (default 20); al alcanzarlo => 'agotado'
--   proximo_intento : cuando el worker debe reintentar (NULL = en cuanto corra)
--   reenvio_estado  : nunca_enviado | programado | aceptado | rechazado | agotado
--   ultimo_error    : ultimo mensaje de error devuelto por SUNAT

ALTER TABLE comprobantes_electronicos
    ADD COLUMN IF NOT EXISTS intentos        INTEGER   NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS max_intentos    INTEGER   NOT NULL DEFAULT 20,
    ADD COLUMN IF NOT EXISTS proximo_intento TIMESTAMP,
    ADD COLUMN IF NOT EXISTS reenvio_estado  VARCHAR(20),
    ADD COLUMN IF NOT EXISTS ultimo_error    TEXT;

CREATE INDEX IF NOT EXISTS idx_ce_reenvio
    ON comprobantes_electronicos (reenvio_estado, proximo_intento);

-- Backfill: los que ya tienen CDR / respuesta 0 quedan como 'aceptado' (fuera de la cola).
UPDATE comprobantes_electronicos
SET reenvio_estado = 'aceptado'
WHERE reenvio_estado IS NULL
  AND (COALESCE(cdr_path, '') <> '' OR codigo_respuesta = '0');

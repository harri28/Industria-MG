-- Migration 036: Rediseño Orden de Compra
-- Agrega forma de pago, crédito, número de factura y costo de envío a la cabecera,
-- y descripción adicional por ítem.

ALTER TABLE ordenes_compra ADD COLUMN IF NOT EXISTS tipo_pago      VARCHAR(20) DEFAULT 'efectivo'; -- efectivo, transferencia, credito
ALTER TABLE ordenes_compra ADD COLUMN IF NOT EXISTS dias_credito   INTEGER      DEFAULT 0;
ALTER TABLE ordenes_compra ADD COLUMN IF NOT EXISTS numero_factura VARCHAR(40);
ALTER TABLE ordenes_compra ADD COLUMN IF NOT EXISTS costo_envio    DECIMAL(12,2) DEFAULT 0;

ALTER TABLE oc_detalles    ADD COLUMN IF NOT EXISTS descripcion    TEXT;

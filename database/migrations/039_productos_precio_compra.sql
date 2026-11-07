-- Migration 039: precio de compra del producto, enlazado con el modulo Compras.
-- Al registrar una orden de compra (oc_guardar), productos.precio_compra se
-- actualiza con el precio unitario de la compra.

ALTER TABLE productos ADD COLUMN IF NOT EXISTS precio_compra DECIMAL(12,2) NOT NULL DEFAULT 0;

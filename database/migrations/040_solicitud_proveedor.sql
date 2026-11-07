-- Migration 040: Proveedor en Solicitud de Compra
-- Permite asociar un proveedor registrado a la solicitud (para el documento/PDF).

ALTER TABLE solicitudes_compra ADD COLUMN IF NOT EXISTS proveedor_id INTEGER REFERENCES proveedores(id);

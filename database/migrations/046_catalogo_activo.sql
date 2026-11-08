-- Migration 046: Soft-delete para productos del catálogo
-- Reemplaza el DELETE físico por una bandera activo.

ALTER TABLE catalogo_productos
    ADD COLUMN IF NOT EXISTS activo BOOLEAN NOT NULL DEFAULT TRUE;

CREATE INDEX IF NOT EXISTS idx_catalogo_productos_activo
    ON catalogo_productos (activo);

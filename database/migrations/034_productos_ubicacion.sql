-- ============================================================
-- Migración 034: Ubicación física de productos en Almacén
-- Ejecutar: psql -U postgres -d industria_mg -f database/migrations/034_productos_ubicacion.sql
-- ============================================================

ALTER TABLE productos ADD COLUMN IF NOT EXISTS ubicacion VARCHAR(60);

COMMENT ON COLUMN productos.ubicacion IS 'Ubicación física del producto en el almacén (ej. Rack A-3, Estante 2)';

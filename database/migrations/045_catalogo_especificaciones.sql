-- Migration 045: Especificaciones técnicas en catálogo de productos
-- Almacena grupos de especificaciones como JSONB:
-- [{ titulo, descripcion, params: [{nombre, valor, unidad, descripcion}] }]

ALTER TABLE catalogo_productos
    ADD COLUMN IF NOT EXISTS especificaciones JSONB DEFAULT '[]'::jsonb;

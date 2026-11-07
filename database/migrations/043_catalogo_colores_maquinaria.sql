-- Migration 043: Colores por modelo de catalogo + vinculo maquinaria -> catalogo
-- Cada modelo del catalogo puede tener una lista de colores, cada uno con su
-- propia imagen: [{"nombre":"Naranja","imagen":"cat_xxx.webp"}, ...].
-- Una maquinaria se registra amarrada a un modelo del catalogo y a un color.

ALTER TABLE catalogo_productos ADD COLUMN IF NOT EXISTS colores JSONB DEFAULT '[]'::jsonb;

ALTER TABLE maquinarias ADD COLUMN IF NOT EXISTS catalogo_producto_id INTEGER REFERENCES catalogo_productos(id);
ALTER TABLE maquinarias ADD COLUMN IF NOT EXISTS color VARCHAR(80);

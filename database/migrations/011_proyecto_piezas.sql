-- ============================================================
-- Migración 011: Piezas del BOM
-- Ejecutar: psql -U postgres -d industria_mg -f database/migrations/011_proyecto_piezas.sql
-- ============================================================

-- 1. Tabla de piezas (agrupaciones de materiales dentro del BOM)
CREATE TABLE IF NOT EXISTS proyecto_piezas (
    id          SERIAL PRIMARY KEY,
    proyecto_id INTEGER NOT NULL REFERENCES proyectos(id) ON DELETE CASCADE,
    nombre      VARCHAR(200) NOT NULL,
    orden       INTEGER DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_proyecto_piezas_proyecto ON proyecto_piezas(proyecto_id);

-- 2. FK en proyecto_bom → proyecto_piezas
ALTER TABLE proyecto_bom
    ADD COLUMN IF NOT EXISTS pieza_id INTEGER REFERENCES proyecto_piezas(id) ON DELETE CASCADE;

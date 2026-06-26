-- Migration 010: Diseños y planos por área de proyecto
CREATE TABLE IF NOT EXISTS area_disenos (
    id               SERIAL PRIMARY KEY,
    proyecto_area_id INTEGER NOT NULL REFERENCES proyecto_areas(id) ON DELETE CASCADE,
    proyecto_id      INTEGER NOT NULL REFERENCES proyectos(id) ON DELETE CASCADE,
    area_id          INTEGER NOT NULL,
    nombre           VARCHAR(150) NOT NULL,
    descripcion      TEXT,
    filename         VARCHAR(255) NOT NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_area_disenos_wa ON area_disenos(proyecto_area_id);

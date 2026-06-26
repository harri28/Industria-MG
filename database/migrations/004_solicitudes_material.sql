-- ============================================================
-- Migración 004: Solicitudes de Material por Área
-- Ejecutar: psql -U postgres -d industria_mg -f database/migrations/004_solicitudes_material.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS solicitudes_material (
    id               SERIAL PRIMARY KEY,
    proyecto_id      INTEGER NOT NULL REFERENCES proyectos(id) ON DELETE CASCADE,
    area_id          INTEGER NOT NULL REFERENCES areas(id),
    proyecto_area_id INTEGER REFERENCES proyecto_areas(id),
    usuario_id       INTEGER REFERENCES usuarios(id),
    producto_id      INTEGER REFERENCES productos(id),
    descripcion      VARCHAR(200) NOT NULL,
    cantidad         DECIMAL(12,3) NOT NULL,
    unidad           VARCHAR(50) DEFAULT 'unidad',
    estado           VARCHAR(20) DEFAULT 'pendiente',
                     -- pendiente | aprobada | rechazada | entregada
    respuesta        TEXT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_solmat_proyecto ON solicitudes_material(proyecto_id);
CREATE INDEX IF NOT EXISTS idx_solmat_area     ON solicitudes_material(area_id);
CREATE INDEX IF NOT EXISTS idx_solmat_estado   ON solicitudes_material(estado);

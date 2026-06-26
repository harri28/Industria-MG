-- ============================================================
-- Migración 001: Workflow por Áreas en Proyectos
-- Ejecutar: psql -U postgres -d industria_mg -f database/migrations/001_proyecto_areas.sql
-- ============================================================

-- 1. Agregar área Electricidad si no existe
INSERT INTO areas (nombre, descripcion)
SELECT 'Electricidad', 'Área de instalaciones y sistemas eléctricos'
WHERE NOT EXISTS (SELECT 1 FROM areas WHERE nombre = 'Electricidad');

-- 2. Tabla de workflow por área por proyecto
CREATE TABLE IF NOT EXISTS proyecto_areas (
    id              SERIAL PRIMARY KEY,
    proyecto_id     INTEGER NOT NULL REFERENCES proyectos(id) ON DELETE CASCADE,
    area_id         INTEGER NOT NULL REFERENCES areas(id),
    estado          VARCHAR(20) NOT NULL DEFAULT 'pendiente',
                    -- pendiente | en_proceso | completado | bloqueado
    porcentaje      INTEGER NOT NULL DEFAULT 0 CHECK (porcentaje >= 0 AND porcentaje <= 100),
    responsable_id  INTEGER REFERENCES usuarios(id),
    observaciones   TEXT,
    orden           INTEGER NOT NULL DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (proyecto_id, area_id)
);

-- 3. Inicializar proyecto_areas para proyectos ya existentes
--    (retrocompatibilidad: crea las 5 áreas de workflow para proyectos activos)
DO $$
DECLARE
    nombres_workflow TEXT[] := ARRAY['Soldadura','Electricidad','Ensamble','Pintura','Control de Calidad'];
    nombre_area      TEXT;
    area_rec         RECORD;
    proyecto_rec     RECORD;
    i                INTEGER := 1;
BEGIN
    FOREACH nombre_area IN ARRAY nombres_workflow LOOP
        SELECT id INTO area_rec FROM areas WHERE nombre = nombre_area LIMIT 1;
        IF FOUND THEN
            FOR proyecto_rec IN SELECT id FROM proyectos WHERE activo = TRUE LOOP
                INSERT INTO proyecto_areas (proyecto_id, area_id, estado, orden)
                VALUES (proyecto_rec.id, area_rec.id, 'pendiente', i)
                ON CONFLICT (proyecto_id, area_id) DO NOTHING;
            END LOOP;
        END IF;
        i := i + 1;
    END LOOP;
END $$;

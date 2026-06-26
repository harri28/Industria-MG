-- ============================================================
-- Migración 019: Áreas como puntos de transformación
-- Ejecutar: psql -U postgres -d industria_mg -f database/migrations/019_areas_transformacion.sql
--
-- Cambios:
--   1. Estados y campos de workflow en proyecto_piezas y proyecto_partes
--   2. Nueva tabla `transformaciones` (eventos en áreas)
--   3. Nueva tabla `transformacion_inputs` (qué se consumió)
--   4. Nueva tabla `transformacion_outputs` (qué se generó)
-- ============================================================

-- 1. Estados y campos en proyecto_piezas
--    estado:
--      planificada → la pieza existe en el plan pero aún no se ha fabricado
--      en_proceso  → actualmente en un área (operario trabajando)
--      disponible  → fabricada y lista para siguiente paso
--      consumida   → fue input de otra transformación, ya no existe físicamente
--      integrada   → finalizada como parte de la máquina
--      stock       → finalizada y enviada a inventario
ALTER TABLE proyecto_piezas
    ADD COLUMN IF NOT EXISTS estado VARCHAR(20) DEFAULT 'planificada',
    ADD COLUMN IF NOT EXISTS area_actual_id INTEGER REFERENCES areas(id) ON DELETE SET NULL;

-- 2. Estados y campos en proyecto_partes (la unidad de trabajo del taller)
--    estado_fabricacion:
--      sin_asignar → operador aún no decide a qué área va
--      pendiente   → área asignada, en cola
--      en_proceso  → responsable del área inició la tarea
--      completada  → responsable marcó como terminada (creó la pieza física)
--      integrada   → la pieza generada ya forma parte de la máquina
--      stock       → la pieza generada fue a inventario
ALTER TABLE proyecto_partes
    ADD COLUMN IF NOT EXISTS estado_fabricacion   VARCHAR(20) DEFAULT 'sin_asignar',
    ADD COLUMN IF NOT EXISTS area_id              INTEGER REFERENCES areas(id) ON DELETE SET NULL,
    ADD COLUMN IF NOT EXISTS fecha_inicio         TIMESTAMP NULL,
    ADD COLUMN IF NOT EXISTS fecha_completada     TIMESTAMP NULL,
    ADD COLUMN IF NOT EXISTS usuario_completo_id  INTEGER REFERENCES usuarios(id) ON DELETE SET NULL;

CREATE INDEX IF NOT EXISTS idx_proyecto_partes_area   ON proyecto_partes(area_id);
CREATE INDEX IF NOT EXISTS idx_proyecto_partes_estado ON proyecto_partes(estado_fabricacion);

-- 3. Tabla transformaciones (evento en un área)
--    tipo:
--      combinacion    → N inputs distintos crean 1 pieza nueva (Soldadura)
--      procesamiento  → 1 input se modifica manteniendo identidad (Pintura)
--      finalizacion   → última transformación de la cadena, decide destino (proyecto/stock)
CREATE TABLE IF NOT EXISTS transformaciones (
    id              SERIAL PRIMARY KEY,
    proyecto_id     INTEGER NOT NULL REFERENCES proyectos(id) ON DELETE CASCADE,
    area_id         INTEGER NOT NULL REFERENCES areas(id),
    tipo            VARCHAR(20) NOT NULL CHECK (tipo IN ('combinacion','procesamiento','finalizacion')),
    fecha           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_id      INTEGER REFERENCES usuarios(id) ON DELETE SET NULL,
    costo_proceso   DECIMAL(12,2) DEFAULT 0,
    observaciones   TEXT,
    -- Solo aplica cuando areas.es_externa = TRUE
    fecha_envio_externo      TIMESTAMP NULL,
    fecha_recepcion_externo  TIMESTAMP NULL,
    proveedor                VARCHAR(200) NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_transformaciones_proyecto ON transformaciones(proyecto_id);
CREATE INDEX IF NOT EXISTS idx_transformaciones_area     ON transformaciones(area_id);
CREATE INDEX IF NOT EXISTS idx_transformaciones_tipo     ON transformaciones(tipo);
CREATE INDEX IF NOT EXISTS idx_transformaciones_fecha    ON transformaciones(fecha);

-- 4. Tabla transformacion_inputs (lo que se consumió en la transformación)
--    Cada input es UN material del BOM o UNA pieza ya transformada (no ambos).
CREATE TABLE IF NOT EXISTS transformacion_inputs (
    id                 SERIAL PRIMARY KEY,
    transformacion_id  INTEGER NOT NULL REFERENCES transformaciones(id) ON DELETE CASCADE,
    proyecto_bom_id    INTEGER REFERENCES proyecto_bom(id) ON DELETE SET NULL,
    proyecto_pieza_id  INTEGER REFERENCES proyecto_piezas(id) ON DELETE SET NULL,
    cantidad           DECIMAL(12,3) NOT NULL DEFAULT 1,
    CONSTRAINT chk_input_source CHECK (
        (proyecto_bom_id IS NOT NULL AND proyecto_pieza_id IS NULL) OR
        (proyecto_bom_id IS NULL AND proyecto_pieza_id IS NOT NULL)
    )
);

CREATE INDEX IF NOT EXISTS idx_trans_inputs_trans ON transformacion_inputs(transformacion_id);
CREATE INDEX IF NOT EXISTS idx_trans_inputs_bom   ON transformacion_inputs(proyecto_bom_id);
CREATE INDEX IF NOT EXISTS idx_trans_inputs_pieza ON transformacion_inputs(proyecto_pieza_id);

-- 5. Tabla transformacion_outputs (lo que se generó)
--    Si destino = proyecto: proyecto_pieza_id NOT NULL
--    Si destino = stock:    producto_id NOT NULL, kardex_id NOT NULL (entrada generada)
CREATE TABLE IF NOT EXISTS transformacion_outputs (
    id                 SERIAL PRIMARY KEY,
    transformacion_id  INTEGER NOT NULL REFERENCES transformaciones(id) ON DELETE CASCADE,
    proyecto_pieza_id  INTEGER REFERENCES proyecto_piezas(id) ON DELETE SET NULL,
    producto_id        INTEGER REFERENCES productos(id) ON DELETE SET NULL,
    kardex_id          INTEGER REFERENCES kardex(id) ON DELETE SET NULL,
    cantidad           DECIMAL(12,3) NOT NULL DEFAULT 1,
    nombre             VARCHAR(200),
    CONSTRAINT chk_output_destination CHECK (
        proyecto_pieza_id IS NOT NULL OR producto_id IS NOT NULL
    )
);

CREATE INDEX IF NOT EXISTS idx_trans_outputs_trans    ON transformacion_outputs(transformacion_id);
CREATE INDEX IF NOT EXISTS idx_trans_outputs_pieza    ON transformacion_outputs(proyecto_pieza_id);
CREATE INDEX IF NOT EXISTS idx_trans_outputs_producto ON transformacion_outputs(producto_id);

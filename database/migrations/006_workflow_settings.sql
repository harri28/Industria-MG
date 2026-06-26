-- ============================================================
-- Migración 006: Ajustes de Workflow en áreas
-- ============================================================

ALTER TABLE areas ADD COLUMN IF NOT EXISTS es_externa      BOOLEAN DEFAULT FALSE;
ALTER TABLE areas ADD COLUMN IF NOT EXISTS orden_workflow   INTEGER DEFAULT 99;

-- Corte es servicio externo
UPDATE areas SET es_externa = TRUE WHERE nombre = 'Corte';

-- Orden canónico de las áreas internas
UPDATE areas SET orden_workflow = CASE nombre
    WHEN 'Corte'              THEN 1
    WHEN 'Torno'              THEN 2
    WHEN 'Taladro'            THEN 3
    WHEN 'Soldadura'          THEN 4
    WHEN 'Rolado'             THEN 5
    WHEN 'Ensamble'           THEN 6
    WHEN 'Electricidad'       THEN 7
    WHEN 'Pintura'            THEN 8
    WHEN 'Control de Calidad' THEN 9
    ELSE 99
END;

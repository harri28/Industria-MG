ALTER TABLE proyecto_piezas
    ADD COLUMN IF NOT EXISTS propiedades_tecnicas JSONB DEFAULT '{}';

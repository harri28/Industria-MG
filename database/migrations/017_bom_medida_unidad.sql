ALTER TABLE proyecto_bom
    ADD COLUMN IF NOT EXISTS medida_unidad VARCHAR(10) DEFAULT 'cm';

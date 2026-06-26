ALTER TABLE proyecto_bom
    ALTER COLUMN medida TYPE INTEGER USING (NULLIF(regexp_replace(medida, '[^0-9]', '', 'g'), '')::INTEGER);

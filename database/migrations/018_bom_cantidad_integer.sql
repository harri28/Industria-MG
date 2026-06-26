ALTER TABLE proyecto_bom
    ALTER COLUMN cantidad_planificada TYPE INTEGER USING ROUND(cantidad_planificada)::INTEGER,
    ALTER COLUMN cantidad_real        TYPE INTEGER USING ROUND(cantidad_real)::INTEGER;

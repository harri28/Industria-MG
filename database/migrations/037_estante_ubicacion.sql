-- Migration 037: Estante / Ubicacion visual de productos (feature MVC)
-- Un unico estante global configurable (columnas A..Z x filas 1..N).
-- La celda del producto usa formato columna+fila (ej. B3 = columna B, fila 3).
-- Se conserva productos.ubicacion (texto libre, migration 034) intacto.
-- Ver ARQUITECTURA_MVC.md

-- Configuracion global del estante: una sola fila (id = 1).
CREATE TABLE IF NOT EXISTS estante_config (
    id         INTEGER PRIMARY KEY DEFAULT 1,
    columnas   INTEGER NOT NULL DEFAULT 6,
    filas      INTEGER NOT NULL DEFAULT 6,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT estante_config_single_row CHECK (id = 1),
    CONSTRAINT estante_config_columnas_rng CHECK (columnas BETWEEN 1 AND 26),
    CONSTRAINT estante_config_filas_rng    CHECK (filas    BETWEEN 1 AND 99)
);

INSERT INTO estante_config (id, columnas, filas)
VALUES (1, 6, 6)
ON CONFLICT (id) DO NOTHING;

-- Celda estructurada del producto dentro del estante (ej. 'B3'). NULL = sin ubicar.
ALTER TABLE productos ADD COLUMN IF NOT EXISTS celda_estante VARCHAR(5);

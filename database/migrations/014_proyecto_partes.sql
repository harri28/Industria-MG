CREATE TABLE IF NOT EXISTS proyecto_partes (
    id          SERIAL PRIMARY KEY,
    pieza_id    INTEGER NOT NULL REFERENCES proyecto_piezas(id) ON DELETE CASCADE,
    nombre      VARCHAR(200) NOT NULL,
    orden       INTEGER DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_proyecto_partes_pieza ON proyecto_partes(pieza_id);

ALTER TABLE proyecto_bom
    ADD COLUMN IF NOT EXISTS parte_id INTEGER REFERENCES proyecto_partes(id) ON DELETE SET NULL;

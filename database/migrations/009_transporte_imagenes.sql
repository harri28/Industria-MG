-- Migration 009: Galería de imágenes para transporte
CREATE TABLE IF NOT EXISTS transporte_imagenes (
    id             SERIAL PRIMARY KEY,
    transporte_id  INTEGER NOT NULL REFERENCES transporte(id) ON DELETE CASCADE,
    filename       VARCHAR(255) NOT NULL,
    orden          SMALLINT NOT NULL DEFAULT 0,
    created_at     TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_trans_img_tid ON transporte_imagenes(transporte_id, orden);

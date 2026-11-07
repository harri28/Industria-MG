-- Migration 044: Seguimiento / bitacora de leads
-- Agrega responsable y proxima accion al lead, y una bitacora de interacciones
-- (llamada, whatsapp, email, visita, nota) con fecha y usuario.

ALTER TABLE leads ADD COLUMN IF NOT EXISTS responsable_id INTEGER REFERENCES usuarios(id);
ALTER TABLE leads ADD COLUMN IF NOT EXISTS proxima_accion VARCHAR(255);
ALTER TABLE leads ADD COLUMN IF NOT EXISTS proxima_fecha DATE;

CREATE TABLE IF NOT EXISTS lead_interacciones (
    id          SERIAL PRIMARY KEY,
    lead_id     INTEGER NOT NULL REFERENCES leads(id) ON DELETE CASCADE,
    tipo        VARCHAR(30) NOT NULL DEFAULT 'nota',
    nota        TEXT NOT NULL,
    usuario_id  INTEGER REFERENCES usuarios(id),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_lead_interacciones_lead ON lead_interacciones(lead_id);

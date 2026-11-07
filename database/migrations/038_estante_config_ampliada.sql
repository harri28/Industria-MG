-- Migration 038: amplia la configuracion global del estante (feature Estante / MVC).
-- Agrega nombre, grosor de linea y ancho. Las celdas se ajustan proporcionalmente
-- al ancho en la Vista (ver modules/inventarios/estante/views/estante.view.php).

ALTER TABLE estante_config ADD COLUMN IF NOT EXISTS nombre       VARCHAR(60) NOT NULL DEFAULT 'Estante Principal';
ALTER TABLE estante_config ADD COLUMN IF NOT EXISTS grosor_linea INTEGER     NOT NULL DEFAULT 7;
ALTER TABLE estante_config ADD COLUMN IF NOT EXISTS ancho        INTEGER     NOT NULL DEFAULT 360;

ALTER TABLE estante_config DROP CONSTRAINT IF EXISTS estante_config_grosor_rng;
ALTER TABLE estante_config ADD  CONSTRAINT estante_config_grosor_rng CHECK (grosor_linea BETWEEN 1 AND 24);
ALTER TABLE estante_config DROP CONSTRAINT IF EXISTS estante_config_ancho_rng;
ALTER TABLE estante_config ADD  CONSTRAINT estante_config_ancho_rng  CHECK (ancho BETWEEN 240 AND 1400);

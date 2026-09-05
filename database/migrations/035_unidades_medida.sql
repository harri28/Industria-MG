-- ============================================================
-- Migración 035: Catálogo de Unidades de Medida (Almacén)
-- Ejecutar: psql -U postgres -d industria_mg -f database/migrations/035_unidades_medida.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS unidades_medida (
    id         SERIAL PRIMARY KEY,
    codigo     VARCHAR(10) UNIQUE NOT NULL,
    nombre     VARCHAR(50) NOT NULL,
    activo     BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO unidades_medida (codigo, nombre) VALUES
    ('unidad', 'Unidad'),
    ('kg',     'Kilogramo'),
    ('g',      'Gramo'),
    ('m',      'Metro'),
    ('m2',     'Metro cuadrado'),
    ('m3',     'Metro cubico'),
    ('lt',     'Litro'),
    ('gln',    'Galon'),
    ('caja',   'Caja'),
    ('rollo',  'Rollo'),
    ('par',    'Par')
ON CONFLICT (codigo) DO NOTHING;

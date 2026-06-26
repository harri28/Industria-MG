-- ============================================================
-- Migración 003: Módulo de Activos (Herramientas)
-- Ejecutar: psql -U postgres -d industria_mg -f database/migrations/003_activos.sql
-- ============================================================

-- 1. Categorías de activos
CREATE TABLE IF NOT EXISTS activo_categorias (
    id          SERIAL PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL,
    descripcion TEXT,
    activo      BOOLEAN DEFAULT TRUE,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Activos / Herramientas
CREATE TABLE IF NOT EXISTS activos (
    id               SERIAL PRIMARY KEY,
    codigo           VARCHAR(50) UNIQUE NOT NULL,
    nombre           VARCHAR(200) NOT NULL,
    descripcion      TEXT,
    categoria_id     INTEGER REFERENCES activo_categorias(id),
    numero_serie     VARCHAR(100),
    fecha_adquisicion DATE,
    estado           VARCHAR(20) DEFAULT 'bueno',
                     -- bueno | regular | dañado | baja
    observaciones    TEXT,
    activo           BOOLEAN DEFAULT TRUE,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Asignaciones de activos a usuarios
CREATE TABLE IF NOT EXISTS activo_asignaciones (
    id           SERIAL PRIMARY KEY,
    activo_id    INTEGER NOT NULL REFERENCES activos(id) ON DELETE CASCADE,
    usuario_id   INTEGER NOT NULL REFERENCES usuarios(id),
    periodo_tipo VARCHAR(10) NOT NULL DEFAULT 'dia',
                 -- dia | semana | mes
    fecha_inicio DATE NOT NULL DEFAULT CURRENT_DATE,
    fecha_fin    DATE NOT NULL,
    devuelto     BOOLEAN DEFAULT FALSE,
    fecha_devolucion TIMESTAMP,
    observaciones TEXT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Índices
CREATE INDEX IF NOT EXISTS idx_activo_asig_activo  ON activo_asignaciones(activo_id);
CREATE INDEX IF NOT EXISTS idx_activo_asig_usuario ON activo_asignaciones(usuario_id);
CREATE INDEX IF NOT EXISTS idx_activo_asig_activa  ON activo_asignaciones(devuelto, fecha_fin);

-- 5. Seed: Categorías base
INSERT INTO activo_categorias (nombre, descripcion) VALUES
    ('Herramienta Eléctrica', 'Taladros, amoladoras, sierras eléctricas'),
    ('Herramienta Manual',    'Martillos, llaves, destornilladores'),
    ('Herramienta de Corte',  'Discos, sierras, hojas de corte'),
    ('Medición',              'Wincha, nivel, calibrador, escuadra'),
    ('Seguridad',             'EPP: cascos, guantes, lentes, arnés')
ON CONFLICT DO NOTHING;

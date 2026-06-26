-- Migration 008: imagen en activos + tablas transporte y mobiliaria_equipos
ALTER TABLE activos ADD COLUMN IF NOT EXISTS imagen VARCHAR(255);

CREATE TABLE IF NOT EXISTS transporte (
    id               SERIAL PRIMARY KEY,
    codigo           VARCHAR(20)  UNIQUE NOT NULL,
    nombre           VARCHAR(150) NOT NULL,
    placa            VARCHAR(20),
    marca            VARCHAR(80),
    modelo           VARCHAR(80),
    anio             SMALLINT,
    color            VARCHAR(40),
    estado           VARCHAR(20)  NOT NULL DEFAULT 'operativo',
    imagen           VARCHAR(255),
    descripcion      VARCHAR(255),
    observaciones    TEXT,
    activo           BOOLEAN NOT NULL DEFAULT TRUE,
    created_at       TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at       TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS mobiliaria_equipos (
    id               SERIAL PRIMARY KEY,
    codigo           VARCHAR(20)  UNIQUE NOT NULL,
    nombre           VARCHAR(150) NOT NULL,
    tipo             VARCHAR(20)  NOT NULL DEFAULT 'equipo',
    ubicacion        VARCHAR(150),
    marca            VARCHAR(80),
    modelo           VARCHAR(80),
    num_serie        VARCHAR(80),
    num_inventario   VARCHAR(40),
    fecha_adquisicion DATE,
    estado           VARCHAR(20)  NOT NULL DEFAULT 'bueno',
    imagen           VARCHAR(255),
    descripcion      VARCHAR(255),
    observaciones    TEXT,
    activo           BOOLEAN NOT NULL DEFAULT TRUE,
    created_at       TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at       TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Catalogos base para facturacion electronica

CREATE TABLE IF NOT EXISTS fe_tipos_documento (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(2) NOT NULL UNIQUE,
    descripcion VARCHAR(255) NOT NULL,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS fe_tipos_documento_identidad (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(2) NOT NULL UNIQUE,
    descripcion VARCHAR(255) NOT NULL,
    descripcion_documento VARCHAR(100) NOT NULL,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS fe_unidades (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(3) NOT NULL UNIQUE,
    descripcion VARCHAR(255) NOT NULL,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS fe_formas_pago (
    id SERIAL PRIMARY KEY,
    descripcion VARCHAR(100) NOT NULL UNIQUE,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS fe_tipos_afectacion_igv (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(2) NOT NULL UNIQUE,
    descripcion VARCHAR(255) NOT NULL,
    tipo VARCHAR(5) NOT NULL,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS fe_monedas (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(3) NOT NULL UNIQUE,
    descripcion VARCHAR(100) NOT NULL,
    pais VARCHAR(100) NOT NULL,
    simbolo VARCHAR(5) NOT NULL,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS fe_tipos_nota_credito (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(2) NOT NULL UNIQUE,
    descripcion VARCHAR(255) NOT NULL,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS fe_tipos_nota_debito (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(2) NOT NULL UNIQUE,
    descripcion VARCHAR(255) NOT NULL,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS fe_tipos_operacion (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(4) NOT NULL UNIQUE,
    descripcion VARCHAR(255) NOT NULL,
    estado BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS ubigeo_departamentos (
    codigo VARCHAR(2) PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS ubigeo_provincias (
    codigo VARCHAR(4) PRIMARY KEY,
    departamento_codigo VARCHAR(2) NOT NULL REFERENCES ubigeo_departamentos(codigo),
    nombre VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS ubigeo_distritos (
    codigo VARCHAR(6) PRIMARY KEY,
    provincia_codigo VARCHAR(4) NOT NULL REFERENCES ubigeo_provincias(codigo),
    departamento_codigo VARCHAR(2) NOT NULL REFERENCES ubigeo_departamentos(codigo),
    nombre VARCHAR(100) NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_ubigeo_provincias_departamento
    ON ubigeo_provincias (departamento_codigo);

CREATE INDEX IF NOT EXISTS idx_ubigeo_distritos_provincia
    ON ubigeo_distritos (provincia_codigo);

CREATE INDEX IF NOT EXISTS idx_ubigeo_distritos_departamento
    ON ubigeo_distritos (departamento_codigo);

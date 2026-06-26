-- Base de empresa emisora para facturacion electronica

CREATE TABLE IF NOT EXISTS empresa_emisora (
    id SERIAL PRIMARY KEY,
    razon_social VARCHAR(255) NOT NULL,
    nombre_comercial VARCHAR(255),
    ruc VARCHAR(11),
    email VARCHAR(150),
    telefono VARCHAR(30),
    direccion_fiscal TEXT,
    codigo_pais VARCHAR(2) NOT NULL DEFAULT 'PE',
    ubigeo VARCHAR(6),
    departamento VARCHAR(100),
    provincia VARCHAR(100),
    distrito VARCHAR(100),
    tax_enabled BOOLEAN NOT NULL DEFAULT TRUE,
    logo_path VARCHAR(255),
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_empresa_emisora_ruc
    ON empresa_emisora (ruc)
    WHERE ruc IS NOT NULL;

INSERT INTO empresa_emisora (
    razon_social,
    nombre_comercial,
    email,
    telefono,
    direccion_fiscal,
    codigo_pais,
    tax_enabled,
    activo
)
SELECT
    'IndustriaMG',
    'IndustriaMG',
    'admin@industria-mg.com',
    NULL,
    NULL,
    'PE',
    TRUE,
    TRUE
WHERE NOT EXISTS (
    SELECT 1 FROM empresa_emisora
);

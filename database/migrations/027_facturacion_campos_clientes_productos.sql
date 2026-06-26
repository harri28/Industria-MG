-- Campos tributarios y comerciales base para clientes y productos

ALTER TABLE clientes ADD COLUMN IF NOT EXISTS tipo_documento_id INTEGER REFERENCES fe_tipos_documento_identidad(id);
ALTER TABLE clientes ADD COLUMN IF NOT EXISTS tipo_documento_codigo VARCHAR(2);
ALTER TABLE clientes ADD COLUMN IF NOT EXISTS numero_documento VARCHAR(20);
ALTER TABLE clientes ADD COLUMN IF NOT EXISTS nombre_completo VARCHAR(255);
ALTER TABLE clientes ADD COLUMN IF NOT EXISTS codigo_pais VARCHAR(2) DEFAULT 'PE';
ALTER TABLE clientes ADD COLUMN IF NOT EXISTS ubigeo VARCHAR(6);

UPDATE clientes
SET tipo_documento_codigo = CASE
        WHEN tipo = 'empresa' AND COALESCE(NULLIF(TRIM(ruc_dni), ''), '') <> '' THEN '6'
        WHEN COALESCE(NULLIF(TRIM(ruc_dni), ''), '') <> '' THEN '1'
        ELSE COALESCE(tipo_documento_codigo, '0')
    END,
    numero_documento = COALESCE(NULLIF(TRIM(ruc_dni), ''), numero_documento, '00000000'),
    nombre_completo = COALESCE(
        NULLIF(TRIM(contacto_nombre), ''),
        NULLIF(TRIM(razon_social), ''),
        nombre_completo,
        'CLIENTE'
    ),
    codigo_pais = COALESCE(NULLIF(TRIM(codigo_pais), ''), 'PE')
WHERE TRUE;

UPDATE clientes c
SET tipo_documento_id = t.id
FROM fe_tipos_documento_identidad t
WHERE t.codigo = COALESCE(NULLIF(c.tipo_documento_codigo, ''), '0')
  AND c.tipo_documento_id IS NULL;

CREATE INDEX IF NOT EXISTS idx_clientes_numero_documento
    ON clientes (numero_documento);

ALTER TABLE productos ADD COLUMN IF NOT EXISTS codigo_interno VARCHAR(50);
ALTER TABLE productos ADD COLUMN IF NOT EXISTS codigo_barras VARCHAR(100);
ALTER TABLE productos ADD COLUMN IF NOT EXISTS codigo_sunat VARCHAR(8) DEFAULT '00000000';
ALTER TABLE productos ADD COLUMN IF NOT EXISTS unidad_id INTEGER REFERENCES fe_unidades(id);
ALTER TABLE productos ADD COLUMN IF NOT EXISTS unidad_codigo VARCHAR(3) DEFAULT 'NIU';
ALTER TABLE productos ADD COLUMN IF NOT EXISTS afectacion_igv_id INTEGER REFERENCES fe_tipos_afectacion_igv(id);
ALTER TABLE productos ADD COLUMN IF NOT EXISTS afectacion_igv_codigo VARCHAR(2) DEFAULT '10';
ALTER TABLE productos ADD COLUMN IF NOT EXISTS porcentaje_igv DECIMAL(5,2) DEFAULT 18.00;
ALTER TABLE productos ADD COLUMN IF NOT EXISTS incluye_igv BOOLEAN DEFAULT TRUE;
ALTER TABLE productos ADD COLUMN IF NOT EXISTS icbper_activo BOOLEAN DEFAULT FALSE;
ALTER TABLE productos ADD COLUMN IF NOT EXISTS factor_icbper DECIMAL(10,4) DEFAULT 0;
ALTER TABLE productos ADD COLUMN IF NOT EXISTS product_type VARCHAR(20) DEFAULT 'product';

UPDATE productos
SET codigo_interno = COALESCE(NULLIF(TRIM(codigo_interno), ''), codigo),
    codigo_barras = NULLIF(TRIM(codigo_barras), ''),
    codigo_sunat = COALESCE(NULLIF(TRIM(codigo_sunat), ''), '00000000'),
    unidad_codigo = CASE
        WHEN LOWER(COALESCE(unidad, '')) IN ('servicio', 'servicios') THEN 'ZZ'
        WHEN LOWER(COALESCE(unidad, '')) IN ('kg', 'kilo', 'kilogramo') THEN 'KGM'
        WHEN LOWER(COALESCE(unidad, '')) IN ('litro', 'l', 'galon', 'galón') THEN 'LTR'
        WHEN LOWER(COALESCE(unidad, '')) IN ('metro', 'm') THEN 'MTR'
        WHEN LOWER(COALESCE(unidad, '')) IN ('tubo', 'tubos') THEN 'TU'
        WHEN LOWER(COALESCE(unidad, '')) IN ('juego', 'set') THEN 'SET'
        WHEN LOWER(COALESCE(unidad, '')) IN ('caja') THEN 'BX'
        WHEN LOWER(COALESCE(unidad, '')) IN ('pieza', 'piezas') THEN 'C62'
        ELSE COALESCE(NULLIF(TRIM(unidad_codigo), ''), 'NIU')
    END,
    afectacion_igv_codigo = COALESCE(NULLIF(TRIM(afectacion_igv_codigo), ''), '10'),
    porcentaje_igv = COALESCE(porcentaje_igv, 18.00),
    incluye_igv = COALESCE(incluye_igv, TRUE),
    icbper_activo = COALESCE(icbper_activo, FALSE),
    factor_icbper = COALESCE(factor_icbper, 0),
    product_type = COALESCE(NULLIF(TRIM(product_type), ''), 'product')
WHERE TRUE;

UPDATE productos p
SET unidad_id = u.id
FROM fe_unidades u
WHERE u.codigo = COALESCE(NULLIF(p.unidad_codigo, ''), 'NIU')
  AND p.unidad_id IS NULL;

UPDATE productos p
SET afectacion_igv_id = a.id
FROM fe_tipos_afectacion_igv a
WHERE a.codigo = COALESCE(NULLIF(p.afectacion_igv_codigo, ''), '10')
  AND p.afectacion_igv_id IS NULL;

CREATE INDEX IF NOT EXISTS idx_productos_codigo_sunat
    ON productos (codigo_sunat);

INSERT INTO clientes (
    tipo,
    razon_social,
    ruc_dni,
    contacto_nombre,
    direccion,
    estado_crm,
    activo,
    tipo_documento_codigo,
    numero_documento,
    nombre_completo,
    codigo_pais
)
SELECT
    'persona',
    'CLIENTES VARIOS',
    '00000000',
    'CLIENTES VARIOS',
    'MOSTRADOR',
    'activo',
    TRUE,
    '1',
    '00000000',
    'CLIENTES VARIOS',
    'PE'
WHERE NOT EXISTS (
    SELECT 1
    FROM clientes
    WHERE UPPER(TRIM(razon_social)) = 'CLIENTES VARIOS'
       OR numero_documento = '00000000'
);

UPDATE clientes c
SET tipo_documento_id = t.id
FROM fe_tipos_documento_identidad t
WHERE t.codigo = '1'
  AND c.tipo_documento_codigo = '1'
  AND c.tipo_documento_id IS NULL;

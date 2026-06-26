-- Infraestructura de series y comprobantes electronicos

ALTER TABLE empresa_emisora ADD COLUMN IF NOT EXISTS api_url VARCHAR(255);
ALTER TABLE empresa_emisora ADD COLUMN IF NOT EXISTS sunat_username VARCHAR(100);
ALTER TABLE empresa_emisora ADD COLUMN IF NOT EXISTS sunat_password VARCHAR(255);
ALTER TABLE empresa_emisora ADD COLUMN IF NOT EXISTS gre_client_id VARCHAR(255);
ALTER TABLE empresa_emisora ADD COLUMN IF NOT EXISTS gre_client_secret TEXT;
ALTER TABLE empresa_emisora ADD COLUMN IF NOT EXISTS certificate_path VARCHAR(255);
ALTER TABLE empresa_emisora ADD COLUMN IF NOT EXISTS certificate_password VARCHAR(255);
ALTER TABLE empresa_emisora ADD COLUMN IF NOT EXISTS certificate_expires_at DATE;
ALTER TABLE empresa_emisora ADD COLUMN IF NOT EXISTS sunat_server VARCHAR(10) DEFAULT 'beta';
ALTER TABLE empresa_emisora ADD COLUMN IF NOT EXISTS whatsapp_instance VARCHAR(255);

CREATE TABLE IF NOT EXISTS series_comprobantes (
    id SERIAL PRIMARY KEY,
    tipo VARCHAR(30) NOT NULL,
    serie VARCHAR(10) NOT NULL UNIQUE,
    ultimo_numero INTEGER NOT NULL DEFAULT 0,
    tipo_documento_id INTEGER REFERENCES fe_tipos_documento(id),
    codigo_tipo_documento VARCHAR(2) NOT NULL,
    descripcion VARCHAR(100),
    anexo_establecimiento VARCHAR(4) NOT NULL DEFAULT '0000',
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_series_comprobantes_tipo
    ON series_comprobantes (tipo, codigo_tipo_documento, activo);

CREATE TABLE IF NOT EXISTS comprobantes_electronicos (
    id SERIAL PRIMARY KEY,
    empresa_emisora_id INTEGER REFERENCES empresa_emisora(id),
    cliente_id INTEGER NOT NULL REFERENCES clientes(id),
    orden_venta_id INTEGER REFERENCES ordenes_venta(id),
    factura_id INTEGER REFERENCES facturas(id),
    serie_id INTEGER REFERENCES series_comprobantes(id),
    comprobante_relacionado_id INTEGER REFERENCES comprobantes_electronicos(id),
    tipo_documento_id INTEGER REFERENCES fe_tipos_documento(id),
    codigo_tipo_documento VARCHAR(2) NOT NULL,
    tipo_operacion_id INTEGER REFERENCES fe_tipos_operacion(id),
    operacion_tipo_codigo VARCHAR(4) NOT NULL DEFAULT '0101',
    moneda_id INTEGER REFERENCES fe_monedas(id),
    moneda_codigo VARCHAR(3) NOT NULL DEFAULT 'PEN',
    forma_pago_id INTEGER REFERENCES fe_formas_pago(id),
    sunat_forma_pago VARCHAR(20) NOT NULL DEFAULT 'Contado',
    numero VARCHAR(30) UNIQUE,
    serie VARCHAR(10) NOT NULL,
    correlativo VARCHAR(8) NOT NULL,
    fecha_emision DATE NOT NULL DEFAULT CURRENT_DATE,
    fecha_vencimiento DATE,
    hora_emision TIME NOT NULL DEFAULT CURRENT_TIME,
    observaciones TEXT,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    descuento_global DECIMAL(12,2) NOT NULL DEFAULT 0,
    gravada DECIMAL(12,2) NOT NULL DEFAULT 0,
    exonerada DECIMAL(12,2) NOT NULL DEFAULT 0,
    inafecta DECIMAL(12,2) NOT NULL DEFAULT 0,
    gratuita DECIMAL(12,2) NOT NULL DEFAULT 0,
    anticipo DECIMAL(12,2) NOT NULL DEFAULT 0,
    otros_cargos DECIMAL(12,2) NOT NULL DEFAULT 0,
    igv DECIMAL(12,2) NOT NULL DEFAULT 0,
    icbper DECIMAL(12,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    monto_credito DECIMAL(12,2) NOT NULL DEFAULT 0,
    vuelto DECIMAL(12,2) NOT NULL DEFAULT 0,
    cuotas JSONB,
    payment_breakdown JSONB,
    payload_json JSONB,
    sunat_response_json JSONB,
    hash_cpe TEXT,
    hash_cdr TEXT,
    xml_path VARCHAR(255),
    cdr_path VARCHAR(255),
    pdf_path VARCHAR(255),
    qr TEXT,
    ticket_sunat VARCHAR(100),
    codigo_respuesta VARCHAR(20),
    mensaje_respuesta TEXT,
    soap_request TEXT,
    soap_response TEXT,
    cdr INTEGER,
    estado_cpe INTEGER,
    estado VARCHAR(30) NOT NULL DEFAULT 'borrador',
    anulado BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_comprobantes_cliente_fecha
    ON comprobantes_electronicos (cliente_id, fecha_emision DESC);

CREATE INDEX IF NOT EXISTS idx_comprobantes_tipo_fecha
    ON comprobantes_electronicos (codigo_tipo_documento, fecha_emision DESC);

CREATE INDEX IF NOT EXISTS idx_comprobantes_orden_venta
    ON comprobantes_electronicos (orden_venta_id);

CREATE INDEX IF NOT EXISTS idx_comprobantes_factura
    ON comprobantes_electronicos (factura_id);

CREATE TABLE IF NOT EXISTS comprobante_detalles (
    id SERIAL PRIMARY KEY,
    comprobante_electronico_id INTEGER NOT NULL REFERENCES comprobantes_electronicos(id) ON DELETE CASCADE,
    producto_id INTEGER REFERENCES productos(id),
    descripcion VARCHAR(255) NOT NULL,
    cantidad DECIMAL(12,3) NOT NULL,
    unidad_id INTEGER REFERENCES fe_unidades(id),
    unidad_codigo VARCHAR(3) NOT NULL DEFAULT 'NIU',
    codigo_interno VARCHAR(50),
    codigo_sunat VARCHAR(8) DEFAULT '00000000',
    afectacion_igv_id INTEGER REFERENCES fe_tipos_afectacion_igv(id),
    afectacion_igv_codigo VARCHAR(2) NOT NULL DEFAULT '10',
    valor_unitario DECIMAL(18,10) NOT NULL DEFAULT 0,
    precio_unitario DECIMAL(18,10) NOT NULL DEFAULT 0,
    valor_total DECIMAL(12,2) NOT NULL DEFAULT 0,
    igv DECIMAL(12,2) NOT NULL DEFAULT 0,
    icbper DECIMAL(12,2) NOT NULL DEFAULT 0,
    factor_icbper DECIMAL(12,4) NOT NULL DEFAULT 0,
    cantidad_bolsas DECIMAL(12,2) NOT NULL DEFAULT 0,
    descuento DECIMAL(12,2) NOT NULL DEFAULT 0,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_comprobante_detalles_comprobante
    ON comprobante_detalles (comprobante_electronico_id);

ALTER TABLE facturas ADD COLUMN IF NOT EXISTS comprobante_electronico_id INTEGER REFERENCES comprobantes_electronicos(id);
ALTER TABLE facturas ADD COLUMN IF NOT EXISTS tipo_documento_id INTEGER REFERENCES fe_tipos_documento(id);
ALTER TABLE facturas ADD COLUMN IF NOT EXISTS codigo_tipo_documento VARCHAR(2);
ALTER TABLE facturas ADD COLUMN IF NOT EXISTS serie VARCHAR(10);
ALTER TABLE facturas ADD COLUMN IF NOT EXISTS correlativo VARCHAR(8);

UPDATE facturas
SET codigo_tipo_documento = COALESCE(NULLIF(TRIM(codigo_tipo_documento), ''), '01')
WHERE codigo_tipo_documento IS NULL OR TRIM(codigo_tipo_documento) = '';

UPDATE facturas f
SET tipo_documento_id = td.id
FROM fe_tipos_documento td
WHERE td.codigo = COALESCE(NULLIF(f.codigo_tipo_documento, ''), '01')
  AND f.tipo_documento_id IS NULL;

INSERT INTO series_comprobantes (tipo, serie, ultimo_numero, codigo_tipo_documento, descripcion, anexo_establecimiento, activo, tipo_documento_id)
SELECT 'factura', 'F001', 0, '01', 'Factura electronica', '0000', TRUE, td.id
FROM fe_tipos_documento td
WHERE td.codigo = '01'
  AND NOT EXISTS (
      SELECT 1 FROM series_comprobantes WHERE serie = 'F001'
  );

INSERT INTO series_comprobantes (tipo, serie, ultimo_numero, codigo_tipo_documento, descripcion, anexo_establecimiento, activo, tipo_documento_id)
SELECT 'boleta', 'B001', 0, '03', 'Boleta electronica', '0000', TRUE, td.id
FROM fe_tipos_documento td
WHERE td.codigo = '03'
  AND NOT EXISTS (
      SELECT 1 FROM series_comprobantes WHERE serie = 'B001'
  );

INSERT INTO series_comprobantes (tipo, serie, ultimo_numero, codigo_tipo_documento, descripcion, anexo_establecimiento, activo, tipo_documento_id)
SELECT 'nota_venta', 'NV01', 0, '02', 'Nota de venta', '0000', TRUE, td.id
FROM fe_tipos_documento td
WHERE td.codigo = '02'
  AND NOT EXISTS (
      SELECT 1 FROM series_comprobantes WHERE serie = 'NV01'
  );

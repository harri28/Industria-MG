-- Módulo Cuentas por Cobrar: facturas emitidas y cobros recibidos

CREATE TABLE IF NOT EXISTS facturas (
    id                SERIAL PRIMARY KEY,
    numero            VARCHAR(30) UNIQUE NOT NULL,
    cliente_id        INTEGER NOT NULL REFERENCES clientes(id),
    orden_venta_id    INTEGER,
    cotizacion_id     INTEGER REFERENCES cotizaciones(id),
    concepto          TEXT,
    fecha_emision     DATE NOT NULL DEFAULT CURRENT_DATE,
    fecha_vencimiento DATE NOT NULL,
    subtotal          DECIMAL(12,2) NOT NULL DEFAULT 0,
    igv               DECIMAL(12,2) NOT NULL DEFAULT 0,
    total             DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_cobrado     DECIMAL(12,2) NOT NULL DEFAULT 0,
    estado            VARCHAR(20) NOT NULL DEFAULT 'pendiente',
    observaciones     TEXT,
    usuario_id        INTEGER REFERENCES usuarios(id),
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_facturas_cliente    ON facturas (cliente_id);
CREATE INDEX IF NOT EXISTS idx_facturas_estado     ON facturas (estado);
CREATE INDEX IF NOT EXISTS idx_facturas_vencimiento ON facturas (fecha_vencimiento);

CREATE TABLE IF NOT EXISTS cobros (
    id          SERIAL PRIMARY KEY,
    factura_id  INTEGER NOT NULL REFERENCES facturas(id),
    fecha       DATE NOT NULL DEFAULT CURRENT_DATE,
    monto       DECIMAL(12,2) NOT NULL CHECK (monto > 0),
    metodo_pago VARCHAR(30) NOT NULL DEFAULT 'efectivo',
    referencia  VARCHAR(100),
    notas       TEXT,
    usuario_id  INTEGER REFERENCES usuarios(id),
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_cobros_factura ON cobros (factura_id);

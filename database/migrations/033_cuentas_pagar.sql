-- ============================================================
-- Migración 033: Módulo Cuentas por Pagar
-- Ejecutar: psql -U postgres -d industria_mg -f database/migrations/033_cuentas_pagar.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS cuentas_pagar (
    id                SERIAL PRIMARY KEY,
    numero            VARCHAR(30) UNIQUE NOT NULL,
    proveedor_id      INTEGER REFERENCES proveedores(id) ON DELETE SET NULL,
    proveedor_nombre  VARCHAR(200),        -- copia para display aunque se elimine el proveedor
    numero_doc        VARCHAR(80),         -- nro de factura/comprobante del proveedor
    concepto          TEXT,
    fecha_emision     DATE NOT NULL DEFAULT CURRENT_DATE,
    fecha_vencimiento DATE NOT NULL,
    total             DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_pagado      DECIMAL(12,2) NOT NULL DEFAULT 0,
    estado            VARCHAR(20) NOT NULL DEFAULT 'pendiente'
                      CHECK (estado IN ('pendiente','parcial','pagada','anulada')),
    orden_compra_id   INTEGER REFERENCES ordenes_compra(id) ON DELETE SET NULL,
    activo            BOOLEAN DEFAULT TRUE,
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_cuentas_pagar_proveedor ON cuentas_pagar(proveedor_id);
CREATE INDEX IF NOT EXISTS idx_cuentas_pagar_estado    ON cuentas_pagar(estado);
CREATE INDEX IF NOT EXISTS idx_cuentas_pagar_vence     ON cuentas_pagar(fecha_vencimiento);

CREATE TABLE IF NOT EXISTS cuentas_pagar_pagos (
    id            SERIAL PRIMARY KEY,
    cuenta_id     INTEGER NOT NULL REFERENCES cuentas_pagar(id) ON DELETE CASCADE,
    fecha         DATE NOT NULL DEFAULT CURRENT_DATE,
    monto         DECIMAL(12,2) NOT NULL,
    metodo_pago   VARCHAR(30) DEFAULT 'efectivo'
                  CHECK (metodo_pago IN ('efectivo','transferencia','cheque','deposito','otros')),
    referencia    VARCHAR(100),
    observaciones TEXT,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_cpp_cuenta ON cuentas_pagar_pagos(cuenta_id);

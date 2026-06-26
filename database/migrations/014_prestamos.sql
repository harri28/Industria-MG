-- 014_prestamos.sql — Módulo Préstamos

CREATE TABLE IF NOT EXISTS prestamos (
    id              SERIAL PRIMARY KEY,
    codigo          VARCHAR(20)   NOT NULL UNIQUE,
    tipo            VARCHAR(20)   NOT NULL CHECK (tipo IN ('recibido','otorgado')),
    entidad         VARCHAR(120)  NOT NULL,
    monto_total     DECIMAL(12,2) NOT NULL CHECK (monto_total > 0),
    tasa_interes    DECIMAL(6,4)  NOT NULL DEFAULT 0,
    plazo_meses     INT           NOT NULL DEFAULT 1,
    fecha_inicio    DATE          NOT NULL,
    fecha_vencimiento DATE        NOT NULL,
    estado          VARCHAR(20)   NOT NULL DEFAULT 'activo'
                    CHECK (estado IN ('activo','pagado','vencido','cancelado')),
    descripcion     TEXT,
    usuario_id      INT           REFERENCES usuarios(id),
    created_at      TIMESTAMP     NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS prestamo_cuotas (
    id              SERIAL PRIMARY KEY,
    prestamo_id     INT           NOT NULL REFERENCES prestamos(id) ON DELETE CASCADE,
    numero_cuota    INT           NOT NULL,
    fecha_vencimiento DATE        NOT NULL,
    monto_capital   DECIMAL(12,2) NOT NULL,
    monto_interes   DECIMAL(12,2) NOT NULL DEFAULT 0,
    monto_total     DECIMAL(12,2) NOT NULL,
    estado          VARCHAR(20)   NOT NULL DEFAULT 'pendiente'
                    CHECK (estado IN ('pendiente','pagado','vencido')),
    fecha_pago      DATE,
    observaciones   TEXT,
    created_at      TIMESTAMP     NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_prestamos_estado      ON prestamos(estado);
CREATE INDEX IF NOT EXISTS idx_prestamos_tipo        ON prestamos(tipo);
CREATE INDEX IF NOT EXISTS idx_prestamo_cuotas_prest ON prestamo_cuotas(prestamo_id);
CREATE INDEX IF NOT EXISTS idx_prestamo_cuotas_est   ON prestamo_cuotas(estado);

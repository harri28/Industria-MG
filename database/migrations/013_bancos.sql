-- 013_bancos.sql — Cuentas bancarias de la empresa

CREATE TABLE IF NOT EXISTS bancos (
    id             SERIAL PRIMARY KEY,
    nombre         VARCHAR(100)  NOT NULL,
    banco          VARCHAR(80)   NOT NULL,
    numero_cuenta  VARCHAR(50),
    tipo_cuenta    VARCHAR(20)   NOT NULL DEFAULT 'corriente'
                   CHECK (tipo_cuenta IN ('corriente','ahorro','recaudadora')),
    saldo_actual   DECIMAL(12,2) NOT NULL DEFAULT 0,
    moneda         VARCHAR(5)    NOT NULL DEFAULT 'PEN',
    activo         BOOLEAN       NOT NULL DEFAULT true,
    created_at     TIMESTAMP     NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_bancos_activo ON bancos(activo);

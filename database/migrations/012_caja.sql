-- 012_caja.sql — Módulo Caja: sesiones de caja y movimientos

CREATE TABLE IF NOT EXISTS cajas (
    id                  SERIAL PRIMARY KEY,
    fecha               DATE         NOT NULL DEFAULT CURRENT_DATE,
    usuario_apertura_id INT          REFERENCES usuarios(id),
    hora_apertura       TIMESTAMP    NOT NULL DEFAULT NOW(),
    saldo_inicial       DECIMAL(12,2) NOT NULL DEFAULT 0,
    usuario_cierre_id   INT          REFERENCES usuarios(id),
    hora_cierre         TIMESTAMP,
    saldo_final_sistema DECIMAL(12,2),
    saldo_final_fisico  DECIMAL(12,2),
    diferencia          DECIMAL(12,2),
    estado              VARCHAR(20)  NOT NULL DEFAULT 'abierta'
                        CHECK (estado IN ('abierta','cerrada')),
    observaciones       TEXT,
    created_at          TIMESTAMP    NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS caja_movimientos (
    id              SERIAL PRIMARY KEY,
    caja_id         INT          NOT NULL REFERENCES cajas(id) ON DELETE CASCADE,
    tipo            VARCHAR(10)  NOT NULL CHECK (tipo IN ('ingreso','egreso')),
    concepto        VARCHAR(60)  NOT NULL,
    descripcion     TEXT,
    monto           DECIMAL(12,2) NOT NULL CHECK (monto > 0),
    referencia_tipo VARCHAR(30),
    referencia_id   INT,
    usuario_id      INT          REFERENCES usuarios(id),
    fecha           TIMESTAMP    NOT NULL DEFAULT NOW(),
    created_at      TIMESTAMP    NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_caja_movimientos_caja ON caja_movimientos(caja_id);
CREATE INDEX IF NOT EXISTS idx_cajas_fecha           ON cajas(fecha);
CREATE INDEX IF NOT EXISTS idx_cajas_estado          ON cajas(estado);

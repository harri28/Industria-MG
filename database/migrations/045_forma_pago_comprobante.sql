-- Migration 045: Forma de pago local en comprobantes electronicos
-- Agrega columna forma_pago (directo / credito / adelanto)
-- El campo anticipo ya existe desde el schema base.

ALTER TABLE comprobantes_electronicos
    ADD COLUMN IF NOT EXISTS forma_pago VARCHAR(20) DEFAULT 'directo';

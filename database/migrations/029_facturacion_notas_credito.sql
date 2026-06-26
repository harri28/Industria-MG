-- Soporte base para notas de credito y debito

ALTER TABLE comprobantes_electronicos ADD COLUMN IF NOT EXISTS referencia_comprobante_id INTEGER REFERENCES comprobantes_electronicos(id);
ALTER TABLE comprobantes_electronicos ADD COLUMN IF NOT EXISTS tipo_nota_credito_id INTEGER REFERENCES fe_tipos_nota_credito(id);
ALTER TABLE comprobantes_electronicos ADD COLUMN IF NOT EXISTS codigo_tipo_nota_credito VARCHAR(2);
ALTER TABLE comprobantes_electronicos ADD COLUMN IF NOT EXISTS tipo_nota_debito_id INTEGER REFERENCES fe_tipos_nota_debito(id);
ALTER TABLE comprobantes_electronicos ADD COLUMN IF NOT EXISTS codigo_tipo_nota_debito VARCHAR(2);
ALTER TABLE comprobantes_electronicos ADD COLUMN IF NOT EXISTS motivo_nota_credito TEXT;
ALTER TABLE comprobantes_electronicos ADD COLUMN IF NOT EXISTS motivo_nota_debito TEXT;
ALTER TABLE comprobantes_electronicos ADD COLUMN IF NOT EXISTS descripcion_nota_credito TEXT;
ALTER TABLE comprobantes_electronicos ADD COLUMN IF NOT EXISTS descripcion_nota_debito TEXT;
ALTER TABLE comprobantes_electronicos ADD COLUMN IF NOT EXISTS documento_modificado_tipo_documento_codigo VARCHAR(2);
ALTER TABLE comprobantes_electronicos ADD COLUMN IF NOT EXISTS documento_modificado_serie VARCHAR(10);
ALTER TABLE comprobantes_electronicos ADD COLUMN IF NOT EXISTS documento_modificado_numero VARCHAR(20);
ALTER TABLE comprobantes_electronicos ADD COLUMN IF NOT EXISTS documento_modificado_numero_completo VARCHAR(30);
ALTER TABLE comprobantes_electronicos ADD COLUMN IF NOT EXISTS documento_modificado_fecha DATE;

CREATE INDEX IF NOT EXISTS idx_comprobantes_referencia_nc
    ON comprobantes_electronicos (referencia_comprobante_id);

INSERT INTO series_comprobantes (tipo, serie, ultimo_numero, codigo_tipo_documento, descripcion, anexo_establecimiento, activo, tipo_documento_id)
SELECT 'nota_credito', 'FC01', 0, '07', 'Nota de credito para facturas', '0000', TRUE, td.id
FROM fe_tipos_documento td
WHERE td.codigo = '07'
  AND NOT EXISTS (
      SELECT 1 FROM series_comprobantes WHERE serie = 'FC01'
  );

INSERT INTO series_comprobantes (tipo, serie, ultimo_numero, codigo_tipo_documento, descripcion, anexo_establecimiento, activo, tipo_documento_id)
SELECT 'nota_credito', 'BC01', 0, '07', 'Nota de credito para boletas', '0000', TRUE, td.id
FROM fe_tipos_documento td
WHERE td.codigo = '07'
  AND NOT EXISTS (
      SELECT 1 FROM series_comprobantes WHERE serie = 'BC01'
  );

INSERT INTO series_comprobantes (tipo, serie, ultimo_numero, codigo_tipo_documento, descripcion, anexo_establecimiento, activo, tipo_documento_id)
SELECT 'nota_debito', 'FD01', 0, '08', 'Nota de debito para facturas', '0000', TRUE, td.id
FROM fe_tipos_documento td
WHERE td.codigo = '08'
  AND NOT EXISTS (
      SELECT 1 FROM series_comprobantes WHERE serie = 'FD01'
  );

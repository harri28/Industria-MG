-- Seeds base de catalogos de facturacion electronica

INSERT INTO fe_tipos_documento (codigo, descripcion, estado) VALUES
    ('01', 'FACTURA ELECTRONICA', TRUE),
    ('03', 'BOLETA DE VENTA ELECTRONICA', TRUE),
    ('07', 'NOTA DE CREDITO ELECTRONICA', TRUE),
    ('08', 'NOTA DE DEBITO ELECTRONICA', TRUE),
    ('09', 'GUIA DE REMISION REMITENTE', TRUE),
    ('02', 'NOTA DE VENTA', TRUE)
ON CONFLICT (codigo) DO UPDATE
SET descripcion = EXCLUDED.descripcion,
    estado = EXCLUDED.estado;

INSERT INTO fe_tipos_documento_identidad (codigo, descripcion, descripcion_documento, estado) VALUES
    ('1', 'DOCUMENTO NACIONAL DE IDENTIDAD (DNI)', 'DNI', TRUE),
    ('6', 'REGISTRO UNICO DE CONTRIBUYENTES (RUC)', 'RUC', TRUE),
    ('4', 'CARNET DE EXTRANJERIA', 'CARNET DE EXTRANJERIA', TRUE),
    ('0', 'OTRO TIPO DE DOCUMENTO', 'OTROS', TRUE),
    ('7', 'PASAPORTE', 'PASAPORTE', TRUE),
    ('A', 'CEDULA DIPLOMATICA DE IDENTIDAD', 'CEDULA DIPLOMATICA DE IDENTIDAD', TRUE)
ON CONFLICT (codigo) DO UPDATE
SET descripcion = EXCLUDED.descripcion,
    descripcion_documento = EXCLUDED.descripcion_documento,
    estado = EXCLUDED.estado;

INSERT INTO fe_formas_pago (descripcion, estado) VALUES
    ('Efectivo', TRUE),
    ('Yape', TRUE),
    ('Plin', TRUE),
    ('Tunki', TRUE),
    ('Tarjeta de credito', TRUE),
    ('Tarjeta de debito', TRUE),
    ('Transferencia', TRUE),
    ('Cheque', TRUE),
    ('Credito', TRUE)
ON CONFLICT (descripcion) DO UPDATE
SET estado = EXCLUDED.estado;

INSERT INTO fe_monedas (codigo, descripcion, pais, simbolo, estado) VALUES
    ('PEN', 'SOL', 'PERU', 'S/', TRUE),
    ('USD', 'US DOLLAR', 'ESTADOS UNIDOS (EEUU)', '$', TRUE),
    ('EUR', 'EURO', 'ESPANA', 'EUR', TRUE)
ON CONFLICT (codigo) DO UPDATE
SET descripcion = EXCLUDED.descripcion,
    pais = EXCLUDED.pais,
    simbolo = EXCLUDED.simbolo,
    estado = EXCLUDED.estado;

INSERT INTO fe_tipos_afectacion_igv (codigo, descripcion, tipo, estado) VALUES
    ('10', 'Gravado - Operacion Onerosa', 'GRAV', TRUE),
    ('20', 'Exonerado - Operacion Onerosa', 'EXO', TRUE),
    ('30', 'Inafecto - Operacion Onerosa', 'INA', TRUE),
    ('40', 'Exportacion', 'EXP', FALSE)
ON CONFLICT (codigo) DO UPDATE
SET descripcion = EXCLUDED.descripcion,
    tipo = EXCLUDED.tipo,
    estado = EXCLUDED.estado;

INSERT INTO fe_tipos_nota_credito (codigo, descripcion, estado) VALUES
    ('01', 'ANULACION DE LA OPERACION', TRUE),
    ('02', 'ANULACION POR ERROR EN EL RUC', TRUE),
    ('03', 'CORRECCION POR ERROR EN LA DESCRIPCION', TRUE),
    ('04', 'DESCUENTO GLOBAL', TRUE),
    ('05', 'DESCUENTO POR ITEM', TRUE),
    ('06', 'DEVOLUCION TOTAL', TRUE),
    ('07', 'DEVOLUCION POR ITEM', TRUE),
    ('08', 'BONIFICACION', TRUE),
    ('09', 'DISMINUCION EN EL VALOR', TRUE),
    ('10', 'OTROS CONCEPTOS', TRUE)
ON CONFLICT (codigo) DO UPDATE
SET descripcion = EXCLUDED.descripcion,
    estado = EXCLUDED.estado;

INSERT INTO fe_tipos_nota_debito (codigo, descripcion, estado) VALUES
    ('01', 'INTERESES POR MORA', TRUE),
    ('02', 'AUMENTO EN EL VALOR', TRUE),
    ('03', 'PENALIDADES/ OTROS CONCEPTOS', TRUE)
ON CONFLICT (codigo) DO UPDATE
SET descripcion = EXCLUDED.descripcion,
    estado = EXCLUDED.estado;

INSERT INTO fe_tipos_operacion (codigo, descripcion, estado) VALUES
    ('0101', 'Venta interna', TRUE),
    ('1001', 'Operacion sujeta a detraccion', TRUE),
    ('1002', 'Operacion sujeta a percepcion', TRUE),
    ('1003', 'Operacion sujeta a retencion', TRUE),
    ('1004', 'Venta con despacho a consumidores finales', TRUE),
    ('2001', 'Exportacion de bienes', TRUE)
ON CONFLICT (codigo) DO UPDATE
SET descripcion = EXCLUDED.descripcion,
    estado = EXCLUDED.estado;

INSERT INTO fe_unidades (codigo, descripcion, estado) VALUES
    ('NIU', 'UNIDAD (BIENES)', TRUE),
    ('ZZ', 'UNIDAD (SERVICIOS)', TRUE),
    ('KGM', 'KILOGRAMO', TRUE),
    ('MTR', 'METRO', TRUE),
    ('LTR', 'LITRO', TRUE),
    ('C62', 'PIEZAS', TRUE),
    ('BX', 'CAJA', TRUE),
    ('SET', 'JUEGO', TRUE),
    ('TU', 'TUBOS', TRUE)
ON CONFLICT (codigo) DO UPDATE
SET descripcion = EXCLUDED.descripcion,
    estado = EXCLUDED.estado;

INSERT INTO ubigeo_departamentos (codigo, nombre) VALUES
    ('15', 'LIMA'),
    ('22', 'SAN MARTIN')
ON CONFLICT (codigo) DO UPDATE
SET nombre = EXCLUDED.nombre;

INSERT INTO ubigeo_provincias (codigo, departamento_codigo, nombre) VALUES
    ('1501', '15', 'LIMA'),
    ('2209', '22', 'SAN MARTIN')
ON CONFLICT (codigo) DO UPDATE
SET departamento_codigo = EXCLUDED.departamento_codigo,
    nombre = EXCLUDED.nombre;

INSERT INTO ubigeo_distritos (codigo, provincia_codigo, departamento_codigo, nombre) VALUES
    ('150101', '1501', '15', 'LIMA'),
    ('220901', '2209', '22', 'TARAPOTO')
ON CONFLICT (codigo) DO UPDATE
SET provincia_codigo = EXCLUDED.provincia_codigo,
    departamento_codigo = EXCLUDED.departamento_codigo,
    nombre = EXCLUDED.nombre;

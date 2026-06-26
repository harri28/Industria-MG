-- Maquinarias vendibles desde el modulo de ventas
-- Mantiene el registro postventa existente y agrega datos comerciales.

ALTER TABLE maquinarias ADD COLUMN IF NOT EXISTS producto_id INTEGER REFERENCES productos(id);
ALTER TABLE maquinarias ADD COLUMN IF NOT EXISTS costo_total DECIMAL(12,2) DEFAULT 0;
ALTER TABLE maquinarias ADD COLUMN IF NOT EXISTS precio_venta DECIMAL(12,2) DEFAULT 0;
ALTER TABLE maquinarias ADD COLUMN IF NOT EXISTS estado_venta VARCHAR(30) DEFAULT 'disponible';
ALTER TABLE maquinarias ADD COLUMN IF NOT EXISTS orden_venta_id INTEGER REFERENCES ordenes_venta(id);
ALTER TABLE maquinarias ADD COLUMN IF NOT EXISTS comprobante_electronico_id INTEGER REFERENCES comprobantes_electronicos(id);

UPDATE maquinarias
SET estado_venta = CASE
    WHEN fecha_venta IS NOT NULL OR cliente_id IS NOT NULL THEN 'vendida'
    ELSE COALESCE(NULLIF(TRIM(estado_venta), ''), 'disponible')
END
WHERE estado_venta IS NULL OR TRIM(estado_venta) = '';

INSERT INTO categorias_producto (nombre, descripcion, activo)
SELECT 'Maquinarias', 'Maquinas fabricadas listas para venta', TRUE
WHERE NOT EXISTS (
    SELECT 1 FROM categorias_producto WHERE LOWER(nombre) = 'maquinarias'
);

INSERT INTO productos (
    codigo,
    nombre,
    descripcion,
    categoria_id,
    unidad,
    stock_actual,
    stock_minimo,
    stock_maximo,
    precio_promedio,
    precio_venta,
    metodo_valuacion,
    es_repuesto,
    codigo_interno,
    codigo_sunat,
    unidad_codigo,
    afectacion_igv_codigo,
    porcentaje_igv,
    incluye_igv,
    product_type,
    activo
)
SELECT
    'MQ-TEST-001',
    'Maquina Cortadora CNC MG-100',
    'Maquinaria de prueba armada con estructura, motor, tablero electrico y repuestos base.',
    c.id,
    'unidad',
    1::DECIMAL(12,3),
    0::DECIMAL(12,3),
    1::DECIMAL(12,3),
    8500.00::DECIMAL(12,2),
    12500.00::DECIMAL(12,2),
    'promedio',
    FALSE,
    'MQ-TEST-001',
    '00000000',
    'NIU',
    '10',
    18.00,
    TRUE,
    'machine',
    TRUE
FROM categorias_producto c
WHERE LOWER(c.nombre) = 'maquinarias'
  AND NOT EXISTS (SELECT 1 FROM productos WHERE codigo = 'MQ-TEST-001');

UPDATE productos p
SET unidad_id = u.id
FROM fe_unidades u
WHERE p.codigo = 'MQ-TEST-001'
  AND u.codigo = 'NIU'
  AND (p.unidad_id IS NULL OR p.unidad_id <> u.id);

UPDATE productos p
SET afectacion_igv_id = a.id
FROM fe_tipos_afectacion_igv a
WHERE p.codigo = 'MQ-TEST-001'
  AND a.codigo = '10'
  AND (p.afectacion_igv_id IS NULL OR p.afectacion_igv_id <> a.id);

INSERT INTO maquinarias (
    producto_id,
    codigo,
    modelo,
    numero_serie,
    descripcion,
    garantia_meses,
    costo_total,
    precio_venta,
    estado_venta,
    activo
)
SELECT
    p.id,
    'MQ-2026-0001',
    'Cortadora CNC MG-100',
    'MG-CNC-TEST-001',
    'Maquina de prueba para venta. Costo referencial calculado por estructura, motor, tablero electrico y repuestos base.',
    12,
    8500.00,
    12500.00,
    'disponible',
    TRUE
FROM productos p
WHERE p.codigo = 'MQ-TEST-001'
  AND NOT EXISTS (SELECT 1 FROM maquinarias WHERE numero_serie = 'MG-CNC-TEST-001');

INSERT INTO kardex (
    producto_id,
    tipo,
    referencia_tipo,
    cantidad,
    precio_unitario,
    saldo_cantidad,
    saldo_valor,
    observaciones
)
SELECT
    p.id,
    'entrada',
    'ajuste_manual',
    p.stock_actual,
    p.precio_promedio,
    p.stock_actual,
    p.stock_actual * p.precio_promedio,
    'Stock inicial de maquinaria de prueba'
FROM productos p
WHERE p.codigo = 'MQ-TEST-001'
  AND p.stock_actual > 0
  AND NOT EXISTS (
      SELECT 1 FROM kardex k
      WHERE k.producto_id = p.id
        AND k.referencia_tipo = 'ajuste_manual'
        AND k.observaciones = 'Stock inicial de maquinaria de prueba'
  );

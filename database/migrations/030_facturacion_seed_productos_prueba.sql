-- Seed operativo de productos y datos de prueba para facturacion

UPDATE empresa_emisora
SET razon_social = COALESCE(NULLIF(TRIM(razon_social), ''), 'IndustriaMG S.A.C.'),
    nombre_comercial = COALESCE(NULLIF(TRIM(nombre_comercial), ''), 'IndustriaMG'),
    ruc = COALESCE(NULLIF(TRIM(ruc), ''), '20123456789'),
    direccion_fiscal = COALESCE(NULLIF(TRIM(direccion_fiscal), ''), 'Av. Industrial 123'),
    ubigeo = COALESCE(NULLIF(TRIM(ubigeo), ''), '150101'),
    departamento = COALESCE(NULLIF(TRIM(departamento), ''), 'LIMA'),
    provincia = COALESCE(NULLIF(TRIM(provincia), ''), 'LIMA'),
    distrito = COALESCE(NULLIF(TRIM(distrito), ''), 'LIMA'),
    email = COALESCE(NULLIF(TRIM(email), ''), 'facturacion@industria-mg.com'),
    api_url = COALESCE(NULLIF(TRIM(api_url), ''), 'https://api.nubefact.com/api/v1/'),
    sunat_server = COALESCE(NULLIF(TRIM(sunat_server), ''), 'beta'),
    certificate_path = COALESCE(NULLIF(TRIM(certificate_path), ''), 'facturacion/certs/20610316884.pfx'),
    certificate_password = COALESCE(NULLIF(TRIM(certificate_password), ''), 'mytems2022'),
    certificate_expires_at = COALESCE(certificate_expires_at, DATE '2026-12-06'),
    codigo_pais = COALESCE(NULLIF(TRIM(codigo_pais), ''), 'PE'),
    tax_enabled = TRUE,
    updated_at = CURRENT_TIMESTAMP
WHERE id = (SELECT id FROM empresa_emisora ORDER BY id LIMIT 1);

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
    icbper_activo,
    factor_icbper,
    product_type,
    activo
)
SELECT
    v.codigo,
    v.nombre,
    v.descripcion,
    c.id,
    v.unidad,
    v.stock_actual,
    v.stock_minimo,
    v.stock_maximo,
    v.precio_promedio,
    v.precio_venta,
    'promedio',
    v.es_repuesto,
    v.codigo,
    v.codigo_sunat,
    v.unidad_codigo,
    '10',
    18.00,
    TRUE,
    FALSE,
    0,
    'product',
    TRUE
FROM (
    VALUES
        ('FE-PRD-0001', 'Tubo Estructural 2x2 x 2mm', 'Tubo estructural para bastidores y marcos', 'Acero y Metales', 'tubos', 60::DECIMAL(12,3), 10::DECIMAL(12,3), 150::DECIMAL(12,3), 46.50::DECIMAL(10,2), 58.00::DECIMAL(10,2), FALSE, '30263600', 'TU'),
        ('FE-PRD-0002', 'Plancha LAC 3mm 1.20x2.40', 'Plancha de acero laminado en caliente', 'Acero y Metales', 'unidad', 22::DECIMAL(12,3), 4::DECIMAL(12,3), 60::DECIMAL(12,3), 135.00::DECIMAL(10,2), 167.00::DECIMAL(10,2), FALSE, '30102304', 'NIU'),
        ('FE-PRD-0003', 'Perno Hexagonal 1/2 x 2 1/2', 'Perno con tuerca y huacha para ensamble', 'Repuestos', 'caja', 35::DECIMAL(12,3), 8::DECIMAL(12,3), 80::DECIMAL(12,3), 18.00::DECIMAL(10,2), 24.50::DECIMAL(10,2), TRUE, '31161606', 'BX'),
        ('FE-PRD-0004', 'Electrodo 7018 1/8', 'Electrodo para trabajos de soldadura estructural', 'Consumibles Soldadura', 'kg', 120::DECIMAL(12,3), 25::DECIMAL(12,3), 250::DECIMAL(12,3), 13.80::DECIMAL(10,2), 18.90::DECIMAL(10,2), FALSE, '23171510', 'KGM'),
        ('FE-PRD-0005', 'Servicio de Corte CNC', 'Servicio de corte de planchas a medida', 'Herramientas', 'servicio', 0::DECIMAL(12,3), 0::DECIMAL(12,3), 0::DECIMAL(12,3), 0::DECIMAL(10,2), 95.00::DECIMAL(10,2), FALSE, '81141504', 'ZZ')
) AS v(codigo, nombre, descripcion, categoria_nombre, unidad, stock_actual, stock_minimo, stock_maximo, precio_promedio, precio_venta, es_repuesto, codigo_sunat, unidad_codigo)
JOIN categorias_producto c
  ON c.nombre = v.categoria_nombre
WHERE NOT EXISTS (
    SELECT 1 FROM productos p WHERE p.codigo = v.codigo
);

UPDATE productos p
SET unidad_id = u.id
FROM fe_unidades u
WHERE u.codigo = COALESCE(NULLIF(p.unidad_codigo, ''), 'NIU')
  AND (p.unidad_id IS NULL OR p.unidad_id <> u.id)
  AND p.codigo LIKE 'FE-PRD-%';

UPDATE productos p
SET afectacion_igv_id = a.id
FROM fe_tipos_afectacion_igv a
WHERE a.codigo = COALESCE(NULLIF(p.afectacion_igv_codigo, ''), '10')
  AND (p.afectacion_igv_id IS NULL OR p.afectacion_igv_id <> a.id)
  AND p.codigo LIKE 'FE-PRD-%';

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
    'Stock inicial de prueba para facturacion electronica'
FROM productos p
WHERE p.codigo LIKE 'FE-PRD-%'
  AND p.stock_actual > 0
  AND NOT EXISTS (
      SELECT 1
      FROM kardex k
      WHERE k.producto_id = p.id
        AND k.referencia_tipo = 'ajuste_manual'
        AND k.observaciones = 'Stock inicial de prueba para facturacion electronica'
  );

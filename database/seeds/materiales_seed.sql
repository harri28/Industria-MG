-- Seed: 20 materiales de inventario (aceros, fijaciones, insumos)
INSERT INTO productos (codigo, nombre, descripcion, categoria_id, unidad, stock_actual, stock_minimo, stock_maximo, precio_promedio, es_repuesto) VALUES

-- Acero y Metales (cat 1)
('MAT-2024-0001', 'Fierro Liso 1"',              'Barra redonda lisa de 1 pulgada x 6m',             1, 'barra',  45,  10, 100,  38.50, false),
('MAT-2024-0002', 'Fierro Liso 5/8"',            'Barra redonda lisa de 5/8 pulgada x 6m',           1, 'barra',  60,  15, 120,  18.90, false),
('MAT-2024-0003', 'Fierro Liso 1/2"',            'Barra redonda lisa de 1/2 pulgada x 6m',           1, 'barra',  80,  20, 150,  12.40, false),
('MAT-2024-0004', 'Tubo Rectangular 2x3 p2.5mm', 'Tubo rectangular 2"x3" pared 2.5mm x 6m',          1, 'unidad', 30,   8,  60,  65.00, false),
('MAT-2024-0005', 'Tubo Cuadrado 2x2 p6mm',      'Tubo cuadrado 2"x2" pared 6mm x 6m',              1, 'unidad', 25,   5,  50,  72.00, false),
('MAT-2024-0006', 'Platina 2x1/8"',              'Platina acero 2" x 1/8" x 6m',                    1, 'barra',  40,  10,  80,  15.20, false),
('MAT-2024-0007', 'Angulo 2" x 3mm',             'Angulo de acero 2" pared 3mm x 6m',               1, 'barra',  35,   8,  70,  28.60, false),

-- Pernos, tornillos y fijaciones (cat 1)
('MAT-2024-0008', 'Perno Hexagonal 1/2" x 2"',   'Perno hex grado 5 con tuerca y huacha x100',       1, 'ciento', 12,   3,  25,  42.00, true),
('MAT-2024-0009', 'Perno Hexagonal 3/8" x 1.5"', 'Perno hex grado 5 con tuerca y huacha x100',       1, 'ciento',  8,   2,  20,  28.00, true),
('MAT-2024-0010', 'Tornillo Autoroscante 1/4"',   'Tornillo autorroscante cabeza hexagonal x100',     1, 'ciento', 20,   5,  40,   8.50, true),
('MAT-2024-0011', 'Tuerca Hexagonal 1/2"',        'Tuerca hexagonal galvanizada x100',                1, 'ciento', 15,   4,  30,   9.80, true),
('MAT-2024-0012', 'Tuerca Hexagonal 3/8"',        'Tuerca hexagonal galvanizada x100',                1, 'ciento', 18,   4,  35,   6.40, true),
('MAT-2024-0013', 'Huacha Plana 1/2"',            'Arandela plana acero zincada x100',                1, 'ciento', 25,   5,  50,   4.20, true),
('MAT-2024-0014', 'Huacha Plana 3/8"',            'Arandela plana acero zincada x100',                1, 'ciento', 30,   5,  60,   3.10, true),
('MAT-2024-0015', 'Huacha de Presion 1/2"',       'Arandela presion acero templado x100',             1, 'ciento', 20,   5,  40,   5.60, true),

-- Consumibles soldadura (cat 5)
('MAT-2024-0016', 'Electrodo 6011 1/8"',          'Electrodo celulocitico 6011 x kg',                 5, 'kg',    150,  30, 300,  12.00, false),
('MAT-2024-0017', 'Electrodo 7018 1/8"',          'Electrodo bajo hidrogeno 7018 x kg',               5, 'kg',    120,  25, 250,  14.50, false),
('MAT-2024-0018', 'Disco de Corte 4.5" x115mm',   'Disco corte acero 115x1x22mm',                     5, 'unidad', 200,  50, 400,   2.80, false),
('MAT-2024-0019', 'Disco de Desbaste 7" x180mm',  'Disco desbaste acero 180x6x22mm',                  5, 'unidad', 80,   20, 160,   5.40, false),

-- Pinturas (cat 3)
('MAT-2024-0020', 'Pintura Anticorrosiva Gris',   'Pintura base anticorrosiva gris x galon',          3, 'galon',  18,   4,  30,  35.00, false);

-- Registrar entradas en kardex para tener historial
INSERT INTO kardex (producto_id, tipo, referencia_tipo, cantidad, precio_unitario, saldo_cantidad, saldo_valor, observaciones)
SELECT id, 'entrada', 'ajuste_manual', stock_actual, precio_promedio,
       stock_actual, stock_actual * precio_promedio,
       'Stock inicial — carga de apertura'
FROM productos
WHERE codigo LIKE 'MAT-2024-%';

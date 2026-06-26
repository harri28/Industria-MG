-- Seed: 20 herramientas + asignaciones
INSERT INTO activos (codigo, nombre, descripcion, categoria_id, numero_serie, fecha_adquisicion, estado) VALUES
('ACT-2024-0001', 'Taladro Percutor Bosch 13mm',       'Taladro percutor 750W mandril 13mm',            1, 'BSH-2024-001', '2024-01-15', 'bueno'),
('ACT-2024-0002', 'Taladro Percutor Bosch 13mm #2',    'Taladro percutor 750W mandril 13mm',            1, 'BSH-2024-002', '2024-01-15', 'regular'),
('ACT-2024-0003', 'Amoladora Angular 4.5" Dewalt',     'Amoladora 850W disco 115mm',                    1, 'DWT-2024-001', '2024-02-10', 'bueno'),
('ACT-2024-0004', 'Amoladora Angular 4.5" Dewalt #2',  'Amoladora 850W disco 115mm',                    1, 'DWT-2024-002', '2024-02-10', 'bueno'),
('ACT-2024-0005', 'Amoladora Angular 7" Dewalt',       'Amoladora 2200W disco 180mm',                   1, 'DWT-2024-003', '2024-03-05', 'bueno'),
('ACT-2024-0006', 'Soldadora Inverter 200A',            'Soldadora MMA 200A con porta electrodo',        1, 'INV-2024-001', '2024-01-20', 'bueno'),
('ACT-2024-0007', 'Esmeril de Banco 8"',                'Esmeril doble piedra 375W',                     1, 'ESM-2024-001', '2023-11-10', 'regular'),
('ACT-2024-0008', 'Martillo Demoledor 5kg',             'Martillo de bola mango fibra de vidrio',        2, 'MRT-2024-001', '2024-01-08', 'bueno'),
('ACT-2024-0009', 'Martillo Carpintero 500g',           'Martillo de uña mango madera',                  2, 'MRT-2024-002', '2023-08-15', 'bueno'),
('ACT-2024-0010', 'Llave de Impacto 1/2"',              'Llave neumatica 1/2" 680Nm',                    2, 'LLV-2024-001', '2024-02-20', 'bueno'),
('ACT-2024-0011', 'Juego de Llaves Combinadas',         'Set 12 piezas 8-19mm acero cromo vanadio',      2, 'JLC-2023-001', '2023-06-01', 'bueno'),
('ACT-2024-0012', 'Discos Corte Metal 4.5" x10',       'Pack 10 discos corte acero 115x1mm',            3, NULL,           '2024-04-01', 'bueno'),
('ACT-2024-0013', 'Discos Desbaste 7" x5',             'Pack 5 discos desbaste acero 180x6mm',          3, NULL,           '2024-04-01', 'bueno'),
('ACT-2024-0014', 'Discos Flap 4.5" Grano 60 x10',    'Pack 10 discos flap zirconio',                  3, NULL,           '2024-03-15', 'regular'),
('ACT-2024-0015', 'Sierra Circular 7.25" Makita',      'Sierra circular 1400W hoja 185mm',              1, 'MKT-2024-001', '2024-01-30', 'bueno'),
('ACT-2024-0016', 'Wincha Stanley 8m',                  'Cinta metrica acero inox 8m x 25mm',            4, 'WIN-2023-001', '2023-05-10', 'bueno'),
('ACT-2024-0017', 'Calibrador Vernier 0-150mm',         'Calibrador digital IP54 resolucion 0.01mm',     4, 'CAL-2024-001', '2024-02-05', 'bueno'),
('ACT-2024-0018', 'Nivel de Burbuja 60cm',              'Nivel aluminio 3 burbujas',                     4, 'NIV-2023-001', '2023-09-20', 'bueno'),
('ACT-2024-0019', 'Casco de Seguridad ANSI',            'Casco dielectrico clase E blanco',              5, 'CAS-2024-001', '2024-01-05', 'bueno'),
('ACT-2024-0020', 'Careta de Soldar Electronica',       'Careta fotosensible oscurecimiento auto DIN 9-13', 5, 'CAR-2024-001', '2024-03-10', 'bueno');

-- Asignaciones activas (mezcla de dia / semana / mes)
INSERT INTO activo_asignaciones (activo_id, usuario_id, periodo_tipo, fecha_inicio, fecha_fin, observaciones) VALUES
((SELECT id FROM activos WHERE codigo='ACT-2024-0001'), 2, 'semana', '2026-03-31', '2026-04-06', 'Uso en proyecto Aserradero lote 20'),
((SELECT id FROM activos WHERE codigo='ACT-2024-0002'), 3, 'dia',    '2026-04-03', '2026-04-03', 'Perforacion marco cabezal'),
((SELECT id FROM activos WHERE codigo='ACT-2024-0003'), 2, 'mes',    '2026-04-01', '2026-04-30', 'Asignado durante fabricacion lote'),
((SELECT id FROM activos WHERE codigo='ACT-2024-0005'), 3, 'semana', '2026-03-31', '2026-04-06', 'Desbaste estructura poleas'),
((SELECT id FROM activos WHERE codigo='ACT-2024-0006'), 2, 'mes',    '2026-04-01', '2026-04-30', 'Soldadura estructura principal'),
((SELECT id FROM activos WHERE codigo='ACT-2024-0008'), 3, 'dia',    '2026-04-03', '2026-04-03', 'Ensamble y ajuste'),
((SELECT id FROM activos WHERE codigo='ACT-2024-0010'), 1, 'semana', '2026-03-31', '2026-04-06', 'Ajuste pernos ruedas y tapas'),
((SELECT id FROM activos WHERE codigo='ACT-2024-0015'), 2, 'semana', '2026-03-31', '2026-04-06', 'Corte tablones guia'),
((SELECT id FROM activos WHERE codigo='ACT-2024-0017'), 1, 'mes',    '2026-04-01', '2026-04-30', 'Control dimensional piezas torno'),
((SELECT id FROM activos WHERE codigo='ACT-2024-0020'), 3, 'mes',    '2026-04-01', '2026-04-30', 'Proteccion personal area soldadura');

-- Asignaciones historicas ya devueltas
INSERT INTO activo_asignaciones (activo_id, usuario_id, periodo_tipo, fecha_inicio, fecha_fin, devuelto, fecha_devolucion, observaciones) VALUES
((SELECT id FROM activos WHERE codigo='ACT-2024-0001'), 1, 'dia',    '2026-03-28', '2026-03-28', TRUE, '2026-03-28 17:30:00', 'Uso puntual perforacion'),
((SELECT id FROM activos WHERE codigo='ACT-2024-0003'), 1, 'semana', '2026-03-24', '2026-03-30', TRUE, '2026-03-30 16:00:00', 'Corte tubos rectangulares'),
((SELECT id FROM activos WHERE codigo='ACT-2024-0009'), 2, 'dia',    '2026-04-02', '2026-04-02', TRUE, '2026-04-02 18:00:00', 'Ensamble general'),
((SELECT id FROM activos WHERE codigo='ACT-2024-0016'), 3, 'semana', '2026-03-24', '2026-03-30', TRUE, '2026-03-28 12:00:00', 'Medicion cortes estructura'),
((SELECT id FROM activos WHERE codigo='ACT-2024-0019'), 2, 'mes',    '2026-03-01', '2026-03-31', TRUE, '2026-03-31 17:00:00', 'EPP area produccion');

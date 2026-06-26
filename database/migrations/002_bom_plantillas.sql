-- ============================================================
-- Migración 002: Plantillas BOM + Nuevas Áreas de Workflow
-- Ejecutar: psql -U postgres -d industria_mg -f database/migrations/002_bom_plantillas.sql
-- ============================================================

-- 1. Nuevas áreas de fabricación
INSERT INTO areas (nombre, descripcion)
SELECT 'Corte', 'Corte en tronzadora y amoladora'
WHERE NOT EXISTS (SELECT 1 FROM areas WHERE nombre = 'Corte');

INSERT INTO areas (nombre, descripcion)
SELECT 'Torno', 'Mecanizado en torno y acabados'
WHERE NOT EXISTS (SELECT 1 FROM areas WHERE nombre = 'Torno');

INSERT INTO areas (nombre, descripcion)
SELECT 'Taladro', 'Perforado, roscado y marcado'
WHERE NOT EXISTS (SELECT 1 FROM areas WHERE nombre = 'Taladro');

INSERT INTO areas (nombre, descripcion)
SELECT 'Rolado', 'Rolado y curvado de materiales'
WHERE NOT EXISTS (SELECT 1 FROM areas WHERE nombre = 'Rolado');

-- 2. Columnas extra en proyecto_bom (soporte para items de plantilla y texto libre)
ALTER TABLE proyecto_bom
    ADD COLUMN IF NOT EXISTS seccion           VARCHAR(100),
    ADD COLUMN IF NOT EXISTS descripcion_libre VARCHAR(200),
    ADD COLUMN IF NOT EXISTS unidad_libre      VARCHAR(50),
    ADD COLUMN IF NOT EXISTS operacion         TEXT,
    ADD COLUMN IF NOT EXISTS orden             INTEGER DEFAULT 0;

-- 3. Tabla de plantillas de BOM
CREATE TABLE IF NOT EXISTS bom_plantillas (
    id          SERIAL PRIMARY KEY,
    codigo      VARCHAR(50) UNIQUE NOT NULL,
    nombre      VARCHAR(200) NOT NULL,
    descripcion TEXT,
    activo      BOOLEAN DEFAULT TRUE,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. Ítems de cada plantilla (cantidad_por_unidad = cantidad para 1 máquina)
CREATE TABLE IF NOT EXISTS bom_plantilla_items (
    id                   SERIAL PRIMARY KEY,
    plantilla_id         INTEGER NOT NULL REFERENCES bom_plantillas(id) ON DELETE CASCADE,
    seccion              VARCHAR(100),
    producto_id          INTEGER REFERENCES productos(id),
    descripcion_material VARCHAR(200) NOT NULL,
    cantidad_por_unidad  DECIMAL(12,3) NOT NULL DEFAULT 1,
    unidad               VARCHAR(50) DEFAULT 'unidad',
    dimension            VARCHAR(100),
    operacion            TEXT,
    orden                INTEGER DEFAULT 0
);

-- 5. Seed: Plantilla Aserradero Portátil GTK24
INSERT INTO bom_plantillas (codigo, nombre, descripcion)
SELECT 'TMPL-ASRDR-001',
       'Aserradero Portátil GTK24',
       'Lista de materiales base para 1 unidad. Escalar por cantidad de máquinas al cargar.'
WHERE NOT EXISTS (SELECT 1 FROM bom_plantillas WHERE codigo = 'TMPL-ASRDR-001');

-- ── SECCIÓN: CAJA / ESTRUCTURA DE POLEAS ─────────────────────────────────────
INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'CAJA / ESTRUCTURA DE POLEAS', 'TUBO RECTANGULAR 2"×3" pared 2.5mm (TRAVEZANO)', 1, 'unidad', '133 cm', 'TAPAR CON PLANCHA DE 3mm A LOS EXTREMOS', 10
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'CAJA / ESTRUCTURA DE POLEAS', 'TUBO RECTANGULAR 2"×3" pared 2.5mm (PARA CRUZ 1)', 1, 'unidad', '17.2 cm', 'TAPAR CON PLANCHA DE 3mm A LOS EXTREMOS', 20
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'CAJA / ESTRUCTURA DE POLEAS', 'TUBO RECTANGULAR 2"×3" pared 2.5mm (PARA CRUZ 2)', 1, 'unidad', '27.7 cm', 'TAPAR CON PLANCHA DE 3mm A LOS EXTREMOS', 30
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'CAJA / ESTRUCTURA DE POLEAS', 'TUBO RECTANGULAR 2"×3" pared 2.5mm (PARA BRAZO)', 1, 'unidad', '37 cm', NULL, 40
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'CAJA / ESTRUCTURA DE POLEAS', 'TUBO RECTANGULAR 3"×3" pared 4mm (TRAVEZANO)', 1, 'unidad', '24.4 cm', 'TAPAR CON PLANCHA DE 3mm A LOS EXTREMOS', 50
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'CAJA / ESTRUCTURA DE POLEAS', 'TUBO CUADRADO 2"×2" pared 6mm (SOPORTE GUIA)', 1, 'unidad', 'VERIFICAR', 'HACER HUECOS CON 5/16 LUEGO PASAR MACHO 3/8" HC', 60
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

-- ── SECCIÓN: MARCO / CASTILLO O CABEZAL ──────────────────────────────────────
INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'MARCO / CASTILLO O CABEZAL', 'TUBO RECTANGULAR 2"×3" pared 2.5mm (PATAS)', 2, 'unidad', '75 cm', 'HACER HUECOS CON BROCA DE 1"', 70
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'MARCO / CASTILLO O CABEZAL', 'TUBO RECTANGULAR 2"×3" pared 2.5mm (PARANTE)', 1, 'unidad', '142.5 cm', NULL, 80
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'MARCO / CASTILLO O CABEZAL', 'TUBO RECTANGULAR 2"×3" pared 2.5mm (TRAVEZANO)', 1, 'unidad', 'MODIFICADO', NULL, 90
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'MARCO / CASTILLO O CABEZAL', 'TUBO RECTANGULAR 3"×3" pared 3mm (PARANTE)', 1, 'unidad', '150 cm', NULL, 100
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'MARCO / CASTILLO O CABEZAL', 'TUBO CUADRADO 2"×2" pared 3mm (tubo pequeño)', 2, 'unidad', 'VERIFICAR', NULL, 110
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'MARCO / CASTILLO O CABEZAL', 'TUBO CUADRADO 2"×2" pared 3mm (tubo grande)', 2, 'unidad', 'VERIFICAR', NULL, 120
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'MARCO / CASTILLO O CABEZAL', 'TUBO REDONDO 2" (EJE QUE ENVUELVE EL CABLE)', 1, 'unidad', 'VERIFICAR', 'LLEVAR A TORNO', 130
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'MARCO / CASTILLO O CABEZAL', 'FIERRO LISO 7/8" (EJE — LLEVAR A TORNO)', 1, 'unidad', 'VERIFICAR', 'LLEVAR A TORNO', 140
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'MARCO / CASTILLO O CABEZAL', 'FIERRO LISO 7/8" (EJE CABLE)', 1, 'unidad', 'VERIFICAR', NULL, 150
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

-- ── SECCIÓN: PORTA POLEAS ─────────────────────────────────────────────────────
INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'PORTA POLEAS', 'CANAL U 1"×2" (SUJETADOR PERNO TEMPLADOR)', 2, 'unidad', 'VERIFICAR', NULL, 160
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'PORTA POLEAS', 'CANAL U 3"×2" (SUJETADOR POLEA ABAJO)', 2, 'unidad', 'VERIFICAR', NULL, 170
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'PORTA POLEAS', 'CANAL U 3"×2" (SUJETADOR POLEA ARRIBA)', 2, 'unidad', 'VERIFICAR', NULL, 180
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

-- ── SECCIÓN: REGLA ────────────────────────────────────────────────────────────
INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'REGLA', 'PLATINA 2"×1/8"', 1, 'metro', '1 m', NULL, 190
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'REGLA', 'ANGULO DE 2" (EMBORNE REGLA)', 1, 'unidad', 'VERIFICAR', NULL, 200
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'REGLA', 'ANGULO DE 2" (PARA VER LA MEDIDA)', 1, 'unidad', 'VERIFICAR', NULL, 210
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

-- ── SECCIÓN: TEMPLADOR ────────────────────────────────────────────────────────
INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'TEMPLADOR', 'PLATINA 1/4"×2" (PARA SOLDAR LA TUERCA)', 1, 'unidad', '9 cm', NULL, 220
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'TEMPLADOR', 'FIERRO LISO 1" (PARTE CON ROSCA)', 1, 'unidad', 'VERIFICAR', 'LLEVAR A TORNO', 230
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'TEMPLADOR', 'FIERRO LISO 1½" (PARA LA TUERCA)', 1, 'unidad', 'VERIFICAR', 'LLEVAR A TORNO', 240
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'TEMPLADOR', 'FIERRO LISO 5/8" (EJE DE TEMPLADOR)', 1, 'unidad', 'VERIFICAR', NULL, 250
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'TEMPLADOR', 'FIERRO LISO 1½" (BOCINAS TEMPLADOR)', 3, 'unidad', 'VERIFICAR', NULL, 260
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'TEMPLADOR', 'FIERRO LISO 1/2" (AGARRADOR — CACHOS)', 2, 'unidad', 'VERIFICAR', 'CORTAR CON INCLINACION', 270
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

-- ── SECCIÓN: BRAZO TEMPLADOR ──────────────────────────────────────────────────
INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'BRAZO TEMPLADOR', 'TUBO CUADRADO 2"×2" pared 6mm (DONDE ENTRA EJE CUADRADO)', 1, 'unidad', '15 cm', 'MARCAR MEDIDAS Y DEJAR EN EL TALADRO', 280
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'BRAZO TEMPLADOR', 'TUBO CUADRADO 2"×2" pared 6mm (DONDE SE SUELDA LA BOCINA)', 1, 'unidad', '~23 cm VERIFICAR', 'MARCAR MEDIDAS Y DEJAR EN EL TALADRO', 290
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'BRAZO TEMPLADOR', 'PLATINA 1/2"×2" (PINZA DEL BRAZO)', 1, 'unidad', 'VERIFICAR', NULL, 300
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'BRAZO TEMPLADOR', 'FIERRO LISO 1" (PINZA DEL BRAZO)', 1, 'unidad', 'VERIFICAR', NULL, 310
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'BRAZO TEMPLADOR', 'FIERRO LISO 1" (BOCINA DE PERNO 5/8")', 1, 'unidad', 'VERIFICAR', NULL, 320
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

-- ── SECCIÓN: EJE DE POLEA 3 CANALES ──────────────────────────────────────────
INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'EJE DE POLEA 3 CANALES', 'TUBO CUADRADO 2"×2" pared 6mm', 1, 'unidad', '15 cm', 'LLEVAR A TORNO', 330
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

-- ── SECCIÓN: GUIA ─────────────────────────────────────────────────────────────
INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'GUIA', 'FIERRO CUADRADO 1/2" (PISTA DE LA GUIA)', 4, 'unidad', 'VERIFICAR', 'CORTAR EN TRONZADORA EN GRUPOS DE 3 O 4 PIEZAS', 340
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'GUIA', 'FIERRO CUADRADO 1/2" (CHAVETA DE LA GUIA)', 2, 'unidad', 'VERIFICAR', 'SOLDAR EN LA ABRAZADERA, HACER HUECO Y PASAR MACHO', 350
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'GUIA', 'TUBO RECTANGULAR 1"×2" pared 3mm', 1, 'unidad', 'VERIFICAR', NULL, 360
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'GUIA', 'TUBO REDONDO GALVANIZADO 3/4"', 1, 'unidad', 'VERIFICAR', 'CORTAR EN TRONZADORA', 370
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'GUIA', 'TUBO REDONDO CEDULA 40 1" (BOCINA DE TUBO GUIA)', 1, 'unidad', 'VERIFICAR', 'CORTAR EN TRONZADORA', 380
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

-- ── SECCIÓN: RUEDAS ───────────────────────────────────────────────────────────
INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'RUEDAS', 'FIERRO LISO 1" (EJE PARA RUEDAS)', 4, 'unidad', 'VERIFICAR', 'LLEVAR A TORNO', 390
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

-- ── SECCIÓN: TAPAS DE RUEDAS ──────────────────────────────────────────────────
INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'TAPAS DE RUEDAS', 'PLATINA DE 4" (CARAS DE LA TAPA)', 8, 'unidad', '4"×4"', 'CORTAR EN TRONZADORA', 400
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'TAPAS DE RUEDAS', 'TUBO REDONDO 4" (CUERPO DE TAPA)', 4, 'unidad', 'VERIFICAR', 'CORTAR CON AMOLADORA', 410
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

-- ── SECCIÓN: PRENSAS ──────────────────────────────────────────────────────────
INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'PRENSAS', 'FIERRO LISO 1" (PERNO PRENSA)', 3, 'unidad', 'VERIFICAR', NULL, 420
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'PRENSAS', 'FIERRO LISO 1¼" (TUERCA PRENSA)', 3, 'unidad', 'VERIFICAR', NULL, 430
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'PRENSAS', 'TUBO RECTANGULAR 2"×2" pared 2mm (TUBO DE PRENSA)', 3, 'unidad', 'VERIFICAR', NULL, 440
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'PRENSAS', 'PLATINA 4"×4" (BASE DE PRENSA)', 3, 'unidad', '4"×4"', 'HACER HUECOS CON BROCA DE 1/4 Y HACER OJO CHINO', 450
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'PRENSAS', 'PLATINA 2"×1/8" (BASE DE PRENSA)', 6, 'unidad', '2"×2"', 'HACER HUECOS CON BROCA DE 1"', 460
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

INSERT INTO bom_plantilla_items (plantilla_id, seccion, descripcion_material, cantidad_por_unidad, unidad, dimension, operacion, orden)
SELECT id, 'PRENSAS', 'FIERRO LISO 3/8" (PALANCA DE PRENSA)', 6, 'unidad', 'VERIFICAR', NULL, 470
FROM bom_plantillas WHERE codigo='TMPL-ASRDR-001';

-- 6. Retrocompat: agregar las 4 nuevas áreas a proyectos activos existentes
DO $$
DECLARE
    nombres_nuevos TEXT[] := ARRAY['Corte','Torno','Taladro','Rolado'];
    nombre_area    TEXT;
    area_rec       RECORD;
    proyecto_rec   RECORD;
    i              INTEGER := 6;
BEGIN
    FOREACH nombre_area IN ARRAY nombres_nuevos LOOP
        SELECT id INTO area_rec FROM areas WHERE nombre = nombre_area LIMIT 1;
        IF FOUND THEN
            FOR proyecto_rec IN SELECT id FROM proyectos WHERE activo = TRUE LOOP
                INSERT INTO proyecto_areas (proyecto_id, area_id, estado, orden)
                VALUES (proyecto_rec.id, area_rec.id, 'pendiente', i)
                ON CONFLICT (proyecto_id, area_id) DO NOTHING;
            END LOOP;
        END IF;
        i := i + 1;
    END LOOP;
END $$;

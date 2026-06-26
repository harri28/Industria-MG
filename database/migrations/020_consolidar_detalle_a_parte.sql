-- ============================================================
-- Migración 020: Consolidar Detalle (proyecto_bom.pieza) en proyecto_partes.nombre
-- Ejecutar: psql -U postgres -d industria_mg -f database/migrations/020_consolidar_detalle_a_parte.sql
--
-- Por cada (pieza_id, valor distinto de pieza/Detalle), crea una proyecto_partes
-- y asigna parte_id a los materiales correspondientes.
--
-- La columna proyecto_bom.pieza NO se elimina aún — se mantiene por compatibilidad
-- mientras el UI/API se actualiza. Eliminarla en una migración futura.
-- ============================================================

DO $$
DECLARE
    rec          RECORD;
    new_parte_id INTEGER;
BEGIN
    FOR rec IN
        SELECT DISTINCT b.pieza_id, b.pieza AS detalle
        FROM proyecto_bom b
        WHERE b.parte_id IS NULL
          AND b.pieza   IS NOT NULL
          AND b.pieza   <> ''
          AND b.pieza_id IS NOT NULL
    LOOP
        INSERT INTO proyecto_partes (pieza_id, nombre, estado_fabricacion, orden)
        VALUES (rec.pieza_id, rec.detalle, 'sin_asignar', 0)
        RETURNING id INTO new_parte_id;

        UPDATE proyecto_bom
        SET parte_id = new_parte_id
        WHERE pieza_id = rec.pieza_id
          AND pieza    = rec.detalle
          AND parte_id IS NULL;
    END LOOP;
END $$;

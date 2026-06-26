-- Agrega sub-componente (parte) a ítems de plantilla BOM
ALTER TABLE bom_plantilla_items
    ADD COLUMN IF NOT EXISTS parte VARCHAR(100);

<?php
/**
 * MODELO — Catalogo (catálogos SUNAT de solo lectura usados por los combos)
 * Ver ARQUITECTURA_MVC.md
 */
class Catalogo
{
    /** Unidades de medida SUNAT (fe_unidades) activas. */
    public static function feUnidades(PDO $db): array
    {
        return $db->query("SELECT id, codigo, descripcion FROM fe_unidades WHERE estado ORDER BY codigo")->fetchAll();
    }

    /** Tipos de afectación IGV (fe_tipos_afectacion_igv) activos. */
    public static function feAfectaciones(PDO $db): array
    {
        return $db->query("SELECT id, codigo, descripcion FROM fe_tipos_afectacion_igv WHERE estado ORDER BY codigo")->fetchAll();
    }
}

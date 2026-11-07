<?php
/**
 * MODELO — UnidadMedida (catálogo interno de unidades de almacén)
 * Ver ARQUITECTURA_MVC.md
 */
class UnidadMedida
{
    /** Unidades de medida activas. */
    public static function listar(PDO $db): array
    {
        return $db->query("SELECT id, codigo, nombre FROM unidades_medida WHERE activo ORDER BY nombre")->fetchAll();
    }

    /** True si ya existe una unidad con ese código. */
    public static function existe(PDO $db, string $codigo): bool
    {
        $stmt = $db->prepare("SELECT id FROM unidades_medida WHERE codigo = :cod");
        $stmt->execute([':cod' => $codigo]);
        return (bool)$stmt->fetch();
    }

    /** Crea una unidad y devuelve su id. */
    public static function crear(PDO $db, string $codigo, string $nombre): int
    {
        $stmt = $db->prepare("INSERT INTO unidades_medida (codigo, nombre) VALUES (:cod, :nom) RETURNING id");
        $stmt->execute([':cod' => $codigo, ':nom' => $nombre]);
        return (int)$stmt->fetchColumn();
    }

    /** Borrado lógico de una unidad. */
    public static function eliminar(PDO $db, int $id): void
    {
        $db->prepare("UPDATE unidades_medida SET activo = false WHERE id = :id")->execute([':id' => $id]);
    }
}

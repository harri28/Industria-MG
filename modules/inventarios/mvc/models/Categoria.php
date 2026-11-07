<?php
/**
 * MODELO — Categoria (categorías de productos)
 * Ver ARQUITECTURA_MVC.md
 */
class Categoria
{
    /** Categorías activas con el conteo de productos activos de cada una. */
    public static function listar(PDO $db): array
    {
        return $db->query("
            SELECT c.*, COUNT(p.id) AS total_productos
            FROM categorias_producto c
            LEFT JOIN productos p ON p.categoria_id = c.id AND p.activo
            WHERE c.activo
            GROUP BY c.id ORDER BY c.nombre")->fetchAll();
    }

    /** Combo simple (id, nombre) de categorías activas. */
    public static function combo(PDO $db): array
    {
        return $db->query("SELECT id, nombre FROM categorias_producto WHERE activo ORDER BY nombre")->fetchAll();
    }

    /** Inserta o actualiza una categoría. */
    public static function guardar(PDO $db, array $in): void
    {
        if (!empty($in['id'])) {
            $db->prepare("UPDATE categorias_producto SET nombre=:nom, descripcion=:desc WHERE id=:id")
               ->execute([':id' => (int)$in['id'], ':nom' => trim($in['nombre']), ':desc' => ($in['descripcion'] ?? null) ?: null]);
        } else {
            $db->prepare("INSERT INTO categorias_producto (nombre, descripcion) VALUES (:nom, :desc)")
               ->execute([':nom' => trim($in['nombre']), ':desc' => ($in['descripcion'] ?? null) ?: null]);
        }
    }

    /** Cuenta productos activos que usan la categoría (para validar antes de eliminar). */
    public static function productosActivos(PDO $db, int $id): int
    {
        $stmt = $db->prepare("SELECT COUNT(*) FROM productos WHERE categoria_id=:id AND activo");
        $stmt->execute([':id' => $id]);
        return (int)$stmt->fetchColumn();
    }

    /** Elimina una categoría (borrado físico, como el comportamiento original). */
    public static function eliminar(PDO $db, int $id): void
    {
        $db->prepare("DELETE FROM categorias_producto WHERE id=:id")->execute([':id' => $id]);
    }
}

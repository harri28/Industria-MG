<?php
/**
 * MODELO — Kardex (movimientos de inventario)
 * Entradas/salidas/ajustes con saldo corrido y costo promedio ponderado.
 * Ver ARQUITECTURA_MVC.md
 */
class Kardex
{
    /**
     * Registra un movimiento (entrada/salida/ajuste) de forma atómica: actualiza stock y
     * precio_promedio del producto, inserta el asiento y resuelve la alerta de stock crítico.
     * Lanza Exception('Stock insuficiente.') si una salida supera el stock.
     * Devuelve ['nuevo_stock' => float, 'precio_promedio' => float].
     *
     * @param mixed $precioUnitarioRaw valor crudo de precio_unitario (null => usa el promedio actual).
     */
    public static function ajustar(PDO $db, int $prodId, string $tipo, float $qty, $precioUnitarioRaw, ?string $obs): array
    {
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("SELECT stock_actual, stock_minimo, precio_promedio FROM productos WHERE id = :id FOR UPDATE");
            $stmt->execute([':id' => $prodId]);
            $pr = $stmt->fetch();

            $stock_ant = (float)$pr['stock_actual'];
            $pp_ant    = (float)$pr['precio_promedio'];
            $pu        = $precioUnitarioRaw === null ? $pp_ant : (float)$precioUnitarioRaw;

            if ($tipo === 'salida') {
                if ($stock_ant < $qty) throw new Exception('Stock insuficiente.');
                $nuevo_stock = $stock_ant - $qty;
                $nuevo_pp    = $pp_ant; // el promedio no cambia en salida
            } elseif ($tipo === 'ajuste') {
                $nuevo_stock = $qty;         // el ajuste fija el stock al valor indicado
                $nuevo_pp    = $pu ?: $pp_ant;
            } else { // entrada
                $nuevo_stock = $stock_ant + $qty;
                $nuevo_pp    = $nuevo_stock > 0
                    ? (($stock_ant * $pp_ant) + ($qty * $pu)) / $nuevo_stock
                    : $pu;
            }

            $db->prepare("UPDATE productos SET stock_actual=:sa, precio_promedio=:pp, updated_at=NOW() WHERE id=:id")
               ->execute([':sa' => $nuevo_stock, ':pp' => $nuevo_pp, ':id' => $prodId]);

            $db->prepare("INSERT INTO kardex
                (producto_id, tipo, referencia_tipo, cantidad, precio_unitario,
                 saldo_cantidad, saldo_valor, observaciones, usuario_id)
                VALUES (:pid, :tipo, 'ajuste_manual', :qty, :pu, :sq, :sv, :obs, :uid)")->execute([
                ':pid' => $prodId, ':tipo' => $tipo, ':qty' => $qty, ':pu' => $pu,
                ':sq'  => $nuevo_stock, ':sv' => $nuevo_stock * $nuevo_pp, ':obs' => $obs, ':uid' => null,
            ]);

            if ($nuevo_stock > (float)$pr['stock_minimo']) {
                $db->prepare("UPDATE alertas SET leida=TRUE WHERE referencia_tipo='producto' AND referencia_id=:id AND tipo='stock_minimo'")
                   ->execute([':id' => $prodId]);
            }

            $db->commit();
            return ['nuevo_stock' => $nuevo_stock, 'precio_promedio' => $nuevo_pp];
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    /** Movimientos de un producto + totales (entradas/salidas/ajustes). */
    public static function listar(PDO $db, int $prodId, array $f): array
    {
        $where  = ['k.producto_id = :pid'];
        $params = [':pid' => $prodId];
        if (!empty($f['desde'])) { $where[] = "k.fecha >= :desde"; $params[':desde'] = $f['desde'] . ' 00:00:00'; }
        if (!empty($f['hasta'])) { $where[] = "k.fecha <= :hasta"; $params[':hasta'] = $f['hasta'] . ' 23:59:59'; }
        if (!empty($f['tipo']))  { $where[] = "k.tipo = :tipo";    $params[':tipo']  = $f['tipo']; }
        $wClause = implode(' AND ', $where);

        $stmtStats = $db->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN k.tipo='entrada' THEN k.cantidad ELSE 0 END), 0) AS ent_qty,
                COALESCE(SUM(CASE WHEN k.tipo='entrada' THEN k.cantidad * k.precio_unitario ELSE 0 END), 0) AS ent_val,
                COALESCE(SUM(CASE WHEN k.tipo='salida'  THEN k.cantidad ELSE 0 END), 0) AS sal_qty,
                COALESCE(SUM(CASE WHEN k.tipo='salida'  THEN k.cantidad * k.precio_unitario ELSE 0 END), 0) AS sal_val,
                COALESCE(SUM(CASE WHEN k.tipo='ajuste'  THEN ABS(k.cantidad) ELSE 0 END), 0) AS adj_qty,
                COUNT(*) AS total
            FROM kardex k WHERE $wClause");
        $stmtStats->execute($params);
        $stats = $stmtStats->fetch();

        $stmt = $db->prepare("
            SELECT k.*, u.nombre AS usuario_nombre
            FROM kardex k
            LEFT JOIN usuarios u ON u.id = k.usuario_id
            WHERE $wClause
            ORDER BY k.fecha DESC LIMIT 200");
        $stmt->execute($params);
        return ['movimientos' => $stmt->fetchAll(), 'stats' => $stats];
    }

    /** Movimientos globales por tipo (entrada/salida) de todos los materiales. */
    public static function global(PDO $db, string $tipo, array $f): array
    {
        $where  = ['k.tipo = :tipo'];
        $params = [':tipo' => $tipo];
        if (!empty($f['desde'])) { $where[] = "k.fecha >= :desde"; $params[':desde'] = $f['desde'] . ' 00:00:00'; }
        if (!empty($f['hasta'])) { $where[] = "k.fecha <= :hasta"; $params[':hasta'] = $f['hasta'] . ' 23:59:59'; }
        if (!empty($f['q']))     { $where[] = "(p.nombre ILIKE :q OR p.codigo ILIKE :q)"; $params[':q'] = '%' . $f['q'] . '%'; }
        $wClause = implode(' AND ', $where);

        $stmt = $db->prepare("
            SELECT k.*, p.nombre AS producto_nombre, p.codigo AS producto_codigo, u.nombre AS usuario_nombre
            FROM kardex k
            JOIN productos p ON p.id = k.producto_id
            LEFT JOIN usuarios u ON u.id = k.usuario_id
            WHERE $wClause
            ORDER BY k.fecha DESC LIMIT 200");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}

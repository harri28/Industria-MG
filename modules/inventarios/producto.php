<?php
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

require_once __DIR__ . '/../../config/database.php';
$db = getDB();

$stmt = $db->prepare("
    SELECT p.*, c.nombre AS categoria_nombre
    FROM productos p
    LEFT JOIN categorias_producto c ON c.id = p.categoria_id
    WHERE p.id = :id");
$stmt->execute([':id' => $id]);
$p = $stmt->fetch();
if (!$p) { header('Location: index.php'); exit; }

$page_title      = $p['codigo'] . ' — ' . $p['nombre'];
$page_breadcrumb = '<a href="index.php">Almacén</a> <span>/</span> Detalle';

require_once __DIR__ . '/../../includes/header.php';

$permisoAlmacen = nivelPermiso('inventarios');
if ($permisoAlmacen === 'ninguno') {
    echo '<div class="alert alert-danger" style="margin:20px">
        <i class="fa fa-lock"></i> No tienes permisos para acceder al módulo de Almacén. Contacta a un administrador.
    </div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}
$almacenSoloLectura = $permisoAlmacen !== 'completo';

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES); }

$esCritico  = (float)$p['stock_minimo'] > 0 && (float)$p['stock_actual'] <= (float)$p['stock_minimo'];
$valorTotal = (float)$p['stock_actual'] * (float)$p['precio_venta'];
?>

<div class="toolbar">
    <div class="toolbar-left">
        <a href="index.php" class="btn btn-secondary btn-sm"><i class="fa fa-arrow-left"></i> Volver a Almacén</a>
    </div>
    <?php if (!$almacenSoloLectura): ?>
    <div class="toolbar-right">
        <a href="index.php?editar=<?= $p['id'] ?>" class="btn btn-primary btn-sm"><i class="fa fa-pen"></i> Editar</a>
    </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body" style="display:grid;grid-template-columns:280px 1fr;gap:28px;align-items:start">

        <!-- IZQUIERDA: imagen -->
        <div>
            <?php if ($p['imagen']): ?>
            <img src="../../assets/uploads/materiales/<?= esc($p['imagen']) ?>"
                 style="width:100%;aspect-ratio:1;object-fit:cover;border-radius:var(--radius-sm);border:1px solid var(--border)">
            <?php else: ?>
            <div style="width:100%;aspect-ratio:1;border:1px dashed var(--border);border-radius:var(--radius-sm);
                        display:flex;align-items:center;justify-content:center;color:var(--text-secondary);font-size:.85rem">
                Sin imagen
            </div>
            <?php endif; ?>
            <div style="margin-top:12px;text-align:center;display:flex;gap:6px;justify-content:center;flex-wrap:wrap">
                <span class="badge <?= $p['es_repuesto'] ? 'badge-normal' : 'badge-activo' ?>">
                    <?= $p['es_repuesto'] ? 'Repuesto' : 'Material' ?>
                </span>
                <?php if ($esCritico): ?><span class="badge badge-retrasado">Stock crítico</span><?php endif; ?>
            </div>
        </div>

        <!-- DERECHA: detalle -->
        <div>
            <div style="font-size:.75rem;color:var(--text-muted);font-weight:700;text-transform:uppercase;margin-bottom:4px">
                <?= esc($p['codigo']) ?>
            </div>
            <h2 style="font-size:1.25rem;font-weight:700;margin-bottom:14px"><?= esc($p['nombre']) ?></h2>

            <?php if ($p['descripcion']): ?>
            <p style="color:var(--text-secondary);font-size:.88rem;margin-bottom:20px"><?= nl2br(esc($p['descripcion'])) ?></p>
            <?php endif; ?>

            <div style="display:flex;flex-direction:column;gap:10px">
                <div style="display:flex;gap:6px">
                    <span class="text-muted" style="min-width:150px">Categoría:</span>
                    <span style="font-weight:600"><?= esc($p['categoria_nombre'] ?? '—') ?></span>
                </div>
                <div style="display:flex;gap:6px">
                    <span class="text-muted" style="min-width:150px">Ubicación:</span>
                    <span style="font-weight:600"><?= esc($p['ubicacion'] ?: '—') ?></span>
                </div>
                <div style="display:flex;gap:6px">
                    <span class="text-muted" style="min-width:150px">Unidad:</span>
                    <span style="font-weight:600"><?= esc($p['unidad']) ?></span>
                </div>
                <div style="display:flex;gap:6px">
                    <span class="text-muted" style="min-width:150px">Stock Actual:</span>
                    <span style="font-weight:700;color:<?= $esCritico ? 'var(--danger)' : 'inherit' ?>">
                        <?= number_format((float)$p['stock_actual'], 2) ?>
                    </span>
                </div>
                <div style="display:flex;gap:6px">
                    <span class="text-muted" style="min-width:150px">Mín / Máx:</span>
                    <span style="font-weight:600">
                        <?= number_format((float)$p['stock_minimo'], 0) ?> /
                        <?= (float)$p['stock_maximo'] > 0 ? number_format((float)$p['stock_maximo'], 0) : '∞' ?>
                    </span>
                </div>
                <div style="display:flex;gap:6px">
                    <span class="text-muted" style="min-width:150px">Precio de Venta:</span>
                    <span style="font-weight:600"><?= formatMoney((float)$p['precio_venta']) ?></span>
                </div>
                <div style="display:flex;gap:6px">
                    <span class="text-muted" style="min-width:150px">Precio Promedio:</span>
                    <span style="font-weight:600"><?= formatMoney((float)$p['precio_promedio']) ?></span>
                </div>
                <div style="display:flex;gap:6px">
                    <span class="text-muted" style="min-width:150px">Valor en Stock:</span>
                    <span style="font-weight:600"><?= formatMoney($valorTotal) ?></span>
                </div>
                <div style="display:flex;gap:6px">
                    <span class="text-muted" style="min-width:150px">Método Valuación:</span>
                    <span style="font-weight:600"><?= esc(ucfirst($p['metodo_valuacion'])) ?></span>
                </div>
                <div style="display:flex;gap:6px">
                    <span class="text-muted" style="min-width:150px">Código de Barras:</span>
                    <span style="font-weight:600"><?= esc($p['codigo_barras'] ?: '—') ?></span>
                </div>
                <div style="display:flex;gap:6px">
                    <span class="text-muted" style="min-width:150px">Código Interno:</span>
                    <span style="font-weight:600"><?= esc($p['codigo_interno'] ?: '—') ?></span>
                </div>
                <div style="display:flex;gap:6px">
                    <span class="text-muted" style="min-width:150px">Actualizado:</span>
                    <span style="font-weight:600"><?= $p['updated_at'] ? date('d/m/Y H:i', strtotime($p['updated_at'])) : '—' ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

try {
    $kpis = $db->query("
        SELECT
            (SELECT COUNT(*) FROM proyectos WHERE estado NOT IN ('completado','cancelado') AND activo) AS proyectos_activos,
            (SELECT COUNT(*) FROM proyectos WHERE estado NOT IN ('completado','cancelado') AND activo AND fecha_entrega_estimada < CURRENT_DATE) AS proyectos_retrasados,
            (SELECT COUNT(*) FROM clientes WHERE activo) AS total_clientes,
            (SELECT COUNT(*) FROM proveedores WHERE activo) AS total_proveedores,
            (SELECT COUNT(*) FROM productos WHERE activo AND stock_actual <= stock_minimo AND stock_minimo > 0) AS stock_critico,
            (SELECT COUNT(*) FROM alertas WHERE leida = FALSE) AS alertas_pendientes
    ")->fetch();

    $proyectos_recientes = $db->query("
        SELECT p.id, p.codigo, p.nombre, p.estado, p.prioridad, p.porcentaje_avance,
               p.fecha_entrega_estimada, c.razon_social AS cliente
        FROM proyectos p
        LEFT JOIN clientes c ON c.id = p.cliente_id
        WHERE p.activo
        ORDER BY p.updated_at DESC LIMIT 8
    ")->fetchAll();

    $stock_critico = $db->query("
        SELECT codigo, nombre, stock_actual, stock_minimo, unidad
        FROM productos
        WHERE activo AND stock_actual <= stock_minimo AND stock_minimo > 0
        ORDER BY (stock_actual - stock_minimo) ASC LIMIT 8
    ")->fetchAll();

    // Datos para gráficos
    $estados_raw = $db->query("
        SELECT estado, COUNT(*) AS total
        FROM proyectos WHERE activo
        GROUP BY estado ORDER BY total DESC
    ")->fetchAll();

    $tendencia_raw = $db->query("
        SELECT TO_CHAR(DATE_TRUNC('month', created_at), 'Mon YY') AS mes,
               COUNT(*) AS total
        FROM proyectos
        WHERE created_at >= NOW() - INTERVAL '6 months'
        GROUP BY DATE_TRUNC('month', created_at)
        ORDER BY DATE_TRUNC('month', created_at)
    ")->fetchAll();

    $stock_chart_raw = $db->query("
        SELECT
            CASE WHEN LENGTH(nombre) > 20 THEN SUBSTRING(nombre,1,18)||'…' ELSE nombre END AS nombre,
            ROUND(stock_actual::numeric,2) AS stock_actual,
            ROUND(stock_minimo::numeric,2) AS stock_minimo
        FROM productos
        WHERE activo AND stock_minimo > 0
        ORDER BY (stock_actual::float / NULLIF(stock_minimo,0)) ASC
        LIMIT 7
    ")->fetchAll();

} catch (Exception $e) {
    $kpis = ['proyectos_activos'=>0,'proyectos_retrasados'=>0,'total_clientes'=>0,'total_proveedores'=>0,'stock_critico'=>0,'alertas_pendientes'=>0];
    $proyectos_recientes = $stock_critico = $estados_raw = $tendencia_raw = $stock_chart_raw = [];
}

// Preparar JSON para gráficos
$estado_labels_map = ['fabricacion'=>'Fabricación','pruebas'=>'Pruebas','entrega'=>'Entrega','completado'=>'Completado','cancelado'=>'Cancelado'];
$estado_colors_map = ['fabricacion'=>'#3b82f6','pruebas'=>'#f59e0b','entrega'=>'#8b5cf6','completado'=>'#10b981','cancelado'=>'#ef4444'];

$ch_estado_labels = $ch_estado_data = $ch_estado_colors = [];
foreach ($estados_raw as $r) {
    $ch_estado_labels[] = $estado_labels_map[$r['estado']] ?? $r['estado'];
    $ch_estado_data[]   = (int)$r['total'];
    $ch_estado_colors[] = $estado_colors_map[$r['estado']] ?? '#94a3b8';
}

$ch_tend_labels = array_column($tendencia_raw, 'mes');
$ch_tend_data   = array_map('intval', array_column($tendencia_raw, 'total'));

$ch_stock_labels  = array_column($stock_chart_raw, 'nombre');
$ch_stock_actual  = array_map('floatval', array_column($stock_chart_raw, 'stock_actual'));
$ch_stock_minimo  = array_map('floatval', array_column($stock_chart_raw, 'stock_minimo'));

function badgeEstado($e) {
    $map = ['fabricacion'=>'Fabricación','pruebas'=>'Pruebas','entrega'=>'Entrega','completado'=>'Completado','cancelado'=>'Cancelado'];
    return '<span class="badge badge-'.htmlspecialchars($e).'">'.htmlspecialchars($map[$e] ?? $e).'</span>';
}
?>

<style>
/* ── KPI Cards ── */
.dash-kpi-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin-bottom: 18px;
}
.dash-kpi {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 4px rgba(0,0,0,.07);
    padding: 18px 20px;
    display: flex;
    align-items: center;
    gap: 14px;
    border-left: 4px solid transparent;
    position: relative;
    overflow: hidden;
    transition: transform .15s, box-shadow .15s;
    cursor: default;
}
.dash-kpi:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.11); }
.dash-kpi.c-blue   { border-left-color: #3b82f6; }
.dash-kpi.c-red    { border-left-color: #ef4444; }
.dash-kpi.c-green  { border-left-color: #10b981; }
.dash-kpi.c-cyan   { border-left-color: #06b6d4; }
.dash-kpi.c-orange { border-left-color: #f59e0b; }
.dash-kpi.c-purple { border-left-color: #8b5cf6; }

.dash-kpi-icon {
    width: 50px; height: 50px; border-radius: 12px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 1.25rem;
}
.c-blue   .dash-kpi-icon { background:#eff6ff; color:#3b82f6; }
.c-red    .dash-kpi-icon { background:#fef2f2; color:#ef4444; }
.c-green  .dash-kpi-icon { background:#ecfdf5; color:#10b981; }
.c-cyan   .dash-kpi-icon { background:#ecfeff; color:#06b6d4; }
.c-orange .dash-kpi-icon { background:#fffbeb; color:#f59e0b; }
.c-purple .dash-kpi-icon { background:#f5f3ff; color:#8b5cf6; }

.dash-kpi-num   { font-size: 2rem; font-weight: 700; line-height: 1; color: #0f172a; }
.dash-kpi-label { font-size: .76rem; color: #64748b; margin-top: 4px; font-weight: 500; }

.dash-kpi-ghost {
    position: absolute; right: -6px; top: 50%;
    transform: translateY(-50%);
    font-size: 4.5rem; opacity: .04; pointer-events: none;
}

/* ── Charts ── */
.dash-charts-grid {
    display: grid;
    grid-template-columns: 1fr 1.7fr 1.3fr;
    gap: 14px;
    margin-bottom: 18px;
}
.dash-chart-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 4px rgba(0,0,0,.07);
    padding: 18px 20px;
}
.dash-chart-title {
    font-size: .72rem; font-weight: 700; letter-spacing: .07em;
    text-transform: uppercase; color: #475569;
    margin-bottom: 14px; display: flex; align-items: center; gap: 7px;
}
.dash-chart-title i { color: #94a3b8; }
</style>

<!-- KPI Cards -->
<div class="dash-kpi-grid">
    <div class="dash-kpi c-blue">
        <div class="dash-kpi-icon"><i class="fa fa-industry"></i></div>
        <div>
            <div class="dash-kpi-num"><?= $kpis['proyectos_activos'] ?></div>
            <div class="dash-kpi-label">Proyectos en curso</div>
        </div>
        <i class="fa fa-industry dash-kpi-ghost"></i>
    </div>
    <div class="dash-kpi c-red">
        <div class="dash-kpi-icon"><i class="fa fa-triangle-exclamation"></i></div>
        <div>
            <div class="dash-kpi-num"><?= $kpis['proyectos_retrasados'] ?></div>
            <div class="dash-kpi-label">Proyectos retrasados</div>
        </div>
        <i class="fa fa-triangle-exclamation dash-kpi-ghost"></i>
    </div>
    <div class="dash-kpi c-green">
        <div class="dash-kpi-icon"><i class="fa fa-users"></i></div>
        <div>
            <div class="dash-kpi-num"><?= $kpis['total_clientes'] ?></div>
            <div class="dash-kpi-label">Clientes registrados</div>
        </div>
        <i class="fa fa-users dash-kpi-ghost"></i>
    </div>
    <div class="dash-kpi c-cyan">
        <div class="dash-kpi-icon"><i class="fa fa-truck"></i></div>
        <div>
            <div class="dash-kpi-num"><?= $kpis['total_proveedores'] ?></div>
            <div class="dash-kpi-label">Proveedores activos</div>
        </div>
        <i class="fa fa-truck dash-kpi-ghost"></i>
    </div>
    <div class="dash-kpi c-orange">
        <div class="dash-kpi-icon"><i class="fa fa-boxes-stacked"></i></div>
        <div>
            <div class="dash-kpi-num"><?= $kpis['stock_critico'] ?></div>
            <div class="dash-kpi-label">Productos en stock crítico</div>
        </div>
        <i class="fa fa-boxes-stacked dash-kpi-ghost"></i>
    </div>
    <div class="dash-kpi c-purple">
        <div class="dash-kpi-icon"><i class="fa fa-bell"></i></div>
        <div>
            <div class="dash-kpi-num"><?= $kpis['alertas_pendientes'] ?></div>
            <div class="dash-kpi-label">Alertas pendientes</div>
        </div>
        <i class="fa fa-bell dash-kpi-ghost"></i>
    </div>
</div>

<!-- Gráficos interactivos -->
<div class="dash-charts-grid">

    <div class="dash-chart-card">
        <div class="dash-chart-title"><i class="fa fa-chart-pie"></i> Proyectos por Estado</div>
        <div style="position:relative;height:230px">
            <canvas id="chartEstados"></canvas>
        </div>
    </div>

    <div class="dash-chart-card">
        <div class="dash-chart-title"><i class="fa fa-chart-line"></i> Proyectos Creados — Últimos 6 meses</div>
        <div style="position:relative;height:230px">
            <canvas id="chartTendencia"></canvas>
        </div>
    </div>

    <div class="dash-chart-card">
        <div class="dash-chart-title"><i class="fa fa-chart-bar"></i> Stock Crítico vs Mínimo</div>
        <div style="position:relative;height:230px">
            <canvas id="chartStock"></canvas>
        </div>
    </div>

</div>

<!-- Tablas -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">

    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fa fa-industry"></i> Proyectos Recientes</span>
            <div class="card-actions">
                <a href="modules/produccion/index.php" class="btn btn-outline btn-sm">Ver todos</a>
            </div>
        </div>
        <div class="table-responsive">
            <table>
                <thead><tr><th>Proyecto</th><th>Cliente</th><th>Estado</th><th>Avance</th></tr></thead>
                <tbody>
                    <?php if (empty($proyectos_recientes)): ?>
                    <tr><td colspan="4" class="text-center text-muted">Sin proyectos.</td></tr>
                    <?php else: ?>
                    <?php foreach ($proyectos_recientes as $p):
                        $retrasado = $p['fecha_entrega_estimada'] && $p['fecha_entrega_estimada'] < date('Y-m-d') && !in_array($p['estado'], ['completado','cancelado']);
                    ?>
                    <tr>
                        <td>
                            <a href="modules/produccion/proyecto.php?id=<?= $p['id'] ?>" class="text-primary font-semibold text-sm"><?= htmlspecialchars($p['codigo']) ?></a>
                            <div class="text-xs text-muted"><?= htmlspecialchars($p['nombre']) ?></div>
                            <?php if ($retrasado): ?><span style="font-size:.65rem;color:var(--danger);font-weight:600">⚠ Retrasado</span><?php endif; ?>
                        </td>
                        <td class="text-sm"><?= htmlspecialchars($p['cliente'] ?? '—') ?></td>
                        <td><?= badgeEstado($p['estado']) ?></td>
                        <td style="min-width:80px">
                            <div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:<?= $p['porcentaje_avance'] ?>%"></div></div>
                            <div class="text-xs text-right"><?= $p['porcentaje_avance'] ?>%</div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fa fa-triangle-exclamation" style="color:var(--warning)"></i> Stock Crítico</span>
            <div class="card-actions">
                <a href="modules/inventarios/index.php" class="btn btn-outline btn-sm">Ver inventario</a>
            </div>
        </div>
        <div class="table-responsive">
            <table>
                <thead><tr><th>Código</th><th>Producto</th><th class="text-right">Stock</th><th class="text-right">Mínimo</th></tr></thead>
                <tbody>
                    <?php if (empty($stock_critico)): ?>
                    <tr><td colspan="4" class="text-center text-muted">Sin productos en stock crítico. ✓</td></tr>
                    <?php else: ?>
                    <?php foreach ($stock_critico as $s): ?>
                    <tr>
                        <td class="text-xs text-muted"><?= htmlspecialchars($s['codigo']) ?></td>
                        <td class="text-sm"><?= htmlspecialchars($s['nombre']) ?></td>
                        <td class="text-right font-bold text-danger"><?= number_format($s['stock_actual'],2) ?> <?= htmlspecialchars($s['unidad']) ?></td>
                        <td class="text-right text-muted text-sm"><?= number_format($s['stock_minimo'],2) ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<?php $extra_js = json_encode([
    'estados_labels' => $ch_estado_labels,
    'estados_data'   => $ch_estado_data,
    'estados_colors' => $ch_estado_colors,
    'tend_labels'    => $ch_tend_labels,
    'tend_data'      => $ch_tend_data,
    'stock_labels'   => $ch_stock_labels,
    'stock_actual'   => $ch_stock_actual,
    'stock_minimo'   => $ch_stock_minimo,
]); ?>
<script>
(function() {
    const d = <?= $extra_js ?>;

    Chart.defaults.font.family = 'system-ui, -apple-system, sans-serif';
    Chart.defaults.color = '#64748b';

    // 1 — Donut: proyectos por estado
    new Chart(document.getElementById('chartEstados'), {
        type: 'doughnut',
        data: {
            labels: d.estados_labels,
            datasets: [{
                data: d.estados_data,
                backgroundColor: d.estados_colors,
                borderWidth: 3,
                borderColor: '#fff',
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { position: 'bottom', labels: { padding: 14, boxWidth: 11, font: { size: 11 } } },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.label}: ${ctx.parsed} proyecto${ctx.parsed !== 1 ? 's' : ''}`
                    }
                }
            }
        }
    });

    // 2 — Line: tendencia mensual
    new Chart(document.getElementById('chartTendencia'), {
        type: 'line',
        data: {
            labels: d.tend_labels,
            datasets: [{
                label: 'Proyectos creados',
                data: d.tend_data,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59,130,246,.10)',
                borderWidth: 2.5,
                pointBackgroundColor: '#3b82f6',
                pointRadius: 5, pointHoverRadius: 7,
                tension: 0.4, fill: true
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(0,0,0,.05)' } },
                x: { grid: { display: false } }
            }
        }
    });

    // 3 — Bar horizontal: stock actual vs mínimo
    new Chart(document.getElementById('chartStock'), {
        type: 'bar',
        data: {
            labels: d.stock_labels,
            datasets: [
                {
                    label: 'Stock actual',
                    data: d.stock_actual,
                    backgroundColor: 'rgba(239,68,68,.75)',
                    borderRadius: 4, borderSkipped: false
                },
                {
                    label: 'Stock mínimo',
                    data: d.stock_minimo,
                    backgroundColor: 'rgba(148,163,184,.35)',
                    borderRadius: 4, borderSkipped: false
                }
            ]
        },
        options: {
            indexAxis: 'y',
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { padding: 12, boxWidth: 11, font: { size: 11 } } },
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                x: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.05)' }, ticks: { precision: 0 } },
                y: { grid: { display: false }, ticks: { font: { size: 11 } } }
            }
        }
    });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

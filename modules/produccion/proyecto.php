<?php
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

require_once __DIR__ . '/../../config/database.php';
$db = getDB();

$stmt = $db->prepare("
    SELECT p.*, c.razon_social AS cliente_nombre, u.nombre AS responsable_nombre
    FROM proyectos p
    LEFT JOIN clientes c ON c.id = p.cliente_id
    LEFT JOIN usuarios u ON u.id = p.responsable_id
    WHERE p.id = :id");
$stmt->execute([':id' => $id]);
$p = $stmt->fetch();
if (!$p) { header('Location: index.php'); exit; }

$page_title      = $p['codigo'] . ' — ' . $p['nombre'];
$page_breadcrumb = '<a href="index.php">Producción</a> <span>›</span> Detalle';

$areas    = $db->query("SELECT id, nombre FROM areas WHERE activo ORDER BY nombre")->fetchAll();
$usuarios = $db->query("SELECT id, nombre FROM usuarios WHERE activo ORDER BY nombre")->fetchAll();
$productos= $db->query("SELECT id, codigo, nombre, unidad, precio_promedio, precio_venta FROM productos WHERE activo ORDER BY nombre")->fetchAll();

// Áreas del workflow para este proyecto
$stmtWf = $db->prepare("
    SELECT pa.*, a.nombre AS area_nombre, a.es_externa, u.nombre AS responsable_nombre
    FROM proyecto_areas pa
    JOIN areas a ON a.id = pa.area_id
    LEFT JOIN usuarios u ON u.id = pa.responsable_id
    WHERE pa.proyecto_id = :id
    ORDER BY pa.orden, a.nombre");
$stmtWf->execute([':id' => $id]);
$workflow_areas = $stmtWf->fetchAll();

require_once __DIR__ . '/../../includes/header.php';

$retrasado = $p['fecha_entrega_estimada'] && $p['fecha_entrega_estimada'] < date('Y-m-d') && !in_array($p['estado'], ['completado','cancelado']);
$desviacion = $p['costo_estimado'] > 0 ? (($p['costo_real'] - $p['costo_estimado']) / $p['costo_estimado'] * 100) : 0;
?>

<!-- CABECERA DEL PROYECTO -->
<div class="card mb-2">
    <div class="card-body" style="display:flex;align-items:flex-start;gap:20px;flex-wrap:wrap">
        <div style="flex:1;min-width:240px">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
                <span style="font-size:.75rem;color:var(--text-muted);font-weight:700;text-transform:uppercase"><?= esc($p['codigo']) ?></span>
                <span class="badge badge-<?= $p['estado'] ?>"><?= estadoLabel($p['estado']) ?></span>
                <span class="badge badge-<?= $p['prioridad'] ?>"><?= $p['prioridad'] ?></span>
                <?php if ($retrasado): ?><span class="badge badge-retrasado">⚠ Retrasado</span><?php endif; ?>
            </div>
            <h2 style="font-size:1.15rem;font-weight:700;margin-bottom:4px"><?= esc($p['nombre']) ?></h2>
            <div style="font-size:.82rem;color:var(--text-secondary)"><?= esc($p['cliente_nombre'] ?? '—') ?> &nbsp;·&nbsp; Resp: <?= esc($p['responsable_nombre'] ?? '—') ?></div>
        </div>

        <!-- KPIs del proyecto -->
        <div style="display:flex;gap:24px;flex-wrap:wrap">
            <div>
                <div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase">Inicio</div>
                <div style="font-weight:600"><?= $p['fecha_inicio'] ? date('d/m/Y', strtotime($p['fecha_inicio'])) : '—' ?></div>
            </div>
            <div>
                <div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase">Entrega Est.</div>
                <div style="font-weight:600;<?= $retrasado ? 'color:var(--danger)' : '' ?>"><?= $p['fecha_entrega_estimada'] ? date('d/m/Y', strtotime($p['fecha_entrega_estimada'])) : '—' ?></div>
            </div>
            <div>
                <div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase">Costo Estimado</div>
                <div style="font-weight:600"><?= formatMoney($p['costo_estimado']) ?></div>
            </div>
            <div>
                <div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase">Costo Real</div>
                <div style="font-weight:600;<?= $desviacion > 10 ? 'color:var(--danger)' : ($desviacion > 0 ? 'color:var(--warning)' : 'color:var(--success)') ?>">
                    <?= formatMoney($p['costo_real']) ?>
                    <?php if ($p['costo_estimado'] > 0): ?>
                    <span style="font-size:.7rem">(<?= $desviacion > 0 ? '+' : '' ?><?= number_format($desviacion, 1) ?>%)</span>
                    <?php endif; ?>
                </div>
            </div>
            <div style="min-width:120px">
                <div style="font-size:.68rem;color:var(--text-muted);text-transform:uppercase">Avance</div>
                <div class="progress-bar-wrap" style="margin-top:6px">
                    <div class="progress-bar-fill <?= $p['porcentaje_avance'] >= 75 ? 'green' : ($p['porcentaje_avance'] >= 40 ? '' : 'red') ?>"
                         style="width:<?= $p['porcentaje_avance'] ?>%"></div>
                </div>
                <div style="font-size:.72rem;text-align:right;font-weight:600"><?= $p['porcentaje_avance'] ?>%</div>
            </div>
        </div>

        <div style="display:flex;gap:6px">
            <button class="btn btn-outline btn-sm" onclick="Modal.open('modalEditarProyecto')"><i class="fa fa-pencil"></i> Editar</button>
            <button class="btn btn-primary btn-sm" onclick="Modal.open('modalCambiarEstado')"><i class="fa fa-arrow-right"></i> Cambiar Estado</button>
        </div>
    </div>
</div>

<!-- TABS -->
<div class="tabs">
    <div class="tab active"  data-group="pry" data-target="tabWorkflow">Workflow por Áreas</div>
    <div class="tab"         data-group="pry" data-target="tabBom">Lista de Materiales</div>
    <div class="tab"         data-group="pry" data-target="tabAsignar">Asignar Áreas</div>
    <div class="tab"         data-group="pry" data-target="tabPiezas">Piezas Producidas</div>
    <div class="tab"         data-group="pry" data-target="tabCostos">Seguimiento de Costos</div>
    <div class="tab"         data-group="pry" data-target="tabAvances">Avances por Área</div>
    <div class="tab"         data-group="pry" data-target="tabAlertas">Alertas</div>
</div>

<!-- ======================================================
     TAB: WORKFLOW POR ÁREAS
     ====================================================== -->
<div class="tab-content active" data-group="pry" id="tabWorkflow">
<?php
$areas_internas = array_values(array_filter($workflow_areas, fn($wa) => !$wa['es_externa']));
$areas_externas = array_values(array_filter($workflow_areas, fn($wa) =>  $wa['es_externa']));

$iconos = [
    'Corte'              => 'fa-scissors',
    'Torno'              => 'fa-circle-notch',
    'Taladro'            => 'fa-screwdriver',
    'Soldadura'          => 'fa-fire',
    'Rolado'             => 'fa-arrows-rotate',
    'Ensamble'           => 'fa-gears',
    'Electricidad'       => 'fa-bolt',
    'Pintura'            => 'fa-paint-roller',
    'Control de Calidad' => 'fa-circle-check',
];
$estadoClase = [
    'pendiente'  => 'badge-normal',
    'en_proceso' => 'badge-fabricacion',
    'completado' => 'badge-completado',
    'bloqueado'  => 'badge-cancelado',
];
$estadoLabel = [
    'pendiente'  => 'Pendiente',
    'en_proceso' => 'En proceso',
    'completado' => 'Completado',
    'bloqueado'  => 'Bloqueado',
];

// Helper para renderizar una tarjeta de área
function renderAreaCard($wa, $iconos, $estadoClase, $estadoLabel) {
    $icono  = $iconos[$wa['area_nombre']] ?? 'fa-industry';
    $cls    = $estadoClase[$wa['estado']] ?? 'badge-normal';
    $lbl    = $estadoLabel[$wa['estado']] ?? $wa['estado'];
    $pct    = (int)$wa['porcentaje'];
    $barCls = $pct >= 75 ? 'green' : ($pct >= 40 ? '' : ($pct > 0 ? 'red' : ''));
    ob_start(); ?>
    <a href="area.php?id=<?= $wa['id'] ?>" class="card" data-wa-id="<?= $wa['id'] ?>"
       style="margin:0;
              background:linear-gradient(145deg,#eef5ff,#e2edfb);
              border:1px solid #b8d0ee;
              box-shadow:0 2px 8px rgba(37,99,235,.09);
              display:block;text-decoration:none;color:inherit;cursor:pointer;
              transition:box-shadow .18s,transform .18s"
       onmouseover="this.style.boxShadow='0 6px 20px rgba(37,99,235,.18)';this.style.transform='translateY(-2px)'"
       onmouseout="this.style.boxShadow='0 2px 8px rgba(37,99,235,.09)';this.style.transform=''">
        <div class="card-body" style="padding:14px 16px">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
                <div style="display:flex;align-items:center;gap:8px">
                    <span style="width:32px;height:32px;border-radius:50%;background:#dbeafe;
                                 display:flex;align-items:center;justify-content:center;color:#2563eb;font-size:.9rem">
                        <i class="fa <?= $icono ?>"></i>
                    </span>
                    <strong style="font-size:.88rem"><?= htmlspecialchars($wa['area_nombre'], ENT_QUOTES) ?></strong>
                </div>
                <span class="badge <?= $cls ?>" style="font-size:.65rem"><?= $lbl ?></span>
            </div>
            <div style="margin-bottom:6px">
                <div class="progress-bar-wrap" style="height:8px">
                    <div class="progress-bar-fill <?= $barCls ?>" style="width:<?= $pct ?>%"></div>
                </div>
                <div style="font-size:.72rem;text-align:right;font-weight:600;margin-top:3px"><?= $pct ?>%</div>
            </div>
            <div style="font-size:.75rem;color:var(--text-muted);margin-bottom:4px">
                <i class="fa fa-user" style="width:12px"></i>
                <?= htmlspecialchars($wa['responsable_nombre'] ?? '— Sin responsable', ENT_QUOTES) ?>
            </div>
            <div id="solBadge-<?= $wa['area_id'] ?>" style="display:none;font-size:.73rem;color:#d97706;
                font-weight:600;margin-bottom:5px;padding:3px 8px;background:#fffbeb;
                border-radius:4px;border:1px solid #fcd34d">
                <i class="fa fa-triangle-exclamation"></i> <span></span>
            </div>
            <?php if ($wa['observaciones']): ?>
            <div style="font-size:.73rem;color:var(--text-secondary);background:var(--bg-muted,#f8fafc);
                        border-radius:4px;padding:5px 8px;line-height:1.4">
                <?= htmlspecialchars($wa['observaciones'], ENT_QUOTES) ?>
            </div>
            <?php endif; ?>
        </div>
    </a>
    <?php return ob_get_clean();
}
?>

    <!-- ── Workflow interno ── -->
    <div class="card" style="margin-bottom:16px">
        <div class="card-header">
            <span class="card-title"><i class="fa fa-diagram-project"></i> Workflow por Áreas</span>
            <div class="card-actions" style="gap:10px">
                <span style="font-size:.8rem;color:var(--text-muted)">
                    Avance global calculado como promedio de las <?= count($areas_internas) ?> áreas internas
                </span>
                <button class="btn btn-outline btn-sm" onclick="Modal.open('modalAjustesWf');cargarAjustesWf()">
                    <i class="fa fa-sliders"></i> Ajustes de Workflow
                </button>
            </div>
        </div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;margin-top:4px">
                <?php foreach ($areas_internas as $wa): ?>
                <?= renderAreaCard($wa, $iconos, $estadoClase, $estadoLabel) ?>
                <?php endforeach; ?>

                <!-- Tarjeta-botón Agregar Área -->
                <div onclick="abrirModalWorkflowAgregar()"
                     style="margin:0;min-height:150px;border:2px dashed #b8d0ee;border-radius:8px;
                            background:#eef5ff;cursor:pointer;display:flex;align-items:center;
                            justify-content:center;transition:background .15s,border-color .15s"
                     onmouseover="this.style.background='#dbeafe';this.style.borderColor='#2563eb'"
                     onmouseout="this.style.background='#eef5ff';this.style.borderColor='#b8d0ee'">
                    <div style="text-align:center;color:#2563eb;pointer-events:none">
                        <i class="fa fa-plus" style="font-size:1.6rem;display:block;margin-bottom:8px;opacity:.7"></i>
                        <div style="font-size:.88rem;font-weight:600">Agregar Área</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Servicios externos ── -->
    <div class="card" style="margin-bottom:16px">
        <div class="card-header">
            <span class="card-title">
                <i class="fa fa-truck-fast" style="color:#f59e0b"></i> Servicios Externos
            </span>
            <div class="card-actions" style="font-size:.78rem;color:var(--text-muted)">
                Procesos realizados fuera de planta — no inciden en el avance global
            </div>
        </div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;margin-top:4px">
                <?php foreach ($areas_externas as $wa):
                    $icono  = $iconos[$wa['area_nombre']] ?? 'fa-truck-fast';
                    $cls    = $estadoClase[$wa['estado']] ?? 'badge-normal';
                    $lbl    = $estadoLabel[$wa['estado']] ?? $wa['estado'];
                    $pct    = (int)$wa['porcentaje'];
                    $barCls = $pct >= 75 ? 'green' : ($pct >= 40 ? '' : ($pct > 0 ? 'red' : ''));
                    $svcData = htmlspecialchars(json_encode([
                        'waId'          => $wa['id'],
                        'nombre'        => $wa['area_nombre'],
                        'estado'        => $wa['estado'],
                        'porcentaje'    => $pct,
                        'responsable_id'=> $wa['responsable_id'] ?? '',
                        'observaciones' => $wa['observaciones'] ?? '',
                    ]), ENT_QUOTES);
                ?>
                <div class="card" style="margin:0;border:1px solid #fcd34d;
                            box-shadow:0 2px 6px rgba(245,158,11,.15);border-left:4px solid #f59e0b;
                            display:flex;flex-direction:column">
                    <!-- Cuerpo clicable → ir a area.php -->
                    <div class="card-body" style="padding:14px 16px;cursor:pointer;flex:1"
                         onclick="window.location='area.php?id=<?= $wa['id'] ?>'">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
                            <div style="display:flex;align-items:center;gap:8px">
                                <span style="width:32px;height:32px;border-radius:50%;background:#fffbeb;
                                             display:flex;align-items:center;justify-content:center;
                                             color:#d97706;font-size:.9rem">
                                    <i class="fa <?= $icono ?>"></i>
                                </span>
                                <div>
                                    <strong style="font-size:.88rem"><?= esc($wa['area_nombre']) ?></strong>
                                    <div style="font-size:.68rem;color:#d97706;font-weight:600;text-transform:uppercase;letter-spacing:.4px">
                                        Servicio externo
                                    </div>
                                </div>
                            </div>
                            <span class="badge <?= $cls ?>" style="font-size:.65rem"><?= $lbl ?></span>
                        </div>
                        <div style="margin-bottom:6px">
                            <div class="progress-bar-wrap" style="height:8px">
                                <div class="progress-bar-fill <?= $barCls ?>" style="width:<?= $pct ?>%;background:#f59e0b"></div>
                            </div>
                            <div style="font-size:.72rem;text-align:right;font-weight:600;margin-top:3px"><?= $pct ?>%</div>
                        </div>
                        <div style="font-size:.75rem;color:var(--text-muted)">
                            <i class="fa fa-user" style="width:12px"></i>
                            <?= esc($wa['responsable_nombre'] ?? '— Sin responsable') ?>
                        </div>
                        <?php if ($wa['observaciones']): ?>
                        <div style="font-size:.73rem;background:#fffbeb;border-radius:4px;
                                    padding:5px 8px;margin-top:6px;line-height:1.4;color:#92400e">
                            <?= esc($wa['observaciones']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <!-- Barra de acciones -->
                    <div style="padding:6px 10px;border-top:1px solid #fef3c7;display:flex;
                                justify-content:flex-end;gap:6px;background:rgba(255,251,235,.6)">
                        <button class="btn btn-outline btn-xs"
                                onclick="editarServicio(<?= $svcData ?>)"
                                title="Editar servicio">
                            <i class="fa fa-pencil"></i> Editar
                        </button>
                        <button class="btn btn-outline btn-xs" style="color:var(--danger)"
                                onclick="eliminarServicio(<?= $wa['id'] ?>, '<?= esc($wa['area_nombre']) ?>')"
                                title="Eliminar servicio">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Tarjeta-botón Agregar Servicio -->
                <div onclick="abrirModalServicio()"
                     style="margin:0;min-height:150px;border:2px dashed #fcd34d;border-radius:8px;
                            background:#fffbeb;cursor:pointer;display:flex;align-items:center;
                            justify-content:center;transition:background .15s,border-color .15s"
                     onmouseover="this.style.background='#fef3c7';this.style.borderColor='#f59e0b'"
                     onmouseout="this.style.background='#fffbeb';this.style.borderColor='#fcd34d'">
                    <div style="text-align:center;color:#d97706;pointer-events:none">
                        <i class="fa fa-plus" style="font-size:1.6rem;display:block;margin-bottom:8px;opacity:.7"></i>
                        <div style="font-size:.88rem;font-weight:600">Agregar Servicio</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ======================================================
     TAB: BOM — Área de Trabajo
     ====================================================== -->
<div class="tab-content active" data-group="pry" id="tabBom">
    <div class="card">
        <div class="card-header">
            <span class="card-title">
                <i class="fa fa-list-check"></i>
                Lista de Materiales para <strong><?= esc($p['nombre']) ?></strong>
            </span>
            <div class="card-actions" style="gap:8px">
                <button class="btn btn-outline btn-sm" onclick="abrirModalPlantilla()">
                    <i class="fa fa-file-import"></i> Cargar Plantilla
                </button>
                <button class="btn btn-outline btn-sm" onclick="configurarPieza()">
                    <i class="fa fa-cog"></i> Configurar Material
                </button>
                <button class="btn btn-primary btn-sm" onclick="abrirModalCrearPieza()">
                    <i class="fa fa-plus"></i> Crear Pieza
                </button>
            </div>
        </div>
        <div class="card-body" style="padding:14px">

            <!-- Contenedor de piezas -->
            <div id="bomContainer">
                <div class="loading"><div class="spinner"></div> Cargando…</div>
            </div>

        </div>
    </div>
</div>

<!-- ======================================================
     TAB: ASIGNAR ÁREAS
     ====================================================== -->
<div class="tab-content" data-group="pry" id="tabAsignar">
    <div class="card">
        <div class="card-header">
            <span class="card-title">
                <i class="fa fa-route"></i> Asignar Áreas a Partes
            </span>
            <div class="card-actions">
                <span id="asignarResumen" style="font-size:.78rem;color:var(--text-muted)"></span>
            </div>
        </div>
        <div style="padding:14px 18px;font-size:.83rem;color:var(--text-muted);border-bottom:1px solid var(--border)">
            Cada parte es la unidad de trabajo del taller. Asígnale el área donde será fabricada.
            El icono <i class="fa fa-globe" style="color:#f59e0b"></i> indica que el área es externa (servicio tercerizado).
            Una vez la parte entra a estado "en proceso", el área queda fija.
        </div>
        <div id="asignarContainer" style="padding:6px 0"></div>
    </div>
</div>

<!-- ======================================================
     TAB: PIEZAS PRODUCIDAS
     ====================================================== -->
<div class="tab-content" data-group="pry" id="tabPiezas">
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fa fa-cubes-stacked"></i> Piezas Producidas</span>
            <div class="card-actions">
                <span id="piezasResumen" style="font-size:.78rem;color:var(--text-muted)"></span>
            </div>
        </div>
        <div style="padding:14px 18px;font-size:.83rem;color:var(--text-muted);border-bottom:1px solid var(--border)">
            Piezas físicas generadas por las transformaciones de áreas. Desde aquí las envías a otra área (procesamiento)
            o las finalizas: integradas a la máquina o pasadas a stock como producto disponible.
        </div>
        <div id="piezasContainer" style="padding:14px 18px"></div>
    </div>
</div>

<!-- Modal: Enviar pieza a área -->
<div class="modal-overlay" id="modalEnviarArea">
    <div class="modal modal-md">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-paper-plane"></i> Enviar pieza a área</span>
            <button class="modal-close" onclick="Modal.close('modalEnviarArea')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="enviarPiezaId">
            <div id="enviarPiezaInfo" style="font-size:.85rem;color:var(--text-secondary);margin-bottom:12px"></div>
            <div class="form-group">
                <label class="form-label">Área *</label>
                <select class="form-control" id="enviarAreaSel"></select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalEnviarArea')">Cancelar</button>
            <button class="btn btn-primary" onclick="confirmarEnviarArea()">
                <i class="fa fa-paper-plane"></i> Enviar
            </button>
        </div>
    </div>
</div>

<!-- Modal: Finalizar pieza -->
<div class="modal-overlay" id="modalFinalizar">
    <div class="modal modal-md">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-flag-checkered"></i> Finalizar pieza</span>
            <button class="modal-close" onclick="Modal.close('modalFinalizar')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="finalizarPiezaId">
            <div id="finalizarPiezaInfo" style="font-size:.85rem;color:var(--text-secondary);margin-bottom:12px"></div>

            <div class="form-group">
                <label class="form-label">Destino *</label>
                <div style="display:flex;gap:14px;margin-top:6px">
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                        <input type="radio" name="finDestino" value="proyecto" checked
                               onchange="toggleStockFields(false)">
                        <i class="fa fa-puzzle-piece"></i> Integrar al proyecto
                    </label>
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                        <input type="radio" name="finDestino" value="stock"
                               onchange="toggleStockFields(true)">
                        <i class="fa fa-warehouse"></i> Pasar a stock
                    </label>
                </div>
            </div>

            <div id="finStockFields" style="display:none">
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Producto (existente o nuevo)</label>
                        <input type="text" class="form-control" id="finProductoNombre"
                               placeholder="Nombre del producto en inventario">
                        <div style="font-size:.7rem;color:var(--text-muted);margin-top:3px">
                            Se creará un nuevo producto si no existe.
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cantidad</label>
                        <input type="number" class="form-control" id="finCantidad" value="1" min="0.001" step="0.001">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Costo unitario S/</label>
                    <input type="number" class="form-control" id="finCostoUnitario" min="0" step="0.01" value="0">
                    <div style="font-size:.7rem;color:var(--text-muted);margin-top:3px">
                        Actualizará el precio promedio del producto.
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Observaciones</label>
                    <input type="text" class="form-control" id="finObs" placeholder="Opcional">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalFinalizar')">Cancelar</button>
            <button class="btn btn-primary" onclick="confirmarFinalizar()">
                <i class="fa fa-check"></i> Confirmar
            </button>
        </div>
    </div>
</div>

<!-- ======================================================
     TAB: COSTOS
     ====================================================== -->
<div class="tab-content" data-group="pry" id="tabCostos">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <!-- Resumen -->
        <div class="card">
            <div class="card-header"><span class="card-title">Planificado vs Real</span></div>
            <div class="card-body" id="costosResumen">
                <div class="loading"><div class="spinner"></div></div>
            </div>
        </div>
        <!-- Costos adicionales -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Costos Adicionales</span>
                <div class="card-actions">
                    <button class="btn btn-primary btn-sm" onclick="Modal.open('modalCosto')">
                        <i class="fa fa-plus"></i> Agregar
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table>
                    <thead><tr><th>Tipo</th><th>Descripción</th><th>Fecha</th><th class="text-right">Monto</th></tr></thead>
                    <tbody id="costosBody">
                        <tr><td colspan="4" class="text-center text-muted">Cargando…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================
     TAB: AVANCES
     ====================================================== -->
<div class="tab-content" data-group="pry" id="tabAvances">
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fa fa-chart-line"></i> Registro de Avances</span>
            <div class="card-actions">
                <button class="btn btn-primary btn-sm" onclick="Modal.open('modalAvance')">
                    <i class="fa fa-plus"></i> Registrar Avance
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table>
                <thead><tr><th>Fecha</th><th>Área</th><th>Usuario</th><th>Descripción</th><th class="text-right">% Avance</th></tr></thead>
                <tbody id="avancesBody">
                    <tr><td colspan="5" class="text-center text-muted">Cargando…</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ======================================================
     TAB: ALERTAS
     ====================================================== -->
<div class="tab-content" data-group="pry" id="tabAlertas">
    <div class="card card-body" id="alertasBody">
        <div class="loading"><div class="spinner"></div></div>
    </div>
</div>

<!-- ======================================================
     MODALES
     ====================================================== -->

<!-- Modal Agregar Área al Workflow -->
<div class="modal-overlay" id="modalWorkflowAgregar">
    <div class="modal" style="max-width:460px">
        <div class="modal-header">
            <span class="modal-title">
                <i class="fa fa-diagram-project" style="color:var(--primary)"></i> Agregar Área al Workflow
            </span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Nombre del área *</label>
                <input type="text" class="form-control" id="wfaAreaNombre"
                       placeholder="Ej: Pintura, Ensamble, Control de Calidad…">
            </div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Estado inicial</label>
                    <select class="form-control" id="wfaEstado">
                        <option value="pendiente">Pendiente</option>
                        <option value="en_proceso">En proceso</option>
                        <option value="completado">Completado</option>
                        <option value="bloqueado">Bloqueado</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">% Avance</label>
                    <input type="number" class="form-control" id="wfaPorcentaje"
                           min="0" max="100" step="5" value="0">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Responsable</label>
                <select class="form-control" id="wfaResponsable">
                    <option value="">— Sin asignar —</option>
                    <?php foreach ($usuarios as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= esc($u['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Observaciones</label>
                <textarea class="form-control" id="wfaObservaciones" rows="2"
                          placeholder="Notas, dependencias, bloqueos…"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalWorkflowAgregar')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarWorkflowAgregar()">
                <i class="fa fa-save"></i> Agregar
            </button>
        </div>
    </div>
</div>

<!-- Modal Agregar / Editar Servicio Externo -->
<div class="modal-overlay" id="modalServicio">
    <div class="modal" style="max-width:460px">
        <div class="modal-header">
            <span class="modal-title" id="modalServicioTitulo">
                <i class="fa fa-truck-fast" style="color:#f59e0b"></i> Agregar Servicio Externo
            </span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="svcWaId">
            <div class="form-group" id="svcNombreWrap">
                <label class="form-label">Nombre del servicio *</label>
                <input type="text" class="form-control" id="svcNombre"
                       placeholder="Ej: Galvanizado, Pintura exterior, Torneado CNC…">
            </div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select class="form-control" id="svcEstado">
                        <option value="pendiente">Pendiente</option>
                        <option value="en_proceso">En proceso</option>
                        <option value="completado">Completado</option>
                        <option value="bloqueado">Bloqueado</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">% Avance</label>
                    <input type="number" class="form-control" id="svcPorcentaje"
                           min="0" max="100" step="5" value="0">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Responsable</label>
                <select class="form-control" id="svcResponsable">
                    <option value="">— Sin asignar —</option>
                    <?php foreach ($usuarios as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= esc($u['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Observaciones</label>
                <textarea class="form-control" id="svcObservaciones" rows="2"
                          placeholder="Empresa externa, plazos, notas…"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalServicio')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarServicio()">
                <i class="fa fa-save"></i> Guardar
            </button>
        </div>
    </div>
</div>

<!-- Modal Workflow Área -->
<div class="modal-overlay" id="modalWorkflow">
    <div class="modal" style="max-width:460px">
        <div class="modal-header">
            <span class="modal-title" id="wfModalTitulo">Actualizar Área</span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="wfId">
            <input type="hidden" id="wfProyectoId" value="<?= $id ?>">
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select class="form-control" id="wfEstado">
                        <option value="pendiente">Pendiente</option>
                        <option value="en_proceso">En proceso</option>
                        <option value="completado">Completado</option>
                        <option value="bloqueado">Bloqueado</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">% Avance del área</label>
                    <input type="number" class="form-control" id="wfPorcentaje" min="0" max="100" step="5">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Responsable</label>
                <select class="form-control" id="wfResponsable">
                    <option value="">— Sin asignar —</option>
                    <?php foreach ($usuarios as $u): ?>
                    <option value="<?= $u['id'] ?>"><?= esc($u['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Observaciones</label>
                <textarea class="form-control" id="wfObservaciones" rows="3"
                          placeholder="Notas sobre el avance, bloqueos, etc."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalWorkflow')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarWorkflow()"><i class="fa fa-save"></i> Guardar</button>
        </div>
    </div>
</div>

<!-- Modal Crear Pieza -->
<div class="modal-overlay" id="modalCrearPieza">
    <div class="modal" style="max-width:440px">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-cube"></i> Nueva Pieza</span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Nombre de la pieza *</label>
                <input type="text" class="form-control" id="piezaNombre"
                       placeholder="Ej: Caja / Estructura de Poleas">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalCrearPieza')">Cancelar</button>
            <button class="btn btn-primary" onclick="crearPieza()">
                <i class="fa fa-save"></i> Crear
            </button>
        </div>
    </div>
</div>

<!-- Modal Configurar Pieza -->
<div class="modal-overlay" id="modalCfgPieza">
    <div class="modal" style="max-width:720px;width:95vw">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-cog"></i> Configurar Material — Propiedades Técnicas</span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body" style="padding:16px 20px">

            <!-- Selector de pieza -->
            <div class="form-group" style="margin-bottom:14px">
                <label class="form-label">Pieza</label>
                <select class="form-control" id="cfgPiezaSelect" onchange="onCfgPiezaChange()">
                    <option value="">— Seleccionar pieza —</option>
                </select>
            </div>

            <!-- Cuerpo (oculto hasta seleccionar pieza) -->
            <div id="cfgBody" style="display:none">
                <div class="tabs" style="margin-bottom:0;border-bottom:1px solid var(--border)">
                    <div class="tab active" data-group="cfg" data-target="cfgGeneral">General</div>
                    <div class="tab" data-group="cfg" data-target="cfgDimensiones">Dimensiones</div>
                    <div class="tab" data-group="cfg" data-target="cfgFisica">Física</div>
                    <div class="tab" data-group="cfg" data-target="cfgMecanica">Mecánica</div>
                    <div class="tab" data-group="cfg" data-target="cfgTermica">Térmica</div>
                    <div class="tab" data-group="cfg" data-target="cfgAcabado">Acabado</div>
                </div>

                <!-- Tab: General -->
                <div class="tab-content active" data-group="cfg" id="cfgGeneral" style="padding:16px 0">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                        <div class="form-group">
                            <label class="form-label">Material</label>
                            <input type="text" class="form-control" id="cfgMaterial" placeholder="Ej: Acero AISI 1045">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Categoría</label>
                            <select class="form-control" id="cfgCategoria">
                                <option value="">— Seleccionar —</option>
                                <option>Acero al carbono</option>
                                <option>Acero inoxidable</option>
                                <option>Acero aleado</option>
                                <option>Aluminio</option>
                                <option>Cobre</option>
                                <option>Titanio</option>
                                <option>Hierro fundido</option>
                                <option>Plástico</option>
                                <option>Madera</option>
                                <option>Compuesto</option>
                                <option>Otro</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Norma / Estándar</label>
                            <input type="text" class="form-control" id="cfgNorma" placeholder="Ej: ASTM A108, DIN 17200">
                        </div>
                        <div class="form-group" style="grid-column:1/-1">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" id="cfgDescripcion" rows="2"
                                      placeholder="Descripción general del material o pieza"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Tab: Dimensiones -->
                <div class="tab-content" data-group="cfg" id="cfgDimensiones" style="padding:16px 0">
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--border)">
                        <span style="font-size:.82rem;color:var(--text-muted);font-weight:600">Unidad de medida:</span>
                        <label style="display:flex;align-items:center;gap:5px;font-size:.85rem;cursor:pointer">
                            <input type="radio" name="cfgUnidad" value="mm" checked onchange="onCfgUnidadChange('mm')"> mm
                        </label>
                        <label style="display:flex;align-items:center;gap:5px;font-size:.85rem;cursor:pointer">
                            <input type="radio" name="cfgUnidad" value="cm" onchange="onCfgUnidadChange('cm')"> cm
                        </label>
                        <label style="display:flex;align-items:center;gap:5px;font-size:.85rem;cursor:pointer">
                            <input type="radio" name="cfgUnidad" value="m" onchange="onCfgUnidadChange('m')"> m
                        </label>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
                        <div class="form-group">
                            <label class="form-label">Largo (<span class="cfg-unit-label">mm</span>)</label>
                            <input type="number" class="form-control cfg-dim-field" id="cfgLargo" min="0" step="0.01">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ancho (<span class="cfg-unit-label">mm</span>)</label>
                            <input type="number" class="form-control cfg-dim-field" id="cfgAncho" min="0" step="0.01">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Alto (<span class="cfg-unit-label">mm</span>)</label>
                            <input type="number" class="form-control cfg-dim-field" id="cfgAlto" min="0" step="0.01">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Espesor (<span class="cfg-unit-label">mm</span>)</label>
                            <input type="number" class="form-control cfg-dim-field" id="cfgEspesor" min="0" step="0.01">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Radio (<span class="cfg-unit-label">mm</span>)</label>
                            <input type="number" class="form-control cfg-dim-field" id="cfgRadio" min="0" step="0.01">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Peso neto (kg)</label>
                            <input type="number" class="form-control" id="cfgPesoNeto" min="0" step="0.001">
                        </div>
                    </div>
                </div>

                <!-- Tab: Física -->
                <div class="tab-content" data-group="cfg" id="cfgFisica" style="padding:16px 0">
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
                        <div class="form-group">
                            <label class="form-label">Densidad (g/cm³)</label>
                            <input type="number" class="form-control" id="cfgDensidad" min="0" step="0.001">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Masa (kg)</label>
                            <input type="number" class="form-control" id="cfgMasa" min="0" step="0.001">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Volumen (cm³)</label>
                            <input type="number" class="form-control" id="cfgVolumen" min="0" step="0.01">
                        </div>
                    </div>
                </div>

                <!-- Tab: Mecánica -->
                <div class="tab-content" data-group="cfg" id="cfgMecanica" style="padding:16px 0">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                        <div class="form-group">
                            <label class="form-label">Resistencia a la tracción (MPa)</label>
                            <input type="number" class="form-control" id="cfgTraccion" min="0" step="0.1">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Límite elástico / Yield (MPa)</label>
                            <input type="number" class="form-control" id="cfgElastico" min="0" step="0.1">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Módulo de Young (GPa)</label>
                            <input type="number" class="form-control" id="cfgYoung" min="0" step="0.1">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Módulo de Corte (GPa)</label>
                            <input type="number" class="form-control" id="cfgCorte" min="0" step="0.1">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Coef. de Poisson</label>
                            <input type="number" class="form-control" id="cfgPoisson" min="0" max="0.5" step="0.001">
                        </div>
                        <div class="form-group" style="display:grid;grid-template-columns:1fr auto;gap:8px;align-items:end">
                            <div>
                                <label class="form-label">Dureza</label>
                                <input type="number" class="form-control" id="cfgDureza" min="0" step="0.1" placeholder="Valor">
                            </div>
                            <select class="form-control" id="cfgDurezaTipo" style="width:80px">
                                <option>HB</option>
                                <option>HRC</option>
                                <option>HV</option>
                                <option>HS</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Elongación (%)</label>
                            <input type="number" class="form-control" id="cfgElongacion" min="0" max="100" step="0.1">
                        </div>
                    </div>
                </div>

                <!-- Tab: Térmica -->
                <div class="tab-content" data-group="cfg" id="cfgTermica" style="padding:16px 0">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                        <div class="form-group">
                            <label class="form-label">Conductividad térmica (W/m·K)</label>
                            <input type="number" class="form-control" id="cfgCondTerm" min="0" step="0.01">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Calor específico (J/kg·K)</label>
                            <input type="number" class="form-control" id="cfgCalorEsp" min="0" step="0.1">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Coef. dilatación térmica (µm/m·°C)</label>
                            <input type="number" class="form-control" id="cfgDilatacion" min="0" step="0.01">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Temperatura de fusión (°C)</label>
                            <input type="number" class="form-control" id="cfgFusion" step="1">
                        </div>
                    </div>
                </div>

                <!-- Tab: Acabado -->
                <div class="tab-content" data-group="cfg" id="cfgAcabado" style="padding:16px 0">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                        <div class="form-group">
                            <label class="form-label">Acabado superficial — Ra</label>
                            <select class="form-control" id="cfgRa">
                                <option value="">— Sin especificar —</option>
                                <option value="N1 — Ra 0.025 µm">N1 — Ra 0.025 µm (espejo)</option>
                                <option value="N2 — Ra 0.05 µm">N2 — Ra 0.05 µm</option>
                                <option value="N3 — Ra 0.1 µm">N3 — Ra 0.1 µm</option>
                                <option value="N4 — Ra 0.2 µm">N4 — Ra 0.2 µm</option>
                                <option value="N5 — Ra 0.4 µm">N5 — Ra 0.4 µm</option>
                                <option value="N6 — Ra 0.8 µm">N6 — Ra 0.8 µm</option>
                                <option value="N7 — Ra 1.6 µm">N7 — Ra 1.6 µm</option>
                                <option value="N8 — Ra 3.2 µm">N8 — Ra 3.2 µm (torneado estándar)</option>
                                <option value="N9 — Ra 6.3 µm">N9 — Ra 6.3 µm</option>
                                <option value="N10 — Ra 12.5 µm">N10 — Ra 12.5 µm (desbaste)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tratamiento térmico</label>
                            <input type="text" class="form-control" id="cfgTratamiento"
                                   placeholder="Ej: Temple y revenido, Cementado">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Recubrimiento / Protección</label>
                            <input type="text" class="form-control" id="cfgRecubrimiento"
                                   placeholder="Ej: Niquelado, Cromado, Anodizado, Pintura epóxica">
                        </div>
                        <div class="form-group" style="grid-column:1/-1">
                            <label class="form-label">Notas técnicas</label>
                            <textarea class="form-control" id="cfgNotas" rows="3"
                                      placeholder="Observaciones adicionales, tolerancias, normas especiales..."></textarea>
                        </div>
                    </div>
                </div>
            </div><!-- /cfgBody -->

        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalCfgPieza')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarPropsPieza()">
                <i class="fa fa-save"></i> Guardar Propiedades
            </button>
        </div>
    </div>
</div>

<!-- Modal Crear Parte -->
<div class="modal-overlay" id="modalCrearParte">
    <div class="modal" style="max-width:440px">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-puzzle-piece"></i> Nueva Parte</span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="partePiezaId">
            <div class="form-group">
                <label class="form-label">Nombre de la parte *</label>
                <input type="text" class="form-control" id="parteNombre"
                       placeholder="Ej: Eje central / Base inferior">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalCrearParte')">Cancelar</button>
            <button class="btn btn-primary" onclick="crearParte()">
                <i class="fa fa-save"></i> Crear
            </button>
        </div>
    </div>
</div>

<!-- Modal BOM — Agregar Material a una Pieza -->
<div class="modal-overlay" id="modalBom">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">Agregar Material</span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="bomPiezaId">
            <div id="bomParteWrap" style="display:none" class="form-group">
                <label class="form-label"><i class="fa fa-puzzle-piece" style="color:#6d28d9;margin-right:4px"></i> Parte</label>
                <select class="form-control" id="bomParteSelect">
                    <option value="">— Sin parte —</option>
                </select>
            </div>
            <div id="bomPiezaLabel"
                 style="font-size:.78rem;color:var(--text-muted);margin-bottom:10px;
                        padding:6px 10px;background:#f1f5f9;border-radius:6px;display:none">
                <i class="fa fa-cube" style="margin-right:5px;color:var(--primary)"></i>
                <span id="bomPiezaNombreLabel"></span>
            </div>
            <div class="form-group" style="position:relative">
                <label class="form-label">Material *</label>
                <input type="text" class="form-control" id="bomProductoBuscar"
                       placeholder="Buscar por nombre o código…" autocomplete="off">
                <input type="hidden" id="bomProducto">
                <div id="bomProductoDrop"></div>
            </div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Cantidad *</label>
                    <input type="number" class="form-control" id="bomCantidad" min="1" step="1">
                </div>
                <div class="form-group">
                    <label class="form-label">Costo Unitario S/</label>
                    <input type="number" class="form-control" id="bomCosto" min="0" step="0.01">
                    <div id="bomCostoHint" style="display:none;font-size:.72rem;color:#16a34a;margin-top:3px">
                        <i class="fa fa-box-archive"></i> Precio de venta del inventario
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Observaciones</label>
                <input type="text" class="form-control" id="bomObs" placeholder="Opcional">
            </div>
            <div class="form-row cols-3">
                <div class="form-group">
                    <label class="form-label">Medida</label>
                    <input type="number" class="form-control" id="bomMedida" min="0" step="1" placeholder="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Unidad de Medida</label>
                    <select class="form-control" id="bomMedidaUnidad">
                        <option value="mm">mm</option>
                        <option value="cm" selected>cm</option>
                        <option value="m">metros</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Detalle</label>
                    <input type="text" class="form-control" id="bomPieza" placeholder="Ej: Eje central">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalBom')">Cancelar</button>
            <button class="btn btn-primary" onclick="agregarBom()"><i class="fa fa-save"></i> Agregar</button>
        </div>
    </div>
</div>

<!-- Modal Cargar Plantilla BOM -->
<div class="modal-overlay" id="modalPlantilla">
    <div class="modal" style="max-width:480px">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-file-import"></i> Cargar desde Plantilla</span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Plantilla *</label>
                <select class="form-control" id="plantillaId" onchange="mostrarDescPlantilla()">
                    <option value="">— Cargando… —</option>
                </select>
                <div id="plantillaDesc" style="font-size:.8rem;color:var(--text-muted);margin-top:5px;min-height:18px"></div>
            </div>
            <div class="form-group">
                <label class="form-label">Cantidad de máquinas *</label>
                <input type="number" class="form-control" id="plantillaCantidad" value="1" min="1" step="1">
                <div style="font-size:.78rem;color:var(--text-muted);margin-top:4px">
                    Las cantidades de la lista se multiplicarán por este número.
                </div>
            </div>
            <div id="plantillaAlerta" style="display:none;margin-top:10px;padding:10px 12px;background:#fffbeb;border:1px solid #fcd34d;border-radius:6px;font-size:.82rem;color:#92400e">
                <i class="fa fa-triangle-exclamation"></i>
                La lista ya tiene materiales. Los nuevos se <strong>agregarán</strong> a los existentes.
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalPlantilla')">Cancelar</button>
            <button class="btn btn-primary" onclick="cargarPlantilla()">
                <i class="fa fa-file-import"></i> Cargar Plantilla
            </button>
        </div>
    </div>
</div>

<!-- Modal Costo Adicional -->
<div class="modal-overlay" id="modalCosto">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">Registrar Costo Adicional</span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Tipo</label>
                    <select class="form-control" id="costoTipo">
                        <option value="mano_obra">Mano de obra</option>
                        <option value="material">Material</option>
                        <option value="overhead">Overhead</option>
                        <option value="otros">Otros</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Fecha</label>
                    <input type="date" class="form-control" id="costoFecha" value="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Descripción *</label>
                <input type="text" class="form-control" id="costoDesc">
            </div>
            <div class="form-group">
                <label class="form-label">Monto (S/) *</label>
                <input type="number" class="form-control" id="costoMonto" min="0" step="0.01">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalCosto')">Cancelar</button>
            <button class="btn btn-primary" onclick="agregarCosto()"><i class="fa fa-save"></i> Guardar</button>
        </div>
    </div>
</div>

<!-- Modal Avance -->
<div class="modal-overlay" id="modalAvance">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">Registrar Avance</span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Área</label>
                    <select class="form-control" id="avanceArea">
                        <option value="">— Sin área —</option>
                        <?php foreach ($areas as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= esc($a['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">% Avance del Proyecto</label>
                    <input type="number" class="form-control" id="avancePct" min="0" max="100"
                           value="<?= $p['porcentaje_avance'] ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Descripción del avance *</label>
                <textarea class="form-control" id="avanceDesc" rows="3"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalAvance')">Cancelar</button>
            <button class="btn btn-primary" onclick="registrarAvance()"><i class="fa fa-save"></i> Registrar</button>
        </div>
    </div>
</div>

<!-- Modal Cambiar Estado -->
<div class="modal-overlay" id="modalCambiarEstado">
    <div class="modal" style="max-width:400px">
        <div class="modal-header">
            <span class="modal-title">Cambiar Estado del Proyecto</span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body">
            <p class="text-sm text-secondary mb-2">Estado actual: <span class="badge badge-<?= $p['estado'] ?>"><?= estadoLabel($p['estado']) ?></span></p>
            <div class="form-group">
                <label class="form-label">Nuevo Estado</label>
                <select class="form-control" id="nuevoEstado">
                    <option value="fabricacion">Fabricación</option>
                    <option value="pruebas">Pruebas</option>
                    <option value="entrega">Entrega</option>
                    <option value="completado">Completado</option>
                    <option value="cancelado">Cancelado</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalCambiarEstado')">Cancelar</button>
            <button class="btn btn-primary" onclick="cambiarEstado()"><i class="fa fa-check"></i> Confirmar</button>
        </div>
    </div>
</div>

<!-- ======================================================
     MODAL: AJUSTES DE WORKFLOW — Lista de áreas
     ====================================================== -->
<div class="modal-overlay" id="modalAjustesWf">
    <div class="modal" style="max-width:620px">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-sliders"></i> Ajustes de Workflow — Áreas</span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body" style="padding:0">
            <div style="padding:10px 16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
                <span style="font-size:.8rem;color:var(--text-muted)">Las áreas activas se asignan automáticamente a cada nuevo proyecto.</span>
                <button class="btn btn-primary btn-sm" onclick="abrirFormWfArea()">
                    <i class="fa fa-plus"></i> Nueva Área
                </button>
            </div>
            <div id="wfAjustesBody" style="min-height:80px"></div>
        </div>
    </div>
</div>

<!-- MODAL: AJUSTES DE WORKFLOW — Formulario crear/editar -->
<div class="modal-overlay" id="modalWfAreaForm">
    <div class="modal" style="max-width:460px">
        <div class="modal-header">
            <span class="modal-title" id="wfAreaFormTitulo"><i class="fa fa-sitemap"></i> Nueva Área</span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="wfAreaFormId">
            <div class="form-row cols-2">
                <div class="form-group" style="grid-column:span 2">
                    <label class="form-label">Nombre del área *</label>
                    <input type="text" class="form-control" id="wfAreaFormNombre" placeholder="Ej: Mecanizado">
                </div>
                <div class="form-group" style="grid-column:span 2">
                    <label class="form-label">Descripción</label>
                    <input type="text" class="form-control" id="wfAreaFormDesc" placeholder="Breve descripción de las tareas">
                </div>
                <div class="form-group">
                    <label class="form-label">Orden en workflow</label>
                    <input type="number" class="form-control" id="wfAreaFormOrden" min="1" max="99" value="99">
                    <div style="font-size:.73rem;color:var(--text-muted);margin-top:3px">Menor número = aparece primero</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Tipo</label>
                    <select class="form-control" id="wfAreaFormExterna">
                        <option value="0">Interno (cuenta en avance)</option>
                        <option value="1">Externo (servicio tercerizado)</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalWfAreaForm');Modal.open('modalAjustesWf');cargarAjustesWf()">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarWfArea()"><i class="fa fa-save"></i> Guardar</button>
        </div>
    </div>
</div>

<style>
/* Celdas editables estilo Excel */
.bom-cel { position:relative; }
.bom-cel:not(.editing)::after {
    content:'';
    position:absolute;
    top:2px; right:3px;
    font-size:.55rem;
    opacity:0;
    transition:opacity .15s;
    color:var(--primary);
    pointer-events:none;
}
.bom-cel:not(.editing):hover::after { opacity:.5; }
#tablaBom thead th { font-size:.75rem; }
#tablaBom td { font-size:.83rem; padding:6px 8px; }
</style>

<?php
function estadoLabel($e) {
    $map = ['fabricacion'=>'Fabricación','pruebas'=>'Pruebas','entrega'=>'Entrega','completado'=>'Completado','cancelado'=>'Cancelado'];
    return $map[$e] ?? $e;
}
function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES); }
?>

<style>
/* BOM — Piezas */
.pieza-card { margin-bottom: 14px; border: 2px solid #cbd5e1; }
.pieza-card .card-header { background: #f8fafc; border-bottom: 1px solid var(--border); }
.tabla-pieza { border-collapse: collapse; width: 100%; }
.tabla-pieza thead th { border-bottom: 1px solid var(--border); background: #f8fafc; font-size:.75rem; }
.tabla-pieza td { font-size:.83rem; padding:6px 8px; border-bottom:1px solid #f1f5f9; }
.tabla-pieza tbody tr[data-bom-id]:hover > td { background: #f7faff; }
.tabla-pieza .bom-cel {
    border-top:    1px solid transparent;
    border-right:  1px solid transparent;
    border-left:   1px solid transparent;
}
.tabla-pieza .bom-cel[tabindex]:focus { outline: none; }

/* Cuadro de especificaciones */
.bom-specs {
    display: flex;
    flex-wrap: wrap;
    gap: 0;
    border: 1px solid var(--border);
    border-radius: 0 0 6px 6px;
    background: #fff;
    font-size: .875rem;
}
.bom-specs-item {
    flex: 1;
    min-width: 115px;
    padding: 8px 14px;
    border-right: 1px solid var(--border);
}
.bom-specs-item--medida    { width: 90px;  flex: none; text-align: center; }
.bom-specs-item--cantidad  { width: 110px; flex: none; text-align: center; }
.bom-specs-item--subtotal  { width: 120px; flex: none; border-right: none; text-align: center; }
.bom-specs-label {
    color: var(--text-muted);
    margin-bottom: 3px;
    text-transform: uppercase;
    font-size: .77rem;
    letter-spacing: .04em;
}
.bom-specs-value { font-weight: 700; color: var(--text); }

/* Partes dentro de pieza */
.parte-section { border-top: 1px solid var(--border); background: #faf5ff; }
.parte-section .bom-specs { border-radius: 0; }
.parte-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 7px 14px;
    background: #f8fafc;
    font-size: .86rem;
    font-weight: 600;
    color: #6d28d9;
}

/* Dropdown búsqueda de producto */
#bomProductoDrop {
    display: none;
    position: absolute;
    z-index: 1000;
    left: 0; right: 0; top: 100%;
    background: #fff;
    border: 1px solid var(--border);
    border-top: none;
    border-radius: 0 0 6px 6px;
    max-height: 220px;
    overflow-y: auto;
    box-shadow: 0 4px 12px rgba(0,0,0,.12);
}
.bom-prod-item {
    padding: 8px 12px;
    cursor: pointer;
    font-size: .83rem;
    border-bottom: 1px solid #f1f5f9;
}
.bom-prod-item:hover { background: #eff6ff; }
.bom-prod-item:last-child { border-bottom: none; }
</style>

<script>
const PID  = <?= $id ?>;
const BASE = 'api.php';
const PRODUCTOS_BOM = <?= json_encode(array_values($productos)) ?>;

// ── Cargar detalle ────────────────────────────────────────
let _areasAll = [];

async function cargarDetalle() {
    try {
        const d = await apiGet(`${BASE}?action=detalle&id=${PID}`);
        _areasAll = d.areas || [];
        renderBom(d.piezas || [], d.bom || [], d.partes || []);
        renderAsignarAreas(d.piezas || [], d.partes || [], d.bom || []);
        renderCostos(d.costos, d.proyecto);
        renderAvances(d.avances);
        renderAlertas(d.proyecto);
        cargarPiezasProducidas();
    } catch(e) {
        Toast.error('Error al cargar datos: ' + (e.message || 'Error de red.'));
        const cont = document.getElementById('bomContainer');
        if (cont) cont.innerHTML = `<div class="empty-state">
            <div class="icon"><i class="fa fa-triangle-exclamation" style="color:var(--danger)"></i></div>
            <p style="color:var(--danger)">Error al cargar materiales. Recarga la página.</p>
        </div>`;
    }
}

// ── PIEZAS PRODUCIDAS ─────────────────────────────────────
async function cargarPiezasProducidas() {
    try {
        const d = await apiGet(`${BASE}?action=piezas_producidas&proyecto_id=${PID}`);
        renderPiezasProducidas(d.piezas || []);
    } catch(e) { /* silencioso si la pestaña no se ha mirado aún */ }
}

function badgeEstadoPieza(estado) {
    const map = {
        en_proceso: { txt: 'En proceso',  bg: '#dbeafe', color: '#1e40af', icon: 'fa-arrows-rotate' },
        disponible: { txt: 'Disponible',  bg: '#dcfce7', color: '#166534', icon: 'fa-circle-check' },
        consumida:  { txt: 'Consumida',   bg: '#f1f5f9', color: '#64748b', icon: 'fa-link' },
        integrada:  { txt: 'Integrada',   bg: '#e0e7ff', color: '#3730a3', icon: 'fa-puzzle-piece' },
        stock:      { txt: 'En stock',    bg: '#fce7f3', color: '#9d174d', icon: 'fa-warehouse' },
    };
    const s = map[estado] || { txt: estado, bg: '#f1f5f9', color: '#64748b', icon: 'fa-circle' };
    return `<span style="display:inline-block;padding:3px 10px;border-radius:10px;
            background:${s.bg};color:${s.color};font-size:.72rem;font-weight:600">
            <i class="fa ${s.icon}"></i> ${s.txt}</span>`;
}

function renderPiezasProducidas(piezas) {
    const cont = document.getElementById('piezasContainer');
    if (!cont) return;
    document.getElementById('piezasResumen').textContent = `${piezas.length} pieza${piezas.length === 1 ? '' : 's'}`;

    if (!piezas.length) {
        cont.innerHTML = `<div class="empty-state" style="padding:30px 20px;text-align:center;color:var(--text-muted)">
            <p>Aún no se han generado piezas. Marca partes como completadas en la <strong>Bandeja del área</strong> para producirlas.</p>
        </div>`;
        return;
    }

    cont.innerHTML = piezas.map(pz => {
        const acciones = [];
        const nombreEsc = esc(pz.nombre).replace(/'/g, "\\'");

        if (pz.estado === 'disponible') {
            acciones.push(`<button class="btn btn-outline btn-sm" onclick="abrirEnviarArea(${pz.id}, '${nombreEsc}')">
                <i class="fa fa-paper-plane"></i> Enviar a área
            </button>`);
            acciones.push(`<button class="btn btn-primary btn-sm" onclick="abrirFinalizarPieza(${pz.id}, '${nombreEsc}')">
                <i class="fa fa-flag-checkered"></i> Finalizar
            </button>`);
        } else if (pz.estado === 'en_proceso') {
            acciones.push(`<span style="font-size:.8rem;color:var(--text-muted)">
                <i class="fa fa-location-dot"></i> ${esc(pz.area_actual_nombre || '—')}
                ${pz.area_actual_externa ? '<i class="fa fa-globe" style="color:#f59e0b;margin-left:3px" title="Externa"></i>' : ''}
            </span>`);
            acciones.push(`<button class="btn btn-outline btn-xs" onclick="cancelarEnvioPiezaProyecto(${pz.id})" title="Devolver a disponible">
                <i class="fa fa-undo"></i>
            </button>`);
        }

        const ult = pz.ultima_transformacion
            ? `<span style="font-size:.72rem;color:var(--text-muted);margin-left:10px">
                 Última operación: ${formatDate(pz.ultima_transformacion.split(/[ T]/)[0])}
               </span>` : '';

        return `
            <div style="display:flex;justify-content:space-between;align-items:center;
                        gap:14px;padding:12px 14px;border:1px solid var(--border);
                        border-radius:8px;margin-bottom:8px;background:#fff">
                <div>
                    <div style="font-weight:600;font-size:.92rem">
                        <i class="fa fa-cube" style="color:var(--primary);opacity:.75;margin-right:6px"></i>${esc(pz.nombre)}
                    </div>
                    <div style="margin-top:4px">
                        ${badgeEstadoPieza(pz.estado)}${ult}
                    </div>
                </div>
                <div style="display:flex;gap:6px;align-items:center">
                    ${acciones.join('')}
                </div>
            </div>`;
    }).join('');
}

// ── ENVIAR A ÁREA ──
function abrirEnviarArea(piezaId, nombre) {
    document.getElementById('enviarPiezaId').value = piezaId;
    document.getElementById('enviarPiezaInfo').innerHTML = `Pieza: <strong>${esc(nombre)}</strong>`;
    const sel = document.getElementById('enviarAreaSel');
    sel.innerHTML = '<option value="">— Seleccionar área —</option>' +
        _areasAll.map(a => {
            const ext = a.es_externa ? ' 🌐' : '';
            return `<option value="${a.id}">${esc(a.nombre)}${ext}</option>`;
        }).join('');
    Modal.open('modalEnviarArea');
}

async function confirmarEnviarArea() {
    const id     = document.getElementById('enviarPiezaId').value;
    const areaId = document.getElementById('enviarAreaSel').value;
    if (!areaId) { Toast.warning('Selecciona un área.'); return; }
    try {
        const r = await apiPost(BASE, { action: 'pieza_enviar_area', id, area_id: areaId });
        if (r.ok) { Toast.success('Pieza enviada al área.'); Modal.close('modalEnviarArea'); cargarPiezasProducidas(); }
        else      { Toast.error(r.error || 'Error.'); }
    } catch(e) { Toast.error(e.message || 'Error de red.'); }
}

async function cancelarEnvioPiezaProyecto(id) {
    if (!confirm('¿Devolver esta pieza a estado disponible (sin procesarla)?')) return;
    try {
        const r = await apiPost(BASE, { action: 'pieza_cancelar_envio', id });
        if (r.ok) { Toast.success('Pieza devuelta.'); cargarPiezasProducidas(); }
        else      { Toast.error(r.error || 'Error.'); }
    } catch(e) { Toast.error(e.message || 'Error de red.'); }
}

// ── FINALIZAR ──
function abrirFinalizarPieza(piezaId, nombre) {
    document.getElementById('finalizarPiezaId').value = piezaId;
    document.getElementById('finalizarPiezaInfo').innerHTML = `Pieza: <strong>${esc(nombre)}</strong>`;
    document.querySelectorAll('input[name="finDestino"]').forEach(r => r.checked = (r.value === 'proyecto'));
    document.getElementById('finProductoNombre').value = nombre;
    document.getElementById('finCantidad').value = '1';
    document.getElementById('finCostoUnitario').value = '0';
    document.getElementById('finObs').value = '';
    toggleStockFields(false);
    Modal.open('modalFinalizar');
}

function toggleStockFields(show) {
    document.getElementById('finStockFields').style.display = show ? '' : 'none';
}

async function confirmarFinalizar() {
    const id      = document.getElementById('finalizarPiezaId').value;
    const destino = document.querySelector('input[name="finDestino"]:checked')?.value;
    if (!destino) { Toast.warning('Selecciona un destino.'); return; }

    const payload = { action: 'pieza_finalizar', id, destino };

    if (destino === 'stock') {
        payload.nuevo_producto_nombre = document.getElementById('finProductoNombre').value.trim();
        payload.cantidad              = document.getElementById('finCantidad').value;
        payload.costo_unitario        = document.getElementById('finCostoUnitario').value;
        payload.observaciones         = document.getElementById('finObs').value;
        if (!payload.nuevo_producto_nombre) { Toast.warning('Indica el nombre del producto.'); return; }
    }

    try {
        const r = await apiPost(BASE, payload);
        if (r.ok) {
            Toast.success(destino === 'stock' ? 'Pieza pasada a stock.' : 'Pieza integrada al proyecto.');
            Modal.close('modalFinalizar');
            cargarPiezasProducidas();
        } else { Toast.error(r.error || 'Error.'); }
    } catch(e) { Toast.error(e.message || 'Error de red.'); }
}

// ── BOM — Área de trabajo por piezas ─────────────────────
let _bomTieneItems  = false;
let _bomReloadTimer = null;
let _bomFocused     = null;
let _bomEditing     = null;
let _partesAll      = [];
let _piezasAll      = [];

function renderBom(piezas, bom, partes) {
    _bomTieneItems = bom.length > 0;
    _partesAll     = partes || [];
    _piezasAll     = piezas || [];
    _bomFocused = _bomEditing = null;
    const cont = document.getElementById('bomContainer');

    if (!piezas.length) {
        cont.innerHTML = `<div class="empty-state">
            <div class="icon"><i class="fa fa-box-open"></i></div>
            <p>No hay piezas. Use <strong>Crear Pieza</strong> para comenzar.</p>
        </div>`;
        return;
    }

    const THEAD = `<thead><tr>
        <th style="width:28px;text-align:center;color:var(--text-muted);font-weight:400;font-size:.72rem;padding:6px 4px">#</th>
        <th style="width:80px">Código</th>
        <th>Material</th>
        <th style="width:110px">Detalle</th>
        <th style="width:70px;text-align:center">Unidad</th>
        <th style="width:90px;text-align:center">Medida</th>
        <th style="width:110px;text-align:center">Cant. <span style="font-weight:400;font-size:.7rem;color:var(--text-muted)">(plan/real)</span></th>
        <th style="width:120px;text-align:center">Total Est.</th>
        <th style="width:36px"></th>
    </tr></thead>`;

    function renderFila(r, rowNum) {
        const nombre = r.prod_nombre || r.descripcion_libre || '';
        const codigo = r.prod_codigo || '—';
        const cantP     = parseInt(r.cantidad_planificada, 10)   || 0;
        const cuEst     = parseFloat(r.costo_unitario_estimado) || 0;
        const cantR     = parseFloat(r.cantidad_real)           || 0;
        const cuReal    = parseFloat(r.costo_unitario_real)     || 0;
        const medida    = Math.round(parseFloat(r.medida) || 0);
        const precioVta = parseFloat(r.precio_venta)            || 0;
        const unidad    = r.medida_unidad || r.unidad || '';
        const pieza     = _piezasAll.find(p => String(p.id) === String(r.pieza_id));
        const largoMm   = parseFloat(pieza?.propiedades_tecnicas?.Largo) || 0;
        const largoCm   = largoMm / 10;
        const tEst      = largoCm > 0 ? (medida / largoCm) * precioVta : 0;
        const tReal     = cantR > 0 ? cantR * (cuReal > 0 ? cuReal : cuEst) : 0;

        const numC = (campo, val) =>
            `<td class="bom-cel" data-id="${r.id}" data-campo="${campo}"
                 data-val="${val}" data-tipo="num"
                 style="text-align:center;padding:5px 8px;
                        user-select:none;white-space:nowrap">
                ${Math.round(val)}</td>`;

        const txtC = (campo, val, extra) =>
            `<td class="bom-cel" data-id="${r.id}" data-campo="${campo}"
                 data-val="${esc(val)}" data-tipo="txt"
                 style="padding:5px 8px;
                        user-select:none;${extra||''}">
                ${val ? esc(val) : '<span style="color:var(--text-muted)">—</span>'}</td>`;

        // Inline real-vs-plan indicator shown below the planned quantity
        let realIndicator = '';
        if (cantR > 0) {
            const diff = cantR - cantP;
            const pct  = cantP > 0 ? (diff / cantP * 100) : 100;
            const clr  = diff > cantP * 0.05 ? '#dc2626' : diff < 0 ? '#16a34a' : '#059669';
            const sign = diff > 0 ? '+' : '';
            const diffTxt = Math.abs(diff) > 0.01
                ? ` <span style="font-weight:400">(${sign}${Math.abs(pct) < 1 ? pct.toFixed(1) : Math.round(pct)}%)</span>`
                : ' <span style="opacity:.7">✓</span>';
            const cantRFmt = cantR % 1 === 0 ? cantR : cantR.toFixed(2);
            realIndicator = `<div style="font-size:.68rem;color:${clr};font-weight:600;margin-top:2px;line-height:1.2">
                Real: ${cantRFmt}${diffTxt}</div>`;
        }

        const cantCell = `<td class="bom-cel" data-id="${r.id}" data-campo="cantidad_planificada"
             data-val="${cantP}" data-tipo="num"
             style="text-align:center;padding:5px 8px;user-select:none;white-space:nowrap">
            ${Math.round(cantP)}${realIndicator}
        </td>`;

        // Total cell — show real total below estimated when available
        let totalContent = formatMoney(tEst);
        if (tReal > 0) {
            const devClr = tReal > tEst * 1.05 ? '#dc2626' : tReal < tEst * 0.95 ? '#16a34a' : '#059669';
            totalContent += `<div style="font-size:.68rem;color:${devClr};font-weight:600;margin-top:2px">Real: ${formatMoney(tReal)}</div>`;
        }

        return {
            html: `<tr data-bom-id="${r.id}" data-costo="${cuEst}">
                <td style="text-align:center;font-size:.71rem;color:var(--text-muted);padding:5px 4px;
                           user-select:none;border-right:1px solid #f1f5f9">${rowNum}</td>
                <td style="font-size:.78rem;color:var(--text-muted);padding:5px 8px">${esc(codigo)}</td>
                <td style="padding:5px 8px;min-width:140px">${esc(nombre)}${r.operacion
                        ? `<div style="font-size:.68rem;color:var(--text-muted)"><i class="fa fa-wrench"
                           style="opacity:.5;margin-right:3px"></i>${esc(r.operacion)}</div>` : ''}</td>
                ${txtC('pieza',  r.pieza  || '', 'width:110px')}
                ${txtC('unidad', unidad, 'text-align:center;width:70px')}
                ${txtC('medida', r.medida || '', 'text-align:center;width:90px')}
                ${cantCell}
                <td class="bom-total" style="padding:5px 8px;font-weight:600;text-align:center">${totalContent}</td>
                <td style="text-align:center;padding:3px 4px">
                    <button class="btn btn-outline btn-xs" style="color:var(--danger)"
                            onclick="eliminarBom(${r.id})" title="Eliminar">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>`,
            tEst,
            tReal
        };
    }

    // ── Cuadro de especificaciones (independiente de la tabla) ──
    function buildSpecs(items, subtotal, subtotalReal) {
        if (!items.length) return '';
        let totalMedida = 0, medidaCount = 0, totalCantidad = 0, totalCantidadReal = 0;
        items.forEach(r => {
            const m = Math.round(parseFloat(r.medida) || 0);
            if (m > 0) { totalMedida += m; medidaCount++; }
            totalCantidad += parseInt(r.cantidad_planificada, 10) || 0;
            totalCantidadReal += parseFloat(r.cantidad_real) || 0;
        });

        // Largo desde propiedades_tecnicas de la pieza (guardado en mm → convertir a cm)
        const piezaId = items[0]?.pieza_id;
        const pieza   = _piezasAll.find(p => String(p.id) === String(piezaId));
        const largoMm = parseFloat(pieza?.propiedades_tecnicas?.Largo) || 0;
        const largoCm = largoMm / 10;
        const cantMat = largoCm > 0 && totalMedida > 0
            ? (totalMedida / largoCm)
            : null;
        const cantMatStr = cantMat !== null
            ? (cantMat % 1 === 0 ? cantMat : cantMat.toFixed(2))
            : '—';

        const medStr = medidaCount > 0 ? totalMedida : '—';
        const hasReal = subtotalReal > 0;

        let cantidadCell = `<div class="bom-specs-label">Cantidad total</div>
            <div class="bom-specs-value">${totalCantidad}</div>`;
        if (hasReal && totalCantidadReal !== totalCantidad) {
            const cntDiff = totalCantidadReal - totalCantidad;
            const cntClr  = cntDiff > 0 ? '#dc2626' : '#16a34a';
            const cntSign = cntDiff > 0 ? '+' : '';
            cantidadCell += `<div style="font-size:.7rem;color:${cntClr};font-weight:600;margin-top:2px">
                Real: ${totalCantidadReal % 1 === 0 ? totalCantidadReal : totalCantidadReal.toFixed(2)} (${cntSign}${cntDiff % 1 === 0 ? cntDiff : cntDiff.toFixed(2)})</div>`;
        }

        let subtotalCell = `<div class="bom-specs-label">Subtotal estimado</div>
            <div class="bom-specs-value" style="font-size:.85rem">${formatMoney(subtotal)}</div>`;
        if (hasReal) {
            const dev    = subtotal > 0 ? ((subtotalReal - subtotal) / subtotal * 100) : 0;
            const devClr = subtotalReal > subtotal * 1.05 ? '#dc2626' : subtotalReal < subtotal * 0.95 ? '#16a34a' : '#059669';
            const devSign = dev > 0 ? '+' : '';
            subtotalCell += `<div style="font-size:.7rem;color:${devClr};font-weight:600;margin-top:4px">
                Real: ${formatMoney(subtotalReal)}${subtotal > 0 ? ` (${devSign}${dev.toFixed(1)}%)` : ''}</div>`;
        }

        return `<div class="bom-specs">
            <div class="bom-specs-item" style="display:flex;align-items:center;gap:6px">
                <span class="bom-specs-label" style="margin-bottom:0;white-space:nowrap;font-size:.92rem">Cantidad de material:</span>
                <span class="bom-specs-value" style="color:${cantMat !== null ? '#166534' : 'var(--text-muted)'};font-size:1.05rem">${cantMatStr}</span>
            </div>
            <div class="bom-specs-item bom-specs-item--medida">
                <div class="bom-specs-label">Medida total</div>
                <div class="bom-specs-value" style="color:${medidaCount > 0 ? '#166534' : 'var(--text-muted)'}">${medStr}</div>
            </div>
            <div class="bom-specs-item bom-specs-item--cantidad">
                ${cantidadCell}
            </div>
            <div class="bom-specs-item bom-specs-item--subtotal">
                ${subtotalCell}
            </div>
            <div style="width:36px;flex-shrink:0"></div>
        </div>`;
    }

    // ── Tabla de ítems BOM ───────────────────────────────────
    function renderTabla(items, tbodyId, showEmptyMsg, showHeader = false) {
        let subtotal = 0, subtotalReal = 0, rowNum = 0;
        const filas = items.map(r => {
            rowNum++;
            const { html, tEst, tReal } = renderFila(r, rowNum);
            subtotal += tEst;
            subtotalReal += tReal || 0;
            return html;
        }).join('');
        const emptyRow = showEmptyMsg
            ? `<tr><td colspan="9" class="text-center text-muted" style="padding:14px;font-size:.82rem">Sin materiales</td></tr>`
            : '';
        return {
            html: `<div class="table-responsive">
                <table class="tabla-pieza" style="border-collapse:collapse;width:100%">
                    ${showHeader ? THEAD : ''}
                    <tbody id="${tbodyId}">${filas || emptyRow}</tbody>
                </table>
            </div>${buildSpecs(items, subtotal, subtotalReal)}`,
            subtotal
        };
    }

    let totalGeneral = 0;
    const bloques = [];

    piezas.forEach((pieza, piezaIdx) => {
        const piezaNum        = piezaIdx + 1;
        const piezaPartes     = (partes || []).filter(p => String(p.pieza_id) === String(pieza.id));
        const standaloneItems = bom.filter(r => String(r.pieza_id) === String(pieza.id) && !r.parte_id);
        let totPieza = 0;

        const { html: mainTableHtml, subtotal: mainSub } = renderTabla(
            standaloneItems, `bomBody-${pieza.id}`, piezaPartes.length === 0
        );
        totPieza += mainSub;

        const partesHtml = piezaPartes.map((parte, parteIdx) => {
            const parteNum   = `${piezaNum}.${parteIdx + 1}`;
            const parteItems = bom.filter(r => String(r.parte_id) === String(parte.id));
            const { html: parteTableHtml, subtotal: parteSub } = renderTabla(
                parteItems, `bomBody-parte-${parte.id}`, true, true
            );
            totPieza += parteSub;
            return `<div class="parte-section">
                <div class="parte-header">
                    <span><i class="fa fa-puzzle-piece" style="margin-right:6px"></i>Parte ${parteNum} — ${esc(parte.nombre)}</span>
                    <div style="display:flex;gap:6px;align-items:center">
                        <button class="btn btn-outline btn-xs"
                                onclick="renombrarParte(${parte.id},'${esc(parte.nombre)}')" title="Renombrar">
                            <i class="fa fa-pencil"></i>
                        </button>
                        <button class="btn btn-outline btn-xs" style="color:var(--danger)"
                                onclick="eliminarParte(${parte.id},'${esc(parte.nombre)}')" title="Eliminar">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>
                ${parteTableHtml}
            </div>`;
        }).join('');

        totalGeneral += totPieza;

        bloques.push(`
        <div class="card pieza-card" id="pieza-card-${pieza.id}">
            <div class="card-header">
                <span class="card-title" style="display:flex;align-items:center;gap:8px;font-size:.9rem">
                    <i class="fa fa-cube" style="color:var(--primary);opacity:.75"></i>
                    <span>Pieza ${piezaNum} — ${esc(pieza.nombre)}</span>
                </span>
                <div class="card-actions" style="gap:6px">
                    <span style="font-size:.8rem;color:var(--text-muted);margin-right:4px">${formatMoney(totPieza)}</span>
                    <button class="btn btn-outline btn-xs"
                            onclick="renombrarPieza(${pieza.id},'${esc(pieza.nombre)}')"
                            title="Renombrar pieza"><i class="fa fa-pencil"></i></button>
                    <button class="btn btn-outline btn-xs" style="color:var(--danger)"
                            onclick="eliminarPieza(${pieza.id},'${esc(pieza.nombre)}')"
                            title="Eliminar pieza"><i class="fa fa-trash"></i></button>
                </div>
            </div>
            ${mainTableHtml}
            ${partesHtml}
            <div style="padding:8px 12px;border-top:1px solid var(--border);display:flex;gap:8px">
                <button class="btn btn-sm" style="background:#86efac;border-color:#86efac;color:#166534"
                        onclick="agregarMaterialAPieza(${pieza.id},'${esc(pieza.nombre)}')">
                    <i class="fa fa-plus"></i> Agregar Material
                </button>
                <button class="btn btn-outline btn-sm"
                        onclick="abrirModalCrearParte(${pieza.id})">
                    <i class="fa fa-puzzle-piece"></i> Agregar Parte
                </button>
            </div>
        </div>`);
    });

    if (piezas.length > 1 || bom.length > 0) {
        bloques.push(`<div style="text-align:right;padding:8px 14px 2px;font-weight:700;
            font-size:.88rem;color:var(--text-secondary)">
            TOTAL GENERAL: <span style="color:var(--primary);font-size:.95rem">${formatMoney(totalGeneral)}</span>
        </div>`);
    }

    cont.innerHTML = bloques.join('');
    cont.querySelectorAll('.bom-cel').forEach(td => bomBindCell(td));
}

// ── ASIGNAR ÁREAS ─────────────────────────────────────────
// Sugiere área a partir del campo `operacion` del BOM (palabras clave).
function sugerirAreaId(textoOperacion) {
    if (!textoOperacion) return null;
    const t = textoOperacion.toLowerCase();
    const reglas = [
        { keys: ['láser','laser'],                      area: 'corte láser' },
        { keys: ['torno'],                              area: 'torno' },
        { keys: ['broca','taladr','macho','rosca'],     area: 'taladro' },
        { keys: ['tronzadora','amoladora','cortar']  ,  area: 'corte' },
        { keys: ['rolar','rolad','curvad']           ,  area: 'rolado' },
        { keys: ['soldar','soldad','tapar','plancha'],  area: 'soldadura' },
        { keys: ['pintar','pintura'],                   area: 'pintura' },
    ];
    for (const r of reglas) {
        if (r.keys.some(k => t.includes(k))) {
            const found = _areasAll.find(a => a.nombre.toLowerCase() === r.area);
            if (found) return found.id;
        }
    }
    return null;
}

function badgeEstadoParte(estado) {
    const map = {
        sin_asignar: { txt: 'Sin asignar', bg: '#f1f5f9', color: '#64748b' },
        pendiente:   { txt: 'Pendiente',   bg: '#fef3c7', color: '#92400e' },
        en_proceso:  { txt: 'En proceso',  bg: '#dbeafe', color: '#1e40af' },
        completada:  { txt: 'Completada',  bg: '#dcfce7', color: '#166534' },
        integrada:   { txt: 'Integrada',   bg: '#e0e7ff', color: '#3730a3' },
        stock:       { txt: 'En stock',    bg: '#fce7f3', color: '#9d174d' },
    };
    const s = map[estado] || map.sin_asignar;
    return `<span style="display:inline-block;padding:2px 8px;border-radius:10px;
            background:${s.bg};color:${s.color};font-size:.7rem;font-weight:600">${s.txt}</span>`;
}

function renderAsignarAreas(piezas, partes, bom) {
    const cont = document.getElementById('asignarContainer');
    if (!cont) return;

    if (!partes.length) {
        cont.innerHTML = `<div class="empty-state" style="padding:30px 20px;text-align:center;color:var(--text-muted)">
            <p>No hay partes definidas. Ve a <strong>Lista de Materiales</strong> para crear partes dentro de cada pieza.</p>
        </div>`;
        document.getElementById('asignarResumen').textContent = '';
        return;
    }

    // Resumen
    const total      = partes.length;
    const asignadas  = partes.filter(p => p.area_id).length;
    document.getElementById('asignarResumen').textContent =
        `${asignadas} de ${total} partes con área asignada`;

    const editable = e => ['sin_asignar','pendiente'].includes(e || 'sin_asignar');

    // Render por pieza
    const html = piezas.map((pieza, piezaIdx) => {
        const piezaNum    = piezaIdx + 1;
        const piezaPartes = partes.filter(p => String(p.pieza_id) === String(pieza.id));
        if (!piezaPartes.length) return '';

        const filas = piezaPartes.map((parte, parteIdx) => {
            const parteNum  = `${piezaNum}.${parteIdx + 1}`;
            const items     = bom.filter(r => String(r.parte_id) === String(parte.id));
            const operaciones = items.map(r => r.operacion).filter(Boolean).join(' · ');
            const operHint  = operaciones || '—';

            const sugerido = !parte.area_id ? sugerirAreaId(operaciones) : null;
            const valSel   = parte.area_id || sugerido || '';
            const esEdit   = editable(parte.estado_fabricacion);

            const opciones = ['<option value="">— Seleccionar área —</option>']
                .concat(_areasAll.map(a => {
                    const sel = String(a.id) === String(valSel) ? 'selected' : '';
                    const ext = a.es_externa ? ' 🌐' : '';
                    return `<option value="${a.id}" ${sel}>${esc(a.nombre)}${ext}</option>`;
                })).join('');

            const hintSugerido = (sugerido && !parte.area_id)
                ? `<div style="font-size:.7rem;color:#16a34a;margin-top:3px">
                     <i class="fa fa-lightbulb"></i> Sugerencia desde operación
                   </div>` : '';

            const numMat = items.length;
            return `
                <tr data-parte-id="${parte.id}">
                    <td style="padding:8px 12px;width:80px;color:var(--text-muted);font-size:.78rem">
                        Parte ${parteNum}
                    </td>
                    <td style="padding:8px 12px;font-weight:600">
                        ${esc(parte.nombre)}
                        <div style="font-size:.72rem;color:var(--text-muted);font-weight:400;margin-top:2px">
                            ${numMat} material${numMat === 1 ? '' : 'es'} · ${esc(operHint)}
                        </div>
                    </td>
                    <td style="padding:8px 12px;width:140px">
                        ${badgeEstadoParte(parte.estado_fabricacion)}
                    </td>
                    <td style="padding:8px 12px;width:240px">
                        <select class="form-control form-control-sm asignar-area-sel"
                                data-parte-id="${parte.id}"
                                ${esEdit ? '' : 'disabled'}
                                style="font-size:.83rem;padding:4px 8px">
                            ${opciones}
                        </select>
                        ${hintSugerido}
                    </td>
                    <td style="padding:8px 12px;width:80px;text-align:center">
                        ${parte.area_id && esEdit
                            ? `<button class="btn btn-outline btn-xs" style="color:var(--danger)"
                                       onclick="quitarAreaParte(${parte.id})" title="Quitar área">
                                  <i class="fa fa-times"></i>
                               </button>`
                            : ''}
                    </td>
                </tr>`;
        }).join('');

        return `
            <div style="padding:14px 18px 4px;border-top:1px solid var(--border)">
                <div style="font-weight:600;font-size:.92rem;margin-bottom:6px;display:flex;align-items:center;gap:8px">
                    <i class="fa fa-cube" style="color:var(--primary);opacity:.75"></i>
                    Pieza ${piezaNum} — ${esc(pieza.nombre)}
                </div>
            </div>
            <table style="width:100%;border-collapse:collapse">
                <tbody>${filas}</tbody>
            </table>`;
    }).join('');

    cont.innerHTML = html;

    // Wire up el cambio de select
    cont.querySelectorAll('.asignar-area-sel').forEach(sel => {
        sel.addEventListener('change', async e => {
            const parteId = e.target.dataset.parteId;
            const areaId  = e.target.value;
            if (!areaId) return;
            try {
                const r = await apiPost(BASE, { action: 'parte_asignar_area', id: parteId, area_id: areaId });
                if (r.ok) { Toast.success('Área asignada.'); cargarDetalle(); }
                else      { Toast.error(r.error || 'Error al asignar.'); }
            } catch(err) { Toast.error(err.message || 'Error de red.'); }
        });
    });
}

async function quitarAreaParte(parteId) {
    if (!confirm('¿Quitar la asignación de área de esta parte?')) return;
    try {
        const r = await apiPost(BASE, { action: 'parte_quitar_area', id: parteId });
        if (r.ok) { Toast.success('Área quitada.'); cargarDetalle(); }
        else      { Toast.error(r.error || 'Error al quitar.'); }
    } catch(err) { Toast.error(err.message || 'Error de red.'); }
}

// ── Bind de una celda ─────────────────────────────────────
function bomBindCell(td) {
    td.addEventListener('keydown', e  => bomCellKey(e, td));
}

// ── Selección (sin editar) ────────────────────────────────
function bomSelect(td) {
    if (_bomFocused && _bomFocused !== td) {
        _bomFocused.style.outline = '';
        _bomFocused.style.outlineOffset = '';
        if (_bomFocused !== _bomEditing) _bomFocused.style.background = '';
    }
    _bomFocused = td;
    td.style.outline = '2px solid #2563eb';
    td.style.outlineOffset = '-2px';
    td.style.background = '#eff6ff';
}

function bomClick(td) {
    if (_bomEditing === td) return;
    if (_bomEditing) {
        // blur del input activo disparará el guardado
        _bomEditing.querySelector('input')?.blur();
        setTimeout(() => { bomSelect(td); td.focus(); }, 30);
        return;
    }
    if (_bomFocused === td) {
        bomStartEdit(td);
    } else {
        bomSelect(td);
        td.focus();
    }
}

// ── Teclas en celda seleccionada (sin editar) ─────────────
function bomCellKey(e, td) {
    if (_bomEditing === td) return;
    const nav = { ArrowRight:'right', ArrowLeft:'left', ArrowDown:'down', ArrowUp:'up' };
    if (nav[e.key]) { e.preventDefault(); bomMove(td, nav[e.key]); return; }
    if (e.key === 'Enter' || e.key === 'F2') { e.preventDefault(); bomStartEdit(td); return; }
    if ((e.key === 'Delete' || e.key === 'Backspace') && !e.ctrlKey) {
        e.preventDefault();
        bomStartEdit(td, '');
        return;
    }
    // Cualquier caracter imprimible → activar edición con ese carácter
    if (e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
        e.preventDefault();
        bomStartEdit(td, e.key);
    }
}

// ── Edición inline ────────────────────────────────────────
function bomStartEdit(td, initChar) {
    if (_bomEditing === td) return;
    bomSelect(td);
    _bomEditing = td;

    const tipo    = td.dataset.tipo;
    const valOrig = td.dataset.val; // dataset decodifica entidades automáticamente

    td.style.padding  = '0';

    const inp     = document.createElement('input');
    inp.type      = tipo === 'num' ? 'number' : 'text';
    if (tipo === 'num') { inp.min = '0'; inp.step = '1'; }
    inp.value     = initChar !== undefined ? initChar : (tipo === 'num' ? (parseFloat(valOrig)||0) : valOrig);
    inp.style.cssText = `width:100%;height:100%;min-height:30px;padding:4px 7px;
        text-align:${tipo==='num'?'right':'left'};border:none;font-size:.85rem;
        font-family:inherit;background:#fff;outline:none;box-sizing:border-box`;

    td.innerHTML = '';
    td.appendChild(inp);
    inp.focus();
    if (initChar === undefined) { inp.select(); } else { inp.setSelectionRange(inp.value.length, inp.value.length); }

    let done = false;

    const onBlur = () => doSave(null);

    const doSave = async (dir) => {
        if (done) return;
        done = true;
        _bomEditing = null;
        inp.removeEventListener('blur', onBlur);

        let nuevo;
        if (tipo === 'num') {
            nuevo = parseFloat(inp.value);
            if (isNaN(nuevo) || nuevo < 0) { doCancel(); return; }
        } else {
            nuevo = inp.value.trim();
        }

        td.dataset.val   = nuevo;
        td.style.padding = '5px 8px';
        if (tipo === 'num') {
            td.textContent = Math.round(nuevo);
        } else {
            td.innerHTML = nuevo ? esc(nuevo) : '<span style="color:var(--text-muted)">—</span>';
        }
        bomSelect(td);
        bomRecalcularFila(td.closest('tr'));

        if (dir) bomMove(td, dir);

        try {
            if (tipo === 'num') {
                await apiPost(BASE, { action:'bom_actualizar', id:td.dataset.id,
                    campo:td.dataset.campo, valor:nuevo, proyecto_id:PID });
            } else {
                await apiPost(BASE, { action:'bom_actualizar_texto', id:td.dataset.id,
                    campo:td.dataset.campo, valor:nuevo });
            }
        } catch(e) { Toast.error('Error al guardar.'); }

        clearTimeout(_bomReloadTimer);
        _bomReloadTimer = setTimeout(async () => {
            if (_bomEditing) return;
            const d = await apiGet(`${BASE}?action=detalle&id=${PID}`);
            if (_bomEditing) return;
            renderBom(d.piezas || [], d.bom || [], d.partes || []);
        }, 1800);
    };

    const doCancel = () => {
        if (done) return;
        done = true;
        _bomEditing = null;
        inp.removeEventListener('blur', onBlur);
        td.style.padding = '5px 8px';
        if (tipo === 'num') {
            td.textContent = Math.round(parseFloat(valOrig) || 0);
        } else {
            td.innerHTML = valOrig ? esc(valOrig) : '<span style="color:var(--text-muted)">—</span>';
        }
        bomSelect(td);
    };

    inp.addEventListener('blur', onBlur);
    inp.addEventListener('keydown', e => {
        if (e.key === 'Escape')  { e.preventDefault(); doCancel(); return; }
        if (e.key === 'Enter')   { e.preventDefault(); doSave('down'); return; }
        if (e.key === 'Tab')     { e.preventDefault(); doSave(e.shiftKey ? 'left' : 'right'); return; }
        if (e.key === 'ArrowDown' && tipo === 'num') { e.preventDefault(); doSave('down'); return; }
        if (e.key === 'ArrowUp'   && tipo === 'num') { e.preventDefault(); doSave('up');   return; }
    });
}

// ── Navegación entre celdas (dentro de la misma pieza) ───
function bomMove(fromTd, dir) {
    const tr       = fromTd.closest('tr');
    const tbody    = fromTd.closest('tbody');
    const rowCels  = [...tr.querySelectorAll('.bom-cel')];
    const col      = rowCels.indexOf(fromTd);
    const allRows  = [...tbody.querySelectorAll('tr[data-bom-id]')];
    const rowIdx   = allRows.indexOf(tr);

    let target = null;
    if (dir === 'right') {
        target = rowCels[col + 1]
            ?? allRows[rowIdx + 1]?.querySelectorAll('.bom-cel')[0]
            ?? null;
    } else if (dir === 'left') {
        if (col > 0) {
            target = rowCels[col - 1];
        } else {
            const pc = allRows[rowIdx - 1]?.querySelectorAll('.bom-cel');
            target = pc ? pc[pc.length - 1] : null;
        }
    } else if (dir === 'down') {
        const nc = allRows[rowIdx + 1]?.querySelectorAll('.bom-cel');
        target = nc ? nc[Math.min(col, nc.length - 1)] : null;
    } else if (dir === 'up') {
        const pc = allRows[rowIdx - 1]?.querySelectorAll('.bom-cel');
        target = pc ? pc[Math.min(col, pc.length - 1)] : null;
    }

    if (target) { bomSelect(target); target.focus(); }
}

// ── Recalcular total de fila ──────────────────────────────
function bomRecalcularFila(tr) {
    const cant  = parseInt(tr.querySelector('.bom-cel[data-campo="cantidad_planificada"]')?.dataset.val, 10) || 0;
    const costo = parseFloat(tr.dataset.costo) || 0;
    const tot   = tr.querySelector('.bom-total');
    if (tot) tot.textContent = formatMoney(cant * costo);
}

// ── PLANTILLAS BOM ────────────────────────────────────────
let _plantillas = [];

async function abrirModalPlantilla() {
    if (!_plantillas.length) {
        try {
            const r = await apiGet(`${BASE}?action=plantillas_listar`);
            _plantillas = r.plantillas || [];
        } catch(e) { Toast.error('Error al cargar plantillas.'); return; }
    }
    const sel = document.getElementById('plantillaId');
    sel.innerHTML = '<option value="">— Seleccionar —</option>' +
        _plantillas.map(p =>
            `<option value="${p.id}" data-desc="${esc(p.descripcion||'')} (${p.total_items} materiales)">
                [${esc(p.codigo)}] ${esc(p.nombre)}
            </option>`
        ).join('');
    document.getElementById('plantillaDesc').textContent = '';
    document.getElementById('plantillaCantidad').value = 1;
    document.getElementById('plantillaAlerta').style.display = _bomTieneItems ? '' : 'none';
    Modal.open('modalPlantilla');
}

function mostrarDescPlantilla() {
    const opt = document.getElementById('plantillaId').selectedOptions[0];
    document.getElementById('plantillaDesc').textContent = opt?.dataset.desc || '';
}

async function cargarPlantilla() {
    const plantillaId = document.getElementById('plantillaId').value;
    const cantidad    = parseInt(document.getElementById('plantillaCantidad').value) || 0;
    if (!plantillaId) { Toast.warning('Seleccione una plantilla.'); return; }
    if (cantidad < 1)  { Toast.warning('La cantidad debe ser al menos 1.'); return; }
    try {
        const r = await apiPost(BASE, {
            action:            'bom_cargar_plantilla',
            proyecto_id:       PID,
            plantilla_id:      plantillaId,
            cantidad_maquinas: cantidad,
        });
        if (r.ok) {
            Toast.success(`Plantilla cargada: ${r.cargados} materiales para ${cantidad} máquina(s).`);
            Modal.close('modalPlantilla');
            cargarDetalle();
        } else {
            Toast.error(r.error || 'Error al cargar plantilla.');
        }
    } catch(e) { Toast.error('Error de conexión.'); }
}

// ── Abrir modal de material para una pieza concreta ───────
function agregarMaterialAPieza(piezaId, piezaNombre) {
    document.getElementById('bomPiezaId').value = piezaId;
    document.getElementById('bomPiezaNombreLabel').textContent = piezaNombre;
    document.getElementById('bomPiezaLabel').style.display = '';

    // Poblar select de partes para esta pieza
    const partesDePieza = _partesAll.filter(p => String(p.pieza_id) === String(piezaId));
    const wrap = document.getElementById('bomParteWrap');
    const sel  = document.getElementById('bomParteSelect');
    if (partesDePieza.length) {
        sel.innerHTML = '<option value="">— Sin parte —</option>' +
            partesDePieza.map(p => `<option value="${p.id}">${esc(p.nombre)}</option>`).join('');
        sel.selectedIndex = 1;
        wrap.style.display = '';
    } else {
        sel.innerHTML = '<option value="">— Sin parte —</option>';
        wrap.style.display = 'none';
    }

    document.getElementById('bomProducto').value       = '';
    document.getElementById('bomProductoBuscar').value = '';
    document.getElementById('bomProductoDrop').style.display = 'none';
    document.getElementById('bomCantidad').value = '';
    document.getElementById('bomCosto').value    = '';
    document.getElementById('bomCostoHint').style.display = 'none';
    document.getElementById('bomObs').value      = '';
    document.getElementById('bomMedida').value        = '';
    document.getElementById('bomMedidaUnidad').value  = 'cm';
    document.getElementById('bomPieza').value    = '';
    Modal.open('modalBom');
    setTimeout(() => document.getElementById('bomProductoBuscar').focus(), 120);
}

function configurarPieza() {
    const sel = document.getElementById('cfgPiezaSelect');
    sel.innerHTML = '<option value="">— Seleccionar pieza —</option>';
    _piezasAll.forEach(p => {
        const o = document.createElement('option');
        o.value = p.id; o.textContent = p.nombre;
        sel.appendChild(o);
    });
    _limpiarFormCfg();
    Modal.open('modalCfgPieza');
    setTimeout(() => initTabs(document.getElementById('modalCfgPieza')), 50);
}

function _limpiarFormCfg() {
    document.querySelectorAll('#modalCfgPieza input, #modalCfgPieza select, #modalCfgPieza textarea').forEach(el => {
        if (el.id === 'cfgPiezaSelect' || el.type === 'radio') return;
        el.value = '';
    });
    _cfgUnidadActual = 'mm';
    document.querySelectorAll('input[name="cfgUnidad"]').forEach(r => r.checked = r.value === 'mm');
    document.querySelectorAll('.cfg-unit-label').forEach(el => el.textContent = 'mm');
    document.getElementById('cfgBody').style.display = 'none';
}

const _cfgToMm = { mm: 1, cm: 10, m: 1000 };
let _cfgUnidadActual = 'mm';

function onCfgUnidadChange(newUnit) {
    const factor = _cfgToMm[_cfgUnidadActual] / _cfgToMm[newUnit];
    document.querySelectorAll('.cfg-dim-field').forEach(el => {
        const v = parseFloat(el.value);
        if (!isNaN(v) && v !== 0) el.value = parseFloat((v * factor).toFixed(6));
    });
    document.querySelectorAll('.cfg-unit-label').forEach(el => el.textContent = newUnit);
    _cfgUnidadActual = newUnit;
}

function onCfgPiezaChange() {
    const piezaId = document.getElementById('cfgPiezaSelect').value;
    if (!piezaId) { document.getElementById('cfgBody').style.display = 'none'; return; }
    const pieza = _piezasAll.find(p => String(p.id) === String(piezaId));
    const props = pieza?.propiedades_tecnicas || {};

    // Restaurar unidad guardada (o mm por defecto)
    const savedUnit = props.UnidadDim || 'mm';
    console.log('[CFG CARGAR] pieza:', piezaId, '| props desde _piezasAll:', JSON.stringify(props), '| unidad:', savedUnit);
    _cfgUnidadActual = savedUnit;
    document.querySelectorAll('input[name="cfgUnidad"]').forEach(r => r.checked = r.value === savedUnit);
    document.querySelectorAll('.cfg-unit-label').forEach(el => el.textContent = savedUnit);

    const dimFields = new Set(['Largo','Ancho','Alto','Espesor','Radio']);
    const fields = [
        'cfgMaterial','cfgCategoria','cfgNorma','cfgDescripcion',
        'cfgLargo','cfgAncho','cfgAlto','cfgEspesor','cfgRadio','cfgPesoNeto',
        'cfgDensidad','cfgMasa','cfgVolumen',
        'cfgTraccion','cfgElastico','cfgYoung','cfgCorte','cfgPoisson',
        'cfgDureza','cfgDurezaTipo','cfgElongacion',
        'cfgCondTerm','cfgCalorEsp','cfgDilatacion','cfgFusion',
        'cfgRa','cfgTratamiento','cfgRecubrimiento','cfgNotas'
    ];
    fields.forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        const key = id.replace('cfg', '');
        let val = props[key] ?? '';
        // Convertir dimensiones de mm (almacenado) a la unidad guardada
        if (val !== '' && dimFields.has(key) && savedUnit !== 'mm') {
            const n = parseFloat(val);
            if (!isNaN(n)) val = String(parseFloat((n / _cfgToMm[savedUnit]).toFixed(6)));
        }
        el.value = val;
    });
    document.getElementById('cfgBody').style.display = '';
    document.querySelector('#modalCfgPieza .tabs .tab').click();
}

async function guardarPropsPieza() {
    const piezaId = document.getElementById('cfgPiezaSelect').value;
    if (!piezaId) { Toast.warning('Seleccione una pieza.'); return; }
    const props = {};
    const dimFields = new Set(['Largo','Ancho','Alto','Espesor','Radio']);
    const factorToMm = _cfgToMm[_cfgUnidadActual];
    document.querySelectorAll('#cfgBody input, #cfgBody select, #cfgBody textarea').forEach(el => {
        if (!el.id || el.value === '' || el.name === 'cfgUnidad') return;
        const key = el.id.replace('cfg', '');
        if (!key) return;
        let val = el.value;
        if (dimFields.has(key) && factorToMm !== 1) {
            const n = parseFloat(val);
            if (!isNaN(n)) val = String(parseFloat((n * factorToMm).toFixed(6)));
        }
        props[key] = val;
    });
    props['UnidadDim'] = _cfgUnidadActual;
    console.log('[CFG GUARDAR] props enviados:', JSON.stringify(props));
    try {
        const r = await apiPost(BASE, { action: 'pieza_propiedades_guardar', id: parseInt(piezaId), propiedades: props });
        console.log('[CFG GUARDAR] respuesta API:', r);
        if (r.ok) {
            Toast.success('Propiedades guardadas.');
            const pieza = _piezasAll.find(p => String(p.id) === String(piezaId));
            if (pieza) { pieza.propiedades_tecnicas = props; console.log('[CFG GUARDAR] _piezasAll actualizado:', props); }
            Modal.close('modalCfgPieza');
        } else Toast.error(r.error || 'Error al guardar.');
    } catch(e) { console.error('[CFG GUARDAR] error:', e); Toast.error('Error: ' + e.message); }
}

function abrirModalCrearPieza() {
    document.getElementById('piezaNombre').value = '';
    Modal.open('modalCrearPieza');
    setTimeout(() => document.getElementById('piezaNombre').focus(), 120);
}

async function crearPieza() {
    const nombre = document.getElementById('piezaNombre').value.trim();
    if (!nombre) { Toast.warning('Ingrese un nombre para la pieza.'); return; }
    try {
        const r = await apiPost(BASE, { action:'pieza_crear', proyecto_id:PID, nombre });
        if (r.ok) {
            Toast.success('Pieza creada.');
            Modal.close('modalCrearPieza');
            cargarDetalle();
        } else { Toast.error(r.error || 'Error.'); }
    } catch(e) { Toast.error('Error de conexión.'); }
}

async function renombrarPieza(id, nombreActual) {
    const nuevo = prompt('Nuevo nombre para la pieza:', nombreActual);
    if (!nuevo || nuevo.trim() === nombreActual) return;
    try {
        const r = await apiPost(BASE, { action:'pieza_renombrar', id, nombre: nuevo.trim() });
        if (r.ok) { Toast.success('Pieza renombrada.'); cargarDetalle(); }
        else Toast.error(r.error || 'Error.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

async function eliminarPieza(id, nombre) {
    if (!confirm(`¿Eliminar la pieza "${nombre}" y todos sus materiales?`)) return;
    try {
        const r = await apiPost(BASE, { action:'pieza_eliminar', id });
        if (r.ok) { Toast.success('Pieza eliminada.'); cargarDetalle(); }
        else Toast.error(r.error || 'Error.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

// ── PARTES ────────────────────────────────────────────────
function abrirModalCrearParte(piezaId) {
    document.getElementById('partePiezaId').value = piezaId;
    document.getElementById('parteNombre').value  = '';
    Modal.open('modalCrearParte');
    setTimeout(() => document.getElementById('parteNombre').focus(), 120);
}

async function crearParte() {
    const nombre  = document.getElementById('parteNombre').value.trim();
    const piezaId = document.getElementById('partePiezaId').value;
    if (!nombre) { Toast.warning('Ingrese un nombre para la parte.'); return; }
    try {
        const r = await apiPost(BASE, { action: 'parte_crear', pieza_id: piezaId, nombre });
        if (r.ok) { Toast.success('Parte creada.'); Modal.close('modalCrearParte'); cargarDetalle(); }
        else Toast.error(r.error || 'Error.');
    } catch(e) { Toast.error(e.message || 'Error de conexión.'); }
}

async function renombrarParte(id, nombreActual) {
    const nuevo = prompt('Nuevo nombre para la parte:', nombreActual);
    if (!nuevo || nuevo.trim() === nombreActual) return;
    try {
        const r = await apiPost(BASE, { action: 'parte_renombrar', id, nombre: nuevo.trim() });
        if (r.ok) { Toast.success('Parte renombrada.'); cargarDetalle(); }
        else Toast.error(r.error || 'Error.');
    } catch(e) { Toast.error(e.message || 'Error de conexión.'); }
}

async function eliminarParte(id, nombre) {
    if (!confirm(`¿Eliminar la parte "${nombre}"? Los materiales asociados quedarán sin parte.`)) return;
    try {
        const r = await apiPost(BASE, { action: 'parte_eliminar', id });
        if (r.ok) { Toast.success('Parte eliminada.'); cargarDetalle(); }
        else Toast.error(r.error || 'Error.');
    } catch(e) { Toast.error(e.message || 'Error de conexión.'); }
}

document.getElementById('parteNombre').addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); crearParte(); }
});

async function agregarBom() {
    const prod     = document.getElementById('bomProducto').value;
    const cant     = document.getElementById('bomCantidad').value;
    const piezaId  = document.getElementById('bomPiezaId').value;
    if (!prod || !cant) { Toast.warning('Seleccione producto y cantidad.'); return; }
    const costo  = document.getElementById('bomCosto').value || 0;
    const obs    = document.getElementById('bomObs').value;
    const medidaVal    = document.getElementById('bomMedida').value.trim();
    const medidaUnidad = document.getElementById('bomMedidaUnidad').value;
    const medida       = medidaVal || '';
    const pieza  = document.getElementById('bomPieza').value;
    try {
        const r = await apiPost(BASE, {
            action: 'bom_agregar', proyecto_id: PID,
            pieza_id: piezaId || null,
            parte_id: document.getElementById('bomParteSelect').value || null,
            producto_id: prod, cantidad_planificada: cant,
            costo_unitario_estimado: costo, observaciones: obs,
            medida, medida_unidad: medidaUnidad, pieza,
        });
        if (r.ok) { Toast.success('Material agregado.'); Modal.close('modalBom'); cargarDetalle(); }
        else Toast.error(r.error || 'Error.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

async function eliminarBom(bomId) {
    if (!confirm('¿Eliminar este material?')) return;
    try {
        await apiPost(BASE, { action:'bom_eliminar', id:bomId });
        Toast.success('Eliminado.'); cargarDetalle();
    } catch(e) { Toast.error('Error.'); }
}

// ── Búsqueda de producto en modal BOM ────────────────────
(function() {
    const inp  = document.getElementById('bomProductoBuscar');
    const hid  = document.getElementById('bomProducto');
    const drop = document.getElementById('bomProductoDrop');

    inp.addEventListener('input', function() {
        const q = this.value.trim().toLowerCase();
        hid.value = '';
        document.getElementById('bomCosto').value = '';

        if (!q) { drop.style.display = 'none'; drop.innerHTML = ''; return; }

        const matches = PRODUCTOS_BOM.filter(p =>
            p.nombre.toLowerCase().includes(q) || p.codigo.toLowerCase().includes(q)
        ).slice(0, 25);

        if (!matches.length) {
            drop.innerHTML = '<div style="padding:10px 12px;color:var(--text-muted);font-size:.83rem">Sin resultados</div>';
        } else {
            drop.innerHTML = matches.map(p =>
                `<div class="bom-prod-item"
                      data-id="${p.id}"
                      data-precio="${parseFloat(p.precio_venta) || 0}"
                      data-label="[${esc(p.codigo)}] ${esc(p.nombre)} (${esc(p.unidad)})">
                    <span style="font-weight:600;color:var(--primary)">${esc(p.codigo)}</span>
                    <span style="margin-left:6px">${esc(p.nombre)}</span>
                    <span style="margin-left:4px;color:var(--text-muted);font-size:.75rem">(${esc(p.unidad)})</span>
                </div>`
            ).join('');
        }
        drop.style.display = 'block';
    });

    drop.addEventListener('click', function(e) {
        const item = e.target.closest('.bom-prod-item');
        if (!item) return;
        hid.value = item.dataset.id;
        inp.value = item.dataset.label;
        const precio = parseFloat(item.dataset.precio) || 0;
        const costoEl = document.getElementById('bomCosto');
        costoEl.value = precio.toFixed(2);
        document.getElementById('bomCostoHint').style.display = precio > 0 ? '' : 'none';
        drop.style.display = 'none';
        document.getElementById('bomCantidad').focus();
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('#bomProductoBuscar') && !e.target.closest('#bomProductoDrop')) {
            drop.style.display = 'none';
        }
    });
})();

// Enter en input de nombre de pieza confirma
document.getElementById('piezaNombre').addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); crearPieza(); }
});

// ── COSTOS ────────────────────────────────────────────────
function renderCostos(costos, proyecto) {
    const est  = parseFloat(proyecto.costo_estimado)  || 0;
    const real = parseFloat(proyecto.costo_real)       || 0;
    const pct  = est > 0 ? Math.min((real / est) * 100, 150) : 0;
    const dev  = est > 0 ? ((real - est) / est * 100) : 0;

    document.getElementById('costosResumen').innerHTML = `
        <div class="cost-bar" style="margin-bottom:16px">
            <div style="display:flex;justify-content:space-between;margin-bottom:8px">
                <span class="text-sm font-semibold">Planificado</span>
                <span class="text-sm font-semibold">${formatMoney(est)}</span>
            </div>
            <div class="progress-bar-wrap" style="height:12px">
                <div class="progress-bar-fill" style="width:100%"></div>
            </div>
            <div style="display:flex;justify-content:space-between;margin-top:12px;margin-bottom:8px">
                <span class="text-sm font-semibold ${real > est ? 'text-danger' : 'text-success'}">Real</span>
                <span class="text-sm font-semibold ${real > est ? 'text-danger' : 'text-success'}">${formatMoney(real)}</span>
            </div>
            <div class="progress-bar-wrap" style="height:12px">
                <div class="progress-bar-fill ${real > est ? 'red' : 'green'}" style="width:${Math.min(pct,100)}%"></div>
            </div>
        </div>
        <div style="text-align:center;padding:10px;background:${dev > 10 ? 'var(--danger-light)' : dev > 0 ? 'var(--warning-light)' : 'var(--success-light)'};border-radius:var(--radius-sm)">
            <div style="font-size:1.3rem;font-weight:700;color:${dev > 10 ? 'var(--danger)' : dev > 0 ? 'var(--warning)' : 'var(--success)'}">
                ${dev > 0 ? '+' : ''}${dev.toFixed(1)}%
            </div>
            <div class="text-xs text-muted">desviación del costo estimado</div>
        </div>`;

    const tbody = document.getElementById('costosBody');
    if (!costos.length) { tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Sin costos adicionales.</td></tr>'; return; }
    const tipoLabel = { mano_obra:'Mano de Obra', material:'Material', overhead:'Overhead', otros:'Otros' };
    tbody.innerHTML = costos.map(c => `<tr>
        <td><span class="badge badge-normal">${tipoLabel[c.tipo] || c.tipo}</span></td>
        <td>${esc(c.descripcion)}</td>
        <td>${c.fecha ? new Date(c.fecha+'T00:00:00').toLocaleDateString('es-PE') : '—'}</td>
        <td class="text-right font-semibold">${formatMoney(c.monto)}</td>
    </tr>`).join('');
}

async function agregarCosto() {
    const desc  = document.getElementById('costoDesc').value.trim();
    const monto = document.getElementById('costoMonto').value;
    if (!desc || !monto) { Toast.warning('Complete todos los campos.'); return; }
    try {
        const r = await apiPost(BASE, {
            action:'costo_agregar', proyecto_id:PID,
            tipo:document.getElementById('costoTipo').value,
            descripcion:desc, monto,
            fecha:document.getElementById('costoFecha').value
        });
        if (r.ok) { Toast.success('Costo registrado.'); Modal.close('modalCosto'); cargarDetalle(); }
        else Toast.error(r.error || 'Error.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

// ── AVANCES ───────────────────────────────────────────────
function renderAvances(avances) {
    const tbody = document.getElementById('avancesBody');
    if (!avances.length) { tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Sin avances registrados.</td></tr>'; return; }
    tbody.innerHTML = avances.map(a => `<tr>
        <td>${new Date(a.fecha).toLocaleString('es-PE')}</td>
        <td>${a.area_nombre ? `<span class="badge badge-normal">${esc(a.area_nombre)}</span>` : '—'}</td>
        <td>${esc(a.usuario_nombre || '—')}</td>
        <td>${esc(a.descripcion)}</td>
        <td class="text-right font-bold">${a.porcentaje}%</td>
    </tr>`).join('');
}

async function registrarAvance() {
    const desc = document.getElementById('avanceDesc').value.trim();
    const pct  = document.getElementById('avancePct').value;
    if (!desc) { Toast.warning('Ingrese la descripción del avance.'); return; }
    try {
        const r = await apiPost(BASE, {
            action:'avance_registrar', proyecto_id:PID,
            area_id:document.getElementById('avanceArea').value,
            descripcion:desc, porcentaje:pct
        });
        if (r.ok) { Toast.success('Avance registrado.'); Modal.close('modalAvance'); location.reload(); }
        else Toast.error(r.error || 'Error.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

// ── ALERTAS ───────────────────────────────────────────────
function renderAlertas(proyecto) {
    const alertas = [];
    const hoy = new Date(); hoy.setHours(0,0,0,0);
    if (proyecto.fecha_entrega_estimada) {
        const fe = new Date(proyecto.fecha_entrega_estimada + 'T00:00:00');
        const dias = Math.round((fe - hoy) / 86400000);
        if (dias < 0 && !['completado','cancelado'].includes(proyecto.estado))
            alertas.push({ tipo:'danger', msg:`⚠ Proyecto retrasado ${Math.abs(dias)} días. Fecha estimada: ${fe.toLocaleDateString('es-PE')}` });
        else if (dias <= 7 && dias >= 0)
            alertas.push({ tipo:'warning', msg:`⏰ Entrega en ${dias} días (${fe.toLocaleDateString('es-PE')})` });
    }
    const est  = parseFloat(proyecto.costo_estimado) || 0;
    const real = parseFloat(proyecto.costo_real) || 0;
    if (est > 0 && real > est * 1.1)
        alertas.push({ tipo:'danger', msg:`💰 Costo real supera el estimado en ${(((real-est)/est)*100).toFixed(1)}%` });
    else if (est > 0 && real > est * 0.9)
        alertas.push({ tipo:'warning', msg:`💰 Costo real se acerca al límite estimado (${(((real-est)/est)*100).toFixed(1)}%)` });
    if (parseInt(proyecto.porcentaje_avance) < 20 && proyecto.fecha_inicio) {
        const fi = new Date(proyecto.fecha_inicio + 'T00:00:00');
        if ((hoy - fi) / 86400000 > 7)
            alertas.push({ tipo:'warning', msg:`📊 Avance bajo (${proyecto.porcentaje_avance}%) a más de 7 días del inicio.` });
    }

    const cont = document.getElementById('alertasBody');
    if (!alertas.length) {
        cont.innerHTML = '<div class="empty-state"><div class="icon">✅</div><p>Sin alertas activas para este proyecto.</p></div>';
    } else {
        cont.innerHTML = alertas.map(a => `<div class="alert alert-${a.tipo}">${a.msg}</div>`).join('');
    }
}

// ── CAMBIAR ESTADO ────────────────────────────────────────
async function cambiarEstado() {
    const estado = document.getElementById('nuevoEstado').value;
    try {
        const r = await apiPost(BASE, { action:'cambiar_estado', id:PID, estado });
        if (r.ok) { Toast.success('Estado actualizado.'); setTimeout(() => location.reload(), 800); }
        else Toast.error(r.error || 'Error.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

function fNum(n) { return Number(n).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 3 }); }
function esc(s)  { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

// ── AGREGAR ÁREA AL WORKFLOW ──────────────────────────────
function abrirModalWorkflowAgregar() {
    document.getElementById('wfaAreaNombre').value    = '';
    document.getElementById('wfaEstado').value        = 'pendiente';
    document.getElementById('wfaPorcentaje').value    = 0;
    document.getElementById('wfaResponsable').value   = '';
    document.getElementById('wfaObservaciones').value = '';
    Modal.open('modalWorkflowAgregar');
    setTimeout(() => document.getElementById('wfaAreaNombre').focus(), 120);
}

async function guardarWorkflowAgregar() {
    const nombre = document.getElementById('wfaAreaNombre').value.trim();
    if (!nombre) { Toast.warning('Ingrese el nombre del área.'); return; }
    try {
        const r = await apiPost(BASE, {
            action:         'workflow_area_agregar',
            proyecto_id:    PID,
            nombre,
            estado:         document.getElementById('wfaEstado').value,
            porcentaje:     document.getElementById('wfaPorcentaje').value,
            responsable_id: document.getElementById('wfaResponsable').value,
            observaciones:  document.getElementById('wfaObservaciones').value,
        });
        if (r.ok) {
            Toast.success('Área agregada.');
            Modal.close('modalWorkflowAgregar');
            setTimeout(() => location.reload(), 700);
        } else {
            Toast.error(r.error || 'Error.');
        }
    } catch(e) { Toast.error('Error de conexión.'); }
}

// ── SERVICIOS EXTERNOS ───────────────────────────────────
let _svcEditing = false;

function abrirModalServicio() {
    _svcEditing = false;
    document.getElementById('svcWaId').value         = '';
    document.getElementById('svcNombreWrap').style.display = '';
    document.getElementById('svcNombre').value        = '';
    document.getElementById('svcEstado').value        = 'pendiente';
    document.getElementById('svcPorcentaje').value    = 0;
    document.getElementById('svcResponsable').value   = '';
    document.getElementById('svcObservaciones').value = '';
    document.getElementById('modalServicioTitulo').innerHTML =
        '<i class="fa fa-truck-fast" style="color:#f59e0b"></i> Agregar Servicio Externo';
    Modal.open('modalServicio');
    setTimeout(() => document.getElementById('svcNombre').focus(), 120);
}

function editarServicio(data) {
    _svcEditing = true;
    document.getElementById('svcWaId').value         = data.waId;
    document.getElementById('svcNombreWrap').style.display = 'none';
    document.getElementById('svcEstado').value        = data.estado      || 'pendiente';
    document.getElementById('svcPorcentaje').value    = data.porcentaje  || 0;
    document.getElementById('svcResponsable').value   = data.responsable_id || '';
    document.getElementById('svcObservaciones').value = data.observaciones  || '';
    document.getElementById('modalServicioTitulo').innerHTML =
        '<i class="fa fa-pencil" style="color:#f59e0b"></i> Editar — ' + esc(data.nombre);
    Modal.open('modalServicio');
}

async function guardarServicio() {
    try {
        let r;
        if (_svcEditing) {
            r = await apiPost(BASE, {
                action:         'workflow_actualizar',
                id:             document.getElementById('svcWaId').value,
                proyecto_id:    PID,
                estado:         document.getElementById('svcEstado').value,
                porcentaje:     document.getElementById('svcPorcentaje').value,
                responsable_id: document.getElementById('svcResponsable').value,
                observaciones:  document.getElementById('svcObservaciones').value,
            });
            if (r.ok) { Toast.success('Servicio actualizado.'); Modal.close('modalServicio'); setTimeout(() => location.reload(), 700); }
            else Toast.error(r.error || 'Error.');
        } else {
            const nombre = document.getElementById('svcNombre').value.trim();
            if (!nombre) { Toast.warning('Ingrese el nombre del servicio.'); return; }
            r = await apiPost(BASE, {
                action:         'servicio_agregar',
                proyecto_id:    PID,
                nombre,
                estado:         document.getElementById('svcEstado').value,
                porcentaje:     document.getElementById('svcPorcentaje').value,
                responsable_id: document.getElementById('svcResponsable').value,
                observaciones:  document.getElementById('svcObservaciones').value,
            });
            if (r.ok) { Toast.success('Servicio agregado.'); Modal.close('modalServicio'); setTimeout(() => location.reload(), 700); }
            else Toast.error(r.error || 'Error.');
        }
    } catch(e) { Toast.error('Error de conexión.'); }
}

async function eliminarServicio(waId, nombre) {
    if (!confirm(`¿Eliminar el servicio "${nombre}"?`)) return;
    try {
        const r = await apiPost(BASE, { action: 'servicio_eliminar', id: waId });
        if (r.ok) { Toast.success('Servicio eliminado.'); setTimeout(() => location.reload(), 600); }
        else Toast.error(r.error || 'Error.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

// ── WORKFLOW ──────────────────────────────────────────────
function abrirModalWorkflow(data) {
    document.getElementById('wfId').value              = data.id;
    document.getElementById('wfProyectoId').value      = data.proyecto_id;
    document.getElementById('wfModalTitulo').textContent = 'Actualizar — ' + data.area_nombre;
    document.getElementById('wfEstado').value          = data.estado;
    document.getElementById('wfPorcentaje').value      = data.porcentaje;
    document.getElementById('wfResponsable').value     = data.responsable_id || '';
    document.getElementById('wfObservaciones').value   = data.observaciones || '';
    Modal.open('modalWorkflow');
}

async function guardarWorkflow() {
    const id          = document.getElementById('wfId').value;
    const proyecto_id = document.getElementById('wfProyectoId').value;
    const estado      = document.getElementById('wfEstado').value;
    const porcentaje  = document.getElementById('wfPorcentaje').value;
    try {
        const r = await apiPost(BASE, {
            action:         'workflow_actualizar',
            id,
            proyecto_id,
            estado,
            porcentaje,
            responsable_id: document.getElementById('wfResponsable').value,
            observaciones:  document.getElementById('wfObservaciones').value,
        });
        if (r.ok) {
            Toast.success('Área actualizada. Recargando…');
            Modal.close('modalWorkflow');
            setTimeout(() => location.reload(), 700);
        } else {
            Toast.error(r.error || 'Error al guardar.');
        }
    } catch(e) { Toast.error('Error de conexión.'); }
}

// ── Solicitudes pendientes en tarjetas de área ────────────
async function cargarSolicitudesPendientes() {
    try {
        const d = await apiGet(`${BASE}?action=solicitudes_pendientes_proyecto&id=${PID}`);
        if (!d.ok) return;
        Object.entries(d.pendientes).forEach(([waId, count]) => {
            const badge = document.getElementById(`solBadge-${waId}`);
            if (badge && count > 0) {
                badge.querySelector('span').textContent = `${count} solicitud${count > 1 ? 'es' : ''} pendiente${count > 1 ? 's' : ''}`;
                badge.style.display = 'block';
            }
        });
    } catch(e) { /* silencioso */ }
}

// ── Ajustes de Workflow ───────────────────────────────────
async function cargarAjustesWf() {
    const body = document.getElementById('wfAjustesBody');
    body.innerHTML = '<div class="loading" style="padding:24px"><div class="spinner"></div></div>';
    try {
        const d = await apiGet(`${BASE}?action=wf_areas_listar`);
        renderAjustesWf(d.areas || []);
    } catch(e) { Toast.error('Error al cargar áreas.'); }
}

function renderAjustesWf(lista) {
    const body = document.getElementById('wfAjustesBody');
    if (!lista.length) {
        body.innerHTML = `<p style="padding:24px;text-align:center;color:var(--text-muted)">Sin áreas configuradas. Usa <strong>Nueva Área</strong> para comenzar.</p>`;
        return;
    }
    body.innerHTML = `<table style="width:100%;border-collapse:collapse">
        <thead>
            <tr style="border-bottom:2px solid var(--border);font-size:.74rem;color:var(--text-muted);text-transform:uppercase">
                <th style="padding:8px 12px;width:44px;text-align:center">Orden</th>
                <th style="padding:8px 12px">Área</th>
                <th style="padding:8px 12px;text-align:center">Tipo</th>
                <th style="padding:8px 12px;text-align:center">Estado</th>
                <th style="padding:8px 12px;text-align:center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            ${lista.map(a => `
            <tr style="border-bottom:1px solid var(--border)">
                <td style="padding:8px 12px;text-align:center;font-weight:700;color:var(--text-muted)">${a.orden_workflow}</td>
                <td style="padding:8px 12px">
                    <strong style="font-size:.88rem">${esc(a.nombre)}</strong>
                    ${a.descripcion ? `<div style="font-size:.75rem;color:var(--text-muted)">${esc(a.descripcion)}</div>` : ''}
                </td>
                <td style="padding:8px 12px;text-align:center">
                    ${a.es_externa
                        ? `<span class="badge" style="background:#f59e0b;color:#fff;font-size:.68rem">Externo</span>`
                        : `<span class="badge badge-completado" style="font-size:.68rem">Interno</span>`}
                </td>
                <td style="padding:8px 12px;text-align:center">
                    ${a.activo
                        ? `<span class="badge badge-completado" style="font-size:.68rem">Activa</span>`
                        : `<span class="badge badge-cancelado" style="font-size:.68rem">Inactiva</span>`}
                </td>
                <td style="padding:8px 12px;text-align:center">
                    <div style="display:flex;gap:5px;justify-content:center">
                        <button class="btn btn-outline btn-xs" onclick="editarWfArea(${a.id})" title="Editar">
                            <i class="fa fa-pencil"></i>
                        </button>
                        <button class="btn btn-xs ${a.activo ? 'btn-outline' : 'btn-primary'}"
                                onclick="toggleWfArea(${a.id},'${esc(a.nombre)}')" title="${a.activo ? 'Desactivar' : 'Activar'}">
                            <i class="fa fa-${a.activo ? 'ban' : 'check'}"></i>
                        </button>
                        <button class="btn btn-xs btn-outline" style="color:#dc2626;border-color:#dc2626"
                                onclick="eliminarWfArea(${a.id},'${esc(a.nombre)}')" title="Eliminar">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>`).join('')}
        </tbody>
    </table>`;
}

function abrirFormWfArea(area = null) {
    document.getElementById('wfAreaFormId').value      = area ? area.id    : '';
    document.getElementById('wfAreaFormNombre').value  = area ? area.nombre : '';
    document.getElementById('wfAreaFormDesc').value    = area ? (area.descripcion || '') : '';
    document.getElementById('wfAreaFormOrden').value   = area ? (area.orden_workflow || 99) : 99;
    document.getElementById('wfAreaFormExterna').value = area ? (area.es_externa ? '1' : '0') : '0';
    document.getElementById('wfAreaFormTitulo').innerHTML = area
        ? `<i class="fa fa-pencil"></i> Editar: ${esc(area.nombre)}`
        : `<i class="fa fa-sitemap"></i> Nueva Área`;
    Modal.close('modalAjustesWf');
    Modal.open('modalWfAreaForm');
}

function editarWfArea(id) {
    apiGet(`${BASE}?action=wf_areas_listar`).then(d => {
        const a = (d.areas || []).find(x => x.id == id);
        if (a) abrirFormWfArea(a);
    });
}

async function guardarWfArea() {
    const nombre = document.getElementById('wfAreaFormNombre').value.trim();
    if (!nombre) { Toast.warning('El nombre es obligatorio.'); return; }
    try {
        const r = await apiPost(BASE, {
            action:         'wf_area_guardar',
            id:             document.getElementById('wfAreaFormId').value,
            nombre,
            descripcion:    document.getElementById('wfAreaFormDesc').value.trim(),
            orden_workflow: document.getElementById('wfAreaFormOrden').value,
            es_externa:     document.getElementById('wfAreaFormExterna').value === '1',
        });
        if (r.ok) {
            Toast.success('Área guardada.');
            Modal.close('modalWfAreaForm');
            Modal.open('modalAjustesWf');
            cargarAjustesWf();
        } else Toast.error(r.error || 'Error al guardar.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

async function toggleWfArea(id, nombre) {
    if (!confirm(`¿Cambiar estado del área "${nombre}"?`)) return;
    try {
        const r = await apiPost(BASE, { action: 'wf_area_toggle', id });
        if (r.ok) cargarAjustesWf();
        else Toast.error(r.error || 'Error.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

async function eliminarWfArea(id, nombre) {
    if (!confirm(`¿Eliminar el área "${nombre}"?\n\nEsta acción no se puede deshacer.`)) return;
    try {
        const r = await apiPost(BASE, { action: 'wf_area_eliminar', id });
        if (r.ok) { Toast.success('Área eliminada.'); cargarAjustesWf(); }
        else Toast.error(r.error || 'Error al eliminar.');
    } catch(e) { Toast.error(e.message || 'Error de conexión.'); }
}

// ── Init ──────────────────────────────────────────────────
initTabs();
cargarDetalle();
cargarSolicitudesPendientes();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

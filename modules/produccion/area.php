<?php
$wa_id = (int)($_GET['id'] ?? 0);
if (!$wa_id) { header('Location: index.php'); exit; }

require_once __DIR__ . '/../../config/database.php';
$db = getDB();

$wa = $db->prepare("
    SELECT pa.*, a.nombre AS area_nombre,
        u.nombre  AS responsable_nombre,
        p.id      AS proyecto_id,
        p.codigo  AS proy_codigo,
        p.nombre  AS proy_nombre,
        p.estado  AS proy_estado
    FROM proyecto_areas pa
    JOIN areas     a ON a.id  = pa.area_id
    JOIN proyectos p ON p.id  = pa.proyecto_id
    LEFT JOIN usuarios u ON u.id = pa.responsable_id
    WHERE pa.id = :id");
$wa->execute([':id' => $wa_id]);
$area = $wa->fetch();
if (!$area) { header('Location: index.php'); exit; }

$usuarios  = $db->query("SELECT id, nombre FROM usuarios WHERE activo ORDER BY nombre")->fetchAll();
$areas_sel = $db->query("SELECT id, nombre FROM areas WHERE activo ORDER BY nombre")->fetchAll();

$page_title      = $area['area_nombre'] . ' — ' . $area['proy_codigo'];
$page_breadcrumb = '<a href="index.php">Producción</a> <span>›</span>
                    <a href="proyecto.php?id=' . $area['proyecto_id'] . '">' . htmlspecialchars($area['proy_codigo']) . '</a>
                    <span>›</span> ' . htmlspecialchars($area['area_nombre']);

require_once __DIR__ . '/../../includes/header.php';

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
$icono = $iconos[$area['area_nombre']] ?? 'fa-industry';

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
$pct    = (int)$area['porcentaje'];
$barCls = $pct >= 75 ? 'green' : ($pct >= 40 ? '' : ($pct > 0 ? 'red' : ''));

$kw_primary = [
    'Soldadura'          => 'SOLDAR',
    'Corte'              => 'CORTAR',
    'Torno'              => 'TORNO',
    'Taladro'            => 'TALADRO',
    'Rolado'             => 'ROLAR',
    'Ensamble'           => 'ENSAMBLE',
    'Pintura'            => 'PINTAR',
    'Control de Calidad' => 'CALIDAD',
    'Electricidad'       => 'ELECTRICIDAD',
];
$area_keyword = $kw_primary[$area['area_nombre']] ?? strtoupper($area['area_nombre']);

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES); }
function estadoLabelProy($e) {
    $map = ['fabricacion'=>'Fabricación','pruebas'=>'Pruebas','entrega'=>'Entrega','completado'=>'Completado','cancelado'=>'Cancelado'];
    return $map[$e] ?? $e;
}
?>

<!-- CABECERA DEL ÁREA -->
<div class="card mb-2">
    <div class="card-body" style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;padding:16px 20px">

        <!-- Icono + nombre -->
        <div style="display:flex;align-items:center;gap:14px;flex:1;min-width:220px">
            <span style="width:48px;height:48px;border-radius:50%;background:var(--primary-light,#eff6ff);
                         display:flex;align-items:center;justify-content:center;
                         color:var(--primary);font-size:1.3rem;flex-shrink:0">
                <i class="fa <?= $icono ?>"></i>
            </span>
            <div>
                <div style="font-size:1.1rem;font-weight:700"><?= esc($area['area_nombre']) ?></div>
                <div style="font-size:.8rem;color:var(--text-muted)">
                    <a href="proyecto.php?id=<?= $area['proyecto_id'] ?>" style="color:var(--primary);text-decoration:none">
                        <?= esc($area['proy_codigo']) ?>
                    </a>
                    — <?= esc($area['proy_nombre']) ?>
                    <span class="badge badge-<?= $area['proy_estado'] ?>" style="font-size:.65rem;margin-left:6px">
                        <?= estadoLabelProy($area['proy_estado']) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Estado + barra progreso -->
        <div style="min-width:200px;flex:1">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                <span class="badge <?= $estadoClase[$area['estado']] ?? 'badge-normal' ?>">
                    <?= $estadoLabel[$area['estado']] ?? $area['estado'] ?>
                </span>
                <span style="font-size:.8rem;color:var(--text-muted)">Avance del área</span>
                <strong style="font-size:.88rem"><?= $pct ?>%</strong>
            </div>
            <div class="progress-bar-wrap" style="height:10px">
                <div class="progress-bar-fill <?= $barCls ?>" style="width:<?= $pct ?>%"></div>
            </div>
        </div>

        <!-- Responsable -->
        <div style="min-width:140px;text-align:center">
            <div style="font-size:.7rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Responsable</div>
            <div style="font-weight:600;font-size:.88rem">
                <?= esc($area['responsable_nombre'] ?? '— Sin asignar') ?>
            </div>
        </div>

        <!-- Acciones -->
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <button class="btn btn-outline btn-sm" onclick="Modal.open('modalEditarArea')">
                <i class="fa fa-pencil"></i> Editar estado
            </button>
            <button class="btn btn-sm" style="background:#6366f1;color:#fff;border:none" onclick="abrirModalDiseno()">
                <i class="fa fa-file-image"></i> Nuevo diseño/plano
            </button>
            <button class="btn btn-primary btn-sm" onclick="Modal.open('modalAvance')">
                <i class="fa fa-plus"></i> Registrar avance
            </button>
        </div>
    </div>

    <?php if ($area['observaciones']): ?>
    <div style="padding:8px 20px 12px;font-size:.8rem;color:var(--text-secondary);
                background:var(--bg-muted,#f8fafc);border-top:1px solid var(--border-color)">
        <i class="fa fa-comment-dots" style="opacity:.5;margin-right:6px"></i><?= esc($area['observaciones']) ?>
    </div>
    <?php endif; ?>
</div>

<!-- CONTENIDO PRINCIPAL -->
<div style="display:grid;grid-template-columns:1fr 380px;gap:16px;align-items:start">

    <!-- ── COLUMNA IZQUIERDA: Tareas BOM ── -->
    <div class="card">
        <div class="card-header">
            <span class="card-title">
                <i class="fa fa-list-check"></i> Materiales asignados a <?= esc($area['area_nombre']) ?>
            </span>
            <button class="btn btn-primary btn-sm" onclick="abrirModalAsignarBom()">
                <i class="fa fa-plus"></i> Agregar Pieza
            </button>
        </div>
        <div class="table-responsive">
            <table id="tablaBomArea">
                <thead><tr>
                    <th>Sección</th>
                    <th>Material</th>
                    <th>Operación</th>
                    <th class="text-right">Cant. Plan.</th>
                    <th class="text-right">Cant. Real</th>
                    <th style="text-align:center;white-space:nowrap">Terminado</th>
                    <th></th>
                </tr></thead>
                <tbody id="bomAreaBody">
                    <tr><td colspan="7" class="text-center text-muted">Cargando…</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ── COLUMNA DERECHA: Solicitudes + Avances ── -->
    <div style="display:flex;flex-direction:column;gap:16px">

        <!-- Panel diseños y planos -->
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fa fa-drafting-compass"></i> Diseños y Planos</span>
                <button class="btn btn-sm" style="background:#6366f1;color:#fff;border:none;font-size:.75rem"
                        onclick="abrirModalDiseno()">
                    <i class="fa fa-plus"></i> Nuevo
                </button>
            </div>
            <div id="disenosBody" style="padding:0 4px">
                <div class="loading"><div class="spinner"></div></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span class="card-title"><i class="fa fa-clock-rotate-left"></i> Registro de trabajo</span>
            </div>
            <div id="avancesBody" style="padding:0 4px">
                <div class="loading"><div class="spinner"></div></div>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================
     MODALES
     ====================================================== -->

<!-- Modal: Editar estado del área -->
<div class="modal-overlay" id="modalEditarArea">
    <div class="modal" style="max-width:440px">
        <div class="modal-header">
            <span class="modal-title">
                <i class="fa <?= $icono ?>"></i> Actualizar — <?= esc($area['area_nombre']) ?>
            </span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="wfId"         value="<?= $wa_id ?>">
            <input type="hidden" id="wfProyectoId" value="<?= $area['proyecto_id'] ?>">
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select class="form-control" id="wfEstado">
                        <option value="pendiente"  <?= $area['estado']==='pendiente'  ? 'selected':'' ?>>Pendiente</option>
                        <option value="en_proceso" <?= $area['estado']==='en_proceso' ? 'selected':'' ?>>En proceso</option>
                        <option value="completado" <?= $area['estado']==='completado' ? 'selected':'' ?>>Completado</option>
                        <option value="bloqueado"  <?= $area['estado']==='bloqueado'  ? 'selected':'' ?>>Bloqueado</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">% Avance del área</label>
                    <input type="number" class="form-control" id="wfPorcentaje"
                           value="<?= $pct ?>" min="0" max="100" step="5">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Responsable</label>
                <select class="form-control" id="wfResponsable">
                    <option value="">— Sin asignar —</option>
                    <?php foreach ($usuarios as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $area['responsable_id'] == $u['id'] ? 'selected' : '' ?>>
                        <?= esc($u['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Observaciones</label>
                <textarea class="form-control" id="wfObservaciones" rows="3"
                          placeholder="Notas, bloqueos, avances…"><?= esc($area['observaciones'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalEditarArea')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarEstadoArea()">
                <i class="fa fa-save"></i> Guardar
            </button>
        </div>
    </div>
</div>

<!-- Modal: Diseño y Plano -->
<div class="modal-overlay" id="modalDiseno">
    <div class="modal" style="max-width:500px">
        <div class="modal-header">
            <span class="modal-title">
                <i class="fa fa-drafting-compass"></i> Nuevo Diseño / Plano — <?= esc($area['area_nombre']) ?>
            </span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Nombre del diseño / plano *</label>
                <input type="text" class="form-control" id="disNombre"
                       placeholder="Ej: Plano de corte, Diseño isométrico…">
            </div>
            <div class="form-group">
                <label class="form-label">Descripción</label>
                <textarea class="form-control" id="disDesc" rows="2"
                          placeholder="Notas, revisión, observaciones…"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Archivos * <span style="font-size:.75rem;color:var(--text-muted)">(JPG, PNG o PDF — máx. 8 MB c/u)</span></label>
                <!-- Zona drop/click -->
                <div id="disDropZone"
                     onclick="document.getElementById('disArchivo').click()"
                     ondragover="event.preventDefault();this.style.borderColor='#6366f1';this.style.background='#eef2ff'"
                     ondragleave="this.style.borderColor='';this.style.background=''"
                     ondrop="disDrop(event)"
                     style="border:2px dashed var(--border);border-radius:8px;padding:24px 16px;
                            text-align:center;cursor:pointer;transition:border-color .15s,background .15s;
                            background:var(--bg-muted,#f8fafc)">
                    <i class="fa fa-cloud-arrow-up" style="font-size:1.8rem;color:var(--text-muted);margin-bottom:6px;display:block"></i>
                    <div style="font-size:.85rem;color:var(--text-secondary)">Arrastra aquí o <strong style="color:#6366f1">haz clic para seleccionar</strong></div>
                    <div style="font-size:.73rem;color:var(--text-muted);margin-top:3px">JPG · PNG · PDF · Puedes seleccionar varios a la vez</div>
                </div>
                <input type="file" id="disArchivo" accept=".jpg,.jpeg,.png,.pdf" multiple style="display:none" onchange="disFileChange(this)">
                <!-- Lista de archivos seleccionados -->
                <div id="disFileList" style="margin-top:8px"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalDiseno')">Cancelar</button>
            <button class="btn btn-primary" style="background:#6366f1;border-color:#6366f1" onclick="guardarDiseno()">
                <i class="fa fa-cloud-arrow-up"></i> Subir
            </button>
        </div>
    </div>
</div>

<!-- Modal: Registrar avance -->
<div class="modal-overlay" id="modalAvance">
    <div class="modal" style="max-width:440px">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-plus"></i> Registrar avance</span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body">
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Responsable</label>
                    <select class="form-control" id="avUsuario">
                        <option value="">— Sin asignar —</option>
                        <?php foreach ($usuarios as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $area['responsable_id'] == $u['id'] ? 'selected' : '' ?>>
                            <?= esc($u['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">% Avance del proyecto</label>
                    <input type="number" class="form-control" id="avPorcentaje"
                           value="<?= $area['proy_pct'] ?? 0 ?>" min="0" max="100" step="5">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Descripción *</label>
                <textarea class="form-control" id="avDescripcion" rows="4"
                          placeholder="¿Qué se realizó en esta área hoy?"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalAvance')">Cancelar</button>
            <button class="btn btn-primary" onclick="registrarAvance()">
                <i class="fa fa-save"></i> Guardar
            </button>
        </div>
    </div>
</div>

<style>
.pieza-card {
    cursor: pointer;
    border: 2px solid var(--border-color, #e2e8f0);
    border-radius: 12px;
    padding: 14px 22px;
    min-width: 130px;
    text-align: center;
    background: #fff;
    user-select: none;
    transition: border-color .18s, background .18s, box-shadow .18s, transform .12s;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
}
.pieza-card:hover {
    border-color: var(--primary, #1d4ed8);
    background: var(--primary-light, #eff6ff);
    box-shadow: 0 4px 12px rgba(29,78,216,.15);
    transform: translateY(-2px);
}
.pieza-card.selected {
    border-color: var(--primary, #1d4ed8);
    background: var(--primary-light, #eff6ff);
    box-shadow: 0 4px 12px rgba(29,78,216,.18);
}
.pieza-card .pieza-nombre {
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: .3px;
    color: var(--text-primary, #1e293b);
}
.pieza-card .pieza-count {
    font-size: .72rem;
    color: var(--text-muted, #94a3b8);
    margin-top: 4px;
}
.pieza-card.selected .pieza-nombre,
.pieza-card:hover .pieza-nombre {
    color: var(--primary, #1d4ed8);
}
</style>

<!-- Modal: Agregar Pieza -->
<div class="modal-overlay" id="modalAsignarBom">
    <div class="modal" style="max-width:680px">
        <div class="modal-header">
            <span class="modal-title">
                <i class="fa fa-puzzle-piece"></i> Agregar Pieza — <?= esc($area['area_nombre']) ?>
            </span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body" style="padding:0">
            <!-- Tarjetas de pieza -->
            <div style="padding:16px 16px 0">
                <div style="font-size:.78rem;color:var(--text-secondary);margin-bottom:10px;font-weight:600;
                            text-transform:uppercase;letter-spacing:.5px">
                    Selecciona una pieza
                </div>
                <div id="piezaCards" style="display:flex;gap:10px;flex-wrap:wrap">
                    <div class="loading"><div class="spinner"></div></div>
                </div>
            </div>
            <!-- Ítems de la pieza seleccionada -->
            <div id="bomDisponibleBody" style="max-height:320px;overflow-y:auto;margin-top:14px;
                                               border-top:1px solid var(--border-color)">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalAsignarBom')">Cancelar</button>
            <button class="btn btn-primary" id="btnAsignar" disabled onclick="asignarBomSeleccionados()">
                <i class="fa fa-check"></i> Asignar al área
            </button>
        </div>
    </div>
</div>

<script>
const WA_ID        = <?= $wa_id ?>;
const PID          = <?= $area['proyecto_id'] ?>;
const AREA_ID      = <?= $area['area_id'] ?>;
const AREA_KEYWORD = <?= json_encode($area_keyword) ?>;
const BASE         = 'api.php';

// ── Cargar datos ──────────────────────────────────────────
async function cargarArea() {
    try {
        const d = await apiGet(`${BASE}?action=area_detalle&id=${WA_ID}`);
        renderBom(d.bom);
        renderAvances(d.avances);
        renderDisenos(d.disenos || []);
    } catch(e) { Toast.error('Error al cargar datos del área.'); }
}

// ── BOM de esta área ──────────────────────────────────────
function renderBom(bom) {
    const tb = document.getElementById('bomAreaBody');
    if (!bom.length) {
        tb.innerHTML = `<tr><td colspan="7" class="text-center" style="padding:24px;color:var(--text-muted)">
            <i class="fa fa-circle-info" style="margin-right:6px"></i>
            No hay materiales con operaciones asignadas a esta área.
        </td></tr>`;
        return;
    }
    tb.innerHTML = bom.map(r => {
        const nombre    = r.prod_nombre || r.descripcion_libre || '(sin nombre)';
        const esLibre   = !r.prod_nombre;
        const cantR     = parseInt(r.cantidad_real, 10);
        const cantP     = parseInt(r.cantidad_planificada, 10);
        const terminado = cantP > 0 && cantR >= cantP;
        return `<tr>
            <td class="text-xs text-muted">${esc(r.seccion || '—')}</td>
            <td>
                ${esLibre
                    ? `<em style="color:var(--text-secondary)">${esc(nombre)}</em>`
                    : `<strong style="font-size:.85rem">${esc(nombre)}</strong>`}
                <div class="text-xs text-muted">${esc(r.unidad || '')}</div>
            </td>
            <td>
                <span style="font-size:.75rem;background:var(--bg-muted,#f1f5f9);
                             border-radius:4px;padding:2px 7px;color:var(--text-secondary)">
                    <i class="fa fa-wrench" style="opacity:.5;margin-right:3px"></i>${esc(r.operacion || '—')}
                </span>
            </td>
            <td class="text-right font-semibold">${fNum(r.cantidad_planificada)}</td>
            <td class="text-right">
                <input type="number" class="form-control text-right" style="width:76px;padding:3px 5px"
                    value="${r.cantidad_real}" step="1" min="0"
                    onchange="actualizarReal(${r.id}, this.value, ${r.costo_unitario_real})">
            </td>
            <td style="text-align:center">
                <label style="display:inline-flex;align-items:center;gap:5px;cursor:${terminado ? 'default' : 'pointer'};
                              font-size:.75rem;color:${terminado ? 'var(--success,#16a34a)' : 'var(--text-muted)'}">
                    <input type="checkbox"
                           style="width:16px;height:16px;accent-color:var(--primary,#1d4ed8);cursor:inherit"
                           ${terminado ? 'checked disabled' : ''}
                           onchange="marcarPiezaTerminada(${r.id}, '${esc(nombre).replace(/'/g,"\\'")}', '${esc(r.seccion || '').replace(/'/g,"\\'")}', this)">
                    ${terminado ? '<i class="fa fa-circle-check"></i>' : ''}
                </label>
            </td>
            <td>
                <span class="badge ${terminado ? 'badge-completado' : (cantR > 0 ? 'badge-fabricacion' : 'badge-normal')}"
                      style="font-size:.65rem">
                    ${terminado ? '<i class="fa fa-check"></i>' : (cantR > 0 ? 'En curso' : 'Pendiente')}
                </span>
            </td>
        </tr>`;
    }).join('');
}

async function marcarPiezaTerminada(bomId, nombre, seccion, chk) {
    chk.disabled = true;
    const desc = `✓ Pieza terminada: ${nombre}${seccion ? ' — Sección: ' + seccion : ''}`;
    try {
        const r = await apiPost(BASE, {
            action:      'pieza_terminar',
            bom_id:      bomId,
            proyecto_id: PID,
            area_id:     AREA_ID,
            descripcion: desc,
        });
        if (r.ok) {
            Toast.success('Pieza marcada como terminada.');
            cargarArea();
        } else {
            Toast.error(r.error || 'Error al marcar.');
            chk.checked  = false;
            chk.disabled = false;
        }
    } catch(e) {
        Toast.error('Error de conexión.');
        chk.checked  = false;
        chk.disabled = false;
    }
}

async function actualizarReal(bomId, cantReal, costoReal) {
    try {
        await apiPost(BASE, { action:'bom_actualizar', id:bomId, proyecto_id:PID,
                              cantidad_real:cantReal, costo_unitario_real:costoReal });
    } catch(e) { Toast.error('Error al actualizar.'); }
}

// ── Avances ───────────────────────────────────────────────
function renderAvances(avances) {
    const el = document.getElementById('avancesBody');
    if (!avances.length) {
        el.innerHTML = `<div style="padding:24px;text-align:center;color:var(--text-muted);font-size:.85rem">
            <i class="fa fa-inbox" style="font-size:1.6rem;margin-bottom:8px;display:block;opacity:.3"></i>
            Sin avances registrados.<br>Usa <strong>Registrar avance</strong> para comenzar.
        </div>`;
        return;
    }
    el.innerHTML = avances.map(a => `
        <div style="padding:12px 16px;border-bottom:1px solid var(--border-color)">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                <span style="font-size:.75rem;font-weight:600;color:var(--primary)">
                    <i class="fa fa-user" style="opacity:.6;margin-right:4px"></i>${esc(a.usuario_nombre || '—')}
                </span>
                <span style="font-size:.72rem;color:var(--text-muted)">${formatDate(a.fecha)}</span>
            </div>
            <div style="font-size:.84rem;line-height:1.5;color:var(--text-primary)">${esc(a.descripcion)}</div>
            <div style="margin-top:5px">
                <span class="badge badge-normal" style="font-size:.65rem">
                    <i class="fa fa-chart-simple"></i> ${a.porcentaje}% proyecto
                </span>
            </div>
        </div>
    `).join('');
}

// ── Guardar estado del área ───────────────────────────────
async function guardarEstadoArea() {
    try {
        const r = await apiPost(BASE, {
            action:         'workflow_actualizar',
            id:             WA_ID,
            proyecto_id:    PID,
            estado:         document.getElementById('wfEstado').value,
            porcentaje:     document.getElementById('wfPorcentaje').value,
            responsable_id: document.getElementById('wfResponsable').value,
            observaciones:  document.getElementById('wfObservaciones').value,
        });
        if (r.ok) {
            Toast.success('Área actualizada.');
            Modal.close('modalEditarArea');
            setTimeout(() => location.reload(), 600);
        } else Toast.error(r.error || 'Error.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

// ── Registrar avance ──────────────────────────────────────
async function registrarAvance() {
    const desc = document.getElementById('avDescripcion').value.trim();
    if (!desc) { Toast.warning('La descripción es obligatoria.'); return; }
    try {
        const r = await apiPost(BASE, {
            action:        'avance_registrar',
            proyecto_id:   PID,
            area_id:       AREA_ID,
            usuario_id:    document.getElementById('avUsuario').value,
            porcentaje:    document.getElementById('avPorcentaje').value,
            descripcion:   desc,
        });
        if (r.ok) {
            Toast.success('Avance registrado.');
            Modal.close('modalAvance');
            document.getElementById('avDescripcion').value = '';
            cargarArea();
        } else Toast.error(r.error || 'Error.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

// ── Diseños y Planos ──────────────────────────────────────
const DIS_BASE = '../../assets/uploads/disenos/';

function renderDisenos(list) {
    const el = document.getElementById('disenosBody');
    if (!el) return;
    if (!list.length) {
        el.innerHTML = `<div style="padding:22px;text-align:center;color:var(--text-muted);font-size:.82rem">
            <i class="fa fa-drafting-compass" style="font-size:1.6rem;opacity:.3;display:block;margin-bottom:8px"></i>
            Sin diseños ni planos subidos.</div>`;
        return;
    }
    el.innerHTML = list.map(d => {
        const ext  = d.filename.split('.').pop().toLowerCase();
        const isPdf = ext === 'pdf';
        const icon  = isPdf ? 'fa-file-pdf' : 'fa-file-image';
        const color = isPdf ? '#ef4444' : '#6366f1';
        const thumb = !isPdf
            ? `<img src="${DIS_BASE}${esc(d.filename)}"
                    style="width:44px;height:44px;object-fit:cover;border-radius:6px;
                           border:1px solid var(--border);flex-shrink:0">`
            : `<span style="width:44px;height:44px;border-radius:6px;background:#fef2f2;
                            border:1px solid #fecaca;display:flex;align-items:center;
                            justify-content:center;flex-shrink:0">
                   <i class="fa ${icon}" style="font-size:1.3rem;color:${color}"></i>
               </span>`;
        return `
        <div style="padding:10px 14px;border-bottom:1px solid var(--border-color);display:flex;align-items:center;gap:10px">
            ${thumb}
            <div style="flex:1;min-width:0">
                <div style="font-size:.85rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                    ${esc(d.nombre)}
                </div>
                ${d.descripcion ? `<div style="font-size:.75rem;color:var(--text-secondary);margin-top:1px">${esc(d.descripcion)}</div>` : ''}
                <div style="font-size:.72rem;color:var(--text-muted);margin-top:2px">${formatDate(d.created_at)}</div>
            </div>
            <div style="display:flex;gap:4px;flex-shrink:0">
                <a href="${DIS_BASE}${esc(d.filename)}" target="_blank"
                   class="btn btn-outline btn-xs" title="Ver / Descargar"
                   style="text-decoration:none;color:#6366f1">
                    <i class="fa fa-eye"></i>
                </a>
                <button class="btn btn-outline btn-xs" style="color:var(--danger)"
                        onclick="eliminarDiseno(${d.id},'${esc(d.nombre)}')" title="Eliminar">
                    <i class="fa fa-trash"></i>
                </button>
            </div>
        </div>`;
    }).join('');
}

let _disFiles = [];

function abrirModalDiseno() {
    _disFiles = [];
    document.getElementById('disDesc').value  = '';
    document.getElementById('disArchivo').value = '';
    disRenderFileList();
    Modal.open('modalDiseno');
}

function disDrop(e) {
    e.preventDefault();
    const dz = document.getElementById('disDropZone');
    dz.style.borderColor = '';
    dz.style.background  = '';
    disAddFiles(e.dataTransfer.files);
}

function disFileChange(input) {
    disAddFiles(input.files);
    input.value = ''; // reset para poder agregar el mismo archivo de nuevo si se quitó
}

function disAddFiles(fileList) {
    const allowed = ['jpg', 'jpeg', 'png', 'pdf'];
    Array.from(fileList).forEach(file => {
        const ext = file.name.split('.').pop().toLowerCase();
        if (!allowed.includes(ext)) {
            Toast.warning(`"${file.name}": formato no válido (JPG, PNG, PDF).`);
            return;
        }
        if (file.size > 8 * 1024 * 1024) {
            Toast.warning(`"${file.name}" supera el límite de 8 MB.`);
            return;
        }
        // Evitar duplicados por nombre+tamaño
        if (_disFiles.some(f => f.name === file.name && f.size === file.size)) return;
        _disFiles.push(file);
    });
    disRenderFileList();
}

function disRemoveFile(idx) {
    _disFiles.splice(idx, 1);
    disRenderFileList();
}

function disRenderFileList() {
    const list = document.getElementById('disFileList');
    if (!_disFiles.length) { list.innerHTML = ''; return; }
    list.innerHTML = _disFiles.map((f, i) => {
        const ext   = f.name.split('.').pop().toLowerCase();
        const isPdf = ext === 'pdf';
        const color = isPdf ? '#ef4444' : '#6366f1';
        const icon  = isPdf ? 'fa-file-pdf' : 'fa-file-image';
        const size  = f.size > 1024 * 1024
            ? (f.size / 1024 / 1024).toFixed(1) + ' MB'
            : Math.round(f.size / 1024) + ' KB';
        return `<div style="display:flex;align-items:center;gap:8px;padding:7px 10px;
                            background:#f0f4ff;border-radius:6px;border:1px solid #c7d2fe;
                            margin-bottom:5px">
            <i class="fa ${icon}" style="color:${color};font-size:1.1rem;flex-shrink:0"></i>
            <div style="flex:1;min-width:0">
                <div style="font-size:.82rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${esc(f.name)}</div>
                <div style="font-size:.71rem;color:var(--text-muted)">${size}</div>
            </div>
            <button type="button" onclick="disRemoveFile(${i})"
                    style="background:none;border:none;cursor:pointer;color:var(--text-muted);padding:2px 6px;font-size:.85rem"
                    title="Quitar">
                <i class="fa fa-times"></i>
            </button>
        </div>`;
    }).join('');
}

async function guardarDiseno() {
    const desc = document.getElementById('disDesc').value.trim();
    if (!_disFiles.length) { Toast.warning('Selecciona al menos un archivo.'); return; }

    const btn = document.querySelector('#modalDiseno .modal-footer .btn-primary');
    btn.disabled = true;
    btn.innerHTML = `<i class="fa fa-spinner fa-spin"></i> Subiendo ${_disFiles.length > 1 ? _disFiles.length + ' archivos' : ''}…`;

    try {
        const resultados = await Promise.all(_disFiles.map(file => {
            const nombre = file.name.replace(/\.[^.]+$/, ''); // nombre = filename sin extensión
            const fd = new FormData();
            fd.append('action',           'diseno_guardar');
            fd.append('proyecto_area_id', WA_ID);
            fd.append('proyecto_id',      PID);
            fd.append('area_id',          AREA_ID);
            fd.append('nombre',           nombre);
            fd.append('descripcion',      desc);
            fd.append('archivo',          file);
            return fetch(BASE, { method: 'POST', body: fd }).then(r => r.json());
        }));

        const fallidos = resultados.filter(r => !r.ok);
        if (!fallidos.length) {
            Toast.success(_disFiles.length === 1 ? 'Archivo subido.' : `${_disFiles.length} archivos subidos.`);
            Modal.close('modalDiseno');
            cargarArea();
        } else {
            Toast.error(`${fallidos.length} archivo(s) no se pudieron subir.`);
            if (fallidos.length < _disFiles.length) cargarArea();
        }
    } catch(e) {
        Toast.error('Error de conexión.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-cloud-arrow-up"></i> Subir';
    }
}

async function eliminarDiseno(id, nombre) {
    if (!confirm(`¿Eliminar "${nombre}"? El archivo se borrará permanentemente.`)) return;
    try {
        const r = await apiPost(BASE, { action: 'diseno_eliminar', id });
        if (r.ok) { Toast.success('Diseño eliminado.'); cargarArea(); }
        else Toast.error(r.error || 'Error al eliminar.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

function fNum(n) { return parseFloat(n).toLocaleString('es', {minimumFractionDigits:0,maximumFractionDigits:3}); }
function esc(s)  { const d=document.createElement('div'); d.textContent=s??''; return d.innerHTML; }

// ── Agregar Pieza ─────────────────────────────────────────
let _bomDisponible = [];
let _piezaSelId    = null;

async function abrirModalAsignarBom() {
    _bomDisponible = [];
    _piezaSelId    = null;
    document.getElementById('piezaCards').innerHTML    = '<div class="loading"><div class="spinner"></div></div>';
    document.getElementById('bomDisponibleBody').innerHTML = '';
    document.getElementById('btnAsignar').disabled     = true;
    Modal.open('modalAsignarBom');
    try {
        const d = await apiGet(`${BASE}?action=bom_disponible&proyecto_area_id=${WA_ID}&proyecto_id=${PID}`);
        _bomDisponible = d.bom || [];
        renderPiezaCards(d.piezas || []);
    } catch(e) {
        document.getElementById('piezaCards').innerHTML =
            '<div style="color:var(--danger);font-size:.85rem">Error al cargar.</div>';
    }
}

function renderPiezaCards(piezas) {
    const el = document.getElementById('piezaCards');
    if (!piezas.length) {
        el.innerHTML = '<div style="font-size:.84rem;color:var(--text-muted)">Este proyecto no tiene piezas definidas.</div>';
        return;
    }
    el.innerHTML = piezas.map(p => {
        const disponibles = _bomDisponible.filter(b => matchPieza(b, p)).length;
        return `<div class="pieza-card" data-id="${p.id}" data-nombre="${esc(p.nombre)}"
                     onclick="seleccionarPieza(${p.id}, ${JSON.stringify(p.nombre)}, this)">
            <div class="pieza-nombre">${esc(p.nombre)}</div>
            <div class="pieza-count">
                ${disponibles} ítem${disponibles !== 1 ? 's' : ''} disponible${disponibles !== 1 ? 's' : ''}
            </div>
        </div>`;
    }).join('');
}

// Coincide si el ítem pertenece a la pieza por ID o por nombre de texto
function matchPieza(b, p) {
    if (b.pieza_id !== null && b.pieza_id !== undefined && String(b.pieza_id) === String(p.id)) return true;
    if (b.pieza && p.nombre && b.pieza.trim().toLowerCase() === p.nombre.trim().toLowerCase()) return true;
    return false;
}

function seleccionarPieza(piezaId, piezaNombre, card) {
    _piezaSelId = piezaId;
    document.querySelectorAll('.pieza-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    const p     = { id: piezaId, nombre: piezaNombre };
    const items = _bomDisponible.filter(b => matchPieza(b, p));
    renderItemsPieza(items);
}

function renderItemsPieza(items) {
    const el  = document.getElementById('bomDisponibleBody');
    const btn = document.getElementById('btnAsignar');

    if (!items.length) {
        el.innerHTML = `<div style="padding:32px;text-align:center;color:var(--text-muted);font-size:.85rem">
            <i class="fa fa-circle-check" style="font-size:1.8rem;opacity:.3;display:block;margin-bottom:8px"></i>
            Todos los materiales de esta pieza ya están asignados al área.
        </div>`;
        btn.disabled = true;
        return;
    }

    btn.disabled = false;
    el.innerHTML = `
    <table style="width:100%;border-collapse:collapse">
        <thead><tr style="background:var(--bg-muted,#f1f5f9);font-size:.76rem;text-transform:uppercase;
                          letter-spacing:.4px;color:var(--text-secondary)">
            <th style="width:36px;padding:8px 12px;text-align:center">
                <input type="checkbox" id="chkTodos" checked onchange="toggleTodosBom(this)">
            </th>
            <th style="padding:8px 10px">Material</th>
            <th style="padding:8px 10px;text-align:center;width:70px">Cant.</th>
            <th style="padding:8px 10px;width:130px">Pieza</th>
            <th style="padding:8px 10px;width:150px">Operación actual</th>
        </tr></thead>
        <tbody>
        ${items.map(r => {
            const nombre = r.prod_nombre || r.descripcion_libre || '(sin nombre)';
            const op     = r.operacion || '';
            const pieza  = r.pieza || '';
            return `<tr style="border-bottom:1px solid var(--border-color)">
                <td style="padding:8px 12px;text-align:center">
                    <input type="checkbox" class="bom-dis-chk" value="${r.id}" checked>
                </td>
                <td style="padding:8px 10px">
                    <div style="font-size:.85rem;font-weight:600">${esc(nombre)}</div>
                    <div style="font-size:.72rem;color:var(--text-muted)">${esc(r.prod_codigo || '')}${r.unidad ? ' · ' + esc(r.unidad) : ''}</div>
                </td>
                <td style="padding:8px 10px;text-align:center;font-weight:700">${parseInt(r.cantidad_planificada, 10) || 0}</td>
                <td style="padding:8px 10px">
                    ${pieza
                        ? `<span style="font-size:.78rem;background:#f0fdf4;border:1px solid #bbf7d0;
                                        border-radius:4px;padding:2px 8px;color:#166534;font-weight:600">
                               ${esc(pieza)}
                           </span>`
                        : `<span style="font-size:.74rem;color:var(--text-muted)">—</span>`}
                </td>
                <td style="padding:8px 10px">
                    ${op
                        ? `<span style="font-size:.74rem;background:#f1f5f9;border-radius:4px;padding:2px 7px;color:var(--text-secondary)">
                               <i class="fa fa-wrench" style="opacity:.5;margin-right:3px"></i>${esc(op)}
                           </span>`
                        : `<span style="font-size:.74rem;color:var(--text-muted)">Sin operación</span>`}
                </td>
            </tr>`;
        }).join('')}
        </tbody>
    </table>`;
}

function toggleTodosBom(chk) {
    document.querySelectorAll('.bom-dis-chk').forEach(c => c.checked = chk.checked);
}

async function asignarBomSeleccionados() {
    const ids = [...document.querySelectorAll('.bom-dis-chk:checked')].map(c => parseInt(c.value, 10));
    if (!ids.length) { Toast.warning('Selecciona al menos un material.'); return; }
    try {
        const r = await apiPost(BASE, {
            action:           'bom_asignar_area',
            bom_ids:          ids,
            proyecto_area_id: WA_ID,
            keyword:          AREA_KEYWORD,
        });
        if (r.ok) {
            Toast.success(`${ids.length} material(es) asignado(s) al área.`);
            Modal.close('modalAsignarBom');
            cargarArea();
        } else Toast.error(r.error || 'Error al asignar.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

cargarArea();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

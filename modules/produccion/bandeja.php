<?php
// modules/produccion/bandeja.php
// Bandeja per-área (cross-proyecto): muestra partes pendientes y en proceso para que
// el responsable del área inicie y marque tareas como completadas.

require_once __DIR__ . '/../../config/database.php';
$db = getDB();

$area_id = (int)($_GET['area_id'] ?? 0);
$area    = null;
if ($area_id) {
    $stmt = $db->prepare("SELECT id, nombre, es_externa FROM areas WHERE id = :id AND activo = TRUE");
    $stmt->execute([':id' => $area_id]);
    $area = $stmt->fetch();
    if (!$area) { header('Location: bandeja.php'); exit; }
}

$page_title      = $area ? 'Bandeja — ' . $area['nombre'] : 'Bandejas de Área';
$page_breadcrumb = $area
    ? '<a href="index.php">Producción</a> <span>›</span> <a href="bandeja.php">Bandejas</a> <span>›</span> ' . htmlspecialchars($area['nombre'])
    : '<a href="index.php">Producción</a> <span>›</span> Bandejas de Área';

require_once __DIR__ . '/../../includes/header.php';

function esc_b($s) { return htmlspecialchars((string)$s, ENT_QUOTES); }
?>

<?php if (!$area): ?>

<!-- ===========================================================
     VISTA RESUMEN: lista de áreas con contadores
     =========================================================== -->
<div class="card">
    <div class="card-header">
        <span class="card-title"><i class="fa fa-clipboard-list"></i> Bandejas de Área</span>
    </div>
    <div style="padding:14px 18px;font-size:.83rem;color:var(--text-muted);border-bottom:1px solid var(--border)">
        Cada área muestra las partes pendientes y en proceso. Hace clic para entrar a la bandeja del área.
    </div>
    <div id="bandejaAreasGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;padding:18px"></div>
</div>

<?php else: ?>

<!-- ===========================================================
     VISTA BANDEJA DE UN ÁREA
     =========================================================== -->
<div style="margin-bottom:14px">
    <h2 style="margin:0;display:flex;align-items:center;gap:10px;font-size:1.1rem">
        <?php if ($area['es_externa']): ?>
            <i class="fa fa-globe" style="color:#f59e0b"></i>
        <?php else: ?>
            <i class="fa fa-industry" style="color:var(--primary)"></i>
        <?php endif; ?>
        <?= esc_b($area['nombre']) ?>
        <?php if ($area['es_externa']): ?>
            <span style="font-size:.75rem;color:#92400e;background:#fef3c7;padding:3px 8px;border-radius:10px">Servicio externo</span>
        <?php endif; ?>
    </h2>
</div>

<div class="card">
    <div class="card-header" style="gap:10px">
        <span class="card-title"><i class="fa fa-arrows-rotate" style="color:#7c3aed"></i> Procesamiento</span>
        <span id="contadorProcesamiento" style="font-size:.78rem;color:var(--text-muted)"></span>
    </div>
    <div style="padding:6px 18px;font-size:.75rem;color:var(--text-muted);border-bottom:1px solid var(--border)">
        Piezas físicas enviadas a esta área para refinarlas (pintura, tratamientos, etc.). La pieza conserva su identidad.
    </div>
    <div id="bandejaProcesamiento" style="padding:14px 18px"></div>
</div>

<div class="card" style="margin-top:14px">
    <div class="card-header" style="gap:10px">
        <span class="card-title"><i class="fa fa-fire" style="color:#dc2626"></i> En proceso</span>
        <span id="contadorEnProceso" style="font-size:.78rem;color:var(--text-muted)"></span>
    </div>
    <div style="padding:6px 18px;font-size:.75rem;color:var(--text-muted);border-bottom:1px solid var(--border)">
        Partes que combinarán materiales del BOM para crear una pieza nueva.
    </div>
    <div id="bandejaEnProceso" style="padding:14px 18px"></div>
</div>

<div class="card" style="margin-top:14px">
    <div class="card-header" style="gap:10px">
        <span class="card-title"><i class="fa fa-hourglass-half" style="color:#92400e"></i> Pendientes</span>
        <span id="contadorPendientes" style="font-size:.78rem;color:var(--text-muted)"></span>
    </div>
    <div id="bandejaPendientes" style="padding:14px 18px"></div>
</div>

<!-- Modal: completar parte -->
<div class="modal-overlay" id="modalCompletar">
    <div class="modal modal-md">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-check"></i> Marcar parte como completada</span>
            <button class="modal-close" onclick="Modal.close('modalCompletar')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="completarParteId">
            <div id="completarParteInfo" style="font-size:.85rem;color:var(--text-secondary);margin-bottom:12px"></div>

            <!-- Combinación: nombre editable de la pieza resultante -->
            <div id="completarNombrePiezaSection" style="display:none" class="form-group">
                <label class="form-label">Nombre de la pieza resultante</label>
                <input type="text" class="form-control" id="completarNombrePieza"
                       placeholder="Dejar vacío para usar el nombre de la parte">
            </div>

            <!-- Combinación: materiales BOM con cantidades reales editables -->
            <div id="completarMaterialesSection" style="display:none;margin-bottom:12px">
                <div style="font-size:.78rem;font-weight:700;color:var(--text-secondary);margin-bottom:6px;
                            text-transform:uppercase;letter-spacing:.04em">
                    <i class="fa fa-list-check"></i> Materiales consumidos
                </div>
                <div id="completarMaterialesList"></div>
            </div>

            <!-- Combinación: piezas físicas disponibles del proyecto como inputs adicionales -->
            <div id="completarPiezasSection" style="display:none;margin-bottom:12px">
                <div style="font-size:.78rem;font-weight:700;color:var(--text-secondary);margin-bottom:6px;
                            text-transform:uppercase;letter-spacing:.04em">
                    <i class="fa fa-cubes"></i> Piezas físicas a consumir
                    <span style="font-weight:400;text-transform:none;font-size:.75rem">(opcional)</span>
                </div>
                <div id="completarPiezasInputList"
                     style="font-size:.82rem;color:var(--text-muted)">Cargando...</div>
            </div>

            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Costo del proceso S/</label>
                    <input type="number" class="form-control" id="completarCosto" min="0" step="0.01" value="0">
                    <div style="font-size:.7rem;color:var(--text-muted);margin-top:3px">
                        <?= $area['es_externa'] ? 'Lo que cobra el proveedor.' : 'Mano de obra y consumibles del área.' ?>
                    </div>
                </div>
                <?php if ($area['es_externa']): ?>
                <div class="form-group">
                    <label class="form-label">Proveedor</label>
                    <input type="text" class="form-control" id="completarProveedor" placeholder="Nombre del taller externo">
                </div>
                <?php endif; ?>
            </div>

            <?php if ($area['es_externa']): ?>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Fecha de envío</label>
                    <input type="date" class="form-control" id="completarFechaEnvio">
                </div>
                <div class="form-group">
                    <label class="form-label">Fecha de recepción</label>
                    <input type="date" class="form-control" id="completarFechaRecep" value="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label">Observaciones</label>
                <textarea class="form-control" id="completarObs" rows="2" placeholder="Notas opcionales"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalCompletar')">Cancelar</button>
            <button class="btn btn-primary" onclick="confirmarCompletar()">
                <i class="fa fa-check"></i> Confirmar
            </button>
        </div>
    </div>
</div>

<?php endif; ?>

<?php
$extra_js = '<script>';
if ($area) {
    $extra_js .= 'const AREA_ID = ' . $area_id . '; const ES_EXTERNA = ' . ($area['es_externa'] ? 'true' : 'false') . ';';
}

$extra_js .= <<<'JS'

// ========================================================
// VISTA RESUMEN (sin area_id)
// ========================================================
async function cargarResumen() {
    const grid = document.getElementById('bandejaAreasGrid');
    if (!grid) return;
    try {
        const d = await apiGet('api.php?action=bandeja_areas_resumen');
        if (!d.areas || !d.areas.length) {
            grid.innerHTML = '<div style="grid-column:1/-1;color:var(--text-muted);text-align:center;padding:30px">No hay áreas activas.</div>';
            return;
        }
        grid.innerHTML = d.areas.map(a => {
            const pend = parseInt(a.pendientes, 10)    || 0;
            const enpr = parseInt(a.en_proceso, 10)    || 0;
            const proc = parseInt(a.procesamiento, 10) || 0;
            const ext  = a.es_externa ? '<i class="fa fa-globe" style="color:#f59e0b;margin-left:6px" title="Servicio externo"></i>' : '';
            return `
                <a href="bandeja.php?area_id=${a.id}"
                   style="display:block;padding:18px;border:1px solid var(--border);border-radius:8px;
                          background:#fff;text-decoration:none;color:inherit;transition:all .15s"
                   onmouseover="this.style.borderColor='var(--primary)';this.style.boxShadow='0 2px 8px rgba(0,0,0,.06)'"
                   onmouseout="this.style.borderColor='var(--border)';this.style.boxShadow='none'">
                    <div style="font-weight:600;font-size:.95rem;margin-bottom:10px">
                        ${esc(a.nombre)}${ext}
                    </div>
                    <div style="display:flex;gap:12px;font-size:.78rem;flex-wrap:wrap">
                        <div>
                            <div style="color:var(--text-muted);font-size:.68rem;text-transform:uppercase">Procesamiento</div>
                            <div style="font-weight:700;color:${proc>0?'#7c3aed':'var(--text-muted)'};font-size:1.15rem">${proc}</div>
                        </div>
                        <div>
                            <div style="color:var(--text-muted);font-size:.68rem;text-transform:uppercase">En proceso</div>
                            <div style="font-weight:700;color:${enpr>0?'#dc2626':'var(--text-muted)'};font-size:1.15rem">${enpr}</div>
                        </div>
                        <div>
                            <div style="color:var(--text-muted);font-size:.68rem;text-transform:uppercase">Pendientes</div>
                            <div style="font-weight:700;color:${pend>0?'#92400e':'var(--text-muted)'};font-size:1.15rem">${pend}</div>
                        </div>
                    </div>
                </a>`;
        }).join('');
    } catch(e) { Toast.error('Error al cargar resumen.'); }
}

// ========================================================
// VISTA BANDEJA DE ÁREA (con area_id)
// ========================================================
let _partesCache = [];
let _piezasCache = [];

async function cargarBandeja() {
    if (typeof AREA_ID === 'undefined') return;
    try {
        const d = await apiGet(`api.php?action=bandeja_listar&area_id=${AREA_ID}`);
        _partesCache = d.partes || [];
        _piezasCache = d.piezas_procesamiento || [];
        renderBandeja();
    } catch(e) { Toast.error('Error al cargar la bandeja.'); }
}

function renderBandeja() {
    const enProc = _partesCache.filter(p => p.estado_fabricacion === 'en_proceso');
    const pend   = _partesCache.filter(p => p.estado_fabricacion === 'pendiente');

    document.getElementById('contadorProcesamiento').textContent = `${_piezasCache.length} pieza${_piezasCache.length === 1 ? '' : 's'}`;
    document.getElementById('contadorEnProceso').textContent     = `${enProc.length} parte${enProc.length === 1 ? '' : 's'}`;
    document.getElementById('contadorPendientes').textContent    = `${pend.length} parte${pend.length === 1 ? '' : 's'}`;

    document.getElementById('bandejaProcesamiento').innerHTML = _piezasCache.length
        ? _piezasCache.map(renderTarjetaPieza).join('')
        : '<div style="color:var(--text-muted);padding:14px;text-align:center;font-size:.85rem">No hay piezas para procesar.</div>';

    document.getElementById('bandejaEnProceso').innerHTML = enProc.length
        ? enProc.map(renderTarjeta).join('')
        : '<div style="color:var(--text-muted);padding:14px;text-align:center;font-size:.85rem">No hay partes en proceso.</div>';

    document.getElementById('bandejaPendientes').innerHTML = pend.length
        ? pend.map(renderTarjeta).join('')
        : '<div style="color:var(--text-muted);padding:14px;text-align:center;font-size:.85rem">No hay partes pendientes.</div>';
}

function renderTarjetaPieza(pz) {
    const entrega = pz.fecha_entrega_estimada
        ? `<span style="font-size:.72rem;color:#92400e;background:#fef3c7;padding:2px 8px;border-radius:10px;margin-left:8px">
             Entrega: ${formatDate(pz.fecha_entrega_estimada)}
           </span>` : '';
    const ult = pz.ultima_transformacion
        ? `<div style="font-size:.72rem;color:var(--text-muted);margin-top:4px">
             <i class="fa fa-clock"></i> Última operación: ${formatDate(pz.ultima_transformacion.split(/[ T]/)[0])}
           </div>` : '';

    const nombreEsc = esc(pz.nombre).replace(/'/g, "\\'");
    const codigoEsc = esc(pz.proy_codigo);

    return `
        <div style="border:1px solid var(--border);border-radius:8px;padding:14px;margin-bottom:10px;background:#faf5ff">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;margin-bottom:8px">
                <div>
                    <div style="font-weight:700;font-size:.95rem">
                        <i class="fa fa-cube" style="color:#7c3aed;margin-right:6px"></i>${esc(pz.nombre)}
                    </div>
                    <div style="font-size:.78rem;color:var(--text-muted);margin-top:2px">
                        <a href="proyecto.php?id=${pz.proyecto_id}" style="color:var(--primary);text-decoration:none">${esc(pz.proy_codigo)}</a>
                        — ${esc(pz.proy_nombre)}
                        ${entrega}
                    </div>
                    ${ult}
                </div>
                <div style="display:flex;gap:6px">
                    <button class="btn btn-outline btn-sm" onclick="cancelarEnvioPieza(${pz.id})" title="Devolver a disponible">
                        <i class="fa fa-undo"></i>
                    </button>
                    <button class="btn btn-primary btn-sm" onclick="abrirCompletarPieza(${pz.id}, '${nombreEsc}', '${codigoEsc}')">
                        <i class="fa fa-check"></i> Marcar completada
                    </button>
                </div>
            </div>
        </div>`;
}

async function cancelarEnvioPieza(id) {
    if (!confirm('¿Devolver esta pieza a estado disponible (sin procesarla)?')) return;
    try {
        const r = await apiPost('api.php', { action: 'pieza_cancelar_envio', id });
        if (r.ok) { Toast.success('Pieza devuelta.'); cargarBandeja(); }
        else      { Toast.error(r.error || 'Error.'); }
    } catch(e) { Toast.error(e.message || 'Error de red.'); }
}

function renderTarjeta(parte) {
    const mats = (parte.materiales || []).map(m => {
        const nom = m.prod_nombre || m.descripcion_libre || '—';
        const cant = parseInt(m.cantidad_planificada, 10) || 0;
        const med = m.medida ? `${m.medida} ${m.medida_unidad || ''}` : '';
        const op  = m.operacion ? `<div style="font-size:.7rem;color:var(--text-muted);margin-left:14px"><i class="fa fa-wrench" style="opacity:.6"></i> ${esc(m.operacion)}</div>` : '';
        return `<li style="margin-bottom:4px">
            <span>${esc(nom)}</span>
            ${med ? `<span style="color:var(--text-muted)"> · ${esc(med)}</span>` : ''}
            <span style="color:var(--text-muted)"> × ${cant}</span>
            ${op}
        </li>`;
    }).join('');

    const enProc    = parte.estado_fabricacion === 'en_proceso';
    const inicioStr = parte.fecha_inicio ? `Iniciada ${formatDate(parte.fecha_inicio.split(/[ T]/)[0])}` : '';
    const entrega   = parte.fecha_entrega_estimada
        ? `<span style="font-size:.72rem;color:#92400e;background:#fef3c7;padding:2px 8px;border-radius:10px;margin-left:8px">
             Entrega: ${formatDate(parte.fecha_entrega_estimada)}
           </span>` : '';

    const acciones = enProc
        ? `<button class="btn btn-primary btn-sm" onclick="abrirCompletar(${parte.id}, '${esc(parte.nombre).replace(/'/g, "\\'")}', '${esc(parte.proy_codigo)}')">
              <i class="fa fa-check"></i> Marcar completada
           </button>`
        : `<button class="btn btn-outline btn-sm" onclick="iniciarParte(${parte.id})">
              <i class="fa fa-play"></i> Iniciar tarea
           </button>`;

    return `
        <div style="border:1px solid var(--border);border-radius:8px;padding:14px;margin-bottom:10px;background:${enProc?'#eff6ff':'#fff'}">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;margin-bottom:8px">
                <div>
                    <div style="font-weight:700;font-size:.95rem">${esc(parte.nombre)}</div>
                    <div style="font-size:.78rem;color:var(--text-muted);margin-top:2px">
                        <a href="proyecto.php?id=${parte.proyecto_id}" style="color:var(--primary);text-decoration:none">${esc(parte.proy_codigo)}</a>
                        — ${esc(parte.proy_nombre)} / ${esc(parte.pieza_nombre)}
                        ${entrega}
                    </div>
                    ${inicioStr ? `<div style="font-size:.72rem;color:var(--text-muted);margin-top:4px"><i class="fa fa-clock"></i> ${inicioStr}</div>` : ''}
                </div>
                <div>${acciones}</div>
            </div>
            ${mats ? `<ul style="margin:8px 0 0;padding-left:18px;font-size:.82rem;list-style-type:disc">${mats}</ul>` : ''}
        </div>`;
}

async function iniciarParte(id) {
    try {
        const r = await apiPost('api.php', { action: 'parte_iniciar', id });
        if (r.ok) { Toast.success('Tarea iniciada.'); cargarBandeja(); }
        else      { Toast.error(r.error || 'Error.'); }
    } catch(e) { Toast.error(e.message || 'Error de red.'); }
}

// _completarTipo: 'parte' (combinacion) o 'pieza' (procesamiento)
let _completarTipo     = 'parte';
let _completarMatsList = [];

const _combinacionSections = ['completarNombrePiezaSection', 'completarMaterialesSection', 'completarPiezasSection'];

function _setCombinacionSections(visible) {
    _combinacionSections.forEach(sid => {
        const el = document.getElementById(sid);
        if (el) el.style.display = visible ? '' : 'none';
    });
}

function abrirCompletar(id, nombre, codigo) {
    _completarTipo = 'parte';
    const parte = _partesCache.find(p => p.id == id);
    _completarMatsList = parte ? (parte.materiales || []) : [];

    document.getElementById('completarParteId').value = id;
    document.getElementById('completarParteInfo').innerHTML =
        `<strong>${esc(nombre)}</strong> · proyecto ${esc(codigo)} <em style="color:var(--text-muted)">(combinación)</em>`;
    document.getElementById('completarCosto').value = '0';
    const obs  = document.getElementById('completarObs');  if (obs)  obs.value  = '';
    const prov = document.getElementById('completarProveedor'); if (prov) prov.value = '';
    const nomPz = document.getElementById('completarNombrePieza'); if (nomPz) nomPz.value = '';

    _setCombinacionSections(true);
    renderMaterialesModal(_completarMatsList);

    const proyectoId = parte ? parte.proyecto_id : null;
    if (proyectoId) {
        cargarPiezasDisponiblesModal(proyectoId);
    } else {
        const el = document.getElementById('completarPiezasSection');
        if (el) el.style.display = 'none';
    }

    Modal.open('modalCompletar');
}

function abrirCompletarPieza(id, nombre, codigo) {
    _completarTipo = 'pieza';
    _completarMatsList = [];

    document.getElementById('completarParteId').value = id;
    document.getElementById('completarParteInfo').innerHTML =
        `<strong>${esc(nombre)}</strong> · proyecto ${esc(codigo)} <em style="color:var(--text-muted)">(procesamiento)</em>`;
    document.getElementById('completarCosto').value = '0';
    const obs  = document.getElementById('completarObs');  if (obs)  obs.value  = '';
    const prov = document.getElementById('completarProveedor'); if (prov) prov.value = '';

    _setCombinacionSections(false);
    Modal.open('modalCompletar');
}

function renderMaterialesModal(materiales) {
    const section = document.getElementById('completarMaterialesSection');
    const el      = document.getElementById('completarMaterialesList');
    if (!section || !el) return;

    if (!materiales.length) {
        section.style.display = 'none';
        return;
    }
    section.style.display = '';
    el.innerHTML = `
        <table style="width:100%;border-collapse:collapse;font-size:.8rem">
            <thead>
                <tr style="background:var(--bg-muted,#f8fafc)">
                    <th style="text-align:left;padding:5px 8px;border:1px solid var(--border);font-weight:600">Material</th>
                    <th style="text-align:center;padding:5px 8px;border:1px solid var(--border);font-weight:600;white-space:nowrap">Plan.</th>
                    <th style="text-align:center;padding:5px 8px;border:1px solid var(--border);font-weight:600;white-space:nowrap">Real</th>
                    <th style="text-align:center;padding:5px 8px;border:1px solid var(--border);font-weight:600;white-space:nowrap">Costo unit. S/</th>
                </tr>
            </thead>
            <tbody>
                ${materiales.map(m => {
                    const nombre  = m.prod_nombre || m.descripcion_libre || '—';
                    const med     = m.medida ? ` ${m.medida}${m.medida_unidad ? ' '+m.medida_unidad : ''}` : '';
                    const cantPlan = parseFloat(m.cantidad_planificada) || 0;
                    const cantReal = parseFloat(m.cantidad_real)  > 0 ? parseFloat(m.cantidad_real)  : cantPlan;
                    const costoR   = parseFloat(m.costo_unitario_real) || 0;
                    return `<tr>
                        <td style="padding:5px 8px;border:1px solid var(--border)">
                            ${esc(nombre)}${med ? `<span style="color:var(--text-muted)"> · ${esc(med.trim())}</span>` : ''}
                        </td>
                        <td style="padding:5px 8px;border:1px solid var(--border);text-align:center;color:var(--text-muted)">${cantPlan}</td>
                        <td style="padding:5px 8px;border:1px solid var(--border);text-align:center">
                            <input type="number" min="0" step="0.001" value="${cantReal}"
                                   data-bom-id="${m.id}" data-field="cantidad_real"
                                   style="width:70px;padding:3px 5px;border:1px solid var(--border);border-radius:4px;text-align:center;font-size:.8rem">
                        </td>
                        <td style="padding:5px 8px;border:1px solid var(--border);text-align:center">
                            <input type="number" min="0" step="0.01" value="${costoR}"
                                   data-bom-id="${m.id}" data-field="costo_unitario_real"
                                   style="width:75px;padding:3px 5px;border:1px solid var(--border);border-radius:4px;text-align:center;font-size:.8rem">
                        </td>
                    </tr>`;
                }).join('')}
            </tbody>
        </table>`;
}

async function cargarPiezasDisponiblesModal(proyectoId) {
    const section = document.getElementById('completarPiezasSection');
    const el      = document.getElementById('completarPiezasInputList');
    if (!section || !el) return;
    el.innerHTML = '<span style="color:var(--text-muted);font-size:.82rem">Cargando...</span>';
    try {
        const d = await apiGet(`api.php?action=piezas_disponibles_proyecto&proyecto_id=${proyectoId}`);
        if (!d.piezas || !d.piezas.length) {
            section.style.display = 'none';
            return;
        }
        section.style.display = '';
        el.innerHTML = d.piezas.map(pz => `
            <label style="display:flex;align-items:center;gap:8px;font-size:.83rem;padding:3px 0;cursor:pointer">
                <input type="checkbox" data-pieza-input-id="${pz.id}"
                       style="accent-color:var(--primary);width:14px;height:14px">
                <span>${esc(pz.nombre)}</span>
                <span style="font-size:.72rem;color:var(--text-muted)">#${pz.id}</span>
            </label>`).join('');
    } catch(e) {
        section.style.display = 'none';
    }
}

async function confirmarCompletar() {
    const id    = document.getElementById('completarParteId').value;
    const costo = document.getElementById('completarCosto').value;
    const obs   = document.getElementById('completarObs').value;
    const action  = _completarTipo === 'pieza' ? 'pieza_completar_procesamiento' : 'parte_completar';
    const payload = { action, id, costo_proceso: costo, observaciones: obs };

    if (typeof ES_EXTERNA !== 'undefined' && ES_EXTERNA) {
        payload.proveedor               = document.getElementById('completarProveedor')?.value  || '';
        payload.fecha_envio_externo     = document.getElementById('completarFechaEnvio')?.value || '';
        payload.fecha_recepcion_externo = document.getElementById('completarFechaRecep')?.value || '';
    }

    if (_completarTipo === 'parte') {
        // Nombre opcional para la pieza resultante
        const nombrePieza = document.getElementById('completarNombrePieza')?.value?.trim() || '';
        if (nombrePieza) payload.nombre_pieza = nombrePieza;

        // Cantidades reales por ítem BOM
        const cantidadesReales = [];
        document.querySelectorAll('[data-bom-id][data-field="cantidad_real"]').forEach(inp => {
            const bomId  = parseInt(inp.dataset.bomId);
            const cantR  = parseFloat(inp.value) || 0;
            const costoEl = document.querySelector(`[data-bom-id="${bomId}"][data-field="costo_unitario_real"]`);
            const costoR  = costoEl ? (parseFloat(costoEl.value) || 0) : 0;
            cantidadesReales.push({ bom_id: bomId, cantidad_real: cantR, costo_unitario_real: costoR });
        });
        if (cantidadesReales.length) payload.cantidades_reales = cantidadesReales;

        // Piezas físicas adicionales seleccionadas
        const piezaInputIds = [];
        document.querySelectorAll('[data-pieza-input-id]:checked').forEach(chk => {
            piezaInputIds.push(parseInt(chk.dataset.piezaInputId));
        });
        if (piezaInputIds.length) payload.pieza_inputs = piezaInputIds;
    }

    try {
        const r = await apiPost('api.php', payload);
        if (r.ok) {
            Toast.success(_completarTipo === 'pieza' ? 'Procesamiento completado.' : 'Parte completada. Pieza creada.');
            Modal.close('modalCompletar');
            cargarBandeja();
        } else { Toast.error(r.error || 'Error.'); }
    } catch(e) { Toast.error(e.message || 'Error de red.'); }
}

// Init
if (typeof AREA_ID !== 'undefined') cargarBandeja();
else cargarResumen();
JS;

$extra_js .= '</script>';

require_once __DIR__ . '/../../includes/footer.php';

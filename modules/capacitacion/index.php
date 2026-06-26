<?php
$page_title      = 'Capacitación';
$page_breadcrumb = 'Sistema / Capacitación';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="tabs" id="mainTabs">
    <button class="tab-btn active" data-tab="materiales">Materiales</button>
    <button class="tab-btn" data-tab="progreso">Progreso por Material</button>
    <button class="tab-btn" data-tab="resumen">Resumen</button>
</div>

<!-- ── RESUMEN ─────────────────────────────────────────────────────── -->
<div class="tab-content" id="tab-resumen">
    <div class="kpi-grid">
        <div class="kpi-card"><div class="kpi-label">Total Materiales</div><div class="kpi-value" id="k-total">—</div></div>
        <div class="kpi-card"><div class="kpi-label">Materiales con Examen</div><div class="kpi-value" id="k-examen" style="color:#6366f1">—</div></div>
        <div class="kpi-card"><div class="kpi-label">Usuarios con Progreso</div><div class="kpi-value" id="k-usuarios">—</div></div>
        <div class="kpi-card"><div class="kpi-label">Completados (total)</div><div class="kpi-value" id="k-completados" style="color:#22c55e">—</div></div>
    </div>
    <div class="card" style="margin-top:4px">
        <div class="card-header">Materiales por Tipo</div>
        <div class="card-body" id="porTipo" style="padding:16px"></div>
    </div>
</div>

<!-- ── MATERIALES ──────────────────────────────────────────────────── -->
<div class="tab-content active" id="tab-materiales">
    <div class="toolbar">
        <div class="toolbar-left">
            <input type="text" class="form-control" id="matSearch" placeholder="Buscar material…" style="width:220px">
            <select class="form-control" id="matTipo" style="width:130px">
                <option value="">Todos los tipos</option>
                <option value="pdf">PDF</option>
                <option value="video">Video</option>
                <option value="texto">Texto</option>
                <option value="enlace">Enlace</option>
            </select>
            <select class="form-control" id="matCategoria" style="width:170px">
                <option value="">Todas las categorías</option>
            </select>
            <select class="form-control" id="matRol" style="width:150px">
                <option value="">Todos los roles</option>
            </select>
            <select class="form-control" id="matArea" style="width:150px">
                <option value="">Todas las áreas</option>
            </select>
        </div>
        <div class="toolbar-right">
            <button class="btn btn-primary" onclick="abrirModalMaterial()">
                <i class="fa fa-plus"></i> Nuevo Material
            </button>
        </div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(310px,1fr));gap:16px" id="gridMateriales">
        <p class="text-secondary" style="grid-column:1/-1;text-align:center;padding:40px">Cargando materiales…</p>
    </div>
</div>

<!-- ── PROGRESO ────────────────────────────────────────────────────── -->
<div class="tab-content" id="tab-progreso">
    <div class="toolbar">
        <div class="toolbar-left">
            <select class="form-control" id="progresoMaterial" style="width:340px">
                <option value="">— Seleccionar material —</option>
            </select>
        </div>
        <div class="toolbar-right">
            <button class="btn btn-primary" onclick="cargarProgreso()">
                <i class="fa fa-search"></i> Ver progreso
            </button>
        </div>
    </div>
    <div class="card">
        <table class="table" id="tablaProgreso">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Email</th>
                    <th>Estado</th>
                    <th>Fecha Completado</th>
                    <th>Examen</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="6" class="text-secondary text-center" style="padding:24px">Seleccione un material para ver el progreso</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ── MODAL: MATERIAL ─────────────────────────────────────────────── -->
<div class="modal-overlay" id="modalMaterial">
    <div class="modal" style="max-width:620px">
        <div class="modal-header">
            <h3 class="modal-title" id="modalMatTitle">Nuevo Material</h3>
            <button class="modal-close" onclick="Modal.close('modalMaterial')">×</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="mat_id">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div class="form-group" style="grid-column:1/3">
                    <label class="form-label">Título *</label>
                    <input type="text" class="form-control" id="mat_titulo">
                </div>
                <div class="form-group">
                    <label class="form-label">Tipo</label>
                    <select class="form-control" id="mat_tipo" onchange="actualizarCamposTipo()">
                        <option value="texto">Texto</option>
                        <option value="pdf">PDF</option>
                        <option value="video">Video</option>
                        <option value="enlace">Enlace</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Categoría</label>
                    <input type="text" class="form-control" id="mat_categoria" list="matCatList" placeholder="Ej: Seguridad Industrial">
                    <datalist id="matCatList"></datalist>
                </div>
                <div class="form-group" style="grid-column:1/3" id="wrap_url">
                    <label class="form-label" id="label_url">URL del recurso</label>
                    <input type="url" class="form-control" id="mat_url" placeholder="https://…">
                </div>
                <div class="form-group" id="wrap_pdf_upload" style="grid-column:1/3;display:none">
                    <label class="form-label">Subir archivo PDF</label>
                    <div style="display:flex;align-items:center;gap:8px">
                        <input type="file" id="matPdfInput" accept=".pdf" style="display:none" onchange="matPdfChange(this)">
                        <button type="button" class="btn btn-secondary" style="font-size:0.78rem;padding:4px 10px" onclick="document.getElementById('matPdfInput').click()">
                            <i class="fa fa-upload"></i> Seleccionar PDF
                        </button>
                        <span id="matPdfName" style="font-size:0.82rem;color:#475569">—</span>
                        <button type="button" id="matPdfQuit" onclick="matPdfClear()" class="btn btn-secondary" style="display:none;font-size:0.78rem;padding:4px 8px"><i class="fa fa-times"></i></button>
                    </div>
                    <div style="font-size:0.75rem;color:#94a3b8;margin-top:4px">Si subes un archivo se ignora la URL. Máx. 20MB.</div>
                </div>
                <div class="form-group" style="grid-column:1/3">
                    <label class="form-label">Descripción / Contenido</label>
                    <textarea class="form-control" id="mat_desc" rows="4" placeholder="Descripción o contenido de texto del material…"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Rol (vacío = todos)</label>
                    <select class="form-control" id="mat_rol_id">
                        <option value="">— Todos los roles —</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Área (vacío = todas)</label>
                    <select class="form-control" id="mat_area_id">
                        <option value="">— Todas las áreas —</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Orden</label>
                    <input type="number" class="form-control" id="mat_orden" min="0" value="0">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('modalMaterial')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarMaterial()">Guardar</button>
        </div>
    </div>
</div>

<!-- ── MODAL: VER MATERIAL ─────────────────────────────────────────── -->
<div class="modal-overlay" id="modalVerMaterial">
    <div class="modal" style="max-width:760px">
        <div class="modal-header">
            <h3 class="modal-title" id="modalVerTitle">—</h3>
            <button class="modal-close" onclick="Modal.close('modalVerMaterial')">×</button>
        </div>
        <div class="modal-body" id="modalVerBody"></div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('modalVerMaterial')">Cerrar</button>
        </div>
    </div>
</div>

<!-- ── MODAL: GESTIONAR EXAMEN (ADMIN) ────────────────────────────── -->
<div class="modal-overlay" id="modalExamenAdmin">
    <div class="modal" style="max-width:740px;max-height:90vh;display:flex;flex-direction:column">
        <div class="modal-header">
            <h3 class="modal-title"><i class="fa fa-clipboard-list" style="color:#6366f1"></i> Examen: <span id="exAdminMatTitulo">—</span></h3>
            <button class="modal-close" onclick="Modal.close('modalExamenAdmin')">×</button>
        </div>
        <div class="modal-body" id="exAdminBody" style="flex:1;overflow-y:auto;padding:20px">
            <p class="text-secondary text-center" style="padding:32px">Cargando…</p>
        </div>
        <div class="modal-footer">
            <button class="btn" id="btnEliminarExamen" onclick="eliminarExamen()"
                style="background:#fee2e2;color:#dc2626;border:1px solid #fca5a5;display:none">
                <i class="fa fa-trash"></i> Eliminar examen
            </button>
            <button class="btn btn-secondary" onclick="Modal.close('modalExamenAdmin')">Cerrar</button>
        </div>
    </div>
</div>

<!-- ── MODAL: TOMAR EXAMEN ─────────────────────────────────────────── -->
<div class="modal-overlay" id="modalTomarExamen">
    <div class="modal" style="max-width:700px;max-height:92vh;display:flex;flex-direction:column">
        <div class="modal-header">
            <h3 class="modal-title" id="exTomarTitulo">—</h3>
            <button class="modal-close" onclick="Modal.close('modalTomarExamen')">×</button>
        </div>
        <div class="modal-body" id="exTomarBody" style="flex:1;overflow-y:auto;padding:20px">
            <p class="text-secondary text-center" style="padding:32px">Cargando examen…</p>
        </div>
        <div class="modal-footer" id="exTomarFooter">
            <button class="btn btn-secondary" onclick="Modal.close('modalTomarExamen')">Cancelar</button>
            <button class="btn btn-primary" id="btnSubmitExamen" onclick="submitExamen()">
                <i class="fa fa-check"></i> Enviar respuestas
            </button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
const BASE        = 'api.php';
const CAP_PDF_BASE = '../../assets/uploads/capacitacion/';

let combos         = { roles: [], areas: [], usuarios: [] };
let todosMateriales = [];
let cacheGrilla    = [];   // materiales visibles en el grid
let matPdfFile     = null;
let matPdfExisting = null;
let exAdminMatId   = null;
let exAdminEid     = null;
let exTomarData    = null;
let exTomarMatId   = null;

// ── HELPERS ──────────────────────────────────────────────────────────
function debounce(fn, d) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), d); }; }

function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── INIT ─────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    initTabs();
    cargarDashboard();
    cargarCombos().then(() => cargarMateriales());
    ['matSearch','matTipo','matCategoria','matRol','matArea'].forEach(id => {
        document.getElementById(id).addEventListener('input',  debounce(cargarMateriales, 350));
        document.getElementById(id).addEventListener('change', cargarMateriales);
    });
});

// ── DASHBOARD ────────────────────────────────────────────────────────
async function cargarDashboard() {
    try {
        const d = await apiGet(BASE + '?action=dashboard');
        if (!d.ok) return;
        document.getElementById('k-total').textContent       = d.total;
        document.getElementById('k-examen').textContent      = d.materiales_con_examen;
        document.getElementById('k-usuarios').textContent    = d.usuarios_con_progreso;
        document.getElementById('k-completados').textContent = d.completados_total;

        const tiposIcon  = { pdf:'fa-file-pdf', video:'fa-play-circle', texto:'fa-file-lines', enlace:'fa-link' };
        const tiposColor = { pdf:'#ef4444', video:'#f97316', texto:'#0ea5e9', enlace:'#22c55e' };
        const total = d.por_tipo.reduce((s, t) => s + parseInt(t.cnt), 0);
        document.getElementById('porTipo').innerHTML = d.por_tipo.map(t => {
            const pct = total > 0 ? Math.round(parseInt(t.cnt) / total * 100) : 0;
            return `<div style="margin-bottom:12px;display:flex;align-items:center;gap:12px">
                <i class="fa ${tiposIcon[t.tipo]||'fa-file'}" style="color:${tiposColor[t.tipo]||'#64748b'};width:20px;text-align:center"></i>
                <div style="flex:1">
                    <div style="display:flex;justify-content:space-between;font-size:0.85rem;margin-bottom:3px">
                        <span>${escHtml(t.tipo.toUpperCase())}</span><span>${t.cnt} materiales</span>
                    </div>
                    <div style="background:#e2e8f0;border-radius:4px;height:8px">
                        <div style="background:${tiposColor[t.tipo]||'#64748b'};width:${pct}%;height:8px;border-radius:4px"></div>
                    </div>
                </div>
            </div>`;
        }).join('') || '<p class="text-secondary" style="font-size:0.85rem">Sin materiales registrados</p>';
    } catch(e) { console.error('cargarDashboard:', e); }
}

// ── COMBOS ───────────────────────────────────────────────────────────
async function cargarCombos() {
    try {
        const [c, cats] = await Promise.all([
            apiGet(BASE + '?action=combos'),
            apiGet(BASE + '?action=categorias_lista'),
        ]);
        if (c.ok) combos = c;

        const optsRol  = '<option value="">Todos los roles</option>'   + (c.roles ||[]).map(r => `<option value="${r.id}">${escHtml(r.nombre)}</option>`).join('');
        const optsArea = '<option value="">Todas las áreas</option>'   + (c.areas ||[]).map(a => `<option value="${a.id}">${escHtml(a.nombre)}</option>`).join('');
        document.getElementById('matRol').innerHTML  = optsRol;
        document.getElementById('matArea').innerHTML = optsArea;

        if (cats.ok) {
            document.getElementById('matCategoria').innerHTML = '<option value="">Todas las categorías</option>' +
                cats.categorias.map(c => `<option value="${escHtml(c)}">${escHtml(c)}</option>`).join('');
            document.getElementById('matCatList').innerHTML = cats.categorias.map(c => `<option value="${escHtml(c)}">`).join('');
        }

        document.getElementById('mat_rol_id').innerHTML  = '<option value="">— Todos los roles —</option>'  + (c.roles ||[]).map(r => `<option value="${r.id}">${escHtml(r.nombre)}</option>`).join('');
        document.getElementById('mat_area_id').innerHTML = '<option value="">— Todas las áreas —</option>' + (c.areas ||[]).map(a => `<option value="${a.id}">${escHtml(a.nombre)}</option>`).join('');

        const d = await apiGet(BASE + '?action=materiales_listar');
        todosMateriales = d.materiales || [];
        document.getElementById('progresoMaterial').innerHTML = '<option value="">— Seleccionar material —</option>' +
            todosMateriales.map(m => `<option value="${m.id}">${escHtml(m.titulo)}</option>`).join('');
    } catch(e) { console.error('cargarCombos:', e); }
}

// ── GRID MATERIALES ───────────────────────────────────────────────────
async function cargarMateriales() {
    try {
        const q    = document.getElementById('matSearch').value;
        const tipo = document.getElementById('matTipo').value;
        const cat  = document.getElementById('matCategoria').value;
        const rol  = document.getElementById('matRol').value;
        const area = document.getElementById('matArea').value;
        const d = await apiGet(`${BASE}?action=materiales_listar&q=${encodeURIComponent(q)}&tipo=${tipo}&categoria=${encodeURIComponent(cat)}&rol_id=${rol}&area_id=${area}`);
        if (!d.ok) return;
        cacheGrilla = d.materiales || [];

        const tiposIcon  = { pdf:'fa-file-pdf', video:'fa-play-circle', texto:'fa-file-lines', enlace:'fa-link' };
        const tiposColor = { pdf:'#ef4444', video:'#f97316', texto:'#0ea5e9', enlace:'#22c55e' };

        const grid = document.getElementById('gridMateriales');
        if (!cacheGrilla.length) {
            grid.innerHTML = '<p class="text-secondary" style="grid-column:1/-1;text-align:center;padding:40px">Sin materiales</p>';
            return;
        }
        grid.innerHTML = cacheGrilla.map(m => {
            const progPct = m.total_progreso > 0 ? Math.round(m.total_completados / m.total_progreso * 100) : 0;
            const tieneExamen = !!m.examen_id;
            return `<div class="card" style="padding:0;overflow:hidden">
                <div style="padding:12px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px">
                    <i class="fa ${tiposIcon[m.tipo]||'fa-file'}" style="color:${tiposColor[m.tipo]||'#64748b'};font-size:1.2rem;width:22px;text-align:center"></i>
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${escHtml(m.titulo)}</div>
                        <div style="font-size:0.77rem;color:#64748b;display:flex;gap:6px;align-items:center">
                            <span>${escHtml(m.categoria || '')}</span>
                            ${m.rol_nombre ? `<span>· ${escHtml(m.rol_nombre)}</span>` : ''}
                            ${tieneExamen ? '<span class="badge" style="background:#ede9fe;color:#6d28d9;font-size:0.68rem;padding:1px 5px;border-radius:3px"><i class="fa fa-clipboard-check"></i> Examen</span>' : ''}
                        </div>
                    </div>
                </div>
                ${m.descripcion ? `<div style="padding:10px 16px;font-size:0.83rem;color:#475569;line-height:1.5;max-height:56px;overflow:hidden">${escHtml(m.descripcion)}</div>` : ''}
                <div style="padding:8px 16px;background:#f8fafc;font-size:0.78rem;color:#64748b;display:flex;align-items:center;gap:8px">
                    <span><i class="fa fa-users"></i> ${m.total_completados}/${m.total_progreso} completaron</span>
                    ${m.total_progreso > 0 ? `<div style="background:#e2e8f0;border-radius:4px;height:6px;flex:1">
                        <div style="background:${tiposColor[m.tipo]||'var(--primary)'};width:${progPct}%;height:6px;border-radius:4px"></div>
                    </div>` : ''}
                </div>
                <div style="padding:8px 12px;border-top:1px solid var(--border);display:flex;gap:6px">
                    <button class="btn btn-secondary" style="flex:1;padding:4px;font-size:0.78rem" onclick="verMaterial(${m.id})">
                        <i class="fa fa-eye"></i> Ver
                    </button>
                    <button class="btn btn-secondary" title="Gestionar examen" style="padding:4px 8px;font-size:0.78rem;color:#6366f1" onclick="abrirModalExamenAdmin(${m.id})">
                        <i class="fa fa-clipboard-list"></i>
                    </button>
                    <button class="btn btn-secondary" style="padding:4px 8px;font-size:0.78rem" onclick="editarMaterial(${m.id})">
                        <i class="fa fa-edit"></i>
                    </button>
                    <button class="btn btn-secondary" style="padding:4px 8px;font-size:0.78rem;color:var(--danger)" onclick="eliminarMaterial(${m.id})">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </div>`;
        }).join('');
    } catch(e) { console.error('cargarMateriales:', e); }
}

// ── TIPO DE MATERIAL ─────────────────────────────────────────────────
function actualizarCamposTipo() {
    const tipo = document.getElementById('mat_tipo').value;
    const labelMap = { pdf:'URL del PDF (opcional si subes archivo)', video:'URL del video (YouTube, Vimeo…)', enlace:'URL del enlace externo' };
    document.getElementById('wrap_url').style.display     = tipo === 'texto' ? 'none' : '';
    document.getElementById('wrap_pdf_upload').style.display = tipo === 'pdf' ? '' : 'none';
    if (labelMap[tipo]) document.getElementById('label_url').textContent = labelMap[tipo];
}

// ── MODAL MATERIAL: NUEVO / EDITAR ────────────────────────────────────
function abrirModalMaterial() {
    document.getElementById('mat_id').value = '';
    document.getElementById('mat_titulo').value = '';
    document.getElementById('mat_tipo').value = 'texto';
    document.getElementById('mat_categoria').value = '';
    document.getElementById('mat_url').value = '';
    document.getElementById('mat_desc').value = '';
    document.getElementById('mat_rol_id').value = '';
    document.getElementById('mat_area_id').value = '';
    document.getElementById('mat_orden').value = '0';
    document.getElementById('modalMatTitle').textContent = 'Nuevo Material';
    matPdfClear();
    actualizarCamposTipo();
    Modal.open('modalMaterial');
}

async function editarMaterial(id) {
    try {
        const d = await apiGet(`${BASE}?action=material_obtener&id=${id}`);
        if (!d.ok) return;
        const m = d.material;
        document.getElementById('mat_id').value        = m.id;
        document.getElementById('mat_titulo').value    = m.titulo;
        document.getElementById('mat_tipo').value      = m.tipo || 'texto';
        document.getElementById('mat_categoria').value = m.categoria || '';
        const isLocalPdf = m.tipo === 'pdf' && m.archivo && !m.archivo.match(/^https?:/);
        if (isLocalPdf) {
            document.getElementById('mat_url').value = '';
            matPdfFile = null; matPdfExisting = m.archivo;
            document.getElementById('matPdfName').textContent = m.archivo + ' (actual)';
            document.getElementById('matPdfQuit').style.display = '';
            document.getElementById('matPdfInput').value = '';
        } else {
            document.getElementById('mat_url').value = m.url || m.archivo || '';
            matPdfClear();
        }
        document.getElementById('mat_desc').value     = m.descripcion || '';
        document.getElementById('mat_rol_id').value   = m.rol_id || '';
        document.getElementById('mat_area_id').value  = m.area_id || '';
        document.getElementById('mat_orden').value    = m.orden || 0;
        document.getElementById('modalMatTitle').textContent = 'Editar Material';
        actualizarCamposTipo();
        Modal.open('modalMaterial');
    } catch(e) { Toast.error('Error al cargar material'); }
}

async function guardarMaterial() {
    const titulo = document.getElementById('mat_titulo').value.trim();
    if (!titulo) { Toast.warning('El título es obligatorio'); return; }
    const tipo = document.getElementById('mat_tipo').value;
    const url  = document.getElementById('mat_url').value.trim();
    let bodyUrl = null, bodyArchivo = null;
    if (tipo === 'pdf') {
        if (matPdfFile)          { bodyUrl = null; bodyArchivo = null; }
        else if (matPdfExisting) { bodyUrl = null; bodyArchivo = matPdfExisting; }
        else                     { bodyUrl = null; bodyArchivo = url || null; }
    } else if (tipo !== 'texto') {
        bodyUrl = url || null;
    }
    try {
        const r = await apiPost(BASE, {
            action: 'material_guardar',
            id:          document.getElementById('mat_id').value || null,
            titulo, tipo,
            categoria:   document.getElementById('mat_categoria').value,
            url:         bodyUrl,
            archivo:     bodyArchivo,
            descripcion: document.getElementById('mat_desc').value,
            rol_id:      document.getElementById('mat_rol_id').value || null,
            area_id:     document.getElementById('mat_area_id').value || null,
            orden:       document.getElementById('mat_orden').value,
        });
        if (!r.ok) { Toast.error(r.error || 'Error'); return; }
        if (tipo === 'pdf' && matPdfFile) {
            const fd = new FormData();
            fd.append('id', r.id);
            fd.append('pdf', matPdfFile);
            const up = await fetch(BASE + '?action=mat_pdf_upload', { method: 'POST', body: fd })
                .then(x => x.json()).catch(() => ({}));
            if (!up.ok) Toast.warning('Material guardado, pero error al subir PDF');
        }
        matPdfClear();
        Toast.success('Material guardado');
        Modal.close('modalMaterial');
        cargarMateriales();
        cargarDashboard();
        cargarCombos();
    } catch(e) { Toast.error('Error al guardar material'); }
}

async function eliminarMaterial(id) {
    if (!confirm('¿Eliminar este material de capacitación?')) return;
    try {
        const r = await apiPost(BASE, { action: 'material_eliminar', id });
        if (r.ok) { Toast.success('Material eliminado'); cargarMateriales(); cargarDashboard(); cargarCombos(); }
        else Toast.error(r.error || 'Error');
    } catch(e) { Toast.error('Error al eliminar'); }
}

// ── PDF upload ────────────────────────────────────────────────────────
function matPdfChange(input) {
    const f = input.files[0];
    if (!f) return;
    if (f.size > 20 * 1024 * 1024) { Toast.warning('El archivo supera los 20MB'); input.value = ''; return; }
    matPdfFile = f; matPdfExisting = null;
    document.getElementById('matPdfName').textContent = f.name;
    document.getElementById('matPdfQuit').style.display = '';
    document.getElementById('mat_url').value = '';
}
function matPdfClear() {
    matPdfFile = null; matPdfExisting = null;
    document.getElementById('matPdfName').textContent = '—';
    document.getElementById('matPdfQuit').style.display = 'none';
    document.getElementById('matPdfInput').value = '';
}

// ── VER MATERIAL ──────────────────────────────────────────────────────
async function verMaterial(id) {
    try {
        const d = await apiGet(`${BASE}?action=material_obtener&id=${id}`);
        if (!d.ok) return;
        const m = d.material;
        document.getElementById('modalVerTitle').textContent = m.titulo;

        let contenido = '';
        if (m.tipo === 'video' && m.url) {
            const ytMatch = m.url.match(/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/))([^&?/\s]{11})/);
            if (ytMatch) {
                contenido = `<div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;border-radius:6px;background:#000">
                    <iframe src="https://www.youtube.com/embed/${ytMatch[1]}?rel=0"
                        style="position:absolute;top:0;left:0;width:100%;height:100%;border:none"
                        allowfullscreen allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture">
                    </iframe>
                </div>`;
            } else {
                contenido = `<a href="${escHtml(m.url)}" target="_blank" class="btn btn-primary">
                    <i class="fa fa-play"></i> Ver Video
                </a>`;
            }
        } else if (m.tipo === 'enlace' && m.url) {
            contenido = `<a href="${escHtml(m.url)}" target="_blank" class="btn btn-primary">
                <i class="fa fa-external-link-alt"></i> Abrir enlace
            </a>`;
        } else if (m.tipo === 'pdf' && (m.url || m.archivo)) {
            let src = m.url || m.archivo;
            if (src && !src.match(/^https?:/)) src = CAP_PDF_BASE + src;
            contenido = `
                <div style="margin-bottom:10px">
                    <a href="${escHtml(src)}" target="_blank" class="btn btn-secondary" style="font-size:0.82rem;padding:4px 12px">
                        <i class="fa fa-external-link-alt"></i> Abrir en nueva pestaña
                    </a>
                </div>
                <embed src="${escHtml(src)}" type="application/pdf" width="100%" height="420"
                    style="border-radius:6px;border:1px solid var(--border);display:block">
                <p style="font-size:0.75rem;color:#94a3b8;margin-top:6px">Si el PDF no carga, use el botón de arriba.</p>`;
        } else if (m.descripcion) {
            contenido = `<div style="font-size:0.9rem;line-height:1.75;white-space:pre-wrap;background:#f8fafc;padding:16px;border-radius:6px;border:1px solid var(--border)">${escHtml(m.descripcion)}</div>`;
        } else {
            contenido = '<p class="text-secondary">Sin contenido disponible.</p>';
        }

        // Sección examen
        const examenSec = m.examen_id ? `
            <div style="margin-top:20px;padding:14px 16px;background:#ede9fe;border-radius:8px;border:1px solid #c4b5fd;display:flex;align-items:center;gap:12px">
                <i class="fa fa-clipboard-list" style="color:#6d28d9;font-size:1.4rem"></i>
                <div style="flex:1">
                    <div style="font-weight:600;font-size:0.9rem;color:#4c1d95">Este material tiene un examen adjunto</div>
                    <div style="font-size:0.82rem;color:#6d28d9">Tómalo para evaluar tu comprensión y marcar el material como completado.</div>
                </div>
                <button class="btn btn-primary" style="background:#6d28d9;border-color:#6d28d9;white-space:nowrap"
                    onclick="Modal.close('modalVerMaterial'); abrirModalTomarExamen(${m.id})">
                    <i class="fa fa-pencil-alt"></i> Tomar examen
                </button>
            </div>` : '';

        document.getElementById('modalVerBody').innerHTML = `
            <div style="font-size:0.82rem;color:#64748b;margin-bottom:14px;display:flex;gap:6px;flex-wrap:wrap">
                ${m.categoria  ? `<span class="badge badge-secondary">${escHtml(m.categoria)}</span>` : ''}
                ${m.rol_nombre ? `<span class="badge badge-primary">${escHtml(m.rol_nombre)}</span>` : ''}
                ${m.area_nombre? `<span class="badge badge-secondary">${escHtml(m.area_nombre)}</span>` : ''}
            </div>
            ${m.descripcion && m.tipo !== 'texto' ? `<p style="font-size:0.87rem;margin-bottom:16px;color:#475569">${escHtml(m.descripcion)}</p>` : ''}
            ${contenido}
            ${examenSec}`;
        Modal.open('modalVerMaterial');
    } catch(e) { Toast.error('Error al cargar material'); }
}

// ── PROGRESO ─────────────────────────────────────────────────────────
async function marcarProgreso(usuario_id, material_id, completado) {
    try {
        const r = await apiPost(BASE, { action: 'progreso_marcar', usuario_id, material_id, completado });
        if (r.ok) cargarProgreso();
        else Toast.error(r.error || 'Error');
    } catch(e) { Toast.error('Error al guardar progreso'); }
}

async function cargarProgreso() {
    const matId = document.getElementById('progresoMaterial').value;
    if (!matId) { Toast.warning('Seleccione un material'); return; }
    try {
        const d = await apiGet(`${BASE}?action=progreso_listar&material_id=${matId}`);
        if (!d.ok) return;
        const tbody = document.querySelector('#tablaProgreso tbody');
        tbody.innerHTML = d.progresos.map(p => {
            const done = p.completado === true || p.completado === 't' || p.completado == 1;
            let examenHtml = '—';
            if (p.examen_puntaje !== null && p.examen_puntaje !== undefined) {
                const ap = p.examen_aprobado === true || p.examen_aprobado === 't';
                examenHtml = `<span class="badge ${ap?'badge-completado':'badge-retrasado'}" style="font-size:0.75rem">${p.examen_puntaje}% ${ap?'✓':'✗'}</span>`;
            }
            return `<tr>
                <td><strong>${escHtml(p.usuario_nombre)}</strong></td>
                <td style="font-size:0.85rem">${escHtml(p.email || '—')}</td>
                <td><span class="badge ${done?'badge-completado':'badge-secondary'}">${done?'Completado':'Pendiente'}</span></td>
                <td style="font-size:0.85rem">${p.fecha_completado ? formatDate(p.fecha_completado) : '—'}</td>
                <td>${examenHtml}</td>
                <td>
                    <button class="btn btn-${done?'secondary':'primary'}"
                        style="padding:3px 10px;font-size:0.78rem${done?';color:var(--danger)':''}"
                        onclick="marcarProgreso(${p.usuario_id},${matId},${!done})">
                        <i class="fa fa-${done?'times':'check'}"></i> ${done?'Desmarcar':'Completar'}
                    </button>
                </td>
            </tr>`;
        }).join('') || `<tr><td colspan="6" class="text-secondary text-center" style="padding:20px">Sin usuarios activos</td></tr>`;
    } catch(e) { Toast.error('Error al cargar progreso'); }
}

// ══════════════════════════════════════════════════════════════════════
// GESTIÓN DE EXAMEN (ADMIN)
// ══════════════════════════════════════════════════════════════════════

async function abrirModalExamenAdmin(matId) {
    exAdminMatId = matId;
    exAdminEid   = null;
    const mat = cacheGrilla.find(m => m.id == matId);
    document.getElementById('exAdminMatTitulo').textContent = mat ? mat.titulo : '—';
    document.getElementById('exAdminBody').innerHTML = '<p class="text-secondary text-center" style="padding:32px">Cargando…</p>';
    document.getElementById('btnEliminarExamen').style.display = 'none';
    Modal.open('modalExamenAdmin');
    await cargarExamenAdmin();
}

async function cargarExamenAdmin() {
    try {
        const d = await apiGet(`${BASE}?action=examen_admin_obtener&material_id=${exAdminMatId}`);
        if (!d.ok) { document.getElementById('exAdminBody').innerHTML = '<p class="text-secondary text-center">Error al cargar</p>'; return; }

        if (!d.examen) {
            exAdminEid = null;
            document.getElementById('btnEliminarExamen').style.display = 'none';
            document.getElementById('exAdminBody').innerHTML = `
                <div style="text-align:center;padding:20px 0 24px">
                    <i class="fa fa-clipboard-list" style="font-size:2.5rem;color:#c4b5fd;margin-bottom:12px;display:block"></i>
                    <p style="color:#64748b;margin-bottom:20px">Este material no tiene examen. Cree uno para evaluar a los usuarios.</p>
                </div>
                <div class="form-group">
                    <label class="form-label">Título del examen *</label>
                    <input type="text" class="form-control" id="exTitulo" placeholder="Ej: Evaluación de seguridad industrial">
                </div>
                <div class="form-group" style="width:180px">
                    <label class="form-label">Puntaje mínimo para aprobar (%)</label>
                    <input type="number" class="form-control" id="exPuntajeMin" value="70" min="0" max="100">
                </div>
                <button class="btn btn-primary" onclick="crearExamen()">
                    <i class="fa fa-plus"></i> Crear examen
                </button>`;
        } else {
            exAdminEid = d.examen.id;
            document.getElementById('btnEliminarExamen').style.display = '';
            renderExamenAdmin(d.examen);
        }
    } catch(e) { document.getElementById('exAdminBody').innerHTML = '<p class="text-secondary text-center">Error al cargar</p>'; }
}

function renderExamenAdmin(examen) {
    const preguntasHtml = examen.preguntas.length
        ? examen.preguntas.map((p, idx) => `
            <div style="border:1px solid var(--border);border-radius:6px;padding:12px 14px;margin-bottom:8px;background:#fafafa">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px">
                    <strong style="font-size:0.88rem;line-height:1.4">${idx+1}. ${escHtml(p.texto)}</strong>
                    <button class="btn btn-secondary" style="padding:2px 8px;font-size:0.75rem;color:var(--danger);white-space:nowrap;flex-shrink:0"
                        onclick="eliminarPregunta(${p.id})">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
                <div style="margin-top:8px;display:grid;grid-template-columns:1fr 1fr;gap:4px">
                    ${p.opciones.map(o => `
                        <div style="font-size:0.82rem;display:flex;align-items:center;gap:6px;padding:4px 8px;border-radius:4px;
                            background:${o.es_correcta?'#dcfce7':'#f1f5f9'};border:1px solid ${o.es_correcta?'#86efac':'#e2e8f0'}">
                            <i class="fa ${o.es_correcta?'fa-check-circle':'fa-circle'}" style="color:${o.es_correcta?'#16a34a':'#94a3b8'};font-size:0.8rem"></i>
                            ${escHtml(o.texto)}
                        </div>`).join('')}
                </div>
            </div>`).join('')
        : `<p style="font-size:0.85rem;color:#94a3b8;text-align:center;padding:12px 0">
            Sin preguntas aún. Agregue la primera usando el formulario de abajo.
           </p>`;

    document.getElementById('exAdminBody').innerHTML = `
        <div style="display:grid;grid-template-columns:1fr 100px;gap:12px;align-items:end;margin-bottom:6px">
            <div class="form-group" style="margin:0">
                <label class="form-label" style="font-size:0.8rem">Título del examen</label>
                <input type="text" class="form-control" id="exTitulo" value="${escHtml(examen.titulo)}">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label" style="font-size:0.8rem">Mín. (%)</label>
                <input type="number" class="form-control" id="exPuntajeMin" value="${examen.puntaje_minimo}" min="0" max="100">
            </div>
        </div>
        <button class="btn btn-secondary" style="font-size:0.8rem;padding:4px 10px;margin-bottom:18px" onclick="actualizarExamenHeader()">
            <i class="fa fa-save"></i> Guardar encabezado
        </button>

        <div style="border-top:1px solid var(--border);padding-top:12px;margin-bottom:10px">
            <strong style="font-size:0.9rem">Preguntas (${examen.preguntas.length})</strong>
        </div>
        ${preguntasHtml}

        <div style="border-top:1px solid var(--border);padding-top:14px;margin-top:10px">
            <strong style="font-size:0.9rem;display:block;margin-bottom:12px">
                <i class="fa fa-plus-circle" style="color:#6366f1"></i> Agregar nueva pregunta
            </strong>
            <div class="form-group">
                <label class="form-label" style="font-size:0.8rem">Texto de la pregunta *</label>
                <textarea class="form-control" id="pqTexto" rows="2"
                    placeholder="Ej: ¿Cuál es el EPP requerido en el área de soldadura?"></textarea>
            </div>
            <div style="display:grid;grid-template-columns:1fr auto;gap:8px;margin-bottom:5px">
                <span style="font-size:0.79rem;color:#64748b;font-weight:600">Opciones de respuesta</span>
                <span style="font-size:0.79rem;color:#64748b;font-weight:600;text-align:center">Correcta</span>
            </div>
            ${['A','B','C','D'].map(ltr => `
            <div style="display:grid;grid-template-columns:1fr auto;gap:8px;margin-bottom:6px;align-items:center">
                <input type="text" class="form-control" id="opt${ltr}" placeholder="Opción ${ltr}">
                <label style="display:flex;align-items:center;gap:5px;font-size:0.82rem;cursor:pointer;white-space:nowrap;padding:0 4px">
                    <input type="radio" name="optCorrecta" value="${ltr}"> ✓
                </label>
            </div>`).join('')}
            <button class="btn btn-primary" style="margin-top:6px;width:100%" onclick="agregarPregunta()">
                <i class="fa fa-plus"></i> Agregar pregunta
            </button>
        </div>`;
}

async function crearExamen() {
    const titulo = document.getElementById('exTitulo').value.trim();
    if (!titulo) { Toast.warning('El título es obligatorio'); return; }
    try {
        const r = await apiPost(BASE, {
            action: 'examen_guardar', material_id: exAdminMatId, titulo,
            puntaje_minimo: parseInt(document.getElementById('exPuntajeMin').value) || 70,
        });
        if (!r.ok) { Toast.error(r.error || 'Error'); return; }
        exAdminEid = r.id;
        Toast.success('Examen creado');
        await cargarExamenAdmin();
        cargarMateriales();
        cargarDashboard();
    } catch(e) { Toast.error('Error al crear examen'); }
}

async function actualizarExamenHeader() {
    const titulo = document.getElementById('exTitulo').value.trim();
    if (!titulo) { Toast.warning('El título es obligatorio'); return; }
    try {
        const r = await apiPost(BASE, {
            action: 'examen_guardar', material_id: exAdminMatId, titulo,
            puntaje_minimo: parseInt(document.getElementById('exPuntajeMin').value) || 70,
        });
        if (r.ok) Toast.success('Examen actualizado');
        else Toast.error(r.error || 'Error');
    } catch(e) { Toast.error('Error al actualizar'); }
}

async function agregarPregunta() {
    if (!exAdminEid) return;
    const texto = document.getElementById('pqTexto').value.trim();
    if (!texto) { Toast.warning('Ingrese el texto de la pregunta'); return; }
    const correctaVal = document.querySelector('input[name="optCorrecta"]:checked')?.value;
    if (!correctaVal) { Toast.warning('Marque cuál es la respuesta correcta'); return; }

    const opciones = ['A','B','C','D']
        .map(ltr => ({ texto: document.getElementById('opt' + ltr).value.trim(), es_correcta: ltr === correctaVal }))
        .filter(o => o.texto);

    if (opciones.length < 2) { Toast.warning('Ingrese al menos 2 opciones de respuesta'); return; }

    try {
        const r = await apiPost(BASE, { action: 'pregunta_guardar', examen_id: exAdminEid, texto, opciones });
        if (!r.ok) { Toast.error(r.error || 'Error'); return; }
        Toast.success('Pregunta agregada');
        cargarExamenAdmin();
    } catch(e) { Toast.error('Error al agregar pregunta'); }
}

async function eliminarPregunta(id) {
    if (!confirm('¿Eliminar esta pregunta?')) return;
    try {
        const r = await apiPost(BASE, { action: 'pregunta_eliminar', id });
        if (r.ok) { Toast.success('Pregunta eliminada'); cargarExamenAdmin(); }
        else Toast.error(r.error || 'Error');
    } catch(e) { Toast.error('Error al eliminar pregunta'); }
}

async function eliminarExamen() {
    if (!confirm('¿Eliminar el examen completo? Se perderán todas las preguntas e intentos.')) return;
    try {
        const r = await apiPost(BASE, { action: 'examen_eliminar', material_id: exAdminMatId });
        if (r.ok) {
            Toast.success('Examen eliminado');
            Modal.close('modalExamenAdmin');
            cargarMateriales();
            cargarDashboard();
        } else Toast.error(r.error || 'Error');
    } catch(e) { Toast.error('Error al eliminar examen'); }
}

// ══════════════════════════════════════════════════════════════════════
// RENDIR EXAMEN (ALUMNO)
// ══════════════════════════════════════════════════════════════════════

async function abrirModalTomarExamen(matId) {
    exTomarMatId = matId;
    exTomarData  = null;
    document.getElementById('exTomarBody').innerHTML = '<p class="text-secondary text-center" style="padding:32px">Cargando examen…</p>';
    document.getElementById('exTomarFooter').innerHTML = `
        <button class="btn btn-secondary" onclick="Modal.close('modalTomarExamen')">Cancelar</button>
        <button class="btn btn-primary" id="btnSubmitExamen" onclick="submitExamen()">
            <i class="fa fa-check"></i> Enviar respuestas
        </button>`;
    Modal.open('modalTomarExamen');

    try {
        const d = await apiGet(`${BASE}?action=examen_tomar&material_id=${matId}`);
        if (!d.ok || !d.examen) {
            document.getElementById('exTomarBody').innerHTML = '<p class="text-secondary text-center" style="padding:32px">No hay examen disponible para este material.</p>';
            document.getElementById('exTomarFooter').innerHTML = '<button class="btn btn-secondary" onclick="Modal.close(\'modalTomarExamen\')">Cerrar</button>';
            return;
        }
        exTomarData = d;
        document.getElementById('exTomarTitulo').textContent = d.examen.titulo;

        let headerHtml = `<p style="font-size:0.85rem;color:#64748b;margin-bottom:16px">
            Puntaje mínimo para aprobar: <strong>${d.examen.puntaje_minimo}%</strong>
            · ${d.examen.preguntas.length} preguntas
        </p>`;

        if (d.mejor_intento) {
            const ap = d.mejor_intento.aprobado === true || d.mejor_intento.aprobado === 't';
            headerHtml = `<div style="background:${ap?'#dcfce7':'#fef3c7'};border-radius:8px;padding:12px 16px;margin-bottom:16px;display:flex;align-items:center;gap:12px">
                <i class="fa fa-${ap?'trophy':'redo'}" style="color:${ap?'#16a34a':'#d97706'};font-size:1.2rem"></i>
                <div>
                    <div style="font-weight:600;font-size:0.88rem;color:${ap?'#16a34a':'#92400e'}">
                        ${ap ? 'Ya aprobaste este examen' : 'Aún no has aprobado — puedes intentarlo de nuevo'}
                    </div>
                    <div style="font-size:0.8rem;color:#64748b">
                        Mejor puntaje: <strong>${d.mejor_intento.puntaje}%</strong> · Mínimo: ${d.examen.puntaje_minimo}%
                    </div>
                </div>
            </div>` + headerHtml;
        }

        document.getElementById('exTomarBody').innerHTML = headerHtml + renderPreguntasTomar(d.examen);
    } catch(e) { Toast.error('Error al cargar examen'); }
}

function renderPreguntasTomar(examen) {
    return examen.preguntas.map((p, idx) => `
        <div style="border:1px solid var(--border);border-radius:8px;padding:14px 16px;margin-bottom:14px" id="pqt-${p.id}">
            <p style="font-weight:600;font-size:0.9rem;margin-bottom:12px;line-height:1.4">
                ${idx+1}. ${escHtml(p.texto)}
            </p>
            ${p.opciones.map(o => `
                <label id="lbl-${o.id}"
                    style="display:flex;align-items:center;gap:10px;padding:8px 12px;border-radius:6px;cursor:pointer;margin-bottom:6px;border:1px solid var(--border);transition:background .15s"
                    onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background=''">
                    <input type="radio" name="resp-${p.id}" value="${o.id}" style="margin:0;accent-color:#6366f1">
                    <span style="font-size:0.87rem">${escHtml(o.texto)}</span>
                </label>`).join('')}
        </div>`).join('');
}

async function submitExamen() {
    if (!exTomarData) return;
    const preguntas = exTomarData.examen.preguntas;
    const respuestas = [];

    for (let i = 0; i < preguntas.length; i++) {
        const p = preguntas[i];
        const sel = document.querySelector(`input[name="resp-${p.id}"]:checked`);
        if (!sel) { Toast.warning(`Responda la pregunta ${i+1}`); return; }
        respuestas.push({ pregunta_id: p.id, opcion_id: parseInt(sel.value) });
    }

    const btn = document.getElementById('btnSubmitExamen');
    if (btn) btn.disabled = true;

    try {
        const r = await apiPost(BASE, { action: 'intento_guardar', examen_id: exTomarData.examen.id, respuestas });
        if (!r.ok) { Toast.error(r.error || 'Error al enviar'); if (btn) btn.disabled = false; return; }

        const ap = r.aprobado;

        // Colorear opciones: verde=correcta, rojo=incorrecta seleccionada
        preguntas.forEach(p => {
            const respMia = respuestas.find(x => x.pregunta_id === p.id);
            const correctaId = r.correctas_map[p.id];
            p.opciones.forEach(o => {
                const lbl = document.getElementById('lbl-' + o.id);
                const inp = lbl ? lbl.querySelector('input') : null;
                if (inp) inp.disabled = true;
                if (!lbl) return;
                if (o.id == correctaId) {
                    lbl.style.background = '#dcfce7';
                    lbl.style.borderColor = '#86efac';
                } else if (respMia && o.id == respMia.opcion_id) {
                    lbl.style.background = '#fee2e2';
                    lbl.style.borderColor = '#fca5a5';
                }
            });
        });

        // Banner de resultado al tope
        const body = document.getElementById('exTomarBody');
        const banner = document.createElement('div');
        banner.style.cssText = `background:${ap?'#dcfce7':'#fee2e2'};border-radius:10px;padding:20px;margin-bottom:18px;text-align:center`;
        banner.innerHTML = `
            <div style="font-size:2.5rem;font-weight:800;color:${ap?'#16a34a':'#dc2626'};line-height:1">${r.puntaje}%</div>
            <div style="font-size:1rem;font-weight:700;color:${ap?'#16a34a':'#dc2626'};margin:6px 0">${ap?'¡Aprobado!':'No aprobado'}</div>
            <div style="font-size:0.85rem;color:#475569">${r.correctas} de ${r.total} respuestas correctas · Mínimo requerido: ${exTomarData.examen.puntaje_minimo}%</div>
            ${ap ? `<div style="font-size:0.82rem;color:#16a34a;margin-top:8px"><i class="fa fa-check-circle"></i> Tu progreso fue marcado como completado automáticamente</div>` : ''}`;
        body.prepend(banner);
        body.scrollTop = 0;

        // Footer
        document.getElementById('exTomarFooter').innerHTML = `
            <button class="btn btn-secondary" onclick="Modal.close('modalTomarExamen')">Cerrar</button>
            ${!ap ? `<button class="btn btn-primary" onclick="abrirModalTomarExamen(${exTomarMatId})">
                <i class="fa fa-redo"></i> Intentar de nuevo
            </button>` : ''}`;

        if (ap) { cargarDashboard(); cargarProgreso(); }
    } catch(e) { Toast.error('Error al enviar respuestas'); if (btn) btn.disabled = false; }
}
</script>

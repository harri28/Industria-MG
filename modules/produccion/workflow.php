<?php
$page_title      = 'Ajustes de Workflow';
$page_breadcrumb = '<a href="index.php">Producción</a> <span>›</span> Ajustes de Workflow';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/database.php';
$db = getDB();
?>

<!-- CABECERA -->
<div class="card mb-2" style="padding:0">
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:16px 20px">
        <div>
            <div style="font-size:1rem;font-weight:700;display:flex;align-items:center;gap:10px">
                <span style="width:38px;height:38px;border-radius:50%;background:var(--primary-light,#eff6ff);
                             display:flex;align-items:center;justify-content:center;color:var(--primary)">
                    <i class="fa fa-diagram-project"></i>
                </span>
                Configuración del Workflow de Producción
            </div>
            <div style="font-size:.8rem;color:var(--text-muted);margin-top:4px;margin-left:48px">
                Las áreas activas se asignan automáticamente a cada nuevo proyecto.
                Las áreas externas no se incluyen en el cálculo de avance global.
            </div>
        </div>
        <button class="btn btn-primary btn-sm" onclick="abrirModal()">
            <i class="fa fa-plus"></i> Nueva Área
        </button>
    </div>
</div>

<!-- TABLA DE ÁREAS -->
<div class="card">
    <div class="table-responsive">
        <table id="tablaAreas">
            <thead>
                <tr>
                    <th style="width:48px">Orden</th>
                    <th>Área</th>
                    <th>Descripción</th>
                    <th style="text-align:center">Tipo</th>
                    <th style="text-align:center">Proyectos</th>
                    <th style="text-align:center">Estado</th>
                    <th style="text-align:center">Acciones</th>
                </tr>
            </thead>
            <tbody id="areasBody">
                <tr><td colspan="7" class="text-center text-muted">Cargando…</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- LEYENDA -->
<div style="margin-top:12px;display:flex;gap:16px;flex-wrap:wrap;font-size:.78rem;color:var(--text-muted)">
    <span><span class="badge badge-completado" style="font-size:.7rem">Interno</span> — Incluida en el avance global del proyecto</span>
    <span><span class="badge" style="font-size:.7rem;background:#f59e0b;color:#fff">Externo</span> — Servicio tercerizado, excluido del avance</span>
    <span><i class="fa fa-circle-info"></i> Las áreas se asignan al crear un nuevo proyecto</span>
</div>

<!-- =====================================================
     MODAL CREAR / EDITAR
     ===================================================== -->
<div class="modal-overlay" id="modalArea">
    <div class="modal" style="max-width:480px">
        <div class="modal-header">
            <span class="modal-title" id="modalTitulo"><i class="fa fa-sitemap"></i> Nueva Área</span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="areaId">
            <div class="form-row cols-2">
                <div class="form-group" style="grid-column:span 2">
                    <label class="form-label">Nombre del área *</label>
                    <input type="text" class="form-control" id="areaNombre" placeholder="Ej: Mecanizado">
                </div>
                <div class="form-group" style="grid-column:span 2">
                    <label class="form-label">Descripción</label>
                    <input type="text" class="form-control" id="areaDesc" placeholder="Breve descripción de las tareas del área">
                </div>
                <div class="form-group">
                    <label class="form-label">Orden en workflow</label>
                    <input type="number" class="form-control" id="areaOrden" min="1" max="99" value="99">
                    <div style="font-size:.73rem;color:var(--text-muted);margin-top:3px">Menor número = aparece primero</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Tipo</label>
                    <select class="form-control" id="areaExterna">
                        <option value="0">Interno (cuenta en avance)</option>
                        <option value="1">Externo (servicio tercerizado)</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalArea')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardar()">
                <i class="fa fa-save"></i> Guardar
            </button>
        </div>
    </div>
</div>

<script>
const BASE = 'api.php';

// ── Cargar áreas ──────────────────────────────────────────
async function cargar() {
    try {
        const d = await apiGet(`${BASE}?action=wf_areas_listar`);
        renderAreas(d.areas || []);
    } catch(e) { Toast.error('Error al cargar áreas.'); }
}

function renderAreas(lista) {
    const tb = document.getElementById('areasBody');
    if (!lista.length) {
        tb.innerHTML = `<tr><td colspan="7" class="text-center text-muted" style="padding:32px">
            Sin áreas configuradas. Usa <strong>Nueva Área</strong> para comenzar.</td></tr>`;
        return;
    }
    tb.innerHTML = lista.map(a => {
        const tipoBadge = a.es_externa
            ? `<span class="badge" style="background:#f59e0b;color:#fff;font-size:.68rem">Externo</span>`
            : `<span class="badge badge-completado" style="font-size:.68rem">Interno</span>`;
        const activoBadge = a.activo
            ? `<span class="badge badge-completado" style="font-size:.68rem">Activa</span>`
            : `<span class="badge badge-cancelado"  style="font-size:.68rem">Inactiva</span>`;
        return `<tr>
            <td class="text-center">
                <span style="font-size:.85rem;font-weight:700;color:var(--text-muted)">${a.orden_workflow || 99}</span>
            </td>
            <td>
                <strong style="font-size:.9rem">${esc(a.nombre)}</strong>
            </td>
            <td style="font-size:.82rem;color:var(--text-secondary)">${esc(a.descripcion || '—')}</td>
            <td style="text-align:center">${tipoBadge}</td>
            <td style="text-align:center">
                <span class="badge badge-normal" style="font-size:.68rem">${a.total_proyectos} proyecto${a.total_proyectos != 1 ? 's' : ''}</span>
            </td>
            <td style="text-align:center">${activoBadge}</td>
            <td style="text-align:center">
                <div style="display:flex;gap:6px;justify-content:center">
                    <button class="btn btn-outline btn-xs" onclick="editar(${a.id})" title="Editar">
                        <i class="fa fa-pencil"></i>
                    </button>
                    <button class="btn btn-xs ${a.activo ? 'btn-outline' : 'btn-primary'}"
                            onclick="toggle(${a.id},'${esc(a.nombre)}')" title="${a.activo ? 'Desactivar' : 'Activar'}">
                        <i class="fa fa-${a.activo ? 'ban' : 'check'}"></i>
                    </button>
                    <button class="btn btn-xs btn-outline" style="color:var(--danger,#dc2626);border-color:var(--danger,#dc2626)"
                            onclick="eliminar(${a.id},'${esc(a.nombre)}')" title="Eliminar">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

// ── Modal ─────────────────────────────────────────────────
function abrirModal() {
    document.getElementById('areaId').value     = '';
    document.getElementById('areaNombre').value = '';
    document.getElementById('areaDesc').value   = '';
    document.getElementById('areaOrden').value  = '99';
    document.getElementById('areaExterna').value= '0';
    document.getElementById('modalTitulo').innerHTML = '<i class="fa fa-sitemap"></i> Nueva Área';
    Modal.open('modalArea');
}

function editar(id) {
    const fila = [...document.querySelectorAll('#areasBody tr')]
        .find(tr => tr.querySelector(`button[onclick="editar(${id})"]`));
    // Recargar datos desde el server
    apiGet(`${BASE}?action=wf_areas_listar`).then(d => {
        const a = d.areas.find(x => x.id == id);
        if (!a) return;
        document.getElementById('areaId').value      = a.id;
        document.getElementById('areaNombre').value  = a.nombre;
        document.getElementById('areaDesc').value    = a.descripcion || '';
        document.getElementById('areaOrden').value   = a.orden_workflow || 99;
        document.getElementById('areaExterna').value = a.es_externa ? '1' : '0';
        document.getElementById('modalTitulo').innerHTML = `<i class="fa fa-pencil"></i> Editar: ${esc(a.nombre)}`;
        Modal.open('modalArea');
    });
}

async function guardar() {
    const nombre = document.getElementById('areaNombre').value.trim();
    if (!nombre) { Toast.warning('El nombre es obligatorio.'); return; }
    try {
        const r = await apiPost(BASE, {
            action:          'wf_area_guardar',
            id:              document.getElementById('areaId').value,
            nombre,
            descripcion:     document.getElementById('areaDesc').value.trim(),
            orden_workflow:  document.getElementById('areaOrden').value,
            es_externa:      document.getElementById('areaExterna').value === '1',
        });
        if (r.ok) {
            Toast.success('Área guardada.');
            Modal.close('modalArea');
            cargar();
        } else Toast.error(r.error || 'Error al guardar.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

async function toggle(id, nombre) {
    if (!confirm(`¿Cambiar estado del área "${nombre}"?`)) return;
    try {
        const r = await apiPost(BASE, { action: 'wf_area_toggle', id });
        if (r.ok) { cargar(); }
        else Toast.error(r.error || 'Error.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

async function eliminar(id, nombre) {
    if (!confirm(`¿Eliminar el área "${nombre}"?\n\nEsta acción no se puede deshacer.`)) return;
    try {
        const r = await apiPost(BASE, { action: 'wf_area_eliminar', id });
        if (r.ok) { Toast.success('Área eliminada.'); cargar(); }
        else Toast.error(r.error || 'Error al eliminar.');
    } catch(e) { Toast.error(e.message || 'Error de conexión.'); }
}

function esc(s) { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; }

cargar();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

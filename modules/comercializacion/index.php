<?php
$page_title      = 'Comercialización Digital';
$page_breadcrumb = 'Comercial & Digital / Comercialización';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="tabs" id="mainTabs">
    <button class="tab-btn active" data-tab="resumen">Resumen</button>
    <button class="tab-btn" data-tab="catalogo">Catálogo</button>
    <button class="tab-btn" data-tab="leads">Leads</button>
</div>

<!-- RESUMEN -->
<div class="tab-content active" id="tab-resumen">
    <div class="kpi-grid">
        <div class="kpi-card"><div class="kpi-label">Productos en Catálogo</div><div class="kpi-value" id="k-cat">—</div></div>
        <div class="kpi-card"><div class="kpi-label">Publicados</div><div class="kpi-value" id="k-pub" style="color:#22c55e">—</div></div>
        <div class="kpi-card"><div class="kpi-label">Leads Nuevos</div><div class="kpi-value" id="k-leads-nuevos" style="color:var(--primary)">—</div></div>
        <div class="kpi-card"><div class="kpi-label">Leads este mes</div><div class="kpi-value" id="k-leads-mes">—</div></div>
        <div class="kpi-card"><div class="kpi-label">Leads Convertidos</div><div class="kpi-value" id="k-leads-conv" style="color:#22c55e">—</div></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:4px">
        <div class="card">
            <div class="card-header">Leads por Origen</div>
            <div class="card-body" id="leadsPorOrigen" style="padding:16px"></div>
        </div>
        <div class="card">
            <div class="card-header">Leads por Estado</div>
            <div class="card-body" id="leadsPorEstado" style="padding:16px"></div>
        </div>
    </div>
</div>

<!-- CATÁLOGO -->
<div class="tab-content" id="tab-catalogo">
    <div class="toolbar">
        <div class="toolbar-left">
            <input type="text" class="form-control" id="catSearch" placeholder="Buscar producto…" style="width:260px">
            <select class="form-control" id="catCategoria" style="width:180px">
                <option value="">Todas las categorías</option>
            </select>
            <select class="form-control" id="catPublicado" style="width:140px">
                <option value="">Todos</option>
                <option value="true">Publicados</option>
                <option value="false">No publicados</option>
            </select>
        </div>
        <div class="toolbar-right">
            <button class="btn btn-primary" onclick="abrirModalCatalogo()">
                <i class="fa fa-plus"></i> Nuevo Producto
            </button>
        </div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px" id="gridCatalogo">
        <p class="text-secondary" style="grid-column:1/-1;text-align:center;padding:40px">Cargando catálogo…</p>
    </div>
</div>

<!-- LEADS -->
<div class="tab-content" id="tab-leads">
    <div class="toolbar">
        <div class="toolbar-left">
            <input type="text" class="form-control" id="leadSearch" placeholder="Buscar lead…" style="width:260px">
            <select class="form-control" id="leadEstado" style="width:150px">
                <option value="">Todos los estados</option>
                <option value="nuevo">Nuevo</option>
                <option value="contactado">Contactado</option>
                <option value="calificado">Calificado</option>
                <option value="convertido">Convertido</option>
                <option value="descartado">Descartado</option>
            </select>
            <select class="form-control" id="leadOrigen" style="width:150px">
                <option value="">Todos los orígenes</option>
                <option value="web">Web</option>
                <option value="facebook">Facebook</option>
                <option value="instagram">Instagram</option>
                <option value="tiktok">TikTok</option>
                <option value="youtube">YouTube</option>
                <option value="directo">Directo</option>
            </select>
        </div>
        <div class="toolbar-right">
            <button class="btn btn-primary" onclick="abrirModalLead()">
                <i class="fa fa-plus"></i> Nuevo Lead
            </button>
        </div>
    </div>
    <div class="card">
        <table class="table" id="tablaLeads">
            <thead>
                <tr><th>Nombre</th><th>Empresa</th><th>Contacto</th><th>Origen</th><th>Estado</th><th>Fecha</th><th>Acciones</th></tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- MODAL: CATÁLOGO -->
<div class="modal-overlay" id="modalCatalogo">
    <div class="modal" style="max-width:600px">
        <div class="modal-header">
            <h3 class="modal-title" id="modalCatTitle">Nuevo Producto</h3>
            <button class="modal-close" onclick="Modal.close('modalCatalogo')">×</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="cat_id">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div class="form-group" style="grid-column:1/3">
                    <label class="form-label">Nombre *</label>
                    <input type="text" class="form-control" id="cat_nombre">
                </div>
                <div class="form-group">
                    <label class="form-label">Categoría</label>
                    <input type="text" class="form-control" id="cat_cat" list="catCatList" placeholder="Ej: Maquinaria Industrial">
                    <datalist id="catCatList"></datalist>
                </div>
                <div class="form-group">
                    <label class="form-label">Precio Referencial (S/)</label>
                    <input type="number" class="form-control" id="cat_precio" min="0" step="0.01">
                </div>
                <div class="form-group" style="grid-column:1/3">
                    <label class="form-label">Descripción corta</label>
                    <input type="text" class="form-control" id="cat_dcorta" maxlength="500" placeholder="Máx. 500 caracteres">
                </div>
                <div class="form-group" style="grid-column:1/3">
                    <label class="form-label">Descripción completa</label>
                    <textarea class="form-control" id="cat_desc" rows="4"></textarea>
                </div>
                <div class="form-group" style="grid-column:1/3">
                    <label class="form-label">Imagen del producto</label>
                    <div id="catImgZone" style="width:100%;height:150px;border:2px dashed #cbd5e1;border-radius:8px;display:flex;align-items:center;justify-content:center;background:#f8fafc;cursor:pointer;overflow:hidden;position:relative" onclick="document.getElementById('catImgInput').click()">
                        <img id="catImgPreview" style="display:none;width:100%;height:100%;object-fit:cover">
                        <div id="catImgPlaceholder" style="text-align:center;color:#94a3b8;pointer-events:none">
                            <i class="fa fa-image" style="font-size:2rem;margin-bottom:6px;display:block"></i>
                            <span style="font-size:0.82rem">Clic para subir imagen (JPG, PNG, WebP · máx 5MB)</span>
                        </div>
                    </div>
                    <input type="file" id="catImgInput" accept=".jpg,.jpeg,.png,.webp" style="display:none" onchange="catImgChange(this)">
                    <button type="button" id="catImgQuit" onclick="catImgClear()" class="btn btn-secondary" style="display:none;margin-top:6px;font-size:0.78rem;padding:3px 10px">
                        <i class="fa fa-times"></i> Quitar imagen
                    </button>
                </div>
                <div class="form-group">
                    <label class="form-label">Orden de aparición</label>
                    <input type="number" class="form-control" id="cat_orden" min="0" value="0">
                </div>
                <div class="form-group" style="display:flex;gap:20px;align-items:flex-end">
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                        <input type="checkbox" id="cat_publicado"> Publicado
                    </label>
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                        <input type="checkbox" id="cat_destacado"> Destacado
                    </label>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('modalCatalogo')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarCatalogo()">Guardar</button>
        </div>
    </div>
</div>

<!-- MODAL: LEAD -->
<div class="modal-overlay" id="modalLead">
    <div class="modal" style="max-width:520px">
        <div class="modal-header">
            <h3 class="modal-title" id="modalLeadTitle">Nuevo Lead</h3>
            <button class="modal-close" onclick="Modal.close('modalLead')">×</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="lead_id">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div class="form-group">
                    <label class="form-label">Nombre</label>
                    <input type="text" class="form-control" id="lead_nombre">
                </div>
                <div class="form-group">
                    <label class="form-label">Empresa</label>
                    <input type="text" class="form-control" id="lead_empresa">
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" id="lead_email">
                </div>
                <div class="form-group">
                    <label class="form-label">Teléfono</label>
                    <input type="text" class="form-control" id="lead_telefono">
                </div>
                <div class="form-group">
                    <label class="form-label">Origen</label>
                    <select class="form-control" id="lead_origen">
                        <option value="directo">Directo</option>
                        <option value="web">Web</option>
                        <option value="facebook">Facebook</option>
                        <option value="instagram">Instagram</option>
                        <option value="tiktok">TikTok</option>
                        <option value="youtube">YouTube</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select class="form-control" id="lead_estado">
                        <option value="nuevo">Nuevo</option>
                        <option value="contactado">Contactado</option>
                        <option value="calificado">Calificado</option>
                        <option value="descartado">Descartado</option>
                    </select>
                </div>
                <div class="form-group" style="grid-column:1/3">
                    <label class="form-label">Mensaje / Interés</label>
                    <textarea class="form-control" id="lead_mensaje" rows="3"></textarea>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('modalLead')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarLead()">Guardar</button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
let catCategorias = [];
const CAT_IMG_BASE = '../../assets/uploads/catalogo/';
let catImgState = { existingFilename: null, file: null, removed: false };

document.addEventListener('DOMContentLoaded', () => {
    initTabs('mainTabs');
    cargarDashboard();
    cargarCategoriasCatalogo().then(() => {
        cargarCatalogo();
        cargarLeads();
    });
    ['catSearch','catPublicado'].forEach(id => {
        document.getElementById(id).addEventListener('input', debounce(cargarCatalogo, 350));
        document.getElementById(id).addEventListener('change', cargarCatalogo);
    });
    document.getElementById('catCategoria').addEventListener('change', cargarCatalogo);
    ['leadSearch','leadEstado','leadOrigen'].forEach(id => {
        document.getElementById(id).addEventListener('input', debounce(cargarLeads, 350));
        document.getElementById(id).addEventListener('change', cargarLeads);
    });
});

function debounce(fn, d) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), d); }; }

async function cargarDashboard() {
    const d = await apiGet('modules/comercializacion/api.php?action=dashboard');
    if (!d.ok) return;
    document.getElementById('k-cat').textContent         = d.total_catalogo;
    document.getElementById('k-pub').textContent         = d.publicados;
    document.getElementById('k-leads-nuevos').textContent = d.leads_nuevos;
    document.getElementById('k-leads-mes').textContent   = d.leads_mes;
    document.getElementById('k-leads-conv').textContent  = d.leads_convertidos;

    const origenMap = { web:'Web', facebook:'Facebook', instagram:'Instagram', tiktok:'TikTok', youtube:'YouTube', directo:'Directo' };
    const total_orig = d.por_origen.reduce((s, o) => s + parseInt(o.cnt), 0);
    document.getElementById('leadsPorOrigen').innerHTML = d.por_origen.map(o => {
        const pct = total_orig > 0 ? Math.round(parseInt(o.cnt) / total_orig * 100) : 0;
        return `<div style="margin-bottom:10px">
            <div style="display:flex;justify-content:space-between;font-size:0.82rem;margin-bottom:3px">
                <span>${origenMap[o.origen]||o.origen}</span><span>${o.cnt} (${pct}%)</span>
            </div>
            <div style="background:#e2e8f0;border-radius:4px;height:8px">
                <div style="background:var(--primary);width:${pct}%;height:8px;border-radius:4px"></div>
            </div>
        </div>`;
    }).join('') || '<p class="text-secondary" style="font-size:0.85rem">Sin datos</p>';

    const estadoMap = { nuevo:'Nuevo', contactado:'Contactado', calificado:'Calificado', convertido:'Convertido', descartado:'Descartado' };
    const estadoCol = { nuevo:'#f97316', contactado:'#0ea5e9', calificado:'#8b5cf6', convertido:'#22c55e', descartado:'#94a3b8' };
    const total_est = d.por_estado.reduce((s, e) => s + parseInt(e.cnt), 0);
    document.getElementById('leadsPorEstado').innerHTML = d.por_estado.map(e => {
        const pct = total_est > 0 ? Math.round(parseInt(e.cnt) / total_est * 100) : 0;
        return `<div style="margin-bottom:10px">
            <div style="display:flex;justify-content:space-between;font-size:0.82rem;margin-bottom:3px">
                <span>${estadoMap[e.estado]||e.estado}</span><span>${e.cnt} (${pct}%)</span>
            </div>
            <div style="background:#e2e8f0;border-radius:4px;height:8px">
                <div style="background:${estadoCol[e.estado]||'#64748b'};width:${pct}%;height:8px;border-radius:4px"></div>
            </div>
        </div>`;
    }).join('') || '<p class="text-secondary" style="font-size:0.85rem">Sin datos</p>';
}

async function cargarCategoriasCatalogo() {
    const d = await apiGet('modules/comercializacion/api.php?action=categorias_catalogo');
    if (!d.ok) return;
    catCategorias = d.categorias;
    const opts = '<option value="">Todas las categorías</option>' + d.categorias.map(c => `<option value="${c}">${c}</option>`).join('');
    document.getElementById('catCategoria').innerHTML = opts;
    document.getElementById('catCatList').innerHTML = d.categorias.map(c => `<option value="${c}">`).join('');
}

async function cargarCatalogo() {
    const q   = document.getElementById('catSearch').value;
    const cat = document.getElementById('catCategoria').value;
    const pub = document.getElementById('catPublicado').value;
    const d = await apiGet(`modules/comercializacion/api.php?action=catalogo_listar&q=${encodeURIComponent(q)}&categoria=${encodeURIComponent(cat)}&publicado=${pub}`);
    if (!d.ok) return;
    const grid = document.getElementById('gridCatalogo');
    if (!d.productos.length) {
        grid.innerHTML = '<p class="text-secondary" style="grid-column:1/-1;text-align:center;padding:40px">Sin productos en el catálogo</p>';
        return;
    }
    grid.innerHTML = d.productos.map(p => `
        <div class="card" style="padding:0;overflow:hidden">
            ${p.imagen_principal ? `<div style="height:160px;overflow:hidden;background:#f1f5f9"><img src="${CAT_IMG_BASE}${p.imagen_principal}" style="width:100%;height:100%;object-fit:cover" loading="lazy"></div>` : ''}
            <div style="background:${p.publicado?'#f0fdf4':'#f8fafc'};padding:8px 12px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border)">
                <span class="badge ${p.publicado?'badge-success':'badge-secondary'}">${p.publicado?'Publicado':'Borrador'}</span>
                ${p.destacado ? '<span class="badge badge-warning"><i class="fa fa-star"></i> Destacado</span>' : ''}
            </div>
            <div style="padding:16px">
                <div style="font-weight:600;margin-bottom:4px">${p.nombre}</div>
                ${p.categoria ? `<div style="font-size:0.78rem;color:#64748b;margin-bottom:8px">${p.categoria}</div>` : ''}
                ${p.descripcion_corta ? `<div style="font-size:0.83rem;color:#475569;margin-bottom:8px;line-height:1.5">${p.descripcion_corta}</div>` : ''}
                ${p.precio_referencial ? `<div style="font-weight:700;color:var(--primary)">${formatMoney(p.precio_referencial)}</div>` : ''}
            </div>
            <div style="padding:8px 12px;border-top:1px solid var(--border);display:flex;gap:6px">
                <button class="btn btn-secondary" style="flex:1;padding:4px;font-size:0.78rem" onclick="editarCatalogo(${p.id})">
                    <i class="fa fa-edit"></i> Editar
                </button>
                <button class="btn btn-secondary" style="padding:4px 8px;font-size:0.78rem" title="${p.publicado?'Despublicar':'Publicar'}" onclick="togglePublicado(${p.id})">
                    <i class="fa fa-${p.publicado?'eye-slash':'eye'}"></i>
                </button>
                <button class="btn btn-secondary" style="padding:4px 8px;font-size:0.78rem;color:var(--danger)" onclick="eliminarCatalogo(${p.id})">
                    <i class="fa fa-trash"></i>
                </button>
            </div>
        </div>`).join('');
}

function abrirModalCatalogo() {
    document.getElementById('cat_id').value = '';
    document.getElementById('cat_nombre').value = '';
    document.getElementById('cat_cat').value = '';
    document.getElementById('cat_precio').value = '';
    document.getElementById('cat_dcorta').value = '';
    document.getElementById('cat_desc').value = '';
    document.getElementById('cat_orden').value = '0';
    document.getElementById('cat_publicado').checked = false;
    document.getElementById('cat_destacado').checked = false;
    document.getElementById('modalCatTitle').textContent = 'Nuevo Producto';
    catImgReset();
    Modal.open('modalCatalogo');
}

async function editarCatalogo(id) {
    const d = await apiGet(`modules/comercializacion/api.php?action=catalogo_obtener&id=${id}`);
    if (!d.ok) return;
    const p = d.producto;
    document.getElementById('cat_id').value = p.id;
    document.getElementById('cat_nombre').value = p.nombre;
    document.getElementById('cat_cat').value = p.categoria || '';
    document.getElementById('cat_precio').value = p.precio_referencial || '';
    document.getElementById('cat_dcorta').value = p.descripcion_corta || '';
    document.getElementById('cat_desc').value = p.descripcion || '';
    document.getElementById('cat_orden').value = p.orden || 0;
    document.getElementById('cat_publicado').checked = p.publicado;
    document.getElementById('cat_destacado').checked = p.destacado;
    document.getElementById('modalCatTitle').textContent = 'Editar Producto';
    catImgState = { existingFilename: p.imagen_principal || null, file: null, removed: false };
    if (p.imagen_principal) catImgSetPreview(CAT_IMG_BASE + p.imagen_principal);
    else catImgReset();
    Modal.open('modalCatalogo');
}

async function guardarCatalogo() {
    const nombre = document.getElementById('cat_nombre').value.trim();
    if (!nombre) { Toast.warning('El nombre es obligatorio'); return; }
    const body = {
        action:              'catalogo_guardar',
        id:                  document.getElementById('cat_id').value || null,
        nombre,
        categoria:           document.getElementById('cat_cat').value,
        precio_referencial:  document.getElementById('cat_precio').value || null,
        descripcion_corta:   document.getElementById('cat_dcorta').value,
        descripcion:         document.getElementById('cat_desc').value,
        orden:               document.getElementById('cat_orden').value,
        publicado:           document.getElementById('cat_publicado').checked,
        destacado:           document.getElementById('cat_destacado').checked,
    };
    const r = await apiPost('modules/comercializacion/api.php', body);
    if (!r.ok) { Toast.error(r.error || 'Error'); return; }
    if (catImgState.file) {
        const fd = new FormData();
        fd.append('action', 'catalogo_img_guardar');
        fd.append('id', r.id);
        fd.append('imagen', catImgState.file);
        await fetch('modules/comercializacion/api.php', { method: 'POST', body: fd });
    } else if (catImgState.removed && catImgState.existingFilename) {
        await apiPost('modules/comercializacion/api.php', { action: 'catalogo_img_eliminar', id: r.id });
    }
    Toast.success('Producto guardado');
    Modal.close('modalCatalogo');
    cargarCatalogo();
    cargarDashboard();
    cargarCategoriasCatalogo();
}

async function togglePublicado(id) {
    const r = await apiPost('modules/comercializacion/api.php', { action: 'catalogo_toggle_publicado', id });
    if (r.ok) { cargarCatalogo(); cargarDashboard(); }
}

async function eliminarCatalogo(id) {
    if (!confirm('¿Eliminar este producto del catálogo?')) return;
    const r = await apiPost('modules/comercializacion/api.php', { action: 'catalogo_eliminar', id });
    if (r.ok) { Toast.success('Eliminado'); cargarCatalogo(); cargarDashboard(); }
    else Toast.error(r.error || 'Error');
}

// LEADS
async function cargarLeads() {
    const q      = document.getElementById('leadSearch').value;
    const estado = document.getElementById('leadEstado').value;
    const origen = document.getElementById('leadOrigen').value;
    const d = await apiGet(`modules/comercializacion/api.php?action=leads_listar&q=${encodeURIComponent(q)}&estado=${estado}&origen=${origen}`);
    if (!d.ok) return;

    const estadoBadge = { nuevo:'badge-primary', contactado:'badge-secondary', calificado:'badge-warning', convertido:'badge-success', descartado:'badge-danger' };
    const origenIco = { web:'fa-globe', facebook:'fa-square-facebook', instagram:'fa-instagram', tiktok:'fa-tiktok', youtube:'fa-youtube', directo:'fa-user' };

    const tbody = document.querySelector('#tablaLeads tbody');
    tbody.innerHTML = d.leads.map(l => `<tr>
        <td><strong>${l.nombre || '—'}</strong></td>
        <td>${l.empresa || '—'}</td>
        <td>
            ${l.email ? `<a href="mailto:${l.email}" style="color:var(--primary)">${l.email}</a><br>` : ''}
            ${l.telefono ? `<a href="https://wa.me/${l.telefono.replace(/\D/g,'')}" target="_blank" style="color:#25D366">${l.telefono}</a>` : ''}
        </td>
        <td><i class="fa ${origenIco[l.origen]||'fa-question'}"></i> ${l.origen || '—'}</td>
        <td><span class="badge ${estadoBadge[l.estado]||'badge-secondary'}">${l.estado}</span></td>
        <td>${formatDate(l.created_at)}</td>
        <td>
            <button class="btn btn-secondary" style="padding:3px 8px;font-size:0.78rem" onclick="editarLead(${l.id})">
                <i class="fa fa-edit"></i>
            </button>
            ${l.estado !== 'convertido' ? `
            <button class="btn btn-primary" style="padding:3px 8px;font-size:0.78rem;background:#22c55e" onclick="convertirLead(${l.id})" title="Convertir a cliente">
                <i class="fa fa-user-plus"></i>
            </button>` : `<span class="badge badge-success"><i class="fa fa-check"></i> Cliente</span>`}
            <button class="btn btn-secondary" style="padding:3px 8px;font-size:0.78rem;color:var(--danger)" onclick="eliminarLead(${l.id})">
                <i class="fa fa-trash"></i>
            </button>
        </td>
    </tr>`).join('') || '<tr><td colspan="7" class="text-secondary text-center" style="padding:20px">Sin leads</td></tr>';
}

function abrirModalLead() {
    document.getElementById('lead_id').value = '';
    document.getElementById('lead_nombre').value = '';
    document.getElementById('lead_empresa').value = '';
    document.getElementById('lead_email').value = '';
    document.getElementById('lead_telefono').value = '';
    document.getElementById('lead_origen').value = 'directo';
    document.getElementById('lead_estado').value = 'nuevo';
    document.getElementById('lead_mensaje').value = '';
    document.getElementById('modalLeadTitle').textContent = 'Nuevo Lead';
    Modal.open('modalLead');
}

async function editarLead(id) {
    const d = await apiGet(`modules/comercializacion/api.php?action=lead_obtener&id=${id}`);
    if (!d.ok) return;
    const lead = d.lead;
    document.getElementById('lead_id').value = lead.id;
    document.getElementById('lead_nombre').value = lead.nombre || '';
    document.getElementById('lead_empresa').value = lead.empresa || '';
    document.getElementById('lead_email').value = lead.email || '';
    document.getElementById('lead_telefono').value = lead.telefono || '';
    document.getElementById('lead_origen').value = lead.origen || 'directo';
    document.getElementById('lead_estado').value = lead.estado || 'nuevo';
    document.getElementById('lead_mensaje').value = lead.mensaje || '';
    document.getElementById('modalLeadTitle').textContent = 'Editar Lead';
    Modal.open('modalLead');
}

async function guardarLead() {
    const body = {
        action:    'lead_guardar',
        id:        document.getElementById('lead_id').value || null,
        nombre:    document.getElementById('lead_nombre').value,
        empresa:   document.getElementById('lead_empresa').value,
        email:     document.getElementById('lead_email').value,
        telefono:  document.getElementById('lead_telefono').value,
        origen:    document.getElementById('lead_origen').value,
        estado:    document.getElementById('lead_estado').value,
        mensaje:   document.getElementById('lead_mensaje').value,
    };
    const r = await apiPost('modules/comercializacion/api.php', body);
    if (r.ok) { Toast.success('Lead guardado'); Modal.close('modalLead'); cargarLeads(); cargarDashboard(); }
    else Toast.error(r.error || 'Error');
}

async function convertirLead(id) {
    if (!confirm('¿Convertir este lead en cliente?')) return;
    const r = await apiPost('modules/comercializacion/api.php', { action: 'lead_convertir', id });
    if (r.ok) { Toast.success('Lead convertido en cliente'); cargarLeads(); cargarDashboard(); }
    else Toast.error(r.error || 'Error');
}

async function eliminarLead(id) {
    if (!confirm('¿Eliminar este lead?')) return;
    const r = await apiPost('modules/comercializacion/api.php', { action: 'lead_eliminar', id });
    if (r.ok) { Toast.success('Lead eliminado'); cargarLeads(); cargarDashboard(); }
    else Toast.error(r.error || 'Error');
}

// ── Imagen de producto en catálogo ────────────────────────────────
function catImgChange(input) {
    const file = input.files[0];
    if (!file) return;
    catImgState.file = file;
    catImgState.removed = false;
    const reader = new FileReader();
    reader.onload = e => catImgSetPreview(e.target.result);
    reader.readAsDataURL(file);
}
function catImgSetPreview(src) {
    document.getElementById('catImgPreview').src = src;
    document.getElementById('catImgPreview').style.display = '';
    document.getElementById('catImgPlaceholder').style.display = 'none';
    document.getElementById('catImgQuit').style.display = '';
}
function catImgClear() {
    catImgState.file = null;
    catImgState.removed = true;
    document.getElementById('catImgPreview').style.display = 'none';
    document.getElementById('catImgPreview').src = '';
    document.getElementById('catImgPlaceholder').style.display = '';
    document.getElementById('catImgQuit').style.display = 'none';
    document.getElementById('catImgInput').value = '';
}
function catImgReset() {
    catImgState = { existingFilename: null, file: null, removed: false };
    document.getElementById('catImgPreview').style.display = 'none';
    document.getElementById('catImgPreview').src = '';
    document.getElementById('catImgPlaceholder').style.display = '';
    document.getElementById('catImgQuit').style.display = 'none';
    document.getElementById('catImgInput').value = '';
}
</script>

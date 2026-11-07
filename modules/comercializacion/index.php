<?php
$page_title      = 'Comercialización Digital';
$page_breadcrumb = 'Comercial & Digital / Comercialización';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="tabs" id="mainTabs">
    <button class="tab-btn active" data-tab="catalogo">Catálogo</button>
    <button class="tab-btn" data-tab="leads">Leads</button>
</div>

<!-- CATÁLOGO -->
<div class="tab-content active" id="tab-catalogo">
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
                <tr><th>Nombre</th><th>Empresa</th><th>Contacto</th><th>Origen</th><th>Estado</th><th>Próxima acción</th><th>Fecha</th><th>Acciones</th></tr>
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
                <div class="form-group" style="grid-column:1/3">
                    <label class="form-label">Colores disponibles</label>
                    <div style="font-size:0.75rem;color:#94a3b8;margin-bottom:6px">Cada color con su imagen. Se usa al registrar una maquinaria.</div>
                    <div id="catColoresList"></div>
                    <button type="button" class="btn btn-secondary" style="font-size:0.78rem;padding:4px 12px;margin-top:4px" onclick="catColorAgregar()">
                        <i class="fa fa-plus"></i> Agregar color
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
                <div class="form-group">
                    <label class="form-label">Responsable</label>
                    <select class="form-control" id="lead_responsable"><option value="">— Sin asignar —</option></select>
                </div>
                <div class="form-group">
                    <label class="form-label">Próxima fecha</label>
                    <input type="date" class="form-control" id="lead_pfecha">
                </div>
                <div class="form-group" style="grid-column:1/3">
                    <label class="form-label">Próxima acción</label>
                    <input type="text" class="form-control" id="lead_paccion" placeholder="Ej. Llamar para enviar cotización">
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

<!-- MODAL: SEGUIMIENTO DE LEAD -->
<div class="modal-overlay" id="modalSeguimiento">
    <div class="modal" style="max-width:560px">
        <div class="modal-header">
            <h3 class="modal-title">Seguimiento — <span id="seg_lead_nombre"></span></h3>
            <button class="modal-close" onclick="Modal.close('modalSeguimiento')">×</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="seg_lead_id">
            <div id="seg_resumen" style="background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:10px 12px;margin-bottom:14px;font-size:0.85rem"></div>

            <div style="display:grid;grid-template-columns:140px 1fr auto;gap:8px;align-items:end;margin-bottom:14px">
                <div class="form-group" style="margin:0">
                    <label class="form-label">Tipo</label>
                    <select class="form-control" id="seg_tipo">
                        <option value="llamada">Llamada</option>
                        <option value="whatsapp">WhatsApp</option>
                        <option value="email">Email</option>
                        <option value="visita">Visita / Reunión</option>
                        <option value="nota">Nota</option>
                    </select>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Nota</label>
                    <input type="text" class="form-control" id="seg_nota" placeholder="Qué se habló / acordó…" onkeydown="if(event.key==='Enter')guardarInteraccion()">
                </div>
                <button class="btn btn-primary" onclick="guardarInteraccion()"><i class="fa fa-plus"></i> Agregar</button>
            </div>

            <div style="font-size:0.78rem;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px">Bitácora</div>
            <div id="seg_timeline"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('modalSeguimiento')">Cerrar</button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
let catCategorias = [];
const CAT_IMG_BASE = '../../assets/uploads/catalogo/';
let catImgState = { existingFilename: null, file: null, removed: false };
// Colores del modelo: [{nombre, imagen(filename existente|null), file(File|null), preview(dataurl|null)}]
let catColores = [];
function esc(s) { const d = document.createElement('div'); d.textContent = String(s ?? ''); return d.innerHTML; }

let leadUsuarios = [];

document.addEventListener('DOMContentLoaded', () => {
    initTabs('mainTabs');
    cargarUsuariosLead();
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
    catColoresReset([]);
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
    let coloresP = p.colores || [];
    if (typeof coloresP === 'string') { try { coloresP = JSON.parse(coloresP) || []; } catch (e) { coloresP = []; } }
    catColoresReset(coloresP);
    Modal.open('modalCatalogo');
}

async function guardarCatalogo() {
    const nombre = document.getElementById('cat_nombre').value.trim();
    if (!nombre) { Toast.warning('El nombre es obligatorio'); return; }
    // Sube las imagenes de color nuevas y arma la lista [{nombre, imagen}].
    const colores = [];
    for (const c of catColores) {
        if (!(c.nombre || '').trim()) continue;
        let imagen = c.imagen || null;
        if (c.file) {
            const fd = new FormData();
            fd.append('action', 'catalogo_color_img');
            fd.append('imagen', c.file);
            const up = await fetch('modules/comercializacion/api.php', { method: 'POST', body: fd }).then(x => x.json());
            if (up.ok) imagen = up.filename;
            else { Toast.error(up.error || 'Error al subir imagen de color'); return; }
        }
        colores.push({ nombre: c.nombre.trim(), imagen });
    }
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
        colores,
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
    cargarCategoriasCatalogo();
}

async function togglePublicado(id) {
    const r = await apiPost('modules/comercializacion/api.php', { action: 'catalogo_toggle_publicado', id });
    if (r.ok) { cargarCatalogo(); }
}

async function eliminarCatalogo(id) {
    if (!confirm('¿Eliminar este producto del catálogo?')) return;
    const r = await apiPost('modules/comercializacion/api.php', { action: 'catalogo_eliminar', id });
    if (r.ok) { Toast.success('Eliminado'); cargarCatalogo(); }
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

    const hoy = new Date().toISOString().slice(0,10);
    const tbody = document.querySelector('#tablaLeads tbody');
    tbody.innerHTML = d.leads.map(l => {
        const nInt = parseInt(l.num_interacciones || 0);
        let proxima = '<span class="text-secondary">—</span>';
        if (l.proxima_accion || l.proxima_fecha) {
            const vencida = l.proxima_fecha && l.proxima_fecha < hoy && l.estado !== 'convertido';
            const col = vencida ? '#dc2626' : '#0f172a';
            proxima = `<div style="font-size:0.82rem;color:${col}">
                ${l.proxima_fecha ? `<i class="fa fa-calendar" style="margin-right:4px"></i>${formatDate(l.proxima_fecha)}${vencida?' <strong>(vencida)</strong>':''}<br>` : ''}
                <span style="color:#475569">${l.proxima_accion ? esc(l.proxima_accion) : ''}</span>
            </div>`;
        }
        return `<tr>
        <td><strong>${l.nombre || '—'}</strong>${l.responsable_nombre ? `<div style="font-size:0.72rem;color:#94a3b8"><i class="fa fa-user-tie"></i> ${esc(l.responsable_nombre)}</div>` : ''}</td>
        <td>${l.empresa || '—'}</td>
        <td>
            ${l.email ? `<a href="mailto:${l.email}" style="color:var(--primary)">${l.email}</a><br>` : ''}
            ${l.telefono ? `<a href="https://wa.me/${l.telefono.replace(/\D/g,'')}" target="_blank" style="color:#25D366">${l.telefono}</a>` : ''}
        </td>
        <td><i class="fa ${origenIco[l.origen]||'fa-question'}"></i> ${l.origen || '—'}</td>
        <td><span class="badge ${estadoBadge[l.estado]||'badge-secondary'}">${l.estado}</span></td>
        <td>${proxima}</td>
        <td>${formatDate(l.created_at)}</td>
        <td>
            <button class="btn btn-secondary" style="padding:3px 8px;font-size:0.78rem" onclick="abrirSeguimiento(${l.id})" title="Seguimiento">
                <i class="fa fa-clock-rotate-left"></i>${nInt>0?` <span style="font-size:0.7rem">${nInt}</span>`:''}
            </button>
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
    </tr>`; }).join('') || '<tr><td colspan="8" class="text-secondary text-center" style="padding:20px">Sin leads</td></tr>';
}

async function cargarUsuariosLead() {
    const d = await apiGet('modules/comercializacion/api.php?action=usuarios_combo');
    if (!d.ok) return;
    leadUsuarios = d.usuarios || [];
    document.getElementById('lead_responsable').innerHTML =
        '<option value="">— Sin asignar —</option>' +
        leadUsuarios.map(u => `<option value="${u.id}">${esc(u.nombre)}</option>`).join('');
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
    document.getElementById('lead_responsable').value = '';
    document.getElementById('lead_paccion').value = '';
    document.getElementById('lead_pfecha').value = '';
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
    document.getElementById('lead_responsable').value = lead.responsable_id || '';
    document.getElementById('lead_paccion').value = lead.proxima_accion || '';
    document.getElementById('lead_pfecha').value = lead.proxima_fecha || '';
    document.getElementById('modalLeadTitle').textContent = 'Editar Lead';
    Modal.open('modalLead');
}

async function guardarLead() {
    const body = {
        action:          'lead_guardar',
        id:              document.getElementById('lead_id').value || null,
        nombre:          document.getElementById('lead_nombre').value,
        empresa:         document.getElementById('lead_empresa').value,
        email:           document.getElementById('lead_email').value,
        telefono:        document.getElementById('lead_telefono').value,
        origen:          document.getElementById('lead_origen').value,
        estado:          document.getElementById('lead_estado').value,
        mensaje:         document.getElementById('lead_mensaje').value,
        responsable_id:  document.getElementById('lead_responsable').value || null,
        proxima_accion:  document.getElementById('lead_paccion').value,
        proxima_fecha:   document.getElementById('lead_pfecha').value || null,
    };
    const r = await apiPost('modules/comercializacion/api.php', body);
    if (r.ok) { Toast.success('Lead guardado'); Modal.close('modalLead'); cargarLeads(); }
    else Toast.error(r.error || 'Error');
}

// ── Seguimiento / bitácora de leads ───────────────────────────────
let segLeadId = null;
const SEG_TIPOS = {
    llamada:  { ico:'fa-solid fa-phone',       col:'#0ea5e9', lbl:'Llamada' },
    whatsapp: { ico:'fa-brands fa-whatsapp',   col:'#25D366', lbl:'WhatsApp' },
    email:    { ico:'fa-solid fa-envelope',    col:'#8b5cf6', lbl:'Email' },
    visita:   { ico:'fa-solid fa-handshake',   col:'#f97316', lbl:'Visita / Reunión' },
    nota:     { ico:'fa-solid fa-note-sticky', col:'#64748b', lbl:'Nota' },
};

async function abrirSeguimiento(id) {
    segLeadId = id;
    document.getElementById('seg_lead_id').value = id;
    document.getElementById('seg_tipo').value = 'llamada';
    document.getElementById('seg_nota').value = '';
    Modal.open('modalSeguimiento');
    await cargarSeguimiento();
}

async function cargarSeguimiento() {
    const d = await apiGet(`modules/comercializacion/api.php?action=lead_obtener&id=${segLeadId}`);
    if (!d.ok) return;
    const lead = d.lead || {};
    document.getElementById('seg_lead_nombre').textContent = lead.nombre || 'Lead';
    const hoy = new Date().toISOString().slice(0,10);
    const vencida = lead.proxima_fecha && lead.proxima_fecha < hoy && lead.estado !== 'convertido';
    document.getElementById('seg_resumen').innerHTML = `
        <div><strong>Estado:</strong> ${esc(lead.estado||'—')} &nbsp;·&nbsp; <strong>Responsable:</strong> ${lead.responsable_nombre ? esc(lead.responsable_nombre) : '<span style="color:#94a3b8">Sin asignar</span>'}</div>
        <div style="margin-top:4px"><strong>Próxima acción:</strong> ${lead.proxima_accion ? esc(lead.proxima_accion) : '—'}
            ${lead.proxima_fecha ? `<span style="color:${vencida?'#dc2626':'#475569'};margin-left:6px"><i class="fa fa-calendar"></i> ${formatDate(lead.proxima_fecha)}${vencida?' (vencida)':''}</span>` : ''}</div>`;

    const items = d.interacciones || [];
    const tl = document.getElementById('seg_timeline');
    if (!items.length) { tl.innerHTML = '<p class="text-secondary" style="font-size:0.85rem">Sin interacciones registradas todavía.</p>'; return; }
    tl.innerHTML = items.map(it => {
        const t = SEG_TIPOS[it.tipo] || SEG_TIPOS.nota;
        return `<div style="display:flex;gap:10px;padding:10px 0;border-bottom:1px solid #f1f5f9">
            <div style="width:30px;height:30px;border-radius:50%;background:${t.col}1a;color:${t.col};display:flex;align-items:center;justify-content:center;flex:0 0 auto"><i class="${t.ico}"></i></div>
            <div style="flex:1">
                <div style="font-size:0.85rem">${esc(it.nota)}</div>
                <div style="font-size:0.72rem;color:#94a3b8;margin-top:2px">
                    ${t.lbl} · ${formatDateTime(it.created_at)}${it.usuario_nombre?` · ${esc(it.usuario_nombre)}`:''}
                </div>
            </div>
            <button class="btn btn-secondary" style="padding:2px 7px;font-size:0.72rem;color:var(--danger);height:fit-content" onclick="eliminarInteraccion(${it.id})"><i class="fa fa-trash"></i></button>
        </div>`;
    }).join('');
}

async function guardarInteraccion() {
    const nota = document.getElementById('seg_nota').value.trim();
    if (!nota) { Toast.warning('Escribe una nota.'); return; }
    const r = await apiPost('modules/comercializacion/api.php', {
        action: 'lead_interaccion_guardar',
        lead_id: segLeadId,
        tipo: document.getElementById('seg_tipo').value,
        nota,
    });
    if (r.ok) { document.getElementById('seg_nota').value = ''; cargarSeguimiento(); cargarLeads(); }
    else Toast.error(r.error || 'Error');
}

async function eliminarInteraccion(id) {
    if (!confirm('¿Eliminar esta interacción?')) return;
    const r = await apiPost('modules/comercializacion/api.php', { action: 'lead_interaccion_eliminar', id });
    if (r.ok) { cargarSeguimiento(); cargarLeads(); }
    else Toast.error(r.error || 'Error');
}

function formatDateTime(ts) {
    if (!ts) return '';
    const d = new Date(String(ts).replace(' ', 'T'));
    if (isNaN(d)) return formatDate(ts);
    return d.toLocaleString('es-PE', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' });
}

async function convertirLead(id) {
    if (!confirm('¿Convertir este lead en cliente?')) return;
    const r = await apiPost('modules/comercializacion/api.php', { action: 'lead_convertir', id });
    if (r.ok) { Toast.success('Lead convertido en cliente'); cargarLeads(); }
    else Toast.error(r.error || 'Error');
}

async function eliminarLead(id) {
    if (!confirm('¿Eliminar este lead?')) return;
    const r = await apiPost('modules/comercializacion/api.php', { action: 'lead_eliminar', id });
    if (r.ok) { Toast.success('Lead eliminado'); cargarLeads(); }
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

// ── Colores del modelo (con imagen por color) ─────────────────────
function catColoresRender() {
    const cont = document.getElementById('catColoresList');
    if (!catColores.length) { cont.innerHTML = '<div style="font-size:0.8rem;color:#cbd5e1;margin:4px 0">Sin colores. Agrega al menos uno si quieres que la máquina elija color.</div>'; return; }
    cont.innerHTML = catColores.map((c, i) => {
        const src = c.preview || (c.imagen ? CAT_IMG_BASE + c.imagen : '');
        const thumb = src
            ? `<img src="${src}" style="width:44px;height:44px;object-fit:cover;border-radius:6px">`
            : `<i class="fa fa-image" style="color:#cbd5e1"></i>`;
        return `<div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
            <div style="width:44px;height:44px;border:1px dashed #cbd5e1;border-radius:6px;display:flex;align-items:center;justify-content:center;cursor:pointer;overflow:hidden;flex:0 0 auto" onclick="document.getElementById('catColorImg${i}').click()">${thumb}</div>
            <input type="file" id="catColorImg${i}" accept=".jpg,.jpeg,.png,.webp" style="display:none" onchange="catColorImg(${i}, this)">
            <input type="text" class="form-control" style="flex:1" placeholder="Nombre del color (ej. Naranja)" value="${(c.nombre||'').replace(/"/g,'&quot;')}" oninput="catColores[${i}].nombre=this.value">
            <button type="button" class="btn btn-secondary" style="padding:4px 9px;color:var(--danger)" onclick="catColorQuitar(${i})"><i class="fa fa-trash"></i></button>
        </div>`;
    }).join('');
}
function catColorAgregar() {
    catColores.push({ nombre: '', imagen: null, file: null, preview: null });
    catColoresRender();
}
function catColorQuitar(i) {
    catColores.splice(i, 1);
    catColoresRender();
}
function catColorImg(i, input) {
    const file = input.files[0];
    if (!file) return;
    catColores[i].file = file;
    const reader = new FileReader();
    reader.onload = e => { catColores[i].preview = e.target.result; catColoresRender(); };
    reader.readAsDataURL(file);
}
function catColoresReset(lista) {
    catColores = (lista || []).map(c => ({ nombre: c.nombre || '', imagen: c.imagen || null, file: null, preview: null }));
    catColoresRender();
}
</script>

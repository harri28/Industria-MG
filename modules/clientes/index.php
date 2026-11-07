<?php
$page_title = 'Clientes y Proveedores';
$page_breadcrumb = '<span>Comercial</span> <span>›</span> Clientes y Proveedores';
require_once __DIR__ . '/../../includes/header.php';
?>

<style>
.cliente-form-grid { display:grid; gap:14px; }
.cliente-form-grid.c2 { grid-template-columns:1fr 1fr; }
.input-lookup { display:grid; grid-template-columns:1fr auto; gap:10px; align-items:end; }
.lookup-note { font-size:.77rem; color:var(--text-muted); margin-top:6px; }
.doc-chip { display:inline-flex; align-items:center; gap:6px; padding:7px 11px; border-radius:999px; background:#eef2ff; color:#4338ca; font-size:.78rem; font-weight:700; }
.ubigeo-box { background:var(--bg-secondary,#f8fafc); border:1px dashed var(--border); border-radius:10px; padding:10px 12px; color:var(--text-muted); font-size:.82rem; }
</style>

<div class="tabs" id="cpTabs">
    <div class="tab active" data-group="cp" data-target="tab-clientes"><i class="fa fa-users"></i> Clientes</div>
    <div class="tab" data-group="cp" data-target="tab-proveedores"><i class="fa fa-truck-field"></i> Proveedores</div>
</div>

<div class="tab-content active" data-group="cp" id="tab-clientes">
<div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:16px">
    <div style="min-width:88px;background:#fff;border:2px solid #000;border-radius:5px;padding:8px 10px">
        <div id="kpiTotal" style="font-size:1.1rem;font-weight:800">—</div>
        <div style="font-size:.6rem;color:var(--text-muted)">Total clientes</div>
    </div>
    <div style="min-width:88px;background:#fff;border:2px solid #000;border-radius:5px;padding:8px 10px">
        <div id="kpiActivos" style="font-size:1.1rem;font-weight:800">—</div>
        <div style="font-size:.6rem;color:var(--text-muted)">Activos</div>
    </div>
    <div style="min-width:88px;background:#fff;border:2px solid #000;border-radius:5px;padding:8px 10px">
        <div id="kpiProspectos" style="font-size:1.1rem;font-weight:800">—</div>
        <div style="font-size:.6rem;color:var(--text-muted)">Prospectos</div>
    </div>
    <div style="min-width:88px;background:#fff;border:2px solid #000;border-radius:5px;padding:8px 10px">
        <div id="kpiInactivos" style="font-size:1.1rem;font-weight:800">—</div>
        <div style="font-size:.6rem;color:var(--text-muted)">Inactivos</div>
    </div>
</div>

<div class="toolbar">
    <div class="toolbar-left">
        <div class="search-box">
            <span class="icon"><i class="fa fa-search"></i></span>
            <input type="text" class="form-control" id="buscar" placeholder="Nombre, documento, telefono, WhatsApp o correo...">
        </div>
        <select class="form-control" id="filtroTipoDoc" style="width:200px">
            <option value="">Todos los documentos</option>
        </select>
    </div>
    <div class="toolbar-right">
        <button class="btn btn-primary btn-sm" onclick="abrirModalCliente()">
            <i class="fa fa-plus"></i> Nuevo Cliente
        </button>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table id="tablaClientes">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Cliente</th>
                    <th>Documento</th>
                    <th>Correo</th>
                    <th>Telefono / WhatsApp</th>
                    <th>Estado CRM</th>
                </tr>
            </thead>
            <tbody id="tbody">
                <tr><td colspan="6" class="text-center text-muted">Cargando...</td></tr>
            </tbody>
        </table>
    </div>
</div>
</div><!-- /tab-clientes -->

<div class="tab-content" data-group="cp" id="tab-proveedores">
    <div class="toolbar">
        <div class="toolbar-left">
            <div class="search-box">
                <span class="icon"><i class="fa fa-search"></i></span>
                <input type="text" class="form-control" id="provSearch" placeholder="Razon social, RUC o contacto...">
            </div>
            <select class="form-control" id="provFiltroActivo" style="width:160px">
                <option value="">Todos</option>
                <option value="true">Activos</option>
                <option value="false">Inactivos</option>
            </select>
        </div>
        <div class="toolbar-right">
            <button class="btn btn-primary btn-sm" onclick="abrirModalProveedor()">
                <i class="fa fa-plus"></i> Nuevo Proveedor
            </button>
        </div>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table id="tablaProv">
                <thead>
                    <tr>
                        <th>Razon Social</th><th>RUC</th><th>Contacto</th>
                        <th>Telefono</th><th>Calificacion</th><th class="text-center">OCs</th>
                        <th>Estado</th><th></th>
                    </tr>
                </thead>
                <tbody id="tbodyProv">
                    <tr><td colspan="8" class="text-center text-muted">Cargando...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div><!-- /tab-proveedores -->

<div class="modal-overlay" id="modalCliente">
    <div class="modal modal-lg">
        <div class="modal-header">
            <span class="modal-title" id="modalClienteTitulo">Nuevo Cliente</span>
            <button class="modal-close" onclick="Modal.close('modalCliente')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="cId">
            <input type="hidden" id="cUbigeo">
            <input type="hidden" id="cTipo">
            <input type="hidden" id="cEstado" value="prospecto">

            <div class="cliente-form-grid c2">
                <div class="form-group">
                    <label class="form-label" style="display:flex;align-items:center;gap:8px">Tipo de documento <span class="doc-chip" id="docChip"><i class="fa fa-id-card"></i> Documento</span></label>
                    <select class="form-control" id="cTipoDocumento" onchange="actualizarEstadoDocumento()"></select>
                </div>
                <div class="form-group">
                    <label class="form-label">Numero de documento</label>
                    <div class="input-lookup">
                        <input type="text" class="form-control" id="cNumeroDocumento" placeholder="Ingrese el documento">
                        <button type="button" class="btn btn-outline" id="btnConsultarDocumento" onclick="consultarDocumento()">
                            <i class="fa fa-search"></i> Consultar
                        </button>
                    </div>
                    <div class="lookup-note" id="lookupNote">Consulta automatica disponible para DNI y RUC.</div>
                </div>
            </div>

            <div class="cliente-form-grid c2">
                <div class="form-group">
                    <label class="form-label" id="labelRazonSocial">Nombre completo</label>
                    <input type="text" class="form-control" id="cRazonSocial" placeholder="Nombre del cliente">
                </div>
                <div class="form-group">
                    <label class="form-label">Direccion</label>
                    <textarea class="form-control" id="cDireccion" rows="1" placeholder="Direccion del cliente" style="min-height:40px"></textarea>
                </div>
            </div>

            <div class="cliente-form-grid c2">
                <div class="form-group">
                    <label class="form-label">Nombre de contacto</label>
                    <input type="text" class="form-control" id="cContacto" placeholder="Contacto comercial o referencia">
                </div>
                <div class="form-group">
                    <label class="form-label">Correo</label>
                    <input type="email" class="form-control" id="cEmail" placeholder="correo@ejemplo.com">
                </div>
            </div>

            <div class="cliente-form-grid c2">
                <div class="form-group">
                    <label class="form-label">Telefono</label>
                    <input type="text" class="form-control" id="cTelefono" placeholder="987654321">
                </div>
                <div class="form-group">
                    <label class="form-label">WhatsApp</label>
                    <input type="text" class="form-control" id="cWhatsapp" placeholder="987654321">
                </div>
            </div>

            <div class="ubigeo-box" id="ubigeoInfo">Sin ubigeo consultado.</div>

            <div class="form-group" style="margin-top:14px">
                <label class="form-label">Notas / Observaciones</label>
                <textarea class="form-control" id="cNotas" rows="2" placeholder="Informacion comercial, acuerdos, referencias, etc."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalCliente')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarCliente()"><i class="fa fa-save"></i> Guardar</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modalDetalle">
    <div class="modal modal-lg">
        <div class="modal-header">
            <span class="modal-title" id="detalleTitulo">Detalle del Cliente</span>
            <button class="modal-close" onclick="Modal.close('modalDetalle')">&times;</button>
        </div>
        <div class="modal-body" id="detalleBody">
            <div class="loading"><div class="spinner"></div></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalDetalle')">Cerrar</button>
            <button class="btn btn-primary" id="btnEditarDesdeDetalle"><i class="fa fa-pencil"></i> Editar</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modalProveedor">
    <div class="modal" style="max-width:560px">
        <div class="modal-header">
            <span class="modal-title" id="modalProvTitulo">Nuevo Proveedor</span>
            <button class="modal-close" onclick="Modal.close('modalProveedor')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="prov_id">
            <div class="cliente-form-grid c2">
                <div class="form-group" style="grid-column:1/3">
                    <label class="form-label">Razon Social *</label>
                    <input type="text" class="form-control" id="prov_razon_social" placeholder="Nombre o razon social del proveedor">
                </div>
                <div class="form-group">
                    <label class="form-label">RUC</label>
                    <input type="text" class="form-control" id="prov_ruc" maxlength="20">
                </div>
                <div class="form-group">
                    <label class="form-label">Contacto</label>
                    <input type="text" class="form-control" id="prov_contacto">
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" id="prov_email">
                </div>
                <div class="form-group">
                    <label class="form-label">Telefono</label>
                    <input type="text" class="form-control" id="prov_telefono">
                </div>
                <div class="form-group" style="grid-column:1/3">
                    <label class="form-label">Direccion</label>
                    <textarea class="form-control" id="prov_direccion" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select class="form-control" id="prov_activo">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalProveedor')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarProveedor()"><i class="fa fa-save"></i> Guardar</button>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
const BASE = 'api.php';
let tiposDocumento = [];

document.addEventListener('DOMContentLoaded', async () => {
    await cargarTiposDocumento();
    actualizarEstadoDocumento();
    cargarKpis();
    cargarClientes();
    cargarProveedores();
    // Si se llega con #proveedores (ej. desde Compras), abrir esa pestaña
    if (location.hash === '#proveedores') {
        document.querySelector('.tab[data-target="tab-proveedores"]')?.click();
    }
});

let debounceTimer;
document.getElementById('buscar').addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(cargarClientes, 300);
});
document.getElementById('filtroTipoDoc').addEventListener('change', cargarClientes);

let provDebTimer;
document.getElementById('provSearch').addEventListener('input', () => {
    clearTimeout(provDebTimer);
    provDebTimer = setTimeout(cargarProveedores, 300);
});
document.getElementById('provFiltroActivo').addEventListener('change', cargarProveedores);

async function cargarTiposDocumento() {
    const d = await apiGet(`${BASE}?action=document_types`);
    tiposDocumento = d.items || [];
    const sel = document.getElementById('cTipoDocumento');
    sel.innerHTML = tiposDocumento.map(item =>
        `<option value="${item.id}" data-codigo="${esc(item.codigo)}" data-label="${esc(item.descripcion_documento || item.descripcion)}">${esc(item.descripcion_documento || item.descripcion)}</option>`
    ).join('');
    const predeterminado = tiposDocumento.find(item => item.codigo === '1') || tiposDocumento[0];
    if (predeterminado) sel.value = String(predeterminado.id);

    // Filtro de la barra superior (mismo catalogo de tipos de documento)
    const filtro = document.getElementById('filtroTipoDoc');
    if (filtro) {
        filtro.innerHTML = '<option value="">Todos los documentos</option>' + tiposDocumento.map(item =>
            `<option value="${item.id}">${esc(item.descripcion_documento || item.descripcion)}</option>`
        ).join('');
    }
}

function obtenerMetaDocumento() {
    const option = document.querySelector('#cTipoDocumento option:checked');
    return {
        codigo: option?.dataset.codigo || '',
        label: option?.dataset.label || 'Documento'
    };
}

function actualizarEstadoDocumento() {
    const meta = obtenerMetaDocumento();
    // El tipo (persona/empresa) se deduce del tipo de documento: RUC (codigo 6) = empresa
    const esEmpresa = meta.codigo === '6';
    document.getElementById('cTipo').value = esEmpresa ? 'empresa' : 'persona';
    document.getElementById('labelRazonSocial').textContent = esEmpresa ? 'Razon social' : 'Nombre completo';
    document.getElementById('cRazonSocial').placeholder = esEmpresa ? 'Razon social del cliente' : 'Nombre completo del cliente';
    document.getElementById('cNumeroDocumento').placeholder = meta.codigo === '6' ? '20123456789' : (meta.codigo === '1' ? '12345678' : 'Ingrese el documento');
    document.getElementById('lookupNote').textContent = ['1','6'].includes(meta.codigo)
        ? 'Consulta automatica disponible para DNI y RUC.'
        : 'Para este tipo de documento el ingreso es manual.';
    document.getElementById('docChip').innerHTML = `<i class="fa fa-id-card"></i> ${esc(meta.label)}`;
}

async function cargarKpis() {
    try {
        const d = await apiGet(`${BASE}?action=kpis`);
        document.getElementById('kpiTotal').textContent = d.kpis.total;
        document.getElementById('kpiActivos').textContent = d.kpis.activos;
        document.getElementById('kpiProspectos').textContent = d.kpis.prospectos;
        document.getElementById('kpiInactivos').textContent = d.kpis.inactivos;
    } catch (e) {}
}

async function cargarClientes() {
    const buscar = document.getElementById('buscar').value.trim();
    const tipoDoc = document.getElementById('filtroTipoDoc').value;
    const params = new URLSearchParams({ action: 'listar' });
    if (buscar) params.set('buscar', buscar);
    if (tipoDoc) params.set('tipo_documento_id', tipoDoc);

    document.getElementById('tbody').innerHTML = '<tr><td colspan="6" class="text-center text-muted"><div class="spinner" style="margin:0 auto"></div></td></tr>';

    try {
        const d = await apiGet(`${BASE}?${params.toString()}`);
        renderTabla(d.clientes || []);
    } catch (e) {
        document.getElementById('tbody').innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error al cargar clientes.</td></tr>';
    }
}

function renderTabla(clientes) {
    const tbody = document.getElementById('tbody');
    if (!clientes.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No hay clientes con los filtros actuales.</td></tr>';
        return;
    }
    tbody.innerHTML = clientes.map(c => {
        const tel = [c.telefono, c.whatsapp].filter(Boolean).join(' / ') || '—';
        return `<tr onclick="editarCliente(${c.id})" style="cursor:pointer" title="Editar cliente">
            <td>${badgeTipo(c.tipo)}</td>
            <td>
                <div class="font-semibold">${esc(c.razon_social || c.nombre_completo || '—')}</div>
            </td>
            <td>
                <div>${esc(c.descripcion_documento || 'Documento')}</div>
                <div class="font-semibold">${esc(c.numero_documento || c.ruc_dni || '—')}</div>
            </td>
            <td>
                ${c.email ? `<a href="mailto:${esc(c.email)}" class="text-primary" onclick="event.stopPropagation()">${esc(c.email)}</a>` : '—'}
            </td>
            <td>${esc(tel)}</td>
            <td>${badgeEstado(c.estado_crm)}</td>
        </tr>`;
    }).join('');
}

function badgeTipo(tipo) {
    return tipo === 'empresa'
        ? '<span class="badge badge-normal"><i class="fa fa-building"></i> Empresa</span>'
        : '<span class="badge badge-pruebas"><i class="fa fa-user"></i> Persona</span>';
}

function badgeEstado(estado) {
    const map = { activo: 'badge-completado', prospecto: 'badge-fabricacion', inactivo: 'badge-cancelado' };
    const labels = { activo: 'Activo', prospecto: 'Prospecto', inactivo: 'Inactivo' };
    return `<span class="badge ${map[estado] || 'badge-normal'}">${labels[estado] || estado}</span>`;
}

function abrirModalCliente() {
    document.getElementById('cId').value = '';
    document.getElementById('cTipo').value = 'persona';
    document.getElementById('cEstado').value = 'prospecto';
    document.getElementById('cRazonSocial').value = '';
    document.getElementById('cContacto').value = '';
    document.getElementById('cNumeroDocumento').value = '';
    document.getElementById('cEmail').value = '';
    document.getElementById('cTelefono').value = '';
    document.getElementById('cWhatsapp').value = '';
    document.getElementById('cDireccion').value = '';
    document.getElementById('cNotas').value = '';
    document.getElementById('cUbigeo').value = '';
    document.getElementById('ubigeoInfo').textContent = 'Sin ubigeo consultado.';
    document.getElementById('modalClienteTitulo').textContent = 'Nuevo Cliente';
    const pred = tiposDocumento.find(item => item.codigo === '1') || tiposDocumento[0];
    if (pred) document.getElementById('cTipoDocumento').value = String(pred.id);
    actualizarEstadoDocumento();
    Modal.open('modalCliente');
}

async function editarCliente(id) {
    const d = await apiGet(`${BASE}?action=obtener&id=${id}`);
    const c = d.cliente;
    document.getElementById('cId').value = c.id;
    document.getElementById('cTipo').value = c.tipo || 'persona';
    document.getElementById('cEstado').value = c.estado_crm || 'prospecto';
    document.getElementById('cRazonSocial').value = c.razon_social || c.nombre_completo || '';
    document.getElementById('cContacto').value = c.contacto_nombre || '';
    document.getElementById('cNumeroDocumento').value = c.numero_documento || c.ruc_dni || '';
    document.getElementById('cEmail').value = c.email || '';
    document.getElementById('cTelefono').value = c.telefono || '';
    document.getElementById('cWhatsapp').value = c.whatsapp || '';
    document.getElementById('cDireccion').value = c.direccion || '';
    document.getElementById('cNotas').value = c.notas || '';
    document.getElementById('cUbigeo').value = c.ubigeo || '';
    document.getElementById('ubigeoInfo').textContent = c.ubigeo ? `Ubigeo consultado: ${c.ubigeo}` : 'Sin ubigeo consultado.';
    if (c.tipo_documento_id) document.getElementById('cTipoDocumento').value = c.tipo_documento_id;
    document.getElementById('modalClienteTitulo').textContent = 'Editar Cliente';
    actualizarEstadoDocumento();
    Modal.open('modalCliente');
}

async function consultarDocumento() {
    const tipo_documento_id = document.getElementById('cTipoDocumento').value;
    const numero_documento = document.getElementById('cNumeroDocumento').value.trim();
    if (!numero_documento) return Toast.warning('Ingresa el numero de documento.');

    const btn = document.getElementById('btnConsultarDocumento');
    const prev = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Consultando';
    try {
        const r = await apiPost(BASE, { action: 'lookup_document', tipo_documento_id, numero_documento });
        if (!r.ok) throw new Error(r.error || 'No se pudo consultar el documento.');
        document.getElementById('cRazonSocial').value = r.cliente.razon_social || '';
        if (r.cliente.contacto_nombre) document.getElementById('cContacto').value = r.cliente.contacto_nombre;
        if (r.cliente.direccion) document.getElementById('cDireccion').value = r.cliente.direccion;
        document.getElementById('cUbigeo').value = r.cliente.ubigeo || '';
        document.getElementById('ubigeoInfo').textContent = r.cliente.ubigeo
            ? `Ubigeo consultado: ${r.cliente.ubigeo}`
            : 'Documento consultado sin ubigeo disponible.';
        Toast.success('Documento consultado correctamente.');
    } catch (e) {
        Toast.error(e.message || 'No se pudo consultar el documento.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = prev;
    }
}

async function guardarCliente() {
    const razonSocial = document.getElementById('cRazonSocial').value.trim();
    if (!razonSocial) return Toast.warning('Completa el nombre o razon social.');
    const id = document.getElementById('cId').value;
    const payload = {
        action: id ? 'actualizar' : 'crear',
        id,
        tipo: document.getElementById('cTipo').value,
        tipo_documento_id: document.getElementById('cTipoDocumento').value,
        numero_documento: document.getElementById('cNumeroDocumento').value.trim(),
        razon_social: razonSocial,
        contacto_nombre: document.getElementById('cContacto').value.trim(),
        email: document.getElementById('cEmail').value.trim(),
        telefono: document.getElementById('cTelefono').value.trim(),
        whatsapp: document.getElementById('cWhatsapp').value.trim(),
        direccion: document.getElementById('cDireccion').value.trim(),
        estado_crm: document.getElementById('cEstado').value,
        notas: document.getElementById('cNotas').value.trim(),
        ubigeo: document.getElementById('cUbigeo').value.trim(),
    };
    try {
        const r = await apiPost(BASE, payload);
        if (!r.ok) throw new Error(r.error || 'Error al guardar.');
        Toast.success(id ? 'Cliente actualizado.' : 'Cliente creado.');
        Modal.close('modalCliente');
        cargarClientes();
        cargarKpis();
    } catch (e) {
        Toast.error(e.message || 'Error al guardar.');
    }
}

async function eliminarCliente(id, nombre) {
    if (!confirm(`¿Desactivar al cliente "${nombre}"?\nNo se eliminara permanentemente.`)) return;
    try {
        const r = await apiPost(BASE, { action: 'eliminar', id });
        if (!r.ok) throw new Error(r.error || 'Error al desactivar.');
        Toast.success('Cliente desactivado.');
        cargarClientes();
        cargarKpis();
    } catch (e) {
        Toast.error(e.message || 'Error al desactivar.');
    }
}

async function verDetalle(id) {
    document.getElementById('detalleTitulo').textContent = 'Cargando...';
    document.getElementById('detalleBody').innerHTML = '<div class="loading"><div class="spinner"></div></div>';
    document.getElementById('btnEditarDesdeDetalle').onclick = () => { Modal.close('modalDetalle'); editarCliente(id); };
    Modal.open('modalDetalle');

    try {
        const [dc, dp] = await Promise.all([
            apiGet(`${BASE}?action=obtener&id=${id}`),
            apiGet(`${BASE}?action=proyectos&id=${id}`)
        ]);
        const c = dc.cliente;
        const proyectos = dp.proyectos || [];
        document.getElementById('detalleTitulo').textContent = c.razon_social || c.nombre_completo || 'Cliente';
        document.getElementById('detalleBody').innerHTML = `
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:18px">
                <div><div class="text-xs text-muted text-uppercase mb-1">Tipo</div><div>${badgeTipo(c.tipo)} &nbsp; ${badgeEstado(c.estado_crm)}</div></div>
                <div><div class="text-xs text-muted text-uppercase mb-1">Documento</div><div class="font-semibold">${esc(c.numero_documento || c.ruc_dni || '—')}</div></div>
                <div><div class="text-xs text-muted text-uppercase mb-1">Contacto</div><div>${esc(c.contacto_nombre || '—')}</div></div>
                <div><div class="text-xs text-muted text-uppercase mb-1">Correo</div><div>${c.email ? `<a href="mailto:${esc(c.email)}" class="text-primary">${esc(c.email)}</a>` : '—'}</div></div>
                <div><div class="text-xs text-muted text-uppercase mb-1">Telefono</div><div>${esc(c.telefono || '—')}</div></div>
                <div><div class="text-xs text-muted text-uppercase mb-1">WhatsApp</div><div>${esc(c.whatsapp || '—')}</div></div>
                <div style="grid-column:span 2"><div class="text-xs text-muted text-uppercase mb-1">Direccion</div><div>${esc(c.direccion || '—')}</div></div>
                <div style="grid-column:span 2"><div class="text-xs text-muted text-uppercase mb-1">Notas / Observaciones</div><div>${esc(c.notas || '—')}</div></div>
            </div>
            <hr style="border:none;border-top:1px solid var(--border);margin-bottom:14px">
            <div style="font-weight:700;font-size:.85rem;margin-bottom:10px"><i class="fa fa-industry"></i> Proyectos vinculados <span class="badge badge-normal" style="margin-left:6px">${proyectos.length}</span></div>
            ${proyectos.length === 0
                ? '<p class="text-muted text-sm">Este cliente no tiene proyectos registrados.</p>'
                : `<div class="table-responsive"><table><thead><tr><th>Codigo</th><th>Nombre</th><th>Estado</th><th class="text-right">Avance</th><th class="text-right">Costo Est.</th></tr></thead><tbody>${proyectos.map(p => `<tr><td><a href="../produccion/proyecto.php?id=${p.id}" class="text-primary font-semibold" target="_blank">${esc(p.codigo)}</a></td><td>${esc(p.nombre || '—')}</td><td><span class="badge badge-${p.estado}">${esc(p.estado)}</span></td><td class="text-right">${p.porcentaje_avance}%</td><td class="text-right">${formatMoney(p.costo_estimado || 0)}</td></tr>`).join('')}</tbody></table></div>`}
        `;
    } catch (e) {
        document.getElementById('detalleBody').innerHTML = '<div class="alert alert-danger">Error al cargar el detalle.</div>';
    }
}

// ── PROVEEDORES ─────────────────────────────────────────────
async function cargarProveedores() {
    const q      = document.getElementById('provSearch').value.trim();
    const activo = document.getElementById('provFiltroActivo').value;
    const params = new URLSearchParams({ action: 'proveedores_listar' });
    if (q) params.set('q', q);
    if (activo) params.set('activo', activo);

    const tbody = document.getElementById('tbodyProv');
    tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted"><div class="spinner" style="margin:0 auto"></div></td></tr>';
    try {
        const d = await apiGet(`${BASE}?${params.toString()}`);
        const provs = d.proveedores || [];
        if (!provs.length) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No hay proveedores con los filtros actuales.</td></tr>';
            return;
        }
        tbody.innerHTML = provs.map(p => `<tr>
            <td><strong>${esc(p.razon_social)}</strong></td>
            <td>${esc(p.ruc || '—')}</td>
            <td>${esc(p.contacto_nombre || '—')}</td>
            <td>${esc(p.telefono || '—')}</td>
            <td>${estrellasProv(p.calificacion)}</td>
            <td class="text-center">${p.total_oc || 0}</td>
            <td>${p.activo
                ? '<span class="badge badge-completado">Activo</span>'
                : '<span class="badge badge-cancelado">Inactivo</span>'}</td>
            <td>
                <div style="display:flex;gap:.25rem">
                    <button class="btn btn-outline btn-xs" onclick="editarProveedor(${p.id})" title="Editar"><i class="fa fa-pencil"></i></button>
                    <button class="btn btn-outline btn-xs" onclick="toggleProveedor(${p.id})" title="${p.activo ? 'Desactivar' : 'Activar'}"><i class="fa fa-power-off"></i></button>
                </div>
            </td>
        </tr>`).join('');
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Error al cargar proveedores.</td></tr>';
    }
}

function estrellasProv(cal) {
    if (!cal) return '<span class="text-muted">—</span>';
    const n = parseFloat(cal);
    let html = '';
    for (let i = 1; i <= 5; i++) {
        html += `<i class="fa fa-star" style="color:${i <= n ? '#f97316' : '#e2e8f0'};font-size:.75rem"></i>`;
    }
    return html + ` <small class="text-muted">(${n})</small>`;
}

function abrirModalProveedor() {
    document.getElementById('prov_id').value = '';
    document.getElementById('prov_razon_social').value = '';
    document.getElementById('prov_ruc').value = '';
    document.getElementById('prov_contacto').value = '';
    document.getElementById('prov_email').value = '';
    document.getElementById('prov_telefono').value = '';
    document.getElementById('prov_direccion').value = '';
    document.getElementById('prov_activo').value = '1';
    document.getElementById('modalProvTitulo').textContent = 'Nuevo Proveedor';
    Modal.open('modalProveedor');
}

async function editarProveedor(id) {
    try {
        const d = await apiGet(`${BASE}?action=proveedor_obtener&id=${id}`);
        const p = d.proveedor;
        document.getElementById('prov_id').value           = p.id;
        document.getElementById('prov_razon_social').value = p.razon_social || '';
        document.getElementById('prov_ruc').value          = p.ruc || '';
        document.getElementById('prov_contacto').value     = p.contacto_nombre || '';
        document.getElementById('prov_email').value        = p.email || '';
        document.getElementById('prov_telefono').value     = p.telefono || '';
        document.getElementById('prov_direccion').value    = p.direccion || '';
        document.getElementById('prov_activo').value       = p.activo ? '1' : '0';
        document.getElementById('modalProvTitulo').textContent = 'Editar Proveedor';
        Modal.open('modalProveedor');
    } catch (e) {
        Toast.error(e.message || 'No se pudo cargar el proveedor.');
    }
}

async function guardarProveedor() {
    const rs = document.getElementById('prov_razon_social').value.trim();
    if (!rs) return Toast.warning('La razon social es obligatoria.');
    const id = document.getElementById('prov_id').value;
    const body = {
        action:          'proveedor_guardar',
        id:              id || null,
        razon_social:    rs,
        ruc:             document.getElementById('prov_ruc').value.trim(),
        contacto_nombre: document.getElementById('prov_contacto').value.trim(),
        email:           document.getElementById('prov_email').value.trim(),
        telefono:        document.getElementById('prov_telefono').value.trim(),
        direccion:       document.getElementById('prov_direccion').value.trim(),
        activo:          document.getElementById('prov_activo').value === '1',
    };
    try {
        const r = await apiPost(BASE, body);
        if (!r.ok) throw new Error(r.error || 'Error al guardar.');
        Toast.success(id ? 'Proveedor actualizado.' : 'Proveedor creado.');
        Modal.close('modalProveedor');
        cargarProveedores();
    } catch (e) {
        Toast.error(e.message || 'Error al guardar.');
    }
}

async function toggleProveedor(id) {
    try {
        const r = await apiPost(BASE, { action: 'proveedor_toggle', id });
        if (!r.ok) throw new Error(r.error || 'Error.');
        Toast.success('Estado actualizado.');
        cargarProveedores();
    } catch (e) {
        Toast.error(e.message || 'Error al actualizar.');
    }
}

function esc(value) {
    const d = document.createElement('div');
    d.textContent = String(value ?? '');
    return d.innerHTML;
}

function escJs(value) {
    return String(value ?? '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
}
</script>
<?php
$extra_js = ob_get_clean();
require_once __DIR__ . '/../../includes/footer.php';

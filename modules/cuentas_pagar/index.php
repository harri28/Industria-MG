<?php
$page_title      = 'Cuentas por Pagar';
$page_breadcrumb = 'Finanzas › Cuentas por Pagar';
require_once __DIR__ . '/../../includes/header.php';
?>

<style>
/* ── Master-Detail ── */
#cpGrid { display:grid; grid-template-columns:1fr 360px; gap:14px; align-items:start; }
#cpPanel {
    position:sticky; top:76px;
    border:1px solid var(--border); border-radius:10px;
    background:#fff; overflow:hidden;
}
#cpPanelContent { display:none; }
#cpPanelEmpty {
    padding:48px 24px; text-align:center; color:var(--text-muted);
}
.cp-det-header { padding:16px 20px; border-bottom:1px solid var(--border); }
.cp-det-grid {
    display:grid; grid-template-columns:1fr 1fr; gap:6px 16px;
    padding:14px 20px; border-bottom:1px solid var(--border);
    font-size:.82rem; color:var(--text-muted);
}
.cp-det-grid span { color:var(--text); font-weight:600; }
.cp-totals {
    display:grid; grid-template-columns:repeat(3,1fr); gap:8px;
    padding:12px 16px; background:var(--bg-secondary);
    border-bottom:1px solid var(--border); text-align:center;
}
.cp-totals .label { font-size:.7rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:.04em; }
.cp-totals .valor { font-size:.95rem; font-weight:700; margin-top:2px; }
#cpPagos { padding:14px 20px; border-bottom:1px solid var(--border); min-height:60px; }
#cpAcciones { padding:12px 20px; display:flex; gap:8px; flex-wrap:wrap; }

/* Aging cards */
.aging-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:10px; margin-bottom:18px; }
.aging-card { border-radius:8px; padding:14px; text-align:center; border:1px solid var(--border); }
.aging-card .a-label { font-size:.72rem; text-transform:uppercase; letter-spacing:.05em; color:var(--text-muted); margin-bottom:4px; }
.aging-card .a-monto { font-size:1rem; font-weight:700; }
.aging-card .a-cant  { font-size:.75rem; color:var(--text-muted); margin-top:2px; }
@media(max-width:900px) { #cpGrid { grid-template-columns:1fr; } .aging-grid { grid-template-columns:1fr 1fr; } }
</style>

<div class="toolbar">
    <div class="toolbar-left">
        <input type="text" id="filtroBuscar" class="form-control" placeholder="Buscar proveedor, nro doc…"
               style="width:200px" oninput="cargarCuentas()">
        <select id="filtroEstado" class="form-control" style="width:140px" onchange="cargarCuentas()">
            <option value="todas">Todos los estados</option>
            <option value="pendiente">Pendiente</option>
            <option value="parcial">Pago parcial</option>
            <option value="vencida">Vencidas</option>
            <option value="pagada">Pagadas</option>
            <option value="anulada">Anuladas</option>
        </select>
    </div>
    <div class="toolbar-right">
        <button class="btn btn-primary" onclick="abrirModalNueva()">
            <i class="fa fa-plus"></i> Nueva Cuenta
        </button>
    </div>
</div>

<!-- Tabs -->
<div class="tabs" style="margin-bottom:14px">
    <div class="tab active" data-group="cp" data-target="tabCuentas">Cuentas</div>
    <div class="tab"        data-group="cp" data-target="tabResumen">Resumen / Aging</div>
</div>

<!-- ═══ TAB: CUENTAS ═══════════════════════════════════════════════ -->
<div class="tab-content active" data-group="cp" id="tabCuentas">
    <div id="cpGrid">
        <!-- Tabla izquierda -->
        <div class="card" style="overflow:hidden">
            <table class="table" style="margin:0">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Proveedor</th>
                        <th>Doc.</th>
                        <th>Vencimiento</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Pendiente</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody id="tbCuentas">
                    <tr><td colspan="7" class="text-center text-muted">Cargando…</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Panel derecho -->
        <div id="cpPanel">
            <div id="cpPanelEmpty">
                <i class="fa fa-file-invoice" style="font-size:2.5rem;opacity:.2;margin-bottom:10px;display:block"></i>
                Selecciona una cuenta para ver el detalle
            </div>
            <div id="cpPanelContent">
                <div class="cp-det-header">
                    <div style="font-weight:700;font-size:1rem" id="cpNumero"></div>
                    <div style="font-size:.82rem;color:var(--text-muted);margin-top:2px" id="cpProveedor"></div>
                    <div style="margin-top:6px" id="cpBadge"></div>
                </div>
                <div class="cp-det-grid">
                    <div>Emisión:<br><span id="cpEmision"></span></div>
                    <div>Vencimiento:<br><span id="cpVence"></span></div>
                    <div id="cpOcWrap" style="display:none">Orden Compra:<br><span id="cpOc"></span></div>
                </div>
                <div id="cpConceptoWrap" style="padding:0 20px 12px;font-size:.82rem;color:var(--text-muted);display:none">
                    Concepto: <span id="cpConcepto" style="color:var(--text)"></span>
                </div>
                <div class="cp-totals">
                    <div><div class="label">Total</div><div class="valor" id="cpTotal"></div></div>
                    <div><div class="label">Pagado</div><div class="valor" style="color:#16a34a" id="cpPagado"></div></div>
                    <div><div class="label">Pendiente</div><div class="valor" style="color:#d97706" id="cpPendiente"></div></div>
                </div>
                <div style="padding:10px 20px 6px;font-weight:600;font-size:.78rem;text-transform:uppercase;
                            letter-spacing:.04em;color:var(--text-muted)">Pagos registrados</div>
                <div id="cpPagos"></div>
                <div id="cpAcciones"></div>
            </div>
        </div>
    </div>
</div>

<!-- ═══ TAB: RESUMEN / AGING ══════════════════════════════════════ -->
<div class="tab-content" data-group="cp" id="tabResumen">
    <div id="resumenContent">
        <div class="text-center text-muted" style="padding:32px">Cargando…</div>
    </div>
</div>

<!-- ══ Modal Nueva / Editar Cuenta ══════════════════════════════════ -->
<div class="modal-overlay" id="modalNuevaCuenta">
    <div class="modal" style="max-width:500px">
        <div class="modal-header">
            <span class="modal-title" id="modalNuevaTitulo"><i class="fa fa-file-invoice"></i> Nueva Cuenta por Pagar</span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="cuentaEditId">
            <div class="form-group">
                <label class="form-label">Proveedor *</label>
                <input type="text" class="form-control" id="cuentaProveedor" placeholder="Nombre del proveedor">
            </div>
            <div class="form-group">
                <label class="form-label">Concepto / descripción *</label>
                <textarea class="form-control" id="cuentaConcepto" rows="2"
                    placeholder="Ej: F001-00123 – Compra de materiales"></textarea>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group">
                    <label class="form-label">Fecha emisión *</label>
                    <input type="date" class="form-control" id="cuentaFechaEmision">
                </div>
                <div class="form-group">
                    <label class="form-label">Fecha vencimiento *</label>
                    <input type="date" class="form-control" id="cuentaFechaVence">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Monto total (S/) *</label>
                <input type="number" class="form-control" id="cuentaTotal" min="0.01" step="0.01" placeholder="0.00">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalNuevaCuenta')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarCuenta()">
                <i class="fa fa-save"></i> Guardar
            </button>
        </div>
    </div>
</div>

<!-- ══ Modal Registrar Pago ════════════════════════════════════════ -->
<div class="modal-overlay" id="modalPago">
    <div class="modal" style="max-width:420px">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-money-bill-wave"></i> Registrar Pago</span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="pagoCuentaId">
            <div id="pagoInfoCuenta" style="background:var(--bg-secondary);border-radius:6px;
                 padding:10px 14px;margin-bottom:14px;font-size:.85rem"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group">
                    <label class="form-label">Fecha *</label>
                    <input type="date" class="form-control" id="pagoFecha">
                </div>
                <div class="form-group">
                    <label class="form-label">Monto (S/) *</label>
                    <input type="number" class="form-control" id="pagoMonto" min="0.01" step="0.01">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Método de pago *</label>
                <select class="form-control" id="pagoMetodo">
                    <option value="efectivo">Efectivo</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="cheque">Cheque</option>
                    <option value="deposito">Depósito</option>
                    <option value="otros">Otros</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Referencia / N° operación</label>
                <input type="text" class="form-control" id="pagoReferencia" placeholder="Nro de transferencia, cheque, etc.">
            </div>
            <div class="form-group">
                <label class="form-label">Observaciones</label>
                <input type="text" class="form-control" id="pagoObs">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalPago')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarPago()">
                <i class="fa fa-save"></i> Registrar
            </button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
const BASE = '<?= APP_BASE ?>modules/cuentas_pagar/api.php';
let _cpId = null;

// ── Inicialización ─────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    initTabs();
    cargarCuentas();
    document.querySelector('[data-target="tabResumen"]').addEventListener('click', cargarResumen);
    const hoy = new Date().toISOString().slice(0, 10);
    document.getElementById('cuentaFechaEmision').value = hoy;
    document.getElementById('pagoFecha').value = hoy;
});

// ── BADGE ──────────────────────────────────────────────────────────────────
function badgeCP(estado, vencida) {
    if (vencida && estado !== 'pagada' && estado !== 'anulada')
        return badge('vencida', 'Vencida');
    const map = {
        pendiente: ['pendiente', 'Pendiente'],
        parcial:   ['parcial',  'Pago parcial'],
        pagada:    ['completado','Pagada'],
        anulada:   ['anulada',  'Anulada'],
    };
    const [cls, txt] = map[estado] || ['secondary', estado];
    return badge(cls, txt);
}

function badgeMetodo(m) {
    const map = { efectivo:'Efectivo', transferencia:'Transferencia', cheque:'Cheque',
                  deposito:'Depósito', otros:'Otros' };
    return `<span style="background:#f1f5f9;border-radius:4px;padding:1px 7px;font-size:.75rem">${map[m]||m}</span>`;
}

function esc(s) { const d=document.createElement('div'); d.textContent=s??''; return d.innerHTML; }

// ── LISTAR ─────────────────────────────────────────────────────────────────
async function cargarCuentas() {
    const q      = document.getElementById('filtroBuscar').value.trim();
    const estado = document.getElementById('filtroEstado').value;
    try {
        const r = await apiGet(`${BASE}?action=listar&q=${encodeURIComponent(q)}&estado=${estado}`);
        const cuentas = r.cuentas || [];
        const tb = document.getElementById('tbCuentas');
        if (!cuentas.length) {
            tb.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Sin resultados</td></tr>';
            return;
        }
        tb.innerHTML = cuentas.map(c => {
            const vencida = c.vencida === true || c.vencida === 't';
            const rowBg   = vencida && c.estado !== 'pagada' ? 'background:#fff7ed' : '';
            const sel     = _cpId === c.id ? 'background:var(--primary-light,#eff6ff)' : rowBg;
            return `<tr id="cpfila-${c.id}" style="cursor:pointer;${sel}" onclick="verCuenta(${c.id})">
                <td style="font-size:.82rem;font-weight:600">${esc(c.codigo)}</td>
                <td style="font-size:.82rem">${esc(c.proveedor||'—')}</td>
                <td style="font-size:.8rem;color:var(--text-muted);max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                    title="${esc(c.concepto||'')}">${esc(c.concepto||'—')}</td>
                <td style="font-size:.82rem${vencida && c.estado !== 'pagada' ? ';color:#d97706;font-weight:600' : ''}">${formatDate(c.fecha_vencimiento)}</td>
                <td class="text-right" style="font-size:.82rem">${formatMoney(c.total)}</td>
                <td class="text-right" style="font-size:.82rem;font-weight:600">${formatMoney(c.pendiente)}</td>
                <td>${badgeCP(c.estado, vencida)}</td>
            </tr>`;
        }).join('');
    } catch(e) { Toast.error(e.message); }
}

// ── VER DETALLE ────────────────────────────────────────────────────────────
async function verCuenta(id) {
    document.querySelectorAll('#tbCuentas tr').forEach(r => r.style.background = '');
    const fila = document.getElementById(`cpfila-${id}`);
    if (fila) fila.style.background = 'var(--primary-light,#eff6ff)';

    if (_cpId === id) return;
    _cpId = id;

    document.getElementById('cpPanelEmpty').style.display = 'none';
    document.getElementById('cpPanelContent').style.display = 'block';
    document.getElementById('cpPagos').innerHTML =
        '<div class="text-muted" style="font-size:.82rem">Cargando…</div>';

    try {
        const r = await apiGet(`${BASE}?action=obtener&id=${id}`);
        const c = r.data;
        const vencida = c.vencida === true || c.vencida === 't';
        const activa  = c.estado !== 'pagada' && c.estado !== 'anulada';

        document.getElementById('cpNumero').textContent    = c.codigo;
        document.getElementById('cpProveedor').textContent = c.proveedor || '—';
        document.getElementById('cpBadge').innerHTML       = badgeCP(c.estado, vencida)
            + (vencida && activa ? `<span style="font-size:.75rem;color:#d97706;margin-left:8px">
               ${c.dias_vencida} día${c.dias_vencida==1?'':'s'} vencida</span>` : '');
        document.getElementById('cpEmision').textContent   = formatDate(c.fecha_emision);
        document.getElementById('cpVence').textContent     = formatDate(c.fecha_vencimiento);
        document.getElementById('cpTotal').textContent     = formatMoney(c.total);
        document.getElementById('cpPagado').textContent    = formatMoney(c.total_pagado);
        document.getElementById('cpPendiente').textContent = formatMoney(c.pendiente);

        const ocW = document.getElementById('cpOcWrap');
        if (c.oc_numero) { ocW.style.display=''; document.getElementById('cpOc').textContent = c.oc_numero; }
        else { ocW.style.display='none'; }

        const cW = document.getElementById('cpConceptoWrap');
        if (c.concepto) { cW.style.display=''; document.getElementById('cpConcepto').textContent = c.concepto; }
        else { cW.style.display='none'; }

        // Pagos
        document.getElementById('cpPagos').innerHTML = c.pagos.length
            ? c.pagos.map(p => `
                <div style="display:flex;justify-content:space-between;align-items:center;
                            padding:7px 0;border-bottom:1px solid var(--border);font-size:.82rem">
                    <div>
                        <div style="font-weight:600">${formatMoney(p.monto)}</div>
                        <div style="color:var(--text-muted)">${formatDate(p.fecha)} · ${badgeMetodo(p.metodo_pago)}</div>
                        ${p.referencia ? `<div style="color:var(--text-muted)">${esc(p.referencia)}</div>` : ''}
                    </div>
                    ${activa ? `<button class="btn btn-xs btn-outline" style="color:var(--danger)"
                        onclick="eliminarPago(${p.id},${c.id})" title="Eliminar pago">
                        <i class="fa fa-trash"></i></button>` : ''}
                </div>`).join('')
            : '<div class="text-muted" style="font-size:.82rem">Sin pagos registrados.</div>';

        document.getElementById('cpAcciones').innerHTML = activa ? `
            <button class="btn btn-primary btn-sm" onclick="abrirModalPago(${c.id}, ${c.pendiente})">
                <i class="fa fa-plus"></i> Registrar Pago
            </button>
            <button class="btn btn-outline btn-sm" onclick="abrirEditarCuenta(${c.id})">
                <i class="fa fa-edit"></i> Editar
            </button>
            <button class="btn btn-outline btn-sm" style="color:var(--danger)" onclick="anularCuenta(${c.id})">
                <i class="fa fa-ban"></i> Anular
            </button>` : '';

    } catch(e) { Toast.error(e.message); }
}

// ── NUEVA / EDITAR CUENTA ─────────────────────────────────────────────────
function abrirModalNueva() {
    document.getElementById('cuentaEditId').value = '';
    document.getElementById('modalNuevaTitulo').innerHTML = '<i class="fa fa-file-invoice"></i> Nueva Cuenta por Pagar';
    document.getElementById('cuentaProveedor').value    = '';
    document.getElementById('cuentaConcepto').value     = '';
    document.getElementById('cuentaTotal').value        = '';
    document.getElementById('cuentaFechaVence').value   = '';
    document.getElementById('cuentaFechaEmision').value = new Date().toISOString().slice(0,10);
    Modal.open('modalNuevaCuenta');
}

async function abrirEditarCuenta(id) {
    try {
        const r = await apiGet(`${BASE}?action=obtener&id=${id}`);
        const c = r.data;
        document.getElementById('cuentaEditId').value      = c.id;
        document.getElementById('modalNuevaTitulo').innerHTML = '<i class="fa fa-edit"></i> Editar Cuenta';
        document.getElementById('cuentaProveedor').value   = c.proveedor   || '';
        document.getElementById('cuentaConcepto').value    = c.concepto    || '';
        document.getElementById('cuentaTotal').value       = c.total;
        document.getElementById('cuentaFechaEmision').value = c.fecha_emision;
        document.getElementById('cuentaFechaVence').value  = c.fecha_vencimiento;
        Modal.open('modalNuevaCuenta');
    } catch(e) { Toast.error(e.message); }
}

async function guardarCuenta() {
    const id      = document.getElementById('cuentaEditId').value;
    const prov    = document.getElementById('cuentaProveedor').value.trim();
    const total   = parseFloat(document.getElementById('cuentaTotal').value);
    const fechaVe = document.getElementById('cuentaFechaVence').value;

    if (!prov)   { Toast.warning('Ingresa el nombre del proveedor'); return; }
    if (!fechaVe){ Toast.warning('Ingresa la fecha de vencimiento'); return; }
    if (!total || total <= 0) { Toast.warning('Ingresa un monto válido'); return; }

    const body = {
        action:            id ? 'editar' : 'crear',
        id:                id || undefined,
        proveedor:         prov,
        concepto:          document.getElementById('cuentaConcepto').value.trim(),
        fecha_emision:     document.getElementById('cuentaFechaEmision').value,
        fecha_vencimiento: fechaVe,
        total,
    };

    try {
        const r = await apiPost(BASE, body);
        if (r.ok) {
            Toast.success(id ? 'Cuenta actualizada' : `Cuenta ${r.codigo} creada`);
            Modal.close('modalNuevaCuenta');
            _cpId = null;
            cargarCuentas();
        } else { Toast.error(r.error || 'Error'); }
    } catch(e) { Toast.error(e.message); }
}

async function anularCuenta(id) {
    if (!confirm('¿Anular esta cuenta? No se podrán registrar más pagos.')) return;
    try {
        await apiPost(BASE, { action: 'anular', id });
        Toast.success('Cuenta anulada');
        _cpId = null;
        document.getElementById('cpPanelContent').style.display = 'none';
        document.getElementById('cpPanelEmpty').style.display   = '';
        cargarCuentas();
    } catch(e) { Toast.error(e.message); }
}

// ── PAGOS ──────────────────────────────────────────────────────────────────
function abrirModalPago(cuentaId, pendiente) {
    document.getElementById('pagoCuentaId').value = cuentaId;
    document.getElementById('pagoMonto').value    = parseFloat(pendiente).toFixed(2);
    document.getElementById('pagoFecha').value    = new Date().toISOString().slice(0,10);
    document.getElementById('pagoMetodo').value   = 'efectivo';
    document.getElementById('pagoReferencia').value = '';
    document.getElementById('pagoObs').value      = '';
    document.getElementById('pagoInfoCuenta').innerHTML =
        `Pendiente: <strong>${formatMoney(pendiente)}</strong>`;
    Modal.open('modalPago');
}

async function guardarPago() {
    const cuentaId = document.getElementById('pagoCuentaId').value;
    const monto    = parseFloat(document.getElementById('pagoMonto').value);
    const fecha    = document.getElementById('pagoFecha').value;
    if (!fecha)    { Toast.warning('Ingresa la fecha del pago'); return; }
    if (!monto || monto <= 0) { Toast.warning('Ingresa un monto válido'); return; }

    try {
        const r = await apiPost(BASE, {
            action:        'pago_registrar',
            cuenta_id:     cuentaId,
            fecha,
            monto,
            metodo_pago:   document.getElementById('pagoMetodo').value,
            referencia:    document.getElementById('pagoReferencia').value.trim(),
            observaciones: document.getElementById('pagoObs').value.trim(),
        });
        if (r.ok) {
            Toast.success('Pago registrado');
            Modal.close('modalPago');
            _cpId = null;
            cargarCuentas();
            verCuenta(parseInt(cuentaId));
        } else { Toast.error(r.error || 'Error'); }
    } catch(e) { Toast.error(e.message); }
}

async function eliminarPago(pagoId, cuentaId) {
    if (!confirm('¿Eliminar este pago?')) return;
    try {
        await apiPost(BASE, { action: 'pago_eliminar', id: pagoId });
        Toast.success('Pago eliminado');
        _cpId = null;
        cargarCuentas();
        verCuenta(cuentaId);
    } catch(e) { Toast.error(e.message); }
}

// ── RESUMEN / AGING ────────────────────────────────────────────────────────
async function cargarResumen() {
    const el = document.getElementById('resumenContent');
    try {
        const r   = await apiGet(`${BASE}?action=resumen`);
        const ag  = r.aging;
        const pe  = r.por_estado || [];

        const total = pe.reduce((s, e) => s + parseFloat(e.pendiente), 0);
        const pagadoTotal = pe.reduce((s, e) => s + parseFloat(e.pagado), 0);

        const estadoMap = {
            pendiente: ['#dbeafe','#1e40af','Pendiente'],
            parcial:   ['#fef9c3','#854d0e','Pago parcial'],
            pagada:    ['#dcfce7','#166534','Pagada'],
            anulada:   ['#f1f5f9','#64748b','Anulada'],
        };

        const estadoCards = pe.map(e => {
            const [bg, color, label] = estadoMap[e.estado] || ['#f1f5f9','#64748b', e.estado];
            return `<div style="border-radius:8px;padding:14px 18px;background:${bg};border:1px solid ${bg}">
                <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:${color};margin-bottom:4px">${label}</div>
                <div style="font-size:1.1rem;font-weight:700;color:${color}">${formatMoney(e.pendiente)}</div>
                <div style="font-size:.75rem;color:${color};opacity:.8">${e.cantidad} cuenta${e.cantidad==1?'':'s'}</div>
            </div>`;
        }).join('');

        const agingCols = [
            { label:'Al día',      cant: ag.al_dia,   monto: ag.monto_al_dia,  bg:'#dcfce7', color:'#166534' },
            { label:'1–30 días',   cant: ag.v_1_30,   monto: ag.monto_1_30,    bg:'#fef9c3', color:'#854d0e' },
            { label:'31–60 días',  cant: ag.v_31_60,  monto: ag.monto_31_60,   bg:'#ffedd5', color:'#c2410c' },
            { label:'61–90 días',  cant: ag.v_61_90,  monto: ag.monto_61_90,   bg:'#fee2e2', color:'#b91c1c' },
            { label:'+90 días',    cant: ag.v_90mas,  monto: ag.monto_90mas,   bg:'#fce7f3', color:'#9d174d' },
        ];

        const agingHtml = agingCols.map(a => `
            <div class="aging-card" style="background:${a.bg};border-color:${a.bg}">
                <div class="a-label" style="color:${a.color}">${a.label}</div>
                <div class="a-monto" style="color:${a.color}">${formatMoney(a.monto||0)}</div>
                <div class="a-cant">${a.cant||0} cuenta${(a.cant||0)==1?'':'s'}</div>
            </div>`).join('');

        el.innerHTML = `
            <div class="card" style="margin-bottom:16px">
                <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                    <span class="card-title"><i class="fa fa-chart-pie"></i> Por estado</span>
                    <div style="font-size:.85rem;color:var(--text-muted)">
                        Total pendiente: <strong>${formatMoney(total)}</strong> &nbsp;·&nbsp;
                        Total pagado: <strong>${formatMoney(pagadoTotal)}</strong>
                    </div>
                </div>
                <div style="padding:16px;display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px">
                    ${estadoCards || '<div class="text-muted">Sin datos</div>'}
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-clock-rotate-left"></i> Antigüedad de deuda (aging)</span>
                </div>
                <div style="padding:16px">
                    <div class="aging-grid">${agingHtml}</div>
                </div>
            </div>`;
    } catch(e) { el.innerHTML = `<div class="text-muted">${e.message}</div>`; }
}
</script>

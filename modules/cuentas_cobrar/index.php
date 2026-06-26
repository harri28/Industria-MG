<?php
$page_title      = 'Cuentas por Cobrar';
$page_breadcrumb = 'Finanzas › Cuentas por Cobrar';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="tabs" data-group="main">
    <button class="tab-btn active" data-tab="facturas">
        <i class="fa fa-file-invoice-dollar"></i> Facturas
    </button>
    <button class="tab-btn" data-tab="cobros">
        <i class="fa fa-money-bill-wave"></i> Cobros
    </button>
    <button class="tab-btn" data-tab="resumen">
        <i class="fa fa-chart-pie"></i> Resumen
    </button>
</div>

<!-- ═══ TAB FACTURAS ═══════════════════════════════════════════════════════ -->
<div id="tab-facturas" class="tab-content active">
    <div class="toolbar">
        <div class="toolbar-left">
            <input type="text" id="fBuscar" class="form-control" placeholder="Buscar número, cliente…" style="width:220px" oninput="cargarFacturas()">
            <select id="fEstado" class="form-control" onchange="cargarFacturas()">
                <option value="">Todos los estados</option>
                <option value="pendiente">Pendiente</option>
                <option value="parcial">Parcial</option>
                <option value="vencida">Vencida</option>
                <option value="cobrada">Cobrada</option>
                <option value="anulada">Anulada</option>
            </select>
        </div>
        <div class="toolbar-right">
            <button class="btn btn-primary" onclick="abrirModalFactura()">
                <i class="fa fa-plus"></i> Nueva Factura
            </button>
        </div>
    </div>

    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th>Número</th>
                    <th>Cliente</th>
                    <th>Concepto</th>
                    <th>Emisión</th>
                    <th>Vencimiento</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">Cobrado</th>
                    <th class="text-right">Pendiente</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tbFacturas">
                <tr><td colspan="10" class="text-center text-muted">Cargando…</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ═══ TAB COBROS ══════════════════════════════════════════════════════════ -->
<div id="tab-cobros" class="tab-content">
    <div class="toolbar">
        <div class="toolbar-left">
            <input type="text" id="cBuscar" class="form-control" placeholder="Buscar factura, cliente…" style="width:220px" oninput="cargarCobros()">
        </div>
    </div>

    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Factura</th>
                    <th>Cliente</th>
                    <th class="text-right">Monto</th>
                    <th>Método</th>
                    <th>Referencia</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tbCobros">
                <tr><td colspan="7" class="text-center text-muted">Cargando…</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ═══ TAB RESUMEN ═════════════════════════════════════════════════════════ -->
<div id="tab-resumen" class="tab-content">
    <div id="resumenContent">
        <div class="text-center text-muted" style="padding:40px">Cargando…</div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════════════════
     MODAL NUEVA / EDITAR FACTURA
════════════════════════════════════════════════════════════════════════ -->
<div id="modalFactura" class="modal">
    <div class="modal-content" style="max-width:600px">
        <div class="modal-header">
            <h3 id="modalFacturaTitulo">Nueva Factura</h3>
            <button class="modal-close" onclick="Modal.close('modalFactura')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="fId">
            <div class="form-row">
                <div class="form-group" style="flex:2">
                    <label>Cliente *</label>
                    <select id="fClienteId" class="form-control" onchange="cargarCotizaciones()" required></select>
                </div>
                <div class="form-group" style="flex:1">
                    <label>Cotización (opcional)</label>
                    <select id="fCotizacionId" class="form-control">
                        <option value="">— Ninguna —</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Concepto</label>
                <input type="text" id="fConcepto" class="form-control" placeholder="Descripción del servicio o producto">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Fecha Emisión *</label>
                    <input type="date" id="fFechaEmision" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Fecha Vencimiento *</label>
                    <input type="date" id="fFechaVencimiento" class="form-control" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Subtotal (S/) *</label>
                    <input type="number" id="fSubtotal" class="form-control" step="0.01" min="0.01" oninput="calcularTotal()" required>
                </div>
                <div class="form-group" style="flex:0;min-width:160px;display:flex;align-items:flex-end;gap:8px;padding-bottom:4px">
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;white-space:nowrap">
                        <input type="checkbox" id="fAplicaIgv" onchange="calcularTotal()"> Aplicar IGV (18%)
                    </label>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>IGV (S/)</label>
                    <input type="text" id="fIgv" class="form-control" readonly style="background:var(--bg-secondary)">
                </div>
                <div class="form-group">
                    <label>Total (S/)</label>
                    <input type="text" id="fTotal" class="form-control" readonly style="background:var(--bg-secondary);font-weight:700">
                </div>
            </div>
            <div class="form-group">
                <label>Observaciones</label>
                <textarea id="fObservaciones" class="form-control" rows="2"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('modalFactura')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarFactura()">
                <i class="fa fa-save"></i> Guardar
            </button>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════════════════
     MODAL VER FACTURA
════════════════════════════════════════════════════════════════════════ -->
<div id="modalVerFactura" class="modal">
    <div class="modal-content" style="max-width:700px">
        <div class="modal-header">
            <h3 id="verFacturaTitulo">Factura</h3>
            <button class="modal-close" onclick="Modal.close('modalVerFactura')">&times;</button>
        </div>
        <div class="modal-body" id="verFacturaCuerpo">
        </div>
        <div class="modal-footer" id="verFacturaFooter">
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════════════════
     MODAL REGISTRAR COBRO
════════════════════════════════════════════════════════════════════════ -->
<div id="modalCobro" class="modal">
    <div class="modal-content" style="max-width:460px">
        <div class="modal-header">
            <h3>Registrar Cobro</h3>
            <button class="modal-close" onclick="Modal.close('modalCobro')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="cobroFacturaId">
            <div id="cobroInfoFactura" style="background:var(--bg-secondary);border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:.9rem"></div>
            <div class="form-row">
                <div class="form-group">
                    <label>Fecha *</label>
                    <input type="date" id="cobroFecha" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Monto (S/) *</label>
                    <input type="number" id="cobroMonto" class="form-control" step="0.01" min="0.01" required>
                </div>
            </div>
            <div class="form-group">
                <label>Método de Pago *</label>
                <select id="cobroMetodo" class="form-control">
                    <option value="efectivo">Efectivo</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="deposito">Depósito</option>
                    <option value="cheque">Cheque</option>
                    <option value="yape">Yape</option>
                    <option value="plin">Plin</option>
                </select>
            </div>
            <div class="form-group">
                <label>Referencia / N° Operación</label>
                <input type="text" id="cobroReferencia" class="form-control" placeholder="Opcional">
            </div>
            <div class="form-group">
                <label>Notas</label>
                <input type="text" id="cobroNotas" class="form-control" placeholder="Opcional">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('modalCobro')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarCobro()">
                <i class="fa fa-check"></i> Registrar Cobro
            </button>
        </div>
    </div>
</div>

<?php
$extra_js = <<<'JS'
<script>
const BASE = 'api.php';

// ─── badges ──────────────────────────────────────────────────────────────────
function badgeFactura(e) {
    const m = {
        pendiente: 'badge-warning',
        parcial:   'badge-primary',
        vencida:   'badge-retrasado',
        cobrada:   'badge-completado',
        anulada:   'badge-secondary',
    };
    return `<span class="badge ${m[e]||'badge-secondary'}">${e}</span>`;
}
function badgeMetodo(m) {
    const ic = { efectivo:'money-bill', transferencia:'exchange-alt', deposito:'university',
                 cheque:'file-alt', yape:'mobile-alt', plin:'mobile-alt' };
    return `<span style="text-transform:capitalize"><i class="fa fa-${ic[m]||'credit-card'}"></i> ${m}</span>`;
}

// ─── tab wiring ───────────────────────────────────────────────────────────────
document.querySelectorAll('.tabs .tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tabs .tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
        if (btn.dataset.tab === 'cobros')  cargarCobros();
        if (btn.dataset.tab === 'resumen') cargarResumen();
    });
});

// ═══ FACTURAS ════════════════════════════════════════════════════════════════
async function cargarFacturas() {
    const q      = document.getElementById('fBuscar').value.trim();
    const estado = document.getElementById('fEstado').value;
    let url = `${BASE}?action=facturas_listar`;
    if (q)      url += `&q=${encodeURIComponent(q)}`;
    if (estado) url += `&estado=${estado}`;
    const tb = document.getElementById('tbFacturas');
    try {
        const d = await apiGet(url);
        if (!d.data.length) {
            tb.innerHTML = '<tr><td colspan="10" class="text-center text-muted">Sin resultados</td></tr>';
            return;
        }
        tb.innerHTML = d.data.map(f => `
        <tr>
            <td><strong>${f.numero}</strong></td>
            <td>${escHtml(f.cliente)}</td>
            <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${escHtml(f.concepto||'—')}</td>
            <td>${formatDate(f.fecha_emision)}</td>
            <td>${formatDate(f.fecha_vencimiento)}</td>
            <td class="text-right">${formatMoney(f.total)}</td>
            <td class="text-right">${formatMoney(f.total_cobrado)}</td>
            <td class="text-right"><strong>${formatMoney(f.pendiente)}</strong></td>
            <td>${badgeFactura(f.estado)}</td>
            <td>
                <button class="btn btn-sm btn-secondary" title="Ver detalle" onclick="verFactura(${f.id})">
                    <i class="fa fa-eye"></i>
                </button>
                ${f.estado !== 'cobrada' && f.estado !== 'anulada' ? `
                <button class="btn btn-sm btn-primary" title="Registrar cobro" onclick="abrirModalCobro(${f.id})">
                    <i class="fa fa-plus"></i>
                </button>
                <button class="btn btn-sm btn-secondary" title="Editar" onclick="editarFactura(${f.id})">
                    <i class="fa fa-edit"></i>
                </button>` : ''}
            </td>
        </tr>`).join('');
    } catch(e) {
        tb.innerHTML = `<tr><td colspan="10" class="text-center text-danger">${e.message}</td></tr>`;
    }
}

// ── modal nueva factura ───────────────────────────────────────────────────────
let _clientes = [];
async function abrirModalFactura(data = null) {
    document.getElementById('modalFacturaTitulo').textContent = data ? 'Editar Factura' : 'Nueva Factura';
    document.getElementById('fId').value              = data?.id || '';
    document.getElementById('fConcepto').value        = data?.concepto || '';
    document.getElementById('fFechaEmision').value    = data?.fecha_emision || new Date().toISOString().slice(0,10);
    document.getElementById('fFechaVencimiento').value = data?.fecha_vencimiento || '';
    document.getElementById('fSubtotal').value        = data?.subtotal || '';
    document.getElementById('fAplicaIgv').checked     = data ? parseFloat(data.igv) > 0 : false;
    document.getElementById('fObservaciones').value   = data?.observaciones || '';
    calcularTotal();

    if (!_clientes.length) {
        const r = await apiGet(`${BASE}?action=clientes_combo`);
        _clientes = r.data;
    }
    const sel = document.getElementById('fClienteId');
    sel.innerHTML = '<option value="">— Seleccione —</option>' +
        _clientes.map(c => `<option value="${c.id}">${escHtml(c.nombre)}</option>`).join('');
    if (data?.cliente_id) sel.value = data.cliente_id;

    await cargarCotizaciones(data?.cotizacion_id);
    Modal.open('modalFactura');
}

async function editarFactura(id) {
    try {
        const r = await apiGet(`${BASE}?action=factura_obtener&id=${id}`);
        abrirModalFactura(r.data);
    } catch(e) { Toast.error(e.message); }
}

async function cargarCotizaciones(selId = null) {
    const cid = document.getElementById('fClienteId').value;
    const sel = document.getElementById('fCotizacionId');
    sel.innerHTML = '<option value="">— Ninguna —</option>';
    if (!cid) return;
    try {
        const r = await apiGet(`${BASE}?action=cotizaciones_combo&cliente_id=${cid}`);
        r.data.forEach(c => {
            const o = document.createElement('option');
            o.value = c.id;
            o.textContent = c.label;
            sel.appendChild(o);
        });
        if (selId) sel.value = selId;
    } catch(_) {}
}

function calcularTotal() {
    const sub = parseFloat(document.getElementById('fSubtotal').value) || 0;
    const igv = document.getElementById('fAplicaIgv').checked ? Math.round(sub * 0.18 * 100) / 100 : 0;
    document.getElementById('fIgv').value   = igv.toFixed(2);
    document.getElementById('fTotal').value = (sub + igv).toFixed(2);
}

async function guardarFactura() {
    const id = document.getElementById('fId').value;
    const payload = {
        action:           'factura_guardar',
        id:               id ? parseInt(id) : 0,
        cliente_id:       parseInt(document.getElementById('fClienteId').value) || 0,
        concepto:         document.getElementById('fConcepto').value.trim(),
        cotizacion_id:    document.getElementById('fCotizacionId').value || null,
        fecha_emision:    document.getElementById('fFechaEmision').value,
        fecha_vencimiento: document.getElementById('fFechaVencimiento').value,
        subtotal:         parseFloat(document.getElementById('fSubtotal').value) || 0,
        aplica_igv:       document.getElementById('fAplicaIgv').checked,
        observaciones:    document.getElementById('fObservaciones').value.trim(),
    };
    try {
        await apiPost(BASE, payload);
        Toast.success(id ? 'Factura actualizada' : 'Factura creada');
        Modal.close('modalFactura');
        cargarFacturas();
    } catch(e) { Toast.error(e.message); }
}

// ── ver factura ───────────────────────────────────────────────────────────────
async function verFactura(id) {
    try {
        const r = await apiGet(`${BASE}?action=factura_obtener&id=${id}`);
        const f = r.data;
        document.getElementById('verFacturaTitulo').textContent = `Factura ${f.numero}`;

        const cobrosHtml = f.cobros.length
            ? `<table class="table" style="font-size:.85rem">
                <thead><tr><th>Fecha</th><th>Monto</th><th>Método</th><th>Referencia</th><th></th></tr></thead>
                <tbody>
                ${f.cobros.map(c => `
                <tr>
                    <td>${formatDate(c.fecha)}</td>
                    <td class="text-right">${formatMoney(c.monto)}</td>
                    <td>${badgeMetodo(c.metodo_pago)}</td>
                    <td>${escHtml(c.referencia||'—')}</td>
                    <td>
                        ${f.estado !== 'cobrada' && f.estado !== 'anulada' ? `
                        <button class="btn btn-sm btn-secondary" onclick="eliminarCobro(${c.id},${f.id})" title="Eliminar">
                            <i class="fa fa-trash"></i>
                        </button>` : ''}
                    </td>
                </tr>`).join('')}
                </tbody>
               </table>`
            : '<p class="text-muted" style="font-size:.85rem">Sin cobros registrados.</p>';

        document.getElementById('verFacturaCuerpo').innerHTML = `
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px 24px;font-size:.9rem;margin-bottom:16px">
            <div><span class="text-muted">Cliente:</span> <strong>${escHtml(f.cliente)}</strong></div>
            <div><span class="text-muted">Estado:</span> ${badgeFactura(f.estado)}</div>
            <div><span class="text-muted">Emisión:</span> ${formatDate(f.fecha_emision)}</div>
            <div><span class="text-muted">Vencimiento:</span> ${formatDate(f.fecha_vencimiento)}</div>
            ${f.concepto ? `<div style="grid-column:1/-1"><span class="text-muted">Concepto:</span> ${escHtml(f.concepto)}</div>` : ''}
        </div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;background:var(--bg-secondary);border-radius:8px;padding:12px 16px;margin-bottom:16px;text-align:center;font-size:.9rem">
            <div><div class="text-muted" style="font-size:.75rem">TOTAL</div><div style="font-size:1.1rem;font-weight:700">${formatMoney(f.total)}</div></div>
            <div><div class="text-muted" style="font-size:.75rem">COBRADO</div><div style="font-size:1.1rem;font-weight:700;color:var(--success)">${formatMoney(f.total_cobrado)}</div></div>
            <div><div class="text-muted" style="font-size:.75rem">PENDIENTE</div><div style="font-size:1.1rem;font-weight:700;color:var(--warning)">${formatMoney(parseFloat(f.total)-parseFloat(f.total_cobrado))}</div></div>
        </div>
        <div style="font-weight:600;margin-bottom:8px">Historial de Cobros</div>
        ${cobrosHtml}
        `;

        document.getElementById('verFacturaFooter').innerHTML = `
            <button class="btn btn-secondary" onclick="Modal.close('modalVerFactura')">Cerrar</button>
            ${f.estado !== 'cobrada' && f.estado !== 'anulada' ? `
            <button class="btn btn-primary" onclick="Modal.close('modalVerFactura');abrirModalCobro(${f.id})">
                <i class="fa fa-plus"></i> Registrar Cobro
            </button>
            <button class="btn btn-secondary" onclick="anularFactura(${f.id})">
                <i class="fa fa-ban"></i> Anular
            </button>` : ''}
        `;

        Modal.open('modalVerFactura');
    } catch(e) { Toast.error(e.message); }
}

async function anularFactura(id) {
    if (!confirm('¿Anular esta factura? Esta acción no se puede deshacer.')) return;
    try {
        await apiPost(BASE, { action: 'factura_anular', id });
        Toast.success('Factura anulada');
        Modal.close('modalVerFactura');
        cargarFacturas();
    } catch(e) { Toast.error(e.message); }
}

// ═══ COBROS ══════════════════════════════════════════════════════════════════
async function cargarCobros() {
    const q   = document.getElementById('cBuscar').value.trim();
    let url = `${BASE}?action=cobros_listar`;
    if (q) url += `&q=${encodeURIComponent(q)}`;
    const tb = document.getElementById('tbCobros');
    try {
        const d = await apiGet(url);
        if (!d.data.length) {
            tb.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Sin cobros registrados</td></tr>';
            return;
        }
        tb.innerHTML = d.data.map(c => `
        <tr>
            <td>${formatDate(c.fecha)}</td>
            <td><strong>${c.factura_numero}</strong></td>
            <td>${escHtml(c.cliente)}</td>
            <td class="text-right"><strong>${formatMoney(c.monto)}</strong></td>
            <td>${badgeMetodo(c.metodo_pago)}</td>
            <td>${escHtml(c.referencia||'—')}</td>
            <td>
                <button class="btn btn-sm btn-secondary" onclick="verFacturaDesd(${c.factura_numero ? `'${c.factura_numero}'` : 0},event)" title="Ver factura">
                    <i class="fa fa-eye"></i>
                </button>
            </td>
        </tr>`).join('');
    } catch(e) {
        tb.innerHTML = `<tr><td colspan="7" class="text-center text-danger">${e.message}</td></tr>`;
    }
}

// ── modal cobro ───────────────────────────────────────────────────────────────
let _cobroFacturaCache = null;

async function abrirModalCobro(facturaId) {
    try {
        const r = await apiGet(`${BASE}?action=factura_obtener&id=${facturaId}`);
        const f = r.data;
        _cobroFacturaCache = f;
        document.getElementById('cobroFacturaId').value = facturaId;
        document.getElementById('cobroFecha').value     = new Date().toISOString().slice(0,10);
        document.getElementById('cobroMonto').value     = '';
        document.getElementById('cobroMetodo').value    = 'efectivo';
        document.getElementById('cobroReferencia').value = '';
        document.getElementById('cobroNotas').value      = '';
        const pendiente = parseFloat(f.total) - parseFloat(f.total_cobrado);
        document.getElementById('cobroInfoFactura').innerHTML = `
            <div style="display:flex;justify-content:space-between">
                <div><strong>${f.numero}</strong> — ${escHtml(f.cliente)}</div>
                ${badgeFactura(f.estado)}
            </div>
            <div style="margin-top:8px;display:flex;gap:24px">
                <span>Total: <strong>${formatMoney(f.total)}</strong></span>
                <span>Cobrado: <strong style="color:var(--success)">${formatMoney(f.total_cobrado)}</strong></span>
                <span>Pendiente: <strong style="color:var(--warning)">${formatMoney(pendiente)}</strong></span>
            </div>`;
        document.getElementById('cobroMonto').max = pendiente.toFixed(2);
        Modal.open('modalCobro');
    } catch(e) { Toast.error(e.message); }
}

async function guardarCobro() {
    const payload = {
        action:      'cobro_guardar',
        factura_id:  parseInt(document.getElementById('cobroFacturaId').value),
        fecha:       document.getElementById('cobroFecha').value,
        monto:       parseFloat(document.getElementById('cobroMonto').value) || 0,
        metodo_pago: document.getElementById('cobroMetodo').value,
        referencia:  document.getElementById('cobroReferencia').value.trim(),
        notas:       document.getElementById('cobroNotas').value.trim(),
    };
    try {
        await apiPost(BASE, payload);
        Toast.success('Cobro registrado');
        Modal.close('modalCobro');
        cargarFacturas();
    } catch(e) { Toast.error(e.message); }
}

async function eliminarCobro(cobroId, facturaId) {
    if (!confirm('¿Eliminar este cobro?')) return;
    try {
        await apiPost(BASE, { action: 'cobro_eliminar', id: cobroId });
        Toast.success('Cobro eliminado');
        Modal.close('modalVerFactura');
        cargarFacturas();
        verFactura(facturaId);
    } catch(e) { Toast.error(e.message); }
}

// ═══ RESUMEN ═════════════════════════════════════════════════════════════════
async function cargarResumen() {
    const el = document.getElementById('resumenContent');
    try {
        const r = await apiGet(`${BASE}?action=dashboard`);
        const k = r.kpi;
        const a = r.aging;
        el.innerHTML = `
        <div class="stats-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px">
            ${kpiCard('fa-clock','Por Cobrar',k.total_pendiente,'var(--warning)')}
            ${kpiCard('fa-exclamation-triangle','Vencido',k.total_vencido,'var(--danger)')}
            ${kpiCard('fa-check-circle','Cobrado este Mes',k.cobrado_mes,'var(--success)')}
            ${kpiCard('fa-file-invoice','Facturas Activas',r.activas,'var(--primary)',true)}
        </div>
        <div class="card" style="max-width:520px">
            <div class="card-header"><h4>Antigüedad de Saldos</h4></div>
            <div class="card-body">
                <table class="table" style="font-size:.9rem">
                    <thead><tr><th>Rango</th><th class="text-right">Monto Pendiente</th></tr></thead>
                    <tbody>
                        <tr><td>1 – 30 días</td>    <td class="text-right">${formatMoney(a.d30||0)}</td></tr>
                        <tr><td>31 – 60 días</td>   <td class="text-right">${formatMoney(a.d60||0)}</td></tr>
                        <tr><td>61 – 90 días</td>   <td class="text-right">${formatMoney(a.d90||0)}</td></tr>
                        <tr><td>Más de 90 días</td> <td class="text-right" style="color:var(--danger);font-weight:700">${formatMoney(a.d90plus||0)}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>`;
    } catch(e) {
        el.innerHTML = `<div class="text-center text-danger">${e.message}</div>`;
    }
}

function kpiCard(icon, label, value, color, isCount = false) {
    const display = isCount ? value : formatMoney(value);
    return `
    <div class="card" style="padding:20px;text-align:center">
        <i class="fa ${icon}" style="font-size:1.8rem;color:${color};margin-bottom:8px"></i>
        <div style="font-size:.8rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em">${label}</div>
        <div style="font-size:1.5rem;font-weight:700;color:${color}">${display}</div>
    </div>`;
}

// ── util ──────────────────────────────────────────────────────────────────────
function escHtml(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── init ──────────────────────────────────────────────────────────────────────
cargarFacturas();
</script>
JS;
require_once __DIR__ . '/../../includes/footer.php';
?>

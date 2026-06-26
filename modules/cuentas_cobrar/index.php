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

    <div style="display:grid;grid-template-columns:1fr 360px;gap:14px;align-items:start">
        <!-- Lista -->
        <div class="card" style="min-width:0;overflow:hidden">
            <table class="table">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Cliente</th>
                        <th>Vencimiento</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Pendiente</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody id="tbFacturas">
                    <tr><td colspan="6" class="text-center text-muted">Cargando…</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Panel de detalle / formulario -->
        <div id="facturaDetalle" style="position:sticky;top:16px">
            <div id="fdPanel" style="background:#fff;border-radius:var(--radius);border:1px solid var(--border);overflow:hidden">
                <div id="fdEmpty" style="padding:48px 24px;text-align:center;color:var(--text-muted)">
                    <i class="fa fa-file-invoice-dollar" style="font-size:2rem;opacity:.25;margin-bottom:10px;display:block"></i>
                    Selecciona una factura para ver el detalle
                </div>
                <div id="fdContent" style="display:none">
                    <div id="fdHeader" style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
                        <div>
                            <div id="fdNumero" style="font-weight:700;font-size:1rem"></div>
                            <div id="fdCliente" style="font-size:.82rem;color:var(--text-muted);margin-top:2px"></div>
                        </div>
                        <div id="fdBadge"></div>
                    </div>
                    <div style="padding:16px 20px;border-bottom:1px solid var(--border)">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:.82rem;color:var(--text-muted);margin-bottom:14px">
                            <div>Emisión: <span id="fdEmision" style="color:var(--text)"></span></div>
                            <div>Vencimiento: <span id="fdVence" style="color:var(--text)"></span></div>
                            <div id="fdConceptoWrap" style="grid-column:1/-1;display:none">Concepto: <span id="fdConcepto" style="color:var(--text)"></span></div>
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;text-align:center">
                            <div style="background:var(--bg-secondary);border-radius:8px;padding:10px 6px">
                                <div style="font-size:.67rem;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted)">Total</div>
                                <div id="fdTotal" style="font-size:.95rem;font-weight:700;margin-top:3px"></div>
                            </div>
                            <div style="background:#ecfdf5;border-radius:8px;padding:10px 6px">
                                <div style="font-size:.67rem;text-transform:uppercase;letter-spacing:.06em;color:#16a34a">Cobrado</div>
                                <div id="fdCobrado" style="font-size:.95rem;font-weight:700;color:#16a34a;margin-top:3px"></div>
                            </div>
                            <div style="background:#fffbeb;border-radius:8px;padding:10px 6px">
                                <div style="font-size:.67rem;text-transform:uppercase;letter-spacing:.06em;color:#d97706">Pendiente</div>
                                <div id="fdPendiente" style="font-size:.95rem;font-weight:700;color:#d97706;margin-top:3px"></div>
                            </div>
                        </div>
                    </div>
                    <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
                        <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-muted);margin-bottom:10px">Historial de Cobros</div>
                        <div id="fdCobros"></div>
                    </div>
                    <div id="fdAcciones" style="padding:12px 20px;display:flex;gap:8px;flex-wrap:wrap"></div>
                </div>
            </div>

            <!-- Formulario inline (sin overlay, sin sombra) -->
            <div id="fdForm" style="display:none;background:#fff;border-radius:var(--radius);border:1px solid var(--border);overflow:hidden;margin-top:0">
                <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
                    <div id="fdFormTitulo" style="font-weight:700;font-size:1rem">Nueva Factura</div>
                    <button onclick="cerrarFormFactura()" style="background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:1rem;padding:2px 6px;border-radius:4px" title="Cerrar">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div style="padding:16px 20px;display:flex;flex-direction:column;gap:10px;max-height:70vh;overflow-y:auto">
                    <input type="hidden" id="fId">
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Cliente *</label>
                        <select id="fClienteId" class="form-control" onchange="cargarCotizaciones()"></select>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Cotización (opcional)</label>
                        <select id="fCotizacionId" class="form-control">
                            <option value="">— Ninguna —</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Concepto</label>
                        <input type="text" id="fConcepto" class="form-control" placeholder="Descripción del servicio o producto">
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                        <div class="form-group" style="margin:0">
                            <label class="form-label">Emisión *</label>
                            <input type="date" id="fFechaEmision" class="form-control">
                        </div>
                        <div class="form-group" style="margin:0">
                            <label class="form-label">Vencimiento *</label>
                            <input type="date" id="fFechaVencimiento" class="form-control">
                        </div>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Subtotal (S/) *</label>
                        <input type="number" id="fSubtotal" class="form-control" step="0.01" min="0.01" oninput="calcularTotal()">
                    </div>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:.88rem;color:var(--text);user-select:none">
                        <input type="checkbox" id="fAplicaIgv" onchange="calcularTotal()"> Aplicar IGV (18%)
                    </label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                        <div class="form-group" style="margin:0">
                            <label class="form-label">IGV (S/)</label>
                            <input type="text" id="fIgv" class="form-control" readonly style="background:var(--bg-secondary)">
                        </div>
                        <div class="form-group" style="margin:0">
                            <label class="form-label">Total (S/)</label>
                            <input type="text" id="fTotal" class="form-control" readonly style="background:var(--bg-secondary);font-weight:700">
                        </div>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Observaciones</label>
                        <textarea id="fObservaciones" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div style="padding:12px 20px;border-top:1px solid var(--border);display:flex;gap:8px;justify-content:flex-end">
                    <button class="btn btn-outline btn-sm" onclick="cerrarFormFactura()">Cancelar</button>
                    <button class="btn btn-primary btn-sm" onclick="guardarFactura()"><i class="fa fa-save"></i> Guardar</button>
                </div>
            </div>
        </div>
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
            tb.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Sin resultados</td></tr>';
            return;
        }
        tb.innerHTML = d.data.map(f => `
        <tr id="fila-${f.id}" style="cursor:pointer" onclick="verFactura(${f.id})">
            <td><strong>${f.numero}</strong></td>
            <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${escHtml(f.cliente)}</td>
            <td>${formatDate(f.fecha_vencimiento)}</td>
            <td class="text-right">${formatMoney(f.total)}</td>
            <td class="text-right"><strong>${formatMoney(f.pendiente)}</strong></td>
            <td>${badgeFactura(f.estado)}</td>
        </tr>`).join('');
    } catch(e) {
        tb.innerHTML = `<tr><td colspan="10" class="text-center text-danger">${e.message}</td></tr>`;
    }
}

// ── formulario inline nueva / editar factura ─────────────────────────────────
let _clientes = [];

function cerrarFormFactura() {
    document.getElementById('fdForm').style.display = 'none';
    document.getElementById('fdPanel').style.display = 'block';
}

async function abrirModalFactura(data = null) {
    document.getElementById('fdFormTitulo').textContent = data ? 'Editar Factura' : 'Nueva Factura';
    document.getElementById('fId').value               = data?.id || '';
    document.getElementById('fConcepto').value         = data?.concepto || '';
    document.getElementById('fFechaEmision').value     = data?.fecha_emision || new Date().toISOString().slice(0,10);
    document.getElementById('fFechaVencimiento').value = data?.fecha_vencimiento || '';
    document.getElementById('fSubtotal').value         = data?.subtotal || '';
    document.getElementById('fAplicaIgv').checked      = data ? parseFloat(data.igv) > 0 : false;
    document.getElementById('fObservaciones').value    = data?.observaciones || '';
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

    // Mostrar panel de formulario, ocultar panel de detalle
    document.getElementById('fdPanel').style.display = 'none';
    document.getElementById('fdForm').style.display  = 'block';
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
        cerrarFormFactura();
        _facturaDetalleId = null;
        cargarFacturas();
    } catch(e) { Toast.error(e.message); }
}

// ── panel de detalle ─────────────────────────────────────────────────────────
let _facturaDetalleId = null;

async function verFactura(id) {
    // Resaltar fila seleccionada
    document.querySelectorAll('#tbFacturas tr').forEach(r => r.style.background = '');
    const fila = document.getElementById(`fila-${id}`);
    if (fila) fila.style.background = 'var(--primary-light, #eff6ff)';

    if (_facturaDetalleId === id) return; // ya cargado
    _facturaDetalleId = id;

    document.getElementById('fdEmpty').style.display = 'none';
    const content = document.getElementById('fdContent');
    content.style.display = 'block';
    document.getElementById('fdCobros').innerHTML = '<div class="text-muted" style="font-size:.82rem">Cargando…</div>';

    try {
        const r = await apiGet(`${BASE}?action=factura_obtener&id=${id}`);
        const f = r.data;
        const pendiente = parseFloat(f.total) - parseFloat(f.total_cobrado);

        document.getElementById('fdNumero').textContent  = f.numero;
        document.getElementById('fdCliente').textContent = f.cliente;
        document.getElementById('fdBadge').innerHTML     = badgeFactura(f.estado);
        document.getElementById('fdEmision').textContent = formatDate(f.fecha_emision);
        document.getElementById('fdVence').textContent   = formatDate(f.fecha_vencimiento);
        document.getElementById('fdTotal').textContent   = formatMoney(f.total);
        document.getElementById('fdCobrado').textContent = formatMoney(f.total_cobrado);
        document.getElementById('fdPendiente').textContent = formatMoney(pendiente);

        const cw = document.getElementById('fdConceptoWrap');
        if (f.concepto) {
            cw.style.display = '';
            document.getElementById('fdConcepto').textContent = f.concepto;
        } else { cw.style.display = 'none'; }

        document.getElementById('fdCobros').innerHTML = f.cobros.length
            ? f.cobros.map(c => `
                <div style="display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid var(--border);font-size:.82rem">
                    <div>
                        <div style="font-weight:600">${formatMoney(c.monto)}</div>
                        <div style="color:var(--text-muted)">${formatDate(c.fecha)} · ${badgeMetodo(c.metodo_pago)}</div>
                        ${c.referencia ? `<div style="color:var(--text-muted)">${escHtml(c.referencia)}</div>` : ''}
                    </div>
                    ${f.estado !== 'cobrada' && f.estado !== 'anulada' ? `
                    <button class="btn btn-xs btn-outline" style="color:var(--danger)" onclick="eliminarCobro(${c.id},${f.id})" title="Eliminar">
                        <i class="fa fa-trash"></i>
                    </button>` : ''}
                </div>`).join('')
            : '<div class="text-muted" style="font-size:.82rem">Sin cobros registrados.</div>';

        const activos = f.estado !== 'cobrada' && f.estado !== 'anulada';
        document.getElementById('fdAcciones').innerHTML = `
            ${activos ? `
            <button class="btn btn-primary btn-sm" onclick="abrirModalCobro(${f.id})">
                <i class="fa fa-plus"></i> Registrar Cobro
            </button>
            <button class="btn btn-outline btn-sm" onclick="editarFactura(${f.id})">
                <i class="fa fa-edit"></i> Editar
            </button>
            <button class="btn btn-outline btn-sm" style="color:var(--danger)" onclick="anularFactura(${f.id})">
                <i class="fa fa-ban"></i> Anular
            </button>` : ''}
        `;
    } catch(e) { Toast.error(e.message); }
}

async function anularFactura(id) {
    if (!confirm('¿Anular esta factura? Esta acción no se puede deshacer.')) return;
    try {
        await apiPost(BASE, { action: 'factura_anular', id });
        Toast.success('Factura anulada');
        _facturaDetalleId = null;
        document.getElementById('fdContent').style.display = 'none';
        document.getElementById('fdEmpty').style.display = '';
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
        _facturaDetalleId = null; // forzar recarga del panel
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
        const tc = r.top_clientes || [];

        const agingBuckets = [
            { label:'Al día',     monto: a.al_dia,   cnt: a.cnt_al_dia,   bg:'#dcfce7', color:'#166534', icon:'fa-circle-check' },
            { label:'1–30 días',  monto: a.d30,       cnt: a.cnt_d30,      bg:'#fef9c3', color:'#854d0e', icon:'fa-clock' },
            { label:'31–60 días', monto: a.d60,       cnt: a.cnt_d60,      bg:'#ffedd5', color:'#c2410c', icon:'fa-triangle-exclamation' },
            { label:'61–90 días', monto: a.d90,       cnt: a.cnt_d90,      bg:'#fee2e2', color:'#b91c1c', icon:'fa-circle-exclamation' },
            { label:'+90 días',   monto: a.d90plus,   cnt: a.cnt_d90plus,  bg:'#fce7f3', color:'#9d174d', icon:'fa-skull-crossbones' },
        ];

        const totalAging = agingBuckets.reduce((s, b) => s + parseFloat(b.monto||0), 0);

        const agingHtml = agingBuckets.map(b => {
            const monto = parseFloat(b.monto||0);
            const pct   = totalAging > 0 ? (monto / totalAging * 100).toFixed(1) : 0;
            return `
            <div style="border-radius:8px;padding:14px 16px;background:${b.bg};border:1px solid ${b.bg}">
                <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;color:${b.color};margin-bottom:6px">
                    <i class="fa ${b.icon}" style="margin-right:4px"></i>${b.label}
                </div>
                <div style="font-size:1.05rem;font-weight:700;color:${b.color}">${formatMoney(monto)}</div>
                <div style="font-size:.72rem;color:${b.color};opacity:.8;margin-top:2px">
                    ${b.cnt||0} factura${(b.cnt||0)==1?'':'s'} · ${pct}%
                </div>
                <div style="height:4px;background:rgba(0,0,0,.08);border-radius:2px;margin-top:8px">
                    <div style="height:4px;background:${b.color};border-radius:2px;width:${pct}%;transition:width .4s"></div>
                </div>
            </div>`;
        }).join('');

        const topHtml = tc.length ? `
            <div class="card" style="margin-top:16px">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-users"></i> Clientes con mayor saldo pendiente</span>
                </div>
                <table class="table" style="margin:0">
                    <thead><tr><th>Cliente</th><th class="text-right">Pendiente</th><th class="text-right">Facturas</th><th>Máx. atraso</th></tr></thead>
                    <tbody>
                    ${tc.map(c => `
                        <tr>
                            <td style="font-size:.85rem">${escHtml(c.cliente)}</td>
                            <td class="text-right" style="font-size:.85rem;font-weight:600">${formatMoney(c.pendiente)}</td>
                            <td class="text-right" style="font-size:.85rem">${c.facturas}</td>
                            <td style="font-size:.82rem">${c.max_dias_vencido
                                ? `<span style="color:#b91c1c;font-weight:600">${c.max_dias_vencido} días</span>`
                                : '<span style="color:#166534">Al día</span>'}</td>
                        </tr>`).join('')}
                    </tbody>
                </table>
            </div>` : '';

        el.innerHTML = `
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:20px">
            ${kpiCard('fa-clock','Por Cobrar',k.total_pendiente,'var(--warning)')}
            ${kpiCard('fa-triangle-exclamation','Vencido',k.total_vencido,'var(--danger)')}
            ${kpiCard('fa-circle-check','Cobrado este Mes',k.cobrado_mes,'var(--success)')}
            ${kpiCard('fa-file-invoice','Facturas Activas',r.activas,'var(--primary)',true)}
        </div>
        <div class="card">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                <span class="card-title"><i class="fa fa-clock-rotate-left"></i> Antigüedad de saldos por cobrar</span>
                <span style="font-size:.8rem;color:var(--text-muted)">Total pendiente: <strong>${formatMoney(totalAging)}</strong></span>
            </div>
            <div style="padding:16px;display:grid;grid-template-columns:repeat(5,1fr);gap:10px">
                ${agingHtml}
            </div>
        </div>
        ${topHtml}`;
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

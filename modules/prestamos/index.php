<?php
$page_title      = 'Préstamos';
$page_breadcrumb = 'Finanzas › Préstamos';
require_once __DIR__ . '/../../includes/header.php';
?>

<!-- ============================================================
     TOOLBAR
     ============================================================ -->
<div class="toolbar">
    <div class="toolbar-left">
        <select id="filtroTipo" class="form-control" style="width:160px" onchange="cargarPrestamos()">
            <option value="">Todos los tipos</option>
            <option value="recibido">Recibidos</option>
            <option value="otorgado">Otorgados</option>
        </select>
        <select id="filtroEstado" class="form-control" style="width:160px" onchange="cargarPrestamos()">
            <option value="">Todos los estados</option>
            <option value="activo">Activo</option>
            <option value="pagado">Pagado</option>
            <option value="vencido">Vencido</option>
            <option value="cancelado">Cancelado</option>
        </select>
    </div>
    <div class="toolbar-right">
        <button class="btn btn-primary" onclick="abrirModalPrestamo()">
            <i class="fa fa-plus"></i> Nuevo Préstamo
        </button>
    </div>
</div>

<!-- ============================================================
     STATS
     ============================================================ -->
<div style="display:flex;gap:14px;flex-wrap:wrap;margin-bottom:20px" id="statsBar"></div>

<!-- ============================================================
     TABLA
     ============================================================ -->
<div class="card">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width:130px">Código</th>
                    <th style="width:100px">Tipo</th>
                    <th>Entidad</th>
                    <th style="text-align:right;width:130px">Monto</th>
                    <th style="width:80px;text-align:center">Plazo</th>
                    <th style="width:110px">Inicio</th>
                    <th style="width:110px">Vencimiento</th>
                    <th style="text-align:right;width:120px">Pendiente</th>
                    <th style="width:110px">Estado</th>
                    <th style="width:60px"></th>
                </tr>
            </thead>
            <tbody id="tablaPrestamos">
                <tr><td colspan="10" class="text-center" style="padding:40px;color:var(--text-muted)">Cargando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================================
     MODAL: NUEVO PRÉSTAMO
     ============================================================ -->
<div class="modal-overlay" id="modalPrestamo">
    <div class="modal" style="max-width:520px">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-hand-holding-dollar"></i> Nuevo Préstamo</span>
            <button class="modal-close" onclick="Modal.close('modalPrestamo')">&times;</button>
        </div>
        <div class="modal-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0 16px">
                <div class="form-group">
                    <label class="form-label">Tipo *</label>
                    <select id="pTipo" class="form-control">
                        <option value="recibido">Recibido (somos deudores)</option>
                        <option value="otorgado">Otorgado (somos acreedores)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Entidad / Persona *</label>
                    <input type="text" id="pEntidad" class="form-control" placeholder="Banco, empresa o persona">
                </div>
                <div class="form-group">
                    <label class="form-label">Monto total (S/) *</label>
                    <input type="number" id="pMonto" class="form-control" step="0.01" min="0.01" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label class="form-label">Tasa anual (%)</label>
                    <input type="number" id="pTasa" class="form-control" step="0.01" min="0" placeholder="0.00"
                           value="0">
                    <small style="color:var(--text-muted);font-size:.72rem">0% = sin interés</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Plazo (meses) *</label>
                    <input type="number" id="pPlazo" class="form-control" min="1" placeholder="12">
                </div>
                <div class="form-group">
                    <label class="form-label">Fecha de inicio *</label>
                    <input type="date" id="pFechaInicio" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Descripción</label>
                <textarea id="pDescripcion" class="form-control" rows="2" placeholder="Opcional..."></textarea>
            </div>
            <!-- Preview cuota -->
            <div id="previewCuota" style="display:none;background:#f8fafc;border:1px solid var(--border);
                border-radius:8px;padding:12px 16px;font-size:.84rem">
                <span style="font-weight:600">Cuota mensual estimada:</span>
                <span id="previewCuotaVal" style="font-weight:800;color:var(--primary);font-size:1rem;margin-left:8px"></span>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalPrestamo')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarPrestamo()">
                <i class="fa fa-save"></i> Crear Préstamo
            </button>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: DETALLE + CUOTAS
     ============================================================ -->
<div class="modal-overlay" id="modalDetalle">
    <div class="modal" style="max-width:680px">
        <div class="modal-header">
            <span class="modal-title" id="detalleTitulo"><i class="fa fa-list-ul"></i> Detalle</span>
            <button class="modal-close" onclick="Modal.close('modalDetalle')">&times;</button>
        </div>
        <div class="modal-body" style="padding:0">
            <div id="detalleStats" style="display:flex;border-bottom:1px solid var(--border)"></div>
            <div style="max-height:420px;overflow-y:auto">
                <table>
                    <thead>
                        <tr>
                            <th style="width:60px;text-align:center">N°</th>
                            <th style="width:110px">Vencimiento</th>
                            <th style="text-align:right;width:110px">Capital</th>
                            <th style="text-align:right;width:110px">Interés</th>
                            <th style="text-align:right;width:110px">Total cuota</th>
                            <th style="width:100px">Estado</th>
                            <th style="width:110px">Fecha pago</th>
                            <th style="width:70px"></th>
                        </tr>
                    </thead>
                    <tbody id="tablaCuotas"></tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalDetalle')">Cerrar</button>
            <button class="btn btn-outline" id="btnCancelarPrestamo"
                    style="color:#dc2626;border-color:#fca5a5" onclick="cancelarPrestamo()">
                <i class="fa fa-ban"></i> Cancelar préstamo
            </button>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: PAGAR CUOTA
     ============================================================ -->
<div class="modal-overlay" id="modalPago">
    <div class="modal" style="max-width:380px">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-circle-check"></i> Registrar Pago</span>
            <button class="modal-close" onclick="Modal.close('modalPago')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="pagoIdCuota">
            <div id="pagoInfo" style="background:#f8fafc;border:1px solid var(--border);
                border-radius:8px;padding:12px 14px;margin-bottom:14px;font-size:.85rem"></div>
            <div class="form-group">
                <label class="form-label">Fecha de pago *</label>
                <input type="date" id="pagoFecha" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Observaciones</label>
                <input type="text" id="pagoObs" class="form-control" placeholder="Opcional...">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalPago')">Cancelar</button>
            <button class="btn btn-primary" onclick="confirmarPago()">
                <i class="fa fa-circle-check"></i> Confirmar pago
            </button>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
const API = 'api.php';
let prestamoActual = null;

// ================================================================
// INIT
// ================================================================
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('pFechaInicio').value = new Date().toISOString().slice(0, 10);
    ['pMonto','pTasa','pPlazo'].forEach(id =>
        document.getElementById(id).addEventListener('input', calcPreviewCuota));
    cargarPrestamos();
});

// ================================================================
// STATS BAR
// ================================================================
function renderStats(prestamos) {
    const activos    = prestamos.filter(p => p.estado === 'activo');
    const recibidos  = activos.filter(p => p.tipo === 'recibido');
    const otorgados  = activos.filter(p => p.tipo === 'otorgado');
    const sumPend    = v => v.reduce((s, p) => s + +p.monto_pendiente, 0);

    const stats = [
        { label:'Préstamos activos',  val: activos.length,           color:'var(--primary)', fmt: false },
        { label:'Deuda pendiente',    val: sumPend(recibidos),        color:'#dc2626',        fmt: true  },
        { label:'Por cobrar',         val: sumPend(otorgados),        color:'#16a34a',        fmt: true  },
    ];
    document.getElementById('statsBar').innerHTML = stats.map(s => `
        <div style="background:#fff;border:1px solid var(--border);border-radius:var(--radius);
                    box-shadow:var(--card-shadow);padding:14px 22px;flex:1;min-width:180px">
            <div style="font-size:.65rem;font-weight:700;text-transform:uppercase;
                        letter-spacing:.07em;color:var(--text-muted);margin-bottom:4px">${s.label}</div>
            <div style="font-size:1.25rem;font-weight:800;color:${s.color}">
                ${s.fmt ? formatMoney(s.val) : s.val}
            </div>
        </div>
    `).join('');
}

// ================================================================
// LISTADO
// ================================================================
async function cargarPrestamos() {
    const tipo   = document.getElementById('filtroTipo').value;
    const estado = document.getElementById('filtroEstado').value;
    let url = API + '?action=listar_prestamos';
    if (tipo)   url += '&tipo='   + tipo;
    if (estado) url += '&estado=' + estado;
    try {
        const res = await apiGet(url);
        renderStats(res.prestamos);
        const tbody = document.getElementById('tablaPrestamos');
        if (!res.prestamos.length) {
            tbody.innerHTML = '<tr><td colspan="10" class="text-center" style="padding:40px;color:var(--text-muted)">Sin préstamos registrados.</td></tr>';
            return;
        }
        tbody.innerHTML = res.prestamos.map(p => `<tr>
            <td style="font-size:.8rem;font-weight:600;color:var(--primary)">${p.codigo}</td>
            <td>${badgeTipo(p.tipo)}</td>
            <td style="font-weight:600">${p.entidad}</td>
            <td style="text-align:right">${formatMoney(p.monto_total)}</td>
            <td style="text-align:center;color:var(--text-muted)">${p.plazo_meses}m</td>
            <td style="font-size:.82rem">${formatDate(p.fecha_inicio)}</td>
            <td style="font-size:.82rem">${formatDate(p.fecha_vencimiento)}</td>
            <td style="text-align:right;font-weight:700;color:${+p.monto_pendiente > 0 ? '#dc2626' : '#16a34a'}">
                ${formatMoney(p.monto_pendiente)}
            </td>
            <td>${badgeEstado(p.estado)}</td>
            <td style="text-align:center">
                <button class="btn btn-outline" style="padding:3px 9px;font-size:.75rem"
                        onclick="verDetalle(${p.id})">
                    <i class="fa fa-eye"></i>
                </button>
            </td>
        </tr>`).join('');
    } catch(e) { console.error(e); }
}

// ================================================================
// NUEVO PRÉSTAMO
// ================================================================
function abrirModalPrestamo() {
    document.getElementById('pTipo').selectedIndex     = 0;
    document.getElementById('pEntidad').value          = '';
    document.getElementById('pMonto').value            = '';
    document.getElementById('pTasa').value             = '0';
    document.getElementById('pPlazo').value            = '';
    document.getElementById('pDescripcion').value      = '';
    document.getElementById('pFechaInicio').value      = new Date().toISOString().slice(0,10);
    document.getElementById('previewCuota').style.display = 'none';
    Modal.open('modalPrestamo');
    setTimeout(() => document.getElementById('pEntidad').focus(), 120);
}

function calcPreviewCuota() {
    const monto = parseFloat(document.getElementById('pMonto').value  || '0');
    const tasa  = parseFloat(document.getElementById('pTasa').value   || '0') / 100;
    const plazo = parseInt(document.getElementById('pPlazo').value    || '0');
    if (monto <= 0 || plazo < 1) { document.getElementById('previewCuota').style.display = 'none'; return; }
    let cuota;
    if (tasa > 0) {
        const r = tasa / 12;
        cuota = monto * r / (1 - Math.pow(1 + r, -plazo));
    } else {
        cuota = monto / plazo;
    }
    document.getElementById('previewCuotaVal').textContent = formatMoney(cuota);
    document.getElementById('previewCuota').style.display = 'block';
}

async function guardarPrestamo() {
    const entidad = document.getElementById('pEntidad').value.trim();
    const monto   = parseFloat(document.getElementById('pMonto').value  || '0');
    const plazo   = parseInt(document.getElementById('pPlazo').value    || '0');
    const fini    = document.getElementById('pFechaInicio').value;
    if (!entidad) { Toast.show('La entidad es requerida', 'error'); return; }
    if (monto <= 0) { Toast.show('Ingrese un monto válido', 'error'); return; }
    if (plazo < 1)  { Toast.show('El plazo debe ser al menos 1 mes', 'error'); return; }
    if (!fini)      { Toast.show('Seleccione la fecha de inicio', 'error'); return; }
    try {
        const res = await apiPost(API, {
            action:       'guardar_prestamo',
            tipo:         document.getElementById('pTipo').value,
            entidad,
            monto_total:  monto,
            tasa_interes: parseFloat(document.getElementById('pTasa').value || '0'),
            plazo_meses:  plazo,
            fecha_inicio: fini,
            descripcion:  document.getElementById('pDescripcion').value.trim(),
        });
        if (!res.ok) { Toast.show(res.error || 'Error al guardar', 'error'); return; }
        Modal.close('modalPrestamo');
        Toast.show('Préstamo creado · ' + res.codigo, 'success');
        cargarPrestamos();
    } catch(e) { Toast.show('Error de conexión', 'error'); }
}

// ================================================================
// DETALLE + CUOTAS
// ================================================================
async function verDetalle(id) {
    try {
        const res = await apiGet(API + '?action=detalle_prestamo&id=' + id);
        prestamoActual = res.prestamo;
        const p = res.prestamo;

        document.getElementById('detalleTitulo').innerHTML =
            `<i class="fa fa-list-ul"></i> ${p.codigo} — ${p.entidad}`;

        const pagado   = res.cuotas.filter(c => c.estado === 'pagado').reduce((s,c) => s + +c.monto_total, 0);
        const pendient = res.cuotas.filter(c => c.estado !== 'pagado').reduce((s,c) => s + +c.monto_total, 0);
        document.getElementById('detalleStats').innerHTML = [
            { label:'Tipo',     val: p.tipo === 'recibido' ? 'Recibido' : 'Otorgado', color:'var(--primary)', fmt:false },
            { label:'Total',    val: +p.monto_total,  color:'var(--text-primary)', fmt:true },
            { label:'Pagado',   val: pagado,           color:'#16a34a',             fmt:true },
            { label:'Pendiente',val: pendient,         color:'#dc2626',             fmt:true },
        ].map((s,i) => `
            <div style="flex:1;text-align:center;padding:14px 10px;
                        ${i>0?'border-left:1px solid var(--border)':''}">
                <div style="font-size:.62rem;font-weight:700;text-transform:uppercase;
                            letter-spacing:.07em;color:var(--text-muted);margin-bottom:3px">${s.label}</div>
                <div style="font-size:.95rem;font-weight:800;color:${s.color}">
                    ${s.fmt ? formatMoney(s.val) : s.val}
                </div>
            </div>`
        ).join('');

        document.getElementById('btnCancelarPrestamo').style.display =
            p.estado === 'activo' ? '' : 'none';

        document.getElementById('tablaCuotas').innerHTML = res.cuotas.map(c => `<tr>
            <td style="text-align:center;font-weight:600">${c.numero_cuota}</td>
            <td style="font-size:.82rem">${formatDate(c.fecha_vencimiento)}</td>
            <td style="text-align:right;font-size:.85rem">${formatMoney(c.monto_capital)}</td>
            <td style="text-align:right;font-size:.85rem">${formatMoney(c.monto_interes)}</td>
            <td style="text-align:right;font-weight:700">${formatMoney(c.monto_total)}</td>
            <td>${badgeCuota(c.estado)}</td>
            <td style="font-size:.8rem;color:var(--text-muted)">${c.fecha_pago ? formatDate(c.fecha_pago) : '—'}</td>
            <td style="text-align:center">
                ${c.estado !== 'pagado' && p.estado === 'activo'
                    ? `<button class="btn btn-outline" style="padding:2px 8px;font-size:.72rem;color:#16a34a;border-color:#bbf7d0"
                               onclick="abrirPago(${c.id}, ${c.numero_cuota}, ${c.monto_total})">
                           <i class="fa fa-check"></i> Pagar
                       </button>`
                    : ''}
            </td>
        </tr>`).join('');

        Modal.open('modalDetalle');
    } catch(e) { Toast.show('Error al cargar detalle', 'error'); }
}

// ================================================================
// PAGO DE CUOTA
// ================================================================
function abrirPago(cuotaId, num, total) {
    document.getElementById('pagoIdCuota').value = cuotaId;
    document.getElementById('pagoFecha').value   = new Date().toISOString().slice(0,10);
    document.getElementById('pagoObs').value     = '';
    document.getElementById('pagoInfo').innerHTML =
        `Cuota <strong>#${num}</strong> — Monto: <strong style="color:var(--primary)">${formatMoney(total)}</strong>`;
    Modal.open('modalPago');
}

async function confirmarPago() {
    const id    = document.getElementById('pagoIdCuota').value;
    const fecha = document.getElementById('pagoFecha').value;
    const obs   = document.getElementById('pagoObs').value.trim();
    if (!fecha) { Toast.show('Seleccione la fecha de pago', 'error'); return; }
    try {
        const res = await apiPost(API, { action:'pagar_cuota', cuota_id:id, fecha_pago:fecha, observaciones:obs });
        if (!res.ok) { Toast.show(res.error || 'Error', 'error'); return; }
        Modal.close('modalPago');
        Toast.show('Pago registrado', 'success');
        cargarPrestamos();
        if (prestamoActual) verDetalle(prestamoActual.id);
    } catch(e) { Toast.show('Error de conexión', 'error'); }
}

// ================================================================
// CANCELAR PRÉSTAMO
// ================================================================
async function cancelarPrestamo() {
    if (!prestamoActual) return;
    if (!confirm('¿Cancelar este préstamo? Esta acción no se puede deshacer.')) return;
    try {
        const res = await apiPost(API, { action:'cancelar_prestamo', id: prestamoActual.id });
        if (!res.ok) { Toast.show(res.error || 'Error', 'error'); return; }
        Modal.close('modalDetalle');
        Toast.show('Préstamo cancelado', 'success');
        cargarPrestamos();
    } catch(e) { Toast.show('Error de conexión', 'error'); }
}

// ================================================================
// BADGES / HELPERS
// ================================================================
function badgeTipo(tipo) {
    return tipo === 'recibido'
        ? '<span class="badge" style="background:#fef3c7;color:#92400e;font-size:.72rem">Recibido</span>'
        : '<span class="badge" style="background:#dbeafe;color:#1e40af;font-size:.72rem">Otorgado</span>';
}
function badgeEstado(e) {
    const map = {
        activo:    'background:#dcfce7;color:#166534',
        pagado:    'background:#f1f5f9;color:#475569',
        vencido:   'background:#fee2e2;color:#991b1b',
        cancelado: 'background:#f9fafb;color:#9ca3af',
    };
    const labels = { activo:'Activo', pagado:'Pagado', vencido:'Vencido', cancelado:'Cancelado' };
    return `<span class="badge" style="${map[e]||''};font-size:.72rem">${labels[e]||e}</span>`;
}
function badgeCuota(e) {
    if (e === 'pagado')   return '<span class="badge badge-completado" style="font-size:.7rem">Pagado</span>';
    if (e === 'vencido')  return '<span class="badge badge-retrasado"  style="font-size:.7rem">Vencido</span>';
    return '<span class="badge" style="background:#f1f5f9;color:#64748b;font-size:.7rem">Pendiente</span>';
}
</script>
<?php $extra_js = ob_get_clean(); ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

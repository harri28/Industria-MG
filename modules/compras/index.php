<?php
$page_title      = 'Compras y Proveedores';
$page_breadcrumb = 'Operaciones / Compras';
require_once __DIR__ . '/../../includes/header.php';
?>

<!-- ============================================================
     TABS
     ============================================================ -->
<div class="tabs" id="mainTabs">
    <div class="tab active" data-group="compras" data-target="tab-resumen"><i class="fa fa-gauge"></i> Resumen</div>
    <div class="tab" data-group="compras" data-target="tab-solicitudes"><i class="fa fa-file-lines"></i> Solicitudes</div>
    <div class="tab" data-group="compras" data-target="tab-ordenes"><i class="fa fa-cart-shopping"></i> Órdenes de Compra</div>
    <div class="tab" data-group="compras" data-target="tab-recepciones"><i class="fa fa-truck-ramp-box"></i> Recepciones</div>
    <div class="tab" data-group="compras" data-target="tab-proveedores"><i class="fa fa-building"></i> Proveedores</div>
</div>

<!-- ============================================================
     TAB: RESUMEN
     ============================================================ -->
<div class="tab-content active" data-group="compras" id="tab-resumen">
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon orange"><i class="fa fa-file-lines"></i></div>
            <div><div class="kpi-value" id="k-sc-pendientes">—</div><div class="kpi-label">SC Pendientes</div></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon blue"><i class="fa fa-cart-shopping"></i></div>
            <div><div class="kpi-value" id="k-oc-abiertas">—</div><div class="kpi-label">OC Abiertas</div></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon green"><i class="fa fa-money-bill-wave"></i></div>
            <div><div class="kpi-value" id="k-total-mes">—</div><div class="kpi-label">Compras este mes</div></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon cyan"><i class="fa fa-truck"></i></div>
            <div><div class="kpi-value" id="k-proveedores">—</div><div class="kpi-label">Proveedores activos</div></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon blue"><i class="fa fa-truck-ramp-box"></i></div>
            <div><div class="kpi-value" id="k-recepciones">—</div><div class="kpi-label">Recepciones hoy</div></div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:4px">
        <!-- Estados de OC -->
        <div class="card">
            <div class="card-header">Estado de Órdenes de Compra</div>
            <div class="card-body" id="estadosOC" style="padding:12px 16px"></div>
        </div>
        <!-- Últimas OC -->
        <div class="card">
            <div class="card-header">Últimas Órdenes</div>
            <div class="card-body" style="padding:0">
                <table class="table" id="ultimasOC">
                    <thead><tr><th>Número</th><th>Proveedor</th><th>Total</th><th>Estado</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     TAB: SOLICITUDES DE COMPRA
     ============================================================ -->
<div class="tab-content" data-group="compras" id="tab-solicitudes">
    <div class="toolbar">
        <div class="toolbar-left">
            <input type="text" class="form-control" id="scSearch" placeholder="Buscar por número u observaciones…" style="width:280px">
            <select class="form-control" id="scFiltroEstado" style="width:160px">
                <option value="">Todos los estados</option>
                <option value="pendiente">Pendiente</option>
                <option value="aprobada">Aprobada</option>
                <option value="rechazada">Rechazada</option>
                <option value="procesada">Procesada</option>
            </select>
        </div>
        <div class="toolbar-right">
            <button class="btn btn-primary" onclick="abrirModalSC()">
                <i class="fa fa-plus"></i> Nueva Solicitud
            </button>
        </div>
    </div>
    <div class="card">
        <table class="table" id="tablasc">
            <thead>
                <tr>
                    <th>Número</th><th>Tipo</th><th>Proyecto</th>
                    <th>Solicitante</th><th>Estado</th><th>Fecha</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- ============================================================
     TAB: ÓRDENES DE COMPRA
     ============================================================ -->
<div class="tab-content" data-group="compras" id="tab-ordenes">
    <div class="toolbar">
        <div class="toolbar-left">
            <input type="text" class="form-control" id="ocSearch" placeholder="Buscar por número o proveedor…" style="width:280px">
            <select class="form-control" id="ocFiltroEstado" style="width:160px">
                <option value="">Todos los estados</option>
                <option value="emitida">Emitida</option>
                <option value="enviada">Enviada</option>
                <option value="recibida_parcial">Recibida Parcial</option>
                <option value="recibida">Recibida</option>
                <option value="anulada">Anulada</option>
            </select>
        </div>
        <div class="toolbar-right">
            <button class="btn btn-primary" onclick="abrirModalOC()">
                <i class="fa fa-plus"></i> Nueva OC
            </button>
        </div>
    </div>
    <div class="card">
        <table class="table" id="tablaoc">
            <thead>
                <tr>
                    <th>Número</th><th>Proveedor</th><th>Total</th>
                    <th>F. Emisión</th><th>F. Entrega Est.</th><th>Estado</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- ============================================================
     TAB: RECEPCIONES
     ============================================================ -->
<div class="tab-content" data-group="compras" id="tab-recepciones">
    <div class="toolbar">
        <div class="toolbar-left">
            <input type="text" class="form-control" id="recSearch" placeholder="Buscar recepciones…" style="width:280px">
        </div>
        <div class="toolbar-right">
            <button class="btn btn-primary" onclick="abrirModalRecepcion()">
                <i class="fa fa-truck-ramp-box"></i> Registrar Recepción
            </button>
        </div>
    </div>
    <div class="card">
        <table class="table" id="tablarec">
            <thead>
                <tr>
                    <th>Número</th><th>OC</th><th>Proveedor</th>
                    <th>Fecha</th><th>Observaciones</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- ============================================================
     TAB: PROVEEDORES
     ============================================================ -->
<div class="tab-content" data-group="compras" id="tab-proveedores">
    <div class="toolbar">
        <div class="toolbar-left">
            <input type="text" class="form-control" id="provSearch" placeholder="Buscar proveedor…" style="width:280px">
            <select class="form-control" id="provFiltroActivo" style="width:140px">
                <option value="">Todos</option>
                <option value="true">Activos</option>
                <option value="false">Inactivos</option>
            </select>
        </div>
        <div class="toolbar-right">
            <button class="btn btn-primary" onclick="abrirModalProveedor()">
                <i class="fa fa-plus"></i> Nuevo Proveedor
            </button>
        </div>
    </div>
    <div class="card">
        <table class="table" id="tablaprov">
            <thead>
                <tr>
                    <th>Razón Social</th><th>RUC</th><th>Contacto</th>
                    <th>Teléfono</th><th>Calificación</th><th>OCs</th><th>Estado</th><th>Acciones</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- ============================================================
     MODAL: SOLICITUD DE COMPRA
     ============================================================ -->
<div class="modal-overlay" id="modalSC">
    <div class="modal" style="max-width:780px">
        <div class="modal-header">
            <h3 class="modal-title" id="modalSCTitle">Nueva Solicitud de Compra</h3>
            <button class="modal-close" onclick="Modal.close('modalSC')">×</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="sc_id">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
                <div class="form-group">
                    <label class="form-label">Proyecto (opcional)</label>
                    <select class="form-control" id="sc_proyecto_id">
                        <option value="">— Sin proyecto —</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Tipo</label>
                    <select class="form-control" id="sc_tipo">
                        <option value="manual">Manual</option>
                        <option value="automatica_bom">Automática por BOM</option>
                        <option value="automatica_stock">Automática por Stock</option>
                    </select>
                </div>
            </div>
            <div class="form-group" style="margin-bottom:16px">
                <label class="form-label">Observaciones</label>
                <textarea class="form-control" id="sc_observaciones" rows="2"></textarea>
            </div>

            <!-- Líneas de detalle -->
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
                <strong style="font-size:0.9rem">Ítems solicitados</strong>
                <button class="btn btn-secondary" style="padding:4px 12px;font-size:0.82rem" onclick="addLineasSC()">
                    <i class="fa fa-plus"></i> Agregar ítem
                </button>
            </div>
            <div style="overflow-x:auto">
                <table class="table" style="font-size:0.84rem">
                    <thead><tr>
                        <th style="width:36%">Producto</th>
                        <th style="width:15%">Cantidad</th>
                        <th style="width:18%">Precio Ref.</th>
                        <th style="width:25%">Justificación</th>
                        <th style="width:6%"></th>
                    </tr></thead>
                    <tbody id="scLineas"></tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('modalSC')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarSC()">Guardar Solicitud</button>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: ORDEN DE COMPRA
     ============================================================ -->
<div class="modal-overlay" id="modalOC">
    <div class="modal" style="max-width:820px">
        <div class="modal-header">
            <h3 class="modal-title" id="modalOCTitle">Nueva Orden de Compra</h3>
            <button class="modal-close" onclick="Modal.close('modalOC')">×</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="oc_id">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:16px">
                <div class="form-group">
                    <label class="form-label">Proveedor *</label>
                    <select class="form-control" id="oc_proveedor_id">
                        <option value="">— Seleccionar —</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Solicitud de Compra (opcional)</label>
                    <select class="form-control" id="oc_solicitud_id">
                        <option value="">— Ninguna —</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Fecha Entrega Estimada</label>
                    <input type="date" class="form-control" id="oc_fecha_entrega">
                </div>
            </div>
            <div class="form-group" style="margin-bottom:16px">
                <label class="form-label">Observaciones</label>
                <textarea class="form-control" id="oc_observaciones" rows="2"></textarea>
            </div>

            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
                <strong style="font-size:0.9rem">Ítems de la OC</strong>
                <button class="btn btn-secondary" style="padding:4px 12px;font-size:0.82rem" onclick="addLineaOC()">
                    <i class="fa fa-plus"></i> Agregar ítem
                </button>
            </div>
            <div style="overflow-x:auto">
                <table class="table" style="font-size:0.84rem">
                    <thead><tr>
                        <th style="width:33%">Producto</th>
                        <th style="width:12%">Cantidad</th>
                        <th style="width:15%">P. Unitario</th>
                        <th style="width:12%">Dto %</th>
                        <th style="width:15%">Subtotal</th>
                        <th style="width:5%"></th>
                    </tr></thead>
                    <tbody id="ocLineas"></tbody>
                </table>
            </div>
            <div style="text-align:right;margin-top:8px;font-size:0.9rem;line-height:1.8">
                Subtotal: <strong id="oc-subtotal">S/ 0.00</strong><br>
                IGV (18%): <strong id="oc-igv">S/ 0.00</strong><br>
                <span style="font-size:1.1rem">Total: <strong id="oc-total" style="color:var(--primary)">S/ 0.00</strong></span>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('modalOC')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarOC()">Guardar OC</button>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: RECEPCIÓN
     ============================================================ -->
<div class="modal-overlay" id="modalRecepcion">
    <div class="modal" style="max-width:760px">
        <div class="modal-header">
            <h3 class="modal-title">Registrar Recepción</h3>
            <button class="modal-close" onclick="Modal.close('modalRecepcion')">×</button>
        </div>
        <div class="modal-body">
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:16px">
                <div class="form-group" style="grid-column:1/3">
                    <label class="form-label">Orden de Compra *</label>
                    <select class="form-control" id="rec_oc_id" onchange="cargarDetallesOC()">
                        <option value="">— Seleccionar OC —</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Fecha</label>
                    <input type="date" class="form-control" id="rec_fecha">
                </div>
            </div>
            <div class="form-group" style="margin-bottom:16px">
                <label class="form-label">Observaciones</label>
                <textarea class="form-control" id="rec_observaciones" rows="2"></textarea>
            </div>
            <div id="recDetallesWrap">
                <p class="text-secondary" style="font-size:0.85rem">Seleccione una OC para ver los ítems pendientes de recepción.</p>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('modalRecepcion')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarRecepcion()">Registrar Recepción</button>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: PROVEEDOR
     ============================================================ -->
<div class="modal-overlay" id="modalProveedor">
    <div class="modal" style="max-width:560px">
        <div class="modal-header">
            <h3 class="modal-title" id="modalProvTitle">Nuevo Proveedor</h3>
            <button class="modal-close" onclick="Modal.close('modalProveedor')">×</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="prov_id">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div class="form-group" style="grid-column:1/3">
                    <label class="form-label">Razón Social *</label>
                    <input type="text" class="form-control" id="prov_razon_social">
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
                    <label class="form-label">Teléfono</label>
                    <input type="text" class="form-control" id="prov_telefono">
                </div>
                <div class="form-group" style="grid-column:1/3">
                    <label class="form-label">Dirección</label>
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
            <button class="btn btn-secondary" onclick="Modal.close('modalProveedor')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarProveedor()">Guardar</button>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: DETALLE SC / OC / RECEPCIÓN (sólo lectura)
     ============================================================ -->
<div class="modal-overlay" id="modalDetalle">
    <div class="modal" style="max-width:700px">
        <div class="modal-header">
            <h3 class="modal-title" id="modalDetalleTitle">Detalle</h3>
            <button class="modal-close" onclick="Modal.close('modalDetalle')">×</button>
        </div>
        <div class="modal-body" id="modalDetalleBody"></div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('modalDetalle')">Cerrar</button>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: EVALUACIÓN PROVEEDOR
     ============================================================ -->
<div class="modal-overlay" id="modalEval">
    <div class="modal" style="max-width:500px">
        <div class="modal-header">
            <h3 class="modal-title">Evaluar Proveedor</h3>
            <button class="modal-close" onclick="Modal.close('modalEval')">×</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="eval_proveedor_id">
            <div class="form-group" style="margin-bottom:12px">
                <label class="form-label">OC relacionada (opcional)</label>
                <select class="form-control" id="eval_oc_id">
                    <option value="">— Ninguna —</option>
                </select>
            </div>
            <?php
            foreach (['calidad' => 'Calidad', 'tiempo_entrega' => 'Tiempo de Entrega', 'precio' => 'Precio', 'servicio' => 'Servicio'] as $k => $label):
            ?>
            <div class="form-group" style="margin-bottom:12px">
                <label class="form-label"><?= $label ?> (1–5)</label>
                <div style="display:flex;gap:8px">
                    <?php for ($i=1; $i<=5; $i++): ?>
                    <label style="cursor:pointer;display:flex;align-items:center;gap:4px;font-size:0.88rem">
                        <input type="radio" name="eval_<?= $k ?>" value="<?= $i ?>" <?= $i==3?'checked':'' ?>> <?= $i ?>
                    </label>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <div class="form-group">
                <label class="form-label">Comentario</label>
                <textarea class="form-control" id="eval_comentario" rows="2"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('modalEval')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarEvaluacion()">Guardar Evaluación</button>
        </div>
    </div>
</div>

<?php
$extra_js = '';
require_once __DIR__ . '/../../includes/footer.php';
?>

<script>
// ============================================================
// ESTADO
// ============================================================
let combos = { proveedores: [], productos: [], proyectos: [], ocs_abiertas: [] };
let scActual = null, ocActual = null;

// ============================================================
// INIT
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    cargarDashboard();
    cargarCombos().then(() => {
        cargarSolicitudes();
        cargarOrdenes();
        cargarRecepciones();
        cargarProveedores();
    });

    document.getElementById('scSearch').addEventListener('input', debounce(cargarSolicitudes, 350));
    document.getElementById('scFiltroEstado').addEventListener('change', cargarSolicitudes);
    document.getElementById('ocSearch').addEventListener('input', debounce(cargarOrdenes, 350));
    document.getElementById('ocFiltroEstado').addEventListener('change', cargarOrdenes);
    document.getElementById('recSearch').addEventListener('input', debounce(cargarRecepciones, 350));
    document.getElementById('provSearch').addEventListener('input', debounce(cargarProveedores, 350));
    document.getElementById('provFiltroActivo').addEventListener('change', cargarProveedores);

    // Fecha default recepción
    document.getElementById('rec_fecha').value = new Date().toISOString().split('T')[0];
});

function debounce(fn, delay) {
    let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), delay); };
}

// ============================================================
// DASHBOARD
// ============================================================
async function cargarDashboard() {
    const d = await apiGet('modules/compras/api.php?action=dashboard');
    if (!d.ok) return;
    document.getElementById('k-sc-pendientes').textContent = d.sc_pendientes;
    document.getElementById('k-oc-abiertas').textContent   = d.oc_abiertas;
    document.getElementById('k-total-mes').textContent     = formatMoney(d.total_oc_mes);
    document.getElementById('k-proveedores').textContent   = d.proveedores_act;
    document.getElementById('k-recepciones').textContent   = d.recepciones_hoy;

    // Estados OC chart
    const estadosMap = { emitida: 'Emitida', enviada: 'Enviada',
        recibida_parcial: 'Recibida Parcial', recibida: 'Recibida', anulada: 'Anulada' };
    const totalOC = d.estados_oc.reduce((s, e) => s + parseInt(e.cnt), 0);
    document.getElementById('estadosOC').innerHTML = d.estados_oc.map(e => {
        const pct = totalOC > 0 ? Math.round(parseInt(e.cnt) / totalOC * 100) : 0;
        return `<div style="margin-bottom:10px">
            <div style="display:flex;justify-content:space-between;font-size:0.82rem;margin-bottom:3px">
                <span>${estadosMap[e.estado] || e.estado}</span>
                <span>${e.cnt} (${pct}%)</span>
            </div>
            <div style="background:#e2e8f0;border-radius:4px;height:8px">
                <div style="background:var(--primary);width:${pct}%;height:8px;border-radius:4px;transition:.4s"></div>
            </div>
        </div>`;
    }).join('') || '<p class="text-secondary" style="font-size:0.85rem">Sin datos</p>';

    // Últimas OC
    const tbody = document.querySelector('#ultimasOC tbody');
    tbody.innerHTML = d.ultimas_oc.map(oc => `<tr>
        <td><code>${oc.numero}</code></td>
        <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${oc.proveedor || '—'}</td>
        <td>${formatMoney(oc.total)}</td>
        <td>${badgeEstadoOC(oc.estado)}</td>
    </tr>`).join('') || '<tr><td colspan="4" class="text-secondary text-center">Sin datos</td></tr>';
}

// ============================================================
// COMBOS
// ============================================================
async function cargarCombos() {
    const d = await apiGet('modules/compras/api.php?action=combos');
    if (!d.ok) return;
    combos = d;

    // Proveedores
    const optsProv = '<option value="">— Seleccionar —</option>' + d.proveedores.map(p => `<option value="${p.id}">${p.razon_social}</option>`).join('');
    ['oc_proveedor_id'].forEach(id => { const el = document.getElementById(id); if (el) el.innerHTML = optsProv; });

    // Proyectos
    const optsPry = '<option value="">— Sin proyecto —</option>' + d.proyectos.map(p => `<option value="${p.id}">[${p.codigo}] ${p.nombre}</option>`).join('');
    document.getElementById('sc_proyecto_id').innerHTML = optsPry;

    // SC aprobadas para OC
    const stmtSC = await apiGet('modules/compras/api.php?action=solicitudes_listar&estado=aprobada');
    const optsSC = '<option value="">— Ninguna —</option>' + (stmtSC.solicitudes || []).map(s => `<option value="${s.id}">${s.numero}</option>`).join('');
    document.getElementById('oc_solicitud_id').innerHTML = optsSC;

    // OCs abiertas para recepción
    const optsOC = '<option value="">— Seleccionar OC —</option>' + d.ocs_abiertas.map(o => `<option value="${o.id}">${o.numero} — ${o.proveedor_nombre}</option>`).join('');
    document.getElementById('rec_oc_id').innerHTML = optsOC;
}

// ============================================================
// SOLICITUDES DE COMPRA
// ============================================================
async function cargarSolicitudes() {
    const q      = document.getElementById('scSearch').value;
    const estado = document.getElementById('scFiltroEstado').value;
    const d = await apiGet(`modules/compras/api.php?action=solicitudes_listar&q=${encodeURIComponent(q)}&estado=${estado}`);
    if (!d.ok) return;
    const tiposMap = { manual: 'Manual', automatica_bom: 'Auto BOM', automatica_stock: 'Auto Stock' };
    const tbody = document.querySelector('#tablasc tbody');
    tbody.innerHTML = d.solicitudes.map(sc => `<tr>
        <td><code>${sc.numero}</code></td>
        <td>${tiposMap[sc.tipo] || sc.tipo}</td>
        <td>${sc.proyecto_nombre || '—'}</td>
        <td>${sc.solicitante_nombre || '—'}</td>
        <td>${badgeEstadoSC(sc.estado)}</td>
        <td>${formatDate(sc.created_at)}</td>
        <td>
            <button class="btn btn-secondary" style="padding:3px 8px;font-size:0.78rem" onclick="verDetalleSC(${sc.id})">
                <i class="fa fa-eye"></i>
            </button>
            ${sc.estado === 'pendiente' ? `
            <button class="btn btn-primary" style="padding:3px 8px;font-size:0.78rem;background:var(--success)" onclick="cambiarEstadoSC(${sc.id},'aprobada')">
                <i class="fa fa-check"></i>
            </button>
            <button class="btn btn-secondary" style="padding:3px 8px;font-size:0.78rem;color:var(--danger)" onclick="cambiarEstadoSC(${sc.id},'rechazada')">
                <i class="fa fa-times"></i>
            </button>` : ''}
            ${sc.estado === 'aprobada' ? `
            <button class="btn btn-primary" style="padding:3px 8px;font-size:0.78rem" title="Crear OC desde esta SC" onclick="crearOCdesdeSC(${sc.id})">
                <i class="fa fa-file-invoice"></i>
            </button>` : ''}
        </td>
    </tr>`).join('') || '<tr><td colspan="7" class="text-secondary text-center" style="padding:20px">Sin solicitudes</td></tr>';
}

function badgeEstadoSC(e) {
    const m = { pendiente:'badge-warning', aprobada:'badge-success', rechazada:'badge-danger', procesada:'badge-secondary' };
    const l = { pendiente:'Pendiente', aprobada:'Aprobada', rechazada:'Rechazada', procesada:'Procesada' };
    return `<span class="badge ${m[e]||'badge-secondary'}">${l[e]||e}</span>`;
}

function abrirModalSC() {
    scActual = null;
    document.getElementById('sc_id').value = '';
    document.getElementById('sc_proyecto_id').value = '';
    document.getElementById('sc_tipo').value = 'manual';
    document.getElementById('sc_observaciones').value = '';
    document.getElementById('scLineas').innerHTML = '';
    addLineasSC();
    document.getElementById('modalSCTitle').textContent = 'Nueva Solicitud de Compra';
    Modal.open('modalSC');
}

function addLineasSC() {
    const optsProductos = combos.productos.map(p => `<option value="${p.id}" data-precio="${p.precio_promedio}">[${p.codigo}] ${p.nombre} (${p.unidad})</option>`).join('');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><select class="form-control" style="font-size:0.82rem"><option value="">— Seleccionar —</option>${optsProductos}</select></td>
        <td><input type="number" class="form-control" min="0.001" step="0.001" value="1" style="font-size:0.82rem"></td>
        <td><input type="number" class="form-control" min="0" step="0.01" placeholder="Ref." style="font-size:0.82rem"></td>
        <td><input type="text" class="form-control" placeholder="Motivo" style="font-size:0.82rem"></td>
        <td><button class="btn btn-secondary" style="padding:2px 7px;font-size:0.78rem;color:var(--danger)" onclick="this.closest('tr').remove()">×</button></td>`;
    document.getElementById('scLineas').appendChild(tr);
}

async function guardarSC() {
    const lineas = [...document.querySelectorAll('#scLineas tr')].map(tr => {
        const inputs = tr.querySelectorAll('input, select');
        return {
            producto_id:        inputs[0].value,
            cantidad:           inputs[1].value,
            precio_referencial: inputs[2].value,
            justificacion:      inputs[3].value,
        };
    }).filter(l => l.producto_id);
    if (!lineas.length) { Toast.warning('Agregue al menos un ítem'); return; }

    const body = {
        action:         'solicitud_guardar',
        id:             document.getElementById('sc_id').value || null,
        proyecto_id:    document.getElementById('sc_proyecto_id').value || null,
        tipo:           document.getElementById('sc_tipo').value,
        observaciones:  document.getElementById('sc_observaciones').value,
        detalles:       lineas,
    };
    const r = await apiPost('modules/compras/api.php', body);
    if (r.ok) { Toast.success('Solicitud guardada'); Modal.close('modalSC'); cargarSolicitudes(); cargarCombos(); }
    else Toast.error(r.error || 'Error al guardar');
}

async function cambiarEstadoSC(id, estado) {
    const r = await apiPost('modules/compras/api.php', { action: 'solicitud_estado', id, estado });
    if (r.ok) { Toast.success('Estado actualizado'); cargarSolicitudes(); }
    else Toast.error(r.error || 'Error');
}

async function verDetalleSC(id) {
    const d = await apiGet(`modules/compras/api.php?action=solicitud_obtener&id=${id}`);
    if (!d.ok) return;
    const sc = d.solicitud;
    document.getElementById('modalDetalleTitle').textContent = `Solicitud ${sc.numero}`;
    document.getElementById('modalDetalleBody').innerHTML = `
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;font-size:0.87rem">
            <div><strong>Número:</strong> ${sc.numero}</div>
            <div><strong>Estado:</strong> ${badgeEstadoSC(sc.estado)}</div>
            <div><strong>Proyecto:</strong> ${sc.proyecto_nombre || '—'}</div>
            <div><strong>Tipo:</strong> ${sc.tipo}</div>
            <div><strong>Solicitante:</strong> ${sc.solicitante_nombre || '—'}</div>
            <div><strong>Fecha:</strong> ${formatDate(sc.created_at)}</div>
        </div>
        ${sc.observaciones ? `<p style="font-size:0.85rem;margin-bottom:12px"><strong>Observaciones:</strong> ${sc.observaciones}</p>` : ''}
        <table class="table" style="font-size:0.83rem">
            <thead><tr><th>Producto</th><th>Cant.</th><th>Precio Ref.</th><th>Justificación</th></tr></thead>
            <tbody>${sc.detalles.map(d => `<tr>
                <td>[${d.codigo}] ${d.producto_nombre}</td>
                <td>${d.cantidad} ${d.unidad}</td>
                <td>${d.precio_referencial ? formatMoney(d.precio_referencial) : '—'}</td>
                <td>${d.justificacion || '—'}</td>
            </tr>`).join('')}</tbody>
        </table>`;
    Modal.open('modalDetalle');
}

async function crearOCdesdeSC(scId) {
    const d = await apiGet(`modules/compras/api.php?action=solicitud_obtener&id=${scId}`);
    if (!d.ok) return;
    abrirModalOC();
    document.getElementById('oc_solicitud_id').value = scId;
    // Prellenar líneas con los productos de la SC
    document.getElementById('ocLineas').innerHTML = '';
    for (const det of d.solicitud.detalles) {
        addLineaOC(det.producto_id, det.cantidad, det.precio_referencial || 0);
    }
    recalcularOC();
}

// ============================================================
// ÓRDENES DE COMPRA
// ============================================================
async function cargarOrdenes() {
    const q      = document.getElementById('ocSearch').value;
    const estado = document.getElementById('ocFiltroEstado').value;
    const d = await apiGet(`modules/compras/api.php?action=oc_listar&q=${encodeURIComponent(q)}&estado=${estado}`);
    if (!d.ok) return;
    const tbody = document.querySelector('#tablaoc tbody');
    tbody.innerHTML = d.ordenes.map(oc => `<tr>
        <td><code>${oc.numero}</code></td>
        <td>${oc.proveedor_nombre || '—'}</td>
        <td><strong>${formatMoney(oc.total)}</strong></td>
        <td>${formatDate(oc.fecha_emision)}</td>
        <td>${oc.fecha_entrega_estimada ? formatDate(oc.fecha_entrega_estimada) : '—'}</td>
        <td>${badgeEstadoOC(oc.estado)}</td>
        <td>
            <button class="btn btn-secondary" style="padding:3px 8px;font-size:0.78rem" onclick="verDetalleOC(${oc.id})">
                <i class="fa fa-eye"></i>
            </button>
            ${oc.estado === 'emitida' ? `
            <button class="btn btn-primary" style="padding:3px 8px;font-size:0.78rem" onclick="cambiarEstadoOC(${oc.id},'enviada')">
                Enviar
            </button>
            <button class="btn btn-secondary" style="padding:3px 8px;font-size:0.78rem;color:var(--danger)" onclick="eliminarOC(${oc.id})">
                <i class="fa fa-trash"></i>
            </button>` : ''}
            ${oc.estado === 'enviada' ? `
            <button class="btn btn-primary" style="padding:3px 8px;font-size:0.78rem;background:var(--success)" onclick="cambiarEstadoOC(${oc.id},'recibida')">
                Recibida
            </button>` : ''}
        </td>
    </tr>`).join('') || '<tr><td colspan="7" class="text-secondary text-center" style="padding:20px">Sin órdenes</td></tr>';
}

function badgeEstadoOC(e) {
    const m = {
        emitida:'badge-warning', enviada:'badge-primary',
        recibida_parcial:'badge-warning', recibida:'badge-success', anulada:'badge-danger'
    };
    const l = {
        emitida:'Emitida', enviada:'Enviada',
        recibida_parcial:'Rec. Parcial', recibida:'Recibida', anulada:'Anulada'
    };
    return `<span class="badge ${m[e]||'badge-secondary'}">${l[e]||e}</span>`;
}

function abrirModalOC() {
    ocActual = null;
    document.getElementById('oc_id').value = '';
    document.getElementById('oc_proveedor_id').value = '';
    document.getElementById('oc_solicitud_id').value = '';
    document.getElementById('oc_fecha_entrega').value = '';
    document.getElementById('oc_observaciones').value = '';
    document.getElementById('ocLineas').innerHTML = '';
    addLineaOC();
    recalcularOC();
    document.getElementById('modalOCTitle').textContent = 'Nueva Orden de Compra';
    Modal.open('modalOC');
}

function addLineaOC(prod_id = '', qty = 1, pu = 0) {
    const optsProductos = combos.productos.map(p =>
        `<option value="${p.id}" data-pu="${p.precio_promedio}" ${p.id == prod_id ? 'selected' : ''}>[${p.codigo}] ${p.nombre} (${p.unidad})</option>`
    ).join('');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><select class="form-control" style="font-size:0.82rem" onchange="autoFillPU(this)"><option value="">—</option>${optsProductos}</select></td>
        <td><input type="number" class="form-control" min="0.001" step="0.001" value="${qty}" style="font-size:0.82rem" oninput="recalcularLineaOC(this)"></td>
        <td><input type="number" class="form-control" min="0" step="0.01" value="${parseFloat(pu).toFixed(2)}" style="font-size:0.82rem" oninput="recalcularLineaOC(this)"></td>
        <td><input type="number" class="form-control" min="0" max="100" step="0.1" value="0" style="font-size:0.82rem" oninput="recalcularLineaOC(this)"></td>
        <td class="lin-sub" style="font-size:0.82rem;text-align:right">S/ 0.00</td>
        <td><button class="btn btn-secondary" style="padding:2px 7px;font-size:0.78rem;color:var(--danger)" onclick="this.closest('tr').remove();recalcularOC()">×</button></td>`;
    document.getElementById('ocLineas').appendChild(tr);
    recalcularOC();
}

function autoFillPU(sel) {
    const opt = sel.options[sel.selectedIndex];
    const pu = parseFloat(opt.dataset.pu || 0);
    const tr = sel.closest('tr');
    tr.querySelectorAll('input')[1].value = pu.toFixed(2);
    recalcularLineaOC(tr.querySelectorAll('input')[0]);
}

function recalcularLineaOC(input) {
    const tr  = input.closest('tr');
    const ins = tr.querySelectorAll('input');
    const qty = parseFloat(ins[0].value) || 0;
    const pu  = parseFloat(ins[1].value) || 0;
    const dto = parseFloat(ins[2].value) || 0;
    const sub = qty * pu * (1 - dto / 100);
    tr.querySelector('.lin-sub').textContent = formatMoney(sub);
    recalcularOC();
}

function recalcularOC() {
    let sub = 0;
    document.querySelectorAll('#ocLineas tr').forEach(tr => {
        const ins = tr.querySelectorAll('input');
        if (ins.length < 3) return;
        const qty = parseFloat(ins[0].value) || 0;
        const pu  = parseFloat(ins[1].value) || 0;
        const dto = parseFloat(ins[2].value) || 0;
        sub += qty * pu * (1 - dto / 100);
    });
    const igv   = sub * 0.18;
    const total = sub + igv;
    document.getElementById('oc-subtotal').textContent = formatMoney(sub);
    document.getElementById('oc-igv').textContent      = formatMoney(igv);
    document.getElementById('oc-total').textContent    = formatMoney(total);
}

async function guardarOC() {
    const provId = document.getElementById('oc_proveedor_id').value;
    if (!provId) { Toast.warning('Seleccione un proveedor'); return; }

    const lineas = [...document.querySelectorAll('#ocLineas tr')].map(tr => {
        const sel = tr.querySelector('select');
        const ins = tr.querySelectorAll('input');
        return {
            producto_id:    sel?.value,
            cantidad:       ins[0]?.value,
            precio_unitario:ins[1]?.value,
            descuento:      ins[2]?.value,
        };
    }).filter(l => l.producto_id);
    if (!lineas.length) { Toast.warning('Agregue al menos un ítem'); return; }

    const body = {
        action:                  'oc_guardar',
        id:                      document.getElementById('oc_id').value || null,
        proveedor_id:            provId,
        solicitud_id:            document.getElementById('oc_solicitud_id').value || null,
        fecha_entrega_estimada:  document.getElementById('oc_fecha_entrega').value || null,
        observaciones:           document.getElementById('oc_observaciones').value,
        detalles:                lineas,
    };
    const r = await apiPost('modules/compras/api.php', body);
    if (r.ok) {
        Toast.success(`OC ${r.numero || ''} guardada`);
        Modal.close('modalOC');
        cargarOrdenes();
        cargarDashboard();
        cargarCombos();
    } else Toast.error(r.error || 'Error al guardar');
}

async function cambiarEstadoOC(id, estado) {
    const r = await apiPost('modules/compras/api.php', { action: 'oc_estado', id, estado });
    if (r.ok) { Toast.success('Estado actualizado'); cargarOrdenes(); cargarDashboard(); cargarCombos(); }
    else Toast.error(r.error || 'Error');
}

async function eliminarOC(id) {
    if (!confirm('¿Eliminar esta OC?')) return;
    const r = await apiPost('modules/compras/api.php', { action: 'oc_eliminar', id });
    if (r.ok) { Toast.success('OC eliminada'); cargarOrdenes(); cargarDashboard(); }
    else Toast.error(r.error || 'Error');
}

async function verDetalleOC(id) {
    const d = await apiGet(`modules/compras/api.php?action=oc_obtener&id=${id}`);
    if (!d.ok) return;
    const oc = d.oc;
    document.getElementById('modalDetalleTitle').textContent = `Orden de Compra ${oc.numero}`;
    document.getElementById('modalDetalleBody').innerHTML = `
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;font-size:0.87rem">
            <div><strong>Número:</strong> ${oc.numero}</div>
            <div><strong>Estado:</strong> ${badgeEstadoOC(oc.estado)}</div>
            <div><strong>Proveedor:</strong> ${oc.proveedor_nombre}</div>
            <div><strong>Email:</strong> ${oc.proveedor_email || '—'}</div>
            <div><strong>Emisión:</strong> ${formatDate(oc.fecha_emision)}</div>
            <div><strong>Entrega Est.:</strong> ${oc.fecha_entrega_estimada ? formatDate(oc.fecha_entrega_estimada) : '—'}</div>
        </div>
        <table class="table" style="font-size:0.83rem">
            <thead><tr><th>Producto</th><th>Cant.</th><th>P.Unit.</th><th>Dto%</th><th>Subtotal</th></tr></thead>
            <tbody>${oc.detalles.map(d => `<tr>
                <td>[${d.codigo}] ${d.producto_nombre}</td>
                <td>${d.cantidad} ${d.unidad}</td>
                <td>${formatMoney(d.precio_unitario)}</td>
                <td>${d.descuento}%</td>
                <td>${formatMoney(d.subtotal)}</td>
            </tr>`).join('')}</tbody>
        </table>
        <div style="text-align:right;font-size:0.87rem;line-height:1.9;margin-top:8px">
            Subtotal: <strong>${formatMoney(oc.subtotal)}</strong><br>
            IGV (18%): <strong>${formatMoney(oc.igv)}</strong><br>
            <strong style="font-size:1rem">Total: ${formatMoney(oc.total)}</strong>
        </div>`;
    Modal.open('modalDetalle');
}

// ============================================================
// RECEPCIONES
// ============================================================
async function cargarRecepciones() {
    const q = document.getElementById('recSearch').value;
    const d = await apiGet(`modules/compras/api.php?action=recepciones_listar&q=${encodeURIComponent(q)}`);
    if (!d.ok) return;
    const tbody = document.querySelector('#tablarec tbody');
    tbody.innerHTML = d.recepciones.map(r => `<tr>
        <td><code>${r.numero}</code></td>
        <td><code>${r.oc_numero || '—'}</code></td>
        <td>${r.proveedor_nombre || '—'}</td>
        <td>${formatDate(r.fecha)}</td>
        <td>${r.observaciones || '—'}</td>
        <td>
            <button class="btn btn-secondary" style="padding:3px 8px;font-size:0.78rem" onclick="verDetalleRecepcion(${r.id})">
                <i class="fa fa-eye"></i>
            </button>
        </td>
    </tr>`).join('') || '<tr><td colspan="6" class="text-secondary text-center" style="padding:20px">Sin recepciones</td></tr>';
}

function abrirModalRecepcion() {
    document.getElementById('rec_oc_id').value = '';
    document.getElementById('rec_fecha').value = new Date().toISOString().split('T')[0];
    document.getElementById('rec_observaciones').value = '';
    document.getElementById('recDetallesWrap').innerHTML = '<p class="text-secondary" style="font-size:0.85rem">Seleccione una OC para ver los ítems pendientes de recepción.</p>';
    Modal.open('modalRecepcion');
}

async function cargarDetallesOC() {
    const ocId = document.getElementById('rec_oc_id').value;
    if (!ocId) { document.getElementById('recDetallesWrap').innerHTML = ''; return; }
    const d = await apiGet(`modules/compras/api.php?action=oc_detalles_para_recepcion&oc_id=${ocId}`);
    if (!d.ok) return;
    if (!d.detalles.length) {
        document.getElementById('recDetallesWrap').innerHTML = '<p class="text-secondary" style="font-size:0.85rem">Esta OC no tiene ítems pendientes.</p>';
        return;
    }
    document.getElementById('recDetallesWrap').innerHTML = `
        <table class="table" style="font-size:0.83rem" id="recDetallesTable">
            <thead><tr>
                <th>Producto</th><th>Pedido</th><th>Ya Recibido</th>
                <th>A Recibir</th><th>Estado</th>
            </tr></thead>
            <tbody>${d.detalles.map(det => {
                const pendiente = parseFloat(det.cantidad) - parseFloat(det.recibido);
                return `<tr data-id="${det.oc_detalle_id}">
                    <td>[${det.codigo}] ${det.producto_nombre} (${det.unidad})</td>
                    <td>${det.cantidad}</td>
                    <td>${det.recibido}</td>
                    <td><input type="number" class="form-control" value="${Math.max(0, pendiente).toFixed(3)}"
                               min="0" step="0.001" max="${pendiente}" style="font-size:0.82rem;width:100px"></td>
                    <td><select class="form-control" style="font-size:0.82rem;width:120px">
                        <option value="conforme">Conforme</option>
                        <option value="disconforme">Disconforme</option>
                        <option value="devuelto">Devuelto</option>
                    </select></td>
                </tr>`;
            }).join('')}</tbody>
        </table>`;
}

async function guardarRecepcion() {
    const ocId = document.getElementById('rec_oc_id').value;
    if (!ocId) { Toast.warning('Seleccione una OC'); return; }
    const filas = document.querySelectorAll('#recDetallesTable tbody tr');
    const detalles = [...filas].map(tr => ({
        oc_detalle_id:    tr.dataset.id,
        cantidad_recibida: tr.querySelector('input').value,
        estado:           tr.querySelector('select').value,
    })).filter(d => parseFloat(d.cantidad_recibida) > 0);
    if (!detalles.length) { Toast.warning('Ingrese al menos una cantidad a recibir'); return; }

    const r = await apiPost('modules/compras/api.php', {
        action:       'recepcion_crear',
        oc_id:        ocId,
        fecha:        document.getElementById('rec_fecha').value,
        observaciones:document.getElementById('rec_observaciones').value,
        detalles,
    });
    if (r.ok) {
        Toast.success(`Recepción ${r.numero} registrada`);
        Modal.close('modalRecepcion');
        cargarRecepciones();
        cargarDashboard();
        cargarCombos();
    } else Toast.error(r.error || 'Error al registrar');
}

async function verDetalleRecepcion(id) {
    const d = await apiGet(`modules/compras/api.php?action=recepcion_obtener&id=${id}`);
    if (!d.ok) return;
    const rec = d.recepcion;
    document.getElementById('modalDetalleTitle').textContent = `Recepción ${rec.numero}`;
    document.getElementById('modalDetalleBody').innerHTML = `
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;font-size:0.87rem">
            <div><strong>Número:</strong> ${rec.numero}</div>
            <div><strong>OC:</strong> ${rec.oc_numero || '—'}</div>
            <div><strong>Proveedor:</strong> ${rec.proveedor_nombre || '—'}</div>
            <div><strong>Fecha:</strong> ${formatDate(rec.fecha)}</div>
        </div>
        ${rec.observaciones ? `<p style="font-size:0.85rem;margin-bottom:12px"><strong>Observaciones:</strong> ${rec.observaciones}</p>` : ''}
        <table class="table" style="font-size:0.83rem">
            <thead><tr><th>Producto</th><th>Pedido</th><th>Recibido</th><th>P.Unit.</th><th>Estado</th></tr></thead>
            <tbody>${rec.detalles.map(d => `<tr>
                <td>[${d.codigo}] ${d.producto_nombre}</td>
                <td>${d.cantidad_pedida} ${d.unidad}</td>
                <td>${d.cantidad_recibida} ${d.unidad}</td>
                <td>${formatMoney(d.precio_unitario)}</td>
                <td><span class="badge ${d.estado==='conforme'?'badge-success':d.estado==='disconforme'?'badge-warning':'badge-danger'}">${d.estado}</span></td>
            </tr>`).join('')}</tbody>
        </table>`;
    Modal.open('modalDetalle');
}

// ============================================================
// PROVEEDORES
// ============================================================
async function cargarProveedores() {
    const q      = document.getElementById('provSearch').value;
    const activo = document.getElementById('provFiltroActivo').value;
    const d = await apiGet(`modules/compras/api.php?action=proveedores_listar&q=${encodeURIComponent(q)}&activo=${activo}`);
    if (!d.ok) return;
    const tbody = document.querySelector('#tablaprov tbody');
    tbody.innerHTML = d.proveedores.map(p => `<tr>
        <td><strong>${p.razon_social}</strong></td>
        <td>${p.ruc || '—'}</td>
        <td>${p.contacto_nombre || '—'}</td>
        <td>${p.telefono || '—'}</td>
        <td>${estrellas(p.calificacion)}</td>
        <td>${p.total_oc}</td>
        <td><span class="badge ${p.activo?'badge-success':'badge-secondary'}">${p.activo?'Activo':'Inactivo'}</span></td>
        <td>
            <button class="btn btn-secondary" style="padding:3px 8px;font-size:0.78rem" onclick="editarProveedor(${p.id})">
                <i class="fa fa-edit"></i>
            </button>
            <button class="btn btn-secondary" style="padding:3px 8px;font-size:0.78rem" title="Evaluar" onclick="abrirEval(${p.id})">
                <i class="fa fa-star"></i>
            </button>
            <button class="btn btn-secondary" style="padding:3px 8px;font-size:0.78rem" title="${p.activo?'Desactivar':'Activar'}" onclick="toggleProveedor(${p.id})">
                <i class="fa fa-power-off"></i>
            </button>
        </td>
    </tr>`).join('') || '<tr><td colspan="8" class="text-secondary text-center" style="padding:20px">Sin proveedores</td></tr>';
}

function estrellas(cal) {
    if (!cal) return '—';
    const n = parseFloat(cal);
    let html = '';
    for (let i = 1; i <= 5; i++) {
        html += `<i class="fa fa-star" style="color:${i<=n?'#f97316':'#e2e8f0'};font-size:0.75rem"></i>`;
    }
    return html + ` <small style="color:#64748b">(${n})</small>`;
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
    document.getElementById('modalProvTitle').textContent = 'Nuevo Proveedor';
    Modal.open('modalProveedor');
}

async function editarProveedor(id) {
    const d = await apiGet(`modules/compras/api.php?action=proveedor_obtener&id=${id}`);
    if (!d.ok) return;
    const p = d.proveedor;
    document.getElementById('prov_id').value            = p.id;
    document.getElementById('prov_razon_social').value  = p.razon_social;
    document.getElementById('prov_ruc').value           = p.ruc || '';
    document.getElementById('prov_contacto').value      = p.contacto_nombre || '';
    document.getElementById('prov_email').value         = p.email || '';
    document.getElementById('prov_telefono').value      = p.telefono || '';
    document.getElementById('prov_direccion').value     = p.direccion || '';
    document.getElementById('prov_activo').value        = p.activo ? '1' : '0';
    document.getElementById('modalProvTitle').textContent = 'Editar Proveedor';
    Modal.open('modalProveedor');
}

async function guardarProveedor() {
    const rs = document.getElementById('prov_razon_social').value.trim();
    if (!rs) { Toast.warning('La razón social es obligatoria'); return; }
    const body = {
        action:          'proveedor_guardar',
        id:              document.getElementById('prov_id').value || null,
        razon_social:    rs,
        ruc:             document.getElementById('prov_ruc').value,
        contacto_nombre: document.getElementById('prov_contacto').value,
        email:           document.getElementById('prov_email').value,
        telefono:        document.getElementById('prov_telefono').value,
        direccion:       document.getElementById('prov_direccion').value,
        activo:          document.getElementById('prov_activo').value === '1',
    };
    const r = await apiPost('modules/compras/api.php', body);
    if (r.ok) { Toast.success('Proveedor guardado'); Modal.close('modalProveedor'); cargarProveedores(); cargarCombos(); }
    else Toast.error(r.error || 'Error al guardar');
}

async function toggleProveedor(id) {
    const r = await apiPost('modules/compras/api.php', { action: 'proveedor_toggle', id });
    if (r.ok) { Toast.success('Estado actualizado'); cargarProveedores(); }
    else Toast.error(r.error || 'Error');
}

// ============================================================
// EVALUACIONES
// ============================================================
async function abrirEval(provId) {
    document.getElementById('eval_proveedor_id').value = provId;
    document.getElementById('eval_comentario').value = '';
    // Cargar OCs de este proveedor
    const d = await apiGet(`modules/compras/api.php?action=oc_listar&proveedor_id=${provId}`);
    const opts = '<option value="">— Ninguna —</option>' + (d.ordenes || []).map(o => `<option value="${o.id}">${o.numero}</option>`).join('');
    document.getElementById('eval_oc_id').innerHTML = opts;
    // Reset radios
    document.querySelectorAll('[name^="eval_"]').forEach(r => { if (r.value == 3) r.checked = true; });
    Modal.open('modalEval');
}

async function guardarEvaluacion() {
    const provId = document.getElementById('eval_proveedor_id').value;
    const getRadio = (name) => document.querySelector(`[name="eval_${name}"]:checked`)?.value || 3;
    const body = {
        action:         'evaluacion_guardar',
        proveedor_id:   provId,
        oc_id:          document.getElementById('eval_oc_id').value || null,
        calidad:        getRadio('calidad'),
        tiempo_entrega: getRadio('tiempo_entrega'),
        precio:         getRadio('precio'),
        servicio:       getRadio('servicio'),
        comentario:     document.getElementById('eval_comentario').value,
    };
    const r = await apiPost('modules/compras/api.php', body);
    if (r.ok) { Toast.success('Evaluación guardada'); Modal.close('modalEval'); cargarProveedores(); }
    else Toast.error(r.error || 'Error al guardar');
}
</script>

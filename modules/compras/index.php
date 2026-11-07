<?php
$page_title      = 'Compras y Proveedores';
$page_breadcrumb = 'Operaciones / Compras';
require_once __DIR__ . '/../../includes/header.php';
?>
<style>
.oc-ac { position: relative; }
.oc-ac .oc-prod[readonly] { background: #f8fafc; cursor: default; padding-right: 26px; }
.oc-prod-clear {
    position: absolute; right: 6px; top: 50%; transform: translateY(-50%);
    border: none; background: transparent; color: var(--text-muted);
    cursor: pointer; display: none; font-size: .85rem; padding: 2px 4px; line-height: 1;
}
.oc-prod-clear:hover { color: var(--danger); }
.sc-head-grid .form-group { margin-bottom: 0; }
/* Editor de texto enriquecido (Observaciones) */
.rte-toolbar { display:flex; flex-wrap:wrap; gap:4px; align-items:center;
    border:1px solid var(--border); border-bottom:none; border-radius:8px 8px 0 0;
    padding:6px 8px; background:#f8fafc; }
.rte-toolbar button { border:1px solid transparent; background:#fff; border-radius:5px;
    min-width:30px; height:28px; cursor:pointer; font-size:.82rem; color:var(--text-primary); }
.rte-toolbar button:hover { border-color:var(--border); }
.rte-toolbar select { height:28px; border:1px solid var(--border); border-radius:5px;
    font-size:.78rem; background:#fff; padding:0 6px; }
.rte-sep { width:1px; height:18px; background:var(--border); margin:0 4px; }
.rte-editor { border:1px solid var(--border); border-radius:0 0 8px 8px;
    min-height:90px; max-height:220px; overflow-y:auto; padding:10px 12px;
    font-size:.85rem; line-height:1.5; outline:none; background:#fff; }
.rte-editor:focus { border-color:var(--primary); box-shadow:0 0 0 3px var(--primary-glow); }
.rte-editor ul { margin:4px 0 4px 20px; list-style:disc; }
.rte-editor ol { margin:4px 0 4px 20px; list-style:decimal; }
.rte-editor:empty:before { content:attr(data-ph); color:var(--text-muted); }
.sc-head-grid .form-label { margin-bottom: 3px; }
.oc-num-chip {
    display: inline-flex; align-items: center;
    background: #e0e7ff; color: #1d4ed8; font-weight: 700;
    font-size: .78rem; letter-spacing: .02em;
    padding: 3px 11px; border-radius: 999px;
}
/* Sin flechas (spinners) en Cantidad / P. Unitario / Sub. Total / Costo de envío: el step corrompe los valores */
.oc-qty::-webkit-inner-spin-button, .oc-qty::-webkit-outer-spin-button,
.oc-pu::-webkit-inner-spin-button,  .oc-pu::-webkit-outer-spin-button,
.oc-sub::-webkit-inner-spin-button, .oc-sub::-webkit-outer-spin-button,
#oc_costo_envio::-webkit-inner-spin-button, #oc_costo_envio::-webkit-outer-spin-button {
    -webkit-appearance: none; margin: 0;
}
.oc-qty, .oc-pu, .oc-sub, #oc_costo_envio { -moz-appearance: textfield; appearance: textfield; }
#ocAcGlobal {
    position: fixed; z-index: 99999; display: none; min-width: 200px;
    background: #fff; border: 1px solid var(--border); border-radius: 8px;
    box-shadow: 0 10px 28px rgba(15,23,42,.18);
    max-height: 240px; overflow-y: auto;
}
.oc-ac-item {
    padding: 8px 11px; font-size: .8rem; cursor: pointer;
    display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #f1f5f9;
}
.oc-ac-item:last-child { border-bottom: none; }
.oc-ac-item:hover, .oc-ac-item.active { background: #eff4ff; }
.oc-ac-cod { color: var(--primary); font-weight: 600; }
.oc-ac-um  { margin-left: auto; color: var(--text-muted); font-size: .72rem;
             background: #f1f5f9; padding: 1px 7px; border-radius: 10px; }
.oc-ac-empty { padding: 9px 11px; font-size: .8rem; color: var(--text-muted); }
</style>

<!-- ============================================================
     TABS
     ============================================================ -->
<div class="tabs" id="mainTabs">
    <div class="tab active" data-group="compras" data-target="tab-ordenes"><i class="fa fa-cart-shopping"></i> Órdenes de Compra</div>
    <div class="tab" data-group="compras" data-target="tab-solicitudes"><i class="fa fa-file-lines"></i> Solicitudes</div>
    <div class="tab" data-group="compras" data-target="tab-recepciones"><i class="fa fa-truck-ramp-box"></i> Recepciones</div>
    <div class="tab" data-group="compras" data-target="tab-proveedores"><i class="fa fa-building"></i> Proveedores</div>
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
<div class="tab-content active" data-group="compras" id="tab-ordenes">
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
            <span class="text-secondary" style="font-size:.82rem;display:inline-flex;align-items:center;gap:6px;margin-right:4px">
                <i class="fa fa-circle-info"></i> Vista informativa
            </span>
            <a class="btn btn-secondary" href="<?= APP_BASE ?>modules/clientes/index.php#proveedores">
                <i class="fa fa-arrow-up-right-from-square"></i> Gestionar en Clientes
            </a>
        </div>
    </div>
    <div class="card">
        <table class="table" id="tablaprov">
            <thead>
                <tr>
                    <th>Razón Social</th><th>RUC</th><th>Contacto</th>
                    <th>Teléfono</th><th>Estado</th>
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
            <h3 class="modal-title" id="modalSCTitle" style="display:flex;align-items:center;gap:10px"><span>Nueva Solicitud de Compra</span> <span class="oc-num-chip" id="scNumChip">—</span></h3>
            <button class="modal-close" onclick="Modal.close('modalSC')">×</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="sc_id">
            <input type="hidden" id="sc_proveedor_id">

            <div class="sc-head-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:10px 14px;margin-bottom:10px">
                <div class="form-group">
                    <label class="form-label">RUC o DNI</label>
                    <div class="input-lookup" style="display:grid;grid-template-columns:1fr auto;gap:8px">
                        <input type="text" class="form-control" id="sc_prov_ruc" placeholder="Ingrese RUC o DNI" onkeydown="if(event.key==='Enter'){event.preventDefault();buscarProveedorSC();}">
                        <button type="button" class="btn btn-outline" onclick="buscarProveedorSC()"><i class="fa fa-search"></i> Buscar</button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Razón Social</label>
                    <input type="text" class="form-control" id="sc_prov_razon" readonly style="background:#f8fafc">
                </div>
                <div class="form-group">
                    <label class="form-label">Número de celular</label>
                    <input type="text" class="form-control" id="sc_prov_tel" readonly style="background:#f8fafc">
                </div>
                <div class="form-group">
                    <label class="form-label">Dirección</label>
                    <input type="text" class="form-control" id="sc_prov_dir" readonly style="background:#f8fafc">
                </div>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
                <strong style="font-size:0.9rem">Productos en o bajo el stock mínimo</strong>
                <button class="btn btn-secondary" style="padding:4px 12px;font-size:0.82rem" onclick="addLineaManualSC()">
                    <i class="fa fa-plus"></i> Agregar ítem
                </button>
            </div>
            <div style="overflow:visible">
                <table class="table" style="font-size:0.84rem">
                    <thead><tr>
                        <th style="width:6%"></th>
                        <th style="width:40%">Producto</th>
                        <th style="width:12%">U.M.</th>
                        <th style="width:14%">Stock actual</th>
                        <th style="width:12%">Mínimo</th>
                        <th style="width:16%">A solicitar</th>
                    </tr></thead>
                    <tbody id="scLineas"></tbody>
                </table>
            </div>
            <div id="scVacio" class="text-secondary" style="display:none;font-size:.84rem;padding:6px 2px">
                No hay productos en o bajo el stock mínimo. Usa “Agregar ítem” para incluir otros.
            </div>

            <div style="margin-top:18px">
                <label class="form-label">Observaciones</label>
                <div class="rte-toolbar">
                    <select onmousedown="event.stopPropagation()" onchange="rteCmd('fontName', this.value); this.selectedIndex=0" title="Fuente">
                        <option value="">Fuente</option>
                        <option value="Arial">Arial</option>
                        <option value="Georgia">Georgia</option>
                        <option value="Times New Roman">Times</option>
                        <option value="Courier New">Courier</option>
                        <option value="Verdana">Verdana</option>
                    </select>
                    <select onchange="rteCmd('fontSize', this.value); this.selectedIndex=0" title="Tamaño">
                        <option value="">Tamaño</option>
                        <option value="2">Pequeño</option>
                        <option value="3">Normal</option>
                        <option value="5">Grande</option>
                        <option value="6">Muy grande</option>
                    </select>
                    <span class="rte-sep"></span>
                    <button type="button" onmousedown="event.preventDefault()" onclick="rteCmd('bold')" title="Negrita"><b>B</b></button>
                    <button type="button" onmousedown="event.preventDefault()" onclick="rteCmd('italic')" title="Cursiva"><i>I</i></button>
                    <button type="button" onmousedown="event.preventDefault()" onclick="rteCmd('underline')" title="Subrayado"><u>U</u></button>
                    <span class="rte-sep"></span>
                    <button type="button" onmousedown="event.preventDefault()" onclick="rteCmd('insertUnorderedList')" title="Lista con viñetas"><i class="fa fa-list-ul"></i></button>
                    <button type="button" onmousedown="event.preventDefault()" onclick="rteCmd('insertOrderedList')" title="Lista numerada"><i class="fa fa-list-ol"></i></button>
                    <span class="rte-sep"></span>
                    <button type="button" onmousedown="event.preventDefault()" onclick="rteCmd('justifyLeft')" title="Alinear izquierda"><i class="fa fa-align-left"></i></button>
                    <button type="button" onmousedown="event.preventDefault()" onclick="rteCmd('justifyCenter')" title="Centrar"><i class="fa fa-align-center"></i></button>
                    <button type="button" onmousedown="event.preventDefault()" onclick="rteCmd('removeFormat')" title="Quitar formato"><i class="fa fa-eraser"></i></button>
                </div>
                <div id="sc_observacion" class="rte-editor" contenteditable="true" data-ph="Notas de la solicitud..."></div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('modalSC')">Cancelar</button>
            <button class="btn btn-secondary" onclick="imprimirSolicitud()"><i class="fa fa-file-pdf"></i> Imprimir PDF</button>
            <button class="btn btn-primary" onclick="guardarSC()"><i class="fa fa-save"></i> Guardar Solicitud</button>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: ORDEN DE COMPRA
     ============================================================ -->
<div class="modal-overlay" id="modalOC">
    <div class="modal" style="max-width:820px">
        <div class="modal-header">
            <h3 class="modal-title" id="modalOCTitle" style="display:flex;align-items:center;gap:10px"><span id="ocTitleText">Nueva Orden de Compra</span> <span class="oc-num-chip" id="ocNumChip">—</span></h3>
            <button class="modal-close" onclick="Modal.close('modalOC')">×</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="oc_id">
            <input type="hidden" id="oc_solicitud_id">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
                <div class="form-group">
                    <label class="form-label">Proveedor *</label>
                    <select class="form-control" id="oc_proveedor_id">
                        <option value="">Seleccionar proveedor...</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Fecha de entrega esperada</label>
                    <input type="date" class="form-control" id="oc_fecha_entrega">
                </div>
            </div>
            <div id="ocPagoGrid" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:16px">
                <div class="form-group">
                    <label class="form-label">Tipo de pago *</label>
                    <select class="form-control" id="oc_tipo_pago" onchange="toggleDiasCredito()">
                        <option value="efectivo">Efectivo</option>
                        <option value="transferencia">Transferencia</option>
                        <option value="credito">Crédito</option>
                    </select>
                </div>
                <div class="form-group" id="ocDiasCreditoWrap" style="display:none">
                    <label class="form-label">Días de crédito</label>
                    <input type="number" class="form-control" id="oc_dias_credito" min="0" step="1" value="30">
                </div>
                <div class="form-group">
                    <label class="form-label">Número de factura</label>
                    <input type="text" class="form-control" id="oc_numero_factura" placeholder="F001-00040598">
                </div>
            </div>

            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
                <strong style="font-size:0.9rem">Productos</strong>
                <button class="btn btn-secondary" style="padding:4px 12px;font-size:0.82rem" onclick="addLineaOC()">
                    <i class="fa fa-plus"></i> Agregar ítem
                </button>
            </div>
            <div style="overflow-x:auto">
                <table class="table" style="font-size:0.84rem">
                    <thead><tr>
                        <th style="width:26%">Producto</th>
                        <th style="width:20%">Descripción</th>
                        <th style="width:9%">U.M.</th>
                        <th style="width:11%">Cantidad</th>
                        <th style="width:13%">P. Unitario</th>
                        <th style="width:13%">Sub. Total</th>
                        <th style="width:5%"></th>
                    </tr></thead>
                    <tbody id="ocLineas"></tbody>
                </table>
            </div>
            <div style="text-align:right;margin-top:8px;font-size:0.9rem">
                Subtotal: <strong id="oc-subtotal">S/ 0.00</strong>
            </div>

            <div class="form-group" style="max-width:200px;margin-top:12px">
                <label class="form-label">Costo de envío</label>
                <input type="number" class="form-control" id="oc_costo_envio" min="0" step="0.01" value="0.00" oninput="recalcularOC()">
            </div>

            <div style="text-align:right;margin-top:8px;font-size:1.1rem">
                Total: <strong id="oc-total" style="color:var(--primary)">S/ 0.00</strong>
            </div>

            <div class="form-group" style="margin-top:16px">
                <label class="form-label">Observaciones</label>
                <textarea class="form-control" id="oc_observaciones" rows="2" placeholder="Notas adicionales para el proveedor..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('modalOC')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarOC()"><i class="fa fa-save"></i> Registrar orden de compra</button>
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
// COMBOS
// ============================================================
async function cargarCombos() {
    const d = await apiGet('api.php?action=combos');
    if (!d.ok) return;
    combos = d;

    // Proveedores
    const optsProv = '<option value="">— Seleccionar —</option>' + d.proveedores.map(p => `<option value="${p.id}">${p.razon_social}</option>`).join('');
    ['oc_proveedor_id'].forEach(id => { const el = document.getElementById(id); if (el) el.innerHTML = optsProv; });

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
    const d = await apiGet(`api.php?action=solicitudes_listar&q=${encodeURIComponent(q)}&estado=${estado}`);
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
            <button class="btn btn-secondary" style="padding:3px 8px;font-size:0.78rem" title="Editar solicitud" onclick="editarSC(${sc.id})">
                <i class="fa fa-pencil"></i>
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

async function abrirModalSC(modoEdicion = false) {
    scActual = null;
    document.getElementById('sc_id').value = '';
    document.getElementById('sc_proveedor_id').value = '';
    document.getElementById('sc_prov_ruc').value = '';
    document.getElementById('sc_prov_razon').value = '';
    document.getElementById('sc_prov_tel').value = '';
    document.getElementById('sc_prov_dir').value = '';
    document.getElementById('sc_observacion').innerHTML = '';
    document.getElementById('scLineas').innerHTML = '';
    document.getElementById('scVacio').style.display = 'none';
    document.getElementById('modalSCTitle').querySelector('span').textContent = 'Nueva Solicitud de Compra';
    Modal.open('modalSC');
    if (modoEdicion) return;   // en edición, los datos los carga editarSC()
    cargarSiguienteNumeroSC();
    // Autollenar con productos en/bajo el stock mínimo
    try {
        const d = await apiGet('api.php?action=productos_bajo_minimo');
        const prods = (d && d.ok) ? d.productos : [];
        prods.forEach(p => document.getElementById('scLineas').appendChild(filaAutoSC(p)));
        document.getElementById('scVacio').style.display = prods.length ? 'none' : 'block';
    } catch (e) {}
}

function rteCmd(cmd, val) {
    document.getElementById('sc_observacion').focus();
    try { document.execCommand(cmd, false, val || null); } catch (e) {}
}

// Devuelve el HTML de observaciones, o null si está vacío
function obsSCHtml() {
    const el = document.getElementById('sc_observacion');
    const txt = (el.textContent || '').trim();
    if (!txt && !el.querySelector('img,ul,ol')) return null;
    return el.innerHTML;
}

async function buscarProveedorSC() {
    const ruc = document.getElementById('sc_prov_ruc').value.trim();
    if (!ruc) { Toast.warning('Ingrese RUC o DNI'); return; }
    try {
        const d = await apiGet(`api.php?action=proveedor_por_ruc&ruc=${encodeURIComponent(ruc)}`);
        if (!d.ok) {
            document.getElementById('sc_proveedor_id').value = '';
            document.getElementById('sc_prov_razon').value = '';
            document.getElementById('sc_prov_tel').value = '';
            document.getElementById('sc_prov_dir').value = '';
            Toast.warning(d.error || 'Proveedor no encontrado');
            return;
        }
        const p = d.proveedor;
        document.getElementById('sc_proveedor_id').value = p.id;
        document.getElementById('sc_prov_razon').value = p.razon_social || '';
        document.getElementById('sc_prov_tel').value   = p.telefono || '';
        document.getElementById('sc_prov_dir').value   = p.direccion || '';
    } catch (e) { Toast.error('Error al buscar proveedor'); }
}

async function cargarSiguienteNumeroSC() {
    const chip = document.getElementById('scNumChip');
    chip.textContent = '—';
    try {
        const d = await apiGet('api.php?action=solicitud_siguiente_numero');
        if (d.ok) chip.textContent = d.numero;
    } catch (e) {}
}

// Fila de producto (readonly). cantidad opcional: si no se pasa, usa (mínimo − actual).
function filaProductoSC(p, cantidad) {
    const actual = parseFloat(p.stock_actual) || 0;
    const minimo = parseFloat(p.stock_minimo) || 0;
    const qty = (cantidad != null) ? cantidad : Math.max(minimo - actual, 0);
    const tr = document.createElement('tr');
    tr.dataset.tipo = 'auto';
    tr.innerHTML = `
        <td style="text-align:center"><button class="btn btn-secondary" style="padding:2px 7px;font-size:0.78rem;color:var(--danger)" onclick="this.closest('tr').remove()"><i class="fa fa-trash"></i></button></td>
        <td>${p.nombre}<input type="hidden" class="sc-pid" value="${p.id}"></td>
        <td>${p.unidad || ''}</td>
        <td>${fmtNum(actual)}</td>
        <td>${fmtNum(minimo)}</td>
        <td><input type="number" class="form-control sc-qty" min="0" step="0.001" value="${fmtNum(qty)}" style="font-size:0.82rem"></td>`;
    return tr;
}
function filaAutoSC(p) { return filaProductoSC(p); }

async function editarSC(id) {
    const d = await apiGet(`api.php?action=solicitud_obtener&id=${id}`);
    if (!d.ok) { Toast.error('No se pudo cargar la solicitud'); return; }
    const sc = d.solicitud;
    // Reset base (modo edición: no autollena ni pide número nuevo)
    await abrirModalSC(true);
    document.getElementById('sc_id').value = sc.id;
    document.getElementById('modalSCTitle').querySelector('span').textContent = 'Editar Solicitud de Compra';
    document.getElementById('scNumChip').textContent = sc.numero;
    // Proveedor
    document.getElementById('sc_proveedor_id').value = sc.proveedor_id || '';
    document.getElementById('sc_prov_ruc').value   = sc.proveedor_ruc || '';
    document.getElementById('sc_prov_razon').value = sc.proveedor_razon_social || '';
    document.getElementById('sc_prov_tel').value   = sc.proveedor_telefono || '';
    document.getElementById('sc_prov_dir').value   = sc.proveedor_direccion || '';
    // Observaciones (HTML)
    document.getElementById('sc_observacion').innerHTML = sc.observaciones || '';
    // Ítems
    (sc.detalles || []).forEach(det => {
        const p = (combos.productos || []).find(x => x.id == det.producto_id)
               || { id: det.producto_id, nombre: det.producto_nombre, unidad: det.unidad, stock_actual: 0, stock_minimo: 0 };
        document.getElementById('scLineas').appendChild(filaProductoSC(p, parseFloat(det.cantidad)));
    });
}

// Fila manual: elegir cualquier producto
function addLineaManualSC() {
    const opts = (combos.productos || []).map(p => `<option value="${p.id}">${p.nombre}</option>`).join('');
    const tr = document.createElement('tr');
    tr.dataset.tipo = 'manual';
    tr.innerHTML = `
        <td style="text-align:center"><button class="btn btn-secondary" style="padding:2px 7px;font-size:0.78rem;color:var(--danger)" onclick="this.closest('tr').remove()"><i class="fa fa-trash"></i></button></td>
        <td><select class="form-control sc-prodsel" onchange="selManualSC(this)" style="font-size:0.82rem"><option value="">— Seleccionar —</option>${opts}</select>
            <input type="hidden" class="sc-pid" value=""></td>
        <td class="sc-um">—</td>
        <td class="sc-actual">—</td>
        <td class="sc-min">—</td>
        <td><input type="number" class="form-control sc-qty" min="0" step="0.001" value="1" style="font-size:0.82rem"></td>`;
    document.getElementById('scLineas').appendChild(tr);
    document.getElementById('scVacio').style.display = 'none';
}

function selManualSC(sel) {
    const tr = sel.closest('tr');
    const p  = (combos.productos || []).find(x => x.id == sel.value);
    tr.querySelector('.sc-pid').value = p ? p.id : '';
    tr.querySelector('.sc-um').textContent     = p ? (p.unidad || '') : '—';
    tr.querySelector('.sc-actual').textContent = p ? fmtNum(p.stock_actual) : '—';
    tr.querySelector('.sc-min').textContent    = p ? fmtNum(p.stock_minimo) : '—';
}

function fmtNum(n) {
    const v = parseFloat(n) || 0;
    return (v % 1 === 0) ? String(v) : v.toFixed(3).replace(/0+$/, '').replace(/\.$/, '');
}

// Recolecta las filas marcadas/seleccionadas con cantidad > 0
function recolectarLineasSC() {
    return [...document.querySelectorAll('#scLineas tr')].map(tr => {
        const pid = tr.querySelector('.sc-pid').value;
        const qty = parseFloat(tr.querySelector('.sc-qty').value) || 0;
        if (!pid || qty <= 0) return null;
        return {
            producto_id: pid,
            cantidad:    qty,
            nombre:      tr.children[1].textContent.trim(),
            unidad:      tr.dataset.tipo === 'auto' ? tr.children[2].textContent.trim() : tr.querySelector('.sc-um').textContent.trim(),
        };
    }).filter(Boolean);
}

async function guardarSCInterno() {
    const lineas = recolectarLineasSC();
    if (!lineas.length) { Toast.warning('Marque al menos un producto con cantidad'); return null; }
    const body = {
        action:        'solicitud_guardar',
        id:            document.getElementById('sc_id').value || null,
        tipo:          'automatica_stock',
        proveedor_id:  document.getElementById('sc_proveedor_id').value || null,
        observaciones: obsSCHtml(),
        detalles:      lineas.map(l => ({ producto_id: l.producto_id, cantidad: l.cantidad })),
    };
    const r = await apiPost('api.php', body);
    if (!r.ok) { Toast.error(r.error || 'Error al guardar'); return null; }
    const proveedor = {
        razon:        document.getElementById('sc_prov_razon').value,
        ruc:          document.getElementById('sc_prov_ruc').value,
        telefono:     document.getElementById('sc_prov_tel').value,
        direccion:    document.getElementById('sc_prov_dir').value,
        observacion:  obsSCHtml(),
    };
    return { numero: r.numero, lineas, proveedor };
}

async function guardarSC() {
    const res = await guardarSCInterno();
    if (!res) return;
    Toast.success('Solicitud guardada');
    Modal.close('modalSC');
    cargarSolicitudes();
}

async function imprimirSolicitud() {
    const res = await guardarSCInterno();
    if (!res) return;
    Toast.success('Solicitud guardada');
    let empresa = null;
    try { const e = await apiGet('api.php?action=empresa_datos'); if (e.ok) empresa = e.empresa; } catch (x) {}
    imprimirVistaSC(res.numero, res.lineas, res.proveedor, empresa);
    Modal.close('modalSC');
    cargarSolicitudes();
}

function imprimirVistaSC(numero, lineas, prov, empresa) {
    const esc = s => String(s == null ? '' : s).replace(/[&<>]/g, c => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;' }[c]));
    const hoy = new Date().toLocaleDateString('es-PE');
    const origin = window.location.origin;
    const logoRel = (empresa && empresa.logo_path) ? empresa.logo_path : 'assets/iconos/logo.jpeg';
    const logoUrl = origin + '<?= APP_BASE ?>' + logoRel.replace(/^\/?industria_mg\//, '').replace(/^\//, '');
    const empNombre = (empresa && (empresa.razon_social || empresa.nombre_comercial)) || 'Empresa';
    const empRuc    = empresa && empresa.ruc ? `RUC ${esc(empresa.ruc)}` : '';
    const empDir    = empresa && empresa.direccion_fiscal ? esc(empresa.direccion_fiscal) : '';

    const filas = lineas.map((l, i) => `
        <tr>
            <td style="text-align:center">${i + 1}</td>
            <td>${esc(l.nombre)}</td>
            <td style="text-align:center">${esc(l.unidad || '')}</td>
            <td style="text-align:center">${fmtNum(l.cantidad)}</td>
        </tr>`).join('');

    const provBloque = (prov && (prov.razon || prov.ruc)) ? `
        <div class="prov">
            <div class="prov-tit">Proveedor</div>
            <div class="prov-grid">
                <div><span>RUC/DNI:</span> ${esc(prov.ruc) || '—'}</div>
                <div><span>Razón social:</span> ${esc(prov.razon) || '—'}</div>
                <div><span>Celular:</span> ${esc(prov.telefono) || '—'}</div>
                <div><span>Dirección:</span> ${esc(prov.direccion) || '—'}</div>
            </div>
        </div>` : '';

    const obs = (prov && prov.observacion) ? `<div class="obs"><div class="obs-tit">Observaciones</div>${prov.observacion}</div>` : '';

    const html = `<!doctype html><html lang="es"><head><meta charset="utf-8">
        <title>${esc(numero) || 'Solicitud de Compra'}</title>
        <style>
            body{font-family:Arial,Helvetica,sans-serif;color:#1e293b;margin:32px}
            .head{display:flex;align-items:center;gap:16px;border-bottom:2px solid #1d4ed8;padding-bottom:12px;margin-bottom:16px}
            .head img{height:62px;object-fit:contain}
            .emp-nombre{font-size:17px;font-weight:700;color:#0f172a}
            .emp-sub{font-size:12px;color:#475569}
            .doc{margin-left:auto;text-align:right}
            .doc .t{font-size:16px;font-weight:700;color:#1d4ed8;letter-spacing:.03em}
            .doc .n{font-size:13px;color:#475569;margin-top:3px}
            .prov{border:1px solid #cbd5e1;border-radius:8px;padding:10px 12px;margin-bottom:16px}
            .prov-tit{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#64748b;font-weight:700;margin-bottom:6px}
            .prov-grid{display:grid;grid-template-columns:1fr 1fr;gap:4px 18px;font-size:13px}
            .prov-grid span{color:#64748b}
            table{width:100%;border-collapse:collapse;font-size:13px}
            th,td{border:1px solid #cbd5e1;padding:7px 9px}
            th{background:#f1f5f9;text-align:left}
            .obs{font-size:13px;margin-top:18px;border-top:1px solid #e2e8f0;padding-top:10px}
            .obs-tit{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#64748b;font-weight:700;margin-bottom:6px}
            .obs ul{margin:4px 0 4px 20px;list-style:disc}
            .obs ol{margin:4px 0 4px 20px;list-style:decimal}
            @media print{body{margin:14mm}}
        </style></head><body>
        <div class="head">
            <img src="${logoUrl}" alt="logo" onerror="this.style.display='none'">
            <div>
                <div class="emp-nombre">${esc(empNombre)}</div>
                <div class="emp-sub">${empRuc}${empRuc && empDir ? ' · ' : ''}${empDir}</div>
            </div>
            <div class="doc">
                <div class="t">SOLICITUD DE COMPRA</div>
                <div class="n">N° ${esc(numero) || '—'} &nbsp;·&nbsp; ${hoy}</div>
            </div>
        </div>
        ${provBloque}
        <table>
            <thead><tr><th style="width:8%;text-align:center">#</th><th>Producto</th><th style="width:14%;text-align:center">U.M.</th><th style="width:16%;text-align:center">Cantidad</th></tr></thead>
            <tbody>${filas}</tbody>
        </table>
        ${obs}
        </body></html>`;
    const w = window.open('', '_blank');
    if (!w) { Toast.warning('Habilite las ventanas emergentes para imprimir'); return; }
    w.document.write(html);
    w.document.close();
    w.focus();
    setTimeout(() => w.print(), 350);
}

async function cambiarEstadoSC(id, estado) {
    const r = await apiPost('api.php', { action: 'solicitud_estado', id, estado });
    if (r.ok) { Toast.success('Estado actualizado'); cargarSolicitudes(); }
    else Toast.error(r.error || 'Error');
}

async function verDetalleSC(id) {
    const d = await apiGet(`api.php?action=solicitud_obtener&id=${id}`);
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
    const d = await apiGet(`api.php?action=solicitud_obtener&id=${scId}`);
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
    const d = await apiGet(`api.php?action=oc_listar&q=${encodeURIComponent(q)}&estado=${estado}`);
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
    document.getElementById('oc_fecha_entrega').value = new Date().toISOString().split('T')[0];
    document.getElementById('oc_tipo_pago').value = 'efectivo';
    document.getElementById('oc_dias_credito').value = '30';
    document.getElementById('oc_numero_factura').value = '';
    document.getElementById('oc_costo_envio').value = '0.00';
    document.getElementById('oc_observaciones').value = '';
    document.getElementById('ocLineas').innerHTML = '';
    toggleDiasCredito();
    addLineaOC();
    recalcularOC();
    document.getElementById('ocTitleText').textContent = 'Nueva Orden de Compra';
    cargarSiguienteNumeroOC();
    Modal.open('modalOC');
}

async function cargarSiguienteNumeroOC() {
    const chip = document.getElementById('ocNumChip');
    chip.textContent = '—';
    try {
        const d = await apiGet('api.php?action=oc_siguiente_numero');
        if (d.ok) chip.textContent = d.numero;
    } catch (e) {}
}

function toggleDiasCredito() {
    const esCredito = document.getElementById('oc_tipo_pago').value === 'credito';
    document.getElementById('ocDiasCreditoWrap').style.display = esCredito ? '' : 'none';
}

function addLineaOC(prod_id = '', qty = 1, pu = 0) {
    const p    = prod_id ? combos.productos.find(x => x.id == prod_id) : null;
    const disp = p ? p.nombre : '';
    const um   = p ? p.unidad : '';
    const puV  = pu ? parseFloat(pu) : (p ? parseFloat(p.precio_promedio || 0) : 0);
    const subV = (parseFloat(qty) || 0) * puV;
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <div class="oc-ac">
                <input class="form-control oc-prod" autocomplete="off" placeholder="Buscar producto o escanee..." value="${disp}" ${p ? 'readonly' : ''}
                       oninput="buscarProductoOC(this)" onfocus="buscarProductoOC(this)"
                       onkeydown="teclaProductoOC(event,this)" onblur="ocultarListaOC(this)" style="font-size:0.82rem">
                <button type="button" class="oc-prod-clear" title="Cambiar producto" onclick="limpiarProductoOC(this)" style="${p ? 'display:block' : ''}"><i class="fa fa-times"></i></button>
                <input type="hidden" class="oc-pid" value="${p ? p.id : ''}">
            </div>
        </td>
        <td><input class="form-control oc-desc" placeholder="Descripción adicional" style="font-size:0.82rem"></td>
        <td><input class="form-control oc-um" value="${um}" readonly style="font-size:0.82rem;background:#f8fafc"></td>
        <td><input type="number" class="form-control oc-qty" min="0" step="0.001" value="${qty}" oninput="lineaCalcOC(this.closest('tr'),'qty')" style="font-size:0.82rem"></td>
        <td><input type="number" class="form-control oc-pu" min="0" step="0.01" value="${puV.toFixed(2)}" oninput="lineaCalcOC(this.closest('tr'),'pu')" style="font-size:0.82rem"></td>
        <td><input type="number" class="form-control oc-sub" min="0" step="0.01" value="${subV.toFixed(2)}" oninput="lineaCalcOC(this.closest('tr'),'sub')" style="font-size:0.82rem"></td>
        <td><button class="btn btn-secondary" style="padding:2px 7px;font-size:0.78rem;color:var(--danger)" onclick="this.closest('tr').remove();recalcularOC()"><i class="fa fa-trash"></i></button></td>`;
    document.getElementById('ocLineas').appendChild(tr);
    recalcularOC();
}

// ---- Autocompletado propio de productos (desplegable montado en <body>) ----
let ocAcInput = null;

function ocAcBox() {
    let b = document.getElementById('ocAcGlobal');
    if (!b) {
        b = document.createElement('div');
        b.id = 'ocAcGlobal';
        document.body.appendChild(b);
        window.addEventListener('scroll', ocAcCerrar, true);
        window.addEventListener('resize', ocAcCerrar);
    }
    return b;
}

function ocAcCerrar() {
    const b = document.getElementById('ocAcGlobal');
    if (b) b.style.display = 'none';
}

function ocAcPosicionar(inp, box) {
    const r = inp.getBoundingClientRect();
    const ancho = Math.max(r.width, 340);
    let left = r.left;
    if (left + ancho > window.innerWidth - 8) left = Math.max(8, window.innerWidth - 8 - ancho);
    box.style.left  = left + 'px';
    box.style.top   = (r.bottom + 2) + 'px';
    box.style.width = ancho + 'px';
}

function buscarProductoOC(inp) {
    if (inp.readOnly) { ocAcCerrar(); return; }
    ocAcInput = inp;
    const box = ocAcBox();
    const q   = inp.value.trim().toLowerCase();
    let items = combos.productos || [];
    if (q) {
        items = items.filter(p =>
            p.codigo.toLowerCase().includes(q) ||
            p.nombre.toLowerCase().includes(q) ||
            (p.codigo_barras && p.codigo_barras.toLowerCase().includes(q)));
    }
    items = items.slice(0, 50);
    if (!items.length) {
        box.innerHTML = '<div class="oc-ac-empty">Sin coincidencias en inventario</div>';
    } else {
        box.innerHTML = items.map(p =>
            `<div class="oc-ac-item" data-id="${p.id}" onmousedown="event.preventDefault();seleccionarProductoOC(this)">
                <span>${p.nombre}</span>
                <span class="oc-ac-um">${p.unidad}</span>
            </div>`).join('');
    }
    ocAcPosicionar(inp, box);
    box.style.display = 'block';
}

function seleccionarProductoOC(el) {
    if (!ocAcInput) return;
    const tr = ocAcInput.closest('tr');
    const p  = (combos.productos || []).find(x => x.id == el.dataset.id);
    if (p) aplicarProductoOC(tr, p);
    ocAcCerrar();
}

function aplicarProductoOC(tr, p) {
    const inp = tr.querySelector('.oc-prod');
    inp.value = p.nombre;
    inp.readOnly = true;
    tr.querySelector('.oc-prod-clear').style.display = 'block';
    tr.querySelector('.oc-pid').value = p.id;
    tr.querySelector('.oc-um').value  = p.unidad;
    const puInp = tr.querySelector('.oc-pu');
    if (!parseFloat(puInp.value)) puInp.value = parseFloat(p.precio_promedio || 0).toFixed(2);
    lineaCalcOC(tr, 'qty');
}

function limpiarProductoOC(btn) {
    const tr  = btn.closest('tr');
    const inp = tr.querySelector('.oc-prod');
    inp.readOnly = false;
    inp.value = '';
    tr.querySelector('.oc-pid').value = '';
    tr.querySelector('.oc-um').value  = '';
    tr.querySelector('.oc-pu').value  = '0.00';
    tr.querySelector('.oc-sub').value = '0.00';
    btn.style.display = 'none';
    recalcularOC();
    inp.focus();
    buscarProductoOC(inp);
}

function teclaProductoOC(e, inp) {
    const tr  = inp.closest('tr');
    const box = ocAcBox();
    const items = [...box.querySelectorAll('.oc-ac-item')];
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        e.preventDefault();
        if (!items.length) return;
        let i = items.findIndex(it => it.classList.contains('active'));
        items.forEach(it => it.classList.remove('active'));
        i = e.key === 'ArrowDown' ? Math.min(i + 1, items.length - 1) : Math.max(i - 1, 0);
        if (i < 0) i = 0;
        items[i].classList.add('active');
        items[i].scrollIntoView({ block: 'nearest' });
    } else if (e.key === 'Enter') {
        e.preventDefault();
        const val = inp.value.trim();
        const exacto = (combos.productos || []).find(x =>
            x.codigo === val || (x.codigo_barras && x.codigo_barras === val));
        const activo = box.querySelector('.oc-ac-item.active') || box.querySelector('.oc-ac-item');
        if (exacto)      { aplicarProductoOC(tr, exacto); ocAcCerrar(); }
        else if (activo) { seleccionarProductoOC(activo); }
    } else if (e.key === 'Escape') {
        ocAcCerrar();
    }
}

function ocultarListaOC(inp) {
    setTimeout(ocAcCerrar, 150);
}

function lineaCalcOC(tr, field) {
    const qty = parseFloat(tr.querySelector('.oc-qty').value) || 0;
    const pu  = parseFloat(tr.querySelector('.oc-pu').value) || 0;
    if (field === 'sub') {
        const sub = parseFloat(tr.querySelector('.oc-sub').value) || 0;
        if (qty > 0) tr.querySelector('.oc-pu').value = (sub / qty).toFixed(2);
    } else {
        tr.querySelector('.oc-sub').value = (qty * pu).toFixed(2);
    }
    recalcularOC();
}

function recalcularOC() {
    let sub = 0;
    document.querySelectorAll('#ocLineas tr').forEach(tr => {
        sub += parseFloat(tr.querySelector('.oc-sub')?.value) || 0;
    });
    const envio = parseFloat(document.getElementById('oc_costo_envio').value) || 0;
    document.getElementById('oc-subtotal').textContent = formatMoney(sub);
    document.getElementById('oc-total').textContent    = formatMoney(sub + envio);
}

async function guardarOC() {
    const provId = document.getElementById('oc_proveedor_id').value;
    if (!provId) { Toast.warning('Seleccione un proveedor'); return; }

    const lineas = [...document.querySelectorAll('#ocLineas tr')].map(tr => ({
        producto_id:     tr.querySelector('.oc-pid')?.value,
        descripcion:     tr.querySelector('.oc-desc')?.value,
        cantidad:        tr.querySelector('.oc-qty')?.value,
        precio_unitario: tr.querySelector('.oc-pu')?.value,
    })).filter(l => l.producto_id);
    if (!lineas.length) { Toast.warning('Agregue al menos un producto del inventario'); return; }

    const body = {
        action:                  'oc_guardar',
        id:                      document.getElementById('oc_id').value || null,
        proveedor_id:            provId,
        solicitud_id:            document.getElementById('oc_solicitud_id').value || null,
        fecha_entrega_estimada:  document.getElementById('oc_fecha_entrega').value || null,
        tipo_pago:               document.getElementById('oc_tipo_pago').value,
        dias_credito:            document.getElementById('oc_dias_credito').value || 0,
        numero_factura:          document.getElementById('oc_numero_factura').value || null,
        costo_envio:             document.getElementById('oc_costo_envio').value || 0,
        observaciones:           document.getElementById('oc_observaciones').value,
        detalles:                lineas,
    };
    const r = await apiPost('api.php', body);
    if (r.ok) {
        Toast.success(`OC ${r.numero || ''} guardada`);
        Modal.close('modalOC');
        cargarOrdenes();
        cargarCombos();
    } else Toast.error(r.error || 'Error al guardar');
}

async function cambiarEstadoOC(id, estado) {
    const r = await apiPost('api.php', { action: 'oc_estado', id, estado });
    if (r.ok) { Toast.success('Estado actualizado'); cargarOrdenes(); cargarCombos(); }
    else Toast.error(r.error || 'Error');
}

async function eliminarOC(id) {
    if (!confirm('¿Eliminar esta OC?')) return;
    const r = await apiPost('api.php', { action: 'oc_eliminar', id });
    if (r.ok) { Toast.success('OC eliminada'); cargarOrdenes(); }
    else Toast.error(r.error || 'Error');
}

async function verDetalleOC(id) {
    const d = await apiGet(`api.php?action=oc_obtener&id=${id}`);
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
    const d = await apiGet(`api.php?action=recepciones_listar&q=${encodeURIComponent(q)}`);
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
    const d = await apiGet(`api.php?action=oc_detalles_para_recepcion&oc_id=${ocId}`);
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

    const r = await apiPost('api.php', {
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
        cargarCombos();
    } else Toast.error(r.error || 'Error al registrar');
}

async function verDetalleRecepcion(id) {
    const d = await apiGet(`api.php?action=recepcion_obtener&id=${id}`);
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
    const d = await apiGet(`api.php?action=proveedores_listar&q=${encodeURIComponent(q)}&activo=${activo}`);
    if (!d.ok) return;
    const tbody = document.querySelector('#tablaprov tbody');
    tbody.innerHTML = d.proveedores.map(p => `<tr>
        <td><strong>${p.razon_social}</strong></td>
        <td>${p.ruc || '—'}</td>
        <td>${p.contacto_nombre || '—'}</td>
        <td>${p.telefono || '—'}</td>
        <td><span class="badge ${p.activo?'badge-success':'badge-secondary'}">${p.activo?'Activo':'Inactivo'}</span></td>
    </tr>`).join('') || '<tr><td colspan="5" class="text-secondary text-center" style="padding:20px">Sin proveedores</td></tr>';
}
</script>

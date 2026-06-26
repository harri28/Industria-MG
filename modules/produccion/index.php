<?php
$page_title      = 'Producción';
$page_breadcrumb = '<span>Operaciones</span> <span>›</span> Producción';
require_once __DIR__ . '/../../includes/header.php';

$db = getDB();

// Clientes para el modal
$clientes = $db->query("SELECT id, razon_social FROM clientes WHERE activo ORDER BY razon_social")->fetchAll();

// Usuarios para responsable
$usuarios_list = $db->query("SELECT id, nombre FROM usuarios WHERE activo ORDER BY nombre")->fetchAll();
?>

<div class="tabs">
    <div class="tab active" data-group="main" data-target="tabProduccion">
        <i class="fa fa-industry"></i> Producción
    </div>
    <div class="tab" data-group="main" data-target="tabFormulacion">
        <i class="fa fa-list-check"></i> Formulación
    </div>
</div>

<div class="tab-content active" data-group="main" id="tabProduccion">

<!-- TOOLBAR -->
<div class="toolbar">
    <div class="toolbar-left">
        <div class="search-box">
            <span class="icon"><i class="fa fa-search"></i></span>
            <input type="text" class="form-control" id="buscar" placeholder="Buscar proyecto, cliente…">
        </div>
        <select class="form-control" id="filtroEstado" style="width:160px">
            <option value="">Todos los estados</option>
            <option value="fabricacion">Fabricación</option>
            <option value="pruebas">Pruebas</option>
            <option value="entrega">Entrega</option>
            <option value="completado">Completado</option>
            <option value="cancelado">Cancelado</option>
        </select>
        <select class="form-control" id="filtroPrioridad" style="width:140px">
            <option value="">Prioridad</option>
            <option value="urgente">Urgente</option>
            <option value="alta">Alta</option>
            <option value="normal">Normal</option>
            <option value="baja">Baja</option>
        </select>
    </div>
    <div class="toolbar-right">
        <a class="btn btn-outline btn-sm" href="bandeja.php" title="Bandejas de área">
            <i class="fa fa-clipboard-list"></i> Bandejas
        </a>
        <button class="btn btn-outline btn-sm" id="btnVistaCuadricula" title="Vista cuadrícula">
            <i class="fa fa-grip"></i>
        </button>
        <button class="btn btn-outline btn-sm" id="btnVistaTabla" title="Vista tabla">
            <i class="fa fa-table-list"></i>
        </button>
<button class="btn btn-primary btn-sm" onclick="abrirModalNuevoProyecto()">
            <i class="fa fa-plus"></i> Nuevo Proyecto
        </button>
    </div>
</div>

<!-- CONTENEDOR DE PROYECTOS -->
<div id="contenedorProyectos">
    <div class="loading"><div class="spinner"></div> Cargando proyectos…</div>
</div>

</div><!-- /tabProduccion -->

<!-- TAB: FORMULACIÓN -->
<div class="tab-content" data-group="main" id="tabFormulacion">

    <!-- Vista: lista de fórmulas -->
    <div id="frmListaView">
        <div class="toolbar" style="margin-top:0">
            <div class="toolbar-left">
                <div class="search-box">
                    <span class="icon"><i class="fa fa-search"></i></span>
                    <input type="text" class="form-control" id="frmBuscar" placeholder="Buscar fórmula…">
                </div>
            </div>
            <div class="toolbar-right">
                <button class="btn btn-primary btn-sm" onclick="abrirModalNuevaFormula()">
                    <i class="fa fa-plus"></i> Nueva Fórmula
                </button>
            </div>
        </div>
        <div id="formulacionContainer" style="padding-top:4px"></div>
    </div>

    <!-- Vista: detalle BOM de fórmula -->
    <div id="frmDetalleView" style="display:none">
        <div class="toolbar" style="margin-top:0">
            <div class="toolbar-left">
                <button class="btn btn-outline btn-sm" onclick="volverListaFormulas()">
                    <i class="fa fa-arrow-left"></i> Fórmulas
                </button>
                <span id="frmDetalleTitulo" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-left:4px"></span>
            </div>
            <div class="toolbar-right">
                <button class="btn btn-outline btn-sm" id="frmDetalleEditBtn" title="Editar nombre/descripción">
                    <i class="fa fa-pencil"></i> Editar
                </button>
                <button class="btn btn-outline btn-sm" style="color:var(--danger)" id="frmDetalleElimBtn" title="Eliminar fórmula">
                    <i class="fa fa-trash"></i> Eliminar
                </button>
            </div>
        </div>

        <!-- Stats bar -->
        <div id="frmStatsBar" style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;padding:14px 0 12px;"></div>

        <!-- Dos columnas: sidebar secciones + contenido -->
        <div id="frmMainGrid" style="display:grid;grid-template-columns:204px minmax(0,1fr);gap:16px;align-items:start;padding-bottom:28px;">

            <!-- Sidebar (sticky wrapper separado del scroll) -->
            <div style="position:sticky;top:12px;">
                <div style="max-height:calc(100vh - 160px);overflow-y:auto;overflow-x:hidden;">

                    <!-- Botón toggle -->
                    <div style="display:flex;justify-content:flex-end;margin-bottom:6px;">
                        <button id="frmSidebarToggle" class="btn btn-outline btn-xs" onclick="toggleFrmSidebar()" title="Contraer">
                            <i class="fa fa-chevron-left"></i>
                        </button>
                    </div>

                    <!-- Contenido colapsable -->
                    <div id="frmSidebarContent">
                        <div style="font-size:.67rem;letter-spacing:.08em;color:var(--text-muted);margin-bottom:8px;padding:0 8px;">SECCIONES</div>
                        <div id="frmSecNav" style="display:flex;flex-direction:column;gap:2px;"></div>

                        <button class="btn btn-outline btn-sm" style="width:100%;margin-top:10px;"
                                onclick="abrirModalItemFormula(_formulaActual.id,null,'','','sec')">
                            <i class="fa fa-puzzle-piece"></i> Agregar Parte
                        </button>
                    </div>
                </div>
            </div>

            <!-- Contenido sección activa -->
            <div id="frmDetalleContainer" style="min-height:300px;"></div>
        </div>
    </div>

</div><!-- /tabFormulacion -->

<!-- ======================================================
     MODAL: NUEVO / EDITAR PROYECTO
     ====================================================== -->
<div class="modal-overlay" id="modalProyecto">
    <div class="modal modal-lg">
        <div class="modal-header">
            <span class="modal-title" id="modalProyectoTitulo">Nuevo Proyecto</span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form id="formProyecto">
                <input type="hidden" id="proyectoId" name="id">
                <div class="form-row cols-3">
                    <div class="form-group">
                        <label class="form-label">Código *</label>
                        <input type="text" class="form-control" name="codigo" id="fCodigo" placeholder="PRY-2026-0001" required>
                    </div>
                    <div class="form-group" style="grid-column: span 2">
                        <label class="form-label">Nombre del Proyecto *</label>
                        <input type="text" class="form-control" name="nombre" required>
                    </div>
                </div>
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Cliente</label>
                        <select class="form-control" name="cliente_id">
                            <option value="">— Sin cliente —</option>
                            <?php foreach ($clientes as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['razon_social']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Responsable</label>
                        <select class="form-control" name="responsable_id">
                            <option value="">— Sin asignar —</option>
                            <?php foreach ($usuarios_list as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row cols-4">
                    <div class="form-group">
                        <label class="form-label">Estado</label>
                        <select class="form-control" name="estado">
                            <option value="fabricacion">Fabricación</option>
                            <option value="pruebas">Pruebas</option>
                            <option value="entrega">Entrega</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Prioridad</label>
                        <select class="form-control" name="prioridad">
                            <option value="baja">Baja</option>
                            <option value="normal" selected>Normal</option>
                            <option value="alta">Alta</option>
                            <option value="urgente">Urgente</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Costo Estimado (S/)</label>
                        <input type="number" class="form-control" name="costo_estimado" step="0.01" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">% Avance</label>
                        <input type="number" class="form-control" name="porcentaje_avance" min="0" max="100" value="0">
                    </div>
                </div>
                <div class="form-row cols-3">
                    <div class="form-group">
                        <label class="form-label">Fecha Inicio</label>
                        <input type="date" class="form-control" name="fecha_inicio">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Entrega Estimada</label>
                        <input type="date" class="form-control" name="fecha_entrega_estimada">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Entrega Real</label>
                        <input type="date" class="form-control" name="fecha_entrega_real">
                    </div>
                </div>
                <div id="grupoFormula" style="display:none">
                    <div class="form-group">
                        <label class="form-label">Lista de Materiales (Fórmula)</label>
                        <select class="form-control" id="proyectoPlantillaId" name="plantilla_id" onchange="toggleCantMaquinas(this.value)">
                            <option value="">— Sin fórmula —</option>
                        </select>
                    </div>
                    <div id="grupoMaquinas" style="display:none">
                        <div class="form-group">
                            <label class="form-label">Cantidad de máquinas</label>
                            <input type="number" class="form-control" name="cantidad_maquinas" id="proyectoCantMaq" min="1" value="1">
                            <div style="font-size:.7rem;color:var(--text-muted);margin-top:3px">Las cantidades de la fórmula se multiplicarán por este valor.</div>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Descripción / Observaciones</label>
                    <textarea class="form-control" name="observaciones" rows="2"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalProyecto')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarProyecto()">
                <i class="fa fa-save"></i> Guardar
            </button>
        </div>
    </div>
</div>

<!-- ======================================================
     MODAL: NUEVA / EDITAR FÓRMULA
     ====================================================== -->
<div class="modal-overlay" id="modalNuevaFormula">
    <div class="modal modal-md">
        <div class="modal-header">
            <span class="modal-title" id="modalFormulaTitulo">Nueva Fórmula</span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="formulaId">
            <div class="form-group">
                <label class="form-label">Nombre de la Fórmula *</label>
                <input type="text" class="form-control" id="formulaNombre" placeholder="Ej. GTK24, Fresadora CNC, Torno 3X…">
            </div>
            <div class="form-group">
                <label class="form-label">Descripción</label>
                <textarea class="form-control" id="formulaDesc" rows="2" placeholder="Descripción del tipo de máquina o producto…"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalNuevaFormula')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarFormula()">
                <i class="fa fa-save"></i> Guardar
            </button>
        </div>
    </div>
</div>

<!-- ======================================================
     MODAL: AGREGAR / EDITAR MATERIAL EN FÓRMULA
     ====================================================== -->
<div class="modal-overlay" id="modalItemFormula">
    <div class="modal modal-md">
        <div class="modal-header">
            <span class="modal-title" id="modalItemFormulaTitulo">Agregar Material</span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="itemFormulaId">
            <input type="hidden" id="itemFormulaPid">
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Pieza</label>
                    <input type="text" class="form-control" id="itemFormulaSec" placeholder="Ej. Bastidor, Caja…">
                </div>
                <div class="form-group">
                    <label class="form-label">Parte</label>
                    <input type="text" class="form-control" id="itemFormulaParte" placeholder="Ej. Travesaño, Cruz 1…">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Material *</label>
                <div style="position:relative">
                    <input type="text" class="form-control" id="itemFormulaMatBuscar"
                           placeholder="Buscar en inventario o escribir nombre libre…" autocomplete="off">
                    <input type="hidden" id="itemFormulaProductoId">
                    <div id="itemFormulaDrop" style="display:none;position:absolute;z-index:1000;left:0;right:0;top:100%;
                         background:#fff;border:1px solid var(--border);border-top:none;border-radius:0 0 6px 6px;
                         max-height:200px;overflow-y:auto;box-shadow:0 4px 12px rgba(0,0,0,.12)"></div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Dimensión / Medida</label>
                <input type="text" class="form-control" id="itemFormulaDim" placeholder="Ej. 133 cm, 2&quot;×3&quot; pared 2.5mm… (opcional)">
            </div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Unidad</label>
                    <input type="text" class="form-control" id="itemFormulaUnidad" value="unidad" placeholder="kg, m, unidad…">
                </div>
                <div class="form-group">
                    <label class="form-label">Cantidad</label>
                    <input type="number" class="form-control" id="itemFormulaCant" min="1" step="1" value="1">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Operación (asignación de área)</label>
                <input type="text" class="form-control" id="itemFormulaOp" placeholder="Ej. SOLDAR, CORTAR, TALADRO… (opcional)">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalItemFormula')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarItemFormula()">
                <i class="fa fa-save"></i> Guardar
            </button>
        </div>
    </div>
</div>

<!-- Modal: Configurar Material (propiedades de pieza en plantilla) -->
<div class="modal-overlay" id="modalCfgMaterialFormula">
    <div class="modal modal-md">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-cog"></i> Configurar Material</span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="cfgFrmPltId">
            <input type="hidden" id="cfgFrmPiezaViejo">
            <div class="form-group">
                <label class="form-label">Nombre de la pieza</label>
                <input type="text" class="form-control" id="cfgFrmPiezaNombre" placeholder="Nombre de la pieza…">
            </div>
            <div class="form-group">
                <label class="form-label">Material base</label>
                <input type="text" class="form-control" id="cfgFrmMaterial" placeholder="Ej. Acero AISI 1045, Aluminio 6061…">
            </div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Largo (mm)</label>
                    <input type="number" class="form-control" id="cfgFrmLargo" min="0" step="0.1" placeholder="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Ancho (mm)</label>
                    <input type="number" class="form-control" id="cfgFrmAncho" min="0" step="0.1" placeholder="0">
                </div>
            </div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Alto / Espesor (mm)</label>
                    <input type="number" class="form-control" id="cfgFrmAlto" min="0" step="0.1" placeholder="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Peso estimado (kg)</label>
                    <input type="number" class="form-control" id="cfgFrmPeso" min="0" step="0.01" placeholder="0">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Norma / Estándar</label>
                <input type="text" class="form-control" id="cfgFrmNorma" placeholder="Ej. ASTM A108, DIN 17200…">
            </div>
            <div class="form-group">
                <label class="form-label">Observaciones</label>
                <textarea class="form-control" id="cfgFrmObs" rows="2" placeholder="Notas técnicas opcionales…"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalCfgMaterialFormula')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarCfgMaterialFormula()">
                <i class="fa fa-save"></i> Guardar
            </button>
        </div>
    </div>
</div>

<style>
.pieza-card { margin-bottom:14px; border:2px solid #cbd5e1; }
.pieza-card .card-header { background:#f8fafc; border-bottom:1px solid var(--border); }
.tabla-pieza { border-collapse:collapse; width:100%; }
.tabla-pieza thead th { border-bottom:1px solid var(--border); background:#f8fafc; font-size:.75rem; }
.tabla-pieza td { font-size:.83rem; padding:6px 8px; border-bottom:1px solid #f1f5f9; }
.bom-specs { display:flex; flex-wrap:wrap; gap:0; border:1px solid var(--border); border-radius:0 0 6px 6px; background:#fff; font-size:.875rem; }
.bom-specs-item { flex:1; min-width:115px; padding:8px 14px; border-right:1px solid var(--border); }
.bom-specs-item--medida   { width:90px;  flex:none; text-align:center; }
.bom-specs-item--cantidad { width:110px; flex:none; text-align:center; }
.bom-specs-item--subtotal { width:120px; flex:none; border-right:none; text-align:center; }
.bom-specs-label { color:var(--text-muted); margin-bottom:3px; text-transform:uppercase; font-size:.77rem; letter-spacing:.04em; }
.bom-specs-value { font-weight:700; color:var(--text); }
.parte-section { border-top:1px solid var(--border); background:#faf5ff; }
.parte-section .bom-specs { border-radius:0; }
.parte-header { display:flex; align-items:center; justify-content:space-between; padding:7px 14px; background:#f8fafc; font-size:.86rem; font-weight:600; color:#6d28d9; }
/* Formulación — diseño moderno con nav lateral */
.frm-nav-btn { display:flex; align-items:center; gap:8px; width:100%; text-align:left; padding:7px 10px; border-radius:6px; border:none; background:transparent; cursor:pointer; font-size:.82rem; color:#64748b; transition:background .15s,color .15s; }
.frm-nav-btn:hover { background:#f1f5f9; }
.frm-nav-btn.active { background:#eff6ff; color:var(--primary); font-weight:600; border-left:2px solid var(--primary); padding-left:8px; }
.frm-stat-card { background:#f8fafc; border:1px solid var(--border); border-radius:8px; padding:12px 14px; }
.frm-stat-label { font-size:.7rem; letter-spacing:.04em; color:var(--text-muted); margin-bottom:4px; display:flex; align-items:center; gap:5px; }
.frm-stat-val { font-size:1.35rem; font-weight:700; }
.frm-stat-val.warn { color:#d97706; }
.frm-piece-row { display:grid; grid-template-columns:32px 80px 108px 1fr 160px; border-bottom:1px solid #f1f5f9; }
.frm-piece-row:last-child { border-bottom:none; }
.frm-piece-row:hover { background:#f8fafc; }
.frm-pc { padding:9px 10px; font-size:.82rem; display:flex; align-items:center; }
.frm-col-head { padding:6px 10px; font-size:.68rem; letter-spacing:.06em; color:var(--text-muted); font-weight:600; display:flex; align-items:center; background:#f8fafc; }
.frm-mat-header { font-size:.7rem; letter-spacing:.05em; color:#475569; padding:8px 10px 5px; border-top:1px solid var(--border); display:flex; align-items:center; gap:7px; }
.frm-status-dot { width:7px; height:7px; border-radius:50%; flex-shrink:0; }
.frm-dot-ok { background:#22c55e; }
.frm-dot-miss { background:#f59e0b; }
.frm-obs-tag { font-size:.68rem; color:var(--text-muted); background:#f1f5f9; border-radius:4px; padding:2px 6px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:135px; }
.frm-measure-miss { color:#d97706; font-style:italic; font-size:.76rem; display:flex; align-items:center; gap:3px; }
.frm-sec-table { background:#fff; border:1px solid var(--border); border-radius:8px; overflow:hidden; }
.frm-sec-actions { display:flex; gap:8px; margin-top:10px; padding-bottom:4px; }
/* Badges de operación */
.frm-op { display:inline-flex; align-items:center; font-size:.67rem; font-weight:700; padding:2px 8px; border-radius:10px; letter-spacing:.04em; white-space:nowrap; }
.frm-op-soldadura,.frm-op-soldar { background:#fef3c7; color:#92400e; }
.frm-op-torno { background:#faeeda; color:#854f0b; }
.frm-op-taladro { background:#d1fae5; color:#065f46; }
.frm-op-corte,.frm-op-corte-laser,.frm-op-corte-láser { background:#fee2e2; color:#991b1b; }
.frm-op-tronzadora { background:#dbeafe; color:#1e40af; }
.frm-op-pintura,.frm-op-pintar { background:#ede9fe; color:#5b21b6; }
.frm-op-rolado,.frm-op-rolar { background:#fce7f3; color:#9d174d; }
.frm-op-escuadra { background:#f0fdf4; color:#166534; }
.frm-op-default { background:#e2e8f0; color:#475569; }
</style>

<script>
const BASE = '../../modules/produccion/api.php';
let vistaActual = 'cuadricula';
let proyectosData = [];

// ── Cargar proyectos ──────────────────────────────────────
async function cargarProyectos() {
    const buscar    = document.getElementById('buscar').value;
    const estado    = document.getElementById('filtroEstado').value;
    const prioridad = document.getElementById('filtroPrioridad').value;

    const params = new URLSearchParams({ action: 'listar' });
    if (buscar)    params.set('buscar',    buscar);
    if (estado)    params.set('estado',    estado);
    if (prioridad) params.set('prioridad', prioridad);

    document.getElementById('contenedorProyectos').innerHTML =
        '<div class="loading"><div class="spinner"></div> Cargando…</div>';

    try {
        const data = await apiGet(`${BASE}?${params}`);
        proyectosData = data.proyectos || [];
        renderProyectos();
    } catch(e) {
        document.getElementById('contenedorProyectos').innerHTML =
            '<div class="alert alert-danger">Error al cargar proyectos.</div>';
    }
}

// ── Render ────────────────────────────────────────────────
function renderProyectos() {
    const cont = document.getElementById('contenedorProyectos');
    if (!proyectosData.length) {
        cont.innerHTML = '<div class="empty-state"><div class="icon"><i class="fa fa-industry"></i></div><p>No hay proyectos con los filtros actuales.</p></div>';
        return;
    }
    if (vistaActual === 'cuadricula') {
        cont.innerHTML = `<div class="project-grid">${proyectosData.map(cardHtml).join('')}</div>`;
    } else {
        cont.innerHTML = tablaHtml(proyectosData);
    }
}

function cardHtml(p) {
    const retrasado = p.fecha_entrega_estimada && new Date(p.fecha_entrega_estimada) < new Date() && !['completado','cancelado'].includes(p.estado);
    const diasRestantes = p.fecha_entrega_estimada ? diffDays(p.fecha_entrega_estimada) : null;
    const pct = parseInt(p.porcentaje_avance) || 0;
    const pClass = pct >= 75 ? 'green' : pct >= 40 ? '' : 'red';

    return `
    <div class="project-card ${retrasado ? 'retrasado' : ''}" onclick="window.location='proyecto.php?id=${p.id}'">
        <div class="project-card-header">
            <img src="/industria_mg/assets/iconos/nuevo_proyecto.png" alt="proyecto"
                 style="width:42px;height:42px;object-fit:contain;border-radius:8px;flex-shrink:0;background:#f8fafc;padding:4px">
            <div style="flex:1;min-width:0">
                <div class="project-code">${esc(p.codigo)}</div>
                <div class="project-name">${esc(p.nombre)}</div>
                <div class="project-client"><i class="fa fa-building" style="font-size:.7rem"></i> ${esc(p.cliente_nombre || '—')}</div>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;flex-shrink:0">
                <span class="badge badge-${p.estado}">${estadoLabel(p.estado)}</span>
                <span class="badge badge-${p.prioridad}">${p.prioridad}</span>
            </div>
        </div>
        <div class="project-card-body">
            <div class="project-meta">
                <div class="project-meta-item">
                    <span class="project-meta-label">Inicio</span>
                    <span class="project-meta-value">${formatDate(p.fecha_inicio)}</span>
                </div>
                <div class="project-meta-item">
                    <span class="project-meta-label">Entrega est.</span>
                    <span class="project-meta-value ${retrasado ? 'text-danger font-bold' : ''}">${formatDate(p.fecha_entrega_estimada)}</span>
                </div>
                <div class="project-meta-item">
                    <span class="project-meta-label">Costo est.</span>
                    <span class="project-meta-value">${formatMoney(p.costo_estimado || 0)}</span>
                </div>
                <div class="project-meta-item">
                    <span class="project-meta-label">Costo real</span>
                    <span class="project-meta-value ${(p.costo_real > p.costo_estimado && p.costo_estimado > 0) ? 'text-danger' : ''}">${formatMoney(p.costo_real || 0)}</span>
                </div>
            </div>
            <div class="progress-bar-wrap">
                <div class="progress-bar-fill ${pClass}" style="width:${pct}%"></div>
            </div>
            <div class="progress-label">${pct}% completado</div>
        </div>
        <div class="project-card-footer">
            ${retrasado ? `<span class="alert-chip"><i class="fa fa-triangle-exclamation"></i> ${Math.abs(diasRestantes)} días de retraso</span>` :
              (diasRestantes !== null && diasRestantes <= 7 && diasRestantes >= 0 ? `<span class="alert-chip text-warning"><i class="fa fa-clock"></i> Vence en ${diasRestantes} días</span>` :
              `<span class="text-muted text-xs"><i class="fa fa-user"></i> ${esc(p.responsable_nombre || 'Sin asignar')}</span>`)}
            <div style="margin-left:auto;display:flex;gap:6px">
                <button class="btn btn-outline btn-xs" onclick="event.stopPropagation();editarProyecto(${p.id})" title="Editar">
                    <i class="fa fa-pencil"></i>
                </button>
                <button class="btn btn-outline btn-xs" onclick="event.stopPropagation();cambiarEstado(${p.id}, '${p.estado}')" title="Cambiar estado">
                    <i class="fa fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>`;
}

function tablaHtml(proyectos) {
    const rows = proyectos.map(p => {
        const retrasado = p.fecha_entrega_estimada && new Date(p.fecha_entrega_estimada) < new Date() && !['completado','cancelado'].includes(p.estado);
        return `<tr>
            <td><a href="proyecto.php?id=${p.id}" class="text-primary font-semibold">${esc(p.codigo)}</a></td>
            <td>${esc(p.nombre)}</td>
            <td>${esc(p.cliente_nombre || '—')}</td>
            <td><span class="badge badge-${p.estado}">${estadoLabel(p.estado)}</span></td>
            <td><span class="badge badge-${p.prioridad}">${p.prioridad}</span></td>
            <td>${formatDate(p.fecha_entrega_estimada)} ${retrasado ? '<span class="text-danger text-xs">⚠ Retrasado</span>' : ''}</td>
            <td>${formatMoney(p.costo_estimado || 0)}</td>
            <td class="${(p.costo_real > p.costo_estimado && p.costo_estimado > 0) ? 'text-danger font-bold' : ''}">${formatMoney(p.costo_real || 0)}</td>
            <td>
                <div class="progress-bar-wrap" style="width:80px">
                    <div class="progress-bar-fill" style="width:${p.porcentaje_avance}%"></div>
                </div>
            </td>
            <td>
                <button class="btn btn-outline btn-xs" onclick="editarProyecto(${p.id})"><i class="fa fa-pencil"></i></button>
                <a href="proyecto.php?id=${p.id}" class="btn btn-outline btn-xs"><i class="fa fa-eye"></i></a>
            </td>
        </tr>`;
    }).join('');

    return `<div class="card">
        <div class="table-responsive">
        <table>
            <thead><tr>
                <th>Código</th><th>Nombre</th><th>Cliente</th><th>Estado</th>
                <th>Prioridad</th><th>Entrega Est.</th><th>Costo Est.</th>
                <th>Costo Real</th><th>Avance</th><th>Acciones</th>
            </tr></thead>
            <tbody>${rows}</tbody>
        </table>
        </div>
    </div>`;
}

function estadoLabel(e) {
    const map = { fabricacion:'Fabricación', pruebas:'Pruebas', entrega:'Entrega', completado:'Completado', cancelado:'Cancelado' };
    return map[e] || e;
}
function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

// ── Guardar ───────────────────────────────────────────────
async function guardarProyecto() {
    const form = document.getElementById('formProyecto');
    const fd   = new FormData(form);
    const data = Object.fromEntries(fd.entries());
    data.action = data.id ? 'actualizar' : 'crear';

    try {
        const res = await apiPost(BASE, data);
        if (res.ok) {
            Toast.success(data.id ? 'Proyecto actualizado.' : 'Proyecto creado.');
            Modal.close('modalProyecto');
            form.reset();
            document.getElementById('proyectoId').value = '';
            document.getElementById('modalProyectoTitulo').textContent = 'Nuevo Proyecto';
            cargarProyectos();
        } else {
            Toast.error(res.error || 'Error al guardar.');
        }
    } catch(e) { Toast.error('Error de conexión.'); }
}

// ── Editar ────────────────────────────────────────────────
async function editarProyecto(id) {
    try {
        const data = await apiGet(`${BASE}?action=obtener&id=${id}`);
        const p = data.proyecto;
        const form = document.getElementById('formProyecto');
        document.getElementById('proyectoId').value = p.id;
        document.getElementById('modalProyectoTitulo').textContent = 'Editar Proyecto';
        for (const [k, v] of Object.entries(p)) {
            const el = form.elements[k];
            if (el) el.value = v ?? '';
        }
        document.getElementById('grupoFormula').style.display = 'none';
        Modal.open('modalProyecto');
    } catch(e) { Toast.error('Error al cargar proyecto.'); }
}

// ── Cambiar estado rápido ─────────────────────────────────
async function cambiarEstado(id, estadoActual) {
    const estados = ['fabricacion','pruebas','entrega','completado'];
    const idx = estados.indexOf(estadoActual);
    const siguiente = estados[idx + 1];
    if (!siguiente) { Toast.warning('El proyecto ya está en su estado final.'); return; }
    if (!confirm(`¿Cambiar estado a "${estadoLabel(siguiente)}"?`)) return;
    try {
        const res = await apiPost(BASE, { action: 'cambiar_estado', id, estado: siguiente });
        if (res.ok) { Toast.success('Estado actualizado.'); cargarProyectos(); }
        else Toast.error(res.error || 'Error.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

// ── Autocompletar código ──────────────────────────────────
document.getElementById('modalProyecto').addEventListener('click', async e => {
    if (e.target.id !== 'fCodigo') return;
    if (document.getElementById('proyectoId').value) return;
    if (document.getElementById('fCodigo').value) return;
    try {
        const r = await apiGet(`${BASE}?action=siguiente_codigo`);
        document.getElementById('fCodigo').value = r.codigo;
    } catch(_){}
});

// ── Vista ─────────────────────────────────────────────────
document.getElementById('btnVistaCuadricula').addEventListener('click', () => { vistaActual = 'cuadricula'; renderProyectos(); });
document.getElementById('btnVistaTabla').addEventListener('click',      () => { vistaActual = 'tabla';     renderProyectos(); });

// ── Filtros ───────────────────────────────────────────────
let debounceTimer;
document.getElementById('buscar').addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(cargarProyectos, 350);
});
document.getElementById('filtroEstado').addEventListener('change',    cargarProyectos);
document.getElementById('filtroPrioridad').addEventListener('change', cargarProyectos);

// ── Formulación / Plantillas ──────────────────────────────
let _plantillas      = [];
let _plantillasCarga = false;

document.querySelector('[data-target="tabFormulacion"]').addEventListener('click', () => {
    if (!_plantillasCarga) cargarPlantillas();
});
document.getElementById('frmBuscar').addEventListener('input', () => {
    if (_plantillasCarga) renderPlantillas();
});

// ── Abrir modal nuevo proyecto ────────────────────────────
function abrirModalNuevoProyecto() {
    document.getElementById('formProyecto').reset();
    document.getElementById('proyectoId').value = '';
    document.getElementById('modalProyectoTitulo').textContent = 'Nuevo Proyecto';
    document.getElementById('grupoFormula').style.display = '';
    document.getElementById('grupoMaquinas').style.display = 'none';
    document.getElementById('proyectoPlantillaId').value = '';
    cargarPlantillasDropdown();
    Modal.open('modalProyecto');
}
function toggleCantMaquinas(val) {
    document.getElementById('grupoMaquinas').style.display = val ? 'block' : 'none';
}

// ── Cargar plantillas ─────────────────────────────────────
async function cargarPlantillas() {
    const cont = document.getElementById('formulacionContainer');
    cont.innerHTML = '<div class="loading"><div class="spinner"></div> Cargando fórmulas…</div>';
    try {
        const data       = await apiGet(`${BASE}?action=plantillas_listar`);
        _plantillas      = data.plantillas || [];
        _plantillasCarga = true;
        renderPlantillas();
        cargarPlantillasDropdown();
    } catch(e) {
        cont.innerHTML = '<div class="alert alert-danger">Error al cargar fórmulas.</div>';
    }
}

async function cargarPlantillasDropdown() {
    if (!_plantillasCarga) {
        try {
            const data  = await apiGet(`${BASE}?action=plantillas_listar`);
            _plantillas = data.plantillas || [];
            _plantillasCarga = true;
        } catch(e) { return; }
    }
    const sel = document.getElementById('proyectoPlantillaId');
    const prev = sel.value;
    sel.innerHTML = '<option value="">— Sin fórmula —</option>'
        + _plantillas.map(p => `<option value="${p.id}">${esc(p.nombre)}</option>`).join('');
    if (prev) sel.value = prev;
}

function renderPlantillas() {
    const cont = document.getElementById('formulacionContainer');
    const q    = document.getElementById('frmBuscar').value.toLowerCase().trim();
    const lista = q
        ? _plantillas.filter(p =>
            p.nombre.toLowerCase().includes(q) ||
            (p.descripcion||'').toLowerCase().includes(q))
        : _plantillas;
    if (!lista.length) {
        cont.innerHTML = `<div class="empty-state">
            <div class="icon"><i class="fa fa-list-check"></i></div>
            <p>${q ? 'No hay fórmulas con esa búsqueda.' : 'No hay fórmulas. Crea la primera con <strong>Nueva Fórmula</strong>.'}</p>
        </div>`;
        return;
    }
    cont.innerHTML = lista.map(p => cardPlantilla(p)).join('');
}

let _itemsCache = {};

function cardPlantilla(p) {
    const c = { accent:'#8b5cf6', bg:'#f5f3ff', chip:'#ede9fe', icon:'#6d28d9' };
    const meta = [p.codigo, p.descripcion].filter(Boolean).join(' · ');
    return `<div id="card-plt-${p.id}" onclick="abrirFormula(${p.id})"
        style="display:flex;align-items:center;justify-content:space-between;
               padding:10px 14px;margin-bottom:8px;cursor:pointer;border-radius:8px;
               border:1px solid ${c.accent}33;border-left:4px solid ${c.accent};
               background:${c.bg};transition:filter .15s;"
        onmouseover="this.style.filter='brightness(.97)'" onmouseout="this.style.filter=''">
        <div style="display:flex;align-items:center;gap:12px;min-width:0;">
            <span style="display:inline-flex;align-items:center;justify-content:center;
                         width:34px;height:34px;border-radius:8px;background:${c.chip};flex-shrink:0;">
                <i class="fa fa-list-check" style="color:${c.icon};font-size:.88rem;"></i>
            </span>
            <span style="min-width:0;">
                <div style="font-size:.88rem;font-weight:700;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    ${esc(p.nombre)}
                </div>
                ${meta ? `<div style="font-size:.71rem;color:#64748b;margin-top:1px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${esc(meta)}</div>` : ''}
            </span>
        </div>
        <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;" onclick="event.stopPropagation()">
            <button class="btn btn-outline btn-sm" style="color:var(--danger)"
                    onclick="confirmarEliminarFormula(${p.id},'${esc(p.nombre)}')"
                    title="Eliminar fórmula"><i class="fa fa-trash"></i>
            </button>
            <i class="fa fa-chevron-right" style="font-size:.8rem;color:${c.accent};pointer-events:none;"></i>
        </div>
    </div>`;
}

let _formulaActual = null;

function abrirFormula(id) {
    const p = _plantillas.find(x => x.id == id);
    if (!p) return;
    _formulaActual = p;
    document.getElementById('frmListaView').style.display = 'none';
    document.getElementById('frmDetalleView').style.display = '';
    const tit = document.getElementById('frmDetalleTitulo');
    tit.innerHTML = `<i class="fa fa-list-check" style="color:var(--primary);opacity:.75"></i>
        <strong>${esc(p.nombre)}</strong>
        ${p.codigo ? `<span style="font-size:.75rem;color:var(--text-muted)">${esc(p.codigo)}</span>` : ''}
        ${p.descripcion ? `<span style="font-size:.78rem;color:var(--text-secondary)">${esc(p.descripcion)}</span>` : ''}`;
    document.getElementById('frmDetalleEditBtn').onclick = () => abrirModalNuevaFormula(p.id, p.nombre, p.descripcion||'');
    document.getElementById('frmDetalleElimBtn').onclick = () => confirmarEliminarFormula(p.id, p.nombre);
    // Resetear sidebar al estado expandido
    _frmSidebarOpen = true;
    document.getElementById('frmMainGrid').style.gridTemplateColumns = '204px minmax(0,1fr)';
    document.getElementById('frmSidebarContent').style.display = '';
    const tog = document.getElementById('frmSidebarToggle');
    tog.innerHTML = '<i class="fa fa-chevron-left"></i>';
    tog.title = 'Contraer';
    cargarDetalleFormula(id);
}

function volverListaFormulas() {
    _formulaActual = null;
    document.getElementById('frmDetalleView').style.display = 'none';
    document.getElementById('frmListaView').style.display = '';
}

async function cargarDetalleFormula(id) {
    const cont = document.getElementById('frmDetalleContainer');
    cont.innerHTML = '<div class="loading"><div class="spinner"></div> Cargando…</div>';
    try {
        const data  = await apiGet(`${BASE}?action=plantilla_detalle&id=${id}`);
        _frmItems   = data.items || [];
        _itemsCache[id] = _frmItems;
        // Mantener sección activa si todavía existe, si no ir a la primera
        const secciones = [...new Set(_frmItems.map(it => it.seccion||''))];
        if (!secciones.includes(_frmSeccionActual)) {
            _frmSeccionActual = secciones[0] ?? '';
        }
        renderFrmStats();
        renderFrmNav();
        renderFrmSeccion();
    } catch(e) {
        cont.innerHTML = `<div style="padding:10px 18px;color:var(--danger);font-size:.82rem">Error al cargar materiales.</div>`;
    }
}

// ── Formulación: renderizado moderno ─────────────────────
let _frmItems = [];
let _frmSeccionActual = null;
let _frmSidebarOpen = true;

function toggleFrmSidebar() {
    _frmSidebarOpen = !_frmSidebarOpen;
    document.getElementById('frmMainGrid').style.gridTemplateColumns =
        _frmSidebarOpen ? '204px minmax(0,1fr)' : '36px minmax(0,1fr)';
    document.getElementById('frmSidebarContent').style.display = _frmSidebarOpen ? '' : 'none';
    const btn = document.getElementById('frmSidebarToggle');
    btn.innerHTML = _frmSidebarOpen ? '<i class="fa fa-chevron-left"></i>' : '<i class="fa fa-chevron-right"></i>';
    btn.title     = _frmSidebarOpen ? 'Contraer' : 'Expandir';
}

function opBadge(op) {
    if (!op) return '';
    const key = op.toLowerCase().trim().replace(/\s+/g,'-').replace(/[áa]/g,'a').replace(/[éa]/g,'e');
    const map = { soldadura:'frm-op-soldadura', soldar:'frm-op-soldadura', torno:'frm-op-torno',
        taladro:'frm-op-taladro', corte:'frm-op-corte', 'corte-laser':'frm-op-corte',
        'corte-láser':'frm-op-corte', tronzadora:'frm-op-tronzadora',
        pintura:'frm-op-pintura', pintar:'frm-op-pintura',
        rolado:'frm-op-rolado', rolar:'frm-op-rolado', escuadra:'frm-op-escuadra' };
    const cls = map[key] || 'frm-op-default';
    return `<span class="frm-op ${cls}">${esc(op.toUpperCase())}</span>`;
}

function renderFrmStats() {
    const secciones = new Set(_frmItems.map(it => it.seccion||'').filter(Boolean));
    const sinMedida = _frmItems.filter(it => !it.dimension).length;
    const ops       = new Set(_frmItems.map(it => (it.operacion||'').trim()).filter(Boolean));
    document.getElementById('frmStatsBar').innerHTML = `
        <div class="frm-stat-card">
            <div class="frm-stat-label"><i class="fa fa-layer-group"></i> Secciones</div>
            <div class="frm-stat-val">${secciones.size}</div>
        </div>
        <div class="frm-stat-card">
            <div class="frm-stat-label"><i class="fa fa-box"></i> Materiales</div>
            <div class="frm-stat-val">${_frmItems.length}</div>
        </div>
        <div class="frm-stat-card">
            <div class="frm-stat-label"><i class="fa fa-triangle-exclamation"></i> Sin medida</div>
            <div class="frm-stat-val${sinMedida ? ' warn' : ''}">${sinMedida}</div>
        </div>
        <div class="frm-stat-card">
            <div class="frm-stat-label"><i class="fa fa-gears"></i> Operaciones</div>
            <div class="frm-stat-val">${ops.size}</div>
        </div>`;
}

function renderFrmNav() {
    const secciones = [];
    const seen = new Set();
    _frmItems.forEach(it => {
        const s = it.seccion || '';
        if (!seen.has(s)) { seen.add(s); secciones.push(s); }
    });
    const nav = document.getElementById('frmSecNav');
    if (!secciones.length) {
        nav.innerHTML = '<div style="font-size:.78rem;color:var(--text-muted);padding:6px 8px;">Sin secciones</div>';
        return;
    }
    nav.innerHTML = secciones.map(s => {
        const label = s || 'Sin sección';
        const count = _frmItems.filter(it => (it.seccion||'') === s).length;
        const active = s === _frmSeccionActual;
        return `<button class="frm-nav-btn${active?' active':''}" onclick="setFrmSeccion('${esc(s)}')">
            <i class="fa fa-cube" style="font-size:12px;min-width:12px;opacity:.65"></i>
            <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1">${esc(label)}</span>
            <span style="font-size:.67rem;color:var(--text-muted);flex-shrink:0">${count}</span>
        </button>`;
    }).join('');
}

function setFrmSeccion(secName) {
    _frmSeccionActual = secName;
    renderFrmNav();
    renderFrmSeccion();
}

function renderFrmSeccion() {
    const pltId   = _formulaActual.id;
    const cont    = document.getElementById('frmDetalleContainer');
    const secName = _frmSeccionActual;
    const items   = _frmItems.filter(it => (it.seccion||'') === (secName||''));

    const secE    = esc(secName||'');
    const label   = secName || 'Sin sección';
    const missing = items.filter(it => !it.dimension).length;

    // Agrupar por material
    const materiales = new Map();
    items.forEach(it => {
        const mat = it.prod_nombre || it.descripcion_material || '—';
        if (!materiales.has(mat)) materiales.set(mat, []);
        materiales.get(mat).push(it);
    });

    let html = `
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:8px;">
            <div>
                <div style="font-size:1rem;font-weight:600;">${esc(label)}</div>
                <div style="font-size:.77rem;color:var(--text-muted);margin-top:2px;">
                    ${items.length} material${items.length!==1?'es':''}
                    ${missing?` &nbsp;·&nbsp; <span style="color:#d97706;">${missing} sin medida</span>`:''}
                </div>
            </div>
            ${secName ? `<div style="display:flex;gap:5px;">
                <button class="btn btn-outline btn-xs" onclick="renombrarPiezaFormula(${pltId},'${secE}')" title="Renombrar"><i class="fa fa-pencil"></i></button>
                <button class="btn btn-outline btn-xs" style="color:var(--danger)" onclick="eliminarPiezaFormula(${pltId},'${secE}')" title="Eliminar sección"><i class="fa fa-trash"></i></button>
            </div>` : ''}
        </div>`;

    if (!items.length) {
        html += `<div style="padding:20px;text-align:center;color:var(--text-muted);font-size:.84rem;border:1px dashed var(--border);border-radius:8px;">
            Sin materiales en esta sección.
        </div>`;
    } else {
        html += `<div class="frm-sec-table">`;
        for (const [matName, rows] of materiales) {
            html += `<div class="frm-mat-header">
                <i class="fa fa-layer-group" style="font-size:11px;opacity:.55"></i>
                <span style="font-size:.75rem;font-weight:600;color:#334155;">${esc(matName)}</span>
            </div>
            <div style="display:grid;grid-template-columns:32px 80px 108px 1fr 160px;">
                <div class="frm-col-head"></div>
                <div class="frm-col-head">CANT.</div>
                <div class="frm-col-head">MEDIDA</div>
                <div class="frm-col-head">PIEZA / PARTE</div>
                <div class="frm-col-head">OPERACIÓN</div>
            </div>`;
            rows.forEach(it => {
                const hasDim = !!it.dimension;
                const dimHtml = hasDim
                    ? `<span style="font-family:monospace;font-size:.8rem;">${esc(it.dimension)}</span>`
                    : `<span class="frm-measure-miss"><i class="fa fa-pencil" style="font-size:10px"></i> Verificar</span>`;
                const parteTag = it.parte
                    ? `<span style="font-size:.68rem;background:#ede9fe;color:#5b21b6;border-radius:4px;padding:1px 5px;margin-left:4px;">${esc(it.parte)}</span>`
                    : '';
                const cod = it.prod_codigo
                    ? `<span style="font-size:.67rem;color:var(--text-muted);margin-left:3px">${esc(it.prod_codigo)}</span>`
                    : '';
                html += `<div class="frm-piece-row">
                    <div class="frm-pc" style="justify-content:center;">
                        <span class="frm-status-dot ${hasDim?'frm-dot-ok':'frm-dot-miss'}"></span>
                    </div>
                    <div class="frm-pc" style="font-weight:600;gap:3px;">
                        ${parseInt(it.cantidad_por_unidad)||0}
                        <span style="font-size:.68rem;color:var(--text-muted)">${esc(it.unidad||'')}</span>
                    </div>
                    <div class="frm-pc">${dimHtml}</div>
                    <div class="frm-pc" style="flex-wrap:wrap;gap:2px;">
                        ${esc(it.prod_nombre||it.descripcion_material||'—')}${cod}${parteTag}
                    </div>
                    <div class="frm-pc" style="flex-direction:column;align-items:flex-start;gap:5px;">
                        ${opBadge(it.operacion)}
                        <div style="display:flex;gap:4px;">
                            <button class="btn btn-outline btn-xs" onclick="abrirModalItemFormula(${pltId},${it.id})" title="Editar"><i class="fa fa-pencil"></i></button>
                            <button class="btn btn-outline btn-xs" style="color:var(--danger)" onclick="confirmarEliminarItemFormula(${it.id},${pltId})" title="Eliminar"><i class="fa fa-trash"></i></button>
                        </div>
                    </div>
                </div>`;
            });
        }
        html += `</div>`;
    }

    html += `<div class="frm-sec-actions">
        <button class="btn btn-sm" style="background:#86efac;border-color:#86efac;color:#166534;"
                onclick="abrirModalItemFormula(${pltId},null,'${secE}','')">
            <i class="fa fa-plus"></i> Agregar Material
        </button>
        ${secName ? `<button class="btn btn-outline btn-sm"
                onclick="abrirModalCfgMaterialFormula(${pltId},'${secE}')">
            <i class="fa fa-cog"></i> Configurar
        </button>` : ''}
    </div>`;

    cont.innerHTML = html;
}

// ── Fórmula: abrir modal crear/editar ─────────────────────
function abrirModalNuevaFormula(id, nombre, desc) {
    document.getElementById('formulaId').value    = id    || '';
    document.getElementById('formulaNombre').value = nombre || '';
    document.getElementById('formulaDesc').value  = desc  || '';
    document.getElementById('modalFormulaTitulo').textContent = id ? 'Editar Fórmula' : 'Nueva Fórmula';
    Modal.open('modalNuevaFormula');
}

async function guardarFormula() {
    const nombre = document.getElementById('formulaNombre').value.trim();
    if (!nombre) { Toast.warning('El nombre es requerido.'); return; }
    const id   = document.getElementById('formulaId').value;
    const desc = document.getElementById('formulaDesc').value.trim();
    try {
        const res = await apiPost(BASE, {
            action: 'plantilla_guardar',
            ...(id ? { id } : {}),
            nombre,
            descripcion: desc
        });
        if (res.ok) {
            Toast.success(id ? 'Fórmula actualizada.' : 'Fórmula creada.');
            Modal.close('modalNuevaFormula');
            _plantillasCarga = false;
            await cargarPlantillas();
            if (id && _formulaActual && _formulaActual.id == id) abrirFormula(id);
        } else Toast.error(res.error || 'Error al guardar.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

async function confirmarEliminarFormula(id, nombre) {
    if (!confirm(`¿Eliminar la fórmula "${nombre}"? Esta acción no se puede deshacer.`)) return;
    try {
        const res = await apiPost(BASE, { action: 'plantilla_eliminar', id });
        if (res.ok) {
            Toast.success('Fórmula eliminada.');
            volverListaFormulas();
            _plantillasCarga = false;
            await cargarPlantillas();
            cargarPlantillasDropdown();
        } else Toast.error(res.error || 'Error.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

// ── Item fórmula: abrir modal agregar/editar ─────────────
function abrirModalItemFormula(pltId, itemId, prefillSec, prefillParte, focusField) {
    document.getElementById('itemFormulaPid').value        = pltId;
    document.getElementById('itemFormulaId').value         = itemId || '';
    document.getElementById('itemFormulaSec').value        = prefillSec  !== undefined ? prefillSec  : '';
    document.getElementById('itemFormulaParte').value      = prefillParte !== undefined ? prefillParte : '';
    document.getElementById('itemFormulaMatBuscar').value  = '';
    document.getElementById('itemFormulaProductoId').value = '';
    document.getElementById('itemFormulaDim').value        = '';
    document.getElementById('itemFormulaUnidad').value     = 'unidad';
    document.getElementById('itemFormulaCant').value       = '1';
    document.getElementById('itemFormulaOp').value         = '';
    document.getElementById('itemFormulaDrop').style.display = 'none';
    document.getElementById('modalItemFormulaTitulo').textContent = itemId ? 'Editar Material' : 'Agregar Material';
    if (itemId) {
        const it = (_itemsCache[pltId] || []).find(x => x.id == itemId);
        if (it) {
            document.getElementById('itemFormulaSec').value        = it.seccion   || '';
            document.getElementById('itemFormulaParte').value      = it.parte     || '';
            document.getElementById('itemFormulaMatBuscar').value  = it.prod_nombre || it.descripcion_material || '';
            document.getElementById('itemFormulaProductoId').value = it.producto_id || '';
            document.getElementById('itemFormulaDim').value        = it.dimension || '';
            document.getElementById('itemFormulaUnidad').value     = it.unidad    || 'unidad';
            document.getElementById('itemFormulaCant').value       = it.cantidad_por_unidad || 1;
            document.getElementById('itemFormulaOp').value         = it.operacion || '';
        }
    }
    Modal.open('modalItemFormula');
    if (focusField === 'sec')   setTimeout(() => document.getElementById('itemFormulaSec').focus(),   80);
    if (focusField === 'parte') setTimeout(() => document.getElementById('itemFormulaParte').focus(), 80);
}

async function guardarItemFormula() {
    const pltId = document.getElementById('itemFormulaPid').value;
    const id    = document.getElementById('itemFormulaId').value;
    const mat   = document.getElementById('itemFormulaMatBuscar').value.trim();
    if (!mat) { Toast.warning('El material es requerido.'); return; }
    const payload = {
        action: 'plantilla_item_guardar',
        plantilla_id:        pltId,
        seccion:             document.getElementById('itemFormulaSec').value.trim(),
        parte:               document.getElementById('itemFormulaParte').value.trim(),
        producto_id:         document.getElementById('itemFormulaProductoId').value || null,
        descripcion_material: mat,
        dimension:           document.getElementById('itemFormulaDim').value.trim(),
        unidad:              document.getElementById('itemFormulaUnidad').value.trim(),
        cantidad_por_unidad: parseInt(document.getElementById('itemFormulaCant').value) || 1,
        operacion:           document.getElementById('itemFormulaOp').value.trim()
    };
    if (id) payload.id = id;
    try {
        const res = await apiPost(BASE, payload);
        if (res.ok) {
            Toast.success(id ? 'Material actualizado.' : 'Material agregado.');
            Modal.close('modalItemFormula');
            delete _itemsCache[pltId];
            cargarDetalleFormula(parseInt(pltId));
        } else Toast.error(res.error || 'Error al guardar.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

async function confirmarEliminarItemFormula(id, plantillaId) {
    if (!confirm('¿Eliminar este material de la fórmula?')) return;
    try {
        const res = await apiPost(BASE, { action: 'plantilla_item_eliminar', id });
        if (res.ok) {
            Toast.success('Material eliminado.');
            delete _itemsCache[plantillaId];
            cargarDetalleFormula(plantillaId);
        } else Toast.error(res.error || 'Error.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

// ── Configurar Material (propiedades de pieza en plantilla) ─
function abrirModalCfgMaterialFormula(pltId, piezaName) {
    document.getElementById('cfgFrmPltId').value       = pltId;
    document.getElementById('cfgFrmPiezaViejo').value  = piezaName;
    document.getElementById('cfgFrmPiezaNombre').value = piezaName;
    document.getElementById('cfgFrmMaterial').value    = '';
    document.getElementById('cfgFrmLargo').value       = '';
    document.getElementById('cfgFrmAncho').value       = '';
    document.getElementById('cfgFrmAlto').value        = '';
    document.getElementById('cfgFrmPeso').value        = '';
    document.getElementById('cfgFrmNorma').value       = '';
    document.getElementById('cfgFrmObs').value         = '';
    // Cargar datos guardados si existen
    const cache = _itemsCache[pltId] || [];
    const cfg   = cache.find(it => it.seccion === piezaName && it._cfg);
    if (cfg) {
        const p = cfg._cfg;
        document.getElementById('cfgFrmMaterial').value = p.material || '';
        document.getElementById('cfgFrmLargo').value    = p.largo    || '';
        document.getElementById('cfgFrmAncho').value    = p.ancho    || '';
        document.getElementById('cfgFrmAlto').value     = p.alto     || '';
        document.getElementById('cfgFrmPeso').value     = p.peso     || '';
        document.getElementById('cfgFrmNorma').value    = p.norma    || '';
        document.getElementById('cfgFrmObs').value      = p.obs      || '';
    }
    Modal.open('modalCfgMaterialFormula');
}

async function guardarCfgMaterialFormula() {
    const pltId     = document.getElementById('cfgFrmPltId').value;
    const viejo     = document.getElementById('cfgFrmPiezaViejo').value;
    const nuevo     = document.getElementById('cfgFrmPiezaNombre').value.trim();
    if (!nuevo) { Toast.warning('El nombre de la pieza es requerido.'); return; }
    const propiedades = {
        material: document.getElementById('cfgFrmMaterial').value.trim(),
        largo:    document.getElementById('cfgFrmLargo').value,
        ancho:    document.getElementById('cfgFrmAncho').value,
        alto:     document.getElementById('cfgFrmAlto').value,
        peso:     document.getElementById('cfgFrmPeso').value,
        norma:    document.getElementById('cfgFrmNorma').value.trim(),
        obs:      document.getElementById('cfgFrmObs').value.trim(),
    };
    try {
        const res = await apiPost(BASE, {
            action: 'plantilla_cfg_pieza',
            plantilla_id:  pltId,
            seccion_viejo: viejo,
            seccion_nuevo: nuevo,
            propiedades
        });
        if (res.ok) {
            Toast.success('Pieza configurada.');
            Modal.close('modalCfgMaterialFormula');
            delete _itemsCache[pltId];
            cargarDetalleFormula(parseInt(pltId));
        } else Toast.error(res.error || 'Error al guardar.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

async function renombrarPiezaFormula(pltId, piezaName) {
    const nuevo = prompt(`Nuevo nombre para la pieza "${piezaName}":`, piezaName);
    if (!nuevo || nuevo.trim() === piezaName) return;
    try {
        const res = await apiPost(BASE, {
            action: 'plantilla_cfg_pieza',
            plantilla_id:  pltId,
            seccion_viejo: piezaName,
            seccion_nuevo: nuevo.trim()
        });
        if (res.ok) {
            _frmSeccionActual = nuevo.trim();
            delete _itemsCache[pltId];
            cargarDetalleFormula(parseInt(pltId));
        } else Toast.error(res.error || 'Error.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

async function eliminarPiezaFormula(pltId, piezaName) {
    if (!confirm(`¿Eliminar la pieza "${piezaName}" y todos sus materiales?`)) return;
    try {
        const res = await apiPost(BASE, {
            action: 'plantilla_eliminar_pieza',
            plantilla_id: pltId,
            seccion:      piezaName
        });
        if (res.ok) {
            Toast.success('Sección eliminada.');
            _frmSeccionActual = null;
            delete _itemsCache[pltId];
            cargarDetalleFormula(parseInt(pltId));
        } else Toast.error(res.error || 'Error.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

// ── Autocompletar producto en modal item ──────────────────
let _acTimer;
document.getElementById('itemFormulaMatBuscar').addEventListener('input', function() {
    clearTimeout(_acTimer);
    document.getElementById('itemFormulaProductoId').value = '';
    const q = this.value.trim();
    if (q.length < 2) { document.getElementById('itemFormulaDrop').style.display = 'none'; return; }
    _acTimer = setTimeout(async () => {
        try {
            const data  = await apiGet(`${BASE}?action=buscar_productos&q=${encodeURIComponent(q)}`);
            const drop  = document.getElementById('itemFormulaDrop');
            const prods = data.productos || [];
            if (!prods.length) { drop.style.display = 'none'; return; }
            drop.innerHTML = prods.map(p =>
                `<div style="padding:8px 12px;cursor:pointer;font-size:.85rem;border-bottom:1px solid #f1f5f9"
                      onmousedown="seleccionarProductoItem(${p.id},'${esc(p.nombre)}','${esc(p.unidad||'')}')">
                    <strong>${esc(p.codigo)}</strong> — ${esc(p.nombre)}
                    <span style="float:right;color:var(--text-muted);font-size:.75rem">${esc(p.unidad||'')}</span>
                </div>`
            ).join('');
            drop.style.display = 'block';
        } catch(_) {}
    }, 300);
});
document.getElementById('itemFormulaMatBuscar').addEventListener('blur', () => {
    setTimeout(() => { document.getElementById('itemFormulaDrop').style.display = 'none'; }, 200);
});
function seleccionarProductoItem(id, nombre, unidad) {
    document.getElementById('itemFormulaProductoId').value = id;
    document.getElementById('itemFormulaMatBuscar').value  = nombre;
    const uEl = document.getElementById('itemFormulaUnidad');
    if (unidad && (!uEl.value || uEl.value === 'unidad')) uEl.value = unidad;
    document.getElementById('itemFormulaDrop').style.display = 'none';
}

// ── Iniciar ───────────────────────────────────────────────
cargarProyectos();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

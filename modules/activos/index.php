<?php
$page_title      = 'Activos & Herramientas';
$page_breadcrumb = 'Activos';
require_once __DIR__ . '/../../includes/header.php';
?>

<!-- TABS -->
<div class="tabs">
    <div class="tab active" data-group="act" data-target="tabResumen">
        <i class="fa fa-gauge"></i> Resumen
    </div>
    <div class="tab" data-group="act" data-target="tabActivos">
        <i class="fa fa-toolbox"></i> Herramientas
    </div>
    <div class="tab" data-group="act" data-target="tabTransporte">
        <i class="fa fa-truck"></i> Transporte
    </div>
    <div class="tab" data-group="act" data-target="tabMobiliaria">
        <i class="fa fa-chair"></i> Mobiliaria y Equipos
    </div>
    <div class="tab" data-group="act" data-target="tabHistorial">
        <i class="fa fa-clock-rotate-left"></i> Historial
    </div>
    <div class="tab" data-group="act" data-target="tabCategorias">
        <i class="fa fa-tags"></i> Categorías
    </div>
</div>

<!-- ======================================================
     TAB: RESUMEN
     ====================================================== -->
<div class="tab-content active" data-group="act" id="tabResumen">
    <div class="kpi-grid" id="kpiGrid" style="margin-bottom:16px">
        <div class="kpi-card"><div class="kpi-value" id="kpiTotal">—</div><div class="kpi-label">Total Herramientas</div></div>
        <div class="kpi-card"><div class="kpi-value text-primary" id="kpiAsignados">—</div><div class="kpi-label">Asignadas ahora</div></div>
        <div class="kpi-card"><div class="kpi-value text-success" id="kpiDisponibles">—</div><div class="kpi-label">Disponibles</div></div>
        <div class="kpi-card"><div class="kpi-value text-danger" id="kpiDañados">—</div><div class="kpi-label">Dañadas</div></div>
        <div class="kpi-card"><div class="kpi-value text-warning" id="kpiVencidos">—</div><div class="kpi-label">Asig. Vencidas</div></div>
    </div>
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fa fa-user-check"></i> Asignaciones Activas</span>
        </div>
        <div class="table-responsive">
            <table>
                <thead><tr>
                    <th>Código</th><th>Herramienta</th><th>Responsable</th>
                    <th>Período</th><th>Desde</th><th>Hasta</th><th>Estado</th><th></th>
                </tr></thead>
                <tbody id="tbodyResumen">
                    <tr><td colspan="8" class="text-center text-muted">Cargando…</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ======================================================
     TAB: HERRAMIENTAS
     ====================================================== -->
<div class="tab-content" data-group="act" id="tabActivos">
    <div class="toolbar">
        <div class="toolbar-left">
            <input type="text" class="form-control" id="buscarActivo" placeholder="Buscar herramienta…"
                   oninput="cargarActivos()" style="width:220px">
            <select class="form-control" id="filtroCategoria" onchange="cargarActivos()" style="width:180px">
                <option value="">Todas las categorías</option>
            </select>
            <select class="form-control" id="filtroEstado" onchange="cargarActivos()" style="width:150px">
                <option value="">Todos los estados</option>
                <option value="bueno">Bueno</option>
                <option value="regular">Regular</option>
                <option value="dañado">Dañado</option>
                <option value="baja">Baja</option>
            </select>
            <label style="display:flex;align-items:center;gap:6px;font-size:.85rem;cursor:pointer">
                <input type="checkbox" id="soloDisponibles" onchange="cargarActivos()"> Solo disponibles
            </label>
        </div>
        <div class="toolbar-right">
            <button class="btn btn-primary" onclick="abrirModalActivo()">
                <i class="fa fa-plus"></i> Nueva Herramienta
            </button>
        </div>
    </div>
    <div class="table-responsive">
        <table id="tablaActivos">
            <thead><tr>
                <th style="width:52px"></th>
                <th>Código</th><th>Herramienta</th><th>Categoría</th>
                <th>N° Serie</th><th>Estado Físico</th><th>Asignación</th>
                <th>Responsable actual</th><th>Hasta</th><th></th>
            </tr></thead>
            <tbody id="tbodyActivos">
                <tr><td colspan="10" class="text-center text-muted">Cargando…</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ======================================================
     TAB: TRANSPORTE
     ====================================================== -->
<div class="tab-content" data-group="act" id="tabTransporte">
    <div class="toolbar">
        <div class="toolbar-left">
            <input type="text" class="form-control" id="buscarTrans" placeholder="Buscar nombre, placa, marca…"
                   oninput="cargarTransporte()" style="width:240px">
            <select class="form-control" id="filtroTransEstado" onchange="cargarTransporte()" style="width:170px">
                <option value="">Todos los estados</option>
                <option value="operativo">Operativo</option>
                <option value="mantenimiento">En mantenimiento</option>
                <option value="baja">Fuera de servicio</option>
            </select>
        </div>
        <div class="toolbar-right">
            <div style="display:flex;gap:2px;margin-right:8px">
                <button id="btnViewList" class="btn btn-sm btn-primary" onclick="setTransView('list')" title="Vista lista">
                    <i class="fa fa-list"></i>
                </button>
                <button id="btnViewGrid" class="btn btn-sm btn-secondary" onclick="setTransView('grid')" title="Vista cuadrícula">
                    <i class="fa fa-grip"></i>
                </button>
            </div>
            <button class="btn btn-primary" onclick="abrirModalTransporte()">
                <i class="fa fa-plus"></i> Nuevo Vehículo
            </button>
        </div>
    </div>
    <div id="transListView">
        <div class="table-responsive">
            <table id="tablaTransporte">
                <thead><tr>
                    <th style="width:52px"></th>
                    <th>Código</th><th>Nombre</th><th>Placa</th>
                    <th>Marca / Modelo</th><th>Año</th><th>Color</th><th>Estado</th><th></th>
                </tr></thead>
                <tbody id="tbodyTransporte">
                    <tr><td colspan="9" class="text-center text-muted">Cargando…</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <div id="transGridView" style="display:none">
        <div id="transGridCards" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:16px;padding:16px 0"></div>
    </div>
</div>

<!-- ======================================================
     TAB: MOBILIARIA Y EQUIPOS
     ====================================================== -->
<div class="tab-content" data-group="act" id="tabMobiliaria">
    <div class="toolbar">
        <div class="toolbar-left">
            <input type="text" class="form-control" id="buscarMob" placeholder="Buscar nombre, marca, ubicación…"
                   oninput="cargarMobiliaria()" style="width:250px">
            <select class="form-control" id="filtroMobTipo" onchange="cargarMobiliaria()" style="width:150px">
                <option value="">Todos los tipos</option>
                <option value="mobiliario">Mobiliario</option>
                <option value="equipo">Equipo</option>
            </select>
            <select class="form-control" id="filtroMobEstado" onchange="cargarMobiliaria()" style="width:140px">
                <option value="">Todos los estados</option>
                <option value="bueno">Bueno</option>
                <option value="regular">Regular</option>
                <option value="dañado">Dañado</option>
                <option value="baja">Baja</option>
            </select>
        </div>
        <div class="toolbar-right">
            <button class="btn btn-primary" onclick="abrirModalMobiliaria()">
                <i class="fa fa-plus"></i> Nuevo Ítem
            </button>
        </div>
    </div>
    <div class="table-responsive">
        <table id="tablaMobiliaria">
            <thead><tr>
                <th style="width:52px"></th>
                <th>Código</th><th>Nombre</th><th>Tipo</th>
                <th>Ubicación</th><th>Marca / Modelo</th><th>Estado</th><th></th>
            </tr></thead>
            <tbody id="tbodyMobiliaria">
                <tr><td colspan="8" class="text-center text-muted">Cargando…</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ======================================================
     TAB: HISTORIAL
     ====================================================== -->
<div class="tab-content" data-group="act" id="tabHistorial">
    <div class="toolbar">
        <div class="toolbar-left">
            <select class="form-control" id="filtroHistUsuario" onchange="cargarHistorial()" style="width:200px">
                <option value="">Todos los responsables</option>
            </select>
        </div>
    </div>
    <div class="table-responsive">
        <table>
            <thead><tr>
                <th>Herramienta</th><th>Responsable</th><th>Período</th>
                <th>Desde</th><th>Hasta</th><th>Devuelto</th><th>Observaciones</th>
            </tr></thead>
            <tbody id="tbodyHistorial">
                <tr><td colspan="7" class="text-center text-muted">Cargando…</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ======================================================
     TAB: CATEGORÍAS
     ====================================================== -->
<div class="tab-content" data-group="act" id="tabCategorias">
    <div class="toolbar">
        <div class="toolbar-left"></div>
        <div class="toolbar-right">
            <button class="btn btn-primary" onclick="abrirModalCategoria()">
                <i class="fa fa-plus"></i> Nueva Categoría
            </button>
        </div>
    </div>
    <div class="table-responsive">
        <table>
            <thead><tr>
                <th>Nombre</th><th>Descripción</th><th>Activos</th><th></th>
            </tr></thead>
            <tbody id="tbodyCategorias">
                <tr><td colspan="4" class="text-center text-muted">Cargando…</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ======================================================
     MODALS
     ====================================================== -->

<!-- Modal: Nueva/Editar Herramienta -->
<div class="modal-overlay" id="modalActivo">
    <div class="modal" style="max-width:580px">
        <div class="modal-header">
            <span class="modal-title" id="modalActivoTitulo">Nueva Herramienta</span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="activoId">
            <!-- Imagen -->
            <div class="form-group">
                <label class="form-label">Imagen</label>
                <div style="display:flex;align-items:center;gap:14px">
                    <div id="activo_img_preview" style="width:80px;height:80px;border:2px dashed var(--border);border-radius:8px;display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0">
                        <span style="font-size:.68rem;color:var(--text-secondary);text-align:center;padding:4px">Sin imagen</span>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:6px">
                        <input type="file" id="activo_imagen" accept="image/*" style="display:none" onchange="previewActImg(this)">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('activo_imagen').click()">
                            <i class="fa fa-upload"></i> Subir imagen
                        </button>
                        <button type="button" class="btn btn-sm" id="activo_btn_quitar" style="display:none;background:var(--danger);color:#fff" onclick="quitarActImg()">
                            <i class="fa fa-trash"></i> Quitar imagen
                        </button>
                    </div>
                </div>
            </div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Nombre *</label>
                    <input type="text" class="form-control" id="activoNombre" placeholder="Ej: Taladro Bosch 13mm">
                </div>
                <div class="form-group">
                    <label class="form-label">Categoría</label>
                    <select class="form-control" id="activoCategoria">
                        <option value="">— Sin categoría —</option>
                    </select>
                </div>
            </div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">N° de Serie / Interno</label>
                    <input type="text" class="form-control" id="activoSerie" placeholder="Opcional">
                </div>
                <div class="form-group">
                    <label class="form-label">Fecha adquisición</label>
                    <input type="date" class="form-control" id="activoFechaAdq">
                </div>
            </div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Estado físico</label>
                    <select class="form-control" id="activoEstado">
                        <option value="bueno">Bueno</option>
                        <option value="regular">Regular</option>
                        <option value="dañado">Dañado</option>
                        <option value="baja">Baja / Desechado</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <input type="text" class="form-control" id="activoDesc" placeholder="Opcional">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Observaciones</label>
                <textarea class="form-control" id="activoObs" rows="2" placeholder="Opcional"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalActivo')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarActivo()">
                <i class="fa fa-save"></i> Guardar
            </button>
        </div>
    </div>
</div>

<!-- Modal: Asignar Herramienta -->
<div class="modal-overlay" id="modalAsignar">
    <div class="modal" style="max-width:460px">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-user-check"></i> Asignar Herramienta</span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="asigActivoId">
            <div class="form-group">
                <label class="form-label">Herramienta</label>
                <input type="text" class="form-control" id="asigActivoNombre" disabled>
            </div>
            <div class="form-group">
                <label class="form-label">Responsable *</label>
                <select class="form-control" id="asigUsuario">
                    <option value="">— Seleccionar —</option>
                </select>
            </div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Período *</label>
                    <select class="form-control" id="asigPeriodo" onchange="actualizarFechaFin()">
                        <option value="dia">Día (1 día)</option>
                        <option value="semana">Semana (7 días)</option>
                        <option value="mes">Mes (30 días)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Fecha inicio *</label>
                    <input type="date" class="form-control" id="asigFechaInicio" onchange="actualizarFechaFin()">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Fecha de devolución estimada</label>
                <input type="text" class="form-control" id="asigFechaFin" disabled
                       style="background:var(--bg-muted);font-weight:600">
            </div>
            <div class="form-group">
                <label class="form-label">Observaciones</label>
                <textarea class="form-control" id="asigObs" rows="2" placeholder="Opcional"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalAsignar')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarAsignacion()">
                <i class="fa fa-check"></i> Asignar
            </button>
        </div>
    </div>
</div>

<!-- Modal: Transporte -->
<div class="modal-overlay" id="modalTransporte">
    <div class="modal" style="max-width:600px">
        <div class="modal-header">
            <span class="modal-title" id="modalTransTitulo">Nuevo Vehículo</span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="transId">
            <!-- Galería de imágenes (hasta 4) -->
            <div class="form-group">
                <label class="form-label">Imágenes (hasta 4) — clic en cada foto para subir</label>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <!-- Slot 0 -->
                    <div style="display:flex;flex-direction:column;align-items:center;gap:5px">
                        <div id="trans_slot_0" style="width:120px;height:95px;border:2px dashed var(--border);border-radius:8px;display:flex;align-items:center;justify-content:center;overflow:hidden;cursor:pointer" onclick="document.getElementById('trans_file_0').click()">
                            <span style="font-size:.65rem;color:var(--text-secondary);text-align:center;padding:4px">Foto 1<br><i class="fa fa-plus" style="font-size:.9rem;display:block;margin-top:4px"></i></span>
                        </div>
                        <input type="hidden" id="trans_keep_0">
                        <input type="file" id="trans_file_0" accept="image/*" style="display:none" onchange="transSlotChange(0,this)">
                        <button type="button" id="trans_quit_0" class="btn btn-sm" style="display:none;background:var(--danger);color:#fff;padding:2px 10px;font-size:.73rem" onclick="transSlotRemove(0)">
                            <i class="fa fa-trash"></i> Quitar
                        </button>
                    </div>
                    <!-- Slot 1 -->
                    <div style="display:flex;flex-direction:column;align-items:center;gap:5px">
                        <div id="trans_slot_1" style="width:120px;height:95px;border:2px dashed var(--border);border-radius:8px;display:flex;align-items:center;justify-content:center;overflow:hidden;cursor:pointer" onclick="document.getElementById('trans_file_1').click()">
                            <span style="font-size:.65rem;color:var(--text-secondary);text-align:center;padding:4px">Foto 2<br><i class="fa fa-plus" style="font-size:.9rem;display:block;margin-top:4px"></i></span>
                        </div>
                        <input type="hidden" id="trans_keep_1">
                        <input type="file" id="trans_file_1" accept="image/*" style="display:none" onchange="transSlotChange(1,this)">
                        <button type="button" id="trans_quit_1" class="btn btn-sm" style="display:none;background:var(--danger);color:#fff;padding:2px 10px;font-size:.73rem" onclick="transSlotRemove(1)">
                            <i class="fa fa-trash"></i> Quitar
                        </button>
                    </div>
                    <!-- Slot 2 -->
                    <div style="display:flex;flex-direction:column;align-items:center;gap:5px">
                        <div id="trans_slot_2" style="width:120px;height:95px;border:2px dashed var(--border);border-radius:8px;display:flex;align-items:center;justify-content:center;overflow:hidden;cursor:pointer" onclick="document.getElementById('trans_file_2').click()">
                            <span style="font-size:.65rem;color:var(--text-secondary);text-align:center;padding:4px">Foto 3<br><i class="fa fa-plus" style="font-size:.9rem;display:block;margin-top:4px"></i></span>
                        </div>
                        <input type="hidden" id="trans_keep_2">
                        <input type="file" id="trans_file_2" accept="image/*" style="display:none" onchange="transSlotChange(2,this)">
                        <button type="button" id="trans_quit_2" class="btn btn-sm" style="display:none;background:var(--danger);color:#fff;padding:2px 10px;font-size:.73rem" onclick="transSlotRemove(2)">
                            <i class="fa fa-trash"></i> Quitar
                        </button>
                    </div>
                    <!-- Slot 3 -->
                    <div style="display:flex;flex-direction:column;align-items:center;gap:5px">
                        <div id="trans_slot_3" style="width:120px;height:95px;border:2px dashed var(--border);border-radius:8px;display:flex;align-items:center;justify-content:center;overflow:hidden;cursor:pointer" onclick="document.getElementById('trans_file_3').click()">
                            <span style="font-size:.65rem;color:var(--text-secondary);text-align:center;padding:4px">Foto 4<br><i class="fa fa-plus" style="font-size:.9rem;display:block;margin-top:4px"></i></span>
                        </div>
                        <input type="hidden" id="trans_keep_3">
                        <input type="file" id="trans_file_3" accept="image/*" style="display:none" onchange="transSlotChange(3,this)">
                        <button type="button" id="trans_quit_3" class="btn btn-sm" style="display:none;background:var(--danger);color:#fff;padding:2px 10px;font-size:.73rem" onclick="transSlotRemove(3)">
                            <i class="fa fa-trash"></i> Quitar
                        </button>
                    </div>
                </div>
            </div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Nombre *</label>
                    <input type="text" class="form-control" id="transNombre" placeholder="Ej: Camioneta Hilux">
                </div>
                <div class="form-group">
                    <label class="form-label">Placa</label>
                    <input type="text" class="form-control" id="transPlaca" placeholder="Ej: ABC-123" style="text-transform:uppercase">
                </div>
            </div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Marca</label>
                    <input type="text" class="form-control" id="transMarca" placeholder="Ej: Toyota">
                </div>
                <div class="form-group">
                    <label class="form-label">Modelo</label>
                    <input type="text" class="form-control" id="transModelo" placeholder="Ej: Hilux 4x4">
                </div>
            </div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Año</label>
                    <input type="number" class="form-control" id="transAnio" min="1990" max="2030" placeholder="Ej: 2022">
                </div>
                <div class="form-group">
                    <label class="form-label">Color</label>
                    <input type="text" class="form-control" id="transColor" placeholder="Ej: Blanco">
                </div>
            </div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select class="form-control" id="transEstado">
                        <option value="operativo">Operativo</option>
                        <option value="mantenimiento">En mantenimiento</option>
                        <option value="baja">Fuera de servicio</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <input type="text" class="form-control" id="transDesc" placeholder="Opcional">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Observaciones</label>
                <textarea class="form-control" id="transObs" rows="2" placeholder="Opcional"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalTransporte')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarTransporte()">
                <i class="fa fa-save"></i> Guardar
            </button>
        </div>
    </div>
</div>

<!-- Modal: Mobiliaria y Equipos -->
<div class="modal-overlay" id="modalMobiliaria">
    <div class="modal" style="max-width:600px">
        <div class="modal-header">
            <span class="modal-title" id="modalMobTitulo">Nuevo Ítem</span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="mobId">
            <!-- Imagen -->
            <div class="form-group">
                <label class="form-label">Imagen</label>
                <div style="display:flex;align-items:center;gap:14px">
                    <div id="mob_img_preview" style="width:80px;height:80px;border:2px dashed var(--border);border-radius:8px;display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0">
                        <span style="font-size:.68rem;color:var(--text-secondary);text-align:center;padding:4px">Sin imagen</span>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:6px">
                        <input type="file" id="mob_imagen" accept="image/*" style="display:none" onchange="previewMobImg(this)">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('mob_imagen').click()">
                            <i class="fa fa-upload"></i> Subir imagen
                        </button>
                        <button type="button" class="btn btn-sm" id="mob_btn_quitar" style="display:none;background:var(--danger);color:#fff" onclick="quitarMobImg()">
                            <i class="fa fa-trash"></i> Quitar imagen
                        </button>
                    </div>
                </div>
            </div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Nombre *</label>
                    <input type="text" class="form-control" id="mobNombre" placeholder="Ej: Escritorio ejecutivo">
                </div>
                <div class="form-group">
                    <label class="form-label">Tipo</label>
                    <select class="form-control" id="mobTipo">
                        <option value="mobiliario">Mobiliario</option>
                        <option value="equipo">Equipo</option>
                    </select>
                </div>
            </div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Ubicación</label>
                    <input type="text" class="form-control" id="mobUbicacion" placeholder="Ej: Oficina principal">
                </div>
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select class="form-control" id="mobEstado">
                        <option value="bueno">Bueno</option>
                        <option value="regular">Regular</option>
                        <option value="dañado">Dañado</option>
                        <option value="baja">Baja</option>
                    </select>
                </div>
            </div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Marca</label>
                    <input type="text" class="form-control" id="mobMarca" placeholder="Opcional">
                </div>
                <div class="form-group">
                    <label class="form-label">Modelo</label>
                    <input type="text" class="form-control" id="mobModelo" placeholder="Opcional">
                </div>
            </div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">N° Serie</label>
                    <input type="text" class="form-control" id="mobNumSerie" placeholder="Opcional">
                </div>
                <div class="form-group">
                    <label class="form-label">N° Inventario</label>
                    <input type="text" class="form-control" id="mobNumInv" placeholder="Opcional">
                </div>
            </div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Fecha adquisición</label>
                    <input type="date" class="form-control" id="mobFechaAdq">
                </div>
                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <input type="text" class="form-control" id="mobDesc" placeholder="Opcional">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Observaciones</label>
                <textarea class="form-control" id="mobObs" rows="2" placeholder="Opcional"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalMobiliaria')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarMobiliaria()">
                <i class="fa fa-save"></i> Guardar
            </button>
        </div>
    </div>
</div>

<!-- Modal: Categoría -->
<div class="modal-overlay" id="modalCategoria">
    <div class="modal" style="max-width:400px">
        <div class="modal-header">
            <span class="modal-title" id="modalCategoriaTitulo">Nueva Categoría</span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="catId">
            <div class="form-group">
                <label class="form-label">Nombre *</label>
                <input type="text" class="form-control" id="catNombre">
            </div>
            <div class="form-group">
                <label class="form-label">Descripción</label>
                <input type="text" class="form-control" id="catDesc">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalCategoria')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarCategoria()">
                <i class="fa fa-save"></i> Guardar
            </button>
        </div>
    </div>
</div>

<script>
const BASE = 'api.php';
const IMG_BASE = '../../assets/uploads/activos/';

// ── Helpers ───────────────────────────────────────────────
function esc(s) { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; }

function badgeEstado(e) {
    const map = {
        bueno:   ['badge-completado', 'Bueno'],
        regular: ['badge-pruebas',    'Regular'],
        dañado:  ['badge-cancelado',  'Dañado'],
        baja:    ['badge-normal',     'Baja'],
    };
    const [cls, lbl] = map[e] || ['badge-normal', e];
    return `<span class="badge ${cls}">${lbl}</span>`;
}

function badgeAsig(estado) {
    const map = {
        disponible: ['badge-completado',  '<i class="fa fa-circle-check"></i> Disponible'],
        asignado:   ['badge-fabricacion', '<i class="fa fa-user-check"></i> Asignado'],
        vencida:    ['badge-retrasado',   '<i class="fa fa-triangle-exclamation"></i> Vencida'],
    };
    const [cls, lbl] = map[estado] || ['badge-normal', estado];
    return `<span class="badge ${cls}" style="font-size:.7rem">${lbl}</span>`;
}

function badgeEstadoTrans(e) {
    const map = {
        operativo:     ['badge-completado', 'Operativo'],
        mantenimiento: ['badge-pruebas',    'Mantenimiento'],
        baja:          ['badge-cancelado',  'Fuera de servicio'],
    };
    const [cls, lbl] = map[e] || ['badge-normal', e];
    return `<span class="badge ${cls}">${lbl}</span>`;
}

function labelPeriodo(p) { return { dia:'Día', semana:'Semana', mes:'Mes' }[p] || p; }

function calcFechaFin(inicio, periodo) {
    const d = new Date(inicio + 'T00:00:00');
    if (periodo === 'semana') d.setDate(d.getDate() + 6);
    else if (periodo === 'mes') d.setDate(d.getDate() + 29);
    return d.toISOString().slice(0, 10);
}

function imgThumb(filename) {
    return filename
        ? `<img src="${IMG_BASE}${filename}" style="width:40px;height:40px;object-fit:cover;border-radius:4px;border:1px solid var(--border)">`
        : `<div style="width:40px;height:40px;border:1px dashed var(--border);border-radius:4px;display:inline-flex;align-items:center;justify-content:center;color:var(--text-secondary);font-size:.65rem;line-height:1.1;text-align:center">sin<br>img</div>`;
}

// ── Shared image preview helpers ──────────────────────────
function makePreviewFn(previewId, quitarBtnId) {
    return function(input) {
        if (!input.files || !input.files[0]) return;
        const reader = new FileReader();
        reader.onload = e => {
            const p = document.getElementById(previewId);
            p.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover">`;
            p.dataset.removed = '';
            document.getElementById(quitarBtnId).style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    };
}

function makeQuitarFn(previewId, inputId, quitarBtnId) {
    return function() {
        const p = document.getElementById(previewId);
        p.innerHTML = '<span style="font-size:.68rem;color:var(--text-secondary);text-align:center;padding:4px">Sin imagen</span>';
        p.dataset.current = '';
        p.dataset.removed = '1';
        document.getElementById(inputId).value = '';
        document.getElementById(quitarBtnId).style.display = 'none';
    };
}

function resetImgPreview(previewId, inputId, quitarBtnId) {
    const p = document.getElementById(previewId);
    p.innerHTML = '<span style="font-size:.68rem;color:var(--text-secondary);text-align:center;padding:4px">Sin imagen</span>';
    p.dataset.current = '';
    p.dataset.removed = '';
    document.getElementById(inputId).value = '';
    document.getElementById(quitarBtnId).style.display = 'none';
}

function loadImgPreview(previewId, quitarBtnId, filename) {
    const p = document.getElementById(previewId);
    if (filename) {
        p.innerHTML = `<img src="${IMG_BASE}${filename}" style="width:100%;height:100%;object-fit:cover">`;
        p.dataset.current = filename;
        document.getElementById(quitarBtnId).style.display = 'block';
    } else {
        p.innerHTML = '<span style="font-size:.68rem;color:var(--text-secondary);text-align:center;padding:4px">Sin imagen</span>';
        p.dataset.current = '';
        document.getElementById(quitarBtnId).style.display = 'none';
    }
    p.dataset.removed = '';
}

function buildFormData(action, fields, fileInputId, previewId) {
    const fd = new FormData();
    fd.append('action', action);
    for (const [k, v] of Object.entries(fields)) fd.append(k, v ?? '');
    const file = document.getElementById(fileInputId).files[0];
    if (file) {
        fd.append('imagen', file);
    } else if (document.getElementById(previewId).dataset.removed === '1') {
        fd.append('remove_imagen', '1');
    }
    return fd;
}

// Bind image preview handlers
const previewActImg  = makePreviewFn('activo_img_preview', 'activo_btn_quitar');
const quitarActImg   = makeQuitarFn('activo_img_preview', 'activo_imagen', 'activo_btn_quitar');
// Transporte multi-slot state
let transSlots = Array.from({length:4}, () => ({existingId:null, existingFilename:null, file:null, removed:false}));
let transView  = 'list';

function setTransView(mode) {
    transView = mode;
    document.getElementById('transListView').style.display = mode === 'list' ? '' : 'none';
    document.getElementById('transGridView').style.display = mode === 'grid' ? '' : 'none';
    document.getElementById('btnViewList').className = 'btn btn-sm ' + (mode === 'list' ? 'btn-primary' : 'btn-secondary');
    document.getElementById('btnViewGrid').className = 'btn btn-sm ' + (mode === 'grid' ? 'btn-primary' : 'btn-secondary');
}

function transSlotChange(i, input) {
    if (!input.files || !input.files[0]) return;
    transSlots[i].file    = input.files[0];
    transSlots[i].removed = false;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('trans_slot_' + i).innerHTML =
            `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover">`;
        document.getElementById('trans_quit_' + i).style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
}

function transSlotRemove(i) {
    const labels = ['Foto 1','Foto 2','Foto 3','Foto 4'];
    transSlots[i].file             = null;
    transSlots[i].removed          = true;
    transSlots[i].existingId       = null;
    transSlots[i].existingFilename = null;
    document.getElementById('trans_keep_' + i).value = '';
    document.getElementById('trans_file_' + i).value = '';
    document.getElementById('trans_slot_' + i).innerHTML =
        `<span style="font-size:.65rem;color:var(--text-secondary);text-align:center;padding:4px">${labels[i]}<br><i class="fa fa-plus" style="font-size:.9rem;display:block;margin-top:4px"></i></span>`;
    document.getElementById('trans_quit_' + i).style.display = 'none';
}
const previewMobImg  = makePreviewFn('mob_img_preview', 'mob_btn_quitar');
const quitarMobImg   = makeQuitarFn('mob_img_preview', 'mob_imagen', 'mob_btn_quitar');

// ── DASHBOARD ─────────────────────────────────────────────
async function cargarDashboard() {
    try {
        const d = await apiGet(`${BASE}?action=dashboard`);
        document.getElementById('kpiTotal').textContent       = d.kpis.total;
        document.getElementById('kpiAsignados').textContent   = d.kpis.asignados;
        document.getElementById('kpiDisponibles').textContent = d.kpis.disponibles;
        document.getElementById('kpiDañados').textContent     = d.kpis.dañados;
        document.getElementById('kpiVencidos').textContent    = d.kpis.vencidos;

        const tb = document.getElementById('tbodyResumen');
        if (!d.asignadas.length) {
            tb.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Sin asignaciones activas.</td></tr>';
            return;
        }
        tb.innerHTML = d.asignadas.map(r => {
            const vencida = r.vencida == '1' || r.vencida === true;
            return `<tr>
                <td class="text-xs text-muted">${esc(r.codigo)}</td>
                <td>${esc(r.activo_nombre)}</td>
                <td>${esc(r.usuario_nombre)}</td>
                <td>${labelPeriodo(r.periodo_tipo)}</td>
                <td>${formatDate(r.fecha_inicio)}</td>
                <td class="${vencida ? 'text-danger' : ''}">${formatDate(r.fecha_fin)}</td>
                <td>${vencida
                    ? '<span class="badge badge-retrasado">Vencida</span>'
                    : '<span class="badge badge-fabricacion">Activa</span>'}</td>
                <td>
                    <button class="btn btn-outline btn-xs" title="Registrar devolución"
                        style="color:var(--success)" onclick="devolverDesdeResumen('${esc(r.codigo)}')">
                        <i class="fa fa-rotate-left"></i>
                    </button>
                </td>
            </tr>`;
        }).join('');
    } catch(e) { Toast.error('Error al cargar resumen.'); }
}

// ── HERRAMIENTAS ──────────────────────────────────────────
async function cargarActivos() {
    const buscar  = document.getElementById('buscarActivo').value;
    const cat     = document.getElementById('filtroCategoria').value;
    const estado  = document.getElementById('filtroEstado').value;
    const soloD   = document.getElementById('soloDisponibles').checked ? 1 : 0;
    try {
        const d = await apiGet(`${BASE}?action=listar&buscar=${encodeURIComponent(buscar)}&categoria_id=${cat}&estado=${estado}&solo_disponibles=${soloD}`);
        const tb = document.getElementById('tbodyActivos');
        if (!d.activos.length) {
            tb.innerHTML = '<tr><td colspan="10" class="text-center text-muted">Sin resultados.</td></tr>';
            return;
        }
        tb.innerHTML = d.activos.map(r => `<tr>
            <td style="text-align:center">${imgThumb(r.imagen)}</td>
            <td class="text-xs text-muted">${esc(r.codigo)}</td>
            <td>
                <strong style="font-size:.88rem">${esc(r.nombre)}</strong>
                ${r.descripcion ? `<div class="text-xs text-muted">${esc(r.descripcion)}</div>` : ''}
            </td>
            <td>${esc(r.categoria_nombre || '—')}</td>
            <td class="text-xs text-muted">${esc(r.numero_serie || '—')}</td>
            <td>${badgeEstado(r.estado)}</td>
            <td>${badgeAsig(r.estado_asig)}</td>
            <td>${r.asig_usuario ? esc(r.asig_usuario) : '<span class="text-muted">—</span>'}</td>
            <td class="${r.estado_asig === 'vencida' ? 'text-danger' : 'text-muted'} text-xs">
                ${r.fecha_fin ? formatDate(r.fecha_fin) : '—'}
            </td>
            <td style="white-space:nowrap">
                ${r.estado_asig !== 'disponible'
                    ? `<button class="btn btn-outline btn-xs" title="Devolver" style="color:var(--success);margin-right:4px"
                          onclick="devolverActivo(${r.id},'${esc(r.nombre)}')">
                          <i class="fa fa-rotate-left"></i></button>`
                    : `<button class="btn btn-primary btn-xs" title="Asignar" style="margin-right:4px"
                          onclick="abrirModalAsignar(${r.id},'${esc(r.nombre)}')">
                          <i class="fa fa-user-plus"></i></button>`}
                <button class="btn btn-outline btn-xs" title="Editar" onclick="editarActivo(${r.id})">
                    <i class="fa fa-pencil"></i>
                </button>
            </td>
        </tr>`).join('');
    } catch(e) { Toast.error('Error al cargar activos.'); }
}

function abrirModalActivo() {
    document.getElementById('activoId').value        = '';
    document.getElementById('activoNombre').value    = '';
    document.getElementById('activoSerie').value     = '';
    document.getElementById('activoFechaAdq').value  = '';
    document.getElementById('activoEstado').value    = 'bueno';
    document.getElementById('activoDesc').value      = '';
    document.getElementById('activoObs').value       = '';
    document.getElementById('activoCategoria').value = '';
    resetImgPreview('activo_img_preview', 'activo_imagen', 'activo_btn_quitar');
    document.getElementById('modalActivoTitulo').textContent = 'Nueva Herramienta';
    Modal.open('modalActivo');
}

async function editarActivo(id) {
    try {
        const d = await apiGet(`${BASE}?action=obtener&id=${id}`);
        const a = d.activo;
        document.getElementById('activoId').value           = a.id;
        document.getElementById('activoNombre').value       = a.nombre;
        document.getElementById('activoCategoria').value    = a.categoria_id || '';
        document.getElementById('activoSerie').value        = a.numero_serie || '';
        document.getElementById('activoFechaAdq').value     = a.fecha_adquisicion || '';
        document.getElementById('activoEstado').value       = a.estado;
        document.getElementById('activoDesc').value         = a.descripcion || '';
        document.getElementById('activoObs').value          = a.observaciones || '';
        loadImgPreview('activo_img_preview', 'activo_btn_quitar', a.imagen);
        document.getElementById('modalActivoTitulo').textContent = 'Editar — ' + a.nombre;
        Modal.open('modalActivo');
    } catch(e) { Toast.error('Error al cargar datos.'); }
}

async function guardarActivo() {
    const nombre = document.getElementById('activoNombre').value.trim();
    if (!nombre) { Toast.warning('El nombre es obligatorio.'); return; }
    const fd = buildFormData('guardar', {
        id:                document.getElementById('activoId').value,
        nombre,
        descripcion:       document.getElementById('activoDesc').value,
        categoria_id:      document.getElementById('activoCategoria').value,
        numero_serie:      document.getElementById('activoSerie').value,
        fecha_adquisicion: document.getElementById('activoFechaAdq').value,
        estado:            document.getElementById('activoEstado').value,
        observaciones:     document.getElementById('activoObs').value,
    }, 'activo_imagen', 'activo_img_preview');
    try {
        const resp = await fetch(BASE, { method: 'POST', body: fd });
        const r = await resp.json();
        if (r.ok) {
            Toast.success('Herramienta guardada.');
            Modal.close('modalActivo');
            cargarActivos(); cargarDashboard();
        } else Toast.error(r.error || 'Error al guardar.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

// ── ASIGNACIONES ──────────────────────────────────────────
function abrirModalAsignar(activoId, activoNombre) {
    document.getElementById('asigActivoId').value     = activoId;
    document.getElementById('asigActivoNombre').value = activoNombre;
    document.getElementById('asigUsuario').value      = '';
    document.getElementById('asigPeriodo').value      = 'dia';
    document.getElementById('asigFechaInicio').value  = new Date().toISOString().slice(0, 10);
    document.getElementById('asigObs').value          = '';
    actualizarFechaFin();
    Modal.open('modalAsignar');
}

function actualizarFechaFin() {
    const inicio  = document.getElementById('asigFechaInicio').value;
    const periodo = document.getElementById('asigPeriodo').value;
    if (!inicio) return;
    document.getElementById('asigFechaFin').value = formatDate(calcFechaFin(inicio, periodo));
}

async function guardarAsignacion() {
    const activoId  = document.getElementById('asigActivoId').value;
    const usuarioId = document.getElementById('asigUsuario').value;
    const periodo   = document.getElementById('asigPeriodo').value;
    const fechaIni  = document.getElementById('asigFechaInicio').value;
    if (!usuarioId) { Toast.warning('Seleccione un responsable.'); return; }
    try {
        const r = await apiPost(BASE, {
            action: 'asignar', activo_id: activoId, usuario_id: usuarioId,
            periodo_tipo: periodo, fecha_inicio: fechaIni,
            observaciones: document.getElementById('asigObs').value,
        });
        if (r.ok) {
            Toast.success('Herramienta asignada.');
            Modal.close('modalAsignar');
            cargarActivos(); cargarDashboard(); cargarHistorial();
        } else Toast.error(r.error || 'Error al asignar.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

async function devolverActivo(activoId, nombre) {
    if (!confirm(`¿Registrar devolución de "${nombre}"?`)) return;
    try {
        const r = await apiPost(BASE, { action: 'devolver', activo_id: activoId });
        if (r.ok) {
            Toast.success('Devolución registrada.');
            cargarActivos(); cargarDashboard(); cargarHistorial();
        } else Toast.error(r.error || 'Error.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

async function devolverDesdeResumen(codigo) {
    try {
        const d = await apiGet(`${BASE}?action=listar&buscar=${encodeURIComponent(codigo)}`);
        if (d.activos && d.activos[0]) devolverActivo(d.activos[0].id, d.activos[0].nombre);
    } catch(e) { Toast.error('Error.'); }
}

// ── TRANSPORTE ────────────────────────────────────────────
async function cargarTransporte() {
    const q      = document.getElementById('buscarTrans').value;
    const estado = document.getElementById('filtroTransEstado').value;
    try {
        const d = await apiGet(`${BASE}?action=trans_listar&q=${encodeURIComponent(q)}&estado=${estado}`);
        const tb    = document.getElementById('tbodyTransporte');
        const grid  = document.getElementById('transGridCards');
        if (!d.transporte.length) {
            tb.innerHTML   = '<tr><td colspan="9" class="text-center text-muted">Sin vehículos registrados.</td></tr>';
            grid.innerHTML = '<p class="text-center text-muted" style="padding:32px;grid-column:1/-1">Sin vehículos registrados.</p>';
            return;
        }
        // List rows
        tb.innerHTML = d.transporte.map(r => `<tr>
            <td style="text-align:center">${imgThumb(r.imagen)}</td>
            <td class="text-xs text-muted">${esc(r.codigo)}</td>
            <td>
                <strong style="font-size:.88rem">${esc(r.nombre)}</strong>
                ${r.descripcion ? `<div class="text-xs text-muted">${esc(r.descripcion)}</div>` : ''}
            </td>
            <td><strong>${esc(r.placa || '—')}</strong></td>
            <td class="text-xs">${[r.marca, r.modelo].filter(Boolean).map(esc).join(' / ') || '—'}</td>
            <td class="text-xs text-muted">${r.anio || '—'}</td>
            <td class="text-xs text-muted">${esc(r.color || '—')}</td>
            <td>${badgeEstadoTrans(r.estado)}</td>
            <td style="white-space:nowrap">
                <button class="btn btn-outline btn-xs" title="Editar" onclick="editarTransporte(${r.id})">
                    <i class="fa fa-pencil"></i>
                </button>
                <button class="btn btn-outline btn-xs" title="Eliminar" style="color:var(--danger)" onclick="eliminarTransporte(${r.id},'${esc(r.nombre)}')">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>`).join('');
        // Grid cards
        grid.innerHTML = d.transporte.map(r => `
            <div class="card" style="overflow:hidden;padding:0">
                <div style="height:140px;background:var(--bg-muted);display:flex;align-items:center;justify-content:center;overflow:hidden">
                    ${r.imagen
                        ? `<img src="${IMG_BASE}${r.imagen}" style="width:100%;height:100%;object-fit:cover">`
                        : `<i class="fa fa-truck" style="font-size:2.5rem;color:var(--text-secondary);opacity:.35"></i>`}
                </div>
                <div style="padding:10px 12px">
                    <div style="font-weight:700;font-size:.88rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${esc(r.nombre)}</div>
                    <div style="font-size:.73rem;color:var(--text-secondary);margin-top:2px">${esc(r.placa || '—')} · ${[r.marca,r.modelo].filter(Boolean).map(esc).join(' ') || '—'}</div>
                    <div style="margin-top:8px;display:flex;justify-content:space-between;align-items:center">
                        ${badgeEstadoTrans(r.estado)}
                        <div style="display:flex;gap:4px">
                            <button class="btn btn-outline btn-xs" onclick="editarTransporte(${r.id})" title="Editar">
                                <i class="fa fa-pencil"></i>
                            </button>
                            <button class="btn btn-outline btn-xs" style="color:var(--danger)" onclick="eliminarTransporte(${r.id},'${esc(r.nombre)}')" title="Eliminar">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>`).join('');
    } catch(e) { Toast.error('Error al cargar transporte.'); }
}

function abrirModalTransporte() {
    document.getElementById('transId').value      = '';
    document.getElementById('transNombre').value  = '';
    document.getElementById('transPlaca').value   = '';
    document.getElementById('transMarca').value   = '';
    document.getElementById('transModelo').value  = '';
    document.getElementById('transAnio').value    = '';
    document.getElementById('transColor').value   = '';
    document.getElementById('transEstado').value  = 'operativo';
    document.getElementById('transDesc').value    = '';
    document.getElementById('transObs').value     = '';
    transSlots = Array.from({length:4}, () => ({existingId:null, existingFilename:null, file:null, removed:false}));
    const labels = ['Foto 1','Foto 2','Foto 3','Foto 4'];
    for (let i = 0; i < 4; i++) {
        document.getElementById('trans_keep_' + i).value = '';
        document.getElementById('trans_file_' + i).value = '';
        document.getElementById('trans_quit_' + i).style.display = 'none';
        document.getElementById('trans_slot_' + i).innerHTML =
            `<span style="font-size:.65rem;color:var(--text-secondary);text-align:center;padding:4px">${labels[i]}<br><i class="fa fa-plus" style="font-size:.9rem;display:block;margin-top:4px"></i></span>`;
    }
    document.getElementById('modalTransTitulo').textContent = 'Nuevo Vehículo';
    Modal.open('modalTransporte');
}

async function editarTransporte(id) {
    try {
        const d = await apiGet(`${BASE}?action=trans_obtener&id=${id}`);
        const v = d.vehiculo;
        document.getElementById('transId').value      = v.id;
        document.getElementById('transNombre').value  = v.nombre;
        document.getElementById('transPlaca').value   = v.placa   || '';
        document.getElementById('transMarca').value   = v.marca   || '';
        document.getElementById('transModelo').value  = v.modelo  || '';
        document.getElementById('transAnio').value    = v.anio    || '';
        document.getElementById('transColor').value   = v.color   || '';
        document.getElementById('transEstado').value  = v.estado;
        document.getElementById('transDesc').value    = v.descripcion   || '';
        document.getElementById('transObs').value     = v.observaciones || '';
        // Load image slots
        transSlots = Array.from({length:4}, () => ({existingId:null, existingFilename:null, file:null, removed:false}));
        const labels = ['Foto 1','Foto 2','Foto 3','Foto 4'];
        for (let i = 0; i < 4; i++) {
            const img = v.imagenes && v.imagenes[i];
            document.getElementById('trans_file_' + i).value = '';
            document.getElementById('trans_quit_' + i).style.display = img ? 'block' : 'none';
            if (img) {
                transSlots[i].existingId       = img.id;
                transSlots[i].existingFilename = img.filename;
                document.getElementById('trans_keep_' + i).value = img.id;
                document.getElementById('trans_slot_' + i).innerHTML =
                    `<img src="${IMG_BASE}${img.filename}" style="width:100%;height:100%;object-fit:cover">`;
            } else {
                document.getElementById('trans_keep_' + i).value = '';
                document.getElementById('trans_slot_' + i).innerHTML =
                    `<span style="font-size:.65rem;color:var(--text-secondary);text-align:center;padding:4px">${labels[i]}<br><i class="fa fa-plus" style="font-size:.9rem;display:block;margin-top:4px"></i></span>`;
            }
        }
        document.getElementById('modalTransTitulo').textContent = 'Editar — ' + v.nombre;
        Modal.open('modalTransporte');
    } catch(e) { Toast.error('Error al cargar datos.'); }
}

async function guardarTransporte() {
    const nombre = document.getElementById('transNombre').value.trim();
    if (!nombre) { Toast.warning('El nombre es obligatorio.'); return; }
    const fd = new FormData();
    fd.append('action',        'trans_guardar');
    fd.append('id',            document.getElementById('transId').value);
    fd.append('nombre',        nombre);
    fd.append('placa',         document.getElementById('transPlaca').value);
    fd.append('marca',         document.getElementById('transMarca').value);
    fd.append('modelo',        document.getElementById('transModelo').value);
    fd.append('anio',          document.getElementById('transAnio').value);
    fd.append('color',         document.getElementById('transColor').value);
    fd.append('estado',        document.getElementById('transEstado').value);
    fd.append('descripcion',   document.getElementById('transDesc').value);
    fd.append('observaciones', document.getElementById('transObs').value);
    for (let i = 0; i < 4; i++) {
        const s = transSlots[i];
        if (s.file) {
            fd.append('imagen_' + i, s.file);
        } else if (s.removed) {
            fd.append('img_remove_' + i, '1');
        } else if (s.existingId) {
            fd.append('img_keep_' + i, s.existingId);
        }
    }
    try {
        const resp = await fetch(BASE, { method: 'POST', body: fd });
        const r = await resp.json();
        if (r.ok) {
            Toast.success('Vehículo guardado.');
            Modal.close('modalTransporte');
            cargarTransporte();
        } else Toast.error(r.error || 'Error al guardar.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

async function eliminarTransporte(id, nombre) {
    if (!confirm(`¿Eliminar el vehículo "${nombre}"? Esta acción no se puede deshacer.`)) return;
    try {
        const r = await apiPost(BASE, { action: 'trans_eliminar', id });
        if (r.ok) { Toast.success('Vehículo eliminado.'); cargarTransporte(); }
        else Toast.error(r.error || 'Error al eliminar.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

// ── MOBILIARIA Y EQUIPOS ──────────────────────────────────
async function cargarMobiliaria() {
    const q      = document.getElementById('buscarMob').value;
    const tipo   = document.getElementById('filtroMobTipo').value;
    const estado = document.getElementById('filtroMobEstado').value;
    try {
        const d = await apiGet(`${BASE}?action=mob_listar&q=${encodeURIComponent(q)}&tipo=${tipo}&estado=${estado}`);
        const tb = document.getElementById('tbodyMobiliaria');
        if (!d.items.length) {
            tb.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Sin ítems registrados.</td></tr>';
            return;
        }
        tb.innerHTML = d.items.map(r => `<tr>
            <td style="text-align:center">${imgThumb(r.imagen)}</td>
            <td class="text-xs text-muted">${esc(r.codigo)}</td>
            <td>
                <strong style="font-size:.88rem">${esc(r.nombre)}</strong>
                ${r.descripcion ? `<div class="text-xs text-muted">${esc(r.descripcion)}</div>` : ''}
            </td>
            <td><span class="badge ${r.tipo === 'mobiliario' ? 'badge-normal' : 'badge-primary'}" style="font-size:.7rem">${r.tipo === 'mobiliario' ? 'Mobiliario' : 'Equipo'}</span></td>
            <td class="text-xs text-muted">${esc(r.ubicacion || '—')}</td>
            <td class="text-xs">${[r.marca, r.modelo].filter(Boolean).map(esc).join(' / ') || '—'}</td>
            <td>${badgeEstado(r.estado)}</td>
            <td style="white-space:nowrap">
                <button class="btn btn-outline btn-xs" title="Editar" onclick="editarMobiliaria(${r.id})">
                    <i class="fa fa-pencil"></i>
                </button>
            </td>
        </tr>`).join('');
    } catch(e) { Toast.error('Error al cargar mobiliaria.'); }
}

function abrirModalMobiliaria() {
    document.getElementById('mobId').value        = '';
    document.getElementById('mobNombre').value    = '';
    document.getElementById('mobTipo').value      = 'equipo';
    document.getElementById('mobUbicacion').value = '';
    document.getElementById('mobMarca').value     = '';
    document.getElementById('mobModelo').value    = '';
    document.getElementById('mobNumSerie').value  = '';
    document.getElementById('mobNumInv').value    = '';
    document.getElementById('mobFechaAdq').value  = '';
    document.getElementById('mobEstado').value    = 'bueno';
    document.getElementById('mobDesc').value      = '';
    document.getElementById('mobObs').value       = '';
    resetImgPreview('mob_img_preview', 'mob_imagen', 'mob_btn_quitar');
    document.getElementById('modalMobTitulo').textContent = 'Nuevo Ítem';
    Modal.open('modalMobiliaria');
}

async function editarMobiliaria(id) {
    try {
        const d = await apiGet(`${BASE}?action=mob_obtener&id=${id}`);
        const m = d.item;
        document.getElementById('mobId').value        = m.id;
        document.getElementById('mobNombre').value    = m.nombre;
        document.getElementById('mobTipo').value      = m.tipo;
        document.getElementById('mobUbicacion').value = m.ubicacion       || '';
        document.getElementById('mobMarca').value     = m.marca           || '';
        document.getElementById('mobModelo').value    = m.modelo          || '';
        document.getElementById('mobNumSerie').value  = m.num_serie       || '';
        document.getElementById('mobNumInv').value    = m.num_inventario  || '';
        document.getElementById('mobFechaAdq').value  = m.fecha_adquisicion || '';
        document.getElementById('mobEstado').value    = m.estado;
        document.getElementById('mobDesc').value      = m.descripcion     || '';
        document.getElementById('mobObs').value       = m.observaciones   || '';
        loadImgPreview('mob_img_preview', 'mob_btn_quitar', m.imagen);
        document.getElementById('modalMobTitulo').textContent = 'Editar — ' + m.nombre;
        Modal.open('modalMobiliaria');
    } catch(e) { Toast.error('Error al cargar datos.'); }
}

async function guardarMobiliaria() {
    const nombre = document.getElementById('mobNombre').value.trim();
    if (!nombre) { Toast.warning('El nombre es obligatorio.'); return; }
    const fd = buildFormData('mob_guardar', {
        id:                document.getElementById('mobId').value,
        nombre,
        tipo:              document.getElementById('mobTipo').value,
        ubicacion:         document.getElementById('mobUbicacion').value,
        marca:             document.getElementById('mobMarca').value,
        modelo:            document.getElementById('mobModelo').value,
        num_serie:         document.getElementById('mobNumSerie').value,
        num_inventario:    document.getElementById('mobNumInv').value,
        fecha_adquisicion: document.getElementById('mobFechaAdq').value,
        estado:            document.getElementById('mobEstado').value,
        descripcion:       document.getElementById('mobDesc').value,
        observaciones:     document.getElementById('mobObs').value,
    }, 'mob_imagen', 'mob_img_preview');
    try {
        const resp = await fetch(BASE, { method: 'POST', body: fd });
        const r = await resp.json();
        if (r.ok) {
            Toast.success('Ítem guardado.');
            Modal.close('modalMobiliaria');
            cargarMobiliaria();
        } else Toast.error(r.error || 'Error al guardar.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

// ── HISTORIAL ─────────────────────────────────────────────
async function cargarHistorial() {
    const uid = document.getElementById('filtroHistUsuario').value;
    try {
        const d = await apiGet(`${BASE}?action=historial&usuario_id=${uid}`);
        const tb = document.getElementById('tbodyHistorial');
        if (!d.historial.length) {
            tb.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Sin registros.</td></tr>';
            return;
        }
        tb.innerHTML = d.historial.map(r => `<tr>
            <td>
                <span class="text-xs text-muted">${esc(r.activo_codigo)}</span>
                <div style="font-size:.85rem;font-weight:600">${esc(r.activo_nombre)}</div>
            </td>
            <td>${esc(r.usuario_nombre)}</td>
            <td><span class="badge badge-normal">${labelPeriodo(r.periodo_tipo)}</span></td>
            <td>${formatDate(r.fecha_inicio)}</td>
            <td>${formatDate(r.fecha_fin)}</td>
            <td>
                ${r.devuelto == '1' || r.devuelto === true
                    ? `<span class="badge badge-completado"><i class="fa fa-check"></i> Sí</span>`
                    : (r.fecha_fin < new Date().toISOString().slice(0,10)
                        ? `<span class="badge badge-retrasado">Vencida</span>`
                        : `<span class="badge badge-fabricacion">Activa</span>`)}
            </td>
            <td class="text-xs text-muted">${esc(r.observaciones || '—')}</td>
        </tr>`).join('');
    } catch(e) { Toast.error('Error al cargar historial.'); }
}

// ── CATEGORÍAS ────────────────────────────────────────────
async function cargarCategorias() {
    try {
        const d = await apiGet(`${BASE}?action=categorias_listar`);
        const tb = document.getElementById('tbodyCategorias');
        if (!d.categorias.length) {
            tb.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Sin categorías.</td></tr>';
            return;
        }
        tb.innerHTML = d.categorias.map(c => `<tr>
            <td><strong>${esc(c.nombre)}</strong></td>
            <td class="text-muted text-sm">${esc(c.descripcion || '—')}</td>
            <td>${c.total_activos}</td>
            <td>
                <button class="btn btn-outline btn-xs" onclick="editarCategoria(${c.id},'${esc(c.nombre)}','${esc(c.descripcion||'')}')">
                    <i class="fa fa-pencil"></i>
                </button>
                <button class="btn btn-outline btn-xs" onclick="eliminarCategoria(${c.id})" style="color:var(--danger)">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>`).join('');
    } catch(e) { Toast.error('Error al cargar categorías.'); }
}

function abrirModalCategoria() {
    document.getElementById('catId').value      = '';
    document.getElementById('catNombre').value  = '';
    document.getElementById('catDesc').value    = '';
    document.getElementById('modalCategoriaTitulo').textContent = 'Nueva Categoría';
    Modal.open('modalCategoria');
}

function editarCategoria(id, nombre, desc) {
    document.getElementById('catId').value      = id;
    document.getElementById('catNombre').value  = nombre;
    document.getElementById('catDesc').value    = desc;
    document.getElementById('modalCategoriaTitulo').textContent = 'Editar Categoría';
    Modal.open('modalCategoria');
}

async function guardarCategoria() {
    const nombre = document.getElementById('catNombre').value.trim();
    if (!nombre) { Toast.warning('El nombre es obligatorio.'); return; }
    try {
        const r = await apiPost(BASE, {
            action: 'categoria_guardar', id: document.getElementById('catId').value,
            nombre, descripcion: document.getElementById('catDesc').value,
        });
        if (r.ok) { Toast.success('Categoría guardada.'); Modal.close('modalCategoria'); cargarCategorias(); }
        else Toast.error(r.error || 'Error.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

async function eliminarCategoria(id) {
    if (!confirm('¿Eliminar esta categoría?')) return;
    try {
        const r = await apiPost(BASE, { action: 'categoria_eliminar', id });
        if (r.ok) { Toast.success('Categoría eliminada.'); cargarCategorias(); }
        else Toast.error(r.error || 'Tiene activos asociados.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

// ── COMBOS ────────────────────────────────────────────────
async function cargarCombos() {
    try {
        const d = await apiGet(`${BASE}?action=combos`);
        const optsCat = '<option value="">— Sin categoría —</option>' +
            d.categorias.map(c => `<option value="${c.id}">${esc(c.nombre)}</option>`).join('');
        document.getElementById('activoCategoria').innerHTML = optsCat;
        document.getElementById('filtroCategoria').innerHTML =
            '<option value="">Todas las categorías</option>' +
            d.categorias.map(c => `<option value="${c.id}">${esc(c.nombre)}</option>`).join('');
        const optsUser = '<option value="">— Seleccionar —</option>' +
            d.usuarios.map(u => `<option value="${u.id}">${esc(u.nombre)}</option>`).join('');
        document.getElementById('asigUsuario').innerHTML       = optsUser;
        document.getElementById('filtroHistUsuario').innerHTML =
            '<option value="">Todos los responsables</option>' +
            d.usuarios.map(u => `<option value="${u.id}">${esc(u.nombre)}</option>`).join('');
    } catch(e) {}
}

// ── Init ──────────────────────────────────────────────────
initTabs();
cargarCombos().then(() => {
    cargarDashboard();
    cargarActivos();
    cargarHistorial();
    cargarCategorias();
    cargarTransporte();
    cargarMobiliaria();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

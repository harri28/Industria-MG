<?php
$page_title      = 'Ventas';
$page_breadcrumb = '<span>Operaciones</span> <span>></span> Ventas';
require_once __DIR__ . '/../../includes/header.php';
$db = getDB();
$tecnicos = $db->query("SELECT id, nombre FROM usuarios WHERE activo ORDER BY nombre")->fetchAll();
$empresaVenta = $db->query("SELECT razon_social, nombre_comercial, ruc, direccion_fiscal, telefono FROM empresa_emisora WHERE activo ORDER BY id LIMIT 1")->fetch() ?: [];
?>

<link rel="stylesheet" href="../../assets/vendor/select2/select2.min.css">
<link rel="stylesheet" href="../../assets/vendor/datatables/dataTables.bootstrap5.min.css">
<style>
.venta-cliente-wrap .select2-container { width:100% !important; }
.venta-cliente-wrap .select2-container--default .select2-selection--single {
    height:40px;border:1px solid var(--border);border-radius:8px;display:flex;align-items:center;
}
.venta-cliente-wrap .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height:38px;padding-left:14px;font-size:.9rem;color:var(--text-primary);
}
.venta-cliente-wrap .select2-container--default .select2-selection--single .select2-selection__arrow { height:38px; }
.select2-dropdown { border-color:var(--border);border-radius:8px;overflow:hidden; }
.venta-stat-grid { display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:14px; }
.venta-stat-card { background:#fff;border:1px solid var(--border);border-radius:12px;padding:14px;box-shadow:0 1px 3px rgba(15,23,42,.06); }
.venta-stat-label { color:var(--text-muted);font-size:.76rem;margin-bottom:5px; }
.venta-stat-value { font-size:1.35rem;font-weight:800;color:var(--text-primary); }
.venta-detail-grid { display:grid;grid-template-columns:1.1fr .9fr;gap:14px;margin-bottom:14px; }
.venta-subtabs { display:flex;gap:8px;align-items:center;margin:0 0 14px;border-bottom:1px solid var(--border); }
.venta-subtab { padding:10px 14px;cursor:pointer;color:var(--text-muted);font-weight:700;font-size:.88rem;border-bottom:2px solid transparent; }
.venta-subtab.active { color:var(--primary);border-color:var(--primary); }
.venta-subcontent { display:none; }
.venta-subcontent.active { display:block; }
.dataTables_wrapper { padding:12px 14px 14px;width:100%;box-sizing:border-box; }
.dataTables_wrapper .row { display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;margin:0 0 10px;width:100%; }
.dataTables_wrapper .row > * { flex:0 0 auto;max-width:100%; }
.dataTables_filter input,
.dataTables_length select { border:1px solid var(--border);border-radius:8px;padding:7px 10px;background:#fff;color:var(--text-primary); }
.dataTables_filter label,
.dataTables_length label { color:var(--text-muted);font-size:.82rem; }
.dataTables_length,
.dataTables_filter,
.dataTables_info,
.dataTables_paginate { margin:0 !important; }
.dataTables_info { color:var(--text-muted);font-size:.82rem; }
.dataTables_paginate { display:flex !important;justify-content:flex-end;align-items:center;gap:6px; }
.dataTables_wrapper .dataTables_paginate ul.pagination {
    list-style:none !important;
    padding-left:0 !important;
    margin:0 !important;
    display:flex !important;
    flex-direction:row !important;
    gap:6px;
    align-items:center;
    justify-content:flex-end;
    flex-wrap:wrap;
}
.dataTables_wrapper .dataTables_paginate ul.pagination li {
    list-style:none !important;
    margin:0 !important;
    padding:0 !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button {
    margin:0 !important;
    padding:0 !important;
    border:0 !important;
    background:transparent !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button::before,
.dataTables_wrapper .dataTables_paginate .paginate_button::after,
.dataTables_wrapper .dataTables_paginate ul.pagination li::before {
    content:none !important;
    display:none !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button a,
.dataTables_wrapper .dataTables_paginate .page-link {
    border:1px solid var(--border) !important;
    border-radius:8px !important;
    background:#fff !important;
    color:var(--text-primary) !important;
    padding:6px 11px !important;
    min-width:34px;
    line-height:1.15 !important;
    font-size:.82rem !important;
    text-decoration:none !important;
    display:inline-flex !important;
    align-items:center;
    justify-content:center;
    box-shadow:none !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.active a,
.dataTables_wrapper .dataTables_paginate .paginate_button.current a,
.dataTables_wrapper .dataTables_paginate .page-item.active .page-link,
.dataTables_wrapper .dataTables_paginate .paginate_button a:hover,
.dataTables_wrapper .dataTables_paginate .page-link:hover {
    background:var(--primary) !important;
    color:#fff !important;
    border-color:var(--primary) !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.disabled a,
.dataTables_wrapper .dataTables_paginate .page-item.disabled .page-link {
    opacity:.5;
    cursor:not-allowed !important;
    background:#fff !important;
    color:var(--text-muted) !important;
    border-color:var(--border) !important;
}
@media (max-width: 900px) {
    .venta-stat-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
    .venta-detail-grid { grid-template-columns:1fr; }
}
</style>

<!-- TABS -->
<div class="tabs">
    <div class="tab active" data-group="vnt" data-target="tabVenta"><i class="fa fa-cart-shopping"></i> Realizar Venta</div>
    <div class="tab" data-group="vnt" data-target="tabListadoVentas"><i class="fa fa-clock-rotate-left"></i> Listado de Ventas</div>
    <div class="tab" data-group="vnt" data-target="tabCotizaciones"><i class="fa fa-file-lines"></i> Cotizaciones</div>
    <div class="tab" data-group="vnt" data-target="tabMaquinarias"><i class="fa fa-gears"></i> Maquinarias</div>
    <div class="tab" data-group="vnt" data-target="tabServicio"><i class="fa fa-screwdriver-wrench"></i> Servicio Tecnico</div>
    <div class="tab" data-group="vnt" data-target="tabOrdenes"><i class="fa fa-receipt"></i> Ordenes de Venta</div>
</div>

<!-- ============================================================
     TAB: REALIZAR VENTA
     ============================================================ -->
<div class="tab-content active" data-group="vnt" id="tabVenta">
    <div style="display:grid;grid-template-columns:1fr 340px;gap:16px;align-items:start">

        <!-- Izquierda: busqueda + carrito -->
        <div>
            <div class="card">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-search"></i> Buscar Producto de Inventario</span>
                </div>
                <div style="padding:14px 16px">
                    <div class="search-box" style="margin-bottom:10px;width:100%;max-width:none">
                        <span class="icon"><i class="fa fa-search"></i></span>
                        <input type="text" id="ventaSearch" class="form-control"
                               style="width:100%;max-width:none"
                               placeholder="Codigo o nombre del producto..." oninput="filtrarProductosVenta()">
                    </div>
                    <div id="ventaResultados" style="max-height:260px;overflow-y:auto;border:1px solid var(--border);border-radius:6px;background:#fff">
                        <div class="text-center text-muted" style="padding:14px;font-size:.85rem">Cargando productos...</div>
                    </div>
                </div>
            </div>

            <div class="card" style="margin-top:14px">
                <div class="card-header">
                    <span class="card-title"><i class="fa fa-cart-shopping"></i> Carrito</span>
                    <div class="card-actions">
                        <button class="btn btn-outline btn-sm" onclick="limpiarCarrito()"><i class="fa fa-trash"></i> Limpiar</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead><tr>
                            <th>Producto</th>
                            <th class="text-right" style="width:70px">Stock</th>
                            <th class="text-right" style="width:90px">Cant.</th>
                            <th class="text-right" style="width:110px">P. Unit. (S/)</th>
                            <th class="text-right" style="width:80px">Dto. (%)</th>
                            <th class="text-right" style="width:110px">Subtotal</th>
                            <th style="width:40px"></th>
                        </tr></thead>
                        <tbody id="ventaCarritoBody">
                            <tr><td colspan="7" class="text-center text-muted" style="padding:20px">Sin productos agregados</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Derecha: resumen y confirmar -->
        <div class="card" style="position:sticky;top:16px">
            <div class="card-header">
                <span class="card-title"><i class="fa fa-receipt"></i> Resumen de Venta</span>
            </div>
            <div style="padding:16px">
                <div class="form-row" style="display:block">
                    <div class="form-group">
                        <label class="form-label">Comprobante</label>
                        <select class="form-control" id="ventaTipoComprobante" onchange="onVentaTipoComprobanteChange()">
                            <option value="boleta">Boleta electronica</option>
                            <option value="factura">Factura electronica</option>
                            <option value="nota_venta">Nota de venta</option>
                        </select>
                    </div>
                    <div class="form-group" style="display:none">
                        <label class="form-label">Serie</label>
                        <select class="form-control" id="ventaSerie"></select>
                    </div>
                </div>
                <div id="ventaComprobanteInfo" class="text-xs text-muted" style="background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:9px 10px;margin:2px 0 12px">
                    Se generara el correlativo automaticamente.
                </div>
                <div class="form-group venta-cliente-wrap">
                    <label class="form-label">Cliente</label>
                    <select class="form-control" id="ventaCliente">
                        <option value="">Sin cliente / clientes varios</option>
                    </select>
                </div>
                <div class="form-group" style="margin-top:12px">
                    <label class="form-label">Observaciones</label>
                    <textarea class="form-control" id="ventaObs" rows="2" placeholder="Notas de la venta..."></textarea>
                </div>
                <hr style="margin:14px 0;border:none;border-top:1px solid var(--border)">
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:.9rem">
                    <span class="text-muted">Subtotal</span>
                    <span id="ventaSubtotal" class="font-semibold">S/ 0.00</span>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:12px;font-size:.9rem">
                    <span class="text-muted">IGV (18%)</span>
                    <span id="ventaIgv" class="font-semibold">S/ 0.00</span>
                </div>
                <div style="display:flex;justify-content:space-between;background:var(--primary-light,#eff6ff);padding:12px;border-radius:8px;margin-bottom:16px">
                    <span class="font-bold" style="color:var(--primary)">TOTAL</span>
                    <span id="ventaTotal" class="font-bold" style="color:var(--primary);font-size:1.15rem">S/ 0.00</span>
                </div>
                <button class="btn btn-primary" style="width:100%;padding:11px;font-size:.95rem;font-weight:600" onclick="abrirModalCobroVenta()">
                    <i class="fa fa-circle-check"></i> Continuar al cobro
                </button>
            </div>
        </div>

    </div>
</div>

<!-- ============================================================
     TAB: LISTADO DE VENTAS
     ============================================================ -->
<div class="tab-content" data-group="vnt" id="tabListadoVentas">
    <div class="venta-stat-grid" id="ventasStats">
        <div class="venta-stat-card"><div class="venta-stat-label">Ventas hoy</div><div class="venta-stat-value">...</div></div>
        <div class="venta-stat-card"><div class="venta-stat-label">Ingresos hoy</div><div class="venta-stat-value">...</div></div>
        <div class="venta-stat-card"><div class="venta-stat-label">Ticket promedio</div><div class="venta-stat-value">...</div></div>
        <div class="venta-stat-card"><div class="venta-stat-label">Anuladas hoy</div><div class="venta-stat-value">...</div></div>
    </div>

    <div class="card" style="margin-bottom:14px">
        <div style="padding:14px 16px;display:grid;grid-template-columns:repeat(5,minmax(130px,1fr)) auto;gap:12px;align-items:end">
            <div class="form-group" style="margin:0">
                <label class="form-label">Desde</label>
                <input type="date" class="form-control" id="ventasDesde" value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Hasta</label>
                <input type="date" class="form-control" id="ventasHasta" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Estado</label>
                <select class="form-control" id="ventasEstado">
                    <option value="">Todos</option>
                    <option value="cobrada">Cobrada</option>
                    <option value="cancelada">Cancelada</option>
                    <option value="confirmada">Confirmada</option>
                    <option value="facturada">Facturada</option>
                </select>
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Comprobante</label>
                <select class="form-control" id="ventasTipo">
                    <option value="">Todos</option>
                    <option value="boleta">Boleta</option>
                    <option value="factura">Factura</option>
                    <option value="nota_venta">Nota de venta</option>
                </select>
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">Buscar</label>
                <input type="text" class="form-control" id="ventasBuscar" placeholder="Venta, cliente, doc...">
            </div>
            <button class="btn btn-primary" onclick="cargarVentasHistorial()"><i class="fa fa-search"></i> Buscar</button>
        </div>
    </div>

    <div class="venta-subtabs">
        <div class="venta-subtab active" data-venta-subtab="ventas" onclick="activarSubtabVentas('ventas')">
            <i class="fa fa-receipt"></i> Ventas
        </div>
        <div class="venta-subtab" data-venta-subtab="notas" onclick="activarSubtabVentas('notas')">
            <i class="fa fa-file-circle-minus"></i> Notas de credito
        </div>
    </div>

    <div class="venta-subcontent active" id="subtabVentasLista">
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fa fa-list-check"></i> Ventas registradas</span>
            <span class="text-sm text-muted" id="ventasResultCount">Cargando...</span>
        </div>
        <div class="table-responsive">
            <table id="tablaVentasHistorial"><thead><tr>
                <th>Venta</th><th>Fecha / Hora</th><th>Cliente</th><th>Comprobante</th>
                <th>Pago</th><th>Items</th><th>Estado</th><th>XML</th><th>CDR</th>
                <th class="text-right">Total</th><th>Acciones</th>
            </tr></thead>
            <tbody id="ventasHistorialBody">
                <tr><td colspan="11" class="text-center"><div class="loading"><div class="spinner"></div></div></td></tr>
            </tbody></table>
        </div>
    </div>
    </div>

    <div class="venta-subcontent" id="subtabNotasCredito">
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fa fa-file-circle-minus"></i> Notas de credito emitidas</span>
            <span class="text-sm text-muted" id="notasCreditoResultCount">Cargando...</span>
        </div>
        <div class="table-responsive">
            <table id="tablaNotasCredito"><thead><tr>
                <th>Nota</th><th>Fecha / Hora</th><th>Cliente</th><th>Comprobante origen</th>
                <th>Motivo</th><th>SUNAT</th><th>XML</th><th>CDR</th><th class="text-right">Total</th><th>Acciones</th>
            </tr></thead>
            <tbody id="notasCreditoBody">
                <tr><td colspan="10" class="text-center"><div class="loading"><div class="spinner"></div></div></td></tr>
            </tbody></table>
        </div>
    </div>
    </div>
</div>

<!-- ============================================================
     TAB: COTIZACIONES
     ============================================================ -->
<div class="tab-content" data-group="vnt" id="tabCotizaciones">
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fa fa-file-lines"></i> Cotizaciones</span>
            <div class="card-actions">
                <div class="search-box"><span class="icon"><i class="fa fa-search"></i></span>
                    <input type="text" class="form-control" id="buscarCot" placeholder="Numero, cliente..." oninput="cargarCotizaciones()">
                </div>
                <select class="form-control" id="filtroEstadoCot" style="width:140px" onchange="cargarCotizaciones()">
                    <option value="">Todos los estados</option>
                    <option value="borrador">Borrador</option>
                    <option value="enviada">Enviada</option>
                    <option value="aprobada">Aprobada</option>
                    <option value="rechazada">Rechazada</option>
                    <option value="vencida">Vencida</option>
                </select>
                <button class="btn btn-primary btn-sm" onclick="abrirModalCotizacion()"><i class="fa fa-plus"></i> Nueva Cotizacion</button>
            </div>
        </div>
        <div class="table-responsive">
            <table><thead><tr>
                <th>Numero</th><th>Cliente</th><th>Fecha</th><th>Vigencia</th>
                <th>Estado</th><th class="text-right">Subtotal</th><th class="text-right">IGV</th><th class="text-right">Total</th><th>Acciones</th>
            </tr></thead>
            <tbody id="cotizacionesBody"><tr><td colspan="9" class="text-center"><div class="loading"><div class="spinner"></div></div></td></tr></tbody></table>
        </div>
    </div>
</div>

<!-- ============================================================
     TAB: MAQUINARIAS
     ============================================================ -->
<div class="tab-content" data-group="vnt" id="tabMaquinarias">
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fa fa-gears"></i> Catalogo de Maquinarias</span>
            <div class="card-actions">
                <div class="search-box"><span class="icon"><i class="fa fa-search"></i></span>
                    <input type="text" class="form-control" id="buscarMaq" placeholder="Modelo, serie, cliente..." oninput="cargarMaquinarias()">
                </div>
                <button class="btn btn-primary btn-sm" onclick="abrirModalMaquinaria()"><i class="fa fa-plus"></i> Registrar Maquinaria</button>
            </div>
        </div>
        <div class="table-responsive">
            <table><thead><tr>
                <th>Codigo</th><th>Modelo</th><th>Nro. Serie</th><th>Cliente</th>
                <th class="text-right">Costo</th><th class="text-right">Precio venta</th>
                <th>Venta</th><th>Garantia</th><th>Acciones</th>
            </tr></thead>
            <tbody id="maquinariasBody"><tr><td colspan="9" class="text-center"><div class="loading"><div class="spinner"></div></div></td></tr></tbody></table>
        </div>
    </div>
</div>

<!-- ============================================================
     TAB: SERVICIO TECNICO
     ============================================================ -->
<div class="tab-content" data-group="vnt" id="tabServicio">
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fa fa-screwdriver-wrench"></i> Servicio Tecnico</span>
            <div class="card-actions">
                <select class="form-control" id="filtroEstadoSrv" style="width:150px" onchange="cargarServicios()">
                    <option value="">Todos los estados</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="en_proceso">En proceso</option>
                    <option value="completado">Completado</option>
                    <option value="cancelado">Cancelado</option>
                </select>
                <button class="btn btn-primary btn-sm" onclick="abrirModalServicio()"><i class="fa fa-plus"></i> Nuevo Servicio</button>
            </div>
        </div>
        <div class="table-responsive">
            <table><thead><tr>
                <th>Fecha</th><th>Cliente</th><th>Maquinaria</th><th>Tipo</th>
                <th>Descripcion</th><th>Tecnico</th><th>Estado</th><th class="text-right">Costo</th><th>Acciones</th>
            </tr></thead>
            <tbody id="serviciosBody"><tr><td colspan="9" class="text-center"><div class="loading"><div class="spinner"></div></div></td></tr></tbody></table>
        </div>
    </div>
</div>

<!-- ============================================================
     TAB: ORDENES DE VENTA
     ============================================================ -->
<div class="tab-content" data-group="vnt" id="tabOrdenes">
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fa fa-receipt"></i> Ordenes de Venta</span>
            <div class="card-actions">
                <div class="search-box"><span class="icon"><i class="fa fa-search"></i></span>
                    <input type="text" class="form-control" id="buscarOV" placeholder="Numero, cliente, proyecto..." oninput="cargarOrdenes()">
                </div>
                <select class="form-control" id="filtroEstadoOV" style="width:150px" onchange="cargarOrdenes()">
                    <option value="">Todos los estados</option>
                    <option value="borrador">Borrador</option>
                    <option value="confirmada">Confirmada</option>
                    <option value="facturada">Facturada</option>
                    <option value="cobrada">Cobrada</option>
                    <option value="cancelada">Cancelada</option>
                </select>
                <button class="btn btn-primary btn-sm" onclick="abrirModalOrden()"><i class="fa fa-plus"></i> Nueva Orden</button>
            </div>
        </div>
        <div class="table-responsive">
            <table><thead><tr>
                <th>Numero</th><th>Cliente</th><th>Proyecto</th><th>Fecha</th><th>Entrega</th>
                <th>Estado</th><th class="text-right">Total</th><th>Acciones</th>
            </tr></thead>
            <tbody id="ordenesBody"><tr><td colspan="8" class="text-center"><div class="loading"><div class="spinner"></div></div></td></tr></tbody></table>
        </div>
    </div>
</div>

<!-- ============================================================  MODALES  ============================================================ -->

<!-- MODAL: COTIZACION -->
<div class="modal-overlay" id="modalCotizacion">
    <div class="modal modal-xl">
        <div class="modal-header"><span class="modal-title" id="tituloCot">Nueva Cotizacion</span><button class="modal-close"><i class="fa fa-times"></i></button></div>
        <div class="modal-body">
            <input type="hidden" id="cotId">
            <div class="form-row cols-4">
                <div class="form-group" style="grid-column:span 2">
                    <label class="form-label">Cliente</label>
                    <select class="form-control" id="cotCliente"></select>
                </div>
                <div class="form-group"><label class="form-label">Fecha Emision</label><input type="date" class="form-control" id="cotFecha" value="<?= date('Y-m-d') ?>"></div>
                <div class="form-group"><label class="form-label">Vigencia (dias)</label><input type="number" class="form-control" id="cotVigencia" value="30" min="1"></div>
            </div>
            <div class="form-group"><label class="form-label">Observaciones</label><textarea class="form-control" id="cotObs" rows="2"></textarea></div>

            <!-- Lineas de detalle -->
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                <label class="form-label" style="margin:0">Detalle de Items</label>
                <button type="button" class="btn btn-outline btn-sm" onclick="agregarLineaCot()"><i class="fa fa-plus"></i> Agregar linea</button>
            </div>
            <div style="border:1.5px solid var(--border);border-radius:var(--radius-sm);overflow:hidden">
                <table style="margin:0" id="tablaCotDetalle">
                    <thead><tr>
                        <th style="width:40%">Descripcion</th>
                        <th class="text-right" style="width:12%">Cantidad</th>
                        <th class="text-right" style="width:15%">Precio Unit. (S/)</th>
                        <th class="text-right" style="width:10%">Dto. (%)</th>
                        <th class="text-right" style="width:15%">Subtotal</th>
                        <th style="width:8%"></th>
                    </tr></thead>
                    <tbody id="cotDetalleBody"></tbody>
                    <tfoot>
                        <tr style="background:#f8fafc">
                            <td colspan="4" class="text-right font-semibold" style="padding:8px 14px">Subtotal:</td>
                            <td class="text-right font-bold" style="padding:8px 14px" id="cotSubtotal">S/ 0.00</td><td></td>
                        </tr>
                        <tr style="background:#f8fafc">
                            <td colspan="4" class="text-right font-semibold" style="padding:4px 14px">IGV (18%):</td>
                            <td class="text-right font-bold" style="padding:4px 14px" id="cotIgv">S/ 0.00</td><td></td>
                        </tr>
                        <tr style="background:var(--primary-light)">
                            <td colspan="4" class="text-right font-bold" style="padding:8px 14px;color:var(--primary)">TOTAL:</td>
                            <td class="text-right font-bold" style="padding:8px 14px;color:var(--primary);font-size:1rem" id="cotTotal">S/ 0.00</td><td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalCotizacion')">Cancelar</button>
            <button class="btn btn-success" onclick="guardarCotizacion('borrador')"><i class="fa fa-floppy-disk"></i> Guardar borrador</button>
            <button class="btn btn-primary" onclick="guardarCotizacion('enviada')"><i class="fa fa-paper-plane"></i> Guardar y enviar</button>
        </div>
    </div>
</div>

<!-- MODAL: MAQUINARIA -->
<div class="modal-overlay" id="modalMaquinaria">
    <div class="modal">
        <div class="modal-header"><span class="modal-title" id="tituloMaq">Registrar Maquinaria</span><button class="modal-close"><i class="fa fa-times"></i></button></div>
        <div class="modal-body">
            <input type="hidden" id="maqId">
            <div class="form-row cols-2">
                <div class="form-group" style="grid-column:span 2">
                    <label class="form-label">Cliente</label>
                    <select class="form-control" id="maqCliente"></select>
                </div>
                <div class="form-group"><label class="form-label">Codigo Interno</label><input type="text" class="form-control" id="maqCodigo"></div>
                <div class="form-group"><label class="form-label">Modelo *</label><input type="text" class="form-control" id="maqModelo"></div>
                <div class="form-group"><label class="form-label">Nro. de Serie</label><input type="text" class="form-control" id="maqSerie"></div>
                <div class="form-group"><label class="form-label">Costo total (S/)</label><input type="number" step="0.01" min="0" class="form-control" id="maqCosto" value="0.00"></div>
                <div class="form-group"><label class="form-label">Precio venta (S/)</label><input type="number" step="0.01" min="0" class="form-control" id="maqPrecio" value="0.00"></div>
                <div class="form-group"><label class="form-label">Estado venta</label>
                    <select class="form-control" id="maqEstadoVenta">
                        <option value="disponible">Disponible</option>
                        <option value="vendida">Vendida</option>
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Fecha de Venta</label><input type="date" class="form-control" id="maqFechaVenta"></div>
                <div class="form-group" style="grid-column:span 2"><label class="form-label">Garantia (meses)</label>
                    <input type="number" class="form-control" id="maqGarantia" value="12" min="0">
                </div>
                <div class="form-group" style="grid-column:span 2"><label class="form-label">Descripcion</label><textarea class="form-control" id="maqDesc" rows="2"></textarea></div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalMaquinaria')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarMaquinaria()"><i class="fa fa-save"></i> Guardar</button>
        </div>
    </div>
</div>

<!-- MODAL: ORDEN DE VENTA -->
<div class="modal-overlay" id="modalOrden">
    <div class="modal modal-xl">
        <div class="modal-header"><span class="modal-title" id="tituloOrden">Nueva Orden de Venta</span><button class="modal-close"><i class="fa fa-times"></i></button></div>
        <div class="modal-body">
            <input type="hidden" id="ovId">
            <div class="form-row cols-4">
                <div class="form-group" style="grid-column:span 2">
                    <label class="form-label">Cliente</label>
                    <select class="form-control" id="ovCliente" onchange="filtrarProyectosCliente()"></select>
                </div>
                <div class="form-group"><label class="form-label">Fecha Emision</label><input type="date" class="form-control" id="ovFecha" value="<?= date('Y-m-d') ?>"></div>
                <div class="form-group"><label class="form-label">Fecha Entrega</label><input type="date" class="form-control" id="ovEntrega"></div>
            </div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Proyecto vinculado</label>
                    <select class="form-control" id="ovProyecto"></select>
                </div>
                <div class="form-group">
                    <label class="form-label">Cotizacion de origen (opcional)</label>
                    <select class="form-control" id="ovCotizacion"></select>
                </div>
            </div>
            <div class="form-group"><label class="form-label">Observaciones</label><textarea class="form-control" id="ovObs" rows="2"></textarea></div>

            <!-- Items -->
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                <label class="form-label" style="margin:0">Items del pedido</label>
                <button type="button" class="btn btn-outline btn-sm" onclick="agregarItemOrden()"><i class="fa fa-plus"></i> Agregar item</button>
            </div>
            <div style="border:1.5px solid var(--border);border-radius:var(--radius-sm);overflow:hidden">
                <table style="margin:0" id="tablaOVItems">
                    <thead><tr>
                        <th style="width:35%">Producto</th>
                        <th style="width:10%">Unidad</th>
                        <th class="text-right" style="width:10%">Cantidad</th>
                        <th class="text-right" style="width:14%">Precio Unit. (S/)</th>
                        <th class="text-right" style="width:9%">Dto. (%)</th>
                        <th class="text-right" style="width:14%">Subtotal</th>
                        <th style="width:8%"></th>
                    </tr></thead>
                    <tbody id="ovItemsBody"></tbody>
                    <tfoot>
                        <tr style="background:#f8fafc">
                            <td colspan="5" class="text-right font-semibold" style="padding:8px 14px">Subtotal:</td>
                            <td class="text-right font-bold" style="padding:8px 14px" id="ovSubtotal">S/ 0.00</td><td></td>
                        </tr>
                        <tr style="background:#f8fafc">
                            <td colspan="5" class="text-right font-semibold" style="padding:4px 14px">IGV (18%):</td>
                            <td class="text-right font-bold" style="padding:4px 14px" id="ovIgv">S/ 0.00</td><td></td>
                        </tr>
                        <tr style="background:var(--primary-light)">
                            <td colspan="5" class="text-right font-bold" style="padding:8px 14px;color:var(--primary)">TOTAL:</td>
                            <td class="text-right font-bold" style="padding:8px 14px;color:var(--primary);font-size:1rem" id="ovTotal">S/ 0.00</td><td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalOrden')">Cancelar</button>
            <button class="btn btn-success" onclick="guardarOrden('borrador')"><i class="fa fa-floppy-disk"></i> Guardar borrador</button>
            <button class="btn btn-primary" onclick="guardarOrden('confirmada')"><i class="fa fa-circle-check"></i> Confirmar orden</button>
        </div>
    </div>
</div>

<!-- MODAL: SERVICIO TECNICO -->
<div class="modal-overlay" id="modalServicio">
    <div class="modal modal-lg">
        <div class="modal-header"><span class="modal-title" id="tituloSrv">Nuevo Servicio</span><button class="modal-close"><i class="fa fa-times"></i></button></div>
        <div class="modal-body">
            <input type="hidden" id="srvId">
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Maquinaria</label>
                    <select class="form-control" id="srvMaquinaria"></select>
                </div>
                <div class="form-group">
                    <label class="form-label">Tipo</label>
                    <select class="form-control" id="srvTipo">
                        <option value="correctivo">Correctivo</option>
                        <option value="preventivo">Preventivo</option>
                        <option value="garantia">Garantia</option>
                    </select>
                </div>
                <div class="form-group" style="grid-column:span 2"><label class="form-label">Descripcion del problema *</label><textarea class="form-control" id="srvDesc" rows="2"></textarea></div>
                <div class="form-group" style="grid-column:span 2"><label class="form-label">Diagnostico</label><textarea class="form-control" id="srvDiag" rows="2"></textarea></div>
                <div class="form-group" style="grid-column:span 2"><label class="form-label">Solucion aplicada</label><textarea class="form-control" id="srvSol" rows="2"></textarea></div>
                <div class="form-group">
                    <label class="form-label">Tecnico asignado</label>
                    <select class="form-control" id="srvTecnico">
                        <option value="">- Sin asignar -</option>
                        <?php foreach ($tecnicos as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Costo (S/)</label><input type="number" class="form-control" id="srvCosto" value="0" min="0" step="0.01"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalServicio')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarServicio()"><i class="fa fa-save"></i> Guardar</button>
        </div>
    </div>
</div>

<!-- MODAL: COBRO DE VENTA -->
<div class="modal-overlay" id="modalCobroVenta">
    <div class="modal" style="max-width:520px" onclick="event.stopPropagation()" ondblclick="event.stopPropagation()">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-cash-register" style="color:var(--primary)"></i> Cobro de venta</span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body" style="padding:18px 20px">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
                <div class="form-group">
                    <label class="form-label">Comprobante</label>
                    <div id="cobroVentaComprobante" class="form-control" style="background:#f8fafc"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Metodo de pago</label>
                    <select class="form-control" id="cobroVentaMetodo" onchange="calcularVueltoVenta()">
                        <option value="efectivo">Efectivo</option>
                        <option value="yape">Yape</option>
                        <option value="plin">Plin</option>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="transferencia">Transferencia</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Cliente</label>
                <div id="cobroVentaCliente" class="form-control" style="background:#f8fafc;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"></div>
            </div>

            <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:14px;margin:14px 0">
                <div style="display:flex;justify-content:space-between;margin-bottom:6px">
                    <span class="text-muted">Subtotal</span><strong id="cobroVentaSubtotal">S/ 0.00</strong>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;color:#16a34a">
                    <span>Descuento</span><strong id="cobroVentaDescuentoLabel">-S/ 0.00</strong>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px">
                    <span class="text-muted">IGV</span><strong id="cobroVentaIgv">S/ 0.00</strong>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:1.2rem;color:#f97316">
                    <strong>Total</strong><strong id="cobroVentaTotal">S/ 0.00</strong>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Descuento global (S/)</label>
                <input type="number" class="form-control" id="cobroVentaDescuento" min="0" step="0.01" value="0.00" oninput="actualizarResumenCobroVenta()">
            </div>

            <div style="display:flex;gap:10px;align-items:center;margin:10px 0 12px;flex-wrap:wrap">
                <button type="button" class="btn btn-outline btn-sm" id="btnPagoMixtoVenta" onclick="togglePagoMixtoVenta()">
                    <i class="fa fa-layer-group"></i> Pago mixto
                </button>
                <label style="display:flex;align-items:center;gap:8px;font-size:.86rem;cursor:pointer">
                    <input type="checkbox" id="cobroVentaCredito" onchange="toggleCreditoVenta(this.checked)">
                    Pago a credito
                </label>
            </div>

            <div id="panelPagoMixtoVenta" style="display:none;border:1px dashed var(--border);border-radius:10px;padding:10px;margin-bottom:12px">
                <div id="pagoMixtoRowsVenta"></div>
                <button type="button" class="btn btn-outline btn-xs" onclick="agregarPagoMixtoVenta()"><i class="fa fa-plus"></i> Agregar metodo</button>
                <div class="text-xs text-muted" id="pagoMixtoResumenVenta" style="margin-top:8px"></div>
            </div>

            <div id="panelCreditoVenta" style="display:none;border:1px dashed #fed7aa;border-radius:10px;padding:10px;margin-bottom:12px;background:#fffaf5">
                <div id="creditoRowsVenta"></div>
                <button type="button" class="btn btn-outline btn-xs" onclick="agregarCuotaCreditoVenta()"><i class="fa fa-plus"></i> Agregar cuota</button>
                <div class="text-xs text-muted" id="creditoResumenVenta" style="margin-top:8px"></div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="form-group">
                    <label class="form-label">Monto recibido</label>
                    <input type="number" class="form-control" id="cobroVentaMonto" min="0" step="0.01" oninput="calcularVueltoVenta()">
                </div>
                <div class="form-group">
                    <label class="form-label">Vuelto</label>
                    <div id="cobroVentaVuelto" class="form-control font-bold" style="background:#f8fafc;color:var(--success)">S/ 0.00</div>
                </div>
            </div>

            <div class="text-xs text-muted" id="cobroVentaNota" style="margin-top:8px">
                Revisa el cobro antes de emitir. El comprobante se generara al confirmar.
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalCobroVenta')">Cancelar</button>
            <button class="btn btn-primary" id="btnConfirmarCobroVenta" onclick="confirmarCobroVenta()">
                <i class="fa fa-check"></i> Pagar y emitir comprobante
            </button>
        </div>
    </div>
</div>

<!-- MODAL: COMPROBANTE DE VENTA -->
<div class="modal-overlay" id="modalVentaTicket">
    <div class="modal" style="max-width:390px">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-circle-check" style="color:var(--success)"></i> Venta completada</span>
            <button class="modal-close" onclick="cerrarTicketVenta()"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body" style="padding:16px;background:#e5e7eb;max-height:72vh;overflow:auto">
            <div style="background:#fff;border-radius:6px;padding:16px;box-shadow:0 2px 8px rgba(15,23,42,.18)">
                <div id="ventaTicketBody"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline btn-sm" onclick="imprimirTicketVenta()"><i class="fa fa-print"></i> Imprimir</button>
            <button class="btn btn-primary" onclick="cerrarTicketVenta()"><i class="fa fa-plus"></i> Nueva venta</button>
        </div>
    </div>
</div>

<!-- MODAL: VENTA DE MAQUINARIA -->
<div class="modal-overlay" id="modalVentaMaquinaria">
    <div class="modal">
        <div class="modal-header"><span class="modal-title"><i class="fa fa-gears"></i> Vender maquinaria</span><button class="modal-close"><i class="fa fa-times"></i></button></div>
        <div class="modal-body">
            <input type="hidden" id="maqVentaId">
            <div style="border:1px solid var(--border);border-radius:12px;padding:12px;margin-bottom:14px;background:#f8fafc">
                <div class="font-semibold" id="maqVentaTitulo">Maquinaria</div>
                <div class="text-xs text-muted" id="maqVentaDetalle">Serie / codigo</div>
            </div>
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Comprobante</label>
                    <select class="form-control" id="maqVentaTipo" onchange="cargarClientesMaquinariaVenta()">
                        <option value="boleta">Boleta electronica</option>
                        <option value="factura">Factura electronica</option>
                        <option value="nota_venta">Nota de venta</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Precio final (S/)</label>
                    <input type="number" class="form-control" id="maqVentaPrecio" step="0.01" min="0">
                </div>
                <div class="form-group" style="grid-column:span 2">
                    <label class="form-label">Cliente</label>
                    <select class="form-control" id="maqVentaCliente"></select>
                    <small class="text-muted">Factura solo permite clientes con RUC. Para credito/cuotas selecciona un cliente real.</small>
                </div>
                <div class="form-group" style="grid-column:span 2">
                    <label class="form-label">Observaciones</label>
                    <textarea class="form-control" id="maqVentaObs" rows="2" placeholder="Detalle para la venta..."></textarea>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalVentaMaquinaria')">Cancelar</button>
            <button class="btn btn-primary" onclick="prepararCobroMaquinaria()"><i class="fa fa-cash-register"></i> Continuar al cobro</button>
        </div>
    </div>
</div>

<!-- MODAL: DETALLE DE VENTA HISTORIAL -->
<div class="modal-overlay" id="modalDetalleVenta">
    <div class="modal modal-lg">
        <div class="modal-header">
            <span class="modal-title" id="detalleVentaTitulo"><i class="fa fa-receipt" style="color:var(--primary)"></i> Detalle de venta</span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body" id="detalleVentaBody">
            <div class="text-center" style="padding:30px"><div class="loading"><div class="spinner"></div></div></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalDetalleVenta')">Cerrar</button>
            <button class="btn btn-primary" onclick="imprimirDetalleVenta()"><i class="fa fa-print"></i> Imprimir comprobante</button>
        </div>
    </div>
</div>

<!-- MODAL: NOTA DE CREDITO -->
<div class="modal-overlay" id="modalNotaCreditoVenta">
    <div class="modal" style="max-width:520px">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-file-circle-minus" style="color:var(--danger)"></i> Emitir nota de credito</span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body" style="padding:18px 20px">
            <input type="hidden" id="notaCreditoOrigenId">
            <div class="form-group">
                <label class="form-label">Comprobante origen</label>
                <div class="form-control" id="notaCreditoOrigenLabel" style="background:#f8fafc"></div>
            </div>
            <div class="form-group">
                <label class="form-label">Motivo SUNAT</label>
                <select class="form-control" id="notaCreditoTipo"></select>
            </div>
            <div class="form-group">
                <label class="form-label">Descripcion</label>
                <textarea class="form-control" id="notaCreditoDescripcion" rows="3" placeholder="Ejemplo: anulacion de la operacion"></textarea>
            </div>
            <div class="text-xs text-muted" style="background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:9px 10px">
                Se emitira una nota de credito total sobre el comprobante seleccionado y se enviara a SUNAT.
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalNotaCreditoVenta')">Cancelar</button>
            <button class="btn btn-primary" id="btnEmitirNotaCreditoVenta" onclick="emitirNotaCreditoVenta()">
                <i class="fa fa-paper-plane"></i> Emitir nota
            </button>
        </div>
    </div>
</div>

<script src="../../assets/vendor/jquery/jquery-3.7.1.min.js"></script>
<script src="../../assets/vendor/select2/select2.min.js"></script>
<script src="../../assets/vendor/datatables/jquery.dataTables.min.js"></script>
<script src="../../assets/vendor/datatables/dataTables.bootstrap5.min.js"></script>
<script>
const BASE = 'api.php';
const EMPRESA_VENTA = <?= json_encode([
    'nombre' => $empresaVenta['nombre_comercial'] ?: ($empresaVenta['razon_social'] ?? 'IndustriaMG'),
    'razon_social' => $empresaVenta['razon_social'] ?? 'IndustriaMG',
    'ruc' => $empresaVenta['ruc'] ?? '',
    'direccion' => $empresaVenta['direccion_fiscal'] ?? '',
    'telefono' => $empresaVenta['telefono'] ?? '',
], JSON_UNESCAPED_UNICODE) ?>;

// HELPERS
function esc(s) { const d=document.createElement('div');d.textContent=String(s||'');return d.innerHTML; }
const estadosCot = { borrador:'badge-cancelado', enviada:'badge-fabricacion', aprobada:'badge-entrega', rechazada:'badge-retrasado', vencida:'badge-pruebas' };
const estadosSrv = { pendiente:'badge-pruebas', en_proceso:'badge-fabricacion', completado:'badge-entrega', cancelado:'badge-cancelado' };
function activarTab(id) {
    document.querySelectorAll('.tab[data-group="vnt"]').forEach(t => { if(t.dataset.target===id) t.click(); });
}

// REALIZAR VENTA
let productosVenta = [];
let carritoVenta   = [];
let seriesVenta = { nota_venta: [], boleta: [], factura: [] };
let clientesVenta = [];
let selectedClienteVenta = null;
let defaultClienteVenta = null;
let ultimoTicketVenta = null;
let ventaDetalleActual = null;
let tiposNotaCreditoVenta = [];
let ventasHistorialTable = null;
let notasCreditoTable = null;
let cobroVentaTotales = { subtotal: 0, igv: 0, total: 0 };
let cobroVentaBaseTotales = { subtotal: 0, igv: 0, total: 0 };
let pagoMixtoVentaActivo = false;
let pagoMixtoVentaRows = [];
let creditoVentaActivo = false;
let creditoVentaRows = [];
let maquinariasVenta = [];
let maquinariaVentaActual = null;

async function cargarProductosVenta() {
    try {
        const d = await apiGet(`${BASE}?action=productos_listar_venta`);
        productosVenta = d.productos || [];
        renderProductosVenta(productosVenta.slice(0, 30));
    } catch(e) {
        document.getElementById('ventaResultados').innerHTML =
            '<div class="text-center" style="padding:12px;color:var(--danger);font-size:.85rem">Error al cargar productos</div>';
    }
}

function filtrarProductosVenta() {
    const q = document.getElementById('ventaSearch').value.trim().toLowerCase();
    const lista = q
        ? productosVenta.filter(p => p.nombre.toLowerCase().includes(q) || p.codigo.toLowerCase().includes(q))
        : productosVenta;
    renderProductosVenta(lista.slice(0, 40));
}

function renderProductosVenta(lista) {
    const el = document.getElementById('ventaResultados');
    if (!lista.length) {
        el.innerHTML = '<div class="text-center text-muted" style="padding:14px;font-size:.85rem">Sin resultados</div>';
        return;
    }
    el.innerHTML = lista.map(p => {
        const tipoProducto = (p.product_type || '').toLowerCase();
        const esServicio = tipoProducto === 'service' || (p.unidad_codigo || '').toLowerCase() === 'zz' || (p.unidad || '').toLowerCase() === 'servicio';
        const esMaquinaria = tipoProducto === 'machine';
        const sinStock = !esServicio && parseFloat(p.stock_actual) <= 0;
        const badgeLabel = esServicio ? 'Servicio' : (esMaquinaria ? 'Maquinaria' : (p.es_repuesto ? 'Repuesto' : 'Material'));
        const badgeBg = esServicio ? '#fff7ed' : (esMaquinaria ? '#fef3c7' : (p.es_repuesto ? '#f0fdf4' : '#eff6ff'));
        const badgeColor = esServicio ? '#ea580c' : (esMaquinaria ? '#b45309' : (p.es_repuesto ? '#16a34a' : '#2563eb'));
        return `<div style="display:flex;align-items:center;gap:12px;padding:9px 14px;border-bottom:1px solid var(--border);cursor:${sinStock?'not-allowed':'pointer'};opacity:${sinStock?.5:1}"
             ${!sinStock ? `onclick="agregarAlCarrito(${p.id})"` : ''}
             onmouseover="${!sinStock?"this.style.background='var(--bg-secondary,#f8fafc)'":''}"
             onmouseout="this.style.background=''">
            <div style="flex:1;min-width:0">
                <div class="font-semibold text-sm" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${esc(p.nombre)}</div>
                <div class="text-xs text-muted">${esc(p.codigo)} - <span style="background:${badgeBg};color:${badgeColor};padding:1px 6px;border-radius:4px;font-size:.68rem">${badgeLabel}</span></div>
            </div>
            <div class="text-right" style="flex-shrink:0">
                <div class="text-sm font-semibold" style="color:${sinStock?'var(--danger)':'var(--text-primary)'}">${esServicio ? 'Servicio' : `${p.stock_actual} ${esc(p.unidad)}`}</div>
                <div class="text-xs text-muted">${formatMoney(p.precio_venta)}</div>
            </div>
            ${!sinStock ? '<i class="fa fa-plus-circle" style="color:var(--primary);font-size:1.1rem;flex-shrink:0"></i>' : '<i class="fa fa-ban" style="color:var(--danger);font-size:1rem;flex-shrink:0"></i>'}
        </div>`;
    }).join('');
}

function normalizarBooleano(v) {
    return v === true || v === 1 || v === '1' || v === 't' || v === 'true';
}

function agregarAlCarrito(id) {
    const p = productosVenta.find(prod => String(prod.id) === String(id));
    if (!p) return;
    maquinariaVentaActual = null;
    const esServicio = (p.product_type || '').toLowerCase() === 'service' || (p.unidad_codigo || '').toLowerCase() === 'zz' || (p.unidad || '').toLowerCase() === 'servicio';
    const stock = esServicio ? 999999 : (parseFloat(p.stock_actual) || 0);
    const existing = carritoVenta.find(i => i.id == id);
    if (existing) {
        if (existing.cantidad < stock) existing.cantidad++;
        renderCarrito();
        return;
    }
    carritoVenta.push({
        id: p.id,
        nombre: p.nombre,
        unidad: p.unidad || p.unidad_codigo || 'NIU',
        esServicio,
        stock,
        precio: parseFloat(p.precio_venta) || 0,
        cantidad: 1,
        descuento: 0,
        afectacion_igv_codigo: p.afectacion_igv_codigo || '10',
        porcentaje_igv: parseFloat(p.porcentaje_igv ?? 18) || 0,
        incluye_igv: normalizarBooleano(p.incluye_igv ?? true),
    });
    renderCarrito();
}

function renderCarrito() {
    const tbody = document.getElementById('ventaCarritoBody');
    if (!carritoVenta.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted" style="padding:20px">Sin productos agregados</td></tr>';
        recalcularVentaTotals();
        return;
    }
    tbody.innerHTML = carritoVenta.map((it, idx) => `<tr>
        <td>
            <div class="font-semibold text-sm">${esc(it.nombre)}</div>
            <div class="text-xs text-muted">${esc(it.unidad)}</div>
        </td>
        <td class="text-right text-sm ${parseFloat(it.stock)<=0&&!it.esServicio?'text-danger':'text-muted'}">${it.esServicio ? 'Servicio' : it.stock}</td>
        <td class="text-right">
            <input type="number" class="form-control text-right" style="width:80px"
                   value="${it.cantidad}" min="0.001" ${it.esServicio ? '' : `max="${it.stock}"`} step="0.001"
                   onchange="updateCarritoItem(${idx},'cantidad',this.value)">
        </td>
        <td class="text-right">
            <input type="number" class="form-control text-right" style="width:100px"
                   value="${it.precio}" min="0" step="0.01"
                   onchange="updateCarritoItem(${idx},'precio',this.value)">
        </td>
        <td class="text-right">
            <input type="number" class="form-control text-right" style="width:70px"
                   value="${it.descuento}" min="0" max="100" step="0.01"
                   onchange="updateCarritoItem(${idx},'descuento',this.value)">
        </td>
        <td class="text-right font-semibold">${formatMoney(calcularLineaVentaUI(it).total)}</td>
        <td class="text-center">
            <button class="btn btn-outline btn-xs" onclick="quitarDelCarrito(${idx})"><i class="fa fa-times"></i></button>
        </td>
    </tr>`).join('');
    recalcularVentaTotals();
}

function updateCarritoItem(idx, field, value) {
    carritoVenta[idx][field] = parseFloat(value) || 0;
    renderCarrito();
}

function quitarDelCarrito(idx) {
    carritoVenta.splice(idx, 1);
    renderCarrito();
}

function limpiarCarrito() {
    carritoVenta = [];
    maquinariaVentaActual = null;
    renderCarrito();
    document.getElementById('ventaSearch').value = '';
    filtrarProductosVenta();
}

function calcularLineaVentaUI(it) {
    const cantidad = Math.max(0, parseFloat(it.cantidad) || 0);
    const precio = Math.max(0, parseFloat(it.precio) || 0);
    const descuentoPct = Math.max(0, Math.min(100, parseFloat(it.descuento) || 0));
    const afectacion = it.afectacion_igv_codigo || '10';
    const porcentaje = afectacion === '10' ? Math.max(0, parseFloat(it.porcentaje_igv) || 18) : 0;
    const totalBruto = cantidad * precio;
    const descuento = totalBruto * (descuentoPct / 100);
    let total = totalBruto - descuento;
    let subtotal = total;
    let igv = 0;

    if (porcentaje > 0 && it.incluye_igv) {
        subtotal = total / (1 + porcentaje / 100);
        igv = total - subtotal;
    } else if (porcentaje > 0) {
        subtotal = total;
        igv = subtotal * (porcentaje / 100);
        total = subtotal + igv;
    }

    return { subtotal, igv, total, descuento };
}

function recalcularVentaTotals() {
    const totales = calcularTotalesVentaUI();
    cobroVentaTotales = totales;
    document.getElementById('ventaSubtotal').textContent = formatMoney(totales.subtotal);
    document.getElementById('ventaIgv').textContent      = formatMoney(totales.igv);
    document.getElementById('ventaTotal').textContent    = formatMoney(totales.total);
}

function calcularTotalesVentaUI() {
    return carritoVenta.reduce((acc, it) => {
        const linea = calcularLineaVentaUI(it);
        acc.subtotal += linea.subtotal;
        acc.igv += linea.igv;
        acc.total += linea.total;
        return acc;
    }, { subtotal: 0, igv: 0, total: 0 });
}

function validarVentaAntesDeCobro() {
    if (!carritoVenta.length) { Toast.warning('Agrega al menos un producto.'); return; }
    const tipoComprobante = document.getElementById('ventaTipoComprobante').value;
    const serie = document.getElementById('ventaSerie').value;
    if (!serie) { Toast.warning('No hay una serie activa para este comprobante.'); return; }
    if (tipoComprobante === 'factura' && !esClienteRuc(selectedClienteVenta)) {
        Toast.warning('Para factura selecciona un cliente con RUC de 11 digitos.');
        return;
    }
    for (const it of carritoVenta) {
        if (it.cantidad <= 0)        { Toast.warning(`Cantidad invalida para "${it.nombre}".`); return; }
        if (!it.esServicio && it.cantidad > it.stock)  { Toast.warning(`Stock insuficiente para "${it.nombre}" (disponible: ${it.stock}).`); return; }
    }
    return true;
}

function abrirModalCobroVenta() {
    if (!validarVentaAntesDeCobro()) return;
    const tipoComprobante = document.getElementById('ventaTipoComprobante').value;
    const labels = { boleta: 'Boleta electronica', factura: 'Factura electronica', nota_venta: 'Nota de venta' };
    const totales = calcularTotalesVentaUI();
    cobroVentaBaseTotales = totales;
    cobroVentaTotales = {...totales};
    pagoMixtoVentaActivo = false;
    pagoMixtoVentaRows = [];
    creditoVentaActivo = false;
    creditoVentaRows = [];

    document.getElementById('cobroVentaComprobante').textContent = labels[tipoComprobante] || 'Comprobante';
    document.getElementById('cobroVentaCliente').textContent = selectedClienteVenta ? clienteVentaLabel(selectedClienteVenta) : 'CLIENTES VARIOS';
    document.getElementById('cobroVentaDescuento').value = '0.00';
    document.getElementById('cobroVentaMetodo').value = 'efectivo';
    document.getElementById('cobroVentaCredito').checked = false;
    document.getElementById('panelPagoMixtoVenta').style.display = 'none';
    document.getElementById('panelCreditoVenta').style.display = 'none';
    actualizarResumenCobroVenta();
    Modal.open('modalCobroVenta');
}

function descuentoCobroVenta() {
    return Math.max(0, parseFloat(document.getElementById('cobroVentaDescuento')?.value) || 0);
}

function actualizarResumenCobroVenta() {
    const base = cobroVentaBaseTotales.total || 0;
    let descuento = descuentoCobroVenta();
    if (descuento > base) {
        descuento = base;
        document.getElementById('cobroVentaDescuento').value = descuento.toFixed(2);
    }
    const ratio = base > 0 ? Math.max(0, (base - descuento) / base) : 0;
    cobroVentaTotales = {
        subtotal: cobroVentaBaseTotales.subtotal * ratio,
        igv: cobroVentaBaseTotales.igv * ratio,
        total: Math.max(0, base - descuento),
    };
    document.getElementById('cobroVentaSubtotal').textContent = formatMoney(cobroVentaTotales.subtotal);
    document.getElementById('cobroVentaDescuentoLabel').textContent = `-${formatMoney(descuento)}`;
    document.getElementById('cobroVentaIgv').textContent = formatMoney(cobroVentaTotales.igv);
    document.getElementById('cobroVentaTotal').textContent = formatMoney(cobroVentaTotales.total);

    if (!pagoMixtoVentaActivo && !creditoVentaActivo) {
        document.getElementById('cobroVentaMonto').value = cobroVentaTotales.total.toFixed(2);
    }
    if (pagoMixtoVentaActivo) actualizarResumenPagoMixtoVenta();
    if (creditoVentaActivo) syncCreditoVenta(true);
    calcularVueltoVenta();
}

function calcularVueltoVenta() {
    const metodo = document.getElementById('cobroVentaMetodo')?.value || 'efectivo';
    const total = cobroVentaTotales.total || 0;
    const input = document.getElementById('cobroVentaMonto');
    if (pagoMixtoVentaActivo || creditoVentaActivo) {
        input.disabled = true;
        input.value = creditoVentaActivo ? '0.00' : total.toFixed(2);
        document.getElementById('cobroVentaVuelto').textContent = 'S/ 0.00';
        return;
    }
    if (metodo !== 'efectivo') {
        input.value = total.toFixed(2);
        input.disabled = true;
        document.getElementById('cobroVentaVuelto').textContent = 'S/ 0.00';
        return;
    }
    input.disabled = false;
    const recibido = parseFloat(input.value) || 0;
    const vuelto = Math.max(0, recibido - total);
    const el = document.getElementById('cobroVentaVuelto');
    el.textContent = formatMoney(vuelto);
    el.style.color = recibido >= total ? 'var(--success)' : 'var(--danger)';
}

function togglePagoMixtoVenta() {
    if (creditoVentaActivo) return Toast.warning('Desactiva el pago a credito para usar pago mixto.');
    pagoMixtoVentaActivo = !pagoMixtoVentaActivo;
    if (pagoMixtoVentaActivo && !pagoMixtoVentaRows.length) {
        pagoMixtoVentaRows = [
            { method: 'efectivo', amount: cobroVentaTotales.total },
        ];
    }
    document.getElementById('panelPagoMixtoVenta').style.display = pagoMixtoVentaActivo ? 'block' : 'none';
    renderPagoMixtoVenta();
    calcularVueltoVenta();
}

function agregarPagoMixtoVenta() {
    pagoMixtoVentaActivo = true;
    pagoMixtoVentaRows.push({ method: 'yape', amount: 0 });
    document.getElementById('panelPagoMixtoVenta').style.display = 'block';
    renderPagoMixtoVenta();
}

function actualizarPagoMixtoVenta(idx, field, value) {
    if (!pagoMixtoVentaRows[idx]) return;
    pagoMixtoVentaRows[idx][field] = field === 'amount' ? Math.max(0, parseFloat(value) || 0) : value;
    actualizarResumenPagoMixtoVenta();
}

function quitarPagoMixtoVenta(idx) {
    pagoMixtoVentaRows.splice(idx, 1);
    if (!pagoMixtoVentaRows.length) {
        pagoMixtoVentaActivo = false;
        document.getElementById('panelPagoMixtoVenta').style.display = 'none';
    }
    renderPagoMixtoVenta();
}

function renderPagoMixtoVenta() {
    const rows = document.getElementById('pagoMixtoRowsVenta');
    rows.innerHTML = pagoMixtoVentaRows.map((row, idx) => `
        <div style="display:grid;grid-template-columns:1fr minmax(150px,170px) 38px;gap:8px;margin-bottom:8px">
            <select class="form-control" onchange="actualizarPagoMixtoVenta(${idx},'method',this.value)">
                ${['efectivo','yape','plin','tarjeta','transferencia'].map(m => `<option value="${m}" ${row.method===m?'selected':''}>${m}</option>`).join('')}
            </select>
            <input type="number" class="form-control text-right" min="0" step="0.01" inputmode="decimal" style="min-width:0" value="${row.amount}" oninput="actualizarPagoMixtoVenta(${idx},'amount',this.value)">
            <button class="btn btn-outline btn-xs" onclick="quitarPagoMixtoVenta(${idx})"><i class="fa fa-times"></i></button>
        </div>
    `).join('');
    actualizarResumenPagoMixtoVenta();
}

function actualizarResumenPagoMixtoVenta() {
    const suma = pagoMixtoVentaRows.reduce((a,r)=>a+(parseFloat(r.amount)||0),0);
    const falta = cobroVentaTotales.total - suma;
    const el = document.getElementById('pagoMixtoResumenVenta');
    el.textContent = `Total pagos: ${formatMoney(suma)} | Diferencia: ${formatMoney(falta)}`;
    el.style.color = Math.abs(falta) <= 0.01 ? 'var(--success)' : 'var(--danger)';
}

function toggleCreditoVenta(active) {
    creditoVentaActivo = !!active;
    if (creditoVentaActivo) {
        pagoMixtoVentaActivo = false;
        pagoMixtoVentaRows = [];
        document.getElementById('panelPagoMixtoVenta').style.display = 'none';
        if (!creditoVentaRows.length) {
            creditoVentaRows = [{ amount: cobroVentaTotales.total, due_date: fechaCuotaDefaultVenta() }];
        }
    } else {
        creditoVentaRows = [];
    }
    document.getElementById('panelCreditoVenta').style.display = creditoVentaActivo ? 'block' : 'none';
    syncCreditoVenta(true);
    calcularVueltoVenta();
}

function fechaCuotaDefaultVenta() {
    const d = new Date();
    d.setDate(d.getDate() + 30);
    return d.toISOString().slice(0,10);
}

function agregarCuotaCreditoVenta() {
    creditoVentaActivo = true;
    document.getElementById('cobroVentaCredito').checked = true;
    creditoVentaRows.push({ amount: 0, due_date: fechaCuotaDefaultVenta() });
    document.getElementById('panelCreditoVenta').style.display = 'block';
    renderCreditoVenta();
}

function actualizarCuotaCreditoVenta(idx, field, value) {
    if (!creditoVentaRows[idx]) return;
    creditoVentaRows[idx][field] = field === 'amount' ? Math.max(0, parseFloat(value) || 0) : value;
    actualizarResumenCreditoVenta();
}

function quitarCuotaCreditoVenta(idx) {
    creditoVentaRows.splice(idx, 1);
    if (!creditoVentaRows.length) toggleCreditoVenta(false);
    renderCreditoVenta();
}

function syncCreditoVenta(force = false) {
    if (!creditoVentaActivo) return;
    if (!creditoVentaRows.length || (force && creditoVentaRows.length === 1)) {
        creditoVentaRows = [{ amount: cobroVentaTotales.total, due_date: creditoVentaRows[0]?.due_date || fechaCuotaDefaultVenta() }];
    }
    renderCreditoVenta();
}

function renderCreditoVenta() {
    const rows = document.getElementById('creditoRowsVenta');
    rows.innerHTML = creditoVentaRows.map((row, idx) => `
        <div style="display:grid;grid-template-columns:minmax(140px,160px) 1fr 38px;gap:8px;margin-bottom:8px">
            <input type="number" class="form-control text-right" min="0" step="0.01" inputmode="decimal" style="min-width:0" value="${row.amount}" oninput="actualizarCuotaCreditoVenta(${idx},'amount',this.value)">
            <input type="date" class="form-control" value="${row.due_date || fechaCuotaDefaultVenta()}" onchange="actualizarCuotaCreditoVenta(${idx},'due_date',this.value)">
            <button class="btn btn-outline btn-xs" onclick="quitarCuotaCreditoVenta(${idx})"><i class="fa fa-times"></i></button>
        </div>
    `).join('');
    actualizarResumenCreditoVenta();
}

function actualizarResumenCreditoVenta() {
    const suma = creditoVentaRows.reduce((a,r)=>a+(parseFloat(r.amount)||0),0);
    const falta = cobroVentaTotales.total - suma;
    const el = document.getElementById('creditoResumenVenta');
    el.textContent = `Total cuotas: ${formatMoney(suma)} | Diferencia: ${formatMoney(falta)}`;
    el.style.color = Math.abs(falta) <= 0.01 ? 'var(--success)' : 'var(--danger)';
}

async function confirmarCobroVenta() {
    if (!validarVentaAntesDeCobro()) return;
    const metodo = document.getElementById('cobroVentaMetodo').value;
    const recibido = parseFloat(document.getElementById('cobroVentaMonto').value) || 0;
    const totalCobro = cobroVentaTotales.total || calcularTotalesVentaUI().total;
    if (creditoVentaActivo) {
        const doc = String(selectedClienteVenta?.numero_documento || selectedClienteVenta?.ruc_dni || '').replace(/\D/g, '');
        const nombre = String(selectedClienteVenta?.razon_social || selectedClienteVenta?.nombre_completo || '').toUpperCase();
        if (!selectedClienteVenta || doc === '00000000' || nombre.includes('CLIENTES VARIOS')) {
            Toast.warning('Para credito selecciona un cliente real.');
            return;
        }
        const sumaCuotas = creditoVentaRows.reduce((a,r)=>a+(parseFloat(r.amount)||0),0);
        if (!creditoVentaRows.length || Math.abs(sumaCuotas - totalCobro) > 0.01) {
            Toast.warning('La suma de cuotas debe coincidir con el total.');
            return;
        }
    } else if (pagoMixtoVentaActivo) {
        const sumaPagos = pagoMixtoVentaRows.reduce((a,r)=>a+(parseFloat(r.amount)||0),0);
        if (!pagoMixtoVentaRows.length || Math.abs(sumaPagos - totalCobro) > 0.01) {
            Toast.warning('La suma del pago mixto debe coincidir con el total.');
            return;
        }
    } else if (metodo === 'efectivo' && recibido + 0.001 < totalCobro) {
        Toast.warning('El monto recibido es menor al total.');
        return;
    }

    const btn = document.getElementById('btnConfirmarCobroVenta');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Emitiendo...';

    const tipoComprobante = document.getElementById('ventaTipoComprobante').value;
    const serie = document.getElementById('ventaSerie').value;
    const items = carritoVenta.map(it => ({
        producto_id:    it.id,
        cantidad:       it.cantidad,
        precio_unitario: it.precio,
        descuento:      it.descuento
    }));
    try {
        const res = await apiPost(BASE, {
            action:        'venta_directa',
            tipo_comprobante: tipoComprobante,
            cliente_id:    selectedClienteVenta?.id || document.getElementById('ventaCliente').value || null,
            observaciones: document.getElementById('ventaObs').value,
            tipo_pago: creditoVentaActivo ? 'credito' : metodo,
            monto_recibido: creditoVentaActivo ? 0 : (pagoMixtoVentaActivo
                ? pagoMixtoVentaRows.filter(r => r.method === 'efectivo').reduce((a,r)=>a+(parseFloat(r.amount)||0),0)
                : (metodo === 'efectivo' ? recibido : totalCobro)),
            descuento: descuentoCobroVenta(),
            es_credito: creditoVentaActivo,
            payment_breakdown: pagoMixtoVentaActivo ? pagoMixtoVentaRows : [],
            cuotas: creditoVentaActivo ? creditoVentaRows : [],
            maquinaria_id: maquinariaVentaActual?.id || null,
            items
        });
        if (res.ok) {
            Toast.success(`Venta ${res.numero} registrada. Comprobante ${res.comprobante_numero}.`);
            const usada = (seriesVenta[tipoComprobante] || []).find(s => s.serie === serie);
            if (usada) usada.ultimo_numero = (parseInt(usada.ultimo_numero) || 0) + 1;
            Modal.close('modalCobroVenta');
            mostrarTicketVenta(res);
            actualizarSeriesVenta();
            cargarProductosVenta();
            cargarMaquinarias();
            maquinariaVentaActual = null;
        } else {
            Toast.error(res.error || 'Error al registrar la venta.');
        }
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-check"></i> Pagar y emitir comprobante';
    }
}

// COTIZACIONES
function buildTicketVentaHTML(data) {
    const compLabels = { boleta:'BOLETA DE VENTA ELECTRONICA', factura:'FACTURA ELECTRONICA', nota_venta:'NOTA DE VENTA', nota_credito:'NOTA DE CREDITO ELECTRONICA' };
    const tipo = data.tipo_comprobante || 'boleta';
    const cliente = data.cliente || selectedClienteVenta || {};
    const clienteNombre = cliente.nombre || cliente.razon_social || cliente.nombre_completo || 'CLIENTES VARIOS';
    const clienteDoc = cliente.documento || cliente.numero_documento || cliente.ruc_dni || cliente.ruc || cliente.dni || '00000000';
    const fechaBase = data.created_at ? new Date(String(data.created_at).replace(' ', 'T')) : new Date();
    const fecha = fechaBase.toLocaleDateString('es-PE', { day:'2-digit', month:'2-digit', year:'numeric' });
    const hora = fechaBase.toLocaleTimeString('es-PE', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
    const items = data.items || [];
    const row = (label, value, strong = false) => `<div style="display:flex;justify-content:space-between;font-size:${strong?'14px':'11px'};font-weight:${strong?'800':'400'};line-height:1.7"><span>${label}</span><span>${value}</span></div>`;
    const sep = '<div style="border-top:1px dashed #111;margin:7px 0"></div>';
    const itemsHtml = items.map(it => {
        const qty = parseFloat(it.cantidad || 0);
        const precio = parseFloat(it.precio_unitario || it.precio || 0);
        const total = parseFloat(it.total || 0);
        return `<div style="margin-bottom:6px">
            <div style="display:flex;justify-content:space-between;font-size:11px;gap:8px">
                <span style="word-break:break-word">${esc(it.descripcion || it.nombre || 'Producto')}</span>
                <span style="white-space:nowrap;font-weight:700">S/ ${total.toFixed(2)}</span>
            </div>
            <div style="font-size:10px;color:#555">${qty} ${esc(it.unidad_codigo || 'NIU')} x S/ ${precio.toFixed(2)}</div>
        </div>`;
    }).join('');

    return `<div style="font-family:'Courier New',monospace;color:#111;font-size:12px;line-height:1.45">
        <div style="text-align:center;margin-bottom:10px">
            <div style="font-size:15px;font-weight:900;text-transform:uppercase">${esc(EMPRESA_VENTA.nombre || EMPRESA_VENTA.razon_social || 'IndustriaMG')}</div>
            ${EMPRESA_VENTA.ruc ? `<div>RUC: ${esc(EMPRESA_VENTA.ruc)}</div>` : ''}
            ${EMPRESA_VENTA.direccion ? `<div style="font-size:10px">${esc(EMPRESA_VENTA.direccion)}</div>` : ''}
            ${EMPRESA_VENTA.telefono ? `<div style="font-size:10px">Tel: ${esc(EMPRESA_VENTA.telefono)}</div>` : ''}
        </div>
        <div style="border-top:2px solid #111;border-bottom:2px solid #111;text-align:center;padding:6px 0;margin-bottom:8px">
            <div style="font-weight:800">${compLabels[tipo] || 'COMPROBANTE DE VENTA'}</div>
            <div>${esc(data.comprobante_numero || data.numero || '---')}</div>
            ${data.comprobante?.error_nubefact ? `<div style="font-size:10px;color:#b91c1c">SUNAT: ${esc(data.comprobante.mensaje || 'Pendiente de envio')}</div>` : ''}
            ${data.comprobante && !data.comprobante.error_nubefact ? `<div style="font-size:10px;color:#15803d">SUNAT: ${esc(data.comprobante.estado_sunat || 'Enviado')}</div>` : ''}
        </div>
        <div style="font-size:11px">
            <div>Fecha : ${fecha} ${hora}</div>
            <div>Venta : ${esc(data.numero || '')}</div>
            <div>Cliente: ${esc(clienteNombre)}</div>
            <div>Doc.   : ${esc(clienteDoc)}</div>
            ${cliente.direccion ? `<div>Dir.   : ${esc(cliente.direccion)}</div>` : ''}
            ${tipo === 'nota_credito' && data.documento_modificado_numero_completo ? `<div>Ref.   : ${esc(data.documento_modificado_numero_completo)}</div>` : ''}
            ${tipo === 'nota_credito' && data.motivo_nota_credito ? `<div>Motivo : ${esc(data.motivo_nota_credito)}</div>` : ''}
        </div>
        ${sep}
        <div style="display:flex;justify-content:space-between;font-size:11px;font-weight:800"><span>DESCRIPCION</span><span>TOTAL</span></div>
        ${sep}
        ${itemsHtml}
        ${sep}
        ${row('OP. GRAVADA:', `S/ ${parseFloat(data.gravada || 0).toFixed(2)}`)}
        ${parseFloat(data.exonerada || 0) > 0 ? row('OP. EXONERADA:', `S/ ${parseFloat(data.exonerada).toFixed(2)}`) : ''}
        ${parseFloat(data.inafecta || 0) > 0 ? row('OP. INAFECTA:', `S/ ${parseFloat(data.inafecta).toFixed(2)}`) : ''}
        ${row('IGV (18%):', `S/ ${parseFloat(data.igv || 0).toFixed(2)}`)}
        ${parseFloat(data.descuento || 0) > 0 ? row('DESCUENTO:', `-S/ ${parseFloat(data.descuento).toFixed(2)}`) : ''}
        <div style="border-top:2px solid #111;margin:7px 0"></div>
        ${row('TOTAL:', `S/ ${parseFloat(data.total || 0).toFixed(2)}`, true)}
        <div style="border-top:2px solid #111;margin:7px 0"></div>
        ${(data.tipo_pago === 'credito' && Array.isArray(data.cuotas) && data.cuotas.length)
            ? data.cuotas.map((c, i) => row(`Cuota ${i + 1} (${c.due_date}):`, `S/ ${parseFloat(c.amount || 0).toFixed(2)}`)).join('')
            : (Array.isArray(data.payment_breakdown) && data.payment_breakdown.length
                ? data.payment_breakdown.map(p => row((p.method || 'Pago') + ':', `S/ ${parseFloat(p.amount || 0).toFixed(2)}`)).join('')
                : row((data.tipo_pago || 'efectivo') + ':', `S/ ${parseFloat(data.monto_recibido || data.total || 0).toFixed(2)}`))
        }
        ${parseFloat(data.vuelto || 0) > 0 ? row('VUELTO:', `S/ ${parseFloat(data.vuelto).toFixed(2)}`) : ''}
        ${sep}
        <div style="text-align:center;font-size:11px;margin-top:8px">Gracias por su compra</div>
    </div>`;
}

function mostrarTicketVenta(data) {
    ultimoTicketVenta = data;
    document.getElementById('ventaTicketBody').innerHTML = buildTicketVentaHTML(data);
    Modal.open('modalVentaTicket');
}

function imprimirTicketVenta() {
    const html = document.getElementById('ventaTicketBody').innerHTML;
    const w = window.open('', '_blank', 'width=420,height=700');
    w.document.write(`<!DOCTYPE html><html><head><meta charset="utf-8"><style>
        *{box-sizing:border-box} body{font-family:'Courier New',monospace;width:80mm;margin:0 auto;padding:4mm 3mm;color:#000}
        @media print{ @page{size:80mm auto;margin:4mm 3mm} body{padding:0} }
    </style></head><body>${html}</body></html>`);
    w.document.close();
    w.focus();
    setTimeout(() => w.print(), 350);
}

function cerrarTicketVenta() {
    Modal.close('modalVentaTicket');
    limpiarCarrito();
    document.getElementById('ventaObs').value = '';
    cargarClientesVenta();
}

// LISTADO DE VENTAS
function ventaFechaHoraLabel(value) {
    if (!value) return '-';
    const d = new Date(String(value).replace(' ', 'T'));
    if (Number.isNaN(d.getTime())) return value;
    return `${d.toLocaleDateString('es-PE')} ${d.toLocaleTimeString('es-PE', {hour:'2-digit', minute:'2-digit'})}`;
}

function ventaTipoLabel(tipo) {
    return { boleta:'Boleta', factura:'Factura', nota_venta:'Nota de venta' }[tipo] || 'Venta';
}

function ventaPagoLabel(v) {
    if (parseFloat(v.monto_credito || 0) > 0 || v.tipo_pago === 'credito') return 'Credito';
    if (parseInt(v.pagos_count || 0, 10) > 1) return 'Mixto';
    return String(v.tipo_pago || 'efectivo').replace('_', ' ');
}

function activarSubtabVentas(tabName) {
    document.querySelectorAll('.venta-subtab').forEach(t => t.classList.toggle('active', t.dataset.ventaSubtab === tabName));
    document.getElementById('subtabVentasLista').classList.toggle('active', tabName === 'ventas');
    document.getElementById('subtabNotasCredito').classList.toggle('active', tabName === 'notas');
    setTimeout(() => {
        if (tabName === 'ventas' && ventasHistorialTable) ventasHistorialTable.columns.adjust();
        if (tabName === 'notas' && notasCreditoTable) notasCreditoTable.columns.adjust();
    }, 80);
}

function dataTableLangVentas() {
    return {
        search: 'Buscar:',
        lengthMenu: 'Mostrar _MENU_ registros',
        info: 'Mostrando _START_ a _END_ de _TOTAL_',
        infoEmpty: 'Sin registros',
        zeroRecords: 'No se encontraron resultados',
        emptyTable: 'Sin registros disponibles',
        paginate: { first:'Primero', previous:'Anterior', next:'Siguiente', last:'Ultimo' },
    };
}

function reiniciarDataTable(refName, selector, order = [[1, 'desc']]) {
    if (typeof window.jQuery === 'undefined' || typeof jQuery.fn.DataTable === 'undefined') return null;
    if (window[refName]) {
        window[refName].destroy();
        window[refName] = null;
    }
    window[refName] = jQuery(selector).DataTable({
        pageLength: 10,
        order,
        autoWidth: false,
        language: dataTableLangVentas(),
        columnDefs: [{ orderable: false, targets: -1 }],
    });
    return window[refName];
}

function archivoVentaBtn(url, label) {
    if (!url) return '<span class="text-xs text-muted">-</span>';
    return `<a class="btn btn-outline btn-xs" href="${esc(url)}" target="_blank" title="Abrir ${label}"><i class="fa fa-file-code"></i></a>`;
}

function cdrVentaCell(v) {
    if (v.cdr_path) return archivoVentaBtn(v.cdr_path, 'CDR');
    const compId = parseInt(v.comprobante_id || v.id || 0, 10);
    const tipo = v.tipo_comprobante || '';
    const codigo = v.codigo_tipo_documento || '';
    const esEnviable = ['boleta', 'factura'].includes(tipo) || ['01', '03', '07'].includes(codigo);
    if (compId && esEnviable && !v.anulado) {
        return `<button class="btn btn-outline btn-xs" onclick="reenviarVentaSunat(${compId})" title="Reenviar a SUNAT"><i class="fa fa-paper-plane"></i></button>`;
    }
    return '<span class="text-xs text-muted">-</span>';
}

function accionesVentaHistorial(v) {
    const id = parseInt(v.id || 0, 10);
    const compId = parseInt(v.comprobante_id || 0, 10);
    const tipo = v.tipo_comprobante || '';
    const tieneCdr = !!v.cdr_path;
    const esElectronico = ['boleta', 'factura'].includes(tipo);
    const reenviar = compId && esElectronico && !tieneCdr && !v.anulado
        ? `<button class="btn btn-outline btn-xs" onclick="reenviarVentaSunat(${compId})" title="Reenviar a SUNAT"><i class="fa fa-paper-plane"></i></button>`
        : '';
    const notaCredito = compId && esElectronico && tieneCdr && !v.anulado
        ? `<button class="btn btn-outline btn-xs" onclick="abrirNotaCreditoVenta(${compId}, '${esc(v.comprobante_numero || '')}')" title="Nota de credito"><i class="fa fa-file-circle-minus"></i></button>`
        : '';
    return `
        <div style="display:flex;gap:5px;flex-wrap:wrap">
            <button class="btn btn-outline btn-xs" onclick="verDetalleVenta(${id})" title="Ver detalle"><i class="fa fa-eye"></i></button>
            <button class="btn btn-outline btn-xs" onclick="imprimirVentaHistorial(${id})" title="Imprimir ticket"><i class="fa fa-print"></i></button>
            ${reenviar}
            ${notaCredito}
        </div>
    `;
}

function badgeVentaEstado(estado, anulado = false) {
    if (anulado || estado === 'cancelada') return '<span class="badge badge-cancelado">Anulada</span>';
    const cls = { cobrada:'badge-entrega', facturada:'badge-fabricacion', confirmada:'badge-pruebas', borrador:'badge-cancelado' }[estado] || 'badge-pruebas';
    const label = { cobrada:'Cobrada', facturada:'Facturada', confirmada:'Confirmada', borrador:'Borrador' }[estado] || estado;
    return `<span class="badge ${cls}">${esc(label)}</span>`;
}

async function cargarVentasStats() {
    try {
        const d = await apiGet(`${BASE}?action=ventas_stats`);
        const s = d.stats || {};
        const cards = [
            ['Ventas hoy', s.total_ventas || 0, 'fa-cash-register', 'var(--primary)'],
            ['Ingresos hoy', formatMoney(s.ingresos || 0), 'fa-sack-dollar', 'var(--success)'],
            ['Ticket promedio', formatMoney(s.ticket_promedio || 0), 'fa-chart-line', '#f59e0b'],
            ['Anuladas hoy', s.anuladas || 0, 'fa-ban', 'var(--danger)'],
        ];
        document.getElementById('ventasStats').innerHTML = cards.map(c => `
            <div class="venta-stat-card">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:12px">
                    <div>
                        <div class="venta-stat-label">${c[0]}</div>
                        <div class="venta-stat-value">${c[1]}</div>
                    </div>
                    <i class="fa ${c[2]}" style="font-size:1.35rem;color:${c[3]}"></i>
                </div>
            </div>
        `).join('');
    } catch(e) {
        document.getElementById('ventasStats').innerHTML = '<div class="card" style="grid-column:1/-1;padding:14px;color:var(--danger)">No se pudieron cargar las estadisticas.</div>';
    }
}

async function cargarVentasHistorial() {
    const params = new URLSearchParams({
        action: 'ventas_historial',
        desde: document.getElementById('ventasDesde').value,
        hasta: document.getElementById('ventasHasta').value,
        estado: document.getElementById('ventasEstado').value,
        tipo: document.getElementById('ventasTipo').value,
        q: document.getElementById('ventasBuscar').value,
    });
    const tbody = document.getElementById('ventasHistorialBody');
    if (ventasHistorialTable) {
        ventasHistorialTable.destroy();
        ventasHistorialTable = null;
        window.ventasHistorialTable = null;
    }
    tbody.innerHTML = '<tr><td colspan="11" class="text-center"><div class="loading"><div class="spinner"></div></div></td></tr>';
    try {
        const d = await apiGet(`${BASE}?${params}`);
        const lista = d.ventas || [];
        document.getElementById('ventasResultCount').textContent = `${lista.length} resultado(s)`;
        if (!lista.length) {
            tbody.innerHTML = '';
            ventasHistorialTable = reiniciarDataTable('ventasHistorialTable', '#tablaVentasHistorial', [[1, 'desc']]);
            return;
        }
        tbody.innerHTML = lista.map(v => `
            <tr>
                <td>
                    <div class="font-semibold text-primary">${esc(v.numero)}</div>
                    <div class="text-xs text-muted">${esc(v.comprobante_numero || 'Sin comprobante')}</div>
                </td>
                <td class="text-sm">${ventaFechaHoraLabel(v.created_at)}</td>
                <td>
                    <div class="font-semibold text-sm">${esc(v.cliente)}</div>
                    <div class="text-xs text-muted">${esc(v.documento || '')}</div>
                </td>
                <td><span class="badge badge-fabricacion">${ventaTipoLabel(v.tipo_comprobante)}</span></td>
                <td class="text-sm" style="text-transform:capitalize">${esc(ventaPagoLabel(v))}</td>
                <td><span class="badge badge-pruebas">${parseInt(v.items_count || 0, 10)} item(s)</span></td>
                <td>${badgeVentaEstado(v.estado, v.anulado)}</td>
                <td>${archivoVentaBtn(v.xml_path, 'XML')}</td>
                <td>${cdrVentaCell(v)}</td>
                <td class="text-right font-semibold">${formatMoney(v.total || 0)}</td>
                <td>${accionesVentaHistorial(v)}</td>
            </tr>
        `).join('');
        ventasHistorialTable = reiniciarDataTable('ventasHistorialTable', '#tablaVentasHistorial', [[1, 'desc']]);
    } catch(e) {
        document.getElementById('ventasResultCount').textContent = 'Error';
        tbody.innerHTML = '<tr><td colspan="11" class="text-center" style="padding:24px;color:var(--danger)">Error al cargar ventas.</td></tr>';
    }
}

function badgeSunatNota(row) {
    const ok = String(row.codigo_respuesta || '') === '0' || !!row.cdr_path;
    const cls = ok ? 'badge-entrega' : 'badge-pruebas';
    const txt = row.mensaje_respuesta || row.estado || (ok ? 'Aceptada' : 'Pendiente');
    return `<span class="badge ${cls}" title="${esc(txt)}" style="max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:inline-block">${esc(txt)}</span>`;
}

function accionesNotaCredito(row) {
    const compId = parseInt(row.id || 0, 10);
    const imprimir = `<button class="btn btn-outline btn-xs" onclick="imprimirNotaCreditoVenta(${compId})" title="Imprimir ticket"><i class="fa fa-print"></i></button>`;
    const reenviar = !row.cdr_path
        ? `<button class="btn btn-outline btn-xs" onclick="reenviarNotaCreditoSunat(${compId})" title="Reenviar nota a SUNAT"><i class="fa fa-paper-plane"></i></button>`
        : '';
    return `<div style="display:flex;gap:5px;flex-wrap:wrap">${imprimir}${reenviar}</div>`;
}

async function cargarNotasCreditoVenta() {
    const params = new URLSearchParams({
        action: 'notas_credito_historial',
        desde: document.getElementById('ventasDesde').value,
        hasta: document.getElementById('ventasHasta').value,
        q: document.getElementById('ventasBuscar').value,
    });
    const tbody = document.getElementById('notasCreditoBody');
    if (notasCreditoTable) {
        notasCreditoTable.destroy();
        notasCreditoTable = null;
        window.notasCreditoTable = null;
    }
    tbody.innerHTML = '<tr><td colspan="10" class="text-center"><div class="loading"><div class="spinner"></div></div></td></tr>';
    try {
        const d = await apiGet(`${BASE}?${params}`);
        const lista = d.notas || [];
        document.getElementById('notasCreditoResultCount').textContent = `${lista.length} resultado(s)`;
        if (!lista.length) {
            tbody.innerHTML = '';
            notasCreditoTable = reiniciarDataTable('notasCreditoTable', '#tablaNotasCredito', [[1, 'desc']]);
            return;
        }
        tbody.innerHTML = lista.map(n => `
            <tr>
                <td>
                    <div class="font-semibold text-primary">${esc(n.numero)}</div>
                    <div class="text-xs text-muted">${esc(n.venta_numero || '')}</div>
                </td>
                <td class="text-sm">${ventaFechaHoraLabel(n.created_at)}</td>
                <td>
                    <div class="font-semibold text-sm">${esc(n.cliente)}</div>
                    <div class="text-xs text-muted">${esc(n.documento || '')}</div>
                </td>
                <td>
                    <div class="font-semibold text-sm">${esc(n.referencia_numero || '-')}</div>
                    <div class="text-xs text-muted">${n.referencia_tipo_codigo === '01' ? 'Factura' : 'Boleta'}</div>
                </td>
                <td>
                    <div class="font-semibold text-sm">${esc(n.codigo_tipo_nota_credito || '')}</div>
                    <div class="text-xs text-muted">${esc(n.motivo_nota_credito || n.descripcion_nota_credito || '')}</div>
                </td>
                <td>${badgeSunatNota(n)}</td>
                <td>${archivoVentaBtn(n.xml_path, 'XML')}</td>
                <td>${cdrVentaCell({ id:n.id, codigo_tipo_documento:'07', cdr_path:n.cdr_path, anulado:false })}</td>
                <td class="text-right font-semibold">${formatMoney(n.total || 0)}</td>
                <td>${accionesNotaCredito(n)}</td>
            </tr>
        `).join('');
        notasCreditoTable = reiniciarDataTable('notasCreditoTable', '#tablaNotasCredito', [[1, 'desc']]);
    } catch(e) {
        document.getElementById('notasCreditoResultCount').textContent = 'Error';
        tbody.innerHTML = '<tr><td colspan="10" class="text-center" style="padding:22px;color:var(--danger)">Error al cargar notas de credito.</td></tr>';
    }
}

async function verDetalleVenta(id) {
    ventaDetalleActual = null;
    document.getElementById('detalleVentaTitulo').innerHTML = '<i class="fa fa-receipt" style="color:var(--primary)"></i> Detalle de venta';
    document.getElementById('detalleVentaBody').innerHTML = '<div class="text-center" style="padding:30px"><div class="loading"><div class="spinner"></div></div></div>';
    Modal.open('modalDetalleVenta');
    try {
        const d = await apiGet(`${BASE}?action=venta_detalle&id=${id}`);
        ventaDetalleActual = d.venta;
        renderDetalleVenta(ventaDetalleActual);
    } catch(e) {
        document.getElementById('detalleVentaBody').innerHTML = '<div class="text-center" style="padding:24px;color:var(--danger)">No se pudo cargar el detalle.</div>';
    }
}

function renderDetalleVenta(v) {
    document.getElementById('detalleVentaTitulo').innerHTML = `<i class="fa fa-receipt" style="color:var(--primary)"></i> Detalle: ${esc(v.numero)}`;
    const items = v.items || [];
    const pagoHtml = renderPagoDetalleVenta(v);
    document.getElementById('detalleVentaBody').innerHTML = `
        <div class="venta-detail-grid">
            <div class="card" style="box-shadow:none;margin:0">
                <div class="card-header"><span class="card-title">Datos de la venta</span></div>
                <div style="padding:14px 16px;font-size:.9rem">
                    <div style="display:flex;justify-content:space-between;margin-bottom:8px"><span class="text-muted">Venta</span><strong>${esc(v.numero)}</strong></div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:8px"><span class="text-muted">Comprobante</span><strong>${esc(v.comprobante_numero || 'Sin comprobante')}</strong></div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:8px"><span class="text-muted">Tipo</span><strong>${ventaTipoLabel(v.tipo_comprobante)}</strong></div>
                    <div style="display:flex;justify-content:space-between;margin-bottom:8px"><span class="text-muted">Fecha</span><strong>${ventaFechaHoraLabel(v.created_at)}</strong></div>
                    <div style="display:flex;justify-content:space-between"><span class="text-muted">Estado</span>${badgeVentaEstado(v.estado, v.anulado)}</div>
                </div>
            </div>
            <div class="card" style="box-shadow:none;margin:0">
                <div class="card-header"><span class="card-title">Cliente y pago</span></div>
                <div style="padding:14px 16px;font-size:.9rem">
                    <div class="font-semibold">${esc(v.cliente?.nombre || 'CLIENTES VARIOS')}</div>
                    <div class="text-xs text-muted" style="margin-bottom:10px">${esc(v.cliente?.documento || '00000000')}</div>
                    ${pagoHtml}
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table><thead><tr>
                <th>Producto</th><th class="text-right">Cantidad</th><th class="text-right">P. Unit.</th>
                <th class="text-right">Dto.</th><th class="text-right">Total</th>
            </tr></thead>
            <tbody>
                ${items.map(i => `
                    <tr>
                        <td>
                            <div class="font-semibold text-sm">${esc(i.descripcion)}</div>
                            <div class="text-xs text-muted">${esc(i.codigo || '')} ${esc(i.unidad_codigo || '')}</div>
                        </td>
                        <td class="text-right">${parseFloat(i.cantidad || 0).toFixed(3)}</td>
                        <td class="text-right">${formatMoney(i.precio_unitario || 0)}</td>
                        <td class="text-right">${formatMoney(i.descuento || 0)}</td>
                        <td class="text-right font-semibold">${formatMoney(i.total || 0)}</td>
                    </tr>
                `).join('')}
            </tbody></table>
        </div>
        <div style="display:flex;justify-content:flex-end;margin-top:14px">
            <div style="min-width:260px;background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:12px">
                <div style="display:flex;justify-content:space-between;margin-bottom:6px"><span class="text-muted">Gravada</span><strong>${formatMoney(v.gravada || v.subtotal || 0)}</strong></div>
                ${parseFloat(v.descuento || 0) > 0 ? `<div style="display:flex;justify-content:space-between;margin-bottom:6px;color:#16a34a"><span>Descuento</span><strong>-${formatMoney(v.descuento)}</strong></div>` : ''}
                <div style="display:flex;justify-content:space-between;margin-bottom:6px"><span class="text-muted">IGV</span><strong>${formatMoney(v.igv || 0)}</strong></div>
                <div style="display:flex;justify-content:space-between;font-size:1.1rem;color:#f97316"><strong>Total</strong><strong>${formatMoney(v.total || 0)}</strong></div>
            </div>
        </div>
    `;
}

function renderPagoDetalleVenta(v) {
    if (v.tipo_pago === 'credito' && Array.isArray(v.cuotas) && v.cuotas.length) {
        return `<div class="text-sm"><strong>Pago a credito</strong>${v.cuotas.map((c, i) =>
            `<div style="display:flex;justify-content:space-between;margin-top:6px"><span>Cuota ${i + 1} (${esc(c.due_date || '')})</span><strong>${formatMoney(c.amount || 0)}</strong></div>`
        ).join('')}</div>`;
    }
    if (Array.isArray(v.payment_breakdown) && v.payment_breakdown.length > 1) {
        return `<div class="text-sm"><strong>Pago mixto</strong>${v.payment_breakdown.map(p =>
            `<div style="display:flex;justify-content:space-between;margin-top:6px;text-transform:capitalize"><span>${esc(p.method || 'Pago')}</span><strong>${formatMoney(p.amount || 0)}</strong></div>`
        ).join('')}</div>`;
    }
    const metodo = Array.isArray(v.payment_breakdown) && v.payment_breakdown.length ? v.payment_breakdown[0].method : (v.tipo_pago || 'efectivo');
    return `<div style="display:flex;justify-content:space-between;text-transform:capitalize"><span>Metodo</span><strong>${esc(metodo)}</strong></div>
        ${parseFloat(v.vuelto || 0) > 0 ? `<div style="display:flex;justify-content:space-between;margin-top:6px"><span>Vuelto</span><strong>${formatMoney(v.vuelto)}</strong></div>` : ''}`;
}

function imprimirDetalleVenta() {
    if (!ventaDetalleActual) return Toast.warning('Primero carga una venta.');
    const html = buildTicketVentaHTML(ventaDetalleActual);
    const w = window.open('', '_blank', 'width=420,height=700');
    w.document.write(`<!DOCTYPE html><html><head><meta charset="utf-8"><style>
        *{box-sizing:border-box} body{font-family:'Courier New',monospace;width:80mm;margin:0 auto;padding:4mm 3mm;color:#000}
        @media print{ @page{size:80mm auto;margin:4mm 3mm} body{padding:0} }
    </style></head><body>${html}</body></html>`);
    w.document.close();
    w.focus();
    setTimeout(() => w.print(), 350);
}

async function imprimirVentaHistorial(id) {
    try {
        const d = await apiGet(`${BASE}?action=venta_detalle&id=${id}`);
        const html = buildTicketVentaHTML(d.venta);
        const w = window.open('', '_blank', 'width=420,height=700');
        w.document.write(`<!DOCTYPE html><html><head><meta charset="utf-8"><style>
            *{box-sizing:border-box} body{font-family:'Courier New',monospace;width:80mm;margin:0 auto;padding:4mm 3mm;color:#000}
            @media print{ @page{size:80mm auto;margin:4mm 3mm} body{padding:0} }
        </style></head><body>${html}</body></html>`);
        w.document.close();
        w.focus();
        setTimeout(() => w.print(), 350);
    } catch(e) {
        Toast.error('No se pudo preparar el ticket.');
    }
}

async function imprimirNotaCreditoVenta(id) {
    try {
        const d = await apiGet(`${BASE}?action=nota_credito_detalle&id=${id}`);
        const html = buildTicketVentaHTML(d.nota);
        const w = window.open('', '_blank', 'width=420,height=700');
        w.document.write(`<!DOCTYPE html><html><head><meta charset="utf-8"><style>
            *{box-sizing:border-box} body{font-family:'Courier New',monospace;width:80mm;margin:0 auto;padding:4mm 3mm;color:#000}
            @media print{ @page{size:80mm auto;margin:4mm 3mm} body{padding:0} }
        </style></head><body>${html}</body></html>`);
        w.document.close();
        w.focus();
        setTimeout(() => w.print(), 350);
    } catch(e) {
        Toast.error('No se pudo preparar el ticket de la nota.');
    }
}

async function cargarTiposNotaCreditoVenta() {
    try {
        const d = await apiGet(`${BASE}?action=tipos_nota_credito`);
        tiposNotaCreditoVenta = d.tipos || [];
        document.getElementById('notaCreditoTipo').innerHTML = tiposNotaCreditoVenta.map(t =>
            `<option value="${t.id}">${esc(t.codigo)} - ${esc(t.descripcion)}</option>`
        ).join('');
    } catch(e) {
        tiposNotaCreditoVenta = [];
    }
}

async function abrirNotaCreditoVenta(comprobanteId, numero) {
    if (!tiposNotaCreditoVenta.length) await cargarTiposNotaCreditoVenta();
    document.getElementById('notaCreditoOrigenId').value = comprobanteId;
    document.getElementById('notaCreditoOrigenLabel').textContent = numero || `Comprobante #${comprobanteId}`;
    document.getElementById('notaCreditoDescripcion').value = 'Anulacion de la operacion';
    Modal.open('modalNotaCreditoVenta');
}

async function emitirNotaCreditoVenta() {
    const btn = document.getElementById('btnEmitirNotaCreditoVenta');
    const payload = {
        action: 'crear_nota_credito',
        comprobante_origen_id: parseInt(document.getElementById('notaCreditoOrigenId').value || '0', 10),
        tipo_nota_credito_id: parseInt(document.getElementById('notaCreditoTipo').value || '0', 10),
        descripcion: document.getElementById('notaCreditoDescripcion').value.trim(),
    };
    if (!payload.comprobante_origen_id || !payload.tipo_nota_credito_id || !payload.descripcion) {
        Toast.warning('Completa motivo y descripcion.');
        return;
    }
    if (!confirm('Emitir nota de credito y enviarla a SUNAT?')) return;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Emitiendo...';
    try {
        const res = await apiPost(BASE, payload);
        if (!res.ok) throw new Error(res.error || 'No se pudo emitir la nota.');
        Toast.success(`Nota de credito ${res.numero} generada.`);
        Modal.close('modalNotaCreditoVenta');
        cargarVentasStats();
        cargarVentasHistorial();
        cargarNotasCreditoVenta();
    } catch(e) {
        Toast.error(e.message || 'Error al emitir nota de credito.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-paper-plane"></i> Emitir nota';
    }
}

async function reenviarVentaSunat(comprobanteId) {
    if (!confirm('Reenviar este comprobante a SUNAT?')) return;
    try {
        const res = await apiPost(BASE, { action:'reenviar_sunat', comprobante_id: comprobanteId });
        if (!res.ok) throw new Error(res.error || 'SUNAT no acepto el comprobante.');
        Toast.success('Comprobante reenviado a SUNAT.');
        cargarVentasHistorial();
        cargarNotasCreditoVenta();
    } catch(e) {
        Toast.error(e.message || 'Error al reenviar a SUNAT.');
    }
}

async function reenviarNotaCreditoSunat(comprobanteId) {
    await reenviarVentaSunat(comprobanteId);
}

async function cargarCotizaciones() {
    const params = new URLSearchParams({ action:'cotizaciones_listar',
        buscar:document.getElementById('buscarCot').value,
        estado:document.getElementById('filtroEstadoCot').value });
    const d = await apiGet(`${BASE}?${params}`);
    const tbody = document.getElementById('cotizacionesBody');
    const lista = d.cotizaciones||[];
    if (!lista.length) { tbody.innerHTML='<tr><td colspan="9" class="text-center text-muted">Sin cotizaciones.</td></tr>'; return; }
    tbody.innerHTML = lista.map(c => {
        const vencida = c.estado==='enviada' && c.fecha_vigencia && c.fecha_vigencia < new Date().toISOString().split('T')[0];
        return `<tr>
            <td class="font-semibold text-primary">${esc(c.numero)}</td>
            <td class="text-sm">${esc(c.cliente_nombre||'-')}</td>
            <td class="text-xs">${formatDate(c.fecha_emision)}</td>
            <td class="text-xs ${vencida?'text-danger font-bold':''}">${formatDate(c.fecha_vigencia)}</td>
            <td><span class="badge ${estadosCot[c.estado]||'badge-normal'}">${c.estado}</span></td>
            <td class="text-right text-sm">${formatMoney(c.subtotal)}</td>
            <td class="text-right text-sm">${formatMoney(c.igv)}</td>
            <td class="text-right font-bold">${formatMoney(c.total)}</td>
            <td style="white-space:nowrap">
                <button class="btn btn-outline btn-xs" onclick="editarCotizacion(${c.id})" title="Editar"><i class="fa fa-pencil"></i></button>
                ${c.estado==='borrador'||c.estado==='enviada' ? `
                <button class="btn btn-outline btn-xs" onclick="cambiarEstadoCot(${c.id},'aprobada')" title="Aprobar"><i class="fa fa-check" style="color:var(--success)"></i></button>
                <button class="btn btn-outline btn-xs" onclick="cambiarEstadoCot(${c.id},'rechazada')" title="Rechazar"><i class="fa fa-times" style="color:var(--danger)"></i></button>
                ` : ''}
                <button class="btn btn-outline btn-xs" onclick="eliminarCotizacion(${c.id})" title="Eliminar"><i class="fa fa-trash"></i></button>
            </td>
        </tr>`;
    }).join('');
}

let combosLoaded = false;
let productosCache = [];
let proyectosCache = [];

function clienteVentaLabel(c) {
    const nombre = c.razon_social || c.nombre_completo || 'Cliente';
    const doc = c.numero_documento || c.ruc_dni || '';
    return doc ? `${nombre} - ${doc}` : nombre;
}

function esClienteRuc(c) {
    const doc = String(c?.numero_documento || c?.ruc_dni || '').replace(/\D/g, '');
    return c?.tipo_documento_codigo === '6' || doc.length === 11;
}

function syncVentaClienteSelect(cliente) {
    selectedClienteVenta = cliente || null;
    const $select = window.jQuery ? window.jQuery('#ventaCliente') : null;
    if (!$select || !$select.length || !$select.data('select2')) {
        if (cliente) document.getElementById('ventaCliente').value = cliente.id;
        return;
    }
    if (!cliente) {
        $select.val(null).trigger('change.select2');
        return;
    }
    const value = String(cliente.id);
    let option = $select.find(`option[value="${value}"]`);
    if (!option.length) {
        option = window.jQuery(new Option(clienteVentaLabel(cliente), value, true, true));
        option[0]._cliente = cliente;
        $select.append(option);
    } else {
        option.prop('selected', true);
        option[0]._cliente = cliente;
    }
    $select.val(value).trigger('change.select2');
}

function setupVentaSelect2() {
    if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.select2 === 'undefined') return;
    const $select = window.jQuery('#ventaCliente');
    if (!$select.length || $select.data('select2')) return;
    $select.select2({
        width: '100%',
        placeholder: 'Buscar cliente por nombre, DNI o RUC',
        allowClear: false,
        templateResult: item => item.text,
        templateSelection: item => item.text,
    });
    $select.on('select2:select', event => {
        const option = event.params?.data?.element;
        selectedClienteVenta = option?._cliente || clientesVenta.find(c => String(c.id) === String(event.params.data.id)) || null;
    });
}

async function cargarClientesVenta(tipo = document.getElementById('ventaTipoComprobante')?.value || 'boleta') {
    const d = await apiGet(`${BASE}?action=clientes_combo&tipo_comprobante=${encodeURIComponent(tipo)}`);
    clientesVenta = d.clientes || [];
    const select = document.getElementById('ventaCliente');
    select.innerHTML = clientesVenta.map(c => `<option value="${c.id}">${esc(clienteVentaLabel(c))}</option>`).join('');
    Array.from(select.options).forEach(opt => {
        opt._cliente = clientesVenta.find(c => String(c.id) === String(opt.value)) || null;
    });

    if (window.jQuery && window.jQuery.fn.select2) {
        const $select = window.jQuery('#ventaCliente');
        if ($select.data('select2')) $select.trigger('change.select2');
    }

    let elegido = null;
    if (tipo === 'boleta') {
        elegido = clientesVenta.find(c => String(c.numero_documento || c.ruc_dni || '') === '00000000')
            || clientesVenta.find(c => String(c.razon_social || c.nombre_completo || '').toUpperCase().includes('CLIENTES VARIOS'))
            || clientesVenta[0] || null;
    } else if (tipo === 'factura') {
        elegido = clientesVenta.find(esClienteRuc) || null;
    } else {
        elegido = defaultClienteVenta || clientesVenta[0] || null;
    }
    syncVentaClienteSelect(elegido);
}

async function cargarClientePorDefectoVenta() {
    try {
        const d = await apiGet(`${BASE}?action=default_cliente`);
        defaultClienteVenta = d.cliente || null;
    } catch(e) {
        defaultClienteVenta = null;
    }
}

async function onVentaTipoComprobanteChange() {
    actualizarSeriesVenta();
    await cargarClientesVenta();
}

function actualizarSeriesVenta() {
    const tipo = document.getElementById('ventaTipoComprobante')?.value || 'boleta';
    const select = document.getElementById('ventaSerie');
    const info = document.getElementById('ventaComprobanteInfo');
    const lista = seriesVenta[tipo] || [];

    if (!select) return;
    select.innerHTML = lista.length
        ? lista.map(s => `<option value="${esc(s.serie)}">${esc(s.serie)} - siguiente ${String((parseInt(s.ultimo_numero) || 0) + 1).padStart(8, '0')}</option>`).join('')
        : '<option value="">Sin serie activa</option>';

    if (info) {
        const etiquetas = {
            boleta: 'Boleta electronica. Cliente por defecto: CLIENTES VARIOS.',
            factura: 'Factura electronica. Solo clientes con RUC de 11 digitos.',
            nota_venta: 'Nota de venta interna. Permite seleccionar cualquier cliente.',
        };
        info.textContent = etiquetas[tipo] || 'Se generara el correlativo automaticamente.';
    }
}

async function cargarCombos() {
    const [dc, dm, dp, dpr, ds] = await Promise.all([
        apiGet(`${BASE}?action=clientes_combo`),
        apiGet(`${BASE}?action=maquinarias_combo`),
        apiGet(`${BASE}?action=proyectos_combo`),
        apiGet(`${BASE}?action=productos_combo`),
        apiGet(`${BASE}?action=series_disponibles`)
    ]);

    const opts = '<option value="">Sin cliente / clientes varios</option>' + (dc.clientes||[]).map(c=>`<option value="${c.id}">${esc(c.razon_social || c.nombre_completo || 'Cliente')}</option>`).join('');
    document.getElementById('cotCliente').innerHTML   = opts;
    document.getElementById('maqCliente').innerHTML   = opts;
    document.getElementById('ovCliente').innerHTML    = opts;
    seriesVenta = { nota_venta: [], boleta: [], factura: [] };
    (ds.series || []).forEach(s => {
        if (!seriesVenta[s.tipo]) seriesVenta[s.tipo] = [];
        seriesVenta[s.tipo].push(s);
    });
    actualizarSeriesVenta();
    setupVentaSelect2();
    await cargarClientePorDefectoVenta();
    await cargarClientesVenta();
    combosLoaded = true;

    document.getElementById('srvMaquinaria').innerHTML = '<option value="">Seleccionar</option>' +
        (dm.maquinarias||[]).map(m=>`<option value="${m.id}">${esc(m.label)}</option>`).join('');

    proyectosCache = dp.proyectos || [];
    productosCache = dpr.productos || [];
    renderProyectosOV();
}

function abrirModalCotizacion(clienteId=null) {
    document.getElementById('cotId').value='';
    document.getElementById('cotObs').value='';
    document.getElementById('cotFecha').value=new Date().toISOString().split('T')[0];
    document.getElementById('cotVigencia').value=30;
    document.getElementById('cotCliente').value=clienteId||'';
    document.getElementById('cotDetalleBody').innerHTML='';
    recalcularCot();
    agregarLineaCot();
    document.getElementById('tituloCot').textContent='Nueva Cotizacion';
    Modal.open('modalCotizacion');
}

async function editarCotizacion(id) {
    const d = await apiGet(`${BASE}?action=cotizacion_obtener&id=${id}`);
    const c = d.cotizacion;
    document.getElementById('cotId').value=c.id;
    document.getElementById('cotCliente').value=c.cliente_id||'';
    document.getElementById('cotFecha').value=c.fecha_emision||'';
    document.getElementById('cotVigencia').value=c.vigencia_dias||30;
    document.getElementById('cotObs').value=c.observaciones||'';
    document.getElementById('cotDetalleBody').innerHTML='';
    (d.detalles||[]).forEach(det => agregarLineaCot(det));
    recalcularCot();
    document.getElementById('tituloCot').textContent='Editar Cotizacion - '+c.numero;
    Modal.open('modalCotizacion');
}

function agregarLineaCot(det=null) {
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" class="form-control" placeholder="Descripcion del producto/servicio..." value="${esc(det?.descripcion||'')}" onchange="recalcularCot()"></td>
        <td><input type="number" class="form-control text-right" value="${det?.cantidad||1}" min="0.01" step="0.01" style="width:80px" onchange="recalcularLinea(this)"></td>
        <td><input type="number" class="form-control text-right" value="${det?.precio_unitario||0}" min="0" step="0.01" onchange="recalcularLinea(this)"></td>
        <td><input type="number" class="form-control text-right" value="${det?.descuento||0}" min="0" max="100" step="0.01" style="width:70px" onchange="recalcularLinea(this)"></td>
        <td class="text-right font-semibold lin-sub" style="padding:8px 14px">${formatMoney(det?.subtotal||0)}</td>
        <td class="text-center"><button type="button" class="btn btn-outline btn-xs" onclick="this.closest('tr').remove();recalcularCot()"><i class="fa fa-times"></i></button></td>`;
    document.getElementById('cotDetalleBody').appendChild(tr);
}

function recalcularLinea(input) {
    const tr  = input.closest('tr');
    const qty = parseFloat(tr.querySelectorAll('input')[1].value)||0;
    const pu  = parseFloat(tr.querySelectorAll('input')[2].value)||0;
    const dto = parseFloat(tr.querySelectorAll('input')[3].value)||0;
    const sub = qty * pu * (1 - dto/100);
    tr.querySelector('.lin-sub').textContent = formatMoney(sub);
    recalcularCot();
}

function recalcularCot() {
    let sub = 0;
    document.querySelectorAll('#cotDetalleBody tr').forEach(tr => {
        const qty = parseFloat(tr.querySelectorAll('input')[1]?.value)||0;
        const pu  = parseFloat(tr.querySelectorAll('input')[2]?.value)||0;
        const dto = parseFloat(tr.querySelectorAll('input')[3]?.value)||0;
        sub += qty * pu * (1 - dto/100);
    });
    const igv   = sub * 0.18;
    const total = sub + igv;
    document.getElementById('cotSubtotal').textContent = formatMoney(sub);
    document.getElementById('cotIgv').textContent      = formatMoney(igv);
    document.getElementById('cotTotal').textContent    = formatMoney(total);
}

async function guardarCotizacion(estado) {
    const detalles = [];
    document.querySelectorAll('#cotDetalleBody tr').forEach(tr => {
        const inputs = tr.querySelectorAll('input');
        const desc = inputs[0].value.trim();
        if (!desc) return;
        const qty = parseFloat(inputs[1].value)||0;
        const pu  = parseFloat(inputs[2].value)||0;
        const dto = parseFloat(inputs[3].value)||0;
        detalles.push({ descripcion:desc, cantidad:qty, precio_unitario:pu, descuento:dto, subtotal:qty*pu*(1-dto/100) });
    });
    if (!detalles.length) { Toast.warning('Agregue al menos un item.'); return; }
    const res = await apiPost(BASE, { action:'cotizacion_guardar',
        id:document.getElementById('cotId').value, estado,
        cliente_id:document.getElementById('cotCliente').value,
        fecha_emision:document.getElementById('cotFecha').value,
        vigencia_dias:document.getElementById('cotVigencia').value,
        observaciones:document.getElementById('cotObs').value, detalles });
    if (res.ok) { Toast.success('Cotizacion guardada.'); Modal.close('modalCotizacion'); cargarCotizaciones(); }
    else Toast.error(res.error||'Error.');
}

async function cambiarEstadoCot(id, estado) {
    const labels = { aprobada:'Aprobar esta cotizacion?', rechazada:'Rechazar esta cotizacion?' };
    if (!confirm(labels[estado])) return;
    const res = await apiPost(BASE,{action:'cotizacion_estado',id,estado});
    if (res.ok) { Toast.success('Estado actualizado.'); cargarCotizaciones(); }
}

async function eliminarCotizacion(id) {
    if (!confirm('Eliminar esta cotizacion?')) return;
    const res = await apiPost(BASE,{action:'cotizacion_eliminar',id});
    if (res.ok) { Toast.success('Cotizacion eliminada.'); cargarCotizaciones(); }
}

// MAQUINARIAS
async function cargarMaquinarias() {
    const params = new URLSearchParams({action:'maquinarias_listar', buscar:document.getElementById('buscarMaq').value});
    const d = await apiGet(`${BASE}?${params}`);
    const tbody = document.getElementById('maquinariasBody');
    maquinariasVenta = d.maquinarias||[];
    const lista = maquinariasVenta;
    if (!lista.length) { tbody.innerHTML='<tr><td colspan="9" class="text-center text-muted">Sin maquinarias registradas.</td></tr>'; return; }
    const gBadge = { vigente:'badge-entrega', vencida:'badge-retrasado', sin_garantia:'badge-cancelado' };
    const vBadge = { disponible:'badge-entrega', vendida:'badge-fabricacion', reservada:'badge-pruebas' };
    tbody.innerHTML = lista.map(m => `<tr>
        <td class="text-xs text-muted">${esc(m.codigo||'-')}</td>
        <td>
            <div class="font-semibold">${esc(m.modelo||'-')}</div>
            <div class="text-xs text-muted">${esc(m.producto_nombre || m.producto_codigo || 'Producto asociado pendiente')}</div>
        </td>
        <td class="text-xs">${esc(m.numero_serie||'-')}</td>
        <td class="text-sm">${esc(m.cliente_nombre||'-')}</td>
        <td class="text-right font-semibold">${formatMoney(m.costo_total_calc || m.costo_total || 0)}</td>
        <td class="text-right font-bold" style="color:var(--primary)">${formatMoney(m.precio_venta_calc || m.precio_venta || 0)}</td>
        <td>
            <span class="badge ${vBadge[m.estado_venta]||'badge-normal'}">${esc((m.estado_venta||'disponible').replace('_',' '))}</span>
            ${m.fecha_venta?`<div class="text-xs text-muted">${formatDate(m.fecha_venta)}</div>`:''}
        </td>
        <td>
            <span class="badge ${gBadge[m.estado_garantia]||'badge-normal'}">${esc((m.estado_garantia||'sin_garantia').replace('_',' '))}</span>
            <div class="text-xs text-muted">${formatDate(m.garantia_hasta)}${m.dias_garantia!==null&&m.estado_garantia==='vigente'?` (${m.dias_garantia}d)`:''}</div>
        </td>
        <td>
            ${(m.estado_venta||'disponible') !== 'vendida'
                ? `<button class="btn btn-success btn-xs" onclick="abrirVentaMaquinaria(${m.id})" title="Vender maquinaria"><i class="fa fa-cash-register"></i></button>`
                : ''}
            <button class="btn btn-outline btn-xs" onclick="editarMaquinaria(${m.id})"><i class="fa fa-pencil"></i></button>
            ${(m.estado_venta||'disponible') === 'vendida'
                ? `<button class="btn btn-outline btn-xs" onclick="nuevoServicioMaq(${m.id})" title="Nuevo servicio"><i class="fa fa-screwdriver-wrench"></i></button>`
                : ''}
        </td>
    </tr>`).join('');
}

function abrirModalMaquinaria() {
    ['maqId','maqCodigo','maqModelo','maqSerie','maqDesc'].forEach(id=>document.getElementById(id).value='');
    document.getElementById('maqFechaVenta').value=''; document.getElementById('maqGarantia').value=12;
    document.getElementById('maqCosto').value='0.00'; document.getElementById('maqPrecio').value='0.00';
    document.getElementById('maqEstadoVenta').value='disponible';
    document.getElementById('maqCliente').value=''; document.getElementById('tituloMaq').textContent='Registrar Maquinaria';
    Modal.open('modalMaquinaria');
}

async function editarMaquinaria(id) {
    if (!maquinariasVenta.length) {
        const d = await apiGet(`${BASE}?action=maquinarias_listar&buscar=`);
        maquinariasVenta = d.maquinarias || [];
    }
    const m = maquinariasVenta.find(x=>x.id==id); if(!m) return;
    document.getElementById('maqId').value=m.id; document.getElementById('maqCliente').value=m.cliente_id||'';
    document.getElementById('maqCodigo').value=m.codigo||''; document.getElementById('maqModelo').value=m.modelo||'';
    document.getElementById('maqSerie').value=m.numero_serie||''; document.getElementById('maqFechaVenta').value=m.fecha_venta||'';
    document.getElementById('maqCosto').value=parseFloat(m.costo_total_calc || m.costo_total || 0).toFixed(2);
    document.getElementById('maqPrecio').value=parseFloat(m.precio_venta_calc || m.precio_venta || 0).toFixed(2);
    document.getElementById('maqEstadoVenta').value=m.estado_venta || 'disponible';
    document.getElementById('maqGarantia').value=m.garantia_meses||12; document.getElementById('maqDesc').value=m.descripcion||'';
    document.getElementById('tituloMaq').textContent='Editar Maquinaria';
    Modal.open('modalMaquinaria');
}

async function guardarMaquinaria() {
    if (!document.getElementById('maqModelo').value.trim()) { Toast.warning('El modelo es obligatorio.'); return; }
    const res = await apiPost(BASE,{action:'maquinaria_guardar', id:document.getElementById('maqId').value,
        cliente_id:document.getElementById('maqCliente').value, codigo:document.getElementById('maqCodigo').value,
        modelo:document.getElementById('maqModelo').value, numero_serie:document.getElementById('maqSerie').value,
        costo_total:document.getElementById('maqCosto').value, precio_venta:document.getElementById('maqPrecio').value,
        estado_venta:document.getElementById('maqEstadoVenta').value,
        fecha_venta:document.getElementById('maqFechaVenta').value, garantia_meses:document.getElementById('maqGarantia').value,
        descripcion:document.getElementById('maqDesc').value});
    if (res.ok) { Toast.success('Maquinaria guardada.'); Modal.close('modalMaquinaria'); cargarMaquinarias(); }
    else Toast.error(res.error||'Error.');
}

async function cargarClientesMaquinariaVenta() {
    const tipo = document.getElementById('maqVentaTipo').value || 'boleta';
    const d = await apiGet(`${BASE}?action=clientes_combo&tipo_comprobante=${encodeURIComponent(tipo)}`);
    const clientes = d.clientes || [];
    const select = document.getElementById('maqVentaCliente');
    select.innerHTML = clientes.map(c => `<option value="${c.id}">${esc(clienteVentaLabel(c))}</option>`).join('');
    Array.from(select.options).forEach(opt => {
        opt._cliente = clientes.find(c => String(c.id) === String(opt.value)) || null;
    });
    const elegido = tipo === 'factura'
        ? clientes.find(esClienteRuc)
        : (clientes.find(c => String(c.numero_documento || c.ruc_dni || '') === '00000000')
            || clientes.find(c => String(c.razon_social || c.nombre_completo || '').toUpperCase().includes('CLIENTES VARIOS'))
            || clientes[0] || null);
    if (elegido) select.value = elegido.id;
}

async function abrirVentaMaquinaria(id) {
    if (!maquinariasVenta.length) await cargarMaquinarias();
    const m = maquinariasVenta.find(x => String(x.id) === String(id));
    if (!m) { Toast.warning('No se encontro la maquinaria.'); return; }
    if ((m.estado_venta || 'disponible') === 'vendida') { Toast.warning('Esta maquinaria ya esta vendida.'); return; }
    if (!m.producto_id) { Toast.warning('Guarda la maquinaria para generar su producto asociado antes de vender.'); return; }

    maquinariaVentaActual = m;
    document.getElementById('maqVentaId').value = m.id;
    document.getElementById('maqVentaTitulo').textContent = m.modelo || 'Maquinaria';
    document.getElementById('maqVentaDetalle').textContent = `${m.codigo || 'Sin codigo'} | Serie: ${m.numero_serie || '-'} | Stock: ${parseFloat(m.stock_actual || 0).toFixed(3)}`;
    document.getElementById('maqVentaPrecio').value = parseFloat(m.precio_venta_calc || m.precio_venta || 0).toFixed(2);
    document.getElementById('maqVentaTipo').value = 'boleta';
    document.getElementById('maqVentaObs').value = `Venta de maquinaria ${m.modelo || ''} ${m.numero_serie ? 'S/N ' + m.numero_serie : ''}`.trim();
    await cargarClientesMaquinariaVenta();
    Modal.open('modalVentaMaquinaria');
}

async function prepararCobroMaquinaria() {
    const m = maquinariaVentaActual;
    if (!m) { Toast.warning('Selecciona una maquinaria.'); return; }
    const tipo = document.getElementById('maqVentaTipo').value || 'boleta';
    const precio = parseFloat(document.getElementById('maqVentaPrecio').value) || 0;
    if (precio <= 0) { Toast.warning('Ingresa un precio de venta valido.'); return; }

    const opt = document.getElementById('maqVentaCliente').selectedOptions[0];
    const cliente = opt?._cliente || null;
    if (tipo === 'factura' && !esClienteRuc(cliente)) {
        Toast.warning('Para factura selecciona un cliente con RUC.');
        return;
    }

    document.getElementById('ventaTipoComprobante').value = tipo;
    await onVentaTipoComprobanteChange();
    syncVentaClienteSelect(cliente);
    document.getElementById('ventaObs').value = document.getElementById('maqVentaObs').value;
    maquinariaVentaActual = m;
    carritoVenta = [{
        id: Number(m.producto_id),
        codigo: m.producto_codigo || m.codigo || '',
        nombre: m.producto_nombre || m.modelo || 'Maquinaria',
        unidad: 'unidad',
        stock: parseFloat(m.stock_actual || 1),
        precio,
        cantidad: 1,
        descuento: 0,
        esServicio: false,
        product_type: 'product',
        unidad_codigo: 'NIU',
        afectacion_igv_codigo: '10',
        porcentaje_igv: 18,
        incluye_igv: true
    }];
    renderCarrito();
    recalcularVentaTotals();
    Modal.close('modalVentaMaquinaria');
    activarTab('tabVenta');
    setTimeout(() => abrirModalCobroVenta(), 120);
}

// SERVICIO TECNICO
async function cargarServicios() {
    const params = new URLSearchParams({action:'servicios_listar', estado:document.getElementById('filtroEstadoSrv').value});
    const d = await apiGet(`${BASE}?${params}`);
    const tbody = document.getElementById('serviciosBody');
    const lista = d.servicios||[];
    if (!lista.length) { tbody.innerHTML='<tr><td colspan="9" class="text-center text-muted">Sin servicios registrados.</td></tr>'; return; }
    tbody.innerHTML = lista.map(s=>`<tr>
        <td class="text-xs">${formatDate(s.fecha_solicitud)}</td>
        <td class="text-sm">${esc(s.cliente_nombre||'-')}</td>
        <td class="text-sm">${esc(s.modelo||'-')} <span class="text-xs text-muted">${esc(s.numero_serie||'')}</span></td>
        <td><span class="badge badge-normal">${s.tipo}</span></td>
        <td class="text-sm">${esc(s.descripcion||'-')}</td>
        <td class="text-sm">${esc(s.tecnico_nombre||'-')}</td>
        <td><span class="badge ${estadosSrv[s.estado]||'badge-normal'}">${s.estado?.replace('_',' ')}</span></td>
        <td class="text-right font-semibold">${formatMoney(s.costo)}</td>
        <td>
            <button class="btn btn-outline btn-xs" onclick="editarServicio(${s.id})"><i class="fa fa-pencil"></i></button>
            ${s.estado==='pendiente'?`<button class="btn btn-outline btn-xs" onclick="estadoServicio(${s.id},'en_proceso')" title="Iniciar"><i class="fa fa-play" style="color:var(--primary)"></i></button>`:''}
            ${s.estado==='en_proceso'?`<button class="btn btn-outline btn-xs" onclick="estadoServicio(${s.id},'completado')" title="Completar"><i class="fa fa-check" style="color:var(--success)"></i></button>`:''}
        </td>
    </tr>`).join('');
}

function abrirModalServicio(maqId=null) {
    ['srvId','srvDesc','srvDiag','srvSol'].forEach(id=>document.getElementById(id).value='');
    document.getElementById('srvTipo').value='correctivo';
    document.getElementById('srvTecnico').value='';
    document.getElementById('srvCosto').value=0;
    if (maqId) document.getElementById('srvMaquinaria').value=maqId;
    else document.getElementById('srvMaquinaria').value='';
    document.getElementById('tituloSrv').textContent='Nuevo Servicio Tecnico';
    Modal.open('modalServicio');
}

function nuevoServicioMaq(maqId) { activarTab('tabServicio'); setTimeout(()=>abrirModalServicio(maqId),100); }

async function editarServicio(id) {
    const d = await apiGet(`${BASE}?action=servicios_listar`);
    const s = (d.servicios||[]).find(x=>x.id==id); if(!s) return;
    document.getElementById('srvId').value=s.id; document.getElementById('srvMaquinaria').value=s.maquinaria_id||'';
    document.getElementById('srvTipo').value=s.tipo||'correctivo'; document.getElementById('srvDesc').value=s.descripcion||'';
    document.getElementById('srvDiag').value=s.diagnostico||''; document.getElementById('srvSol').value=s.solucion||'';
    document.getElementById('srvTecnico').value=s.tecnico_id||''; document.getElementById('srvCosto').value=s.costo||0;
    document.getElementById('tituloSrv').textContent='Editar Servicio';
    Modal.open('modalServicio');
}

async function guardarServicio() {
    if (!document.getElementById('srvDesc').value.trim()) { Toast.warning('La descripcion es obligatoria.'); return; }
    const res = await apiPost(BASE,{action:'servicio_guardar', id:document.getElementById('srvId').value,
        maquinaria_id:document.getElementById('srvMaquinaria').value, tipo:document.getElementById('srvTipo').value,
        descripcion:document.getElementById('srvDesc').value, diagnostico:document.getElementById('srvDiag').value,
        solucion:document.getElementById('srvSol').value, tecnico_id:document.getElementById('srvTecnico').value,
        costo:document.getElementById('srvCosto').value});
    if (res.ok) { Toast.success('Servicio guardado.'); Modal.close('modalServicio'); cargarServicios(); }
    else Toast.error(res.error||'Error.');
}

async function estadoServicio(id, estado) {
    const res = await apiPost(BASE,{action:'servicio_estado',id,estado});
    if (res.ok) { Toast.success('Estado actualizado.'); cargarServicios(); }
}

// ORDENES DE VENTA
const estadosOV = {
    borrador:  'badge-cancelado',
    confirmada:'badge-fabricacion',
    facturada: 'badge-pruebas',
    cobrada:   'badge-entrega',
    cancelada: 'badge-retrasado'
};
const labelOV = { borrador:'Borrador', confirmada:'Confirmada', facturada:'Facturada', cobrada:'Cobrada', cancelada:'Cancelada' };

function renderProyectosOV(clienteId=null) {
    const filtrados = clienteId
        ? proyectosCache.filter(p => p.cliente_id == clienteId)
        : proyectosCache;
    document.getElementById('ovProyecto').innerHTML =
        '<option value="">- Sin proyecto -</option>' +
        filtrados.map(p=>`<option value="${p.id}">${esc(p.codigo)} - ${esc(p.nombre)}</option>`).join('');
}

function filtrarProyectosCliente() {
    renderProyectosOV(document.getElementById('ovCliente').value || null);
}

async function cargarOrdenes() {
    const params = new URLSearchParams({
        action: 'ordenes_listar',
        buscar: document.getElementById('buscarOV').value,
        estado: document.getElementById('filtroEstadoOV').value
    });
    const d = await apiGet(`${BASE}?${params}`);
    const tbody = document.getElementById('ordenesBody');
    const lista = d.ordenes || [];
    if (!lista.length) { tbody.innerHTML='<tr><td colspan="8" class="text-center text-muted">Sin ordenes de venta.</td></tr>'; return; }
    tbody.innerHTML = lista.map(o => `<tr>
        <td class="font-semibold text-primary text-sm">${esc(o.numero)}</td>
        <td class="text-sm">${esc(o.cliente_nombre||'-')}</td>
        <td class="text-sm">${o.proyecto_codigo ? `<span class="text-xs font-semibold">${esc(o.proyecto_codigo)}</span><div class="text-xs text-muted">${esc(o.proyecto_nombre)}</div>` : '-'}</td>
        <td class="text-xs">${formatDate(o.fecha_emision)}</td>
        <td class="text-xs ${o.fecha_entrega && o.fecha_entrega < new Date().toISOString().split('T')[0] && o.estado !== 'cobrada' ? 'text-danger font-bold' : ''}">${formatDate(o.fecha_entrega)}</td>
        <td><span class="badge ${estadosOV[o.estado]||'badge-normal'}">${labelOV[o.estado]||o.estado}</span></td>
        <td class="text-right font-bold">${formatMoney(o.total)}</td>
        <td style="white-space:nowrap">
            <button class="btn btn-outline btn-xs" onclick="editarOrden(${o.id})" title="Editar"><i class="fa fa-pencil"></i></button>
            ${o.estado==='borrador' ? `<button class="btn btn-outline btn-xs" onclick="cambiarEstadoOrden(${o.id},'confirmada')" title="Confirmar"><i class="fa fa-circle-check" style="color:var(--success)"></i></button>` : ''}
            ${o.estado==='confirmada' ? `<button class="btn btn-outline btn-xs" onclick="cambiarEstadoOrden(${o.id},'facturada')" title="Marcar facturada"><i class="fa fa-file-invoice-dollar" style="color:var(--primary)"></i></button>` : ''}
            ${o.estado==='facturada' ? `<button class="btn btn-outline btn-xs" onclick="cambiarEstadoOrden(${o.id},'cobrada')" title="Marcar cobrada"><i class="fa fa-sack-dollar" style="color:var(--success)"></i></button>` : ''}
            ${['borrador','cancelada'].includes(o.estado) ? `<button class="btn btn-outline btn-xs" onclick="eliminarOrden(${o.id})" title="Eliminar"><i class="fa fa-trash"></i></button>` : ''}
        </td>
    </tr>`).join('');
}

function abrirModalOrden() {
    document.getElementById('ovId').value = '';
    document.getElementById('ovObs').value = '';
    document.getElementById('ovFecha').value = new Date().toISOString().split('T')[0];
    document.getElementById('ovEntrega').value = '';
    document.getElementById('ovCliente').value = '';
    document.getElementById('ovCotizacion').innerHTML = '<option value="">- Sin cotizacion -</option>';
    renderProyectosOV();
    document.getElementById('ovItemsBody').innerHTML = '';
    recalcularOrden();
    agregarItemOrden();
    document.getElementById('tituloOrden').textContent = 'Nueva Orden de Venta';
    Modal.open('modalOrden');
}

async function editarOrden(id) {
    const d = await apiGet(`${BASE}?action=orden_obtener&id=${id}`);
    const o = d.orden;
    document.getElementById('ovId').value          = o.id;
    document.getElementById('ovCliente').value     = o.cliente_id || '';
    document.getElementById('ovFecha').value       = o.fecha_emision || '';
    document.getElementById('ovEntrega').value     = o.fecha_entrega || '';
    document.getElementById('ovObs').value         = o.observaciones || '';
    renderProyectosOV(o.cliente_id);
    document.getElementById('ovProyecto').value    = o.proyecto_id || '';
    document.getElementById('ovCotizacion').innerHTML = '<option value="">- Sin cotizacion -</option>';
    document.getElementById('ovItemsBody').innerHTML = '';
    (d.items||[]).forEach(it => agregarItemOrden(it));
    recalcularOrden();
    document.getElementById('tituloOrden').textContent = 'Editar Orden - ' + o.numero;
    Modal.open('modalOrden');
}

function agregarItemOrden(it=null) {
    const opts = productosCache.map(p =>
        `<option value="${p.id}" data-precio="${p.precio_venta}" data-unidad="${esc(p.unidad)}">${esc(p.codigo)} - ${esc(p.nombre)}</option>`
    ).join('');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <select class="form-control" onchange="onProductoSelect(this)">
                <option value="">- Seleccionar producto -</option>
                ${opts}
            </select>
        </td>
        <td class="text-sm text-muted ov-unidad" style="padding:8px 14px">-</td>
        <td><input type="number" class="form-control text-right" value="${it?.cantidad||1}" min="0.001" step="0.001" style="width:80px" onchange="recalcularItemOrden(this)"></td>
        <td><input type="number" class="form-control text-right" value="${it?.precio_unitario||0}" min="0" step="0.01" onchange="recalcularItemOrden(this)"></td>
        <td><input type="number" class="form-control text-right" value="${it?.descuento||0}" min="0" max="100" step="0.01" style="width:70px" onchange="recalcularItemOrden(this)"></td>
        <td class="text-right font-semibold ov-sub" style="padding:8px 14px">${formatMoney(it?.subtotal||0)}</td>
        <td class="text-center"><button type="button" class="btn btn-outline btn-xs" onclick="this.closest('tr').remove();recalcularOrden()"><i class="fa fa-times"></i></button></td>`;
    document.getElementById('ovItemsBody').appendChild(tr);
    if (it?.producto_id) {
        const sel = tr.querySelector('select');
        sel.value = it.producto_id;
        tr.querySelector('.ov-unidad').textContent = it.unidad || '-';
    }
}

function onProductoSelect(sel) {
    const opt = sel.options[sel.selectedIndex];
    const tr  = sel.closest('tr');
    tr.querySelector('.ov-unidad').textContent = opt.dataset.unidad || '-';
    const precioInput = tr.querySelectorAll('input')[1];
    if (!precioInput.value || parseFloat(precioInput.value) === 0) {
        precioInput.value = opt.dataset.precio || 0;
    }
    recalcularItemOrden(sel);
}

function recalcularItemOrden(el) {
    const tr  = el.closest('tr');
    const qty = parseFloat(tr.querySelectorAll('input')[0].value) || 0;
    const pu  = parseFloat(tr.querySelectorAll('input')[1].value) || 0;
    const dto = parseFloat(tr.querySelectorAll('input')[2].value) || 0;
    tr.querySelector('.ov-sub').textContent = formatMoney(qty * pu * (1 - dto/100));
    recalcularOrden();
}

function recalcularOrden() {
    let sub = 0;
    document.querySelectorAll('#ovItemsBody tr').forEach(tr => {
        const inputs = tr.querySelectorAll('input');
        const qty = parseFloat(inputs[0]?.value) || 0;
        const pu  = parseFloat(inputs[1]?.value) || 0;
        const dto = parseFloat(inputs[2]?.value) || 0;
        sub += qty * pu * (1 - dto/100);
    });
    const igv   = sub * 0.18;
    const total = sub + igv;
    document.getElementById('ovSubtotal').textContent = formatMoney(sub);
    document.getElementById('ovIgv').textContent      = formatMoney(igv);
    document.getElementById('ovTotal').textContent    = formatMoney(total);
}

async function guardarOrden(estado) {
    const items = [];
    let valido = true;
    document.querySelectorAll('#ovItemsBody tr').forEach(tr => {
        const sel    = tr.querySelector('select');
        const inputs = tr.querySelectorAll('input');
        const prodId = parseInt(sel?.value);
        if (!prodId) { valido = false; return; }
        const qty = parseFloat(inputs[0].value) || 0;
        const pu  = parseFloat(inputs[1].value) || 0;
        const dto = parseFloat(inputs[2].value) || 0;
        items.push({ producto_id:prodId, cantidad:qty, precio_unitario:pu, descuento:dto, subtotal:qty*pu*(1-dto/100) });
    });
    if (!valido) { Toast.warning('Selecciona un producto en todos los items.'); return; }
    if (!items.length) { Toast.warning('Agrega al menos un item.'); return; }
    const res = await apiPost(BASE, {
        action: 'orden_guardar',
        id:            document.getElementById('ovId').value,
        estado,
        cliente_id:    document.getElementById('ovCliente').value,
        proyecto_id:   document.getElementById('ovProyecto').value,
        cotizacion_id: document.getElementById('ovCotizacion').value,
        fecha_emision: document.getElementById('ovFecha').value,
        fecha_entrega: document.getElementById('ovEntrega').value,
        observaciones: document.getElementById('ovObs').value,
        items
    });
    if (res.ok) { Toast.success('Orden guardada.'); Modal.close('modalOrden'); cargarOrdenes(); }
    else Toast.error(res.error || 'Error al guardar.');
}

async function cambiarEstadoOrden(id, estado) {
    const msgs = { confirmada:'Confirmar esta orden?', facturada:'Marcar como facturada?', cobrada:'Marcar como cobrada?' };
    if (!confirm(msgs[estado] || 'Cambiar estado?')) return;
    const res = await apiPost(BASE, {action:'orden_estado', id, estado});
    if (res.ok) { Toast.success('Estado actualizado.'); cargarOrdenes(); }
}

async function eliminarOrden(id) {
    if (!confirm('Eliminar esta orden de venta?')) return;
    const res = await apiPost(BASE, {action:'orden_eliminar', id});
    if (res.ok) { Toast.success('Orden eliminada.'); cargarOrdenes(); }
}

// INIT
cargarProductosVenta();
cargarVentasStats();
cargarVentasHistorial();
cargarNotasCreditoVenta();
cargarTiposNotaCreditoVenta();
cargarCotizaciones();
cargarMaquinarias();
cargarServicios();
cargarOrdenes();
cargarCombos();
document.getElementById('ventasBuscar')?.addEventListener('keyup', e => {
    if (e.key === 'Enter') {
        cargarVentasHistorial();
        cargarNotasCreditoVenta();
    }
});
['ventasDesde','ventasHasta','ventasEstado','ventasTipo'].forEach(id => {
    document.getElementById(id)?.addEventListener('change', () => {
        cargarVentasHistorial();
        cargarNotasCreditoVenta();
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>




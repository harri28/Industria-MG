<?php
$page_title      = 'Ventas';
$page_breadcrumb = '<span>Operaciones</span> <span>></span> Ventas';
require_once __DIR__ . '/../../includes/header.php';
$db = getDB();
$tecnicos = $db->query("SELECT id, nombre FROM usuarios WHERE activo ORDER BY nombre")->fetchAll();
$empresaVenta = $db->query("SELECT razon_social, nombre_comercial, ruc, direccion_fiscal, telefono, tax_enabled, logo_path FROM empresa_emisora WHERE activo ORDER BY id LIMIT 1")->fetch() ?: [];
$taxEnabledVenta = array_key_exists('tax_enabled', $empresaVenta) ? filter_var($empresaVenta['tax_enabled'], FILTER_VALIDATE_BOOLEAN) : true;
$logoCotBase64 = null;
$logoCandidates = [];
if (!empty($empresaVenta['logo_path'])) {
    $logoCandidates[] = __DIR__ . '/../../' . ltrim($empresaVenta['logo_path'], '/');
}
$logoCandidates[] = __DIR__ . '/../../assets/iconos/logo.jpeg';
foreach ($logoCandidates as $lf) {
    if (file_exists($lf)) {
        $ext = strtolower(pathinfo($lf, PATHINFO_EXTENSION));
        $mime = match($ext) { 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp', 'svg' => 'image/svg+xml', default => 'image/jpeg' };
        $logoCotBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($lf));
        break;
    }
}
?>

<link rel="stylesheet" href="../../assets/vendor/select2/select2.min.css">
<link rel="stylesheet" href="../../assets/vendor/datatables/dataTables.bootstrap5.min.css">
<style>
.venta-cliente-wrap .select2-container { width:100% !important; }
.venta-cliente-wrap .select2-container--default .select2-selection--single {
    height:32px;border:1px solid var(--border);border-radius:6px;display:flex;align-items:center;
}
.venta-cliente-wrap .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height:30px;padding-left:10px;font-size:.82rem;color:var(--text-primary);
}
.venta-cliente-wrap .select2-container--default .select2-selection--single .select2-selection__arrow { height:30px; }
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
    <div class="tab" data-group="vnt" data-target="tabMaquinarias"><i class="fa fa-gears"></i> Maquinarias</div>
    <div class="tab" data-group="vnt" data-target="tabListadoVentas"><i class="fa fa-clock-rotate-left"></i> Historial de Ventas</div>
    <div class="tab" data-group="vnt" data-target="tabCotizaciones"><i class="fa fa-file-lines"></i> Cotizaciones</div>
    <div class="tab" data-group="vnt" data-target="tabServicio"><i class="fa fa-screwdriver-wrench"></i> Servicio Tecnico</div>
    <div class="tab" data-group="vnt" data-target="tabOrdenes"><i class="fa fa-receipt"></i> Ordenes de Venta</div>
</div>

<!-- ============================================================
     TAB: REALIZAR VENTA
     ============================================================ -->
<div class="tab-content active" data-group="vnt" id="tabVenta">
<div style="display:grid;grid-template-columns:1fr 1.5fr;gap:16px;align-items:start">

    <!-- Columna izquierda: Buscar producto -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fa fa-search"></i> Buscar Producto de Inventario</span>
        </div>
        <style>
        .venta-chip{border:1px solid var(--border);background:#fff;color:var(--text-secondary);padding:5px 13px;border-radius:999px;font-size:.78rem;font-weight:600;cursor:pointer;transition:all .15s}
        .venta-chip:hover{border-color:var(--primary);color:var(--primary)}
        .venta-chip.active{background:var(--primary);border-color:var(--primary);color:#fff}
        .venta-card{border:1px solid var(--border);border-radius:8px;padding:10px;cursor:pointer;background:#fff;display:flex;flex-direction:column;gap:6px;transition:border-color .15s,box-shadow .15s}
        .venta-card:hover{border-color:var(--primary);box-shadow:0 2px 8px rgba(0,0,0,.06)}
        </style>
        <div style="padding:14px 16px">
            <div class="search-box" style="margin-bottom:10px;width:100%;max-width:none">
                <span class="icon"><i class="fa fa-search"></i></span>
                <input type="text" id="ventaSearch" class="form-control"
                       style="width:100%;max-width:none"
                       placeholder="Codigo o nombre del producto..." oninput="filtrarProductosVenta()">
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px">
                <button type="button" class="venta-chip active" data-tipo="todos" onclick="setFiltroVenta('todos',this)">Todos</button>
                <button type="button" class="venta-chip" data-tipo="repuesto" onclick="setFiltroVenta('repuesto',this)">Repuestos</button>
                <button type="button" class="venta-chip" data-tipo="maquinaria" onclick="setFiltroVenta('maquinaria',this)">Maquinarias</button>
                <button type="button" class="venta-chip" data-tipo="servicio" onclick="setFiltroVenta('servicio',this)">Servicios</button>
                <label style="margin-left:auto;display:flex;align-items:center;gap:6px;font-size:.78rem;color:var(--text-secondary);cursor:pointer;white-space:nowrap">
                    <input type="checkbox" id="ventaMostrarMateriales" onchange="filtrarProductosVenta()"> Mostrar materiales
                </label>
            </div>
            <div id="ventaResultados" style="max-height:360px;overflow-y:auto;border:1px solid var(--border);border-radius:6px;background:#fff;padding:10px">
                <div class="text-center text-muted" style="padding:14px;font-size:.85rem">Cargando productos...</div>
            </div>
        </div>
    </div>

    <!-- Columna derecha: Carrito + Resumen unificados -->
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fa fa-receipt"></i> Resumen de la Venta</span>
            <div class="card-actions">
                <button class="btn btn-outline btn-sm" onclick="limpiarCarrito()"><i class="fa fa-trash"></i> Limpiar</button>
            </div>
        </div>
        <!-- Footer unificado: formulario + totales -->
        <style>
        .medio-pago-chip{border:1px solid var(--border);background:#fff;color:var(--text-secondary);padding:5px 10px;border-radius:6px;font-size:.78rem;font-weight:600;cursor:pointer;transition:all .15s;display:inline-flex;align-items:center;gap:5px}
        .medio-pago-chip:hover{border-color:var(--primary);color:var(--primary)}
        .medio-pago-chip.active{background:var(--primary);border-color:var(--primary);color:#fff}
        .forma-pago-btn{border:1px solid var(--border);background:#fff;color:var(--text-secondary);padding:6px 14px;border-radius:6px;font-size:.82rem;font-weight:600;cursor:pointer;transition:all .15s}
        .forma-pago-btn:hover{border-color:var(--primary);color:var(--primary)}
        .forma-pago-btn.active{background:var(--primary);border-color:var(--primary);color:#fff}
        </style>
        <div style="padding:16px">

            <!-- Formulario de venta -->
            <div>
                <div style="display:grid;grid-template-columns:148px 130px 1fr;gap:10px;align-items:end;margin-bottom:10px">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" style="font-size:.78rem">Comprobante</label>
                        <select class="form-control" id="ventaTipoComprobante" onchange="onVentaTipoComprobanteChange()" style="height:32px;font-size:.78rem;padding:4px 8px">
                            <option value="boleta">Boleta</option>
                            <option value="factura">Factura</option>
                            <option value="nota_venta">Nota de venta</option>
                        </select>
                        <select id="ventaSerie" style="display:none"></select>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" style="font-size:.78rem">Correlativo</label>
                        <input type="text" id="ventaCorrelatvoDisplay" class="form-control" readonly
                            style="height:32px;font-size:.82rem;padding:4px 10px;background:#f8fafc;color:var(--primary);font-weight:700;cursor:default" value="---">
                    </div>
                    <div class="form-group venta-cliente-wrap" style="margin-bottom:0">
                        <label class="form-label" style="font-size:.78rem">Cliente</label>
                        <div style="display:flex;gap:6px;align-items:center">
                            <div style="flex:1;position:relative">
                                <input type="text" id="ventaClienteBuscar" class="form-control"
                                    style="height:32px;font-size:.82rem;padding:4px 10px"
                                    placeholder="Buscar cliente..." autocomplete="off"
                                    oninput="filtrarClientesVenta()"
                                    onfocus="mostrarClientesDropVenta(ventaClientesUltimos())">
                                <div id="ventaClienteDrop" style="display:none;position:absolute;top:100%;left:0;right:0;z-index:999;background:#fff;border:1px solid var(--border);border-radius:6px;box-shadow:0 4px 16px rgba(0,0,0,.12);max-height:220px;overflow-y:auto;margin-top:2px"></div>
                            </div>
                            <button type="button" class="btn btn-outline btn-sm" onclick="abrirModalNuevoClienteRapido()" title="Nuevo cliente" style="flex-shrink:0;height:32px;padding:0 9px">
                                <i class="fa fa-user-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div id="ventaComprobanteInfo" style="display:none"></div>
                <!-- Dirección | Celular | Forma de pago | Medios de pago — una sola fila -->
                <div style="display:grid;grid-template-columns:1fr 110px 130px 130px;gap:8px;align-items:end;margin-bottom:10px">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" style="font-size:.78rem">Dirección</label>
                        <input type="text" id="ventaClienteDireccion" class="form-control" readonly
                            style="height:32px;font-size:.82rem;padding:4px 10px;background:#f8fafc" placeholder="—">
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" style="font-size:.78rem">Celular</label>
                        <input type="text" id="ventaClienteCelular" class="form-control" readonly
                            style="height:32px;font-size:.82rem;padding:4px 10px;background:#f8fafc" placeholder="—">
                    </div>
                    <!-- Forma de pago dropdown -->
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" style="font-size:.78rem">Forma de pago</label>
                        <div style="position:relative">
                            <div id="formasPagoInput" onclick="toggleFormasPagoDrop()"
                                style="display:flex;align-items:center;justify-content:space-between;cursor:pointer;height:32px;padding:4px 10px;border:1px solid var(--border);border-radius:6px;background:#fff;font-size:.82rem;user-select:none">
                                <span id="formasPagoLabel" style="color:var(--text-primary)">Directo</span>
                                <i class="fa fa-chevron-down" style="font-size:.7rem;color:var(--text-secondary)"></i>
                            </div>
                            <div id="formasPagoDrop" style="display:none;position:absolute;top:100%;left:0;right:0;z-index:1000;background:#fff;border:1px solid var(--border);border-radius:6px;box-shadow:0 4px 16px rgba(0,0,0,.12);margin-top:2px;overflow:hidden">
                                <div onclick="seleccionarFormaPago('directo')" id="formaItem_directo" style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:.82rem" onmouseenter="this.style.background='#f0f4ff'" onmouseleave="this.style.background=''">
                                    <i class="fa fa-check" style="width:14px;color:var(--primary)"></i> Directo
                                </div>
                                <div onclick="seleccionarFormaPago('credito')" id="formaItem_credito" style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:.82rem;border-top:1px solid var(--border)" onmouseenter="this.style.background='#f0f4ff'" onmouseleave="this.style.background=''">
                                    <i class="fa fa-check" style="width:14px;color:var(--primary);visibility:hidden"></i> Crédito
                                </div>
                                <div onclick="seleccionarFormaPago('adelanto')" id="formaItem_adelanto" style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:.82rem;border-top:1px solid var(--border)" onmouseenter="this.style.background='#f0f4ff'" onmouseleave="this.style.background=''">
                                    <i class="fa fa-check" style="width:14px;color:var(--primary);visibility:hidden"></i> Adelanto
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Medios de pago dropdown -->
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label" style="font-size:.78rem">Medios de pago</label>
                        <div style="position:relative">
                            <div id="mediosPagoInput" onclick="toggleMediosPagoDrop()"
                                style="display:flex;align-items:center;justify-content:space-between;cursor:pointer;height:32px;padding:4px 10px;border:1px solid var(--border);border-radius:6px;background:#fff;font-size:.82rem;user-select:none">
                                <span id="mediosPagoLabel" style="color:var(--text-primary)">Efectivo</span>
                                <i class="fa fa-chevron-down" style="font-size:.7rem;color:var(--text-secondary)"></i>
                            </div>
                            <div id="mediosPagoDrop" style="display:none;position:absolute;top:100%;right:0;left:auto;width:160px;z-index:1000;background:#fff;border:1px solid var(--border);border-radius:6px;box-shadow:0 4px 16px rgba(0,0,0,.12);margin-top:2px;overflow:hidden">
                                <div onclick="toggleMedioPagoItem('efectivo')" id="medioItem_efectivo" style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:.82rem" onmouseenter="this.style.background='#f0f4ff'" onmouseleave="this.style.background=''">
                                    <i class="fa fa-check" style="width:14px;color:var(--primary)"></i><i class="fa fa-money-bills"></i> Efectivo
                                </div>
                                <div onclick="toggleMedioPagoItem('yape')" id="medioItem_yape" style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:.82rem;border-top:1px solid var(--border)" onmouseenter="this.style.background='#f0f4ff'" onmouseleave="this.style.background=''">
                                    <i class="fa fa-check" style="width:14px;color:var(--primary);visibility:hidden"></i><i class="fa fa-mobile-screen"></i> Yape
                                </div>
                                <div onclick="toggleMedioPagoItem('plin')" id="medioItem_plin" style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:.82rem;border-top:1px solid var(--border)" onmouseenter="this.style.background='#f0f4ff'" onmouseleave="this.style.background=''">
                                    <i class="fa fa-check" style="width:14px;color:var(--primary);visibility:hidden"></i><i class="fa fa-mobile-screen"></i> Plin
                                </div>
                                <div onclick="toggleMedioPagoItem('transferencia')" id="medioItem_transferencia" style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:.82rem;border-top:1px solid var(--border)" onmouseenter="this.style.background='#f0f4ff'" onmouseleave="this.style.background=''">
                                    <i class="fa fa-check" style="width:14px;color:var(--primary);visibility:hidden"></i><i class="fa fa-building-columns"></i> Transferencia
                                </div>
                                <div onclick="toggleMedioPagoItem('visa')" id="medioItem_visa" style="display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;font-size:.82rem;border-top:1px solid var(--border)" onmouseenter="this.style.background='#f0f4ff'" onmouseleave="this.style.background=''">
                                    <i class="fa fa-check" style="width:14px;color:var(--primary);visibility:hidden"></i><i class="fa fa-credit-card"></i> Visa/MC
                                </div>
                            </div>
                        </div>
                    </div>
                </div><!-- /fila única -->
                <!-- Monto adelanto (condicional) -->
                <div id="panelAdelanto" class="form-group" style="display:none;margin-bottom:10px">
                    <label class="form-label">Monto adelantado (S/)</label>
                    <input type="number" class="form-control" id="ventaMontoAdelanto" min="0" step="0.01" placeholder="0.00">
                </div>
                <!-- Carrito de productos -->
                <div style="margin-bottom:0;border:1px solid var(--border);border-radius:6px;overflow:hidden">
                    <table style="width:100%">
                        <thead><tr>
                            <th>Producto</th>
                            <th class="text-right" style="width:90px">Cant.</th>
                            <th class="text-right" style="width:110px">P. Unit. (S/)</th>
                            <th class="text-right" style="width:110px">Subtotal</th>
                            <th style="width:40px"></th>
                        </tr></thead>
                        <tbody id="ventaCarritoBody">
                            <tr><td colspan="5" class="text-center text-muted" style="padding:20px">Sin productos agregados</td></tr>
                        </tbody>
                        <tfoot>
                            <?php if ($taxEnabledVenta): ?>
                            <tr style="background:#f8fafc;border-top:1px solid var(--border)" id="ventaIgvRow">
                                <td colspan="3" class="text-right text-muted" style="padding:4px 12px;font-size:.82rem">IGV (18%)</td>
                                <td class="text-right font-semibold" style="padding:4px 12px;font-size:.92rem" id="ventaIgv">S/ 0.00</td>
                                <td></td>
                            </tr>
                            <?php endif; ?>
                            <tr style="background:var(--primary-light,#fff7ed);border-top:2px solid var(--primary)">
                                <td colspan="3" class="text-right font-bold" style="padding:8px 12px;color:var(--primary);font-size:.92rem">TOTAL</td>
                                <td class="text-right font-bold" style="padding:8px 12px;color:var(--primary);font-size:1.1rem" id="ventaTotal">S/ 0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <!-- Observaciones -->
                <div class="form-group" style="margin-top:14px">
                    <label class="form-label">Observaciones</label>
                    <textarea class="form-control" id="ventaObs" rows="2" placeholder="Notas de la venta..."></textarea>
                </div>
            </div>

            <!-- Botón Generar Venta -->
            <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border);display:flex;justify-content:flex-end">
                <button class="btn btn-primary" style="padding:9px 24px;font-size:.95rem;font-weight:600" onclick="abrirModalCobroVenta()">
                    <i class="fa fa-circle-check"></i> Generar Venta
                </button>
            </div>

        </div>
    </div>

</div><!-- /grid columnas -->
</div><!-- /tabVenta -->

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
                    <option value="aprobada">Aprobada</option>
                    <option value="rechazada">Rechazada</option>
                    <option value="vencida">Vencida</option>
                </select>
                <button class="btn btn-primary btn-sm" onclick="abrirModalCotizacion()"><i class="fa fa-plus"></i> Nueva Cotizacion</button>
            </div>
        </div>
        <div class="table-responsive">
            <table><thead><tr>
                <th>ID</th><th>Numero</th><th>Cliente</th><th>Fecha</th><th>Vigencia</th>
                <th>Estado</th><th class="text-right">Subtotal</th><th class="text-right">IGV</th><th class="text-right">Total</th><th>Acciones</th>
            </tr></thead>
            <tbody id="cotizacionesBody"><tr><td colspan="10" class="text-center"><div class="loading"><div class="spinner"></div></div></td></tr></tbody></table>
        </div>
    </div>
</div>

<!-- ============================================================
     TAB: MAQUINARIAS
     ============================================================ -->
<div class="tab-content" data-group="vnt" id="tabMaquinarias">
    <div id="maqStats" style="display:none;gap:.75rem;margin-bottom:.75rem;grid-template-columns:repeat(3,1fr)">
        <div style="background:#f1f5f9;border:1px solid #cbd5e1;border-radius:var(--radius-sm);padding:.7rem 1rem;display:flex;align-items:center;gap:.75rem">
            <i class="fa fa-gears" style="color:var(--primary);font-size:1.4rem"></i>
            <div><div style="font-size:.72rem;color:#64748b;text-transform:uppercase;font-weight:600">Total</div>
            <div style="font-size:1.3rem;font-weight:700;color:var(--text)" id="maqStatTotal">0</div></div>
        </div>
        <div style="background:#dcfce7;border:1px solid #86efac;border-radius:var(--radius-sm);padding:.7rem 1rem;display:flex;align-items:center;gap:.75rem">
            <i class="fa fa-circle-check" style="color:#16a34a;font-size:1.4rem"></i>
            <div><div style="font-size:.72rem;color:#15803d;text-transform:uppercase;font-weight:600">Disponibles</div>
            <div style="font-size:1.3rem;font-weight:700;color:#15803d" id="maqStatDisp">0</div></div>
        </div>
        <div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:var(--radius-sm);padding:.7rem 1rem;display:flex;align-items:center;gap:.75rem">
            <i class="fa fa-cash-register" style="color:#d97706;font-size:1.4rem"></i>
            <div><div style="font-size:.72rem;color:#92400e;text-transform:uppercase;font-weight:600">Vendidas</div>
            <div style="font-size:1.3rem;font-weight:700;color:#92400e" id="maqStatVend">0</div></div>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fa fa-gears"></i> Maquinarias disponibles</span>
            <div class="card-actions">
                <select class="form-control" id="filtroEstadoMaq" style="width:160px" onchange="cargarMaquinarias()">
                    <option value="todos">Todos los estados</option>
                    <option value="disponible">Disponible</option>
                    <option value="vendida">Vendida</option>
                    <option value="reservada">Reservada</option>
                </select>
                <div class="search-box"><span class="icon"><i class="fa fa-search"></i></span>
                    <input type="text" class="form-control" id="buscarMaq" placeholder="Modelo, serie, cliente..." oninput="cargarMaquinarias()">
                </div>
                <button class="btn btn-primary btn-sm" onclick="abrirModalMaquinaria()"><i class="fa fa-plus"></i> Registrar Maquinaria</button>
            </div>
        </div>
        <div class="table-responsive">
            <table><thead><tr>
                <th>Codigo</th><th>Modelo</th><th>Nro. Serie</th><th>Stock</th>
                <th>Costo</th><th>Precio venta</th><th>Fecha de registro</th>
                <th>Acciones</th>
            </tr></thead>
            <tbody id="maquinariasBody"><tr><td colspan="8" class="text-center"><div class="loading"><div class="spinner"></div></div></td></tr></tbody></table>
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
            <!-- Lineas de detalle -->
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                <label class="form-label" style="margin:0">Maquinarias a cotizar</label>
                <button type="button" class="btn btn-outline btn-sm" onclick="agregarLineaCot()"><i class="fa fa-plus"></i> Agregar línea</button>
            </div>
            <div style="border:1.5px solid var(--border);border-radius:var(--radius-sm);overflow:hidden">
                <table style="margin:0" id="tablaCotDetalle">
                    <thead><tr>
                        <th style="width:48%">Maquinaria / Descripción</th>
                        <th class="text-right" style="width:12%">Cantidad</th>
                        <th class="text-right" style="width:18%">Precio Unit. (S/)</th>
                        <th class="text-right" style="width:16%">Subtotal</th>
                        <th style="width:6%"></th>
                    </tr></thead>
                    <tbody id="cotDetalleBody"></tbody>
                    <tfoot>
                        <tr style="background:#f8fafc">
                            <td colspan="3" class="text-right font-semibold" style="padding:8px 14px">Subtotal:</td>
                            <td class="text-right font-bold" style="padding:8px 14px" id="cotSubtotal">S/ 0.00</td><td></td>
                        </tr>
                        <tr style="background:#f8fafc" id="cotIgvRow">
                            <td colspan="3" class="text-right font-semibold" style="padding:4px 14px">IGV (18%):</td>
                            <td class="text-right font-bold" style="padding:4px 14px" id="cotIgv">S/ 0.00</td><td></td>
                        </tr>
                        <tr style="background:var(--primary-light)">
                            <td colspan="3" class="text-right font-bold" style="padding:8px 14px;color:var(--primary)">TOTAL:</td>
                            <td class="text-right font-bold" style="padding:8px 14px;color:var(--primary);font-size:1rem" id="cotTotal">S/ 0.00</td><td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="form-group" style="margin-top:14px"><label class="form-label">Observaciones</label><textarea class="form-control" id="cotObs" rows="2"></textarea></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalCotizacion')">Cancelar</button>
            <button class="btn btn-success" onclick="guardarCotizacion()"><i class="fa fa-floppy-disk"></i> Guardar</button>
            <button class="btn btn-primary" onclick="imprimirCotizacion()"><i class="fa fa-print"></i> Imprimir</button>
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
                    <label class="form-label">Modelo (del catálogo) *</label>
                    <select class="form-control" id="maqCatalogo" onchange="onMaqCatalogoChange()"></select>
                </div>
                <div class="form-group">
                    <label class="form-label">Color</label>
                    <select class="form-control" id="maqColor" onchange="onMaqColorChange()"><option value="">—</option></select>
                </div>
                <div class="form-group">
                    <label class="form-label">Vista</label>
                    <div style="display:flex;align-items:center;gap:8px">
                        <div style="width:60px;height:46px;border:1px solid #e2e8f0;border-radius:6px;background:#f8fafc;display:flex;align-items:center;justify-content:center;overflow:hidden;flex:0 0 auto">
                            <img id="maqImgPreview" style="display:none;width:100%;height:100%;object-fit:cover">
                            <i id="maqImgIcon" class="fa fa-image" style="color:#cbd5e1"></i>
                        </div>
                        <span class="text-xs text-muted">Cambia con el color</span>
                    </div>
                </div>
                <input type="hidden" id="maqModelo">
                <div class="form-group"><label class="form-label">Codigo Interno</label><input type="text" class="form-control" id="maqCodigo"></div>
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
                <div class="form-group" style="grid-column:span 2;display:grid;grid-template-columns:110px 110px 1fr;gap:12px;margin-bottom:0">
                    <div>
                        <label class="form-label">Garantía (meses)</label>
                        <input type="number" class="form-control" id="maqGarantia" value="12" min="0">
                    </div>
                    <div>
                        <label class="form-label">Stock</label>
                        <input type="number" class="form-control" id="maqStock" value="1" min="0" step="1">
                    </div>
                    <div>
                        <label style="display:flex;align-items:flex-start;gap:8px;cursor:pointer;font-size:.82rem;padding-top:18px;line-height:1.25">
                            <input type="checkbox" id="maqDisponibleSiempre" style="margin-top:2px">
                            <span>Siempre disponible<br><span style="font-size:.72rem;color:#94a3b8;font-weight:400">(fabricada a pedido, no controla stock)</span></span>
                        </label>
                    </div>
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

<!-- MODAL: COBRO DE VENTA (confirmación) -->
<div class="modal-overlay" id="modalCobroVenta">
    <div class="modal" style="max-width:480px" onclick="event.stopPropagation()" ondblclick="event.stopPropagation()">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-cash-register" style="color:var(--primary)"></i> Confirmar venta</span>
            <button class="modal-close" onclick="Modal.close('modalCobroVenta')"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body" style="padding:18px 20px">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
                <div class="form-group">
                    <label class="form-label">Comprobante</label>
                    <div id="cobroVentaComprobante" class="form-control" style="background:#f8fafc"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Cliente</label>
                    <div id="cobroVentaCliente" class="form-control" style="background:#f8fafc;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"></div>
                </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
                <div class="form-group">
                    <label class="form-label">Medios de pago</label>
                    <div id="cobroMediosDisplay" style="display:flex;gap:4px;flex-wrap:wrap;min-height:36px;align-items:center;padding:4px 0"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Forma de pago</label>
                    <div id="cobroFormaDisplay" class="form-control" style="background:#f8fafc"></div>
                </div>
            </div>

            <!-- Lista de ítems -->
            <div style="border:1px solid var(--border);border-radius:8px;overflow:hidden;margin-bottom:12px">
                <table style="width:100%;border-collapse:collapse;font-size:.82rem">
                    <thead><tr style="background:#f8fafc">
                        <th style="padding:7px 10px;text-align:left">Producto</th>
                        <th style="padding:7px 8px;text-align:right;width:50px">Cant.</th>
                        <th style="padding:7px 8px;text-align:right;width:80px">P.Unit.</th>
                        <th style="padding:7px 8px;text-align:right;width:80px">Subtotal</th>
                    </tr></thead>
                    <tbody id="cobroItemsList"></tbody>
                </table>
                <div style="border-top:1px solid var(--border);padding:8px 10px;display:flex;justify-content:space-between;align-items:center;background:#f8fafc">
                    <span class="text-muted" style="font-size:.8rem">IGV (18%): <strong id="cobroVentaIgv">S/ 0.00</strong></span>
                    <span style="font-size:1.05rem;font-weight:700;color:#e97316">TOTAL <strong id="cobroVentaTotal">S/ 0.00</strong></span>
                </div>
            </div>
            <!-- IDs ocultos requeridos por actualizarResumenCobroVenta -->
            <span id="cobroVentaSubtotal" style="display:none"></span>
            <span id="cobroVentaDescuentoLabel" style="display:none"></span>
            <input type="hidden" id="cobroVentaDescuento" value="0">

            <!-- Solo visible si medio=efectivo único y forma=directo -->
            <div id="panelMontoRecibido" style="display:none;grid-template-columns:1fr 1fr;gap:12px;margin-top:4px">
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

<!-- MODAL: COMPROBANTE DE VENTA (A4) -->
<div class="modal-overlay" id="modalVentaTicket">
    <div class="modal" style="max-width:860px;width:98vw">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-circle-check" style="color:var(--success)"></i> Venta completada</span>
            <button class="modal-close" onclick="cerrarTicketVenta()"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body" style="padding:16px;background:#e5e7eb;max-height:80vh;overflow:auto">
            <div id="ventaTicketBody" style="background:#fff;border-radius:4px;box-shadow:0 2px 8px rgba(15,23,42,.18);padding:30px;max-width:794px;margin:0 auto;font-family:Arial,sans-serif;font-size:12px;color:#111"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline btn-sm" onclick="imprimirTicketVenta()"><i class="fa fa-print"></i> Imprimir A4</button>
            <button class="btn btn-primary" onclick="cerrarTicketVenta()"><i class="fa fa-plus"></i> Nueva venta</button>
        </div>
    </div>
</div>

<!-- MODAL: NUEVO CLIENTE (completo, igual al módulo de Clientes) -->
<style>
.nc-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.nc-input-lookup { display:grid; grid-template-columns:1fr auto; gap:10px; align-items:end; }
.nc-lookup-note { font-size:.77rem; color:var(--text-muted); margin-top:6px; }
.nc-doc-chip { display:inline-flex; align-items:center; gap:6px; padding:4px 10px; border-radius:999px; background:#eef2ff; color:#4338ca; font-size:.78rem; font-weight:700; }
.nc-ubigeo-box { background:var(--bg-secondary,#f8fafc); border:1px dashed var(--border); border-radius:8px; padding:9px 12px; color:var(--text-muted); font-size:.82rem; margin-bottom:12px; }
</style>
<div class="modal-overlay" id="modalNuevoClienteRapido">
    <div class="modal modal-lg">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-user-plus"></i> Nuevo Cliente</span>
            <button class="modal-close" onclick="Modal.close('modalNuevoClienteRapido')"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="ncUbigeo">
            <div class="nc-form-grid" style="margin-bottom:12px">
                <div class="form-group">
                    <label class="form-label" style="display:flex;align-items:center;gap:8px">Tipo de documento <span class="nc-doc-chip" id="ncDocChip"><i class="fa fa-id-card"></i> Documento</span></label>
                    <select class="form-control" id="ncTipoDoc" onchange="ncActualizarEstadoDoc()"></select>
                </div>
                <div class="form-group">
                    <label class="form-label">Numero de documento</label>
                    <div class="nc-input-lookup">
                        <input type="text" class="form-control" id="ncNroDoc" placeholder="Ingrese el documento">
                        <button type="button" class="btn btn-outline" id="ncBtnConsultar" onclick="ncConsultarDocumento()">
                            <i class="fa fa-search"></i> Consultar
                        </button>
                    </div>
                    <div class="nc-lookup-note" id="ncLookupNote">Consulta automatica disponible para DNI y RUC.</div>
                </div>
            </div>
            <div class="nc-form-grid" style="margin-bottom:12px">
                <div class="form-group">
                    <label class="form-label" id="ncLabelNombre">Nombre completo</label>
                    <input type="text" class="form-control" id="ncNombre" placeholder="Nombre del cliente">
                </div>
                <div class="form-group">
                    <label class="form-label">Dirección</label>
                    <input type="text" class="form-control" id="ncDireccion" placeholder="Dirección del cliente">
                </div>
            </div>
            <div class="nc-form-grid" style="margin-bottom:12px">
                <div class="form-group">
                    <label class="form-label">Nombre de contacto</label>
                    <input type="text" class="form-control" id="ncContacto" placeholder="Contacto comercial o referencia">
                </div>
                <div class="form-group">
                    <label class="form-label">Correo</label>
                    <input type="email" class="form-control" id="ncEmail" placeholder="correo@ejemplo.com">
                </div>
            </div>
            <div class="nc-form-grid" style="margin-bottom:12px">
                <div class="form-group">
                    <label class="form-label">Teléfono</label>
                    <input type="text" class="form-control" id="ncTelefono" placeholder="987654321">
                </div>
                <div class="form-group">
                    <label class="form-label">WhatsApp</label>
                    <input type="text" class="form-control" id="ncWhatsapp" placeholder="987654321">
                </div>
            </div>
            <div class="nc-ubigeo-box" id="ncUbigeoInfo">Sin ubigeo consultado.</div>
            <div class="form-group">
                <label class="form-label">Notas / Observaciones</label>
                <textarea class="form-control" id="ncNotas" rows="2" placeholder="Información comercial, acuerdos, referencias, etc."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalNuevoClienteRapido')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarNuevoClienteRapido()"><i class="fa fa-save"></i> Guardar</button>
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
const TAX_ENABLED = <?= $taxEnabledVenta ? 'true' : 'false' ?>;
const EMPRESA_VENTA = <?= json_encode([
    'nombre' => $empresaVenta['nombre_comercial'] ?: ($empresaVenta['razon_social'] ?? 'IndustriaMG'),
    'razon_social' => $empresaVenta['razon_social'] ?? 'IndustriaMG',
    'ruc' => $empresaVenta['ruc'] ?? '',
    'direccion' => $empresaVenta['direccion_fiscal'] ?? '',
    'telefono' => $empresaVenta['telefono'] ?? '',
    'logo' => $logoCotBase64,
], JSON_UNESCAPED_UNICODE) ?>;

// HELPERS
function esc(s) { const d=document.createElement('div');d.textContent=String(s||'');return d.innerHTML; }
const estadosCot = { aprobada:'badge-entrega', rechazada:'badge-retrasado', vencida:'badge-pruebas' };
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
// Medios y forma de pago (Resumen de la Venta)
let ventaMediosPago = ['efectivo'];
let ventaFormaPago = 'directo';
const MEDIOS_LABELS = { efectivo:'Efectivo', yape:'Yape', plin:'Plin', transferencia:'Transferencia', visa:'Visa/MC' };
const FORMA_LABELS  = { directo:'Directo (contado)', credito:'Credito', adelanto:'Con adelanto' };
let maquinariasVenta = [];
let maquinariaVentaActual = null;

function toggleMediosPagoDrop() {
    const drop = document.getElementById('mediosPagoDrop');
    if (!drop) return;
    drop.style.display = drop.style.display === 'none' ? 'block' : 'none';
}

function toggleMedioPagoItem(medio) {
    const idx = ventaMediosPago.indexOf(medio);
    if (idx >= 0) {
        if (ventaMediosPago.length === 1) return; // mínimo uno
        ventaMediosPago.splice(idx, 1);
    } else {
        ventaMediosPago.push(medio);
    }
    actualizarMediosPagoUI();
}

function actualizarMediosPagoUI() {
    Object.keys(MEDIOS_LABELS).forEach(m => {
        const item = document.getElementById(`medioItem_${m}`);
        if (!item) return;
        const check = item.querySelector('.fa-check');
        if (check) check.style.visibility = ventaMediosPago.includes(m) ? 'visible' : 'hidden';
    });
    const lbl = document.getElementById('mediosPagoLabel');
    if (lbl) lbl.textContent = ventaMediosPago.map(m => MEDIOS_LABELS[m] || m).join(', ');
}

document.addEventListener('click', e => {
    const drop = document.getElementById('mediosPagoDrop');
    const inp  = document.getElementById('mediosPagoInput');
    if (drop && inp && !drop.contains(e.target) && !inp.contains(e.target)) {
        drop.style.display = 'none';
    }
});

function toggleFormasPagoDrop() {
    const drop = document.getElementById('formasPagoDrop');
    if (drop) drop.style.display = drop.style.display === 'none' ? 'block' : 'none';
}

function seleccionarFormaPago(forma) {
    ventaFormaPago = forma;
    ['directo','credito','adelanto'].forEach(f => {
        const item = document.getElementById(`formaItem_${f}`);
        if (item) item.querySelector('.fa-check').style.visibility = f === forma ? 'visible' : 'hidden';
    });
    const lbl = document.getElementById('formasPagoLabel');
    if (lbl) lbl.textContent = FORMA_LABELS[forma] || forma;
    const drop = document.getElementById('formasPagoDrop');
    if (drop) drop.style.display = 'none';
    document.getElementById('panelAdelanto').style.display = forma === 'adelanto' ? 'block' : 'none';
}

document.addEventListener('click', e => {
    const drop = document.getElementById('formasPagoDrop');
    const inp  = document.getElementById('formasPagoInput');
    if (drop && inp && !drop.contains(e.target) && !inp.contains(e.target)) drop.style.display = 'none';
});

const BASE_NC = '../clientes/api.php';
let ncTiposDocumento = [];

async function cargarTiposDocumentoNC() {
    if (ncTiposDocumento.length) return;
    const d = await apiGet(`${BASE_NC}?action=document_types`);
    ncTiposDocumento = d.items || [];
    const sel = document.getElementById('ncTipoDoc');
    sel.innerHTML = ncTiposDocumento.map(t =>
        `<option value="${t.id}" data-codigo="${esc(t.codigo)}" data-label="${esc(t.descripcion_documento||t.descripcion)}">${esc(t.descripcion_documento||t.descripcion)}</option>`
    ).join('');
    const pred = ncTiposDocumento.find(t => t.codigo === '1') || ncTiposDocumento[0];
    if (pred) sel.value = String(pred.id);
}

function ncObtenerMetaDoc() {
    const opt = document.querySelector('#ncTipoDoc option:checked');
    return { codigo: opt?.dataset.codigo || '', label: opt?.dataset.label || 'Documento' };
}

function ncActualizarEstadoDoc() {
    const meta = ncObtenerMetaDoc();
    const esEmpresa = meta.codigo === '6';
    document.getElementById('ncLabelNombre').textContent = esEmpresa ? 'Razon social' : 'Nombre completo';
    document.getElementById('ncNombre').placeholder = esEmpresa ? 'Razon social del cliente' : 'Nombre completo del cliente';
    document.getElementById('ncNroDoc').placeholder = meta.codigo === '6' ? '20123456789' : (meta.codigo === '1' ? '12345678' : 'Ingrese el documento');
    document.getElementById('ncLookupNote').textContent = ['1','6'].includes(meta.codigo)
        ? 'Consulta automatica disponible para DNI y RUC.'
        : 'Para este tipo de documento el ingreso es manual.';
    document.getElementById('ncDocChip').innerHTML = `<i class="fa fa-id-card"></i> ${esc(meta.label)}`;
}

async function ncConsultarDocumento() {
    const tipo_documento_id = document.getElementById('ncTipoDoc').value;
    const numero_documento = document.getElementById('ncNroDoc').value.trim();
    if (!numero_documento) return Toast.warning('Ingresa el numero de documento.');
    const btn = document.getElementById('ncBtnConsultar');
    const prev = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
    try {
        const r = await apiPost(BASE_NC, { action: 'lookup_document', tipo_documento_id, numero_documento });
        if (!r.ok) throw new Error(r.error || 'No se pudo consultar.');
        document.getElementById('ncNombre').value = r.cliente.razon_social || '';
        if (r.cliente.contacto_nombre) document.getElementById('ncContacto').value = r.cliente.contacto_nombre;
        if (r.cliente.direccion) document.getElementById('ncDireccion').value = r.cliente.direccion;
        document.getElementById('ncUbigeo').value = r.cliente.ubigeo || '';
        document.getElementById('ncUbigeoInfo').textContent = r.cliente.ubigeo
            ? `Ubigeo: ${r.cliente.ubigeo}` : 'Documento consultado sin ubigeo disponible.';
        Toast.success('Documento consultado.');
    } catch (e) {
        Toast.error(e.message || 'No se pudo consultar.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = prev;
    }
}

async function abrirModalNuevoClienteRapido() {
    await cargarTiposDocumentoNC();
    document.getElementById('ncNroDoc').value = '';
    document.getElementById('ncNombre').value = '';
    document.getElementById('ncContacto').value = '';
    document.getElementById('ncEmail').value = '';
    document.getElementById('ncTelefono').value = '';
    document.getElementById('ncWhatsapp').value = '';
    document.getElementById('ncDireccion').value = '';
    document.getElementById('ncNotas').value = '';
    document.getElementById('ncUbigeo').value = '';
    document.getElementById('ncUbigeoInfo').textContent = 'Sin ubigeo consultado.';
    ncActualizarEstadoDoc();
    Modal.open('modalNuevoClienteRapido');
}

async function guardarNuevoClienteRapido() {
    const nombre = document.getElementById('ncNombre').value.trim();
    if (!nombre) { Toast.warning('El nombre es requerido.'); return; }
    const meta = ncObtenerMetaDoc();
    const res = await apiPost(BASE_NC, {
        action: 'crear',
        tipo: meta.codigo === '6' ? 'empresa' : 'persona',
        tipo_documento_id: document.getElementById('ncTipoDoc').value,
        numero_documento: document.getElementById('ncNroDoc').value.trim(),
        razon_social: nombre,
        contacto_nombre: document.getElementById('ncContacto').value.trim(),
        email: document.getElementById('ncEmail').value.trim(),
        telefono: document.getElementById('ncTelefono').value.trim(),
        whatsapp: document.getElementById('ncWhatsapp').value.trim(),
        direccion: document.getElementById('ncDireccion').value.trim(),
        notas: document.getElementById('ncNotas').value.trim(),
        ubigeo: document.getElementById('ncUbigeo').value.trim(),
        estado_crm: 'activo',
    });
    if (!res.ok) { Toast.error(res.error || 'Error al guardar cliente.'); return; }
    Toast.success('Cliente registrado.');
    Modal.close('modalNuevoClienteRapido');
    await cargarClientesVenta();
    if (res.id) seleccionarClienteVenta(res.id);
}

async function cargarProductosVenta() {
    try {
        const d = await apiGet(`${BASE}?action=productos_listar_venta`);
        productosVenta = d.productos || [];
        filtrarProductosVenta();
    } catch(e) {
        document.getElementById('ventaResultados').innerHTML =
            '<div class="text-center" style="padding:12px;color:var(--danger);font-size:.85rem">Error al cargar productos</div>';
    }
}

let ventaFiltroTipo = 'todos';

function clasificarItemVenta(p) {
    const pt = (p.product_type || '').toLowerCase();
    if (pt === 'machine') return 'maquinaria';
    if (pt === 'service' || (p.unidad_codigo || '').toLowerCase() === 'zz' || (p.unidad || '').toLowerCase() === 'servicio') return 'servicio';
    if (normalizarBooleano(p.es_repuesto)) return 'repuesto';
    return 'material';
}

function setFiltroVenta(tipo, btn) {
    ventaFiltroTipo = tipo;
    document.querySelectorAll('.venta-chip').forEach(c => c.classList.remove('active'));
    if (btn) btn.classList.add('active');
    filtrarProductosVenta();
}

function filtrarProductosVenta() {
    const q = document.getElementById('ventaSearch').value.trim().toLowerCase();
    const mostrarMat = document.getElementById('ventaMostrarMateriales')?.checked;
    let lista = productosVenta.filter(p => {
        const t = clasificarItemVenta(p);
        if (ventaFiltroTipo === 'todos') return t !== 'material' || mostrarMat;
        return t === ventaFiltroTipo;
    });
    if (q) lista = lista.filter(p => p.nombre.toLowerCase().includes(q) || (p.codigo || '').toLowerCase().includes(q));
    renderProductosVenta(lista.slice(0, 60));
}

function renderProductosVenta(lista) {
    const el = document.getElementById('ventaResultados');
    if (!lista.length) {
        el.innerHTML = '<div class="text-center text-muted" style="padding:14px;font-size:.85rem">Sin resultados</div>';
        return;
    }
    const META = {
        maquinaria: ['Maquinaria', '#fef3c7', '#b45309'],
        servicio:   ['Servicio',   '#fff7ed', '#ea580c'],
        repuesto:   ['Repuesto',   '#f0fdf4', '#16a34a'],
        material:   ['Material',    '#eff6ff', '#2563eb'],
    };
    const cards = lista.map(p => {
        const t = clasificarItemVenta(p);
        const esServicio = t === 'servicio';
        const esMaqSiempre = t === 'maquinaria' && normalizarBooleano(p.maq_disponible_siempre);
        const sinStock = !esServicio && !esMaqSiempre && parseFloat(p.stock_actual) <= 0;
        const [badgeLabel, badgeBg, badgeColor] = META[t];
        const stockTxt = esServicio ? 'Servicio' : (esMaqSiempre ? 'Disponible' : `${parseFloat(p.stock_actual).toFixed(2)} ${esc(p.unidad || '')}`);
        return `<div class="venta-card" style="cursor:${sinStock?'not-allowed':'pointer'};opacity:${sinStock?.5:1}"
             ${!sinStock ? `onclick="agregarAlCarrito(${p.id})"` : ''}>
            <div style="display:flex;justify-content:space-between;align-items:center;gap:6px">
                <span style="background:${badgeBg};color:${badgeColor};padding:1px 7px;border-radius:4px;font-size:.66rem;font-weight:600">${badgeLabel}</span>
                ${!sinStock ? '<i class="fa fa-plus-circle" style="color:var(--primary);font-size:1rem"></i>' : '<i class="fa fa-ban" style="color:var(--danger);font-size:.9rem"></i>'}
            </div>
            <div class="font-semibold" style="font-size:.82rem;line-height:1.25;min-height:2.05em;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical" title="${esc(p.nombre)}">${esc(p.nombre)}</div>
            <div style="display:flex;justify-content:space-between;align-items:baseline;gap:6px">
                <span class="text-xs" style="color:${sinStock?'var(--danger)':'var(--text-muted)'}">Stock: ${stockTxt}</span>
                <span style="font-weight:700;color:var(--primary);font-size:.8rem;white-space:nowrap">${formatMoney(p.precio_venta)}</span>
            </div>
        </div>`;
    }).join('');
    el.innerHTML = `<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(148px,1fr));gap:10px">${cards}</div>`;
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
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted" style="padding:20px">Sin productos agregados</td></tr>';
        recalcularVentaTotals();
        return;
    }
    tbody.innerHTML = carritoVenta.map((it, idx) => `<tr>
        <td>
            <div class="font-semibold" style="font-size:.95rem">${esc(it.nombre)}</div>
        </td>
        <td class="text-right">
            <input type="number" class="form-control text-right" style="width:80px"
                   value="${it.cantidad}" min="0.01" ${it.esServicio ? '' : `max="${it.stock}"`} step="0.01"
                   onchange="updateCarritoItem(${idx},'cantidad',this.value)">
        </td>
        <td class="text-right">
            <input type="number" class="form-control text-right" style="width:100px"
                   value="${it.precio}" min="0" step="0.01"
                   onchange="updateCarritoItem(${idx},'precio',this.value)">
        </td>
        <td class="text-right font-semibold">${formatMoney(calcularLineaVentaUI(it).total)}</td>
        <td class="text-center">
            <button class="btn btn-outline btn-xs" onclick="quitarDelCarrito(${idx})"><i class="fa fa-times"></i></button>
        </td>
    </tr>`).join('');
    recalcularVentaTotals();
}

function updateCarritoItem(idx, field, value) {
    let v = parseFloat(value) || 0;
    if (field === 'cantidad') v = Math.round(v * 100) / 100; // 2 decimales
    carritoVenta[idx][field] = v;
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
    const porcentaje = (TAX_ENABLED && afectacion === '10') ? Math.max(0, parseFloat(it.porcentaje_igv) || 18) : 0;
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
    const elSub = document.getElementById('ventaSubtotal');
    const elIgv = document.getElementById('ventaIgv');
    if (elSub) elSub.textContent = formatMoney(totales.subtotal);
    if (elIgv) elIgv.textContent = formatMoney(totales.igv);
    document.getElementById('ventaTotal').textContent = formatMoney(totales.total);
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
    creditoVentaActivo = ventaFormaPago === 'credito';
    creditoVentaRows = [];

    document.getElementById('cobroVentaComprobante').textContent = labels[tipoComprobante] || 'Comprobante';
    document.getElementById('cobroVentaCliente').textContent = selectedClienteVenta ? clienteVentaLabel(selectedClienteVenta) : 'CLIENTES VARIOS';
    document.getElementById('cobroVentaDescuento').value = '0.00';

    // Lista de ítems
    const tbody = document.getElementById('cobroItemsList');
    if (tbody) {
        tbody.innerHTML = carritoVenta.map(it => {
            const linea = calcularLineaVentaUI(it);
            return `<tr style="border-bottom:1px solid var(--border)">
                <td style="padding:6px 10px">${esc(it.nombre)}</td>
                <td style="padding:6px 8px;text-align:right">${it.cantidad}</td>
                <td style="padding:6px 8px;text-align:right">${formatMoney(it.precio)}</td>
                <td style="padding:6px 8px;text-align:right;font-weight:600">${formatMoney(linea.total)}</td>
            </tr>`;
        }).join('');
    }

    // Mostrar medios de pago seleccionados
    document.getElementById('cobroMediosDisplay').innerHTML = ventaMediosPago
        .map(m => `<span style="background:var(--primary);color:#fff;padding:3px 8px;border-radius:4px;font-size:.75rem;font-weight:600">${MEDIOS_LABELS[m]||m}</span>`)
        .join(' ');

    // Mostrar forma de pago
    document.getElementById('cobroFormaDisplay').textContent = FORMA_LABELS[ventaFormaPago] || ventaFormaPago;

    // Monto recibido solo si único medio efectivo y forma directo
    const soloEfectivo = ventaMediosPago.length === 1 && ventaMediosPago[0] === 'efectivo';
    const panelMonto = document.getElementById('panelMontoRecibido');
    panelMonto.style.display = (soloEfectivo && ventaFormaPago === 'directo') ? 'grid' : 'none';

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
    const soloEfectivo = ventaMediosPago && ventaMediosPago.length === 1 && ventaMediosPago[0] === 'efectivo';
    const pagoDirecto = ventaFormaPago === 'directo';
    const total = cobroVentaTotales.total || 0;
    const input = document.getElementById('cobroVentaMonto');
    if (!input) return;
    if (!soloEfectivo || !pagoDirecto || creditoVentaActivo) {
        input.disabled = true;
        input.value = total.toFixed(2);
        const elV = document.getElementById('cobroVentaVuelto');
        if (elV) elV.textContent = 'S/ 0.00';
        return;
    }
    input.disabled = false;
    const recibido = parseFloat(input.value) || 0;
    const vuelto = Math.max(0, recibido - total);
    const el = document.getElementById('cobroVentaVuelto');
    if (el) {
        el.textContent = formatMoney(vuelto);
        el.style.color = recibido >= total ? 'var(--success)' : 'var(--danger)';
    }
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
    const totalCobro = cobroVentaTotales.total || calcularTotalesVentaUI().total;
    const esCredito = ventaFormaPago === 'credito';
    const soloEfectivo = ventaMediosPago.length === 1 && ventaMediosPago[0] === 'efectivo';
    const recibido = parseFloat(document.getElementById('cobroVentaMonto')?.value) || totalCobro;
    const adelanto = ventaFormaPago === 'adelanto'
        ? Math.max(0, parseFloat(document.getElementById('ventaMontoAdelanto')?.value) || 0) : 0;

    if (esCredito) {
        const doc = String(selectedClienteVenta?.numero_documento || selectedClienteVenta?.ruc_dni || '').replace(/\D/g, '');
        if (!selectedClienteVenta || doc === '00000000') {
            Toast.warning('Para credito selecciona un cliente real.');
            return;
        }
    }
    if (ventaFormaPago === 'adelanto' && adelanto <= 0) {
        Toast.warning('Ingresa el monto adelantado.');
        return;
    }
    if (soloEfectivo && ventaFormaPago === 'directo' && recibido + 0.001 < totalCobro) {
        Toast.warning('El monto recibido es menor al total.');
        return;
    }

    const btn = document.getElementById('btnConfirmarCobroVenta');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Emitiendo...';

    const tipoComprobante = document.getElementById('ventaTipoComprobante').value;
    const serie = document.getElementById('ventaSerie').value;
    const items = carritoVenta.map(it => ({
        producto_id:     it.id,
        cantidad:        it.cantidad,
        precio_unitario: it.precio,
        descuento:       it.descuento
    }));
    const paymentBreakdown = ventaMediosPago.length === 1
        ? [{ method: ventaMediosPago[0], amount: totalCobro }]
        : ventaMediosPago.map(m => ({ method: m, amount: 0 }));
    try {
        const res = await apiPost(BASE, {
            action:           'venta_directa',
            tipo_comprobante: tipoComprobante,
            cliente_id:       selectedClienteVenta?.id || null,
            observaciones:    document.getElementById('ventaObs').value,
            forma_pago:       ventaFormaPago,
            medios_pago:      ventaMediosPago,
            monto_adelanto:   adelanto,
            tipo_pago:        esCredito ? 'credito' : ventaMediosPago[0],
            monto_recibido:   esCredito ? 0 : recibido,
            descuento:        descuentoCobroVenta(),
            es_credito:       esCredito,
            payment_breakdown: paymentBreakdown,
            cuotas:           [],
            maquinaria_id:    maquinariaVentaActual?.id || null,
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
    const compLabels = { boleta:'BOLETA DE VENTA ELECTRÓNICA', factura:'FACTURA ELECTRÓNICA', nota_venta:'NOTA DE VENTA', nota_credito:'NOTA DE CRÉDITO ELECTRÓNICA' };
    const tipo = data.tipo_comprobante || 'boleta';
    const cliente = data.cliente || selectedClienteVenta || {};
    const clienteNombre = cliente.nombre || cliente.razon_social || cliente.nombre_completo || 'CLIENTES VARIOS';
    const clienteDoc = cliente.documento || cliente.numero_documento || cliente.ruc_dni || cliente.ruc || cliente.dni || '00000000';
    const fechaBase = data.created_at ? new Date(String(data.created_at).replace(' ', 'T')) : new Date();
    const fecha = fechaBase.toLocaleDateString('es-PE', { day:'2-digit', month:'2-digit', year:'numeric' });
    const hora = fechaBase.toLocaleTimeString('es-PE', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
    const items = data.items || [];
    const PRIMARY = '#e97316';

    const logoHtml = EMPRESA_VENTA.logo
        ? `<img src="${EMPRESA_VENTA.logo}" style="max-height:84px;max-width:240px;object-fit:contain">`
        : `<div style="font-size:22px;font-weight:900;color:${PRIMARY}">${esc(EMPRESA_VENTA.nombre || EMPRESA_VENTA.razon_social || 'IndustriaMG')}</div>`;

    const itemsHtml = items.map((it, i) => {
        const qty = parseFloat(it.cantidad || 0);
        const precio = parseFloat(it.precio_unitario || it.precio || 0);
        const dto = parseFloat(it.descuento || 0);
        const total = parseFloat(it.total || 0);
        return `<tr style="border-bottom:1px solid #e5e7eb">
            <td style="padding:6px 8px;text-align:center;color:#555">${i+1}</td>
            <td style="padding:6px 8px">${esc(it.descripcion || it.nombre || 'Producto')}</td>
            <td style="padding:6px 8px;text-align:center;color:#555">${esc(it.unidad_codigo || 'NIU')}</td>
            <td style="padding:6px 8px;text-align:right">${qty.toFixed(2)}</td>
            <td style="padding:6px 8px;text-align:right">S/ ${precio.toFixed(2)}</td>
            <td style="padding:6px 8px;text-align:right;color:#dc2626">${dto > 0 ? `-S/ ${dto.toFixed(2)}` : '-'}</td>
            <td style="padding:6px 8px;text-align:right;font-weight:700">S/ ${total.toFixed(2)}</td>
        </tr>`;
    }).join('');

    // Pago info
    const formaLabel = { directo:'Contado', credito:'Crédito', adelanto:'Con adelanto' }[data.forma_pago || 'directo'] || 'Contado';
    let mediosStr = '';
    if (Array.isArray(data.medios_pago) && data.medios_pago.length) {
        mediosStr = data.medios_pago.map(m => MEDIOS_LABELS[m] || m).join(', ');
    } else if (Array.isArray(data.payment_breakdown) && data.payment_breakdown.length) {
        mediosStr = data.payment_breakdown.map(p => MEDIOS_LABELS[p.method] || p.method).join(', ');
    } else {
        mediosStr = MEDIOS_LABELS[data.tipo_pago] || data.tipo_pago || 'Efectivo';
    }
    const pagoHtml = `${formaLabel} · ${mediosStr}`;

    // Nota credito ref
    const notaCreditoRef = tipo === 'nota_credito' && data.documento_modificado_numero_completo
        ? `<div style="font-size:11px;color:#555"><b>Ref:</b> ${esc(data.documento_modificado_numero_completo)}${data.motivo_nota_credito ? ' — ' + esc(data.motivo_nota_credito) : ''}</div>` : '';

    return `<div>
        <table style="width:100%;border-bottom:2px solid ${PRIMARY};padding-bottom:14px;margin-bottom:14px;border-collapse:collapse">
            <tr>
                <td style="vertical-align:top;width:55%">
                    ${logoHtml}
                    ${EMPRESA_VENTA.ruc ? `<div style="margin-top:4px;font-size:11px;color:#555">RUC: ${esc(EMPRESA_VENTA.ruc)}</div>` : ''}
                    ${EMPRESA_VENTA.direccion ? `<div style="font-size:11px;color:#555">${esc(EMPRESA_VENTA.direccion)}</div>` : ''}
                    ${EMPRESA_VENTA.telefono ? `<div style="font-size:11px;color:#555">Tel: ${esc(EMPRESA_VENTA.telefono)}</div>` : ''}
                </td>
                <td style="vertical-align:top;text-align:right">
                    <div style="background:${PRIMARY};color:#fff;padding:10px 14px;border-radius:8px;display:inline-block;text-align:center">
                        <div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.5px">${compLabels[tipo] || 'COMPROBANTE'}</div>
                        <div style="font-size:17px;font-weight:900;letter-spacing:1px;margin-top:2px">${esc(data.comprobante_numero || data.numero || '---')}</div>
                    </div>
                    <div style="margin-top:8px;font-size:11px;color:#555;text-align:right">
                        <div>Fecha: ${fecha} ${hora}</div>
                        ${data.comprobante && !data.comprobante.error_nubefact ? `<div style="color:#15803d;margin-top:3px">✓ ${esc(data.comprobante.estado_sunat || 'Enviado')}</div>` : ''}
                    </div>
                </td>
            </tr>
        </table>
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:10px 14px;margin-bottom:14px">
            <table style="width:100%;border-collapse:collapse"><tr>
                <td style="vertical-align:top;width:50%">
                    <div style="font-size:10px;text-transform:uppercase;color:#94a3b8;font-weight:600">${tipo==='factura'?'Razón Social / RUC':'Cliente'}</div>
                    <div style="font-size:13px;font-weight:700;margin-top:2px">${esc(clienteNombre)}</div>
                    <div style="font-size:11px;color:#555">${tipo==='factura'?'RUC':'Doc.'}: ${esc(clienteDoc)}</div>
                    ${cliente.direccion ? `<div style="font-size:11px;color:#555">${esc(cliente.direccion)}</div>` : ''}
                    ${notaCreditoRef}
                </td>
                <td style="vertical-align:top;text-align:right">
                    <div style="font-size:10px;text-transform:uppercase;color:#94a3b8;font-weight:600">N° Venta</div>
                    <div style="font-size:12px;font-weight:600;margin-top:2px">${esc(data.numero || '-')}</div>
                    <div style="font-size:11px;color:#555;margin-top:4px"><b>Pago:</b> ${esc(pagoHtml)}</div>
                    ${data.observaciones ? `<div style="font-size:11px;color:#555;margin-top:2px"><b>Obs:</b> ${esc(data.observaciones)}</div>` : ''}
                </td>
            </tr></table>
        </div>
        <table style="width:100%;border-collapse:collapse;margin-bottom:14px">
            <thead>
                <tr style="background:${PRIMARY};color:#fff">
                    <th style="padding:7px 8px;text-align:center;width:28px">#</th>
                    <th style="padding:7px 8px;text-align:left">Descripción</th>
                    <th style="padding:7px 8px;text-align:center;width:40px">U.M.</th>
                    <th style="padding:7px 8px;text-align:right;width:55px">Cant.</th>
                    <th style="padding:7px 8px;text-align:right;width:80px">P. Unit.</th>
                    <th style="padding:7px 8px;text-align:right;width:65px">Desc.</th>
                    <th style="padding:7px 8px;text-align:right;width:80px">Total</th>
                </tr>
            </thead>
            <tbody>${itemsHtml || `<tr><td colspan="7" style="padding:12px;text-align:center;color:#94a3b8">Sin items</td></tr>`}</tbody>
        </table>
        <table style="width:100%;border-collapse:collapse;margin-bottom:14px">
            <tr>
                <td style="width:52%"></td>
                <td>
                    <table style="width:100%;border-collapse:collapse">
                        ${parseFloat(data.gravada||0)>0 ? `<tr><td style="padding:4px 8px;color:#555;font-size:11px">Op. Gravada:</td><td style="padding:4px 8px;text-align:right;font-size:11px">S/ ${parseFloat(data.gravada||0).toFixed(2)}</td></tr>` : ''}
                        ${parseFloat(data.exonerada||0)>0 ? `<tr><td style="padding:4px 8px;color:#555;font-size:11px">Op. Exonerada:</td><td style="padding:4px 8px;text-align:right;font-size:11px">S/ ${parseFloat(data.exonerada||0).toFixed(2)}</td></tr>` : ''}
                        ${parseFloat(data.inafecta||0)>0 ? `<tr><td style="padding:4px 8px;color:#555;font-size:11px">Op. Inafecta:</td><td style="padding:4px 8px;text-align:right;font-size:11px">S/ ${parseFloat(data.inafecta||0).toFixed(2)}</td></tr>` : ''}
                        <tr><td style="padding:4px 8px;color:#555;font-size:11px">IGV (18%):</td><td style="padding:4px 8px;text-align:right;font-size:11px">S/ ${parseFloat(data.igv||0).toFixed(2)}</td></tr>
                        ${parseFloat(data.descuento||0)>0 ? `<tr><td style="padding:4px 8px;color:#16a34a;font-size:11px">Descuento:</td><td style="padding:4px 8px;text-align:right;color:#16a34a;font-size:11px">-S/ ${parseFloat(data.descuento||0).toFixed(2)}</td></tr>` : ''}
                        <tr style="background:${PRIMARY};color:#fff">
                            <td style="padding:9px 8px;font-weight:700;border-radius:4px 0 0 4px">TOTAL:</td>
                            <td style="padding:9px 8px;text-align:right;font-weight:700;font-size:14px;border-radius:0 4px 4px 0">S/ ${parseFloat(data.total||0).toFixed(2)}</td>
                        </tr>
                        ${parseFloat(data.vuelto||0)>0 ? `<tr><td style="padding:4px 8px;color:#555;font-size:11px">Vuelto:</td><td style="padding:4px 8px;text-align:right;font-size:11px">S/ ${parseFloat(data.vuelto||0).toFixed(2)}</td></tr>` : ''}
                        ${parseFloat(data.monto_adelanto||0)>0 ? `<tr><td style="padding:4px 8px;color:#f97316;font-size:11px">Adelanto:</td><td style="padding:4px 8px;text-align:right;color:#f97316;font-size:11px">S/ ${parseFloat(data.monto_adelanto||0).toFixed(2)}</td></tr>` : ''}
                    </table>
                </td>
            </tr>
        </table>
        <div style="border-top:1px solid #e2e8f0;padding-top:12px;text-align:center;font-size:11px;color:#94a3b8">
            Gracias por su compra · ${esc(EMPRESA_VENTA.nombre || EMPRESA_VENTA.razon_social || '')}
        </div>
    </div>`;
}

function mostrarTicketVenta(data) {
    ultimoTicketVenta = data;
    document.getElementById('ventaTicketBody').innerHTML = buildTicketVentaHTML(data);
    Modal.open('modalVentaTicket');
}

function imprimirTicketVenta() {
    const html = document.getElementById('ventaTicketBody').innerHTML;
    const w = window.open('', '_blank', 'width=900,height=700');
    w.document.write(`<!DOCTYPE html><html><head><meta charset="utf-8"><title>Comprobante</title><style>
        *{box-sizing:border-box} body{font-family:Arial,sans-serif;font-size:12px;max-width:794px;margin:0 auto;padding:20px;color:#111}
        table{border-collapse:collapse} img{max-width:100%}
        @media print{ @page{size:A4;margin:15mm} body{padding:0;margin:0;font-size:12px} }
    </style></head><body>${html}</body></html>`);
    w.document.close();
    w.focus();
    setTimeout(() => w.print(), 350);
}

function cerrarTicketVenta() {
    Modal.close('modalVentaTicket');
    limpiarCarrito();
    document.getElementById('ventaObs').value = '';
    // Reset medios/forma pago chips
    ventaMediosPago = ['efectivo'];
    ventaFormaPago = 'directo';
    actualizarMediosPagoUI();
    seleccionarFormaPago('directo');
    const pa = document.getElementById('panelAdelanto');
    if (pa) pa.style.display = 'none';
    const ma = document.getElementById('montoAdelantoInput');
    if (ma) ma.value = '';
    selectedClienteVenta = null;
    const cb = document.getElementById('ventaClienteBuscar');
    if (cb) cb.value = '';
    const dir = document.getElementById('ventaClienteDireccion');
    if (dir) dir.value = '';
    const cel = document.getElementById('ventaClienteCelular');
    if (cel) cel.value = '';
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

function acortarComprobante(numero) {
    if (!numero) return '';
    // Reduce el padding del correlativo a 4 dígitos: B001-00000012 → B001-0012, F001-00000005 → F001-0005
    return String(numero).replace(/^([A-Z]\d{3})-0*(\d+)$/i, (_, serie, corr) => `${serie}-${corr.padStart(4,'0')}`);
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
        dom: "<'row'<'col-sm-12 col-md-6'f><'col-sm-12 col-md-6'l>>rt<'row'<'col-sm-12 col-md-6'i><'col-sm-12 col-md-6'p>>",
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
    const notaCredito = compId && esElectronico && tieneCdr && !v.anulado
        ? `<button class="btn btn-outline btn-xs" onclick="abrirNotaCreditoVenta(${compId}, '${esc(v.comprobante_numero || '')}')" title="Nota de credito"><i class="fa fa-file-circle-minus"></i></button>`
        : '';
    return `
        <div style="display:flex;gap:5px;flex-wrap:wrap">
            <button class="btn btn-outline btn-xs" onclick="imprimirVentaHistorial(${id})" title="Imprimir A4"><i class="fa fa-print"></i></button>
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
            <tr style="cursor:pointer" onclick="verDetalleVenta(${parseInt(v.id || 0, 10)})">
                <td>
                    <div class="font-semibold text-primary">${esc(acortarComprobante(v.comprobante_numero) || 'Sin comprobante')}</div>
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
                <td onclick="event.stopPropagation()">${archivoVentaBtn(v.xml_path, 'XML')}</td>
                <td onclick="event.stopPropagation()">${cdrVentaCell(v)}</td>
                <td class="text-right font-semibold">${formatMoney(v.total || 0)}</td>
                <td onclick="event.stopPropagation()">${accionesVentaHistorial(v)}</td>
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
    document.getElementById('detalleVentaBody').innerHTML = `
        <div style="background:#eef2f7;padding:16px;margin:-16px;min-height:100%">
            <div style="background:#fff;max-width:794px;margin:0 auto;padding:24px 28px;box-shadow:0 2px 14px rgba(15,23,42,.1);font-family:Arial,sans-serif;font-size:12px;color:#111">
                ${buildTicketVentaHTML(v)}
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
    const w = window.open('', '_blank', 'width=900,height=700');
    w.document.write(`<!DOCTYPE html><html><head><meta charset="utf-8"><title>Comprobante</title><style>
        *{box-sizing:border-box} body{font-family:Arial,sans-serif;font-size:12px;max-width:794px;margin:0 auto;padding:20px;color:#111}
        table{border-collapse:collapse} img{max-width:100%}
        @media print{ @page{size:A4;margin:15mm} body{padding:0;margin:0;font-size:12px} }
    </style></head><body>${html}</body></html>`);
    w.document.close();
    w.focus();
    setTimeout(() => w.print(), 350);
}

async function imprimirVentaHistorial(id) {
    try {
        const d = await apiGet(`${BASE}?action=venta_detalle&id=${id}`);
        const html = buildTicketVentaHTML(d.venta);
        const w = window.open('', '_blank', 'width=900,height=700');
        w.document.write(`<!DOCTYPE html><html><head><meta charset="utf-8"><title>Comprobante</title><style>
            *{box-sizing:border-box} body{font-family:Arial,sans-serif;font-size:12px;max-width:794px;margin:0 auto;padding:20px;color:#111}
            table{border-collapse:collapse} img{max-width:100%}
            @media print{ @page{size:A4;margin:15mm} body{padding:0;margin:0;font-size:12px} }
        </style></head><body>${html}</body></html>`);
        w.document.close();
        w.focus();
        setTimeout(() => w.print(), 350);
    } catch(e) {
        Toast.error('No se pudo preparar la impresión.');
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
    if (!lista.length) { tbody.innerHTML='<tr><td colspan="10" class="text-center text-muted">Sin cotizaciones.</td></tr>'; return; }
    tbody.innerHTML = lista.map(c => {
        const vencida = c.estado==='enviada' && c.fecha_vigencia && c.fecha_vigencia < new Date().toISOString().split('T')[0];
        const correlativo = cotCorrelativo(c.id);
        return `<tr style="cursor:pointer" onclick="editarCotizacion(${c.id})">
            <td class="font-bold text-primary">${correlativo}</td>
            <td class="font-semibold text-sm">${esc(c.numero)}</td>
            <td class="text-sm">${esc(c.cliente_nombre||'-')}</td>
            <td class="text-xs">${formatDate(c.fecha_emision)}</td>
            <td class="text-xs ${vencida?'text-danger font-bold':''}">${formatDate(c.fecha_vigencia)}</td>
            <td><span class="badge ${estadosCot[c.estado]||'badge-normal'}">${c.estado}</span></td>
            <td class="text-right text-sm">${formatMoney(c.subtotal)}</td>
            <td class="text-right text-sm">${formatMoney(c.igv)}</td>
            <td class="text-right font-bold">${formatMoney(c.total)}</td>
            <td style="white-space:nowrap" onclick="event.stopPropagation()"></td>
        </tr>`;
    }).join('');
}

function cotCorrelativo(id) {
    return 'COT' + String(id).padStart(3, '0');
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

function ventaClientesUltimos() {
    return [...clientesVenta].sort((a, b) => b.id - a.id).slice(0, 5);
}

function mostrarClientesDropVenta(lista) {
    const drop = document.getElementById('ventaClienteDrop');
    if (!drop) return;
    if (!lista.length) {
        drop.innerHTML = '<div style="padding:10px 12px;font-size:.82rem;color:var(--text-secondary)">Sin resultados</div>';
    } else {
        drop.innerHTML = lista.map(c => {
            const nombre = c.razon_social || c.nombre_completo || 'Cliente';
            const doc = c.numero_documento || c.ruc_dni || '';
            return `<div onclick="seleccionarClienteVenta(${c.id})"
                style="padding:8px 12px;cursor:pointer;font-size:.82rem;border-bottom:1px solid var(--border)"
                onmouseenter="this.style.background='#f0f4ff'" onmouseleave="this.style.background=''">
                <div style="font-weight:600">${esc(nombre)}</div>
                ${doc ? `<div style="font-size:.75rem;color:var(--text-secondary)">${esc(doc)}</div>` : ''}
            </div>`;
        }).join('');
    }
    drop.style.display = 'block';
}

function filtrarClientesVenta() {
    const q = (document.getElementById('ventaClienteBuscar')?.value || '').toLowerCase().trim();
    if (!q) { mostrarClientesDropVenta(ventaClientesUltimos()); return; }
    const res = clientesVenta.filter(c => {
        const nombre = (c.razon_social || c.nombre_completo || '').toLowerCase();
        const doc = (c.numero_documento || c.ruc_dni || '').toLowerCase();
        return nombre.includes(q) || doc.includes(q);
    }).slice(0, 5);
    mostrarClientesDropVenta(res);
}

function seleccionarClienteVenta(id) {
    const c = clientesVenta.find(x => String(x.id) === String(id));
    selectedClienteVenta = c || null;
    const input = document.getElementById('ventaClienteBuscar');
    if (input) input.value = c ? clienteVentaLabel(c) : '';
    const drop = document.getElementById('ventaClienteDrop');
    if (drop) drop.style.display = 'none';
    const esGenerico = !c || String(c.numero_documento || c.ruc_dni || '') === '00000000';
    const dir = document.getElementById('ventaClienteDireccion');
    if (dir) dir.value = esGenerico ? '' : (c?.direccion || '');
    const cel = document.getElementById('ventaClienteCelular');
    if (cel) cel.value = esGenerico ? '' : (c?.telefono || c?.celular || '');
}

document.addEventListener('click', e => {
    const drop = document.getElementById('ventaClienteDrop');
    const input = document.getElementById('ventaClienteBuscar');
    if (drop && input && !drop.contains(e.target) && e.target !== input) {
        drop.style.display = 'none';
    }
});

async function cargarClientesVenta(tipo = document.getElementById('ventaTipoComprobante')?.value || 'boleta') {
    const d = await apiGet(`${BASE}?action=clientes_combo&tipo_comprobante=${encodeURIComponent(tipo)}`);
    clientesVenta = d.clientes || [];

    let elegido = null;
    if (tipo === 'boleta') {
        elegido = clientesVenta.find(c => String(c.numero_documento || c.ruc_dni || '') === '00000000')
            || clientesVenta.find(c => String(c.razon_social || c.nombre_completo || '').toUpperCase().includes('CLIENTES VARIOS'))
            || null;
    } else if (tipo === 'factura') {
        elegido = clientesVenta.find(esClienteRuc) || null;
    } else {
        elegido = defaultClienteVenta || null;
    }
    if (elegido) seleccionarClienteVenta(elegido.id);
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
        ? lista.map(s => `<option value="${esc(s.serie)}">${esc(s.serie)}</option>`).join('')
        : '<option value="">Sin serie activa</option>';

    const corrEl = document.getElementById('ventaCorrelatvoDisplay');
    if (corrEl) {
        const s = lista[0];
        corrEl.value = s
            ? `${s.serie}-${String((parseInt(s.ultimo_numero) || 0) + 1).padStart(4, '0')}`
            : '---';
    }

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
    // Fase 1: datos críticos para la vista inicial (cliente + correlativo) — aparecen de inmediato
    const [dc, ds, ddef] = await Promise.all([
        apiGet(`${BASE}?action=clientes_combo`),
        apiGet(`${BASE}?action=series_disponibles`),
        apiGet(`${BASE}?action=default_cliente`)
    ]);

    seriesVenta = { nota_venta: [], boleta: [], factura: [] };
    (ds.series || []).forEach(s => {
        if (!seriesVenta[s.tipo]) seriesVenta[s.tipo] = [];
        seriesVenta[s.tipo].push(s);
    });
    actualizarSeriesVenta();

    clientesVenta = dc.clientes || [];
    defaultClienteVenta = ddef.cliente || null;
    const tipo = document.getElementById('ventaTipoComprobante')?.value || 'boleta';
    let elegido = null;
    if (tipo === 'boleta') {
        elegido = clientesVenta.find(c => String(c.numero_documento || c.ruc_dni || '') === '00000000')
            || clientesVenta.find(c => String(c.razon_social || c.nombre_completo || '').toUpperCase().includes('CLIENTES VARIOS'))
            || null;
    } else if (tipo === 'factura') {
        elegido = clientesVenta.find(esClienteRuc) || null;
    } else {
        elegido = defaultClienteVenta || null;
    }
    if (elegido) seleccionarClienteVenta(elegido.id);

    const opts = '<option value="">Sin cliente / clientes varios</option>' + clientesVenta.map(c=>`<option value="${c.id}">${esc(c.razon_social || c.nombre_completo || 'Cliente')}</option>`).join('');
    document.getElementById('cotCliente').innerHTML = opts;
    document.getElementById('ovCliente').innerHTML  = opts;

    combosLoaded = true;

    // Fase 2: datos secundarios — se cargan en background sin bloquear la UI
    Promise.all([
        apiGet(`${BASE}?action=maquinarias_combo`),
        apiGet(`${BASE}?action=proyectos_combo`),
        apiGet(`${BASE}?action=productos_combo`),
        apiGet(`${BASE}?action=catalogo_modelos`)
    ]).then(([dm, dp, dpr, dmod]) => {
        maqCatalogoModelos = (dmod.modelos || []).map(m => ({
            ...m,
            colores: typeof m.colores === 'string' ? (JSON.parse(m.colores || '[]') || []) : (m.colores || [])
        }));
        poblarSelectCatalogoMaq();
        document.getElementById('srvMaquinaria').innerHTML = '<option value="">Seleccionar</option>' +
            (dm.maquinarias||[]).map(m=>`<option value="${m.id}">${esc(m.label)}</option>`).join('');
        proyectosCache = dp.proyectos || [];
        productosCache = dpr.productos || [];
        renderProyectosOV();
    });
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
    const maqOpts = '<option value="">— Seleccionar maquinaria —</option>' +
        maquinariasVenta.map(m => {
            const precio = parseFloat(m.precio_venta_calc || m.precio_venta || 0);
            const label  = esc(m.modelo) + (m.numero_serie ? ` · Serie: ${esc(m.numero_serie)}` : '') + ` (${formatMoney(precio)})`;
            return `<option value="${m.id}" data-desc="${esc(m.modelo)}" data-precio="${precio}">${label}</option>`;
        }).join('');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td style="padding:6px 8px">
            <select class="form-control" style="margin-bottom:4px;font-size:0.82rem" onchange="onCotMaqChange(this)">${maqOpts}</select>
            <input type="text" class="form-control" placeholder="Descripción..." value="${esc(det?.descripcion||'')}" style="font-size:0.82rem" oninput="recalcularCot()">
        </td>
        <td><input type="number" class="form-control text-right" value="${det?.cantidad||1}" min="1" step="1" style="width:70px" onchange="recalcularLinea(this)"></td>
        <td><input type="number" class="form-control text-right" value="${det?.precio_unitario||0}" min="0" step="0.01" onchange="recalcularLinea(this)"></td>
        <td class="text-right font-semibold lin-sub" style="padding:8px 14px">${formatMoney(det?.subtotal||0)}</td>
        <td class="text-center"><button type="button" class="btn btn-outline btn-xs" onclick="this.closest('tr').remove();recalcularCot()"><i class="fa fa-times"></i></button></td>`;
    document.getElementById('cotDetalleBody').appendChild(tr);
}

function onCotMaqChange(sel) {
    const opt = sel.selectedOptions[0];
    if (!opt || !opt.value) return;
    const tr = sel.closest('tr');
    const inputs = tr.querySelectorAll('input');
    inputs[0].value = opt.dataset.desc || '';
    inputs[2].value = opt.dataset.precio || 0;
    recalcularLinea(inputs[2]);
}

function recalcularLinea(input) {
    const tr  = input.closest('tr');
    const qty = parseFloat(tr.querySelectorAll('input')[1].value)||0;
    const pu  = parseFloat(tr.querySelectorAll('input')[2].value)||0;
    const sub = qty * pu;
    tr.querySelector('.lin-sub').textContent = formatMoney(sub);
    recalcularCot();
}

function recalcularCot() {
    let sub = 0;
    document.querySelectorAll('#cotDetalleBody tr').forEach(tr => {
        const qty = parseFloat(tr.querySelectorAll('input')[1]?.value)||0;
        const pu  = parseFloat(tr.querySelectorAll('input')[2]?.value)||0;
        sub += qty * pu;
    });
    const igv   = TAX_ENABLED ? sub * 0.18 : 0;
    const total = sub + igv;
    document.getElementById('cotSubtotal').textContent = formatMoney(sub);
    document.getElementById('cotIgv').textContent      = formatMoney(igv);
    document.getElementById('cotTotal').textContent    = formatMoney(total);
    const igvRow = document.getElementById('cotIgvRow');
    if (igvRow) igvRow.style.display = TAX_ENABLED ? '' : 'none';
}

async function guardarCotizacion(closeModal = true) {
    const detalles = [];
    document.querySelectorAll('#cotDetalleBody tr').forEach(tr => {
        const inputs = tr.querySelectorAll('input');
        const desc = inputs[0].value.trim();
        if (!desc) return;
        const qty = parseFloat(inputs[1].value)||0;
        const pu  = parseFloat(inputs[2].value)||0;
        detalles.push({ descripcion:desc, cantidad:qty, precio_unitario:pu, descuento:0, subtotal:qty*pu });
    });
    if (!detalles.length) { Toast.warning('Agregue al menos un item.'); return null; }
    const res = await apiPost(BASE, { action:'cotizacion_guardar',
        id:document.getElementById('cotId').value, estado: 'aprobada',
        cliente_id:document.getElementById('cotCliente').value,
        fecha_emision:document.getElementById('cotFecha').value,
        vigencia_dias:document.getElementById('cotVigencia').value,
        observaciones:document.getElementById('cotObs').value, detalles });
    if (!res.ok) { Toast.error(res.error||'Error.'); return null; }
    document.getElementById('cotId').value = res.id;
    cargarCotizaciones();
    if (closeModal) { Toast.success('Cotización guardada.'); Modal.close('modalCotizacion'); }
    return res;
}

async function imprimirCotizacion() {
    const res = await guardarCotizacion(false);
    if (!res) return;
    const numero = cotCorrelativo(res.id);
    const clienteSel = document.getElementById('cotCliente');
    const clienteNombre = clienteSel.selectedOptions[0]?.text || '';
    const fecha = document.getElementById('cotFecha').value || '';
    const vigencia = document.getElementById('cotVigencia').value || 30;
    const obs = document.getElementById('cotObs').value || '';

    const rows = [];
    document.querySelectorAll('#cotDetalleBody tr').forEach(tr => {
        const inputs = tr.querySelectorAll('input');
        const desc = inputs[0].value.trim(); if (!desc) return;
        const qty = parseFloat(inputs[1].value)||0;
        const pu  = parseFloat(inputs[2].value)||0;
        const sub = qty * pu;
        rows.push({ desc, qty, pu, sub });
    });

    const subtotal = rows.reduce((a,r)=>a+r.sub,0);
    const igv = TAX_ENABLED ? subtotal * 0.18 : 0;
    const total = subtotal + igv;

    const logoHtml = EMPRESA_VENTA.logo
        ? `<img src="${EMPRESA_VENTA.logo}" style="max-height:60px;max-width:180px;object-fit:contain">`
        : `<div style="font-size:20px;font-weight:900;color:#f97316">${esc(EMPRESA_VENTA.nombre)}</div>`;

    const html = `<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">
<title>Cotización ${esc(numero)}</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,sans-serif;font-size:11px;color:#111;padding:28px 32px}
.hdr{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:22px}
.cot-box{border:2.5px solid #f97316;border-radius:6px;padding:12px 22px;text-align:center;min-width:150px}
.cot-box .lbl{font-size:9px;text-transform:uppercase;letter-spacing:1px;color:#64748b;margin-bottom:4px}
.cot-box .num{font-size:22px;font-weight:900;color:#f97316}
.empresa{font-size:12px;font-weight:700;color:#1e293b;margin-bottom:2px}
.empresa-sub{font-size:10px;color:#64748b}
.section{margin-bottom:16px}
.section-title{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#64748b;border-bottom:1px solid #e2e8f0;padding-bottom:4px;margin-bottom:8px}
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:4px 20px}
.info-row{display:flex;gap:6px;font-size:11px}
.info-lbl{color:#64748b;min-width:80px}
.info-val{font-weight:600;color:#1e293b}
table{width:100%;border-collapse:collapse;margin-bottom:12px}
thead th{background:#f97316;color:#fff;padding:7px 10px;text-align:left;font-size:10px;font-weight:700}
thead th.r{text-align:right}
tbody td{padding:6px 10px;border-bottom:1px solid #f1f5f9;font-size:11px;vertical-align:top}
tbody td.r{text-align:right}
tbody tr:nth-child(even){background:#f8fafc}
.totals{width:100%}
.tot-row{display:flex;justify-content:space-between;padding:8px 12px;font-size:12px;border-bottom:1px solid #f1f5f9;background:#f8fafc}
.tot-row.total{background:#fff7ed;border-top:2px solid #f97316;border-bottom:2px solid #f97316;font-size:15px;font-weight:900;color:#f97316}
.obs{background:#f8fafc;border-left:3px solid #f97316;padding:8px 12px;font-size:10px;color:#475569;border-radius:0 4px 4px 0}
@media print{body{padding:14px 20px}@page{margin:10mm}}
</style></head><body>
<div class="hdr">
  <div>
    ${logoHtml}
    <div class="empresa" style="margin-top:6px">${esc(EMPRESA_VENTA.razon_social)}</div>
    ${EMPRESA_VENTA.ruc?`<div class="empresa-sub">RUC: ${esc(EMPRESA_VENTA.ruc)}</div>`:''}
    ${EMPRESA_VENTA.direccion?`<div class="empresa-sub">${esc(EMPRESA_VENTA.direccion)}</div>`:''}
    ${EMPRESA_VENTA.telefono?`<div class="empresa-sub">Tel: ${esc(EMPRESA_VENTA.telefono)}</div>`:''}
  </div>
  <div class="cot-box">
    <div class="lbl">Cotización</div>
    <div class="num">${esc(numero)}</div>
    <div style="font-size:9px;color:#64748b;margin-top:4px">Fecha: ${esc(fecha)}</div>
    <div style="font-size:9px;color:#64748b">Vigencia: ${esc(vigencia)} días</div>
  </div>
</div>

<div class="section">
  <div class="section-title">Datos del cliente</div>
  <div class="info-grid">
    <div class="info-row"><span class="info-lbl">Cliente:</span><span class="info-val">${esc(clienteNombre || 'Sin especificar')}</span></div>
    <div class="info-row"><span class="info-lbl">Fecha:</span><span class="info-val">${esc(fecha)}</span></div>
  </div>
</div>

<div class="section">
  <div class="section-title">Detalle de cotización</div>
  <table>
    <thead><tr>
      <th style="width:54%">Descripción</th>
      <th class="r" style="width:10%">Cant.</th>
      <th class="r" style="width:18%">Precio Unit.</th>
      <th class="r" style="width:18%">Subtotal</th>
    </tr></thead>
    <tbody>
      ${rows.map(r=>`<tr>
        <td>${esc(r.desc)}</td>
        <td class="r">${r.qty}</td>
        <td class="r">S/ ${r.pu.toFixed(2)}</td>
        <td class="r">S/ ${r.sub.toFixed(2)}</td>
      </tr>`).join('')}
    </tbody>
  </table>
  <div class="totals">
    <div class="tot-row"><span>Subtotal:</span><span>S/ ${subtotal.toFixed(2)}</span></div>
    ${TAX_ENABLED?`<div class="tot-row"><span>IGV (18%):</span><span>S/ ${igv.toFixed(2)}</span></div>`:''}
    <div class="tot-row total"><span>TOTAL:</span><span>S/ ${total.toFixed(2)}</span></div>
  </div>
</div>

${obs?`<div class="section"><div class="section-title">Observaciones</div><div class="obs">${esc(obs)}</div></div>`:''}

<script>
function esc(s){const d=document.createElement('div');d.textContent=String(s||'');return d.innerHTML;}
window.onload=()=>{ window.print(); };
<\/script>
</body></html>`;

    const w = window.open('', '_blank', 'width=900,height=700');
    w.document.write(html);
    w.document.close();
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
    const estadoFiltro = document.getElementById('filtroEstadoMaq')?.value || 'todos';
    const params = new URLSearchParams({action:'maquinarias_listar', buscar:document.getElementById('buscarMaq').value, estado_venta:estadoFiltro});
    const d = await apiGet(`${BASE}?${params}`);
    const tbody = document.getElementById('maquinariasBody');
    maquinariasVenta = d.maquinarias||[];

    // Stats cards
    const st = d.stats || {};
    const msEl = document.getElementById('maqStats');
    if (msEl) {
        document.getElementById('maqStatTotal').textContent = st.total || 0;
        document.getElementById('maqStatDisp').textContent  = st.disponibles || 0;
        document.getElementById('maqStatVend').textContent  = st.vendidas || 0;
        msEl.style.display = 'grid';
    }

    const lista = maquinariasVenta;
    if (!lista.length) { tbody.innerHTML='<tr><td colspan="8" class="text-center text-muted">Sin maquinarias registradas.</td></tr>'; return; }
    const vBadge = { disponible:'badge-entrega', vendida:'badge-fabricacion', reservada:'badge-pruebas' };
    tbody.innerHTML = lista.map(m => `<tr style="cursor:pointer" onclick="editarMaquinaria(${m.id})">
        <td class="text-xs text-muted">${esc(m.codigo||'-')}</td>
        <td class="font-semibold">${esc(m.modelo||'-')}</td>
        <td class="text-xs">${esc(m.numero_serie||'-')}</td>
        <td class="font-semibold">${m.disponible_siempre
            ? '<span style="color:#16a34a">Siempre disponible</span>'
            : (m.estado_venta === 'vendida' ? 0 : (m.stock_actual !== null && m.stock_actual !== undefined ? parseInt(parseFloat(m.stock_actual)) : 1))}</td>
        <td class="font-semibold">${formatMoney(m.costo_total_calc || m.costo_total || 0)}</td>
        <td class="font-bold" style="color:var(--primary)">${formatMoney(m.precio_venta_calc || m.precio_venta || 0)}</td>
        <td class="text-sm">${ventaFechaHoraLabel(m.created_at)}</td>
        <td onclick="event.stopPropagation()">
            ${(m.estado_venta||'disponible') === 'vendida'
                ? `<button class="btn btn-outline btn-xs" onclick="nuevoServicioMaq(${m.id})" title="Nuevo servicio"><i class="fa fa-screwdriver-wrench"></i></button>`
                : ''}
        </td>
    </tr>`).join('');
}

// ── Catálogo de modelos (para registrar maquinaria) ───────────────
const MAQ_CAT_IMG = '../../assets/uploads/catalogo/';
let maqCatalogoModelos = [];

function poblarSelectCatalogoMaq() {
    const sel = document.getElementById('maqCatalogo');
    if (!sel) return;
    sel.innerHTML = '<option value="">— Selecciona un modelo —</option>' +
        maqCatalogoModelos.map(m => `<option value="${m.id}">${esc(m.nombre)}</option>`).join('');
}

function maqModeloActual() {
    const id = document.getElementById('maqCatalogo').value;
    return maqCatalogoModelos.find(m => String(m.id) === String(id)) || null;
}

function onMaqCatalogoChange() {
    const m = maqModeloActual();
    const colorSel = document.getElementById('maqColor');
    document.getElementById('maqModelo').value = m ? (m.nombre || '') : '';
    if (!m) { colorSel.innerHTML = '<option value="">—</option>'; onMaqColorChange(); return; }
    const desc = document.getElementById('maqDesc');
    if (!desc.value.trim()) desc.value = m.descripcion || m.descripcion_corta || '';
    const colores = m.colores || [];
    colorSel.innerHTML = '<option value="">' + (colores.length ? 'Selecciona color' : 'Sin colores') + '</option>' +
        colores.map(c => `<option value="${esc(c.nombre)}">${esc(c.nombre)}</option>`).join('');
    onMaqColorChange();
}

function onMaqColorChange() {
    const m = maqModeloActual();
    const color = document.getElementById('maqColor').value;
    let img = m ? (m.imagen_principal || null) : null;
    if (m && color) {
        const c = (m.colores || []).find(x => x.nombre === color);
        if (c && c.imagen) img = c.imagen;
    }
    const prev = document.getElementById('maqImgPreview');
    const icon = document.getElementById('maqImgIcon');
    if (img) { prev.src = MAQ_CAT_IMG + img; prev.style.display = ''; icon.style.display = 'none'; }
    else { prev.src = ''; prev.style.display = 'none'; icon.style.display = ''; }
}

function abrirModalMaquinaria() {
    ['maqId','maqCodigo','maqModelo','maqSerie','maqDesc'].forEach(id=>document.getElementById(id).value='');
    document.getElementById('maqFechaVenta').value=''; document.getElementById('maqGarantia').value=12;
    document.getElementById('maqCosto').value='0.00'; document.getElementById('maqPrecio').value='0.00';
    document.getElementById('maqEstadoVenta').value='disponible';
    document.getElementById('maqDisponibleSiempre').checked=false;
    document.getElementById('maqStock').value=1;
    document.getElementById('maqCatalogo').value=''; onMaqCatalogoChange();
    document.getElementById('tituloMaq').textContent='Registrar Maquinaria';
    Modal.open('modalMaquinaria');
}

async function editarMaquinaria(id) {
    if (!maquinariasVenta.length) {
        const d = await apiGet(`${BASE}?action=maquinarias_listar&buscar=`);
        maquinariasVenta = d.maquinarias || [];
    }
    const m = maquinariasVenta.find(x=>x.id==id); if(!m) return;
    document.getElementById('maqId').value=m.id;
    document.getElementById('maqCatalogo').value = m.catalogo_producto_id || '';
    onMaqCatalogoChange();
    const colorSel = document.getElementById('maqColor');
    if (m.color && !Array.from(colorSel.options).some(o=>o.value===m.color)) {
        colorSel.insertAdjacentHTML('beforeend', `<option value="${esc(m.color)}">${esc(m.color)}</option>`);
    }
    colorSel.value = m.color || '';
    onMaqColorChange();
    document.getElementById('maqCodigo').value=m.codigo||''; document.getElementById('maqModelo').value=m.modelo||'';
    document.getElementById('maqSerie').value=m.numero_serie||''; document.getElementById('maqFechaVenta').value=m.fecha_venta||'';
    document.getElementById('maqCosto').value=parseFloat(m.costo_total_calc || m.costo_total || 0).toFixed(2);
    document.getElementById('maqPrecio').value=parseFloat(m.precio_venta_calc || m.precio_venta || 0).toFixed(2);
    document.getElementById('maqEstadoVenta').value=m.estado_venta || 'disponible';
    document.getElementById('maqDisponibleSiempre').checked = normalizarBooleano(m.disponible_siempre);
    document.getElementById('maqStock').value = (m.stock_actual !== null && m.stock_actual !== undefined)
        ? Math.max(0, parseInt(parseFloat(m.stock_actual)))
        : (m.estado_venta === 'vendida' ? 0 : 1);
    document.getElementById('maqGarantia').value=m.garantia_meses||12; document.getElementById('maqDesc').value=m.descripcion||'';
    document.getElementById('tituloMaq').textContent='Editar Maquinaria';
    Modal.open('modalMaquinaria');
}

async function guardarMaquinaria() {
    if (!document.getElementById('maqCatalogo').value) { Toast.warning('Selecciona el modelo del catálogo.'); return; }
    const res = await apiPost(BASE,{action:'maquinaria_guardar', id:document.getElementById('maqId').value,
        catalogo_producto_id:document.getElementById('maqCatalogo').value,
        color:document.getElementById('maqColor').value,
        modelo:document.getElementById('maqModelo').value, codigo:document.getElementById('maqCodigo').value,
        numero_serie:document.getElementById('maqSerie').value,
        costo_total:document.getElementById('maqCosto').value, precio_venta:document.getElementById('maqPrecio').value,
        estado_venta:document.getElementById('maqEstadoVenta').value,
        fecha_venta:document.getElementById('maqFechaVenta').value, garantia_meses:document.getElementById('maqGarantia').value,
        disponible_siempre:document.getElementById('maqDisponibleSiempre').checked,
        stock:document.getElementById('maqStock').value,
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




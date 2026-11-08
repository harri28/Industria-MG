<?php
$page_title      = 'Reportes y Análisis';
$page_breadcrumb = 'Reportes';
$extra_js = '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>';
require_once __DIR__ . '/../../includes/header.php';
?>

<style>
.kpi-new{background:#fff;border:1px solid var(--border);border-radius:10px;padding:16px 18px;transition:box-shadow .15s}
.kpi-new:hover{box-shadow:0 2px 8px rgba(0,0,0,.08)}
.kpi-new .kpi-icon-box{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:17px}
.kpi-new .kpi-val{font-size:1.45rem;font-weight:700;color:#0f172a;line-height:1.1;margin:6px 0 2px}
.kpi-new .kpi-lbl{font-size:0.75rem;color:#64748b;font-weight:500;text-transform:uppercase;letter-spacing:.04em}
.kpi-new .kpi-sub{font-size:0.72rem;color:#94a3b8;margin-top:3px}
.trend-badge{font-size:0.72rem;font-weight:600;padding:2px 8px;border-radius:12px}
.chart-box{position:relative}
.section-mini-kpi{display:grid;gap:12px;margin-bottom:16px}
</style>

<div class="tabs" id="mainTabs">
    <div class="tab active" data-group="rep" data-target="tab-kpis"><i class="fa fa-gauge"></i> Dashboard</div>
    <div class="tab" data-group="rep" data-target="tab-produccion"><i class="fa fa-industry"></i> Producción</div>
    <div class="tab" data-group="rep" data-target="tab-ventas"><i class="fa fa-file-invoice-dollar"></i> Ventas</div>
    <div class="tab" data-group="rep" data-target="tab-compras"><i class="fa fa-cart-shopping"></i> Compras</div>
    <div class="tab" data-group="rep" data-target="tab-inventario"><i class="fa fa-boxes-stacked"></i> Inventario</div>
    <div class="tab" data-group="rep" data-target="tab-alertas"><i class="fa fa-bell"></i> Alertas</div>
    <div class="tab" data-group="rep" data-target="tab-rentabilidad"><i class="fa fa-chart-line"></i> Rentabilidad</div>
    <div class="tab" data-group="rep" data-target="tab-comprobantes"><i class="fa fa-receipt"></i> Comprobantes</div>
    <div class="tab" data-group="rep" data-target="tab-productividad"><i class="fa fa-person-digging"></i> Productividad</div>
</div>

<!-- ============================================================
     DASHBOARD (KPIs)
     ============================================================ -->
<div class="tab-content active" data-group="rep" id="tab-kpis">

    <!-- KPI Cards -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px">
        <div class="kpi-new">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <div class="kpi-icon-box" style="background:#ffedd5"><i class="fa fa-arrow-trend-up" style="color:#ea580c"></i></div>
                <span id="kTrendIngresos" class="trend-badge" style="background:#f1f5f9;color:#94a3b8">—</span>
            </div>
            <div class="kpi-val" id="k-ingresos">—</div>
            <div class="kpi-lbl">Ingresos del Mes</div>
            <div class="kpi-sub" id="k-ingresos-ant"></div>
        </div>
        <div class="kpi-new">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <div class="kpi-icon-box" style="background:#dbeafe"><i class="fa fa-industry" style="color:#1d4ed8"></i></div>
                <span id="kProyRetrasados" class="trend-badge" style="background:#fee2e2;color:#991b1b">— ret.</span>
            </div>
            <div class="kpi-val" id="k-proy-activos">—</div>
            <div class="kpi-lbl">Proyectos Activos</div>
            <div class="kpi-sub" id="k-proy-comp"></div>
        </div>
        <div class="kpi-new">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <div class="kpi-icon-box" style="background:#dcfce7"><i class="fa fa-boxes-stacked" style="color:#16a34a"></i></div>
                <span id="kStockCritBadge" class="trend-badge" style="background:#ffedd5;color:#c2410c">— críticos</span>
            </div>
            <div class="kpi-val" id="k-inv">—</div>
            <div class="kpi-lbl">Valor Inventario</div>
            <div class="kpi-sub">productos activos</div>
        </div>
        <div class="kpi-new">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <div class="kpi-icon-box" style="background:#ede9fe"><i class="fa fa-cart-shopping" style="color:#7c3aed"></i></div>
                <span id="kTrendCompras" class="trend-badge" style="background:#f1f5f9;color:#94a3b8">—</span>
            </div>
            <div class="kpi-val" id="k-compras">—</div>
            <div class="kpi-lbl">Compras del Mes</div>
            <div class="kpi-sub" id="k-compras-ant"></div>
        </div>
    </div>

    <!-- Gráficos principales -->
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:16px">
        <div class="card">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                <span><i class="fa fa-chart-column" style="color:var(--primary)"></i> Ingresos vs Compras — Últimos 6 meses</span>
                <span style="font-size:0.73rem;color:#94a3b8">órdenes confirmadas</span>
            </div>
            <div class="chart-box" style="padding:16px;height:250px">
                <canvas id="chartIC"></canvas>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><i class="fa fa-chart-pie" style="color:#7c3aed"></i> Proyectos por Estado</div>
            <div class="chart-box" style="padding:16px;height:250px">
                <canvas id="chartPE"></canvas>
            </div>
        </div>
    </div>

    <!-- Segunda fila: alertas + stock crítico -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div class="card">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                <span><i class="fa fa-bell" style="color:#dc2626"></i> Alertas Pendientes</span>
                <span class="badge badge-danger" id="kAlertasCount" style="font-size:0.8rem">—</span>
            </div>
            <div id="kAlertasMini" style="padding:12px;font-size:0.83rem;min-height:80px">
                <div class="text-muted text-center" style="padding:16px">Cargando…</div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><i class="fa fa-triangle-exclamation" style="color:#d97706"></i> Stock Crítico</div>
            <table class="table" id="kStockTable" style="font-size:0.82rem;margin:0">
                <thead><tr><th>Producto</th><th class="text-right">Stock</th><th class="text-right">Mínimo</th></tr></thead>
                <tbody><tr><td colspan="3" class="text-center text-muted" style="padding:12px">Cargando…</td></tr></tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================
     PRODUCCIÓN
     ============================================================ -->
<div class="tab-content" data-group="rep" id="tab-produccion">
    <div class="toolbar">
        <div class="toolbar-left">
            <label style="font-size:0.85rem">Desde:</label>
            <input type="date" class="form-control" id="prodDesde" style="width:150px">
            <label style="font-size:0.85rem">Hasta:</label>
            <input type="date" class="form-control" id="prodHasta" style="width:150px">
        </div>
        <div class="toolbar-right">
            <button class="btn btn-primary" onclick="cargarProduccion()"><i class="fa fa-search"></i> Consultar</button>
        </div>
    </div>

    <!-- Mini KPIs producción -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px" id="prodKpis">
        <div class="card" style="padding:14px 16px;text-align:center">
            <div style="font-size:1.6rem;font-weight:700;color:#1d4ed8" id="pKpiActivos">—</div>
            <div style="font-size:0.73rem;color:#64748b;text-transform:uppercase">Activos</div>
        </div>
        <div class="card" style="padding:14px 16px;text-align:center">
            <div style="font-size:1.6rem;font-weight:700;color:#dc2626" id="pKpiRetrasados">—</div>
            <div style="font-size:0.73rem;color:#64748b;text-transform:uppercase">Retrasados</div>
        </div>
        <div class="card" style="padding:14px 16px;text-align:center">
            <div style="font-size:1.6rem;font-weight:700;color:#16a34a" id="pKpiCompletados">—</div>
            <div style="font-size:0.73rem;color:#64748b;text-transform:uppercase">Completados (mes)</div>
        </div>
        <div class="card" style="padding:14px 16px;text-align:center">
            <div style="font-size:1.6rem;font-weight:700;color:#7c3aed" id="pKpiTotal">—</div>
            <div style="font-size:0.73rem;color:#64748b;text-transform:uppercase">Total período</div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
        <!-- Gráfico bar: proyectos por mes -->
        <div class="card">
            <div class="card-header"><i class="fa fa-chart-bar" style="color:var(--primary)"></i> Proyectos por Mes</div>
            <div class="chart-box" style="padding:16px;height:220px">
                <canvas id="chartProdMes"></canvas>
            </div>
        </div>
        <!-- Estados + retrasados -->
        <div class="card">
            <div class="card-header">Proyectos Retrasados</div>
            <div style="overflow:auto;max-height:220px">
                <table class="table" id="tablaRetrasados" style="margin:0;font-size:0.83rem">
                    <thead><tr><th>Código</th><th>Nombre</th><th>F. Estimada</th><th>Días retraso</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><i class="fa fa-scale-balanced" style="color:#7c3aed"></i> Costo Planificado vs Real</div>
        <table class="table" id="tablaProdCostos">
            <thead>
                <tr><th>Código</th><th>Nombre</th><th>Costo Plan.</th><th>Costo Real</th><th>Desviación</th><th>Avance</th><th>Estado</th></tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- ============================================================
     VENTAS
     ============================================================ -->
<div class="tab-content" data-group="rep" id="tab-ventas">
    <div class="toolbar">
        <div class="toolbar-left">
            <label style="font-size:0.85rem">Desde:</label>
            <input type="date" class="form-control" id="ventasDesde" style="width:150px">
            <label style="font-size:0.85rem">Hasta:</label>
            <input type="date" class="form-control" id="ventasHasta" style="width:150px">
        </div>
        <div class="toolbar-right">
            <button class="btn btn-primary" onclick="cargarVentas()"><i class="fa fa-search"></i> Consultar</button>
        </div>
    </div>

    <!-- Mini KPIs ventas -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:16px" id="ventasKpis">
        <div class="card" style="padding:14px 16px">
            <div style="font-size:0.72rem;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px"><i class="fa fa-sack-dollar" style="color:#ea580c"></i> Ingresos período</div>
            <div style="font-size:1.3rem;font-weight:700;color:#0f172a" id="vKpiIngresos">—</div>
        </div>
        <div class="card" style="padding:14px 16px">
            <div style="font-size:0.72rem;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px"><i class="fa fa-file-invoice" style="color:#1d4ed8"></i> Órdenes de Venta</div>
            <div style="font-size:1.3rem;font-weight:700;color:#0f172a" id="vKpiOrdenes">—</div>
        </div>
        <div class="card" style="padding:14px 16px">
            <div style="font-size:0.72rem;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px"><i class="fa fa-receipt" style="color:#7c3aed"></i> Ticket Promedio</div>
            <div style="font-size:1.3rem;font-weight:700;color:#0f172a" id="vKpiTicket">—</div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:16px">
        <!-- Line chart: ingresos OV por mes -->
        <div class="card">
            <div class="card-header"><i class="fa fa-chart-line" style="color:#ea580c"></i> Ingresos Mensuales (Órdenes de Venta)</div>
            <div class="chart-box" style="padding:16px;height:230px">
                <canvas id="chartVentasMes"></canvas>
            </div>
        </div>
        <!-- Donut: pipeline cotizaciones -->
        <div class="card">
            <div class="card-header"><i class="fa fa-chart-pie" style="color:#1d4ed8"></i> Pipeline Cotizaciones</div>
            <div class="chart-box" style="padding:16px;height:230px">
                <canvas id="chartVentasPipeline"></canvas>
            </div>
        </div>
    </div>

    <!-- Top clientes -->
    <div class="card">
        <div class="card-header"><i class="fa fa-trophy" style="color:#d97706"></i> Top 10 Clientes por Ingresos</div>
        <div class="chart-box" style="padding:16px;height:220px">
            <canvas id="chartTopClientes"></canvas>
        </div>
    </div>
</div>

<!-- ============================================================
     COMPRAS
     ============================================================ -->
<div class="tab-content" data-group="rep" id="tab-compras">
    <div class="toolbar">
        <div class="toolbar-left">
            <label style="font-size:0.85rem">Desde:</label>
            <input type="date" class="form-control" id="comprasDesde" style="width:150px">
            <label style="font-size:0.85rem">Hasta:</label>
            <input type="date" class="form-control" id="comprasHasta" style="width:150px">
        </div>
        <div class="toolbar-right">
            <button class="btn btn-primary" onclick="cargarCompras()"><i class="fa fa-search"></i> Consultar</button>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
        <div class="card">
            <div class="card-header"><i class="fa fa-chart-bar" style="color:#7c3aed"></i> Compras Mensuales</div>
            <div class="chart-box" style="padding:16px;height:250px">
                <canvas id="chartComprasMes"></canvas>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><i class="fa fa-trophy" style="color:#d97706"></i> Top Proveedores por Monto</div>
            <div class="chart-box" style="padding:16px;height:250px">
                <canvas id="chartTopProv"></canvas>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Detalle Proveedores</div>
        <table class="table" id="tablaTopProv">
            <thead><tr><th>Proveedor</th><th class="text-right">OCs</th><th class="text-right">Monto</th><th>Calificación</th></tr></thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- ============================================================
     INVENTARIO
     ============================================================ -->
<div class="tab-content" data-group="rep" id="tab-inventario">
    <div class="toolbar">
        <div class="toolbar-right">
            <button class="btn btn-primary" onclick="cargarInventario()"><i class="fa fa-sync"></i> Actualizar</button>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1.5fr;gap:16px;margin-bottom:16px">
        <div class="card">
            <div class="card-header"><i class="fa fa-chart-pie" style="color:#16a34a"></i> Valor por Categoría</div>
            <div class="chart-box" style="padding:16px;height:280px">
                <canvas id="chartInvCategoria"></canvas>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><i class="fa fa-triangle-exclamation" style="color:#dc2626"></i> Stock Crítico</div>
            <table class="table" id="tablaStockCritico" style="font-size:0.83rem">
                <thead><tr><th>Código</th><th>Nombre</th><th>Categoría</th><th class="text-right">Stock</th><th class="text-right">Mínimo</th><th>Unidad</th><th class="text-right">Valor</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><i class="fa fa-clock" style="color:#94a3b8"></i> Productos sin movimiento (+30 días)</div>
        <table class="table" id="tablaSinMovimiento" style="font-size:0.83rem">
            <thead><tr><th>Código</th><th>Nombre</th><th class="text-right">Stock</th><th>Último movimiento</th></tr></thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- ============================================================
     ALERTAS
     ============================================================ -->
<div class="tab-content" data-group="rep" id="tab-alertas">
    <div class="toolbar">
        <div class="toolbar-left">
            <select class="form-control" id="alertaFiltro" style="width:160px">
                <option value="">Todas</option>
                <option value="false">No leídas</option>
                <option value="true">Leídas</option>
            </select>
        </div>
        <div class="toolbar-right">
            <button class="btn btn-secondary" onclick="generarAlertas()">
                <i class="fa fa-sync"></i> Generar automáticas
            </button>
            <button class="btn btn-primary" onclick="marcarTodasLeidas()">
                <i class="fa fa-check-double"></i> Marcar todas leídas
            </button>
        </div>
    </div>
    <div class="card">
        <table class="table" id="tablaAlertas">
            <thead><tr><th>Tipo</th><th>Mensaje</th><th>Referencia</th><th>Fecha</th><th>Estado</th><th>Acción</th></tr></thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- ============================================================
     RENTABILIDAD
     ============================================================ -->
<div class="tab-content" data-group="rep" id="tab-rentabilidad">
    <div class="toolbar">
        <div class="toolbar-left">
            <input type="date" class="form-control" id="rentDesde" style="width:140px">
            <input type="date" class="form-control" id="rentHasta" style="width:140px">
            <select class="form-control" id="rentEstado" style="width:150px">
                <option value="todos">Todos los estados</option>
                <option value="fabricacion">Fabricación</option>
                <option value="pruebas">Pruebas</option>
                <option value="entrega">Entrega</option>
                <option value="completado">Completado</option>
            </select>
            <button class="btn btn-secondary" onclick="cargarRentabilidad()">
                <i class="fa fa-filter"></i> Filtrar
            </button>
        </div>
    </div>
    <div id="rentKpis" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin-bottom:16px"></div>

    <!-- Gráfico horizontal bar: top proyectos por margen% -->
    <div class="card" style="margin-bottom:16px">
        <div class="card-header"><i class="fa fa-chart-bar" style="color:#ea580c"></i> Margen % por Proyecto (Top 10)</div>
        <div class="chart-box" style="padding:16px;height:280px">
            <canvas id="chartRentMargenes"></canvas>
        </div>
    </div>

    <div class="card" style="overflow:hidden">
        <table class="table" style="margin:0">
            <thead>
                <tr>
                    <th>Proyecto</th>
                    <th>Cliente</th>
                    <th>Estado</th>
                    <th class="text-right">Ingreso</th>
                    <th class="text-right">Costo real</th>
                    <th class="text-right">Margen</th>
                    <th>Margen %</th>
                </tr>
            </thead>
            <tbody id="tbRentabilidad">
                <tr><td colspan="7" class="text-center text-muted">Selecciona un rango y filtra</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ============================================================
     COMPROBANTES ELECTRÓNICOS
     ============================================================ -->
<div class="tab-content" data-group="rep" id="tab-comprobantes">
    <div class="toolbar">
        <div class="toolbar-left">
            <input type="date" class="form-control" id="compDesde" style="width:140px">
            <input type="date" class="form-control" id="compHasta" style="width:140px">
            <select class="form-control" id="compTipo" style="width:150px">
                <option value="">Todos los tipos</option>
                <option value="01">Factura</option>
                <option value="03">Boleta</option>
                <option value="NV">Nota de Venta</option>
                <option value="07">Nota de Crédito</option>
                <option value="08">Nota de Débito</option>
            </select>
            <select class="form-control" id="compEstado" style="width:140px">
                <option value="">Todos los estados</option>
                <option value="emitido">Emitido</option>
                <option value="aceptado">Aceptado SUNAT</option>
                <option value="rechazado">Rechazado</option>
                <option value="anulado">Anulado</option>
            </select>
            <input type="text" class="form-control" id="compBuscar" placeholder="N° o cliente…" style="width:160px">
            <button class="btn btn-secondary" onclick="cargarComprobantes()">
                <i class="fa fa-filter"></i> Filtrar
            </button>
        </div>
    </div>

    <div id="compResumen" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;margin-bottom:16px"></div>

    <div class="card" style="overflow:hidden">
        <table class="table" style="margin:0;font-size:.83rem">
            <thead>
                <tr>
                    <th>Número</th><th>Tipo</th><th>Orden Venta</th><th>Cliente</th><th>RUC / DNI</th><th>Fecha</th>
                    <th class="text-right">Subtotal</th><th class="text-right">IGV</th><th class="text-right">Total</th><th>Estado</th>
                </tr>
            </thead>
            <tbody id="tbComprobantes">
                <tr><td colspan="10" class="text-center text-muted">Selecciona un rango y filtra</td></tr>
            </tbody>
            <tfoot id="tfComprobantes" style="display:none">
                <tr style="font-weight:700;background:#f8fafc">
                    <td colspan="6">TOTALES</td>
                    <td class="text-right" id="compTotalSubtotal"></td>
                    <td class="text-right" id="compTotalIgv"></td>
                    <td class="text-right" id="compTotalTotal"></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- ============================================================
     PRODUCTIVIDAD POR ÁREA
     ============================================================ -->
<div class="tab-content" data-group="rep" id="tab-productividad">
    <div class="toolbar">
        <div class="toolbar-left">
            <input type="date" class="form-control" id="prodAreaDesde" style="width:140px">
            <input type="date" class="form-control" id="prodAreaHasta" style="width:140px">
            <button class="btn btn-secondary" onclick="cargarProductividad()">
                <i class="fa fa-filter"></i> Filtrar
            </button>
        </div>
    </div>

    <!-- Gráfico de barras por área -->
    <div class="card" style="margin-bottom:16px">
        <div class="card-header"><i class="fa fa-chart-bar" style="color:var(--primary)"></i> Partes Completadas por Área</div>
        <div class="chart-box" style="padding:16px;height:220px">
            <canvas id="chartProdAreas"></canvas>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
        <div class="card" style="overflow:hidden">
            <div class="card-header"><span class="card-title"><i class="fa fa-location-dot"></i> Métricas por área</span></div>
            <table class="table" style="margin:0;font-size:.83rem">
                <thead><tr><th>Área</th><th class="text-right">Completadas</th><th class="text-right">Backlog</th><th class="text-right">T. Prom.</th></tr></thead>
                <tbody id="tbProdAreas"><tr><td colspan="4" class="text-center text-muted">Cargando…</td></tr></tbody>
            </table>
        </div>
        <div class="card" style="overflow:hidden">
            <div class="card-header"><span class="card-title"><i class="fa fa-users"></i> Top operarios</span></div>
            <table class="table" style="margin:0;font-size:.83rem">
                <thead><tr><th>Operario</th><th>Área</th><th class="text-right">Partes</th><th class="text-right">T. Prom.</th></tr></thead>
                <tbody id="tbProdOperarios"><tr><td colspan="4" class="text-center text-muted">Cargando…</td></tr></tbody>
            </table>
        </div>
    </div>

    <!-- Gráfico de línea: tendencia 30 días -->
    <div class="card">
        <div class="card-header"><i class="fa fa-chart-line" style="color:#0891b2"></i> Tendencia — Últimos 30 días</div>
        <div class="chart-box" style="padding:16px;height:200px">
            <canvas id="chartProdTendencia"></canvas>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
// ── URL base de esta API ──────────────────────────────────────────────────
const BASE_REP = '<?= APP_BASE ?>modules/reportes/api.php';

// ── Configuración Chart.js ────────────────────────────────────────────────
if (typeof Chart !== 'undefined') {
    Chart.defaults.font.family = "system-ui,-apple-system,sans-serif";
    Chart.defaults.font.size   = 11;
    Chart.defaults.color       = '#64748b';
}

// ── Helpers ───────────────────────────────────────────────────────────────
function mkChart(id, config) {
    if (typeof Chart === 'undefined') return null;
    const exist = Chart.getChart(id);
    if (exist) exist.destroy();
    const el = document.getElementById(id);
    if (!el) return null;
    return new Chart(el, config);
}
function mesLabel(mes) {
    const names = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    const [y, m] = mes.split('-');
    const yr = parseInt(y);
    return names[parseInt(m)-1] + (yr !== new Date().getFullYear() ? ' \''+y.slice(2) : '');
}
function escHtml(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function trendBadge(cur, ant, inverse) {
    if (!ant || ant == 0) return '<span style="background:#f1f5f9;color:#94a3b8;font-size:.72rem;font-weight:600;padding:2px 8px;border-radius:12px">—</span>';
    const pct = ((cur - ant) / ant * 100).toFixed(1);
    const up = parseFloat(pct) >= 0;
    const good = inverse ? !up : up;
    const bg = good ? '#dcfce7' : '#fee2e2';
    const col = good ? '#166534' : '#991b1b';
    return `<span style="background:${bg};color:${col};font-size:.72rem;font-weight:600;padding:2px 8px;border-radius:12px">${up?'▲':'▼'} ${Math.abs(pct)}%</span>`;
}

// ── Inicialización ────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const hoy    = new Date().toISOString().split('T')[0];
    const priMes = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];
    const priAno = new Date().getFullYear() + '-01-01';

    document.getElementById('prodDesde').value       = priMes;
    document.getElementById('prodHasta').value       = hoy;
    document.getElementById('ventasDesde').value     = priAno;
    document.getElementById('ventasHasta').value     = hoy;
    document.getElementById('comprasDesde').value    = priAno;
    document.getElementById('comprasHasta').value    = hoy;
    document.getElementById('rentDesde').value       = priAno;
    document.getElementById('rentHasta').value       = hoy;
    document.getElementById('compDesde').value       = priMes;
    document.getElementById('compHasta').value       = hoy;
    document.getElementById('prodAreaDesde').value   = priAno;
    document.getElementById('prodAreaHasta').value   = hoy;

    cargarKPIs();
    document.getElementById('alertaFiltro').addEventListener('change', cargarAlertas);

    document.querySelectorAll('.tab[data-group="rep"]').forEach(tab => tab.addEventListener('click', () => {
        const target = tab.dataset.target.replace('tab-', '');
        if (target === 'alertas')      cargarAlertas();
        if (target === 'inventario')   cargarInventario();
        if (target === 'produccion')   cargarProduccion();
        if (target === 'ventas')       cargarVentas();
        if (target === 'rentabilidad') cargarRentabilidad();
        if (target === 'comprobantes') cargarComprobantes();
        if (target === 'productividad')cargarProductividad();
        if (target === 'compras')      cargarCompras();
    }));
});

// ── DASHBOARD KPIs ────────────────────────────────────────────────────────
async function cargarKPIs() {
    const d = await apiGet(BASE_REP + '?action=kpis');
    if (!d.ok) return;

    document.getElementById('k-ingresos').textContent    = formatMoney(d.ingresos_mes);
    document.getElementById('k-proy-activos').textContent= d.proy_activos;
    document.getElementById('k-inv').textContent         = formatMoney(d.valor_inv);
    document.getElementById('k-compras').textContent     = formatMoney(d.compras_mes);
    document.getElementById('kAlertasCount').textContent = d.alertas_no_leidas;

    document.getElementById('kTrendIngresos').innerHTML  = trendBadge(d.ingresos_mes, d.ingresos_ant);
    document.getElementById('kTrendCompras').innerHTML   = trendBadge(d.compras_mes, d.compras_ant, true);

    if (d.ingresos_ant > 0)
        document.getElementById('k-ingresos-ant').textContent = 'vs ' + formatMoney(d.ingresos_ant) + ' anterior';
    if (d.compras_ant > 0)
        document.getElementById('k-compras-ant').textContent  = 'vs ' + formatMoney(d.compras_ant) + ' anterior';

    const kRet = document.getElementById('kProyRetrasados');
    kRet.textContent = d.proy_retrasados + ' ret.';
    kRet.style.background = d.proy_retrasados > 0 ? '#fee2e2' : '#dcfce7';
    kRet.style.color = d.proy_retrasados > 0 ? '#991b1b' : '#166534';

    document.getElementById('k-proy-comp').textContent = d.proy_completados_mes + ' completados este mes';
    document.getElementById('kStockCritBadge').textContent = d.stock_critico + ' críticos';

    // Gráfico: Ingresos vs Compras 6 meses
    const allM = [...new Set([
        ...(d.ingresos_6meses||[]).map(r => r.mes),
        ...(d.compras_6meses||[]).map(r => r.mes)
    ])].sort();
    const ingMap  = Object.fromEntries((d.ingresos_6meses||[]).map(r => [r.mes, parseFloat(r.ingresos)]));
    const compMap = Object.fromEntries((d.compras_6meses||[]).map(r => [r.mes, parseFloat(r.monto)]));

    mkChart('chartIC', {
        type: 'bar',
        data: {
            labels: allM.map(mesLabel),
            datasets: [
                { label: 'Ingresos', data: allM.map(m => ingMap[m]||0),
                  backgroundColor: 'rgba(234,88,12,0.82)', borderRadius: 5, borderSkipped: false },
                { label: 'Compras', data: allM.map(m => compMap[m]||0),
                  backgroundColor: 'rgba(29,78,216,0.72)', borderRadius: 5, borderSkipped: false }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top', labels: { usePointStyle: true, padding: 12 } },
                tooltip: { callbacks: { label: ctx => ` ${ctx.dataset.label}: ${formatMoney(ctx.raw)}` } }
            },
            scales: { y: { beginAtZero: true, ticks: { callback: v => v >= 1000 ? 'S/'+Math.round(v/1000)+'k' : formatMoney(v) } } }
        }
    });

    // Gráfico: Proyectos por estado (donut)
    const EM = { fabricacion:'Fabricación', pruebas:'Pruebas', entrega:'Entrega', completado:'Completado', cancelado:'Cancelado' };
    const EC = ['#f97316','#0ea5e9','#8b5cf6','#22c55e','#94a3b8'];
    const estados = d.proyectos_estados || [];
    mkChart('chartPE', {
        type: 'doughnut',
        data: {
            labels: estados.map(e => EM[e.estado]||e.estado),
            datasets: [{ data: estados.map(e => parseInt(e.cnt)), backgroundColor: EC.slice(0, estados.length), borderWidth: 2, borderColor: '#fff' }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '65%',
            plugins: { legend: { position: 'right', labels: { usePointStyle: true, padding: 10 } } }
        }
    });

    // Mini tabla stock crítico
    const tb = document.querySelector('#kStockTable tbody');
    if (tb) tb.innerHTML = (d.stock_critico_detalle||[]).map(p => `<tr>
        <td style="font-size:.83rem">${escHtml(p.nombre)}</td>
        <td class="text-right" style="font-weight:600;color:#dc2626">${p.stock_actual}</td>
        <td class="text-right text-muted">${p.stock_minimo}</td>
    </tr>`).join('') || '<tr><td colspan="3" class="text-center text-muted" style="padding:12px">Sin stock crítico</td></tr>';

    // Mini alertas
    const aMini = document.getElementById('kAlertasMini');
    if (aMini) {
        const TI = {
            retraso_proyecto: { icon: 'fa-clock',         color: '#dc2626' },
            stock_minimo:     { icon: 'fa-boxes-stacked', color: '#d97706' },
            garantia_vence:   { icon: 'fa-shield-halved', color: '#7c3aed' }
        };
        aMini.innerHTML = (d.alertas_recientes||[]).map(a => {
            const t = TI[a.tipo] || { icon: 'fa-bell', color: '#64748b' };
            return `<div style="display:flex;gap:8px;align-items:flex-start;padding:7px 0;border-bottom:1px solid var(--border)">
                <i class="fa ${t.icon}" style="color:${t.color};margin-top:1px;font-size:12px;flex-shrink:0"></i>
                <span style="font-size:.82rem;color:#334155">${escHtml(a.mensaje)}</span>
            </div>`;
        }).join('') || '<div class="text-muted text-center" style="padding:16px;font-size:.83rem">Sin alertas pendientes</div>';
    }
}

// ── PRODUCCIÓN ────────────────────────────────────────────────────────────
async function cargarProduccion() {
    const desde = document.getElementById('prodDesde').value;
    const hasta = document.getElementById('prodHasta').value;
    const d = await apiGet(`${BASE_REP}?action=produccion&desde=${desde}&hasta=${hasta}`);
    if (!d.ok) return;

    // Mini KPIs
    const totalPeriodo  = d.proyectos.length;
    const totalActivos  = (d.por_estado||[]).filter(e => !['completado','cancelado'].includes(e.estado)).reduce((s,e) => s+parseInt(e.cnt),0);
    const totalComp     = parseInt(((d.por_estado||[]).find(e => e.estado==='completado')||{cnt:0}).cnt);
    document.getElementById('pKpiActivos').textContent    = totalActivos;
    document.getElementById('pKpiRetrasados').textContent = d.retrasados.length;
    document.getElementById('pKpiCompletados').textContent= totalComp;
    document.getElementById('pKpiTotal').textContent      = totalPeriodo;

    // Gráfico: proyectos por mes (bar)
    const meses = d.por_mes || [];
    if (meses.length) {
        mkChart('chartProdMes', {
            type: 'bar',
            data: {
                labels: meses.map(m => mesLabel(m.mes)),
                datasets: [
                    { label: 'Nuevos', data: meses.map(m => parseInt(m.total)||0),
                      backgroundColor: 'rgba(29,78,216,0.75)', borderRadius: 4 },
                    { label: 'Completados', data: meses.map(m => parseInt(m.completados)||0),
                      backgroundColor: 'rgba(22,163,74,0.75)', borderRadius: 4 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'top', labels: { usePointStyle: true, padding: 10 } } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    }

    // Tabla retrasados
    document.querySelector('#tablaRetrasados tbody').innerHTML = d.retrasados.map(p => `<tr>
        <td><code>${escHtml(p.codigo)}</code></td>
        <td>${escHtml(p.nombre)}</td>
        <td>${formatDate(p.fecha_entrega_estimada)}</td>
        <td><strong style="color:#dc2626">${p.dias_retraso} días</strong></td>
    </tr>`).join('') || '<tr><td colspan="4" class="text-muted text-center" style="padding:16px">Sin proyectos retrasados</td></tr>';

    // Tabla costos
    document.querySelector('#tablaProdCostos tbody').innerHTML = d.proyectos.map(p => {
        const desv = parseFloat(p.desviacion);
        return `<tr>
            <td><code>${escHtml(p.codigo)}</code></td>
            <td>${escHtml(p.nombre)}</td>
            <td>${formatMoney(p.costo_estimado)}</td>
            <td>${formatMoney(p.costo_real)}</td>
            <td style="color:${desv>0?'#dc2626':desv<0?'#16a34a':'inherit'};font-weight:600">
                ${desv>=0?'+':''}${formatMoney(desv)}
            </td>
            <td>
                <div style="display:flex;align-items:center;gap:6px">
                    <div style="flex:1;height:6px;background:#e2e8f0;border-radius:3px;min-width:60px">
                        <div style="background:var(--primary);height:6px;border-radius:3px;width:${Math.min(100,p.porcentaje_avance)}%"></div>
                    </div>
                    <span style="font-size:.8rem">${p.porcentaje_avance}%</span>
                </div>
            </td>
            <td><span class="badge badge-secondary">${escHtml(p.estado)}</span></td>
        </tr>`;
    }).join('') || '<tr><td colspan="7" class="text-muted text-center" style="padding:16px">Sin datos en el período</td></tr>';
}

// ── VENTAS ────────────────────────────────────────────────────────────────
async function cargarVentas() {
    const desde = document.getElementById('ventasDesde').value;
    const hasta = document.getElementById('ventasHasta').value;
    const d = await apiGet(`${BASE_REP}?action=ventas&desde=${desde}&hasta=${hasta}`);
    if (!d.ok) return;

    // Mini KPIs desde OV
    const ovMeses = d.ov_por_mes || [];
    const totalIngresos = ovMeses.reduce((s, m) => s + parseFloat(m.ingresos), 0);
    const totalOrdenes  = ovMeses.reduce((s, m) => s + parseInt(m.ordenes), 0);
    document.getElementById('vKpiIngresos').textContent = formatMoney(totalIngresos);
    document.getElementById('vKpiOrdenes').textContent  = totalOrdenes;
    document.getElementById('vKpiTicket').textContent   = totalOrdenes > 0 ? formatMoney(totalIngresos / totalOrdenes) : '—';

    // Gráfico línea: Ingresos OV por mes
    mkChart('chartVentasMes', {
        type: 'line',
        data: {
            labels: ovMeses.map(m => mesLabel(m.mes)),
            datasets: [{
                label: 'Ingresos OV',
                data: ovMeses.map(m => parseFloat(m.ingresos)),
                borderColor: '#ea580c',
                backgroundColor: 'rgba(234,88,12,0.1)',
                borderWidth: 2.5,
                pointRadius: 4,
                pointBackgroundColor: '#ea580c',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => ` Ingresos: ${formatMoney(ctx.raw)}` } }
            },
            scales: { y: { beginAtZero: true, ticks: { callback: v => v>=1000 ? 'S/'+Math.round(v/1000)+'k' : formatMoney(v) } } }
        }
    });

    // Gráfico donut: pipeline cotizaciones
    const PM = { borrador:'Borrador', enviada:'Enviada', aprobada:'Aprobada', rechazada:'Rechazada', vencida:'Vencida' };
    const PC = ['#94a3b8','#0ea5e9','#22c55e','#ef4444','#f97316'];
    const pipeline = d.pipeline || [];
    mkChart('chartVentasPipeline', {
        type: 'doughnut',
        data: {
            labels: pipeline.map(e => PM[e.estado]||e.estado),
            datasets: [{
                data: pipeline.map(e => parseInt(e.cnt)),
                backgroundColor: PC.slice(0, pipeline.length),
                borderWidth: 2, borderColor: '#fff'
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '60%',
            plugins: {
                legend: { position: 'right', labels: { usePointStyle: true, padding: 10 } },
                tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.raw} (${formatMoney(pipeline[ctx.dataIndex]?.monto||0)})` } }
            }
        }
    });

    // Gráfico horizontal bar: top clientes OV
    const topOV = (d.top_clientes_ov || []).slice(0, 10).reverse();
    if (topOV.length) {
        mkChart('chartTopClientes', {
            type: 'bar',
            data: {
                labels: topOV.map(c => escHtml(c.razon_social.length > 22 ? c.razon_social.slice(0,22)+'…' : c.razon_social)),
                datasets: [{
                    label: 'Ingresos',
                    data: topOV.map(c => parseFloat(c.ingresos)),
                    backgroundColor: 'rgba(29,78,216,0.75)',
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => ` ${formatMoney(ctx.raw)}` } }
                },
                scales: { x: { beginAtZero: true, ticks: { callback: v => v>=1000?'S/'+Math.round(v/1000)+'k':formatMoney(v) } } }
            }
        });
    }
}

// ── COMPRAS ───────────────────────────────────────────────────────────────
async function cargarCompras() {
    const desde = document.getElementById('comprasDesde').value;
    const hasta = document.getElementById('comprasHasta').value;
    const d = await apiGet(`${BASE_REP}?action=compras&desde=${desde}&hasta=${hasta}`);
    if (!d.ok) return;

    // Gráfico bar: compras por mes
    const meses = d.por_mes || [];
    mkChart('chartComprasMes', {
        type: 'bar',
        data: {
            labels: meses.map(m => mesLabel(m.mes)),
            datasets: [{
                label: 'Monto comprado',
                data: meses.map(m => parseFloat(m.monto)),
                backgroundColor: 'rgba(124,58,237,0.75)',
                borderRadius: 5, borderSkipped: false
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => ` ${formatMoney(ctx.raw)} (${meses[ctx.dataIndex]?.ordenes} OCs)` } }
            },
            scales: { y: { beginAtZero: true, ticks: { callback: v => v>=1000?'S/'+Math.round(v/1000)+'k':formatMoney(v) } } }
        }
    });

    // Gráfico horizontal bar: top proveedores
    const top = (d.top_proveedores||[]).slice(0,8).reverse();
    if (top.length) {
        mkChart('chartTopProv', {
            type: 'bar',
            data: {
                labels: top.map(p => p.razon_social.length > 20 ? p.razon_social.slice(0,20)+'…' : p.razon_social),
                datasets: [{
                    label: 'Monto',
                    data: top.map(p => parseFloat(p.monto_total)),
                    backgroundColor: 'rgba(8,145,178,0.75)',
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => ` ${formatMoney(ctx.raw)}` } }
                },
                scales: { x: { beginAtZero: true, ticks: { callback: v => v>=1000?'S/'+Math.round(v/1000)+'k':formatMoney(v) } } }
            }
        });
    }

    // Tabla detalle
    document.querySelector('#tablaTopProv tbody').innerHTML = d.top_proveedores.map(p => `<tr>
        <td>${escHtml(p.razon_social)}</td>
        <td class="text-right">${p.ordenes}</td>
        <td class="text-right"><strong>${formatMoney(p.monto_total)}</strong></td>
        <td>${p.calificacion ? '★ ' + p.calificacion : '—'}</td>
    </tr>`).join('') || '<tr><td colspan="4" class="text-muted text-center" style="padding:16px">Sin datos</td></tr>';
}

// ── INVENTARIO ────────────────────────────────────────────────────────────
async function cargarInventario() {
    const d = await apiGet(BASE_REP + '?action=inventario');
    if (!d.ok) return;

    // Gráfico doughnut: valor por categoría
    const cats = (d.por_categoria||[]).filter(c => parseFloat(c.valor_total) > 0);
    const catColors = ['#1d4ed8','#ea580c','#16a34a','#7c3aed','#0891b2','#d97706','#dc2626','#64748b','#ec4899','#059669'];
    mkChart('chartInvCategoria', {
        type: 'doughnut',
        data: {
            labels: cats.map(c => c.categoria || 'Sin categoría'),
            datasets: [{
                data: cats.map(c => parseFloat(c.valor_total)),
                backgroundColor: catColors.slice(0, cats.length),
                borderWidth: 2, borderColor: '#fff'
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '60%',
            plugins: {
                legend: { position: 'right', labels: { usePointStyle: true, padding: 8, font: { size: 11 } } },
                tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${formatMoney(ctx.raw)} (${cats[ctx.dataIndex]?.productos} prod.)` } }
            }
        }
    });

    // Tabla stock crítico
    document.querySelector('#tablaStockCritico tbody').innerHTML = d.criticos.map(p => `<tr>
        <td><code style="font-size:.78rem">${escHtml(p.codigo)}</code></td>
        <td style="font-size:.83rem">${escHtml(p.nombre)}</td>
        <td style="font-size:.82rem;color:#64748b">${escHtml(p.categoria||'—')}</td>
        <td class="text-right"><strong style="color:#dc2626">${p.stock_actual}</strong></td>
        <td class="text-right">${p.stock_minimo}</td>
        <td style="color:#64748b">${escHtml(p.unidad)}</td>
        <td class="text-right">${formatMoney(p.valor)}</td>
    </tr>`).join('') || '<tr><td colspan="7" class="text-muted text-center" style="padding:16px">Sin stock crítico</td></tr>';

    // Tabla sin movimiento
    document.querySelector('#tablaSinMovimiento tbody').innerHTML = d.sin_movimiento.map(p => `<tr>
        <td><code style="font-size:.78rem">${escHtml(p.codigo)}</code></td>
        <td style="font-size:.83rem">${escHtml(p.nombre)}</td>
        <td class="text-right">${p.stock_actual} ${escHtml(p.unidad)}</td>
        <td style="color:#64748b;font-size:.82rem">${p.ultimo_movimiento ? formatDate(p.ultimo_movimiento) : 'Nunca'}</td>
    </tr>`).join('') || '<tr><td colspan="4" class="text-muted text-center" style="padding:16px">Sin productos inactivos</td></tr>';
}

// ── ALERTAS ───────────────────────────────────────────────────────────────
async function cargarAlertas() {
    const leida = document.getElementById('alertaFiltro').value;
    const d = await apiGet(`${BASE_REP}?action=alertas_listar&leida=${leida}`);
    if (!d.ok) return;
    const TM = { retraso_proyecto:'Retraso Proyecto', stock_minimo:'Stock Crítico', garantia_vence:'Garantía por Vencer' };
    const TC = { retraso_proyecto:'badge-danger', stock_minimo:'badge-warning', garantia_vence:'badge-primary' };
    document.querySelector('#tablaAlertas tbody').innerHTML = d.alertas.map(a => `<tr style="${a.leida?'opacity:.5':''}">
        <td><span class="badge ${TC[a.tipo]||'badge-secondary'}">${TM[a.tipo]||a.tipo}</span></td>
        <td>${escHtml(a.mensaje)}</td>
        <td style="font-size:.8rem">${escHtml(a.referencia_tipo)} #${a.referencia_id}</td>
        <td style="white-space:nowrap">${formatDate(a.created_at)}</td>
        <td><span class="badge ${a.leida?'badge-secondary':'badge-warning'}">${a.leida?'Leída':'Pendiente'}</span></td>
        <td>${!a.leida?`<button class="btn btn-secondary" style="padding:3px 8px;font-size:.78rem" onclick="marcarLeida(${a.id})"><i class="fa fa-check"></i></button>`:''}
        </td>
    </tr>`).join('') || '<tr><td colspan="6" class="text-muted text-center" style="padding:20px">Sin alertas</td></tr>';
}
async function marcarLeida(id) {
    const r = await apiPost(BASE_REP, { action:'alerta_marcar_leida', id });
    if (r.ok) cargarAlertas();
}
async function marcarTodasLeidas() {
    const r = await apiPost(BASE_REP, { action:'alerta_marcar_leida', id:0 });
    if (r.ok) { Toast.success('Todas las alertas marcadas como leídas'); cargarAlertas(); }
}
async function generarAlertas() {
    const r = await apiPost(BASE_REP, { action:'alertas_generar' });
    if (r.ok) { Toast.success(`${r.generadas} alertas generadas`); cargarAlertas(); cargarKPIs(); }
    else Toast.error(r.error || 'Error');
}

// ── RENTABILIDAD ──────────────────────────────────────────────────────────
async function cargarRentabilidad() {
    const desde  = document.getElementById('rentDesde').value;
    const hasta  = document.getElementById('rentHasta').value;
    const estado = document.getElementById('rentEstado').value;
    const tb     = document.getElementById('tbRentabilidad');
    tb.innerHTML = '<tr><td colspan="7" class="text-center text-muted"><i class="fa fa-spinner fa-spin"></i> Cargando…</td></tr>';

    try {
        const r  = await apiGet(`<?= APP_BASE ?>modules/reportes/api.php?action=rentabilidad&desde=${desde}&hasta=${hasta}&estado=${estado}`);
        const ps = r.proyectos || [];
        const t  = r.totales;

        // KPI Cards
        const kpiEl = document.getElementById('rentKpis');
        const mc = t.margen_total >= 0 ? '#166534' : '#b91c1c';
        kpiEl.innerHTML = [
            ['fa-arrow-trend-up','Ingresos Totales',   t.ingreso_total,            '#1d4ed8', false],
            ['fa-wrench',        'Costos Totales',      t.costo_total,              '#92400e', false],
            ['fa-sack-dollar',   'Margen Total',        t.margen_total,             mc,        false],
            ['fa-percent',       'Margen Promedio',     t.margen_pct_total!==null?t.margen_pct_total+'%':'—', mc, true],
            ['fa-circle-check',  'Proyectos rentables', t.rentables,               '#166534', true],
            ['fa-circle-xmark',  'En pérdida',          t.en_perdida,              '#b91c1c', true],
        ].map(([ico,lbl,val,color,raw]) => `
            <div class="card" style="padding:14px 16px">
                <div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);margin-bottom:4px">
                    <i class="fa ${ico}" style="color:${color};margin-right:4px"></i>${lbl}
                </div>
                <div style="font-size:1.1rem;font-weight:700;color:${color}">${raw?val:formatMoney(val)}</div>
            </div>`).join('');

        // Gráfico horizontal bar: top proyectos por margen%
        const conVenta = ps.filter(p => p.margen_pct !== null).sort((a,b) => parseFloat(b.margen_pct)-parseFloat(a.margen_pct)).slice(0,10).reverse();
        if (conVenta.length) {
            mkChart('chartRentMargenes', {
                type: 'bar',
                data: {
                    labels: conVenta.map(p => p.codigo.length>18 ? p.codigo.slice(0,18)+'…' : p.codigo),
                    datasets: [{
                        label: 'Margen %',
                        data: conVenta.map(p => parseFloat(p.margen_pct)),
                        backgroundColor: conVenta.map(p => parseFloat(p.margen_pct)>=0 ? 'rgba(22,163,74,0.75)' : 'rgba(220,38,38,0.75)'),
                        borderRadius: 4
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true, maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: ctx => ` Margen: ${ctx.raw}%` } }
                    },
                    scales: { x: { ticks: { callback: v => v+'%' } } }
                }
            });
        }

        if (!ps.length) {
            tb.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Sin proyectos en el período</td></tr>';
            return;
        }

        const badgeProy = est => {
            const m = { fabricacion:['#dbeafe','#1e40af','Fabricación'], pruebas:['#fef9c3','#854d0e','Pruebas'],
                        entrega:['#dcfce7','#166534','Entrega'], completado:['#e0e7ff','#3730a3','Completado'],
                        cancelado:['#f1f5f9','#64748b','Cancelado'] };
            const [bg,c,txt] = m[est]||['#f1f5f9','#64748b',est];
            return `<span style="background:${bg};color:${c};padding:2px 8px;border-radius:8px;font-size:.72rem;font-weight:600">${txt}</span>`;
        };

        tb.innerHTML = ps.map(p => {
            const ing   = parseFloat(p.ingreso);
            const cost  = parseFloat(p.costo_real);
            const marg  = parseFloat(p.margen);
            const pct   = p.margen_pct;
            const noVta = ing === 0;
            const mc2   = noVta ? '#94a3b8' : (marg >= 0 ? '#166534' : '#b91c1c');
            const pctHtml = pct !== null
                ? `<div style="display:flex;align-items:center;gap:6px">
                    <div style="flex:1;height:6px;background:#e2e8f0;border-radius:3px">
                        <div style="height:6px;border-radius:3px;background:${mc2};width:${Math.min(Math.abs(pct),100)}%"></div>
                    </div>
                    <span style="font-size:.8rem;font-weight:700;color:${mc2};white-space:nowrap">${pct}%</span>
                   </div>`
                : `<span style="font-size:.78rem;color:#94a3b8">Sin venta</span>`;
            return `<tr>
                <td style="font-size:.83rem">
                    <div style="font-weight:600">${escHtml(p.codigo)}</div>
                    <div style="color:var(--text-muted);font-size:.78rem">${escHtml(p.nombre)}</div>
                </td>
                <td style="font-size:.82rem">${escHtml(p.cliente)}</td>
                <td>${badgeProy(p.estado)}</td>
                <td class="text-right" style="font-size:.83rem${noVta?';color:#94a3b8':''}">${noVta?'—':formatMoney(ing)}</td>
                <td class="text-right" style="font-size:.83rem">${formatMoney(cost)}</td>
                <td class="text-right" style="font-size:.83rem;font-weight:600;color:${mc2}">${noVta?'—':formatMoney(marg)}</td>
                <td style="min-width:120px">${pctHtml}</td>
            </tr>`;
        }).join('');

    } catch(e) {
        tb.innerHTML = `<tr><td colspan="7" class="text-center text-danger">${escHtml(e.message)}</td></tr>`;
    }
}

// ── COMPROBANTES ──────────────────────────────────────────────────────────
async function cargarComprobantes() {
    const desde  = document.getElementById('compDesde').value;
    const hasta  = document.getElementById('compHasta').value;
    const tipo   = document.getElementById('compTipo').value;
    const estado = document.getElementById('compEstado').value;
    const q      = document.getElementById('compBuscar').value.trim();
    const tb     = document.getElementById('tbComprobantes');

    tb.innerHTML = '<tr><td colspan="10" class="text-center text-muted"><i class="fa fa-spinner fa-spin"></i> Cargando…</td></tr>';

    try {
        const url = `<?= APP_BASE ?>modules/reportes/api.php?action=comprobantes`
            + `&desde=${desde}&hasta=${hasta}&tipo=${encodeURIComponent(tipo)}`
            + `&estado=${estado}&q=${encodeURIComponent(q)}`;
        const r  = await apiGet(url);
        const cs = r.comprobantes || [];
        const rs = r.resumen      || [];

        const tipoColors = {
            '01':['#dbeafe','#1e40af','Facturas'],
            '03':['#dcfce7','#166534','Boletas'],
            'NV':['#f1f5f9','#475569','Notas Venta'],
            '07':['#fce7f3','#9d174d','Notas Crédito'],
            '08':['#ffedd5','#c2410c','Notas Débito'],
        };
        document.getElementById('compResumen').innerHTML = rs.map(r => {
            const [bg,color,label] = tipoColors[r.tipo_doc]||['#f1f5f9','#475569',r.tipo_doc_nombre||r.tipo_doc||'—'];
            return `<div style="border-radius:8px;padding:12px 14px;background:${bg};border:1px solid ${bg}">
                <div style="font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:${color};margin-bottom:4px">${label}</div>
                <div style="font-size:1rem;font-weight:700;color:${color}">${formatMoney(r.total)}</div>
                <div style="font-size:.72rem;color:${color};opacity:.8;margin-top:1px">${r.cantidad} comprobante${r.cantidad==1?'':'s'}</div>
            </div>`;
        }).join('');

        if (!cs.length) {
            tb.innerHTML = '<tr><td colspan="10" class="text-center text-muted">Sin comprobantes en el período</td></tr>';
            document.getElementById('tfComprobantes').style.display = 'none';
            return;
        }

        const badgeComp = est => {
            const m = { emitido:['#dbeafe','#1e40af','Emitido'], aceptado:['#dcfce7','#166534','Aceptado'],
                        rechazado:['#fee2e2','#b91c1c','Rechazado'], anulado:['#f1f5f9','#64748b','Anulado'] };
            const [bg,c,txt] = m[est]||['#f1f5f9','#64748b',est||'—'];
            return `<span style="background:${bg};color:${c};padding:2px 8px;border-radius:8px;font-size:.72rem;font-weight:600">${txt}</span>`;
        };
        const tipoLabel = cod => ({'01':'Factura','03':'Boleta','NV':'Nota Venta','07':'N. Crédito','08':'N. Débito'}[cod]||cod||'—');

        let sumSub=0, sumIgv=0, sumTot=0;
        tb.innerHTML = cs.map(c => {
            sumSub += parseFloat(c.subtotal||0);
            sumIgv += parseFloat(c.igv||0);
            sumTot += parseFloat(c.total||0);
            const td = c.tipo_doc||'';
            const [bg,col] = tipoColors[td] ? [tipoColors[td][0],tipoColors[td][1]] : ['#f1f5f9','#475569'];
            return `<tr>
                <td style="font-weight:600;white-space:nowrap">
                    ${escHtml(c.numero_completo)}
                    ${c.hash_cdr?'<i class="fa fa-circle-check" style="color:#16a34a;margin-left:4px;font-size:.75rem" title="CDR recibido"></i>':''}
                </td>
                <td><span style="background:${bg};color:${col};padding:2px 7px;border-radius:6px;font-size:.72rem;font-weight:600">${tipoLabel(td)}</span></td>
                <td style="font-size:.78rem;color:#475569;white-space:nowrap">${c.orden_venta_codigo ? escHtml(c.orden_venta_codigo) : '<span style="color:#cbd5e1">—</span>'}</td>
                <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${escHtml(c.cliente)}">${escHtml(c.cliente)}</td>
                <td style="color:#64748b">${escHtml(c.cliente_ruc||'—')}</td>
                <td style="white-space:nowrap">${formatDate(c.fecha_emision)}</td>
                <td class="text-right">${formatMoney(c.subtotal)}</td>
                <td class="text-right">${formatMoney(c.igv)}</td>
                <td class="text-right" style="font-weight:600">${formatMoney(c.total)}</td>
                <td>${badgeComp(c.estado)}</td>
            </tr>`;
        }).join('');

        const tf = document.getElementById('tfComprobantes');
        tf.style.display = '';
        document.getElementById('compTotalSubtotal').textContent = formatMoney(sumSub);
        document.getElementById('compTotalIgv').textContent      = formatMoney(sumIgv);
        document.getElementById('compTotalTotal').textContent     = formatMoney(sumTot);

    } catch(e) {
        tb.innerHTML = `<tr><td colspan="9" class="text-center text-danger">${escHtml(e.message)}</td></tr>`;
    }
}

// ── PRODUCTIVIDAD POR ÁREA ────────────────────────────────────────────────
async function cargarProductividad() {
    const desde = document.getElementById('prodAreaDesde').value;
    const hasta = document.getElementById('prodAreaHasta').value;

    document.getElementById('tbProdAreas').innerHTML =
        '<tr><td colspan="4" class="text-center text-muted"><i class="fa fa-spinner fa-spin"></i></td></tr>';
    document.getElementById('tbProdOperarios').innerHTML =
        '<tr><td colspan="4" class="text-center text-muted"><i class="fa fa-spinner fa-spin"></i></td></tr>';

    try {
        const r  = await apiGet(`<?= APP_BASE ?>modules/reportes/api.php?action=productividad&desde=${desde}&hasta=${hasta}`);
        const as = r.areas     || [];
        const op = r.operarios || [];
        const pd = r.por_dia   || [];

        // Gráfico bar: partes por área
        if (as.length) {
            mkChart('chartProdAreas', {
                type: 'bar',
                data: {
                    labels: as.map(a => a.area),
                    datasets: [
                        { label: 'Completadas', data: as.map(a => parseInt(a.completadas)||0),
                          backgroundColor: 'rgba(22,163,74,0.78)', borderRadius: 4 },
                        { label: 'Backlog', data: as.map(a => parseInt(a.backlog)||0),
                          backgroundColor: 'rgba(217,119,6,0.72)', borderRadius: 4 }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { position: 'top', labels: { usePointStyle: true, padding: 10 } } },
                    scales: { y: { beginAtZero: true, stacked: false, ticks: { stepSize: 1 } } }
                }
            });
        }

        // Gráfico línea: tendencia 30 días
        if (pd.length) {
            mkChart('chartProdTendencia', {
                type: 'line',
                data: {
                    labels: pd.map(d => d.dia ? d.dia.slice(5) : ''),
                    datasets: [{
                        label: 'Partes completadas',
                        data: pd.map(d => parseInt(d.completadas)),
                        borderColor: '#0891b2',
                        backgroundColor: 'rgba(8,145,178,0.12)',
                        borderWidth: 2, pointRadius: 3,
                        pointBackgroundColor: '#0891b2',
                        fill: true, tension: 0.3
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                }
            });
        } else {
            document.getElementById('chartProdTendencia').parentElement.innerHTML =
                '<div class="text-muted text-center" style="padding:24px;font-size:.83rem">Sin partes completadas en los últimos 30 días.</div>';
        }

        // Tablas
        const maxComp = Math.max(...as.map(a => parseInt(a.completadas)||0), 1);
        document.getElementById('tbProdAreas').innerHTML = as.length
            ? as.map(a => {
                const comp = parseInt(a.completadas)||0;
                const blog = parseInt(a.backlog)||0;
                const extIco = a.es_externa ? ' <i class="fa fa-globe" style="color:#f59e0b;font-size:.72rem" title="Externa"></i>' : '';
                return `<tr>
                    <td>${escHtml(a.area)}${extIco}</td>
                    <td class="text-right" style="font-weight:600;color:${comp>0?'#166534':'var(--text-muted)'}">${comp}</td>
                    <td class="text-right" style="color:${blog>0?'#d97706':'var(--text-muted)'}">${blog}</td>
                    <td class="text-right" style="color:var(--text-muted)">${a.tiempo_promedio_h!==null?a.tiempo_promedio_h+'h':'—'}</td>
                </tr>`;
            }).join('')
            : '<tr><td colspan="4" class="text-center text-muted">Sin áreas</td></tr>';

        document.getElementById('tbProdOperarios').innerHTML = op.length
            ? op.map(o => `<tr>
                <td>${escHtml(o.usuario)}</td>
                <td style="color:var(--text-muted)">${escHtml(o.area)}</td>
                <td class="text-right" style="font-weight:600">${o.completadas}</td>
                <td class="text-right" style="color:var(--text-muted)">${o.tiempo_prom_h!==null?o.tiempo_prom_h+'h':'—'}</td>
            </tr>`).join('')
            : '<tr><td colspan="4" class="text-center text-muted">Sin datos en el período</td></tr>';

    } catch(e) {
        document.getElementById('tbProdAreas').innerHTML =
            `<tr><td colspan="4" class="text-danger">${escHtml(e.message)}</td></tr>`;
    }
}
</script>

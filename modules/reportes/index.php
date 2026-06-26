<?php
$page_title      = 'Reportes y KPIs';
$page_breadcrumb = 'Reportes';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="tabs" id="mainTabs">
    <div class="tab active" data-group="rep" data-target="tab-kpis"><i class="fa fa-gauge"></i> KPIs</div>
    <div class="tab" data-group="rep" data-target="tab-produccion"><i class="fa fa-industry"></i> Producción</div>
    <div class="tab" data-group="rep" data-target="tab-ventas"><i class="fa fa-file-invoice-dollar"></i> Ventas</div>
    <div class="tab" data-group="rep" data-target="tab-compras"><i class="fa fa-cart-shopping"></i> Compras</div>
    <div class="tab" data-group="rep" data-target="tab-inventario"><i class="fa fa-boxes-stacked"></i> Inventario</div>
    <div class="tab" data-group="rep" data-target="tab-alertas"><i class="fa fa-bell"></i> Alertas</div>
</div>

<!-- ============================================================
     KPIs GENERALES
     ============================================================ -->
<div class="tab-content active" data-group="rep" id="tab-kpis">
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon blue"><i class="fa fa-industry"></i></div>
            <div><div class="kpi-value" id="k-proy-activos">—</div><div class="kpi-label">Proyectos Activos</div></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon red"><i class="fa fa-triangle-exclamation"></i></div>
            <div><div class="kpi-value" id="k-proy-retr">—</div><div class="kpi-label">Proyectos Retrasados</div></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon green"><i class="fa fa-circle-check"></i></div>
            <div><div class="kpi-value" id="k-proy-comp">—</div><div class="kpi-label">Completados este mes</div></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon green"><i class="fa fa-money-bill-wave"></i></div>
            <div><div class="kpi-value" id="k-ingresos">—</div><div class="kpi-label">Ingresos este mes</div></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon orange"><i class="fa fa-cart-shopping"></i></div>
            <div><div class="kpi-value" id="k-compras">—</div><div class="kpi-label">Compras este mes</div></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon cyan"><i class="fa fa-boxes-stacked"></i></div>
            <div><div class="kpi-value" id="k-inv">—</div><div class="kpi-label">Valor Inventario</div></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon orange"><i class="fa fa-circle-exclamation"></i></div>
            <div><div class="kpi-value" id="k-stock-crit">—</div><div class="kpi-label">Stock Crítico</div></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon red"><i class="fa fa-bell"></i></div>
            <div><div class="kpi-value" id="k-alertas">—</div><div class="kpi-label">Alertas Pendientes</div></div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:4px">
        <!-- Variación ingresos -->
        <div class="card">
            <div class="card-header">Comparativa Mes Actual vs Anterior</div>
            <div class="card-body" id="comparativa" style="padding:16px"></div>
        </div>
        <!-- Rentabilidad proyectos -->
        <div class="card">
            <div class="card-header">Acceso Rápido</div>
            <div class="card-body" style="padding:16px;display:flex;flex-direction:column;gap:10px">
                <button class="btn btn-secondary" style="text-align:left" onclick="document.querySelector('[data-target=tab-produccion]').click()">
                    <i class="fa fa-industry" style="width:20px"></i> Reporte de Producción
                </button>
                <button class="btn btn-secondary" style="text-align:left" onclick="document.querySelector('[data-target=tab-ventas]').click()">
                    <i class="fa fa-file-invoice-dollar" style="width:20px"></i> Reporte de Ventas
                </button>
                <button class="btn btn-secondary" style="text-align:left" onclick="document.querySelector('[data-target=tab-compras]').click()">
                    <i class="fa fa-cart-shopping" style="width:20px"></i> Reporte de Compras
                </button>
                <button class="btn btn-secondary" style="text-align:left" onclick="document.querySelector('[data-target=tab-inventario]').click()">
                    <i class="fa fa-boxes-stacked" style="width:20px"></i> Reporte de Inventario
                </button>
                <button class="btn btn-secondary" style="text-align:left" onclick="document.querySelector('[data-target=tab-alertas]').click()">
                    <i class="fa fa-bell" style="width:20px"></i> Ver Alertas
                </button>
            </div>
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

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
        <div class="card">
            <div class="card-header">Proyectos por Estado</div>
            <div class="card-body" id="prodPorEstado" style="padding:16px"></div>
        </div>
        <div class="card">
            <div class="card-header">Proyectos Retrasados</div>
            <div class="card-body" style="padding:0">
                <table class="table" id="tablaRetrasados">
                    <thead><tr><th>Código</th><th>Nombre</th><th>F. Estimada</th><th>Días retraso</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Costo Planificado vs Real</div>
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

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
        <div class="card">
            <div class="card-header">Pipeline de Cotizaciones</div>
            <div class="card-body" id="ventasPipeline" style="padding:16px"></div>
        </div>
        <div class="card">
            <div class="card-header">Top 10 Clientes por Ingresos</div>
            <div class="card-body" style="padding:0">
                <table class="table" id="tablaTopClientes">
                    <thead><tr><th>Cliente</th><th>Cotizaciones</th><th>Ingresos</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Ingresos Mensuales</div>
        <table class="table" id="tablaVentasMes">
            <thead><tr><th>Mes</th><th>Cotizaciones</th><th>Aprobadas</th><th>Tasa Aprobación</th><th>Ingresos</th></tr></thead>
            <tbody></tbody>
        </table>
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

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
        <div class="card">
            <div class="card-header">Compras Mensuales</div>
            <div class="card-body" id="comprasPorMes" style="padding:16px"></div>
        </div>
        <div class="card">
            <div class="card-header">Top Proveedores por Monto</div>
            <div class="card-body" style="padding:0">
                <table class="table" id="tablaTopProv">
                    <thead><tr><th>Proveedor</th><th>OCs</th><th>Monto</th><th>Calificación</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
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

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
        <div class="card">
            <div class="card-header">Valor por Categoría</div>
            <div class="card-body" id="invPorCategoria" style="padding:16px"></div>
        </div>
        <div class="card">
            <div class="card-header">Productos sin movimiento (+30 días)</div>
            <div class="card-body" style="padding:0">
                <table class="table" id="tablaSinMovimiento" style="font-size:0.83rem">
                    <thead><tr><th>Código</th><th>Nombre</th><th>Stock</th><th>Último movimiento</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Stock Crítico</div>
        <table class="table" id="tablaStockCritico">
            <thead><tr><th>Código</th><th>Nombre</th><th>Categoría</th><th>Stock</th><th>Mínimo</th><th>Unidad</th><th>Valor en Stock</th></tr></thead>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Fechas por defecto
    const hoy    = new Date().toISOString().split('T')[0];
    const priMes = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];
    const priAno = new Date().getFullYear() + '-01-01';
    document.getElementById('prodDesde').value   = priMes;
    document.getElementById('prodHasta').value   = hoy;
    document.getElementById('ventasDesde').value = priAno;
    document.getElementById('ventasHasta').value = hoy;
    document.getElementById('comprasDesde').value= priAno;
    document.getElementById('comprasHasta').value= hoy;

    cargarKPIs();
    document.getElementById('alertaFiltro').addEventListener('change', cargarAlertas);

    // Cargar datos cuando se activa cada tab
    document.querySelectorAll('.tab[data-group="rep"]').forEach(tab => tab.addEventListener('click', () => {
        const target = tab.dataset.target.replace('tab-', '');
        if (target === 'alertas')   cargarAlertas();
        if (target === 'inventario')cargarInventario();
        if (target === 'produccion')cargarProduccion();
        if (target === 'ventas')    cargarVentas();
        if (target === 'compras')   cargarCompras();
    }));
});

// ============================================================
// KPIs
// ============================================================
async function cargarKPIs() {
    const d = await apiGet('modules/reportes/api.php?action=kpis');
    if (!d.ok) return;

    document.getElementById('k-proy-activos').textContent = d.proy_activos;
    document.getElementById('k-proy-retr').textContent    = d.proy_retrasados;
    document.getElementById('k-proy-comp').textContent    = d.proy_completados_mes;
    document.getElementById('k-ingresos').textContent     = formatMoney(d.ingresos_mes);
    document.getElementById('k-compras').textContent      = formatMoney(d.compras_mes);
    document.getElementById('k-inv').textContent          = formatMoney(d.valor_inv);
    document.getElementById('k-stock-crit').textContent   = d.stock_critico;
    document.getElementById('k-alertas').textContent      = d.alertas_no_leidas;

    // Comparativa
    const varIngresos = d.ingresos_ant > 0 ? ((d.ingresos_mes - d.ingresos_ant) / d.ingresos_ant * 100).toFixed(1) : null;
    const varCompras  = d.compras_ant  > 0 ? ((d.compras_mes  - d.compras_ant)  / d.compras_ant  * 100).toFixed(1) : null;

    document.getElementById('comparativa').innerHTML = `
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <div style="text-align:center;padding:16px;background:#f8fafc;border-radius:8px">
                <div style="font-size:0.82rem;color:#64748b;margin-bottom:4px">Ingresos mes anterior</div>
                <div style="font-size:1.1rem;font-weight:700">${formatMoney(d.ingresos_ant)}</div>
                ${varIngresos !== null ? `<div style="font-size:0.82rem;color:${parseFloat(varIngresos)>=0?'#22c55e':'var(--danger)'}">
                    ${parseFloat(varIngresos)>=0?'▲':'▼'} ${Math.abs(varIngresos)}% vs mes anterior
                </div>` : ''}
            </div>
            <div style="text-align:center;padding:16px;background:#f8fafc;border-radius:8px">
                <div style="font-size:0.82rem;color:#64748b;margin-bottom:4px">Compras mes anterior</div>
                <div style="font-size:1.1rem;font-weight:700">${formatMoney(d.compras_ant)}</div>
                ${varCompras !== null ? `<div style="font-size:0.82rem;color:${parseFloat(varCompras)<=0?'#22c55e':'var(--danger)'}">
                    ${parseFloat(varCompras)>=0?'▲':'▼'} ${Math.abs(varCompras)}% vs mes anterior
                </div>` : ''}
            </div>
        </div>`;
}

// ============================================================
// PRODUCCIÓN
// ============================================================
async function cargarProduccion() {
    const desde = document.getElementById('prodDesde').value;
    const hasta = document.getElementById('prodHasta').value;
    const d = await apiGet(`modules/reportes/api.php?action=produccion&desde=${desde}&hasta=${hasta}`);
    if (!d.ok) return;

    const estadosMap = { fabricacion:'Fabricación', pruebas:'Pruebas', entrega:'Entrega', completado:'Completado', cancelado:'Cancelado' };
    const colores = { fabricacion:'#f97316', pruebas:'#0ea5e9', entrega:'#8b5cf6', completado:'#22c55e', cancelado:'#94a3b8' };
    const total = d.por_estado.reduce((s, e) => s + parseInt(e.cnt), 0);
    document.getElementById('prodPorEstado').innerHTML = d.por_estado.map(e => {
        const pct = total > 0 ? Math.round(parseInt(e.cnt) / total * 100) : 0;
        return `<div style="margin-bottom:10px">
            <div style="display:flex;justify-content:space-between;font-size:0.82rem;margin-bottom:3px">
                <span>${estadosMap[e.estado]||e.estado}</span>
                <span>${e.cnt} (${pct}%)</span>
            </div>
            <div style="background:#e2e8f0;border-radius:4px;height:8px">
                <div style="background:${colores[e.estado]||'#64748b'};width:${pct}%;height:8px;border-radius:4px"></div>
            </div>
        </div>`;
    }).join('') || '<p class="text-secondary" style="font-size:0.85rem">Sin datos</p>';

    document.querySelector('#tablaRetrasados tbody').innerHTML = d.retrasados.map(p => `<tr>
        <td><code>${p.codigo}</code></td>
        <td>${p.nombre}</td>
        <td>${formatDate(p.fecha_entrega_estimada)}</td>
        <td><strong style="color:var(--danger)">${p.dias_retraso} días</strong></td>
    </tr>`).join('') || '<tr><td colspan="4" class="text-secondary text-center" style="padding:16px">Sin proyectos retrasados</td></tr>';

    document.querySelector('#tablaProdCostos tbody').innerHTML = d.proyectos.map(p => {
        const desv = parseFloat(p.desviacion);
        return `<tr>
            <td><code>${p.codigo}</code></td>
            <td>${p.nombre}</td>
            <td>${formatMoney(p.costo_estimado)}</td>
            <td>${formatMoney(p.costo_real)}</td>
            <td style="color:${desv>0?'var(--danger)':desv<0?'#22c55e':'inherit'}">
                ${desv >= 0 ? '+' : ''}${formatMoney(desv)}
            </td>
            <td>
                <div style="background:#e2e8f0;border-radius:4px;height:8px;width:80px;display:inline-block">
                    <div style="background:var(--primary);width:${Math.min(100,p.porcentaje_avance)}%;height:8px;border-radius:4px"></div>
                </div> ${p.porcentaje_avance}%
            </td>
            <td><span class="badge badge-secondary">${p.estado}</span></td>
        </tr>`;
    }).join('') || '<tr><td colspan="7" class="text-secondary text-center" style="padding:16px">Sin datos en el período</td></tr>';
}

// ============================================================
// VENTAS
// ============================================================
async function cargarVentas() {
    const desde = document.getElementById('ventasDesde').value;
    const hasta = document.getElementById('ventasHasta').value;
    const d = await apiGet(`modules/reportes/api.php?action=ventas&desde=${desde}&hasta=${hasta}`);
    if (!d.ok) return;

    const pipelineMap = { borrador:'Borrador', enviada:'Enviada', aprobada:'Aprobada', rechazada:'Rechazada', vencida:'Vencida' };
    const pipelineCol = { borrador:'#94a3b8', enviada:'#0ea5e9', aprobada:'#22c55e', rechazada:'#ef4444', vencida:'#f97316' };
    const totalCot = d.pipeline.reduce((s, e) => s + parseInt(e.cnt), 0);
    document.getElementById('ventasPipeline').innerHTML = d.pipeline.map(e => {
        const pct = totalCot > 0 ? Math.round(parseInt(e.cnt) / totalCot * 100) : 0;
        return `<div style="margin-bottom:10px">
            <div style="display:flex;justify-content:space-between;font-size:0.82rem;margin-bottom:3px">
                <span>${pipelineMap[e.estado]||e.estado}</span>
                <span>${e.cnt} · ${formatMoney(e.monto)}</span>
            </div>
            <div style="background:#e2e8f0;border-radius:4px;height:8px">
                <div style="background:${pipelineCol[e.estado]||'#64748b'};width:${pct}%;height:8px;border-radius:4px"></div>
            </div>
        </div>`;
    }).join('') || '<p class="text-secondary" style="font-size:0.85rem">Sin datos</p>';

    document.querySelector('#tablaTopClientes tbody').innerHTML = d.top_clientes.map(c => `<tr>
        <td>${c.razon_social}</td>
        <td>${c.cotizaciones}</td>
        <td><strong>${formatMoney(c.ingresos)}</strong></td>
    </tr>`).join('') || '<tr><td colspan="3" class="text-secondary text-center" style="padding:16px">Sin datos</td></tr>';

    document.querySelector('#tablaVentasMes tbody').innerHTML = d.por_mes.map(m => {
        const tasa = m.cotizaciones > 0 ? Math.round(m.aprobadas / m.cotizaciones * 100) : 0;
        return `<tr>
            <td>${m.mes}</td>
            <td>${m.cotizaciones}</td>
            <td>${m.aprobadas}</td>
            <td>${tasa}%</td>
            <td><strong>${formatMoney(m.ingresos)}</strong></td>
        </tr>`;
    }).join('') || '<tr><td colspan="5" class="text-secondary text-center" style="padding:16px">Sin datos en el período</td></tr>';
}

// ============================================================
// COMPRAS
// ============================================================
async function cargarCompras() {
    const desde = document.getElementById('comprasDesde').value;
    const hasta = document.getElementById('comprasHasta').value;
    const d = await apiGet(`modules/reportes/api.php?action=compras&desde=${desde}&hasta=${hasta}`);
    if (!d.ok) return;

    const totalCompras = d.por_mes.reduce((s, m) => s + parseFloat(m.monto), 0);
    document.getElementById('comprasPorMes').innerHTML = d.por_mes.map(m => {
        const pct = totalCompras > 0 ? Math.round(parseFloat(m.monto) / totalCompras * 100) : 0;
        return `<div style="margin-bottom:10px">
            <div style="display:flex;justify-content:space-between;font-size:0.82rem;margin-bottom:3px">
                <span>${m.mes}</span>
                <span>${m.ordenes} OCs · ${formatMoney(m.monto)}</span>
            </div>
            <div style="background:#e2e8f0;border-radius:4px;height:8px">
                <div style="background:var(--primary);width:${pct}%;height:8px;border-radius:4px"></div>
            </div>
        </div>`;
    }).join('') || '<p class="text-secondary" style="font-size:0.85rem">Sin datos en el período</p>';

    document.querySelector('#tablaTopProv tbody').innerHTML = d.top_proveedores.map(p => `<tr>
        <td>${p.razon_social}</td>
        <td>${p.ordenes}</td>
        <td><strong>${formatMoney(p.monto_total)}</strong></td>
        <td>${p.calificacion ? '★ ' + p.calificacion : '—'}</td>
    </tr>`).join('') || '<tr><td colspan="4" class="text-secondary text-center" style="padding:16px">Sin datos</td></tr>';
}

// ============================================================
// INVENTARIO
// ============================================================
async function cargarInventario() {
    const d = await apiGet('modules/reportes/api.php?action=inventario');
    if (!d.ok) return;

    const totalValor = d.por_categoria.reduce((s, c) => s + parseFloat(c.valor_total), 0);
    document.getElementById('invPorCategoria').innerHTML = d.por_categoria.map(c => {
        const pct = totalValor > 0 ? Math.round(parseFloat(c.valor_total) / totalValor * 100) : 0;
        return `<div style="margin-bottom:10px">
            <div style="display:flex;justify-content:space-between;font-size:0.82rem;margin-bottom:3px">
                <span>${c.categoria || 'Sin categoría'}</span>
                <span>${c.productos} prods · ${formatMoney(c.valor_total)}</span>
            </div>
            <div style="background:#e2e8f0;border-radius:4px;height:8px">
                <div style="background:var(--primary);width:${pct}%;height:8px;border-radius:4px"></div>
            </div>
        </div>`;
    }).join('') || '<p class="text-secondary" style="font-size:0.85rem">Sin datos</p>';

    document.querySelector('#tablaSinMovimiento tbody').innerHTML = d.sin_movimiento.map(p => `<tr>
        <td><code>${p.codigo}</code></td>
        <td>${p.nombre}</td>
        <td>${p.stock_actual} ${p.unidad}</td>
        <td>${p.ultimo_movimiento ? formatDate(p.ultimo_movimiento) : 'Nunca'}</td>
    </tr>`).join('') || '<tr><td colspan="4" class="text-secondary text-center" style="padding:16px">Sin productos inactivos</td></tr>';

    document.querySelector('#tablaStockCritico tbody').innerHTML = d.criticos.map(p => `<tr>
        <td><code>${p.codigo}</code></td>
        <td>${p.nombre}</td>
        <td>${p.categoria || '—'}</td>
        <td><strong style="color:var(--danger)">${p.stock_actual}</strong></td>
        <td>${p.stock_minimo}</td>
        <td>${p.unidad}</td>
        <td>${formatMoney(p.valor)}</td>
    </tr>`).join('') || '<tr><td colspan="7" class="text-secondary text-center" style="padding:16px">Sin stock crítico</td></tr>';
}

// ============================================================
// ALERTAS
// ============================================================
async function cargarAlertas() {
    const leida = document.getElementById('alertaFiltro').value;
    const d = await apiGet(`modules/reportes/api.php?action=alertas_listar&leida=${leida}`);
    if (!d.ok) return;
    const tiposMap = {
        retraso_proyecto: 'Retraso Proyecto',
        stock_minimo:     'Stock Crítico',
        garantia_vence:   'Garantía por Vencer',
    };
    const tipoColor = {
        retraso_proyecto: 'badge-danger',
        stock_minimo:     'badge-warning',
        garantia_vence:   'badge-primary',
    };
    const tbody = document.querySelector('#tablaAlertas tbody');
    tbody.innerHTML = d.alertas.map(a => `<tr style="${a.leida?'opacity:0.5':''}">
        <td><span class="badge ${tipoColor[a.tipo]||'badge-secondary'}">${tiposMap[a.tipo]||a.tipo}</span></td>
        <td>${a.mensaje}</td>
        <td style="font-size:0.8rem">${a.referencia_tipo} #${a.referencia_id}</td>
        <td style="white-space:nowrap">${formatDate(a.created_at)}</td>
        <td><span class="badge ${a.leida?'badge-secondary':'badge-warning'}">${a.leida?'Leída':'Pendiente'}</span></td>
        <td>
            ${!a.leida ? `<button class="btn btn-secondary" style="padding:3px 8px;font-size:0.78rem" onclick="marcarLeida(${a.id})">
                <i class="fa fa-check"></i>
            </button>` : ''}
        </td>
    </tr>`).join('') || '<tr><td colspan="6" class="text-secondary text-center" style="padding:20px">Sin alertas</td></tr>';
}

async function marcarLeida(id) {
    const r = await apiPost('modules/reportes/api.php', { action: 'alerta_marcar_leida', id });
    if (r.ok) cargarAlertas();
}

async function marcarTodasLeidas() {
    const r = await apiPost('modules/reportes/api.php', { action: 'alerta_marcar_leida', id: 0 });
    if (r.ok) { Toast.success('Todas las alertas marcadas como leídas'); cargarAlertas(); }
}

async function generarAlertas() {
    const r = await apiPost('modules/reportes/api.php', { action: 'alertas_generar' });
    if (r.ok) { Toast.success(`${r.generadas} alertas generadas`); cargarAlertas(); cargarKPIs(); }
    else Toast.error(r.error || 'Error');
}
</script>

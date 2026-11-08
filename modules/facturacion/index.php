<?php
$page_title      = 'Facturacion';
$page_breadcrumb = '<span>Analytics</span> <span>></span> Facturacion';
require_once __DIR__ . '/../../includes/header.php';
?>

<!-- TABS -->
<div class="tabs">
    <div class="tab active" data-group="fac" data-target="tabReporte"><i class="fa fa-chart-line"></i> Reporte de Ventas</div>
    <div class="tab" data-group="fac" data-target="tabNotas"><i class="fa fa-file-lines"></i> Notas de credito</div>
    <div class="tab" data-group="fac" data-target="tabRent"><i class="fa fa-coins"></i> Rentabilidad</div>
    <div class="tab" data-group="fac" data-target="tabReenvios"><i class="fa fa-rotate"></i> Reenvios SUNAT <span class="nav-badge" id="facReenviosBadge" style="display:none">0</span></div>
    <div class="tab" data-group="fac" data-target="tabDoc"><i class="fa fa-circle-question"></i> Documentacion SUNAT</div>
</div>

<!-- ============================ REPORTE DE VENTAS ============================ -->
<div class="tab-content active" data-group="fac" id="tabReporte">
    <div class="card" style="margin-bottom:14px">
        <div style="padding:14px 16px;display:flex;gap:14px;align-items:end;flex-wrap:wrap">
            <div class="form-group" style="margin:0"><label class="form-label">Desde</label>
                <input type="date" class="form-control" id="repDesde"></div>
            <div class="form-group" style="margin:0"><label class="form-label">Hasta</label>
                <input type="date" class="form-control" id="repHasta"></div>
            <button class="btn btn-primary" onclick="cargarReporte()"><i class="fa fa-search"></i> Buscar</button>
            <span style="color:var(--text-secondary);font-size:.8rem">Rapido:</span>
            <a href="#" onclick="rangoRapido('repDesde','repHasta','hoy',cargarReporte);return false">Hoy</a>
            <a href="#" onclick="rangoRapido('repDesde','repHasta','semana',cargarReporte);return false">Esta semana</a>
            <a href="#" onclick="rangoRapido('repDesde','repHasta','mes',cargarReporte);return false">Este mes</a>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <span class="card-title">Detalle de ventas <span class="text-muted" id="repCount"></span></span>
            <div class="card-actions">
                <input type="text" class="form-control" id="repBuscar" placeholder="Cliente o comprobante..." style="max-width:220px;display:inline-block" oninput="filtrarTabla('tablaReporte',this.value)">
                <button class="btn btn-outline btn-sm" onclick="exportarCSV('tablaReporte','reporte_ventas')"><i class="fa fa-file-csv"></i> Exportar</button>
            </div>
        </div>
        <div class="table-responsive">
            <table id="tablaReporte">
                <thead><tr>
                    <th>Fecha</th><th>Cliente</th><th>Comprobante</th><th class="text-right">Total</th>
                    <th>XML</th><th>CDR</th><th>SUNAT</th><th>Estado</th><th>Acciones</th>
                </tr></thead>
                <tbody><tr><td colspan="9" class="text-center text-muted" style="padding:20px">Elige un rango y pulsa Buscar</td></tr></tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================ NOTAS DE CREDITO ============================ -->
<div class="tab-content" data-group="fac" id="tabNotas">
    <div class="card" style="margin-bottom:14px">
        <div style="padding:14px 16px;display:flex;gap:14px;align-items:end;flex-wrap:wrap">
            <div class="form-group" style="margin:0"><label class="form-label">Desde</label>
                <input type="date" class="form-control" id="ncDesde"></div>
            <div class="form-group" style="margin:0"><label class="form-label">Hasta</label>
                <input type="date" class="form-control" id="ncHasta"></div>
            <button class="btn btn-primary" onclick="cargarNotas()"><i class="fa fa-search"></i> Buscar</button>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <span class="card-title">Notas de credito / debito <span class="text-muted" id="ncCount"></span></span>
            <div class="card-actions">
                <button class="btn btn-outline btn-sm" onclick="exportarCSV('tablaNotas','notas_credito')"><i class="fa fa-file-csv"></i> Exportar</button>
            </div>
        </div>
        <div class="table-responsive">
            <table id="tablaNotas">
                <thead><tr>
                    <th>Fecha</th><th>Cliente</th><th>Comprobante</th><th>Afecta a</th>
                    <th class="text-right">Total</th><th>SUNAT</th><th>Estado</th>
                </tr></thead>
                <tbody><tr><td colspan="7" class="text-center text-muted" style="padding:20px">Elige un rango y pulsa Buscar</td></tr></tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================ RENTABILIDAD ============================ -->
<div class="tab-content" data-group="fac" id="tabRent">
    <div class="card" style="margin-bottom:14px">
        <div style="padding:14px 16px;display:flex;gap:14px;align-items:end;flex-wrap:wrap">
            <div class="form-group" style="margin:0"><label class="form-label">Desde</label>
                <input type="date" class="form-control" id="rtDesde"></div>
            <div class="form-group" style="margin:0"><label class="form-label">Hasta</label>
                <input type="date" class="form-control" id="rtHasta"></div>
            <button class="btn btn-primary" onclick="cargarRentabilidad()"><i class="fa fa-search"></i> Buscar</button>
        </div>
    </div>
    <div class="kpi-grid" style="margin-bottom:14px">
        <div class="kpi-card green"><div class="kpi-icon green"><i class="fa fa-arrow-trend-up"></i></div>
            <div><div class="kpi-value" id="rtIngreso">S/ 0.00</div><div class="kpi-label">Ingreso neto</div></div></div>
        <div class="kpi-card red"><div class="kpi-icon red"><i class="fa fa-arrow-trend-down"></i></div>
            <div><div class="kpi-value" id="rtCosto">S/ 0.00</div><div class="kpi-label">Costo</div></div></div>
        <div class="kpi-card blue"><div class="kpi-icon blue"><i class="fa fa-coins"></i></div>
            <div><div class="kpi-value" id="rtMargen">S/ 0.00</div><div class="kpi-label">Margen</div></div></div>
        <div class="kpi-card orange"><div class="kpi-icon orange"><i class="fa fa-percent"></i></div>
            <div><div class="kpi-value" id="rtMargenPct">0 %</div><div class="kpi-label">% Ganancia</div></div></div>
    </div>
    <div class="card">
        <div class="card-header"><span class="card-title">Rentabilidad por dia</span></div>
        <div class="table-responsive">
            <table id="tablaRent">
                <thead><tr><th>Fecha</th><th class="text-right">Ingreso</th><th class="text-right">Costo</th>
                    <th class="text-right">Margen</th><th class="text-right">%</th></tr></thead>
                <tbody><tr><td colspan="5" class="text-center text-muted" style="padding:20px">Elige un rango y pulsa Buscar</td></tr></tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================ REENVIOS SUNAT ============================ -->
<div class="tab-content" data-group="fac" id="tabReenvios">
    <p class="text-muted" style="font-size:.82rem;margin:0 0 12px">Comprobantes con reintentos automaticos — el worker corre cada <span id="reRetryMin">5</span> minutos.</p>
    <div class="kpi-grid" style="margin-bottom:14px">
        <div class="kpi-card orange"><div class="kpi-icon orange"><i class="fa fa-clock"></i></div>
            <div><div class="kpi-value" id="reProgramados">0</div><div class="kpi-label">Programados</div>
            <div class="kpi-sub">Esperando proximo intento del worker</div></div></div>
        <div class="kpi-card gray"><div class="kpi-icon gray"><i class="fa fa-ban"></i></div>
            <div><div class="kpi-value" id="reAgotados">0</div><div class="kpi-label">Agotados</div>
            <div class="kpi-sub">Reintentos sin exito — reenvio manual</div></div></div>
        <div class="kpi-card red"><div class="kpi-icon red"><i class="fa fa-triangle-exclamation"></i></div>
            <div><div class="kpi-value" id="reRechazados">0</div><div class="kpi-label">Rechazados</div>
            <div class="kpi-sub">SUNAT devolvio error permanente</div></div></div>
        <div class="kpi-card blue"><div class="kpi-icon blue"><i class="fa fa-hourglass-half"></i></div>
            <div><div class="kpi-value" id="reProximo" style="font-size:1rem">—</div><div class="kpi-label">Proximo intento</div>
            <div class="kpi-sub">El worker corre cada <span class="reRetryMin">5</span> min</div></div></div>
    </div>
    <div class="card" style="margin-bottom:14px">
        <div style="padding:14px 16px;display:flex;gap:12px;align-items:end;flex-wrap:wrap">
            <div class="form-group" style="margin:0"><label class="form-label">Desde</label>
                <input type="date" class="form-control" id="reDesde"></div>
            <div class="form-group" style="margin:0"><label class="form-label">Hasta</label>
                <input type="date" class="form-control" id="reHasta"></div>
            <div class="form-group" style="margin:0"><label class="form-label">Tipo</label>
                <select class="form-control" id="reTipo"><option value="">Todos</option>
                    <option value="01">Factura</option><option value="03">Boleta</option><option value="07">Nota credito</option></select></div>
            <div class="form-group" style="margin:0"><label class="form-label">Estado</label>
                <select class="form-control" id="reEstado"><option value="">Todos</option>
                    <option value="nunca_enviado">Nunca enviado</option><option value="programado">Programado</option>
                    <option value="rechazado">Rechazado</option><option value="agotado">Agotado</option></select></div>
            <button class="btn btn-primary" onclick="cargarReenvios()"><i class="fa fa-search"></i> Buscar</button>
            <button class="btn btn-outline" onclick="procesarCola()"><i class="fa fa-bolt"></i> Procesar ahora</button>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><span class="card-title">Comprobantes pendientes <span class="text-muted" id="reCount"></span></span></div>
        <div class="table-responsive">
            <table id="tablaReenvios">
                <thead><tr>
                    <th>Comprobante</th><th>Fecha venta</th><th>Cliente</th><th class="text-right">Total</th>
                    <th class="text-center">Intentos</th><th>Proximo intento</th><th>Estado</th><th>Ultimo error</th><th></th>
                </tr></thead>
                <tbody><tr><td colspan="9" class="text-center text-muted" style="padding:20px">Cargando...</td></tr></tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================ DOCUMENTACION SUNAT ============================ -->
<div class="tab-content" data-group="fac" id="tabDoc">
    <div class="card" style="margin-bottom:14px">
        <div class="card-header"><span class="card-title"><i class="fa fa-layer-group"></i> Rangos de codigos de respuesta</span></div>
        <div class="table-responsive">
            <table><thead><tr><th>Codigo</th><th>Significado</th><th>Detalle</th><th>Que hace el sistema</th></tr></thead>
                <tbody id="docRangos"></tbody></table>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><span class="card-title"><i class="fa fa-list"></i> Codigos frecuentes</span></div>
        <div class="table-responsive">
            <table><thead><tr><th>Codigo</th><th>Tipo</th><th>Mensaje</th></tr></thead>
                <tbody id="docCodigos"></tbody></table>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
const BASE = 'api.php';

document.addEventListener('DOMContentLoaded', () => {
    rangoRapido('repDesde','repHasta','mes');
    cargarReporte();
    cargarReenvios();
    cargarDocumentacion();
});

// ── Utilidades de rango de fechas ──
function rangoRapido(idD, idH, tipo, cb) {
    const hoy = new Date();
    const fmt = d => d.toISOString().slice(0,10);
    let desde = new Date(hoy), hasta = new Date(hoy);
    if (tipo === 'hoy')    { /* mismo dia */ }
    else if (tipo === 'semana') { desde.setDate(hoy.getDate() - hoy.getDay()); }
    else if (tipo === 'mes')    { desde = new Date(hoy.getFullYear(), hoy.getMonth(), 1); }
    document.getElementById(idD).value = fmt(desde);
    document.getElementById(idH).value = fmt(hasta);
    if (cb) cb();
}

function filtrarTabla(tablaId, texto) {
    const t = texto.toLowerCase();
    document.querySelectorAll(`#${tablaId} tbody tr`).forEach(tr => {
        tr.style.display = tr.textContent.toLowerCase().includes(t) ? '' : 'none';
    });
}

function exportarCSV(tablaId, nombre) {
    const filas = [];
    document.querySelectorAll(`#${tablaId} thead tr, #${tablaId} tbody tr`).forEach(tr => {
        if (tr.style.display === 'none') return;
        const cols = Array.from(tr.querySelectorAll('th,td')).map(td =>
            '"' + td.innerText.replace(/\s+/g,' ').trim().replace(/"/g,'""') + '"');
        filas.push(cols.join(';'));
    });
    const blob = new Blob(['﻿' + filas.join('\n')], {type:'text/csv;charset=utf-8'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `${nombre}_${new Date().toISOString().slice(0,10)}.csv`;
    a.click();
}

function tipoDocLabel(c) {
    return {'01':'FACTURA','03':'BOLETA','02':'NOTA DE VENTA','07':'NOTA CREDITO','08':'NOTA DEBITO'}[c] || c;
}
function archivoLink(path, icon, titulo) {
    if (!path) return '<span class="text-muted">—</span>';
    return `<a href="/industria_mg/${path}" target="_blank" title="${titulo}"><i class="fa ${icon}"></i></a>`;
}
function xmlLinkDescarga(path) {
    if (!path) return '<span class="text-muted">—</span>';
    return `<a href="/industria_mg/${path}" target="_blank" download title="Descargar XML / Respuesta SUNAT" style="color:var(--primary);font-size:1.05rem"><i class="fa fa-file-code"></i></a>`;
}
function cdrLinkVer(path) {
    if (!path) return '<span class="text-muted">—</span>';
    return `<a href="/industria_mg/${path}" target="_blank" title="Ver CDR en nueva pestaña" style="color:var(--primary);font-size:1.05rem"><i class="fa fa-file-circle-check"></i></a>`;
}
function badgeSunat(c) {
    const cod = (c.codigo_respuesta || '').trim();
    const estado = (c.estado || '').toLowerCase();
    const aceptado = cod === '0' || (c.cdr_path && c.cdr_path.trim() !== '') || estado.includes('acept');
    const observado = estado.includes('observ');
    const msg = (c.mensaje_respuesta || '').trim();
    let label, cls;
    if (aceptado)      { label = 'Aceptado';  cls = 'badge-completado'; }
    else if (observado){ label = 'Observado'; cls = 'badge-normal'; }
    else               { label = cod ? `Rechazado (${cod})` : 'Pendiente'; cls = cod ? 'badge-cancelado' : 'badge-normal'; }
    const detalle = msg ? `<div class="text-xs text-muted" style="max-width:260px;white-space:normal" title="${msg.replace(/"/g,'&quot;')}">${msg.length>80?msg.slice(0,80)+'…':msg}</div>` : '';
    return `<span class="badge ${cls}">${label}</span>${detalle}`;
}
function badgeCola(estado) {
    const m = {nunca_enviado:['Nunca enviado','badge-normal'], programado:['Programado','badge-fabricacion'],
               rechazado:['Rechazado','badge-cancelado'], agotado:['Agotado','badge-retrasado']};
    const [l,c] = m[estado] || [estado,'badge-normal'];
    return `<span class="badge ${c}">${l}</span>`;
}

// ── Reporte de Ventas ──
async function cargarReporte() {
    const d = await apiGet(`${BASE}?action=fact_reporte&desde=${document.getElementById('repDesde').value}&hasta=${document.getElementById('repHasta').value}`);
    const rows = d.comprobantes || [];
    document.getElementById('repCount').textContent = `(${rows.length} resultados)`;
    const tb = document.querySelector('#tablaReporte tbody');
    tb.innerHTML = rows.length ? rows.map(c => {
        const puedeReenviar = !c.cdr_path && !c.anulado;
        return `<tr>
        <td style="white-space:nowrap">${formatDate(c.fecha_emision)}<div class="text-xs text-muted">${(c.hora_emision||'').slice(0,5)}</div></td>
        <td>${esc(c.cliente_nombre)}<div class="text-xs text-muted">${esc(c.cliente_doc)}</div></td>
        <td><strong>${esc(c.numero)}</strong><div class="text-xs text-muted">${tipoDocLabel(c.codigo_tipo_documento)}</div></td>
        <td class="text-right">${formatMoney(c.total)}</td>
        <td>${xmlLinkDescarga(c.xml_path)}</td>
        <td>${cdrLinkVer(c.cdr_path)}</td>
        <td>${badgeSunat(c)}</td>
        <td>${c.anulado ? '<span class="badge badge-cancelado">Anulado</span>' : '<span class="badge badge-completado">Vigente</span>'}</td>
        <td style="white-space:nowrap">
            <button class="btn btn-outline btn-xs" title="Imprimir" onclick="imprimirComprobanteFact(${c.id})"><i class="fa fa-print"></i></button>
            ${puedeReenviar
                ? `<button class="btn btn-outline btn-xs" title="Reenviar a SUNAT" onclick="reenviarComprobante(${c.id},this)"><i class="fa fa-paper-plane"></i></button>`
                : ''}
        </td>
    </tr>`;
    }).join('') : '<tr><td colspan="9" class="text-center text-muted" style="padding:20px">Sin comprobantes en el rango</td></tr>';
}

async function imprimirComprobanteFact(id) {
    try {
        const d = await apiGet(`${BASE}?action=fact_comprobante_detalle&id=${id}`);
        if (!d.ok) { Toast.error('No se pudo cargar el comprobante.'); return; }
        const c = d.comprobante || {};
        const items = d.items || [];
        const emp = d.empresa || {};
        const logo = emp.logo
            ? `<img src="${emp.logo}" style="max-height:72px;max-width:220px;object-fit:contain">`
            : `<div style="font-size:18px;font-weight:900;color:#f97316">${esc(emp.nombre || 'IndustriaMG')}</div>`;
        const itemsHtml = items.map((it, i) => `
            <tr>
                <td style="padding:6px 8px;border-bottom:1px solid #eee">${i+1}</td>
                <td style="padding:6px 8px;border-bottom:1px solid #eee">${esc(it.descripcion)}</td>
                <td style="padding:6px 8px;border-bottom:1px solid #eee;text-align:center">${esc(it.unidad_codigo || 'NIU')}</td>
                <td style="padding:6px 8px;border-bottom:1px solid #eee;text-align:right">${parseFloat(it.cantidad||0).toFixed(2)}</td>
                <td style="padding:6px 8px;border-bottom:1px solid #eee;text-align:right">${formatMoney(it.precio_unitario||0)}</td>
                <td style="padding:6px 8px;border-bottom:1px solid #eee;text-align:right"><strong>${formatMoney(it.total||0)}</strong></td>
            </tr>`).join('');
        const tipoLabel = tipoDocLabel(c.codigo_tipo_documento) || 'Comprobante';
        const html = `<!DOCTYPE html><html><head><meta charset="utf-8"><title>${esc(c.numero)}</title>
<style>
*{box-sizing:border-box} body{font-family:Arial,sans-serif;font-size:12px;max-width:794px;margin:0 auto;padding:24px;color:#111}
.hdr{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:18px}
.doc-box{border:2.5px solid #f97316;border-radius:8px;padding:10px 18px;text-align:center;min-width:220px}
.doc-box .lbl{font-size:10px;font-weight:700;color:#f97316;text-transform:uppercase;letter-spacing:.6px}
.doc-box .num{font-size:18px;font-weight:900;color:#f97316;margin-top:4px}
.section{background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:10px 12px;margin-bottom:14px}
.tot-row{display:flex;justify-content:space-between;padding:4px 0}
.tot-row.total{border-top:2px solid #f97316;font-weight:900;font-size:14px;color:#f97316;padding-top:8px;margin-top:4px}
table.items{width:100%;border-collapse:collapse;margin-bottom:10px}
table.items th{background:#f1f5f9;padding:7px 8px;font-size:11px;text-align:left}
@media print{ @page{size:A4;margin:15mm} body{padding:0;margin:0} }
</style></head><body>
<div class="hdr">
    <div>
        ${logo}
        <div style="margin-top:6px;font-weight:700">${esc(emp.razon_social || '')}</div>
        ${emp.ruc?`<div style="font-size:11px;color:#64748b">RUC: ${esc(emp.ruc)}</div>`:''}
        ${emp.direccion?`<div style="font-size:11px;color:#64748b">${esc(emp.direccion)}</div>`:''}
    </div>
    <div class="doc-box">
        <div class="lbl">${esc(tipoLabel).toUpperCase()}</div>
        <div class="num">${esc(c.numero)}</div>
        <div style="font-size:10px;color:#64748b;margin-top:4px">${formatDate(c.fecha_emision)} ${(c.hora_emision||'').slice(0,5)}</div>
    </div>
</div>
<div class="section">
    <div style="font-size:10px;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:4px">Cliente</div>
    <div style="font-weight:700">${esc(c.cliente_nombre || 'Sin cliente')}</div>
    <div style="font-size:11px;color:#64748b">${esc(c.cliente_doc || '')}</div>
</div>
<table class="items">
    <thead><tr>
        <th style="width:32px">#</th><th>Descripción</th>
        <th style="width:50px;text-align:center">U.M.</th>
        <th style="width:70px;text-align:right">Cant.</th>
        <th style="width:90px;text-align:right">P. Unit.</th>
        <th style="width:110px;text-align:right">Total</th>
    </tr></thead>
    <tbody>${itemsHtml || '<tr><td colspan="6" style="padding:12px;text-align:center;color:#94a3b8">Sin ítems</td></tr>'}</tbody>
</table>
<div style="display:flex;justify-content:flex-end">
    <div style="min-width:280px">
        ${parseFloat(c.gravada || 0) > 0 ? `<div class="tot-row"><span style="color:#64748b">Op. Gravada</span><strong>${formatMoney(c.gravada)}</strong></div>` : ''}
        ${parseFloat(c.igv || 0) > 0 ? `<div class="tot-row"><span style="color:#64748b">IGV</span><strong>${formatMoney(c.igv)}</strong></div>` : ''}
        <div class="tot-row total"><span>TOTAL</span><span>${formatMoney(c.total || 0)}</span></div>
    </div>
</div>
<script>window.onload=function(){setTimeout(function(){window.print();},350);};<\/script>
</body></html>`;
        const w = window.open('', '_blank', 'width=900,height=700');
        w.document.write(html);
        w.document.close();
    } catch(e) {
        Toast.error('Error al preparar la impresión.');
    }
}

// ── Notas de credito ──
async function cargarNotas() {
    const d = await apiGet(`${BASE}?action=fact_notas_credito&desde=${document.getElementById('ncDesde').value}&hasta=${document.getElementById('ncHasta').value}`);
    const rows = d.comprobantes || [];
    document.getElementById('ncCount').textContent = `(${rows.length})`;
    const tb = document.querySelector('#tablaNotas tbody');
    tb.innerHTML = rows.length ? rows.map(c => `<tr>
        <td style="white-space:nowrap">${formatDate(c.fecha_emision)}</td>
        <td>${esc(c.cliente_nombre)}</td>
        <td><strong>${esc(c.numero)}</strong><div class="text-xs text-muted">${tipoDocLabel(c.codigo_tipo_documento)}</div></td>
        <td class="text-xs">${esc(c.doc_modificado||'—')}</td>
        <td class="text-right">${formatMoney(c.total)}</td>
        <td>${badgeSunat(c)}</td>
        <td>${c.anulado ? '<span class="badge badge-cancelado">Anulado</span>' : '<span class="badge badge-completado">Vigente</span>'}</td>
    </tr>`).join('') : '<tr><td colspan="7" class="text-center text-muted" style="padding:20px">Sin notas en el rango</td></tr>';
}

// ── Rentabilidad ──
async function cargarRentabilidad() {
    const d = await apiGet(`${BASE}?action=fact_rentabilidad&desde=${document.getElementById('rtDesde').value}&hasta=${document.getElementById('rtHasta').value}`);
    const r = d.rentabilidad || {};
    document.getElementById('rtIngreso').textContent   = formatMoney(r.ingreso || 0);
    document.getElementById('rtCosto').textContent     = formatMoney(r.costo || 0);
    document.getElementById('rtMargen').textContent    = formatMoney(r.margen || 0);
    document.getElementById('rtMargenPct').textContent = (r.margen_pct || 0) + ' %';
    const tb = document.querySelector('#tablaRent tbody');
    const dias = r.dias || [];
    tb.innerHTML = dias.length ? dias.map(x => `<tr>
        <td>${formatDate(x.fecha)}</td>
        <td class="text-right">${formatMoney(x.ingreso)}</td>
        <td class="text-right">${formatMoney(x.costo)}</td>
        <td class="text-right" style="color:${x.margen>=0?'var(--success)':'var(--danger)'}">${formatMoney(x.margen)}</td>
        <td class="text-right">${x.margen_pct} %</td>
    </tr>`).join('') : '<tr><td colspan="5" class="text-center text-muted" style="padding:20px">Sin datos en el rango</td></tr>';
}

// ── Reenvios SUNAT ──
async function cargarReenvios() {
    const p = new URLSearchParams({action:'fact_reenvios',
        desde:document.getElementById('reDesde').value, hasta:document.getElementById('reHasta').value,
        tipo:document.getElementById('reTipo').value, estado:document.getElementById('reEstado').value});
    const d = await apiGet(`${BASE}?${p.toString()}`);
    const st = d.estadisticas || {};
    document.getElementById('reProgramados').textContent = st.programados || 0;
    document.getElementById('reAgotados').textContent    = st.agotados || 0;
    document.getElementById('reRechazados').textContent  = st.rechazados || 0;
    document.getElementById('reProximo').textContent     = st.proximo ? formatDateTime(st.proximo) : '—';
    document.querySelectorAll('.reRetryMin').forEach(e => e.textContent = st.retry_min || 5);
    document.getElementById('reRetryMin').textContent = st.retry_min || 5;
    const badge = document.getElementById('facReenviosBadge');
    if (st.total > 0) { badge.textContent = st.total > 99 ? '99+' : st.total; badge.style.display = ''; }
    else badge.style.display = 'none';

    const rows = d.comprobantes || [];
    document.getElementById('reCount').textContent = `(${rows.length})`;
    const tb = document.querySelector('#tablaReenvios tbody');
    tb.innerHTML = rows.length ? rows.map(c => `<tr>
        <td><strong>${esc(c.numero)}</strong><div class="text-xs text-muted">${tipoDocLabel(c.codigo_tipo_documento)}</div></td>
        <td style="white-space:nowrap">${formatDate(c.fecha_emision)}<div class="text-xs text-muted">${(c.hora_emision||'').slice(0,5)}</div></td>
        <td>${esc(c.cliente_nombre)}<div class="text-xs text-muted">${esc(c.cliente_doc)}</div></td>
        <td class="text-right">${formatMoney(c.total)}</td>
        <td class="text-center">${c.intentos||0}/${c.max_intentos||20}</td>
        <td class="text-xs">${c.proximo_intento ? formatDateTime(c.proximo_intento) : '—'}</td>
        <td>${badgeCola(c.estado_cola)}</td>
        <td class="text-xs" style="max-width:220px;white-space:normal;color:var(--danger)">${esc(c.ultimo_error||'')}</td>
        <td class="text-center"><button class="btn btn-outline btn-xs" title="Reenviar ahora" onclick="reenviarComprobante(${c.id},this)"><i class="fa fa-paper-plane"></i></button></td>
    </tr>`).join('') : '<tr><td colspan="9" class="text-center text-muted" style="padding:20px">No hay comprobantes pendientes 🎉</td></tr>';
}

async function reenviarComprobante(id, btn) {
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>'; }
    try {
        const r = await apiPost(BASE, {action:'fact_reenviar', comprobante_id:id});
        if (r.ok) Toast.success(r.mensaje || 'Enviado a SUNAT.');
        else Toast.warning(r.mensaje || 'SUNAT no acepto el comprobante.');
    } catch(e) {
        Toast.error(e.message || 'Error al reenviar.');
    }
    cargarReenvios();
}

async function procesarCola() {
    try {
        const r = await apiPost(BASE, {action:'fact_procesar_cola'});
        const s = r.resumen || {};
        Toast.success(`Procesados: ${s.procesados||0} · Aceptados: ${s.aceptados||0} · Reprogramados: ${s.reprogramados||0}`);
    } catch(e) {
        Toast.error(e.message || 'Error al procesar la cola.');
    }
    cargarReenvios();
}

// ── Documentacion SUNAT ──
async function cargarDocumentacion() {
    const d = await apiGet(`${BASE}?action=fact_documentacion`);
    const doc = d.doc || {};
    const tipoBadge = {aceptado:'badge-completado', transitorio:'badge-fabricacion', contribuyente:'badge-normal',
                       rechazado:'badge-cancelado', observado:'badge-retrasado'};
    document.getElementById('docRangos').innerHTML = (doc.rangos||[]).map(r => `<tr>
        <td><strong>${r.rango}</strong></td>
        <td><span class="badge ${tipoBadge[r.tipo]||'badge-normal'}">${esc(r.titulo)}</span></td>
        <td class="text-xs" style="white-space:normal">${esc(r.detalle)}</td>
        <td class="text-xs" style="white-space:normal">${esc(r.accion)}</td>
    </tr>`).join('');
    document.getElementById('docCodigos').innerHTML = (doc.codigos||[]).map(c => `<tr>
        <td><strong>${c.codigo}</strong></td>
        <td><span class="badge ${tipoBadge[c.tipo]||'badge-normal'}">${esc(c.tipo)}</span></td>
        <td class="text-xs" style="white-space:normal">${esc(c.mensaje)}</td>
    </tr>`).join('');
}

function formatDateTime(s) {
    if (!s) return '—';
    const d = new Date(s.replace(' ','T'));
    if (isNaN(d)) return s;
    return d.toLocaleString('es-PE', {day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'});
}
function esc(s){ return String(s==null?'':s).replace(/[&<>"]/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m])); }
</script>
<?php
$extra_js = ob_get_clean();
require_once __DIR__ . '/../../includes/footer.php';

<?php
$page_title      = 'Caja';
$page_breadcrumb = 'Finanzas › Caja';
require_once __DIR__ . '/../../includes/header.php';
?>

<style>
.caja-status {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 20px 24px;
    margin-bottom: 20px;
    display: flex;
    gap: 28px;
    align-items: center;
    flex-wrap: wrap;
    box-shadow: var(--card-shadow);
}
.caja-stats { display:flex; gap:24px; flex:1; flex-wrap:wrap; }
.caja-stat  { display:flex; flex-direction:column; gap:3px; }
.caja-stat-lbl {
    font-size:.66rem; font-weight:600; text-transform:uppercase;
    letter-spacing:.08em; color:var(--text-muted);
}
.caja-stat-val { font-size:1.18rem; font-weight:800; color:var(--text-primary); }
.caja-stat-val.ing { color:#16a34a; }
.caja-stat-val.eg  { color:#dc2626; }
.caja-stat-val.cur { color:var(--primary); }
.caja-meta {
    font-size:.79rem; color:var(--text-muted);
    display:flex; flex-direction:column; gap:4px; min-width:180px;
}
.caja-actions { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px; align-items:center; }
.btn-ing    { background:#16a34a!important; border-color:#15803d!important; color:#fff!important; }
.btn-ing:hover { background:#15803d!important; }
.btn-eg     { background:#dc2626!important; border-color:#b91c1c!important; color:#fff!important; }
.btn-eg:hover  { background:#b91c1c!important; }
.btn-cerrar { background:#d97706!important; border-color:#b45309!important; color:#fff!important; }
.btn-cerrar:hover { background:#b45309!important; }
.mov-ing { color:#16a34a; font-weight:700; }
.mov-eg  { color:#dc2626; font-weight:700; }
.banco-card {
    background:#fff;border:2px solid var(--border);border-radius:var(--radius);
    box-shadow:var(--card-shadow);padding:16px 20px;min-width:220px;flex:1;max-width:300px;
    cursor:pointer;transition:border-color .15s,box-shadow .15s,background .15s;user-select:none;
}
.banco-card:hover { border-color:var(--primary); box-shadow:0 4px 16px rgba(29,78,216,.13); }
.banco-card.selected {
    border-color:var(--primary);background:#eff6ff;
    box-shadow:0 4px 20px rgba(29,78,216,.18);
}
.dif-box {
    border-radius:8px; padding:13px 16px; text-align:center;
    margin-top:12px; display:none;
}
.dif-val { font-size:1.35rem; font-weight:800; display:block; margin-top:3px; }
.dif-msg { font-size:.78rem; margin-top:4px; display:block; }
</style>

<!-- ============================================================
     STATUS CARD
     ============================================================ -->
<div class="caja-status">
    <div style="display:flex;flex-direction:column;gap:8px;min-width:130px">
        <span id="cajaBadge"></span>
        <span style="font-size:.79rem;color:var(--text-muted)" id="cajaFecha">—</span>
    </div>
    <div class="caja-stats">
        <div class="caja-stat">
            <span class="caja-stat-lbl">Saldo inicial</span>
            <span class="caja-stat-val" id="stInicial">—</span>
        </div>
        <div class="caja-stat">
            <span class="caja-stat-lbl">Ingresos</span>
            <span class="caja-stat-val ing" id="stIngresos">—</span>
        </div>
        <div class="caja-stat">
            <span class="caja-stat-lbl">Egresos</span>
            <span class="caja-stat-val eg" id="stEgresos">—</span>
        </div>
        <div class="caja-stat">
            <span class="caja-stat-lbl">Saldo actual</span>
            <span class="caja-stat-val cur" id="stSaldo">—</span>
        </div>
    </div>
    <div class="caja-meta">
        <span id="metaApertura">—</span>
    </div>
</div>

<!-- ============================================================
     BANCOS
     ============================================================ -->
<div style="margin-bottom:20px">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
        <span style="font-weight:700;font-size:.93rem;color:var(--text-primary)">
            <i class="fa fa-building-columns" style="color:var(--primary);margin-right:6px"></i>
            Cuentas Bancarias
        </span>
        <div style="display:flex;gap:8px">
            <button class="btn btn-outline" style="padding:5px 14px;font-size:.82rem" onclick="abrirModalTransferencia()">
                <i class="fa fa-right-left"></i> Transferir
            </button>
            <button class="btn btn-outline" style="padding:5px 14px;font-size:.82rem" onclick="abrirConfigBancos()">
                <i class="fa fa-gear"></i> Configuración de banco
            </button>
        </div>
    </div>
    <div id="bancosCards" style="display:flex;gap:14px;flex-wrap:wrap">
        <div style="color:var(--text-muted);font-size:.86rem;padding:10px 0">Cargando...</div>
    </div>
</div>

<!-- Botones: caja abierta -->
<div class="caja-actions" id="cajaActions" style="display:none">
    <button class="btn btn-primary btn-ing" onclick="abrirModalIngreso()">
        <i class="fa fa-arrow-up"></i> Registrar Ingreso
    </button>
    <button class="btn btn-primary btn-eg" onclick="abrirModalEgreso()">
        <i class="fa fa-arrow-down"></i> Registrar Egreso
    </button>
    <button class="btn btn-outline" onclick="abrirModalArqueo()">
        <i class="fa fa-scale-balanced"></i> Arqueo
    </button>
    <button class="btn btn-warning btn-cerrar" onclick="abrirModalCerrar()" style="margin-left:auto">
        <i class="fa fa-lock"></i> Cerrar Caja
    </button>
</div>

<!-- Botón: caja cerrada -->
<div id="cajaBtnAbrir" style="display:none;margin-bottom:20px">
    <button class="btn btn-primary" onclick="abrirModalApertura()">
        <i class="fa fa-lock-open"></i> Abrir Caja
    </button>
</div>

<!-- ============================================================
     TABS
     ============================================================ -->
<div class="tabs">
    <div class="tab active" data-group="cajaTab" data-target="tabMovimientos">
        <i class="fa fa-list-ul"></i> Movimientos del turno
    </div>
    <div class="tab" data-group="cajaTab" data-target="tabHistorial">
        <i class="fa fa-clock-rotate-left"></i> Historial de sesiones
    </div>
</div>

<!-- Tab: Movimientos -->
<div class="tab-content active" data-group="cajaTab" id="tabMovimientos">
    <div class="card">
        <div class="table-responsive">
            <table id="tablaMovimientos">
                <thead>
                    <tr>
                        <th style="width:75px">Hora</th>
                        <th style="width:110px">Tipo</th>
                        <th style="width:175px">Concepto</th>
                        <th>Descripción</th>
                        <th style="text-align:right;width:120px">Monto</th>
                        <th style="width:130px">Usuario</th>
                    </tr>
                </thead>
                <tbody id="tbodyMovimientos">
                    <tr><td colspan="6" class="text-center" style="padding:40px;color:var(--text-muted)">
                        Cargando...
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Tab: Historial -->
<div class="tab-content" data-group="cajaTab" id="tabHistorial">
    <div class="card">
        <div class="table-responsive">
            <table id="tablaHistorial">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Apertura</th>
                        <th>Cierre</th>
                        <th style="text-align:right">S. Inicial</th>
                        <th style="text-align:right">Ingresos</th>
                        <th style="text-align:right">Egresos</th>
                        <th style="text-align:right">S. Sistema</th>
                        <th style="text-align:right">S. Físico</th>
                        <th style="text-align:right">Diferencia</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody id="tbodyHistorial">
                    <tr><td colspan="10" class="text-center" style="padding:40px;color:var(--text-muted)">
                        Cargando...
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>


<!-- ============================================================
     MODAL: APERTURA
     ============================================================ -->
<div class="modal-overlay" id="modalApertura">
    <div class="modal" style="max-width:420px">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-lock-open"></i> Apertura de Caja</span>
            <button class="modal-close" onclick="Modal.close('modalApertura')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Fondo inicial (S/) *</label>
                <input type="number" id="apertSaldo" class="form-control" step="0.01" min="0" placeholder="0.00">
                <small style="color:var(--text-muted);font-size:.75rem">Dinero en caja al iniciar (para dar vuelto, etc.)</small>
            </div>
            <div class="form-group">
                <label class="form-label">Observaciones</label>
                <textarea id="apertObs" class="form-control" rows="2" placeholder="Opcional..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalApertura')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarApertura()">
                <i class="fa fa-lock-open"></i> Abrir Caja
            </button>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: INGRESO
     ============================================================ -->
<div class="modal-overlay" id="modalIngreso">
    <div class="modal" style="max-width:440px">
        <div class="modal-header">
            <span class="modal-title" style="color:#16a34a">
                <i class="fa fa-arrow-up"></i> Registrar Ingreso
            </span>
            <button class="modal-close" onclick="Modal.close('modalIngreso')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Concepto *</label>
                <select id="ingConcepto" class="form-control">
                    <option value="venta_efectivo">Venta en efectivo</option>
                    <option value="cobro_cliente">Cobro a cliente</option>
                    <option value="ingreso_extraordinario">Ingreso extraordinario</option>
                    <option value="otro">Otro</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Descripción</label>
                <input type="text" id="ingDesc" class="form-control" placeholder="Detalle del ingreso...">
            </div>
            <div class="form-group">
                <label class="form-label">Monto (S/) *</label>
                <input type="number" id="ingMonto" class="form-control" step="0.01" min="0.01" placeholder="0.00">
            </div>
            <div class="form-group">
                <label class="form-label">Cuenta bancaria</label>
                <select id="ingBanco" class="form-control"></select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalIngreso')">Cancelar</button>
            <button class="btn btn-primary btn-ing" onclick="guardarMovimiento('ingreso')">
                <i class="fa fa-check"></i> Registrar Ingreso
            </button>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: EGRESO
     ============================================================ -->
<div class="modal-overlay" id="modalEgreso">
    <div class="modal" style="max-width:440px">
        <div class="modal-header">
            <span class="modal-title" style="color:#dc2626">
                <i class="fa fa-arrow-down"></i> Registrar Egreso
            </span>
            <button class="modal-close" onclick="Modal.close('modalEgreso')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Concepto *</label>
                <input type="text" id="egConcepto" class="form-control" placeholder="Ej: Compra de insumos, Pago transporte...">
            </div>
            <div class="form-group">
                <label class="form-label">Descripción</label>
                <input type="text" id="egDesc" class="form-control" placeholder="Detalle del egreso...">
            </div>
            <div class="form-group">
                <label class="form-label">Monto (S/) *</label>
                <input type="number" id="egMonto" class="form-control" step="0.01" min="0.01" placeholder="0.00">
            </div>
            <div class="form-group">
                <label class="form-label">Cuenta bancaria</label>
                <select id="egBanco" class="form-control"></select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalEgreso')">Cancelar</button>
            <button class="btn btn-primary btn-eg" onclick="guardarMovimiento('egreso')">
                <i class="fa fa-check"></i> Registrar Egreso
            </button>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: ARQUEO
     ============================================================ -->
<div class="modal-overlay" id="modalArqueo">
    <div class="modal" style="max-width:460px">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-scale-balanced"></i> Arqueo de Caja</span>
            <button class="modal-close" onclick="Modal.close('modalArqueo')">&times;</button>
        </div>
        <div class="modal-body">
            <div style="background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:16px;margin-bottom:16px">
                <table style="width:100%;font-size:.87rem;border-collapse:collapse">
                    <tr>
                        <td style="color:var(--text-muted);padding:4px 0">Saldo inicial:</td>
                        <td style="text-align:right;font-weight:600" id="arqueoInicial">—</td>
                    </tr>
                    <tr>
                        <td style="color:#16a34a;padding:4px 0">+ Total ingresos:</td>
                        <td style="text-align:right;font-weight:600;color:#16a34a" id="arqueoIngresos">—</td>
                    </tr>
                    <tr>
                        <td style="color:#dc2626;padding:4px 0">− Total egresos:</td>
                        <td style="text-align:right;font-weight:600;color:#dc2626" id="arqueoEgresos">—</td>
                    </tr>
                    <tr style="border-top:2px solid var(--border)">
                        <td style="font-weight:700;padding:8px 0 4px">Saldo en sistema:</td>
                        <td style="text-align:right;font-weight:800;font-size:1.05rem;color:var(--primary)" id="arqueoSistema">—</td>
                    </tr>
                </table>
            </div>
            <div class="form-group">
                <label class="form-label">Monto físico contado (S/) *</label>
                <input type="number" id="arqueoFisico" class="form-control" step="0.01" min="0"
                       placeholder="0.00" oninput="calcArqueoDif()">
            </div>
            <div class="dif-box" id="arqueoDifBox">
                <span style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted)">Diferencia</span>
                <span class="dif-val" id="arqueoDifVal">—</span>
                <span class="dif-msg" id="arqueoDifMsg">—</span>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalArqueo')">Cerrar</button>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: CIERRE
     ============================================================ -->
<div class="modal-overlay" id="modalCerrar">
    <div class="modal" style="max-width:480px">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-lock" style="color:#d97706"></i> Cerrar Caja</span>
            <button class="modal-close" onclick="Modal.close('modalCerrar')">&times;</button>
        </div>
        <div class="modal-body">
            <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;
                        padding:10px 14px;margin-bottom:16px;font-size:.82rem;color:#92400e">
                <i class="fa fa-triangle-exclamation"></i>
                Esta acción cerrará el turno. No se podrán registrar más movimientos hasta nueva apertura.
            </div>
            <div style="background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:16px;margin-bottom:16px">
                <table style="width:100%;font-size:.87rem;border-collapse:collapse">
                    <tr>
                        <td style="color:var(--text-muted);padding:4px 0">Saldo inicial:</td>
                        <td style="text-align:right;font-weight:600" id="cerrarInicial">—</td>
                    </tr>
                    <tr>
                        <td style="color:#16a34a;padding:4px 0">+ Ingresos:</td>
                        <td style="text-align:right;font-weight:600;color:#16a34a" id="cerrarIngresos">—</td>
                    </tr>
                    <tr>
                        <td style="color:#dc2626;padding:4px 0">− Egresos:</td>
                        <td style="text-align:right;font-weight:600;color:#dc2626" id="cerrarEgresos">—</td>
                    </tr>
                    <tr style="border-top:2px solid var(--border)">
                        <td style="font-weight:700;padding:8px 0 4px">Saldo sistema:</td>
                        <td style="text-align:right;font-weight:800;font-size:1.05rem;color:var(--primary)" id="cerrarSistema">—</td>
                    </tr>
                </table>
            </div>
            <div class="form-group">
                <label class="form-label">Monto físico contado (S/) *</label>
                <input type="number" id="cerrarFisico" class="form-control" step="0.01" min="0"
                       placeholder="0.00" oninput="calcCierreDif()">
            </div>
            <div class="dif-box" id="cierreDifBox">
                <span style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted)">Diferencia</span>
                <span class="dif-val" id="cierreDifVal">—</span>
                <span class="dif-msg" id="cierreDifMsg">—</span>
            </div>
            <div class="form-group" style="margin-top:14px">
                <label class="form-label">Observaciones al cierre</label>
                <textarea id="cerrarObs" class="form-control" rows="2" placeholder="Opcional..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalCerrar')">Cancelar</button>
            <button class="btn btn-warning btn-cerrar" onclick="confirmarCierre()">
                <i class="fa fa-lock"></i> Confirmar Cierre
            </button>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: DETALLE DE BANCO
     ============================================================ -->
<div class="modal-overlay" id="modalDetalleBanco">
    <div class="modal" style="max-width:620px">
        <div class="modal-header">
            <span class="modal-title" id="detalleBancoTitle"><i class="fa fa-building-columns"></i> Banco</span>
            <button class="modal-close" onclick="Modal.close('modalDetalleBanco')">&times;</button>
        </div>
        <div class="modal-body" style="padding:0">
            <!-- Stats -->
            <div id="detalleBancoStats"
                 style="display:flex;gap:0;border-bottom:1px solid var(--border)">
            </div>
            <!-- Tabs -->
            <div class="tabs" style="padding:0 18px;border-bottom:1px solid var(--border)">
                <div class="tab active" data-group="bancoTab" data-target="bancoTabTransferencias">
                    <i class="fa fa-right-left"></i> Transferencias
                </div>
                <div class="tab" data-group="bancoTab" data-target="bancoTabMovimientos">
                    <i class="fa fa-list-ul"></i> Movimientos de caja
                </div>
            </div>
            <!-- Tab: Transferencias -->
            <div class="tab-content active" data-group="bancoTab" id="bancoTabTransferencias"
                 style="max-height:340px;overflow-y:auto">
                <table>
                    <thead>
                        <tr>
                            <th style="width:120px">Fecha</th>
                            <th>Origen</th>
                            <th>Destino</th>
                            <th>Concepto</th>
                            <th style="text-align:right;width:120px">Monto</th>
                        </tr>
                    </thead>
                    <tbody id="detalleTransferencias">
                        <tr><td colspan="5" class="text-center" style="padding:30px;color:var(--text-muted)">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
            <!-- Tab: Movimientos -->
            <div class="tab-content" data-group="bancoTab" id="bancoTabMovimientos"
                 style="max-height:340px;overflow-y:auto">
                <table>
                    <thead>
                        <tr>
                            <th style="width:120px">Fecha</th>
                            <th style="width:100px">Tipo</th>
                            <th>Concepto</th>
                            <th style="text-align:right;width:120px">Monto</th>
                        </tr>
                    </thead>
                    <tbody id="detalleMovimientos">
                        <tr><td colspan="4" class="text-center" style="padding:30px;color:var(--text-muted)">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: TRANSFERENCIA ENTRE BANCOS
     ============================================================ -->
<div class="modal-overlay" id="modalTransferencia">
    <div class="modal" style="max-width:440px">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-right-left"></i> Transferencia entre bancos</span>
            <button class="modal-close" onclick="Modal.close('modalTransferencia')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Banco origen *</label>
                <select id="transOrigen" class="form-control" onchange="actualizarSaldoOrigen()"></select>
                <div id="transSaldoOrigen" style="font-size:.75rem;color:var(--text-muted);margin-top:4px"></div>
            </div>
            <div style="text-align:center;margin:4px 0;color:var(--text-muted)">
                <i class="fa fa-arrow-down"></i>
            </div>
            <div class="form-group">
                <label class="form-label">Banco destino *</label>
                <select id="transDestino" class="form-control"></select>
            </div>
            <div class="form-group">
                <label class="form-label">Monto (S/) *</label>
                <input type="number" id="transMonto" class="form-control" step="0.01" min="0.01" placeholder="0.00">
            </div>
            <div class="form-group">
                <label class="form-label">Concepto</label>
                <input type="text" id="transConcepto" class="form-control" placeholder="Motivo de la transferencia...">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalTransferencia')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarTransferencia()">
                <i class="fa fa-right-left"></i> Confirmar transferencia
            </button>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: CONFIGURACIÓN BANCARIA
     ============================================================ -->
<div class="modal-overlay" id="modalConfigBancos">
    <div class="modal" style="max-width:520px">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-gear"></i> Configuración de banco</span>
            <button class="modal-close" onclick="Modal.close('modalConfigBancos')">&times;</button>
        </div>
        <div class="modal-body" style="padding:0">
            <div style="padding:14px 18px;border-bottom:1px solid var(--border);
                        display:flex;justify-content:flex-end">
                <button class="btn btn-primary" style="font-size:.82rem;padding:5px 14px"
                        onclick="abrirModalBanco()">
                    <i class="fa fa-plus"></i> Agregar banco
                </button>
            </div>
            <div id="configBancosList" style="padding:8px 0">
                <div style="padding:30px;text-align:center;color:var(--text-muted)">Cargando...</div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: BANCO
     ============================================================ -->
<div class="modal-overlay" id="modalBanco">
    <div class="modal" style="max-width:440px">
        <div class="modal-header">
            <span class="modal-title" id="modalBancoTitle">
                <i class="fa fa-building-columns"></i> Agregar Banco
            </span>
            <button class="modal-close" onclick="Modal.close('modalBanco')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="bancoId">

            <!-- Logo / ícono -->
            <div class="form-group" style="display:flex;align-items:center;gap:16px">
                <div id="bancoLogoPreview"
                     style="width:64px;height:64px;border-radius:10px;border:2px dashed var(--border);
                            display:flex;align-items:center;justify-content:center;
                            overflow:hidden;flex-shrink:0;background:#f8fafc;cursor:pointer"
                     onclick="document.getElementById('bancoLogo').click()" title="Cambiar ícono">
                    <i class="fa fa-building-columns" id="bancoLogoIcon"
                       style="font-size:1.6rem;color:var(--text-muted);opacity:.4"></i>
                </div>
                <div style="flex:1">
                    <label class="form-label" style="margin-bottom:4px">Ícono / Logo</label>
                    <input type="file" id="bancoLogo" accept="image/*"
                           style="display:none" onchange="previewLogo(this)">
                    <button class="btn btn-outline" style="font-size:.78rem;padding:4px 12px;width:100%"
                            onclick="document.getElementById('bancoLogo').click()">
                        <i class="fa fa-upload"></i> Subir imagen
                    </button>
                    <div style="font-size:.72rem;color:var(--text-muted);margin-top:4px">
                        JPG, PNG, WEBP · máx. 1 MB
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Nombre / Alias *</label>
                <input type="text" id="bancoNombre" class="form-control" placeholder="Ej: Cuenta Principal BCP">
            </div>
            <div class="form-group">
                <label class="form-label">Banco *</label>
                <select id="bancoBanco" class="form-control">
                    <option value="BCP">BCP</option>
                    <option value="Scotiabank">Scotiabank</option>
                    <option value="BBVA">BBVA</option>
                    <option value="Interbank">Interbank</option>
                    <option value="BanBif">BanBif</option>
                    <option value="Pichincha">Pichincha</option>
                    <option value="Otro">Otro</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">N° de Cuenta</label>
                <input type="text" id="bancoNumero" class="form-control" placeholder="Opcional">
            </div>
            <div class="form-group">
                <label class="form-label">Tipo de cuenta</label>
                <select id="bancoTipo" class="form-control">
                    <option value="corriente">Corriente</option>
                    <option value="ahorro">Ahorro</option>
                    <option value="recaudadora">Recaudadora</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Saldo actual (S/)</label>
                <input type="number" id="bancoSaldo" class="form-control" step="0.01" min="0" placeholder="0.00">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalBanco')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarBanco()">
                <i class="fa fa-save"></i> Guardar
            </button>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
const API = 'api.php';
let cajaActual  = null;
let bancosCache = [];

// ================================================================
// INIT
// ================================================================
document.addEventListener('DOMContentLoaded', () => {
    initTabs();
    cargarEstado();
    cargarBancos();
    cargarHistorial();
    document.querySelector('[data-target="tabHistorial"]').addEventListener('click', cargarHistorial);
});

// ================================================================
// STATUS CARD
// ================================================================
async function cargarEstado() {
    try {
        const res = await apiGet(API + '?action=estado_actual');
        cajaActual = res.caja;
        renderStatus();
        if (cajaActual) {
            cargarMovimientos();
        } else {
            document.getElementById('tbodyMovimientos').innerHTML =
                '<tr><td colspan="6" class="text-center" style="padding:40px;color:var(--text-muted)">Abra la caja para registrar movimientos.</td></tr>';
        }
    } catch(e) { console.error(e); }
}

function renderStatus() {
    const c = cajaActual;
    if (c) {
        document.getElementById('cajaBadge').innerHTML =
            '<span class="badge badge-completado" style="font-size:.78rem;padding:4px 12px"><i class="fa fa-lock-open"></i> Abierta</span>';
        document.getElementById('cajaFecha').textContent =
            'Turno iniciado ' + new Date(c.hora_apertura).toLocaleDateString('es-PE', {day:'2-digit', month:'long'});
        document.getElementById('stInicial').textContent  = formatMoney(c.saldo_inicial);
        document.getElementById('stIngresos').textContent = formatMoney(c.total_ingresos);
        document.getElementById('stEgresos').textContent  = formatMoney(c.total_egresos);
        document.getElementById('stSaldo').textContent    = formatMoney(saldoSistema());
        document.getElementById('metaApertura').innerHTML =
            '<i class="fa fa-user" style="opacity:.5;margin-right:4px"></i>' +
            (c.usuario_apertura_nombre || '—') + ' &nbsp;·&nbsp; ' +
            new Date(c.hora_apertura).toLocaleTimeString('es-PE', {hour:'2-digit', minute:'2-digit'});
        document.getElementById('cajaActions').style.display = 'flex';
        document.getElementById('cajaBtnAbrir').style.display = 'none';
    } else {
        document.getElementById('cajaBadge').innerHTML =
            '<span class="badge badge-retrasado" style="font-size:.78rem;padding:4px 12px"><i class="fa fa-lock"></i> Cerrada</span>';
        document.getElementById('cajaFecha').textContent = 'Sin caja abierta';
        ['stInicial','stIngresos','stEgresos','stSaldo'].forEach(id =>
            document.getElementById(id).textContent = '—');
        document.getElementById('metaApertura').textContent = 'Abra la caja para comenzar a operar';
        document.getElementById('cajaActions').style.display = 'none';
        document.getElementById('cajaBtnAbrir').style.display = 'block';
    }
}

// ================================================================
// MOVIMIENTOS
// ================================================================
async function cargarMovimientos() {
    if (!cajaActual) return;
    try {
        const res = await apiGet(API + '?action=listar_movimientos&caja_id=' + cajaActual.id);
        const tbody = document.getElementById('tbodyMovimientos');
        if (!res.movimientos.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center" style="padding:40px;color:var(--text-muted)">Sin movimientos en este turno.</td></tr>';
            return;
        }
        tbody.innerHTML = res.movimientos.map(m => {
            const esIng = m.tipo === 'ingreso';
            const hora  = new Date(m.fecha).toLocaleTimeString('es-PE', {hour:'2-digit', minute:'2-digit'});
            return `<tr>
                <td style="font-size:.81rem;color:var(--text-muted)">${hora}</td>
                <td>${esIng
                    ? '<span class="badge badge-completado"><i class="fa fa-arrow-up"></i> Ingreso</span>'
                    : '<span class="badge badge-retrasado"><i class="fa fa-arrow-down"></i> Egreso</span>'}</td>
                <td style="font-size:.84rem">${labelConcepto(m.concepto)}</td>
                <td style="font-size:.82rem;color:var(--text-muted)">${m.descripcion || '—'}</td>
                <td style="text-align:right" class="${esIng ? 'mov-ing' : 'mov-eg'}">
                    ${esIng ? '+' : '−'} ${formatMoney(m.monto)}
                </td>
                <td style="font-size:.79rem;color:var(--text-muted)">${m.usuario_nombre || '—'}</td>
            </tr>`;
        }).join('');
    } catch(e) { console.error(e); }
}

// ================================================================
// HISTORIAL
// ================================================================
async function cargarHistorial() {
    try {
        const res = await apiGet(API + '?action=historial_cajas');
        const tbody = document.getElementById('tbodyHistorial');
        if (!res.cajas.length) {
            tbody.innerHTML = '<tr><td colspan="10" class="text-center" style="padding:40px;color:var(--text-muted)">Sin sesiones registradas.</td></tr>';
            return;
        }
        tbody.innerHTML = res.cajas.map(c => {
            const si   = +c.saldo_inicial;
            const ing  = +c.total_ingresos;
            const eg   = +c.total_egresos;
            const sist = c.saldo_final_sistema !== null ? +c.saldo_final_sistema : si + ing - eg;
            const fis  = c.saldo_final_fisico  !== null ? +c.saldo_final_fisico  : null;
            const dif  = c.diferencia          !== null ? +c.diferencia          : null;

            let difHtml = '—';
            if (dif !== null) {
                if      (dif === 0) difHtml = '<span style="color:#16a34a;font-weight:700">S/ 0.00 ✔</span>';
                else if (dif  > 0) difHtml = `<span style="color:#1d4ed8;font-weight:700">+${formatMoney(dif)}</span>`;
                else               difHtml = `<span style="color:#dc2626;font-weight:700">${formatMoney(dif)}</span>`;
            }
            const apHora = new Date(c.hora_apertura).toLocaleTimeString('es-PE', {hour:'2-digit', minute:'2-digit'});
            const clHora = c.hora_cierre
                ? new Date(c.hora_cierre).toLocaleTimeString('es-PE', {hour:'2-digit', minute:'2-digit'})
                : null;
            return `<tr>
                <td style="font-size:.84rem">${c.fecha}</td>
                <td style="font-size:.79rem">${apHora}<br><span style="color:var(--text-muted)">${c.usuario_apertura_nombre || '—'}</span></td>
                <td style="font-size:.79rem">${clHora
                    ? clHora + '<br><span style="color:var(--text-muted)">' + (c.usuario_cierre_nombre || '—') + '</span>'
                    : '—'}</td>
                <td style="text-align:right">${formatMoney(si)}</td>
                <td style="text-align:right;color:#16a34a;font-weight:600">${formatMoney(ing)}</td>
                <td style="text-align:right;color:#dc2626;font-weight:600">${formatMoney(eg)}</td>
                <td style="text-align:right;font-weight:700;color:var(--primary)">${formatMoney(sist)}</td>
                <td style="text-align:right">${fis !== null ? formatMoney(fis) : '—'}</td>
                <td style="text-align:right">${difHtml}</td>
                <td>${c.estado === 'abierta'
                    ? '<span class="badge badge-completado" style="font-size:.7rem">Abierta</span>'
                    : '<span class="badge" style="font-size:.7rem;background:#f1f5f9;color:#64748b">Cerrada</span>'}</td>
            </tr>`;
        }).join('');
    } catch(e) { console.error(e); }
}

// ================================================================
// HELPERS
// ================================================================
function labelConcepto(c) {
    return ({
        venta_efectivo:         'Venta en efectivo',
        cobro_cliente:          'Cobro a cliente',
        ingreso_extraordinario: 'Ingreso extraordinario',
        compra_menor:           'Compra menor',
        pago_rapido:            'Pago rápido',
        devolucion_cliente:     'Devolución a cliente',
        otro:                   'Otro',
    })[c] || c;
}

function saldoSistema() {
    if (!cajaActual) return 0;
    return +cajaActual.saldo_inicial + +cajaActual.total_ingresos - +cajaActual.total_egresos;
}

function renderDifBox(boxId, valId, msgId, dif) {
    const box = document.getElementById(boxId);
    const val = document.getElementById(valId);
    const msg = document.getElementById(msgId);
    box.style.display = 'block';
    if (Math.abs(dif) < 0.005) {
        box.style.background = '#f0fdf4'; box.style.border = '1px solid #bbf7d0';
        val.style.color = '#16a34a'; val.textContent = 'S/ 0.00';
        msg.textContent = '✔ Caja cuadrada perfectamente';
    } else if (dif > 0) {
        box.style.background = '#eff6ff'; box.style.border = '1px solid #bfdbfe';
        val.style.color = '#1d4ed8'; val.textContent = '+' + formatMoney(dif);
        msg.textContent = 'Sobrante en caja';
    } else {
        box.style.background = '#fef2f2'; box.style.border = '1px solid #fecaca';
        val.style.color = '#dc2626'; val.textContent = formatMoney(dif);
        msg.textContent = '⚠ Faltante en caja';
    }
}

// ================================================================
// APERTURA
// ================================================================
function abrirModalApertura() {
    document.getElementById('apertSaldo').value = '';
    document.getElementById('apertObs').value   = '';
    Modal.open('modalApertura');
    setTimeout(() => document.getElementById('apertSaldo').focus(), 120);
}

async function guardarApertura() {
    const saldo = parseFloat(document.getElementById('apertSaldo').value || '0');
    const obs   = document.getElementById('apertObs').value.trim();
    if (saldo < 0) { Toast.show('El saldo inicial no puede ser negativo', 'error'); return; }
    try {
        const res = await apiPost(API, { action:'abrir_caja', saldo_inicial:saldo, observaciones:obs });
        if (!res.ok) { Toast.show(res.error || 'Error al abrir caja', 'error'); return; }
        Modal.close('modalApertura');
        Toast.show('Caja abierta correctamente', 'success');
        await cargarEstado();
    } catch(e) { Toast.show('Error de conexión', 'error'); }
}

// ================================================================
// INGRESO / EGRESO
// ================================================================
function buildBancoOptions(selectId) {
    const opts = '<option value="">— Sin banco / Efectivo —</option>'
        + bancosCache.map(b => `<option value="${b.id}">${b.nombre} (${b.banco})</option>`).join('');
    const sel = document.getElementById(selectId);
    sel.innerHTML = opts;
    if (selectedBancoId) sel.value = selectedBancoId;
}

function abrirModalIngreso() {
    document.getElementById('ingConcepto').selectedIndex = 0;
    document.getElementById('ingDesc').value  = '';
    document.getElementById('ingMonto').value = '';
    buildBancoOptions('ingBanco');
    Modal.open('modalIngreso');
    setTimeout(() => document.getElementById('ingMonto').focus(), 120);
}

function abrirModalEgreso() {
    document.getElementById('egConcepto').value = '';
    document.getElementById('egDesc').value  = '';
    document.getElementById('egMonto').value = '';
    buildBancoOptions('egBanco');
    Modal.open('modalEgreso');
    setTimeout(() => document.getElementById('egMonto').focus(), 120);
}

async function guardarMovimiento(tipo) {
    const p       = tipo === 'ingreso' ? 'ing' : 'eg';
    const monto   = parseFloat(document.getElementById(p + 'Monto').value || '0');
    const bancoId = document.getElementById(p + 'Banco').value || null;
    if (monto <= 0)  { Toast.show('Ingrese un monto válido', 'error'); return; }
    if (!cajaActual) { Toast.show('No hay caja abierta', 'error'); return; }
    try {
        const res = await apiPost(API, {
            action:      'registrar_movimiento',
            caja_id:     cajaActual.id,
            tipo,
            concepto:    document.getElementById(p + 'Concepto').value,
            descripcion: document.getElementById(p + 'Desc').value.trim(),
            monto,
            banco_id:    bancoId,
        });
        if (!res.ok) { Toast.show(res.error || 'Error al registrar', 'error'); return; }
        Modal.close('modalIngreso');
        Modal.close('modalEgreso');
        Toast.show(tipo === 'ingreso' ? 'Ingreso registrado' : 'Egreso registrado', 'success');
        await cargarEstado();
        await cargarMovimientos();
        cargarBancos();
    } catch(e) { Toast.show('Error de conexión', 'error'); }
}

// ================================================================
// ARQUEO
// ================================================================
function abrirModalArqueo() {
    if (!cajaActual) return;
    document.getElementById('arqueoInicial').textContent  = formatMoney(cajaActual.saldo_inicial);
    document.getElementById('arqueoIngresos').textContent = formatMoney(cajaActual.total_ingresos);
    document.getElementById('arqueoEgresos').textContent  = formatMoney(cajaActual.total_egresos);
    document.getElementById('arqueoSistema').textContent  = formatMoney(saldoSistema());
    document.getElementById('arqueoFisico').value = '';
    document.getElementById('arqueoDifBox').style.display = 'none';
    Modal.open('modalArqueo');
    setTimeout(() => document.getElementById('arqueoFisico').focus(), 120);
}

function calcArqueoDif() {
    const f = parseFloat(document.getElementById('arqueoFisico').value || '');
    if (isNaN(f)) { document.getElementById('arqueoDifBox').style.display = 'none'; return; }
    renderDifBox('arqueoDifBox', 'arqueoDifVal', 'arqueoDifMsg', f - saldoSistema());
}

// ================================================================
// CIERRE
// ================================================================
function abrirModalCerrar() {
    if (!cajaActual) return;
    document.getElementById('cerrarInicial').textContent  = formatMoney(cajaActual.saldo_inicial);
    document.getElementById('cerrarIngresos').textContent = formatMoney(cajaActual.total_ingresos);
    document.getElementById('cerrarEgresos').textContent  = formatMoney(cajaActual.total_egresos);
    document.getElementById('cerrarSistema').textContent  = formatMoney(saldoSistema());
    document.getElementById('cerrarFisico').value = '';
    document.getElementById('cerrarObs').value    = '';
    document.getElementById('cierreDifBox').style.display = 'none';
    Modal.open('modalCerrar');
    setTimeout(() => document.getElementById('cerrarFisico').focus(), 120);
}

function calcCierreDif() {
    const f = parseFloat(document.getElementById('cerrarFisico').value || '');
    if (isNaN(f)) { document.getElementById('cierreDifBox').style.display = 'none'; return; }
    renderDifBox('cierreDifBox', 'cierreDifVal', 'cierreDifMsg', f - saldoSistema());
}

// ================================================================
// BANCOS
// ================================================================
const LOGO_BASE = '../../assets/uploads/bancos/';
let selectedBancoId = null;

function logoHtml(logo, size = 36) {
    const s = `width:${size}px;height:${size}px;border-radius:8px;object-fit:contain;background:#f1f5f9;padding:2px`;
    return logo
        ? `<img src="${LOGO_BASE}${logo}" style="${s}" onerror="this.style.display='none'">`
        : `<span style="display:inline-flex;align-items:center;justify-content:center;${s}">
               <i class="fa fa-building-columns" style="color:#94a3b8;font-size:${Math.round(size*.55)}px"></i>
           </span>`;
}

async function cargarBancos() {
    try {
        const res = await apiGet(API + '?action=listar_bancos');
        bancosCache = res.bancos;
        const container = document.getElementById('bancosCards');
        if (!bancosCache.length) {
            container.innerHTML = '<div style="color:var(--text-muted);font-size:.86rem;padding:10px 0">Sin cuentas bancarias registradas. Use "Configuración de banco" para agregar.</div>';
            return;
        }
        container.innerHTML = bancosCache.map(b => `
            <div class="banco-card${selectedBancoId === b.id ? ' selected' : ''}"
                 id="bancoCard${b.id}" onclick="seleccionarBanco(${b.id})">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
                    ${logoHtml(b.logo, 40)}
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:700;font-size:.92rem;line-height:1.2;
                                    white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${b.nombre}</div>
                        <div style="font-size:.75rem;color:var(--text-muted)">${b.banco}</div>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;padding-top:12px">
                    <div style="text-align:center;padding:0 6px">
                        <div style="font-size:.6rem;font-weight:700;text-transform:uppercase;
                                    letter-spacing:.07em;color:var(--text-muted);margin-bottom:4px">Saldo</div>
                        <div style="font-size:.87rem;font-weight:800;color:var(--primary)">${formatMoney(b.saldo_actual)}</div>
                    </div>
                    <div style="text-align:center;padding:0 6px;border-left:1px solid var(--border)">
                        <div style="font-size:.6rem;font-weight:700;text-transform:uppercase;
                                    letter-spacing:.07em;color:#16a34a;margin-bottom:4px">Ingresos</div>
                        <div style="font-size:.87rem;font-weight:800;color:#16a34a">${formatMoney(b.total_ingresos)}</div>
                    </div>
                    <div style="text-align:center;padding:0 6px;border-left:1px solid var(--border)">
                        <div style="font-size:.6rem;font-weight:700;text-transform:uppercase;
                                    letter-spacing:.07em;color:#dc2626;margin-bottom:4px">Egresos</div>
                        <div style="font-size:.87rem;font-weight:800;color:#dc2626">${formatMoney(b.total_egresos)}</div>
                    </div>
                </div>
            </div>
        `).join('');
    } catch(e) { console.error(e); }
}

function seleccionarBanco(id) {
    selectedBancoId = id;
    document.querySelectorAll('.banco-card').forEach(el => el.classList.remove('selected'));
    const card = document.getElementById('bancoCard' + id);
    if (card) card.classList.add('selected');
    abrirDetalleBanco(id);
}

async function abrirDetalleBanco(id) {
    const b = bancosCache.find(x => +x.id === id);
    if (!b) return;

    // Título
    document.getElementById('detalleBancoTitle').innerHTML =
        `${logoHtml(b.logo, 22)} <span style="margin-left:8px">${b.nombre}</span>
         <span style="font-size:.75rem;color:var(--text-muted);font-weight:400;margin-left:8px">${b.banco}</span>`;

    // Stats
    document.getElementById('detalleBancoStats').innerHTML = [
        { label:'Saldo', val: formatMoney(b.saldo_actual), color:'var(--primary)' },
        { label:'Ingresos', val: formatMoney(b.total_ingresos), color:'#16a34a' },
        { label:'Egresos',  val: formatMoney(b.total_egresos),  color:'#dc2626' },
    ].map((s, i) => `
        <div style="flex:1;text-align:center;padding:16px 12px;
                    ${i > 0 ? 'border-left:1px solid var(--border)' : ''}">
            <div style="font-size:.62rem;font-weight:700;text-transform:uppercase;
                        letter-spacing:.07em;color:var(--text-muted);margin-bottom:4px">${s.label}</div>
            <div style="font-size:1.05rem;font-weight:800;color:${s.color}">${s.val}</div>
        </div>
    `).join('');

    Modal.open('modalDetalleBanco');
    initTabs(document.getElementById('modalDetalleBanco'));

    // Cargar datos en paralelo
    cargarDetalleTransferencias(id);
    cargarDetalleMovimientos(id);
}

async function cargarDetalleTransferencias(id) {
    const tbody = document.getElementById('detalleTransferencias');
    try {
        const res = await apiGet(API + '?action=historial_transferencias&banco_id=' + id);
        if (!res.transferencias.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center" style="padding:24px;color:var(--text-muted)">Sin transferencias registradas.</td></tr>';
            return;
        }
        tbody.innerHTML = res.transferencias.map(t => {
            const esOrigen  = +t.banco_origen_id === id;
            const fecha     = new Date(t.fecha).toLocaleDateString('es-PE', {day:'2-digit',month:'2-digit',year:'numeric'});
            const hora      = new Date(t.fecha).toLocaleTimeString('es-PE', {hour:'2-digit',minute:'2-digit'});
            return `<tr>
                <td style="font-size:.8rem;color:var(--text-muted)">${fecha}<br>${hora}</td>
                <td style="font-size:.82rem">${t.origen_nombre}</td>
                <td style="font-size:.82rem">${t.destino_nombre}</td>
                <td style="font-size:.8rem;color:var(--text-muted)">${t.concepto || '—'}</td>
                <td style="text-align:right;font-weight:700;
                           color:${esOrigen ? '#dc2626' : '#16a34a'}">
                    ${esOrigen ? '−' : '+'} ${formatMoney(t.monto)}
                </td>
            </tr>`;
        }).join('');
    } catch(e) { tbody.innerHTML = '<tr><td colspan="5" class="text-center" style="padding:24px;color:var(--text-muted)">Error al cargar.</td></tr>'; }
}

async function cargarDetalleMovimientos(id) {
    const tbody = document.getElementById('detalleMovimientos');
    try {
        const res = await apiGet(API + '?action=movimientos_banco&banco_id=' + id);
        if (!res.movimientos.length) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center" style="padding:24px;color:var(--text-muted)">Sin movimientos de caja asociados.</td></tr>';
            return;
        }
        tbody.innerHTML = res.movimientos.map(m => {
            const esIng = m.tipo === 'ingreso';
            const fecha = new Date(m.fecha).toLocaleDateString('es-PE', {day:'2-digit',month:'2-digit',year:'numeric'});
            const hora  = new Date(m.fecha).toLocaleTimeString('es-PE', {hour:'2-digit',minute:'2-digit'});
            return `<tr>
                <td style="font-size:.8rem;color:var(--text-muted)">${fecha}<br>${hora}</td>
                <td>${esIng
                    ? '<span class="badge badge-completado" style="font-size:.7rem"><i class="fa fa-arrow-up"></i> Ingreso</span>'
                    : '<span class="badge badge-retrasado"  style="font-size:.7rem"><i class="fa fa-arrow-down"></i> Egreso</span>'}</td>
                <td style="font-size:.82rem">${labelConcepto(m.concepto)}${m.descripcion ? '<br><span style="color:var(--text-muted);font-size:.76rem">'+m.descripcion+'</span>' : ''}</td>
                <td style="text-align:right;font-weight:700;color:${esIng ? '#16a34a' : '#dc2626'}">
                    ${esIng ? '+' : '−'} ${formatMoney(m.monto)}
                </td>
            </tr>`;
        }).join('');
    } catch(e) { tbody.innerHTML = '<tr><td colspan="4" class="text-center" style="padding:24px;color:var(--text-muted)">Error al cargar.</td></tr>'; }
}

function abrirConfigBancos() {
    renderConfigBancos();
    Modal.open('modalConfigBancos');
}

function renderConfigBancos() {
    const list = document.getElementById('configBancosList');
    if (!bancosCache.length) {
        list.innerHTML = '<div style="padding:24px;text-align:center;color:var(--text-muted)">Sin bancos registrados.</div>';
        return;
    }
    list.innerHTML = bancosCache.map(b => `
        <div style="display:flex;align-items:center;gap:12px;padding:10px 18px;
                    border-bottom:1px solid var(--border)">
            ${logoHtml(b.logo, 32)}
            <div style="flex:1;min-width:0">
                <div style="font-weight:600;font-size:.88rem">${b.nombre}</div>
                <div style="font-size:.75rem;color:var(--text-muted)">${b.banco}${b.numero_cuenta ? ' · ' + b.numero_cuenta : ''}</div>
            </div>
            <div style="display:flex;gap:6px;flex-shrink:0">
                <button class="btn btn-outline" style="padding:3px 10px;font-size:.78rem"
                        onclick="editarBanco(${b.id})">
                    <i class="fa fa-pen"></i> Editar
                </button>
                <button class="btn btn-outline" style="padding:3px 10px;font-size:.78rem;
                        color:#dc2626;border-color:#fca5a5"
                        onclick="eliminarBancoConfig(${b.id})">
                    <i class="fa fa-trash"></i>
                </button>
            </div>
        </div>
    `).join('');
}

async function eliminarBancoConfig(id) {
    if (!confirm('¿Eliminar esta cuenta bancaria?')) return;
    try {
        const res = await apiPost(API, { action: 'eliminar_banco', id });
        if (!res.ok) { Toast.show(res.error || 'Error', 'error'); return; }
        if (selectedBancoId === id) selectedBancoId = null;
        Toast.show('Cuenta eliminada', 'success');
        await cargarBancos();
        renderConfigBancos();
    } catch(e) { Toast.show('Error de conexión', 'error'); }
}

// ================================================================
// TRANSFERENCIAS
// ================================================================
function abrirModalTransferencia() {
    if (bancosCache.length < 2) {
        Toast.show('Necesitas al menos 2 bancos para transferir', 'warning');
        return;
    }
    const opts = bancosCache.map(b =>
        `<option value="${b.id}" data-saldo="${b.saldo_actual}">${b.nombre} (${b.banco})</option>`
    ).join('');
    document.getElementById('transOrigen').innerHTML  = opts;
    document.getElementById('transDestino').innerHTML = opts;
    // Pre-seleccionar banco activo como origen si hay uno
    if (selectedBancoId) {
        document.getElementById('transOrigen').value = selectedBancoId;
        // Destino = primer banco distinto
        const otro = bancosCache.find(b => b.id !== selectedBancoId);
        if (otro) document.getElementById('transDestino').value = otro.id;
    } else {
        // Destino por defecto = segundo banco
        document.getElementById('transDestino').value = bancosCache[1].id;
    }
    document.getElementById('transMonto').value    = '';
    document.getElementById('transConcepto').value = '';
    actualizarSaldoOrigen();
    Modal.open('modalTransferencia');
    setTimeout(() => document.getElementById('transMonto').focus(), 120);
}

function actualizarSaldoOrigen() {
    const sel    = document.getElementById('transOrigen');
    const opt    = sel.options[sel.selectedIndex];
    const saldo  = opt ? parseFloat(opt.dataset.saldo) : 0;
    const label  = document.getElementById('transSaldoOrigen');
    label.textContent = opt ? `Saldo disponible: ${formatMoney(saldo)}` : '';
}

async function guardarTransferencia() {
    const origenId  = parseInt(document.getElementById('transOrigen').value);
    const destinoId = parseInt(document.getElementById('transDestino').value);
    const monto     = parseFloat(document.getElementById('transMonto').value || '0');
    const concepto  = document.getElementById('transConcepto').value.trim();

    if (origenId === destinoId) { Toast.show('El banco origen y destino deben ser diferentes', 'error'); return; }
    if (monto <= 0) { Toast.show('Ingrese un monto válido', 'error'); return; }

    try {
        const res = await apiPost(API, {
            action:          'transferir_banco',
            banco_origen_id:  origenId,
            banco_destino_id: destinoId,
            monto,
            concepto,
        });
        if (!res.ok) { Toast.show(res.error || 'Error al transferir', 'error'); return; }
        Modal.close('modalTransferencia');
        Toast.show('Transferencia realizada correctamente', 'success');
        cargarBancos();
    } catch(e) { Toast.show('Error de conexión', 'error'); }
}

function resetLogoPreview(src) {
    const preview = document.getElementById('bancoLogoPreview');
    const icon    = document.getElementById('bancoLogoIcon');
    document.getElementById('bancoLogo').value = '';
    if (src) {
        preview.style.border = '2px solid var(--border)';
        preview.innerHTML    = `<img src="${src}" style="width:100%;height:100%;object-fit:contain;padding:4px">`;
    } else {
        preview.style.border = '2px dashed var(--border)';
        preview.innerHTML    = '<i class="fa fa-building-columns" id="bancoLogoIcon" style="font-size:1.6rem;color:var(--text-muted);opacity:.4"></i>';
    }
}

function previewLogo(input) {
    if (!input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        const preview = document.getElementById('bancoLogoPreview');
        preview.style.border = '2px solid var(--primary)';
        preview.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:contain;padding:4px">`;
    };
    reader.readAsDataURL(input.files[0]);
}

function abrirModalBanco() {
    document.getElementById('bancoId').value     = '';
    document.getElementById('bancoNombre').value = '';
    document.getElementById('bancoBanco').selectedIndex = 0;
    document.getElementById('bancoNumero').value = '';
    document.getElementById('bancoTipo').selectedIndex  = 0;
    document.getElementById('bancoSaldo').value  = '';
    document.getElementById('modalBancoTitle').innerHTML = '<i class="fa fa-building-columns"></i> Agregar Banco';
    resetLogoPreview(null);
    Modal.open('modalBanco');
    setTimeout(() => document.getElementById('bancoNombre').focus(), 120);
}

function editarBanco(id) {
    const b = bancosCache.find(x => +x.id === id);
    if (!b) return;
    document.getElementById('bancoId').value     = b.id;
    document.getElementById('bancoNombre').value = b.nombre;
    document.getElementById('bancoBanco').value  = b.banco;
    document.getElementById('bancoNumero').value = b.numero_cuenta || '';
    document.getElementById('bancoTipo').value   = b.tipo_cuenta;
    document.getElementById('bancoSaldo').value  = b.saldo_actual;
    document.getElementById('modalBancoTitle').innerHTML = '<i class="fa fa-pen"></i> Editar Banco';
    resetLogoPreview(b.logo ? LOGO_BASE + b.logo : null);
    Modal.open('modalBanco');
}

async function guardarBanco() {
    const nombre = document.getElementById('bancoNombre').value.trim();
    if (!nombre) { Toast.show('El nombre es requerido', 'error'); return; }
    const form = new FormData();
    form.append('action',        'guardar_banco');
    form.append('id',            document.getElementById('bancoId').value || '');
    form.append('nombre',        nombre);
    form.append('banco',         document.getElementById('bancoBanco').value);
    form.append('numero_cuenta', document.getElementById('bancoNumero').value.trim());
    form.append('tipo_cuenta',   document.getElementById('bancoTipo').value);
    form.append('saldo_actual',  document.getElementById('bancoSaldo').value || '0');
    const logoFile = document.getElementById('bancoLogo').files[0];
    if (logoFile) form.append('logo', logoFile);
    try {
        const res  = await fetch(API, { method: 'POST', body: form });
        const json = await res.json();
        if (!json.ok) { Toast.show(json.error || 'Error al guardar', 'error'); return; }
        Modal.close('modalBanco');
        Toast.show('Banco guardado', 'success');
        await cargarBancos();
        renderConfigBancos();
    } catch(e) { Toast.show('Error de conexión', 'error'); }
}

async function confirmarCierre() {
    const f = parseFloat(document.getElementById('cerrarFisico').value || '');
    if (isNaN(f) || f < 0) { Toast.show('Ingrese el monto físico contado', 'error'); return; }
    const obs = document.getElementById('cerrarObs').value.trim();
    try {
        const res = await apiPost(API, {
            action:        'cerrar_caja',
            caja_id:       cajaActual.id,
            saldo_fisico:  f,
            observaciones: obs,
        });
        if (!res.ok) { Toast.show(res.error || 'Error al cerrar caja', 'error'); return; }
        Modal.close('modalCerrar');
        Toast.show('Caja cerrada correctamente', 'success');
        cajaActual = null;
        await cargarEstado();
        cargarHistorial();
    } catch(e) { Toast.show('Error de conexión', 'error'); }
}
</script>
<?php $extra_js = ob_get_clean(); ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<?php
$page_title      = 'Configuracion';
$page_breadcrumb = '<span>Sistema</span> <span>/</span> Configuracion';
require_once __DIR__ . '/../../includes/header.php';
$db = getDB();
$roles = $db->query("SELECT id, nombre FROM roles WHERE activo ORDER BY nombre")->fetchAll();
$areas = $db->query("SELECT id, nombre FROM areas WHERE activo ORDER BY nombre")->fetchAll();
$modulos = [
    'produccion'       => 'Produccion',
    'compras'          => 'Compras',
    'inventarios'      => 'Inventarios',
    'clientes'         => 'Clientes / CRM',
    'comercializacion' => 'Comercializacion',
    'reportes'         => 'Reportes',
    'seguridad'        => 'Seguridad',
    'capacitacion'     => 'Capacitacion',
];
?>

<style>
.empresa-grid {
    display: grid;
    gap: 1rem;
}
.empresa-hero {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: flex-start;
    padding: 1.1rem 1.2rem;
    border: 1px solid var(--border);
    border-radius: 16px;
    background: linear-gradient(135deg, rgba(29, 78, 216, .05), rgba(14, 165, 233, .03));
}
.empresa-hero h3 {
    margin: 0 0 .35rem;
    font-size: 1.05rem;
    color: var(--text-primary);
}
.empresa-hero p {
    margin: 0;
    color: var(--text-secondary);
    max-width: 760px;
    line-height: 1.5;
}
.empresa-pill {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    white-space: nowrap;
    padding: .5rem .8rem;
    border-radius: 999px;
    background: rgba(14, 165, 233, .1);
    color: #0891b2;
    font-size: .75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
}
.empresa-columns {
    display: grid;
    grid-template-columns: 1.1fr .9fr;
    gap: 1rem;
}
.empresa-card {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 16px;
    overflow: hidden;
}
.empresa-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    padding: .95rem 1.1rem;
    border-bottom: 1px solid var(--border);
    background: var(--bg-secondary);
}
.empresa-card-header h4 {
    margin: 0;
    font-size: .95rem;
    color: var(--text-primary);
}
.empresa-card-header p {
    margin: .2rem 0 0;
    font-size: .8rem;
    color: var(--text-secondary);
}
.empresa-card-body {
    padding: 1rem 1.1rem 1.15rem;
}
.empresa-upload {
    padding: .9rem 1rem;
    border: 1px dashed var(--border);
    border-radius: 12px;
    background: var(--bg-secondary);
}
.empresa-status {
    padding: .9rem 1rem;
    border-radius: 12px;
    background: var(--bg-secondary);
    color: var(--text-secondary);
    font-size: .84rem;
    line-height: 1.45;
}
.empresa-status strong {
    color: var(--text-primary);
}

.empresa-ubigeo-help {
    margin-top: .45rem;
    padding: .7rem .8rem;
    border: 1px dashed var(--border);
    border-radius: 12px;
    background: var(--bg-secondary);
    color: var(--text-secondary);
    font-size: .8rem;
}
@media (max-width: 980px) {
    .empresa-columns {
        grid-template-columns: 1fr;
    }
    .empresa-hero {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>

<!-- TABS -->
<div class="tabs">
    <div class="tab active" data-group="cfg" data-target="tabEmpresa">
        <i class="fa fa-building"></i> Empresa
    </div>
    <div class="tab" data-group="cfg" data-target="tabUsuarios">
        <i class="fa fa-users"></i> Usuarios
    </div>
    <div class="tab" data-group="cfg" data-target="tabRoles">
        <i class="fa fa-shield-halved"></i> Roles y Accesos
    </div>
    <div class="tab" data-group="cfg" data-target="tabAreas">
        <i class="fa fa-sitemap"></i> Areas
    </div>
</div>

<!-- ============================================================
     TAB: EMPRESA
     ============================================================ -->
<div class="tab-content active" data-group="cfg" id="tabEmpresa">
    <div class="empresa-grid">
        <div class="empresa-hero">
            <div>
                <h3>Datos para emitir en SUNAT</h3>
            </div>
            <div class="empresa-pill">
                <i class="fa fa-receipt"></i> Emision electronica
            </div>
        </div>
        <div class="empresa-columns">
            <div class="empresa-card">
                <div class="empresa-card-header">
                    <div>
                        <h4>Datos de la empresa</h4>
                        <p>Informacion comercial y fiscal visible en los comprobantes.</p>
                    </div>
                    <span class="badge badge-normal">Empresa emisora</span>
                </div>
                <div class="empresa-card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Razon social *</label>
                            <input type="text" class="form-control" id="empRazonSocial" placeholder="IndustriaMG S.A.C.">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nombre comercial</label>
                            <input type="text" class="form-control" id="empNombreComercial" placeholder="IndustriaMG">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">RUC</label>
                            <input type="text" class="form-control" id="empRuc" maxlength="11" placeholder="20123456789">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="empEmail" placeholder="facturacion@empresa.com">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Telefono</label>
                            <input type="text" class="form-control" id="empTelefono" placeholder="987654321">
                        </div>
                        <div class="form-group">
                            <label class="form-label">WhatsApp Instance</label>
                            <input type="text" class="form-control" id="empWhatsappInstance" placeholder="Opcional">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Direccion fiscal</label>
                        <textarea class="form-control" id="empDireccion" rows="3" placeholder="Av. Industrial 123"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Ubigeo</label>
                            <input type="text" class="form-control" id="empUbigeo" maxlength="6" placeholder="150101">
                            <div class="empresa-ubigeo-help" id="empUbigeoHelp">Ejemplo: 150101 corresponde a Lima.</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Pais</label>
                            <input type="text" class="form-control" id="empPais" value="PE" maxlength="2">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Departamento</label>
                            <input type="text" class="form-control" id="empDepartamento" placeholder="LIMA" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Provincia</label>
                            <input type="text" class="form-control" id="empProvincia" placeholder="LIMA" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Distrito</label>
                            <input type="text" class="form-control" id="empDistrito" placeholder="LIMA" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Logo</label>
                            <input type="file" class="form-control" id="empLogo" accept="image/*">
                            <div class="text-xs text-muted" id="empLogoActual" style="margin-top:4px">Sin logo cargado.</div>
                        </div>
                        <div class="form-group" style="display:flex;align-items:end">
                            <label style="display:flex;align-items:center;gap:.5rem;font-size:.85rem;cursor:pointer">
                                <input type="checkbox" id="empTaxEnabled" checked>
                                Facturacion electronica habilitada
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="empresa-card">
                <div class="empresa-card-header">
                    <div>
                        <h4>SUNAT y certificado</h4>
                        <p>Credenciales, entorno y certificado digital para pruebas.</p>
                    </div>
                    <span class="badge badge-completado" id="empSunatBadge">Pruebas</span>
                </div>
                <div class="empresa-card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Ambiente SUNAT</label>
                            <select class="form-control" id="empSunatServer">
                                <option value="beta">Beta / Pruebas</option>
                                <option value="prod">Produccion</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Usuario SOL</label>
                            <input type="text" class="form-control" id="empSunatUsername" placeholder="Usuario secundario SOL">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Clave SOL</label>
                            <input type="text" class="form-control" id="empSunatPassword" placeholder="Clave SOL">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">GRE Client ID</label>
                            <input type="text" class="form-control" id="empGreClientId" placeholder="Client ID GRE">
                        </div>
                        <div class="form-group">
                            <label class="form-label">GRE Client Secret</label>
                            <input type="text" class="form-control" id="empGreClientSecret" placeholder="Client Secret GRE">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Clave certificado</label>
                            <input type="text" class="form-control" id="empCertificadoPassword" placeholder="Clave del certificado">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Vencimiento certificado</label>
                            <input type="date" class="form-control" id="empCertificadoVence">
                        </div>
                    </div>
                    <div class="empresa-upload">
                        <div class="form-group" style="margin-bottom:.6rem">
                            <label class="form-label">Certificado digital (.pfx)</label>
                            <input type="file" class="form-control" id="empCertificado" accept=".pfx,.pem">
                            <div class="text-xs text-muted" id="empCertificadoActual" style="margin-top:6px">Sin certificado cargado.</div>
                        </div>
                        <div class="text-xs text-muted">Puedes subir aqui el certificado digital y el sistema guardara la ruta automaticamente.</div>
                    </div>
                    <div class="empresa-status" style="margin-top:1rem">
                        <strong>Estado</strong><br>
                        Estos datos se usan para pruebas de emision. Quedaron precargados con valores Mytems y un certificado base para que puedas configurar y validar sin empezar desde cero.
                    </div>
                    <div style="display:flex;justify-content:flex-end;margin-top:1rem">
                        <button class="btn btn-primary btn-sm" onclick="guardarEmpresa()">
                            <i class="fa fa-save"></i> Guardar configuracion
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     TAB: USUARIOS
     ============================================================ -->
<div class="tab-content" data-group="cfg" id="tabUsuarios">
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fa fa-users"></i> GestiÃ³n de Usuarios</span>
            <div class="card-actions">
                <div class="search-box">
                    <span class="icon"><i class="fa fa-search"></i></span>
                    <input type="text" class="form-control" id="buscarUsuario"
                           placeholder="Buscarâ€¦" oninput="filtrarUsuarios()">
                </div>
                <button class="btn btn-primary btn-sm" onclick="abrirModalUsuario()">
                    <i class="fa fa-plus"></i> Nuevo Usuario
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table>
                <thead><tr>
                    <th>Nombre</th><th>Email</th><th>Rol</th><th>Ãrea</th>
                    <th>Ãšltimo Acceso</th><th>Estado</th><th></th>
                </tr></thead>
                <tbody id="usuariosBody">
                    <tr><td colspan="7" class="text-center">
                        <div class="loading"><div class="spinner"></div></div>
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================
     TAB: ROLES Y ACCESOS
     ============================================================ -->
<div class="tab-content" data-group="cfg" id="tabRoles">
    <div style="display:flex;gap:1rem;margin-bottom:1rem;align-items:center;justify-content:space-between">
        <h3 style="margin:0;font-size:1rem;font-weight:600;color:var(--text-primary)">
            <i class="fa fa-shield-halved"></i> Roles y Permisos
        </h3>
        <button class="btn btn-primary btn-sm" onclick="abrirModalRol()">
            <i class="fa fa-plus"></i> Nuevo Rol
        </button>
    </div>
    <div id="rolesGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem">
        <div class="card" style="padding:2rem;text-align:center">
            <div class="loading"><div class="spinner"></div></div>
        </div>
    </div>
</div>

<!-- ============================================================
     TAB: ÃREAS
     ============================================================ -->
<div class="tab-content" data-group="cfg" id="tabAreas">
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fa fa-sitemap"></i> Ãreas de la Empresa</span>
            <div class="card-actions">
                <button class="btn btn-primary btn-sm" onclick="abrirModalArea()">
                    <i class="fa fa-plus"></i> Nueva Ãrea
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table>
                <thead><tr>
                    <th>Nombre</th><th>DescripciÃ³n</th><th>Usuarios</th><th>Estado</th><th></th>
                </tr></thead>
                <tbody id="areasBody">
                    <tr><td colspan="5" class="text-center">
                        <div class="loading"><div class="spinner"></div></div>
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: USUARIO
     ============================================================ -->
<div class="modal-overlay" id="modalUsuario">
    <div class="modal" style="max-width:520px">
        <div class="modal-header">
            <span class="modal-title" id="modalUsuarioTitulo">Nuevo Usuario</span>
            <button class="modal-close" onclick="Modal.close('modalUsuario')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="usuarioId">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nombre completo *</label>
                    <input type="text" class="form-control" id="usuarioNombre" placeholder="Juan PÃ©rez">
                </div>
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" class="form-control" id="usuarioEmail" placeholder="juan@empresa.com">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">ContraseÃ±a <span id="passHint" style="color:var(--text-secondary);font-size:.75rem">(requerida)</span></label>
                    <input type="password" class="form-control" id="usuarioPassword" placeholder="MÃ­nimo 6 caracteres">
                </div>
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select class="form-control" id="usuarioActivo">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Rol</label>
                    <select class="form-control" id="usuarioRol">
                        <option value="">â€” Sin rol â€”</option>
                        <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Ãrea</label>
                    <select class="form-control" id="usuarioArea">
                        <option value="">â€” Sin Ã¡rea â€”</option>
                        <?php foreach ($areas as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('modalUsuario')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarUsuario()">
                <i class="fa fa-save"></i> Guardar
            </button>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: ROL
     ============================================================ -->
<div class="modal-overlay" id="modalRol">
    <div class="modal" style="max-width:680px">
        <div class="modal-header">
            <span class="modal-title" id="modalRolTitulo">Nuevo Rol</span>
            <button class="modal-close" onclick="Modal.close('modalRol')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="rolId">
            <div class="form-row">
                <div class="form-group" style="flex:2">
                    <label class="form-label">Nombre del rol *</label>
                    <input type="text" class="form-control" id="rolNombre" placeholder="Ej: Supervisor">
                </div>
                <div class="form-group" style="flex:3">
                    <label class="form-label">DescripciÃ³n</label>
                    <input type="text" class="form-control" id="rolDesc" placeholder="Breve descripciÃ³n">
                </div>
            </div>

            <div style="margin-bottom:.5rem">
                <label class="form-label" style="margin-bottom:.5rem">
                    <i class="fa fa-lock-open" style="color:var(--primary)"></i> Permisos por mÃ³dulo
                </label>
                <label style="display:flex;align-items:center;gap:.5rem;font-size:.85rem;margin-bottom:.75rem;cursor:pointer">
                    <input type="checkbox" id="rolPermAll" onchange="toggleAllPerms(this.checked)">
                    <strong>Acceso total a todos los mÃ³dulos (Administrador)</strong>
                </label>
            </div>

            <div id="permMatrix" style="border:1px solid var(--border);border-radius:8px;overflow:hidden">
                <table style="width:100%;border-collapse:collapse">
                    <thead>
                        <tr style="background:var(--bg-secondary)">
                            <th style="padding:.5rem .75rem;text-align:left;font-size:.8rem;color:var(--text-secondary);font-weight:600">MÃ³dulo</th>
                            <th style="padding:.5rem .75rem;text-align:center;font-size:.8rem;color:var(--text-secondary);font-weight:600">Sin acceso</th>
                            <th style="padding:.5rem .75rem;text-align:center;font-size:.8rem;color:var(--text-secondary);font-weight:600">Lectura</th>
                            <th style="padding:.5rem .75rem;text-align:center;font-size:.8rem;color:var(--text-secondary);font-weight:600">Acceso completo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modulos as $key => $label): ?>
                        <tr style="border-top:1px solid var(--border)">
                            <td style="padding:.5rem .75rem;font-size:.875rem"><?= $label ?></td>
                            <td style="text-align:center">
                                <input type="radio" name="perm_<?= $key ?>" value="ninguno" checked>
                            </td>
                            <td style="text-align:center">
                                <input type="radio" name="perm_<?= $key ?>" value="lectura">
                            </td>
                            <td style="text-align:center">
                                <input type="radio" name="perm_<?= $key ?>" value="completo">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('modalRol')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarRol()">
                <i class="fa fa-save"></i> Guardar Rol
            </button>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: ÃREA
     ============================================================ -->
<div class="modal-overlay" id="modalArea">
    <div class="modal" style="max-width:440px">
        <div class="modal-header">
            <span class="modal-title" id="modalAreaTitulo">Nueva Ãrea</span>
            <button class="modal-close" onclick="Modal.close('modalArea')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="areaId">
            <div class="form-group">
                <label class="form-label">Nombre del Ã¡rea *</label>
                <input type="text" class="form-control" id="areaNombre" placeholder="Ej: Soldadura">
            </div>
            <div class="form-group">
                <label class="form-label">DescripciÃ³n</label>
                <textarea class="form-control" id="areaDesc" rows="3" placeholder="DescripciÃ³n opcional"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('modalArea')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarArea()">
                <i class="fa fa-save"></i> Guardar
            </button>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
const API = '../seguridad/api.php';
const API_CFG = 'api.php';
const MODULOS = <?= json_encode($modulos) ?>;

let todosUsuarios = [];

// â”€â”€ Init â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
document.addEventListener('DOMContentLoaded', () => {
    cargarEmpresa();
    cargarUsuarios();
    cargarRoles();
    cargarAreas();

    const ubigeoInput = document.getElementById('empUbigeo');
    if (ubigeoInput) {
        ubigeoInput.addEventListener('input', () => {
            if (ubigeoInput.value.trim().length === 6) {
                autocompletarUbigeoEmpresa();
            } else {
                limpiarUbigeoEmpresa();
            }
        });
        ubigeoInput.addEventListener('change', autocompletarUbigeoEmpresa);
        ubigeoInput.addEventListener('blur', autocompletarUbigeoEmpresa);
    }

    const sunatServer = document.getElementById('empSunatServer');
    if (sunatServer) {
        sunatServer.addEventListener('change', () => actualizarBadgeSunat(sunatServer.value));
    }
});

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// EMPRESA
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
async function cargarEmpresa() {
    try {
        const d = await apiGet(API_CFG + '?action=empresa_obtener');
        if (!d.ok || !d.empresa) return;
        const e = d.empresa;
        document.getElementById('empRazonSocial').value = e.razon_social || '';
        document.getElementById('empNombreComercial').value = e.nombre_comercial || '';
        document.getElementById('empRuc').value = e.ruc || '';
        document.getElementById('empEmail').value = e.email || '';
        document.getElementById('empTelefono').value = e.telefono || '';
        document.getElementById('empWhatsappInstance').value = e.whatsapp_instance || '';
        document.getElementById('empDireccion').value = e.direccion_fiscal || '';
        document.getElementById('empUbigeo').value = e.ubigeo || '';
        document.getElementById('empPais').value = e.codigo_pais || 'PE';
        document.getElementById('empDepartamento').value = e.departamento || '';
        document.getElementById('empProvincia').value = e.provincia || '';
        document.getElementById('empDistrito').value = e.distrito || '';
        document.getElementById('empSunatServer').value = e.sunat_server || 'beta';
        actualizarBadgeSunat(e.sunat_server || 'beta');
        document.getElementById('empSunatUsername').value = e.sunat_username || '';
        document.getElementById('empSunatPassword').value = e.sunat_password || '';
        document.getElementById('empGreClientId').value = e.gre_client_id || '';
        document.getElementById('empGreClientSecret').value = e.gre_client_secret || '';
        document.getElementById('empCertificadoPassword').value = e.certificate_password || '';
        document.getElementById('empCertificadoVence').value = e.certificate_expires_at || '';
        document.getElementById('empTaxEnabled').checked = !!Number(e.tax_enabled ?? 1);
        document.getElementById('empCertificadoActual').textContent = e.certificate_path
            ? `Actual: ${e.certificate_path}`
            : 'Sin certificado cargado.';
        document.getElementById('empLogoActual').textContent = e.logo_path
            ? `Actual: ${e.logo_path}`
            : 'Sin logo cargado.';
        actualizarAyudaUbigeoEmpresa(e.ubigeo || '', e.departamento || '', e.provincia || '', e.distrito || '');
    } catch (e) {
        Toast.error('Error al cargar la empresa emisora.');
    }
}

function actualizarBadgeSunat(server) {
    const badge = document.getElementById('empSunatBadge');
    if (!badge) return;

    if (server === 'prod') {
        badge.textContent = 'Produccion';
        badge.className = 'badge badge-normal';
        return;
    }

    badge.textContent = 'Pruebas';
    badge.className = 'badge badge-completado';
}

function actualizarAyudaUbigeoEmpresa(ubigeo, departamento, provincia, distrito) {
    const help = document.getElementById('empUbigeoHelp');
    if (!help) return;

    if (ubigeo && departamento && provincia && distrito) {
        help.textContent = `${ubigeo} corresponde a ${distrito}, ${provincia}, ${departamento}.`;
        return;
    }

    help.textContent = 'Ejemplo: 150101 corresponde a Lima.';
}

function limpiarUbigeoEmpresa() {
    document.getElementById('empDepartamento').value = '';
    document.getElementById('empProvincia').value = '';
    document.getElementById('empDistrito').value = '';
    actualizarAyudaUbigeoEmpresa(document.getElementById('empUbigeo').value.trim(), '', '', '');
}

async function autocompletarUbigeoEmpresa() {
    const ubigeo = document.getElementById('empUbigeo').value.trim();
    if (!/^\d{6}$/.test(ubigeo)) {
        limpiarUbigeoEmpresa();
        return;
    }

    try {
        const data = await apiGet(API_CFG + '?action=ubigeo_resolver&codigo=' + encodeURIComponent(ubigeo));
        if (!data.ok) {
            limpiarUbigeoEmpresa();
            return;
        }

        document.getElementById('empDepartamento').value = data.departamento || '';
        document.getElementById('empProvincia').value = data.provincia || '';
        document.getElementById('empDistrito').value = data.distrito || '';
        actualizarAyudaUbigeoEmpresa(ubigeo, data.departamento || '', data.provincia || '', data.distrito || '');
    } catch (error) {
        limpiarUbigeoEmpresa();
    }
}

async function guardarEmpresa() {
    const razonSocial = document.getElementById('empRazonSocial').value.trim();
    if (!razonSocial) return Toast.error('La razon social es obligatoria.');

    const fd = new FormData();
    fd.append('action', 'empresa_guardar');
    fd.append('razon_social', razonSocial);
    fd.append('nombre_comercial', document.getElementById('empNombreComercial').value.trim());
    fd.append('ruc', document.getElementById('empRuc').value.trim());
    fd.append('email', document.getElementById('empEmail').value.trim());
    fd.append('telefono', document.getElementById('empTelefono').value.trim());
    fd.append('whatsapp_instance', document.getElementById('empWhatsappInstance').value.trim());
    fd.append('direccion_fiscal', document.getElementById('empDireccion').value.trim());
    fd.append('ubigeo', document.getElementById('empUbigeo').value.trim());
    fd.append('codigo_pais', document.getElementById('empPais').value.trim());
    fd.append('departamento', document.getElementById('empDepartamento').value.trim());
    fd.append('provincia', document.getElementById('empProvincia').value.trim());
    fd.append('distrito', document.getElementById('empDistrito').value.trim());
    fd.append('sunat_server', document.getElementById('empSunatServer').value);
    fd.append('sunat_username', document.getElementById('empSunatUsername').value.trim());
    fd.append('sunat_password', document.getElementById('empSunatPassword').value.trim());
    fd.append('gre_client_id', document.getElementById('empGreClientId').value.trim());
    fd.append('gre_client_secret', document.getElementById('empGreClientSecret').value.trim());
    fd.append('certificate_password', document.getElementById('empCertificadoPassword').value.trim());
    fd.append('certificate_expires_at', document.getElementById('empCertificadoVence').value);
    fd.append('tax_enabled', document.getElementById('empTaxEnabled').checked ? '1' : '0');

    const logo = document.getElementById('empLogo').files[0];
    const certificado = document.getElementById('empCertificado').files[0];
    if (logo) fd.append('logo', logo);
    if (certificado) fd.append('certificado', certificado);

    try {
        const resp = await fetch(API_CFG, { method: 'POST', body: fd });
        const r = await resp.json();
        if (!r.ok) throw new Error(r.error || 'No se pudo guardar la empresa.');
        Toast.success('Empresa emisora actualizada.');
        cargarEmpresa();
    } catch (e) {
        Toast.error(e.message || 'Error al guardar la empresa.');
    }
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// USUARIOS
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
async function cargarUsuarios() {
    try {
        const d = await apiGet(API + '?action=usuarios_listar');
        todosUsuarios = d.usuarios || [];
        renderUsuarios(todosUsuarios);
    } catch(e) {
        document.getElementById('usuariosBody').innerHTML =
            '<tr><td colspan="7" class="text-center" style="color:var(--danger)">Error al cargar usuarios</td></tr>';
    }
}

function filtrarUsuarios() {
    const q = document.getElementById('buscarUsuario').value.toLowerCase();
    renderUsuarios(todosUsuarios.filter(u =>
        u.nombre.toLowerCase().includes(q) ||
        u.email.toLowerCase().includes(q) ||
        (u.rol_nombre||'').toLowerCase().includes(q)
    ));
}

function renderUsuarios(lista) {
    const tbody = document.getElementById('usuariosBody');
    if (!lista.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Sin usuarios</td></tr>';
        return;
    }
    tbody.innerHTML = lista.map(u => `
        <tr>
            <td><strong>${u.nombre}</strong></td>
            <td style="color:var(--text-secondary)">${u.email}</td>
            <td>${u.rol_nombre ? `<span class="badge badge-normal">${u.rol_nombre}</span>` : 'â€”'}</td>
            <td>${u.area_nombre || 'â€”'}</td>
            <td style="font-size:.8rem;color:var(--text-secondary)">${u.ultimo_acceso ? formatDate(u.ultimo_acceso) : 'Nunca'}</td>
            <td>
                <span class="badge ${u.activo ? 'badge-completado' : 'badge-cancelado'}">
                    ${u.activo ? 'Activo' : 'Inactivo'}
                </span>
            </td>
            <td>
                <div style="display:flex;gap:.5rem">
                    <button class="btn btn-secondary btn-sm" onclick="editarUsuario(${u.id})" title="Editar">
                        <i class="fa fa-pen"></i>
                    </button>
                    <button class="btn btn-sm ${u.activo ? 'btn-warning' : 'btn-secondary'}"
                            onclick="toggleUsuario(${u.id},'${u.nombre}')" title="${u.activo ? 'Desactivar' : 'Activar'}">
                        <i class="fa fa-${u.activo ? 'ban' : 'check'}"></i>
                    </button>
                </div>
            </td>
        </tr>`).join('');
}

function abrirModalUsuario() {
    document.getElementById('usuarioId').value      = '';
    document.getElementById('usuarioNombre').value  = '';
    document.getElementById('usuarioEmail').value   = '';
    document.getElementById('usuarioPassword').value= '';
    document.getElementById('usuarioActivo').value  = '1';
    document.getElementById('usuarioRol').value     = '';
    document.getElementById('usuarioArea').value    = '';
    document.getElementById('modalUsuarioTitulo').textContent = 'Nuevo Usuario';
    document.getElementById('passHint').textContent = '(requerida)';
    Modal.open('modalUsuario');
}

async function editarUsuario(id) {
    try {
        const d = await apiGet(API + '?action=usuario_obtener&id=' + id);
        const u = d.usuario;
        document.getElementById('usuarioId').value      = u.id;
        document.getElementById('usuarioNombre').value  = u.nombre;
        document.getElementById('usuarioEmail').value   = u.email;
        document.getElementById('usuarioPassword').value= '';
        document.getElementById('usuarioActivo').value  = u.activo ? '1' : '0';
        document.getElementById('usuarioRol').value     = u.rol_id  || '';
        document.getElementById('usuarioArea').value    = u.area_id || '';
        document.getElementById('modalUsuarioTitulo').textContent = 'Editar Usuario';
        document.getElementById('passHint').textContent = '(dejar vacÃ­o para no cambiar)';
        Modal.open('modalUsuario');
    } catch(e) { Toast.error('Error al cargar usuario'); }
}

async function guardarUsuario() {
    const id     = document.getElementById('usuarioId').value;
    const nombre = document.getElementById('usuarioNombre').value.trim();
    const email  = document.getElementById('usuarioEmail').value.trim();
    const pass   = document.getElementById('usuarioPassword').value;

    if (!nombre || !email) return Toast.error('Nombre y email son obligatorios.');
    if (!id && !pass) return Toast.error('La contraseÃ±a es obligatoria para nuevos usuarios.');

    const payload = {
        action:   id ? 'usuario_actualizar' : 'usuario_crear',
        id,
        nombre,
        email,
        password: pass,
        activo:   document.getElementById('usuarioActivo').value === '1',
        rol_id:   document.getElementById('usuarioRol').value  || null,
        area_id:  document.getElementById('usuarioArea').value || null,
    };

    try {
        await apiPost(API, payload);
        Toast.success(id ? 'Usuario actualizado.' : 'Usuario creado.');
        Modal.close('modalUsuario');
        cargarUsuarios();
    } catch(e) { Toast.error(e.message || 'Error al guardar'); }
}

async function toggleUsuario(id, nombre) {
    if (!confirm(`Â¿${nombre}? Â¿Confirmar cambio de estado?`)) return;
    try {
        await apiPost(API, { action: 'usuario_toggle', id });
        cargarUsuarios();
    } catch(e) { Toast.error('Error al cambiar estado'); }
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// ROLES
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
async function cargarRoles() {
    try {
        const d = await apiGet(API + '?action=roles_listar');
        renderRoles(d.roles || []);
    } catch(e) {
        document.getElementById('rolesGrid').innerHTML =
            '<div class="card" style="padding:1rem;color:var(--danger)">Error al cargar roles</div>';
    }
}

function renderRoles(roles) {
    const grid = document.getElementById('rolesGrid');
    if (!roles.length) {
        grid.innerHTML = '<div class="card" style="padding:2rem;text-align:center;color:var(--text-secondary)">Sin roles</div>';
        return;
    }
    grid.innerHTML = roles.map(r => {
        const permisos = typeof r.permisos === 'string' ? JSON.parse(r.permisos || '{}') : (r.permisos || {});
        const esAdmin  = permisos.all === true;
        const modCount = esAdmin ? Object.keys(MODULOS).length : Object.values(permisos).filter(v => v).length;
        return `
        <div class="card">
            <div style="padding:1rem 1.25rem;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
                <div>
                    <div style="font-weight:600;font-size:.95rem">${r.nombre}</div>
                    <div style="font-size:.78rem;color:var(--text-secondary)">${r.descripcion || 'â€”'}</div>
                </div>
                <span class="badge badge-normal">${r.total_usuarios} usuario${r.total_usuarios!=1?'s':''}</span>
            </div>
            <div style="padding:.75rem 1.25rem">
                <div style="font-size:.78rem;color:var(--text-secondary);margin-bottom:.5rem">
                    ${esAdmin
                        ? '<span style="color:var(--primary);font-weight:600"><i class="fa fa-crown"></i> Acceso total</span>'
                        : `${modCount} mÃ³dulo${modCount!=1?'s':''} con acceso`}
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:.25rem">
                    ${esAdmin
                        ? Object.values(MODULOS).map(m => `<span class="badge badge-completado" style="font-size:.7rem">${m}</span>`).join('')
                        : Object.entries(MODULOS).map(([k,v]) => {
                            const p = permisos[k];
                            if (!p) return '';
                            const cls = p === 'lectura' ? 'badge-normal' : 'badge-completado';
                            const icon = p === 'lectura' ? '<i class="fa fa-eye" title="Lectura"></i> ' : '';
                            return `<span class="badge ${cls}" style="font-size:.7rem">${icon}${v}</span>`;
                          }).join('')}
                </div>
            </div>
            <div style="padding:.5rem 1.25rem 1rem;display:flex;gap:.5rem">
                <button class="btn btn-secondary btn-sm" onclick="editarRol(${r.id})">
                    <i class="fa fa-pen"></i> Editar
                </button>
                ${r.total_usuarios == 0 ? `
                <button class="btn btn-sm" style="background:var(--danger);color:#fff" onclick="eliminarRol(${r.id},'${r.nombre}')">
                    <i class="fa fa-trash"></i>
                </button>` : ''}
            </div>
        </div>`;
    }).join('');
}

function abrirModalRol() {
    document.getElementById('rolId').value    = '';
    document.getElementById('rolNombre').value= '';
    document.getElementById('rolDesc').value  = '';
    document.getElementById('rolPermAll').checked = false;
    document.getElementById('permMatrix').style.opacity = '1';
    document.getElementById('permMatrix').style.pointerEvents = '';
    // Reset radios
    Object.keys(MODULOS).forEach(k => {
        const r = document.querySelector(`input[name="perm_${k}"][value="ninguno"]`);
        if (r) r.checked = true;
    });
    document.getElementById('modalRolTitulo').textContent = 'Nuevo Rol';
    Modal.open('modalRol');
}

async function editarRol(id) {
    try {
        const d = await apiGet(API + '?action=rol_obtener&id=' + id);
        const r = d.rol;
        const permisos = r.permisos || {};
        const esAdmin  = permisos.all === true;

        document.getElementById('rolId').value    = r.id;
        document.getElementById('rolNombre').value= r.nombre;
        document.getElementById('rolDesc').value  = r.descripcion || '';
        document.getElementById('rolPermAll').checked = esAdmin;
        document.getElementById('permMatrix').style.opacity = esAdmin ? '.4' : '1';
        document.getElementById('permMatrix').style.pointerEvents = esAdmin ? 'none' : '';

        Object.keys(MODULOS).forEach(k => {
            const val = esAdmin ? 'completo' : (permisos[k] === true ? 'completo' : (permisos[k] === 'lectura' ? 'lectura' : 'ninguno'));
            const radio = document.querySelector(`input[name="perm_${k}"][value="${val}"]`);
            if (radio) radio.checked = true;
        });

        document.getElementById('modalRolTitulo').textContent = 'Editar Rol: ' + r.nombre;
        Modal.open('modalRol');
    } catch(e) { Toast.error('Error al cargar rol'); }
}

function toggleAllPerms(checked) {
    document.getElementById('permMatrix').style.opacity = checked ? '.4' : '1';
    document.getElementById('permMatrix').style.pointerEvents = checked ? 'none' : '';
    if (checked) {
        Object.keys(MODULOS).forEach(k => {
            const r = document.querySelector(`input[name="perm_${k}"][value="completo"]`);
            if (r) r.checked = true;
        });
    }
}

async function guardarRol() {
    const id     = document.getElementById('rolId').value;
    const nombre = document.getElementById('rolNombre').value.trim();
    if (!nombre) return Toast.error('El nombre es obligatorio.');

    const esAdmin = document.getElementById('rolPermAll').checked;
    let permisos  = {};

    if (esAdmin) {
        permisos = { all: true };
    } else {
        Object.keys(MODULOS).forEach(k => {
            const val = document.querySelector(`input[name="perm_${k}"]:checked`)?.value;
            if (val === 'completo') permisos[k] = true;
            else if (val === 'lectura') permisos[k] = 'lectura';
        });
    }

    const payload = {
        action:      'rol_guardar',
        id,
        nombre,
        descripcion: document.getElementById('rolDesc').value.trim(),
        permisos,
    };

    try {
        await apiPost(API, payload);
        Toast.success(id ? 'Rol actualizado.' : 'Rol creado.');
        Modal.close('modalRol');
        cargarRoles();
    } catch(e) { Toast.error(e.message || 'Error al guardar'); }
}

async function eliminarRol(id, nombre) {
    if (!confirm(`Â¿Eliminar el rol "${nombre}"? Esta acciÃ³n no se puede deshacer.`)) return;
    try {
        await apiPost(API, { action: 'rol_eliminar', id });
        Toast.success('Rol eliminado.');
        cargarRoles();
    } catch(e) { Toast.error(e.message || 'Error al eliminar'); }
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// ÃREAS
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
async function cargarAreas() {
    try {
        const d = await apiGet(API + '?action=areas_listar');
        renderAreas(d.areas || []);
    } catch(e) {
        document.getElementById('areasBody').innerHTML =
            '<tr><td colspan="5" class="text-center" style="color:var(--danger)">Error al cargar Ã¡reas</td></tr>';
    }
}

function renderAreas(areas) {
    const tbody = document.getElementById('areasBody');
    if (!areas.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Sin Ã¡reas registradas</td></tr>';
        return;
    }
    tbody.innerHTML = areas.map(a => `
        <tr>
            <td><strong>${a.nombre}</strong></td>
            <td style="color:var(--text-secondary)">${a.descripcion || 'â€”'}</td>
            <td>${a.total_usuarios} usuario${a.total_usuarios!=1?'s':''}</td>
            <td>
                <span class="badge ${a.activo ? 'badge-completado' : 'badge-cancelado'}">
                    ${a.activo ? 'Activa' : 'Inactiva'}
                </span>
            </td>
            <td>
                <div style="display:flex;gap:.5rem">
                    <button class="btn btn-secondary btn-sm" onclick="editarArea(${a.id},'${a.nombre.replace(/'/g,"\\'")}','${(a.descripcion||'').replace(/'/g,"\\'")}')">
                        <i class="fa fa-pen"></i>
                    </button>
                    <button class="btn btn-sm ${a.activo ? 'btn-warning' : 'btn-secondary'}"
                            onclick="toggleArea(${a.id})" title="${a.activo ? 'Desactivar' : 'Activar'}">
                        <i class="fa fa-${a.activo ? 'ban' : 'check'}"></i>
                    </button>
                    ${a.total_usuarios == 0 ? `
                    <button class="btn btn-sm" style="background:var(--danger);color:#fff"
                            onclick="eliminarArea(${a.id},'${a.nombre.replace(/'/g,"\\'")}')">
                        <i class="fa fa-trash"></i>
                    </button>` : ''}
                </div>
            </td>
        </tr>`).join('');
}

function abrirModalArea() {
    document.getElementById('areaId').value    = '';
    document.getElementById('areaNombre').value= '';
    document.getElementById('areaDesc').value  = '';
    document.getElementById('modalAreaTitulo').textContent = 'Nueva Ãrea';
    Modal.open('modalArea');
}

function editarArea(id, nombre, desc) {
    document.getElementById('areaId').value    = id;
    document.getElementById('areaNombre').value= nombre;
    document.getElementById('areaDesc').value  = desc;
    document.getElementById('modalAreaTitulo').textContent = 'Editar Ãrea';
    Modal.open('modalArea');
}

async function guardarArea() {
    const id     = document.getElementById('areaId').value;
    const nombre = document.getElementById('areaNombre').value.trim();
    if (!nombre) return Toast.error('El nombre es obligatorio.');

    try {
        await apiPost(API, {
            action:      'area_guardar',
            id,
            nombre,
            descripcion: document.getElementById('areaDesc').value.trim(),
        });
        Toast.success(id ? 'Ãrea actualizada.' : 'Ãrea creada.');
        Modal.close('modalArea');
        cargarAreas();
    } catch(e) { Toast.error(e.message || 'Error al guardar'); }
}

async function toggleArea(id) {
    try {
        await apiPost(API, { action: 'area_toggle', id });
        cargarAreas();
    } catch(e) { Toast.error('Error al cambiar estado'); }
}

async function eliminarArea(id, nombre) {
    if (!confirm(`Â¿Eliminar el Ã¡rea "${nombre}"?`)) return;
    try {
        await apiPost(API, { action: 'area_eliminar', id });
        Toast.success('Ãrea eliminada.');
        cargarAreas();
    } catch(e) { Toast.error(e.message || 'Error al eliminar'); }
}
</script>
<?php
$extra_js = ob_get_clean();
require_once __DIR__ . '/../../includes/footer.php';




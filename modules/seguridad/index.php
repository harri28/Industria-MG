<?php
$page_title      = 'Seguridad y Auditoría';
$page_breadcrumb = '<span>Sistema</span> <span>›</span> Seguridad';
require_once __DIR__ . '/../../includes/header.php';

$db = getDB();
$roles = $db->query("SELECT id, nombre FROM roles WHERE activo ORDER BY nombre")->fetchAll();
$areas = $db->query("SELECT id, nombre FROM areas WHERE activo ORDER BY nombre")->fetchAll();

$modulos = [
    'produccion'       => 'Producción',
    'compras'          => 'Compras',
    'inventarios'      => 'Inventarios',
    'clientes'         => 'Clientes / CRM',
    'comercializacion' => 'Comercialización',
    'reportes'         => 'Reportes',
    'seguridad'        => 'Seguridad',
    'capacitacion'     => 'Capacitación',
];
?>

<!-- TABS -->
<div class="tabs">
    <div class="tab active" data-group="seg" data-target="tabUsuarios">
        <i class="fa fa-users"></i> Usuarios
    </div>
    <div class="tab" data-group="seg" data-target="tabRoles">
        <i class="fa fa-id-badge"></i> Roles
    </div>
    <div class="tab" data-group="seg" data-target="tabAreas">
        <i class="fa fa-sitemap"></i> Áreas
    </div>
    <div class="tab" data-group="seg" data-target="tabAuditoria">
        <i class="fa fa-clock-rotate-left"></i> Auditoría
    </div>
    <div class="tab" data-group="seg" data-target="tabBackups">
        <i class="fa fa-database"></i> Backups
    </div>
</div>

<!-- ============================================================
     TAB: USUARIOS
     ============================================================ -->
<div class="tab-content active" data-group="seg" id="tabUsuarios">
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fa fa-users"></i> Gestión de Usuarios</span>
            <div class="card-actions">
                <div class="search-box">
                    <span class="icon"><i class="fa fa-search"></i></span>
                    <input type="text" class="form-control" id="buscarUsuario" placeholder="Buscar usuario…" oninput="filtrarUsuarios()">
                </div>
                <button class="btn btn-primary btn-sm" onclick="abrirModalUsuario()">
                    <i class="fa fa-plus"></i> Nuevo Usuario
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table>
                <thead><tr>
                    <th>Nombre</th><th>Email</th><th>Rol</th><th>Área</th>
                    <th>Último Acceso</th><th>Estado</th><th>Acciones</th>
                </tr></thead>
                <tbody id="usuariosBody">
                    <tr><td colspan="7" class="text-center"><div class="loading"><div class="spinner"></div></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================
     TAB: ROLES
     ============================================================ -->
<div class="tab-content" data-group="seg" id="tabRoles">
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fa fa-id-badge"></i> Roles y Permisos</span>
            <div class="card-actions">
                <button class="btn btn-primary btn-sm" onclick="abrirModalRol()">
                    <i class="fa fa-plus"></i> Nuevo Rol
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table>
                <thead><tr>
                    <th>Rol</th><th>Descripción</th><th>Usuarios</th><th>Permisos</th><th>Acciones</th>
                </tr></thead>
                <tbody id="rolesBody">
                    <tr><td colspan="5" class="text-center"><div class="loading"><div class="spinner"></div></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================
     TAB: ÁREAS
     ============================================================ -->
<div class="tab-content" data-group="seg" id="tabAreas">
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fa fa-sitemap"></i> Áreas de Trabajo</span>
            <div class="card-actions">
                <button class="btn btn-primary btn-sm" onclick="abrirModalArea()">
                    <i class="fa fa-plus"></i> Nueva Área
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table>
                <thead><tr>
                    <th>Área</th><th>Descripción</th><th>Usuarios</th><th>Estado</th><th>Acciones</th>
                </tr></thead>
                <tbody id="areasBody">
                    <tr><td colspan="5" class="text-center"><div class="loading"><div class="spinner"></div></div></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================
     TAB: AUDITORÍA
     ============================================================ -->
<div class="tab-content" data-group="seg" id="tabAuditoria">
    <div class="card">
        <div class="card-header">
            <span class="card-title"><i class="fa fa-clock-rotate-left"></i> Registro de Auditoría</span>
            <div class="card-actions" style="gap:8px;flex-wrap:wrap">
                <select class="form-control" id="filtroModulo" style="width:150px" onchange="cargarAuditoria()">
                    <option value="">Todos los módulos</option>
                    <?php foreach ($modulos as $k => $v): ?>
                    <option value="<?= $k ?>"><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="form-control" id="filtroAccion" style="width:140px" onchange="cargarAuditoria()">
                    <option value="">Todas las acciones</option>
                    <option value="crear">Crear</option>
                    <option value="editar">Editar</option>
                    <option value="eliminar">Eliminar</option>
                    <option value="login">Login</option>
                </select>
                <input type="date" class="form-control" id="filtroDesde" style="width:140px" onchange="cargarAuditoria()">
                <input type="date" class="form-control" id="filtroHasta" style="width:140px" onchange="cargarAuditoria()">
                <button class="btn btn-outline btn-sm" onclick="cargarAuditoria()"><i class="fa fa-search"></i> Buscar</button>
            </div>
        </div>
        <div class="table-responsive">
            <table>
                <thead><tr>
                    <th>Fecha</th><th>Usuario</th><th>Acción</th>
                    <th>Módulo</th><th>Tabla</th><th>ID</th><th>IP</th><th></th>
                </tr></thead>
                <tbody id="auditoriaBody">
                    <tr><td colspan="8" class="text-center text-muted">Use los filtros y presione Buscar.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================
     TAB: BACKUPS
     ============================================================ -->
<div class="tab-content" data-group="seg" id="tabBackups">
    <div style="display:grid;grid-template-columns:300px 1fr;gap:14px;align-items:start">
        <div class="card">
            <div class="card-header"><span class="card-title"><i class="fa fa-database"></i> Generar Backup</span></div>
            <div class="card-body" style="text-align:center;padding:30px 20px">
                <div style="margin-bottom:12px"><img src="../../assets/iconos/backup.png" alt="Backup" style="width:80px;height:80px;object-fit:contain"></div>
                <p class="text-secondary text-sm mb-2">Respaldo completo de <strong>industria_mg</strong> en formato SQL.</p>
                <button class="btn btn-primary" id="btnBackup" onclick="generarBackup()" style="margin-top:12px;width:100%">
                    <i class="fa fa-download"></i> Generar Backup Ahora
                </button>
                <div id="backupStatus" style="margin-top:12px"></div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <span class="card-title">Historial de Backups</span>
                <div class="card-actions">
                    <button class="btn btn-outline btn-sm" onclick="cargarBackups()"><i class="fa fa-rotate"></i> Actualizar</button>
                </div>
            </div>
            <div class="table-responsive">
                <table>
                    <thead><tr>
                        <th>Archivo</th><th>Tamaño</th><th>Fecha</th><th>Estado</th><th></th>
                    </tr></thead>
                    <tbody id="backupsBody">
                        <tr><td colspan="5" class="text-center"><div class="loading"><div class="spinner"></div></div></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: USUARIO
     ============================================================ -->
<div class="modal-overlay" id="modalUsuario">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title" id="modalUsuarioTitulo">Nuevo Usuario</span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="uId">
            <div class="form-row cols-2">
                <div class="form-group" style="grid-column:span 2">
                    <label class="form-label">Nombre completo *</label>
                    <input type="text" class="form-control" id="uNombre">
                </div>
                <div class="form-group" style="grid-column:span 2">
                    <label class="form-label">Email *</label>
                    <input type="email" class="form-control" id="uEmail">
                </div>
                <div class="form-group" style="grid-column:span 2">
                    <label class="form-label" id="uPasswordLabel">Contraseña *</label>
                    <input type="password" class="form-control" id="uPassword" placeholder="Mínimo 6 caracteres">
                    <span class="text-xs text-muted" id="uPasswordHint"></span>
                </div>
                <div class="form-group">
                    <label class="form-label">Rol</label>
                    <select class="form-control" id="uRol">
                        <option value="">— Sin rol —</option>
                        <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Área</label>
                    <select class="form-control" id="uArea">
                        <option value="">— Sin área —</option>
                        <?php foreach ($areas as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="grid-column:span 2;display:flex;align-items:center;gap:8px">
                    <input type="checkbox" id="uActivo" checked style="width:16px;height:16px;accent-color:var(--primary)">
                    <label for="uActivo" class="form-label" style="margin:0;cursor:pointer">Usuario activo</label>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalUsuario')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarUsuario()"><i class="fa fa-save"></i> Guardar</button>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: ROL
     ============================================================ -->
<div class="modal-overlay" id="modalRol">
    <div class="modal modal-lg">
        <div class="modal-header">
            <span class="modal-title" id="modalRolTitulo">Nuevo Rol</span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="rId">
            <div class="form-row cols-2">
                <div class="form-group">
                    <label class="form-label">Nombre del Rol *</label>
                    <input type="text" class="form-control" id="rNombre">
                </div>
                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <input type="text" class="form-control" id="rDescripcion">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Permisos por módulo</label>
                <div style="border:1.5px solid var(--border);border-radius:var(--radius-sm);overflow:hidden">
                    <table style="margin:0">
                        <thead><tr>
                            <th style="width:200px">Módulo</th>
                            <th class="text-center" style="color:var(--danger)">Sin acceso</th>
                            <th class="text-center" style="color:var(--warning)">Solo lectura</th>
                            <th class="text-center" style="color:var(--success)">Acceso completo</th>
                        </tr></thead>
                        <tbody>
                            <?php foreach ($modulos as $key => $label): ?>
                            <tr>
                                <td class="font-semibold text-sm"><?= $label ?></td>
                                <td class="text-center">
                                    <input type="radio" name="perm_<?= $key ?>" value="ninguno" checked
                                           style="accent-color:var(--danger);width:16px;height:16px">
                                </td>
                                <td class="text-center">
                                    <input type="radio" name="perm_<?= $key ?>" value="lectura"
                                           style="accent-color:var(--warning);width:16px;height:16px">
                                </td>
                                <td class="text-center">
                                    <input type="radio" name="perm_<?= $key ?>" value="completo"
                                           style="accent-color:var(--success);width:16px;height:16px">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <tr style="background:var(--primary-light)">
                                <td class="font-bold text-sm" style="color:var(--primary)">Administrador total</td>
                                <td></td><td></td>
                                <td class="text-center">
                                    <input type="checkbox" id="rAdminTotal"
                                           style="accent-color:var(--primary);width:16px;height:16px"
                                           onchange="toggleAdminTotal(this.checked)">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalRol')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarRol()"><i class="fa fa-save"></i> Guardar Rol</button>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: ÁREA
     ============================================================ -->
<div class="modal-overlay" id="modalArea">
    <div class="modal" style="max-width:440px">
        <div class="modal-header">
            <span class="modal-title" id="modalAreaTitulo">Nueva Área</span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="aId">
            <div class="form-group">
                <label class="form-label">Nombre del Área *</label>
                <input type="text" class="form-control" id="aNombre" placeholder="Ej: Soldadura, Pintura…">
            </div>
            <div class="form-group">
                <label class="form-label">Descripción</label>
                <textarea class="form-control" id="aDescripcion" rows="2"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalArea')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarArea()"><i class="fa fa-save"></i> Guardar</button>
        </div>
    </div>
</div>

<!-- MODAL: Detalle auditoría -->
<div class="modal-overlay" id="modalAuditoria">
    <div class="modal" style="max-width:520px">
        <div class="modal-header">
            <span class="modal-title">Detalle del Registro</span>
            <button class="modal-close"><i class="fa fa-times"></i></button>
        </div>
        <div class="modal-body" id="modalAuditoriaBody"></div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="Modal.close('modalAuditoria')">Cerrar</button>
        </div>
    </div>
</div>

<script>
const BASE    = 'api.php';
const MODULOS = <?= json_encode(array_keys($modulos)) ?>;

// ── USUARIOS ─────────────────────────────────────────────
let usuariosData = [];

async function cargarUsuarios() {
    try {
        const data = await apiGet(`${BASE}?action=usuarios_listar`);
        usuariosData = data.usuarios || [];
        renderUsuarios(usuariosData);
    } catch(e) { Toast.error('Error al cargar usuarios.'); }
}

function filtrarUsuarios() {
    const q = document.getElementById('buscarUsuario').value.toLowerCase();
    renderUsuarios(usuariosData.filter(u =>
        u.nombre.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)
    ));
}

function renderUsuarios(lista) {
    const tbody = document.getElementById('usuariosBody');
    if (!lista.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">Sin usuarios registrados.</td></tr>';
        return;
    }
    tbody.innerHTML = lista.map(u => `<tr>
        <td>
            <div style="display:flex;align-items:center;gap:10px">
                <div style="width:32px;height:32px;border-radius:50%;background:var(--primary-light);color:var(--primary);
                            display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0">
                    ${esc(u.nombre.charAt(0).toUpperCase())}
                </div>
                <span class="font-semibold">${esc(u.nombre)}</span>
            </div>
        </td>
        <td class="text-secondary text-sm">${esc(u.email)}</td>
        <td>${u.rol_nombre  ? `<span class="badge badge-normal">${esc(u.rol_nombre)}</span>`       : '<span class="text-muted text-xs">—</span>'}</td>
        <td>${u.area_nombre ? `<span class="badge badge-fabricacion">${esc(u.area_nombre)}</span>` : '<span class="text-muted text-xs">—</span>'}</td>
        <td class="text-xs text-muted">${u.ultimo_acceso ? new Date(u.ultimo_acceso).toLocaleString('es-PE') : 'Nunca'}</td>
        <td><span class="badge ${u.activo ? 'badge-activo' : 'badge-cancelado'}">${u.activo ? 'Activo' : 'Inactivo'}</span></td>
        <td>
            <button class="btn btn-outline btn-xs" onclick="editarUsuario(${u.id})" title="Editar"><i class="fa fa-pencil"></i></button>
            <button class="btn btn-outline btn-xs" onclick="toggleUsuario(${u.id})" title="${u.activo ? 'Desactivar' : 'Activar'}">
                <i class="fa fa-${u.activo ? 'ban' : 'circle-check'}"></i>
            </button>
        </td>
    </tr>`).join('');
}

function abrirModalUsuario() {
    document.getElementById('uId').value = '';
    document.getElementById('uNombre').value = '';
    document.getElementById('uEmail').value = '';
    document.getElementById('uPassword').value = '';
    document.getElementById('uRol').value = '';
    document.getElementById('uArea').value = '';
    document.getElementById('uActivo').checked = true;
    document.getElementById('modalUsuarioTitulo').textContent = 'Nuevo Usuario';
    document.getElementById('uPasswordLabel').textContent = 'Contraseña *';
    document.getElementById('uPasswordHint').textContent = '';
    Modal.open('modalUsuario');
}

async function editarUsuario(id) {
    try {
        const data = await apiGet(`${BASE}?action=usuario_obtener&id=${id}`);
        const u = data.usuario;
        document.getElementById('uId').value       = u.id;
        document.getElementById('uNombre').value   = u.nombre;
        document.getElementById('uEmail').value    = u.email;
        document.getElementById('uPassword').value = '';
        document.getElementById('uRol').value      = u.rol_id  || '';
        document.getElementById('uArea').value     = u.area_id || '';
        document.getElementById('uActivo').checked = u.activo;
        document.getElementById('modalUsuarioTitulo').textContent = 'Editar Usuario';
        document.getElementById('uPasswordLabel').textContent = 'Nueva Contraseña';
        document.getElementById('uPasswordHint').textContent  = 'Dejar vacío para no cambiar';
        Modal.open('modalUsuario');
    } catch(e) { Toast.error('Error al cargar usuario.'); }
}

async function guardarUsuario() {
    const id       = document.getElementById('uId').value;
    const nombre   = document.getElementById('uNombre').value.trim();
    const email    = document.getElementById('uEmail').value.trim();
    const password = document.getElementById('uPassword').value;
    if (!nombre || !email) { Toast.warning('Nombre y email son obligatorios.'); return; }
    if (!id && !password)  { Toast.warning('La contraseña es obligatoria.'); return; }
    try {
        const res = await apiPost(BASE, {
            action: id ? 'usuario_actualizar' : 'usuario_crear',
            id, nombre, email, password,
            rol_id:  document.getElementById('uRol').value,
            area_id: document.getElementById('uArea').value,
            activo:  document.getElementById('uActivo').checked,
        });
        if (res.ok) { Toast.success(id ? 'Usuario actualizado.' : 'Usuario creado.'); Modal.close('modalUsuario'); cargarUsuarios(); }
        else Toast.error(res.error || 'Error.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

async function toggleUsuario(id) {
    const res = await apiPost(BASE, { action:'usuario_toggle', id });
    if (res.ok) { Toast.success('Estado actualizado.'); cargarUsuarios(); }
    else Toast.error(res.error || 'Error.');
}

// ── ROLES ─────────────────────────────────────────────────
async function cargarRoles() {
    try {
        const data = await apiGet(`${BASE}?action=roles_listar`);
        const tbody = document.getElementById('rolesBody');
        const roles = data.roles || [];
        if (!roles.length) { tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Sin roles.</td></tr>'; return; }
        const colPermisos = { true:'badge-entrega', lectura:'badge-pruebas' };
        tbody.innerHTML = roles.map(r => {
            const permisos = typeof r.permisos === 'string' ? JSON.parse(r.permisos||'{}') : (r.permisos||{});
            const badges = permisos.all
                ? '<span class="badge badge-entrega">Administrador Total</span>'
                : Object.entries(permisos).map(([k,v]) =>
                    `<span class="badge ${v===true||v==='completo'?'badge-entrega':'badge-pruebas'}" style="margin:1px">${k}</span>`
                  ).join('') || '<span class="text-muted text-xs">Sin permisos</span>';
            return `<tr>
                <td class="font-semibold">${esc(r.nombre)}</td>
                <td class="text-secondary text-sm">${esc(r.descripcion||'—')}</td>
                <td class="text-center"><span class="badge badge-normal">${r.total_usuarios}</span></td>
                <td style="max-width:260px;white-space:normal;line-height:1.8">${badges}</td>
                <td>
                    <button class="btn btn-outline btn-xs" onclick="editarRol(${r.id})"><i class="fa fa-pencil"></i></button>
                    <button class="btn btn-outline btn-xs" onclick="eliminarRol(${r.id})"><i class="fa fa-trash"></i></button>
                </td>
            </tr>`;
        }).join('');
    } catch(e) { Toast.error('Error al cargar roles.'); }
}

function abrirModalRol() {
    document.getElementById('rId').value = '';
    document.getElementById('rNombre').value = '';
    document.getElementById('rDescripcion').value = '';
    document.getElementById('rAdminTotal').checked = false;
    MODULOS.forEach(m => {
        const r = document.querySelector(`input[name="perm_${m}"][value="ninguno"]`);
        if (r) { r.checked = true; r.disabled = false; }
        document.querySelectorAll(`input[name="perm_${m}"]`).forEach(x => x.disabled = false);
    });
    document.getElementById('modalRolTitulo').textContent = 'Nuevo Rol';
    Modal.open('modalRol');
}

async function editarRol(id) {
    try {
        const data = await apiGet(`${BASE}?action=rol_obtener&id=${id}`);
        const r = data.rol;
        const permisos = r.permisos || {};
        document.getElementById('rId').value = r.id;
        document.getElementById('rNombre').value = r.nombre;
        document.getElementById('rDescripcion').value = r.descripcion || '';
        document.getElementById('modalRolTitulo').textContent = 'Editar Rol';
        if (permisos.all) {
            document.getElementById('rAdminTotal').checked = true;
            toggleAdminTotal(true);
        } else {
            document.getElementById('rAdminTotal').checked = false;
            toggleAdminTotal(false);
            MODULOS.forEach(m => {
                const val = permisos[m];
                const v   = (val === true || val === 'completo') ? 'completo' : val === 'lectura' ? 'lectura' : 'ninguno';
                const radio = document.querySelector(`input[name="perm_${m}"][value="${v}"]`);
                if (radio) radio.checked = true;
            });
        }
        Modal.open('modalRol');
    } catch(e) { Toast.error('Error al cargar rol.'); }
}

function toggleAdminTotal(checked) {
    MODULOS.forEach(m => {
        document.querySelectorAll(`input[name="perm_${m}"]`).forEach(r => {
            r.disabled = checked;
            if (checked && r.value === 'completo') r.checked = true;
        });
    });
}

async function guardarRol() {
    const nombre = document.getElementById('rNombre').value.trim();
    if (!nombre) { Toast.warning('El nombre del rol es obligatorio.'); return; }
    let permisos = {};
    if (document.getElementById('rAdminTotal').checked) {
        permisos = { all: true };
    } else {
        MODULOS.forEach(m => {
            const sel = document.querySelector(`input[name="perm_${m}"]:checked`);
            if (sel && sel.value !== 'ninguno') permisos[m] = sel.value === 'completo' ? true : 'lectura';
        });
    }
    try {
        const res = await apiPost(BASE, {
            action:'rol_guardar',
            id: document.getElementById('rId').value,
            nombre,
            descripcion: document.getElementById('rDescripcion').value,
            permisos,
        });
        if (res.ok) { Toast.success('Rol guardado.'); Modal.close('modalRol'); cargarRoles(); }
        else Toast.error(res.error || 'Error.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

async function eliminarRol(id) {
    if (!confirm('¿Eliminar este rol?')) return;
    const res = await apiPost(BASE, { action:'rol_eliminar', id });
    if (res.ok) { Toast.success('Rol eliminado.'); cargarRoles(); }
    else Toast.error(res.error || 'No se puede eliminar.');
}

// ── ÁREAS ─────────────────────────────────────────────────
async function cargarAreas() {
    try {
        const data = await apiGet(`${BASE}?action=areas_listar`);
        const tbody = document.getElementById('areasBody');
        const areas = data.areas || [];
        if (!areas.length) { tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Sin áreas.</td></tr>'; return; }
        tbody.innerHTML = areas.map(a => `<tr>
            <td class="font-semibold">${esc(a.nombre)}</td>
            <td class="text-secondary text-sm">${esc(a.descripcion||'—')}</td>
            <td class="text-center"><span class="badge badge-normal">${a.total_usuarios} usuarios</span></td>
            <td><span class="badge ${a.activo ? 'badge-activo' : 'badge-cancelado'}">${a.activo ? 'Activa' : 'Inactiva'}</span></td>
            <td>
                <button class="btn btn-outline btn-xs" onclick="editarArea(${a.id}, ${JSON.stringify(a.nombre)}, ${JSON.stringify(a.descripcion||'')})"><i class="fa fa-pencil"></i></button>
                <button class="btn btn-outline btn-xs" onclick="toggleArea(${a.id})" title="Activar/Desactivar"><i class="fa fa-power-off"></i></button>
                <button class="btn btn-outline btn-xs" onclick="eliminarArea(${a.id})" title="Eliminar"><i class="fa fa-trash"></i></button>
            </td>
        </tr>`).join('');
    } catch(e) { Toast.error('Error al cargar áreas.'); }
}

function abrirModalArea() {
    document.getElementById('aId').value = '';
    document.getElementById('aNombre').value = '';
    document.getElementById('aDescripcion').value = '';
    document.getElementById('modalAreaTitulo').textContent = 'Nueva Área';
    Modal.open('modalArea');
}

function editarArea(id, nombre, desc) {
    document.getElementById('aId').value = id;
    document.getElementById('aNombre').value = nombre;
    document.getElementById('aDescripcion').value = desc;
    document.getElementById('modalAreaTitulo').textContent = 'Editar Área';
    Modal.open('modalArea');
}

async function guardarArea() {
    const nombre = document.getElementById('aNombre').value.trim();
    if (!nombre) { Toast.warning('El nombre es obligatorio.'); return; }
    try {
        const res = await apiPost(BASE, {
            action:'area_guardar',
            id: document.getElementById('aId').value,
            nombre,
            descripcion: document.getElementById('aDescripcion').value,
        });
        if (res.ok) { Toast.success('Área guardada.'); Modal.close('modalArea'); cargarAreas(); }
        else Toast.error(res.error || 'Error.');
    } catch(e) { Toast.error('Error de conexión.'); }
}

async function toggleArea(id) {
    const res = await apiPost(BASE, { action:'area_toggle', id });
    if (res.ok) { Toast.success('Estado actualizado.'); cargarAreas(); }
}

async function eliminarArea(id) {
    if (!confirm('¿Eliminar esta área?')) return;
    const res = await apiPost(BASE, { action:'area_eliminar', id });
    if (res.ok) { Toast.success('Área eliminada.'); cargarAreas(); }
    else Toast.error(res.error || 'No se puede eliminar.');
}

// ── AUDITORÍA ─────────────────────────────────────────────
async function cargarAuditoria() {
    const tbody = document.getElementById('auditoriaBody');
    tbody.innerHTML = '<tr><td colspan="8" class="text-center"><div class="loading"><div class="spinner"></div></div></td></tr>';
    try {
        const params = new URLSearchParams({
            action: 'auditoria_listar',
            modulo: document.getElementById('filtroModulo').value,
            accion: document.getElementById('filtroAccion').value,
            desde:  document.getElementById('filtroDesde').value,
            hasta:  document.getElementById('filtroHasta').value,
        });
        const data = await apiGet(`${BASE}?${params}`);
        const rows = data.registros || [];
        if (!rows.length) { tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Sin registros para los filtros seleccionados.</td></tr>'; return; }
        const colores = { crear:'badge-entrega', editar:'badge-fabricacion', eliminar:'badge-retrasado', login:'badge-normal', logout:'badge-cancelado' };
        tbody.innerHTML = rows.map(r => `<tr>
            <td class="text-xs">${new Date(r.created_at).toLocaleString('es-PE')}</td>
            <td class="text-sm">${esc(r.usuario_nombre||'Sistema')}</td>
            <td><span class="badge ${colores[r.accion]||'badge-normal'}">${esc(r.accion)}</span></td>
            <td class="text-sm">${esc(r.modulo||'—')}</td>
            <td class="text-xs text-muted">${esc(r.tabla||'—')}</td>
            <td class="text-xs text-muted">${r.registro_id||'—'}</td>
            <td class="text-xs text-muted">${esc(r.ip||'—')}</td>
            <td>${(r.datos_anteriores||r.datos_nuevos) ? `<button class="btn btn-outline btn-xs" onclick="verDetalle(this, ${r.id})"><i class="fa fa-eye"></i></button>` : ''}</td>
        </tr>`).join('');

        // Guardar datos para ver detalle
        rows.forEach(r => { if(r.id) window['_audit_'+r.id] = r; });
    } catch(e) { tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Error al cargar.</td></tr>'; }
}

function verDetalle(btn, id) {
    const r = window['_audit_'+id];
    if (!r) return;
    const fmt = v => { try { return JSON.stringify(typeof v==='string'?JSON.parse(v):v, null, 2); } catch(_){ return v; } };
    document.getElementById('modalAuditoriaBody').innerHTML = `
        ${r.datos_anteriores ? `<p class="text-xs font-semibold text-muted mb-1">Datos anteriores:</p>
            <pre style="background:#fef2f2;padding:10px;border-radius:6px;font-size:.75rem;overflow-x:auto">${esc(fmt(r.datos_anteriores))}</pre>` : ''}
        ${r.datos_nuevos ? `<p class="text-xs font-semibold text-muted mb-1 mt-1">Datos nuevos:</p>
            <pre style="background:#f0fdf4;padding:10px;border-radius:6px;font-size:.75rem;overflow-x:auto">${esc(fmt(r.datos_nuevos))}</pre>` : ''}
    `;
    Modal.open('modalAuditoria');
}

// ── BACKUPS ───────────────────────────────────────────────
async function cargarBackups() {
    try {
        const data = await apiGet(`${BASE}?action=backup_listar`);
        const tbody = document.getElementById('backupsBody');
        const rows = data.backups || [];
        if (!rows.length) { tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Sin backups generados.</td></tr>'; return; }
        tbody.innerHTML = rows.map(b => `<tr>
            <td class="text-sm font-semibold">${esc(b.nombre)}</td>
            <td class="text-xs text-muted">${formatBytes(b.tamanio)}</td>
            <td class="text-xs">${new Date(b.created_at).toLocaleString('es-PE')}</td>
            <td><span class="badge badge-entrega">${esc(b.estado)}</span></td>
            <td><button class="btn btn-outline btn-xs" onclick="eliminarBackup(${b.id})"><i class="fa fa-trash"></i></button></td>
        </tr>`).join('');
    } catch(e) {}
}

async function generarBackup() {
    const btn    = document.getElementById('btnBackup');
    const status = document.getElementById('backupStatus');
    btn.disabled = true;
    btn.innerHTML = '<div class="spinner" style="width:14px;height:14px;border-width:2px;display:inline-block"></div> Generando…';
    status.innerHTML = '';
    try {
        const res = await apiPost(BASE, { action:'backup_crear' });
        if (res.ok) {
            Toast.success('Backup generado: ' + res.nombre);
            status.innerHTML = `<div class="alert alert-success" style="margin:0;font-size:.78rem">✓ ${esc(res.nombre)} · ${formatBytes(res.tamanio)}</div>`;
            cargarBackups();
        } else {
            Toast.error(res.error || 'Error al generar backup.');
            status.innerHTML = `<div class="alert alert-danger" style="margin:0;font-size:.78rem">${esc(res.error||'Error')}</div>`;
        }
    } catch(e) { Toast.error('Error de conexión.'); }
    btn.disabled = false;
    btn.innerHTML = '<i class="fa fa-download"></i> Generar Backup Ahora';
}

async function eliminarBackup(id) {
    if (!confirm('¿Eliminar este backup?')) return;
    const res = await apiPost(BASE, { action:'backup_eliminar', id });
    if (res.ok) { Toast.success('Backup eliminado.'); cargarBackups(); }
}

function formatBytes(b) {
    if (!b) return '—';
    if (b < 1024) return b + ' B';
    if (b < 1048576) return (b/1024).toFixed(1) + ' KB';
    return (b/1048576).toFixed(2) + ' MB';
}

function esc(s) { const d = document.createElement('div'); d.textContent = String(s||''); return d.innerHTML; }

// ── Iniciar ───────────────────────────────────────────────
cargarUsuarios();
cargarRoles();
cargarAreas();
cargarBackups();

const hoy = new Date().toISOString().split('T')[0];
const mes = new Date(new Date().setDate(1)).toISOString().split('T')[0];
document.getElementById('filtroDesde').value = mes;
document.getElementById('filtroHasta').value = hoy;
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<?php
// includes/header.php
require_once __DIR__ . '/../config/session.php';
startAppSession();
require_once __DIR__ . '/../config/database.php';

// Redirigir al login si no hay sesión activa o está corrupta
if (empty($_SESSION['usuario']) || !is_array($_SESSION['usuario'])) {
    session_destroy();
    $base = str_repeat('../', substr_count($_SERVER['REQUEST_URI'], '/') - 2);
    header('Location: ' . $base . 'login.php');
    exit;
}

$usuario = getUsuarioActual();

// Módulo activo basado en la URL
$modulo_actual = '';
$uri = $_SERVER['REQUEST_URI'];
if (strpos($uri, '/produccion')        !== false) $modulo_actual = 'produccion';
elseif (strpos($uri, '/inventarios')   !== false) $modulo_actual = 'inventarios';
elseif (strpos($uri, '/compras')       !== false) $modulo_actual = 'compras';
elseif (strpos($uri, '/activos')       !== false) $modulo_actual = 'activos';
elseif (strpos($uri, '/clientes')      !== false) $modulo_actual = 'clientes';
elseif (strpos($uri, '/ventas')        !== false) $modulo_actual = 'ventas';
elseif (strpos($uri, '/comercial')     !== false) $modulo_actual = 'comercial';
elseif (strpos($uri, '/cuentas_cobrar') !== false) $modulo_actual = 'cuentas_cobrar';
elseif (strpos($uri, '/cuentas_pagar') !== false) $modulo_actual = 'cuentas_pagar';
elseif (strpos($uri, '/prestamos')     !== false) $modulo_actual = 'prestamos';
elseif (strpos($uri, '/caja')          !== false) $modulo_actual = 'caja';
elseif (strpos($uri, '/reportes')      !== false) $modulo_actual = 'reportes';
elseif (strpos($uri, '/seguridad')     !== false) $modulo_actual = 'seguridad';
elseif (strpos($uri, '/configuracion') !== false) $modulo_actual = 'configuracion';
elseif (strpos($uri, '/capacitacion')  !== false) $modulo_actual = 'capacitacion';

// Reportes de áreas (avances) — últimos 7 días
$avances_bell = [];
try {
    $db = getDB();
    $stmt = $db->query("
        SELECT pa.id, pa.descripcion, pa.porcentaje, pa.created_at,
               a.nombre  AS area_nombre,
               p.codigo  AS proy_codigo,
               p.id      AS proyecto_id,
               pa2.id    AS proyecto_area_id,
               u.nombre  AS usuario_nombre
        FROM proyecto_avances pa
        JOIN proyectos p ON p.id = pa.proyecto_id
        LEFT JOIN areas a ON a.id = pa.area_id
        LEFT JOIN proyecto_areas pa2 ON pa2.proyecto_id = pa.proyecto_id AND pa2.area_id = pa.area_id
        LEFT JOIN usuarios u ON u.id = pa.usuario_id
        WHERE pa.created_at >= NOW() - INTERVAL '7 days'
        ORDER BY pa.created_at DESC
        LIMIT 40
    ");
    $avances_bell = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$page_title = $page_title ?? APP_NAME;
$page_breadcrumb = $page_breadcrumb ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= str_repeat('../', substr_count($uri, '/') - 2) ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="<?= str_repeat('../', substr_count($uri, '/') - 2) ?>assets/js/main.js"></script>
    <script>
    try { if (localStorage.getItem('sidebar_collapsed')==='1') document.documentElement.classList.add('sidebar-collapsed'); } catch(e){}
    function toggleSidebar(){
        var c=document.documentElement.classList.toggle('sidebar-collapsed');
        try{localStorage.setItem('sidebar_collapsed',c?'1':'0');}catch(e){}
        var icon=document.getElementById('sidebarToggleIcon');
        if(icon)icon.style.transform=c?'rotate(180deg)':'rotate(0deg)';
    }
    document.addEventListener('DOMContentLoaded',function(){
        if(document.documentElement.classList.contains('sidebar-collapsed')){
            var icon=document.getElementById('sidebarToggleIcon');
            if(icon)icon.style.transform='rotate(180deg)';
        }
    });
    </script>
</head>
<body>

<!-- ======================================================
     SIDEBAR
     ====================================================== -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <img src="/industria_mg/assets/iconos/logo.jpeg" alt="IndustriaMG"
             style="width:36px;height:36px;object-fit:contain;border-radius:6px;flex-shrink:0">
        <div>
            <div class="logo-text"><?= APP_NAME ?></div>
            <div class="logo-sub">Sistema de Gestión</div>
        </div>
        <button type="button" onclick="toggleSidebar()" title="Contraer menú"
                style="margin-left:auto;flex-shrink:0;background:transparent;border:none;cursor:pointer;
                       color:var(--sidebar-text);width:28px;height:28px;display:flex;align-items:center;
                       justify-content:center;border-radius:6px;font-size:.85rem;transition:background .15s;"
                onmouseover="this.style.background='rgba(255,255,255,.1)'"
                onmouseout="this.style.background='transparent'">
            <i class="fa fa-chevron-left" id="sidebarToggleIcon"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <!-- PRINCIPAL -->
        <div class="nav-section">Principal</div>
        <a href="<?= str_repeat('../', substr_count($uri, '/') - 2) ?>index.php"
           class="nav-item <?= $modulo_actual === '' ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fa fa-gauge"></i></span>
            <span>Dashboard</span>
        </a>

        <!-- OPERACIONES -->
        <div class="nav-section">Operaciones</div>
        <a href="<?= str_repeat('../', substr_count($uri, '/') - 2) ?>modules/produccion/index.php"
           class="nav-item <?= $modulo_actual === 'produccion' ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fa fa-industry"></i></span>
            <span>Producción</span>
        </a>
        <a href="<?= str_repeat('../', substr_count($uri, '/') - 2) ?>modules/inventarios/index.php"
           class="nav-item <?= $modulo_actual === 'inventarios' ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fa fa-boxes-stacked"></i></span>
            <span>Inventarios</span>
        </a>
        <a href="<?= str_repeat('../', substr_count($uri, '/') - 2) ?>modules/compras/index.php"
           class="nav-item <?= $modulo_actual === 'compras' ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fa fa-cart-shopping"></i></span>
            <span>Compras</span>
        </a>
        <a href="<?= str_repeat('../', substr_count($uri, '/') - 2) ?>modules/activos/index.php"
           class="nav-item <?= $modulo_actual === 'activos' ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fa fa-toolbox"></i></span>
            <span>Activos</span>
        </a>

        <!-- COMERCIAL Y DIGITAL -->
        <div class="nav-section">Comercial y Digital</div>
        <a href="<?= str_repeat('../', substr_count($uri, '/') - 2) ?>modules/clientes/index.php"
           class="nav-item <?= $modulo_actual === 'clientes' ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fa fa-users"></i></span>
            <span>Clientes</span>
        </a>
        <a href="<?= str_repeat('../', substr_count($uri, '/') - 2) ?>modules/ventas/index.php"
           class="nav-item <?= $modulo_actual === 'ventas' ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fa fa-file-invoice-dollar"></i></span>
            <span>Ventas</span>
        </a>
        <a href="<?= str_repeat('../', substr_count($uri, '/') - 2) ?>modules/comercializacion/index.php"
           class="nav-item <?= $modulo_actual === 'comercial' ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fa fa-globe"></i></span>
            <span>Comercialización</span>
        </a>

        <!-- FINANZAS -->
        <div class="nav-section">Finanzas</div>
        <a href="<?= str_repeat('../', substr_count($uri, '/') - 2) ?>modules/caja/index.php"
           class="nav-item <?= $modulo_actual === 'caja' ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fa fa-cash-register"></i></span>
            <span>Caja</span>
        </a>
        <a href="<?= str_repeat('../', substr_count($uri, '/') - 2) ?>modules/cuentas_cobrar/index.php"
           class="nav-item <?= $modulo_actual === 'cuentas_cobrar' ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fa fa-hand-holding-dollar"></i></span>
            <span>Cuentas por Cobrar</span>
        </a>
        <a href="<?= str_repeat('../', substr_count($uri, '/') - 2) ?>modules/cuentas_pagar/index.php"
           class="nav-item <?= $modulo_actual === 'cuentas_pagar' ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fa fa-file-invoice"></i></span>
            <span>Cuentas por Pagar</span>
        </a>
        <a href="<?= str_repeat('../', substr_count($uri, '/') - 2) ?>modules/prestamos/index.php"
           class="nav-item <?= $modulo_actual === 'prestamos' ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fa fa-hand-holding-dollar"></i></span>
            <span>Préstamos</span>
        </a>

        <!-- ANALYTICS -->
        <div class="nav-section">Analytics</div>
        <a href="<?= str_repeat('../', substr_count($uri, '/') - 2) ?>modules/reportes/index.php"
           class="nav-item <?= $modulo_actual === 'reportes' ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fa fa-chart-bar"></i></span>
            <span>Reportes</span>
        </a>

        <!-- SISTEMA -->
        <div class="nav-section">Sistema</div>
        <a href="<?= str_repeat('../', substr_count($uri, '/') - 2) ?>modules/seguridad/index.php"
           class="nav-item <?= $modulo_actual === 'seguridad' ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fa fa-shield-halved"></i></span>
            <span>Seguridad</span>
        </a>
        <a href="<?= str_repeat('../', substr_count($uri, '/') - 2) ?>modules/configuracion/index.php"
           class="nav-item <?= $modulo_actual === 'configuracion' ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fa fa-sliders"></i></span>
            <span>Configuración</span>
        </a>
        <a href="<?= str_repeat('../', substr_count($uri, '/') - 2) ?>modules/capacitacion/index.php"
           class="nav-item <?= $modulo_actual === 'capacitacion' ? 'active' : '' ?>">
            <span class="nav-icon"><i class="fa fa-graduation-cap"></i></span>
            <span>Capacitación</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-avatar"><?= strtoupper(substr($usuario['nombre'], 0, 1)) ?></div>
        <div>
            <div class="user-name"><?= htmlspecialchars($usuario['nombre']) ?></div>
            <div class="user-role"><?= htmlspecialchars($usuario['rol'] ?? 'Administrador') ?></div>
        </div>
    </div>
</aside>


<!-- ======================================================
     MAIN WRAPPER
     ====================================================== -->
<div class="main-wrapper">
    <!-- TOPBAR -->
    <header class="topbar">
        <div>
            <div class="topbar-title"><?= htmlspecialchars($page_title) ?></div>
            <?php if ($page_breadcrumb): ?>
            <div class="topbar-breadcrumb"><?= $page_breadcrumb ?></div>
            <?php endif; ?>
        </div>
        <div class="topbar-right">
            <!-- Campana de reportes de áreas -->
            <div style="position:relative" id="bellWrap">
                <button class="topbar-btn" id="bellBtn" title="Reportes de áreas" onclick="bellToggle(event)">
                    <i class="fa fa-bell" id="bellIcon"></i>
                    <span id="bellBadge" style="display:none;position:absolute;top:4px;right:4px;
                          min-width:16px;height:16px;background:#ef4444;color:#fff;font-size:.6rem;
                          font-weight:700;border-radius:8px;display:none;align-items:center;
                          justify-content:center;padding:0 4px;line-height:1">0</span>
                </button>
                <!-- Panel desplegable -->
                <div id="bellPanel" style="display:none;position:fixed;right:16px;top:56px;
                     width:360px;max-height:500px;background:#fff;
                     border:1px solid var(--border);border-radius:10px;
                     box-shadow:0 8px 28px rgba(0,0,0,.14);z-index:2000;
                     overflow:hidden;flex-direction:column">
                    <div style="padding:11px 16px;border-bottom:1px solid var(--border);
                                display:flex;justify-content:space-between;align-items:center;
                                background:#f8fafc">
                        <span style="font-weight:700;font-size:.85rem">
                            <i class="fa fa-clipboard-list" style="color:var(--primary);margin-right:6px"></i>
                            Reportes de Áreas
                        </span>
                        <span style="font-size:.72rem;color:var(--text-muted)">últimos 7 días</span>
                    </div>
                    <div style="overflow-y:auto;max-height:430px">
                        <?php if (empty($avances_bell)): ?>
                        <div style="padding:32px;text-align:center;color:var(--text-muted);font-size:.84rem">
                            <i class="fa fa-inbox" style="font-size:1.8rem;opacity:.25;display:block;margin-bottom:10px"></i>
                            Sin reportes recientes
                        </div>
                        <?php else: ?>
                        <?php foreach ($avances_bell as $av): ?>
                        <a href="<?= str_repeat('../', substr_count($uri, '/') - 2) ?>modules/produccion/proyecto.php?id=<?= $av['proyecto_id'] ?>#tabAvances"
                           style="display:block;padding:10px 16px;border-bottom:1px solid #f1f5f9;
                                  text-decoration:none;color:inherit;transition:background .12s"
                           onmouseover="this.style.background='#f8fafc'"
                           onmouseout="this.style.background=''"
                           data-avid="<?= $av['id'] ?>">
                            <div style="display:flex;justify-content:space-between;gap:8px;align-items:flex-start">
                                <div style="flex:1;min-width:0">
                                    <div style="font-size:.76rem;font-weight:700;color:var(--primary);margin-bottom:2px">
                                        <i class="fa fa-industry" style="opacity:.6;margin-right:3px"></i>
                                        <?= htmlspecialchars($av['proy_codigo']) ?>
                                        <?php if ($av['area_nombre']): ?>
                                        <span style="color:var(--text-muted);font-weight:400"> · <?= htmlspecialchars($av['area_nombre']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size:.81rem;color:var(--text-primary);line-height:1.4;
                                                overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;
                                                -webkit-box-orient:vertical">
                                        <?= htmlspecialchars($av['descripcion']) ?>
                                    </div>
                                    <div style="font-size:.7rem;color:var(--text-muted);margin-top:3px">
                                        <i class="fa fa-user" style="opacity:.5;margin-right:2px"></i>
                                        <?= htmlspecialchars($av['usuario_nombre'] ?? '—') ?>
                                        &nbsp;·&nbsp;
                                        <span style="background:#eff6ff;color:#1d4ed8;padding:1px 6px;
                                                     border-radius:4px;font-weight:600">
                                            <?= $av['porcentaje'] ?>%
                                        </span>
                                    </div>
                                </div>
                                <div style="font-size:.67rem;color:var(--text-muted);white-space:nowrap;margin-top:1px">
                                    <?= date('d/m H:i', strtotime($av['created_at'])) ?>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <style>
            @keyframes bellRing {
                0%,100%{ transform:rotate(0) }
                15%    { transform:rotate(18deg) }
                30%    { transform:rotate(-16deg) }
                45%    { transform:rotate(12deg) }
                60%    { transform:rotate(-10deg) }
                75%    { transform:rotate(6deg) }
            }
            #bellIcon.ringing { animation: bellRing .7s ease forwards; }
            </style>
            <script>
            (function(){
                const KEY  = 'bell_last_id';
                const avances = <?= json_encode(array_column($avances_bell, 'id')) ?>;
                const maxId = avances.length ? Math.max(...avances) : 0;
                const lastId = parseInt(localStorage.getItem(KEY) || '0');
                const newCount = avances.filter(id => id > lastId).length;

                // Badge
                const badge = document.getElementById('bellBadge');
                if (newCount > 0) {
                    badge.textContent = newCount > 99 ? '99+' : newCount;
                    badge.style.display = 'flex';
                }

                // Sonido + animación si hay nuevos (y no es primera visita)
                if (newCount > 0 && lastId > 0) {
                    playBell();
                    const icon = document.getElementById('bellIcon');
                    icon.classList.add('ringing');
                    icon.addEventListener('animationend', () => icon.classList.remove('ringing'));
                }

                // Si es primera visita, guardamos el maxId sin sonar
                if (!localStorage.getItem(KEY)) {
                    localStorage.setItem(KEY, maxId);
                }

                window.bellToggle = function(e) {
                    e.stopPropagation();
                    const panel = document.getElementById('bellPanel');
                    const open  = panel.style.display !== 'none';
                    panel.style.display = open ? 'none' : 'flex';
                    if (!open && maxId > 0) {
                        localStorage.setItem(KEY, maxId);
                        badge.style.display = 'none';
                    }
                };

                document.addEventListener('click', function(e) {
                    const wrap = document.getElementById('bellWrap');
                    if (wrap && !wrap.contains(e.target))
                        document.getElementById('bellPanel').style.display = 'none';
                });

                function playBell() {
                    try {
                        const ctx  = new (window.AudioContext || window.webkitAudioContext)();
                        const osc  = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(1046, ctx.currentTime);          // Do6
                        osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.15); // La5
                        gain.gain.setValueAtTime(0.25, ctx.currentTime);
                        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 1.0);
                        osc.start(ctx.currentTime);
                        osc.stop(ctx.currentTime + 1.0);
                    } catch(e) {}
                }
            })();
            </script>
            <a href="<?= str_repeat('../', substr_count($uri, '/') - 2) ?>logout.php"
               class="topbar-btn" title="Cerrar sesión — <?= htmlspecialchars($usuario['nombre']) ?>">
                <i class="fa fa-right-from-bracket"></i>
            </a>
        </div>
    </header>

    <!-- CONTENIDO -->
    <main class="content">

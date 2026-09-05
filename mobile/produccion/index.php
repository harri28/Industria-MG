<?php
// mobile/produccion/index.php
// Bandeja móvil del operario: ver y avanzar las partes asignadas a su área,
// reutilizando el mismo backend que modules/produccion/api.php (bandeja_listar,
// parte_iniciar, parte_completar) — sin duplicar lógica de negocio.

require_once __DIR__ . '/../../config/session.php';
startAppSession();
require_once __DIR__ . '/../../config/database.php';

if (empty($_SESSION['usuario']) || !is_array($_SESSION['usuario'])) {
    header('Location: ' . APP_BASE . 'login.php');
    exit;
}

$usuario = getUsuarioActual();
$db = getDB();

$areas  = $db->query("SELECT id, nombre FROM areas WHERE activo ORDER BY nombre")->fetchAll();
$areaId = (int)($_GET['area_id'] ?? $usuario['area_id'] ?? 0);
if (!$areaId && $areas) $areaId = (int)$areas[0]['id'];

function esc_m($s) { return htmlspecialchars((string)$s, ENT_QUOTES); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Mi Bandeja — IndustriaMG</title>
    <link rel="stylesheet" href="<?= APP_BASE ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        body { margin: 0; background: var(--body-bg); font-family: var(--font); padding-bottom: 24px; }

        .m-topbar {
            position: sticky; top: 0; z-index: 20;
            background: var(--sidebar-bg); border-bottom: 1px solid var(--border);
            padding: 12px 16px; display: flex; align-items: center; gap: 10px;
            box-shadow: var(--card-shadow);
        }
        .m-topbar .brand { flex: 1; }
        .m-topbar .brand strong { display: block; font-size: .95rem; }
        .m-topbar .brand span { display: block; font-size: .72rem; color: var(--text-muted); }
        .m-topbar a { color: var(--text-secondary); font-size: 1.1rem; padding: 6px; }

        .m-wrap { padding: 14px; max-width: 560px; margin: 0 auto; }

        .m-area-select { width: 100%; margin-bottom: 12px; font-size: .95rem; padding: 12px; }

        .m-tabs { display: flex; gap: 8px; margin-bottom: 14px; }
        .m-tab {
            flex: 1; text-align: center; padding: 10px; border-radius: var(--radius-sm);
            background: #fff; border: 1.5px solid var(--border); font-size: .85rem; font-weight: 600;
            color: var(--text-secondary); cursor: pointer;
        }
        .m-tab.active { background: var(--primary-light); border-color: var(--primary); color: var(--primary-dark); }
        .m-tab .cnt { display: inline-block; margin-left: 4px; }

        .m-card {
            background: #fff; border: 1px solid var(--card-border); border-radius: var(--radius);
            box-shadow: var(--card-shadow); padding: 14px; margin-bottom: 12px;
        }
        .m-card .proy { font-size: .72rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; }
        .m-card .pieza { font-size: .78rem; color: var(--text-secondary); margin: 2px 0 4px; }
        .m-card .parte { font-size: 1.05rem; font-weight: 700; margin-bottom: 8px; }
        .m-card .mats { font-size: .78rem; color: var(--text-secondary); margin-bottom: 10px; line-height: 1.6; }
        .m-card .fecha { font-size: .74rem; color: var(--text-muted); margin-bottom: 10px; }
        .m-card .fecha.atrasado { color: var(--danger); font-weight: 600; }

        .m-btn {
            width: 100%; padding: 14px; border: none; border-radius: var(--radius-sm);
            font-size: .95rem; font-weight: 700; cursor: pointer; display: flex;
            align-items: center; justify-content: center; gap: 8px;
        }
        .m-btn-start { background: var(--primary); color: #fff; }
        .m-btn-complete { background: var(--success); color: #fff; }

        .m-empty { text-align: center; padding: 40px 16px; color: var(--text-muted); }
        .m-empty i { font-size: 2rem; margin-bottom: 10px; display: block; }

        .m-sheet-body { padding: 18px; }
        .m-sheet-body textarea { width: 100%; min-height: 80px; }
    </style>
</head>
<body>

<div class="m-topbar">
    <div class="brand">
        <strong>Mi Bandeja</strong>
        <span><?= esc_m($usuario['nombre'] ?? 'Operario') ?></span>
    </div>
    <a href="<?= APP_BASE ?>modules/produccion/bandeja.php<?= $areaId ? '?area_id=' . $areaId : '' ?>" title="Ver en escritorio">
        <i class="fa fa-desktop"></i>
    </a>
    <a href="<?= APP_BASE ?>logout.php" title="Salir">
        <i class="fa fa-right-from-bracket"></i>
    </a>
</div>

<div class="m-wrap">
    <select class="form-control m-area-select" id="areaSelect" onchange="cambiarArea(this.value)">
        <?php foreach ($areas as $a): ?>
        <option value="<?= $a['id'] ?>" <?= (int)$a['id'] === $areaId ? 'selected' : '' ?>><?= esc_m($a['nombre']) ?></option>
        <?php endforeach; ?>
    </select>

    <div class="m-tabs">
        <div class="m-tab active" data-filtro="en_proceso" onclick="filtrar('en_proceso')">
            En proceso <span class="cnt" id="cntProceso"></span>
        </div>
        <div class="m-tab" data-filtro="pendiente" onclick="filtrar('pendiente')">
            Pendientes <span class="cnt" id="cntPendiente"></span>
        </div>
    </div>

    <div id="listaPartes">
        <div class="loading" style="padding:30px"><div class="spinner"></div></div>
    </div>
</div>

<!-- BOTTOM SHEET: Completar parte -->
<div class="modal-overlay" id="modalCompletar">
    <div class="modal" style="max-width:420px">
        <div class="modal-header">
            <span class="modal-title">Completar tarea</span>
            <button class="modal-close" onclick="Modal.close('modalCompletar')">&times;</button>
        </div>
        <div class="m-sheet-body">
            <input type="hidden" id="completar_parte_id">
            <p style="font-size:.85rem;color:var(--text-secondary);margin-bottom:10px">
                <strong id="completar_parte_nombre"></strong> se marcará como completada usando las cantidades planificadas.
            </p>
            <label class="form-label">Observaciones (opcional)</label>
            <textarea class="form-control" id="completar_obs" placeholder="Notas del proceso..."></textarea>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('modalCompletar')">Cancelar</button>
            <button class="btn btn-primary" onclick="confirmarCompletar()">
                <i class="fa fa-check"></i> Confirmar
            </button>
        </div>
    </div>
</div>

<script src="<?= APP_BASE ?>assets/js/main.js"></script>
<script>
const API      = '<?= APP_BASE ?>modules/produccion/api.php';
const AREA_ID  = <?= (int)$areaId ?>;
let partesData = [];
let filtroActual = 'en_proceso';

function cambiarArea(id) {
    window.location.href = `index.php?area_id=${id}`;
}

function filtrar(f) {
    filtroActual = f;
    document.querySelectorAll('.m-tab').forEach(t => t.classList.toggle('active', t.dataset.filtro === f));
    render();
}

async function cargar() {
    if (!AREA_ID) {
        document.getElementById('listaPartes').innerHTML = `
            <div class="m-empty"><i class="fa fa-triangle-exclamation"></i>No tienes un área asignada. Selecciona una arriba.</div>`;
        return;
    }
    try {
        const d = await apiGet(`${API}?action=bandeja_listar&area_id=${AREA_ID}`);
        if (!d.ok) throw new Error(d.error || 'Error al cargar');
        partesData = d.partes || [];
        document.getElementById('cntProceso').textContent   = partesData.filter(p => p.estado_fabricacion === 'en_proceso').length || '';
        document.getElementById('cntPendiente').textContent = partesData.filter(p => p.estado_fabricacion === 'pendiente').length || '';
        render();
    } catch (e) {
        document.getElementById('listaPartes').innerHTML = `
            <div class="m-empty"><i class="fa fa-circle-exclamation"></i>${e.message || 'Error al cargar tu bandeja'}</div>`;
    }
}

function render() {
    const lista = partesData.filter(p => p.estado_fabricacion === filtroActual);
    const cont  = document.getElementById('listaPartes');
    if (!lista.length) {
        cont.innerHTML = `
            <div class="m-empty">
                <i class="fa fa-mug-hot"></i>
                Sin tareas ${filtroActual === 'en_proceso' ? 'en proceso' : 'pendientes'} en esta área.
            </div>`;
        return;
    }
    const hoy = new Date().toISOString().slice(0, 10);
    cont.innerHTML = lista.map(p => {
        const atrasado = p.fecha_entrega_estimada && p.fecha_entrega_estimada < hoy;
        const mats = (p.materiales || []).slice(0, 3)
            .map(m => `${m.prod_nombre || m.descripcion_libre || 'Material'} · ${parseFloat(m.cantidad_planificada || 0)} ${m.medida_unidad || ''}`)
            .join('<br>');
        const boton = p.estado_fabricacion === 'pendiente'
            ? `<button class="m-btn m-btn-start" onclick="iniciarParte(${p.id})"><i class="fa fa-play"></i> Iniciar</button>`
            : `<button class="m-btn m-btn-complete" onclick="abrirCompletar(${p.id}, '${(p.nombre || '').replace(/'/g, "\\'")}')"><i class="fa fa-check"></i> Completar</button>`;
        return `
        <div class="m-card">
            <div class="proy">${p.proy_codigo || ''} · ${p.proy_nombre || ''}</div>
            <div class="pieza">${p.pieza_nombre || ''}</div>
            <div class="parte">${p.nombre}</div>
            ${mats ? `<div class="mats">${mats}</div>` : ''}
            ${p.fecha_entrega_estimada ? `<div class="fecha ${atrasado ? 'atrasado' : ''}">
                <i class="fa fa-calendar"></i> Entrega: ${formatDate(p.fecha_entrega_estimada)} ${atrasado ? '· Atrasado' : ''}
            </div>` : ''}
            ${boton}
        </div>`;
    }).join('');
}

async function iniciarParte(id) {
    try {
        const r = await apiPost(API, { action: 'parte_iniciar', id });
        if (r.ok) { Toast.success('Tarea iniciada.'); cargar(); }
        else Toast.error(r.error || 'Error al iniciar');
    } catch (e) { Toast.error(e.message || 'Error al iniciar'); }
}

function abrirCompletar(id, nombre) {
    document.getElementById('completar_parte_id').value = id;
    document.getElementById('completar_parte_nombre').textContent = nombre;
    document.getElementById('completar_obs').value = '';
    Modal.open('modalCompletar');
}

async function confirmarCompletar() {
    const id  = document.getElementById('completar_parte_id').value;
    const obs = document.getElementById('completar_obs').value;
    try {
        const r = await apiPost(API, { action: 'parte_completar', id, observaciones: obs });
        if (r.ok) {
            Toast.success('Tarea completada.');
            Modal.close('modalCompletar');
            cargar();
        } else Toast.error(r.error || 'Error al completar');
    } catch (e) { Toast.error(e.message || 'Error al completar'); }
}

cargar();
</script>
</body>
</html>

<?php
$page_title      = 'Comercialización Digital';
$page_breadcrumb = 'Comercial & Digital / Comercialización';
require_once __DIR__ . '/../../includes/header.php';
?>
<style>
/* Especificaciones técnicas — acordeón */
details > summary { list-style: none; }
details > summary::-webkit-details-marker { display: none; }
details[open] > summary .fa-angle-right { transform: rotate(90deg); }
details > summary .fa-angle-right { display: inline-block; transition: transform .18s ease; }
details[open] > summary { background: #f0f9ff !important; color: var(--primary) !important; }
details[open] > summary .fa-angle-right { color: var(--primary) !important; }
</style>

<div class="tabs" id="mainTabs">
    <button class="tab-btn active" data-tab="catalogo">Catálogo</button>
    <button class="tab-btn" data-tab="leads">Leads</button>
</div>

<!-- CATÁLOGO -->
<div class="tab-content active" id="tab-catalogo">
    <div class="toolbar">
        <div class="toolbar-left">
            <input type="text" class="form-control" id="catSearch" placeholder="Buscar producto…" style="width:260px">
            <select class="form-control" id="catCategoria" style="width:180px">
                <option value="">Todas las categorías</option>
            </select>
            <select class="form-control" id="catPublicado" style="width:140px">
                <option value="">Todos</option>
                <option value="true">Publicados</option>
                <option value="false">No publicados</option>
            </select>
        </div>
        <div class="toolbar-right">
            <button class="btn btn-primary" onclick="abrirModalCatalogo()">
                <i class="fa fa-plus"></i> Nuevo Producto
            </button>
        </div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px" id="gridCatalogo">
        <p class="text-secondary" style="grid-column:1/-1;text-align:center;padding:40px">Cargando catálogo…</p>
    </div>
</div>

<!-- LEADS -->
<div class="tab-content" id="tab-leads">
    <div class="toolbar">
        <div class="toolbar-left">
            <input type="text" class="form-control" id="leadSearch" placeholder="Buscar lead…" style="width:260px">
            <select class="form-control" id="leadEstado" style="width:150px">
                <option value="">Todos los estados</option>
                <option value="nuevo">Nuevo</option>
                <option value="contactado">Contactado</option>
                <option value="calificado">Calificado</option>
                <option value="convertido">Convertido</option>
                <option value="descartado">Descartado</option>
            </select>
            <select class="form-control" id="leadOrigen" style="width:150px">
                <option value="">Todos los orígenes</option>
                <option value="web">Web</option>
                <option value="facebook">Facebook</option>
                <option value="instagram">Instagram</option>
                <option value="tiktok">TikTok</option>
                <option value="youtube">YouTube</option>
                <option value="directo">Directo</option>
            </select>
        </div>
        <div class="toolbar-right">
            <button class="btn btn-primary" onclick="abrirModalLead()">
                <i class="fa fa-plus"></i> Nuevo Lead
            </button>
        </div>
    </div>
    <div class="card">
        <table class="table" id="tablaLeads">
            <thead>
                <tr><th>Nombre</th><th>Empresa</th><th>Contacto</th><th>Origen</th><th>Estado</th><th>Próxima acción</th><th>Fecha</th><th>Acciones</th></tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- MODAL: CATÁLOGO -->
<div class="modal-overlay" id="modalCatalogo">
    <div class="modal" style="max-width:760px">
        <div class="modal-header">
            <h3 class="modal-title" id="modalCatTitle">Nuevo Producto</h3>
            <button class="modal-close" onclick="Modal.close('modalCatalogo')">×</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="cat_id">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div class="form-group" style="grid-column:1/3">
                    <label class="form-label">Nombre *</label>
                    <input type="text" class="form-control" id="cat_nombre">
                </div>
                <div class="form-group">
                    <label class="form-label">Categoría</label>
                    <input type="text" class="form-control" id="cat_cat" list="catCatList" placeholder="Ej: Maquinaria Industrial">
                    <datalist id="catCatList"></datalist>
                </div>
                <div class="form-group">
                    <label class="form-label">Precio Referencial (S/)</label>
                    <input type="number" class="form-control" id="cat_precio" min="0" step="0.01">
                </div>
                <div class="form-group" style="grid-column:1/3">
                    <label class="form-label">Descripción corta</label>
                    <input type="text" class="form-control" id="cat_dcorta" maxlength="500" placeholder="Máx. 500 caracteres">
                </div>
                <div class="form-group" style="grid-column:1/3">
                    <label class="form-label">Descripción completa</label>
                    <textarea class="form-control" id="cat_desc" rows="4"></textarea>
                </div>
                <div class="form-group" style="grid-column:1/3">
                    <label class="form-label">Imagen del producto</label>
                    <div id="catImgZone" style="width:100%;height:100px;border:2px dashed #cbd5e1;border-radius:8px;display:flex;align-items:center;justify-content:center;background:#f8fafc;cursor:pointer;overflow:hidden;position:relative" onclick="document.getElementById('catImgInput').click()">
                        <img id="catImgPreview" style="display:none;max-width:100%;max-height:100px;object-fit:contain">
                        <div id="catImgPlaceholder" style="text-align:center;color:#94a3b8;pointer-events:none">
                            <i class="fa fa-image" style="font-size:2rem;margin-bottom:6px;display:block"></i>
                            <span style="font-size:0.82rem">Clic para subir imagen (JPG, PNG, WebP · máx 5MB)</span>
                        </div>
                    </div>
                    <input type="file" id="catImgInput" accept=".jpg,.jpeg,.png,.webp" style="display:none" onchange="catImgChange(this)">
                    <button type="button" id="catImgQuit" onclick="catImgClear()" class="btn btn-secondary" style="display:none;margin-top:6px;font-size:0.78rem;padding:3px 10px">
                        <i class="fa fa-times"></i> Quitar imagen
                    </button>
                </div>
                <div class="form-group" style="grid-column:1/3">
                    <label class="form-label">Colores disponibles</label>
                    <div style="font-size:0.75rem;color:#94a3b8;margin-bottom:6px">Cada color con su imagen. Se usa al registrar una maquinaria.</div>
                    <div id="catColoresList"></div>
                    <button type="button" class="btn btn-secondary" style="font-size:0.78rem;padding:4px 12px;margin-top:4px" onclick="catColorAgregar()">
                        <i class="fa fa-plus"></i> Agregar color
                    </button>
                </div>
                <!-- ESPECIFICACIONES TÉCNICAS -->
                <div class="form-group" style="grid-column:1/3;border-top:1px solid var(--border);padding-top:14px;margin-top:4px">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
                        <label class="form-label" style="margin:0">Especificaciones Técnicas <span style="font-size:0.75rem;color:#94a3b8;font-weight:400">(opcional)</span></label>
                        <div style="display:flex;gap:6px">
                            <button type="button" class="btn btn-secondary" style="font-size:0.75rem;padding:3px 10px" onclick="catSpecsCargarPlantilla()" title="Cargar los 7 grupos del Aserradero GTK23 como punto de partida">
                                <i class="fa fa-file-import"></i> Plantilla GTK23
                            </button>
                            <button type="button" class="btn btn-secondary" style="font-size:0.75rem;padding:3px 10px" onclick="catSpecsToggle()">
                                <i id="catSpecsIco" class="fa fa-chevron-down"></i> <span id="catSpecsLbl">Mostrar</span>
                            </button>
                        </div>
                    </div>
                    <div id="catSpecsWrap" style="display:none">
                        <div id="catSpecsList"></div>
                        <button type="button" class="btn btn-secondary" style="font-size:0.78rem;padding:4px 14px;margin-top:10px" onclick="catSpecGrupoAgregar()">
                            <i class="fa fa-plus"></i> Agregar grupo
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Orden de aparición</label>
                    <input type="number" class="form-control" id="cat_orden" min="0" value="0">
                </div>
                <div class="form-group" style="display:flex;gap:20px;align-items:flex-end">
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                        <input type="checkbox" id="cat_publicado"> Publicado
                    </label>
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                        <input type="checkbox" id="cat_destacado"> Destacado
                    </label>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('modalCatalogo')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarCatalogo()">Guardar</button>
        </div>
    </div>
</div>

<!-- MODAL: LEAD -->
<div class="modal-overlay" id="modalLead">
    <div class="modal" style="max-width:520px">
        <div class="modal-header">
            <h3 class="modal-title" id="modalLeadTitle">Nuevo Lead</h3>
            <button class="modal-close" onclick="Modal.close('modalLead')">×</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="lead_id">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div class="form-group">
                    <label class="form-label">Nombre</label>
                    <input type="text" class="form-control" id="lead_nombre">
                </div>
                <div class="form-group">
                    <label class="form-label">Empresa</label>
                    <input type="text" class="form-control" id="lead_empresa">
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" id="lead_email">
                </div>
                <div class="form-group">
                    <label class="form-label">Teléfono</label>
                    <input type="text" class="form-control" id="lead_telefono">
                </div>
                <div class="form-group">
                    <label class="form-label">Origen</label>
                    <select class="form-control" id="lead_origen">
                        <option value="directo">Directo</option>
                        <option value="web">Web</option>
                        <option value="facebook">Facebook</option>
                        <option value="instagram">Instagram</option>
                        <option value="tiktok">TikTok</option>
                        <option value="youtube">YouTube</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select class="form-control" id="lead_estado">
                        <option value="nuevo">Nuevo</option>
                        <option value="contactado">Contactado</option>
                        <option value="calificado">Calificado</option>
                        <option value="descartado">Descartado</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Responsable</label>
                    <select class="form-control" id="lead_responsable"><option value="">— Sin asignar —</option></select>
                </div>
                <div class="form-group">
                    <label class="form-label">Próxima fecha</label>
                    <input type="date" class="form-control" id="lead_pfecha">
                </div>
                <div class="form-group" style="grid-column:1/3">
                    <label class="form-label">Próxima acción</label>
                    <input type="text" class="form-control" id="lead_paccion" placeholder="Ej. Llamar para enviar cotización">
                </div>
                <div class="form-group" style="grid-column:1/3">
                    <label class="form-label">Mensaje / Interés</label>
                    <textarea class="form-control" id="lead_mensaje" rows="3"></textarea>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('modalLead')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarLead()">Guardar</button>
        </div>
    </div>
</div>

<!-- MODAL: SEGUIMIENTO DE LEAD -->
<div class="modal-overlay" id="modalSeguimiento">
    <div class="modal" style="max-width:560px">
        <div class="modal-header">
            <h3 class="modal-title">Seguimiento — <span id="seg_lead_nombre"></span></h3>
            <button class="modal-close" onclick="Modal.close('modalSeguimiento')">×</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="seg_lead_id">
            <div id="seg_resumen" style="background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:10px 12px;margin-bottom:14px;font-size:0.85rem"></div>

            <div style="display:grid;grid-template-columns:140px 1fr auto;gap:8px;align-items:end;margin-bottom:14px">
                <div class="form-group" style="margin:0">
                    <label class="form-label">Tipo</label>
                    <select class="form-control" id="seg_tipo">
                        <option value="llamada">Llamada</option>
                        <option value="whatsapp">WhatsApp</option>
                        <option value="email">Email</option>
                        <option value="visita">Visita / Reunión</option>
                        <option value="nota">Nota</option>
                    </select>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Nota</label>
                    <input type="text" class="form-control" id="seg_nota" placeholder="Qué se habló / acordó…" onkeydown="if(event.key==='Enter')guardarInteraccion()">
                </div>
                <button class="btn btn-primary" onclick="guardarInteraccion()"><i class="fa fa-plus"></i> Agregar</button>
            </div>

            <div style="font-size:0.78rem;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px">Bitácora</div>
            <div id="seg_timeline"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('modalSeguimiento')">Cerrar</button>
        </div>
    </div>
</div>

<!-- MODAL: CONFIRMACIÓN -->
<div class="modal-overlay" id="modalConfirmar">
    <div class="modal" style="max-width:400px">
        <div class="modal-header" style="border-bottom:none;padding-bottom:0">
            <div style="width:48px;height:48px;border-radius:50%;background:#fee2e2;display:flex;align-items:center;justify-content:center;margin:0 auto">
                <i class="fa fa-triangle-exclamation" style="color:#dc2626;font-size:1.3rem"></i>
            </div>
        </div>
        <div class="modal-body" style="text-align:center;padding-top:12px">
            <p id="confirmarMensaje" style="font-size:0.95rem;color:#374151;margin:0"></p>
        </div>
        <div class="modal-footer" style="justify-content:center;gap:12px">
            <button class="btn btn-secondary" style="min-width:90px" onclick="Modal.close('modalConfirmar')">Cancelar</button>
            <button class="btn btn-primary" id="confirmarOkBtn" style="min-width:90px;background:var(--danger);border-color:var(--danger)">Eliminar</button>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
// ── Confirm personalizado ─────────────────────────────────────────
function confirmar(mensaje, labelOk, onOk) {
    if (typeof labelOk === 'function') { onOk = labelOk; labelOk = 'Confirmar'; }
    document.getElementById('confirmarMensaje').textContent = mensaje;
    document.getElementById('confirmarOkBtn').textContent = labelOk;
    const btn = document.getElementById('confirmarOkBtn');
    const nuevo = btn.cloneNode(true);
    btn.parentNode.replaceChild(nuevo, btn);
    nuevo.addEventListener('click', () => { Modal.close('modalConfirmar'); onOk(); });
    Modal.open('modalConfirmar');
}

let catCategorias = [];
const CAT_IMG_BASE = '../../assets/uploads/catalogo/';
let catImgState = { existingFilename: null, file: null, removed: false };
// Colores del modelo: [{nombre, imagen(filename existente|null), file(File|null), preview(dataurl|null)}]
let catColores = [];
let catSpecs   = [];

// Plantilla de referencia: Aserradero Horizontal GTK23 PREMIUM-X
const CAT_SPECS_GTK23 = [
    { titulo: 'Potencia', descripcion: '', params: [
        { nombre: 'Eléctrico', valor: '15', unidad: 'HP', descripcion: '220 / 380 / 400 V' }
    ]},
    { titulo: 'Sierra', descripcion: '', params: [
        { nombre: 'Longitud de la sierra',      valor: '4,010',         unidad: 'mm',    descripcion: '158"' },
        { nombre: 'Sistema de guiasierra',       valor: 'Rodillos-guía', unidad: '',      descripcion: 'Para cortes más estables y precisos' },
        { nombre: 'Lubricación de la sierra',    valor: 'Tanque de agua',unidad: '',      descripcion: 'Con válvula de activación/desactivación' },
        { nombre: 'Sistema tensor de la sierra', valor: 'Tensado sencillo con tornillos', unidad: '', descripcion: '' },
        { nombre: 'Espesor de la sierra',        valor: '1',             unidad: 'mm',    descripcion: '0.042"' },
        { nombre: 'Ancho de la sierra',          valor: '32',            unidad: 'mm',    descripcion: '1 1/4"' },
        { nombre: 'Tamaño de guiasierra',        valor: '31.75 / 38.10', unidad: 'mm',    descripcion: '1 1/4" y 1 1/2"' }
    ]},
    { titulo: 'Características del cabezal', descripcion: '', params: [
        { nombre: 'Posición del operador',           valor: 'Avance manual',     unidad: '', descripcion: '' },
        { nombre: 'Movimiento horizontal',           valor: 'Avance de empuje',  unidad: '', descripcion: '' },
        { nombre: 'Movimiento vertical del cabezal', valor: 'Sistema automático con tornillos', unidad: '', descripcion: '' },
        { nombre: 'Brazo del guiasierra',            valor: 'Ajustable',         unidad: '', descripcion: 'Con palanca manual' },
        { nombre: 'Ancho del cabezal',               valor: '2.00',              unidad: 'm', descripcion: '' },
        { nombre: 'Altura del cabezal',              valor: '1.65',              unidad: 'm', descripcion: '' },
        { nombre: 'Longitud del cabezal',            valor: '0.90',              unidad: 'm', descripcion: '' }
    ]},
    { titulo: 'Poleas portasierras', descripcion: '', params: [
        { nombre: 'Diámetro de polea portasierras', valor: '457.2', unidad: 'mm', descripcion: '18"' },
        { nombre: 'Tipo de polea', valor: 'Portasierras con fajas', unidad: '', descripcion: 'Mayor estabilidad y transmisión eficiente de potencia' }
    ]},
    { titulo: 'Capacidad de corte', descripcion: '', params: [
        { nombre: 'Ancho máximo de corte',      valor: '66',          unidad: 'cm',   descripcion: '26"' },
        { nombre: 'Diámetro máximo del tronco', valor: '71',          unidad: 'cm',   descripcion: '28"' },
        { nombre: 'Velocidad de producción',    valor: 'Hasta 0.75',  unidad: 'm³/h', descripcion: '' },
        { nombre: 'Profundidad máx. de corte',  valor: '26',          unidad: 'cm',   descripcion: '10 1/2"' },
        { nombre: 'Longitud máx. del tronco',   valor: '4.9',         unidad: 'm',    descripcion: '167", extensiones ilimitadas opcionales' },
        { nombre: 'Peso máximo del tronco',     valor: '1,814',       unidad: 'kg',   descripcion: '4,000 lbs' }
    ]},
    { titulo: 'Características de la cama', descripcion: '', params: [
        { nombre: 'Soportes laterales',          valor: '2 soportes reforzados',    unidad: '',       descripcion: 'Platinas 3/8"' },
        { nombre: 'Ancho total de la bancada',   valor: '85',                       unidad: 'cm',     descripcion: '' },
        { nombre: 'Longitud de la bancada',      valor: '6',                        unidad: 'm',      descripcion: 'Dos piezas de 3 m; extensión opcional' },
        { nombre: 'Durmientes',                  valor: 'Canal U 2"×6"×1/4" + tubo 2×6×3mm', unidad: '', descripcion: 'Base sólida' },
        { nombre: 'Niveladores de tronco',       valor: 'N/A',                      unidad: '',       descripcion: '' },
        { nombre: 'Rotor de troncos',            valor: 'Manual',                   unidad: '',       descripcion: 'Facilita manipulación' },
        { nombre: 'Cargador de troncos',         valor: 'Manual',                   unidad: '',       descripcion: 'Carga segura y efectiva' },
        { nombre: 'Paquete de remolque',         valor: 'Opcional',                 unidad: '',       descripcion: 'Sistema de transporte incorporable' },
        { nombre: 'Prensas para troncos',        valor: '3',                        unidad: 'unid.',  descripcion: 'Ajustables, estabilidad en el corte' },
        { nombre: 'Extensiones de bancada',      valor: '3',                        unidad: 'm c/u',  descripcion: 'Para troncos de mayor longitud' }
    ]},
    { titulo: 'Dimensiones y requisitos', descripcion: '', params: [
        { nombre: 'Construcción de la bancada', valor: 'Doble riel',          unidad: '', descripcion: 'Mayor estabilidad' },
        { nombre: 'Construcción del marco',     valor: 'Semi-voladizo',       unidad: '', descripcion: '1.60 m de altura' },
        { nombre: 'Dimensiones generales',      valor: '0.90 × 2.20 × 1.60', unidad: 'm', descripcion: '' },
        { nombre: 'Peso de la máquina',         valor: '550',                 unidad: 'kg', descripcion: 'Opción potencia más pesada 500 kg' }
    ]}
];
function esc(s) { const d = document.createElement('div'); d.textContent = String(s ?? ''); return d.innerHTML; }

let leadUsuarios = [];

document.addEventListener('DOMContentLoaded', () => {
    initTabs('mainTabs');
    cargarUsuariosLead();
    cargarCategoriasCatalogo().then(() => {
        cargarCatalogo();
        cargarLeads();
    });
    ['catSearch','catPublicado'].forEach(id => {
        document.getElementById(id).addEventListener('input', debounce(cargarCatalogo, 350));
        document.getElementById(id).addEventListener('change', cargarCatalogo);
    });
    document.getElementById('catCategoria').addEventListener('change', cargarCatalogo);
    ['leadSearch','leadEstado','leadOrigen'].forEach(id => {
        document.getElementById(id).addEventListener('input', debounce(cargarLeads, 350));
        document.getElementById(id).addEventListener('change', cargarLeads);
    });
});

function debounce(fn, d) { let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), d); }; }

async function cargarCategoriasCatalogo() {
    const d = await apiGet('api.php?action=categorias_catalogo');
    if (!d.ok) return;
    catCategorias = d.categorias;
    const opts = '<option value="">Todas las categorías</option>' + d.categorias.map(c => `<option value="${c}">${c}</option>`).join('');
    document.getElementById('catCategoria').innerHTML = opts;
    document.getElementById('catCatList').innerHTML = d.categorias.map(c => `<option value="${c}">`).join('');
}

async function cargarCatalogo() {
    const q   = document.getElementById('catSearch').value;
    const cat = document.getElementById('catCategoria').value;
    const pub = document.getElementById('catPublicado').value;
    const d = await apiGet(`api.php?action=catalogo_listar&q=${encodeURIComponent(q)}&categoria=${encodeURIComponent(cat)}&publicado=${pub}`);
    if (!d.ok) return;
    const grid = document.getElementById('gridCatalogo');
    if (!d.productos.length) {
        grid.innerHTML = '<p class="text-secondary" style="grid-column:1/-1;text-align:center;padding:40px">Sin productos en el catálogo</p>';
        return;
    }
    grid.innerHTML = d.productos.map(p => {
        // Parsear specs (JSONB llega como string desde PHP)
        let specs = p.especificaciones || [];
        if (typeof specs === 'string') { try { specs = JSON.parse(specs) || []; } catch(e) { specs = []; } }
        specs = specs.filter(g => g.titulo);

        // Colores
        let colores = p.colores || [];
        if (typeof colores === 'string') { try { colores = JSON.parse(colores) || []; } catch(e) { colores = []; } }
        colores = colores.filter(c => c.nombre);

        // Bloque de colores disponibles
        const coloresHtml = colores.length ? `
            <div style="padding:0 16px 12px;display:flex;flex-wrap:wrap;gap:6px">
                ${colores.map(c => `
                    <span style="display:inline-flex;align-items:center;gap:5px;background:#f1f5f9;border:1px solid var(--border);border-radius:20px;padding:2px 10px;font-size:0.74rem;color:#475569">
                        ${c.imagen ? `<img src="${CAT_IMG_BASE}${c.imagen}" style="width:16px;height:16px;border-radius:50%;object-fit:cover">` : `<span style="width:12px;height:12px;border-radius:50%;background:#94a3b8;display:inline-block"></span>`}
                        ${esc(c.nombre)}
                    </span>`).join('')}
            </div>` : '';

        // Ficha técnica: acordeón de grupos
        const fichaHtml = specs.length ? `
            <div style="border-top:1px solid var(--border)">
                <button type="button"
                    onclick="event.stopPropagation();this.nextElementSibling.style.display=this.nextElementSibling.style.display==='none'?'block':'none';this.querySelector('i').className=this.nextElementSibling.style.display==='block'?'fa fa-chevron-up':'fa fa-chevron-down'"
                    style="width:100%;padding:8px 14px;border:none;background:#f8fafc;cursor:pointer;text-align:left;font-size:0.8rem;font-weight:600;color:#374151;display:flex;align-items:center;justify-content:space-between;gap:8px">
                    <span><i class="fa fa-list-check" style="color:var(--primary);margin-right:6px"></i>Ficha técnica <span style="font-weight:400;color:#64748b">(${specs.length} grupo${specs.length>1?'s':''})</span></span>
                    <i class="fa fa-chevron-down" style="color:#94a3b8;font-size:0.75rem"></i>
                </button>
                <div style="display:none">
                    ${specs.map(g => `
                    <details style="border-bottom:1px solid #f1f5f9" onclick="event.stopPropagation()">
                        <summary style="list-style:none;padding:7px 14px;cursor:pointer;font-size:0.8rem;font-weight:600;color:#1e293b;background:#fafafa;display:flex;align-items:center;justify-content:space-between;user-select:none;outline:none">
                            <span>
                                <i class="fa fa-angle-right" style="margin-right:6px;color:#94a3b8;transition:transform .2s" class="sum-ico"></i>
                                ${esc(g.titulo)}
                            </span>
                            <span style="font-size:0.72rem;color:#94a3b8;font-weight:400">${(g.params||[]).length} parámetro${(g.params||[]).length!==1?'s':''}</span>
                        </summary>
                        ${g.descripcion ? `<p style="margin:0;padding:4px 14px 0;font-size:0.77rem;color:#64748b;font-style:italic">${esc(g.descripcion)}</p>` : ''}
                        <div style="padding:6px 14px 10px;overflow-x:auto">
                            <table style="width:100%;border-collapse:collapse;font-size:0.76rem;min-width:300px">
                                ${(g.params||[]).map(p2 => `
                                <tr style="border-bottom:1px solid #f1f5f9">
                                    <td style="padding:4px 8px 4px 0;color:#475569;font-weight:500;width:45%;vertical-align:top">${esc(p2.nombre)}</td>
                                    <td style="padding:4px 0;color:#0f172a;font-weight:600;vertical-align:top">
                                        ${esc(p2.valor)}${p2.unidad ? `<span style="font-weight:400;color:#64748b;margin-left:3px">${esc(p2.unidad)}</span>` : ''}
                                        ${p2.descripcion ? `<div style="font-size:0.72rem;color:#94a3b8;font-weight:400">${esc(p2.descripcion)}</div>` : ''}
                                    </td>
                                </tr>`).join('')}
                            </table>
                        </div>
                    </details>`).join('')}
                </div>
            </div>` : '';

        return `
        <div class="card" style="padding:0;overflow:hidden;display:flex;flex-direction:column;cursor:pointer" onclick="editarCatalogo(${p.id})">
            ${p.imagen_principal ? `<div style="height:160px;overflow:hidden;background:#f1f5f9;flex:0 0 auto"><img src="${CAT_IMG_BASE}${p.imagen_principal}" style="width:100%;height:100%;object-fit:cover" loading="lazy"></div>` : ''}
            <div style="background:${p.publicado?'#f0fdf4':'#f8fafc'};padding:8px 12px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border);flex:0 0 auto">
                <span class="badge ${p.publicado?'badge-success':'badge-secondary'}">${p.publicado?'Publicado':'Borrador'}</span>
                ${p.destacado ? '<span class="badge badge-warning"><i class="fa fa-star"></i> Destacado</span>' : ''}
            </div>
            <div style="padding:16px 16px 12px;flex:1">
                <div style="font-weight:600;margin-bottom:4px">${esc(p.nombre)}</div>
                ${p.categoria ? `<div style="font-size:0.78rem;color:#64748b;margin-bottom:6px">${esc(p.categoria)}</div>` : ''}
                ${p.descripcion_corta ? `<div style="font-size:0.83rem;color:#475569;margin-bottom:8px;line-height:1.5">${esc(p.descripcion_corta)}</div>` : ''}
                ${p.precio_referencial ? `<div style="font-weight:700;color:var(--primary);font-size:1.05rem">${formatMoney(p.precio_referencial)}</div>` : ''}
            </div>
            ${coloresHtml}
            ${fichaHtml}
            <div style="padding:8px 12px;border-top:1px solid var(--border);display:flex;gap:6px;flex:0 0 auto" onclick="event.stopPropagation()">
                <button class="btn btn-secondary" style="flex:1;padding:4px;font-size:0.78rem" onclick="editarCatalogo(${p.id})">
                    <i class="fa fa-edit"></i> Editar
                </button>
                <button class="btn btn-secondary" style="padding:4px 8px;font-size:0.78rem;color:var(--danger)" onclick="eliminarCatalogo(${p.id})">
                    <i class="fa fa-trash"></i>
                </button>
            </div>
        </div>`;
    }).join('');
}

function abrirModalCatalogo() {
    document.getElementById('cat_id').value = '';
    document.getElementById('cat_nombre').value = '';
    document.getElementById('cat_cat').value = '';
    document.getElementById('cat_precio').value = '';
    document.getElementById('cat_dcorta').value = '';
    document.getElementById('cat_desc').value = '';
    document.getElementById('cat_orden').value = '0';
    document.getElementById('cat_publicado').checked = false;
    document.getElementById('cat_destacado').checked = false;
    document.getElementById('modalCatTitle').textContent = 'Nuevo Producto';
    catImgReset();
    catColoresReset([]);
    catSpecsReset([]);
    // Colapsar specs al abrir nuevo
    document.getElementById('catSpecsWrap').style.display = 'none';
    document.getElementById('catSpecsIco').className = 'fa fa-chevron-down';
    document.getElementById('catSpecsLbl').textContent = 'Mostrar';
    Modal.open('modalCatalogo');
}

async function editarCatalogo(id) {
    const d = await apiGet(`api.php?action=catalogo_obtener&id=${id}`);
    if (!d.ok) return;
    const p = d.producto;
    document.getElementById('cat_id').value = p.id;
    document.getElementById('cat_nombre').value = p.nombre;
    document.getElementById('cat_cat').value = p.categoria || '';
    document.getElementById('cat_precio').value = p.precio_referencial || '';
    document.getElementById('cat_dcorta').value = p.descripcion_corta || '';
    document.getElementById('cat_desc').value = p.descripcion || '';
    document.getElementById('cat_orden').value = p.orden || 0;
    document.getElementById('cat_publicado').checked = p.publicado;
    document.getElementById('cat_destacado').checked = p.destacado;
    document.getElementById('modalCatTitle').textContent = 'Editar Producto';
    catImgState = { existingFilename: p.imagen_principal || null, file: null, removed: false };
    if (p.imagen_principal) catImgSetPreview(CAT_IMG_BASE + p.imagen_principal);
    else catImgReset();
    let coloresP = p.colores || [];
    if (typeof coloresP === 'string') { try { coloresP = JSON.parse(coloresP) || []; } catch (e) { coloresP = []; } }
    catColoresReset(coloresP);
    let specsP = p.especificaciones || [];
    if (typeof specsP === 'string') { try { specsP = JSON.parse(specsP) || []; } catch (e) { specsP = []; } }
    // Colapsar antes de resetear para que catSpecsReset lo abra solo si hay datos
    document.getElementById('catSpecsWrap').style.display = 'none';
    document.getElementById('catSpecsIco').className = 'fa fa-chevron-down';
    document.getElementById('catSpecsLbl').textContent = 'Mostrar';
    catSpecsReset(specsP);
    Modal.open('modalCatalogo');
}

async function guardarCatalogo() {
    const nombre = document.getElementById('cat_nombre').value.trim();
    if (!nombre) { Toast.warning('El nombre es obligatorio'); return; }
    // Sube las imagenes de color nuevas y arma la lista [{nombre, imagen}].
    const colores = [];
    for (const c of catColores) {
        if (!(c.nombre || '').trim()) continue;
        let imagen = c.imagen || null;
        if (c.file) {
            const fd = new FormData();
            fd.append('action', 'catalogo_color_img');
            fd.append('imagen', c.file);
            const up = await fetch('api.php', { method: 'POST', body: fd }).then(x => x.json());
            if (up.ok) imagen = up.filename;
            else { Toast.error(up.error || 'Error al subir imagen de color'); return; }
        }
        colores.push({ nombre: c.nombre.trim(), imagen });
    }
    const body = {
        action:              'catalogo_guardar',
        id:                  document.getElementById('cat_id').value || null,
        nombre,
        categoria:           document.getElementById('cat_cat').value,
        precio_referencial:  document.getElementById('cat_precio').value || null,
        descripcion_corta:   document.getElementById('cat_dcorta').value,
        descripcion:         document.getElementById('cat_desc').value,
        orden:               document.getElementById('cat_orden').value,
        publicado:           document.getElementById('cat_publicado').checked,
        destacado:           document.getElementById('cat_destacado').checked,
        colores,
        especificaciones:    catSpecs,
    };
    const r = await apiPost('api.php', body);
    if (!r.ok) { Toast.error(r.error || 'Error'); return; }
    if (catImgState.file) {
        const fd = new FormData();
        fd.append('action', 'catalogo_img_guardar');
        fd.append('id', r.id);
        fd.append('imagen', catImgState.file);
        await fetch('api.php', { method: 'POST', body: fd });
    } else if (catImgState.removed && catImgState.existingFilename) {
        await apiPost('api.php', { action: 'catalogo_img_eliminar', id: r.id });
    }
    Toast.success('Producto guardado');
    Modal.close('modalCatalogo');
    cargarCatalogo();
    cargarCategoriasCatalogo();
}

async function togglePublicado(id) {
    const r = await apiPost('api.php', { action: 'catalogo_toggle_publicado', id });
    if (r.ok) { cargarCatalogo(); }
}

function eliminarCatalogo(id) {
    confirmar('¿Eliminar este producto del catálogo? Esta acción no se puede deshacer.', 'Eliminar', async () => {
        const r = await apiPost('api.php', { action: 'catalogo_eliminar', id });
        if (r.ok) { Toast.success('Eliminado'); cargarCatalogo(); }
        else Toast.error(r.error || 'Error');
    });
}

// LEADS
async function cargarLeads() {
    const q      = document.getElementById('leadSearch').value;
    const estado = document.getElementById('leadEstado').value;
    const origen = document.getElementById('leadOrigen').value;
    const d = await apiGet(`api.php?action=leads_listar&q=${encodeURIComponent(q)}&estado=${estado}&origen=${origen}`);
    if (!d.ok) return;

    const estadoBadge = { nuevo:'badge-primary', contactado:'badge-secondary', calificado:'badge-warning', convertido:'badge-success', descartado:'badge-danger' };
    const origenIco = { web:'fa-globe', facebook:'fa-square-facebook', instagram:'fa-instagram', tiktok:'fa-tiktok', youtube:'fa-youtube', directo:'fa-user' };

    const hoy = new Date().toISOString().slice(0,10);
    const tbody = document.querySelector('#tablaLeads tbody');
    tbody.innerHTML = d.leads.map(l => {
        const nInt = parseInt(l.num_interacciones || 0);
        let proxima = '<span class="text-secondary">—</span>';
        if (l.proxima_accion || l.proxima_fecha) {
            const vencida = l.proxima_fecha && l.proxima_fecha < hoy && l.estado !== 'convertido';
            const col = vencida ? '#dc2626' : '#0f172a';
            proxima = `<div style="font-size:0.82rem;color:${col}">
                ${l.proxima_fecha ? `<i class="fa fa-calendar" style="margin-right:4px"></i>${formatDate(l.proxima_fecha)}${vencida?' <strong>(vencida)</strong>':''}<br>` : ''}
                <span style="color:#475569">${l.proxima_accion ? esc(l.proxima_accion) : ''}</span>
            </div>`;
        }
        return `<tr>
        <td><strong>${l.nombre || '—'}</strong>${l.responsable_nombre ? `<div style="font-size:0.72rem;color:#94a3b8"><i class="fa fa-user-tie"></i> ${esc(l.responsable_nombre)}</div>` : ''}</td>
        <td>${l.empresa || '—'}</td>
        <td>
            ${l.email ? `<a href="mailto:${l.email}" style="color:var(--primary)">${l.email}</a><br>` : ''}
            ${l.telefono ? `<a href="https://wa.me/${l.telefono.replace(/\D/g,'')}" target="_blank" style="color:#25D366">${l.telefono}</a>` : ''}
        </td>
        <td><i class="fa ${origenIco[l.origen]||'fa-question'}"></i> ${l.origen || '—'}</td>
        <td><span class="badge ${estadoBadge[l.estado]||'badge-secondary'}">${l.estado}</span></td>
        <td>${proxima}</td>
        <td>${formatDate(l.created_at)}</td>
        <td>
            <button class="btn btn-secondary" style="padding:3px 8px;font-size:0.78rem" onclick="abrirSeguimiento(${l.id})" title="Seguimiento">
                <i class="fa fa-clock-rotate-left"></i>${nInt>0?` <span style="font-size:0.7rem">${nInt}</span>`:''}
            </button>
            <button class="btn btn-secondary" style="padding:3px 8px;font-size:0.78rem" onclick="editarLead(${l.id})">
                <i class="fa fa-edit"></i>
            </button>
            ${l.estado !== 'convertido' ? `
            <button class="btn btn-primary" style="padding:3px 8px;font-size:0.78rem;background:#22c55e" onclick="convertirLead(${l.id})" title="Convertir a cliente">
                <i class="fa fa-user-plus"></i>
            </button>` : `<span class="badge badge-success"><i class="fa fa-check"></i> Cliente</span>`}
            <button class="btn btn-secondary" style="padding:3px 8px;font-size:0.78rem;color:var(--danger)" onclick="eliminarLead(${l.id})">
                <i class="fa fa-trash"></i>
            </button>
        </td>
    </tr>`; }).join('') || '<tr><td colspan="8" class="text-secondary text-center" style="padding:20px">Sin leads</td></tr>';
}

async function cargarUsuariosLead() {
    const d = await apiGet('api.php?action=usuarios_combo');
    if (!d.ok) return;
    leadUsuarios = d.usuarios || [];
    document.getElementById('lead_responsable').innerHTML =
        '<option value="">— Sin asignar —</option>' +
        leadUsuarios.map(u => `<option value="${u.id}">${esc(u.nombre)}</option>`).join('');
}

function abrirModalLead() {
    document.getElementById('lead_id').value = '';
    document.getElementById('lead_nombre').value = '';
    document.getElementById('lead_empresa').value = '';
    document.getElementById('lead_email').value = '';
    document.getElementById('lead_telefono').value = '';
    document.getElementById('lead_origen').value = 'directo';
    document.getElementById('lead_estado').value = 'nuevo';
    document.getElementById('lead_mensaje').value = '';
    document.getElementById('lead_responsable').value = '';
    document.getElementById('lead_paccion').value = '';
    document.getElementById('lead_pfecha').value = '';
    document.getElementById('modalLeadTitle').textContent = 'Nuevo Lead';
    Modal.open('modalLead');
}

async function editarLead(id) {
    const d = await apiGet(`api.php?action=lead_obtener&id=${id}`);
    if (!d.ok) return;
    const lead = d.lead;
    document.getElementById('lead_id').value = lead.id;
    document.getElementById('lead_nombre').value = lead.nombre || '';
    document.getElementById('lead_empresa').value = lead.empresa || '';
    document.getElementById('lead_email').value = lead.email || '';
    document.getElementById('lead_telefono').value = lead.telefono || '';
    document.getElementById('lead_origen').value = lead.origen || 'directo';
    document.getElementById('lead_estado').value = lead.estado || 'nuevo';
    document.getElementById('lead_mensaje').value = lead.mensaje || '';
    document.getElementById('lead_responsable').value = lead.responsable_id || '';
    document.getElementById('lead_paccion').value = lead.proxima_accion || '';
    document.getElementById('lead_pfecha').value = lead.proxima_fecha || '';
    document.getElementById('modalLeadTitle').textContent = 'Editar Lead';
    Modal.open('modalLead');
}

async function guardarLead() {
    const body = {
        action:          'lead_guardar',
        id:              document.getElementById('lead_id').value || null,
        nombre:          document.getElementById('lead_nombre').value,
        empresa:         document.getElementById('lead_empresa').value,
        email:           document.getElementById('lead_email').value,
        telefono:        document.getElementById('lead_telefono').value,
        origen:          document.getElementById('lead_origen').value,
        estado:          document.getElementById('lead_estado').value,
        mensaje:         document.getElementById('lead_mensaje').value,
        responsable_id:  document.getElementById('lead_responsable').value || null,
        proxima_accion:  document.getElementById('lead_paccion').value,
        proxima_fecha:   document.getElementById('lead_pfecha').value || null,
    };
    const r = await apiPost('api.php', body);
    if (r.ok) { Toast.success('Lead guardado'); Modal.close('modalLead'); cargarLeads(); }
    else Toast.error(r.error || 'Error');
}

// ── Seguimiento / bitácora de leads ───────────────────────────────
let segLeadId = null;
const SEG_TIPOS = {
    llamada:  { ico:'fa-solid fa-phone',       col:'#0ea5e9', lbl:'Llamada' },
    whatsapp: { ico:'fa-brands fa-whatsapp',   col:'#25D366', lbl:'WhatsApp' },
    email:    { ico:'fa-solid fa-envelope',    col:'#8b5cf6', lbl:'Email' },
    visita:   { ico:'fa-solid fa-handshake',   col:'#f97316', lbl:'Visita / Reunión' },
    nota:     { ico:'fa-solid fa-note-sticky', col:'#64748b', lbl:'Nota' },
};

async function abrirSeguimiento(id) {
    segLeadId = id;
    document.getElementById('seg_lead_id').value = id;
    document.getElementById('seg_tipo').value = 'llamada';
    document.getElementById('seg_nota').value = '';
    Modal.open('modalSeguimiento');
    await cargarSeguimiento();
}

async function cargarSeguimiento() {
    const d = await apiGet(`api.php?action=lead_obtener&id=${segLeadId}`);
    if (!d.ok) return;
    const lead = d.lead || {};
    document.getElementById('seg_lead_nombre').textContent = lead.nombre || 'Lead';
    const hoy = new Date().toISOString().slice(0,10);
    const vencida = lead.proxima_fecha && lead.proxima_fecha < hoy && lead.estado !== 'convertido';
    document.getElementById('seg_resumen').innerHTML = `
        <div><strong>Estado:</strong> ${esc(lead.estado||'—')} &nbsp;·&nbsp; <strong>Responsable:</strong> ${lead.responsable_nombre ? esc(lead.responsable_nombre) : '<span style="color:#94a3b8">Sin asignar</span>'}</div>
        <div style="margin-top:4px"><strong>Próxima acción:</strong> ${lead.proxima_accion ? esc(lead.proxima_accion) : '—'}
            ${lead.proxima_fecha ? `<span style="color:${vencida?'#dc2626':'#475569'};margin-left:6px"><i class="fa fa-calendar"></i> ${formatDate(lead.proxima_fecha)}${vencida?' (vencida)':''}</span>` : ''}</div>`;

    const items = d.interacciones || [];
    const tl = document.getElementById('seg_timeline');
    if (!items.length) { tl.innerHTML = '<p class="text-secondary" style="font-size:0.85rem">Sin interacciones registradas todavía.</p>'; return; }
    tl.innerHTML = items.map(it => {
        const t = SEG_TIPOS[it.tipo] || SEG_TIPOS.nota;
        return `<div style="display:flex;gap:10px;padding:10px 0;border-bottom:1px solid #f1f5f9">
            <div style="width:30px;height:30px;border-radius:50%;background:${t.col}1a;color:${t.col};display:flex;align-items:center;justify-content:center;flex:0 0 auto"><i class="${t.ico}"></i></div>
            <div style="flex:1">
                <div style="font-size:0.85rem">${esc(it.nota)}</div>
                <div style="font-size:0.72rem;color:#94a3b8;margin-top:2px">
                    ${t.lbl} · ${formatDateTime(it.created_at)}${it.usuario_nombre?` · ${esc(it.usuario_nombre)}`:''}
                </div>
            </div>
            <button class="btn btn-secondary" style="padding:2px 7px;font-size:0.72rem;color:var(--danger);height:fit-content" onclick="eliminarInteraccion(${it.id})"><i class="fa fa-trash"></i></button>
        </div>`;
    }).join('');
}

async function guardarInteraccion() {
    const nota = document.getElementById('seg_nota').value.trim();
    if (!nota) { Toast.warning('Escribe una nota.'); return; }
    const r = await apiPost('api.php', {
        action: 'lead_interaccion_guardar',
        lead_id: segLeadId,
        tipo: document.getElementById('seg_tipo').value,
        nota,
    });
    if (r.ok) { document.getElementById('seg_nota').value = ''; cargarSeguimiento(); cargarLeads(); }
    else Toast.error(r.error || 'Error');
}

function eliminarInteraccion(id) {
    confirmar('¿Eliminar esta interacción?', 'Eliminar', async () => {
        const r = await apiPost('api.php', { action: 'lead_interaccion_eliminar', id });
        if (r.ok) { cargarSeguimiento(); cargarLeads(); }
        else Toast.error(r.error || 'Error');
    });
}

function formatDateTime(ts) {
    if (!ts) return '';
    const d = new Date(String(ts).replace(' ', 'T'));
    if (isNaN(d)) return formatDate(ts);
    return d.toLocaleString('es-PE', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' });
}

function convertirLead(id) {
    confirmar('¿Convertir este lead en cliente? Se creará un registro en Clientes.', 'Convertir', async () => {
        const r = await apiPost('api.php', { action: 'lead_convertir', id });
        if (r.ok) { Toast.success('Lead convertido en cliente'); cargarLeads(); }
        else Toast.error(r.error || 'Error');
    });
}

function eliminarLead(id) {
    confirmar('¿Eliminar este lead? Esta acción no se puede deshacer.', 'Eliminar', async () => {
        const r = await apiPost('api.php', { action: 'lead_eliminar', id });
        if (r.ok) { Toast.success('Lead eliminado'); cargarLeads(); }
        else Toast.error(r.error || 'Error');
    });
}

// ── Imagen de producto en catálogo ────────────────────────────────
function catImgChange(input) {
    const file = input.files[0];
    if (!file) return;
    catImgState.file = file;
    catImgState.removed = false;
    const reader = new FileReader();
    reader.onload = e => catImgSetPreview(e.target.result);
    reader.readAsDataURL(file);
}
function catImgSetPreview(src) {
    document.getElementById('catImgPreview').src = src;
    document.getElementById('catImgPreview').style.display = '';
    document.getElementById('catImgPlaceholder').style.display = 'none';
    document.getElementById('catImgQuit').style.display = '';
}
function catImgClear() {
    catImgState.file = null;
    catImgState.removed = true;
    document.getElementById('catImgPreview').style.display = 'none';
    document.getElementById('catImgPreview').src = '';
    document.getElementById('catImgPlaceholder').style.display = '';
    document.getElementById('catImgQuit').style.display = 'none';
    document.getElementById('catImgInput').value = '';
}
function catImgReset() {
    catImgState = { existingFilename: null, file: null, removed: false };
    document.getElementById('catImgPreview').style.display = 'none';
    document.getElementById('catImgPreview').src = '';
    document.getElementById('catImgPlaceholder').style.display = '';
    document.getElementById('catImgQuit').style.display = 'none';
    document.getElementById('catImgInput').value = '';
}

// ── Colores del modelo (con imagen por color) ─────────────────────
function catColoresRender() {
    const cont = document.getElementById('catColoresList');
    if (!catColores.length) { cont.innerHTML = '<div style="font-size:0.8rem;color:#cbd5e1;margin:4px 0">Sin colores. Agrega al menos uno si quieres que la máquina elija color.</div>'; return; }
    cont.innerHTML = catColores.map((c, i) => {
        const src = c.preview || (c.imagen ? CAT_IMG_BASE + c.imagen : '');
        const thumb = src
            ? `<img src="${src}" style="width:44px;height:44px;object-fit:cover;border-radius:6px">`
            : `<i class="fa fa-image" style="color:#cbd5e1"></i>`;
        return `<div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
            <div style="width:44px;height:44px;border:1px dashed #cbd5e1;border-radius:6px;display:flex;align-items:center;justify-content:center;cursor:pointer;overflow:hidden;flex:0 0 auto" onclick="document.getElementById('catColorImg${i}').click()">${thumb}</div>
            <input type="file" id="catColorImg${i}" accept=".jpg,.jpeg,.png,.webp" style="display:none" onchange="catColorImg(${i}, this)">
            <input type="text" class="form-control" style="flex:1" placeholder="Nombre del color (ej. Naranja)" value="${(c.nombre||'').replace(/"/g,'&quot;')}" oninput="catColores[${i}].nombre=this.value">
            <button type="button" class="btn btn-secondary" style="padding:4px 9px;color:var(--danger)" onclick="catColorQuitar(${i})"><i class="fa fa-trash"></i></button>
        </div>`;
    }).join('');
}
function catColorAgregar() {
    catColores.push({ nombre: '', imagen: null, file: null, preview: null });
    catColoresRender();
}
function catColorQuitar(i) {
    catColores.splice(i, 1);
    catColoresRender();
}
function catColorImg(i, input) {
    const file = input.files[0];
    if (!file) return;
    catColores[i].file = file;
    const reader = new FileReader();
    reader.onload = e => { catColores[i].preview = e.target.result; catColoresRender(); };
    reader.readAsDataURL(file);
}
function catColoresReset(lista) {
    catColores = (lista || []).map(c => ({ nombre: c.nombre || '', imagen: c.imagen || null, file: null, preview: null }));
    catColoresRender();
}

// ── Especificaciones Técnicas ─────────────────────────────────────
function catSpecsToggle() {
    const wrap = document.getElementById('catSpecsWrap');
    const ico  = document.getElementById('catSpecsIco');
    const lbl  = document.getElementById('catSpecsLbl');
    const abre = wrap.style.display === 'none';
    wrap.style.display = abre ? 'block' : 'none';
    ico.className  = abre ? 'fa fa-chevron-up'   : 'fa fa-chevron-down';
    lbl.textContent = abre ? 'Ocultar'            : 'Mostrar';
    if (abre) catSpecsRender();
}

function catSpecsReset(lista) {
    catSpecs = (lista || []).map(g => ({
        titulo: g.titulo || '',
        descripcion: g.descripcion || '',
        params: (g.params || []).map(p => ({
            nombre: p.nombre || '', valor: p.valor || '',
            unidad: p.unidad || '', descripcion: p.descripcion || ''
        }))
    }));
    // Mostrar automáticamente si hay datos
    const wrap = document.getElementById('catSpecsWrap');
    if (catSpecs.length > 0 && wrap.style.display === 'none') catSpecsToggle();
    catSpecsRender();
}

function catSpecsCargarPlantilla() {
    if (catSpecs.length > 0) {
        confirmar('¿Reemplazar las especificaciones actuales con la plantilla GTK23?', 'Cargar plantilla', () => {
            catSpecsReset(JSON.parse(JSON.stringify(CAT_SPECS_GTK23)));
        });
    } else {
        catSpecsReset(JSON.parse(JSON.stringify(CAT_SPECS_GTK23)));
        if (document.getElementById('catSpecsWrap').style.display === 'none') catSpecsToggle();
    }
}

function catSpecsRender() {
    const cont = document.getElementById('catSpecsList');
    if (!cont) return;
    if (!catSpecs.length) {
        cont.innerHTML = '<p style="font-size:0.8rem;color:#94a3b8;padding:6px 0 2px">Sin grupos todavía. Agrega uno o carga la plantilla GTK23.</p>';
        return;
    }
    cont.innerHTML = catSpecs.map((g, gi) => `
        <div style="border:1px solid var(--border);border-radius:8px;margin-bottom:10px;background:#f8fafc;overflow:hidden">
            <!-- Cabecera del grupo -->
            <div style="display:flex;gap:8px;align-items:center;padding:10px 12px;border-bottom:1px solid var(--border);background:#f1f5f9">
                <input type="text" class="form-control" style="flex:1;font-weight:600;font-size:0.88rem"
                    placeholder="Título del grupo (ej. Potencia)"
                    value="${esc(g.titulo)}"
                    oninput="catSpecs[${gi}].titulo=this.value">
                <button type="button" class="btn btn-secondary" style="padding:3px 9px;color:var(--danger);flex:0 0 auto" title="Eliminar grupo" onclick="catSpecGrupoQuitar(${gi})">
                    <i class="fa fa-trash"></i>
                </button>
            </div>
            <!-- Descripción del grupo -->
            <div style="padding:8px 12px 0">
                <input type="text" class="form-control" style="font-size:0.8rem;color:#64748b"
                    placeholder="Descripción del grupo (opcional)"
                    value="${esc(g.descripcion)}"
                    oninput="catSpecs[${gi}].descripcion=this.value">
            </div>
            <!-- Tabla de parámetros -->
            <div style="padding:8px 12px;overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:0.78rem;min-width:520px">
                    <thead>
                        <tr style="background:#e2e8f0;color:#475569">
                            <th style="padding:5px 7px;text-align:left;border:1px solid #cbd5e1;width:26%">Parámetro</th>
                            <th style="padding:5px 7px;text-align:left;border:1px solid #cbd5e1;width:18%">Valor</th>
                            <th style="padding:5px 7px;text-align:left;border:1px solid #cbd5e1;width:12%">Unidad</th>
                            <th style="padding:5px 7px;text-align:left;border:1px solid #cbd5e1">Descripción complementaria</th>
                            <th style="padding:5px 7px;border:1px solid #cbd5e1;width:28px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        ${g.params.length === 0
                            ? `<tr><td colspan="5" style="padding:8px;color:#94a3b8;text-align:center;border:1px solid #cbd5e1;font-style:italic">Sin parámetros</td></tr>`
                            : g.params.map((p, pi) => `
                        <tr>
                            <td style="border:1px solid #cbd5e1;padding:1px 2px">
                                <input type="text" style="width:100%;border:none;background:transparent;padding:4px 6px;font-size:0.78rem;outline:none"
                                    placeholder="Nombre" value="${esc(p.nombre)}"
                                    oninput="catSpecs[${gi}].params[${pi}].nombre=this.value">
                            </td>
                            <td style="border:1px solid #cbd5e1;padding:1px 2px">
                                <input type="text" style="width:100%;border:none;background:transparent;padding:4px 6px;font-size:0.78rem;outline:none"
                                    placeholder="Valor" value="${esc(p.valor)}"
                                    oninput="catSpecs[${gi}].params[${pi}].valor=this.value">
                            </td>
                            <td style="border:1px solid #cbd5e1;padding:1px 2px">
                                <input type="text" style="width:100%;border:none;background:transparent;padding:4px 6px;font-size:0.78rem;outline:none"
                                    placeholder="mm, HP…" value="${esc(p.unidad)}"
                                    oninput="catSpecs[${gi}].params[${pi}].unidad=this.value">
                            </td>
                            <td style="border:1px solid #cbd5e1;padding:1px 2px">
                                <input type="text" style="width:100%;border:none;background:transparent;padding:4px 6px;font-size:0.78rem;outline:none"
                                    placeholder="Aclaración adicional…" value="${esc(p.descripcion)}"
                                    oninput="catSpecs[${gi}].params[${pi}].descripcion=this.value">
                            </td>
                            <td style="border:1px solid #cbd5e1;padding:1px;text-align:center">
                                <button type="button" onclick="catSpecParamQuitar(${gi},${pi})"
                                    style="border:none;background:none;color:#dc2626;cursor:pointer;padding:4px 6px;line-height:1" title="Eliminar parámetro">
                                    <i class="fa fa-times"></i>
                                </button>
                            </td>
                        </tr>`).join('')
                        }
                    </tbody>
                </table>
            </div>
            <div style="padding:6px 12px 10px">
                <button type="button" class="btn btn-secondary" style="font-size:0.75rem;padding:3px 10px" onclick="catSpecParamAgregar(${gi})">
                    <i class="fa fa-plus"></i> Agregar parámetro
                </button>
            </div>
        </div>`).join('');
}

function catSpecGrupoAgregar() {
    catSpecs.push({ titulo: '', descripcion: '', params: [] });
    catSpecsRender();
    // Enfocar el título del nuevo grupo
    setTimeout(() => {
        const inputs = document.querySelectorAll('#catSpecsList input[placeholder="Título del grupo (ej. Potencia)"]');
        if (inputs.length) inputs[inputs.length - 1].focus();
    }, 50);
}

function catSpecGrupoQuitar(gi) {
    catSpecs.splice(gi, 1);
    catSpecsRender();
}

function catSpecParamAgregar(gi) {
    catSpecs[gi].params.push({ nombre: '', valor: '', unidad: '', descripcion: '' });
    catSpecsRender();
    // Enfocar el nombre del nuevo parámetro
    setTimeout(() => {
        const rows = document.querySelectorAll(`#catSpecsList tbody tr`);
        if (rows.length) {
            const inp = rows[rows.length - 1].querySelector('input');
            if (inp) inp.focus();
        }
    }, 50);
}

function catSpecParamQuitar(gi, pi) {
    catSpecs[gi].params.splice(pi, 1);
    catSpecsRender();
}
</script>

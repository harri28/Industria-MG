<?php
$page_title      = 'Inventarios';
$page_breadcrumb = '<span>Operaciones</span> <span>/</span> Inventarios';
require_once __DIR__ . '/../../includes/header.php';
?>

<!-- TABS -->
<div class="tabs">
    <div class="tab active" data-group="inv" data-target="tabProductos">
        <i class="fa fa-boxes-stacked"></i> Materiales
    </div>
    <div class="tab" data-group="inv" data-target="tabRepuestos">
        <i class="fa fa-screwdriver-wrench"></i> Repuestos
    </div>
    <div class="tab" data-group="inv" data-target="tabKardex">
        <i class="fa fa-list"></i> Kardex
    </div>
    <div class="tab" data-group="inv" data-target="tabCategorias">
        <i class="fa fa-tags"></i> Categorias
    </div>
</div>

<!-- ============================================================
     TAB: MATERIALES (PRODUCTOS)
     ============================================================ -->
<div class="tab-content active" data-group="inv" id="tabProductos">
    <div class="toolbar">
        <div class="toolbar-left">
            <div class="search-box">
                <span class="icon"><i class="fa fa-search"></i></span>
                <input type="text" class="form-control" id="prodSearch" placeholder="Buscar codigo o nombre...">
            </div>
            <select class="form-control" id="prodCategoria" style="width:180px">
                <option value="">Todas las categorias</option>
            </select>
            <select class="form-control" id="prodRepuesto" style="width:140px">
                <option value="">Todos</option>
                <option value="false">Materiales</option>
                <option value="true">Repuestos</option>
            </select>
            <label style="display:flex;align-items:center;gap:6px;font-size:.85rem;cursor:pointer">
                <input type="checkbox" id="prodCritico"> Solo criticos
            </label>
        </div>
        <div class="toolbar-right">
            <button class="btn btn-primary" onclick="abrirModalProducto()">
                <i class="fa fa-plus"></i> Nuevo Material
            </button>
        </div>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table id="tablaprod">
                <thead><tr>
                    <th style="width:52px"></th>
                    <th>Codigo</th><th>Nombre</th><th>Categoria</th>
                    <th>Unidad</th><th>Stock</th><th>Min/Max</th>
                    <th>P. de Venta</th><th>Total</th><th></th>
                </tr></thead>
                <tbody>
                    <tr><td colspan="10" class="text-center">
                        <div class="loading"><div class="spinner"></div></div>
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================
     TAB: REPUESTOS
     ============================================================ -->
<div class="tab-content" data-group="inv" id="tabRepuestos">
    <div class="toolbar">
        <div class="toolbar-left">
            <div class="search-box">
                <span class="icon"><i class="fa fa-search"></i></span>
                <input type="text" class="form-control" id="repSearch" placeholder="Buscar codigo o nombre...">
            </div>
            <select class="form-control" id="repCategoria" style="width:180px">
                <option value="">Todas las categorias</option>
            </select>
            <label style="display:flex;align-items:center;gap:6px;font-size:.85rem;cursor:pointer">
                <input type="checkbox" id="repCritico"> Solo criticos
            </label>
        </div>
        <div class="toolbar-right">
            <button class="btn btn-primary" onclick="abrirModalRepuesto()">
                <i class="fa fa-plus"></i> Nuevo Repuesto
            </button>
        </div>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table id="tablarep">
                <thead><tr>
                    <th style="width:52px"></th>
                    <th>Codigo</th><th>Nombre</th><th>Categoria</th>
                    <th>Unidad</th><th>Stock</th><th>Min/Max</th>
                    <th>P. de Venta</th><th>Total</th><th></th>
                </tr></thead>
                <tbody>
                    <tr><td colspan="10" class="text-center">
                        <div class="loading"><div class="spinner"></div></div>
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================
     TAB: KARDEX
     ============================================================ -->
<div class="tab-content" data-group="inv" id="tabKardex">
    <div class="toolbar">
        <div class="toolbar-left">
            <select class="form-control" id="kardexProducto" style="width:300px">
                <option value="">— Seleccionar material —</option>
            </select>
            <input type="date" class="form-control" id="kardexDesde" style="width:150px">
            <input type="date" class="form-control" id="kardexHasta" style="width:150px">
            <select class="form-control" id="kardexTipo" style="width:130px">
                <option value="">Todos los tipos</option>
                <option value="entrada">Entrada</option>
                <option value="salida">Salida</option>
                <option value="ajuste">Ajuste</option>
            </select>
        </div>
        <div class="toolbar-right">
            <button class="btn btn-primary" onclick="cargarKardex()">
                <i class="fa fa-search"></i> Consultar
            </button>
        </div>
    </div>

    <div id="kardexInfo" style="display:none;background:var(--primary);color:#fff;padding:.75rem 1rem;margin-bottom:.75rem;border-radius:var(--radius);display:none;align-items:center;gap:1.5rem">
        <strong id="kardexProductoNombre"></strong>
        <span>Stock: <strong id="kardexStockActual"></strong></span>
        <span>P. Prom.: <strong id="kardexPrecioPromedio"></strong></span>
        <button class="btn btn-sm" style="background:rgba(255,255,255,.2);color:#fff;margin-left:auto" onclick="abrirAjuste()">
            <i class="fa fa-sliders"></i> Ajuste
        </button>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table id="tablaKardex">
                <thead><tr>
                    <th>Fecha</th><th>Tipo</th><th>Referencia</th>
                    <th style="text-align:right">Cantidad</th>
                    <th style="text-align:right">P. Unit.</th>
                    <th style="text-align:right">Saldo Cant.</th>
                    <th style="text-align:right">Saldo Valor</th>
                    <th>Observaciones</th>
                </tr></thead>
                <tbody>
                    <tr><td colspan="8" class="text-center text-muted" style="padding:1.5rem">
                        Seleccione un material para ver su kardex
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================
     TAB: CATEGORIAS
     ============================================================ -->
<div class="tab-content" data-group="inv" id="tabCategorias">
    <div class="toolbar">
        <div class="toolbar-left"></div>
        <div class="toolbar-right">
            <button class="btn btn-primary" onclick="abrirModalCategoria()">
                <i class="fa fa-plus"></i> Nueva Categoria
            </button>
        </div>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table id="tablaCats">
                <thead><tr>
                    <th>Nombre</th><th>Descripcion</th><th>Materiales</th><th></th>
                </tr></thead>
                <tbody>
                    <tr><td colspan="4" class="text-center">
                        <div class="loading"><div class="spinner"></div></div>
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: MATERIAL / PRODUCTO
     ============================================================ -->
<div class="modal-overlay" id="modalProducto">
    <div class="modal" style="max-width:600px">
        <div class="modal-header">
            <span class="modal-title" id="modalProdTitle">Nuevo Material</span>
            <button class="modal-close" onclick="Modal.close('modalProducto')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="prod_id">
            <div class="form-group">
                <label class="form-label">Imagen</label>
                <div style="display:flex;align-items:center;gap:14px">
                    <div id="prod_img_preview" style="width:80px;height:80px;border:2px dashed var(--border);border-radius:8px;display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0">
                        <span style="font-size:.68rem;color:var(--text-secondary);text-align:center;padding:4px">Sin imagen</span>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:6px">
                        <input type="file" id="prod_imagen" accept="image/*" style="display:none" onchange="previewImagen(this)">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('prod_imagen').click()">
                            <i class="fa fa-upload"></i> Subir imagen
                        </button>
                        <button type="button" class="btn btn-sm" id="prod_btn_quitar" style="display:none;background:var(--danger);color:#fff" onclick="quitarImagen()">
                            <i class="fa fa-trash"></i> Quitar imagen
                        </button>
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Codigo *</label>
                    <input type="text" class="form-control" id="prod_codigo" placeholder="Ej: AC-001">
                </div>
                <div class="form-group">
                    <label class="form-label">Unidad</label>
                    <select class="form-control" id="prod_unidad">
                        <option value="unidad">Unidad</option>
                        <option value="kg">Kilogramo</option>
                        <option value="g">Gramo</option>
                        <option value="m">Metro</option>
                        <option value="m2">Metro cuadrado</option>
                        <option value="m3">Metro cubico</option>
                        <option value="lt">Litro</option>
                        <option value="gln">Galon</option>
                        <option value="caja">Caja</option>
                        <option value="rollo">Rollo</option>
                        <option value="par">Par</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Nombre *</label>
                <input type="text" class="form-control" id="prod_nombre" placeholder="Nombre del material">
            </div>
            <div class="form-group">
                <label class="form-label">Descripcion</label>
                <textarea class="form-control" id="prod_descripcion" rows="2"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Codigo interno</label>
                    <input type="text" class="form-control" id="prod_codigo_interno" placeholder="Codigo interno de facturacion">
                </div>
                <div class="form-group">
                    <label class="form-label">Codigo de barras</label>
                    <input type="text" class="form-control" id="prod_codigo_barras" placeholder="Opcional">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Codigo SUNAT</label>
                    <input type="text" class="form-control" id="prod_codigo_sunat" maxlength="8" placeholder="00000000">
                </div>
                <div class="form-group">
                    <label class="form-label">Tipo de item</label>
                    <select class="form-control" id="prod_product_type">
                        <option value="product">Producto</option>
                        <option value="service">Servicio</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Categoria</label>
                    <select class="form-control" id="prod_categoria_id">
                        <option value="">— Sin categoria —</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Metodo Valuacion</label>
                    <select class="form-control" id="prod_metodo">
                        <option value="promedio">Precio Promedio</option>
                        <option value="fifo">FIFO</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Unidad FE</label>
                    <select class="form-control" id="prod_unidad_codigo"></select>
                </div>
                <div class="form-group">
                    <label class="form-label">Afectacion IGV</label>
                    <select class="form-control" id="prod_afectacion_igv"></select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Stock Minimo</label>
                    <input type="number" class="form-control" id="prod_stock_min" min="0" step="0.001" value="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Stock Maximo</label>
                    <input type="number" class="form-control" id="prod_stock_max" min="0" step="0.001" value="0">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Precio de Venta (S/)</label>
                <input type="number" class="form-control" id="prod_precio_venta" min="0" step="0.01" value="0" placeholder="0.00">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Porcentaje IGV</label>
                    <input type="number" class="form-control" id="prod_porcentaje_igv" min="0" step="0.01" value="18.00">
                </div>
                <div class="form-group" style="display:flex;align-items:end">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:.875rem">
                        <input type="checkbox" id="prod_incluye_igv" checked>
                        El precio de venta incluye IGV
                    </label>
                </div>
            </div>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:.875rem">
                <input type="checkbox" id="prod_es_repuesto">
                Es repuesto para maquinaria vendida
            </label>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('modalProducto')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarProducto()">
                <i class="fa fa-save"></i> Guardar
            </button>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: AJUSTE MANUAL
     ============================================================ -->
<div class="modal-overlay" id="modalAjuste">
    <div class="modal" style="max-width:460px">
        <div class="modal-header">
            <span class="modal-title">Ajuste Manual de Stock</span>
            <button class="modal-close" onclick="Modal.close('modalAjuste')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="ajuste_prod_id">
            <p style="font-size:.9rem;margin-bottom:1rem;color:var(--text-secondary)">
                Material: <strong id="ajuste_prod_nombre" style="color:var(--text-primary)"></strong><br>
                Stock actual: <strong id="ajuste_stock_actual"></strong>
            </p>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Tipo de Ajuste</label>
                    <select class="form-control" id="ajuste_tipo">
                        <option value="entrada">Entrada</option>
                        <option value="salida">Salida</option>
                        <option value="ajuste">Ajuste (fijar stock)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Cantidad *</label>
                    <input type="number" class="form-control" id="ajuste_cantidad" min="0.001" step="0.001" placeholder="0">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Precio Unitario <span style="color:var(--text-secondary);font-size:.75rem">(dejar vacio para usar precio promedio)</span></label>
                <input type="number" class="form-control" id="ajuste_precio" min="0" step="0.01" placeholder="Opcional">
            </div>
            <div class="form-group">
                <label class="form-label">Observaciones</label>
                <textarea class="form-control" id="ajuste_obs" rows="2"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('modalAjuste')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarAjuste()">
                <i class="fa fa-check"></i> Aplicar
            </button>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: CATEGORIA
     ============================================================ -->
<div class="modal-overlay" id="modalCategoria">
    <div class="modal" style="max-width:440px">
        <div class="modal-header">
            <span class="modal-title" id="modalCatTitle">Nueva Categoria</span>
            <button class="modal-close" onclick="Modal.close('modalCategoria')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="cat_id">
            <div class="form-group">
                <label class="form-label">Nombre *</label>
                <input type="text" class="form-control" id="cat_nombre" placeholder="Ej: Metales, Pinturas...">
            </div>
            <div class="form-group">
                <label class="form-label">Descripcion</label>
                <textarea class="form-control" id="cat_descripcion" rows="2"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('modalCategoria')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarCategoria()">
                <i class="fa fa-save"></i> Guardar
            </button>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
const API_INV = 'api.php';
let combos = { categorias: [] };
let todosProductos = [];

document.addEventListener('DOMContentLoaded', () => {
    initTabs();
    cargarCombos().then(() => {
        cargarProductos();
        cargarRepuestos();
        cargarCategorias();
    });

    let debTimer;
    document.getElementById('prodSearch').addEventListener('input', () => {
        clearTimeout(debTimer);
        debTimer = setTimeout(cargarProductos, 350);
    });
    ['prodCategoria','prodRepuesto'].forEach(id =>
        document.getElementById(id).addEventListener('change', cargarProductos));
    document.getElementById('prodCritico').addEventListener('change', cargarProductos);

    let repDebTimer;
    document.getElementById('repSearch').addEventListener('input', () => {
        clearTimeout(repDebTimer);
        repDebTimer = setTimeout(cargarRepuestos, 350);
    });
    document.getElementById('repCategoria').addEventListener('change', cargarRepuestos);
    document.getElementById('repCritico').addEventListener('change', cargarRepuestos);
});


// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// COMBOS
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
async function cargarCombos() {
    try {
        const d = await apiGet(API_INV + '?action=combos');
        if (!d.ok) return;
        combos = d;
        const opts = '<option value="">Todas las categorias</option>' +
            d.categorias.map(c => `<option value="${c.id}">${c.nombre}</option>`).join('');
        document.getElementById('prodCategoria').innerHTML = opts;
        document.getElementById('repCategoria').innerHTML = opts;
        document.getElementById('prod_unidad_codigo').innerHTML =
            (d.unidades || []).map(u => `<option value="${u.codigo}">${u.codigo} - ${u.descripcion}</option>`).join('');
        document.getElementById('prod_afectacion_igv').innerHTML =
            (d.afectaciones || []).map(a => `<option value="${a.codigo}">${a.codigo} - ${a.descripcion}</option>`).join('');

        const dp = await apiGet(API_INV + '?action=productos_listar');
        todosProductos = dp.productos || [];
        document.getElementById('kardexProducto').innerHTML =
            '<option value="">— Seleccionar material —</option>' +
            todosProductos.map(p =>
                `<option value="${p.id}" data-stock="${p.stock_actual}" data-pp="${p.precio_promedio}" data-nombre="${p.nombre}">[${p.codigo}] ${p.nombre}</option>`
            ).join('');
    } catch(e) { console.error('Combos error:', e); }
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// PRODUCTOS / MATERIALES
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
async function cargarProductos() {
    const q        = document.getElementById('prodSearch').value;
    const cat      = document.getElementById('prodCategoria').value;
    const repuesto = document.getElementById('prodRepuesto').value;
    const critico  = document.getElementById('prodCritico').checked ? '1' : '';
    try {
        const d = await apiGet(`${API_INV}?action=productos_listar&q=${encodeURIComponent(q)}&categoria_id=${cat}&es_repuesto=${repuesto}&stock_critico=${critico}`);
        if (!d.ok) return;
        const tbody = document.querySelector('#tablaprod tbody');
        tbody.innerHTML = d.productos.map(p => {
            const esCritico = parseFloat(p.stock_minimo) > 0 && parseFloat(p.stock_actual) <= parseFloat(p.stock_minimo);
            const valor = parseFloat(p.stock_actual) * parseFloat(p.precio_venta);
            const imgCell = p.imagen
                ? `<img src="../../assets/uploads/materiales/${p.imagen}" style="width:40px;height:40px;object-fit:cover;border-radius:4px;border:1px solid var(--border)">`
                : `<div style="width:40px;height:40px;border:1px dashed var(--border);border-radius:4px;display:inline-flex;align-items:center;justify-content:center;color:var(--text-secondary);font-size:.65rem;line-height:1.1;text-align:center">sin<br>img</div>`;
            return `<tr>
                <td style="text-align:center">${imgCell}</td>
                <td><code>${p.codigo}</code></td>
                <td>
                    ${p.nombre}
                    ${p.es_repuesto ? '<span class="badge badge-normal" style="font-size:.7rem;margin-left:4px">Repuesto</span>' : ''}
                </td>
                <td>${p.categoria_nombre || '—'}</td>
                <td>${p.unidad}</td>
                <td>
                    <strong style="color:${esCritico?'var(--danger)':'inherit'}">${Math.round(p.stock_actual)}</strong>
                    ${esCritico ? ' <i class="fa fa-triangle-exclamation" style="color:var(--danger);font-size:.8rem" title="Stock critico"></i>' : ''}
                </td>
                <td style="font-size:.82rem">${Math.round(p.stock_minimo)} / ${parseFloat(p.stock_maximo)>0 ? Math.round(p.stock_maximo) : 'inf'}</td>
                <td>${formatMoney(p.precio_venta)}</td>
                <td>${formatMoney(valor)}</td>
                <td>
                    <div style="display:flex;gap:.25rem">
                        <button class="btn btn-secondary btn-sm" onclick="editarProducto(${p.id})" title="Editar">
                            <i class="fa fa-pen"></i>
                        </button>
                        <button class="btn btn-secondary btn-sm" title="Ver Kardex" onclick="verKardexProducto(${p.id})">
                            <i class="fa fa-list"></i>
                        </button>
                        <button class="btn btn-secondary btn-sm" title="Ajuste de stock" onclick="abrirAjuste(${p.id},'${p.nombre.replace(/'/g,"\\'")}',${p.stock_actual})">
                            <i class="fa fa-sliders"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
        }).join('') || '<tr><td colspan="10" class="text-center text-muted" style="padding:1.5rem">Sin materiales registrados</td></tr>';
    } catch(e) {
        document.querySelector('#tablaprod tbody').innerHTML =
            '<tr><td colspan="10" class="text-center" style="color:var(--danger)">Error al cargar materiales</td></tr>';
    }
}

async function abrirModalProducto() {
    document.getElementById('prod_id').value          = '';
    document.getElementById('prod_codigo').value      = '...';
    document.getElementById('prod_nombre').value      = '';
    document.getElementById('prod_descripcion').value = '';
    document.getElementById('prod_codigo_interno').value = '';
    document.getElementById('prod_codigo_barras').value = '';
    document.getElementById('prod_codigo_sunat').value = '00000000';
    document.getElementById('prod_product_type').value = 'product';
    document.getElementById('prod_unidad').value      = 'unidad';
    document.getElementById('prod_unidad_codigo').value = 'NIU';
    document.getElementById('prod_afectacion_igv').value = '10';
    document.getElementById('prod_metodo').value      = 'promedio';
    document.getElementById('prod_stock_min').value    = '0';
    document.getElementById('prod_stock_max').value    = '0';
    document.getElementById('prod_precio_venta').value = '0';
    document.getElementById('prod_porcentaje_igv').value = '18.00';
    document.getElementById('prod_incluye_igv').checked = true;
    document.getElementById('prod_es_repuesto').checked = false;
    document.getElementById('prod_categoria_id').innerHTML =
        '<option value="">— Sin categoria —</option>' +
        combos.categorias.map(c => `<option value="${c.id}">${c.nombre}</option>`).join('');
    document.getElementById('prod_imagen').value = '';
    const preview = document.getElementById('prod_img_preview');
    preview.innerHTML = '<span style="font-size:.68rem;color:var(--text-secondary);text-align:center;padding:4px">Sin imagen</span>';
    preview.dataset.current = '';
    preview.dataset.removed = '';
    document.getElementById('prod_btn_quitar').style.display = 'none';
    document.getElementById('modalProdTitle').textContent = 'Nuevo Material';
    Modal.open('modalProducto');
    try {
        const d = await apiGet(API_INV + '?action=generar_codigo');
        if (d.ok) document.getElementById('prod_codigo').value = d.codigo;
    } catch(e) { document.getElementById('prod_codigo').value = ''; }
}

async function editarProducto(id) {
    try {
        const d = await apiGet(`${API_INV}?action=producto_obtener&id=${id}`);
        if (!d.ok) return;
        const p = d.producto;
        document.getElementById('prod_categoria_id').innerHTML =
            '<option value="">— Sin categoria —</option>' +
            combos.categorias.map(c => `<option value="${c.id}" ${c.id == p.categoria_id ? 'selected' : ''}>${c.nombre}</option>`).join('');
        document.getElementById('prod_id').value          = p.id;
        document.getElementById('prod_codigo').value      = p.codigo;
        document.getElementById('prod_nombre').value      = p.nombre;
        document.getElementById('prod_descripcion').value = p.descripcion || '';
        document.getElementById('prod_codigo_interno').value = p.codigo_interno || p.codigo || '';
        document.getElementById('prod_codigo_barras').value = p.codigo_barras || '';
        document.getElementById('prod_codigo_sunat').value = p.codigo_sunat || '00000000';
        document.getElementById('prod_product_type').value = p.product_type || 'product';
        document.getElementById('prod_unidad').value      = p.unidad;
        document.getElementById('prod_unidad_codigo').value = p.unidad_codigo || 'NIU';
        document.getElementById('prod_afectacion_igv').value = p.afectacion_igv_codigo || '10';
        document.getElementById('prod_metodo').value      = p.metodo_valuacion;
        document.getElementById('prod_stock_min').value    = p.stock_minimo;
        document.getElementById('prod_stock_max').value    = p.stock_maximo;
        document.getElementById('prod_precio_venta').value = p.precio_venta ?? 0;
        document.getElementById('prod_porcentaje_igv').value = p.porcentaje_igv ?? 18;
        document.getElementById('prod_incluye_igv').checked = !!Number(p.incluye_igv ?? 1);
        document.getElementById('prod_es_repuesto').checked = p.es_repuesto;
        const preview = document.getElementById('prod_img_preview');
        document.getElementById('prod_imagen').value = '';
        if (p.imagen) {
            preview.innerHTML = `<img src="../../assets/uploads/materiales/${p.imagen}" style="width:100%;height:100%;object-fit:cover">`;
            preview.dataset.current = p.imagen;
            document.getElementById('prod_btn_quitar').style.display = 'block';
        } else {
            preview.innerHTML = '<span style="font-size:.68rem;color:var(--text-secondary);text-align:center;padding:4px">Sin imagen</span>';
            preview.dataset.current = '';
            document.getElementById('prod_btn_quitar').style.display = 'none';
        }
        preview.dataset.removed = '';
        document.getElementById('modalProdTitle').textContent = 'Editar Material';
        Modal.open('modalProducto');
    } catch(e) { Toast.error('Error al cargar material'); }
}

async function guardarProducto() {
    const nombre = document.getElementById('prod_nombre').value.trim();
    const codigo = document.getElementById('prod_codigo').value.trim();
    if (!nombre) return Toast.error('El nombre es obligatorio.');
    const id = document.getElementById('prod_id').value;

    const fd = new FormData();
    fd.append('action', 'producto_guardar');
    if (id) fd.append('id', id);
    fd.append('codigo', codigo);
    fd.append('nombre', nombre);
    fd.append('descripcion', document.getElementById('prod_descripcion').value);
    fd.append('codigo_interno', document.getElementById('prod_codigo_interno').value);
    fd.append('codigo_barras', document.getElementById('prod_codigo_barras').value);
    fd.append('codigo_sunat', document.getElementById('prod_codigo_sunat').value);
    fd.append('categoria_id', document.getElementById('prod_categoria_id').value || '');
    fd.append('unidad', document.getElementById('prod_unidad').value);
    fd.append('unidad_codigo', document.getElementById('prod_unidad_codigo').value);
    fd.append('afectacion_igv_codigo', document.getElementById('prod_afectacion_igv').value);
    fd.append('metodo_valuacion', document.getElementById('prod_metodo').value);
    fd.append('stock_minimo', document.getElementById('prod_stock_min').value);
    fd.append('stock_maximo', document.getElementById('prod_stock_max').value);
    fd.append('precio_venta', document.getElementById('prod_precio_venta').value);
    fd.append('porcentaje_igv', document.getElementById('prod_porcentaje_igv').value);
    fd.append('incluye_igv', document.getElementById('prod_incluye_igv').checked ? '1' : '0');
    fd.append('product_type', document.getElementById('prod_product_type').value);
    fd.append('es_repuesto', document.getElementById('prod_es_repuesto').checked ? '1' : '0');

    const imgFile = document.getElementById('prod_imagen').files[0];
    const preview = document.getElementById('prod_img_preview');
    if (imgFile) {
        fd.append('imagen', imgFile);
    } else if (preview.dataset.removed === '1') {
        fd.append('remove_imagen', '1');
    }

    try {
        const resp = await fetch(API_INV, { method: 'POST', body: fd });
        const r = await resp.json();
        if (r.ok) {
            Toast.success('Material guardado.');
            Modal.close('modalProducto');
            cargarProductos();
            cargarCombos();
        } else Toast.error(r.error || 'Error al guardar');
    } catch(e) { Toast.error(e.message || 'Error al guardar'); }
}

function previewImagen(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        const preview = document.getElementById('prod_img_preview');
        preview.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover">`;
        preview.dataset.removed = '';
        document.getElementById('prod_btn_quitar').style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
}

function quitarImagen() {
    const preview = document.getElementById('prod_img_preview');
    preview.innerHTML = '<span style="font-size:.68rem;color:var(--text-secondary);text-align:center;padding:4px">Sin imagen</span>';
    preview.dataset.current = '';
    preview.dataset.removed = '1';
    document.getElementById('prod_imagen').value = '';
    document.getElementById('prod_btn_quitar').style.display = 'none';
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// KARDEX
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
async function cargarKardex() {
    const prodId = document.getElementById('kardexProducto').value;
    if (!prodId) return Toast.error('Seleccione un material.');
    const desde = document.getElementById('kardexDesde').value;
    const hasta = document.getElementById('kardexHasta').value;
    const tipo  = document.getElementById('kardexTipo').value;

    const opt = document.querySelector(`#kardexProducto option[value="${prodId}"]`);
    document.getElementById('kardexProductoNombre').textContent = opt?.dataset.nombre || '';
    document.getElementById('kardexStockActual').textContent    = opt?.dataset.stock  || '0';
    document.getElementById('kardexPrecioPromedio').textContent = opt ? formatMoney(opt.dataset.pp) : '—';
    document.getElementById('kardexInfo').style.display = 'flex';

    try {
        const d = await apiGet(`${API_INV}?action=kardex_listar&producto_id=${prodId}&desde=${desde}&hasta=${hasta}&tipo=${tipo}`);
        if (!d.ok) return;
        const tipoColor = { entrada: 'badge-completado', salida: 'badge-cancelado', ajuste: 'badge-normal' };
        document.querySelector('#tablaKardex tbody').innerHTML = d.movimientos.map(m => `<tr>
            <td style="white-space:nowrap">${formatDate(m.fecha)}</td>
            <td><span class="badge ${tipoColor[m.tipo]||'badge-normal'}">${m.tipo}</span></td>
            <td style="font-size:.8rem">${m.referencia_tipo || '—'}</td>
            <td style="text-align:right"><strong>${m.tipo==='salida'?'- ':'+'}${m.cantidad}</strong></td>
            <td style="text-align:right">${formatMoney(m.precio_unitario)}</td>
            <td style="text-align:right">${m.saldo_cantidad}</td>
            <td style="text-align:right">${formatMoney(m.saldo_valor)}</td>
            <td style="font-size:.8rem">${m.observaciones || '—'}</td>
        </tr>`).join('') || '<tr><td colspan="8" class="text-center text-muted" style="padding:1.5rem">Sin movimientos en el periodo</td></tr>';
    } catch(e) { Toast.error('Error al cargar kardex'); }
}

function verKardexProducto(prodId) {
    document.getElementById('kardexProducto').value = prodId;
    document.querySelector('.tab[data-target="tabKardex"]').click();
    setTimeout(cargarKardex, 100);
}

function abrirAjuste(prodId, nombre, stock) {
    if (prodId) {
        document.getElementById('ajuste_prod_id').value = prodId;
        document.getElementById('ajuste_prod_nombre').textContent = nombre;
        document.getElementById('ajuste_stock_actual').textContent = stock;
    } else {
        const opt = document.querySelector(`#kardexProducto option[value="${document.getElementById('kardexProducto').value}"]`);
        if (!opt || !opt.value) return Toast.error('Seleccione un material en el Kardex.');
        document.getElementById('ajuste_prod_id').value = opt.value;
        document.getElementById('ajuste_prod_nombre').textContent = opt.dataset.nombre;
        document.getElementById('ajuste_stock_actual').textContent = opt.dataset.stock;
    }
    document.getElementById('ajuste_tipo').value     = 'entrada';
    document.getElementById('ajuste_cantidad').value = '';
    document.getElementById('ajuste_precio').value   = '';
    document.getElementById('ajuste_obs').value      = '';
    Modal.open('modalAjuste');
}

async function guardarAjuste() {
    const qty = parseFloat(document.getElementById('ajuste_cantidad').value);
    if (!qty || qty <= 0) return Toast.error('Ingrese una cantidad valida.');
    try {
        const r = await apiPost(API_INV, {
            action:          'ajuste_stock',
            producto_id:     document.getElementById('ajuste_prod_id').value,
            tipo:            document.getElementById('ajuste_tipo').value,
            cantidad:        qty,
            precio_unitario: document.getElementById('ajuste_precio').value || null,
            observaciones:   document.getElementById('ajuste_obs').value,
        });
        if (r.ok) {
            Toast.success(`Stock actualizado: ${r.nuevo_stock}`);
            Modal.close('modalAjuste');
            cargarProductos();
            cargarCombos();
            if (document.getElementById('kardexProducto').value == document.getElementById('ajuste_prod_id').value)
                cargarKardex();
        } else Toast.error(r.error || 'Error al ajustar');
    } catch(e) { Toast.error(e.message || 'Error al ajustar'); }
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// CATEGORIAS
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
async function cargarCategorias() {
    try {
        const d = await apiGet(API_INV + '?action=categorias_listar');
        if (!d.ok) return;
        document.querySelector('#tablaCats tbody').innerHTML = d.categorias.map(c => `<tr>
            <td><strong>${c.nombre}</strong></td>
            <td>${c.descripcion || '—'}</td>
            <td>${c.total_productos}</td>
            <td>
                <div style="display:flex;gap:.25rem">
                    <button class="btn btn-secondary btn-sm" onclick="editarCategoria(${c.id},'${c.nombre.replace(/'/g,"\\'")}','${(c.descripcion||'').replace(/'/g,"\\'")}')">
                        <i class="fa fa-pen"></i>
                    </button>
                    <button class="btn btn-sm" style="background:var(--danger);color:#fff" onclick="eliminarCategoria(${c.id})">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>`).join('') || '<tr><td colspan="4" class="text-center text-muted" style="padding:1.5rem">Sin categorias</td></tr>';
    } catch(e) {
        document.querySelector('#tablaCats tbody').innerHTML =
            '<tr><td colspan="4" class="text-center" style="color:var(--danger)">Error al cargar categorias</td></tr>';
    }
}

function abrirModalCategoria() {
    document.getElementById('cat_id').value          = '';
    document.getElementById('cat_nombre').value      = '';
    document.getElementById('cat_descripcion').value = '';
    document.getElementById('modalCatTitle').textContent = 'Nueva Categoria';
    Modal.open('modalCategoria');
}

function editarCategoria(id, nombre, desc) {
    document.getElementById('cat_id').value          = id;
    document.getElementById('cat_nombre').value      = nombre;
    document.getElementById('cat_descripcion').value = desc;
    document.getElementById('modalCatTitle').textContent = 'Editar Categoria';
    Modal.open('modalCategoria');
}

async function guardarCategoria() {
    const nombre = document.getElementById('cat_nombre').value.trim();
    if (!nombre) return Toast.error('El nombre es obligatorio.');
    try {
        const r = await apiPost(API_INV, {
            action:      'categoria_guardar',
            id:          document.getElementById('cat_id').value || null,
            nombre,
            descripcion: document.getElementById('cat_descripcion').value,
        });
        if (r.ok) {
            Toast.success('Categoria guardada.');
            Modal.close('modalCategoria');
            cargarCategorias();
            cargarCombos();
        } else Toast.error(r.error || 'Error al guardar');
    } catch(e) { Toast.error(e.message || 'Error al guardar'); }
}

async function eliminarCategoria(id) {
    if (!confirm('Eliminar esta categoria?')) return;
    try {
        const r = await apiPost(API_INV, { action: 'categoria_eliminar', id });
        if (r.ok) { Toast.success('Categoria eliminada.'); cargarCategorias(); cargarCombos(); }
        else Toast.error(r.error || 'Error');
    } catch(e) { Toast.error(e.message || 'Error'); }
}

// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
// REPUESTOS
// â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
async function cargarRepuestos() {
    const q       = document.getElementById('repSearch').value;
    const cat     = document.getElementById('repCategoria').value;
    const critico = document.getElementById('repCritico').checked ? '1' : '';
    try {
        const d = await apiGet(`${API_INV}?action=productos_listar&q=${encodeURIComponent(q)}&categoria_id=${cat}&es_repuesto=true&stock_critico=${critico}`);
        if (!d.ok) return;
        const tbody = document.querySelector('#tablarep tbody');
        tbody.innerHTML = d.productos.map(p => {
            const esCritico = parseFloat(p.stock_minimo) > 0 && parseFloat(p.stock_actual) <= parseFloat(p.stock_minimo);
            const valor = parseFloat(p.stock_actual) * parseFloat(p.precio_venta);
            const imgCell = p.imagen
                ? `<img src="../../assets/uploads/materiales/${p.imagen}" style="width:40px;height:40px;object-fit:cover;border-radius:4px;border:1px solid var(--border)">`
                : `<div style="width:40px;height:40px;border:1px dashed var(--border);border-radius:4px;display:inline-flex;align-items:center;justify-content:center;color:var(--text-secondary);font-size:.65rem;line-height:1.1;text-align:center">sin<br>img</div>`;
            return `<tr>
                <td style="text-align:center">${imgCell}</td>
                <td><code>${p.codigo}</code></td>
                <td>${p.nombre}</td>
                <td>${p.categoria_nombre || '—'}</td>
                <td>${p.unidad}</td>
                <td>
                    <strong style="color:${esCritico?'var(--danger)':'inherit'}">${Math.round(p.stock_actual)}</strong>
                    ${esCritico ? ' <i class="fa fa-triangle-exclamation" style="color:var(--danger);font-size:.8rem" title="Stock critico"></i>' : ''}
                </td>
                <td style="font-size:.82rem">${Math.round(p.stock_minimo)} / ${parseFloat(p.stock_maximo)>0 ? Math.round(p.stock_maximo) : 'inf'}</td>
                <td>${formatMoney(p.precio_venta)}</td>
                <td>${formatMoney(valor)}</td>
                <td>
                    <div style="display:flex;gap:.25rem">
                        <button class="btn btn-secondary btn-sm" onclick="editarProducto(${p.id})" title="Editar">
                            <i class="fa fa-pen"></i>
                        </button>
                        <button class="btn btn-secondary btn-sm" title="Ver Kardex" onclick="verKardexProducto(${p.id})">
                            <i class="fa fa-list"></i>
                        </button>
                        <button class="btn btn-secondary btn-sm" title="Ajuste de stock" onclick="abrirAjuste(${p.id},'${p.nombre.replace(/'/g,"\\'")}',${p.stock_actual})">
                            <i class="fa fa-sliders"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
        }).join('') || '<tr><td colspan="10" class="text-center text-muted" style="padding:1.5rem">Sin repuestos registrados</td></tr>';
    } catch(e) {
        document.querySelector('#tablarep tbody').innerHTML =
            '<tr><td colspan="10" class="text-center" style="color:var(--danger)">Error al cargar repuestos</td></tr>';
    }
}

function abrirModalRepuesto() {
    abrirModalProducto().then(() => {
        document.getElementById('prod_es_repuesto').checked = true;
        document.getElementById('modalProdTitle').textContent = 'Nuevo Repuesto';
    });
}
</script>
<?php
$extra_js = ob_get_clean();
require_once __DIR__ . '/../../includes/footer.php';


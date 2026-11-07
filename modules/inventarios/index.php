<?php
$page_title      = 'Inventario';
$page_breadcrumb = '<span>Operaciones</span> <span>/</span> Inventario';
require_once __DIR__ . '/../../includes/header.php';

$permisoAlmacen = nivelPermiso('inventarios');
if ($permisoAlmacen === 'ninguno') {
    echo '<div class="alert alert-danger" style="margin:20px">
        <i class="fa fa-lock"></i> No tienes permisos para acceder al módulo de Inventario. Contacta a un administrador.
    </div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}
$almacenSoloLectura = $permisoAlmacen !== 'completo';
?>

<style>
#tablaprod tbody tr.fila-producto,
#tablarep tbody tr.fila-producto { cursor: pointer; transition: background-color .15s ease; }
#tablaprod tbody tr.fila-producto:hover,
#tablarep tbody tr.fila-producto:hover { background: #ffedd5; }
.prod-modal-grid { display: grid; grid-template-columns: 190px 1fr; gap: 24px; }
@media (max-width: 640px) {
    .prod-modal-grid { grid-template-columns: 1fr; }
}
.btn-importar { transition: background-color .15s ease, border-color .15s ease, color .15s ease; }
.btn-importar:hover {
    background: #dcfce7;       /* verde pastel */
    border-color: #86efac;
    color: #166534;
}
</style>

<!-- TABS -->
<div class="tabs">
    <div class="tab active" data-group="inv" data-target="tabProductos">
        <i class="fa fa-boxes-stacked"></i> Inventario
    </div>
    <div class="tab" data-group="inv" data-target="tabRepuestos">
        <i class="fa fa-screwdriver-wrench"></i> Repuestos
    </div>
    <div class="tab" data-group="inv" data-target="tabEntradas">
        <i class="fa fa-arrow-down"></i> Entradas
    </div>
    <div class="tab" data-group="inv" data-target="tabSalidas">
        <i class="fa fa-arrow-up"></i> Salidas
    </div>
    <div class="tab" data-group="inv" data-target="tabKardex">
        <i class="fa fa-list"></i> Kardex
    </div>
    <div class="tab" data-group="inv" data-target="tabCategorias">
        <i class="fa fa-tags"></i> Categorias
    </div>
    <div class="tab" data-group="inv" data-target="tabCeldas">
        <i class="fa fa-table-cells"></i> Celdas
    </div>
</div>

<!-- ============================================================
     TAB: INVENTARIO (PRODUCTOS)
     ============================================================ -->
<div class="tab-content active" data-group="inv" id="tabProductos">
    <div class="toolbar">
        <div class="toolbar-left">
            <div class="search-box" style="min-width:320px;border-color:var(--primary)" title="Escribe para filtrar, o escanea un código de barras y presiona Enter">
                <span class="icon" style="color:var(--primary)"><i class="fa fa-barcode"></i></span>
                <input type="text" class="form-control" id="prodSearch"
                       placeholder="Buscar codigo, nombre o escanear código de barras..."
                       onkeydown="if(event.key==='Enter'){event.preventDefault();buscarPorCodigo(this.value)}">
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
            <?php if (!$almacenSoloLectura): ?>
            <button class="btn btn-primary" onclick="abrirModalProducto()">
                <i class="fa fa-plus"></i> Nuevo Material
            </button>
            <button class="btn btn-secondary btn-importar" onclick="abrirModalImportar()">
                <i class="fa fa-file-excel"></i> Importar
            </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table id="tablaprod">
                <thead><tr>
                    <th style="width:52px"></th>
                    <th>Codigo</th><th>Nombre</th><th>Categoria</th>
                    <th>Unidad</th><th>Ubicacion</th><th>Stock</th><th>Min</th>
                    <th>P. de Venta</th><th>Total</th>
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
            <?php if (!$almacenSoloLectura): ?>
            <button class="btn btn-primary" onclick="abrirModalRepuesto()">
                <i class="fa fa-plus"></i> Nuevo Repuesto
            </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table id="tablarep">
                <thead><tr>
                    <th style="width:52px"></th>
                    <th>Codigo</th><th>Nombre</th><th>Categoria</th>
                    <th>Unidad</th><th>Ubicacion</th><th>Stock</th><th>Min</th>
                    <th>P. de Venta</th><th>Total</th>
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
     TAB: ENTRADAS
     ============================================================ -->
<div class="tab-content" data-group="inv" id="tabEntradas">
    <div class="toolbar">
        <div class="toolbar-left">
            <div class="search-box">
                <span class="icon"><i class="fa fa-search"></i></span>
                <input type="text" class="form-control" id="entSearch" placeholder="Buscar material...">
            </div>
            <input type="date" class="form-control" id="entDesde" style="width:150px">
            <input type="date" class="form-control" id="entHasta" style="width:150px">
        </div>
        <div class="toolbar-right">
            <button class="btn btn-secondary" onclick="cargarEntradas()">
                <i class="fa fa-search"></i> Consultar
            </button>
            <?php if (!$almacenSoloLectura): ?>
            <button class="btn btn-primary" onclick="abrirModalMovimiento('entrada')">
                <i class="fa fa-plus"></i> Nueva Entrada
            </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table id="tablaEntradas">
                <thead><tr>
                    <th>Fecha</th><th>Codigo</th><th>Material</th>
                    <th style="text-align:right">Cantidad</th>
                    <th style="text-align:right">P. Unit.</th>
                    <th style="text-align:right">Saldo</th>
                    <th>Usuario</th>
                    <th>Observaciones</th>
                </tr></thead>
                <tbody>
                    <tr><td colspan="8" class="text-center">
                        <div class="loading"><div class="spinner"></div></div>
                    </td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================
     TAB: SALIDAS
     ============================================================ -->
<div class="tab-content" data-group="inv" id="tabSalidas">
    <div class="toolbar">
        <div class="toolbar-left">
            <div class="search-box">
                <span class="icon"><i class="fa fa-search"></i></span>
                <input type="text" class="form-control" id="salSearch" placeholder="Buscar material...">
            </div>
            <input type="date" class="form-control" id="salDesde" style="width:150px">
            <input type="date" class="form-control" id="salHasta" style="width:150px">
        </div>
        <div class="toolbar-right">
            <button class="btn btn-secondary" onclick="cargarSalidas()">
                <i class="fa fa-search"></i> Consultar
            </button>
            <?php if (!$almacenSoloLectura): ?>
            <button class="btn btn-primary" onclick="abrirModalMovimiento('salida')">
                <i class="fa fa-plus"></i> Nueva Salida
            </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table id="tablaSalidas">
                <thead><tr>
                    <th>Fecha</th><th>Codigo</th><th>Material</th>
                    <th style="text-align:right">Cantidad</th>
                    <th style="text-align:right">P. Unit.</th>
                    <th style="text-align:right">Saldo</th>
                    <th>Usuario</th>
                    <th>Observaciones</th>
                </tr></thead>
                <tbody>
                    <tr><td colspan="8" class="text-center">
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
        <?php if (!$almacenSoloLectura): ?>
        <button class="btn btn-sm" style="background:rgba(255,255,255,.2);color:#fff;margin-left:auto" onclick="abrirAjuste()">
            <i class="fa fa-sliders"></i> Ajuste
        </button>
        <?php endif; ?>
    </div>

    <div id="kardexStats" style="display:none;gap:.75rem;margin-bottom:.75rem;grid-template-columns:repeat(3,1fr)">
        <div style="background:#dcfce7;border:1px solid #86efac;border-radius:var(--radius-sm);padding:.6rem 1rem;display:flex;justify-content:space-between;align-items:center">
            <div>
                <div style="font-size:.75rem;color:#16a34a;font-weight:600;text-transform:uppercase">Entradas</div>
                <div style="font-size:1.1rem;font-weight:700;color:#15803d" id="statsEntQty">0</div>
            </div>
            <div style="text-align:right">
                <div style="font-size:.75rem;color:#16a34a">Valor</div>
                <div style="font-weight:600;color:#15803d" id="statsEntVal">S/ 0</div>
            </div>
        </div>
        <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:var(--radius-sm);padding:.6rem 1rem;display:flex;justify-content:space-between;align-items:center">
            <div>
                <div style="font-size:.75rem;color:#dc2626;font-weight:600;text-transform:uppercase">Salidas</div>
                <div style="font-size:1.1rem;font-weight:700;color:#b91c1c" id="statsSalQty">0</div>
            </div>
            <div style="text-align:right">
                <div style="font-size:.75rem;color:#dc2626">Valor</div>
                <div style="font-weight:600;color:#b91c1c" id="statsSalVal">S/ 0</div>
            </div>
        </div>
        <div style="background:#f1f5f9;border:1px solid #cbd5e1;border-radius:var(--radius-sm);padding:.6rem 1rem;display:flex;justify-content:space-between;align-items:center">
            <div>
                <div style="font-size:.75rem;color:#475569;font-weight:600;text-transform:uppercase">Ajustes</div>
                <div style="font-size:1.1rem;font-weight:700;color:#334155" id="statsAdjQty">0</div>
            </div>
            <div style="text-align:right">
                <div style="font-size:.75rem;color:#475569">Movimientos</div>
                <div style="font-weight:600;color:#334155" id="statsTotal">0</div>
            </div>
        </div>
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
                    <th>Usuario</th>
                    <th>Observaciones</th>
                </tr></thead>
                <tbody>
                    <tr><td colspan="9" class="text-center text-muted" style="padding:1.5rem">
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
            <?php if (!$almacenSoloLectura): ?>
            <button class="btn btn-primary" onclick="abrirModalCategoria()">
                <i class="fa fa-plus"></i> Nueva Categoria
            </button>
            <?php endif; ?>
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

    <div class="card" style="margin-top:18px">
        <div class="card-header"><span class="card-title"><i class="fa fa-ruler"></i> Unidades de Medida</span></div>
        <div class="card-body">
            <?php if (!$almacenSoloLectura): ?>
            <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;margin-bottom:14px">
                <div class="form-group" style="margin:0;width:160px">
                    <label class="form-label">Código *</label>
                    <input type="text" class="form-control" id="uni_codigo" placeholder="Ej: pln" maxlength="10">
                </div>
                <div class="form-group" style="margin:0;flex:1;min-width:200px">
                    <label class="form-label">Nombre *</label>
                    <input type="text" class="form-control" id="uni_nombre" placeholder="Ej: Plancha">
                </div>
                <button class="btn btn-primary" onclick="guardarUnidad()"><i class="fa fa-plus"></i> Agregar</button>
            </div>
            <?php endif; ?>
            <div class="table-responsive">
                <table>
                    <thead><tr><th style="width:160px">Código</th><th>Nombre</th><th style="width:80px"></th></tr></thead>
                    <tbody id="listaUnidades">
                        <tr><td colspan="3" class="text-center text-muted">—</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     TAB: CELDAS (configuracion global del estante)
     ============================================================ -->
<div class="tab-content" data-group="inv" id="tabCeldas">
    <div style="display:grid;grid-template-columns:360px 1fr;gap:20px;align-items:start">
        <div class="card">
            <div class="card-header"><span class="card-title"><i class="fa fa-sliders"></i> Configuración del Estante</span></div>
            <div class="card-body">
                <?php if ($almacenSoloLectura): ?>
                <div class="alert alert-info" style="font-size:.85rem">Tu rol es de solo lectura; no puedes cambiar esta configuración.</div>
                <?php endif; ?>
                <div class="form-group">
                    <label class="form-label">Nombre del estante</label>
                    <input type="text" class="form-control" id="est_nombre" maxlength="60" placeholder="Ej: Estante Principal" oninput="previewEstante()">
                </div>
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Ancho del estante (px)</label>
                        <input type="number" class="form-control" id="est_ancho" min="240" max="1400" step="10" value="360" oninput="previewEstante()">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Grosor de líneas (px)</label>
                        <input type="number" class="form-control" id="est_grosor" min="1" max="24" step="1" value="7" oninput="previewEstante()">
                    </div>
                </div>
                <div class="form-row cols-2">
                    <div class="form-group">
                        <label class="form-label">Columnas (A, B, C…)</label>
                        <input type="number" class="form-control" id="est_columnas" min="1" max="26" value="6" oninput="previewEstante()">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Filas (1 … N)</label>
                        <input type="number" class="form-control" id="est_filas" min="1" max="99" value="6" oninput="previewEstante()">
                    </div>
                </div>
                <div style="color:var(--text-secondary);font-size:.8rem;margin-bottom:10px">
                    Al cambiar el ancho, cada celda se ajusta proporcionalmente. Esta configuración es global.
                </div>
                <?php if (!$almacenSoloLectura): ?>
                <button class="btn btn-primary" onclick="guardarEstante()"><i class="fa fa-save"></i> Guardar configuración</button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><span class="card-title" id="est_preview_titulo">Vista previa</span></div>
            <div class="card-body" id="est_preview" style="overflow:auto"></div>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: MATERIAL / PRODUCTO
     ============================================================ -->
<div class="modal-overlay" id="modalProducto">
    <div class="modal modal-xl">
        <div class="modal-header">
            <span class="modal-title" id="modalProdTitle">Nuevo Material</span>
            <button class="modal-close" onclick="Modal.close('modalProducto')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="prod_id">
            <div class="prod-modal-grid">

                <!-- IZQUIERDA: imagen + flags -->
                <div>
                    <label class="form-label">Imagen</label>
                    <div id="prod_img_preview" style="width:100%;aspect-ratio:1;border:2px dashed var(--border);border-radius:8px;display:flex;align-items:center;justify-content:center;overflow:hidden">
                        <span style="font-size:.68rem;color:var(--text-secondary);text-align:center;padding:4px">Sin imagen</span>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:6px;margin-top:8px">
                        <input type="file" id="prod_imagen" accept="image/*" style="display:none" onchange="previewImagen(this)">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('prod_imagen').click()">
                            <i class="fa fa-upload"></i> Subir imagen
                        </button>
                        <button type="button" class="btn btn-sm" id="prod_btn_quitar" style="display:none;background:var(--danger);color:#fff" onclick="quitarImagen()">
                            <i class="fa fa-trash"></i> Quitar imagen
                        </button>
                    </div>
                    <div style="margin-top:16px;display:flex;flex-direction:column;gap:10px">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:.8rem">
                            <input type="checkbox" id="prod_incluye_igv" checked>
                            Precio incluye IGV
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:.8rem">
                            <input type="checkbox" id="prod_es_repuesto">
                            Es repuesto
                        </label>
                    </div>
                </div>

                <!-- DERECHA: campos -->
                <div>
                    <div style="display:flex;gap:12px">
                        <div class="form-group" style="flex:2">
                            <label class="form-label">Codigo de barras</label>
                            <div class="search-box" style="border:1.5px solid var(--primary);border-radius:var(--radius-sm);background:#fff7ed"
                                 title="Escanea con el lector o escribe manualmente">
                                <span class="icon" style="color:var(--primary)"><i class="fa fa-barcode"></i></span>
                                <input type="text" class="form-control" id="prod_codigo_barras" placeholder="Escanear o escribir..."
                                       style="width:100%;background:transparent;border:none"
                                       onkeydown="if(event.key==='Enter'){event.preventDefault();this.blur();}">
                            </div>
                        </div>
                        <div class="form-group" style="flex:1">
                            <label class="form-label">Unidad de Medida</label>
                            <select class="form-control" id="prod_unidad"></select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Codigo *</label>
                        <input type="text" class="form-control" id="prod_codigo" placeholder="Ej: AC-001">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nombre *</label>
                        <input type="text" class="form-control" id="prod_nombre" placeholder="Nombre del material">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Descripcion</label>
                        <textarea class="form-control" id="prod_descripcion" rows="2"></textarea>
                    </div>
                    <div class="form-row cols-2">
                        <div class="form-group">
                            <label class="form-label">Tipo de item</label>
                            <select class="form-control" id="prod_product_type">
                                <option value="product">Producto</option>
                                <option value="service">Servicio</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Categoria</label>
                            <select class="form-control" id="prod_categoria_id">
                                <option value="">— Sin categoria —</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row cols-3">
                        <div class="form-group">
                            <label class="form-label">Metodo Valuacion</label>
                            <select class="form-control" id="prod_metodo">
                                <option value="promedio">Precio Promedio</option>
                                <option value="fifo">FIFO</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Unidad FE</label>
                            <select class="form-control" id="prod_unidad_codigo"></select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Afectacion IGV</label>
                            <select class="form-control" id="prod_afectacion_igv"></select>
                        </div>
                    </div>
                    <div class="form-row cols-3">
                        <div class="form-group">
                            <label class="form-label">Precio de Compra (S/)</label>
                            <input type="number" class="form-control" id="prod_precio_compra" min="0" step="0.01" value="0" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Precio de Venta (S/)</label>
                            <input type="number" class="form-control" id="prod_precio_venta" min="0" step="0.01" value="0" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Stock Minimo</label>
                            <input type="number" class="form-control" id="prod_stock_min" min="0" step="0.001" value="0">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            Ubicación en Estante
                            <span style="font-weight:600;color:var(--primary)" id="prod_celda_label"></span>
                            <button type="button" class="btn btn-sm" style="padding:1px 8px;margin-left:6px" onclick="limpiarCeldaProducto()">Quitar</button>
                        </label>
                        <div style="color:var(--text-secondary);font-size:.8rem;margin-bottom:6px">Haz clic en una celda para marcar dónde se guarda esta pieza.</div>
                        <div id="prod_estante_selector" style="overflow:auto"></div>
                        <input type="hidden" id="prod_celda_estante" value="">
                    </div>
                </div>
            </div>
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
     MODAL: NUEVA ENTRADA / SALIDA
     ============================================================ -->
<div class="modal-overlay" id="modalMovimiento">
    <div class="modal" style="max-width:460px">
        <div class="modal-header">
            <span class="modal-title" id="modalMovTitle">Nueva Entrada</span>
            <button class="modal-close" onclick="Modal.close('modalMovimiento')">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="mov_tipo" value="entrada">
            <div class="form-group">
                <label class="form-label">Material *</label>
                <select class="form-control" id="mov_producto_id">
                    <option value="">— Seleccionar material —</option>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Cantidad *</label>
                    <input type="number" class="form-control" id="mov_cantidad" min="0.001" step="0.001" placeholder="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Precio Unitario <span style="color:var(--text-secondary);font-size:.75rem">(opcional)</span></label>
                    <input type="number" class="form-control" id="mov_precio" min="0" step="0.01" placeholder="Opcional">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Observaciones</label>
                <textarea class="form-control" id="mov_obs" rows="2"></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('modalMovimiento')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarMovimiento()">
                <i class="fa fa-check"></i> Registrar
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

<!-- ============================================================
     MODAL: IMPORTAR MATERIALES (.xlsx)
     ============================================================ -->
<div class="modal-overlay" id="modalImportar">
    <div class="modal" style="max-width:520px">
        <div class="modal-header">
            <span class="modal-title"><i class="fa fa-file-excel"></i> Importar materiales desde Excel</span>
            <button class="modal-close" onclick="Modal.close('modalImportar')">&times;</button>
        </div>
        <div class="modal-body">
            <div style="font-size:.82rem;color:var(--text-secondary);margin-bottom:12px">
                Archivo <strong>.xlsx</strong> con columnas: Código · SKU · Grupo · Material/Insumo · Unidad ·
                Stock Actual · Stock Mínimo · Precio Compra · Precio Venta.
                El <strong>Grupo</strong> crea la categoría, el <strong>SKU</strong> va como código de barras,
                y los códigos ya existentes se omiten.
            </div>
            <div class="form-group">
                <input type="file" class="form-control" id="imp_archivo" accept=".xlsx">
            </div>
            <div id="imp_resultado" style="font-size:.85rem"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('modalImportar')">Cerrar</button>
            <button class="btn btn-primary" id="imp_btn" onclick="ejecutarImportacion()">
                <i class="fa fa-upload"></i> Importar
            </button>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL: CONFIRMACIÓN (reutilizable)
     ============================================================ -->
<div class="modal-overlay" id="modalConfirm">
    <div class="modal" style="max-width:420px">
        <div class="modal-header">
            <span class="modal-title" id="confirmTitulo">Confirmar</span>
            <button class="modal-close" onclick="Modal.close('modalConfirm')">&times;</button>
        </div>
        <div class="modal-body">
            <p id="confirmMensaje" style="margin:0;color:var(--text-secondary)"></p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('modalConfirm')">Cancelar</button>
            <button class="btn" id="confirmBtnOk" style="background:var(--danger);color:#fff" onclick="ejecutarConfirm()">
                <i class="fa fa-trash"></i> Eliminar
            </button>
        </div>
    </div>
</div>

<?php ob_start(); ?>
<script>
const API_INV = 'api.php';

// Modal de confirmación propio (reemplaza window.confirm).
let _confirmCb = null;
function abrirConfirm(mensaje, onOk, opts) {
    opts = opts || {};
    document.getElementById('confirmTitulo').textContent = opts.titulo || 'Confirmar';
    document.getElementById('confirmMensaje').textContent = mensaje;
    document.getElementById('confirmBtnOk').innerHTML = opts.btn || '<i class="fa fa-trash"></i> Eliminar';
    _confirmCb = onOk;
    Modal.open('modalConfirm');
}
function ejecutarConfirm() {
    Modal.close('modalConfirm');
    const cb = _confirmCb; _confirmCb = null;
    if (cb) cb();
}

// Importar materiales desde Excel (.xlsx)
function abrirModalImportar() {
    document.getElementById('imp_archivo').value = '';
    document.getElementById('imp_resultado').innerHTML = '';
    Modal.open('modalImportar');
}
async function ejecutarImportacion() {
    const file = document.getElementById('imp_archivo').files[0];
    if (!file) return Toast.error('Selecciona un archivo .xlsx');
    const btn = document.getElementById('imp_btn');
    btn.disabled = true; btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Importando...';
    document.getElementById('imp_resultado').innerHTML = '';
    const fd = new FormData();
    fd.append('action', 'importar_materiales');
    fd.append('archivo', file);
    try {
        const resp = await fetch(API_INV, { method: 'POST', body: fd });
        const r = await resp.json();
        if (r.ok) {
            let html = `<div style="color:var(--success);font-weight:700">✓ Importación completada.</div>
                <ul style="margin:8px 0 0 16px;color:var(--text-secondary)">
                    <li>Materiales creados: <strong>${r.creados}</strong></li>
                    <li>Omitidos (código ya existente): <strong>${r.omitidos}</strong></li>
                    <li>Categorías nuevas: <strong>${r.cat_nuevas}</strong></li>
                    <li>Unidades nuevas: <strong>${r.uni_nuevas}</strong></li>
                </ul>`;
            if (r.errores && r.errores.length)
                html += `<div style="color:var(--danger);margin-top:8px">Avisos (${r.errores.length}):<br>${r.errores.slice(0,10).join('<br>')}</div>`;
            document.getElementById('imp_resultado').innerHTML = html;
            Toast.success(`${r.creados} materiales importados.`);
            cargarProductos(); cargarCombos();
        } else {
            document.getElementById('imp_resultado').innerHTML = `<div style="color:var(--danger)">${r.error || 'Error al importar'}</div>`;
            Toast.error(r.error || 'Error al importar');
        }
    } catch(e) {
        document.getElementById('imp_resultado').innerHTML = `<div style="color:var(--danger)">${e.message || 'Error al importar'}</div>`;
    } finally {
        btn.disabled = false; btn.innerHTML = '<i class="fa fa-upload"></i> Importar';
    }
}
const ALMACEN_SOLO_LECTURA = <?= json_encode($almacenSoloLectura) ?>;
let combos = { categorias: [] };
let todosProductos = [];

// ===================== FEATURE ESTANTE (MVC) =====================
// Espejo en JS de la Vista estante.view.php (misma geometria y colores) para
// el selector clickeable del producto y la configuracion global (pestaña Celdas).
let estanteCfg   = { columnas: 6, filas: 6, nombre: 'Estante Principal', grosor: 7, ancho: 360 };
let prodCeldaSel = null;

function colLetra(n) { let s = ''; while (n > 0) { n--; s = String.fromCharCode(65 + (n % 26)) + s; n = Math.floor(n / 26); } return s; }

function cajaEstanteSVG(x, y, w, h, activa, label, clickOnclick) {
    const fill   = activa ? 'var(--primary)' : '#c9d6e5';
    const stroke = activa ? 'var(--primary-dark, #ea580c)' : '#90a4bd';
    const det    = activa ? 'rgba(255,255,255,.55)' : '#90a4bd';
    const cx = x + w / 2;
    let s = `<g style="${clickOnclick ? 'cursor:pointer' : ''}"${clickOnclick || ''}>`;
    s += `<rect x="${x}" y="${y}" width="${w}" height="${h}" rx="3" fill="${fill}" stroke="${stroke}" stroke-width="1.5"/>`;
    s += `<line x1="${x}" y1="${y + h * 0.3}" x2="${x + w}" y2="${y + h * 0.3}" stroke="${det}" stroke-width="1.2"/>`;
    s += `<line x1="${cx}" y1="${y}" x2="${cx}" y2="${y + h * 0.3}" stroke="${det}" stroke-width="1.2"/>`;
    if (label) { const lfs = Math.max(8, Math.min(16, Math.round(h * 0.42)));
        s += `<text x="${cx}" y="${y + h * 0.68}" text-anchor="middle" font-size="${lfs}" font-weight="800" fill="#fff" style="pointer-events:none">${label}</text>`; }
    return s + '</g>';
}

function construirEstanteSVG(cfg, celdaActiva, opts) {
    opts = opts || {};
    const clickable = !!opts.clickable;
    const cols  = Math.max(1, cfg.columnas | 0);
    const filas = Math.max(1, cfg.filas | 0);
    const shelf = Math.min(24, Math.max(1, (cfg.grosor | 0) || 7));
    const ancho = Math.min(1400, Math.max(240, (cfg.ancho | 0) || 360));
    const gutterL = 24, gutterT = 20, pad = 12;
    const cell = Math.max(18, (ancho - 2 * pad - gutterL - 2 * shelf) / cols);
    const rowH = Math.max(26, Math.round(cell * 1.12));
    const boxW = Math.max(12, cell - 8), boxH = Math.max(12, rowH - 14);
    const fs = Math.max(9, Math.min(15, Math.round(cell * 0.28)));
    const gridW = cols * cell, gridH = filas * rowH;
    const leftPostX = pad + gutterL, x0 = leftPostX + shelf, rightPostX = x0 + gridW;
    const frameTop = pad + gutterT, frameBot = frameTop + gridH + shelf;
    const W = rightPostX + shelf + pad, H = frameBot + pad;
    const over = 3, railL = leftPostX - over, railW = (rightPostX + shelf) - railL + over;
    const DARK = '#334155';
    let s = `<svg viewBox="0 0 ${W} ${H}" style="width:100%;max-width:${W}px;height:auto;font-family:inherit">`;
    for (let c = 1; c <= cols; c++) {
        const cx = x0 + (c - 1) * cell + cell / 2;
        s += `<text x="${cx}" y="${frameTop - 7}" text-anchor="middle" font-size="${fs}" font-weight="700" fill="#64748b">${colLetra(c)}</text>`;
    }
    for (let i = 0; i <= filas; i++) {
        const ry = frameTop + i * rowH;
        s += `<rect x="${railL}" y="${ry}" width="${railW}" height="${shelf}" rx="2" fill="${DARK}"/>`;
    }
    s += `<rect x="${leftPostX}" y="${frameTop}" width="${shelf}" height="${gridH + shelf}" rx="2" fill="${DARK}"/>`;
    s += `<rect x="${rightPostX}" y="${frameTop}" width="${shelf}" height="${gridH + shelf}" rx="2" fill="${DARK}"/>`;
    for (let f = filas; f >= 1; f--) {
        const rowIdx = filas - f;
        const bandTop = frameTop + rowIdx * rowH + shelf;
        const shelfY = frameTop + (rowIdx + 1) * rowH;
        const boxY = shelfY - boxH;
        s += `<text x="${pad + gutterL - 8}" y="${bandTop + (rowH - shelf) / 2 + 4}" text-anchor="middle" font-size="${fs}" font-weight="700" fill="#64748b">${f}</text>`;
        for (let c = 1; c <= cols; c++) {
            const boxX = x0 + (c - 1) * cell + (cell - boxW) / 2, code = colLetra(c) + f;
            const activa = celdaActiva && celdaActiva.toUpperCase() === code;
            const click = clickable ? ` onclick="seleccionarCeldaProducto('${code}')"` : '';
            s += cajaEstanteSVG(boxX, boxY, boxW, boxH, activa, activa ? code : '', click);
        }
    }
    return s + '</svg>';
}

async function cargarEstanteCfg() {
    try {
        const d = await apiGet(API_INV + '?action=estante_config');
        if (d.ok) estanteCfg = { columnas: d.columnas, filas: d.filas, nombre: d.nombre, grosor: d.grosor, ancho: d.ancho };
    } catch (e) {}
    llenarFormEstante();
    previewEstante();
}

function renderSelectorProducto() {
    document.getElementById('prod_estante_selector').innerHTML = construirEstanteSVG(estanteCfg, prodCeldaSel, { clickable: true });
    document.getElementById('prod_celda_estante').value = prodCeldaSel || '';
    document.getElementById('prod_celda_label').textContent = prodCeldaSel ? ('· ' + prodCeldaSel) : '';
}
function seleccionarCeldaProducto(code) { prodCeldaSel = (prodCeldaSel === code) ? null : code; renderSelectorProducto(); }
function limpiarCeldaProducto() { prodCeldaSel = null; renderSelectorProducto(); }

// ---- Pestaña Celdas: configuracion global ----
function llenarFormEstante() {
    const el = id => document.getElementById(id);
    if (!el('est_columnas')) return;
    el('est_nombre').value   = estanteCfg.nombre || '';
    el('est_ancho').value    = estanteCfg.ancho;
    el('est_grosor').value   = estanteCfg.grosor;
    el('est_columnas').value = estanteCfg.columnas;
    el('est_filas').value    = estanteCfg.filas;
}
function leerFormEstante() {
    const num = (id, min, max, def) => Math.min(max, Math.max(min, parseInt(document.getElementById(id).value || def, 10) || def));
    return {
        nombre:   (document.getElementById('est_nombre').value || '').trim(),
        ancho:    num('est_ancho', 240, 1400, 360),
        grosor:   num('est_grosor', 1, 24, 7),
        columnas: num('est_columnas', 1, 26, 6),
        filas:    num('est_filas', 1, 99, 6),
    };
}
function previewEstante() {
    if (!document.getElementById('est_preview')) return;
    const cfg = leerFormEstante();
    document.getElementById('est_preview').innerHTML = construirEstanteSVG(cfg, null, {});
    document.getElementById('est_preview_titulo').textContent = cfg.nombre || 'Vista previa';
}
async function guardarEstante() {
    const cfg = leerFormEstante();
    const r = await apiPost(API_INV, { action: 'estante_config_guardar', ...cfg });
    if (r.ok) {
        estanteCfg = { columnas: r.columnas, filas: r.filas, nombre: r.nombre, grosor: r.grosor, ancho: r.ancho };
        llenarFormEstante(); previewEstante();
        Toast.success('Configuración del estante guardada.');
    } else Toast.error(r.error || 'No se pudo guardar la configuracion.');
}
// =================== FIN FEATURE ESTANTE =========================

function irDetalleProducto(id) {
    window.location.href = `producto.php?id=${id}`;
}

document.addEventListener('DOMContentLoaded', () => {
    initTabs();
    cargarEstanteCfg();
    cargarCombos().then(() => {
        cargarProductos();
        cargarRepuestos();
        cargarCategorias();
        cargarEntradas();
        cargarSalidas();

        const editarId = new URLSearchParams(window.location.search).get('editar');
        if (editarId && !ALMACEN_SOLO_LECTURA) {
            editarProducto(parseInt(editarId, 10));
            window.history.replaceState({}, '', 'index.php');
        }
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

    let entDebTimer;
    document.getElementById('entSearch').addEventListener('input', () => {
        clearTimeout(entDebTimer);
        entDebTimer = setTimeout(cargarEntradas, 350);
    });
    let salDebTimer;
    document.getElementById('salSearch').addEventListener('input', () => {
        clearTimeout(salDebTimer);
        salDebTimer = setTimeout(cargarSalidas, 350);
    });

    // Foco automático en el campo de búsqueda/escaneo al cargar
    document.getElementById('prodSearch').focus();
});

// ══════════════════════════════════════════════════
// LECTOR DE CÓDIGO DE BARRAS
// ══════════════════════════════════════════════════
async function buscarPorCodigo(codigo) {
    codigo = codigo.trim();
    const input = document.getElementById('prodSearch');
    if (!codigo) return;
    try {
        const d = await apiGet(`${API_INV}?action=productos_listar&q=${encodeURIComponent(codigo)}`);
        const lista = d.productos || [];
        const exacto = lista.find(p =>
            (p.codigo || '').toLowerCase() === codigo.toLowerCase() ||
            (p.codigo_barras || '').toLowerCase() === codigo.toLowerCase()
        );

        if (exacto) {
            // Coincidencia exacta (típico del escáner): abrir kardex y limpiar para el siguiente escaneo
            Toast.success(`${exacto.nombre} · Stock: ${parseFloat(exacto.stock_actual||0).toFixed(2)} ${exacto.unidad||''}`);
            verKardexProducto(exacto.id);
            input.value = '';
            input.focus();
        } else if (lista.length > 0) {
            // Varios resultados: dejar el texto como filtro en la tabla
            document.querySelector('.tab[data-target="tabProductos"]')?.click();
            cargarProductos();
            Toast.info(`${lista.length} resultado${lista.length>1?'s':''} para "${codigo}"`);
        } else {
            Toast.error(`Código "${codigo}" no encontrado`);
            input.select();
        }
    } catch(e) {
        Toast.error('Error al buscar código');
    }
}


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
        renderUnidadesMedida();
        renderListaUnidades();

        const dp = await apiGet(API_INV + '?action=productos_listar');
        todosProductos = dp.productos || [];
        const prodOpts = '<option value="">— Seleccionar material —</option>' +
            todosProductos.map(p =>
                `<option value="${p.id}" data-stock="${p.stock_actual}" data-pp="${p.precio_promedio}" data-nombre="${p.nombre}">[${p.codigo}] ${p.nombre}</option>`
            ).join('');
        document.getElementById('kardexProducto').innerHTML = prodOpts;
        document.getElementById('mov_producto_id').innerHTML = prodOpts;
    } catch(e) { console.error('Combos error:', e); }
}

function renderUnidadesMedida(seleccionar) {
    const sel = document.getElementById('prod_unidad');
    const actual = seleccionar || sel.value;
    sel.innerHTML = (combos.unidades_medida || []).map(u => `<option value="${u.codigo}">${u.nombre}</option>`).join('');
    if (actual) sel.value = actual;
}

// Lista de unidades de medida en la pestaña Categorias.
function renderListaUnidades() {
    const tb = document.getElementById('listaUnidades');
    if (!tb) return;
    const us = combos.unidades_medida || [];
    const accion = u => ALMACEN_SOLO_LECTURA ? '' :
        `<button class="btn btn-sm" style="background:var(--danger);color:#fff" title="Eliminar" onclick="eliminarUnidad(${u.id})"><i class="fa fa-trash"></i></button>`;
    tb.innerHTML = us.length
        ? us.map(u => `<tr><td><strong>${u.codigo}</strong></td><td>${u.nombre}</td><td class="text-right">${accion(u)}</td></tr>`).join('')
        : '<tr><td colspan="3" class="text-center text-muted">Sin unidades registradas.</td></tr>';
}

function eliminarUnidad(id) {
    abrirConfirm('¿Eliminar esta unidad de medida?', async () => {
        try {
            const r = await apiPost(API_INV, { action: 'unidad_medida_eliminar', id });
            if (r.ok) {
                combos.unidades_medida = (combos.unidades_medida || []).filter(u => u.id != id);
                renderUnidadesMedida();
                renderListaUnidades();
                Toast.success('Unidad eliminada.');
            } else Toast.error(r.error || 'No se pudo eliminar.');
        } catch(e) { Toast.error(e.message || 'Error al eliminar'); }
    }, { titulo: 'Eliminar unidad de medida' });
}

async function guardarUnidad() {
    const codigo = document.getElementById('uni_codigo').value.trim();
    const nombre = document.getElementById('uni_nombre').value.trim();
    if (!codigo || !nombre) return Toast.error('Codigo y nombre son obligatorios.');
    try {
        const r = await apiPost(API_INV, { action: 'unidad_medida_guardar', codigo, nombre });
        if (r.ok) {
            combos.unidades_medida = combos.unidades_medida || [];
            combos.unidades_medida.push({ id: r.id, codigo: r.codigo, nombre: r.nombre });
            renderUnidadesMedida();
            renderListaUnidades();
            document.getElementById('uni_codigo').value = '';
            document.getElementById('uni_nombre').value = '';
            Toast.success('Unidad agregada.');
        } else Toast.error(r.error || 'Error al guardar');
    } catch(e) { Toast.error(e.message || 'Error al guardar'); }
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
            return `<tr class="fila-producto" data-id="${p.id}" onclick="irDetalleProducto(${p.id})">
                <td style="text-align:center">${imgCell}</td>
                <td><code>${p.codigo}</code></td>
                <td>
                    ${p.nombre}
                    ${p.es_repuesto ? '<span class="badge badge-normal" style="font-size:.7rem;margin-left:4px">Repuesto</span>' : ''}
                </td>
                <td>${p.categoria_nombre || '—'}</td>
                <td>${p.unidad}</td>
                <td>${p.ubicacion || '—'}</td>
                <td>
                    <strong style="color:${esCritico?'var(--danger)':'inherit'}">${Math.round(p.stock_actual)}</strong>
                    ${esCritico ? ' <i class="fa fa-triangle-exclamation" style="color:var(--danger);font-size:.8rem" title="Stock critico"></i>' : ''}
                </td>
                <td style="font-size:.82rem">${Math.round(p.stock_minimo)}</td>
                <td>${formatMoney(p.precio_venta)}</td>
                <td>${formatMoney(valor)}</td>
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
    document.getElementById('prod_codigo_barras').value = '';
    document.getElementById('prod_product_type').value = 'product';
    document.getElementById('prod_unidad').value      = 'unidad';
    document.getElementById('prod_unidad_codigo').value = 'NIU';
    document.getElementById('prod_afectacion_igv').value = '20';
    document.getElementById('prod_metodo').value      = 'promedio';
    document.getElementById('prod_stock_min').value    = '0';
    document.getElementById('prod_precio_compra').value = '0';
    document.getElementById('prod_precio_venta').value = '0';
    document.getElementById('prod_incluye_igv').checked = true;
    document.getElementById('prod_es_repuesto').checked = false;
    prodCeldaSel = null; renderSelectorProducto();
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
    codigoEditadoManualmente = false;
    Modal.open('modalProducto');
    // Enfocar el campo de código de barras: si el foco queda en otro input
    // (p.ej. "Código" de una apertura anterior del modal), el lector físico
    // escribe ahí en vez del campo de escaneo.
    setTimeout(() => document.getElementById('prod_codigo_barras').focus(), 50);
    try {
        const d = await apiGet(API_INV + '?action=generar_codigo');
        if (d.ok) document.getElementById('prod_codigo').value = d.codigo;
    } catch(e) { document.getElementById('prod_codigo').value = ''; }
}

// Codigo interno sugerido como abreviacion del nombre (ej. "Carro" -> "CRRO").
// Se regenera mientras el usuario no haya editado el codigo a mano.
let codigoEditadoManualmente = false;
let nombreDebTimer;
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('prod_codigo').addEventListener('input', () => {
        codigoEditadoManualmente = true;
    });
    document.getElementById('prod_nombre').addEventListener('input', () => {
        clearTimeout(nombreDebTimer);
        nombreDebTimer = setTimeout(async () => {
            const esNuevo = !document.getElementById('prod_id').value;
            const nombre  = document.getElementById('prod_nombre').value.trim();
            if (!esNuevo || codigoEditadoManualmente || !nombre) return;
            try {
                const d = await apiGet(`${API_INV}?action=generar_codigo&nombre=${encodeURIComponent(nombre)}`);
                if (d.ok) document.getElementById('prod_codigo').value = d.codigo;
            } catch(e) { /* mantener codigo actual si falla */ }
        }, 400);
    });
});

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
        document.getElementById('prod_codigo_barras').value = p.codigo_barras || '';
        document.getElementById('prod_product_type').value = p.product_type || 'product';
        document.getElementById('prod_unidad').value      = p.unidad;
        document.getElementById('prod_unidad_codigo').value = p.unidad_codigo || 'NIU';
        document.getElementById('prod_afectacion_igv').value = p.afectacion_igv_codigo || '20';
        document.getElementById('prod_metodo').value      = p.metodo_valuacion;
        document.getElementById('prod_stock_min').value    = p.stock_minimo;
        document.getElementById('prod_precio_compra').value = p.precio_compra ?? 0;
        document.getElementById('prod_precio_venta').value = p.precio_venta ?? 0;
        document.getElementById('prod_incluye_igv').checked = !!Number(p.incluye_igv ?? 1);
        document.getElementById('prod_es_repuesto').checked = p.es_repuesto;
        prodCeldaSel = p.celda_estante || null; renderSelectorProducto();
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
    fd.append('codigo_barras', document.getElementById('prod_codigo_barras').value);
    fd.append('categoria_id', document.getElementById('prod_categoria_id').value || '');
    fd.append('unidad', document.getElementById('prod_unidad').value);
    fd.append('unidad_codigo', document.getElementById('prod_unidad_codigo').value);
    fd.append('afectacion_igv_codigo', document.getElementById('prod_afectacion_igv').value);
    fd.append('metodo_valuacion', document.getElementById('prod_metodo').value);
    fd.append('celda_estante', document.getElementById('prod_celda_estante').value);
    fd.append('stock_minimo', document.getElementById('prod_stock_min').value);
    fd.append('precio_compra', document.getElementById('prod_precio_compra').value);
    fd.append('precio_venta', document.getElementById('prod_precio_venta').value);
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

        // Stats bar
        const st = d.stats || {};
        document.getElementById('statsEntQty').textContent = parseFloat(st.ent_qty||0).toFixed(2);
        document.getElementById('statsEntVal').textContent = formatMoney(st.ent_val||0);
        document.getElementById('statsSalQty').textContent = parseFloat(st.sal_qty||0).toFixed(2);
        document.getElementById('statsSalVal').textContent = formatMoney(st.sal_val||0);
        document.getElementById('statsAdjQty').textContent = parseFloat(st.adj_qty||0).toFixed(2);
        document.getElementById('statsTotal').textContent  = st.total || 0;
        const ksEl = document.getElementById('kardexStats');
        ksEl.style.display = 'grid';

        const tipoColor = { entrada: 'badge-completado', salida: 'badge-cancelado', ajuste: 'badge-normal' };
        const rowBorder = { entrada: 'border-left:3px solid #16a34a', salida: 'border-left:3px solid #dc2626', ajuste: 'border-left:3px solid #3b82f6' };
        document.querySelector('#tablaKardex tbody').innerHTML = d.movimientos.map(m => `<tr style="${rowBorder[m.tipo]||''}">
            <td style="white-space:nowrap">${formatDate(m.fecha)}</td>
            <td><span class="badge ${tipoColor[m.tipo]||'badge-normal'}">${m.tipo}</span></td>
            <td style="font-size:.8rem">${m.referencia_tipo || '—'}</td>
            <td style="text-align:right"><strong>${m.tipo==='salida'?'- ':'+'}${parseFloat(m.cantidad).toFixed(2)}</strong></td>
            <td style="text-align:right">${formatMoney(m.precio_unitario)}</td>
            <td style="text-align:right">${m.saldo_cantidad}</td>
            <td style="text-align:right">${formatMoney(m.saldo_valor)}</td>
            <td style="font-size:.8rem">${m.usuario_nombre || '—'}</td>
            <td style="font-size:.8rem">${m.observaciones || '—'}</td>
        </tr>`).join('') || '<tr><td colspan="9" class="text-center text-muted" style="padding:1.5rem">Sin movimientos en el periodo</td></tr>';
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

// ══════════════════════════════════════════════════
// ENTRADAS / SALIDAS
// ══════════════════════════════════════════════════
async function cargarEntradas() { cargarMovimientos('entrada'); }
async function cargarSalidas()  { cargarMovimientos('salida'); }

async function cargarMovimientos(tipo) {
    const prefix = tipo === 'entrada' ? 'ent' : 'sal';
    const q      = document.getElementById(`${prefix}Search`).value;
    const desde  = document.getElementById(`${prefix}Desde`).value;
    const hasta  = document.getElementById(`${prefix}Hasta`).value;
    const tbodySel = tipo === 'entrada' ? '#tablaEntradas tbody' : '#tablaSalidas tbody';
    try {
        const d = await apiGet(`${API_INV}?action=kardex_global&tipo=${tipo}&q=${encodeURIComponent(q)}&desde=${desde}&hasta=${hasta}`);
        if (!d.ok) return;
        document.querySelector(tbodySel).innerHTML = d.movimientos.map(m => `<tr>
            <td style="white-space:nowrap">${formatDate(m.fecha)}</td>
            <td><code>${m.producto_codigo}</code></td>
            <td>${m.producto_nombre}</td>
            <td style="text-align:right"><strong>${parseFloat(m.cantidad).toFixed(2)}</strong></td>
            <td style="text-align:right">${formatMoney(m.precio_unitario)}</td>
            <td style="text-align:right">${m.saldo_cantidad}</td>
            <td style="font-size:.8rem">${m.usuario_nombre || '—'}</td>
            <td style="font-size:.8rem">${m.observaciones || '—'}</td>
        </tr>`).join('') || `<tr><td colspan="8" class="text-center text-muted" style="padding:1.5rem">Sin ${tipo === 'entrada' ? 'entradas' : 'salidas'} registradas</td></tr>`;
    } catch(e) {
        document.querySelector(tbodySel).innerHTML =
            `<tr><td colspan="8" class="text-center" style="color:var(--danger)">Error al cargar ${tipo === 'entrada' ? 'entradas' : 'salidas'}</td></tr>`;
    }
}

function abrirModalMovimiento(tipo) {
    document.getElementById('mov_tipo').value = tipo;
    document.getElementById('mov_producto_id').value = '';
    document.getElementById('mov_cantidad').value = '';
    document.getElementById('mov_precio').value = '';
    document.getElementById('mov_obs').value = '';
    document.getElementById('modalMovTitle').textContent = tipo === 'entrada' ? 'Nueva Entrada' : 'Nueva Salida';
    Modal.open('modalMovimiento');
}

async function guardarMovimiento() {
    const tipo = document.getElementById('mov_tipo').value;
    const producto_id = document.getElementById('mov_producto_id').value;
    const qty = parseFloat(document.getElementById('mov_cantidad').value);
    if (!producto_id) return Toast.error('Seleccione un material.');
    if (!qty || qty <= 0) return Toast.error('Ingrese una cantidad valida.');
    try {
        const r = await apiPost(API_INV, {
            action:          'ajuste_stock',
            producto_id,
            tipo,
            cantidad:        qty,
            precio_unitario: document.getElementById('mov_precio').value || null,
            observaciones:   document.getElementById('mov_obs').value,
        });
        if (r.ok) {
            Toast.success(`Stock actualizado: ${r.nuevo_stock}`);
            Modal.close('modalMovimiento');
            cargarProductos();
            cargarRepuestos();
            cargarCombos();
            if (tipo === 'entrada') cargarEntradas(); else cargarSalidas();
            if (document.getElementById('kardexProducto').value == producto_id) cargarKardex();
        } else Toast.error(r.error || 'Error al registrar');
    } catch(e) { Toast.error(e.message || 'Error al registrar'); }
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
                ${ALMACEN_SOLO_LECTURA ? '' : `
                <div style="display:flex;gap:.25rem">
                    <button class="btn btn-secondary btn-sm" onclick="editarCategoria(${c.id},'${c.nombre.replace(/'/g,"\\'")}','${(c.descripcion||'').replace(/'/g,"\\'")}')">
                        <i class="fa fa-pen"></i>
                    </button>
                    <button class="btn btn-sm" style="background:var(--danger);color:#fff" onclick="eliminarCategoria(${c.id})">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>`}
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
            return `<tr class="fila-producto" data-id="${p.id}" onclick="irDetalleProducto(${p.id})">
                <td style="text-align:center">${imgCell}</td>
                <td><code>${p.codigo}</code></td>
                <td>${p.nombre}</td>
                <td>${p.categoria_nombre || '—'}</td>
                <td>${p.unidad}</td>
                <td>${p.ubicacion || '—'}</td>
                <td>
                    <strong style="color:${esCritico?'var(--danger)':'inherit'}">${Math.round(p.stock_actual)}</strong>
                    ${esCritico ? ' <i class="fa fa-triangle-exclamation" style="color:var(--danger);font-size:.8rem" title="Stock critico"></i>' : ''}
                </td>
                <td style="font-size:.82rem">${Math.round(p.stock_minimo)}</td>
                <td>${formatMoney(p.precio_venta)}</td>
                <td>${formatMoney(valor)}</td>
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


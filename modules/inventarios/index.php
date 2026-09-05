<?php
$page_title      = 'Almacén';
$page_breadcrumb = '<span>Operaciones</span> <span>/</span> Almacén';
require_once __DIR__ . '/../../includes/header.php';

$permisoAlmacen = nivelPermiso('inventarios');
if ($permisoAlmacen === 'ninguno') {
    echo '<div class="alert alert-danger" style="margin:20px">
        <i class="fa fa-lock"></i> No tienes permisos para acceder al módulo de Almacén. Contacta a un administrador.
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
</div>

<!-- ============================================================
     TAB: INVENTARIO (PRODUCTOS)
     ============================================================ -->
<div class="tab-content active" data-group="inv" id="tabProductos">
    <div class="toolbar">
        <div class="toolbar-left">
            <div class="search-box" style="border-color:var(--primary);background:#fff7ed" title="Lector de código de barras — escanea y presiona Enter">
                <span class="icon" style="color:var(--primary)"><i class="fa fa-barcode"></i></span>
                <input type="text" class="form-control" id="scanBarra"
                       placeholder="Escanear código de barras..."
                       style="background:transparent"
                       onkeydown="if(event.key==='Enter'){event.preventDefault();buscarPorCodigo(this.value)}">
            </div>
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
            <?php if (!$almacenSoloLectura): ?>
            <button class="btn btn-primary" onclick="abrirModalProducto()">
                <i class="fa fa-plus"></i> Nuevo Material
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
                    <th>Unidad</th><th>Ubicacion</th><th>Stock</th><th>Min/Max</th>
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
                    <th>Unidad</th><th>Ubicacion</th><th>Stock</th><th>Min/Max</th>
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
                    <div class="form-group">
                        <label class="form-label">Codigo de barras</label>
                        <div class="search-box" style="border:1.5px solid var(--primary);border-radius:var(--radius-sm);background:#fff7ed"
                             title="Escanea con el lector o escribe manualmente">
                            <span class="icon" style="color:var(--primary)"><i class="fa fa-barcode"></i></span>
                            <input type="text" class="form-control" id="prod_codigo_barras" placeholder="Escanear o escribir..."
                                   style="width:100%;background:transparent;border:none"
                                   onkeydown="if(event.key==='Enter'){event.preventDefault();this.blur();}">
                        </div>
                    </div>
                    <div class="form-row cols-3">
                        <div class="form-group">
                            <label class="form-label">Codigo *</label>
                            <input type="text" class="form-control" id="prod_codigo" placeholder="Ej: AC-001">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Unidad</label>
                            <div style="display:flex;gap:6px">
                                <select class="form-control" id="prod_unidad" style="flex:1"></select>
                                <button type="button" class="btn btn-secondary btn-sm" title="Agregar unidad de medida" onclick="abrirModalUnidad()">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ubicacion</label>
                            <input type="text" class="form-control" id="prod_ubicacion" placeholder="Ej: Rack A-3">
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
                            <label class="form-label">Precio de Venta (S/)</label>
                            <input type="number" class="form-control" id="prod_precio_venta" min="0" step="0.01" value="0" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Stock Minimo</label>
                            <input type="number" class="form-control" id="prod_stock_min" min="0" step="0.001" value="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Stock Maximo</label>
                            <input type="number" class="form-control" id="prod_stock_max" min="0" step="0.001" value="0">
                        </div>
                    </div>
                    <div class="form-group" style="max-width:220px">
                        <label class="form-label">Porcentaje IGV</label>
                        <input type="number" class="form-control" id="prod_porcentaje_igv" min="0" step="0.01" value="18.00">
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
     MODAL: NUEVA UNIDAD DE MEDIDA
     ============================================================ -->
<div class="modal-overlay" id="modalUnidad">
    <div class="modal" style="max-width:400px">
        <div class="modal-header">
            <span class="modal-title">Nueva Unidad de Medida</span>
            <button class="modal-close" onclick="Modal.close('modalUnidad')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Codigo *</label>
                <input type="text" class="form-control" id="uni_codigo" placeholder="Ej: pln (plancha)" maxlength="10">
            </div>
            <div class="form-group">
                <label class="form-label">Nombre *</label>
                <input type="text" class="form-control" id="uni_nombre" placeholder="Ej: Plancha">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="Modal.close('modalUnidad')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarUnidad()">
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

<?php ob_start(); ?>
<script>
const API_INV = 'api.php';
const ALMACEN_SOLO_LECTURA = <?= json_encode($almacenSoloLectura) ?>;
let combos = { categorias: [] };
let todosProductos = [];

function irDetalleProducto(id) {
    window.location.href = `producto.php?id=${id}`;
}

document.addEventListener('DOMContentLoaded', () => {
    initTabs();
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

    // Foco automático en el campo de escaneo al cargar
    document.getElementById('scanBarra').focus();
});

// ══════════════════════════════════════════════════
// LECTOR DE CÓDIGO DE BARRAS
// ══════════════════════════════════════════════════
async function buscarPorCodigo(codigo) {
    codigo = codigo.trim();
    const input = document.getElementById('scanBarra');
    if (!codigo) return;
    try {
        const d = await apiGet(`${API_INV}?action=productos_listar&q=${encodeURIComponent(codigo)}`);
        const lista = d.productos || [];
        const exacto = lista.find(p =>
            (p.codigo || '').toLowerCase() === codigo.toLowerCase() ||
            (p.codigo_barras || '').toLowerCase() === codigo.toLowerCase()
        );

        if (exacto) {
            Toast.success(`${exacto.nombre} · Stock: ${parseFloat(exacto.stock_actual||0).toFixed(2)} ${exacto.unidad||''}`);
            verKardexProducto(exacto.id);
        } else if (lista.length > 0) {
            document.getElementById('prodSearch').value = codigo;
            cargarProductos();
            Toast.info(`${lista.length} resultado${lista.length>1?'s':''} para "${codigo}"`);
            document.querySelector('.tab[data-target="tabProductos"]')?.click();
        } else {
            Toast.error(`Código "${codigo}" no encontrado`);
            input.select();
            return;
        }
    } catch(e) {
        Toast.error('Error al buscar código');
    }
    input.value = '';
    input.focus();
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

function abrirModalUnidad() {
    document.getElementById('uni_codigo').value = '';
    document.getElementById('uni_nombre').value = '';
    Modal.open('modalUnidad');
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
            renderUnidadesMedida(r.codigo);
            Toast.success('Unidad agregada.');
            Modal.close('modalUnidad');
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
                <td style="font-size:.82rem">${Math.round(p.stock_minimo)} / ${parseFloat(p.stock_maximo)>0 ? Math.round(p.stock_maximo) : 'inf'}</td>
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
    document.getElementById('prod_afectacion_igv').value = '10';
    document.getElementById('prod_metodo').value      = 'promedio';
    document.getElementById('prod_ubicacion').value   = '';
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
        document.getElementById('prod_afectacion_igv').value = p.afectacion_igv_codigo || '10';
        document.getElementById('prod_metodo').value      = p.metodo_valuacion;
        document.getElementById('prod_ubicacion').value   = p.ubicacion || '';
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
    fd.append('codigo_barras', document.getElementById('prod_codigo_barras').value);
    fd.append('categoria_id', document.getElementById('prod_categoria_id').value || '');
    fd.append('unidad', document.getElementById('prod_unidad').value);
    fd.append('unidad_codigo', document.getElementById('prod_unidad_codigo').value);
    fd.append('afectacion_igv_codigo', document.getElementById('prod_afectacion_igv').value);
    fd.append('metodo_valuacion', document.getElementById('prod_metodo').value);
    fd.append('ubicacion', document.getElementById('prod_ubicacion').value);
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
            <td style="text-align:right"><strong>${m.tipo==='salida'?'- ':'+'}${m.cantidad}</strong></td>
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
            <td style="text-align:right"><strong>${m.cantidad}</strong></td>
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
                <td style="font-size:.82rem">${Math.round(p.stock_minimo)} / ${parseFloat(p.stock_maximo)>0 ? Math.round(p.stock_maximo) : 'inf'}</td>
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


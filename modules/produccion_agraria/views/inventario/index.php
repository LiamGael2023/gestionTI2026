<div class="breadcrumb">
    <div class="container-xl">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?php echo BASE_URL; ?>/produccion_agraria">Prod. Agraria</a>
            </li>
            <li class="breadcrumb-item active">Inventario</li>
        </ol>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <!-- Header con título -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <div class="card mb-3 border-0 shadow-sm">
                    <div class="card-body py-2 px-3">
                        <div class="text-uppercase text-muted fw-bold fs-4">
                            <i class="ti ti-leaf me-2 text-primary"></i>
                            Sistema de Seguimiento y control de Productos Agricolas
                        </div>
                    </div>
                </div>
                <h3 class="fw-bold mb-1">Gestión de Inventario</h3>
                <p class="text-muted mb-0">Control de stock por lotes y trazabilidad PEPS</p>
            </div>
        </div>
        
        <!-- 1. PANEL DE FILTROS Y BÚSQUEDA -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <!-- Selector de Centro de Producción -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Centro de Producción</label>
                        <select class="form-select" id="filtro-centro">
                            <option value="">Todos los centros</option>
                            <?php foreach ($centros as $centro): ?>
                            <option value="<?php echo $centro['id_centro']; ?>"><?php echo htmlspecialchars($centro['nombre_centro']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Filtro de Clase de Producto -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Clase de Producto</label>
                        <select class="form-select" id="filtro-clase">
                            <option value="">Todas las clases</option>
                            <?php foreach ($clases as $clase): ?>
                            <option value="<?php echo $clase['id_clase']; ?>"><?php echo htmlspecialchars($clase['nombre_clase']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Buscador Global -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Buscar</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ti ti-search"></i></span>
                            <input type="text" class="form-control" placeholder="Nombre de producto..." id="buscar-global">
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
        
        
        <!-- 4. TABLA DE PRODUCTOS (NIVEL 1) -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Productos en Inventario</h4>
                <div>
                    <span class="text-muted me-3"><?php echo count($productos); ?> productos registrados</span>
                    <button class="btn btn-warning me-2" onclick="agregarStockMasivo()">
                        <i class="ti ti-package-import me-1"></i>Agregar Stock (+10)
                    </button>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal-producto" onclick="limpiarFormProducto()">
                        <i class="ti ti-plus me-1"></i>Nuevo Producto
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table" id="tabla-productos">
                        <thead>
                            <tr>
                                <th class="w-1 text-center">Imagen</th>
                                <th>Producto</th>
                                <th>Nombre Científico</th>
                                <th>Clase</th>
                                <th>Centro</th>
                                <th class="w-1 text-center">Unidad</th>
                                <th class="w-1 text-center">Maneja Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos as $producto): ?>
                            <tr data-id="<?php echo $producto['id_producto']; ?>" data-centro="<?php echo $producto['id_centro']; ?>" data-clase="<?php echo $producto['id_clase']; ?>" data-nombre="<?php echo strtolower(htmlspecialchars($producto['nombre'])); ?>" style="cursor: pointer;" onclick="mostrarLotes(<?php echo $producto['id_producto']; ?>, '<?php echo htmlspecialchars($producto['nombre'], ENT_QUOTES); ?>')">
                                <td class="text-center">
                                    <?php if (!empty($producto['imagen_nombre'])): ?>
                                    <a href="javascript:void(0)" class="d-inline-block position-relative"
                                       onclick="event.stopPropagation(); mostrarPreviewImagen('<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=ver_imagen_producto&id=<?php echo $producto['id_producto']; ?>', '<?php echo htmlspecialchars($producto['imagen_nombre'], ENT_QUOTES); ?>')"
                                       title="<?php echo htmlspecialchars($producto['imagen_nombre']); ?> - Click para ampliar">
                                        <img src="<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=ver_imagen_producto&id=<?php echo $producto['id_producto']; ?>"
                                             alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                             class="avatar"
                                             style="object-fit: cover; width: 48px; height: 48px; border-radius: 8px; border: 2px solid #dee2e6; background: #f8f9fa;">
                                    </a>
                                    <?php else: ?>
                                    <span class="avatar bg-secondary-lt" style="width: 48px; height: 48px; border-radius: 8px; font-size: 20px; display: inline-flex; align-items: center; justify-content: center;">📦</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <div class="font-weight-medium"><?php echo htmlspecialchars($producto['nombre']); ?></div>
                                            <div class="text-muted small">Código: PRD<?php echo str_pad($producto['id_producto'], 3, '0', STR_PAD_LEFT); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="fst-italic text-muted"><?php echo htmlspecialchars($producto['nombre_cientifico'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($producto['nombre_clase'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($producto['centros'] ?? $producto['nombre_centro'] ?? '-'); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($producto['unidad_medida']); ?></td>
                                <td class="text-center">
                                    <?php if ($producto['maneja_stock']): ?>
                                    <span class="badge bg-success">Sí</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">No</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($productos)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No hay productos registrados</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="pagination-wrapper">
                    <div class="pagination-info" id="paginacion-info">Mostrando <span id="paginacion-inicio">1</span>-<span id="paginacion-fin">15</span> de <span id="paginacion-total"><?php echo count($productos); ?></span> productos</div>
                    <div class="pagination-controls" id="paginacion-controles">
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<!-- Modal: Formulario Producto -->
<div class="modal fade" id="modal-producto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-producto-titulo">Nuevo Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-producto">
                <div class="modal-body">
                    <input type="hidden" id="id_producto" name="id_producto">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">Nombre del Producto</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nombre Científico</label>
                            <input type="text" class="form-control" id="nombre_cientifico" name="nombre_cientifico" placeholder="Ej: Lentinula edodes">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label required">Unidad de Medida</label>
                            <input type="text" class="form-control" id="unidad_medida" name="unidad_medida" required placeholder="Ej: kg, unidades, cajas">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Maneja Stock</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="maneja_stock" name="maneja_stock" value="1" checked>
                                <label class="form-check-label" for="maneja_stock">Sí</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Clase</label>
                            <select class="form-select" id="id_clase" name="id_clase" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($clases as $clase): ?>
                                <option value="<?php echo $clase['id_clase']; ?>"><?php echo htmlspecialchars($clase['nombre_clase']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Centros de Produccion</label>
                            <div class="border rounded p-2" style="max-height: 140px; overflow-y: auto;" id="contenedor-centros-checkboxes">
                                <?php foreach ($centros as $centro): ?>
                                <div class="form-check">
                                    <input class="form-check-input centro-checkbox" type="checkbox" value="<?php echo $centro['id_centro']; ?>" id="chk-centro-<?php echo $centro['id_centro']; ?>" onchange="actualizarCentroPrincipal()">
                                    <label class="form-check-label small" for="chk-centro-<?php echo $centro['id_centro']; ?>"><?php echo htmlspecialchars($centro['nombre_centro']); ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" id="id_centro" name="id_centro">
                            <small class="form-hint text-muted">El primer centro marcado sera el centro principal</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipo de Precio</label>
                            <select class="form-select" id="tipo_precio" name="tipo_precio" onchange="togglePorcentajeUIT()">
                                <option value="">Seleccione...</option>
                                <option value="Variable">Variable</option>
                                <option value="UIT">Porcentaje UIT</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="campo-porcentaje-uit" style="display: none;">
                            <label class="form-label required">Porcentaje UIT (%)</label>
                            <input type="number" step="0.0001" class="form-control" id="porcentaje_uit" name="porcentaje_uit" placeholder="Ej: 0.0350 = 3.5%" oninput="calcularPrecioUIT()">
                            <small class="form-hint">Ejemplo: 0.0350 = 3.5% de una UIT</small>
                        </div>
                        <div class="col-md-6" id="campo-precio-uit" style="display: none;">
                            <label class="form-label">Precio Calculado (S/)</label>
                            <input type="text" class="form-control bg-light" id="precio_calculado" readonly>
                            <small class="form-hint text-muted">Precio basado en UIT actual (<?php echo date('Y'); ?>)</small>
                        </div>
                        <div class="col-md-6" id="campo-precio-actual" style="display: none;">
                            <label class="form-label">Precio Actual (S/)</label>
                            <input type="text" class="form-control bg-light" id="precio_actual" readonly>
                            <small class="form-hint text-muted">Último precio registrado en historial</small>
                        </div>
                        <div class="col-md-6" id="campo-nuevo-precio" style="display: none;">
                            <label class="form-label">Nuevo Precio (S/)</label>
                            <input type="number" step="0.01" class="form-control" id="nuevo_precio" name="nuevo_precio" placeholder="0.00">
                            <small class="form-hint">Dejar vacío para mantener el precio actual</small>
                        </div>
                        <!-- Imagen de referencia del producto -->
                        <div class="col-md-12">
                            <label class="form-label">Imagen de Referencia</label>
                            <input type="file" class="form-control" id="imagen_referencia" name="imagen_referencia" accept="image/jpeg,image/png,image/gif,image/webp" onchange="handleImagenChange(this)">
                            <small class="form-hint text-muted">Formatos: JPG, PNG, GIF, WEBP</small>
                            
                            <!-- Preview de imagen actual o nueva -->
                            <div class="mt-2 p-3 bg-light border rounded text-center" id="preview-imagen-container" style="display: none;">
                                <div class="d-inline-block position-relative">
                                    <img id="preview-imagen" src="" alt="Vista previa" class="shadow-sm"
                                         style="max-height: 150px; max-width: 150px; object-fit: cover; border-radius: 8px; border: 2px solid #dee2e6; display: block;">
                                    <span id="preview-badge-nueva" class="badge bg-success position-absolute top-0 end-0 m-1" style="display: none;">NUEVA</span>
                                    <span id="preview-badge-actual" class="badge bg-info position-absolute top-0 end-0 m-1" style="display: none;">ACTUAL</span>
                                </div>
                                <div id="preview-imagen-nombre" class="text-muted small mt-2 fw-semibold"></div>
                            </div>
                            
                            <div class="form-check mt-2" id="check-eliminar-imagen-container" style="display: none;">
                                <input class="form-check-input" type="checkbox" id="eliminar_imagen" name="eliminar_imagen" value="1" onchange="handleEliminarImagenChange(this)">
                                <label class="form-check-label text-danger" for="eliminar_imagen">Eliminar imagen actual</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btn-guardar-producto" onclick="handleSubmitProducto(event)">
                        <i class="ti ti-check me-1"></i>Guardar
                    </button>
                </div>
                <!-- Area de debug visible para diagnosticar problemas -->
                <div id="debug-area" class="mx-3 mb-2 p-2 small text-muted border-top" style="display: none; font-family: monospace; max-height: 80px; overflow-y: auto;"></div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Previsualizacion Ampliada de Imagen -->
<div class="modal fade" id="modal-preview-imagen" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-photo me-2"></i>Imagen de Referencia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <img id="preview-imagen-ampliada" src="" alt="Vista previa ampliada" class="img-fluid rounded shadow-sm" style="max-height: 400px; object-fit: contain;">
                <div id="preview-imagen-ampliada-nombre" class="text-muted mt-3 small"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Estilos compartidos del módulo -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/variables.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/components.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/common.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/responsive.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/inventario.css">

<!-- 5. MODAL DETALLE DE PRODUCTO - LOTES + KARDEX -->
<div class="modal fade" id="modal-detalle-producto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <div class="d-flex align-items-center">
                    <i class="ti ti-box fs-3 me-3"></i>
                    <div>
                        <h5 class="modal-title mb-0" id="modal-detalle-titulo">Detalle del Producto</h5>
                        <small class="opacity-75" id="modal-detalle-subtitulo">Código: PRD001 | Clase: Hongos</small>
                    </div>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <button class="btn btn-primary btn-sm" onclick="accionEditarDesdeDetalle()" title="Editar Producto">
                        <i class="ti ti-edit me-1"></i>Editar
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="accionEliminarDesdeDetalle()" title="Eliminar Producto">
                        <i class="ti ti-trash me-1"></i>Eliminar
                    </button>
                    <div class="vr mx-2 opacity-50"></div>
                    <!-- Acción de Producto -->
                    <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#modal-reajuste-stock" data-bs-dismiss="modal">
                        <i class="ti ti-adjustments me-1"></i>Reajuste Stock
                    </button>
                    <div class="vr mx-2 opacity-50"></div>
                    <!-- Exportar -->
                    <button class="btn btn-light btn-sm" onclick="exportarDetalleProductoPDF()" title="Exportar PDF">
                        <i class="ti ti-file-type-pdf me-1"></i>PDF
                    </button>
                    <button class="btn btn-light btn-sm" onclick="exportarDetalleProductoExcel()" title="Exportar Excel">
                        <i class="ti ti-file-type-xls me-1"></i>Excel
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body">
                <!-- Resumen del Producto -->
                <div class="row g-3 mb-4 d-none">
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <small class="text-muted">Stock Total</small>
                                <h4 class="mb-0 text-success" id="modal-stock-total">0 unid.</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <small class="text-muted">Lotes Activos</small>
                                <h4 class="mb-0 text-primary" id="modal-lotes-activos">0</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <small class="text-muted">Total Entradas</small>
                                <h4 class="mb-0 text-info" id="modal-total-entradas">0 unid.</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <small class="text-muted">Total Salidas</small>
                                <h4 class="mb-0 text-warning" id="modal-total-salidas">0 unid.</h4>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- SECCIÓN 1: LOTES ACTIVOS -->
                <div class="card mb-4">
                    <div class="card-header bg-primary-lt d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="ti ti-packages me-2"></i>Lotes Activos - Stock por Lote
                        </h5>
                        <button class="btn btn-success btn-sm" onclick="mostrarModalNuevoLote()">
                            <i class="ti ti-plus me-1"></i>Nuevo Lote
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th>Lote (Fecha Creacion)</th>
                                <th>Centros</th>
                                        <th class="text-center">Stock</th>
                                        <th>Antiguedad</th>
                                        <th class="text-center">Estado PEPS</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="modal-tbody-lotes">
                                    <!-- Los lotes se cargarán dinámicamente -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- SECCIÓN 2: KARDEX DEL PRODUCTO -->
                <div class="card">
                    <div class="card-header bg-info-lt">
                        <h5 class="card-title mb-0">
                            <i class="ti ti-file-text me-2"></i>Kardex del Producto - Historial de Movimientos
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-vcenter table-sm">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th>Observación</th>
                                        <th>Lote Afectado</th>
                                        <th class="text-end">Entrada</th>
                                        <th class="text-end">Salida</th>
                                        <th class="text-end">Stock Acumulado</th>
                                    </tr>
                                </thead>
                                <tbody id="modal-tbody-kardex">
                                    <!-- El kardex se cargará dinámicamente -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Nuevo Lote -->
<div class="modal fade" id="modal-nuevo-lote" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="ti ti-package-import me-2"></i>Nuevo Lote</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-nuevo-lote">
                <div class="modal-body">
                    <input type="hidden" id="lote-id-producto" name="id_producto">
                    <div class="mb-3">
                        <label class="form-label required">Codigo de Lote</label>
                        <input type="text" class="form-control bg-light" id="lote-codigo" name="codigo_lote" readonly required placeholder="Auto-generado">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stock Inicial</label>
                        <input type="number" class="form-control" id="lote-stock" name="stock_inicial" min="0" value="0" placeholder="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Centro de Producción</label>
                        <select class="form-select" id="lote-centro" name="id_centro">
                            <option value="">Seleccione centro...</option>
                            <?php foreach ($centros as $centro): ?>
                            <option value="<?php echo $centro['id_centro']; ?>"><?php echo htmlspecialchars($centro['nombre_centro']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="ti ti-check me-1"></i>Crear Lote
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Reportar Merma -->
<div class="modal fade" id="modal-reportar-merma" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-white">
                <h5 class="modal-title"><i class="ti ti-alert-triangle me-2"></i>Reportar Merma</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-merma">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="ti ti-info-circle me-2"></i>La merma descontará automáticamente del stock del lote seleccionado.
                    </div>
                    <input type="hidden" id="merma-id-producto" name="id_producto">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Lote</label>
                            <input type="text" class="form-control bg-light" id="merma-lote-texto" readonly>
                            <input type="hidden" id="merma-id-lote" name="id_lote">
                        </div>
                        
                        <div class="col-md-12">
                            <label class="form-label text-muted fw-semibold">Método de Descuento</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="merma_metodo" id="metodo-exacto" value="exacto" checked autocomplete="off">
                                <label class="btn btn-outline-warning d-flex align-items-center justify-content-center py-2" for="metodo-exacto">
                                    <i class="ti ti-hash me-2 fs-3"></i>Cantidad Exacta
                                </label>
                                
                                <input type="radio" class="btn-check" name="merma_metodo" id="metodo-porcentaje" value="porcentaje" autocomplete="off">
                                <label class="btn btn-outline-warning d-flex align-items-center justify-content-center py-2" for="metodo-porcentaje">
                                    <i class="ti ti-percentage me-2 fs-3"></i>Porcentaje (%)
                                </label>
                            </div>
                        </div>

                        
                        <!-- Contenedor Cantidad Exacta -->
                        <div class="col-md-6" id="container-cantidad-exacta">
                            <label class="form-label">Cantidad Afectada</label>
                            <input type="number" class="form-control" id="merma-cantidad" name="cantidad" min="1" required placeholder="0">
                        </div>

                        <!-- Contenedor Cantidad Porcentual (Oculto por defecto) -->
                        <div class="col-md-6 d-none" id="container-cantidad-porcentual">
                            <label class="form-label">Porcentaje de Merma</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="merma-porcentaje" min="0.01" max="100" step="any" placeholder="0.00">
                                <span class="input-group-text bg-warning-lt text-warning border-warning">%</span>
                            </div>
                        </div>

                        <!-- Contenedor Preview Cálculo (Oculto por defecto) -->
                        <div class="col-12 d-none" id="container-preview-calculado">
                            <div class="card bg-warning-lt border-0 shadow-none">
                                <div class="card-body py-2 px-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <span class="text-warning-muted small d-block">Equivalente en unidades (Redondeado hacia abajo)</span>
                                            <strong class="h3 mb-0 text-warning" id="txt-resultado-preview">0 unidades</strong>
                                        </div>
                                        <div class="text-warning fs-1">
                                            <i class="ti ti-calculator"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Motivo Detallado</label>
                            <textarea class="form-control" id="merma-motivo" name="motivo" rows="3" placeholder="Describa las causas de la merma..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">Reportar Merma</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Reajuste de Stock -->
<div class="modal fade" id="modal-reajuste-stock" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="ti ti-adjustments me-2"></i>Reajuste de Stock</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="ti ti-alert-triangle me-2"></i><strong>Acción administrativa:</strong> Esta operación quedará registrada para auditoría.
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Centro de Producción</label>
                        <select class="form-select">
                            <option>Centro 1 - Huaral</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Producto y Lote</label>
                        <select class="form-select">
                            <option>Hongos Shiitake - Lote: 2025-04-08</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Stock Teórico (Sistema)</label>
                        <input type="number" class="form-control" value="180" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Stock Físico (Contado)</label>
                        <input type="number" class="form-control" placeholder="Ingrese cantidad real">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Diferencia</label>
                        <div class="alert alert-secondary mb-0">
                            <strong>Variación:</strong> <span id="diferencia-stock">Por calcular</span>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Justificación del Reajuste</label>
                        <textarea class="form-control" rows="3" placeholder="Explique por qué se realiza este reajuste..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-info">Aplicar Reajuste</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Merma Rápida (desde fila) -->
<div class="modal fade" id="modal-merma" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Registrar Merma de Lote</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Producto</label>
                    <input type="text" class="form-control" value="Hongos Shiitake" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Lote</label>
                    <input type="text" class="form-control" value="2025-04-08" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Stock Actual</label>
                    <input type="text" class="form-control" value="180 unidades" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tipo de Merma</label>
                    <select class="form-select">
                        <option>Vencimiento</option>
                        <option>Deterioro</option>
                        <option>Plaga</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Cantidad</label>
                    <input type="number" class="form-control" placeholder="Cantidad a descontar">
                </div>
                <div class="mb-3">
                    <label class="form-label">Motivo</label>
                    <textarea class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning">Registrar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Datos de vinculacion clase-centro para filtro en formulario
const VINCULACIONES = <?php echo json_encode($vinculaciones); ?>;
// Mostrar detalle de producto (lotes + kardex) - Carga desde BD vía AJAX
function mostrarLotes(productoId, nombreProducto) {
    // Guardar producto actual para crear lotes
    productoActualId = productoId;
    productoActualNombre = nombreProducto;
    
    const modal = new bootstrap.Modal(document.getElementById('modal-detalle-producto'));
    
    // Actualizar título
    document.getElementById('modal-detalle-titulo').textContent = nombreProducto;
    document.getElementById('modal-detalle-subtitulo').textContent = `ID: ${productoId}`;
    
    // Mostrar loading
    document.getElementById('modal-tbody-lotes').innerHTML = '<tr><td colspan="6" class="text-center py-4"><div class="spinner-border text-success"></div><p class="mt-2 text-muted">Cargando lotes...</p></td></tr>';
    document.getElementById('modal-tbody-kardex').innerHTML = '<tr><td colspan="7" class="text-center py-4"><div class="spinner-border text-success"></div><p class="mt-2 text-muted">Cargando movimientos...</p></td></tr>';
    
    modal.show();
    
    // Cargar lotes
    fetch(`<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=obtener_lotes&id_producto=${productoId}`)
        .then(r => r.text())
        .then(text => {
            const trimmed = text.trim();
            const jsonStart = trimmed.indexOf('{');
            const jsonEnd = trimmed.lastIndexOf('}');
            if (jsonStart === -1 || jsonEnd === -1) return { lotes: [], stock_total: 0 };
            return JSON.parse(trimmed.substring(jsonStart, jsonEnd + 1));
        })
        .then(data => {
            const lotes = data.lotes || [];
            modalLotesData = lotes; // Guardar caché global
            const stockTotal = data.stock_total || 0;
            
            // Actualizar resumen
            document.getElementById('modal-stock-total').textContent = stockTotal.toLocaleString() + ' unid.';
            document.getElementById('modal-lotes-activos').textContent = lotes.length;
            
            // Generar HTML de lotes
            const tbodyLotes = document.getElementById('modal-tbody-lotes');
            if (lotes.length === 0) {
                tbodyLotes.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">No hay lotes activos</td></tr>';
            } else {
                tbodyLotes.innerHTML = lotes.map((lote) => {
                    const estadoClass = lote.estado_texto === 'Agotado' ? 'bg-secondary' : 
                                       lote.estado_texto === 'Stock Crítico' ? 'bg-danger' :
                                       lote.estado_texto === 'Por Vencer' ? 'bg-warning' : 'bg-success';
                    const rowClass = lote.estado_texto === 'Stock Crítico' ? 'table-danger' : '';
                    return `
                        <tr class="${rowClass}">
                            <td><code>${lote.codigo_lote}</code></td>
                            <td><span class="text-muted small">${lote.nombre_centro || '-'}</span></td>
                            <td class="text-center">
                                <span class="badge ${lote.stock_actual < 50 ? 'bg-warning' : 'bg-success'} fs-6">${parseInt(lote.stock_actual).toLocaleString()}</span>
                            </td>
                            <td>
                                <span class="${lote.antiguedad_dias > 20 ? 'text-danger fw-bold' : lote.antiguedad_dias > 7 ? 'text-warning' : 'text-success'}">
                                    <i class="ti ti-clock me-1"></i>${lote.antiguedad_dias} días
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge ${estadoClass}">${lote.estado_texto}</span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-white btn-icon" onclick="mostrarModalMerma(${lote.id_lote}, '${lote.codigo_lote}', ${lote.stock_actual})" title="Registrar Merma">
                                    <i class="ti ti-minus text-danger"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                }).join('');
            }
        })
        .catch(err => {
            document.getElementById('modal-tbody-lotes').innerHTML = '<tr><td colspan="6" class="text-center py-4 text-danger">Error al cargar lotes</td></tr>';
        });
    
    // Cargar kardex
    fetch(`<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=obtener_kardex&id_producto=${productoId}`)
        .then(r => r.text())
        .then(text => {
            const trimmed = text.trim();
            const jsonStart = trimmed.indexOf('{');
            const jsonEnd = trimmed.lastIndexOf('}');
            if (jsonStart === -1 || jsonEnd === -1) return { movimientos: [] };
            return JSON.parse(trimmed.substring(jsonStart, jsonEnd + 1));
        })
        .then(data => {
            const movimientos = data.movimientos || [];
            modalKardexData = movimientos; // Guardar caché global
            
            // Calcular totales (REINTEGRO es entrada - devuelve stock al sistema)
            const totalEntradas = movimientos.filter(m => m.tipo_movimiento === 'Entrada' || m.tipo_movimiento === 'INGRESO' || m.tipo_movimiento === 'REINTEGRO').reduce((sum, m) => sum + (m.cantidad || 0), 0);
            const totalSalidas = movimientos.filter(m => m.tipo_movimiento === 'Salida' || m.tipo_movimiento === 'VENTA' || m.tipo_movimiento === 'MERMA' || m.tipo_movimiento === 'DONACION' || m.tipo_movimiento === 'REAJUSTE').reduce((sum, m) => sum + (m.cantidad || 0), 0);
            
            document.getElementById('modal-total-entradas').textContent = totalEntradas.toLocaleString() + ' unid.';
            document.getElementById('modal-total-salidas').textContent = totalSalidas.toLocaleString() + ' unid.';
            
            // Generar HTML de kardex
            const tbodyKardex = document.getElementById('modal-tbody-kardex');
            if (movimientos.length === 0) {
                tbodyKardex.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted">No hay movimientos registrados</td></tr>';
            } else {
                tbodyKardex.innerHTML = movimientos.map((mov) => {
                    const tipoClass = mov.tipo_movimiento === 'Entrada' || mov.tipo_movimiento === 'INGRESO' || mov.tipo_movimiento === 'REINTEGRO' ? 'bg-primary' : 
                                     mov.tipo_movimiento === 'Merma' || mov.tipo_movimiento === 'DONACION' ? 'bg-danger' : 'bg-warning';
                    const esEntrada = mov.tipo_movimiento === 'Entrada' || mov.tipo_movimiento === 'INGRESO' || mov.tipo_movimiento === 'REINTEGRO';
                    const cantidad = parseInt(mov.cantidad) || 0;
                    return `
                        <tr>
                            <td>${new Date(mov.fecha).toLocaleDateString('es-PE')}</td>
                            <td><span class="badge ${tipoClass}">${mov.tipo_movimiento}</span></td>
                            <td>${mov.documento || '-'}</td>
                            <td><code>${mov.codigo_lote || '-'}</code></td>
                            <td class="text-end ${esEntrada ? 'text-success fw-bold' : ''}">${esEntrada ? '+' + cantidad.toLocaleString() : '-'}</td>
                            <td class="text-end ${!esEntrada ? 'text-warning fw-bold' : ''}">${!esEntrada ? '-' + cantidad.toLocaleString() : '-'}</td>
                            <td class="text-end fw-bold">${parseInt(mov.saldo_final || 0).toLocaleString()}</td>
                        </tr>
                    `;
                }).join('');
            }
        })
        .catch(err => {
            document.getElementById('modal-tbody-kardex').innerHTML = '<tr><td colspan="7" class="text-center py-4 text-danger">Error al cargar movimientos</td></tr>';
        });
}

function accionEditarDesdeDetalle() {
    if (!productoActualId) return;
    const modalDetalle = bootstrap.Modal.getInstance(document.getElementById('modal-detalle-producto'));
    if (modalDetalle) modalDetalle.hide();
    editarProducto(productoActualId);
}

function accionEliminarDesdeDetalle() {
    if (!productoActualId) return;
    const modalDetalle = bootstrap.Modal.getInstance(document.getElementById('modal-detalle-producto'));
    if (modalDetalle) modalDetalle.hide();
    eliminarProducto(productoActualId);
}

// Variables globales para el producto actual en el modal
let productoActualId = null;
let productoActualNombre = null;
let loteActualId = null;
let loteActualStock = null;
let modalLotesData = [];
let modalKardexData = [];

// Mostrar modal para reportar merma
function mostrarModalMerma(idLote, codigoLote, stockActual) {
    loteActualId = idLote;
    loteActualStock = stockActual;
    
    // Cerrar el modal de detalle temporalmente
    const modalDetalle = bootstrap.Modal.getInstance(document.getElementById('modal-detalle-producto'));
    if (modalDetalle) {
        modalDetalle.hide();
    }
    
    // Configurar el formulario
    document.getElementById('merma-id-producto').value = productoActualId;
    document.getElementById('merma-id-lote').value = idLote;
    document.getElementById('merma-lote-texto').value = codigoLote + ' (Stock: ' + stockActual + ')';
    document.getElementById('merma-cantidad').value = '';
    document.getElementById('merma-motivo').value = '';
    
    // Reiniciar campos de porcentaje y método de descuento
    const mermaPorcentaje = document.getElementById('merma-porcentaje');
    if (mermaPorcentaje) {
        mermaPorcentaje.value = '';
    }
    const metodoExacto = document.getElementById('metodo-exacto');
    if (metodoExacto) {
        metodoExacto.checked = true;
        // Lanzar evento change para restablecer la visualización
        metodoExacto.dispatchEvent(new Event('change'));
    }
    
    // Mostrar modal merma
    const modalMerma = new bootstrap.Modal(document.getElementById('modal-reportar-merma'));
    modalMerma.show();
    
    // Al cerrar modal de merma, reabrir modal de detalle
    document.getElementById('modal-reportar-merma').addEventListener('hidden.bs.modal', function() {
        mostrarLotes(productoActualId, productoActualNombre);
    }, { once: true });
}

// Guardar merma
function guardarMerma(e) {
    e.preventDefault();
    
    const formData = new FormData(document.getElementById('form-merma'));
    const cantidad = parseFloat(formData.get('cantidad')) || 0;
    
    // Validar cantidad mayor a cero
    if (cantidad <= 0) {
        Swal.fire('Advertencia', 'La cantidad de merma a descontar debe ser mayor a 0 unidades', 'warning');
        return;
    }
    
    // Validar que no exceda el stock
    if (cantidad > loteActualStock) {
        Swal.fire('Advertencia', 'La cantidad no puede exceder el stock actual del lote (' + loteActualStock + ')', 'warning');
        return;
    }
    
    const data = {
        id_lote: parseInt(formData.get('id_lote')),
        id_producto: parseInt(formData.get('id_producto')),
        cantidad: cantidad,
        motivo: formData.get('motivo')
    };
    
    fetch('<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=guardar_merma', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(r => r.text())
    .then(text => {
        const trimmed = text.trim();
        const jsonStart = trimmed.indexOf('{');
        const jsonEnd = trimmed.lastIndexOf('}');
        if (jsonStart === -1 || jsonEnd === -1) {
            Swal.fire('Error', 'Respuesta inválida del servidor', 'error');
            return;
        }
        const result = JSON.parse(trimmed.substring(jsonStart, jsonEnd + 1));
        if (result.success) {
            Swal.fire({ icon: 'success', title: 'Guardado', text: 'Merma registrada exitosamente', timer: 1500, showConfirmButton: false });
            bootstrap.Modal.getInstance(document.getElementById('modal-reportar-merma')).hide();
        } else {
            Swal.fire('Error', result.message || 'No se pudo registrar la merma', 'error');
        }
    })
    .catch(err => {
        Swal.fire('Error', 'Error de conexión', 'error');
    });
}

// Mostrar modal para crear nuevo lote
function mostrarModalNuevoLote() {
    if (!productoActualId) {
        Swal.fire('Advertencia', 'Seleccione un producto', 'warning');
        return;
    }
    
    // Cerrar el modal de detalle temporalmente
    const modalDetalle = bootstrap.Modal.getInstance(document.getElementById('modal-detalle-producto'));
    if (modalDetalle) {
        modalDetalle.hide();
    }
    
    // Configurar el formulario
    document.getElementById('lote-id-producto').value = productoActualId;
    // Generar codigo automatico: LOT-YYYYMMDD-PRD{id}
    var ahora = new Date();
    var fechaCod = ahora.getFullYear() +
        String(ahora.getMonth() + 1).padStart(2, '0') +
        String(ahora.getDate()).padStart(2, '0');
    document.getElementById('lote-codigo').value = 'LOT-' + fechaCod + '-PRD' + productoActualId;
    document.getElementById('lote-stock').value = '0';
    document.getElementById('lote-centro').value = '';
    
    // Filtrar centros: solo los vinculados al producto
    var selectCentro = document.getElementById('lote-centro');
    var options = selectCentro.options;
    for (var i = 1; i < options.length; i++) {
        options[i].style.display = 'none';
    }
    
    fetch('<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=obtener_producto&id=' + productoActualId)
        .then(function(r) { return r.text(); })
        .then(function(text) {
            var trimmed = text.trim();
            var jsonStart = trimmed.indexOf('{');
            var jsonEnd = trimmed.lastIndexOf('}');
            if (jsonStart === -1 || jsonEnd === -1) return;
            var prod = JSON.parse(trimmed.substring(jsonStart, jsonEnd + 1));
            
            if (prod && prod.centros) {
                var centrosIds = prod.centros.map(function(c) { return parseInt(c.id_centro); });
                for (var i = 1; i < options.length; i++) {
                    if (centrosIds.includes(parseInt(options[i].value))) {
                        options[i].style.display = '';
                    }
                }
                // Seleccionar primer centro disponible
                if (centrosIds.length > 0) {
                    selectCentro.value = centrosIds[0];
                }
            } else {
                // Fallback: mostrar todos
                for (var i = 1; i < options.length; i++) {
                    options[i].style.display = '';
                }
            }
        })
        .catch(function() {
            // Fallback en error
            for (var i = 1; i < options.length; i++) {
                options[i].style.display = '';
            }
        });
    
    // Mostrar modal nuevo lote
    const modalLote = new bootstrap.Modal(document.getElementById('modal-nuevo-lote'));
    modalLote.show();
    
    // Al cerrar modal de lote, reabrir modal de detalle
    document.getElementById('modal-nuevo-lote').addEventListener('hidden.bs.modal', function() {
        mostrarLotes(productoActualId, productoActualNombre);
    }, { once: true });
}

// Guardar nuevo lote
function guardarLote(e) {
    e.preventDefault();
    
    const formData = new FormData(document.getElementById('form-nuevo-lote'));
    const data = {
        id_producto: parseInt(formData.get('id_producto')),
        codigo_lote: formData.get('codigo_lote'),
        stock_inicial: parseFloat(formData.get('stock_inicial')) || 0,
        id_centro: formData.get('id_centro') || null
    };
    
    fetch('<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=guardar_lote', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(r => r.text())
    .then(text => {
        const trimmed = text.trim();
        const jsonStart = trimmed.indexOf('{');
        const jsonEnd = trimmed.lastIndexOf('}');
        if (jsonStart === -1 || jsonEnd === -1) {
            Swal.fire('Error', 'Respuesta inválida del servidor', 'error');
            return;
        }
        const result = JSON.parse(trimmed.substring(jsonStart, jsonEnd + 1));
        if (result.success) {
            Swal.fire({ icon: 'success', title: 'Guardado', text: 'Lote creado exitosamente', timer: 1500, showConfirmButton: false });
            bootstrap.Modal.getInstance(document.getElementById('modal-nuevo-lote')).hide();
            // El evento hidden.bs.modal recargará el modal de detalle
        } else {
            Swal.fire('Error', result.message || 'No se pudo crear el lote', 'error');
        }
    })
    .catch(err => {
        Swal.fire('Error', 'Error de conexión', 'error');
    });
}

// Helper para obtener clase del producto
function getClaseProducto(productoId) {
    const clases = {
        'PRD001': 'Hongos',
        'PRD002': 'Plantones',
        'PRD003': 'Frutas',
        'PRD004': 'Insectos',
        'PRD005': 'Plantones',
        'PRD006': 'Hongos'
    };
    return clases[productoId] || '-';
}

// Toggle Stock Crítico
function toggleStockCritico() {
    const btn = document.getElementById('btn-stock-critico');
    btn.classList.toggle('btn-outline-danger');
    btn.classList.toggle('btn-danger');
    
    if (btn.classList.contains('btn-danger')) {
        btn.innerHTML = '<i class="ti ti-check me-1"></i>Mostrando Críticos';
        // Aquí se filtraría la tabla para mostrar solo stock crítico
    } else {
        btn.innerHTML = '<i class="ti ti-alert-triangle me-1"></i>Stock Crítico';
        // Aquí se mostraría toda la tabla
    }
}

// ========================================
// GESTIÓN DE PRODUCTOS - CRUD
// ========================================

let modalProducto = null;
let formProducto = null;

// Helper para convertir archivo a base64
function fileToBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = () => resolve(reader.result.split(',')[1]);
        reader.onerror = error => reject(error);
    });
}

// Debug visible en la página para diagnosticar sin depender de la consola
function debugLog(msg) {
    const debugArea = document.getElementById('debug-area');
    if (debugArea) {
        debugArea.style.display = 'block';
        const time = new Date().toLocaleTimeString();
        debugArea.innerHTML += `<div>[${time}] ${msg}</div>`;
        debugArea.scrollTop = debugArea.scrollHeight;
    }
    console.log(msg);
}

// Mostrar previsualizacion ampliada de imagen
function mostrarPreviewImagen(imgUrl, imgNombre) {
    const imgEl = document.getElementById('preview-imagen-ampliada');
    const nombreEl = document.getElementById('preview-imagen-ampliada-nombre');
    
    if (imgEl) imgEl.src = imgUrl;
    if (nombreEl) nombreEl.textContent = imgNombre || '';
    
    const modal = new bootstrap.Modal(document.getElementById('modal-preview-imagen'));
    modal.show();
}

// Manejar cambio de imagen en el input file
function handleImagenChange(input) {
    debugLog('handleImagenChange llamado');
    const file = input.files[0];
    const previewImg = document.getElementById('preview-imagen');
    const previewContainer = document.getElementById('preview-imagen-container');
    const previewNombre = document.getElementById('preview-imagen-nombre');
    const checkEliminarContainer = document.getElementById('check-eliminar-imagen-container');
    
    const badgeNueva = document.getElementById('preview-badge-nueva');
    const badgeActual = document.getElementById('preview-badge-actual');
    
    if (file) {
        debugLog('Archivo seleccionado: ' + file.name + ' | tipo: ' + file.type + ' | tamaño: ' + file.size + ' bytes');
        // Usar URL.createObjectURL para preview más rápido y robusto
        const objectUrl = URL.createObjectURL(file);
        if (previewImg) {
            previewImg.src = objectUrl;
            previewImg.style.display = 'block';
            previewImg.onload = function() {
                debugLog('Vista previa cargada correctamente');
            };
            previewImg.onerror = function() {
                debugLog('ERROR: No se pudo mostrar la vista previa');
                Swal.fire('Error', 'No se pudo mostrar la vista previa de la imagen', 'warning');
            };
        }
        if (previewContainer) {
            previewContainer.style.display = 'block';
        }
        if (previewNombre) {
            previewNombre.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
        }
        if (badgeNueva) badgeNueva.style.display = 'inline-block';
        if (badgeActual) badgeActual.style.display = 'none';
        // Ocultar checkbox eliminar si se selecciona nueva imagen
        if (checkEliminarContainer) checkEliminarContainer.style.display = 'none';
    } else {
        if (previewImg) previewImg.src = '';
        if (previewContainer) previewContainer.style.display = 'none';
        if (previewNombre) previewNombre.textContent = '';
        if (badgeNueva) badgeNueva.style.display = 'none';
        if (badgeActual) badgeActual.style.display = 'none';
        debugLog('No hay archivo seleccionado');
    }
}

// Manejar checkbox eliminar imagen
function handleEliminarImagenChange(checkbox) {
    const previewContainer = document.getElementById('preview-imagen-container');
    const previewImg = document.getElementById('preview-imagen');
    if (checkbox.checked) {
        if (previewContainer) previewContainer.style.display = 'none';
    } else {
        const idProducto = document.getElementById('id_producto').value;
        if (idProducto && previewImg && previewImg.dataset.hasImage === 'true') {
            previewImg.src = `<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=ver_imagen_producto&id=${idProducto}`;
            previewContainer.style.display = 'block';
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    formProducto = document.getElementById('form-producto');
    const modalEl = document.getElementById('modal-producto');
    if (modalEl && typeof bootstrap !== 'undefined') {
        modalProducto = new bootstrap.Modal(modalEl);
    }
    debugLog('DOM cargado correctamente');
});

// Toggle visibilidad de campos según tipo de precio
function togglePorcentajeUIT() {
    const tipoPrecio = document.getElementById('tipo_precio').value;
    const campoPorcentaje = document.getElementById('campo-porcentaje-uit');
    const campoPrecioUIT = document.getElementById('campo-precio-uit');
    const campoPrecioActual = document.getElementById('campo-precio-actual');
    const campoNuevoPrecio = document.getElementById('campo-nuevo-precio');
    const inputPorcentaje = document.getElementById('porcentaje_uit');
    
    if (tipoPrecio === 'UIT') {
        campoPorcentaje.style.display = 'block';
        campoPrecioUIT.style.display = 'block';
        campoPrecioActual.style.display = 'none';
        campoNuevoPrecio.style.display = 'none';
        inputPorcentaje.required = true;
        calcularPrecioUIT();
    } else if (tipoPrecio === 'Variable') {
        campoPorcentaje.style.display = 'none';
        campoPrecioUIT.style.display = 'none';
        campoPrecioActual.style.display = 'block';
        campoNuevoPrecio.style.display = 'block';
        inputPorcentaje.required = false;
        inputPorcentaje.value = '';
        document.getElementById('precio_calculado').value = '';
    } else {
        campoPorcentaje.style.display = 'none';
        campoPrecioUIT.style.display = 'none';
        campoPrecioActual.style.display = 'none';
        campoNuevoPrecio.style.display = 'none';
        inputPorcentaje.required = false;
        inputPorcentaje.value = '';
        document.getElementById('precio_calculado').value = '';
        document.getElementById('precio_actual').value = '';
        document.getElementById('nuevo_precio').value = '';
    }
}

function actualizarCentroPrincipal() {
    var checked = document.querySelectorAll('.centro-checkbox:checked');
    document.getElementById('id_centro').value = checked.length > 0 ? checked[0].value : '';
    filtrarClasesPorCentro();
}

function filtrarClasesPorCentro() {
    // Clases permitidas: union de todos los centros marcados
    var clasesPermitidas = [];
    var checked = document.querySelectorAll('.centro-checkbox:checked');
    
    if (checked.length > 0) {
        var unidas = {};
        checked.forEach(function(cb) {
            var vinc = VINCULACIONES[parseInt(cb.value)] || [];
            vinc.forEach(function(idClase) { unidas[idClase] = true; });
        });
        clasesPermitidas = Object.keys(unidas).map(Number);
    }
    
    var selectClase = document.getElementById('id_clase');
    var hayVinculaciones = clasesPermitidas.length > 0;
    
    var options = selectClase.options;
    for (var i = 1; i < options.length; i++) {
        var idClase = parseInt(options[i].value);
        if (hayVinculaciones) {
            options[i].style.display = clasesPermitidas.includes(idClase) ? '' : 'none';
        } else {
            options[i].style.display = '';
        }
    }
}

function obtenerCentrosSeleccionados() {
    var ids = [];
    document.querySelectorAll('.centro-checkbox:checked').forEach(function(cb) {
        ids.push(parseInt(cb.value));
    });
    return ids;
}

function marcarCentros(ids) {
    document.querySelectorAll('.centro-checkbox').forEach(function(cb) {
        cb.checked = ids.includes(parseInt(cb.value));
    });
    actualizarCentroPrincipal();
}

// Calcular precio basado en porcentaje de UIT
function calcularPrecioUIT() {
    const porcentaje = parseFloat(document.getElementById('porcentaje_uit').value) || 0;
    const uitActual = <?php echo $uitActual ? $uitActual : 0; ?>;
    
    if (uitActual > 0 && porcentaje > 0) {
        const precioCalculado = (uitActual * porcentaje).toFixed(2);
        document.getElementById('precio_calculado').value = precioCalculado;
    } else {
        document.getElementById('precio_calculado').value = '';
    }
}

function limpiarFormProducto() {
    document.getElementById('id_producto').value = '';
    document.getElementById('nombre').value = '';
    document.getElementById('nombre_cientifico').value = '';
    document.getElementById('unidad_medida').value = '';
    document.getElementById('maneja_stock').checked = true;
    document.getElementById('id_clase').value = '';
    document.getElementById('id_centro').value = '';
    marcarCentros([]);
    filtrarClasesPorCentro();
    document.getElementById('tipo_precio').value = '';
    document.getElementById('porcentaje_uit').value = '';
    document.getElementById('modal-producto-titulo').textContent = 'Nuevo Producto';
    // Limpiar imagen
    document.getElementById('imagen_referencia').value = '';
    document.getElementById('preview-imagen').src = '';
    document.getElementById('preview-imagen-container').style.display = 'none';
    const previewNombreClear = document.getElementById('preview-imagen-nombre');
    if (previewNombreClear) previewNombreClear.textContent = '';
    const badgeNuevaClear = document.getElementById('preview-badge-nueva');
    const badgeActualClear = document.getElementById('preview-badge-actual');
    if (badgeNuevaClear) badgeNuevaClear.style.display = 'none';
    if (badgeActualClear) badgeActualClear.style.display = 'none';
    document.getElementById('eliminar_imagen').checked = false;
    document.getElementById('check-eliminar-imagen-container').style.display = 'none';
    togglePorcentajeUIT();
}

function editarProducto(id) {
    fetch(`<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=obtener_producto&id=${id}`)
        .then(r => r.text())
        .then(text => {
            const trimmed = text.trim();
            const jsonStart = trimmed.indexOf('{');
            const jsonEnd = trimmed.lastIndexOf('}');
            if (jsonStart === -1 || jsonEnd === -1) {
                Swal.fire('Error', 'Respuesta inválida del servidor', 'error');
                return;
            }
            try {
                const data = JSON.parse(trimmed.substring(jsonStart, jsonEnd + 1));
                if (data && data.id_producto) {
                    document.getElementById('id_producto').value = data.id_producto;
                    document.getElementById('nombre').value = data.nombre;
                    document.getElementById('nombre_cientifico').value = data.nombre_cientifico || '';
                    document.getElementById('unidad_medida').value = data.unidad_medida;
                    document.getElementById('maneja_stock').checked = data.maneja_stock == 1;
                    document.getElementById('id_centro').value = data.id_centro;
                    // Marcar checkboxes de centros
                    if (data.centros && data.centros.length > 0) {
                        marcarCentros(data.centros.map(function(c) { return parseInt(c.id_centro); }));
                    } else {
                        marcarCentros([]);
                    }
                    filtrarClasesPorCentro();
                    document.getElementById('id_clase').value = data.id_clase;
                    document.getElementById('tipo_precio').value = data.tipo_precio || '';
                    document.getElementById('porcentaje_uit').value = data.porcentaje_uit || '';
                    document.getElementById('modal-producto-titulo').textContent = 'Editar Producto';
                    togglePorcentajeUIT();
                    
                    // Cargar imagen de referencia si existe
                    const previewImg = document.getElementById('preview-imagen');
                    const previewContainer = document.getElementById('preview-imagen-container');
                    const checkEliminarContainer = document.getElementById('check-eliminar-imagen-container');
                    const inputImagen = document.getElementById('imagen_referencia');
                    
                    const previewNombreEdit = document.getElementById('preview-imagen-nombre');
                    const badgeNuevaEdit = document.getElementById('preview-badge-nueva');
                    const badgeActualEdit = document.getElementById('preview-badge-actual');
                    if (data.imagen_nombre) {
                        previewImg.src = `<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=ver_imagen_producto&id=${data.id_producto}`;
                        previewImg.dataset.hasImage = 'true';
                        previewContainer.style.display = 'block';
                        if (previewNombreEdit) previewNombreEdit.textContent = data.imagen_nombre;
                        if (badgeNuevaEdit) badgeNuevaEdit.style.display = 'none';
                        if (badgeActualEdit) badgeActualEdit.style.display = 'inline-block';
                        checkEliminarContainer.style.display = 'block';
                        document.getElementById('eliminar_imagen').checked = false;
                    } else {
                        previewImg.src = '';
                        previewImg.dataset.hasImage = 'false';
                        previewContainer.style.display = 'none';
                        if (previewNombreEdit) previewNombreEdit.textContent = '';
                        if (badgeNuevaEdit) badgeNuevaEdit.style.display = 'none';
                        if (badgeActualEdit) badgeActualEdit.style.display = 'none';
                        checkEliminarContainer.style.display = 'none';
                        document.getElementById('eliminar_imagen').checked = false;
                    }
                    inputImagen.value = '';
                    
                    // Si es UIT, recalcular el precio
                    if (data.tipo_precio === 'UIT') {
                        calcularPrecioUIT();
                    }
                    // Cargar precio actual desde historial
                    if (data.tipo_precio === 'Variable') {
                        cargarPrecioActual(data.id_producto);
                    }
                    if (modalProducto) modalProducto.show();
                } else {
                    Swal.fire('Error', 'No se encontró el producto', 'error');
                }
            } catch (e) {
                Swal.fire('Error', 'Error al procesar respuesta', 'error');
            }
        })
        .catch(err => {
            Swal.fire('Error', 'Error al obtener datos', 'error');
        });
}

// Cargar precio actual desde historial_precio
function cargarPrecioActual(idProducto) {
    fetch(`<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=obtener_precio_actual&id_producto=${idProducto}`)
        .then(r => r.text())
        .then(text => {
            const trimmed = text.trim();
            const jsonStart = trimmed.indexOf('{');
            const jsonEnd = trimmed.lastIndexOf('}');
            if (jsonStart === -1 || jsonEnd === -1) {
                return;
            }
            try {
                const data = JSON.parse(trimmed.substring(jsonStart, jsonEnd + 1));
                if (data && data.precio_oficial) {
                    document.getElementById('precio_actual').value = data.precio_oficial;
                } else {
                    document.getElementById('precio_actual').value = 'Sin precio registrado';
                }
            } catch (e) {
                // Ignorar error
            }
        })
        .catch(err => {
            // Ignorar error
        });
}

function eliminarProducto(id) {
    Swal.fire({
        title: '¿Eliminar producto?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('id_producto', id);
            
            fetch('<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=eliminar_producto', {
                method: 'POST',
                body: formData
            })
            .then(r => r.text())
            .then(text => {
                const trimmed = text.trim();
                const jsonStart = trimmed.indexOf('{');
                const jsonEnd = trimmed.lastIndexOf('}');
                if (jsonStart === -1 || jsonEnd === -1) {
                    Swal.fire('Error', 'Respuesta inválida', 'error');
                    return;
                }
                try {
                    const data = JSON.parse(trimmed.substring(jsonStart, jsonEnd + 1));
                    if (data.success) {
                        Swal.fire('Eliminado', 'El producto fue eliminado', 'success')
                            .then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.message || 'No se pudo eliminar', 'error');
                    }
                } catch (e) {
                    Swal.fire('Error', 'Error al procesar respuesta', 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error', 'Error de conexión', 'error');
            });
        }
    });
}

async function handleSubmitProducto(e) {
    if (e && e.preventDefault) e.preventDefault();
    if (e && e.stopPropagation) e.stopPropagation();
    
    debugLog('=== INICIANDO GUARDADO ===');
    
    try {
        // Construir payload JSON desde los campos del formulario
        const payload = {
            id_producto: document.getElementById('id_producto').value || null,
            nombre: document.getElementById('nombre').value,
            nombre_cientifico: document.getElementById('nombre_cientifico').value,
            unidad_medida: document.getElementById('unidad_medida').value,
            maneja_stock: document.getElementById('maneja_stock').checked ? 1 : 0,
            id_clase: document.getElementById('id_clase').value,
            id_centro: document.getElementById('id_centro').value,
            centros: obtenerCentrosSeleccionados(),
            tipo_precio: document.getElementById('tipo_precio').value,
            porcentaje_uit: document.getElementById('porcentaje_uit').value
        };
        debugLog('Payload base construido');
        
        // Procesar imagen
        const inputImagen = document.getElementById('imagen_referencia');
        const checkEliminar = document.getElementById('eliminar_imagen');
        
        if (checkEliminar && checkEliminar.checked) {
            debugLog('Opcion: eliminar imagen actual');
            payload.eliminar_imagen = true;
        } else if (inputImagen && inputImagen.files && inputImagen.files[0]) {
            debugLog('Procesando imagen seleccionada...');
            try {
                payload.imagen_base64 = await fileToBase64(inputImagen.files[0]);
                payload.imagen_nombre = inputImagen.files[0].name;
                debugLog('Imagen convertida a base64 exitosamente');
            } catch (err) {
                debugLog('ERROR al leer imagen: ' + err.message);
                Swal.fire('Error', 'No se pudo leer la imagen seleccionada', 'error');
                return;
            }
        } else {
            debugLog('Sin imagen nueva ni eliminacion');
        }
        
        debugLog('Enviando peticion AJAX...');
        
        // Guardar producto
        const response = await fetch('<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=guardar_producto', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        const text = await response.text();
        debugLog('Respuesta recibida (' + text.length + ' chars)');
        
        const trimmed = text.trim();
        const jsonStart = trimmed.indexOf('{');
        const jsonEnd = trimmed.lastIndexOf('}');
        if (jsonStart === -1 || jsonEnd === -1) {
            debugLog('ERROR: Respuesta no contiene JSON valido');
            Swal.fire('Error', 'Respuesta invalida del servidor', 'error');
            return;
        }
        
        const data = JSON.parse(trimmed.substring(jsonStart, jsonEnd + 1));
        debugLog('Respuesta JSON: success=' + data.success);
        
        if (!data.success) {
            debugLog('ERROR del servidor: ' + (data.message || 'Sin mensaje'));
            Swal.fire('Error', data.message || 'No se pudo guardar', 'error');
            return;
        }
        
        // Si es Variable y se ingreso un nuevo precio, guardarlo en historial
        const tipoPrecio = document.getElementById('tipo_precio').value;
        const nuevoPrecio = document.getElementById('nuevo_precio').value;
        
        if (tipoPrecio === 'Variable' && nuevoPrecio && nuevoPrecio.trim() !== '') {
            debugLog('Guardando precio variable...');
            const precioData = {
                id_producto: data.id || document.getElementById('id_producto').value,
                precio_oficial: parseFloat(nuevoPrecio)
            };
            await fetch('<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=guardar_precio', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(precioData)
            });
        }
        
        debugLog('Guardado exitoso! Recargando pagina...');
        if (modalProducto) modalProducto.hide();
        Swal.fire({
            icon: 'success',
            title: 'Guardado',
            text: 'El producto se guardo correctamente',
            timer: 1500,
            showConfirmButton: false
        }).then(() => location.reload());
        
    } catch (err) {
        debugLog('ERROR inesperado: ' + err.message);
        console.error(err);
        Swal.fire('Error', 'Error de conexion o ejecucion: ' + err.message, 'error');
    }
}

// Función para aplicar filtros con animación
function aplicarFiltros() {
    const filtroCentro = document.getElementById('filtro-centro').value;
    const filtroClase = document.getElementById('filtro-clase').value;
    const busqueda = document.getElementById('buscar-global').value.toLowerCase().trim();
    const tbody = document.querySelector('tbody');
    
    // Agregar clase de loading al contenedor
    if (tbody) tbody.classList.add('filtro-loading');
    
    const filas = document.querySelectorAll('tbody tr[data-id]');
    
    // Primera fase: desvanecer todas las filas
    filas.forEach(fila => {
        fila.classList.add('filtrando');
        fila.classList.remove('mostrando');
    });
    
    // Esperar a que termine la animación de desvanecimiento
    setTimeout(() => {
        let visibles = 0;
        
        filas.forEach(fila => {
            const centroId = fila.getAttribute('data-centro');
            const claseId = fila.getAttribute('data-clase');
            const nombre = fila.getAttribute('data-nombre');
            
            const coincideCentro = !filtroCentro || centroId === filtroCentro;
            const coincideClase = !filtroClase || claseId === filtroClase;
            const coincideBusqueda = !busqueda || nombre.includes(busqueda);
            
            if (coincideCentro && coincideClase && coincideBusqueda) {
                fila.classList.add('filtro-pasa');
                fila.style.display = '';
                fila.classList.remove('filtrando');
                fila.classList.add('mostrando');
                visibles++;
            } else {
                fila.classList.remove('filtro-pasa');
                fila.style.display = 'none';
                fila.classList.remove('filtrando', 'mostrando');
            }
        });
        
        // Actualizar contador
        const contador = document.querySelector('.card-header .text-muted.me-3');
        if (contador) {
            contador.textContent = visibles + ' productos mostrados';
        }
        
        // Quitar loading
        if (tbody) tbody.classList.remove('filtro-loading');
        
        // Limpiar clase mostrando después de la animación
        setTimeout(() => {
            filas.forEach(fila => {
                if (fila.style.display !== 'none') {
                    fila.classList.remove('mostrando');
                }
            });
        }, 400);
        
        irAPagina(1);
        
    }, 300); // Esperar 300ms para la animación de desvanecimiento
}

// =================================================================================
// AGREGAR STOCK MASIVO (+10 a cada producto)
// =================================================================================
function agregarStockMasivo() {
    Swal.fire({
        title: 'Agregar Stock Masivo',
        text: 'Se creara un nuevo lote con 10 unidades para cada producto que maneja stock. ¿Continuar?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Si, agregar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#f59f00',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (!result.isConfirmed) return;
        
        Swal.fire({
            title: 'Procesando...',
            text: 'Creando lotes para todos los productos. Esto puede tomar unos segundos.',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
        
        fetch('<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=agregar_stock_masivo', {
            method: 'POST'
        })
        .then(r => r.text())
        .then(text => {
            const trimmed = text.trim();
            const jsonStart = trimmed.indexOf('{');
            const jsonEnd = trimmed.lastIndexOf('}');
            const result = JSON.parse(trimmed.substring(jsonStart, jsonEnd + 1));
            
            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Stock agregado',
                    text: 'Productos procesados: ' + result.productos_procesados + ' | Lotes creados: ' + result.lotes_creados + (result.omitidos > 0 ? ' | Omitidos (ya existian): ' + result.omitidos : ''),
                    timer: 3000,
                    showConfirmButton: true
                }).then(() => location.reload());
            } else {
                Swal.fire('Error', result.message || 'Error al procesar', 'error');
            }
        })
        .catch(err => {
            Swal.fire('Error', 'Error de conexion: ' + err.message, 'error');
        });
    });
}

// =================================================================================
// PAGINACION - 15 registros por pagina
// =================================================================================
const FILAS_POR_PAGINA = 15;
let paginaActual = 1;

function obtenerFilasVisibles() {
    return Array.from(document.querySelectorAll('tbody tr[data-id].filtro-pasa'));
}

function irAPagina(pagina) {
    paginaActual = pagina;
    aplicarPaginacion();
}

function aplicarPaginacion() {
    var filasVisibles = obtenerFilasVisibles();
    var totalFilas = filasVisibles.length;
    var totalPaginas = Math.ceil(totalFilas / FILAS_POR_PAGINA) || 1;
    
    if (paginaActual > totalPaginas) paginaActual = totalPaginas;
    if (paginaActual < 1) paginaActual = 1;
    
    var inicio = (paginaActual - 1) * FILAS_POR_PAGINA;
    var fin = Math.min(inicio + FILAS_POR_PAGINA, totalFilas);
    
    filasVisibles.forEach(function(fila, index) {
        if (index >= inicio && index < fin) {
            fila.style.display = '';
        } else {
            fila.style.display = 'none';
        }
    });
    
    renderizarPaginacion(totalFilas, totalPaginas);
}

function renderizarPaginacion(totalFilas, totalPaginas) {
    document.getElementById('paginacion-inicio').textContent = totalFilas === 0 ? 0 : (paginaActual - 1) * FILAS_POR_PAGINA + 1;
    document.getElementById('paginacion-fin').textContent = Math.min(paginaActual * FILAS_POR_PAGINA, totalFilas);
    document.getElementById('paginacion-total').textContent = totalFilas;
    
    var contenedor = document.getElementById('paginacion-controles');
    if (totalPaginas <= 1) {
        contenedor.innerHTML = '';
        return;
    }
    
    var html = '<button class="page-btn ' + (paginaActual === 1 ? 'disabled' : '') + '" onclick="irAPagina(' + (paginaActual - 1) + ')"><i class="ti ti-chevron-left"></i></button>';
    
    var paginas = [];
    if (totalPaginas <= 7) {
        for (var i = 1; i <= totalPaginas; i++) paginas.push(i);
    } else {
        paginas.push(1);
        if (paginaActual > 3) paginas.push('...');
        var desde = Math.max(2, paginaActual - 1);
        var hasta = Math.min(totalPaginas - 1, paginaActual + 1);
        for (var i = desde; i <= hasta; i++) paginas.push(i);
        if (paginaActual < totalPaginas - 2) paginas.push('...');
        paginas.push(totalPaginas);
    }
    
    paginas.forEach(function(p) {
        if (p === '...') {
            html += '<span class="page-ellipsis">...</span>';
        } else {
            html += '<button class="page-btn ' + (paginaActual === p ? 'active' : '') + '" onclick="irAPagina(' + p + ')">' + p + '</button>';
        }
    });
    
    html += '<button class="page-btn ' + (paginaActual === totalPaginas ? 'disabled' : '') + '" onclick="irAPagina(' + (paginaActual + 1) + ')"><i class="ti ti-chevron-right"></i></button>';
    
    contenedor.innerHTML = html;
}

// Inicializar event listeners para formularios
document.addEventListener('DOMContentLoaded', function() {
    const formNuevoLote = document.getElementById('form-nuevo-lote');
    if (formNuevoLote) {
        formNuevoLote.addEventListener('submit', guardarLote);
    }
    
    const formMerma = document.getElementById('form-merma');
    if (formMerma) {
        formMerma.addEventListener('submit', guardarMerma);
    }
    
    // Event listeners para la opción porcentual de merma
    const metodoExacto = document.getElementById('metodo-exacto');
    const metodoPorcentaje = document.getElementById('metodo-porcentaje');
    const containerExacta = document.getElementById('container-cantidad-exacta');
    const containerPorcentual = document.getElementById('container-cantidad-porcentual');
    const containerPreview = document.getElementById('container-preview-calculado');
    const mermaCantidadInput = document.getElementById('merma-cantidad');
    const mermaPorcentajeInput = document.getElementById('merma-porcentaje');
    const txtResultadoPreview = document.getElementById('txt-resultado-preview');
    
    function actualizarVisualizacionMerma() {
        if (metodoExacto && metodoExacto.checked) {
            if (containerExacta) containerExacta.classList.remove('d-none');
            if (containerPorcentual) containerPorcentual.classList.add('d-none');
            if (containerPreview) containerPreview.classList.add('d-none');
            if (mermaCantidadInput) {
                mermaCantidadInput.required = true;
                mermaCantidadInput.readOnly = false;
            }
            if (mermaPorcentajeInput) {
                mermaPorcentajeInput.required = false;
            }
        } else if (metodoPorcentaje && metodoPorcentaje.checked) {
            if (containerExacta) containerExacta.classList.add('d-none');
            if (containerPorcentual) containerPorcentual.classList.remove('d-none');
            if (containerPreview) containerPreview.classList.remove('d-none');
            if (mermaCantidadInput) {
                mermaCantidadInput.required = false;
                mermaCantidadInput.readOnly = true;
            }
            if (mermaPorcentajeInput) {
                mermaPorcentajeInput.required = true;
            }
            calcularMermaPorcentual();
        }
    }
    
    function calcularMermaPorcentual() {
        if (!mermaPorcentajeInput || !txtResultadoPreview || !mermaCantidadInput) return;
        
        let pct = parseFloat(mermaPorcentajeInput.value);
        if (isNaN(pct)) {
            pct = 0;
        } else if (pct > 100) {
            pct = 100;
            mermaPorcentajeInput.value = 100;
        } else if (pct < 0) {
            pct = 0;
            mermaPorcentajeInput.value = 0;
        }
        
        // Calcular redondeando hacia abajo (Math.floor) según requerimiento
        const cantidadCalculada = Math.floor(loteActualStock * (pct / 100));
        
        mermaCantidadInput.value = cantidadCalculada;
        txtResultadoPreview.innerText = `${pct}% de ${loteActualStock} = ${cantidadCalculada} unidades`;
    }
    
    if (metodoExacto) {
        metodoExacto.addEventListener('change', actualizarVisualizacionMerma);
    }
    if (metodoPorcentaje) {
        metodoPorcentaje.addEventListener('change', actualizarVisualizacionMerma);
    }
    if (mermaPorcentajeInput) {
        mermaPorcentajeInput.addEventListener('input', calcularMermaPorcentual);
    }
    
    // Event listeners para filtros
    const filtroCentro = document.getElementById('filtro-centro');
    const filtroClase = document.getElementById('filtro-clase');
    const buscarGlobal = document.getElementById('buscar-global');
    let timeoutBusqueda = null;
    
    function filtrarClasesSegunCentro() {
        var idCentro = parseInt(filtroCentro.value) || 0;
        var clasesPermitidas = VINCULACIONES[idCentro] || [];
        var hayVinculaciones = clasesPermitidas.length > 0;
        
        var options = filtroClase.options;
        for (var i = 1; i < options.length; i++) {
            var idClase = parseInt(options[i].value);
            if (hayVinculaciones) {
                options[i].style.display = clasesPermitidas.includes(idClase) ? '' : 'none';
            } else {
                options[i].style.display = '';
            }
        }
        if (filtroClase.value && hayVinculaciones && !clasesPermitidas.includes(parseInt(filtroClase.value))) {
            filtroClase.value = '';
        }
    }
    
    function filtrarCentrosSegunClase() {
        var idClase = parseInt(filtroClase.value) || 0;
        var centrosPermitidos = [];
        for (var c in VINCULACIONES) {
            if (VINCULACIONES[c].includes(idClase)) {
                centrosPermitidos.push(parseInt(c));
            }
        }
        var hayVinculaciones = centrosPermitidos.length > 0;
        
        var options = filtroCentro.options;
        for (var i = 1; i < options.length; i++) {
            var idCentro = parseInt(options[i].value);
            if (hayVinculaciones) {
                options[i].style.display = centrosPermitidos.includes(idCentro) ? '' : 'none';
            } else {
                options[i].style.display = '';
            }
        }
        if (filtroCentro.value && hayVinculaciones && !centrosPermitidos.includes(parseInt(filtroCentro.value))) {
            filtroCentro.value = '';
        }
    }
    
    if (filtroCentro) {
        filtroCentro.addEventListener('change', function() {
            filtrarClasesSegunCentro();
            aplicarFiltros();
        });
    }
    
    if (filtroClase) {
        filtroClase.addEventListener('change', function() {
            filtrarCentrosSegunClase();
            aplicarFiltros();
        });
    }
    
    if (buscarGlobal) {
        buscarGlobal.addEventListener('input', function() {
            // Debounce para búsqueda: esperar 300ms después de que el usuario deje de escribir
            clearTimeout(timeoutBusqueda);
            timeoutBusqueda = setTimeout(aplicarFiltros, 300);
        });
    }
    
    // Inicializar paginacion: marcar todas las filas visibles y paginar
    var filasIniciales = document.querySelectorAll('tbody tr[data-id]');
    filasIniciales.forEach(function(fila) {
        fila.classList.add('filtro-pasa');
    });
    aplicarPaginacion();
});

// =================================================================================
// FUNCIONES DE EXPORTACIÓN (EXCEL / PDF) PARA DETALLE DE PRODUCTO
// =================================================================================

function exportarDetalleProductoExcel() {
    if (!productoActualId || !productoActualNombre) {
        Swal.fire('Advertencia', 'No hay datos cargados para exportar', 'warning');
        return;
    }
    
    let csvContent = "\uFEFF"; // UTF-8 BOM para Excel
    csvContent += `REPORTE DE INVENTARIO - DETALLE DE PRODUCTO\n`;
    csvContent += `Producto;${productoActualNombre}\n`;
    csvContent += `ID Producto;${productoActualId}\n`;
    csvContent += `Fecha de Reporte;${new Date().toLocaleString('es-PE')}\n\n`;
    
    // Sección Lotes Activos
    csvContent += `LOTES ACTIVOS - STOCK POR LOTE\n`;
    csvContent += `Lote;Stock;Antigüedad (días);Estado PEPS\n`;
    
    if (modalLotesData.length === 0) {
        csvContent += `No hay lotes activos\n`;
    } else {
        modalLotesData.forEach(lote => {
            csvContent += `${lote.codigo_lote};${lote.stock_actual};${lote.antiguedad_dias};${lote.estado_texto}\n`;
        });
    }
    
    csvContent += `\n`;
    
    // Sección Kardex del Producto
    csvContent += `KARDEX DEL PRODUCTO - HISTORIAL DE MOVIMIENTOS\n`;
    csvContent += `Fecha;Tipo;Motivo/Documento;Lote Afectado;Entrada;Salida;Stock Acumulado\n`;
    
    if (modalKardexData.length === 0) {
        csvContent += `No hay movimientos registrados\n`;
    } else {
        modalKardexData.forEach(mov => {
            const esEntrada = mov.tipo_movimiento === 'Entrada' || mov.tipo_movimiento === 'INGRESO' || mov.tipo_movimiento === 'REINTEGRO';
            const entrada = esEntrada ? mov.cantidad : 0;
            const salida = !esEntrada ? mov.cantidad : 0;
            const fechaStr = new Date(mov.fecha).toLocaleDateString('es-PE');
            
            csvContent += `${fechaStr};${mov.tipo_movimiento};${mov.documento || '-'};${mov.codigo_lote || '-'};${entrada};${salida};${mov.saldo_final}\n`;
        });
    }
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.setAttribute("href", url);
    link.setAttribute("download", `reporte_stock_${productoActualNombre.toLowerCase().replace(/[^a-z0-9]+/g, '_')}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function exportarDetalleProductoPDF() {
    if (!productoActualId || !productoActualNombre) {
        Swal.fire('Advertencia', 'No hay datos cargados para exportar', 'warning');
        return;
    }
    
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
    
    const primaryColor = [18, 53, 91]; 
    const warningColor = [224, 86, 36]; 
    
    // Barra superior decorativa
    doc.setFillColor(...primaryColor);
    doc.rect(0, 0, 210, 8, 'F');
    doc.setFillColor(...warningColor);
    doc.rect(0, 8, 210, 1.5, 'F');
    
    // Membrete oficial
    doc.setTextColor(...primaryColor);
    doc.setFont("helvetica", "bold");
    doc.setFontSize(10);
    doc.text("GOBIERNO REGIONAL LA LIBERTAD", 14, 16);
    doc.setFontSize(13);
    doc.text("PROYECTO ESPECIAL CHAVIMOCHIC", 14, 21);
    doc.setFontSize(8);
    doc.setFont("helvetica", "normal");
    doc.setTextColor(110, 110, 110);
    doc.text("SUBGERENCIA DE DESARROLLO AGRÍCOLA", 14, 25);
    
    doc.setDrawColor(200, 200, 200);
    doc.line(14, 28, 196, 28);
    
    doc.setTextColor(33, 37, 41);
    doc.setFont("helvetica", "bold");
    doc.setFontSize(14);
    doc.text("REPORTE DE INVENTARIO Y STOCK", 14, 36);
    
    doc.setFontSize(9);
    doc.setFont("helvetica", "normal");
    doc.setTextColor(70, 70, 70);
    
    doc.setFont("helvetica", "bold");
    doc.text("Producto:", 14, 42);
    doc.setFont("helvetica", "normal");
    doc.text(productoActualNombre, 35, 42);
    
    doc.setFont("helvetica", "bold");
    doc.text("ID Producto:", 14, 47);
    doc.setFont("helvetica", "normal");
    doc.text(productoActualId.toString(), 35, 47);
    
    doc.setFont("helvetica", "bold");
    doc.text("Fecha Emisión:", 115, 42);
    doc.setFont("helvetica", "normal");
    doc.text(new Date().toLocaleString('es-PE'), 145, 42);
    
    const stockTotalText = document.getElementById('modal-stock-total')?.textContent || '0';
    doc.setFont("helvetica", "bold");
    doc.text("Stock Total:", 115, 47);
    doc.setFont("helvetica", "normal");
    doc.text(stockTotalText, 145, 47);
    
    // 1. LOTES ACTIVOS
    doc.setFillColor(...primaryColor);
    doc.setFont("helvetica", "bold");
    doc.setFontSize(10);
    doc.setTextColor(...primaryColor);
    doc.text("1. Lotes Activos (Stock por Lote)", 14, 56);
    
    const lotesHeaders = [["Código de Lote", "Stock Actual", "Antigüedad", "Estado PEPS"]];
    const lotesRows = modalLotesData.map(lote => [
        lote.codigo_lote,
        parseInt(lote.stock_actual).toLocaleString() + ' unid.',
        lote.antiguedad_dias + ' días',
        lote.estado_texto
    ]);
    
    doc.autoTable({
        startY: 59,
        head: lotesHeaders,
        body: lotesRows.length > 0 ? lotesRows : [["No hay lotes activos", "", "", ""]],
        theme: 'striped',
        headStyles: { fillColor: primaryColor, halign: 'left', fontStyle: 'bold' },
        styles: { fontSize: 8.5, cellPadding: 2 },
        columnStyles: {
            0: { cellWidth: 50 },
            1: { cellWidth: 35, halign: 'center' },
            2: { cellWidth: 35 },
            3: { cellWidth: 40 }
        },
        margin: { left: 14, right: 14 }
    });
    
    // 2. KARDEX HISTORIAL
    let nextY = doc.lastAutoTable.finalY + 10;
    
    if (nextY > 230) {
        doc.addPage();
        nextY = 20;
    }
    
    doc.setTextColor(...primaryColor);
    doc.setFont("helvetica", "bold");
    doc.setFontSize(10);
    doc.text("2. Kardex del Producto (Historial de Movimientos)", 14, nextY);
    
    const kardexHeaders = [["Fecha", "Tipo Movimiento", "Documento/Motivo", "Lote", "Entrada", "Salida", "Saldo Final"]];
    const kardexRows = modalKardexData.map(mov => {
        const esEntrada = mov.tipo_movimiento === 'Entrada' || mov.tipo_movimiento === 'INGRESO' || mov.tipo_movimiento === 'REINTEGRO';
        const entrada = esEntrada ? parseInt(mov.cantidad).toLocaleString() : '-';
        const salida = !esEntrada ? parseInt(mov.cantidad).toLocaleString() : '-';
        const fechaStr = new Date(mov.fecha).toLocaleDateString('es-PE');
        return [
            fechaStr,
            mov.tipo_movimiento,
            mov.documento || '-',
            mov.codigo_lote || '-',
            entrada,
            salida,
            parseInt(mov.saldo_final).toLocaleString()
        ];
    });
    
    doc.autoTable({
        startY: nextY + 3,
        head: kardexHeaders,
        body: kardexRows.length > 0 ? kardexRows : [["No hay movimientos registrados", "", "", "", "", "", ""]],
        theme: 'striped',
        headStyles: { fillColor: primaryColor, fontStyle: 'bold' },
        styles: { fontSize: 8, cellPadding: 1.8 },
        columnStyles: {
            0: { cellWidth: 22 },
            1: { cellWidth: 28 },
            2: { cellWidth: 45 },
            3: { cellWidth: 32 },
            4: { cellWidth: 18, halign: 'right' },
            5: { cellWidth: 18, halign: 'right' },
            6: { cellWidth: 20, halign: 'right' }
        },
        margin: { left: 14, right: 14 },
        didDrawPage: function(data) {
            doc.setFontSize(7.5);
            doc.setTextColor(150, 150, 150);
            doc.text("Proyecto Especial Chavimochic - Sistema de Gestión Agraria", 14, 287);
            
            const totalPages = doc.internal.getNumberOfPages();
            doc.text("Página " + data.pageNumber + " de " + totalPages, 185, 287);
        }
    });
    
    doc.save(`reporte_stock_${productoActualNombre.toLowerCase().replace(/[^a-z0-9]+/g, '_')}.pdf`);
}

</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>

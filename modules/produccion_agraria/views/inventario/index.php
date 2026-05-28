<style>
/* Animaciones para filtrado de tabla */
tbody tr {
    transition: all 0.3s ease-in-out;
    opacity: 1;
}

tbody tr.filtrando {
    opacity: 0;
    transform: translateY(-10px);
}

tbody tr.mostrando {
    animation: fadeInUp 0.4s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(15px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Spinner de carga */
.filtro-loading {
    position: relative;
    pointer-events: none;
}

.filtro-loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #3498db;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

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
                    
                    <!-- Toggle Stock Crítico -->
                    <div class="col-md-2">
                        <button class="btn btn-outline-danger w-100" id="btn-stock-critico" onclick="toggleStockCritico()">
                            <i class="ti ti-alert-triangle me-1"></i>
                            Stock Crítico
                        </button>
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
                                <th>Producto</th>
                                <th>Nombre Científico</th>
                                <th>Clase</th>
                                <th>Centro</th>
                                <th class="w-1 text-center">Unidad</th>
                                <th class="w-1 text-center">Maneja Stock</th>
                                <th class="w-1 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos as $producto): ?>
                            <tr data-id="<?php echo $producto['id_producto']; ?>" data-centro="<?php echo $producto['id_centro']; ?>" data-clase="<?php echo $producto['id_clase']; ?>" data-nombre="<?php echo strtolower(htmlspecialchars($producto['nombre'])); ?>">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="avatar avatar-sm bg-success-lt me-2">📦</span>
                                        <div>
                                            <div class="font-weight-medium"><?php echo htmlspecialchars($producto['nombre']); ?></div>
                                            <div class="text-muted small">Código: PRD<?php echo str_pad($producto['id_producto'], 3, '0', STR_PAD_LEFT); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="fst-italic text-muted"><?php echo htmlspecialchars($producto['nombre_cientifico'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($producto['nombre_clase'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($producto['nombre_centro'] ?? '-'); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($producto['unidad_medida']); ?></td>
                                <td class="text-center">
                                    <?php if ($producto['maneja_stock']): ?>
                                    <span class="badge bg-success">Sí</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">No</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($producto['maneja_stock']): ?>
                                    <button class="btn btn-sm btn-info me-1" onclick="mostrarLotes(<?php echo $producto['id_producto']; ?>, '<?php echo htmlspecialchars($producto['nombre'], ENT_QUOTES); ?>')" title="Ver Lotes y Stock">
                                        <i class="ti ti-box"></i>
                                    </button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-primary me-1" onclick="editarProducto(<?php echo $producto['id_producto']; ?>)" title="Editar">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="eliminarProducto(<?php echo $producto['id_producto']; ?>)" title="Eliminar">
                                        <i class="ti ti-trash"></i>
                                    </button>
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
            <div class="card-footer d-flex align-items-center">
                <p class="m-0 text-muted">Mostrando <?php echo count($productos); ?> productos</p>
            </div>
        </div>
        
    </div>
</div>

<!-- Modal: Formulario Producto -->
<div class="modal fade" id="modal-producto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
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
                            <label class="form-label required">Centro de Producción</label>
                            <select class="form-select" id="id_centro" name="id_centro" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($centros as $centro): ?>
                                <option value="<?php echo $centro['id_centro']; ?>"><?php echo htmlspecialchars($centro['nombre_centro']); ?></option>
                                <?php endforeach; ?>
                            </select>
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
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="ti ti-check me-1"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Estilos compartidos del módulo -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/variables.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/components.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/common.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/responsive.css">

<!-- 5. MODAL DETALLE DE PRODUCTO - LOTES + KARDEX -->
<div class="modal fade" id="modal-detalle-producto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <div class="d-flex align-items-center">
                    <i class="ti ti-box fs-3 me-3"></i>
                    <div>
                        <h5 class="modal-title mb-0" id="modal-producto-titulo">Detalle del Producto</h5>
                        <small class="opacity-75" id="modal-producto-subtitulo">Código: PRD001 | Clase: Hongos</small>
                    </div>
                </div>
                <div class="d-flex gap-2 align-items-center">
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
                                        <th>Lote (Fecha Creación)</th>
                                        <th class="text-center">Stock</th>
                                        <th>Antigüedad</th>
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
                                        <th>Motivo/Documento</th>
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
                        <label class="form-label required">Código de Lote</label>
                        <input type="text" class="form-control" id="lote-codigo" name="codigo_lote" required placeholder="Ej: LOT-2025-001">
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
                            <select class="form-select" id="merma-id-lote" name="id_lote" required>
                                <option value="">Seleccione lote...</option>
                            </select>
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

                        <div class="col-md-6">
                            <label class="form-label">Tipo de Merma</label>
                            <select class="form-select" id="merma-tipo" name="tipo_merma" required>
                                <option value="Vencimiento">Vencimiento / Caducidad</option>
                                <option value="Deterioro">Deterioro / Daño físico</option>
                                <option value="Plaga">Plaga / Enfermedad</option>
                                <option value="Proceso">Errores de proceso</option>
                                <option value="Otro">Otros</option>
                            </select>
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
// Mostrar detalle de producto (lotes + kardex) - Carga desde BD vía AJAX
function mostrarLotes(productoId, nombreProducto) {
    // Guardar producto actual para crear lotes
    productoActualId = productoId;
    productoActualNombre = nombreProducto;
    
    const modal = new bootstrap.Modal(document.getElementById('modal-detalle-producto'));
    
    // Actualizar título
    document.getElementById('modal-producto-titulo').textContent = nombreProducto;
    document.getElementById('modal-producto-subtitulo').textContent = `ID: ${productoId}`;
    
    // Mostrar loading
    document.getElementById('modal-tbody-lotes').innerHTML = '<tr><td colspan="5" class="text-center py-4"><div class="spinner-border text-success"></div><p class="mt-2 text-muted">Cargando lotes...</p></td></tr>';
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
                tbodyLotes.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">No hay lotes activos</td></tr>';
            } else {
                tbodyLotes.innerHTML = lotes.map((lote) => {
                    const estadoClass = lote.estado_texto === 'Agotado' ? 'bg-secondary' : 
                                       lote.estado_texto === 'Stock Crítico' ? 'bg-danger' :
                                       lote.estado_texto === 'Por Vencer' ? 'bg-warning' : 'bg-success';
                    const rowClass = lote.estado_texto === 'Stock Crítico' ? 'table-danger' : '';
                    return `
                        <tr class="${rowClass}">
                            <td><code>${lote.codigo_lote}</code></td>
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
            document.getElementById('modal-tbody-lotes').innerHTML = '<tr><td colspan="5" class="text-center py-4 text-danger">Error al cargar lotes</td></tr>';
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
    const selectLote = document.getElementById('merma-id-lote');
    selectLote.innerHTML = `<option value="${idLote}">${codigoLote} (Stock: ${stockActual})</option>`;
    selectLote.value = idLote;
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
        tipo_merma: formData.get('tipo_merma'),
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
    document.getElementById('lote-codigo').value = '';
    document.getElementById('lote-stock').value = '0';
    document.getElementById('lote-centro').value = '';
    
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

document.addEventListener('DOMContentLoaded', function() {
    formProducto = document.getElementById('form-producto');
    const modalEl = document.getElementById('modal-producto');
    if (modalEl && typeof bootstrap !== 'undefined') {
        modalProducto = new bootstrap.Modal(modalEl);
    }
    if (formProducto) {
        formProducto.addEventListener('submit', handleSubmitProducto);
    }
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
    document.getElementById('tipo_precio').value = '';
    document.getElementById('porcentaje_uit').value = '';
    document.getElementById('modal-producto-titulo').textContent = 'Nuevo Producto';
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
                    document.getElementById('id_clase').value = data.id_clase;
                    document.getElementById('id_centro').value = data.id_centro;
                    document.getElementById('tipo_precio').value = data.tipo_precio || '';
                    document.getElementById('porcentaje_uit').value = data.porcentaje_uit || '';
                    document.getElementById('modal-producto-titulo').textContent = 'Editar Producto';
                    togglePorcentajeUIT();
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

function handleSubmitProducto(e) {
    e.preventDefault();
    const formData = new FormData(formProducto);
    
    // Asegurar que maneja_stock se envíe correctamente
    if (!document.getElementById('maneja_stock').checked) {
        formData.delete('maneja_stock');
    }
    
    // Guardar producto primero
    fetch('<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=guardar_producto', {
        method: 'POST',
        body: formData
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
        try {
            const data = JSON.parse(trimmed.substring(jsonStart, jsonEnd + 1));
            if (data.success) {
                // Si es Variable y se ingresó un nuevo precio, guardarlo en historial
                const tipoPrecio = document.getElementById('tipo_precio').value;
                const nuevoPrecio = document.getElementById('nuevo_precio').value;
                
                if (tipoPrecio === 'Variable' && nuevoPrecio && nuevoPrecio.trim() !== '') {
                    const precioData = {
                        id_producto: data.id || document.getElementById('id_producto').value,
                        precio_oficial: parseFloat(nuevoPrecio)
                    };
                    
                    return fetch('<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=guardar_precio', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(precioData)
                    }).then(r => r.json());
                }
                
                return Promise.resolve({ success: true });
            } else {
                Swal.fire('Error', data.message || 'No se pudo guardar', 'error');
                return Promise.reject(data.message);
            }
        } catch (e) {
            Swal.fire('Error', 'Error al procesar respuesta', 'error');
            return Promise.reject(e);
        }
    })
    .then(result => {
        if (result.success) {
            if (modalProducto) modalProducto.hide();
            Swal.fire({
                icon: 'success',
                title: 'Guardado',
                text: 'El producto se guardó correctamente',
                timer: 1500,
                showConfirmButton: false
            }).then(() => location.reload());
        } else {
            Swal.fire('Error', 'No se pudo guardar el precio', 'error');
        }
    })
    .catch(err => {
        if (err !== 'No se pudo guardar') {
            Swal.fire('Error', 'Error de conexión', 'error');
        }
    });
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
                fila.style.display = '';
                fila.classList.remove('filtrando');
                fila.classList.add('mostrando');
                visibles++;
            } else {
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
        
    }, 300); // Esperar 300ms para la animación de desvanecimiento
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
    
    if (filtroCentro) {
        filtroCentro.addEventListener('change', aplicarFiltros);
    }
    
    if (filtroClase) {
        filtroClase.addEventListener('change', aplicarFiltros);
    }
    
    if (buscarGlobal) {
        buscarGlobal.addEventListener('input', function() {
            // Debounce para búsqueda: esperar 300ms después de que el usuario deje de escribir
            clearTimeout(timeoutBusqueda);
            timeoutBusqueda = setTimeout(aplicarFiltros, 300);
        });
    }
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

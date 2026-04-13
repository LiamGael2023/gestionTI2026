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
                            <option value="CP01">Centro 1 - Huaral</option>
                            <option value="CP02">Centro 2 - Huacho</option>
                            <option value="CP03">Centro 3 - Barranca</option>
                            <option value="CP04">Centro 4 - Oyón</option>
                            <option value="CP05">Centro 5 - Sayán</option>
                            <option value="CP06">Centro 6 - Huaura</option>
                            <option value="CP07">Centro 7 - Pativilca</option>
                            <option value="CP08">Centro 8 - Supe</option>
                        </select>
                    </div>
                    
                    <!-- Filtro de Clase de Producto -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Clase de Producto</label>
                        <select class="form-select" id="filtro-clase">
                            <option value="">Todas las clases</option>
                            <option value="hongos">Hongos</option>
                            <option value="plantones">Plantones</option>
                            <option value="frutas">Frutas</option>
                            <option value="insectos">Insectos</option>
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
        
        <!-- 2. WIDGETS DE RESUMEN -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card bg-gradient-primary text-white">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="ti ti-coins fs-1"></i>
                        </div>
                        <div class="ms-3">
                            <h5 class="mb-1">S/. 245,680.00</h5>
                            <small>Valorización Total de Stock</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-gradient-warning text-white">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="ti ti-alert-circle fs-1"></i>
                        </div>
                        <div class="ms-3">
                            <h5 class="mb-1">12</h5>
                            <small>Alertas de Merma (última semana)</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-gradient-danger text-white">
                    <div class="card-body d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="ti ti-clock-exclamation fs-1"></i>
                        </div>
                        <div class="ms-3">
                            <h5 class="mb-1">8</h5>
                            <small>Lotes por Vencer (PEPS)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 3. ACCIONES GLOBALES -->
        <div class="d-flex gap-2 mb-4">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal-ingresar-produccion">
                <i class="ti ti-package-import me-2"></i>Ingresar Producción
            </button>
        </div>
        
        <!-- 4. TABLA DE PRODUCTOS (NIVEL 1) -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Productos en Inventario</h4>
                <span class="text-muted">6 productos activos</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table" id="tabla-productos">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="w-1 text-center">Stock Total</th>
                                <th class="w-1 text-center">Lotes Activos</th>
                                <th class="w-1">Estado General</th>
                                <th class="w-1 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Producto 1: Hongos Shiitake -->
                            <tr class="cursor-pointer producto-row" data-producto="PRD001" onclick="mostrarLotes('PRD001', 'Hongos Shiitake')">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="avatar avatar-sm bg-success-lt me-2">🍄</span>
                                        <div>
                                            <div class="font-weight-medium">Hongos Shiitake</div>
                                            <div class="text-muted small">Hongos | Código: PRD001</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success fs-6">703</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-outline-primary">3 lotes</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-success">1 Nuevo</span>
                                        <span class="badge bg-warning">1 Rotación</span>
                                        <span class="badge bg-danger">1 Crítico</span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-primary btn-sm" onclick="event.stopPropagation(); mostrarLotes('PRD001', 'Hongos Shiitake')">
                                        <i class="ti ti-box me-1"></i>Ver Lotes
                                    </button>
                                </td>
                            </tr>
                            
                            <!-- Producto 2: Plantones de Fresa -->
                            <tr class="cursor-pointer producto-row" data-producto="PRD002" onclick="mostrarLotes('PRD002', 'Plantones de Fresa')">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="avatar avatar-sm bg-success-lt me-2">🌱</span>
                                        <div>
                                            <div class="font-weight-medium">Plantones de Fresa</div>
                                            <div class="text-muted small">Plantones | Código: PRD002</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success fs-6">1,200</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-outline-primary">1 lote</span>
                                </td>
                                <td>
                                    <span class="badge bg-success">Nuevo</span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-primary btn-sm" onclick="event.stopPropagation(); mostrarLotes('PRD002', 'Plantones de Fresa')">
                                        <i class="ti ti-box me-1"></i>Ver Lotes
                                    </button>
                                </td>
                            </tr>
                            
                            <!-- Producto 3: Fresa Premium -->
                            <tr class="cursor-pointer producto-row" data-producto="PRD003" onclick="mostrarLotes('PRD003', 'Fresa Premium')">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="avatar avatar-sm bg-success-lt me-2">🍓</span>
                                        <div>
                                            <div class="font-weight-medium">Fresa Premium</div>
                                            <div class="text-muted small">Frutas | Código: PRD003</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success fs-6">85</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-outline-primary">1 lote</span>
                                </td>
                                <td>
                                    <span class="badge bg-warning">Rotación</span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-primary btn-sm" onclick="event.stopPropagation(); mostrarLotes('PRD003', 'Fresa Premium')">
                                        <i class="ti ti-box me-1"></i>Ver Lotes
                                    </button>
                                </td>
                            </tr>
                            
                            <!-- Producto 4: Chinche depredadora -->
                            <tr class="cursor-pointer producto-row" data-producto="PRD004" onclick="mostrarLotes('PRD004', 'Chinche depredadora')">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="avatar avatar-sm bg-success-lt me-2">🐞</span>
                                        <div>
                                            <div class="font-weight-medium">Chinche depredadora</div>
                                            <div class="text-muted small">Insectos | Código: PRD004</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success fs-6">2,500</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-outline-primary">1 lote</span>
                                </td>
                                <td>
                                    <span class="badge bg-success">Nuevo</span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-primary btn-sm" onclick="event.stopPropagation(); mostrarLotes('PRD004', 'Chinche depredadora')">
                                        <i class="ti ti-box me-1"></i>Ver Lotes
                                    </button>
                                </td>
                            </tr>
                            
                            <!-- Producto 5: Plantones de Palta -->
                            <tr class="cursor-pointer producto-row" data-producto="PRD005" onclick="mostrarLotes('PRD005', 'Plantones de Palta')">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="avatar avatar-sm bg-warning-lt me-2">🌱</span>
                                        <div>
                                            <div class="font-weight-medium">Plantones de Palta</div>
                                            <div class="text-muted small">Plantones | Código: PRD005</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-warning fs-6">8</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-outline-primary">1 lote</span>
                                </td>
                                <td>
                                    <span class="badge bg-danger">Stock Crítico</span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-primary btn-sm" onclick="event.stopPropagation(); mostrarLotes('PRD005', 'Plantones de Palta')">
                                        <i class="ti ti-box me-1"></i>Ver Lotes
                                    </button>
                                </td>
                            </tr>
                            
                            <!-- Producto 6: Hongos Oyster -->
                            <tr class="cursor-pointer producto-row" data-producto="PRD006" onclick="mostrarLotes('PRD006', 'Hongos Oyster')">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="avatar avatar-sm bg-success-lt me-2">🍄</span>
                                        <div>
                                            <div class="font-weight-medium">Hongos Oyster</div>
                                            <div class="text-muted small">Hongos | Código: PRD006</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success fs-6">750</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-outline-primary">1 lote</span>
                                </td>
                                <td>
                                    <span class="badge bg-success">Nuevo</span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-primary btn-sm" onclick="event.stopPropagation(); mostrarLotes('PRD006', 'Hongos Oyster')">
                                        <i class="ti ti-box me-1"></i>Ver Lotes
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center">
                <p class="m-0 text-muted">Mostrando 6 productos</p>
            </div>
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
                    <button class="btn btn-light btn-sm" title="Exportar PDF">
                        <i class="ti ti-file-type-pdf me-1"></i>PDF
                    </button>
                    <button class="btn btn-light btn-sm" title="Exportar Excel">
                        <i class="ti ti-file-type-xls me-1"></i>Excel
                    </button>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body">
                <!-- Resumen del Producto -->
                <div class="row g-3 mb-4">
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
                    <div class="card-header bg-primary-lt">
                        <h5 class="card-title mb-0">
                            <i class="ti ti-packages me-2"></i>Lotes Activos - Stock por Lote
                        </h5>
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

<!-- MODAL: Ingresar Producción -->
<div class="modal fade" id="modal-ingresar-produccion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="ti ti-package-import me-2"></i>Ingresar Producción</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Centro de Producción</label>
                        <select class="form-select">
                            <option value="">Seleccione centro</option>
                            <option>Centro 1 - Huaral</option>
                            <option>Centro 2 - Huacho</option>
                            <option>Centro 3 - Barranca</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Fecha de Producción</label>
                        <input type="date" class="form-control" value="2025-04-13">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Clase de Producto</label>
                        <select class="form-select">
                            <option value="">Seleccione clase</option>
                            <option>Hongos</option>
                            <option>Plantones</option>
                            <option>Frutas</option>
                            <option>Insectos</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Producto</label>
                        <select class="form-select">
                            <option value="">Seleccione producto</option>
                            <option>Hongos Shiitake</option>
                            <option>Hongos Oyster</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Cantidad Producida</label>
                        <input type="number" class="form-control" placeholder="0">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Unidad de Medida</label>
                        <select class="form-select">
                            <option>Unidades</option>
                            <option>Kilogramos</option>
                            <option>Cajas</option>
                            <option>Bolsas</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Observaciones</label>
                        <textarea class="form-control" rows="2" placeholder="Notas adicionales..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success">Registrar Ingreso</button>
            </div>
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
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="ti ti-info-circle me-2"></i>La merma descontará automáticamente del stock del lote seleccionado.
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
                            <option>Hongos Shiitake - Lote: 2025-04-08 (Stock: 180)</option>
                            <option>Plantones de Fresa - Lote: 2025-04-10 (Stock: 1200)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tipo de Merma</label>
                        <select class="form-select">
                            <option>Vencimiento / Caducidad</option>
                            <option>Deterioro / Daño físico</option>
                            <option>Plaga / Enfermedad</option>
                            <option>Errores de proceso</option>
                            <option>Otros</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Cantidad Afectada</label>
                        <input type="number" class="form-control" placeholder="0">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Motivo Detallado</label>
                        <textarea class="form-control" rows="3" placeholder="Describa las causas de la merma..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning">Reportar Merma</button>
            </div>
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
// Datos de ejemplo: Lotes por producto
const lotesPorProducto = {
    'PRD001': [
        { lote: '2025-04-13', stock: 500, antiguedad: 2, estado: 'Nuevo', estadoClass: 'bg-success' },
        { lote: '2025-04-08', stock: 180, antiguedad: 7, estado: 'Rotación', estadoClass: 'bg-warning' },
        { lote: '2025-03-25', stock: 23, antiguedad: 21, estado: 'Crítico', estadoClass: 'bg-danger' }
    ],
    'PRD002': [
        { lote: '2025-04-10', stock: 1200, antiguedad: 5, estado: 'Nuevo', estadoClass: 'bg-success' }
    ],
    'PRD003': [
        { lote: '2025-04-05', stock: 85, antiguedad: 10, estado: 'Rotación', estadoClass: 'bg-warning' }
    ],
    'PRD004': [
        { lote: '2025-04-12', stock: 2500, antiguedad: 3, estado: 'Nuevo', estadoClass: 'bg-success' }
    ],
    'PRD005': [
        { lote: '2025-03-15', stock: 8, antiguedad: 31, estado: 'Stock Crítico', estadoClass: 'bg-danger' }
    ],
    'PRD006': [
        { lote: '2025-04-11', stock: 750, antiguedad: 4, estado: 'Nuevo', estadoClass: 'bg-success' }
    ]
};

// Datos de ejemplo: Kardex por producto (agregado, no por lote)
const kardexPorProducto = {
    'PRD001': [
        { fecha: '08/04/2025', tipo: 'Entrada', tipoClass: 'bg-primary', documento: 'Ingreso de Producción - CP01', lote: '2025-04-13', entrada: 500, salida: 0, stock: 500 },
        { fecha: '10/04/2025', tipo: 'Salida', tipoClass: 'bg-warning', documento: 'Venta #PRO-2025-0125', lote: '2025-04-08', entrada: 0, salida: 150, stock: 350 },
        { fecha: '12/04/2025', tipo: 'Salida', tipoClass: 'bg-warning', documento: 'Venta #PRO-2025-0130', lote: '2025-03-25', entrada: 0, salida: 100, stock: 250 },
        { fecha: '13/04/2025', tipo: 'Salida', tipoClass: 'bg-warning', documento: 'Venta #PRO-2025-0135', lote: '2025-03-25', entrada: 0, salida: 47, stock: 203 },
        { fecha: '13/04/2025', tipo: 'Entrada', tipoClass: 'bg-primary', documento: 'Ingreso de Producción - CP02', lote: '2025-04-08', entrada: 180, salida: 0, stock: 383 },
        { fecha: '14/04/2025', tipo: 'Salida', tipoClass: 'bg-warning', documento: 'Venta #PRO-2025-0140', lote: '2025-04-08', entrada: 0, salida: 200, stock: 183 },
        { fecha: '15/04/2025', tipo: 'Entrada', tipoClass: 'bg-primary', documento: 'Ingreso de Producción - CP01', lote: '2025-04-13', entrada: 500, salida: 0, stock: 683 },
        { fecha: '15/04/2025', tipo: 'Salida', tipoClass: 'bg-warning', documento: 'Venta #PRO-2025-0142', lote: '2025-04-13', entrada: 0, salida: 100, stock: 583 },
        { fecha: '16/04/2025', tipo: 'Merma', tipoClass: 'bg-danger', documento: 'Merma por deterioro', lote: '2025-03-25', entrada: 0, salida: 23, stock: 560 },
        { fecha: '16/04/2025', tipo: 'Salida', tipoClass: 'bg-warning', documento: 'Venta #PRO-2025-0145', lote: '2025-04-13', entrada: 0, salida: 120, stock: 440 },
        { fecha: '17/04/2025', tipo: 'Salida', tipoClass: 'bg-warning', documento: 'Venta #PRO-2025-0148', lote: '2025-04-13', entrada: 0, salida: 80, stock: 360 },
        { fecha: '18/04/2025', tipo: 'Entrada', tipoClass: 'bg-primary', documento: 'Ingreso de Producción - CP03', lote: '2025-04-18', entrada: 300, salida: 0, stock: 660 }
    ],
    'PRD002': [
        { fecha: '10/04/2025', tipo: 'Entrada', tipoClass: 'bg-primary', documento: 'Ingreso de Producción - CP01', lote: '2025-04-10', entrada: 1200, salida: 0, stock: 1200 },
        { fecha: '12/04/2025', tipo: 'Salida', tipoClass: 'bg-warning', documento: 'Venta #PRO-2025-0132', lote: '2025-04-10', entrada: 0, salida: 200, stock: 1000 },
        { fecha: '15/04/2025', tipo: 'Salida', tipoClass: 'bg-warning', documento: 'Venta #PRO-2025-0140', lote: '2025-04-10', entrada: 0, salida: 150, stock: 850 },
        { fecha: '17/04/2025', tipo: 'Salida', tipoClass: 'bg-warning', documento: 'Venta #PRO-2025-0146', lote: '2025-04-10', entrada: 0, salida: 300, stock: 550 }
    ],
    'PRD003': [
        { fecha: '05/04/2025', tipo: 'Entrada', tipoClass: 'bg-primary', documento: 'Ingreso de Producción - CP02', lote: '2025-04-05', entrada: 200, salida: 0, stock: 200 },
        { fecha: '08/04/2025', tipo: 'Salida', tipoClass: 'bg-warning', documento: 'Venta #PRO-2025-0120', lote: '2025-04-05', entrada: 0, salida: 50, stock: 150 },
        { fecha: '10/04/2025', tipo: 'Salida', tipoClass: 'bg-warning', documento: 'Venta #PRO-2025-0126', lote: '2025-04-05', entrada: 0, salida: 40, stock: 110 },
        { fecha: '12/04/2025', tipo: 'Merma', tipoClass: 'bg-danger', documento: 'Merma por maduración excesiva', lote: '2025-04-05', entrada: 0, salida: 15, stock: 95 },
        { fecha: '14/04/2025', tipo: 'Salida', tipoClass: 'bg-warning', documento: 'Venta #PRO-2025-0138', lote: '2025-04-05', entrada: 0, salida: 60, stock: 35 },
        { fecha: '16/04/2025', tipo: 'Salida', tipoClass: 'bg-warning', documento: 'Venta #PRO-2025-0144', lote: '2025-04-05', entrada: 0, salida: 35, stock: 0 }
    ],
    'PRD004': [
        { fecha: '12/04/2025', tipo: 'Entrada', tipoClass: 'bg-primary', documento: 'Ingreso de Producción - CP01', lote: '2025-04-12', entrada: 2500, salida: 0, stock: 2500 },
        { fecha: '15/04/2025', tipo: 'Salida', tipoClass: 'bg-warning', documento: 'Venta #PRO-2025-0141', lote: '2025-04-12', entrada: 0, salida: 500, stock: 2000 },
        { fecha: '18/04/2025', tipo: 'Salida', tipoClass: 'bg-warning', documento: 'Venta #PRO-2025-0150', lote: '2025-04-12', entrada: 0, salida: 800, stock: 1200 }
    ],
    'PRD005': [
        { fecha: '15/03/2025', tipo: 'Entrada', tipoClass: 'bg-primary', documento: 'Ingreso de Producción - CP03', lote: '2025-03-15', entrada: 500, salida: 0, stock: 500 },
        { fecha: '20/03/2025', tipo: 'Salida', tipoClass: 'bg-warning', documento: 'Venta #PRO-2025-0085', lote: '2025-03-15', entrada: 0, salida: 150, stock: 350 },
        { fecha: '25/03/2025', tipo: 'Salida', tipoClass: 'bg-warning', documento: 'Venta #PRO-2025-0095', lote: '2025-03-15', entrada: 0, salida: 200, stock: 150 },
        { fecha: '01/04/2025', tipo: 'Salida', tipoClass: 'bg-warning', documento: 'Venta #PRO-2025-0105', lote: '2025-03-15', entrada: 0, salida: 100, stock: 50 },
        { fecha: '05/04/2025', tipo: 'Merma', tipoClass: 'bg-danger', documento: 'Merma por defecto genético', lote: '2025-03-15', entrada: 0, salida: 30, stock: 20 },
        { fecha: '08/04/2025', tipo: 'Salida', tipoClass: 'bg-warning', documento: 'Venta #PRO-2025-0122', lote: '2025-03-15', entrada: 0, salida: 12, stock: 8 }
    ],
    'PRD006': [
        { fecha: '11/04/2025', tipo: 'Entrada', tipoClass: 'bg-primary', documento: 'Ingreso de Producción - CP02', lote: '2025-04-11', entrada: 750, salida: 0, stock: 750 },
        { fecha: '14/04/2025', tipo: 'Salida', tipoClass: 'bg-warning', documento: 'Venta #PRO-2025-0136', lote: '2025-04-11', entrada: 0, salida: 200, stock: 550 },
        { fecha: '16/04/2025', tipo: 'Salida', tipoClass: 'bg-warning', documento: 'Venta #PRO-2025-0143', lote: '2025-04-11', entrada: 0, salida: 150, stock: 400 },
        { fecha: '18/04/2025', tipo: 'Salida', tipoClass: 'bg-warning', documento: 'Venta #PRO-2025-0149', lote: '2025-04-11', entrada: 0, salida: 100, stock: 300 },
        { fecha: '20/04/2025', tipo: 'Entrada', tipoClass: 'bg-primary', documento: 'Ingreso de Producción - CP01', lote: '2025-04-20', entrada: 450, salida: 0, stock: 750 }
    ]
};

// Mostrar detalle de producto (lotes + kardex)
function mostrarLotes(productoId, nombreProducto) {
    const modal = new bootstrap.Modal(document.getElementById('modal-detalle-producto'));
    
    // Actualizar título y subtítulo
    document.getElementById('modal-producto-titulo').textContent = nombreProducto;
    document.getElementById('modal-producto-subtitulo').textContent = `Código: ${productoId} | Clase: ${getClaseProducto(productoId)}`;
    
    // Obtener lotes del producto
    const lotes = lotesPorProducto[productoId] || [];
    const kardex = kardexPorProducto[productoId] || [];
    
    // Calcular resumen
    const stockTotal = lotes.reduce((sum, lote) => sum + lote.stock, 0);
    const totalEntradas = kardex.filter(m => m.tipo === 'Entrada').reduce((sum, m) => sum + m.entrada, 0);
    const totalSalidas = kardex.filter(m => m.tipo === 'Salida').reduce((sum, m) => sum + m.salida, 0);
    
    // Actualizar resumen en el modal
    document.getElementById('modal-stock-total').textContent = stockTotal.toLocaleString() + ' unid.';
    document.getElementById('modal-lotes-activos').textContent = lotes.length;
    document.getElementById('modal-total-entradas').textContent = totalEntradas.toLocaleString() + ' unid.';
    document.getElementById('modal-total-salidas').textContent = totalSalidas.toLocaleString() + ' unid.';
    
    // Generar HTML de lotes
    const tbodyLotes = document.getElementById('modal-tbody-lotes');
    tbodyLotes.innerHTML = lotes.map((lote) => `
        <tr class="${lote.estado === 'Crítico' || lote.estado === 'Stock Crítico' ? 'table-danger' : ''}">
            <td>
                <code>${lote.lote}</code>
            </td>
            <td class="text-center">
                <span class="badge ${lote.stock < 50 ? 'bg-warning' : 'bg-success'} fs-6">${lote.stock.toLocaleString()}</span>
            </td>
            <td>
                <span class="${lote.antiguedad > 20 ? 'text-danger fw-bold' : lote.antiguedad > 7 ? 'text-warning' : 'text-success'}">
                    <i class="ti ti-clock me-1"></i>${lote.antiguedad} días
                </span>
            </td>
            <td class="text-center">
                <span class="badge ${lote.estadoClass}">${lote.estado}</span>
            </td>
            <td class="text-center">
                <button class="btn btn-white btn-icon" data-bs-toggle="modal" data-bs-target="#modal-merma" title="Registrar Merma">
                    <i class="ti ti-minus text-danger"></i>
                </button>
            </td>
        </tr>
    `).join('');
    
    // Generar HTML de kardex
    const tbodyKardex = document.getElementById('modal-tbody-kardex');
    tbodyKardex.innerHTML = kardex.map((mov) => `
        <tr>
            <td>${mov.fecha}</td>
            <td><span class="badge ${mov.tipoClass}">${mov.tipo}</span></td>
            <td>${mov.documento}</td>
            <td><code>${mov.lote}</code></td>
            <td class="text-end ${mov.entrada > 0 ? 'text-success fw-bold' : ''}">${mov.entrada > 0 ? '+' + mov.entrada.toLocaleString() : '-'}</td>
            <td class="text-end ${mov.salida > 0 ? 'text-warning fw-bold' : ''}">${mov.salida > 0 ? '-' + mov.salida.toLocaleString() : '-'}</td>
            <td class="text-end fw-bold">${mov.stock.toLocaleString()}</td>
        </tr>
    `).join('');
    
    // Mostrar modal
    modal.show();
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
</script>

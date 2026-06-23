<div class="breadcrumb">
    <div class="container-xl">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?php echo BASE_URL; ?>/produccion_agraria">Prod. Agraria</a>
            </li>
            <li class="breadcrumb-item active">Tablas Maestras</li>
        </ol>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        
        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-body py-2 px-3">
                <div class="text-uppercase text-muted fw-bold fs-4">
                    <i class="ti ti-leaf me-2 text-primary"></i>
                    Sistema de Seguimiento y control de Productos Agricolas
</div>
</div>

<!-- Modal: Vincular Centros a Clase -->
<div class="modal fade" id="modal-vinculacion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-link me-2"></i>Vincular Centros a: <span id="vinculacion-clase-nombre" class="text-primary"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Selecciona los centros de produccion donde esta clase puede estar disponible.</p>
                <div id="vinculacion-checkboxes" class="row g-2">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="guardarVinculaciones()">
                    <i class="ti ti-check me-1"></i>Guardar Vinculaciones
                </button>
            </div>
        </div>
    </div>
</div>
        </div>
        
        <!-- Pestañas de navegación -->
        <div class="card mb-3">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $tabla == 'clase' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/produccion_agraria?action=tablas&tabla=clase">
                            <i class="ti ti-category me-1"></i>Clases
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $tabla == 'centro' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/produccion_agraria?action=tablas&tabla=centro">
                            <i class="ti ti-building me-1"></i>Centros
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $tabla == 'uit' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/produccion_agraria?action=tablas&tabla=uit">
                            <i class="ti ti-coin me-1"></i>UIT
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $tabla == 'cliente' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/produccion_agraria?action=tablas&tabla=cliente">
                            <i class="ti ti-users me-1"></i>Clientes
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                
                <!-- SECCIÓN: CLASES -->
                <?php if ($tabla == 'clase'): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="card-title">Clases de Producto</h3>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal-clase" onclick="limpiarFormClase()">
                        <i class="ti ti-plus me-1"></i>Nueva Clase
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th class="w-1">ID</th>
                                <th>Nombre de Clase</th>
                                <th class="w-1 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-clases">
                            <?php foreach ($clases as $clase): ?>
                            <tr data-id="<?php echo $clase['id_clase']; ?>">
                                <td><?php echo $clase['id_clase']; ?></td>
                                <td><?php echo htmlspecialchars($clase['nombre_clase']); ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-info me-1" onclick="abrirVinculacion(<?php echo $clase['id_clase']; ?>, '<?php echo htmlspecialchars($clase['nombre_clase'], ENT_QUOTES); ?>')" title="Vincular Centros">
                                        <i class="ti ti-link"></i>
                                    </button>
                                    <button class="btn btn-sm btn-primary me-1" onclick="editarClase(<?php echo $clase['id_clase']; ?>)" title="Editar">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="eliminarClase(<?php echo $clase['id_clase']; ?>)" title="Eliminar">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($clases)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted">No hay clases registradas</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
                <!-- SECCIÓN: CENTROS DE PRODUCCIÓN -->
                <?php if ($tabla == 'centro'): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="card-title">Centros de Producción</h3>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal-centro" onclick="limpiarFormCentro()">
                        <i class="ti ti-plus me-1"></i>Nuevo Centro
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th class="w-1">ID</th>
                                <th>Nombre</th>
                                <th>Ubicación</th>
                                <th>Encargado</th>
                                <th class="w-1 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-centros">
                            <?php foreach ($centros as $centro): ?>
                            <tr data-id="<?php echo $centro['id_centro']; ?>">
                                <td><?php echo $centro['id_centro']; ?></td>
                                <td><?php echo htmlspecialchars($centro['nombre_centro']); ?></td>
                                <td><?php echo htmlspecialchars($centro['ubicacion'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($centro['encargado']); ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-primary me-1" onclick="editarCentro(<?php echo $centro['id_centro']; ?>)" title="Editar">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="eliminarCentro(<?php echo $centro['id_centro']; ?>)" title="Eliminar">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($centros)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No hay centros registrados</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
                <!-- SECCIÓN: UIT -->
                <?php if ($tabla == 'uit'): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="card-title">Valores UIT</h3>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal-uit" onclick="limpiarFormUit()">
                        <i class="ti ti-plus me-1"></i>Nueva UIT
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th class="w-1">Año</th>
                                <th>Valor (S/)</th>
                                <th class="w-1 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-uits">
                            <?php foreach ($uits as $uit): ?>
                            <tr data-anio="<?php echo $uit['anio']; ?>">
                                <td><?php echo $uit['anio']; ?></td>
                                <td><?php echo number_format($uit['valor'], 2); ?></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-primary me-1" onclick="editarUit(<?php echo $uit['anio']; ?>)" title="Editar">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="eliminarUit(<?php echo $uit['anio']; ?>)" title="Eliminar">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($uits)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted">No hay UIT registradas</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
                <!-- SECCIÓN: CLIENTES -->
                <?php if ($tabla == 'cliente'): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="card-title">Clientes</h3>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal-cliente" onclick="limpiarFormCliente()">
                        <i class="ti ti-plus me-1"></i>Nuevo Cliente
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th class="w-1">ID</th>
                                <th>DNI/RUC</th>
                                <th>Nombre / Razón Social</th>
                                <th>Tipo</th>
                                <th class="w-1 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-clientes">
                            <?php foreach ($clientes as $cliente): ?>
                            <tr data-id="<?php echo $cliente['id_cliente']; ?>">
                                <td><?php echo $cliente['id_cliente']; ?></td>
                                <td><?php echo htmlspecialchars($cliente['dni_ruc']); ?></td>
                                <td><?php echo htmlspecialchars($cliente['nombre_rs']); ?></td>
                                <td>
                                    <?php 
                                    $tipo = $cliente['tipo_cliente'];
                                    $tipoTexto = ($tipo == 1 || $tipo === true) ? 'Planilla' : 'Externo';
                                    echo htmlspecialchars($tipoTexto); 
                                    ?>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-primary me-1" onclick="editarCliente(<?php echo $cliente['id_cliente']; ?>)" title="Editar">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="eliminarCliente(<?php echo $cliente['id_cliente']; ?>)" title="Eliminar">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($clientes)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No hay clientes registrados</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
        
    </div>
</div>

<!-- Modal: Formulario Clase -->
<div class="modal fade" id="modal-clase" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-clase-titulo">Nueva Clase</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-clase">
                <div class="modal-body">
                    <input type="hidden" id="id_clase" name="id_clase">
                    <div class="mb-3">
                        <label class="form-label required">Nombre de Clase</label>
                        <input type="text" class="form-control" id="nombre_clase" name="nombre_clase" required placeholder="Ej: Hongos, Plantones, Frutas">
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

<!-- Modal: Formulario Centro de Producción -->
<div class="modal fade" id="modal-centro" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-centro-titulo">Nuevo Centro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-centro">
                <div class="modal-body">
                    <input type="hidden" id="id_centro" name="id_centro">
                    <div class="mb-3">
                        <label class="form-label required">Nombre del Centro</label>
                        <input type="text" class="form-control" id="nombre_centro" name="nombre_centro" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ubicación</label>
                        <input type="text" class="form-control" id="ubicacion" name="ubicacion" placeholder="Ej: Lima, Arequipa, etc.">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Encargado</label>
                        <input type="text" class="form-control" id="encargado" name="encargado" required>
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

<!-- Modal: Formulario UIT -->
<div class="modal fade" id="modal-uit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-uit-titulo">Nueva UIT</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-uit">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Año</label>
                        <input type="number" class="form-control" id="anio" name="anio" required min="2000" max="2100" placeholder="Ej: 2024">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Valor (S/)</label>
                        <input type="number" step="0.01" class="form-control" id="valor" name="valor" required placeholder="Ej: 4950.00">
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

<!-- Modal: Formulario Cliente -->
<div class="modal fade" id="modal-cliente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-cliente-titulo">Nuevo Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-cliente">
                <div class="modal-body">
                    <input type="hidden" id="id_cliente" name="id_cliente">
                    <div class="mb-3">
                        <label class="form-label required">DNI / RUC</label>
                        <input type="text" class="form-control" id="dni_ruc" name="dni_ruc" required placeholder="Ej: 12345678 o 20123456789">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Nombre / Razón Social</label>
                        <input type="text" class="form-control" id="nombre_rs" name="nombre_rs" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Tipo de Cliente</label>
                        <select class="form-select" id="tipo_cliente" name="tipo_cliente" required>
                            <option value="">Seleccionar...</option>
                            <option value="1">Planilla</option>
                            <option value="0">Externo</option>
                        </select>
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

<!-- Toast para notificaciones -->
<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="toast-notificacion" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <i class="ti ti-bell me-2"></i>
            <strong class="me-auto">Notificación</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body" id="toast-mensaje"></div>
    </div>
</div>

<!-- Estilos compartidos del módulo -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/variables.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/components.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/common.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/responsive.css">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Datos de centros para vinculacion (cargados desde PHP)
const CENTROS_DISPONIBLES = <?php echo json_encode($centros); ?>;
// ========================================
// GESTIÓN DE CLASES
// ========================================

let modalClase = null;
let toast = null;
let formClase = null;

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    formClase = document.getElementById('form-clase');
    
    // Inicializar modal y toast
    const modalEl = document.getElementById('modal-clase');
    const toastEl = document.getElementById('toast-notificacion');
    
    if (modalEl && typeof bootstrap !== 'undefined') {
        modalClase = new bootstrap.Modal(modalEl);
    }
    
    if (toastEl && typeof bootstrap !== 'undefined') {
        toast = new bootstrap.Toast(toastEl);
    }
    
    // Bind form submit
    if (formClase) {
        formClase.addEventListener('submit', handleSubmitClase);
    }
});

function limpiarFormClase() {
    document.getElementById('id_clase').value = '';
    document.getElementById('nombre_clase').value = '';
    document.getElementById('modal-clase-titulo').textContent = 'Nueva Clase';
}

function mostrarToast(mensaje, tipo = 'success') {
    const toastBody = document.getElementById('toast-mensaje');
    const toastEl = document.getElementById('toast-notificacion');
    if (toastBody) toastBody.textContent = mensaje;
    if (toastEl) {
        toastEl.classList.remove('text-bg-success', 'text-bg-danger');
        toastEl.classList.add('text-bg-' + tipo);
    }
    if (toast) toast.show();
}

function editarClase(id) {
    fetch(`<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=obtener_clase&id=${id}`)
        .then(r => r.text())
        .then(text => {
            const trimmed = text.trim();
            const jsonStart = trimmed.indexOf('{');
            const jsonEnd = trimmed.lastIndexOf('}');
            
            if (jsonStart === -1 || jsonEnd === -1) {
                mostrarToast('Respuesta inválida del servidor', 'danger');
                return;
            }
            
            try {
                const data = JSON.parse(trimmed.substring(jsonStart, jsonEnd + 1));
                if (data && data.id_clase) {
                    document.getElementById('id_clase').value = data.id_clase;
                    document.getElementById('nombre_clase').value = data.nombre_clase;
                    document.getElementById('modal-clase-titulo').textContent = 'Editar Clase';
                    if (modalClase) {
                        modalClase.show();
                    } else {
                        const modalEl = document.getElementById('modal-clase');
                        if (modalEl && typeof bootstrap !== 'undefined') {
                            const bsModal = new bootstrap.Modal(modalEl);
                            bsModal.show();
                        }
                    }
                } else {
                    mostrarToast('No se encontró la clase', 'danger');
                }
            } catch (e) {
                mostrarToast('Error al procesar respuesta', 'danger');
            }
        })
        .catch(err => {
            mostrarToast('Error al obtener datos', 'danger');
        });
}

function eliminarClase(id) {
    Swal.fire({
        title: '¿Eliminar clase?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('id_clase', id);
            
            fetch('<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=eliminar_clase', {
                method: 'POST',
                body: formData
            })
            .then(r => r.text())
            .then(text => {
                const trimmed = text.trim();
                const jsonStart = trimmed.indexOf('{');
                const jsonEnd = trimmed.lastIndexOf('}');
                
                if (jsonStart === -1 || jsonEnd === -1) {
                    console.error('No se encontró JSON:', trimmed.substring(0, 200));
                    Swal.fire('Error', 'Respuesta inválida', 'error');
                    return;
                }
                
                try {
                    const data = JSON.parse(trimmed.substring(jsonStart, jsonEnd + 1));
                    if (data.success) {
                        Swal.fire('Eliminado', 'La clase fue eliminada', 'success')
                            .then(() => location.reload());
                    } else {
                        Swal.fire('Error', 'No se pudo eliminar', 'error');
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

function handleSubmitClase(e) {
    e.preventDefault();
    
    const formData = new FormData(formClase);
    
    fetch('<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=guardar_clase', {
        method: 'POST',
        body: formData
    })
    .then(r => r.text())
    .then(text => {
        // Limpiar espacios y buscar el JSON
        const trimmed = text.trim();
        const jsonStart = trimmed.indexOf('{');
        const jsonEnd = trimmed.lastIndexOf('}');
        
        if (jsonStart === -1 || jsonEnd === -1) {
            console.error('No se encontró JSON en la respuesta:', trimmed.substring(0, 200));
            Swal.fire('Error', 'Respuesta inválida del servidor', 'error');
            return;
        }
        
        const jsonStr = trimmed.substring(jsonStart, jsonEnd + 1);
        
        try {
            const data = JSON.parse(jsonStr);
            if (data.success) {
                if (modalClase) modalClase.hide();
                Swal.fire({
                    icon: 'success',
                    title: 'Guardado',
                    text: 'La clase se guardó correctamente',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => location.reload());
            } else {
                Swal.fire('Error', data.message || 'No se pudo guardar', 'error');
            }
        } catch (e) {
            console.error('JSON parse error:', e, 'Texto:', jsonStr.substring(0, 100));
            Swal.fire('Error', 'Error al procesar respuesta', 'error');
        }
    })
    .catch(err => {
        console.error('Fetch error:', err);
        Swal.fire('Error', 'Error de conexión', 'error');
    });
}

// ========================================
// VINCULACION CLASE <-> CENTROS DE PRODUCCION
// ========================================

let vinculacionClaseId = null;
let vinculacionClaseNombre = '';
let modalVinculacion = null;

function abrirVinculacion(idClase, nombreClase) {
    vinculacionClaseId = idClase;
    vinculacionClaseNombre = nombreClase;
    
    document.getElementById('vinculacion-clase-nombre').textContent = nombreClase;
    document.getElementById('vinculacion-checkboxes').innerHTML = '<div class="col-12 text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div> Cargando...</div>';
    
    if (!modalVinculacion) {
        modalVinculacion = new bootstrap.Modal(document.getElementById('modal-vinculacion'));
    }
    modalVinculacion.show();
    
    fetch('<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=obtener_vinculacion&id_clase=' + idClase)
        .then(r => r.text())
        .then(function(text) {
            var vincTrimmed = text.trim();
            var vincStart = vincTrimmed.indexOf('[');
            var vincEnd = vincTrimmed.lastIndexOf(']');
            var vinculados = [];
            if (vincStart !== -1 && vincEnd !== -1) {
                vinculados = JSON.parse(vincTrimmed.substring(vincStart, vincEnd + 1));
            }
            
            var htmlCheck = '';
            CENTROS_DISPONIBLES.forEach(function(c) {
                var checked = vinculados.includes(parseInt(c.id_centro)) ? 'checked' : '';
                htmlCheck += '<div class="col-md-6"><div class="form-check"><input class="form-check-input" type="checkbox" value="' + c.id_centro + '" id="vc-' + c.id_centro + '" ' + checked + '><label class="form-check-label" for="vc-' + c.id_centro + '">' + c.nombre_centro + '</label></div></div>';
            });
            
            if (CENTROS_DISPONIBLES.length === 0) {
                htmlCheck = '<div class="col-12 text-center text-muted py-3">No hay centros registrados</div>';
            }
            
            document.getElementById('vinculacion-checkboxes').innerHTML = htmlCheck;
        })
        .catch(function(err) {
            document.getElementById('vinculacion-checkboxes').innerHTML = '<div class="col-12 text-center text-danger py-3">Error al cargar vinculaciones</div>';
        });
}

function guardarVinculaciones() {
    var checks = document.querySelectorAll('#vinculacion-checkboxes input[type="checkbox"]:checked');
    var centrosIds = [];
    checks.forEach(function(c) { centrosIds.push(parseInt(c.value)); });
    
    var data = { id_clase: vinculacionClaseId, centros: centrosIds };
    
    fetch('<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=guardar_vinculaciones', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.text())
    .then(function(text) {
        var trimmed = text.trim();
        var jsonStart = trimmed.indexOf('{');
        var jsonEnd = trimmed.lastIndexOf('}');
        var result = JSON.parse(trimmed.substring(jsonStart, jsonEnd + 1));
        if (result.success) {
            Swal.fire({ icon: 'success', title: 'Vinculaciones guardadas', text: vinculacionClaseNombre + ' ahora tiene ' + centrosIds.length + ' centros vinculados', timer: 2000, showConfirmButton: false });
            modalVinculacion.hide();
        } else {
            Swal.fire('Error', result.message || 'Error al guardar', 'error');
        }
    })
    .catch(function(err) {
        Swal.fire('Error', 'Error de conexion', 'error');
    });
}

// ========================================
// GESTIÓN DE CENTROS DE PRODUCCIÓN
// ========================================

let modalCentro = null;
let formCentro = null;

document.addEventListener('DOMContentLoaded', function() {
    formCentro = document.getElementById('form-centro');
    const modalEl = document.getElementById('modal-centro');
    if (modalEl && typeof bootstrap !== 'undefined') {
        modalCentro = new bootstrap.Modal(modalEl);
    }
    if (formCentro) {
        formCentro.addEventListener('submit', handleSubmitCentro);
    }
});

function limpiarFormCentro() {
    document.getElementById('id_centro').value = '';
    document.getElementById('nombre_centro').value = '';
    document.getElementById('ubicacion').value = '';
    document.getElementById('encargado').value = '';
    document.getElementById('modal-centro-titulo').textContent = 'Nuevo Centro';
}

function editarCentro(id) {
    fetch(`<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=obtener_centro&id=${id}`)
        .then(r => r.text())
        .then(text => {
            const trimmed = text.trim();
            const jsonStart = trimmed.indexOf('{');
            const jsonEnd = trimmed.lastIndexOf('}');
            if (jsonStart === -1 || jsonEnd === -1) {
                mostrarToast('Respuesta inválida del servidor', 'danger');
                return;
            }
            try {
                const data = JSON.parse(trimmed.substring(jsonStart, jsonEnd + 1));
                if (data && data.id_centro) {
                    document.getElementById('id_centro').value = data.id_centro;
                    document.getElementById('nombre_centro').value = data.nombre_centro;
                    document.getElementById('ubicacion').value = data.ubicacion || '';
                    document.getElementById('encargado').value = data.encargado;
                    document.getElementById('modal-centro-titulo').textContent = 'Editar Centro';
                    if (modalCentro) modalCentro.show();
                } else {
                    mostrarToast('No se encontró el centro', 'danger');
                }
            } catch (e) {
                mostrarToast('Error al procesar respuesta', 'danger');
            }
        })
        .catch(err => {
            mostrarToast('Error al obtener datos', 'danger');
        });
}

function eliminarCentro(id) {
    Swal.fire({
        title: '¿Eliminar centro?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('id_centro', id);
            fetch('<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=eliminar_centro', {
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
                        Swal.fire('Eliminado', 'El centro fue eliminado', 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', 'No se pudo eliminar', 'error');
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

function handleSubmitCentro(e) {
    e.preventDefault();
    const formData = new FormData(formCentro);
    fetch('<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=guardar_centro', {
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
                if (modalCentro) modalCentro.hide();
                Swal.fire({
                    icon: 'success',
                    title: 'Guardado',
                    text: 'El centro se guardó correctamente',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => location.reload());
            } else {
                Swal.fire('Error', data.message || 'No se pudo guardar', 'error');
            }
        } catch (e) {
            Swal.fire('Error', 'Error al procesar respuesta', 'error');
        }
    })
    .catch(err => {
        Swal.fire('Error', 'Error de conexión', 'error');
    });
}

// ========================================
// GESTIÓN DE UIT
// ========================================

let modalUit = null;
let formUit = null;

document.addEventListener('DOMContentLoaded', function() {
    formUit = document.getElementById('form-uit');
    const modalEl = document.getElementById('modal-uit');
    if (modalEl && typeof bootstrap !== 'undefined') {
        modalUit = new bootstrap.Modal(modalEl);
    }
    if (formUit) {
        formUit.addEventListener('submit', handleSubmitUit);
    }
});

function limpiarFormUit() {
    document.getElementById('anio').value = '';
    document.getElementById('valor').value = '';
    document.getElementById('modal-uit-titulo').textContent = 'Nueva UIT';
}

function editarUit(anio) {
    fetch(`<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=obtener_uit&anio=${anio}`)
        .then(r => r.text())
        .then(text => {
            const trimmed = text.trim();
            const jsonStart = trimmed.indexOf('{');
            const jsonEnd = trimmed.lastIndexOf('}');
            if (jsonStart === -1 || jsonEnd === -1) {
                mostrarToast('Respuesta inválida del servidor', 'danger');
                return;
            }
            try {
                const data = JSON.parse(trimmed.substring(jsonStart, jsonEnd + 1));
                if (data && data.anio) {
                    document.getElementById('anio').value = data.anio;
                    document.getElementById('valor').value = data.valor;
                    document.getElementById('modal-uit-titulo').textContent = 'Editar UIT';
                    if (modalUit) modalUit.show();
                } else {
                    mostrarToast('No se encontró la UIT', 'danger');
                }
            } catch (e) {
                mostrarToast('Error al procesar respuesta', 'danger');
            }
        })
        .catch(err => {
            mostrarToast('Error al obtener datos', 'danger');
        });
}

function eliminarUit(anio) {
    Swal.fire({
        title: '¿Eliminar UIT?',
        text: `Eliminar UIT del año ${anio}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('anio', anio);
            fetch('<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=eliminar_uit', {
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
                        Swal.fire('Eliminado', 'La UIT fue eliminada', 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', 'No se pudo eliminar', 'error');
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

function handleSubmitUit(e) {
    e.preventDefault();
    const formData = new FormData(formUit);
    fetch('<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=guardar_uit', {
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
                if (modalUit) modalUit.hide();
                Swal.fire({
                    icon: 'success',
                    title: 'Guardado',
                    text: 'La UIT se guardó correctamente',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => location.reload());
            } else {
                Swal.fire('Error', data.message || 'No se pudo guardar', 'error');
            }
        } catch (e) {
            Swal.fire('Error', 'Error al procesar respuesta', 'error');
        }
    })
    .catch(err => {
        Swal.fire('Error', 'Error de conexión', 'error');
    });
}

// ========================================
// GESTIÓN DE CLIENTES
// ========================================

let modalCliente = null;
let formCliente = null;

document.addEventListener('DOMContentLoaded', function() {
    formCliente = document.getElementById('form-cliente');
    const modalEl = document.getElementById('modal-cliente');
    if (modalEl && typeof bootstrap !== 'undefined') {
        modalCliente = new bootstrap.Modal(modalEl);
    }
    if (formCliente) {
        formCliente.addEventListener('submit', handleSubmitCliente);
    }
});

function limpiarFormCliente() {
    document.getElementById('id_cliente').value = '';
    document.getElementById('dni_ruc').value = '';
    document.getElementById('nombre_rs').value = '';
    document.getElementById('tipo_cliente').value = '';
    document.getElementById('modal-cliente-titulo').textContent = 'Nuevo Cliente';
}

function editarCliente(id) {
    fetch(`<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=obtener_cliente&id=${id}`)
        .then(r => r.text())
        .then(text => {
            const trimmed = text.trim();
            const jsonStart = trimmed.indexOf('{');
            const jsonEnd = trimmed.lastIndexOf('}');
            if (jsonStart === -1 || jsonEnd === -1) {
                mostrarToast('Respuesta inválida del servidor', 'danger');
                return;
            }
            try {
                const data = JSON.parse(trimmed.substring(jsonStart, jsonEnd + 1));
                if (data && data.id_cliente) {
                    document.getElementById('id_cliente').value = data.id_cliente;
                    document.getElementById('dni_ruc').value = data.dni_ruc;
                    document.getElementById('nombre_rs').value = data.nombre_rs;
                    document.getElementById('tipo_cliente').value = (data.tipo_cliente == 1 || data.tipo_cliente === true) ? '1' : '0';
                    document.getElementById('modal-cliente-titulo').textContent = 'Editar Cliente';
                    if (modalCliente) modalCliente.show();
                } else {
                    mostrarToast('No se encontró el cliente', 'danger');
                }
            } catch (e) {
                mostrarToast('Error al procesar respuesta', 'danger');
            }
        })
        .catch(err => {
            mostrarToast('Error al obtener datos', 'danger');
        });
}

function eliminarCliente(id) {
    Swal.fire({
        title: '¿Eliminar cliente?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc3545'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('id_cliente', id);
            fetch('<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=eliminar_cliente', {
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
                        Swal.fire('Eliminado', 'El cliente fue eliminado', 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', 'No se pudo eliminar', 'error');
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

function handleSubmitCliente(e) {
    e.preventDefault();
    const formData = new FormData(formCliente);
    fetch('<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=guardar_cliente', {
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
                if (modalCliente) modalCliente.hide();
                Swal.fire({
                    icon: 'success',
                    title: 'Guardado',
                    text: 'El cliente se guardó correctamente',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => location.reload());
            } else {
                Swal.fire('Error', data.message || 'No se pudo guardar', 'error');
            }
        } catch (e) {
            Swal.fire('Error', 'Error al procesar respuesta', 'error');
        }
    })
    .catch(err => {
        Swal.fire('Error', 'Error de conexión', 'error');
    });
}
</script>

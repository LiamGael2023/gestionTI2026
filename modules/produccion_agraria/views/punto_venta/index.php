<div class="breadcrumb">
    <div class="container-xl">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="<?php echo BASE_URL; ?>/produccion_agraria">Prod. Agraria</a>
            </li>
            <li class="breadcrumb-item active">Punto de Venta</li>
        </ol>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <!-- Título -->
        <h3 class="mb-4 fw-bold">Nueva Venta</h3>
        
        <!-- Formulario de Encabezado -->
        <div class="row g-2 mb-2">
            <div class="col-md-5">
                <div class="position-relative">
                    <input type="text" class="form-control" id="busqueda-cliente" 
                           placeholder="Buscar cliente..." autocomplete="off">
                    <div class="dropdown-menu w-100" id="dropdown-clientes" style="display: none; max-height: 300px; overflow-y: auto;">
                        <!-- Resultados de búsqueda se mostrarán aquí -->
                    </div>
                    <input type="hidden" id="id_cliente">
                    <input type="hidden" id="cliente-seleccionado-nombre">
                </div>
            </div>
            <div class="col-md-3">
                <input type="date" class="form-control" id="fecha" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-4">
                <select class="form-select" id="metodo_pago">
                    <option value="">Método de pago</option>
                    <option value="efectivo">Efectivo</option>
                    <option value="tarjeta">Tarjeta</option>
                    <option value="transferencia">Transferencia</option>
                </select>
            </div>
        </div>
        
        <!-- Área de Items (Tabla) -->
        <div class="card mb-2">
            <div class="card-body p-0">
                <div class="table-container" style="height: 250px; max-height: 250px; overflow-y: auto; position: relative; display: block;">
                    <table class="table table-vcenter card-table mb-0" id="tabla-items">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="w-1">Cantidad</th>
                                <th class="w-1">Precio Unit.</th>
                                <th class="w-1">Subtotal</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody id="tbody-items">
                            <!-- Items se agregarán dinámicamente -->
                        </tbody>
                    </table>
                    
                    <!-- Mensaje cuando está vacío -->
                    <div class="text-center text-muted" id="mensaje-vacio">
                        <i class="ti ti-shopping-cart-off fs-1 mb-2"></i>
                        <p>No hay productos agregados</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Selector de Productos -->
        <div class="card mb-2">
            <div class="card-body py-2">
                <div class="position-relative">
                    <input type="text" class="form-control" id="busqueda-producto" 
                           placeholder="Buscar producto..." autocomplete="off">
                    <div class="dropdown-menu w-100" id="dropdown-productos" style="display: none;">
                        <!-- Resultados de búsqueda se mostrarán aquí -->
                    </div>
                </div>
                <small class="text-muted">Clic para agregar (cantidad: 1)</small>
            </div>
        </div>
        
        <!-- Footer de Venta -->
        <div class="row align-items-center g-2">
            <div class="col-md-5">
                <div class="bg-success text-white p-2 rounded d-flex justify-content-between align-items-center">
                    <span class="fs-5 fw-bold">Total:</span>
                    <span class="fs-4 fw-bold" id="total-venta">S/. 0.00</span>
                </div>
            </div>
            <div class="col-md-7">
                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-outline-secondary" id="btn-limpiar">
                        <i class="ti ti-trash me-1"></i>Limpiar
                    </button>
                    <button class="btn btn-success" id="btn-procesar">
                        <i class="ti ti-check me-1"></i>Procesar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Estilos compartidos del módulo -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/variables.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/components.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/common.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/responsive.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* Estilos específicos para Punto de Venta */
    
    /* Tabla con scroll y header fijo */
    .table-container {
        height: 250px !important;
        max-height: 250px !important;
        overflow-y: auto;
        position: relative;
    }
    
    #tabla-items {
        margin-bottom: 0;
        width: 100%;
    }
    
    #tabla-items thead {
        position: sticky;
        top: 0;
        z-index: 10;
        background: #f8fafc;
    }
    
    #tabla-items thead th {
        border-top: none;
        background: #f8fafc;
        box-shadow: 0 1px 0 #dee2e6;
    }
    
    #tabla-items tbody tr:last-child td {
        border-bottom: none;
    }
    
    /* Animaciones para items de la tabla */
    #tabla-items tbody tr {
        transition: all 0.3s ease-in-out;
        opacity: 1;
    }
    
    #tabla-items tbody tr.agregando {
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
    
    /* Animaciones para dropdown de búsqueda */
    .dropdown-menu {
        animation: fadeInDown 0.2s ease-out;
    }
    
    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Mensaje vacío centrado */
    #mensaje-vacio {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
        width: 100%;
    }
    
    /* Dropdown de búsqueda de clientes */
    #dropdown-clientes {
        z-index: 1050;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    #dropdown-clientes .dropdown-item {
        padding: 10px 15px;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }
    
    #dropdown-clientes .dropdown-item:hover {
        background-color: #f8f9fa;
        transform: translateX(3px);
    }
    
    /* Estilos mejorados para items de productos */
    #dropdown-productos {
        width: 100% !important;
        min-width: 100% !important;
    }
    
    #dropdown-productos .dropdown-item {
        padding: 12px 16px;
        border-bottom: 1px solid #f0f0f0;
        transition: all 0.2s ease;
        width: 100%;
        display: block;
    }
    
    #dropdown-productos .dropdown-item:last-child {
        border-bottom: none;
    }
    
    #dropdown-productos .dropdown-item:hover {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        transform: translateX(5px);
    }
    
    #dropdown-productos .dropdown-item:hover .producto-unidad,
    #dropdown-productos .dropdown-item:hover .producto-precio {
        color: rgba(255, 255, 255, 0.9);
    }
    
    .producto-icono {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
    }
    
    #dropdown-productos .dropdown-item:hover .producto-icono {
        background: rgba(255, 255, 255, 0.2);
    }
    
    .producto-nombre {
        font-weight: 600;
        font-size: 0.95rem;
        color: #2d3748;
    }
    
    #dropdown-productos .dropdown-item:hover .producto-nombre {
        color: white;
    }
    
    .producto-unidad {
        font-size: 0.8rem;
        color: #718096;
        margin-top: 2px;
    }
    
    .producto-precio {
        font-weight: 700;
        font-size: 1rem;
        color: #38a169;
        background: #f0fff4;
        padding: 4px 12px;
        border-radius: 20px;
        margin-left: auto;
        flex-shrink: 0;
        white-space: nowrap;
    }
    
    #dropdown-productos .dropdown-item:hover .producto-precio {
        background: rgba(255, 255, 255, 0.2);
        color: white;
    }
    
    /* Layout de producto en el dropdown - grid fijo */
    .producto-row {
        display: grid;
        grid-template-columns: 1fr auto;
        align-items: center;
        width: 100%;
        gap: 16px;
    }
    
    .producto-info {
        display: flex;
        align-items: center;
        min-width: 0;
        overflow: hidden;
    }
    
    .producto-text {
        min-width: 0;
        overflow: hidden;
    }
    
    .producto-text .producto-nombre {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>

<script>
// ========================================
// PUNTO DE VENTA - LÓGICA
// ========================================

let items = [];
let productosDisponibles = <?php echo json_encode($productos); ?>;
let clientesDisponibles = <?php echo json_encode($clientes); ?>;

// Inicializar fecha actual
document.getElementById('fecha').valueAsDate = new Date();

// ========================================
// BÚSQUEDA DE CLIENTES CON AUTOCOMPLETADO
// ========================================
const busquedaClienteInput = document.getElementById('busqueda-cliente');
const dropdownClientes = document.getElementById('dropdown-clientes');

busquedaClienteInput.addEventListener('input', function() {
    const query = this.value.toLowerCase().trim();
    
    if (query.length < 2) {
        dropdownClientes.style.display = 'none';
        return;
    }
    
    // Búsqueda local en el array de clientes (igual que productos)
    const resultados = clientesDisponibles.filter(c => 
        c.nombre_rs.toLowerCase().includes(query) || 
        c.dni_ruc.toLowerCase().includes(query)
    );
    
    if (resultados.length === 0) {
        dropdownClientes.innerHTML = '<div class="dropdown-item text-muted">No se encontraron clientes</div>';
    } else {
        dropdownClientes.innerHTML = resultados.map(c => `
            <a class="dropdown-item" href="#" onclick="seleccionarCliente(${c.id_cliente}, '${c.nombre_rs.replace(/'/g, "\\'")}', '${c.dni_ruc.replace(/'/g, "\\'")}', event)">
                <div class="fw-semibold">${c.nombre_rs}</div>
                <div class="small text-muted">${c.dni_ruc} - ${c.tipo_cliente}</div>
            </a>
        `).join('');
    }
    
    dropdownClientes.style.display = 'block';
});

// Seleccionar cliente del dropdown
function seleccionarCliente(id, nombre, dniRuc, event) {
    event.preventDefault();
    document.getElementById('id_cliente').value = id;
    document.getElementById('cliente-seleccionado-nombre').value = nombre;
    document.getElementById('busqueda-cliente').value = `${nombre} (${dniRuc})`;
    dropdownClientes.style.display = 'none';
}

// Cerrar dropdown de clientes al hacer clic fuera
document.addEventListener('click', function(e) {
    if (!busquedaClienteInput.contains(e.target) && !dropdownClientes.contains(e.target)) {
        dropdownClientes.style.display = 'none';
    }
});

// ========================================
// BÚSQUEDA DE PRODUCTOS CON AUTOCOMPLETADO
// ========================================
const busquedaInput = document.getElementById('busqueda-producto');
const dropdownProductos = document.getElementById('dropdown-productos');

busquedaInput.addEventListener('input', function() {
    const query = this.value.toLowerCase().trim();
    
    if (query.length < 2) {
        dropdownProductos.style.display = 'none';
        return;
    }
    
    const resultados = productosDisponibles.filter(p => 
        p.nombre.toLowerCase().includes(query)
    );
    
    if (resultados.length === 0) {
        dropdownProductos.innerHTML = '<div class="dropdown-item text-muted">No se encontraron productos</div>';
    } else {
        dropdownProductos.innerHTML = resultados.map(p => `
            <a class="dropdown-item producto-item" href="#" onclick="agregarProductoDirecto(${p.id_producto}, '${p.nombre.replace(/'/g, "\\'")}', '${p.unidad_medida.replace(/'/g, "\\'")}', ${p.precio_venta}, event)">
                <div class="producto-row">
                    <div class="producto-info">
                        <div class="producto-icono me-3">
                            <i class="ti ti-package"></i>
                        </div>
                        <div class="producto-text">
                            <div class="producto-nombre">${p.nombre}</div>
                            <div class="producto-unidad">${p.unidad_medida}</div>
                        </div>
                    </div>
                    <div class="producto-precio">
                        S/. ${p.precio_venta.toFixed(2)}
                    </div>
                </div>
            </a>
        `).join('');
    }
    
    dropdownProductos.style.display = 'block';
});

// Agregar producto al hacer clic en el dropdown
function agregarProductoDirecto(id, nombre, unidad, precio, event) {
    event.preventDefault();
    
    // Verificar si ya existe
    const existente = items.find(i => i.id_producto === id);
    if (existente) {
        existente.cantidad += 1;
        existente.subtotal = existente.cantidad * existente.precio;
    } else {
        // Marcar como nuevo para animación
        items.push({
            id_producto: id,
            nombre: nombre,
            unidad: unidad,
            cantidad: 1,
            precio: parseFloat(precio) || 0,
            subtotal: parseFloat(precio) || 0,
            _esNuevo: true
        });
    }
    
    renderItems();
    
    // Limpiar búsqueda
    document.getElementById('busqueda-producto').value = '';
    dropdownProductos.style.display = 'none';
}

// Cerrar dropdown al hacer clic fuera
document.addEventListener('click', function(e) {
    if (!busquedaInput.contains(e.target) && !dropdownProductos.contains(e.target)) {
        dropdownProductos.style.display = 'none';
    }
});

// Renderizar items en tabla
function renderItems() {
    const tbody = document.getElementById('tbody-items');
    const mensajeVacio = document.getElementById('mensaje-vacio');
    
    if (items.length === 0) {
        tbody.innerHTML = '';
        mensajeVacio.style.display = 'block';
        document.getElementById('total-venta').textContent = 'S/. 0.00';
        return;
    }
    
    mensajeVacio.style.display = 'none';
    
    tbody.innerHTML = items.map((item, index) => `
        <tr class="${item._esNuevo ? 'agregando' : ''}">
            <td>
                <div class="font-weight-medium">${item.nombre}</div>
                <div class="text-muted small">${item.unidad}</div>
            </td>
            <td>
                <input type="number" class="form-control" value="${item.cantidad}" 
                       min="1" onchange="actualizarCantidad(${index}, this.value)" style="width: 80px;">
            </td>
            <td>
                <input type="number" class="form-control" value="${item.precio.toFixed(2)}" 
                       step="0.01" onchange="actualizarPrecio(${index}, this.value)" style="width: 100px;">
            </td>
            <td class="text-end fw-bold">S/. ${item.subtotal.toFixed(2)}</td>
            <td>
                <button class="btn btn-danger btn-sm" onclick="eliminarItem(${index})">
                    <i class="ti ti-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
    
    // Quitar marca de nuevo después de renderizar
    items.forEach(item => delete item._esNuevo);
    
    calcularTotal();
}

// Actualizar cantidad
function actualizarCantidad(index, nuevaCantidad) {
    items[index].cantidad = parseInt(nuevaCantidad) || 1;
    items[index].subtotal = items[index].cantidad * items[index].precio;
    renderItems();
}

// Actualizar precio
function actualizarPrecio(index, nuevoPrecio) {
    items[index].precio = parseFloat(nuevoPrecio) || 0;
    items[index].subtotal = items[index].cantidad * items[index].precio;
    renderItems();
}

// Eliminar item
function eliminarItem(index) {
    items.splice(index, 1);
    renderItems();
}

// Calcular total
function calcularTotal() {
    const total = items.reduce((sum, item) => sum + item.subtotal, 0);
    document.getElementById('total-venta').textContent = 'S/. ' + total.toFixed(2);
}

// Limpiar venta
document.getElementById('btn-limpiar').addEventListener('click', function() {
    Swal.fire({
        title: '¿Limpiar venta?',
        text: 'Se eliminarán todos los productos agregados',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, limpiar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (!result.isConfirmed) return;
        items = [];
        document.getElementById('id_cliente').value = '';
        document.getElementById('busqueda-cliente').value = '';
        document.getElementById('cliente-seleccionado-nombre').value = '';
        renderItems();
    });
});

// Procesar venta
document.getElementById('btn-procesar').addEventListener('click', function() {
    // Validaciones
    const idCliente = document.getElementById('id_cliente').value;
    const fecha = document.getElementById('fecha').value;
    
    if (!idCliente) {
        Swal.fire('Advertencia', 'Seleccione un cliente', 'warning');
        return;
    }
    if (items.length === 0) {
        Swal.fire('Advertencia', 'Agregue al menos un producto', 'warning');
        return;
    }
    
    const total = items.reduce((sum, item) => sum + item.subtotal, 0);
    
    const ventaData = {
        id_cliente: idCliente,
        fecha: fecha,
        total: total,
        items: items
    };
    
    // Enviar al servidor
    fetch('<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=guardar_venta', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(ventaData)
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
                Swal.fire({
                    icon: 'success',
                    title: 'Venta registrada',
                    text: 'La venta se guardó correctamente',
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
});
</script>

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
        <!-- Título y Switch de Modo Venta Masiva -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0 fw-bold"><i class="ti ti-shopping-cart-share me-2 text-primary"></i>Nueva Venta</h3>
            <div class="form-check form-switch m-0 py-2 px-3 bg-success-lt rounded-pill border border-success border-opacity-25 shadow-sm">
                <input class="form-check-input bg-success border-success" type="checkbox" id="chk-venta-masiva" style="cursor: pointer;">
                <label class="form-check-label fw-bold text-success" for="chk-venta-masiva" style="cursor: pointer; font-size: 0.9rem;">
                    <i class="ti ti-users me-1 fs-3 align-middle"></i> Modo Venta Masiva (Cola)
                </label>
            </div>
        </div>

        <!-- Alerta de Modo Venta Masiva -->
        <div class="alert alert-success d-none mb-3 shadow-sm border-0 bg-success-lt" id="alert-modo-masivo">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="me-3 text-success fs-1">
                        <i class="ti ti-info-circle-filled"></i>
                    </div>
                    <div>
                        <strong class="text-success d-block h4 mb-0">Modo Venta Masiva Activo</strong>
                        <span class="text-muted small">Los datos de Cliente, Producto y Método de Pago permanecerán fijos para registrar consecutivamente a la fila sin recargar la página.</span>
                    </div>
                </div>
                <span class="badge bg-success text-white py-1 px-2 rounded">Alta Velocidad</span>
            </div>
        </div>
        
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
                    <option value="VENTA">Venta</option>
                    <option value="DONACION">Donación</option>
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
        
        <!-- Panel de Historial de Venta Masiva (Oculto por defecto) -->
        <div class="card d-none mt-3 border-0 shadow-sm" id="card-historial-cola">
            <div class="card-header bg-success-lt py-2 px-3 border-0">
                <h4 class="card-title text-success mb-0 d-flex align-items-center fw-bold">
                    <i class="ti ti-history me-2 fs-3"></i> Últimas Ventas Procesadas en Cola
                </h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                    <table class="table table-vcenter card-table mb-0" id="tabla-historial-cola">
                        <thead>
                            <tr class="text-muted small">
                                <th>Hora</th>
                                <th>Cliente</th>
                                <th>Producto</th>
                                <th class="text-end">Cant.</th>
                                <th class="text-end">Monto</th>
                                <th class="text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-historial-cola">
                            <tr>
                                <td colspan="6" class="text-center py-3 text-muted small">
                                    <i class="ti ti-inbox me-1"></i>Ninguna venta registrada en esta sesión de cola
                                </td>
                            </tr>
                        </tbody>
                    </table>
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
        dropdownClientes.innerHTML = `
            <div class="dropdown-item text-muted">No se encontraron clientes</div>
            <a class="dropdown-item text-success fw-bold border-top" href="#" onclick="registrarClienteRapidoDesdeInput(event, '${query.replace(/'/g, "\\'")}')">
                <i class="ti ti-user-plus me-2"></i>Registrar rápido: "${query}"
            </a>
        `;
    } else {
        dropdownClientes.innerHTML = resultados.map(c => `
            <a class="dropdown-item" href="#" onclick="seleccionarCliente(${c.id_cliente}, '${c.nombre_rs.replace(/'/g, "\\'")}', '${c.dni_ruc.replace(/'/g, "\\'")}', event)">
                <div class="fw-semibold">${c.nombre_rs}</div>
                <div class="small text-muted">${c.dni_ruc} - ${c.tipo_cliente}</div>
            </a>
        `).join('') + `
            <a class="dropdown-item text-success fw-bold border-top" href="#" onclick="registrarClienteRapidoDesdeInput(event, '${query.replace(/'/g, "\\'")}')">
                <i class="ti ti-user-plus me-2"></i>Registrar rápido: "${query}"
            </a>
        `;
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

// Registrar cliente rápido desde input de autocompletado
function registrarClienteRapidoDesdeInput(event, nombre) {
    if (event) event.preventDefault();
    
    if (!nombre || nombre.trim() === '') {
        Swal.fire('Advertencia', 'El nombre del cliente no puede estar vacío', 'warning');
        return;
    }
    
    Swal.fire({
        title: 'Registrando cliente...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    fetch('<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=crear_cliente_rapido', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ nombre: nombre.trim() })
    })
    .then(r => r.text())
    .then(text => {
        const trimmed = text.trim();
        const jsonStart = trimmed.indexOf('{');
        const jsonEnd = trimmed.lastIndexOf('}');
        if (jsonStart === -1 || jsonEnd === -1) {
            throw new Error('Respuesta inválida del servidor');
        }
        const data = JSON.parse(trimmed.substring(jsonStart, jsonEnd + 1));
        
        if (data.success) {
            // Añadir al array local para futuras búsquedas
            clientesDisponibles.push({
                id_cliente: data.id_cliente,
                nombre_rs: data.nombre_rs,
                dni_ruc: data.dni_ruc,
                tipo_cliente: 'Externo'
            });
            
            // Seleccionar automáticamente
            document.getElementById('id_cliente').value = data.id_cliente;
            document.getElementById('cliente-seleccionado-nombre').value = data.nombre_rs;
            document.getElementById('busqueda-cliente').value = `${data.nombre_rs} (${data.dni_ruc})`;
            dropdownClientes.style.display = 'none';
            
            Swal.fire({
                icon: 'success',
                title: 'Cliente registrado',
                text: `${data.nombre_rs} fue seleccionado correctamente`,
                timer: 1500,
                showConfirmButton: false
            });
            
            // Enfocar en cantidad si el producto ya está en tabla
            setTimeout(() => {
                const qtyInput = document.querySelector('#tbody-items input[type="number"]');
                if (qtyInput) {
                    qtyInput.focus();
                    qtyInput.select();
                } else {
                    document.getElementById('busqueda-producto').focus();
                }
            }, 300);
        } else {
            Swal.fire('Error', data.message || 'No se pudo registrar el cliente', 'error');
        }
    })
    .catch(err => {
        Swal.fire('Error', 'Error de conexión con el servidor: ' + err.message, 'error');
    });
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
    const metodoPago = document.getElementById('metodo_pago').value;
    
    if (!idCliente) {
        Swal.fire('Advertencia', 'Seleccione un cliente', 'warning');
        return;
    }
    if (!metodoPago) {
        Swal.fire('Advertencia', 'Seleccione el método de pago', 'warning');
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
        metodo_pago: metodoPago,
        items: items
    };
    
    // Deshabilitar botón para evitar doble clic
    const btnProcesar = document.getElementById('btn-procesar');
    if (btnProcesar) btnProcesar.disabled = true;
    
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
        if (btnProcesar) btnProcesar.disabled = false;
        
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
                // Si el modo de venta masiva está activo
                const esMasivo = document.getElementById('chk-venta-masiva')?.checked;
                
                if (esMasivo) {
                    const nombreCliente = document.getElementById('cliente-seleccionado-nombre').value || document.getElementById('busqueda-cliente').value;
                    const primerItem = items[0] || { nombre: 'Producto', cantidad: 0 };
                    
                    // Agregar al historial de cola
                    agregarHistorialCola(nombreCliente, primerItem.nombre, primerItem.cantidad, total);
                    
                    // Mostrar toast rápido y no bloqueante
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'bottom-end',
                        showConfirmButton: false,
                        timer: 1500,
                        timerProgressBar: true
                    });
                    Toast.fire({
                        icon: 'success',
                        title: 'Venta en cola registrada con éxito'
                    });
                    
                    // Conservar producto bloqueado y vaciar para la siguiente venta
                    const lockedProduct = items.length > 0 ? { ...items[0] } : null;
                    items = [];
                    if (lockedProduct) {
                        lockedProduct.cantidad = 1;
                        lockedProduct.subtotal = lockedProduct.precio;
                        items.push(lockedProduct);
                    }
                    renderItems();
                    
                    // Enfocar automáticamente el input de cantidad
                    setTimeout(() => {
                        const qtyInput = document.querySelector('#tbody-items input[type="number"]');
                        if (qtyInput) {
                            qtyInput.focus();
                            qtyInput.select();
                        }
                    }, 200);
                } else {
                    // Flujo estándar: recargar página
                    Swal.fire({
                        icon: 'success',
                        title: 'Venta registrada',
                        text: 'La venta se guardó correctamente',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                }
            } else {
                Swal.fire('Error', data.message || 'No se pudo guardar', 'error');
            }
        } catch (e) {
            Swal.fire('Error', 'Error al procesar respuesta', 'error');
        }
    })
    .catch(err => {
        if (btnProcesar) btnProcesar.disabled = false;
        Swal.fire('Error', 'Error de conexión', 'error');
    });
});

// Event listeners para el Modo Venta Masiva (Cola)
const chkVentaMasiva = document.getElementById('chk-venta-masiva');
const alertModoMasivo = document.getElementById('alert-modo-masivo');
const cardHistorialCola = document.getElementById('card-historial-cola');

if (chkVentaMasiva) {
    chkVentaMasiva.addEventListener('change', function() {
        if (this.checked) {
            if (alertModoMasivo) alertModoMasivo.classList.remove('d-none');
            if (cardHistorialCola) cardHistorialCola.classList.remove('d-none');
        } else {
            if (alertModoMasivo) alertModoMasivo.classList.add('d-none');
            if (cardHistorialCola) cardHistorialCola.classList.add('d-none');
        }
    });
}

// Función para agregar registros al historial de venta masiva
function agregarHistorialCola(cliente, producto, cantidad, total) {
    const tbody = document.getElementById('tbody-historial-cola');
    if (!tbody) return;
    
    const hora = new Date().toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    
    // Remover mensaje de vacío si existe
    if (tbody.innerHTML.includes('Ninguna venta registrada')) {
        tbody.innerHTML = '';
    }
    
    const tr = document.createElement('tr');
    tr.style.backgroundColor = 'rgba(40, 167, 69, 0.1)';
    tr.innerHTML = `
        <td>${hora}</td>
        <td><strong class="text-dark">${cliente}</strong></td>
        <td>${producto}</td>
        <td class="text-end fw-bold">${cantidad}</td>
        <td class="text-end text-success fw-bold">S/. ${parseFloat(total).toFixed(2)}</td>
        <td class="text-center"><span class="badge bg-success-lt text-success"><i class="ti ti-check me-1"></i>Guardado</span></td>
    `;
    
    tbody.insertBefore(tr, tbody.firstChild);
    
    // Suavizar fondo a los 2 segundos
    setTimeout(() => {
        tr.style.transition = 'background-color 1s ease';
        tr.style.backgroundColor = 'transparent';
    }, 2000);
}

// Escuchar tecla Enter en el input de cantidad para procesar ágilmente
const tbodyItems = document.getElementById('tbody-items');
if (tbodyItems) {
    tbodyItems.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && e.target.type === 'number') {
            e.preventDefault();
            document.getElementById('btn-procesar').click();
        }
    });
}
</script>

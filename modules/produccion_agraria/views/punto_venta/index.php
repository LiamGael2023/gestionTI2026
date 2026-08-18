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
        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-body py-2 px-3">
                <div class="text-uppercase text-muted fw-bold fs-4">
                    <i class="ti ti-leaf me-2 text-primary"></i>
                    Sistema de Seguimiento y control de Productos Agricolas
                </div>
            </div>
        </div>
        <!-- Título -->
        <h3 class="mb-3 fw-bold"><i class="ti ti-shopping-cart-share me-2 text-primary"></i>Nueva Venta</h3>

        <!-- ============================================================
             POS 2 COLUMNAS: Catálogo (izquierda) + Cliente/Carrito (derecha)
             ============================================================ -->
        <div class="row g-3 pos-main-row">

            <!-- COLUMNA IZQUIERDA: CATÁLOGO DE PRODUCTOS -->
            <div class="col-lg-5 col-xl-4 d-flex">
                <div class="card flex-fill d-flex flex-column mb-0">
                    <div class="card-header py-2 d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">
                            <i class="ti ti-packages me-2 text-primary"></i>Catálogo de Productos
                        </h4>
                        <span class="badge bg-primary-lt" id="productos-count"></span>
                    </div>
                    <div class="card-body d-flex flex-column p-2">
                        <div class="d-flex gap-2 mb-2 flex-shrink-0">
                            <div class="input-icon w-100">
                                <input type="text" class="form-control" id="busqueda-producto"
                                       placeholder="Buscar producto..." autocomplete="off">
                                <span class="input-icon-addon">
                                    <i class="ti ti-search"></i>
                                </span>
                            </div>
                            <div class="form-check form-switch mb-0 align-self-center flex-shrink-0">
                                <input class="form-check-input" type="checkbox" id="chk-solo-stock" style="cursor:pointer;">
                                <label class="form-check-label small text-nowrap fw-semibold" for="chk-solo-stock">Solo stock</label>
                            </div>
                        </div>
                        <div class="pos-scroll-area" id="productos-grid">
                            <div class="text-center py-4 text-muted small" id="productos-cargando">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div>Cargando productos...
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COLUMNA DERECHA: CLIENTE Y PAGO + CARRITO -->
            <div class="col-lg-7 col-xl-8 d-flex flex-column gap-3">

                <!-- Card: Cliente y Pago -->
                <div class="card flex-shrink-0">
                    <div class="card-header py-2">
                        <h4 class="card-title mb-0">
                            <i class="ti ti-user me-2 text-primary"></i>Cliente y Pago
                        </h4>
                    </div>
                    <div class="card-body py-2 px-3">
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label small fw-semibold mb-1">Cliente</label>
                                <div class="position-relative">
                                    <input type="text" class="form-control" id="busqueda-cliente"
                                           placeholder="Buscar cliente..." autocomplete="off">
                                    <div class="dropdown-menu w-100" id="dropdown-clientes" style="display: none; max-height: 300px; overflow-y: auto;">
                                        <!-- Resultados de búsqueda se mostrarán aquí -->
                                    </div>
                                    <input type="hidden" id="id_cliente">
                                    <input type="hidden" id="cliente-seleccionado-nombre">
                                    <input type="hidden" id="tipo_cliente">
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold mb-1">Fecha</label>
                                <input type="date" class="form-control" id="fecha" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold mb-1">Método</label>
                                <select class="form-select" id="metodo_pago">
                                    <option value="">Método de pago</option>
                                    <option value="VENTA">Venta</option>
                                    <option value="DONACION">Donación</option>
                                </select>
                            </div>
                            <div class="col-12" id="div-descuento-planilla" style="display:none;">
                                <div class="d-flex align-items-center border rounded w-100 py-2 px-3 gap-2" style="background:#fff;">
                                    <label class="fw-semibold text-muted small mb-0" for="chk-descuento-planilla" style="cursor:pointer;">
                                        <i class="ti ti-calculator me-1 align-middle"></i> Descuento planilla
                                    </label>
                                    <input type="checkbox" id="chk-descuento-planilla" class="form-check-input m-0">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between border rounded w-100 py-2 px-3 gap-2" style="background: #fff;">
                                    <label class="fw-semibold text-muted small mb-0" for="chk-venta-masiva" style="cursor: pointer;">
                                        <i class="ti ti-users me-1 align-middle"></i> Modo Venta Masiva (Cola)
                                    </label>
                                    <input type="checkbox" id="chk-venta-masiva" class="form-check-input m-0" style="cursor: pointer; float: none; width: 2.5em; height: 1.25em; background-size: 1.25em; background-position: left center; border-radius: 2em; flex-shrink: 0;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card: Carrito (con barra fija inferior) -->
                <div class="card flex-fill d-flex flex-column mb-0">
                    <div class="card-header py-2">
                        <h4 class="card-title mb-0">
                            <i class="ti ti-basket me-2 text-primary"></i>Carrito
                        </h4>
                    </div>
                    <div class="pos-scroll-area p-0" id="contenedor-tabla-items">
                        <table class="table table-vcenter card-table mb-0" id="tabla-items">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th class="w-1">Cant</th>
                                    <th class="w-1">Precio</th>
                                    <th class="w-1">Subtotal</th>
                                    <th class="w-1"></th>
                                </tr>
                            </thead>
                            <tbody id="tbody-items">
                                <!-- Items se agregarán dinámicamente -->
                            </tbody>
                        </table>
                        <div class="text-center text-muted" id="mensaje-vacio">
                            <i class="ti ti-shopping-cart-off fs-1 mb-2"></i>
                            <p>No hay productos agregados</p>
                        </div>
                    </div>
                    <!-- Barra fija: Total + acciones -->
                    <div class="card-footer pos-cart-footer py-2 px-3 d-flex align-items-center gap-2">
                        <div class="total-box flex-fill d-flex justify-content-between align-items-center px-3 py-2">
                            <span class="fw-bold total-label">TOTAL</span>
                            <span class="fs-3 fw-bold total-amount" id="total-venta">S/. 0.00</span>
                        </div>
                        <div class="d-flex gap-2 flex-shrink-0">
                            <button class="btn btn-outline-secondary flex-shrink-0" id="btn-limpiar" title="Limpiar carrito">
                                <i class="ti ti-trash"></i>
                            </button>
                            <button class="btn btn-success btn-lg fw-bold flex-shrink-0" id="btn-procesar">
                                <i class="ti ti-check me-2"></i>Procesar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel de Historial -->
        <div class="card border-0 shadow-sm mt-3" id="card-historial-cola">
            <div class="card-header bg-success-lt py-2 px-3 border-0">
                <h4 class="card-title text-success mb-0 d-flex align-items-center fw-bold">
                    <i class="ti ti-history me-2 fs-3"></i> Últimas Ventas Procesadas
                </h4>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 380px; overflow-y: auto;">
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
                                    <i class="ti ti-inbox me-1"></i>Ninguna venta registrada en esta sesión
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
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/punto_venta.css?v=<?php echo @filemtime(__DIR__ . '/../../assets/css/punto_venta.css'); ?>">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// ========================================
// PUNTO DE VENTA - LÓGICA
// ========================================

let items = [];
let numGrupo = localStorage.getItem('pech_pos_num_grupo') || null;
let productosDisponibles = <?php echo json_encode($productos, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE); ?>;
let clientesDisponibles = <?php echo json_encode($clientes, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE); ?>;
let csrfToken = <?php echo json_encode($csrfToken ?? '', JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE); ?>;

// Normaliza texto para búsquedas sin distinguir tildes ni mayúsculas
function normalizarTexto(str) {
    const s = String(str == null ? '' : str).toLowerCase();
    return s.replace(/[áéíóúüñ]/g, c => ({ 'á':'a','é':'e','í':'i','ó':'o','ú':'u','ü':'u','ñ':'n' }[c]));
}

// Inicializar fecha actual
document.getElementById('fecha').valueAsDate = new Date();

// ========================================
// BÚSQUEDA DE CLIENTES CON AUTOCOMPLETADO
// ========================================
const busquedaClienteInput = document.getElementById('busqueda-cliente');
const dropdownClientes = document.getElementById('dropdown-clientes');
let clienteAutoSeleccionando = false;

busquedaClienteInput.addEventListener('input', function() {
    if (clienteAutoSeleccionando) return;
    const query = normalizarTexto(this.value).trim();
    
    if (query.length < 2) {
        dropdownClientes.style.display = 'none';
        return;
    }
    
    // Búsqueda local en el array de clientes (igual que productos)
    const resultados = clientesDisponibles.filter(c => 
        normalizarTexto(c.nombre_rs).includes(query) || 
        normalizarTexto(c.dni_ruc).includes(query)
    );
    
    if (resultados.length === 0) {
        const docLimpio = query.replace(/\D/g, '');
        const esDNI = docLimpio.length === 8;
        const esRUC = docLimpio.length === 11;
        
        if (esDNI || esRUC) {
            const fuente = esDNI ? 'RENIEC' : 'SUNAT';
            dropdownClientes.innerHTML = `<div class="dropdown-item text-muted">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>Buscando en ${fuente}...
            </div>`;
            dropdownClientes.style.display = 'block';
            
            fetch(`<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=buscar_cliente_api&documento=${docLimpio}&csrf_token=${encodeURIComponent(csrfToken)}`)
                .then(r => r.text())
                .then(text => {
                    const trimmed = text.trim();
                    const js = trimmed.indexOf('{');
                    const je = trimmed.lastIndexOf('}');
                    if (js === -1 || je === -1) { throw new Error('Respuesta invalida'); }
                    const d = JSON.parse(trimmed.substring(js, je + 1));
                    
                    if (d.success && d.data) {
                        const c = d.data;
                        const tipoTxt = (c.tipo_cliente == 0 || c.tipo_cliente === 'Planilla') ? 'Planilla' : 'Externo';
                        const existe = clientesDisponibles.find(x => x.id_cliente == c.id_cliente);
                        if (!existe) {
                            clientesDisponibles.push({
                                id_cliente: c.id_cliente,
                                nombre_rs: c.nombre_rs,
                                dni_ruc: c.dni_ruc,
                                tipo_cliente: tipoTxt
                            });
                        }
                        const nombreEsc = c.nombre_rs.replace(/'/g, "\\'");
                        const dniEsc = c.dni_ruc.replace(/'/g, "\\'");
                        dropdownClientes.innerHTML = `<a class="dropdown-item" href="#" onclick="seleccionarCliente(${c.id_cliente}, '${nombreEsc}', '${dniEsc}', '${tipoTxt}', event)">
                            <div class="fw-semibold text-success"><i class="ti ti-check me-1"></i>${c.nombre_rs}</div>
                            <div class="small text-muted">${c.dni_ruc} - ${tipoTxt} (${fuente})</div>
                        </a>
                        <a class="dropdown-item text-success fw-bold border-top" href="#" onclick="registrarClienteRapidoDesdeInput(event, '${query.replace(/'/g, "\\'")}')">
                            <i class="ti ti-user-plus me-2"></i>Registrar manualmente
                        </a>`;
                        dropdownClientes.style.display = 'block';
                    } else {
                        dropdownClientes.innerHTML = `
                            <div class="dropdown-item text-muted small">No encontrado en ${fuente}</div>
                            <a class="dropdown-item text-success fw-bold border-top" href="#" onclick="registrarClienteRapidoDesdeInput(event, '${query.replace(/'/g, "\\'")}')">
                                <i class="ti ti-user-plus me-2"></i>Registrar manualmente: "${query}"
                            </a>`;
                    }
                })
                .catch(() => {
                    dropdownClientes.innerHTML = `
                        <div class="dropdown-item text-danger">Error al consultar ${fuente}</div>
                        <a class="dropdown-item text-success fw-bold border-top" href="#" onclick="registrarClienteRapidoDesdeInput(event, '${query.replace(/'/g, "\\'")}')">
                            <i class="ti ti-user-plus me-2"></i>Registrar manualmente
                        </a>`;
                });
            return;
        }
        
        // Comportamiento original para texto normal
        dropdownClientes.innerHTML = `
            <div class="dropdown-item text-muted">No se encontraron clientes</div>
            <a class="dropdown-item text-success fw-bold border-top" href="#" onclick="registrarClienteRapidoDesdeInput(event, '${query.replace(/'/g, "\\'")}')">
                <i class="ti ti-user-plus me-2"></i>Registrar rápido: "${query}"
            </a>
        `;
    } else {
        dropdownClientes.innerHTML = resultados.map(c => {
            const nombreEsc = c.nombre_rs.replace(/'/g, "\\'");
            const dniEsc = c.dni_ruc.replace(/'/g, "\\'");
            const tipoEsc = String(c.tipo_cliente).replace(/'/g, "\\'");
            return `<a class="dropdown-item" href="#" onclick="seleccionarCliente(${c.id_cliente}, '${nombreEsc}', '${dniEsc}', '${tipoEsc}', event)">
                <div class="fw-semibold">${c.nombre_rs}</div>
                <div class="small text-muted">${c.dni_ruc} - ${c.tipo_cliente}</div>
            </a>`;
        }).join('') + `
            <a class="dropdown-item text-success fw-bold border-top" href="#" onclick="registrarClienteRapidoDesdeInput(event, '${query.replace(/'/g, "\\'")}')">
                <i class="ti ti-user-plus me-2"></i>Registrar rápido: "${query}"
            </a>
        `;
    }
    
    dropdownClientes.style.display = 'block';
});

// Seleccionar cliente del dropdown
function seleccionarCliente(id, nombre, dniRuc, tipoCliente, event) {
    if (event) event.preventDefault();
    document.getElementById('id_cliente').value = id;
    document.getElementById('cliente-seleccionado-nombre').value = nombre;
    document.getElementById('tipo_cliente').value = tipoCliente;
    document.getElementById('busqueda-cliente').value = `${nombre} (${dniRuc})`;
    dropdownClientes.style.display = 'none';
    actualizarOpcionesPlanilla();
}

function actualizarOpcionesPlanilla() {
    const tipoCliente = document.getElementById('tipo_cliente').value;
    const divDescuento = document.getElementById('div-descuento-planilla');
    const chkDescuento = document.getElementById('chk-descuento-planilla');
    const selMetodo = document.getElementById('metodo_pago');
    if (tipoCliente === 'Planilla') {
        divDescuento.style.display = 'flex';
    } else {
        divDescuento.style.display = 'none';
        chkDescuento.checked = false;
        if (selMetodo.value === 'PLANILLA') selMetodo.value = 'VENTA';
    }
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
        body: JSON.stringify({ nombre: nombre.trim(), csrf_token: csrfToken })
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
// CATÁLOGO DE PRODUCTOS (GRID)
// ========================================
const busquedaInput = document.getElementById('busqueda-producto');

function renderProductos(filtro) {
    const grid = document.getElementById('productos-grid');
    const query = normalizarTexto(filtro).trim();
    const chkStock = document.getElementById('chk-solo-stock');

    let productos = productosDisponibles.slice();

    if (chkStock && chkStock.checked) {
        productos = productos.filter(p => Number(p.stock_total || 0) > 0);
    }

    if (query.length >= 1) {
        productos = productos.filter(p => {
            const campos = [p.nombre, p.nombre_clase, p.nombre_centro, String(p.id_producto || '')]
                .filter(v => v != null)
                .map(normalizarTexto);
            return campos.some(t => t.includes(query));
        });
    }

    const counter = document.getElementById('productos-count');
    if (counter) counter.textContent = productos.length + ' producto' + (productos.length === 1 ? '' : 's');

    if (productos.length === 0) {
        grid.innerHTML = '<div class="text-center py-4 text-muted small">Sin resultados</div>';
        return;
    }

    // Ordenar: con stock primero, luego alfabéticamente
    productos.sort((a, b) => {
        const sA = Number(a.stock_total || 0) > 0 ? 0 : 1;
        const sB = Number(b.stock_total || 0) > 0 ? 0 : 1;
        if (sA !== sB) return sA - sB;
        return normalizarTexto(a.nombre).localeCompare(normalizarTexto(b.nombre));
    });

    grid.innerHTML = productos.map(p => {
        const stockTotal = Number(p.stock_total || 0);
        let stockClass = 'ok';
        let stockText = 'Stock: ' + stockTotal;
        if (stockTotal <= 0) {
            stockClass = 'agotado';
            stockText = 'Agotado';
        } else if (stockTotal < 10) {
            stockClass = 'critico';
        }

        let imgHtml = '<i class="ti ti-package"></i>';
        if (p.imagen_nombre) {
            imgHtml = `<img src="<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=ver_imagen_producto&id=${p.id_producto}" alt="">`;
        }

        const precio = Number(p.precio_venta || 0);
        const titleAttr = String(p.nombre || '').replace(/"/g, '&quot;');

        return `
            <div class="producto-card${stockTotal <= 0 ? ' agotado' : ''}"
                 onclick="agregarProductoDirecto(${p.id_producto}, event)"
                 title="${titleAttr}">
                <div class="producto-card-img">${imgHtml}</div>
                <div class="producto-card-body">
                    <div class="producto-card-nombre">${p.nombre}</div>
                    <div class="producto-card-meta">${p.unidad_medida || ''}</div>
                </div>
                <div class="producto-card-side">
                    <span class="producto-stock ${stockClass}">${stockText}</span>
                    <span class="producto-precio">S/. ${precio.toFixed(2)}</span>
                </div>
            </div>`;
    }).join('');
}

busquedaInput.addEventListener('input', function() {
    renderProductos(this.value);
});

const chkSoloStock = document.getElementById('chk-solo-stock');
if (chkSoloStock) {
    chkSoloStock.addEventListener('change', function() {
        renderProductos(document.getElementById('busqueda-producto').value);
    });
}

// Agregar producto al hacer clic en la tarjeta del catálogo
function agregarProductoDirecto(id, event) {
    if (event) event.preventDefault();

    const prodRef = productosDisponibles.find(p => p.id_producto == id);
    if (!prodRef) return;

    const stockTotal = Number(prodRef.stock_total || 0);
    const nombre = prodRef.nombre;
    const unidad = prodRef.unidad_medida || '';
    const precio = Number(prodRef.precio_venta || 0);

    if (stockTotal <= 0) {
        Swal.fire('Sin stock', 'Este producto no cuenta con stock disponible', 'warning');
        return;
    }

    // Verificar si ya existe
    const existente = items.find(i => i.id_producto == id);
    if (existente) {
        if (existente.cantidad + 1 > stockTotal) {
            Swal.fire('Stock insuficiente', `No puedes agregar más unidades. El stock máximo disponible es ${stockTotal} ${unidad}`, 'warning');
            return;
        }
        existente.cantidad += 1;
        existente.subtotal = existente.cantidad * existente.precio;
    } else {
        // Marcar como nuevo para animación
        items.push({
            id_producto: id,
            nombre: nombre,
            unidad: unidad,
            cantidad: 1,
            precio: precio,
            subtotal: precio,
            stock_max: stockTotal,
            nombre_centro: prodRef.nombre_centro || '',
            imagen_nombre: prodRef.imagen_nombre || '',
            _esNuevo: true
        });
    }

    renderItems();
}

// Render inicial del catálogo
document.addEventListener('DOMContentLoaded', function() {
    renderProductos('');
});

// Renderizar items en tabla
function renderItems() {
    const tbody = document.getElementById('tbody-items');
    const mensajeVacio = document.getElementById('mensaje-vacio');
    
    if (items.length === 0) {
        tbody.innerHTML = '';
        mensajeVacio.style.display = 'block';
        document.getElementById('total-venta').textContent = 'S/. 0.00';
        saveCartToLocalStorage();
        return;
    }
    
    mensajeVacio.style.display = 'none';
    
    tbody.innerHTML = items.map((item, index) => {
        const stockRestante = item.stock_max - item.cantidad;
        let stockBadgeClass = 'text-success';
        if (stockRestante < 5) {
            stockBadgeClass = 'text-danger fw-bold';
        } else if (stockRestante < 10) {
            stockBadgeClass = 'text-warning fw-bold';
        }
        
        let imgHtml = '';
        if (item.imagen_nombre) {
            imgHtml = `<img src="<?php echo BASE_URL; ?>/index.php?module=produccion_agraria&action=ver_imagen_producto&id=${item.id_producto}" 
                            alt="${item.nombre}" 
                            class="avatar avatar-sm me-2 border rounded" 
                            style="object-fit: cover; width: 38px; height: 38px;">`;
        } else {
            imgHtml = `<span class="avatar avatar-sm bg-secondary-lt me-2 rounded" style="width: 38px; height: 38px; font-size: 16px; display: inline-flex; align-items: center; justify-content: center;">📦</span>`;
        }
        
        return `
            <tr class="${item._esNuevo ? 'agregando' : ''}">
                <td>
                    <div class="d-flex align-items-center">
                        ${imgHtml}
                        <div>
                            <div class="font-weight-medium">${item.nombre}</div>
                            <div class="text-muted small">
                                ${item.unidad} | <span class="text-info">${item.nombre_centro || 'Sin centro'}</span> | 
                                Stock restante: <span class="${stockBadgeClass}">${stockRestante}</span>
                            </div>
                        </div>
                    </div>
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
        `;
    }).join('');
    
    // Quitar marca de nuevo después de renderizar
    items.forEach(item => delete item._esNuevo);
    
    calcularTotal();
    saveCartToLocalStorage();
}

// Actualizar cantidad con validación de stock máximo
function actualizarCantidad(index, nuevaCantidad) {
    const item = items[index];
    const qty = parseInt(nuevaCantidad) || 1;
    if (item.stock_max !== undefined && qty > item.stock_max) {
        Swal.fire('Stock insuficiente', `La cantidad excede el stock máximo disponible (${item.stock_max} ${item.unidad})`, 'warning');
        renderItems();
        return;
    }
    item.cantidad = qty;
    item.subtotal = item.cantidad * item.precio;
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
        document.getElementById('tipo_cliente').value = '';
        document.getElementById('chk-descuento-planilla').checked = false;
        document.getElementById('div-descuento-planilla').style.display = 'none';
        clearCartFromLocalStorage();
        renderItems();
    });
});

// Procesar venta
document.getElementById('btn-procesar').addEventListener('click', function() {
    // Validaciones
    const idCliente = document.getElementById('id_cliente').value;
    const fecha = document.getElementById('fecha').value;
    const metodoPagoSelect = document.getElementById('metodo_pago').value;
    const esPlanilla = document.getElementById('tipo_cliente').value === 'Planilla' || document.getElementById('tipo_cliente').value === '1';
    const descuentoPlanilla = esPlanilla && document.getElementById('chk-descuento-planilla').checked;
    
    if (!idCliente) {
        Swal.fire('Advertencia', 'Seleccione un cliente', 'warning');
        return;
    }
    if (items.length === 0) {
        Swal.fire('Advertencia', 'Agregue al menos un producto', 'warning');
        return;
    }

    let metodoPagoFinal = metodoPagoSelect;
    if (descuentoPlanilla) {
        metodoPagoFinal = 'PLANILLA';
    } else if (!metodoPagoSelect) {
        Swal.fire('Advertencia', 'Seleccione el método de pago', 'warning');
        return;
    }
    
    const total = items.reduce((sum, item) => sum + item.subtotal, 0);
    
    const ventaData = {
        id_cliente: idCliente,
        fecha: fecha,
        total: total,
        metodo_pago: metodoPagoFinal,
        descuento_planilla: descuentoPlanilla ? 1 : 0,
        num_grupo: numGrupo || undefined,
        items: items,
        csrf_token: csrfToken
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
                // Agregar al historial de ultimas ventas (siempre)
                const nombreCliente = document.getElementById('cliente-seleccionado-nombre').value || document.getElementById('busqueda-cliente').value;
                const primerItem = items[0] || { nombre: 'Producto', cantidad: 0 };
                agregarHistorialCola(nombreCliente, primerItem.nombre, primerItem.cantidad, total);
                
                const esMasivo = document.getElementById('chk-venta-masiva')?.checked;
                
                if (esMasivo) {
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
                    // Limpiamos el localStorage y guardamos el nuevo estado del carrito masivo bloqueado
                    clearCartFromLocalStorage();
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
                    // Flujo estándar: limpiar local storage y recargar
                    clearCartFromLocalStorage();
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

// ========================================
// PERSISTENCIA LOCALSTORAGE - CART UTILS
// ========================================

function saveCartToLocalStorage() {
    const cartData = {
        items: items,
        id_cliente: document.getElementById('id_cliente').value || '',
        busqueda_cliente: document.getElementById('busqueda-cliente').value || '',
        cliente_seleccionado_nombre: document.getElementById('cliente-seleccionado-nombre').value || '',
        tipo_cliente: document.getElementById('tipo_cliente').value || '',
        metodo_pago: document.getElementById('metodo_pago').value || '',
        descuento_planilla: document.getElementById('chk-descuento-planilla').checked
    };
    localStorage.setItem('pech_pos_cart', JSON.stringify(cartData));
}

function loadCartFromLocalStorage() {
    try {
        const dataStr = localStorage.getItem('pech_pos_cart');
        if (!dataStr) return;
        
        const cartData = JSON.parse(dataStr);
        if (!cartData) return;
        
        if (Array.isArray(cartData.items)) {
            items = cartData.items;
            // Restaurar dinámicamente datos de centro, imagen y stock_max si son de una sesión antigua o incompleta
            items.forEach(item => {
                const prodRef = productosDisponibles.find(p => p.id_producto == item.id_producto);
                if (prodRef) {
                    if (!item.nombre_centro) item.nombre_centro = prodRef.nombre_centro;
                    if (!item.imagen_nombre) item.imagen_nombre = prodRef.imagen_nombre;
                    if (item.stock_max === undefined) item.stock_max = prodRef.stock_total;
                }
            });
        }
        
        if (cartData.id_cliente) {
            document.getElementById('id_cliente').value = cartData.id_cliente;
            document.getElementById('busqueda-cliente').value = cartData.busqueda_cliente || '';
            document.getElementById('cliente-seleccionado-nombre').value = cartData.cliente_seleccionado_nombre || '';
            document.getElementById('tipo_cliente').value = cartData.tipo_cliente || '';
            actualizarOpcionesPlanilla();
            if (cartData.descuento_planilla) {
                const chk = document.getElementById('chk-descuento-planilla');
                const sel = document.getElementById('metodo_pago');
                chk.checked = true;
                sel.value = 'PLANILLA';
                sel.disabled = true;
            }
        }
        
        if (cartData.metodo_pago && cartData.metodo_pago !== 'PLANILLA') {
            document.getElementById('metodo_pago').value = cartData.metodo_pago;
        }
        
        renderItems();
    } catch (e) {
        console.error('Error al restaurar carrito:', e);
    }
}

function clearCartFromLocalStorage() {
    localStorage.removeItem('pech_pos_cart');
}

// Generar num_grupo para venta masiva
function generarNumGrupo() {
    const now = new Date();
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, '0');
    const d = String(now.getDate()).padStart(2, '0');
    const h = String(now.getHours()).padStart(2, '0');
    const min = String(now.getMinutes()).padStart(2, '0');
    const s = String(now.getSeconds()).padStart(2, '0');
    return `VTA-${y}${m}${d}-${h}${min}${s}`;
}

// Listener del checkbox venta masiva: generar/clear num_grupo
document.getElementById('chk-venta-masiva').addEventListener('change', function() {
    if (this.checked) {
        if (!numGrupo) {
            numGrupo = generarNumGrupo();
            localStorage.setItem('pech_pos_num_grupo', numGrupo);
        }
    } else {
        numGrupo = null;
        localStorage.removeItem('pech_pos_num_grupo');
    }
});

// Agregar listeners para persistir cambios en cliente y método de pago
document.getElementById('metodo_pago').addEventListener('change', saveCartToLocalStorage);
document.getElementById('chk-descuento-planilla').addEventListener('change', function() {
    const sel = document.getElementById('metodo_pago');
    if (this.checked) {
        sel.value = 'PLANILLA';
        sel.disabled = true;
    } else {
        sel.disabled = false;
        if (sel.value === 'PLANILLA') sel.value = 'VENTA';
    }
    saveCartToLocalStorage();
});
document.getElementById('busqueda-cliente').addEventListener('change', function() {
    // Si limpian a mano el campo de cliente
    if (this.value.trim() === '') {
        document.getElementById('id_cliente').value = '';
        document.getElementById('cliente-seleccionado-nombre').value = '';
        document.getElementById('tipo_cliente').value = '';
        document.getElementById('chk-descuento-planilla').checked = false;
        document.getElementById('div-descuento-planilla').style.display = 'none';
        document.getElementById('metodo_pago').disabled = false;
    }
    saveCartToLocalStorage();
});
// También guardar si se limpia el cliente por evento input vacío
document.getElementById('busqueda-cliente').addEventListener('input', function() {
    if (this.value.trim() === '') {
        document.getElementById('id_cliente').value = '';
        document.getElementById('cliente-seleccionado-nombre').value = '';
        document.getElementById('tipo_cliente').value = '';
        document.getElementById('chk-descuento-planilla').checked = false;
        document.getElementById('div-descuento-planilla').style.display = 'none';
        document.getElementById('metodo_pago').disabled = false;
        saveCartToLocalStorage();
    }
});

// Cargar carrito guardado al iniciar
document.addEventListener('DOMContentLoaded', loadCartFromLocalStorage);

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

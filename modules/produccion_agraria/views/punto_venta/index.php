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
        <div class="row g-3 mb-4">
            <div class="col-md-5">
                <input type="text" class="form-control form-control-lg" placeholder="Cliente" id="cliente">
            </div>
            <div class="col-md-3">
                <input type="date" class="form-control form-control-lg" id="fecha">
            </div>
            <div class="col-md-4">
                <select class="form-select form-select-lg" id="metodo_pago">
                    <option value="">metodo de pago</option>
                    <option value="efectivo">Efectivo</option>
                    <option value="tarjeta">Tarjeta</option>
                    <option value="transferencia">Transferencia</option>
                </select>
            </div>
        </div>
        
        <!-- Área de Items (Tabla) -->
        <div class="card mb-4">
            <div class="card-body p-0">
                <table class="table table-vcenter card-table" id="tabla-items">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="w-1">Cantidad</th>
                            <th class="w-1">Precio Unit.</th>
                            <th class="w-1">Subtotal</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Items se agregarán dinámicamente -->
                    </tbody>
                </table>
                
                <!-- Mensaje cuando está vacío -->
                <div class="text-center py-5 text-muted" id="mensaje-vacio">
                    <i class="ti ti-shopping-cart-off fs-1 mb-2"></i>
                    <p>No hay productos agregados</p>
                </div>
            </div>
        </div>
        
        <!-- Buscador de Productos -->
        <div class="position-relative mb-4">
            <input type="text" class="form-control form-control-lg ps-4" placeholder="Buscar producto ..." id="buscar-producto">
            <span class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted">
                <i class="ti ti-search fs-4"></i>
            </span>
        </div>
        
        <!-- Footer de Venta -->
        <div class="row align-items-center g-3">
            <div class="col-md-5">
                <div class="bg-success text-white p-3 rounded d-flex justify-content-between align-items-center">
                    <span class="fs-4 fw-bold">Total:</span>
                    <span class="fs-2 fw-bold">S/. 0.00</span>
                </div>
            </div>
            <div class="col-md-7">
                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-outline-secondary btn-lg px-5" id="btn-limpiar">Limpiar</button>
                    <button class="btn btn-success btn-lg px-5" id="btn-procesar">Procesar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Estilos personalizados para Punto de Venta */
    .form-control-lg, .form-select-lg {
        font-size: 1rem;
        padding: 0.75rem 1rem;
    }
    
    .bg-success {
        background: linear-gradient(135deg, #009540 0%, #00c851 100%) !important;
    }
    
    .btn-success {
        background: linear-gradient(135deg, #009540 0%, #00c851 100%);
        border: none;
    }
    
    .btn-success:hover {
        background: linear-gradient(135deg, #007a33 0%, #009540 100%);
    }
    
    footer.bg-success {
        background: linear-gradient(135deg, #009540 0%, #007a33 100%) !important;
    }
    
    #tabla-items tbody:empty + #mensaje-vacio {
        display: block;
    }
    
    #tabla-items tbody:not(:empty) + #mensaje-vacio {
        display: none;
    }
</style>

<script>
    // Set fecha actual por defecto
    document.getElementById('fecha').valueAsDate = new Date();
</script>

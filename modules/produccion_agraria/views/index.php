
<div class='page-body'>
    <div class='container-xl' style='padding: 0 2rem;'>
        <h3 class='card-title mb-4'>Módulos de Producción Agraria</h3>
        <div class='row g-3'>
        <div class='col-md-4 col-sm-6'>
            <a href='<?php echo BASE_URL; ?>/produccion_agraria?action=inventario' class='card-link'>
                <div class='card-link bg-gradient-primary h-100 text-white'>
                    <div class='card-body text-center'>
                        <i class='ti ti-package icon-lg mb-3'></i>
                        <h5 class='card-title'>Inventario</h5>
                        <p class='card-text font-small'>Gestión de productos y existencias</p>
                    </div>
                </div>
            </a>
        </div>
        <div class='col-md-4 col-sm-6'>
            <a href='<?php echo BASE_URL; ?>/produccion_agraria?action=punto_venta' class='card-link'>
                <div class='card-link bg-gradient-primary h-100 text-white'>
                    <div class='card-body text-center'>
                        <i class='ti ti-shopping-cart icon-lg mb-3'></i>
                        <h5 class='card-title'>Punto de Venta</h5>
                        <p class='card-text font-small'>Ventas y facturación</p>
                    </div>
                </div>
            </a>
        </div>
        <div class='col-md-4 col-sm-6'>
            <a href='<?php echo BASE_URL; ?>/produccion_agraria?action=bandeja' class='card-link'>
                <div class='card-link bg-gradient-primary h-100 text-white'>
                    <div class='card-body text-center'>
                        <i class='ti ti-inbox icon-lg mb-3'></i>
                        <h5 class='card-title'>Bandeja de Entrada</h5>
                        <p class='card-text font-small'>Documentos y solicitudes</p>
                    </div>
                </div>
            </a>
        </div>
        <div class='col-md-4 col-sm-6'>
            <a href='<?php echo BASE_URL; ?>/produccion_agraria?action=tablas' class='card-link'>
                <div class='card-link bg-gradient-primary h-100 text-white'>
                    <div class='card-body text-center'>
                        <i class='ti ti-table icon-lg mb-3'></i>
                        <h5 class='card-title'>Tablas</h5>
                        <p class='card-text font-small'>Datos maestros y catálogos</p>
                    </div>
                </div>
            </a>
        </div>
        <div class='col-md-4 col-sm-6'>
            <a href='<?php echo BASE_URL; ?>/produccion_agraria?action=reportes' class='card-link'>
                <div class='card-link bg-gradient-primary h-100 text-white'>
                    <div class='card-body text-center'>
                        <i class='ti ti-file-report icon-lg mb-3'></i>
                        <h5 class='card-title'>Reportes</h5>
                        <p class='card-text font-small'>Informes y estadísticas</p>
                    </div>
                </div>
            </a>
        </div>
        <div class='col-md-4 col-sm-6'>
            <a href='<?php echo BASE_URL; ?>/produccion_agraria?action=dashboard' class='card-link'>
                <div class='card-link bg-gradient-primary h-100 text-white'>
                    <div class='card-body text-center'>
                        <i class='ti ti-dashboard icon-lg mb-3'></i>
                        <h5 class='card-title'>Dashboard</h5>
                        <p class='card-text font-small'>Panel principal</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>

<style>
    /* Gradientes para las tarjetas de módulos */
    .bg-gradient-primary {
        background: linear-gradient(135deg, var(--pech-verde, #009540) 0%, #00c851 100%) !important;
    }

    /* Tarjeta de módulo */
    .card-link {
        text-decoration: none;
        color: inherit;
        transition: all 0.3s ease;
        cursor: pointer;
        border-radius: 8px;
        overflow: hidden;
        min-height: 150px;
        padding: 1.5rem;
    }

    .card-link .card-body {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        height: 100%;
    }

    .card-link:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15) !important;
        color: inherit;
        text-decoration: none;
    }

    .card-link .card-title {
        font-weight: 600;
        text-align: center;
    }

    /* Estilos para listas */
    .list-unstyled li:last-child {
        border-bottom: none !important;
        margin-bottom: 0 !important;
        padding-bottom: 0 !important;
    }

    /* Font small */
    .font-small {
        font-size: 0.875rem;
    }

    .icon-lg {
        font-size: 2.5rem;
    }
</style>
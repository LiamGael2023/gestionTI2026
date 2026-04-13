
<div class='page-body'>
    <div class='container-xl' style='padding: 0 2rem;'>
        <h2 class='card-title mb-5'>Módulos de Producción Agraria</h2>
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
                        <p class='card-text font-small'>Realizar las Proformas</p>
                    </div>
                </div>
            </a>
        </div>
        <div class='col-md-4 col-sm-6'>
            <a href='<?php echo BASE_URL; ?>/produccion_agraria?action=bandeja' class='card-link'>
                <div class='card-link bg-gradient-primary h-100 text-white'>
                    <div class='card-body text-center'>
                        <i class='ti ti-inbox icon-lg mb-3'></i>
                        <h5 class='card-title'>Bandeja de Proformas</h5>
                        <p class='card-text font-small'>Validar las Proformas</p>
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
                        <p class='card-text font-small'>Datos maestros y catálogos y planillas</p>
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

<!-- Estilos compartidos del módulo -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/variables.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/components.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/common.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/modules/produccion_agraria/assets/css/responsive.css">

<style>
    /* Estilos específicos para las tarjetas de módulos */
    .card-link {
        text-decoration: none;
        color: inherit;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    /* Gradientes para las tarjetas de módulos */
    .bg-gradient-primary {
        background: linear-gradient(135deg, var(--pech-verde, #009540) 0%, #00c851 100%) !important;
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
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        color: #70e796;
        text-decoration: none;
    }

    .card-link:hover .card-title {
        color: #70e796;
    }

    .card-link:hover .card-text {
        color: #70e796;
    }

    .card-link:hover i {
        color: #70e796;
    }

    .card-link .card-title {
        font-weight: 600;
        text-align: center;
    }

    .page-body h2 {
        font-size: 1.5rem;
    }

    /* Font small */
    .font-small {
        font-size: 0.875rem;
    }
    
    .card-link .card-text {
        margin-bottom: 1.5rem; 
        padding: 0 10px;       
    }

    .icon-lg {
        font-size: 2.5rem;
        margin-top: 1.5rem; 
    }
</style>
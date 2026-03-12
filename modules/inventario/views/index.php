<style>
    .card-modern {
        border-radius: 16px;
        transition: all .2s ease;
    }

    .card-modern:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, .08);
    }

    .icon-box {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .icon-box i {
        font-size: 24px;
    }

    .arrow-link {
        color: #6c757d;
        font-size: 20px;
        transition: all .2s ease;
    }

    .arrow-link:hover {
        color: var(--tblr-primary);
        transform: translateX(4px);
    }
</style>



<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="mb-4">
            <h2 class="fw-bold mb-1">Inventario de Bienes Informáticos</h2>
            <div class="text-muted">
                Resumen general de activos y tareas de soporte técnico.
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        
        <div class="row g-4">

            <!-- Card 1 -->
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card shadow-sm card-modern h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="icon-box bg-primary-lt text-primary">
                                <i class="ti ti-device-desktop"></i>
                            </div>
                            <div class="h2 mb-0 fw-bold">124</div>
                        </div>

                        <div class="fw-semibold">Inventario de Activos</div>
                        <div class="text-muted small">
                            Gestión centralizada de hardware y equipos de red.
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-uppercase text-muted small fw-semibold">
                                Activos Totales
                            </span>

                            <a href="?module=inventario&action=activos" class="arrow-link">
                                <i class="ti ti-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Card 2 -->
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card shadow-sm card-modern h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="icon-box bg-primary-lt text-primary">
                                <i class="ti ti-device-desktop"></i>
                            </div>
                            <div class="h2 mb-0 fw-bold">124</div>
                        </div>

                        <div class="fw-semibold">Gestión de Equipos</div>
                        <div class="text-muted small">
                            Administración y control de los equipos registrados.
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-uppercase text-muted small fw-semibold">
                                Equipos Totales
                            </span>

                            <a href="?module=inventario&action=equipos" class="arrow-link">
                                <i class="ti ti-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Card 3 -->
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card shadow-sm card-modern h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="icon-box bg-warning-lt text-warning">
                                <i class="ti ti-license"></i>
                            </div>
                            <div class="h2 mb-0 fw-bold">28</div>
                        </div>

                        <div class="fw-semibold">Registro de Software</div>
                        <div class="text-muted small">
                            Control de licencias y suscripciones SaaS.
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-uppercase text-muted small fw-semibold">
                                Licencias Activas
                            </span>

                            <a href="software.php" class="arrow-link">
                                <i class="ti ti-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Card 4 -->
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card shadow-sm card-modern h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="icon-box bg-danger-lt text-danger">
                                <i class="ti ti-tool"></i>
                            </div>
                            <div class="h2 mb-0 fw-bold">8</div>
                        </div>

                        <div class="fw-semibold">Mantenimiento Técnico</div>
                        <div class="text-muted small">
                            Reparaciones preventivas y correctivas.
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-uppercase text-muted small fw-semibold">
                                Tareas Pendientes
                            </span>

                            <a href="mantenimiento.php" class="arrow-link">
                                <i class="ti ti-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    
</div>


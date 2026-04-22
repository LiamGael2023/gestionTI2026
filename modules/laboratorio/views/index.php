<div class="page-header d-print-none">
    <div class="container-xl">
        
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">
                    Módulo Principal
                </div>
                <h2 class="page-title">
                    <i class="ti ti-microscope me-2"></i> Laboratorio
                </h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h3 class="card-title mb-0">
                            <i class="ti ti-user me-2"></i> Información Personal
                        </h3>
                        <button class="btn btn-sm btn-ghost-primary" title="Editar perfil">
                            <i class="ti ti-edit"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if ($usuarioData): ?>
                            <div class="mb-3 pb-3 border-bottom">
                                <strong>Nombre:</strong>
                                <p class="text-secondary mb-0">
                                    <?php echo htmlspecialchars($usuarioData['nombres'] . ' ' . $usuarioData['apellidos'], ENT_QUOTES, 'UTF-8'); ?>
                                </p>
                            </div>
                            <div class="mb-0">
                                <strong>Rol:</strong>
                                <p class="text-secondary mb-0">
                                    <?php echo htmlspecialchars($usuarioData['rol'], ENT_QUOTES, 'UTF-8'); ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger mb-0">
                                <i class="ti ti-alert-circle me-2"></i> Error al cargar datos del usuario
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">
                            <i class="ti ti-briefcase me-2"></i> Responsabilidades
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($responsabilidades)): ?>
                            <ul class="list-unstyled">
                                <?php foreach ($responsabilidades as $resp): ?>
                                    <li class="mb-2 pb-2 border-bottom">
                                        <i class="ti ti-<?php echo htmlspecialchars($resp['icono'], ENT_QUOTES, 'UTF-8'); ?> text-primary me-2"></i>
                                        <strong><?php echo htmlspecialchars($resp['descripcion'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="alert alert-warning mb-0">
                                <i class="ti ti-alert-circle me-2"></i> No tienes responsabilidades asignadas
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== SECCIÓN 3: MENÚ DE MÓDULOS ===== -->
        <div class="row mb-3">
            <div class="col-12">
                <h3 class="mb-3">
                    <i class="ti ti-apps me-2"></i> Menú
                </h3>
            </div>
        </div>

        <!-- Grid de módulos con tarjetas clicables -->
        <div class="row row-deck row-cards">
            <?php if (!empty($responsabilidades)): ?>
                <?php foreach ($responsabilidades as $resp): ?>
                    <div class="col-sm-6 col-lg-4">
                        <a href="<?php echo htmlspecialchars($resp['url'], ENT_QUOTES, 'UTF-8'); ?>" 
                           class="card card-link border-0 shadow-sm bg-gradient-<?php echo htmlspecialchars($resp['color'], ENT_QUOTES, 'UTF-8'); ?> text-white">
                            <div class="card-body text-center py-5">
                                <div class="mb-3">
                                    <i class="ti ti-<?php echo htmlspecialchars($resp['icono'], ENT_QUOTES, 'UTF-8'); ?>" style="font-size: 2.5rem;"></i>
                                </div>
                                <h4 class="card-title text-white mb-1">
                                    <?php echo htmlspecialchars($resp['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                                </h4>
                                <p class="text-white-50 font-small">
                                    <?php echo htmlspecialchars($resp['descripcion'], ENT_QUOTES, 'UTF-8'); ?>
                                </p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-warning">
                        <i class="ti ti-alert-circle me-2"></i>
                        <strong>Sin acceso</strong> - No tienes responsabilidades asignadas en este módulo.
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- ===== ESTILOS PERSONALIZADOS ===== -->
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
    }

    .card-link:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15) !important;
        color: inherit;
        text-decoration: none;
    }

    .card-link .card-title {
        font-weight: 600;
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
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Efectos visuales personalizados si es necesario
        console.log('Laboratorio módulo cargado');
    });
</script>

<div class="card card-body mb-3">
    <h4 class="fw-bold mb-3">Fichas Técnicas</h4>

    <?php if ($totalFichas < $minimoFichasRequeridas): ?>
        <div class="alert alert-info" role="alert">
            Debe registrar al menos <?php echo $minimoFichasRequeridas; ?> fichas técnicas. Actualmente tiene <?php echo $totalFichas; ?>.
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-vcenter card-table table-striped">
            <thead>
                <tr>
                    <th>Marca</th>
                    <th>Modelo</th>
                    <th>Documento</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th class="text-center">Ranking</th>
                    <th class="text-end">Acción</th>
                </tr>
            </thead>
            <tbody id="tabla-fichas-tecnicas">
                <?php if (!empty($fichasTecnicas)): ?>
                    <?php $totalFichasLista = count($fichasTecnicas); ?>
                    <?php foreach ($fichasTecnicas as $indiceFicha => $ficha): ?>
                        <?php $estadoFicha = (int) $ficha['Estado']; ?>
                        <?php $esPrimeraFicha = $indiceFicha === 0; ?>
                        <?php $esUltimaFicha = $indiceFicha === ($totalFichasLista - 1); ?>
                        <tr data-id="<?php echo (int) $ficha['Id']; ?>">
                            <td><?php echo htmlspecialchars($ficha['Marca']); ?></td>
                            <td><?php echo htmlspecialchars($ficha['Modelo']); ?></td>
                            <td>
                                <?php if (!empty($ficha['Documento'])): ?>
                                    <a href="index.php?module=adquisiciones&action=verFichaTecnicaAjax&id=<?php echo (int) $ficha['Id']; ?>"
                                        onclick="return abrirPdfEnModal(this.href);"
                                        class="text-decoration-none text-reset"
                                        title="Ver PDF">
                                        <i class="ti ti-file-text icon-action"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="text-secondary">Sin documento</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($formatearFecha($ficha['FechaRegistro'])); ?></td>
                            <td>
                                <?php if ($estadoFicha === 1): ?>
                                    <span class="badge bg-success-lt">Enviado</span>
                                <?php else: ?>
                                    <span class="badge bg-warning-lt text-dark">Cargado</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="acciones-iconos justify-content-center">
                                    <i class="ti ti-arrow-up icon-action <?php echo $esPrimeraFicha ? 'text-secondary' : ''; ?>"
                                        title="Subir prioridad"
                                        <?php if (!$esPrimeraFicha): ?>onclick="moverFichaTecnicaRango(<?php echo (int) $ficha['Id']; ?>, 'up')" <?php endif; ?>></i>

                                    <i class="ti ti-arrow-down icon-action <?php echo $esUltimaFicha ? 'text-secondary' : ''; ?>"
                                        title="Bajar prioridad"
                                        <?php if (!$esUltimaFicha): ?>onclick="moverFichaTecnicaRango(<?php echo (int) $ficha['Id']; ?>, 'down')" <?php endif; ?>></i>
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="acciones-iconos">
                                    <?php if ($estadoFicha === 0): ?>
                                        <i class="ti ti-send icon-action"
                                            title="Marcar como enviada"
                                            onclick="cambiarEstadoFichaTecnica(<?php echo (int) $ficha['Id']; ?>, 1)"></i>
                                    <?php else: ?>
                                        <i class="ti ti-send-off icon-action"
                                            title="Marcar como pendiente"
                                            onclick="cambiarEstadoFichaTecnica(<?php echo (int) $ficha['Id']; ?>, 0)"></i>
                                    <?php endif; ?>

                                    <i class="ti ti-trash icon-action"
                                        title="Eliminar"
                                        onclick="eliminarFichaTecnica(<?php echo (int) $ficha['Id']; ?>)"></i>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-secondary">No hay fichas técnicas registradas.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-end mt-3">
        <button type="button"
            class="btn btn-primary"
            data-bs-toggle="modal"
            data-bs-target="#modalAgregarFichaTecnica"
            data-toggle="modal"
            data-target="#modalAgregarFichaTecnica"
            onclick="return abrirModalAgregarFichaTecnica();">
            Agregar
        </button>
    </div>
</div>

<div class="modal modal-blur fade" id="modalAgregarFichaTecnica" tabindex="-1" aria-labelledby="modalAgregarFichaTecnicaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAgregarFichaTecnicaLabel">Agregar Nueva Ficha Técnica</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Cerrar" onclick="return cerrarModalAgregarFichaTecnica();"></button>
            </div>
            <div class="modal-body">
                <form id="form-ficha-tecnica" enctype="multipart/form-data" onsubmit="return guardarFichaTecnica(event)">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Marca</label>
                            <input type="text" class="form-control" id="ficha_marca" name="Marca" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Modelo</label>
                            <input type="text" class="form-control" id="ficha_modelo" name="Modelo" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Documento PDF</label>
                            <input type="file" class="form-control" id="ficha_documento" name="DocumentoPDF" accept=".pdf" required>
                        </div>
                        <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal" onclick="return cerrarModalAgregarFichaTecnica();">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function abrirModalAgregarFichaTecnica() {
        const modalElement = document.getElementById('modalAgregarFichaTecnica');
        mostrarModal(modalElement);
        return false;
    }

    function cerrarModalAgregarFichaTecnica() {
        const modalElement = document.getElementById('modalAgregarFichaTecnica');
        ocultarModal(modalElement);
        return false;
    }

    function limpiarFormularioFichaTecnica() {
        const form = document.getElementById('form-ficha-tecnica');
        if (form) {
            form.reset();
        }
    }

    function inicializarModalAgregarFichaTecnica() {
        inicializarModalConLimpieza({
            modalId: 'modalAgregarFichaTecnica',
            datasetKey: 'adqFichaInit',
            limpiarFn: limpiarFormularioFichaTecnica
        });
    }

    async function guardarFichaTecnica(e) {
        e.preventDefault();

        try {
            const marca = document.getElementById('ficha_marca').value.trim();
            const modelo = document.getElementById('ficha_modelo').value.trim();
            const file = document.getElementById('ficha_documento').files[0];

            if (!marca || !modelo) {
                throw new Error('Marca y modelo son obligatorios.');
            }

            validarPdf(file);
            const documentoBase64 = await fileToBase64(file);
            const data = await enviarJson('index.php?module=adquisiciones&action=guardarFichaTecnicaAjax', {
                IdCatalogoTecnologico: idTecnologia,
                Marca: marca,
                Modelo: modelo,
                Anio: anioActual,
                Documento: documentoBase64
            });

            if (!data.ok) {
                throw new Error(data.error || 'No se pudo guardar la ficha técnica.');
            }

            cerrarModalAgregarFichaTecnica();
            await recargarVistaTecnologia();
        } catch (error) {
            window.adqNotifySafe('danger', 'Error al guardar ficha tecnica', error.message || 'Error al guardar la ficha tecnica.');
        }

        return false;
    }

    async function eliminarFichaTecnica(id) {
        const confirmado = await window.adqConfirmSafe({
            titulo: 'Confirmar eliminacion',
            mensaje: '¿Desea eliminar esta ficha técnica?',
            textoAceptar: 'Eliminar',
            textoCancelar: 'Cancelar',
            claseAceptar: 'btn-danger'
        });

        if (!confirmado) {
            return;
        }

        try {
            const data = await enviarJson('index.php?module=adquisiciones&action=eliminarFichaTecnicaAjax', {
                Id: id
            });
            if (!data.ok) {
                throw new Error(data.error || 'No se pudo eliminar la ficha técnica.');
            }
            await recargarVistaTecnologia();
        } catch (error) {
            window.adqNotifySafe('danger', 'Error al eliminar ficha tecnica', error.message || 'Error al eliminar la ficha tecnica.');
        }
    }

    async function cambiarEstadoFichaTecnica(id, estado) {
        try {
            const data = await enviarJson('index.php?module=adquisiciones&action=cambiarEstadoFichaTecnicaAjax', {
                Id: id,
                Estado: estado
            });

            if (!data.ok) {
                throw new Error(data.error || 'No se pudo cambiar el estado.');
            }

            await recargarVistaTecnologia();
        } catch (error) {
            window.adqNotifySafe('danger', 'Error al cambiar estado', error.message || 'Error al cambiar el estado.');
        }
    }

    async function moverFichaTecnicaRango(id, direccion) {
        if (!['up', 'down'].includes(direccion)) {
            window.adqNotifySafe('danger', 'Error', 'Dirección inválida para mover la ficha técnica.');
            return;
        }

        try {
            const data = await enviarJson('index.php?module=adquisiciones&action=moverFichaTecnicaRangoAjax', {
                Id: id,
                Direccion: direccion
            });

            if (!data.ok) {
                throw new Error(data.error || 'No se pudo cambiar el rango de la ficha técnica.');
            }

            await recargarVistaTecnologia();
        } catch (error) {
            window.adqNotifySafe('danger', 'Error al mover ficha tecnica', error.message || 'Error al cambiar el rango de la ficha técnica.');
        }
    }
</script>
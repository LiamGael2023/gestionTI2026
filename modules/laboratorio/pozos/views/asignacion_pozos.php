<?php
$id_proyecto = intval($_GET['id_proyecto'] ?? 0);
$asignacionModel = new MonitoreoPozoAsignacionModel($conn);
$catastroModel   = new CatastroPozoModel($conn);
$pozos           = $catastroModel->obtenerParaGeoportal();
$asignaciones    = $id_proyecto > 0 ? $asignacionModel->obtenerAsignacionesPorProyecto($id_proyecto, false) : [];

if ($id_proyecto <= 0) {
    echo '<div class="container-xl"><div class="alert alert-warning">Debe especificar un ID de proyecto.</div></div>';
    return;
}

$sqlProy = "SELECT Nombre_Proyecto, Temporada FROM laboratorio.Proyecto_Monitoreo WHERE Id_Proyecto = ?";
$stmtProy = sqlsrv_query($conn, $sqlProy, [$id_proyecto]);
$proy = sqlsrv_fetch_array($stmtProy, SQLSRV_FETCH_ASSOC);
?>
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle"><?php echo htmlspecialchars($proy['Temporada'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                <h2 class="page-title"><i class="ti ti-ruler-measure me-2"></i><?php echo htmlspecialchars($proy['Nombre_Proyecto'] ?? 'Asignacion de Pozos', ENT_QUOTES, 'UTF-8'); ?></h2>
            </div>
            <div class="col-auto ms-auto">
                <a href="?module=laboratorio&action=muestra&tab=masiva" class="btn btn-secondary">
                    <i class="ti ti-arrow-left me-1"></i> Volver al proyecto
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-list-check me-2"></i>Asignaciones de Pozos a Numeros de Muestra</h3>
            </div>
            <div class="card-body p-2">
                <div class="table-responsive">
                    <table id="tabla-asignaciones" class="table table-vcenter card-table table-striped">
                        <thead>
                            <tr>
                                <th>N° Muestra</th>
                                <th>Pozo Asignado</th>
                                <th>Valle</th>
                                <th>Ubicacion</th>
                                <th>Tipo</th>
                                <th>Tipo Analisis</th>
                                <th class="w-1">Accion</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal Swap -->
<div class="modal fade" id="modal-swap-pozo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-arrows-exchange me-2"></i>Cambiar Pozo (Swap)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="swap-numero-muestra">
                <p class="text-muted small mb-2">Slot N° <b id="swap-label-muestra">-</b></p>
                <div class="mb-3">
                    <label class="form-label">Pozo Actual</label>
                    <input type="text" class="form-control" id="swap-pozo-actual" readonly disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nuevo Pozo</label>
                    <select class="form-select" id="swap-nuevo-pozo">
                        <option value="">Seleccione un pozo...</option>
                        <?php foreach ($pozos as $p): ?>
                        <option value="<?php echo htmlspecialchars($p['Id_Pozo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars(($p['Id_Pozo'] ?? '') . ' - ' . ($p['valle'] ?? '') . ' - ' . ($p['ubicacion'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" id="btn-confirmar-swap">
                    <i class="ti ti-arrows-exchange me-1"></i> Cambiar Pozo
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
var idProyectoActual = <?php echo $id_proyecto; ?>;

document.addEventListener('DOMContentLoaded', function () {

    var tabla = $('#tabla-asignaciones').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'modules/laboratorio/pozos/controllers/PozoAPI.php?action=listar_asignaciones&id_proyecto=' + idProyectoActual,
            type: 'POST'
        },
        columns: [
            { data: 'numero_muestra' },
            { data: 'id_pozo' },
            { data: 'valle' },
            { data: 'ubicacion' },
            { data: 'tipopozo' },
            { data: 'es_laboratorio' },
            { data: 'accion', orderable: false, searchable: false }
        ],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        },
        pageLength: 50,
        order: [[0, 'asc']]
    });

    window.abrirSwapPozo = function (numMuestra, pozoActual) {
        $('#swap-numero-muestra').val(numMuestra);
        $('#swap-label-muestra').text(numMuestra);
        $('#swap-pozo-actual').val(pozoActual);
        $('#swap-nuevo-pozo').val('');
        $('#modal-swap-pozo').modal('show');
    };

    $('#btn-confirmar-swap').on('click', function () {
        var numMuestra = parseInt($('#swap-numero-muestra').val());
        var nuevoPozo  = $('#swap-nuevo-pozo').val();

        if (!nuevoPozo) {
            Swal.fire('Atencion', 'Debe seleccionar un nuevo pozo.', 'warning');
            return;
        }

        var btn = $('#btn-confirmar-swap');
        btn.prop('disabled', true);

        $.ajax({
            url: 'modules/laboratorio/pozos/controllers/PozoAPI.php?action=guardar_asignacion',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({
                id_proyecto: idProyectoActual,
                numero_muestra: numMuestra,
                id_pozo: nuevoPozo
            })
        })
        .done(function (resp) {
            if (resp.success) {
                $('#modal-swap-pozo').modal('hide');
                Swal.fire({ icon: 'success', title: 'Swap realizado', timer: 1500, showConfirmButton: false });
                tabla.ajax.reload(null, false);
            } else {
                Swal.fire('Error', resp.message, 'error');
            }
        })
        .fail(function () {
            Swal.fire('Error', 'Error de red.', 'error');
        })
        .always(function () {
            btn.prop('disabled', false);
        });
    });

});
</script>

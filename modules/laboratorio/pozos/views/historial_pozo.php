<?php
$id_pozo = strtoupper(trim((string)($_GET['id_pozo'] ?? '')));
$catastroModel = new CatastroPozoModel($conn);

if ($id_pozo === '') {
    echo '<div class="container-xl"><div class="alert alert-warning">Debe especificar un ID de pozo.</div></div>';
    return;
}

$pozo = $catastroModel->obtenerPorId($id_pozo);
if (!$pozo) {
    echo '<div class="container-xl"><div class="alert alert-danger">Pozo no encontrado: ' . htmlspecialchars($id_pozo, ENT_QUOTES, 'UTF-8') . '</div></div>';
    return;
}
?>
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Historial de Resultados</div>
                <h2 class="page-title"><i class="ti ti-chart-line me-2"></i><?php echo htmlspecialchars($id_pozo, ENT_QUOTES, 'UTF-8'); ?></h2>
            </div>
            <div class="col-auto ms-auto">
                <div class="d-flex gap-2 align-items-center">
                    <label class="form-label mb-0">Desde ano:</label>
                    <select class="form-select" id="filtro-anio" style="width:120px;">
                        <option value="">Todos</option>
                        <?php for ($y = 2017; $y <= intval(date('Y')); $y++): ?>
                        <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                    <a href="?module=laboratorio&action=pozos&subaction=index" class="btn btn-secondary">
                        <i class="ti ti-arrow-left me-1"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <!-- Info del pozo -->
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-info-circle me-2"></i>Datos del Pozo</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3"><b>ID Pozo:</b> <?php echo htmlspecialchars($pozo['Id_Pozo'] ?? $id_pozo, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="col-md-3"><b>Codigo:</b> <?php echo htmlspecialchars($pozo['codigo'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="col-md-3"><b>Cod. PECH:</b> <?php echo htmlspecialchars($pozo['codigopech'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="col-md-3"><b>Valle:</b> <?php echo htmlspecialchars($pozo['valle'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="col-md-3 mt-2"><b>Ubicacion:</b> <?php echo htmlspecialchars($pozo['ubicacion'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="col-md-3 mt-2"><b>Propietario:</b> <?php echo htmlspecialchars($pozo['propietario'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="col-md-3 mt-2"><b>Tipo:</b> <?php echo htmlspecialchars($pozo['tipopozo'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="col-md-3 mt-2"><b>PR:</b> <?php echo floatval($pozo['pr'] ?? 0); ?></div>
                    <div class="col-md-3 mt-2"><b>Coord. Este:</b> <?php echo floatval($pozo['coord_este'] ?? 0); ?></div>
                    <div class="col-md-3 mt-2"><b>Coord. Norte:</b> <?php echo floatval($pozo['coord_norte'] ?? 0); ?></div>
                    <div class="col-md-3 mt-2"><b>Cota:</b> <?php echo floatval($pozo['cota'] ?? 0); ?></div>
                    <div class="col-md-3 mt-2"><b>Zona:</b> <?php echo floatval($pozo['zona'] ?? 0); ?></div>
                    <div class="col-md-3 mt-2"><b>Departamento:</b> <?php echo htmlspecialchars($pozo['departamento'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="col-md-3 mt-2"><b>Provincia:</b> <?php echo htmlspecialchars($pozo['provincia'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="col-md-3 mt-2"><b>Distrito:</b> <?php echo htmlspecialchars($pozo['distrito'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="col-md-3 mt-2"><b>AAA:</b> <?php echo htmlspecialchars($pozo['aaa'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="col-md-3 mt-2"><b>ALA:</b> <?php echo htmlspecialchars($pozo['ala'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="col-md-3 mt-2"><b>UH:</b> <?php echo htmlspecialchars($pozo['uh'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="col-md-3 mt-2"><b>Fecha Inventario:</b> <?php echo htmlspecialchars($pozo['fechainventario'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="col-md-3 mt-2"><b>Ultima Sinc:</b> <?php echo ($pozo['Fecha_Sincronizacion'] ?? null) instanceof DateTime ? $pozo['Fecha_Sincronizacion']->format('d/m/Y H:i') : '-'; ?></div>
                </div>
            </div>
        </div>

        <!-- Resultados -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-table me-2"></i>Resultados de Parametros</h3>
                <span class="badge bg-blue-lt" id="badge-total-resultados">Cargando...</span>
            </div>
            <div class="card-body p-2">
                <div class="table-responsive">
                    <table id="tabla-resultados" class="table table-vcenter card-table table-striped">
                        <thead>
                            <tr>
                                <th>Fecha Medicion</th>
                                <th>Parametro</th>
                                <th>Categoria</th>
                                <th>Unidad</th>
                                <th>Valor Hallado</th>
                                <th>Muestra ID</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-resultados"></tbody>
                    </table>
                </div>
                <div id="sin-resultados" class="text-center text-muted py-4 d-none">
                    <i class="ti ti-database-off" style="font-size:2rem;"></i>
                    <p class="mt-2">No hay resultados registrados para este pozo.</p>
                </div>
            </div>
        </div>

        <!-- Monitoreos -->
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-calendar-event me-2"></i>Historial de Monitoreos</h3>
                <span class="badge bg-green-lt" id="badge-total-monitoreos">Cargando...</span>
            </div>
            <div class="card-body p-2">
                <div class="table-responsive">
                    <table id="tabla-monitoreos" class="table table-vcenter card-table table-striped">
                        <thead>
                            <tr>
                                <th>Proyecto</th>
                                <th>Fecha Toma</th>
                                <th>Orden</th>
                                <th># Muestra</th>
                                <th>Estado</th>
                                <th>Parametros</th>
                                <th>Accion</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-monitoreos"></tbody>
                    </table>
                </div>
                <div id="sin-monitoreos" class="text-center text-muted py-4 d-none">
                    <i class="ti ti-calendar-off" style="font-size:2rem;"></i>
                    <p class="mt-2">No hay monitoreos registrados para este pozo.</p>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var idPozo = <?php echo json_encode($id_pozo); ?>;

    function cargarResultados(anioDesde) {
        var url = 'modules/laboratorio/pozos/controllers/PozoAPI.php?action=historial_pozo&id_pozo=' + encodeURIComponent(idPozo);
        if (anioDesde) {
            url += '&anio_desde=' + encodeURIComponent(anioDesde);
        }

        var tbody = $('#tbody-resultados');
        var badge = $('#badge-total-resultados');
        var sinRes = $('#sin-resultados');

        tbody.html('<tr><td colspan="6" class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-1"></span> Cargando resultados...</td></tr>');

        $.getJSON(url, function (resp) {
            tbody.empty();
            if (!resp.success || !resp.resultados || !resp.resultados.length) {
                sinRes.removeClass('d-none');
                badge.text('0 resultados');
                return;
            }
            sinRes.addClass('d-none');

            resp.resultados.forEach(function (r) {
                var fecha = r.Fecha_Medicion || r.Fecha_Creacion || '';
                if (typeof fecha === 'object' && fecha.date) {
                    fecha = fecha.date.substring(0, 10);
                } else if (fecha.length > 10) {
                    fecha = fecha.substring(0, 10);
                }

                var row = '<tr>'
                    + '<td>' + htmlspecialchars(fecha || '-') + '</td>'
                    + '<td><b>' + htmlspecialchars(r.Parametro || r.Nombre || '-') + '</b></td>'
                    + '<td>' + htmlspecialchars(r.Categoria || '-') + '</td>'
                    + '<td>' + htmlspecialchars(r.Unidad_Medida || '-') + '</td>'
                    + '<td>' + (r.Valor_Hallado !== null && r.Valor_Hallado !== undefined ? r.Valor_Hallado : '-') + '</td>'
                    + '<td>#' + (r.Id_Muestra || '-') + '</td>'
                    + '</tr>';
                tbody.append(row);
            });

            badge.text(resp.resultados.length + ' resultados');
        })
        .fail(function () {
            tbody.html('<tr><td colspan="6" class="text-center text-danger py-3">Error al cargar resultados.</td></tr>');
        });
    }

    $('#filtro-anio').on('change', function () {
        cargarResultados($(this).val());
    });

    cargarResultados('');

    // Cargar monitoreos
    function cargarMonitoreos() {
        var url = 'modules/laboratorio/pozos/controllers/PozoAPI.php?action=monitoreos_pozo&id_pozo=' + encodeURIComponent(idPozo);
        var tbody = $('#tbody-monitoreos');
        var badge = $('#badge-total-monitoreos');
        var sinMon = $('#sin-monitoreos');

        tbody.html('<tr><td colspan="7" class="text-center text-muted py-3"><span class="spinner-border spinner-border-sm me-1"></span> Cargando monitoreos...</td></tr>');

        $.getJSON(url, function (resp) {
            tbody.empty();
            if (!resp.success || !resp.monitoreos || !resp.monitoreos.length) {
                sinMon.removeClass('d-none');
                badge.text('0 monitoreos');
                return;
            }
            sinMon.addClass('d-none');

            resp.monitoreos.forEach(function (m) {
                var fecha = m.Fecha_Toma || '';
                if (typeof fecha === 'object' && fecha.date) fecha = fecha.date.substring(0, 10);
                else if (fecha.length > 10) fecha = fecha.substring(0, 10);

                var row = '<tr>'
                    + '<td>' + htmlspecialchars(m.Proyecto || '-') + '</td>'
                    + '<td>' + htmlspecialchars(fecha || '-') + '</td>'
                    + '<td>' + (m.Orden || '-') + '</td>'
                    + '<td>#' + (m.Numero_Muestra || '-') + '</td>'
                    + '<td><span class="badge bg-' + (m.Estado === 'Finalizado' ? 'success' : m.Estado === 'Por Recepcionar' ? 'warning' : 'secondary') + '">' + htmlspecialchars(m.Estado || '-') + '</span></td>'
                    + '<td>' + (m.Total_Parametros || 0) + '</td>'
                    + '<td><a class="btn btn-sm btn-outline-primary" href="?module=laboratorio&action=muestra&subaction=resultados_pasados&id_muestra=' + (m.Id_Muestra || 0) + '" title="Ver resultados"><i class="ti ti-eye"></i></a></td>'
                    + '</tr>';
                tbody.append(row);
            });

            badge.text(resp.monitoreos.length + ' monitoreos');
        })
        .fail(function () {
            tbody.html('<tr><td colspan="7" class="text-center text-danger py-3">Error al cargar monitoreos.</td></tr>');
        });
    }

    cargarMonitoreos();

});

function htmlspecialchars(str) {
    if (!str) return '';
    return str.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}
</script>

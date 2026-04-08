<?php
require_once __DIR__ . '/../models/trabajador.php';
require_once __DIR__ . '/../controllers/trabajadorController.php';
require_once __DIR__ . '/../../../core/Auth.php';

Auth::check();
$idUsuarioLogueado = $_SESSION["usuario_id"] ?? null;

$anio       = $_GET["anio"]       ?? 2026;
$componente = $_GET["componente"] ?? null;
$meta       = $_GET["meta"]       ?? '';


if (empty($componente)) {
    $componentes = TrabajadorController::ctrMostrarComponentes();
    if (!empty($componentes)) {
        $componente = $componentes[0]["Id_Componente"];
    }
}


$mapMeta = [
    // 2361 => 227,
    // 1321 => 220,
    // 803  => 219,
    // 1224  => 227
];

if (isset($mapMeta[$idUsuarioLogueado])) {
    $meta       = $mapMeta[$idUsuarioLogueado];
    $componente = 26;
}


$trabajadores  = TrabajadorController::ctrMostrarTrabajadoresFiltro($anio, $componente, $meta);
$componentes   = TrabajadorController::ctrMostrarComponentes();
$metas         = TrabajadorController::ctrMostrarMetas($anio, $componente);
$marcaciones   = TrabajadorController::ctrMostrarTurnoTrabajador();
$marcacionturno = '';


$trabajadoresJS = [];
foreach ($trabajadores as $row) {
    $idTrabajador     = $row['Id_Trabajador'];
    $turnos           = TrabajadorController::ctrListarTurnosTrabajador($idTrabajador, $anio);
    $trabajadoresJS[] = [
        'id'         => $idTrabajador,
        'nombre'     => $row['Trab_Nombres_Full'],
        'componente' => $row['Id_Componente'],
        'meta'       => $row['Id_Meta'],
        'turnos'     => $turnos
    ];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Trabajadores</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="css/style.css">

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<style>
.tabla-scroll { overflow-x: auto; }
#tablaHorarioModal th,
#tablaHorarioModal td { text-align: center; font-size: 12px; white-space: nowrap; }
#tablaHorarioModal th:first-child,
#tablaHorarioModal td:first-child { position: sticky; left: 0; background: white; z-index: 2; }
#tablaHorarioModal thead th:first-child { z-index: 3; background: #f8f9fa; }
#modalDescripcion.modal { z-index: 1600 !important; }
</style>
</head>
<body style="background:#f4f6f9;">

<div class="page-body">
  <div class="container-xl">

    <?php require_once __DIR__ . '/partials/_filtros.php'; ?>
    <?php require_once __DIR__ . '/partials/_tabla.php'; ?>
    <?php require_once __DIR__ . '/partials/_modales.php'; ?>

  </div>
</div>

<div id="contenedorPDF" style="position:absolute; left:-9999px; top:0; background:white;"></div>

<script>
    const TRABAJADORES_JS  = <?php echo json_encode($trabajadoresJS); ?>;
    const ANIO_ACTUAL      = <?php echo json_encode($anio); ?>;
    const MAP_META_USUARIO = <?php echo json_encode($mapMeta ?? []); ?>;
    const ID_USUARIO       = <?php echo json_encode($idUsuarioLogueado); ?>;
</script>


<script src="modules/turnos/assets/js/turno.config.js"></script>
<script src="modules/turnos/assets/js/turno.tabla.js"></script>
<script src="modules/turnos/assets/js/turno.modal.js"></script>
<script src="modules/turnos/assets/js/turno.guardar.js"></script>
<script src="modules/turnos/assets/js/turno.init.js"></script>

</body>
</html>
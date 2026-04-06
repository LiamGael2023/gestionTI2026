<?php
require_once __DIR__ . '/../models/trabajador.php';
require_once __DIR__ . '/../controllers/trabajadorController.php';
require_once __DIR__ . '/../../../core/Auth.php';

// session_start();

// $idUsuarioLogueado = $_SESSION["id_Trabajador"] ?? null;
Auth::check();
$idUsuarioLogueado = $_SESSION["usuario_id"] ?? null;

$anio = $_GET["anio"] ?? 2026;
$componente = $_GET["componente"] ?? '';
$meta = $_GET["meta"] ?? '';
$tipotrabajador = $_GET["tipotrabajador"] ?? '';

$mapMeta = [
    // 2361 => 227,
    // 1321 => 220,
    // 803  => 219,
    // 1224  => 227
];

if(isset($mapMeta[$idUsuarioLogueado])){
    $meta = $mapMeta[$idUsuarioLogueado];
    $componente = 26;

}

$trabajadores = TrabajadorController::ctrMostrarTrabajadoresFiltro(
    $anio,
    $componente,
    $meta,
    $tipotrabajador
);



$trabajadoresJS = [];
foreach ($trabajadores as $row) {
    $idTrabajador = $row['Id_Trabajador']; 

    $turnos = TrabajadorController::ctrListarTurnosTrabajador($idTrabajador, $anio);

    $trabajadoresJS[] = [
        'id' => $idTrabajador,
        'nombre' => $row['Trab_Nombres_Full'],
        'componente' => $row['Id_Componente'],
        'meta' => $row['Id_Meta'],
        'turnos' => $turnos
    ];
}
// Arreglo de días de la semana
$diasSemana = ["Dom","Lun","Mar","Mie","Jue","Vie","Sab"];
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
/* Scroll horizontal para la tabla de horarios */
.tabla-scroll{
    overflow-x:auto;
}
#tablaHorarioModal th, #tablaHorarioModal td{
    text-align:center;
    font-size:12px;
    white-space:nowrap;
}
#tablaHorarioModal th:first-child, #tablaHorarioModal td:first-child{
    position:sticky;
    left:0;
    background:white;
    z-index:2;
}
#tablaHorarioModal thead th:first-child{
    z-index:3;
    background:#f8f9fa;
}
</style>
</head>
<body style="background:#f4f6f9;">


<!-- BODY -->
<div class="page-body">
  <div class="container-xl">

    <!-- CARD FILTROS -->
    <div class="card mb-3">
      <div class="card-body">

       <form method="GET">

         <div class="d-flex flex-column">

  
            <div class="d-flex align-items-end" style="gap:15px; width:100%;">

            <div>
              <label>Año</label>
              <select name="anio" id="anio" class="form-control">
                <?php for($i = date("Y")-7; $i <= date("Y")+2; $i++): ?>
                  <option value="<?= $i ?>" <?= ($anio == $i) ? 'selected' : '' ?>><?= $i ?></option>
                <?php endfor; ?>
              </select>
            </div>

             <div class="filtro-item">
                <label>Componente</label>
                <select name="componente" id="componente" class="form-control" <?= isset($mapMeta[$idUsuarioLogueado]) ? 'disabled' : '' ?>>
                    <?php
                    $componentes = TrabajadorController::ctrMostrarComponentes();
                    foreach ($componentes as $comp) {
                        echo '<option value="'.$comp["Id_Componente"].'" '.($componente == $comp["Id_Componente"] ? 'selected' : '').'>'.$comp["Comp_Descripcion"].'</option>';
                    }
                    ?>
                </select>
            </div>

            <div class="filtro-item">
                    <label>Meta</label>
                    <select name="meta" id="meta" class="form-control" <?= isset($mapMeta[$idUsuarioLogueado]) ? 'disabled' : '' ?>>
                        
                        <?php
                        $metas = TrabajadorController::ctrMostrarMetas($anio, $componente);
                        foreach ($metas as $m) {
                            echo '<option value="'.$m["Id_Meta"].'" '.($meta == $m["Id_Meta"] ? 'selected' : '').'>'.$m["Meta_Descripcion"].'</option>';
                        }
                        ?>
                    </select>
             </div>
            <div class="filtro-item">
                <label>Tipo Trabajador</label>
                <select name="tipotrabajador" id="tipotrabajador" class="form-control">
                    
                    <?php
                    $tipotrabajadores = TrabajadorController::ctrMostrarTipoTrabajador();
                    foreach ($tipotrabajadores as $tt) {
                        echo '<option value="'.$tt["Id_Trabajador_Tipo"].'" '.($tipotrabajador == $tt["Id_Trabajador_Tipo"] ? 'selected' : '').'>'.$tt["TrabTipo_Descripcion"].'</option>';
                    }
                    ?>
                </select>
            </div>
        </div>
        </div>
          <!-- BOTONES -->
          <div class="d-flex justify-content-end mt-3 flex-wrap" style="gap:10px;">
            <button type="submit" class="btn btn-primary">
              <i class="fa fa-search"></i> Buscar
            </button>

            <button type="button" id="btnAgregarHorario" class="btn btn-success">
              <i class="fa fa-calendar"></i> Agregar Turno
            </button>

            <button type="button" id="btnUsuariosSeleccionados" class="btn btn-success">
              <i class="fa fa-users"></i> Usuarios
            </button>
          </div>

        </form>

      </div>
    </div>

    <!-- CARD TABLA -->
    <div class="card">
      <div class="card-body">

        <div class="table-responsive">
          <table id="tablaTrabajadores" class="table table-bordered table-striped">
            <thead>
              <tr>
                <th><input type="checkbox" id="checkAll"></th>
                <th>Nombre</th>
                <th>Componente</th>
                <th>Meta</th>
                <th>Horario</th>
                <th>Turno</th>
                <th>Eliminar</th>
              </tr>
            </thead>

            <tbody>
 
<?php if(!empty($trabajadores)): ?>
    <?php foreach($trabajadores as $row): ?>
    <tr
        data-trabajador="<?= $row['Id_Trabajador'] ?>"
        data-componente="<?= $row['Id_Componente'] ?>"
        data-meta="<?= $row['Id_Meta'] ?>"
        data-anio="<?= $anio ?>"
        data-horario="<?= $row['Id_Horario'] ?? '' ?>"
        data-fechainicio="<?= $row['FechaInicioTurno'] ?? '' ?>"
        data-fechafin="<?= $row['FechaFinTurno'] ?? '' ?>"

    >
        <td><input type="checkbox" class="checkItem"></td>
        <td><?= $row['Trab_Nombres_Full'] ?></td>
        <td><?= $row['Comp_Descripcion'] ?></td>
        <td><?= $row['Meta_Descripcion'] ?></td>
        <td class="horarioSeleccionado">
            <?php if(!empty($row['Id_Horario'])): ?>
                <span class="badge badge-success"><?= $row['Horario'] ?? '' ?></span>
            <?php else: ?>
                <span class="badge badge-light">Sin horario</span>
            <?php endif; ?>
      </td>
        <td class="turnoAsignado" id="turno-<?= $row['Id_Trabajador'] ?>">
            <?php if(!empty($row['Turno'])): ?>
                <!-- <span class="badge badge-info"><?= $row['Turno'] ?></span> -->
                <span class="badge badge-info">Turno asignado</span>
            <?php else: ?>
                <span class="badge badge-light">Sin turno</span>
            <?php endif; ?>
        </td>
       <td>
        <button type="button" class="btnEliminarTurno btn btn-danger btn-sm">
            Eliminar
        </button>
    </td>
    </tr>
    <?php endforeach; ?>
<?php else: ?>
<tr>
<td></td>
<td></td>
<td></td>
<td class="text-center">No se encontraron registros</td>
<td></td>
<td></td>
<td></td>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>


<style>
  /* Asegurar que este modal siempre esté encima del otro */
  #modalDescripcion.modal {
      z-index: 1600 !important;
  }
</style>

<!-- MODAL DESCRIPCION DE TURNOS-->
<div class="modal fade" id="modalDescripcion" tabindex="-1" role="dialog" aria-labelledby="modalDescripcionLabel" aria-hidden="true" >
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalDescripcionLabel">Descripción del turno</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
         <div id="sugerenciasObservacion" class="mb-2"></div>
        <textarea id="descripcionTurno" class="form-control" rows="3" placeholder="Ingrese descripción"></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" id="guardarDescripcion" class="btn btn-success">Guardar</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
      </div>
    </div>
  </div>
</div>
<!-- MODALTURNO MENSUAL -->
<div class="modal fade" id="modalHorarioMes" tabindex="-1" role="dialog" aria-labelledby="modalHorarioMesLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalHorarioMesLabel">Asignar Turno</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">

  <!-- FILTROS DE MES Y AÑO -->
  <div class="filtros mb-2">
    <label>Mes:</label>
    <select id="mesModal" class="form-control form-control-sm d-inline w-auto">
      <option value="1">Enero</option>
      <option value="2">Febrero</option>
      <option value="3">Marzo</option>
      <option value="4">Abril</option>
      <option value="5">Mayo</option>
      <option value="6">Junio</option>
      <option value="7">Julio</option>
      <option value="8">Agosto</option>
      <option value="9">Septiembre</option>
      <option value="10">Octubre</option>
      <option value="11">Noviembre</option>
      <option value="12">Diciembre</option>
    </select>

    <label>Año:</label>
   <input type="number" id="anioModal" value="<?= $anio ?>"
     class="form-control form-control-sm d-inline w-auto" min="2000" max="<?= date("Y")+5 ?>">

    <button type="button" id="btnActualizarModal" class="btn btn-primary btn-sm">Actualizar</button>
    <button id="btnDescargarReporte" class="btn btn-info">
  <i class="fa fa-file-pdf"></i> Descargar Reporte
</button>
    
   
    <div class="filtro-item">
            <label>Tipo Marcacion</label>
            <select name="marcacion" id="marcacion" class="form-control">
               
                <?php
                $marcaciones = TrabajadorController::ctrMostrarTurnoTrabajador();
                foreach ($marcaciones as $marc) {
                    echo '<option value="'.$marc["Id_Marcacion_Tipo"].'" '.($marcacionturno == $marc["Id_Marcacion_Tipo"] ? 'selected' : '').'>'.$marc["MarcTipo_Descripcion"].'</option>';
                }
                ?>
            </select>
        </div>
     <div class="filtro-item">
        <button type="button" id="btnAgregarObservacion" class="btn btn-warning btn-sm mt-2" style="display:none;">
            <i class="fa fa-pencil"></i> Agregar Observación
        </button>
    </div>
  </div>

  <!-- TABLA HORARIO -->
 
  
<div id="reportePDF">

    <h5 id="tituloReporte" class="text-center mb-3"></h5>

    <div id="leyendaMarcacion" class="mb-2"></div>

    <div class="tabla-scroll">
      <table class="table table-bordered" id="tablaHorarioModal">
        <thead></thead>
        <tbody></tbody>
      </table>
    </div>

  </div>


</div>
      <div class="modal-footer">
        <button class="btn btn-success" id="guardarHorarioModal">Guardar Turno</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
<script>

let turnosBDGlobal = [];
let turnosTemp = [];

// SUGERENCIAS META 220

const sugerenciasMeta220 = [
    "DESARENADOR Y TANGUCHE",
    "DESARENADOR",
    "AGONIA",
    "CHOROBAL",
    "CAMARA DE CARGA",
    "BOCATOMA",
    "AREA COMERCIAL",
    "OPER. S.E. CHAO",
     "OPER. C.H. VIRU",
    "DPT GENERACION",
    "TURNOS EXTRA LABORADOS POR FALTA DE PERSONAL",
    "CAPACITACION TEORICO PRACTICO PRACTICANTES / C.H VIRU"
];

function cargarSugerenciasObservacion(){
    let html = '';

    sugerenciasMeta220.forEach(texto => {
        html += `
            <button type="button" class="btn btn-sm btn-outline-primary m-1 sugerencia-item">
                ${texto}
            </button>
        `;
    });

    $("#sugerenciasObservacion").html(html);
}

//
$(document).ready(function(){

    // DataTable
    $('#tablaTrabajadores').DataTable({
        pageLength:10,
        language:{ url:"//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" }
    });

    // Check all
    $('#checkAll').on('click', function(){
        let tabla = $('#tablaTrabajadores').DataTable();
        let rows = tabla.rows({ search: 'applied' }).nodes();
        $('input.checkItem', rows).prop('checked', this.checked);
    });

    
    $(document).on("click", ".modal .close, .modal [data-dismiss='modal']", function(e){
        e.stopPropagation();
        let modalId = $(this).closest(".modal").attr("id");
        $("#" + modalId).modal("hide");
    });

    $("#modalDescripcion").on("hidden.bs.modal", function(){
    if($("#modalHorarioMes").hasClass("show")){
        $("body").addClass("modal-open");
        $(".modal-backdrop").css("z-index", "");
    }
});

}); 


// ===============================
// CAMBIO DE FILTROS
// ===============================

$("#anio").change(function(){
    var anio = $(this).val();

    $.ajax({
        url:"modules/turnos/ajax/metas.php",
        method:"POST",
        data:{anio:anio},
        success:function(respuesta){
            $("#meta").html('<option value="">Todos</option>'+respuesta);
        }
    });

    $("form").submit();
});


$("#componente").change(function(){
    var componente = $(this).val();

    $.ajax({
        url:"modules/turnos/ajax/metas.php",
        method:"POST",
        data:{componente:componente},
        success:function(respuesta){
            $("#meta").html('<option value="">Todos</option>'+respuesta);
        }
    });

    $("form").submit();
});


// ===============================
// ABRIR MODAL
// ===============================

$("#btnAgregarHorario").click(function(){

    let trabajadores = [];

    let tabla = $('#tablaTrabajadores').DataTable();

tabla.rows().every(function(){
    let fila = $(this.node());

    let check = fila.find(".checkItem").prop("checked");

    if(check){
        trabajadores.push({
            id: fila.data("trabajador"),
            nombre: fila.find("td:eq(1)").text(),
            horario: fila.data("horario"),
            fechainicio: fila.data("fechainicio"),
            fechafin: fila.data("fechafin")
        });
    }
});

    if(trabajadores.length == 0){
        alert("Seleccione trabajadores");
        return;
    }

    // limpiar memoria temporal
    turnosTemp = [];

    sessionStorage.setItem("trabajadoresHorario", JSON.stringify(trabajadores));
     turnosBDGlobal = <?php echo json_encode($trabajadoresJS); ?>;

    $("#modalHorarioMes").modal("show");

    cargarTablaHorarioModal();

});


// ===============================
// CHECKBOX → GUARDADO TEMPORAL
// ===============================
let checkboxTemporal = null;
let ultimoCheckMarcado = null; 

$(document).on("change", "#tablaHorarioModal input[type='checkbox']", function(){

    let checked    = $(this).prop("checked");
    let trabajador = $(this).data("trabajador");
    let dia        = $(this).data("dia");
    let anio       = $("#anioModal").val();
    let mes        = $("#mesModal").val();
    let marcacion  = $("#marcacion").val();

    let fila = $("#tablaTrabajadores tbody tr[data-trabajador='" + trabajador + "']");
    let meta = fila.data("meta");

    if(checked){

        if(meta == 220){
          
            checkboxTemporal = $(this);

            let descripcion = "";
            let turnoTemp = turnosTemp.find(t =>
                t.trabajador == trabajador && t.dia == dia && t.mes == mes && t.anio == anio
            );
            if(turnoTemp){
                descripcion = turnoTemp.descripcion;
            } else {
                let trabajadorBD = turnosBDGlobal.find(t => t.id == trabajador);
                if(trabajadorBD){
                    trabajadorBD.turnos.forEach(turno => {
                        let fi = new Date(turno.FechaInicioTurno.date);
                        let ff = new Date(turno.FechaFinTurno.date);
                        let fechaCelda = new Date(anio, mes - 1, dia);
                        if(fechaCelda >= fi && fechaCelda <= ff){
                            descripcion = turno.Descripcion || "";
                        }
                    });
                }
            }
            cargarSugerenciasObservacion();
            $("#sugerenciasObservacion").show();
            $("#descripcionTurno").val(descripcion);
            $(".modal-backdrop").css("z-index", "1049");
            $("#modalDescripcion").css("z-index", "1700");
            $("#modalDescripcion").modal("show");

        } else {
           $("#sugerenciasObservacion").hide();
            ultimoCheckMarcado = $(this);

            let index = turnosTemp.findIndex(t =>
                t.trabajador == trabajador && t.dia == dia && t.mes == mes && t.anio == anio
            );
            if(index !== -1){
                turnosTemp[index].marcacion = marcacion;
            } else {
                turnosTemp.push({ trabajador, dia, mes, anio, marcacion, descripcion: "" });
            }

            $("#btnAgregarObservacion").show();
        }

    } else {
       
        turnosTemp = turnosTemp.filter(t =>
            !(t.trabajador == trabajador && t.dia == dia && t.mes == mes && t.anio == anio)
        );

      
        if(ultimoCheckMarcado &&
           ultimoCheckMarcado.data("trabajador") == trabajador &&
           ultimoCheckMarcado.data("dia") == dia){
            ultimoCheckMarcado = null;
        }

       
        let quedanNO227 = false;
        $("#tablaHorarioModal input[type='checkbox']:checked").each(function(){
            let trab = $(this).data("trabajador");
            let f = $("#tablaTrabajadores tbody tr[data-trabajador='" + trab + "']");
            if(f.data("meta") != 220) quedanNO227 = true;
        });
        if(!quedanNO227) $("#btnAgregarObservacion").hide();

        console.log("TEMP:", turnosTemp);
    }
});

// ===============================
// CLICK SUGERENCIAS
// ===============================
$(document).on("click", ".sugerencia-item", function(){

    let texto = $(this).text();
    let actual = $("#descripcionTurno").val();

    if(actual.trim() === ""){
        $("#descripcionTurno").val(texto);
    }else{
        $("#descripcionTurno").val(actual + " - " + texto);
    }

});
// ===============================
// CLICK EN "AGREGAR OBSERVACIÓN"
// ===============================

$(document).on("click", "#btnAgregarObservacion", function(){

    if(!ultimoCheckMarcado){
        alert("No hay ningún turno marcado");
        return;
    }

    let trabajador = ultimoCheckMarcado.data("trabajador");
    let dia        = ultimoCheckMarcado.data("dia");
    let anio       = $("#anioModal").val();
    let mes        = $("#mesModal").val();

   
    let turnoTemp = turnosTemp.find(t =>
        t.trabajador == trabajador && t.dia == dia && t.mes == mes && t.anio == anio
    );
    $("#descripcionTurno").val(turnoTemp ? turnoTemp.descripcion : "");

   
    checkboxTemporal = ultimoCheckMarcado;

    $(".modal-backdrop").css("z-index", "1049");
    $("#modalDescripcion").css("z-index", "1700");
    $("#modalDescripcion").modal("show");
});


// ===============================
// GUARDAR DESCRIPCIÓN
// ===============================

$("#guardarDescripcion").click(function(){

    if(!checkboxTemporal) return;

    let descripcion = $("#descripcionTurno").val().trim();
    let trabajador  = checkboxTemporal.data("trabajador");
    let dia         = checkboxTemporal.data("dia");
    let anio        = $("#anioModal").val();
    let mes         = $("#mesModal").val();
    let marcacion   = $("#marcacion").val();

    let index = turnosTemp.findIndex(t =>
        t.trabajador == trabajador && t.dia == dia && t.mes == mes && t.anio == anio
    );
    if(index !== -1){
        turnosTemp[index].descripcion = descripcion;
    } else {
        turnosTemp.push({ trabajador, dia, mes, anio, marcacion, descripcion });
    }

    checkboxTemporal = null;
    $("#modalDescripcion").modal("hide");
    console.log("TEMP:", turnosTemp);
});
// ===============================
// GUARDAR
// ===============================

$("#guardarHorarioModal").click(function(){

    if(turnosTemp.length === 0){
        alert("No hay datos para guardar");
        return;
    }

    let datos = [];

    turnosTemp.forEach(t => {

        let fila = $("#tablaTrabajadores tbody tr[data-trabajador='"+t.trabajador+"']");

        datos.push({
            trabajador: t.trabajador,
            anio: t.anio,
            mes: t.mes,
            componente: fila.data("componente"),
            meta: fila.data("meta"),
            horario: fila.data("horario") || null,

            fechainicioturno: `${t.anio}-${String(t.mes).padStart(2,'0')}-${String(t.dia).padStart(2,'0')}`,
            fechafinturno: `${t.anio}-${String(t.mes).padStart(2,'0')}-${String(t.dia).padStart(2,'0')}`,

            marcacionturno: t.marcacion,
            descripcion: t.descripcion || ""
        });

    });

    console.log("DATOS A GUARDAR:", datos);

    $("#guardarHorarioModal").prop("disabled", true);

    $.ajax({
        url: "modules/turnos/ajax/guardarHorarios.ajax.php",
        method:"POST",
        data:{datos: JSON.stringify(datos)},
        success:function(respuesta){

           console.log(respuesta);
            let seleccionados = [];
            $("#tablaTrabajadores tbody tr").each(function(){
                if($(this).find(".checkItem").prop("checked")){
                    seleccionados.push({
                        id: $(this).data("trabajador"),
                        componente: $(this).data("componente"),
                        meta: $(this).data("meta"),
                        tipotrabajador: $("#tipotrabajador").val(),
                        anio: $("#anio").val()
                    });
                }
            });

            if(seleccionados.length > 0){
                $.ajax({
                    url: "modules/turnos/ajax/guardarUsuariosSeleccionados.ajax.php",
                    method: "POST",
                    data: { datos: JSON.stringify(seleccionados) },
                    success: function(res){
                        console.log(respuesta)
                        console.log("Usuarios seleccionados guardados");
                    }
                });
            }

                      
                turnosTemp = [];
                $("#guardarHorarioModal").prop("disabled", false);

                 obtenerTurnosActualizados(function(){
                    cargarTablaHorarioModal();

                  
                    turnosBDGlobal.forEach(function(trab){
                        if(trab.turnos && trab.turnos.length > 0){
                            $("#turno-" + trab.id).html('<span class="badge badge-info">Turno asignado</span>');
                        }
                    });

                    let alerta = $(
                        '<div class="alert alert-success alert-dismissible fade show mt-2" role="alert">' +
                        '<strong>Turnos guardados correctamente</strong>' +
                        '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>' +
                        '</div>'
                    );
                    $(".modal-body").prepend(alerta);
                    setTimeout(function(){ alerta.alert("close"); }, 3000);
                });
        },
        error:function(){
            alert("Error al guardar");
            $("#guardarHorarioModal").prop("disabled", false);
        }
    });

});


//traer trabajadores guardados
$("#btnUsuariosSeleccionados").click(function(){

    let componente = $("#componente").val();
    let meta = $("#meta").val();
    let tipotrabajador = $("#tipotrabajador").val();
    let anio = $("#anio").val();

    $.ajax({
       url: "turnos/controladores/trabajadorController.php",
        method:"POST",
        data:{
            accion: "traerSeleccionados",
            componente: componente,
            meta: meta,
            tipotrabajador: tipotrabajador,
            anio: anio
        },
       success:function(res){
    let seleccionados = res; 

    $("#tablaTrabajadores tbody tr").each(function(){
        let id = $(this).data("trabajador");

        if(seleccionados.includes(id)){
            $(this).find(".checkItem").prop("checked", true);
        } else {
            $(this).find(".checkItem").prop("checked", false);
        }
    });
}
    });
});
function agruparTurnosPorTrabajador(data){

    let agrupado = {};

    data.forEach(t => {

        let id = t.Id_Trabajador;

        if(!agrupado[id]){
            agrupado[id] = {
                id: id,
                turnos: []
            };
        }

        agrupado[id].turnos.push({
            Id_Marcacion_Tipo: t.Id_Marcacion_Tipo,
            FechaInicioTurno: t.ProcDias_Fecha_Ini,
            FechaFinTurno: t.ProcDias_Fecha_Fin,
            Descripcion: t.ProcDias_Documento,
            MarcTipo_Descripcion: t.MarcTipo_Descripcion || ""
        });

    });

    return Object.values(agrupado);
}
function obtenerTurnosActualizados(callback){

    let trabajadores = JSON.parse(sessionStorage.getItem("trabajadoresHorario"));
    let ids = trabajadores.map(t => t.id);

    $.ajax({
        url: "modules/turnos/ajax/listarTurnosActualizados.ajax.php",
        method: "POST",
        data: {
            anio: $("#anioModal").val(),
            mes: $("#mesModal").val(),
            trabajadores: ids 
        },
        success: function(res){

            let data = JSON.parse(res);

            console.log("TURNOS ACTUALIZADOS:", data);
            turnosBDGlobal = agruparTurnosPorTrabajador(data);

            if(callback) callback();
        }
    });
}

// ===============================
// cargar turnos mes y dias
// ===============================

function cargarTablaHorarioModal(){

    let trabajadores = JSON.parse(sessionStorage.getItem("trabajadoresHorario"));

    let mes = parseInt($("#mesModal").val());
    let anio = parseInt($("#anioModal").val());

    let diasMes = new Date(anio, mes, 0).getDate();
    let diasSemana = ["Dom","Lun","Mar","Mie","Jue","Vie","Sab"];

let turnosBD = turnosBDGlobal;

    generarLeyenda(turnosBD);

    let thead = '<tr><th>Trabajador</th>';

    for(let d=1; d<=diasMes; d++){
        let date = new Date(anio, mes-1, d);
        thead += `<th>${diasSemana[date.getDay()]}</th>`;
    }

    thead += '</tr><tr><th></th>';

    for(let d=1; d<=diasMes; d++){
        thead += `<th>${d}</th>`;
    }

    thead += '</tr>';

    $("#tablaHorarioModal thead").html(thead);

    let html = '';

    trabajadores.forEach(t=>{

        html += `<tr><td>${t.nombre}</td>`;

      
        let turnosTrab = turnosBD.find(x => x.id == t.id)?.turnos || [];

        for(let d=1; d<=diasMes; d++){

            let color = "";
            let clase = "";

           turnosTrab.forEach(turno => {

    let fi = new Date(turno.FechaInicioTurno.date);
    let ff = new Date(turno.FechaFinTurno.date);
    fi.setHours(0,0,0,0);
    ff.setHours(23,59,59,999);

    let fechaCelda = new Date(anio, mes-1, d);

    // Solo pinta si la celda está dentro del rango Y pertenece al mes/año visible
    let celdaEnMes = fechaCelda.getFullYear() == anio && (fechaCelda.getMonth()+1) == mes;

    if(celdaEnMes && fechaCelda >= fi && fechaCelda <= ff){
        color = coloresMarcacion[turno.Id_Marcacion_Tipo] || "#17a2b8";
        clase = "turno-existente";
    }

});

            html += `
            <td class="${clase}" style="background:${color}">
                <input type="checkbox"
                    data-trabajador="${t.id}"
                    data-dia="${d}">
            </td>`;
        }

        html += '</tr>';

    });

    $("#tablaHorarioModal tbody").html(html);
console.log(turnosBD);

}

$("#btnActualizarModal").click(function(){
    obtenerTurnosActualizados(function(){
        cargarTablaHorarioModal();
    });
});

// ===============================
// AUXILIAR
// ===============================

function crearObjeto(t, inicio, fin){

    let fila = $("#tablaTrabajadores tbody tr[data-trabajador='"+t.trabajador+"']");

    return {
        trabajador: t.trabajador,
        anio: t.anio,
        mes: t.mes,
        componente: fila.data("componente"),
        meta: fila.data("meta"),
        horario: fila.data("horario") || null,
        fechainicioturno: `${t.anio}-${String(t.mes).padStart(2,'0')}-${String(inicio).padStart(2,'0')}`,
        fechafinturno: `${t.anio}-${String(t.mes).padStart(2,'0')}-${String(fin).padStart(2,'0')}`,
        marcacionturno: t.marcacion,
        descripcion: t.descripcion || ""
    };

}
const colores = ["#28a745","#ead595ff","#e65261ff","#58cadbff","#868e96ff","#288df8ff","#CCA0E8","#F54927"];
const coloresMarcacion = {};
for(let i=1; i<=80; i++){
    coloresMarcacion[i] = colores[(i-1) % colores.length];
}
//leyenda
function generarLeyenda(turnosBD){

    let mes = parseInt($("#mesModal").val());
    let anio = parseInt($("#anioModal").val());

    // Filtrar turnos solo del mes seleccionado
    let turnosFiltrados = [];

    turnosBD.forEach(trab => {
        trab.turnos.forEach(t => {
            let fechaTurno = new Date(t.FechaInicioTurno.date);
            if(fechaTurno.getMonth() === mes-1 && fechaTurno.getFullYear() === anio){
                turnosFiltrados.push(t);
            }
        });
    });

    let usados = {};

    turnosFiltrados.forEach(t => {
        usados[t.Id_Marcacion_Tipo] = t.MarcTipo_Descripcion || 'Turno';
    });

    let html = '';
    for(let id in usados){
        html += `<span class="badge" style="background:${coloresMarcacion[id] || '#17a2b8'}; margin-right:5px;">${usados[id]}</span>`;
    }

    $("#leyendaMarcacion").html(html);
}




//generar pdf
$("#btnDescargarReporte").click(function(){

    let mes = $("#mesModal option:selected").text();
    let anio = $("#anioModal").val();

    let original = document.querySelector("#reportePDF");


    let clon = original.cloneNode(true);

   
    clon.querySelectorAll(".tabla-scroll").forEach(el=>{
        el.style.overflow = "visible";
    });

  
    clon.querySelectorAll("th, td").forEach(el=>{
        el.style.position = "static";
        el.style.left = "auto";
    });


    let tabla = clon.querySelector("#tablaHorarioModal");
    tabla.style.width = "max-content";

 
    clon.querySelector("#tituloReporte").innerHTML = `<b>Horario - ${mes} ${anio}</b>`;


    let contenedor = document.getElementById("contenedorPDF");
    contenedor.innerHTML = "";
    contenedor.appendChild(clon);

    html2canvas(clon, {
        scale: 2,
        useCORS: true
    }).then(canvas => {

        const imgData = canvas.toDataURL("image/png");

        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('l', 'mm', 'a4');

        let imgWidth = 297;
        let imgHeight = canvas.height * imgWidth / canvas.width;

        pdf.addImage(imgData, 'PNG', 0, 0, imgWidth, imgHeight);

        pdf.save(`Horario_${mes}_${anio}.pdf`);

        // limpiar
        contenedor.innerHTML = "";
    });

});

//eliminar turno trabajador 
$(document).on("click", ".btnEliminarTurno", function(){

    let fila = $(this).closest("tr");

    let componente = fila.data("componente");
    let meta = fila.data("meta");
    let anio = fila.data("anio");
    let trabajador = fila.data("trabajador");

    if(!confirm("¿Eliminar turno de este trabajador?")){
        return;
    }

    $.ajax({
        url: "turnos/controladores/trabajadorController.php",
        method: "POST",
        data: {
            accion: "eliminarTurno",
            componente: componente,
            meta: meta,
            anio: anio,
            trabajador: trabajador
        },
        success:function(res){

            try{
                let r = JSON.parse(res);

                if(r.status === "ok"){

                    alert("Turno eliminado");

                  
                    fila.find(".turnoAsignado").html('<span class="badge badge-light">Sin turno</span>');
                    // fila.find(".horarioSeleccionado").html('<span class="badge badge-light">Sin horario</span>');

                }else{
                    alert("Error al eliminar");
                    console.error(r.detalle);
                }

            }catch(e){
                console.error("Error:", res);
                alert("Error inesperado");
            }

        }
    });

});
</script>
</div> 
    </div> 
  </div> 
</div> 

<div id="contenedorPDF" style="position:absolute; left:-9999px; top:0; background:white;"></div>
</body>

</html>
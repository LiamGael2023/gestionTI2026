<?php

require_once __DIR__ . '/../models/trabajador.php';
require_once __DIR__ . '/../controllers/trabajadorController.php';

$anio = $_GET["anio"] ?? '';
$componente = $_GET["componente"] ?? '';
$meta = $_GET["meta"] ?? '';
$tipotrabajador = $_GET["tipotrabajador"] ?? '';

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

<body>

<div class="container mt-4">

<h2><i class="fa fa-users"></i> Trabajadores</h2>

<form method="GET">
    
    <div class="barra-superior d-flex flex-wrap gap-3 mb-3">

        <div class="filtro-item">
            <label>Año</label>
            <select name="anio" id="anio" class="form-control">
                <?php for($i = date("Y")-7; $i <= date("Y")+2; $i++): ?>
                    <option value="<?= $i ?>" <?= ($anio == $i) ? 'selected' : '' ?>><?= $i ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="filtro-item">
            <label>Componente</label>
            <select name="componente" id="componente" class="form-control">
                <option value="">Todos</option>
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
            <select name="meta" id="meta" class="form-control">
                <option value="">Todos</option>
                <?php
                $metas = TrabajadorController::ctrMostrarMetas($anio, $componente);
                foreach ($metas as $m) {
                    echo '<option value="'.$m["Id_Meta"].'" '.($meta == $m["Id_Meta"] ? 'selected' : '').'>'.$m["Meta_Descripcion"].'</option>';
                }
                ?>
            </select>
        </div>

        <div class="filtro-item">
            <label>Tipo Trabajador </label>
            <select name="tipotrabajador" id="tipotrabajador" class="form-control">
                <option value="">Todos</option>
                <?php
                $tipotrabajadores = TrabajadorController::ctrMostrarTipoTrabajador();
                foreach ($tipotrabajadores as $tt) {
                    echo '<option value="'.$tt["Id_Trabajador_Tipo"].'" '.($tipotrabajador == $tt["Id_Trabajador_Tipo"] ? 'selected' : '').'>'.$tt["TrabTipo_Descripcion"].'</option>';
                }
                ?>
            </select>
        </div>

        <div class="filtro-item d-flex align-items-end gap-2">
            <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Buscar</button>
            <button type="button" id="btnAgregarHorario" class="btn btn-success"><i class="fa fa-calendar"></i> Agregar Turno</button>
        <button type="button" id="btnUsuariosSeleccionados" class="btn btn-success"><i class="fa fa-calendar"></i> Usuarios para turno</button>
       
        </div>

    </div>
</form>

<table id="tablaTrabajadores" class="display table table-bordered">
    <thead>
    <tr>
        <th><input type="checkbox" id="checkAll"></th>
        <th>Nombre del trabajador</th>
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
                <span class="badge badge-info"><?= $row['Turno'] ?></span>
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
    <input type="number" id="anioModal" value="<?= date("Y") ?>" class="form-control form-control-sm d-inline w-auto" min="2000" max="<?= date("Y")+5 ?>">

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
        <button class="btn btn-success" id="guardarHorarioModal">Guardar</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
<script>

let turnosTemp = [];

$(document).ready(function(){

    // DataTable
    $('#tablaTrabajadores').DataTable({
        pageLength:10,
        language:{ url:"//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" }
    });

    // Check all
    $('#checkAll').click(function(){
        $('.checkItem').prop('checked', $(this).prop('checked'));
    });

});


// ===============================
// CAMBIO DE FILTROS
// ===============================

$("#anio").change(function(){
    var anio = $(this).val();

    $.ajax({
        url:"ajax/metas.php",
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
        url:"ajax/metas.php",
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

    $("#tablaTrabajadores tbody tr").each(function(){

        let check = $(this).find(".checkItem").prop("checked");

        if(check){
            trabajadores.push({
                id: $(this).data("trabajador"),
                nombre: $(this).find("td:eq(1)").text(),
                horario: $(this).data("horario"),
                fechainicio: $(this).data("fechainicio"),
                fechafin: $(this).data("fechafin")
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

    $("#modalHorarioMes").modal("show");

    cargarTablaHorarioModal();

});


// ===============================
// CHECKBOX → GUARDADO TEMPORAL
// ===============================

$(document).on("change", "#tablaHorarioModal input[type='checkbox']", function(){

    let checked = $(this).prop("checked");

    let trabajador = $(this).data("trabajador");
    let dia = $(this).data("dia");

    let anio = $("#anioModal").val();
    let mes = $("#mesModal").val();
    let marcacion = $("#marcacion").val();

    if(checked){

        turnosTemp.push({
            trabajador: trabajador,
            dia: dia,
            mes: mes,
            anio: anio,
            marcacion: marcacion
        });

    }else{

        turnosTemp = turnosTemp.filter(t => 
            !(t.trabajador == trabajador && t.dia == dia)
        );

    }

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
    let agrupados = {};

 
    turnosTemp.forEach(t => {
        let key = `${t.trabajador}-${t.marcacion}`;
        if(!agrupados[key]) agrupados[key] = [];
        agrupados[key].push(t);
    });

 
    Object.values(agrupados).forEach(lista => {
        lista.sort((a,b)=>a.dia-b.dia);

        let inicio = lista[0].dia;
        let fin = lista[0].dia;

        for(let i=1;i<lista.length;i++){
            if(lista[i].dia === fin + 1){
                fin = lista[i].dia;
            } else {
                datos.push(crearObjeto(lista[i-1], inicio, fin));
                inicio = lista[i].dia;
                fin = lista[i].dia;
            }
        }

        datos.push(crearObjeto(lista[0], inicio, fin));
    });

    $("#guardarHorarioModal").prop("disabled", true);

    $.ajax({
        url:"../ajax/guardarHorarios.ajax.php",
        method:"POST",
        data:{datos: JSON.stringify(datos)},
        success:function(respuesta){

           
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
                    url: "../ajax/guardarUsuariosSeleccionados.ajax.php",
                    method: "POST",
                    data: { datos: JSON.stringify(seleccionados) },
                    success: function(res){
                        console.log("Usuarios seleccionados guardados");
                    }
                });
            }

            alert("Turnos guardados correctamente");
            $("#modalHorarioMes").modal("hide");
            location.reload();

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
        url:"../controladores/trabajadorController.php",
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


// ===============================
// TABLA MODAL
// ===============================

function cargarTablaHorarioModal(){

    let trabajadores = JSON.parse(sessionStorage.getItem("trabajadoresHorario"));

    let mes = parseInt($("#mesModal").val());
    let anio = parseInt($("#anioModal").val());

    let diasMes = new Date(anio, mes, 0).getDate();
    let diasSemana = ["Dom","Lun","Mar","Mie","Jue","Vie","Sab"];

    let turnosBD = <?php echo json_encode($trabajadoresJS); ?>;
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

                let inicio = new Date(turno.FechaInicioTurno.date).getDate();
                let fin = new Date(turno.FechaFinTurno.date).getDate();

                if(d >= inicio && d <= fin){

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
console.log(turnosTrab);
}

// ===============================
// ACTUALIZAR MES/AÑO
// ===============================

$("#btnActualizarModal").click(function(){
    cargarTablaHorarioModal();
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
        marcacionturno: t.marcacion
    };

}
const colores = ["#28a745","#ead595ff","#e65261ff","#58cadbff","#868e96ff","#288df8ff","#CCA0E8","#F54927"];
const coloresMarcacion = {};
for(let i=1; i<=80; i++){
    coloresMarcacion[i] = colores[(i-1) % colores.length];
}
//leyenda
function generarLeyenda(turnosBD){

    let usados = {};

    turnosBD.forEach(trab => {

        trab.turnos.forEach(t => {

            let tipo = t.Id_Marcacion_Tipo;
            let nombre = t.MarcTipo_Descripcion || ("Tipo " + tipo);

            usados[tipo] = nombre;

        });

    });

    let html = '';

    Object.keys(usados).forEach(tipo => {

        let color = coloresMarcacion[tipo] || "#17a2b8";

        html += `
        <span style="
            display:inline-block;
            margin-right:10px;
            padding:5px 10px;
            background:${color};
            color:#fff;
            border-radius:5px;
            font-size:12px;
        ">
            ${usados[tipo]}
        </span>`;
    });

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
        url: "../controladores/trabajadorController.php",
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
<div id="contenedorPDF" style="position:absolute; left:-9999px; top:0; background:white;"></div>
</body>
</html>
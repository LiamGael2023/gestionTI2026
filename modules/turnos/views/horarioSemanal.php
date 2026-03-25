<?php
$mes = $_GET["mes"] ?? date("m");
$anio = $_GET["anio"] ?? date("Y");

$primerDia = strtotime("$anio-$mes-01");
$diasMes = date("t", $primerDia);

$diasSemana = ["Dom","Lun","Mar","Mie","Jue","Vie","Sab"];
$meses = [
    1=>"Enero",2=>"Febrero",3=>"Marzo",4=>"Abril",5=>"Mayo",6=>"Junio",
    7=>"Julio",8=>"Agosto",9=>"Septiembre",10=>"Octubre",11=>"Noviembre",12=>"Diciembre"
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Horario mensual</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
<style>
body{background:#f5f6fa;}
h3{margin-bottom:20px;}

/* Contenedor scroll */
.tabla-scroll{
    overflow-x:auto;
    border:1px solid #ddd;
    background:white;
}

/* Tabla */
#tablaHorario{
    font-size:12px;
    text-align:center;
    min-width:900px; /* ancho visible para 15 días */
}

/* Encabezado */
#tablaHorario th{
    background:#2c3e50;
    color:white;
    white-space:nowrap;
}

/* Checkboxes */
#tablaHorario td input{
    transform:scale(1.2);
    cursor:pointer;
}

/* Columna trabajador fija */
#tablaHorario th:first-child,
#tablaHorario td:first-child{
    position:sticky;
    left:0;
    background:white;
    z-index:2;
    font-weight:bold;
}

#tablaHorario thead th:first-child{
    background:#2c3e50;
    z-index:3;
}

#guardar{
    margin-top:15px;
}

/* Contenedor de filtros */
.filtros{
    margin-bottom:15px;
}
</style>
</head>
<body>

<div class="container mt-4">
<h3>Horario del Mes</h3>

<div class="filtros">
<form method="GET" id="formFiltro">
    <label>Mes:</label>
    <select name="mes" id="mes">
        <?php foreach($meses as $num=>$nombre): ?>
        <option value="<?= $num ?>" <?= ($mes==$num)?'selected':'' ?>><?= $nombre ?></option>
        <?php endforeach; ?>
    </select>
    <label>Año:</label>
    <input type="number" name="anio" value="<?= $anio ?>" min="2000" max="<?= date("Y")+5 ?>">
    <button type="submit" class="btn btn-primary btn-sm">Actualizar</button>
</form>
</div>

<div class="tabla-scroll">
<table class="table table-bordered" id="tablaHorario">
<thead>
<tr>
<th rowspan="2">Trabajador</th>
<?php
for($d=1; $d<=$diasMes; $d++){
    $fecha = "$anio-$mes-$d";
    $diaSemana = date("w", strtotime($fecha));
    echo "<th>".$diasSemana[$diaSemana]."</th>";
}
?>
</tr>
<tr>
<?php
for($d=1; $d<=$diasMes; $d++){
    echo "<th>".$d."</th>";
}
?>
</tr>
</thead>
<tbody></tbody>
</table>
</div>

<button class="btn btn-success" id="guardar">Guardar</button>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
let trabajadores = JSON.parse(sessionStorage.getItem("trabajadoresHorario"));
let diasMes = <?= $diasMes ?>;
let html = "";

if(trabajadores){
    trabajadores.forEach(function(t){
        html += `<tr>`;
        html += `<td>${t.nombre}</td>`;
        for(let i=1;i<=diasMes;i++){
            html += `<td><input type="checkbox" data-trabajador="${t.id}" data-dia="${i}"></td>`;
        }
        html += `</tr>`;
    });
}

$("#tablaHorario tbody").html(html);

/* Limitar scroll: mostrar 15 días */
$("#tablaHorario th, #tablaHorario td").each(function(index){
    if(index>15) $(this).css("min-width","80px"); // opcional ancho fijo para scroll
});

/* Guardar datos */
$("#guardar").click(function(){
    let datos = [];
    $("#tablaHorario input:checked").each(function(){
        datos.push({
            trabajador: $(this).data("trabajador"),
            dia: $(this).data("dia")
        });
    });
    console.log(datos);
    // Aquí iría tu AJAX para guardar
});
</script>

</body>
</html>
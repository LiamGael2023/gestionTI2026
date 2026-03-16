<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<script src="https://cdn.jsdelivr.net/npm/qrious@4.0.2/dist/qrious.min.js"></script>

<style>
.qr-container {
    display:inline-flex;
    flex-direction:column;
    align-items:center;
    padding:8px;
    background:white;
    border-radius:8px;
    box-shadow:0 1px 4px rgba(0,0,0,.12);
    max-width:100%;
}

.qr-box{
    width:150px;
    height:150px;
    overflow:hidden;
    display:flex;
    align-items:center;
    justify-content:center;
}

.qr-box canvas{
    max-width:100%!important;
    max-height:100%!important;
    display:block;
}

@media (max-width:576px){

.qr-container{width:100%}

.qr-box{
width:200px;
height:200px
}

#papeletaAvatar{
width:100%!important;
height:auto!important;
padding-top:100%;
background-size:cover!important;
background-position:center!important;
}

}
</style>

<div class="modal fade" id="modalQR" tabindex="-1">
<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
<div class="modal-content rounded-4 shadow">

<div class="modal-header">
<h5 class="modal-title fw-bold">Detalle de Papeleta</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body bg-light">

<div class="card border-0 shadow-sm">
<div class="card-body">

<div class="row g-4 align-items-center">

<div class="col-12 col-sm-3 text-center">
<div id="papeletaAvatar" class="avatar rounded border"
style="width:150px;height:150px;background-size:cover;background-position:center;margin:auto;">
</div>
</div>

<div class="col-12 col-sm-6 text-center text-sm-start">

<h4 class="fw-bold" id="papeletaNombres"></h4>

<div class="row g-2 mt-2 small text-muted">

<div class="col-md-6">
<div class="d-flex align-items-start gap-2">
<img src="vistas/img/iconos/papeleta/gerencia.svg" width="18">
<div>
<strong>Gerencia:</strong><br>
<span id="papeletaSubgerencia"></span>
</div>
</div>
</div>

<div class="col-md-6">
<div class="d-flex align-items-start gap-2">
<img src="vistas/img/iconos/papeleta/oficina.svg" width="18">
<div>
<strong>Oficina:</strong><br>
<span id="papeletaOficina"></span>
</div>
</div>
</div>

</div>
</div>

<div class="col-12 col-sm-3 text-center">

<div class="qr-container">

<div class="qr-box" id="qrBox">
<canvas id="qrCanvas"></canvas>
</div>

<div class="mt-2">
<span id="papeletaID" style="color:black;font-weight:bold;"></span>
</div>

</div>

</div>
</div>

<hr class="my-4">

<div class="small">

<div class="mb-3 d-flex align-items-start gap-2">
<img src="vistas/img/iconos/papeleta/concepto.svg" width="18">
<div>
<strong>Concepto/Motivo:</strong>
<p class="mb-0" id="papeletaConceptoMotivo"></p>
</div>
</div>

<div class="mb-3 d-flex align-items-start gap-2">
<img src="vistas/img/iconos/papeleta/lugar.svg" width="18">
<div>
<strong>Lugar:</strong>
<p class="mb-0" id="papeletaLugar"></p>
</div>
</div>

<div class="row g-4">

<div class="col-sm-6">

<div class="d-flex align-items-start gap-2">
<img src="vistas/img/iconos/papeleta/fecha.svg" width="18">

<div>
<strong>Fechas</strong>

<div class="d-flex flex-column flex-md-row gap-3 mt-1">

<div>
<small class="text-muted">Inicio</small>
<p class="mb-0" id="papeletaFechaInicio"></p>
</div>

<div>
<small class="text-muted">Fin</small>
<p class="mb-0" id="papeletaFechaFin"></p>
</div>

</div>

</div>

</div>

</div>

<div class="col-sm-6">

<div class="d-flex align-items-start gap-2">
<img src="vistas/img/iconos/papeleta/hora.svg" width="18">

<div>

<strong>Horas</strong>

<div class="d-flex flex-column flex-md-row gap-3 mt-1">

<div>
<small class="text-muted">Inicio</small>
<p class="mb-0" id="papeletaHoraInicio"></p>
</div>

<div>
<small class="text-muted">Fin</small>
<p class="mb-0" id="papeletaHoraFin"></p>
</div>

</div>

</div>

</div>

</div>

</div>

</div>

<hr id="rowVehiculo" class="my-4 d-none">

<div class="row g-4 small d-none" id="vehiculoInfo">

<div class="col-sm-4">
<strong>Placa</strong>
<p id="papeletaPlaca"></p>
</div>

<div class="col-sm-4">
<strong>Kilom Inicial</strong>
<p id="papeletaKMInicial"></p>
</div>

<div class="col-sm-4">
<strong>Kilom Final</strong>
<p id="papeletaKMFinal"></p>
</div>

</div>

</div>
</div>
</div>
</div>

<div class="modal-footer">
<button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
</div>

</div>
</div>
</div>

<script>

/* FUNCION PARA ABRIR DESDE SCANNER */

function abrirModalPapeleta(id){

var modal=$("#modalQR")

modal.data("qr-id",id)

$("#papeletaID").text(id)

modal.modal("show")

}

$(document).ready(function(){

var canvasEl=document.getElementById("qrCanvas")
var qrBox=document.getElementById("qrBox")

function getQrSize(){

var boxW=qrBox?qrBox.offsetWidth:0

if(boxW>20) return boxW

return window.innerWidth<=576?200:150

}

var qr=new QRious({

element:canvasEl,
size:getQrSize(),
value:"INIT"

})

var modal=$("#modalQR")

modal.on("shown.bs.modal",function(){

var s=getQrSize()

if(qr.size!==s){

qr.size=s

}

})

modal.on("show.bs.modal",function(event){

var id=$(event.relatedTarget).data("id")||$(this).data("qr-id")

if(!id) return

var idStr=String(id)

qr.value=idStr

$("#papeletaID").text(idStr)

$.ajax({

url:"modules/papeletas/ajax/papeleta.ajax.php",
type:"POST",
data:{accion:"mostrar_detalle",id_papeleta:id},
dataType:"json",

success:function(resp){

if(resp.status!=="success") return

var d=resp.data

function fmtFecha(f){

if(!f) return ""

var dt=new Date(f)

return isNaN(dt)?f:dt.toLocaleDateString("es-ES")

}

function fmtHora(h){

if(!h) return ""

var dt=new Date(h)

return isNaN(dt)?h:dt.toLocaleTimeString("es-ES",{hour:"2-digit",minute:"2-digit"})

}

$("#papeletaNombres").text(d.nombres)
$("#papeletaSubgerencia").text(d.gerencia)
$("#papeletaOficina").text(d.oficina)

$("#papeletaFechaInicio").text(fmtFecha(d.fecha_inicio))
$("#papeletaFechaFin").text(fmtFecha(d.fecha_fin))

$("#papeletaHoraInicio").text(fmtHora(d.hora_salida))
$("#papeletaHoraFin").text(fmtHora(d.hora_llegada))

$("#papeletaConceptoMotivo").html((d.Id_Trabajador_Concepto_APP||"")+"<br>"+(d.Id_Trabajador_Motivo_APP||""))

$("#papeletaLugar").text(d.Id_Trabajador_Lugar_APP||"")

var urlFoto="/gestionti/public/fotos-trabajador/"+d.Trab_Fotocheck+".jpg"

$("#papeletaAvatar").css("background-image","url('"+urlFoto+"')")

var esVeh=parseInt(d.es_salida_vehicular)===1

$("#rowVehiculo").toggleClass("d-none",!esVeh)
$("#vehiculoInfo").toggleClass("d-none",!esVeh)

if(esVeh){

$("#papeletaPlaca").text(d.placa||"")
$("#papeletaKMInicial").text(d.kilometraje_inicial||"")
$("#papeletaKMFinal").text(d.kilometraje_final||"")

}

}

})

})

modal.on("hidden.bs.modal",function(){

qr.value=""

$("#modalQR span,#modalQR p").text("")

$("#papeletaConceptoMotivo").html("")

$("#papeletaAvatar").css("background-image","")

})

})
</script>

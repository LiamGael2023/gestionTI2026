
$("#guardarHorarioModal").click(function () {

    if (turnosTemp.length === 0) {
        alert("No hay datos para guardar");
        return;
    }

    const datos = turnosTemp.map(t => {
        const fila = $("#tablaTrabajadores tbody tr[data-trabajador='" + t.trabajador + "']");
        return {
            trabajador      : t.trabajador,
            anio            : t.anio,
            mes             : t.mes,
            componente      : fila.data("componente"),
            meta            : fila.data("meta"),
            horario         : fila.data("horario") || null,
            fechainicioturno: `${t.anio}-${String(t.mes).padStart(2,'0')}-${String(t.dia).padStart(2,'0')}`,
            fechafinturno   : `${t.anio}-${String(t.mes).padStart(2,'0')}-${String(t.dia).padStart(2,'0')}`,
            marcacionturno  : t.marcacion,
            descripcion     : t.descripcion || ""
        };
    });

    $("#guardarHorarioModal").prop("disabled", true);

    $.ajax({
        url   : "modules/turnos/ajax/guardarHorarios.ajax.php",
        method: "POST",
        data  : { datos: JSON.stringify(datos) },
        success(respuesta) {
            console.log(respuesta);

            // Guardar usuarios seleccionados
            const seleccionados = [];
            $("#tablaTrabajadores tbody tr").each(function () {
                if ($(this).find(".checkItem").prop("checked")) {
                    seleccionados.push({
                        id           : $(this).data("trabajador"),
                        componente   : $(this).data("componente"),
                        meta         : $(this).data("meta"),
                        tipotrabajador: $(this).data("tipotrabajador"),
                        anio         : $("#anio").val()
                    });
                }
            });

            if (seleccionados.length > 0) {
                $.ajax({
                    url   : "modules/turnos/ajax/guardarUsuariosSeleccionados.ajax.php",
                    method: "POST",
                    data  : { datos: JSON.stringify(seleccionados) },
                    success() { console.log("Usuarios seleccionados guardados"); }
                });
            }

            turnosTemp = [];
            $("#guardarHorarioModal").prop("disabled", false);

            obtenerTurnosActualizados(function () {
                cargarTablaHorarioModal();

                turnosBDGlobal.forEach(trab => {
                    if (trab.turnos && trab.turnos.length > 0) {
                        $("#turno-" + trab.id).html('<span class="badge badge-info">Turno asignado</span>');
                    }
                });

                const alerta = $(
                    '<div class="alert alert-success alert-dismissible fade show mt-2" role="alert">' +
                    '<strong>Turnos guardados correctamente</strong>' +
                    '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>' +
                    '</div>'
                );
                $(".modal-body").prepend(alerta);
                setTimeout(() => alerta.alert("close"), 3000);
            });
        },
        error() {
            alert("Error al guardar");
            $("#guardarHorarioModal").prop("disabled", false);
        }
    });
});


// ── Eliminar turno ────────────────────────────────────────
$(document).on("click", ".btnEliminarTurno", function () {

    const fila = $(this).closest("tr");

    if (!confirm("¿Eliminar turno de este trabajador?")) return;

    $.ajax({
        url   : "modules/turnos/ajax/eliminarTurno.ajax.php",
        method: "POST",
        data  : {
            accion    : "eliminarTurno",
            componente: fila.data("componente"),
            meta      : fila.data("meta"),
            anio      : fila.data("anio"),
            trabajador: fila.data("trabajador")
        },
        success(res) {
            try {
                const r = JSON.parse(res);
                if (r.status === "ok") {
                    alert("Turno eliminado");
                    fila.find(".turnoAsignado").html('<span class="badge badge-light">Sin turno</span>');
                } else {
                    alert("Error al eliminar");
                    console.error(r.detalle);
                }
            } catch (e) {
                console.error("Error:", res);
                alert("Error inesperado");
            }
        }
    });
});


// ── Descargar Excel ───────────────────────────────────────
$("#btnDescargarExcel").click(function () {
    const mes = $("#mesModal").val();
    const anio = $("#anioModal").val();
    const datos = {
        trabajadores: JSON.parse(sessionStorage.getItem("trabajadoresHorario")) || [],
        turnos: turnosBDGlobal,
        mes: mes,
        anio: anio
    };

    const mesTexto = $("#mesModal option:selected").text().toLowerCase();

    const nombreArchivo = `turnos_${mesTexto}_${anio}.xlsx`;

    _descargarArchivo(
        "modules/turnos/reportes/ajax/reporte.ajax.php",
        datos,
        nombreArchivo
    );
});


// ── Descargar PDF ─────────────────────────────────────────
$("#btnDescargarPDF").click(function () {
const mes = $("#mesModal").val();
    const anio = $("#anioModal").val();
    const datos = {
        trabajadores: JSON.parse(sessionStorage.getItem("trabajadoresHorario")) || [],
        turnos: turnosBDGlobal,
       mes: mes,
        anio: anio
    };
const mesTexto = $("#mesModal option:selected").text().toLowerCase();

    const nombreArchivo = `turnos_${mesTexto}_${anio}.pdf`;

    _descargarArchivo(
        "modules/turnos/reportes/ajax/reportePdf.ajax.php",
        datos,
        nombreArchivo
  );
});



function _descargarArchivo(url, datos, nombreArchivo) {
    fetch(url, {
        method : "POST",
        headers: { "Content-Type": "application/json" },
        body   : JSON.stringify(datos)
    })
    .then(res => {
        if (!res.ok) return res.text().then(t => { throw new Error(t); });
        return res.blob();
    })
    .then(blob => {
        if (blob.size < 1000) {
            blob.text().then(t => console.error("Respuesta sospechosa:", t));
            alert("Error al generar el archivo. Revisa la consola.");
            return;
        }
        const a = document.createElement("a");
        a.href     = window.URL.createObjectURL(blob);
        a.download = nombreArchivo;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(a.href);
    })
    .catch(err => {
        console.error("Error capturado:", err.message);
        alert("Error: " + err.message);
    });
}



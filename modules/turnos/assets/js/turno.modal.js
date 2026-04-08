
// ── Sugerencias ───────────────────────────────────────────
function cargarSugerenciasObservacion() {
    let html = '';
    sugerenciasMeta220.forEach(texto => {
        html += `<button type="button" class="btn btn-sm btn-outline-primary m-1 sugerencia-item">${texto}</button>`;
    });
    $("#sugerenciasObservacion").html(html);
}

$(document).on("click", ".sugerencia-item", function () {
    const texto  = $(this).text().trim();
    const actual = $("#descripcionTurno").val();
    $("#descripcionTurno").val(actual.trim() === "" ? texto : actual + " - " + texto);
});



$(document).on("change", "#tablaHorarioModal input[type='checkbox']", function () {

    const checked   = $(this).prop("checked");
    const trabajador = $(this).data("trabajador");
    const dia        = $(this).data("dia");
    const anio       = $("#anioModal").val();
    const mes        = $("#mesModal").val();
    const marcacion  = $("#marcacion").val();

    const fila = $("#tablaTrabajadores tbody tr[data-trabajador='" + trabajador + "']");
    const meta = fila.data("meta");

    if (checked) {

        if (meta == 220) {
            
            checkboxTemporal = $(this);

            let descripcion = "";
            const turnoTemp = turnosTemp.find(t =>
                t.trabajador == trabajador && t.dia == dia && t.mes == mes && t.anio == anio
            );
            if (turnoTemp) {
                descripcion = turnoTemp.descripcion;
            } else {
                const tBD = turnosBDGlobal.find(t => t.id == trabajador);
                if (tBD) {
                    tBD.turnos.forEach(turno => {
                        const fi = new Date(turno.FechaInicioTurno.date);
                        const ff = new Date(turno.FechaFinTurno.date);
                        if (new Date(anio, mes - 1, dia) >= fi && new Date(anio, mes - 1, dia) <= ff) {
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

            const index = turnosTemp.findIndex(t =>
                t.trabajador == trabajador && t.dia == dia && t.mes == mes && t.anio == anio
            );

            if (index !== -1) {
                turnosTemp[index].marcacion = marcacion;
            } else {
                let descripcionBD = "";
                const tBD = turnosBDGlobal.find(t => t.id == trabajador);
                if (tBD) {
                    tBD.turnos.forEach(turno => {
                        const fi = new Date(turno.FechaInicioTurno.date);
                        const ff = new Date(turno.FechaFinTurno.date);
                        fi.setHours(0, 0, 0, 0);
                        ff.setHours(23, 59, 59, 999);
                        if (new Date(anio, mes - 1, dia) >= fi && new Date(anio, mes - 1, dia) <= ff) {
                            descripcionBD = turno.Descripcion || "";
                        }
                    });
                }
                turnosTemp.push({ trabajador, dia, mes, anio, marcacion, descripcion: descripcionBD });
            }

            $("#btnAgregarObservacion").show();
        }

    } else {
        
        turnosTemp = turnosTemp.filter(t =>
            !(t.trabajador == trabajador && t.dia == dia && t.mes == mes && t.anio == anio)
        );

        if (ultimoCheckMarcado &&
            ultimoCheckMarcado.data("trabajador") == trabajador &&
            ultimoCheckMarcado.data("dia") == dia) {
            ultimoCheckMarcado = null;
        }

      
        let quedanNoMeta220 = false;
        $("#tablaHorarioModal input[type='checkbox']:checked").each(function () {
            const trab = $(this).data("trabajador");
            const f = $("#tablaTrabajadores tbody tr[data-trabajador='" + trab + "']");
            if (f.data("meta") != 220) quedanNoMeta220 = true;
        });
        if (!quedanNoMeta220) $("#btnAgregarObservacion").hide();
    }
});


//  "Agregar Observación" ───────────────────────────
$(document).on("click", "#btnAgregarObservacion", function () {

    if (!ultimoCheckMarcado) {
        alert("No hay ningún turno marcado");
        return;
    }

    const trabajador = ultimoCheckMarcado.data("trabajador");
    const dia        = ultimoCheckMarcado.data("dia");
    const anio       = $("#anioModal").val();
    const mes        = $("#mesModal").val();

    const turnoTemp = turnosTemp.find(t =>
        t.trabajador == trabajador && t.dia == dia && t.mes == mes && t.anio == anio
    );
    $("#descripcionTurno").val(turnoTemp ? turnoTemp.descripcion : "");

    checkboxTemporal = ultimoCheckMarcado;
    $(".modal-backdrop").css("z-index", "1049");
    $("#modalDescripcion").css("z-index", "1700");
    $("#modalDescripcion").modal("show");
});


//  Guardar descripción del modal ─────────────────────────
$("#guardarDescripcion").click(function () {

    if (!checkboxTemporal) return;

    const descripcion = $("#descripcionTurno").val().trim();
    const trabajador  = checkboxTemporal.data("trabajador");
    const dia         = checkboxTemporal.data("dia");
    const anio        = $("#anioModal").val();
    const mes         = $("#mesModal").val();
    const marcacion   = $("#marcacion").val();

    const index = turnosTemp.findIndex(t =>
        t.trabajador == trabajador && t.dia == dia && t.mes == mes && t.anio == anio
    );

    if (index !== -1) {
        turnosTemp[index].descripcion = descripcion;
    } else {
        turnosTemp.push({ trabajador, dia, mes, anio, marcacion, descripcion });
    }

    checkboxTemporal = null;
    $("#modalDescripcion").modal("hide");
});



$("#modalDescripcion").on("hidden.bs.modal", function () {
    if ($("#modalHorarioMes").hasClass("show")) {
        $("body").addClass("modal-open");
        $(".modal-backdrop").css("z-index", "");
    }
});
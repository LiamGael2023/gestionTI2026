

$(document).ready(function () {

    // ── DataTable ─────────────────────────────────────────
    $('#tablaTrabajadores').DataTable({
        pageLength: 10,
        language: { url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" }
    });

    // Marcar checks al repintar páginas del DataTable
    $('#tablaTrabajadores').on('draw.dt', function () {
        marcarChecksVisibles();
    });


    // ── Check All ─────────────────────────────────────────
    $('#checkAll').on('click', function () {
        const checked = this.checked;
        const tabla   = $('#tablaTrabajadores').DataTable();

        tabla.rows({ search: 'applied' }).every(function () {
            const fila = $(this.node());
            const id   = fila.data('trabajador');
            if (id === undefined) return;

            if (checked) {
                if (!seleccionadosUsuario.includes(id)) seleccionadosUsuario.push(id);
            } else {
                seleccionadosUsuario = seleccionadosUsuario.filter(x => x != id);
            }
        });

        $('input.checkItem', tabla.rows({ search: 'applied' }).nodes()).prop('checked', checked);
    });


    // ── Check individual ──────────────────────────────────
    $(document).on("change", ".checkItem", function () {
        const id = $(this).closest("tr").data("trabajador");
        if (this.checked) {
            if (!seleccionadosUsuario.includes(id)) seleccionadosUsuario.push(id);
        } else {
            seleccionadosUsuario = seleccionadosUsuario.filter(x => x != id);
        }
    });



    $(document).on("click", ".modal .close, .modal [data-dismiss='modal']", function (e) {
        e.stopPropagation();
        const modalId = $(this).closest(".modal").attr("id");
        $("#" + modalId).modal("hide");
    });


    
    $("#anio").change(function () {
        $.ajax({
            url    : "modules/turnos/ajax/metas.php",
            method : "POST",
            data   : { anio: $(this).val() },
            success(respuesta) {
                $("#meta").html('<option value="">Todos</option>' + respuesta);
                $("form").submit();
            }
        });
    });



    $("#componente").change(function () {
        $.ajax({
            url    : "modules/turnos/ajax/metas.php",
            method : "POST",
            data   : { componente: $(this).val() },
            success(respuesta) {
                $("#meta").html('<option value="">Todos</option>' + respuesta);
                $("form").submit();
            }
        });
    });


   
    $("#btnActualizarModal").click(function () {
        obtenerTurnosActualizados(function () {
            cargarTablaHorarioModal();
        });
    });


  
    $("#btnAgregarHorario").click(function () {

        const seleccionTotal = [...new Set([...seleccionadosBD, ...seleccionadosUsuario])];
        const trabajadores   = [];

        seleccionTotal.forEach(id => {
            const t = TRABAJADORES_JS.find(x => x.id == id);
            if (t) {
                trabajadores.push({ id: t.id, nombre: t.nombre, horario: null, fechainicio: null, fechafin: null });
            }
        });

        if (trabajadores.length === 0) {
            alert("Seleccione trabajadores");
            return;
        }

        turnosTemp = [];
        sessionStorage.setItem("trabajadoresHorario", JSON.stringify(trabajadores));
        turnosBDGlobal = TRABAJADORES_JS;

        $("#modalHorarioMes").modal("show");
        cargarTablaHorarioModal();
    });


    
    $("#btnUsuariosSeleccionados").click(function () {
        $.ajax({
            url      : "modules/turnos/ajax/traerUsuariosSeleccionados.ajax.php",
            method   : "POST",
            dataType : "json",
            data     : {
                accion    : "traerSeleccionados",
                componente: $("#componente").val(),
                meta      : $("#meta").val(),
                anio      : $("#anio").val()
            },
            success(res) {
                seleccionadosBD = res;
                marcarChecksVisibles();
            },
            error(xhr) {
                console.log(xhr.responseText);
            }
        });
    });

}); 


function marcarChecksVisibles() {
    $("#tablaTrabajadores tbody tr").each(function () {
        const id = $(this).data("trabajador");
        const marcado = seleccionadosBD.includes(id) || seleccionadosUsuario.includes(id);
        $(this).find(".checkItem").prop("checked", marcado);
    });
}
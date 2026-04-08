

function cargarTablaHorarioModal() {

    const trabajadores = JSON.parse(sessionStorage.getItem("trabajadoresHorario"));
    const mes     = parseInt($("#mesModal").val());
    const anio    = parseInt($("#anioModal").val());
    const diasMes = new Date(anio, mes, 0).getDate();
    const dias    = ["Dom", "Lun", "Mar", "Mie", "Jue", "Vie", "Sab"];

    generarLeyenda(turnosBDGlobal);

    
    let thead = '<tr><th>Trabajador</th>';
    for (let d = 1; d <= diasMes; d++) {
        thead += `<th>${dias[new Date(anio, mes - 1, d).getDay()]}</th>`;
    }
    thead += '</tr><tr><th></th>';
    for (let d = 1; d <= diasMes; d++) {
        thead += `<th>${d}</th>`;
    }
    thead += '</tr>';
    $("#tablaHorarioModal thead").html(thead);

  
    let html = '';

    trabajadores.forEach(t => {

        html += `<tr><td>${t.nombre}</td>`;

        const turnosTrab = (turnosBDGlobal.find(x => x.id == t.id)?.turnos || [])
            .filter(turno => MARCACIONES_VALIDAS.includes(parseInt(turno.Id_Marcacion_Tipo)));

        for (let d = 1; d <= diasMes; d++) {

            let bgColor  = "";
            let clase    = "";

            turnosTrab.forEach(turno => {
                const fi = new Date(turno.FechaInicioTurno.date);
                const ff = new Date(turno.FechaFinTurno.date);
                fi.setHours(0, 0, 0, 0);
                ff.setHours(23, 59, 59, 999);
                const fechaCelda = new Date(anio, mes - 1, d);
                const celdaEnMes = fechaCelda.getFullYear() === anio &&
                                   (fechaCelda.getMonth() + 1) === mes;

                if (celdaEnMes && fechaCelda >= fi && fechaCelda <= ff) {
                    const estilo = coloresMarcacion[parseInt(turno.Id_Marcacion_Tipo)];
                    if (estilo) {
                        bgColor = estilo.bg;
                        clase   = "turno-existente";
                    }
                }
            });

            html += `
            <td class="${clase}" style="background:${bgColor}; text-align:center;">
                <input type="checkbox" data-trabajador="${t.id}" data-dia="${d}">
            </td>`;
        }

        html += '</tr>';
    });

    $("#tablaHorarioModal tbody").html(html);
}


function generarLeyenda(turnosBD) {

    const mes  = parseInt($("#mesModal").val());
    const anio = parseInt($("#anioModal").val());
    const usados = new Set();

    turnosBD.forEach(trab => {
        trab.turnos.forEach(t => {
            const idMar = parseInt(t.Id_Marcacion_Tipo);
            if (!MARCACIONES_VALIDAS.includes(idMar)) return;
            const fechaTurno = new Date(t.FechaInicioTurno.date);
            if (fechaTurno.getMonth() === mes - 1 && fechaTurno.getFullYear() === anio) {
                usados.add(idMar);
            }
        });
    });

    let html = '';

    MARCACIONES_VALIDAS.forEach(id => {
        const estilo  = coloresMarcacion[id];
        const desc    = descripcionMarcacion[id] || "Turno";
        const opacity = usados.has(id) ? "1" : "0.35";

        html += `
        <span style="display:inline-flex;align-items:center;gap:4px;margin-right:8px;margin-bottom:4px;opacity:${opacity};">
            <span style="background:${estilo.bg};color:${estilo.fg};padding:2px 7px;border-radius:3px;
                         font-weight:bold;font-size:12px;border:1px solid rgba(0,0,0,0.15);">
                ${estilo.label}
            </span>
            <span style="font-size:12px;">${desc}</span>
        </span>`;
    });

    $("#leyendaMarcacion").html(html);
}


function agruparTurnosPorTrabajador(data) {

    const agrupado = {};

    data.forEach(t => {
        const id = t.Id_Trabajador;
        if (!agrupado[id]) {
            agrupado[id] = { id, turnos: [] };
        }
        agrupado[id].turnos.push({
            Id_Marcacion_Tipo : t.Id_Marcacion_Tipo,
            FechaInicioTurno  : t.ProcDias_Fecha_Ini,
            FechaFinTurno     : t.ProcDias_Fecha_Fin,
            Descripcion       : t.ProcDias_Documento,
            MarcTipo_Descripcion: t.MarcTipo_Descripcion || ""
        });
    });

    return Object.values(agrupado);
}


function obtenerTurnosActualizados(callback) {

    const trabajadores = JSON.parse(sessionStorage.getItem("trabajadoresHorario"));
    const ids = trabajadores.map(t => t.id);

    $.ajax({
        url: "modules/turnos/ajax/listarTurnosActualizados.ajax.php",
        method: "POST",
        data: {
            anio: $("#anioModal").val(),
            mes : $("#mesModal").val(),
            trabajadores: ids
        },
        success(res) {
            const data = JSON.parse(res);
            turnosBDGlobal = agruparTurnosPorTrabajador(data);
            if (callback) callback();
        }
    });
}

const MARCACIONES_VALIDAS = [61, 62, 42, 12, 41];

const coloresMarcacion = {
    61: { bg: "#FFC090", fg: "#000000", label: "TD" },  // Turno Día
    62: { bg: "#4BACC6", fg: "#FFFFFF", label: "TN" },  // Turno Noche
    42: { bg: "#00B050", fg: "#FFFFFF", label: "C"  },  // Compensación
    12: { bg: "#FFE0FF", fg: "#000000", label: "O"  },  // Onomástico
    41: { bg: "#4472C4", fg: "#FFFFFF", label: "D"  },  // Descanso Semanal
};

const descripcionMarcacion = {
    61: "Turno Día",
    62: "Turno Noche",
    42: "Compensación",
    12: "Onomástico",
    41: "Descanso Semanal",
};

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

// Estado global compartido entre los módulos JS
let turnosBDGlobal   = [];
let turnosTemp       = [];
let seleccionadosBD  = [];
let seleccionadosUsuario = [];
let checkboxTemporal = null;
let ultimoCheckMarcado = null;


function getColorMarcacion(id) {
    const m = coloresMarcacion[id];
    return m ? m.bg : "";
}
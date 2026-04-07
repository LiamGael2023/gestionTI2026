var CONSOLIDADO_OFICIAL_SECCIONES = [
	{ id: '1.1', titulo: 'DE EQUIPOS DE COMPUTO DE PROCESAMIENTO DISENO INGENIERIA (T1)', codigos: ['T1'] },
	{ id: '1.2', titulo: 'DE EQUIPOS DE COMPUTO ESTANDAR (T2)', codigos: ['T2'] },
	{ id: '1.3', titulo: 'DE EQUIPOS PORTATILES ESTANDAR (T3) Y INGENIERIA (T4), DISENO (T26)', codigos: ['T3', 'T4', 'T26'] },
	{ id: '1.4', titulo: 'DE EQUIPOS DE IMPRESION (T5, T6, T8)', codigos: ['T5', 'T6', 'T8'] },
	{ id: '1.5', titulo: 'DE ESCANER (T16)', codigos: ['T16'] },
	{ id: '1.6', titulo: 'DE DISCOS DUROS EXTERNOS (T27, T28, T29)', codigos: ['T27', 'T28', 'T29'] },
];

function exportarConsolidado() {
	if (!xlsxDisponible()) {
		return;
	}

	var tabla = document.getElementById('tabla-consolidado');
	if (!tabla) {
		notificar('info', 'Sin datos para exportar', 'No hay datos para exportar.');
		return;
	}

	var anio = obtenerAnioConsolidado();
	var headers = [];
	if (tabla.tHead && tabla.tHead.rows.length > 0) {
		for (var h = 0; h < tabla.tHead.rows[0].cells.length; h++) {
			headers.push(textoCelda(tabla.tHead.rows[0].cells[h]));
		}
	}

	var filasHoja = [];
	var merges = [];
	var totalCols = Math.max(headers.length, 1);

	filasHoja.push(['Consolidado de Equipos por Centro de Costo']);
	merges.push(rango(0, 0, 0, totalCols - 1));

	if (anio) {
		filasHoja.push(['Anio: ' + anio]);
		merges.push(rango(1, 0, 1, totalCols - 1));
	}

	filasHoja.push([]);
	filasHoja.push(headers);

	if (tabla.tBodies && tabla.tBodies.length > 0) {
		for (var r = 0; r < tabla.tBodies[0].rows.length; r++) {
			var fila = tabla.tBodies[0].rows[r];
			filasHoja.push(filaDesdeDom(fila, headers.length));
		}
	}

	if (tabla.tFoot && tabla.tFoot.rows.length > 0) {
		for (var f = 0; f < tabla.tFoot.rows.length; f++) {
			filasHoja.push(filaDesdeDom(tabla.tFoot.rows[f], headers.length));
		}
	}

	var worksheet = XLSX.utils.aoa_to_sheet(filasHoja, { sheetStubs: true });
	worksheet['!merges'] = merges;
	worksheet['!cols'] = calcularAnchos(headers);
	var workbook = XLSX.utils.book_new();
	XLSX.utils.book_append_sheet(workbook, worksheet, 'Consolidado');
	XLSX.writeFile(workbook, 'Consolidado_Equipos' + (anio ? '_' + anio : '') + '.xlsx');
}

function exportarConsolidadoOficial() {
	if (!xlsxDisponible()) {
		return;
	}

	var anio = obtenerAnioConsolidado();
	var body = new URLSearchParams();
	body.append('anio', anio || '');

	fetch('index.php?module=adquisiciones&action=consolidadoFormatoOficialAjax', {
		method: 'POST',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
		},
		body: body.toString(),
	})
		.then(function(response) {
			if (!response.ok) {
				throw new Error('No se pudo obtener la data del consolidado.');
			}
			return response.json();
		})
		.then(function(data) {
			if (!data || data.success !== true) {
				throw new Error((data && data.message) ? data.message : 'Respuesta invalida del servidor.');
			}

			var filas = Array.isArray(data.filas) ? data.filas : [];
			if (filas.length === 0) {
				notificar('info', 'Sin datos para exportar', 'No hay información para el año seleccionado.');
				return;
			}

			exportarFormatoOficialXlsx(filas, String(data.anio || anio || ''));
		})
		.catch(function(error) {
			notificar('error', 'No se pudo exportar', error && error.message ? error.message : 'Error inesperado.');
		});
}

function exportarFormatoOficialXlsx(filas, anio) {
	var secciones = construirSeccionesConAnio(anio);
	var metas = obtenerMetasOficiales();
	var agrupado = agruparPorSeccion(filas, secciones);
	var filasHoja = [];
	var merges = [];
	var contadorItem = 1;
	var columnasFijas = 7;
	var columnasMetas = metas.length * 2;
	var indiceInicioMetas = columnasFijas;
	var indiceFinMetas = indiceInicioMetas + columnasMetas - 1;
	var indiceTotalInicial = indiceFinMetas + 1;
	var indiceMontoTotal = indiceFinMetas + 2;
	var totalColumnas = indiceMontoTotal + 1;

	var filaCabecera1 = new Array(totalColumnas).fill('');
	var filaCabecera2 = new Array(totalColumnas).fill('');

	filaCabecera1[0] = 'N';
	filaCabecera1[1] = 'USUARIO ASIGNADO';
	filaCabecera1[2] = 'TIPO DE EQUIPO';
	filaCabecera1[3] = 'DESCRIPCION DEL COMPONENTE';
	filaCabecera1[4] = 'REFERENCIA';
	filaCabecera1[5] = 'UNIDAD MEDIDA';
	filaCabecera1[6] = 'PRECIO UNITARIO';
	filaCabecera1[indiceInicioMetas] = 'METAS SIAF';
	filaCabecera1[indiceTotalInicial] = 'TOTAL INICIAL';
	filaCabecera1[indiceMontoTotal] = 'MONTO TOTAL';

	for (var m = 0; m < metas.length; m++) {
		var colCodigo = indiceInicioMetas + (m * 2);
		var colNombre = colCodigo + 1;
		filaCabecera2[colCodigo] = metas[m].codigo;
		filaCabecera2[colNombre] = metas[m].nombre;
	}

	filasHoja.push(filaCabecera1);
	filasHoja.push(filaCabecera2);

	merges.push(rango(0, 0, 1, 0));
	merges.push(rango(0, 1, 1, 1));
	merges.push(rango(0, 2, 1, 2));
	merges.push(rango(0, 3, 1, 3));
	merges.push(rango(0, 4, 1, 4));
	merges.push(rango(0, 5, 1, 5));
	merges.push(rango(0, 6, 1, 6));
	merges.push(rango(0, indiceInicioMetas, 0, indiceFinMetas));
	merges.push(rango(0, indiceTotalInicial, 1, indiceTotalInicial));
	merges.push(rango(0, indiceMontoTotal, 1, indiceMontoTotal));

	for (var s = 0; s < secciones.length; s++) {
		var seccion = secciones[s];
		var items = agrupado[seccion.id] || [];
		if (items.length === 0) {
			continue;
		}

		filasHoja.push([seccion.id + ' ' + seccion.titulo]);
		merges.push(rango(filasHoja.length - 1, 0, filasHoja.length - 1, totalColumnas - 1));

		for (var i = 0; i < items.length; i++) {
			filasHoja.push(construirFilaDetalleOficial(items[i], contadorItem, metas));
			contadorItem++;
		}

		filasHoja.push(construirFilaResumenOficial('SUBTOTAL ' + seccion.id, totalColumnas));
		merges.push(rango(filasHoja.length - 1, 0, filasHoja.length - 1, 6));
	}

	var otros = agrupado.otros || [];
	if (otros.length > 0) {
		filasHoja.push(['OTROS TIPOS']);
		merges.push(rango(filasHoja.length - 1, 0, filasHoja.length - 1, totalColumnas - 1));

		for (var o = 0; o < otros.length; o++) {
			filasHoja.push(construirFilaDetalleOficial(otros[o], contadorItem, metas));
			contadorItem++;
		}

		filasHoja.push(construirFilaResumenOficial('SUBTOTAL OTROS', totalColumnas));
		merges.push(rango(filasHoja.length - 1, 0, filasHoja.length - 1, 6));
	}

	filasHoja.push(construirFilaResumenOficial('TOTAL GENERAL', totalColumnas));
	merges.push(rango(filasHoja.length - 1, 0, filasHoja.length - 1, 6));

	var worksheet = XLSX.utils.aoa_to_sheet(filasHoja, { sheetStubs: true });
	worksheet['!merges'] = merges;
	var anchos = [
		{ wch: 6 },
		{ wch: 22 },
		{ wch: 34 },
		{ wch: 28 },
		{ wch: 16 },
		{ wch: 14 },
		{ wch: 14 },
	];
	for (var a = 0; a < metas.length; a++) {
		anchos.push({ wch: 8 });
		anchos.push({ wch: 24 });
	}
	anchos.push({ wch: 14 });
	anchos.push({ wch: 14 });
	worksheet['!cols'] = anchos;

	var workbook = XLSX.utils.book_new();
	XLSX.utils.book_append_sheet(workbook, worksheet, 'Consolidado');
	XLSX.writeFile(workbook, 'RESUMEN_Consolidado_Oficial_' + (anio || 'sin_anio') + '.xlsx');
}

function construirSeccionesConAnio(anio) {
	var prefijo = 'CONSOLIDADO DE REQUERIMIENTO ' + anio + ' ';
	return CONSOLIDADO_OFICIAL_SECCIONES.map(function(seccion) {
		return {
			id: seccion.id,
			titulo: prefijo + seccion.titulo,
			codigos: seccion.codigos.slice(),
		};
	});
}

function construirFilaDetalleOficial(fila, contadorItem, metas) {
	var filaHoja = [
		contadorItem,
		'',
		formatoTipo(fila),
		'',
		'',
		valorTexto(fila.UnidadMedida),
		valorNumeroSeguro(fila.PrecioUnitario),
	];

	for (var meta = 0; meta < metas.length; meta++) {
		var clave = 'Meta' + metas[meta].codigo;
		filaHoja.push('');
		filaHoja.push(valorNumeroPositivo(fila[clave]));
	}

	filaHoja.push('');
	filaHoja.push('');
	return filaHoja;
}

function construirFilaResumenOficial(etiqueta, totalColumnas) {
	var fila = [etiqueta];
	for (var indice = 1; indice < totalColumnas; indice++) {
		fila.push('');
	}
	return fila;
}

function obtenerMetasOficiales() {
	return [
		{ codigo: '003', nombre: 'PRODUCCION AGRARIA' },
		{ codigo: '004', nombre: 'CONTROL INTERNO' },
		{ codigo: '005', nombre: 'DIRECCION TEC. SUPERVISION Y ADMINISTRACION' },
		{ codigo: '006', nombre: 'MEDIO AMBIENTE' },
		{ codigo: '007', nombre: 'SANEAMIENTO FISICO LEGAL' },
		{ codigo: '008', nombre: 'UTF' },
		{ codigo: '009', nombre: 'SGOYM' },
		{ codigo: '010', nombre: 'PTAP' },
	];
}

function agruparPorSeccion(filas, secciones) {
	var salida = { otros: [] };
	for (var s = 0; s < secciones.length; s++) {
		salida[secciones[s].id] = [];
	}

	for (var i = 0; i < filas.length; i++) {
		var fila = filas[i];
		var codigo = String(fila.TipoCodigo || '').toUpperCase().trim();
		var seccionEncontrada = null;
		for (var j = 0; j < secciones.length; j++) {
			if (secciones[j].codigos.indexOf(codigo) >= 0) {
				seccionEncontrada = secciones[j].id;
				break;
			}
		}
		if (seccionEncontrada) {
			salida[seccionEncontrada].push(fila);
		} else {
			salida.otros.push(fila);
		}
	}

	return salida;
}

function filaDesdeDom(tr, totalColumnas) {
	var fila = [];
	for (var c = 0; c < totalColumnas; c++) {
		if (!tr.cells[c]) {
			fila.push('');
			continue;
		}
		var texto = textoCelda(tr.cells[c]);
		fila.push(parseNumeroLocal(texto));
	}
	return fila;
}

function textoCelda(celda) {
	if (!celda) {
		return '';
	}
	return String(celda.textContent || '').replace(/\s+/g, ' ').trim();
}

function parseNumeroLocal(valor) {
	if (!valor) {
		return '';
	}
	var limpio = String(valor).replace(/\./g, '').replace(',', '.');
	if (/^-?\d+(\.\d+)?$/.test(limpio)) {
		return Number(limpio);
	}
	return valor;
}

function calcularAnchos(headers) {
	var anchos = [];
	for (var i = 0; i < headers.length; i++) {
		var base = String(headers[i] || '').length;
		anchos.push({ wch: Math.max(10, Math.min(base + 4, 40)) });
	}
	return anchos;
}

function valorNumeroSeguro(valor) {
	if (valor === null || typeof valor === 'undefined' || valor === '') {
		return '';
	}
	var n = Number(valor);
	return isNaN(n) ? '' : n;
}

function valorNumeroPositivo(valor) {
	var n = Number(valor || 0);
	return n > 0 ? n : '';
}

function formatoTipo(fila) {
	var codigo = valorTexto(fila.TipoCodigo);
	var nombre = valorTexto(fila.TipoNombre);
	if (codigo && nombre) {
		return codigo + ': ' + nombre;
	}
	return codigo || nombre;
}

function valorTexto(valor) {
	if (valor === null || typeof valor === 'undefined') {
		return '';
	}
	return String(valor).trim();
}

function obtenerAnioConsolidado() {
	var anioEl = document.getElementById('filtroAnioConsolidado');
	return anioEl ? String(anioEl.value || '').trim() : '';
}

function xlsxDisponible() {
	if (typeof XLSX !== 'undefined') {
		return true;
	}
	notificar('error', 'SheetJS no disponible', 'No se pudo cargar la libreria XLSX para generar el archivo.');
	return false;
}

function rango(inicioFila, inicioColumna, finFila, finColumna) {
	return {
		s: { r: inicioFila, c: inicioColumna },
		e: { r: finFila, c: finColumna },
	};
}

function notificar(tipo, titulo, mensaje) {
	if (typeof window.adqNotifySafe === 'function') {
		window.adqNotifySafe(tipo, titulo, mensaje);
		return;
	}
	if (tipo === 'error') {
		console.error(titulo + ': ' + mensaje);
	} else {
		console.log(titulo + ': ' + mensaje);
	}
}

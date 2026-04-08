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
			var metasCabecera = normalizarMetasCabeceraOficial(data.metasCabecera);
			if (metasCabecera.length === 0) {
				notificar('error', 'No se pudo exportar', 'No hay metas SIAF activas para construir la cabecera del consolidado.');
				return;
			}
			if (filas.length === 0) {
				notificar('info', 'Sin datos para exportar', 'No hay información para el año seleccionado.');
				return;
			}

			exportarFormatoOficialXlsx(filas, String(data.anio || anio || ''), metasCabecera);
		})
		.catch(function(error) {
			notificar('error', 'No se pudo exportar', error && error.message ? error.message : 'Error inesperado.');
		});
}

function exportarFormatoOficialXlsx(filas, anio, metasCabecera) {
	var metas = metasCabecera;
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
	var layout = {
		columnasFijas: columnasFijas,
		indiceInicioMetas: indiceInicioMetas,
		indiceTotalInicial: indiceTotalInicial,
		indiceMontoTotal: indiceMontoTotal,
		totalColumnas: totalColumnas,
	};
    var totalGeneral = crearTotalesAcumulados(metas);

	var filaCabecera1 = new Array(totalColumnas).fill('');
	var filaCabecera2 = new Array(totalColumnas).fill('');

	filaCabecera1[0] = 'N';
	filaCabecera1[1] = 'USUARIO ASIGNADO';
	filaCabecera1[2] = 'TIPO DE EQUIPO';
	filaCabecera1[3] = 'DESCRIPCION DEL COMPONENTE';
	filaCabecera1[4] = 'REFERENCIA';
	filaCabecera1[5] = 'UNIDAD DE MEDIDA';
	filaCabecera1[6] = 'PRECIO UNITARIO REFERENCIA';
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

	for (var i = 0; i < filas.length; i++) {
		acumularTotalesItem(totalGeneral, filas[i], metas);
		filasHoja.push(construirFilaDetalleOficial(filas[i], contadorItem, metas, layout));
		contadorItem++;
	}

	filasHoja.push(construirFilaResumenOficial('TOTAL GENERAL', totalGeneral, metas, layout));
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

function construirFilaDetalleOficial(fila, contadorItem, metas, layout) {
	var precioUnitario = valorNumeroSeguro(fila.PrecioUnitario);
	var totalInicial = 0;
	var montoTotal = 0;
	var filaHoja = [
		contadorItem,
		'',
		formatoTipo(fila),
		'',
		'',
		valorTexto(fila.UnidadMedida),
		precioUnitario,
	];

	for (var meta = 0; meta < metas.length; meta++) {
		var cantidad = obtenerCantidadMetaFila(fila, metas[meta].codigo);
		var montoMeta = redondearMonto(cantidad * (Number(precioUnitario) || 0));
		totalInicial += cantidad;
		montoTotal += montoMeta;
		filaHoja.push(valorNumeroPositivo(cantidad));
		filaHoja.push(valorNumeroPositivo(montoMeta));
	}

	filaHoja.push(valorNumeroPositivo(totalInicial));
	filaHoja.push(valorNumeroPositivo(redondearMonto(montoTotal)));
	return filaHoja;
}

function construirFilaResumenOficial(etiqueta, totales, metas, layout) {
	var fila = new Array(layout.totalColumnas).fill('');
	fila[0] = etiqueta;

	for (var meta = 0; meta < metas.length; meta++) {
		var colCantidad = layout.indiceInicioMetas + (meta * 2);
		var colMonto = colCantidad + 1;
		fila[colCantidad] = valorNumeroPositivo(totales.cantidades[meta]);
		fila[colMonto] = valorNumeroPositivo(redondearMonto(totales.montos[meta]));
	}

	fila[layout.indiceTotalInicial] = valorNumeroPositivo(totales.totalInicial);
	fila[layout.indiceMontoTotal] = valorNumeroPositivo(redondearMonto(totales.montoTotal));
	return fila;
}

function crearTotalesAcumulados(metas) {
	return {
		cantidades: new Array(metas.length).fill(0),
		montos: new Array(metas.length).fill(0),
		totalInicial: 0,
		montoTotal: 0,
	};
}

function acumularTotalesItem(totales, fila, metas) {
	var precioUnitario = Number(valorNumeroSeguro(fila.PrecioUnitario) || 0);
	for (var meta = 0; meta < metas.length; meta++) {
		var cantidad = obtenerCantidadMetaFila(fila, metas[meta].codigo);
		var monto = redondearMonto(cantidad * precioUnitario);
		totales.cantidades[meta] += cantidad;
		totales.montos[meta] += monto;
		totales.totalInicial += cantidad;
		totales.montoTotal += monto;
	}
}

function obtenerCantidadMetaFila(fila, codigoMeta) {
	if (!fila || typeof fila !== 'object') {
		return 0;
	}

	var codigo = normalizarCodigoMetaSiaf(codigoMeta);
	var clave4 = obtenerClaveMetaCampo(codigo);
	var clave3 = 'Meta' + codigo.padStart(3, '0');

	if (Object.prototype.hasOwnProperty.call(fila, clave4)) {
		return valorNumeroCantidad(fila[clave4]);
	}

	if (Object.prototype.hasOwnProperty.call(fila, clave3)) {
		return valorNumeroCantidad(fila[clave3]);
	}

	return 0;
}

function valorNumeroCantidad(valor) {
	var n = Number(valor || 0);
	return isNaN(n) ? 0 : n;
}

function redondearMonto(valor) {
	return Math.round((Number(valor || 0) + Number.EPSILON) * 100) / 100;
}

function normalizarMetasCabeceraOficial(metasRaw) {
	if (!Array.isArray(metasRaw)) {
		return [];
	}

	var salida = [];
	var vistos = {};

	for (var i = 0; i < metasRaw.length; i++) {
		var meta = metasRaw[i] || {};
		var codigo = normalizarCodigoMetaSiaf(meta.CodigoMeta || meta.codigo || '');
		if (!codigo || vistos[codigo]) {
			continue;
		}

		vistos[codigo] = true;
		salida.push({
			codigo: codigo,
			nombre: valorTexto(meta.Descripcion || meta.nombre || codigo),
		});
	}

	return salida;
}

function normalizarCodigoMetaSiaf(codigo) {
	var limpio = valorTexto(codigo).replace(/[^0-9]/g, '');
	if (!limpio) {
		return '';
	}
	if (limpio.length < 3) {
		limpio = limpio.padStart(3, '0');
	}
	if (limpio.length > 4) {
		limpio = limpio.slice(-4);
	}
	return limpio;
}

function obtenerClaveMetaCampo(codigoMeta) {
	var codigo = normalizarCodigoMetaSiaf(codigoMeta);
	if (!codigo) {
		return '';
	}
	return 'Meta' + codigo.padStart(4, '0');
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

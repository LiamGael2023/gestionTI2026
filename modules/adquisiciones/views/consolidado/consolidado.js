function exportarConsolidado() {
	var anio = obtenerAnioConsolidado();
	var url = 'index.php?module=adquisiciones&action=consolidadoNoOficialXlsxAjax';
	if (anio) {
		url += '&anio=' + encodeURIComponent(anio);
	}
	window.location.href = url;
}

function exportarConsolidadoXlsx(tabla, anio) {
	var cabeceraInfo = obtenerCabecerasExportacionConsolidado(tabla);

	var filasHoja = [];
	var merges = [];
	var totalCols = Math.max(cabeceraInfo.totalCols, 1);

	filasHoja.push(['Consolidado de Equipos por Centro de Costo']);
	merges.push(rango(0, 0, 0, totalCols - 1));

	if (anio) {
		filasHoja.push(['Anio: ' + anio]);
		merges.push(rango(1, 0, 1, totalCols - 1));
	}

	filasHoja.push([]);
	for (var hr = 0; hr < cabeceraInfo.rows.length; hr++) {
		filasHoja.push(cabeceraInfo.rows[hr]);
	}
	for (var hm = 0; hm < cabeceraInfo.merges.length; hm++) {
		merges.push(desplazarRango(cabeceraInfo.merges[hm], filasHoja.length - cabeceraInfo.rows.length));
	}

	if (tabla.tBodies && tabla.tBodies.length > 0) {
		if (cabeceraInfo.source === 'data') {
			filasHoja = filasHoja.concat(construirFilasConsolidadoDesdeDatos(cabeceraInfo));
		} else {
			for (var r = 0; r < tabla.tBodies[0].rows.length; r++) {
				var fila = tabla.tBodies[0].rows[r];
				if (Array.isArray(cabeceraInfo.columnIndexes) && cabeceraInfo.columnIndexes.length > 0) {
					filasHoja.push(filaDesdeDomPorIndices(fila, cabeceraInfo.columnIndexes));
				} else {
					filasHoja.push(filaDesdeDom(fila, totalCols));
				}
			}
		}
	}

	if (tabla.tFoot && tabla.tFoot.rows.length > 0) {
		if (cabeceraInfo.source === 'data') {
			filasHoja.push(construirFilaTotalConsolidadoDesdeDatos(cabeceraInfo));
		} else {
			for (var f = 0; f < tabla.tFoot.rows.length; f++) {
				if (Array.isArray(cabeceraInfo.columnIndexes) && cabeceraInfo.columnIndexes.length > 0) {
					filasHoja.push(filaDesdeDomPorIndices(tabla.tFoot.rows[f], cabeceraInfo.columnIndexes));
				} else {
					filasHoja.push(filaDesdeDom(tabla.tFoot.rows[f], totalCols));
				}
			}
		}
	}

	var worksheet = XLSX.utils.aoa_to_sheet(filasHoja, { sheetStubs: true });
	worksheet['!merges'] = merges;
	worksheet['!cols'] = calcularAnchosConsolidadoPorContenido(filasHoja, totalCols);
	var workbook = XLSX.utils.book_new();
	XLSX.utils.book_append_sheet(workbook, worksheet, 'Consolidado');
	XLSX.writeFile(workbook, 'Consolidado_Equipos' + (anio ? '_' + anio : '') + '.xlsx');
}

function calcularAnchosConsolidadoPorContenido(filasHoja, totalCols) {
	if (totalCols <= 0) {
		return [];
	}

	var maximos = new Array(totalCols).fill(0);
	var inicioMedicion = 3; // Ignora titulo, anio y fila en blanco superior

	for (var r = inicioMedicion; r < filasHoja.length; r++) {
		var fila = Array.isArray(filasHoja[r]) ? filasHoja[r] : [];
		for (var c = 0; c < totalCols; c++) {
			var valor = fila[c];
			if (valor === null || typeof valor === 'undefined') {
				continue;
			}
			var texto = String(valor);
			if (texto.length > maximos[c]) {
				maximos[c] = texto.length;
			}
		}
	}

	var anchos = [];
	for (var i = 0; i < totalCols; i++) {
		var esColumnaFija = i === 0 || i === 1;
		var base = maximos[i] > 0 ? maximos[i] : 1;
		var extra = esColumnaFija ? 1.2 : 0.4;
		var minimo = esColumnaFija ? 8 : 2.8;
		var maximo = esColumnaFija ? 45 : 20;
		var ajustado = Math.min(Math.max(base + extra, minimo), maximo);
		anchos.push({ wch: ajustado });
	}

	return anchos;
}

function cargarCabeceraCompletaExportacion() {
	return fetch('index.php?module=adquisiciones&action=consolidadoCabeceraExportacionAjax', {
		method: 'GET',
		headers: {
			'X-Requested-With': 'XMLHttpRequest',
		},
	})
		.then(function(response) {
			if (!response.ok) {
				throw new Error('No se pudo obtener la cabecera completa del consolidado.');
			}
			return response.json();
		})
		.then(function(data) {
			if (!data || data.success !== true) {
				throw new Error('Respuesta invalida para cabecera de exportacion.');
			}

			var cabecera = Array.isArray(data.cabeceraCentros) ? data.cabeceraCentros : [];
			window.adqConsolidadoCabeceraCentrosExportacion = cabecera;

			var columnasPlano = [];
			for (var i = 0; i < cabecera.length; i++) {
				var grupo = cabecera[i] || {};
				var columnas = Array.isArray(grupo.columnas) ? grupo.columnas : [];
				if (columnas.length > 0) {
					for (var j = 0; j < columnas.length; j++) {
						columnasPlano.push(columnas[j]);
					}
					continue;
				}
				columnasPlano.push({
					key: '',
					label: String(grupo.label || ''),
				});
			}

			window.adqConsolidadoColumnasPlanoExportacion = columnasPlano;
		});
}

function exportarConsolidadoOficial() {
	var anio = obtenerAnioConsolidado();
	var url = 'index.php?module=adquisiciones&action=consolidadoOficialXlsxAjax';
	if (anio) {
		url += '&anio=' + encodeURIComponent(anio);
	}
	window.location.href = url;
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
		filasHoja.push(construirFilaDetalleOficial(filas[i], contadorItem, metas));
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

function construirFilaDetalleOficial(fila, contadorItem, metas) {
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

function filaDesdeDomPorIndices(tr, indices) {
	var fila = [];
	for (var i = 0; i < indices.length; i++) {
		var idx = indices[i];
		if (!tr.cells[idx]) {
			fila.push('');
			continue;
		}
		var texto = textoCelda(tr.cells[idx]);
		fila.push(parseNumeroLocal(texto));
	}
	return fila;
}

function construirFilasConsolidadoDesdeDatos(cabeceraInfo) {
	var equipos = obtenerEquiposOrdenadosConsolidado();
	var matriz = window.adqConsolidadoMatriz && typeof window.adqConsolidadoMatriz === 'object' ? window.adqConsolidadoMatriz : {};
	var filas = [];

	for (var i = 0; i < equipos.length; i++) {
		var equipo = String(equipos[i] || '');
		var fila = [equipo, obtenerTipoSolicitudPorEquipo(equipo)];
		var totalFila = 0;
		var valoresEquipo = matriz[equipo] && typeof matriz[equipo] === 'object' ? matriz[equipo] : {};

		for (var c = 0; c < cabeceraInfo.columnKeys.length; c++) {
			var key = cabeceraInfo.columnKeys[c];
			var cantidad = Number(valoresEquipo[key] || 0);
			fila.push(cantidad > 0 ? cantidad : '');
		}

		for (var keyTotal in valoresEquipo) {
			if (!Object.prototype.hasOwnProperty.call(valoresEquipo, keyTotal)) {
				continue;
			}
			totalFila += Number(valoresEquipo[keyTotal] || 0);
		}

		fila.push(totalFila > 0 ? totalFila : '');
		filas.push(fila);
	}

	return filas;
}

function obtenerEquiposOrdenadosConsolidado() {
	var equipos = Array.isArray(window.adqConsolidadoEquipos) ? window.adqConsolidadoEquipos.slice() : [];
	if (!equipos.length) {
		return equipos;
	}

	equipos.sort(function(a, b) {
		var equipoA = String(a || '');
		var equipoB = String(b || '');
		var tipoA = obtenerTipoSolicitudPorEquipo(equipoA).toUpperCase();
		var tipoB = obtenerTipoSolicitudPorEquipo(equipoB).toUpperCase();

		if (tipoA !== tipoB) {
			if (!tipoA) {
				return 1;
			}
			if (!tipoB) {
				return -1;
			}
			return tipoA.localeCompare(tipoB, 'es', { sensitivity: 'base' });
		}

		return equipoA.localeCompare(equipoB, 'es', { sensitivity: 'base' });
	});

	return equipos;
}

function construirFilaTotalConsolidadoDesdeDatos(cabeceraInfo) {
	var totalesMap = window.adqConsolidadoTotalesPorColumna && typeof window.adqConsolidadoTotalesPorColumna === 'object'
		? window.adqConsolidadoTotalesPorColumna
		: {};
	var fila = ['TOTAL', ''];
	var totalGeneral = 0;

	for (var c = 0; c < cabeceraInfo.columnKeys.length; c++) {
		var key = cabeceraInfo.columnKeys[c];
		var total = Number(totalesMap[key] || 0);
		fila.push(total > 0 ? total : '');
	}

	for (var keyTotal in totalesMap) {
		if (!Object.prototype.hasOwnProperty.call(totalesMap, keyTotal)) {
			continue;
		}
		totalGeneral += Number(totalesMap[keyTotal] || 0);
	}

	fila.push(totalGeneral > 0 ? totalGeneral : '');
	return fila;
}

function obtenerCabecerasHojaDesdeTabla(tabla) {
	if (!tabla || !tabla.tHead || !tabla.tHead.rows.length) {
		return { rows: [], merges: [], headers: [], totalCols: 0 };
	}

	var rows = [];
	var merges = [];
	var ocupadas = [];
	var totalCols = 0;

	for (var r = 0; r < tabla.tHead.rows.length; r++) {
		var row = tabla.tHead.rows[r];
		var salida = [];
		var col = 0;

		while (ocupadas[r] && ocupadas[r][col]) {
			salida[col] = '';
			col++;
		}

		for (var c = 0; c < row.cells.length; c++) {
			while (ocupadas[r] && ocupadas[r][col]) {
				salida[col] = '';
				col++;
			}

			var cell = row.cells[c];
			var texto = textoCelda(cell);
			var colspan = Math.max(parseInt(cell.colSpan || 1, 10), 1);
			var rowspan = Math.max(parseInt(cell.rowSpan || 1, 10), 1);

			salida[col] = texto;
			for (var extra = 1; extra < colspan; extra++) {
				salida[col + extra] = '';
			}

			if (colspan > 1 || rowspan > 1) {
				merges.push(rango(r, col, r + rowspan - 1, col + colspan - 1));
			}

			for (var rr = 0; rr < rowspan; rr++) {
				if (!ocupadas[r + rr]) {
					ocupadas[r + rr] = [];
				}
				for (var cc = 0; cc < colspan; cc++) {
					ocupadas[r + rr][col + cc] = true;
				}
			}

			col += colspan;
		}

		totalCols = Math.max(totalCols, salida.length);
		rows.push(salida);
	}

	for (var i = 0; i < rows.length; i++) {
		while (rows[i].length < totalCols) {
			rows[i].push('');
		}
	}

	var headers = rows.length > 0 ? rows[rows.length - 1].slice() : [];
	return { rows: rows, merges: merges, headers: headers, totalCols: totalCols };
}

function obtenerCabecerasExportacionConsolidado(tabla) {
	var metadata = Array.isArray(window.adqConsolidadoCabeceraCentrosExportacion) && window.adqConsolidadoCabeceraCentrosExportacion.length
		? window.adqConsolidadoCabeceraCentrosExportacion
		: (Array.isArray(window.adqConsolidadoCabeceraCentros) ? window.adqConsolidadoCabeceraCentros : []);
	var columnasPlano = Array.isArray(window.adqConsolidadoColumnasPlanoExportacion) && window.adqConsolidadoColumnasPlanoExportacion.length
		? window.adqConsolidadoColumnasPlanoExportacion
		: (Array.isArray(window.adqConsolidadoColumnasPlano) ? window.adqConsolidadoColumnasPlano : []);

	if (!metadata.length || !columnasPlano.length) {
		return obtenerCabecerasHojaDesdeTabla(tabla);
	}

	var columnasMetadataSet = {};
	for (var x = 0; x < columnasPlano.length; x++) {
		var keyPlano = String((columnasPlano[x] && columnasPlano[x].key) || '');
		if (!keyPlano) {
			continue;
		}
		columnasMetadataSet[keyPlano] = {
			key: keyPlano,
			label: String((columnasPlano[x] && columnasPlano[x].label) || ''),
			domIndex: -1,
		};
	}

	var tieneGrupos = metadata.some(function(grupo) {
		if (!Array.isArray(grupo.columnas)) {
			return false;
		}
		var activas = grupo.columnas.filter(function(col) {
			return !!columnasMetadataSet[String((col && col.key) || '')];
		});
		return activas.length > 1;
	});

	var domTotalCols = obtenerTotalColumnasDom(tabla);
	var indiceTotalDom = Math.max(domTotalCols - 1, 0);
	var columnIndexes = [0, -1];

	if (!tieneGrupos) {
		var headersSimples = ['Equipo', 'Tipo de Solicitud'];
		var columnKeysSimples = [];
		for (var s = 0; s < columnasPlano.length; s++) {
			var keySimplePlano = String((columnasPlano[s] && columnasPlano[s].key) || '');
			if (!keySimplePlano) {
				continue;
			}
			headersSimples.push(String((columnasPlano[s] && columnasPlano[s].label) || ''));
			columnIndexes.push(-1);
			columnKeysSimples.push(keySimplePlano);
		}
			headersSimples.push('TOTAL');
		columnIndexes.push(indiceTotalDom);
		return {
			rows: [headersSimples],
			merges: [],
			headers: headersSimples.slice(),
			totalCols: headersSimples.length,
			columnIndexes: columnIndexes,
			columnKeys: columnKeysSimples,
			source: 'data',
		};
	}

	var totalCols = 3;
	var fila1 = ['Equipo', 'Tipo de Solicitud'];
	var fila2 = ['', ''];
	var merges = [rango(0, 0, 1, 0), rango(0, 1, 1, 1)];
	var headers = ['Equipo', 'Tipo de Solicitud'];
	var colActual = 2;
	var columnKeys = [];

	for (var i = 0; i < metadata.length; i++) {
		var grupo = metadata[i] || {};
		var columnas = Array.isArray(grupo.columnas) ? grupo.columnas.filter(function(col) {
			return !!columnasMetadataSet[String((col && col.key) || '')];
		}) : [];
		var labelGrupo = String(grupo.label || '');
		if (!columnas.length) {
			continue;
		}

		if (columnas.length > 1) {
			fila1.push(labelGrupo);
			for (var espacio = 1; espacio < columnas.length; espacio++) {
				fila1.push('');
			}
			merges.push(rango(0, colActual, 0, colActual + columnas.length - 1));
			for (var j = 0; j < columnas.length; j++) {
				var keyColumna = String((columnas[j] && columnas[j].key) || '');
				var labelColumna = String((columnas[j] && columnas[j].label) || '');
				fila2.push(labelColumna);
				headers.push(labelColumna);
				columnIndexes.push(-1);
				columnKeys.push(keyColumna);
			}
			colActual += columnas.length;
			totalCols += columnas.length;
			continue;
		}

		var labelSimple = columnas.length === 1 ? String((columnas[0] && columnas[0].label) || labelGrupo) : labelGrupo;
		var keySimple = String((columnas[0] && columnas[0].key) || '');
		fila1.push(labelSimple);
		fila2.push('');
		merges.push(rango(0, colActual, 1, colActual));
		headers.push(labelSimple);
		columnIndexes.push(-1);
		columnKeys.push(keySimple);
		colActual += 1;
		totalCols += 1;
	}

	fila1.push('TOTAL');
	fila2.push('');
	merges.push(rango(0, colActual, 1, colActual));
	headers.push('TOTAL');
	columnIndexes.push(indiceTotalDom);

	return {
		rows: [fila1, fila2],
		merges: merges,
		headers: headers,
		totalCols: totalCols,
		columnIndexes: columnIndexes,
		columnKeys: columnKeys,
		source: 'data',
	};
}

function obtenerTipoSolicitudPorEquipo(equipo) {
	var mapa = window.adqConsolidadoTiposSolicitudPorEquipo && typeof window.adqConsolidadoTiposSolicitudPorEquipo === 'object'
		? window.adqConsolidadoTiposSolicitudPorEquipo
		: {};
	return String(mapa[equipo] || '');
}

function obtenerTotalColumnasDom(tabla) {
	if (!tabla || !tabla.tHead || !tabla.tHead.rows.length) {
		return 0;
	}
	return tabla.tHead.rows[0].cells.length;
}

function obtenerColumnasConDatosParaExportacion(tabla, columnasPlano) {
	if (!tabla || !Array.isArray(columnasPlano) || !columnasPlano.length) {
		return [];
	}

	var totalesMap = window.adqConsolidadoTotalesPorColumna && typeof window.adqConsolidadoTotalesPorColumna === 'object'
		? window.adqConsolidadoTotalesPorColumna
		: null;

	if (totalesMap) {
		var columnasDesdeTotales = [];
		for (var t = 0; t < columnasPlano.length; t++) {
			var keyMeta = String((columnasPlano[t] && columnasPlano[t].key) || '');
			var totalMeta = Number(totalesMap[keyMeta] || 0);
			if (totalMeta > 0) {
				columnasDesdeTotales.push({
					key: keyMeta,
					label: String((columnasPlano[t] && columnasPlano[t].label) || ''),
					domIndex: -1,
				});
			}
		}
		return columnasDesdeTotales;
	}

	var totalColsDom = obtenerTotalColumnasDom(tabla);
	if (totalColsDom < 3) {
		return [];
	}

	var totalFila = (tabla.tFoot && tabla.tFoot.rows && tabla.tFoot.rows.length > 0) ? tabla.tFoot.rows[0] : null;
	var cantidadColumnasCentroDom = totalColsDom - 2;
	var cantidadColumnasMetadata = columnasPlano.length;
	if (cantidadColumnasMetadata !== cantidadColumnasCentroDom) {
		return [];
	}

	var columnas = [];
	for (var i = 0; i < columnasPlano.length; i++) {
		var domIndex = i + 1;
		var valorTotal = 0;
		if (totalFila && totalFila.cells[domIndex]) {
			valorTotal = Number(parseNumeroLocal(textoCelda(totalFila.cells[domIndex])) || 0);
		}

		if (valorTotal > 0) {
			columnas.push({
				key: String((columnasPlano[i] && columnasPlano[i].key) || ''),
				label: String((columnasPlano[i] && columnasPlano[i].label) || ''),
				domIndex: domIndex,
			});
		}
	}

	return columnas;
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

function desplazarRango(merge, offsetFilas) {
	return {
		s: { r: merge.s.r + offsetFilas, c: merge.s.c },
		e: { r: merge.e.r + offsetFilas, c: merge.e.c },
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

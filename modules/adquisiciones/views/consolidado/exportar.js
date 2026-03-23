function exportarConsolidado() {
	// Obtener la tabla
	const tabla = document.getElementById('tabla-consolidado');
	if (!tabla) {
		if (typeof window.adqNotifySafe === 'function') {
			window.adqNotifySafe('info', 'Sin datos para exportar', 'No hay datos para exportar.');
		} else {
			console.warn('No hay datos para exportar.');
		}
		return;
	}
	
	// Crear un libro de trabajo
	let html = '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
	html += '<head>';
	html += '<meta charset="UTF-8">';
	html += '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets>';
	html += '<x:ExcelWorksheet><x:Name>Consolidado</x:Name>';
	html += '<x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet>';
	html += '</x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
	html += '</head>';
	html += '<body>';
	html += '<table>';
	
	// Agregar encabezado con título
	html += '<tr><td colspan="' + (tabla.rows[0].cells.length) + '" style="font-size:16pt; font-weight:bold; text-align:center;">';
	html += 'Consolidado de Equipos por Centro de Costo';
	html += '</td></tr>';
	
	// Agregar año si existe
	const anio = document.getElementById('filtroAnioConsolidado').value;
	if (anio) {
		html += '<tr><td colspan="' + (tabla.rows[0].cells.length) + '" style="text-align:center;">';
		html += 'Año: ' + anio;
		html += '</td></tr>';
	}
	
	html += '<tr><td colspan="' + (tabla.rows[0].cells.length) + '"></td></tr>'; // Fila vacía
	
	// Copiar contenido de la tabla
	html += tabla.outerHTML;
	html += '</table></body></html>';
	
	// Crear el archivo y descargarlo
	const blob = new Blob(['\ufeff', html], {
		type: 'application/vnd.ms-excel'
	});
	
	const url = window.URL.createObjectURL(blob);
	const link = document.createElement('a');
	link.href = url;
	
	const anioTexto = anio ? '_' + anio : '';
	link.download = 'Consolidado_Equipos' + anioTexto + '.xls';
	
	document.body.appendChild(link);
	link.click();
	document.body.removeChild(link);
	window.URL.revokeObjectURL(url);
}

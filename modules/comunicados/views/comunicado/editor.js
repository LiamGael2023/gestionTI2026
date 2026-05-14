(function() {
	const shell = document.querySelector('.com-editor-shell');
	if (!shell || shell.dataset.editorBound === '1') {
		return;
	}
	shell.dataset.editorBound = '1';

	const email = document.getElementById('comEmail');
	const canvas = document.getElementById('comCanvas');
	const selectorPlantilla = document.getElementById('selectorPlantilla');
	const selectedClass = 'is-selected';
	const logoPeCh = 'https://app.chavimochic.gob.pe/Webservice/contador/LogoPech25.png';
	const imagenCabeceraPeCh = 'https://app.chavimochic.gob.pe/webservice/loginasistencia/fondoPECH.jpg';
	const marcaPeCh = 'CHAVIMOCHIC';
	const entidadPeCh = 'PROYECTO ESPECIAL CHAVIMOCHIC';
	let selectedId = null;
	let dragBlockId = null;

	const presets = {
		'': [
			{ type: 'cabecera', area: 'AREA DE INFORMATICA', badge: 'AVISO IMPORTANTE', tone: 'red', banner: '', logo: logoPeCh, height: '150', position: 'center' },
			{ type: 'titulo', icon: 'ðŸ“£', text: 'COMUNICADO NRO. 000-2026-PECH-INF', subtitle: 'Comunicado institucional' },
			{ type: 'meta', text: 'ðŸ“£ Comunicado informativo | ðŸ“… 14/05/2026 | ðŸ•˜ 11:30' },
			{ type: 'aviso', tone: 'green', title: 'Mensaje principal', text: 'Redacte aqui el resumen del comunicado institucional.' },
			{ type: 'parrafo', text: 'Se comunica a todo el personal usuario que la informacion detallada en el presente comunicado debe ser revisada y difundida oportunamente.' },
			{ type: 'pie', text: 'ðŸ’» AREA DE INFORMATICA - PROYECTO ESPECIAL CHAVIMOCHIC\nMensaje informativo institucional. âœ…' }
		],
		pred_siga_salida: [
			{ type: 'cabecera', area: 'AREA DE INFORMATICA', badge: 'âš  AVISO IMPORTANTE', tone: 'red', banner: '', logo: logoPeCh, height: '150', position: 'center' },
			{ type: 'titulo', icon: 'ðŸ”§', text: 'Salida Temporal del Sistema SIGA por Actualizacion Programada', subtitle: '' },
			{ type: 'meta', text: 'ðŸ“£ Comunicado informativo | ðŸ“… 21/04/2026 | ðŸ•˜ 11:22' },
			{ type: 'aviso', tone: 'red', title: 'ðŸš« Suspension temporal del acceso al SIGA', text: 'ðŸ“ Se realizara una actualizacion programada del sistema SIGA.\nðŸ”’ Durante este proceso, el acceso al sistema sera restringido temporalmente.' },
			{ type: 'parrafo', text: 'Se comunica a todo el personal usuario que el dia 21 de abril de 2026 se llevara a cabo una actualizacion programada del sistema SIGA, por lo que se solicita salir del sistema y no realizar operaciones mientras dure el proceso de actualizacion.' },
			{ type: 'tarjetas', items: 'ðŸ—“|Fecha de actualizacion|21/04/2026\nðŸ’»|Sistema afectado|Sistema Integrado de Gestion Administrativa - SIGA\nâš™|Motivo|Actualizacion programada del sistema' },
			{ type: 'lista', tone: 'blue', title: 'ðŸ§© Indicaciones para los usuarios', items: 'âœ… Guardar previamente la informacion en proceso.\nðŸšª Cerrar sesion y salir completamente del sistema SIGA.\nâ›” No intentar ingresar nuevamente hasta que se comunique el restablecimiento del servicio.' },
			{ type: 'panel', tone: 'green', title: 'âœ… Recomendacion', text: 'Se recomienda a los usuarios tomar las previsiones necesarias para evitar inconvenientes durante el periodo de actualizacion.' },
			{ type: 'parrafo', text: 'ðŸ”” Una vez culminado el proceso de actualizacion, se comunicara oportunamente la habilitacion del sistema.\n\nðŸ™ Agradecemos su comprension y colaboracion.' },
			{ type: 'pie', text: 'ðŸ’» AREA DE INFORMATICA - PROYECTO ESPECIAL CHAVIMOCHIC\nMensaje informativo institucional. âœ…' }
		],
		pred_siga_ok: [
			{ type: 'cabecera', area: 'AREA DE INFORMATICA', badge: 'âœ… AVISO IMPORTANTE', tone: 'green', banner: '', logo: logoPeCh, height: '150', position: 'center' },
			{ type: 'titulo', icon: 'âœ…', text: 'Restablecimiento del Sistema SIGA', subtitle: '' },
			{ type: 'meta', text: 'ðŸ“£ Comunicado informativo | ðŸ“… 21/04/2026 | ðŸ•˜ 11:04' },
			{ type: 'aviso', tone: 'green', title: 'ðŸŸ¢ Sistema habilitado nuevamente', text: 'ðŸ“ La actualizacion programada del sistema SIGA ha culminado satisfactoriamente.\nðŸ’» El acceso al sistema se encuentra disponible para todos los usuarios.' },
			{ type: 'parrafo', text: 'Se comunica a todo el personal usuario que la actualizacion programada del sistema SIGA realizada el dia 21 de abril de 2026 se ha ejecutado con exito, por lo que ya pueden ingresar y realizar sus operaciones con normalidad.' },
			{ type: 'tarjetas', items: 'ðŸ—“|Fecha de actualizacion|21/04/2026\nðŸ’»|Sistema|Sistema Integrado de Gestion Administrativa - SIGA\nâš™|Estado|Actualizacion culminada con exito' },
			{ type: 'lista', tone: 'blue', title: 'ðŸ§© Informacion adicional', items: 'âœ… La actualizacion del sistema fue completada correctamente.\nðŸ”’ El acceso al SIGA ha sido restablecido.\nðŸ“Œ Los usuarios pueden retomar sus actividades habituales en la plataforma.' },
			{ type: 'panel', tone: 'green', title: 'âœ… Recomendacion', text: 'En caso de presentarse alguna incidencia durante el ingreso o uso del sistema, agradeceremos reportarla oportunamente al Area de Informatica.' },
			{ type: 'pie', text: 'ðŸ’» AREA DE INFORMATICA - PROYECTO ESPECIAL CHAVIMOCHIC\nMensaje informativo institucional. âœ…' }
		],
		pred_evento: [
			{ type: 'cabecera', area: 'AREA DE INFORMATICA', badge: 'ðŸ“£ EVENTO INFORMATIVO', tone: 'green', banner: '', logo: logoPeCh, height: '150', position: 'center' },
			{ type: 'titulo', icon: 'ðŸ“Œ', text: 'INSCRIBETE AL Webinar - Buenas Practicas en Calidad de Servicios 2026', subtitle: '' },
			{ type: 'meta', text: 'ðŸ“£ Comunicado informativo | ðŸ“… 14/05/2026 | ðŸ•˜ 11:18' },
			{ type: 'aviso', tone: 'green', title: 'ðŸŽ¯ Participacion institucional y fortalecimiento de capacidades', text: 'Se difunde el presente recordatorio a fin de poner en conocimiento del personal la realizacion del webinar.' },
			{ type: 'imagen', src: '', alt: 'Imagen del evento' },
			{ type: 'parrafo', text: 'Se pone en conocimiento de todo el personal del Proyecto Especial Chavimochic la realizacion del primer webinar, espacio virtual en el cual se brindara informacion relevante sobre el proceso de postulacion, criterios y alcances del referido concurso.' },
			{ type: 'tarjetas', items: 'ðŸ—“|Fecha|miercoles, 22 de abril de 2026\nðŸ•˜|Hora|11:30 a.m.\nðŸ‘¤|Emisor|Secretaria de Gestion Publica - PCM' },
			{ type: 'lista', tone: 'blue', title: 'ðŸ”— Accesos disponibles', items: 'âš  Registrese previamente antes de ingresar a la reunion.\nâ€¢ Registro: https://facilita.gob.pe/t/52283\nâ€¢ Microsoft Teams: Ingresar al enlace de conexion' },
			{ type: 'lista', tone: 'gray', title: 'ðŸ“Œ Consideraciones', items: 'â€¢ Es muy importante realizar el registro previo antes de ingresar a la reunion.\nâ€¢ El webinar permitira conocer los detalles de postulacion.\nâ€¢ Constituye una oportunidad para fortalecer la calidad de atencion al ciudadano.' },
			{ type: 'firma', name: 'Atentamente,', role: 'Area de Informatica' },
			{ type: 'pie', text: 'ðŸ’» AREA DE INFORMATICA - PROYECTO ESPECIAL CHAVIMOCHIC\nComunicado institucional informativo. ðŸ“£' }
		],
		pred_modulo: [
			{ type: 'cabecera', area: 'AREA DE INFORMATICA', badge: 'ðŸš˜ ðŸ§¾ NUEVO MODULO 2026', tone: 'blue', banner: '', logo: logoPeCh, height: '150', position: 'center' },
			{ type: 'titulo', icon: 'ðŸ“£', text: 'COMUNICADO NRO. 013-2026-PECH-INF', subtitle: 'ðŸš˜ Implementacion del modulo de Papeletas de Conductores' },
			{ type: 'panel', tone: 'gray', title: 'ðŸ“Œ Dirigido a:', text: 'Jefe de Transportes: Ing. Juan Porras\nJefe de Maquinaria Pesada: Ing. Moises Sandoval' },
			{ type: 'panel', tone: 'gray', title: 'ðŸ‘‹ Buenas tardes:', text: 'Se comunica que ya se ha desarrollado el modulo de Papeletas de Conductores. En ese sentido, resulta necesaria la informacion contenida en el archivo Excel adjunto, a fin de realizar el ingreso de datos correspondiente y dar inicio a la etapa de pruebas del modulo.' },
			{ type: 'panel', tone: 'blue', title: 'ðŸ“Œ Informacion requerida', text: 'Por tal motivo, se solicita el envio de la informacion requerida lo mas pronto posible, con la finalidad de continuar con la implementacion y validacion funcional del sistema.' },
			{ type: 'adjunto', tone: 'green', title: 'Archivo adjunto para descarga', text: 'Puede descargar el archivo Excel desde el siguiente enlace:', button: 'DESCARGAR ARCHIVO EXCEL', fileName: 'Proyecto_Transportes_Requerimientos.xlsx', href: '#' },
			{ type: 'panel', tone: 'orange', title: 'â³ Atencion prioritaria', text: 'La remision oportuna de esta informacion permitira iniciar las pruebas del modulo y efectuar las validaciones necesarias antes de su puesta en funcionamiento.' },
			{ type: 'firma', name: 'Saludos cordiales', role: 'Area de Informatica - Proyecto Especial Chavimochic' },
			{ type: 'pie', text: 'ðŸ’» AREA DE INFORMATICA - PROYECTO ESPECIAL CHAVIMOCHIC\nðŸ“© Este es un mensaje informativo. Por favor no responder a este correo.' }
		],
		pred_bim: [
			{ type: 'cabecera', area: 'AREA DE INFORMATICA', badge: 'ðŸ‘· CAPACITACION BIM', tone: 'green', banner: '', logo: logoPeCh, height: '150', position: 'center' },
			{ type: 'titulo', icon: 'ðŸ“Š', text: 'Presentacion Autodesk AEC Collection', subtitle: '' },
			{ type: 'parrafo', text: 'Se realizara la presentacion de la Autodesk AEC Collection, que integra herramientas BIM y CAD orientadas al diseno, modelamiento y gestion de proyectos.' },
			{ type: 'lista', tone: 'gray', title: '', items: 'ðŸ§© Herramientas: Revit, Civil 3D y AutoCAD\nðŸŽ¯ Objetivo: Implementacion de la metodologia BIM en proyectos del PECH' },
			{ type: 'panel', tone: 'green', title: 'ðŸ’» Reunion de Microsoft Teams', text: 'ðŸ—“ Fecha: Miercoles 15/04/2025\nâ° Hora: 10:00 a.m.\n\nðŸ”— Unirse:\nhttps://teams.microsoft.com/\n\nðŸ†” Id. de reunion: 229 102 450 462 333\nðŸ” Codigo de acceso: zW6CS7gr' },
			{ type: 'pie', text: 'Impulsando la transformacion digital en el PECH ðŸš€' }
		],
		pred_salud: [
			{ type: 'cabecera', area: 'UNIDAD FUNCIONAL DE PERSONAL', badge: 'ðŸ©º CAMPANA PREVENTIVA', tone: 'red', banner: '', logo: logoPeCh, height: '170', position: 'center' },
			{ type: 'titulo', icon: '', text: 'COMUNICADO NÂ° 011-2026-APER', subtitle: 'Jornada de tamizaje dirigida a los servidores civiles del Campamento San Jose.' },
			{ type: 'aviso', tone: 'red', title: 'âš  IMPORTANTE: asistir en ayunas, 8 horas antes de la toma de muestras.', text: '' },
			{ type: 'parrafo', text: 'SE PONE EN CONOCIMIENTO DE LOS SERVIDORES CIVILES DEL CAMPAMENTO SAN JOSE, EN EL MARCO DEL CONVENIO SUSCRITO CON ESSALUD - PROGRAMA PREVENIR, QUE EL DIA MIERCOLES 13 DE MAYO DE 2026 SE LLEVARA A CABO UNA JORNADA DE TAMIZAJE, A PARTIR DE LAS 8:00 A.M.' },
			{ type: 'tarjetas', items: 'ðŸ—“|Fecha|Miercoles 13 de mayo de 2026\nâ°|Hora|08:00 a.m.\nðŸ“|Lugar|Campamento San Jose\nðŸ‘¥|Organiza|Unidad Funcional de Personal' },
			{ type: 'lista', tone: 'blue', title: 'ðŸ§ª Servicios de tamizaje', items: 'ðŸ©¸ Glucosa en ayunas\nðŸ§¬ HDL - Colesterol\nðŸŒ€ Trigliceridos\nðŸ“ Antropometria\nðŸ’— Presion arterial' },
			{ type: 'lista', tone: 'gray', title: 'ðŸ“Œ Indicaciones', items: 'âœ… Asistir puntualmente para una atencion ordenada.\nðŸ½ Mantener ayuno de 8 horas antes de la toma de muestras.\nðŸªª Acudir con documento de identificacion personal.' },
			{ type: 'firma', name: 'TRUJILLO, 12 DE MAYO DE 2026', role: 'UNIDAD FUNCIONAL DE PERSONAL\nPROYECTO ESPECIAL CHAVIMOCHIC' },
			{ type: 'pie', text: 'ðŸ©º Comunicado institucional - PROYECTO ESPECIAL CHAVIMOCHIC' }
		]
	};

	function uid() {
		return 'b_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 8);
	}

	function cloneConfig(value) {
		return JSON.parse(JSON.stringify(value || {}));
	}

	function escapeHtml(value) {
		return String(value || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function nl2br(value) {
		return escapeHtml(value).replace(/\n/g, '<br>');
	}

	function toneColor(tone) {
		const map = {
			red: { bg: '#fff1f2', border: '#ffb4ba', title: '#d71920', soft: '#fee2e2', solid: '#ef2b22' },
			green: { bg: '#ecfdf3', border: '#7be6a1', title: '#08783b', soft: '#d8f8e5', solid: '#16a34a' },
			blue: { bg: '#eff6ff', border: '#93c5fd', title: '#004d99', soft: '#dbeafe', solid: '#2563eb' },
			orange: { bg: '#fff7ed', border: '#fdba74', title: '#9a3412', soft: '#ffedd5', solid: '#f97316' },
			gray: { bg: '#f8fafc', border: '#d9dee3', title: '#111827', soft: '#eef2f7', solid: '#64748b' }
		};
		return map[tone] || map.blue;
	}

	function normalizeBlock(block) {
		const normalized = Object.assign(defaultBlock(block.type || 'parrafo'), block || {});
		normalized.id = normalized.id || uid();
		if (normalized.type === 'cabecera') {
			normalized.logo = normalized.logo || logoPeCh;
			normalized.banner = normalized.banner || imagenCabeceraPeCh;
			normalized.entidad = normalized.entidad || entidadPeCh;
			normalized.area = normalized.area || 'AREA DE INFORMATICA';
			normalized.height = normalized.height || '150';
			normalized.position = normalized.position || 'center';
		}
		return normalized;
	}

	function defaultBlock(type) {
		switch (type) {
			case 'cabecera':
				return { id: uid(), type: 'cabecera', logo: logoPeCh, banner: imagenCabeceraPeCh, entidad: entidadPeCh, area: 'AREA DE INFORMATICA', badge: 'AVISO IMPORTANTE', tone: 'blue', height: '150', position: 'center' };
			case 'meta':
				return { id: uid(), type: 'meta', text: 'ðŸ“£ Comunicado informativo | ðŸ“… ' + new Date().toLocaleDateString('es-PE') + ' | ðŸ•˜ ' + new Date().toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' }) };
			case 'aviso':
				return { id: uid(), type: 'aviso', tone: 'red', title: 'Aviso importante', text: 'Detalle el aviso destacado.' };
			case 'tarjetas':
				return { id: uid(), type: 'tarjetas', items: 'ðŸ—“|Fecha|Ingrese la fecha\nðŸ’»|Sistema|Ingrese el sistema\nâš™|Estado|Ingrese el estado' };
			case 'panel':
				return { id: uid(), type: 'panel', tone: 'blue', title: 'Informacion', text: 'Detalle la informacion del panel.' };
			case 'lista':
				return { id: uid(), type: 'lista', tone: 'blue', title: 'Indicaciones', items: 'âœ… Primera indicacion\nâœ… Segunda indicacion' };
			case 'adjunto':
				return { id: uid(), type: 'adjunto', tone: 'green', title: 'Archivo adjunto para descarga', text: 'Puede descargar el archivo desde el siguiente enlace:', button: 'DESCARGAR ARCHIVO', fileName: 'documento.pdf', href: '#' };
			case 'pie':
				return { id: uid(), type: 'pie', text: 'ðŸ’» AREA DE INFORMATICA - PROYECTO ESPECIAL CHAVIMOCHIC\nMensaje informativo institucional. âœ…' };
			case 'titulo':
				return { id: uid(), type: 'titulo', icon: 'ðŸ“£', text: 'Titulo del comunicado', subtitle: '' };
			case 'imagen':
				return { id: uid(), type: 'imagen', src: '', alt: 'Imagen del comunicado' };
			case 'boton':
				return { id: uid(), type: 'boton', text: 'Abrir enlace', href: '#', tone: 'blue' };
			case 'firma':
				return { id: uid(), type: 'firma', name: 'Atentamente,', role: 'Area de Informatica' };
			case 'parrafo':
			default:
				return { id: uid(), type: 'parrafo', text: 'Nuevo parrafo editable.' };
		}
	}

	function createDefaultPreset() {
		return ['cabecera', 'titulo', 'meta', 'aviso', 'parrafo', 'pie'].map(defaultBlock);
	}

	function getPresetBlocks(key) {
		const configuredPreset = presets[key || ''];
		return Array.isArray(configuredPreset) && configuredPreset.length ? cloneConfig(configuredPreset) : createDefaultPreset();
	}

	function readInitialBlocks() {
		const node = document.getElementById('comInitialBlocks');
		try {
			const parsed = JSON.parse(node ? node.textContent : '[]');
			return Array.isArray(parsed) && parsed.length ? parsed.map(normalizeBlock) : presets[''].map(normalizeBlock);
		} catch (e) {
			return presets[''].map(normalizeBlock);
		}
	}

	let blocks = readInitialBlocks();

	function blockActions() {
		return '' +
			'<div class="com-block-actions">' +
			'<button type="button" class="btn btn-icon btn-sm js-block-up" title="Subir"><i class="ti ti-arrow-up"></i></button>' +
			'<button type="button" class="btn btn-icon btn-sm js-block-down" title="Bajar"><i class="ti ti-arrow-down"></i></button>' +
			'<button type="button" class="btn btn-icon btn-sm text-danger js-block-delete" title="Eliminar"><i class="ti ti-trash"></i></button>' +
			'</div>';
	}

	function blockMeta(type) {
		const map = {
			cabecera: { icon: 'ti-layout-navbar', label: 'Cabecera' },
			titulo: { icon: 'ti-heading', label: 'Titulo' },
			meta: { icon: 'ti-calendar-time', label: 'Metadatos' },
			aviso: { icon: 'ti-alert-triangle', label: 'Aviso' },
			imagen: { icon: 'ti-photo', label: 'Imagen' },
			parrafo: { icon: 'ti-align-left', label: 'Parrafo' },
			tarjetas: { icon: 'ti-layout-grid', label: 'Tarjetas' },
			panel: { icon: 'ti-box', label: 'Panel' },
			lista: { icon: 'ti-list-check', label: 'Lista' },
			adjunto: { icon: 'ti-paperclip', label: 'Archivo adjunto' },
			boton: { icon: 'ti-click', label: 'Boton' },
			firma: { icon: 'ti-signature', label: 'Firma' },
			pie: { icon: 'ti-layout-bottombar', label: 'Pie' }
		};
		return map[type] || { icon: 'ti-box', label: 'Bloque' };
	}

	function blockHead(type) {
		const meta = blockMeta(type);
		return '<div class="com-block-head"><i class="ti ' + meta.icon + '"></i><span>' + meta.label + '</span></div>';
	}

	function input(label, field, value, type, placeholder) {
		const hint = placeholder ? ' placeholder="' + escapeHtml(placeholder) + '"' : '';
		return '<div class="mb-2"><label class="form-label small mb-1 com-field-label">' + label + '</label><input type="' + (type || 'text') + '" class="form-control form-control-sm" data-field="' + field + '" value="' + escapeHtml(value) + '"' + hint + '></div>';
	}

	function textarea(label, field, value, rows) {
		return '<div class="mb-2"><label class="form-label small mb-1 com-field-label">' + label + '</label><textarea class="form-control form-control-sm" data-field="' + field + '" rows="' + (rows || 3) + '">' + escapeHtml(value) + '</textarea></div>';
	}

	function toneSelect(value) {
		const tones = [
			{ value: 'blue', label: 'AZUL' },
			{ value: 'green', label: 'VERDE' },
			{ value: 'red', label: 'ROJO' },
			{ value: 'orange', label: 'NARANJA' },
			{ value: 'gray', label: 'GRIS' }
		];
		return '<div class="mb-2"><label class="form-label small mb-1 com-field-label">Color</label><select class="form-select form-select-sm" data-field="tone">' +
			tones.map(function(tone) {
				return '<option value="' + tone.value + '"' + (tone.value === value ? ' selected' : '') + '>' + tone.label + '</option>';
			}).join('') +
			'</select></div>';
	}

	function renderBlock(block) {
		const selected = block.id === selectedId ? ' ' + selectedClass : '';
		let html = '<div class="com-block' + selected + '" draggable="true" data-id="' + escapeHtml(block.id) + '" data-type="' + escapeHtml(block.type) + '">' + blockActions() + blockHead(block.type);
		if (block.type === 'cabecera') {
			const color = toneColor(block.tone);
			const headerHeight = Math.max(90, Math.min(260, parseInt(block.height || 150, 10) || 150));
			const headerPosition = block.position || 'center';
			const headerBg = block.banner ? 'background-image:linear-gradient(90deg,rgba(0,77,153,0.72),rgba(0,0,0,0.12)),url(' + escapeHtml(block.banner) + ');' : 'background:#0b4f7d;';
			html += '<div style="' + headerBg + 'color:#fff;padding:18px;border-radius:12px;background-size:cover;background-position:' + escapeHtml(headerPosition) + ';min-height:' + headerHeight + 'px;">' +
				'<div class="fw-bold fs-3">' + escapeHtml(marcaPeCh) + '</div><div>' + escapeHtml(block.entidad || entidadPeCh) + '</div>' +
				'<span class="badge mt-2" style="background:' + color.solid + ';color:#fff;">' + escapeHtml(block.badge) + '</span></div>' +
				'<div class="com-control-panel">' + input('Logo URL', 'logo', block.logo, 'url', logoPeCh) + input('Imagen de cabecera URL', 'banner', block.banner, 'url', imagenCabeceraPeCh) +
				input('Entidad', 'entidad', block.entidad || entidadPeCh) + input('Area', 'area', block.area) + input('Etiqueta', 'badge', block.badge) + toneSelect(block.tone) + '</div>';
		} else if (block.type === 'titulo') {
			html += '<div class="com-control-panel">' + input('Icono', 'icon', block.icon) + '<h1 contenteditable="true" data-field="text" style="margin:8px 0 12px;color:#111827;font-size:18px;line-height:1.2;font-weight:800;text-align:center;">' + escapeHtml(block.text) + '</h1>' +
				input('Subtitulo', 'subtitle', block.subtitle) + '</div>';
		} else if (block.type === 'meta') {
			html += '<div class="com-control-panel">' + input('Metadatos', 'text', block.text) + '</div>';
		} else if (block.type === 'aviso') {
			html += '<div class="com-control-panel">' + toneSelect(block.tone) + input('Titulo', 'title', block.title) + textarea('Texto', 'text', block.text, 3) + '</div>';
		} else if (block.type === 'tarjetas') {
			html += '<div class="com-control-panel">' + textarea('Tarjetas: icono|titulo|texto', 'items', block.items, 5) + '</div>';
		} else if (block.type === 'panel') {
			html += '<div class="com-control-panel">' + toneSelect(block.tone) + input('Titulo', 'title', block.title) + textarea('Texto', 'text', block.text, 4) + '</div>';
		} else if (block.type === 'lista') {
			html += '<div class="com-control-panel">' + toneSelect(block.tone) + input('Titulo', 'title', block.title) + textarea('Items', 'items', block.items, 5) + '</div>';
		} else if (block.type === 'adjunto') {
			html += '<div class="com-control-panel">' + toneSelect(block.tone || 'green') + input('Titulo', 'title', block.title) + textarea('Descripcion', 'text', block.text, 2) + input('Texto del boton', 'button', block.button) + input('Nombre visible del archivo', 'fileName', block.fileName) + input('URL del archivo', 'href', block.href, 'url') + '</div>';
		} else if (block.type === 'pie') {
			html += '<div class="com-control-panel">' + textarea('Pie institucional', 'text', block.text, 3) + '</div>';
		} else if (block.type === 'parrafo') {
			html += '<div class="com-control-panel">' + textarea('Parrafo', 'text', block.text, 5) + '</div>';
		} else if (block.type === 'imagen') {
			html += '<div style="text-align:center;">' +
				(block.src ? '<img src="' + escapeHtml(block.src) + '" alt="' + escapeHtml(block.alt) + '" style="max-width:100%;height:auto;border:0;display:block;margin:0 auto;border-radius:8px;">' : '<div class="text-secondary py-5 border rounded">Seleccione o cargue una imagen</div>') +
				'<div class="com-control-panel text-start">' + input('URL de imagen', 'src', block.src, 'url') + input('Texto alternativo', 'alt', block.alt) + '</div>' +
				'</div>';
		} else if (block.type === 'boton') {
			html += '<div class="com-control-panel">' + toneSelect(block.tone || 'blue') + input('Texto', 'text', block.text) + input('URL', 'href', block.href, 'url') + '</div>';
		} else if (block.type === 'firma') {
			html += '<div class="com-control-panel">' + input('Firma / ciudad / cierre', 'name', block.name) + textarea('Detalle', 'role', block.role, 2) + '</div>';
		}
		html += '</div>';
		return html;
	}

	function render() {
		if (!blocks.length) {
			email.innerHTML = '<div class="com-drop-empty">Arrastre bloques aqui para construir el comunicado.</div>';
		} else {
			email.innerHTML = blocks.map(renderBlock).join('');
		}
		bindBlockEvents();
		updatePreview();
	}

	function collectState() {
		const next = [];
		email.querySelectorAll('.com-block').forEach(function(node) {
			const id = node.dataset.id;
			const existing = blocks.find(function(block) { return block.id === id; }) || defaultBlock(node.dataset.type);
			const copy = Object.assign({}, existing, { id: id, type: node.dataset.type });
			node.querySelectorAll('[data-field]').forEach(function(field) {
				copy[field.dataset.field] = field.matches('input, textarea, select') ? field.value.trim() : field.textContent.trim();
			});
			next.push(copy);
		});
		blocks = next;
	}

	function moveBlock(id, delta) {
		collectState();
		const index = blocks.findIndex(function(block) { return block.id === id; });
		const nextIndex = index + delta;
		if (index < 0 || nextIndex < 0 || nextIndex >= blocks.length) {
			return;
		}
		const item = blocks.splice(index, 1)[0];
		blocks.splice(nextIndex, 0, item);
		selectedId = id;
		render();
	}

	function bindBlockEvents() {
		email.querySelectorAll('.com-block').forEach(function(node) {
			node.addEventListener('click', function(event) {
				selectedId = node.dataset.id;
				email.querySelectorAll('.com-block').forEach(function(el) { el.classList.remove(selectedClass); });
				node.classList.add(selectedClass);
				event.stopPropagation();
			});
			node.addEventListener('input', function() {
				collectState();
				updatePreview();
			});
			node.addEventListener('change', function() {
				collectState();
				updatePreview();
			});
			node.addEventListener('dragstart', function(event) {
				collectState();
				dragBlockId = node.dataset.id;
				event.dataTransfer.setData('text/plain', dragBlockId);
				event.dataTransfer.effectAllowed = 'move';
			});
			node.querySelector('.js-block-up').addEventListener('click', function(event) {
				event.stopPropagation();
				moveBlock(node.dataset.id, -1);
			});
			node.querySelector('.js-block-down').addEventListener('click', function(event) {
				event.stopPropagation();
				moveBlock(node.dataset.id, 1);
			});
			node.querySelector('.js-block-delete').addEventListener('click', function(event) {
				event.stopPropagation();
				collectState();
				blocks = blocks.filter(function(block) { return block.id !== node.dataset.id; });
				if (selectedId === node.dataset.id) {
					selectedId = null;
				}
				render();
			});
		});
	}

	function getDropIndex(event) {
		const nodes = Array.from(email.querySelectorAll('.com-block'));
		for (let i = 0; i < nodes.length; i++) {
			const rect = nodes[i].getBoundingClientRect();
			if (event.clientY < rect.top + rect.height / 2) {
				return i;
			}
		}
		return nodes.length;
	}

	canvas.addEventListener('dragover', function(event) {
		event.preventDefault();
		event.dataTransfer.dropEffect = 'move';
	});

	canvas.addEventListener('drop', function(event) {
		event.preventDefault();
		collectState();
		const type = event.dataTransfer.getData('application/x-com-block-type');
		const id = event.dataTransfer.getData('text/plain') || dragBlockId;
		const index = getDropIndex(event);
		if (type) {
			blocks.splice(index, 0, defaultBlock(type));
		} else if (id) {
			const currentIndex = blocks.findIndex(function(block) { return block.id === id; });
			if (currentIndex >= 0) {
				const item = blocks.splice(currentIndex, 1)[0];
				blocks.splice(currentIndex < index ? index - 1 : index, 0, item);
				selectedId = item.id;
			}
		}
		render();
	});

	document.querySelectorAll('[data-block-type]').forEach(function(btn) {
		btn.addEventListener('dragstart', function(event) {
			event.dataTransfer.setData('application/x-com-block-type', btn.dataset.blockType);
			event.dataTransfer.effectAllowed = 'copy';
		});
		btn.addEventListener('click', function() {
			collectState();
			const block = defaultBlock(btn.dataset.blockType);
			blocks.push(block);
			selectedId = block.id;
			render();
		});
	});

	function splitLines(value) {
		return String(value || '').split(/\r?\n/).map(function(line) { return line.trim(); }).filter(Boolean);
	}

	function blockToHtml(block) {
		const color = toneColor(block.tone);
		if (block.type === 'cabecera') {
			const headerHeight = Math.max(90, Math.min(260, parseInt(block.height || 150, 10) || 150));
			const headerPosition = block.position || 'center';
			const bg = block.banner ? 'background-image:linear-gradient(90deg,rgba(0,77,153,0.72),rgba(0,0,0,0.12)),url(' + escapeHtml(block.banner) + ');' : 'background:#0b4f7d;';
			return '<tr><td height="' + headerHeight + '" style="' + bg + 'background-size:cover;background-position:' + escapeHtml(headerPosition) + ';background-repeat:no-repeat;padding:18px 24px;color:#ffffff;height:' + headerHeight + 'px;">' +
				'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>' +
				'<td style="vertical-align:top;"><img src="' + escapeHtml(block.logo || logoPeCh) + '" alt="' + escapeHtml(marcaPeCh) + '" style="max-width:235px;height:auto;display:block;border:0;"><div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:800;line-height:1.2;margin-top:8px;">ðŸ› ' + escapeHtml(block.entidad || entidadPeCh) + '</div><div style="font-family:Arial,Helvetica,sans-serif;font-size:11px;">ðŸ’» ' + escapeHtml(block.area) + '</div></td>' +
				'<td align="right" style="vertical-align:top;"><span style="display:inline-block;background:' + color.solid + ';color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:10px;font-weight:800;border-radius:18px;padding:8px 15px;">' + escapeHtml(block.badge) + '</span></td>' +
				'</tr></table></td></tr>';
		}
		if (block.type === 'titulo') {
			return '<tr><td style="padding:18px 26px 6px 26px;text-align:center;"><h1 style="margin:0;color:#111827;font-family:Arial,Helvetica,sans-serif;font-size:18px;line-height:1.22;font-weight:800;">' + escapeHtml(block.icon) + ' ' + escapeHtml(block.text) + '</h1>' +
				(block.subtitle ? '<div style="margin-top:8px;color:#334155;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.35;">' + escapeHtml(block.subtitle) + '</div>' : '') + '</td></tr>';
		}
		if (block.type === 'meta') {
			return '<tr><td style="padding:0 26px 14px 26px;text-align:center;color:#334155;font-family:Arial,Helvetica,sans-serif;font-size:10px;">' + escapeHtml(block.text) + '</td></tr>';
		}
		if (block.type === 'aviso') {
			return '<tr><td style="padding:9px 26px;"><div style="background:' + color.bg + ';border:1px solid ' + color.border + ';border-radius:9px;padding:11px 13px;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">' +
				'<div style="color:' + color.title + ';font-weight:800;font-size:13px;">' + escapeHtml(block.title) + '</div>' +
				(block.text ? '<div style="margin-top:6px;font-size:11px;line-height:1.45;">' + nl2br(block.text) + '</div>' : '') + '</div></td></tr>';
		}
		if (block.type === 'parrafo') {
			return '<tr><td style="padding:10px 26px;"><div style="font-family:Arial,Helvetica,sans-serif;color:#0f172a;font-size:12px;line-height:1.65;text-align:left;">' + nl2br(block.text) + '</div></td></tr>';
		}
		if (block.type === 'imagen') {
			return block.src ? '<tr><td style="padding:10px 26px;text-align:center;"><img src="' + escapeHtml(block.src) + '" alt="' + escapeHtml(block.alt) + '" style="max-width:100%;height:auto;border:0;display:block;margin:0 auto;border-radius:9px;"></td></tr>' : '';
		}
		if (block.type === 'tarjetas') {
			const cards = splitLines(block.items).map(function(line) {
				const p = line.split('|');
				return '<td style="width:33.33%;padding:5px;"><div style="background:#f8fafc;border:1px solid #d9e4f2;border-radius:9px;padding:12px;font-family:Arial,Helvetica,sans-serif;min-height:62px;"><div style="font-weight:800;color:#111827;font-size:11px;">' + escapeHtml(p[0] || '') + ' ' + escapeHtml(p[1] || '') + '</div><div style="color:#334155;font-size:10px;line-height:1.35;margin-top:6px;">' + escapeHtml(p.slice(2).join('|')) + '</div></div></td>';
			});
			let rows = '';
			for (let i = 0; i < cards.length; i += 3) {
				rows += '<tr>' + cards.slice(i, i + 3).join('') + '</tr>';
			}
			return '<tr><td style="padding:9px 21px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">' + rows + '</table></td></tr>';
		}
		if (block.type === 'panel') {
			return '<tr><td style="padding:9px 26px;"><div style="background:' + color.bg + ';border:1px solid ' + color.border + ';border-radius:10px;padding:14px;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">' +
				'<div style="color:' + color.title + ';font-weight:800;font-size:13px;margin-bottom:8px;">' + escapeHtml(block.title) + '</div><div style="font-size:11px;line-height:1.55;">' + nl2br(block.text) + '</div></div></td></tr>';
		}
		if (block.type === 'lista') {
			const items = splitLines(block.items).map(function(item) {
				return '<div style="margin:5px 0;font-size:11px;line-height:1.38;">' + escapeHtml(item) + '</div>';
			}).join('');
			return '<tr><td style="padding:9px 26px;"><div style="background:' + color.bg + ';border:1px solid ' + color.border + ';border-radius:10px;padding:14px;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">' +
				(block.title ? '<div style="color:' + color.title + ';font-weight:800;font-size:13px;margin-bottom:8px;">' + escapeHtml(block.title) + '</div>' : '') + items + '</div></td></tr>';
		}
		if (block.type === 'boton') {
			return '<tr><td style="padding:12px 26px;text-align:center;"><a href="' + escapeHtml(block.href || '#') + '" style="display:inline-block;background:' + color.solid + ';color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:11px;line-height:1.2;text-decoration:none;padding:11px 22px;border-radius:7px;font-weight:800;">' + escapeHtml(block.text) + '</a></td></tr>';
		}
		if (block.type === 'adjunto') {
			return '<tr><td style="padding:9px 26px;"><div style="background:' + color.bg + ';border:1px solid ' + color.border + ';border-radius:10px;padding:16px;font-family:Arial,Helvetica,sans-serif;color:#0f172a;text-align:left;">' +
				'<div style="color:' + color.title + ';font-weight:800;font-size:13px;margin-bottom:10px;">ðŸ“ ' + escapeHtml(block.title) + '</div>' +
				'<div style="font-size:12px;line-height:1.5;margin-bottom:14px;">' + nl2br(block.text) + '</div>' +
				'<div style="text-align:center;"><a href="' + escapeHtml(block.href || '#') + '" style="display:inline-block;background:' + color.solid + ';color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.2;text-decoration:none;padding:12px 28px;border-radius:8px;font-weight:800;">â¬‡ ' + escapeHtml(block.button || 'DESCARGAR ARCHIVO') + '</a>' +
				(block.fileName ? '<div style="font-size:11px;color:#0f5132;margin-top:10px;">' + escapeHtml(block.fileName) + '</div>' : '') +
				'</div></div></td></tr>';
		}
		if (block.type === 'firma') {
			return '<tr><td style="padding:14px 26px 18px 26px;"><div style="font-family:Arial,Helvetica,sans-serif;color:#0f172a;text-align:center;"><div style="font-weight:800;font-size:12px;">' + escapeHtml(block.name) + '</div><div style="font-size:11px;line-height:1.45;margin-top:8px;">' + nl2br(block.role) + '</div></div></td></tr>';
		}
		if (block.type === 'pie') {
			return '<tr><td style="padding:12px 24px;background:#eaf0f7;border-top:1px solid #cbd5e1;text-align:center;"><div style="font-family:Arial,Helvetica,sans-serif;color:#334155;font-size:10px;line-height:1.35;">' + nl2br(block.text) + '</div></td></tr>';
		}
		return '';
	}

	function generateHtml() {
		collectState();
		const rows = blocks.map(blockToHtml).join('');
		return '<table role="presentation" width="760" cellspacing="0" cellpadding="0" border="0" align="center" style="width:760px;max-width:100%;margin:0 auto;background:#ffffff;border-collapse:separate;border-spacing:0;border:1px solid #cbd5e1;border-radius:12px;overflow:hidden;">' +
			rows +
			'</table>';
	}

	function updatePreview() {
		generateHtml();
	}

	selectorPlantilla.addEventListener('change', function() {
		const option = selectorPlantilla.options[selectorPlantilla.selectedIndex];
		let nextBlocks = [];
		if (selectorPlantilla.value.indexOf('db_') === 0) {
			try {
				nextBlocks = JSON.parse(option.dataset.json || '[]');
			} catch (e) {
				nextBlocks = [];
			}
		} else {
			nextBlocks = getPresetBlocks(selectorPlantilla.value);
		}
		blocks = (Array.isArray(nextBlocks) && nextBlocks.length ? nextBlocks : getPresetBlocks('')).map(normalizeBlock);
		selectedId = null;
		const firstTitle = blocks.find(function(block) { return block.type === 'titulo' && block.text; });
		if (firstTitle && !document.getElementById('tituloComunicado').value.trim()) {
			document.getElementById('tituloComunicado').value = firstTitle.text;
		}
		render();
	});

	document.querySelectorAll('.js-insertar-archivo').forEach(function(btn) {
		btn.addEventListener('click', function() {
			insertFile(btn.dataset.url, btn.dataset.tipo);
		});
	});

	function insertFile(url, tipo) {
		collectState();
		if (tipo === 'IMAGEN') {
			let block = selectedId ? blocks.find(function(item) { return item.id === selectedId && (item.type === 'imagen' || item.type === 'cabecera'); }) : null;
			if (!block) {
				block = defaultBlock('imagen');
				blocks.push(block);
				selectedId = block.id;
			}
			if (block.type === 'cabecera') {
				block.banner = url;
			} else {
				block.src = url;
			}
			render();
			return;
		}
		const attachment = defaultBlock('adjunto');
		attachment.href = url;
		attachment.fileName = url.split('/').pop() || 'documento';
		blocks.push(attachment);
		selectedId = attachment.id;
		render();
	}

	document.getElementById('formUploadEditor').addEventListener('submit', function(event) {
		event.preventDefault();
		const form = event.currentTarget;
		const data = new FormData(form);
		if (!data.get('archivo') || !data.get('archivo').name) {
			window.comNotifySafe('warning', 'Seleccione un archivo', 'Debe elegir un archivo para subir.');
			return;
		}
		fetch('index.php?module=comunicados&action=subirArchivoAjax', {
			method: 'POST',
			body: data,
			headers: { 'X-Requested-With': 'XMLHttpRequest' }
		}).then(function(response) {
			return response.json();
		}).then(function(res) {
			if (!res.success) {
				window.comNotifySafe('danger', 'No se pudo subir', res.message || '');
				return;
			}
			window.comNotifySafe('success', 'Archivo cargado', 'La ruta ya esta disponible.');
			form.reset();
			insertFile(res.archivo.UrlPublica, res.archivo.TipoArchivo);
		}).catch(function() {
			window.comNotifySafe('danger', 'Error de conexion', 'No se pudo conectar con el servidor.');
		});
	});

	document.getElementById('btnGuardarComunicado').addEventListener('click', function() {
		collectState();
		const firstTitle = blocks.find(function(block) { return block.type === 'titulo' && block.text; });
		let titulo = document.getElementById('tituloComunicado').value.trim();
		if (!titulo && firstTitle) {
			titulo = firstTitle.text;
			document.getElementById('tituloComunicado').value = titulo;
		}
		if (!titulo) {
			window.comNotifySafe('warning', 'Titulo obligatorio', 'Debe ingresar el titulo del comunicado.');
			return;
		}
		const idPlantillaRaw = selectorPlantilla.value.indexOf('db_') === 0 ? selectorPlantilla.value.replace('db_', '') : '';
		const payload = {
			IdComunicado: shell.dataset.id || '',
			IdPlantilla: idPlantillaRaw,
			TituloComunicado: titulo,
			AsuntoCorreo: document.getElementById('asuntoCorreo').value.trim(),
			EstadoComunicado: document.getElementById('estadoComunicado').value,
			ContenidoJson: JSON.stringify(blocks),
			HtmlFinal: generateHtml()
		};

		fetch('index.php?module=comunicados&action=guardarComunicadoAjax', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
			body: JSON.stringify(payload)
		}).then(function(response) {
			return response.json();
		}).then(function(res) {
			window.comNotifySafe(res.success ? 'success' : 'danger', res.success ? 'Comunicado guardado' : 'No se pudo guardar', res.message || '');
			if (res.success && res.id) {
				shell.dataset.id = String(res.id);
			}
		}).catch(function() {
			window.comNotifySafe('danger', 'Error de conexion', 'No se pudo conectar con el servidor.');
		});
	});

	document.getElementById('btnGuardarComoPlantilla').addEventListener('click', function() {
		collectState();
		const firstTitle = blocks.find(function(block) { return block.type === 'titulo' && block.text; });
		const suggestedName = 'Plantilla - ' + (firstTitle && firstTitle.text ? firstTitle.text : (document.getElementById('tituloComunicado').value.trim() || 'Comunicado'));
		window.comPromptSafe({
			titulo: 'Guardar como plantilla',
			mensaje: 'Ingrese el nombre de la nueva plantilla.',
			valor: suggestedName,
			textoAceptar: 'Crear plantilla',
			requerido: true
		}).then(function(nombre) {
			if (nombre === null) {
				return;
			}

			fetch('index.php?module=comunicados&action=guardarPlantillaAjax', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
				body: JSON.stringify({
					NombrePlantilla: nombre.trim(),
					DescripcionPlantilla: 'Generada desde el editor de comunicados',
					ContenidoJson: JSON.stringify(blocks),
					HtmlBase: generateHtml()
				})
			}).then(function(response) {
				return response.json();
			}).then(function(res) {
				window.comNotifySafe(res.success ? 'success' : 'danger', res.success ? 'Plantilla creada' : 'No se pudo crear', res.message || '');
			}).catch(function() {
				window.comNotifySafe('danger', 'Error de conexion', 'No se pudo conectar con el servidor.');
			});
		});
	});

	document.getElementById('btnCopiarHtml').addEventListener('click', function() {
		const html = generateHtml();
		function done(ok) {
			window.comNotifySafe(ok ? 'success' : 'danger', ok ? 'HTML copiado' : 'No se pudo copiar', ok ? 'Pegue el contenido en su correo institucional.' : 'Intente copiar nuevamente.');
		}
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(html).then(function() { done(true); }).catch(function() { done(false); });
			return;
		}
		const temporal = document.createElement('textarea');
		temporal.value = html;
		temporal.style.position = 'fixed';
		temporal.style.left = '-9999px';
		temporal.style.top = '0';
		document.body.appendChild(temporal);
		temporal.focus();
		temporal.select();
		const copied = document.execCommand('copy');
		document.body.removeChild(temporal);
		done(copied);
	});

	email.addEventListener('click', function() {
		selectedId = null;
		email.querySelectorAll('.com-block').forEach(function(el) { el.classList.remove(selectedClass); });
	});

	render();
})();

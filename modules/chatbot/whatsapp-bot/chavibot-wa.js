/**
 * chavibot-wa.js — ChaviBot WhatsApp Personal (Baileys 6.x)
 * CHAVIMOCHIC · Sistema de Gestión TI
 *
 * IMPORTANTE: buttonsMessage y listMessage YA NO FUNCIONAN en WhatsApp
 * personal (Meta los bloqueó en 2023). Este bot usa:
 *
 *   ✅ Poll (encuesta) → simula el menú con opciones clicables
 *   ✅ Texto enriquecido (*negrita* _cursiva_) → respuestas y confirmaciones
 *   ✅ Reaction → feedback visual
 *
 * FLUJO:
 *   Login (usuario + contraseña) → Poll "Ver Menú" → submenús → consultas
 */

const { default: makeWASocket,
        useMultiFileAuthState,
        DisconnectReason,
        fetchLatestBaileysVersion,
        makeCacheableSignalKeyStore } = require('@whiskeysockets/baileys');
const pino   = require('pino');
const qrcode = require('qrcode-terminal');
const fetch  = (...a) => import('node-fetch').then(({ default: f }) => f(...a));
const fs     = require('fs');
const path   = require('path');

// ─── CONFIGURACIÓN ────────────────────────────────────────────────────────
const CFG = {
    PHP_URL:            'http://localhost/gestionTI/modules/chatbot/ajax/chavibot.ajax.php',
    NODE_TOKEN:         'chavibot_node_2026',
    SESSION_DIR:        path.join(__dirname, 'wa_session'),
    TIMEOUT_MIN:        120,
    MAX_INTENTOS_LOGIN: 3,
};

// ─── MENÚ COMO POLL ────────────────────────────────────────────────────────
// Los polls SÍ funcionan en WhatsApp personal y se ven como opciones clicables
const MENU_POLL = {
    name: '🤖 ChaviBot — ¿Qué deseas consultar?',
    values: [
        '🎫 Soporte / Tickets',
        '💻 Inventario y equipos',
        '🧪 Laboratorio',
        '🏢 Salas',
        '📜 Certificados',
        '💬 Consulta libre',
        '🚪 Cerrar sesión',
    ],
    selectableCount: 1,
};

const SUBMENUS_POLL = {
    '🎫 Soporte / Tickets': {
        name: '🎫 Soporte — ¿Qué consulta?',
        values: [
            '1· Tickets abiertos',
            '2· Tickets de alta prioridad',
            '3· Carga por técnico',
            '← Volver al menú',
        ],
        selectableCount: 1,
    },
    '💻 Inventario y equipos': {
        name: '💻 Inventario — ¿Qué consulta?',
        values: [
            '1· Equipos disponibles',
            '2· Estaciones asignadas',
            '3· Resumen por tipo',
            '← Volver al menú',
        ],
        selectableCount: 1,
    },
    '🧪 Laboratorio': {
        name: '🧪 Laboratorio — ¿Qué consulta?',
        values: [
            '1· Reactivos con stock bajo',
            '2· Reactivos por vencer',
            '3· Solicitudes pendientes',
            '← Volver al menú',
        ],
        selectableCount: 1,
    },
    '🏢 Salas': {
        name: '🏢 Salas — ¿Qué consulta?',
        values: [
            '1· Reservas de hoy',
            '2· Reservas de mañana',
            '3· Lista de salas',
            '← Volver al menú',
        ],
        selectableCount: 1,
    },
    '📜 Certificados': {
        name: '📜 Certificados — ¿Qué consulta?',
        values: [
            '1· Por vencer (90 días)',
            '2· Ya vencidos',
            '← Volver al menú',
        ],
        selectableCount: 1,
    },
};

// Poll de confirmación (Acepto / Rechazo)
function pollConfirmacion(tema) {
    return {
        name: `¿Confirmar consulta sobre ${tema}?`,
        values: ['✅ Continuar', '❌ Cancelar'],
        selectableCount: 1,
    };
}

// ─── MAPEO SUBOPCIÓN → PREGUNTA ────────────────────────────────────────────
const CONSULTAS = {
    '🎫 Soporte / Tickets': {
        '1· Tickets abiertos':          '¿Qué tickets de soporte están abiertos?',
        '2· Tickets de alta prioridad': '¿Qué tickets tienen alta prioridad?',
        '3· Carga por técnico':         '¿Cuántos tickets tiene cada técnico asignado?',
    },
    '💻 Inventario y equipos': {
        '1· Equipos disponibles': '¿Qué equipos están disponibles sin asignar?',
        '2· Estaciones asignadas':'¿Qué estaciones de trabajo están asignadas?',
        '3· Resumen por tipo':    '¿Cuántos equipos hay en inventario por tipo?',
    },
    '🧪 Laboratorio': {
        '1· Reactivos con stock bajo': '¿Qué reactivos tienen stock bajo?',
        '2· Reactivos por vencer':     '¿Qué reactivos están por vencer?',
        '3· Solicitudes pendientes':   '¿Qué solicitudes de análisis están pendientes?',
    },
    '🏢 Salas': {
        '1· Reservas de hoy':    '¿Qué salas están reservadas hoy?',
        '2· Reservas de mañana': '¿Qué salas hay disponibles mañana?',
        '3· Lista de salas':     '¿Cuáles son todas las salas disponibles?',
    },
    '📜 Certificados': {
        '1· Por vencer (90 días)': '¿Qué certificados vencen pronto?',
        '2· Ya vencidos':          '¿Qué certificados ya están vencidos?',
    },
};

// ─── ESTADO DE SESIONES ────────────────────────────────────────────────────
const sesiones = new Map();
const logger   = pino({ level: 'silent' });

// ═══════════════════════════════════════════════════════════════════════════
// CONEXIÓN
// ═══════════════════════════════════════════════════════════════════════════
async function iniciar() {
    if (!fs.existsSync(CFG.SESSION_DIR))
        fs.mkdirSync(CFG.SESSION_DIR, { recursive: true });

    const { state, saveCreds } = await useMultiFileAuthState(CFG.SESSION_DIR);
    const { version }          = await fetchLatestBaileysVersion();

    console.log('\n' + '═'.repeat(55));
    console.log('  🤖  ChaviBot WhatsApp · CHAVIMOCHIC');
    console.log('  Menú via Polls (funciona en WA personal)');
    console.log('═'.repeat(55) + '\n');

    const sock = makeWASocket({
        version,
        auth: {
            creds: state.creds,
            keys:  makeCacheableSignalKeyStore(state.keys, logger),
        },
        logger,
        printQRInTerminal: false,
        browser: ['ChaviBot', 'Chrome', '120.0'],
        getMessage: async () => ({ conversation: '' }),
    });

    sock.ev.on('connection.update', async ({ connection, lastDisconnect, qr }) => {
        if (qr) {
            console.log('📱 ESCANEA EL QR:\n   WhatsApp → ⋮ → Dispositivos vinculados\n');
            qrcode.generate(qr, { small: true });
        }
        if (connection === 'open') {
            console.log(`\n✅ Conectado: +${sock.user?.id?.split(':')[0]}`);
            console.log('💬 Esperando mensajes...\n');
        }
        if (connection === 'close') {
            const code = lastDisconnect?.error?.output?.statusCode;
            if (code === DisconnectReason.loggedOut) {
                console.log('❌ Sesión cerrada. Borra wa_session/ y reinicia.');
                fs.rmSync(CFG.SESSION_DIR, { recursive: true, force: true });
            } else {
                console.log(`⚠️  Desconectado (${code}). Reconectando en 5s...`);
                setTimeout(iniciar, 5000);
            }
        }
    });

    sock.ev.on('creds.update', saveCreds);

    sock.ev.on('messages.upsert', async ({ messages, type }) => {
        if (type !== 'notify') return;

        for (const msg of messages) {
            if (msg.key.fromMe)                       continue;
            if (msg.key.remoteJid?.endsWith('@g.us')) continue;

            const jid      = msg.key.remoteJid;
            const telefono = jid.split('@')[0];

            // Extraer texto o selección de poll
            const texto     = extraerTexto(msg);
            const pollVoto  = extraerPollVoto(msg);

            if (!texto && !pollVoto) continue;

            const hora = new Date().toLocaleTimeString('es-PE', { hour:'2-digit', minute:'2-digit' });
            console.log(`📨 [${hora}] +${telefono}: ${pollVoto || texto?.slice(0,55)}`);

            try {
                await procesarMensaje(sock, jid, telefono, texto, pollVoto);
            } catch (e) {
                console.error(`❌ +${telefono}:`, e.message);
                await txt(sock, jid, '⚠️ Error interno. Escribe *menú* para continuar.');
            }
        }
    });
}

// Extrae texto plano del mensaje
function extraerTexto(msg) {
    return (
        msg.message?.conversation              ||
        msg.message?.extendedTextMessage?.text ||
        ''
    ).trim();
}

// Extrae la opción seleccionada en un poll
function extraerPollVoto(msg) {
    const upd = msg.message?.pollUpdateMessage;
    if (!upd) return null;
    try {
        const votos = upd.vote?.selectedOptions;
        if (votos && votos.length > 0) return votos[0].toString('utf8').trim();
        return null;
    } catch { return null; }
}

// ═══════════════════════════════════════════════════════════════════════════
// PROCESADOR PRINCIPAL
// ═══════════════════════════════════════════════════════════════════════════
async function procesarMensaje(sock, jid, telefono, texto, pollVoto) {
    let ses = obtenerSesion(telefono);

    // Expirar sesión
    if (ses.autenticado && Date.now() - ses.ts > CFG.TIMEOUT_MIN * 60 * 1000) {
        resetSesion(telefono); ses = obtenerSesion(telefono);
        await txt(sock, jid, '⏰ Sesión expirada.\nEscribe tu *usuario* para volver a ingresar.');
        return;
    }
    ses.ts = Date.now();

    // Login
    if (!ses.autenticado) {
        await flujoLogin(sock, jid, telefono, texto || pollVoto, ses);
        return;
    }

    // Voto en poll
    if (pollVoto) {
        await procesarPoll(sock, jid, telefono, pollVoto, ses);
        return;
    }

    // Texto libre
    await procesarTexto(sock, jid, telefono, texto, ses);
}

// ═══════════════════════════════════════════════════════════════════════════
// LOGIN CON usuario + contraseña
// ═══════════════════════════════════════════════════════════════════════════
async function flujoLogin(sock, jid, telefono, entrada, ses) {
    switch (ses.paso) {

        case 'inicio':
            ses.paso = 'esperando_usuario';
            await txt(sock, jid,
                '👋 ¡Hola! Soy *ChaviBot*, el asistente de TI de CHAVIMOCHIC. 🤖\n\n' +
                '🔐 Escribe tu *nombre de usuario* del sistema:'
            );
            break;

        case 'esperando_usuario':
            if (!entrada) return;
            ses.usuarioPendiente = entrada.trim();
            ses.paso             = 'esperando_contrasena';
            await txt(sock, jid,
                `👤 Usuario: *${ses.usuarioPendiente}*\n\n` +
                '🔑 Escribe tu *contraseña*:\n' +
                '_Borra este mensaje después de enviarlo por seguridad._'
            );
            break;

        case 'esperando_contrasena':
            if (!entrada) return;
            await autenticar(sock, jid, telefono, entrada, ses);
            break;
    }
}

async function autenticar(sock, jid, telefono, contrasena, ses) {
    await txt(sock, jid, '⏳ _Verificando credenciales..._');

    const res = await php('wa_login', {
        telefono,
        usuario:    ses.usuarioPendiente,
        contrasena: contrasena,
    });

    if (!res || res.error || !res.usuario) {
        ses.intentosLogin    = (ses.intentosLogin || 0) + 1;
        ses.paso             = 'esperando_usuario';
        ses.usuarioPendiente = null;

        if (ses.intentosLogin >= CFG.MAX_INTENTOS_LOGIN) {
            await txt(sock, jid,
                `🚫 *${ses.intentosLogin} intentos fallidos.*\n\n` +
                'Acceso bloqueado. Contacta a TI: ☎️ *interno 123*'
            );
            resetSesion(telefono);
            return;
        }

        await txt(sock, jid,
            `❌ *Usuario o contraseña incorrectos.*\n` +
            `_(Intento ${ses.intentosLogin}/${CFG.MAX_INTENTOS_LOGIN})_\n\n` +
            'Escribe tu *usuario* para intentar de nuevo:'
        );
        return;
    }

    const u = res.usuario;
    Object.assign(ses, {
        autenticado: true, paso: 'menu',
        idUsuario:   u.id_usuario, usuario: u.usuario,
        nombres:     u.nombres,   apellidos: u.apellidos || '',
        rol:         u.rol || 'usuario', area: u.area || '',
        sessionId:   `wa_${telefono}_${Date.now()}`,
        intentosLogin: 0, submenuActivo: null,
    });

    await php('wa_registrar', {
        telefono, idUsuario: ses.idUsuario,
        nombres: ses.nombres, apellidos: ses.apellidos,
        rol: ses.rol, area: ses.area,
    });

    // Bienvenida con reacción visual
    const msgBienvenida = await txt(sock, jid,
        `✅ *¡Bienvenido/a, ${ses.nombres.split(' ')[0]}!*\n` +
        (ses.area ? `📂 Área: _${ses.area}_\n` : '') +
        `🎭 Rol: _${ses.rol}_`
    );

    await delay(800);
    await enviarMenuPoll(sock, jid, ses);
}

// ═══════════════════════════════════════════════════════════════════════════
// ENVIAR MENÚ COMO POLL
// ═══════════════════════════════════════════════════════════════════════════
async function enviarMenuPoll(sock, jid, ses) {
    // Primero un texto intro (como el BCP: "selecciona Ver Menú o escribe")
    await txt(sock, jid,
        `Por favor selecciona una opción del menú o escribe tu consulta directamente.\n\n` +
        `_Escribe *menú* para ver las opciones._`
    );
    await delay(400);

    // El poll aparece como un mensaje interactivo con opciones clicables
    await sock.sendMessage(jid, {
        poll: {
            name:            MENU_POLL.name,
            values:          MENU_POLL.values,
            selectableCount: MENU_POLL.selectableCount,
        },
    });
}

async function enviarSubMenuPoll(sock, jid, seccionNombre) {
    const subPoll = SUBMENUS_POLL[seccionNombre];
    if (!subPoll) return;

    await sock.sendMessage(jid, {
        poll: {
            name:            subPoll.name,
            values:          subPoll.values,
            selectableCount: subPoll.selectableCount,
        },
    });
}

async function enviarPollConfirmacion(sock, jid, tema) {
    await sock.sendMessage(jid, {
        poll: pollConfirmacion(tema),
    });
}

// ═══════════════════════════════════════════════════════════════════════════
// PROCESAR VOTO EN POLL
// ═══════════════════════════════════════════════════════════════════════════
async function procesarPoll(sock, jid, telefono, voto, ses) {

    // ── Menú principal ───────────────────────────────────────────────────
    if (MENU_POLL.values.includes(voto)) {

        if (voto === '🚪 Cerrar sesión') {
            resetSesion(telefono);
            await txt(sock, jid, `👋 Sesión cerrada. ¡Hasta pronto, *${ses.nombres.split(' ')[0]}*!`);
            return;
        }

        if (voto === '💬 Consulta libre') {
            ses.paso = 'consulta_libre'; ses.submenuActivo = null;
            await txt(sock, jid,
                '💬 *Consulta libre*\n\n' +
                'Escribe tu pregunta con tus propias palabras.\n' +
                '_Escribe *menú* para volver al menú principal._'
            );
            return;
        }

        // Tiene submenú
        if (SUBMENUS_POLL[voto]) {
            ses.submenuActivo = voto;
            ses.paso          = 'submenu';
            await enviarSubMenuPoll(sock, jid, voto);
            return;
        }
    }

    // ── Submenú ──────────────────────────────────────────────────────────
    if (ses.submenuActivo && SUBMENUS_POLL[ses.submenuActivo]) {
        const subOpciones = SUBMENUS_POLL[ses.submenuActivo].values;

        if (voto === '← Volver al menú') {
            ses.paso = 'menu'; ses.submenuActivo = null;
            await enviarMenuPoll(sock, jid, ses);
            return;
        }

        if (subOpciones.includes(voto)) {
            const pregunta = CONSULTAS[ses.submenuActivo]?.[voto];
            if (pregunta) {
                ses.paso = 'confirmacion';
                ses.pendienteConsulta = pregunta;
                ses.pendienteTema     = ses.submenuActivo.replace(/[🎫💻🧪🏢📜]/g,'').trim();
                await enviarPollConfirmacion(sock, jid, ses.pendienteTema);
                return;
            }
        }
    }

    // ── Confirmación ─────────────────────────────────────────────────────
    if (voto === '✅ Continuar' && ses.paso === 'confirmacion') {
        const pregunta = ses.pendienteConsulta;
        ses.pendienteConsulta = null;
        ses.pendienteTema     = null;
        ses.paso              = ses.submenuActivo ? 'submenu' : 'menu';
        await realizarConsulta(sock, jid, telefono, pregunta, ses);
        return;
    }

    if (voto === '❌ Cancelar' && ses.paso === 'confirmacion') {
        ses.pendienteConsulta = null;
        ses.pendienteTema     = null;
        ses.paso              = ses.submenuActivo ? 'submenu' : 'menu';
        await txt(sock, jid, 'Cancelado. ¿Qué más necesitas consultar?');
        await delay(400);
        if (ses.submenuActivo) {
            await enviarSubMenuPoll(sock, jid, ses.submenuActivo);
        } else {
            await enviarMenuPoll(sock, jid, ses);
        }
        return;
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// PROCESAR TEXTO LIBRE
// ═══════════════════════════════════════════════════════════════════════════
async function procesarTexto(sock, jid, telefono, texto, ses) {
    const cmd = texto.toLowerCase().trim();

    // Comandos globales
    if (['salir','logout','bye','adios','adiós'].includes(cmd)) {
        resetSesion(telefono);
        await txt(sock, jid, `👋 Sesión cerrada. ¡Hasta pronto, *${ses.nombres.split(' ')[0]}*!`);
        return;
    }
    if (['menu','menú','inicio','m'].includes(cmd)) {
        ses.paso = 'menu'; ses.submenuActivo = null;
        await enviarMenuPoll(sock, jid, ses);
        return;
    }
    if (['ayuda','help','?'].includes(cmd)) {
        await txt(sock, jid,
            '📋 *Comandos disponibles:*\n\n' +
            '*menú* → Ver menú con opciones\n' +
            '*salir* → Cerrar sesión\n' +
            '*ayuda* → Este mensaje\n\n' +
            '_También puedes escribir tu pregunta directamente._'
        );
        return;
    }

    // Entrenamiento (admin/tecnico)
    if (texto.startsWith('/entrenar') && ['admin','tecnico'].includes(ses.rol)) {
        await flujoEntrenar(sock, jid, texto, ses);
        return;
    }

    // Consulta directa en consulta_libre
    if (ses.paso === 'consulta_libre' && texto) {
        await realizarConsulta(sock, jid, telefono, texto, ses);
        return;
    }

    // Cualquier texto libre → consulta directa + ofrecer menú
    await realizarConsulta(sock, jid, telefono, texto, ses);
}

// ═══════════════════════════════════════════════════════════════════════════
// REALIZAR CONSULTA
// ═══════════════════════════════════════════════════════════════════════════
async function realizarConsulta(sock, jid, telefono, pregunta, ses) {
    await txt(sock, jid, '⏳ _Consultando el sistema..._');

    const res = await php('wa_responder', {
        telefono,
        mensaje:   pregunta,
        idUsuario: ses.idUsuario,
        nombres:   ses.nombres,
        apellidos: ses.apellidos,
        rol:       ses.rol,
        area:      ses.area,
        sessionId: ses.sessionId,
    });

    if (!res || res.error) {
        await txt(sock, jid, `⚠️ ${res?.respuesta || 'No pude procesar la consulta. Intenta de nuevo.'}`);
    } else {
        // Encabezado con ícono del módulo consultado
        const schema = res.schema || '';
        const iconos = {
            'soporte':'🎫','inventario':'💻','laboratorio':'🧪',
            'salas':'🏢','activos':'📜','comun':'👥',
        };
        const icono = iconos[schema.split('.')[0]?.toLowerCase()] || '📊';
        const encab = schema ? `${icono} _${schema}_\n─────────────────────\n` : '';
        await txt(sock, jid, encab + fmtWA(res.respuesta));
    }

    // Ofrecer continuar con poll del submenú activo o menú principal
    await delay(1000);
    if (ses.submenuActivo && SUBMENUS_POLL[ses.submenuActivo]) {
        await txt(sock, jid, '_¿Quieres consultar algo más?_');
        await delay(400);
        await enviarSubMenuPoll(sock, jid, ses.submenuActivo);
    } else {
        await txt(sock, jid, '_¿Tienes otra consulta? Selecciona una opción:_');
        await delay(400);
        await enviarMenuPoll(sock, jid, ses);
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// ENTRENAMIENTO RAG
// ═══════════════════════════════════════════════════════════════════════════
async function flujoEntrenar(sock, jid, texto, ses) {
    const partes = texto.replace(/^\/entrenar\s*/i, '').split('|');
    if (partes.length < 4) {
        await txt(sock, jid,
            '📚 *Formato para entrenar:*\n\n' +
            '`/entrenar [pregunta]|[palabras,clave]|[schema.tabla]|[SELECT ...]`\n\n' +
            '*Ejemplo:*\n' +
            '/entrenar ¿Equipos en baja?|equipo,baja|inventario.activo|SELECT codigoPatrimonial FROM inventario.activo WHERE estado=\'baja\''
        );
        return;
    }
    const res = await php('wa_agregar_rag', {
        rol:      ses.rol,
        pregunta: partes[0].trim(),
        palabras: partes[1].trim(),
        schema:   partes[2].trim(),
        sql:      partes[3].trim(),
        respBase: partes[4]?.trim() || '',
    });
    await txt(sock, jid,
        res?.error
            ? '❌ Error al guardar el ejemplo.'
            : `✅ Ejemplo guardado (ID: ${res?.id || '?'})\n_"${partes[0].trim()}"_`
    );
}

// ═══════════════════════════════════════════════════════════════════════════
// HELPERS
// ═══════════════════════════════════════════════════════════════════════════
function fmtWA(t) {
    return (t || '')
        .replace(/#{1,6}\s*(.+)/gm, '*$1*')
        .replace(/\*\*(.*?)\*\*/g,  '*$1*')
        .replace(/`{3}[\s\S]*?`{3}/g, '')
        .replace(/`([^`]+)`/g, '$1')
        .replace(/\n{3,}/g, '\n\n')
        .trim();
}

async function txt(sock, jid, texto) {
    try {
        await sock.sendMessage(jid, { text: texto });
    } catch (e) { console.error('Error enviando:', e.message); }
}

async function php(accion, datos = {}) {
    try {
        const body = new URLSearchParams({ accion, node_token: CFG.NODE_TOKEN, ...datos });
        const res  = await fetch(CFG.PHP_URL, {
            method: 'POST', body,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        });
        if (!res.ok) { console.error(`PHP [${accion}] HTTP ${res.status}`); return null; }
        const t = await res.text();
        try   { return JSON.parse(t); }
        catch { console.error(`PHP [${accion}] JSON inválido`); return null; }
    } catch (e) { console.error(`PHP [${accion}]:`, e.message); return null; }
}

function delay(ms) { return new Promise(r => setTimeout(r, ms)); }

function obtenerSesion(tel) {
    if (!sesiones.has(tel)) sesiones.set(tel, {
        paso: 'inicio', autenticado: false, idUsuario: 0,
        usuario: null, usuarioPendiente: null,
        nombres: '', apellidos: '', rol: 'usuario', area: '',
        sessionId: '', submenuActivo: null,
        pendienteConsulta: null, pendienteTema: null,
        intentosLogin: 0, ts: Date.now(),
    });
    return sesiones.get(tel);
}

function resetSesion(tel) {
    sesiones.set(tel, {
        paso: 'inicio', autenticado: false, idUsuario: 0,
        usuario: null, usuarioPendiente: null,
        nombres: '', apellidos: '', rol: 'usuario', area: '',
        sessionId: '', submenuActivo: null,
        pendienteConsulta: null, pendienteTema: null,
        intentosLogin: 0, ts: Date.now(),
    });
}

setInterval(() => {
    const lim = CFG.TIMEOUT_MIN * 60 * 1000; let n = 0;
    for (const [tel, ses] of sesiones)
        if (Date.now() - ses.ts > lim) { sesiones.delete(tel); n++; }
    if (n) console.log(`🧹 ${n} sesión(es) limpiada(s)`);
}, 30 * 60 * 1000);

// ─── ARRANCAR ─────────────────────────────────────────────────────────────
iniciar().catch(e => { console.error('Error fatal:', e); process.exit(1); });
/**
 * chavibot-wa.js — ChaviBot WhatsApp Personal v2
 * CHAVIMOCHIC · Sistema de Gestión TI
 *
 * CAMBIOS v2:
 *  - Login solo por DNI (sin contraseña)
 *  - Menú dinámico según permisos reales de comun.Permisos
 *  - Submenús solo muestran módulos que el usuario tiene permiso de ver
 *  - Permisos se envían en cada llamada wa_responder para filtrar RAG
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

// ─── CONFIG ───────────────────────────────────────────────────────────────
const CFG = {
    PHP_URL:    'http://localhost/gestionTI/modules/chatbot/ajax/chavibot.ajax.php',
    NODE_TOKEN: 'chavibot_node_2026',
    SESSION_DIR: path.join(__dirname, 'wa_session'),
    TIMEOUT_MIN: 120,
    MAX_INTENTOS: 3,
};

// ─── DEFINICIÓN DE MÓDULOS DISPONIBLES ────────────────────────────────────
// Cada módulo tiene: emoji, label, los nombres de módulo del sistema que lo habilitan,
// y sus opciones (con o sin parámetro)
const MODULOS_CHAT = [
    {
        id: 'soporte', emoji: '🎫', label: 'Soporte / Tickets',
        modulosSistema: ['reportestecnicos', 'soporte'],
        opciones: [
            { id: '1', label: 'Tickets abiertos',         pregunta: '¿Qué tickets de soporte están abiertos?' },
            { id: '2', label: 'Tickets alta prioridad',   pregunta: '¿Qué tickets tienen alta prioridad?' },
            { id: '3', label: 'Carga por técnico',        pregunta: '¿Cuántos tickets tiene cada técnico asignado?' },
        ],
    },
    {
        id: 'inventario', emoji: '💻', label: 'Inventario y equipos',
        modulosSistema: ['inventario'],
        opciones: [
            { id: '1', label: 'Equipos disponibles',       pregunta: '¿Qué equipos están disponibles sin asignar?' },
            { id: '2', label: 'Estaciones asignadas',      pregunta: '¿Qué estaciones de trabajo están asignadas?' },
            { id: '3', label: 'Resumen por tipo',          pregunta: '¿Cuántos equipos hay en inventario por tipo?' },
            { id: '4', label: 'Detalle equipo (cód. pat.)', preguntaFn: (p) => `¿Cuál es el detalle del equipo con código patrimonial ${p}?`, pedirParam: '🔍 Escribe el *código patrimonial* del equipo:\n_Ej: PAT-0042_\n\n_Escribe *cancelar* para volver._' },
            { id: '5', label: 'Periféricos de estación',   preguntaFn: (p) => `¿Qué periféricos tiene la estación ${p}?`,                    pedirParam: '🔍 Escribe el *nombre de la estación*:\n_Ej: ESTACION-01_\n\n_Escribe *cancelar* para volver._' },
            { id: '6', label: 'Asignado a quién (cód.)',   preguntaFn: (p) => `¿A quién está asignado el equipo ${p}?`,                      pedirParam: '🔍 Escribe el *código patrimonial*:\n\n_Escribe *cancelar* para volver._' },
            { id: '7', label: 'Historial estación',        preguntaFn: (p) => `¿Cuál es el historial de asignaciones de la estación ${p}?`,  pedirParam: '🔍 Escribe el *nombre de la estación*:\n\n_Escribe *cancelar* para volver._' },
        ],
    },
    {
        id: 'laboratorio', emoji: '🧪', label: 'Laboratorio',
        modulosSistema: ['agricola', 'laboratorio'],
        opciones: [
            { id: '1', label: 'Stock bajo',               pregunta: '¿Qué reactivos tienen stock bajo?' },
            { id: '2', label: 'Reactivos por vencer',     pregunta: '¿Qué reactivos están por vencer?' },
            { id: '3', label: 'Solicitudes pendientes',   pregunta: '¿Qué solicitudes de análisis están pendientes?' },
        ],
    },
    {
        id: 'salas', emoji: '🏢', label: 'Salas',
        modulosSistema: ['salas'],
        opciones: [
            { id: '1', label: 'Reservas de hoy',          pregunta: '¿Qué salas están reservadas hoy?' },
            { id: '2', label: 'Reservas de mañana',       pregunta: '¿Qué salas hay disponibles mañana?' },
            { id: '3', label: 'Lista de salas',            pregunta: '¿Cuáles son todas las salas disponibles?' },
        ],
    },
    {
        id: 'certificados', emoji: '📜', label: 'Certificados',
        modulosSistema: ['certificados'],
        opciones: [
            { id: '1', label: 'Por vencer (90 días)',     pregunta: '¿Qué certificados vencen pronto?' },
            { id: '2', label: 'Ya vencidos',              pregunta: '¿Qué certificados ya están vencidos?' },
        ],
    },
];

// ─── ESTADO ───────────────────────────────────────────────────────────────
const sesiones = new Map();
const logger   = pino({ level: 'silent' });

// ═══════════════════════════════════════════════════════════════════════════
// HELPERS DE MENÚ DINÁMICO (basado en permisos reales del usuario)
// ═══════════════════════════════════════════════════════════════════════════

/** Filtra los módulos a los que el usuario tiene acceso según sus permisos */
function modulosPermitidos(permisos, rol) {
    // Admin/técnico → todo
    if (['admin','tecnico','administrador'].includes(rol)) return MODULOS_CHAT;
    if (!permisos || permisos.length === 0) return [];
    return MODULOS_CHAT.filter(mod =>
        mod.modulosSistema.some(ms => permisos.includes(ms))
    );
}

/** Genera el texto del menú principal según los módulos permitidos */
function textoMenuPrincipal(ses) {
    const mods = modulosPermitidos(ses.permisos, ses.rol);
    if (mods.length === 0) {
        return `👤 *${ses.nombres.split(' ')[0]}* · _${ses.rol}_\n\n⚠️ No tienes módulos habilitados en ChaviBot.\nContacta al administrador de TI.`;
    }

    const primer = ses.nombres.split(' ')[0];
    let menu = `👤 *${primer}* · _${ses.rol}_\n\n`;

    mods.forEach((mod, i) => {
        menu += `*${i + 1}* · ${mod.emoji}  ${mod.label}\n`;
    });
    menu += `*${mods.length + 1}* · 💬  Consulta libre\n`;
    menu += `*0* · 🚪  Cerrar sesión\n\n`;
    menu += `_Escribe el número de tu elección._`;
    return menu;
}

/** Genera el texto del submenú de un módulo específico */
function textoSubMenu(mod) {
    let sub = `${mod.emoji} *${mod.label}*\n\n`;
    mod.opciones.forEach(op => {
        sub += `*${op.id}* · ${op.label}${op.pedirParam ? ' _(pide dato)_' : ''}\n`;
    });
    sub += `*0* · ← Volver al menú`;
    return sub;
}

// ═══════════════════════════════════════════════════════════════════════════
// CONEXIÓN WHATSAPP
// ═══════════════════════════════════════════════════════════════════════════
async function iniciar() {
    if (!fs.existsSync(CFG.SESSION_DIR))
        fs.mkdirSync(CFG.SESSION_DIR, { recursive: true });

    const { state, saveCreds } = await useMultiFileAuthState(CFG.SESSION_DIR);
    const { version }          = await fetchLatestBaileysVersion();

    console.log('\n' + '═'.repeat(55));
    console.log('  🤖  ChaviBot v2 · WhatsApp · CHAVIMOCHIC');
    console.log('  Login por DNI + permisos reales del sistema');
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
            console.log('📱 Escanea el QR: WhatsApp → ⋮ → Dispositivos vinculados\n');
            qrcode.generate(qr, { small: true });
        }
        if (connection === 'open') {
            console.log(`✅ Conectado: +${sock.user?.id?.split(':')[0]}\n`);
        }
        if (connection === 'close') {
            const code = lastDisconnect?.error?.output?.statusCode;
            if (code === DisconnectReason.loggedOut) {
                console.log('❌ Sesión cerrada. Borra wa_session/ y reinicia.');
                fs.rmSync(CFG.SESSION_DIR, { recursive: true, force: true });
            } else {
                console.log(`⚠️ Desconectado (${code}). Reconectando en 5s...`);
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
            const texto    = (
                msg.message?.conversation              ||
                msg.message?.extendedTextMessage?.text ||
                ''
            ).trim();
            if (!texto) continue;

            const h = new Date().toLocaleTimeString('es-PE', { hour:'2-digit', minute:'2-digit' });
            console.log(`📨 [${h}] +${telefono}: ${texto.slice(0, 60)}`);

            try {
                await manejar(sock, jid, telefono, texto);
            } catch (e) {
                console.error(`❌ +${telefono}:`, e.message);
                await send(sock, jid, '⚠️ Error interno. Escribe *menú* para continuar.');
            }
        }
    });
}

// ═══════════════════════════════════════════════════════════════════════════
// MANEJADOR PRINCIPAL
// ═══════════════════════════════════════════════════════════════════════════
async function manejar(sock, jid, telefono, texto) {
    let ses = getSes(telefono);

    // Expirar sesión
    if (ses.auth && Date.now() - ses.ts > CFG.TIMEOUT_MIN * 60 * 1000) {
        resetSes(telefono); ses = getSes(telefono);
        await send(sock, jid, '⏰ Sesión expirada.\nEscribe tu *DNI* para volver a ingresar.');
        return;
    }
    ses.ts = Date.now();

    if (!ses.auth) { await flujoLogin(sock, jid, telefono, texto, ses); return; }

    const cmd = texto.toLowerCase().trim();

    // Comandos globales
    if (['salir','logout','bye','adios','adiós','exit'].includes(cmd)) {
        resetSes(telefono);
        await send(sock, jid, `👋 ¡Hasta pronto, *${ses.nombres.split(' ')[0]}*!`);
        return;
    }
    if (['menu','menú','inicio','m'].includes(cmd) || texto === '00') {
        ses.paso = 'menu'; ses.modActivo = null; ses.opcionParam = null;
        await send(sock, jid, textoMenuPrincipal(ses));
        return;
    }
    if (['ayuda','help','?'].includes(cmd)) {
        await send(sock, jid,
            `📋 *Comandos:*\n\n*menú* → Ver menú\n*salir* → Cerrar sesión\n*ayuda* → Este mensaje\n\n_O escribe tu pregunta directamente._`
        );
        return;
    }
    if (texto.startsWith('/entrenar') && ['admin','tecnico'].includes(ses.rol)) {
        await flujoEntrenar(sock, jid, texto, ses); return;
    }

    // Enrutar por estado
    const mods = modulosPermitidos(ses.permisos, ses.rol);

    switch (ses.paso) {

        case 'menu': {
            const n = parseInt(texto);
            if (texto === '0') {
                resetSes(telefono);
                await send(sock, jid, `👋 ¡Hasta pronto, *${ses.nombres.split(' ')[0]}*!`);
                return;
            }
            // Consulta libre (último número del menú)
            if (n === mods.length + 1) {
                ses.paso = 'libre'; ses.modActivo = null;
                await send(sock, jid, '💬 *Consulta libre*\n\nEscribe tu pregunta.\n_Escribe *menú* para volver._');
                return;
            }
            // Módulo numerado
            if (n >= 1 && n <= mods.length) {
                ses.modActivo = mods[n - 1];
                ses.paso      = 'submenu';
                await send(sock, jid, textoSubMenu(ses.modActivo));
                return;
            }
            // Texto libre → consulta directa
            await consultar(sock, jid, telefono, texto, ses);
            break;
        }

        case 'submenu': {
            if (texto === '0') {
                ses.paso = 'menu'; ses.modActivo = null;
                await send(sock, jid, textoMenuPrincipal(ses));
                return;
            }
            const opcion = ses.modActivo?.opciones.find(o => o.id === texto);
            if (opcion) {
                if (opcion.pedirParam) {
                    ses.opcionParam = opcion;
                    ses.paso        = 'pidiendo_param';
                    await send(sock, jid, opcion.pedirParam);
                } else {
                    await consultar(sock, jid, telefono, opcion.pregunta, ses);
                }
            } else {
                await send(sock, jid, '❓ Opción inválida.\n\n' + textoSubMenu(ses.modActivo));
            }
            break;
        }

        case 'pidiendo_param': {
            if (['cancelar','cancel','0'].includes(cmd)) {
                ses.paso = 'submenu'; ses.opcionParam = null;
                await send(sock, jid, textoSubMenu(ses.modActivo));
                return;
            }
            const pregunta = ses.opcionParam.preguntaFn(texto.trim());
            ses.paso = 'submenu'; ses.opcionParam = null;
            await consultar(sock, jid, telefono, pregunta, ses);
            break;
        }

        case 'libre': {
            if (texto === '0') {
                ses.paso = 'menu'; ses.modActivo = null;
                await send(sock, jid, textoMenuPrincipal(ses));
            } else {
                await consultar(sock, jid, telefono, texto, ses);
            }
            break;
        }

        default:
            ses.paso = 'menu';
            await send(sock, jid, textoMenuPrincipal(ses));
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// FLUJO LOGIN — solo DNI
// ═══════════════════════════════════════════════════════════════════════════
async function flujoLogin(sock, jid, telefono, texto, ses) {
    switch (ses.paso) {

        case 'inicio':
            ses.paso = 'esperando_dni';
            await send(sock, jid,
                `👋 ¡Hola! Soy *ChaviBot*, el asistente de TI de CHAVIMOCHIC. 🤖\n\n` +
                `🪪 Escribe tu *número de DNI* para identificarte:`
            );
            break;

        case 'esperando_dni':
            await verificarDNI(sock, jid, telefono, texto.trim(), ses);
            break;
    }
}

async function verificarDNI(sock, jid, telefono, dni, ses) {
    // Validar que sea solo dígitos (8 caracteres típico PE)
    if (!/^\d{6,12}$/.test(dni)) {
        await send(sock, jid,
            `❌ *DNI inválido.* Debe contener solo números (6-12 dígitos).\n\nEscribe tu *DNI*:`
        );
        return;
    }

    await send(sock, jid, '⏳ _Verificando identidad..._');

    const res = await php('wa_login_dni', { telefono, dni });

    if (!res || res.error || !res.usuario) {
        ses.intentos = (ses.intentos || 0) + 1;
        ses.paso     = 'esperando_dni';

        if (ses.intentos >= CFG.MAX_INTENTOS) {
            await send(sock, jid,
                `🚫 *${ses.intentos} intentos fallidos.*\n\nContacta a TI: ☎️ *interno 123*`
            );
            resetSes(telefono);
            return;
        }

        await send(sock, jid,
            `❌ *DNI no encontrado o usuario inactivo.*\n_(Intento ${ses.intentos}/${CFG.MAX_INTENTOS})_\n\nEscribe tu *DNI* de nuevo:`
        );
        return;
    }

    const u = res.usuario;
    Object.assign(ses, {
        auth:      true,
        paso:      'menu',
        idUsuario: u.id_usuario,
        nombres:   u.nombres + (u.apellidos ? ' ' + u.apellidos : ''),
        rol:       u.rol || 'usuario',
        dni:       u.dni || dni,
        sessionId: `wa_${telefono}_${Date.now()}`,
        permisos:  u.permisos || [],   // ← permisos reales del sistema
        modActivo: null,
        opcionParam: null,
        intentos:  0,
    });

    await php('wa_registrar', {
        telefono,
        idUsuario: ses.idUsuario,
        nombres:   ses.nombres,
        rol:       ses.rol,
        dni:       ses.dni,
    });

    // Bienvenida personalizada con módulos disponibles
    const mods = modulosPermitidos(ses.permisos, ses.rol);
    const primer = ses.nombres.split(' ')[0];

    await send(sock, jid,
        `✅ *¡Bienvenido/a, ${primer}!*\n🎭 Rol: _${ses.rol}_\n\n` +
        (mods.length > 0
            ? `Tienes acceso a *${mods.length} módulo(s)*: ${mods.map(m => m.emoji + ' ' + m.label).join(', ')}.`
            : `⚠️ No tienes módulos habilitados. Contacta al administrador.`)
    );
    await pausa(700);
    await send(sock, jid, textoMenuPrincipal(ses));
}

// ═══════════════════════════════════════════════════════════════════════════
// CONSULTA
// ═══════════════════════════════════════════════════════════════════════════
async function consultar(sock, jid, telefono, pregunta, ses) {
    await send(sock, jid, '⏳ _Consultando..._');

    const res = await php('wa_responder', {
        telefono,
        mensaje:   pregunta,
        idUsuario: ses.idUsuario,
        nombres:   ses.nombres,
        apellidos: '',
        rol:       ses.rol,
        dni:       ses.dni,
        area:      ses.area || '',
        sessionId: ses.sessionId,
        permisos:  JSON.stringify(ses.permisos),  // ← enviados al PHP para filtrar RAG
    });

    if (!res || res.error) {
        await send(sock, jid, `⚠️ ${res?.respuesta || 'No pude procesar la consulta.'}`);
    } else {
        await send(sock, jid, fmt(res.respuesta));
    }

    await pausa(800);
    if (ses.modActivo && ses.paso === 'submenu') {
        await send(sock, jid, '_Escribe otro número o *0* para volver al menú._\n\n' + textoSubMenu(ses.modActivo));
    } else {
        await send(sock, jid, textoMenuPrincipal(ses));
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// ENTRENAMIENTO
// ═══════════════════════════════════════════════════════════════════════════
async function flujoEntrenar(sock, jid, texto, ses) {
    const partes = texto.replace(/^\/entrenar\s*/i, '').split('|');
    if (partes.length < 4) {
        await send(sock, jid, '📚 *Formato:*\n/entrenar [pregunta]|[palabras]|[schema.tabla]|[SELECT ...]');
        return;
    }
    const res = await php('wa_agregar_rag', {
        rol:      ses.rol,
        pregunta: partes[0].trim(), palabras: partes[1].trim(),
        schema:   partes[2].trim(), sql:      partes[3].trim(),
        respBase: partes[4]?.trim() || '',
    });
    await send(sock, jid,
        res?.error ? '❌ Error al guardar.'
                   : `✅ Guardado (ID: ${res?.id})\n_"${partes[0].trim()}"_`
    );
}

// ═══════════════════════════════════════════════════════════════════════════
// UTILS
// ═══════════════════════════════════════════════════════════════════════════
function fmt(t) {
    return (t || '')
        .replace(/#{1,6}\s*(.+)/gm, '*$1*')
        .replace(/\*\*(.*?)\*\*/g,  '*$1*')
        .replace(/`{3}[\s\S]*?`{3}/g, '')
        .replace(/`([^`]+)`/g, '$1')
        .replace(/\n{3,}/g, '\n\n').trim();
}

async function php(accion, datos = {}) {
    try {
        const body = new URLSearchParams({ accion, node_token: CFG.NODE_TOKEN, ...datos });
        const r    = await fetch(CFG.PHP_URL, {
            method: 'POST', body,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            timeout: 60000,
        });
        if (!r.ok) { console.error(`PHP [${accion}] HTTP ${r.status}`); return null; }
        const t = await r.text();
        try { return JSON.parse(t); } catch { console.error(`PHP [${accion}] JSON inválido`); return null; }
    } catch (e) { console.error(`PHP [${accion}]:`, e.message); return null; }
}

async function send(sock, jid, texto) {
    try { await sock.sendMessage(jid, { text: texto }); }
    catch (e) { console.error('send:', e.message); }
}

function pausa(ms) { return new Promise(r => setTimeout(r, ms)); }

function getSes(tel) {
    if (!sesiones.has(tel)) sesiones.set(tel, {
        paso: 'inicio', auth: false, idUsuario: 0,
        nombres: '', rol: 'usuario', dni: '', area: '',
        sessionId: '', permisos: [],
        modActivo: null, opcionParam: null,
        intentos: 0, ts: Date.now(),
    });
    return sesiones.get(tel);
}

function resetSes(tel) {
    sesiones.set(tel, {
        paso: 'inicio', auth: false, idUsuario: 0,
        nombres: '', rol: 'usuario', dni: '', area: '',
        sessionId: '', permisos: [],
        modActivo: null, opcionParam: null,
        intentos: 0, ts: Date.now(),
    });
}

setInterval(() => {
    const lim = CFG.TIMEOUT_MIN * 60 * 1000; let n = 0;
    for (const [tel, ses] of sesiones)
        if (Date.now() - ses.ts > lim) { sesiones.delete(tel); n++; }
    if (n) console.log(`🧹 ${n} sesión(es) limpiada(s)`);
}, 30 * 60 * 1000);

iniciar().catch(e => { console.error('Fatal:', e); process.exit(1); });

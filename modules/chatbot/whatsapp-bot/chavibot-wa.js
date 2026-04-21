/**
 * chavibot-wa.js — ChaviBot WhatsApp Personal (Baileys 6.x)
 * CHAVIMOCHIC · Sistema de Gestión TI
 *
 * Menú por texto numerado. Flujos con parámetros para inventario.
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
    PHP_URL:            'http://localhost/gestionTI/modules/chatbot/ajax/chavibot.ajax.php',
    NODE_TOKEN:         'chavibot_node_2026',
    SESSION_DIR:        path.join(__dirname, 'wa_session'),
    TIMEOUT_MIN:        120,
    MAX_INTENTOS_LOGIN: 3,
};

// ─── MENSAJES ─────────────────────────────────────────────────────────────
const M = {

    bienvenida: () =>
`👋 ¡Hola! Soy *ChaviBot*, el asistente de TI de CHAVIMOCHIC.

🔐 Escribe tu *Nombre de Usuario* del sistema:`,

    pedirClave: (u) =>
`👤 Usuario: *${u}*

🔑 Escribe tu *Contraseña*:`,

    loginError: (i, max) =>
`❌ *Usuario o contraseña incorrectos.*
_(Intento ${i}/${max})_

Escribe tu *usuario* para intentar de nuevo:`,

    bloqueado: () =>
`🚫 *Demasiados intentos fallidos.*
Contacta a TI: ☎️ *interno 123*`,

    loginOk: (n, area, rol) =>
`✅ *¡Bienvenido/a, ${n.split(' ')[0]}!*
${area ? `📂 _${area}_\n` : ''}🎭 _${rol}_`,

    menu: (n, rol) =>
`👤 *${n.split(' ')[0]}* · _${rol}_

*1* · 🎫  Soporte / Tickets
*2* · 💻  Inventario y equipos
*3* · 🧪  Laboratorio
*4* · 🏢  Salas
*5* · 📜  Certificados
*6* · 💬  Consulta libre
*0* · 🚪  Cerrar sesión

_Responde con el número de tu elección._`,

    subMenus: {
        '1':
`🎫 *Soporte / Tickets*

*1* · Tickets abiertos
*2* · Tickets de alta prioridad
*3* · Carga por técnico
*0* · ← Volver al menú`,

        '2':
`💻 *Inventario y equipos*

*Sin parámetro:*
*1* · ¿Qué usuarios están asignados a las estaciones actualmente?
*2* · ¿Qué estaciones no tienen ningún trabajador asignado?
*3* · ¿Cuáles son las asignaciones que ya fueron liberadas o terminadas?
*4* · ¿Qué equipos tienen garantía vigente?
*5* · ¿Cuántos equipos hay por tipo?
*6* · ¿Cuáles son los ambientes por ubicación?
*7* . ¿Cuál es el mapa de red con IPs y estaciones?

// *Por código o nombre:*
// *4* · Detalle de un equipo  _(pide código patrimonial)_
// *5* · Periféricos de una estación  _(pide nombre)_
// *6* · A quién está asignado un equipo  _(pide código patrimonial)_
// *7* · Historial de una estación  _(pide nombre)_

*0* · ← Volver al menú`,

        '3':
`🧪 *Laboratorio*

*1* · Reactivos con stock bajo
*2* · Reactivos por vencer
*3* · Solicitudes pendientes
*0* · ← Volver al menú`,

        '4':
`🏢 *Salas*

*1* · Reservas de hoy
*2* · Reservas de mañana
*3* · Lista de salas
*0* · ← Volver al menú`,

        '5':
`📜 *Certificados*

*1* · Por vencer (90 días)
*2* · Ya vencidos
*0* · ← Volver al menú`,
    },

    // Pedirle el parámetro al usuario
    pedirParam: {
        '4': `🔍 Escribe el *código patrimonial* del equipo:\n_Ej: 123456 ó PAT-0042_\n\n_Escribe *cancelar* para volver._`,
        '5': `🔍 Escribe el *nombre de la estación*:\n_Ej: ESTACION-01 ó LAB-A_\n\n_Escribe *cancelar* para volver._`,
        '6': `🔍 Escribe el *código patrimonial* del equipo:\n_Ej: 123456 ó PAT-0042_\n\n_Escribe *cancelar* para volver._`,
        '7': `🔍 Escribe el *nombre de la estación*:\n_Ej: ESTACION-01 ó LAB-A_\n\n_Escribe *cancelar* para volver._`,
    },

    consultando: () => `⏳ _Consultando..._`,
    navSubMenu:  () => `_Escribe otro número o *0* para volver al menú._`,

    ayuda: () =>
`📋 *Comandos:*

*menú* → Ver menú principal
*salir* → Cerrar sesión
*ayuda* → Este mensaje

_También puedes escribir tu pregunta directamente, por ejemplo:_
_"¿Periféricos de ESTACION-01?"_
_"¿A quién está asignado PAT-0042?"_`,

    logout:  (n) => `👋 ¡Hasta pronto, *${n.split(' ')[0]}*!`,
    expirada:()  => `⏰ Sesión expirada. Escribe tu *usuario* para reingresar.`,
    error:   (m) => `⚠️ ${m || 'Error al procesar. Intenta de nuevo.'}`,
};

// ─── OPCIONES SIN PARÁMETRO (ejecutan directo) ────────────────────────────
const PREGUNTAS = {
    '1': {
        '1': '¿Qué tickets de soporte están abiertos?',
        '2': '¿Qué tickets tienen alta prioridad?',
        '3': '¿Cuántos tickets tiene cada técnico asignado?',
    },
    '2': {
        '1': '¿Qué usuarios están asignados a las estaciones actualmente?',
        '2': '¿Qué estaciones no tienen ningún trabajador asignado?',
        '3': '¿Cuáles son las asignaciones que ya fueron liberadas o terminadas?',
        '4': '¿Qué equipos tienen garantía vigente?',
        '5': '¿Cuántos equipos hay por tipo?',
        '6': '¿Cuáles son los ambientes por ubicación?',
        '7': '¿Cuál es el mapa de red con IPs y estaciones?',
        // 4,5,6,7 → piden parámetro → ver OPCIONES_CON_PARAM
    },
    '3': {
        '1': '¿Qué reactivos tienen stock bajo?',
        '2': '¿Qué reactivos están por vencer?',
        '3': '¿Qué solicitudes de análisis están pendientes?',
    },
    '4': {
        '1': '¿Qué salas están reservadas hoy?',
        '2': '¿Qué salas hay disponibles mañana?',
        '3': '¿Cuáles son todas las salas disponibles?',
    },
    '5': {
        '1': '¿Qué certificados vencen pronto?',
        '2': '¿Qué certificados ya están vencidos?',
    },
};

// ─── OPCIONES CON PARÁMETRO ────────────────────────────────────────────────
// plantilla: función que recibe el parámetro y devuelve la pregunta completa
const OPCIONES_CON_PARAM = {
    '2': {
        '4': (p) => `¿Cuál es el detalle del equipo con código patrimonial ${p}?`,
        '5': (p) => `¿Qué periféricos tiene la estación ${p}?`,
        '6': (p) => `¿A quién está asignado el equipo ${p}?`,
        '7': (p) => `¿Cuál es el historial de asignaciones de la estación ${p}?`,
    },
};

const NOMBRES_SECCION = {
    '1':'Soporte','2':'Inventario','3':'Laboratorio',
    '4':'Salas','5':'Certificados',
};

// ─── ESTADO ───────────────────────────────────────────────────────────────
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

    console.log('\n' + '═'.repeat(50));
    console.log('  🤖  ChaviBot WhatsApp · CHAVIMOCHIC');
    console.log('═'.repeat(50) + '\n');

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

            const h = new Date().toLocaleTimeString('es-PE',{hour:'2-digit',minute:'2-digit'});
            console.log(`📨 [${h}] +${telefono}: ${texto.slice(0,60)}`);

            try {
                await manejar(sock, jid, telefono, texto);
            } catch (e) {
                console.error(`❌ +${telefono}:`, e.message);
                await send(sock, jid, M.error());
            }
        }
    });
}

// ═══════════════════════════════════════════════════════════════════════════
// MANEJADOR PRINCIPAL
// ═══════════════════════════════════════════════════════════════════════════
async function manejar(sock, jid, telefono, texto) {
    let ses = get(telefono);

    // Expirar sesión
    if (ses.auth && Date.now() - ses.ts > CFG.TIMEOUT_MIN * 60 * 1000) {
        reset(telefono); ses = get(telefono);
        await send(sock, jid, M.expirada());
        return;
    }
    ses.ts = Date.now();

    // Login
    if (!ses.auth) { await login(sock, jid, telefono, texto, ses); return; }

    const cmd = texto.toLowerCase().trim();

    // ── Comandos globales (siempre disponibles) ──────────────────────────
    if (['salir','logout','bye','adios','adiós','exit'].includes(cmd)) {
        reset(telefono);
        await send(sock, jid, M.logout(ses.nombres));
        return;
    }
    if (['menu','menú','inicio','m'].includes(cmd) || texto === '00') {
        ses.paso = 'menu'; ses.sub = null; ses.opcionParam = null;
        await send(sock, jid, M.menu(ses.nombres, ses.rol));
        return;
    }
    if (['ayuda','help','?'].includes(cmd)) {
        await send(sock, jid, M.ayuda());
        return;
    }
    if (texto.startsWith('/entrenar') && ['admin','tecnico'].includes(ses.rol)) {
        await entrenar(sock, jid, texto, ses);
        return;
    }

    // ── Enrutar por estado actual ────────────────────────────────────────
    switch (ses.paso) {

        // ── Menú principal ───────────────────────────────────────────────
        case 'menu':
            if (['1','2','3','4','5'].includes(texto)) {
                ses.sub  = texto;
                ses.paso = 'submenu';
                await send(sock, jid, M.subMenus[texto]);
            } else if (texto === '6') {
                ses.paso = 'libre'; ses.sub = null;
                await send(sock, jid,
                    '💬 *Consulta libre*\n\nEscribe tu pregunta con tus propias palabras.\n_Escribe *menú* para volver._'
                );
            } else if (texto === '0') {
                reset(telefono);
                await send(sock, jid, M.logout(ses.nombres));
            } else {
                // Texto libre → consulta directa con RAG
                await consultar(sock, jid, telefono, texto, ses);
            }
            break;

        // ── Submenú ──────────────────────────────────────────────────────
        case 'submenu':
            if (texto === '0') {
                ses.paso = 'menu'; ses.sub = null;
                await send(sock, jid, M.menu(ses.nombres, ses.rol));

            } else if (PREGUNTAS[ses.sub]?.[texto]) {
                // Opción sin parámetro → ejecutar directo
                const pregunta = PREGUNTAS[ses.sub][texto];
                await consultar(sock, jid, telefono, pregunta, ses);

            } else if (OPCIONES_CON_PARAM[ses.sub]?.[texto]) {
                // Opción con parámetro → pedir el dato al usuario
                ses.opcionParam = texto;   // guardamos qué opción eligió
                ses.paso        = 'pidiendo_param';
                await send(sock, jid, M.pedirParam[texto]);

            } else {
                await send(sock, jid, '❓ Opción no válida.\n\n' + M.subMenus[ses.sub]);
            }
            break;

        // ── Esperando parámetro del usuario ──────────────────────────────
        case 'pidiendo_param':
            if (['cancelar','cancel','0'].includes(cmd)) {
                ses.paso        = 'submenu';
                ses.opcionParam = null;
                await send(sock, jid, M.subMenus[ses.sub]);
                return;
            }
            // El texto que escribe el usuario ES el parámetro
            const param    = texto.trim();
            const fnPregunta = OPCIONES_CON_PARAM[ses.sub]?.[ses.opcionParam];
            if (!fnPregunta) {
                ses.paso = 'submenu'; ses.opcionParam = null;
                await send(sock, jid, M.subMenus[ses.sub]);
                return;
            }
            const preguntaConParam = fnPregunta(param);
            ses.paso        = 'submenu';   // volver a submenu después de responder
            ses.opcionParam = null;
            await consultar(sock, jid, telefono, preguntaConParam, ses);
            break;

        // ── Consulta libre ────────────────────────────────────────────────
        case 'libre':
            if (texto === '0') {
                ses.paso = 'menu'; ses.sub = null;
                await send(sock, jid, M.menu(ses.nombres, ses.rol));
            } else {
                await consultar(sock, jid, telefono, texto, ses);
            }
            break;

        default:
            ses.paso = 'menu';
            await send(sock, jid, M.menu(ses.nombres, ses.rol));
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// LOGIN
// ═══════════════════════════════════════════════════════════════════════════
async function login(sock, jid, telefono, texto, ses) {
    switch (ses.paso) {
        case 'inicio':
            ses.paso = 'usr';
            await send(sock, jid, M.bienvenida());
            break;
        case 'usr':
            ses.usuPend = texto;
            ses.paso    = 'pwd';
            await send(sock, jid, M.pedirClave(texto));
            break;
        case 'pwd':
            await verificar(sock, jid, telefono, texto, ses);
            break;
    }
}

async function verificar(sock, jid, telefono, pwd, ses) {
    const res = await php('wa_login', {
        telefono, usuario: ses.usuPend, contrasena: pwd,
    });

    if (!res || res.error || !res.usuario) {
        ses.intentos = (ses.intentos || 0) + 1;
        ses.paso     = 'usr';
        ses.usuPend  = null;
        if (ses.intentos >= CFG.MAX_INTENTOS_LOGIN) {
            await send(sock, jid, M.bloqueado());
            reset(telefono);
            return;
        }
        await send(sock, jid, M.loginError(ses.intentos, CFG.MAX_INTENTOS_LOGIN));
        return;
    }

    const u = res.usuario;
    Object.assign(ses, {
        auth: true, paso: 'menu',
        idUsuario: u.id_usuario, nombres: u.nombres,
        apellidos: u.apellidos || '', rol: u.rol || 'usuario',
        area: u.area || '', sessionId: `wa_${telefono}_${Date.now()}`,
        sub: null, opcionParam: null, intentos: 0,
    });

    await php('wa_registrar', {
        telefono, idUsuario: ses.idUsuario,
        nombres: ses.nombres, apellidos: ses.apellidos,
        rol: ses.rol, area: ses.area,
    });

    await send(sock, jid, M.loginOk(ses.nombres, ses.area, ses.rol));
    await pausa(600);
    await send(sock, jid, M.menu(ses.nombres, ses.rol));
}

// ═══════════════════════════════════════════════════════════════════════════
// CONSULTA → PHP → Ollama → respuesta
// ═══════════════════════════════════════════════════════════════════════════
async function consultar(sock, jid, telefono, pregunta, ses) {
    await send(sock, jid, M.consultando());

    const res = await php('wa_responder', {
        telefono, mensaje: pregunta,
        idUsuario: ses.idUsuario, nombres: ses.nombres,
        apellidos: ses.apellidos, rol: ses.rol,
        area: ses.area, sessionId: ses.sessionId,
    });

    if (!res || res.error) {
        await send(sock, jid, M.error(res?.respuesta));
    } else {
        const schema = res.schema || '';
        const iconos = {
            soporte:'🎫', inventario:'💻', laboratorio:'🧪',
            salas:'🏢', activos:'📜', comun:'👥',
        };
        const icono = iconos[schema.split('.')[0]?.toLowerCase()] || '📊';
        const enc   = schema ? `${icono} _${schema}_\n─────────────────────\n` : '';
        await send(sock, jid, enc + fmt(res.respuesta));
    }

    // Navegar de vuelta
    await pausa(800);
    if (ses.sub) {
        await send(sock, jid, M.navSubMenu());
        await pausa(300);
        await send(sock, jid, M.subMenus[ses.sub]);
    } else {
        await send(sock, jid, M.menu(ses.nombres, ses.rol));
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// ENTRENAMIENTO RAG
// ═══════════════════════════════════════════════════════════════════════════
async function entrenar(sock, jid, texto, ses) {
    const partes = texto.replace(/^\/entrenar\s*/i,'').split('|');
    if (partes.length < 4) {
        await send(sock, jid,
            '📚 *Formato:*\n/entrenar [pregunta]|[palabras]|[schema.tabla]|[SELECT ...]'
        );
        return;
    }
    const res = await php('wa_agregar_rag', {
        rol: ses.rol, pregunta: partes[0].trim(),
        palabras: partes[1].trim(), schema: partes[2].trim(),
        sql: partes[3].trim(), respBase: partes[4]?.trim() || '',
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

async function send(sock, jid, texto) {
    try { await sock.sendMessage(jid, { text: texto }); }
    catch (e) { console.error('send:', e.message); }
}

async function php(accion, datos = {}) {
    try {
        const body = new URLSearchParams({ accion, node_token: CFG.NODE_TOKEN, ...datos });
        const r    = await fetch(CFG.PHP_URL, {
            method: 'POST', body,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        });
        if (!r.ok) { console.error(`PHP [${accion}] HTTP ${r.status}`); return null; }
        const t = await r.text();
        try { return JSON.parse(t); } catch { return null; }
    } catch (e) { console.error(`PHP [${accion}]:`, e.message); return null; }
}

function pausa(ms) { return new Promise(r => setTimeout(r, ms)); }

function get(tel) {
    if (!sesiones.has(tel)) sesiones.set(tel, {
        paso:'inicio', auth:false, idUsuario:0,
        usuPend:null, nombres:'', apellidos:'',
        rol:'usuario', area:'', sessionId:'',
        sub:null, opcionParam:null, intentos:0, ts:Date.now(),
    });
    return sesiones.get(tel);
}

function reset(tel) {
    sesiones.set(tel, {
        paso:'inicio', auth:false, idUsuario:0,
        usuPend:null, nombres:'', apellidos:'',
        rol:'usuario', area:'', sessionId:'',
        sub:null, opcionParam:null, intentos:0, ts:Date.now(),
    });
}

setInterval(() => {
    const lim = CFG.TIMEOUT_MIN * 60 * 1000; let n = 0;
    for (const [tel, ses] of sesiones)
        if (Date.now() - ses.ts > lim) { sesiones.delete(tel); n++; }
    if (n) console.log(`🧹 ${n} sesión(es) limpiada(s)`);
}, 30 * 60 * 1000);

iniciar().catch(e => { console.error('Fatal:', e); process.exit(1); });
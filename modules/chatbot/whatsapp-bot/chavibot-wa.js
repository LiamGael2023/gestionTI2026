/**
 * chavibot-wa.js
 * Bot de WhatsApp Personal para ChaviBot — CHAVIMOCHIC
 * Alojado en: modules/chatbot/whatsapp-bot/
 *
 * CARACTERÍSTICAS:
 *  - Login con usuario + contraseña (tabla comun.Usuarios)
 *  - Menú de opciones numéricas
 *  - Consultas a BD_GESTION_TI via PHP
 *  - Colores y estilo Tabler (verde #009540 / azul #004d99)
 *
 * PRIMER USO:
 *  cd modules/chatbot/whatsapp-bot
 *  npm install
 *  node chavibot-wa.js
 *  → Escanear QR con WhatsApp
 *
 * ERROR "Bad MAC":
 *  Borrar la carpeta wa_session/ y volver a iniciar
 */

const { default: makeWASocket,
        useMultiFileAuthState,
        DisconnectReason,
        fetchLatestBaileysVersion,
        makeCacheableSignalKeyStore } = require('@whiskeysockets/baileys');
const pino    = require('pino');
const qrcode  = require('qrcode-terminal');
const fetch   = (...a) => import('node-fetch').then(({ default: f }) => f(...a));
const fs      = require('fs');
const path    = require('path');

// ═══════════════════════════════════════════════════════════════════════════
// CONFIGURACIÓN
// ═══════════════════════════════════════════════════════════════════════════
const CFG = {
    // URL del AJAX PHP (ajustar si accedes por IP en vez de localhost)
    PHP_URL: 'http://localhost/gestionTI/modules/chatbot/ajax/chavibot.ajax.php',

    // Token compartido con PHP (debe coincidir en config/whatsapp.php)
    NODE_TOKEN: 'chavibot_node_2026',

    // Carpeta de sesión WhatsApp (relativa a este archivo)
    SESSION_DIR: path.join(__dirname, 'wa_session'),

    // Expiración de sesión por inactividad (minutos)
    TIMEOUT_MIN: 120,

    // Máximo intentos de login fallidos antes de bloquear
    MAX_INTENTOS_LOGIN: 3,
};

// ═══════════════════════════════════════════════════════════════════════════
// MENÚ PRINCIPAL
// ═══════════════════════════════════════════════════════════════════════════
const MENU_PRINCIPAL = `
┌─────────────────────────────┐
│  🤖  *ChaviBot — CHAVIMOCHIC*  │
├─────────────────────────────┤
│  *1* · 🎫 Soporte (Tickets)  │
│  *2* · 💻 Inventario         │
│  *3* · 🧪 Laboratorio        │
│  *4* · 🏢 Salas              │
│  *5* · 📜 Certificados       │
│  *6* · 💬 Consulta libre     │
│  *0* · 🚪 Cerrar sesión      │
└─────────────────────────────┘
_Escribe el número o tu pregunta directamente._`.trim();

// Submenús por sección
const SUBMENUS = {
    '1': `*🎫 SOPORTE — ¿Qué consulta?*\n\n  *1* · Tickets abiertos\n  *2* · Tickets de alta prioridad\n  *3* · Carga por técnico\n  *0* · ← Volver al menú`,
    '2': `*💻 INVENTARIO — ¿Qué consulta?*\n\n  *1* · Equipos disponibles\n  *2* · Estaciones asignadas\n  *3* · Resumen por tipo\n  *0* · ← Volver al menú`,
    '3': `*🧪 LABORATORIO — ¿Qué consulta?*\n\n  *1* · Reactivos con stock bajo\n  *2* · Reactivos por vencer\n  *3* · Solicitudes pendientes\n  *0* · ← Volver al menú`,
    '4': `*🏢 SALAS — ¿Qué consulta?*\n\n  *1* · Reservas de hoy\n  *2* · Reservas de mañana\n  *3* · Lista de salas\n  *0* · ← Volver al menú`,
    '5': `*📜 CERTIFICADOS — ¿Qué consulta?*\n\n  *1* · Certificados por vencer (90 días)\n  *2* · Certificados vencidos\n  *0* · ← Volver al menú`,
};

// Preguntas que dispara cada opción del submenú
const CONSULTAS_MENU = {
    '1_1': '¿Qué tickets de soporte están abiertos?',
    '1_2': '¿Qué tickets tienen alta prioridad?',
    '1_3': '¿Cuántos tickets tiene cada técnico?',
    '2_1': '¿Qué equipos están disponibles sin asignar?',
    '2_2': '¿Qué estaciones de trabajo están asignadas?',
    '2_3': '¿Cuántos equipos hay en inventario por tipo?',
    '3_1': '¿Qué reactivos tienen stock bajo?',
    '3_2': '¿Qué reactivos están por vencer?',
    '3_3': '¿Qué solicitudes de análisis están pendientes?',
    '4_1': '¿Qué salas están reservadas hoy?',
    '4_2': '¿Qué salas hay disponibles mañana?',
    '4_3': '¿Cuáles son todas las salas?',
    '5_1': '¿Qué certificados vencen pronto?',
    '5_2': '¿Qué certificados están vencidos?',
};

// ═══════════════════════════════════════════════════════════════════════════
// ESTADO DE SESIONES (en memoria, una por número de teléfono)
// ═══════════════════════════════════════════════════════════════════════════
// Estructura de cada sesión:
// {
//   paso: 'inicio' | 'esperando_usuario' | 'esperando_contrasena' | 'menu' | 'submenu' | 'consulta_libre'
//   autenticado: bool
//   idUsuario: int
//   usuario: string       (nombre de usuario / login)
//   nombres: string
//   apellidos: string
//   rol: string           ('admin' | 'tecnico' | 'usuario')
//   area: string
//   sessionId: string
//   submenuActivo: string | null   ('1'...'5')
//   intentosLogin: int
//   ts: timestamp
// }
const sesiones = new Map();

const logger = pino({ level: 'silent' });

// ═══════════════════════════════════════════════════════════════════════════
// CONEXIÓN A WHATSAPP
// ═══════════════════════════════════════════════════════════════════════════
async function iniciar() {
    if (!fs.existsSync(CFG.SESSION_DIR))
        fs.mkdirSync(CFG.SESSION_DIR, { recursive: true });

    const { state, saveCreds } = await useMultiFileAuthState(CFG.SESSION_DIR);
    const { version }          = await fetchLatestBaileysVersion();

    console.log('\n' + '═'.repeat(55));
    console.log('  🤖  ChaviBot WhatsApp · CHAVIMOCHIC');
    console.log('  Alojado en: modules/chatbot/whatsapp-bot/');
    console.log('  PHP URL: ' + CFG.PHP_URL);
    console.log('═'.repeat(55) + '\n');

    const sock = makeWASocket({
        version,
        auth: {
            creds: state.creds,
            keys:  makeCacheableSignalKeyStore(state.keys, logger), // previene Bad MAC
        },
        logger,
        printQRInTerminal: false,
        browser: ['ChaviBot CHAVIMOCHIC', 'Chrome', '120.0'],
        getMessage: async () => ({ conversation: '' }),
    });

    // ── Eventos de conexión ───────────────────────────────────────────────
    sock.ev.on('connection.update', async ({ connection, lastDisconnect, qr }) => {
        if (qr) {
            console.log('📱 ESCANEA ESTE QR CON TU WHATSAPP:');
            console.log('   WhatsApp → ⋮ → Dispositivos vinculados → Vincular dispositivo\n');
            qrcode.generate(qr, { small: true });
            console.log('');
        }
        if (connection === 'open') {
            const num = sock.user?.id?.split(':')[0] || '?';
            console.log(`✅ Conectado al número: +${num}`);
            console.log('💬 Esperando mensajes...\n');
        }
        if (connection === 'close') {
            const code = lastDisconnect?.error?.output?.statusCode;
            if (code === DisconnectReason.loggedOut) {
                console.log('❌ Sesión cerrada (logged out).');
                console.log('   Borra wa_session/ y vuelve a iniciar.');
                fs.rmSync(CFG.SESSION_DIR, { recursive: true, force: true });
            } else {
                console.log(`⚠️  Desconectado (${code}). Reconectando en 5s...`);
                setTimeout(iniciar, 5000);
            }
        }
    });

    sock.ev.on('creds.update', saveCreds);

    // ── Mensajes entrantes ────────────────────────────────────────────────
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

            const hora = new Date().toLocaleTimeString('es-PE');
            console.log(`📨 [${hora}] +${telefono}: ${texto.slice(0, 60)}`);

            try {
                await procesarMensaje(sock, jid, telefono, texto);
            } catch (e) {
                console.error(`❌ Error con +${telefono}:`, e.message);
                await enviar(sock, jid, '⚠️ Error interno. Por favor intenta de nuevo.');
            }
        }
    });

    return sock;
}

// ═══════════════════════════════════════════════════════════════════════════
// PROCESADOR PRINCIPAL
// ═══════════════════════════════════════════════════════════════════════════
async function procesarMensaje(sock, jid, telefono, texto) {
    let ses = obtenerSesion(telefono);

    // Expirar por inactividad
    if (ses.autenticado && Date.now() - ses.ts > CFG.TIMEOUT_MIN * 60 * 1000) {
        resetSesion(telefono);
        ses = obtenerSesion(telefono);
        await enviar(sock, jid,
            '⏰ Tu sesión expiró por inactividad.\n\nEscribe *hola* para volver a iniciar sesión.'
        );
        return;
    }
    ses.ts = Date.now();

    if (!ses.autenticado) {
        await flujoLogin(sock, jid, telefono, texto, ses);
    } else {
        await flujoMenu(sock, jid, telefono, texto, ses);
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// FLUJO DE LOGIN (usuario + contraseña de comun.Usuarios)
// ═══════════════════════════════════════════════════════════════════════════
async function flujoLogin(sock, jid, telefono, texto, ses) {
    switch (ses.paso) {

        case 'inicio':
            // Cualquier mensaje inicia el flujo
            ses.paso = 'esperando_usuario';
            await enviar(sock, jid,
                '👋 ¡Hola! Soy *ChaviBot*, el asistente de TI de CHAVIMOCHIC.\n\n' +
                '🔐 Para continuar, escribe tu *nombre de usuario* del sistema:'
            );
            break;

        case 'esperando_usuario':
            ses.usuarioPendiente = texto.trim();
            ses.paso             = 'esperando_contrasena';
            await enviar(sock, jid,
                `👤 Usuario: *${ses.usuarioPendiente}*\n\n` +
                '🔑 Ahora escribe tu *contraseña*:\n' +
                '_Por seguridad, borra este mensaje después de enviarlo._'
            );
            break;

        case 'esperando_contrasena':
            await autenticarUsuario(sock, jid, telefono, texto, ses);
            break;
    }
}

async function autenticarUsuario(sock, jid, telefono, contrasena, ses) {
    // Llamar al PHP para verificar contra comun.Usuarios
    const res = await php('wa_login', {
        telefono,
        usuario:    ses.usuarioPendiente,
        contrasena: contrasena,
    });

    if (!res || res.error || !res.usuario) {
        ses.intentosLogin = (ses.intentosLogin || 0) + 1;
        ses.paso          = 'esperando_usuario'; // volver al inicio del login
        ses.usuarioPendiente = null;

        if (ses.intentosLogin >= CFG.MAX_INTENTOS_LOGIN) {
            await enviar(sock, jid,
                `🚫 *${ses.intentosLogin} intentos fallidos.*\n\n` +
                'Tu acceso ha sido bloqueado temporalmente.\n' +
                'Contacta al área de TI: ☎️ interno 123'
            );
            resetSesion(telefono);
            return;
        }

        await enviar(sock, jid,
            `❌ *Usuario o contraseña incorrectos.*\n` +
            `(Intento ${ses.intentosLogin}/${CFG.MAX_INTENTOS_LOGIN})\n\n` +
            'Escribe tu *nombre de usuario* para intentar de nuevo:'
        );
        return;
    }

    // Login exitoso
    const u = res.usuario;
    Object.assign(ses, {
        autenticado:    true,
        paso:           'menu',
        idUsuario:      u.id_usuario,
        usuario:        u.usuario,
        nombres:        u.nombres,
        apellidos:      u.apellidos || '',
        rol:            u.rol || 'usuario',
        area:           u.area || '',
        sessionId:      `wa_${telefono}_${Date.now()}`,
        intentosLogin:  0,
        submenuActivo:  null,
    });

    // Registrar sesión en BD
    await php('wa_registrar', {
        telefono,
        idUsuario: ses.idUsuario,
        nombres:   ses.nombres,
        apellidos: ses.apellidos,
        rol:       ses.rol,
        area:      ses.area,
    });

    const primerNombre = ses.nombres.split(' ')[0];
    await enviar(sock, jid,
        `✅ *¡Bienvenido/a, ${primerNombre}!*\n` +
        (ses.area ? `📂 Área: ${ses.area}\n` : '') +
        `🎭 Rol: ${ses.rol}\n`
    );

    // Mostrar menú
    await mostrarMenuPrincipal(sock, jid, ses);
}

// ═══════════════════════════════════════════════════════════════════════════
// FLUJO DE MENÚ
// ═══════════════════════════════════════════════════════════════════════════
async function flujoMenu(sock, jid, telefono, texto, ses) {
    const cmd = texto.trim().toLowerCase();

    // Comandos globales
    if (['0','salir','logout','menu','menú'].includes(cmd) && ses.paso === 'menu') {
        resetSesion(telefono);
        await enviar(sock, jid,
            `👋 Sesión cerrada. ¡Hasta pronto, *${ses.nombres.split(' ')[0]}*!`
        );
        return;
    }

    if (['menu','menú','inicio','m'].includes(cmd)) {
        ses.paso          = 'menu';
        ses.submenuActivo = null;
        await mostrarMenuPrincipal(sock, jid, ses);
        return;
    }

    // Comando de entrenamiento (solo admin/tecnico)
    if (texto.startsWith('/entrenar') && ['admin','tecnico'].includes(ses.rol)) {
        await flujoEntrenar(sock, jid, texto, ses);
        return;
    }

    switch (ses.paso) {

        // ── En menú principal ────────────────────────────────────────────
        case 'menu':
            if (['1','2','3','4','5'].includes(texto.trim())) {
                // Ir al submenú de la sección elegida
                ses.submenuActivo = texto.trim();
                ses.paso          = 'submenu';
                await enviar(sock, jid, SUBMENUS[texto.trim()]);
            } else if (texto.trim() === '6') {
                ses.paso = 'consulta_libre';
                await enviar(sock, jid,
                    '💬 *Consulta libre*\n\nEscribe tu pregunta con tus propias palabras:\n' +
                    '_Ej: "¿cuántos equipos sin asignar hay?"_\n\n' +
                    'Escribe *0* para volver al menú.'
                );
            } else if (texto.trim() === '0') {
                resetSesion(telefono);
                await enviar(sock, jid, `👋 Sesión cerrada. ¡Hasta pronto!`);
            } else {
                // Texto libre desde el menú → tratar como consulta directa
                await procesarConsulta(sock, jid, telefono, texto, ses);
            }
            break;

        // ── En submenú ───────────────────────────────────────────────────
        case 'submenu':
            if (texto.trim() === '0') {
                ses.paso          = 'menu';
                ses.submenuActivo = null;
                await mostrarMenuPrincipal(sock, jid, ses);
            } else {
                const clave = `${ses.submenuActivo}_${texto.trim()}`;
                const pregunta = CONSULTAS_MENU[clave];
                if (pregunta) {
                    await procesarConsulta(sock, jid, telefono, pregunta, ses);
                } else {
                    await enviar(sock, jid,
                        '❓ Opción no válida. Elige un número del submenú o *0* para volver.'
                    );
                }
            }
            break;

        // ── Consulta libre ────────────────────────────────────────────────
        case 'consulta_libre':
            if (texto.trim() === '0') {
                ses.paso = 'menu';
                await mostrarMenuPrincipal(sock, jid, ses);
            } else {
                await procesarConsulta(sock, jid, telefono, texto, ses);
            }
            break;
    }
}

async function mostrarMenuPrincipal(sock, jid, ses) {
    const primerNombre = ses.nombres.split(' ')[0];
    await enviar(sock, jid, `👤 *${primerNombre}* · ${ses.rol}\n\n${MENU_PRINCIPAL}`);
}

// ═══════════════════════════════════════════════════════════════════════════
// PROCESAR CONSULTA A LA BD (via PHP)
// ═══════════════════════════════════════════════════════════════════════════
async function procesarConsulta(sock, jid, telefono, pregunta, ses) {
    await enviar(sock, jid, '⏳ _Consultando..._');

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
        await enviar(sock, jid,
            `⚠️ ${res?.respuesta || 'No pude procesar la consulta. Intenta de nuevo.'}`
        );
    } else {
        await enviar(sock, jid, res.respuesta);
    }

    // Después de responder, mostrar opciones de navegación
    setTimeout(async () => {
        if (ses.submenuActivo) {
            await enviar(sock, jid,
                `_Escribe otro número del submenú o *0* para volver al menú principal._`
            );
        } else {
            await enviar(sock, jid,
                `_Escribe *menu* para volver al menú principal._`
            );
        }
    }, 1500);
}

// ═══════════════════════════════════════════════════════════════════════════
// ENTRENAMIENTO RAG POR WHATSAPP
// ═══════════════════════════════════════════════════════════════════════════
async function flujoEntrenar(sock, jid, texto, ses) {
    const contenido = texto.replace(/^\/entrenar\s*/i, '').trim();
    const partes    = contenido.split('|');

    if (partes.length < 4) {
        await enviar(sock, jid,
            `📚 *Formato para entrenar:*\n\n` +
            `/entrenar [pregunta]|[palabras,clave]|[schema.tabla]|[SELECT ...]\n\n` +
            `*Ejemplo:*\n/entrenar ¿Equipos en baja?|equipo,baja|inventario.activo|SELECT codigoPatrimonial FROM inventario.activo WHERE estado='baja'`
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

    await enviar(sock, jid,
        res?.error
            ? `❌ Error al guardar el ejemplo.`
            : `✅ Ejemplo guardado (ID: ${res?.id || '?'})\n_"${partes[0].trim()}"_`
    );
}

// ═══════════════════════════════════════════════════════════════════════════
// HELPERS
// ═══════════════════════════════════════════════════════════════════════════
async function php(accion, datos = {}) {
    try {
        const body = new URLSearchParams({
            accion,
            node_token: CFG.NODE_TOKEN,
            ...datos,
        });
        const res = await fetch(CFG.PHP_URL, {
            method:  'POST',
            body,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        });
        if (!res.ok) {
            const t = await res.text().catch(() => '');
            console.error(`PHP [${accion}] → HTTP ${res.status}: ${t.slice(0, 200)}`);
            return null;
        }
        const t = await res.text();
        try   { return JSON.parse(t); }
        catch { console.error(`PHP [${accion}] → JSON inválido: ${t.slice(0, 200)}`); return null; }
    } catch (e) {
        console.error(`PHP [${accion}]:`, e.message);
        return null;
    }
}

async function enviar(sock, jid, texto) {
    try   { await sock.sendMessage(jid, { text: texto }); }
    catch (e) { console.error('Error enviando:', e.message); }
}

function obtenerSesion(tel) {
    if (!sesiones.has(tel)) {
        sesiones.set(tel, {
            paso:            'inicio',
            autenticado:     false,
            idUsuario:       0,
            usuario:         null,
            usuarioPendiente:null,
            nombres:         '',
            apellidos:       '',
            rol:             'usuario',
            area:            '',
            sessionId:       '',
            submenuActivo:   null,
            intentosLogin:   0,
            ts:              Date.now(),
        });
    }
    return sesiones.get(tel);
}

function resetSesion(tel) {
    sesiones.set(tel, {
        paso:            'inicio',
        autenticado:     false,
        idUsuario:       0,
        usuario:         null,
        usuarioPendiente:null,
        nombres:         '',
        apellidos:       '',
        rol:             'usuario',
        area:            '',
        sessionId:       '',
        submenuActivo:   null,
        intentosLogin:   0,
        ts:              Date.now(),
    });
}

// Limpiar sesiones inactivas cada 30 min
setInterval(() => {
    const lim = CFG.TIMEOUT_MIN * 60 * 1000;
    let n = 0;
    for (const [tel, ses] of sesiones) {
        if (Date.now() - ses.ts > lim) {
            sesiones.delete(tel);
            n++;
        }
    }
    if (n > 0) console.log(`🧹 ${n} sesión(es) limpiada(s) por inactividad.`);
}, 30 * 60 * 1000);

// ═══════════════════════════════════════════════════════════════════════════
// ARRANCAR
// ═══════════════════════════════════════════════════════════════════════════
iniciar().catch(e => {
    console.error('Error fatal:', e);
    process.exit(1);
});

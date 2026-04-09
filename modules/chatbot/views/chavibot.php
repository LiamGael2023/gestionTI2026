<?php
/**
 * modules/chatbot/views/chavibot.php
 *
 * Esta vista se incluye DENTRO del sistema (después de header.php).
 * NO debe tener su propio <!DOCTYPE>, <html>, <head> ni <body>.
 * El sistema ya los tiene en public/header.php y public/footer.php.
 *
 * Sesión: Auth.php guarda usuario_id, usuario_nombre, usuario_rol
 */

// La sesión ya está iniciada por Auth.php → no llamar session_start()
// $conn ya existe (lo abre header.php)

// ── Datos del usuario desde sesión real ───────────────────────────────────
$nombreUsuario = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario');
$rolRaw        = strtolower(trim($_SESSION['usuario_rol'] ?? 'usuario'));
$rolMap        = ['admin'=>'admin','administrador'=>'admin','tecnico'=>'tecnico','técnico'=>'tecnico'];
$rolNorm       = $rolMap[$rolRaw] ?? $rolRaw;
$esAdmin       = in_array($rolNorm, ['admin','tecnico']);
$primerNombre  = htmlspecialchars(explode(' ', $nombreUsuario)[0]);

// ── URL del AJAX (usa BASE_URL del sistema) ───────────────────────────────
// BASE_URL = http://host/gestionTI  (definido en config/config.php)
$ajaxUrl = BASE_URL . '/modules/chatbot/ajax/chavibot.ajax.php';

// ── Sugerencias rápidas ───────────────────────────────────────────────────
$sugerencias = [
    '¿Qué tickets están abiertos?',
    '¿Qué reactivos tienen stock bajo?',
    '¿Qué salas están reservadas hoy?',
    '¿Qué equipos están disponibles?',
    '¿Qué certificados vencen pronto?',
];
?>

<?php /* CSS del chatbot — usando BASE_URL para ruta absoluta */ ?>
<link rel="stylesheet" href="<?= BASE_URL ?>/modules/chatbot/views/css/chavibot.css">

<div class="container-xl py-3">
<div class="cb-wrapper">

    <!-- ══ PANEL CHAT ═══════════════════════════════════════════════════ -->
    <section class="cb-chat-panel">

        <header class="cb-header">
            <div class="cb-header-logo">🤖</div>
            <div class="cb-header-info">
                <h2>ChaviBot</h2>
                <span>Asistente TI · CHAVIMOCHIC</span>
            </div>
            <div class="cb-header-actions">
                <?php if ($esAdmin): ?>
                <button class="cb-btn-icon" id="cb-btn-train" title="Entrenar bot">🧠</button>
                <?php endif; ?>
                <button class="cb-btn-icon" title="Nueva conversación" onclick="location.reload()">↺</button>
            </div>
        </header>

        <!-- Info usuario -->
        <div style="padding:10px 20px 0">
            <div class="cb-user-info">
                <span class="icon">👤</span>
                <div>
                    <div class="nombre"><?= $nombreUsuario ?></div>
                    <div class="detalle"><?= ucfirst($rolNorm) ?></div>
                </div>
            </div>
        </div>

        <!-- Mensajes -->
        <div class="cb-messages" id="cb-messages">
            <div class="cb-bienvenida" id="cb-bienvenida">
                <div class="cb-bv-icon">🤖</div>
                <h3>¡Hola, <?= $primerNombre ?>!</h3>
                <p>Soy <strong>ChaviBot</strong>, tu asistente de TI.<br>
                   Consulta información del sistema de gestión CHAVIMOCHIC.</p>
            </div>
        </div>

        <!-- Sugerencias rápidas -->
        <div id="cb-sugerencias-wrap">
            <div class="cb-sugerencias">
                <?php foreach ($sugerencias as $s): ?>
                <button class="cb-sug-btn"><?= htmlspecialchars($s) ?></button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Input -->
        <div class="cb-input-area">
            <div class="cb-input-wrap">
                <textarea id="cb-input"
                    placeholder="Escribe tu consulta… (Enter para enviar)"
                    rows="1" autofocus></textarea>
            </div>
            <button class="cb-send-btn" id="cb-send-btn" title="Enviar">➤</button>
        </div>

    </section>

    <!-- ══ PANEL ENTRENAMIENTO (solo admin/tecnico) ══════════════════════ -->
    <?php if ($esAdmin): ?>
    <aside class="cb-train-panel oculto" id="cb-train-panel">

        <div class="cb-train-header">
            <h3>🧠 Entrenamiento RAG</h3>
            <button class="cb-btn-icon"
                onclick="document.getElementById('cb-train-panel').classList.add('oculto')"
                title="Cerrar">✕</button>
        </div>

        <div class="cb-train-tabs">
            <button class="cb-tab activo" data-tab="agregar">➕ Agregar</button>
            <button class="cb-tab"        data-tab="lista">📋 Lista</button>
        </div>

        <div class="cb-train-body">

            <!-- Tab: Agregar ejemplo -->
            <div id="cb-tab-agregar" class="cb-tab-content">
                <div id="cb-rag-alert" class="cb-alert"></div>
                <form id="cb-form-rag" autocomplete="off">

                    <div class="cb-form-group">
                        <label>Pregunta de ejemplo *</label>
                        <input type="text" id="rag-pregunta"
                               placeholder="¿Cuántos equipos hay disponibles?">
                    </div>
                    <div class="cb-form-group">
                        <label>Palabras clave *</label>
                        <input type="text" id="rag-palabras"
                               placeholder="equipo,disponible,inventario">
                        <div class="cb-hint">Separadas por coma, en minúsculas.</div>
                    </div>
                    <div class="cb-form-group">
                        <label>Schema.Tabla *</label>
                        <input type="text" id="rag-schema"
                               placeholder="inventario.activo">
                    </div>
                    <div class="cb-form-group">
                        <label>Query SQL *</label>
                        <textarea id="rag-sql" rows="4"
                            placeholder="SELECT codigoPatrimonial FROM inventario.activo WHERE estado='disponible'"></textarea>
                        <div class="cb-hint">Variables: {{DNI}}, {{USUARIO_ID}}, {{AREA}}</div>
                    </div>
                    <div class="cb-form-group">
                        <label>Descripción (opcional)</label>
                        <input type="text" id="rag-respbase"
                               placeholder="Muestra equipos disponibles sin asignar.">
                    </div>
                    <div class="cb-form-group">
                        <label>Rol permitido</label>
                        <select id="rag-rol">
                            <option value="">Todos los roles</option>
                            <option value="admin">Solo Admin</option>
                            <option value="admin,tecnico">Admin y Técnico</option>
                        </select>
                    </div>
                    <div class="cb-form-group">
                        <label>Canal</label>
                        <select id="rag-canal">
                            <option value="ambos">Web + WhatsApp</option>
                            <option value="web">Solo Web</option>
                            <option value="whatsapp">Solo WhatsApp</option>
                        </select>
                    </div>

                    <button type="submit" class="cb-btn-primary">💾 Guardar ejemplo</button>
                </form>

                <div style="margin-top:14px;background:#f0fdf4;border:1px solid #bbf7d0;
                            border-radius:8px;padding:10px 12px;font-size:.78rem;color:#166534">
                    <strong>📱 También puedes entrenar desde WhatsApp:</strong><br>
                    <code style="font-size:.74rem">
                        /entrenar [pregunta]|[palabras,clave]|[schema.tabla]|[SELECT ...]
                    </code>
                </div>
            </div>

            <!-- Tab: Lista de ejemplos -->
            <div id="cb-tab-lista" class="cb-tab-content" style="display:none">
                <div id="cb-rag-lista">
                    <p style="color:#94a3b8;font-size:.84rem;padding:.5rem">Cargando…</p>
                </div>
            </div>

        </div><!-- /cb-train-body -->
    </aside>
    <?php endif; ?>

</div><!-- /cb-wrapper -->
</div><!-- /container-xl -->

<!-- URL del AJAX inyectada por PHP (siempre absoluta con BASE_URL) -->
<script>
    window.CHAVIBOT_AJAX_URL = <?= json_encode($ajaxUrl) ?>;
</script>

<!-- JS del chatbot con ruta absoluta -->
<script src="<?= BASE_URL ?>/modules/chatbot/views/js/chavibot.js"></script>

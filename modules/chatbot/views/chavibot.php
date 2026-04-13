<?php
/**
 * modules/chatbot/views/chavibot.php
 * Vista chat — estilo WhatsApp con colores Tabler PECH
 * Se incluye DENTRO del sistema (después de header.php)
 */

$nombreUsuario = htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario');
$rolRaw  = strtolower(trim($_SESSION['usuario_rol'] ?? 'usuario'));
$rolMap  = ['admin'=>'admin','administrador'=>'admin','tecnico'=>'tecnico','técnico'=>'tecnico'];
$rolNorm = $rolMap[$rolRaw] ?? $rolRaw;
$esAdmin = in_array($rolNorm, ['admin','tecnico']);
$primerNombre = htmlspecialchars(explode(' ', $nombreUsuario)[0]);

// URL absoluta del AJAX (usa BASE_URL del sistema)
$ajaxUrl = (defined('BASE_URL') ? BASE_URL : '') . '/modules/chatbot/ajax/chavibot.ajax.php';
?>

<link rel="stylesheet" href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/modules/chatbot/views/css/chavibot.css">

<div class="container-xl" style="padding-top:.75rem;padding-bottom:.75rem">
<div class="cb-wrapper">

  <!-- ═══ PANEL CHAT ════════════════════════════════════════════════════ -->
  <section class="cb-chat-panel">

    <!-- Header WhatsApp -->
    <header class="cb-header">
      <div class="cb-header-logo">🤖</div>
      <div class="cb-header-info">
        <h2>ChaviBot</h2>
        <span>online</span>
      </div>
      <div class="cb-header-actions">
        <?php if ($esAdmin): ?>
        <button class="cb-btn-icon" id="cb-btn-train" title="Panel de entrenamiento">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/></svg>
        </button>
        <?php endif; ?>
        <button class="cb-btn-icon" title="Nueva conversación" onclick="location.reload()">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.57"/></svg>
        </button>
      </div>
    </header>

    <!-- Info usuario -->
    <div class="cb-user-info">
      <span class="icon">👤</span>
      <div>
        <div class="nombre"><?= $nombreUsuario ?></div>
        <div class="detalle"><?= ucfirst($rolNorm) ?></div>
      </div>
    </div>

    <!-- Mensajes -->
    <div class="cb-messages" id="cb-messages">
      <div class="cb-bienvenida" id="cb-bienvenida">
        <div class="cb-bv-icon">🤖</div>
        <h3>¡Hola, <?= $primerNombre ?>!</h3>
        <p>Soy <strong>ChaviBot</strong>, tu asistente de TI.<br>Consulta información del sistema de gestión CHAVIMOCHIC.</p>
      </div>
    </div>

    <!-- Sugerencias rápidas -->
    <div id="cb-sugerencias-wrap">
      <div class="cb-sugerencias">
        <button class="cb-sug-btn">🎫 Tickets abiertos</button>
        <button class="cb-sug-btn">💻 Equipos disponibles</button>
        <button class="cb-sug-btn">🧪 Stock bajo</button>
        <button class="cb-sug-btn">🏢 Salas hoy</button>
        <button class="cb-sug-btn">📜 Certificados</button>
      </div>
    </div>

    <!-- Input -->
    <div class="cb-input-area">
      <div class="cb-input-wrap">
        <button class="cb-icon-sm" title="Emoji">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9" stroke-width="2.5"/><line x1="15" y1="9" x2="15.01" y2="9" stroke-width="2.5"/></svg>
        </button>
        <textarea id="cb-input"
          placeholder="Escribe tu consulta…"
          rows="1" autofocus style="height:auto"></textarea>
        <button class="cb-icon-sm" title="Adjuntar">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
        </button>
      </div>
      <button class="cb-send-btn" id="cb-send-btn" title="Enviar">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="22" y1="2" x2="11" y2="13"/>
          <polygon points="22 2 15 22 11 13 2 9 22 2"/>
        </svg>
      </button>
    </div>

  </section>

  <!-- ═══ PANEL ENTRENAMIENTO ═══════════════════════════════════════════ -->
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

      <div id="cb-tab-agregar" class="cb-tab-content">
        <div id="cb-rag-alert" class="cb-alert"></div>
        <form id="cb-form-rag" autocomplete="off">
          <div class="cb-form-group">
            <label>Pregunta de ejemplo *</label>
            <input type="text" id="rag-pregunta" placeholder="¿Cuántos equipos hay disponibles?">
          </div>
          <div class="cb-form-group">
            <label>Palabras clave *</label>
            <input type="text" id="rag-palabras" placeholder="equipo,disponible,inventario">
            <div class="cb-hint">Separadas por coma, en minúsculas.</div>
          </div>
          <div class="cb-form-group">
            <label>Schema.Tabla *</label>
            <input type="text" id="rag-schema" placeholder="inventario.activo">
          </div>
          <div class="cb-form-group">
            <label>Query SQL *</label>
            <textarea id="rag-sql" rows="4"
              placeholder="SELECT codigoPatrimonial FROM inventario.activo WHERE estado='disponible'"></textarea>
            <div class="cb-hint">Variables: {{DNI}}, {{USUARIO_ID}}, {{AREA}}</div>
          </div>
          <div class="cb-form-group">
            <label>Descripción (opcional)</label>
            <input type="text" id="rag-respbase" placeholder="Muestra equipos disponibles sin asignar.">
          </div>
          <div class="cb-form-group">
            <label>Rol permitido</label>
            <select id="rag-rol">
              <option value="">Todos</option>
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

        <div style="margin-top:12px;background:#f0fdf4;border:0.5px solid #bbf7d0;border-radius:var(--radio);padding:10px 12px;font-size:.75rem;color:#166534">
          <strong>📱 Entrenar por WhatsApp:</strong><br>
          <code style="font-size:.71rem">/entrenar [pregunta]|[palabras]|[schema.tabla]|[SELECT ...]</code>
        </div>
      </div>

      <div id="cb-tab-lista" class="cb-tab-content" style="display:none">
        <div id="cb-rag-lista">
          <p style="color:var(--txt3);font-size:.82rem">Cargando…</p>
        </div>
      </div>

    </div>
  </aside>
  <?php endif; ?>

</div><!-- /cb-wrapper -->
</div><!-- /container-xl -->

<script>window.CHAVIBOT_AJAX_URL = <?= json_encode($ajaxUrl) ?>;</script>
<script src="<?= defined('BASE_URL') ? BASE_URL : '' ?>/modules/chatbot/views/js/chavibot.js"></script>

<?php
/**
 * modules/chatbot/config/whatsapp.php
 * Token para el bot de WhatsApp personal (Baileys + Node.js)
 * NO requiere WhatsApp Business
 */

// Token secreto compartido entre Node.js y este PHP
// DEBE coincidir con CFG.NODE_TOKEN en nodejs/chavibot-wa.js
if (!defined('WA_NODE_TOKEN'))
    define('WA_NODE_TOKEN', 'chavibot_node_2026');

// URL de la API de personal de CHAVIMOCHIC
if (!defined('API_PERSONAL_URL'))
    define('API_PERSONAL_URL', 'https://www.chavimochic.gob.pe/api_incidencias/api_personal.php');

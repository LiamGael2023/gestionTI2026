<?php
/**
 * modules/chatbot/controllers/ChatbotController.php
 * Router del módulo chatbot — cargado por index.php cuando module=chatbot
 *
 * URLs:
 *   /chatbot          → action=index  → dashboard (views/index.php)
 *   /chatbot/chavibot → action=chavibot → chat (views/chavibot.php)
 */

// $conn y $action ya existen en el contexto (definidos por index.php y el router)
$action = $_GET['action'] ?? (isset($_GET['route']) ? explode('/', $_GET['route'])[1] ?? 'index' : 'index');

switch ($action) {

    case 'chavibot':
        // Cargar el modelo y controlador del ChaviBot (nombres en minúscula correctos)
        require_once 'modules/chatbot/models/ChavibotModel.php';
        require_once 'modules/chatbot/controllers/ChavibotController.php';
        include 'modules/chatbot/views/chavibot.php';   // ← minúscula 'b'
        break;

    default:   // index, dashboard, cualquier otro
        include 'modules/chatbot/views/index.php';
        break;
}

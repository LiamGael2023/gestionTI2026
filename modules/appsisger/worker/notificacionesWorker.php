<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/../../../config/db.php";
require_once __DIR__ . "/../models/NotificacionModel.php";
require_once __DIR__ . "/../services/FCMService.php";

echo "🚀 Worker ejecutado...\n";

$notificaciones = NotificacionModel::obtenerPendientes();

echo "📦 RESULTADO:\n";
var_dump($notificaciones);

if(!empty($notificaciones)){
    foreach($notificaciones as $n){

        $titulo = "Orden de riego actualizada";
        $mensaje = "Se actualizó su orden N° " . $n["Rec_Numero"];

        $token = $n["token"];

        echo "📤 Enviando a: $token\n";

        $resp = FCMService::enviar($token, $titulo, $mensaje);

        echo "📩 RAW RESPUESTA:\n";
var_dump($resp);

        NotificacionModel::marcarEnviado($n["Id"]);
    }
} else {
    echo "❌ No hay notificaciones\n";
}

echo "✅ FIN\n";
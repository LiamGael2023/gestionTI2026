<?php
$lockFile = sys_get_temp_dir() . '/fcm_worker.lock';
$lock = fopen($lockFile, 'c');

if (!flock($lock, LOCK_EX | LOCK_NB)) {
    echo "⚠️ Worker ya está corriendo. Saliendo.\n";
    exit(0);
}
require_once __DIR__ . "/../../../config/db.php";
require_once __DIR__ . "/../models/NotificacionModel.php";
require_once __DIR__ . "/../services/FCMService.php";

echo "🚀 Worker ejecutado...\n";

$notificaciones = NotificacionModel::obtenerPendientes();

echo "📦 RESULTADO:\n";
var_dump($notificaciones);

if(!empty($notificaciones)){
    foreach($notificaciones as $n){

        $titulo = "Actualización de Orden de Riego";
        $mensaje = "Se actualizó su orden de riego con numero de recibo N° " . $n["Rec_Numero"]. ". Ingrese para verificar.";

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
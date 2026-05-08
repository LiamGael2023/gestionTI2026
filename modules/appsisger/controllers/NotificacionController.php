<?php

require_once __DIR__ . "/../../../config/db.php";
require_once __DIR__ . "/../models/NotificacionModel.php";
require_once __DIR__ . "/../services/NotificacionService.php";
require_once __DIR__ . "/../services/FCMService.php";

class NotificacionController {

    public static function enviarPendientes(){

        $pendientes = NotificacionModel::obtenerPendientes();

        foreach($pendientes as $n){

            $titulo = "Orden de Riego";
            $mensaje = "Se actualizó su orden de riego";

            FCMService::enviar($n["Token"], $titulo, $mensaje);

            self::marcarEnviado($n["AmbOpe_CodigoCatastral"], $n["Rec_Numero"],$n["Periodo"],$n["Id_Anio"],$n["Band"] );
        }

        echo json_encode(["success"=>true]);
    }

    private static function marcarEnviado($codigo, $rec){

        $conn = Conexion::conectar();

        $sql = "
        UPDATE Aplicativo.NotificacionesPendientes
        SET Estado = 1
        WHERE AmbOpe_CodigoCatastral = ? AND Rec_Numero = ? AND Periodo = ? AND Id_Anio = ? AND Band = ? 
        ";

        sqlsrv_query($conn, $sql, [$codigo, $rec]);
    }
}
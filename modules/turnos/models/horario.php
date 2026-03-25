<?php
require_once __DIR__ . '/../modelo/conexion.php';

class Horario
{

    static public function mdlListarHorarios(){

    $conn = Conexion::conectar();

 $sql = "SELECT 
                    Id_Horario,
                    Hora_Descripcion,
                    Hora_HorIni,
                    Hora_HorFin
                FROM BDPERSONAL.Asistencia.Tbl_Horario";

    $stmt = sqlsrv_query($conn, $sql);

    if($stmt === false){
        $errors = sqlsrv_errors();
        error_log(print_r($errors,true));
        return [];
    }

    $datos = [];

    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
        $datos[] = $row;
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    return $datos;
}
}
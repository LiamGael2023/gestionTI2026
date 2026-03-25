<?php

require_once __DIR__ . '/../modelo/conexion.php';

class HorarioTrabajadorModelo{

    static public function mdlGuardarHorario($datos){

        $conn = Conexion::conectar();

        $sql = "EXEC BDPERSONAL.Asistencia.Guardar_Turno_Trabajador
            @Id_Anio = ?,
            @Id_Mes = ?,
            @Id_Trabajador = ?,
            @Id_Componente = ?,
            @Id_Meta = ?,
            @Id_Horario = ?,
            @FechaInicioTurno = ?,
            @FechaFinTurno = ?,
            @Id_marcacion_tipo =?";

        $params = array(
            $datos["anio"],
            $datos["mes"],
            $datos["trabajador"],
            $datos["componente"],
            $datos["meta"],
            $datos["horario"],
            $datos["fechainicioturno"],
            $datos["fechafinturno"],
            $datos["marcacionturno"]
        );

        $stmt = sqlsrv_query($conn, $sql, $params);

        if($stmt === false){
            echo json_encode(sqlsrv_errors());
            exit;
        }

        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        return "ok";
    }

}
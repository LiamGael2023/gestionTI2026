<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


class HorarioTrabajadorModel{
    private $conn;


      public function __construct($conn2)
    {
        $this->conn = $conn2;
    }

    static public function mdlGuardarHorario($conn, $datos){

    $sql = "EXEC BDPERSONAL.Asistencia.Guardar_Turno_Trabajador2
        @Id_Anio = ?,
        @Id_Mes = ?,
        @Id_Trabajador = ?,
        @Id_Componente = ?,
        @Id_Meta = ?,
        @Id_Horario = ?,
        @FechaInicioTurno = ?,
        @FechaFinTurno = ?,
        @Id_marcacion_tipo = ?,
        @DescripcionTurno = ?";

    $params = array(
        $datos["anio"],
        $datos["mes"],
        $datos["trabajador"],
        $datos["componente"],
        $datos["meta"],
        $datos["horario"],
        $datos["fechainicioturno"],
        $datos["fechafinturno"],
        $datos["marcacionturno"],
        $datos["descripcion"]
    );

    $stmt = sqlsrv_query($conn, $sql, $params);

    if($stmt === false){
        return [
            "status" => "error",
            "error" => sqlsrv_errors()
        ];
    }

    sqlsrv_free_stmt($stmt);

    return [
        "status" => "ok"
    ];
}

static public function mdlGuardarTrabajador($datos){


        $conn = Conexion::conectar();
 $sql = "EXEC BDPERSONAL.Asistencia.Guardar_Trabajador_Seleccionado_Turno 
        @Id_Trabajador = ?, 
        @Id_Componente = ?, 
        @Id_Meta = ?, 
        @Id_Trabajador_Tipo = ?, 
        @Id_Anio = ?";
    $params = [
        $datos['id'],
        $datos['componente'],
        $datos['meta'],
        $datos['tipotrabajador'],
        $datos['anio']
    ];

    echo $sql;
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
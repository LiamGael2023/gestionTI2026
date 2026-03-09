<?php

class ModeloColaborador
{


    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function listar()
    {
        $sql = "SELECT * FROM inicio WHERE activo = 1";
        return sqlsrv_query($this->conn, $sql);
    }

    static public function MdlMostrarColaborador($idJefe, $fecha)
    {
        $conn = Conexion::conectar();

        // ✅ Si no se envía fecha, se pasa NULL (SQL tomará GETDATE())
        $sql = "{CALL BDPERSONAL.Aplicativo.SP_Listar_Colaboradores_Papeleta_Diaria(?, ?)}";
        $params = array($idJefe, $fecha);

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            error_log('Error en la consulta: ' . print_r($errors, true));
            return []; // Devuelve array vacío en caso de error
        }

        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }

        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        return $result;
    }

    static public function mdlMostrarTrabajadoresActivos()
    {
        $conn = Conexion::conectar();

        $sql = "EXEC [Aplicativo].[SP_Listar_Trabajadores_Activos]";

        $stmt = sqlsrv_query($conn, $sql);

        $resultados = array();

        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $resultados[] = $row;
            }
            sqlsrv_free_stmt($stmt);
        }

        sqlsrv_close($conn);
        return $resultados;
    }
}

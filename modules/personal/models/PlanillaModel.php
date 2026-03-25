<?php

class PlanillaModel
{
    /* ======================================================
       MODELO PARA VISTAS
    ====================================================== */
    private $conn;

    public function __construct($conn2)
    {
        $this->conn = $conn2;
    }

    public function listar()
    {
        $sql = "SELECT * FROM inicio WHERE activo = 1";
        return sqlsrv_query($this->conn, $sql);
    }

    /* ======================================================
       MÉTODOS ESTÁTICOS PARA AJAX
    ====================================================== */

    public static function mdlConsultarAniosBoletas($datos)
    {
        $conn = Conexion::conectar();

        $sql = "EXEC BD_PERSONAL.Planilla.SP_Listar_Anios_Boletas ?";
        $params = [$datos['id_trabajador']];

        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {

            echo '<pre>';
            print_r(sqlsrv_errors());
            echo '</pre>';
            exit;
        }


        $rows = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $row;
        }

        sqlsrv_close($conn);

        return ['status' => 'success', 'data' => $rows];
    }

    public static function mdlListarBoletasPorAnio($datos)
    {
        $conn = Conexion::conectar();

        $sql = "EXEC BD_PERSONAL.Planilla.SP_Listar_Boletas_X_Anio ?, ?";
        $params = [
            $datos['id_trabajador'],
            $datos['anio']
        ];

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            error_log(print_r(sqlsrv_errors(), true));
            return ['status' => 'error', 'message' => 'Error consultando boletas'];
        }

        $rows = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rows[] = $row;
        }

        sqlsrv_close($conn);

        return ['status' => 'success', 'data' => $rows];
    }

    public static function mdlActualizarDescargadoBoleta($datos)
    {
        $conn = Conexion::conectar();

        $sql = "EXEC BD_PERSONAL.Planilla.SP_ACTUALIZAR_DESCARGADO ?,?,?,?";
        $params = [
            $datos['id_trabajador'],
            $datos['anio'],
            $datos['mes'],
            $datos['planilla']
        ];

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            error_log(print_r(sqlsrv_errors(), true));
            return ['status' => 'error', 'message' => 'Error actualizando boleta'];
        }

        sqlsrv_close($conn);

        return ['status' => 'success', 'message' => 'Boleta actualizada'];
    }


    static public function cargarInformacionUsuario($tabla, $item, $valor)
    {

        $conn = Conexion::conectar();

        if ($item != null) {
            $query = "EXEC BD_PERSONAL.Aplicativo.SP_Login_Usuario_Vigilante ?";
            $params = array($valor);

            $stmt = sqlsrv_query($conn, $query, $params);
        } else {
            $query = "EXEC BD_PERSONAL.Aplicativo.SP_Login_Usuario_Vigilante ";
            $stmt = sqlsrv_query($conn, $query);
        }

        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        $result = array();
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }

        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        return (count($result) == 1) ? $result[0] : $result;
    }


}

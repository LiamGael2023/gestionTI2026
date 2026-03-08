<?php
require_once __DIR__ . "/../../../config/db.php";


class ActivosModel {

    static public function mdlCrearActivo($tabla, $datos) {
        $conn = Conexion::conectar();
        $sql = "{call inventario.sp_CrearActivo(?, ?, ?, ?)}";

        $params = array(
            array($datos["descripcion"], SQLSRV_PARAM_IN),
            array($datos["icono"], SQLSRV_PARAM_IN),
            array($datos["compuesto"], SQLSRV_PARAM_IN),
            array($datos["idUsuarioRegistro"], SQLSRV_PARAM_IN)
        );

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            return "error";
        }

        $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        return $resultado['resultado'];
    }

    /*=============================================
    MOSTRAR ACTIVOS (CORREGIDO PARA SQLSRV)
    =============================================*/
    static public function mdlMostrarActivos($tabla, $item, $valor) {
        $conn = Conexion::conectar();

        if ($item != null) {
            // Consulta filtrada
            $sql = "SELECT * FROM $tabla WHERE $item = ?";
            $params = array($valor);
            $stmt = sqlsrv_query($conn, $sql, $params);

            if ($stmt === false) return "error";

            $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        } else {
            // Consulta general
            $sql = "SELECT * FROM $tabla ORDER BY descripcion ASC";
            $stmt = sqlsrv_query($conn, $sql);

            if ($stmt === false) return "error";

            $resultado = array();
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $resultado[] = $row;
            }
        }

        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
        return $resultado;
    }
}
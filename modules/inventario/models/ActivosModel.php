<?php
require_once __DIR__ . "/../../../config/db.php";


class ActivosModel {

    /*=============================================
    AGREGAR ACTIVOS 
    =============================================*/
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

        return $resultado['resultado'] ?? "error";
    }

    /*=============================================
    MOSTRAR ACTIVOS  (solo activo = 1)
    =============================================*/
    static public function mdlMostrarActivos($tabla, $item, $valor) {
        $conn = Conexion::conectar();

        if ($item != null) {
            // Consulta filtrada — solo registros activos
            $sql = "SELECT * FROM $tabla WHERE $item = ? AND activo = 1";
            $params = array($valor);
            $stmt = sqlsrv_query($conn, $sql, $params);

            if ($stmt === false) return "error";

            $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        } else {
            // Consulta general — solo registros activos
            $sql = "SELECT * FROM $tabla WHERE activo = 1 ORDER BY descripcion ASC";
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

    /*=============================================
    EDITAR ACTIVOS
    =============================================*/
    static public function mdlEditarActivo($tabla, $datos) {
        $conn = Conexion::conectar();
        $sql = "{call inventario.sp_EditarActivo(?, ?, ?, ?, ?)}";

        $params = array(
            array($datos["idActivos"],   SQLSRV_PARAM_IN),
            array($datos["descripcion"], SQLSRV_PARAM_IN),
            array($datos["compuesto"],   SQLSRV_PARAM_IN),
            array($datos["icono"],       SQLSRV_PARAM_IN),
            array($datos["usuario"],     SQLSRV_PARAM_IN)
        );

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            return "error";
        }

        $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        return $resultado['resultado'] ?? "error";
    }

    /*=============================================
    ELIMINAR ACTIVO (lógico via SP)
    =============================================*/
    static public function mdlEliminarActivo($datos) {
        $conn = Conexion::conectar();
        $sql = "{call inventario.sp_EliminarActivo(?, ?)}";

        $params = array(
            array($datos["idActivos"],           SQLSRV_PARAM_IN),
            array($datos["idUsuarioModifica"],   SQLSRV_PARAM_IN)
        );

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            return ["resultado" => "error", "mensaje" => "Error al ejecutar el SP."];
        }

        $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        return $resultado ?? ["resultado" => "error", "mensaje" => "Sin respuesta del SP."];
    }
}

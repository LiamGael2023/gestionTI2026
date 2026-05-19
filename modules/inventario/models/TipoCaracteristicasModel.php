<?php
require_once __DIR__ . "/../../../config/db.php";


class TipoCaracteristicasModel
{

    /*=============================================
    AGREGAR TIPO CARACTERISTICAS
    =============================================*/
    static public function mdlCrearTipoCaracteristica($tabla, $datos)
    {
        $conn = Conexion::conectar();
        $sql  = "{call inventario.sp_CrearTipoCaracteristica(?, ?)}";

        $params = array(
            array($datos["descripcion"],       SQLSRV_PARAM_IN),
            array($datos["idUsuarioRegistro"], SQLSRV_PARAM_IN)
        );

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) return "error";

        $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        return $resultado['resultado'] ?? "error";
    }

    /*=============================================
    EDITAR TIPO CARACTERISTICAS
    =============================================*/
    static public function mdlEditarTipoCaracteristica($tabla, $datos)
    {
        $conn = Conexion::conectar();
        $sql  = "{call inventario.sp_EditarTipoCaracteristica(?, ?, ?)}";

        $params = array(
            array($datos["idTipoCaracteristicas"], SQLSRV_PARAM_IN),
            array($datos["descripcion"],           SQLSRV_PARAM_IN),
            array($datos["usuario"],               SQLSRV_PARAM_IN)
        );

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) return "error";

        $fila      = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $resultado = $fila ? $fila['resultado'] : "error";

        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        return $resultado; // "ok" | "error_duplicado" | "error"
    }

    /*=============================================
    MOSTRAR TIPO CARACTERISTICAS  (solo activo = 1)
    =============================================*/
    static public function mdlMostrarTipoCaracteristicas($tabla, $item, $valor)
    {
        $conn = Conexion::conectar();

        if ($item != null) {
            // Consulta filtrada — solo registros activos
            $sql    = "SELECT * FROM $tabla WHERE $item = ? AND activo = 1";
            $params = array($valor);
            $stmt   = sqlsrv_query($conn, $sql, $params);

            if ($stmt === false) return "error";

            $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        } else {
            // Consulta general — solo registros activos
            $sql  = "SELECT * FROM $tabla WHERE activo = 1 ORDER BY descripcion ASC";
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
    ELIMINAR TIPO CARACTERISTICA (lógico via SP)
    =============================================*/
    static public function mdlEliminarTipoCaracteristica($datos)
    {
        $conn = Conexion::conectar();
        $sql  = "{call inventario.sp_EliminarTipoCaracteristica(?, ?)}";

        $params = array(
            array($datos["idTipoCaracteristica"], SQLSRV_PARAM_IN),
            array($datos["idUsuarioModifica"],    SQLSRV_PARAM_IN)
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

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
        $sql = "{call inventario.sp_CrearTipoCaracteristica(?, ?)}";

        $params = array(
            array($datos["descripcion"], SQLSRV_PARAM_IN),
            array($datos["idUsuarioRegistro"], SQLSRV_PARAM_IN)
        );

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            return "error_db";
        }

        // Leemos el valor simple que devuelve el SELECT 'ok'
        $fila = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
        $resultado = ($fila) ? $fila[0] : "error_vacio";

        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        return $resultado; // Retornará "ok", "error_duplicado", etc.
    }

    /*=============================================
    MOSTRAR TIPO CARACTERISTICAS
    =============================================*/
    static public function mdlMostrarTipoCaracteristicas($tabla, $item, $valor)
    {
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

    /*=============================================
    EDITAR TIPO CARACTERISTICAS
    =============================================*/
    static public function mdlEditarTipoCaracteristica($tabla, $datos)
    {
        $conn = Conexion::conectar();
        $sql = "{call inventario.sp_EditarTipoCaracteristica(?, ?, ?)}";

        $params = array(
            array($datos["idTipoCaracteristicas"], SQLSRV_PARAM_IN),
            array($datos["descripcion"], SQLSRV_PARAM_IN),
            array($datos["usuario"], SQLSRV_PARAM_IN)
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
}

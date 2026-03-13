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
        if (!$conn) {
            error_log("SQLSRV: conexión fallida en mdlEditarTipoCaracteristica");
            return ["status" => "error", "message" => "Conexión a BD fallida"];
        }

        $sql = "{call inventario.sp_EditarTipoCaracteristica(?, ?, ?)}";

        $id = (int) $datos["idTipoCaracteristicas"];
        $descripcion = substr(trim($datos["descripcion"]), 0, 100);
        $usuario = (int) $datos["usuario"];

        // Forzar NVARCHAR si tu BD usa Unicode
        $params = [
            [$id, SQLSRV_PARAM_IN],
            [$descripcion, SQLSRV_PARAM_IN],
            [$usuario, SQLSRV_PARAM_IN]
        ];

        $stmt = sqlsrv_prepare($conn, $sql, $params);
        if ($stmt === false) {
            $errs = sqlsrv_errors();
            error_log("sqlsrv_prepare error: " . print_r($errs, true));
            sqlsrv_close($conn);
            return ["status" => "error", "message" => "Error preparando la consulta", "detail" => $errs];
        }

        if (!sqlsrv_execute($stmt)) {
            $errs = sqlsrv_errors();
            error_log("sqlsrv_execute error: " . print_r($errs, true));
            sqlsrv_free_stmt($stmt);
            sqlsrv_close($conn);
            return ["status" => "error", "message" => "Error ejecutando el procedimiento", "detail" => $errs];
        }

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        if ($row === null || $row === false) {
            error_log("mdlEditarTipoCaracteristica: no se obtuvo resultado del SP");
            return ["status" => "error", "message" => "No se obtuvo resultado del procedimiento"];
        }

        $resultado = $row['resultado'] ?? null;
        $mensaje = $row['mensaje'] ?? null;

        if ($resultado === 'ok') {
            return "ok";
        } elseif ($resultado === 'error_duplicado') {
            return "error_duplicado";
        } else {
            error_log("SP returned error: " . print_r($row, true));
            return ["status" => "error", "message" => $mensaje ?? "Error desconocido", "detail" => $row];
        }
    }
}

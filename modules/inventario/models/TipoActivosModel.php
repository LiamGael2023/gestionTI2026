<?php
require_once __DIR__ . "/../../../config/db.php";

class TipoActivosModel
{
    /*=============================================
    AGREGAR TIPO ACTIVO
    =============================================*/
    static public function mdlCrearActivo($tabla, $datos)
    {
        $conn = Conexion::conectar();
        $sql  = "{call inventario.sp_CrearTipoActivo(?, ?, ?, ?, ?, ?)}";

        $params = array(
            array($datos["descripcion"],       SQLSRV_PARAM_IN),
            array($datos["icono"],             SQLSRV_PARAM_IN),
            array($datos["esCompuesto"],       SQLSRV_PARAM_IN),
            array($datos["esComponente"],      SQLSRV_PARAM_IN),
            array($datos["esPeriferico"],      SQLSRV_PARAM_IN),
            array($datos["idUsuarioRegistro"], SQLSRV_PARAM_IN)
        );

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            $msg    = $errors[0]['message'] ?? 'Error desconocido al ejecutar SP.';
            sqlsrv_close($conn);
            return ["resultado" => "error", "mensaje" => $msg];
        }

        $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        if (!$resultado) return ["resultado" => "error", "mensaje" => "Sin respuesta del SP."];

        return $resultado['resultado'] ?? "error";
    }

    /*=============================================
    MOSTRAR TIPO ACTIVOS  (solo activo = 1)
    =============================================*/
    static public function mdlMostrarActivos($tabla, $item, $valor)
    {
        $conn = Conexion::conectar();

        // JOIN con comun.Usuarios para traer nombres y apellidos del registrador
        $joinSql = "
            SELECT
                ta.*,
                LTRIM(RTRIM(ISNULL(u.nombres, '') + ' ' + ISNULL(u.apellidos, ''))) AS nombreUsuario
            FROM inventario.tipoActivo ta
            LEFT JOIN comun.Usuarios u ON u.id_usuario = ta.idUsuarioRegistro
        ";

        if ($item != null) {
            $sql    = $joinSql . " WHERE ta.$item = ? AND ta.activo = 1";
            $params = array($valor);
            $stmt   = sqlsrv_query($conn, $sql, $params);
            if ($stmt === false) { sqlsrv_close($conn); return "error"; }
            $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        } else {
            $sql  = $joinSql . " WHERE ta.activo = 1 ORDER BY ta.descripcion ASC";
            $stmt = sqlsrv_query($conn, $sql);
            if ($stmt === false) { sqlsrv_close($conn); return "error"; }
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
    EDITAR TIPO ACTIVO
    =============================================*/
    static public function mdlEditarActivo($tabla, $datos)
    {
        $conn = Conexion::conectar();
        $sql  = "{call inventario.sp_EditarTipoActivo(?, ?, ?, ?, ?, ?, ?)}";

        $params = array(
            array($datos["idTipoActivo"],  SQLSRV_PARAM_IN),
            array($datos["descripcion"],   SQLSRV_PARAM_IN),
            array($datos["esCompuesto"],   SQLSRV_PARAM_IN),
            array($datos["esComponente"],  SQLSRV_PARAM_IN),
            array($datos["esPeriferico"],  SQLSRV_PARAM_IN),
            array($datos["icono"],         SQLSRV_PARAM_IN),
            array($datos["usuario"],       SQLSRV_PARAM_IN)
        );

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            $msg    = $errors[0]['message'] ?? 'Error desconocido al ejecutar SP.';
            sqlsrv_close($conn);
            return ["resultado" => "error", "mensaje" => $msg];
        }

        $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        return $resultado['resultado'] ?? "error";
    }

    /*=============================================
    ELIMINAR TIPO ACTIVO (lógico via SP)
    =============================================*/
    static public function mdlEliminarActivo($datos)
    {
        $conn = Conexion::conectar();
        $sql  = "{call inventario.sp_EliminarTipoActivo(?, ?)}";

        $params = array(
            array($datos["idTipoActivo"],      SQLSRV_PARAM_IN),
            array($datos["idUsuarioModifica"], SQLSRV_PARAM_IN)
        );

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            $msg    = $errors[0]['message'] ?? 'Error desconocido al ejecutar SP.';
            sqlsrv_close($conn);
            return ["resultado" => "error", "mensaje" => $msg];
        }

        $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        return $resultado ?? ["resultado" => "error", "mensaje" => "Sin respuesta del SP."];
    }
}
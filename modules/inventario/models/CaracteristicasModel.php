<?php
require_once __DIR__ . "/../../../config/db.php";

class CaracteristicasModel
{
    /*=============================================
    AGREGAR CARACTERISTICA
    =============================================*/
    static public function mdlCrearCaracteristica($tabla, $datos)
    {
        $conn = Conexion::conectar();
        $sql  = "{call inventario.sp_CrearCaracteristica(?, ?, ?)}";

        $params = array(
            array($datos["idTipoCaracteristica"], SQLSRV_PARAM_IN),
            array($datos["valor"],                SQLSRV_PARAM_IN),
            array($datos["idUsuarioCreacion"],    SQLSRV_PARAM_IN)
        );

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            sqlsrv_close($conn);
            return "error";
        }

        $fila = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        return $fila['resultado'] ?? "error";
    }

    /*=============================================
    EDITAR CARACTERISTICA
    =============================================*/
    static public function mdlEditarCaracteristica($tabla, $datos)
    {
        $conn = Conexion::conectar();
        $sql  = "{call inventario.sp_EditarCaracteristica(?, ?, ?, ?)}";

        $params = array(
            array($datos["idCaracteristica"],     SQLSRV_PARAM_IN),
            array($datos["idTipoCaracteristica"], SQLSRV_PARAM_IN),
            array($datos["valor"],                SQLSRV_PARAM_IN),
            array($datos["idUsuarioModifica"],    SQLSRV_PARAM_IN)
        );

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            sqlsrv_close($conn);
            return "error";
        }

        $fila      = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $resultado = $fila['resultado'] ?? "error";

        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        return $resultado;
    }

    /*=============================================
    MOSTRAR CARACTERISTICAS — solo activo = 1
    =============================================*/
    static public function mdlMostrarCaracteristicas($tabla, $item, $valor)
    {
        $conn = Conexion::conectar();

        if ($item != null) {
            $sql = "SELECT c.*, t.descripcion AS tipoDescripcion
                    FROM $tabla c
                    LEFT JOIN inventario.tipoCaracteristica t
                      ON c.idTipoCaracteristica = t.idTipoCaracteristica
                    WHERE c.$item = ? AND c.activo = 1
                    ORDER BY c.valor ASC";

            $params = array($valor);
            $stmt   = sqlsrv_query($conn, $sql, $params);

            if ($stmt === false) {
                sqlsrv_close($conn);
                return [];
            }

            // Registro único cuando se filtra por PK (modal editar)
            if ($item === "idCaracteristica") {
                $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                $resultado = $resultado ?: [];
            } else {
                $resultado = [];
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $resultado[] = $row;
                }
            }

        } else {
            // Consulta general — solo activos
            $sql = "SELECT c.*, t.descripcion AS tipoDescripcion
                    FROM $tabla c
                    LEFT JOIN inventario.tipoCaracteristica t
                      ON c.idTipoCaracteristica = t.idTipoCaracteristica
                    WHERE c.activo = 1
                    ORDER BY c.valor ASC";

            $stmt = sqlsrv_query($conn, $sql);

            if ($stmt === false) {
                sqlsrv_close($conn);
                return [];
            }

            $resultado = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $resultado[] = $row;
            }
        }

        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
        return $resultado;
    }

    /*=============================================
    ELIMINAR CARACTERISTICA (lógico via SP)
    =============================================*/
    static public function mdlEliminarCaracteristica($datos)
    {
        $conn = Conexion::conectar();
        $sql  = "{call inventario.sp_EliminarCaracteristica(?, ?)}";

        $params = array(
            array($datos["idCaracteristica"],  SQLSRV_PARAM_IN),
            array($datos["idUsuarioModifica"], SQLSRV_PARAM_IN)
        );

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            sqlsrv_close($conn);
            return ["resultado" => "error", "mensaje" => "Error al ejecutar el SP."];
        }

        $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        return $resultado ?? ["resultado" => "error", "mensaje" => "Sin respuesta del SP."];
    }
}

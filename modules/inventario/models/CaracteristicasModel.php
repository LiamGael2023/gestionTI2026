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
        $sql = "{call inventario.sp_CrearCaracteristica(?, ?, ?)}";

        $params = array(
            array($datos["idTipoCaracteristica"], SQLSRV_PARAM_IN),
            array($datos["valor"], SQLSRV_PARAM_IN),
            array($datos["idUsuarioCreacion"], SQLSRV_PARAM_IN)
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
        $sql = "{call inventario.sp_EditarCaracteristica(?, ?, ?, ?)}";

        $params = array(
            array($datos["idCaracteristica"], SQLSRV_PARAM_IN),
            array($datos["idTipoCaracteristica"], SQLSRV_PARAM_IN),
            array($datos["valor"], SQLSRV_PARAM_IN),
            array($datos["idUsuarioModifica"], SQLSRV_PARAM_IN)
        );

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            sqlsrv_close($conn);
            return "error";
        }

        $fila = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $resultado = $fila['resultado'] ?? "error";

        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        return $resultado; // "ok" o "error_duplicado" u otro código del SP
    }

    /*=============================================
    MOSTRAR CARACTERISTICAS
    =============================================*/
    static public function mdlMostrarCaracteristicas($tabla, $item, $valor)
    {
        $conn = Conexion::conectar();

        if ($item != null) {
            // Consulta filtrada (incluye descripción del tipo)
            $sql = "SELECT c.*, t.descripcion AS tipoDescripcion
                    FROM $tabla c
                    LEFT JOIN inventario.tipoCaracteristica t
                      ON c.idTipoCaracteristica = t.idTipoCaracteristica
                    WHERE c.$item = ?";
            $params = array($valor);
            $stmt = sqlsrv_query($conn, $sql, $params);

            if ($stmt === false) {
                sqlsrv_close($conn);
                return "error";
            }

            $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        } else {
            // Consulta general con JOIN para traer la descripción del tipo
            $sql = "SELECT c.*, t.descripcion AS tipoDescripcion
                    FROM $tabla c
                    LEFT JOIN inventario.tipoCaracteristica t
                      ON c.idTipoCaracteristica = t.idTipoCaracteristica
                    ORDER BY c.valor ASC";
            $stmt = sqlsrv_query($conn, $sql);

            if ($stmt === false) {
                sqlsrv_close($conn);
                return "error";
            }

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

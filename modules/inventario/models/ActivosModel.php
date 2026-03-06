<?php
require_once "../../../config/db.php"; // Ruta hacia tu db.php

class ActivosModel
{
    static public function mdlCrearActivo($tabla, $datos)
    {

        $conn = Conexion::conectar();

        // Llamada al SP con los 4 parámetros definidos
        $sql = "{call inventario.sp_InsertarActivo(?, ?, ?, ?)}";

        $params = array(
            array($datos["descripcion"], SQLSRV_PARAM_IN),
            array($datos["icono"], SQLSRV_PARAM_IN),
            array($datos["compuesto"], SQLSRV_PARAM_IN),
            array($datos["idUsuarioRegistro"], SQLSRV_PARAM_IN)
        );

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            // Error en la ejecución
            return "error";
        }

        // Obtener el resultado 'ok' del SP
        $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        return $resultado['resultado'];
    }

    /*=============================================
MOSTRAR ACTIVOS
=============================================*/

    static public function mdlMostrarActivos($tabla, $item, $valor)
    {

        if ($item != null) {

            $stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla WHERE $item = :$item");

            $stmt->bindParam(":" . $item, $valor, PDO::PARAM_STR);

            $stmt->execute();

            return $stmt->fetch();
        } else {

            $stmt = Conexion::conectar()->prepare("SELECT * FROM $tabla ORDER BY descripcion ASC");

            $stmt->execute();

            return $stmt->fetchAll();
        }
    }
}

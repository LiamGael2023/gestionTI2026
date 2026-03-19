<?php
require_once __DIR__ . "/../../../config/db.php";

class UbicacionModel
{
    private static function ejecutarSP($sql, $params)
    {
        $conn = Conexion::conectar();
        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            sqlsrv_close($conn);
            return ["resultado" => "error", "mensaje" => "Error al ejecutar el procedimiento."];
        }
        $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
        return $resultado ?? ["resultado" => "error", "mensaje" => "Sin respuesta del servidor."];
    }

    static public function mdlCrearUbicacion($datos)
    {
        return self::ejecutarSP(
            "{call inventario.sp_CrearUbicacion(?, ?, ?)}",
            [
                [$datos["descripcion"],      SQLSRV_PARAM_IN],
                [$datos["idUbicacionPadre"], SQLSRV_PARAM_IN],
                [$datos["idUsuario"],        SQLSRV_PARAM_IN],
            ]
        );
    }

    static public function mdlEditarUbicacion($datos)
    {
        return self::ejecutarSP(
            "{call inventario.sp_EditarUbicacion(?, ?, ?, ?)}",
            [
                [$datos["idUbicacion"],      SQLSRV_PARAM_IN],
                [$datos["descripcion"],      SQLSRV_PARAM_IN],
                [$datos["idUbicacionPadre"], SQLSRV_PARAM_IN],
                [$datos["idUsuario"],        SQLSRV_PARAM_IN],
            ]
        );
    }

    static public function mdlMostrarUbicacion($item, $valor)
    {
        $conn = Conexion::conectar();

        $sql = "
            SELECT u.idUbicacion,
                   u.descripcion,
                   u.idUbicacionPadre,
                   p.descripcion AS descripcionPadre,
                   u.idUsuarioRegistro,
                   u.fechaCreacion,
                   u.idUsuarioModifica,
                   u.fechaModificacion
            FROM inventario.ubicacion u
            LEFT JOIN inventario.ubicacion p ON u.idUbicacionPadre = p.idUbicacion
        ";

        if ($item !== null) {
            $sql   .= " WHERE u.$item = ?";
            $params = [[$valor, SQLSRV_PARAM_IN]];
            $stmt   = sqlsrv_query($conn, $sql, $params);
            if ($stmt === false) { sqlsrv_close($conn); return "error"; }
            $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        } else {
            $sql  .= " ORDER BY u.descripcion ASC";
            $stmt  = sqlsrv_query($conn, $sql);
            if ($stmt === false) { sqlsrv_close($conn); return "error"; }
            $resultado = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $resultado[] = $row;
            }
        }

        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
        return $resultado;
    }
}

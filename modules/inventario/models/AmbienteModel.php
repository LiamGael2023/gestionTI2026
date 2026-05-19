<?php
require_once __DIR__ . "/../../../config/db.php";

class AmbienteModel
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

    static public function mdlCrearAmbiente($datos)
    {
        return self::ejecutarSP(
            "{call inventario.sp_CrearAmbiente(?, ?, ?)}",
            [
                [$datos["descripcion"], SQLSRV_PARAM_IN],
                [$datos["idUbicacion"], SQLSRV_PARAM_IN],
                [$datos["idUsuario"],   SQLSRV_PARAM_IN],
            ]
        );
    }

    static public function mdlEditarAmbiente($datos)
    {
        return self::ejecutarSP(
            "{call inventario.sp_EditarAmbiente(?, ?, ?, ?)}",
            [
                [$datos["idAmbiente"],  SQLSRV_PARAM_IN],
                [$datos["descripcion"], SQLSRV_PARAM_IN],
                [$datos["idUbicacion"], SQLSRV_PARAM_IN],
                [$datos["idUsuario"],   SQLSRV_PARAM_IN],
            ]
        );
    }

    static public function mdlMostrarAmbiente($item, $valor)
    {
        $conn = Conexion::conectar();

        $sql = "
            SELECT a.idAmbiente,
                   a.descripcion,
                   a.idUbicacion,
                   u.descripcion AS nombreUbicacion,
                   a.idUsuarioRegistro,
                   a.fechaCreacion,
                   a.idUsuarioModifica,
                   a.fechaModificacion
            FROM inventario.ambiente a
            INNER JOIN inventario.ubicacion u ON a.idUbicacion = u.idUbicacion
        ";

        if ($item !== null) {
            $sql   .= " WHERE a.$item = ?";
            $params = [[$valor, SQLSRV_PARAM_IN]];
            $stmt   = sqlsrv_query($conn, $sql, $params);
            if ($stmt === false) { sqlsrv_close($conn); return "error"; }
            $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        } else {
            $sql  .= " ORDER BY u.descripcion, a.descripcion ASC";
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

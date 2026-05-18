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

    // CTE Corregido: Construye la ruta de PADRE hacia HIJO
    $sql = "
    WITH Jerarquia AS (
        SELECT 
            idUbicacion, 
            idUbicacionPadre,
            descripcion,
            CAST(descripcion AS VARCHAR(MAX)) AS rutaPropia
        FROM inventario.ubicacion
        WHERE idUbicacionPadre IS NULL
        
        UNION ALL
        
        SELECT 
            u.idUbicacion,
            u.idUbicacionPadre,
            u.descripcion,
            CAST(j.rutaPropia + ' > ' + u.descripcion AS VARCHAR(MAX))
        FROM inventario.ubicacion u
        INNER JOIN Jerarquia j ON u.idUbicacionPadre = j.idUbicacion
    )
    SELECT u.*, j.rutaPropia,
            LTRIM(RTRIM(ISNULL(us.nombres, '') + ' ' + ISNULL(us.apellidos, ''))) as nombreUsuario
    FROM inventario.ubicacion u
    LEFT JOIN Jerarquia j ON u.idUbicacion = j.idUbicacion
    LEFT JOIN comun.Usuarios us ON us.id_usuario =  u.idUsuarioRegistro";

    if ($item !== null) {
        $sql .= " WHERE u.$item = ?";
        $stmt = sqlsrv_query($conn, $sql, [[$valor, SQLSRV_PARAM_IN]]);
        return ($stmt === false) ? "error" : sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    } else {
        $sql .= " ORDER BY j.rutaPropia ASC";
        $stmt = sqlsrv_query($conn, $sql);
        if ($stmt === false) return "error";
        $resultado = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $resultado[] = $row;
        }
        return $resultado;
    }
}
}

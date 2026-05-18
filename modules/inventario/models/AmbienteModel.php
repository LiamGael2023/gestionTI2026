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
        WITH JerarquiaUbicaciones AS (
            -- Ancla: Sedes principales
            SELECT 
                idUbicacion, 
                CAST(descripcion AS VARCHAR(MAX)) AS rutaCompleta
            FROM inventario.ubicacion
            WHERE idUbicacionPadre IS NULL
            
            UNION ALL
            
            -- Recursión: Construcción de la ruta
            SELECT 
                u.idUbicacion,
                CAST(u.descripcion + ' – ' + j.rutaCompleta AS VARCHAR(MAX))
            FROM inventario.ubicacion u
            INNER JOIN JerarquiaUbicaciones j ON u.idUbicacionPadre = j.idUbicacion
        )
        SELECT 
            a.*, 
            j.rutaCompleta AS nombreUbicacion,
            LTRIM(RTRIM(ISNULL(us.nombres, '') + ' ' + ISNULL(us.apellidos, ''))) as nombreUsuario
        FROM inventario.ambiente a
        LEFT JOIN JerarquiaUbicaciones j ON a.idUbicacion = j.idUbicacion
        LEFT JOIN comun.Usuarios us ON us.id_usuario =  a.idUsuarioRegistro
    ";

        if ($item !== null) {
            $sql .= " WHERE a.$item = ? ";
            $stmt = sqlsrv_query($conn, $sql, [[$valor, SQLSRV_PARAM_IN]]);
            $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        } else {
            $sql .= " ORDER BY a.descripcion ASC";
            $stmt = sqlsrv_query($conn, $sql);
            $resultado = [];
            if ($stmt !== false) {
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                    $resultado[] = $row;
                }
            }
        }

        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
        return $resultado;
    }

   static public function mdlEliminarAmbiente($idAmbiente)
{
    return self::ejecutarSP(
        "{call inventario.sp_EliminarAmbiente(?)}",
        [
            [$idAmbiente, SQLSRV_PARAM_IN],
        ]
    );
}
}

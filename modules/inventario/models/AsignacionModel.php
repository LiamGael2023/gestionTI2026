<?php
require_once __DIR__ . "/../../../config/db.php";

class AsignacionModel
{
    /* ── Crear / reasignar ── */
    static public function mdlCrearAsignacion($datos)
    {
        $conn = Conexion::conectar();
        $stmt = sqlsrv_query(
            $conn,
            "{call inventario.sp_CrearAsignacion(?, ?, ?, ?, ?, ?, ?, ?, ?)}",
            [
                [$datos['idEstacion'], SQLSRV_PARAM_IN],
                [$datos['idAmbiente'], SQLSRV_PARAM_IN],
                [$datos['dniTrabajadorResponsable'], SQLSRV_PARAM_IN],
                [$datos['trabajadorResponsable'], SQLSRV_PARAM_IN],
                [$datos['trabajadorAsignado'], SQLSRV_PARAM_IN],
                [$datos['fechaAsignacion'], SQLSRV_PARAM_IN],
                [$datos['motivoCambio'], SQLSRV_PARAM_IN],
                [$datos['observaciones'], SQLSRV_PARAM_IN],
                [$datos['idUsuario'], SQLSRV_PARAM_IN],
            ]
        );
        if ($stmt === false) {
            sqlsrv_close($conn);
            return ["resultado" => "error", "mensaje" => "Error SP."];
        }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
        return $row ?? ["resultado" => "error", "mensaje" => "Sin respuesta."];
    }

    /* ── Liberar ── */
    static public function mdlLiberarAsignacion($datos)
    {
        $conn = Conexion::conectar();
        $stmt = sqlsrv_query(
            $conn,
            "{call inventario.sp_LiberarAsignacion(?, ?, ?, ?)}",
            [
                [$datos['idAsignacion'], SQLSRV_PARAM_IN],
                [$datos['fechaLiberacion'], SQLSRV_PARAM_IN],
                [$datos['motivoCambio'], SQLSRV_PARAM_IN],
                [$datos['idUsuario'], SQLSRV_PARAM_IN],
            ]
        );
        if ($stmt === false) {
            sqlsrv_close($conn);
            return ["resultado" => "error", "mensaje" => "Error SP."];
        }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
        return $row ?? ["resultado" => "error", "mensaje" => "Sin respuesta."];
    }

    /* ── Listar asignaciones activas (tabla principal) ── */
    static public function mdlListarActivas()
    {
        $conn = Conexion::conectar();
        $sql = "
                        WITH jerarquia AS (
    -- Ancla: Sedes principales (Raíz)
    SELECT
        idUbicacion,
        idUbicacionPadre,
        descripcion,
        idUbicacion AS idSede,
        descripcion AS nombreSede
    FROM inventario.ubicacion
    WHERE idUbicacionPadre IS NULL

    UNION ALL

    -- Recursión: Sub-ubicaciones (niveles inferiores)
    SELECT
        u.idUbicacion,
        u.idUbicacionPadre,
        u.descripcion,
        j.idSede,
        j.nombreSede
    FROM inventario.ubicacion u
    INNER JOIN jerarquia j ON u.idUbicacionPadre = j.idUbicacion
)
SELECT
    -- Datos de la Asignación
    a.idAsignacion,
    a.idEstacion,
    est.nombreEstacion,
    ip.ipAddress,
    
    -- Datos del Ambiente y Ubicación
    a.idAmbiente,
    amb.descripcion AS nombreAmbiente,
    j.descripcion AS nombreUbicacion,
    
    -- Datos de la Sede (Raíz)
    j.idSede,
    j.nombreSede,
    
    -- Datos del Personal y Auditoría
    a.dniTrabajadorResponsable,
    a.trabajadorResponsable,
    a.trabajadorAsignado,
    a.fechaAsignacion,
    a.observaciones,
    a.idUsuarioRegistro,
    a.fechaCreacion,
    LTRIM(RTRIM(ISNULL(u.nombres, '') + ' ' + ISNULL(u.apellidos, ''))) as nombreUsuario
FROM inventario.asignacion a
INNER JOIN inventario.estacion est ON a.idEstacion = est.idEstacion
LEFT JOIN inventario.ip ip ON est.idEstacion = ip.idEstacion
LEFT JOIN inventario.ambiente amb ON a.idAmbiente = amb.idAmbiente
LEFT JOIN comun.Usuarios u ON u.id_usuario = a.idUsuarioRegistro
-- Unimos con la jerarquía a través del idUbicacion del ambiente
LEFT JOIN jerarquia j ON amb.idUbicacion = j.idUbicacion
WHERE a.fechaLiberacion IS NULL
ORDER BY j.nombreSede ASC, j.descripcion ASC, amb.descripcion ASC, est.nombreEstacion ASC
        ";
        $stmt = sqlsrv_query($conn, $sql);
        $rows = [];
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))
                $rows[] = $row;
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);
        return $rows;
    }

    /* ── Historial de una estación ── */
    static public function mdlHistorialEstacion(int $idEstacion)
    {
        $conn = Conexion::conectar();
        $sql = "
            SELECT
                a.idAsignacion,
                a.dniTrabajadorResponsable,
                a.trabajadorResponsable,
                a.trabajadorAsignado,
                amb.descripcion AS nombreAmbiente,
                a.fechaAsignacion,
                a.fechaLiberacion,
                a.motivoCambio,
                a.observaciones,
                a.idUsuarioRegistro,
                a.fechaCreacion
            FROM inventario.asignacion a
            LEFT JOIN inventario.ambiente amb ON a.idAmbiente = amb.idAmbiente
            WHERE a.idEstacion = ?
            ORDER BY a.fechaAsignacion DESC
        ";
        $stmt = sqlsrv_query($conn, $sql, [[$idEstacion, SQLSRV_PARAM_IN]]);
        $rows = [];
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))
                $rows[] = $row;
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);
        return $rows;
    }

    /* ── Asignación activa de una estación ── */
    static public function mdlAsignacionActiva(int $idEstacion)
    {
        $conn = Conexion::conectar();
        $sql = "
            SELECT TOP 1
                a.idAsignacion, a.idEstacion, a.idAmbiente,
                a.dniTrabajadorResponsable, a.trabajadorResponsable,
                a.trabajadorAsignado, a.fechaAsignacion, a.observaciones
            FROM inventario.asignacion a
            WHERE a.idEstacion = ? AND a.fechaLiberacion IS NULL
        ";
        $stmt = sqlsrv_query($conn, $sql, [[$idEstacion, SQLSRV_PARAM_IN]]);
        $row = null;
        if ($stmt !== false) {
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);
        return $row;
    }

    /* ── Listar ambientes para combo ── */
    static public function mdlListarAmbientes()
    {
        $conn = Conexion::conectar();
        /* CTE recursivo: sube desde la ubicación del ambiente hasta la raíz (sede).
           Devuelve por cada ambiente:
             - idUbicacion        : la ubicación directa donde está el ambiente
             - nombreUbicacion    : descripción de esa ubicación directa
             - idSede             : id del nodo raíz (idUbicacionPadre IS NULL)
             - nombreSede         : descripción del nodo raíz
        */
        $sql = "
            WITH jerarquia AS (
                -- Ancla: todas las ubicaciones con su nivel
                SELECT
                    idUbicacion,
                    idUbicacionPadre,
                    descripcion,
                    idUbicacion      AS idRaiz,
                    descripcion      AS nombreRaiz
                FROM inventario.ubicacion
                WHERE idUbicacionPadre IS NULL

                UNION ALL

                SELECT
                    u.idUbicacion,
                    u.idUbicacionPadre,
                    u.descripcion,
                    j.idRaiz,
                    j.nombreRaiz
                FROM inventario.ubicacion u
                INNER JOIN jerarquia j ON u.idUbicacionPadre = j.idUbicacion
            )
            SELECT
                a.idAmbiente,
                a.idUbicacion,
                a.descripcion,
                j.descripcion  AS nombreUbicacion,
                j.idRaiz       AS idSede,
                j.nombreRaiz   AS nombreSede
            FROM inventario.ambiente a
            LEFT JOIN jerarquia j ON a.idUbicacion = j.idUbicacion
            ORDER BY j.nombreRaiz ASC, j.descripcion ASC, a.descripcion ASC
        ";
        $stmt = sqlsrv_query($conn, $sql);
        $rows = [];
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))
                $rows[] = [
                    "idAmbiente" => intval($row["idAmbiente"]),
                    "idUbicacion" => $row["idUbicacion"] !== null ? intval($row["idUbicacion"]) : null,
                    "descripcion" => $row["descripcion"],
                    "nombreUbicacion" => $row["nombreUbicacion"] ?? "",
                    "idSede" => $row["idSede"] !== null ? intval($row["idSede"]) : null,
                    "nombreSede" => $row["nombreSede"] ?? "",
                ];
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);
        return $rows;
    }

    /* ── Listar estaciones sin asignación activa ── */
    static public function mdlEstacionesSinAsignacion()
    {
        $conn = Conexion::conectar();
        $sql = "
            SELECT est.idEstacion, est.nombreEstacion, ip.ipAddress
            FROM inventario.estacion est
            LEFT JOIN inventario.ip ip ON est.idEstacion = ip.idEstacion
            WHERE est.idEstacion NOT IN (
                SELECT idEstacion FROM inventario.asignacion
                WHERE fechaLiberacion IS NULL
            )
            ORDER BY est.nombreEstacion ASC
        ";
        $stmt = sqlsrv_query($conn, $sql);
        $rows = [];
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))
                $rows[] = $row;
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);
        return $rows;
    }

    /* ── Equipos de una estación para el reporte ── */
    static public function mdlEquiposEstacion(int $idEstacion)
    {
        $conn = Conexion::conectar();
        $sql = "
            SELECT
                e.idActivo,
                e.codigoPatrimonial,
                e.numeroSerie,
                a.descripcion  AS nombreActivo,
                a.icono        AS iconoActivo,
                a.esCompuesto,
                CASE
                    WHEN UPPER(a.descripcion) = 'SOFTWARE' THEN 'Software'
                    WHEN a.esCompuesto = 1 THEN 'Equipo Principal'
                    ELSE 'Periférico'
                END AS tipoEquipo,
                STRING_AGG(tc.descripcion + ': ' + c.valor, ', ') AS caracteristicas
            FROM inventario.estacionActivo ee
            INNER JOIN inventario.activo  e  ON ee.idActivo         = e.idActivo
            INNER JOIN inventario.tipoActivo a  ON e.idTipoActivo           = a.idTipoActivo
            LEFT  JOIN inventario.activoCaracteristica ec ON e.idActivo = ec.idActivo
            LEFT  JOIN inventario.caracteristicas      c  ON ec.idCaracteristica = c.idCaracteristica
            LEFT  JOIN inventario.tipoCaracteristica   tc ON c.idTipoCaracteristica = tc.idTipoCaracteristica
            WHERE ee.idEstacion = ?
            GROUP BY e.idActivo, e.codigoPatrimonial, e.numeroSerie,
                     a.descripcion, a.icono, a.esCompuesto
            ORDER BY a.esCompuesto DESC, a.descripcion ASC
        ";
        $stmt = sqlsrv_query($conn, $sql, [[$idEstacion, SQLSRV_PARAM_IN]]);
        $rows = [];
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $row['esCompuesto'] = ($row['esCompuesto'] === true || $row['esCompuesto'] === 1);
                $rows[] = $row;
            }
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);
        return $rows;
    }
}
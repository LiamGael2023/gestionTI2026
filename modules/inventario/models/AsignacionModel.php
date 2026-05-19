<?php
require_once __DIR__ . "/../../../config/db.php";

class AsignacionModel
{
    /* ── Crear / reasignar ── */
    static public function mdlCrearAsignacion($datos)
    {
        $conn = Conexion::conectar();
        $stmt = sqlsrv_query($conn,
            "{call inventario.sp_CrearAsignacion(?, ?, ?, ?, ?, ?, ?, ?, ?)}",
            [
                [$datos['idEstacion'],               SQLSRV_PARAM_IN],
                [$datos['idAmbiente'],               SQLSRV_PARAM_IN],
                [$datos['dniTrabajadorResponsable'],  SQLSRV_PARAM_IN],
                [$datos['trabajadorResponsable'],     SQLSRV_PARAM_IN],
                [$datos['trabajadorAsignado'],        SQLSRV_PARAM_IN],
                [$datos['fechaAsignacion'],           SQLSRV_PARAM_IN],
                [$datos['motivoCambio'],              SQLSRV_PARAM_IN],
                [$datos['observaciones'],             SQLSRV_PARAM_IN],
                [$datos['idUsuario'],                 SQLSRV_PARAM_IN],
            ]
        );
        if ($stmt === false) { sqlsrv_close($conn); return ["resultado"=>"error","mensaje"=>"Error SP."]; }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt); sqlsrv_close($conn);
        return $row ?? ["resultado"=>"error","mensaje"=>"Sin respuesta."];
    }

    /* ── Liberar ── */
    static public function mdlLiberarAsignacion($datos)
    {
        $conn = Conexion::conectar();
        $stmt = sqlsrv_query($conn,
            "{call inventario.sp_LiberarAsignacion(?, ?, ?, ?)}",
            [
                [$datos['idAsignacion'],   SQLSRV_PARAM_IN],
                [$datos['fechaLiberacion'],SQLSRV_PARAM_IN],
                [$datos['motivoCambio'],   SQLSRV_PARAM_IN],
                [$datos['idUsuario'],      SQLSRV_PARAM_IN],
            ]
        );
        if ($stmt === false) { sqlsrv_close($conn); return ["resultado"=>"error","mensaje"=>"Error SP."]; }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt); sqlsrv_close($conn);
        return $row ?? ["resultado"=>"error","mensaje"=>"Sin respuesta."];
    }

    /* ── Listar asignaciones activas (tabla principal) ── */
    static public function mdlListarActivas()
    {
        $conn = Conexion::conectar();
        $sql  = "
            SELECT
                a.idAsignacion,
                a.idEstacion,
                est.nombreEstacion,
                ip.ipAddress,
                a.idAmbiente,
                amb.descripcion         AS nombreAmbiente,
                a.dniTrabajadorResponsable,
                a.trabajadorResponsable,
                a.trabajadorAsignado,
                a.fechaAsignacion,
                a.observaciones,
                a.idUsuarioRegistro,
                a.fechaCreacion
            FROM inventario.asignacion a
            INNER JOIN inventario.estacion est ON a.idEstacion = est.idEstacion
            LEFT  JOIN inventario.ip       ip  ON est.idIp    = ip.idIp
            LEFT  JOIN inventario.ambiente amb ON a.idAmbiente = amb.idAmbiente
            WHERE a.fechaLiberacion IS NULL
            ORDER BY est.nombreEstacion ASC
        ";
        $stmt = sqlsrv_query($conn, $sql);
        $rows = [];
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $rows[] = $row;
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);
        return $rows;
    }

    /* ── Historial de una estación ── */
    static public function mdlHistorialEstacion(int $idEstacion)
    {
        $conn = Conexion::conectar();
        $sql  = "
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
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $rows[] = $row;
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);
        return $rows;
    }

    /* ── Asignación activa de una estación ── */
    static public function mdlAsignacionActiva(int $idEstacion)
    {
        $conn = Conexion::conectar();
        $sql  = "
            SELECT TOP 1
                a.idAsignacion, a.idEstacion, a.idAmbiente,
                a.dniTrabajadorResponsable, a.trabajadorResponsable,
                a.trabajadorAsignado, a.fechaAsignacion, a.observaciones
            FROM inventario.asignacion a
            WHERE a.idEstacion = ? AND a.fechaLiberacion IS NULL
        ";
        $stmt = sqlsrv_query($conn, $sql, [[$idEstacion, SQLSRV_PARAM_IN]]);
        $row  = null;
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
        $sql  = "
            SELECT a.idAmbiente, a.descripcion, u.descripcion AS nombreUbicacion
            FROM inventario.ambiente a
            LEFT JOIN inventario.ubicacion u ON a.idUbicacion = u.idUbicacion
            ORDER BY a.descripcion ASC
        ";
        $stmt = sqlsrv_query($conn, $sql);
        $rows = [];
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))
                $rows[] = [
                    "idAmbiente"      => intval($row["idAmbiente"]),
                    "descripcion"     => $row["descripcion"],
                    "nombreUbicacion" => $row["nombreUbicacion"] ?? "",
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
        $sql  = "
            SELECT est.idEstacion, est.nombreEstacion, ip.ipAddress
            FROM inventario.estacion est
            LEFT JOIN inventario.ip ip ON est.idIp = ip.idIp
            WHERE est.idEstacion NOT IN (
                SELECT idEstacion FROM inventario.asignacion
                WHERE fechaLiberacion IS NULL
            )
            ORDER BY est.nombreEstacion ASC
        ";
        $stmt = sqlsrv_query($conn, $sql);
        $rows = [];
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $rows[] = $row;
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);
        return $rows;
    }

    /* ── Equipos de una estación para el reporte ── */
    static public function mdlEquiposEstacion(int $idEstacion)
    {
        $conn = Conexion::conectar();
        $sql  = "
            SELECT
                e.idEquipo,
                e.codigoPatrimonial,
                e.numeroSerie,
                a.descripcion  AS nombreActivo,
                a.icono        AS iconoActivo,
                a.compuesto,
                CASE
                    WHEN UPPER(a.descripcion) = 'SOFTWARE' THEN 'Software'
                    WHEN a.compuesto = 1 THEN 'Equipo Principal'
                    ELSE 'Periférico'
                END AS tipoEquipo,
                STRING_AGG(tc.descripcion + ': ' + c.valor, ', ') AS caracteristicas
            FROM inventario.estacionEquipo ee
            INNER JOIN inventario.equipo  e  ON ee.idEquipo          = e.idEquipo
            INNER JOIN inventario.activos a  ON e.idActivo           = a.idActivos
            LEFT  JOIN inventario.equipoCaracteristica ec ON e.idEquipo = ec.idEquipo
            LEFT  JOIN inventario.caracteristicas      c  ON ec.idCaracteristica = c.idCaracteristica
            LEFT  JOIN inventario.tipoCaracteristica   tc ON c.idTipoCaracteristica = tc.idTipoCaracteristica
            WHERE ee.idEstacion = ?
            GROUP BY e.idEquipo, e.codigoPatrimonial, e.numeroSerie,
                     a.descripcion, a.icono, a.compuesto
            ORDER BY a.compuesto DESC, a.descripcion ASC
        ";
        $stmt = sqlsrv_query($conn, $sql, [[$idEstacion, SQLSRV_PARAM_IN]]);
        $rows = [];
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $row['compuesto'] = ($row['compuesto'] === true || $row['compuesto'] === 1);
                $rows[] = $row;
            }
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);
        return $rows;
    }
}

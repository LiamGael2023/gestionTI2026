<?php
require_once __DIR__ . "/../../../config/db.php";

class ReporteAsignacionModel
{
    const API_PERSONAL = 'https://www.chavimochic.gob.pe/api_incidencias/api_personal.php';

    /* ════════════════════════════════════════════════════════
       OBTENER PERSONAL DESDE API
    ════════════════════════════════════════════════════════ */
    static public function mdlObtenerPersonalApi(): array
    {
        try {
            $ctx  = stream_context_create(['http' => ['timeout' => 10]]);
            $json = @file_get_contents(self::API_PERSONAL, false, $ctx);
            if (!$json) return [];
            $decoded = json_decode($json, true);
            return ($decoded['success'] ?? false) ? ($decoded['data'] ?? []) : [];
        } catch (Exception $e) {
            return [];
        }
    }

    /* ════════════════════════════════════════════════════════
       TIPOS DE ACTIVO
    ════════════════════════════════════════════════════════ */
    static public function mdlListarTiposActivo(): array
    {
        $conn = Conexion::conectar();
        $sql  = "SELECT idTipoActivo, descripcion, icono
                 FROM inventario.tipoActivo WHERE activo = 1
                 ORDER BY descripcion ASC";
        $stmt = sqlsrv_query($conn, $sql);
        $rows = [];
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $rows[] = $row;
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);
        return $rows;
    }

    /* ════════════════════════════════════════════════════════
       REPORTE PRINCIPAL — activos PADRE por trabajador
    ════════════════════════════════════════════════════════ */
    static public function mdlReporteAsignaciones(array $filtros = []): array
    {
        $conn = Conexion::conectar();
        $params = []; $whereExtra = '';

        if (!empty($filtros['idTipoActivo'])) {
            $whereExtra .= " AND ta.idTipoActivo = ?";
            $params[]    = [intval($filtros['idTipoActivo']), SQLSRV_PARAM_IN];
        }
        if (!empty($filtros['fechaDesde'])) {
            $whereExtra .= " AND a.fechaAsignacion >= ?";
            $params[]    = [$filtros['fechaDesde'], SQLSRV_PARAM_IN];
        }
        if (!empty($filtros['fechaHasta'])) {
            $whereExtra .= " AND a.fechaAsignacion <= ?";
            $params[]    = [$filtros['fechaHasta'], SQLSRV_PARAM_IN];
        }

        $inDnis = '';
        if (!empty($filtros['dnis']) && is_array($filtros['dnis'])) {
            $safe   = array_map(fn($d) => "'" . str_replace("'", "''", $d) . "'", $filtros['dnis']);
            $inDnis = " AND a.dniTrabajadorResponsable IN (" . implode(',', $safe) . ")";
        }

        $sql = "
            SELECT
                a.dniTrabajadorResponsable                 AS dni,
                a.trabajadorResponsable                     AS nombreTrabajador,
                ta.idTipoActivo,
                ta.descripcion                              AS tipoActivo,
                ta.icono                                    AS iconoTipo,
                COUNT(DISTINCT act.idActivo)                AS cantidad,
                MIN(CONVERT(VARCHAR,a.fechaAsignacion,103)) AS fechaAsignacion,
                est.nombreEstacion,
                ISNULL(ub.descripcion,'')                   AS ubicacion,
                ISNULL(amb.descripcion,'')                  AS ambiente
            FROM inventario.asignacion       a
            INNER JOIN inventario.estacion       est ON a.idEstacion     = est.idEstacion
            INNER JOIN inventario.estacionActivo ea  ON ea.idEstacion    = est.idEstacion
            INNER JOIN inventario.activo         act ON ea.idActivo      = act.idActivo
            INNER JOIN inventario.tipoActivo     ta  ON act.idTipoActivo = ta.idTipoActivo
            LEFT  JOIN inventario.ambiente       amb ON a.idAmbiente     = amb.idAmbiente
            LEFT  JOIN inventario.ubicacion      ub  ON amb.idUbicacion  = ub.idUbicacion
            WHERE a.fechaLiberacion IS NULL
              AND est.activo = 1 AND act.activo = 1 AND ta.activo = 1
              AND act.idActivoPadre IS NULL
              $whereExtra $inDnis
            GROUP BY
                a.dniTrabajadorResponsable, a.trabajadorResponsable,
                ta.idTipoActivo, ta.descripcion, ta.icono,
                est.nombreEstacion, ub.descripcion, amb.descripcion
            ORDER BY a.dniTrabajadorResponsable, ta.descripcion
        ";

        $stmt = empty($params) ? sqlsrv_query($conn, $sql) : sqlsrv_query($conn, $sql, $params);
        $rows = [];
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $rows[] = $row;
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);
        return $rows;
    }

    /* ════════════════════════════════════════════════════════
       DETALLE POR DNI + TIPO — incluye activos hijos/componentes
    ════════════════════════════════════════════════════════ */
    static public function mdlDetalleActivosTrabajador(string $dni, int $idTipoActivo): array
    {
        $conn = Conexion::conectar();

        $sqlPadres = "
            SELECT
                act.idActivo,
                act.codigoPatrimonial,
                act.numeroSerie,
                act.codigoLicencia,
                act.estado,
                act.idActivoPadre,
                ISNULL(CONVERT(VARCHAR,act.fechaAdquisicion,103),'')    AS fechaAdquisicion,
                ISNULL(CONVERT(VARCHAR,act.fechaInicioGarantia,103),'') AS fechaInicioGarantia,
                ISNULL(CONVERT(VARCHAR,act.fechaFinGarantia,103),'')    AS fechaFinGarantia,
                ta.idTipoActivo,
                ta.descripcion  AS tipoActivo,
                ta.icono        AS iconoTipo,
                est.nombreEstacion,
                CONVERT(VARCHAR, a.fechaAsignacion, 103) AS fechaAsignacion,
                ISNULL(ub.descripcion,'')  AS ubicacion,
                ISNULL(amb.descripcion,'') AS ambiente
            FROM inventario.asignacion      a
            INNER JOIN inventario.estacion       est ON a.idEstacion     = est.idEstacion
            INNER JOIN inventario.estacionActivo ea  ON ea.idEstacion    = est.idEstacion
            INNER JOIN inventario.activo         act ON ea.idActivo      = act.idActivo
            INNER JOIN inventario.tipoActivo     ta  ON act.idTipoActivo = ta.idTipoActivo
            LEFT  JOIN inventario.ambiente       amb ON a.idAmbiente     = amb.idAmbiente
            LEFT  JOIN inventario.ubicacion      ub  ON amb.idUbicacion  = ub.idUbicacion
            WHERE a.dniTrabajadorResponsable = ?
              AND ta.idTipoActivo = ?
              AND a.fechaLiberacion IS NULL
              AND est.activo = 1 AND act.activo = 1
              AND act.idActivoPadre IS NULL
            ORDER BY act.codigoPatrimonial
        ";

        $stmtP = sqlsrv_query($conn, $sqlPadres, [
            [$dni,          SQLSRV_PARAM_IN],
            [$idTipoActivo, SQLSRV_PARAM_IN],
        ]);
        $padres = [];
        if ($stmtP !== false) {
            while ($row = sqlsrv_fetch_array($stmtP, SQLSRV_FETCH_ASSOC)) $padres[] = $row;
            sqlsrv_free_stmt($stmtP);
        }

        if (empty($padres)) { sqlsrv_close($conn); return []; }

        $inIds  = implode(',', array_map(fn($p) => intval($p['idActivo']), $padres));
        $padres = self::_agregarHijos($conn, $padres, $inIds);

        sqlsrv_close($conn);
        return $padres;
    }

    /* ════════════════════════════════════════════════════════
       BÚSQUEDA POR CÓDIGO PATRIMONIAL — con hijos y trabajador
    ════════════════════════════════════════════════════════ */
    static public function mdlBuscarPorCodigoPatrimonial(string $codigo): array
    {
        if (trim($codigo) === '') return [];
        $conn     = Conexion::conectar();
        $busqueda = '%' . $codigo . '%';

        $sql = "
            SELECT
                act.idActivo,
                act.codigoPatrimonial,
                act.numeroSerie,
                act.codigoLicencia,
                act.estado,
                act.idActivoPadre,
                ISNULL(CONVERT(VARCHAR,act.fechaAdquisicion,103),'')    AS fechaAdquisicion,
                ISNULL(CONVERT(VARCHAR,act.fechaInicioGarantia,103),'') AS fechaInicioGarantia,
                ISNULL(CONVERT(VARCHAR,act.fechaFinGarantia,103),'')    AS fechaFinGarantia,
                ta.idTipoActivo,
                ta.descripcion        AS tipoActivo,
                ta.icono              AS iconoTipo,
                -- Trabajador con asignación activa
                ISNULL(asig.dniTrabajadorResponsable,'') AS dniAsignado,
                ISNULL(asig.trabajadorResponsable,'')    AS nombreAsignado,
                ISNULL(CONVERT(VARCHAR,asig.fechaAsignacion,103),'')    AS fechaAsignacion,
                ISNULL(est.nombreEstacion,'')            AS nombreEstacion,
                ISNULL(ub.descripcion,'')                AS ubicacion,
                ISNULL(amb.descripcion,'')               AS ambiente,
                -- Activo padre (si este activo es componente)
                ISNULL(padre.codigoPatrimonial,'')  AS codigoPatrimonialPadre,
                ISNULL(taPadre.descripcion,'')      AS tipoPadre
            FROM inventario.activo act
            INNER JOIN inventario.tipoActivo     ta      ON act.idTipoActivo   = ta.idTipoActivo
            LEFT  JOIN inventario.estacionActivo ea      ON ea.idActivo        = act.idActivo
            LEFT  JOIN inventario.estacion       est     ON ea.idEstacion      = est.idEstacion
            LEFT  JOIN inventario.asignacion     asig    ON asig.idEstacion    = est.idEstacion
                                                        AND asig.fechaLiberacion IS NULL
            LEFT  JOIN inventario.ambiente       amb     ON asig.idAmbiente    = amb.idAmbiente
            LEFT  JOIN inventario.ubicacion      ub      ON amb.idUbicacion    = ub.idUbicacion
            LEFT  JOIN inventario.activo         padre   ON act.idActivoPadre  = padre.idActivo
            LEFT  JOIN inventario.tipoActivo     taPadre ON padre.idTipoActivo = taPadre.idTipoActivo
            WHERE act.activo = 1
              AND act.codigoPatrimonial LIKE ?
            ORDER BY act.codigoPatrimonial
        ";

        $stmt    = sqlsrv_query($conn, $sql, [[$busqueda, SQLSRV_PARAM_IN]]);
        $activos = [];
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $activos[] = $row;
            sqlsrv_free_stmt($stmt);
        }

        if (empty($activos)) { sqlsrv_close($conn); return []; }

        // Agregar hijos solo a los activos raíz (que no son hijos de otro)
        $raices  = array_filter($activos, fn($a) => empty($a['idActivoPadre']));
        $idsR    = implode(',', array_map(fn($a) => intval($a['idActivo']), $raices));
        if ($idsR) {
            $activos = self::_agregarHijos($conn, $activos, $idsR);
        } else {
            foreach ($activos as &$a) $a['hijos'] = [];
        }

        sqlsrv_close($conn);
        return $activos;
    }

    /* ════════════════════════════════════════════════════════
       HELPER PRIVADO — adjuntar hijos a array de padres
    ════════════════════════════════════════════════════════ */
    private static function _agregarHijos($conn, array $padres, string $inIds): array
    {
        $sqlH = "
            SELECT
                hijo.idActivo,
                hijo.idActivoPadre,
                hijo.codigoPatrimonial,
                hijo.numeroSerie,
                hijo.codigoLicencia,
                hijo.estado,
                ISNULL(CONVERT(VARCHAR,hijo.fechaAdquisicion,103),'')    AS fechaAdquisicion,
                ISNULL(CONVERT(VARCHAR,hijo.fechaInicioGarantia,103),'') AS fechaInicioGarantia,
                ISNULL(CONVERT(VARCHAR,hijo.fechaFinGarantia,103),'')    AS fechaFinGarantia,
                tah.idTipoActivo,
                tah.descripcion  AS tipoActivo,
                tah.icono        AS iconoTipo
            FROM inventario.activo hijo
            INNER JOIN inventario.tipoActivo tah ON hijo.idTipoActivo = tah.idTipoActivo
            WHERE hijo.idActivoPadre IN ($inIds)
              AND hijo.activo = 1
            ORDER BY hijo.idActivoPadre, tah.descripcion, hijo.codigoPatrimonial
        ";
        $stmtH = sqlsrv_query($conn, $sqlH);
        $mapaH = [];
        if ($stmtH !== false) {
            while ($row = sqlsrv_fetch_array($stmtH, SQLSRV_FETCH_ASSOC))
                $mapaH[intval($row['idActivoPadre'])][] = $row;
            sqlsrv_free_stmt($stmtH);
        }
        foreach ($padres as &$p) $p['hijos'] = $mapaH[intval($p['idActivo'])] ?? [];
        return $padres;
    }

    /* ════════════════════════════════════════════════════════
       RESUMEN GLOBAL por tipo (para gráfico)
    ════════════════════════════════════════════════════════ */
    static public function mdlResumenPorTipo(array $filtros = []): array
    {
        $conn = Conexion::conectar();
        $params = []; $whereExtra = ''; $inDnis = '';

        if (!empty($filtros['idTipoActivo'])) {
            $whereExtra .= " AND ta.idTipoActivo = ?";
            $params[]    = [intval($filtros['idTipoActivo']), SQLSRV_PARAM_IN];
        }
        if (!empty($filtros['dnis']) && is_array($filtros['dnis'])) {
            $safe   = array_map(fn($d) => "'" . str_replace("'", "''", $d) . "'", $filtros['dnis']);
            $inDnis = " AND a.dniTrabajadorResponsable IN (" . implode(',', $safe) . ")";
        }

        $sql = "
            SELECT ta.descripcion AS tipoActivo, ta.icono,
                   COUNT(DISTINCT act.idActivo) AS total
            FROM inventario.asignacion      a
            INNER JOIN inventario.estacion       est ON a.idEstacion     = est.idEstacion
            INNER JOIN inventario.estacionActivo ea  ON ea.idEstacion    = est.idEstacion
            INNER JOIN inventario.activo         act ON ea.idActivo      = act.idActivo
            INNER JOIN inventario.tipoActivo     ta  ON act.idTipoActivo = ta.idTipoActivo
            WHERE a.fechaLiberacion IS NULL
              AND est.activo = 1 AND act.activo = 1 AND ta.activo = 1
              AND act.idActivoPadre IS NULL
              $whereExtra $inDnis
            GROUP BY ta.descripcion, ta.icono
            ORDER BY total DESC
        ";

        $stmt = empty($params) ? sqlsrv_query($conn, $sql) : sqlsrv_query($conn, $sql, $params);
        $rows = [];
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $rows[] = $row;
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);
        return $rows;
    }
}

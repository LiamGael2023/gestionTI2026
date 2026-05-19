<?php
require_once __DIR__ . "/../../../config/db.php";

class EstacionModel
{
    /* ════════════════════════════════════════
       CREAR
    ════════════════════════════════════════ */
    static public function mdlCrearEstacion($datos)
    {
        $todosIds = self::buildEquiposIds($datos["principalId"], $datos["perifericosIds"], $datos["softwareIds"]);
        $conn = Conexion::conectar();
        $stmt = sqlsrv_query($conn,
            "{call inventario.sp_CrearEstacion(?, ?, ?, ?, ?, ?)}",
            [
                [$datos["nombreEstacion"],    SQLSRV_PARAM_IN],
                [$datos["idIp"],              SQLSRV_PARAM_IN],
                [$datos["codigoAnydesk"],     SQLSRV_PARAM_IN],
                [$datos["contrasenaAnydesk"], SQLSRV_PARAM_IN],
                [$todosIds,                   SQLSRV_PARAM_IN],
                [$datos["idUsuario"],         SQLSRV_PARAM_IN],
            ]
        );
        if ($stmt === false) { sqlsrv_close($conn); return ["resultado" => "error", "mensaje" => "Error al ejecutar SP."]; }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
        return $row ?? ["resultado" => "error", "mensaje" => "Sin respuesta."];
    }

    /* ════════════════════════════════════════
       EDITAR
    ════════════════════════════════════════ */
    static public function mdlEditarEstacion($datos)
    {
        $todosIds = self::buildEquiposIds($datos["principalId"], $datos["perifericosIds"], $datos["softwareIds"]);
        $conn = Conexion::conectar();
        $stmt = sqlsrv_query($conn,
            "{call inventario.sp_EditarEstacion(?, ?, ?, ?, ?, ?, ?)}",
            [
                [$datos["idEstacion"],        SQLSRV_PARAM_IN],
                [$datos["nombreEstacion"],    SQLSRV_PARAM_IN],
                [$datos["idIp"],              SQLSRV_PARAM_IN],
                [$datos["codigoAnydesk"],     SQLSRV_PARAM_IN],
                [$datos["contrasenaAnydesk"], SQLSRV_PARAM_IN],
                [$todosIds,                   SQLSRV_PARAM_IN],
                [$datos["idUsuario"],         SQLSRV_PARAM_IN],
            ]
        );
        if ($stmt === false) { sqlsrv_close($conn); return ["resultado" => "error", "mensaje" => "Error al ejecutar SP."]; }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
        return $row ?? ["resultado" => "error", "mensaje" => "Sin respuesta."];
    }

    /* ════════════════════════════════════════
       MOSTRAR ESTACION(ES) — solo activo = 1
    ════════════════════════════════════════ */
    static public function mdlMostrarEstacion($item, $valor)
    {
        $conn = Conexion::conectar();
        $sql  = "
            SELECT est.idEstacion, est.nombreEstacion, est.idIp,
                   ip.ipAddress, est.codigoAnydesk, est.contrasenaAnydesk,
                   est.idUsuarioRegistro, est.fechaCreacion,
                   est.idUsuarioModifica, est.fechaModificacion,
                   COUNT(ee.idEquipo) AS totalEquipos
            FROM inventario.estacion est
            LEFT JOIN inventario.ip             ip ON est.idIp       = ip.idIp
            LEFT JOIN inventario.estacionEquipo ee ON est.idEstacion = ee.idEstacion
            WHERE est.activo = 1
        ";
        $groupBy = "
            GROUP BY est.idEstacion, est.nombreEstacion, est.idIp, ip.ipAddress,
                     est.codigoAnydesk, est.contrasenaAnydesk,
                     est.idUsuarioRegistro, est.fechaCreacion,
                     est.idUsuarioModifica, est.fechaModificacion
        ";
        if ($item !== null) {
            $sql  .= " AND est.$item = ? " . $groupBy;
            $stmt  = sqlsrv_query($conn, $sql, [[$valor, SQLSRV_PARAM_IN]]);
            if ($stmt === false) { sqlsrv_close($conn); return null; }
            $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        } else {
            $sql  .= $groupBy . " ORDER BY est.nombreEstacion ASC";
            $stmt  = sqlsrv_query($conn, $sql);
            if ($stmt === false) { sqlsrv_close($conn); return []; }
            $resultado = [];
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $resultado[] = $row;
        }
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
        return $resultado;
    }

    /* ════════════════════════════════════════
       ELIMINAR ESTACION (lógico via SP)
    ════════════════════════════════════════ */
    static public function mdlEliminarEstacion($datos)
    {
        $conn = Conexion::conectar();
        $stmt = sqlsrv_query($conn,
            "{call inventario.sp_EliminarEstacion(?, ?)}",
            [
                [$datos["idEstacion"],        SQLSRV_PARAM_IN],
                [$datos["idUsuarioModifica"], SQLSRV_PARAM_IN],
            ]
        );
        if ($stmt === false) { sqlsrv_close($conn); return ["resultado" => "error", "mensaje" => "Error al ejecutar el SP."]; }
        $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
        return $resultado ?? ["resultado" => "error", "mensaje" => "Sin respuesta del SP."];
    }

    /* ════════════════════════════════════════
       EQUIPOS AGRUPADOS (para página editar)
    ════════════════════════════════════════ */
    static public function mdlEquiposDeEstacionAgrupados(int $idEstacion)
    {
        $conn = Conexion::conectar();
        $sql  = "
            SELECT e.idEquipo, e.numeroSerie, e.codigoPatrimonial,
                   a.descripcion AS nombreActivo, a.icono AS iconoActivo,
                   a.compuesto
            FROM inventario.estacionEquipo ee
            INNER JOIN inventario.equipo  e ON ee.idEquipo = e.idEquipo
            INNER JOIN inventario.activos a ON e.idActivo  = a.idActivos
            WHERE ee.idEstacion = ?
            ORDER BY a.descripcion ASC
        ";
        $stmt  = sqlsrv_query($conn, $sql, [[$idEstacion, SQLSRV_PARAM_IN]]);
        $grupos = ['principal' => [], 'perifericos' => [], 'software' => []];

        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $desc        = strtoupper(trim($row['nombreActivo'] ?? ''));
                $compuesto   = $row['compuesto'];
                $esCompuesto = ($compuesto === true || $compuesto === 1 || $compuesto === '1');

                $item = [
                    "idEquipo"          => intval($row["idEquipo"]),
                    "nombreActivo"      => $row["nombreActivo"]      ?? "",
                    "iconoActivo"       => $row["iconoActivo"]       ?? "ti-package",
                    "numeroSerie"       => $row["numeroSerie"]       ?? "",
                    "codigoPatrimonial" => $row["codigoPatrimonial"] ?? "",
                ];

                if ($desc === 'SOFTWARE') {
                    $grupos['software'][] = $item;
                } elseif ($esCompuesto) {
                    $grupos['principal'][] = $item;
                } else {
                    $grupos['perifericos'][] = $item;
                }
            }
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);
        return $grupos;
    }

    /* ════════════════════════════════════════
       VER DETALLE
    ════════════════════════════════════════ */
    static public function mdlVerDetalle(int $idEstacion)
    {
        $conn = Conexion::conectar();
        $sql  = "
            SELECT e.idEquipo, e.numeroSerie, e.codigoPatrimonial,
                   a.descripcion AS nombreActivo, a.icono AS iconoActivo,
                   a.compuesto
            FROM inventario.estacionEquipo ee
            INNER JOIN inventario.equipo  e ON ee.idEquipo = e.idEquipo
            INNER JOIN inventario.activos a ON e.idActivo  = a.idActivos
            WHERE ee.idEstacion = ?
            ORDER BY a.descripcion ASC
        ";
        $stmt        = sqlsrv_query($conn, $sql, [[$idEstacion, SQLSRV_PARAM_IN]]);
        $grupos      = ['principal' => [], 'perifericos' => [], 'software' => []];
        $idPrincipal = null;

        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $desc        = strtoupper(trim($row['nombreActivo'] ?? ''));
                $compuesto   = $row['compuesto'];
                $esCompuesto = ($compuesto === true || $compuesto === 1 || $compuesto === '1');
                $item = [
                    "idEquipo"          => intval($row["idEquipo"]),
                    "nombreActivo"      => $row["nombreActivo"]      ?? "",
                    "iconoActivo"       => $row["iconoActivo"]       ?? "ti-package",
                    "numeroSerie"       => $row["numeroSerie"]       ?? "",
                    "codigoPatrimonial" => $row["codigoPatrimonial"] ?? "",
                    "estado"            => "",
                ];
                if ($desc === 'SOFTWARE') {
                    $grupos['software'][] = $item;
                } elseif ($esCompuesto) {
                    $grupos['principal'][] = $item;
                    $idPrincipal = intval($row["idEquipo"]);
                } else {
                    $grupos['perifericos'][] = $item;
                }
            }
            sqlsrv_free_stmt($stmt);
        }

        $grupos['componentesPrincipal'] = [];
        if ($idPrincipal) {
            $sql2  = "
                SELECT e.idEquipo, e.numeroSerie, e.codigoPatrimonial,
                       a.descripcion AS nombreActivo, a.icono AS iconoActivo
                FROM inventario.equipo e
                INNER JOIN inventario.activos a ON e.idActivo = a.idActivos
                WHERE e.idEquipoPadre = ?
                ORDER BY a.descripcion ASC
            ";
            $stmt2 = sqlsrv_query($conn, $sql2, [[$idPrincipal, SQLSRV_PARAM_IN]]);
            if ($stmt2 !== false) {
                while ($row = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC)) {
                    $grupos['componentesPrincipal'][] = [
                        "idEquipo"          => intval($row["idEquipo"]),
                        "nombreActivo"      => $row["nombreActivo"]      ?? "",
                        "iconoActivo"       => $row["iconoActivo"]       ?? "ti-package",
                        "numeroSerie"       => $row["numeroSerie"]       ?? "",
                        "codigoPatrimonial" => $row["codigoPatrimonial"] ?? "",
                        "estado"            => "",
                    ];
                }
                sqlsrv_free_stmt($stmt2);
            }
        }

        sqlsrv_close($conn);
        return $grupos;
    }

    /* ════════════════════════════════════════
       LISTAR EQUIPOS POR TIPO
    ════════════════════════════════════════ */
    static public function mdlListarEquiposTipo(string $tipo, int $idEstacion, array $excluir = [])
    {
        $conn = Conexion::conectar();

        if ($tipo === 'software') {
            $condTipo = "UPPER(a.descripcion) = 'SOFTWARE'";
        } elseif ($tipo === 'principal') {
            $condTipo = "a.compuesto = 1 AND UPPER(a.descripcion) <> 'SOFTWARE'";
        } else {
            $condTipo = "a.compuesto = 0 AND UPPER(a.descripcion) <> 'SOFTWARE'";
        }

        $excluirFinal = array_filter(array_unique($excluir));
        $sql = "
            SELECT e.idEquipo, e.numeroSerie, e.codigoPatrimonial,
                   a.descripcion AS nombreActivo, a.icono AS iconoActivo
            FROM inventario.equipo e
            INNER JOIN inventario.activos a ON e.idActivo = a.idActivos
            WHERE $condTipo
              AND e.idEquipo NOT IN (
                  SELECT ee2.idEquipo FROM inventario.estacionEquipo ee2
                  WHERE ee2.idEstacion <> ?
              )
        ";
        $params = [[$idEstacion === 0 ? -1 : $idEstacion, SQLSRV_PARAM_IN]];

        if (!empty($excluirFinal)) {
            $ph   = implode(',', array_fill(0, count($excluirFinal), '?'));
            $sql .= " AND e.idEquipo NOT IN ($ph)";
            foreach ($excluirFinal as $id) $params[] = [$id, SQLSRV_PARAM_IN];
        }

        $sql .= $tipo === 'software'
            ? " ORDER BY a.descripcion ASC, e.idEquipo ASC"
            : " ORDER BY e.codigoPatrimonial ASC, a.descripcion ASC";

        $stmt = sqlsrv_query($conn, $sql, $params);
        $rows = [];
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) $rows[] = $row;
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);
        return $rows;
    }

    /* ════════════════════════════════════════
       LISTAR IPs DISPONIBLES
    ════════════════════════════════════════ */
    static public function mdlListarIps(int $idEstacion = 0)
    {
        $conn = Conexion::conectar();

        if ($idEstacion > 0) {
            $sql  = "
                SELECT ip.idIp, ip.ipAddress
                FROM inventario.ip ip
                WHERE ip.estado = 'disponible'
                   OR ip.idIp = (SELECT idIp FROM inventario.estacion WHERE idEstacion = ?)
                ORDER BY
                    CAST(PARSENAME(ip.ipAddress,4) AS INT),
                    CAST(PARSENAME(ip.ipAddress,3) AS INT),
                    CAST(PARSENAME(ip.ipAddress,2) AS INT),
                    CAST(PARSENAME(ip.ipAddress,1) AS INT)
            ";
            $stmt = sqlsrv_query($conn, $sql, [[$idEstacion, SQLSRV_PARAM_IN]]);
        } else {
            $sql  = "
                SELECT ip.idIp, ip.ipAddress
                FROM inventario.ip ip
                WHERE ip.estado = 'disponible'
                ORDER BY
                    CAST(PARSENAME(ip.ipAddress,4) AS INT),
                    CAST(PARSENAME(ip.ipAddress,3) AS INT),
                    CAST(PARSENAME(ip.ipAddress,2) AS INT),
                    CAST(PARSENAME(ip.ipAddress,1) AS INT)
            ";
            $stmt = sqlsrv_query($conn, $sql);
        }

        $rows = [];
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))
                $rows[] = ["idIp" => intval($row["idIp"]), "ipAddress" => $row["ipAddress"]];
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);
        return $rows;
    }

    /* ════════════════════════════════════════
       CREAR TERMINAL
    ════════════════════════════════════════ */
    static public function mdlCrearTerminal($datos)
    {
        $conn = Conexion::conectar();
        $stmt = sqlsrv_query($conn,
            "{call inventario.sp_CrearTerminal(?, ?, ?)}",
            [
                [$datos['nombreEstacion'], SQLSRV_PARAM_IN],
                [$datos['idEquipo'],       SQLSRV_PARAM_IN],
                [$datos['idUsuario'],      SQLSRV_PARAM_IN],
            ]
        );
        if ($stmt === false) { sqlsrv_close($conn); return ["resultado" => "error", "mensaje" => "Error SP."]; }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
        return $row ?? ["resultado" => "error", "mensaje" => "Sin respuesta."];
    }

    /* ════════════════════════════════════════
       EQUIPOS DISPONIBLES PARA TERMINAL
    ════════════════════════════════════════ */
    static public function mdlEquiposDisponibles()
    {
        $conn = Conexion::conectar();
        $sql  = "
            SELECT e.idEquipo,
                   a.descripcion AS nombreActivo,
                   e.codigoPatrimonial,
                   e.numeroSerie,
                   a.icono
            FROM inventario.equipo e
            INNER JOIN inventario.activos a ON e.idActivo = a.idActivos
            WHERE e.idEquipo NOT IN (
                SELECT idEquipo FROM inventario.estacionEquipo
            )
            ORDER BY a.descripcion ASC, e.codigoPatrimonial ASC
        ";
        $stmt = sqlsrv_query($conn, $sql);
        $rows = [];
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))
                $rows[] = [
                    'idEquipo'          => intval($row['idEquipo']),
                    'nombreActivo'      => $row['nombreActivo']      ?? '',
                    'codigoPatrimonial' => $row['codigoPatrimonial'] ?? '',
                    'numeroSerie'       => $row['numeroSerie']       ?? '',
                    'icono'             => $row['icono']             ?? 'ti-package',
                    'label'             => '[' . ($row['codigoPatrimonial'] ?? 'S/C') . '] ' . ($row['nombreActivo'] ?? ''),
                ];
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);
        return $rows;
    }

    /* ════════════════════════════════════════
       HELPER
    ════════════════════════════════════════ */
    private static function buildEquiposIds(string $principalId, string $perifericosIds, string $softwareIds): string
    {
        $partes = [];
        if ($principalId    !== '') $partes[] = $principalId;
        if ($perifericosIds !== '') $partes[] = $perifericosIds;
        if ($softwareIds    !== '') $partes[] = $softwareIds;
        $ids = array_filter(array_unique(array_map('intval', explode(',', implode(',', $partes)))));
        return implode(',', $ids);
    }
}

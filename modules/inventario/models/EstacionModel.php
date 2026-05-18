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

        $stmt = sqlsrv_query($conn, "{call inventario.sp_CrearEstacion(?, ?, ?, ?, ?, ?)}", [
            [$datos["nombreEstacion"],    SQLSRV_PARAM_IN],
            [$datos["codigoAnydesk"],     SQLSRV_PARAM_IN],
            [$datos["contrasenaAnydesk"], SQLSRV_PARAM_IN],
            [$datos["direccionFisica"],   SQLSRV_PARAM_IN],
            [$todosIds,                   SQLSRV_PARAM_IN],
            [$datos["idUsuario"],         SQLSRV_PARAM_IN]
        ]);

        if ($stmt === false) {
            sqlsrv_close($conn);
            return ["resultado" => "error", "mensaje" => "Error al ejecutar SP."];
        }

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        // CRÍTICO: Limpiar resultados pendientes del SP para poder usar la conexión de nuevo
        while (sqlsrv_next_result($stmt)) {
        }
        sqlsrv_free_stmt($stmt);

        if (($row['resultado'] ?? '') === 'ok' && !empty($datos['ipsIds'])) {
            $idNuevo = intval($row['idEstacion']);
            // Usamos array_unique para evitar procesar la misma IP dos veces
            $arrIps = array_unique(array_filter(array_map('intval', explode(',', $datos['ipsIds']))));

            if (count($arrIps) > 0) {
                $in = implode(',', $arrIps);
                $sqlUpd = "UPDATE inventario.ip SET idEstacion = ?, estado = 'asignada' WHERE idIp IN ($in)";
                sqlsrv_query($conn, $sqlUpd, [$idNuevo]);
            }
        }

        sqlsrv_close($conn);
        return $row;
    }

    /* ════════════════════════════════════════
       EDITAR
    ════════════════════════════════════════ */
    static public function mdlEditarEstacion($datos)
    {
        $todosIds = self::buildEquiposIds($datos["principalId"], $datos["perifericosIds"], $datos["softwareIds"]);
        $conn = Conexion::conectar();

        $stmt = sqlsrv_query($conn, "{call inventario.sp_EditarEstacion(?, ?, ?, ?, ?, ?, ?)}", [
            [$datos["idEstacion"],        SQLSRV_PARAM_IN],
            [$datos["nombreEstacion"],    SQLSRV_PARAM_IN],
            [$datos["codigoAnydesk"],     SQLSRV_PARAM_IN],
            [$datos["contrasenaAnydesk"], SQLSRV_PARAM_IN],
            [$datos["direccionFisica"],   SQLSRV_PARAM_IN],
            [$todosIds,                   SQLSRV_PARAM_IN],
            [$datos["idUsuario"],         SQLSRV_PARAM_IN]
        ]);

        if ($stmt === false) {
            sqlsrv_close($conn);
            return ["resultado" => "error", "mensaje" => "Error SP."];
        }

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        // Limpiar conexión
        while (sqlsrv_next_result($stmt)) {
        }
        sqlsrv_free_stmt($stmt);

        if (($row['resultado'] ?? '') === 'ok') {
            $idEst = intval($datos['idEstacion']);

            // 1. Liberar IPs anteriores
            $sqlLib = "UPDATE inventario.ip SET idEstacion = NULL, estado = 'Disponible' WHERE idEstacion = ?";
            sqlsrv_query($conn, $sqlLib, [$idEst]);

            // 2. Asignar nuevas (evitando duplicados con array_unique)
            if (!empty($datos['ipsIds'])) {
                $arrIps = array_unique(array_filter(array_map('intval', explode(',', $datos['ipsIds']))));
                if (count($arrIps) > 0) {
                    $in = implode(',', $arrIps);
                    $sqlAsig = "UPDATE inventario.ip SET idEstacion = ?, estado = 'asignada' WHERE idIp IN ($in)";
                    sqlsrv_query($conn, $sqlAsig, [$idEst]);
                }
            }
        }

        sqlsrv_close($conn);
        return $row;
    }

    /* ════════════════════════════════════════
       SINCRONIZAR IPs (helper interno)
       Una estación puede tener varias IPs,
       pero una IP solo pertenece a una estación.
    ════════════════════════════════════════ */
    private static function _sincronizarIps($conn, int $idEstacion, string $ipsIds, $idUsuario): array
    {
        $log = ['idEstacion' => $idEstacion, 'ipsIds_recibido' => $ipsIds, 'pasos' => []];

        // Validar y limpiar IDs — enteros positivos únicos
        $raw       = trim($ipsIds) !== '' ? explode(',', $ipsIds) : [];
        $nuevosIds = array_values(array_filter(array_unique(array_map('intval', $raw)), fn($id) => $id > 0));
        $log['nuevosIds_parseados'] = $nuevosIds;

        // 1. Desasignar IPs que ya no están en la nueva lista
        if (!empty($nuevosIds)) {
            // IDs son enteros validados → interpolación directa, sin riesgo SQL injection
            $inList = implode(',', $nuevosIds);
            $sql    = "UPDATE inventario.ip
                       SET idEstacion = NULL, estado = 'disponible',
                           idUsuarioModifica = ?, fechaModificacion = GETDATE()
                       WHERE idEstacion = ? AND idIp NOT IN ($inList)";
            $stmtD  = sqlsrv_query($conn, $sql, [
                [$idUsuario,  SQLSRV_PARAM_IN],
                [$idEstacion, SQLSRV_PARAM_IN],
            ]);
            if ($stmtD === false) {
                $log['pasos'][] = ['desasignar_NOT_IN' => 'ERROR', 'sql_error' => sqlsrv_errors()];
            } else {
                $log['pasos'][] = ['desasignar_NOT_IN' => 'OK', 'filas' => sqlsrv_rows_affected($stmtD)];
                sqlsrv_free_stmt($stmtD);
            }
        } else {
            // Sin IPs nuevas → desasignar todas las de esta estación
            $stmtD = sqlsrv_query(
                $conn,
                "UPDATE inventario.ip
                 SET idEstacion = NULL, estado = 'disponible',
                     idUsuarioModifica = ?, fechaModificacion = GETDATE()
                 WHERE idEstacion = ?",
                [[$idUsuario, SQLSRV_PARAM_IN], [$idEstacion, SQLSRV_PARAM_IN]]
            );
            if ($stmtD === false) {
                $log['pasos'][] = ['desasignar_TODOS' => 'ERROR', 'sql_error' => sqlsrv_errors()];
            } else {
                $log['pasos'][] = ['desasignar_TODOS' => 'OK', 'filas' => sqlsrv_rows_affected($stmtD)];
                sqlsrv_free_stmt($stmtD);
            }
        }

        // 2. Asignar cada IP nueva
        foreach ($nuevosIds as $idIp) {
            $sql   = "UPDATE inventario.ip
                      SET idEstacion = ?, estado = 'asignado',
                          idUsuarioModifica = ?, fechaModificacion = GETDATE()
                      WHERE idIp = ? AND (estado = 'disponible' OR idEstacion = ?)";
            $stmtA = sqlsrv_query($conn, $sql, [
                [$idEstacion, SQLSRV_PARAM_IN],
                [$idUsuario,  SQLSRV_PARAM_IN],
                [$idIp,       SQLSRV_PARAM_IN],
                [$idEstacion, SQLSRV_PARAM_IN],
            ]);
            if ($stmtA === false) {
                $log['pasos'][] = ['asignar_ip_' . $idIp => 'ERROR', 'sql_error' => sqlsrv_errors()];
            } else {
                $log['pasos'][] = ['asignar_ip_' . $idIp => 'OK', 'filas' => sqlsrv_rows_affected($stmtA)];
                sqlsrv_free_stmt($stmtA);
            }
        }

        return $log;
    }

    /* ════════════════════════════════════════
       MOSTRAR ESTACION(ES) — solo activo = 1
    ════════════════════════════════════════ */
    static public function mdlMostrarEstacion($item, $valor)
    {
        $conn = Conexion::conectar();
        $sql = "
    SELECT est.idEstacion, est.nombreEstacion,
           est.codigoAnydesk, est.contrasenaAnydesk,
           est.direccionFisica,
           est.idUsuarioRegistro, est.fechaCreacion,
           est.idUsuarioModifica, est.fechaModificacion,
           COUNT(DISTINCT ea.idActivo) AS totalEquipos,
           MAX(CASE WHEN ta.esCompuesto = 1
               THEN act.codigoPatrimonial ELSE NULL END) AS codigoPatrimonialPrincipal,
           MAX(CASE WHEN ta.esCompuesto = 1
               THEN ta.descripcion ELSE NULL END) AS nombreTipoPrincipal,
           (SELECT STRING_AGG(CAST(ip2.idIp AS VARCHAR), ',')
            FROM inventario.ip ip2
            WHERE ip2.idEstacion = est.idEstacion) AS ipsIds,
           (SELECT STRING_AGG(ip2.ipAddress, ', ')
            FROM inventario.ip ip2
            WHERE ip2.idEstacion = est.idEstacion) AS ipsTexto,
            LTRIM(RTRIM(ISNULL(u.nombres, '') + ' ' + ISNULL(u.apellidos, ''))) as nombreUsuario
    FROM inventario.estacion est
    LEFT JOIN inventario.estacionActivo ea  ON est.idEstacion = ea.idEstacion
    LEFT JOIN inventario.activo         act ON ea.idActivo    = act.idActivo
    LEFT JOIN inventario.tipoActivo     ta  ON act.idTipoActivo = ta.idTipoActivo
    LEFT JOIN comun.Usuarios u ON u.id_usuario = est.idUsuarioRegistro
    WHERE est.activo = 1
";
        $groupBy = "
            GROUP BY est.idEstacion, est.nombreEstacion,
                     est.codigoAnydesk, est.contrasenaAnydesk, est.direccionFisica,
                     est.idUsuarioRegistro, est.fechaCreacion,
                     est.idUsuarioModifica, est.fechaModificacion, u.nombres, u.apellidos
        ";
        if ($item !== null) {
            $sql  .= " AND est.$item = ? " . $groupBy;
            $stmt  = sqlsrv_query($conn, $sql, [[$valor, SQLSRV_PARAM_IN]]);
            if ($stmt === false) {
                sqlsrv_close($conn);
                return null;
            }
            $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        } else {
            $sql  .= $groupBy . " ORDER BY est.nombreEstacion ASC";
            $stmt  = sqlsrv_query($conn, $sql);
            if ($stmt === false) {
                sqlsrv_close($conn);
                return [];
            }
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

        // Desasignar IPs antes de eliminar la estación
        $stmtIp = sqlsrv_query(
            $conn,
            "UPDATE inventario.ip
             SET idEstacion = NULL, estado = 'disponible',
                 idUsuarioModifica = ?, fechaModificacion = GETDATE()
             WHERE idEstacion = ?",
            [[$datos["idUsuarioModifica"], SQLSRV_PARAM_IN], [$datos["idEstacion"], SQLSRV_PARAM_IN]]
        );
        if ($stmtIp !== false) sqlsrv_free_stmt($stmtIp);

        $stmt = sqlsrv_query(
            $conn,
            "{call inventario.sp_EliminarEstacion(?, ?)}",
            [
                [$datos["idEstacion"],        SQLSRV_PARAM_IN],
                [$datos["idUsuarioModifica"], SQLSRV_PARAM_IN],
            ]
        );
        if ($stmt === false) {
            sqlsrv_close($conn);
            return ["resultado" => "error", "mensaje" => "Error al ejecutar el SP."];
        }
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
            SELECT a.idActivo, a.codigoPatrimonial, a.numeroSerie,
                   ta.descripcion AS nombreActivo, ta.icono AS iconoActivo,
                   ta.esCompuesto, ta.esPeriferico, ta.esComponente
            FROM inventario.estacionActivo ea
            INNER JOIN inventario.activo     a  ON ea.idActivo    = a.idActivo
            INNER JOIN inventario.tipoActivo ta ON a.idTipoActivo = ta.idTipoActivo
            WHERE ea.idEstacion = ?
            ORDER BY ta.descripcion ASC
        ";
        $stmt   = sqlsrv_query($conn, $sql, [[$idEstacion, SQLSRV_PARAM_IN]]);
        $grupos = ['principal' => [], 'perifericos' => [], 'software' => []];

        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $esCompuesto  = intval($row['esCompuesto']  ?? 0);
                $esPeriferico = intval($row['esPeriferico'] ?? 0);

                $item = [
                    "idActivo"          => intval($row["idActivo"]),
                    "nombreActivo"      => $row["nombreActivo"]      ?? "",
                    "iconoActivo"       => $row["iconoActivo"]       ?? "ti-package",
                    "numeroSerie"       => $row["numeroSerie"]       ?? "",
                    "codigoPatrimonial" => $row["codigoPatrimonial"] ?? "",
                ];

                if ($esCompuesto) {
                    $grupos['principal'][] = $item;
                } elseif ($esPeriferico) {
                    $grupos['perifericos'][] = $item;
                } else {
                    $grupos['software'][] = $item;
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
            SELECT a.idActivo, a.codigoPatrimonial, a.numeroSerie,
                   ta.descripcion AS nombreActivo, ta.icono AS iconoActivo,
                   ta.esCompuesto, ta.esPeriferico, ta.esComponente
            FROM inventario.estacionActivo ea
            INNER JOIN inventario.activo     a  ON ea.idActivo    = a.idActivo
            INNER JOIN inventario.tipoActivo ta ON a.idTipoActivo = ta.idTipoActivo
            WHERE ea.idEstacion = ?
            ORDER BY ta.descripcion ASC
        ";
        $stmt        = sqlsrv_query($conn, $sql, [[$idEstacion, SQLSRV_PARAM_IN]]);
        $grupos      = ['principal' => [], 'perifericos' => [], 'software' => [], 'componentesPrincipal' => [], 'ips' => []];
        $idPrincipal = null;

        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $esCompuesto  = intval($row['esCompuesto']  ?? 0);
                $esPeriferico = intval($row['esPeriferico'] ?? 0);

                $item = [
                    "idActivo"          => intval($row["idActivo"]),
                    "nombreActivo"      => $row["nombreActivo"]      ?? "",
                    "iconoActivo"       => $row["iconoActivo"]       ?? "ti-package",
                    "numeroSerie"       => $row["numeroSerie"]       ?? "",
                    "codigoPatrimonial" => $row["codigoPatrimonial"] ?? "",
                    "estado"            => "",
                ];

                if ($esCompuesto) {
                    $grupos['principal'][] = $item;
                    $idPrincipal = intval($row["idActivo"]);
                } elseif ($esPeriferico) {
                    $grupos['perifericos'][] = $item;
                } else {
                    $grupos['software'][] = $item;
                }
            }
            sqlsrv_free_stmt($stmt);
        }

        // Componentes internos del activo principal (vía idActivoPadre)
        if ($idPrincipal) {
            $sql2  = "
                SELECT a.idActivo, a.codigoPatrimonial, a.numeroSerie,
                       ta.descripcion AS nombreActivo, ta.icono AS iconoActivo
                FROM inventario.activo a
                INNER JOIN inventario.tipoActivo ta ON a.idTipoActivo = ta.idTipoActivo
                WHERE a.idActivoPadre = ? AND a.activo = 1
                ORDER BY ta.descripcion ASC
            ";
            $stmt2 = sqlsrv_query($conn, $sql2, [[$idPrincipal, SQLSRV_PARAM_IN]]);
            if ($stmt2 !== false) {
                while ($row = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC)) {
                    $grupos['componentesPrincipal'][] = [
                        "idActivo"          => intval($row["idActivo"]),
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

        // IPs de la estación
        $sqlIp = "SELECT idIp, ipAddress FROM inventario.ip WHERE idEstacion = ? ORDER BY
                      CAST(PARSENAME(ipAddress,4) AS INT), CAST(PARSENAME(ipAddress,3) AS INT),
                      CAST(PARSENAME(ipAddress,2) AS INT), CAST(PARSENAME(ipAddress,1) AS INT)";
        $stmtIp = sqlsrv_query($conn, $sqlIp, [[$idEstacion, SQLSRV_PARAM_IN]]);
        if ($stmtIp !== false) {
            while ($row = sqlsrv_fetch_array($stmtIp, SQLSRV_FETCH_ASSOC)) {
                $grupos['ips'][] = ["idIp" => intval($row["idIp"]), "ipAddress" => $row["ipAddress"]];
            }
            sqlsrv_free_stmt($stmtIp);
        }

        sqlsrv_close($conn);
        return $grupos;
    }

    /* ════════════════════════════════════════
       LISTAR EQUIPOS POR TIPO
    ════════════════════════════════════════ */
    static public function mdlListarEquiposTipo($tipo, $idEstacion, $excluir = [])
    {
        $conn = Conexion::conectar();

        $filtroTipo = "";
        if ($tipo === 'principal') {
            $filtroTipo = "AND ta.esCompuesto = 1 AND ta.esPeriferico = 0 AND ta.esComponente = 0";
        } elseif ($tipo === 'periferico') {
            $filtroTipo = "AND ta.esPeriferico = 1";
        } elseif ($tipo === 'ups_estabilizador') {
            $filtroTipo = "AND ta.esPeriferico = 1 AND (UPPER(ta.descripcion) LIKE '%UPS%' OR UPPER(ta.descripcion) LIKE '%ESTABILIZADOR%')";
        } elseif ($tipo === 'software') {
            $filtroTipo = "AND ta.esCompuesto = 0 AND ta.esPeriferico = 0 AND ta.esComponente = 0";
        }

        $sql = "SELECT
                a.idActivo,
                a.codigoPatrimonial,
                a.numeroSerie,
                ta.descripcion AS nombreActivo,
                ta.icono       AS iconoActivo
            FROM inventario.activo a
            INNER JOIN inventario.tipoActivo ta ON a.idTipoActivo = ta.idTipoActivo
            WHERE a.activo = 1
            $filtroTipo
            AND (
                -- disponible y NO asignado a ninguna estación
                (a.estado = 'disponible' AND a.idActivo NOT IN (
                    SELECT idActivo FROM inventario.estacionActivo
                ))
                OR
                -- ya pertenece a esta estación (editar)
                a.idActivo IN (
                    SELECT idActivo FROM inventario.estacionActivo WHERE idEstacion = ?
                )
            )";

        if (!empty($excluir)) {
            $idsExcluir = implode(',', array_map('intval', $excluir));
            $sql .= " AND a.idActivo NOT IN ($idsExcluir)";
        }

        $sql .= " ORDER BY ta.descripcion ASC, a.codigoPatrimonial ASC";

        $stmt = sqlsrv_query($conn, $sql, [[$idEstacion, SQLSRV_PARAM_IN]]);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            sqlsrv_close($conn);
            return ["error" => $errors];
        }

        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }

        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
        return $result;
    }

    /* ════════════════════════════════════════
       LISTAR IPs DISPONIBLES
       Retorna IPs libres + las ya asignadas a $idEstacion
    ════════════════════════════════════════ */
    static public function mdlListarIps(int $idEstacion = 0)
    {
        $conn = Conexion::conectar();

        $orderBy = "ORDER BY
            CAST(PARSENAME(ip.ipAddress,4) AS INT),
            CAST(PARSENAME(ip.ipAddress,3) AS INT),
            CAST(PARSENAME(ip.ipAddress,2) AS INT),
            CAST(PARSENAME(ip.ipAddress,1) AS INT)";

        if ($idEstacion > 0) {
            // Editar: mostrar IPs disponibles + las que ya pertenecen a ESTA estación
            $sql  = "SELECT ip.idIp, ip.ipAddress
                     FROM inventario.ip ip
                     WHERE (ip.estado = 'disponible' AND ip.idEstacion IS NULL)
                        OR ip.idEstacion = ?
                     $orderBy";
            $stmt = sqlsrv_query($conn, $sql, [[$idEstacion, SQLSRV_PARAM_IN]]);
        } else {
            // Crear: solo IPs realmente libres (disponibles y sin estación asignada)
            $sql  = "SELECT ip.idIp, ip.ipAddress
                     FROM inventario.ip ip
                     WHERE ip.estado = 'disponible'
                       AND ip.idEstacion IS NULL
                     $orderBy";
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
       IPs ACTUALES DE UNA ESTACIÓN
    ════════════════════════════════════════ */
    static public function mdlIpsDeEstacion($idEstacion)
    {
        $conn = Conexion::conectar();
        // Agregamos DISTINCT para evitar repeticiones por los JOINs
        $sql = "SELECT DISTINCT i.idIp, i.ipAddress, i.estado 
        FROM inventario.ip i 
        WHERE i.idEstacion = ?";
        $stmt = sqlsrv_query($conn, $sql, [$idEstacion]);
        $ips = [];
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $ips[] = $row;
            }
        }
        sqlsrv_close($conn);
        return $ips;
    }

    /* ════════════════════════════════════════
       CREAR TERMINAL
    ════════════════════════════════════════ */
    static public function mdlCrearTerminal($datos)
    {
        $conn = Conexion::conectar();
        $stmt = sqlsrv_query(
            $conn,
            "{call inventario.sp_CrearTerminal(?, ?, ?)}",
            [
                [$datos['nombreEstacion'], SQLSRV_PARAM_IN],
                [$datos['idActivo'],       SQLSRV_PARAM_IN],
                [$datos['idUsuario'],      SQLSRV_PARAM_IN],
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

    /* ════════════════════════════════════════
       EQUIPOS DISPONIBLES PARA TERMINAL
    ════════════════════════════════════════ */
    static public function mdlEquiposDisponibles()
    {
        $conn = Conexion::conectar();
        $sql  = "
            SELECT a.idActivo,
                   ta.descripcion AS nombreActivo,
                   a.codigoPatrimonial,
                   a.numeroSerie,
                   ta.icono
            FROM inventario.activo a
            INNER JOIN inventario.tipoActivo ta ON a.idTipoActivo = ta.idTipoActivo
            WHERE a.activo = 1
              AND a.estado = 'disponible'
              AND a.idActivo NOT IN (
                  SELECT idActivo FROM inventario.estacionActivo
              )
            ORDER BY ta.descripcion ASC, a.codigoPatrimonial ASC
        ";
        $stmt = sqlsrv_query($conn, $sql);
        $rows = [];
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))
                $rows[] = [
                    'idActivo'          => intval($row['idActivo']),
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
       HELPER — construir cadena de IDs de activos
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

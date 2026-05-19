<?php
require_once __DIR__ . "/../../../config/db.php";

class IpModel
{
    /* ────────────────────────────────────────────
       SERVER-SIDE PROCESSING para DataTables
       Devuelve: [ 'rows'=>[], 'total'=>N, 'filtered'=>N ]
    ──────────────────────────────────────────── */
    static public function mdlServerSide(
        int    $start,
        int    $length,
        string $search,
        string $orderField,
        string $orderDir
    ) {
        $conn = Conexion::conectar();

        // Campos permitidos para ORDER BY (whitelist — nunca confiar en input del usuario)
        $whitelist = ['i.ipAddress','i.estado','i.fechaCreacion','u.descripcion','i.idIp'];
        if (!in_array($orderField, $whitelist)) $orderField = 'i.idIp';
        if (!in_array($orderDir, ['ASC','DESC'])) $orderDir = 'ASC';

        $baseFrom = "
            FROM inventario.ip i
            INNER JOIN inventario.ubicacion u ON i.idUbicacion = u.idUbicacion
        ";

        // Total sin filtro
        $sqlTotal = "SELECT COUNT(*) AS total " . $baseFrom;
        $stmtTotal = sqlsrv_query($conn, $sqlTotal);
        $total = 0;
        if ($stmtTotal !== false) {
            $rowT  = sqlsrv_fetch_array($stmtTotal, SQLSRV_FETCH_ASSOC);
            $total = intval($rowT['total'] ?? 0);
            sqlsrv_free_stmt($stmtTotal);
        }

        // WHERE para búsqueda
        $whereClause = "";
        $params      = [];
        if ($search !== '') {
            $whereClause = "
                WHERE (
                    i.ipAddress        LIKE ?
                 OR i.cidrOrigen       LIKE ?
                 OR u.descripcion      LIKE ?
                 OR i.estado           LIKE ?
                 OR CONVERT(VARCHAR, i.fechaCreacion, 103) LIKE ?
                )
            ";
            $like = '%' . $search . '%';
            $params = [
                [$like, SQLSRV_PARAM_IN],
                [$like, SQLSRV_PARAM_IN],
                [$like, SQLSRV_PARAM_IN],
                [$like, SQLSRV_PARAM_IN],
                [$like, SQLSRV_PARAM_IN],
            ];
        }

        // Total filtrado
        $sqlFiltered  = "SELECT COUNT(*) AS total " . $baseFrom . $whereClause;
        $stmtFiltered = empty($params)
            ? sqlsrv_query($conn, $sqlFiltered)
            : sqlsrv_query($conn, $sqlFiltered, $params);
        $filtered = 0;
        if ($stmtFiltered !== false) {
            $rowF     = sqlsrv_fetch_array($stmtFiltered, SQLSRV_FETCH_ASSOC);
            $filtered = intval($rowF['total'] ?? 0);
            sqlsrv_free_stmt($stmtFiltered);
        }

        // Filas paginadas — orden numérico correcto para IPs cuando se ordena por ipAddress
        $orderExpr = ($orderField === 'i.ipAddress')
            ? "CAST(PARSENAME(i.ipAddress,4) AS INT) $orderDir,
               CAST(PARSENAME(i.ipAddress,3) AS INT) $orderDir,
               CAST(PARSENAME(i.ipAddress,2) AS INT) $orderDir,
               CAST(PARSENAME(i.ipAddress,1) AS INT) $orderDir"
            : "$orderField $orderDir";

        $sqlRows = "
            SELECT i.idIp,
                   i.ipAddress,
                   i.mascara,
                   i.cidrOrigen,
                   i.idUbicacion,
                   u.descripcion AS descripcionUbicacion,
                   i.estado,
                   i.idUsuarioRegistro,
                   i.fechaCreacion,
                   i.idUsuarioModifica,
                   i.fechaModificacion
            $baseFrom
            $whereClause
            ORDER BY $orderExpr
            OFFSET ? ROWS FETCH NEXT ? ROWS ONLY
        ";

        $rowParams = array_merge($params, [
            [$start,  SQLSRV_PARAM_IN],
            [$length, SQLSRV_PARAM_IN],
        ]);

        $stmtRows = sqlsrv_query($conn, $sqlRows, $rowParams);
        $rows = [];
        if ($stmtRows !== false) {
            while ($row = sqlsrv_fetch_array($stmtRows, SQLSRV_FETCH_ASSOC)) {
                $rows[] = $row;
            }
            sqlsrv_free_stmt($stmtRows);
        }

        sqlsrv_close($conn);

        return [
            'rows'     => $rows,
            'total'    => $total,
            'filtered' => $filtered,
        ];
    }

    /* ────────────────────────────────────────────
       SP genérico
    ──────────────────────────────────────────── */
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

    /* ────────────────────────────────────────────
       CREAR IP INDIVIDUAL
    ──────────────────────────────────────────── */
    static public function mdlCrearIp($datos)
    {
        return self::ejecutarSP(
            "{call inventario.sp_CrearIp(?, ?, ?, ?, ?, ?)}",
            [
                [$datos["ipAddress"],   SQLSRV_PARAM_IN],
                [$datos["mascara"],     SQLSRV_PARAM_IN],
                [$datos["cidrOrigen"],  SQLSRV_PARAM_IN],
                [$datos["idUbicacion"], SQLSRV_PARAM_IN],
                [$datos["estado"],      SQLSRV_PARAM_IN],
                [$datos["idUsuario"],   SQLSRV_PARAM_IN],
            ]
        );
    }

    /* ────────────────────────────────────────────
       CREAR RANGO CIDR
    ──────────────────────────────────────────── */
    static public function mdlCrearRangoCidr(
        array  $hosts,
        string $mascara,
        string $cidrOrigen,
        int    $idUbicacion,
        string $estado,
        int    $idUsuario
    ) {
        if (empty($hosts)) {
            return ["resultado" => "error", "mensaje" => "No hay IPs para registrar."];
        }

        $conn = Conexion::conectar();
        $insertadas = 0;
        $omitidas   = 0;

        foreach ($hosts as $ip) {
            if (in_array($ip, ['0.0.0.0', '127.0.0.1', '255.255.255.255'])) { $omitidas++; continue; }

            $stmt = sqlsrv_query($conn, "{call inventario.sp_CrearIp(?, ?, ?, ?, ?, ?)}", [
                [$ip,          SQLSRV_PARAM_IN],
                [$mascara,     SQLSRV_PARAM_IN],
                [$cidrOrigen,  SQLSRV_PARAM_IN],
                [$idUbicacion, SQLSRV_PARAM_IN],
                [$estado,      SQLSRV_PARAM_IN],
                [$idUsuario,   SQLSRV_PARAM_IN],
            ]);

            if ($stmt !== false) {
                $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                if (($row["resultado"] ?? '') === 'ok') $insertadas++; else $omitidas++;
                sqlsrv_free_stmt($stmt);
            } else {
                $omitidas++;
            }
        }

        sqlsrv_close($conn);

        if ($insertadas === 0) {
            return ["resultado" => "error", "mensaje" => "No se insertó ninguna IP. Todas ya estaban registradas."];
        }

        $msg = "Se registraron {$insertadas} dirección(es) IP correctamente.";
        if ($omitidas > 0) $msg .= " {$omitidas} fueron omitidas (duplicadas o reservadas).";
        return ["resultado" => "ok", "mensaje" => $msg];
    }

    /* ────────────────────────────────────────────
       VERIFICAR DUPLICADOS (preview CIDR)
    ──────────────────────────────────────────── */
    static public function mdlVerificarDuplicadosCidr(array $hosts)
    {
        if (empty($hosts)) return [];
        $conn         = Conexion::conectar();
        $placeholders = implode(',', array_fill(0, count($hosts), '?'));
        $params       = array_map(fn($ip) => [$ip, SQLSRV_PARAM_IN], $hosts);
        $stmt         = sqlsrv_query($conn, "SELECT ipAddress FROM inventario.ip WHERE ipAddress IN ($placeholders)", $params);
        $result       = [];
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $result[] = $row['ipAddress'];
            }
            sqlsrv_free_stmt($stmt);
        }
        sqlsrv_close($conn);
        return $result;
    }

    /* ────────────────────────────────────────────
       EDITAR
    ──────────────────────────────────────────── */
    static public function mdlEditarIp($datos)
    {
        return self::ejecutarSP(
            "{call inventario.sp_EditarIp(?, ?, ?, ?, ?, ?)}",
            [
                [$datos["idIp"],        SQLSRV_PARAM_IN],
                [$datos["ipAddress"],   SQLSRV_PARAM_IN],
                [$datos["mascara"],     SQLSRV_PARAM_IN],
                [$datos["idUbicacion"], SQLSRV_PARAM_IN],
                [$datos["estado"],      SQLSRV_PARAM_IN],
                [$datos["idUsuario"],   SQLSRV_PARAM_IN],
            ]
        );
    }

    /* ────────────────────────────────────────────
       MOSTRAR UNO (para modal editar)
    ──────────────────────────────────────────── */
    static public function mdlMostrarIp($item, $valor)
    {
        $conn = Conexion::conectar();
        $sql  = "
            SELECT i.idIp, i.ipAddress, i.mascara, i.cidrOrigen,
                   i.idUbicacion, u.descripcion AS descripcionUbicacion,
                   i.estado, i.idUsuarioRegistro, i.fechaCreacion,
                   i.idUsuarioModifica, i.fechaModificacion
            FROM inventario.ip i
            INNER JOIN inventario.ubicacion u ON i.idUbicacion = u.idUbicacion
            WHERE i.$item = ?
        ";
        $stmt = sqlsrv_query($conn, $sql, [[$valor, SQLSRV_PARAM_IN]]);
        if ($stmt === false) { sqlsrv_close($conn); return null; }
        $resultado = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);
        return $resultado ?: null;
    }
}

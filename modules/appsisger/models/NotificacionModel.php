<?php
class NotificacionModel{

public static function obtenerPendientes(){ 

    $conn = Conexion::conectar();

    $sql = "SELECT 
               n.*,
                d.token
            FROM BDSISGERWEB.Aplicativo.NotificacionesPendientes n
            INNER JOIN BDSISGERWEB.Aplicativo.vw_ConductoresPorAmbito d
                ON d.AmbOpe_CodigoCatastral = n.AmbOpe_CodigoCatastral
            WHERE n.Estado = 0";

    $stmt = sqlsrv_query($conn, $sql);


    $data = [];

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $data[] = $row;
    }

    return $data;
}

public static function obtenerPendientesConTokens($codigoUnico){ 

    $conn = Conexion::conectar();

    $sql = "SELECT 
               n.*,
               d.token
            FROM BDSISGERWEB.Aplicativo.NotificacionesPendientes n
            INNER JOIN BDSISGERWEB.Aplicativo.vw_ConductoresPorAmbito d
                ON d.AmbOpe_CodigoCatastral = n.AmbOpe_CodigoCatastral
            WHERE n.Estado = 0
            AND d.CodigoUnico = ?";

    $stmt = sqlsrv_query($conn, $sql, [$codigoUnico]);
    if ($stmt === false) {
    echo json_encode(['error' => sqlsrv_errors()]);
    exit;
}

    $data = [];

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $data[] = $row;
    }

    return $data;
}


    public static function marcarEnviado($id){

        $conn = Conexion::conectar();

        $sql = "UPDATE Aplicativo.NotificacionesPendientes SET Estado = 1 WHERE Id = ?";

        sqlsrv_query($conn, $sql, [$id]);
    }

    public static function obtenerTodos($codigoUnico){ 

    $conn = Conexion::conectar();

    $sql = "SELECT 
               n.Id,
               n.AmbOpe_CodigoCatastral,
               n.Estado,
               n.FechaRegistro,
               d.token
            FROM BDSISGERWEB.Aplicativo.NotificacionesPendientes n
            INNER JOIN BDSISGERWEB.Aplicativo.vw_ConductoresPorAmbito d
                ON d.AmbOpe_CodigoCatastral = n.AmbOpe_CodigoCatastral
            WHERE d.CodigoUnico = ?
            ORDER BY n.FechaRegistro DESC";

    $stmt = sqlsrv_query($conn, $sql, [$codigoUnico]);

    if ($stmt === false) {
        error_log("Error query: " . print_r(sqlsrv_errors(), true));
        return [];
    }

    $data = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $data[] = $row;
    }

    return $data;
}
public static function obtenerHistorial($codigoUnico) {
    $conn = Conexion::conectar();

    $sql = "SELECT DISTINCT
                n.Id,
                RTRIM(LTRIM(n.AmbOpe_CodigoCatastral)) as AmbOpe_CodigoCatastral,
                n.Estado,
                CAST(n.FechaRegistro AS DATE) as FechaRegistro
            FROM BDSISGERWEB.Aplicativo.NotificacionesPendientes n
            INNER JOIN BDSISGERWEB.Aplicativo.vw_ConductoresPorAmbito d
                ON RTRIM(LTRIM(d.AmbOpe_CodigoCatastral)) = RTRIM(LTRIM(n.AmbOpe_CodigoCatastral))
            WHERE d.CodigoUnico = ?
            AND n.Estado = 1
            ORDER BY CAST(n.FechaRegistro AS DATE) DESC";

    $stmt = sqlsrv_query($conn, $sql, [$codigoUnico]);

    if ($stmt === false) {
        error_log("Error obtenerHistorial: " . print_r(sqlsrv_errors(), true));
        return [];
    }

    $data = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        if ($row['FechaRegistro'] instanceof DateTime) {
            $row['FechaRegistro'] = $row['FechaRegistro']->format('Y-m-d');
        }
        $data[] = $row;
    }

    return $data;
}
}


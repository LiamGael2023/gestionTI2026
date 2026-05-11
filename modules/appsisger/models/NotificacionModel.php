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
}


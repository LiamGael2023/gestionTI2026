<?php

class NotificacionModel{

static public function obtenerPendientesConTokens(){

    $conn = Conexion::conectar();

    $sql = "SELECT 
                n.Id,
                n.Id_AmbitoOrganizacionUsuarios,
                n.Id_Anio,
                n.AmbOpe_CodigoCatastral,
                n.Rec_Numero,
                n.Band,
                n.Periodo,
                d.Token
            FROM BDSISGERWEB.Aplicativo.NotificacionesPendientes n
            INNER JOIN BDSISGERWEB.Aplicativo.vw_ConductoresPorAmbito d
                ON d.AmbOpe_CodigoCatastral = n.AmbOpe_CodigoCatastral
            WHERE n.Estado = 0";

    $stmt = sqlsrv_query($conn, $sql);

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $data = [];

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $data[] = $row;
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    return $data;
}
}
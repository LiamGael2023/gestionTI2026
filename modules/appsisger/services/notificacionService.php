<?php

    $datos = NotificacionModel::obtenerPendientesConTokens();

foreach ($datos as $row) {

    enviarFCM(
        $row['Token'],
        "Orden de riego",
        "Catastral: " . $row['AmbOpe_CodigoCatastral']
    );
}

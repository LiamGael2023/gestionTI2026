
<?php
class DispositivoModel {
public static function guardarToken($codigoUnico, $token){

    $conn = Conexion::conectar();

    $sql = "
    IF NOT EXISTS (
        SELECT 1 FROM BDSISGERWEB.Aplicativo.DispositivosRegistrados WHERE token = ?
    )
    INSERT INTO BDSISGERWEB.Aplicativo.DispositivosRegistrados (
        CodigoUnico,
        token,
        fecha
    )
    VALUES (?, ?, GETDATE())
    ";

    $params = [
        $token,
        $codigoUnico,
        $token
    ];

    return sqlsrv_query($conn, $sql, $params);
}
}
<?php


class ModeloConductor
{

    static public function MdlMostrarConductor($fecha)
    {

        $conn = Conexion::conectar();

        $sql = "{CALL [Transportes].[SP_Listar_Conductores_Papeleta_Diaria](?)}";
        $params = array($fecha);


        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            error_log('Error en la consulta: ' . print_r($errors, true));
            return []; // Devuelve array vacío en caso de error
        }

        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }

        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);


        return $result;
    }

    static public function MdlMostrarConductorReporteHistorial($item, $valor)
    {
        $conn = Conexion::conectar();

        $sql = "EXEC Transportes.SP_HISTORIAL_ASIGNAMIENTO_CONDUCTOR ?";
        $params = array($valor);

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            error_log('Error en la consulta: ' . print_r($errors, true));
            sqlsrv_close($conn);
            return [];
        }

        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }

        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        return $result;
    }
}

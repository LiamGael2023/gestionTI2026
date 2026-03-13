<?php

class PeriodoModel{

    static public function obtenerPeriodo($idAmbito,$codigoCatastral){

        $conn = Conexion::conectar();

        $sql = "{CALL BDSISGERWEB.appsisger.pa_ObtenerPeriodoxUC(?,?)}";

        $params = [
            $idAmbito,
            $codigoCatastral
        ];

        $stmt = sqlsrv_query($conn,$sql,$params);

        if($stmt === false){
            return [
                "error" => sqlsrv_errors()
            ];
        }

        $data = [];

        while($row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC)){
            $data[] = $row;
        }

        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        return $data;
    }
}

<?php

class OrdenRiegoModel{

    static public function obtenerOrden($idAmbito,$anio,$uc,$periodo){

        $conn = Conexion::conectar();

        $sql = "{CALL BDSISGERWEB.Distribucion.pa_OrdenRiego_Listar_Aplicativo_sisger(?,?,?,?)}";

        $params = [
            $idAmbito,
            $anio,
            $uc,
            $periodo
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
<?php

class UsuarioModel{

    static public function obtenerUsuario($codigo){

        $conn = Conexion::conectar();

        $sql = "{CALL BDSISGERWEB.appsisger.ObtenerUsuarioxCodigo(?)}";

        $params = [$codigo];

        $stmt = sqlsrv_query($conn,$sql,$params);

        if($stmt === false){
            return [];
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

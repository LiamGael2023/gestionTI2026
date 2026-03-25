<?php

class Trabajador {

    static public function mdlMostrarTrabajadoresFiltro($anio, $componente, $meta, $tipotrabajador){

    $conn = Conexion::conectar();

    $sql = "EXEC BDPERSONAL.Escalafon.pa_Listar_Trabajadores_Meta
            @anio = ?,
            @id_componente = ?,
            @id_meta = ?,
            @id_trabajador_tipo=?";

    $params = array($anio, $componente, $meta,$tipotrabajador);

    $stmt = sqlsrv_query($conn, $sql, $params);

    if($stmt === false){
        die(print_r(sqlsrv_errors(), true));
    }

    $datos = [];

    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
        $datos[] = $row;
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    return $datos;

}

static public function mdlMostrarComponente(){

    $conn = Conexion::conectar();

//    $sql = "SELECT Id_Componente, Comp_Descripcion FROM escalafon.Tbl_Componente";
   $sql = "SELECT DISTINCT
    c.Id_Componente AS Id_Componente,
    c.Comp_Descripcion
FROM BDPERSONAL.Escalafon.Tbl_Componente c
INNER JOIN BDPERSONAL.Escalafon.Tbl_Trabajador t 
    ON t.Id_Componente = c.Id_Componente
WHERE t.Trab_Estado = 1 and c.Id_Componente !=33";

    $stmt = sqlsrv_query($conn, $sql);

    if($stmt === false){
        $errors = sqlsrv_errors();
        error_log(print_r($errors,true));
        return [];
    }

    $datos = [];

    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
        $datos[] = $row;
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    return $datos;
}
static public function mdlMostrarMetas($anio, $componente){

    $conn = Conexion::conectar();

   
  $sql = "SELECT DISTINCT
    m.Id_Meta AS Id_Meta,
    m.Meta_Descripcion,m.Id_Componente AS descripcion, m.Id_Anio
FROM BDPERSONAL.Escalafon.Tbl_Meta m
INNER JOIN BDPERSONAL.Escalafon.Tbl_Trabajador t 
    ON t.Id_Meta = m.Id_Meta
WHERE t.Trab_Estado = 1 AND Id_Anio = ?  and m.Id_Componente = ?";

    $params = array($anio,$componente);

    $stmt = sqlsrv_query($conn, $sql, $params);

    if($stmt === false){
        die(print_r(sqlsrv_errors(), true));
    }

    $datos = [];

    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
        $datos[] = $row;
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    return $datos;
}

static public function mdlMostrarTipoTrabajador(){

    $conn = Conexion::conectar();

  $sql = "SELECT Id_Trabajador_Tipo, TrabTipo_Descripcion FROM BDPERSONAL.Escalafon.Tbl_Trabajador_tipo";
    $stmt = sqlsrv_query($conn, $sql);

    if($stmt === false){
        $errors = sqlsrv_errors();
        error_log(print_r($errors,true));
        return [];
    }

    $datos = [];

    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
        $datos[] = $row;
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    return $datos;
}

static public function mdlMostrarTurnoTrabajador(){

    $conn = Conexion::conectar();

  $sql = "SELECT DISTINCT
    mt.Id_Marcacion_Tipo, mt.MarcTipo_Descripcion
    from BDPERSONAL.Asistencia.Tbl_Marcacion_Tipo mt

    where MarcTipo_Estado=1
    order by MarcTipo_Descripcion ASC";
  
    $stmt = sqlsrv_query($conn, $sql);

    if($stmt === false){
        $errors = sqlsrv_errors();
        error_log(print_r($errors,true));
        return [];
    }

    $datos = [];

    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
        $datos[] = $row;
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    return $datos;
}
static public function mdlMostrarTurnosTrabajador($id_trabajador, $anio){
    $conn = Conexion::conectar();

    $sql = "EXEC BDPERSONAL.Asistencia.pa_Listar_Turnos_Trabajador @id_trabajador = ?, @anio = ?";
    $params = array($id_trabajador, $anio);

    $stmt = sqlsrv_query($conn, $sql, $params);

    if($stmt === false){
        die(print_r(sqlsrv_errors(), true));
    }

    $datos = [];
    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
        $datos[] = $row;
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    return $datos;
}


 static public function mdlGuardarTrabajadorTurno($datos){

        $conn = Conexion::conectar();

        $sql = "EXEC BDPERSONAL.Asistencia.Guardar_Turno_Trabajador
            @Id_Trabajador = ?,
            @Id_Componente = ?,
            @Id_Meta = ?,
            @Id_Trabajador_Tipo = ?,
            @Id_Anio = ?,";

        $params = array(
            $datos["trabajador"],
            $datos["componente"],
            $datos["meta"],
            $datos["trabajadortipo"],
            $datos["anio"]
        );

        $stmt = sqlsrv_query($conn, $sql, $params);

        if($stmt === false){
            echo json_encode(sqlsrv_errors());
            exit;
        }

        sqlsrv_free_stmt($stmt);
        sqlsrv_close($conn);

        return "ok";
    }

   static public function mdlMostrarTrabajadorSeleccionados($componente, $meta, $tipotrabajador, $anio){
    $conn = Conexion::conectar();

    $sql = "EXEC BDPERSONAL.Asistencia.pa_ListarTrabajadores_Seleccionados 
            @Id_Componente = ?, 
            @Id_Meta = ?, 
            @Id_Trabajador_Tipo = ?, 
            @Id_Anio = ?";

    $params = array($componente, $meta, $tipotrabajador, $anio);

    $stmt = sqlsrv_query($conn, $sql, $params);

    if($stmt === false){
        die(json_encode(sqlsrv_errors())); 
    }

    $datos = [];
    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){
        $datos[] = $row;
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    return $datos;
}

 static public function mdlEliminarTurnoTrabajador($componente, $meta, $anio, $id_trabajador){

    $conn = Conexion::conectar();

    $sql = "EXEC BDPERSONAL.Asistencia.pa_Eliminar_Trabajadores_Seleccionados 
            @Id_Componente = ?, 
            @Id_Meta = ?, 
            @Id_Anio = ?,
            @Id_Trabajador = ?";

    $params = array($componente, $meta, $anio, $id_trabajador);

    $stmt = sqlsrv_query($conn, $sql, $params);

    if($stmt === false){
        return ["status"=>"error", "detalle"=>sqlsrv_errors()];
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    return ["status"=>"ok"];
}




}

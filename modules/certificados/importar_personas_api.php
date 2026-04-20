<?php

require_once __DIR__ . '/../../config/db.php';

$conn = Conexion::conectar();

/* URL DE TU API */
$url = "https://www.chavimochic.gob.pe/api_incidencias/api_personal.php";

$response = file_get_contents($url);
$data = json_decode($response, true);

if(!isset($data['data'])){
    die("No se encontraron datos en el API");
}

foreach($data['data'] as $p){

    /* FILTRO POR TIPO */
    if(
        $p['TrabTipo_Descripcion'] !== 'CAP' &&
        $p['TrabTipo_Descripcion'] !== 'CAP - INCORPORACIÓN POR MANDATO JUDICIAL'
    ){
        continue; // salta al siguiente registro
    }

    $dni = $p['Documento'];
    $nombres = $p['Nombres'];
    $apellidos = $p['Trab_Paterno']." ".$p['Trab_Materno'];
    $correo = $p['Correo'] ?? null;
    $telefono = $p['Celular'] ?? null;
    $gerencia = $p['Gerencia_Laboral'] ?? null;
    $codigo_reloj = $p['codigo_reloj'] ?? null;

    /* verificar si ya existe por DNI */
    $sql_check = "SELECT id_persona FROM certificados.Personas WHERE dni = ?";
    $check = sqlsrv_query($conn,$sql_check,[$dni]);

    if(sqlsrv_fetch_array($check)){

        $sql_update = "
        UPDATE certificados.Personas
        SET nombres = ?, 
            apellidos = ?, 
            correo = ?, 
            telefono = ?, 
            gerencia_laboral = ?, 
            codigo_reloj = ?
        WHERE dni = ?
        ";

        $params = [
            $nombres,
            $apellidos,
            $correo,
            $telefono,
            $gerencia,
            $codigo_reloj,
            $dni
        ];

        sqlsrv_query($conn,$sql_update,$params);

    }else{

        $sql_insert = "
        INSERT INTO certificados.Personas
        (dni,nombres,apellidos,correo,telefono,gerencia_laboral,codigo_reloj)
        VALUES (?,?,?,?,?,?,?)
        ";

        $params = [
            $dni,
            $nombres,
            $apellidos,
            $correo,
            $telefono,
            $gerencia,
            $codigo_reloj
        ];

        sqlsrv_query($conn,$sql_insert,$params);
    }

}

echo "Importación CAP completada";

?>
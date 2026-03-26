<?php

$datos = json_decode($_POST['datos'], true);
if(!$datos) { echo "No hay datos"; exit; }

$conn = Conexion::conectar();

foreach($datos as $fila){
    $sql = "EXEC Asistencia.Guardar_Trabajador_Seleccionado_Turno 
        @Id_Trabajador = ?, 
        @Id_Componente = ?, 
        @Id_Meta = ?, 
        @Id_Trabajador_Tipo = ?, 
        @Id_Anio = ?";
    $params = [
        $fila['id'],
        $fila['componente'],
        $fila['meta'],
        $fila['tipotrabajador'],
        $fila['anio']
    ];

    sqlsrv_query($conn, $sql, $params);
}

sqlsrv_close($conn);
echo "ok";
?>
<?php
// Desactivar reporte de errores visuales para no romper el JSON
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

// 1. Obtener término
$busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';

// 2. Consumir API
$apiUrl = "https://www.chavimochic.gob.pe/api_incidencias/api_personal.php";
$jsonResponse = @file_get_contents($apiUrl);

if ($jsonResponse === FALSE) {
    echo json_encode(['success' => false, 'data' => []]);
    exit;
}

// 3. Decodificar
$respuesta = json_decode($jsonResponse, true);
$listaPersonal = isset($respuesta['data']) ? $respuesta['data'] : [];

// 4. Filtrado Local
if (!empty($busqueda)) {
    $busqueda = mb_strtolower($busqueda, 'UTF-8');
    
    $listaFiltrada = array_filter($listaPersonal, function($persona) use ($busqueda) {
        $textoCompleto = $persona['Documento'] . ' ' . 
                         $persona['Nombres'] . ' ' . 
                         $persona['Trab_Paterno'] . ' ' . 
                         $persona['Trab_Materno'] . ' ' .
                         $persona['usuario'];
        
        $textoCompleto = mb_strtolower($textoCompleto, 'UTF-8');
        return strpos($textoCompleto, $busqueda) !== false;
    });

    $listaPersonal = array_values($listaFiltrada);
}

// 5. Respuesta
echo json_encode(['success' => true, 'data' => $listaPersonal]);
?>
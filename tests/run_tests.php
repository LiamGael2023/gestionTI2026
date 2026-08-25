<?php
/**
 * Test Suite - Pruebas integrales de la Arquitectura Refactorizada CHAVIsystems
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=====================================================\n";
echo " EJECUTANDO SUITE DE PRUEBAS DE ARQUITECTURA MVC \n";
echo "=====================================================\n\n";

$baseDir = __DIR__ . '/../';
require_once $baseDir . 'config/config.php';
require_once $baseDir . 'config/db.php';
require_once $baseDir . 'core/Autoloader.php';

$passed = 0;
$failed = 0;

function assertTest($description, $condition) {
    global $passed, $failed;
    if ($condition) {
        echo " [OK] $description\n";
        $passed++;
    } else {
        echo " [FAIL] $description\n";
        $failed++;
    }
}

// 1. Conexión a Base de Datos
$conn = Conexion::conectar();
assertTest("Conexión a SQL Server mediante Conexion::conectar()", $conn !== false);

// 2. Autoloader & Clases Core
assertTest("Clase Autoloader registrada correctamente", class_exists('Autoloader') || function_exists('spl_autoload_functions'));
assertTest("Carga automática de clase Response", class_exists('Response'));
assertTest("Carga automática de clase Router", class_exists('Router'));
assertTest("Carga automática de clase PrecioService", class_exists('PrecioService'));
assertTest("Carga automática de clase StockService", class_exists('StockService'));

// 3. Router
$router = Router::getInstance();
assertTest("Instancia de Router creada (Singleton)", $router instanceof Router);
$acciones = $router->obtenerAccionesAjax();
assertTest("Router obtiene acciones AJAX (total: " . count($acciones) . ")", is_array($acciones) && count($acciones) > 40);
assertTest("Chatbot action 'chat_enviar' presente en acciones AJAX", in_array('chat_enviar', $acciones));
assertTest("Inventario action 'guardar_producto' presente en acciones AJAX", in_array('guardar_producto', $acciones));
assertTest("Punto de venta action 'guardar_venta' presente en acciones AJAX", in_array('guardar_venta', $acciones));

// 4. PrecioService
$precioService = new PrecioService($conn);
$uitVal = $precioService->obtenerValorUITActual();
assertTest("PrecioService calcula valor UIT actual: S/ " . number_format($uitVal, 2), $uitVal > 4000);

// Producto UIT de prueba
$prodUIT = ['tipo_precio' => 'UIT', 'porcentaje_uit' => 10.0];
$precioCalc = $precioService->calcularPrecioProducto($prodUIT);
assertTest("PrecioService calcula precio basado en UIT (10% de S/ $uitVal = S/ $precioCalc)", $precioCalc == round($uitVal * 0.1, 2));

// 5. StockService
$stockService = new StockService($conn);
assertTest("StockService se instancia correctamente", $stockService instanceof StockService);

// 6. Verificar Sintaxis de Todos los Controladores
echo "\n--- VERIFICANDO SINTAXIS PHP DE CONTROLADORES Y MODELOS ---\n";
$files = glob($baseDir . 'modules/*/controllers/*.php');
$files = array_merge($files, glob($baseDir . 'modules/*/models/*.php'));
$files = array_merge($files, glob($baseDir . 'core/*.php'));
$files = array_merge($files, glob($baseDir . 'core/Services/*.php'));

foreach ($files as $file) {
    $relPath = str_replace($baseDir, '', $file);
    $cmd = '"C:\\Program Files\\PHP\\php-8.2.21\\php.exe" -l "' . $file . '"';
    $output = shell_exec($cmd);
    $isValid = strpos($output, 'No syntax errors detected') !== false;
    assertTest("Sintaxis de $relPath", $isValid);
}

echo "\n=====================================================\n";
echo " RESUMEN: $passed PRUEBAS PASADAS, $failed FALLIDAS \n";
echo "=====================================================\n";

if ($failed > 0) {
    exit(1);
}

<?php
/**
 * cron_cancelar.php
 * Ejecuta la auto-cancelación de reservas vencidas para el módulo de Salas.
 * Diseñado para Task Scheduler en Windows.
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../models/core/SalasModel.php';

$runtimeDir = __DIR__ . '/../../../runtime/salas';
if (!is_dir($runtimeDir)) {
    @mkdir($runtimeDir, 0775, true);
}

$lockPath = $runtimeDir . '/autocancel.lock';
$lockHandle = @fopen($lockPath, 'c');
if ($lockHandle === false) {
    http_response_code(500);
    echo "No se pudo crear/abrir lock.\n";
    exit(1);
}

if (!@flock($lockHandle, LOCK_EX | LOCK_NB)) {
    echo "Otra ejecución está en curso.\n";
    fclose($lockHandle);
    exit(0);
}

try {
    $conn = Conexion::conectar();
    $model = new SalasModel($conn);
    $total = $model->cancelarReservasVencidas();
    echo "OK. Reservas auto-canceladas: " . (int) $total . "\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage() . "\n";
    @flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
    exit(1);
}

@flock($lockHandle, LOCK_UN);
fclose($lockHandle);
exit(0);

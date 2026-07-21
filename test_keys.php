<?php
require_once 'config/db.php';
require_once 'modules/produccion_agraria/models/PuntoVentaModel.php';

$conn = Conexion::conectar();
$model = new PuntoVentaModel($conn);
$productos = $model->listarProductosVenta();

echo "<html><body><pre>\n";
foreach ($productos as $p) {
    echo "ID: " . $p['id_producto'] . " | Name: " . $p['nombre'] . "\n";
    echo "Keys: " . implode(', ', array_keys($p)) . "\n";
    echo "nombre_centro: '" . ($p['nombre_centro'] ?? 'NOT SET') . "'\n";
    echo "--------------------\n";
}
echo "</pre></body></html>\n";
?>

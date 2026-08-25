<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

$base_path = realpath(dirname(__FILE__) . '/../../../../');
require_once $base_path . '/config/db.php';

$conn = Conexion::conectar();

// Obtener el ID del informe desde la URL
$id_registro = intval($_GET['id'] ?? 1);

echo "<h1>🔍 DEBUG RESIDUOS - ID INFORME: $id_registro</h1>";

// PASO 1: Verificar que el informe existe
echo "<h2>1. Verificar Cabecera Informe:</h2>";
$sql1 = "SELECT Id_Registro_Res, Mes, Anio, Ubicacion FROM laboratorio.Registro_Residuos_Log WHERE Id_Registro_Res = ?";
$stmt1 = sqlsrv_query($conn, $sql1, [$id_registro]);
if ($row1 = sqlsrv_fetch_array($stmt1, SQLSRV_FETCH_ASSOC)) {
    echo "✅ Informe encontrado: Mes {$row1['Mes']}, Año {$row1['Anio']}, Ubicación: {$row1['Ubicacion']}<br>";
} else {
    echo "❌ Informe NO encontrado<br>";
    die();
}

// PASO 2: Contar registros en Detalle_Residuos_Log
echo "<h2>2. Registros en Detalle_Residuos_Log:</h2>";
$sql2 = "SELECT COUNT(*) as total FROM laboratorio.Detalle_Residuos_Log WHERE Id_Registro_Res = ? AND Activo = 1";
$stmt2 = sqlsrv_query($conn, $sql2, [$id_registro]);
if ($row2 = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC)) {
    echo "Total registros: <strong>" . $row2['total'] . "</strong><br>";
    if ($row2['total'] == 0) {
        echo "❌ PROBLEMA: No hay registros en Detalle_Residuos_Log<br>";
    }
}

// PASO 3: Ver todos los datos crudos
echo "<h2>3. Datos Crudos de Detalle_Residuos_Log:</h2>";
$sql3 = "SELECT 
    drl.Id_Detalle_Res,
    drl.Fecha_Dia,
    drl.Peso_Valor,
    rc.Id_Residuo_Cat,
    rc.Codigo_Item,
    rc.Subcategoria,
    rc.Nombre_Item,
    rc.Tipo_Principal
FROM laboratorio.Detalle_Residuos_Log drl
LEFT JOIN laboratorio.Residuo_Catalogo rc ON drl.Id_Residuo_Cat = rc.Id_Residuo_Cat
WHERE drl.Id_Registro_Res = ? AND drl.Activo = 1
ORDER BY drl.Fecha_Dia, rc.Subcategoria";

$stmt3 = sqlsrv_query($conn, $sql3, [$id_registro]);

echo '<table border="1" cellpadding="10" style="width: 100%; margin-bottom: 20px; font-size: 12px;">';
echo '<tr style="background: #e0e0e0;"><th>ID</th><th>Fecha</th><th>Código</th><th>Nombre Residuo</th><th>Subcategoría</th><th>Tipo Principal</th><th>Peso</th></tr>';

$count = 0;
$subcatList = [];
while ($row = sqlsrv_fetch_array($stmt3, SQLSRV_FETCH_ASSOC)) {
    $fecha = $row['Fecha_Dia'] ? $row['Fecha_Dia']->format('d-m-Y') : 'NULL';
    echo '<tr>';
    echo '<td>' . $row['Id_Detalle_Res'] . '</td>';
    echo '<td>' . $fecha . '</td>';
    echo '<td>' . htmlspecialchars($row['Codigo_Item'] ?? 'NULL') . '</td>';
    echo '<td>' . htmlspecialchars($row['Nombre_Item'] ?? 'NULL') . '</td>';
    echo '<td><strong>' . htmlspecialchars($row['Subcategoria'] ?? 'NULL') . '</strong></td>';
    echo '<td>' . htmlspecialchars($row['Tipo_Principal'] ?? 'NULL') . '</td>';
    echo '<td style="text-align: right;">' . number_format($row['Peso_Valor'] ?? 0, 2) . '</td>';
    echo '</tr>';
    $count++;
    if ($row['Subcategoria']) {
        $subcatList[strtoupper(trim($row['Subcategoria']))] = $row['Subcategoria'];
    }
}
echo '</table>';

echo "<strong>Total de registros encontrados: $count</strong><br><br>";

// PASO 4: Comparar con categorías FIJAS
echo "<h2>4. Comparación con Categorías Fijas:</h2>";
$categoriasEsperadas = [
    'ORGÁNICO', 'APROVECHABLE', 'NO APROVECHABLE',
    'QUÍMICO', 'BIOLÓGICO', 'METALES PESADOS', 'REACTIVOS', 'MATERIAL CONTAMINADO'
];

echo '<table border="1" cellpadding="8" style="margin-bottom: 20px;">';
echo '<tr style="background: #f0f0f0;"><th>Categoría Esperada</th><th>Encontrada en BD?</th><th>Valor Exacto en BD</th></tr>';

foreach ($categoriasEsperadas as $cat) {
    $catUpper = strtoupper(trim($cat));
    $encontrada = isset($subcatList[$catUpper]) ? '✅ SÍ' : '❌ NO';
    $valor = $subcatList[$catUpper] ?? '-';
    echo '<tr>';
    echo '<td><strong>' . $cat . '</strong> (uppercase: ' . $catUpper . ')</td>';
    echo '<td>' . $encontrada . '</td>';
    echo '<td>' . htmlspecialchars($valor) . '</td>';
    echo '</tr>';
}
echo '</table>';

// PASO 5: Subcategorías que sí existen en BD
echo "<h2>5. Subcategorías Reales en BD para este Informe:</h2>";
if (empty($subcatList)) {
    echo "❌ NINGUNA - No hay datos de residuos registrados<br>";
} else {
    echo '<ul>';
    foreach ($subcatList as $upper => $original) {
        echo '<li><strong>' . htmlspecialchars($original) . '</strong> (UPPER: ' . $upper . ')</li>';
    }
    echo '</ul>';
}

echo "<hr>";
echo "<p style='color: #666; font-size: 12px;'>Script de debug - Si no ves datos aquí, el problema está en la BD, no en PHP.</p>";
?>

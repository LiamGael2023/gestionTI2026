<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

$base_path = realpath(dirname(__FILE__) . '/../../../../');
require_once $base_path . '/config/db.php';

$conn = Conexion::conectar();

$id_registro_res = intval($_GET['id'] ?? 0);

if ($id_registro_res <= 0) {
    die('ID no válido');
}

// Categorías FIJAS
$subcategoriasNoPeligrosas = ['ORGÁNICO' => true, 'APROVECHABLE' => true, 'NO APROVECHABLE' => true];
$subcategoriasPeligrosas = ['QUÍMICO' => true, 'BIOLÓGICO' => true, 'METALES PESADOS' => true, 'REACTIVOS' => true, 'MATERIAL CONTAMINADO' => true];
$categoriasEsperadas = array_merge(array_keys($subcategoriasNoPeligrosas), array_keys($subcategoriasPeligrosas));

echo "<h2>🔍 DEBUG: Verificando Informe ID " . $id_registro_res . "</h2>";

// 1. Revisar cabecera
echo "<h3>1. Información del Informe:</h3>";
$sqlCab = "SELECT Mes, Anio, Ubicacion FROM laboratorio.Registro_Residuos_Log WHERE Id_Registro_Res = ?";
$stmtCab = sqlsrv_query($conn, $sqlCab, [$id_registro_res]);
if ($stmtCab && $cab = sqlsrv_fetch_array($stmtCab, SQLSRV_FETCH_ASSOC)) {
    echo "Mes: {$cab['Mes']} | Año: {$cab['Anio']} | Ubicación: {$cab['Ubicacion']}<br>";
}

// 2. Revisar subcategorías en BD  
echo "<h3>2. Subcategorías en Residuo_Catalogo:</h3>";
$sqlSubcat = "SELECT DISTINCT UPPER(TRIM(rc.Subcategoria)) as Subcategoria, COUNT(*) as Cantidad 
              FROM laboratorio.Residuo_Catalogo rc 
              WHERE rc.Activo = 1 
              GROUP BY UPPER(TRIM(rc.Subcategoria))
              ORDER BY Subcategoria";
$stmtSubcat = sqlsrv_query($conn, $sqlSubcat);
$subcatsEnBD = [];
if ($stmtSubcat) {
    echo '<table border="1" cellpadding="8">';
    echo '<tr><th>Subcategoría (BD)</th><th>Cantidad de Items</th><th>¿Coincide con Fijas?</th></tr>';
    while ($row = sqlsrv_fetch_array($stmtSubcat, SQLSRV_FETCH_ASSOC)) {
        $subcatBD = $row['Subcategoria'];
        $coincide = in_array($subcatBD, $categoriasEsperadas) ? '✅ SÍ' : '❌ NO';
        echo '<tr><td><strong>' . $subcatBD . '</strong></td><td>' . $row['Cantidad'] . '</td><td>' . $coincide . '</td></tr>';
        $subcatsEnBD[] = $subcatBD;
    }
    echo '</table>';
} else {
    echo 'Error en consulta de subcategorías';
}

echo "<hr>";

// 3. Revisar Detalle_Residuos_Log
echo "<h3>3. Detalles de Residuos (Detalle_Residuos_Log):</h3>";
$sql = "SELECT 
    drl.Fecha_Dia,
    drl.Peso_Valor,
    rc.Codigo_Item,
    rc.Subcategoria,
    rc.Tipo_Principal,
    rc.Id_Residuo_Cat
FROM laboratorio.Detalle_Residuos_Log drl
JOIN laboratorio.Residuo_Catalogo rc ON drl.Id_Residuo_Cat = rc.Id_Residuo_Cat
WHERE drl.Id_Registro_Res = ? AND drl.Activo = 1
ORDER BY drl.Fecha_Dia ASC, rc.Subcategoria ASC";

$stmt = sqlsrv_query($conn, $sql, [$id_registro_res]);

if ($stmt === false) {
    echo '<div style="background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24;">';
    echo '<strong>❌ ERROR SQL:</strong><pre>' . print_r(sqlsrv_errors(), true) . '</pre>';
    echo '</div>';
} else {
    echo '<table border="1" cellpadding="10" style="width: 100%; margin-bottom: 20px;">';
    echo '<tr style="background: #f0f0f0;"><th>Fecha</th><th>Código</th><th>Subcategoría (BD)</th><th>Tipo Principal</th><th>Peso</th></tr>';
    
    $count = 0;
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $fecha = $row['Fecha_Dia']->format('d-m-Y');
        echo '<tr>';
        echo '<td>' . $fecha . '</td>';
        echo '<td>' . htmlspecialchars($row['Codigo_Item']) . '</td>';
        echo '<td><strong>' . htmlspecialchars($row['Subcategoria']) . '</strong></td>';
        echo '<td>' . htmlspecialchars($row['Tipo_Principal']) . '</td>';
        echo '<td style="text-align: right;">' . number_format($row['Peso_Valor'], 2) . '</td>';
        echo '</tr>';
        $count++;
    }
    
    echo '</table>';
    
    if ($count === 0) {
        echo '<div style="background: #fff3cd; padding: 15px; border: 1px solid #ffeeba; border-radius: 4px; color: #856404;">';
        echo '<strong>⚠️ PROBLEMA ENCONTRADO:</strong><br>';
        echo 'No hay registros en <code>Detalle_Residuos_Log</code> para este informe.');
        echo '</div>';
    } else {
        echo '<div style="background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 4px; color: #155724;">';
        echo '<strong>✅ Datos encontrados:</strong> ' . $count . ' registros';
        echo '</div>';
    }
}
?>

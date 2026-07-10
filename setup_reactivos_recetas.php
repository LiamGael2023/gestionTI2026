<?php
/**
 * setup_reactivos_recetas.php
 * Script para configurar reactivos, unidades de medida y recetas
 * Ejecutar: php setup_reactivos_recetas.php
 */
require_once __DIR__ . '/config/db.php';

$conn = Conexion::conectar();
if (!$conn) die("Error de conexion\n");

$usuario_id = 1;

echo "=== CONFIGURANDO UNIDADES DE MEDIDA ===\n";
$unidades = [
    ['Mililitros', 'mL'],
    ['Litros', 'L'],
    ['Gramos', 'g'],
    ['Kilogramos', 'kg'],
    ['Miligramos', 'mg'],
    ['Unidades', 'und'],
    ['Tabletas', 'tab'],
    ['Sobres', 'sob'],
];

foreach ($unidades as $u) {
    $check = sqlsrv_query($conn, "SELECT Id_Unidad_Medida FROM laboratorio.Unidad_Medida WHERE Nombre = ? AND Activo = 1", [$u[0]]);
    if ($check && sqlsrv_fetch_array($check)) {
        echo "  $u[0] ya existe\n";
        continue;
    }
    sqlsrv_query($conn, "INSERT INTO laboratorio.Unidad_Medida (Nombre, Abreviatura, Activo, Fecha_Creacion, Usuario_Creacion) VALUES (?, ?, 1, GETDATE(), ?)", [$u[0], $u[1], $usuario_id]);
    echo "  + $u[0] ($u[1])\n";
}

echo "\n=== CONFIGURANDO REACTIVOS ===\n";
// Cada reactivo con: [Nombre, Tipo, Unidad_Nombre, Cantidad_Inicial, Proveedor_Id]
$reactivos_data = [
    ['Ácido Clorhídrico 37%', 'Agua', 'Litros', 5.0],
    ['Ácido Sulfúrico 98%', 'Agua', 'Litros', 3.0],
    ['Ácido Nítrico 65%', 'Agua', 'Litros', 2.0],
    ['Hidróxido de Sodio 0.1N', 'Agua', 'Litros', 10.0],
    ['Indicador Negro de Ericromo T', 'Agua', 'Gramos', 100.0],
    ['Solución Buffer pH 4.0', 'Agua', 'Mililitros', 500.0],
    ['Solución Buffer pH 7.0', 'Agua', 'Mililitros', 500.0],
    ['Solución Buffer pH 10.0', 'Agua', 'Mililitros', 500.0],
    ['Cloruro de Sodio PA', 'Agua', 'Gramos', 500.0],
    ['Nitrato de Plata 0.0141N', 'Agua', 'Litros', 2.0],
    ['Cromato de Potasio 5%', 'Agua', 'Mililitros', 250.0],
    ['Solución Estándar de Cobre 1000ppm', 'Agua', 'Mililitros', 100.0],
    ['Solución Estándar de Zinc 1000ppm', 'Agua', 'Mililitros', 100.0],
    ['Solución Estándar de Hierro 1000ppm', 'Agua', 'Mililitros', 100.0],
    ['Solución Estándar de Manganeso 1000ppm', 'Agua', 'Mililitros', 100.0],
    ['Cloruro de Bario 10%', 'Agua', 'Mililitros', 500.0],
    ['Ácido Sulfámico', 'Agua', 'Gramos', 250.0],
    ['Medio de Cultivo Coliformes Totales', 'Agua', 'Sobres', 50.0],
    ['Medio de Cultivo E.coli', 'Agua', 'Sobres', 50.0],
    ['Agua Destilada', 'Agua', 'Litros', 20.0],
];

foreach ($reactivos_data as $r) {
    $check = sqlsrv_query($conn, "SELECT Id_Reactivo FROM laboratorio.Reactivo_Lab WHERE Nombre = ? AND Activo = 1", [$r[0]]);
    if ($check && sqlsrv_fetch_array($check)) {
        echo "  $r[0] ya existe\n";
        continue;
    }
    
    // Obtener Id_Unidad_Medida
    $stmtUm = sqlsrv_query($conn, "SELECT Id_Unidad_Medida FROM laboratorio.Unidad_Medida WHERE Nombre = ? AND Activo = 1", [$r[2]]);
    $rowUm = $stmtUm ? sqlsrv_fetch_array($stmtUm, SQLSRV_FETCH_ASSOC) : null;
    $id_um = $rowUm ? intval($rowUm['Id_Unidad_Medida']) : null;
    
    // Insertar reactivo (stock inicia en 0)
    sqlsrv_query($conn, 
        "INSERT INTO laboratorio.Reactivo_Lab (Nombre, Tipo, Cantidad_Inicial, Cantidad_Stock, Cantidad_Reservada, Fecha_Vencimiento, Id_Unidad_Medida, Id_Proveedor, Activo, Fecha_Creacion, Usuario_Creacion)
         VALUES (?, ?, ?, 0, 0, DATEADD(YEAR, 2, GETDATE()), ?, 1, 1, GETDATE(), ?)",
        [$r[0], $r[1], $r[3], $id_um, $usuario_id]
    );
    
    // Obtener el ID creado
    $stmtId = sqlsrv_query($conn, "SELECT SCOPE_IDENTITY() AS id");
    sqlsrv_next_result($stmtId);
    $rowId = sqlsrv_fetch_array($stmtId, SQLSRV_FETCH_ASSOC);
    $id_reactivo = intval($rowId['id'] ?? 0);
    
    if ($id_reactivo > 0) {
        // Registrar INGRESO INICIAL (el trigger TR_Post_IngresoReactivo hará el kardex + stock)
        sqlsrv_query($conn,
            "INSERT INTO laboratorio.Ingreso_Reactivo (Id_Reactivo, Id_Usuario, Cantidad, Factura_Referencia, Fecha_Ingreso, Activo, Fecha_Creacion, Usuario_Creacion)
             VALUES (?, ?, ?, 'INGRESO INICIAL', GETDATE(), 1, GETDATE(), ?)",
            [$id_reactivo, $usuario_id, $r[3], $usuario_id]
        );
        echo "  + $r[0] (stock inicial: $r[3] $r[2])\n";
    }
}

echo "\n=== CONFIGURANDO RECETAS (Reactivo → Servicio) ===\n";
// Mapeo: Servicio_ID => [[Reactivo_Nombre, Cantidad_Necesaria], ...]
$recetas = [
    // Servicio 1: Análisis de pH para Suelo (requiere buffer)
    1 => [['Solución Buffer pH 7.0', 0.005], ['Agua Destilada', 0.010]],
    
    // Servicio 2: Análisis de Cloruros (Cl-)
    2 => [['Nitrato de Plata 0.0141N', 0.010], ['Cromato de Potasio 5%', 0.002], ['Agua Destilada', 0.050]],
    
    // Servicio 3: Análisis de Cobre (Cu)
    3 => [['Ácido Nítrico 65%', 0.005], ['Solución Estándar de Cobre 1000ppm', 0.001], ['Agua Destilada', 0.100]],
    
    // Servicio 4: Análisis de Coliformes Termotolerantes
    4 => [['Medio de Cultivo Coliformes Totales', 1.0], ['Agua Destilada', 0.100]],
    
    // Servicio 5: Análisis de Coliformes Totales
    5 => [['Medio de Cultivo Coliformes Totales', 1.0], ['Agua Destilada', 0.100]],
    
    // Servicio 6: Cromo Hexavalente
    6 => [['Ácido Sulfúrico 98%', 0.003], ['Agua Destilada', 0.100]],
    
    // Servicio 7: Dureza Total
    7 => [['Solución Buffer pH 10.0', 0.005], ['Indicador Negro de Ericromo T', 0.050], ['Agua Destilada', 0.050]],
    
    // Servicio 8: E.coli
    8 => [['Medio de Cultivo E.coli', 1.0], ['Agua Destilada', 0.100]],
    
    // Servicio 9: Hierro (Fe)
    9 => [['Ácido Clorhídrico 37%', 0.005], ['Solución Estándar de Hierro 1000ppm', 0.001], ['Agua Destilada', 0.100]],
    
    // Servicio 10: Manganeso (Mn)
    10 => [['Ácido Nítrico 65%', 0.005], ['Solución Estándar de Manganeso 1000ppm', 0.001], ['Agua Destilada', 0.100]],
    
    // Servicio 11: Nitratos
    11 => [['Ácido Sulfámico', 0.100], ['Ácido Sulfúrico 98%', 0.005], ['Agua Destilada', 0.050]],
    
    // Servicio 12: Nitritos
    12 => [['Ácido Sulfámico', 0.050], ['Ácido Clorhídrico 37%', 0.003], ['Agua Destilada', 0.050]],
    
    // Servicio 13: Sulfatos
    13 => [['Cloruro de Bario 10%', 0.005], ['Ácido Clorhídrico 37%', 0.002], ['Agua Destilada', 0.050]],
    
    // Servicio 14: Turbidez
    14 => [['Agua Destilada', 0.100]],
    
    // Servicio 15: Zinc
    15 => [['Ácido Clorhídrico 37%', 0.005], ['Solución Estándar de Zinc 1000ppm', 0.001], ['Agua Destilada', 0.100]],
];

foreach ($recetas as $id_servicio => $items) {
    foreach ($items as $item) {
        $nombre_reactivo = $item[0];
        $cantidad = $item[1];
        
        // Buscar IDs
        $stmtR = sqlsrv_query($conn, "SELECT Id_Reactivo FROM laboratorio.Reactivo_Lab WHERE Nombre = ? AND Activo = 1", [$nombre_reactivo]);
        $rowR = $stmtR ? sqlsrv_fetch_array($stmtR, SQLSRV_FETCH_ASSOC) : null;
        if (!$rowR) { echo "  ⚠ Reactivo '$nombre_reactivo' no encontrado\n"; continue; }
        $id_reactivo = intval($rowR['Id_Reactivo']);
        
        // Verificar si ya existe la receta
        $check = sqlsrv_query($conn, "SELECT Id_Receta_Servicio FROM laboratorio.Receta_Servicio WHERE Id_Reactivo = ? AND Id_Servicio = ? AND Activo = 1", [$id_reactivo, $id_servicio]);
        if ($check && sqlsrv_fetch_array($check)) {
            // Actualizar cantidad
            sqlsrv_query($conn, "UPDATE laboratorio.Receta_Servicio SET Cantidad_Necesaria = ?, Fecha_Modificacion = GETDATE() WHERE Id_Reactivo = ? AND Id_Servicio = ?", [$cantidad, $id_reactivo, $id_servicio]);
            echo "  ~ $nombre_reactivo → Servicio $id_servicio (actualizado: $cantidad)\n";
        } else {
            sqlsrv_query($conn, "INSERT INTO laboratorio.Receta_Servicio (Id_Reactivo, Id_Servicio, Cantidad_Necesaria, Activo, Fecha_Creacion, Usuario_Creacion) VALUES (?, ?, ?, 1, GETDATE(), ?)", [$id_reactivo, $id_servicio, $cantidad, $usuario_id]);
            echo "  + $nombre_reactivo → Servicio $id_servicio ($cantidad)\n";
        }
    }
}

echo "\n=== VERIFICACIÓN FINAL ===\n";
$stmt = sqlsrv_query($conn, "SELECT COUNT(*) AS cnt FROM laboratorio.Reactivo_Lab WHERE Activo = 1");
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
echo "Reactivos activos: " . intval($row['cnt'] ?? 0) . "\n";

$stmt2 = sqlsrv_query($conn, "SELECT COUNT(*) AS cnt FROM laboratorio.Receta_Servicio WHERE Activo = 1");
$row2 = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC);
echo "Recetas activas: " . intval($row2['cnt'] ?? 0) . "\n";

$stmt3 = sqlsrv_query($conn, "SELECT COUNT(*) AS cnt FROM laboratorio.Ingreso_Reactivo WHERE Activo = 1");
$row3 = sqlsrv_fetch_array($stmt3, SQLSRV_FETCH_ASSOC);
echo "Ingresos registrados: " . intval($row3['cnt'] ?? 0) . "\n";

$stmt4 = sqlsrv_query($conn, "SELECT COUNT(*) AS cnt FROM laboratorio.Movimiento_Kardex WHERE Activo = 1");
$row4 = sqlsrv_fetch_array($stmt4, SQLSRV_FETCH_ASSOC);
echo "Movimientos kardex: " . intval($row4['cnt'] ?? 0) . "\n";

echo "\n✅ Configuración completada.\n";

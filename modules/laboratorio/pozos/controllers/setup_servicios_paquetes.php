<?php
/**
 * Script de Configuración Automática: Servicios, Paquetes y Datos Históricos
 * 
 * Ejecutar via HTTP: http://localhost/gestionTI/modules/laboratorio/pozos/controllers/setup_servicios_paquetes.php
 * 
 * 1. Crea un servicio por cada parámetro de laboratorio (calidad_agua_laboratorio)
 * 2. Liga cada servicio al equipo 01
 * 3. Crea un paquete "Análisis Completo Pozos" que incluye todos los servicios
 * 4. Actualiza cada parámetro con su Id_Servicio correspondiente
 */
error_reporting(E_ALL);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../../../../config/db.php';
    
    $conn = Conexion::conectar();
    if (!$conn) {
        throw new Exception("Error de conexión a BD");
    }
    
    $usuario_id = 1; // Sistema
    $id_equipo = 1;  // Equipo 01
    $stats = [
        'servicios_creados' => 0,
        'parametros_actualizados' => 0,
        'paquete_id' => null,
        'errores' => []
    ];

    // ====================================================================
    // PASO 1: Obtener todos los parámetros que mapean a calidad_agua_laboratorio
    // ====================================================================
    $sqlParams = "SELECT Id_Parametro, Nombre, Posgre_Tabla, Posgre_Nombre, Id_Servicio 
                  FROM laboratorio.Parametro_Analisis 
                  WHERE Posgre_Tabla = 'calidad_agua_laboratorio' AND Posgre_Nombre IS NOT NULL AND Activo = 1
                  ORDER BY Nombre";
    $stmtParams = sqlsrv_query($conn, $sqlParams);
    if ($stmtParams === false) {
        throw new Exception("Error al obtener parámetros: " . print_r(sqlsrv_errors(), true));
    }

    $parametros_lab = [];
    while ($row = sqlsrv_fetch_array($stmtParams, SQLSRV_FETCH_ASSOC)) {
        $parametros_lab[] = $row;
    }

    if (empty($parametros_lab)) {
        throw new Exception("No hay parámetros mapeados a calidad_agua_laboratorio. Crea los parámetros primero.");
    }

    // ====================================================================
    // PASO 1B: Obtener parámetros in-situ (pozos_monitoreo)
    // ====================================================================
    $sqlParamsInSitu = "SELECT Id_Parametro, Nombre, Posgre_Tabla, Posgre_Nombre, Id_Servicio 
                        FROM laboratorio.Parametro_Analisis 
                        WHERE Posgre_Tabla = 'pozos_monitoreo' AND Posgre_Nombre IS NOT NULL AND Activo = 1
                        ORDER BY Nombre";
    $stmtParamsInSitu = sqlsrv_query($conn, $sqlParamsInSitu);
    $parametros_insitu = [];
    while ($row = sqlsrv_fetch_array($stmtParamsInSitu, SQLSRV_FETCH_ASSOC)) {
        $parametros_insitu[] = $row;
    }

    // ====================================================================
    // PASO 2: Crear un servicio por cada parámetro de lab SI no tiene servicio asignado
    // ====================================================================
    $servicios_ids_lab = [];
    
    foreach ($parametros_lab as $param) {
        $nombre_servicio = "Análisis de " . $param['Nombre'];
        
        // Verificar si ya tiene servicio asignado
        if (!empty($param['Id_Servicio'])) {
            $servicios_ids_lab[] = $param['Id_Servicio'];
            continue;
        }
        
        // Verificar si ya existe un servicio con ese nombre
        $sqlCheckServ = "SELECT Id_Servicio FROM laboratorio.Servicio_Tecnico WHERE Nombre = ? AND Activo = 1";
        $stmtCheckServ = sqlsrv_query($conn, $sqlCheckServ, [$nombre_servicio]);
        $existeServ = sqlsrv_fetch_array($stmtCheckServ, SQLSRV_FETCH_ASSOC);
        
        if ($existeServ) {
            $id_servicio = $existeServ['Id_Servicio'];
        } else {
            // Crear servicio nuevo
            $sqlInsServ = "INSERT INTO laboratorio.Servicio_Tecnico (Nombre, Descripcion, Tipo_Muestra, Tipo_Vista, Usuario_Creacion, Activo, Fecha_Creacion) 
                           VALUES (?, ?, 'Agua', 'INTERNO', ?, 1, GETDATE()); SELECT SCOPE_IDENTITY() AS id;";
            $stmtInsServ = sqlsrv_query($conn, $sqlInsServ, [
                $nombre_servicio, 
                "Servicio para análisis de " . $param['Nombre'] . " (mapeo PG: " . $param['Posgre_Nombre'] . ")",
                $usuario_id
            ]);
            if ($stmtInsServ === false) {
                $stats['errores'][] = "Error al crear servicio '$nombre_servicio': " . print_r(sqlsrv_errors(), true);
                continue;
            }
            sqlsrv_next_result($stmtInsServ);
            $rowId = sqlsrv_fetch_array($stmtInsServ, SQLSRV_FETCH_ASSOC);
            $id_servicio = $rowId['id'];
            $stats['servicios_creados']++;
        }
        
        $servicios_ids_lab[] = $id_servicio;
        
        // Actualizar parámetro con el Id_Servicio
        $sqlUpdParam = "UPDATE laboratorio.Parametro_Analisis SET Id_Servicio = ?, Fecha_Modificacion = GETDATE() WHERE Id_Parametro = ?";
        $stmtUpdParam = sqlsrv_query($conn, $sqlUpdParam, [$id_servicio, $param['Id_Parametro']]);
        if ($stmtUpdParam !== false) {
            $stats['parametros_actualizados']++;
        }
        
        // Ligar servicio a equipo 01
        $sqlCheckReq = "SELECT COUNT(1) AS total FROM laboratorio.Requisito_Equipo WHERE Id_Equipo = ? AND Id_Servicio = ? AND Activo = 1";
        $stmtCheckReq = sqlsrv_query($conn, $sqlCheckReq, [$id_equipo, $id_servicio]);
        $rowReq = sqlsrv_fetch_array($stmtCheckReq, SQLSRV_FETCH_ASSOC);
        if (intval($rowReq['total']) === 0) {
            $sqlInsReq = "INSERT INTO laboratorio.Requisito_Equipo (Id_Equipo, Id_Servicio, Es_Bloqueante, Usuario_Creacion, Activo, Fecha_Creacion) 
                          VALUES (?, ?, 0, ?, 1, GETDATE())";
            sqlsrv_query($conn, $sqlInsReq, [$id_equipo, $id_servicio, $usuario_id]);
        }
    }

    // ====================================================================
    // PASO 2B: Crear servicio In-Situ si no existe y asignar parámetros
    // ====================================================================
    $sqlCheckInSitu = "SELECT Id_Servicio FROM laboratorio.Servicio_Tecnico WHERE Nombre = 'In-Situ Pozos' AND Activo = 1";
    $stmtCheckInSitu = sqlsrv_query($conn, $sqlCheckInSitu);
    $rowInSitu = sqlsrv_fetch_array($stmtCheckInSitu, SQLSRV_FETCH_ASSOC);
    
    $id_servicio_insitu = null;
    if ($rowInSitu) {
        $id_servicio_insitu = $rowInSitu['Id_Servicio'];
    } else {
        $sqlInsInSitu = "INSERT INTO laboratorio.Servicio_Tecnico (Nombre, Descripcion, Tipo_Muestra, Tipo_Vista, Usuario_Creacion, Activo, Fecha_Creacion) 
                         VALUES ('In-Situ Pozos', 'Parámetros medidos en campo', 'Agua', 'INTERNO', ?, 1, GETDATE()); SELECT SCOPE_IDENTITY() AS id;";
        $stmtInsInSitu = sqlsrv_query($conn, $sqlInsInSitu, [$usuario_id]);
        if ($stmtInsInSitu !== false) {
            sqlsrv_next_result($stmtInsInSitu);
            $rowId = sqlsrv_fetch_array($stmtInsInSitu, SQLSRV_FETCH_ASSOC);
            $id_servicio_insitu = $rowId['id'];
            $stats['servicios_creados']++;
        }
    }

    // Asignar parámetros in-situ al servicio
    if ($id_servicio_insitu) {
        foreach ($parametros_insitu as $param) {
            if (empty($param['Id_Servicio'])) {
                $sqlUpdParam = "UPDATE laboratorio.Parametro_Analisis SET Id_Servicio = ?, Fecha_Modificacion = GETDATE() WHERE Id_Parametro = ?";
                sqlsrv_query($conn, $sqlUpdParam, [$id_servicio_insitu, $param['Id_Parametro']]);
                $stats['parametros_actualizados']++;
            }
        }
        // Ligar a equipo
        $sqlCheckReq = "SELECT COUNT(1) AS total FROM laboratorio.Requisito_Equipo WHERE Id_Equipo = ? AND Id_Servicio = ? AND Activo = 1";
        $stmtCheckReq = sqlsrv_query($conn, $sqlCheckReq, [$id_equipo, $id_servicio_insitu]);
        $rowReq = sqlsrv_fetch_array($stmtCheckReq, SQLSRV_FETCH_ASSOC);
        if (intval($rowReq['total']) === 0) {
            $sqlInsReq = "INSERT INTO laboratorio.Requisito_Equipo (Id_Equipo, Id_Servicio, Es_Bloqueante, Usuario_Creacion, Activo, Fecha_Creacion) 
                          VALUES (?, ?, 0, ?, 1, GETDATE())";
            sqlsrv_query($conn, $sqlInsReq, [$id_equipo, $id_servicio_insitu, $usuario_id]);
        }
    }

    // ====================================================================
    // PASO 3: Crear paquete "Análisis Completo Pozos" con todos los servicios de lab
    // ====================================================================
    $nombre_paquete = 'Análisis Completo Pozos';
    $sqlCheckPaq = "SELECT Id_Producto FROM laboratorio.Producto_Venta WHERE Nombre_Comercial = ? AND Activo = 1";
    $stmtCheckPaq = sqlsrv_query($conn, $sqlCheckPaq, [$nombre_paquete]);
    $rowPaq = sqlsrv_fetch_array($stmtCheckPaq, SQLSRV_FETCH_ASSOC);
    
    if ($rowPaq) {
        $id_paquete = $rowPaq['Id_Producto'];
    } else {
        $sqlInsPaq = "INSERT INTO laboratorio.Producto_Venta (Nombre_Comercial, Descripcion, Precio_Venta, Tipo, Tipo_Vista, Usuario_Creacion, Activo, Fecha_Creacion) 
                      VALUES (?, 'Paquete completo con todos los análisis de laboratorio para pozos', 0, 'Paquete', 'INTERNO', ?, 1, GETDATE()); 
                      SELECT SCOPE_IDENTITY() AS id;";
        $stmtInsPaq = sqlsrv_query($conn, $sqlInsPaq, [$nombre_paquete, $usuario_id]);
        if ($stmtInsPaq === false) {
            throw new Exception("Error al crear paquete: " . print_r(sqlsrv_errors(), true));
        }
        sqlsrv_next_result($stmtInsPaq);
        $rowId = sqlsrv_fetch_array($stmtInsPaq, SQLSRV_FETCH_ASSOC);
        $id_paquete = $rowId['id'];
    }
    
    $stats['paquete_id'] = $id_paquete;

    // Agregar todos los servicios de lab al paquete
    $servicios_unicos = array_unique($servicios_ids_lab);
    // También agregar In-Situ si existe
    if ($id_servicio_insitu) {
        $servicios_unicos[] = $id_servicio_insitu;
    }
    
    foreach ($servicios_unicos as $id_serv) {
        $sqlCheckPS = "SELECT COUNT(1) AS total FROM laboratorio.Producto_Servicio WHERE Id_Producto = ? AND Id_Servicio = ? AND Activo = 1";
        $stmtCheckPS = sqlsrv_query($conn, $sqlCheckPS, [$id_paquete, $id_serv]);
        $rowPS = sqlsrv_fetch_array($stmtCheckPS, SQLSRV_FETCH_ASSOC);
        if (intval($rowPS['total']) === 0) {
            $sqlInsPS = "INSERT INTO laboratorio.Producto_Servicio (Id_Producto, Id_Servicio, Usuario_Creacion, Activo, Fecha_Creacion) VALUES (?, ?, ?, 1, GETDATE())";
            sqlsrv_query($conn, $sqlInsPS, [$id_paquete, $id_serv, $usuario_id]);
        }
    }

    // ====================================================================
    // PASO 4: Asegurar que existe el cliente CHAVIMOCHIC
    // ====================================================================
    $sqlCliente = "IF NOT EXISTS (SELECT 1 FROM laboratorio.Cliente WHERE Razon_Social = 'CHAVIMOCHIC')
                   BEGIN
                       INSERT INTO laboratorio.Cliente (Razon_Social, RUC, Activo, Usuario_Creacion, Fecha_Creacion) 
                       VALUES ('CHAVIMOCHIC', '20146030971', 1, " . intval($usuario_id) . ", GETDATE());
                   END";
    sqlsrv_query($conn, $sqlCliente);

    echo json_encode([
        'success' => true,
        'message' => 'Configuración de servicios y paquetes completada',
        'stats' => $stats,
        'parametros_lab' => count($parametros_lab),
        'parametros_insitu' => count($parametros_insitu),
        'servicios_lab_ids' => $servicios_unicos,
        'paquete_id' => $id_paquete
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>

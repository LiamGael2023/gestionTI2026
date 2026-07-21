<?php
// Prevenir cualquier output antes de headers
ob_start();

try {
    // Calcular ruta base
    $base_path = dirname(dirname(dirname(__DIR__)));
    
    // Si no hay conexión, cargarla
    if (!isset($conn) || !$conn) {
        require_once $base_path . '/config/db.php';
        require_once $base_path . '/core/Auth.php';
        Auth::check();
        $conn = Conexion::conectar();
    }
    
    require_once __DIR__ . '/../models/PuntoVentaModel.php';
    
    $model = new PuntoVentaModel($conn);
    $action = $_GET['action'] ?? $_POST['action'] ?? 'index';
    
    // ========================================
    // ACCIONES AJAX/JSON
    // ========================================
    
    if ($action == 'buscar_producto') {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        $id = intval($_GET['id'] ?? 0);
        $producto = $model->buscarProducto($id);
        echo json_encode($producto);
        exit;
    }

    if ($action == 'buscar_clientes') {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        $query = $_GET['q'] ?? '';
        error_log('buscar_clientes llamado con query: ' . $query);
        $clientes = $model->buscarClientes($query);
        error_log('Resultados: ' . count($clientes));
        echo json_encode($clientes);
        exit;
    }
    
    if ($action == 'guardar_venta') {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        $data = json_decode(file_get_contents('php://input'), true);
        $result = $model->guardarVenta($data);
        echo json_encode($result);
        exit;
    }

    if ($action == 'crear_cliente_rapido') {
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        $data = json_decode(file_get_contents('php://input'), true);
        $nombre = trim($data['nombre'] ?? '');
        if (empty($nombre)) {
            echo json_encode(['success' => false, 'message' => 'El nombre no puede estar vacío']);
            exit;
        }
        $result = $model->crearClienteRapido($nombre);
        echo json_encode($result);
        exit;
    }

    if ($action == 'buscar_cliente_api') {
        $documento = trim($_GET['documento'] ?? '');
        $html = ($_GET['html'] ?? '') === '1';

        if ($html) {
            while (ob_get_level()) ob_end_clean();
            echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>API Test</title></head><body style="font-family:monospace;padding:20px">';
            echo '<h2>Diagnostico buscar_cliente_api</h2>';
            echo '<b>PHP:</b> ' . phpversion() . '<br>';
            echo '<b>curl:</b> ' . (function_exists('curl_init')?'SI':'NO') . '<br>';
            echo '<b>fopen:</b> ' . ini_get('allow_url_fopen') . '<br>';
            echo '<b>Documento:</b> ' . htmlspecialchars($documento) . '<hr>';

            if (!empty($documento)) {
                echo '<h3>RENIEC</h3>';
                $t1 = microtime(true);
                $ch = curl_init("https://api.apis.net.pe/v1/dni?numero=$documento");
                curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>10, CURLOPT_SSL_VERIFYPEER=>false]);
                $r1 = curl_exec($ch);
                $h1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $e1 = curl_error($ch);
                curl_close($ch);
                $j1 = json_decode($r1, true);
                echo "HTTP: $h1 | Error: " . ($e1?:'ninguno') . ' | Time: ' . round((microtime(true)-$t1)*1000) . 'ms<br>';
                echo '<pre>' . htmlspecialchars(substr($r1?:'(vacio)',0,800)) . '</pre>';
                echo 'nombre: ' . ($j1['nombre'] ?? 'NO') . '<hr>';

                echo '<h3>Personal PECH</h3>';
                $t2 = microtime(true);
                $ch2 = curl_init("https://www.chavimochic.gob.pe/api_incidencias/api_personal.php?documento=$documento");
                curl_setopt_array($ch2, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>10, CURLOPT_SSL_VERIFYPEER=>false]);
                $r2 = curl_exec($ch2);
                $h2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                $e2 = curl_error($ch2);
                curl_close($ch2);
                $j2 = json_decode($r2, true);
                echo "HTTP: $h2 | Error: " . ($e2?:'ninguno') . ' | Time: ' . round((microtime(true)-$t2)*1000) . 'ms<br>';
                echo '<pre>' . htmlspecialchars(substr($r2?:'(vacio)',0,800)) . '</pre>';
                $esEmpleado = ($j2 && !empty($j2['data']));
                echo 'Es empleado: ' . ($esEmpleado ? 'SI' : 'NO') . '<hr>';

                echo '<h3>Model buscarClientePorAPI</h3>';
                $res = $model->buscarClientePorAPI($documento);
                echo '<pre>' . ($res ? htmlspecialchars(json_encode($res, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)) : 'NULL') . '</pre>';
                echo '<b>lastError:</b> ' . htmlspecialchars($model->lastError ?? '') . '<br>';
                
                echo '<h3 style="color:red">Debug paso a paso</h3>';
                echo "1. httpGet RENIEC:<br>";
                $d1 = $model->httpGet("https://api.apis.net.pe/v1/dni?numero=$documento");
                echo '<pre>' . ($d1 ? htmlspecialchars(json_encode($d1, JSON_UNESCAPED_UNICODE)) : 'NULL') . '</pre>';
                echo "nombre RENIEC: " . ($d1['nombre'] ?? 'NO') . "<br>";
                echo "lastError: " . htmlspecialchars($model->lastError ?? '') . "<hr>";
                
                echo "2. Personal PECH (raw):<br>";
                $d2 = $model->httpGet("https://www.chavimochic.gob.pe/api_incidencias/api_personal.php?documento=$documento");
                if ($d2 && !empty($d2['data'])) {
                    $e = $d2['data'][0];
                    echo "nombre: " . htmlspecialchars($e['Trab_Paterno'] . ' ' . $e['Trab_Materno'] . ' ' . $e['Nombres']) . "<br>";
                    echo "es empleado: SI<br>";
                } else {
                    echo "es empleado: NO<br>";
                }
                echo "lastError: " . htmlspecialchars($model->lastError ?? '') . "<hr>";
                
                echo "3. Test registrarClienteAPI (con nombre de PECH):<br>";
                if ($d2 && !empty($d2['data'])) {
                    $e = $d2['data'][0];
                    $nombrePech = trim($e['Trab_Paterno'] . ' ' . $e['Trab_Materno'] . ' ' . $e['Nombres']);
                    $model->lastError = '';
                    $r3 = $model->registrarClienteAPI($documento, $nombrePech, 0, 'TEST');
                    if ($r3) {
                        echo '<pre>' . htmlspecialchars(json_encode($r3, JSON_UNESCAPED_UNICODE)) . '</pre>';
                    } else {
                        echo "<b style='color:red'>FALLO</b><br>";
                        echo "lastError: <b>" . htmlspecialchars($model->lastError ?? 'vacio') . "</b><br>";
                    }
                } else {
                    echo "sin datos de Personal PECH<br>";
                }
            }
            echo '</body></html>';
            exit;
        }

        try {
            ob_clean();
            header('Content-Type: application/json; charset=utf-8');

            if (empty($documento)) {
                echo json_encode(['success' => false, 'message' => 'Documento vacio']);
                exit;
            }
            $result = $model->buscarClientePorAPI($documento);
            $diag = $model->lastError ?? '';
            echo json_encode([
                'success' => true,
                'data' => $result,
                'diag' => $diag,
                'has_curl' => function_exists('curl_init'),
                'has_fopen' => (bool)ini_get('allow_url_fopen'),
            ]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
        exit;
    }
    
    // ========================================
    // VISTA
    // ========================================
    
    ob_clean();
    $clientes = $model->listarClientes();
    $productos = $model->listarProductosVenta();
    $ventasHoy = $model->listarVentasHoy();
    
    include __DIR__ . '/../views/punto_venta/index.php';
    
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

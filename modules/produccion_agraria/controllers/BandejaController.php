<?php
// Prevenir cualquier output antes de headers
ob_start();

// Configurar manejador de errores para AJAX
set_error_handler(function($severity, $message, $file, $line) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $message, 'file' => $file, 'line' => $line]);
    exit;
});

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
    
    require_once __DIR__ . '/../models/BandejaModel.php';
    
    $model = new BandejaModel($conn);
    $action = $_GET['action'] ?? $_POST['action'] ?? 'index';
    
    // ========================================
    // ACCIONES AJAX/JSON
    // ========================================
    
    if ($action == 'obtener_proforma') {
        error_log('[BandejaController] obtener_proforma - ID: ' . ($_GET['id'] ?? 'null'));
        // Limpiar TODO el output buffer acumulado (header.php, etc)
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json; charset=utf-8');
        $id = intval($_GET['id'] ?? 0);
        $proforma = $model->obtenerProforma($id);
        $json = json_encode($proforma ?: ['error' => 'Proforma no encontrada']);
        error_log('[BandejaController] Respuesta: ' . substr($json, 0, 200));
        echo $json;
        exit;
    }
    
    if ($action == 'procesar_proforma') {
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json; charset=utf-8');
        $data = json_decode(file_get_contents('php://input'), true);
        $id = intval($data['id_transaccion'] ?? 0);
        $result = $model->procesarProforma($id, $data);
        echo json_encode($result);
        exit;
    }
    
    if ($action == 'anular_proforma') {
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json; charset=utf-8');
        $data = json_decode(file_get_contents('php://input'), true);
        $id = intval($data['id_transaccion'] ?? 0);
        $motivo = $data['motivo'] ?? '';
        $result = $model->anularProforma($id, $motivo);
        echo json_encode($result);
        exit;
    }
    
    if ($action == 'siguiente_correlativo') {
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json; charset=utf-8');
        $serie = $_GET['serie'] ?? '';
        $siguiente = $model->obtenerSiguienteCorrelativo($serie);
        echo json_encode(['siguiente' => $siguiente]);
        exit;
    }
    
    // ========================================
    // ACCIONES AJAX - VOUCHERS
    // ========================================
    
    // Listar vouchers
    if ($action == 'listar_vouchers') {
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/../models/VoucherModel.php';
        $voucherModel = new VoucherModel($conn);
        $vouchers = $voucherModel->listarVouchers();
        echo json_encode(['success' => true, 'vouchers' => $vouchers]);
        exit;
    }
    
    // Guardar nuevo voucher (con archivo BLOB opcional)
    if ($action == 'guardar_voucher') {
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/../models/VoucherModel.php';
        $voucherModel = new VoucherModel($conn);
        
        $input = file_get_contents('php://input');
        error_log("[BandejaController] Raw input: " . substr($input, 0, 200));
        
        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("[BandejaController] JSON Error: " . json_last_error_msg());
            echo json_encode(['success' => false, 'message' => 'Error JSON: ' . json_last_error_msg()]);
            exit;
        }
        
        // Procesar archivo BLOB si viene en base64
        if (!empty($data['archivo_base64']) && !empty($data['archivo_nombre'])) {
            error_log("[BandejaController] Archivo recibido: " . $data['archivo_nombre']);
            // Decodificar base64 y preparar para SQL Server
            $archivo_binario = base64_decode($data['archivo_base64']);
            if ($archivo_binario !== false) {
                $data['archivo_blob'] = $archivo_binario;
                error_log("[BandejaController] Archivo decodificado: " . strlen($archivo_binario) . " bytes");
            } else {
                error_log("[BandejaController] Error al decodificar base64");
            }
        }
        
        $result = $voucherModel->guardarVoucher($data);
        error_log("[BandejaController] Resultado: " . json_encode($result));
        echo json_encode($result);
        exit;
    }
    
    // Descargar archivo BLOB de voucher
    if ($action == 'descargar_voucher') {
        while (ob_get_level()) { ob_end_clean(); }
        require_once __DIR__ . '/../models/VoucherModel.php';
        $voucherModel = new VoucherModel($conn);
        
        $idVoucher = intval($_GET['id'] ?? 0);
        $archivo = $voucherModel->obtenerArchivoBlob($idVoucher);
        
        if (!$archivo) {
            http_response_code(404);
            echo 'Archivo no encontrado';
            exit;
        }
        
        // Usar nombre original del archivo (guardado en url_imagen)
        $nombreOriginal = $archivo['archivo_nombre'] ?? '';
        if (empty($nombreOriginal)) {
            $nombreOriginal = 'voucher_' . $idVoucher . '.bin';
        }
        
        // Determinar tipo MIME por extensión del nombre original
        $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        $mimeTypes = [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'bmp'  => 'image/bmp',
            'webp' => 'image/webp'
        ];
        $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
        
        // Para PDF e imágenes, mostrar inline (visualizar en navegador); para otros, forzar descarga
        $disposition = ($mimeType === 'application/pdf' || strpos($mimeType, 'image/') === 0) ? 'inline' : 'attachment';
        
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: ' . $disposition . '; filename="' . $nombreOriginal . '"');
        header('Content-Length: ' . strlen($archivo['archivo_blob']));
        header('Cache-Control: public, max-age=0');
        
        // Enviar binario directamente
        echo $archivo['archivo_blob'];
        exit;
    }
    
    // Listar proformas disponibles para asignar
    if ($action == 'listar_proformas_disponibles') {
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/../models/VoucherModel.php';
        $voucherModel = new VoucherModel($conn);
        $proformas = $voucherModel->listarProformasDisponibles();
        echo json_encode(['success' => true, 'proformas' => $proformas]);
        exit;
    }
    
    // Asignar voucher a proformas
    if ($action == 'asignar_voucher_proformas') {
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/../models/VoucherModel.php';
        $voucherModel = new VoucherModel($conn);
        
        $data = json_decode(file_get_contents('php://input'), true);
        $idVoucher = intval($data['id_voucher'] ?? 0);
        $idsTransacciones = $data['ids_transacciones'] ?? [];
        
        $result = $voucherModel->asignarVoucherAProformas($idVoucher, $idsTransacciones);
        echo json_encode($result);
        exit;
    }
    
    // Des-asignar voucher (quitar de todas las proformas vinculadas)
    if ($action == 'desasignar_voucher') {
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/../models/VoucherModel.php';
        $voucherModel = new VoucherModel($conn);
        
        $data = json_decode(file_get_contents('php://input'), true);
        $idVoucher = intval($data['id_voucher'] ?? 0);
        
        $result = $voucherModel->desasignarVoucher($idVoucher);
        echo json_encode($result);
        exit;
    }
    
    // Eliminar voucher completamente
    if ($action == 'eliminar_voucher') {
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/../models/VoucherModel.php';
        $voucherModel = new VoucherModel($conn);
        
        $data = json_decode(file_get_contents('php://input'), true);
        $idVoucher = intval($data['id_voucher'] ?? 0);
        
        $result = $voucherModel->eliminarVoucher($idVoucher);
        echo json_encode($result);
        exit;
    }
    
    // Actualizar voucher (editar monto, num_operacion, fecha)
    if ($action == 'actualizar_voucher') {
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json; charset=utf-8');
        require_once __DIR__ . '/../models/VoucherModel.php';
        $voucherModel = new VoucherModel($conn);
        
        $data = json_decode(file_get_contents('php://input'), true);
        $idVoucher = intval($data['id_voucher'] ?? 0);
        
        $result = $voucherModel->actualizarVoucher($idVoucher, $data);
        echo json_encode($result);
        exit;
    }
    
    // ========================================
    // VISTA
    // ========================================
    
    ob_clean();
    
    // Obtener filtros
    $estado = $_GET['estado'] ?? '';
    $filtros = [
        'fecha_desde' => $_GET['fecha_desde'] ?? '',
        'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
        'cliente' => $_GET['cliente'] ?? ''
    ];
    
    // Manejar opción TODAS
    if ($estado === 'TODAS') {
        $filtros['ver_todas'] = true;
    } elseif (!empty($estado)) {
        $filtros['estado'] = $estado;
    }
    // Si estado está vacío, por defecto se muestran pendientes (no completadas/anuladas)
    
    // Limpiar filtros vacíos
    $filtros = array_filter($filtros);
    
    $proformas = $model->listarProformas($filtros);
    $metodosPago = $model->listarMetodosPago();
    
    include __DIR__ . '/../views/bandeja/index.php';
    
} catch (Throwable $e) {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
    exit;
}

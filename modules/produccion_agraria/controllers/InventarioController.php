<?php
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
    
    require_once __DIR__ . '/../models/InventarioModel.php';
    
    $model = new InventarioModel($conn);
    $action = $_GET['action'] ?? $_POST['action'] ?? 'listar';
    
    // ========================================
    // ACCIONES AJAX/JSON
    // ========================================
    
    if ($action == 'guardar_producto') {
        header('Content-Type: application/json; charset=utf-8');
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        // Procesar imagen base64 si viene
        if (!empty($data['imagen_base64']) && !empty($data['imagen_nombre'])) {
            $archivo_binario = base64_decode($data['imagen_base64']);
            if ($archivo_binario !== false) {
                $data['imagen_blob'] = $archivo_binario;
            }
        }
        
        $result = $model->guardarProducto($data);
        echo json_encode($result);
        exit;
    }
    
    if ($action == 'eliminar_producto') {
        header('Content-Type: application/json; charset=utf-8');
        $id = intval($_POST['id_producto'] ?? 0);
        $result = $model->eliminarProducto($id);
        echo json_encode($result);
        exit;
    }
    
    if ($action == 'obtener_producto') {
        header('Content-Type: application/json; charset=utf-8');
        $id = intval($_GET['id'] ?? 0);
        $producto = $model->obtenerProducto($id);
        echo json_encode($producto);
        exit;
    }
    
    if ($action == 'obtener_lotes') {
        header('Content-Type: application/json; charset=utf-8');
        $id = intval($_GET['id_producto'] ?? 0);
        $lotes = $model->listarLotesPorProducto($id);
        $stockTotal = $model->obtenerStockTotal($id);
        echo json_encode(['lotes' => $lotes, 'stock_total' => $stockTotal]);
        exit;
    }
    
    if ($action == 'obtener_kardex') {
        header('Content-Type: application/json; charset=utf-8');
        $id = intval($_GET['id_producto'] ?? 0);
        $movimientos = $model->listarMovimientosKardex($id);
        echo json_encode(['movimientos' => $movimientos]);
        exit;
    }
    
    if ($action == 'guardar_lote') {
        header('Content-Type: application/json; charset=utf-8');
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $result = $model->guardarLote($data);
        echo json_encode($result);
        exit;
    }
    
    if ($action == 'guardar_merma') {
        header('Content-Type: application/json; charset=utf-8');
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $result = $model->guardarMerma($data);
        echo json_encode($result);
        exit;
    }
    
    if ($action == 'obtener_precio_actual') {
        header('Content-Type: application/json; charset=utf-8');
        $id = intval($_GET['id_producto'] ?? 0);
        $precio = $model->obtenerPrecioActual($id);
        echo json_encode($precio ?: ['precio_oficial' => null]);
        exit;
    }
    
    if ($action == 'guardar_precio') {
        header('Content-Type: application/json; charset=utf-8');
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        $result = $model->guardarPrecio($data);
        echo json_encode($result);
        exit;
    }
    
    if ($action == 'agregar_stock_masivo') {
        while (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: application/json; charset=utf-8');
        $result = $model->agregarStockMasivo();
        echo json_encode($result);
        exit;
    }
    
    if ($action == 'ver_imagen_producto') {
        $id = intval($_GET['id'] ?? 0);
        
        // Intentar método primario
        $archivo = $model->obtenerImagenProducto($id);
        
        // Fallback: si falla, intentar método alternativo
        if (empty($archivo) || empty($archivo['imagen_blob'])) {
            error_log("[ver_imagen_producto] Metodo primario fallo para ID: $id, intentando fallback");
            $archivo = $model->obtenerImagenProductoBase64($id);
        }
        
        // Limpiar cualquier buffer de salida previo para evitar corrupción de la imagen binaria
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        if (empty($archivo) || empty($archivo['imagen_blob'])) {
            error_log("[ver_imagen_producto] Imagen no encontrada para producto ID: $id");
            // Devolver imagen transparente 1x1 en vez de JSON para evitar icono roto en <img>
            header('Content-Type: image/gif');
            header('Content-Length: 43');
            header('Cache-Control: no-store');
            echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
            exit;
        }
        
        $nombreOriginal = $archivo['imagen_nombre'] ?? 'producto_' . $id . '.bin';
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
        $size = strlen($archivo['imagen_blob']);
        error_log("[ver_imagen_producto] Sirviendo imagen '$nombreOriginal' (type: $mimeType, size: $size bytes) para producto ID: $id");
        
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: inline; filename="' . $nombreOriginal . '"');
        header('Content-Length: ' . $size);
        header('Cache-Control: public, max-age=3600');
        echo $archivo['imagen_blob'];
        exit;
    }
    
    // ========================================
    // VISTA
    // ========================================
    
    $productos = $model->listarProductos();
    $clases = $model->listarClasesSelect();
    $centros = $model->listarCentrosSelect();
    $uitActual = $model->obtenerUITActual();
    
    // Cargar vinculaciones clase-centro para filtro en formulario
    $vinculaciones = [];
    $sqlV = "SELECT id_clase, id_centro FROM BD_PRODUCCIONDESARROLLO.dbo.clase_centro ORDER BY id_clase, id_centro";
    $stmtV = sqlsrv_query($conn, $sqlV);
    if ($stmtV) {
        while ($rowV = sqlsrv_fetch_array($stmtV, SQLSRV_FETCH_ASSOC)) {
            $idCentro = (int)$rowV['id_centro'];
            $idClase = (int)$rowV['id_clase'];
            if (!isset($vinculaciones[$idCentro])) {
                $vinculaciones[$idCentro] = [];
            }
            $vinculaciones[$idCentro][] = $idClase;
        }
    }
    
    include __DIR__ . '/../views/inventario/index.php';
    
} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

<?php
/**
 * Response - Gestor estandarizado de respuestas para la arquitectura MVC
 */
class Response {
    /**
     * Devuelve una respuesta JSON limpia cancelando cualquier salida previa de buffers
     */
    public static function json($data, $statusCode = 200) {
        while (ob_get_level()) {
            ob_end_clean();
        }
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Renderiza una vista HTML
     */
    public static function render($viewPath, $data = [], $withLayout = true) {
        extract($data);
        if ($withLayout && file_exists(__DIR__ . '/../public/header.php')) {
            include __DIR__ . '/../public/header.php';
        }
        
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            echo "<div class='container-xl mt-3'><div class='alert alert-danger'>Vista no encontrada: " . htmlspecialchars($viewPath) . "</div></div>";
        }

        if ($withLayout && file_exists(__DIR__ . '/../public/footer.php')) {
            include __DIR__ . '/../public/footer.php';
        }
        exit;
    }

    /**
     * Redirecciona a una URL dada
     */
    public static function redirect($url) {
        while (ob_get_level()) {
            ob_end_clean();
        }
        header("Location: $url");
        exit;
    }

    /**
     * Sirve contenido binario (ej: imágenes, PDFs, vouchers)
     */
    public static function binary($data, $contentType = 'image/jpeg', $filename = null) {
        while (ob_get_level()) {
            ob_end_clean();
        }
        header("Content-Type: $contentType");
        if ($filename) {
            header("Content-Disposition: inline; filename=\"$filename\"");
        } else {
            header("Content-Disposition: inline");
        }
        header("Content-Length: " . strlen($data));
        echo $data;
        exit;
    }
}

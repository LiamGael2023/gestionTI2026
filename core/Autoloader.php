<?php
/**
 * Autoloader - Carga automática de clases para la arquitectura MVC de CHAVIsystems
 */
spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . '/../';

    // Mapeo de directorios clave
    $paths = [
        'core/' . $class . '.php',
        'core/Services/' . $class . '.php',
        'config/' . $class . '.php',
    ];

    foreach ($paths as $path) {
        $fullPath = $baseDir . $path;
        if (file_exists($fullPath)) {
            require_once $fullPath;
            return;
        }
    }
});

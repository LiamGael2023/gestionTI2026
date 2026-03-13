<?php
/**
 * Plantilla de configuración general (sin credenciales)
 * Copiar a config.php y completar valores reales en entorno local/servidor.
 */

// URL base del proyecto (sin barra final)
define('BASE_URL', 'http://localhost/GestionTI');

// Configuración de correo SMTP
define('MAIL_HOST',      'smtp.gmail.com');
define('MAIL_PORT',      587);
define('MAIL_USER',      'usuario@dominio.com');
define('MAIL_PASS',      'REEMPLAZAR_PASSWORD_SMTP');
define('MAIL_FROM',      'usuario@dominio.com');
define('MAIL_FROM_NAME', 'GestionTI - PECH');

// Zona horaria del sistema
date_default_timezone_set('America/Lima');

// Configuración de sesiones
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.gc_maxlifetime', 900);

// Errores (en producción se recomienda desactivar display_errors)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

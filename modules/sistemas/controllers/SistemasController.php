<?php
// 1. Cargar el Modelo correspondiente
require_once 'modules/sistemas/models/SistemasModel.php';

// 2. Instanciar el Modelo
$model = new SistemasModel($conn);

// 3. Determinar la acción a realizar
$action = $_GET['action'] ?? 'index';

// =================================================================================
// PROCESAR ACCIONES
// =================================================================================

switch ($action) {
    case 'guardar':
        $nombre   = strtolower(trim($_POST['nombre']));
        $etiqueta = trim($_POST['etiqueta']);
        $icono    = trim($_POST['icono']);
        $orden    = intval($_POST['orden'] ?? 99);
        $path     = "modules/" . $nombre;

        // Validación: Consultar al Modelo si el nombre ya existe en BD
        // y al Controlador si la carpeta existe físicamente
        if ($model->existeNombre($nombre) || file_exists($path)) {
            echo "<script>alert('Error: El sistema o carpeta \"$nombre\" ya existe.'); window.history.back();</script>";
            exit;
        }

        // Registrar en Base de Datos a través del Modelo
        if ($model->registrarModulo($nombre, $etiqueta, $icono, $orden)) {
            // Si la BD responde bien, el Controlador ejecuta la "Fábrica de Archivos"
            generarEstructuraMVC($nombre, $etiqueta);
            echo "<script>window.location.href='index.php?module=sistemas&msg=success';</script>";
        }
        break;

    case 'actualizar':
        $id       = $_POST['id_modulo'];
        $etiqueta = trim($_POST['etiqueta']);
        $icono    = trim($_POST['icono']);
        $orden    = intval($_POST['orden']);

        if ($model->actualizarModulo($id, $etiqueta, $icono, $orden)) {
            echo "<script>window.location.href='index.php?module=sistemas&msg=updated';</script>";
        }
        break;

    case 'eliminar':
        $id = intval($_GET['id']);
        if ($model->eliminarModulo($id)) {
            echo "<script>window.location.href='index.php?module=sistemas&msg=deleted';</script>";
        }
        break;

    default:
        // Carga de datos inicial para la vista
        $res_modulos = $model->listarModulos();
        include 'modules/sistemas/views/index.php';
        break;
}

// =================================================================================
// FUNCIONES DE APOYO DEL CONTROLADOR (Fábrica de Código)
// =================================================================================

/**
 * Crea físicamente las carpetas y los archivos base bajo el patrón MVC
 */
function generarEstructuraMVC($nombre, $etiqueta) {
    $path = "modules/" . $nombre;
    $className = ucfirst($nombre);

    // Crear directorios
    mkdir($path, 0777, true);
    mkdir($path . "/controllers", 0777, true);
    mkdir($path . "/models", 0777, true);
    mkdir($path . "/views", 0777, true);

    // 1. Plantilla del Modelo
    $model_template = "<?php\nclass {$className}Model {\n    private \$db;\n\n    public function __construct(\$db) {\n        \$this->db = \$db;\n    }\n\n    public function listar() {\n        \$sql = \"SELECT * FROM $nombre WHERE activo = 1\";\n        return sqlsrv_query(\$this->db, \$sql);\n    }\n}";
    file_put_contents($path . "/models/{$className}Model.php", $model_template);

    // 2. Plantilla del Controlador
    $controller_template = "<?php\nrequire_once 'modules/$nombre/models/{$className}Model.php';\n\n\$model = new {$className}Model(\$conn);\n\$action = \$_GET['action'] ?? 'index';\n\nswitch(\$action) {\n    case 'guardar':\n        // Lógica de guardado\n        break;\n    default:\n        include 'modules/$nombre/views/index.php';\n        break;\n}";
    file_put_contents($path . "/controllers/{$className}Controller.php", $controller_template);

    // 3. Plantilla de la Vista
    $view_template = "<div class='page-header'><div class='container-xl'><h2 class='page-title'>$etiqueta</h2></div></div>\n<div class='page-body'><div class='container-xl'><div class='card'><div class='card-body'>Bienvenido al sistema de $etiqueta.</div></div></div></div>";
    file_put_contents($path . "/views/index.php", $view_template);
}
<?php
// 1. Cargamos el Modelo (La lógica de datos)
require_once 'modules/usuarios/models/UsuarioModel.php';

// 2. Instanciamos el modelo y capturamos la acción
$model = new UsuarioModel($conn);
$action = $_GET['action'] ?? 'index';

// 3. El Controlador solo toma decisiones, no escribe SQL
switch ($action) {
    case 'index':
        include 'modules/usuarios/views/index.php';
        break;

    case 'guardar':
        // El controlador valida y organiza la data
        $id_nuevo = $model->guardar($_POST);
        
        if ($id_nuevo) {
            // Actualizamos permisos a través del modelo
            $permisos = $_POST['permisos'] ?? [];
            $model->actualizarPermisos($id_nuevo, $permisos);
            
            echo "<script>window.location.href = 'index.php?module=usuarios&msg=success';</script>";
        } else {
            die("Error al guardar el usuario.");
        }
        break;

    case 'actualizar':
        $id = $_POST['id_usuario'];
        
        // El modelo decide internamente si actualiza con o sin contraseña
        if ($model->guardar($_POST)) {
            // Actualizamos permisos a través del modelo
            $permisos = $_POST['permisos'] ?? [];
            $model->actualizarPermisos($id, $permisos);
            
            echo "<script>window.location.href = 'index.php?module=usuarios&msg=updated';</script>";
        } else {
            die("Error al actualizar el usuario.");
        }
        break;

    default:
        include 'modules/usuarios/views/index.php';
        break;
}
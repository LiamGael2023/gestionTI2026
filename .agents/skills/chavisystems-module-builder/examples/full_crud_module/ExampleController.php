<?php
require_once __DIR__ . "/../../../config/db.php";
require_once __DIR__ . "/ExampleModel.php";

class ExampleController
{
    /**
     * Procesa la creacion de un nuevo registro
     */
    static public function ctrCrear()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Metodo no permitido.']);
            return;
        }

        $codigo      = trim($_POST['codigo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        if ($codigo === '' || $descripcion === '') {
            echo json_encode(['status' => 'error', 'message' => 'Los campos con (*) son obligatorios.']);
            return;
        }

        $datos = [
            'codigo'      => $codigo,
            'descripcion' => $descripcion,
            'created_by'  => (int) ($_SESSION['usuario_id'] ?? 0)
        ];

        $respuesta = ExampleModel::mdlCrear($datos);
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Procesa la edicion de un registro existente
     */
    static public function ctrEditar()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Metodo no permitido.']);
            return;
        }

        $id          = (int) ($_POST['id'] ?? 0);
        $codigo      = trim($_POST['codigo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        if ($id === 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID de registro invalido.']);
            return;
        }

        if ($codigo === '' || $descripcion === '') {
            echo json_encode(['status' => 'error', 'message' => 'Los campos con (*) son obligatorios.']);
            return;
        }

        $datos = [
            'id'          => $id,
            'codigo'      => $codigo,
            'descripcion' => $descripcion
        ];

        $respuesta = ExampleModel::mdlEditar($datos);
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Procesa la eliminacion de un registro
     */
    static public function ctrEliminar()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Metodo no permitido.']);
            return;
        }

        $id = (int) ($_POST['id'] ?? 0);

        if ($id === 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID de registro invalido.']);
            return;
        }

        $respuesta = ExampleModel::mdlEliminar($id);
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Muestra todos los registros
     */
    static public function ctrListar()
    {
        return ExampleModel::mdlListar();
    }

    /**
     * Obtener un registro por ID
     */
    static public function ctrObtenerPorId($id)
    {
        return ExampleModel::mdlObtenerPorId((int)$id);
    }
}

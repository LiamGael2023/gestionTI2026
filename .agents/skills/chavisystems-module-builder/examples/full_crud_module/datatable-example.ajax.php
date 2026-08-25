<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/ExampleController.php";
require_once __DIR__ . "/ExampleModel.php";

class TablaEjemplo
{
    public function mostrarTabla()
    {
        $items = ExampleController::ctrListar();
        $permisos = Auth::permisosModulo('ejemplo');

        $datos = [];
        for ($i = 0; $i < count($items); $i++) {

            $badgeEstado = ($items[$i]["estado"] == 1)
                ? '<span class="badge bg-green-lt">Activo</span>'
                : '<span class="badge bg-red-lt">Inactivo</span>';

            $acciones = '<div class="btn-list flex-nowrap">';

            if ($permisos['pueden_editar'] == 1) {
                $acciones .= '<button class="btn btn-icon btn-outline-primary btnEditar" idItem="' . $items[$i]["id"] . '" title="Editar">
                    <i class="ti ti-edit"></i>
                </button>';
            }

            if ($permisos['pueden_eliminar'] == 1) {
                $acciones .= '<button class="btn btn-icon btn-outline-danger btnEliminar" idItem="' . $items[$i]["id"] . '" title="Eliminar">
                    <i class="ti ti-trash"></i>
                </button>';
            }

            $acciones .= '</div>';

            $datos[] = [
                ($i + 1),
                htmlspecialchars($items[$i]["codigo"], ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($items[$i]["descripcion"], ENT_QUOTES, 'UTF-8'),
                $badgeEstado,
                $acciones
            ];
        }

        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["data" => $datos], JSON_UNESCAPED_UNICODE);
    }
}

$activarTabla = new TablaEjemplo();
$activarTabla->mostrarTabla();

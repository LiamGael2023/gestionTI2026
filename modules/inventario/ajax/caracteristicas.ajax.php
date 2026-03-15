<?php
session_start();
require_once __DIR__ . "/../models/CaracteristicasModel.php";
require_once __DIR__ . "/../controllers/CaracteristicasController.php";

class AjaxCaracteristicas
{
    public $idCaracteristica;

    public function ajaxMostrarEditarCaracteristica()
    {
        $item = "idCaracteristica";
        $valor = (int)$this->idCaracteristica;
        $car = CaracteristicasController::ctrMostrarCaracteristicas($item, $valor);

        if (!$car) {
            echo json_encode(["status" => "error", "message" => "No se encontró el registro"]);
            return;
        }

        $fechaFormateada = "";
        if (!empty($car["fechaCreacion"])) {
            if ($car["fechaCreacion"] instanceof DateTime) {
                $fechaFormateada = $car["fechaCreacion"]->format("d/m/Y H:i:s");
            } else {
                $fechaFormateada = date("d/m/Y H:i:s", strtotime($car["fechaCreacion"]));
            }
        }

        $respuesta = [
            "idCaracteristica"   => intval($car["idCaracteristica"]),
            "idTipoCaracteristica"=> intval($car["idTipoCaracteristica"] ?? 0),
            "valor"              => $car["valor"] ?? "",
            "idUsuarioCreacion"  => $car["idUsuarioCreacion"] ?? "N/A",
            "fechaCreacion"      => $fechaFormateada
        ];

        echo json_encode($respuesta);
    }

    /*=============================================
    AGREGAR CARACTERISTICA
    =============================================*/
    public function ajaxCrearCaracteristica()
    {
        $respuesta = CaracteristicasController::ctrCrearCaracteristica();
        // Si el controlador devuelve array/objeto, lo codificamos; si devuelve string, lo devolvemos tal cual
        if (is_array($respuesta) || is_object($respuesta)) {
            echo json_encode($respuesta);
        } else {
            echo $respuesta;
        }
    }

    public function ajaxEditarCaracteristica()
    {
        $respuesta = CaracteristicasController::ctrEditarCaracteristica();
        // Limpieza de buffer para evitar espacios en blanco antes de la respuesta
        if (ob_get_length()) ob_clean();

        if (is_array($respuesta) || is_object($respuesta)) {
            echo json_encode($respuesta);
        } else {
            echo $respuesta;
        }
    }
}

/* --- DISPARADORES --- */

// 1. CARGAR (Si solo viene el ID para obtener datos y no viene el campo de edición)
if (isset($_POST["idCaracteristica"]) && !isset($_POST["editarValor"])) {
    $mostrar = new AjaxCaracteristicas();
    $mostrar->idCaracteristica = $_POST["idCaracteristica"];
    $mostrar->ajaxMostrarEditarCaracteristica();
}

// 2. EDITAR (Si viene el ID oculto del formulario de edición)
else if (isset($_POST["editarIdCaracteristica"])) {
    $editar = new AjaxCaracteristicas();
    $editar->ajaxEditarCaracteristica();
}

// 3. CREAR (Si viene el nuevo valor de la característica)
else if (isset($_POST["nuevoValor"])) {
    $crear = new AjaxCaracteristicas();
    $crear->ajaxCrearCaracteristica();
}

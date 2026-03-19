<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . "/../models/CaracteristicasModel.php";
require_once __DIR__ . "/../controllers/CaracteristicasController.php";

class AjaxCaracteristicas
{
    public $idCaracteristica;

    private function formatDate($value)
    {
        if (empty($value)) return null;
        if ($value instanceof DateTime) return $value->format("d/m/Y H:i:s");
        $ts = strtotime((string)$value);
        return $ts !== false ? date("d/m/Y H:i:s", $ts) : (string)$value;
    }

    /*=============================================
    CARGAR DATOS PARA MODAL EDITAR
    =============================================*/
    public function ajaxMostrarEditarCaracteristica()
    {
        if (ob_get_length()) ob_clean();

        $item  = "idCaracteristica";
        $valor = (int)$this->idCaracteristica;

        // El modelo ahora devuelve UN solo array asociativo para este item
        $car = CaracteristicasController::ctrMostrarCaracteristicas($item, $valor);

        if (!$car || $car === "error") {
            echo json_encode([
                "resultado" => "error",
                "mensaje"   => "No se encontró el registro.",
                "data"      => null
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $respuesta = [
            "resultado" => "ok",
            "mensaje"   => "Registro obtenido.",
            "data"      => [
                "idCaracteristica"     => intval($car["idCaracteristica"]     ?? 0),
                "idTipoCaracteristica" => intval($car["idTipoCaracteristica"] ?? 0),
                "valor"                => $car["valor"]             ?? "",
                "tipoDescripcion"      => $car["tipoDescripcion"]   ?? null,
                "idUsuarioCreacion"    => $car["idUsuarioCreacion"] ?? null,
                "fechaCreacion"        => $this->formatDate($car["fechaCreacion"]     ?? null),
                "idUsuarioModifica"    => $car["idUsuarioModifica"] ?? null,
                "fechaModificacion"    => $this->formatDate($car["fechaModificacion"] ?? null),
            ]
        ];

        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    }

    /*=============================================
    CREAR
    =============================================*/
    public function ajaxCrearCaracteristica()
    {
        if (ob_get_length()) ob_clean();
        $respuesta = CaracteristicasController::ctrCrearCaracteristica();

        if (is_array($respuesta)) {
            echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(["resultado" => (string)$respuesta], JSON_UNESCAPED_UNICODE);
        }
    }

    /*=============================================
    EDITAR
    =============================================*/
    public function ajaxEditarCaracteristica()
    {
        if (ob_get_length()) ob_clean();
        $respuesta = CaracteristicasController::ctrEditarCaracteristica();

        if (is_array($respuesta)) {
            echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(["resultado" => (string)$respuesta], JSON_UNESCAPED_UNICODE);
        }
    }

    /*=============================================
    ELIMINAR (lógico)
    =============================================*/
    public function ajaxEliminarCaracteristica()
    {
        if (ob_get_length()) ob_clean();
        $respuesta = CaracteristicasController::ctrEliminarCaracteristica();
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    }
}

/* ── DISPARADORES ───────────────────────────────────────────── */

// 1. CARGAR para editar (solo idCaracteristica, sin editarValor)
if (isset($_POST["idCaracteristica"]) && !isset($_POST["editarValor"])) {
    $obj = new AjaxCaracteristicas();
    $obj->idCaracteristica = $_POST["idCaracteristica"];
    $obj->ajaxMostrarEditarCaracteristica();
    exit;
}

// 2. EDITAR
if (isset($_POST["editarIdCaracteristica"])) {
    $obj = new AjaxCaracteristicas();
    $obj->ajaxEditarCaracteristica();
    exit;
}

// 3. CREAR
if (isset($_POST["nuevoValor"])) {
    $obj = new AjaxCaracteristicas();
    $obj->ajaxCrearCaracteristica();
    exit;
}

// 4. ELIMINAR
if (isset($_POST["eliminarIdCaracteristica"])) {
    $obj = new AjaxCaracteristicas();
    $obj->ajaxEliminarCaracteristica();
    exit;
}

echo json_encode(["resultado" => "error", "mensaje" => "Solicitud inválida."]);

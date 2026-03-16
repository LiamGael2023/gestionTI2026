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
        // Si ya es DateTime
        if ($value instanceof DateTime) return $value->format("d/m/Y H:i:s");
        // Intentar parsear
        $ts = strtotime($value);
        if ($ts === false) return (string)$value;
        return date("d/m/Y H:i:s", $ts);
    }

    public function ajaxMostrarEditarCaracteristica()
    {
        // Evitar salidas previas que rompan el JSON
        if (ob_get_length()) ob_clean();

        $item = "idCaracteristica";
        $valor = (int)$this->idCaracteristica;
        $car = CaracteristicasController::ctrMostrarCaracteristicas($item, $valor);

        if (!$car) {
            echo json_encode([
                "resultado" => "error",
                "mensaje"   => "No se encontró el registro",
                "data"      => null
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Función local para formatear fecha aceptando DateTime o string
        $formatDate = function ($value) {
            if ($value === null || $value === '') return null;
            if ($value instanceof DateTime) {
                return $value->format("d/m/Y H:i:s");
            }
            // Si viene como array (SQLSRV puede devolver arrays con DateTime en algunos drivers)
            if (is_array($value) && isset($value['date'])) {
                // estructura tipo ['date' => '2026-03-15 13:41:21', ...]
                return date("d/m/Y H:i:s", strtotime($value['date']));
            }
            // intentar parsear string
            $ts = strtotime((string)$value);
            if ($ts === false) return (string)$value;
            return date("d/m/Y H:i:s", $ts);
        };

        $fechaCreacion = $formatDate($car["fechaCreacion"] ?? ($car->fechaCreacion ?? null));
        $fechaModificacion = $formatDate($car["fechaModificacion"] ?? ($car->fecha_modificacion ?? null));

        $respuesta = [
            "resultado" => "ok",
            "mensaje"   => "Registro obtenido",
            "data" => [
                "idCaracteristica"     => intval($car["idCaracteristica"] ?? $car->id ?? 0),
                "idTipoCaracteristica" => intval($car["idTipoCaracteristica"] ?? $car->idTipoCaracteristica ?? 0),
                "valor"                => $car["valor"] ?? ($car->valor ?? ""),
                "idUsuarioCreacion"    => $car["idUsuarioCreacion"] ?? ($car->idUsuarioRegistro ?? null),
                "fechaCreacion"        => $fechaCreacion,
                // incluir tipoDescripcion que tu modelo ya trae por el JOIN
                "tipoDescripcion"      => $car["tipoDescripcion"] ?? ($car->tipoDescripcion ?? null),
                // si no necesitas mostrar modificación puedes devolverlo igual (null si no existe)
                "idUsuarioModifica"    => $car["idUsuarioModifica"] ?? ($car->idUsuarioModifica ?? null),
                "fechaModificacion"    => $fechaModificacion
            ]
        ];

        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    }



    /*=============================================
    AGREGAR CARACTERISTICA
    =============================================*/
    public function ajaxCrearCaracteristica()
    {
        if (ob_get_length()) ob_clean();
        $respuesta = CaracteristicasController::ctrCrearCaracteristica();
        if (is_array($respuesta) || is_object($respuesta)) {
            echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
        } else {
            // si el controlador devuelve string (ej. 'ok' o 'error'), normalizar
            echo json_encode(["resultado" => (string)$respuesta], JSON_UNESCAPED_UNICODE);
        }
    }

    public function ajaxEditarCaracteristica()
    {
        if (ob_get_length()) ob_clean();
        $respuesta = CaracteristicasController::ctrEditarCaracteristica();
        if (is_array($respuesta) || is_object($respuesta)) {
            echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(["resultado" => (string)$respuesta], JSON_UNESCAPED_UNICODE);
        }
    }
}

/* --- DISPARADORES --- */

// 1. CARGAR (Si solo viene el ID para obtener datos y no viene el campo de edición)
if (isset($_POST["idCaracteristica"]) && !isset($_POST["editarValor"])) {
    $mostrar = new AjaxCaracteristicas();
    $mostrar->idCaracteristica = $_POST["idCaracteristica"];
    $mostrar->ajaxMostrarEditarCaracteristica();
    exit;
}

// 2. EDITAR (Si viene el ID oculto del formulario de edición)
if (isset($_POST["editarIdCaracteristica"])) {
    $editar = new AjaxCaracteristicas();
    $editar->ajaxEditarCaracteristica();
    exit;
}

// 3. CREAR (Si viene el nuevo valor de la característica)
if (isset($_POST["nuevoValor"])) {
    $crear = new AjaxCaracteristicas();
    $crear->ajaxCrearCaracteristica();
    exit;
}

// Si no coincide ninguna ruta, devolver error
echo json_encode(["resultado" => "error", "mensaje" => "Solicitud inválida"]);

<?php
session_start();
require_once __DIR__ . "/../models/TipoCaracteristicasModel.php";
require_once __DIR__ . "/../controllers/TipoCaracteristicasController.php";

class AjaxTipoCaracteristicas
{
    public $idTipo;

    /*=============================================
    MOSTRAR PARA EDITAR
    =============================================*/
    public function ajaxMostrarEditarTipoCaracteristica()
    {
        $item  = "idTipoCaracteristica";
        $valor = (int)$this->idTipo;

        $tipo = TipoCaracteristicasController::ctrMostrarTipoCaracteristicas($item, $valor);

        if (!$tipo) {
            echo json_encode(["status" => "error", "message" => "No se encontró el registro"]);
            return;
        }

        $fechaFormateada = "";
        if (!empty($tipo["fechaCreacion"])) {
            if ($tipo["fechaCreacion"] instanceof DateTime) {
                $fechaFormateada = $tipo["fechaCreacion"]->format("d/m/Y");
            } else {
                $fechaFormateada = date("d/m/Y", strtotime($tipo["fechaCreacion"]));
            }
        }

        $respuesta = [
            "idTipoCaracteristica" => intval($tipo["idTipoCaracteristica"]),
            "descripcion"          => $tipo["descripcion"] ?? "",
            "idUsuarioRegistro"    => $tipo["idUsuarioRegistro"] ?? "N/A",
            "fechaCreacion"        => $fechaFormateada
        ];

        echo json_encode($respuesta);
    }

    /*=============================================
    CREAR
    =============================================*/
    public function ajaxCrearTipoCaracteristica()
    {
        $respuesta = TipoCaracteristicasController::ctrCrearTipoCaracteristica();
        echo json_encode($respuesta);
    }

    /*=============================================
    EDITAR
    =============================================*/
    public function ajaxEditarTipoCaracteristica()
    {
        $respuesta = TipoCaracteristicasController::ctrEditarTipoCaracteristica();
        if (ob_get_length()) ob_clean();
        echo json_encode($respuesta);
    }

    /*=============================================
    ELIMINAR (lógico)
    =============================================*/
    public function ajaxEliminarTipoCaracteristica()
    {
        $respuesta = TipoCaracteristicasController::ctrEliminarTipoCaracteristica();
        echo json_encode($respuesta);
    }
}

/* ── DISPARADORES ───────────────────────────────────────────── */

// 1. CARGAR datos para modal editar
if (isset($_POST["idTipoCaracteristica"]) && !isset($_POST["editarDescripcion"])) {
    $obj        = new AjaxTipoCaracteristicas();
    $obj->idTipo = $_POST["idTipoCaracteristica"];
    $obj->ajaxMostrarEditarTipoCaracteristica();
}

// 2. EDITAR
elseif (isset($_POST["editarIdTipoCaracteristica"])) {
    $obj = new AjaxTipoCaracteristicas();
    $obj->ajaxEditarTipoCaracteristica();
}

// 3. CREAR
elseif (isset($_POST["nuevaDescripcion"])) {
    $obj = new AjaxTipoCaracteristicas();
    $obj->ajaxCrearTipoCaracteristica();
}

// 4. ELIMINAR
elseif (isset($_POST["eliminarIdTipoCaracteristica"])) {
    $obj = new AjaxTipoCaracteristicas();
    $obj->ajaxEliminarTipoCaracteristica();
}

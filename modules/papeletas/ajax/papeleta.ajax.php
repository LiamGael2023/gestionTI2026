<?php


error_log(print_r($_POST, true));

require_once __DIR__ . "/../controllers/PapeletasController.php";
require_once __DIR__ . "/../models/PapeletasModel.php";

header('Content-Type: application/json; charset=utf-8');

class AjaxPapeleta
{

    public function ajaxCrearPapeleta()
    {
        $respuesta = ControladorPapeleta::ctrCrearPapeleta();
        echo $respuesta;
    }
    public function ajaxActualizarEstado()
    {
        $id_papeleta = $_POST["id_papeleta"];
        $campo = $_POST["campo"];
        $valor_actual = isset($_POST["valor_actual"]) ? (int) $_POST["valor_actual"] : null;

        if (!in_array($campo, ['estadoJI', 'estadoJP'])) {
            echo json_encode(["status" => "error", "message" => "Campo no permitido"]);
            return;
        }

        if ($valor_actual === null) {
            echo json_encode(["status" => "error", "message" => "Valor actual no recibido"]);
            return;
        }

        $respuesta = ControladorPapeleta::ctrActualizarEstadoPapeleta($id_papeleta, $campo, $valor_actual);

        if ($respuesta["status"] === "success") {
            echo json_encode([
                "status" => "success",
                "message" => "Estado actualizado correctamente",
                "valor" => $respuesta["valor"]
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => $respuesta["message"]
            ]);
        }
    }

    /* ✅ Nuevo método para mostrar evidencias */
    public function ajaxMostrarEvidencias()
    {
        $id_papeleta = $_POST["id_papeleta"];
        $respuesta = ControladorPapeleta::ctrMostrarEvidenciasPapeleta($id_papeleta);

        if ($respuesta && count($respuesta) > 0) {
            echo json_encode(["status" => "success", "data" => $respuesta]);
        } else {
            echo json_encode(["status" => "empty", "data" => []]);
        }
        exit;
    }



    public function ajaxSubirEvidencias()
    {
        if (!isset($_POST['id_papeleta_modal']) || !isset($_FILES['evidencia'])) {
            echo json_encode(["status" => "error", "message" => "ID de papeleta o archivos no recibidos"]);
            return;
        }

        $respuesta = ControladorPapeleta::ctrSubirEvidencias($_POST, $_FILES);
        echo json_encode($respuesta);
        exit;
    }




    /* ✅ Nuevo método para mostrar la información completa de la papeleta */
    public function ajaxMostrarPapeletaDetalle()
    {
        if (!isset($_POST["id_papeleta"]) || empty($_POST["id_papeleta"])) {
            echo json_encode(["status" => "error", "message" => "ID de papeleta no recibido"]);
            exit;
        }

        $id_papeleta = $_POST["id_papeleta"];
        $respuesta   = ControladorPapeleta::ctrMostrarPapeletaReporte('Id_papeleta', $id_papeleta);

        if ($respuesta === false) {
            echo json_encode(["status" => "error", "message" => "Error al consultar la papeleta"]);
            exit;
        }

        if (!empty($respuesta)) {
            // 🔹 Convertimos a UTF-8 SOLO si hace falta
            array_walk_recursive($respuesta[0], function (&$item) {
                if (is_string($item) && !mb_detect_encoding($item, 'UTF-8', true)) {
                    $item = mb_convert_encoding($item, 'UTF-8', 'ISO-8859-1');
                }
            });

            $json = json_encode([
                "status" => "success",
                "data"   => $respuesta[0]
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($json === false) {
                error_log("❌ Error al json_encode: " . json_last_error_msg());
                echo json_encode([
                    "status"  => "error",
                    "message" => "Error al convertir a JSON: " . json_last_error_msg()
                ]);
            } else {
                echo $json;
            }
        } else {
            echo json_encode(["status" => "empty", "data" => []]);
        }

        exit;
    }

    public function ajaxEliminarEvidencia()
    {
        if (!isset($_POST["id_evidencia"])) {
            echo json_encode(["status" => "error", "message" => "ID de evidencia no recibido"]);
            return;
        }

        $id_evidencia = intval($_POST["id_evidencia"]);
        $respuesta = ControladorPapeleta::ctrEliminarEvidencia($id_evidencia);

        if ($respuesta) {
            echo json_encode(["status" => "success", "message" => "Evidencia eliminada correctamente"]);
        } else {
            echo json_encode(["status" => "error", "message" => "No se pudo eliminar la evidencia"]);
        }
    }

    public function ajaxActualizarJefeInmediato()
    {
        if (!isset($_POST["id_papeleta"])) {
            echo json_encode(["status" => "error", "message" => "ID de papeleta no recibido"]);
            return;
        }

        $id_papeleta = intval($_POST["id_papeleta"]);
        $cod_jefe = isset($_POST["jefe_papeleta"]) ? $_POST["jefe_papeleta"] : null;
        $respuesta = ControladorPapeleta::ctrActualizarJefeInmediato($id_papeleta, $cod_jefe);

        if ($respuesta) {
            echo json_encode(["status" => "success", "message" => "Jefe Inmediato actualizado correctamente"]);
        } else {
            echo json_encode(["status" => "error", "message" => "No se pudo actualizar Jefe Inmediato   "]);
        }
    }


    public function ajaxMarcarNoAutorizado()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!isset($_POST["id_papeleta"])) {
            echo json_encode(["status" => "error", "message" => "ID de papeleta no recibido"]);
            return;
        }

        $id_papeleta = intval($_POST["id_papeleta"]);
        $respuesta = ControladorPapeleta::ctrNoAutorizado($id_papeleta);

        echo json_encode($respuesta);
    }


    public function ajaxMarcarSalida()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!isset($_POST["id_papeleta"])) {
            echo json_encode(["status" => "error", "message" => "ID de papeleta no recibido"]);
            return;
        }

        $id_papeleta = intval($_POST["id_papeleta"]);
        $respuesta = ControladorPapeleta::ctrMarcarSalida($id_papeleta);

        echo json_encode($respuesta);
    }


    public function ajaxMarcarRetorno()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!isset($_POST["id_papeleta"])) {
            echo json_encode(["status" => "error", "message" => "ID de papeleta no recibido"]);
            return;
        }

        $id_papeleta = intval($_POST["id_papeleta"]);
        $respuesta = ControladorPapeleta::ctrMarcarRetorno($id_papeleta);

        echo json_encode($respuesta);
    }


    public function ajaxConsultarJefesUnidad()
    {
        $q = isset($_GET['q']) ? trim($_GET['q']) : null;
        $respuesta = ControladorPapeleta::ctrMostrarJefeUnidad();

        $data = [];
        foreach ($respuesta as $row) {


            $nombreCompleto = utf8_encode(($row["trabajador"]));
            $oficina = utf8_encode($row["oficina"]);
            $fotocheck = utf8_encode($row["fotocheck"]);


            if (!$q || stripos($nombreCompleto, $q) !== false) {
                $data[] = [
                    "id" => $row["Id_Trabajador"],
                    "text" => $nombreCompleto,
                    "oficina" => $oficina,
                    "foto" => "../fotosIndividuales/" . $fotocheck . ".jpg"


                ];
            }
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function ajaxTienePapeletaPendiente()
    {

        $respuesta = ControladorPapeleta::ctrTienePapeletaPendiente();

        if ($respuesta["status"] === "success") {
            // Devuelve solo 1 o 0
            $tiene = $respuesta["data"][0]["TienePendiente"] ?? 0;
            echo json_encode(["status" => "success", "tienePendiente" => $tiene]);
        } else {
            echo json_encode(["status" => "error", "tienePendiente" => 0, "message" => $respuesta["message"]]);
        }
    }
}


if (isset($_POST["accion"]) && $_POST["accion"] == "intercambiar_estado") {

    $id_papeleta = $_POST["id_papeleta"];
    $campo = $_POST["campo"];
    $id_jefe = $_POST["id_jefe"];

    $respuesta = ControladorPapeleta::ctrIntercambiarEstado($id_papeleta, $campo, $id_jefe);

    echo json_encode($respuesta);
}

/* Rutas de entrada */
if (isset($_POST["concepto"])) {
    $crear = new AjaxPapeleta();
    $crear->ajaxCrearPapeleta();
}

// ✅ Nueva ruta para obtener lista de jefes (usada por Select2)
if (isset($_GET["action"]) && $_GET["action"] === "getJefes") {
    $ajax = new AjaxPapeleta();
    $ajax->ajaxConsultarJefesUnidad();
    exit;
}
if (isset($_POST["accion"]) && $_POST["accion"] === "tiene_papeleta_pendiente") {
    $ajax = new AjaxPapeleta();
    $ajax->ajaxTienePapeletaPendiente();
    exit;
}


if (isset($_POST["action"]) && $_POST["action"] === "anularPapeleta") {

    $id = $_POST["id_papeleta"];
    $respuesta = ControladorPapeleta::ctrAnularPapeleta($id);

    if ($respuesta) {
        echo json_encode([
            "status" => "ok",
            "message" => "La papeleta fue marcada como ANULADA."
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "No se pudo anular la papeleta."
        ]);
        exit;
    }
    exit; // 👈 CRÍTICO

}

if (isset($_POST["accion"]) && $_POST["accion"] === "mostrar_detalle" && !empty($_POST["id_papeleta"])) {
    $detalle = new AjaxPapeleta();
    $detalle->ajaxMostrarPapeletaDetalle();
    exit;
}
if (isset($_POST["accion"]) && $_POST["accion"] === "eliminar_evidencia") {
    $eliminar = new AjaxPapeleta();
    $eliminar->ajaxEliminarEvidencia();
    exit;
}


if (isset($_POST["accion"]) && $_POST["accion"] === "marcar_salida") {
    $eliminar = new AjaxPapeleta();
    $eliminar->ajaxMarcarSalida();
    exit;
}
if (isset($_POST["accion"]) && $_POST["accion"] === "marcar_retorno") {
    $eliminar = new AjaxPapeleta();
    $eliminar->ajaxMarcarRetorno();
    exit;
}

if (isset($_POST["accion"]) && $_POST["accion"] === "actualizar_jefe") {
    $actualizar = new AjaxPapeleta();
    $actualizar->ajaxActualizarJefeInmediato();
    exit;
}

if (isset($_POST["accion"]) && $_POST["accion"] === "no_autorizado") {
    $actualizar = new AjaxPapeleta();
    $actualizar->ajaxMarcarNoAutorizado();
    exit;
}

if (isset($_POST["accion"]) && $_POST["accion"] === "actualizarEstado") {
    $actualizar = new AjaxPapeleta();
    $actualizar->ajaxActualizarEstado();
}

if (isset($_POST["accion"]) && $_POST["accion"] === "mostrar" && !empty($_POST["id_papeleta"])) {
    $mostrar = new AjaxPapeleta();
    $mostrar->ajaxMostrarEvidencias();
}
if (isset($_POST["accion"]) && $_POST["accion"] === "subir_evidencias") {
    $insertar = new AjaxPapeleta();
    $insertar->ajaxSubirEvidencias();
}

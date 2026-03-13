<?php
error_log(print_r($_POST, true));


require_once __DIR__ . "/../controllers/ColaboradorController.php";
require_once __DIR__ . "/../models/ColaboradorModel.php";

header('Content-Type: application/json; charset=utf-8');

class AjaxColaborador
{

    public function ajaxConsultarTrabajadoresActivos()
    {
        $q = isset($_GET['q']) ? trim($_GET['q']) : null;
        $respuesta = ControladorColaborador::ctrMostrarTrabajadoresActivos();

        $data = [];
        foreach ($respuesta as $row) {


            $nombreCompleto = utf8_encode($row["Trabajador_apellidos"]) . " " . utf8_encode($row["Trabajador_nombres"]);
            $oficina = utf8_encode($row["gerencia"]);
            $fotocheck = utf8_encode($row["Trab_Fotocheck"]);


            if (!$q || stripos($nombreCompleto, $q) !== false) {
                $data[] = [
                    "id" => $row["id_trabajador"],
                    "text" => $nombreCompleto,
                    "oficina" => $oficina,
                    "foto" => "../gestionti/public/fotos-trabajador/" . $fotocheck . ".jpg"
                ];
            }
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}


// ✅ Nueva ruta para obtener lista de jefes (usada por Select2)
if (isset($_GET["action"]) && $_GET["action"] === "getTrabajadoresActivos") {
    $ajax = new AjaxColaborador();
    $ajax->ajaxConsultarTrabajadoresActivos();
    exit;
}

<?php
error_log(print_r($_POST, true));

require_once __DIR__ . "/../controllers/InicioController.php";

class AjaxPlanilla
{
    // ✅ AÑOS
    static public function ajaxConsultarAniosBoletas()
    {
        $respuesta = InicioController::ctrConsultarAniosBoletas();
        echo json_encode($respuesta);
    }

    // ✅ LISTAR BOLETAS POR AÑO
    static public function ajaxListarBoletasPorAnio()
    {
        $anio = isset($_POST["anio"]) ? trim($_POST["anio"]) : null;

        $respuesta = InicioController::ctrListarBoletasPorAnio($anio);

        // Si hubo error, devolvemos tal cual
        if ($respuesta["status"] !== "success") {
            echo json_encode($respuesta);
            return;
        }

        $data = $respuesta["data"];
        $dataUTF = [];

        foreach ($data as $fila) {

            $nuevaFila = [];
            foreach ($fila as $key => $value) {

                // ✅ Si es numérico o null, no necesita conversión
                if (is_numeric($value) || $value === null) {
                    $nuevaFila[$key] = $value;
                } else {
                    // ✅ Convertir texto correctamente
                    $nuevaFila[$key] = ($value);
                }
            }

            $dataUTF[] = $nuevaFila;
        }

        // ✅ Devolver JSON limpio
        echo json_encode([
            "status" => "success",
            "data" => $dataUTF
        ]);
    }
    static public function ajaxActualizarDescargadoBoleta()
    {
        $anio = isset($_POST["anio"]) ? trim($_POST["anio"]) : null;
        $mes = isset($_POST["mes"]) ? trim($_POST["mes"]) : null;
        $planilla_auxiliar = isset($_POST["planilla_auxiliar"]) ? trim($_POST["planilla_auxiliar"]) : null;

        // ✅ Validaciones básicas
        if (!$anio || !$mes || !$planilla_auxiliar) {
            echo json_encode([
                "status" => "error",
                "message" => "Faltan parámetros obligatorios"
            ]);
            return;
        }

        // ✅ Orden correcto: trabajador, año, mes, planilla
        $respuesta = InicioController::ctrActualizarDescargadoBoleta(
            $mes,
            $anio,
            $planilla_auxiliar
        );

        echo json_encode($respuesta);
    }



    static public function ajaxConsultarAniosBoletasPorTrabajador()
    {
        $id_trabajador = isset($_POST["id_Trabajador"]) ? trim($_POST["id_Trabajador"]) : null;

        $respuesta = InicioController::ctrConsultarAniosBoletasPorTrabajador($id_trabajador);
        echo json_encode($respuesta);
    }

    static public function ajaxListarBoletasPorAnioPorTrabajador()
    {
        $anio = isset($_POST["anio"]) ? trim($_POST["anio"]) : null;
        $id_trabajador = isset($_POST["id_Trabajador"]) ? trim($_POST["id_Trabajador"]) : null;

        $respuesta = InicioController::ctrListarBoletasPorAnioPorTrabajador($anio, $id_trabajador);

        // Si hubo error, devolvemos tal cual
        if ($respuesta["status"] !== "success") {
            echo json_encode($respuesta);
            return;
        }

        $data = $respuesta["data"];
        $dataUTF = [];

        foreach ($data as $fila) {

            $nuevaFila = [];
            foreach ($fila as $key => $value) {

                // ✅ Si es numérico o null, no necesita conversión
                if (is_numeric($value) || $value === null) {
                    $nuevaFila[$key] = $value;
                } else {
                    // ✅ Convertir texto correctamente
                    $nuevaFila[$key] = ($value);
                }
            }

            $dataUTF[] = $nuevaFila;
        }

        // ✅ Devolver JSON limpio
        echo json_encode([
            "status" => "success",
            "data" => $dataUTF
        ]);
    }
}

// ✅ RUTAS
if (isset($_POST["accion"])) {

    if ($_POST["accion"] === "consultarAniosBoletas") {
        AjaxPlanilla::ajaxConsultarAniosBoletas();
    }

    if ($_POST["accion"] === "listarBoletasPorAnio") {
        AjaxPlanilla::ajaxListarBoletasPorAnio();
    }


    

    
    if ($_POST["accion"] === "consultarAniosBoletasPorTrabajador") {
        AjaxPlanilla::ajaxConsultarAniosBoletasPorTrabajador();
    }

    if ($_POST["accion"] === "listarBoletasPorAnioPorTrabajador") {
        AjaxPlanilla::ajaxListarBoletasPorAnioPorTrabajador();
    }

    if ($_POST["accion"] === "actualizar-descargado") {
        AjaxPlanilla::ajaxActualizarDescargadoBoleta();
    }
}

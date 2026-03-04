<?php

require_once __DIR__ . '/../../../config/db2.php';
require_once __DIR__ . '/../models/InicioModel.php';

/* ======================================================
   DETECTAR SI ES PETICIÓN AJAX
====================================================== */
$isAjax = (
    !empty($_POST['accion']) ||
    (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
     strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
);

/* ======================================================
   CONEXIÓN Y MODELO (para vistas)
====================================================== */
$conn  = Conexion2::conectar();
$model = new InicioModel($conn);

/* ======================================================
   ROUTER DE VISTAS (SOLO SI NO ES AJAX)
====================================================== */
if (!$isAjax) {

    $action = $_GET['action'] ?? 'index';

    switch ($action) {
        case 'guardar':
            // lógica si aplica
            break;

        default:
            require_once __DIR__ . '/../views/index.php';
            break;
    }
}

/* ======================================================
   CONTROLLER (SOLO MÉTODOS PARA AJAX)
====================================================== */
class InicioController
{
    public static function ctrConsultarAniosBoletas()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return InicioModel::mdlConsultarAniosBoletas([
            'id_trabajador' => $_SESSION['id_Trabajador'] ?? null
        ]);
    }

    public static function ctrListarBoletasPorAnio($anio)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return InicioModel::mdlListarBoletasPorAnio([
            'id_trabajador' => $_SESSION['id_Trabajador'] ?? null,
            'anio'          => $anio
        ]);
    }

    public static function ctrActualizarDescargadoBoleta($mes, $anio, $planilla)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return InicioModel::mdlActualizarDescargadoBoleta([
            'id_trabajador' => $_SESSION['id_Trabajador'] ?? null,
            'anio'          => $anio,
            'mes'           => $mes,
            'planilla'      => $planilla
        ]);
    }

    public static function ctrConsultarAniosBoletasPorTrabajador($id_trabajador)
    {
        return InicioModel::mdlConsultarAniosBoletas([
            'id_trabajador' => $id_trabajador
        ]);
    }

    public static function ctrListarBoletasPorAnioPorTrabajador($anio, $id_trabajador)
    {
        return InicioModel::mdlListarBoletasPorAnio([
            'id_trabajador' => $id_trabajador,
            'anio'          => $anio
        ]);
    }
}

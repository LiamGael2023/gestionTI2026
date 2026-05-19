<?php
require_once __DIR__ . "/../models/AsignacionModel.php";

class AsignacionController
{
    static public function ctrCrearAsignacion()
    {
        if (!isset($_POST["nuevoIdEstacion"])) return null;
        $datos = [
            'idEstacion'               => intval($_POST['nuevoIdEstacion']),
            'idAmbiente'               => !empty($_POST['nuevoIdAmbiente']) ? intval($_POST['nuevoIdAmbiente']) : null,
            'dniTrabajadorResponsable' => trim($_POST['nuevoDniResponsable']    ?? ''),
            'trabajadorResponsable'    => trim($_POST['nuevoTrabajadorResponsable'] ?? ''),
            'trabajadorAsignado'       => trim($_POST['nuevoTrabajadorAsignado']    ?? '') ?: null,
            'fechaAsignacion'          => $_POST['nuevoFechaAsignacion']        ?? date('Y-m-d'),
            'motivoCambio'             => trim($_POST['nuevoMotivoCambio']       ?? '') ?: null,
            'observaciones'            => trim($_POST['nuevoObservaciones']      ?? '') ?: null,
            'idUsuario'                => $_SESSION['usuario_id'],
        ];
        return AsignacionModel::mdlCrearAsignacion($datos);
    }

    static public function ctrLiberarAsignacion()
    {
        if (!isset($_POST['idAsignacion'])) return null;
        $datos = [
            'idAsignacion'   => intval($_POST['idAsignacion']),
            'fechaLiberacion'=> $_POST['fechaLiberacion'] ?? date('Y-m-d'),
            'motivoCambio'   => trim($_POST['motivoCambio'] ?? '') ?: 'Liberación',
            'idUsuario'      => $_SESSION['usuario_id'],
        ];
        return AsignacionModel::mdlLiberarAsignacion($datos);
    }

    static public function ctrListarActivas()
    {
        return AsignacionModel::mdlListarActivas();
    }

    static public function ctrHistorialEstacion(int $idEstacion)
    {
        return AsignacionModel::mdlHistorialEstacion($idEstacion);
    }

    static public function ctrAsignacionActiva(int $idEstacion)
    {
        return AsignacionModel::mdlAsignacionActiva($idEstacion);
    }

    static public function ctrListarAmbientes()
    {
        return AsignacionModel::mdlListarAmbientes();
    }

    static public function ctrEstacionesSinAsignacion()
    {
        return AsignacionModel::mdlEstacionesSinAsignacion();
    }

    static public function ctrEquiposEstacion(int $idEstacion)
    {
        return AsignacionModel::mdlEquiposEstacion($idEstacion);
    }
}

<?php
require_once __DIR__ . "/../models/EstacionModel.php";

class EstacionController
{
    static public function ctrCrearEstacion()
    {
        if (!isset($_POST["nuevoNombreEstacion"])) return null;
        $datos = [
            "nombreEstacion"    => mb_strtoupper(trim($_POST["nuevoNombreEstacion"]), "UTF-8"),
            "idIp"              => !empty($_POST["nuevoIdIp"]) ? intval($_POST["nuevoIdIp"]) : null,
            "codigoAnydesk"     => trim($_POST["nuevoCodigoAnydesk"]     ?? ''),
            "contrasenaAnydesk" => trim($_POST["nuevoContrasenaAnydesk"] ?? ''),
            "principalId"       => trim($_POST["nuevoEquipoPrincipalId"] ?? ''),
            "perifericosIds"    => trim($_POST["nuevoPerifericosIds"]     ?? ''),
            "softwareIds"       => trim($_POST["nuevoSoftwareIds"]        ?? ''),
            "idUsuario"         => $_SESSION["usuario_id"],
        ];
        return EstacionModel::mdlCrearEstacion($datos);
    }

    static public function ctrEditarEstacion()
    {
        if (!isset($_POST["editarNombreEstacion"])) return null;
        if (empty($_POST["editarIdEstacion"]))
            return ["resultado" => "error", "mensaje" => "ID de estación no recibido."];
        $datos = [
            "idEstacion"        => intval($_POST["editarIdEstacion"]),
            "nombreEstacion"    => mb_strtoupper(trim($_POST["editarNombreEstacion"]), "UTF-8"),
            "idIp"              => !empty($_POST["editarIdIp"]) ? intval($_POST["editarIdIp"]) : null,
            "codigoAnydesk"     => trim($_POST["editarCodigoAnydesk"]     ?? ''),
            "contrasenaAnydesk" => trim($_POST["editarContrasenaAnydesk"] ?? ''),
            "principalId"       => trim($_POST["editarEquipoPrincipalId"] ?? ''),
            "perifericosIds"    => trim($_POST["editarPerifericosIds"]     ?? ''),
            "softwareIds"       => trim($_POST["editarSoftwareIds"]        ?? ''),
            "idUsuario"         => $_SESSION["usuario_id"],
        ];
        return EstacionModel::mdlEditarEstacion($datos);
    }

    static public function ctrMostrarEstacion($item, $valor)
    {
        return EstacionModel::mdlMostrarEstacion($item, $valor);
    }

    static public function ctrEliminarEstacion()
    {
        if (empty($_POST["eliminarIdEstacion"]))
            return ["resultado" => "error", "mensaje" => "ID no recibido."];
        $datos = [
            "idEstacion"        => intval($_POST["eliminarIdEstacion"]),
            "idUsuarioModifica" => intval($_SESSION["usuario_id"]),
        ];
        return EstacionModel::mdlEliminarEstacion($datos);
    }

    static public function ctrEquiposDeEstacionAgrupados(int $idEstacion)
    {
        return EstacionModel::mdlEquiposDeEstacionAgrupados($idEstacion);
    }

    static public function ctrVerDetalle(int $idEstacion)
    {
        return EstacionModel::mdlVerDetalle($idEstacion);
    }

    static public function ctrListarEquiposTipo(string $tipo, int $idEstacion, array $excluir = [])
    {
        return EstacionModel::mdlListarEquiposTipo($tipo, $idEstacion, $excluir);
    }

    static public function ctrListarIps(int $idEstacion = 0)
    {
        return EstacionModel::mdlListarIps($idEstacion);
    }

    static public function ctrCrearTerminal()
    {
        if (!isset($_POST['terminalNombre'])) return null;
        $datos = [
            'nombreEstacion' => mb_strtoupper(trim($_POST['terminalNombre'] ?? ''), "UTF-8"),
            'idEquipo'       => intval($_POST['terminalIdEquipo'] ?? 0),
            'idUsuario'      => $_SESSION['usuario_id'],
        ];
        return EstacionModel::mdlCrearTerminal($datos);
    }

    static public function ctrEquiposDisponibles()
    {
        return EstacionModel::mdlEquiposDisponibles();
    }
}

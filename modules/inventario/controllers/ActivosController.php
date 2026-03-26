<?php
require_once __DIR__ . "/../models/ActivosModel.php";

class ActivosController
{
    /*=============================================
    CREAR ACTIVO
    =============================================*/
    static public function ctrCrearActivo()
    {
        if (!isset($_POST["nuevoIdTipoActivo"])) return null;
        $datos = [
            "idActivo"            => null,
            "idTipoActivo"        => intval($_POST["nuevoIdTipoActivo"]),
            "idActivoPadre"       => null,
            "codigoPatrimonial"   => mb_strtoupper(trim($_POST["nuevoCodigoPatrimonial"]   ?? ''), "UTF-8"),
            "codigoLicencia"      => mb_strtoupper(trim($_POST["nuevoCodigoLicencia"]       ?? ''), "UTF-8"),
            "numeroSerie"         => mb_strtoupper(trim($_POST["nuevoNumeroSerie"]          ?? ''), "UTF-8"),
            "fechaAdquisicion"    => !empty($_POST["nuevoFechaAdquisicion"])    ? $_POST["nuevoFechaAdquisicion"]    : null,
            "fechaInicioGarantia" => !empty($_POST["nuevoFechaInicioGarantia"]) ? $_POST["nuevoFechaInicioGarantia"] : null,
            "fechaFinGarantia"    => !empty($_POST["nuevoFechaFinGarantia"])    ? $_POST["nuevoFechaFinGarantia"]    : null,
            "estado"              => trim($_POST["nuevoEstado"]                 ?? 'disponible'),
            "idCaracteristicas"   => trim($_POST["nuevoCaracteristicasIds"]     ?? ''),
            "idUsuario"           => $_SESSION["usuario_id"],
        ];
        return ActivosModel::mdlCrearActivo($datos);
    }

    /*=============================================
    EDITAR ACTIVO
    =============================================*/
    static public function ctrEditarActivo()
    {
        if (!isset($_POST["editarIdTipoActivo"])) return null;
        if (empty($_POST["editarIdActivo"]))
            return ["resultado" => "error", "mensaje" => "ID de activo no recibido."];
        $activoActual = ActivosModel::mdlMostrarActivo('inventario.activo', 'idActivo', intval($_POST["editarIdActivo"]));
        $datos = [
            "idActivo"            => intval($_POST["editarIdActivo"]),
            "idTipoActivo"        => intval($_POST["editarIdTipoActivo"]),
            "idActivoPadre"       => $activoActual["idActivoPadre"] ?? null,
            "codigoPatrimonial"   => mb_strtoupper(trim($_POST["editarCodigoPatrimonial"]   ?? ''), "UTF-8"),
            "codigoLicencia"      => mb_strtoupper(trim($_POST["editarCodigoLicencia"]       ?? ''), "UTF-8"),
            "numeroSerie"         => mb_strtoupper(trim($_POST["editarNumeroSerie"]          ?? ''), "UTF-8"),
            "fechaAdquisicion"    => !empty($_POST["editarFechaAdquisicion"])    ? $_POST["editarFechaAdquisicion"]    : null,
            "fechaInicioGarantia" => !empty($_POST["editarFechaInicioGarantia"]) ? $_POST["editarFechaInicioGarantia"] : null,
            "fechaFinGarantia"    => !empty($_POST["editarFechaFinGarantia"])    ? $_POST["editarFechaFinGarantia"]    : null,
            "estado"              => trim($_POST["editarEstado"]                 ?? 'disponible'),
            "idCaracteristicas"   => trim($_POST["editarCaracteristicasIds"]     ?? ''),
            "idUsuario"           => $_SESSION["usuario_id"],
        ];
        return ActivosModel::mdlEditarActivo($datos);
    }

    /*=============================================
    MOSTRAR ACTIVO(S)
    =============================================*/
    static public function ctrMostrarActivo($item, $valor)
    {
        return ActivosModel::mdlMostrarActivo('inventario.activo', $item, $valor);
    }

    /*=============================================
    MOSTRAR CARACTERÍSTICAS DE UN ACTIVO
    =============================================*/
    static public function ctrMostrarCaracteristicasActivo($idActivo)
    {
        return ActivosModel::mdlMostrarCaracteristicasActivo($idActivo);
    }

    /*=============================================
    COMPONENTES
    =============================================*/
    static public function ctrMostrarComponentes(int $idActivoPadre)
    {
        return ActivosModel::mdlMostrarComponentes($idActivoPadre);
    }

    static public function ctrActivosDisponibles(int $idPadre)
    {
        return ActivosModel::mdlActivosDisponibles($idPadre);
    }

    static public function ctrAgregarComponente(int $idPadre, int $idHijo)
    {
        if ($idPadre === $idHijo)
            return ["resultado" => "error", "mensaje" => "Un activo no puede ser su propio componente."];
        return ActivosModel::mdlAgregarComponente($idPadre, $idHijo);
    }

    static public function ctrQuitarComponente(int $idHijo)
    {
        return ActivosModel::mdlQuitarComponente($idHijo);
    }

    /*=============================================
    ELIMINAR ACTIVO (lógico)
    =============================================*/
    static public function ctrEliminarActivo()
    {
        if (empty($_POST["eliminarIdActivo"]))
            return ["resultado" => "error", "mensaje" => "ID no recibido."];
        $datos = [
            "idActivo"          => intval($_POST["eliminarIdActivo"]),
            "idUsuarioModifica" => intval($_SESSION["usuario_id"]),
        ];
        return ActivosModel::mdlEliminarActivo($datos);
    }
}

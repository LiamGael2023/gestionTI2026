<?php
require_once __DIR__ . "/../models/EquipoModel.php";

class EquipoController
{
    /*=============================================
    CREAR EQUIPO (INSERT — idEquipo = NULL)
    POST keys: nuevoIdActivo, nuevoCodigoPatrimonial,
               nuevoNumeroSerie, nuevoFechaAdquisicion,
               nuevoFechaInicioGarantia, nuevoFechaFinGarantia,
               nuevoCaracteristicasIds
    =============================================*/
    static public function ctrCrearEquipo()
    {
        if (!isset($_POST["nuevoIdActivo"])) return null;

        $idUsuario = $_SESSION["usuario_id"];

        $datos = [
            "idEquipo"            => null,   // NULL → INSERT en el SP
            "idActivo"            => intval($_POST["nuevoIdActivo"]),
            "idEquipoPadre"       => null,
            "codigoPatrimonial"   => trim($_POST["nuevoCodigoPatrimonial"] ?? ''),
            "numeroSerie"         => trim($_POST["nuevoNumeroSerie"]       ?? ''),
            "fechaInicioGarantia" => self::normalizarFecha($_POST["nuevoFechaInicioGarantia"] ?? ''),
            "fechaFinGarantia"    => self::normalizarFecha($_POST["nuevoFechaFinGarantia"]    ?? ''),
            "fechaAdquisicion"    => self::normalizarFecha($_POST["nuevoFechaAdquisicion"]    ?? ''),
            "idCaracteristicas"   => trim($_POST["nuevoCaracteristicasIds"] ?? '') ?: null,
            "idUsuario"           => $idUsuario,
        ];

        return EquipoModel::mdlCrearEquipo($datos);
    }

    /*=============================================
    EDITAR EQUIPO (UPDATE — idEquipo con valor)
    POST keys: editarIdEquipo, editarIdActivo,
               editarCodigoPatrimonial, editarNumeroSerie,
               editarFechaAdquisicion, editarFechaInicioGarantia,
               editarFechaFinGarantia, editarCaracteristicasIds
    =============================================*/
    static public function ctrEditarEquipo()
    {
        if (!isset($_POST["editarIdActivo"])) return null;

        if (empty($_POST["editarIdEquipo"])) {
            return ["resultado" => "error", "mensaje" => "ID de equipo no recibido."];
        }

        $idUsuario = $_SESSION["usuario_id"];

        $datos = [
            "idEquipo"            => intval($_POST["editarIdEquipo"]),
            "idActivo"            => intval($_POST["editarIdActivo"]),
            "idEquipoPadre"       => null,
            "codigoPatrimonial"   => trim($_POST["editarCodigoPatrimonial"] ?? ''),
            "numeroSerie"         => trim($_POST["editarNumeroSerie"]       ?? ''),
            "fechaInicioGarantia" => self::normalizarFecha($_POST["editarFechaInicioGarantia"] ?? ''),
            "fechaFinGarantia"    => self::normalizarFecha($_POST["editarFechaFinGarantia"]    ?? ''),
            "fechaAdquisicion"    => self::normalizarFecha($_POST["editarFechaAdquisicion"]    ?? ''),
            "idCaracteristicas"   => trim($_POST["editarCaracteristicasIds"] ?? '') ?: null,
            "idUsuario"           => $idUsuario,
        ];

        return EquipoModel::mdlEditarEquipo($datos);
    }

    /*=============================================
    MOSTRAR EQUIPO(S)
    =============================================*/
    static public function ctrMostrarEquipo($item, $valor)
    {
        $tabla = "inventario.equipo";
        return EquipoModel::mdlMostrarEquipo($tabla, $item, $valor);
    }

    /*=============================================
    HELPER — Convierte 'YYYY-MM-DD' a NULL si vacío
    =============================================*/
    private static function normalizarFecha($fecha)
    {
        $fecha = trim($fecha);
        return ($fecha !== '' && $fecha !== '0000-00-00') ? $fecha : null;
    }

    /*=============================================
    MOSTRAR CARACTERÍSTICAS DE UN EQUIPO
    Devuelve [{idCaracteristica, tipo, valor}]
    =============================================*/
    static public function ctrMostrarCaracteristicasEquipo($idEquipo)
    {
        return EquipoModel::mdlMostrarCaracteristicasEquipo($idEquipo);
    }
}
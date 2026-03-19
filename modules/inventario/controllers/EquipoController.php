<?php
require_once __DIR__ . "/../models/EquipoModel.php";

class EquipoController
{
    /*=============================================
    CREAR EQUIPO
    =============================================*/
    static public function ctrCrearEquipo()
    {
        if (!isset($_POST["nuevoIdActivo"])) return null;

        $datos = [
            "idEquipo"            => null,   // el SP genera el ID (IDENTITY)
            "idActivo"            => intval($_POST["nuevoIdActivo"]),
            "idEquipoPadre"       => null,   // nuevo equipo sin padre
            "codigoPatrimonial"   => trim($_POST["nuevoCodigoPatrimonial"]   ?? ''),
            "numeroSerie"         => trim($_POST["nuevoNumeroSerie"]         ?? ''),
            "fechaAdquisicion"    => !empty($_POST["nuevoFechaAdquisicion"])
                                        ? $_POST["nuevoFechaAdquisicion"]    : null,
            "fechaInicioGarantia" => !empty($_POST["nuevoFechaInicioGarantia"])
                                        ? $_POST["nuevoFechaInicioGarantia"] : null,
            "fechaFinGarantia"    => !empty($_POST["nuevoFechaFinGarantia"])
                                        ? $_POST["nuevoFechaFinGarantia"]    : null,
            // El model espera "idCaracteristicas", el form envía "nuevoCaracteristicasIds"
            "idCaracteristicas"   => trim($_POST["nuevoCaracteristicasIds"]  ?? ''),
            "idUsuario"           => $_SESSION["usuario_id"],
        ];

        return EquipoModel::mdlCrearEquipo($datos);
    }

    /*=============================================
    EDITAR EQUIPO
    =============================================*/
    static public function ctrEditarEquipo()
    {
        if (!isset($_POST["editarIdActivo"])) return null;
        if (empty($_POST["editarIdEquipo"])) {
            return ["resultado" => "error", "mensaje" => "ID de equipo no recibido."];
        }

        // Recuperar el idEquipoPadre actual para no perderlo al editar
        $equipoActual = EquipoModel::mdlMostrarEquipo(
            'inventario.equipo', 'idEquipo', intval($_POST["editarIdEquipo"])
        );

        $datos = [
            "idEquipo"            => intval($_POST["editarIdEquipo"]),
            "idActivo"            => intval($_POST["editarIdActivo"]),
            "idEquipoPadre"       => $equipoActual["idEquipoPadre"] ?? null,
            "codigoPatrimonial"   => trim($_POST["editarCodigoPatrimonial"]   ?? ''),
            "numeroSerie"         => trim($_POST["editarNumeroSerie"]         ?? ''),
            "fechaAdquisicion"    => !empty($_POST["editarFechaAdquisicion"])
                                        ? $_POST["editarFechaAdquisicion"]    : null,
            "fechaInicioGarantia" => !empty($_POST["editarFechaInicioGarantia"])
                                        ? $_POST["editarFechaInicioGarantia"] : null,
            "fechaFinGarantia"    => !empty($_POST["editarFechaFinGarantia"])
                                        ? $_POST["editarFechaFinGarantia"]    : null,
            "idCaracteristicas"   => trim($_POST["editarCaracteristicasIds"]  ?? ''),
            "idUsuario"           => $_SESSION["usuario_id"],
        ];

        return EquipoModel::mdlEditarEquipo($datos);
    }

    /*=============================================
    MOSTRAR EQUIPO(S)
    =============================================*/
    static public function ctrMostrarEquipo($item, $valor)
    {
        return EquipoModel::mdlMostrarEquipo('inventario.equipo', $item, $valor);
    }

    /*=============================================
    MOSTRAR CARACTERÍSTICAS DE UN EQUIPO
    =============================================*/
    static public function ctrMostrarCaracteristicasEquipo($idEquipo)
    {
        return EquipoModel::mdlMostrarCaracteristicasEquipo($idEquipo);
    }

    /*=============================================
    ARMAR EQUIPO — COMPONENTES
    =============================================*/

    /** Componentes actuales del equipo padre */
    static public function ctrMostrarComponentes(int $idEquipoPadre)
    {
        return EquipoModel::mdlMostrarComponentes($idEquipoPadre);
    }

    /** Equipos disponibles para ser componentes */
    static public function ctrEquiposDisponibles(int $idPadre)
    {
        return EquipoModel::mdlEquiposDisponibles($idPadre);
    }

    /** Asignar componente al padre */
    static public function ctrAgregarComponente(int $idPadre, int $idHijo)
    {
        if ($idPadre === $idHijo) {
            return ["resultado" => "error", "mensaje" => "Un equipo no puede ser su propio componente."];
        }
        return EquipoModel::mdlAgregarComponente($idPadre, $idHijo);
    }

    /** Quitar componente del padre */
    static public function ctrQuitarComponente(int $idHijo)
    {
        return EquipoModel::mdlQuitarComponente($idHijo);
    }
}
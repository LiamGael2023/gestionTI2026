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
$conn2  = Conexion2::conectar();
$model = new InicioModel($conn2);

/* ======================================================
   ROUTER DE VISTAS (SOLO SI NO ES AJAX)
   Se evita ejecutar cuando alguien "include" el controlador
   desde otro módulo (por ejemplo AuthController), definiendo
   SKIP_INICIO_ROUTING antes de la inclusión.
====================================================== */
if (!$isAjax && !defined('SKIP_INICIO_ROUTING')) {

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

    static public function cargarInformacionUsuario($documento)
    {


        $tabla = "Escalafon.Tblajador";

        $item = "Trab_Documento";
        $valor = $documento;

        $respuesta = InicioModel::cargarInformacionUsuario($tabla, $item, $valor);

        if (!$respuesta || !isset($respuesta["rol"]) || empty($respuesta["rol"])) {
            return;
        }
        switch ($respuesta["rol"]) {

            case "colaborador":
                $_SESSION["id_Trabajador"] = $respuesta['id_Trabajador'];
                $_SESSION["id_Trabajador_Tipo"] = $respuesta['id_Trabajador_Tipo'];
                $_SESSION["TrabTipo_Descripcion"] = $respuesta['TrabTipo_Descripcion'];
                $_SESSION["Trab_Documento"] = $respuesta['Documento'];
                $_SESSION["Fecha_Nacimiento"] = $respuesta['Fecha_Nacimiento'];
                $_SESSION["Correo"] = $respuesta['Correo'];
                $_SESSION["Celular"] = $respuesta['Celular'];
                $_SESSION["Trab_Paterno"] = ($respuesta['Trab_Paterno']);
                $_SESSION["Trab_Materno"] = ($respuesta['Trab_Materno']);
                $_SESSION["Trab_Nombres"] = ($respuesta['Nombres']);
                $_SESSION["Oficina"] = $respuesta['Unidad_Laboral'];
                $_SESSION["DireccionActualizada"] = $respuesta['DireccionActualizada'];
                $_SESSION["Gerencia"] = $respuesta['Gerencia_Laboral'];

                $_SESSION["JefeArea"] = $respuesta['JefeArea'];
                $_SESSION["esJefeSede"] = $respuesta['JefeSede'];
                $_SESSION["FirmaPersonal"] = $respuesta['FirmaPersonal'];
                $_SESSION["FirmaJefe"] = $respuesta['FirmaJefe'];
                $_SESSION["FirmaJefeSede"] = $respuesta['FirmaJefeSede'];
                $_SESSION["Trab_Fotocheck"] = $respuesta['codigo_reloj'];
                $_SESSION["Id_Horario"] = $respuesta['Id_Horario'];
                $_SESSION["Jefe"] = $respuesta['Jefe'];
                $_SESSION["Id_Establecimiento"] = $respuesta['Id_Establecimiento'];
                $_SESSION["SEDE"] = $respuesta['SEDE'];
                $_SESSION["JefeInmediato"] = $respuesta['JefeInmediato'];
                $_SESSION["cod_jefe"] = $respuesta['cod_jefe'];
                $_SESSION["foto_personal"] = $respuesta['FotoPersonal'];
                $_SESSION["rol"] = $respuesta['rol'];
                $_SESSION["EsConductor"] = $respuesta['EsConductor'];
                $_SESSION["EsASST"] = $respuesta['EsASST'];
                $_SESSION["esSubgerente"] = $respuesta['esSubgerente'];
                $_SESSION["esUPER"] = $respuesta['esUPER'];
                $_SESSION["esAptoDifusion"] = $respuesta['esAptoDifusion'];
                $_SESSION["esJefeTransportes"] = $respuesta['esJefeTransportes'];

                break;
            case "vigilante":
                if ($respuesta["documento"] == $_POST["ingUsuario"] && $respuesta["contrasenia"] == $_POST["ingPassword"]) {
                    $_SESSION["iniciarSesion"] = "ok";
                    $_SESSION["id_vigilante"] = $respuesta['id_vigilante'];
                    $_SESSION["Trab_Documento"] = $respuesta['documento'];
                    $_SESSION["Trab_Paterno"] = utf8_encode($respuesta['Trab_Paterno']);
                    $_SESSION["Trab_Materno"] = utf8_encode($respuesta['Trab_Materno']);
                    $_SESSION["Trab_Nombres"] = utf8_encode($respuesta['Trab_Nombres']);
                    $_SESSION["foto_personal"] = $respuesta['foto_personal'];
                    $_SESSION["Correo"] = utf8_encode($respuesta['correo_electronico']);
                    $_SESSION["Celular"] = $respuesta['telefono'];
                    $_SESSION["SEDE"] = $respuesta['sede'];
                    $_SESSION["ultimo_acceso"] = $respuesta['ultimo_acceso'];
                    $_SESSION["rol"] = $respuesta['rol'];               // "vigilante"
                    $_SESSION["rol_vigilante"] = $respuesta['rol_vigilante']; // rol interno o jerarquía
                    echo '<script>
                                window.location = "inicio-vigilantes";
                            </script>';
                } else {

                    echo '<br><div class="alert alert-danger">Error al ingresar, vuelve a intentarlo</div>';
                }
                break;

            default:
                echo '<br><div class="alert alert-danger">Usuario no encontrado, vuelve a intentarlo</div>';
                break;
        }
    }
}

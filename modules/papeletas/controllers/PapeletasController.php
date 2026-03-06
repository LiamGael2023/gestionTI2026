<?php
// En lugar de rutas relativas simples, usa __DIR__
require_once __DIR__ . "/../../../config/db.php"; 
require_once __DIR__ . "/../models/PapeletasModel.php";
$model = new ModeloPapeleta($conn);
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'guardar':
        // Lógica de guardado
        break;
    default:
        include 'modules/papeletas/views/index.php';
        break;
}
$conn  = Conexion::conectar();
$model = new ModeloPapeleta($conn);

class ControladorPapeleta
{

    static public function ctrMostrarPapeletasUsuario($id_trabajador, $start, $length, $search)
    {
        $respuesta = ModeloPapeleta::MdlMostrarPapeletasUsuarios($id_trabajador, $start, $length, $search);
        return $respuesta;
    }

    static public function ctrMostrarJefeUnidad()
    {

        // Lo pasas al modelo
        $respuesta = ModeloPapeleta::mdlMostrarJefesUnidad();

        return $respuesta;
    }
    static public function ctrMostrarPapeletasPendientesJefe(
        $id_jefe,
        $start,
        $length,
        $search,
        $filtro,      // ← HOY, AYER, MES, ESTE AÑO...
        $firmas
    ) {

        $tabla = "Aplicativo.papeleta";

        $respuesta = ModeloPapeleta::MdlMostrarPapeletasPendientesJefe(
            $id_jefe,
            $start,
            $length,
            $search,
            $filtro,      // ← HOY, AYER, MES, ESTE AÑO...
            $firmas
        );

        return $respuesta;
    }

    static public function ctrMostrarPapeletaVigilantes($id_establecimiento = null, $start = 0, $length = 10, $search = null, $filtroFecha, $filtroCerrar)
    {
        $respuesta = ModeloPapeleta::MdlMostrarPapeletasVigilantes(
            $id_establecimiento,
            $start,
            $length,
            $search, // <-- pasamos también al modelo
            $filtroFecha,
            $filtroCerrar
        );

        return $respuesta;
    }



    static public function ctrMostrarPapeletaUPER($id_establecimiento = null, $start = 0, $length = 10, $search = null, $filtroFecha, $filtroCerrar)
    {
        $respuesta = ModeloPapeleta::MdlMostrarPapeletasUPER(
            $id_establecimiento,
            $start,
            $length,
            $search, // <-- pasamos también al modelo
            $filtroFecha,
            $filtroCerrar
        );

        return $respuesta;
    }

    static public function ctrMostrarPapeletasPorTrabajador($id_trabajador, $start = 0, $length = 10, $search = null)
    {
        $respuesta = ModeloPapeleta::MdlMostrarPapeletasPorTrabajador(
            $id_trabajador,
            $start,
            $length,
            $search
        );

        return $respuesta;
    }

    static public function ctrMostrarPapeletaReporte($item, $valor)
    {

        $tabla = "Aplicativo.papeleta";

        $respuesta = ModeloPapeleta::MdlMostrarPapeletaReporte(

            $item,
            $valor
        );

        return $respuesta;
    }




    static public  function ctrMostrarEvidenciasPapeleta($id_papeleta)
    {
        $evidencias = ModeloPapeleta::mdlObtenerEvidencias($id_papeleta);
        return $evidencias; // array con nombre, tipo y base64
    }


    public static function ctrEliminarEvidencia($idImagen)
    {
        return ModeloPapeleta::mdlEliminarEvidencia($idImagen);
    }

    static public function ctrAnularPapeleta($id)
    {
        return ModeloPapeleta::mdlAnularPapeleta("aplicativo.papeleta", $id);
    }


    static public function ctrActualizarJefeInmediato($id_papeleta, $codJefe)
    {
        return ModeloPapeleta::mdlActualizarJefeInmediato($id_papeleta, $codJefe);
    }

    static public function ctrNoAutorizado($id_papeleta)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $codigo_jefe = $_SESSION["id_Trabajador"] ?? null;
        return ModeloPapeleta::mdlNoAutorizado($id_papeleta, $codigo_jefe);
    }


    static public function ctrTienePapeletaPendiente()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $id_trabajador = $_SESSION["id_Trabajador"] ?? null;
        return ModeloPapeleta::mdlTienePapeletaPendiente($id_trabajador);
    }

    static public function ctrMarcarSalida($id_papeleta)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return ModeloPapeleta::mdlMarcarHoraSalida($id_papeleta);
    }

    static public function ctrMarcarRetorno($id_papeleta)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return ModeloPapeleta::mdlMarcarHoraRetorno($id_papeleta);
    }

    static public function ctrMostrarConceptos($item, $valor)
    {

        $tabla = "Escalafon.Tbl_Trabajador_Concepto_APP";

        $respuesta = ModeloPapeleta::MdlMostrarConceptos($tabla, $item, $valor);

        return $respuesta;
    }

    static public function ctrCrearPapeleta()
    {

        function convertirFechaSQL($fecha)
        {
            if (empty($fecha)) return null;
            $f = DateTime::createFromFormat('d/m/Y', $fecha);
            return $f ? $f->format('Y-m-d H:i:s') : null;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["concepto"])) {

            // $tabla = "Aplicativo.papeleta";
            $fechaInicioSQL = convertirFechaSQL($_POST["fechaini"]);
            $fechaFinSQL    = convertirFechaSQL($_POST["fechafin"]);
            $sinretorno = ($_POST["conRetornoCheckbox"] == 'SI') ? 'SI' : 'NO';
            $cerrar = (isset($_POST["Cerrar"]) && ($_POST["Cerrar"] == '1' || $_POST["Cerrar"] === true)) ? 1 : 0;
            $salida_vehiculo = isset($_POST["salidaConVehiculoCheckbox"]) ? 1 : 0;
            $salida_trujillo = isset($_POST["salidaSedeTrujilloCheckbox"]) ? 1 : 0;
            $solicitar_combustible = isset($_POST["solicitarCombustibleCheckBox"]) ? 1 : 0;
            $datos = array(
                "Id_Trabajador" => $_POST["Id_Trabajador"],
                "nombres" => $_POST["trabajador"],
                "oficina" => $_POST["nuevoOfi"],
                "gerencia" => $_POST["nuevoGerencia"],
                "cod_jefe" => $_POST["Jefe"],
                "FirmaPersonal" => $_POST["FirmaPersonal"],
                "FirmaJefe" => $_POST["FirmaJefe"],
                "FirmaJefeSede" => $_POST["FirmaJefeSede"],
                "Id_Trabajador_Concepto_APP" => strtoupper($_POST["concepto"]),
                "Id_Trabajador_Motivo_APP" => strtoupper($_POST["motivo"]),
                "Id_Trabajador_Lugar_APP" => $_POST["lugar"],
                "destinatario" => $_POST["funcionario"],
                "fecha_inicio"  => $fechaInicioSQL,
                "fecha_fin"     => $fechaFinSQL,
                "Id_Establecimiento" => $_POST["Id_Establecimiento"],
                "observacion" => $_POST["observaciones"],
                "sinretorno" => $sinretorno,
                "JefeInmediato" => $_POST["JefeInmediato"],
                "Cerrar" => $_POST["Cerrar"],
                "placa" => $_POST["placa"],
                "placaseleccionada" => $_POST["placaseleccionada"],
                "kilometraje_inicial" => $_POST["kminicial"],
                "salida_trujillo" => $salida_trujillo,
                "salida_vehiculo" => $salida_vehiculo,
                "solicitar_combustible" => $solicitar_combustible,
                "sede" => $_POST["sede"]

            );

            $respuesta = ModeloPapeleta::mdlCrearPapeleta($datos);

            if ($respuesta == "ok") {
                echo json_encode(array("status" => "success", "message" => "¡La Papeleta ha sido guardada correctamente!"));
            } else {
                echo json_encode(array("status" => "error", "message" => "Hubo un error al guardar la Papeletaas."));
            }
        } else {
            echo json_encode(array("status" => "error", "message" => "¡La Papeleta no puede ir vacío o llevar caracteres especiales!"));
        }
    }

    static public function ctrIntercambiarEstado($id_papeleta, $campo, $id_jefe_aprobacion)
    {
        $respuesta = ModeloPapeleta::mdlIntercambiarEstado($id_papeleta, $campo, $id_jefe_aprobacion);
        return $respuesta;
    }


    public static function ctrSubirEvidencias($post, $files)
    {
        if (!isset($post['id_papeleta_modal']) || empty($post['id_papeleta_modal'])) {
            return ["status" => "error", "message" => "ID de papeleta no recibido"];
        }

        $id_papeleta = $post['id_papeleta_modal'];

        // Asegurarse de tomar solo los archivos del input evidencia[]
        if (!isset($files['evidencia'])) {
            return ["status" => "error", "message" => "No se recibieron archivos"];
        }
        $archivos = $files['evidencia'];

        $guardados = [];

        foreach ($archivos['tmp_name'] as $i => $tmpName) {
            if ($archivos['error'][$i] !== UPLOAD_ERR_OK) continue; // ignorar errores

            $nombre = $archivos['name'][$i];
            $tipo = $archivos['type'][$i];

            $contenido = file_get_contents($tmpName);
            if ($contenido === false) continue;

            $insertado = ModeloPapeleta::mdlInsertarEvidencia($id_papeleta, $nombre, $tipo, $contenido);
            if ($insertado) $guardados[] = $nombre;
        }

        if (count($guardados) > 0) {
            return ["status" => "success", "message" => count($guardados) . " imágenes guardadas."];
        } else {
            return ["status" => "error", "message" => "No se pudieron guardar las imágenes."];
        }
    }
    static public function ctrFotoJefe()
    {
        $respuesta = ModeloPapeleta::mdlFotoJefe();

        if ($respuesta && isset($respuesta['trab_fotocheck'])) {
            return $respuesta['trab_fotocheck']; // solo el fotocheck
        } else {
            return "default"; // fallback si no hay dato
        }
    }
}

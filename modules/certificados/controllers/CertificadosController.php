<?php

// Controlador certificados
if (session_status() === PHP_SESSION_NONE) session_start();

// Detectar si es AJAX al inicio
$esAjaxAlInicio = (
    !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) || (
    isset($_SERVER['HTTP_ACCEPT'])
    && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false
);

// Si es AJAX, activar buffering para capturar y descartar el header.php
if ($esAjaxAlInicio) {
    ob_start();
}

// Para obtener usuario actual
//$id_usuario_registro = $_SESSION['id_usuario'] ?? 1;
//$usuario_actual = $_SESSION['nombre_completo'] ?? 'Usuario no identificado';
// Incluir archivos necesarios
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/db.php';          // Clase Conexion
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../models/CertificadosModel.php';

require_once __DIR__ . '/../../../vendor/autoload.php';

require_once __DIR__ . '/../PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/../PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer-master/src/SMTP.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
 // Modelo
//require_once __DIR__.'/../../../config/permisos.php';

//repararPermisosUploads();
if (!isset($_GET['module'])) {
    $_GET['module'] = 'certificados';
}

Auth::check();

// Crear la conexión PDO
$db = Conexion::conectar();

// Instancia del modelo
$model = new CertificadosModel($db);

function obtenerIdUsuarioSesion(): ?int {
    if (isset($_SESSION['usuario_id']) && is_numeric($_SESSION['usuario_id'])) {
        return (int)$_SESSION['usuario_id'];
    }
    // Compatibilidad por si existe la clave antigua en alguna sesión.
    if (isset($_SESSION['id_usuario']) && is_numeric($_SESSION['id_usuario'])) {
        return (int)$_SESSION['id_usuario'];
    }
    return null;
}

// Función para responder JSON limpiando cualquier buffer de salida previo
function responderJSON(array $data): void {
    // Limpiar cualquier output bufferizado (ej: el header.php incluido)
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Solo enviar header si aún no han sido enviados
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=UTF-8');
    }
    
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function esPeticionAjax(): bool {
    return (
        !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    ) || (
        isset($_SERVER['HTTP_ACCEPT'])
        && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false
    );
}

function responderNotificacion(string $tipo, string $titulo, string $mensaje, string $destino, array $extra = []): void {
    $tipoSeguro = in_array($tipo, ['success', 'info', 'warning', 'danger'], true) ? $tipo : 'info';

    if (esPeticionAjax()) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(array_merge([
            'success' => $tipoSeguro !== 'danger',
            'type' => $tipoSeguro,
            'title' => $titulo,
            'message' => $mensaje,
            'redirect' => $destino,
        ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $tituloJs = json_encode($titulo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $mensajeJs = json_encode($mensaje, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $destinoJs = json_encode($destino, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    echo "<script>
    (function() {
        if (!window.adqNotify || !window.adqNotifySafe) {
            function ensureContainer() {
                var container = document.getElementById('adq-alert-stack');
                if (!container) {
                    container = document.createElement('div');
                    container.id = 'adq-alert-stack';
                    container.className = 'position-fixed bottom-0 end-0 p-3 d-flex flex-column gap-2';
                    container.style.zIndex = '1100';
                    container.setAttribute('aria-live', 'polite');
                    container.setAttribute('aria-atomic', 'false');
                    document.body.appendChild(container);
                }
                return container;
            }

            function getAlertType(type) {
                var allowed = ['success', 'info', 'warning', 'danger'];
                return allowed.indexOf(type) >= 0 ? type : 'info';
            }

            function getDefaultHeading(type) {
                switch (type) {
                    case 'success': return 'Operacion completada';
                    case 'warning': return 'Atencion';
                    case 'danger': return 'Ocurrio un problema';
                    default: return 'Informacion';
                }
            }

            window.adqNotify = function(type, heading, description, options) {
                var opts = Object.assign({ delay: 3200, autohide: true }, options || {});
                var alertType = getAlertType(type);
                var alertHeading = heading || getDefaultHeading(alertType);
                var alertDescription = description || '';
                var stack = ensureContainer();

                var alertEl = document.createElement('div');
                alertEl.className = 'alert alert-' + alertType;
                alertEl.style.margin = '0';
                alertEl.setAttribute('role', 'alert');

                var contentWrap = document.createElement('div');
                var headingEl = document.createElement('h4');
                headingEl.className = 'alert-heading';
                headingEl.textContent = alertHeading;

                var descriptionEl = document.createElement('div');
                descriptionEl.className = 'alert-description';
                descriptionEl.textContent = alertDescription;
                descriptionEl.style.whiteSpace = 'pre-line';

                contentWrap.appendChild(headingEl);
                if (alertDescription !== '') {
                    contentWrap.appendChild(descriptionEl);
                }

                alertEl.appendChild(contentWrap);
                stack.appendChild(alertEl);

                function closeAlert() {
                    if (alertEl.parentNode) {
                        alertEl.parentNode.removeChild(alertEl);
                    }
                }

                alertEl.addEventListener('click', closeAlert);
                if (opts.autohide) {
                    window.setTimeout(closeAlert, opts.delay);
                }

                return alertEl;
            };

            window.adqNotifySafe = function(type, heading, description, options) {
                if (typeof window.adqNotify === 'function') {
                    return window.adqNotify(type, heading, description, options);
                }
                console.warn(description || heading || 'Ocurrio un evento.');
                return null;
            };
        }

        window.adqNotifySafe('" . $tipoSeguro . "', " . $tituloJs . ", " . $mensajeJs . ", { delay: 3200, autohide: true });
        window.setTimeout(function() {
            window.location = " . $destinoJs . ";
        }, 900);
    })();
    </script>";
    exit;
}

// Acción por defecto
$action = $_GET['action'] ?? 'index';

switch($action){
    case 'crear':
    $personas = $model->listarPersonas();
    $esPeticionAjax = (
        !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    ) || (
        isset($_SERVER['HTTP_ACCEPT'])
        && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false
    );

    $responderCrear = function(bool $ok, string $mensaje, int $statusCode = 200, array $extra = []) use ($esPeticionAjax) {
        if($esPeticionAjax){
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(array_merge([
                'success' => $ok,
                'message' => $mensaje,
            ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }

        $destino = 'index.php?module=certificados';
        responderNotificacion(
            $ok ? 'success' : 'danger',
            $ok ? 'Operacion completada' : 'Ocurrio un problema',
            $mensaje,
            $destino
        );
    };
    
    // 🔧 Función para dar permisos SOLO al archivo
    function darPermisoArchivo($archivo){
        $archivo = escapeshellarg($archivo);
        $comando = "icacls $archivo /grant Todos:F";
        exec($comando);
    }

    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_persona'])) {
        $id_persona = $_POST['id_persona'];
        $codigo_reloj = $_POST['codigo_reloj'] ?? $model->obtenerCodigoReloj($id_persona);
        $fecha_emision = $_POST['fecha_emision'];
        $duracion = (int)$_POST['duracion_anios'];
        $tipo_certificado = $_POST['tipo_certificado'] ?? 'TOKEN_SOFTWARE';
        $estado = $_POST['estado'] ?? 'activo';
        $id_usuario_registro = obtenerIdUsuarioSesion();
        if (!$id_usuario_registro) {
            $responderCrear(false, 'No se pudo identificar el usuario en sesión.', 401);
        }

        $tipo_tramite   = $_POST['tipo_tramite']   ?? 'Entidad';
        $estado_tramite = $_POST['estado_tramite'] ?? 'No Tramitado';

        // Archivos
        $archivo_nombre = $_FILES['archivo']['name'] ?? null;
        $archivo_tmp = $_FILES['archivo']['tmp_name'] ?? null;
        $archivo_tipo = $_FILES['archivo']['type'] ?? null;

        // Validación tipos de archivo
        $tipos_validos = ['image/jpeg','image/png','image/gif','application/x-pkcs12','application/pkcs12'];
        if($archivo_nombre && !in_array($archivo_tipo, $tipos_validos)){
            $responderCrear(false, 'Solo se permiten JPG, PNG, GIF o PFX', 422);
        }

        // Carpeta de uploads
        umask(0);
        $upload_dir = __DIR__ . "/../../uploads/certificados/";
        if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        // 🔥 LIMPIAR NOMBRE (evitar espacios/problemas IIS)
        $archivo_nombre = time() . "_" . preg_replace('/[^A-Za-z0-9.\-_]/', '_', $archivo_nombre);

        $ruta_final = $upload_dir . $archivo_nombre;

        if(move_uploaded_file($archivo_tmp, $ruta_final)){

            // 🔥 APLICAR PERMISOS SOLO AL ARCHIVO
            darPermisoArchivo($ruta_final);

            // Calcular fecha de vencimiento
            $fecha_vencimiento = date('Y-m-d', strtotime("+$duracion years", strtotime($fecha_emision)));

            try {
                $idCertificadoCreado = $model->crearCertificado(
                    $codigo_reloj,
                    $fecha_emision,
                    $duracion,
                    $fecha_vencimiento,
                    $estado,
                    $id_persona,
                    $id_usuario_registro,
                    $archivo_nombre,
                    $tipo_certificado,
                    $tipo_tramite,
                    $estado_tramite
                );

                if($idCertificadoCreado){
                    $filaNueva = $model->obtenerFilaTablaPrincipal($idCertificadoCreado);
                    $responderCrear(true, 'Certificado creado correctamente', 200, [
                        'row' => $filaNueva,
                        'id_certificado' => $idCertificadoCreado,
                    ]);
                }

                $responderCrear(false, 'Error al crear certificado', 500);
            } catch(Throwable $e) {
                if(file_exists($ruta_final)){
                    unlink($ruta_final);
                }

                $responderCrear(false, $e->getMessage(), 500);
            }
        } else {
            $responderCrear(false, 'Error al subir el archivo', 500);
        }
    }

    require __DIR__ . '/../views/crear_certificado.php';
    exit;
break;

    /* ========================================================
       ELIMINAR CERTIFICADO
    ======================================================== */
    case 'eliminar':

$id = $_GET['id'] ?? null;

if($id){
    $resultado = $model->eliminar($id);

    if(esPeticionAjax()){
        responderJSON([
            'success' => (bool)$resultado,
            'message' => $resultado ? 'Certificado eliminado correctamente' : 'Error al eliminar el certificado',
        ]);
    }

    if($resultado){
        responderNotificacion('success', 'Operacion completada', 'Certificado eliminado correctamente', 'index.php?module=certificados&msg=eliminado');
    }else{
        responderNotificacion('danger', 'Ocurrio un problema', 'Error al eliminar el certificado', 'index.php?module=certificados');
    }

    exit;
}

die('Certificado no especificado');

break;
    /* ========================================================
       CREAR BACKUP
    ======================================================== */
   case 'crearBackup1':
function darPermisoArchivo($archivo){
        $archivo = escapeshellarg($archivo);
        $comando = "icacls $archivo /grant Todos:F";
        exec($comando);
    }

    $certificados = $model->listarSinBackup(); // Lista certificados sin backup

    if($_SERVER['REQUEST_METHOD'] === 'POST'){

        $id_certificado = $_POST['id_certificado'] ?? null;

        // Validar archivo
        if(!isset($_FILES['archivo']) || $_FILES['archivo']['error'] != 0){
            die("Debe seleccionar un archivo válido.");
        }

        $archivo_subido = $_FILES['archivo']['name'];
        $tmp_archivo = $_FILES['archivo']['tmp_name'];

        if(!$id_certificado){
            die("Debe seleccionar un certificado.");
        }

        // Carpeta destino
        umask(0);
        $destinoCarpeta = __DIR__ . "/../../uploads/backups/";

        if(!is_dir($destinoCarpeta)){
            mkdir($destinoCarpeta, 0777, true);
        }

        // Limpiar nombre archivo
        $archivo_limpio = preg_replace('/[^A-Za-z0-9.\-_]/', '_', $archivo_subido);

        // Nombre final
        $nombreBackup = time() . "_" . $archivo_limpio;

        $destino = $destinoCarpeta . $nombreBackup;

        // Mover archivo
        if(move_uploaded_file($tmp_archivo, $destino)){

           darPermisoArchivo($destino); // ✅ CORRECTO

    // Obtener datos del certificado
            $certificado = $model->obtenerPorId($id_certificado);
            if(!$certificado){
                die("Certificado no encontrado.");
            }

            $identificador = $certificado['nombres']." ".$certificado['apellidos']."-".date('Y-m-d', strtotime($certificado['fecha_emision']));

            $id_usuario = obtenerIdUsuarioSesion();
            if (!$id_usuario) {
                responderNotificacion('danger', 'Ocurrio un problema', 'No se pudo identificar el usuario en sesión.', 'index.php?module=certificados');
            }

            // Guardar backup en BD
            $resultado = $model->crearBackup1(
                $certificado['codigo_reloj'],
                $identificador,
                $nombreBackup,
                $id_certificado,
                $id_usuario
            );

            if($resultado){
                responderNotificacion('success', 'Operacion completada', 'Ingreso correcto de backup', 'index.php?module=certificados');
            }else{
                die("Error al registrar el backup en la base de datos.");
            }

        }else{
            die("Error al subir el archivo al servidor.");
        }
    }

    require 'modules/certificados/views/crearBackup.php';
    exit;

    /* ========================================================
       VER BACKUPS
    ======================================================== */
    case 'verBackups':
    $id = $_GET['id'] ?? null;
    if(!$id) die("Certificado no especificado");

    $certificado = $model->obtenerPorId($id);
    $backups = $model->listarBackups1($id);

    require 'modules/certificados/views/backups_modal.php'; // 👈 NUEVO ARCHIVO LIMPIO
    exit; // 🔥 ESTO ES LO MÁS IMPORTANTE
    /* ========================================================
       DETALLE CERTIFICADO
    ======================================================== */
    case 'detalle':

$id = $_GET['id'] ?? null;

if(!$id){
    die("Certificado no encontrado");
}

$certificado = $model->obtenerPorId1($id);

$backups = $model->obtenerBackups2($id);

if(!$certificado){
    die("Certificado no existe");
}

require 'modules/certificados/views/detalle.php';

exit;

    /* ========================================================
       LISTAR PERSONAS
    ======================================================== */
    case 'verPersonas':
        $personas = $model->listarPersonas();
        require 'modules/certificados/views/verPersonas.php';
        exit;

    /* ========================================================
       DASHBOARD / LISTADO PRINCIPAL
    ======================================================== */
    default:

    $buscar = $_GET['buscar'] ?? null;
$fecha_inicio = $_GET['fecha_inicio'] ?? null;
$fecha_fin = $_GET['fecha_fin'] ?? null;
$tipo_tramite = $_GET['tipo_tramite'] ?? null;

$certificados = $model->listar($buscar, $fecha_inicio, $fecha_fin, $tipo_tramite);
        $porVencer = $model->porVencer();

        $proximos = $model->certificadosPorVencer(); // últimos 30 días
        $model->actualizarEstadoCertificados();
        
        require 'modules/certificados/views/index.php';
        exit;

case  'dashboard':
       $total = $model->total();
        $activos = $model->activos();
        $vencidos = $model->vencidos();
        $porVencer = $model->porVencer();
        $totalPersonas = $model->totalPersonas();
$certificadosMes = $model->certificadosPorMes();
$certificadosAnio = $model->certificadosPorAnio();
$gerenciasComparativa = $model->comparativaGerencias();
require 'modules/certificados/views/dashboard.php';
        exit;    
break;
case 'verPersonas1':

$buscar1 = $_GET['buscar1'] ?? "";

if($buscar1 != ""){
    $personas = $model->buscar1($buscar1);
}else{
    $personas = $model->listar2();
}

$gerencias = $model->contarGerencias();
$total = $model->total1();

require 'modules/certificados/views/verpersonas.php';

break;

case 'nuevo':

require 'modules/certificados/views/form.php';

break;

case 'guardar':
    $resultado = $model->guardar($_POST);
    
    if(esPeticionAjax()){
        responderJSON([
            'success' => (bool)$resultado,
            'message' => 'Persona ingresada correctamente',
        ]);
    }
    
    responderNotificacion('success', 'Operacion completada', 'Persona ingresada correctamente', 'index.php?module=certificados');
break;


case 'editar':

$id = $_GET['id'];

$persona = $model->obtener($id);

require 'modules/certificados/views/form.php';

break;

case 'actualizar':

    $resultado = $model->actualizar($_POST);
    
    if(esPeticionAjax()){
        responderJSON([
            'success' => (bool)$resultado,
            'message' => 'Persona actualizada correctamente',
        ]);
    }
    
    responderNotificacion('success', 'Operacion completada', 'Persona actualizada correctamente', 'index.php?module=certificados');
break;

case 'verCertificadosPersona':

$id_persona = $_GET['id'];

$persona = $model->obtenerPersona($id_persona);

$certificados = $model->obtenerCertificadosPersona($id_persona);

require 'modules/certificados/views/vercertificadospersona.php';

break;

case 'certificadosPorVencer1':

$tipo = $_GET['tipo'] ?? 'PERSONAL';

// IMPORTANTE: usar MAYÚSCULAS como en BD
$certificados = $model->obtenerCertificadosPorVencer2($tipo);

require 'modules/certificados/views/certificados_por_vencer.php';

break;
case 'exportarPDF':

    // Obtener certificados por vencer
    $certificados = $model->obtenerCertificadosPorVencer2();

    // Obtener datos de la API
    $apiUrl = "https://www.chavimochic.gob.pe/api_incidencias/api_personal.php";
    $apiResponse = file_get_contents($apiUrl);
    $apiData = json_decode($apiResponse, true);

    // Crear arreglo de personal por DNI
    $personalPorDNI = [];
    if(isset($apiData['data'])){
        foreach($apiData['data'] as $p){
            $personalPorDNI[$p['Documento']] = $p;
        }
    }

    // --- PDF ---
    require_once __DIR__ . '/../models/libs/fpdf/fpdf.php';

    class PDF extends FPDF {
        function Row($data, $widths, $aligns = []) {
            $nb = 0;
            for($i=0;$i<count($data);$i++){
                $texto = iconv('UTF-8','ISO-8859-1//TRANSLIT', $data[$i] ?? '');
                $nb = max($nb,$this->NbLines($widths[$i],$texto));
            }
            $h = 4 * $nb;
            for($i=0;$i<count($data);$i++){
                $w = $widths[$i];
                $a = isset($aligns[$i]) ? $aligns[$i] : 'L';
                $x = $this->GetX();
                $y = $this->GetY();
                $this->Rect($x,$y,$w,$h);
                $texto = iconv('UTF-8','ISO-8859-1//TRANSLIT', $data[$i] ?? '');
                $this->MultiCell($w,4,$texto,0,$a);
                $this->SetXY($x+$w,$y);
            }
            $this->Ln($h);
        }

        function NbLines($w,$txt){
            $cw=&$this->CurrentFont['cw'];
            if($w==0) $w=$this->w-$this->rMargin-$this->x;
            $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
            $s=str_replace("\r",'',$txt);
            $nb=strlen($s);
            if($nb>0 and $s[$nb-1]=="\n") $nb--;
            $sep=-1; $i=0; $j=0; $l=0; $nl=1;
            while($i<$nb){
                $c=$s[$i];
                if($c=="\n"){$i++; $sep=-1; $j=$i; $l=0; $nl++; continue;}
                if($c==' ') $sep=$i;
                $l += $cw[$c];
                if($l>$wmax){
                    if($sep==-1){if($i==$j) $i++;} else{$i=$sep+1;}
                    $sep=-1; $j=$i; $l=0; $nl++;
                } else $i++;
            }
            return $nl;
        }
    }

    $pdf = new PDF('L','mm','A4');
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(true,10);

    // Titulo
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,10,iconv('UTF-8','ISO-8859-1//TRANSLIT','CERTIFICADOS DIGITALES POR VENCER - PRÓXIMOS 10 DÍAS'),0,1,'C');
    $pdf->Ln(4);

    // Encabezados
    $pdf->SetFont('Arial','B',7);
    $headers = ['DNI','DIRECCIÓN LABORAL','APELLIDOS Y NOMBRES','CARGO','ÁREA','CELULAR','EMAIL','TIPO CERT.','MODO DESC.'];
    $widths = [18,45,45,25,25,22,40,28,28];
    $pdf->Row($headers,$widths,array_fill(0,count($headers),'C'));

    // Datos
    $pdf->SetFont('Arial','',6);

    foreach($certificados as $c){

        // Cargo según API
        $cargo = isset($personalPorDNI[$c['dni']]) ? $personalPorDNI[$c['dni']]['Carg_Descripcion'] : '-';

        // Modo de descarga según tipo de certificado
        $modo_descarga = '-';
        if(isset($c['tipo_certificado'])){
            if($c['tipo_certificado'] === 'TOKEN_SOFTWARE' || $c['tipo_certificado'] === 'SOFTWARE'){
                $modo_descarga = 'SOFTWARE (PC, laptop)';
            } elseif($c['tipo_certificado'] === 'TOKEN_HARDWARE' || $c['tipo_certificado'] === 'HARDWARE'){
                $modo_descarga = 'HARDWARE (TOKEN, USB)';
            }
        }

        $fechaVenc = $c['fecha_vencimiento'];
        $fechaVencStr = ($fechaVenc instanceof DateTime) 
            ? $fechaVenc->format('d/m/Y') 
            : date('d/m/Y', strtotime($fechaVenc));

        $row = [
            $c['dni'],
            'AV. FÁTIMA N° 431 – URB. LA MERCED - TRUJILLO',
            $c['apellidos'].' '.$c['nombres'],
            $cargo,
            $c['gerencia_laboral'],
            $c['telefono'],
            $c['correo'],
            $c['tipo_certificado'] ?? 'N/A',
            $modo_descarga
        ];

        $pdf->Row($row,$widths);
    }

    // Guardar PDF
    $tmpDir = __DIR__ . '/tmp/';
    if(!file_exists($tmpDir)) mkdir($tmpDir,0777,true);

    $filename = 'certificados_por_vencer_' . date('Ymd_His') . '.pdf';
    $filePath = $tmpDir . $filename;
    $pdf->Output('F',$filePath);

    responderNotificacion('success', 'Operacion completada', 'Pdf generado correctamente', 'index.php?module=certificados&action=certificadosPorVencer1');
break;

case 'enviar':
function formatearMensajeFinal($texto){

    $lineas = preg_split("/\r\n|\n|\r/", trim($texto));

    $html = "";
    $enTabla = false;
    $contador = 1;

    foreach($lineas as $linea){

        $linea = trim($linea);

        if($linea === ""){
            $html .= "<br>";
            continue;
        }

        // 🔹 ENCABEZADOS
        if(stripos($linea, 'Estimad@s') !== false){
            $html .= "<strong>$linea</strong><br><br>";
            continue;
        }

        if(preg_match('/\* PARA MODO (.*)/i', $linea)){

            if($enTabla){
                $html .= "</table><br>";
                $enTabla = false;
                $contador = 1;
            }

            $html .= "<div style='margin-top:0px; margin-bottom:4px;'>
            <strong style='color:#1f3c88;'>$linea</strong>
          </div>";
            continue;
        }

        // 🔹 IGNORAR encabezado pegado de Excel
        if(stripos($linea, 'N°') !== false || stripos($linea, 'Nombre') !== false){
            continue;
        }

        // 🔹 DETECTAR FILAS tipo Excel (aunque estén mal pegadas)
        if(preg_match('/^\d+$/', $linea)){
            continue; // ignoramos número solo
        }

        // 🔹 NOMBRES (CREA TABLA BIEN ALINEADA)
        if(preg_match('/^[A-ZÁÉÍÓÚÑ ]{5,}$/', $linea)){

            if(!$enTabla){
                $html .= "
                <table style='width:100%; border-collapse:collapse; margin-top:10px; font-family:Arial;'>
                    <tr>
                        <th style='border:1px solid #ccc; padding:8px; background:#1f3c88; color:#fff; text-align:center;'>N°</th>
                        <th style='border:1px solid #ccc; padding:8px; background:#1f3c88; color:#fff; text-align:left;'>Nombre Completo</th>
                    </tr>";
                $enTabla = true;
            }

            $html .= "
                <tr>
                    <td style='border:1px solid #ccc; padding:8px; text-align:center;'>$contador</td>
                    <td style='border:1px solid #ccc; padding:8px;'>$linea</td>
                </tr>";

            $contador++;
            continue;
        }

        // 🔹 TEXTO NORMAL
        if($enTabla){
            $html .= "</table><br>";
            $enTabla = false;
        }

        $html .= "<p style='margin:5px 0;'>$linea</p>";
    }

    if($enTabla){
        $html .= "</table>";
    }

    return $html;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Acceso no permitido');
}

$region = $_POST['region'];
$correos = $_POST['correos'];
$mensaje = formatearMensajeFinal($_POST['mensaje']);
$excel = $_FILES['excel'];
$recibo = $_FILES['recibo'];

try {

    $baseDir = __DIR__ . '/../tramites';

    if (!file_exists($baseDir)) {
        mkdir($baseDir, 0777, true);
    }

    $timestamp = date('Ymd_His');

    $excelPath  = "$baseDir/{$timestamp}_" . basename($excel['name']);
    $reciboPath = "$baseDir/{$timestamp}_" . basename($recibo['name']);

    move_uploaded_file($excel['tmp_name'], $excelPath);
    move_uploaded_file($recibo['tmp_name'], $reciboPath);

    /* CORREO */

    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'franklinuquiche@gmail.com';
    $mail->Password   = 'vfanmcargdsomgzq';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';
    $mail->Encoding   = 'base64';
    $mail->isHTML(true);

    $mail->setFrom($mail->Username, 'Sistema de Certificados');

    foreach ($correos as $correo){
        if(filter_var($correo, FILTER_VALIDATE_EMAIL)){
            $mail->addAddress($correo);
        }
    }

    // ✅ ADJUNTAR ARCHIVOS POR SEPARADO
    if (file_exists($excelPath)) {
        $mail->addAttachment($excelPath, basename($excel['name']));
    }

    if (file_exists($reciboPath)) {
        $mail->addAttachment($reciboPath, basename($recibo['name']));
    }

    $mail->Subject = "Remisión de Documentación – Región $region";

    $mail->Body = "
<div style='font-family:Arial, Helvetica, sans-serif; background-color:#f4f6f9; padding:30px;'>
    <div style='max-width:650px; margin:auto; background:#ffffff; border-radius:8px; padding:35px; box-shadow:0 2px 8px rgba(0,0,0,0.05);'>
        
        <div style='text-align:center; margin-bottom:25px;'>
            <h2 style='margin:0; color:#1f3c88;'>CONSUMO ELECTRÓNICO</h2>
            <p style='margin:6px 0 0 0; font-size:14px; color:#555;'>
                Sistema de Gestión de Certificados Digitales<br>
                Proyecto Especial Chavimochic
            </p>
        </div>

        <div style='font-size:14px; color:#333; line-height:1.6;'>
            <p>
                <strong>Región:</strong> $region<br>
                <strong>Fecha:</strong> " . date('d/m/Y') . "
            </p>

            <p>
                A la Sede Superior correspondiente:
            </p>

            <p>
                Tengo el agrado de dirigirme a usted para saludarle cordialmente y, a la vez,
                remitir la información relacionada al trámite de Certificados Digitales
                gestionado por esta dependencia.
            </p>

            <div style='background:#f8f9fa; padding:18px; border-left:4px solid #1f3c88; margin:20px 0;'>
    <strong>Detalle del Trámite:</strong><br><br>
    $mensaje
</div>

            <p>
                Se adjuntan los siguientes documentos:<br>
                1.- Lista de usuarios (Excel).<br>
                2.- Boleta de pago 200 certificados (PDF).<br>
                Documentación para su evaluación y acciones administrativas correspondientes.
            </p>

            <p>
                Sin otro particular, reitero a usted las muestras de mi especial
                consideración y estima institucional.
            </p>

            <p style='margin-top:35px;'>
                Atentamente,<br><br>
                <strong>Proyecto Especial Chavimochic</strong><br>
                Sistema de Gestión de Certificados Digitales
            </p>
        </div>

        <hr style='margin-top:30px; border:none; border-top:1px solid #e0e0e0;'>

        <p style='font-size:12px; color:#888; text-align:center;'>
            Comunicación generada automáticamente a través del sistema institucional.<br>
            Este mensaje constituye una notificación electrónica oficial.
        </p>

    </div>
</div>
";

    $mail->send();

    responderNotificacion('success', 'Operacion completada', 'Tramite enviado correctamente', 'index.php?module=certificados&action=tramite');

} catch (Exception $e) {
    responderNotificacion('danger', 'Ocurrio un problema', 'Error: ' . $e->getMessage(), 'index.php?module=certificados&action=tramite');
}

break;

    case 'tramite':

  require_once __DIR__ . '/../models/CertificadosModel.php';;

    include __DIR__ . '/../views/tramite.php';

break;

case 'eliminar1':

$id = $_GET['id'];

$resultado = $model->eliminar1($id);

if(esPeticionAjax()){
    responderJSON([
        'success' => (bool)$resultado,
        'message' => $resultado ? 'Persona eliminada correctamente' : 'Error al eliminar la persona',
    ]);
}

responderNotificacion('success', 'Operacion completada', 'Persona eliminada correctamente', 'index.php?module=certificados&action=verPersonas1');
break;

case 'eliminarBackup':

$id = $_GET['id'];
$archivo = basename($_GET['archivo']);

$ruta = __DIR__ . '/../../uploads/backups/' . $archivo;

if(!empty($archivo) && file_exists($ruta)){
    unlink($ruta);
}

$resultado = $model->eliminarBackup($id);

if(esPeticionAjax()){
    responderJSON([
        'success' => (bool)$resultado,
        'message' => $resultado ? 'Backup eliminado correctamente' : 'Error al eliminar el backup',
    ]);
}

if($resultado){
    responderNotificacion('success', 'Operacion completada', 'Backup eliminado correctamente', 'index.php?module=certificados');
}else{
    responderNotificacion('danger', 'Ocurrio un problema', 'Error al eliminar el backup', 'index.php?module=certificados');
}

break;

case 'sincronizarAPI':

$url = "https://www.chavimochic.gob.pe/api_incidencias/api_personal.php";

$json = file_get_contents($url);

$sql = "{CALL certificados.SP_SincronizarPersonasAPI(?)}";

$params = [$json];

$stmt = sqlsrv_query($db,$sql,$params);
responderNotificacion('success', 'Operacion completada', 'Sincronizacion completada', 'index.php?module=personas');

break;
case 'detalleModal':
    $id = $_GET['id'] ?? null;
    if (!$id) die("Certificado no especificado");

    $certificado = $model->obtenerPorId1($id);

    $backups = $model->obtenerBackups2($id);

    require 'modules/certificados/views/detalle_modal.php';
    exit;

case 'exportarExcel':


    $tipo = $_GET['tipo'] ?? 'PERSONAL';

// 👇 AQUÍ ESTÁ LA SOLUCIÓN
$certificados = $model->obtenerCertificadosPorVencer2($tipo);
    // --- Obtener datos de la API para el cargo ---
    $apiUrl = "https://www.chavimochic.gob.pe/api_incidencias/api_personal.php";
    $apiResponse = file_get_contents($apiUrl);
    $apiData = json_decode($apiResponse, true);

    // Crear arreglo de personal por DNI
    $personalPorDNI = [];
    if(isset($apiData['data'])){
        foreach($apiData['data'] as $p){
            $personalPorDNI[$p['Documento']] = $p;
        }
    }

    // --- Crear Excel ---
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Certificados');

    // Encabezados
    $headers = ['DNI','DIRECCIÓN LABORAL','APELLIDOS Y NOMBRES','CARGO','ÁREA','CELULAR','EMAIL','TIPO CERT.','MODO DESC.'];
    $sheet->fromArray($headers, NULL, 'A1');

    // Estilo encabezado
    $sheet->getStyle('A1:I1')->getFont()->setBold(true);
    $sheet->getStyle('A1:I1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A1:I1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
          ->getStartColor()->setARGB('FFB0C4DE');

    // Datos
    $rowNum = 2;
    $certificadosIds = []; // Guardar IDs para actualizar su estado
    foreach($certificados as $c){
        $cargo = isset($personalPorDNI[$c['dni']]) ? $personalPorDNI[$c['dni']]['Carg_Descripcion'] : '-';

        $modo_descarga = '-';
        if(isset($c['tipo_certificado'])){
            if($c['tipo_certificado'] === 'TOKEN_SOFTWARE' || $c['tipo_certificado'] === 'SOFTWARE'){
                $modo_descarga = 'SOFTWARE (PC, laptop)';
            } elseif($c['tipo_certificado'] === 'TOKEN_HARDWARE' || $c['tipo_certificado'] === 'HARDWARE'){
                $modo_descarga = 'HARDWARE (TOKEN, USB)';
            }
        }

        $dataRow = [
            $c['dni'],
            'AV. FÁTIMA N° 431 – URB. LA MERCED - TRUJILLO',
            $c['apellidos'].' '.$c['nombres'],
            $cargo,
            $c['gerencia_laboral'],
            $c['telefono'],
            $c['correo'],
            $c['tipo_certificado'] ?? 'N/A',
            $modo_descarga
        ];

        $sheet->fromArray($dataRow, NULL, 'A'.$rowNum);
        $rowNum++;

        if (isset($c['id_certificado'])) {
            $certificadosIds[] = $c['id_certificado'];
        }
    }

    // Ajustar ancho de columnas automáticamente
    foreach(range('A','I') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // --- Guardar en archivo temporal ---
    $tmpDir = __DIR__ . '/tmp/';
    if(!file_exists($tmpDir)) mkdir($tmpDir, 0777, true);

    $filename = 'certificados_por_vencer_' . date('Ymd_His') . '.xlsx';
    $filePath = $tmpDir . $filename;

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save($filePath);

    // --- Actualizar estado_tramite a 'TRAMITADO' mediante método del modelo ---
    if(count($certificadosIds) > 0){
        $model->marcarCertificadosTramitados($certificadosIds);
    }

    // --- Avisar al usuario y redirigir ---
    responderNotificacion(
        'success',
        'Operacion completada',
        'Excel generado correctamente y certificados actualizados a TRAMITADO',
        'index.php?module=certificados&action=certificadosPorVencer1'
    );

break;

case 'restablecerEstados':
    // Llamar al modelo para actualizar los estados
    $model->actualizarEstadosTramite();

    // Avisar al usuario y redirigir
    responderNotificacion(
        'info',
        'Informacion',
        'Certificados activos regresados a NO TRAMITADO y vencidos marcados como TRAMITADO',
        'index.php?module=certificados&action=certificadosPorVencer1'
    );
break;

case 'eliminarArchivosTmp':
    $tmpDir = __DIR__ . '/tmp/';
    if(file_exists($tmpDir)){
        $files = array_diff(scandir($tmpDir), array('.', '..'));
        foreach($files as $file){
            $filePath = $tmpDir . $file;
            if(is_file($filePath)) unlink($filePath);
        }
    }

    if(esPeticionAjax()){
        responderJSON([
            'success' => true,
            'message' => 'Archivos temporales eliminados correctamente',
        ]);
    }

    responderNotificacion(
        'success',
        'Operacion completada',
        'Todos los archivos temporales han sido eliminados',
        'index.php?module=certificados&action=certificadosPorVencer1'
    );
break;

case 'subirBoleta':
    umask(0);

    // 🔧 Función para dar permisos SOLO al archivo
    function darPermisoArchivo($archivo){
        $archivo = escapeshellarg($archivo);
        $comando = "icacls $archivo /grant Todos:F";
        exec($comando);
    }

    if(isset($_FILES['boletaPDF']) && $_FILES['boletaPDF']['error'] == 0){
        $dir = __DIR__ . '/boletas/';

        if(!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        $filename = basename($_FILES['boletaPDF']['name']);
        $target = $dir . $filename;

        if(move_uploaded_file($_FILES['boletaPDF']['tmp_name'], $target)){

            // 🔥 APLICAR PERMISOS SOLO AL ARCHIVO SUBIDO
            darPermisoArchivo($target);

            if(esPeticionAjax()){
                responderJSON([
                    'success' => true,
                    'message' => 'PDF subido correctamente',
                    'filename' => $filename,
                ]);
            }

                        responderNotificacion('success', 'Operacion completada', 'PDF subido correctamente', 'index.php?module=certificados');
        } else {
            if(esPeticionAjax()){
                responderJSON([
                    'success' => false,
                    'message' => 'Error al subir el PDF',
                ]);
            }

                        responderNotificacion('danger', 'Ocurrio un problema', 'Error al subir el PDF', 'index.php?module=certificados');
        }
    } else {
        if(esPeticionAjax()){
            responderJSON([
                'success' => false,
                'message' => 'No se selecciono ningun archivo',
            ]);
        }

                responderNotificacion('warning', 'Atencion', 'No se selecciono ningun archivo', 'index.php?module=certificados');
    }
    exit;
break;

case 'eliminarBoleta':
    if(isset($_POST['archivo'])){
        $dir = __DIR__ . '/boletas/';
        $archivo = basename($_POST['archivo']); // seguridad básica
        $ruta = $dir . $archivo;

        $success = false;
        $message = 'Archivo no especificado';

        if(file_exists($ruta)){
            if(unlink($ruta)){
                $success = true;
                $message = 'PDF eliminado correctamente';
            } else {
                $success = false;
                $message = 'Error al eliminar el archivo';
            }
        } else {
            $success = false;
            $message = 'El archivo no existe';
        }

        if(esPeticionAjax()){
            responderJSON([
                'success' => $success,
                'message' => $message,
            ]);
        }

        responderNotificacion(
            $success ? 'success' : 'danger',
            'Operacion completada',
            $message,
            'index.php?module=certificados'
        );
    } else {
        if(esPeticionAjax()){
            responderJSON([
                'success' => false,
                'message' => 'Archivo no especificado',
            ]);
        }

        responderNotificacion('warning', 'Atencion', 'Archivo no especificado', 'index.php?module=certificados');
    }
    exit;
break;
case 'guardarBackup3':

    umask(0);

    function darPermisoArchivo($archivo){
        $archivo = escapeshellarg($archivo);
        $comando = "icacls $archivo /grant Todos:F";
        exec($comando);
    }

    $id_certificado = $_POST['id_certificado'];
    $file = $_FILES['archivo'];

    if(isset($file) && $file['error'] == 0){

        $cert = $model->getCertificadoById($id_certificado);

        // 🔥 RUTA FÍSICA REAL (IMPORTANTE)
        $folder = __DIR__ . "/../../uploads/backups/";

        if(!file_exists($folder)){
            mkdir($folder, 0777, true);
        }

        $fileName = uniqid("backup_") . ".pfx";
        $path = $folder . $fileName;

        if(move_uploaded_file($file['tmp_name'], $path)){

            // 🔥 PERMISOS CORRECTOS EN WINDOWS
            darPermisoArchivo($path);

            $data = [
                'codigo_reloj' => $cert['codigo_reloj'],
                'identificador' => uniqid('BKP_'),
                'ruta_archivo' => $fileName,
                'id_certificado' => $id_certificado,
                'id_usuario_backup' => (obtenerIdUsuarioSesion() ?? 0)
            ];

            if ($data['id_usuario_backup'] <= 0) {
                responderNotificacion('danger', 'Ocurrio un problema', 'No se pudo identificar el usuario en sesión.', 'index.php?module=certificados&action=index&id=' . $id_certificado);
            }

            $model->insertBackup($data);

                        responderNotificacion('success', 'Operacion completada', 'Backup subido correctamente', 'index.php?module=certificados&action=index&id=' . $id_certificado);

        } else {
                        responderNotificacion('danger', 'Ocurrio un problema', 'Error al subir el backup', 'index.php?module=certificados&action=index&id=' . $id_certificado);
        }

    } else {
                responderNotificacion('warning', 'Atencion', 'No se selecciono archivo', 'index.php?module=certificados&action=index&id=' . $id_certificado);
    }

break;
    // =========================
    // =========================
    // ELIMINAR
    // =========================
    case 'eliminarBackup3':

    $id = $_GET['id'];
    $id_cert = $_GET['cert'];

    $sql = "SELECT ruta_archivo FROM certificados.BackupsCertificados WHERE id_backup = ?";
    $stmt = sqlsrv_query($conn, $sql, [$id]);
    $file = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    $path = "modules/uploads/backups/" . $file['ruta_archivo'];

    if(file_exists($path)){
        unlink($path);
    }

    $model->deleteBackup($id);

        responderNotificacion('success', 'Operacion completada', 'Backup eliminado correctamente', 'index.php?module=certificados&action=index&id=' . $id_cert);

break;

}
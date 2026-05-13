<?php
/**
 * Endpoint dedicado para servir PDFs binarios del módulo Adquisiciones.
 *
 * Se invoca directamente (no pasa por index.php) para evitar que el layout
 * principal (header, navbar, etc.) contamine la salida con HTML antes de los
 * headers del PDF.
 *
 * URL de uso:
 *   modules/adquisiciones/pdf.php?tipo=especificacion&id=123
 *   modules/adquisiciones/pdf.php?tipo=ficha&id=123
 *   modules/adquisiciones/pdf.php?tipo=orden&id=123
 *   modules/adquisiciones/pdf.php?tipo=verificacion&id=123
 */

// Evitar cualquier output accidental
while (ob_get_level() > 0) {
	ob_end_clean();
}

// Errores nunca al navegador en respuestas binarias
ini_set('display_errors', '0');
error_reporting(E_ALL);

// El endpoint vive en modules/adquisiciones/, subimos 2 niveles para llegar al
// directorio raíz (CHAVIsystems/) donde están config/, core/, modules/, etc.
chdir(dirname(__DIR__, 2));

require_once 'config/config.php';
require_once 'config/db.php';
require_once 'core/Auth.php';

// Verificación de sesión: si el usuario no está logueado, no entregamos el PDF
Auth::check();

require_once 'modules/adquisiciones/helpers.php';

// Mapa de tipo -> [tabla, campos_nombre_archivo]
$tipos = [
	'especificacion' => ['adquisiciones.EspecificacionTecnica', ['Codigo']],
	'ficha'          => ['adquisiciones.FichaTecnica',          ['Marca', 'Modelo']],
	'orden'          => ['adquisiciones.OrdenCompra',           ['NumeroOrden']],
	'verificacion'   => ['adquisiciones.VerificacionTecnica',   ['Observacion']],
];

$tipo = isset($_GET['tipo']) ? (string) $_GET['tipo'] : '';
$id   = isset($_GET['id'])   ? (int) $_GET['id']     : 0;

if (!isset($tipos[$tipo]) || $id <= 0) {
	http_response_code(400);
	exit;
}

[$tabla, $camposNombre] = $tipos[$tipo];

$conn = Conexion::conectar();
if (!$conn) {
	http_response_code(500);
	exit;
}

// Construye SQL con tabla validada (viene del mapa, no del input directo)
$sql = 'SELECT Documento, ' . implode(', ', $camposNombre) . ' FROM ' . $tabla . ' WHERE Id = ?';
$stmt = sqlsrv_query($conn, $sql, [$id]);
$row = $stmt ? sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) : null;

if (!$row || empty($row['Documento'])) {
	http_response_code(404);
	exit;
}

$decoded = base64_decode($row['Documento'], true);
if ($decoded === false || substr($decoded, 0, 5) !== '%PDF-') {
	http_response_code(500);
	exit;
}

// Arma el nombre del archivo a partir de los campos configurados
$partesNombre = [];
foreach ($camposNombre as $campo) {
	$partesNombre[] = isset($row[$campo]) ? (string) $row[$campo] : '';
}
$nombre = preg_replace('/[^a-zA-Z0-9_\-]/', '_', trim(implode('_', $partesNombre), '_'));
if ($nombre === '') {
	$nombre = 'documento';
}

// Guardia final: si por alguna razón algo imprimió antes, lo logueamos y abortamos
// (en lugar de mandar bytes binarios con Content-Type text/html)
if (headers_sent($archivoOrigen, $lineaOrigen)) {
	error_log(sprintf('[adquisiciones/pdf.php] Headers ya enviados en %s:%d', $archivoOrigen, $lineaOrigen));
	http_response_code(500);
	exit;
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $nombre . '.pdf"');
header('Content-Length: ' . strlen($decoded));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

echo $decoded;
exit;
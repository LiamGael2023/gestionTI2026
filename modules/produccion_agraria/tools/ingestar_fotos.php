<?php
set_time_limit(0);
ini_set('memory_limit', '512M');
date_default_timezone_set('America/Lima');

$server = '10.0.100.252';
$conn = sqlsrv_connect($server, [
    'Database' => 'BD_PRODUCCIONDESARROLLO',
    'Uid' => 'sa',
    'PWD' => 'SrvPRU01#$',
    'CharacterSet' => 'UTF-8',
    'TrustServerCertificate' => true,
    'Encrypt' => true
]);

if (!$conn) die("ERROR: no se pudo conectar a BD_PRODUCCIONDESARROLLO\n");

echo str_repeat("=", 70) . "\n";
echo "  INGESTA DE FOTOS - Wikipedia API\n";
echo "  " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 70) . "\n\n";

$opt = ["Scrollable" => SQLSRV_CURSOR_STATIC];

$s = sqlsrv_query($conn,
    "SELECT p.id_producto, p.nombre, p.nombre_cientifico, cl.nombre_clase
     FROM dbo.producto p
     LEFT JOIN dbo.clase cl ON p.id_clase = cl.id_clase
     WHERE p.imagen_blob IS NULL AND p.activo = 1
     ORDER BY p.id_producto",
    [], $opt);

$productos = [];
while ($r = sqlsrv_fetch_array($s, SQLSRV_FETCH_ASSOC)) {
    $productos[] = $r;
}
sqlsrv_free_stmt($s);

$total = count($productos);
echo "Productos sin imagen: $total\n\n";

if ($total === 0) { echo "Nada que hacer.\n"; exit; }

$ok = 0; $skip = 0; $err = 0;
$startTime = microtime(true);

foreach ($productos as $idx => $p) {
    $id = $p['id_producto'];
    $nombre = $p['nombre'];
    $cientifico = $p['nombre_cientifico'];
    $num = $idx + 1;

    echo "[$num/$total] #$id $nombre... ";

    $queries = [];
    $corto = trim(str_ireplace(
        ['PLANTA DE ', 'PLANTA ', 'PLANTAS DE ', 'FRUTA DE ', 'FRUTA ', 'FRUTO DE ', 'FRUTO '],
        '', $nombre));

    if (!empty($cientifico)) {
        $queries[] = $cientifico;
    }
    if ($corto && $corto !== $nombre && strlen($corto) > 2) {
        $queries[] = $corto;
    }
    $queries[] = $nombre;

    $palabras = preg_split('/\s+/', str_replace(['(', ')', ',', '.'], '', $corto));
    $keywords = array_filter($palabras, function ($w) {
        return strlen($w) > 3 && !in_array(strtoupper($w), ['PARA', 'CON', 'LOS', 'LAS', 'DEL', 'POR', 'QUE', 'UNA', 'X']);
    });
    if (count($keywords) >= 2) {
        $queries[] = implode(' ', array_slice($keywords, 0, 2));
    }

    $queries = array_values(array_unique(array_filter($queries)));

    $imgUrl = null;
    $pageTitle = null;

    foreach ($queries as $query) {
        $encoded = urlencode($query);
        $apiUrl = "https://en.wikipedia.org/api/rest_v1/page/summary/$encoded";

        $data = httpGet($apiUrl);
        if (!$data) continue;

        $thumb = $data['thumbnail']['source'] ?? $data['originalimage']['source'] ?? null;
        $title = $data['title'] ?? '';

        if ($thumb) {
            $thumb600 = preg_replace('/\/\d+px-/', '/600px-', $thumb);
            $imgUrl = $thumb;
            $pageTitle = $title . " (wikipedia)";
            break;
        }
    }

    if (!$imgUrl) {
        echo "SIN RESULTADOS\n";
        $skip++;
        continue;
    }

    $imageBinary = httpGetRaw($imgUrl);
    if (!$imageBinary || strlen($imageBinary) < 200) {
        echo "ERROR DESCARGA\n";
        $err++;
        continue;
    }
    if (strlen($imageBinary) > 2500000) {
        $kb = round(strlen($imageBinary) / 1024);
        echo "DEMASIADO GRANDE ({$kb}KB)\n";
        $skip++;
        continue;
    }

    $ext = pathinfo(parse_url($imgUrl, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
    $imageName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $nombre) . '.' . $ext;

    $blob = [$imageBinary, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY('max')];
    $stmt = sqlsrv_query($conn,
        "UPDATE dbo.producto SET imagen_nombre = ?, imagen_blob = ? WHERE id_producto = ?",
        [$imageName, $blob, $id]);

    if ($stmt) {
        $kb = round(strlen($imageBinary) / 1024);
        echo "OK {$pageTitle} ({$kb}KB)\n";
        $ok++;
    } else {
        echo "ERROR BD: " . print_r(sqlsrv_errors(), true) . "\n";
        $err++;
    }

    usleep(500000);
}

$elapsed = round(microtime(true) - $startTime);
echo "\n" . str_repeat("=", 70) . "\n";
echo "  REPORTE FINAL\n";
echo str_repeat("=", 70) . "\n";
echo "  Total: $total\n";
echo "  OK: $ok\n";
echo "  Sin resultados: $skip\n";
echo "  Errores: $err\n";
echo "  Tiempo: {$elapsed}s\n";

sqlsrv_close($conn);

// ═══════════════════════════════════════════════════
function httpGet($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_USERAGENT => 'gestionTI-photo-ingester/1.0 (https://gestionti.chavimochic.gob.pe)'
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    return $resp ? json_decode($resp, true) : null;
}

function httpGetRaw($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'gestionTI-photo-ingester/1.0'
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($code === 200 && $resp) ? $resp : null;
}

<?php
error_reporting(E_ALL);
ini_set('display_errors', '0');

$base_path = realpath(dirname(__FILE__) . '/../../../../');
require_once $base_path . '/config/db.php';

if (!defined('BASE_URL')) {
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    define('BASE_URL', $protocol . $host . '/gestionTI');
}

require_once $base_path . '/core/Auth.php';

Auth::check();

$autoloadLibs = $base_path . '/libs/vendor/autoload.php';
if (!file_exists($autoloadLibs)) {
    http_response_code(500);
    die('No se encontro la libreria de exportacion (libs/vendor/autoload.php)');
}

require_once $autoloadLibs;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Html as HtmlWriter;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$id_muestra = intval($_GET['id_muestra'] ?? 0);
$previewHtml = (isset($_GET['preview_html']) && $_GET['preview_html'] === '1');
if ($id_muestra <= 0) {
    http_response_code(400);
    die('Muestra invalida');
}

$conn = Conexion::conectar();
require_once $base_path . '/modules/laboratorio/models/LaboratorioModel.php';
$labAuthExp = new LaboratorioModel($conn);
$labAuthExp->denegarSiSinPermiso($_SESSION['usuario_id'], '?module=laboratorio&action=muestra', 'exportar');
if (!$conn) {
    http_response_code(500);
    die('Error de conexion');
}

$sqlMuestra = "SELECT TOP 1
                m.Id_Muestra,
                m.Estado,
                m.Valle,
                m.Tipo_Servicio,
                m.Observacion_Muestra,
                m.Fecha_Toma,
                m.Fecha_Analisis,
                m.Fecha_Validacion,
                m.Fecha_Recepcion,
                CONCAT(c.Nombres, ' ', c.Apellido_Paterno, ' ', c.Apellido_Materno) AS Agricultor,
                da.Id_Muestra AS TieneDetalleAgua,
                da.Uso_Agua,
                da.Fuente_Agua,
                da.Nivel_Agua,
                da.Cantidad_Muestra,
                ds.Id_Muestra AS TieneDetalleSuelo,
                ds.Fuente_Riego,
                ds.Profundidad,
                ds.Numero_Submuestras,
                ds.Cantidad_Muestra AS Cantidad_Suelo,
                ds.Cultivo_Anterior,
                ds.Cultivo_Implementado,
                ds.Cultivo_Por_Implementar,
                m.Id_Especialista,
                m.Id_Jefe_Lab,
                CONCAT(ue.nombres, ' ', ue.apellidos) AS Especialista,
                CONCAT(uj.nombres, ' ', uj.apellidos) AS JefeLab
              FROM laboratorio.Muestra_Lab m
              LEFT JOIN laboratorio.Cliente c ON m.Id_Cliente = c.Id_Cliente
              LEFT JOIN laboratorio.Detalle_Agua da ON m.Id_Muestra = da.Id_Muestra AND da.Activo = 1
              LEFT JOIN laboratorio.Detalle_Suelo ds ON m.Id_Muestra = ds.Id_Muestra AND ds.Activo = 1
              LEFT JOIN comun.Usuarios ue ON m.Id_Especialista = ue.id_usuario
              LEFT JOIN comun.Usuarios uj ON m.Id_Jefe_Lab = uj.id_usuario
              WHERE m.Id_Muestra = ? AND m.Activo = 1";
$stmtMuestra = sqlsrv_query($conn, $sqlMuestra, [$id_muestra]);

if ($stmtMuestra === false) {
    http_response_code(500);
    die('Error consultando muestra: ' . print_r(sqlsrv_errors(), true));
}

$muestra = sqlsrv_fetch_array($stmtMuestra, SQLSRV_FETCH_ASSOC);

if (!$muestra) {
    http_response_code(404);
    die('Muestra no encontrada');
}

if (strcasecmp(trim((string)($muestra['Estado'] ?? '')), 'Finalizado') !== 0) {
    http_response_code(409);
    die('Solo se puede exportar muestras finalizadas');
}

// ── Firmas digitales ────────────────────────────────────────────────────────
// Encargado de Laboratorio (Id_Jefe_Lab) → posición IZQUIERDA en el informe
// Analista Jefe (Id_Especialista)        → posición DERECHA en el informe
$firmaEncargadoB64 = null;
$firmaAnalistaB64  = null;

$idJefeLab = intval($muestra['Id_Jefe_Lab'] ?? 0);
if ($idJefeLab > 0) {
    $stmtFE = sqlsrv_query($conn,
        "SELECT TOP 1 Img_Firma FROM laboratorio.Usuario_Lab_Firma WHERE Id_Usuario = ? AND Activo = 1",
        [$idJefeLab]);
    if ($stmtFE) {
        $rowFE = sqlsrv_fetch_array($stmtFE, SQLSRV_FETCH_ASSOC);
        if ($rowFE && !empty($rowFE['Img_Firma'])) $firmaEncargadoB64 = $rowFE['Img_Firma'];
    }
}

$idEspecialista = intval($muestra['Id_Especialista'] ?? 0);
if ($idEspecialista > 0) {
    $stmtFA = sqlsrv_query($conn,
        "SELECT TOP 1 Img_Firma FROM laboratorio.Usuario_Lab_Firma WHERE Id_Usuario = ? AND Activo = 1",
        [$idEspecialista]);
    if ($stmtFA) {
        $rowFA = sqlsrv_fetch_array($stmtFA, SQLSRV_FETCH_ASSOC);
        if ($rowFA && !empty($rowFA['Img_Firma'])) $firmaAnalistaB64 = $rowFA['Img_Firma'];
    }
}

/**
 * Inyecta firmas digitales como imágenes dentro del XLSX (ZIP) después del restore.
 */
$injectFirmasEnXlsx = function ($zipPath, $encargadoB64, $analistaB64, $firmaRow0) {
    if (!$encargadoB64 && !$analistaB64) return;

    $zip = new \ZipArchive();
    if ($zip->open($zipPath) !== true) return;

    $nsXdr = 'http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing';
    $nsA   = 'http://schemas.openxmlformats.org/drawingml/2006/main';
    $nsR   = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    // Caja máxima: ~4 columnas de ancho x 7 filas de alto
    $maxWemu = 3990000;
    $maxHemu = 1900238;
    $emuPerPx = 9525; // 914400 EMU/inch ÷ 96 DPI

    // ─── 1. Decodificar imágenes y calcular dimensiones reales ───────────────
    // col 1 = columna B (encargado, IZQUIERDA)
    // col 6 = columna G (analista,  DERECHA)
    $imagesToAdd = [];
    foreach ([
        ['encargado', $encargadoB64, 1],
        ['analista',  $analistaB64,  6],
    ] as [$tag, $b64, $colFrom]) {
        if (!$b64) continue;
        // Soporta "data:image/...;base64,..." y base64 puro (blob de BD)
        $b64clean = preg_replace('#^data:image/[\w+\-]+;base64,#', '', trim((string)$b64));
        $raw = base64_decode($b64clean, true);
        if ($raw === false || strlen($raw) < 10) continue;
        // Tipo por magic bytes
        $isPng = (substr($raw, 0, 8) === "\x89PNG\r\n\x1a\n");
        $ext   = $isPng ? 'png' : 'jpg';

        // Dimensiones en EMU preservando la relación de aspecto original
        $imgInfo = function_exists('getimagesizefromstring') ? @getimagesizefromstring($raw) : false;
        if ($imgInfo && $imgInfo[0] > 0 && $imgInfo[1] > 0) {
            $emuW = $imgInfo[0] * $emuPerPx;
            $emuH = $imgInfo[1] * $emuPerPx;
            $scale = min($maxWemu / $emuW, $maxHemu / $emuH);
            $cx = (int)round($emuW * $scale);
            $cy = (int)round($emuH * $scale);
        } else {
            // Fallback: proporción 2.5:1 típica de firma
            $cx = 3192000;
            $cy = 1276800;
        }

        $mediaFile = 'xl/media/firma_' . $tag . '.' . $ext;
        $zip->deleteName($mediaFile);
        $zip->addFromString($mediaFile, $raw);
        $imagesToAdd[] = ['tag' => $tag, 'ext' => $ext, 'colFrom' => $colFrom, 'cx' => $cx, 'cy' => $cy];
    }

    if (empty($imagesToAdd)) { $zip->close(); return; }

    // ─── 2. [Content_Types].xml — registrar extensiones de imagen ────────────
    $ctXml = $zip->getFromName('[Content_Types].xml');
    if ($ctXml !== false) {
        $ctChanged = false;
        foreach (['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg'] as $e => $mime) {
            if (strpos($ctXml, 'Extension="' . $e . '"') === false) {
                $ctXml    = str_replace('</Types>', '<Default Extension="' . $e . '" ContentType="' . $mime . '"/></Types>', $ctXml);
                $ctChanged = true;
            }
        }
        if ($ctChanged) {
            $zip->deleteName('[Content_Types].xml');
            $zip->addFromString('[Content_Types].xml', $ctXml);
        }
    }

    // ─── 3. drawing1.xml.rels — agregar relaciones de imagen ─────────────────
    $relsPath = 'xl/drawings/_rels/drawing1.xml.rels';
    $relsXml  = $zip->getFromName($relsPath);
    if ($relsXml === false || trim($relsXml) === '') {
        $relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                 . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>';
    }
    preg_match_all('/Id="rId(\d+)"/i', $relsXml, $m2);
    $maxId   = empty($m2[1]) ? 50 : max(array_map('intval', $m2[1]));
    $relType = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/image';
    foreach ($imagesToAdd as &$info) {
        $maxId++;
        $info['rId'] = 'rId' . $maxId;
        $relsXml = str_replace(
            '</Relationships>',
            '<Relationship Id="' . $info['rId'] . '" Type="' . $relType
                . '" Target="../media/firma_' . $info['tag'] . '.' . $info['ext'] . '"/></Relationships>',
            $relsXml
        );
    }
    unset($info);
    $zip->deleteName($relsPath);
    $zip->addFromString($relsPath, $relsXml);

    // ─── 4. drawing1.xml — inyectar anclas con DOMDocument (oneCellAnchor) ───
    // oneCellAnchor: la imagen mantiene su tamaño real y no se estira
    $drawingPath = 'xl/drawings/drawing1.xml';
    $drawingXml  = $zip->getFromName($drawingPath);
    if ($drawingXml === false) { $zip->close(); return; }

    $dom = new \DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = true;
    if (!@$dom->loadXML($drawingXml)) { $zip->close(); return; }
    $root = $dom->documentElement;

    if ($root->lookupNamespaceURI('a') === null) {
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:a', $nsA);
    }
    if ($root->lookupNamespaceURI('r') === null) {
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:r', $nsR);
    }

    $picId = 9000;
    foreach ($imagesToAdd as $info) {
        $picId++;
        $cx = $info['cx'];
        $cy = $info['cy'];
        // oneCellAnchor: posición fija desde celda inicial + dimensiones absolutas
        $anchorXml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<xdr:oneCellAnchor'
            .   ' xmlns:xdr="' . $nsXdr . '"'
            .   ' xmlns:a="'   . $nsA   . '"'
            .   ' xmlns:r="'   . $nsR   . '"'
            . '>'
            .   '<xdr:from>'
            .     '<xdr:col>'    . $info['colFrom'] . '</xdr:col>'
            .     '<xdr:colOff>114300</xdr:colOff>'
            .     '<xdr:row>'    . $firmaRow0       . '</xdr:row>'
            .     '<xdr:rowOff>114300</xdr:rowOff>'
            .   '</xdr:from>'
            .   '<xdr:ext cx="' . $cx . '" cy="' . $cy . '"/>'
            .   '<xdr:pic>'
            .     '<xdr:nvPicPr>'
            .       '<xdr:cNvPr id="' . $picId . '" name="Firma_' . htmlspecialchars($info['tag'], ENT_XML1, 'UTF-8') . '"/>'
            .       '<xdr:cNvPicPr><a:picLocks noChangeAspect="1"/></xdr:cNvPicPr>'
            .     '</xdr:nvPicPr>'
            .     '<xdr:blipFill>'
            .       '<a:blip r:embed="' . $info['rId'] . '"/>'
            .       '<a:stretch><a:fillRect/></a:stretch>'
            .     '</xdr:blipFill>'
            .     '<xdr:spPr>'
            .       '<a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm>'
            .       '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom>'
            .     '</xdr:spPr>'
            .   '</xdr:pic>'
            .   '<xdr:clientData/>'
            . '</xdr:oneCellAnchor>';

        $anchorDoc = new \DOMDocument();
        if (@$anchorDoc->loadXML($anchorXml)) {
            $node = $dom->importNode($anchorDoc->documentElement, true);
            $root->appendChild($node);
        }
    }

    $zip->deleteName($drawingPath);
    $zip->addFromString($drawingPath, $dom->saveXML());
    $zip->close();
};

$formatNumero = function ($valor) {
    if ($valor === null || $valor === '') {
        return '';
    }
    if (!is_numeric($valor)) {
        return trim((string)$valor);
    }
    $num = (float)$valor;
    if (floor($num) == $num) {
        return (string)((int)$num);
    }
    return rtrim(rtrim(number_format($num, 4, '.', ''), '0'), '.');
};

$sqlParametros = "SELECT
                    pa.Id_Parametro,
                    pa.Categoria,
                    pa.Nombre AS Parametro,
                    ISNULL(um.Abreviatura, pa.Unidad_Medida) AS UnidadParametro,
                    pa.Metodo_Utilizado,
                    pa.Tipo_Parametro,
                    CASE pa.Categoria WHEN 'Fisico' THEN 1 WHEN 'Quimico' THEN 2 WHEN 'Microbiologico' THEN 3 ELSE 4 END AS OrderCat
                  FROM laboratorio.Parametro_Analisis pa
                  LEFT JOIN laboratorio.Unidad_Medida um ON pa.Id_Unidad_Medida = um.Id_Unidad_Medida AND um.Activo = 1
                  WHERE pa.Activo = 1
                  ORDER BY OrderCat, pa.Nombre";
$stmtParametros = sqlsrv_query($conn, $sqlParametros);
if (!$stmtParametros) {
    http_response_code(500);
    die('No se pudo obtener parametros');
}

$parametros = [];
$idsParametros = [];
while ($p = sqlsrv_fetch_array($stmtParametros, SQLSRV_FETCH_ASSOC)) {
    $parametros[] = $p;
    $idsParametros[intval($p['Id_Parametro'])] = true;
}

$sqlResultados = "SELECT
                    ra.Id_Parametro,
                    MAX(CAST(ra.Valor_Hallado AS NVARCHAR(255))) AS Valor_Hallado
                  FROM laboratorio.Resultado_Analisis ra
                  INNER JOIN laboratorio.Solicitud_Analisis sa ON ra.Id_Solicitud_Analisis = sa.Id_Solicitud_Analisis
                  WHERE sa.Id_Muestra = ?
                    AND sa.Activo = 1
                    AND ra.Activo = 1
                  GROUP BY ra.Id_Parametro";
$stmtResultados = sqlsrv_query($conn, $sqlResultados, [$id_muestra]);
if (!$stmtResultados) {
    http_response_code(500);
    die('No se pudo obtener resultados');
}

$resultadosPorParametro = [];
while ($r = sqlsrv_fetch_array($stmtResultados, SQLSRV_FETCH_ASSOC)) {
    $resultadosPorParametro[intval($r['Id_Parametro'])] = trim((string)($r['Valor_Hallado'] ?? ''));
}

$limitesPorParametro = [];
$normativas = [];
if (!empty($idsParametros)) {
    $paramIds = array_keys($idsParametros);
    $ph = implode(',', array_fill(0, count($paramIds), '?'));

    $sqlLimites = "SELECT
                     l.Id_Parametro,
                     l.Id_Normativa,
                     n.Nombre AS Normativa,
                     n.Descripcion AS DescripcionNormativa,
                     l.Unidad_Medida,
                     l.Valor_Max,
                     l.Valor_Min
                   FROM laboratorio.Limite_Legal l
                   INNER JOIN laboratorio.Normativa_Legal n ON l.Id_Normativa = n.Id_Normativa
                   WHERE l.Activo = 1
                     AND l.Id_Parametro IN ($ph)
                   ORDER BY l.Id_Normativa, l.Id_Parametro";

    $stmtLimites = sqlsrv_query($conn, $sqlLimites, $paramIds);
    if ($stmtLimites) {
        while ($l = sqlsrv_fetch_array($stmtLimites, SQLSRV_FETCH_ASSOC)) {
            $idp = intval($l['Id_Parametro']);
            $idn = intval($l['Id_Normativa']);
            if (!isset($limitesPorParametro[$idp])) {
                $limitesPorParametro[$idp] = [];
            }
            $limitesPorParametro[$idp][$idn] = $l;

            if (!isset($normativas[$idn])) {
                $normativas[$idn] = [
                    'id' => $idn,
                    'descripcion' => trim((string)($l['DescripcionNormativa'] ?? 'NORMATIVA')),
                    'nombre' => trim((string)($l['Normativa'] ?? '-')),
                ];
            }
        }
    }
}

if (!empty($normativas)) {
    ksort($normativas);
    $normativas = array_values($normativas);
} else {
    $normativas = [[
        'id' => 0,
        'descripcion' => 'NORMATIVA',
        'nombre' => '-',
    ]];
}

$formatLimite = function ($limite) use ($formatNumero) {
    if (!$limite) {
        return '-';
    }
    $min = $formatNumero($limite['Valor_Min'] ?? null);
    $max = $formatNumero($limite['Valor_Max'] ?? null);
    $hasMin = $min !== '';
    $hasMax = $max !== '';
    if ($hasMin && $hasMax) {
        return $min . '-' . $max;
    }
    if ($hasMax) {
        return $max;
    }
    if ($hasMin) {
        return $min;
    }
    return '-';
};

$normalizar = function ($txt) {
    $txt = trim((string)$txt);
    $txt = str_replace(
        ['Á', 'É', 'Í', 'Ó', 'Ú', 'á', 'é', 'í', 'ó', 'ú', 'Ñ', 'ñ'],
        ['A', 'E', 'I', 'O', 'U', 'a', 'e', 'i', 'o', 'u', 'N', 'n'],
        $txt
    );
    $txt = preg_replace('/\s+/', ' ', $txt);
    return strtoupper($txt);
};

$sheetData = [];
foreach ($parametros as $p) {
    // Filtrar parámetros según tipo de muestra (Agua/Suelo/Ambos)
    // $esSuelo se determina más abajo; guardamos todos y filtramos al agrupar
    $tipo = trim((string)($p['Tipo_Parametro'] ?? 'Ambos'));
    if ($tipo === '') $tipo = 'Ambos';
    $p['_Tipo'] = $tipo;
    
    $idp = intval($p['Id_Parametro']);
    $limites = $limitesPorParametro[$idp] ?? [];

    $resultado = $resultadosPorParametro[$idp] ?? '';
    $resultado = ($resultado === '' ? '-' : $formatNumero($resultado));

    $normCols = [];
    foreach ($normativas as $normativa) {
        $limite = $limites[$normativa['id']] ?? null;
        $unidad = trim((string)($limite['Unidad_Medida'] ?? ($p['UnidadParametro'] ?? '-')));
        if ($unidad === '') {
            $unidad = '-';
        }
        $normCols[] = [
            'unidad' => $unidad,
            'limite' => $formatLimite($limite),
        ];
    }

    $sheetData[] = [
        'id_parametro' => $idp,
        'categoria' => trim((string)($p['Categoria'] ?? 'Sin categoria')),
        'parametro' => trim((string)($p['Parametro'] ?? '-')),
        'metodo' => trim((string)($p['Metodo_Utilizado'] ?? '-')),
        'normativas' => $normCols,
        'resultado' => $resultado,
        '_Tipo' => $tipo
    ];
}

$agricultor = trim((string)($muestra['Agricultor'] ?? '-'));
$valle = trim((string)($muestra['Valle'] ?? '-'));
$fuente = trim((string)($muestra['Fuente_Agua'] ?? '-'));
$nivelAgua = trim((string)($muestra['Nivel_Agua'] ?? '-'));
$uso = trim((string)($muestra['Uso_Agua'] ?? '-'));
$fuenteRiego = trim((string)($muestra['Fuente_Riego'] ?? '-'));
$profundidadSuelo = trim((string)($muestra['Profundidad'] ?? '-'));
$numeroSubmuestras = trim((string)($muestra['Numero_Submuestras'] ?? '-'));
$cultivoAnterior = trim((string)($muestra['Cultivo_Anterior'] ?? ''));
$cultivoImplementado = trim((string)($muestra['Cultivo_Implementado'] ?? ''));
$cultivoPorImplementar = trim((string)($muestra['Cultivo_Por_Implementar'] ?? ''));
$cantidad = $muestra['Cantidad_Muestra'] ?? $muestra['Cantidad_Suelo'] ?? '-';
$tipoServicio = trim((string)($muestra['Tipo_Servicio'] ?? '-'));
$especialista = trim((string)($muestra['Especialista'] ?? '-'));
$jefeLab = trim((string)($muestra['JefeLab'] ?? '-'));
$observaciones = trim((string)($muestra['Observacion_Muestra'] ?? ''));

// Formatear observaciones de recepción: checklist + observación extra
$observacionFormateada = $observaciones;
if ($observaciones !== '') {
    $marker = '[RECEPCION]';
    $posMarker = strpos($observaciones, $marker);

    if ($posMarker !== false) {
        $observacionExtra = trim(substr($observaciones, 0, $posMarker));
        $jsonRaw = trim(substr($observaciones, $posMarker + strlen($marker)));

        $recepcion = json_decode($jsonRaw, true);
        if (!is_array($recepcion)) {
            $ini = strpos($jsonRaw, '{');
            $fin = strrpos($jsonRaw, '}');
            if ($ini !== false && $fin !== false && $fin >= $ini) {
                $recepcion = json_decode(substr($jsonRaw, $ini, $fin - $ini + 1), true);
            }
        }

        $lineas = [];
        if (is_array($recepcion) && !empty($recepcion['items']) && is_array($recepcion['items'])) {
            $negativos = [
                'Muestra correctamente rotulada' => 'La muestra no se encuentra correctamente rotulada',
                'Envase limpio y adecuado' => 'El envase se encuentra sucio e inadecuado',
                'Cantidad suficiente de muestra' => 'La cantidad de muestra es insuficiente',
                'Muestra sin contaminación visible' => 'La muestra presenta contaminación visible',
                'Ficha de muestreo completa' => 'La ficha de muestreo está incompleta',
                'Información legible y coherente' => 'La información es ilegible o incoherente'
            ];

            foreach ($recepcion['items'] as $it) {
                $itemTexto = trim((string)($it['item'] ?? ''));
                if ($itemTexto === '') {
                    continue;
                }
                $cumple = !empty($it['cumple']);
                $lineas[] = $cumple
                    ? ('- ' . $itemTexto)
                    : ('- ' . ($negativos[$itemTexto] ?? ('No cumple: ' . $itemTexto)));
            }
        }

        if ($observacionExtra !== '') {
            if (!empty($lineas)) {
                $lineas[] = '';
            }
            $lineas[] = $observacionExtra;
        }

        $observacionFormateada = !empty($lineas) ? implode("\n", $lineas) : ($observacionExtra !== '' ? $observacionExtra : '-');
    }
}

$parseSqlsrvDate = function ($value) {
    if ($value instanceof DateTime) {
        return $value;
    }
    $txt = trim((string)$value);
    if ($txt === '') {
        return null;
    }
    // SQL Server suele devolver: 2026-04-01 09:09:12.850
    $dt = DateTime::createFromFormat('Y-m-d H:i:s.u', $txt);
    if ($dt instanceof DateTime) {
        return $dt;
    }
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $txt);
    if ($dt instanceof DateTime) {
        return $dt;
    }
    try {
        return new DateTime($txt);
    } catch (Exception $e) {
        return null;
    }
};

$fechaTomaObj = $parseSqlsrvDate($muestra['Fecha_Toma'] ?? null);
$fechaFirmaObj = $parseSqlsrvDate($muestra['Fecha_Validacion'] ?? null);
$fechaAnalisisObj = $parseSqlsrvDate($muestra['Fecha_Analisis'] ?? null);

$fechaToma = $fechaTomaObj ? $fechaTomaObj->format('d/m/Y') : '-';
$fechaEmision = $fechaFirmaObj ? $fechaFirmaObj->format('d/m/Y') : ($fechaAnalisisObj ? $fechaAnalisisObj->format('d/m/Y') : date('d/m/Y'));

$mesesES = [
    1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL', 5 => 'MAYO', 6 => 'JUNIO',
    7 => 'JULIO', 8 => 'AGOSTO', 9 => 'SETIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE'
];
$baseFirma = $fechaFirmaObj ?: ($fechaAnalisisObj ?: new DateTime());
$fechaFirmaMesAnio = ($mesesES[intval($baseFirma->format('n'))] ?? strtoupper($baseFirma->format('F'))) . ' ' . $baseFirma->format('Y');

$countDetalle = function ($tabla) use ($conn, $id_muestra) {
    $sql = "SELECT COUNT(1) AS Total FROM laboratorio.$tabla WHERE Id_Muestra = ? AND Activo = 1";
    $stmt = sqlsrv_query($conn, $sql, [$id_muestra]);
    if ($stmt === false) {
        return 0;
    }
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    return intval($row['Total'] ?? 0);
};

$countAgua = $countDetalle('Detalle_Agua');
$countSuelo = $countDetalle('Detalle_Suelo');

$isFilled = function ($value) {
    $txt = trim((string)$value);
    if ($txt === '' || $txt === '-') {
        return false;
    }
    $up = strtoupper($txt);
    return $up !== 'NULL' && $up !== 'N/A';
};

$hasDetalleAgua = !empty($muestra['TieneDetalleAgua']);
$hasDetalleSuelo = !empty($muestra['TieneDetalleSuelo']);
$hasDataAgua = (
    $isFilled($muestra['Uso_Agua'] ?? null) ||
    $isFilled($muestra['Fuente_Agua'] ?? null) ||
    $isFilled($muestra['Nivel_Agua'] ?? null) ||
    $isFilled($muestra['Cantidad_Muestra'] ?? null)
);
$hasDataSuelo = (
    $isFilled($muestra['Fuente_Riego'] ?? null) ||
    $isFilled($muestra['Profundidad'] ?? null) ||
    $isFilled($muestra['Numero_Submuestras'] ?? null) ||
    $isFilled($muestra['Cantidad_Suelo'] ?? null)
);

$tipoForzado = strtolower(trim((string)($_GET['tipo_muestra'] ?? '')));

if ($tipoForzado === 'agua') {
    $esSuelo = false;
} elseif ($tipoForzado === 'suelo') {
    $esSuelo = true;
} elseif ($countAgua > 0) {
    // Priorizar agua cuando exista detalle activo de agua.
    // Evita que registros mixtos caigan por error en plantilla de suelo.
    $esSuelo = false;
} elseif ($countSuelo > 0) {
    $esSuelo = true;
} elseif ($hasDataAgua && !$hasDataSuelo) {
    $esSuelo = false;
} elseif ($hasDataSuelo && !$hasDataAgua) {
    $esSuelo = true;
} elseif ($hasDetalleAgua && !$hasDetalleSuelo) {
    $esSuelo = false;
} elseif ($hasDetalleSuelo && !$hasDetalleAgua) {
    $esSuelo = true;
} elseif ($hasDataAgua) {
    // Caso ambiguo: priorizar agua para respetar plantilla azul de resultados de agua.
    $esSuelo = false;
} else {
    // Fallback por contenido, en caso de registros incompletos.
    $esSuelo = (
        ($profundidadSuelo !== '' && $profundidadSuelo !== '-') ||
        ($numeroSubmuestras !== '' && $numeroSubmuestras !== '-') ||
        ($fuenteRiego !== '' && $fuenteRiego !== '-')
    );
}

$plantillaDir = $base_path . '/modules/laboratorio/muestra/plantilla';
$resolverPlantilla = function ($dir, array $preferidas, array $keywords) {
    foreach ($preferidas as $nombre) {
        $ruta = $dir . '/' . $nombre;
        if (file_exists($ruta)) {
            return $ruta;
        }
    }

    $plantillas = glob($dir . '/*.xlsx');
    $plantillas = array_values(array_filter($plantillas ?: [], function ($p) {
        $base = strtoupper((string)basename($p));
        return strpos($base, '~$') !== 0 && strpos($base, 'RESIDUOS') === false;
    }));

    foreach ($plantillas as $p) {
        $base = strtoupper((string)basename($p));
        foreach ($keywords as $kw) {
            if (strpos($base, strtoupper($kw)) !== false) {
                return $p;
            }
        }
    }

    return $plantillas[0] ?? null;
};

if ($esSuelo) {
    $plantillaPath = $resolverPlantilla(
        $plantillaDir,
        ['CSJ-DRDYCS-LAYS – R - 1-RESULTADOS ANALISIS DE  SUELOS.xlsx'],
        ['SUELO', 'SUELOS']
    );
} else {
    $plantillaPath = $resolverPlantilla(
        $plantillaDir,
        [
            'CSJ-DRDYCS-LAYS – R - 2- RESULTADOS ANALISIS DE AGUAS.xlsx',
            'AGUAS SUBTERRANEAS - MONITOREO.xlsx'
        ],
        ['AGUA', 'AGUAS']
    );
}

if (!$plantillaPath || !file_exists($plantillaPath)) {
    http_response_code(500);
    die('No se encontro la plantilla de exportacion');
}

$reader = new XlsxReader();
$reader->setReadDataOnly(false);
$reader->setIncludeCharts(true);
$spreadsheet = $reader->load($plantillaPath);
if ($esSuelo) {
    $sheet = $spreadsheet->getSheetByName('FORMATO 002 -2024');
    if ($sheet === null) {
        $sheet = $spreadsheet->getSheetByName('FORMATO 002-2024');
    }
    if ($sheet === null) {
        $sheet = $spreadsheet->getActiveSheet();
    }
} else {
    // Para agua, usar la hoja activa por defecto de la plantilla oficial.
    $sheet = $spreadsheet->getActiveSheet();
}

// Agrupación de parámetros por categoría
$categoriasMap = [
    'FISICO' => 'fisicos',
    'FISICOS' => 'fisicos',
    'QUIMICO' => 'quimicos',
    'QUIMICOS' => 'quimicos',
    'MICROBIOLOGICO' => 'microbiologicos',
    'MICROBIOLOGICOS' => 'microbiologicos',
];

$grupos = [
    'fisicos' => [],
    'quimicos' => [],
    'microbiologicos' => [],
];

foreach ($sheetData as $item) {
    // Filtrar por tipo: solo parámetros que aplican a este reporte
    $tipo = $item['_Tipo'] ?? 'Ambos';
    if ($tipo !== 'Ambos' && $tipo !== ($esSuelo ? 'Suelo' : 'Agua')) {
        continue;
    }
    $catNorm = $normalizar($item['categoria']);
    $dest = 'quimicos';
    foreach ($categoriasMap as $needle => $mapTo) {
        if (strpos($catNorm, $needle) !== false) {
            $dest = $mapTo;
            break;
        }
    }
    $grupos[$dest][] = $item;
}

$tipoServUpper = $normalizar($tipoServicio);
$codigoTipoServicio = trim((string)$tipoServicio);
$esInterno = (
    $tipoServUpper === 'INTERNO' ||
    strpos($tipoServUpper, 'INTERNO') !== false ||
    $codigoTipoServicio === '1' ||
    strtoupper($codigoTipoServicio) === 'I'
);
$esExterno = (
    $tipoServUpper === 'EXTERNO' ||
    strpos($tipoServUpper, 'EXTERNO') !== false ||
    $codigoTipoServicio === '2' ||
    strtoupper($codigoTipoServicio) === 'E'
);

$setCheckMark = function ($cell, $enabled) use ($sheet) {
    $sheet->setCellValue($cell, $enabled ? 'X' : '');
    $alignment = $sheet->getStyle($cell)->getAlignment();
    $alignment->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $alignment->setVertical(Alignment::VERTICAL_CENTER);
};

$normalizeStyleText = function ($value) {
    $txt = strtoupper(trim((string)$value));
    $txt = strtr($txt, [
        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
        'Ñ' => 'N'
    ]);
    $txt = preg_replace('/\s+/', ' ', $txt);
    return $txt;
};

$paintBlueHeaders = function ($sheet, $isSuelo) use ($normalizeStyleText) {
    $exactTargets = [
        'VALLE',
        'SOLICITANTE',
        'NIVEL DE AGUA',
        'USO',
        'SERVICIO',
        'INTERNO',
        'EXTERNO',
        'PARAMETRO A EVALUAR',
        'METODO UTILIZADO',
        'NORMATIVA DE PRUEBA',
        'UNIDAD DE MEDIDA',
        'PARAMETRO',
        'RESULTADOS'
    ];

    $prefixTargets = [
        'FINALIDAD DEL ANALISIS',
        'FECHA DE TOMA DE MUESTRA',
        'FECHA EMISION DE RESULTADOS',
        'CLASIFICACION',
        '1. PARAMETROS FISICOS',
        '2. PARAMETROS QUIMICOS',
        '3. PARAMETROS MICROBIOLOGICOS'
    ];

    $categoryPrefixes = [
        '1. PARAMETROS FISICOS',
        '2. PARAMETROS QUIMICOS',
        '3. PARAMETROS MICROBIOLOGICOS'
    ];

    $paintRange = function ($range) use ($sheet) {
        $style = $sheet->getStyle($range);
        $style->getFill()->setFillType(Fill::FILL_SOLID);
        $style->getFill()->getStartColor()->setARGB('FF2F5597');
        $style->getFont()->setBold(true);
        $style->getFont()->getColor()->setARGB(Color::COLOR_WHITE);
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $style->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $style->getBorders()->getAllBorders()->getColor()->setARGB(Color::COLOR_WHITE);
    };

    $mergeMeta = [];
    foreach ($sheet->getMergeCells() as $range) {
        $parts = Coordinate::rangeBoundaries($range);
        $mergeMeta[] = [
            'range' => $range,
            'startCol' => intval($parts[0][0]),
            'startRow' => intval($parts[0][1]),
            'endCol' => intval($parts[1][0]),
            'endRow' => intval($parts[1][1])
        ];
    }

    $usedRanges = [];
    $maxCol = Coordinate::columnIndexFromString('K');
    $maxRow = min(120, $sheet->getHighestRow());
    $categoryStartCol = 'B';
    $categoryEndCol = $isSuelo ? 'K' : 'I';

    for ($r = 1; $r <= $maxRow; $r++) {
        for ($c = 1; $c <= $maxCol; $c++) {
            $col = Coordinate::stringFromColumnIndex($c);
            $coord = $col . $r;
            $txtNorm = $normalizeStyleText($sheet->getCell($coord)->getValue());
            if ($txtNorm === '') {
                continue;
            }

            $isCategory = false;
            foreach ($categoryPrefixes as $prefix) {
                if (strpos($txtNorm, $prefix) === 0) {
                    $isCategory = true;
                    break;
                }
            }

            if ($isCategory) {
                $applyRange = $categoryStartCol . $r . ':' . $categoryEndCol . $r;
                if (!isset($usedRanges[$applyRange])) {
                    $usedRanges[$applyRange] = true;
                    $paintRange($applyRange);
                }
                continue;
            }

            $matched = in_array($txtNorm, $exactTargets, true);
            if (!$matched) {
                foreach ($prefixTargets as $prefix) {
                    if (strpos($txtNorm, $prefix) === 0) {
                        $matched = true;
                        break;
                    }
                }
            }

            if (!$matched) {
                continue;
            }

            $applyRange = $coord;
            foreach ($mergeMeta as $m) {
                if ($c >= $m['startCol'] && $c <= $m['endCol'] && $r >= $m['startRow'] && $r <= $m['endRow']) {
                    $applyRange = $m['range'];
                    break;
                }
            }

            if (isset($usedRanges[$applyRange])) {
                continue;
            }
            $usedRanges[$applyRange] = true;
            $paintRange($applyRange);
        }
    }

    if (!$isSuelo) {
        // Agua: asegurar bloque de normativa totalmente azul en cabecera.
        for ($r = 1; $r <= $maxRow; $r++) {
            $txtNorm = $normalizeStyleText($sheet->getCell('E' . $r)->getValue());
            if (strpos($txtNorm, 'NORMATIVA DE PRUEBA') !== false) {
                $paintRange('E' . $r . ':H' . $r);
                if (($r + 1) <= $maxRow) {
                    $paintRange('E' . ($r + 1) . ':H' . ($r + 1));
                }
                if (($r + 2) <= $maxRow) {
                    $paintRange('E' . ($r + 2) . ':H' . ($r + 2));
                }
                break;
            }
        }
    }
};

if ($esSuelo) {
    // ===== REPORTE SUELO: posiciones fijas R-1 =====
    // 27=header fis, 28=blank, 29-34=data (6 filas)
    // 35=header quim, 36=blank, 37-41=data (5 filas)
    $DATA_FIS_START  = 29;  $DATA_FIS_END  = 34;
    $DATA_QUIM_START = 37;  $DATA_QUIM_END = 41;
    
    $sheet->setCellValue('D12', strtoupper($agricultor));
    $sheet->setCellValue('D20', $fechaToma);
    $sheet->setCellValue('J20', $fechaEmision);
    $sheet->setCellValue('J65', $fechaFirmaMesAnio);

    $textoValle = $normalizar($valle);
    $isChao = strpos($textoValle, 'CHAO') !== false;
    $isViru = strpos($textoValle, 'VIRU') !== false;
    $isMoche = strpos($textoValle, 'MOCHE') !== false;
    $isChicama = strpos($textoValle, 'CHICAMA') !== false;
    $isOtroValle = !$isChao && !$isViru && !$isMoche && !$isChicama && $textoValle !== '-' && $textoValle !== '';
    $setCheckMark('E10', $isChao);
    $setCheckMark('G10', $isViru);
    $setCheckMark('I10', $isMoche);
    $setCheckMark('K10', $isChicama);
    $sheet->setCellValue('E11', ($isOtroValle ? strtoupper(trim((string)$valle)) : ''));

    $textoProf = $normalizar($profundidadSuelo);
    $setCheckMark('F14', strpos($textoProf, '30') !== false);
    $setCheckMark('I14', strpos($textoProf, '60') !== false);
    $setCheckMark('K14', strpos($textoProf, '90') !== false);
    $sheet->setCellValue('D16', ($numeroSubmuestras !== '' ? $numeroSubmuestras : '-'));
    $sheet->setCellValue('E18', $cultivoAnterior);
    $sheet->setCellValue('H18', $cultivoImplementado);
    $sheet->setCellValue('K18', $cultivoPorImplementar);
    $setCheckMark('E22', $esInterno);
    $setCheckMark('I22', $esExterno);

    // ─── Escribir grupo SUELO ───
    $escribirGrupoSuelo = function ($grupo, $dataStart, $dataEnd) use ($sheet, &$shiftAcum) {
        $count = count($grupo);
        if ($count === 0) return;
        
        $available = $dataEnd - $dataStart + 1;
        
        // Insertar filas extra si no caben
        if ($count > $available) {
            $extra = $count - $available;
            $sheet->insertNewRowBefore($dataEnd + 1, $extra);
            $shiftAcum += $extra;
        }
        
        for ($i = 0; $i < $count; $i++) {
            $r = $dataStart + $i;
            $item = $grupo[$i];
            
            // Combinar columnas
            $sheet->mergeCells('B' . $r . ':C' . $r);
            $sheet->mergeCells('D' . $r . ':F' . $r);
            $sheet->mergeCells('H' . $r . ':I' . $r);
            $sheet->mergeCells('J' . $r . ':K' . $r);
            
            $sheet->setCellValue('B' . $r, $item['parametro']);
            $sheet->setCellValue('D' . $r, ($item['metodo'] !== '' ? $item['metodo'] : '-'));
            $sheet->setCellValue('G' . $r, $item['normativas'][0]['unidad'] ?? '-');
            $sheet->setCellValue('H' . $r, ($item['resultado'] !== '' ? $item['resultado'] : '-'));
            $sheet->setCellValue('J' . $r, '');
            
            // Estilo: B:K (sin columna A)
            $sR = $sheet->getStyle('B' . $r . ':K' . $r);
            $sR->getFill()->setFillType(Fill::FILL_SOLID);
            $sR->getFill()->getStartColor()->setARGB(Color::COLOR_WHITE);
            $sR->getFont()->getColor()->setARGB(Color::COLOR_BLACK);
            $sR->getFont()->setBold(false);
            $sR->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sR->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sR->getBorders()->getAllBorders()->getColor()->setARGB('FF999999');
            $sR->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            $sheet->getRowDimension($r)->setRowHeight(25);
        }
        
        // Insertar fila separadora DESPUES del grupo
        $sepRow = $dataStart + $count;
        $sheet->insertNewRowBefore($sepRow, 1);
        $ss = $sheet->getStyle('A' . $sepRow . ':K' . $sepRow);
        $ss->getFill()->setFillType(Fill::FILL_SOLID);
        $ss->getFill()->getStartColor()->setARGB(Color::COLOR_WHITE);
        $ss->getFont()->getColor()->setARGB(Color::COLOR_BLACK);
        $ss->getFont()->setBold(false);
        $ss->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_NONE);
        $sheet->getRowDimension($sepRow)->setRowHeight(4);
        $shiftAcum++;
    };

    // Estilo cabeceras SUELO (merge + estilo, sin tocar el texto de la plantilla)
    $estiloHeaderSuelo = function ($row) use ($sheet) {
        $sh = $sheet->getStyle('B' . $row . ':K' . $row);
        $sh->getFill()->setFillType(Fill::FILL_SOLID);
        $sh->getFill()->getStartColor()->setARGB('FF2F5597');
        $sh->getFont()->getColor()->setARGB(Color::COLOR_WHITE);
        $sh->getFont()->setBold(true);
        $sh->getFont()->setSize(11);
        $sh->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sh->getAlignment()->setIndent(2);
        $sh->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sh->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_NONE);
        $sheet->getRowDimension($row)->setRowHeight(22);
    };

    $shiftAcum = 0;
    $escribirGrupoSuelo($grupos['fisicos'],  $DATA_FIS_START,  $DATA_FIS_END);
    $shiftFisicos = $shiftAcum; // solo inserciones de físicos
    $escribirGrupoSuelo($grupos['quimicos'], $DATA_QUIM_START + $shiftAcum, $DATA_QUIM_END + $shiftAcum);
    // $shiftAcum ahora incluye todo

    // Cabeceras: la de físicos no se mueve, la de químicos solo por físicos
    $estiloHeaderSuelo(27);
    $estiloHeaderSuelo(35 + $shiftFisicos);
    $desfaseFilasSuelo = $shiftAcum;

    // Observación y firma
    $sheet->setCellValue('B' . (43 + $shiftAcum), ($observacionFormateada !== '' ? $observacionFormateada : '-'));
    $sheet->setCellValue('J' . (65 + $shiftAcum), $fechaFirmaMesAnio);
} else {
    // ===== REPORTE AGUA: colocar datos respetando estructura de la plantilla =====
    // Posiciones fijas de la plantilla CSJ-DRDYCS-LAYS – R - 2
    // 28=header fis, 29=blank, 30-35=data (6 filas)
    // 36=header quim, 37=blank, 38-48=data (11 filas)
    // 49=header micro, 50=blank, 51-54=data (4 filas)
    $DATA_FIS_START  = 30;  $DATA_FIS_END  = 35;
    $DATA_QUIM_START = 38;  $DATA_QUIM_END = 48;
    $DATA_MICRO_START= 51;  $DATA_MICRO_END = 54;
    
    $normativasLayout = $normativas;
    if (count($normativasLayout) < 2) {
        $normativasLayout[] = ['id' => 0, 'descripcion' => '-', 'nombre' => '-'];
    }

    $sheet->setCellValue('D12', strtoupper($agricultor));
    $sheet->setCellValue('D18', ($nivelAgua !== '' && $nivelAgua !== '-' ? strtoupper($nivelAgua) : '-'));
    $sheet->setCellValue('D20', $fechaToma);
    $sheet->setCellValue('J20', $fechaEmision);
    $sheet->setCellValue('J79', $fechaFirmaMesAnio);

    $textoValle = $normalizar($valle);
    $isChao = strpos($textoValle, 'CHAO') !== false;
    $isViru = strpos($textoValle, 'VIRU') !== false;
    $isMoche = strpos($textoValle, 'MOCHE') !== false;
    $isChicama = strpos($textoValle, 'CHICAMA') !== false;
    $isOtroValle = !$isChao && !$isViru && !$isMoche && !$isChicama && $textoValle !== '-' && $textoValle !== '';
    $setCheckMark('E10', $isChao);
    $setCheckMark('G10', $isViru);
    $setCheckMark('I10', $isMoche);
    $setCheckMark('K10', $isChicama);
    $sheet->setCellValue('E11', ($isOtroValle ? strtoupper(trim((string)$valle)) : ''));

    $textoFuente = $normalizar($fuente);
    $setCheckMark('F14', strpos($textoFuente, 'SUBTERR') !== false);
    $setCheckMark('I14', strpos($textoFuente, 'SUPERF') !== false);
    $setCheckMark('K14', (strpos($textoFuente, 'OTRO') !== false || ($textoFuente !== 'SUBTERRANEA' && $textoFuente !== 'SUBTERRANEO' && $textoFuente !== 'SUPERFICIAL' && $textoFuente !== '-' && $textoFuente !== '')));

    $textoUso = $normalizar($uso);
    $setCheckMark('F16', strpos($textoUso, 'CONSUMO') !== false);
    $setCheckMark('I16', strpos($textoUso, 'RIEGO') !== false);
    $setCheckMark('K16', strpos($textoUso, 'ANIMAL') !== false);

    $setCheckMark('E22', $esInterno);
    $setCheckMark('I22', $esExterno);

    // Cabecera de normativas (filas 24-26)
    $sheet->setCellValue('E24', strtoupper(trim((string)($normativasLayout[0]['descripcion'] ?? '-'))));
    $sheet->setCellValue('E25', strtoupper(trim((string)($normativasLayout[0]['nombre'] ?? '-'))));
    $sheet->setCellValue('G24', strtoupper(trim((string)($normativasLayout[1]['descripcion'] ?? '-'))));
    $sheet->setCellValue('G25', strtoupper(trim((string)($normativasLayout[1]['nombre'] ?? '-'))));
    $sNorm = $sheet->getStyle('E24:H25');
    $sNorm->getFill()->setFillType(Fill::FILL_SOLID);
    $sNorm->getFill()->getStartColor()->setARGB('FF2F5597');
    $sNorm->getFont()->getColor()->setARGB(Color::COLOR_WHITE);
    $sNorm->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $sNorm->getBorders()->getAllBorders()->getColor()->setARGB(Color::COLOR_WHITE);

    // ─── Escribir grupo de parámetros ───
    $escribirGrupo = function ($grupo, $dataStart, $dataEnd) use ($sheet, &$shiftAcum) {
        $count = count($grupo);
        if ($count === 0) return;
        
        $sheet->getColumnDimension('D')->setWidth(29);
        $available = $dataEnd - $dataStart + 1;
        
        if ($count > $available) {
            $extra = $count - $available;
            $sheet->insertNewRowBefore($dataEnd + 1, $extra);
            $shiftAcum += $extra;
        }
        
        for ($i = 0; $i < $count; $i++) {
            $r = $dataStart + $i;
            $item = $grupo[$i];
            
            $sheet->mergeCells('B' . $r . ':C' . $r);
            $sheet->mergeCells('I' . $r . ':K' . $r);
            
            $sheet->setCellValue('B' . $r, $item['parametro']);
            $sheet->setCellValue('D' . $r, ($item['metodo'] !== '' ? $item['metodo'] : '-'));
            $sheet->setCellValue('E' . $r, $item['normativas'][0]['unidad'] ?? '-');
            $sheet->setCellValue('F' . $r, $item['normativas'][0]['limite'] ?? '-');
            $sheet->setCellValue('G' . $r, $item['normativas'][1]['unidad'] ?? '-');
            $sheet->setCellValue('H' . $r, $item['normativas'][1]['limite'] ?? '-');
            $sheet->setCellValue('I' . $r, ($item['resultado'] !== '' ? $item['resultado'] : '-'));
            
            $sR = $sheet->getStyle('B' . $r . ':K' . $r);
            $sR->getFill()->setFillType(Fill::FILL_SOLID);
            $sR->getFill()->getStartColor()->setARGB(Color::COLOR_WHITE);
            $sR->getFont()->getColor()->setARGB(Color::COLOR_BLACK);
            $sR->getFont()->setBold(false);
            $sR->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sR->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sR->getBorders()->getAllBorders()->getColor()->setARGB('FF999999');
            
            $sheet->getStyle('B' . $r . ':C' . $r)->getFont()->setSize(11);
            $sheet->getStyle('B' . $r . ':C' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            $sheet->getStyle('D' . $r)->getFont()->setSize(9);
            $sheet->getStyle('D' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D' . $r)->getAlignment()->setWrapText(true);
            
            $sheet->getStyle('E' . $r . ':H' . $r)->getFont()->setSize(9);
            $sheet->getStyle('E' . $r . ':H' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            $sheet->getStyle('I' . $r . ':K' . $r)->getFont()->setSize(9);
            $sheet->getStyle('I' . $r . ':K' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
            $sheet->getRowDimension($r)->setRowHeight(36);
        }
    };

    // ─── Estilo cabeceras de categoría ───
    $estiloHeader = function ($row) use ($sheet) {
        $sheet->mergeCells('B' . $row . ':K' . $row);
        $sh = $sheet->getStyle('B' . $row . ':K' . $row);
        $sh->getFill()->setFillType(Fill::FILL_SOLID);
        $sh->getFill()->getStartColor()->setARGB('FF2F5597');
        $sh->getFont()->getColor()->setARGB(Color::COLOR_WHITE);
        $sh->getFont()->setBold(true);
        $sh->getFont()->setSize(11);
        $sh->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sh->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sh->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_NONE);
        $sheet->getRowDimension($row)->setRowHeight(22);
    };
    $shiftAcum = 0;
    $escribirGrupo($grupos['fisicos'],         $DATA_FIS_START,  $DATA_FIS_END);
    $escribirGrupo($grupos['quimicos'],        $DATA_QUIM_START + $shiftAcum, $DATA_QUIM_END + $shiftAcum);
    $escribirGrupo($grupos['microbiologicos'], $DATA_MICRO_START + $shiftAcum, $DATA_MICRO_END + $shiftAcum);

    // Aplicar estilo a cabeceras después de inserciones (posiciones ya ajustadas)
    $estiloHeader(28);
    $estiloHeader(36 + $shiftAcum);
    $estiloHeader(49 + $shiftAcum);

    $sheet->setCellValue('B' . (56 + $shiftAcum), ($observacionFormateada !== '' ? $observacionFormateada : '-'));

}

// Fallback visual: reponer encabezados azules y bordes blancos, sin tocar el pie final.
$paintBlueHeaders($sheet, $esSuelo);

// Barrido final: forzar colores correctos después de paintBlueHeaders
if ($esSuelo) {
    $hFis = 27;
    $hQuim = 35 + $shiftFisicos;
    for ($rr = 27; $rr <= 65 + $shiftAcum; $rr++) {
        $sr = $sheet->getStyle('B' . $rr . ':K' . $rr);
        if ($rr == $hFis || $rr == $hQuim) {
            $sr->getFill()->setFillType(Fill::FILL_SOLID);
            $sr->getFill()->getStartColor()->setARGB('FF2F5597');
            $sr->getFont()->getColor()->setARGB(Color::COLOR_WHITE);
            $sr->getFont()->setBold(true);
            $sr->getFont()->setSize(11);
            $sr->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sr->getAlignment()->setIndent(2);
            $sr->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_NONE);
            $sheet->getRowDimension($rr)->setRowHeight(22);
        } else {
            $sr->getFill()->setFillType(Fill::FILL_SOLID);
            $sr->getFill()->getStartColor()->setARGB(Color::COLOR_WHITE);
            $sr->getFont()->getColor()->setARGB(Color::COLOR_BLACK);
            $sr->getFont()->setBold(false);
        }
    }
} else {
    $estiloHeader(28);
    $estiloHeader(36 + $shiftAcum);
    $estiloHeader(49 + $shiftAcum);
}

$nombreArchivo = ($esSuelo ? 'Analisis_Suelo_Muestra_' : 'Analisis_Agua_Muestra_') . $id_muestra . '.xlsx';

while (ob_get_level() > 0) {
    ob_end_clean();
}

if ($previewHtml) {
    $previewSpreadsheet = clone $spreadsheet;
    $previewSheet = $previewSpreadsheet->getSheet($previewSpreadsheet->getIndex($sheet));

    foreach (range('A', 'K') as $col) {
        $previewSheet->getColumnDimension($col)->setWidth(22);
    }

    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

    $htmlWriter = new HtmlWriter($previewSpreadsheet);
    $htmlWriter->setSheetIndex($previewSpreadsheet->getIndex($previewSheet));
    if (method_exists($htmlWriter, 'setUseInlineCss')) {
        $htmlWriter->setUseInlineCss(true);
    }
    if (method_exists($htmlWriter, 'setEmbedImages')) {
        $htmlWriter->setEmbedImages(true);
    }

    ob_start();
    $htmlWriter->save('php://output');
    $html = ob_get_clean();

    $previewCss = '<style>'
        . 'html,body{margin:0;padding:16px 8px;background:#e8eaf0 !important;font-family:Calibri,Arial,sans-serif;}'
        . 'table{border-collapse:collapse !important;margin:0 auto !important;background:#ffffff !important;'
        . '      box-shadow:0 2px 12px rgba(0,0,0,0.18);}'
        . 'td,th{min-width:20px !important;padding:2px 4px !important;vertical-align:middle !important;}'
        . '</style>';

    if (stripos($html, '</head>') !== false) {
        $html = str_ireplace('</head>', $previewCss . '</head>', $html);
    } else {
        $html = $previewCss . $html;
    }

    // Inyectar firmas como sección HTML al final del reporte
    if ($firmaEncargadoB64 || $firmaAnalistaB64) {
        $firmaHtml = '<div style="font-family:Calibri,Arial,sans-serif;padding:16px 24px;background:#fff;">' .
            '<table style="width:100%;border-collapse:collapse;"><tr>';
        // Izquierda: Encargado de Laboratorio
        $firmaHtml .= '<td style="width:45%;text-align:center;padding:8px;">';
        if ($firmaEncargadoB64) {
            $firmaHtml .= '<img src="' . htmlspecialchars($firmaEncargadoB64, ENT_QUOTES, 'UTF-8') . '" style="max-height:70px;max-width:200px;object-fit:contain;" alt="Firma encargado">';
        }
        $firmaHtml .= '<hr style="border-top:1px solid #333;margin:4px 0;"><div style="font-size:10pt;">' .
            htmlspecialchars($jefeLab ?: '', ENT_QUOTES, 'UTF-8') . '</div>' .
            '<div style="font-size:8pt;color:#555;">ENCARGADO DE LABORATORIO</div></td>';
        // Espacio central
        $firmaHtml .= '<td style="width:10%;"></td>';
        // Derecha: Analista Jefe
        $firmaHtml .= '<td style="width:45%;text-align:center;padding:8px;">';
        if ($firmaAnalistaB64) {
            $firmaHtml .= '<img src="' . htmlspecialchars($firmaAnalistaB64, ENT_QUOTES, 'UTF-8') . '" style="max-height:70px;max-width:200px;object-fit:contain;" alt="Firma analista">';
        }
        $firmaHtml .= '<hr style="border-top:1px solid #333;margin:4px 0;"><div style="font-size:10pt;">' .
            htmlspecialchars($especialista ?: '', ENT_QUOTES, 'UTF-8') . '</div>' .
            '<div style="font-size:8pt;color:#555;">ANALISTA JEFE</div></td>';
        $firmaHtml .= '</tr></table></div>';
        if (stripos($html, '</body>') !== false) {
            $html = str_ireplace('</body>', $firmaHtml . '</body>', $html);
        } else {
            $html .= $firmaHtml;
        }
    }

    echo $html;
    exit;
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header('Cache-Control: max-age=0');

$tmpFile = tempnam(sys_get_temp_dir(), 'xlsx_muestra_');
if ($tmpFile === false) {
    http_response_code(500);
    die('No se pudo crear archivo temporal');
}

$writer = new Xlsx($spreadsheet);
$writer->setPreCalculateFormulas(false);
$writer->save($tmpFile);

// Restaurar solo drawings/media de la plantilla para conservar bloques de color
// definidos como formas sin alterar estilos de celdas generadas.
$srcZip = new ZipArchive();
$dstZip = new ZipArchive();
if ($srcZip->open($plantillaPath) === true && $dstZip->open($tmpFile) === true) {
    for ($i = 0; $i < $srcZip->numFiles; $i++) {
        $name = $srcZip->getNameIndex($i);
        if ($name === false) {
            continue;
        }

        $mustRestore = (
            strpos($name, 'xl/media/') === 0 ||
            strpos($name, 'xl/drawings/') === 0 ||
            $name === 'xl/worksheets/_rels/sheet1.xml.rels'
        );

        if (!$mustRestore) {
            continue;
        }

        $content = $srcZip->getFromName($name);
        if ($content === false) {
            continue;
        }

        $dstZip->deleteName($name);
        $dstZip->addFromString($name, $content);
    }
    $srcZip->close();
    $dstZip->close();
}

// Inyectar firmas digitales en el XLSX generado
// 0-indexed OOXML: row 66 = Excel fila 67 (inicio del espacio vacío de firmas, antes de la línea A/B en fila 74)
// La imagen con cy calculado ocupa ~7 filas y queda justo encima de las líneas A y B
$firmaRow0 = $esSuelo ? (66 + $desfaseFilasSuelo) : (66 + $desfaseFilasAgua);
$injectFirmasEnXlsx($tmpFile, $firmaEncargadoB64, $firmaAnalistaB64, $firmaRow0);

readfile($tmpFile);
@unlink($tmpFile);
exit;

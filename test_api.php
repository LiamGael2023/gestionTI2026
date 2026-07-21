<?php
echo "<h2>Diagnostico API</h2>";
echo "<b>curl_init:</b> " . (function_exists('curl_init') ? 'SI' : 'NO') . "<br>";
echo "<b>allow_url_fopen:</b> " . ini_get('allow_url_fopen') . "<br>";
echo "<b>PHP:</b> " . phpversion() . "<br><hr>";

// RENIEC
echo "<h3>RENIEC (apis.net.pe)</h3>";
$ch = curl_init("https://api.apis.net.pe/v1/dni?numero=75720362");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>10, CURLOPT_SSL_VERIFYPEER=>false]);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);
echo "HTTP: $httpCode | Error: " . ($curlErr?:'ninguno') . "<br>";
echo "<pre>" . htmlspecialchars(substr($resp ?: '(vacio)', 0, 500)) . "</pre>";

// Personal PECH
echo "<h3>Personal PECH (chavimochic.gob.pe)</h3>";
$ch2 = curl_init("https://www.chavimochic.gob.pe/api_incidencias/api_personal.php?documento=75720362");
curl_setopt_array($ch2, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>10, CURLOPT_SSL_VERIFYPEER=>false]);
$resp2 = curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
$curlErr2 = curl_error($ch2);
curl_close($ch2);
echo "HTTP: $httpCode2 | Error: " . ($curlErr2?:'ninguno') . "<br>";
echo "<pre>" . htmlspecialchars(substr($resp2 ?: '(vacio)', 0, 500)) . "</pre>";


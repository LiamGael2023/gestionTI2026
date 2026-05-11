<?php
class FCMService {

    private static $projectId = "sisgeralertas";

    private static function base64UrlEncode($data){
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function enviar($token, $titulo, $mensaje){

       $accessToken = self::getAccessToken();

    // TEMPORAL: ver si hay token
    echo "\n🔑 ACCESS TOKEN: " . ($accessToken ? substr($accessToken, 0, 30) . "..." : "NULL") . "\n";

    if(!$accessToken){
        error_log("NO HAY ACCESS TOKEN");
        return false;
    }
        $url = "https://fcm.googleapis.com/v1/projects/" . self::$projectId . "/messages:send";

        $data = [
            "message" => [
                "token" => $token,
                "notification" => [
                    "title" => $titulo,
                    "body" => $mensaje
                ]
            ]
        ];

        $headers = [
            "Authorization: Bearer " . $accessToken,
            "Content-Type: application/json"
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . '/../certs/cacert.pem');

       $response = curl_exec($ch);

        if($response === false){
            error_log("CURL ERROR: " . curl_error($ch));
            curl_close($ch);
            return false;
        }

       // TEMPORAL: ver respuesta completa
        echo "\n🔍 FCM RESPONSE: " . $response . "\n";

        curl_close($ch);

        // Verificar si FCM aceptó el mensaje
        $decoded = json_decode($response, true);
        if(isset($decoded['name'])){
            return true;  // OK: {"name":"projects/.../messages/..."}
        }

        error_log("FCM ERROR RESPUESTA: " . $response);
        return false;
    }

   private static function getAccessToken(){

    $keyFile = __DIR__ . "/../config/firebase.json";

    if(!file_exists($keyFile)){
        echo "\n❌ NO EXISTE firebase.json en: $keyFile\n";
        return null;
    }
    echo "\n✅ firebase.json encontrado\n";

    $json = json_decode(file_get_contents($keyFile), true);

   $privateKey = str_replace(['\n', '\r\n', '\r'], "\n", $json["private_key"]);
$privateKeyResource = openssl_pkey_get_private($privateKey);

echo "\n🔑 CLAVE CARGADA: " . ($privateKeyResource ? "OK" : "FALLÓ") . "\n";
if(!$privateKeyResource){
    echo "\n❌ OPENSSL: " . openssl_error_string() . "\n";
    return null;
}

    if(!$json){
        echo "\n❌ ERROR leyendo firebase.json\n";
        return null;
    }
    echo "\n✅ firebase.json leído OK\n";

    try {
        $timeResponse = @file_get_contents("https://worldtimeapi.org/api/timezone/America/Lima");
        $timeData = json_decode($timeResponse, true);
        $now = ($timeData && isset($timeData['unixtime'])) ? $timeData['unixtime'] : time();
    } catch (Exception $e) {
        $now = time();
    }
    echo "\n⏰ HORA: $now\n";

    $header = self::base64UrlEncode(json_encode([
        "alg" => "RS256",
        "typ" => "JWT"
    ]));

    $claim = self::base64UrlEncode(json_encode([
        "iss" => $json["client_email"],
        "scope" => "https://www.googleapis.com/auth/firebase.messaging",
        "aud" => $json["token_uri"],
        "exp" => $now + 3600,
        "iat" => $now
    ]));

    $signatureInput = $header . "." . $claim;

    openssl_sign(
        $signatureInput,
        $signature,
        $privateKeyResource,
        "SHA256"
    );
    echo "\n🔐 FIRMA generada: " . ($signature ? strlen($signature) . " bytes" : "VACÍA/FALLÓ") . "\n";
echo "\n🔐 OPENSSL ERROR: " . openssl_error_string() . "\n";

    $jwt = $signatureInput . "." . self::base64UrlEncode($signature);
    echo "\n🔍 JWT HEADER: " . base64_decode(str_pad(strtr(explode('.', $jwt)[0], '-_', '+/'), strlen(explode('.', $jwt)[0]) % 4, '=', STR_PAD_RIGHT)) . "\n";
echo "\n🔍 JWT CLAIM: " . base64_decode(str_pad(strtr(explode('.', $jwt)[1], '-_', '+/'), strlen(explode('.', $jwt)[1]) % 4, '=', STR_PAD_RIGHT)) . "\n";
echo "\n🔍 CLIENT EMAIL: " . $json["client_email"] . "\n";
echo "\n🔍 TOKEN URI: " . $json["token_uri"] . "\n";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $json["token_uri"]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . '/../certs/cacert.pem');
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        "grant_type" => "urn:ietf:params:oauth:grant-type:jwt-bearer",
        "assertion" => $jwt
    ]));

    $raw = curl_exec($ch);

    if($raw === false){
        echo "\n❌ CURL TOKEN ERROR: " . curl_error($ch) . "\n";
        curl_close($ch);
        return null;
    }

    echo "\n📨 TOKEN RESPONSE: $raw\n";

    $response = json_decode($raw, true);
    curl_close($ch);

    if(!isset($response["access_token"])){
        echo "\n❌ ERROR TOKEN GOOGLE: " . json_encode($response) . "\n";
        return null;
    }

    return $response["access_token"];
}}
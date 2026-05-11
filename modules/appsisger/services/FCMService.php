<?php
class FCMService {

    private static $projectId = "sisgeralertas";

    // ✅ BASE64 URL SAFE (NECESARIO PARA GOOGLE)
    private static function base64UrlEncode($data){
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function enviar($token, $titulo, $mensaje){

        $accessToken = self::getAccessToken();

        // 🔍 VALIDACIÓN CLAVE
        if(!$accessToken){
           error_log("❌ NO HAY ACCESS TOKEN");
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
            echo "❌ CURL ERROR: " . curl_error($ch) . "\n";
            curl_close($ch);
            return false;
        }

        curl_close($ch);

        return $response;
    }

    private static function getAccessToken(){

        $keyFile = __DIR__ . "/../config/firebase.json";

        if(!file_exists($keyFile)){
            echo "❌ NO EXISTE firebase.json\n";
            return null;
        }

        $json = json_decode(file_get_contents($keyFile), true);

        if(!$json){
            echo "❌ ERROR leyendo firebase.json\n";
            return null;
        }

        $now = time();

        // ✅ HEADER
        $header = self::base64UrlEncode(json_encode([
            "alg" => "RS256",
            "typ" => "JWT"
        ]));

        // ✅ CLAIM
        $claim = self::base64UrlEncode(json_encode([
            "iss" => $json["client_email"],
            "scope" => "https://www.googleapis.com/auth/firebase.messaging",
            "aud" => $json["token_uri"],
            "exp" => $now + 3600,
            "iat" => $now
        ]));

        $signatureInput = $header . "." . $claim;

        // ✅ FIRMA
        openssl_sign(
            $signatureInput,
            $signature,
            $json["private_key"],
            "SHA256"
        );

        $jwt = $signatureInput . "." . self::base64UrlEncode($signature);

        // 🔗 REQUEST TOKEN
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $json["token_uri"]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // ✅ SSL FIX
        curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . '/../certs/cacert.pem');

        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            "grant_type" => "urn:ietf:params:oauth:grant-type:jwt-bearer",
            "assertion" => $jwt
        ]));

        $raw = curl_exec($ch);

        if($raw === false){
            echo "❌ CURL TOKEN ERROR: " . curl_error($ch) . "\n";
            curl_close($ch);
            return null;
        }

        $response = json_decode($raw, true);

        curl_close($ch);

        if(!isset($response["access_token"])){
            error_log("ERROR TOKEN GOOGLE: " . json_encode($response));
            print_r($response);
            return null;
        }

        return $response["access_token"];
    }
}
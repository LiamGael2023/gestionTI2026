<?php

class FcmService {

    public function send($token, $title, $body) {

        $url = "https://fcm.googleapis.com/fcm/send";

        $fields = [
            "to" => $token,
            "notification" => [
                "title" => $title,
                "body" => $body
            ]
        ];

        $headers = [
            "Authorization: key=TU_SERVER_KEY",
            "Content-Type: application/json"
        ];

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => json_encode($fields)
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return [
            "success" => $error === "",
            "httpCode" => $httpCode,
            "response" => $response,
            "error" => $error
        ];
    }
}
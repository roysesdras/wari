<?php
// /var/www/wari.digiroys.com/tmp/debug_ai.php
require_once __DIR__ . '/../classes/AI.php';

class AIDebug extends AI {
    public function debugGenerate($prompt, $systemInstruction = null) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . ($_ENV['GEMINI_MODEL'] ?? 'gemini-2.0-flash') . ":generateContent?key=" . ($_ENV['GEMINI_API_KEY'] ?? '');

        $payload = [
            "contents" => [
                ["parts" => [["text" => $prompt]]]
            ],
            "generationConfig" => [
                "temperature" => 0.7,
                "responseMimeType" => "application/json"
            ]
        ];

        if ($systemInstruction) {
            $payload["systemInstruction"] = ["parts" => [["text" => $systemInstruction]]];
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            "http_code" => $httpCode,
            "response" => json_decode($response, true)
        ];
    }
}

$ai = new AIDebug();
$test = $ai->debugGenerate("Génère un brouillon pour un cours de gestion de budget. Réponds en JSON.");

echo "HTTP CODE: " . $test['http_code'] . "\n";
print_r($test['response']);

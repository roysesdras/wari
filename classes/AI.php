<?php
// /var/www/wari.digiroys.com/classes/AI.php

class AI
{
    private $apiKey;
    private $model;
    private $baseUrl = "https://generativelanguage.googleapis.com/v1beta/models/";

    public function __construct()
    {
        // Chargement du .env si non chargé
        $this->loadEnv();
        $this->apiKey = $_ENV['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY') ?: '';
        $this->model  = $_ENV['GEMINI_MODEL'] ?? getenv('GEMINI_MODEL') ?: 'gemini-2.0-flash';
    }

    private function loadEnv()
    {
        if (isset($_ENV['GEMINI_API_KEY']) && $_ENV['GEMINI_API_KEY']) return;

        $possiblePaths = [
            '/var/www/wari.digiroys.com/wari-admin/.env',
            '/var/www/html/wari-admin/.env',
            __DIR__ . '/../wari-admin/.env'
        ];

        foreach ($possiblePaths as $envFile) {
            if (file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (strpos($line, '#') === 0 || !strpos($line, '=')) continue;
                    list($name, $value) = explode('=', $line, 2);
                    $name  = trim($name);
                    $value = trim($value, " \t\n\r\0\x0B\"'");
                    $_ENV[$name] = $value;
                    putenv("$name=$value");
                }
                break;
            }
        }
    }

    /**
     * Envoie un prompt à Gemini et retourne la réponse brute (JSON de l'API)
     */
    public function generate($prompt, $systemInstruction = null)
    {
        $url = $this->baseUrl . $this->model . ":generateContent?key=" . $this->apiKey;

        $payload = [
            "contents" => [
                ["parts" => [["text" => $prompt]]]
            ],
            "generationConfig" => [
                "temperature" => 0.7,
                "topP" => 0.95,
                "topK" => 40,
                "maxOutputTokens" => 8192,
                "responseMimeType" => "application/json"
            ]
        ];

        if ($systemInstruction) {
            $payload["systemInstruction"] = [
                "parts" => [["text" => $systemInstruction]]
            ];
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        file_put_contents(__DIR__ . '/../tmp/ai_raw_log.json', $response); // Log pour debug
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return json_encode(["error" => "CURL Error: " . $err]);
        }

        $data = json_decode($response, true);
        $textResponse = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (!$textResponse) {
            return json_encode(["error" => "L'IA n'a pas renvoyé de contenu.", "raw" => $data]);
        }

        return $textResponse;
    }

    /**
     * Méthode spécifique pour Wari Academy avec instructions système prédéfinies
     */
    public function askWari($prompt, $context = "Général")
    {
        $systemText = "Tu es l'assistant IA de Wari Academy, une plateforme d'éducation financière en Afrique. 
        Ton ton est expert, pédagogique, encourageant et direct. 
        Tu utilises des exemples concrets du quotidien africain (marchés, épargne tontine, entrepreneuriat local, mobile money).
        Tu réponds TOUJOURS au format JSON pour être intégré dans une application.
        Contexte actuel : $context";

        return $this->generate($prompt, $systemText);
    }
}

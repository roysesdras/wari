<?php
// /var/www/wari.digiroys.com/classes/AI.php
// Moteur IA principal avec fallback automatique Gemini → Groq

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
     * Méthode publique principale — Essaie Gemini, puis Groq en fallback
     */
    public function generate($prompt, $systemInstruction = null)
    {
        // 1. Essayer Gemini en premier
        $geminiResult = $this->callGemini($prompt, $systemInstruction);

        if ($geminiResult['success']) {
            return $geminiResult['data'];
        }

        // 2. Gemini a échoué → Fallback sur Groq
        $this->logFallback($geminiResult['error']);

        try {
            require_once __DIR__ . '/Groq.php';
            $groq = new Groq();
            $groqResult = $groq->generate($prompt, $systemInstruction);

            // Vérifier que Groq n'a pas aussi échoué
            $decoded = json_decode($groqResult, true);
            if (is_array($decoded) && isset($decoded['error'])) {
                // Les deux providers ont échoué
                $this->logFallback("Groq aussi a échoué: " . $decoded['error']);
                return $geminiResult['data']; // Retourner l'erreur Gemini originale
            }

            return $groqResult;
        } catch (Exception $e) {
            $this->logFallback("Exception Groq: " . $e->getMessage());
            return $geminiResult['data'];
        }
    }

    /**
     * Appel direct à l'API Gemini
     * @return array ['success' => bool, 'data' => string, 'error' => string|null]
     */
    private function callGemini($prompt, $systemInstruction = null)
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        file_put_contents(__DIR__ . '/../tmp/ai_raw_log.json', $response); // Log pour debug
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        // Échec cURL (timeout, DNS, etc.)
        if ($err) {
            return [
                'success' => false,
                'data' => json_encode(["error" => "CURL Error: " . $err]),
                'error' => "cURL: $err"
            ];
        }

        // Échec HTTP (quota dépassé = 429, erreur serveur = 500, etc.)
        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['error']['message'] ?? "HTTP $httpCode";
            return [
                'success' => false,
                'data' => json_encode(["error" => "Gemini Error: " . $errorMsg]),
                'error' => "HTTP $httpCode: $errorMsg"
            ];
        }

        $data = json_decode($response, true);
        $textResponse = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

        // Réponse vide de Gemini
        if (!$textResponse) {
            return [
                'success' => false,
                'data' => json_encode(["error" => "L'IA n'a pas renvoyé de contenu.", "raw" => $data]),
                'error' => "Réponse vide de Gemini"
            ];
        }

        return [
            'success' => true,
            'data' => $textResponse,
            'error' => null
        ];
    }

    /**
     * Log les événements de fallback pour le monitoring
     */
    private function logFallback($reason)
    {
        $logFile = __DIR__ . '/../tmp/ai_fallback_log.txt';
        $timestamp = date('Y-m-d H:i:s');
        $entry = "[$timestamp] FALLBACK Gemini → Groq | Raison: $reason\n";
        file_put_contents($logFile, $entry, FILE_APPEND);
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

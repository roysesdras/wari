<?php
// /var/www/wari.digiroys.com/classes/Groq.php
// Provider Groq (fallback automatique si Gemini échoue)

class Groq
{
    private $apiKey;
    private $model;
    private $baseUrl = "https://api.groq.com/openai/v1/chat/completions";

    public function __construct()
    {
        $this->loadEnv();
        $this->apiKey = $_ENV['GROQ_API_KEY'] ?? getenv('GROQ_API_KEY') ?: '';
        $this->model  = $_ENV['GROQ_MODEL'] ?? getenv('GROQ_MODEL') ?: 'llama-3.3-70b-versatile';
    }

    private function loadEnv()
    {
        if (isset($_ENV['GROQ_API_KEY']) && $_ENV['GROQ_API_KEY']) return;

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
                    if (!isset($_ENV[$name])) {
                        $_ENV[$name] = $value;
                        putenv("$name=$value");
                    }
                }
                break;
            }
        }
    }

    /**
     * Envoie un prompt à Groq et retourne la réponse (format compatible avec AI.php)
     */
    public function generate($prompt, $systemInstruction = null)
    {
        if (!$this->apiKey) {
            return json_encode(["error" => "Clé API Groq non configurée."]);
        }

        $messages = [];

        // Instruction système (équivalent du systemInstruction de Gemini)
        if ($systemInstruction) {
            $messages[] = [
                "role" => "system",
                "content" => $systemInstruction
            ];
        }

        $messages[] = [
            "role" => "user",
            "content" => $prompt
        ];

        $payload = [
            "model" => $this->model,
            "messages" => $messages,
            "temperature" => 0.7,
            "top_p" => 0.95,
            "max_tokens" => 8192,
            "response_format" => ["type" => "json_object"]
        ];

        $ch = curl_init($this->baseUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return json_encode(["error" => "CURL Error (Groq): " . $err]);
        }

        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['error']['message'] ?? "Erreur HTTP $httpCode";
            return json_encode(["error" => "Groq API Error: " . $errorMsg]);
        }

        $data = json_decode($response, true);
        $textResponse = $data['choices'][0]['message']['content'] ?? null;

        if (!$textResponse) {
            return json_encode(["error" => "Groq n'a pas renvoyé de contenu.", "raw" => $data]);
        }

        return $textResponse;
    }
}

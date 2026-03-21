<?php

/**
 * WARI MONITORING SYSTEM - Alertes temps réel pour l'admin
 * 
 * Ce fichier doit être inclus au début de TOUS les fichiers PHP de l'application
 */

// ============================================
// CONFIGURATION - VOS CLÉS (déjà configurées)
// ============================================

define('MONITORING_TELEGRAM_BOT_TOKEN', '8476303658:AAGMQzVtwSj-k4KqzCaNpmUuQ3PMCtzXpzI');  // ← Mettez votre vrai token
define('MONITORING_TELEGRAM_CHAT_ID', '892276105'); // ← Mettez votre vrai chat ID
define('MONITORING_ADMIN_EMAIL', 'financewari1@gmail.com');

// Paramètres généraux
define('MONITORING_ENABLED', true);           // Activer/désactiver le monitoring
define('MONITORING_RATE_LIMIT', 300);         // Secondes entre 2 alertes identiques (5 min)
define('MONITORING_MIN_LEVEL', E_WARNING);    // Niveau minimum d'alerte

// ============================================
// CLASSE PRINCIPALE DE MONITORING
// ============================================

class WariMonitoring
{

    private static $instance = null;
    private $errors = [];
    private $lastAlertTime = [];
    private $startTime;
    private $requestId;

    private function __construct()
    {
        $this->startTime = microtime(true);
        $this->requestId = uniqid('wari_', true);

        // Enregistrer les gestionnaires d'erreurs
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Gestionnaire d'erreurs PHP (warnings, notices, etc.)
     */
    public function handleError($errno, $errstr, $errfile, $errline)
    {
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $errorTypes = [
            E_ERROR             => 'ERREUR FATALE',
            E_WARNING           => 'WARNING',
            E_PARSE             => 'ERREUR DE SYNTAXE',
            E_NOTICE            => 'NOTICE',
            E_CORE_ERROR        => 'CORE ERROR',
            E_CORE_WARNING      => 'CORE WARNING',
            E_COMPILE_ERROR     => 'COMPILE ERROR',
            E_COMPILE_WARNING   => 'COMPILE WARNING',
            E_USER_ERROR        => 'ERREUR UTILISATEUR',
            E_USER_WARNING      => 'WARNING UTILISATEUR',
            E_USER_NOTICE       => 'NOTICE UTILISATEUR',
            E_STRICT            => 'STRICT',
            E_RECOVERABLE_ERROR => 'ERREUR RÉCUPÉRABLE',
            E_DEPRECATED        => 'DÉPRÉCIÉ',
            E_USER_DEPRECATED   => 'DÉPRÉCIÉ UTILISATEUR',
        ];

        $type = $errorTypes[$errno] ?? 'ERREUR INCONNUE';

        $this->errors[] = [
            'type'      => $type,
            'message'   => $errstr,
            'file'      => $errfile,
            'line'      => $errline,
            'time'      => date('Y-m-d H:i:s'),
            'request_id' => $this->requestId,
            'url'       => $this->getCurrentUrl(),
            'ip'        => $_SERVER['REMOTE_ADDR'] ?? 'CLI',
        ];

        // Envoyer l'alerte si le niveau est suffisant
        if ($errno >= MONITORING_MIN_LEVEL) {
            $this->sendAlert($this->errors[count($this->errors) - 1]);
        }

        return true;
    }

    /**
     * Gestionnaire d'exceptions non catchées
     */
    public function handleException($exception)
    {
        $error = [
            'type'       => 'EXCEPTION',
            'message'    => $exception->getMessage(),
            'file'       => $exception->getFile(),
            'line'       => $exception->getLine(),
            'trace'      => $exception->getTraceAsString(),
            'time'       => date('Y-m-d H:i:s'),
            'request_id' => $this->requestId,
            'url'        => $this->getCurrentUrl(),
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? 'CLI',
        ];

        $this->errors[] = $error;
        $this->sendAlert($error, true);

        // Afficher une page d'erreur user-friendly
        $this->displayErrorPage($error);
    }

    /**
     * Gestionnaire de fin d'exécution (capture les fatal errors)
     */
    public function handleShutdown()
    {
        $error = error_get_last();

        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $errorTypes = [
                E_ERROR         => 'ERREUR FATALE',
                E_PARSE         => 'ERREUR DE SYNTAXE',
                E_CORE_ERROR    => 'CORE ERROR',
                E_COMPILE_ERROR => 'COMPILE ERROR',
            ];

            $errorData = [
                'type'       => $errorTypes[$error['type']] ?? 'ERREUR FATALE',
                'message'    => $error['message'],
                'file'       => $error['file'],
                'line'       => $error['line'],
                'time'       => date('Y-m-d H:i:s'),
                'request_id' => $this->requestId,
                'url'        => $this->getCurrentUrl(),
                'ip'         => $_SERVER['REMOTE_ADDR'] ?? 'CLI',
                'fatal'      => true,
            ];

            $this->errors[] = $errorData;
            $this->sendAlert($errorData, true);
        }

        // Log toutes les erreurs dans un fichier
        $this->writeToLog();
    }

    /**
     * Envoi de l'alerte (Telegram + Email + Log)
     */
    private function sendAlert($error, $highPriority = false)
    {
        if (!MONITORING_ENABLED) return;

        // Rate limiting - éviter le spam
        $errorKey = md5($error['message'] . $error['file']);
        if (isset($this->lastAlertTime[$errorKey])) {
            if (time() - $this->lastAlertTime[$errorKey] < MONITORING_RATE_LIMIT) {
                return;
            }
        }
        $this->lastAlertTime[$errorKey] = time();

        // Envoyer sur Telegram
        $this->sendTelegramAlert($error, $highPriority);

        // Envoyer par email
        $this->sendEmailAlert($error, $highPriority);
    }

    /**
     * Envoi Telegram
     */
    private function sendTelegramAlert($error, $highPriority)
    {
        if (empty(MONITORING_TELEGRAM_BOT_TOKEN) || empty(MONITORING_TELEGRAM_CHAT_ID)) {
            return;
        }

        $emoji = $highPriority ? '🚨' : '⚠️';
        $type = $error['type'];
        $message = substr($error['message'], 0, 200);
        $file = basename($error['file']);
        $line = $error['line'];
        $url = $error['url'];
        $time = $error['time'];

        $text = "{$emoji} <b>ALERTE WARI - {$type}</b> {$emoji}\n\n";
        $text .= "📍 <b>Fichier:</b> {$file}:{$line}\n";
        $text .= "💬 <b>Message:</b> {$message}\n";
        $text .= "🔗 <b>URL:</b> {$url}\n";
        $text .= "⏰ <b>Heure:</b> {$time}\n";
        $text .= "🆔 <b>Request ID:</b> {$error['request_id']}\n\n";
        $text .= "⚡ Action requise !";

        $apiUrl = "https://api.telegram.org/bot" . MONITORING_TELEGRAM_BOT_TOKEN . "/sendMessage";
        $postData = [
            'chat_id' => MONITORING_TELEGRAM_CHAT_ID,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        // Envoi asynchrone
        $this->asyncHttpPost($apiUrl, $postData);
    }

    /**
     * Envoi Email
     */
    private function sendEmailAlert($error, $highPriority)
    {
        if (empty(MONITORING_ADMIN_EMAIL)) {
            return;
        }

        $subject = ($highPriority ? '🚨 ' : '⚠️ ') . 'Erreur WARI - ' . $error['type'];

        $body = "Erreur détectée sur WARI Finance\n\n";
        $body .= "Type: {$error['type']}\n";
        $body .= "Message: {$error['message']}\n";
        $body .= "Fichier: {$error['file']}:{$error['line']}\n";
        $body .= "URL: {$error['url']}\n";
        $body .= "Heure: {$error['time']}\n";
        $body .= "IP: {$error['ip']}\n";
        $body .= "Request ID: {$error['request_id']}\n";

        if (isset($error['trace'])) {
            $body .= "\nTrace:\n{$error['trace']}\n";
        }

        $headers = 'From: monitoring@wari.digiroys.com' . "\r\n";
        $headers .= 'X-Priority: ' . ($highPriority ? '1' : '3') . "\r\n";

        @mail(MONITORING_ADMIN_EMAIL, $subject, $body, $headers);
    }

    /**
     * HTTP POST asynchrone
     */
    private function asyncHttpPost($url, $data)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        @curl_exec($ch);
        @curl_close($ch);
    }

    /**
     * Écriture dans le fichier log
     */
    private function writeToLog()
    {
        if (empty($this->errors)) return;

        $logDir = __DIR__ . '/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/errors_' . date('Y-m-d') . '.log';
        $content = '';

        foreach ($this->errors as $error) {
            $content .= "[" . $error['time'] . "] ";
            $content .= "[" . $error['type'] . "] ";
            $content .= $error['message'] . " in ";
            $content .= $error['file'] . ":" . $error['line'];
            $content .= " [URL: " . $error['url'] . "]\n";
        }

        @file_put_contents($logFile, $content, FILE_APPEND | LOCK_EX);
    }

    /**
     * Affichage d'une page d'erreur user-friendly
     */
    private function displayErrorPage($error)
    {
        http_response_code(500);

        // Si c'est une requête AJAX/API, retourner JSON
        if (
            isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
            strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
        ) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Une erreur est survenue', 'request_id' => $error['request_id']]);
            exit;
        }

        // Sinon, afficher une page HTML
        echo '<!DOCTYPE html>
<html>
<head>
    <title>Maintenance - Wari Finance</title>
    <style>
        body { font-family: Arial, sans-serif; background: #080B10; color: #fff; text-align: center; padding: 50px; }
        .container { max-width: 600px; margin: 0 auto; }
        h1 { color: #F5A623; }
        .error-code { background: #161B24; padding: 20px; border-radius: 10px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Maintenance en cours</h1>
        <p>Nous rencontrons un problème technique. Notre équipe a été notifiée.</p>
        <div class="error-code">
            <strong>Code d\'erreur:</strong> ' . $error['request_id'] . '
        </div>
        <p>Merci de réessayer dans quelques minutes.</p>
    </div>
</body>
</html>';
        exit;
    }

    /**
     * Récupération de l'URL courante
     */
    private function getCurrentUrl()
    {
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return $scheme . '://' . $host . $uri;
    }

    /**
     * Méthode publique pour logger manuellement
     */
    public function logManual($message, $type = 'INFO')
    {
        $error = [
            'type'       => $type,
            'message'    => $message,
            'file'       => 'manual',
            'line'       => 0,
            'time'       => date('Y-m-d H:i:s'),
            'request_id' => $this->requestId,
            'url'        => $this->getCurrentUrl(),
            'ip'         => $_SERVER['REMOTE_ADDR'] ?? 'CLI',
        ];

        $this->errors[] = $error;
        $this->sendAlert($error);
    }
}

// ============================================
// INITIALISATION
// ============================================

WariMonitoring::getInstance();

// Fonction helper pour logger manuellement
function wari_alert($message, $type = 'INFO')
{
    WariMonitoring::getInstance()->logManual($message, $type);
}

// ============================================
// TEST AUTOMATIQUE (à supprimer après test)
// ============================================

// Décommentez la ligne suivante pour tester (puis re-commentez)
//trigger_error('Test du système de monitoring WARI', E_USER_WARNING);

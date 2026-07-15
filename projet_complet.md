# Projet Wari Finance - Spécifications & Code source complet

Ce document compile la structure complète ainsi que le code source des fichiers essentiels du projet.

## 1. Arborescence du Projet

```
wari.digiroys.com/
├── .gitignore
├── .htaccess
├── .vscode/
│   └── settings.json
├── Dockerfile
├── Guide_utilisation_WARI.pdf
├── academy/
│   ├── course.php
│   ├── index.php
│   ├── lesson.php
│   ├── pdf_achat.php
│   ├── pdf_achat_verify.php
│   └── pdf_download.php
├── academy-admin/
│   ├── ai_gateway.php
│   ├── categories.php
│   ├── courses.php
│   ├── emails.php
│   ├── index.php
│   ├── lessons.php
│   ├── login.php
│   ├── logout.php
│   ├── pdfs.php
│   └── stats.php
├── accueil/
│   ├── .htaccess
│   ├── admin_avis.php
│   ├── apropos.php
│   ├── avis.php
│   ├── faq.php
│   ├── google78efe12e6780df7e.html
│   ├── index.php
│   ├── laisser-avis.php
│   ├── mentions-legales.php
│   └── sitemap.xml
├── assets/
│   ├── academy_1.png
│   ├── admin_pwa_icon.png
│   ├── default.jpg
│   ├── finaleduc.jpg
│   ├── fonts/
│   │   ├── Montserrat-Italic-VariableFont_wght.ttf
│   │   ├── Montserrat-VariableFont_wght.ttf
│   │   ├── OpenSans-Italic-VariableFont_wdth,wght.ttf
│   │   └── OpenSans-VariableFont_wdth,wght.ttf
│   ├── main.js
│   ├── styles.css
│   ├── wari-teesh.png
│   ├── wariLog.png
│   ├── wari_og_1.png
│   ├── wari_og_2.png
│   ├── wari_og_3.png
│   ├── warifinance3d.png
│   └── warilog1.png
├── bouton_paid_license.php
├── bouton_whatsapp.php
├── classes/
│   ├── AI.php
│   ├── Academy.php
│   ├── Groq.php
│   ├── Mailer.php
│   ├── Push.php
│   └── Vecu.php
├── coach/
│   └── index.php
├── composer.json
├── composer.lock
├── config/
│   ├── add_debt.php
│   ├── add_distribution.php
│   ├── add_expense.php
│   ├── add_vault_transaction.php
│   ├── admin_wari_99.php
│   ├── auth.php
│   ├── config.php
│   ├── db.php
│   ├── delete_expense.php
│   ├── export_pdf.php
│   ├── forgot-password.php
│   ├── get_history.php
│   ├── get_vault_history.php
│   ├── join_challenge.php
│   ├── logout.php
│   ├── no_cache.php
│   ├── partial_pay.php
│   ├── pay_debt.php
│   ├── process_auth.php
│   ├── quit_challenge.php
│   ├── reset-password.php
│   ├── save_data.php
│   ├── save_subscription.php
│   ├── save_user_challenge.php
│   ├── session_check.php
│   ├── session_config.php
│   └── update_challenge.php
├── cron/
│   ├── send_academy_emails.php
│   ├── send_premium_announcement.php
│   ├── send_premium_push.php
│   ├── send_proactive_coach_alerts.php
│   ├── send_reactivation_emails.php
│   └── test_academy_email.php
├── debug_session.php
├── email.html
├── google78efe12e6780df7e.html
├── guide/
│   └── index.php
├── index.php
├── manifest.json
├── offline.php
├── paid/
│   ├── activation-success.php
│   ├── fedapay-callback.php
│   ├── fedapay-checkout.php
│   ├── index.php
│   └── invoice.php
├── push_log.txt
├── rapport/
│   ├── admin/
│   │   ├── auth.php
│   │   ├── calcul.js
│   │   ├── edit.php
│   │   ├── index.php
│   │   ├── insert.php
│   │   ├── login.php
│   │   └── logout.php
│   ├── assets/
│   ├── index.php
│   └── view.php
├── reserve_sauve.js
├── robots.txt
├── scratch/
│   ├── test_join_direct.php
│   └── test_session_simulation.php
├── send_daily_reminder.php
├── seo_bot.php
├── sitemap.xml
├── sw.js
├── temp_cat_check.php
├── temp_check_premium.php
├── templates/
│   └── emails/
│       ├── academy.html
│       ├── premium_announcement.html
│       └── reactivation.html
├── test_gemini_api.php
├── test_push.php
├── test_session.php
├── test_stats.php
├── test_tables.php
├── tmp_courses.php
├── vecu/
│   ├── admin/
│   │   ├── auth.php
│   │   ├── delete.php
│   │   ├── edit.php
│   │   ├── export_subscribers.php
│   │   ├── index.php
│   │   ├── insert.php
│   │   ├── login.php
│   │   └── logout.php
│   ├── article.php
│   ├── assets/
│   │   ├── form.php
│   │   └── subscribe_whatsapp.php
│   ├── index.php
├── wari-admin/
│   ├── .env
│   ├── .htaccess
│   ├── audit.log
│   ├── auth.php
│   ├── config.php
│   ├── debug_stats.php
│   ├── generate_password.php
│   ├── index.php
│   ├── manifest.json
│   └── sw.js
└── wari_monitoring.php
```

## 2. Fichiers sources clés

### `Dockerfile`

```dockerfile
FROM php:8.2-apache

# Installation des dépendances système (pour intl et gmp)
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libgmp-dev \
    && rm -rf /var/lib/apt/lists/*

# Installation des extensions PHP
RUN docker-php-ext-configure intl \
    && docker-php-ext-install pdo pdo_mysql intl bcmath gmp

# Configuration Apache
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && a2enmod rewrite
```

### `composer.json`

```json
{
    "require": {
        "minishlink/web-push": "^10.0",
        "phpmailer/phpmailer": "^7.0",
        "fedapay/fedapay-php": "^0.4.8",
        "dompdf/dompdf": "^3.1"
    }
}

```

### `.htaccess`

```htaccess
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^login/?$ config/auth.php [QSA,L]
    RewriteRule ^register/?$ config/auth.php [QSA,L]
</IfModule>

<IfModule mod_headers.c>
    <FilesMatch "\.php$">
        Header set Cache-Control "no-store, no-cache, must-revalidate, max-age=0"
        Header set Pragma "no-cache"
        Header set Expires "0"
    </FilesMatch>
</IfModule>
```

### `wari_monitoring.php`

```php
<?php

/**
 * WARI MONITORING SYSTEM - Alertes temps réel pour l'admin
 * 
 * Ce fichier doit être inclus au début de TOUS les fichiers PHP de l'application
 */

// ============================================
// CONFIGURATION - VOS CLÉS (déjà configurées)
// ============================================

define('MONITORING_TELEGRAM_BOT_TOKEN', '********');  // ← Mettez votre vrai token
define('MONITORING_TELEGRAM_CHAT_ID', '********'); // ← Mettez votre vrai chat ID
define('MONITORING_ADMIN_EMAIL', 'wari.finance.inter@gmail.com');

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

```

### `wari-admin/.env`

```ini
# Généré par generate_password.php
ADMIN_PASSWORD_HASH=********
CSRF_SECRET=********

# Clés VAPID (à déplacer ici depuis le code)
VAPID_SUBJECT=********
VAPID_PUBLIC_KEY=********
VAPID_PRIVATE_KEY=********

# Base de données (optionnel, si pas déjà dans db.php)
DB_HOST=********
DB_NAME=********
DB_USER=********
DB_PASS=********

# SMTP Configuration (Configuration active par défaut : Gmail)
SMTP_HOST=********
SMTP_PORT=********
SMTP_USER=********
SMTP_PASS=********
SMTP_FROM=********
SMTP_FROM_NAME=********
SMTP_SECURE=********

# --- CONFIGURATION ALTERNATIVE DE SECOURS (Hostinger) ---
# Pour utiliser le SMTP professionnel Hostinger, commentez les variables Gmail ci-dessus 
# (en ajoutant un # au début de chaque ligne) et décommentez les variables ci-dessous :
# SMTP_HOST=smtp.hostinger.com
# SMTP_PORT=465
# SMTP_USER=votre-email@pro.com
# SMTP_PASS=votre-mot-de-passe-pro
# SMTP_FROM=votre-email@pro.com
# SMTP_FROM_NAME=Wari Finance (Pro)
# SMTP_SECURE=ssl

# CONFIG USER ACADEMY
ACADEMY_ADMIN_USERS=********

# ── FedaPay
FEDAPAY_SECRET_KEY=********
FEDAPAY_PUBLIC_KEY=********
FEDAPAY_ENV=********

# ── CinetPay
CINETPAY_API_KEY=********
CINETPAY_SITE_ID=********
CINETPAY_ENV=********

# ── Gemini AI
GEMINI_API_KEY=********
GEMINI_MODEL=********

# ── Groq AI (Fallback automatique si Gemini échoue)
GROQ_API_KEY=********
GROQ_MODEL=********
```

### `wari-admin/auth.php`

```php
<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

// Connexion automatique directe demandée par l'utilisateur
$_SESSION['is_admin'] = true;
$_SESSION['admin_id'] = 'admin_direct';
$_SESSION['login_time'] = time();

// Vérification CSRF pour toutes les requêtes POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!validateCsrf($csrfToken)) {
        http_response_code(403);
        die(json_encode(['error' => 'Token CSRF invalide']));
    }
}

// Authentification manuelle par mot de passe désactivée pour accès direct
/*
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_pass'])) {
    if (password_verify($_POST['admin_pass'], ADMIN_PASSWORD_HASH)) {
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_id'] = 'admin_' . bin2hex(random_bytes(4));
        $_SESSION['login_time'] = time();
        auditLog('LOGIN_SUCCESS', ['ip' => $_SERVER['REMOTE_ADDR']]);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $loginError = "Mot de passe incorrect";
        auditLog('LOGIN_FAILED', ['ip' => $_SERVER['REMOTE_ADDR'], 'reason' => 'wrong_password']);
        sleep(2);
    }
}
*/

// Logout désactivé pour maintenir l'accès direct
/*
if (isset($_POST['admin_logout'])) {
    auditLog('LOGOUT', ['admin_id' => $_SESSION['admin_id'] ?? 'unknown']);
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
*/

// Vérification session active (Enforce l'accès direct de l'admin)
function requireAuth(): void
{
    $_SESSION['is_admin'] = true;
    $_SESSION['admin_id'] = 'admin_direct';
    $_SESSION['login_time'] = time();
}

```

### `wari-admin/config.php`

```php
<?php

declare(strict_types=1);

session_start();

// Charge les variables d'environnement
function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        die('Fichier .env manquant. Exécute generate_password.php');
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

loadEnv(__DIR__ . '/.env');

// Configuration sécurisée
define('ADMIN_PASSWORD_HASH', $_ENV['ADMIN_PASSWORD_HASH'] ?? '');
define('CSRF_SECRET', $_ENV['CSRF_SECRET'] ?? '');
define('VAPID_CONFIG', [
    'VAPID' => [
        'subject'    => $_ENV['VAPID_SUBJECT'] ?? '',
        'publicKey'  => $_ENV['VAPID_PUBLIC_KEY'] ?? '',
        'privateKey' => $_ENV['VAPID_PRIVATE_KEY'] ?? '',
    ]
]);

// Sécurité session
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', '1');

// Headers de sécurité
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' fonts.googleapis.com; font-src fonts.gstatic.com; connect-src 'self' https://cdn.jsdelivr.net;");

/**
 * Génère/valide un token CSRF
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token']) || $_SESSION['csrf_time'] < time() - 3600) {
        $_SESSION['csrf_token'] = bin2hex(hash_hmac('sha256', random_bytes(32), CSRF_SECRET, true));
        $_SESSION['csrf_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function validateCsrf(string $token): bool
{
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * Logging d'audit sécurisé
 */
function auditLog(string $action, array $details = []): void
{
    $log = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip'        => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user'      => $_SESSION['admin_id'] ?? 'anonymous',
        'action'    => $action,
        'details'   => $details,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];

    $line = json_encode($log, JSON_UNESCAPED_UNICODE) . "\n";
    file_put_contents(__DIR__ . '/audit.log', $line, FILE_APPEND | LOCK_EX);
}

/**
 * Réponse JSON sécurisée
 */
function jsonResponse(bool $success, array $data = [], int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['success' => $success], $data));
    exit;
}

/**
 * Validation ID utilisateur
 */
function validateUserId($id): int
{
    $id = filter_var($id, FILTER_VALIDATE_INT);
    if ($id === false || $id <= 0) {
        throw new InvalidArgumentException('ID utilisateur invalide');
    }
    return $id;
}

/**
 * Nettoyage des entrées
 */
function cleanInput(string $input, int $maxLength = 1000): string
{
    $input = substr($input, 0, $maxLength);
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

```

### `classes/AI.php`

```php
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
        $this->model  = $_ENV['GEMINI_MODEL'] ?? getenv('GEMINI_MODEL') ?: 'gemini-flash-latest';
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

```

### `classes/Academy.php`

```php
<?php
// /var/www/html/classes/Academy.php

class Academy
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // ============================================================
    // CATEGORIES
    // ============================================================

    /**
     * Récupère toutes les catégories actives
     */
    // ✅ APRÈS — avec GROUP BY
    public function getCategories()
    {
        return $this->pdo->query("
        SELECT c.*,
            COUNT(DISTINCT co.id) as nb_cours
        FROM academy_categories c
        LEFT JOIN academy_courses co ON co.category_id = c.id AND co.est_actif = 1
        WHERE c.est_actif = 1
        GROUP BY c.id          
        ORDER BY c.ordre ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère une catégorie par son slug
     */
    public function getCategoryBySlug($slug)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM academy_categories
            WHERE slug = ? AND est_actif = 1
        ");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ============================================================
    // COURS
    // ============================================================

    /**
     * Récupère tous les cours d'une catégorie
     */
    public function getCoursesByCategory($category_id)
    {
        $stmt = $this->pdo->prepare("
            SELECT co.*,
                COUNT(DISTINCT l.id) as nb_lecons
            FROM academy_courses co
            LEFT JOIN academy_lessons l ON l.course_id = co.id AND l.est_actif = 1
            WHERE co.category_id = ? AND co.est_actif = 1
            ORDER BY co.ordre ASC
        ");
        $stmt->execute([$category_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère un cours par son slug
     */
    public function getCourseBySlug($slug)
    {
        $stmt = $this->pdo->prepare("
            SELECT co.*, c.titre as category_titre, c.slug as category_slug, c.icone as category_icone, c.couleur as category_couleur
            FROM academy_courses co
            JOIN academy_categories c ON c.id = co.category_id
            WHERE co.slug = ? AND co.est_actif = 1
        ");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère un cours par son ID
     */
    public function getCourseById($course_id)
    {
        $stmt = $this->pdo->prepare("
            SELECT co.*, c.titre as category_titre, c.slug as category_slug, c.icone as category_icone, c.couleur as category_couleur
            FROM academy_courses co
            JOIN academy_categories c ON c.id = co.category_id
            WHERE co.id = ? AND co.est_actif = 1
        ");
        $stmt->execute([$course_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ============================================================
    // LEÇONS
    // ============================================================

    /**
     * Récupère toutes les leçons d'un cours
     */
    public function getLessonsByCourse($course_id)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM academy_lessons
            WHERE course_id = ? AND est_actif = 1
            ORDER BY ordre ASC
        ");
        $stmt->execute([$course_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère une leçon par son ID
     */
    public function getLessonById($lesson_id)
    {
        $stmt = $this->pdo->prepare("
            SELECT l.*, co.titre as course_titre, co.slug as course_slug
            FROM academy_lessons l
            JOIN academy_courses co ON co.id = l.course_id
            WHERE l.id = ? AND l.est_actif = 1
        ");
        $stmt->execute([$lesson_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère la leçon suivante dans un cours
     */
    public function getNextLesson($course_id, $current_ordre)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM academy_lessons
            WHERE course_id = ? AND ordre > ? AND est_actif = 1
            ORDER BY ordre ASC
            LIMIT 1
        ");
        $stmt->execute([$course_id, $current_ordre]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère la leçon précédente dans un cours
     */
    public function getPrevLesson($course_id, $current_ordre)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM academy_lessons
            WHERE course_id = ? AND ordre < ? AND est_actif = 1
            ORDER BY ordre DESC
            LIMIT 1
        ");
        $stmt->execute([$course_id, $current_ordre]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // ============================================================
    // PROGRESSION UTILISATEUR
    // ============================================================

    /**
     * Marque une leçon comme complétée pour un utilisateur
     */
    public function markLessonComplete($user_id, $lesson_id, $course_id)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO academy_progress (user_id, lesson_id, course_id, est_complete, complete_le)
            VALUES (?, ?, ?, 1, NOW())
            ON DUPLICATE KEY UPDATE
                est_complete = 1,
                complete_le  = IF(est_complete = 0, NOW(), complete_le)
        ");
        return $stmt->execute([$user_id, $lesson_id, $course_id]);
    }

    /**
     * Vérifie si une leçon est complétée par un utilisateur
     */
    public function isLessonComplete($user_id, $lesson_id)
    {
        $stmt = $this->pdo->prepare("
            SELECT est_complete FROM academy_progress
            WHERE user_id = ? AND lesson_id = ?
        ");
        $stmt->execute([$user_id, $lesson_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (bool)$row['est_complete'] : false;
    }

    /**
     * Calcule le pourcentage de progression d'un utilisateur dans un cours
     */
    public function getCourseProgress($user_id, $course_id)
    {
        // Total des leçons du cours
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as total FROM academy_lessons
            WHERE course_id = ? AND est_actif = 1
        ");
        $stmt->execute([$course_id]);
        $total = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        if ($total === 0) return 0;

        // Leçons complétées par l'utilisateur
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as done FROM academy_progress
            WHERE user_id = ? AND course_id = ? AND est_complete = 1
        ");
        $stmt->execute([$user_id, $course_id]);
        $done = (int)$stmt->fetch(PDO::FETCH_ASSOC)['done'];

        return round(($done / $total) * 100);
    }

    /**
     * Récupère tous les cours avec la progression d'un utilisateur
     */
    public function getAllCoursesWithProgress($user_id)
    {
        $stmt = $this->pdo->prepare("
            SELECT co.*,
                c.titre as category_titre,
                c.couleur as category_couleur,
                c.icone as category_icone,
                COUNT(DISTINCT l.id) as nb_lecons,
                COUNT(DISTINCT CASE WHEN p.est_complete = 1 THEN p.lesson_id END) as lecons_completes
            FROM academy_courses co
            JOIN academy_categories c ON c.id = co.category_id
            LEFT JOIN academy_lessons l ON l.course_id = co.id AND l.est_actif = 1
            LEFT JOIN academy_progress p ON p.course_id = co.id AND p.user_id = ?
            WHERE co.est_actif = 1 AND c.est_actif = 1
            GROUP BY co.id
            ORDER BY c.ordre ASC, co.ordre ASC
        ");
        $stmt->execute([$user_id]);
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calcul du pourcentage pour chaque cours
        foreach ($courses as &$course) {
            $total = (int)$course['nb_lecons'];
            $done  = (int)$course['lecons_completes'];
            $course['progression'] = $total > 0 ? round(($done / $total) * 100) : 0;
        }

        return $courses;
    }

    /**
     * Compte le nombre de cours que l'utilisateur n'a pas encore terminés (progression < 100%)
     */
    public function getUnfinishedCoursesCount($user_id)
    {
        $courses = $this->getAllCoursesWithProgress($user_id);
        $count = 0;
        foreach ($courses as $c) {
            if ($c['progression'] < 100) {
                $count++;
            }
        }
        return $count;
    }

    // ============================================================
    // PDF PAYANTS
    // ============================================================

    /**
     * Récupère les PDF liés à un cours
     */
    public function getPdfsByCourse($course_id)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM academy_pdfs
            WHERE course_id = ? AND est_actif = 1
            ORDER BY id ASC
        ");
        $stmt->execute([$course_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Vérifie si un utilisateur a déjà acheté un PDF
     */
    public function hasUserBoughtPdf($user_id, $pdf_id)
    {
        $stmt = $this->pdo->prepare("
            SELECT id FROM academy_pdf_achats
            WHERE user_id = ? AND pdf_id = ? AND statut = 'paye'
        ");
        $stmt->execute([$user_id, $pdf_id]);
        return (bool)$stmt->fetch();
    }

    /**
     * Enregistre un achat de PDF
     */
    public function savePdfAchat($user_id, $pdf_id, $montant, $reference = null)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO academy_pdf_achats (user_id, pdf_id, montant, statut, reference)
            VALUES (?, ?, ?, 'paye', ?)
        ");
        return $stmt->execute([$user_id, $pdf_id, $montant, $reference]);
    }

    // ============================================================
    // STATS ADMIN
    // ============================================================

    /**
     * Statistiques globales pour le tableau de bord admin
     */
    public function getAdminStats()
    {
        return [
            'total_apprenants' => $this->pdo->query("
                SELECT COUNT(DISTINCT user_id) as n FROM academy_progress
            ")->fetch(PDO::FETCH_ASSOC)['n'],

            'total_completions' => $this->pdo->query("
                SELECT COUNT(*) as n FROM academy_progress WHERE est_complete = 1
            ")->fetch(PDO::FETCH_ASSOC)['n'],

            'total_cours' => $this->pdo->query("
                SELECT COUNT(*) as n FROM academy_courses WHERE est_actif = 1
            ")->fetch(PDO::FETCH_ASSOC)['n'],

            'total_revenus' => $this->pdo->query("
                SELECT COALESCE(SUM(montant), 0) as n FROM academy_pdf_achats WHERE statut = 'paye'
            ")->fetch(PDO::FETCH_ASSOC)['n'],
        ];
    }

    /**
     * Cours les plus suivis
     */
    // ✅ APRÈS — cast direct en int dans la requête
    public function getTopCourses($limit = 5)
    {
        $limit = (int)$limit; // sécurisé car forcé en entier
        $stmt = $this->pdo->query("
        SELECT co.titre, co.slug,
            COUNT(DISTINCT p.user_id) as nb_apprenants,
            ROUND(
                COUNT(CASE WHEN p.est_complete = 1 THEN 1 END) * 100.0 /
                NULLIF(COUNT(p.id), 0)
            ) as taux_completion
        FROM academy_courses co
        LEFT JOIN academy_progress p ON p.course_id = co.id
        WHERE co.est_actif = 1
        GROUP BY co.id
        ORDER BY nb_apprenants DESC
        LIMIT {$limit}
    ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

```

### `classes/Groq.php`

```php
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

```

### `classes/Mailer.php`

```php
<?php
// /var/www/html/classes/Mailer.php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private $mail;

    public function __construct()
    {
        $this->loadEnv();
        $this->mail = new PHPMailer(true);

        // Configuration SMTP
        $this->mail->isSMTP();
        $this->mail->Host       = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
        $this->mail->SMTPAuth   = true;
        $this->mail->Username   = $_ENV['SMTP_USER'] ?? '';
        $this->mail->Password   = $_ENV['SMTP_PASS'] ?? '';
        
        $secure = strtolower($_ENV['SMTP_SECURE'] ?? 'tls');
        if ($secure === 'ssl') {
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
        
        $this->mail->Port       = $_ENV['SMTP_PORT'] ?? 587;
        $this->mail->CharSet    = 'UTF-8';

        // Expéditeur
        $this->mail->setFrom(
            $_ENV['SMTP_FROM'] ?? 'noreply@wari.digiroys.com',
            $_ENV['SMTP_FROM_NAME'] ?? 'Wari Finance'
        );
    }

    private function loadEnv()
    {
        if (isset($_ENV['SMTP_USER']) && $_ENV['SMTP_USER']) return;

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

    public function send($to, $subject, $body, $isHTML = true)
    {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($to);
            $this->mail->isHTML($isHTML);
            $this->mail->Subject = $subject;
            $this->mail->Body    = $body;
            $this->mail->AltBody = strip_tags($body);

            $this->mail->send();
            return ['success' => true, 'message' => 'Email envoyé'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

```

### `classes/Push.php`

```php
<?php
// /var/www/wari.digiroys.com/classes/Push.php

require_once __DIR__ . '/../vendor/autoload.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class Push
{
    private static function loadEnv()
    {
        if (isset($_ENV['VAPID_PUBLIC_KEY'])) return;

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

    public static function sendToAll(PDO $pdo, string $title, string $body, string $url, string $type = null, string $target_id = null)
    {
        self::loadEnv();

        $logId = null;
        if ($type !== null && $target_id !== null) {
            try {
                $stmt = $pdo->prepare("INSERT INTO wari_push_logs (type, target_id, title, sent_count) VALUES (?, ?, ?, 0)");
                $stmt->execute([$type, $target_id, $title]);
                $logId = $pdo->lastInsertId();
            } catch (Exception $e) {
                error_log("Erreur d'insertion dans wari_push_logs : " . $e->getMessage());
            }
        }

        if ($logId) {
            $connector = (strpos($url, '?') === false) ? '?' : '&';
            $url .= $connector . 'push_log_id=' . $logId;
        }

        $vapidConfig = [
            'VAPID' => [
                'subject'    => $_ENV['VAPID_SUBJECT'] ?? 'mailto:info@rebonly.com',
                'publicKey'  => $_ENV['VAPID_PUBLIC_KEY'] ?? 'BH9WpcuMhkSEOjnwf8KVZfDTv9Ps6nGaQ9RQ77e4D15ywgPmO7wNgTlldejjFjyWCp3PoBYareDXjlFBTdpzm40',
                'privateKey' => $_ENV['VAPID_PRIVATE_KEY'] ?? '5RRIDWOg5l8uik2FAhvqvc-VXfcNupUB7JUGFOxox6c',
            ]
        ];

        try {
            $webPush = new WebPush($vapidConfig);

            // Récupérer tous les abonnements push
            $subs = $pdo->query("SELECT endpoint, p256dh, auth FROM wari_subscriptions")
                         ->fetchAll(PDO::FETCH_ASSOC);

            if (empty($subs)) {
                return ['success' => true, 'recipients' => 0];
            }

            $payload = json_encode([
                'title' => $title,
                'body'  => $body,
                'icon'  => 'https://i.postimg.cc/x80KpBqW/warifinance3d.png',
                'badge' => 'https://i.postimg.cc/x80KpBqW/warifinance3d.png',
                'url'   => $url,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            foreach ($subs as $sub) {
                if (empty($sub['endpoint']) || empty($sub['p256dh']) || empty($sub['auth'])) {
                    continue;
                }

                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $sub['endpoint'],
                        'keys' => ['p256dh' => $sub['p256dh'], 'auth' => $sub['auth']],
                    ]),
                    $payload
                );
            }

            $expiredEndpoints = [];
            $successCount = 0;
            $index = 0;

            foreach ($webPush->flush() as $report) {
                if ($report->isSuccess()) {
                    $successCount++;
                } elseif ($report->isSubscriptionExpired()) {
                    // Supprimer l'abonnement expiré
                    if (isset($subs[$index]['endpoint'])) {
                        $expiredEndpoints[] = $subs[$index]['endpoint'];
                    }
                }
                $index++;
            }

            if (!empty($expiredEndpoints)) {
                $expiredEndpoints = array_filter($expiredEndpoints);
                if (!empty($expiredEndpoints)) {
                    $placeholders = implode(',', array_fill(0, count($expiredEndpoints), '?'));
                    $pdo->prepare("DELETE FROM wari_subscriptions WHERE endpoint IN ($placeholders)")
                        ->execute($expiredEndpoints);
                }
            }

            if ($logId) {
                try {
                    $stmt = $pdo->prepare("UPDATE wari_push_logs SET sent_count = ? WHERE id = ?");
                    $stmt->execute([$successCount, $logId]);
                } catch (Exception $e) {
                    error_log("Erreur de mise à jour wari_push_logs : " . $e->getMessage());
                }
            }

            return ['success' => true, 'recipients' => $successCount];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function sendToUser(PDO $pdo, int $userId, string $title, string $body, string $url, string $type = 'coach_proactive_alert')
    {
        self::loadEnv();

        $logId = null;
        try {
            $stmt = $pdo->prepare("INSERT INTO wari_push_logs (type, target_id, title, sent_count) VALUES (?, ?, ?, 0)");
            $stmt->execute([$type, (string)$userId, $title]);
            $logId = $pdo->lastInsertId();
        } catch (Exception $e) {
            error_log("Erreur d'insertion dans wari_push_logs : " . $e->getMessage());
        }

        if ($logId) {
            $connector = (strpos($url, '?') === false) ? '?' : '&';
            $url .= $connector . 'push_log_id=' . $logId;
        }

        $vapidConfig = [
            'VAPID' => [
                'subject'    => $_ENV['VAPID_SUBJECT'] ?? 'mailto:info@rebonly.com',
                'publicKey'  => $_ENV['VAPID_PUBLIC_KEY'] ?? 'BH9WpcuMhkSEOjnwf8KVZfDTv9Ps6nGaQ9RQ77e4D15ywgPmO7wNgTlldejjFjyWCp3PoBYareDXjlFBTdpzm40',
                'privateKey' => $_ENV['VAPID_PRIVATE_KEY'] ?? '5RRIDWOg5l8uik2FAhvqvc-VXfcNupUB7JUGFOxox6c',
            ]
        ];

        try {
            $webPush = new WebPush($vapidConfig);

            // Récupérer les abonnements push de cet utilisateur
            $stmt = $pdo->prepare("SELECT endpoint, p256dh, auth FROM wari_subscriptions WHERE user_id = ?");
            $stmt->execute([$userId]);
            $subs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($subs)) {
                return ['success' => true, 'recipients' => 0];
            }

            $payload = json_encode([
                'title' => $title,
                'body'  => $body,
                'icon'  => 'https://i.postimg.cc/x80KpBqW/warifinance3d.png',
                'badge' => 'https://i.postimg.cc/x80KpBqW/warifinance3d.png',
                'url'   => $url,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            foreach ($subs as $sub) {
                if (empty($sub['endpoint']) || empty($sub['p256dh']) || empty($sub['auth'])) {
                    continue;
                }

                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $sub['endpoint'],
                        'keys' => ['p256dh' => $sub['p256dh'], 'auth' => $sub['auth']],
                    ]),
                    $payload
                );
            }

            $expiredEndpoints = [];
            $successCount = 0;
            $index = 0;

            foreach ($webPush->flush() as $report) {
                if ($report->isSuccess()) {
                    $successCount++;
                } elseif ($report->isSubscriptionExpired()) {
                    if (isset($subs[$index]['endpoint'])) {
                        $expiredEndpoints[] = $subs[$index]['endpoint'];
                    }
                }
                $index++;
            }

            if (!empty($expiredEndpoints)) {
                $expiredEndpoints = array_filter($expiredEndpoints);
                if (!empty($expiredEndpoints)) {
                    $placeholders = implode(',', array_fill(0, count($expiredEndpoints), '?'));
                    $pdo->prepare("DELETE FROM wari_subscriptions WHERE endpoint IN ($placeholders)")
                        ->execute($expiredEndpoints);
                }
            }

            if ($logId) {
                try {
                    $stmt = $pdo->prepare("UPDATE wari_push_logs SET sent_count = ? WHERE id = ?");
                    $stmt->execute([$successCount, $logId]);
                } catch (Exception $e) {
                    error_log("Erreur de mise à jour wari_push_logs : " . $e->getMessage());
                }
            }

            return ['success' => true, 'recipients' => $successCount];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

```

### `classes/Vecu.php`

```php
<?php
// /var/www/html/classes/Vecu.php

class Vecu
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->ensureTableExists();
    }

    private function ensureTableExists()
    {
        $sql = "CREATE TABLE IF NOT EXISTS wari_vecu_reads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            article_id INT NOT NULL,
            read_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY user_article (user_id, article_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            // Silently fail if we can't create the table
        }
    }

    public function getUnreadCount($user_id)
    {
        if (!$user_id) return 0;

        $sql = "SELECT COUNT(*) FROM wari_articles a
                LEFT JOIN wari_vecu_reads r ON r.article_id = a.id AND r.user_id = ?
                WHERE r.id IS NULL";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$user_id]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function markAsRead($user_id, $article_id)
    {
        if (!$user_id || !$article_id) return false;

        $sql = "INSERT IGNORE INTO wari_vecu_reads (user_id, article_id) VALUES (?, ?)";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$user_id, $article_id]);
        } catch (PDOException $e) {
            return false;
        }
    }
}

```

### `config/db.php`

```php
<?php
require_once __DIR__ . '/../wari_monitoring.php';
$host = 'db'; // Souvent 'mysql' ou 'db' ou l'IP du VPS
$db   = 'wari_db';
$user = 'root';
$pass = '********';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

/**
 * Log une action d'authentification ou d'activité dans wari_audit
 */
function logAuthAttempt($pdo, $action, $email = null, $userId = null, $details = null) {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'system';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'system';
        $stmt = $pdo->prepare("INSERT INTO wari_audit (action, email, user_id, details, ip, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$action, $email, $userId, $details, $ip, substr($userAgent, 0, 255)]);
    } catch (Exception $e) {
        // Fallback silencieux si la table audit a un souci
    }
}

```

### `config/config.php`

```php
{
"sujet": "mailto: <info@rebonly.com>",
    "clé publique": "BH9WpcuMhkSEOjnwf8KVZfDTv9Ps6nGaQ9RQ77e4D15ywgPmO7wNgTlldejjFjyWCp3PoBYareDXjlFBTdpzm40",
    "clé privée": "********"
    }
```

### `config/session_check.php`

```php
<?php
require_once __DIR__ . '/../wari_monitoring.php';  // ← TOUJOURS EN PREMIER
if (!isset($pdo)) require 'db.php';

// 1. TENTATIVE DE RECONNEXION PAR COOKIE (Ton code actuel)
if (!isset($_SESSION['user_id']) && isset($_COOKIE['wari_remember'])) {
    $token = $_COOKIE['wari_remember'];
    $stmt  = $pdo->prepare("
        SELECT u.*, l.statut as licence_statut 
        FROM wari_users u
        LEFT JOIN wari_licences l ON l.commande_id = u.commande_id
        WHERE u.remember_token = ? AND u.remember_expires > NOW()
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user && $user['licence_statut'] !== 'suspendu') {
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_email'] = $user['email'];

        // Renouvellement du cookie
        $newToken = bin2hex(random_bytes(32));
        $expires  = date('Y-m-d H:i:s', strtotime('+90 days'));
        $pdo->prepare("UPDATE wari_users SET remember_token = ?, remember_expires = ? WHERE id = ?")
            ->execute([$newToken, $expires, $user['id']]);

        setcookie('wari_remember', $newToken, [
            'expires'  => time() + (90 * 24 * 3600),
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax', // Changé de Strict à Lax pour éviter les blocs sur sous-domaine
        ]);
    } else {
        setcookie('wari_remember', '', ['expires' => time() - 3600, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']);
    }
}

// 2. CONTRÔLE D'ACCÈS / SUSPENSION / EXPIRATION D'ABONNEMENT
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("
        SELECT u.email, u.commande_id, l.statut as licence_statut, l.date_expiration 
        FROM wari_users u
        LEFT JOIN wari_licences l ON l.commande_id = u.commande_id
        WHERE u.id = ?
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $userLicense = $stmt->fetch();

    $_SESSION['is_premium'] = false;
    $has_paid = false;
    
    if ($userLicense) {
        $userEmail = $userLicense['email'];
        $licence_statut = $userLicense['licence_statut'];
        $date_expiration = $userLicense['date_expiration'];
        
        // SECURITE RENFORCEE : L'utilisateur a-t-il vraiment payé via FedaPay ?
        $stmtPay = $pdo->prepare("SELECT id FROM wari_payments WHERE commande_id = ? AND reference_fedapay IS NOT NULL AND reference_fedapay != '' AND statut = 'approved' LIMIT 1");
        $stmtPay->execute([$userLicense['commande_id']]);
        if ($stmtPay->fetch()) {
            $has_paid = true;
        }

        // Est premium si la licence est valide ET qu'il a payé
        $_SESSION['is_premium'] = ($date_expiration !== null && strtotime($date_expiration) >= time() && $has_paid);

        // Définir les contournements (bypass) pour éviter les redirections infinies
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $isBypass = (
            strpos($scriptName, '/paid/') !== false ||
            strpos($scriptName, '/wari-admin/') !== false ||
            strpos($scriptName, '/config/logout.php') !== false ||
            strpos($scriptName, '/config/auth.php') !== false ||
            strpos($scriptName, '/config/process_auth.php') !== false
        );

        if (!$isBypass) {
            // Vérification Compte Suspendu
            if ($licence_statut === 'suspendu') {
                unset($_SESSION['user_id']);
                unset($_SESSION['user_email']);
                session_destroy();
                setcookie('wari_remember', '', ['expires' => time() - 3600, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']);
                
                if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
                    http_response_code(403);
                    exit(json_encode(['error' => 'Compte suspendu']));
                }
                header('Location: https://wari.digiroys.com/config/auth.php?error=suspended');
                exit();
            }

            // Gestion de l'expiration et du blocage global (Monétisation)
            // Test Agile pour info@rebonly.com ou Lancement Global à partir du 1er Janvier 2027.
            $transitionDate = strtotime('2027-01-01 00:00:00');
            $isTransitionActive = (time() >= $transitionDate);

            if ($userEmail === 'info@rebonly.com' || $isTransitionActive) {
                // L'accès est bloqué si la licence est absente, expirée OU non payée via FedaPay
                $isExpired = (
                    $date_expiration === null || 
                    strtotime($date_expiration) < time() ||
                    !$has_paid
                );
                
                if ($isExpired) {
                    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
                        http_response_code(402); // Payment Required
                        exit(json_encode(['error' => 'Licence expirée', 'expired' => true]));
                    }
                    header('Location: https://wari.digiroys.com/paid/index.php');
                    exit();
                }
            }
        }
    }
}

// 3. LA SÉCURITÉ CRITIQUE (Si déconnecté)
if (!isset($_SESSION['user_id'])) {
    // Si c'est un appel API (JSON)
    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
        http_response_code(403);
        exit(json_encode(['error' => 'Non autorisé']));
    } 
    
    // On récupère l'URL complète actuelle pour la redirection future
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    $current_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

    // Si c'est un utilisateur qui navigue
    if (basename($_SERVER['PHP_SELF']) !== 'auth.php') {
        // ✅ ON AJOUTE LE PARAMÈTRE REDIRECT ICI
        header('Location: https://wari.digiroys.com/config/auth.php?redirect=' . urlencode($current_url));
        exit();
    }
}
```

### `config/session_config.php`

```php
<?php
require_once __DIR__ . '/../wari_monitoring.php';  // ← TOUJOURS EN PREMIER
// Configuration session pour 90 jours comme WhatsApp

// ⛔ ÉVITER les inclusions multiples
if (session_status() === PHP_SESSION_NONE) {

    // Configuration AVANT de démarrer la session
    $sessionPath = __DIR__ . '/sessions_data';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0700, true);
    }

    ini_set('session.save_path', $sessionPath);
    ini_set('session.gc_maxlifetime', 90 * 24 * 3600);
    ini_set('session.cookie_lifetime', 90 * 24 * 3600);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.cookie_samesite', 'Strict');

    // Démarrer la session UNE SEULE FOIS
    session_start();
}

// Prolonger la session à chaque accès (seulement si connecté)
if (isset($_SESSION['user_id'])) {
    setcookie(session_name(), session_id(), time() + (90 * 24 * 3600), '/', '', true, true);
}

```

### `config/process_auth.php`

```php
<?php
// ← Ces deux lignes EN PREMIER, avant tout
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
// Configuration session 90 jours avant tout output
require_once __DIR__ . '/../wari_monitoring.php';  // ← TOUJOURS EN PREMIER
require 'session_config.php'; // Charge la config 90 jours

// Ne nettoyer la session que pour login/register, pas pour toutes les requêtes POST
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    in_array($_POST['action'], ['login', 'register'])
) {

    // Nettoyer uniquement les données d'authentification
    unset($_SESSION['user_id']);
    unset($_SESSION['user_email']);
    session_regenerate_id(true); // Sécurité: nouvel ID de session
}

require 'db.php';
require 'no_cache.php';

// ============================================
// FONCTIONS DE DÉTECTION BRUTE FORCE
// ============================================

/**
 * Vérifie si une IP est temporairement bloquée
 */
function isIpBlocked($pdo, $ip)
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM wari_audit 
        WHERE action = 'LOGIN_FAILED' 
        AND ip = ? 
        AND time > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
    ");
    $stmt->execute([$ip]);
    return $stmt->fetchColumn() >= 5; // Bloque après 5 échecs en 10 min
}



/**
 * Compte les échecs récents pour une IP
 */
function countRecentFails($pdo, $ip)
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM wari_audit 
        WHERE action = 'LOGIN_FAILED' 
        AND ip = ? 
        AND time > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
    ");
    $stmt->execute([$ip]);
    return $stmt->fetchColumn();
}

// ─── VÉRIFICATION DU COOKIE "REMEMBER ME" AU CHARGEMENT ──────────────────────
// Si l'utilisateur a un cookie valide, on le connecte automatiquement
if (!isset($_SESSION['user_id']) && isset($_COOKIE['wari_remember'])) {
    $token = $_COOKIE['wari_remember'];

    $stmt = $pdo->prepare("
        SELECT u.* FROM wari_users u
        WHERE u.remember_token = ?
        AND u.remember_expires > NOW()
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        // ✅ Vérifier que le compte n'est pas suspendu
        $stmtLic = $pdo->prepare("SELECT statut FROM wari_licences WHERE commande_id = ?");
        $stmtLic->execute([$user['commande_id']]);
        $licence = $stmtLic->fetch();

        if (!$licence || $licence['statut'] !== 'suspendu') {
            // ✅ Connexion automatique
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['login_time'] = time(); // Pour tracker
            $_SESSION['last_activity'] = time(); // Pour prolonger

            // ✅ Log de la visite automatique
            logAuthAttempt($pdo, 'AUTO_LOGIN', $user['email'], $user['id'], 'Connexion via cookie');

            // ✅ Prolonger la session PHP aussi
            setcookie(session_name(), session_id(), time() + (90 * 24 * 3600), '/', '', true, true);

            // ✅ On renouvelle le cookie pour 90 jours supplémentaires
            $newToken = bin2hex(random_bytes(32));
            $expires  = date('Y-m-d H:i:s', strtotime('+90 days'));

            $pdo->prepare("UPDATE wari_users SET remember_token = ?, remember_expires = ? WHERE id = ?")
                ->execute([$newToken, $expires, $user['id']]);

            setcookie('wari_remember', $newToken, [
                'expires'  => time() + (90 * 24 * 3600),
                'path'     => '/',
                'secure'   => true,
                'httponly' => true,
                'samesite' => 'Strict',
            ]);

            $redir = !empty($_POST['redirect']) ? $_POST['redirect'] : (!empty($_GET['redirect']) ? $_GET['redirect'] : '');
            if (!empty($redir)) {
                header('Location: ' . $redir);
            } else {
                header('Location: ../index.php');
            }
            exit();
        }
    } else {
        // Token invalide ou expiré → on supprime le cookie
        setcookie('wari_remember', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }
}

// ─── TRAITEMENT DU FORMULAIRE ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action'];
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    // ── INSCRIPTION ───────────────────────────────────────────────────────────
    if ($action === 'register') {
        $commande_id = trim($_POST['commande_id']);

        $stmt = $pdo->prepare("SELECT * FROM wari_licences WHERE commande_id = ? AND statut = 'disponible'");
        $stmt->execute([$commande_id]);
        $licence = $stmt->fetch();

        if ($licence) {
            $duree_jours = isset($licence['duree_jours']) ? intval($licence['duree_jours']) : 30;
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("INSERT INTO wari_users (email, password, commande_id) VALUES (?, ?, ?)");
                $stmt->execute([$email, $hashedPassword, $commande_id]);

                $stmt = $pdo->prepare("UPDATE wari_licences SET statut = 'utilise', date_expiration = DATE_ADD(NOW(), INTERVAL ? DAY) WHERE commande_id = ?");
                $stmt->execute([$duree_jours, $commande_id]);

                $redirParam = isset($_POST['redirect']) && !empty($_POST['redirect']) ? '&redirect=' . urlencode($_POST['redirect']) : '';
                header('Location: auth.php?success=1' . $redirParam);
                exit();
            } catch (Exception $e) {
                die("Erreur : Cet email est peut-être déjà utilisé.");
            }
        } else {
            die("Erreur : Numéro de commande invalide ou déjà utilisé.");
        }
    }

    // ── CONNEXION ─────────────────────────────────────────────────────────────
    // ── CONNEXION ─────────────────────────────────────────────────────────────
    elseif ($action === 'login') {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // 🔴 VÉRIFICATION : IP bloquée ?
        if (isIpBlocked($pdo, $ip)) {
            wari_alert("🚫 IP BLOQUÉE - Tentative depuis IP: $ip sur email: $email (trop d'échecs)", 'SECURITY');
            die("Trop de tentatives. Réessayez dans 10 minutes.");
        }

        $stmt = $pdo->prepare("SELECT * FROM wari_users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {

            // Vérification suspension
            $stmtLic = $pdo->prepare("SELECT statut FROM wari_licences WHERE commande_id = ?");
            $stmtLic->execute([$user['commande_id']]);
            $licence = $stmtLic->fetch();

            if ($licence && $licence['statut'] === 'suspendu') {
                logAuthAttempt($pdo, 'LOGIN_FAILED_SUSPENDED', $email, $user['id'], 'Compte suspendu');
                wari_alert("🔒 TENTATIVE SUR COMPTE SUSPENDU - User: {$user['email']} (ID: {$user['id']}) depuis IP: $ip", 'SECURITY');
                die("Accès suspendu. Contactez l'administrateur.");
            }

            // ✅ CONNEXION RÉUSSIE
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_email'] = $user['email'];

            logAuthAttempt($pdo, 'LOGIN_SUCCESS', $email, $user['id'], 'Connexion normale');

            // 🔐 Alerte si connexion après échecs récents
            $recentFails = countRecentFails($pdo, $ip);
            if ($recentFails > 0) {
                wari_alert("✅ CONNEXION APRÈS ÉCHECS - User: {$user['email']} a réussi après $recentFails échec(s) récent(s) depuis IP: $ip", 'SECURITY');
            }

            // ✅ Remember me — cookie 90 jours
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+90 days'));

            $pdo->prepare("UPDATE wari_users SET remember_token = ?, remember_expires = ? WHERE id = ?")
                ->execute([$token, $expires, $user['id']]);

            setcookie('wari_remember', $token, [
                'expires'  => time() + (90 * 24 * 3600),
                'path'     => '/',
                'secure'   => true,
                'httponly' => true,
                'samesite' => 'Strict',
            ]);

            $redir = !empty($_POST['redirect']) ? $_POST['redirect'] : (!empty($_GET['redirect']) ? $_GET['redirect'] : '');
            if (!empty($redir)) {
                header('Location: ' . $redir);
            } else {
                header('Location: ../index.php');
            }
            exit();
        } else {
            // 🔴 ÉCHEC DE CONNEXION
            logAuthAttempt($pdo, 'LOGIN_FAILED', $email, null, 'Mot de passe incorrect');

            $recentFails = countRecentFails($pdo, $ip);

            // Alerte progressive
            if ($recentFails == 3) {
                wari_alert("⚠️ TENTATIVES SUSPECTES - 3 échecs depuis IP: $ip sur email: $email", 'SECURITY');
            } elseif ($recentFails == 5) {
                wari_alert("🚨 BRUTE FORCE DÉTECTÉ - 5 échecs depuis IP: $ip - BLOCAGE ACTIVÉ", 'SECURITY');
            }

            die("Identifiants incorrects.");
        }
    }
}

```

### `config/save_subscription.php`

```php
<?php
// 1. Toujours le monitoring et la config session en premier
require_once __DIR__ . '/../wari_monitoring.php'; 
require 'session_config.php'; // Gère le session_start() et les 90 jours

// 2. Pas besoin de session_start() ici si session_config le fait déjà
require 'db.php';
require 'no_cache.php';

// 3. Le check de session (qui renverra le 403 propre si déconnecté)
require 'session_check.php'; 

// À partir d'ici, on est SÛR que l'utilisateur est connecté grâce à session_check
$userId = $_SESSION['user_id'];

// 4. Récupération des données JSON
$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['endpoint'])) {
    $endpoint = $data['endpoint'];
    $p256dh   = $data['keys']['p256dh'] ?? '';
    $auth     = $data['keys']['auth'] ?? '';

    // Vérification des doublons
    $check = $pdo->prepare("SELECT id FROM wari_subscriptions WHERE user_id = ? AND endpoint = ?");
    $check->execute([$userId, $endpoint]);

    if (!$check->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO wari_subscriptions (user_id, endpoint, p256dh, auth) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $endpoint, $p256dh, $auth]);
    }

    echo json_encode(['success' => true]);
} else {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
}
```

### `config/save_user_challenge.php`

```php
<?php
// /var/www/wari.digiroys.com/config/save_user_challenge.php
require_once __DIR__ . '/../wari_monitoring.php'; // Toujours en premier
require_once __DIR__ . '/session_config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/no_cache.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$action = $data['action'] ?? '';

if ($action === 'submit') {
    $category = $data['category'] ?? '';
    $message = trim($data['message'] ?? '');
    
    // Validation
    $validCategories = ['repartition', 'coach', 'academy', 'vecu', 'autre'];
    if (!in_array($category, $validCategories)) {
        echo json_encode(['success' => false, 'error' => 'Catégorie invalide']);
        exit;
    }
    
    if (empty($message)) {
        echo json_encode(['success' => false, 'error' => 'Message vide']);
        exit;
    }
    
    // Sécurité : Nettoyer le message
    $cleanMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    
    try {
        $pdo->beginTransaction();
        
        // 1. Insérer le défi
        $stmt = $pdo->prepare("INSERT INTO wari_user_challenges (user_id, category, message) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $category, $cleanMessage]);
        
        // 2. Mettre à jour le statut de l'utilisateur
        $stmtUpdate = $pdo->prepare("UPDATE wari_users SET feedback_status = 1, last_feedback_prompt_at = NOW() WHERE id = ?");
        $stmtUpdate->execute([$_SESSION['user_id']]);
        
        $pdo->commit();
        
        // 3. Notifier l'admin via Telegram (Message sur-mesure propre)
        if (defined('MONITORING_TELEGRAM_BOT_TOKEN') && defined('MONITORING_TELEGRAM_CHAT_ID') && MONITORING_TELEGRAM_BOT_TOKEN && MONITORING_TELEGRAM_CHAT_ID) {
            $userEmail = $_SESSION['user_email'] ?? 'Utilisateur inconnu';
            
            $text = "<b>NOUVEAU DÉFI SIGNALÉ SUR WARI</b>\n\n";
            $text .= "<b>Utilisateur :</b> $userEmail\n";
            $text .= "<b>Module :</b> " . ucfirst($category) . "\n";
            $text .= "<b>Description :</b>\n<i>\"" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "\"</i>\n\n";
            $text .= "<b>Signalé le :</b> " . date('d/m/Y H:i:s') . "\n";
            $text .= "<i>Gérer les retours sur la console Admin Wari.</i>";
            
            $telegramUrl = "https://api.telegram.org/bot" . MONITORING_TELEGRAM_BOT_TOKEN . "/sendMessage";
            $postData = [
                'chat_id' => MONITORING_TELEGRAM_CHAT_ID,
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true
            ];
            
            $ch = curl_init($telegramUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            @curl_exec($ch);
            @curl_close($ch);
        }
        
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'error' => 'Erreur lors de la sauvegarde : ' . $e->getMessage()]);
    }
    
} elseif ($action === 'dismiss') {
    try {
        $stmtUpdate = $pdo->prepare("UPDATE wari_users SET last_feedback_prompt_at = NOW() WHERE id = ?");
        $stmtUpdate->execute([$_SESSION['user_id']]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Action inconnue']);
}

```

### `config/update_challenge.php`

```php
<?php
// /var/www/wari.digiroys.com/config/update_challenge.php
session_start();
require 'session_config.php';
require 'db.php';
require 'no_cache.php';
require 'session_check.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

if (!isset($_SESSION['is_premium']) || !$_SESSION['is_premium']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Cette fonctionnalité requiert un abonnement Wari Premium']);
    exit();
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

$action = $input['action'] ?? 'deposit'; // 'deposit' ou 'complete' (pour no_frivolities)
$challenge_id = isset($input['challenge_id']) ? intval($input['challenge_id']) : null;

if (!$challenge_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de défi manquant']);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Récupérer le défi
    $stmt = $pdo->prepare("SELECT * FROM wari_savings_challenges WHERE id = ? AND user_id = ? AND status = 'active'");
    $stmt->execute([$challenge_id, $user_id]);
    $challenge = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$challenge) {
        throw new Exception("Défi introuvable ou non actif");
    }

    $amount_to_save = 0;
    $metadata = json_decode($challenge['metadata'], true) ?? [];

    if ($challenge['challenge_type'] === '52_weeks') {
        if ($action !== 'deposit') {
            throw new Exception("Action non valide pour ce défi");
        }
        $week_number = isset($input['week_number']) ? intval($input['week_number']) : null;
        if (!$week_number || $week_number < 1 || $week_number > 52) {
            throw new Exception("Numéro de semaine invalide");
        }

        // Vérifier si déjà validé
        $checked_weeks = $metadata['checked_weeks'] ?? [];
        if (in_array($week_number, $checked_weeks)) {
            throw new Exception("Cette semaine a déjà été validée");
        }

        $amount_to_save = $week_number * intval($challenge['base_amount']);
        $checked_weeks[] = $week_number;
        sort($checked_weeks);
        $metadata['checked_weeks'] = $checked_weeks;
        $label_vault = "Défi 52 sem - Semaine " . $week_number;

    } elseif ($challenge['challenge_type'] === 'emergency_fund') {
        if ($action !== 'deposit') {
            throw new Exception("Action non valide pour ce défi");
        }
        $amount_to_save = isset($input['amount']) ? intval($input['amount']) : 0;
        if ($amount_to_save <= 0) {
            throw new Exception("Montant de versement invalide");
        }
        $label_vault = "Dépôt Fonds Urgence";

    } elseif ($challenge['challenge_type'] === 'no_frivolities') {
        if ($action === 'complete') {
            // Validation finale du défi de 7 jours
            $end_time = strtotime($metadata['end_date']);
            if (time() < $end_time) {
                throw new Exception("Le défi n'est pas encore terminé (attendre la fin des 7 jours)");
            }
            if ($metadata['failed'] === true) {
                throw new Exception("Ce défi a déjà échoué");
            }

            // Marquer comme complété
            $stmtComplete = $pdo->prepare("
                UPDATE wari_savings_challenges 
                SET status = 'completed' 
                WHERE id = ?
            ");
            $stmtComplete->execute([$challenge_id]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Félicitations ! Défi complété avec succès !']);
            exit();
        } else {
            throw new Exception("Action non prise en charge pour ce défi");
        }
    }

    // --- MISE À JOUR TECHNIQUE ET FINANCIÈRE ---

    // A. Récupérer l'utilisateur pour éditer son budget JSON
    $stmtUser = $pdo->prepare("SELECT budget_data, project_capital FROM wari_users WHERE id = ?");
    $stmtUser->execute([$user_id]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$user || !$user['budget_data']) {
        throw new Exception("Données utilisateur introuvables");
    }

    $budgetData = json_decode($user['budget_data'], true);
    $categories = &$budgetData['categories'];

    // B. Déduire de la Poche (par défaut Train de vie id=2, sinon la première catégorie ayant du solde)
    $category_id_to_deduct = isset($input['category_id']) ? intval($input['category_id']) : 2;
    $deducted = false;

    foreach ($categories as &$cat) {
        if ($cat['id'] == $category_id_to_deduct) {
            if ($cat['balance'] < $amount_to_save) {
                throw new Exception("Solde insuffisant dans la catégorie " . $cat['name']);
            }
            $cat['balance'] = max(0, $cat['balance'] - $amount_to_save);
            $deducted = true;
            break;
        }
    }

    if (!$deducted) {
        // Fallback sur la première catégorie ayant assez de solde et n'étant pas Projet ou Épargne
        foreach ($categories as &$cat) {
            $catNameLower = strtolower($cat['name']);
            if (strpos($catNameLower, 'projet') === false && strpos($catNameLower, 'épargne') === false) {
                if ($cat['balance'] >= $amount_to_save) {
                    $cat['balance'] = max(0, $cat['balance'] - $amount_to_save);
                    $deducted = true;
                    break;
                }
            }
        }
    }

    if (!$deducted) {
        throw new Exception("Solde insuffisant dans votre Poche pour ce versement.");
    }

    // C. Mettre à jour le capital projet (coffre-fort)
    $new_project_capital = intval($user['project_capital']) + $amount_to_save;
    $budgetData['projectCapital'] = $new_project_capital;

    // D. Prepend une transaction de coffre-fort dans le JSON pour le rendu immédiat
    // Format français abrégé
    $months_fr = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sept', 'Oct', 'Nov', 'Déc'];
    $date_formatted = date('d') . ' ' . $months_fr[intval(date('n')) - 1];

    $newTx = [
        'date' => $date_formatted,
        'type' => 'in',
        'amount' => $amount_to_save,
        'label' => $label_vault
    ];

    $vaultTransactions = $budgetData['vaultTransactions'] ?? [];
    array_unshift($vaultTransactions, $newTx);
    if (count($vaultTransactions) > 20) {
        array_pop($vaultTransactions);
    }
    $budgetData['vaultTransactions'] = $vaultTransactions;

    // E. Insérer le mouvement dans la table wari_vault_history SQL
    $stmtVault = $pdo->prepare("INSERT INTO wari_vault_history (user_id, type, amount, label) VALUES (?, 'in', ?, ?)");
    $stmtVault->execute([$user_id, $amount_to_save, $label_vault]);

    // F. Sauvegarder l'utilisateur SQL
    $stmtUserUpdate = $pdo->prepare("
        UPDATE wari_users 
        SET budget_data = ?, project_capital = ?, last_budget_at = NOW() 
        WHERE id = ?
    ");
    $stmtUserUpdate->execute([json_encode($budgetData), $new_project_capital, $user_id]);

    // G. Mettre à jour le Défi d'Épargne
    $new_current_amount = intval($challenge['current_amount']) + $amount_to_save;
    
    // Vérification de complétion
    $new_status = 'active';
    if ($challenge['challenge_type'] !== 'no_frivolities' && $new_current_amount >= intval($challenge['target_amount'])) {
        $new_status = 'completed';
    }

    $stmtChallengeUpdate = $pdo->prepare("
        UPDATE wari_savings_challenges 
        SET current_amount = ?, status = ?, metadata = ? 
        WHERE id = ?
    ");
    $stmtChallengeUpdate->execute([$new_current_amount, $new_status, json_encode($metadata), $challenge_id]);

    // Récupérer le défi mis à jour
    $stmtGetChallenge = $pdo->prepare("SELECT * FROM wari_savings_challenges WHERE id = ?");
    $stmtGetChallenge->execute([$challenge_id]);
    $updatedChallenge = $stmtGetChallenge->fetch(PDO::FETCH_ASSOC);

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'challenge' => $updatedChallenge, 
        'budget_data' => $budgetData
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

```

### `academy-admin/ai_gateway.php`

```php
<?php
// /var/www/wari.digiroys.com/academy-admin/ai_gateway.php

error_reporting(E_ALL);
ini_set('display_errors', 0); // Empêche la pollution du JSON par des erreurs HTML

if (session_status() === PHP_SESSION_NONE) session_start();
// Autoriser soit l'admin de l'academy, soit l'utilisateur du dashboard principal
if (!isset($_SESSION['academy_user']) && !isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Accès non autorisé']);
    exit;
}

try {
    require_once __DIR__ . '/../classes/AI.php';
    $ai = new AI();

    header('Content-Type: application/json; charset=utf-8');

    $action = $_POST['action'] ?? '';

    switch ($action) {
    case 'draft_course':
        $sujet = $_POST['sujet'] ?? '';
        if (!$sujet) {
            echo json_encode(['error' => 'Sujet manquant']);
            break;
        }

        $prompt = "Génère un brouillon complet pour un nouveau cours sur le sujet : '$sujet'. 
        Génère EXACTEMENT entre 3 et 5 leçons au maximum. Les leçons doivent s'enchaîner de manière logique, comme une histoire continue. Chaque leçon doit préparer la suivante.
        Tu dois impérativement retourner UN SEUL OBJET JSON (pas une liste) avec les clés :
        - 'titre' (accrocheur, attirant et pro)
        - 'description' (2-3 phrases impactantes)
        - 'niveau' (debutant, intermediaire ou avance)
        - 'duree_minutes' (estimation)
        - 'lecons' (tableau d'objets avec 'titre' et 'type' [texte ou video])";

        $system = "Tu es l'expert pédagogique de Wari Academy. Tes cours sont des coups de poing de réalité financière. Fini la théorie, place à l'action. NE FAIS PAS DES COURS TROP LONGS, 3 à 5 leçons suffisent amplement.";

        echo $ai->generate($prompt, $system);
        break;

    case 'write_lesson':
        $titreLecon = $_POST['titre_lecon'] ?? '';
        $courseId = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
        $currentOrdre = isset($_POST['ordre']) ? (int)$_POST['ordre'] : 0;

        if (!$titreLecon) {
            echo json_encode(['error' => 'Titre de leçon manquant']);
            break;
        }

        $courseContext = "";
        $syllabus = "";
        $previousLessonContent = "";

        if ($courseId > 0) {
            try {
                require_once __DIR__ . '/../config/db.php';

                // 1. Infos du cours
                $stmtCourse = $pdo->prepare("SELECT titre, description FROM academy_courses WHERE id = ?");
                $stmtCourse->execute([$courseId]);
                $course = $stmtCourse->fetch(PDO::FETCH_ASSOC);

                if ($course) {
                    $courseContext = "Titre du cours : \"" . $course['titre'] . "\"\nDescription du cours : " . $course['description'];
                }

                // 2. Syllabus (Toutes les leçons du cours pour la chronologie)
                $stmtLessons = $pdo->prepare("SELECT titre, ordre FROM academy_lessons WHERE course_id = ? ORDER BY ordre ASC");
                $stmtLessons->execute([$courseId]);
                $allLessons = $stmtLessons->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($allLessons)) {
                    $syllabus = "Syllabus complet du cours (les leçons s'enchaînent dans cet ordre) :\n";
                    foreach ($allLessons as $l) {
                        $mark = ($l['ordre'] == $currentOrdre) ? " -> [CETTE LEÇON]" : "";
                        $syllabus .= "- Leçon " . $l['ordre'] . " : " . $l['titre'] . $mark . "\n";
                    }
                }

                // 3. Contenu de la leçon précédente
                $stmtPrev = $pdo->prepare("SELECT titre, contenu FROM academy_lessons WHERE course_id = ? AND ordre < ? ORDER BY ordre DESC LIMIT 1");
                $stmtPrev->execute([$courseId, $currentOrdre]);
                $prev = $stmtPrev->fetch(PDO::FETCH_ASSOC);

                if ($prev) {
                    $previousLessonContent = "Détail de la leçon précédente (que l'élève vient de terminer) :\n"
                                           . "Titre : \"" . $prev['titre'] . "\"\n"
                                           . "Contenu textuel (pour faire une transition fluide) : " . mb_substr(strip_tags($prev['contenu']), 0, 1000) . "\n";
                }
            } catch (Exception $e) {
                // Fallback silencieux en cas d'erreur DB
            }
        }

        if (!$courseContext) {
            $courseContext = $_POST['cours_context'] ?? 'Cours Wari Academy';
        }

        $prompt = "Tu dois rédiger le contenu de la leçon actuelle : '$titreLecon'.

CONTEXTE GÉNÉRAL DU COURS :
$courseContext

$syllabus

$previousLessonContent

RÈGLES PÉDAGOGIQUES CRUCIALES POUR LA RÉDACTION :
1. CHRONOLOGIE ET TRANSITION :
   - Fais une transition fluide et naturelle avec la leçon précédente si elle existe (ex: 'Après avoir vu X dans la leçon précédente, passons maintenant à...').
   - Ne répète PAS les concepts introductifs ou généraux déjà expliqués dans la leçon précédente.
   - Cette leçon est l'étape numéro $currentOrdre du cours. Rédige-la comme une suite logique, pas comme un résumé du cours entier.
   - Ne déborde PAS sur le sujet des leçons suivantes listées dans le syllabus.
2. FORMAT ET CONCISENESS :
   - Le contenu doit être court, direct et percutant (maximum 200 à 300 mots).
   - Utilise des exemples concrets et terre-à-terre du quotidien ou des finances en Afrique (les tontines, le Mobile Money, les enveloppes, le marché, les petites boutiques, etc.).
3. MISSION D'AUJOURD'HUI :
   - Inclus OBLIGATOIREMENT à la fin de la leçon une section 'MISSION D'AUJOURD'HUI' : une petite action concrète et rapide que l'élève peut faire immédiatement.
4. CODE ET FORMAT DE RETOUR :
   - Tu dois impérativement retourner un objet JSON contenant uniquement la clé 'contenu' avec ton code HTML.
   - Utilise uniquement les balises : <h2> pour les titres de section, <p> pour le texte, <ul> et <li> pour les listes.
   - Le bloc de la mission doit être balisé avec ce code HTML exact :
     <div class='bg-slate-800 border-l-4 border-gold-500 p-4 my-4'><div class='text-emerald-500 font-bold mb-1'>💡 MISSION D'AUJOURD'HUI</div>Le texte de la mission concrète...</div>";

        $system = "Tu es le rédacteur principal de Wari Academy. Ton ton est direct, sans filtre, et hyper-pratique. Tu ne fais pas de longs discours. Tu vas droit au but pour aider l'utilisateur à sortir de la pauvreté.";

        $htmlResult = $ai->generate($prompt, $system);
        
        // 1. Nettoyer les balises de code markdown si l'IA en a ajouté autour du JSON
        $htmlResult = preg_replace('/^```(?:json)?\s*/i', '', $htmlResult);
        $htmlResult = preg_replace('/\s*```$/', '', $htmlResult);
        $htmlResult = trim($htmlResult);

        // 2. Décoder le JSON retourné par l'IA (car l'API Gemini/Groq est forcée en responseMimeType JSON)
        $decoded = json_decode($htmlResult, true);
        
        if (json_last_error() === JSON_ERROR_NONE && $decoded !== null) {
            if (is_array($decoded) && isset($decoded['contenu'])) {
                $content = $decoded['contenu'];
            } else {
                $content = is_string($decoded) ? $decoded : $htmlResult;
            }
        } else {
            $content = $htmlResult;
        }

        // 3. Nettoyage final des balises markdown de bloc si l'IA en a ajouté dans la chaîne
        $content = preg_replace('/^```(?:html)?\s*/i', '', $content);
        $content = preg_replace('/\s*```$/', '', $content);
        $content = trim($content, " \t\n\r\0\x0B\"");

        echo json_encode(['contenu' => $content]);
        break;

    case 'generate_quiz':
        $contenuLecon = $_POST['contenu'] ?? '';
        if (!$contenuLecon) {
            echo json_encode(['error' => 'Contenu de leçon manquant pour le quiz']);
            break;
        }

        $prompt = "Génère un quiz de 3 questions basé sur ce contenu : " . mb_substr(strip_tags($contenuLecon), 0, 2000) . ".
        Retourne un JSON avec une clé 'questions' qui est un tableau d'objets :
        - 'question' (texte)
        - 'options' (tableau de 3-4 textes)
        - 'reponse_index' (index de la bonne réponse dans le tableau options)";

        $system = "Tu es l'évaluateur de Wari Academy. Tes questions vérifient la compréhension pratique de l'élève.";

        echo $ai->generate($prompt, $system);
        break;

    case 'get_coach_advice':
        $financialData = $_POST['data'] ?? ''; 
        
        $prompt = "Analyse ces données financières Wari et réponds EXCLUSIVEMENT au format JSON.
        
        DONNÉES : $financialData. 
        
        CONSIGNES POUR TON ANALYSE :
        1. PRÉDICTION : Calcule la trajectoire en utilisant 'temporal.days_left' et 'daily_budget'. Estime une date de fin de cash (ex: le 25 du mois) ou confirme que tout va bien.
        2. BUDGET : Rappelle le 'daily_budget' comme une règle d'or pour le reste du mois.
        3. DETTES : Si 'total_debts' > 0, donne un conseil prioritaire de remboursement.
        4. ACADEMY : Recommande un cours parmi : 'L\'art de l\'épargne forcée', 'Gérer son fonds de roulement', 'Négocier ses dettes' ou 'Investir en soi'.
        
        STRUCTURE JSON À RETOURNER :
        {
          \"message\": \"Ton conseil de Grand Frère expert (direct, motivant, max 2 phrases).\",
          \"prediction\": \"Ta prédiction de date + budget quotidien (ex: 'Fin de cash estimée le 28. Règle d'or : 5 000 F / jour max').\",
          \"dette_conseil\": \"Conseil dettes (si applicable, sinon vide).\",
          \"academy_reco\": \"Titre du cours recommandé.\",
          \"alerte_rouge\": \"Message Choc de 5 mots (si critique, sinon vide).\"
        }";

        $system = "Tu es le Coach Wari, le Grand Frère de la souveraineté financière en Afrique. 
        Tu es un expert rigoureux sur les chiffres mais profondément motivant. 
        Ton ton doit être direct, utilisant des images fortes du quotidien. Appelle l'utilisateur 'Champion·ne'.";

        echo $ai->generate($prompt, $system);
        break;

    case 'coach_chat':
        $userMessage = $_POST['message'] ?? '';
        $financialData = $_POST['data'] ?? '';
        $chatHistory = $_POST['history'] ?? '[]';

        if (!$userMessage) {
            echo json_encode(['error' => 'Message vide']);
            break;
        }

        // Récupération des cours actifs de l'Academy pour enrichir le contexte
        $coursesInfo = "";
        try {
            require_once __DIR__ . '/../config/db.php';
            $stmt = $pdo->query("SELECT titre, slug, description FROM academy_courses WHERE est_actif = 1 LIMIT 8");
            $activeCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($activeCourses)) {
                $coursesInfo = "COURS ACTIFS DISPONIBLES DANS WARI ACADEMY (à recommander chaleureusement avec leur titre si pertinent pour l'éduquer) :\n";
                foreach ($activeCourses as $course) {
                    $coursesInfo .= "- Formation : \"" . $course['titre'] . "\" (slug: " . $course['slug'] . ") : " . $course['description'] . "\n";
                }
            }
        } catch (Exception $e) {
            $coursesInfo = "Aucun cours d'academy n'est disponible actuellement.";
        }

        $prompt = "Tu es le Coach Wari, un mentor financier bienveillant, rigoureux et mature, agissant comme le grand frère de la souveraineté financière en Afrique.
        Tu as accès aux données budgétaires réelles de l'utilisateur sous forme de JSON pour personnaliser tes réponses.
        
        DONNÉES FINANCIÈRES :
        $financialData
        
        CATALOGUE DE COURS WARI ACADEMY :
        $coursesInfo
        
        HISTORIQUE RÉCENT DE LA CONVERSATION :
        $chatHistory
        
        MESSAGE DE L'UTILISATEUR :
        '$userMessage'
        
        Consignes absolues pour ton comportement et ton ton :
        1. BANISSEMENT DU SURNOM 'CHAMPION·NE' ET NOMS FAMILIERS : Tu as l'interdiction FORMELLE et absolue d'utiliser le terme \"Champion·ne\", \"Mon frère\", \"Ma sœur\", \"Fréro\", ou tout autre surnom. Adresse-toi à l'utilisateur directement et naturellement en disant \"tu\" (ou \"vous\" selon la phrase), de manière sincère, respectueuse, digne et humaine.
        2. RESPECT DU BUDGET ET DES POURCENTAGES DÉFINIS PAR L'UTILISATEUR : Dans les DONNÉES FINANCIÈRES, tu reçois les enveloppes actives de l'utilisateur avec le \"Pourcentage cible\" pour le Portefeuille Personnel (\"enveloppes_personnelles_details\") et le Portefeuille Professionnel (\"enveloppes_professionnelles_details\"). Tu reçois également le portefeuille actuellement ouvert et visible par l'utilisateur (\"portefeuille_actif\"). Si l'utilisateur a configuré ses propres enveloppes ou modifié ses pourcentages cibles, tu DOIS te baser EXCLUSIVEMENT sur ses propres réglages personnalisés pour faire tes analyses et lui donner des conseils. Analyse spécifiquement le portefeuille actif, mais garde à l'esprit l'autre portefeuille pour des conseils globaux. De plus, tu reçois la synthèse des Défis d'épargne en cours (\"defis_epargne_actifs_details\") et le journal des 25 dernières dépenses réelles enregistrées (\"depenses_recentes_details\") pour que tu aies une vision omnisciente de toutes ses actions de dépenses, notes de frais et configurations sans rien louper de son activité. Si et seulement si les données personnalisées de pourcentages cibles sont absentes ou vides, tu peux alors lui suggérer la méthode de référence par défaut des 4 enveloppes de Wari (Train de vie 50%, Projet 25%, Épargne 15%, Imprévu 10%).
        3. RECOMMANDATION DES COURS DE L'ACADEMY : Si l'utilisateur exprime le besoin d'apprendre, de s'éduquer, de comprendre l'investissement, la gestion des dettes ou s'il fait face à des blocages financiers, recommande-lui spécifiquement un cours actif de Wari Academy issu du catalogue ci-dessus en le nommant clairement.
        4. PAS DE SALUTATIONS / SIGNATURES RÉPÉTITIVES : Ne commence JAMAIS tes réponses par un salut au milieu d'une discussion continue, sauf si le message de l'utilisateur est une salutation initiale. Ne mets pas de phrases de clôture stéréotypées (ex. \"Force à toi !\", \"Bonne chance !\") à la fin de chaque message. Réponds directement, naturellement et de façon fluide, comme dans une discussion instantanée WhatsApp.
        5. PARAGRAPHES ET SAUTS DE LIGNE : N'utilise JAMAIS de listes numérotées rigides (1..., 2..., 3...) ou de tirets. Divise tes réponses en courts paragraphes aérés, séparés obligatoirement par des sauts de ligne doubles (\\n\\n) pour structurer ton discours. Ne rédige jamais un seul bloc de texte compact qui serait difficile à lire sur un écran de téléphone.
        6. INTÉGRATION DE L'OBJECTIF VISÉ : Dans le JSON, tu reçois le capital actuel du projet ('capital_projet_perso' ou 'capital_projet_pro') ainsi que l'objectif ciblé par l'utilisateur ('objectif_projet_perso_montant'/'objectif_projet_pro_montant' et leurs labels). Lorsque tu parles de son projet, fais référence au nom de son projet et calcule sa progression exacte (ex: 'Tu as déjà mis de côté 4 000 F sur ton objectif de 250 000 F pour ton projet de Terrain').
        7. HUMANISER AU MAXIMUM (EMPATHIE ET SAGESSE) : Apporte une réelle profondeur humaine et de l'empathie à tes réponses. Comprends les difficultés réelles de la vie (les sollicitations de la famille, le coût de la vie au pays, la tentation de gaspiller son argent sur un coup de tête).
        8. SIMPLE SALUTATION = RÉPONSE SIMPLE ET CHALEUREUSE : Si le message est un simple salut initial, réponds simplement et chaleureusement sans aucun chiffre financier (ex : \"Salut, ravi de te retrouver ! Comment se passe ta journée ? De quoi veux-tu qu'on parle aujourd'hui ?\").
        9. BANIS LES EXCLAMATIONS ARTIFICIELLES : N'utilise JAMAIS de mots d'exclamation artificiels ou robotiques comme \"Waaah\", \"Waooh\", \"Wari !\", ou \"Ohh\". Sois mature et posé.
        10. CONCISION DYNAMIQUE : Limite ta réponse à 3 ou 4 phrases maximum. Sois percutant, va droit au but sans fioritures et sans faire de longs discours théoriques.
        11. CONNAISSANCE DES FONCTIONNALITÉS PREMIUM : Maîtrise parfaitement les outils Premium de Wari (disponibles pour seulement 590F) pour en parler ou guider l'utilisateur : le Planificateur de Dettes (Méthode Boule de Neige) pour son apurement de dettes, le Simulateur d'Investissement UEMOA pour calculer ses gains d'épargne régionaux, les Graphiques de Tendance (Évolution mensuelle, Taux d'Épargne %, Donut de répartition des enveloppes), l'Export de Bilan financier, le Portefeuille Pro et les Défis d'épargne. Si l'utilisateur est Premium, conseille-lui d'utiliser ces outils pour résoudre ses problèmes. S'il est gratuit, suggère-lui subtilement comment ces outils spécifiques peuvent l'aider.
        12. COMPARAISON DE L'HISTORIQUE SUR 6 MOIS : Dans les DONNÉES FINANCIÈRES, tu reçois également la synthèse de l'historique financier des 6 derniers mois (\"historique_6_derniers_mois\"). Si l'utilisateur te demande son évolution, s'il s'améliore ou s'il fait un bilan global, utilise cette table historique pour lui répondre en comparant ses mois récents. Sois factuel et félicite-le s'il s'améliore ou alerte-le poliment s'il régresse.
        13. CONNAISSANCE DU JOURNAL DE BORD \"WARI VÉCU\" : Dans les DONNÉES FINANCIÈRES, tu reçois la liste des journaux de bord rédigés par l'auteur Esdras (\"articles_vecu_details\"). Ce journal intime de discipline s'appelle \"Wari Vécu\". Si l'utilisateur a besoin de motivation, de comprendre la discipline financière face aux pressions familiales ou sociales, ou s'il te demande ce qu'on raconte dans le journal, recommande-lui d'aller lire le journal \"Wari Vécu\" en citant un titre pertinent de la liste pour l'inspirer.
        
        Tu dois impérativement répondre sous ce format JSON exact :
        {
            \"response\": \"Ta réponse sous forme de texte simple, rédigée de manière causante et structurée en courts paragraphes aérés par des sauts de ligne (\\\\n\\\\n), sans liste et sans salutation/clôture répétitive.\"
        }";

        $system = "Tu es le Coach Wari, un mentor de confiance dévoué à 100% et expert en discipline financière en Afrique. Tu parles directement avec franchise, respect, maturité, bienveillance et rigueur budgétaire.";

        echo $ai->generate($prompt, $system);
        break;

    case 'save_draft_course':
        require_once __DIR__ . '/../config/db.php';
        $titre = trim($_POST['titre'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $niveau = $_POST['niveau'] ?? 'debutant';
        $duree_minutes = (int)($_POST['duree_minutes'] ?? 10);
        $category_id = (int)($_POST['category_id'] ?? 0);
        
        if (!$titre || !$category_id) {
            echo json_encode(['error' => 'Titre ou catégorie manquant pour la sauvegarde.']);
            break;
        }
        
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $titre)), '-')) . '-' . time();
        
        $stmt = $pdo->prepare("
            INSERT INTO academy_courses (category_id, slug, titre, description, niveau, duree_minutes, auteur, est_gratuit, est_actif)
            VALUES (?, ?, ?, ?, ?, ?, 'Wari Finance', 1, 1)
        ");
        $stmt->execute([$category_id, $slug, $titre, $description, $niveau, $duree_minutes]);
        
        echo json_encode(['success' => true, 'course_id' => $pdo->lastInsertId()]);
        break;

    case 'save_draft_lesson':
        require_once __DIR__ . '/../config/db.php';
        $course_id = (int)($_POST['course_id'] ?? 0);
        $titre = trim($_POST['titre'] ?? '');
        $contenu = trim($_POST['contenu'] ?? '');
        $type = $_POST['type'] ?? 'texte';
        $ordre = (int)($_POST['ordre'] ?? 0);
        
        if (!$course_id || !$titre || !$contenu) {
            echo json_encode(['error' => 'Données manquantes pour la sauvegarde de la leçon.']);
            break;
        }
        
        $pdo->prepare("
            INSERT INTO academy_lessons (course_id, titre, contenu, type, ordre, est_actif)
            VALUES (?, ?, ?, ?, ?, 1)
        ")->execute([$course_id, $titre, $contenu, $type, $ordre]);
        
        echo json_encode(['success' => true, 'lesson_id' => $pdo->lastInsertId()]);
        break;

    case 'notify_course_published':
        require_once __DIR__ . '/../config/db.php';
        $course_id = (int)($_POST['course_id'] ?? 0);
        
        if ($course_id) {
            $stmt = $pdo->prepare("SELECT slug, titre FROM academy_courses WHERE id = ?");
            $stmt->execute([$course_id]);
            $course = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($course) {
                try {
                    require_once __DIR__ . '/../classes/Push.php';
                    $pushTitle = "Nouveau cours disponible ! 📚";
                    $pushBody  = "Découvrez le cours : \"" . $course['titre'] . "\" sur Wari Academy.";
                    $pushUrl   = "https://wari.digiroys.com/academy/course.php?slug=" . urlencode($course['slug']) . "&utm_source=push&utm_campaign=new_course";
                    Push::sendToAll($pdo, $pushTitle, $pushBody, $pushUrl, 'course', $course['slug']);
                    echo json_encode(['success' => true]);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                }
            } else {
                echo json_encode(['error' => 'Cours non trouvé']);
            }
        } else {
            echo json_encode(['error' => 'ID manquant']);
        }
        break;

    case 'generate_course_ideas':
        require_once __DIR__ . '/../config/db.php';
        
        $theme = trim($_POST['theme'] ?? '');
        $themeContext = $theme ? "Contexte obligatoire : Le cours doit porter spécifiquement sur le thème suivant : '$theme'." : "Thème : Général (éducation financière, épargne, gestion, mentalité, investissement...).";

        $stmt = $pdo->query("SELECT titre FROM academy_courses");
        $existing = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $existingStr = empty($existing) ? "Aucun" : implode("', '", $existing);

        $system = "Tu es un concepteur de formations en éducation financière. Ton audience : des jeunes qui veulent sortir de la pauvreté.
$themeContext
RÈGLES :
1. Titres ultra-directs, percutants (hook).
2. PAS de 'gros français' compliqué.
3. Formats: 'Comment...', 'Le secret...', '[Sujet] : action...', etc.
4. NE DOIT PAS être un de ces titres existants : '$existingStr'.
Retourne STRICTEMENT un objet JSON valide (sans markdown) : { \"idees\": [\"Titre 1\", \"Titre 2\", \"Titre 3\", \"Titre 4\", \"Titre 5\"] }";

        $prompt = "Génère 5 idées de titres de cours percutants.";
        echo $ai->generate($prompt, $system);
        break;

    default:
        echo json_encode(['error' => 'Action inconnue']);
        break;
}

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

```

### `paid/fedapay-checkout.php`

```php
<?php
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);

// On charge les dépendances installées via Composer
require_once '../vendor/autoload.php';

// On inclut la connexion à la base de données
require_once '../config/db.php';

require_once '../config/session_config.php';

// Configuration FedaPay
\FedaPay\FedaPay::setApiKey("sk_live_-t3Pw_JoJ8VGBqP8eTZr-ar5"); // Remplace par ta clé secrète
\FedaPay\FedaPay::setEnvironment('live'); // Passe en 'live' quand tu es prêt

// 1. Détection Recharge vs Nouvel Achat
$user_id = $_SESSION['user_id'] ?? null;
$commande_id = null;
$type_paiement = 'achat'; // Par défaut

if ($user_id) {
    // Mode Recharge : Récupérer les détails depuis la session
    $stmt = $pdo->prepare("SELECT email, commande_id FROM wari_users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if ($user) {
        $customer_email = $user['email'];
        $commande_id = $user['commande_id'];
        $type_paiement = 'recharge';
    } else {
        die("Utilisateur de session non valide.");
    }
} else {
    // Mode Achat Licence
    $email_brut = $_POST['customer_email'] ?? null;
    $customer_email = filter_var($email_brut, FILTER_SANITIZE_EMAIL);
}

if (!$customer_email) {
    die("L'adresse email est requise pour continuer.");
}

// 2. Choix de la formule
$plan = $_POST['plan'] ?? 'mensuel';
$amount = ($plan === 'annuel') ? 5000 : 590;
$duree_jours = ($plan === 'annuel') ? 365 : 30;

$description = ($type_paiement === 'recharge')
    ? "Recharge Wari Finance - " . ($plan === 'annuel' ? '12 mois' : '1 mois')
    : "Achat Licence Wari Finance - " . ($plan === 'annuel' ? '12 mois' : '1 mois');

try {
    // A. Création de la transaction chez FedaPay
    $transaction = \FedaPay\Transaction::create([
        "description" => $description,
        "amount" => $amount,
        "currency" => ["iso" => "XOF"],
        "callback_url" => "https://wari.digiroys.com/paid/fedapay-callback.php",
        "customer" => [
            "email" => $customer_email
        ]
    ]);

    // B. Récupération de l'URL de paiement
    $token = $transaction->generateToken();

    // C. Enregistrement dans la table wari_payments avec les nouvelles colonnes
    $stmt = $pdo->prepare("
        INSERT INTO wari_payments 
        (reference_fedapay, email_client, montant, statut, type_paiement, commande_id, duree_jours, date_creation) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $transaction->id,
        $customer_email,
        $amount,
        'pending',
        $type_paiement,
        $commande_id,
        $duree_jours
    ]);

    // D. Redirection vers FedaPay
    header("Location: " . $token->url);
    exit();
} catch (Exception $e) {
    echo "Désolé, une erreur est survenue : " . $e->getMessage();
}

```

### `paid/fedapay-callback.php`

```php
<?php
// 1. On coupe l'affichage des alertes de dépréciation pour PHP 8.2+
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);

require_once '../vendor/autoload.php';
require_once '../config/db.php';

require_once '../config/session_config.php';

// Configuration FedaPay
\FedaPay\FedaPay::setApiKey("sk_live_-t3Pw_JoJ8VGBqP8eTZr-ar5");
\FedaPay\FedaPay::setEnvironment('live');

// 1. Récupération de l'ID de la transaction envoyé par FedaPay dans l'URL
$transaction_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$transaction_id) {
    die("ID de transaction manquant.");
}

try {
    // 2. On demande à FedaPay le statut réel de cette transaction
    $transaction = \FedaPay\Transaction::retrieve($transaction_id);
    $status = $transaction->status; // 'approved', 'declined', ou 'pending'

    if ($status === 'approved') {
        // A. On récupère les détails du paiement enregistré
        $stmtPayment = $pdo->prepare("SELECT * FROM wari_payments WHERE reference_fedapay = ?");
        $stmtPayment->execute([$transaction_id]);
        $payment = $stmtPayment->fetch();

        if ($payment) {
            // B. On met à jour notre table wari_payments
            $stmtUpdate = $pdo->prepare("UPDATE wari_payments SET statut = 'approved' WHERE reference_fedapay = ?");
            $stmtUpdate->execute([$transaction_id]);

            // C. Traitement différencié
            if ($payment['type_paiement'] === 'recharge') {
                $duree = intval($payment['duree_jours']);
                $commande_id = $payment['commande_id'];

                // Prolonge à partir de date_expiration si elle est dans le futur, sinon à partir de NOW()
                $stmtLic = $pdo->prepare("
                    UPDATE wari_licences 
                    SET date_expiration = DATE_ADD(GREATEST(COALESCE(date_expiration, NOW()), NOW()), INTERVAL ? DAY)
                    WHERE commande_id = ?
                ");
                $stmtLic->execute([$duree, $commande_id]);

                // Récupérer la nouvelle date d'expiration
                $stmtNewExp = $pdo->prepare("SELECT date_expiration FROM wari_licences WHERE commande_id = ?");
                $stmtNewExp->execute([$commande_id]);
                $newExpiration = $stmtNewExp->fetchColumn();

                // Envoi de la facture par e-mail
                try {
                    require_once __DIR__ . '/../classes/Mailer.php';
                    $mailer = new Mailer();

                    $ref = strtoupper(substr($transaction_id, 0, 8));
                    $date = date('d/m/Y à H:i');
                    $email = $payment['email_client'];
                    $price_formatted = number_format($payment['montant'], 0, '', ' ');
                    $duration_label = ($duree === 365) ? "365 jours (Annuel)" : "30 jours (Mensuel)";
                    $desc = "Recharge d'accès Wari Finance Pro";
                    
                    $exp_formatted = date('d/m/Y à H:i', strtotime($newExpiration));
                    
                    $extension_block = "
                        <div style='background-color: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 12px; padding: 15px; text-align: center; margin-bottom: 30px;'>
                            <div style='color: #10b981; font-weight: bold; font-size: 15px; margin-bottom: 5px;'>Accès prolongé avec succès !</div>
                            <div style='font-size: 13px; color: #eef0f6;'>Votre abonnement est désormais valide jusqu'au <strong>$exp_formatted</strong>.</div>
                        </div>
                    ";

                    $body = "
                    <div style='background-color: #07090e; color: #eef0f6; font-family: sans-serif; padding: 30px; border-radius: 16px; max-width: 600px; margin: 0 auto; border: 1px solid rgba(255, 255, 255, 0.05);'>
                        <div style='text-align: center; margin-bottom: 30px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 20px;'>
                            <h1 style='color: #e8a923; font-size: 26px; margin: 0; font-family: sans-serif;'>WARI <span style='font-size: 14px; color: #fff; font-weight: normal; letter-spacing: 2px;'>FINANCE</span></h1>
                            <p style='color: #6b7491; font-size: 11px; text-transform: uppercase; letter-spacing: 2px; margin-top: 5px; margin-bottom: 0;'>Discipline & Progrès</p>
                        </div>
                        
                        <div style='margin-bottom: 30px;'>
                            <h2 style='font-size: 18px; color: #fff; margin-top: 0; margin-bottom: 15px;'>Facture & Reçu de paiement</h2>
                            <table style='width: 100%; border-collapse: collapse; font-size: 13px; color: #6b7491;'>
                                <tr>
                                    <td style='padding: 4px 0;'>Référence facture :</td>
                                    <td style='padding: 4px 0; text-align: right; color: #fff; font-weight: bold;'>WARI-INV-$ref</td>
                                </tr>
                                <tr>
                                    <td style='padding: 4px 0;'>Date de paiement :</td>
                                    <td style='padding: 4px 0; text-align: right; color: #fff;'>$date</td>
                                </tr>
                                <tr>
                                    <td style='padding: 4px 0;'>Compte client :</td>
                                    <td style='padding: 4px 0; text-align: right; color: #e8a923; font-weight: bold;'>$email</td>
                                </tr>
                                <tr>
                                    <td style='padding: 4px 0;'>Mode de règlement :</td>
                                    <td style='padding: 4px 0; text-align: right; color: #fff;'>FedaPay (Mobile Money / Cartes)</td>
                                </tr>
                            </table>
                        </div>
                        
                        <div style='background-color: #0c0f17; border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 12px; padding: 20px; margin-bottom: 30px;'>
                            <h3 style='font-size: 13px; text-transform: uppercase; color: #e8a923; margin-top: 0; margin-bottom: 15px; letter-spacing: 1px;'>Détails de l'abonnement</h3>
                            <table style='width: 100%; border-collapse: collapse; font-size: 14px;'>
                                <thead>
                                    <tr style='border-bottom: 1px solid rgba(255,255,255,0.08); color: #6b7491; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;'>
                                        <th style='text-align: left; padding-bottom: 10px;'>Désignation</th>
                                        <th style='text-align: right; padding-bottom: 10px;'>Durée</th>
                                        <th style='text-align: right; padding-bottom: 10px;'>Prix</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style='padding: 15px 0 10px; color: #fff;'>$desc</td>
                                        <td style='padding: 15px 0 10px; text-align: right; color: #fff;'>$duration_label</td>
                                        <td style='padding: 15px 0 10px; text-align: right; color: #e8a923; font-weight: bold;'>$price_formatted F CFA</td>
                                    </tr>
                                </tbody>
                            </table>
                            
                            <div style='border-top: 1px solid rgba(255,255,255,0.08); margin-top: 15px; padding-top: 15px; text-align: right;'>
                                <span style='font-size: 12px; color: #6b7491; margin-right: 10px;'>Total payé :</span>
                                <span style='font-size: 20px; font-weight: bold; color: #e8a923;'>$price_formatted F CFA</span>
                            </div>
                        </div>
                        
                        $extension_block

                        <div style='text-align: center; margin-bottom: 30px;'>
                            <a href='https://wari.digiroys.com/paid/invoice.php?id=$transaction_id' style='display: inline-block; background-color: #e8a923; color: #07090e; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: bold; font-family: sans-serif; font-size: 14px;'>
                                Télécharger ma facture en PDF
                            </a>
                        </div>
                        
                        <div style='text-align: center; margin-top: 30px; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 20px; font-size: 11px; color: #6b7491; line-height: 1.5;'>
                            <p>Merci pour votre confiance. Cet e-mail tient lieu de reçu officiel de paiement.</p>
                            <p>© WARI Finance by Digiroys — <a href='mailto:wari-finance@digiroys.com' style='color: #e8a923; text-decoration: none;'>Besoin d'aide ?</a></p>
                            <p style='margin-top: 10px; color: #525b75; font-size: 10px;'>
                                Digiroys — RC: RB/COT/19 A 50622 — IFU: 0 2019 1088 5778<br>
                                Contact : <a href='mailto:wari-finance@digiroys.com' style='color: #e8a923; text-decoration: none;'>wari-finance@digiroys.com</a>
                            </p>
                        </div>
                    </div>
                    ";

                    $mailer->send($email, "Facture WARI Finance - Reçu de paiement [Ref: WARI-INV-$ref]", $body, true);
                } catch (Exception $mailEx) {
                    // Silencieux
                }

                // On stocke le succès de la recharge en session
                $_SESSION['recharge_success'] = true;

                // Redirection directe vers le tableau de bord
                header("Location: ../index.php");
                exit();
            } else {
                // Achat d'une nouvelle licence
                $_SESSION['pending_activation_email'] = $payment['email_client'];
                $_SESSION['payment_ref'] = $transaction_id;
                $_SESSION['pending_duree_jours'] = $payment['duree_jours']; // Pour activation-success.php
                $_SESSION['pending_montant'] = $payment['montant'];

                // Direction : La génération de la licence avec l'identifiant en paramètre
                header("Location: activation-success.php?id=" . urlencode($transaction_id));
                exit();
            }
        } else {
            die("Paiement non trouvé dans notre base de données.");
        }
    } else {
        // Si le paiement a échoué ou est annulé
        header("Location: index.php?error=payment_failed");
        exit();
    }
} catch (Exception $e) {
    echo "Erreur lors de la vérification : " . $e->getMessage();
}

```

### `paid/index.php`

```php
<?php
require_once __DIR__ . '/../wari_monitoring.php';  // TOUJOURS EN PREMIER
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/db.php';

$userId = $_SESSION['user_id'] ?? null;
$userEmail = $_SESSION['user_email'] ?? null;
$isRecharge = false;
$commandeId = null;
$currentExpiration = null;
$isExpired = false;

if ($userId) {
    // Récupérer la licence de l'utilisateur connecté
    $stmt = $pdo->prepare("
        SELECT u.commande_id, l.date_expiration, l.statut 
        FROM wari_users u
        LEFT JOIN wari_licences l ON l.commande_id = u.commande_id
        WHERE u.id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $licInfo = $stmt->fetch();

    if ($licInfo) {
        $isRecharge = true;
        $commandeId = $licInfo['commande_id'];
        $currentExpiration = $licInfo['date_expiration'];
        if ($currentExpiration) {
            $isExpired = (strtotime($currentExpiration) < time());
        } else {
            $isExpired = true; // Jamais configuré = expiré par défaut
        }
    }
}

$price = 590; // Prix de départ (Mensuel)
$product_name = $isRecharge ? "WARI | Recharge d'Accès" : "WARI | Licence Pro";
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $product_name ?></title>

    <!-- Balises SEO -->
    <meta name="description" content="Activez ou rechargez votre Licence Pro WARI. Gérez votre budget, épargnez et maîtrisez vos finances avec WARI.">
    <meta name="keywords" content="Wari, gestion budget, épargne, finance personnelle, Afrique, licence pro, abonnement">
    <meta name="author" content="Wari">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://wari.digiroys.com/paid/">
    <meta property="og:title" content="WARI Finance — Abonnement & Recharge">
    <meta property="og:description" content="Prenez le contrôle de votre argent. Rechargez votre temps d'accès MTN MoMo, Orange Money, Wave.">
    <meta property="og:image" content="https://wari.digiroys.com/assets/wari_og_1.png">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://wari.digiroys.com/paid/">
    <meta property="twitter:title" content="WARI Finance — Abonnement & Recharge">
    <meta property="twitter:description" content="Gérez vos finances comme un champion. Recharge de temps d'accès ultra simple.">
    <meta property="twitter:image" content="https://wari.digiroys.com/assets/wari_og_1.png">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/warifinance3d.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Quicksand:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --gold: #E8A923;
            --gold-lt: #F5C347;
            --gold-dk: #B8841A;
            --bg: #07090E;
            --s1: #0C0F17;
            --s2: #131824;
            --text: #EEF0F6;
            --muted: #6B7491;
            --border: rgba(255, 255, 255, 0.05);
            --plan-bg: rgba(255, 255, 255, 0.02);
            --plan-border: rgba(255, 255, 255, 0.08);
            --input-bg: rgba(255, 255, 255, 0.03);
            --input-border: rgba(255, 255, 255, 0.1);
            --radius: 24px;
        }

        .light-mode {
            --bg: #F8FAFC;
            --s1: #FFFFFF;
            --s2: #F1F5F9;
            --text: #0F172A;
            --muted: #64748B;
            --border: rgba(15, 23, 42, 0.06);
            --plan-bg: rgba(15, 23, 42, 0.02);
            --plan-border: rgba(15, 23, 42, 0.06);
            --input-bg: rgba(15, 23, 42, 0.02);
            --input-border: rgba(15, 23, 42, 0.08);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Quicksand', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            overflow-x: hidden;
            transition: background 0.3s ease, color 0.3s ease;
        }

        .blob {
            position: fixed;
            width: 800px;
            height: 800px;
            background: radial-gradient(circle, rgba(232, 169, 35, .06) 0%, transparent 70%);
            top: -200px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 0;
            pointer-events: none;
        }

        .light-mode .blob {
            background: radial-gradient(circle, rgba(232, 169, 35, .03) 0%, transparent 70%);
        }

        .wrap { position: relative; z-index: 1; max-width: 1000px; width: 100%; }

        .checkout-container {
            display: grid;
            grid-template-columns: 1fr 440px;
            gap: 4rem;
            align-items: center;
        }

        @media (max-width: 900px) {
            .checkout-container { grid-template-columns: 1fr; gap: 2.5rem; justify-content: center; }
            .feature-list { display: inline-block; text-align: left; }
            .product-info { order: 2; }
            .payment-box { order: 1; }
        }

        .mobile-advantages-title { display: none; }
        @media (max-width: 600px) {
            body { padding: 1rem 0.5rem; }
            .checkout-container { grid-template-columns: 1fr; gap: 1.5rem; max-width: 460px; margin: 0 auto; }
            .product-info { text-align: left; padding: 1rem; }
            .product-info .badge, .product-info .product-title { display: none; }
            .mobile-advantages-title { display: block; margin-top: 1rem; }
            .payment-box { padding: 1.8rem 1.2rem; border-radius: 20px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); }
            .plans-container { grid-template-columns: 1fr 1fr; gap: 12px; }
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            border: 1px solid rgba(232, 169, 35, .3);
            background: rgba(232, 169, 35, .07);
            padding: .4rem 1rem;
            border-radius: 50px;
            font-size: .75rem;
            font-weight: 600;
            color: var(--gold-lt);
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .dot { width: 6px; height: 6px; background: var(--gold); border-radius: 50%; box-shadow: 0 0 8px var(--gold); }

        .product-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: clamp(2.2rem, 5vw, 3.2rem);
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
        }

        .product-title span {
            background: linear-gradient(to right, var(--text), var(--gold));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .feature-list { list-style: none; margin: 2rem 0; }
        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
            font-size: 1.05rem;
            color: var(--muted);
        }
        .feature-item svg { color: var(--gold); flex-shrink: 0; }
        .feature-item span strong { color: var(--text); }

        .payment-box {
            background: var(--s1);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 1rem;
            position: relative;
            overflow: hidden;
            transition: background 0.3s, border 0.3s;
        }

        .order-summary {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .price-label { font-size: 0.85rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.5rem; }
        .price-value { font-family: 'Plus Jakarta Sans'; font-size: 3.5rem; font-weight: 800; color: var(--gold-lt); line-height: 1; }
        .price-currency { font-size: 1.2rem; font-weight: 400; margin-left: 5px; }

        .discount-pill {
            display: inline-block;
            background: rgba(232, 169, 35, 0.1);
            color: var(--gold-lt);
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            margin-top: 10px;
        }

        /* Status card */
        .status-alert-box {
            display: flex;
            gap: 12px;
            padding: 16px;
            border-radius: 16px;
            background: var(--plan-bg);
            border: 1px solid var(--border);
            text-align: left;
            margin-bottom: 1.5rem;
        }
        .status-alert-box.expired {
            border-color: rgba(239, 68, 68, 0.2);
            background: rgba(239, 68, 68, 0.05);
        }
        .status-alert-box.active {
            border-color: rgba(16, 185, 129, 0.2);
            background: rgba(16, 185, 129, 0.05);
        }
        .status-alert-icon { font-size: 1.5rem; flex-shrink: 0; display: flex; align-items: center; }
        .status-alert-content { display: flex; flex-direction: column; }
        .status-alert-title { font-weight: 700; font-size: 0.95rem; margin-bottom: 2px; }
        .status-alert-box.expired .status-alert-title { color: #ef4444; }
        .status-alert-box.active .status-alert-title { color: #10b981; }
        .status-alert-desc { font-size: 0.8rem; color: var(--muted); line-height: 1.4; }
        .status-alert-desc strong { color: var(--text); }

        /* Plans selector CSS */
        .plans-container { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin: 1.5rem 0; }
        .plan-card {
            background: var(--plan-bg);
            border: 1.5px solid var(--plan-border);
            border-radius: 16px;
            padding: 1.8rem 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            text-align: left;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 195px;
        }
        .plan-card:hover {
            border-color: rgba(232, 169, 35, 0.4);
            background: var(--s2);
            transform: translateY(-2px);
        }
        .plan-card.active {
            border-color: var(--gold);
            background: rgba(232, 169, 35, 0.06);
            box-shadow: 0 0 15px rgba(232, 169, 35, 0.1);
        }
        .plan-badge {
            display: inline-block;
            align-self: flex-start;
            font-size: 0.65rem;
            text-transform: uppercase;
            font-weight: 700;
            background: var(--s2);
            padding: 2px 8px;
            border-radius: 20px;
            color: var(--text);
            border: 1px solid var(--border);
            margin-bottom: 8px;
        }
        .plan-badge.gold-badge {
            background: rgba(232, 169, 35, 0.2);
            color: var(--gold-lt);
            border: 1px solid rgba(232, 169, 35, 0.3);
        }
        .plan-name {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--muted);
            margin-bottom: 5px;
        }
        .plan-card.active .plan-name { color: var(--text); }
        .plan-price {
            font-family: 'Plus Jakarta Sans';
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 5px;
            display: flex;
            align-items: baseline;
        }
        .plan-card.active .plan-price { color: var(--gold); }
        .plan-curr { font-size: 0.8rem; font-weight: 500; margin-left: 4px; }
        .plan-desc { font-size: 0.75rem; color: var(--muted); line-height: 1.3; }

        .pay-input {
            width: 100%;
            padding: 16px;
            background: var(--input-bg);
            border: 1.5px solid var(--input-border);
            border-radius: 14px;
            color: var(--text);
            font-family: 'Quicksand', sans-serif;
            font-size: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            outline: none;
        }

        .pay-input:focus { border-color: var(--gold); background: var(--s1); box-shadow: 0 0 20px rgba(232, 169, 35, 0.1); }

        .payment-methods-label {
            font-size: 0.75rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 12px;
            display: block;
            font-weight: 600;
            text-align: left;
        }

        .pay-btn-modern {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 14px;
            font-size: 1rem;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            transition: all 0.3s ease;
            margin-bottom: 10px;
            text-decoration: none;
            font-family: 'Quicksand', sans-serif;
        }

        .btn-fedapay {
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dk) 100%);
            color: #07090E;
        }

        .pay-btn-modern:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(232, 169, 35, 0.2); filter: brightness(1.1); }

        .trust-strip {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 1.5rem;
            opacity: 0.5;
            font-size: 0.75rem;
        }

        .trust-item-small { display: flex; align-items: center; gap: 5px; }

        .footer { text-align: center; margin-top: 3rem; color: var(--muted); font-size: 0.8rem; }
    </style>
</head>

<body>
    <!-- Script d'initialisation immédiate du thème -->
    <script>
        const savedTheme = localStorage.getItem('wari_theme') || 'dark';
        if (savedTheme === 'light') {
            document.documentElement.classList.add('light-mode');
            document.body.classList.add('light-mode');
        }
    </script>

    <div class="blob"></div>

    <div class="wrap">
        <div class="checkout-container">
            
            <!-- GAUCHE : RECAPITULATIF & VALEUR (Masqué sur mobile) -->
            <div class="product-info">
                <?php if ($isRecharge): ?>
                    <span class="badge"><span class="dot"></span> Renouvellement</span>
                    <h1 class="product-title">Prolongez votre<br><span>Accès Wari</span></h1>
                <?php else: ?>
                    <span class="badge"><span class="dot"></span> Nouveau Compte</span>
                    <h1 class="product-title">Activez votre<br><span>Licence Wari</span></h1>
                <?php endif; ?>
                
                <h3 class="mobile-advantages-title" style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1.2rem; color: var(--gold-lt);">Vos avantages Premium inclus :</h3>
                <ul class="feature-list">
                    <li class="feature-item">
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"></path></svg>
                        <span>Accès complet à l'application <strong>Wari-Finance</strong></span>
                    </li>
                    <li class="feature-item">
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"></path></svg>
                        <span>Gestion enveloppes, dettes et coffre-fort</span>
                    </li>
                    <li class="feature-item">
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"></path></svg>
                        <span>Discipline budgétaire augmentée avec le <strong>Coach IA</strong></span>
                    </li>
                    <li class="feature-item">
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"></path></svg>
                        <span><strong>Défis d'Épargne Interactifs</strong> (Ludiques et motivants)</span>
                    </li>
                    <li class="feature-item">
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"></path></svg>
                        <span><strong>Multi-portefeuilles</strong> (Séparation distincte Perso / Pro)</span>
                    </li>
                    <li class="feature-item">
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"></path></svg>
                        <span><strong>Bilans PDF Professionnels</strong> (Rapports complets & Analyses)</span>
                    </li>
                    <li class="feature-item">
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"></path></svg>
                        <span><strong>Alertes Prédictives Proactives</strong> (Notifications du Coach IA)</span>
                    </li>
                    <li class="feature-item">
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"></path></svg>
                        <span>Espace <strong>Wari Academy Premium</strong> (Cours & outils Excel)</span>
                    </li>
                </ul>

                <div style="margin-top: 2rem; color: var(--muted); font-size: 0.9rem; line-height: 1.6;">
                    <p>Paiement 100% sécurisé.</p>
                    <p>Activation ou prolongation instantanée de votre temps de licence.</p>
                </div>
            </div>

            <!-- DROITE : PAIEMENT (Interface principale mobile-snug) -->
            <div class="payment-box">
                
                <?php if ($isRecharge): ?>
                    <!-- Statut de l'abonnement actuel -->
                    <div class="status-alert-box <?= $isExpired ? 'expired' : 'active' ?>">
                        <div class="status-alert-icon">
                            <?php if ($isExpired): ?>
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                            <?php else: ?>
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            <?php endif; ?>
                        </div>
                        <div class="status-alert-content">
                            <div class="status-alert-title"><?= $isExpired ? 'Abonnement Expiré' : 'Abonnement Actif' ?></div>
                            <div class="status-alert-desc">
                                <?php if ($currentExpiration): ?>
                                    <?= $isExpired ? 'Votre accès a expiré le' : 'Votre accès est valide jusqu\'au' ?> <strong><?= date('d/m/Y à H:i', strtotime($currentExpiration)) ?></strong>.
                                <?php else: ?>
                                    Aucun abonnement actif enregistré sur votre licence.
                                <?php endif; ?>
                                <br>Rechargez ci-dessous pour continuer.
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="order-summary">
                    <div class="price-label" id="display-price-label">Total Mensuel</div>
                    <div class="price-value" id="display-price-value">
                        <?= number_format($price, 0, '', '&nbsp;') ?><span class="price-currency">F CFA</span>
                    </div>
                    <div class="discount-pill" id="discount-pill-id">Accès pendant 30 jours complets</div>
                </div>

                <form action="fedapay-checkout.php" method="POST">
                    <input type="hidden" name="plan" id="selected-plan-input" value="mensuel">

                    <?php if ($isRecharge): ?>
                        <div class="user-locked-info" style="margin-bottom: 20px; text-align: left;">
                            <label style="display:block; margin-bottom: 6px; font-size: 0.85rem; color: var(--muted); font-weight: 500;">Compte à recharger</label>
                            <div style="background: var(--plan-bg); border: 1px solid var(--border); padding: 12px 16px; border-radius: 12px; font-size: 0.95rem; font-weight: 600; color: var(--gold-lt);">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                <?= htmlspecialchars($userEmail) ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <label style="display:block; margin-bottom: 8px; font-size: 0.85rem; color: var(--muted); font-weight: 500;">Email de livraison de la licence</label>
                        <input type="email" name="customer_email" class="pay-input" placeholder="votre@email.com" required>
                    <?php endif; ?>

                    <span class="payment-methods-label">Sélectionner votre formule</span>
                    
                    <!-- Sélecteur de forfait -->
                    <div class="plans-container">
                        <div class="plan-card active" id="plan-mensuel" onclick="selectPlan('mensuel', 590)">
                            <div class="plan-badge">Standard</div>
                            <div class="plan-name">Mensuel</div>
                            <div class="plan-price">590 <span class="plan-curr">F CFA</span></div>
                            <div class="plan-desc">Accès 30 jours</div>
                        </div>
                        <div class="plan-card" id="plan-annuel" onclick="selectPlan('annuel', 5000)">
                            <div class="plan-badge gold-badge">Économique</div>
                            <div class="plan-name">Annuel</div>
                            <div class="plan-price">5 000 <span class="plan-curr">F CFA</span></div>
                            <div class="plan-desc">Accès 365 jours<br><strong style="color:var(--gold-lt);">Économisez 30%</strong></div>
                        </div>
                    </div>

                    <span class="payment-methods-label">Moyen de paiement sécurisé</span>

                    <button type="submit" class="pay-btn-modern btn-fedapay">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                        <span>Payer avec FedaPay</span>
                    </button>
                </form>

                <div class="trust-strip">
                    <div class="trust-item-small">MTN, Orange, Wave, Moov</div>
                    <div class="trust-item-small">Instantané</div>
                    <div class="trust-item-small">Premium</div>
                </div>
            </div>

        </div>

        <div class="footer">
            © <?= date('Y') ?> WARI Finance by Digiroys — <a href="mailto:wari.finance.inter@gmail.com" style="color: var(--gold); text-decoration: none;">Besoin d'aide ?</a>
        </div>
    </div>

    <script>
        function selectPlan(plan, price) {
            document.getElementById('selected-plan-input').value = plan;
            
            // Basculer la classe active
            document.getElementById('plan-mensuel').classList.toggle('active', plan === 'mensuel');
            document.getElementById('plan-annuel').classList.toggle('active', plan === 'annuel');
            
            // Mettre à jour le prix affiché
            const formattedPrice = new Intl.NumberFormat('fr-FR').format(price);
            document.getElementById('display-price-value').innerHTML = `${formattedPrice}<span class="price-currency">F CFA</span>`;
            
            // Mettre à jour le libellé
            if (plan === 'annuel') {
                document.getElementById('display-price-label').innerText = "Total Annuel (Économique)";
                document.getElementById('discount-pill-id').innerText = "Accès pendant 365 jours complets";
            } else {
                document.getElementById('display-price-label').innerText = "Total Mensuel";
                document.getElementById('discount-pill-id').innerText = "Accès pendant 30 jours complets";
            }
        }
    </script>
</body>

</html>
```

### `coach/index.php`

```php
<?php
require_once __DIR__ . '/../wari_monitoring.php';  // ← TOUJOURS EN PREMIER
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session_check.php';

$userId = $_SESSION['user_id'];

// 1. Récupérer le budget de l'utilisateur
$stmt = $pdo->prepare("SELECT budget_data, budget_data_pro FROM wari_users WHERE id = ?");
$stmt->execute([$userId]);
$userData = $stmt->fetch();
$budgetRaw = (!empty($userData['budget_data'])) ? $userData['budget_data'] : 'null';
$budgetRawPro = (!empty($userData['budget_data_pro'])) ? $userData['budget_data_pro'] : 'null';

$budgetData = json_decode($budgetRaw, true);
$categories = isset($budgetData['categories']) ? $budgetData['categories'] : [];
$projectCapital = isset($budgetData['projectCapital']) ? (float)$budgetData['projectCapital'] : 0.0;
$currency = isset($budgetData['currency']) ? $budgetData['currency'] : 'F';

$budgetDataPro = json_decode($budgetRawPro, true);
$categoriesPro = isset($budgetDataPro['categories']) ? $budgetDataPro['categories'] : [];
$projectCapitalPro = isset($budgetDataPro['projectCapital']) ? (float)$budgetDataPro['projectCapital'] : 0.0;

// 2. Dépenses du mois actuel pour les deux portefeuilles
$stmtExp = $pdo->prepare("
    SELECT category_id, wallet_type, SUM(amount) as total 
    FROM wari_expenses 
    WHERE user_id = ? 
    AND MONTH(date_expense) = MONTH(CURRENT_DATE()) 
    AND YEAR(date_expense) = YEAR(CURRENT_DATE())
    GROUP BY category_id, wallet_type
");
$stmtExp->execute([$userId]);
$expensesRaw = $stmtExp->fetchAll(PDO::FETCH_ASSOC);

$expensesPerso = [];
$expensesPro = [];
foreach ($expensesRaw as $exp) {
    if ($exp['wallet_type'] === 'pro') {
        $expensesPro[(int)$exp['category_id']] = (float)$exp['total'];
    } else {
        $expensesPerso[(int)$exp['category_id']] = (float)$exp['total'];
    }
}

// 3. Dettes actives
$stmtDebts = $pdo->prepare("
    SELECT id, person_name, amount, type 
    FROM wari_debts 
    WHERE user_id = ? AND status = 'pending' 
    ORDER BY created_at DESC
");
$stmtDebts->execute([$userId]);
$debts = $stmtDebts->fetchAll(PDO::FETCH_ASSOC);

// 4. Calcul de l'enveloppe Cash/Disponible (Perso)
$cash = 0.0;
foreach ($categories as $cat) {
    $catId = (int)$cat['id'];
    $catName = (string)$cat['name'];
    $spent = isset($expensesPerso[$catId]) ? (float)$expensesPerso[$catId] : 0.0;
    
    $isProjet = (strpos(strtolower($catName), 'projet') !== false);
    $isEpargne = (strpos(strtolower($catName), 'épargne') !== false) || ($catId === 1);
    
    if (!$isEpargne && !$isProjet) {
        $balance = isset($cat['balance']) ? (float)$cat['balance'] : 0.0;
        $soldeReel = max(0.0, $balance - $spent);
        $cash += $soldeReel;
    }
}

// 4b. Calcul de l'enveloppe Cash/Disponible (Pro)
$cashPro = 0.0;
foreach ($categoriesPro as $cat) {
    $catId = (int)$cat['id'];
    $catName = (string)$cat['name'];
    $spent = isset($expensesPro[$catId]) ? (float)$expensesPro[$catId] : 0.0;
    
    $isProjet = (strpos(strtolower($catName), 'projet') !== false);
    $isEpargne = (strpos(strtolower($catName), 'épargne') !== false) || ($catId === 1);
    
    if (!$isEpargne && !$isProjet) {
        $balance = isset($cat['balance']) ? (float)$cat['balance'] : 0.0;
        $soldeReel = max(0.0, $balance - $spent);
        $cashPro += $soldeReel;
    }
}

// 5. Calcul des variables pour le contexte AI
$daysInMonth = (int)date('t');
$day = (int)date('j');
$daysLeft = $daysInMonth - $day + 1;

$totalDettes = 0;
foreach ($debts as $d) {
    $totalDettes += (int)$d['amount'];
}

// Synthèse Portefeuille Personnel
$catSummaryPersoLines = [];
foreach ($categories as $c) {
    $catId = (int)$c['id'];
    $catName = (string)$c['name'];
    $spent = isset($expensesPerso[$catId]) ? (int)$expensesPerso[$catId] : 0;
    $isProjet = (strpos(strtolower($catName), 'projet') !== false);
    $limit = $isProjet ? $projectCapital : (isset($c['balance']) ? $c['balance'] : 0);
    $targetPercent = isset($c['percent']) ? (int)$c['percent'] : 0;
    $catSummaryPersoLines[] = "- {$catName}: {$spent} {$currency} dépensés sur {$limit} {$currency} prévus (Pourcentage cible : {$targetPercent}%)";
}
$catSummaryPerso = implode("\n", $catSummaryPersoLines);

// Synthèse Portefeuille Professionnel
$catSummaryProLines = [];
foreach ($categoriesPro as $c) {
    $catId = (int)$c['id'];
    $catName = (string)$c['name'];
    $spent = isset($expensesPro[$catId]) ? (int)$expensesPro[$catId] : 0;
    $isProjet = (strpos(strtolower($catName), 'projet') !== false);
    $limit = $isProjet ? $projectCapitalPro : (isset($c['balance']) ? $c['balance'] : 0);
    $targetPercent = isset($c['percent']) ? (int)$c['percent'] : 0;
    $catSummaryProLines[] = "- {$catName}: {$spent} {$currency} dépensés sur {$limit} {$currency} prévus (Pourcentage cible : {$targetPercent}%)";
}
$catSummaryPro = implode("\n", $catSummaryProLines);

// Synthèse Défis d'Épargne
$stmtChallenges = $pdo->prepare("
    SELECT challenge_type, target_amount, current_amount, status 
    FROM wari_savings_challenges 
    WHERE user_id = ? AND status = 'active'
");
$stmtChallenges->execute([$userId]);
$challengesRaw = $stmtChallenges->fetchAll(PDO::FETCH_ASSOC);
$challengesLines = [];
foreach ($challengesRaw as $ch) {
    $challengesLines[] = "- Défi {$ch['challenge_type']} : {$ch['current_amount']} {$currency} épargnés sur un objectif de {$ch['target_amount']} {$currency}";
}
$challengesSummary = empty($challengesLines) ? "Aucun défi d'épargne actif." : implode("\n", $challengesLines);

// Synthèse Dépenses Récentes (25 dernières)
$stmtRecentExp = $pdo->prepare("
    SELECT category_id, amount, description, date_expense, wallet_type 
    FROM wari_expenses 
    WHERE user_id = ? 
    ORDER BY date_expense DESC 
    LIMIT 25
");
$stmtRecentExp->execute([$userId]);
$recentExpensesRaw = $stmtRecentExp->fetchAll(PDO::FETCH_ASSOC);

$allCatNames = [
    1 => 'Épargne',
    2 => 'Train de vie',
    3 => 'Projet',
    4 => 'Imprévu',
    101 => 'Stock & Matériel',
    102 => 'Bénéfice Réinvesti',
    103 => 'Frais de Fonctionnement',
    104 => 'Marketing & Publicité'
];
foreach ($categories as $c) {
    $allCatNames[(int)$c['id']] = (string)$c['name'];
}
foreach ($categoriesPro as $c) {
    $allCatNames[(int)$c['id']] = (string)$c['name'];
}

$recentExpensesLines = [];
foreach ($recentExpensesRaw as $exp) {
    $catName = $allCatNames[(int)$exp['category_id']] ?? "Catégorie {$exp['category_id']}";
    $walletLabel = ($exp['wallet_type'] === 'pro') ? 'Pro' : 'Perso';
    $dateFormatted = date('d/m/Y H:i', strtotime($exp['date_expense']));
    $recentExpensesLines[] = "- [{$walletLabel}] {$dateFormatted} : {$catName} - Dépense de {$exp['amount']} {$currency} (Note: \"{$exp['description']}\")";
}
$recentExpensesSummary = empty($recentExpensesLines) ? "Aucune dépense récente." : implode("\n", $recentExpensesLines);

$debtsSummaryLines = [];
foreach ($debts as $d) {
    $typeLabel = ($d['type'] === 'loan') ? 'Prêt à' : 'Dette envers';
    $amountFormatted = number_format((int)$d['amount'], 0, '.', ' ');
    $debtsSummaryLines[] = "- {$typeLabel} {$d['person_name']} : {$amountFormatted} {$currency}";
}
$debtsSummary = empty($debtsSummaryLines) ? "Aucune dette active." : implode("\n", $debtsSummaryLines);

// 6. Historique des 6 derniers mois pour l'IA (Portefeuille Personnel et Professionnel cumulés)
$historySummaryLines = [];
try {
    $stmtMonths = $pdo->prepare("
        SELECT DISTINCT DATE_FORMAT(dt, '%Y-%m') as month_key, DATE_FORMAT(dt, '%m') as month_num, DATE_FORMAT(dt, '%Y') as year
        FROM (
            SELECT distributed_at as dt FROM wari_distributions WHERE user_id = ? AND distributed_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
            UNION
            SELECT date_expense as dt FROM wari_expenses WHERE user_id = ? AND date_expense >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
        ) as combined
        ORDER BY month_key DESC
    ");
    $stmtMonths->execute([$userId, $userId]);
    $allMonths = $stmtMonths->fetchAll(PDO::FETCH_ASSOC);

    $stmtDistribAgg = $pdo->prepare("
        SELECT DATE_FORMAT(distributed_at, '%Y-%m') as month_key, SUM(amount) as total_distributed
        FROM wari_distributions WHERE user_id = ? GROUP BY month_key
    ");
    $stmtDistribAgg->execute([$userId]);
    $distribs = $stmtDistribAgg->fetchAll(PDO::FETCH_KEY_PAIR);

    $stmtExpAgg = $pdo->prepare("
        SELECT DATE_FORMAT(date_expense, '%Y-%m') as month_key, SUM(amount) as total_spent
        FROM wari_expenses WHERE user_id = ? GROUP BY month_key
    ");
    $stmtExpAgg->execute([$userId]);
    $exps = $stmtExpAgg->fetchAll(PDO::FETCH_KEY_PAIR);

    foreach ($allMonths as $m) {
        $mKey = $m['month_key'];
        $rev = $distribs[$mKey] ?? 0;
        $spent = $exps[$mKey] ?? 0;
        $saved = max(0, $rev - $spent);
        $rate = $rev > 0 ? round(($saved / $rev) * 100, 1) : 0;
        $historySummaryLines[] = "- Mois de {$mKey} : {$rev} {$currency} gagnés, {$spent} {$currency} dépensés (Taux d'épargne : {$rate}%)";
    }
} catch (Exception $e) {
    // Silencieux si échec
}
$historySummary = empty($historySummaryLines) ? "Aucun historique disponible." : implode("\n", $historySummaryLines);

// 7. Récupérer les articles publiés dans Wari Vécu pour le contexte de l'IA
$articlesSummaryLines = [];
try {
    $stmtArticles = $pdo->query("SELECT id, titre, date_publication FROM wari_articles ORDER BY date_publication DESC LIMIT 5");
    $articlesRaw = $stmtArticles->fetchAll(PDO::FETCH_ASSOC);
    foreach ($articlesRaw as $art) {
        $articlesSummaryLines[] = "- Journal Vécu du {$art['date_publication']} : \"{$art['titre']}\" (ID de l'article : {$art['id']})";
    }
} catch (Exception $e) {
    // Silencieux si échec
}
$articlesSummary = empty($articlesSummaryLines) ? "Aucun article publié dans le journal Vécu." : implode("\n", $articlesSummaryLines);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Wari Coach - Ton mentor financier</title>
    <meta name="description" content="Prends le contrôle de ton budget avec le Coach Wari, disponible 24h/24 pour te conseiller.">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <meta id="metaThemeColor" name="theme-color" content="#000000">

    <script>
        // Initialisation immédiate du thème
        const savedTheme = localStorage.getItem('wari_theme') || 'dark';
        if (savedTheme === 'light') {
            document.documentElement.classList.add('light-mode');
            document.addEventListener('DOMContentLoaded', () => {
                document.body.classList.add('light-mode');
                const metaThemeColor = document.getElementById('metaThemeColor');
                if (metaThemeColor) metaThemeColor.setAttribute('content', '#f1f5f9');
            });
        }

        function toggleTheme() {
            const isLight = document.body.classList.toggle('light-mode');
            document.documentElement.classList.toggle('light-mode', isLight);
            localStorage.setItem('wari_theme', isLight ? 'light' : 'dark');
            const metaThemeColor = document.getElementById('metaThemeColor');
            if (metaThemeColor) {
                metaThemeColor.setAttribute('content', isLight ? '#f1f5f9' : '#000000');
            }
            updateThemeButton();
        }

        function updateThemeButton() {
            const isLight = document.documentElement.classList.contains('light-mode');
            const themeIcon = document.getElementById('themeIcon');
            const sunIcon = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>`;
            const moonIcon = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>`;
            if (themeIcon) {
                themeIcon.innerHTML = isLight ? sunIcon : moonIcon;
            }
        }

        document.addEventListener('DOMContentLoaded', updateThemeButton);
    </script>

    <style>
        /* Variables de couleurs personnalisées Wari */
        :root {
            --color-primary: #C9A84C; /* Or Wari */
            --color-primary-rgb: 201, 168, 76;
            --color-bg-app: #000000; /* Noir pur AMOLED */
            --color-bg-card: #0c0d0e; /* Gris/noir ultra sombre */
            --color-bg-card-rgb: 12, 13, 14;
            --color-border: rgba(255, 255, 255, 0.05); /* Bordure ultra fine et sombre */
            --color-text-main: #f8fafc;
            --color-text-muted: #64748b; /* Texte secondaire plus sombre et discret */
            --color-success: #10b981;

            --color-bg-chat: #020203;
            --color-bg-header: #000000;
            --color-bg-btn: #0a0a0a;
            --color-bg-btn-hover: rgba(255, 255, 255, 0.1);
            --color-bg-bubble-coach: #0c0d0e;
            --color-bg-chip: #0a0a0a;
            --color-bg-input: #0a0a0a;
            --color-bg-input-focus: #0d0d0d;
            --color-scrollbar-thumb: rgba(255, 255, 255, 0.1);
        }

        /* Mode Clair */
        body.light-mode {
            --color-bg-app: #f1f5f9;
            --color-bg-card: #ffffff;
            --color-border: rgba(15, 23, 42, 0.08);
            --color-text-main: #0f172a;
            --color-text-muted: #475569;

            --color-bg-chat: #ffffff;
            --color-bg-header: #ffffff;
            --color-bg-btn: #f1f5f9;
            --color-bg-btn-hover: rgba(15, 23, 42, 0.05);
            --color-bg-bubble-coach: #f1f5f9;
            --color-bg-chip: #f1f5f9;
            --color-bg-input: #f8fafc;
            --color-bg-input-focus: #ffffff;
            --color-scrollbar-thumb: rgba(15, 23, 42, 0.1);
        }

        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            position: fixed;
            background-color: var(--color-bg-app);
            font-family: 'Quicksand', sans-serif;
            color: var(--color-text-main);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .chat-app {
            width: 100%;
            max-width: 600px;
            position: fixed;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            height: 100%;
            display: flex;
            flex-direction: column;
            background: var(--color-bg-chat); /* Arrière-plan chat dynamique */
            border-left: 1px solid var(--color-border);
            border-right: 1px solid var(--color-border);
            box-shadow: none; /* Retrait de l'ombre portée */
            overflow: hidden;
        }

        @media (max-width: 600px) {
            .chat-app {
                border-left: none;
                border-right: none;
            }
        }

        /* En-tête */
        .chat-header {
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--color-border);
            background: var(--color-bg-header); /* Fond dynamique */
            z-index: 10;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-btn {
            background: var(--color-bg-btn); /* Fond dynamique */
            border: 1px solid var(--color-border);
            color: var(--color-text-muted);
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
        }

        .back-btn:hover {
            background: var(--color-bg-btn-hover);
            color: var(--color-text-main);
            transform: translateX(-2px);
        }

        .back-btn:active {
            transform: scale(0.95);
        }

        .avatar-container {
            position: relative;
        }

        .avatar {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--color-primary), #d97706);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: none; /* Retrait de l'ombre */
        }

        .avatar svg {
            width: 1.25rem;
            height: 1.25rem;
            color: #020617;
        }

        .status-indicator {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 0.625rem;
            height: 0.625rem;
            background-color: var(--color-success);
            border: 2px solid var(--color-bg-header);
            border-radius: 50%;
            animation: pulse-status 2s infinite;
        }

        @keyframes pulse-status {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { box-shadow: 0 0 0 4px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .header-info h1 {
            font-size: 1.08rem;
            font-weight: 800;
            margin: 0;
            color: var(--color-text-main);
            letter-spacing: -0.01em;
        }

        .header-info p {
            font-size: 0.78rem;
            font-weight: 700;
            margin: 0.1rem 0 0 0;
            color: var(--color-primary);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        /* Messages */
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1.25rem 1rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            scrollbar-width: thin;
            scrollbar-color: var(--color-scrollbar-thumb) transparent;
        }

        .chat-messages::-webkit-scrollbar {
            width: 4px;
        }
        .chat-messages::-webkit-scrollbar-thumb {
            background: var(--color-scrollbar-thumb);
            border-radius: 2px;
        }

        /* Bulles de message */
        .message {
            max-width: 82%;
            padding: 0.8rem 1rem;
            font-size: 0.92rem;
            line-height: 1.5;
            animation: message-in 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes message-in {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message-user {
            background: var(--color-primary);
            color: #000000; /* Noir pur contrasté sur le doré */
            align-self: flex-end;
            border-radius: 1.25rem 1.25rem 0 1.25rem;
            font-weight: 600;
            box-shadow: none; /* Retrait de l'ombre */
        }

        .message-coach {
            background: var(--color-bg-bubble-coach); /* Fond dynamique */
            border: 1px solid var(--color-border);
            color: var(--color-text-main);
            align-self: flex-start;
            border-radius: 1.25rem 1.25rem 1.25rem 0;
            box-shadow: none; /* Retrait de l'ombre */
        }

        .message-coach strong {
            color: var(--color-primary);
            font-weight: 600;
        }

        /* Indicateur d'attente (Thinking) */
        .message-thinking {
            align-self: flex-start;
            background: var(--color-bg-bubble-coach); /* Fond dynamique */
            border: 1px solid var(--color-border);
            padding: 0.8rem 1.2rem;
            border-radius: 1.25rem 1.25rem 1.25rem 0;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            box-shadow: none;
        }

        .dot {
            width: 0.4rem;
            height: 0.4rem;
            background-color: var(--color-text-muted);
            border-radius: 50%;
            animation: bounce-dot 1.4s infinite ease-in-out both;
        }

        .dot:nth-child(1) { animation-delay: -0.32s; }
        .dot:nth-child(2) { animation-delay: -0.16s; }

        @keyframes bounce-dot {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1.0); }
        }

        /* Transitions pour la compression clavier */
        .chat-header, .avatar, .back-btn, .header-info h1, .header-info p, .suggestions-row {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Suggestions */
        .suggestions-row {
            display: flex;
            gap: 0.5rem;
            padding: 0.6rem 1rem;
            overflow-x: auto;
            white-space: nowrap;
            background: var(--color-bg-chat); /* Fond dynamique */
            border-top: 1px solid var(--color-border);
            scrollbar-width: none; /* Firefox */
            max-height: 50px;
        }

        /* Styles de compression quand le clavier virtuel est ouvert */
        .keyboard-active .suggestions-row {
            max-height: 0 !important;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            margin: 0 !important;
            border-top: none !important;
            opacity: 0;
            pointer-events: none;
        }

        .keyboard-active .chat-header {
            padding: 0.5rem 1rem;
        }

        .keyboard-active .avatar {
            width: 2rem;
            height: 2rem;
        }

        .keyboard-active .back-btn {
            width: 2rem;
            height: 2rem;
        }

        .keyboard-active .header-info h1 {
            font-size: 0.98rem;
        }

        .keyboard-active .header-info p {
            font-size: 0.72rem;
        }

        .suggestions-row::-webkit-scrollbar {
            display: none; /* Safari et Chrome */
        }

        .suggestion-chip {
            background: var(--color-bg-chip); /* Fond dynamique */
            border: 1px solid var(--color-border);
            color: var(--color-text-muted);
            padding: 0.4rem 0.8rem;
            border-radius: 100px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .suggestion-chip:hover {
            border-color: rgba(var(--color-primary-rgb), 0.4);
            color: var(--color-text-main);
            background: rgba(var(--color-primary-rgb), 0.05);
        }

        .suggestion-chip:active {
            transform: scale(0.95);
        }

        /* Zone de saisie */
        .input-bar {
            padding: 0.75rem 1rem;
            background: var(--color-bg-header); /* Fond dynamique */
            border-top: 1px solid var(--color-border);
            display: flex;
            gap: 0.75rem;
            align-items: flex-end;
        }

        .chat-input {
            flex: 1;
            background: var(--color-bg-input); /* Fond dynamique */
            border: 1px solid var(--color-border);
            border-radius: 1rem;
            padding: 0.55rem 1rem;
            color: var(--color-text-main);
            font-size: 0.92rem;
            font-family: inherit;
            outline: none;
            resize: none;
            overflow-y: auto;
            max-height: 120px;
            min-height: 38px;
            height: 38px;
            line-height: 1.4;
            transition: border-color 0.2s ease, background-color 0.2s ease;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .chat-input::-webkit-scrollbar {
            display: none;
        }

        .chat-input:focus {
            border-color: rgba(var(--color-primary-rgb), 0.5);
            background: var(--color-bg-input-focus);
        }

        .chat-input::placeholder {
            color: var(--color-text-muted);
            opacity: 0.5;
        }

        .send-btn {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 1rem;
            background: linear-gradient(135deg, var(--color-primary) 0%, #d97706 100%);
            border: none;
            color: #020617;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: none; /* Retrait de l'ombre */
        }

        .send-btn:hover {
            transform: scale(1.05);
            box-shadow: none; /* Retrait de l'ombre */
        }

        .send-btn:active {
            transform: scale(0.95);
        }

        .send-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .send-btn svg {
            width: 1.25rem;
            height: 1.25rem;
        }

        /* Désactiver l'icône de suppression par défaut des inputs type search */
        input[type="search"]::-webkit-search-decoration,
        input[type="search"]::-webkit-search-cancel-button,
        input[type="search"]::-webkit-search-results-button,
        input[type="search"]::-webkit-search-results-decoration {
            -webkit-appearance: none;
            display: none;
        }

        .clear-btn {
            background: transparent;
            border: none;
            color: var(--color-text-muted);
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .clear-btn:hover {
            background: var(--color-bg-btn-hover);
            color: #ef4444; /* Rouge au survol */
        }

        .clear-btn:active {
            transform: scale(0.95);
        }

        .theme-toggle-btn {
            background: transparent;
            border: none;
            color: var(--color-text-muted);
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .theme-toggle-btn:hover {
            background: var(--color-bg-btn-hover);
            color: var(--color-primary);
        }

        .theme-toggle-btn:active {
            transform: scale(0.95);
        }
    </style>
</head>
<body>
    <div class="chat-app" id="chatApp">
        <!-- Header -->
        <header class="chat-header">
            <div class="header-left">
                <a href="../" class="back-btn" title="Retour au tableau de bord">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                </a>
                <div class="avatar-container">
                    <div class="avatar">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 9h.01M16 9h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <span class="status-indicator"></span>
                </div>
                <div class="header-info">
                    <h1>Coach Wari</h1>
                    <p>Conseiller Financier</p>
                </div>
            </div>
            <div class="header-right">
                <button class="theme-toggle-btn" onclick="toggleTheme()" title="Changer le thème">
                    <span id="themeIcon" style="display: flex; align-items: center; justify-content: center;"></span>
                </button>
                <button class="clear-btn" onclick="clearHistory()" title="Effacer l'historique de discussion">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                </button>
            </div>
        </header>

        <!-- Zone des Messages -->
        <main class="chat-messages" id="chatMessages">
            <!-- Rendu par JS -->
        </main>

        <!-- Puces de Suggestions -->
        <section class="suggestions-row" id="suggestionsRow">
            <button class="suggestion-chip" onclick="sendSuggestion('Comment puis-je optimiser mon budget ce mois-ci ?')">💡 Optimiser mon budget</button>
            <button class="suggestion-chip" onclick="sendSuggestion('Fais-moi une analyse complète de ma situation financière actuelle.')">📊 Analyse complète</button>
            <button class="suggestion-chip" onclick="sendSuggestion('Quel est ton meilleur conseil de discipline financière aujourd\'hui ?')">🥋 Conseil discipline</button>
            <button class="suggestion-chip" onclick="sendSuggestion('Comment gérer mes dettes et les rembourser plus vite ?')">💸 Gérer mes dettes</button>
        </section>

        <!-- Zone d'écriture -->
        <footer class="input-bar">
            <textarea class="chat-input" id="chatInput" placeholder="Pose ta question financière..." autocomplete="off" name="wari_chat_query" autocorrect="off" spellcheck="false" rows="1"></textarea>
            <button class="send-btn" id="sendBtn" onclick="submitChat()" aria-label="Envoyer le message">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </button>
        </footer>
    </div>

    <!-- Script de gestion du Chat Coach AI -->
    <script>
        var coachChatHistory = [];

        // Fonction pour formater en gras (**texte**) et ajouter des retours à la ligne de manière sécurisée (Sans XSS)
        function renderFormattedText(container, text) {
            var lines = text.split('\n');
            for (var i = 0; i < lines.length; i++) {
                if (i > 0) {
                    container.appendChild(document.createElement('br'));
                }
                
                var line = lines[i];
                var boldRegex = /\*\*(.*?)\*\*/g;
                var lastIdx = 0;
                var match;
                
                while ((match = boldRegex.exec(line)) !== null) {
                    var textBefore = line.substring(lastIdx, match.index);
                    if (textBefore) {
                        container.appendChild(document.createTextNode(textBefore));
                    }
                    
                    var strong = document.createElement('strong');
                    strong.textContent = match[1];
                    container.appendChild(strong);
                    
                    lastIdx = boldRegex.lastIndex;
                }
                
                var textAfter = line.substring(lastIdx);
                if (textAfter) {
                    container.appendChild(document.createTextNode(textAfter));
                }
            }
        }

        // Ajouter un message à la liste de manière sécurisée
        function appendChatMessage(text, sender) {
            var container = document.getElementById('chatMessages');
            if (!container) return;
            
            var msgDiv = document.createElement('div');
            if (sender === 'user') {
                msgDiv.className = "message message-user";
                msgDiv.textContent = text;
            } else {
                msgDiv.className = "message message-coach";
                renderFormattedText(msgDiv, text);
            }
            
            container.appendChild(msgDiv);
            container.scrollTop = container.scrollHeight;
        }

        // Ajouter un indicateur de réflexion
        function appendChatThinking(id) {
            var container = document.getElementById('chatMessages');
            if (!container) return;
            
            var msgDiv = document.createElement('div');
            msgDiv.id = id;
            msgDiv.className = "message-thinking";
            
            for (var i = 0; i < 3; i++) {
                var dot = document.createElement('span');
                dot.className = "dot";
                msgDiv.appendChild(dot);
            }
            
            container.appendChild(msgDiv);
            container.scrollTop = container.scrollHeight;
        }

        // Récupérer le contexte financier calculé par PHP et LocalStorage
        function getFinancialStatusContext() {
            var baseContext = <?php echo json_encode([
                'cash_restant_perso' => $cash,
                'cash_restant_pro' => $cashPro,
                'jours_restants' => $daysLeft,
                'budget_quotidien_conseille_perso' => ($daysLeft > 0 ? (int)round($cash / $daysLeft) : 0),
                'budget_quotidien_conseille_pro' => ($daysLeft > 0 ? (int)round($cashPro / $daysLeft) : 0),
                'enveloppes_personnelles_details' => $catSummaryPerso,
                'enveloppes_professionnelles_details' => $catSummaryPro,
                'defis_epargne_actifs_details' => $challengesSummary,
                'depenses_recentes_details' => $recentExpensesSummary,
                'dettes_details' => $debtsSummary,
                'total_dettes' => $totalDettes,
                'capital_projet_perso' => $projectCapital,
                'capital_projet_pro' => $projectCapitalPro,
                'historique_6_derniers_mois' => $historySummary,
                'articles_vecu_details' => $articlesSummary,
                'devise' => $currency
            ]); ?>;

            baseContext.portefeuille_actif = localStorage.getItem("wari_current_wallet") || "perso";

            // Récupération de l'objectif d'épargne projet ciblé par l'utilisateur (Perso)
            var goalStr = localStorage.getItem("wari_vault_goal");
            var goalAmount = 0;
            var goalLabel = "";
            if (goalStr) {
                try {
                    var goal = JSON.parse(goalStr);
                    if (goal) {
                        goalAmount = parseInt(goal.amount) || 0;
                        goalLabel = goal.label || goal.name || "";
                    }
                } catch(e) {}
            }
            baseContext.objectif_projet_perso_montant = goalAmount;
            baseContext.objectif_projet_perso_label = goalLabel;

            // Récupération de l'objectif d'épargne projet ciblé par l'utilisateur (Pro)
            var goalStrPro = localStorage.getItem("wari_vault_goal_pro");
            var goalAmountPro = 0;
            var goalLabelPro = "";
            if (goalStrPro) {
                try {
                    var goalPro = JSON.parse(goalStrPro);
                    if (goalPro) {
                        goalAmountPro = parseInt(goalPro.amount) || 0;
                        goalLabelPro = goalPro.label || goalPro.name || "";
                    }
                } catch(e) {}
            }
            baseContext.objectif_projet_pro_montant = goalAmountPro;
            baseContext.objectif_projet_pro_label = goalLabelPro;

            return baseContext;
        }

        // Action de soumission de la discussion
        async function submitChat() {
            var input = document.getElementById('chatInput');
            var sendBtn = document.getElementById('sendBtn');
            if (!input || !sendBtn) return;
            
            var text = input.value.trim();
            if (!text) return;
            
            input.value = '';
            input.style.height = '38px';
            input.disabled = true;
            sendBtn.disabled = true;
            
            // Fermer le clavier virtuel en enlevant le focus de la zone de saisie
            input.blur();
            
            appendChatMessage(text, 'user');
            
            var thinkingId = 'thinking_' + Date.now();
            appendChatThinking(thinkingId);
            
            var financialContext = getFinancialStatusContext();
            
            try {
                var formData = new FormData();
                formData.append('action', 'coach_chat');
                formData.append('message', text);
                formData.append('data', JSON.stringify(financialContext));
                formData.append('history', JSON.stringify(coachChatHistory));
                
                var res = await fetch('../academy-admin/ai_gateway.php', {
                    method: 'POST',
                    body: formData
                });
                
                var result = await res.json();
                
                var thinkingEl = document.getElementById(thinkingId);
                if (thinkingEl) {
                    thinkingEl.remove();
                }
                
                if (result && result.response) {
                    appendChatMessage(result.response, 'coach');
                    
                    coachChatHistory.push({ role: 'user', content: text });
                    coachChatHistory.push({ role: 'model', content: result.response });
                    if (coachChatHistory.length > 20) {
                        coachChatHistory.shift();
                        coachChatHistory.shift();
                    }
                    localStorage.setItem('wari_coach_history', JSON.stringify(coachChatHistory));
                } else {
                    appendChatMessage("Désolé, j'ai rencontré un petit problème réseau. Reste concentré sur tes objectifs !", 'coach');
                }
            } catch (err) {
                console.error("Coach chat error:", err);
                var thinkingEl = document.getElementById(thinkingId);
                if (thinkingEl) {
                    thinkingEl.remove();
                }
                appendChatMessage("Aïe, impossible de me connecter pour l'instant. Garde ta discipline budgétaire, c'est le plus important !", 'coach');
            } finally {
                input.disabled = false;
                sendBtn.disabled = false;
                
                setTimeout(adjustLayoutForKeyboard, 50);
            }
        }

        // Envoyer une suggestion pré-définie
        function sendSuggestion(text) {
            var input = document.getElementById('chatInput');
            if (input) {
                input.value = text;
                submitChat();
            }
        }

        // Ajuster la hauteur de la page selon le clavier virtuel
        function adjustLayoutForKeyboard() {
            if (window.visualViewport) {
                var vv = window.visualViewport;
                var height = vv.height;
                var topOffset = vv.offsetTop;
                
                var app = document.getElementById('chatApp');
                if (app) {
                    app.style.height = height + 'px';
                    app.style.top = topOffset + 'px';
                }
                
                window.scrollTo(0, 0);
                
                var messagesContainer = document.getElementById('chatMessages');
                if (messagesContainer) {
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }
            }
        }

        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', adjustLayoutForKeyboard);
            window.visualViewport.addEventListener('scroll', adjustLayoutForKeyboard);
        }

        // Effacer l'historique de discussion
        function clearHistory() {
            localStorage.removeItem('wari_coach_history');
            coachChatHistory = [];
            
            var container = document.getElementById('chatMessages');
            if (container) {
                container.replaceChildren(); // Nettoyage DOM sécurisé
            }
            
            appendChatMessage("Historique effacé. De quoi aimerais-tu parler maintenant ?", 'coach');
        }

        // Au chargement complet de la page
        window.addEventListener('DOMContentLoaded', function() {
            adjustLayoutForKeyboard();
            
            var input = document.getElementById('chatInput');
            var app = document.getElementById('chatApp');
            if (input) {


                // Auto-growing textarea
                input.addEventListener('input', function() {
                    this.style.height = '38px';
                    var scrollH = this.scrollHeight;
                    if (scrollH > 38) {
                        this.style.height = Math.min(scrollH, 120) + 'px';
                    }
                    adjustLayoutForKeyboard();
                });

                if (app) {
                    input.addEventListener('focus', function() {
                        app.classList.add('keyboard-active');
                        setTimeout(adjustLayoutForKeyboard, 100);
                    });
                    input.addEventListener('blur', function() {
                        app.classList.remove('keyboard-active');
                        setTimeout(adjustLayoutForKeyboard, 100);
                    });
                }
            }
            
            // Charger l'historique de discussion
            var savedHistory = localStorage.getItem('wari_coach_history');
            if (savedHistory) {
                try {
                    coachChatHistory = JSON.parse(savedHistory) || [];
                } catch(e) {
                    coachChatHistory = [];
                }
            }
            
            // Rendre les messages passés
            if (coachChatHistory && coachChatHistory.length > 0) {
                for (var i = 0; i < coachChatHistory.length; i++) {
                    var msg = coachChatHistory[i];
                    var sender = (msg.role === 'user') ? 'user' : 'coach';
                    appendChatMessage(msg.content, sender);
                }
            } else {
                // Premier message du Coach
                appendChatMessage("Salut ! Ravi de te retrouver pour t'accompagner dans ta discipline financière. De quoi aimerais-tu parler aujourd'hui ?", 'coach');
            }
        });
    </script>
</body>
</html>

```

### `academy/index.php`

```php
<?php
// /var/www/html/academy/index.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Academy.php';
require_once __DIR__ . '/../config/session_check.php';

// session_start uniquement si pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? null;
$email   = $_SESSION['user_email'] ?? 'visiteur';
$coursEnCours = $coursEnCours ?? ""; 
$coursTermines = $coursTermines ?? ""; 

// ✅ Log de la visite Academy
logAuthAttempt($pdo, 'ACADEMY_VISIT', $email, $user_id);

$academy         = new Academy($pdo);
$categories      = $academy->getCategories();
$coursesWithProgress = $user_id ? $academy->getAllCoursesWithProgress($user_id) : [];

$totalCours      = array_sum(array_column($categories, 'nb_cours'));
$totalCategories = count($categories);
if ($user_id && !empty($coursesWithProgress)) {
    foreach ($coursesWithProgress as $c) {
        if ($c['progression'] > 0 && $c['progression'] < 100) $coursEnCours++;
        if ($c['progression'] == 100) $coursTermines++;
    }

    // ✅ Trier pour mettre les cours non terminés en premier
    usort($coursesWithProgress, function($a, $b) {
        // Un cours terminé (100) va à la fin
        if ($a['progression'] == 100 && $b['progression'] < 100) return 1;
        if ($a['progression'] < 100 && $b['progression'] == 100) return -1;
        return 0; // Garder l'ordre original sinon
    });
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wari-Academy | Éducation Financière</title>

    <meta name="description" content="Maîtrisez votre destin financier avec Wari Academy. Formations exclusives, outils de gestion premium et coaching pour les bâtisseurs d'empire.">
    <meta name="keywords" content="éducation financière, gestion de budget, investissement, wari academy, rebonly, finance personnelle, coaching business">
    <meta name="author" content="Wari-Academy">
    <meta name="robots" content="index, follow">

    <meta property="og:type" content="website">
    <meta property="og:url" content="https://wari.digiroys.com/academy">
    <meta property="og:title" content="Wari Academy | Comprendre l’argent simplement">
    <meta property="og:description" content="Devenez inarrêtable. Maîtrisez l'art de l'argent avec Wari Academy, la formation qui transforme les rêveurs en bâtisseurs d'empire.">
    <meta property="og:image" content="../assets/finaleduc.jpg">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Wari Academy | Comprendre l’argent simplement">
    <meta name="twitter:description" content="Devenez inarrêtable. Maîtrisez l'art de l'argent avec Wari Academy, la formation qui transforme les rêveurs en bâtisseurs d'empire.">
    <meta name="twitter:image" content="../assets/finaleduc.jpg">

    <meta name="theme-color" content="#080b10">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <link rel="icon" type="image/png" href="../assets/warifinance3d.png" />
    <link rel="apple-touch-icon" href="../assets/warifinance3d.png">
    <link rel="canonical" href="https://wari.digiroys.com/academy" />

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        h1,
        h2,
        h3,
        .font-outfit {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
        }
    </style>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0F0A02;
            color: #FAF5E9;
        }

        .font-heading {
            font-family: 'Outfit', sans-serif;
        }

        /* Couleur Or Wari */
        .text-wari-gold {
            color: #F5A623;
        }

        .bg-wari-gold {
            background-color: #F5A623;
        }

        .border-wari-gold {
            border-color: rgba(201, 168, 76, 0.2);
        }

        /* Bento Glass Effect */
        .bento-card {
            background: linear-gradient(145deg, #1A1209 0%, #0F0A02 100%);
            border: 1px solid rgba(201, 168, 76, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .bento-card:hover {
            border-color: rgba(201, 168, 76, 0.4);
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        /* Animation de brillance */
        .shimmer {
            position: relative;
            overflow: hidden;
        }

        .shimmer::after {
            content: '';
            position: absolute;
            top: -150%;
            left: -150%;
            width: 300%;
            height: 300%;
            background: radial-gradient(circle, rgba(201, 168, 76, 0.08) 0%, transparent 50%);
            pointer-events: none;
        }
    </style>
</head>

<body class="antialiased selection:bg-wari-gold selection:text-black">

    <nav class="sticky top-0 z-50 bg-[#0F0A02] border-b border-wari-gold/20 px-4 h-16 flex items-center justify-between">
        <a href="https://wari.digiroys.com/academy/" class="font-heading text-xl font-black text-wari-gold tracking-tighter">
            Wari<span class="text-white/80 font-light">Academy.</span>
        </a>

        <div class="flex items-center gap-1 bg-white/5 p-1 rounded-xl border border-white/10">
            <a href="https://wari.digiroys.com" class="text-white/60 hover:text-white px-2 py-2 text-xs font-bold uppercase tracking-widest transition-all">← App</a>
            <a href="/academy/" class="bg-wari-gold text-black px-2 py-2 text-xs font-black uppercase tracking-widest rounded-lg shadow-lg">Acdm</a>
        </div>

        <?php if ($user_id): ?>
            <a href="#" class="flex items-center gap-3 px-2 py-2 rounded-xl">
                <!-- <span class="text-xs font-bold text-white/80">Profil</span> -->
                <div class="w-8 h-8 bg-wari-gold rounded-lg flex items-center justify-center text-black font-bold text-xs"><?= substr($_SESSION['user_email'] ?? 'U', 0, 2) ?></div>
            </a>
        <?php else: ?>
            <a href="https://wari.digiroys.com/config/auth.php" class="bg-wari-gold text-black px-2 py-3 rounded-xl text-xs font-black uppercase tracking-widest hover:scale-105 transition-all shadow-xl shadow-wari-gold/20">
                Rejoindre
            </a>
        <?php endif; ?>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-4">

        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mb-8">

            <div class="md:col-span-8 bento-card shimmer rounded-[1rem] p-4 md:p-8 flex flex-col justify-end min-h-[220px]">
                <div class="mb-8">
                    <span class="inline-flex items-center gap-2 bg-wari-gold/10 border border-wari-gold/30 text-wari-gold text-[10px] font-black uppercase tracking-[0.3em] px-5 py-2 rounded-full">
                        Savoir c'est Pouvoir
                    </span>
                </div>
                <h1 class="font-heading text-4xl md:text-6xl font-black text-white leading-[1.1] mb-6">
                    L'argent, ça <br><span class="text-wari-gold italic">s'apprend.</span>
                </h1>
                <p class="text-white/60 text-sm md:text-lg max-w-lg leading-relaxed">
                    Découvre tout ce que l’école ne t’a pas appris pour dominer tes finances et construire, pas à pas, ta liberté financière.
                </p>
            </div>

            <div class="md:col-span-4 grid grid-rows-2 gap-5">
                <div class="bento-card rounded-[1rem] p-4 flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-black text-wari-gold uppercase mb-1">Cours</p>
                        <div class="text-4xl font-heading font-black"><?= $totalCours ?: '24' ?></div>
                    </div>
                    <span class="text-4xl opacity-100">
                        <svg width="46" height="46" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="m18.364 2-4.546 4.09v10L18.364 12V2ZM7 4.727c-1.773 0-3.682.364-5 1.364v13.327a.49.49 0 0 0 .455.455c.09 0 .136-.064.227-.064 1.227-.59 3-.99 4.318-.99 1.773 0 3.682.363 5 1.363 1.227-.773 3.454-1.364 5-1.364 1.5 0 3.046.282 4.318.964.091.045.136.027.227.027a.489.489 0 0 0 .455-.454V6.09c-.546-.41-1.136-.682-1.818-.91v12.273C19.182 17.136 18.09 17 17 17c-1.546 0-3.773.59-5 1.364V6.09c-1.318-1-3.227-1.363-5-1.363Z"></path>
                        </svg>
                    </span>
                </div>
                <div class="bento-card rounded-[1rem] p-4 flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-black text-wari-gold uppercase mb-1">Domaines</p>
                        <div class="text-4xl font-heading font-black"><?= $totalCategories ?: '06' ?></div>
                    </div>
                    <span class="text-4xl opacity-100">
                        <svg width="46" height="46" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17.25 4.5H6.75a.75.75 0 0 1 0-1.5h10.5a.75.75 0 1 1 0 1.5Z"></path>
                            <path d="M18.75 6.75H5.25a.75.75 0 0 1 0-1.5h13.5a.75.75 0 1 1 0 1.5Z"></path>
                            <path d="M19.647 21H4.353a2.106 2.106 0 0 1-2.103-2.103V9.603A2.106 2.106 0 0 1 4.353 7.5h15.294a2.106 2.106 0 0 1 2.103 2.103v9.294A2.106 2.106 0 0 1 19.647 21Z"></path>
                        </svg>
                    </span>
                </div>
            </div>
        </div>

        <?php if ($user_id): ?>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-20">
                <div class="bg-white/5 border border-white/10 p-4 rounded-[1rem] flex items-center gap-4">
                    <div class="text-2xl text-blue-400">▶️</div>
                    <div>
                        <div class="text-xl font-bold"><?= $coursEnCours ?></div>
                        <div class="text-[9px] text-white/30 uppercase font-bold tracking-widest">En cours</div>
                    </div>
                </div>
                <div class="bg-white/5 border border-white/10 p-4 rounded-[1rem] flex items-center gap-4">
                    <div class="text-2xl text-green-400">🏆</div>
                    <div>
                        <div class="text-xl font-bold"><?= $coursTermines ?></div>
                        <div class="text-[9px] text-white/30 uppercase font-bold tracking-widest">Terminés</div>
                    </div>
                </div>
                <div class="bg-white/5 p-4 border border-white/10 rounded-[1rem] flex items-center gap-4">
                    <div class="text-2xl">⚡</div>
                    <div>
                        <div class="text-xl font-bold text-wari-gold">Pro</div>
                        <div class="text-[9px] text-wari-gold/50 uppercase font-bold tracking-widest">Niveau</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="flex items-center justify-between mb-10">
            <h2 class="font-heading text-3xl font-black">Tous les cours</h2>
            <div class="flex-1 h-[1px] bg-white/10"></div>
            <span class="text-xs font-bold text-white/70 uppercase tracking-[0.3em]"><?= $totalCours ?> Modules</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php foreach ($coursesWithProgress as $course): 
                $isNew = ($course['progression'] == 0);
                $isInProgress = ($course['progression'] > 0 && $course['progression'] < 100);
                $isPremiumCourse = ($course['est_gratuit'] == 0 || $course['niveau'] === 'avance');
                $isLocked = !($_SESSION['is_premium'] ?? false) && $isPremiumCourse;
            ?>
                <a href="/academy/course.php?slug=<?= $course['slug'] ?>" 
                   class="bento-card rounded-[1rem] overflow-hidden group flex flex-col <?= $isNew ? 'border-amber-500/30' : ($isInProgress ? 'border-blue-500/30' : '') ?>">
                    
                    <?php if ($isLocked): ?>
                        <div class="h-3 bg-gradient-to-r from-amber-500 to-orange-600 shadow-[0_0_20px_rgba(245,166,35,0.2)] relative">
                             <span class="absolute right-4 top-4 bg-gradient-to-r from-amber-500 to-orange-600 text-black text-[8px] font-black px-2 py-0.5 rounded uppercase tracking-widest">Premium</span>
                        </div>
                    <?php elseif ($isNew): ?>
                        <div class="h-3 bg-amber-500 shadow-[0_0_20px_rgba(245,166,35,0.3)] relative">
                            <span class="absolute right-4 top-4 bg-amber-500 text-black text-[8px] font-black px-2 py-0.5 rounded uppercase tracking-widest animate-pulse">Nouveau</span>
                        </div>
                    <?php elseif ($isInProgress): ?>
                        <div class="h-3 bg-blue-500 shadow-[0_0_20px_rgba(59,130,246,0.3)] relative">
                             <span class="absolute right-4 top-4 bg-blue-500 text-white text-[8px] font-black px-2 py-0.5 rounded uppercase tracking-widest">En cours</span>
                        </div>
                    <?php else: ?>
                        <div class="h-3 bg-emerald-500/50 shadow-[0_0_20px_rgba(16,185,129,0.1)]"></div>
                    <?php endif; ?>

                    <div class="p-6 flex-1">
                        <div class="flex justify-between items-start mb-6">
                            <?php if ($isPremiumCourse): ?>
                                <span class="bg-amber-500/10 border border-amber-500/30 px-3 py-1 rounded-lg text-[9px] font-black uppercase text-amber-500 flex items-center gap-1.5">
                                    🔒 Premium
                                </span>
                            <?php else: ?>
                                <span class="bg-white/5 border border-white/10 px-3 py-1 rounded-lg text-[9px] font-black uppercase text-wari-gold flex items-center gap-1.5">
                                    <?php
                                    $lucideIcons = ['wallet','landmark','rocket','alert-triangle','trending-up','brain','book','lightbulb','target','award','gem','key','bar-chart','globe','briefcase','shield','zap','leaf'];
                                    if (in_array($course['category_icone'], $lucideIcons)): ?>
                                        <i data-lucide="<?= htmlspecialchars($course['category_icone']) ?>" class="w-3 h-3 shrink-0"></i>
                                    <?php else: ?>
                                        <?= htmlspecialchars($course['category_icone']) ?>
                                    <?php endif; ?>
                                    <span><?= htmlspecialchars($course['category_titre']) ?></span>
                                </span>
                            <?php endif; ?>
                            <span class="text-[9px] font-bold text-white/20 uppercase tracking-widest italic"><?= $course['niveau'] ?></span>
                        </div>
                        <h3 class="font-heading text-xl font-black text-white mb-4 group-hover:text-wari-gold transition-colors leading-tight"><?= $course['titre'] ?></h3>
                        <p class="text-white/40 text-sm line-clamp-2 leading-relaxed mb-8"><?= $course['description'] ?></p>

                        <?php if (!$isLocked && $course['progression'] > 0): ?>
                            <div class="space-y-3">
                                <div class="flex justify-between text-[9px] font-black uppercase text-wari-gold/70">
                                    <span>Progression</span>
                                    <span><?= $course['progression'] ?>%</span>
                                </div>
                                <div class="h-1.5 w-full bg-white/5 rounded-full overflow-hidden">
                                    <div class="h-full bg-wari-gold shadow-[0_0_10px_rgba(201,168,76,0.5)]" style="width: <?= $course['progression'] ?>%"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-8 pt-0 mt-auto flex items-center justify-between border-t border-white/5 pt-6 bg-white/[0.02]">
                        <div class="flex gap-4">
                            <span class="text-[10px] font-bold text-white/30 uppercase">⏱ <?= $course['duree_minutes'] ?>m</span>
                            <span class="text-[10px] font-bold text-white/30 uppercase">📖 <?= $course['nb_lecons'] ?> leçons</span>
                        </div>
                        <span class="text-[10px] font-black uppercase tracking-widest text-wari-gold group-hover:translate-x-2 transition-transform">
                            <?php if ($isLocked): ?>
                                🔒 Débloquer →
                            <?php else: ?>
                                <?= ($course['progression'] == 100) ? 'Revoir' : ($isInProgress ? 'Continuer →' : 'Commencer →') ?>
                            <?php endif; ?>
                        </span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

    </div>

    <footer class="mt-5 py-10 border-t border-white/5 bg-black/50 text-center">
        <div class="font-heading text-xl font-black text-wari-gold mb-4 tracking-tighter">Wari Academy.</div>
        <p class="text-[10px] font-bold text-white/40 uppercase tracking-[0.4em] mb-8 italic">Le savoir est la seule monnaie qui ne se dévalue jamais.</p>
        <div class="text-[9px] text-white/20">&copy; <?= date('Y') ?> WARI FINANCE — TOUS DROITS RÉSERVÉS.</div>
    </footer>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>
```

### `academy/course.php`

```php
<?php
// /var/www/html/academy/course.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Academy.php';

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/session_check.php';



$user_id = $_SESSION['user_id'] ?? null;

// Redirection si non connecté
if (!$user_id) {
    header('Location: https://wari.digiroys.com/config/auth.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Tracking du clic sur notification push
if (isset($_GET['push_log_id'])) {
    try {
        $pushLogId = (int)$_GET['push_log_id'];
        $stmtClick = $pdo->prepare("UPDATE wari_push_logs SET click_count = click_count + 1 WHERE id = ?");
        $stmtClick->execute([$pushLogId]);
    } catch (Exception $e) {
        error_log("Erreur tracking clic push : " . $e->getMessage());
    }
}

$academy = new Academy($pdo);

// Récupération du cours via le slug
$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header('Location: /academy/');
    exit;
}

$course = $academy->getCourseBySlug($slug);
if (!$course) {
    header('Location: /academy/');
    exit;
}

$isLocked = !($_SESSION['is_premium'] ?? false) && ($course['est_gratuit'] == 0 || $course['niveau'] === 'avance');

$lessons  = $academy->getLessonsByCourse($course['id']);
$pdfs     = $academy->getPdfsByCourse($course['id']);

if ($isLocked) {
    $progress = 0;
    foreach ($lessons as &$lesson) {
        $lesson['complete'] = false;
    }
    unset($lesson);
    $nextLesson = null;
    $totalLecons = count($lessons);
    $doneLecons = 0;
    $coursTermine = false;
} else {
    $progress = $academy->getCourseProgress($user_id, $course['id']);
    // Statut de chaque leçon pour cet utilisateur
    foreach ($lessons as &$lesson) {
        $lesson['complete'] = $academy->isLessonComplete($user_id, $lesson['id']);
    }
    unset($lesson);

    // Première leçon non complétée = leçon à reprendre
    $nextLesson = null;
    foreach ($lessons as $l) {
        if (!$l['complete']) {
            $nextLesson = $l;
            break;
        }
    }
    // Si tout est terminé, pointer sur la première leçon
    if (!$nextLesson && !empty($lessons)) {
        $nextLesson = $lessons[0];
    }
    $totalLecons   = count($lessons);
    $doneLecons    = count(array_filter($lessons, fn($l) => $l['complete']));
    $coursTermine  = $totalLecons > 0 && $doneLecons === $totalLecons;
}
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($course['titre']) ?> | Wari Academy</title>

    <!-- SEO -->
    <meta name="description" content="<?= htmlspecialchars($course['description'] ?? 'Découvrez ce cours sur Wari Academy et améliorez votre intelligence financière.') ?>">
    <meta name="robots" content="index, follow">
    <meta name="author" content="Wari Academy">

    <!-- Canonical (IMPORTANT avec slug) -->
    <link rel="canonical" href="https://wari.digiroys.com/academy/course.php?slug=<?= urlencode($course['slug']) ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/warifinance3d.png" />
    <link rel="apple-touch-icon" href="../assets/warifinance3d.png" />

    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($course['titre']) ?> | Wari Academy">
    <meta property="og:description" content="<?= htmlspecialchars($course['description'] ?? '') ?>">
    <meta property="og:image" content="../assets/default.jpg">
    <meta property="og:url" content="https://wari.digiroys.com/academy/course.php?slug=<?= urlencode($course['slug']) ?>">
    <meta property="og:type" content="article">
    <meta property="og:site_name" content="Wari Academy">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($course['titre']) ?> | Wari Academy">
    <meta name="twitter:description" content="<?= htmlspecialchars($course['description'] ?? '') ?>">
    <meta name="twitter:image" content="../assets/default.jpg">

    <!-- Theme -->
    <meta name="theme-color" content="#0f172a">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- STRUCTURED DATA -->
    <script type="application/ld+json">
    {
    "@context": "https://schema.org",
    "@type": "Course",
    "name": "<?= htmlspecialchars($course['titre']) ?>",
    "description": "<?= htmlspecialchars($course['description'] ?? '') ?>",
    "url": "https://wari.digiroys.com/academy/course.php?slug=<?= urlencode($course['slug']) ?>",
    "provider": {
        "@type": "Organization",
        "name": "Wari Academy",
        "url": "https://wari.digiroys.com"
    }
    }
    </script>
    <!-- Utilisation de Outfit (Titres) et Plus Jakarta Sans (Corps) comme défini par le standard Tailwind de Wari -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                        heading: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        slate: {
                            850: '#151e2e',
                            900: '#0f172a',
                            950: '#020617',
                        },
                        wari: {
                            gold: '#C9A84C',
                            goldLight: '#F0D080',
                            goldDark: '#8B6914',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .cat-color {
            color: <?= htmlspecialchars($course['category_couleur'] ?? '#C9A84C') ?>;
        }
        .bg-cat-color {
            background-color: <?= htmlspecialchars($course['category_couleur'] ?? '#C9A84C') ?>;
        }
        .border-cat-color {
            border-color: <?= htmlspecialchars($course['category_couleur'] ?? '#C9A84C') ?>;
        }
        
        /* Glassmorphism Bento Cards */
        .bento-card {
            background: rgba(30, 41, 59, 0.4); /* base slate-800 with transparency */
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 1.5rem;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .bento-card:hover {
            border-color: rgba(255, 255, 255, 0.1);
            background: rgba(30, 41, 59, 0.6);
        }
        .bento-card-highlight {
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.9));
            border: 1px solid rgba(201, 168, 76, 0.2);
        }
    </style>
</head>

<body class="bg-slate-950 text-slate-300 font-sans antialiased min-h-screen flex flex-col selection:bg-wari-gold selection:text-slate-950">

    <!-- ── NAVIGATION ──────────────────────────────────────────── -->
    <nav class="bg-slate-950/80 backdrop-blur-md mt-3 mb-2 px-4 h-18 flex items-center justify-between">
        <a href="/academy/" class="font-heading text-xl font-black text-wari-gold tracking-tight">
            Wari<span class="font-light text-white">Academy.</span>
        </a>
        
        <div class="flex items-center gap-2">
            <a href="https://wari.digiroys.com" class="flex items-center gap-2 text-slate-400 hover:text-white transition-colors text-[10px] font-bold uppercase tracking-widest mr-4">
                ← App
            </a>
            <a href="/academy/" class="flex items-center gap-2 text-slate-400 hover:text-white transition-colors text-[10px] font-bold uppercase tracking-widest">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Cours
            </a>
        </div>

        <a href="#" class="flex items-center gap-3 bg-white/5 hover:bg-white/10">
            <div class="w-8 h-8 bg-wari-gold rounded-lg flex items-center justify-center text-slate-950 font-bold text-xs uppercase"><?= substr($_SESSION['user_email'] ?? 'U', 0, 2) ?></div>
        </a>
    </nav>

    <main class="flex-1 w-full max-w-7xl mx-auto px-2 md:px-2 py-2 md:py-4 flex flex-col gap-8 md:gap-12">
        
        <!-- ── HERO BENTO (Span full) ────────────────────────────── -->
        <section class="bento-card bento-card-highlight p-2 md:p-12 relative overflow-hidden group rounded-[1rem]">
            <!-- Decorative background elements -->
            <div class="absolute -top-40 -right-40 w-96 h-96 bg-cat-color rounded-full mix-blend-multiply filter blur-3xl opacity-10 group-hover:opacity-[0.15] transition-opacity duration-700"></div>
            <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-wari-gold rounded-full mix-blend-multiply filter blur-3xl opacity-10 group-hover:opacity-[0.15] transition-opacity duration-700"></div>

            <div class="relative z-10 flex flex-col md:flex-row md:items-end justify-between gap-8">
                <div class="flex-1">
                    <!-- Breadcrumb -->
                    <div class="flex items-center gap-2 text-xs md:text-sm text-slate-400 mb-6 font-medium">
                        <a href="/academy/" class="hover:text-wari-gold transition-colors">Academy</a>
                        <span>/</span>
                        <a href="/academy/?cat=<?= htmlspecialchars($course['category_slug']) ?>" class="hover:text-wari-gold transition-colors">
                            <?= htmlspecialchars($course['category_titre']) ?>
                        </a>
                        <span>/</span>
                        <strong class="text-wari-goldLight truncate max-w-[200px] sm:max-w-none block"><?= htmlspecialchars($course['titre']) ?></strong>
                    </div>

                    <!-- Badges -->
                    <div class="flex flex-wrap items-center gap-3 mb-6">
                        <!-- Category Badge -->
                        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest border border-cat-color bg-slate-900/50 cat-color shadow-[0_0_15px_rgba(0,0,0,0.2)]">
                            <?php
                            $lucideIcons = ['wallet','landmark','rocket','alert-triangle','trending-up','brain','book','lightbulb','target','award','gem','key','bar-chart','globe','briefcase','shield','zap','leaf'];
                            if (in_array($course['category_icone'], $lucideIcons)): ?>
                                <i data-lucide="<?= htmlspecialchars($course['category_icone']) ?>" class="w-4 h-4 shrink-0"></i>
                            <?php else: ?>
                                <span><?= htmlspecialchars($course['category_icone'] ?? '') ?></span>
                            <?php endif; ?>
                            <span><?= htmlspecialchars($course['category_titre']) ?></span>
                        </div>

                        <!-- Premium Badge if applicable -->
                        <?php if ($course['est_gratuit'] == 0 || $course['niveau'] === 'avance'): ?>
                            <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-black uppercase tracking-widest border border-amber-500/30 bg-amber-500/10 text-amber-500 shadow-[0_0_10px_rgba(245,166,35,0.1)]">
                                🔒 Premium
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Title & Description -->
                    <h1 class="font-heading text-4xl md:text-5xl lg:text-7xl font-black text-white leading-[1.1] mb-6">
                        <?= htmlspecialchars($course['titre']) ?>
                    </h1>

                    <?php if ($course['description']): ?>
                        <p class="text-slate-400 text-sm md:text-lg max-w-2xl leading-relaxed mb-8">
                            <?= htmlspecialchars($course['description']) ?>
                        </p>
                    <?php endif; ?>

                    <!-- Meta Tags -->
                    <div class="flex flex-wrap gap-4 text-xs font-bold uppercase tracking-widest text-slate-400">
                        <div class="flex items-center gap-2 bg-slate-800/50 px-4 py-2 rounded-xl border border-slate-700/50">
                            <strong class="text-white"><?= $course['duree_minutes'] ?> min</strong>
                        </div>
                        <div class="flex items-center gap-2 bg-slate-800/50 px-4 py-2 rounded-xl border border-slate-700/50">
                            <strong class="text-white"><?= $totalLecons ?> leçon<?= $totalLecons > 1 ? 's' : '' ?></strong>
                        </div>
                        <div class="flex items-center gap-2 bg-slate-800/50 px-4 py-2 rounded-xl border border-slate-700/50">
                            <strong class="text-white"><?= ucfirst($course['niveau']) ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Progress & CTA Box -->
                <div class="bg-slate-900/60 backdrop-blur-md rounded-3xl p-4 border border-white/10 shrink-0 w-full md:w-80 shadow-2xl">
                    <div class="flex justify-between items-end mb-3">
                        <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Progression</div>
                        <div class="text-3xl font-heading font-black text-wari-gold"><?= $progress ?>%</div>
                    </div>
                    
                    <div class="h-2 w-full bg-slate-800 rounded-full overflow-hidden mb-4">
                        <div class="h-full bg-gradient-to-r from-wari-goldDark to-wari-gold rounded-full transition-all duration-1000 relative" style="width:<?= $progress ?>%">
                            <div class="absolute inset-0 bg-white/20 animate-pulse"></div>
                        </div>
                    </div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-slate-500 text-center mb-6">
                        <?= $doneLecons ?> / <?= $totalLecons ?> terminées
                    </div>

                    <?php if ($isLocked): ?>
                        <a href="https://wari.digiroys.com/paid/index.php" 
                           class="group flex items-center justify-center gap-2 w-full py-4 px-6 rounded-2xl font-black text-xs uppercase tracking-widest transition-all transform hover:scale-[1.02] active:scale-[0.98] shadow-lg bg-gradient-to-r from-amber-500 to-orange-600 text-slate-950 shadow-amber-500/20">
                            🔒 Débloquer ce cours
                        </a>
                    <?php elseif ($nextLesson): ?>
                        <a href="/academy/lesson.php?id=<?= $nextLesson['id'] ?>" 
                           class="group flex items-center justify-center gap-2 w-full py-4 px-6 rounded-2xl font-black text-xs uppercase tracking-widest transition-all transform hover:scale-[1.02] active:scale-[0.98] shadow-lg <?= $coursTermine ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/20' : 'bg-wari-gold text-slate-950 hover:bg-wari-goldLight shadow-wari-gold/20' ?>">
                            <?php if ($coursTermine): ?>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                Revoir le module
                            <?php elseif ($progress > 0): ?>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Continuer
                            <?php else: ?>
                                Commencer
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- ── MAIN CONTENT GRID ────────────────────────────────── -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- LISTE DES LEÇONS -->
            <div class="lg:col-span-8 space-y-6">
                <div class="bento-card rounded-[1rem] overflow-hidden relative">
                    <?php if ($isLocked): ?>
                        <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-md z-30 flex flex-col items-center justify-center p-6 text-center">
                            <div class="w-16 h-16 bg-wari-gold/10 text-wari-gold rounded-full flex items-center justify-center text-3xl mb-4 border border-wari-gold/20 animate-pulse">🔒</div>
                            <h3 class="font-heading text-2xl font-black text-white mb-2">Contenu Premium</h3>
                            <p class="text-slate-400 text-sm max-w-md mb-6">Ce module de formation et ses outils de calculs exclusifs sont réservés aux membres Premium.</p>
                            <a href="https://wari.digiroys.com/paid/index.php" class="bg-gradient-to-r from-amber-500 to-orange-600 text-black font-black text-xs uppercase tracking-widest px-8 py-4 rounded-xl shadow-lg shadow-amber-500/20 active:scale-95 transition-all">
                                Débloquer l'accès
                            </a>
                        </div>
                    <?php endif; ?>
                    <div class="p-6 md:p-8 border-b border-white/5 flex items-center justify-between bg-slate-900/40">
                        <h2 class="font-heading text-2xl md:text-3xl font-black text-white flex items-center gap-3">
                            Programme
                        </h2>
                        <span class="px-4 py-1 bg-slate-800 text-slate-300 text-xs font-black uppercase tracking-widest rounded-full border border-slate-700">
                            <?= $doneLecons ?> / <?= $totalLecons ?>
                        </span>
                    </div>

                    <div class="divide-y divide-white/5">
                        <?php if (!empty($lessons)): ?>
                            <?php foreach ($lessons as $i => $lesson): ?>
                                <?php
                                $isComplete = $lesson['complete'];
                                $isCurrent  = $nextLesson && $lesson['id'] === $nextLesson['id'] && !$coursTermine;
                                ?>
                                <a href="<?= $isLocked ? 'https://wari.digiroys.com/paid/index.php' : '/academy/lesson.php?id=' . $lesson['id'] ?>" 
                                   class="group p-5 md:p-6 flex items-center gap-5 hover:bg-slate-800/50 transition-colors <?= $isCurrent ? 'bg-slate-800/30 border-l-4 border-cat-color' : 'border-l-4 border-transparent' ?>">
                                    
                                    <!-- Lesson Number or Check -->
                                    <div class="w-12 h-12 shrink-0 rounded-2xl flex items-center justify-center font-black text-base transition-all <?= $isComplete ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : ($isCurrent ? 'bg-cat-color text-slate-950 shadow-lg shadow-cat-color/30' : 'bg-slate-800 text-slate-500 group-hover:bg-slate-700') ?>">
                                        <?php if ($isComplete): ?>
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                        <?php else: ?>
                                            <?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Lesson Info -->
                                    <div class="flex-1 min-w-0">
                                        <div class="text-base md:text-lg font-bold truncate transition-colors <?= $isComplete ? 'text-slate-500 line-through decoration-slate-600/50' : ($isCurrent ? 'text-white' : 'text-slate-200 group-hover:text-white') ?>">
                                            <?= htmlspecialchars($lesson['titre']) ?>
                                        </div>
                                        <div class="text-[10px] uppercase font-bold tracking-widest mt-1.5 flex items-center gap-2 <?= $isComplete ? 'text-slate-600' : 'text-slate-400' ?>">
                                            <?php
                                            $types = ['texte' => '📄 LECTURE', 'video' => '🎥 VIDÉO', 'quiz' => '🧩 QUIZ'];
                                            echo $types[$lesson['type']] ?? '📄 LECTURE';
                                            ?>
                                        </div>
                                    </div>

                                    <!-- Lesson Status Icon -->
                                    <div class="shrink-0 text-2xl opacity-50 group-hover:opacity-100 transition-opacity group-hover:scale-110 transform duration-300">
                                        <?php if ($isComplete): ?>
                                            ✅
                                        <?php elseif ($isCurrent): ?>
                                            <span class="animate-pulse">▶️</span>
                                        <?php else: ?>
                                            🔒
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-16 text-center text-slate-500">
                                <svg class="w-16 h-16 mx-auto mb-4 opacity-30 block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                <p class="text-sm font-medium">Aucune leçon disponible pour ce cours pour le moment.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- SIDEBAR -->
            <aside class="lg:col-span-4 space-y-6">
                
                <!-- Badge cours terminé -->
                <?php if ($coursTermine): ?>
                    <div class="bento-card rounded-[2rem] overflow-hidden border border-emerald-500/30">
                        <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/10 to-transparent"></div>
                        <div class="relative p-8 text-center">
                            <div class="text-5xl mb-4 animate-bounce">🏆</div>
                            <h3 class="font-heading text-xl font-black text-emerald-400 mb-2">MASTERCLASS</h3>
                            <p class="text-sm text-emerald-400/80 font-medium leading-relaxed">Félicitations, tu as complété ce module avec succès.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Stats Bento -->
                <div class="bento-card rounded-[1rem] p-4">
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mb-6 flex items-center gap-2">
                        <span>Statistiques</span>
                        <div class="flex-1 h-[1px] bg-white/5 ml-2"></div>
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-slate-900/50 border border-white/5 rounded-2xl p-4 text-center hover:bg-slate-800/80 transition-colors">
                            <div class="font-heading text-4xl font-black text-white mb-2"><?= $totalLecons ?></div>
                            <div class="text-[9px] uppercase font-black text-slate-500 tracking-[0.2em]">Leçons</div>
                        </div>
                        <div class="bg-slate-900/50 border border-white/5 rounded-2xl p-4 text-center hover:bg-slate-800/80 transition-colors">
                            <div class="font-heading text-4xl font-black text-white mb-2"><?= $course['duree_minutes'] ?></div>
                            <div class="text-[9px] uppercase font-black text-slate-500 tracking-[0.2em]">Minutes</div>
                        </div>
                        <div class="bg-slate-900/50 border border-white/5 rounded-2xl p-4 text-center hover:bg-slate-800/80 transition-colors">
                            <div class="font-heading text-4xl font-black text-wari-gold mb-2"><?= $progress ?>%</div>
                            <div class="text-[9px] uppercase font-black text-slate-500 tracking-[0.2em]">Acquis</div>
                        </div>
                        <div class="bg-slate-900/50 border border-white/5 rounded-2xl p-4 text-center hover:bg-slate-800/80 transition-colors">
                            <div class="font-heading text-4xl font-black text-white mb-2"><?= ucfirst($course['niveau'][0]) ?></div>
                            <div class="text-[9px] uppercase font-black text-slate-500 tracking-[0.2em]"><?= ucfirst($course['niveau']) ?></div>
                        </div>
                    </div>
                </div>

                <!-- PDF Payants -->
                <?php if (!empty($pdfs)): ?>
                    <div class="bento-card rounded-[2rem] p-8">
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mb-6 flex items-center gap-2">
                           <span>Ressources</span>
                            <div class="flex-1 h-[1px] bg-white/5 ml-2"></div>
                        </h3>
                        <div class="space-y-4">
                            <?php foreach ($pdfs as $pdf): ?>
                                <?php $acheté = $academy->hasUserBoughtPdf($user_id, $pdf['id']); ?>
                                <div class="bg-slate-900/50 border border-white/5 rounded-2xl p-5 hover:border-white/10 transition-colors">
                                    <div class="flex gap-4">
                                       
                                        <div class="flex-1 min-w-0">
                                            <div class="font-bold text-white text-sm mb-1 line-clamp-2"><?= htmlspecialchars($pdf['titre']) ?></div>
                                            <?php if ($pdf['description']): ?>
                                                <div class="text-[11px] text-slate-500 mb-4 line-clamp-2 leading-relaxed"><?= htmlspecialchars($pdf['description']) ?></div>
                                            <?php endif; ?>

                                            <div class="flex items-center justify-between mt-auto">
                                                <?php if ($isLocked): ?>
                                                    <span class="text-[9px] font-black uppercase tracking-widest text-amber-500 px-3 py-1 bg-amber-500/10 rounded-lg">Premium</span>
                                                    <a href="https://wari.digiroys.com/paid/index.php" class="text-[10px] font-black uppercase tracking-widest text-slate-900 bg-wari-gold hover:bg-wari-goldLight px-4 py-2 rounded-xl transition-colors">
                                                        Débloquer
                                                    </a>
                                                <?php elseif ($pdf['est_gratuit'] || $acheté): ?>
                                                    <span class="text-[9px] font-black uppercase tracking-widest text-emerald-400 px-3 py-1 bg-emerald-500/10 rounded-lg">Gratuit</span>
                                                    <a href="/academy/pdf_download.php?id=<?= $pdf['id'] ?>" class="text-[10px] font-black uppercase tracking-widest text-white bg-slate-700 hover:bg-slate-600 px-4 py-2 rounded-xl transition-colors">
                                                        Télécharger
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-xs font-black text-wari-gold"><?= number_format($pdf['prix'], 0, ',', ' ') ?> FCFA</span>
                                                    <a href="/academy/pdf_achat.php?id=<?= $pdf['id'] ?>" class="text-[10px] font-black uppercase tracking-widest text-slate-900 bg-wari-gold hover:bg-wari-goldLight px-4 py-2 rounded-xl transition-colors">
                                                        Obtenir
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Auteur -->
                <div class="bento-card rounded-[1rem] p-4">
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mb-6 flex items-center gap-2">
                       <span>Auteur</span>
                        <div class="flex-1 h-[1px] bg-white/5 ml-2"></div>
                    </h3>
                    <div class="flex items-center gap-5 bg-slate-900/50 border border-white/5 rounded-2xl p-5">
                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-wari-goldDark to-wari-gold flex items-center justify-center text-3xl shadow-xl shrink-0">
                            🧑🏾
                        </div>
                        <div>
                            <div class="font-heading font-black text-white text-lg mb-1"><?= htmlspecialchars($course['auteur']) ?></div>
                            <div class="text-[10px] font-bold uppercase tracking-widest text-wari-gold">Coach financier</div>
                        </div>
                    </div>
                </div>

            </aside>

        </div>
    </main>
    
    <footer class="mt-auto py-10 border-t border-white/5 text-center">
        <div class="font-heading text-lg font-black text-wari-gold mb-2 tracking-tighter">Wari Academy.</div>
        <div class="text-[9px] font-bold text-white/30 uppercase tracking-[0.3em]">&copy; <?= date('Y') ?> WARI FINANCE — TOUS DROITS RÉSERVÉS.</div>
    </footer>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
```

### `academy/lesson.php`

```php
<?php
// /var/www/html/academy/lesson.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Academy.php';

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/session_check.php';
$user_id = $_SESSION['user_id'] ?? null;

// ✅ Log de la lecture de leçon
logAuthAttempt($pdo, 'COURSE_READ', $_SESSION['user_email'], $user_id, "Leçon ID: " . ($_GET['id'] ?? 'none'));

$academy = new Academy($pdo);

// Récupération de la leçon
$lesson_id = (int)($_GET['id'] ?? 0);
if (!$lesson_id) {
    header('Location: /academy/');
    exit;
}

$lesson = $academy->getLessonById($lesson_id);
if (!$lesson) {
    header('Location: /academy/');
    exit;
}

$course   = $academy->getCourseById($lesson['course_id']);
if (!$course) {
    header('Location: /academy/');
    exit;
}

// Vérification de sécurité premium
$isLocked = !($_SESSION['is_premium'] ?? false) && ($course['est_gratuit'] == 0 || $course['niveau'] === 'avance');
if ($isLocked) {
    header('Location: /academy/course.php?slug=' . urlencode($course['slug']) . '&error=premium');
    exit;
}
$lessons  = $academy->getLessonsByCourse($lesson['course_id']);
$prevLesson = $academy->getPrevLesson($lesson['course_id'], $lesson['ordre']);
$nextLesson = $academy->getNextLesson($lesson['course_id'], $lesson['ordre']);
$progress   = $academy->getCourseProgress($user_id, $lesson['course_id']);
$isComplete = $academy->isLessonComplete($user_id, $lesson_id);

// Statut de toutes les leçons (pour la sidebar)
foreach ($lessons as &$l) {
    $l['complete'] = $academy->isLessonComplete($user_id, $l['id']);
}
unset($l);

$totalLecons = count($lessons);
$doneLecons  = count(array_filter($lessons, fn($l) => $l['complete']));

// Numéro de la leçon courante
$currentIndex = 0;
foreach ($lessons as $i => $l) {
    if ($l['id'] === $lesson_id) {
        $currentIndex = $i;
        break;
    }
}

// Marquer comme complétée si action POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete') {
    $academy->markLessonComplete($user_id, $lesson_id, $lesson['course_id']);
    // Recalcul
    $isComplete = true;
    $progress   = $academy->getCourseProgress($user_id, $lesson['course_id']);
    $doneLecons = min($doneLecons + 1, $totalLecons);

    // Si leçon suivante → rediriger directement
    if ($nextLesson) {
        header('Location: /academy/lesson.php?id=' . $nextLesson['id']);
        exit;
    } else {
        // Cours terminé → retour à la page du cours
        header('Location: /academy/course.php?slug=' . urlencode($course['slug']) . '&termine=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($lesson['titre']) ?> | Wari Academy</title>

    <link rel="icon" type="image/png" href="../assets/warifinance3d.png" />
    <link rel="apple-touch-icon" href="../assets/warifinance3d.png" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <!-- Outfit (Titres) et Plus Jakarta Sans (Corps) -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                        heading: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        slate: {
                            850: '#151e2e',
                            900: '#0f172a',
                            950: '#020617',
                        },
                        wari: {
                            gold: '#C9A84C',
                            goldLight: '#F0D080',
                            goldDark: '#8B6914',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --wari-gold: #C9A84C;
            --cat-color: <?= htmlspecialchars($course['category_couleur'] ?? '#C9A84C') ?>;
        }
        
        /* Glassmorphism Bento Cards */
        .bento-card {
            background: rgba(30, 41, 59, 0.4); /* slate-800 with opacity */
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 1rem;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .bento-card-highlight {
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.9));
            /* border-top: 4px solid var(--cat-color); */
        }

        /* Variables CSS pour les éléments générés depuis la BDD */
        .lesson-content {
            font-size: 1.05rem;
            line-height: 1.8;
            color: #cbd5e1; /* text-slate-300 */
        }
        .lesson-content h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.75rem;
            font-weight: 800;
            color: #ffffff;
            margin-top: 2.5rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding-bottom: 0.5rem;
        }
        .lesson-content h3 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.25rem;
            font-weight: 700;
            color: #e2e8f0;
            margin-top: 2rem;
            margin-bottom: 0.75rem;
        }
        .lesson-content p {
            margin-bottom: 1.25rem;
            color: #cbd5e1;
        }
        .lesson-content ul, .lesson-content ol {
            padding-left: 1.5rem;
            margin-bottom: 1.25rem;
            color: #cbd5e1;
        }
        .lesson-content ul { list-style-type: disc; }
        .lesson-content ol { list-style-type: decimal; }
        .lesson-content li { margin-bottom: 0.5rem; }
        .lesson-content strong { color: #f8fafc; font-weight: 700; }
        .lesson-content em { font-style: italic; color: #94a3b8; }
        .lesson-content blockquote {
            border-left: 4px solid var(--wari-gold);
            background: rgba(201, 168, 76, 0.08);
            padding: 1.25rem 1.5rem;
            border-radius: 0 1rem 1rem 0;
            margin: 2rem 0;
            font-style: italic;
            color: #e2e8f0;
            font-size: 1.1rem;
        }
        .lesson-content .encadre {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(201, 168, 76, 0.2);
            border-radius: 1.25rem;
            padding: 1.5rem 1.75rem;
            margin: 2rem 0;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .lesson-content .encadre-titre {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 0.9rem;
            color: var(--wari-gold);
            margin-bottom: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        .lesson-content a {
            color: var(--wari-gold);
            text-decoration: underline;
            text-underline-offset: 4px;
            transition: color 0.2s;
        }
        .lesson-content a:hover {
            color: #ffffff;
        }
        .lesson-content img {
            max-width: 100%;
            height: auto;
            border-radius: 1rem;
            margin: 2rem auto;
            display: block;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255,255,255,0.05);
        }

        /* Video Wrapper */
        .video-wrap {
            position: relative;
            width: 100%;
            aspect-ratio: 16/9;
            background: #000;
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .video-wrap iframe {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            border: none;
        }
        
    </style>
</head>

<body class="bg-slate-950 text-slate-300 font-sans antialiased min-h-screen flex flex-col selection:bg-wari-gold selection:text-slate-950">

    <!-- ── NAVIGATION ── -->
    <nav class="sticky top-0 z-50 bg-slate-950/80 backdrop-blur-md h-20 flex flex-col justify-center px-2 md:px-4">
        <div class="flex items-center justify-between gap-4 md:gap-8 w-full">
            
            <a href="/academy/" class="font-heading text-xl md:text-2xl font-black text-wari-gold tracking-tight shrink-0">
                Wari<span class="font-light text-white">Academy</span>
            </a>

            <!-- Progress Bar inside Nav -->
            <div class="hidden md:flex flex-col flex-1 max-w-sm ml-auto mr-auto">
                <div class="flex justify-between items-center text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">
                    <span class="truncate pr-4">Cours : <?= htmlspecialchars($course['titre']) ?></span>
                    <span class="text-wari-gold shrink-0"><?= $progress ?>%</span>
                </div>
                <div class="h-1.5 w-full bg-slate-800 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-wari-goldDark to-wari-gold rounded-full transition-all duration-700" style="width:<?= $progress ?>%"></div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="https://wari.digiroys.com" class="shrink-0 flex items-center gap-2 bg-slate-900 border border-slate-700 px-3 py-2 rounded-xl text-[9px] font-bold uppercase tracking-widest text-slate-400">
                    ← App
                </a>
                <a href="/academy/course.php?slug=<?= urlencode($course['slug']) ?>" class="shrink-0 flex items-center gap-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 px-3 py-2 rounded-xl transition-all text-[9px] font-bold uppercase tracking-widest text-slate-300 hover:text-white">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Sommaire
                </a>
            </div>
        </div>
        <!-- Mobile progress bar (under nav elements) -->
        <div class="md:hidden w-full mt-1 flex flex-col">
            <div class="flex justify-between text-[9px] font-bold uppercase tracking-widest text-slate-400 mb-1">
                <span class="truncate">Progression</span>
                <span class="text-wari-gold"><?= $progress ?>%</span>
            </div>
            <div class="h-1 w-full bg-slate-800 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-wari-goldDark to-wari-gold rounded-full" style="width:<?= $progress ?>%"></div>
            </div>
        </div>
    </nav>

    <!-- ── LAYOUT PRINCIPAL ── -->
    <div class="flex-1 w-full max-w-[1400px] mx-auto px-2 md:px-4 py-8 md:py-6 grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <!-- ── MAIN (Gauche) ── -->
        <main class="lg:col-span-8 flex flex-col gap-8 w-full max-w-4xl mx-auto">

            <!-- En-tête leçon (Bento Card) -->
            <header class="bento-card bento-card-highlight p-2 md:p-4 relative overflow-hidden">
                <!-- Decorative glares -->
                <!-- <div class="absolute -top-32 -right-32 w-64 h-64 bg-[var(--cat-color)] rounded-full mix-blend-multiply opacity-20 blur-3xl"></div> -->
                
                <div class="relative z-10">
                    <!-- Breadcrumbs -->
                    <div class="flex flex-wrap items-center gap-2 text-xs md:text-sm font-medium text-slate-400 mb-6">
                        <a href="/academy/" class="hover:text-white transition-colors">Academy</a>
                        <span>/</span>
                        <a href="/academy/course.php?slug=<?= urlencode($course['slug']) ?>" class="hover:text-white transition-colors">
                            <?= htmlspecialchars($course['titre']) ?>
                        </a>
                        <span>/</span>
                        <strong class="text-wari-goldLight truncate max-w-[150px] sm:max-w-none"><?= htmlspecialchars($lesson['titre']) ?></strong>
                    </div>

                    <!-- Lesson Badge -->
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest mb-6 border border-white/10 bg-black/40 shadow-inner">
                        <span class="w-2 h-2 rounded-full bg-[var(--cat-color)] shadow-[0_0_10px_var(--cat-color)] animate-pulse"></span>
                        Leçon <?= str_pad($currentIndex + 1, 2, '0', STR_PAD_LEFT) ?> sur <?= str_pad($totalLecons, 2, '0', STR_PAD_LEFT) ?>
                    </div>

                    <!-- Title -->
                    <h1 class="font-heading text-3xl md:text-5xl font-black text-white leading-[1.15] mb-6">
                        <?= htmlspecialchars($lesson['titre']) ?>
                    </h1>

                    <!-- Meta Tags -->
                    <div class="flex flex-wrap items-center gap-4 text-xs font-bold uppercase tracking-widest text-slate-400">
                        <?php // $typesIcon = ['texte', 'video', 'quiz']; ?>
                        <div class="flex items-center gap-2 bg-slate-900/60 px-4 py-2 rounded-xl border border-white/5">
                            <!--  -->
                            <span class="text-white"><?= ucfirst($lesson['type'] ?: 'Lecture') ?></span>
                        </div>
                        <div class="flex items-center gap-2 bg-slate-900/60 px-4 py-2 rounded-xl border border-white/5">
                            <span class="text-white"><?= htmlspecialchars($course['auteur']) ?></span>
                        </div>
                        
                        <?php if ($isComplete): ?>
                            <div class="flex items-center gap-2 bg-emerald-500/10 px-4 py-2 rounded-xl border border-emerald-500/20 text-emerald-400 ml-auto md:ml-0">
                                Validé
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <!-- Video Section -->
            <?php if ($lesson['type'] === 'video' && $lesson['video_url']): ?>
                <div class="video-wrap group">
                    <!-- Overlay gradient for extra style until interact -->
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-transparent to-transparent opacity-50 pointer-events-none group-hover:opacity-0 transition-opacity duration-500 z-10"></div>
                    <iframe src="<?= htmlspecialchars($lesson['video_url']) ?>" allowfullscreen loading="lazy"></iframe>
                </div>
            <?php endif; ?>

            <!-- Contenu Texte -->
            <?php if (!empty(trim($lesson['contenu']))): ?>
                <article class="bento-card p-2 md:p-4">
                    <div class="lesson-content">
                        <?= $lesson['contenu'] ?>
                    </div>
                </article>
            <?php endif; ?>

            <!-- Complete Action block -->
            <div class="bento-card p-2 md:p-4 text-center relative overflow-hidden hover:border-t-wari-gold transition-colors">
                <div class="absolute inset-0 bg-gradient-to-b from-wari-gold/5 to-transparent opacity-50"></div>
                
                <div class="relative z-10 flex flex-col items-center">
                    <?php if ($isComplete): ?>
                        <div class="w-16 h-16 bg-emerald-500/10 text-emerald-400 rounded-full flex items-center justify-center text-3xl mb-4 border border-emerald-500/30">✅</div>
                        <h3 class="font-heading text-xl font-bold text-white mb-2">Leçon validée !</h3>
                        <p class="text-slate-400 text-sm mb-8">Excellent travail. Vous avez déjà accompli cette étape.</p>
                        
                        <?php if ($nextLesson): ?>
                            <a href="/academy/lesson.php?id=<?= $nextLesson['id'] ?>" class="inline-flex items-center gap-3 bg-wari-gold hover:bg-wari-goldLight text-slate-900 font-black text-sm uppercase tracking-widest px-8 py-4 rounded-xl transition-all shadow-lg shadow-wari-gold/20 transform hover:-translate-y-1">
                                Étape suivante →
                            </a>
                        <?php else: ?>
                            <a href="/academy/course.php?slug=<?= urlencode($course['slug']) ?>" class="inline-flex items-center gap-3 bg-emerald-500 text-white font-black text-sm uppercase tracking-widest px-8 py-4 rounded-xl transition-all shadow-lg shadow-emerald-500/20 transform hover:-translate-y-1">
                                🏆 Terminer le module
                            </a>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="w-16 h-16 bg-white/5 text-wari-gold rounded-full flex items-center justify-center text-3xl mb-4 border border-white/10">🎓</div>
                        <h3 class="font-heading text-2xl font-black text-white mb-2">Avez-vous compris l'essentiel ?</h3>
                        <p class="text-slate-400 text-sm mb-8 max-w-md">Validez cette leçon pour enregistrer votre progression et débloquer la suite de la masterclass.</p>

                        <form method="POST" class="w-full sm:w-auto">
                            <input type="hidden" name="action" value="complete">
                            <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-3 bg-wari-gold hover:bg-wari-goldLight text-slate-900 font-black text-sm uppercase tracking-widest px-8 py-4 rounded-xl transition-all shadow-lg shadow-wari-gold/20 transform hover:scale-105 active:scale-95">
                                Valider la leçon
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pre/Next Navigations Bottom -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Précédente -->
                <?php if ($prevLesson): ?>
                    <a href="/academy/lesson.php?id=<?= $prevLesson['id'] ?>" class="group bento-card p-6 flex items-center gap-4 hover:-translate-x-1 transition-all">
                        <div class="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center text-wari-gold group-hover:bg-wari-gold/20 transition-colors shrink-0">
                            ←
                        </div>
                        <div class="min-w-0">
                            <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Leçon précédente</div>
                            <div class="font-bold text-white text-sm truncate"><?= htmlspecialchars($prevLesson['titre']) ?></div>
                        </div>
                    </a>
                <?php else: ?>
                    <div class="bento-card p-6 flex items-center gap-4 opacity-50 cursor-not-allowed">
                        <div class="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center text-slate-600 shrink-0">←</div>
                        <div>
                            <div class="text-[10px] font-black text-slate-600 uppercase tracking-widest mb-1.5">Début du cours</div>
                            <div class="font-bold text-slate-500 text-sm">Première leçon</div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Suivante -->
                <?php if ($nextLesson): ?>
                    <a href="/academy/lesson.php?id=<?= $nextLesson['id'] ?>" class="group bento-card p-6 flex items-center gap-4 text-right hover:translate-x-1 transition-all flex-row-reverse">
                        <div class="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center text-wari-gold group-hover:bg-wari-gold/20 transition-colors shrink-0">
                            →
                        </div>
                        <div class="min-w-0">
                            <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Leçon suivante</div>
                            <div class="font-bold text-white text-sm truncate"><?= htmlspecialchars($nextLesson['titre']) ?></div>
                        </div>
                    </a>
                <?php else: ?>
                    <a href="/academy/course.php?slug=<?= urlencode($course['slug']) ?>" class="group bento-card border-emerald-500/20 bg-emerald-500/5 p-6 flex items-center gap-4 text-right hover:-translate-y-1 transition-all flex-row-reverse">
                        <div class="w-12 h-12 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-400 shrink-0">
                            🏁
                        </div>
                        <div class="min-w-0">
                            <div class="text-[10px] font-black text-emerald-500/70 uppercase tracking-widest mb-1.5">Fin du cours</div>
                            <div class="font-bold text-emerald-400 text-sm">Retour au sommaire</div>
                        </div>
                    </a>
                <?php endif; ?>
            </div>

        </main>

        <!-- ── SIDEBAR (Droite) ── -->
        <aside class="lg:col-span-4 w-full flex flex-col gap-6 sticky top-28">

            <!-- Progression block -->
            <div class="bento-card p-3 rounded-[1rem]">
                <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mb-6 flex items-center gap-2">
                    <span>Global</span>
                    <div class="flex-1 h-[1px] bg-white/5 ml-2"></div>
                </h3>
                
                <div class="flex justify-between items-end mb-3">
                    <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Progression</div>
                    <div class="text-3xl font-heading font-black text-wari-gold"><?= $progress ?>%</div>
                </div>
                
                <div class="h-2 w-full bg-slate-800 rounded-full overflow-hidden mb-4">
                    <div class="h-full bg-gradient-to-r from-wari-goldDark to-wari-gold rounded-full transition-all duration-1000 relative" style="width:<?= $progress ?>%">
                        <div class="absolute inset-0 bg-white/20 animate-pulse"></div>
                    </div>
                </div>
                <div class="text-[10px] font-bold uppercase tracking-widest text-slate-500 text-center">
                    <?= $doneLecons ?> / <?= $totalLecons ?> terminées
                </div>
            </div>

            <!-- List of lessons -->
            <div class="bento-card rounded-[1rem] overflow-hidden flex flex-col max-h-[600px]">
                <div class="p-3 border-b border-white/5 shrink-0 bg-slate-900/40">
                    <h3 class="text-[10px] font-black text-white uppercase tracking-[0.3em] flex items-center gap-2 mb-1">
                        <span>Plan d'action</span>
                    </h3>
                    <div class="text-xs text-slate-500 font-medium"><?= htmlspecialchars($course['titre']) ?></div>
                </div>
                
                <!-- Scrollable list -->
                <div class="overflow-y-auto overflow-x-hidden flex-1 divide-y divide-white/5 custom-scrollbar">
                    <?php foreach ($lessons as $i => $l): ?>
                        <?php
                        $isActive = $l['id'] === $lesson_id;
                        $isDone   = $l['complete'];
                        ?>
                        <a href="/academy/lesson.php?id=<?= $l['id'] ?>" class="group p-4 flex items-center gap-3 transition-colors <?= $isActive ? 'bg-slate-800/60 border-l-4 border-[var(--cat-color)]' : 'hover:bg-slate-800/30 border-l-4 border-transparent' ?>">
                            
                            <!-- Icon/Number -->
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black shrink-0 transition-colors <?= $isDone ? 'bg-emerald-500/10 text-emerald-400' : ($isActive ? 'bg-[var(--cat-color)] text-slate-900' : 'bg-slate-800 text-slate-500 group-hover:bg-slate-700') ?>">
                                <?= $isDone ? '✓' : str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?>
                            </div>
                            
                            <!-- title -->
                            <div class="text-sm font-semibold line-clamp-2 <?= $isDone ? 'text-slate-500 line-through decoration-slate-600/50' : ($isActive ? 'text-white' : 'text-slate-300 group-hover:text-white') ?>">
                                <?= htmlspecialchars($l['titre']) ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <a href="/academy/course.php?slug=<?= urlencode($course['slug']) ?>" class="w-full text-center p-4 rounded-xl border border-white/10 text-slate-400 hover:text-white hover:bg-white/5 transition-colors text-xs font-bold uppercase tracking-widest">
                ← Quitter la leçon
            </a>

        </aside>

    </div>

    <!-- Scrollbar pour la sidebar -->
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: rgba(15, 23, 42, 0.5); }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.2); }
    </style>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
```

### `index.php`

```php
<?php
require_once __DIR__ . '/wari_monitoring.php';  // ← TOUJOURS EN PREMIER
// Configuration session 90 jours avant tout output
require 'config/session_config.php'; // Charge la config 90 jours
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
require 'config/db.php'; // <--- INDISPENSABLE : Pour que $pdo fonctionne !
require_once __DIR__ . '/classes/Academy.php';
require_once __DIR__ . '/classes/Vecu.php';

require_once __DIR__ . '/config/session_check.php';

$academy = new Academy($pdo);
$unfinishedCoursesCount = $academy->getUnfinishedCoursesCount($_SESSION['user_id']);

$vecu = new Vecu($pdo);
$unreadVecuCount = $vecu->getUnreadCount($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wari-Finance | Gestion Budget & Objectifs Financiers</title>
    <meta name="description" content="Avec Wari, chaque franc a un rôle. Planifie, contrôle et fais grandir ton argent directement depuis ton téléphone.">

    <meta name="keywords" content="Wari Finance, gestion budget, épargne, finance personnelle, Afrique, licence pro">
    <meta name="author" content="Digiroys">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Quicksand', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Open Graph (Facebook, WhatsApp, LinkedIn) -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Wari-Finance - Gère ton argent sans stress">
    <meta property="og:description" content="Budget, objectifs, conseils simples pour maîtriser tes finances au quotidien. Application gratuite.">
    <meta property="og:url" content="https://wari.digiroys.com/accueil/">
    <meta property="og:image" content="https://wari.digiroys.com/assets/wari_og_1.png">
    <meta property="og:locale" content="fr_FR">

    <link rel="icon" type="image/png" href="./assets/warifinance3d.png" />
    <link rel="apple-touch-icon" href="./assets/warifinance3d.png">

    <link rel="stylesheet" href="./assets/styles.css?v=137">

    <link rel="manifest" href="manifest.json">
    <meta id="metaThemeColor" name="theme-color" content="#000000">

    <script src="https://stats.digiroys.com/tracker.js" data-key="key_wari_789"></script>
    <script>
        <?php if (isset($_SESSION['user_email'])): ?>
            // On identifie l'utilisateur pour TOUTES ses actions sur le dashboard
            DigiStats.identify("<?= $_SESSION['user_email'] ?>");
        <?php endif; ?>
    </script>

    <script>
        // Initialisation immédiate du thème
        const savedTheme = localStorage.getItem('wari_theme') || 'dark';
        if (savedTheme === 'light') {
            document.documentElement.classList.add('light-mode');
            document.addEventListener('DOMContentLoaded', () => {
                document.body.classList.add('light-mode');
                const metaThemeColor = document.getElementById('metaThemeColor');
                if (metaThemeColor) metaThemeColor.setAttribute('content', '#f1f5f9');
            });
        }

        function toggleTheme() {
            const isLight = document.body.classList.toggle('light-mode');
            document.documentElement.classList.toggle('light-mode', isLight);
            localStorage.setItem('wari_theme', isLight ? 'light' : 'dark');
            const metaThemeColor = document.getElementById('metaThemeColor');
            if (metaThemeColor) {
                metaThemeColor.setAttribute('content', isLight ? '#f1f5f9' : '#000000');
            }
            updateThemeButton();
        }

        function updateThemeButton() {
            const isLight = document.documentElement.classList.contains('light-mode');
            const metaThemeColor = document.getElementById('metaThemeColor');
            if (metaThemeColor) {
                metaThemeColor.setAttribute('content', isLight ? '#f1f5f9' : '#000000');
            }
            const themeIcon = document.getElementById('themeIcon');
            const themeToggleBtn = document.getElementById('themeToggleBtn');
            const sunIcon = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>`;
            const moonIcon = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>`;
            
            if (themeIcon) {
                themeIcon.innerHTML = isLight ? sunIcon : moonIcon;
                themeIcon.className = isLight ? "text-slate-800" : "text-slate-400";
            }
            if (themeToggleBtn) {
                themeToggleBtn.className = isLight 
                    ? "w-10 h-10 rounded-2xl flex items-center justify-center active:scale-95 transition-all duration-300 bg-slate-200 hover:bg-slate-300 border border-slate-300/50"
                    : "w-10 h-10 rounded-2xl flex items-center justify-center active:scale-95 transition-all duration-300 bg-white/5 hover:bg-white/10 border border-white/5";
            }

            // Mettre à jour les boutons dans les modals
            const modalIcons = document.querySelectorAll('.modalThemeIcon');
            modalIcons.forEach(icon => {
                icon.innerHTML = isLight ? sunIcon : moonIcon;
                icon.className = isLight ? "modalThemeIcon text-slate-800" : "modalThemeIcon text-slate-400";
            });

            const modalButtons = document.querySelectorAll('.modalThemeToggleBtn');
            modalButtons.forEach(btn => {
                btn.className = isLight
                    ? "modalThemeToggleBtn w-9 h-9 flex items-center justify-center rounded-xl active:scale-95 transition-all duration-300 bg-slate-200 hover:bg-slate-300 border border-slate-300/50"
                    : "modalThemeToggleBtn w-9 h-9 flex items-center justify-center rounded-xl active:scale-95 transition-all duration-300 bg-white/5 hover:bg-white/10 border border-white/5";
            });
        }

        document.addEventListener('DOMContentLoaded', updateThemeButton);
    </script>

</head>

<body class="p-3 pb-20">

    <div class="max-w-md mx-auto">
        <header class="flex items-center justify-between mb-4">
            <div class="flex flex-col">
                <h1 class="text-3xl font-black tracking-tighter text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-yellow-600">
                    WARI - Finance
                </h1>
                <p class="text-[8px] font-bold uppercase tracking-[0.3em] text-transparent bg-clip-text bg-gradient-to-r from-slate-400 to-slate-600">Discipline | Liberté | Suivis</p>
                <div class="flex items-center gap-2 mt-0.5">
                    <span id="liveClock" class="text-[9px] font-bold tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-amber-400/80 to-yellow-600/60"></span>
                    <span id="offlineBadge" class="hidden text-[8px] font-black uppercase tracking-widest bg-red-500/20 text-red-400 px-1.5 py-0.5 rounded-sm border border-red-500/30">Hors Ligne ⚡</span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <!-- Bouton Guide -->
                <a href="guide/index.php" title="Guide d'utilisation"
                    class="w-10 h-10 rounded-2xl flex items-center justify-center active:scale-95 transition-all duration-300 bg-white/5 hover:bg-amber-500/10 border border-white/5 hover:border-amber-500/30">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-amber-500"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                </a>

                <!-- Bouton de bascule de thème -->
                <button id="themeToggleBtn" onclick="toggleTheme()" title="Changer le thème"
                    class="w-10 h-10 rounded-2xl flex items-center justify-center active:scale-95 transition-all duration-300 bg-white/5 hover:bg-white/10 border border-white/5">
                    <span id="themeIcon" class="text-slate-400"></span>
                </button>

                <!-- ✅ BOUTON DÉCONNEXION / SORTIR -->
                <a href="config/logout.php" title="Se déconnecter"
                    class="w-10 h-10 rounded-2xl flex items-center justify-center active:scale-95 transition-all duration-300 group bg-white/5 hover:bg-red-500/10 border border-white/5 hover:border-red-500/20 shadow-md">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" class="text-slate-400 group-hover:text-red-400 transition-colors" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17 16L21 12M21 12L17 8M21 12H9M13 16V17C13 18.1046 12.1046 19 11 19H5C3.89543 19 3 18.1046 3 17V7C3 5.89543 3.89543 5 5 5H11C12.1046 5 13 5.89543 13 7V8" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
            </div>
        </header>

        <!-- Sélecteur de Portefeuille : Perso / Pro -->
        <div class="flex justify-center mb-4">
            <div class="bg-slate-900/80 p-0.5 rounded-2xl border border-white/5 flex gap-1 shadow-inner select-none">
                <button id="wallet-btn-perso" onclick="switchWallet('perso')" class="px-4 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all duration-300 bg-gradient-to-r from-yellow-400 to-yellow-600 text-slate-950 shadow-md">
                    Personnel
                </button>
                <button id="wallet-btn-pro" onclick="switchWallet('pro')" class="px-4 py-1.5 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all duration-300 text-slate-400 hover:text-white flex items-center gap-1.5">
                    Professionnel
                    <?php if (!isset($_SESSION['is_premium']) || !$_SESSION['is_premium']): ?>
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="text-amber-500"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    <?php endif; ?>
                </button>
            </div>
        </div>

        <!-- Jauge de Santé Financière -->
        <section id="gauge-section" class="glass-card p-3 mb-4">
            <div class="flex justify-between items-start mb-2">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <h3 class="text-[11px] uppercase tracking-widest text-emerald-400 font-bold">Santé financière</h3>
                        <?php if (isset($_SESSION['is_premium']) && $_SESSION['is_premium']): ?>
                            <span class="text-[7.5px] font-black uppercase tracking-widest bg-amber-500/20 text-amber-400 border border-amber-500/30 px-1.5 py-0.5 rounded-full select-none animate-pulse">Pro</span>
                        <?php endif; ?>
                        <div id="radarStatusContainer" class="flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-slate-900/50 border border-white/5 cursor-pointer active:scale-95 transition-all" onclick="subscribeUserToPush(true)">
                            <div id="radarDot" class="w-1.5 h-1.5 rounded-full bg-slate-600"></div>
                            <span id="radarText" class="text-[7px] font-black uppercase tracking-widest text-slate-500">Radar OFF</span>
                        </div>
                    </div>
                </div>
                
                <!-- Bouton d'exportation PDF (Premium) -->
                <button onclick="exportFinancialReport()" class="flex items-center gap-1 px-2.5 py-1 rounded-xl bg-slate-900 hover:bg-slate-800 border border-white/5 hover:border-amber-500/30 text-slate-400 hover:text-white text-[9px] font-black uppercase tracking-wider transition-all duration-300 active:scale-95 shadow-md">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-amber-500">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    Bilan
                </button>
            </div>

            

            <?php if (isset($_SESSION['is_premium']) && $_SESSION['is_premium']): ?>
            <!-- Tabs pour choisir le type de graphique -->
            <div class="flex justify-center gap-1.5 mb-3 bg-slate-900/60 p-1 rounded-xl border border-white/5">
                <button onclick="switchPremiumChart('trend')" id="btnChartTrend" class="flex-1 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-wider text-white bg-slate-800 transition-all select-none">
                    Evolution
                </button>
                <button onclick="switchPremiumChart('savings')" id="btnChartSavings" class="flex-1 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-wider text-slate-400 hover:text-white transition-all select-none">
                    Epargne %
                </button>
                <button onclick="switchPremiumChart('donut')" id="btnChartDonut" class="flex-1 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-wider text-slate-400 hover:text-white transition-all select-none">
                    Repartition
                </button>
            </div>
            <?php endif; ?>

            <!-- Zone du Graphique Évolution -->
            <div class="relative w-full h-[140px] p-1 flex items-center justify-center mb-2">
                <div id="chartLoader" class="absolute text-slate-500 text-[10px] italic">Chargement du graphique...</div>
                <svg id="trendChartSvg" class="w-full h-full opacity-0 transition-opacity duration-500" viewBox="0 0 400 140"></svg>
            </div>
            
            <!-- Légende du Graphique -->
            <div id="chartLegend" class="flex justify-center gap-4 select-none">
                <div class="flex items-center gap-1.5 text-[8.5px] font-bold text-slate-400 uppercase tracking-wider">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-[0_0_6px_rgba(16,185,129,0.5)]"></span>
                    Revenus
                </div>
                <div class="flex items-center gap-1.5 text-[8.5px] font-bold text-slate-400 uppercase tracking-wider">
                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 shadow-[0_0_6px_rgba(239,68,68,0.5)]"></span>
                    Dépenses
                </div>
            </div>
        </section>

        <div id="bankPocketSection" class="grid grid-cols-2 gap-3 mb-4">
            <!-- BANQUE : Effet Blur pour se concentrer sur l'actif de croissance -->
            <div class="glass-card p-3 cursor-pointer active:scale-95 transition-all group" 
                 onclick="const el = this.querySelector('#bankAmount'); el.classList.toggle('blur-[6px]'); el.classList.toggle('opacity-30'); el.classList.toggle('opacity-100');">
                <div class="flex justify-between items-start mb-1">
                    <div class="flex items-center gap-1.5">
                        <p class="text-[8px] uppercase tracking-widest text-slate-500 font-black">Banque (Réserves)</p>
                        <button onclick="event.stopPropagation(); openHelpModal('coffre-fort')" class="w-3.5 h-3.5 rounded-full border border-slate-600 text-slate-500 flex items-center justify-center text-[7px] font-bold hover:bg-slate-800 hover:text-white transition-colors" title="C'est quoi ?">?</button>
                    </div>
                    <span class="opacity-0 group-hover:opacity-100 transition-opacity">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </span>
                </div>
                <p id="bankAmount" class="text-lg font-black text-white blur-[6px] opacity-30 transition-all duration-500 select-none">0 F</p>
                <p class="text-[7px] text-slate-600 mt-1 uppercase tracking-wider leading-tight">Liberté de Sécurité</p>
            </div>

            <!-- POCHE : Reste en clair pour les dépenses quotidiennes -->
            <div class="glass-card p-3">
                <div class="flex items-center gap-1.5 mb-1">
                    <p class="text-[8px] uppercase tracking-widest text-slate-500 font-black">Poche (Dispo)</p>
                    <button onclick="openHelpModal('train-de-vie')" class="w-3.5 h-3.5 rounded-full border border-slate-600 text-slate-500 flex items-center justify-center text-[7px] font-bold hover:bg-slate-800 hover:text-white transition-colors" title="C'est quoi ?">?</button>
                </div>
                <p id="cashAmount" class="text-lg font-black text-emerald-400">0 F</p>
                <p class="text-[7px] text-slate-600 mt-1 uppercase tracking-wider leading-tight text-left">Dispo (Vie + Imprévus)</p>
            </div>
        </div>

        <!-- Section insertion du montant a repartire -->
        <div class="glass-card gold-border p-3 mb-4 shadow-2xl relative">
            <div class="flex justify-between items-center mb-3">
                <label class="block text-[11px] uppercase tracking-[0.2em] text-yellow-500 font-bold">Montant à répartir</label>
                <select id="currencySelector" onchange="render()" class="bg-slate-800 text-yellow-500 text-xs font-bold rounded px-1 py-1 outline-none">
                    <option value="F">CFA</option>
                    <option value="$">USD ($)</option>
                    <option value="€">EUR (€)</option>
                </select>
            </div>
            <div class="flex items-end border-b-2 border-slate-700 pb-2 focus-within:border-emerald-500 transition-colors">
                <!-- Champs cachés pour capturer et bloquer l'autofill Google/Samsung Pass -->
                <input type="text" style="display:none;" autocomplete="new-password">
                <input type="password" style="display:none;" autocomplete="new-password">
                
                <input type="number" id="mainAmount" name="amount" placeholder="0" onfocus="this.select()"
                    autocomplete="off" inputmode="numeric" pattern="[0-9]*" autocorrect="off" spellcheck="false"
                    class="bg-transparent text-4xl w-full font-extrabold outline-none text-white">
                <span id="currentSymbol" class="text-xl font-bold text-slate-500 ml-2">F</span>
            </div>
        </div>

        <!-- Section de contrôle de l'édition -->
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xs font-bold uppercase tracking-widest text-slate-500">Répartition</h3>
            
            <div class="flex items-center gap-2">
                <button id="lockBtn" onclick="toggleEditMode()"
                    class="flex items-center gap-1 px-3 py-1 rounded-full bg-slate-900 border border-slate-700 transition-all active:scale-95 shadow-lg">
                    <span class="text-[11px] font-black uppercase tracking-[0.1em] text-slate-400">Lecture</span>
                </button>
                
                <button id="focusModeBtn" onclick="toggleFocusMode()" 
                    class="flex items-center gap-1 px-3 py-1 rounded-full bg-slate-800 border border-slate-700 transition-all active:scale-95 shadow-lg">
                    <span id="focusIcon"></span>
                    <span id="focusText" class="text-slate-400 text-[11px] font-bold uppercase tracking-wider">FOCUS</span>
                </button>
            </div>
        </div>

        <!-- Conteneur des catégories -->
        <div id="categoryContainer" class="grid grid-cols-2 gap-3 mb-6">
        </div>

        <!-- Section Juge barre de % -->
        <div id="statusIndicator" class="mt-4 flex items-center justify-center space-x-2 p-3 rounded-2xl transition-all duration-500">
            <div id="statusIcon"></div>
            <span id="statusText" class="font-bold text-sm uppercase tracking-wider"></span>
        </div>

        <!-- Section Versement a la banque (capital investir)-->
        <div id="projectVault" class="mt-4 glass-card p-3 relative overflow-hidden group border-none shadow-2xl">
            <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-500/5 rounded-full blur-3xl group-hover:bg-emerald-500/15 transition-all duration-1000"></div>

            <div class="flex items-center justify-between mb-3 relative z-10">
                <div>
                    <h3 class="text-[10px] uppercase tracking-[0.1em] text-emerald-400 font-black leading-none">Liberté d'Action</h3>
                    <p class="text-[9px] text-slate-500 font-medium mt-0.5">Plantée à chaque répartition</p>
                </div>
                <div class="text-right">
                    <div class="flex items-baseline justify-end gap-1 leading-none">
                        <span id="totalProjectSaved" class="text-2xl font-black text-white tracking-tighter">0</span>
                    </div>
                    <div class="flex items-center justify-end gap-1 opacity-90">
                        <span class="text-[7px] uppercase tracking-[0.1em] text-slate-500 font-black">Liberté :</span>
                        <span id="totalGlobalAmount" class="text-[9px] font-black text-emerald-400">0</span>
                    </div>
                </div>
            </div>

            <div class="relative mb-3">
                <div class="flex justify-between items-center mb-1 px-0.5">
                    <span id="vaultGoalAmountDisplay" class="text-[8px] font-bold text-emerald-500/80 tracking-widest uppercase">Objectif: --</span>
                </div>
                <div class="relative w-full h-1.5 bg-slate-900/60 rounded-full p-[1px] border border-white/5 shadow-inner">
                    <div id="vaultProgress" class="h-full bg-gradient-to-r from-emerald-600 via-emerald-400 to-teal-300 rounded-full transition-all duration-1000" style="width: 0%"></div>
                </div>
            </div>

            <div class="bg-slate-900/40 px-3 py-1.5 rounded-xl border border-slate-800/50 flex items-center justify-between gap-3">
                <div class="flex flex-col">
                    <p class="text-[7px] uppercase tracking-widest text-slate-500 font-bold">Cible</p>
                    <p id="vaultGoalLabel" class="text-[10px] text-white font-black truncate max-w-[100px]">Définir</p>
                </div>
                <div class="flex gap-2">
                    <button onclick="openGoalModal()" class="text-emerald-500 active:scale-90 transition-transform">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    </button>
                    <button id="deleteGoalBtn" onclick="deleteGoal()" class="text-red-400/70 text-[10px] hidden">✕</button>
                </div>
            </div>

            <div class="mt-3 pt-2 border-t border-slate-800/40">
                <div class="flex justify-between items-center mb-1">
                    <p class="text-[7px] uppercase tracking-[0.2em] text-slate-500 font-bold">Historique</p>
                    <button onclick="window.toggleVaultHistory()" id="toggleHistBtn" class="text-[8px] text-slate-400 font-black uppercase tracking-widest">Détails</button>
                </div>
                <div id="vaultHistory" class="max-h-8 overflow-hidden transition-all duration-500">
                    <p class="text-[8px] text-slate-600 italic text-center py-1">Aucun mouvement</p>
                </div>
            </div>
        </div>

        <script>
            let isHistoryExpanded = false;

            window.toggleVaultHistory = function() {
                const container = document.getElementById("vaultHistory");
                isHistoryExpanded = !isHistoryExpanded;

                if (isHistoryExpanded) {
                    container.style.maxHeight = "400px"; // On déplie
                    container.style.overflowY = "auto";
                } else {
                    container.style.maxHeight = "96px"; // On réduit (environ 3 lignes)
                    container.style.overflowY = "hidden";
                    container.scrollTop = 0;
                }
            };
        </script>

        <?php if (isset($_SESSION['is_premium']) && $_SESSION['is_premium']): ?>
        <!-- Section Défis d'Épargne -->
        <div id="challengesSection" class="mt-4 glass-card p-3 shadow-2xl relative">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-[11px] uppercase tracking-[0.1em] text-indigo-400 font-bold">Défis d'Épargne</h3>
                <span id="completedChallengesCountDisplay" class="text-[9px] font-black uppercase tracking-widest bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 px-2.5 py-0.5 rounded-full select-none">
                    0 réussi
                </span>
            </div>
            <div id="challengesContent" class="space-y-4">
                <!-- Rendu dynamique par JS -->
                <p class="text-slate-500 text-[11px] italic text-center">Chargement des défis...</p>
            </div>
        </div>
        <?php else: ?>
        <!-- Version Lock Premium pour les défis -->
        <div class="mt-4 glass-card p-3 shadow-2xl relative overflow-hidden border border-indigo-500/20">
            <div class="absolute -right-10 -top-10 w-24 h-24 bg-indigo-500/10 rounded-full blur-2xl"></div>
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-[11px] uppercase tracking-[0.1em] text-slate-400 font-bold">Défis d'Épargne</h3>
                <span class="text-[7.5px] font-black uppercase tracking-widest bg-amber-500/20 text-amber-400 border border-amber-500/30 px-1.5 py-0.5 rounded-full">PREMIUM</span>
            </div>
            <div class="text-center py-4 relative z-10">
                <svg class="mx-auto text-slate-500 mb-2" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <p class="text-white font-bold text-xs mb-1">Défis d'Épargne Interactifs</p>
                <p class="text-slate-400 text-[9px] max-w-[220px] mx-auto mb-3">Activez Wari Premium pour relever des défis ludiques d'épargne et booster votre discipline financière.</p>
                <a href="https://wari.digiroys.com/paid/index.php" class="inline-block text-[9px] bg-amber-500 text-slate-950 px-4 py-1.5 rounded-xl font-black uppercase tracking-wider hover:bg-amber-400 transition-all">S'abonner maintenant</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Section Simulateur d'Investissement Premium -->
        <?php if (isset($_SESSION['is_premium']) && $_SESSION['is_premium']): ?>
        <div id="simulatorSection" class="mt-4 glass-card p-3 shadow-2xl relative border border-amber-500/10">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-[11px] uppercase tracking-[0.1em] text-amber-400 font-bold flex items-center gap-1.5">
                    <i class="ri-line-chart-line"></i> Simulateur d'Investissement UEMOA
                </h3>
                <span class="text-[8px] font-black uppercase tracking-widest bg-amber-500/20 text-amber-300 border border-amber-500/30 px-2.5 py-0.5 rounded-full select-none">PRO</span>
            </div>
            <p class="text-slate-400 text-[9.5px] leading-tight mb-3">
                Calculez l'évolution de votre épargne placée sur les supports réels d'Afrique de l'Ouest (Bons du Trésor, DAT, Bourse BRVM) grâce aux intérêts composés.
            </p>
            <button onclick="openSimulatorModal()" class="w-full py-2.5 bg-amber-500/20 text-amber-400 border border-amber-500/30 rounded-xl font-extrabold text-[10px] uppercase tracking-wider hover:bg-amber-500/30 transition-all flex items-center justify-center gap-1.5">
                Lancer une simulation
            </button>
        </div>
        <?php else: ?>
        <!-- Version Lock Premium pour le simulateur -->
        <div class="mt-4 glass-card p-3 shadow-2xl relative overflow-hidden border border-amber-500/10">
            <div class="absolute -right-10 -top-10 w-24 h-24 bg-amber-500/10 rounded-full blur-2xl"></div>
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-[11px] uppercase tracking-[0.1em] text-slate-400 font-bold flex items-center gap-1.5">
                    <i class="ri-line-chart-line"></i> Simulateur d'Investissement
                </h3>
                <span class="text-[7.5px] font-black uppercase tracking-widest bg-amber-500/20 text-amber-400 border border-amber-500/30 px-1.5 py-0.5 rounded-full">PREMIUM</span>
            </div>
            <div class="text-center py-4 relative z-10">
                <svg class="mx-auto text-slate-500 mb-2" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <p class="text-white font-bold text-xs mb-1">Simulations financières UEMOA</p>
                <p class="text-slate-400 text-[9px] max-w-[220px] mx-auto mb-3">Estimez vos intérêts composés sur les obligations d'État, les DAT et l'épargne Mobile Money locale.</p>
                <a href="https://wari.digiroys.com/paid/index.php" class="inline-block text-[9px] bg-amber-500 text-slate-950 px-4 py-1.5 rounded-xl font-black uppercase tracking-wider hover:bg-amber-400 transition-all">Débloquer le Simulateur</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Dette Section -->
        <div id="debtSection" class="mt-4 glass-card p-3 shadow-2xl relative">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-1.5">
                    <h3 class="text-[11px] uppercase tracking-[0.1em] text-red-400 font-bold">Carnet de Dettes</h3>
                    <button onclick="openHelpModal('dette')" class="w-4 h-4 rounded-full border border-red-500/50 text-red-400 flex items-center justify-center text-[9px] font-bold hover:bg-red-500/20 transition-colors" title="C'est quoi ?">?</button>
                </div>
                <div class="flex items-center gap-1.5">
                    <?php if (isset($_SESSION['is_premium']) && $_SESSION['is_premium']): ?>
                    <button onclick="openSnowballModal()" class="text-[11px] bg-amber-500/20 text-amber-400 px-2 py-1 rounded-full border border-amber-500/30 font-bold hover:bg-amber-500/35 active:scale-95 transition-all flex items-center gap-1">
                        Plan Pro
                    </button>
                    <?php endif; ?>
                    <button onclick="openDebtModal()" class="text-[11px] bg-red-500/20 text-red-400 px-2 py-1 rounded-full border border-red-500/30 font-bold hover:bg-red-500/40 transition-all">
                        + Add
                    </button>
                </div>
            </div>  

            <div id="debtList" class="space-y-3">
                <p class="text-slate-500 text-[11px] italic text-center">Aucune dette ou créance en cours.</p>
            </div>
        </div>

        <!-- Modal pour ajouter une dette -->
        <div id="debtModal" class="fixed inset-0 bg-slate-900/90 backdrop-blur-sm hidden items-center justify-center p-4 z-[110]">
            <div class="glass-card w-full max-w-sm p-4 border border-slate-700 shadow-2xl">
                <h3 id="debtModalTitle" class="text-red-400 font-bold mb-4 uppercase tracking-widest text-sm">Ajouter une note</h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-[11px] text-slate-400 mb-1">TYPE</label>
                        <select id="debtType" class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-white outline-none">
                            <option value="loan">On me doit (Créance)</option>
                            <option value="debt">Je dois (Dette)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] text-slate-400 mb-1">NOM DE LA PERSONNE</label>
                        <input type="text" id="debtPerson" class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-white outline-none" placeholder="Ex: Moussa">
                    </div>
                    <div>
                        <label class="block text-[11px] text-slate-400 mb-1 uppercase">
                            Montant (<span class="currencyLabel">F</span>)
                        </label>
                        <input type="number" id="debtAmount" class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-white outline-none" placeholder="0">
                    </div>

                    <div>
                        <label class="block text-[11px] text-slate-400 mb-1 uppercase">Date d'échéance (optionnel)</label>
                        <input type="date" id="debtDueDate"
                            class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-white outline-none">
                    </div>

                    <div class="flex gap-2 pt-2">
                        <button onclick="closeDebtModal()" class="flex-1 py-3 bg-slate-800 text-slate-400 rounded-xl font-bold text-sm">Annuler</button>
                        <button onclick="submitDebt()" class="flex-1 py-3 bg-red-600 text-white rounded-xl font-bold text-sm">Enregistrer</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de remboursement -->
        <div id="payModal" class="fixed inset-0 bg-slate-900/90 backdrop-blur-sm hidden items-center justify-center p-4 z-[120]">
            <div class="glass-card w-full max-w-sm p-4 border border-slate-700 shadow-2xl">
                <h3 class="text-emerald-400 font-bold mb-1 uppercase text-sm">Remboursement</h3>
                <p id="payModalTarget" class="text-slate-400 text-[11px] mb-4"></p>

                <input type="hidden" id="payDebtId">
                <input type="hidden" id="payDebtType">

                <div class="space-y-4">
                    <div>
                        <label id="paySourceEnvelopeLabel" class="block text-[11px] text-slate-400 mb-1 uppercase">Prélever depuis l'enveloppe</label>
                        <select id="paySourceEnvelope" class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-white outline-none focus:border-emerald-500 mb-4">
                            <!-- Rempli en JS -->
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] text-slate-400 mb-1 uppercase">
                            Montant du versement (<span class="currencyLabel">F</span>)
                        </label>
                        <input type="number" id="payPartAmount" class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-white outline-none focus:border-emerald-500" placeholder="0">
                    </div>

                    <div class="flex gap-2 pt-2">
                        <button onclick="closePayModal()" class="flex-1 py-3 bg-slate-800 text-slate-400 rounded-xl font-bold text-sm">Annuler</button>
                        <button onclick="submitPartialPay()" class="flex-1 py-3 bg-emerald-600 text-white rounded-xl font-bold text-sm">Confirmer</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Premium Planificateur Anti-Dette (Boule de Neige) -->
        <div id="debtSnowballModal" class="fixed inset-0 bg-slate-900/90 backdrop-blur-sm hidden items-center justify-center p-4 z-[120]">
            <div class="glass-card w-full max-w-md p-4 border border-slate-700 shadow-2xl max-h-[85vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-slate-700/60 pb-3 mb-3">
                    <h3 class="text-amber-400 font-bold uppercase tracking-wider text-sm flex items-center gap-1.5">
                        <i class="ri-rocket-2-line"></i> Planificateur Anti-Dette Pro
                    </h3>
                    <button onclick="closeSnowballModal()" class="text-slate-400 hover:text-white text-lg font-bold">&times;</button>
                </div>

                <!-- Bilan Net -->
                <div class="grid grid-cols-3 gap-2 mb-4 bg-slate-800/40 p-2.5 rounded-xl border border-slate-700/50">
                    <div class="text-center">
                        <p class="text-[9px] uppercase tracking-wider text-slate-500 font-bold">Mes Dettes</p>
                        <p id="sbTotalDebts" class="text-xs font-black text-red-400">0 F</p>
                    </div>
                    <div class="text-center border-x border-slate-700/60">
                        <p class="text-[9px] uppercase tracking-wider text-slate-500 font-bold">Mes Créances</p>
                        <p id="sbTotalLoans" class="text-xs font-black text-emerald-400">0 F</p>
                    </div>
                    <div class="text-center">
                        <p class="text-[9px] uppercase tracking-wider text-slate-500 font-bold">Situation Net</p>
                        <p id="sbNetSituation" class="text-xs font-black text-white">0 F</p>
                    </div>
                </div>

                <!-- Entrée de mensualité -->
                <div class="mb-4">
                    <label class="block text-[10px] uppercase tracking-wider text-slate-400 mb-1 font-bold">
                        Capacité de remboursement mensuelle (<span class="currencyLabel">F</span>)
                    </label>
                    <input type="number" id="sbMonthlyCap" oninput="calculateSnowball()"
                        class="w-full bg-slate-800 border border-slate-700 rounded-xl p-2.5 text-white outline-none focus:border-amber-500 font-bold"
                        placeholder="Ex: 20000" value="20000">
                    <p class="text-[9px] text-slate-500 mt-1.5 italic leading-tight">
                        La méthode <b>Boule de Neige</b> trie tes dettes de la plus petite à la plus grande pour les éliminer une par une en reportant la force de remboursement.
                    </p>
                </div>

                <!-- Contenu Dynamique -->
                <div class="space-y-4">
                    <!-- Feuille de route -->
                    <div>
                        <h4 class="text-[10px] uppercase tracking-wider text-amber-500 font-bold mb-2 flex items-center gap-1">
                            <i class="ri-list-ordered"></i> Ordre de Libération (Dettes)
                        </h4>
                        <div id="sbDebtTimeline" class="space-y-2 max-h-[220px] overflow-y-auto pr-1">
                            <!-- Rendu dynamique JS -->
                        </div>
                    </div>

                    <!-- Créances actives -->
                    <div>
                        <h4 class="text-[10px] uppercase tracking-wider text-emerald-400 font-bold mb-2 flex items-center gap-1">
                            <i class="ri-hand-coin-line"></i> Créances à encaisser (Opportunités)
                        </h4>
                        <div id="sbLoanTimeline" class="space-y-2 max-h-[120px] overflow-y-auto pr-1">
                            <!-- Rendu dynamique JS -->
                        </div>
                    </div>
                </div>

                <!-- Bilan final / Date de liberté -->
                <div class="mt-4 pt-3 border-t border-slate-700/60 text-center">
                    <p class="text-[10px] text-slate-400">Libération totale estimée :</p>
                    <p id="sbFinalDate" class="text-lg font-black text-amber-400 uppercase tracking-widest mt-1">DANS X MOIS</p>
                </div>
            </div>
        </div>

        <!-- Modal Premium Simulateur d'Investissement UEMOA -->
        <div id="simulatorModal" onclick="closeSimulatorModal()" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden items-end md:items-center justify-center z-[120]" style="overscroll-behavior: contain;">
            <div onclick="event.stopPropagation()" class="glass-card w-full max-w-2xl h-full md:h-[85vh] md:max-h-[750px] p-4 pt-safe md:p-6 border-t border-x md:border border-slate-800 rounded-none md:rounded-[2rem] shadow-2xl flex flex-col animate-slide-up" style="scrollbar-width: none; -ms-overflow-style: none;">
                <style>
                    #simulatorModal .glass-card::-webkit-scrollbar {
                        display: none;
                    }
                </style>
                <div class="flex items-center justify-between border-b border-slate-700/60 pb-3 mb-3 shrink-0">
                    <h3 class="text-amber-400 font-bold uppercase tracking-wider text-sm flex items-center gap-1.5">
                        <i class="ri-calculator-line"></i> Simulateur d'Investissement UEMOA
                    </h3>
                    <button onclick="closeSimulatorModal()" class="text-slate-400 hover:text-white text-lg font-bold">&times;</button>
                </div>

                <div class="space-y-4 overflow-y-auto flex-1 pr-1 pb-28 custom-scrollbar" style="scrollbar-width: none; -ms-overflow-style: none; overscroll-behavior: contain;">
                    <style>
                        #simulatorModal .space-y-4::-webkit-scrollbar {
                            display: none;
                        }
                    </style>
                    <!-- Sélection du Placement -->
                    <div>
                        <label class="block text-[10px] uppercase tracking-wider text-slate-400 mb-1 font-bold">Où veux-tu placer ton argent ? (Placements courants)</label>
                        <select id="simPlacementType" onchange="onSimulatorPlacementChange()" class="w-full bg-slate-800 border border-slate-700 rounded-xl p-2.5 text-white outline-none focus:border-amber-500 font-medium">
                            <option value="momo" data-rate="3.5">Épargne Mobile Money Spéciale (Bonus de 3.5% par an)</option>
                            <option value="dat" data-rate="5.5">Coffre bloqué en banque/microfinance (Bonus de 5.5% par an)</option>
                            <option value="bonds" data-rate="6.25" selected>Prêter de l'argent à l'État (Bonus de 6.25% par an)</option>
                            <option value="brvm" data-rate="8.0">Achat de parts d'entreprises - BRVM (Bonus moyen de 8.0% par an)</option>
                        </select>
                    </div>

                    <!-- Ligne Montant & Taux d'intérêt -->
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] uppercase tracking-wider text-slate-400 mb-1 font-bold">Somme que tu mets chaque mois (<span class="currencyLabel">F</span>)</label>
                            <input type="number" id="simMonthlyAmount" oninput="runSimulation()"
                                class="w-full bg-slate-800 border border-slate-700 rounded-xl p-2.5 text-white outline-none focus:border-amber-500 font-bold text-center"
                                placeholder="Ex: 25000" value="20000">
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase tracking-wider text-slate-400 mb-1 font-bold">Pourcentage de bonus par an (%)</label>
                            <input type="number" step="0.05" id="simAnnualRate" oninput="runSimulation()"
                                class="w-full bg-slate-800 border border-slate-700 rounded-xl p-2.5 text-white outline-none focus:border-amber-500 font-bold text-center text-amber-400"
                                placeholder="Ex: 6.25" value="6.25">
                        </div>
                    </div>

                    <!-- Durée -->
                    <div>
                        <label class="block text-[10px] uppercase tracking-wider text-slate-400 mb-1 font-bold">Pendant combien d'années (de 1 à 10 ans)</label>
                        <div class="flex items-center gap-2">
                            <input type="range" id="simYearsRange" min="1" max="10" value="5" oninput="document.getElementById('simYearsText').innerText = this.value + ' an(s)'; runSimulation();" class="flex-1 accent-amber-500">
                            <span id="simYearsText" class="w-16 text-center font-bold text-xs text-white bg-slate-800 border border-slate-700 py-1.5 px-2.5 rounded-lg select-none">5 an(s)</span>
                        </div>
                    </div>

                    <!-- Enveloppes de Résultats -->
                    <div class="grid grid-cols-3 gap-2 bg-slate-800/40 p-2.5 rounded-xl border border-slate-700/50">
                        <div class="text-center">
                            <p class="text-[8.5px] uppercase tracking-wider text-slate-500 font-bold">Ton argent versé</p>
                            <p id="simTotalDeposits" class="text-xs font-black text-white">0 F</p>
                        </div>
                        <div class="text-center border-x border-slate-700/60">
                            <p class="text-[8.5px] uppercase tracking-wider text-slate-500 font-bold">Cadeau de la banque (Intérêts)</p>
                            <p id="simTotalInterest" class="text-xs font-black text-emerald-400">+0 F</p>
                        </div>
                        <div class="text-center">
                            <p class="text-[8.5px] uppercase tracking-wider text-slate-500 font-bold">Somme finale</p>
                            <p id="simTotalFinal" class="text-xs font-black text-amber-400">0 F</p>
                        </div>
                    </div>

                    <!-- Graphique en colonnes stylé nativement -->
                    <div>
                        <h4 class="text-[10px] uppercase tracking-wider text-slate-400 font-bold mb-2.5 flex items-center gap-1">
                            <i class="ri-bar-chart-fill"></i> Évolution de ton trésor d'année en année
                        </h4>
                        <!-- Graphique -->
                        <div class="bg-slate-900/50 border border-slate-800 p-3 rounded-2xl">
                            <div id="simChartContainer" class="flex items-end justify-between h-36 gap-1 px-1">
                                <!-- Généré dynamiquement en JS (barres verticales) -->
                            </div>
                        </div>
                        <!-- Légende -->
                        <div class="flex justify-center gap-4 mt-2 text-[9px] font-bold text-slate-400">
                            <div class="flex items-center gap-1">
                                <span class="w-2.5 h-2.5 bg-slate-700 rounded-sm"></span> Ton propre argent
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="w-2.5 h-2.5 bg-emerald-500 rounded-sm"></span> Cadeau de la banque
                            </div>
                        </div>
                    </div>

                    <!-- Message d'éducation financière -->
                    <div class="bg-amber-950/10 border border-amber-900/20 p-2.5 rounded-xl text-[9px] text-amber-400/90 leading-relaxed italic">
                        <i class="ri-information-line"></i> Ce calcul te montre comment ton argent grandit si tu mets de côté la même somme chaque mois sans y toucher. Le bonus s'ajoute chaque année sur l'ancien montant.
                    </div>
                </div>
            </div>
        </div>

        <!-- Barre de Navigation Fixe en Bas -->
        <nav class="fixed bottom-0 left-0 right-0 z-[120] bg-[#0B141A]/95 backdrop-blur-lg shadow-[0_-10px_30px_rgba(0,0,0,0.6)] py-0 px-4 pb-safe">
            <div class="max-w-md mx-auto flex items-center justify-between relative">
                
                <!-- 1. Academy -->
                <div class="relative flex-1 flex justify-center">
                    <div id="license-message" 
                        class="absolute bottom-full mb-3 w-44 p-3 bg-slate-950 border border-amber-500/30 rounded-xl shadow-2xl opacity-0 translate-y-2 transition-all duration-500 ease-out z-50 pointer-events-none text-center">
                        <p class="text-[10px] font-semibold text-slate-200 leading-tight">
                           <span class="text-amber-500 font-bold uppercase tracking-wider">Exclusivité</span><br>
                            <?= $unfinishedCoursesCount > 0 ? "Tu as <b>$unfinishedCoursesCount</b> cours en attente !" : "Boostez votre éducation financière." ?>
                        </p>
                        <div class="absolute -bottom-1.5 left-1/2 -translate-x-1/2 w-3 h-3 bg-slate-950 border-r border-b border-amber-500/30 rotate-45"></div>
                    </div>

                    <a href="https://wari.digiroys.com/academy/" onclick="trackLicenseBuy()" title="Academy"
                        class="w-10 h-10 rounded-xl flex flex-col items-center justify-center text-slate-400 hover:text-indigo-400 active:scale-95 transition-all">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
                            <path d="M1.5 9 12 3l10.5 6L12 15 1.5 9Z"></path>
                            <path d="M5.25 11.25v6L12 21l6.75-3.75v-6"></path>
                            <path d="M22.5 17.25V9"></path>
                            <path d="M12 15v6"></path>
                        </svg>
                        <span class="text-[8px] font-bold uppercase tracking-widest mt-0.5">Academy</span>
                        <?php if ($unfinishedCoursesCount > 0): ?>
                            <span class="absolute top-0 right-4 flex h-4 w-4 items-center justify-center rounded-full bg-red-600 text-[8px] font-black text-white ring-2 ring-slate-950 animate-pulse">
                                <?= $unfinishedCoursesCount ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>

                <!-- 2. Mettre à jour (Sync) -->
                <div class="flex-1 flex justify-center">
                    <button onclick="saveBudget()" title="Mettre à jour"
                        class="w-10 h-10 rounded-xl flex flex-col items-center justify-center text-slate-400 hover:text-emerald-400 active:scale-95 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span class="text-[8px] font-bold uppercase tracking-widest mt-0.5">Sync</span>
                    </button>
                </div>

                <!-- 3. Bouton Ajout Dépense (+) au centre -->
                <div class="flex-1 flex justify-center -translate-y-4">
                    <button onclick="openExpenseModal()" title="Ajouter Dépense"
                        class="w-12 h-12 rounded-full bg-gradient-to-br from-yellow-400 to-amber-600 text-black active:scale-95 hover:scale-105 transition-all flex items-center justify-center font-bold shadow-lg shadow-amber-500/20 border-4 border-[#0B141A]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 5v14M5 12h14" />
                        </svg>
                    </button>
                </div>

                <!-- 4. Historique -->
                <div class="flex-1 flex justify-center">
                    <button onclick="openHistoryModal()" title="Historique"
                        class="w-10 h-10 rounded-xl flex flex-col items-center justify-center text-slate-400 hover:text-amber-400 active:scale-95 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-[8px] font-bold uppercase tracking-widest mt-0.5">Historique</span>
                    </button>
                </div>

                <!-- 5. Vécu -->
                <div class="relative flex-1 flex justify-center">
                    <a href="https://wari.digiroys.com/vecu/" title="Vécu"
                        class="w-10 h-10 rounded-xl flex flex-col items-center justify-center text-slate-400 hover:text-cyan-400 active:scale-95 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path d="m14.728 22.609-2.66-5.379-2.105-2.69a3.414 3.414 0 0 1-.475-1.737V6.75h.735a1.885 1.885 0 0 1 1.886 1.885v8.595"></path>
                            <path d="M5.996 13.737v-3.493S7.743 6.75 9.49 6.75"></path>
                            <path d="m17.348 12.863-3.098-2.035"></path>
                            <path d="m7.994 22.423 2.507-3.673"></path>
                            <path d="M12.108 5a1.747 1.747 0 1 0 0-3.492 1.747 1.747 0 0 0 0 3.493Z"></path>
                        </svg>
                        <span class="text-[8px] font-bold uppercase tracking-widest mt-0.5">Vécu</span>
                        <?php if ($unreadVecuCount > 0): ?>
                            <span class="absolute top-0 right-4 flex h-4 w-4 items-center justify-center rounded-full bg-emerald-600 text-[8px] font-black text-white ring-2 ring-slate-950 animate-bounce">
                                <?= $unreadVecuCount ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </nav>

        <script>
            window.addEventListener('load', () => {
                const msgBulle = document.getElementById('license-message');
                const lastDisplay = localStorage.getItem('wari_academy_nudge');
                const now = new Date().getTime();
                
                // Délai de 21 jours (21 jours * 24h * 60m * 60s * 1000ms)
                const delay = 21 * 24 * 60 * 60 * 1000;

                if (!lastDisplay || (now - parseInt(lastDisplay)) > delay) {
                    setTimeout(() => {
                        if (msgBulle) {
                            // Apparition
                            msgBulle.classList.remove('opacity-0', 'translate-y-2');
                            msgBulle.classList.add('opacity-100', 'translate-y-0');
                            
                            // On enregistre l'affichage
                            localStorage.setItem('wari_academy_nudge', now.toString());

                            // Disparition après 5 secondes (lecture rapide)
                            setTimeout(() => closeLicenseMsg(), 5000);
                        }
                    }, 4000); 
                }
            });

            function closeLicenseMsg() {
                const msgBulle = document.getElementById('license-message');
                if (msgBulle) {
                    msgBulle.classList.replace('opacity-100', 'opacity-0');
                    msgBulle.classList.add('translate-y-2');
                }
            }

            function trackLicenseBuy() {
                closeLicenseMsg();
                if (window.DigiStats && typeof window.DigiStats.track === 'function') {
                    window.DigiStats.track('click_academy_access', { platform: 'web' });
                }
            }
        </script>

        <!-- Bouton Installation PWA -->
        <div id="installBtn" onclick="triggerInstall()" class="hidden mt-6 group cursor-pointer">
            <div class="glass border-amber-500/20 bg-amber-500/5 p-4 rounded-2xl flex items-center justify-between hover:bg-amber-500/10 transition-all active:scale-95 border border-dashed">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-amber-500 rounded-xl flex items-center justify-center text-black shadow-lg shadow-amber-500/20">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-[11px] uppercase tracking-widest text-amber-500 font-black">Expérience Mobile</h4>
                        <p class="text-white font-bold text-xs">Installer l'application Wari</p>
                    </div>
                </div>
                <div class="text-amber-500 animate-bounce">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL OBJECTIF -->
    <div id="goalModal" class="fixed inset-0 bg-slate-900/90 backdrop-blur-sm hidden items-center justify-center p-4 z-[130]">
        <div class="glass-card w-full max-w-sm p-4 border border-slate-700 shadow-2xl">
            <h3 class="text-emerald-400 font-bold uppercase tracking-widest text-sm mb-4">Définir un objectif</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1 uppercase">Nom de l'objectif</label>
                    <input type="text" id="goalLabel" placeholder="Ex: MacBook Pro, Terrain..."
                        class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-white outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-[11px] text-slate-400 mb-1 uppercase">Montant cible</label>
                    <input type="number" id="goalAmount" placeholder="0"
                        class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-white outline-none focus:border-emerald-500">
                </div>
                <div class="flex gap-2 pt-2">
                    <button onclick="closeGoalModal()" class="flex-1 py-3 bg-slate-800 text-slate-400 rounded-xl font-bold text-sm">Annuler</button>
                    <button onclick="saveGoal()" class="flex-1 py-3 bg-emerald-600 text-white rounded-xl font-bold text-sm">Valider</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ MODAL HISTORIQUE -->
    <div id="historyModal" onclick="closeHistoryModal()" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden items-end md:items-center justify-center z-[130]">
        <div onclick="event.stopPropagation()" class="glass-card w-full max-w-md h-full md:h-[85vh] md:max-h-[750px] p-2 pt-safe md:p-6 border-t border-x md:border border-slate-800 rounded-none md:rounded-[2rem] shadow-2xl flex flex-col animate-slide-up">

            <div class="flex items-center justify-between mb-4 shrink-0">
                <h3 class="text-amber-400 font-bold uppercase tracking-widest text-xs">Historique</h3>

                <div class="flex items-center gap-2">
                    <select onchange="loadMonthlyHistory(this.value)"
                        class="bg-slate-800 text-slate-300 text-[11px] border border-slate-700 rounded-lg px-2 py-1">
                        <option value="3">3 mois</option>
                        <option value="6" selected>6 mois</option>
                        <option value="12">12 mois</option>
                    </select>

                    <button onclick="toggleTheme()" class="modalThemeToggleBtn w-9 h-9 flex items-center justify-center rounded-xl bg-white/5 text-slate-400 hover:text-white active:scale-95 transition-all" title="Changer le thème">
                        <span class="modalThemeIcon text-slate-400"></span>
                    </button>

                    <button onclick="closeHistoryModal()" class="w-9 h-9 flex items-center justify-center rounded-xl bg-white/5 text-slate-400 hover:text-white active:scale-95 transition-all" title="Fermer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <div id="historyContent" class="space-y-4 overflow-y-auto pb-28 custom-scrollbar flex-1 pr-1">
                <p class="text-slate-500 text-[12px] italic text-center py-4">Chargement...</p>
            </div>
        </div>
    </div>

    <!-- Bulle de prévisualisation flottante du Coach AI -->
    <div id="coachBubble" class="fixed bottom-[136px] right-6 z-50 max-w-[210px] bg-slate-950/95 backdrop-blur-md border border-amber-500/30 text-amber-50 px-3 py-2 rounded-2xl rounded-br-none shadow-2xl shadow-black/80 text-[11px] font-semibold leading-relaxed flex items-start gap-2 opacity-0 pointer-events-none transition-all duration-500 translate-y-2 select-none">
        <span class="flex-1">Salut, je suis ton coach financier. Et si on discutait un peu ?</span>
        <button onclick="dismissCoachBubble(event)" class="text-slate-400 hover:text-white p-0.5 active:scale-95 transition-colors font-bold text-xs leading-none">✕</button>
    </div>

    <!-- Bouton Coach AI → page dédiée /coach -->
    <a id="coachButton" href="/coach/"
        class="fixed bottom-20 right-6 w-12 h-12 bg-gradient-to-br from-amber-400 via-amber-500 to-orange-600 rounded-full flex items-center justify-center text-slate-950 active:scale-95 hover:scale-110 transition-all duration-300 z-50 group border border-white/10 touch-none">

        <!-- Custom coach/AI SVG Icon -->
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-950" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 9h.01M16 9h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>

        <div class="absolute inset-0 rounded-full bg-gradient-to-t from-transparent to-white/25 pointer-events-none"></div>
    </a>

    <div id="expenseModal" class="fixed inset-0 bg-slate-900/90 backdrop-blur-sm hidden items-center justify-center p-4 z-[100]">
        <div class="glass-card w-full max-w-sm p-4 border border-slate-700 shadow-2xl">
            <h3 class="text-yellow-500 font-bold mb-4 uppercase tracking-widest text-sm">⚡ Dépense Flash</h3>

            <div class="space-y-5 p-1">
                <div>
                    <label class="block text-[11px] uppercase tracking-[0.15em] text-slate-500 mb-2 font-black">
                        Montant à déduire (<span class="currencyLabel">F</span>)
                    </label>
                    <input type="number" id="expAmount"
                        class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl p-4 text-white text-xl font-black outline-none focus:border-emerald-500/50 focus:ring-4 focus:ring-emerald-500/10 transition-all placeholder:text-slate-700"
                        placeholder="0">
                </div>

                <div>
                    <label class="block text-[11px] uppercase tracking-[0.15em] text-slate-500 mb-2 font-black">
                        Motif de la dépense
                    </label>
                    <input type="text" id="expNote"
                        class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl p-4 text-white text-sm outline-none focus:border-emerald-500/50 focus:ring-4 focus:ring-emerald-500/10 transition-all placeholder:text-slate-700"
                        placeholder="Ex: Achat Ordinateur, Loyer, Resto...">
                </div>

                <div>
                    <label class="block text-[11px] uppercase tracking-[0.15em] text-slate-500 mb-2 font-black">
                        Catégorie cible
                    </label>
                    <select id="expCategory"
                        class="w-full bg-slate-900/50 border border-slate-700/50 rounded-2xl p-4 text-white text-sm outline-none focus:border-emerald-500/50 appearance-none cursor-pointer tracking-wide">
                    </select>
                </div>

                <div class="flex gap-3 pt-4">
                    <button onclick="closeExpenseModal()"
                        class="flex-1 py-4 bg-slate-800/50 hover:bg-slate-800 text-slate-400 rounded-2xl font-black text-[11px] uppercase tracking-widest transition-all">
                        Annuler
                    </button>
                    <button onclick="submitExpense()"
                        class="flex-1 py-4 bg-emerald-600 hover:bg-emerald-500 text-white rounded-2xl font-black text-[11px] uppercase tracking-widest shadow-lg shadow-emerald-900/40 active:scale-[0.98] transition-all">
                        Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js');
        }
    </script>

    <script>
        let deferredPrompt;
        const installBtn = document.getElementById('installBtn');

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            // On affiche le bouton seulement si l'app peut être installée
            if (installBtn) installBtn.classList.remove('hidden');
        });

        window.triggerInstall = async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const {
                    outcome
                } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    console.log('Wari installé !');
                    if (installBtn) installBtn.classList.add('hidden');
                }
                deferredPrompt = null;
            } else {
                alert("Pour installer : cliquez sur les 3 points du navigateur puis 'Installer l'application'");
            }
        };
    </script>

    <script>
        <?php
        $userId = $_SESSION['user_id'];

        // 1. Récupérer le budget personnel et professionnel, et les infos de feedback
        $stmt = $pdo->prepare("SELECT budget_data, budget_data_pro, last_budget_at, feedback_status, last_feedback_prompt_at, date_inscription FROM wari_users WHERE id = ?");
        $stmt->execute([$userId]);
        $userData = $stmt->fetch();
        $budgetRaw = (!empty($userData['budget_data'])) ? $userData['budget_data'] : 'null';
        $budgetRawPro = (!empty($userData['budget_data_pro'])) ? $userData['budget_data_pro'] : 'null';

        $defaultProBudget = json_encode([
            "currency" => "F",
            "categories" => [
                ["id" => 101, "icon" => '<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round" class="text-blue-400"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>', "name" => "Stock & Matériel", "amount" => 0, "balance" => 0, "percent" => 40],
                ["id" => 102, "icon" => '<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round" class="text-emerald-400"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>', "name" => "Bénéfice Réinvesti", "amount" => 0, "balance" => 0, "percent" => 30],
                ["id" => 103, "icon" => '<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round" class="text-amber-400"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>', "name" => "Frais de Fonctionnement", "amount" => 0, "balance" => 0, "percent" => 20],
                ["id" => 104, "icon" => '<svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round" class="text-red-400"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>', "name" => "Marketing & Publicité", "amount" => 0, "balance" => 0, "percent" => 10]
            ],
            "projectCapital" => 0,
            "vaultTransactions" => []
        ]);
        if ($budgetRawPro === 'null') {
            $budgetRawPro = $defaultProBudget;
        }

        // AJOUTER CE BLOC APRÈS $budgetRaw = ...
        if ($budgetRaw !== 'null') {
            $budgetData = json_decode($budgetRaw, true);
            $lastMonth = isset($budgetData['lastSavedMonth']) ? $budgetData['lastSavedMonth'] : null;
            $currentMonth = date('Y-m');

            if ($lastMonth && $lastMonth !== $currentMonth) {
                // Récupérer les dépenses du mois qui vient de se terminer ($lastMonth) pour calculer le report
                $stmtPrevExp = $pdo->prepare("
                    SELECT category_id, SUM(amount) as total 
                    FROM wari_expenses 
                    WHERE user_id = ? AND wallet_type = 'perso'
                    AND DATE_FORMAT(date_expense, '%Y-%m') = ?
                    GROUP BY category_id
                ");
                $stmtPrevExp->execute([$userId, $lastMonth]);
                $prevExpenses = [];
                foreach ($stmtPrevExp->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $prevExpenses[$row['category_id']] = (int)$row['total'];
                }

                if (isset($budgetData['categories'])) {
                    foreach ($budgetData['categories'] as &$cat) {
                        $catId = $cat['id'];
                        $isProjet = isset($cat['name']) && (strpos(strtolower($cat['name']), 'projet') !== false);
                        if (!$isProjet) {
                            $spent = isset($prevExpenses[$catId]) ? $prevExpenses[$catId] : 0;
                            $cat['balance'] = max(0, (isset($cat['balance']) ? (int)$cat['balance'] : 0) - $spent);
                        }
                    }
                }
                $budgetData['hasDepositedToday'] = false;
                $budgetData['lastSavedMonth'] = $currentMonth;

                $newBudgetRaw = json_encode($budgetData);
                $stmtUpdate = $pdo->prepare("UPDATE wari_users SET budget_data = ? WHERE id = ?");
                $stmtUpdate->execute([$newBudgetRaw, $userId]);
                $budgetRaw = $newBudgetRaw;
            }
        }

        // Rollover pour le budget pro
        if ($budgetRawPro !== 'null') {
            $budgetDataPro = json_decode($budgetRawPro, true);
            $lastMonthPro = isset($budgetDataPro['lastSavedMonth']) ? $budgetDataPro['lastSavedMonth'] : null;
            $currentMonth = date('Y-m');

            if ($lastMonthPro && $lastMonthPro !== $currentMonth) {
                $stmtPrevExpPro = $pdo->prepare("
                    SELECT category_id, SUM(amount) as total 
                    FROM wari_expenses 
                    WHERE user_id = ? AND wallet_type = 'pro'
                    AND DATE_FORMAT(date_expense, '%Y-%m') = ?
                    GROUP BY category_id
                ");
                $stmtPrevExpPro->execute([$userId, $lastMonthPro]);
                $prevExpensesPro = [];
                foreach ($stmtPrevExpPro->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $prevExpensesPro[$row['category_id']] = (int)$row['total'];
                }

                if (isset($budgetDataPro['categories'])) {
                    foreach ($budgetDataPro['categories'] as &$cat) {
                        $catId = $cat['id'];
                        $isProjet = isset($cat['name']) && (strpos(strtolower($cat['name']), 'projet') !== false);
                        if (!$isProjet) {
                            $spent = isset($prevExpensesPro[$catId]) ? $prevExpensesPro[$catId] : 0;
                            $cat['balance'] = max(0, (isset($cat['balance']) ? (int)$cat['balance'] : 0) - $spent);
                        }
                    }
                }
                $budgetDataPro['hasDepositedToday'] = false;
                $budgetDataPro['lastSavedMonth'] = $currentMonth;

                $newBudgetRawPro = json_encode($budgetDataPro);
                $stmtUpdatePro = $pdo->prepare("UPDATE wari_users SET budget_data_pro = ? WHERE id = ?");
                $stmtUpdatePro->execute([$newBudgetRawPro, $userId]);
                $budgetRawPro = $newBudgetRawPro;
            }
        }

        // 2. RÉCUPÉRER LES DÉPENSES DU MOIS ACTUEL (MARS 2026) POUR CHAQUE WALLET
        $stmtExpPerso = $pdo->prepare("
            SELECT category_id, SUM(amount) as total 
            FROM wari_expenses 
            WHERE user_id = ? AND wallet_type = 'perso'
            AND MONTH(date_expense) = MONTH(CURRENT_DATE()) 
            AND YEAR(date_expense) = YEAR(CURRENT_DATE())
            GROUP BY category_id
        ");
        $stmtExpPerso->execute([$userId]);
        $expensesPerso = $stmtExpPerso->fetchAll(PDO::FETCH_KEY_PAIR);

        $stmtExpPro = $pdo->prepare("
            SELECT category_id, SUM(amount) as total 
            FROM wari_expenses 
            WHERE user_id = ? AND wallet_type = 'pro'
            AND MONTH(date_expense) = MONTH(CURRENT_DATE()) 
            AND YEAR(date_expense) = YEAR(CURRENT_DATE())
            GROUP BY category_id
        ");
        $stmtExpPro->execute([$userId]);
        $expensesPro = $stmtExpPro->fetchAll(PDO::FETCH_KEY_PAIR);

        // 3. Récupérer les dettes
        $stmtDebts = $pdo->prepare("
            SELECT id, person_name, amount, type 
            FROM wari_debts 
            WHERE user_id = ? AND status = 'pending' 
            ORDER BY created_at DESC
        ");
        $stmtDebts->execute([$userId]);
        $debts = $stmtDebts->fetchAll(PDO::FETCH_ASSOC);

        // 4. Récupérer le défi d'épargne actif
        $stmtActiveChallenge = $pdo->prepare("
            SELECT id, user_id, challenge_type, base_amount, target_amount, current_amount, status, metadata 
            FROM wari_savings_challenges 
            WHERE user_id = ? AND status = 'active'
        ");
        $stmtActiveChallenge->execute([$userId]);
        $activeChallenge = $stmtActiveChallenge->fetch(PDO::FETCH_ASSOC);

        // Récupérer le nombre de défis complétés
        $stmtCompletedCount = $pdo->prepare("
            SELECT COUNT(*) 
            FROM wari_savings_challenges 
            WHERE user_id = ? AND status = 'completed'
        ");
        $stmtCompletedCount->execute([$userId]);
        $completedCount = $stmtCompletedCount->fetchColumn();

        // Envoi au JS
        echo "const dbDataPerso = " . $budgetRaw . ";\n";
        echo "const dbDataPro = " . $budgetRawPro . ";\n";
        echo "let dbData = dbDataPerso;\n";
        echo "const currentExpensesPerso = " . json_encode($expensesPerso) . ";\n";
        echo "const currentExpensesPro = " . json_encode($expensesPro) . ";\n";
        echo "let currentExpenses = currentExpensesPerso;\n";
        echo "const dbDebts = " . json_encode($debts) . ";\n";
        echo "let dbActiveChallenge = " . ($activeChallenge ? json_encode($activeChallenge) : 'null') . ";\n";
        echo "let dbCompletedChallengesCount = " . intval($completedCount) . ";\n";
        echo "const dbIsPremium = " . ((isset($_SESSION['is_premium']) && $_SESSION['is_premium']) ? 'true' : 'false') . ";\n";
        ?>
    </script>

    <script>
        // Horloge locale automatique
        function startLiveClock() {
            const el = document.getElementById("liveClock");
            if (!el) return;

            function tick() {
                const now = new Date();

                // Jour et mois en français selon le pays de l'utilisateur
                const day = now.toLocaleDateString(navigator.language, {
                    day: "numeric",
                    month: "long",
                    year: "numeric" // ✅ Année ajoutée
                });
                const time = now.toLocaleTimeString(navigator.language, {
                    hour: "2-digit",
                    minute: "2-digit",
                    second: "2-digit"
                });

                el.innerText = `${day} | ${time}`;
            }

            tick(); // Affichage immédiat
            setInterval(tick, 1000); // Mise à jour chaque seconde
        }

        startLiveClock();
    </script>

    <script>
        // On attend que la page soit prête
        document.addEventListener('DOMContentLoaded', () => {
            const lastClosed = localStorage.getItem('wari_push_modal_closed');
            const isDenied = Notification.permission === 'denied';
            const isDefault = Notification.permission === 'default';

            // 24 heures en millisecondes
            const twentyFourHours = 24 * 60 * 60 * 1000;
            const now = Date.now();

            // On affiche si : 
            // 1. Pas de permission accordée
            // 2. ET (Jamais fermé OU fermé il y a plus de 24h)
            if ((isDefault || isDenied) && (!lastClosed || (now - parseInt(lastClosed) > twentyFourHours))) {
    setTimeout(showWariPushModal, 3000);
}
        });

        function showWariPushModal() {
            if (document.getElementById('push-modal')) return; // Si le modal existe déjà, on ne fait rien
            const modalHtml = `
                <div id="push-modal" style="position:fixed; inset:0; background:#080b10; z-index:9999; display:flex; align-items:center; justify-content:center; padding:20px; backdrop-filter: blur(11px);">
                    <div style="background:#0d1117; border:1px solid #f5a623; border-radius:30px; padding:40px; text-align:center; max-width:400px; box-shadow: 0 20px 50px rgba(0,0,0,0.5);">
                        <h2 style="color:#fff; font-weight:900; letter-spacing:-1px; margin-bottom:11px; text-transform:uppercase;">RADAR DÉSACTIVÉ</h2>
                        <p style="color:#556070; font-size:14px; line-height:1.6; margin-bottom:30px;">
                            Champion&middot;ne, ton système d'alerte est éteint. 
                            Sans tes notifications, tu navigues à vue et ton budget risque de déraper.
                        </p>
                        <button id="activate-push" style="background:#f5a623; color:#000; border:none; padding:18px 30px; border-radius:15px; font-weight:800; cursor:pointer; width:100%; font-size:14px; text-transform:uppercase; transition: transform 0.2s;">
                            ACTIVER MON RADAR
                        </button>
                        <button onclick="closeWariModal()" style="background:transparent; border:none; margin-top:20px; color:#556070; font-size:11px; cursor:pointer; text-decoration:underline; text-transform:uppercase; letter-spacing:1px;">
                            Je préfère rester dans le noir
                        </button>
                    </div>
                </div>`;

            document.body.insertAdjacentHTML('beforeend', modalHtml);

            document.getElementById('activate-push').addEventListener('click', function() {
                subscribeUserToPush(true); // Manuel
                document.getElementById('push-modal').remove();
            });
        }

        function closeWariModal() {
            // On cache le modal et on s'en souvient pour 24h pour ne pas être "lourd"
            localStorage.setItem('wari_push_modal_closed', Date.now());
            document.getElementById('push-modal').remove();
        }


        async function subscribeUserToPush(isManual = false) {
            // Détection du mode PWA sur iOS (requis par Apple pour les pushs)
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
            const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
            if (isIOS && !isStandalone) {
                if (isManual) {
                    alert("Pour activer le radar sur iPhone, vous devez d'abord ajouter l'application à votre écran d'accueil :\n1. Cliquez sur l'icône de partage (carré avec une flèche vers le haut).\n2. Sélectionnez 'Sur l'écran d'accueil'.\n3. Ouvrez Wari depuis votre écran d'accueil et réessayez.");
                }
                updateRadarUI('unsupported');
                return;
            }

            // 1. Vérifier si le navigateur supporte les notifications
            if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                console.warn('Push non supporté sur ce navigateur.');
                updateRadarUI('unsupported');
                if (isManual) {
                    alert("Les notifications Push ne sont pas prises en charge par ce navigateur ou cette configuration.");
                }
                return;
            }

            try {
                const registration = await navigator.serviceWorker.ready;

                // 2. Ta clé VAPID Publique (celle que tu as dans ton PHP)
                const vapidPublicKey = 'BH9WpcuMhkSEOjnwf8KVZfDTv9Ps6nGaQ9RQ77e4D15ywgPmO7wNgTlldejjFjyWCp3PoBYareDXjlFBTdpzm40';
                const convertedVapidKey = urlBase64ToUint8Array(vapidPublicKey);

                // 3. Demander la souscription au navigateur
                const subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: convertedVapidKey
                });

                // 4. Envoyer les données à ton serveur (save_subscription.php)
                const response = await fetch('./config/save_subscription.php', {
                    method: 'POST',
                    body: JSON.stringify(subscription),
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error("Erreur serveur lors de l'enregistrement (" + response.status + ")");
                }

                console.log('✅ Radar activé avec succès !');
                updateRadarUI('active');

            } catch (error) {
                console.error('❌ Erreur lors de la souscription :', error);
                updateRadarUI('inactive');

                if (isManual) {
                    // On vérifie si c'est un refus de permission
                    if (Notification.permission === 'denied') {
                        showNotificationHelp(); // On appelle notre nouveau guide
                    } else {
                        alert("Oups ! Une petite erreur technique : " + error.message + "\nRéessaie dans un instant, Champion.");
                    }
                }
            }
        }

        function updateRadarUI(status) {
            const dot = document.getElementById('radarDot');
            const text = document.getElementById('radarText');
            const container = document.getElementById('radarStatusContainer');
            if (!dot || !text) return;

            if (status === 'active' || Notification.permission === 'granted') {
                // On vérifie quand même si on a une souscription réelle si possible
                // Mais pour l'UI, permission granted + call success = actif
                dot.className = "w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.6)] animate-pulse";
                text.innerText = "Radar ON";
                text.className = "text-[7px] font-black uppercase tracking-widest text-emerald-400";
                container.onclick = null; // Désactiver le clic si déjà actif
                container.style.cursor = 'default';
            } else if (status === 'unsupported') {
                dot.className = "w-1.5 h-1.5 rounded-full bg-red-500/30";
                text.innerText = "Radar HS";
                text.className = "text-[7px] font-black uppercase tracking-widest text-slate-600";
            } else {
                dot.className = "w-1.5 h-1.5 rounded-full bg-slate-600";
                text.innerText = "Radar OFF";
                text.className = "text-[7px] font-black uppercase tracking-widest text-slate-500";
                container.onclick = () => subscribeUserToPush(true);
                container.style.cursor = 'pointer';
            }
        }

        // Vérification initiale du statut
        if ('Notification' in window) {
            if (Notification.permission === 'granted') {
                setTimeout(() => updateRadarUI('active'), 1000);
            }
        }

        // Fonction utilitaire indispensable pour VAPID
        function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);
            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }


        function showNotificationHelp() {
            const helpHtml = `
                <div id="help-modal" style="position:fixed; inset:0; background:rgba(8,11,16,0.95); z-index:10000; display:flex; align-items:center; justify-content:center; padding:20px; backdrop-filter: blur(11px);">
                    <div style="background:#0d1117; border:2px solid #f5a623; border-radius:30px; padding:30px; text-align:center; max-width:450px; box-shadow: 0 0 40px rgba(245,166,35,0.15);">
                        <div style="margin-bottom:15px; display:flex; justify-content:center;">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#f5a623" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </div>
                        <h2 style="color:#fff; font-weight:900; margin-bottom:15px; text-transform:uppercase;">RÉGLAGES DU RADAR</h2>
                        <p style="color:#94a3b8; font-size:13px; line-height:1.6; margin-bottom:25px; text-align:left;">
                            Champion·ne, ton radar est bloqué par ton système. Pour l'activer :<br><br>
                            <strong>Sur Android :</strong> Reste appuyé sur l'icône Wari > Infos > Notifications > Autoriser.<br><br>
                            <strong>Sur iPhone :</strong> Réglages > Notifications > Wari > Autoriser.
                        </p>
                        <button onclick="document.getElementById('help-modal').remove()" style="background:#f5a623; color:#000; border:none; padding:18px; border-radius:15px; font-weight:900; cursor:pointer; width:100%; text-transform:uppercase;">J'ai compris !</button>
                    </div>
                </div>`;
            document.body.insertAdjacentHTML('beforeend', helpHtml);
        }
    </script>

    <!-- Backdrop Flouteur pour le Chat Coach -->
    <div id="coachChatBackdrop" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-[140] hidden opacity-0 transition-opacity duration-300 cursor-pointer" onclick="closeCoachChat()"></div>

    <!-- Tiroir / Drawer Chat Coach AI -->
    <div id="coachChatModal" class="fixed inset-x-0 bottom-0 z-[150] h-[80vh] md:h-[600px] max-w-md mx-auto bg-slate-950/98 backdrop-blur-xl border border-white/10 rounded-t-[2rem] shadow-2xl flex flex-col translate-y-full transition-transform duration-500 ease-out">
        <!-- Barre de glissement supérieure tactile -->
        <div class="w-12 h-1 bg-white/20 rounded-full mx-auto my-3 cursor-pointer" onclick="closeCoachChat()"></div>
        
        <!-- En-tête -->
        <div class="px-5 pb-3 flex justify-between items-center border-b border-white/5">
            <div class="flex items-center gap-3">
                <div class="relative">
                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-amber-400 via-amber-500 to-orange-600 flex items-center justify-center text-slate-950 shadow-md shadow-amber-500/20">
                        <!-- Coach mini SVG Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-slate-950" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 9h.01M16 9h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-500 border-2 border-slate-950 rounded-full"></span>
                </div>
                <div>
                    <h3 class="text-sm font-black text-white leading-tight">Coach Wari</h3>
                    <p class="text-[9px] text-amber-500 font-bold uppercase tracking-widest">Conseiller Financier</p>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <button onclick="toggleTheme()" class="modalThemeToggleBtn w-8 h-8 flex items-center justify-center rounded-xl bg-white/5 text-slate-400 hover:text-white active:scale-95 transition-all" title="Changer le thème">
                    <span class="modalThemeIcon text-slate-400"></span>
                </button>
                <button onclick="closeCoachChat()" class="p-2 rounded-xl bg-white/5 text-slate-400 hover:text-white active:scale-95 transition-all" title="Fermer">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Zone des Messages -->
        <div id="coachChatMessages" class="flex-1 overflow-y-auto px-5 py-4 space-y-3 custom-scrollbar flex flex-col">
        </div>

        <!-- Puces de Suggestions -->
        <div class="px-5 py-2 overflow-x-auto whitespace-nowrap scrollbar-none flex gap-2 border-t border-white/5">
            <button onclick="sendCoachSuggestion('Comment puis-je optimiser mon budget ce mois-ci ?')" class="px-3 py-1.5 rounded-full bg-slate-900 border border-white/5 text-slate-300 text-[10px] font-semibold hover:border-indigo-500/30 hover:text-white active:scale-95 transition-all">
                💡 Optimiser mon budget
            </button>
            <button onclick="sendCoachSuggestion('Fais-moi une analyse complète de ma situation financière actuelle.')" class="px-3 py-1.5 rounded-full bg-slate-900 border border-white/5 text-slate-300 text-[10px] font-semibold hover:border-indigo-500/30 hover:text-white active:scale-95 transition-all">
                📊 Analyse complète
            </button>
            <button onclick="sendCoachSuggestion('Quel est ton meilleur conseil de discipline financière aujourd\'hui ?')" class="px-3 py-1.5 rounded-full bg-slate-900 border border-white/5 text-slate-300 text-[10px] font-semibold hover:border-indigo-500/30 hover:text-white active:scale-95 transition-all">
                🥋 Conseil discipline
            </button>
            <button onclick="sendCoachSuggestion('Comment gérer mes dettes et les rembourser plus vite ?')" class="px-3 py-1.5 rounded-full bg-slate-900 border border-white/5 text-slate-300 text-[10px] font-semibold hover:border-indigo-500/30 hover:text-white active:scale-95 transition-all">
                💸 Gérer mes dettes
            </button>
        </div>
        
        <!-- Zone d'écriture -->
        <div class="px-4 py-3 bg-slate-950 border-t border-white/5 flex gap-2 items-center">
            <input type="text" id="coachChatInput" placeholder="Pose ta question financière..." 
                class="bg-slate-900 border border-white/10 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-amber-500/50 flex-1"
                onkeypress="handleCoachChatKeypress(event)">
            
            <button onclick="submitCoachChat()" id="coachChatSendBtn"
                class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-400 via-amber-500 to-orange-600 text-slate-950 flex items-center justify-center active:scale-95 transition-all hover:scale-105 shadow-md shadow-amber-500/10">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Script de gestion du Chat Coach AI -->
    <script>
        let coachChatHistory = [];

        // Gestion de l'affichage stratégique de la bulle d'appel à l'action
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                const bubble = document.getElementById('coachBubble');
                const isDismissed = localStorage.getItem('coach_bubble_dismissed') === 'true';
                const modal = document.getElementById('coachChatModal');
                const isChatOpen = modal && modal.classList.contains('translate-y-0');
                
                if (bubble && !isDismissed && !isChatOpen) {
                    bubble.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-2');
                    bubble.classList.add('opacity-100', 'translate-y-0');
                }
            }, 3000); // S'affiche après 3 secondes
        });

        window.dismissCoachBubble = function(event) {
            if (event) event.stopPropagation(); // Évite d'ouvrir le chat
            const bubble = document.getElementById('coachBubble');
            if (bubble) {
                bubble.classList.remove('opacity-100', 'translate-y-0');
                bubble.classList.add('opacity-0', 'pointer-events-none', 'translate-y-2');
                localStorage.setItem('coach_bubble_dismissed', 'true');
            }
        };

        // Fonction pour ajuster la position du modal et de la zone scrollable quand le clavier mobile s'ouvre
        function adjustModalForKeyboard() {
            const modal = document.getElementById('coachChatModal');
            if (!modal) return;
            
            // Si le modal est affiché (classe translate-y-0 présente)
            if (modal.classList.contains('translate-y-0')) {
                if (window.visualViewport) {
                    const vvHeight = window.visualViewport.height;
                    const keyboardHeight = window.innerHeight - vvHeight;
                    
                    if (keyboardHeight > 80) { // Seuil pour détecter un clavier virtuel
                        // Garder bottom à 0px (le navigateur aligne déjà fixed bottom-0 sur le clavier sur mobile)
                        modal.style.bottom = '0px';
                        // Ajuster la hauteur pour tenir précisément dans la zone visible
                        modal.style.height = `${vvHeight}px`;
                    } else {
                        // Clavier fermé
                        modal.style.bottom = '0px';
                        modal.style.height = ''; // Revient à 80vh ou h-[600px]
                    }
                }
                
                // Défilement automatique vers le bas
                const messagesContainer = document.getElementById('coachChatMessages');
                if (messagesContainer) {
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }
            }
        }

        // Enregistrement des écouteurs sur le visualViewport
        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', adjustModalForKeyboard);
            window.visualViewport.addEventListener('scroll', adjustModalForKeyboard);
        }

        window.openCoachChat = function() {
            const modal = document.getElementById('coachChatModal');
            const backdrop = document.getElementById('coachChatBackdrop');
            if (!modal || !backdrop) return;

            // Bloquer le défilement de l'arrière-plan
            document.body.style.overflow = 'hidden';

            // Masque la bulle si elle est visible
            const bubble = document.getElementById('coachBubble');
            if (bubble) {
                bubble.classList.remove('opacity-100', 'translate-y-0');
                bubble.classList.add('opacity-0', 'pointer-events-none', 'translate-y-2');
            }

            backdrop.classList.remove('hidden');
            setTimeout(() => {
                backdrop.classList.add('opacity-100');
            }, 10);

            modal.classList.remove('translate-y-full');
            modal.classList.add('translate-y-0');
            
            // Applique l'ajustement immédiatement au cas où
            setTimeout(adjustModalForKeyboard, 50);

            setTimeout(() => {
                document.getElementById('coachChatInput')?.focus();
            }, 300);
        };

        window.closeCoachChat = function() {
            const modal = document.getElementById('coachChatModal');
            const backdrop = document.getElementById('coachChatBackdrop');
            if (!modal || !backdrop) return;

            // Rétablir le défilement de l'arrière-plan
            document.body.style.overflow = '';

            modal.classList.remove('translate-y-0');
            modal.classList.add('translate-y-full');

            // Réinitialise les styles
            modal.style.bottom = '0px';
            modal.style.height = '';

            backdrop.classList.remove('opacity-100');
            setTimeout(() => {
                backdrop.classList.add('hidden');
            }, 300);
        };

        window.sendCoachSuggestion = function(text) {
            const input = document.getElementById('coachChatInput');
            if (input) {
                input.value = text;
                submitCoachChat();
            }
        };

        window.handleCoachChatKeypress = function(e) {
            if (e.key === 'Enter') {
                submitCoachChat();
            }
        };

        window.submitCoachChat = async function() {
            const input = document.getElementById('coachChatInput');
            const sendBtn = document.getElementById('coachChatSendBtn');
            const messagesContainer = document.getElementById('coachChatMessages');
            
            if (!input || !messagesContainer || !sendBtn) return;
            
            const text = input.value.trim();
            if (!text) return;
            
            input.value = '';
            input.disabled = true;
            sendBtn.disabled = true;
            
            appendChatMessage(text, 'user');
            
            const thinkingId = 'thinking_' + Date.now();
            appendChatThinking(thinkingId);
            
            const financialContext = getFinancialStatusContext();
            
            try {
                const formData = new FormData();
                formData.append('action', 'coach_chat');
                formData.append('message', text);
                formData.append('data', JSON.stringify(financialContext));
                formData.append('history', JSON.stringify(coachChatHistory));
                
                const res = await fetch('academy-admin/ai_gateway.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await res.json();
                
                const thinkingEl = document.getElementById(thinkingId);
                if (thinkingEl) thinkingEl.remove();
                
                if (result.response) {
                    appendChatMessage(result.response, 'coach');
                    
                    coachChatHistory.push({ role: 'user', content: text });
                    coachChatHistory.push({ role: 'model', content: result.response });
                    if (coachChatHistory.length > 20) {
                        coachChatHistory.shift();
                        coachChatHistory.shift();
                    }
                } else {
                    appendChatMessage("Désolé Champion·ne, j'ai rencontré un petit problème réseau. Reste concentré sur tes objectifs !", 'coach');
                }
            } catch (err) {
                console.error("Coach chat error:", err);
                const thinkingEl = document.getElementById(thinkingId);
                if (thinkingEl) thinkingEl.remove();
                appendChatMessage("Aïe, impossible de me connecter pour l'instant. Garde ta discipline budgétaire, c'est le plus important !", 'coach');
            } finally {
                input.disabled = false;
                sendBtn.disabled = false;
                
                // Ré-ajuste au cas où
                setTimeout(adjustModalForKeyboard, 50);
                input.focus();
            }
        };

        function appendChatMessage(text, sender) {
            const container = document.getElementById('coachChatMessages');
            if (!container) return;
            
            const msgDiv = document.createElement('div');
            if (sender === 'user') {
                msgDiv.className = "bg-amber-500 text-black self-end rounded-2xl rounded-tr-none px-3.5 py-2.5 text-xs font-semibold max-w-[85%] ml-auto shadow-sm";
            } else {
                msgDiv.className = "bg-slate-900 border border-white/5 text-slate-100 self-start rounded-2xl rounded-tl-none px-4 py-3 text-xs max-w-[85%] mr-auto leading-relaxed shadow-sm";
            }
            
            const formattedText = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            msgDiv.innerHTML = formattedText.replace(/\n/g, '<br>');
            
            container.appendChild(msgDiv);
            container.scrollTop = container.scrollHeight;
        }

        function appendChatThinking(id) {
            const container = document.getElementById('coachChatMessages');
            if (!container) return;
            
            const msgDiv = document.createElement('div');
            msgDiv.id = id;
            msgDiv.className = "bg-slate-900 border border-white/5 text-slate-400 self-start rounded-2xl rounded-tl-none px-4 py-3 text-xs max-w-[85%] mr-auto flex items-center gap-1 shadow-sm";
            msgDiv.innerHTML = `
                <span class="animate-bounce" style="animation-delay: 0.1s">•</span>
                <span class="animate-bounce" style="animation-delay: 0.2s">•</span>
                <span class="animate-bounce" style="animation-delay: 0.3s">•</span>
            `;
            
            container.appendChild(msgDiv);
            container.scrollTop = container.scrollHeight;
        }

        function getFinancialStatusContext() {
            const currency = document.getElementById("currencySelector")?.value || "F";
            const now = new Date();
            const year = now.getFullYear();
            const month = now.getMonth();
            const day = now.getDate();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const daysLeft = daysInMonth - day + 1;

            const cashAmountEl = document.getElementById("cashAmount");
            const cashValue = parseInt(cashAmountEl?.innerText.replace(/[^0-9]/g, "")) || 0;
            const budgetQuotidien = Math.round(cashValue / daysLeft);

            const totalDettes = (typeof dbDebts !== "undefined" ? dbDebts : [])
              .reduce((acc, d) => acc + (parseInt(d.amount) || 0), 0);

            const catSummary = categories
              .map(c => `- ${c.name}: ${currentExpenses[c.id] || 0} ${currency} dépensés sur ${c.name.toLowerCase().includes("projet") ? projectCapital : c.balance || 0} ${currency} prévus`)
              .join("\n");

            const debtsSummary = (typeof dbDebts !== "undefined" ? dbDebts : [])
              .map(d => `- ${d.type === 'loan' ? 'Prêt à' : 'Dette envers'} ${d.person_name} : ${parseInt(d.amount).toLocaleString()} ${currency}`)
              .join("\n") || "Aucune dette active.";

            // Récupération de l'objectif d'épargne projet ciblé par l'utilisateur
            const goalStr = localStorage.getItem("wari_vault_goal");
            let goalAmount = 0;
            let goalLabel = "";
            if (goalStr) {
                try {
                    const goal = JSON.parse(goalStr);
                    if (goal) {
                        goalAmount = parseInt(goal.amount) || 0;
                        goalLabel = goal.label || goal.name || "";
                    }
                } catch(e) {}
            }

            return {
              cash_restant: cashValue,
              jours_restants: daysLeft,
              budget_quotidien_conseille: budgetQuotidien,
              categories_details: catSummary,
              dettes_details: debtsSummary,
              total_dettes: totalDettes,
              capital_projet: projectCapital,
              devise: currency,
              objectif_projet_montant: goalAmount,
              objectif_projet_label: goalLabel
            };
        }
    </script>

    <script>
        (function() {
            const btn = document.getElementById('coachButton');
            if (!btn) return;
            
            let isDragging = false;
            let startX, startY, initialLeft, initialTop;
            let dragDistance = 0;
            
            function onDragStart(clientX, clientY) {
                isDragging = true;
                startX = clientX;
                startY = clientY;
                
                const rect = btn.getBoundingClientRect();
                initialLeft = rect.left;
                initialTop = rect.top;
                
                btn.style.bottom = 'auto';
                btn.style.right = 'auto';
                btn.style.left = initialLeft + 'px';
                btn.style.top = initialTop + 'px';
                
                dragDistance = 0;
            }
            
            function onDragMove(clientX, clientY) {
                if (!isDragging) return;
                const dx = clientX - startX;
                const dy = clientY - startY;
                
                dragDistance += Math.abs(dx) + Math.abs(dy);
                
                const newLeft = Math.max(10, Math.min(window.innerWidth - 60, initialLeft + dx));
                const newTop = Math.max(10, Math.min(window.innerHeight - 60, initialTop + dy));
                
                btn.style.left = newLeft + 'px';
                btn.style.top = newTop + 'px';
            }
            
            function onDragEnd(e) {
                if (!isDragging) return;
                isDragging = false;
                
                if (dragDistance > 10) {
                    e.preventDefault();
                    btn.addEventListener('click', preventClick, { capture: true });
                    setTimeout(() => {
                        btn.removeEventListener('click', preventClick, { capture: true });
                    }, 50);
                }
            }
            
            function preventClick(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            // Touch Events
            btn.addEventListener('touchstart', (e) => {
                const touch = e.touches[0];
                onDragStart(touch.clientX, touch.clientY);
            }, { passive: true });
            
            btn.addEventListener('touchmove', (e) => {
                const touch = e.touches[0];
                onDragMove(touch.clientX, touch.clientY);
            }, { passive: true });
            
            btn.addEventListener('touchend', onDragEnd);
            
            // Mouse Events
            btn.addEventListener('mousedown', (e) => {
                onDragStart(e.clientX, e.clientY);
                e.preventDefault();
            });
            
            document.addEventListener('mousemove', (e) => {
                onDragMove(e.clientX, e.clientY);
            });
            
            document.addEventListener('mouseup', onDragEnd);
        })();
    </script>

    <!-- MODAL DE PRÉSENTATION WARI PREMIUM -->
    <div id="premiumIntroModal" class="fixed inset-0 bg-slate-950/90 backdrop-blur-sm hidden items-center justify-center p-4 z-[170]">
        <div class="glass-card w-full max-w-sm p-6 border border-amber-500/30 shadow-2xl relative flex flex-col text-center" onclick="event.stopPropagation()">
            
            <!-- Indicateurs de progression -->
            <div class="flex justify-center gap-1.5 mb-4 select-none shrink-0">
                <div class="premium-intro-dot w-2 h-2 rounded-full bg-amber-500 transition-colors" data-step="1"></div>
                <div class="premium-intro-dot w-2 h-2 rounded-full bg-slate-700 transition-colors" data-step="2"></div>
                <div class="premium-intro-dot w-2 h-2 rounded-full bg-slate-700 transition-colors" data-step="3"></div>
                <div class="premium-intro-dot w-2 h-2 rounded-full bg-slate-700 transition-colors" data-step="4"></div>
            </div>

            <!-- Contenu de la diapositive -->
            <div id="premiumIntroContent" class="flex-1 flex flex-col justify-center min-h-[160px] mb-6">
                <!-- Rempli par JavaScript -->
            </div>

            <!-- Zone de boutons -->
            <div id="premiumIntroButtons" class="shrink-0 flex flex-col gap-2">
                <!-- Rempli par JavaScript -->
            </div>
        </div>
    </div>

    <!-- MODAL PREMIUM CÉRÉMONIE FIN DE MOIS -->
    <div id="endOfMonthModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden items-center justify-center p-4 z-[160]">
        <div class="glass-card w-full max-w-md p-5 border border-slate-700 shadow-2xl relative flex flex-col max-h-[90vh]" onclick="event.stopPropagation()">
            <div class="text-center shrink-0 pb-3 border-b border-slate-700/60">
                <div class="w-12 h-12 mx-auto bg-amber-500/10 text-amber-400 rounded-full flex items-center justify-center mb-2 border border-amber-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-white font-bold text-lg">Bilan de fin de mois</h3>
                <p class="text-slate-400 text-[11px] mt-1 leading-normal">
                    Félicitations ! Le mois dernier s'est terminé et tu as réussi à préserver de l'argent. Choisis ce que tu souhaites en faire pour démarrer ce nouveau mois.
                </p>
            </div>

            <!-- Liste des catégories éligibles -->
            <div id="eomCategoryList" class="my-4 space-y-3 overflow-y-auto pr-1 flex-1 custom-scrollbar">
                <!-- Rempli dynamiquement en JS -->
            </div>

            <div class="shrink-0 pt-3 border-t border-slate-700/60">
                <button onclick="submitEndOfMonth()" class="w-full py-3 bg-gradient-to-r from-amber-500 to-yellow-600 text-black rounded-xl font-bold text-sm shadow-md active:scale-95 transition-all">
                    Valider mes choix
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL AIDE CONTEXTUELLE -->
    <div id="helpModal" class="fixed inset-0 bg-slate-900/90 backdrop-blur-sm hidden items-center justify-center p-4 z-[140]" onclick="closeHelpModal()">
        <div class="glass-card w-full max-w-sm p-5 border border-slate-700 shadow-2xl relative" onclick="event.stopPropagation()">
            <button onclick="closeHelpModal()" class="absolute top-4 right-4 text-slate-400 hover:text-white">✕</button>
            <div id="helpIcon" class="w-10 h-10 rounded-xl bg-slate-800 flex items-center justify-center mb-3 text-amber-500"></div>
            <h3 id="helpTitle" class="text-white font-bold text-lg mb-2">Titre</h3>
            <p id="helpDesc" class="text-slate-400 text-sm mb-5 leading-relaxed">Description courte.</p>
            <a href="guide/index.php" class="block w-full py-2.5 bg-amber-500 text-slate-950 text-center rounded-xl font-bold text-sm hover:bg-amber-400 transition-colors">
                Lire le guide complet
            </a>
        </div>
    </div>

    <!-- MODAL ONBOARDING (BIENVENUE) -->
    <div id="onboardingModal" class="fixed inset-0 bg-slate-950/95 backdrop-blur-md hidden items-center justify-center p-4 z-[150]">
        <div class="glass-card w-full max-w-md p-6 border border-amber-500/20 shadow-2xl relative text-center">
            <div class="w-16 h-16 mx-auto bg-gradient-to-br from-amber-400 to-yellow-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg shadow-amber-500/20">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
            </div>
            <h2 class="text-2xl font-bold text-white mb-2 font-serif">Bienvenue sur Wari</h2>
            <p class="text-slate-400 text-sm mb-6 leading-relaxed">
                Plus qu'une application, Wari est une méthode pour reprendre le contrôle de tes finances. Apprends à maîtriser ton <strong class="text-emerald-400">Coffre-fort</strong> et ton <strong class="text-amber-500">Train de vie</strong>.
            </p>
            <div class="flex flex-col gap-3">
                <a href="guide/index.php" class="block w-full py-3 bg-gradient-to-r from-amber-500 to-yellow-600 text-black rounded-xl font-bold text-sm shadow-md active:scale-95 transition-all">
                    Découvrir comment ça marche (Guide)
                </a>
                <button onclick="closeOnboarding()" class="block w-full py-3 bg-slate-800 text-slate-300 rounded-xl font-bold text-sm hover:bg-slate-700 active:scale-95 transition-all">
                    J'ai compris, fermer
                </button>
            </div>
        </div>
    </div>

    <script src="./assets/main.js?v=137"></script>
    <script>
        // Logique Onboarding
        document.addEventListener('DOMContentLoaded', () => {
            if (!localStorage.getItem('wari_onboarding_seen')) {
                const onboardingModal = document.getElementById('onboardingModal');
                if (onboardingModal) {
                    onboardingModal.classList.remove('hidden');
                    onboardingModal.classList.add('flex');
                }
            }
        });

        function closeOnboarding() {
            localStorage.setItem('wari_onboarding_seen', 'true');
            const onboardingModal = document.getElementById('onboardingModal');
            if (onboardingModal) {
                onboardingModal.classList.add('hidden');
                onboardingModal.classList.remove('flex');
            }
        }

        // Logique Aide Contextuelle (Tooltips)
        const helpData = {
            'coffre-fort': {
                title: 'Banque (Coffre-Fort)',
                icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>',
                desc: 'Ton épargne intouchable. L\'argent qui entre ici ne peut pas être dépensé directement. C\'est ce qui te garantit la sécurité et tes investissements futurs.'
            },
            'train-de-vie': {
                title: 'Poche (Train de vie)',
                icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 12V8H6a2 2 0 0 1-2-2c0-1.1.9-2 2-2h12v4"></path><path d="M4 6v12c0 1.1.9 2 2 2h14v-4"></path><path d="M18 12a2 2 0 0 0-2 2c0 1.1.9 2 2 2h4v-4h-4z"></path></svg>',
                desc: 'C\'est l\'argent que tu as le droit de dépenser au quotidien. L\'objectif est que ce solde atteigne rigoureusement 0 avant ton prochain revenu.'
            },
            'dette': {
                title: 'Le carnet de dettes',
                icon: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>',
                desc: 'Renseigner vos prêts et vos emprunts, tout ce que vous prêtez et tout ce qu\'on vous prête, votre budget n\'en sera que plus sain. '
            }
        };

        function openHelpModal(type) {
            const data = helpData[type];
            if (!data) return;
            document.getElementById('helpTitle').innerText = data.title;
            document.getElementById('helpDesc').innerText = data.desc;
            document.getElementById('helpIcon').innerHTML = data.icon;
            
            const modal = document.getElementById('helpModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeHelpModal() {
            const modal = document.getElementById('helpModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    </script>
    <?php if (isset($_SESSION['recharge_success'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                showToastMessage("Abonnement prolongé avec succès ! 🚀", "success");
            });
        </script>
        <?php unset($_SESSION['recharge_success']); ?>
    <?php endif; ?>

    <?php
    // Logique PHP pour décider de l'affichage du modal Défis
    $showChallengesModal = false;
    if (isset($userData['feedback_status']) && (int)$userData['feedback_status'] === 0) {
        $dateInscription = $userData['date_inscription'] ?? null;
        $lastPrompt = $userData['last_feedback_prompt_at'] ?? null;
        
        if ($dateInscription) {
            $diffInscription = time() - strtotime($dateInscription);
            $daysInscription = $diffInscription / (24 * 3600);
            
            if ($daysInscription >= 3) {
                if ($lastPrompt === null) {
                    $showChallengesModal = true;
                } else {
                    $diffPrompt = time() - strtotime($lastPrompt);
                    $daysPrompt = $diffPrompt / (24 * 3600);
                    if ($daysPrompt >= 14) {
                        $showChallengesModal = true;
                    }
                }
            }
        }
    }
    ?>

    <?php if ($showChallengesModal): ?>
    <!-- MODAL DÉFIS UTILISATEURS -->
    <div id="challengesModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 z-[145]">
        <div class="w-full max-w-sm rounded-3xl p-5 border shadow-2xl relative transition-all duration-300 bg-slate-900 border-slate-800 text-slate-100 modal-challenges-container"
             style="font-family: 'Quicksand', sans-serif;">
            
            <button onclick="dismissChallengesModal()" class="absolute top-4 right-4 text-slate-400 hover:text-white transition-colors text-base font-bold">✕</button>
            
            <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center mb-3">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#C9A84C" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
            </div>
            
            <h3 class="font-extrabold text-xl mb-1 tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-yellow-600">Partagez vos défis</h3>
            
            <p class="text-xs mb-4 text-slate-400 leading-relaxed font-medium">
                Quel défi ou difficulté rencontrez-vous dans l'utilisation de Wari ?
            </p>
            
            <form id="challengesForm" onsubmit="submitChallenge(event)">
                <div class="grid grid-cols-2 gap-2 mb-4">
                    <label class="category-chip-label">
                        <input type="radio" name="challenge_category" value="repartition" checked class="hidden">
                        <span class="category-chip">Répartition</span>
                    </label>
                    <label class="category-chip-label">
                        <input type="radio" name="challenge_category" value="coach" class="hidden">
                        <span class="category-chip">Coach IA</span>
                    </label>
                    <label class="category-chip-label">
                        <input type="radio" name="challenge_category" value="academy" class="hidden">
                        <span class="category-chip">Académie</span>
                    </label>
                    <label class="category-chip-label">
                        <input type="radio" name="challenge_category" value="vecu" class="hidden">
                        <span class="category-chip">Vécu</span>
                    </label>
                    <label class="category-chip-label col-span-2">
                        <input type="radio" name="challenge_category" value="autre" class="hidden">
                        <span class="category-chip">Autre</span>
                    </label>
                </div>
                
                <div class="relative mb-4">
                    <textarea id="challenge_message" name="message" rows="3" maxlength="500" required
                              class="w-full text-sm rounded-xl p-3 bg-slate-950 border border-slate-800 text-white placeholder-slate-700 focus:outline-none focus:border-amber-500/50 transition-colors resize-none textarea-challenges"
                              placeholder="Décrivez votre problème ou suggestion ici..."
                              oninput="updateMessageCount()"></textarea>
                    <span id="charCount" class="absolute bottom-2 right-3 text-[9px] text-slate-600 font-bold char-count-span">0 / 500</span>
                </div>
                
                <button type="submit" id="btnSubmitChallenge"
                        class="w-full py-3 bg-gradient-to-r from-yellow-400 to-yellow-600 text-slate-950 rounded-xl font-bold text-sm shadow-md active:scale-95 transition-all flex items-center justify-center gap-1.5 hover:from-yellow-300 hover:to-yellow-500">
                    <span>Envoyer mon avis</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </button>
            </form>
        </div>
    </div>
    
    <style>
        /* Styles adaptatifs pour le modal Défis */
        .modal-challenges-container {
            transition: background-color 0.3s, border-color 0.3s, color 0.3s;
        }
        
        .textarea-challenges {
            transition: background-color 0.3s, border-color 0.3s, color 0.3s;
        }
        
        /* Compatibilité mode clair basée sur la classe .light-mode sur le document */
        .light-mode .modal-challenges-container {
            background-color: #ffffff !important;
            border-color: rgba(15, 23, 42, 0.08) !important;
            color: #0f172a !important;
        }
        
        .light-mode .textarea-challenges {
            background-color: #f8fafc !important;
            border-color: rgba(15, 23, 42, 0.08) !important;
            color: #0f172a !important;
            placeholder-color: #94a3b8 !important;
        }
        
        .light-mode .char-count-span {
            color: #94a3b8 !important;
        }
        
        .category-chip-label {
            cursor: pointer;
            display: block;
        }
        
        .category-chip {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px 14px;
            border-radius: 14px;
            font-size: 13px;
            font-weight: 700;
            text-align: center;
            background-color: #0a0a0a;
            border: 1px solid rgba(255, 255, 255, 0.03);
            color: #94a3b8;
            transition: all 0.2s ease;
            user-select: none;
        }
        
        .light-mode .category-chip {
            background-color: #f1f5f9;
            border-color: rgba(15, 23, 42, 0.04);
            color: #475569;
        }
        
        .category-chip-label input[type="radio"]:checked + .category-chip {
            background-color: rgba(201, 168, 76, 0.15) !important;
            border-color: #C9A84C !important;
            color: #C9A84C !important;
        }
        
        .light-mode .category-chip-label input[type="radio"]:checked + .category-chip {
            background-color: rgba(201, 168, 76, 0.1) !important;
            border-color: #C9A84C !important;
            color: #785a1a !important;
        }
    </style>
    
    <script>
        function updateMessageCount() {
            const textarea = document.getElementById('challenge_message');
            const countSpan = document.getElementById('charCount');
            if (textarea && countSpan) {
                countSpan.innerText = `${textarea.value.length} / 500`;
                
                // Auto-grow height logique
                textarea.style.height = 'auto';
                textarea.style.height = Math.min(textarea.scrollHeight, 180) + 'px';
                textarea.style.overflowY = textarea.scrollHeight > 180 ? 'auto' : 'hidden';
            }
        }
        
        async function dismissChallengesModal() {
            try {
                await fetch('config/save_user_challenge.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'dismiss' })
                });
            } catch (e) {
                console.error("Erreur lors de la fermeture du popup:", e);
            }
            
            const modal = document.getElementById('challengesModal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }
        
        async function submitChallenge(event) {
            event.preventDefault();
            const form = document.getElementById('challengesForm');
            const categoryInput = form.querySelector('input[name="challenge_category"]:checked');
            const messageInput = document.getElementById('challenge_message');
            const btn = document.getElementById('btnSubmitChallenge');
            
            if (!categoryInput || !messageInput || !messageInput.value.trim()) return;
            
            const originalBtnHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = `<span class="inline-block w-4 h-4 border-2 border-slate-950 border-t-transparent rounded-full animate-spin"></span> Envoi...`;
            
            try {
                const response = await fetch('config/save_user_challenge.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'submit',
                        category: categoryInput.value,
                        message: messageInput.value.trim()
                    })
                });
                
                const result = await response.json();
                if (result.success) {
                    btn.className = "w-full py-3 bg-emerald-500 text-slate-950 rounded-xl font-bold text-xs shadow-md transition-all flex items-center justify-center gap-1.5";
                    btn.innerHTML = `✓ Merci pour votre retour !`;
                    
                    setTimeout(() => {
                        const modal = document.getElementById('challengesModal');
                        if (modal) {
                            modal.classList.add('hidden');
                            modal.classList.remove('flex');
                        }
                    }, 1500);
                } else {
                    alert(result.error || "Une erreur est survenue.");
                    btn.disabled = false;
                    btn.innerHTML = originalBtnHtml;
                }
            } catch (e) {
                console.error(e);
                alert("Impossible de se connecter au serveur.");
                btn.disabled = false;
                btn.innerHTML = originalBtnHtml;
            }
        }
    </script>
    <?php endif; ?>
</body>

</html>
```

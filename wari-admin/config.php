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

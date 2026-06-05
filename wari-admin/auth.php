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

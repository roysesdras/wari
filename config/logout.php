<?php
session_start();
require 'db.php';

// ✅ Ici on supprime VRAIMENT le cookie — déconnexion volontaire
if (isset($_COOKIE['wari_remember'])) {
    $pdo->prepare("UPDATE wari_users SET remember_token = NULL, remember_expires = NULL WHERE remember_token = ?")
        ->execute([$_COOKIE['wari_remember']]);

    setcookie('wari_remember', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}

$_SESSION = array();

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

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("Location: auth.php");
exit();

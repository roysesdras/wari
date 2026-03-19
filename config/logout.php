<?php
session_start();

// 1. On vide les variables de session
$_SESSION = array();

// 2. On détruit le cookie de session
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

// 3. On détruit la session
session_destroy();

// 4. On ajoute des headers pour empêcher le retour arrière
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// 5. Redirection
header("Location: auth.php");
exit();

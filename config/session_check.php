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

// 2. LA SÉCURITÉ CRITIQUE (Ce qu'il manquait)
// Si après la tentative de cookie, il n'y a TOUJOURS PAS de session :
if (!isset($_SESSION['user_id'])) {
    // Si c'est un appel API (JSON)
    if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
        http_response_code(403);
        exit(json_encode(['error' => 'Non autorisé']));
    } 
    
    // Si c'est un utilisateur qui navigue (on le renvoie au login)
    // Ne redirige pas si on est déjà sur la page de login pour éviter les boucles
    if (basename($_SERVER['PHP_SELF']) !== 'login.php') {
        header('Location: login.php');
        exit();
    }
}
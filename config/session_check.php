<?php
// config/session_check.php
if (!isset($pdo)) require 'db.php';

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
            'samesite' => 'Strict',
        ]);
    } else {
        // Cookie invalide — on le supprime
        setcookie('wari_remember', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }
}

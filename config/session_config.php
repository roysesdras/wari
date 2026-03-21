<?php
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

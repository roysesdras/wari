<?php
// Configuration session pour 90 jours comme WhatsApp
ini_set('session.gc_maxlifetime', 90 * 24 * 3600); // 90 jours en secondes
ini_set('session.cookie_lifetime', 90 * 24 * 3600); // 90 jours
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');

// Démarrer la session avec ces paramètres
session_start();

// Prolonger la session à chaque accès (comme WhatsApp)
if (isset($_SESSION['user_id'])) {
    // Renouveler la session pour 90 jours de plus
    setcookie(session_name(), session_id(), time() + (90 * 24 * 3600), '/', '', true, true);
}
?>
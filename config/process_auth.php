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
 * Enregistre une tentative dans l'audit
 */
function logAuthAttempt($pdo, $action, $email = null, $userId = null, $details = null)
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    $stmt = $pdo->prepare("
        INSERT INTO wari_audit (action, user_id, email, ip, user_agent, details) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$action, $userId, $email, $ip, substr($userAgent, 0, 255), $details]);
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

            header('Location: ../index.php');
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
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("INSERT INTO wari_users (email, password, commande_id) VALUES (?, ?, ?)");
                $stmt->execute([$email, $hashedPassword, $commande_id]);

                $stmt = $pdo->prepare("UPDATE wari_licences SET statut = 'utilise' WHERE commande_id = ?");
                $stmt->execute([$commande_id]);

                header('Location: auth.php?success=1');
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

            header('Location: ../index.php');
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

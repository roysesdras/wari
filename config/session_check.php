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
    if ($userLicense) {
        $userEmail = $userLicense['email'];
        $licence_statut = $userLicense['licence_statut'];
        $date_expiration = $userLicense['date_expiration'];
        $_SESSION['is_premium'] = ($date_expiration !== null && strtotime($date_expiration) >= time());

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

            // Restriction d'expiration de l'abonnement
            // Test Agile : Uniquement info@rebonly.com jusqu'au 31 Décembre 2026 inclus.
            // Lancement Global automatique : Pour TOUT LE MONDE à partir du 1er Janvier 2027.
            $transitionDate = strtotime('2027-01-01 00:00:00');
            $isTransitionActive = (time() >= $transitionDate);

            if ($userEmail === 'info@rebonly.com' || $isTransitionActive) {
                $isExpired = (
                    $date_expiration === null || 
                    strtotime($date_expiration) < time()
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
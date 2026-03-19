<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_unset();
    session_destroy();
    session_start();
}

require 'db.php';
require 'no_cache.php';

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

            // ✅ On renouvelle le cookie pour 30 jours supplémentaires
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
    elseif ($action === 'login') {
        $stmt = $pdo->prepare("SELECT * FROM wari_users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {

            // Vérification suspension
            $stmtLic = $pdo->prepare("SELECT statut FROM wari_licences WHERE commande_id = ?");
            $stmtLic->execute([$user['commande_id']]);
            $licence = $stmtLic->fetch();

            if ($licence && $licence['statut'] === 'suspendu') {
                die("Accès suspendu. Contactez l'administrateur.");
            }

            // ✅ Session
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_email'] = $user['email'];

            // ✅ Remember me — cookie 30 jours
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));

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
            die("Identifiants incorrects.");
        }
    }
}

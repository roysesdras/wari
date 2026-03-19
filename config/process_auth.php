<?php
// process_auth.php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_unset();
    session_destroy();
    session_start();
}
require 'db.php';
require 'no_cache.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    if ($action === 'register') {
        $commande_id = $_POST['commande_id'];

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
    } elseif ($action === 'login') {
        $stmt = $pdo->prepare("SELECT * FROM wari_users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {

            // ✅ Vérification suspension
            $stmtLic = $pdo->prepare("SELECT statut FROM wari_licences WHERE commande_id = ?");
            $stmtLic->execute([$user['commande_id']]);
            $licence = $stmtLic->fetch();

            if ($licence && $licence['statut'] === 'suspendu') {
                die("Accès suspendu. Contactez l'administrateur.");
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            header('Location: ../index.php');
            exit();
        } else {
            die("Identifiants incorrects.");
        }
    }
}

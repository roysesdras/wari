<?php
session_start();
require 'session_config.php';
require 'db.php';
require 'no_cache.php';
require 'session_check.php'; // ← ajout // Ton fichier de connexion PDO

if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['success' => false, 'message' => 'Non connecté']));
}

$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['endpoint'])) {
    // On extrait les clés de l'objet de souscription
    $endpoint = $data['endpoint'];
    $p256dh = $data['keys']['p256dh'];
    $auth = $data['keys']['auth'];

    // On vérifie si cet abonnement existe déjà pour éviter les doublons
    $check = $pdo->prepare("SELECT id FROM wari_subscriptions WHERE user_id = ? AND endpoint = ?");
    $check->execute([$userId, $endpoint]);

    if (!$check->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO wari_subscriptions (user_id, endpoint, p256dh, auth) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $endpoint, $p256dh, $auth]);
    }

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
}

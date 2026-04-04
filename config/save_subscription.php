<?php
// 1. Toujours le monitoring et la config session en premier
require_once __DIR__ . '/../wari_monitoring.php'; 
require 'session_config.php'; // Gère le session_start() et les 90 jours

// 2. Pas besoin de session_start() ici si session_config le fait déjà
require 'db.php';
require 'no_cache.php';

// 3. Le check de session (qui renverra le 403 propre si déconnecté)
require 'session_check.php'; 

// À partir d'ici, on est SÛR que l'utilisateur est connecté grâce à session_check
$userId = $_SESSION['user_id'];

// 4. Récupération des données JSON
$data = json_decode(file_get_contents('php://input'), true);

if ($data && isset($data['endpoint'])) {
    $endpoint = $data['endpoint'];
    $p256dh   = $data['keys']['p256dh'] ?? '';
    $auth     = $data['keys']['auth'] ?? '';

    // Vérification des doublons
    $check = $pdo->prepare("SELECT id FROM wari_subscriptions WHERE user_id = ? AND endpoint = ?");
    $check->execute([$userId, $endpoint]);

    if (!$check->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO wari_subscriptions (user_id, endpoint, p256dh, auth) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $endpoint, $p256dh, $auth]);
    }

    echo json_encode(['success' => true]);
} else {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
}
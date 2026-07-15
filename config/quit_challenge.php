<?php
// /var/www/wari.digiroys.com/config/quit_challenge.php
require 'session_config.php';

require 'db.php';
require 'no_cache.php';
require 'session_check.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

if (!isset($_SESSION['is_premium']) || !$_SESSION['is_premium']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Cette fonctionnalité requiert un abonnement Wari Premium']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        UPDATE wari_savings_challenges 
        SET status = 'abandoned' 
        WHERE user_id = ? AND status = 'active'
    ");
    $stmt->execute([$user_id]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

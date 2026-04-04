<?php
// get_vault_history.php
header('Content-Type: application/json'); // Indispensable pour le JS
session_start();
require 'session_config.php';
require 'db.php';
require 'no_cache.php';
require 'session_check.php'; // ← ajout
require_once __DIR__ . '/../wari_monitoring.php';  // ← TOUJOURS EN PREMIER


if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT type, amount, label, DATE_FORMAT(created_at, '%d %b') as date 
                           FROM wari_vault_history 
                           WHERE user_id = ? 
                           ORDER BY created_at DESC LIMIT 20");
    $stmt->execute([$_SESSION['user_id']]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'history' => $history]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

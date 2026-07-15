<?php
// get_vault_history.php
header('Content-Type: application/json'); // Indispensable pour le JS
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
    $walletType = $_GET['wallet_type'] ?? 'perso';
    if (!in_array($walletType, ['perso', 'pro'])) {
        $walletType = 'perso';
    }
    $stmt = $pdo->prepare("SELECT type, amount, label, DATE_FORMAT(created_at, '%d %b') as date 
                           FROM wari_vault_history 
                           WHERE user_id = ? AND wallet_type = ?
                           ORDER BY created_at DESC LIMIT 20");
    $stmt->execute([$_SESSION['user_id'], $walletType]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'history' => $history]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

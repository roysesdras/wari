<?php
// add_vault_transaction.php
require 'session_config.php';
 // ← en premier, configure ET démarre la session
require 'db.php';
require 'session_check.php'; // ← vérifie le cookie si session expirée
require 'no_cache.php';
require_once __DIR__ . '/../wari_monitoring.php';  // ← TOUJOURS EN PREMIER

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['type'], $input['amount'], $input['label'])) {
    try {
        $walletType = $input['wallet_type'] ?? 'perso';
        if (!in_array($walletType, ['perso', 'pro'])) {
            $walletType = 'perso';
        }
        $stmt = $pdo->prepare("INSERT INTO wari_vault_history (user_id, type, amount, label, wallet_type) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            $input['type'],
            intval($input['amount']),
            htmlspecialchars($input['label']),
            $walletType
        ]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

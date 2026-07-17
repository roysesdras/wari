<?php
require 'session_config.php';
 // ← en premier, configure ET démarre la session
require 'db.php';
require 'session_check.php'; // ← vérifie le cookie si session expirée
require 'no_cache.php';
require_once __DIR__ . '/../wari_monitoring.php';  // ← TOUJOURS EN PREMIER

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit();
}

$data   = json_decode(file_get_contents('php://input'), true);
$amount = isset($data['amount']) ? (int)$data['amount'] : 0;

if ($amount <= 0) {
    echo json_encode(['success' => false, 'error' => 'Montant invalide']);
    exit();
}

try {
    $walletType = $data['wallet_type'] ?? 'perso';
    if (!in_array($walletType, ['perso', 'pro'])) {
        $walletType = 'perso';
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO wari_distributions (user_id, amount, wallet_type) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$_SESSION['user_id'], $amount, $walletType]);
    $distributionId = $pdo->lastInsertId();

    if (isset($data['details']) && is_array($data['details'])) {
        $stmtDetail = $pdo->prepare("
            INSERT INTO wari_distribution_details (distribution_id, category_name, amount)
            VALUES (?, ?, ?)
        ");
        foreach ($data['details'] as $detail) {
            $catName = isset($detail['category_name']) ? trim($detail['category_name']) : '';
            $catAmount = isset($detail['amount']) ? intval($detail['amount']) : 0;
            if ($catName !== '' && $catAmount > 0) {
                $stmtDetail->execute([$distributionId, $catName, $catAmount]);
            }
        }
    }

    $pdo->commit();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

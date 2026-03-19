<?php
// get_vault_history.php
session_start();
require 'db.php';

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

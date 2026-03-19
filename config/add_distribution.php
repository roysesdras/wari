<?php
session_start();
require 'db.php';

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
    $stmt = $pdo->prepare("
        INSERT INTO wari_distributions (user_id, amount) 
        VALUES (?, ?)
    ");
    $stmt->execute([$_SESSION['user_id'], $amount]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

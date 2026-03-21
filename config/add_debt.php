<?php
session_start();
require 'session_config.php'; // ← en premier, configure ET démarre la session
require 'db.php';
require 'session_check.php'; // ← vérifie le cookie si session expirée
require 'no_cache.php';

if (!isset($_SESSION['user_id'])) exit();

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (isset($data['amount'], $data['person'], $data['type'])) {
    $due_date = !empty($data['due_date']) ? $data['due_date'] : null;

    $stmt = $pdo->prepare("INSERT INTO wari_debts (user_id, person_name, amount, type, due_date) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['user_id'],
        htmlspecialchars($data['person']),
        intval($data['amount']),
        $data['type'],
        $due_date
    ]);
    echo json_encode(['success' => true]);
}

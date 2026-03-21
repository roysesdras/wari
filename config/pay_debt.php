<?php
session_start();
require 'session_config.php';
require 'db.php';
require 'no_cache.php';
require 'session_check.php'; // ← ajout

if (!isset($_SESSION['user_id'])) exit();

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (isset($data['id'])) {
    $stmt = $pdo->prepare("UPDATE wari_debts SET status = 'paid' WHERE id = ? AND user_id = ?");
    $stmt->execute([$data['id'], $_SESSION['user_id']]);
    echo json_encode(['success' => true]);
}

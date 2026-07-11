<?php
session_start();
require 'session_config.php';
require 'db.php';
require 'no_cache.php';
require 'session_check.php'; // ← ajout
require_once __DIR__ . '/../wari_monitoring.php';  // ← TOUJOURS EN PREMIER

$data = json_decode(file_get_contents('php://input'), true);
$userId = $_SESSION['user_id'];
$debtId = $data['id'];
$amount = intval($data['amount']);
$type = $data['type']; // 'debt' ou 'loan'
$catId = isset($data['category_id']) ? intval($data['category_id']) : 2;

// 0. Récupérer le nom du créancier/débiteur
$stmtDebtInfo = $pdo->prepare("SELECT person_name FROM wari_debts WHERE id = ? AND user_id = ?");
$stmtDebtInfo->execute([$debtId, $userId]);
$debtInfo = $stmtDebtInfo->fetch();
$personName = $debtInfo ? $debtInfo['person_name'] : '';

// 1. Mise à jour de la dette
$stmt = $pdo->prepare("UPDATE wari_debts SET amount = amount - ? WHERE id = ? AND user_id = ?");
$stmt->execute([$amount, $debtId, $userId]);

// 2. Si le montant tombe à 0 ou moins, on marque comme payé
$pdo->prepare("UPDATE wari_debts SET status = 'paid' WHERE amount <= 0 AND id = ?")->execute([$debtId]);

// 3. Enregistrement de l'impact sur le budget
$desc = ($type == 'debt') ? "Remboursement dette envers {$personName}" : "Récupération prêt à {$personName}";
$finalAmount = ($type == 'debt') ? $amount : -$amount;
$wType = isset($data['wallet_type']) ? $data['wallet_type'] : 'perso';

$stmtExp = $pdo->prepare("INSERT INTO wari_expenses (user_id, category_id, amount, description, wallet_type) VALUES (?, ?, ?, ?, ?)");
$stmtExp->execute([$userId, $catId, $finalAmount, $desc, $wType]);

echo json_encode(['success' => true]);

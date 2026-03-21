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

// 1. Mise à jour de la dette
$stmt = $pdo->prepare("UPDATE wari_debts SET amount = amount - ? WHERE id = ? AND user_id = ?");
$stmt->execute([$amount, $debtId, $userId]);

// 2. Si le montant tombe à 0 ou moins, on marque comme payé
$pdo->prepare("UPDATE wari_debts SET status = 'paid' WHERE amount <= 0 AND id = ?")->execute([$debtId]);

// 3. Enregistrement de l'impact sur le budget
// Si c'est une DETTE (je paie), c'est une dépense.
// Si c'est une CRÉANCE (on me paie), on pourrait l'ajouter en revenu, mais restons sur la dépense pour l'instant.
$desc = ($type == 'debt') ? "Remboursement dette" : "Récupération prêt";
$catId = 99; // On peut créer une catégorie spéciale "Dettes" ou utiliser une existante

$stmtExp = $pdo->prepare("INSERT INTO wari_expenses (user_id, category_id, amount, description) VALUES (?, ?, ?, ?)");
$stmtExp->execute([$userId, $catId, $amount, $desc]);

echo json_encode(['success' => true]);

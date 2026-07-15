<?php
// config/delete_expense.php
error_reporting(0);
ini_set('display_errors', 0);

require 'session_config.php';

require 'db.php';
require 'no_cache.php';
require 'session_check.php';
require_once __DIR__ . '/../wari_monitoring.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['expense_id'])) {
    echo json_encode(['success' => false, 'error' => 'ID manquant']);
    exit();
}

$user_id = $_SESSION['user_id'];
$expense_id = intval($data['expense_id']);

try {
    // Vérifier l'existence et la date de la dépense
    $stmt = $pdo->prepare("SELECT * FROM wari_expenses WHERE id = ? AND user_id = ?");
    $stmt->execute([$expense_id, $user_id]);
    $expense = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$expense) {
        echo json_encode(['success' => false, 'error' => 'Dépense introuvable']);
        exit();
    }

    $walletType = $expense['wallet_type'] ?? 'perso';
    $amount = (int)$expense['amount'];
    $category_id = (int)$expense['category_id'];

    // Vérification du délai de 24h
    $expenseDate = strtotime($expense['date_expense']);
    $now = time();
    $diffHours = ($now - $expenseDate) / 3600;

    if ($diffHours > 24) {
        echo json_encode(['success' => false, 'error' => 'Le délai de grâce de 24h est dépassé. Cette dépense ne peut plus être annulée.']);
        exit();
    }

    // Récupérer le budget_data pour voir si c'est un "Projet"
    if ($walletType === 'pro') {
        $stmtUser = $pdo->prepare("SELECT budget_data_pro as budget_data, project_capital_pro as project_capital FROM wari_users WHERE id = ?");
    } else {
        $stmtUser = $pdo->prepare("SELECT budget_data, project_capital FROM wari_users WHERE id = ?");
    }
    $stmtUser->execute([$user_id]);
    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if ($userData && $userData['budget_data']) {
        $budgetData = json_decode($userData['budget_data'], true);
        $categories = $budgetData['categories'] ?? [];
        
        $catName = '';
        foreach ($categories as $cat) {
            if ((int)$cat['id'] === $category_id) {
                $catName = mb_strtolower($cat['name'], 'UTF-8');
                break;
            }
        }

        if (strpos($catName, 'projet') !== false || strpos($catName, 'investissement') !== false) {
            $currentCapital = (float)($userData['project_capital'] ?? 0);
            $newCapital = max(0, $currentCapital - $amount);
            
            if ($walletType === 'pro') {
                $stmtUpdate = $pdo->prepare("UPDATE wari_users SET project_capital_pro = ? WHERE id = ?");
            } else {
                $stmtUpdate = $pdo->prepare("UPDATE wari_users SET project_capital = ? WHERE id = ?");
            }
            $stmtUpdate->execute([$newCapital, $user_id]);
        }
    }

    // Supprimer la dépense
    $stmtDelete = $pdo->prepare("DELETE FROM wari_expenses WHERE id = ?");
    $stmtDelete->execute([$expense_id]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erreur serveur: ' . $e->getMessage()]);
}

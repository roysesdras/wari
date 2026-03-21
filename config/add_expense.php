<?php
// add_expense.php
error_reporting(0);     // ← masque les warnings
ini_set('display_errors', 0); // ← empêche l'affichage des erreurs

session_start();
require 'session_config.php';
require 'db.php';
require 'no_cache.php';
require 'session_check.php'; // ← ajout

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

// 2. Récupérer les données envoyées par le JavaScript (fetch)
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (isset($data['amount']) && isset($data['category_id'])) {
    $user_id = $_SESSION['user_id'];
    $amount = intval($data['amount']);
    $category_id = intval($data['category_id']);
    $description = isset($data['description']) ? $data['description'] : 'Dépense rapide';

    try {
        // 1. Insérer la dépense
        $stmt = $pdo->prepare("INSERT INTO wari_expenses (user_id, category_id, amount, description, date_expense) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $category_id, $amount, $description]);

        // 2. Vérifier si la catégorie est "Projet" via le JSON stocké
        $stmtUser = $pdo->prepare("SELECT budget_data, project_capital FROM wari_users WHERE id = ?");
        $stmtUser->execute([$user_id]);
        $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if ($userData && $userData['budget_data']) {
            $budgetData = json_decode($userData['budget_data'], true);
            $categories = $budgetData['categories'] ?? [];

            // On cherche la catégorie correspondante par ID
            $matchedCat = null;
            foreach ($categories as $cat) {
                if ((int)$cat['id'] === $category_id) {
                    $matchedCat = $cat;
                    break;
                }
            }

            // Si c'est une catégorie Projet, on déduit du capital
            if ($matchedCat && stripos($matchedCat['name'], 'projet') !== false) {
                $stmtCap = $pdo->prepare("
                    UPDATE wari_users 
                    SET project_capital = GREATEST(0, project_capital - ?) 
                    WHERE id = ?
                ");
                $stmtCap->execute([$amount, $user_id]);
            }
        }

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Données incomplètes']);
}

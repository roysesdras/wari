<?php
// add_expense.php
error_reporting(0);     // ← masque les warnings
ini_set('display_errors', 0); // ← empêche l'affichage des erreurs

require 'session_config.php';

require 'db.php';
require 'no_cache.php';
require 'session_check.php'; // ← ajout
require_once __DIR__ . '/../wari_monitoring.php';  // ← TOUJOURS EN PREMIER

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

    $walletType = $data['wallet_type'] ?? 'perso';
    if (!in_array($walletType, ['perso', 'pro'])) {
        $walletType = 'perso';
    }

    try {
        // 1. Insérer la dépense
        $stmt = $pdo->prepare("INSERT INTO wari_expenses (user_id, category_id, amount, description, wallet_type, date_expense) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $category_id, $amount, $description, $walletType]);

        // 1b. Détection défi "Zéro Futilités" (uniquement pour le portefeuille personnel)
        if ($walletType === 'perso') {
            $stmtChallenge = $pdo->prepare("SELECT * FROM wari_savings_challenges WHERE user_id = ? AND challenge_type = 'no_frivolities' AND status = 'active'");
        $stmtChallenge->execute([$user_id]);
        $activeChallenge = $stmtChallenge->fetch(PDO::FETCH_ASSOC);

        if ($activeChallenge) {
            $isFrivolous = false;
            $lowerDesc = mb_strtolower($description, 'UTF-8');
            $keywords = ['loisir', 'futilit', 'fete', 'sorti', 'cadeau', 'plaisir', 'resto', 'voyage', 'shopping', 'alcool', 'cinema', 'jeu', 'bar', 'biere', 'boite', 'pub', 'club', 'gadget', 'lux', 'spa', 'massage'];
            foreach ($keywords as $kw) {
                if (strpos($lowerDesc, $kw) !== false) {
                    $isFrivolous = true;
                    break;
                }
            }

            // Récupérer le budget_data pour vérifier également le nom de la catégorie
            $stmtUserForCat = $pdo->prepare("SELECT budget_data FROM wari_users WHERE id = ?");
            $stmtUserForCat->execute([$user_id]);
            $userDataForCat = $stmtUserForCat->fetch(PDO::FETCH_ASSOC);

            if (!$isFrivolous && $userDataForCat && $userDataForCat['budget_data']) {
                $budgetData = json_decode($userDataForCat['budget_data'], true);
                $categories = $budgetData['categories'] ?? [];
                foreach ($categories as $cat) {
                    if ((int)$cat['id'] === $category_id) {
                        $catNameLower = mb_strtolower($cat['name'], 'UTF-8');
                        foreach ($keywords as $kw) {
                            if (strpos($catNameLower, $kw) !== false) {
                                $isFrivolous = true;
                                break 2;
                            }
                        }
                    }
                }
            }

            if ($isFrivolous) {
                $metadata = json_decode($activeChallenge['metadata'], true) ?? [];
                $metadata['failed'] = true;
                $metadata['fail_reason'] = "Dépense : " . $description . " (" . $amount . " F CFA)";
                $metadata['fail_date'] = date('Y-m-d H:i:s');

                $stmtFail = $pdo->prepare("
                    UPDATE wari_savings_challenges 
                    SET status = 'abandoned', metadata = ? 
                    WHERE id = ?
                ");
                $stmtFail->execute([json_encode($metadata), $activeChallenge['id']]);
            }
        }
        }

        // 2. Vérifier si la catégorie est "Projet" via le JSON stocké
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
                if ($walletType === 'pro') {
                    $stmtCap = $pdo->prepare("
                        UPDATE wari_users 
                        SET project_capital_pro = GREATEST(0, project_capital_pro - ?) 
                        WHERE id = ?
                    ");
                } else {
                    $stmtCap = $pdo->prepare("
                        UPDATE wari_users 
                        SET project_capital = GREATEST(0, project_capital - ?) 
                        WHERE id = ?
                    ");
                }
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

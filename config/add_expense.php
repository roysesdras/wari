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
        // A. Récupérer les données de l'utilisateur (budget_data & project_capital)
        if ($walletType === 'pro') {
            $stmtUser = $pdo->prepare("SELECT budget_data_pro as budget_data, project_capital_pro as project_capital FROM wari_users WHERE id = ?");
        } else {
            $stmtUser = $pdo->prepare("SELECT budget_data, project_capital FROM wari_users WHERE id = ?");
        }
        $stmtUser->execute([$user_id]);
        $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

        $matchedCat = null;
        if ($userData && $userData['budget_data']) {
            $budgetData = json_decode($userData['budget_data'], true);
            $categoriesList = $budgetData['categories'] ?? [];
            foreach ($categoriesList as $cat) {
                if ((int)$cat['id'] === $category_id) {
                    $matchedCat = $cat;
                    break;
                }
            }
        }

        // Récupérer le nom de la catégorie (soit fourni par JS, soit extrait depuis les données du profil)
        $category_name = isset($data['category_name']) ? trim($data['category_name']) : null;
        if (empty($category_name) && $matchedCat) {
            $category_name = $matchedCat['name'];
        }

        // 1. Insérer la dépense avec le nom de l'enveloppe historique
        $stmt = $pdo->prepare("INSERT INTO wari_expenses (user_id, category_id, amount, description, wallet_type, date_expense, category_name) VALUES (?, ?, ?, ?, ?, NOW(), ?)");
        $stmt->execute([$user_id, $category_id, $amount, $description, $walletType, $category_name]);

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

                // Vérifier également le nom de la catégorie
                if (!$isFrivolous && $matchedCat) {
                    $catNameLower = mb_strtolower($matchedCat['name'], 'UTF-8');
                    foreach ($keywords as $kw) {
                        if (strpos($catNameLower, $kw) !== false) {
                            $isFrivolous = true;
                            break;
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

        // 2. Si c'est une catégorie Projet, on déduit du capital
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

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Données incomplètes']);
}

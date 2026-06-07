<?php
// /var/www/wari.digiroys.com/config/update_challenge.php
session_start();
require 'session_config.php';
require 'db.php';
require 'no_cache.php';
require 'session_check.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

if (!isset($_SESSION['is_premium']) || !$_SESSION['is_premium']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Cette fonctionnalité requiert un abonnement Wari Premium']);
    exit();
}

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

$action = $input['action'] ?? 'deposit'; // 'deposit' ou 'complete' (pour no_frivolities)
$challenge_id = isset($input['challenge_id']) ? intval($input['challenge_id']) : null;

if (!$challenge_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de défi manquant']);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Récupérer le défi
    $stmt = $pdo->prepare("SELECT * FROM wari_savings_challenges WHERE id = ? AND user_id = ? AND status = 'active'");
    $stmt->execute([$challenge_id, $user_id]);
    $challenge = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$challenge) {
        throw new Exception("Défi introuvable ou non actif");
    }

    $amount_to_save = 0;
    $metadata = json_decode($challenge['metadata'], true) ?? [];

    if ($challenge['challenge_type'] === '52_weeks') {
        if ($action !== 'deposit') {
            throw new Exception("Action non valide pour ce défi");
        }
        $week_number = isset($input['week_number']) ? intval($input['week_number']) : null;
        if (!$week_number || $week_number < 1 || $week_number > 52) {
            throw new Exception("Numéro de semaine invalide");
        }

        // Vérifier si déjà validé
        $checked_weeks = $metadata['checked_weeks'] ?? [];
        if (in_array($week_number, $checked_weeks)) {
            throw new Exception("Cette semaine a déjà été validée");
        }

        $amount_to_save = $week_number * intval($challenge['base_amount']);
        $checked_weeks[] = $week_number;
        sort($checked_weeks);
        $metadata['checked_weeks'] = $checked_weeks;
        $label_vault = "Défi 52 sem - Semaine " . $week_number;

    } elseif ($challenge['challenge_type'] === 'emergency_fund') {
        if ($action !== 'deposit') {
            throw new Exception("Action non valide pour ce défi");
        }
        $amount_to_save = isset($input['amount']) ? intval($input['amount']) : 0;
        if ($amount_to_save <= 0) {
            throw new Exception("Montant de versement invalide");
        }
        $label_vault = "Dépôt Fonds Urgence";

    } elseif ($challenge['challenge_type'] === 'no_frivolities') {
        if ($action === 'complete') {
            // Validation finale du défi de 7 jours
            $end_time = strtotime($metadata['end_date']);
            if (time() < $end_time) {
                throw new Exception("Le défi n'est pas encore terminé (attendre la fin des 7 jours)");
            }
            if ($metadata['failed'] === true) {
                throw new Exception("Ce défi a déjà échoué");
            }

            // Marquer comme complété
            $stmtComplete = $pdo->prepare("
                UPDATE wari_savings_challenges 
                SET status = 'completed' 
                WHERE id = ?
            ");
            $stmtComplete->execute([$challenge_id]);
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Félicitations ! Défi complété avec succès !']);
            exit();
        } else {
            throw new Exception("Action non prise en charge pour ce défi");
        }
    }

    // --- MISE À JOUR TECHNIQUE ET FINANCIÈRE ---

    // A. Récupérer l'utilisateur pour éditer son budget JSON
    $stmtUser = $pdo->prepare("SELECT budget_data, project_capital FROM wari_users WHERE id = ?");
    $stmtUser->execute([$user_id]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$user || !$user['budget_data']) {
        throw new Exception("Données utilisateur introuvables");
    }

    $budgetData = json_decode($user['budget_data'], true);
    $categories = &$budgetData['categories'];

    // B. Déduire de la Poche (par défaut Train de vie id=2, sinon la première catégorie ayant du solde)
    $category_id_to_deduct = isset($input['category_id']) ? intval($input['category_id']) : 2;
    $deducted = false;

    foreach ($categories as &$cat) {
        if ($cat['id'] == $category_id_to_deduct) {
            if ($cat['balance'] < $amount_to_save) {
                throw new Exception("Solde insuffisant dans la catégorie " . $cat['name']);
            }
            $cat['balance'] = max(0, $cat['balance'] - $amount_to_save);
            $deducted = true;
            break;
        }
    }

    if (!$deducted) {
        // Fallback sur la première catégorie ayant assez de solde et n'étant pas Projet ou Épargne
        foreach ($categories as &$cat) {
            $catNameLower = strtolower($cat['name']);
            if (strpos($catNameLower, 'projet') === false && strpos($catNameLower, 'épargne') === false) {
                if ($cat['balance'] >= $amount_to_save) {
                    $cat['balance'] = max(0, $cat['balance'] - $amount_to_save);
                    $deducted = true;
                    break;
                }
            }
        }
    }

    if (!$deducted) {
        throw new Exception("Solde insuffisant dans votre Poche pour ce versement.");
    }

    // C. Mettre à jour le capital projet (coffre-fort)
    $new_project_capital = intval($user['project_capital']) + $amount_to_save;
    $budgetData['projectCapital'] = $new_project_capital;

    // D. Prepend une transaction de coffre-fort dans le JSON pour le rendu immédiat
    // Format français abrégé
    $months_fr = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sept', 'Oct', 'Nov', 'Déc'];
    $date_formatted = date('d') . ' ' . $months_fr[intval(date('n')) - 1];

    $newTx = [
        'date' => $date_formatted,
        'type' => 'in',
        'amount' => $amount_to_save,
        'label' => $label_vault
    ];

    $vaultTransactions = $budgetData['vaultTransactions'] ?? [];
    array_unshift($vaultTransactions, $newTx);
    if (count($vaultTransactions) > 20) {
        array_pop($vaultTransactions);
    }
    $budgetData['vaultTransactions'] = $vaultTransactions;

    // E. Insérer le mouvement dans la table wari_vault_history SQL
    $stmtVault = $pdo->prepare("INSERT INTO wari_vault_history (user_id, type, amount, label) VALUES (?, 'in', ?, ?)");
    $stmtVault->execute([$user_id, $amount_to_save, $label_vault]);

    // F. Sauvegarder l'utilisateur SQL
    $stmtUserUpdate = $pdo->prepare("
        UPDATE wari_users 
        SET budget_data = ?, project_capital = ?, last_budget_at = NOW() 
        WHERE id = ?
    ");
    $stmtUserUpdate->execute([json_encode($budgetData), $new_project_capital, $user_id]);

    // G. Mettre à jour le Défi d'Épargne
    $new_current_amount = intval($challenge['current_amount']) + $amount_to_save;
    
    // Vérification de complétion
    $new_status = 'active';
    if ($challenge['challenge_type'] !== 'no_frivolities' && $new_current_amount >= intval($challenge['target_amount'])) {
        $new_status = 'completed';
    }

    $stmtChallengeUpdate = $pdo->prepare("
        UPDATE wari_savings_challenges 
        SET current_amount = ?, status = ?, metadata = ? 
        WHERE id = ?
    ");
    $stmtChallengeUpdate->execute([$new_current_amount, $new_status, json_encode($metadata), $challenge_id]);

    // Récupérer le défi mis à jour
    $stmtGetChallenge = $pdo->prepare("SELECT * FROM wari_savings_challenges WHERE id = ?");
    $stmtGetChallenge->execute([$challenge_id]);
    $updatedChallenge = $stmtGetChallenge->fetch(PDO::FETCH_ASSOC);

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'challenge' => $updatedChallenge, 
        'budget_data' => $budgetData
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

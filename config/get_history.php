<?php
session_start();
require 'session_config.php';
require 'db.php';
require 'no_cache.php';
require 'session_check.php'; // ← ajout
require_once __DIR__ . '/../wari_monitoring.php';  // ← TOUJOURS EN PREMIER

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die(json_encode(['error' => 'Non autorisé']));
}

$userId = $_SESSION['user_id'];
$months = isset($_GET['months']) ? (int)$_GET['months'] : 6;
if (!in_array($months, [3, 6, 12])) $months = 6;

$walletType = $_GET['wallet_type'] ?? 'perso';
if (!in_array($walletType, ['perso', 'pro'])) {
    $walletType = 'perso';
}

// ✅ Traduction manuelle des mois en français
$moisFr = [
    '01' => 'Janvier',
    '02' => 'Février',
    '03' => 'Mars',
    '04' => 'Avril',
    '05' => 'Mai',
    '06' => 'Juin',
    '07' => 'Juillet',
    '08' => 'Août',
    '09' => 'Septembre',
    '10' => 'Octobre',
    '11' => 'Novembre',
    '12' => 'Décembre',
];

try {
    // 0. Récupérer tous les mois uniques ayant eu de l'activité (soit répartition soit dépense)
    $stmtMonths = $pdo->prepare("
        SELECT DISTINCT DATE_FORMAT(dt, '%Y-%m') as month_key, DATE_FORMAT(dt, '%m') as month_num, DATE_FORMAT(dt, '%Y') as year
        FROM (
            SELECT distributed_at as dt FROM wari_distributions WHERE user_id = ? AND wallet_type = ? AND distributed_at >= DATE_SUB(CURRENT_DATE(), INTERVAL ? MONTH)
            UNION
            SELECT date_expense as dt FROM wari_expenses WHERE user_id = ? AND wallet_type = ? AND date_expense >= DATE_SUB(CURRENT_DATE(), INTERVAL ? MONTH)
        ) as combined
        ORDER BY month_key DESC
    ");
    $stmtMonths->bindValue(1, $userId, PDO::PARAM_INT);
    $stmtMonths->bindValue(2, $walletType, PDO::PARAM_STR);
    $stmtMonths->bindValue(3, $months, PDO::PARAM_INT);
    $stmtMonths->bindValue(4, $userId, PDO::PARAM_INT);
    $stmtMonths->bindValue(5, $walletType, PDO::PARAM_STR);
    $stmtMonths->bindValue(6, $months, PDO::PARAM_INT);
    $stmtMonths->execute();
    $allMonths = $stmtMonths->fetchAll(PDO::FETCH_ASSOC);

    // 1. Répartitions agrégées par mois
    $stmtDistribAgg = $pdo->prepare("
        SELECT 
            DATE_FORMAT(distributed_at, '%Y-%m') as month_key,
            SUM(amount) as total_distributed,
            COUNT(*)    as nb_repartitions
        FROM wari_distributions
        WHERE user_id = ? AND wallet_type = ?
        AND distributed_at >= DATE_SUB(CURRENT_DATE(), INTERVAL ? MONTH)
        GROUP BY month_key
    ");
    $stmtDistribAgg->bindValue(1, $userId, PDO::PARAM_INT);
    $stmtDistribAgg->bindValue(2, $walletType, PDO::PARAM_STR);
    $stmtDistribAgg->bindValue(3, $months, PDO::PARAM_INT);
    $stmtDistribAgg->execute();

    $distribAggByMonth = [];
    foreach ($stmtDistribAgg->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $distribAggByMonth[$row['month_key']] = [
            'total_distributed' => (int)$row['total_distributed'],
            'nb_repartitions'   => (int)$row['nb_repartitions']
        ];
    }

    // 2. Répartitions individuelles (date + heure + montant)
    $stmtDetails = $pdo->prepare("
        SELECT 
            DATE_FORMAT(distributed_at, '%Y-%m')             as month_key,
            DATE_FORMAT(distributed_at, '%d/%m à %H:%M')    as datetime_label,
            amount
        FROM wari_distributions
        WHERE user_id = ? AND wallet_type = ?
        AND distributed_at >= DATE_SUB(CURRENT_DATE(), INTERVAL ? MONTH)
        ORDER BY distributed_at DESC
    ");
    $stmtDetails->bindValue(1, $userId, PDO::PARAM_INT);
    $stmtDetails->bindValue(2, $walletType, PDO::PARAM_STR);
    $stmtDetails->bindValue(3, $months, PDO::PARAM_INT);
    $stmtDetails->execute();

    // Grouper les détails par mois
    $detailsByMonth = [];
    foreach ($stmtDetails->fetchAll(PDO::FETCH_ASSOC) as $detail) {
        $detailsByMonth[$detail['month_key']][] = [
            'datetime' => $detail['datetime_label'],
            'amount'   => (int)$detail['amount'],
        ];
    }

    // 3. Dépenses par mois
    $stmtExp = $pdo->prepare("
        SELECT 
            DATE_FORMAT(date_expense, '%Y-%m') as month_key,
            SUM(amount) as total_spent
        FROM wari_expenses
        WHERE user_id = ? AND wallet_type = ?
        AND date_expense >= DATE_SUB(CURRENT_DATE(), INTERVAL ? MONTH)
        GROUP BY month_key
    ");
    $stmtExp->bindValue(1, $userId, PDO::PARAM_INT);
    $stmtExp->bindValue(2, $walletType, PDO::PARAM_STR);
    $stmtExp->bindValue(3, $months, PDO::PARAM_INT);
    $stmtExp->execute();

    $expensesByMonth = [];
    foreach ($stmtExp->fetchAll(PDO::FETCH_ASSOC) as $exp) {
        $expensesByMonth[$exp['month_key']] = (int)$exp['total_spent'];
    }

    // 3b. Dépenses détaillées
    $stmtExpDetails = $pdo->prepare("
        SELECT 
            id,
            DATE_FORMAT(date_expense, '%Y-%m') as month_key,
            DATE_FORMAT(date_expense, '%d/%m/%Y') as date_day_label,
            DATE_FORMAT(date_expense, '%Hh%i') as time_label,
            date_expense as raw_date,
            amount,
            category_id,
            description
        FROM wari_expenses
        WHERE user_id = ? AND wallet_type = ?
        AND date_expense >= DATE_SUB(CURRENT_DATE(), INTERVAL ? MONTH)
        ORDER BY date_expense DESC
    ");
    $stmtExpDetails->bindValue(1, $userId, PDO::PARAM_INT);
    $stmtExpDetails->bindValue(2, $walletType, PDO::PARAM_STR);
    $stmtExpDetails->bindValue(3, $months, PDO::PARAM_INT);
    $stmtExpDetails->execute();

    $expDetailsByMonth = [];
    $now = time();
    foreach ($stmtExpDetails->fetchAll(PDO::FETCH_ASSOC) as $expDet) {
        $expTime = strtotime($expDet['raw_date']);
        $diffHours = ($now - $expTime) / 3600;
        
        $expDetailsByMonth[$expDet['month_key']][] = [
            'id' => (int)$expDet['id'],
            'date_day_label' => $expDet['date_day_label'],
            'time_label' => $expDet['time_label'],
            'raw_date' => $expDet['raw_date'],
            'amount' => (int)$expDet['amount'],
            'category_id' => (int)$expDet['category_id'],
            'description' => $expDet['description'],
            'cancellable' => ($diffHours <= 24)
        ];
    }

    // 3c. Transactions du coffre-fort par mois
    $stmtVault = $pdo->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month_key,
            DATE_FORMAT(created_at, '%d/%m/%Y') as date_day_label,
            DATE_FORMAT(created_at, '%Hh%i') as time_label,
            type,
            amount,
            label
        FROM wari_vault_history
        WHERE user_id = ? AND wallet_type = ?
        AND created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL ? MONTH)
        ORDER BY created_at DESC
    ");
    $stmtVault->bindValue(1, $userId, PDO::PARAM_INT);
    $stmtVault->bindValue(2, $walletType, PDO::PARAM_STR);
    $stmtVault->bindValue(3, $months, PDO::PARAM_INT);
    $stmtVault->execute();
    
    $vaultByMonth = [];
    foreach ($stmtVault->fetchAll(PDO::FETCH_ASSOC) as $v) {
        $vaultByMonth[$v['month_key']][] = [
            'date_day_label' => $v['date_day_label'],
            'time_label'     => $v['time_label'],
            'type'           => $v['type'],
            'amount'         => (int)$v['amount'],
            'label'          => $v['label']
        ];
    }

    // 4. Fusion
    $history = [];
    foreach ($allMonths as $m) {
        $monthKey = $m['month_key'];
        $monthNum = $m['month_num'];
        $year     = $m['year'];

        $distAgg = $distribAggByMonth[$monthKey] ?? ['total_distributed' => 0, 'nb_repartitions' => 0];
        $totalDistributed = $distAgg['total_distributed'];
        $nbRepartitions   = $distAgg['nb_repartitions'];

        $totalSpent       = $expensesByMonth[$monthKey] ?? 0;
        $totalSaved       = max(0, $totalDistributed - $totalSpent);

        // Label en français
        $label = ($moisFr[$monthNum] ?? '??') . ' ' . $year;

        $history[] = [
            'month_key'         => $monthKey,
            'label'             => $label,
            'nb_repartitions'   => $nbRepartitions,
            'total_distributed' => $totalDistributed,
            'total_spent'       => $totalSpent,
            'total_saved'       => $totalSaved,
            'details'           => $detailsByMonth[$monthKey] ?? [],
            'expenses'          => $expDetailsByMonth[$monthKey] ?? [],
            'vault'             => $vaultByMonth[$monthKey] ?? [],
        ];
    }

    echo json_encode(['success' => true, 'history' => $history]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

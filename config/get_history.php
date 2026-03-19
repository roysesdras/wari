<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die(json_encode(['error' => 'Non autorisé']));
}

$userId = $_SESSION['user_id'];
$months = isset($_GET['months']) ? (int)$_GET['months'] : 6;
if (!in_array($months, [3, 6, 12])) $months = 6;

// ✅ Traduction manuelle des mois en français
$moisFr = [
    '01' => 'Janvier', '02' => 'Février',  '03' => 'Mars',
    '04' => 'Avril',   '05' => 'Mai',       '06' => 'Juin',
    '07' => 'Juillet', '08' => 'Août',      '09' => 'Septembre',
    '10' => 'Octobre', '11' => 'Novembre',  '12' => 'Décembre',
];

try {
    // 1. Répartitions agrégées par mois
    $stmtDistrib = $pdo->prepare("
        SELECT 
            DATE_FORMAT(distributed_at, '%Y-%m') as month_key,
            DATE_FORMAT(distributed_at, '%m')    as month_num,
            DATE_FORMAT(distributed_at, '%Y')    as year,
            SUM(amount) as total_distributed,
            COUNT(*)    as nb_repartitions
        FROM wari_distributions
        WHERE user_id = ?
        AND distributed_at >= DATE_SUB(CURRENT_DATE(), INTERVAL ? MONTH)
        GROUP BY month_key
        ORDER BY month_key DESC
        LIMIT ?
    ");
    $stmtDistrib->bindValue(1, $userId, PDO::PARAM_INT);
    $stmtDistrib->bindValue(2, $months, PDO::PARAM_INT);
    $stmtDistrib->bindValue(3, $months, PDO::PARAM_INT);
    $stmtDistrib->execute();
    $distributions = $stmtDistrib->fetchAll(PDO::FETCH_ASSOC);

    // 2. Répartitions individuelles (date + heure + montant)
    $stmtDetails = $pdo->prepare("
        SELECT 
            DATE_FORMAT(distributed_at, '%Y-%m')             as month_key,
            DATE_FORMAT(distributed_at, '%d/%m à %H:%M')    as datetime_label,
            amount
        FROM wari_distributions
        WHERE user_id = ?
        AND distributed_at >= DATE_SUB(CURRENT_DATE(), INTERVAL ? MONTH)
        ORDER BY distributed_at DESC
    ");
    $stmtDetails->bindValue(1, $userId, PDO::PARAM_INT);
    $stmtDetails->bindValue(2, $months, PDO::PARAM_INT);
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
        WHERE user_id = ?
        AND date_expense >= DATE_SUB(CURRENT_DATE(), INTERVAL ? MONTH)
        GROUP BY month_key
    ");
    $stmtExp->bindValue(1, $userId, PDO::PARAM_INT);
    $stmtExp->bindValue(2, $months, PDO::PARAM_INT);
    $stmtExp->execute();

    $expensesByMonth = [];
    foreach ($stmtExp->fetchAll(PDO::FETCH_ASSOC) as $exp) {
        $expensesByMonth[$exp['month_key']] = (int)$exp['total_spent'];
    }

    // 4. Fusion
    $history = [];
    foreach ($distributions as $dist) {
        $monthKey         = $dist['month_key'];
        $totalDistributed = (int)$dist['total_distributed'];
        $totalSpent       = $expensesByMonth[$monthKey] ?? 0;
        $totalSaved       = max(0, $totalDistributed - $totalSpent);

        // Label en français
        $label = ($moisFr[$dist['month_num']] ?? '??') . ' ' . $dist['year'];

        $history[] = [
            'month_key'         => $monthKey,
            'label'             => $label,
            'nb_repartitions'   => (int)$dist['nb_repartitions'],
            'total_distributed' => $totalDistributed,
            'total_spent'       => $totalSpent,
            'total_saved'       => $totalSaved,
            'details'           => $detailsByMonth[$monthKey] ?? [],
        ];
    }

    echo json_encode(['success' => true, 'history' => $history]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
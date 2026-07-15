<?php
// /var/www/wari.digiroys.com/scratch/test_join_direct.php

$_SESSION['user_id'] = 73; // elimiopportunity@gmail.com
$_SESSION['is_premium'] = true;

require __DIR__ . '/../config/db.php';

// Simulate input payload
$data = [
    'challenge_type' => '52_weeks',
    'base_amount' => 500
];

$challenge_type = $data['challenge_type'] ?? null;
$user_id = $_SESSION['user_id'];

try {
    // Vérifier s'il y a déjà un défi actif
    $stmtCheck = $pdo->prepare("SELECT id FROM wari_savings_challenges WHERE user_id = ? AND status = 'active'");
    $stmtCheck->execute([$user_id]);
    $existing = $stmtCheck->fetch();
    if ($existing) {
        echo "FAIL: Vous avez déjà un défi actif (ID: " . $existing['id'] . ")\n";
        exit();
    }

    $base_amount = 0;
    $target_amount = 0;
    $metadata = [];

    if ($challenge_type === '52_weeks') {
        $base_amount = intval($data['base_amount'] ?? 500);
        if (!in_array($base_amount, [100, 500, 1000])) {
            $base_amount = 500;
        }
        $target_amount = ($base_amount * 52 * 53) / 2;
        $metadata = ['checked_weeks' => []];
    } elseif ($challenge_type === 'emergency_fund') {
        $target_amount = 100000;
        $metadata = ['start_date' => date('Y-m-d H:i:s')];
    } elseif ($challenge_type === 'no_frivolities') {
        $target_amount = 0;
        $start = time();
        $end = $start + (7 * 24 * 3600); // 7 jours
        $metadata = [
            'start_date' => date('Y-m-d H:i:s', $start),
            'end_date' => date('Y-m-d H:i:s', $end),
            'failed' => false,
            'fail_reason' => null
        ];
    } else {
        echo "FAIL: Type de défi invalide\n";
        exit();
    }

    // Insert
    $stmtInsert = $pdo->prepare("
        INSERT INTO wari_savings_challenges (user_id, challenge_type, base_amount, target_amount, current_amount, status, metadata)
        VALUES (?, ?, ?, ?, 0, 'active', ?)
    ");
    $stmtInsert->execute([$user_id, $challenge_type, $base_amount, $target_amount, json_encode($metadata)]);

    $newId = $pdo->lastInsertId();
    echo "SUCCESS: Défi créé avec ID $newId\n";

    // Clean up
    $pdo->prepare("DELETE FROM wari_savings_challenges WHERE id = ?")->execute([$newId]);
    echo "CLEANUP: Défi temporaire supprimé\n";

} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}

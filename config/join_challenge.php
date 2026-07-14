<?php
// /var/www/wari.digiroys.com/config/join_challenge.php
session_start();
file_put_contents(__DIR__ . '/../tmp/challenge_debug.log', date('Y-m-d H:i:s') . " - START - Session: " . json_encode($_SESSION) . " - RawInput: " . file_get_contents('php://input') . "\n", FILE_APPEND);
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

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$challenge_type = $data['challenge_type'] ?? null;
if (!$challenge_type) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Type de défi manquant']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Vérifier s'il y a déjà un défi actif
    $stmtCheck = $pdo->prepare("SELECT id FROM wari_savings_challenges WHERE user_id = ? AND status = 'active'");
    $stmtCheck->execute([$user_id]);
    if ($stmtCheck->fetch()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Vous avez déjà un défi actif. Veuillez l\'abandonner avant d\'en commencer un nouveau.']);
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
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Type de défi invalide']);
        exit();
    }

    $stmtInsert = $pdo->prepare("
        INSERT INTO wari_savings_challenges (user_id, challenge_type, base_amount, target_amount, current_amount, status, metadata)
        VALUES (?, ?, ?, ?, 0, 'active', ?)
    ");
    $stmtInsert->execute([$user_id, $challenge_type, $base_amount, $target_amount, json_encode($metadata)]);

    $newId = $pdo->lastInsertId();
    
    // Récupérer le défi créé
    $stmtGet = $pdo->prepare("SELECT * FROM wari_savings_challenges WHERE id = ?");
    $stmtGet->execute([$newId]);
    $challenge = $stmtGet->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'challenge' => $challenge]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

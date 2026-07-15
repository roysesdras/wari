<?php
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/db.php';

// Accept only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Method Not Allowed");
}

// Get JSON raw body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$slug = filter_var($data['slug'] ?? '', FILTER_UNSAFE_RAW);
$seconds = isset($data['seconds']) ? intval($data['seconds']) : 0;

if (!empty($slug) && $seconds >= 3) { // Only log if they read for at least 3 seconds
    // Cap to 20 minutes (1200 seconds) to avoid database bloating or artificial inflation from background tabs
    if ($seconds > 1200) {
        $seconds = 1200;
    }

    $user_id = $_SESSION['user_id'] ?? null;

    try {
        $stmt = $pdo->prepare("INSERT INTO wari_reading_stats (article_slug, user_id, seconds_spent) VALUES (?, ?, ?)");
        $stmt->execute([$slug, $user_id, $seconds]);
        echo json_encode(['success' => true]);
        exit;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

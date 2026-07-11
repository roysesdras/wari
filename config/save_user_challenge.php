<?php
// /var/www/wari.digiroys.com/config/save_user_challenge.php
require_once __DIR__ . '/../wari_monitoring.php'; // Toujours en premier
require_once __DIR__ . '/session_config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/no_cache.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$action = $data['action'] ?? '';

if ($action === 'submit') {
    $category = $data['category'] ?? '';
    $message = trim($data['message'] ?? '');
    
    // Validation
    $validCategories = ['repartition', 'coach', 'academy', 'vecu', 'autre'];
    if (!in_array($category, $validCategories)) {
        echo json_encode(['success' => false, 'error' => 'Catégorie invalide']);
        exit;
    }
    
    if (empty($message)) {
        echo json_encode(['success' => false, 'error' => 'Message vide']);
        exit;
    }
    
    // Sécurité : Nettoyer le message
    $cleanMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    
    try {
        $pdo->beginTransaction();
        
        // 1. Insérer le défi
        $stmt = $pdo->prepare("INSERT INTO wari_user_challenges (user_id, category, message) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $category, $cleanMessage]);
        
        // 2. Mettre à jour le statut de l'utilisateur
        $stmtUpdate = $pdo->prepare("UPDATE wari_users SET feedback_status = 1, last_feedback_prompt_at = NOW() WHERE id = ?");
        $stmtUpdate->execute([$_SESSION['user_id']]);
        
        $pdo->commit();
        
        // 3. Notifier l'admin via Telegram (Message sur-mesure propre)
        if (defined('MONITORING_TELEGRAM_BOT_TOKEN') && defined('MONITORING_TELEGRAM_CHAT_ID') && MONITORING_TELEGRAM_BOT_TOKEN && MONITORING_TELEGRAM_CHAT_ID) {
            $userEmail = $_SESSION['user_email'] ?? 'Utilisateur inconnu';
            
            $text = "<b>NOUVEAU DÉFI SIGNALÉ SUR WARI</b>\n\n";
            $text .= "<b>Utilisateur :</b> $userEmail\n";
            $text .= "<b>Module :</b> " . ucfirst($category) . "\n";
            $text .= "<b>Description :</b>\n<i>\"" . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . "\"</i>\n\n";
            $text .= "<b>Signalé le :</b> " . date('d/m/Y H:i:s') . "\n";
            $text .= "<i>Gérer les retours sur la console Admin Wari.</i>";
            
            $telegramUrl = "https://api.telegram.org/bot" . MONITORING_TELEGRAM_BOT_TOKEN . "/sendMessage";
            $postData = [
                'chat_id' => MONITORING_TELEGRAM_CHAT_ID,
                'text' => $text,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true
            ];
            
            $ch = curl_init($telegramUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            @curl_exec($ch);
            @curl_close($ch);
        }
        
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'error' => 'Erreur lors de la sauvegarde : ' . $e->getMessage()]);
    }
    
} elseif ($action === 'dismiss') {
    try {
        $stmtUpdate = $pdo->prepare("UPDATE wari_users SET last_feedback_prompt_at = NOW() WHERE id = ?");
        $stmtUpdate->execute([$_SESSION['user_id']]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Action inconnue']);
}

<?php
// /var/www/wari.digiroys.com/cron/send_premium_push.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🚀 [" . date('Y-m-d H:i:s') . "] Envoi de la notification Push Wari Premium...\n";

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Push.php';

$title = "Wari Premium est là ! ✨";
$body = "Profitez de plus de fonctionnalités (Défis d'épargne, Portefeuille Pro, bilans PDF) à seulement 590F.";
$url = "https://wari.digiroys.com/paid/?utm_source=push&utm_campaign=premium_launch";

$res = Push::sendToAll($pdo, $title, $body, $url, 'premium_launch', 'launch');

if ($res['success']) {
    echo "✅ Notification push envoyée avec succès à {$res['recipients']} abonnés !\n";
} else {
    echo "❌ Échec de l'envoi : " . ($res['message'] ?? 'Erreur inconnue') . "\n";
}

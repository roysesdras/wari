<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config/db.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

// Clés VAPID (à déplacer dans .env !)
$auth = [
    'VAPID' => [
        'subject'    => 'mailto:info@rebonly.com',
        'publicKey'  => 'BH9WpcuMhkSEOjnwf8KVZfDTv9Ps6nGaQ9RQ77e4D15ywgPmO7wNgTlldejjFjyWCp3PoBYareDXjlFBTdpzm40',
        'privateKey' => '5RRIDWOg5l8uik2FAhvqvc-VXfcNupUB7JUGFOxox6c',
    ],
];

$webPush = new WebPush($auth);

// Récupérer avec vérification des colonnes
try {
    $stmt = $pdo->query("SELECT id, user_id, endpoint, p256dh, auth, created_at 
                         FROM wari_subscriptions 
                         ORDER BY created_at DESC");
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("[ERREUR SQL] " . $e->getMessage() . "\n");
}

if (empty($subscriptions)) {
    die("Aucun abonné trouvé.\n");
}

echo "📊 " . count($subscriptions) . " subscription(s) trouvée(s)\n\n";

// Stats
$stats = [
    'total'     => count($subscriptions),
    'success'   => 0,
    'expired'   => 0,
    'failed'    => 0,
    'invalid'   => 0
];

$expiredEndpoints = [];

foreach ($subscriptions as $i => $sub) {
    echo "[" . ($i + 1) . "/" . $stats['total'] . "] User #{$sub['user_id']}... ";

    // Vérification des données
    if (empty($sub['endpoint']) || empty($sub['p256dh']) || empty($sub['auth'])) {
        echo "❌ DONNÉES INCOMPLÈTES (endpoint/p256dh/auth manquant)\n";
        $stats['invalid']++;
        continue;
    }

    // Debug (affiche les 50 premiers caractères)
    echo "\n   Endpoint: " . substr($sub['endpoint'], 0, 50) . "... ";

    $subscription = Subscription::create([
        'endpoint' => $sub['endpoint'],
        'publicKey' => $sub['p256dh'],  // Vérifie ce nom !
        'authToken' => $sub['auth'],     // Vérifie ce nom !
    ]);

    $webPush->queueNotification(
        $subscription,
        json_encode([
            'title' => 'Wari - Message du Coach',
            'body'  => "Note tes entrées et dépenses de la journée. 💰",
            'icon'  => 'https://i.postimg.cc/x80KpBqW/warifinance3d.png',
            'badge' => 'https://i.postimg.cc/x80KpBqW/warifinance3d.png',
            'tag'   => 'daily-reminder-' . date('Y-m-d'), // Évite les doublons
            'requireInteraction' => false,
        ], JSON_UNESCAPED_UNICODE)
    );
}

echo "\n📤 Envoi en cours...\n\n";

foreach ($webPush->flush() as $report) {
    $endpoint = $report->getRequest()->getUri()->__toString();
    $endpointShort = substr($endpoint, 0, 60) . "...";

    if ($report->isSuccess()) {
        echo "✅ OK: $endpointShort\n";
        $stats['success']++;
    } elseif ($report->isSubscriptionExpired()) {
        echo "💀 EXPIRÉ: $endpointShort\n";
        $stats['expired']++;
        $expiredEndpoints[] = $endpoint;
    } else {
        echo "❌ ÉCHEC: {$report->getReason()} | $endpointShort\n";
        $stats['failed']++;
    }
}

// Nettoyage des subscriptions expirées
if (!empty($expiredEndpoints)) {
    echo "\n🧹 Nettoyage de " . count($expiredEndpoints) . " subscription(s) expirée(s)...\n";
    $placeholders = implode(',', array_fill(0, count($expiredEndpoints), '?'));
    $pdo->prepare("DELETE FROM wari_subscriptions WHERE endpoint IN ($placeholders)")
        ->execute($expiredEndpoints);
}

// Résumé
echo "\n" . str_repeat("=", 50) . "\n";
echo "📈 RÉSULTATS:\n";
echo "   Total:     {$stats['total']}\n";
echo "   ✅ Succès:  {$stats['success']} (" . round($stats['success'] / $stats['total'] * 100) . "%)\n";
echo "   💀 Expirés: {$stats['expired']} (supprimés)\n";
echo "   ❌ Échecs:  {$stats['failed']}\n";
echo "   ⚠️  Invalides: {$stats['invalid']}\n";
echo str_repeat("=", 50) . "\n";

// Log dans fichier
$log = date('Y-m-d H:i:s') . " | " . json_encode($stats) . "\n";
file_put_contents(__DIR__ . '/push_log.txt', $log, FILE_APPEND);

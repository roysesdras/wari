<?php
// 1. Charger les dépendances
require __DIR__ . '/vendor/autoload.php';
// Remplace 'config.php' par le chemin réel de ton fichier de connexion PDO
// Ou utilise le bloc de connexion ci-dessous
require __DIR__ . '/config/db.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

// 2. Tes clés VAPID
$auth = [
    'VAPID' => [
        'subject' => 'mailto:info@rebonly.com',
        'publicKey' => 'BH9WpcuMhkSEOjnwf8KVZfDTv9Ps6nGaQ9RQ77e4D15ywgPmO7wNgTlldejjFjyWCp3PoBYareDXjlFBTdpzm40',
        'privateKey' => '5RRIDWOg5l8uik2FAhvqvc-VXfcNupUB7JUGFOxox6c',
    ],
];

$webPush = new WebPush($auth);

// 3. Récupérer les abonnés
// On s'assure que $pdo est bien accessible (déjà défini dans ton config.php)
try {
    $stmt = $pdo->query("SELECT endpoint, p256dh, auth FROM wari_subscriptions");
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("[ERREUR SQL] " . $e->getMessage());
}

if (empty($subscriptions)) {
    die("Aucun abonné trouvé dans la base de données.\n");
}

foreach ($subscriptions as $sub) {
    $subscription = Subscription::create([
        'endpoint' => $sub['endpoint'],
        'publicKey' => $sub['p256dh'],
        'authToken' => $sub['auth'],
    ]);

    // 4. Préparer la notification (avec JSON_UNESCAPED_UNICODE pour l'emoji 💰)
    $webPush->queueNotification(
        $subscription,
        json_encode([
            'title' => 'Wari - Message du Coach',
            'body' => "Note tes entrées et dépenses de la journée. 💰",
            'icon' => 'https://i.postimg.cc/x80KpBqW/warifinance3d.png'
        ], JSON_UNESCAPED_UNICODE)
    );
}

// 5. Exécuter l'envoi
echo "Envoi en cours pour " . count($subscriptions) . " appareil(s)...\n";

foreach ($webPush->flush() as $report) {
    if ($report->isSuccess()) {
        echo "[OK] Notification envoyée avec succès.\n";
    } else {
        echo "[ERREUR] Échec : {$report->getReason()}\n";
    }
}

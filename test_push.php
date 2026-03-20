<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config/db.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

$auth = [
    'VAPID' => [
        'subject'    => 'mailto:info@rebonly.com',
        'publicKey'  => 'BH9WpcuMhkSEOjnwf8KVZfDTv9Ps6nGaQ9RQ77e4D15ywgPmO7wNgTlldejjFjyWCp3PoBYareDXjlFBTdpzm40',
        'privateKey' => '5RRIDWOg5l8uik2FAhvqvc-VXfcNupUB7JUGFOxox6c',
    ],
];

$webPush = new WebPush($auth);

// Test avec UNE seule subscription
$sub = $pdo->query("SELECT * FROM wari_subscriptions LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$sub) {
    die("Aucune subscription trouvée\n");
}

echo "Test sur user_id: {$sub['user_id']}\n";
echo "Endpoint: " . substr($sub['endpoint'], 0, 50) . "...\n";

// Test des deux syntaxes possibles
echo "\n--- Test 1: avec 'p256dh' et 'auth' ---\n";
try {
    $subscription1 = Subscription::create([
        'endpoint' => $sub['endpoint'],
        'p256dh'   => $sub['p256dh'],
        'auth'     => $sub['auth'],
    ]);

    $webPush->queueNotification($subscription1, json_encode([
        'title' => 'Test 1',
        'body'  => 'Syntaxe p256dh/auth',
        'icon'  => 'https://i.postimg.cc/x80KpBqW/warifinance3d.png'
    ]));

    foreach ($webPush->flush() as $report) {
        echo $report->isSuccess() ? "✅ SUCCÈS\n" : "❌ ÉCHEC: " . $report->getReason() . "\n";
    }
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
}

echo "\n--- Test 2: avec 'publicKey' et 'authToken' ---\n";
try {
    $subscription2 = Subscription::create([
        'endpoint'  => $sub['endpoint'],
        'publicKey' => $sub['p256dh'],
        'authToken' => $sub['auth'],
    ]);

    $webPush->queueNotification($subscription2, json_encode([
        'title' => 'Test 2',
        'body'  => 'Syntaxe publicKey/authToken',
        'icon'  => 'https://i.postimg.cc/x80KpBqW/warifinance3d.png'
    ]));

    foreach ($webPush->flush() as $report) {
        echo $report->isSuccess() ? "✅ SUCCÈS\n" : "❌ ÉCHEC: " . $report->getReason() . "\n";
    }
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
}

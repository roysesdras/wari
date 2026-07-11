<?php
// /var/www/wari.digiroys.com/cron/send_premium_announcement.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Campagne active jusqu'en Juin 2027 inclus
$endCampaignDate = strtotime('2027-07-01 00:00:00');
if (time() >= $endCampaignDate) {
    die("Campaign completed (June 2027 has passed).\n");
}

echo "🚀 [" . date('Y-m-d H:i:s') . "] Démarrage de la campagne Wari Premium...\n";

// 1. Chargement du .env pour $_ENV
$envFile = __DIR__ . '/../wari-admin/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0 || !strpos($line, '=')) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}
   
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../classes/Mailer.php';
require_once __DIR__ . '/../config/db.php';

$mailer = new Mailer();

// 2. Charger le registre des emails envoyés (format: [user_id => "YYYY-MM"])
$sentLogFile = __DIR__ . '/../tmp/premium_email_sent_users.json';
$sentHistory = [];
if (file_exists($sentLogFile)) {
    $data = json_decode(file_get_contents($sentLogFile), true);
    if (is_array($data)) {
        $sentHistory = $data;
    }
}

$currentMonth = date('Y-m');

// Identifier les utilisateurs ayant déjà reçu l'email ce mois-ci
$sentThisMonth = [];
foreach ($sentHistory as $userId => $month) {
    if ($month === $currentMonth) {
        $sentThisMonth[] = (int)$userId;
    }
}

// 3. Sélectionner les cibles qui n'ont pas encore reçu l'email ce mois-ci
$placeholders = '';
$params = [];
if (!empty($sentThisMonth)) {
    $placeholders = 'AND id NOT IN (' . implode(',', array_fill(0, count($sentThisMonth), '?')) . ')';
    $params = $sentThisMonth;
}

$stmt = $pdo->prepare("
    SELECT id, email 
    FROM wari_users 
    WHERE email IS NOT NULL AND email != ''
    $placeholders
    ORDER BY id ASC
    LIMIT 100
");
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($users)) {
    die("✅ Tous les utilisateurs éligibles ont reçu l'annonce Wari Premium pour le mois de " . date('F Y') . ".\n");
}

echo "📧 " . count($users) . " cibles identifiées pour ce lot ce mois-ci.\n";

// 4. Chargement du template HTML
$templatePath = __DIR__ . '/../templates/emails/premium_announcement.html';
if (!file_exists($templatePath)) {
    die("❌ Erreur : Le template HTML est introuvable.\n");
}

$htmlTemplate = file_get_contents($templatePath);

foreach ($users as $user) {
    echo "✉️ {$user['email']} : ";

    // Préparation des variables du template
    $replacements = [
        '{{PREMIUM_URL}}'      => 'https://wari.digiroys.com/paid/?utm_source=email&utm_campaign=premium_announcement',
        '{{UNSUBSCRIBE_URL}}'  => 'https://wari.digiroys.com/unsubscribe?token=' . base64_encode($user['id'])
    ];

    $emailBody = str_replace(array_keys($replacements), array_values($replacements), $htmlTemplate);
    $subject = "Contrôle encore mieux ton budget avec Wari Premium";

    $result = $mailer->send($user['email'], $subject, $emailBody);

    if ($result['success']) {
        echo "✅\n";
        $sentHistory[(string)$user['id']] = $currentMonth;
        file_put_contents($sentLogFile, json_encode($sentHistory, JSON_PRETTY_PRINT));
    } else {
        echo "❌ (" . $result['message'] . ")\n";
    }

    // Pause de 2 secondes pour éviter le spam de quota SMTP
    usleep(2000000);
}

echo "🏁 Fin du traitement pour ce lot.\n";

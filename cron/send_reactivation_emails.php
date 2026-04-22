<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🚀 [" . date('Y-m-d H:i:s') . "] Démarrage de la campagne de réactivation...\n";

// 1. Chargement du .env pour $_ENV
$envFile = '/var/www/html/wari-admin/.env';
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

// 2. Sélection des 150 cibles (Sécurité Gmail : ~1000/semaine max pour être tranquille)
$inactiveUsers = $pdo->query("
    SELECT u.id, u.email, 
    -- Nombre de jours d'absence (Ton chiffre principal)
    DATEDIFF(NOW(), COALESCE(u.last_budget_at, u.date_inscription)) as days_inactive,
    
    -- On remplace 'streak_lost' par le nombre de dépenses qu'il a déjà notées par le passé
    -- Cela lui rappelle qu'il a déjà investi du temps et qu'il est en train de tout gâcher
    (SELECT COUNT(*) FROM wari_expenses WHERE user_id = u.id) as total_noted,

    -- On vérifie s'il a déjà un abonnement Push (pour adapter le message plus tard)
    (SELECT COUNT(*) FROM wari_subscriptions WHERE user_id = u.id) as has_push

FROM wari_users u
WHERE u.email IS NOT NULL AND u.email != ''
    -- Sécurité Gmail (7 jours entre deux relances)
    AND (u.last_email_sent IS NULL OR u.last_email_sent < DATE_SUB(NOW(), INTERVAL 7 DAY))
    -- Inactif depuis au moins 3 jours
    AND DATEDIFF(NOW(), COALESCE(u.last_budget_at, u.date_inscription)) >= 3
ORDER BY (u.last_email_sent IS NULL) DESC, days_inactive DESC
LIMIT 150
")->fetchAll(PDO::FETCH_ASSOC);

if (empty($inactiveUsers)) {
    die("✅ Tout le monde a été relancé cette semaine.\n");
}

echo "📧 " . count($inactiveUsers) . " cibles identifiées pour aujourd'hui.\n";

// 3. Chargement du template HTML
$templatePath = '/var/www/html/templates/emails/reactivation.html'; // Vérifie bien ce chemin !
if (!file_exists($templatePath)) {
    die("❌ Erreur : Le template HTML est introuvable dans $templatePath\n");
}

$htmlTemplate = file_get_contents($templatePath);

// Gestion du mois en français (Version robuste)
$formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::LONG, IntlDateFormatter::NONE);
$formatter->setPattern('MMMM');
$moisActuel = $formatter->format(new DateTime()); // Retourne "avril", "mai", etc.

foreach ($inactiveUsers as $user) {
    echo "✉️ {$user['email']} : ";

    // Préparation des variables du template
    $replacements = [
        '{{MONTH}}'            => ucfirst($moisActuel),
        '{{DAYS_INACTIVE}}'    => $user['days_inactive'],
        '{{STREAK_DAYS}}'      => $user['streak_lost'] ?? 0,
        '{{TOTAL_NOTED}}'      => $user['total_noted'] ?? 0,
        '{{REACTIVATE_URL}}'   => 'https://wari.digiroys.com/?utm_source=email&utm_campaign=reactivation',
        '{{IPHONE_GUIDE_URL}}' => 'https://wari.digiroys.com/iphone-help',
        '{{UNSUBSCRIBE_URL}}'  => 'https://wari.digiroys.com/unsubscribe?token=' . base64_encode($user['id'])
    ];

    $emailBody = str_replace(array_keys($replacements), array_values($replacements), $htmlTemplate);
    $subject = "🔔 Ton Coach Wari t'attend depuis {$user['days_inactive']} jours";

    $result = $mailer->send($user['email'], $subject, $emailBody);

    if ($result['success']) {
        echo "✅\n";
        $pdo->prepare("UPDATE wari_users SET last_email_sent = NOW() WHERE id = ?")->execute([$user['id']]);
    } else {
        echo "❌ (" . $result['message'] . ")\n";
    }

    // Pause de 2 secondes pour Gmail (Crucial pour 150 mails)
    usleep(2000000);
}

echo "🏁 Fin du traitement.\n";

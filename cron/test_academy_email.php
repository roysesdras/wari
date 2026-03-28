<?php
// /var/www/html/cron/test_academy_email.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$targetEmail = $_GET['email'] ?? $argv[1] ?? null;

if (!$targetEmail) {
    die("❌ Veuillez fournir une adresse email via le paramètre ?email=... ou en argument de ligne de commande.\n");
}

echo "🚀 Envoi d'un email de test Academy à : $targetEmail\n";

$envFile = __DIR__ . '/../wari-admin/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0 || !strpos($line, '=')) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value, " \t\n\r\0\x0B\"'");
    }
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../classes/Mailer.php';
require_once __DIR__ . '/../config/db.php';

$mailer = new Mailer();

// Prendre le premier cours disponible pour avoir des vraies données
$coursActif = $pdo->query("
    SELECT co.titre, co.slug, co.description, co.duree_minutes, co.niveau, co.auteur,
           c.titre AS cat_titre, c.icone AS cat_icone, COUNT(DISTINCT l.id) AS nb_lecons
    FROM academy_courses co
    JOIN academy_categories c ON c.id = co.category_id
    LEFT JOIN academy_lessons l ON l.course_id = co.id AND l.est_actif = 1
    GROUP BY co.id
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

if (!$coursActif) {
    die("❌ Aucun cours trouvé dans la BDD pour le test.\n");
}

$templatePath = __DIR__ . '/../templates/emails/academy.html';
if (!file_exists($templatePath)) {
    die("❌ Impossible de trouver le template : $templatePath\n");
}
$htmlTemplate = file_get_contents($templatePath);

$replacements = [
    '{{COURSE_TITRE}}'    => $coursActif['titre'],
    '{{COURSE_DESC}}'     => $coursActif['description'],
    '{{COURSE_CAT}}'      => $coursActif['cat_icone'] . ' ' . $coursActif['cat_titre'],
    '{{COURSE_DUREE}}'    => $coursActif['duree_minutes'],
    '{{COURSE_LECONS}}'   => $coursActif['nb_lecons'],
    '{{COURSE_NIVEAU}}'   => ucfirst($coursActif['niveau']),
    '{{COURSE_AUTEUR}}'   => $coursActif['auteur'],
    '{{COURSE_URL}}'      => 'https://wari.digiroys.com/academy/course.php?slug=' . urlencode($coursActif['slug']),
    '{{ACADEMY_URL}}'     => 'https://wari.digiroys.com/academy/',
    '{{UNSUBSCRIBE_URL}}' => '#',
];

$emailBody = str_replace(array_keys($replacements), array_values($replacements), $htmlTemplate);
$subject = "TEST WARI ACADEMY : {$coursActif['titre']}";

$result = $mailer->send($targetEmail, $subject, $emailBody);

if ($result['success']) {
    echo "✅ Email de test envoyé avec succès à $targetEmail !\n";
} else {
    echo "❌ Échec de l'envoi : " . $result['message'] . "\n";
}

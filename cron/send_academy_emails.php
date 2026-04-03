<?php
// /var/www/html/cron/send_academy_emails.php
//
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Autorise le script à tourner pendant 20 minutes (1200 secondes)
set_time_limit(1200);

echo "🚀 [" . date('Y-m-d H:i:s') . "] Démarrage de la campagne Academy...\n";

// ── 1. Chargement du .env ─────────────────────────────────────
$envFile = __DIR__ . '/../wari-admin/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0 || !strpos($line, '=')) continue;
        list($name, $value) = explode('=', $line, 2);
        $name  = trim($name);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        putenv("{$name}={$value}");
        $_ENV[$name]    = $value;
        $_SERVER[$name] = $value;
    }
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../classes/Mailer.php';
require_once __DIR__ . '/../classes/Academy.php';
require_once __DIR__ . '/../config/db.php';

$mailer  = new Mailer();
$academy = new Academy($pdo);

$BATCH = 200; // Max emails par exécution (Gmail = 500/jour, 2 crons = 400/semaine)

// ── 2. LOGIQUE DE SÉLECTION DU COURS ─────────────────────────
//
// Étape A : quel est le dernier cours envoyé ?
$dernierCours = $pdo->query("
    SELECT course_id, MAX(envoye_le) as dernier_envoi
    FROM academy_email_log
    WHERE statut = 'envoye' AND course_id IS NOT NULL
    GROUP BY course_id
    ORDER BY dernier_envoi DESC
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

$coursActif = null;

if ($dernierCours) {
    // Étape B : combien d'users n'ont PAS encore reçu ce cours ?
    $restants = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM wari_users u
        WHERE u.email IS NOT NULL AND u.email != ''
        AND u.academy_inscrit = 1
        AND u.id NOT IN (
            SELECT el.user_id
            FROM academy_email_log el
            WHERE el.course_id = ? AND el.statut = 'envoye'
        )
    ");
    $restants->execute([$dernierCours['course_id']]);
    $nbRestants = (int)$restants->fetch(PDO::FETCH_ASSOC)['total'];

    echo "📊 Utilisateurs n'ayant pas encore reçu le dernier cours : {$nbRestants}\n";

    if ($nbRestants > 0) {
        // Il reste des gens → on continue avec le même cours
        echo "🔄 Cycle en cours — on continue avec le même cours.\n";
        $coursActif = $pdo->prepare("
            SELECT co.id, co.titre, co.slug, co.description,
                   co.duree_minutes, co.niveau, co.auteur,
                   c.titre   AS cat_titre,
                   c.icone   AS cat_icone,
                   c.couleur AS cat_couleur,
                   COUNT(DISTINCT l.id) AS nb_lecons
            FROM academy_courses co
            JOIN academy_categories c ON c.id = co.category_id
            LEFT JOIN academy_lessons l ON l.course_id = co.id AND l.est_actif = 1
            WHERE co.id = ? AND co.est_actif = 1
            GROUP BY co.id
        ");
        $coursActif->execute([$dernierCours['course_id']]);
        $coursActif = $coursActif->fetch(PDO::FETCH_ASSOC);
    } else {
        // Tout le monde a reçu → on passe au cours suivant
        echo "✅ Tout le monde a reçu le dernier cours — passage au cours suivant.\n";
    }
}

// Étape C : si pas de cours actif → on prend le prochain cours non encore envoyé
if (!$coursActif) {
    $coursActif = $pdo->query("
        SELECT co.id, co.titre, co.slug, co.description,
               co.duree_minutes, co.niveau, co.auteur,
               c.titre   AS cat_titre,
               c.icone   AS cat_icone,
               c.couleur AS cat_couleur,
               COUNT(DISTINCT l.id) AS nb_lecons
        FROM academy_courses co
        JOIN academy_categories c ON c.id = co.category_id
        LEFT JOIN academy_lessons l ON l.course_id = co.id AND l.est_actif = 1
        WHERE co.est_actif = 1
        AND co.id NOT IN (
            -- Exclure les cours déjà envoyés à TOUT le monde
            SELECT el.course_id
            FROM academy_email_log el
            WHERE el.statut = 'envoye'
            GROUP BY el.course_id
            HAVING COUNT(DISTINCT el.user_id) >= (
                SELECT COUNT(*) FROM wari_users
                WHERE academy_inscrit = 1
                AND email IS NOT NULL AND email != ''
            )
        )
        GROUP BY co.id
        ORDER BY co.ordre ASC
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);
}

// Étape D : si tous les cours ont été envoyés → on recommence depuis le début
if (!$coursActif) {
    echo "🔁 Tous les cours ont été envoyés à tout le monde — on recommence le cycle.\n";
    $coursActif = $pdo->query("
        SELECT co.id, co.titre, co.slug, co.description,
               co.duree_minutes, co.niveau, co.auteur,
               c.titre   AS cat_titre,
               c.icone   AS cat_icone,
               c.couleur AS cat_couleur,
               COUNT(DISTINCT l.id) AS nb_lecons
        FROM academy_courses co
        JOIN academy_categories c ON c.id = co.category_id
        LEFT JOIN academy_lessons l ON l.course_id = co.id AND l.est_actif = 1
        WHERE co.est_actif = 1
        GROUP BY co.id
        ORDER BY co.ordre ASC
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);
}

if (!$coursActif) {
    die("⚠️  Aucun cours actif trouvé. Campagne annulée.\n");
}

echo "📘 Cours sélectionné : {$coursActif['titre']}\n";

// ── 3. SÉLECTION DES DESTINATAIRES ───────────────────────────
$users = $pdo->prepare("
    SELECT u.id, u.email
    FROM wari_users u
    WHERE u.email IS NOT NULL AND u.email != ''
    AND u.academy_inscrit = 1
    AND u.id NOT IN (
        SELECT el.user_id
        FROM academy_email_log el
        WHERE el.course_id = :course_id AND el.statut = 'envoye'
    )
    ORDER BY u.date_inscription ASC
    LIMIT :limit
");

// On lie les paramètres manuellement pour préciser le type INT pour la limite
$users->bindValue(':course_id', $coursActif['id'], PDO::PARAM_INT);
$users->bindValue(':limit', (int)$BATCH, PDO::PARAM_INT); // <-- ICI on force l'entier
$users->execute();

$users = $users->fetchAll(PDO::FETCH_ASSOC);

if (empty($users)) {
    die("✅ Tous les utilisateurs ont déjà reçu ce cours. Rien à envoyer.\n");
}

echo "📧 " . count($users) . " utilisateurs ciblés...\n";

// ── 4. CHARGEMENT DU TEMPLATE ─────────────────────────────────
$templatePath = __DIR__ . '/../templates/emails/academy.html';
if (!file_exists($templatePath)) {
    die("❌ Template introuvable : $templatePath\n");
}
$htmlTemplate = file_get_contents($templatePath);

// ── 5. ENVOI ──────────────────────────────────────────────────
$envoyes = 0;
$echecs  = 0;

foreach ($users as $user) {

    echo "✉️  {$user['email']} : ";

    $courseUrl = 'https://wari.digiroys.com/academy/course.php?slug='
        . urlencode($coursActif['slug'])
        . '&utm_source=email&utm_campaign=academy_weekly';

    $replacements = [
        '{{COURSE_TITRE}}'    => $coursActif['titre'],
        '{{COURSE_DESC}}'     => $coursActif['description'],
        '{{COURSE_CAT}}'      => $coursActif['cat_icone'] . ' ' . $coursActif['cat_titre'],
        '{{COURSE_DUREE}}'    => $coursActif['duree_minutes'],
        '{{COURSE_LECONS}}'   => $coursActif['nb_lecons'],
        '{{COURSE_NIVEAU}}'   => ucfirst($coursActif['niveau']),
        '{{COURSE_AUTEUR}}'   => $coursActif['auteur'],
        '{{COURSE_URL}}'      => $courseUrl,
        '{{ACADEMY_URL}}'     => 'https://wari.digiroys.com/academy/?utm_source=email&utm_campaign=academy_weekly',
        '{{UNSUBSCRIBE_URL}}' => 'https://wari.digiroys.com/academy/unsubscribe?token=' . base64_encode($user['id']),
    ];

    $emailBody = str_replace(
        array_keys($replacements),
        array_values($replacements),
        $htmlTemplate
    );

    $subject = "📚 Cette semaine sur Wari Academy : {$coursActif['titre']}";
    $result  = $mailer->send($user['email'], $subject, $emailBody);

    if ($result['success']) {
        echo "✅\n";
        $envoyes++;

        $pdo->prepare("
            UPDATE wari_users SET last_academy_email_sent = NOW() WHERE id = ?
        ")->execute([$user['id']]);

        $pdo->prepare("
            INSERT INTO academy_email_log (user_id, course_id, sujet, statut)
            VALUES (?, ?, ?, 'envoye')
        ")->execute([$user['id'], $coursActif['id'], $subject]);
    } else {
        echo "❌ (" . $result['message'] . ")\n";
        $echecs++;

        $pdo->prepare("
            INSERT INTO academy_email_log (user_id, course_id, sujet, statut)
            VALUES (?, ?, ?, 'echec')
        ")->execute([$user['id'], $coursActif['id'], $subject]);
    }

    usleep(3000000); // 3s de pause — respect quota SMTP Gmail
}

// ── 6. RÉSUMÉ ─────────────────────────────────────────────────
// Combien reste-t-il à envoyer pour ce cours ?
$resteFinal = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM wari_users u
    WHERE u.email IS NOT NULL AND u.email != ''
    AND u.academy_inscrit = 1
    AND u.id NOT IN (
        SELECT el.user_id FROM academy_email_log el
        WHERE el.course_id = ? AND el.statut = 'envoye'
    )
");
$resteFinal->execute([$coursActif['id']]);
$nbReste = (int)$resteFinal->fetch(PDO::FETCH_ASSOC)['total'];

echo "\n══════════════════════════════════════\n";
echo "✅ Envoyés         : {$envoyes}\n";
echo "❌ Échecs          : {$echecs}\n";
echo "📘 Cours           : {$coursActif['titre']}\n";
echo "⏳ Reste à envoyer : {$nbReste} utilisateurs pour ce cours\n";
if ($nbReste > 0) {
    $prochainBatch = ceil($nbReste / $BATCH);
    echo "📅 Prochains batchs nécessaires : ~{$prochainBatch}\n";
} else {
    echo "🎉 Tout le monde a reçu ce cours !\n";
}
echo "🏁 [" . date('Y-m-d H:i:s') . "] Campagne Academy terminée.\n";

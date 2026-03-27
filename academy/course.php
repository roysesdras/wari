<?php
// /var/www/html/academy/course.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Academy.php';

session_start();
$user_id = $_SESSION['user_id'] ?? null;

// Redirection si non connecté
if (!$user_id) {
    header('Location: https://wari.digiroys.com/login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$academy = new Academy($pdo);

// Récupération du cours via le slug
$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header('Location: /academy/');
    exit;
}

$course = $academy->getCourseBySlug($slug);
if (!$course) {
    header('Location: /academy/');
    exit;
}

$lessons  = $academy->getLessonsByCourse($course['id']);
$pdfs     = $academy->getPdfsByCourse($course['id']);
$progress = $academy->getCourseProgress($user_id, $course['id']);

// Statut de chaque leçon pour cet utilisateur
foreach ($lessons as &$lesson) {
    $lesson['complete'] = $academy->isLessonComplete($user_id, $lesson['id']);
}
unset($lesson);

// Première leçon non complétée = leçon à reprendre
$nextLesson = null;
foreach ($lessons as $l) {
    if (!$l['complete']) {
        $nextLesson = $l;
        break;
    }
}
// Si tout est terminé, pointer sur la première leçon
if (!$nextLesson && !empty($lessons)) {
    $nextLesson = $lessons[0];
}

$totalLecons   = count($lessons);
$doneLecons    = count(array_filter($lessons, fn($l) => $l['complete']));
$coursTermine  = $totalLecons > 0 && $doneLecons === $totalLecons;
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($course['titre']) ?> — Wari Academy</title>

    <link rel="icon" type="image/png" href="../assets/warifinance3d.png" />
    <link rel="apple-touch-icon" href="../assets/warifinance3d.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        /* ── VARIABLES ───────────────────────────────────────── */
        :root {
            --or: #C9A84C;
            --or-light: #F0D080;
            --or-dark: #8B6914;
            --terre: #1A1209;
            --encre: #0F0A02;
            --creme: #FAF5E9;
            --creme2: #F0E8D0;
            --blanc: #FFFFFF;
            --gris: #6B6050;
            --cat-color: <?= htmlspecialchars($course['category_couleur'] ?? '#C9A84C') ?>;

            --font-titre: 'Playfair Display', serif;
            --font-corps: 'DM Sans', sans-serif;

            --rayon: 14px;
            --ombre: 0 4px 24px rgba(0, 0, 0, .10);
            --transition: .25s cubic-bezier(.4, 0, .2, 1);
        }

        /* ── RESET ───────────────────────────────────────────── */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: var(--font-corps);
            background: var(--creme);
            color: var(--terre);
            min-height: 100vh;
        }

        /* ── NAV ──────────────────────────────────────────────── */
        .topnav {
            background: var(--encre);
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid rgba(201, 168, 76, .15);
        }

        .topnav-logo {
            font-family: var(--font-titre);
            font-size: 1.1rem;
            font-weight: 900;
            color: var(--or);
            text-decoration: none;
        }

        .topnav-logo span {
            color: var(--blanc);
        }

        .topnav-back {
            font-size: .82rem;
            color: rgba(255, 255, 255, .55);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: color var(--transition);
        }

        .topnav-back:hover {
            color: var(--or);
        }

        .topnav-user {
            font-size: .8rem;
            font-weight: 600;
            color: var(--or-light);
            background: rgba(201, 168, 76, .12);
            border: 1px solid rgba(201, 168, 76, .25);
            padding: 6px 14px;
            border-radius: 999px;
            text-decoration: none;
        }

        /* ── HERO COURS ───────────────────────────────────────── */
        .course-hero {
            background: var(--encre);
            padding: 48px 24px 40px;
            position: relative;
            overflow: hidden;
        }

        .course-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg,
                    color-mix(in srgb, var(--cat-color) 20%, transparent) 0%,
                    transparent 60%);
        }

        .course-hero::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            border: 1px solid rgba(201, 168, 76, .08);
            top: -80px;
            right: -60px;
            pointer-events: none;
        }

        .course-hero-inner {
            max-width: 860px;
            margin: 0 auto;
            position: relative;
        }

        .course-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        .course-breadcrumb a {
            font-size: .78rem;
            color: rgba(255, 255, 255, .45);
            text-decoration: none;
            transition: color var(--transition);
        }

        .course-breadcrumb a:hover {
            color: var(--or);
        }

        .course-breadcrumb span {
            color: rgba(255, 255, 255, .2);
            font-size: .78rem;
        }

        .course-breadcrumb strong {
            font-size: .78rem;
            color: var(--or-light);
        }

        .cat-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--cat-color);
            background: color-mix(in srgb, var(--cat-color) 15%, transparent);
            border: 1px solid color-mix(in srgb, var(--cat-color) 30%, transparent);
            padding: 4px 12px;
            border-radius: 999px;
            margin-bottom: 16px;
        }

        .course-hero h1 {
            font-family: var(--font-titre);
            font-size: clamp(1.6rem, 4vw, 2.4rem);
            font-weight: 900;
            color: var(--blanc);
            line-height: 1.2;
            margin-bottom: 14px;
        }

        .course-hero p {
            font-size: .95rem;
            color: rgba(255, 255, 255, .6);
            line-height: 1.7;
            max-width: 600px;
            margin-bottom: 28px;
        }

        .course-hero-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
        }

        .meta-item {
            font-size: .8rem;
            color: rgba(255, 255, 255, .5);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .meta-item strong {
            color: rgba(255, 255, 255, .85);
        }

        /* ── BARRE PROGRESSION HERO ───────────────────────────── */
        .hero-progress {
            margin-top: 28px;
            background: rgba(255, 255, 255, .08);
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .hero-progress-info {
            flex: 1;
        }

        .hero-progress-label {
            font-size: .8rem;
            color: rgba(255, 255, 255, .5);
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
        }

        .hero-progress-label strong {
            color: var(--or);
        }

        .progress-bar {
            height: 6px;
            background: rgba(255, 255, 255, .1);
            border-radius: 999px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--or-dark), var(--or));
            border-radius: 999px;
            transition: width .8s ease;
        }

        .hero-cta-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--or);
            color: var(--encre);
            font-weight: 700;
            font-size: .88rem;
            padding: 12px 24px;
            border-radius: 999px;
            text-decoration: none;
            white-space: nowrap;
            transition: all var(--transition);
            flex-shrink: 0;
        }

        .hero-cta-btn:hover {
            background: var(--or-light);
            transform: translateY(-2px);
        }

        .hero-cta-btn.termine {
            background: #2E7D32;
            color: var(--blanc);
        }

        /* ── LAYOUT PRINCIPAL ─────────────────────────────────── */
        .course-layout {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 24px;
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 28px;
            align-items: start;
        }

        @media (max-width: 768px) {
            .course-layout {
                grid-template-columns: 1fr;
            }

            .sidebar {
                order: -1;
            }
        }

        /* ── LISTE DES LEÇONS ─────────────────────────────────── */
        .lessons-block {
            background: var(--blanc);
            border-radius: var(--rayon);
            border: 1.5px solid var(--creme2);
            overflow: hidden;
        }

        .lessons-block-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--creme2);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .lessons-block-header h2 {
            font-family: var(--font-titre);
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--encre);
        }

        .lessons-count {
            font-size: .78rem;
            color: var(--gris);
            background: var(--creme);
            padding: 3px 10px;
            border-radius: 999px;
        }

        .lesson-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px 24px;
            border-bottom: 1px solid var(--creme2);
            text-decoration: none;
            color: var(--terre);
            transition: background var(--transition);
            position: relative;
        }

        .lesson-item:last-child {
            border-bottom: none;
        }

        .lesson-item:hover {
            background: var(--creme);
        }

        .lesson-item.active {
            background: color-mix(in srgb, var(--cat-color) 6%, var(--blanc));
            border-left: 3px solid var(--cat-color);
        }

        .lesson-num {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .78rem;
            font-weight: 700;
            background: var(--creme2);
            color: var(--gris);
            flex-shrink: 0;
            transition: all var(--transition);
        }

        .lesson-item.complete .lesson-num {
            background: #E8F5E9;
            color: #2E7D32;
        }

        .lesson-item.active .lesson-num {
            background: var(--cat-color);
            color: var(--blanc);
        }

        .lesson-info {
            flex: 1;
            min-width: 0;
        }

        .lesson-titre {
            font-size: .9rem;
            font-weight: 600;
            color: var(--encre);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .lesson-item.complete .lesson-titre {
            color: var(--gris);
        }

        .lesson-meta {
            font-size: .75rem;
            color: var(--gris);
            margin-top: 2px;
            display: flex;
            gap: 10px;
        }

        .lesson-status {
            flex-shrink: 0;
            font-size: 1rem;
        }

        /* ── SIDEBAR ──────────────────────────────────────────── */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .sidebar-card {
            background: var(--blanc);
            border-radius: var(--rayon);
            border: 1.5px solid var(--creme2);
            overflow: hidden;
        }

        .sidebar-card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--creme2);
            font-family: var(--font-titre);
            font-size: 1rem;
            font-weight: 700;
            color: var(--encre);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sidebar-card-body {
            padding: 18px 20px;
        }

        /* Stats du cours */
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .stat-box {
            background: var(--creme);
            border-radius: 10px;
            padding: 14px;
            text-align: center;
        }

        .stat-box strong {
            display: block;
            font-family: var(--font-titre);
            font-size: 1.5rem;
            color: var(--or-dark);
            line-height: 1;
        }

        .stat-box span {
            font-size: .72rem;
            color: var(--gris);
            margin-top: 4px;
            display: block;
        }

        /* PDF payants */
        .pdf-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid var(--creme2);
        }

        .pdf-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .pdf-icone {
            font-size: 1.4rem;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .pdf-info {
            flex: 1;
        }

        .pdf-titre {
            font-size: .85rem;
            font-weight: 600;
            color: var(--encre);
            margin-bottom: 2px;
        }

        .pdf-desc {
            font-size: .75rem;
            color: var(--gris);
            line-height: 1.4;
            margin-bottom: 8px;
        }

        .pdf-prix {
            font-size: .78rem;
            font-weight: 700;
            color: var(--or-dark);
        }

        .pdf-prix.gratuit {
            color: #2E7D32;
        }

        .pdf-btn {
            display: inline-block;
            font-size: .75rem;
            font-weight: 600;
            padding: 5px 14px;
            border-radius: 999px;
            text-decoration: none;
            background: var(--encre);
            color: var(--blanc);
            transition: background var(--transition);
            margin-top: 6px;
        }

        .pdf-btn:hover {
            background: var(--or-dark);
        }

        .pdf-btn.gratuit {
            background: #E8F5E9;
            color: #2E7D32;
        }

        /* Auteur */
        .auteur-card {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .auteur-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--or-dark), var(--or));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .auteur-nom {
            font-weight: 600;
            font-size: .9rem;
            color: var(--encre);
            margin-bottom: 2px;
        }

        .auteur-role {
            font-size: .75rem;
            color: var(--gris);
        }

        /* Badge terminé */
        .badge-termine {
            background: #E8F5E9;
            border: 1px solid #A5D6A7;
            border-radius: 10px;
            padding: 14px 16px;
            text-align: center;
        }

        .badge-termine .icone {
            font-size: 2rem;
            margin-bottom: 6px;
        }

        .badge-termine strong {
            display: block;
            font-size: .9rem;
            color: #1B5E20;
            font-weight: 700;
        }

        .badge-termine span {
            font-size: .78rem;
            color: #388E3C;
        }

        /* ── ANIMATIONS ───────────────────────────────────────── */
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(16px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .course-hero-inner {
            animation: fadeUp .5s ease both;
        }

        .lessons-block,
        .sidebar-card {
            animation: fadeUp .5s ease both;
        }

        .lesson-item {
            animation: fadeUp .35s ease both;
        }

        /* ── EMPTY ────────────────────────────────────────────── */
        .empty {
            padding: 40px 24px;
            text-align: center;
            color: var(--gris);
        }
    </style>
</head>

<body>

    <!-- ── NAVIGATION ──────────────────────────────────────────── -->
    <nav class="topnav">
        <a href="/academy/" class="topnav-logo">Wari<span> Academy</span></a>
        <a href="/academy/" class="topnav-back">← Retour aux cours</a>
        <a href="https://wari.digiroys.com/profil" class="topnav-user">👤 Mon profil</a>
    </nav>

    <!-- ── HERO ────────────────────────────────────────────────── -->
    <section class="course-hero">
        <div class="course-hero-inner">

            <div class="course-breadcrumb">
                <a href="/academy/">Academy</a>
                <span>/</span>
                <a href="/academy/?cat=<?= htmlspecialchars($course['category_slug']) ?>">
                    <?= htmlspecialchars($course['category_titre']) ?>
                </a>
                <span>/</span>
                <strong><?= htmlspecialchars($course['titre']) ?></strong>
            </div>

            <div class="cat-badge">
                <?= htmlspecialchars($course['category_titre']) ?>
            </div>

            <h1><?= htmlspecialchars($course['titre']) ?></h1>

            <?php if ($course['description']): ?>
                <p><?= htmlspecialchars($course['description']) ?></p>
            <?php endif; ?>

            <div class="course-hero-meta">
                <div class="meta-item">⏱ <strong><?= $course['duree_minutes'] ?> min</strong> estimées</div>
                <div class="meta-item">📖 <strong><?= $totalLecons ?> leçon<?= $totalLecons > 1 ? 's' : '' ?></strong></div>
                <div class="meta-item">🎯 <strong><?= ucfirst($course['niveau']) ?></strong></div>
                <div class="meta-item">✍️ Par <strong><?= htmlspecialchars($course['auteur']) ?></strong></div>
            </div>

            <!-- Progression -->
            <div class="hero-progress">
                <div class="hero-progress-info">
                    <div class="hero-progress-label">
                        <span><?= $doneLecons ?> / <?= $totalLecons ?> leçons terminées</span>
                        <strong><?= $progress ?>%</strong>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width:<?= $progress ?>%"></div>
                    </div>
                </div>

                <?php if ($nextLesson): ?>
                    <a
                        href="/academy/lesson.php?id=<?= $nextLesson['id'] ?>"
                        class="hero-cta-btn <?= $coursTermine ? 'termine' : '' ?>">
                        <?php if ($coursTermine): ?>
                            ✅ Revoir le cours
                        <?php elseif ($progress > 0): ?>
                            ▶ Continuer
                        <?php else: ?>
                            🚀 Commencer
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            </div>

        </div>
    </section>

    <!-- ── LAYOUT ──────────────────────────────────────────────── -->
    <div class="course-layout">

        <!-- ── LISTE DES LEÇONS ─────────────────────────────────── -->
        <div>
            <div class="lessons-block">
                <div class="lessons-block-header">
                    <h2>Leçons du cours</h2>
                    <span class="lessons-count"><?= $doneLecons ?>/<?= $totalLecons ?> complétées</span>
                </div>

                <?php if (!empty($lessons)): ?>
                    <?php foreach ($lessons as $i => $lesson): ?>
                        <?php
                        $isComplete = $lesson['complete'];
                        $isCurrent  = $nextLesson && $lesson['id'] === $nextLesson['id'] && !$coursTermine;
                        $classes    = 'lesson-item';
                        if ($isComplete) $classes .= ' complete';
                        if ($isCurrent)  $classes .= ' active';
                        ?>
                        <a
                            href="/academy/lesson.php?id=<?= $lesson['id'] ?>"
                            class="<?= $classes ?>"
                            style="animation-delay: <?= $i * .06 ?>s">
                            <div class="lesson-num">
                                <?php if ($isComplete): ?>✓
                            <?php else: echo $i + 1; ?>
                            <?php endif; ?>
                            </div>

                            <div class="lesson-info">
                                <div class="lesson-titre"><?= htmlspecialchars($lesson['titre']) ?></div>
                                <div class="lesson-meta">
                                    <?php
                                    $types = ['texte' => '📄 Lecture', 'video' => '🎥 Vidéo', 'quiz' => '🧩 Quiz'];
                                    echo $types[$lesson['type']] ?? '📄 Lecture';
                                    ?>
                                </div>
                            </div>

                            <div class="lesson-status">
                                <?php if ($isComplete): ?>✅
                                <?php elseif ($isCurrent): ?>▶️
                                <?php else: ?>🔒
                            <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>

                <?php else: ?>
                    <div class="empty">
                        <p>Aucune leçon disponible pour ce cours pour le moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── SIDEBAR ──────────────────────────────────────────── -->
        <aside class="sidebar">

            <!-- Badge cours terminé -->
            <?php if ($coursTermine): ?>
                <div class="badge-termine">
                    <div class="icone">🏆</div>
                    <strong>Cours terminé !</strong>
                    <span>Félicitations, tu as complété ce cours.</span>
                </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="sidebar-card">
                <div class="sidebar-card-header">📊 Résumé</div>
                <div class="sidebar-card-body">
                    <div class="stats-grid">
                        <div class="stat-box">
                            <strong><?= $totalLecons ?></strong>
                            <span>Leçons</span>
                        </div>
                        <div class="stat-box">
                            <strong><?= $course['duree_minutes'] ?></strong>
                            <span>Minutes</span>
                        </div>
                        <div class="stat-box">
                            <strong><?= $progress ?>%</strong>
                            <span>Progression</span>
                        </div>
                        <div class="stat-box">
                            <strong><?= ucfirst($course['niveau'][0]) ?></strong>
                            <span><?= ucfirst($course['niveau']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PDF Payants -->
            <?php if (!empty($pdfs)): ?>
                <div class="sidebar-card">
                    <div class="sidebar-card-header">📄 Ressources du cours</div>
                    <div class="sidebar-card-body">
                        <?php foreach ($pdfs as $pdf): ?>
                            <?php $acheté = $academy->hasUserBoughtPdf($user_id, $pdf['id']); ?>
                            <div class="pdf-item">
                                <div class="pdf-icone">📘</div>
                                <div class="pdf-info">
                                    <div class="pdf-titre"><?= htmlspecialchars($pdf['titre']) ?></div>
                                    <?php if ($pdf['description']): ?>
                                        <div class="pdf-desc"><?= htmlspecialchars($pdf['description']) ?></div>
                                    <?php endif; ?>

                                    <?php if ($pdf['est_gratuit'] || $acheté): ?>
                                        <div class="pdf-prix gratuit">✅ Gratuit</div>
                                        <a href="/academy/pdf_download.php?id=<?= $pdf['id'] ?>" class="pdf-btn gratuit">
                                            Télécharger
                                        </a>
                                    <?php else: ?>
                                        <div class="pdf-prix"><?= number_format($pdf['prix'], 0, ',', ' ') ?> FCFA</div>
                                        <a href="/academy/pdf_achat.php?id=<?= $pdf['id'] ?>" class="pdf-btn">
                                            Obtenir ce guide
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Auteur -->
            <div class="sidebar-card">
                <div class="sidebar-card-header">✍️ Auteur</div>
                <div class="sidebar-card-body">
                    <div class="auteur-card">
                        <div class="auteur-avatar">🧑🏾</div>
                        <div>
                            <div class="auteur-nom"><?= htmlspecialchars($course['auteur']) ?></div>
                            <div class="auteur-role">Coach financier · Wari Finance</div>
                        </div>
                    </div>
                </div>
            </div>

        </aside>
    </div>

</body>

</html>
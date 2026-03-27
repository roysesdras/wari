<?php
// /var/www/html/academy/lesson.php

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

// Récupération de la leçon
$lesson_id = (int)($_GET['id'] ?? 0);
if (!$lesson_id) {
    header('Location: /academy/');
    exit;
}

$lesson = $academy->getLessonById($lesson_id);
if (!$lesson) {
    header('Location: /academy/');
    exit;
}

$course   = $academy->getCourseById($lesson['course_id']);
$lessons  = $academy->getLessonsByCourse($lesson['course_id']);
$prevLesson = $academy->getPrevLesson($lesson['course_id'], $lesson['ordre']);
$nextLesson = $academy->getNextLesson($lesson['course_id'], $lesson['ordre']);
$progress   = $academy->getCourseProgress($user_id, $lesson['course_id']);
$isComplete = $academy->isLessonComplete($user_id, $lesson_id);

// Statut de toutes les leçons (pour la sidebar)
foreach ($lessons as &$l) {
    $l['complete'] = $academy->isLessonComplete($user_id, $l['id']);
}
unset($l);

$totalLecons = count($lessons);
$doneLecons  = count(array_filter($lessons, fn($l) => $l['complete']));

// Numéro de la leçon courante
$currentIndex = 0;
foreach ($lessons as $i => $l) {
    if ($l['id'] === $lesson_id) {
        $currentIndex = $i;
        break;
    }
}

// Marquer comme complétée si action POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete') {
    $academy->markLessonComplete($user_id, $lesson_id, $lesson['course_id']);
    // Recalcul
    $isComplete = true;
    $progress   = $academy->getCourseProgress($user_id, $lesson['course_id']);
    $doneLecons = min($doneLecons + 1, $totalLecons);

    // Si leçon suivante → rediriger directement
    if ($nextLesson) {
        header('Location: /academy/lesson.php?id=' . $nextLesson['id']);
        exit;
    } else {
        // Cours terminé → retour à la page du cours
        header('Location: /academy/course.php?slug=' . urlencode($course['slug']) . '&termine=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($lesson['titre']) ?> — Wari Academy</title>

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

        /* ── NAV ─────────────────────────────────────────────── */
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
            gap: 16px;
        }

        .topnav-logo {
            font-family: var(--font-titre);
            font-size: 1.1rem;
            font-weight: 900;
            color: var(--or);
            text-decoration: none;
            flex-shrink: 0;
        }

        .topnav-logo span {
            color: var(--blanc);
        }

        /* Barre de progression dans la nav */
        .nav-progress {
            flex: 1;
            max-width: 320px;
        }

        .nav-progress-label {
            font-size: .7rem;
            color: rgba(255, 255, 255, .4);
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
        }

        .nav-progress-label span {
            color: var(--or);
        }

        .nav-progress-bar {
            height: 4px;
            background: rgba(255, 255, 255, .1);
            border-radius: 999px;
            overflow: hidden;
        }

        .nav-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--or-dark), var(--or));
            border-radius: 999px;
            transition: width .6s ease;
        }

        .topnav-back {
            font-size: .8rem;
            color: rgba(255, 255, 255, .5);
            text-decoration: none;
            flex-shrink: 0;
            transition: color var(--transition);
        }

        .topnav-back:hover {
            color: var(--or);
        }

        /* ── LAYOUT ──────────────────────────────────────────── */
        .lesson-layout {
            max-width: 1060px;
            margin: 0 auto;
            padding: 32px 24px 60px;
            display: grid;
            grid-template-columns: 1fr 280px;
            gap: 28px;
            align-items: start;
        }

        @media (max-width: 820px) {
            .lesson-layout {
                grid-template-columns: 1fr;
            }

            .lesson-sidebar {
                order: -1;
            }
        }

        /* ── CONTENU PRINCIPAL ───────────────────────────────── */
        .lesson-main {}

        /* En-tête leçon */
        .lesson-header {
            background: var(--blanc);
            border-radius: var(--rayon);
            border: 1.5px solid var(--creme2);
            padding: 28px 32px;
            margin-bottom: 20px;
            border-top: 4px solid var(--cat-color);
        }

        .lesson-breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .lesson-breadcrumb a {
            font-size: .75rem;
            color: var(--gris);
            text-decoration: none;
            transition: color var(--transition);
        }

        .lesson-breadcrumb a:hover {
            color: var(--or-dark);
        }

        .lesson-breadcrumb span {
            font-size: .75rem;
            color: var(--creme2);
        }

        .lesson-breadcrumb strong {
            font-size: .75rem;
            color: var(--terre);
        }

        .lesson-num-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--cat-color);
            background: color-mix(in srgb, var(--cat-color) 12%, transparent);
            padding: 3px 12px;
            border-radius: 999px;
            margin-bottom: 12px;
        }

        .lesson-header h1 {
            font-family: var(--font-titre);
            font-size: clamp(1.4rem, 3vw, 2rem);
            font-weight: 900;
            color: var(--encre);
            line-height: 1.25;
            margin-bottom: 14px;
        }

        .lesson-meta-row {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .lesson-meta-item {
            font-size: .78rem;
            color: var(--gris);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .badge-complete {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #E8F5E9;
            color: #2E7D32;
            border: 1px solid #A5D6A7;
            font-size: .75rem;
            font-weight: 700;
            padding: 3px 12px;
            border-radius: 999px;
        }

        /* ── VIDÉO ───────────────────────────────────────────── */
        .video-wrap {
            background: #000;
            border-radius: var(--rayon);
            overflow: hidden;
            margin-bottom: 20px;
            aspect-ratio: 16/9;
        }

        .video-wrap iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        /* ── CONTENU TEXTE ───────────────────────────────────── */
        .lesson-content {
            background: var(--blanc);
            border-radius: var(--rayon);
            border: 1.5px solid var(--creme2);
            padding: 36px 40px;
            margin-bottom: 20px;
            line-height: 1.85;
            font-size: .97rem;
        }

        @media (max-width: 600px) {
            .lesson-content {
                padding: 24px 20px;
            }

            .lesson-header {
                padding: 20px;
            }
        }

        /* Styles du contenu HTML des leçons */
        .lesson-content h2 {
            font-family: var(--font-titre);
            font-size: 1.35rem;
            color: var(--encre);
            margin: 28px 0 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--creme2);
        }

        .lesson-content h3 {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--terre);
            margin: 22px 0 8px;
        }

        .lesson-content p {
            margin-bottom: 16px;
            color: var(--terre);
        }

        .lesson-content ul,
        .lesson-content ol {
            padding-left: 22px;
            margin-bottom: 16px;
        }

        .lesson-content li {
            margin-bottom: 8px;
            color: var(--terre);
        }

        .lesson-content strong {
            color: var(--encre);
            font-weight: 700;
        }

        .lesson-content em {
            font-style: italic;
            color: var(--gris);
        }

        .lesson-content blockquote {
            border-left: 4px solid var(--or);
            background: color-mix(in srgb, var(--or) 6%, var(--blanc));
            padding: 16px 20px;
            border-radius: 0 10px 10px 0;
            margin: 20px 0;
            font-style: italic;
            color: var(--terre);
        }

        .lesson-content .encadre {
            background: color-mix(in srgb, var(--cat-color) 8%, var(--blanc));
            border: 1px solid color-mix(in srgb, var(--cat-color) 25%, transparent);
            border-radius: 10px;
            padding: 18px 20px;
            margin: 20px 0;
        }

        .lesson-content .encadre-titre {
            font-weight: 700;
            font-size: .85rem;
            color: var(--cat-color);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .lesson-content a {
            color: var(--or-dark);
            text-decoration: underline;
        }

        .lesson-content img {
            max-width: 100%;
            border-radius: 10px;
            margin: 16px 0;
        }

        /* ── NAVIGATION ENTRE LEÇONS ─────────────────────────── */
        .lesson-nav {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 20px;
        }

        .lesson-nav-btn {
            background: var(--blanc);
            border: 1.5px solid var(--creme2);
            border-radius: var(--rayon);
            padding: 16px 20px;
            text-decoration: none;
            transition: all var(--transition);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .lesson-nav-btn:hover {
            border-color: var(--or);
            box-shadow: var(--ombre);
            transform: translateY(-2px);
        }

        .lesson-nav-btn.prev {
            flex-direction: row;
        }

        .lesson-nav-btn.next {
            flex-direction: row-reverse;
            text-align: right;
            margin-left: auto;
        }

        .lesson-nav-btn.disabled {
            opacity: .4;
            pointer-events: none;
        }

        .nav-arrow {
            font-size: 1.2rem;
            flex-shrink: 0;
            color: var(--or-dark);
        }

        .nav-btn-label {
            font-size: .68rem;
            color: var(--gris);
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-bottom: 3px;
        }

        .nav-btn-titre {
            font-size: .85rem;
            font-weight: 600;
            color: var(--encre);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* ── BOUTON MARQUER COMPLÉTÉE ────────────────────────── */
        .complete-section {
            background: var(--blanc);
            border: 1.5px solid var(--creme2);
            border-radius: var(--rayon);
            padding: 24px 28px;
            text-align: center;
            margin-bottom: 20px;
        }

        .complete-section p {
            font-size: .88rem;
            color: var(--gris);
            margin-bottom: 16px;
            line-height: 1.6;
        }

        .btn-complete {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--encre);
            color: var(--blanc);
            font-weight: 700;
            font-size: .92rem;
            padding: 14px 32px;
            border-radius: 999px;
            border: none;
            cursor: pointer;
            transition: all var(--transition);
            font-family: var(--font-corps);
        }

        .btn-complete:hover {
            background: var(--or-dark);
            transform: translateY(-2px);
        }

        .btn-complete.done {
            background: #2E7D32;
            cursor: default;
            transform: none;
        }

        /* ── SIDEBAR ─────────────────────────────────────────── */
        .lesson-sidebar {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .sidebar-card {
            background: var(--blanc);
            border-radius: var(--rayon);
            border: 1.5px solid var(--creme2);
            overflow: hidden;
        }

        .sidebar-card-header {
            padding: 14px 18px;
            border-bottom: 1px solid var(--creme2);
            font-weight: 700;
            font-size: .88rem;
            color: var(--encre);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .sidebar-progress-text {
            font-size: .75rem;
            color: var(--gris);
            font-weight: 400;
        }

        /* Liste toutes les leçons dans la sidebar */
        .sidebar-lesson {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 18px;
            border-bottom: 1px solid var(--creme2);
            text-decoration: none;
            color: var(--terre);
            transition: background var(--transition);
            font-size: .82rem;
        }

        .sidebar-lesson:last-child {
            border-bottom: none;
        }

        .sidebar-lesson:hover {
            background: var(--creme);
        }

        .sidebar-lesson.active {
            background: color-mix(in srgb, var(--cat-color) 8%, var(--blanc));
            font-weight: 600;
            border-left: 3px solid var(--cat-color);
        }

        .sidebar-lesson.complete {
            color: var(--gris);
        }

        .s-num {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--creme2);
            color: var(--gris);
            font-size: .68rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .sidebar-lesson.complete .s-num {
            background: #E8F5E9;
            color: #2E7D32;
        }

        .sidebar-lesson.active .s-num {
            background: var(--cat-color);
            color: var(--blanc);
        }

        .s-titre {
            flex: 1;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Sidebar progress bar */
        .sidebar-progress-wrap {
            padding: 14px 18px;
        }

        .sidebar-progress-label {
            display: flex;
            justify-content: space-between;
            font-size: .75rem;
            color: var(--gris);
            margin-bottom: 6px;
        }

        .sidebar-progress-label strong {
            color: var(--or-dark);
        }

        .progress-bar {
            height: 5px;
            background: var(--creme2);
            border-radius: 999px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--or-dark), var(--or));
            border-radius: 999px;
            transition: width .6s ease;
        }

        /* ── ANIMATIONS ───────────────────────────────────────── */
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(14px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .lesson-header {
            animation: fadeUp .4s ease both;
        }

        .lesson-content {
            animation: fadeUp .4s ease both .08s;
        }

        .complete-section {
            animation: fadeUp .4s ease both .14s;
        }

        .lesson-nav {
            animation: fadeUp .4s ease both .18s;
        }
    </style>
</head>

<body>

    <!-- ── NAVIGATION ──────────────────────────────────────────── -->
    <nav class="topnav">
        <a href="/academy/" class="topnav-logo">Wari<span> Academy</span></a>

        <div class="nav-progress">
            <div class="nav-progress-label">
                Cours : <?= htmlspecialchars($course['titre']) ?>
                <span><?= $progress ?>%</span>
            </div>
            <div class="nav-progress-bar">
                <div class="nav-progress-fill" style="width:<?= $progress ?>%"></div>
            </div>
        </div>

        <a href="/academy/course.php?slug=<?= urlencode($course['slug']) ?>" class="topnav-back">
            ← Vue du cours
        </a>
    </nav>

    <!-- ── LAYOUT ──────────────────────────────────────────────── -->
    <div class="lesson-layout">

        <!-- ── CONTENU PRINCIPAL ─────────────────────────────────── -->
        <main class="lesson-main">

            <!-- En-tête -->
            <div class="lesson-header">
                <div class="lesson-breadcrumb">
                    <a href="/academy/">Academy</a>
                    <span>/</span>
                    <a href="/academy/course.php?slug=<?= urlencode($course['slug']) ?>">
                        <?= htmlspecialchars($course['titre']) ?>
                    </a>
                    <span>/</span>
                    <strong><?= htmlspecialchars($lesson['titre']) ?></strong>
                </div>

                <div class="lesson-num-badge">
                    Leçon <?= $currentIndex + 1 ?> sur <?= $totalLecons ?>
                </div>

                <h1><?= htmlspecialchars($lesson['titre']) ?></h1>

                <div class="lesson-meta-row">
                    <?php
                    $types = ['texte' => '📄 Lecture', 'video' => '🎥 Vidéo', 'quiz' => '🧩 Quiz'];
                    ?>
                    <span class="lesson-meta-item"><?= $types[$lesson['type']] ?? '📄 Lecture' ?></span>
                    <span class="lesson-meta-item">✍️ <?= htmlspecialchars($course['auteur']) ?></span>
                    <?php if ($isComplete): ?>
                        <span class="badge-complete">✅ Leçon complétée</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Vidéo si applicable -->
            <?php if ($lesson['type'] === 'video' && $lesson['video_url']): ?>
                <div class="video-wrap">
                    <iframe
                        src="<?= htmlspecialchars($lesson['video_url']) ?>"
                        allowfullscreen
                        loading="lazy"></iframe>
                </div>
            <?php endif; ?>

            <!-- Contenu de la leçon -->
            <div class="lesson-content">
                <?= $lesson['contenu'] /* Contenu HTML stocké en BDD — à filtrer avec HTMLPurifier en production */ ?>
            </div>

            <!-- Bouton marquer complétée -->
            <div class="complete-section">
                <?php if ($isComplete): ?>
                    <button class="btn-complete done" disabled>
                        ✅ Leçon déjà complétée
                    </button>
                    <?php if ($nextLesson): ?>
                        <p style="margin-top:14px; margin-bottom:0; font-size:.82rem; color:var(--gris);">
                            <a href="/academy/lesson.php?id=<?= $nextLesson['id'] ?>" style="color:var(--or-dark); font-weight:600;">
                                Passer à la leçon suivante →
                            </a>
                        </p>
                    <?php else: ?>
                        <p style="margin-top:14px; margin-bottom:0; font-size:.82rem; color:#2E7D32; font-weight:600;">
                            🏆 Tu as terminé toutes les leçons de ce cours !
                        </p>
                    <?php endif; ?>
                <?php else: ?>
                    <p>Tu as lu cette leçon jusqu'au bout ? Marque-la comme complétée pour suivre ta progression.</p>
                    <form method="POST">
                        <input type="hidden" name="action" value="complete">
                        <button type="submit" class="btn-complete">
                            ✅ Marquer comme complétée
                            <?php if ($nextLesson): ?>→ Leçon suivante<?php endif; ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Navigation précédent / suivant -->
            <div class="lesson-nav">
                <!-- Précédente -->
                <?php if ($prevLesson): ?>
                    <a href="/academy/lesson.php?id=<?= $prevLesson['id'] ?>" class="lesson-nav-btn prev">
                        <span class="nav-arrow">←</span>
                        <div>
                            <div class="nav-btn-label">Précédente</div>
                            <div class="nav-btn-titre"><?= htmlspecialchars($prevLesson['titre']) ?></div>
                        </div>
                    </a>
                <?php else: ?>
                    <div class="lesson-nav-btn prev disabled">
                        <span class="nav-arrow">←</span>
                        <div>
                            <div class="nav-btn-label">Précédente</div>
                            <div class="nav-btn-titre">Début du cours</div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Suivante -->
                <?php if ($nextLesson): ?>
                    <a href="/academy/lesson.php?id=<?= $nextLesson['id'] ?>" class="lesson-nav-btn next">
                        <span class="nav-arrow">→</span>
                        <div>
                            <div class="nav-btn-label">Suivante</div>
                            <div class="nav-btn-titre"><?= htmlspecialchars($nextLesson['titre']) ?></div>
                        </div>
                    </a>
                <?php else: ?>
                    <a href="/academy/course.php?slug=<?= urlencode($course['slug']) ?>" class="lesson-nav-btn next">
                        <span class="nav-arrow">🏁</span>
                        <div>
                            <div class="nav-btn-label">Fin du cours</div>
                            <div class="nav-btn-titre">Retour au cours</div>
                        </div>
                    </a>
                <?php endif; ?>
            </div>

        </main>

        <!-- ── SIDEBAR ────────────────────────────────────────────── -->
        <aside class="lesson-sidebar">

            <!-- Progression du cours -->
            <div class="sidebar-card">
                <div class="sidebar-card-header">
                    📊 Progression
                    <span class="sidebar-progress-text"><?= $doneLecons ?>/<?= $totalLecons ?></span>
                </div>
                <div class="sidebar-progress-wrap">
                    <div class="sidebar-progress-label">
                        <span>Cours complété</span>
                        <strong><?= $progress ?>%</strong>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width:<?= $progress ?>%"></div>
                    </div>
                </div>
            </div>

            <!-- Toutes les leçons -->
            <div class="sidebar-card">
                <div class="sidebar-card-header">
                    📖 Plan du cours
                </div>
                <?php foreach ($lessons as $i => $l): ?>
                    <?php
                    $isActive = $l['id'] === $lesson_id;
                    $isDone   = $l['complete'];
                    $cls = 'sidebar-lesson';
                    if ($isActive) $cls .= ' active';
                    if ($isDone)   $cls .= ' complete';
                    ?>
                    <a href="/academy/lesson.php?id=<?= $l['id'] ?>" class="<?= $cls ?>">
                        <div class="s-num">
                            <?= $isDone ? '✓' : ($i + 1) ?>
                        </div>
                        <div class="s-titre"><?= htmlspecialchars($l['titre']) ?></div>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Lien retour cours -->
            <a
                href="/academy/course.php?slug=<?= urlencode($course['slug']) ?>"
                style="
                display:block; text-align:center;
                background:var(--encre); color:var(--or);
                padding:12px; border-radius:var(--rayon);
                font-size:.82rem; font-weight:600;
                text-decoration:none;
                transition: opacity .2s;
            ">
                ← Retour à la page du cours
            </a>

        </aside>
    </div>

</body>

</html>
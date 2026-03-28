<?php
// /var/www/html/academy-admin/lessons.php

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['academy_user'])) {
    header('Location: /academy-admin/login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Academy.php';

$academy = new Academy($pdo);
$user    = $_SESSION['academy_user'];
$action  = $_GET['action'] ?? 'list';
$msg     = '';
$error   = '';

// Filtre par cours (depuis courses.php ou GET)
$filterCourseId = (int)($_GET['course_id'] ?? 0);

// Liste de tous les cours pour le sélecteur
$allCourses = $pdo->query("
    SELECT co.id, co.titre, c.icone as cat_icone, c.titre as cat_titre
    FROM academy_courses co
    JOIN academy_categories c ON c.id = co.category_id
    WHERE co.est_actif = 1
    ORDER BY c.ordre ASC, co.ordre ASC
")->fetchAll(PDO::FETCH_ASSOC);

// ════════════════════════════════════════════════════════
// TRAITEMENT POST
// ════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    // ── Ajouter une leçon
    if ($postAction === 'add_lesson') {
        $course_id = (int)($_POST['course_id'] ?? 0);
        $titre     = trim($_POST['titre'] ?? '');
        $contenu   = trim($_POST['contenu'] ?? '');
        $type      = $_POST['type'] ?? 'texte';
        $video_url = trim($_POST['video_url'] ?? '');
        $ordre     = (int)($_POST['ordre'] ?? 0);

        if ($course_id && $titre && $contenu) {
            // Ordre auto si non précisé
            if (!$ordre) {
                $maxOrdre = $pdo->prepare("SELECT MAX(ordre) as m FROM academy_lessons WHERE course_id = ?");
                $maxOrdre->execute([$course_id]);
                $ordre = ((int)$maxOrdre->fetch(PDO::FETCH_ASSOC)['m']) + 1;
            }

            $pdo->prepare("
                INSERT INTO academy_lessons (course_id, titre, contenu, type, video_url, ordre)
                VALUES (?, ?, ?, ?, ?, ?)
            ")->execute([$course_id, $titre, $contenu, $type, $video_url ?: null, $ordre]);

            $msg    = "✅ Leçon <strong>" . htmlspecialchars($titre) . "</strong> créée avec succès.";
            $action = 'list';
            $filterCourseId = $course_id;
        } else {
            $error  = "Le cours, le titre et le contenu sont obligatoires.";
            $action = 'add';
        }
    }

    // ── Modifier une leçon
    if ($postAction === 'edit_lesson') {
        $id        = (int)($_POST['id'] ?? 0);
        $titre     = trim($_POST['titre'] ?? '');
        $contenu   = trim($_POST['contenu'] ?? '');
        $type      = $_POST['type'] ?? 'texte';
        $video_url = trim($_POST['video_url'] ?? '');
        $ordre     = (int)($_POST['ordre'] ?? 0);
        $est_actif = isset($_POST['est_actif']) ? 1 : 0;

        if ($id && $titre && $contenu) {
            $pdo->prepare("
                UPDATE academy_lessons
                SET titre = ?, contenu = ?, type = ?, video_url = ?, ordre = ?, est_actif = ?
                WHERE id = ?
            ")->execute([$titre, $contenu, $type, $video_url ?: null, $ordre, $est_actif, $id]);
            $msg    = "✅ Leçon mise à jour avec succès.";
            $action = 'list';
        } else {
            $error = "Données invalides.";
        }
    }

    // ── Supprimer une leçon
    if ($postAction === 'delete_lesson') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $pdo->prepare("DELETE FROM academy_lessons WHERE id = ?")->execute([$id]);
            $msg    = "🗑️ Leçon supprimée.";
            $action = 'list';
        }
    }

    // ── Réordonner (monter/descendre)
    if ($postAction === 'reorder') {
        $id        = (int)($_POST['id'] ?? 0);
        $direction = $_POST['direction'] ?? 'up';
        $course_id = (int)($_POST['course_id'] ?? 0);

        if ($id && $course_id) {
            $curr = $pdo->prepare("SELECT ordre FROM academy_lessons WHERE id = ?");
            $curr->execute([$id]);
            $currentOrdre = (int)$curr->fetch(PDO::FETCH_ASSOC)['ordre'];

            if ($direction === 'up') {
                // Trouver la leçon juste au-dessus
                $swap = $pdo->prepare("
                    SELECT id, ordre FROM academy_lessons
                    WHERE course_id = ? AND ordre < ? AND est_actif = 1
                    ORDER BY ordre DESC LIMIT 1
                ");
            } else {
                // Trouver la leçon juste en-dessous
                $swap = $pdo->prepare("
                    SELECT id, ordre FROM academy_lessons
                    WHERE course_id = ? AND ordre > ? AND est_actif = 1
                    ORDER BY ordre ASC LIMIT 1
                ");
            }
            $swap->execute([$course_id, $currentOrdre]);
            $swapLesson = $swap->fetch(PDO::FETCH_ASSOC);

            if ($swapLesson) {
                $pdo->prepare("UPDATE academy_lessons SET ordre = ? WHERE id = ?")->execute([$swapLesson['ordre'], $id]);
                $pdo->prepare("UPDATE academy_lessons SET ordre = ? WHERE id = ?")->execute([$currentOrdre, $swapLesson['id']]);
            }
        }
        $action = 'list';
        $filterCourseId = $course_id;
    }
}

// ── Leçon à éditer
$lessonEdit = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM academy_lessons WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $lessonEdit = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($lessonEdit) {
        $filterCourseId = $lessonEdit['course_id'];
    } else {
        $action = 'list';
    }
}

// ── Liste des leçons (filtrée ou toutes)
$lessonsQuery = "
    SELECT l.*,
        co.titre as course_titre, co.id as course_id,
        c.icone as cat_icone,
        COUNT(DISTINCT p.user_id) as nb_completes
    FROM academy_lessons l
    JOIN academy_courses co ON co.id = l.course_id
    JOIN academy_categories c ON c.id = co.category_id
    LEFT JOIN academy_progress p ON p.lesson_id = l.id AND p.est_complete = 1
";
if ($filterCourseId) {
    $stmt = $pdo->prepare($lessonsQuery . " WHERE l.course_id = ? GROUP BY l.id ORDER BY l.ordre ASC");
    $stmt->execute([$filterCourseId]);
} else {
    $stmt = $pdo->query($lessonsQuery . " GROUP BY l.id ORDER BY co.id ASC, l.ordre ASC");
}
$lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cours filtré (pour afficher son nom)
$coursFiltre = null;
if ($filterCourseId) {
    $stmt = $pdo->prepare("SELECT co.*, c.titre as cat_titre FROM academy_courses co JOIN academy_categories c ON c.id = co.category_id WHERE co.id = ?");
    $stmt->execute([$filterCourseId]);
    $coursFiltre = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leçons — Wari Academy Admin</title>

    <link rel="icon" type="image/png" href="../assets/warifinance3d.png" />
    <link rel="apple-touch-icon" href="../assets/warifinance3d.png">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif']
                    },
                    colors: {
                        gold: {
                            50: '#FFFBEB',
                            100: '#FEF3C7',
                            200: '#FDE68A',
                            300: '#FCD34D',
                            400: '#F0D080',
                            500: '#C9A84C',
                            600: '#B8950A',
                            700: '#8B6914',
                            800: '#6B4F10',
                            900: '#3D2B0F',
                        },
                        ink: {
                            50: '#F5F0E8',
                            100: '#E8DFC8',
                            200: '#D4C09A',
                            300: '#B89A60',
                            400: '#8B6914',
                            500: '#5A3E10',
                            600: '#2A1A04',
                            700: '#1A0F02',
                            800: '#100A01',
                            900: '#0A0601',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        ::-webkit-scrollbar {
            width: 5px;
        }

        ::-webkit-scrollbar-track {
            background: #100A01;
        }

        ::-webkit-scrollbar-thumb {
            background: #3D2B0F;
            border-radius: 999px;
        }

        .bg-pattern {
            background-image: repeating-linear-gradient(45deg,
                    transparent, transparent 40px,
                    rgba(201, 168, 76, .015) 40px, rgba(201, 168, 76, .015) 41px);
        }

        .card-gold-top {
            position: relative;
        }

        .card-gold-top::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, #C9A84C, transparent);
            border-radius: 999px;
        }

        .field-input {
            width: 100%;
            background: rgba(255, 255, 255, .05);
            border: 1px solid rgba(201, 168, 76, .15);
            border-radius: 10px;
            padding: 10px 14px;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            color: #e2e8f0;
            outline: none;
            transition: border-color .2s;
        }

        .field-input:focus {
            border-color: rgba(201, 168, 76, .5);
            background: rgba(201, 168, 76, .04);
        }

        .field-input::placeholder {
            color: rgba(255, 255, 255, .2);
        }

        select.field-input option {
            background: #100A01;
            color: #e2e8f0;
        }

        textarea.field-input {
            resize: vertical;
        }

        .field-label {
            display: block;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: rgba(148, 163, 184, .6);
            margin-bottom: 6px;
        }

        /* Éditeur contenu */
        .content-editor {
            min-height: 300px;
            font-family: 'Poppins', monospace;
            font-size: 13px;
            line-height: 1.7;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .anim {
            animation: fadeUp .35s ease both;
        }
    </style>
</head>

<body class="bg-ink-800 bg-pattern text-slate-200 min-h-screen flex">

    <!-- ════ SIDEBAR ════════════════════════════════════════════ -->
    <aside class="w-56 bg-ink-900 border-r border-gold-900/30 min-h-screen fixed left-0 top-0 bottom-0 flex flex-col z-50">
        <div class="px-5 py-6 border-b border-gold-900/20">
            <span class="block font-black text-gold-500 text-lg tracking-wide leading-none">Wari Academy</span>
            <span class="block text-[10px] text-slate-600 tracking-[.15em] uppercase mt-1">Administration</span>
        </div>
        <nav class="flex-1 px-3 py-4 space-y-0.5">
            <p class="text-[9px] font-bold tracking-[.15em] uppercase text-slate-700 px-2 pt-2 pb-1">Principal</p>
            <a href="/academy-admin/index.php" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-white/5 text-[13px] transition-all"><span>📊</span> Dashboard</a>
            <p class="text-[9px] font-bold tracking-[.15em] uppercase text-slate-700 px-2 pt-4 pb-1">Contenu</p>
            <a href="/academy-admin/courses.php" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-white/5 text-[13px] transition-all"><span>📚</span> Cours</a>
            <a href="/academy-admin/lessons.php" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-gold-500 bg-gold-900/20 font-semibold text-[13px]"><span>📖</span> Leçons</a>
            <a href="/academy-admin/pdfs.php" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-white/5 text-[13px] transition-all"><span>📄</span> PDF Payants</a>
            <p class="text-[9px] font-bold tracking-[.15em] uppercase text-slate-700 px-2 pt-4 pb-1">Données</p>
            <a href="/academy-admin/stats.php" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-white/5 text-[13px] transition-all"><span>📈</span> Statistiques</a>
            <a href="/academy-admin/emails.php" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-white/5 text-[13px] transition-all"><span>✉️</span> Emails</a>
            <p class="text-[9px] font-bold tracking-[.15em] uppercase text-slate-700 px-2 pt-4 pb-1">App</p>
            <a href="/academy/" target="_blank" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-white/5 text-[13px] transition-all"><span>🌐</span> Voir Academy</a>
            <a href="https://wari.digiroys.com/accueil/" target="_blank" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-white/5 text-[13px] transition-all"><span>←</span> Retour Wari</a>
        </nav>
        <div class="px-3 py-4 border-t border-gold-900/20">
            <div class="flex items-center gap-3 px-2 py-2 mb-1">
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-gold-700 to-gold-500 flex items-center justify-center text-sm shrink-0">👤</div>
                <div>
                    <p class="text-[13px] font-semibold text-gold-400 leading-none"><?= htmlspecialchars($user) ?></p>
                    <p class="text-[10px] text-slate-600 mt-0.5">Admin Academy</p>
                </div>
            </div>
            <a href="/academy-admin/logout.php" class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-600 hover:text-red-400 hover:bg-red-950/30 text-[12px] transition-all">🚪 Se déconnecter</a>
        </div>
    </aside>

    <!-- ════ MAIN ════════════════════════════════════════════════ -->
    <div class="ml-56 flex-1 flex flex-col min-h-screen">

        <!-- Topbar -->
        <div class="bg-ink-900/80 backdrop-blur border-b border-gold-900/20 px-8 h-14 flex items-center justify-between sticky top-0 z-40">
            <div class="flex items-center gap-3">
                <a href="/academy-admin/index.php" class="text-slate-600 hover:text-gold-500 text-xs transition-colors">Dashboard</a>
                <span class="text-slate-700">/</span>
                <a href="/academy-admin/courses.php" class="text-slate-600 hover:text-gold-500 text-xs transition-colors">Cours</a>
                <span class="text-slate-700">/</span>
                <span class="font-bold text-slate-100 text-sm">
                    Leçons <?= $coursFiltre ? '— ' . htmlspecialchars($coursFiltre['titre']) : '' ?>
                </span>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-[11px] text-slate-500"><?= count($lessons) ?> leçon<?= count($lessons) > 1 ? 's' : '' ?></span>
                <a href="/academy-admin/lessons.php?action=add<?= $filterCourseId ? '&course_id=' . $filterCourseId : '' ?>"
                    class="bg-gold-500 hover:bg-gold-400 text-ink-900 font-bold text-[12px] px-4 py-1.5 rounded-full transition-all">
                    + Nouvelle leçon
                </a>
            </div>
        </div>

        <div class="p-8 flex-1">

            <!-- Messages -->
            <?php if ($msg): ?>
                <div class="mb-6 bg-emerald-950/40 border border-emerald-800/40 text-emerald-400 rounded-xl px-5 py-3 text-sm anim">
                    <?= $msg ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="mb-6 bg-red-950/40 border border-red-800/40 text-red-400 rounded-xl px-5 py-3 text-sm anim">
                    ⚠️ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- ════════════════════════════════════════════════
             FORMULAIRE AJOUT / ÉDITION
        ════════════════════════════════════════════════ -->
            <?php if ($action === 'add' || $action === 'edit'): ?>
                <div class="card-gold-top bg-ink-900 border border-gold-900/25 rounded-2xl p-7 mb-8 anim">

                    <h2 class="font-bold text-slate-100 text-base mb-6 flex items-center gap-2">
                        <?= $action === 'edit' ? '✏️ Modifier la leçon' : '➕ Nouvelle leçon' ?>
                    </h2>

                    <form method="POST">
                        <input type="hidden" name="action" value="<?= $action === 'edit' ? 'edit_lesson' : 'add_lesson' ?>">
                        <?php if ($action === 'edit'): ?>
                            <input type="hidden" name="id" value="<?= $lessonEdit['id'] ?>">
                        <?php endif; ?>

                        <div class="grid grid-cols-2 gap-5 mb-5">

                            <!-- Cours parent -->
                            <?php if ($action === 'add'): ?>
                                <div class="col-span-2">
                                    <label class="field-label">Cours parent *</label>
                                    <select name="course_id" class="field-input" required>
                                        <option value="">-- Choisir un cours --</option>
                                        <?php foreach ($allCourses as $c): ?>
                                            <option value="<?= $c['id'] ?>"
                                                <?= $filterCourseId == $c['id'] ? 'selected' : '' ?>>
                                                <?= $c['cat_icone'] ?> <?= htmlspecialchars($c['cat_titre']) ?>
                                                — <?= htmlspecialchars($c['titre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <!-- Titre -->
                            <div class="col-span-2">
                                <label class="field-label">Titre de la leçon *</label>
                                <input type="text" name="titre" class="field-input"
                                    placeholder="ex: Qu'est-ce qu'un budget ?"
                                    value="<?= htmlspecialchars($lessonEdit['titre'] ?? '') ?>"
                                    required>
                            </div>

                            <!-- Type + ordre -->
                            <div>
                                <label class="field-label">Type de leçon</label>
                                <select name="type" class="field-input" id="type-select"
                                    onchange="toggleVideoUrl(this.value)">
                                    <?php foreach (['texte' => '📄 Lecture / Texte', 'video' => '🎥 Vidéo', 'quiz' => '🧩 Quiz'] as $val => $lbl): ?>
                                        <option value="<?= $val ?>"
                                            <?= ($lessonEdit['type'] ?? 'texte') === $val ? 'selected' : '' ?>>
                                            <?= $lbl ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="field-label">Ordre d'affichage</label>
                                <input type="number" name="ordre" class="field-input"
                                    min="1" placeholder="Auto si vide"
                                    value="<?= $lessonEdit['ordre'] ?? '' ?>">
                            </div>

                            <!-- URL Vidéo (si type = video) -->
                            <div class="col-span-2" id="video-url-field"
                                style="display:<?= ($lessonEdit['type'] ?? '') === 'video' ? 'block' : 'none' ?>">
                                <label class="field-label">URL de la vidéo (YouTube embed)</label>
                                <input type="url" name="video_url" class="field-input"
                                    placeholder="https://www.youtube.com/embed/XXXXXXXXX"
                                    value="<?= htmlspecialchars($lessonEdit['video_url'] ?? '') ?>">
                                <p class="text-[11px] text-slate-600 mt-1.5">
                                    💡 Utilise le lien "Intégrer" de YouTube : youtube.com/embed/ID_VIDEO
                                </p>
                            </div>

                            <!-- Contenu HTML -->
                            <div class="col-span-2">
                                <label class="field-label">
                                    Contenu de la leçon *
                                    <span class="text-gold-900 normal-case tracking-normal font-normal ml-2">
                                        — HTML accepté (h2, p, ul, blockquote, strong...)
                                    </span>
                                </label>
                                <textarea name="contenu" class="field-input content-editor"
                                    placeholder="<h2>Introduction</h2>&#10;<p>Contenu de ta leçon ici...</p>"
                                    required><?= htmlspecialchars($lessonEdit['contenu'] ?? '') ?></textarea>

                                <!-- Aide balises -->
                                <div class="flex flex-wrap gap-2 mt-3">
                                    <?php
                                    $tags = [
                                        '<h2>Titre</h2>' => 'h2',
                                        '<p>Paragraphe</p>' => 'p',
                                        '<strong>Gras</strong>' => 'strong',
                                        '<ul><li>Item</li></ul>' => 'liste',
                                        '<blockquote>Citation</blockquote>' => 'citation',
                                        '<div class="bg-slate-800 border-l-4 border-wari-gold p-4 my-4"><div class="text-wari-gold font-bold mb-1">💡 ASTUCE</div>Texte</div>' => 'encadré',
                                    ];

                                    foreach ($tags as $tagContent => $label):
                                        // On échappe pour que le HTML soit safe dans l'attribut onclick
                                        $safeTag = htmlspecialchars($tagContent, ENT_QUOTES, 'UTF-8');
                                    ?>
                                        <button type="button"
                                            onclick="insertTag('<?= $safeTag ?>')"
                                            class="text-[10px] font-bold uppercase tracking-widest bg-slate-900 border border-slate-800 text-slate-500 hover:text-wari-gold hover:border-wari-gold/50 hover:bg-slate-800 px-3 py-1.5 rounded-xl transition-all duration-200">
                                            &lt;<?= $label ?>&gt;
                                        </button>
                                    <?php endforeach; ?>

                                    <!-- Bouton IA -->
                                    <button type="button" onclick="generateLessonContent()" id="btn-ai-write"
                                        class="text-[10px] font-bold uppercase tracking-widest bg-gold-950/30 border border-gold-500/30 text-gold-500 hover:text-gold-400 hover:border-gold-500 hover:bg-gold-900/40 px-3 py-1.5 rounded-xl transition-all duration-200 flex items-center gap-1.5">
                                        ✨ Rédiger avec l'IA
                                    </button>
                                </div>
                            </div>

                            <!-- Actif (edit seulement) -->
                            <?php if ($action === 'edit'): ?>
                                <div class="col-span-2">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" name="est_actif" value="1"
                                            class="accent-gold-500 w-4 h-4"
                                            <?= ($lessonEdit['est_actif'] ?? 1) ? 'checked' : '' ?>>
                                        <span class="text-[13px] text-slate-300">Leçon active (visible pour les apprenants)</span>
                                    </label>
                                </div>
                            <?php endif; ?>

                        </div>

                        <!-- Boutons -->
                        <div class="flex items-center gap-3 pt-4 border-t border-gold-900/20">
                            <button type="submit"
                                class="bg-gold-500 hover:bg-gold-400 text-ink-900 font-bold text-[13px] px-6 py-2.5 rounded-full transition-all">
                                <?= $action === 'edit' ? '💾 Enregistrer' : '✅ Créer la leçon' ?>
                            </button>
                            <a href="/academy-admin/lessons.php<?= $filterCourseId ? '?course_id=' . $filterCourseId : '' ?>"
                                class="text-slate-500 hover:text-slate-300 text-[13px] transition-colors px-4">
                                Annuler
                            </a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- ════════════════════════════════════════════════
             FILTRE PAR COURS
        ════════════════════════════════════════════════ -->
            <?php if ($action === 'list'): ?>

                <div class="flex items-center gap-3 mb-6 anim">
                    <form method="GET" class="flex items-center gap-3">
                        <label class="text-[11px] text-slate-500 font-semibold whitespace-nowrap">Filtrer par cours :</label>
                        <select name="course_id" onchange="this.form.submit()"
                            class="bg-ink-900 border border-gold-900/25 text-slate-300 text-[12px] rounded-xl px-3 py-2 outline-none focus:border-gold-700/50 transition-colors">
                            <option value="">— Tous les cours —</option>
                            <?php foreach ($allCourses as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $filterCourseId == $c['id'] ? 'selected' : '' ?>>
                                    <?= $c['cat_icone'] ?> <?= htmlspecialchars($c['titre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>

                    <?php if ($filterCourseId && $coursFiltre): ?>
                        <span class="text-[11px] bg-gold-900/20 border border-gold-900/30 text-gold-600 px-3 py-1 rounded-full">
                            📚 <?= htmlspecialchars($coursFiltre['titre']) ?> — <?= htmlspecialchars($coursFiltre['cat_titre']) ?>
                        </span>
                        <a href="/academy-admin/lessons.php" class="text-[11px] text-slate-600 hover:text-slate-400 transition-colors">
                            ✕ Effacer le filtre
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Stats rapides -->
                <div class="grid grid-cols-4 gap-4 mb-6">
                    <?php
                    $totalLecons   = count($lessons);
                    $totalActives  = count(array_filter($lessons, fn($l) => $l['est_actif']));
                    $totalTexte    = count(array_filter($lessons, fn($l) => $l['type'] === 'texte'));
                    $totalVideo    = count(array_filter($lessons, fn($l) => $l['type'] === 'video'));
                    ?>
                    <?php foreach (
                        [
                            ['label' => 'Total leçons',  'val' => $totalLecons,  'icon' => '📖'],
                            ['label' => 'Actives',        'val' => $totalActives, 'icon' => '✅'],
                            ['label' => 'Texte',          'val' => $totalTexte,   'icon' => '📄'],
                            ['label' => 'Vidéo',          'val' => $totalVideo,   'icon' => '🎥'],
                        ] as $i => $s
                    ): ?>
                        <div class="card-gold-top bg-ink-900 border border-gold-900/25 rounded-2xl p-5 anim"
                            style="animation-delay:<?= $i * .05 ?>s">
                            <div class="text-2xl opacity-70 mb-2"><?= $s['icon'] ?></div>
                            <p class="font-black text-gold-500 text-3xl leading-none"><?= $s['val'] ?></p>
                            <p class="text-slate-600 text-[11px] mt-1"><?= $s['label'] ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Liste des leçons -->
                <div class="card-gold-top bg-ink-900 border border-gold-900/25 rounded-2xl overflow-hidden anim">

                    <div class="px-6 py-4 border-b border-gold-900/20 flex items-center justify-between">
                        <p class="font-bold text-slate-100 text-sm">📖 Leçons</p>
                        <a href="/academy-admin/lessons.php?action=add<?= $filterCourseId ? '&course_id=' . $filterCourseId : '' ?>"
                            class="text-[11px] text-gold-700 hover:text-gold-500 font-semibold transition-colors">
                            + Ajouter →
                        </a>
                    </div>

                    <?php if (!empty($lessons)): ?>

                        <!-- Header -->
                        <div class="grid grid-cols-12 gap-3 px-6 py-2.5 bg-white/[.02] border-b border-gold-900/10">
                            <div class="col-span-1 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600 text-center">Ordre</div>
                            <div class="col-span-4 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600">Leçon</div>
                            <div class="col-span-3 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600">Cours</div>
                            <div class="col-span-1 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600 text-center">Type</div>
                            <div class="col-span-1 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600 text-center">Complétés</div>
                            <div class="col-span-1 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600 text-center">Statut</div>
                            <div class="col-span-1 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600 text-right">Actions</div>
                        </div>

                        <div class="divide-y divide-gold-900/10">
                            <?php foreach ($lessons as $i => $lesson): ?>
                                <div class="grid grid-cols-12 gap-3 px-6 py-4 hover:bg-white/[.025] transition-colors items-center"
                                    style="animation: fadeUp .3s ease <?= $i * .03 ?>s both">

                                    <!-- Ordre + flèches -->
                                    <div class="col-span-1 flex flex-col items-center gap-0.5">
                                        <?php if ($filterCourseId): ?>
                                            <form method="POST">
                                                <input type="hidden" name="action" value="reorder">
                                                <input type="hidden" name="id" value="<?= $lesson['id'] ?>">
                                                <input type="hidden" name="course_id" value="<?= $lesson['course_id'] ?>">
                                                <input type="hidden" name="direction" value="up">
                                                <button type="submit"
                                                    class="text-slate-600 hover:text-gold-500 transition-colors text-xs leading-none">▲</button>
                                            </form>
                                            <span class="font-bold text-gold-700 text-sm"><?= $lesson['ordre'] ?></span>
                                            <form method="POST">
                                                <input type="hidden" name="action" value="reorder">
                                                <input type="hidden" name="id" value="<?= $lesson['id'] ?>">
                                                <input type="hidden" name="course_id" value="<?= $lesson['course_id'] ?>">
                                                <input type="hidden" name="direction" value="down">
                                                <button type="submit"
                                                    class="text-slate-600 hover:text-gold-500 transition-colors text-xs leading-none">▼</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="font-bold text-gold-700 text-sm"><?= $lesson['ordre'] ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Titre -->
                                    <div class="col-span-4 min-w-0">
                                        <p class="font-semibold text-slate-100 text-[13px] truncate">
                                            <?= htmlspecialchars($lesson['titre']) ?>
                                        </p>
                                        <p class="text-[11px] text-slate-600 mt-0.5 truncate">
                                            <?= mb_substr(strip_tags($lesson['contenu']), 0, 60) ?>...
                                        </p>
                                    </div>

                                    <!-- Cours parent -->
                                    <div class="col-span-3">
                                        <a href="/academy-admin/lessons.php?course_id=<?= $lesson['course_id'] ?>"
                                            class="text-[11px] text-slate-500 hover:text-gold-500 transition-colors truncate block">
                                            <?= $lesson['cat_icone'] ?> <?= htmlspecialchars($lesson['course_titre']) ?>
                                        </a>
                                    </div>

                                    <!-- Type -->
                                    <div class="col-span-1 text-center">
                                        <span class="text-base">
                                            <?= ['texte' => '📄', 'video' => '🎥', 'quiz' => '🧩'][$lesson['type']] ?? '📄' ?>
                                        </span>
                                    </div>

                                    <!-- Nb complétés -->
                                    <div class="col-span-1 text-center">
                                        <span class="font-bold text-gold-500 text-sm"><?= number_format($lesson['nb_completes']) ?></span>
                                    </div>

                                    <!-- Statut -->
                                    <div class="col-span-1 text-center">
                                        <span class="text-[10px] px-2 py-0.5 rounded-full font-semibold
                            <?= $lesson['est_actif']
                                    ? 'bg-emerald-950/50 text-emerald-500 border border-emerald-800/40'
                                    : 'bg-slate-800/50 text-slate-500 border border-slate-700/40' ?>">
                                            <?= $lesson['est_actif'] ? '✓ Active' : '✗ Inactif' ?>
                                        </span>
                                    </div>

                                    <!-- Actions -->
                                    <div class="col-span-1 flex items-center justify-end gap-1.5">
                                        <!-- Voir sur Academy -->
                                        <a href="/academy/lesson.php?id=<?= $lesson['id'] ?>" target="_blank"
                                            title="Voir"
                                            class="w-7 h-7 rounded-lg bg-white/5 hover:bg-blue-900/30 flex items-center justify-center text-slate-500 hover:text-blue-400 transition-all text-sm">
                                            🌐
                                        </a>
                                        <!-- Éditer -->
                                        <a href="/academy-admin/lessons.php?action=edit&id=<?= $lesson['id'] ?>"
                                            title="Modifier"
                                            class="w-7 h-7 rounded-lg bg-white/5 hover:bg-gold-900/30 flex items-center justify-center text-slate-500 hover:text-gold-400 transition-all text-sm">
                                            ✏️
                                        </a>
                                        <!-- Supprimer -->
                                        <form method="POST" onsubmit="return confirm('Supprimer cette leçon ?')">
                                            <input type="hidden" name="action" value="delete_lesson">
                                            <input type="hidden" name="id" value="<?= $lesson['id'] ?>">
                                            <button type="submit" title="Supprimer"
                                                class="w-7 h-7 rounded-lg bg-white/5 hover:bg-red-950/40 flex items-center justify-center text-slate-600 hover:text-red-400 transition-all text-sm">
                                                🗑️
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    <?php else: ?>
                        <div class="px-6 py-16 text-center text-slate-600">
                            <p class="text-4xl mb-4">📖</p>
                            <p class="text-sm">Aucune leçon pour le moment.</p>
                            <a href="/academy-admin/lessons.php?action=add<?= $filterCourseId ? '&course_id=' . $filterCourseId : '' ?>"
                                class="inline-block mt-4 bg-gold-500 text-ink-900 font-bold text-[12px] px-5 py-2 rounded-full hover:bg-gold-400 transition-all">
                                Créer la première leçon →
                            </a>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endif; ?>

        </div>
    </div>

    <script>
        // Afficher/masquer le champ URL vidéo selon le type
        function toggleVideoUrl(type) {
            const field = document.getElementById('video-url-field');
            if (field) field.style.display = type === 'video' ? 'block' : 'none';
        }

        // Insérer une balise HTML dans le textarea
        function insertTag(tag) {
            const textarea = document.querySelector('textarea[name="contenu"]');
            if (!textarea) return;
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            textarea.value = text.slice(0, start) + tag + text.slice(end);
            textarea.selectionStart = textarea.selectionEnd = start + tag.length;
            textarea.focus();
        }

        // --- GÉNÉRATION IA ---
        async function generateLessonContent() {
            const titleField = document.querySelector('input[name="titre"]');
            const contentArea = document.querySelector('textarea[name="contenu"]');
            const btn = document.getElementById('btn-ai-write');
            
            // On essaie de récupérer le contexte du cours
            let courseContext = "";
            const courseSelect = document.querySelector('select[name="course_id"]');
            if (courseSelect) {
                courseContext = courseSelect.options[courseSelect.selectedIndex].text;
            } else {
                // Si on édite, on peut avoir le titre via le PHP injecté ou un titre de page
                courseContext = "Cours Wari Academy";
            }

            if (!titleField.value.trim()) {
                alert("Veuillez d'abord saisir le titre de la leçon.");
                titleField.focus();
                return;
            }

            if (contentArea.value.trim() && !confirm("L'IA va remplacer le contenu actuel. Continuer ?")) {
                return;
            }

            const originalBtnHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '⌛ Rédaction...';
            btn.classList.add('animate-pulse');

            try {
                const formData = new FormData();
                formData.append('action', 'write_lesson');
                formData.append('titre_lecon', titleField.value);
                formData.append('cours_context', courseContext);

                const response = await fetch('ai_gateway.php', {
                    method: 'POST',
                    body: formData
                });

                let data = await response.json();

                // Sécurité : si l'IA renvoie un tableau au lieu d'un objet
                if (Array.isArray(data)) data = data[0];

                if (data.error) {
                    alert("Erreur IA : " + data.error);
                } else if (data.contenu) {
                    contentArea.value = data.contenu;
                    // Petit effet visuel
                    contentArea.classList.add('ring-2', 'ring-gold-500/50');
                    setTimeout(() => contentArea.classList.remove('ring-2', 'ring-gold-500/50'), 2000);
                }
            } catch (err) {
                console.error(err);
                alert("Erreur lors de la communication avec l'IA.");
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalBtnHtml;
                btn.classList.remove('animate-pulse');
            }
        }
    </script>

</body>

</html>
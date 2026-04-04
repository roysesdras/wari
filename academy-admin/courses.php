<?php
// /var/www/html/academy-admin/courses.php

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

// ── Récupération des catégories pour le formulaire
$categories = $pdo->query("
    SELECT * FROM academy_categories WHERE est_actif = 1 ORDER BY ordre ASC
")->fetchAll(PDO::FETCH_ASSOC);

// ════════════════════════════════════════════════════════
// TRAITEMENT DES ACTIONS POST
// ════════════════════════════════════════════════════════

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    // ── Ajouter un cours
    if ($postAction === 'add_course') {
        $titre       = trim($_POST['titre'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $niveau      = $_POST['niveau'] ?? 'debutant';
        $duree       = (int)($_POST['duree_minutes'] ?? 10);
        $auteur      = trim($_POST['auteur'] ?? 'Wari Finance');
        $est_gratuit = isset($_POST['est_gratuit']) ? 1 : 0;

        // Génération du slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-',
            iconv('UTF-8', 'ASCII//TRANSLIT', $titre)
        ), '-'));
        $slug = $slug . '-' . time();

        if ($titre && $category_id) {
            $stmt = $pdo->prepare("
                INSERT INTO academy_courses
                    (category_id, slug, titre, description, niveau, duree_minutes, auteur, est_gratuit)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$category_id, $slug, $titre, $description, $niveau, $duree, $auteur, $est_gratuit]);
            $msg = "Cours <strong>" . htmlspecialchars($titre) . "</strong> créé avec succès.";
            $action = 'list';
        } else {
            $error = "Le titre et la catégorie sont obligatoires.";
            $action = 'add';
        }
    }

    // ── Modifier un cours
    if ($postAction === 'edit_course') {
        $id          = (int)($_POST['id'] ?? 0);
        $titre       = trim($_POST['titre'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $niveau      = $_POST['niveau'] ?? 'debutant';
        $duree       = (int)($_POST['duree_minutes'] ?? 10);
        $auteur      = trim($_POST['auteur'] ?? '');
        $est_gratuit = isset($_POST['est_gratuit']) ? 1 : 0;
        $est_actif   = isset($_POST['est_actif']) ? 1 : 0;

        if ($id && $titre && $category_id) {
            $pdo->prepare("
                UPDATE academy_courses SET
                    category_id = ?, titre = ?, description = ?,
                    niveau = ?, duree_minutes = ?, auteur = ?,
                    est_gratuit = ?, est_actif = ?
                WHERE id = ?
            ")->execute([$category_id, $titre, $description, $niveau, $duree, $auteur, $est_gratuit, $est_actif, $id]);
            $msg = "✅ Cours mis à jour avec succès.";
            $action = 'list';
        } else {
            $error = "Données invalides.";
        }
    }

    // ── Supprimer un cours
    if ($postAction === 'delete_course') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $pdo->prepare("DELETE FROM academy_courses WHERE id = ?")->execute([$id]);
            $msg = "🗑️ Cours supprimé.";
            $action = 'list';
        }
    }

    // ── Toggle actif/inactif
    if ($postAction === 'toggle_actif') {
        $id  = (int)($_POST['id'] ?? 0);
        $val = (int)($_POST['est_actif'] ?? 0);
        $pdo->prepare("UPDATE academy_courses SET est_actif = ? WHERE id = ?")->execute([$val, $id]);
        $action = 'list';
    }
}

// ── Récupération du cours à éditer
$courseEdit = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM academy_courses WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $courseEdit = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$courseEdit) { $action = 'list'; }
}

// ── Liste des cours avec stats
$courses = $pdo->query("
    SELECT co.*,
        c.titre as cat_titre, c.couleur as cat_couleur, c.icone as cat_icone,
        COUNT(DISTINCT l.id) as nb_lecons,
        COUNT(DISTINCT p.user_id) as nb_apprenants,
        COUNT(DISTINCT pdf.id) as nb_pdfs
    FROM academy_courses co
    JOIN academy_categories c ON c.id = co.category_id
    LEFT JOIN academy_lessons l ON l.course_id = co.id
    LEFT JOIN academy_progress p ON p.course_id = co.id
    LEFT JOIN academy_pdfs pdf ON pdf.course_id = co.id
    GROUP BY co.id
    ORDER BY co.category_id ASC, co.ordre ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cours — Wari Academy Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <link rel="icon" type="image/png" href="../assets/warifinance3d.png" />
    <link rel="apple-touch-icon" href="../assets/warifinance3d.png">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Poppins', 'sans-serif'] },
                    colors: {
                        gold: {
                            50:'#FFFBEB', 100:'#FEF3C7', 200:'#FDE68A',
                            300:'#FCD34D', 400:'#F0D080', 500:'#C9A84C',
                            600:'#B8950A', 700:'#8B6914', 800:'#6B4F10', 900:'#3D2B0F',
                        },
                        ink: {
                            50:'#F5F0E8', 100:'#E8DFC8', 200:'#D4C09A',
                            300:'#B89A60', 400:'#8B6914', 500:'#5A3E10',
                            600:'#2A1A04', 700:'#1A0F02', 800:'#100A01', 900:'#0A0601',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Poppins', sans-serif; }
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: #100A01; }
        ::-webkit-scrollbar-thumb { background: #3D2B0F; border-radius: 999px; }
        .bg-pattern {
            background-image: repeating-linear-gradient(45deg,
                transparent, transparent 40px,
                rgba(201,168,76,.015) 40px, rgba(201,168,76,.015) 41px);
        }
        .card-gold-top { position: relative; }
        .card-gold-top::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, transparent, #C9A84C, transparent);
            border-radius: 999px;
        }
        /* Input styles */
        .field-input {
            width: 100%;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(201,168,76,.15);
            border-radius: 10px;
            padding: 10px 14px;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            color: #e2e8f0;
            outline: none;
            transition: border-color .2s;
        }
        .field-input:focus { border-color: rgba(201,168,76,.5); background: rgba(201,168,76,.04); }
        .field-input::placeholder { color: rgba(255,255,255,.2); }
        select.field-input option { background: #100A01; color: #e2e8f0; }
        textarea.field-input { resize: vertical; min-height: 90px; }
        .field-label {
            display: block; font-size: 10px; font-weight: 700;
            letter-spacing: .12em; text-transform: uppercase;
            color: rgba(148,163,184,.6); margin-bottom: 6px;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .anim { animation: fadeUp .35s ease both; }
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
        <a href="/academy-admin/index.php" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-white/5 text-[13px] transition-all">Dashboard</a>
        <p class="text-[9px] font-bold tracking-[.15em] uppercase text-slate-700 px-2 pt-4 pb-1">Contenu</p>
        <a href="/academy-admin/courses.php" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-gold-500 bg-gold-900/20 font-semibold text-[13px]">Cours</a>
        <a href="/academy-admin/lessons.php" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-white/5 text-[13px] transition-all">Leçons</a>
        <a href="/academy-admin/pdfs.php" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-white/5 text-[13px] transition-all">PDF Payants</a>
        <p class="text-[9px] font-bold tracking-[.15em] uppercase text-slate-700 px-2 pt-4 pb-1">Données</p>
        <a href="/academy-admin/stats.php" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-white/5 text-[13px] transition-all">Statistiques</a>
        <a href="/academy-admin/emails.php" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-white/5 text-[13px] transition-all">Emails</a>
        <p class="text-[9px] font-bold tracking-[.15em] uppercase text-slate-700 px-2 pt-4 pb-1">App</p>
        <a href="/academy/" target="_blank" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-white/5 text-[13px] transition-all">Voir Academy</a>
        <a href="https://wari.digiroys.com/accueil/" target="_blank" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-white/5 text-[13px] transition-all">Retour Wari</a>
    </nav>
    <div class="px-3 py-4 border-t border-gold-900/20">
        <div class="flex items-center gap-3 px-2 py-2 mb-1">
            <div>
                <p class="text-[13px] font-semibold text-gold-400 leading-none"><?= htmlspecialchars($user) ?></p>
                <p class="text-[10px] text-slate-600 mt-0.5">Admin Academy</p>
            </div>
        </div>
        <a href="/academy-admin/logout.php" class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-600 hover:text-red-400 hover:bg-red-950/30 text-[12px] transition-all">Se déconnecter</a>
    </div>
</aside>

<!-- ════ MAIN ════════════════════════════════════════════════ -->
<div class="ml-56 flex-1 flex flex-col min-h-screen">

    <!-- Topbar -->
    <div class="bg-ink-900/80 backdrop-blur border-b border-gold-900/20 px-8 h-14 flex items-center justify-between sticky top-0 z-40">
        <div class="flex items-center gap-3">
            <a href="/academy-admin/index.php" class="text-slate-600 hover:text-gold-500 text-xs transition-colors">Dashboard</a>
            <span class="text-slate-700">/</span>
            <span class="font-bold text-slate-100 text-sm">Cours</span>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-[11px] text-slate-500"><?= count($courses) ?> cours au total</span>
            <a href="/academy-admin/courses.php?action=add"
               class="bg-gold-500 hover:bg-gold-400 text-ink-900 font-bold text-[12px] px-4 py-1.5 rounded-full transition-all">
                + Nouveau cours
            </a>
        </div>
    </div>

    <div class="p-8 flex-1">

        <!-- ── Messages ─────────────────────────────────── -->
        <?php if ($msg): ?>
        <div class="mb-6 bg-emerald-950/40 border border-emerald-800/40 text-emerald-400 rounded-xl px-5 py-3 text-sm anim">
            <?= $msg ?>
        </div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="mb-6 bg-red-950/40 border border-red-800/40 text-red-400 rounded-xl px-5 py-3 text-sm anim">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <!-- ════════════════════════════════════════════════
             FORMULAIRE AJOUT / ÉDITION
        ════════════════════════════════════════════════ -->
        <?php if ($action === 'add' || $action === 'edit'): ?>
        <div class="card-gold-top bg-ink-900 border border-gold-900/25 rounded-2xl p-7 mb-8 anim">

            <h2 class="font-bold text-slate-100 text-base mb-6 flex items-center gap-2">
                <?= $action === 'edit' ? '✏️ Modifier le cours' : '➕ Nouveau cours' ?>
            </h2>

            <form method="POST">
                <input type="hidden" name="action" value="<?= $action === 'edit' ? 'edit_course' : 'add_course' ?>">
                <?php if ($action === 'edit'): ?>
                <input type="hidden" name="id" value="<?= $courseEdit['id'] ?>">
                <?php endif; ?>

                <div class="grid grid-cols-2 gap-5 mb-5">

                    <!-- Titre -->
                    <div class="col-span-2">
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="field-label mb-0">Titre du cours *</label>
                            <button type="button" onclick="generateDraft()" id="btn-ai-draft"
                                    class="text-[10px] font-bold uppercase tracking-wider text-gold-500 hover:text-gold-400 flex items-center gap-1.5 transition-all opacity-70 hover:opacity-100">
                                <span class="text-xs">🪄</span> Draft Magique (IA)
                            </button>
                        </div>
                        <input type="text" id="course_title" name="titre" class="field-input"
                               placeholder="ex: Gérer son budget au quotidien"
                               value="<?= htmlspecialchars($courseEdit['titre'] ?? '') ?>"
                               required>
                    </div>

                    <!-- Catégorie -->
                    <div>
                        <label class="field-label">Catégorie *</label>
                        <select name="category_id" class="field-input" required>
                            <option value="">-- Choisir --</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"
                                <?= ($courseEdit['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                <?= $cat['icone'] . ' ' . htmlspecialchars($cat['titre']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Niveau -->
                    <div>
                        <label class="field-label">Niveau</label>
                        <select name="niveau" class="field-input">
                            <?php foreach (['debutant' => 'Débutant', 'intermediaire' => 'Intermédiaire', 'avance' => 'Avancé'] as $val => $lbl): ?>
                            <option value="<?= $val ?>"
                                <?= ($courseEdit['niveau'] ?? 'debutant') === $val ? 'selected' : '' ?>>
                                <?= $lbl ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Durée -->
                    <div>
                        <label class="field-label">Durée estimée (minutes)</label>
                        <input type="number" name="duree_minutes" class="field-input"
                               min="1" max="300"
                               value="<?= $courseEdit['duree_minutes'] ?? 10 ?>">
                    </div>

                    <!-- Auteur -->
                    <div>
                        <label class="field-label">Auteur</label>
                        <input type="text" name="auteur" class="field-input"
                               placeholder="Wari Finance"
                               value="<?= htmlspecialchars($courseEdit['auteur'] ?? 'Wari Finance') ?>">
                    </div>

                    <!-- Description -->
                    <div class="col-span-2">
                        <label class="field-label">Description</label>
                        <textarea name="description" class="field-input"
                                  placeholder="Décris en quelques phrases ce que l'apprenant va apprendre..."><?= htmlspecialchars($courseEdit['description'] ?? '') ?></textarea>
                    </div>

                    <!-- Checkboxes -->
                    <div class="col-span-2 flex gap-8">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="est_gratuit" value="1"
                                   class="accent-gold-500 w-4 h-4"
                                   <?= ($courseEdit['est_gratuit'] ?? 1) ? 'checked' : '' ?>>
                            <span class="text-[13px] text-slate-300">Cours gratuit</span>
                        </label>
                        <?php if ($action === 'edit'): ?>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="est_actif" value="1"
                                   class="accent-gold-500 w-4 h-4"
                                   <?= ($courseEdit['est_actif'] ?? 1) ? 'checked' : '' ?>>
                            <span class="text-[13px] text-slate-300">Cours actif (visible)</span>
                        </label>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Boutons -->
                <div class="flex items-center gap-3 pt-4 border-t border-gold-900/20">
                    <button type="submit"
                            class="bg-gold-500 hover:bg-gold-400 text-ink-900 font-bold text-[13px] px-6 py-2.5 rounded-full transition-all">
                        <?= $action === 'edit' ? '💾 Enregistrer les modifications' : '✅ Créer le cours' ?>
                    </button>
                    <a href="/academy-admin/courses.php"
                       class="text-slate-500 hover:text-slate-300 text-[13px] transition-colors px-4">
                        Annuler
                    </a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- ════════════════════════════════════════════════
             LISTE DES COURS — BENTO
        ════════════════════════════════════════════════ -->
        <?php if ($action === 'list'): ?>

        <!-- Stats rapides -->
        <div class="grid grid-cols-4 gap-4 mb-6">
            <?php
            $totalCours    = count($courses);
            $totalActifs   = count(array_filter($courses, fn($c) => $c['est_actif']));
            $totalLecons   = array_sum(array_column($courses, 'nb_lecons'));
            $totalApprenant = array_sum(array_column($courses, 'nb_apprenants'));
            ?>
            <?php foreach ([
                ['label' => 'Total cours',   'val' => $totalCours,     'icon' => '📚'],
                ['label' => 'Cours actifs',  'val' => $totalActifs,    'icon' => '✅'],
                ['label' => 'Total leçons',  'val' => $totalLecons,    'icon' => '📖'],
                ['label' => 'Apprenants',    'val' => $totalApprenant, 'icon' => '👥'],
            ] as $i => $s): ?>
            <div class="card-gold-top bg-ink-900 border border-gold-900/25 rounded-2xl p-5 anim" style="animation-delay:<?= $i * .05 ?>s">
                <div class="text-2xl opacity-70 mb-2"><?= $s['icon'] ?></div>
                <p class="font-black text-gold-500 text-3xl leading-none"><?= number_format($s['val']) ?></p>
                <p class="text-slate-600 text-[11px] mt-1"><?= $s['label'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Tableau des cours -->
        <div class="card-gold-top bg-ink-900 border border-gold-900/25 rounded-2xl overflow-hidden anim">

            <div class="px-6 py-4 border-b border-gold-900/20 flex items-center justify-between">
                <p class="font-bold text-slate-100 text-sm">📚 Tous les cours</p>
                <a href="/academy-admin/courses.php?action=add"
                   class="text-[11px] text-gold-700 hover:text-gold-500 font-semibold transition-colors">
                    + Ajouter →
                </a>
            </div>

            <?php if (!empty($courses)): ?>

            <!-- Header tableau -->
            <div class="grid grid-cols-12 gap-3 px-6 py-2.5 bg-white/[.02] border-b border-gold-900/10">
                <div class="col-span-4 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600">Cours</div>
                <div class="col-span-2 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600">Catégorie</div>
                <div class="col-span-1 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600 text-center">Leçons</div>
                <div class="col-span-1 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600 text-center">Appren.</div>
                <div class="col-span-1 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600 text-center">PDF</div>
                <div class="col-span-1 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600 text-center">Statut</div>
                <div class="col-span-2 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600 text-right">Actions</div>
            </div>

            <div class="divide-y divide-gold-900/10">
                <?php foreach ($courses as $i => $course): ?>
                <div class="grid grid-cols-12 gap-3 px-6 py-4 hover:bg-white/[.025] transition-colors items-center"
                     style="animation: fadeUp .3s ease <?= $i * .04 ?>s both">

                    <!-- Titre + niveau -->
                    <div class="col-span-4 min-w-0">
                        <p class="font-semibold text-slate-100 text-[13px] truncate">
                            <?= htmlspecialchars($course['titre']) ?>
                        </p>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-[10px] text-slate-600">
                                ⏱ <?= $course['duree_minutes'] ?> min
                            </span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full font-medium
                                <?= $course['niveau'] === 'debutant' ? 'bg-emerald-950/50 text-emerald-500' :
                                   ($course['niveau'] === 'intermediaire' ? 'bg-blue-950/50 text-blue-400' :
                                    'bg-orange-950/50 text-orange-400') ?>">
                                <?= ucfirst($course['niveau']) ?>
                            </span>
                            <?php if (!$course['est_gratuit']): ?>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-gold-900/30 text-gold-600 font-medium">
                                💰 Payant
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Catégorie -->
                    <div class="col-span-2">
                        <span class="text-[11px] font-medium text-slate-400">
                            <?= $course['cat_icone'] ?> <?= htmlspecialchars($course['cat_titre']) ?>
                        </span>
                    </div>

                    <!-- Leçons -->
                    <div class="col-span-1 text-center">
                        <span class="font-bold text-gold-500 text-sm"><?= $course['nb_lecons'] ?></span>
                    </div>

                    <!-- Apprenants -->
                    <div class="col-span-1 text-center">
                        <span class="font-bold text-slate-300 text-sm"><?= number_format($course['nb_apprenants']) ?></span>
                    </div>

                    <!-- PDF -->
                    <div class="col-span-1 text-center">
                        <span class="font-bold text-slate-400 text-sm"><?= $course['nb_pdfs'] ?></span>
                    </div>

                    <!-- Statut toggle -->
                    <div class="col-span-1 text-center">
                        <form method="POST">
                            <input type="hidden" name="action" value="toggle_actif">
                            <input type="hidden" name="id" value="<?= $course['id'] ?>">
                            <input type="hidden" name="est_actif" value="<?= $course['est_actif'] ? 0 : 1 ?>">
                            <button type="submit"
                                    class="text-[11px] px-2.5 py-1 rounded-full font-semibold transition-all
                                    <?= $course['est_actif']
                                        ? 'bg-emerald-950/50 text-emerald-500 border border-emerald-800/40 hover:bg-red-950/40 hover:text-red-400 hover:border-red-800/40'
                                        : 'bg-slate-800/50 text-slate-500 border border-slate-700/40 hover:bg-emerald-950/40 hover:text-emerald-400' ?>">
                                <?= $course['est_actif'] ? '✓ Actif' : '✗ Inactif' ?>
                            </button>
                        </form>
                    </div>

                    <!-- Actions -->
                    <div class="col-span-2 flex items-center justify-end gap-2">
                        <!-- Voir leçons -->
                        <a href="/academy-admin/lessons.php?course_id=<?= $course['id'] ?>"
                           title="Gérer les leçons"
                           class="w-7 h-7 rounded-lg bg-white/5 hover:bg-gold-900/30 flex items-center justify-center text-slate-500 hover:text-gold-500 transition-all text-sm">
                            📖
                        </a>
                        <!-- Voir sur Academy -->
                        <a href="/academy/course.php?slug=<?= urlencode($course['slug']) ?>"
                           target="_blank"
                           title="Voir sur Academy"
                           class="w-7 h-7 rounded-lg bg-white/5 hover:bg-blue-900/30 flex items-center justify-center text-slate-500 hover:text-blue-400 transition-all text-sm">
                            🌐
                        </a>
                        <!-- Éditer -->
                        <a href="/academy-admin/courses.php?action=edit&id=<?= $course['id'] ?>"
                           title="Modifier"
                           class="w-7 h-7 rounded-lg bg-white/5 hover:bg-gold-900/30 flex items-center justify-center text-slate-500 hover:text-gold-400 transition-all text-sm">
                            ✏️
                        </a>
                        <!-- Supprimer -->
                        <form method="POST" onsubmit="return confirm('Supprimer ce cours et toutes ses leçons ?')">
                            <input type="hidden" name="action" value="delete_course">
                            <input type="hidden" name="id" value="<?= $course['id'] ?>">
                            <button type="submit"
                                    title="Supprimer"
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
                <p class="text-4xl mb-4">📚</p>
                <p class="text-sm">Aucun cours pour le moment.</p>
                <a href="/academy-admin/courses.php?action=add"
                   class="inline-block mt-4 bg-gold-500 text-ink-900 font-bold text-[12px] px-5 py-2 rounded-full hover:bg-gold-400 transition-all">
                    Créer le premier cours →
                </a>
            </div>
            <?php endif; ?>

        </div>
        <?php endif; ?>

    </div>
</div>

    <script>
        async function generateDraft() {
            const titleInput = document.getElementById('course_title');
            const descInput  = document.querySelector('textarea[name="description"]');
            const btn        = document.getElementById('btn-ai-draft');
            const levelSelect = document.querySelector('select[name="niveau"]');
            const durationInput = document.querySelector('input[name="duree_minutes"]');

            if (!titleInput.value.trim()) {
                alert("Veuillez d'abord saisir un sujet dans le champ Titre.");
                titleInput.focus();
                return;
            }

            const originalBtnHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="text-xs">⌛</span> Génération...';
            btn.classList.add('animate-pulse');

            try {
                const formData = new FormData();
                formData.append('action', 'draft_course');
                formData.append('sujet', titleInput.value);

                const response = await fetch('ai_gateway.php', {
                    method: 'POST',
                    body: formData
                });

                let data = await response.json();
                
                // Sécurité : si l'IA renvoie un tableau au lieu d'un objet
                if (Array.isArray(data)) data = data[0];

                if (data.error) {
                    alert("Erreur IA : " + data.error);
                } else {
                    // Remplissage des champs
                    if (data.titre) titleInput.value = data.titre;
                    if (data.description) descInput.value = data.description;
                    if (data.niveau) levelSelect.value = data.niveau;
                    if (data.duree_minutes) durationInput.value = data.duree_minutes;

                    // Petit effet visuel
                    descInput.classList.add('ring-2', 'ring-gold-500/50');
                    setTimeout(() => descInput.classList.remove('ring-2', 'ring-gold-500/50'), 2000);
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
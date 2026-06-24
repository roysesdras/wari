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
            
            // Notification Web Push à tous les abonnés
            try {
                require_once __DIR__ . '/../classes/Push.php';
                $pushTitle = "Nouveau cours disponible ! 📚";
                $pushBody  = "Découvrez le cours : \"" . $titre . "\" sur Wari Academy.";
                $pushUrl   = "https://wari.digiroys.com/academy/course.php?slug=" . urlencode($slug) . "&utm_source=push&utm_campaign=new_course";
                Push::sendToAll($pdo, $pushTitle, $pushBody, $pushUrl, 'course', $slug);
            } catch (Exception $e) {
                error_log("Erreur envoi push nouveau cours : " . $e->getMessage());
            }

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
            $msg = "Cours mis à jour avec succès.";
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
            $msg = "Cours supprimé.";
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
        COUNT(DISTINCT pdf.id) as nb_pdfs,
        COALESCE(pl.push_sent, 0) as push_sent,
        COALESCE(pl.push_clicks, 0) as push_clicks
    FROM academy_courses co
    JOIN academy_categories c ON c.id = co.category_id
    LEFT JOIN academy_lessons l ON l.course_id = co.id
    LEFT JOIN academy_progress p ON p.course_id = co.id
    LEFT JOIN academy_pdfs pdf ON pdf.course_id = co.id
    LEFT JOIN (
        SELECT target_id, SUM(sent_count) as push_sent, SUM(click_count) as push_clicks
        FROM wari_push_logs
        WHERE type = 'course'
        GROUP BY target_id
    ) pl ON pl.target_id COLLATE utf8mb4_unicode_ci = co.slug COLLATE utf8mb4_unicode_ci
    GROUP BY co.id
    ORDER BY co.category_id ASC, co.ordre ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cours : Wari Academy Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

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
            <button onclick="openIdeatorModal()" class="bg-indigo-500/20 hover:bg-indigo-500/40 text-indigo-300 border border-indigo-500/30 font-bold text-[12px] px-4 py-1.5 rounded-full transition-all flex items-center gap-1.5">
                💡 Idées IA
            </button>
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
                <?php if ($action === 'edit'): ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gold-500"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/></svg>
                    Modifier le cours
                <?php else: ?>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gold-500"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                    Nouveau cours
                <?php endif; ?>
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
                            <div class="flex items-center gap-3">
                                <button type="button" onclick="generateDraft()" id="btn-ai-draft"
                                        class="text-[10px] font-bold uppercase tracking-wider text-slate-500 hover:text-slate-300 flex items-center gap-1.5 transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/></svg>
                                    Draft
                                </button>
                                <button type="button" onclick="generateAllAuto()" id="btn-ai-all"
                                        class="text-[10px] font-bold uppercase tracking-wider bg-gold-900/40 text-gold-500 hover:bg-gold-500 hover:text-ink-900 px-3 py-1.5 rounded-full flex items-center gap-1.5 transition-all border border-gold-500/50">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                                    Générer TOUT (Auto)
                                </button>
                            </div>
                        </div>
                        <input type="text" id="course_title" name="titre" class="field-input"
                               placeholder="ex: Gérer son budget au quotidien"
                               value="<?= htmlspecialchars($courseEdit['titre'] ?? $_GET['title'] ?? '') ?>"
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
                                <?php if (!in_array($cat['icone'], ['wallet','landmark','rocket','alert-triangle','trending-up','brain','book','lightbulb','target','award','gem','key','bar-chart','globe','briefcase','shield','zap','leaf'])): ?>
                                    <?= $cat['icone'] ?> 
                                <?php endif; ?>
                                <?= htmlspecialchars($cat['titre']) ?>
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
                            class="bg-gold-500 hover:bg-gold-400 text-ink-900 font-bold text-[13px] px-6 py-2.5 rounded-full transition-all flex items-center gap-2">
                        <?php if ($action === 'edit'): ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7"/><path d="M7 3v4a1 1 0 0 0 1 1h7"/></svg>
                            Enregistrer les modifications
                        <?php else: ?>
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                            Créer le cours
                        <?php endif; ?>
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
                ['label' => 'Total cours',   'val' => $totalCours,     'svg' => '<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/>'],
                ['label' => 'Cours actifs',  'val' => $totalActifs,    'svg' => '<path d="M20 6 9 17l-5-5"/>'],
                ['label' => 'Total leçons',  'val' => $totalLecons,    'svg' => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>'],
                ['label' => 'Apprenants',    'val' => $totalApprenant, 'svg' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>'],
            ] as $i => $s): ?>
            <div class="card-gold-top bg-ink-900 border border-gold-900/25 rounded-2xl p-5 anim" style="animation-delay:<?= $i * .05 ?>s">
                <div class="text-gold-700 mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?= $s['svg'] ?></svg>
                </div>
                <p class="font-black text-gold-500 text-3xl leading-none"><?= number_format($s['val']) ?></p>
                <p class="text-slate-600 text-[11px] mt-1"><?= $s['label'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Tableau des cours -->
        <div class="card-gold-top bg-ink-900 border border-gold-900/25 rounded-2xl overflow-hidden anim">

            <div class="px-6 py-4 border-b border-gold-900/20 flex items-center justify-between">
                <p class="font-bold text-slate-100 text-sm flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gold-700"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>
                    Tous les cours
                </p>
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
                            <span class="text-[10px] text-slate-600 flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                <?= $course['duree_minutes'] ?> min
                            </span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full font-medium
                                <?= $course['niveau'] === 'debutant' ? 'bg-emerald-950/50 text-emerald-500' :
                                   ($course['niveau'] === 'intermediaire' ? 'bg-blue-950/50 text-blue-400' :
                                    'bg-orange-950/50 text-orange-400') ?>">
                                <?= ucfirst($course['niveau']) ?>
                            </span>
                            <?php if (!$course['est_gratuit']): ?>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-gold-900/30 text-gold-600 font-medium flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 18V6"/></svg>
                                Payant
                            </span>
                            <?php endif; ?>
                            <?php if ($course['push_sent'] > 0): ?>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 font-medium flex items-center gap-1 border border-white/5" title="Notifications Web Push : Envoyées et cliquées">
                                📣 <?= $course['push_sent'] ?> env. / <?= $course['push_clicks'] ?> clics
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Catégorie -->
                    <div class="col-span-2">
                        <span class="text-[11px] font-medium text-slate-400 flex items-center gap-1.5">
                            <?php
                            $lucideIcons = ['wallet','landmark','rocket','alert-triangle','trending-up','brain','book','lightbulb','target','award','gem','key','bar-chart','globe','briefcase','shield','zap','leaf'];
                            if (in_array($course['cat_icone'], $lucideIcons)): ?>
                                <i data-lucide="<?= $course['cat_icone'] ?>" class="w-3 h-3 shrink-0"></i>
                            <?php else: ?>
                                <?= $course['cat_icone'] ?>
                            <?php endif; ?>
                            <?= htmlspecialchars($course['cat_titre']) ?>
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
                                <?= $course['est_actif'] ? 'Actif' : 'Inactif' ?>
                            </button>
                        </form>
                    </div>

                    <!-- Actions -->
                    <div class="col-span-2 flex items-center justify-end gap-2">
                        <!-- Push -->
                        <button type="button" onclick="sendPushNotification(<?= $course['id'] ?>)"
                           title="Envoyer Notification Push"
                           class="w-7 h-7 rounded-lg bg-indigo-500/10 hover:bg-indigo-500/30 flex items-center justify-center text-indigo-400 hover:text-indigo-300 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 17H2a3 3 0 0 0 3-3V9a7 7 0 0 1 14 0v5a3 3 0 0 0 3 3zm-8.27 4a2 2 0 0 1-3.46 0"/></svg>
                        </button>
                        <!-- Voir leçons -->
                        <a href="/academy-admin/lessons.php?course_id=<?= $course['id'] ?>"
                           title="Gérer les leçons"
                           class="w-7 h-7 rounded-lg bg-white/5 hover:bg-gold-900/30 flex items-center justify-center text-slate-500 hover:text-gold-500 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                        </a>
                        <!-- Voir sur Academy -->
                        <a href="/academy/course.php?slug=<?= urlencode($course['slug']) ?>"
                           target="_blank"
                           title="Voir sur Academy"
                           class="w-7 h-7 rounded-lg bg-white/5 hover:bg-blue-900/30 flex items-center justify-center text-slate-500 hover:text-blue-400 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
                        </a>
                        <!-- Éditer -->
                        <a href="/academy-admin/courses.php?action=edit&id=<?= $course['id'] ?>"
                           title="Modifier"
                           class="w-7 h-7 rounded-lg bg-white/5 hover:bg-gold-900/30 flex items-center justify-center text-slate-500 hover:text-gold-400 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/></svg>
                        </a>
                        <!-- Supprimer -->
                        <form method="POST" onsubmit="return confirm('Supprimer ce cours et toutes ses leçons ?')">
                            <input type="hidden" name="action" value="delete_course">
                            <input type="hidden" name="id" value="<?= $course['id'] ?>">
                            <button type="submit"
                                    title="Supprimer"
                                    class="w-7 h-7 rounded-lg bg-white/5 hover:bg-red-950/40 flex items-center justify-center text-slate-600 hover:text-red-400 transition-all">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php else: ?>
            <div class="px-6 py-16 text-center text-slate-600">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-4 opacity-30"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>
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

<!-- Modal Générateur Auto -->
<div id="auto-generator-modal" class="fixed inset-0 bg-ink-900/90 backdrop-blur-sm z-[100] flex items-center justify-center hidden opacity-0 transition-opacity duration-300">
    <div class="bg-ink-800 border border-gold-900/50 rounded-2xl p-8 max-w-md w-full shadow-2xl relative overflow-hidden flex flex-col max-h-[90vh]">
        <div class="absolute top-0 left-0 right-0 h-1 bg-ink-900">
            <div id="ag-progress-bar" class="h-full bg-gold-500 w-0 transition-all duration-500 ease-out"></div>
        </div>
        <div class="text-center mb-6 shrink-0">
            <div class="w-16 h-16 rounded-full bg-gold-900/30 border-2 border-gold-500/30 flex items-center justify-center mx-auto mb-4 relative">
                <svg id="ag-spinner" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gold-500 animate-spin"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                <svg id="ag-success" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-emerald-500 hidden"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <h3 class="text-xl font-bold text-white mb-2" id="ag-title">Génération Magique</h3>
            <p class="text-sm text-slate-400" id="ag-status">Préparation du moteur IA...</p>
        </div>
        <div class="space-y-3 overflow-y-auto flex-1 pr-2" id="ag-steps" style="min-height: 150px;">
            <!-- Étapes insérées ici par JS -->
        </div>
        <div class="mt-8 text-center hidden shrink-0" id="ag-btn-container">
            <a href="/academy-admin/courses.php" class="inline-block bg-gold-500 hover:bg-gold-400 text-ink-900 font-bold text-sm px-6 py-2.5 rounded-full transition-all shadow-[0_0_20px_rgba(201,168,76,0.3)]">
                Voir mon nouveau cours !
            </a>
        </div>
    </div>
</div>

<!-- Modal Ideator IA -->
<div id="ideator-modal" class="fixed inset-0 bg-ink-900/90 backdrop-blur-sm z-[100] flex items-center justify-center hidden opacity-0 transition-opacity duration-300">
    <div class="bg-ink-800 border border-indigo-500/30 rounded-2xl p-8 max-w-lg w-full shadow-[0_0_40px_rgba(99,102,241,0.15)] relative flex flex-col max-h-[90vh]">
        
        <div class="flex justify-between items-start mb-6">
            <div>
                <h3 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="text-indigo-400">💡</span> Idées de cours
                </h3>
                <p class="text-[13px] text-slate-400 mt-1">L'IA génère des titres percutants (sans doublons).</p>
            </div>
            <button onclick="closeIdeatorModal()" class="text-slate-500 hover:text-white transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>

        <!-- Formulaire de thème -->
        <div id="ideator-form" class="mb-4">
            <label class="block text-[12px] font-bold text-slate-400 uppercase tracking-widest mb-2">Sur quel thème ? (optionnel)</label>
            <div class="flex gap-2">
                <input type="text" id="ideator-theme" placeholder="ex: sortir des dettes, l'immobilier, budget..." class="flex-1 bg-ink-900 border border-indigo-500/30 rounded-xl px-4 py-2 text-sm text-slate-200 outline-none focus:border-indigo-500/60 transition-colors">
                <button onclick="fetchCourseIdeas()" class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold px-5 py-2 rounded-xl transition-colors">Générer</button>
            </div>
        </div>

        <!-- Zone de chargement -->
        <div id="ideator-loading" class="hidden py-12 flex flex-col items-center justify-center text-center">
            <svg class="w-10 h-10 text-indigo-500 animate-spin mb-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
            <p class="text-indigo-300 font-medium">Réflexion en cours...</p>
            <p class="text-[11px] text-slate-500 mt-2">Recherche de concepts forts et inédits</p>
        </div>

        <!-- Liste des idées -->
        <div id="ideator-results" class="hidden flex-1 overflow-y-auto space-y-3 pr-2">
            <!-- Injecté via JS -->
        </div>

        <div id="ideator-actions" class="mt-6 pt-4 border-t border-indigo-900/30 text-right hidden">
            <button onclick="fetchCourseIdeas()" class="text-[12px] text-indigo-400 hover:text-indigo-300 font-medium flex items-center gap-1.5 ml-auto">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 21v-5h5"/></svg>
                Regénérer d'autres idées
            </button>
        </div>
    </div>
</div>

    <script>
        async function generateAllAuto() {
            const titleInput = document.getElementById('course_title');
            const categoryInput = document.querySelector('select[name="category_id"]');
            
            if (!titleInput.value.trim() || !categoryInput.value) {
                alert("Veuillez d'abord saisir un titre ET choisir une catégorie.");
                return;
            }

            const modal = document.getElementById('auto-generator-modal');
            const statusTxt = document.getElementById('ag-status');
            const stepsDiv = document.getElementById('ag-steps');
            const progressBar = document.getElementById('ag-progress-bar');
            
            // Afficher le modal
            modal.classList.remove('hidden');
            setTimeout(() => modal.classList.remove('opacity-0'), 50);
            
            stepsDiv.innerHTML = '';
            
            const addStep = (text) => {
                const div = document.createElement('div');
                div.className = 'flex items-center gap-3 text-sm text-slate-300 bg-white/5 p-3 rounded-lg border border-white/5 anim';
                div.innerHTML = `<svg class="w-4 h-4 text-gold-500 shrink-0 animate-spin" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> <span>${text}</span>`;
                stepsDiv.appendChild(div);
                stepsDiv.scrollTop = stepsDiv.scrollHeight;
                return div;
            };

            const completeStep = (div) => {
                const svg = div.querySelector('svg');
                svg.classList.remove('animate-spin', 'text-gold-500');
                svg.classList.add('text-emerald-500');
                svg.innerHTML = '<polyline points="20 6 9 17 4 12"></polyline>';
                div.classList.add('border-emerald-500/30', 'bg-emerald-500/5');
            };

            try {
                // ETAPE 1: Brouillon
                statusTxt.innerText = "Création du plan du cours...";
                progressBar.style.width = '10%';
                let step1 = addStep("Analyse du sujet et structuration...");
                
                let fd1 = new FormData();
                fd1.append('action', 'draft_course');
                fd1.append('sujet', titleInput.value);
                
                let res1 = await fetch('ai_gateway.php', { method: 'POST', body: fd1 });
                let draft = await res1.json();
                if (Array.isArray(draft)) draft = draft[0];
                
                if (draft.error) throw new Error(draft.error);
                completeStep(step1);

                // ETAPE 2: Sauvegarde Cours
                statusTxt.innerText = "Sauvegarde de la coquille vide...";
                progressBar.style.width = '20%';
                let step2 = addStep("Création de l'architecture en base de données...");
                
                let fd2 = new FormData();
                fd2.append('action', 'save_draft_course');
                fd2.append('titre', draft.titre || titleInput.value);
                fd2.append('description', draft.description || '');
                fd2.append('niveau', draft.niveau || 'debutant');
                fd2.append('duree_minutes', draft.duree_minutes || 10);
                fd2.append('category_id', categoryInput.value);

                let res2 = await fetch('ai_gateway.php', { method: 'POST', body: fd2 });
                let saveCourse = await res2.json();
                
                if (!saveCourse.success) throw new Error(saveCourse.error || "Erreur sauvegarde cours");
                let courseId = saveCourse.course_id;
                completeStep(step2);

                // ETAPE 3: Boucle des leçons
                let lecons = draft.lecons || [];
                if (!Array.isArray(lecons) || lecons.length === 0) throw new Error("L'IA n'a généré aucune leçon.");
                
                for (let i = 0; i < lecons.length; i++) {
                    let pct = 20 + (((i+0.5) / lecons.length) * 80);
                    progressBar.style.width = pct + '%';
                    statusTxt.innerText = `Rédaction de la leçon ${i+1}/${lecons.length}...`;
                    
                    let lessonStep = addStep(`Rédaction IA : ${lecons[i].titre}`);
                    
                    // Générer le HTML
                    let fd3 = new FormData();
                    fd3.append('action', 'write_lesson');
                    fd3.append('titre_lecon', lecons[i].titre);
                    fd3.append('cours_context', `${draft.titre} - ${draft.description}`);
                    
                    let res3 = await fetch('ai_gateway.php', { method: 'POST', body: fd3 });
                    let lessonContent = await res3.json();
                    
                    if (lessonContent.error) throw new Error(`Erreur leçon ${i+1}: ${lessonContent.error}`);
                    
                    // Sauvegarder leçon
                    let fd4 = new FormData();
                    fd4.append('action', 'save_draft_lesson');
                    fd4.append('course_id', courseId);
                    fd4.append('titre', lecons[i].titre);
                    fd4.append('contenu', lessonContent.contenu);
                    fd4.append('type', lecons[i].type === 'video' ? 'video' : 'texte');
                    fd4.append('ordre', i + 1);
                    
                    let res4 = await fetch('ai_gateway.php', { method: 'POST', body: fd4 });
                    let saveLesson = await res4.json();
                    
                    if (!saveLesson.success) throw new Error("Erreur sauvegarde leçon en BDD");
                    
                    completeStep(lessonStep);
                }
                
                // FINALISATION
                progressBar.style.width = '100%';
                statusTxt.innerText = "Génération terminée avec succès !";
                statusTxt.classList.replace('text-slate-400', 'text-emerald-400');
                document.getElementById('ag-spinner').classList.add('hidden');
                document.getElementById('ag-success').classList.remove('hidden');
                
                // L'envoi de la notification Push a été retiré ici pour laisser l'utilisateur vérifier le cours avant de publier.
                document.getElementById('ag-btn-container').innerHTML = `
                    <a href="/academy-admin/lessons.php?course_id=${courseId}" class="inline-block bg-gold-500 hover:bg-gold-400 text-ink-900 font-bold text-sm px-6 py-2.5 rounded-full transition-all shadow-[0_0_20px_rgba(201,168,76,0.3)]">
                        Voir les leçons générées
                    </a>
                `;
                document.getElementById('ag-btn-container').classList.remove('hidden');

            } catch (err) {
                console.error(err);
                statusTxt.innerText = "Erreur fatale : " + err.message;
                statusTxt.classList.replace('text-slate-400', 'text-red-400');
                document.getElementById('ag-spinner').classList.add('hidden');
                
                // Afficher le bouton pour fermer la modale
                let btnCont = document.getElementById('ag-btn-container');
                btnCont.classList.remove('hidden');
                btnCont.innerHTML = `<button onclick="document.getElementById('auto-generator-modal').classList.add('hidden')" class="bg-red-500/20 text-red-500 font-bold px-6 py-2.5 rounded-full hover:bg-red-500/30">Fermer</button>`;
            }
        }

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
            btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="animate-spin"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Génération...';
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

        // --- IDEATOR IA ---
        function openIdeatorModal() {
            const modal = document.getElementById('ideator-modal');
            modal.classList.remove('hidden');
            setTimeout(() => modal.classList.remove('opacity-0'), 50);
            
            document.getElementById('ideator-form').classList.remove('hidden');
            document.getElementById('ideator-loading').classList.add('hidden');
            document.getElementById('ideator-results').classList.add('hidden');
            document.getElementById('ideator-actions').classList.add('hidden');
            document.getElementById('ideator-theme').value = '';
            document.getElementById('ideator-theme').focus();
        }

        function closeIdeatorModal() {
            const modal = document.getElementById('ideator-modal');
            modal.classList.add('opacity-0');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }

        async function fetchCourseIdeas() {
            let theme = document.getElementById('ideator-theme').value.trim();
            
            document.getElementById('ideator-form').classList.add('hidden');
            document.getElementById('ideator-loading').classList.remove('hidden');
            document.getElementById('ideator-results').classList.add('hidden');
            document.getElementById('ideator-actions').classList.add('hidden');
            document.getElementById('ideator-results').innerHTML = '';
            
            // Reset loading state
            document.getElementById('ideator-loading').innerHTML = `
                <svg class="w-10 h-10 text-indigo-500 animate-spin mb-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                <p class="text-indigo-300 font-medium">Réflexion en cours...</p>
                <p class="text-[11px] text-slate-500 mt-2">Recherche de concepts forts et inédits${theme ? ` sur "${theme}"` : ''}</p>
            `;

            try {
                let fd = new FormData();
                fd.append('action', 'generate_course_ideas');
                if (theme) fd.append('theme', theme);
                
                let res = await fetch('ai_gateway.php', { method: 'POST', body: fd });
                let data = await res.json();
                
                if (data.error) throw new Error(data.error);
                
                let idees = data.idees || [];
                if (!Array.isArray(idees) || idees.length === 0) {
                    if (Array.isArray(data)) idees = data;
                    else throw new Error("Format JSON invalide");
                }

                idees.forEach(titre => {
                    let safeTitle = titre.replace(/"/g, '&quot;').replace(/'/g, "\\'");
                    document.getElementById('ideator-results').innerHTML += `
                        <div class="bg-white/5 border border-white/5 p-4 rounded-xl hover:bg-white/10 transition-colors flex justify-between items-center gap-4 anim">
                            <p class="text-[13px] font-semibold text-slate-200 flex-1 leading-snug">${titre}</p>
                            <button onclick="useIdea('${safeTitle}')" class="bg-indigo-500 hover:bg-indigo-400 text-white text-[11px] font-bold px-3 py-1.5 rounded-lg whitespace-nowrap transition-colors shadow-[0_0_15px_rgba(99,102,241,0.3)]">
                                Utiliser
                            </button>
                        </div>
                    `;
                });

                document.getElementById('ideator-loading').classList.add('hidden');
                document.getElementById('ideator-results').classList.remove('hidden');
                document.getElementById('ideator-actions').classList.remove('hidden');

            } catch (err) {
                console.error(err);
                document.getElementById('ideator-loading').innerHTML = `
                    <p class="text-red-400 font-bold mb-4">Erreur : ${err.message}</p>
                    <button onclick="fetchCourseIdeas()" class="bg-indigo-500 hover:bg-indigo-400 px-5 py-2 rounded-lg text-white text-xs font-bold transition-colors">Réessayer</button>
                `;
            }
        }

        function useIdea(title) {
            closeIdeatorModal();
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('action') === 'add') {
                document.getElementById('course_title').value = title;
            } else {
                window.location.href = '/academy-admin/courses.php?action=add&title=' + encodeURIComponent(title);
            }
        }

        async function sendPushNotification(courseId) {
            if(!confirm("⚠️ Veux-tu vraiment envoyer une notification Push à TOUS les utilisateurs pour annoncer ce cours ?\nAssure-toi de l'avoir relu avant !")) return;
            
            try {
                let fd = new FormData();
                fd.append('action', 'notify_course_published');
                fd.append('course_id', courseId);
                
                let res = await fetch('ai_gateway.php', { method: 'POST', body: fd });
                let data = await res.json();
                
                if (data.error) throw new Error(data.error);
                
                alert("✅ Notification Push envoyée avec succès à tous les utilisateurs !");
                window.location.reload();
            } catch (err) {
                alert("❌ Erreur lors de l'envoi : " + err.message);
            }
        }
    </script>
</body>
</html>
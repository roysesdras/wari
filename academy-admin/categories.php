<?php
// /var/www/html/academy-admin/categories.php

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['academy_user'])) {
    header('Location: /academy-admin/login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$user   = $_SESSION['academy_user'];
$action = $_GET['action'] ?? 'list';
$msg    = '';
$error  = '';

// Emojis disponibles pour les icônes
$emojis = ['💰','🏦','🚀','⚠️','📈','🧠','📚','💡','🎯','🏆','💎','🔑','📊','🌍','💼','🛡️','⚡','🌱'];

// ════════════════════════════════════════════════════════
// TRAITEMENT POST
// ════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    // ── Ajouter une catégorie
    if ($postAction === 'add_category') {
        $titre       = trim($_POST['titre'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $icone       = trim($_POST['icone'] ?? '📚');
        $couleur     = trim($_POST['couleur'] ?? '#C9A84C');
        $ordre       = (int)($_POST['ordre'] ?? 0);

        // Génération du slug
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-',
            iconv('UTF-8', 'ASCII//TRANSLIT', $titre)
        ), '-'));

        // Vérifier unicité du slug
        $check = $pdo->prepare("SELECT id FROM academy_categories WHERE slug = ?");
        $check->execute([$slug]);
        if ($check->fetch()) {
            $slug .= '-' . time();
        }

        // Ordre auto
        if (!$ordre) {
            $maxOrdre = $pdo->query("SELECT MAX(ordre) FROM academy_categories")->fetchColumn();
            $ordre = ((int)$maxOrdre) + 1;
        }

        if ($titre) {
            $pdo->prepare("
                INSERT INTO academy_categories (slug, titre, description, icone, couleur, ordre)
                VALUES (?, ?, ?, ?, ?, ?)
            ")->execute([$slug, $titre, $description, $icone, $couleur, $ordre]);
            $msg    = "✅ Catégorie <strong>" . htmlspecialchars($titre) . "</strong> créée.";
            $action = 'list';
        } else {
            $error  = "Le titre est obligatoire.";
            $action = 'add';
        }
    }

    // ── Modifier une catégorie
    if ($postAction === 'edit_category') {
        $id          = (int)($_POST['id'] ?? 0);
        $titre       = trim($_POST['titre'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $icone       = trim($_POST['icone'] ?? '📚');
        $couleur     = trim($_POST['couleur'] ?? '#C9A84C');
        $ordre       = (int)($_POST['ordre'] ?? 0);
        $est_actif   = isset($_POST['est_actif']) ? 1 : 0;

        if ($id && $titre) {
            $pdo->prepare("
                UPDATE academy_categories
                SET titre = ?, description = ?, icone = ?, couleur = ?, ordre = ?, est_actif = ?
                WHERE id = ?
            ")->execute([$titre, $description, $icone, $couleur, $ordre, $est_actif, $id]);
            $msg    = "✅ Catégorie mise à jour.";
            $action = 'list';
        } else {
            $error = "Le titre est obligatoire.";
        }
    }

    // ── Supprimer une catégorie
    if ($postAction === 'delete_category') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            // Vérifier si des cours y sont liés
            $nbCours = $pdo->prepare("SELECT COUNT(*) FROM academy_courses WHERE category_id = ?");
            $nbCours->execute([$id]);
            if ((int)$nbCours->fetchColumn() > 0) {
                $error  = "Impossible de supprimer : cette catégorie contient des cours. Supprime ou déplace les cours d'abord.";
                $action = 'list';
            } else {
                $pdo->prepare("DELETE FROM academy_categories WHERE id = ?")->execute([$id]);
                $msg    = "🗑️ Catégorie supprimée.";
                $action = 'list';
            }
        }
    }

    // ── Toggle actif/inactif
    if ($postAction === 'toggle_actif') {
        $id  = (int)($_POST['id'] ?? 0);
        $val = (int)($_POST['est_actif'] ?? 0);
        $pdo->prepare("UPDATE academy_categories SET est_actif = ? WHERE id = ?")->execute([$val, $id]);
        $action = 'list';
    }

    // ── Réordonner
    if ($postAction === 'reorder') {
        $id        = (int)($_POST['id'] ?? 0);
        $direction = $_POST['direction'] ?? 'up';

        $curr = $pdo->prepare("SELECT ordre FROM academy_categories WHERE id = ?");
        $curr->execute([$id]);
        $currentOrdre = (int)$curr->fetch(PDO::FETCH_ASSOC)['ordre'];

        if ($direction === 'up') {
            $swap = $pdo->prepare("SELECT id, ordre FROM academy_categories WHERE ordre < ? ORDER BY ordre DESC LIMIT 1");
        } else {
            $swap = $pdo->prepare("SELECT id, ordre FROM academy_categories WHERE ordre > ? ORDER BY ordre ASC LIMIT 1");
        }
        $swap->execute([$currentOrdre]);
        $swapCat = $swap->fetch(PDO::FETCH_ASSOC);

        if ($swapCat) {
            $pdo->prepare("UPDATE academy_categories SET ordre = ? WHERE id = ?")->execute([$swapCat['ordre'], $id]);
            $pdo->prepare("UPDATE academy_categories SET ordre = ? WHERE id = ?")->execute([$currentOrdre, $swapCat['id']]);
        }
        $action = 'list';
    }
}

// ── Catégorie à éditer
$catEdit = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM academy_categories WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $catEdit = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$catEdit) $action = 'list';
}

// ── Liste des catégories avec stats
$categories = $pdo->query("
    SELECT c.*,
        COUNT(DISTINCT co.id) AS nb_cours,
        COUNT(DISTINCT p.user_id) AS nb_apprenants
    FROM academy_categories c
    LEFT JOIN academy_courses co ON co.category_id = c.id AND co.est_actif = 1
    LEFT JOIN academy_progress p ON p.course_id = co.id
    GROUP BY c.id
    ORDER BY c.ordre ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catégories — Wari Academy Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Poppins', 'sans-serif'] },
                    colors: {
                        gold: {
                            50:'#FFFBEB',100:'#FEF3C7',200:'#FDE68A',
                            300:'#FCD34D',400:'#F0D080',500:'#C9A84C',
                            600:'#B8950A',700:'#8B6914',800:'#6B4F10',900:'#3D2B0F',
                        },
                        ink: {
                            50:'#F5F0E8',100:'#E8DFC8',200:'#D4C09A',
                            300:'#B89A60',400:'#8B6914',500:'#5A3E10',
                            600:'#2A1A04',700:'#1A0F02',800:'#100A01',900:'#0A0601',
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
        textarea.field-input { resize: vertical; min-height: 80px; }
        .field-label {
            display: block; font-size: 10px; font-weight: 700;
            letter-spacing: .12em; text-transform: uppercase;
            color: rgba(148,163,184,.6); margin-bottom: 6px;
        }

        /* Sélecteur emoji */
        .emoji-picker { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
        .emoji-btn {
            width: 36px; height: 36px; border-radius: 8px;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.06);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; cursor: pointer;
            transition: all .2s;
        }
        .emoji-btn:hover, .emoji-btn.selected {
            background: rgba(201,168,76,.15);
            border-color: rgba(201,168,76,.4);
        }

        /* Aperçu couleur */
        .color-preview {
            width: 36px; height: 36px; border-radius: 8px;
            border: 2px solid rgba(255,255,255,.1);
            cursor: pointer; overflow: hidden;
        }
        .color-preview input[type="color"] {
            width: 100%; height: 100%;
            border: none; padding: 0; cursor: pointer;
            background: none;
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
        <a href="/academy-admin/index.php" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-white/5 text-[13px] transition-all"><span>📊</span> Dashboard</a>
        <p class="text-[9px] font-bold tracking-[.15em] uppercase text-slate-700 px-2 pt-4 pb-1">Contenu</p>
        <a href="/academy-admin/categories.php" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-gold-500 bg-gold-900/20 font-semibold text-[13px]"><span>🗂</span> Catégories</a>
        <a href="/academy-admin/courses.php" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-white/5 text-[13px] transition-all"><span>📚</span> Cours</a>
        <a href="/academy-admin/lessons.php" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-white/5 text-[13px] transition-all"><span>📖</span> Leçons</a>
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
            <span class="font-bold text-slate-100 text-sm">Catégories</span>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-[11px] text-slate-500"><?= count($categories) ?> catégorie<?= count($categories) > 1 ? 's' : '' ?></span>
            <a href="/academy-admin/categories.php?action=add"
               class="bg-gold-500 hover:bg-gold-400 text-ink-900 font-bold text-[12px] px-4 py-1.5 rounded-full transition-all">
                + Nouvelle catégorie
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
                <?= $action === 'edit' ? '✏️ Modifier la catégorie' : '➕ Nouvelle catégorie' ?>
            </h2>

            <form method="POST" id="cat-form">
                <input type="hidden" name="action" value="<?= $action === 'edit' ? 'edit_category' : 'add_category' ?>">
                <input type="hidden" name="icone" id="icone-hidden" value="<?= htmlspecialchars($catEdit['icone'] ?? '📚') ?>">
                <?php if ($action === 'edit'): ?>
                <input type="hidden" name="id" value="<?= $catEdit['id'] ?>">
                <?php endif; ?>

                <div class="grid grid-cols-2 gap-5 mb-5">

                    <!-- Titre -->
                    <div class="col-span-2">
                        <label class="field-label">Titre de la catégorie *</label>
                        <input type="text" name="titre" class="field-input"
                               placeholder="ex: Gérer son budget au quotidien"
                               value="<?= htmlspecialchars($catEdit['titre'] ?? '') ?>"
                               required>
                    </div>

                    <!-- Description -->
                    <div class="col-span-2">
                        <label class="field-label">Description</label>
                        <textarea name="description" class="field-input"
                                  placeholder="Une courte description de cette thématique..."><?= htmlspecialchars($catEdit['description'] ?? '') ?></textarea>
                    </div>

                    <!-- Emoji icône -->
                    <div>
                        <label class="field-label">Icône (emoji)</label>
                        <div class="flex items-center gap-3 mb-2">
                            <div id="emoji-preview"
                                 class="w-12 h-12 rounded-xl bg-gold-900/20 border border-gold-900/30 flex items-center justify-center text-2xl">
                                <?= $catEdit['icone'] ?? '📚' ?>
                            </div>
                            <span class="text-[12px] text-slate-500">Clique sur un emoji pour le sélectionner</span>
                        </div>
                        <div class="emoji-picker">
                            <?php foreach ($emojis as $emoji): ?>
                            <button type="button"
                                    class="emoji-btn <?= ($catEdit['icone'] ?? '📚') === $emoji ? 'selected' : '' ?>"
                                    onclick="selectEmoji('<?= $emoji ?>')">
                                <?= $emoji ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Couleur + ordre -->
                    <div class="flex flex-col gap-4">
                        <div>
                            <label class="field-label">Couleur d'accentuation</label>
                            <div class="flex items-center gap-3">
                                <div class="color-preview" id="color-preview-box"
                                     style="background:<?= $catEdit['couleur'] ?? '#C9A84C' ?>">
                                    <input type="color" name="couleur"
                                           value="<?= $catEdit['couleur'] ?? '#C9A84C' ?>"
                                           oninput="updateColorPreview(this.value)">
                                </div>
                                <div>
                                    <p class="text-[13px] text-slate-300 font-medium" id="color-val">
                                        <?= $catEdit['couleur'] ?? '#C9A84C' ?>
                                    </p>
                                    <p class="text-[11px] text-slate-600">Couleur des cartes et barres</p>
                                </div>
                            </div>
                            <!-- Couleurs rapides -->
                            <div class="flex gap-2 mt-3">
                                <?php foreach (['#C9A84C','#4CAF50','#2196F3','#FF9800','#F44336','#9C27B0','#00BCD4','#E91E63'] as $clr): ?>
                                <button type="button"
                                        onclick="updateColorPreview('<?= $clr ?>')"
                                        class="w-6 h-6 rounded-full border-2 border-transparent hover:border-white/40 transition-all"
                                        style="background:<?= $clr ?>">
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div>
                            <label class="field-label">Ordre d'affichage</label>
                            <input type="number" name="ordre" class="field-input"
                                   min="1" placeholder="Auto si vide"
                                   value="<?= $catEdit['ordre'] ?? '' ?>">
                        </div>
                    </div>

                    <!-- Actif (edit seulement) -->
                    <?php if ($action === 'edit'): ?>
                    <div class="col-span-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="est_actif" value="1"
                                   class="accent-gold-500 w-4 h-4"
                                   <?= ($catEdit['est_actif'] ?? 1) ? 'checked' : '' ?>>
                            <span class="text-[13px] text-slate-300">Catégorie active (visible sur Academy)</span>
                        </label>
                    </div>
                    <?php endif; ?>

                    <!-- Aperçu de la carte -->
                    <div class="col-span-2">
                        <label class="field-label">Aperçu de la carte</label>
                        <div class="inline-flex flex-col items-center gap-2 bg-white/5 border border-white/8 rounded-xl p-4 w-36 text-center relative overflow-hidden" id="card-preview">
                            <div class="absolute top-0 left-0 right-0 h-0.5 transition-all" id="card-top-bar" style="background:#C9A84C"></div>
                            <span class="text-2xl" id="preview-icon"><?= $catEdit['icone'] ?? '📚' ?></span>
                            <span class="text-[12px] font-semibold text-slate-200" id="preview-titre">
                                <?= htmlspecialchars($catEdit['titre'] ?? 'Titre catégorie') ?>
                            </span>
                            <span class="text-[10px] text-slate-600">0 cours</span>
                        </div>
                    </div>

                </div>

                <!-- Boutons -->
                <div class="flex items-center gap-3 pt-4 border-t border-gold-900/20">
                    <button type="submit"
                            class="bg-gold-500 hover:bg-gold-400 text-ink-900 font-bold text-[13px] px-6 py-2.5 rounded-full transition-all">
                        <?= $action === 'edit' ? '💾 Enregistrer' : '✅ Créer la catégorie' ?>
                    </button>
                    <a href="/academy-admin/categories.php"
                       class="text-slate-500 hover:text-slate-300 text-[13px] transition-colors px-4">
                        Annuler
                    </a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- ════════════════════════════════════════════════
             LISTE DES CATÉGORIES
        ════════════════════════════════════════════════ -->
        <?php if ($action === 'list'): ?>

        <!-- Bento cartes aperçu -->
        <div class="grid grid-cols-6 gap-3 mb-6 anim">
            <?php foreach ($categories as $i => $cat): ?>
            <div class="relative bg-ink-900 border border-gold-900/20 rounded-xl p-4 text-center overflow-hidden"
                 style="animation-delay:<?= $i * .05 ?>s">
                <div class="absolute top-0 left-0 right-0 h-0.5" style="background:<?= $cat['couleur'] ?>"></div>
                <div class="text-2xl mb-2"><?= $cat['icone'] ?></div>
                <p class="text-[11px] font-semibold text-slate-300 leading-tight mb-1">
                    <?= htmlspecialchars($cat['titre']) ?>
                </p>
                <p class="text-[10px] text-slate-600"><?= $cat['nb_cours'] ?> cours</p>
                <?php if (!$cat['est_actif']): ?>
                <span class="absolute top-2 right-2 text-[8px] bg-red-950/60 text-red-500 px-1.5 py-0.5 rounded-full">Inactif</span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <!-- Bouton ajouter -->
            <a href="/academy-admin/categories.php?action=add"
               class="relative bg-ink-900/50 border border-dashed border-gold-900/30 rounded-xl p-4 text-center hover:bg-gold-900/10 hover:border-gold-900/50 transition-all flex flex-col items-center justify-center gap-2">
                <span class="text-2xl text-gold-900">➕</span>
                <span class="text-[11px] text-slate-600 hover:text-gold-700">Nouvelle</span>
            </a>
        </div>

        <!-- Tableau de gestion -->
        <div class="card-gold-top bg-ink-900 border border-gold-900/25 rounded-2xl overflow-hidden anim">

            <div class="px-6 py-4 border-b border-gold-900/20 flex items-center justify-between">
                <p class="font-bold text-slate-100 text-sm">🗂 Toutes les catégories</p>
                <a href="/academy-admin/categories.php?action=add"
                   class="text-[11px] text-gold-700 hover:text-gold-500 font-semibold transition-colors">
                    + Ajouter →
                </a>
            </div>

            <!-- Header -->
            <div class="grid grid-cols-12 gap-3 px-6 py-2.5 bg-white/[.02] border-b border-gold-900/10">
                <div class="col-span-1 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600 text-center">Ordre</div>
                <div class="col-span-1 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600 text-center">Icône</div>
                <div class="col-span-3 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600">Titre</div>
                <div class="col-span-3 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600">Description</div>
                <div class="col-span-1 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600 text-center">Cours</div>
                <div class="col-span-1 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600 text-center">Statut</div>
                <div class="col-span-2 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600 text-right">Actions</div>
            </div>

            <div class="divide-y divide-gold-900/10">
                <?php foreach ($categories as $i => $cat): ?>
                <div class="grid grid-cols-12 gap-3 px-6 py-4 hover:bg-white/[.025] transition-colors items-center"
                     style="animation: fadeUp .3s ease <?= $i * .04 ?>s both">

                    <!-- Ordre + flèches -->
                    <div class="col-span-1 flex flex-col items-center gap-0.5">
                        <form method="POST">
                            <input type="hidden" name="action" value="reorder">
                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                            <input type="hidden" name="direction" value="up">
                            <button type="submit" class="text-slate-600 hover:text-gold-500 transition-colors text-xs">▲</button>
                        </form>
                        <span class="font-bold text-gold-700 text-sm"><?= $cat['ordre'] ?></span>
                        <form method="POST">
                            <input type="hidden" name="action" value="reorder">
                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                            <input type="hidden" name="direction" value="down">
                            <button type="submit" class="text-slate-600 hover:text-gold-500 transition-colors text-xs">▼</button>
                        </form>
                    </div>

                    <!-- Icône + couleur -->
                    <div class="col-span-1 flex justify-center">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-xl"
                             style="background: color-mix(in srgb, <?= $cat['couleur'] ?> 15%, transparent); border: 1px solid color-mix(in srgb, <?= $cat['couleur'] ?> 30%, transparent)">
                            <?= $cat['icone'] ?>
                        </div>
                    </div>

                    <!-- Titre -->
                    <div class="col-span-3 min-w-0">
                        <p class="font-semibold text-slate-100 text-[13px] truncate">
                            <?= htmlspecialchars($cat['titre']) ?>
                        </p>
                        <div class="flex items-center gap-1.5 mt-1">
                            <div class="w-2.5 h-2.5 rounded-full shrink-0" style="background:<?= $cat['couleur'] ?>"></div>
                            <span class="text-[10px] text-slate-600"><?= $cat['couleur'] ?></span>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="col-span-3 min-w-0">
                        <p class="text-[11px] text-slate-500 line-clamp-2">
                            <?= htmlspecialchars($cat['description'] ?? '—') ?>
                        </p>
                    </div>

                    <!-- Nb cours -->
                    <div class="col-span-1 text-center">
                        <a href="/academy-admin/courses.php?category_id=<?= $cat['id'] ?>"
                           class="font-bold text-gold-500 text-sm hover:text-gold-400 transition-colors">
                            <?= $cat['nb_cours'] ?>
                        </a>
                    </div>

                    <!-- Statut toggle -->
                    <div class="col-span-1 text-center">
                        <form method="POST">
                            <input type="hidden" name="action" value="toggle_actif">
                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                            <input type="hidden" name="est_actif" value="<?= $cat['est_actif'] ? 0 : 1 ?>">
                            <button type="submit"
                                    class="text-[10px] px-2 py-0.5 rounded-full font-semibold transition-all
                                    <?= $cat['est_actif']
                                        ? 'bg-emerald-950/50 text-emerald-500 border border-emerald-800/40 hover:bg-red-950/40 hover:text-red-400 hover:border-red-800/40'
                                        : 'bg-slate-800/50 text-slate-500 border border-slate-700/40 hover:bg-emerald-950/40 hover:text-emerald-400' ?>">
                                <?= $cat['est_actif'] ? '✓ Active' : '✗ Inactive' ?>
                            </button>
                        </form>
                    </div>

                    <!-- Actions -->
                    <div class="col-span-2 flex items-center justify-end gap-1.5">
                        <!-- Voir cours -->
                        <a href="/academy-admin/courses.php?category_id=<?= $cat['id'] ?>"
                           title="Voir les cours"
                           class="w-7 h-7 rounded-lg bg-white/5 hover:bg-gold-900/30 flex items-center justify-center text-slate-500 hover:text-gold-500 transition-all text-sm">
                            📚
                        </a>
                        <!-- Modifier -->
                        <a href="/academy-admin/categories.php?action=edit&id=<?= $cat['id'] ?>"
                           title="Modifier"
                           class="w-7 h-7 rounded-lg bg-white/5 hover:bg-gold-900/30 flex items-center justify-center text-slate-500 hover:text-gold-400 transition-all text-sm">
                            ✏️
                        </a>
                        <!-- Supprimer -->
                        <form method="POST" onsubmit="return confirm('Supprimer cette catégorie ?')">
                            <input type="hidden" name="action" value="delete_category">
                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                            <button type="submit" title="Supprimer"
                                    class="w-7 h-7 rounded-lg bg-white/5 hover:bg-red-950/40 flex items-center justify-center text-slate-600 hover:text-red-400 transition-all text-sm">
                                🗑️
                            </button>
                        </form>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>

        </div>
        <?php endif; ?>

    </div>
</div>

<script>
    // Sélection emoji
    function selectEmoji(emoji) {
        document.getElementById('icone-hidden').value   = emoji;
        document.getElementById('emoji-preview').textContent = emoji;
        document.getElementById('preview-icon').textContent  = emoji;
        document.querySelectorAll('.emoji-btn').forEach(b => b.classList.remove('selected'));
        event.target.classList.add('selected');
    }

    // Mise à jour couleur
    function updateColorPreview(val) {
        document.querySelector('input[name="couleur"]').value = val;
        document.getElementById('color-val').textContent     = val;
        document.getElementById('card-top-bar').style.background = val;
        document.getElementById('color-preview-box').style.background = val;
    }

    // Aperçu titre en live
    document.querySelector('input[name="titre"]')?.addEventListener('input', function() {
        const el = document.getElementById('preview-titre');
        if (el) el.textContent = this.value || 'Titre catégorie';
    });
</script>

</body>
</html>
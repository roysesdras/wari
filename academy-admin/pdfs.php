<?php
// /var/www/html/academy-admin/pdfs.php

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

// Liste de tous les cours pour le sélecteur
$allCourses = $pdo->query("
    SELECT co.id, co.titre, c.icone as cat_icone, c.titre as cat_titre
    FROM academy_courses co
    JOIN academy_categories c ON c.id = co.category_id
    WHERE co.est_actif = 1
    ORDER BY c.ordre ASC, co.ordre ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Liste de toutes les leçons pour le sélecteur optionnel
$allLessons = $pdo->query("
    SELECT l.id, l.titre, l.course_id, co.titre as course_titre
    FROM academy_lessons l
    JOIN academy_courses co ON co.id = l.course_id
    WHERE l.est_actif = 1
    ORDER BY co.id ASC, l.ordre ASC
")->fetchAll(PDO::FETCH_ASSOC);

// ════════════════════════════════════════════════════════
// TRAITEMENT POST
// ════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    // ── Ajouter un PDF
    if ($postAction === 'add_pdf') {
        $course_id  = (int)($_POST['course_id'] ?? 0);
        $lesson_id  = (int)($_POST['lesson_id'] ?? 0) ?: null;
        $titre      = trim($_POST['titre'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $prix       = (float)($_POST['prix'] ?? 0);
        $est_gratuit = isset($_POST['est_gratuit']) ? 1 : 0;
        $auteur     = trim($_POST['auteur'] ?? 'Wari Finance');

        // Upload du fichier PDF
        $fichier_path = '';
        if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '/var/www/html/uploads/academy/pdfs/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $ext      = strtolower(pathinfo($_FILES['fichier']['name'], PATHINFO_EXTENSION));
            $filename = uniqid('pdf_') . '_' . time() . '.' . $ext;
            $destPath = $uploadDir . $filename;

            $allowedExt = ['pdf', 'xlsx', 'xls', 'zip'];
            if (in_array($ext, $allowedExt) && move_uploaded_file($_FILES['fichier']['tmp_name'], $destPath)) {
                $fichier_path = '/uploads/academy/pdfs/' . $filename;
            } else {
                $error  = "Format non accepté. Fichiers autorisés : PDF, Excel (.xlsx/.xls), ZIP.";
                $action = 'add';
            }
        } elseif ($postAction === 'add_pdf') {
            $error  = "Veuillez sélectionner un fichier (PDF, Excel ou ZIP).";
            $action = 'add';
        }

        if (!$error && $course_id && $titre && $fichier_path) {
            $pdo->prepare("
                INSERT INTO academy_pdfs
                    (course_id, lesson_id, titre, description, fichier_path, prix, est_gratuit, auteur)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ")->execute([$course_id, $lesson_id, $titre, $description, $fichier_path, $prix, $est_gratuit, $auteur]);
            $msg    = "✅ PDF <strong>" . htmlspecialchars($titre) . "</strong> ajouté avec succès.";
            $action = 'list';
        }
    }

    // ── Modifier un PDF
    if ($postAction === 'edit_pdf') {
        $id          = (int)($_POST['id'] ?? 0);
        $titre       = trim($_POST['titre'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $prix        = (float)($_POST['prix'] ?? 0);
        $est_gratuit = isset($_POST['est_gratuit']) ? 1 : 0;
        $est_actif   = isset($_POST['est_actif']) ? 1 : 0;
        $auteur      = trim($_POST['auteur'] ?? '');
        $lesson_id   = (int)($_POST['lesson_id'] ?? 0) ?: null;

        if ($id && $titre) {
            // Nouveau fichier uploadé ?
            $updateFile = '';
            if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '/var/www/html/uploads/academy/pdfs/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $ext      = strtolower(pathinfo($_FILES['fichier']['name'], PATHINFO_EXTENSION));
                $filename = uniqid('pdf_') . '_' . time() . '.' . $ext;
                $allowedExt = ['pdf', 'xlsx', 'xls', 'zip'];
                if (in_array($ext, $allowedExt) && move_uploaded_file($_FILES['fichier']['tmp_name'], $uploadDir . $filename)) {
                    $updateFile = '/uploads/academy/pdfs/' . $filename;
                }
            }

            if ($updateFile) {
                $pdo->prepare("
                    UPDATE academy_pdfs SET
                        titre = ?, description = ?, prix = ?, est_gratuit = ?,
                        est_actif = ?, auteur = ?, lesson_id = ?, fichier_path = ?
                    WHERE id = ?
                ")->execute([$titre, $description, $prix, $est_gratuit, $est_actif, $auteur, $lesson_id, $updateFile, $id]);
            } else {
                $pdo->prepare("
                    UPDATE academy_pdfs SET
                        titre = ?, description = ?, prix = ?, est_gratuit = ?,
                        est_actif = ?, auteur = ?, lesson_id = ?
                    WHERE id = ?
                ")->execute([$titre, $description, $prix, $est_gratuit, $est_actif, $auteur, $lesson_id, $id]);
            }
            $msg    = "✅ PDF mis à jour avec succès.";
            $action = 'list';
        } else {
            $error = "Données invalides.";
        }
    }

    // ── Supprimer un PDF
    if ($postAction === 'delete_pdf') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            // Récupérer le chemin pour supprimer le fichier
            $stmt = $pdo->prepare("SELECT fichier_path FROM academy_pdfs WHERE id = ?");
            $stmt->execute([$id]);
            $pdf = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($pdf && $pdf['fichier_path']) {
                $fullPath = '/var/www/html' . $pdf['fichier_path'];
                if (file_exists($fullPath)) unlink($fullPath);
            }
            $pdo->prepare("DELETE FROM academy_pdfs WHERE id = ?")->execute([$id]);
            $msg    = "🗑️ PDF supprimé.";
            $action = 'list';
        }
    }
}

// ── PDF à éditer
$pdfEdit = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM academy_pdfs WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $pdfEdit = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$pdfEdit) $action = 'list';
}

// ── Liste des PDF avec stats
$pdfs = $pdo->query("
    SELECT p.*,
        co.titre  AS course_titre,
        co.slug   AS course_slug,
        c.icone   AS cat_icone,
        l.titre   AS lesson_titre,
        COUNT(DISTINCT a.id)      AS nb_achats,
        COALESCE(SUM(a.montant), 0) AS total_revenus
    FROM academy_pdfs p
    JOIN academy_courses co ON co.id = p.course_id
    JOIN academy_categories c ON c.id = co.category_id
    LEFT JOIN academy_lessons l ON l.id = p.lesson_id
    LEFT JOIN academy_pdf_achats a ON a.pdf_id = p.id AND a.statut = 'paye'
    GROUP BY p.id
    ORDER BY p.cree_le DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Stats globales
$totalRevenus = array_sum(array_column($pdfs, 'total_revenus'));
$totalAchats  = array_sum(array_column($pdfs, 'nb_achats'));
$totalPdfs    = count($pdfs);
$totalGratuits = count(array_filter($pdfs, fn($p) => $p['est_gratuit']));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Payants — Wari Academy Admin</title>
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
        select.field-input option { background: #100A01; color: #e2e8f0; }
        textarea.field-input { resize: vertical; min-height: 80px; }
        .field-label {
            display: block; font-size: 10px; font-weight: 700;
            letter-spacing: .12em; text-transform: uppercase;
            color: rgba(148,163,184,.6); margin-bottom: 6px;
        }
        /* Upload zone */
        .upload-zone {
            border: 2px dashed rgba(201,168,76,.2);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            cursor: pointer;
            transition: all .2s;
            background: rgba(255,255,255,.02);
        }
        .upload-zone:hover {
            border-color: rgba(201,168,76,.4);
            background: rgba(201,168,76,.04);
        }
        .upload-zone input[type="file"] { display: none; }
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
        <a href="/academy-admin/courses.php" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-white/5 text-[13px] transition-all"><span>📚</span> Cours</a>
        <a href="/academy-admin/lessons.php" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-white/5 text-[13px] transition-all"><span>📖</span> Leçons</a>
        <a href="/academy-admin/pdfs.php" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-gold-500 bg-gold-900/20 font-semibold text-[13px]"><span>📄</span> PDF Payants</a>
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
            <span class="font-bold text-slate-100 text-sm">PDF Payants</span>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-[11px] text-slate-500"><?= $totalPdfs ?> PDF au total</span>
            <a href="/academy-admin/pdfs.php?action=add"
               class="bg-gold-500 hover:bg-gold-400 text-ink-900 font-bold text-[12px] px-4 py-1.5 rounded-full transition-all">
                + Nouveau PDF
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
                <?= $action === 'edit' ? '✏️ Modifier le PDF' : '➕ Ajouter un PDF payant' ?>
            </h2>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?= $action === 'edit' ? 'edit_pdf' : 'add_pdf' ?>">
                <?php if ($action === 'edit'): ?>
                <input type="hidden" name="id" value="<?= $pdfEdit['id'] ?>">
                <?php endif; ?>

                <div class="grid grid-cols-2 gap-5 mb-5">

                    <!-- Cours parent -->
                    <div>
                        <label class="field-label">Cours associé *</label>
                        <select name="course_id" class="field-input" required id="course-select"
                                onchange="filterLessons(this.value)">
                            <option value="">-- Choisir un cours --</option>
                            <?php foreach ($allCourses as $c): ?>
                            <option value="<?= $c['id'] ?>"
                                <?= ($pdfEdit['course_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                <?= $c['cat_icone'] ?> <?= htmlspecialchars($c['titre']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Leçon liée (optionnel) -->
                    <div>
                        <label class="field-label">
                            Leçon liée
                            <span class="text-gold-900 normal-case tracking-normal font-normal ml-1">— optionnel</span>
                        </label>
                        <select name="lesson_id" class="field-input" id="lesson-select">
                            <option value="">— Lié au cours entier —</option>
                            <?php foreach ($allLessons as $l): ?>
                            <option value="<?= $l['id'] ?>"
                                    data-course="<?= $l['course_id'] ?>"
                                <?= ($pdfEdit['lesson_id'] ?? '') == $l['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($l['course_titre']) ?> → <?= htmlspecialchars($l['titre']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Titre -->
                    <div class="col-span-2">
                        <label class="field-label">Titre du PDF *</label>
                        <input type="text" name="titre" class="field-input"
                           placeholder="ex: Guide complet — Créer ton budget en 7 jours"
                               value="<?= htmlspecialchars($pdfEdit['titre'] ?? '') ?>"
                               required>
                    </div>

                    <!-- Description -->
                    <div class="col-span-2">
                        <label class="field-label">Description</label>
                        <textarea name="description" class="field-input"
                                  placeholder="Décris ce que contient ce guide, à qui il est destiné..."><?= htmlspecialchars($pdfEdit['description'] ?? '') ?></textarea>
                    </div>

                    <!-- Prix -->
                    <div>
                        <label class="field-label">Prix (FCFA)</label>
                        <input type="number" name="prix" class="field-input"
                               min="0" step="100"
                               placeholder="ex: 2500"
                               value="<?= $pdfEdit['prix'] ?? 0 ?>">
                    </div>

                    <!-- Auteur -->
                    <div>
                        <label class="field-label">Auteur</label>
                        <input type="text" name="auteur" class="field-input"
                               placeholder="Wari Finance"
                               value="<?= htmlspecialchars($pdfEdit['auteur'] ?? 'Wari Finance') ?>">
                    </div>

                    <!-- Upload fichier -->
                    <div class="col-span-2">
                        <label class="field-label">
                            Fichier PDF *
                            <?php if ($action === 'edit' && $pdfEdit['fichier_path']): ?>
                            <span class="text-emerald-600 normal-case tracking-normal font-normal ml-2">
                                ✓ Fichier actuel : <?= basename($pdfEdit['fichier_path']) ?>
                                — Laisser vide pour conserver
                            </span>
                            <?php endif; ?>
                        </label>

                        <div class="upload-zone" onclick="document.getElementById('file-input').click()">
                            <input type="file" id="file-input" name="fichier" accept=".pdf,.xlsx,.xls,.zip"
                                   onchange="showFileName(this)">
                            <div id="upload-placeholder">
                                <p class="text-3xl mb-2">📁</p>
                                <p class="text-sm font-semibold text-slate-400">
                                    Clique pour sélectionner un fichier
                                </p>
                                <p class="text-[11px] text-slate-600 mt-1">
                                    Formats acceptés : <span class="text-gold-800">PDF · Excel (.xlsx/.xls) · ZIP</span> — Max recommandé : 20 Mo
                                </p>
                            </div>
                            <div id="upload-selected" class="hidden">
                                <p class="text-2xl mb-1">✅</p>
                                <p class="text-sm font-semibold text-gold-500" id="file-name"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Checkboxes -->
                    <div class="col-span-2 flex gap-8">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="est_gratuit" value="1"
                                   class="accent-gold-500 w-4 h-4"
                                   id="gratuit-cb"
                                   onchange="togglePrix(this.checked)"
                                   <?= ($pdfEdit['est_gratuit'] ?? 0) ? 'checked' : '' ?>>
                            <span class="text-[13px] text-slate-300">PDF gratuit</span>
                        </label>
                        <?php if ($action === 'edit'): ?>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="est_actif" value="1"
                                   class="accent-gold-500 w-4 h-4"
                                   <?= ($pdfEdit['est_actif'] ?? 1) ? 'checked' : '' ?>>
                            <span class="text-[13px] text-slate-300">PDF actif (visible)</span>
                        </label>
                        <?php endif; ?>
                    </div>

                </div>

                <!-- Boutons -->
                <div class="flex items-center gap-3 pt-4 border-t border-gold-900/20">
                    <button type="submit"
                            class="bg-gold-500 hover:bg-gold-400 text-ink-900 font-bold text-[13px] px-6 py-2.5 rounded-full transition-all">
                        <?= $action === 'edit' ? '💾 Enregistrer' : '✅ Ajouter le PDF' ?>
                    </button>
                    <a href="/academy-admin/pdfs.php"
                       class="text-slate-500 hover:text-slate-300 text-[13px] transition-colors px-4">
                        Annuler
                    </a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- ════════════════════════════════════════════════
             LISTE DES PDF — BENTO
        ════════════════════════════════════════════════ -->
        <?php if ($action === 'list'): ?>

        <!-- Stats bento -->
        <div class="grid grid-cols-4 gap-4 mb-6">
            <?php foreach ([
                ['label' => 'Total PDF',    'val' => $totalPdfs,                                           'icon' => '📄', 'sub' => 'guides disponibles'],
                ['label' => 'Payants',       'val' => $totalPdfs - $totalGratuits,                          'icon' => '💰', 'sub' => 'guides premium'],
                ['label' => 'Achats',        'val' => number_format($totalAchats),                          'icon' => '🛒', 'sub' => 'achats effectués'],
                ['label' => 'Revenus FCFA',  'val' => number_format($totalRevenus, 0, ',', ' ') . ' F',    'icon' => '🏆', 'sub' => 'générés au total'],
            ] as $i => $s): ?>
            <div class="card-gold-top bg-ink-900 border border-gold-900/25 rounded-2xl p-5 anim"
                 style="animation-delay:<?= $i * .05 ?>s">
                <div class="text-2xl opacity-70 mb-2"><?= $s['icon'] ?></div>
                <p class="font-black text-gold-500 text-3xl leading-none"><?= $s['val'] ?></p>
                <p class="text-slate-600 text-[11px] mt-1"><?= $s['label'] ?></p>
                <p class="text-slate-700 text-[10px]"><?= $s['sub'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Tableau des PDF -->
        <div class="card-gold-top bg-ink-900 border border-gold-900/25 rounded-2xl overflow-hidden anim">

            <div class="px-6 py-4 border-b border-gold-900/20 flex items-center justify-between">
                <p class="font-bold text-slate-100 text-sm">📄 Tous les PDF</p>
                <a href="/academy-admin/pdfs.php?action=add"
                   class="text-[11px] text-gold-700 hover:text-gold-500 font-semibold transition-colors">
                    + Ajouter →
                </a>
            </div>

            <?php if (!empty($pdfs)): ?>

            <!-- Header -->
            <div class="grid grid-cols-12 gap-3 px-6 py-2.5 bg-white/[.02] border-b border-gold-900/10">
                <div class="col-span-4 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600">PDF / Guide</div>
                <div class="col-span-2 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600">Cours</div>
                <div class="col-span-1 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600 text-center">Prix</div>
                <div class="col-span-1 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600 text-center">Achats</div>
                <div class="col-span-2 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600 text-center">Revenus</div>
                <div class="col-span-1 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600 text-center">Statut</div>
                <div class="col-span-1 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600 text-right">Actions</div>
            </div>

            <div class="divide-y divide-gold-900/10">
                <?php foreach ($pdfs as $i => $pdf): ?>
                <div class="grid grid-cols-12 gap-3 px-6 py-4 hover:bg-white/[.025] transition-colors items-center"
                     style="animation: fadeUp .3s ease <?= $i * .04 ?>s both">

                    <!-- Titre + auteur -->
                    <div class="col-span-4 min-w-0">
                        <div class="flex items-start gap-2">
                            <span class="text-xl mt-0.5 shrink-0">📘</span>
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-100 text-[13px] truncate">
                                    <?= htmlspecialchars($pdf['titre']) ?>
                                </p>
                                <p class="text-[11px] text-slate-600 mt-0.5">
                                    ✍️ <?= htmlspecialchars($pdf['auteur']) ?>
                                    <?php if ($pdf['lesson_titre']): ?>
                                    · <span class="text-gold-900">📖 <?= htmlspecialchars($pdf['lesson_titre']) ?></span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Cours -->
                    <div class="col-span-2">
                        <p class="text-[11px] text-slate-500 truncate">
                            <?= $pdf['cat_icone'] ?> <?= htmlspecialchars($pdf['course_titre']) ?>
                        </p>
                    </div>

                    <!-- Prix -->
                    <div class="col-span-1 text-center">
                        <?php if ($pdf['est_gratuit']): ?>
                        <span class="text-[10px] bg-emerald-950/50 text-emerald-500 border border-emerald-800/30 px-2 py-0.5 rounded-full font-semibold">
                            Gratuit
                        </span>
                        <?php else: ?>
                        <span class="font-bold text-gold-500 text-sm">
                            <?= number_format($pdf['prix'], 0, ',', ' ') ?>
                        </span>
                        <span class="text-[9px] text-gold-800 block">FCFA</span>
                        <?php endif; ?>
                    </div>

                    <!-- Nb achats -->
                    <div class="col-span-1 text-center">
                        <span class="font-bold text-slate-300 text-sm"><?= $pdf['nb_achats'] ?></span>
                    </div>

                    <!-- Revenus -->
                    <div class="col-span-2 text-center">
                        <span class="font-bold text-gold-500 text-sm">
                            <?= number_format($pdf['total_revenus'], 0, ',', ' ') ?>
                        </span>
                        <span class="text-[10px] text-gold-800"> FCFA</span>
                    </div>

                    <!-- Statut -->
                    <div class="col-span-1 text-center">
                        <span class="text-[10px] px-2 py-0.5 rounded-full font-semibold
                            <?= $pdf['est_actif']
                                ? 'bg-emerald-950/50 text-emerald-500 border border-emerald-800/40'
                                : 'bg-slate-800/50 text-slate-500 border border-slate-700/40' ?>">
                            <?= $pdf['est_actif'] ? '✓ Actif' : '✗ Inactif' ?>
                        </span>
                    </div>

                    <!-- Actions -->
                    <div class="col-span-1 flex items-center justify-end gap-1.5">
                        <!-- Télécharger -->
                        <a href="<?= htmlspecialchars($pdf['fichier_path']) ?>"
                           target="_blank"
                           title="Télécharger"
                           class="w-7 h-7 rounded-lg bg-white/5 hover:bg-blue-900/30 flex items-center justify-center text-slate-500 hover:text-blue-400 transition-all text-sm">
                            ⬇️
                        </a>
                        <!-- Éditer -->
                        <a href="/academy-admin/pdfs.php?action=edit&id=<?= $pdf['id'] ?>"
                           title="Modifier"
                           class="w-7 h-7 rounded-lg bg-white/5 hover:bg-gold-900/30 flex items-center justify-center text-slate-500 hover:text-gold-400 transition-all text-sm">
                            ✏️
                        </a>
                        <!-- Supprimer -->
                        <form method="POST" onsubmit="return confirm('Supprimer ce PDF et son fichier ?')">
                            <input type="hidden" name="action" value="delete_pdf">
                            <input type="hidden" name="id" value="<?= $pdf['id'] ?>">
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
                <p class="text-4xl mb-4">📄</p>
                <p class="text-sm">Aucun PDF pour le moment.</p>
                <a href="/academy-admin/pdfs.php?action=add"
                   class="inline-block mt-4 bg-gold-500 text-ink-900 font-bold text-[12px] px-5 py-2 rounded-full hover:bg-gold-400 transition-all">
                    Ajouter le premier guide →
                </a>
            </div>
            <?php endif; ?>

        </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Afficher le nom du fichier sélectionné
    function showFileName(input) {
        const placeholder = document.getElementById('upload-placeholder');
        const selected    = document.getElementById('upload-selected');
        const nameEl      = document.getElementById('file-name');
        if (input.files.length > 0) {
            const name = input.files[0].name;
            const size = (input.files[0].size / 1024 / 1024).toFixed(2);
            nameEl.textContent = name + ' (' + size + ' Mo)';
            placeholder.classList.add('hidden');
            selected.classList.remove('hidden');
        }
    }

    // Griser le champ prix si gratuit coché
    function togglePrix(isGratuit) {
        const prixInput = document.querySelector('input[name="prix"]');
        if (prixInput) {
            prixInput.disabled = isGratuit;
            prixInput.style.opacity = isGratuit ? '.3' : '1';
            if (isGratuit) prixInput.value = 0;
        }
    }

    // Filtrer les leçons selon le cours sélectionné
    function filterLessons(courseId) {
        const select  = document.getElementById('lesson-select');
        const options = select.querySelectorAll('option[data-course]');
        options.forEach(opt => {
            opt.style.display = (!courseId || opt.dataset.course == courseId) ? '' : 'none';
        });
        select.value = '';
    }

    // Init
    document.addEventListener('DOMContentLoaded', () => {
        const gratuitCb = document.getElementById('gratuit-cb');
        if (gratuitCb) togglePrix(gratuitCb.checked);
        const courseSelect = document.getElementById('course-select');
        if (courseSelect) filterLessons(courseSelect.value);
    });
</script>

</body>
</html>
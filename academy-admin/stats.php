<?php
// /var/www/html/academy-admin/stats.php

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['academy_user'])) {
    header('Location: /academy-admin/login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Academy.php';

$academy = new Academy($pdo);
$user    = $_SESSION['academy_user'];

// ════════════════════════════════════════════════════════
// DONNÉES STATISTIQUES
// ════════════════════════════════════════════════════════

// ── Stats globales
$stats = $academy->getAdminStats();

// ── Apprenants par semaine (8 dernières semaines)
$apprenantsSemaine = $pdo->query("
    SELECT
        DATE_FORMAT(MIN(commence_le), '%d/%m') AS semaine,
        YEARWEEK(commence_le, 1) AS yw,
        COUNT(DISTINCT user_id) AS total
    FROM academy_progress
    WHERE commence_le >= DATE_SUB(NOW(), INTERVAL 8 WEEK)
    GROUP BY yw
    ORDER BY yw ASC
")->fetchAll(PDO::FETCH_ASSOC);

// ── Completions par semaine (8 dernières semaines)
$completionsSemaine = $pdo->query("
    SELECT
        DATE_FORMAT(MIN(complete_le), '%d/%m') AS semaine,
        YEARWEEK(complete_le, 1) AS yw,
        COUNT(*) AS total
    FROM academy_progress
    WHERE est_complete = 1
    AND complete_le >= DATE_SUB(NOW(), INTERVAL 8 WEEK)
    GROUP BY yw
    ORDER BY yw ASC
")->fetchAll(PDO::FETCH_ASSOC);

// ── Top cours par apprenants
$topCours = $pdo->query("
    SELECT co.titre, co.slug,
        c.icone AS cat_icone, c.couleur AS cat_couleur,
        COUNT(DISTINCT p.user_id) AS nb_apprenants,
        COUNT(DISTINCT CASE WHEN p.est_complete = 1 THEN p.id END) AS nb_completions,
        COUNT(DISTINCT l.id) AS nb_lecons,
        ROUND(
            COUNT(DISTINCT CASE WHEN p.est_complete = 1 THEN p.id END) * 100.0 /
            NULLIF(COUNT(DISTINCT p.id), 0)
        ) AS taux_completion
    FROM academy_courses co
    JOIN academy_categories c ON c.id = co.category_id
    LEFT JOIN academy_lessons l ON l.course_id = co.id
    LEFT JOIN academy_progress p ON p.course_id = co.id
    WHERE co.est_actif = 1
    GROUP BY co.id
    ORDER BY nb_apprenants DESC
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

// ── Stats par catégorie
$statsCat = $pdo->query("
    SELECT c.titre, c.icone, c.couleur,
        COUNT(DISTINCT co.id) AS nb_cours,
        COUNT(DISTINCT p.user_id) AS nb_apprenants,
        COUNT(DISTINCT CASE WHEN p.est_complete = 1 THEN p.id END) AS nb_completions
    FROM academy_categories c
    LEFT JOIN academy_courses co ON co.category_id = c.id AND co.est_actif = 1
    LEFT JOIN academy_progress p ON p.course_id = co.id
    WHERE c.est_actif = 1
    GROUP BY c.id
    ORDER BY nb_apprenants DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ── Emails Academy (7 dernières campagnes)
$emailCampagnes = $pdo->query("
    SELECT co.titre AS course_titre,
        COUNT(DISTINCT el.user_id) AS nb_envoyes,
        COUNT(DISTINCT CASE WHEN el.statut = 'echec' THEN el.user_id END) AS nb_echecs,
        DATE_FORMAT(MAX(el.envoye_le), '%d/%m/%Y') AS date_envoi
    FROM academy_email_log el
    JOIN academy_courses co ON co.id = el.course_id
    GROUP BY el.course_id
    ORDER BY MAX(el.envoye_le) DESC
    LIMIT 7
")->fetchAll(PDO::FETCH_ASSOC);

// ── Revenus PDF par mois (6 derniers mois)
$revenusParMois = $pdo->query("
    SELECT
        DATE_FORMAT(achete_le, '%m/%Y') AS mois,
        DATE_FORMAT(achete_le, '%Y%m') AS mois_sort,
        COUNT(*) AS nb_achats,
        SUM(montant) AS total
    FROM academy_pdf_achats
    WHERE statut = 'paye'
    AND achete_le >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY mois_sort, mois
    ORDER BY mois_sort ASC
")->fetchAll(PDO::FETCH_ASSOC);

// ── Leçons les plus complétées
$topLecons = $pdo->query("
    SELECT l.titre, co.titre AS course_titre,
        COUNT(DISTINCT p.user_id) AS nb_completes
    FROM academy_lessons l
    JOIN academy_courses co ON co.id = l.course_id
    JOIN academy_progress p ON p.lesson_id = l.id AND p.est_complete = 1
    GROUP BY l.id
    ORDER BY nb_completes DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// ── Utilisateurs les plus actifs
$topUsers = $pdo->query("
    SELECT u.email,
        COUNT(DISTINCT p.course_id) AS nb_cours,
        COUNT(DISTINCT CASE WHEN p.est_complete = 1 THEN p.lesson_id END) AS nb_lecons_completes
    FROM academy_progress p
    JOIN wari_users u ON u.id = p.user_id
    GROUP BY p.user_id
    ORDER BY nb_lecons_completes DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Valeurs max pour les barres
$maxApprenants = max(array_column($topCours, 'nb_apprenants') ?: [1]);
$maxCatApp     = max(array_column($statsCat,  'nb_apprenants') ?: [1]);
$maxRevenusMois = max(array_column($revenusParMois, 'total') ?: [1]);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques — Wari Academy Admin</title>
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

        /* Barres horizontales custom */
        .hbar {
            height: 6px;
            background: rgba(201,168,76,.1);
            border-radius: 999px;
            overflow: hidden;
            margin-top: 6px;
        }
        .hbar-fill {
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, #8B6914, #C9A84C);
            transition: width 1s ease;
        }

        /* Histogramme vertical */
        .chart-bar {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }
        .chart-bar-fill {
            width: 100%;
            border-radius: 4px 4px 0 0;
            background: linear-gradient(180deg, #C9A84C, #8B6914);
            transition: height 1s ease;
            min-height: 4px;
        }
        .chart-bar-label {
            font-size: 9px;
            color: #6B6050;
            text-align: center;
            white-space: nowrap;
        }
        .chart-bar-val {
            font-size: 10px;
            font-weight: 700;
            color: #C9A84C;
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
        <a href="/academy-admin/courses.php" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-white/5 text-[13px] transition-all">Cours</a>
        <a href="/academy-admin/lessons.php" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-white/5 text-[13px] transition-all">Leçons</a>
        <a href="/academy-admin/pdfs.php" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-white/5 text-[13px] transition-all">PDF Payants</a>
        <p class="text-[9px] font-bold tracking-[.15em] uppercase text-slate-700 px-2 pt-4 pb-1">Données</p>
        <a href="/academy-admin/stats.php" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-gold-500 bg-gold-900/20 font-semibold text-[13px]">Statistiques</a>
        <a href="/academy-admin/emails.php" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-white/5 text-[13px] transition-all">Emails</a>
        <p class="text-[9px] font-bold tracking-[.15em] uppercase text-slate-700 px-2 pt-4 pb-1">App</p>
        <a href="/academy/" target="_blank" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-white/5 text-[13px] transition-all">Voir Academy</a>
        <a href="https://wari.digiroys.com/accueil/" target="_blank" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-white/5 text-[13px] transition-all">Retour Wari</a>
    </nav>
    <div class="px-3 py-4 border-t border-gold-900/20">
        <div class="flex items-center gap-3 px-2 py-2 mb-1">
            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-gold-700 to-gold-500 flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
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
            <span class="font-bold text-slate-100 text-sm">Statistiques</span>
        </div>
        <span class="text-[11px] text-slate-500">Données en temps réel</span>
    </div>

    <div class="p-8 flex-1">

        <!-- ════ LIGNE 1 — KPIs GLOBAUX ══════════════════ -->
        <div class="grid grid-cols-12 gap-4 mb-4">

            <div class="col-span-3 card-gold-top bg-ink-900 border border-gold-900/25 rounded-2xl p-5 anim overflow-hidden">
                <div class="absolute top-4 right-4 text-gold-900/50">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <p class="text-[10px] font-bold tracking-[.12em] uppercase text-slate-600 mb-2">Apprenants</p>
                <p class="font-black text-gold-500 text-5xl leading-none"><?= number_format($stats['total_apprenants']) ?></p>
                <p class="text-slate-600 text-[11px] mt-2">utilisateurs actifs sur Academy</p>
            </div>

            <div class="col-span-3 card-gold-top bg-ink-900 border border-gold-900/25 rounded-2xl p-5 anim overflow-hidden" style="animation-delay:.06s">
                <div class="absolute top-4 right-4 text-gold-900/50">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                </div>
                <p class="text-[10px] font-bold tracking-[.12em] uppercase text-slate-600 mb-2">Completions</p>
                <p class="font-black text-gold-500 text-5xl leading-none"><?= number_format($stats['total_completions']) ?></p>
                <p class="text-slate-600 text-[11px] mt-2">leçons complétées au total</p>
            </div>

            <div class="col-span-3 card-gold-top bg-ink-900 border border-gold-900/25 rounded-2xl p-5 anim overflow-hidden" style="animation-delay:.12s">
                <div class="absolute top-4 right-4 text-gold-900/50">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>
                </div>
                <p class="text-[10px] font-bold tracking-[.12em] uppercase text-slate-600 mb-2">Cours actifs</p>
                <p class="font-black text-gold-500 text-5xl leading-none"><?= $stats['total_cours'] ?></p>
                <p class="text-slate-600 text-[11px] mt-2">cours publiés</p>
            </div>

            <div class="col-span-3 card-gold-top bg-gradient-to-br from-gold-900/30 to-ink-900 border border-gold-700/30 rounded-2xl p-5 anim overflow-hidden" style="animation-delay:.18s">
                <div class="absolute top-4 right-4 text-gold-800/50">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 18V6"/></svg>
                </div>
                <p class="text-[10px] font-bold tracking-[.12em] uppercase text-gold-800 mb-2">Revenus PDF</p>
                <p class="font-black text-gold-400 leading-none" style="font-size:2.4rem">
                    <?= number_format($stats['total_revenus'], 0, ',', ' ') ?>
                </p>
                <p class="text-gold-900 text-[11px] mt-2 font-semibold">FCFA générés</p>
            </div>

        </div>

        <!-- ════ LIGNE 2 — COURS + CATÉGORIES ════════════ -->
        <div class="grid grid-cols-12 gap-4 mb-4">

            <!-- Top cours -->
            <div class="col-span-7 card-gold-top bg-ink-900 border border-gold-900/25 rounded-2xl overflow-hidden anim">
                <div class="px-6 py-4 border-b border-gold-900/20 flex items-center justify-between">
                    <p class="font-bold text-slate-100 text-sm flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gold-700"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2z"/></svg>
                        Cours les plus suivis
                    </p>
                    <span class="text-[10px] text-slate-600">par nombre d'apprenants</span>
                </div>
                <div class="p-6 space-y-4">
                    <?php foreach ($topCours as $i => $cours): ?>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-5 h-5 rounded-md flex items-center justify-center text-[10px] font-black shrink-0
                                    <?= $i === 0 ? 'bg-gold-900/50 text-gold-500' : 'bg-white/5 text-slate-600' ?>">
                                    <?= $i + 1 ?>
                                </span>
                                <span class="text-[12px] font-semibold text-slate-200 truncate">
                                    <?= $cours['cat_icone'] ?> <?= htmlspecialchars($cours['titre']) ?>
                                </span>
                            </div>
                            <div class="flex items-center gap-4 shrink-0 ml-3">
                                <span class="text-[11px] text-slate-500"><?= $cours['taux_completion'] ?? 0 ?>%</span>
                                <span class="font-bold text-gold-500 text-sm"><?= number_format($cours['nb_apprenants']) ?></span>
                            </div>
                        </div>
                        <div class="hbar">
                            <div class="hbar-fill" style="width:<?= $maxApprenants > 0 ? round($cours['nb_apprenants'] / $maxApprenants * 100) : 0 ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($topCours)): ?>
                    <p class="text-slate-600 text-sm text-center py-8">Aucune donnée disponible.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats par catégorie -->
            <div class="col-span-5 card-gold-top bg-ink-900 border border-gold-900/25 rounded-2xl overflow-hidden anim" style="animation-delay:.08s">
                <div class="px-6 py-4 border-b border-gold-900/70">
                    <p class="font-bold text-slate-100 text-sm flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gold-700"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
                        Par thématique
                    </p>
                </div>
                <div class="divide-y divide-gold-900/10">
                    <?php foreach ($statsCat as $cat): ?>
                    <div class="px-6 py-3.5">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-[12px] font-semibold text-slate-300">
                                <?= $cat['icone'] ?> <?= htmlspecialchars($cat['titre']) ?>
                            </span>
                            <span class="font-bold text-gold-500 text-sm"><?= number_format($cat['nb_apprenants']) ?></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="hbar flex-1">
                                <div class="hbar-fill" style="width:<?= $maxCatApp > 0 ? round($cat['nb_apprenants'] / $maxCatApp * 100) : 0 ?>%; background: <?= $cat['couleur'] ?>"></div>
                            </div>
                            <span class="text-[10px] text-slate-600 shrink-0"><?= $cat['nb_cours'] ?> cours</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

        <!-- ════ LIGNE 3 — REVENUS MOIS + EMAILS ════════ -->
        <div class="grid grid-cols-12 gap-4 mb-4">

            <!-- Histogramme revenus par mois -->
            <div class="col-span-6 card-gold-top bg-ink-900 border border-gold-900/25 rounded-2xl overflow-hidden anim">
                <div class="px-6 py-4 border-b border-gold-900/70 flex items-center justify-between">
                    <p class="font-bold text-slate-100 text-sm flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gold-700"><circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 18V6"/></svg>
                        Revenus PDF — 6 derniers mois
                    </p>
                </div>
                <div class="p-6">
                    <?php if (!empty($revenusParMois)): ?>
                    <div class="flex items-end gap-2" style="height: 140px;">
                        <?php foreach ($revenusParMois as $mois): ?>
                        <div class="chart-bar">
                            <span class="chart-bar-val"><?= number_format($mois['total'], 0) ?></span>
                            <div class="chart-bar-fill w-full"
                                 style="height: <?= $maxRevenusMois > 0 ? max(4, round($mois['total'] / $maxRevenusMois * 100)) : 4 ?>px">
                            </div>
                            <span class="chart-bar-label"><?= $mois['mois'] ?></span>
                            <span class="chart-bar-label text-slate-700"><?= $mois['nb_achats'] ?> vte<?= $mois['nb_achats'] > 1 ? 's' : '' ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="text-[10px] text-slate-700 mt-3 text-center">Montants en FCFA</p>
                    <?php else: ?>
                    <div class="flex items-center justify-center h-32 text-slate-600 text-sm">
                        Aucune vente enregistrée pour le moment.
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Campagnes email -->
            <div class="col-span-6 card-gold-top bg-ink-900 border border-gold-900/25 rounded-2xl overflow-hidden anim" style="animation-delay:.08s">
                <div class="px-6 py-4 border-b border-gold-900/20 flex items-center justify-between">
                    <p class="font-bold text-slate-100 text-sm flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gold-700"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        Campagnes emails récentes
                    </p>
                    <a href="/academy-admin/emails.php" class="text-[11px] text-gold-700 hover:text-gold-500 font-semibold transition-colors">
                        Tout voir →
                    </a>
                </div>
                <div class="divide-y divide-gold-900/10">
                    <?php if (!empty($emailCampagnes)): ?>
                        <?php foreach ($emailCampagnes as $camp): ?>
                        <div class="flex items-center gap-3 px-6 py-3 hover:bg-white/[.02] transition-colors">
                            <div class="w-7 h-7 rounded-lg bg-gold-900/20 flex items-center justify-center shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gold-700"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[12px] font-semibold text-slate-200 truncate">
                                    <?= htmlspecialchars($camp['course_titre']) ?>
                                </p>
                                <p class="text-[10px] text-slate-600"><?= $camp['date_envoi'] ?></p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="font-bold text-gold-500 text-sm"><?= number_format($camp['nb_envoyes']) ?></p>
                                <?php if ($camp['nb_echecs'] > 0): ?>
                                <p class="text-[9px] text-red-500"><?= $camp['nb_echecs'] ?> échec<?= $camp['nb_echecs'] > 1 ? 's' : '' ?></p>
                                <?php else: ?>
                                <p class="text-[9px] text-emerald-600">OK</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div class="px-6 py-10 text-center text-slate-600 text-sm">
                        Aucune campagne envoyée.
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- ════ LIGNE 4 — TOP LEÇONS + TOP USERS ═══════ -->
        <div class="grid grid-cols-12 gap-4">

            <!-- Leçons les plus complétées -->
            <div class="col-span-6 card-gold-top bg-ink-900 border border-gold-900/25 rounded-2xl overflow-hidden anim">
                <div class="px-6 py-4 border-b border-gold-900/20">
                    <p class="font-bold text-slate-100 text-sm flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gold-700"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                        Leçons les plus complétées
                    </p>
                </div>
                <div class="divide-y divide-gold-900/10">
                    <?php if (!empty($topLecons)): ?>
                        <?php foreach ($topLecons as $i => $lecon): ?>
                        <div class="flex items-center gap-3 px-6 py-3.5 hover:bg-white/[.02] transition-colors">
                            <div class="w-6 h-6 rounded-md flex items-center justify-center text-[10px] font-black shrink-0
                                <?= $i === 0 ? 'bg-gold-900/50 text-gold-500' : 'bg-white/5 text-slate-600' ?>">
                                <?= $i + 1 ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[12px] font-semibold text-slate-200 truncate">
                                    <?= htmlspecialchars($lecon['titre']) ?>
                                </p>
                                <p class="text-[10px] text-slate-600 truncate">
                                    <?= htmlspecialchars($lecon['course_titre']) ?>
                                </p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="font-bold text-gold-500 text-sm"><?= number_format($lecon['nb_completes']) ?></p>
                                <p class="text-[9px] text-slate-600">complétions</p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div class="px-6 py-10 text-center text-slate-600 text-sm">Aucune donnée.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Utilisateurs les plus actifs -->
            <div class="col-span-6 card-gold-top bg-ink-900 border border-gold-900/25 rounded-2xl overflow-hidden anim" style="animation-delay:.08s">
                <div class="px-6 py-4 border-b border-gold-900/20">
                    <p class="font-bold text-slate-100 text-sm flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gold-700"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        Apprenants les plus actifs
                    </p>
                </div>
                <div class="divide-y divide-gold-900/10">
                    <?php if (!empty($topUsers)): ?>
                        <?php foreach ($topUsers as $i => $u): ?>
                        <div class="flex items-center gap-3 px-6 py-3.5 hover:bg-white/[.02] transition-colors">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-gold-800 to-gold-600 flex items-center justify-center text-[11px] font-bold shrink-0 text-ink-900">
                                <?= $i + 1 ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[12px] font-semibold text-slate-200 truncate">
                                    <?= htmlspecialchars($u['email']) ?>
                                </p>
                                <p class="text-[10px] text-slate-600">
                                    <?= $u['nb_cours'] ?> cours · <?= $u['nb_lecons_completes'] ?> leçons complétées
                                </p>
                            </div>
                            <div class="shrink-0">
                                <span class="text-[10px] bg-gold-900/20 border border-gold-900/30 text-gold-700 px-2.5 py-1 rounded-full font-semibold">
                                    <?= $u['nb_lecons_completes'] ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <div class="px-6 py-10 text-center text-slate-600 text-sm">Aucune donnée.</div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>
</div>

</body>
</html>
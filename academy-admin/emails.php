<?php
// /var/www/html/academy-admin/emails.php

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['academy_user'])) {
    header('Location: /academy-admin/login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$user = $_SESSION['academy_user'];

// ════════════════════════════════════════════════════════
// FILTRES
// ════════════════════════════════════════════════════════
$filterCourse = (int)($_GET['course_id'] ?? 0);
$filterStatut = $_GET['statut'] ?? '';
$filterPage   = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 50;
$offset       = ($filterPage - 1) * $perPage;

// ── Liste des cours pour le filtre
$allCourses = $pdo->query("
    SELECT id, titre FROM academy_courses WHERE est_actif = 1 ORDER BY ordre ASC
")->fetchAll(PDO::FETCH_ASSOC);

// ════════════════════════════════════════════════════════
// STATS GLOBALES
// ════════════════════════════════════════════════════════
$statsEmail = $pdo->query("
    SELECT
        COUNT(*) AS total,
        COUNT(CASE WHEN statut = 'envoye' THEN 1 END) AS total_envoyes,
        COUNT(CASE WHEN statut = 'echec'  THEN 1 END) AS total_echecs,
        COUNT(DISTINCT user_id)   AS total_users,
        COUNT(DISTINCT course_id) AS total_cours
    FROM academy_email_log
")->fetch(PDO::FETCH_ASSOC);

$emailsSemaine = $pdo->query("
    SELECT COUNT(*) as total FROM academy_email_log
    WHERE statut = 'envoye'
    AND envoye_le >= DATE_SUB(NOW(), INTERVAL 7 DAY)
")->fetch(PDO::FETCH_ASSOC)['total'];

// ── Taux de succès
$tauxSucces = $statsEmail['total'] > 0
    ? round($statsEmail['total_envoyes'] / $statsEmail['total'] * 100)
    : 0;

// ════════════════════════════════════════════════════════
// CAMPAGNES GROUPÉES (par cours)
// ════════════════════════════════════════════════════════
$campagnes = $pdo->query("
    SELECT
        co.id AS course_id,
        co.titre AS course_titre,
        co.slug  AS course_slug,
        c.icone  AS cat_icone,
        COUNT(DISTINCT el.user_id) AS nb_envoyes,
        COUNT(CASE WHEN el.statut = 'echec' THEN 1 END) AS nb_echecs,
        COUNT(DISTINCT CASE WHEN el.statut = 'envoye' THEN el.user_id END) AS nb_ok,
        MAX(el.envoye_le) AS dernier_envoi,
        MIN(el.envoye_le) AS premier_envoi
    FROM academy_email_log el
    JOIN academy_courses co ON co.id = el.course_id
    JOIN academy_categories c ON c.id = co.category_id
    GROUP BY el.course_id
    ORDER BY dernier_envoi DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ════════════════════════════════════════════════════════
// LOGS DÉTAILLÉS (avec filtres + pagination)
// ════════════════════════════════════════════════════════
$where  = ['1=1'];
$params = [];

if ($filterCourse) {
    $where[]  = 'el.course_id = ?';
    $params[] = $filterCourse;
}
if ($filterStatut) {
    $where[]  = 'el.statut = ?';
    $params[] = $filterStatut;
}

$whereStr = implode(' AND ', $where);

// Total pour pagination
$countStmt = $pdo->prepare("
    SELECT COUNT(*) FROM academy_email_log el WHERE {$whereStr}
");
$countStmt->execute($params);
$totalLogs = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalLogs / $perPage);

// Logs paginés
$logsStmt = $pdo->prepare("
    SELECT el.*,
        u.email AS user_email,
        co.titre AS course_titre,
        c.icone  AS cat_icone
    FROM academy_email_log el
    JOIN wari_users u ON u.id = el.user_id
    LEFT JOIN academy_courses co ON co.id = el.course_id
    LEFT JOIN academy_categories c ON c.id = co.category_id
    WHERE {$whereStr}
    ORDER BY el.envoye_le DESC
    LIMIT {$perPage} OFFSET {$offset}
");
$logsStmt->execute($params);
$logs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emails : Wari Academy Admin</title>
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
        /* Barre taux succès */
        .success-bar {
            height: 6px; background: rgba(255,255,255,.06);
            border-radius: 999px; overflow: hidden;
        }
        .success-fill {
            height: 100%; border-radius: 999px;
            background: linear-gradient(90deg, #16a34a, #4ade80);
            transition: width 1s ease;
        }
        /* Filtre select */
        .filter-select {
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(201,168,76,.15);
            border-radius: 10px;
            padding: 7px 12px;
            font-family: 'Poppins', sans-serif;
            font-size: 12px;
            color: #cbd5e1;
            outline: none;
            transition: border-color .2s;
        }
        .filter-select:focus { border-color: rgba(201,168,76,.4); }
        .filter-select option { background: #100A01; }

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
        <a href="/academy-admin/stats.php" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-white/5 text-[13px] transition-all">Statistiques</a>
        <a href="/academy-admin/emails.php" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-gold-500 bg-gold-900/20 font-semibold text-[13px]">Emails</a>
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
            <span class="font-bold text-slate-100 text-sm">Emails envoyés</span>
        </div>
        <span class="text-[11px] text-slate-500">
            <?= number_format($statsEmail['total']) ?> emails au total
        </span>
    </div>

    <div class="p-8 flex-1">

        <!-- ════ KPIs GLOBAUX ═══════════════════════════ -->
        <div class="grid grid-cols-12 gap-4 mb-6">

            <!-- Total envoyés -->
            <div class="col-span-3 card-gold-top bg-ink-900 border border-gold-900/25 rounded-2xl p-5 anim">
                <div class="text-gold-700 mb-2"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg></div>
                <p class="font-black text-gold-500 text-4xl leading-none"><?= number_format($statsEmail['total_envoyes']) ?></p>
                <p class="text-slate-600 text-[11px] mt-1">emails envoyés</p>
            </div>

            <!-- Échecs -->
            <div class="col-span-2 card-gold-top bg-ink-900 border border-gold-900/25 rounded-2xl p-5 anim" style="animation-delay:.05s">
                <div class="text-gold-700 mb-2"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m4.9 4.9 14.2 14.2"/></svg></div>
                <p class="font-black text-red-500 text-4xl leading-none"><?= number_format($statsEmail['total_echecs']) ?></p>
                <p class="text-slate-600 text-[11px] mt-1">échecs</p>
            </div>

            <!-- Taux succès -->
            <div class="col-span-3 card-gold-top bg-ink-900 border border-gold-900/25 rounded-2xl p-5 anim" style="animation-delay:.10s">
                <div class="flex items-baseline justify-between mb-2">
                    <p class="text-[10px] font-bold tracking-[.1em] uppercase text-slate-600">Taux de succès</p>
                    <p class="font-black text-emerald-500 text-xl"><?= $tauxSucces ?>%</p>
                </div>
                <div class="success-bar">
                    <div class="success-fill" style="width:<?= $tauxSucces ?>%"></div>
                </div>
                <p class="text-slate-600 text-[10px] mt-2">
                    <?= number_format($statsEmail['total_users']) ?> destinataires uniques
                </p>
            </div>

            <!-- Cette semaine -->
            <div class="col-span-2 card-gold-top bg-ink-900 border border-gold-900/25 rounded-2xl p-5 anim" style="animation-delay:.15s">
                <div class="text-gold-700 mb-2"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg></div>
                <p class="font-black text-gold-500 text-4xl leading-none"><?= number_format($emailsSemaine) ?></p>
                <p class="text-slate-600 text-[11px] mt-1">cette semaine</p>
            </div>

            <!-- Cours couverts -->
            <div class="col-span-2 card-gold-top bg-ink-900 border border-gold-900/25 rounded-2xl p-5 anim" style="animation-delay:.20s">
                <div class="text-gold-700 mb-2"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg></div>
                <p class="font-black text-gold-500 text-4xl leading-none"><?= $statsEmail['total_cours'] ?></p>
                <p class="text-slate-600 text-[11px] mt-1">cours envoyés</p>
            </div>

        </div>

        <!-- ════ CAMPAGNES PAR COURS ══════════════════════ -->
        <div class="card-gold-top bg-ink-900 border border-gold-900/25 rounded-2xl overflow-hidden mb-6 anim">

            <div class="px-6 py-4 border-b border-gold-900/20">
                <p class="font-bold text-slate-100 text-sm flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gold-700"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                    Campagnes par cours
                </p>
                <p class="text-[11px] text-slate-600 mt-0.5">Vue agrégée par cours envoyé</p>
            </div>

            <?php if (!empty($campagnes)): ?>

            <!-- Header -->
            <div class="grid grid-cols-12 gap-3 px-6 py-2.5 bg-white/[.02] border-b border-gold-900/10">
                <div class="col-span-4 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600">Cours</div>
                <div class="col-span-2 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600 text-center">Envoyés</div>
                <div class="col-span-2 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600 text-center">Échecs</div>
                <div class="col-span-2 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600 text-center">Taux OK</div>
                <div class="col-span-2 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600 text-right">Dernier envoi</div>
            </div>

            <div class="divide-y divide-gold-900/10">
                <?php foreach ($campagnes as $i => $camp): ?>
                <?php
                    $tauxOk = $camp['nb_envoyes'] > 0
                        ? round($camp['nb_ok'] / $camp['nb_envoyes'] * 100) : 0;
                ?>
                <div class="grid grid-cols-12 gap-3 px-6 py-4 items-center hover:bg-white/[.025] transition-colors"
                     style="animation: fadeUp .3s ease <?= $i * .04 ?>s both">

                    <div class="col-span-4 flex items-center gap-2 min-w-0">
                        <a href="/academy-admin/emails.php?course_id=<?= $camp['course_id'] ?>"
                           class="font-semibold text-slate-200 text-[13px] truncate hover:text-gold-400 transition-colors">
                            <?= $camp['cat_icone'] ?> <?= htmlspecialchars($camp['course_titre']) ?>
                        </a>
                    </div>

                    <div class="col-span-2 text-center">
                        <span class="font-bold text-gold-500 text-sm"><?= number_format($camp['nb_envoyes']) ?></span>
                    </div>

                    <div class="col-span-2 text-center">
                        <?php if ($camp['nb_echecs'] > 0): ?>
                        <span class="font-bold text-red-500 text-sm"><?= $camp['nb_echecs'] ?></span>
                        <?php else: ?>
                        <span class="text-emerald-600 text-sm font-bold">0</span>
                        <?php endif; ?>
                    </div>

                    <div class="col-span-2 text-center">
                        <span class="text-[11px] px-2.5 py-0.5 rounded-full font-semibold
                            <?= $tauxOk >= 90
                                ? 'bg-emerald-950/50 text-emerald-500 border border-emerald-800/30'
                                : ($tauxOk >= 70
                                    ? 'bg-amber-950/50 text-amber-500 border border-amber-800/30'
                                    : 'bg-red-950/50 text-red-500 border border-red-800/30') ?>">
                            <?= $tauxOk ?>%
                        </span>
                    </div>

                    <div class="col-span-2 text-right">
                        <p class="text-[12px] text-slate-400">
                            <?= date('d/m/Y', strtotime($camp['dernier_envoi'])) ?>
                        </p>
                        <p class="text-[10px] text-slate-600">
                            <?= date('H:i', strtotime($camp['dernier_envoi'])) ?>
                        </p>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>

            <?php else: ?>
            <div class="px-6 py-12 text-center text-slate-600">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-3 opacity-20"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                <p class="text-sm">Aucun email envoyé pour le moment.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- ════ LOGS DÉTAILLÉS ════════════════════════════ -->
        <div class="card-gold-top bg-ink-900 border border-gold-900/25 rounded-2xl overflow-hidden anim">

            <div class="px-6 py-4 border-b border-gold-900/20 flex items-center justify-between flex-wrap gap-3">
                <div>
                    <p class="font-bold text-slate-100 text-sm flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-gold-700"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
                        Logs détaillés
                    </p>
                    <p class="text-[11px] text-slate-600 mt-0.5">
                        <?= number_format($totalLogs) ?> entrées
                        <?= $filterCourse ? '— filtrées' : '' ?>
                    </p>
                </div>

                <!-- Filtres -->
                <form method="GET" class="flex items-center gap-2 flex-wrap">
                    <select name="course_id" class="filter-select" onchange="this.form.submit()">
                        <option value="">— Tous les cours —</option>
                        <?php foreach ($allCourses as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $filterCourse == $c['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['titre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="statut" class="filter-select" onchange="this.form.submit()">
                        <option value="">— Tous les statuts —</option>
                        <option value="envoye" <?= $filterStatut === 'envoye' ? 'selected' : '' ?>>Envoyés</option>
                        <option value="echec"  <?= $filterStatut === 'echec'  ? 'selected' : '' ?>>Erreurs</option>
                    </select>
                    <?php if ($filterCourse || $filterStatut): ?>
                    <a href="/academy-admin/emails.php"
                       class="text-[11px] text-slate-500 hover:text-slate-300 transition-colors px-2">
                        Réinitialiser
                    </a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (!empty($logs)): ?>

            <!-- Header -->
            <div class="grid grid-cols-12 gap-3 px-6 py-2.5 bg-white/[.02] border-b border-gold-900/10">
                <div class="col-span-4 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600">Destinataire</div>
                <div class="col-span-4 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600">Cours</div>
                <div class="col-span-2 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600 text-center">Statut</div>
                <div class="col-span-2 text-[10px] font-bold uppercase tracking-[.1em] text-slate-600 text-right">Date</div>
            </div>

            <div class="divide-y divide-gold-900/10">
                <?php foreach ($logs as $i => $log): ?>
                <div class="grid grid-cols-12 gap-3 px-6 py-3 items-center hover:bg-white/[.02] transition-colors"
                     style="animation: fadeUp .25s ease <?= min($i * .02, .3) ?>s both">

                    <!-- Email -->
                    <div class="col-span-4 min-w-0">
                        <p class="text-[12px] text-slate-300 truncate font-medium">
                            <?= htmlspecialchars($log['user_email']) ?>
                        </p>
                    </div>

                    <!-- Cours -->
                    <div class="col-span-4 min-w-0">
                        <p class="text-[12px] text-slate-500 truncate">
                            <?= $log['cat_icone'] ?> <?= htmlspecialchars($log['course_titre'] ?? '—') ?>
                        </p>
                    </div>

                    <!-- Statut -->
                    <div class="col-span-2 text-center">
                        <span class="text-[10px] px-2 py-0.5 rounded-full font-semibold
                            <?= $log['statut'] === 'envoye'
                                ? 'bg-emerald-950/50 text-emerald-500 border border-emerald-800/30'
                                : 'bg-red-950/50 text-red-400 border border-red-800/30' ?>">
                            <?= $log['statut'] === 'envoye' ? 'Envoyé' : 'Échec' ?>
                        </span>
                    </div>

                    <!-- Date -->
                    <div class="col-span-2 text-right">
                        <p class="text-[11px] text-slate-500">
                            <?= date('d/m/Y', strtotime($log['envoye_le'])) ?>
                        </p>
                        <p class="text-[10px] text-slate-700">
                            <?= date('H:i', strtotime($log['envoye_le'])) ?>
                        </p>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="px-6 py-4 border-t border-gold-900/20 flex items-center justify-between">
                <p class="text-[11px] text-slate-600">
                    Page <?= $filterPage ?> / <?= $totalPages ?>
                    · <?= number_format($totalLogs) ?> entrées
                </p>
                <div class="flex items-center gap-2">
                    <?php if ($filterPage > 1): ?>
                    <a href="?page=<?= $filterPage - 1 ?><?= $filterCourse ? '&course_id='.$filterCourse : '' ?><?= $filterStatut ? '&statut='.$filterStatut : '' ?>"
                       class="text-[11px] bg-white/5 hover:bg-gold-900/20 border border-white/5 hover:border-gold-900/30 text-slate-400 hover:text-gold-500 px-3 py-1.5 rounded-lg transition-all">
                        ← Précédente
                    </a>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $filterPage - 2);
                    $end   = min($totalPages, $filterPage + 2);
                    for ($p = $start; $p <= $end; $p++):
                    ?>
                    <a href="?page=<?= $p ?><?= $filterCourse ? '&course_id='.$filterCourse : '' ?><?= $filterStatut ? '&statut='.$filterStatut : '' ?>"
                       class="text-[11px] px-3 py-1.5 rounded-lg transition-all
                           <?= $p === $filterPage
                               ? 'bg-gold-500 text-ink-900 font-bold'
                               : 'bg-white/5 hover:bg-gold-900/20 border border-white/5 text-slate-400 hover:text-gold-500' ?>">
                        <?= $p ?>
                    </a>
                    <?php endfor; ?>

                    <?php if ($filterPage < $totalPages): ?>
                    <a href="?page=<?= $filterPage + 1 ?><?= $filterCourse ? '&course_id='.$filterCourse : '' ?><?= $filterStatut ? '&statut='.$filterStatut : '' ?>"
                       class="text-[11px] bg-white/5 hover:bg-gold-900/20 border border-white/5 hover:border-gold-900/30 text-slate-400 hover:text-gold-500 px-3 py-1.5 rounded-lg transition-all">
                        Suivante →
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div class="px-6 py-12 text-center text-slate-600">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-3 opacity-20"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                <p class="text-sm">Aucun log pour ces filtres.</p>
            </div>
            <?php endif; ?>

        </div>

    </div>
</div>

</body>
</html>
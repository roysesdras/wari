<?php
// /var/www/html/academy-admin/index.php

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['academy_user'])) {
    header('Location: /academy-admin/login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Academy.php';

$academy  = new Academy($pdo);
$stats    = $academy->getAdminStats();
$topCours = $academy->getTopCourses(5);

$emailsSemaine = $pdo->query("
    SELECT COUNT(*) as total FROM academy_email_log
    WHERE statut = 'envoye'
    AND envoye_le >= DATE_SUB(NOW(), INTERVAL 7 DAY)
")->fetch(PDO::FETCH_ASSOC)['total'];

$dernierEmail = $pdo->query("
    SELECT co.titre, co.slug, MAX(el.envoye_le) as date_envoi,
           COUNT(DISTINCT el.user_id) as nb_recus
    FROM academy_email_log el
    JOIN academy_courses co ON co.id = el.course_id
    WHERE el.statut = 'envoye'
    GROUP BY el.course_id
    ORDER BY date_envoi DESC
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

$achatsRecents = $pdo->query("
    SELECT a.*, p.titre as pdf_titre, u.email as user_email
    FROM academy_pdf_achats a
    JOIN academy_pdfs p ON p.id = a.pdf_id
    JOIN wari_users u ON u.id = a.user_id
    WHERE a.statut = 'paye'
    ORDER BY a.achete_le DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$user = $_SESSION['academy_user'];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Wari Academy Admin</title>

    <link rel="icon" type="image/png" href="../assets/warifinance3d.png" />
    <link rel="apple-touch-icon" href="../assets/warifinance3d.png">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Poppins font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
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
                    },
                    animation: {
                        'fade-up': 'fadeUp .4s ease both',
                        'fade-up-d1': 'fadeUp .4s ease .07s both',
                        'fade-up-d2': 'fadeUp .4s ease .14s both',
                        'fade-up-d3': 'fadeUp .4s ease .21s both',
                    },
                    keyframes: {
                        fadeUp: {
                            '0%': {
                                opacity: '0',
                                transform: 'translateY(14px)'
                            },
                            '100%': {
                                opacity: '1',
                                transform: 'translateY(0)'
                            },
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

        /* Scrollbar custom */
        ::-webkit-scrollbar {
            width: 5px;
        }

        ::-webkit-scrollbar-track {
            background: #100A01;
        }

        ::-webkit-scrollbar-thumb {
            background: #000000;
            border-radius: 999px;
        }

        /* Motif de fond subtil */
        .bg-pattern {
            background-image:
                repeating-linear-gradient(45deg,
                    transparent, transparent 40px,
                    rgba(201, 168, 76, .015) 40px,
                    rgba(201, 168, 76, .015) 41px);
        }

        /* Barre or en haut des cartes */
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

        /* Glow doré */
        .gold-glow {
            box-shadow: 0 0 30px rgba(201, 168, 76, .06);
        }

        /* Progress bar */
        .prog-bar {
            height: 3px;
            background: rgba(201, 168, 76, .15);
            border-radius: 999px;
            overflow: hidden;
        }

        .prog-fill {
            height: 100%;
            background: linear-gradient(90deg, #8B6914, #C9A84C);
            border-radius: 999px;
        }
    </style>
</head>

<body class="bg-ink-800 bg-pattern text-slate-200 min-h-screen flex">

    <!-- ════════════════════════════════════════════════════════
     SIDEBAR
════════════════════════════════════════════════════════ -->
    <aside class="w-56 bg-ink-900 border-r border-gold-900/30 min-h-screen fixed left-0 top-0 bottom-0 flex flex-col z-50">

        <!-- Logo -->
        <div class="px-5 py-6 border-b border-gold-900/20">
            <span class="block font-black text-gold-500 text-lg tracking-wide leading-none">
                Wari Academy
            </span>
            <span class="block text-[10px] text-slate-600 tracking-[.15em] uppercase mt-1">
                Administration
            </span>
        </div>

        <!-- Nav -->
        <nav class="flex-1 px-3 py-4 space-y-0.5">

            <p class="text-[9px] font-bold tracking-[.15em] uppercase text-slate-700 px-2 pt-2 pb-1">Principal</p>
            <a href="/academy-admin/index.php"
                class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-gold-500 bg-gold-900/20 font-semibold text-[13px]">
                <span>📊</span> Dashboard
            </a>

            <p class="text-[9px] font-bold tracking-[.15em] uppercase text-slate-700 px-2 pt-4 pb-1">Contenu</p>
            <a href="/academy-admin/courses.php"
                class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-white/5 text-[13px] transition-all">
                <span>📚</span> Cours
            </a>
            <a href="/academy-admin/lessons.php"
                class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-white/5 text-[13px] transition-all">
                <span>📖</span> Leçons
            </a>
            <a href="/academy-admin/pdfs.php"
                class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-white/5 text-[13px] transition-all">
                <span>📄</span> PDF Payants
            </a>

            <p class="text-[9px] font-bold tracking-[.15em] uppercase text-slate-700 px-2 pt-4 pb-1">Données</p>
            <a href="/academy-admin/stats.php"
                class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-white/5 text-[13px] transition-all">
                <span>📈</span> Statistiques
            </a>
            <a href="/academy-admin/emails.php"
                class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-white/5 text-[13px] transition-all">
                <span>✉️</span> Emails
            </a>

            <p class="text-[9px] font-bold tracking-[.15em] uppercase text-slate-700 px-2 pt-4 pb-1">App</p>
            <a href="/academy/" target="_blank"
                class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-white/5 text-[13px] transition-all">
                <span>🌐</span> Voir Academy
            </a>
            <a href="https://wari.digiroys.com/accueil/" target="_blank"
                class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-slate-400 hover:text-slate-200 hover:bg-white/5 text-[13px] transition-all">
                <span>←</span> Retour Wari
            </a>
        </nav>

        <!-- User + logout -->
        <div class="px-3 py-4 border-t border-gold-900/20">
            <div class="flex items-center gap-3 px-2 py-2 mb-1">
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-gold-700 to-gold-500 flex items-center justify-center text-sm shrink-0">
                    👤
                </div>
                <div>
                    <p class="text-[13px] font-semibold text-gold-400 leading-none">
                        <?= htmlspecialchars($user) ?>
                    </p>
                    <p class="text-[10px] text-slate-600 mt-0.5">Admin Academy</p>
                </div>
            </div>
            <a href="/academy-admin/logout.php"
                class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-600 hover:text-red-400 hover:bg-red-950/30 text-[12px] transition-all">
                🚪 Se déconnecter
            </a>
        </div>
    </aside>

    <!-- ════════════════════════════════════════════════════════
     MAIN
════════════════════════════════════════════════════════ -->
    <div class="ml-56 flex-1 flex flex-col min-h-screen">

        <!-- Topbar -->
        <div class="bg-ink-900/80 backdrop-blur border-b border-gold-900/20 px-8 h-14 flex items-center justify-between sticky top-0 z-40">
            <div class="flex items-center gap-3">
                <span class="font-bold text-slate-100 text-base">Dashboard</span>
                <span class="text-slate-700 text-xs">·</span>
                <span class="text-slate-500 text-xs"><span class="text-wari-gold-light text-xs font-bold uppercase tracking-wider">
                        <?php
                        // On force l'heure du Bénin
                        date_default_timezone_set('Africa/Porto-Novo');

                        $jours = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
                        $mois = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

                        $date = getdate();
                        echo $jours[$date['wday']] . ' ' . $date['mday'] . ' ' . $mois[$date['mon']] . ' ' . $date['year'];
                        ?>
                    </span></span>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-[11px] text-gold-700 bg-gold-900/20 border border-gold-900/30 px-3 py-1 rounded-full font-medium">
                    ✉️ <?= $emailsSemaine ?> emails cette semaine
                </span>
                <a href="/academy-admin/courses.php?action=add"
                    class="bg-gold-500 hover:bg-gold-400 text-ink-900 font-bold text-[12px] px-4 py-1.5 rounded-full transition-all">
                    + Nouveau cours
                </a>
            </div>
        </div>

        <!-- Content -->
        <div class="p-8 flex-1">

            <!-- ════ BENTO GRID PRINCIPAL ════════════════════ -->
            <div class="grid grid-cols-12 gap-4 mb-4">

                <!-- ── Carte Apprenants (large) -->
                <div class="col-span-3 relative bg-ink-900 border border-gold-900/25 rounded-2xl p-6 gold-glow card-gold-top animate-fade-up overflow-hidden">
                    <div class="absolute top-4 right-5 text-3xl opacity-75">👥</div>
                    <p class="text-[10px] font-bold tracking-[.14em] uppercase text-slate-500 mb-3">
                        Apprenants
                    </p>
                    <p class="font-black text-gold-500 text-5xl leading-none mb-2">
                        <?= number_format($stats['total_apprenants']) ?>
                    </p>
                    <p class="text-slate-500 text-[12px]">utilisateurs actifs sur Academy</p>
                    <div class="mt-4 h-px bg-gradient-to-r from-gold-900/40 to-transparent"></div>
                    <p class="text-[11px] text-slate-600 mt-3">
                        📈 Nombre total d'apprenants inscrits
                    </p>
                </div>

                <!-- ── Carte Completions -->
                <div class="col-span-3 relative bg-ink-900 border border-gold-900/25 rounded-2xl p-6 card-gold-top animate-fade-up-d1 overflow-hidden">
                    <div class="absolute top-4 right-5 text-3xl opacity-75">✅</div>
                    <p class="text-[10px] font-bold tracking-[.14em] uppercase text-slate-500 mb-3">
                        Completions
                    </p>
                    <p class="font-black text-gold-500 text-5xl leading-none mb-2">
                        <?= number_format($stats['total_completions']) ?>
                    </p>
                    <p class="text-slate-500 text-[12px]">leçons complétées au total</p>
                    <div class="mt-4 h-px bg-gradient-to-r from-gold-900/40 to-transparent"></div>
                    <p class="text-[11px] text-slate-600 mt-3">
                        🎯 Engagement des apprenants
                    </p>
                </div>

                <!-- ── Carte Cours actifs -->
                <div class="col-span-3 relative bg-ink-900 border border-gold-900/25 rounded-2xl p-6 card-gold-top animate-fade-up-d2 overflow-hidden">
                    <div class="absolute top-4 right-5 text-3xl opacity-75">📚</div>
                    <p class="text-[10px] font-bold tracking-[.14em] uppercase text-slate-500 mb-3">
                        Cours actifs
                    </p>
                    <p class="font-black text-gold-500 text-5xl leading-none mb-2">
                        <?= $stats['total_cours'] ?>
                    </p>
                    <p class="text-slate-500 text-[12px]">cours publiés sur Academy</p>
                    <div class="mt-4 h-px bg-gradient-to-r from-gold-900/40 to-transparent"></div>
                    <p class="text-[11px] text-slate-600 mt-3">
                        📖 Contenu disponible
                    </p>
                </div>

                <!-- ── Carte Revenus PDF -->
                <div class="col-span-3 relative bg-gradient-to-br from-gold-900/30 to-ink-900 border border-gold-700/30 rounded-2xl p-6 card-gold-top animate-fade-up-d3 overflow-hidden">
                    <div class="absolute top-4 right-5 text-3xl opacity-75  ">💰</div>
                    <p class="text-[10px] font-bold tracking-[.14em] uppercase text-gold-800 mb-3">
                        Revenus PDF
                    </p>
                    <p class="font-black text-gold-400 leading-none mb-2" style="font-size:2.4rem">
                        <?= number_format($stats['total_revenus'], 0, ',', ' ') ?>
                    </p>
                    <p class="text-gold-800 text-[12px] font-semibold">FCFA générés</p>
                    <div class="mt-4 h-px bg-gradient-to-r from-gold-700/30 to-transparent"></div>
                    <p class="text-[11px] text-gold-900 mt-3">
                        🏆 Via les guides PDF payants
                    </p>
                </div>

            </div>

            <!-- ════ BENTO 2e LIGNE ═══════════════════════════ -->
            <div class="grid grid-cols-12 gap-4 mb-4">

                <!-- ── Dernier email (large) -->
                <div class="col-span-8 relative bg-ink-900 border border-gold-900/25 rounded-2xl p-6 overflow-hidden animate-fade-up">
                    <div class="absolute inset-0 bg-gradient-to-br from-gold-900/10 to-transparent pointer-events-none"></div>

                    <p class="text-[10px] font-bold tracking-[.14em] uppercase text-slate-500 mb-4">
                        Dernier email Academy envoyé
                    </p>

                    <?php if ($dernierEmail): ?>
                        <div class="flex items-center gap-6">
                            <div class="w-14 h-14 rounded-xl bg-gold-900/30 border border-gold-900/40 flex items-center justify-center text-2xl shrink-0">
                                📧
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-slate-100 text-base leading-tight truncate">
                                    <?= htmlspecialchars($dernierEmail['titre']) ?>
                                </p>
                                <p class="text-slate-500 text-[12px] mt-1">
                                    Envoyé le <?= date('d/m/Y à H:i', strtotime($dernierEmail['date_envoi'])) ?>
                                </p>
                                <div class="flex items-center gap-4 mt-3">
                                    <span class="text-[11px] bg-gold-900/20 border border-gold-900/30 text-gold-600 px-3 py-1 rounded-full font-medium">
                                        <?= number_format($dernierEmail['nb_recus']) ?> destinataires
                                    </span>
                                    <span class="text-[11px] text-slate-500">
                                        ✉️ <?= $emailsSemaine ?> emails envoyés cette semaine
                                    </span>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="font-black text-gold-500 text-4xl leading-none">
                                    <?= number_format($dernierEmail['nb_recus']) ?>
                                </p>
                                <p class="text-slate-600 text-[11px] mt-1">reçus</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="flex items-center gap-4 text-slate-600">
                            <span class="text-2xl">📭</span>
                            <p class="text-[13px]">Aucun email Academy envoyé pour le moment.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- ── Actions rapides (col 4) -->
                <div class="col-span-4 bg-ink-900 border border-gold-900/25 rounded-2xl p-5 animate-fade-up-d1">
                    <p class="text-[10px] font-bold tracking-[.14em] uppercase text-slate-500 mb-4">
                        Actions rapides
                    </p>
                    <div class="grid grid-cols-2 gap-2">
                        <a href="/academy-admin/courses.php?action=add"
                            class="flex flex-col items-center gap-2 bg-white/5 hover:bg-gold-900/20 border border-white/5 hover:border-gold-900/30 rounded-xl p-3 text-center transition-all group">
                            <span class="text-xl group-hover:scale-110 transition-transform">➕</span>
                            <span class="text-[11px] font-semibold text-slate-400 group-hover:text-gold-500 leading-tight">Ajouter cours</span>
                        </a>
                        <a href="/academy-admin/lessons.php?action=add"
                            class="flex flex-col items-center gap-2 bg-white/5 hover:bg-gold-900/20 border border-white/5 hover:border-gold-900/30 rounded-xl p-3 text-center transition-all group">
                            <span class="text-xl group-hover:scale-110 transition-transform">📝</span>
                            <span class="text-[11px] font-semibold text-slate-400 group-hover:text-gold-500 leading-tight">Ajouter leçon</span>
                        </a>
                        <a href="/academy-admin/pdfs.php?action=add"
                            class="flex flex-col items-center gap-2 bg-white/5 hover:bg-gold-900/20 border border-white/5 hover:border-gold-900/30 rounded-xl p-3 text-center transition-all group">
                            <span class="text-xl group-hover:scale-110 transition-transform">📄</span>
                            <span class="text-[11px] font-semibold text-slate-400 group-hover:text-gold-500 leading-tight">Ajouter PDF</span>
                        </a>
                        <a href="/academy-admin/stats.php"
                            class="flex flex-col items-center gap-2 bg-white/5 hover:bg-gold-900/20 border border-white/5 hover:border-gold-900/30 rounded-xl p-3 text-center transition-all group">
                            <span class="text-xl group-hover:scale-110 transition-transform">📈</span>
                            <span class="text-[11px] font-semibold text-slate-400 group-hover:text-gold-500 leading-tight">Statistiques</span>
                        </a>
                    </div>
                </div>

            </div>

            <!-- ════ BENTO 3e LIGNE ═══════════════════════════ -->
            <div class="grid grid-cols-12 gap-4">

                <!-- ── Top cours -->
                <div class="col-span-7 bg-ink-900 border border-gold-900/25 rounded-2xl overflow-hidden animate-fade-up">
                    <div class="px-6 py-4 border-b border-gold-900/20 flex items-center justify-between">
                        <p class="font-bold text-slate-100 text-sm">🏆 Cours les plus suivis</p>
                        <a href="/academy-admin/stats.php"
                            class="text-[11px] text-gold-700 hover:text-gold-500 font-semibold transition-colors">
                            Tout voir →
                        </a>
                    </div>

                    <div class="divide-y divide-gold-900/10">
                        <?php if (!empty($topCours)): ?>
                            <?php foreach ($topCours as $i => $cours): ?>
                                <div class="flex items-center gap-4 px-6 py-3.5 hover:bg-white/[.03] transition-colors">
                                    <div class="w-7 h-7 rounded-lg flex items-center justify-center text-[11px] font-black shrink-0
                                <?= $i === 0 ? 'bg-gold-900/40 text-gold-500' : 'bg-white/5 text-slate-500' ?>">
                                        <?= $i + 1 ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[13px] font-semibold text-slate-200 truncate">
                                            <?= htmlspecialchars($cours['titre']) ?>
                                        </p>
                                        <div class="prog-bar mt-1.5">
                                            <div class="prog-fill" style="width:<?= $cours['taux_completion'] ?? 0 ?>%"></div>
                                        </div>
                                        <p class="text-[10px] text-slate-600 mt-1">
                                            <?= $cours['taux_completion'] ?? 0 ?>% de complétion
                                        </p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <p class="font-bold text-gold-500 text-base leading-none">
                                            <?= number_format($cours['nb_apprenants']) ?>
                                        </p>
                                        <p class="text-[10px] text-slate-600 mt-0.5">apprenants</p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="px-6 py-10 text-center text-slate-600 text-sm">
                                Aucune donnée pour le moment.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ── Achats PDF récents -->
                <div class="col-span-5 bg-ink-900 border border-gold-900/25 rounded-2xl overflow-hidden animate-fade-up-d1">
                    <div class="px-6 py-4 border-b border-gold-900/20 flex items-center justify-between">
                        <p class="font-bold text-slate-100 text-sm">💰 Achats PDF récents</p>
                        <a href="/academy-admin/pdfs.php"
                            class="text-[11px] text-gold-700 hover:text-gold-500 font-semibold transition-colors">
                            Gérer →
                        </a>
                    </div>

                    <div class="divide-y divide-gold-900/10">
                        <?php if (!empty($achatsRecents)): ?>
                            <?php foreach ($achatsRecents as $achat): ?>
                                <div class="flex items-center gap-3 px-6 py-3.5 hover:bg-white/[.03] transition-colors">
                                    <div class="w-7 h-7 rounded-lg bg-emerald-950/50 flex items-center justify-center text-xs shrink-0">
                                        📘
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[12px] font-semibold text-slate-200 truncate">
                                            <?= htmlspecialchars($achat['pdf_titre']) ?>
                                        </p>
                                        <p class="text-[10px] text-slate-600 truncate mt-0.5">
                                            <?= htmlspecialchars($achat['user_email']) ?>
                                            · <?= date('d/m/Y', strtotime($achat['achete_le'])) ?>
                                        </p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <p class="text-[12px] font-bold text-gold-500">
                                            <?= number_format($achat['montant'], 0, ',', ' ') ?>
                                            <span class="text-[9px] text-gold-800">FCFA</span>
                                        </p>
                                        <span class="text-[9px] bg-emerald-950/60 text-emerald-500 border border-emerald-900/40 px-1.5 py-0.5 rounded-full font-medium">
                                            ✓ Payé
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="px-6 py-10 text-center text-slate-600 text-sm">
                                Aucun achat pour le moment.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

        </div>
    </div>

</body>

</html>
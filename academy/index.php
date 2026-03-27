<?php
// /var/www/html/academy/index.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Academy.php';

// session_start uniquement si pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? null;

$academy         = new Academy($pdo);
$categories      = $academy->getCategories();
$coursesWithProgress = $user_id ? $academy->getAllCoursesWithProgress($user_id) : [];

$totalCours      = array_sum(array_column($categories, 'nb_cours'));
$totalCategories = count($categories);
$coursEnCours    = 0;
$coursTermines   = 0;

if ($user_id && !empty($coursesWithProgress)) {
    foreach ($coursesWithProgress as $c) {
        if ($c['progression'] > 0 && $c['progression'] < 100) $coursEnCours++;
        if ($c['progression'] == 100) $coursTermines++;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wari Academy — Éducation Financière</title>

    <link rel="icon" type="image/png" href="../assets/warifinance3d.png" />
    <link rel="apple-touch-icon" href="../assets/warifinance3d.png">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0F0A02;
            color: #FAF5E9;
        }

        .font-heading {
            font-family: 'Outfit', sans-serif;
        }

        /* Couleur Or Wari */
        .text-wari-gold {
            color: #F5A623;
        }

        .bg-wari-gold {
            background-color: #F5A623;
        }

        .border-wari-gold {
            border-color: rgba(201, 168, 76, 0.2);
        }

        /* Bento Glass Effect */
        .bento-card {
            background: linear-gradient(145deg, #1A1209 0%, #0F0A02 100%);
            border: 1px solid rgba(201, 168, 76, 0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .bento-card:hover {
            border-color: rgba(201, 168, 76, 0.4);
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        /* Animation de brillance */
        .shimmer {
            position: relative;
            overflow: hidden;
        }

        .shimmer::after {
            content: '';
            position: absolute;
            top: -150%;
            left: -150%;
            width: 300%;
            height: 300%;
            background: radial-gradient(circle, rgba(201, 168, 76, 0.08) 0%, transparent 50%);
            pointer-events: none;
        }
    </style>
</head>

<body class="antialiased selection:bg-wari-gold selection:text-black">

    <nav class="sticky top-0 z-50 bg-[#0F0A02] border-b border-wari-gold/20 px-6 h-20 flex items-center justify-between">
        <a href="https://wari.digiroys.com/accueil/" class="font-heading text-2xl font-black text-wari-gold tracking-tighter">
            Wari<span class="text-white/80 font-light">Academy.</span>
        </a>

        <div class="hidden md:flex items-center gap-1 bg-white/5 p-1 rounded-xl border border-white/10">
            <a href="https://wari.digiroys.com" class="text-white/60 hover:text-white px-4 py-2 text-xs font-bold uppercase tracking-widest transition-all">← Dashboard</a>
            <a href="/academy/" class="bg-wari-gold text-black px-4 py-2 text-xs font-black uppercase tracking-widest rounded-lg shadow-lg">Academy</a>
        </div>

        <?php if ($user_id): ?>
            <a href="https://wari.digiroys.com/profil" class="flex items-center gap-3 bg-white/5 border border-white/10 px-4 py-2 rounded-xl hover:bg-white/10 transition-all">
                <span class="text-xs font-bold text-white/80">Mon Profil</span>
                <div class="w-8 h-8 bg-wari-gold rounded-lg flex items-center justify-center text-black font-bold text-xs">ES</div>
            </a>
        <?php else: ?>
            <a href="https://wari.digiroys.com/register" class="bg-wari-gold text-black px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest hover:scale-105 transition-all shadow-xl shadow-wari-gold/20">
                Rejoindre l'élite
            </a>
        <?php endif; ?>
    </nav>

    <div class="max-w-7xl mx-auto px-6 py-12">

        <div class="grid grid-cols-1 md:grid-cols-12 gap-5 mb-16">

            <div class="md:col-span-8 bento-card shimmer rounded-[3rem] p-10 md:p-16 flex flex-col justify-end min-h-[420px]">
                <div class="mb-8">
                    <span class="inline-flex items-center gap-2 bg-wari-gold/10 border border-wari-gold/30 text-wari-gold text-[10px] font-black uppercase tracking-[0.3em] px-5 py-2 rounded-full">
                        <span class="w-2 h-2 bg-wari-gold rounded-full animate-pulse"></span>
                        Savoir c'est Pouvoir
                    </span>
                </div>
                <h1 class="font-heading text-5xl md:text-7xl font-black text-white leading-[1.1] mb-6">
                    L'argent, ça <br><span class="text-wari-gold italic">s'apprend.</span>
                </h1>
                <p class="text-white/60 text-sm md:text-lg max-w-lg leading-relaxed">
                    Des mini-cours stratégiques pour maîtriser tes finances, bâtis sur mesure pour l'entrepreneur africain moderne.
                </p>
            </div>

            <div class="md:col-span-4 grid grid-rows-3 gap-5">
                <div class="bento-card rounded-[2.5rem] p-8 flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-black text-wari-gold uppercase mb-1">Cours</p>
                        <div class="text-4xl font-heading font-black"><?= $totalCours ?: '24' ?></div>
                    </div>
                    <span class="text-4xl opacity-100">📚</span>
                </div>
                <div class="bento-card rounded-[2.5rem] p-8 flex items-center justify-between bg-wari-gold !text-black">
                    <div>
                        <p class="text-[10px] font-black uppercase mb-1 text-wari-gold">Accès</p>
                        <div class="text-4xl font-heading font-black text-white">Free</div>
                    </div>
                    <span class="text-4xl opacity-100">🔓</span>
                </div>
                <div class="bento-card rounded-[2.5rem] p-8 flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-black text-wari-gold uppercase mb-1">Domaines</p>
                        <div class="text-4xl font-heading font-black"><?= $totalCategories ?: '06' ?></div>
                    </div>
                    <span class="text-4xl opacity-100">💎</span>
                </div>
            </div>
        </div>

        <?php if ($user_id): ?>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-20">
                <div class="bg-white/5 border border-white/10 p-6 rounded-[2rem] flex items-center gap-4">
                    <div class="text-2xl">📖</div>
                    <div>
                        <div class="text-xl font-bold"><?= count($coursesWithProgress) ?></div>
                        <div class="text-[9px] text-white/30 uppercase font-bold tracking-widest">Disponibles</div>
                    </div>
                </div>
                <div class="bg-white/5 border border-white/10 p-6 rounded-[2rem] flex items-center gap-4">
                    <div class="text-2xl text-blue-400">▶️</div>
                    <div>
                        <div class="text-xl font-bold"><?= $coursEnCours ?></div>
                        <div class="text-[9px] text-white/30 uppercase font-bold tracking-widest">En cours</div>
                    </div>
                </div>
                <div class="bg-white/5 border border-white/10 p-6 rounded-[2rem] flex items-center gap-4">
                    <div class="text-2xl text-green-400">🏆</div>
                    <div>
                        <div class="text-xl font-bold"><?= $coursTermines ?></div>
                        <div class="text-[9px] text-white/30 uppercase font-bold tracking-widest">Terminés</div>
                    </div>
                </div>
                <div class="bg-wari-gold/10 border border-wari-gold/20 p-6 rounded-[2rem] flex items-center gap-4">
                    <div class="text-2xl">⚡</div>
                    <div>
                        <div class="text-xl font-bold text-wari-gold">Pro</div>
                        <div class="text-[9px] text-wari-gold/50 uppercase font-bold tracking-widest">Niveau</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="flex items-center gap-4 mb-8">
            <h2 class="font-heading text-2xl font-black uppercase tracking-tight">Thématiques</h2>
            <div class="flex-1 h-[1px] bg-white/10"></div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-20">
            <?php foreach ($categories as $cat): ?>
                <a href="/academy/categorie.php?slug=<?= $cat['slug'] ?>" class="bento-card rounded-3xl p-6 text-center group">
                    <div class="text-3xl mb-3 group-hover:scale-125 transition-transform"><?= $cat['icone'] ?></div>
                    <div class="text-[10px] font-black uppercase text-white/80"><?= $cat['titre'] ?></div>
                    <div class="text-[9px] text-wari-gold/50 mt-1"><?= $cat['nb_cours'] ?> COURS</div>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="flex items-center justify-between mb-10">
            <h2 class="font-heading text-3xl font-black">Tous les cours</h2>
            <span class="text-xs font-bold text-white/70 uppercase tracking-[0.3em]"><?= $totalCours ?> Modules</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php foreach ($coursesWithProgress as $course): ?>
                <a href="/academy/course.php?slug=<?= $course['slug'] ?>" class="bento-card rounded-[2.5rem] overflow-hidden group flex flex-col">
                    <div class="h-3 bg-wari-gold shadow-[0_0_20px_rgba(201,168,76,0.3)]"></div>
                    <div class="p-8 flex-1">
                        <div class="flex justify-between items-start mb-6">
                            <span class="bg-white/5 border border-white/10 px-3 py-1 rounded-lg text-[9px] font-black uppercase text-wari-gold">
                                <?= $course['category_icone'] ?> <?= $course['category_titre'] ?>
                            </span>
                            <span class="text-[9px] font-bold text-white/20 uppercase tracking-widest italic"><?= $course['niveau'] ?></span>
                        </div>
                        <h3 class="font-heading text-xl font-black text-white mb-4 group-hover:text-wari-gold transition-colors leading-tight"><?= $course['titre'] ?></h3>
                        <p class="text-white/40 text-sm line-clamp-2 leading-relaxed mb-8"><?= $course['description'] ?></p>

                        <?php if ($course['progression'] > 0): ?>
                            <div class="space-y-3">
                                <div class="flex justify-between text-[9px] font-black uppercase text-wari-gold/70">
                                    <span>Progression</span>
                                    <span><?= $course['progression'] ?>%</span>
                                </div>
                                <div class="h-1.5 w-full bg-white/5 rounded-full overflow-hidden">
                                    <div class="h-full bg-wari-gold shadow-[0_0_10px_rgba(201,168,76,0.5)]" style="width: <?= $course['progression'] ?>%"></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-8 pt-0 mt-auto flex items-center justify-between border-t border-white/5 pt-6 bg-white/[0.02]">
                        <div class="flex gap-4">
                            <span class="text-[10px] font-bold text-white/30 uppercase">⏱ <?= $course['duree_minutes'] ?>m</span>
                            <span class="text-[10px] font-bold text-white/30 uppercase">📖 <?= $course['nb_lecons'] ?> leçons</span>
                        </div>
                        <span class="text-[10px] font-black uppercase tracking-widest text-wari-gold group-hover:translate-x-2 transition-transform">
                            <?= ($course['progression'] == 100) ? 'Revoir' : 'Ouvrir →' ?>
                        </span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

    </div>

    <footer class="mt-32 py-10 border-t border-white/5 bg-black/50 text-center">
        <div class="font-heading text-xl font-black text-wari-gold mb-4 tracking-tighter">Wari Academy.</div>
        <p class="text-[10px] font-bold text-white/40 uppercase tracking-[0.4em] mb-8 italic">Le savoir est la seule monnaie qui ne se dévalue jamais.</p>
        <div class="text-[9px] text-white/20">&copy; <?= date('Y') ?> WARI FINANCE — TOUS DROITS RÉSERVÉS.</div>
    </footer>

</body>

</html>
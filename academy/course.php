<?php
// /var/www/html/academy/course.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Academy.php';

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/session_check.php';

$user_id = $_SESSION['user_id'] ?? null;

// Redirection si non connecté
if (!$user_id) {
    header('Location: https://wari.digiroys.com/config/auth.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$academy = new Academy($pdo);

// Récupération du cours via le slug
$slug = $_GET['slug'] ?? '';
if (!$slug) {
    header('Location: /academy/');
    exit;
}

$course = $academy->getCourseBySlug($slug);
if (!$course) {
    header('Location: /academy/');
    exit;
}

$lessons  = $academy->getLessonsByCourse($course['id']);
$pdfs     = $academy->getPdfsByCourse($course['id']);
$progress = $academy->getCourseProgress($user_id, $course['id']);

// Statut de chaque leçon pour cet utilisateur
foreach ($lessons as &$lesson) {
    $lesson['complete'] = $academy->isLessonComplete($user_id, $lesson['id']);
}
unset($lesson);

// Première leçon non complétée = leçon à reprendre
$nextLesson = null;
foreach ($lessons as $l) {
    if (!$l['complete']) {
        $nextLesson = $l;
        break;
    }
}
// Si tout est terminé, pointer sur la première leçon
if (!$nextLesson && !empty($lessons)) {
    $nextLesson = $lessons[0];
}

$totalLecons   = count($lessons);
$doneLecons    = count(array_filter($lessons, fn($l) => $l['complete']));
$coursTermine  = $totalLecons > 0 && $doneLecons === $totalLecons;
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($course['titre']) ?> — Wari Academy</title>

    <link rel="icon" type="image/png" href="../assets/warifinance3d.png" />
    <link rel="apple-touch-icon" href="../assets/warifinance3d.png" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <!-- Utilisation de Outfit (Titres) et Plus Jakarta Sans (Corps) comme défini par le standard Tailwind de Wari -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                        heading: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        slate: {
                            850: '#151e2e',
                            900: '#0f172a',
                            950: '#020617',
                        },
                        wari: {
                            gold: '#C9A84C',
                            goldLight: '#F0D080',
                            goldDark: '#8B6914',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .cat-color {
            color: <?= htmlspecialchars($course['category_couleur'] ?? '#C9A84C') ?>;
        }
        .bg-cat-color {
            background-color: <?= htmlspecialchars($course['category_couleur'] ?? '#C9A84C') ?>;
        }
        .border-cat-color {
            border-color: <?= htmlspecialchars($course['category_couleur'] ?? '#C9A84C') ?>;
        }
        
        /* Glassmorphism Bento Cards */
        .bento-card {
            background: rgba(30, 41, 59, 0.4); /* base slate-800 with transparency */
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 1.5rem;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .bento-card:hover {
            border-color: rgba(255, 255, 255, 0.1);
            background: rgba(30, 41, 59, 0.6);
        }
        .bento-card-highlight {
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.9));
            border: 1px solid rgba(201, 168, 76, 0.2);
        }
    </style>
</head>

<body class="bg-slate-950 text-slate-300 font-sans antialiased min-h-screen flex flex-col selection:bg-wari-gold selection:text-slate-950">

    <!-- ── NAVIGATION ──────────────────────────────────────────── -->
    <nav class="bg-slate-950/80 backdrop-blur-md mt-3 mb-2 px-4 h-18 flex items-center justify-between">
        <a href="/academy/" class="font-heading text-2xl font-black text-wari-gold tracking-tight">
            Wari<span class="font-light text-white">Academy.</span>
        </a>
        
        <a href="/academy/" class="hidden md:flex items-center gap-2 text-slate-400 hover:text-white transition-colors text-xs font-bold uppercase tracking-widest">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Retour aux cours
        </a>

        <a href="#" class="flex items-center gap-3 bg-white/5 hover:bg-white/10">
            <div class="w-8 h-8 bg-wari-gold rounded-lg flex items-center justify-center text-slate-950 font-bold text-xs uppercase"><?= substr($_SESSION['user_email'] ?? 'U', 0, 2) ?></div>
        </a>
    </nav>

    <main class="flex-1 w-full max-w-7xl mx-auto px-2 md:px-2 py-2 md:py-4 flex flex-col gap-8 md:gap-12">
        
        <!-- ── HERO BENTO (Span full) ────────────────────────────── -->
        <section class="bento-card bento-card-highlight p-2 md:p-12 relative overflow-hidden group rounded-[1rem]">
            <!-- Decorative background elements -->
            <div class="absolute -top-40 -right-40 w-96 h-96 bg-cat-color rounded-full mix-blend-multiply filter blur-3xl opacity-10 group-hover:opacity-[0.15] transition-opacity duration-700"></div>
            <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-wari-gold rounded-full mix-blend-multiply filter blur-3xl opacity-10 group-hover:opacity-[0.15] transition-opacity duration-700"></div>

            <div class="relative z-10 flex flex-col md:flex-row md:items-end justify-between gap-8">
                <div class="flex-1">
                    <!-- Breadcrumb -->
                    <div class="flex items-center gap-2 text-xs md:text-sm text-slate-400 mb-6 font-medium">
                        <a href="/academy/" class="hover:text-wari-gold transition-colors">Academy</a>
                        <span>/</span>
                        <a href="/academy/?cat=<?= htmlspecialchars($course['category_slug']) ?>" class="hover:text-wari-gold transition-colors">
                            <?= htmlspecialchars($course['category_titre']) ?>
                        </a>
                        <span>/</span>
                        <strong class="text-wari-goldLight truncate max-w-[200px] sm:max-w-none block"><?= htmlspecialchars($course['titre']) ?></strong>
                    </div>

                    <!-- Category Badge -->
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest mb-6 border border-cat-color bg-slate-900/50 cat-color shadow-[0_0_15px_rgba(0,0,0,0.2)]">
                        <span class="w-1.5 h-1.5 rounded-full bg-cat-color"></span>
                        <?= htmlspecialchars($course['category_titre']) ?>
                    </div>

                    <!-- Title & Description -->
                    <h1 class="font-heading text-4xl md:text-5xl lg:text-7xl font-black text-white leading-[1.1] mb-6">
                        <?= htmlspecialchars($course['titre']) ?>
                    </h1>

                    <?php if ($course['description']): ?>
                        <p class="text-slate-400 text-sm md:text-lg max-w-2xl leading-relaxed mb-8">
                            <?= htmlspecialchars($course['description']) ?>
                        </p>
                    <?php endif; ?>

                    <!-- Meta Tags -->
                    <div class="flex flex-wrap gap-4 text-xs font-bold uppercase tracking-widest text-slate-400">
                        <div class="flex items-center gap-2 bg-slate-800/50 px-4 py-2 rounded-xl border border-slate-700/50">
                            <strong class="text-white"><?= $course['duree_minutes'] ?> min</strong>
                        </div>
                        <div class="flex items-center gap-2 bg-slate-800/50 px-4 py-2 rounded-xl border border-slate-700/50">
                            <strong class="text-white"><?= $totalLecons ?> leçon<?= $totalLecons > 1 ? 's' : '' ?></strong>
                        </div>
                        <div class="flex items-center gap-2 bg-slate-800/50 px-4 py-2 rounded-xl border border-slate-700/50">
                            <strong class="text-white"><?= ucfirst($course['niveau']) ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Progress & CTA Box -->
                <div class="bg-slate-900/60 backdrop-blur-md rounded-3xl p-4 border border-white/10 shrink-0 w-full md:w-80 shadow-2xl">
                    <div class="flex justify-between items-end mb-3">
                        <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Progression</div>
                        <div class="text-3xl font-heading font-black text-wari-gold"><?= $progress ?>%</div>
                    </div>
                    
                    <div class="h-2 w-full bg-slate-800 rounded-full overflow-hidden mb-4">
                        <div class="h-full bg-gradient-to-r from-wari-goldDark to-wari-gold rounded-full transition-all duration-1000 relative" style="width:<?= $progress ?>%">
                            <div class="absolute inset-0 bg-white/20 animate-pulse"></div>
                        </div>
                    </div>
                    <div class="text-[10px] font-bold uppercase tracking-widest text-slate-500 text-center mb-6">
                        <?= $doneLecons ?> / <?= $totalLecons ?> terminées
                    </div>

                    <?php if ($nextLesson): ?>
                        <a href="/academy/lesson.php?id=<?= $nextLesson['id'] ?>" 
                           class="group flex items-center justify-center gap-2 w-full py-4 px-6 rounded-2xl font-black text-xs uppercase tracking-widest transition-all transform hover:scale-[1.02] active:scale-[0.98] shadow-lg <?= $coursTermine ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/20' : 'bg-wari-gold text-slate-950 hover:bg-wari-goldLight shadow-wari-gold/20' ?>">
                            <?php if ($coursTermine): ?>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                Revoir le module
                            <?php elseif ($progress > 0): ?>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Continuer
                            <?php else: ?>
                                Commencer
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- ── MAIN CONTENT GRID ────────────────────────────────── -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- LISTE DES LEÇONS -->
            <div class="lg:col-span-8 space-y-6">
                <div class="bento-card rounded-[1rem] overflow-hidden">
                    <div class="p-6 md:p-8 border-b border-white/5 flex items-center justify-between bg-slate-900/40">
                        <h2 class="font-heading text-2xl md:text-3xl font-black text-white flex items-center gap-3">
                            Programme
                        </h2>
                        <span class="px-4 py-1 bg-slate-800 text-slate-300 text-xs font-black uppercase tracking-widest rounded-full border border-slate-700">
                            <?= $doneLecons ?> / <?= $totalLecons ?>
                        </span>
                    </div>

                    <div class="divide-y divide-white/5">
                        <?php if (!empty($lessons)): ?>
                            <?php foreach ($lessons as $i => $lesson): ?>
                                <?php
                                $isComplete = $lesson['complete'];
                                $isCurrent  = $nextLesson && $lesson['id'] === $nextLesson['id'] && !$coursTermine;
                                ?>
                                <a href="/academy/lesson.php?id=<?= $lesson['id'] ?>" 
                                   class="group p-5 md:p-6 flex items-center gap-5 hover:bg-slate-800/50 transition-colors <?= $isCurrent ? 'bg-slate-800/30 border-l-4 border-cat-color' : 'border-l-4 border-transparent' ?>">
                                    
                                    <!-- Lesson Number or Check -->
                                    <div class="w-12 h-12 shrink-0 rounded-2xl flex items-center justify-center font-black text-base transition-all <?= $isComplete ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : ($isCurrent ? 'bg-cat-color text-slate-950 shadow-lg shadow-cat-color/30' : 'bg-slate-800 text-slate-500 group-hover:bg-slate-700') ?>">
                                        <?php if ($isComplete): ?>
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                        <?php else: ?>
                                            <?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Lesson Info -->
                                    <div class="flex-1 min-w-0">
                                        <div class="text-base md:text-lg font-bold truncate transition-colors <?= $isComplete ? 'text-slate-500 line-through decoration-slate-600/50' : ($isCurrent ? 'text-white' : 'text-slate-200 group-hover:text-white') ?>">
                                            <?= htmlspecialchars($lesson['titre']) ?>
                                        </div>
                                        <div class="text-[10px] uppercase font-bold tracking-widest mt-1.5 flex items-center gap-2 <?= $isComplete ? 'text-slate-600' : 'text-slate-400' ?>">
                                            <?php
                                            $types = ['texte' => '📄 LECTURE', 'video' => '🎥 VIDÉO', 'quiz' => '🧩 QUIZ'];
                                            echo $types[$lesson['type']] ?? '📄 LECTURE';
                                            ?>
                                        </div>
                                    </div>

                                    <!-- Lesson Status Icon -->
                                    <div class="shrink-0 text-2xl opacity-50 group-hover:opacity-100 transition-opacity group-hover:scale-110 transform duration-300">
                                        <?php if ($isComplete): ?>
                                            ✅
                                        <?php elseif ($isCurrent): ?>
                                            <span class="animate-pulse">▶️</span>
                                        <?php else: ?>
                                            🔒
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-16 text-center text-slate-500">
                                <svg class="w-16 h-16 mx-auto mb-4 opacity-30 block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                <p class="text-sm font-medium">Aucune leçon disponible pour ce cours pour le moment.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- SIDEBAR -->
            <aside class="lg:col-span-4 space-y-6">
                
                <!-- Badge cours terminé -->
                <?php if ($coursTermine): ?>
                    <div class="bento-card rounded-[2rem] overflow-hidden border border-emerald-500/30">
                        <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/10 to-transparent"></div>
                        <div class="relative p-8 text-center">
                            <div class="text-5xl mb-4 animate-bounce">🏆</div>
                            <h3 class="font-heading text-xl font-black text-emerald-400 mb-2">MASTERCLASS</h3>
                            <p class="text-sm text-emerald-400/80 font-medium leading-relaxed">Félicitations, tu as complété ce module avec succès.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Stats Bento -->
                <div class="bento-card rounded-[1rem] p-4">
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mb-6 flex items-center gap-2">
                        <span>Statistiques</span>
                        <div class="flex-1 h-[1px] bg-white/5 ml-2"></div>
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-slate-900/50 border border-white/5 rounded-2xl p-4 text-center hover:bg-slate-800/80 transition-colors">
                            <div class="font-heading text-4xl font-black text-white mb-2"><?= $totalLecons ?></div>
                            <div class="text-[9px] uppercase font-black text-slate-500 tracking-[0.2em]">Leçons</div>
                        </div>
                        <div class="bg-slate-900/50 border border-white/5 rounded-2xl p-4 text-center hover:bg-slate-800/80 transition-colors">
                            <div class="font-heading text-4xl font-black text-white mb-2"><?= $course['duree_minutes'] ?></div>
                            <div class="text-[9px] uppercase font-black text-slate-500 tracking-[0.2em]">Minutes</div>
                        </div>
                        <div class="bg-slate-900/50 border border-white/5 rounded-2xl p-4 text-center hover:bg-slate-800/80 transition-colors">
                            <div class="font-heading text-4xl font-black text-wari-gold mb-2"><?= $progress ?>%</div>
                            <div class="text-[9px] uppercase font-black text-slate-500 tracking-[0.2em]">Acquis</div>
                        </div>
                        <div class="bg-slate-900/50 border border-white/5 rounded-2xl p-4 text-center hover:bg-slate-800/80 transition-colors">
                            <div class="font-heading text-4xl font-black text-white mb-2"><?= ucfirst($course['niveau'][0]) ?></div>
                            <div class="text-[9px] uppercase font-black text-slate-500 tracking-[0.2em]"><?= ucfirst($course['niveau']) ?></div>
                        </div>
                    </div>
                </div>

                <!-- PDF Payants -->
                <?php if (!empty($pdfs)): ?>
                    <div class="bento-card rounded-[2rem] p-8">
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mb-6 flex items-center gap-2">
                           <span>Ressources</span>
                            <div class="flex-1 h-[1px] bg-white/5 ml-2"></div>
                        </h3>
                        <div class="space-y-4">
                            <?php foreach ($pdfs as $pdf): ?>
                                <?php $acheté = $academy->hasUserBoughtPdf($user_id, $pdf['id']); ?>
                                <div class="bg-slate-900/50 border border-white/5 rounded-2xl p-5 hover:border-white/10 transition-colors">
                                    <div class="flex gap-4">
                                       
                                        <div class="flex-1 min-w-0">
                                            <div class="font-bold text-white text-sm mb-1 line-clamp-2"><?= htmlspecialchars($pdf['titre']) ?></div>
                                            <?php if ($pdf['description']): ?>
                                                <div class="text-[11px] text-slate-500 mb-4 line-clamp-2 leading-relaxed"><?= htmlspecialchars($pdf['description']) ?></div>
                                            <?php endif; ?>

                                            <div class="flex items-center justify-between mt-auto">
                                                <?php if ($pdf['est_gratuit'] || $acheté): ?>
                                                    <span class="text-[9px] font-black uppercase tracking-widest text-emerald-400 px-3 py-1 bg-emerald-500/10 rounded-lg">Gratuit</span>
                                                    <a href="/academy/pdf_download.php?id=<?= $pdf['id'] ?>" class="text-[10px] font-black uppercase tracking-widest text-white bg-slate-700 hover:bg-slate-600 px-4 py-2 rounded-xl transition-colors">
                                                        Télécharger
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-xs font-black text-wari-gold"><?= number_format($pdf['prix'], 0, ',', ' ') ?> FCFA</span>
                                                    <a href="/academy/pdf_achat.php?id=<?= $pdf['id'] ?>" class="text-[10px] font-black uppercase tracking-widest text-slate-900 bg-wari-gold hover:bg-wari-goldLight px-4 py-2 rounded-xl transition-colors">
                                                        Obtenir
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Auteur -->
                <div class="bento-card rounded-[1rem] p-4">
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mb-6 flex items-center gap-2">
                       <span>Auteur</span>
                        <div class="flex-1 h-[1px] bg-white/5 ml-2"></div>
                    </h3>
                    <div class="flex items-center gap-5 bg-slate-900/50 border border-white/5 rounded-2xl p-5">
                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-wari-goldDark to-wari-gold flex items-center justify-center text-3xl shadow-xl shrink-0">
                            🧑🏾
                        </div>
                        <div>
                            <div class="font-heading font-black text-white text-lg mb-1"><?= htmlspecialchars($course['auteur']) ?></div>
                            <div class="text-[10px] font-bold uppercase tracking-widest text-wari-gold">Coach financier</div>
                        </div>
                    </div>
                </div>

            </aside>

        </div>
    </main>
    
    <footer class="mt-auto py-10 border-t border-white/5 text-center">
        <div class="font-heading text-lg font-black text-wari-gold mb-2 tracking-tighter">Wari Academy.</div>
        <div class="text-[9px] font-bold text-white/30 uppercase tracking-[0.3em]">&copy; <?= date('Y') ?> WARI FINANCE — TOUS DROITS RÉSERVÉS.</div>
    </footer>

</body>
</html>
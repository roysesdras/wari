<?php
// /var/www/html/academy/lesson.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Academy.php';

session_start();
$user_id = $_SESSION['user_id'] ?? null;

// Redirection si non connecté
if (!$user_id) {
    header('Location: https://wari.digiroys.com/login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$academy = new Academy($pdo);

// Récupération de la leçon
$lesson_id = (int)($_GET['id'] ?? 0);
if (!$lesson_id) {
    header('Location: /academy/');
    exit;
}

$lesson = $academy->getLessonById($lesson_id);
if (!$lesson) {
    header('Location: /academy/');
    exit;
}

$course   = $academy->getCourseById($lesson['course_id']);
$lessons  = $academy->getLessonsByCourse($lesson['course_id']);
$prevLesson = $academy->getPrevLesson($lesson['course_id'], $lesson['ordre']);
$nextLesson = $academy->getNextLesson($lesson['course_id'], $lesson['ordre']);
$progress   = $academy->getCourseProgress($user_id, $lesson['course_id']);
$isComplete = $academy->isLessonComplete($user_id, $lesson_id);

// Statut de toutes les leçons (pour la sidebar)
foreach ($lessons as &$l) {
    $l['complete'] = $academy->isLessonComplete($user_id, $l['id']);
}
unset($l);

$totalLecons = count($lessons);
$doneLecons  = count(array_filter($lessons, fn($l) => $l['complete']));

// Numéro de la leçon courante
$currentIndex = 0;
foreach ($lessons as $i => $l) {
    if ($l['id'] === $lesson_id) {
        $currentIndex = $i;
        break;
    }
}

// Marquer comme complétée si action POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'complete') {
    $academy->markLessonComplete($user_id, $lesson_id, $lesson['course_id']);
    // Recalcul
    $isComplete = true;
    $progress   = $academy->getCourseProgress($user_id, $lesson['course_id']);
    $doneLecons = min($doneLecons + 1, $totalLecons);

    // Si leçon suivante → rediriger directement
    if ($nextLesson) {
        header('Location: /academy/lesson.php?id=' . $nextLesson['id']);
        exit;
    } else {
        // Cours terminé → retour à la page du cours
        header('Location: /academy/course.php?slug=' . urlencode($course['slug']) . '&termine=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($lesson['titre']) ?> — Wari Academy</title>

    <link rel="icon" type="image/png" href="../assets/warifinance3d.png" />
    <link rel="apple-touch-icon" href="../assets/warifinance3d.png" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <!-- Outfit (Titres) et Plus Jakarta Sans (Corps) -->
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
        :root {
            --wari-gold: #C9A84C;
            --cat-color: <?= htmlspecialchars($course['category_couleur'] ?? '#C9A84C') ?>;
        }
        
        /* Glassmorphism Bento Cards */
        .bento-card {
            background: rgba(30, 41, 59, 0.4); /* slate-800 with opacity */
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 1rem;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .bento-card-highlight {
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.9));
            /* border-top: 4px solid var(--cat-color); */
        }

        /* Variables CSS pour les éléments générés depuis la BDD */
        .lesson-content {
            font-size: 1.05rem;
            line-height: 1.8;
            color: #cbd5e1; /* text-slate-300 */
        }
        .lesson-content h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.75rem;
            font-weight: 800;
            color: #ffffff;
            margin-top: 2.5rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding-bottom: 0.5rem;
        }
        .lesson-content h3 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.25rem;
            font-weight: 700;
            color: #e2e8f0;
            margin-top: 2rem;
            margin-bottom: 0.75rem;
        }
        .lesson-content p {
            margin-bottom: 1.25rem;
            color: #cbd5e1;
        }
        .lesson-content ul, .lesson-content ol {
            padding-left: 1.5rem;
            margin-bottom: 1.25rem;
            color: #cbd5e1;
        }
        .lesson-content ul { list-style-type: disc; }
        .lesson-content ol { list-style-type: decimal; }
        .lesson-content li { margin-bottom: 0.5rem; }
        .lesson-content strong { color: #f8fafc; font-weight: 700; }
        .lesson-content em { font-style: italic; color: #94a3b8; }
        .lesson-content blockquote {
            border-left: 4px solid var(--wari-gold);
            background: rgba(201, 168, 76, 0.08);
            padding: 1.25rem 1.5rem;
            border-radius: 0 1rem 1rem 0;
            margin: 2rem 0;
            font-style: italic;
            color: #e2e8f0;
            font-size: 1.1rem;
        }
        .lesson-content .encadre {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(201, 168, 76, 0.2);
            border-radius: 1.25rem;
            padding: 1.5rem 1.75rem;
            margin: 2rem 0;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .lesson-content .encadre-titre {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 0.9rem;
            color: var(--wari-gold);
            margin-bottom: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        .lesson-content a {
            color: var(--wari-gold);
            text-decoration: underline;
            text-underline-offset: 4px;
            transition: color 0.2s;
        }
        .lesson-content a:hover {
            color: #ffffff;
        }
        .lesson-content img {
            max-width: 100%;
            height: auto;
            border-radius: 1rem;
            margin: 2rem auto;
            display: block;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255,255,255,0.05);
        }

        /* Video Wrapper */
        .video-wrap {
            position: relative;
            width: 100%;
            aspect-ratio: 16/9;
            background: #000;
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .video-wrap iframe {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            border: none;
        }
        
    </style>
</head>

<body class="bg-slate-950 text-slate-300 font-sans antialiased min-h-screen flex flex-col selection:bg-wari-gold selection:text-slate-950">

    <!-- ── NAVIGATION ── -->
    <nav class="sticky top-0 z-50 bg-slate-950/80 backdrop-blur-md h-20 flex flex-col justify-center px-2 md:px-4">
        <div class="flex items-center justify-between gap-4 md:gap-8 w-full">
            
            <a href="/academy/" class="font-heading text-xl md:text-2xl font-black text-wari-gold tracking-tight shrink-0">
                Wari<span class="font-light text-white">Academy</span>
            </a>

            <!-- Progress Bar inside Nav -->
            <div class="hidden md:flex flex-col flex-1 max-w-sm ml-auto mr-auto">
                <div class="flex justify-between items-center text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">
                    <span class="truncate pr-4">Cours : <?= htmlspecialchars($course['titre']) ?></span>
                    <span class="text-wari-gold shrink-0"><?= $progress ?>%</span>
                </div>
                <div class="h-1.5 w-full bg-slate-800 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-wari-goldDark to-wari-gold rounded-full transition-all duration-700" style="width:<?= $progress ?>%"></div>
                </div>
            </div>

            <a href="/academy/course.php?slug=<?= urlencode($course['slug']) ?>" class="shrink-0 flex items-center gap-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 px-4 py-2 rounded-xl transition-all text-xs font-bold uppercase tracking-widest text-slate-300 hover:text-white">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                <span class="hidden sm:inline">Vue du cours</span>
            </a>
        </div>
        <!-- Mobile progress bar (under nav elements) -->
        <div class="md:hidden w-full mt-1 flex flex-col">
            <div class="flex justify-between text-[9px] font-bold uppercase tracking-widest text-slate-400 mb-1">
                <span class="truncate">Progression</span>
                <span class="text-wari-gold"><?= $progress ?>%</span>
            </div>
            <div class="h-1 w-full bg-slate-800 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-wari-goldDark to-wari-gold rounded-full" style="width:<?= $progress ?>%"></div>
            </div>
        </div>
    </nav>

    <!-- ── LAYOUT PRINCIPAL ── -->
    <div class="flex-1 w-full max-w-[1400px] mx-auto px-2 md:px-4 py-8 md:py-6 grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <!-- ── MAIN (Gauche) ── -->
        <main class="lg:col-span-8 flex flex-col gap-8 w-full max-w-4xl mx-auto">

            <!-- En-tête leçon (Bento Card) -->
            <header class="bento-card bento-card-highlight p-2 md:p-4 relative overflow-hidden">
                <!-- Decorative glares -->
                <!-- <div class="absolute -top-32 -right-32 w-64 h-64 bg-[var(--cat-color)] rounded-full mix-blend-multiply opacity-20 blur-3xl"></div> -->
                
                <div class="relative z-10">
                    <!-- Breadcrumbs -->
                    <div class="flex flex-wrap items-center gap-2 text-xs md:text-sm font-medium text-slate-400 mb-6">
                        <a href="/academy/" class="hover:text-white transition-colors">Academy</a>
                        <span>/</span>
                        <a href="/academy/course.php?slug=<?= urlencode($course['slug']) ?>" class="hover:text-white transition-colors">
                            <?= htmlspecialchars($course['titre']) ?>
                        </a>
                        <span>/</span>
                        <strong class="text-wari-goldLight truncate max-w-[150px] sm:max-w-none"><?= htmlspecialchars($lesson['titre']) ?></strong>
                    </div>

                    <!-- Lesson Badge -->
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest mb-6 border border-white/10 bg-black/40 shadow-inner">
                        <span class="w-2 h-2 rounded-full bg-[var(--cat-color)] shadow-[0_0_10px_var(--cat-color)] animate-pulse"></span>
                        Leçon <?= str_pad($currentIndex + 1, 2, '0', STR_PAD_LEFT) ?> sur <?= str_pad($totalLecons, 2, '0', STR_PAD_LEFT) ?>
                    </div>

                    <!-- Title -->
                    <h1 class="font-heading text-3xl md:text-5xl font-black text-white leading-[1.15] mb-6">
                        <?= htmlspecialchars($lesson['titre']) ?>
                    </h1>

                    <!-- Meta Tags -->
                    <div class="flex flex-wrap items-center gap-4 text-xs font-bold uppercase tracking-widest text-slate-400">
                        <?php // $typesIcon = ['texte', 'video', 'quiz']; ?>
                        <div class="flex items-center gap-2 bg-slate-900/60 px-4 py-2 rounded-xl border border-white/5">
                            <!--  -->
                            <span class="text-white"><?= ucfirst($lesson['type'] ?: 'Lecture') ?></span>
                        </div>
                        <div class="flex items-center gap-2 bg-slate-900/60 px-4 py-2 rounded-xl border border-white/5">
                            <span class="text-white"><?= htmlspecialchars($course['auteur']) ?></span>
                        </div>
                        
                        <?php if ($isComplete): ?>
                            <div class="flex items-center gap-2 bg-emerald-500/10 px-4 py-2 rounded-xl border border-emerald-500/20 text-emerald-400 ml-auto md:ml-0">
                                Validé
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <!-- Video Section -->
            <?php if ($lesson['type'] === 'video' && $lesson['video_url']): ?>
                <div class="video-wrap group">
                    <!-- Overlay gradient for extra style until interact -->
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-transparent to-transparent opacity-50 pointer-events-none group-hover:opacity-0 transition-opacity duration-500 z-10"></div>
                    <iframe src="<?= htmlspecialchars($lesson['video_url']) ?>" allowfullscreen loading="lazy"></iframe>
                </div>
            <?php endif; ?>

            <!-- Contenu Texte -->
            <?php if (!empty(trim($lesson['contenu']))): ?>
                <article class="bento-card p-2 md:p-4">
                    <div class="lesson-content">
                        <?= $lesson['contenu'] ?>
                    </div>
                </article>
            <?php endif; ?>

            <!-- Complete Action block -->
            <div class="bento-card p-2 md:p-4 text-center relative overflow-hidden hover:border-t-wari-gold transition-colors">
                <div class="absolute inset-0 bg-gradient-to-b from-wari-gold/5 to-transparent opacity-50"></div>
                
                <div class="relative z-10 flex flex-col items-center">
                    <?php if ($isComplete): ?>
                        <div class="w-16 h-16 bg-emerald-500/10 text-emerald-400 rounded-full flex items-center justify-center text-3xl mb-4 border border-emerald-500/30">✅</div>
                        <h3 class="font-heading text-xl font-bold text-white mb-2">Leçon validée !</h3>
                        <p class="text-slate-400 text-sm mb-8">Excellent travail. Vous avez déjà accompli cette étape.</p>
                        
                        <?php if ($nextLesson): ?>
                            <a href="/academy/lesson.php?id=<?= $nextLesson['id'] ?>" class="inline-flex items-center gap-3 bg-wari-gold hover:bg-wari-goldLight text-slate-900 font-black text-sm uppercase tracking-widest px-8 py-4 rounded-xl transition-all shadow-lg shadow-wari-gold/20 transform hover:-translate-y-1">
                                Étape suivante →
                            </a>
                        <?php else: ?>
                            <a href="/academy/course.php?slug=<?= urlencode($course['slug']) ?>" class="inline-flex items-center gap-3 bg-emerald-500 text-white font-black text-sm uppercase tracking-widest px-8 py-4 rounded-xl transition-all shadow-lg shadow-emerald-500/20 transform hover:-translate-y-1">
                                🏆 Terminer le module
                            </a>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="w-16 h-16 bg-white/5 text-wari-gold rounded-full flex items-center justify-center text-3xl mb-4 border border-white/10">🎓</div>
                        <h3 class="font-heading text-2xl font-black text-white mb-2">Avez-vous compris l'essentiel ?</h3>
                        <p class="text-slate-400 text-sm mb-8 max-w-md">Validez cette leçon pour enregistrer votre progression et débloquer la suite de la masterclass.</p>

                        <form method="POST" class="w-full sm:w-auto">
                            <input type="hidden" name="action" value="complete">
                            <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-3 bg-wari-gold hover:bg-wari-goldLight text-slate-900 font-black text-sm uppercase tracking-widest px-8 py-4 rounded-xl transition-all shadow-lg shadow-wari-gold/20 transform hover:scale-105 active:scale-95">
                                Valider la leçon
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pre/Next Navigations Bottom -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Précédente -->
                <?php if ($prevLesson): ?>
                    <a href="/academy/lesson.php?id=<?= $prevLesson['id'] ?>" class="group bento-card p-6 flex items-center gap-4 hover:-translate-x-1 transition-all">
                        <div class="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center text-wari-gold group-hover:bg-wari-gold/20 transition-colors shrink-0">
                            ←
                        </div>
                        <div class="min-w-0">
                            <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Leçon précédente</div>
                            <div class="font-bold text-white text-sm truncate"><?= htmlspecialchars($prevLesson['titre']) ?></div>
                        </div>
                    </a>
                <?php else: ?>
                    <div class="bento-card p-6 flex items-center gap-4 opacity-50 cursor-not-allowed">
                        <div class="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center text-slate-600 shrink-0">←</div>
                        <div>
                            <div class="text-[10px] font-black text-slate-600 uppercase tracking-widest mb-1.5">Début du cours</div>
                            <div class="font-bold text-slate-500 text-sm">Première leçon</div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Suivante -->
                <?php if ($nextLesson): ?>
                    <a href="/academy/lesson.php?id=<?= $nextLesson['id'] ?>" class="group bento-card p-6 flex items-center gap-4 text-right hover:translate-x-1 transition-all flex-row-reverse">
                        <div class="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center text-wari-gold group-hover:bg-wari-gold/20 transition-colors shrink-0">
                            →
                        </div>
                        <div class="min-w-0">
                            <div class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5">Leçon suivante</div>
                            <div class="font-bold text-white text-sm truncate"><?= htmlspecialchars($nextLesson['titre']) ?></div>
                        </div>
                    </a>
                <?php else: ?>
                    <a href="/academy/course.php?slug=<?= urlencode($course['slug']) ?>" class="group bento-card border-emerald-500/20 bg-emerald-500/5 p-6 flex items-center gap-4 text-right hover:-translate-y-1 transition-all flex-row-reverse">
                        <div class="w-12 h-12 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-400 shrink-0">
                            🏁
                        </div>
                        <div class="min-w-0">
                            <div class="text-[10px] font-black text-emerald-500/70 uppercase tracking-widest mb-1.5">Fin du cours</div>
                            <div class="font-bold text-emerald-400 text-sm">Retour au sommaire</div>
                        </div>
                    </a>
                <?php endif; ?>
            </div>

        </main>

        <!-- ── SIDEBAR (Droite) ── -->
        <aside class="lg:col-span-4 w-full flex flex-col gap-6 sticky top-28">

            <!-- Progression block -->
            <div class="bento-card p-3 rounded-[1rem]">
                <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mb-6 flex items-center gap-2">
                    <span>Global</span>
                    <div class="flex-1 h-[1px] bg-white/5 ml-2"></div>
                </h3>
                
                <div class="flex justify-between items-end mb-3">
                    <div class="text-[10px] font-black uppercase tracking-widest text-slate-400">Progression</div>
                    <div class="text-3xl font-heading font-black text-wari-gold"><?= $progress ?>%</div>
                </div>
                
                <div class="h-2 w-full bg-slate-800 rounded-full overflow-hidden mb-4">
                    <div class="h-full bg-gradient-to-r from-wari-goldDark to-wari-gold rounded-full transition-all duration-1000 relative" style="width:<?= $progress ?>%">
                        <div class="absolute inset-0 bg-white/20 animate-pulse"></div>
                    </div>
                </div>
                <div class="text-[10px] font-bold uppercase tracking-widest text-slate-500 text-center">
                    <?= $doneLecons ?> / <?= $totalLecons ?> terminées
                </div>
            </div>

            <!-- List of lessons -->
            <div class="bento-card rounded-[1rem] overflow-hidden flex flex-col max-h-[600px]">
                <div class="p-3 border-b border-white/5 shrink-0 bg-slate-900/40">
                    <h3 class="text-[10px] font-black text-white uppercase tracking-[0.3em] flex items-center gap-2 mb-1">
                        <span>Plan d'action</span>
                    </h3>
                    <div class="text-xs text-slate-500 font-medium"><?= htmlspecialchars($course['titre']) ?></div>
                </div>
                
                <!-- Scrollable list -->
                <div class="overflow-y-auto overflow-x-hidden flex-1 divide-y divide-white/5 custom-scrollbar">
                    <?php foreach ($lessons as $i => $l): ?>
                        <?php
                        $isActive = $l['id'] === $lesson_id;
                        $isDone   = $l['complete'];
                        ?>
                        <a href="/academy/lesson.php?id=<?= $l['id'] ?>" class="group p-4 flex items-center gap-3 transition-colors <?= $isActive ? 'bg-slate-800/60 border-l-4 border-[var(--cat-color)]' : 'hover:bg-slate-800/30 border-l-4 border-transparent' ?>">
                            
                            <!-- Icon/Number -->
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black shrink-0 transition-colors <?= $isDone ? 'bg-emerald-500/10 text-emerald-400' : ($isActive ? 'bg-[var(--cat-color)] text-slate-900' : 'bg-slate-800 text-slate-500 group-hover:bg-slate-700') ?>">
                                <?= $isDone ? '✓' : str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?>
                            </div>
                            
                            <!-- title -->
                            <div class="text-sm font-semibold line-clamp-2 <?= $isDone ? 'text-slate-500 line-through decoration-slate-600/50' : ($isActive ? 'text-white' : 'text-slate-300 group-hover:text-white') ?>">
                                <?= htmlspecialchars($l['titre']) ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <a href="/academy/course.php?slug=<?= urlencode($course['slug']) ?>" class="w-full text-center p-4 rounded-xl border border-white/10 text-slate-400 hover:text-white hover:bg-white/5 transition-colors text-xs font-bold uppercase tracking-widest">
                ← Quitter la leçon
            </a>

        </aside>

    </div>

    <!-- Scrollbar pour la sidebar -->
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: rgba(15, 23, 42, 0.5); }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.1); border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.2); }
    </style>

</body>
</html>
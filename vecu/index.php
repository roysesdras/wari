<?php
require_once __DIR__ . '/../config/db.php';

// Récupération de tous les articles, du plus récent au plus ancien
try {
    $stmt = $pdo->query("SELECT * FROM wari_articles ORDER BY date_publication DESC");
    $articles = $stmt->fetchAll();
} catch (PDOException $e) {
    $articles = []; // En cas d'erreur, on affiche une liste vide
}

// Fonction pour estimer le temps de lecture
function tempsLecture($texte) {
    $motsParMinute = 200;
    $nombreDeMots = str_word_count(strip_tags($texte));
    $temps = ceil($nombreDeMots / $motsParMinute);
    return $temps > 0 ? $temps : 1;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wari Vécu | Journal de Discipline</title>
    <meta name="description" content="Le journal de bord d'un entrepreneur. Une publication mensuelle sur la gestion par enveloppes, la discipline financière et la quête de liberté.">
    <meta name="keywords" content="Wari-Finance, discipline financière, gestion enveloppes, épargne Afrique, finances personnelles, Esdras, Cotonou, EdTech">
    <meta name="author" content="Esdras - RebOnly">
    <link rel="canonical" href="https://wari.digiroys.com/vecu/">

    <meta property="og:type" content="website">
    <meta property="og:url" content="https://wari.digiroys.com/vecu/">
    <meta property="og:title" content="Wari Vécu | Journal de Discipline & Souveraineté">
    <meta property="og:description" content="Quitter la pauvreté par la discipline. Suivez mon parcours mensuel de gestion financière réelle.">
    <meta property="og:image" content="https://i.postimg.cc/pV3tGx6V/bigstock-Discipline-55020278.jpg">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Wari Vécu | Journal de Discipline">
    <meta name="twitter:description" content="Pas un blog, mais un récit de combat pour la liberté financière.">
    <meta name="twitter:image" content="https://i.postimg.cc/pV3tGx6V/bigstock-Discipline-55020278.jpg">

    <link rel="icon" type="image/png" href="https://wari.digiroys.com/assets/warifinance3d.png" />
    <link rel="apple-touch-icon" href="https://wari.digiroys.com/assets/warifinance3d.png">
    <meta name="theme-color" content="#020617">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #020617; }
        .font-serif { font-family: 'Cormorant Garamond', serif; }
        .selection-gold::selection { background: rgba(234, 178, 8, 0.3); }
        .fade-in { animation: fadeIn 1s ease-out forwards; opacity: 0; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="text-slate-200 selection-gold">

    <header class="pt-5 pb-20 px-6 fade-in">
        <div class="max-w-3xl mx-auto">
            <h1 class="text-6xl md:text-8xl font-black tracking-tighter text-white mb-8">
                Wari <span class="text-transparent bg-clip-text bg-gradient-to-b from-amber-200 to-amber-600">Vécu</span>
            </h1>
            <div class="relative">
                <div class="absolute -left-4 top-0 bottom-0 w-1 bg-amber-500/20 rounded-full"></div>
                <p class="text-xl md:text-2xl leading-relaxed text-slate-400 font-serif italic pl-6">
                    "Ceci n'est pas un blog. Je publie une fois par mois, quand j'ai quelque chose de vrai à raconter. 
                    Mes défis d’utilisation. Quatre enveloppes. <span class="text-white font-medium not-italic">Un seul objectif : quitter la pauvreté par la discipline.</span>"
                </p>
            </div>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 pb-10">
        <div class="space-y-20">
            
            <?php if (empty($articles)): ?>
                <p class="text-center text-slate-500 italic py-20 border border-dashed border-white/5 rounded-2xl">
                    Le carnet est encore vide. La discipline commence bientôt.
                </p>
            <?php else: ?>
                <?php 
                    // 1. On prépare le formateur de date (une seule fois avant la boucle)
                    $formatter = new IntlDateFormatter(
                        'fr_FR', 
                        IntlDateFormatter::LONG, 
                        IntlDateFormatter::NONE,
                        null,
                        null,
                        'MMMM yyyy'
                    );

                    foreach ($articles as $index => $article): 
                        $delay = ($index + 1) * 0.2;
                        $dateObj = strtotime($article['date_publication']);
                        
                        // 2. On utilise le formateur au lieu de strftime
                        // ucfirst permet de mettre la majuscule (ex: Avril 2026)
                        $moisAnnee = ucfirst($formatter->format($dateObj)); 
                ?>
                <article class="group relative fade-in" style="animation-delay: <?php echo $delay; ?>s;">
                    <a href="article.php?s=<?php echo $article['slug']; ?>" class="flex flex-col md:flex-row md:items-start gap-6 md:gap-12">
                        <div class="md:w-32 shrink-0 pt-2">
                            <p class="text-[10px] font-black text-amber-500 uppercase tracking-[0.3em] mb-1">
                                <?php echo htmlspecialchars($article['mois_compteur']); ?>
                            </p>
                            <p class="text-xs font-bold text-slate-600 uppercase tracking-widest">
                                <?php 
                                    $formatterSide = new IntlDateFormatter('fr_FR', IntlDateFormatter::SHORT, IntlDateFormatter::NONE, null, null, 'MMM yyyy');
                                    echo $formatterSide->format($dateObj); 
                                ?>
                            </p>
                        </div>

                        <div class="flex-1">
                            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4 group-hover:text-amber-400 transition-colors duration-500 leading-tight">
                                <?php echo htmlspecialchars($article['titre']); ?>
                            </h2>
                            <p class="text-lg text-slate-400 leading-relaxed mb-6 line-clamp-3 font-light">
                                <?php echo htmlspecialchars(strip_tags($article['resume'])); ?>
                            </p>

                            <div class="flex items-center gap-6 text-slate-500">
                                <div class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest whitespace-nowrap">
                                    <span><?php echo tempsLecture($article['contenu']); ?> min de lecture</span>
                                </div>
                                <div class="h-px flex-1 bg-white/5"></div>
                                <div class="flex items-center gap-1 text-[10px] font-black text-amber-500 uppercase tracking-widest group-hover:translate-x-2 transition-all">
                                    <span>Lire le récit</span>
                                    <span>→</span>
                                </div>
                            </div>
                        </div>
                    </a>
                </article>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </main>

    <footer class="bg-slate-900/30 border-t border-white/5 py-5 px-4 mt-10">
        <div class="max-w-xl mx-auto text-center">
            <h3 class="text-3xl font-bold text-white mb-4 italic font-serif">Rejoindre la discipline</h3>
            <p class="text-slate-400 mb-10 text-lg font-light">
                Pas de mails que personne ne lit. Reçois le récit mensuel <span class="text-green-500 font-bold">directement sur WhatsApp</span>.
            </p>
            
            <?php include 'assets/form.php'; ?>
            
            <p class="mt-8 text-[10px] text-slate-600 uppercase tracking-widest font-bold">
                Discipline financière • Souveraineté • Vrai Vécu
            </p>
        </div>
    </footer>

</body>
</html>
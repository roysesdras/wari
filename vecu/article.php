<?php
// 1. Connexion (Vérifie le chemin selon l'emplacement de ton fichier)
require_once '../config/db.php'; 
require_once __DIR__ . '/../classes/Vecu.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$user_id = $_SESSION['user_id'] ?? null;

// Tracking du clic sur notification push
if (isset($_GET['push_log_id'])) {
    try {
        $pushLogId = (int)$_GET['push_log_id'];
        $stmtClick = $pdo->prepare("UPDATE wari_push_logs SET click_count = click_count + 1 WHERE id = ?");
        $stmtClick->execute([$pushLogId]);
    } catch (Exception $e) {
        error_log("Erreur tracking clic push vecu : " . $e->getMessage());
    }
}

// 2. Sécurisation du slug
$slug = filter_input(INPUT_GET, 's', FILTER_SANITIZE_SPECIAL_CHARS);

if (!$slug) {
    header('Location: index.php');
    exit;
}

// 3. Récupération de l'article
try {
    $query = $pdo->prepare("SELECT * FROM wari_articles WHERE slug = :slug LIMIT 1");
    $query->execute(['slug' => $slug]);
    $article = $query->fetch();

    if (!$article) {
        header('Location: index.php');
        exit;
    }

    // Marquer comme lu si connecté
    if ($user_id) {
        $vecu = new Vecu($pdo);
        $vecu->markAsRead($user_id, $article['id']);
    }
} catch (PDOException $e) {
    die("Erreur lors de la récupération du récit.");
}

// 4. Formatage de la date (Version Moderne)
$formatter = new IntlDateFormatter('fr_FR', IntlDateFormatter::LONG, IntlDateFormatter::NONE, null, null, 'dd MMMM yyyy');
$date_formattee = $formatter->format(strtotime($article['date_publication']));


// Préparation des variables SEO ===================================================================
$titre_page = htmlspecialchars($article['titre']) . " | Wari Vécu";
$description_page = htmlspecialchars(mb_substr(strip_tags($article['resume']), 0, 160));
$url_actuelle = "https://wari.digiroys.com/vecu/article.php?s=" . $article['slug'];

// Logique pour l'image de partage (OG Image)
$chemin_physique = __DIR__ . '/uploads/' . $article['image_url'];

// 2. On définit l'URL publique pour le navigateur et les réseaux sociaux
// Ton URL de base semble être wari.digiroys.com
$url_publique = "https://wari.digiroys.com/vecu/uploads/" . htmlspecialchars($article['image_url']);

if (!empty($article['image_url']) && file_exists($chemin_physique)) {
    $og_image = $url_publique;
} else {
    // Image de secours si le fichier n'existe pas physiquement
    $og_image = "https://i.postimg.cc/P5wJ32yf/vict.jpg";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?php echo $titre_page; ?></title>
    <meta name="description" content="<?php echo $description_page; ?>">
    <meta name="author" content="Esdras - Wari Vécu">
    <link rel="canonical" href="<?php echo $url_actuelle; ?>">

    <meta property="og:type" content="article">
    <meta property="og:url" content="<?php echo $url_actuelle; ?>">
    <meta property="og:title" content="<?php echo $titre_page; ?>">
    <meta property="og:description" content="<?php echo $description_page; ?>">
    <meta property="og:image" content="<?php echo $og_image; ?>">
    <meta property="og:site_name" content="Wari Vécu">
    <meta property="article:published_time" content="<?php echo $article['date_publication']; ?>">
    <meta property="fb:app_id" content="967910445637901" />

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $titre_page; ?>">
    <meta name="twitter:description" content="<?php echo $description_page; ?>">
    <meta name="twitter:image" content="<?php echo $og_image; ?>">

    <link rel="icon" type="image/png" href="https://wari.digiroys.com/assets/warifinance3d.png" />
    <link rel="apple-touch-icon" href="https://wari.digiroys.com/assets/warifinance3d.png">
    <meta name="theme-color" content="#020617">

    <meta name="robots" content="index, follow">
    
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Quicksand', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
    
    <style>
        body { font-family: 'Quicksand', sans-serif; }

        .prose .ql-editor p,
        .prose .ql-editor div,
        .prose p, 
        .prose div {
            margin-top: 0 !important;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }

        .prose p:has(> br:only-child),
        .prose div:has(> br:only-child) {
            height: 0;
            line-height: 0;
            margin: 0 !important;
            overflow: hidden;
        }
        .prose p:empty {
        display: none;
        }

        .prose p,
        .prose div {
            margin-top: 0 !important;
            margin-bottom: 1.5rem;
            line-height: inherit !important; 
        }

        .prose p:has(> br:only-child),
        .prose div:has(> br:only-child) {
            height: 0 !important;
            line-height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
        }
        /* Fallback de sécurité pour les éléments vraiment vides */
        .prose p:empty, .prose div:empty {
        display: none !important;
        }

        /* --- 2. GESTION DES TITRES H2 ET H3 (Nouveau !) --- */
        .prose h2,
        .prose h3 {
            margin-bottom: 0 !important;
            margin-top: 1em !important; 
            line-height: 1.2 !important;
            padding-bottom: 0 !important;
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-200 min-h-screen selection:bg-[#D4AF37] selection:text-slate-900">

    <main class="max-w-2xl mx-auto py-6 px-3">
        <a href="https://wari.digiroys.com" class="inline-flex items-center gap-2 text-slate-500 hover:text-white transition-colors text-xs font-bold uppercase tracking-widest mb-10">
            ← Dashboard
        </a>
        
        <header class="mb-8 text-left md:text-left">
            <p class="text-xs text-[#D4AF37] font-bold tracking-[0.2em] uppercase mb-3">
                <?php echo htmlspecialchars($article['mois_compteur']); ?> • <?php echo $date_formattee; ?>
            </p>
            <h1 class="text-3xl md:text-5xl font-bold text-white leading-tight tracking-tight">
                <?php echo htmlspecialchars($article['titre']); ?>
            </h1>
        </header>

        <?php if (!empty($article['image_url'])): ?>
        <figure class="mb-8">
            <img src="uploads/<?php echo htmlspecialchars($article['image_url']); ?>" 
                 alt="Preuve terrain" 
                 class="rounded-2xl border border-[#D4AF37]/20 w-full object-cover shadow-2xl grayscale hover:grayscale-0 transition-all duration-700 ease-in-out">
            <?php if(!empty($article['resume'])): ?>
                <figcaption class="text-xs text-slate-500 mt-4 italic text-center px-4">
                    "<?php echo htmlspecialchars($article['resume']); ?>"
                </figcaption>
            <?php endif; ?>
        </figure>
        <?php endif; ?>

        <!-- J'ai retiré 'prose-p:my-0' et 'prose-headings:my-3' -->
        <!-- car c'est le fichier CSS qui va gérer ça maintenant -->
        <section class="prose prose-invert prose-lg max-w-none text-slate-400 transition-colors
                    leading-normal
                    prose-ul:my-2 prose-li:my-0
                    prose-headings:text-white
                    prose-strong:text-[#D4AF37]
                    prose-blockquote:border-[#D4AF37]
                    prose-a:text-[#D4AF37]
                    hover:prose-a:text-white">

                    <?php echo $article['contenu']; ?>
        </section>

        <div class="max-w-2xl mx-auto pt-8">
            <a href="index.php" class="text-[#D4AF37] text-sm font-bold flex justify-end items-center gap-2 hover:translate-x-[4px] transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>  
                <span>Retour au journal</span>
            </a>
        </div>

        <footer class="mt-5 pt-10 border-t border-[#D4AF37]/10 flex flex-col sm:flex-row justify-between gap-4">
            <div class="text-sm text-slate-500 italic">
                Récit par <span class="text-slate-300 font-medium italic">Esdras</span>
                
            </div>

            <div class="flex items-center gap-2">
                <span class="h-px w-4 bg-[#D4AF37]/30"></span>
                <p class="text-[#D4AF37] font-bold text-xs tracking-widest uppercase">
                    La discipline libère
                </p>
            </div>  
        </footer>
    </main>

    <section class="bg-gradient-to-b from-slate-900 to-slate-950 border-t border-[#D4AF37]/20 py-12 px-2 mt-2">
        <div class="max-w-2xl mx-auto normal">
            <h3 class="text-2xl md:text-3xl text-white font-black mb-4 uppercase tracking-wide">
                Fini de lire. <span class="text-[#D4AF37]">À toi de jouer.</span>
            </h3>
            <p class="text-slate-400 text-base md:text-lg mb-8 leading-relaxed">
                Lire mon histoire ne changera pas ton compte bancaire. Appliquer la discipline stricte des 4 enveloppes, <span class="text-white font-bold">oui</span>.<br>
                Wari-Finance est l'outil exact que j'utilise pour dompter mon argent chaque mois.
            </p>
            
            <a href="https://wari.digiroys.com/config/auth.php?utm_source=vecu_article&utm_medium=cta_bottom" 
               class="inline-block bg-[#D4AF37] text-slate-950 font-black px-2 py-2 rounded-xl uppercase tracking-widest hover:bg-white hover:scale-105 transition-all duration-300 shadow-[0_0_20px_rgba(212,175,55,0.2)]">
                Démarrer ma gestion sur Wari
            </a>
            
            <p class="mt-6 text-xs text-slate-500 font-medium uppercase tracking-widest">
                Méthode 100% Gratuite • Sans carte bancaire
            </p>
        </div>
    </section>

</body>
</html>
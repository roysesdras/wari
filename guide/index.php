<?php
require_once '../config/db.php';
require_once '../config/session_check.php';

$is_premium = $_SESSION['is_premium'] ?? false;
$userEmail = $_SESSION['user_email'] ?? 'Utilisateur';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Guide d'utilisation - Wari Finance</title>
    
    <!-- Open Graph (Facebook, WhatsApp, LinkedIn) -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Le Guide Officiel - Wari Finance">
    <meta property="og:description" content="Découvre la méthode Wari : Coffre-fort, Train de vie, et la règle des 4 enveloppes pour maîtriser tes finances.">
    <meta property="og:url" content="https://wari.digiroys.com/guide/">
    <meta property="og:image" content="https://wari.digiroys.com/assets/wari_og_1.png">
    <meta property="og:locale" content="fr_FR">

    <link rel="icon" type="image/png" href="../assets/warifinance3d.png" />
    <link rel="apple-touch-icon" href="../assets/warifinance3d.png">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    
    <style>
        /* Base Variables (Wari Theme) */
        :root {
            --bg: #0e0f11;
            --surface: #161719;
            --surface-hover: #1c1e21;
            --border: #232428;
            --text: #f0efe8;
            --muted: #6b6a65;
            --accent: #f59e0b;
            --accent2: #2a2b2f;
            --danger: #ef4444;
            --success: #10b981;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Quicksand', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        /* Navbar minimaliste */
        .navbar {
            position: fixed;
            top: 0; width: 100%;
            background: rgba(14, 15, 17, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 15px 20px;
            display: flex;
            align-items: center;
            z-index: 100;
        }

        .back-btn {
            color: var(--muted);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            transition: color 0.2s;
        }

        .back-btn:hover { color: var(--text); }
        
        .nav-title {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            font-family: 'Quicksand', sans-serif;
            font-weight: 800;
            font-size: 20px;
            color: var(--accent);
            letter-spacing: 0.5px;
        }

        /* Container du guide */
        .guide-container {
            max-width: 800px;
            margin: 80px auto 40px;
            padding: 0 20px;
            animation: fadeUp 0.5s ease;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .hero {
            text-align: center;
            padding: 40px 0;
            border-bottom: 1px solid var(--border);
            margin-bottom: 40px;
        }

        .hero h1 {
            font-family: 'Quicksand', sans-serif;
            font-weight: 800;
            font-size: 42px;
            color: var(--text);
            margin-bottom: 10px;
        }

        .hero p {
            color: var(--muted);
            font-size: 16px;
            max-width: 500px;
            margin: 0 auto;
        }

        /* Sections conceptuelles */
        .concept-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .concept-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 4px; height: 100%;
            background: var(--accent);
        }

        .concept-card.danger::before { background: var(--danger); }
        .concept-card.success::before { background: var(--success); }
        .concept-card.premium::before { background: linear-gradient(180deg, #f59e0b, #d97706); }

        .concept-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 48px; height: 48px;
            border-radius: 12px;
            background: var(--accent2);
            color: var(--accent);
            font-size: 24px;
            margin-bottom: 20px;
        }

        .concept-card.danger .concept-icon { background: rgba(239, 68, 68, 0.1); color: var(--danger); }
        .concept-card.success .concept-icon { background: rgba(16, 185, 129, 0.1); color: var(--success); }

        .concept-title {
            font-family: 'Quicksand', sans-serif;
            font-weight: 800;
            font-size: 28px;
            margin-bottom: 15px;
            color: var(--text);
        }

        .concept-text {
            color: #a1a1aa; /* Gris clair textuel */
            font-size: 15px;
            margin-bottom: 20px;
        }

        .concept-rule {
            background: #0e0f11;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 15px;
            font-size: 14px;
            color: var(--text);
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }

        .concept-rule i { color: var(--accent); margin-top: 2px; }

        /* Premium Badge */
        .premium-badge {
            position: absolute;
            top: 20px; right: 20px;
            background: rgba(245, 158, 11, 0.15);
            color: var(--accent);
            border: 1px solid rgba(245, 158, 11, 0.3);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 40px 0;
            color: var(--muted);
            font-size: 13px;
        }

        .cta-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--text);
            color: var(--bg);
            padding: 12px 24px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 500;
            margin-top: 30px;
            transition: opacity 0.2s;
        }

        .cta-btn:hover { opacity: 0.9; }

        @media (max-width: 600px) {
            .hero h1 { font-size: 32px; }
            .concept-card { padding: 20px; }
            .concept-title { font-size: 24px; }
            .nav-title { display: none; }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="https://wari.digiroys.com/" class="back-btn">
            <i class="ri-arrow-left-line"></i> Retour au tableau de bord
        </a>
        <div class="nav-title">Wari Guide</div>
    </nav>

    <div class="guide-container">
        
        <div class="hero">
            <h1>Le Manuel de la Discipline</h1>
            <p>Bienvenue sur Wari Finance. Ce n'est pas qu'une simple application, c'est une philosophie de vie pour reprendre le contrôle total de tes finances.</p>
        </div>

        <!-- 1. La Répartition -->
        <div class="concept-card" id="repartition">
            <div class="concept-icon"><i class="ri-pie-chart-line"></i></div>
            <h2 class="concept-title">La Règle d'Or (4 envellopes)</h2>
            <p class="concept-text">Wari repose sur un principe fondamental : dès qu'une somme d'argent entre dans tes poches, elle doit être divisée avant même que tu n'y touches.</p>
            <p class="concept-text">Tu as le choix de ta répartition : <strong>40% Train de vie, 30% Projet, 20% Épargne, 10% Imprévu</strong> (ou autre selon tes objectifs). L'application s'occupe de diviser automatiquement tes revenus à chaque fois que tu fais une répartition.</p>
            <div class="concept-rule">
                <i class="ri-lightbulb-flash-line"></i>
                <div><strong>L'état d'esprit :</strong> N'épargne pas ce qu'il reste après avoir dépensé, mais dépense ce qu'il reste après avoir épargné.</div>
            </div>
        </div>

        <!-- Le Projet (Liberté d'action) -->
        <div class="concept-card success" id="projet">
            <div class="concept-icon"><i class="ri-flag-2-line"></i></div>
            <h2 class="concept-title">Le Projet (Objectif Capital)</h2>
            <p class="concept-text">Wari te permet de définir un grand objectif financier (ex: Acheter une voiture, Payer un terrain, Créer une entreprise).</p>
            <p class="concept-text">Chaque fois que tu fais une répartition, tu peux allouer une partie à ce projet spécifique. La barre de progression t'indique où tu en es. C'est ta liberté d'action qui grandit pas à pas.</p>
            <div class="concept-rule">
                <i class="ri-rocket-line"></i>
                <div><strong>L'état d'esprit :</strong> Un objectif écrit est un rêve avec une date d'échéance. Voir la jauge monter est la meilleure des motivations.</div>
            </div>
        </div>

        <!-- 2. Le Coffre-Fort -->
        <div class="concept-card success" id="coffre-fort">
            <div class="concept-icon"><i class="ri-safe-2-line"></i></div>
            <h2 class="concept-title">Le Coffre-Fort (Épargne Intouchable)</h2>
            <p class="concept-text">Le Coffre-Fort est l'endroit sacré où réside ton épargne. Contrairement à d'autres applications, Wari ne te permet pas de déduire une dépense directement depuis ton épargne.</p>
            <p class="concept-text">L'argent qui entre dans le Coffre-Fort y reste, afin de financer tes investissements futurs ou tes fonds d'urgence projets. C'est l'indicateur principal de ton enrichissement réel.</p>
            <div class="concept-rule">
                <i class="ri-lock-line"></i>
                <div><strong>L'état d'esprit :</strong> Ce qui est dans le coffre est verrouillé pour ton toi du futur. Ton moi du présent n'y a pas accès pour les plaisirs éphémères.</div>
            </div>
        </div>

        <!-- 3. Train de vie à 0 -->
        <div class="concept-card danger" id="train-de-vie">
            <div class="concept-icon"><i class="ri-wallet-3-line"></i></div>
            <h2 class="concept-title">Le "Train de Vie" à Zéro</h2>
            <p class="concept-text">Le "Train de Vie" représente l'argent liquide ou le solde mobile money que tu as <strong>le droit</strong> d'utiliser au quotidien pour tes besoins et tes envies.</p>
            <p class="concept-text">La magie de Wari réside dans la discipline : chaque fois que tu achètes quelque chose, tu dois l'enregistrer dans "Ajouter Dépense". L'objectif ultime est que ce solde atteigne rigoureusement <strong>0 FCFA</strong> juste avant ton prochain revenu.</p>
            <div class="concept-rule">
                <i class="ri-focus-3-line"></i>
                <div><strong>L'état d'esprit :</strong> Un train de vie à 0 ne veut pas dire que tu es pauvre, cela veut dire que tu as parfaitement respecté ton budget et sauvé ton épargne !</div>
            </div>
        </div>

        <!-- L'imprévu -->
        <div class="concept-card" id="imprevu" style="border-left-color: #3b82f6;">
            <div class="concept-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;"><i class="ri-lifebuoy-line"></i></div>
            <h2 class="concept-title">Le Fonds d'Imprévus</h2>
            <p class="concept-text">Parfois, il arrive des imprévus : un pneu crevé, une maladie soudaine... Ton Train de Vie intègre cette poche d'imprévus (dans "Poche (Dispo)").</p>
            <p class="concept-text">Il est recommandé de toujours allouer un petit pourcentage de tes entrées aux imprévus. Ainsi, lorsqu'un problème survient, tu n'as pas besoin de toucher à ton Coffre-Fort ou de t'endetter.</p>
        </div>

        <!-- 4. La Dette -->
        <div class="concept-card danger" id="dette">
            <div class="concept-icon"><i class="ri-error-warning-line"></i></div>
            <h2 class="concept-title">La Dette (Solde Négatif)</h2>
            <p class="concept-text">Que se passe-t-il si tu dépenses plus que ce que tu as dans ton Train de Vie ? Le système Wari est impitoyable : il te plongera dans le négatif (ex: <strong>- 5 000 F</strong>).</p>
            <p class="concept-text">Ceci est une <strong>dette envers toi-même</strong>. Lors de ton prochain revenu (répartition), l'application prélèvera automatiquement ces 5 000 F de ton nouveau Train de Vie pour rembourser le "trou" financier que tu as causé, avant de te donner le reste.</p>
            <div class="concept-rule">
                <i class="ri-scales-line"></i>
                <div><strong>L'état d'esprit :</strong> Chaque excès d'aujourd'hui est un impôt direct sur ton confort de demain. La dette n'est jamais pardonnée, elle est toujours remboursée.</div>
            </div>
        </div>

        <!-- Le Carnet de Dettes -->
        <div class="concept-card" id="carnet-dettes" style="border-left-color: #f43f5e;">
            <div class="concept-icon" style="background: rgba(244, 63, 94, 0.1); color: #f43f5e;"><i class="ri-contacts-book-2-line"></i></div>
            <h2 class="concept-title">Le Carnet de Dettes</h2>
            <p class="concept-text">Au-delà de ta gestion personnelle, il arrive de prêter de l'argent à un proche ou, à l'inverse, d'en emprunter. Le Carnet de Dettes te permet de consigner scrupuleusement ces transactions extérieures.</p>
            <p class="concept-text">Il est vivement conseillé de toujours noter ce que tu prêtes ou empruntes. Cela évite les oublis frustrants, préserve tes relations sociales, et t'assure que cet argent retourne bien à sa place dans ton système financier.</p>
        </div>

        <!-- Wari Vécu (Standard) -->
        <div class="concept-card" id="vecu" style="border-left-color: #0ea5e9;">
            <div class="concept-icon" style="background: rgba(14, 165, 233, 0.1); color: #0ea5e9;"><i class="ri-book-open-line"></i></div>
            <h2 class="concept-title">Le Wari Vécu</h2>
            <p class="concept-text">L'éducation financière n'est pas qu'une question de chiffres, c'est aussi de l'expérience humaine. "Le Vécu" est une section où tu découvriras des histoires vraies, des leçons et des partages d'expérience d'autres utilisateurs.</p>
            <p class="concept-text">Apprends des erreurs et des succès des autres pour ne pas les répéter, et fortifie ta psychologie face à l'argent.</p>
            <a href="../vecu/" style="color: #0ea5e9; text-decoration: none; font-size: 14px; font-weight: 500;">Lire le Vécu →</a>
        </div>

        <!-- Section Premium -->
        <div class="hero" style="border: none; margin-bottom: 20px; padding-bottom: 0; padding-top: 20px;">
            <h1 style="font-size: 32px; color: var(--accent);">Les Fonctionnalités Premium</h1>
            <p>Débloque la pleine puissance de ton coach financier.</p>
        </div>

        <div class="concept-card premium" id="coach-ia">
            <span class="premium-badge">Premium</span>
            <div class="concept-icon"><i class="ri-robot-2-line"></i></div>
            <h2 class="concept-title">Le Coach IA Financier</h2>
            <p class="concept-text">L'IA de Wari n'est pas un simple robot de discussion. C'est un analyste financier privé qui a accès à l'historique complet de tes dépenses et de tes entrées.</p>
            <p class="concept-text">Il peut détecter tes mauvaises habitudes ("Tu dépenses trop le week-end"), te féliciter sur tes prouesses, et te fournir des plans de redressement personnalisés si tu finis tes fins de mois dans le rouge.</p>
            <a href="../coach/" style="color: var(--accent); text-decoration: none; font-size: 14px; font-weight: 500;">Consulter ton Coach →</a>
        </div>

        <div class="concept-card premium" id="defis">
            <span class="premium-badge">Premium</span>
            <div class="concept-icon" style="color: #6366f1; background: rgba(99, 102, 241, 0.1);"><i class="ri-trophy-line"></i></div>
            <h2 class="concept-title">Défis d'Épargne</h2>
            <p class="concept-text">L'épargne ne doit pas être une corvée ennuyeuse. Wari te propose des défis interactifs et ludiques pour booster ta discipline financière (ex: 52 Semaines, Défi du mois, etc).</p>
            <p class="concept-text">Chaque défi réussi renforce ton capital tout en te donnant un sentiment d'accomplissement incomparable.</p>
        </div>

        <div class="concept-card premium" id="academy">
            <span class="premium-badge">Premium</span>
            <div class="concept-icon"><i class="ri-graduation-cap-line"></i></div>
            <h2 class="concept-title">La Wari Academy</h2>
            <p class="concept-text">La discipline sans éducation mène à la stagnation. L'Academy est ton université de la richesse : tu y trouveras des cours exclusifs, des stratégies d'investissement, et des Masterclass rédigées par des experts.</p>
            <p class="concept-text">Ton abonnement Premium te donne un accès illimité à tous les modules éducatifs et aux téléchargements PDF des cours.</p>
            <a href="../academy/" style="color: var(--accent); text-decoration: none; font-size: 14px; font-weight: 500;">Ouvrir l'Academy →</a>
        </div>

        <div class="concept-card premium" id="export">
            <span class="premium-badge">Premium</span>
            <div class="concept-icon"><i class="ri-file-pdf-2-line"></i></div>
            <h2 class="concept-title">Rapports & Exports PDF</h2>
            <p class="concept-text">Les finances professionnelles exigent une comptabilité irréprochable. L'accès Premium te permet de générer en un clic des relevés PDF officiels de ton activité sur Wari (Mensuels ou Annuels).</p>
            <p class="concept-text">Ces documents sont parfaits pour avoir une vue globale sur la santé financière de ton année, ou pour justifier de ta gestion personnelle.</p>
        </div>

        <div class="footer">
            <a href="https://wari.digiroys.com/" class="cta-btn">J'ai compris, retour au tableau de bord</a>
            <div style="margin-top: 20px;">© <?php echo date('Y'); ?> Wari Finance.</div>
        </div>

    </div>

</body>
</html>

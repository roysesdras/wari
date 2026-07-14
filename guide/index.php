<?php
session_start();
require_once '../config/db.php';

$user_id = $_SESSION['user_id'] ?? null;
$is_premium = false;
$userEmail = $_SESSION['user_email'] ?? 'Utilisateur';

if ($user_id) {
    // Si connecté, on vérifie son statut Premium
    $stmt = $pdo->prepare("
        SELECT u.commande_id, l.date_expiration 
        FROM wari_users u
        LEFT JOIN wari_licences l ON l.commande_id = u.commande_id
        WHERE u.id = ?
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $lic = $stmt->fetch();
    if ($lic) {
        $date_expiration = $lic['date_expiration'];
        $stmtPay = $pdo->prepare("SELECT id FROM wari_payments WHERE commande_id = ? AND reference_fedapay IS NOT NULL AND reference_fedapay != '' AND statut = 'approved' LIMIT 1");
        $stmtPay->execute([$lic['commande_id']]);
        $has_paid = (bool)$stmtPay->fetch();
        $is_premium = ($date_expiration !== null && strtotime($date_expiration) >= time() && $has_paid);
    }
}
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
            .concept-card { padding: 8px; }
            .concept-title { font-size: 24px; }
            .nav-title { display: none; }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <?php if ($user_id): ?>
        <a href="https://wari.digiroys.com/" class="back-btn">
            <i class="ri-arrow-left-line"></i> Retour au tableau de bord
        </a>
        <?php else: ?>
        <a href="https://wari.digiroys.com/config/auth.php" class="back-btn">
            <i class="ri-login-box-line"></i> Se connecter / S'inscrire
        </a>
        <?php endif; ?>
        <div class="nav-title">Wari Guide</div>
    </nav>

    <div class="guide-container">
        
        <div class="hero">
            <h1>Le Manuel de la Discipline</h1>
            <p><strong style="color: var(--accent);">IMPORTANT A SAVOIR :</strong> Wari n'est pas un compte bancaire ni un portefeuille électronique (comme Mobile Money), mais un simulateur et un outil de suivi visuel. Votre argent réel reste en totale sécurité dans vos propres poches ou sur vos comptes bancaires habituels.</p>
        </div>

        <!-- 1. La Répartition -->
        <div class="concept-card" id="repartition">
            <div class="concept-icon"><i class="ri-pie-chart-line"></i></div>
            <h2 class="concept-title">La Règle d'Or (4 enveloppes)</h2>
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
        <div class="concept-card" id="imprevu">
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
        <div class="concept-card" id="carnet-dettes">
            <div class="concept-icon" style="background: rgba(244, 63, 94, 0.1); color: #f43f5e;"><i class="ri-contacts-book-2-line"></i></div>
            <h2 class="concept-title">Le Carnet de Dettes</h2>
            <p class="concept-text">Au-delà de ta gestion personnelle, il arrive de prêter de l'argent à un proche ou, à l'inverse, d'en emprunter. Le Carnet de Dettes te permet de consigner scrupuleusement ces transactions extérieures.</p>
            <p class="concept-text">Il est vivement conseillé de toujours noter ce que tu prêtes ou empruntes. Cela évite les oublis frustrants, préserve tes relations sociales, et t'assure que cet argent retourne bien à sa place dans ton système financier.</p>
        </div>

        <!-- Wari Vécu (Standard) -->
        <div class="concept-card" id="vecu">
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

        <div class="concept-card premium" id="portefeuille-pro">
            <span class="premium-badge">Premium</span>
            <div class="concept-icon" style="color: #f59e0b; background: rgba(245, 158, 11, 0.1);"><i class="ri-briefcase-line"></i></div>
            <h2 class="concept-title">Le Portefeuille Professionnel (Pro Wallet)</h2>
            <p class="concept-text">La règle numéro un de tout entrepreneur ou commerçant à succès est la suivante : <strong>ne jamais mélanger son argent personnel avec la caisse de son entreprise</strong>.</p>
            <p class="concept-text">Le Portefeuille Pro de Wari te permet de gérer séparément les flux de ton activité commerciale, de ton projet agricole, ou de tes services de freelance, en appliquant un modèle d'enveloppes Pro rigoureux.</p>
            
            <p class="concept-text" style="font-weight: bold; color: var(--accent); margin-top: 15px;">Les 4 enveloppes professionnelles et leurs cibles :</p>
            <ul style="list-style-type: disc; margin-left: 20px; margin-bottom: 20px; font-size: 14px; color: #a1a1aa; line-height: 1.6;">
                <li><strong>Stock & Matériel (40%) :</strong> Ton outil de production. C'est l'enveloppe réservée à l'achat des marchandises, des matières premières, ou des équipements indispensables à tes ventes.</li>
                <li><strong>Bénéfice Réinvesti (30%) :</strong> L'enveloppe de croissance. C'est la part du revenu réservée à faire grandir ton commerce, financer ton développement futur ou consolider ta trésorerie de réserve.</li>
                <li><strong>Frais de Fonctionnement (20%) :</strong> Tes charges fixes. Loyer de la boutique, électricité, transport professionnel, connexion internet, outils de travail... Tout ce qui fait tourner ton business au jour le jour.</li>
                <li><strong>Marketing & Publicité (10%) :</strong> Ton levier de visibilité. C'est le budget dédié à te faire connaître (flyers, parrainages, publicités sponsorisées sur les réseaux sociaux).</li>
            </ul>

            <div class="concept-rule">
                <i class="ri-lightbulb-line"></i>
                <div><strong>Comment l'utiliser ?</strong> Dès qu'une recette (vente, contrat) entre dans ton activité, saisis-la dans le Portefeuille Pro. Wari la divise automatiquement. Quand tu fais une dépense pro (ex: acheter du stock), choisis l'enveloppe correspondante pour suivre tes soldes en temps réel.</div>
            </div>
        </div>

        <div class="concept-card premium" id="plan-snowball">
            <span class="premium-badge">Premium</span>
            <div class="concept-icon" style="color: #ef4444; background: rgba(239, 68, 68, 0.1);"><i class="ri-rocket-line"></i></div>
            <h2 class="concept-title">Le Planificateur Anti-Dette & Créances</h2>
            <p class="concept-text">Rembourser ses dettes au hasard en donnant des petites sommes à gauche et à droite est inefficace et fatigue mentalement. Wari utilise la méthode de la <strong>"Boule de Neige"</strong> pour vous libérer des dettes rapidement et simplement.</p>

            <p class="concept-text" style="font-weight: bold; color: var(--accent); margin-top: 15px;">La méthode de la Boule de Neige en français facile :</p>
            <p class="concept-text" style="font-size: 13.5px; color: #a1a1aa; line-height: 1.6;">
                Imaginez que <strong>Koffi</strong> ait trois dettes :
                <br>• 10 000 F à son frère Moussa.
                <br>• 30 000 F à sa tante Aminata.
                <br>• 100 000 F à la banque.
                <br><br>
                Koffi décide d'accorder <strong>20 000 F par mois</strong> pour rembourser tout ça.
                Au lieu de diviser ses 20 000 F entre tout le monde (ce qui ne rembourse personne rapidement), Wari lui conseille de rembourser la plus petite dette en premier (Moussa).
                <br><br>
                • <strong>Mois 1</strong> : Koffi paie Moussa en totalité (10 000 F). Moussa est remboursé ! Koffi ressent une victoire psychologique et a une dette en moins. Il lui reste 10 000 F qu'il envoie directement à sa tante Aminata.
                <br>• <strong>Mois 2</strong> : Koffi se concentre sur sa tante Aminata avec sa force maximale (20 000 F). Tante Aminata est soldée !
                <br>• <strong>Mois 3</strong> : Koffi attaque la banque avec ses 20 000 F. Sa force de remboursement grossit comme une boule de neige !
            </p>

            <p class="concept-text" style="font-weight: bold; color: var(--accent); margin-top: 15px;">Comment ça fonctionne dans Wari ?</p>
            <ul style="list-style-type: disc; margin-left: 20px; margin-bottom: 20px; font-size: 14px; color: #a1a1aa; line-height: 1.6;">
                <li><strong>Le Bilan Net :</strong> Wari compare l'argent que vous devez (<strong>Dettes en Rouge</strong>) et l'argent qu'on vous doit (<strong>Créances en Vert Émeraude</strong>). Si votre bilan net est positif, vous êtes virtuellement riche !</li>
                <li><strong>La liaison avec votre poche (Système Hybride) :</strong> Lorsque vous notez un remboursement de dette, Wari vous demande : <strong>"Depuis quelle enveloppe provient l'argent ?"</strong>. Si vous choisissez l'enveloppe <strong>Train de vie</strong>, Wari déduit l'argent de cette enveloppe. Ainsi, votre application affiche toujours exactement le même montant que l'argent réel qui reste dans votre poche.</li>
                <li><strong>Entrée d'argent des prêts :</strong> Si quelqu'un vous rembourse de l'argent (créance), Wari l'ajoute directement dans l'enveloppe de votre choix pour augmenter votre budget disponible.</li>
            </ul>

            <div class="concept-rule">
                <i class="ri-heart-pulse-line"></i>
                <div><strong>L'impact psychologique :</strong> Éliminer les dettes une par une, de la plus petite à la plus grande, donne une sensation de contrôle et de liberté immédiate.</div>
            </div>
        </div>

        <div class="concept-card premium" id="invest-uemoa">
            <span class="premium-badge">Premium</span>
            <div class="concept-icon" style="color: #fbbf24; background: rgba(251, 191, 36, 0.1);"><i class="ri-line-chart-line"></i></div>
            <h2 class="concept-title">Le Simulateur d'Investissement</h2>
            <p class="concept-text">Laisser son argent dormir sous son matelas ou sur son compte Wave/MoMo ordinaire te fait perdre de la richesse. Par exemple, si un sac de riz coûte 15 000 F aujourd'hui, le même sac coûtera peut-être 16 000 F l'année prochaine. Si ton argent n'a pas grandi, tu ne pourras plus acheter le sac entier. Le simulateur te montre comment ton argent peut travailler pour toi.</p>

            <p class="concept-text" style="font-weight: bold; color: var(--accent); margin-top: 15px;">Les trois choses à comprendre simplement :</p>
            <ul style="list-style-type: disc; margin-left: 20px; margin-bottom: 20px; font-size: 14px; color: #a1a1aa; line-height: 1.6;">
                <li><strong>Le Taux d'intérêt (Le cadeau de la banque) :</strong> C'est de l'argent gratuit que la banque te donne pour te remercier de laisser tes économies chez elle. Par exemple, si tu bloques 100 000 F à un taux de 5%, la banque te reverse 5 000 F de cadeau à la fin de l'année.</li>
                <li><strong>Les Intérêts Composés (Le cadeau qui fait des bébés) :</strong> La deuxième année, la banque ne calcule pas ton cadeau sur tes 100 000 F de départ, mais sur 105 000 F (ton argent de départ + le premier cadeau). Plus les années passent, plus tes cadeaux font d'autres bébés cadeaux, et ta cagnotte grandit toute seule.</li>
                <li><strong>Comment connaître le taux de ta banque ?</strong> Rends-toi dans une banque de ton quartier (comme BOA, Ecobank, Coris...) et demande : <strong>"Si je bloque de l'argent chez vous sans y toucher (Dépôt à Terme), quel est le pourcentage de cadeau (taux d'intérêt) que vous me donnez ?"</strong>. Tu n'as plus qu'à écrire ce chiffre dans le simulateur.</li>
            </ul>

            <p class="concept-text" style="font-weight: bold; color: var(--accent); margin-top: 15px;">Où placer ton argent chez nous (UEMOA) :</p>
            <ul style="list-style-type: disc; margin-left: 20px; margin-bottom: 20px; font-size: 14px; color: #a1a1aa; line-height: 1.6;">
                <li><strong>Épargne Mobile Money Spéciale (3% à 3.5% par an) :</strong> Attention, garder ton argent sur ton compte ordinaire Wave, Orange Money ou MTN MoMo rapporte <strong>zéro intérêt (0 F)</strong>. Pour gagner ces bonus, tu dois activer les options d'épargne spéciales (comme MoMo Kash de MTN, Celtiis Épargne, Orange Sini...).</li>
                <li><strong>Compte bloqué (DAT) en Microfinance (5.5% par an) :</strong> Tu bloques tes économies dans une agence de microfinance locale pendant 6 mois ou 1 an. C'est une excellente barrière pour t'empêcher de dépenser sur un coup de tête !</li>
                <li><strong>Prêter à l'État - Bons du Trésor (6.25% par an) :</strong> Tu prêtes tes économies aux pays de notre région (Bénin, Togo, Côte d'Ivoire...) pour les aider à construire des routes ou des écoles. L'État te rembourse avec un excellent bonus. C'est le placement le plus sûr.</li>
                <li><strong>Achat de parts d'entreprises - BRVM (8% par an en moyenne) :</strong> Tu achètes des petites parts de grandes entreprises de chez nous (comme la Sonatel ou Ecobank) à la bourse. C'est ce qui peut te rapporter le plus, mais la valeur peut monter ou descendre.</li>
            </ul>

            <div class="concept-rule">
                <i class="ri-lightbulb-line"></i>
                <div><strong>Rappel :</strong> Wari ne garde pas ton argent réel. Le simulateur sert à te donner le déclic. Une fois que tu as vu comment ton argent peut grandir, va dans ta banque ou ouvre ton appli Mobile Money pour faire le placement pour de vrai !</div>
            </div>
        </div>

        <div class="concept-card premium" id="stats-visuelles">
            <span class="premium-badge">Premium</span>
            <div class="concept-icon" style="color: #3b82f6; background: rgba(59, 130, 246, 0.1);"><i class="ri-pie-chart-line"></i></div>
            <h2 class="concept-title">Les Graphiques de Tendance et Statistiques</h2>
            <p class="concept-text">Pour bien piloter son argent, il est indispensable de visualiser ou analyser ses comportements d'achat. Wari Premium ajoute un module commutable de trois graphiques directement sur votre tableau de bord.</p>

            <p class="concept-text" style="font-weight: bold; color: var(--accent); margin-top: 15px;">Les trois graphiques a votre disposition :</p>
            <ul style="list-style-type: disc; margin-left: 20px; margin-bottom: 20px; font-size: 14px; color: #a1a1aa; line-height: 1.6;">
                <li><strong>Le graphique d'Evolution :</strong> C'est la vue par defaut. Elle affiche sous forme de barres vos revenus de chaque mois face a vos depenses. L'objectif est de s'assurer que la barre verte reste toujours plus haute que la barre rouge.</li>
                <li><strong>La courbe du Taux d'Epargne :</strong> Affiche le pourcentage reel de vos revenus que vous reussissez a mettre de cote de mois en mois. C'est l'indicateur le plus important de votre sante financiere. Les experts s'accordent a dire qu'un taux d'épargne sain doit etre superieur a 15% de vos revenus globaux.</li>
                <li><strong>La repartition des depenses (Donut) :</strong> Un graphique circulaire qui analyse vos achats du mois en cours par enveloppe. Il vous montre la repartition exacte en pourcentage entre vos besoins obligatoires (Train de vie), vos projets, vos epargnes et vos imprevus pour detecter les fuites de tresorerie.</li>
            </ul>

            <p class="concept-text" style="font-weight: bold; color: var(--accent); margin-top: 15px;">Foire aux questions : Pourquoi mon taux d'épargne affiche 0% ?</p>
            <p class="concept-text" style="font-size: 13.5px; color: #a1a1aa; line-height: 1.6; margin-bottom: 15px;">
                Si vous voyez votre courbe à 0% alors que vous avez de l'épargne de côté, cela s'explique par deux situations :
                <br>• <strong>Dépenses supérieures aux revenus</strong> : Si au cours du mois, vos dépenses cumulées dépassent le total des revenus répartis, votre épargne nette mensuelle est nulle. Vous avez pioché dans vos réserves passées.
                <br>• <strong>Aucun revenu réparti ce mois-ci</strong> : Si vous n'avez fait aucune répartition de gain ce mois-ci, votre taux d'épargne se calcule sur un revenu de 0 F, ce qui donne 0%. Dès votre prochaine répartition, la courbe remontera !
            </p>

            <div class="concept-rule">
                <i class="ri-lightbulb-line"></i>
                <div><strong>Conseil de lecture :</strong> Si vous constatez que le taux d'épargne baisse de mois en mois, il est temps d'ouvrir le donut de repartition pour verifier quelle enveloppe a deborde et reduire vos dépenses superflues.</div>
            </div>
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
            <p class="concept-text">L'épargne ne doit pas être une corvée ennuyeuse. Wari te propose des défis interactifs et ludiques (Défi 52 Semaines, Fonds d'urgence, Zéro Frivolité) pour booster ta discipline financière.</p>
            
            <p class="concept-text" style="font-weight: bold; color: var(--accent); margin-top: 15px;">Comment ça marche concrètement ?</p>
            <p class="concept-text" style="font-size: 14px;"><strong>Il ne s'agit pas de créer de nouvelles dépenses réelles</strong>. C'est un virement d'épargne interne et indolore :</p>
            <ul style="list-style-type: disc; margin-left: 20px; margin-bottom: 20px; font-size: 14px; color: #a1a1aa; line-height: 1.6;">
                <li>Lorsque tu valides un dépôt ou une semaine du défi, Wari <strong>déduit automatiquement</strong> ce montant de ton enveloppe <em>Train de vie</em> (dépenses quotidiennes).</li>
                <li>Cet argent est instantanément <strong>transféré et versé</strong> dans ton <em>Coffre-fort</em> (capital projet). Ton disponible quotidien baisse et ton capital d'épargne augmente.</li>
            </ul>

            <p class="concept-text" style="font-weight: bold; color: var(--accent);">Les 3 défis disponibles :</p>
            <ul style="list-style-type: decimal; margin-left: 20px; margin-bottom: 20px; font-size: 14px; color: #a1a1aa; line-height: 1.6;">
                <li><strong>Le Défi 52 Semaines :</strong> Tu épargnes progressivement chaque semaine (Semaine 1 = 500 F, Semaine 2 = 1 000 F, ..., Semaine 52 = 26 000 F pour une base de 500 F). À la fin de l'année, tu te retrouves avec un trésor de <strong>689 000 F CFA</strong> sans effort !</li>
                <li><strong>Le Fonds d'Urgence :</strong> Fais des dépôts du montant de ton choix à ton rythme pour te constituer un matelas de sécurité de <strong>100 000 F CFA</strong>.</li>
                <li><strong>Zéro Frivolité :</strong> 7 jours de pure discipline pour résister à tous les achats d'impulsion et garder ton budget intact.</li>
            </ul>

            <div class="concept-rule">
                <i class="ri-heart-pulse-line"></i>
                <div><strong>L'état d'esprit :</strong> Tu n'as pas besoin de trouver de l'argent supplémentaire. Tu as juste besoin de sacrifier un peu de ton superflu quotidien pour bâtir ton capital d'avenir.</div>
            </div>
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

        <div class="concept-card premium" id="cloud-sync">
            <span class="premium-badge">Premium</span>
            <div class="concept-icon" style="color: #06b6d4; background: rgba(6, 182, 212, 0.1);"><i class="ri-cloud-line"></i></div>
            <h2 class="concept-title">Sauvegarde & Synchronisation Cloud</h2>
            <p class="concept-text">Tes données financières sont précieuses. Avec Wari Premium, ton historique, tes enveloppes budgétaires et la progression de tes défis d'épargne sont sauvegardés automatiquement et en temps réel.</p>
            <p class="concept-text">Que tu changes de téléphone ou que tu te connectes depuis un autre appareil, tu retrouves instantanément toutes tes informations financières intactes. Zéro risque de perte de données.</p>
        </div>

        <?php if (!$user_id): ?>
        <div class="concept-card success" style="text-align: center; border-color: rgba(245, 158, 11, 0.3); background: linear-gradient(180deg, var(--surface) 0%, rgba(245, 158, 11, 0.03) 100%);">
            <div class="concept-icon" style="color: var(--accent); background: rgba(245, 158, 11, 0.1);"><i class="ri-medal-line"></i></div>
            <h2 class="concept-title" style="color: var(--accent);">Prêt à reprendre le contrôle ?</h2>
            <p class="concept-text" style="max-width: 500px; margin: 0 auto 25px;">
                Appliquez dès aujourd'hui la méthode des enveloppes, relevez des défis d'épargne stimulants et pilotez vos finances sereinement avec Wari Finance.
            </p>
            <a href="https://wari.digiroys.com/config/auth.php" class="cta-btn" style="background: var(--accent); color: var(--bg); font-weight: 800; text-transform: uppercase; letter-spacing: 1px; font-size: 13px; margin-top: 0;">
                Créer mon compte gratuitement
            </a>
        </div>
        <?php endif; ?>

        <div class="footer">
            <?php if ($user_id): ?>
            <a href="https://wari.digiroys.com/" class="cta-btn">J'ai compris, retour au tableau de bord</a>
            <?php else: ?>
            <a href="https://wari.digiroys.com/config/auth.php" class="cta-btn">S'inscrire / Se connecter</a>
            <?php endif; ?>
            <div style="margin-top: 20px;">© <?php echo date('Y'); ?> Wari Finance.</div>
        </div>

    </div>

</body>
</html>

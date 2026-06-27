<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>À Propos de Wari Finance | La vision derrière l'application</title>
    <meta name="description" content="Découvrez pourquoi Wari Finance a été créé. Notre mission : aider la jeunesse africaine à reprendre le contrôle de ses finances grâce à la répartition intelligente.">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://wari.digiroys.com/accueil/apropos.php">
    
    <link rel="icon" type="image/png" href="../assets/warifinance3d.png" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg: #080B10;
            --surface: #0D1117;
            --gold: #F5A623;
            --gold-dk: #d4921f;
            --gold-lt: #ffbe3d;
            --text: #E8EAF0;
            --muted: #556070;
            --border: rgba(255, 255, 255, 0.06);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg); color: var(--text); line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; padding: 4rem 1.5rem; }
        
        .header { margin-bottom: 3rem; text-align: center; }
        .logo { font-size: 1.8rem; font-weight: 700; color: var(--gold); text-decoration: none; display: inline-block; margin-bottom: 2rem; }
        h1 { font-size: 2.5rem; margin-bottom: 1rem; line-height: 1.2; }
        .lead { font-size: 1.2rem; color: var(--muted); }
        
        .content-section { background: var(--surface); border: 1px solid var(--border); border-radius: 24px; padding: 2.5rem; margin-bottom: 2rem; }
        h2 { font-size: 1.5rem; color: var(--gold); margin-bottom: 1rem; margin-top: 1.5rem; }
        h2:first-child { margin-top: 0; }
        p { margin-bottom: 1.5rem; color: #cbd5e1; }
        ul { margin-bottom: 1.5rem; padding-left: 1.5rem; color: #cbd5e1; }
        li { margin-bottom: 0.5rem; }
        
        .btn { display: inline-block; background: var(--gold); color: #000; padding: 0.8rem 2rem; border-radius: 30px; text-decoration: none; font-weight: 600; margin-top: 1rem; }
        .btn:hover { background: var(--gold-lt); }
        
        footer { margin-top: 4rem; text-align: center; color: var(--muted); font-size: 0.9rem; padding-bottom: 2rem; }
        .back-link { display: inline-flex; align-items: center; gap: 0.5rem; color: var(--muted); text-decoration: none; margin-bottom: 2rem; transition: color 0.2s; }
        .back-link:hover { color: var(--gold); }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Retour à l'accueil
        </a>

        <div class="header">
            <a href="index.php" class="logo">WARI-Finance</a>
            <h1>Notre Mission : Redonner le pouvoir sur votre argent</h1>
            <p class="lead">L'application pensée par et pour la jeunesse africaine.</p>
        </div>

        <div class="content-section">
            <h2>L'origine de Wari Finance</h2>
            <p>Tout a commencé par un constat simple : il est incroyablement facile de dépenser son argent aujourd'hui avec le mobile money et les paiements en ligne. Mais il est tout aussi difficile de savoir exactement où cet argent est passé à la fin du mois.</p>
            <p>La majorité des applications de gestion budgétaire sont complexes, pensées pour des experts-comptables, ou inadaptées à nos réalités (dépenses imprévues fréquentes, gestion de projets personnels non bancarisés, etc.).</p>
            
            <h2>Notre Philosophie : La Répartition</h2>
            <p>Chez Wari, nous ne croyons pas à la simple notation des dépenses. Noter une dépense, c'est constater les dégâts. Nous croyons à la <strong>planification budgétaire par répartition</strong>.</p>
            <p>Le système des "4 piliers" (Épargne, Train de vie, Projet, Imprévu) est au cœur de notre application. Chaque franc CFA que vous gagnez doit recevoir une mission dès son arrivée. Ainsi, vous ne vous demandez plus si vous pouvez vous permettre un achat : votre budget "Train de vie" vous donne la réponse instantanément.</p>

            <h2>Qui sommes-nous ?</h2>
            <p>Wari Finance est le produit phare de <strong>Digiroys</strong>, un écosystème de solutions numériques visant à autonomiser la jeunesse et les entrepreneurs africains.</p>
            <ul>
                <li><strong>Simplicité :</strong> Des interfaces claires, sans jargon financier.</li>
                <li><strong>Sécurité :</strong> Vos données sont privées et chiffrées.</li>
                <li><strong>Éducation :</strong> À travers la <em>Wari Academy</em>, nous formons nos utilisateurs à de meilleures décisions.</li>
            </ul>

            <div style="text-align: center; margin-top: 3rem;">
                <p>Prêt à reprendre le contrôle de vos finances ?</p>
                <a href="../paid/" class="btn">Commencer l'expérience Wari</a>
            </div>
        </div>
        
        <footer>
            <p>© <?php echo date('Y'); ?> Digiroys. Wari Finance.</p>
        </footer>
    </div>
</body>
</html>

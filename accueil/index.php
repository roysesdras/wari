<?php
require '../config/db.php';
require_once __DIR__ . '/../wari_monitoring.php';  // ← TOUJOURS EN PREMIER
// DÉTECTION BOT + PRÉ-RENDU POUR SEO
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isBot = preg_match('/Googlebot|bingbot|Slurp|DuckDuckBot|Baiduspider/i', $userAgent);

if ($isBot) {
    // Servir une version HTML pure pour les bots (pas de JS)
    header('Content-Type: text/html; charset=utf-8');
?>
    <!DOCTYPE html>
    <html lang="fr">

    <head>
        <meta charset="UTF-8">
        <title>Wari-Finance | Gestion Budget & Objectifs Financiers | Afrique</title>
        <meta name="description" content="Gère ton argent sans stress avec Wari. Répartition automatique, objectifs clairs, dépenses maîtrisées. Application gratuite.">
        <!-- Schema.org ci-dessus -->
    </head>

    <body>
        <h1>Wari-Finance - Gère ton argent sans stress</h1>
        <p>Application de gestion budgétaire pour la jeunesse africaine.</p>
        <ul>
            <li>Répartition automatique de ton argent</li>
            <li>Objectifs financiers clairs et atteignables</li>
            <li>Dépenses maîtrisées sans stress</li>
        </ul>
        <p><a href="https://wari.digiroys.com/accueil/">Accéder à l'application</a></p>
    </body>

    </html>
<?php
    exit; // Stopper ici pour les bots
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">

    <link rel="apple-touch-icon" href="https://wari.digiroys.com/assets/warifinance3d.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">


    <!-- SCHEMA.ORG - Données structurées pour Google -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "WebApplication",
            "name": "Wari-Finance",
            "applicationCategory": "FinanceApplication",
            "operatingSystem": "Any",
            "url": "https://wari.digiroys.com/accueil/",
            "description": "Gère ton argent sans stress avec Wari : budget, objectifs, conseils simples pour enfin maîtriser tes finances au quotidien.",
            "offers": {
                "@type": "Offer",
                "price": "0",
                "priceCurrency": "XOF"
            },
            "featureList": "Répartition automatique de l'argent, Objectifs financiers clairs, Dépenses maîtrisées, Conseils budgétaires personnalisés",
            "audience": {
                "@type": "Audience",
                "audienceType": "Jeunes professionnels, étudiants, particuliers en Afrique francophone"
            },
            "availableLanguage": ["French"],
            "aggregateRating": {
                "@type": "AggregateRating",
                "ratingValue": "4.7",
                "ratingCount": "127",
                "bestRating": "5"
            },
            "author": {
                "@type": "Organization",
                "name": "Wari Finance",
                "url": "https://wari.digiroys.com"
            }
        }
    </script>

    <!-- Open Graph (Facebook, WhatsApp, LinkedIn) -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Wari-Finance - Gère ton argent sans stress">
    <meta property="og:description" content="Budget, objectifs, conseils simples pour maîtriser tes finances au quotidien. Application gratuite.">
    <meta property="og:url" content="https://wari.digiroys.com/accueil/">
    <meta property="og:image" content="https://wari.digiroys.com/assets/wari_og_1.png">
    <meta property="og:locale" content="fr_FR">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Wari-Finance - Gère ton argent sans stress">
    <meta name="twitter:description" content="Budget, objectifs, conseils simples pour maîtriser tes finances.">

    <!-- SEO de base optimisé -->
    <title>Wari-Finance | Gestion Budget & Objectifs Financiers | Afrique</title>
    <meta name="description" content="Gère ton argent sans stress avec Wari. Répartition automatique, objectifs clairs, dépenses maîtrisées. Application gratuite pour la gestion budgétaire quotidienne.">
    <meta name="keywords" content="gestion budget, objectifs financiers, épargne, dépenses, finance personnelle, Afrique, Côte d'Ivoire">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="https://wari.digiroys.com/accueil/">

    <style>
        /* ── RESET & VARIABLES ───────────────────────────────────────── */
        :root {
            /* 🌟 PALETTE WARI OFFICIELLE */
            --gold: #F5A623;
            --gold-dim: rgba(245, 166, 35, 0.12);
            --gold-border: rgba(245, 166, 35, 0.3);
            --gold-dk: #d4921f;
            /* Gold plus foncé */
            --gold-lt: #ffbe3d;
            /* Gold plus clair */

            --bg: #080B10;
            --surface: #0D1117;
            --surface2: #161B24;
            --border: rgba(255, 255, 255, 0.06);

            --text: #E8EAF0;
            --muted: #556070;

            --red: #F56565;
            --red-dim: rgba(245, 101, 101, 0.12);
            --green: #48BB78;
            --green-dim: rgba(72, 187, 120, 0.12);
            --blue: #63B3ED;
            --blue-dim: rgba(99, 179, 237, 0.12);
            --orange: #ED8936;

            /* 🎯 COULEURS FONCTIONNELLES */
            --teal: var(--gold);
            /* Remplacé par gold WARI */
            --teal-lt: #ffbe3d;
            /* Gold plus clair */
            --teal-dk: #d4921f;
            /* Gold plus foncé */
            --sky: var(--blue);
            /* Bleu WARI */
            --amber: var(--gold);
            /* Gold WARI */
            --rose: var(--red);
            /* Rouge WARI */
            --muted2: var(--muted);
            /* Simplifié */
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        /* ── BACKGROUND BLOBS ─────────────────────────────────────────── */
        .background-blobs {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            pointer-events: none;
            opacity: 0.18;
        }

        .background-blobs svg {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* ── LAYOUT ───────────────────────────────────────────────────── */
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0.5rem 0.5rem;
        }

        /* ── TYPO ─────────────────────────────────────────────────────── */
        h1,
        h2,
        h3 {
            font-weight: 600;
            letter-spacing: -0.02em;
        }

        h1 {
            font-size: 2.4rem;
            line-height: 1.1;
            margin-bottom: 0.5rem;
        }

        h2 {
            font-size: 2rem;
            margin-bottom: 1.5rem;
            color: #f1f5f9;
        }

        h3 {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
        }

        p {
            color: var(--muted);
            margin-bottom: 1.5rem;
        }

        /* ── LOGO ─────────────────────────────────────────────────────── */
        .logo {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--gold-dk), var(--gold), var(--gold-lt));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .subhead {
            font-size: 1.1rem;
            color: var(--muted2);
            border-left: 4px solid var(--gold);
            padding-left: 1rem;
            margin-bottom: 2rem;
        }

        /* ── CTA BUTTON ───────────────────────────────────────────────── */
        .btn {
            display: inline-block;
            background: var(--gold);
            color: #0f172a;
            font-weight: 600;
            padding: 0.85rem 2rem;
            border-radius: 40px;
            text-decoration: none;
            transition: background 0.2s, transform 0.15s;
            border: none;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(245, 166, 35, 0.25);
            font-size: 1rem;
        }

        .btn:hover {
            background: var(--teal-lt);
            transform: translateY(-2px);
        }

        /* ── HERO PHONES ──────────────────────────────────────────────── */
        .phones-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: center;
            margin: 2.5rem 0 1rem;
        }

        .phone-svg {
            flex: 0 1 auto;
            width: 160px;
            max-width: 45%;
            transition: transform 0.2s ease;
        }

        .phone-svg svg {
            width: 100%;
            height: auto;
            display: block;
            filter: drop-shadow(0 20px 15px rgba(0, 0, 0, 0.6));
        }

        @media (min-width: 380px) {
            .phone-svg {
                width: 170px;
            }
        }

        /* ── SECTION DIVIDER ──────────────────────────────────────────── */
        .section-divider {
            width: 100%;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--border2), transparent);
            margin: 3.5rem 0;
        }

        /* ── FEATURE CARDS ────────────────────────────────────────────── */
        .cards-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
            margin: 3rem 0;
        }

        .feature-card {
            background: var(--surface);
            border-radius: 24px;
            padding: 1.8rem 1.5rem;
            border: 1px solid var(--gold);
            /* box-shadow: 0 12px 30px rgba(245, 166, 35, 0.12); */
            transition: transform 0.2s, border-color 0.2s;
        }

        .feature-card:hover {
            transform: translateY(-4px);
            border-color: rgba(245, 166, 35, 0.12);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 1.5rem;
        }

        .card-icon {
            width: 48px;
            height: 48px;
            background: var(--bg2);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border2);
        }

        .card-icon svg {
            width: 28px;
            height: 28px;
            stroke: var(--gold);
            fill: none;
            stroke-width: 2;
        }

        .percent-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #f1f5f9;
            line-height: 1;
            margin-bottom: 0.75rem;
        }

        .progress-bg {
            background: var(--bg2);
            height: 8px;
            border-radius: 20px;
            overflow: hidden;
            margin: 0.5rem 0 0.2rem;
        }

        .progress-fill {
            background: linear-gradient(90deg, var(--gold), var(--blue));
            height: 8px;
            border-radius: 20px;
        }

        .category-note {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: var(--muted);
            margin-top: 0.3rem;
        }

        /* ── RÉPARTITION ──────────────────────────────────────────────── */
        .ideal-repart {
            background: var(--surface);
            border-radius: 28px;
            padding: 2rem 1.8rem;
            margin: 3rem 0;
            /* border: 1px solid var(--border); */
            text-align: center;
        }

        .mini-pie-svg {
            width: 140px;
            margin: 1rem auto 1.5rem;
        }

        .legend-dots {
            display: flex;
            flex-wrap: wrap;
            gap: 1.2rem;
            justify-content: center;
            margin: 1.5rem 0 0.5rem;
        }

        .legend-dots div {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.9rem;
        }

        .dot-color {
            width: 12px;
            height: 12px;
            border-radius: 12px;
            display: inline-block;
            flex-shrink: 0;
        }

        /* ── TRUST BAR ────────────────────────────────────────────────── */
        .trust-bar {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 1rem 1rem;
            margin: 3rem 0;
            font-size: 0.9rem;
            color: var(--muted);
        }

        /* ── GUIDE PDF ────────────────────────────────────────────────── */
        .guide-block {
            background: var(--surface);
            border-radius: 28px;
            padding: 2.5rem;
            margin: 2rem 0;
            border: 1px solid var(--border);
            text-align: center;
        }

        .guide-block h3 {
            font-size: 1.5rem;
            margin: 0;
            color: #f1f5f9;
        }

        .guide-block .btn-download {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--teal);
            color: #0f172a;
            font-weight: 600;
            padding: 0.85rem 2rem;
            border-radius: 40px;
            text-decoration: none;
            transition: background 0.2s, transform 0.15s;
            box-shadow: 0 8px 20px rgba(245, 166, 35, 0.2);
        }

        .guide-block .btn-download:hover {
            background: var(--teal-lt);
            transform: translateY(-2px);
        }


        /* CTA laisser un avis */
        .avis-cta {
            text-align: center;
            margin-top: 2rem;
        }

        .btn-avis {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: transparent;
            color: var(--gold);
            font-weight: 600;
            padding: 0.75rem 1.8rem;
            border-radius: 40px;
            border: 1.5px solid rgba(45, 212, 191, 0.4);
            text-decoration: none;
            font-size: 0.95rem;
            transition: background 0.2s, border-color 0.2s, transform 0.15s;
            cursor: pointer;
        }

        .btn-avis:hover {
            background: rgba(45, 212, 191, 0.08);
            border-color: var(--gold);
            transform: translateY(-2px);
        }

        /* ── MISC ─────────────────────────────────────────────────────── */
        .text-accent {
            color: var(--teal);
            font-weight: 500;
        }

        .badge-light {
            background: var(--surface);
            border: 1px solid var(--border2);
            color: var(--muted);
            padding: 0.2rem 1rem;
            border-radius: 30px;
            font-size: 0.8rem;
            display: inline-block;
            margin-bottom: 1rem;
        }

        /* ── FOOTER ───────────────────────────────────────────────────── */
        footer {
            margin-top: 4rem;
            padding: 2rem 0 1rem;
            border-top: 1px solid var(--border);
            text-align: center;
            font-size: 0.9rem;
            color: var(--muted2);
        }

        /* ── RESPONSIVE ───────────────────────────────────────────────── */
        @media (min-width: 640px) {
            .cards-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            h1 {
                font-size: 3rem;
            }

            .container {
                padding: 2rem 2rem;
            }

            .avis-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 1024px) {
            .hero {
                display: grid;
                grid-template-columns: 1fr 1.2fr;
                align-items: center;
                gap: 2rem;
            }

            .phones-grid {
                margin: 0;
                justify-content: flex-end;
            }

            .cards-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 1.2rem;
            }

            .phone-svg {
                width: 180px;
            }

            .avis-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
    </style>
</head>

<body>
    <div class="background-blobs" aria-hidden="true">
        <svg viewBox="0 0 800 600" xmlns="http://www.w3.org/2000/svg">
            <circle cx="100" cy="200" r="180" fill="#1e293b" opacity="0.2" />
            <circle cx="700" cy="400" r="260" fill="#0f172a" opacity="0.3" />
            <rect x="0" y="0" width="800" height="600" fill="url(#grad)" />
            <defs>
                <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
                    <stop offset="0%" stop-color="#1e2b3c" stop-opacity="0.1" />
                    <stop offset="100%" stop-color="#1e2b3c" stop-opacity="0" />
                </linearGradient>
            </defs>
        </svg>
    </div>

    <main class="container">

        <!-- ── HEADER ────────────────────────────────────────────────── -->
        <header>
            <div class="logo">WARI-Finance</div>
            <p class="subhead">Chaque franc doit avoir une mission.</p>
        </header>

        <!-- ── HERO ──────────────────────────────────────────────────── -->
        <section class="hero" aria-label="Introduction">
            <div>
                <h1>Contrôlez <br>chaque CFA <br><span class="text-accent">que vous dépensez</span></h1>
                <p>Wari - Finance est l'outil parfait qui t’aide à décider à l’avance où va ton argent.</p>
                <a class="btn" href="../paid/landing-vente.php" target="_blanck" aria-label="Découvrir l'appli">Découvrir l'appli →</a>
                <div style="margin-top: 1.5rem; display: flex; gap: 1.5rem; flex-wrap: wrap;">
                    <span style="display:flex;align-items:center;gap:0.4rem;font-size:0.9rem;color:var(--muted)"><span style="background:var(--gold);width:10px;height:10px;border-radius:10px;flex-shrink:0;"></span> Épargne 40%</span>
                    <span style="display:flex;align-items:center;gap:0.4rem;font-size:0.9rem;color:var(--muted)"><span style="background:var(--blue);width:10px;height:10px;border-radius:10px;flex-shrink:0;"></span> Train de vie 30%</span>
                </div>
            </div>

            <div class="phones-grid">

                <!-- ── Écran 1 : Dashboard solde + donut + transactions (iPhone) ── -->
                <div class="phone-svg">
                    <svg viewBox="0 0 160 320" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="spark1" x1="0" y1="0" x2="1" y2="0">
                                <stop offset="0%" stop-color="#F5A623" />
                                <stop offset="100%" stop-color="#63B3ED" />
                            </linearGradient>
                        </defs>
                        <!-- Corps -->
                        <rect x="3" y="3" width="154" height="314" rx="22" fill="#1a2a3a" stroke="#2d4155" stroke-width="1.5" />
                        <!-- Écran -->
                        <rect x="11" y="20" width="138" height="282" rx="14" fill="#0b121e" />
                        <!-- Dynamic island (iPhone) -->
                        <rect x="57" y="13" width="46" height="9" rx="4.5" fill="#0d1822" />
                        <!-- Status bar -->
                        <text x="18" y="34" fill="#4e6478" font-size="6" font-family="Inter,sans-serif" font-weight="500">9:41</text>
                        <rect x="120" y="28" width="18" height="7" rx="2" fill="none" stroke="#4e6478" stroke-width=".8" />
                        <rect x="121" y="29.5" width="13" height="4" rx="1" fill="#F5A623" />
                        <rect x="139.5" y="30" width="1.5" height="3" rx=".75" fill="#4e6478" />
                        <!-- Header -->
                        <text x="18" y="51" fill="#7a93aa" font-size="6.5" font-family="Inter,sans-serif">Bonjour, Adjovi 👋</text>
                        <circle cx="140" cy="48" r="8" fill="#1e3040" stroke="#2d4155" stroke-width=".8" />
                        <text x="140" y="51" text-anchor="middle" fill="#F5A623" font-size="6" font-family="Inter,sans-serif" font-weight="700">AK</text>
                        <!-- Carte solde -->
                        <rect x="16" y="58" width="128" height="62" rx="12" fill="#1e3040" stroke="#2d4155" stroke-width=".8" />
                        <circle cx="130" cy="62" r="20" fill="#F5A623" opacity=".04" />
                        <circle cx="14" cy="110" r="16" fill="#38bdf8" opacity=".04" />
                        <text x="24" y="72" fill="#4e6478" font-size="5.5" font-family="Inter,sans-serif">Solde total</text>
                        <text x="24" y="88" fill="#f1f5f9" font-size="14" font-family="Inter,sans-serif" font-weight="700">485 000</text>
                        <text x="86" y="88" fill="#7a93aa" font-size="7" font-family="Inter,sans-serif">CFA</text>
                        <rect x="24" y="93" width="36" height="10" rx="5" fill="rgba(45,212,191,.12)" />
                        <text x="42" y="100.5" text-anchor="middle" fill="#F5A623" font-size="5.5" font-family="Inter,sans-serif">↑ +3.2%</text>
                        <polyline points="90,108 97,102 104,105 111,98 118,101 125,94 132,96" stroke="#F5A623" stroke-width="1.2" fill="none" stroke-linecap="round" stroke-linejoin="round" />
                        <!-- Donut chart 4 piliers -->
                        <circle cx="80" cy="162" r="28" fill="none" stroke="#1e3040" stroke-width="7" />
                        <circle cx="80" cy="162" r="28" fill="none" stroke="#F5A623" stroke-width="7" stroke-dasharray="73.9 175.9" stroke-dashoffset="44" transform="rotate(-90 80 162)" />
                        <circle cx="80" cy="162" r="28" fill="none" stroke="#38bdf8" stroke-width="7" stroke-dasharray="54.5 175.9" stroke-dashoffset="-29.9" transform="rotate(-90 80 162)" />
                        <circle cx="80" cy="162" r="28" fill="none" stroke="#F5A623" stroke-width="7" stroke-dasharray="31.7 175.9" stroke-dashoffset="-84.4" transform="rotate(-90 80 162)" />
                        <circle cx="80" cy="162" r="28" fill="none" stroke="#f87171" stroke-width="7" stroke-dasharray="15.8 175.9" stroke-dashoffset="-116.1" transform="rotate(-90 80 162)" />
                        <text x="80" y="159" text-anchor="middle" fill="#f1f5f9" font-size="8" font-family="Inter,sans-serif" font-weight="700">42%</text>
                        <text x="80" y="168" text-anchor="middle" fill="#4e6478" font-size="5" font-family="Inter,sans-serif">Épargne</text>
                        <!-- Légende donut -->
                        <rect x="16" y="198" width="7" height="7" rx="2" fill="#F5A623" />
                        <text x="26" y="204.5" fill="#c8d8e8" font-size="5.5" font-family="Inter,sans-serif">Épargne 42%</text>
                        <rect x="82" y="198" width="7" height="7" rx="2" fill="#38bdf8" />
                        <text x="92" y="204.5" fill="#c8d8e8" font-size="5.5" font-family="Inter,sans-serif">TDV 31%</text>
                        <rect x="16" y="209" width="7" height="7" rx="2" fill="#F5A623" />
                        <text x="26" y="215.5" fill="#c8d8e8" font-size="5.5" font-family="Inter,sans-serif">Projet 18%</text>
                        <rect x="82" y="209" width="7" height="7" rx="2" fill="#f87171" />
                        <text x="92" y="215.5" fill="#c8d8e8" font-size="5.5" font-family="Inter,sans-serif">Imprévu 9%</text>
                        <!-- Dernières transactions -->
                        <text x="18" y="232" fill="#7a93aa" font-size="5.5" font-family="Inter,sans-serif">Dernières opérations</text>
                        <rect x="16" y="237" width="128" height="16" rx="5" fill="#1e3040" />
                        <rect x="20" y="241" width="8" height="8" rx="3" fill="rgba(45,212,191,.15)" />
                        <text x="23.5" y="247" text-anchor="middle" fill="#F5A623" font-size="5" font-family="Inter,sans-serif">E</text>
                        <text x="33" y="247" fill="#c8d8e8" font-size="5.5" font-family="Inter,sans-serif">Virement épargne</text>
                        <text x="136" y="247" text-anchor="end" fill="#F5A623" font-size="5.5" font-family="Inter,sans-serif" font-weight="600">+25 000</text>
                        <rect x="16" y="256" width="128" height="16" rx="5" fill="#1e3040" />
                        <rect x="20" y="260" width="8" height="8" rx="3" fill="rgba(248,113,113,.12)" />
                        <text x="23.5" y="266" text-anchor="middle" fill="#f87171" font-size="6" font-family="Inter,sans-serif">↓</text>
                        <text x="33" y="266" fill="#c8d8e8" font-size="5.5" font-family="Inter,sans-serif">Courses marché</text>
                        <text x="136" y="266" text-anchor="end" fill="#f87171" font-size="5.5" font-family="Inter,sans-serif" font-weight="600">−8 500</text>
                        <rect x="16" y="275" width="128" height="16" rx="5" fill="#1e3040" />
                        <rect x="20" y="279" width="8" height="8" rx="3" fill="rgba(251,191,36,.12)" />
                        <text x="23.5" y="285" text-anchor="middle" fill="#F5A623" font-size="5" font-family="Inter,sans-serif">P</text>
                        <text x="33" y="285" fill="#c8d8e8" font-size="5.5" font-family="Inter,sans-serif">Projet voyage</text>
                        <text x="136" y="285" text-anchor="end" fill="#F5A623" font-size="5.5" font-family="Inter,sans-serif" font-weight="600">+12 000</text>
                        <!-- Home bar -->
                        <rect x="55" y="308" width="50" height="3" rx="1.5" fill="#2d4155" />
                    </svg>
                </div>

                <!-- ── Écran 2 : Suivi épargne + courbe mensuelle (Android) ── -->
                <div class="phone-svg">
                    <svg viewBox="0 0 160 320" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="pg1" x1="0" y1="0" x2="1" y2="0">
                                <stop offset="0%" stop-color="#F5A623" />
                                <stop offset="100%" stop-color="#63B3ED" />
                            </linearGradient>
                        </defs>
                        <!-- Corps -->
                        <rect x="3" y="3" width="154" height="314" rx="22" fill="#1a2a3a" stroke="#2d4155" stroke-width="1.5" />
                        <!-- Écran -->
                        <rect x="11" y="20" width="138" height="282" rx="14" fill="#0b121e" />
                        <!-- Punch-hole camera (Android) -->
                        <circle cx="32" cy="28" r="5" fill="#0d1822" stroke="#1a2a3a" stroke-width=".8" />
                        <circle cx="32" cy="28" r="2.5" fill="#090f17" />
                        <!-- Status bar -->
                        <text x="44" y="33" fill="#4e6478" font-size="6" font-family="Inter,sans-serif" font-weight="500">9:41</text>
                        <rect x="120" y="27" width="18" height="7" rx="2" fill="none" stroke="#4e6478" stroke-width=".8" />
                        <rect x="121" y="28.5" width="10" height="4" rx="1" fill="#F5A623" />
                        <rect x="139.5" y="29" width="1.5" height="3" rx=".75" fill="#4e6478" />
                        <!-- Titre écran -->
                        <text x="20" y="50" fill="#7a93aa" font-size="9" font-family="Inter,sans-serif">‹</text>
                        <text x="80" y="51" text-anchor="middle" fill="#f1f5f9" font-size="8" font-family="Inter,sans-serif" font-weight="600">Mon Épargne</text>
                        <!-- Carte montant épargné -->
                        <rect x="16" y="58" width="128" height="54" rx="12" fill="#1e3040" stroke="#2d4155" stroke-width=".8" />
                        <circle cx="132" cy="60" r="22" fill="#F5A623" opacity=".05" />
                        <text x="24" y="72" fill="#4e6478" font-size="5.5" font-family="Inter,sans-serif">Épargne accumulée</text>
                        <text x="24" y="86" fill="#f1f5f9" font-size="13" font-family="Inter,sans-serif" font-weight="700">203 800</text>
                        <text x="90" y="86" fill="#7a93aa" font-size="6.5" font-family="Inter,sans-serif">CFA</text>
                        <text x="24" y="97" fill="#4e6478" font-size="5.5" font-family="Inter,sans-serif">Objectif : 500 000 CFA</text>
                        <rect x="90" y="92" width="50" height="5" rx="2.5" fill="#0b121e" />
                        <rect x="90" y="92" width="20.4" height="5" rx="2.5" fill="#F5A623" />
                        <!-- Barre de progression objectif -->
                        <text x="18" y="126" fill="#7a93aa" font-size="5.5" font-family="Inter,sans-serif">Progression vers l'objectif</text>
                        <rect x="16" y="130" width="128" height="8" rx="4" fill="#1e3040" />
                        <rect x="16" y="130" width="52.2" height="8" rx="4" fill="url(#pg1)" />
                        <text x="18" y="148" fill="#F5A623" font-size="6" font-family="Inter,sans-serif" font-weight="600">40.8%</text>
                        <text x="136" y="148" text-anchor="end" fill="#4e6478" font-size="5.5" font-family="Inter,sans-serif">500 000 CFA</text>
                        <!-- 2 mini-stats -->
                        <text x="18" y="164" fill="#7a93aa" font-size="5.5" font-family="Inter,sans-serif">Ce mois</text>
                        <rect x="16" y="169" width="58" height="36" rx="8" fill="#1e3040" stroke="#243447" stroke-width=".8" />
                        <text x="45" y="180" text-anchor="middle" fill="#4e6478" font-size="5" font-family="Inter,sans-serif">Versé</text>
                        <text x="45" y="191" text-anchor="middle" fill="#F5A623" font-size="8" font-family="Inter,sans-serif" font-weight="700">+42 000</text>
                        <text x="45" y="199" text-anchor="middle" fill="#4e6478" font-size="4.5" font-family="Inter,sans-serif">CFA</text>
                        <rect x="86" y="169" width="58" height="36" rx="8" fill="#1e3040" stroke="#243447" stroke-width=".8" />
                        <text x="115" y="180" text-anchor="middle" fill="#4e6478" font-size="5" font-family="Inter,sans-serif">Manquant</text>
                        <text x="115" y="191" text-anchor="middle" fill="#f1f5f9" font-size="8" font-family="Inter,sans-serif" font-weight="700">296 200</text>
                        <text x="115" y="199" text-anchor="middle" fill="#4e6478" font-size="4.5" font-family="Inter,sans-serif">CFA</text>
                        <!-- Graphique courbe mensuelle -->
                        <text x="18" y="218" fill="#7a93aa" font-size="5.5" font-family="Inter,sans-serif">Historique mensuel</text>
                        <rect x="16" y="222" width="128" height="44" rx="8" fill="#1e3040" />
                        <line x1="16" y1="243" x2="144" y2="243" stroke="#243447" stroke-width=".5" />
                        <line x1="16" y1="254" x2="144" y2="254" stroke="#243447" stroke-width=".5" />
                        <polyline points="22,258 36,251 50,248 64,244 78,241 92,237 106,234 120,230 134,226" stroke="#F5A623" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round" />
                        <polygon points="22,258 36,251 50,248 64,244 78,241 92,237 106,234 120,230 134,226 134,262 22,262" fill="#F5A623" opacity=".07" />
                        <text x="22" y="270" fill="#4e6478" font-size="4.5" font-family="Inter,sans-serif">Jan</text>
                        <text x="50" y="270" fill="#4e6478" font-size="4.5" font-family="Inter,sans-serif">Mar</text>
                        <text x="78" y="270" fill="#4e6478" font-size="4.5" font-family="Inter,sans-serif">Mai</text>
                        <text x="106" y="270" fill="#4e6478" font-size="4.5" font-family="Inter,sans-serif">Juil</text>
                        <text x="130" y="270" fill="#4e6478" font-size="4.5" font-family="Inter,sans-serif">Sep</text>
                        <!-- Bouton CTA -->
                        <rect x="16" y="278" width="128" height="16" rx="8" fill="#F5A623" />
                        <text x="80" y="289" text-anchor="middle" fill="#0b121e" font-size="6" font-family="Inter,sans-serif" font-weight="700">+ Enregistrer un versement</text>
                        <!-- Nav Android -->
                        <circle cx="55" cy="308" r="3" fill="#2d4155" />
                        <circle cx="80" cy="308" r="3" fill="#2d4155" />
                        <circle cx="105" cy="308" r="3" fill="#2d4155" />
                    </svg>
                </div>

            </div>
        </section>

        <div class="section-divider"></div>

        <!-- ── 4 PILIERS ─────────────────────────────────────────────── -->
        <section aria-label="Les 4 piliers de Wari">
            <div style="margin-bottom: 2.5rem; text-align: center;">
                <span class="badge-light">Épargne · Train de vie · Projet · Imprévu</span>
                <h2>Vos pourcentages, clairs et vivants</h2>
                <p style="max-width: 600px; margin-left: auto; margin-right: auto;">Suivi en temps réel, objectifs visuels, et une loupe sur votre santé financière.</p>
            </div>

            <div class="cards-grid">
                <div class="feature-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <svg viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="9" stroke-width="1.8" />
                                <path d="M12 6v6l4 2" stroke-width="1.8" />
                            </svg>
                        </div>
                        <h3>Épargne</h3>
                    </div>
                    <div class="percent-value">42%</div>
                    <div class="progress-bg">
                        <div class="progress-fill" style="width:42%"></div>
                    </div>
                    <div class="category-note"><span>Objectif 50%</span><span>+5% ce mois</span></div>
                </div>

                <div class="feature-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <svg viewBox="0 0 24 24" stroke="currentColor">
                                <circle cx="12" cy="8" r="3" stroke-width="1.8" />
                                <path d="M5 18v2M19 18v2M9 14v4M15 14v4" stroke-width="1.8" />
                                <path d="M5 14h14v4H5z" stroke-width="1.8" />
                            </svg>
                        </div>
                        <h3>Train de vie</h3>
                    </div>
                    <div class="percent-value">31%</div>
                    <div class="progress-bg">
                        <div class="progress-fill" style="width:31%"></div>
                    </div>
                    <div class="category-note"><span>dans objectif</span><span>✓</span></div>
                </div>

                <div class="feature-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <svg viewBox="0 0 24 24" stroke="currentColor">
                                <circle cx="12" cy="12" r="8" stroke-width="1.8" />
                                <circle cx="12" cy="12" r="3" stroke-width="1.8" fill="#F5A623" fill-opacity="0.4" />
                                <path d="M12 4v2M20 12h-2M12 20v-2M4 12H6" stroke-width="1.8" />
                            </svg>
                        </div>
                        <h3>Projet</h3>
                    </div>
                    <div class="percent-value">18%</div>
                    <div class="progress-bg">
                        <div class="progress-fill" style="width:18%"></div>
                    </div>
                    <div class="category-note"><span>voyage 2025</span><span>+3%</span></div>
                </div>

                <div class="feature-card">
                    <div class="card-header">
                        <div class="card-icon">
                            <svg viewBox="0 0 24 24" stroke="currentColor">
                                <path d="M12 8v5M12 16h.01" stroke-width="2" stroke-linecap="round" />
                                <circle cx="12" cy="12" r="9" stroke-width="1.8" />
                            </svg>
                        </div>
                        <h3>Imprévu</h3>
                    </div>
                    <div class="percent-value">9%</div>
                    <div class="progress-bg">
                        <div class="progress-fill" style="width:9%"></div>
                    </div>
                    <div class="category-note"><span>matelas sécurité</span><span>+2%</span></div>
                </div>
            </div>
        </section>

        <div class="section-divider"></div>

        <!-- ── RÉPARTITION ────────────────────────────────────────────── -->
        <section class="ideal-repart" aria-label="Répartition WARI">
            <h3 style="font-size: 1.8rem; color: var(--gold);">Répartition WARI</h3>
            <div class="mini-pie-svg">
                <svg viewBox="0 0 40 40" width="120" height="120">
                    <circle cx="20" cy="20" r="15" fill="var(--bg2)" stroke="#F5A623" stroke-width="2" stroke-dasharray="94 94" stroke-dashoffset="0" stroke-linecap="round" />
                    <circle cx="20" cy="20" r="11" fill="var(--bg2)" stroke="#38bdf8" stroke-width="2" stroke-dasharray="59 94" stroke-dashoffset="-20" stroke-linecap="round" />
                    <circle cx="20" cy="20" r="7" fill="var(--bg2)" stroke="#F5A623" stroke-width="2" stroke-dasharray="28 94" stroke-dashoffset="-55" stroke-linecap="round" />
                    <circle cx="20" cy="20" r="3" fill="var(--bg2)" stroke="#f87171" stroke-width="2" stroke-dasharray="10 94" stroke-dashoffset="-83" stroke-linecap="round" />
                    <circle cx="20" cy="20" r="2" fill="#0f172a" />
                </svg>
            </div>
            <div class="legend-dots">
                <div><span class="dot-color" style="background:#F5A623"></span> Épargne 42%</div>
                <div><span class="dot-color" style="background:#38bdf8"></span> Train de vie 31%</div>
                <div><span class="dot-color" style="background:#F5A623"></span> Projet 18%</div>
                <div><span class="dot-color" style="background:#f87171"></span> Imprévu 9%</div>
            </div>
            <p style="margin-top: 2rem; margin-bottom: 0;">Visualisation douce, alertes intelligentes, et vous restez maître.</p>
        </section>

        <div class="section-divider"></div>

        <!-- ══════════════════════════════════════════════════════════════
             ── SECTION AVIS ──────────────────────────────────────────
        ══════════════════════════════════════════════════════════════ -->

        <?php include 'avis.php'; ?>

        <div class="section-divider"></div>

        <!-- ── TRUST BAR ──────────────────────────────────────────────── -->
        <div class="trust-bar" role="list">
            <span>🔒 Données chiffrées</span>
            <span>⚡ Mise à jour temps réel</span>
            <span>📱 iOS · Android · PWA</span>
        </div>

        <!-- ── GUIDE PDF ───────────────────────────────────────────────── -->
        <section class="guide-block" aria-label="Guide d'utilisation">
            <div style="display:flex;align-items:center;justify-content:center;gap:1rem;margin-bottom:1.5rem;">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#F5A623" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                <h3>Guide d'utilisation</h3>
            </div>
            <p style="color:var(--muted);margin-bottom:1.5rem;">Téléchargez le guide complet pour maîtriser WARI. Découvrez comment optimiser votre répartition financière en moin de 10 minutes.</p>
            <a href="Guide_utilisation_WARI.pdf" download class="btn-download">
                Télécharger le guide
            </a>
        </section>

        <!-- ── FOOTER ─────────────────────────────────────────────────── -->
        <footer>
            <p>WARI — Finance : application de finance personnelle · design slate · mobile first</p>
            <p style="margin-top: 0.8rem;">Suivi épargne, train de vie, projet, imprévu — avec pourcentages clairs.<br>© 2026 Digiroys · Afrique</p>
        </footer>

    </main>
</body>

</html>
<?php
$price = 2500;
$product_name = "WARI Finance - Licence Pro";
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WARI Finance — Licence Pro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --gold: #E8A923;
            --gold-lt: #F5C347;
            --gold-dk: #B8841A;
            --bg: #07090E;
            --s1: #0C0F17;
            --s2: #131824;
            --s3: #1A2030;
            --text: #EEF0F6;
            --muted: #6B7491;
            --radius: 20px;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding: 2rem 1rem 4rem;
            overflow-x: hidden;
        }

        /* ── Noise overlay ── */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='1'/%3E%3C/svg%3E");
            opacity: .03;
            pointer-events: none;
            z-index: 0;
        }

        /* ── Glow blob ── */
        .blob {
            position: fixed;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(232, 169, 35, .12) 0%, transparent 70%);
            border-radius: 50%;
            top: -150px;
            left: 50%;
            transform: translateX(-50%);
            pointer-events: none;
            z-index: 0;
            animation: float 8s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateX(-50%) translateY(0);
            }

            50% {
                transform: translateX(-50%) translateY(40px);
            }
        }

        /* ── Layout ── */
        .wrap {
            position: relative;
            z-index: 1;
            max-width: 960px;
            margin: 0 auto;
        }

        /* ── Top badge ── */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            border: 1px solid rgba(232, 169, 35, .35);
            background: rgba(232, 169, 35, .07);
            padding: .4rem 1rem;
            border-radius: 50px;
            font-size: .78rem;
            font-weight: 500;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--gold-lt);
            margin-bottom: 1.5rem;
            animation: fadeUp .6s ease both;
        }

        .badge .dot {
            width: 6px;
            height: 6px;
            background: var(--gold);
            border-radius: 50%;
            box-shadow: 0 0 8px var(--gold);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1
            }

            50% {
                opacity: .3
            }
        }

        /* ── Hero ── */
        .hero {
            text-align: center;
            margin-bottom: 2.5rem;
            animation: fadeUp .7s .1s ease both;
        }

        .hero-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: clamp(2.2rem, 5.5vw, 3.8rem);
            font-weight: 800;
            line-height: 1.12;
            letter-spacing: -.01em;
            margin-bottom: 1rem;
        }

        .hero-title span {
            background: linear-gradient(120deg, var(--gold-lt), var(--gold), var(--gold-dk));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-sub {
            color: var(--muted);
            font-size: 1.05rem;
            max-width: 480px;
            margin: 0 auto;
            line-height: 1.65;
        }

        /* ── Bento Grid ── */
        .bento {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            grid-auto-rows: auto;
            gap: 1rem;
            animation: fadeUp .8s .2s ease both;
        }

        .card {
            background: var(--s1);
            border: 1px solid rgba(255, 255, 255, .055);
            border-radius: var(--radius);
            padding: 1.6rem;
            position: relative;
            overflow: hidden;
            transition: border-color .3s, transform .3s;
        }

        .card:hover {
            border-color: rgba(232, 169, 35, .3);
            transform: translateY(-2px);
        }

        .card::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(ellipse at top left, rgba(232, 169, 35, .05), transparent 65%);
            pointer-events: none;
        }

        /* Grid placement */
        .card-price {
            grid-column: span 7;
        }

        .card-lifetime {
            grid-column: span 5;
        }

        .card-features {
            grid-column: span 5;
        }

        .card-payment {
            grid-column: span 7;
        }

        .card-trust {
            grid-column: span 12;
        }

        @media (max-width: 680px) {

            .card-price,
            .card-lifetime,
            .card-features,
            .card-payment,
            .card-trust {
                grid-column: span 12;
            }
        }

        /* ── Price card ── */
        .card-price {
            background: linear-gradient(135deg, var(--s2), var(--s1));
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 220px;
        }

        .price-label {
            font-size: .75rem;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: .5rem;
        }

        .price-amount {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: clamp(3rem, 7vw, 5rem);
            font-weight: 800;
            line-height: 1;
            color: var(--gold-lt);
            letter-spacing: -.02em;
        }

        .price-unit {
            font-family: 'DM Sans', sans-serif;
            font-size: 1.1rem;
            font-weight: 400;
            color: var(--gold);
            margin-left: .3rem;
        }

        .price-note {
            font-size: .82rem;
            color: var(--muted);
            margin-top: .5rem;
        }

        .price-strike {
            text-decoration: line-through;
            color: var(--muted);
            font-size: .9rem;
            margin-right: .4rem;
        }

        .price-save {
            background: rgba(232, 169, 35, .15);
            border: 1px solid rgba(232, 169, 35, .3);
            color: var(--gold-lt);
            border-radius: 50px;
            padding: .2rem .7rem;
            font-size: .75rem;
            font-weight: 600;
            display: inline-block;
            margin-top: .8rem;
        }

        /* ── Lifetime card ── */
        .card-lifetime {
            background: linear-gradient(145deg, rgba(232, 169, 35, .12), rgba(232, 169, 35, .03));
            border-color: rgba(232, 169, 35, .2);
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
            gap: .9rem;
        }

        .lifetime-icon {
            font-size: 2.5rem;
            filter: drop-shadow(0 0 12px rgba(232, 169, 35, .5));
        }

        .lifetime-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.3rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .lifetime-desc {
            font-size: .88rem;
            color: var(--muted);
            line-height: 1.55;
        }

        /* ── Features card ── */
        .card-features {
            min-height: 280px;
        }

        .features-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 1.2rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .08em;
            font-size: .72rem;
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: .8rem;
            padding: .6rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, .04);
        }

        .feature-item:last-child {
            border-bottom: none;
        }

        .feat-check {
            width: 20px;
            height: 20px;
            background: rgba(232, 169, 35, .15);
            border: 1px solid rgba(232, 169, 35, .3);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: .7rem;
            color: var(--gold);
            margin-top: 1px;
        }

        .feat-text {
            font-size: .9rem;
            color: var(--text);
            line-height: 1.4;
        }

        .feat-sub {
            font-size: .76rem;
            color: var(--muted);
        }

        /* ── Payment card ── */
        .card-payment .pay-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 1.2rem;
        }

        .pay-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .75rem;
        }

        .pay-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .4rem;
            padding: 1.1rem .8rem;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, .08);
            background: var(--s2);
            color: var(--text);
            text-decoration: none;
            font-size: .82rem;
            font-weight: 500;
            cursor: pointer;
            transition: all .25s ease;
            position: relative;
            overflow: hidden;
        }

        .pay-btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(232, 169, 35, .1), transparent);
            opacity: 0;
            transition: opacity .25s;
        }

        .pay-btn:hover {
            border-color: rgba(232, 169, 35, .45);
            transform: translateY(-2px);
        }

        .pay-btn:hover::before {
            opacity: 1;
        }

        .pay-icon {
            font-size: 1.6rem;
        }

        .pay-name {
            color: var(--text);
        }

        .pay-tag {
            font-size: .68rem;
            color: var(--muted);
        }

        .pay-btn.primary {
            grid-column: span 2;
            flex-direction: row;
            background: linear-gradient(135deg, var(--gold), var(--gold-dk));
            border: none;
            color: #07090E;
            font-weight: 700;
            font-size: .95rem;
            padding: 1.2rem;
            gap: .6rem;
            margin-top: .3rem;
        }

        .pay-btn.primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(232, 169, 35, .35);
            filter: brightness(1.05);
        }

        .pay-btn.primary::before {
            display: none;
        }

        /* ── Video card ── */
        .card-video {
            grid-column: span 12;
            padding: 1.8rem;
            background: linear-gradient(135deg, var(--s2), var(--s1));
            border-color: rgba(232, 169, 35, .2);
        }

        .video-header {
            display: flex;
            align-items: center;
            gap: .8rem;
            margin-bottom: 1.2rem;
        }

        .video-pill {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            background: rgba(232, 169, 35, .12);
            border: 1px solid rgba(232, 169, 35, .3);
            color: var(--gold-lt);
            border-radius: 50px;
            padding: .35rem .9rem;
            font-size: .75rem;
            font-weight: 600;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .video-pill .rec {
            width: 7px;
            height: 7px;
            background: var(--gold);
            border-radius: 50%;
            box-shadow: 0 0 8px var(--gold);
            animation: pulse 1.5s infinite;
        }

        .video-label {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.15rem;
            font-weight: 700;
        }

        .video-sub {
            font-size: .85rem;
            color: var(--muted);
            margin-left: auto;
        }

        .video-frame-wrap {
            position: relative;
            width: 100%;
            padding-top: 56.25%;
            /* 16:9 */
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid rgba(232, 169, 35, .15);
            box-shadow: 0 20px 60px rgba(0, 0, 0, .5), 0 0 0 1px rgba(232, 169, 35, .08);
        }

        .video-frame-wrap iframe {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            border: none;
        }

        /* ── Trust strip ── */
        .card-trust {
            display: flex;
            align-items: center;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 1rem;
            padding: 1.2rem 2rem;
            border-color: rgba(255, 255, 255, .04);
            background: rgba(255, 255, 255, .015);
        }

        .trust-item {
            display: flex;
            align-items: center;
            gap: .6rem;
            font-size: .82rem;
            color: var(--muted);
        }

        .trust-icon {
            font-size: 1.1rem;
            filter: grayscale(.3);
        }

        .card-payment {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 25px;
            max-width: 550px;
            margin: 20px auto;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            color: #fff;
        }

        .pay-title {
            font-size: 1.2rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 10px;
            background: linear-gradient(90deg, #ffd700, #ffa500);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .pay-subtitle {
            text-align: center;
            color: #bbb;
            font-size: 0.9rem;
            margin-bottom: 20px;
            line-height: 1.4;
        }

        .input-group {
            position: relative;
            margin-bottom: 15px;
        }

        /* Style du champ email sans fond blanc */
        .pay-input {
            width: 100%;
            padding: 14px 15px;
            background: rgba(255, 255, 255, 0.07);
            /* Fond sombre/transparent */
            border: 1.5px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s ease;
            outline: none;
            box-sizing: border-box;
        }

        .pay-input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .pay-input:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: #ffa500;
            box-shadow: 0 0 15px rgba(254, 165, 0, 0.2);
        }

        /* Bouton Premium */
        .pay-btn-modern {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #ffa500 0%, #ff8c00 100%);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .pay-btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 140, 0, 0.4);
        }

        .pay-btn-modern:active {
            transform: translateY(0);
        }

        /* ── Fade-up keyframe ── */
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ── Shimmer on price ── */
        @keyframes shimmer {
            0% {
                background-position: -400px 0;
            }

            100% {
                background-position: 400px 0;
            }
        }

        .price-amount {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(90deg, var(--gold-dk) 0%, var(--gold-lt) 50%, var(--gold-dk) 100%);
            background-size: 400px 100%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: shimmer 4s linear infinite;
        }

        /* ── Bottom footer ── */
        .foot {
            text-align: center;
            margin-top: 2rem;
            color: var(--muted);
            font-size: .82rem;
            animation: fadeUp .9s .4s ease both;
        }

        .foot a {
            color: var(--gold);
            text-decoration: none;
        }

        .foot a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="blob"></div>

    <div class="wrap">

        <!-- Badge -->
        <div style="text-align:center; animation: fadeUp .5s ease both;">
            <span class="badge">
                <span class="dot"></span>
                Offre de lancement disponible
            </span>
        </div>

        <!-- Hero -->
        <div class="hero">
            <h1 class="hero-title">
                Prends le contrôle<br>de ton <span>argent</span>
            </h1>
            <p class="hero-sub">
                Une seule fois. Accès à vie. Zéro abonnement.<br>
                WARI Finance transforme tes finances dès aujourd'hui.
            </p>
        </div>

        <!-- Bento Grid -->
        <div class="bento">

            <!-- Price -->
            <div class="card card-price">
                <div>
                    <div class="price-label">Paiement unique</div>
                    <div class="price-amount">
                        <?= number_format($price, 0, '', '&nbsp;') ?><span class="price-unit">F CFA</span>
                    </div>
                    <div class="price-note">
                        <span class="price-strike">5 000 F CFA</span>
                        <span class="price-save">— 50% de réduction</span>
                    </div>
                </div>
                <p class="price-note" style="margin-top:1rem;">
                    Activation instantanée par e-mail dès réception du paiement.
                </p>
            </div>

            <!-- Lifetime -->
            <div class="card card-lifetime">
                <div class="lifetime-icon">♾️</div>
                <div class="lifetime-title">Licence à vie,<br>sans condition</div>
                <div class="lifetime-desc">
                    Aucun frais mensuel. Toutes les mises à jour incluses, pour toujours.
                </div>
            </div>

            <!-- Features -->
            <div class="card card-features">
                <div class="features-title">Ce que tu obtiens</div>

                <div class="feature-item">
                    <div class="feat-check">✦</div>
                    <div>
                        <div class="feat-text">Budget automatique intelligent</div>
                        <div class="feat-sub">Catégorisation et alertes en temps réel</div>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feat-check">✦</div>
                    <div>
                        <div class="feat-text">Objectifs financiers clairs</div>
                        <div class="feat-sub">Épargne, voyages, projets — tout organisé</div>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feat-check">✦</div>
                    <div>
                        <div class="feat-text">Suivi des dépenses sans effort</div>
                        <div class="feat-sub">Tableaux de bord visuels et rapports mensuels</div>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feat-check">✦</div>
                    <div>
                        <div class="feat-text">Conseils personnalisés IA</div>
                        <div class="feat-sub">Recommandations adaptées à ton profil</div>
                    </div>
                </div>
            </div>

            <!-- Payment -->
            <div class="card-payment">
                <div class="pay-title">💳 WARI PRO LICENCE</div>
                <p class="pay-subtitle">
                    Libérez toute la puissance de Wari. Payez une seule fois, profitez à vie. Votre licence Pro arrive dans votre boîte mail juste après validation.
                </p>

                <form action="fedapay-checkout.php" method="POST">
                    <div class="input-group">
                        <input type="email" name="customer_email" class="pay-input" placeholder="Votre adresse email..." required>
                    </div>

                    <button type="submit" class="pay-btn-modern">
                        <span>⚡</span>
                        <span>Payer 2 500 F CFA</span>
                    </button>
                </form>
            </div>

            <!-- Video Demo -->
            <div class="card card-video">
                <div class="video-header">
                    <div class="video-pill"><span class="rec"></span> Démo live</div>
                    <div class="video-label">Vois WARI Finance en action</div>
                    <div class="video-sub">2 min pour tout comprendre</div>
                </div>
                <div class="video-frame-wrap">
                    <iframe
                        src="https://www.youtube.com/embed/j9r_wSjByQ8?rel=0&modestbranding=1&color=white"
                        title="WARI Finance — Démo"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                    </iframe>
                </div>
            </div>

            <!-- Trust -->
            <div class="card card-trust">
                <div class="trust-item"><span class="trust-icon">🔒</span> Paiement 100% sécurisé</div>
                <div class="trust-item"><span class="trust-icon">⚡</span> Activation instantanée</div>
                <div class="trust-item"><span class="trust-icon">♾️</span> Accès à vie garanti</div>
                <div class="trust-item"><span class="trust-icon">📧</span> Code envoyé par email</div>
                <div class="trust-item"><span class="trust-icon">🤝</span> Support réactif</div>
            </div>

        </div><!-- /bento -->

        <!-- Footer -->
        <div class="foot">
            Des questions ? <a href="mailto:support@wari.digiroys.com">support@wari.digiroys.com</a>
            &nbsp;·&nbsp; © <?= date('Y') ?> WARI Finance by Digiroys
        </div>

    </div><!-- /wrap -->
</body>

</html>
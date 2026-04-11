<?php
$price = 2500;
$product_name = "WARI | Licence Pro";
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $product_name ?></title>

    <!-- Balises SEO de base -->
    <meta name="description" content="Activez votre Licence Pro WARI. Gérez votre budget, épargnez et maîtrisez vos finances à vie avec WARI.">
    <meta name="keywords" content="Wari, gestion budget, épargne, finance personnelle, Afrique, licence pro">
    <meta name="author" content="Wari">

    <!-- Open Graph / Facebook / WhatsApp (Ce qui s'affiche quand on partage le lien) -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://wari.digiroys.com/paid/">
    <meta property="og:title" content="WARI Finance — Activez votre Licence Pro">
    <meta property="og:description" content="Prenez le contrôle de votre argent. Accès à vie, Wari Academy incluse.">
    <meta property="og:image" content="https://wari.digiroys.com/assets/wari_og_1.png"> <!-- METTEZ UNE BELLE IMAGE ICI -->

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://wari.digiroys.com/paid/">
    <meta property="twitter:title" content="WARI Finance — Activez votre Licence Pro">
    <meta property="twitter:description" content="Gérez vos finances comme un champion. Accès à vie sans abonnement.">
    <meta property="twitter:image" content="https://wari.digiroys.com/assets/wari_og_1.png">

    <!-- Favicon (L'icône dans l'onglet du navigateur) -->
    <link rel="icon" type="image/png" href="../assets/warifinance3d.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
    <style>
        :root {
            --gold: #E8A923;
            --gold-lt: #F5C347;
            --gold-dk: #B8841A;
            --bg: #07090E;
            --s1: #0C0F17;
            --s2: #131824;
            --text: #EEF0F6;
            --muted: #6B7491;
            --radius: 24px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            overflow-x: hidden;
        }

        .blob {
            position: fixed;
            width: 800px;
            height: 800px;
            background: radial-gradient(circle, rgba(232, 169, 35, .08) 0%, transparent 70%);
            top: -200px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 0;
            pointer-events: none;
        }

        .wrap { position: relative; z-index: 1; max-width: 1000px; width: 100%; }

        .checkout-container {
            display: grid;
            grid-template-columns: 1fr 420px;
            gap: 4rem;
            align-items: center;
        }

        @media (max-width: 900px) {
            .checkout-container { grid-template-columns: 1fr; gap: 2.5rem; text-align: center; }
            .feature-list { display: inline-block; text-align: left; }
            .product-info { order: 2; }
            .payment-box { order: 1; }
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            border: 1px solid rgba(232, 169, 35, .3);
            background: rgba(232, 169, 35, .07);
            padding: .4rem 1rem;
            border-radius: 50px;
            font-size: .75rem;
            font-weight: 600;
            color: var(--gold-lt);
            margin-bottom: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .dot { width: 6px; height: 6px; background: var(--gold); border-radius: 50%; box-shadow: 0 0 8px var(--gold); }

        .product-title {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: clamp(2.5rem, 5vw, 3.5rem);
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
        }

        .product-title span {
            background: linear-gradient(to right, #fff, var(--gold));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .feature-list { list-style: none; margin: 2rem 0; }
        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
            font-size: 1.05rem;
            color: var(--muted);
        }
        .feature-item svg { color: var(--gold); flex-shrink: 0; }

        .payment-box {
            background: var(--s1);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: var(--radius);
            padding: 2.5rem;
            box-shadow: 0 40px 80px -20px rgba(0, 0, 0, 0.6);
            position: relative;
            overflow: hidden;
        }

        .payment-box::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
        }

        .order-summary {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .price-label { font-size: 0.85rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.5rem; }
        .price-value { font-family: 'Plus Jakarta Sans'; font-size: 3.5rem; font-weight: 800; color: var(--gold-lt); line-height: 1; }
        .price-currency { font-size: 1.2rem; font-weight: 400; margin-left: 5px; }

        .discount-pill {
            display: inline-block;
            background: rgba(232, 169, 35, 0.1);
            color: var(--gold-lt);
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            margin-top: 10px;
        }

        .pay-input {
            width: 100%;
            padding: 16px;
            background: rgba(255, 255, 255, 0.03);
            border: 1.5px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            color: #fff;
            font-size: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            outline: none;
        }

        .pay-input:focus { border-color: var(--gold); background: rgba(255, 255, 255, 0.06); box-shadow: 0 0 20px rgba(232, 169, 35, 0.1); }

        .payment-methods-label {
            font-size: 0.75rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 12px;
            display: block;
            font-weight: 600;
        }

        .pay-btn-modern {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 14px;
            font-size: 1rem;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            transition: all 0.3s ease;
            margin-bottom: 10px;
            text-decoration: none;
        }

        /* FedaPay Style */
        .btn-fedapay {
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dk) 100%);
            color: #07090E;
        }

        /* CinetPay Style */
        .btn-cinetpay {
            background: #ffffff;
            color: #000;
            border: 1px solid #ddd;
        }

        .pay-btn-modern:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(232, 169, 35, 0.2); filter: brightness(1.1); }

        .trust-strip {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 1.5rem;
            opacity: 0.5;
            font-size: 0.75rem;
        }

        .trust-item-small { display: flex; align-items: center; gap: 5px; }

        .footer { text-align: center; margin-top: 3rem; color: var(--muted); font-size: 0.8rem; }
    </style>
</head>

<body>
    <div class="blob"></div>

    <div class="wrap">
        <div class="checkout-container">
            
            <!-- GAUCHE : RECAPITULATIF & VALEUR -->
            <div class="product-info">
                <span class="badge"><span class="dot"></span> Offre de lancement</span>
                <h1 class="product-title">Activez votre<br><span>Licence Pro</span></h1>
                
                <ul class="feature-list">
                    <li class="feature-item">
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"></path></svg>
                        <span>Accès à vie — <strong>Zéro abonnement</strong></span>
                    </li>
                    <li class="feature-item">
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"></path></svg>
                        <span>Accès à vie à l'appli <strong>Wari-Finance</strong></span>
                    </li>
                    <li class="feature-item">
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"></path></svg>
                        <span>Accès à vie à <strong>Wari Academy</strong></span>
                    </li>
                    <li class="feature-item">
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"></path></svg>
                        <span>Accès illimité à <strong>Tout autres services Wari</strong></span>
                    </li>
                </ul>

                <div style="margin-top: 2rem; color: var(--muted); font-size: 0.9rem; line-height: 1.6;">
                    <p>🔒 Paiement 100% sécurisé.</p>
                    <p>⚡ Votre code d'activation sera envoyé instantanément par email.</p>
                </div>
            </div>

            <!-- DROITE : PAIEMENT -->
            <div class="payment-box">
                <div class="order-summary">
                    <div class="price-label">Total unique</div>
                    <div class="price-value">
                        <?= number_format($price, 0, '', '&nbsp;') ?><span class="price-currency">F CFA</span>
                    </div>
                    <div class="discount-pill">Un paiement unique, pour toujours</div>
                </div>

                <form action="fedapay-checkout.php" method="POST">
                    <label style="display:block; margin-bottom: 8px; font-size: 0.85rem; color: var(--muted); font-weight: 500;">Email de livraison</label>
                    <input type="email" name="customer_email" class="pay-input" placeholder="votre@email.com" required>

                    <span class="payment-methods-label">Choisir un moyen de paiement</span>

                    <!-- Moyen 1 : FedaPay (Actif) -->
                    <button type="submit" class="pay-btn-modern btn-fedapay">
                        <span>💳</span>
                        <span>Payer avec FedaPay</span>
                    </button>
                </form>

                <!-- Moyen 2 : CinetPay (Commenté en attente d'activation business) -->
                
                <!-- <form action="cinetpay-checkout.php" method="POST">
                    <input type="hidden" name="customer_email" value="..."> 
                    <button type="submit" class="pay-btn-modern btn-cinetpay">
                        <img src="https://cinetpay.com/img/logo.png" alt="CinetPay" style="height: 20px;">
                        <span>Payer avec CinetPay</span>
                    </button>
                </form> -->
               

                <div class="trust-strip">
                    <div class="trust-item-small">🛡️ Sécurisé</div>
                    <div class="trust-item-small">🚀 Instantané</div>
                    <div class="trust-item-small">💎 Premium</div>
                </div>
            </div>

        </div>

        <div class="footer">
            © <?= date('Y') ?> WARI Finance by Digiroys — <a href="mailto:financewari1@gmail.com" style="color: var(--gold); text-decoration: none;">Besoin d'aide ?</a>
        </div>
    </div>
</body>

</html>
<?php
require_once __DIR__ . '/../wari_monitoring.php';  // TOUJOURS EN PREMIER
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/db.php';

$userId = $_SESSION['user_id'] ?? null;
$userEmail = $_SESSION['user_email'] ?? null;
$isRecharge = false;
$commandeId = null;
$currentExpiration = null;
$isExpired = false;

if ($userId) {
    // Récupérer la licence de l'utilisateur connecté
    $stmt = $pdo->prepare("
        SELECT u.commande_id, l.date_expiration, l.statut 
        FROM wari_users u
        LEFT JOIN wari_licences l ON l.commande_id = u.commande_id
        WHERE u.id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $licInfo = $stmt->fetch();

    if ($licInfo) {
        $isRecharge = true;
        $commandeId = $licInfo['commande_id'];
        $currentExpiration = $licInfo['date_expiration'];
        if ($currentExpiration) {
            $isExpired = (strtotime($currentExpiration) < time());
        } else {
            $isExpired = true; // Jamais configuré = expiré par défaut
        }
    }
}

$price = 590; // Prix de départ (Mensuel)
$product_name = $isRecharge ? "WARI | Recharge d'Accès" : "WARI | Licence Pro";
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $product_name ?></title>

    <!-- Balises SEO -->
    <meta name="description" content="Activez ou rechargez votre Licence Pro WARI. Gérez votre budget, épargnez et maîtrisez vos finances avec WARI.">
    <meta name="keywords" content="Wari, gestion budget, épargne, finance personnelle, Afrique, licence pro, abonnement">
    <meta name="author" content="Wari">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://wari.digiroys.com/paid/">
    <meta property="og:title" content="WARI Finance — Abonnement & Recharge">
    <meta property="og:description" content="Prenez le contrôle de votre argent. Rechargez votre temps d'accès MTN MoMo, Orange Money, Wave.">
    <meta property="og:image" content="https://wari.digiroys.com/assets/wari_og_1.png">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://wari.digiroys.com/paid/">
    <meta property="twitter:title" content="WARI Finance — Abonnement & Recharge">
    <meta property="twitter:description" content="Gérez vos finances comme un champion. Recharge de temps d'accès ultra simple.">
    <meta property="twitter:image" content="https://wari.digiroys.com/assets/wari_og_1.png">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/warifinance3d.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Quicksand:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
            --border: rgba(255, 255, 255, 0.05);
            --plan-bg: rgba(255, 255, 255, 0.02);
            --plan-border: rgba(255, 255, 255, 0.08);
            --input-bg: rgba(255, 255, 255, 0.03);
            --input-border: rgba(255, 255, 255, 0.1);
            --radius: 24px;
        }

        .light-mode {
            --bg: #F8FAFC;
            --s1: #FFFFFF;
            --s2: #F1F5F9;
            --text: #0F172A;
            --muted: #64748B;
            --border: rgba(15, 23, 42, 0.06);
            --plan-bg: rgba(15, 23, 42, 0.02);
            --plan-border: rgba(15, 23, 42, 0.06);
            --input-bg: rgba(15, 23, 42, 0.02);
            --input-border: rgba(15, 23, 42, 0.08);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Quicksand', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            overflow-x: hidden;
            transition: background 0.3s ease, color 0.3s ease;
        }

        .blob {
            position: fixed;
            width: 800px;
            height: 800px;
            background: radial-gradient(circle, rgba(232, 169, 35, .06) 0%, transparent 70%);
            top: -200px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 0;
            pointer-events: none;
        }

        .light-mode .blob {
            background: radial-gradient(circle, rgba(232, 169, 35, .03) 0%, transparent 70%);
        }

        .wrap { position: relative; z-index: 1; max-width: 1000px; width: 100%; }

        .checkout-container {
            display: grid;
            grid-template-columns: 1fr 440px;
            gap: 4rem;
            align-items: center;
        }

        @media (max-width: 900px) {
            .checkout-container { grid-template-columns: 1fr; gap: 2.5rem; justify-content: center; }
            .feature-list { display: inline-block; text-align: left; }
            .product-info { order: 2; }
            .payment-box { order: 1; }
        }

        @media (max-width: 600px) {
            body { padding: 1rem 0.5rem; }
            .checkout-container { grid-template-columns: 1fr; gap: 1rem; max-width: 460px; margin: 0 auto; }
            .product-info { display: none; }
            .payment-box { padding: 1.8rem 1.2rem; border-radius: 20px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); }
            .plans-container { grid-template-columns: 1fr; gap: 10px; }
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
            font-size: clamp(2.2rem, 5vw, 3.2rem);
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
        }

        .product-title span {
            background: linear-gradient(to right, var(--text), var(--gold));
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
        .feature-item span strong { color: var(--text); }

        .payment-box {
            background: var(--s1);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 2.5rem;
            box-shadow: 0 40px 80px -20px rgba(0, 0, 0, 0.6);
            position: relative;
            overflow: hidden;
            transition: background 0.3s, border 0.3s;
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
            border-bottom: 1px solid var(--border);
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

        /* Status card */
        .status-alert-box {
            display: flex;
            gap: 12px;
            padding: 16px;
            border-radius: 16px;
            background: var(--plan-bg);
            border: 1px solid var(--border);
            text-align: left;
            margin-bottom: 1.5rem;
        }
        .status-alert-box.expired {
            border-color: rgba(239, 68, 68, 0.2);
            background: rgba(239, 68, 68, 0.05);
        }
        .status-alert-box.active {
            border-color: rgba(16, 185, 129, 0.2);
            background: rgba(16, 185, 129, 0.05);
        }
        .status-alert-icon { font-size: 1.5rem; flex-shrink: 0; display: flex; align-items: center; }
        .status-alert-content { display: flex; flex-direction: column; }
        .status-alert-title { font-weight: 700; font-size: 0.95rem; margin-bottom: 2px; }
        .status-alert-box.expired .status-alert-title { color: #ef4444; }
        .status-alert-box.active .status-alert-title { color: #10b981; }
        .status-alert-desc { font-size: 0.8rem; color: var(--muted); line-height: 1.4; }
        .status-alert-desc strong { color: var(--text); }

        /* Plans selector CSS */
        .plans-container { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin: 1.5rem 0; }
        .plan-card {
            background: var(--plan-bg);
            border: 1.5px solid var(--plan-border);
            border-radius: 16px;
            padding: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            text-align: left;
        }
        .plan-card:hover {
            border-color: rgba(232, 169, 35, 0.4);
            background: var(--s2);
            transform: translateY(-2px);
        }
        .plan-card.active {
            border-color: var(--gold);
            background: rgba(232, 169, 35, 0.06);
            box-shadow: 0 0 15px rgba(232, 169, 35, 0.1);
        }
        .plan-badge {
            position: absolute;
            top: 10px; right: 10px;
            font-size: 0.65rem;
            text-transform: uppercase;
            font-weight: 700;
            background: var(--s2);
            padding: 2px 8px;
            border-radius: 20px;
            color: var(--text);
            border: 1px solid var(--border);
        }
        .plan-badge.gold-badge {
            background: rgba(232, 169, 35, 0.2);
            color: var(--gold-lt);
            border: 1px solid rgba(232, 169, 35, 0.3);
        }
        .plan-name {
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--muted);
            margin-bottom: 5px;
        }
        .plan-card.active .plan-name { color: var(--text); }
        .plan-price {
            font-family: 'Plus Jakarta Sans';
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 5px;
            display: flex;
            align-items: baseline;
        }
        .plan-card.active .plan-price { color: var(--gold); }
        .plan-curr { font-size: 0.8rem; font-weight: 500; margin-left: 4px; }
        .plan-desc { font-size: 0.75rem; color: var(--muted); line-height: 1.3; }

        .pay-input {
            width: 100%;
            padding: 16px;
            background: var(--input-bg);
            border: 1.5px solid var(--input-border);
            border-radius: 14px;
            color: var(--text);
            font-family: 'Quicksand', sans-serif;
            font-size: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            outline: none;
        }

        .pay-input:focus { border-color: var(--gold); background: var(--s1); box-shadow: 0 0 20px rgba(232, 169, 35, 0.1); }

        .payment-methods-label {
            font-size: 0.75rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 12px;
            display: block;
            font-weight: 600;
            text-align: left;
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
            font-family: 'Quicksand', sans-serif;
        }

        .btn-fedapay {
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dk) 100%);
            color: #07090E;
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
    <!-- Script d'initialisation immédiate du thème -->
    <script>
        const savedTheme = localStorage.getItem('wari_theme') || 'dark';
        if (savedTheme === 'light') {
            document.documentElement.classList.add('light-mode');
            document.body.classList.add('light-mode');
        }
    </script>

    <div class="blob"></div>

    <div class="wrap">
        <div class="checkout-container">
            
            <!-- GAUCHE : RECAPITULATIF & VALEUR (Masqué sur mobile) -->
            <div class="product-info">
                <?php if ($isRecharge): ?>
                    <span class="badge"><span class="dot"></span> Renouvellement</span>
                    <h1 class="product-title">Prolongez votre<br><span>Accès Wari</span></h1>
                <?php else: ?>
                    <span class="badge"><span class="dot"></span> Nouveau Compte</span>
                    <h1 class="product-title">Activez votre<br><span>Licence Wari</span></h1>
                <?php endif; ?>
                
                <ul class="feature-list">
                    <li class="feature-item">
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"></path></svg>
                        <span>Accès complet à l'application <strong>Wari-Finance</strong></span>
                    </li>
                    <li class="feature-item">
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"></path></svg>
                        <span>Gestion enveloppes, dettes et coffre-fort</span>
                    </li>
                    <li class="feature-item">
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"></path></svg>
                        <span>Accès à <strong>Wari Academy</strong></span>
                    </li>
                    <li class="feature-item">
                        <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"></path></svg>
                        <span>Discipline budgétaire augmentée avec le <strong>Coach IA</strong></span>
                    </li>
                </ul>

                <div style="margin-top: 2rem; color: var(--muted); font-size: 0.9rem; line-height: 1.6;">
                    <p>Paiement 100% sécurisé.</p>
                    <p>Activation ou prolongation instantanée de votre temps de licence.</p>
                </div>
            </div>

            <!-- DROITE : PAIEMENT (Interface principale mobile-snug) -->
            <div class="payment-box">
                
                <?php if ($isRecharge): ?>
                    <!-- Statut de l'abonnement actuel -->
                    <div class="status-alert-box <?= $isExpired ? 'expired' : 'active' ?>">
                        <div class="status-alert-icon">
                            <?php if ($isExpired): ?>
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                            <?php else: ?>
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            <?php endif; ?>
                        </div>
                        <div class="status-alert-content">
                            <div class="status-alert-title"><?= $isExpired ? 'Abonnement Expiré' : 'Abonnement Actif' ?></div>
                            <div class="status-alert-desc">
                                <?php if ($currentExpiration): ?>
                                    <?= $isExpired ? 'Votre accès a expiré le' : 'Votre accès est valide jusqu\'au' ?> <strong><?= date('d/m/Y à H:i', strtotime($currentExpiration)) ?></strong>.
                                <?php else: ?>
                                    Aucun abonnement actif enregistré sur votre licence.
                                <?php endif; ?>
                                <br>Rechargez ci-dessous pour continuer.
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="order-summary">
                    <div class="price-label" id="display-price-label">Total Mensuel</div>
                    <div class="price-value" id="display-price-value">
                        <?= number_format($price, 0, '', '&nbsp;') ?><span class="price-currency">F CFA</span>
                    </div>
                    <div class="discount-pill" id="discount-pill-id">Accès pendant 30 jours complets</div>
                </div>

                <form action="fedapay-checkout.php" method="POST">
                    <input type="hidden" name="plan" id="selected-plan-input" value="mensuel">

                    <?php if ($isRecharge): ?>
                        <div class="user-locked-info" style="margin-bottom: 20px; text-align: left;">
                            <label style="display:block; margin-bottom: 6px; font-size: 0.85rem; color: var(--muted); font-weight: 500;">Compte à recharger</label>
                            <div style="background: var(--plan-bg); border: 1px solid var(--border); padding: 12px 16px; border-radius: 12px; font-size: 0.95rem; font-weight: 600; color: var(--gold-lt);">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                <?= htmlspecialchars($userEmail) ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <label style="display:block; margin-bottom: 8px; font-size: 0.85rem; color: var(--muted); font-weight: 500;">Email de livraison de la licence</label>
                        <input type="email" name="customer_email" class="pay-input" placeholder="votre@email.com" required>
                    <?php endif; ?>

                    <span class="payment-methods-label">Sélectionner votre formule</span>
                    
                    <!-- Sélecteur de forfait -->
                    <div class="plans-container">
                        <div class="plan-card active" id="plan-mensuel" onclick="selectPlan('mensuel', 590)">
                            <div class="plan-badge">Standard</div>
                            <div class="plan-name">Mensuel</div>
                            <div class="plan-price">590 <span class="plan-curr">F CFA</span></div>
                            <div class="plan-desc">Accès 30 jours</div>
                        </div>
                        <div class="plan-card" id="plan-annuel" onclick="selectPlan('annuel', 5000)">
                            <div class="plan-badge gold-badge">Économique</div>
                            <div class="plan-name">Annuel</div>
                            <div class="plan-price">5 000 <span class="plan-curr">F CFA</span></div>
                            <div class="plan-desc">Accès 365 jours<br><strong style="color:var(--gold-lt);">Économisez 30%</strong></div>
                        </div>
                    </div>

                    <span class="payment-methods-label">Moyen de paiement sécurisé</span>

                    <button type="submit" class="pay-btn-modern btn-fedapay">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 8px;"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                        <span>Payer avec FedaPay</span>
                    </button>
                </form>

                <div class="trust-strip">
                    <div class="trust-item-small">MTN, Orange, Wave, Moov</div>
                    <div class="trust-item-small">Instantané</div>
                    <div class="trust-item-small">Premium</div>
                </div>
            </div>

        </div>

        <div class="footer">
            © <?= date('Y') ?> WARI Finance by Digiroys — <a href="mailto:wari.finance.inter@gmail.com" style="color: var(--gold); text-decoration: none;">Besoin d'aide ?</a>
        </div>
    </div>

    <script>
        function selectPlan(plan, price) {
            document.getElementById('selected-plan-input').value = plan;
            
            // Basculer la classe active
            document.getElementById('plan-mensuel').classList.toggle('active', plan === 'mensuel');
            document.getElementById('plan-annuel').classList.toggle('active', plan === 'annuel');
            
            // Mettre à jour le prix affiché
            const formattedPrice = new Intl.NumberFormat('fr-FR').format(price);
            document.getElementById('display-price-value').innerHTML = `${formattedPrice}<span class="price-currency">F CFA</span>`;
            
            // Mettre à jour le libellé
            if (plan === 'annuel') {
                document.getElementById('display-price-label').innerText = "Total Annuel (Économique)";
                document.getElementById('discount-pill-id').innerText = "Accès pendant 365 jours complets";
            } else {
                document.getElementById('display-price-label').innerText = "Total Mensuel";
                document.getElementById('discount-pill-id').innerText = "Accès pendant 30 jours complets";
            }
        }
    </script>
</body>

</html>
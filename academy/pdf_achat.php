<?php
// /var/www/html/academy/pdf_achat.php

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: https://wari.digiroys.com/login?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Chargement du .env
$envFile = '/var/www/html/wari-admin/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0 || !strpos($line, '=')) continue;
        list($name, $value) = explode('=', $line, 2);
        $name  = trim($name);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        $_ENV[$name] = $value;
    }
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Academy.php';

$academy   = new Academy($pdo);
$user_id   = $_SESSION['user_id'];

// Récupération du PDF
$pdf_id = (int)($_GET['id'] ?? 0);
if (!$pdf_id) {
    header('Location: /academy/');
    exit;
}

$stmt = $pdo->prepare("
    SELECT p.*, co.titre AS course_titre, co.slug AS course_slug,
           c.titre AS cat_titre, c.icone AS cat_icone
    FROM academy_pdfs p
    JOIN academy_courses co ON co.id = p.course_id
    JOIN academy_categories c ON c.id = co.category_id
    WHERE p.id = ? AND p.est_actif = 1
");
$stmt->execute([$pdf_id]);
$pdf = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pdf) {
    header('Location: /academy/');
    exit;
}

// Déjà acheté ? → rediriger vers download
if ($academy->hasUserBoughtPdf($user_id, $pdf_id)) {
    header('Location: /academy/pdf_download.php?id=' . $pdf_id);
    exit;
}

// Gratuit ? → téléchargement direct
if ($pdf['est_gratuit']) {
    header('Location: /academy/pdf_download.php?id=' . $pdf_id);
    exit;
}

// Récupération des infos utilisateur
$userInfo = $pdo->prepare("SELECT * FROM wari_users WHERE id = ?");
$userInfo->execute([$user_id]);
$user = $userInfo->fetch(PDO::FETCH_ASSOC);

// Génération d'une référence unique pour la transaction
$transactionRef = 'WARI_PDF_' . $pdf_id . '_' . $user_id . '_' . time();
$_SESSION['pdf_transaction_ref'] = $transactionRef;
$_SESSION['pdf_pending_id']      = $pdf_id;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Obtenir <?= htmlspecialchars($pdf['titre']) ?> — Wari Academy</title>

    <link rel="icon" type="image/png" href="../assets/warifinance3d.png" />
    <link rel="apple-touch-icon" href="../assets/warifinance3d.png" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --or:      #C9A84C;
            --or-dark: #8B6914;
            --encre:   #0F0A02;
            --creme:   #FAF5E9;
            --creme2:  #F0E8D0;
            --blanc:   #FFFFFF;
            --gris:    #7A6E60;
            --tr: .22s cubic-bezier(.4,0,.2,1);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--encre);
            min-height: 100vh;
            display: flex; flex-direction: column;
            align-items: center;
            padding: 40px 16px;
            position: relative;
            overflow-x: hidden;
        }
        body::before {
            content: '';
            position: fixed; inset: 0;
            background:
                radial-gradient(ellipse at 20% 30%, rgba(201,168,76,.07) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 70%, rgba(201,168,76,.05) 0%, transparent 50%),
                repeating-linear-gradient(45deg, transparent, transparent 40px,
                    rgba(201,168,76,.015) 40px, rgba(201,168,76,.015) 41px);
            pointer-events: none;
        }

        /* ── NAV ── */
        .nav {
            width: 100%; max-width: 560px;
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 32px; position: relative;
        }
        .nav-logo {
            font-size: 1rem; font-weight: 800;
            color: var(--or); text-decoration: none;
        }
        .nav-back {
            font-size: .78rem; color: rgba(255,255,255,.3);
            text-decoration: none; transition: color var(--tr);
        }
        .nav-back:hover { color: var(--or); }

        /* ── CARD PRINCIPALE ── */
        .card {
            width: 100%; max-width: 560px;
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(201,168,76,.15);
            border-radius: 24px;
            overflow: hidden;
            position: relative;
        }
        .card::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0; height: 2px;
            background: linear-gradient(90deg, transparent, var(--or), transparent);
        }

        /* ── HEADER PDF ── */
        .pdf-header {
            padding: 28px 32px;
            border-bottom: 1px solid rgba(201,168,76,.1);
            display: flex; gap: 18px; align-items: flex-start;
        }
        .pdf-icon {
            width: 56px; height: 56px; border-radius: 14px;
            background: rgba(201,168,76,.1);
            border: 1px solid rgba(201,168,76,.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem; flex-shrink: 0;
        }
        .pdf-info {}
        .pdf-cat {
            font-size: .68rem; font-weight: 700; letter-spacing: .1em;
            text-transform: uppercase; color: var(--or);
            margin-bottom: 6px;
        }
        .pdf-titre {
            font-size: 1.05rem; font-weight: 700;
            color: #fff; line-height: 1.3; margin-bottom: 6px;
        }
        .pdf-auteur {
            font-size: .78rem; color: rgba(255,255,255,.35);
        }
        .pdf-prix {
            font-size: 1.6rem; font-weight: 800;
            color: var(--or); margin-left: auto;
            white-space: nowrap; flex-shrink: 0;
            align-self: center;
        }
        .pdf-prix span {
            font-size: .7rem; font-weight: 500;
            color: rgba(201,168,76,.6); display: block;
            text-align: right;
        }

        /* ── BODY ── */
        .card-body { padding: 28px 32px; }

        .section-title {
            font-size: .68rem; font-weight: 700;
            letter-spacing: .12em; text-transform: uppercase;
            color: rgba(255,255,255,.25); margin-bottom: 14px;
        }

        /* ── SÉLECTION PAYS / MÉTHODE ── */
        .pays-grid {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 10px; margin-bottom: 24px;
        }
        .pays-btn {
            background: rgba(255,255,255,.04);
            border: 1.5px solid rgba(255,255,255,.06);
            border-radius: 12px;
            padding: 14px 16px;
            cursor: pointer; text-align: left;
            transition: all var(--tr);
            display: flex; align-items: center; gap: 10px;
        }
        .pays-btn:hover {
            border-color: rgba(201,168,76,.3);
            background: rgba(201,168,76,.05);
        }
        .pays-btn.selected {
            border-color: var(--or);
            background: rgba(201,168,76,.1);
        }
        .pays-btn .flag { font-size: 1.4rem; }
        .pays-btn-info {}
        .pays-btn-name {
            font-size: .82rem; font-weight: 600;
            color: #e2e8f0; line-height: 1;
        }
        .pays-btn-sub {
            font-size: .68rem; color: rgba(255,255,255,.3);
            margin-top: 2px;
        }
        .pays-btn .check {
            margin-left: auto; width: 18px; height: 18px;
            border-radius: 50%;
            border: 1.5px solid rgba(255,255,255,.15);
            display: flex; align-items: center; justify-content: center;
            font-size: .65rem; flex-shrink: 0; transition: all var(--tr);
        }
        .pays-btn.selected .check {
            background: var(--or); border-color: var(--or); color: var(--encre);
        }

        /* ── MÉTHODES DE PAIEMENT ── */
        .methodes {
            display: flex; gap: 8px; flex-wrap: wrap;
            margin-bottom: 24px;
        }
        .methode-tag {
            font-size: .7rem; font-weight: 600;
            padding: 4px 10px; border-radius: 999px;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.07);
            color: rgba(255,255,255,.4);
        }

        /* ── RÉCAPITULATIF ── */
        .recap {
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(201,168,76,.1);
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 24px;
        }
        .recap-row {
            display: flex; justify-content: space-between;
            align-items: center; padding: 6px 0;
            font-size: .82rem;
        }
        .recap-row + .recap-row {
            border-top: 1px solid rgba(255,255,255,.05);
        }
        .recap-row span:first-child { color: rgba(255,255,255,.4); }
        .recap-row span:last-child  { color: #e2e8f0; font-weight: 600; }
        .recap-total {
            font-size: .95rem !important;
            padding-top: 10px !important;
            margin-top: 4px;
        }
        .recap-total span:last-child {
            color: var(--or) !important;
            font-size: 1.1rem !important;
            font-weight: 800 !important;
        }

        /* ── CTA PAIEMENT ── */
        .btn-pay {
            width: 100%;
            background: var(--or);
            color: var(--encre);
            font-family: 'Poppins', sans-serif;
            font-size: .92rem; font-weight: 800;
            padding: 15px;
            border: none; border-radius: 12px;
            cursor: pointer; transition: all var(--tr);
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-pay:hover { background: #F0D080; transform: translateY(-1px); }
        .btn-pay:disabled { opacity: .4; cursor: not-allowed; transform: none; }

        /* ── SÉCURITÉ ── */
        .security-note {
            display: flex; align-items: center; gap: 6px; justify-content: center;
            margin-top: 14px;
            font-size: .72rem; color: rgba(255,255,255,.2);
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(14px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .card { animation: fadeUp .4s ease both; }

        @media (max-width: 480px) {
            .pdf-header { flex-wrap: wrap; }
            .pdf-prix { margin-left: 0; }
            .pays-grid { grid-template-columns: 1fr; }
            .card-body { padding: 22px 20px; }
        }
    </style>
</head>
<body>

<!-- Nav -->
<nav class="nav">
    <a href="https://wari.digiroys.com/accueil/" class="nav-logo">Wari Academy</a>
    <a href="/academy/course.php?slug=<?= urlencode($pdf['course_slug']) ?>" class="nav-back">
        ← Retour au cours
    </a>
</nav>

<!-- Card principale -->
<div class="card">

    <!-- Header PDF -->
    <div class="pdf-header">
        <div class="pdf-icon">📘</div>
        <div class="pdf-info">
            <p class="pdf-cat"><?= $pdf['cat_icone'] ?> <?= htmlspecialchars($pdf['cat_titre']) ?></p>
            <p class="pdf-titre"><?= htmlspecialchars($pdf['titre']) ?></p>
            <p class="pdf-auteur">✍️ <?= htmlspecialchars($pdf['auteur']) ?></p>
        </div>
        <div class="pdf-prix">
            <?= number_format($pdf['prix'], 0, ',', ' ') ?>
            <span>FCFA</span>
        </div>
    </div>

    <!-- Body -->
    <div class="card-body">

        <!-- Sélection du pays / opérateur -->
        <p class="section-title">Ton pays / opérateur</p>

        <div class="pays-grid">

            <!-- FedaPay — Bénin/Togo -->
            <div class="pays-btn selected" id="btn-feda" onclick="selectProvider('fedapay')">
                <span class="flag">🌍</span>
                <div class="pays-btn-info">
                    <p class="pays-btn-name">Bénin · Togo · CI</p>
                    <p class="pays-btn-sub">MTN, Moov, Carte</p>
                </div>
                <span class="check">✓</span>
            </div>

            <!-- CinetPay — Côte d'Ivoire + autres -->
            <div class="pays-btn" id="btn-cinet" onclick="selectProvider('cinetpay')">
                <span class="flag">🌍</span>
                <div class="pays-btn-info">
                    <p class="pays-btn-name">CI · SN · CM · ML...</p>
                    <p class="pays-btn-sub">Orange, MTN, Wave</p>
                </div>
                <span class="check"></span>
            </div>

        </div>

        <!-- Méthodes disponibles -->
        <div id="methodes-display" class="methodes">
            <span class="methode-tag">📱 MTN MoMo</span>
            <span class="methode-tag">📱 Moov Money</span>
            <span class="methode-tag">💳 Visa/Mastercard</span>
        </div>

        <!-- Récapitulatif -->
        <p class="section-title">Récapitulatif</p>
        <div class="recap">
            <div class="recap-row">
                <span>Guide PDF</span>
                <span><?= htmlspecialchars($pdf['titre']) ?></span>
            </div>
            <div class="recap-row">
                <span>Cours</span>
                <span><?= htmlspecialchars($pdf['course_titre']) ?></span>
            </div>
            <div class="recap-row recap-total">
                <span>Total à payer</span>
                <span><?= number_format($pdf['prix'], 0, ',', ' ') ?> FCFA</span>
            </div>
        </div>

        <!-- Bouton paiement FedaPay -->
        <div id="form-fedapay">
            <button class="btn-pay" onclick="initFedaPay()">
                🔐 Payer avec FedaPay
            </button>
        </div>

        <!-- Bouton paiement CinetPay -->
        <div id="form-cinetpay" style="display:none;">
            <button class="btn-pay" onclick="initCinetPay()">
                🔐 Payer avec CinetPay
            </button>
        </div>

        <p class="security-note">
            🔒 Paiement 100% sécurisé · Téléchargement immédiat après confirmation
        </p>

    </div>
</div>

<!-- SDK FedaPay -->
<script src="https://cdn.fedapay.com/checkout.js?v=1.1.7"></script>

<!-- SDK CinetPay -->
<script src="https://cdn.cinetpay.com/seamless/main.js"></script>

<script>
    let currentProvider = 'fedapay';

    const methodes = {
        fedapay:  ['📱 MTN MoMo', '📱 Moov Money', '💳 Visa/Mastercard'],
        cinetpay: ['📱 Orange Money', '📱 MTN MoMo', '🌊 Wave', '📱 Moov', '💳 Carte bancaire']
    };

    function selectProvider(provider) {
        currentProvider = provider;

        // Toggle UI
        document.getElementById('btn-feda').classList.toggle('selected',  provider === 'fedapay');
        document.getElementById('btn-cinet').classList.toggle('selected', provider === 'cinetpay');
        document.getElementById('btn-feda').querySelector('.check').textContent  = provider === 'fedapay'  ? '✓' : '';
        document.getElementById('btn-cinet').querySelector('.check').textContent = provider === 'cinetpay' ? '✓' : '';

        // Méthodes
        const m = methodes[provider];
        document.getElementById('methodes-display').innerHTML =
            m.map(x => `<span class="methode-tag">${x}</span>`).join('');

        // Formulaires
        document.getElementById('form-fedapay').style.display  = provider === 'fedapay'  ? 'block' : 'none';
        document.getElementById('form-cinetpay').style.display = provider === 'cinetpay' ? 'block' : 'none';
    }

    // ── FEDAPAY ──────────────────────────────────────────
    function initFedaPay() {
        let widget = FedaPay.init({
            sandbox:       <?= ($_ENV['FEDAPAY_ENV'] ?? 'live') === 'sandbox' ? 'true' : 'false' ?>,
            public_key:    "<?= $_ENV['FEDAPAY_PUBLIC_KEY'] ?? '' ?>",
            transaction: {
                amount:      <?= (int)$pdf['prix'] ?>,
                description: "<?= addslashes($pdf['titre']) ?>",
                currency:    { iso: 'XOF' },
                custom_metadata: {
                    pdf_id:    "<?= $pdf_id ?>",
                    user_id:   "<?= $user_id ?>",
                    reference: "<?= $transactionRef ?>"
                }
            },
            customer: {
                email: "<?= addslashes($user['email']) ?>",
                lastname: "<?= addslashes($user['nom'] ?? '') ?>"
            },
            onComplete: function(transaction) {
                if (transaction.reason === FedaPay.CHECKOUT_COMPLETED) {
                    // Vérification côté serveur + redirection
                    window.location.href = '/academy/pdf_achat_verify.php'
                        + '?provider=fedapay'
                        + '&transaction_id=' + transaction.transaction.id
                        + '&pdf_id=<?= $pdf_id ?>'
                        + '&ref=<?= $transactionRef ?>';
                }
            }
        });

        widget.open();
    }

    // ── CINETPAY ─────────────────────────────────────────
    function initCinetPay() {
        CinetPay.setConfig({
            apikey:        "<?= $_ENV['CINETPAY_API_KEY'] ?? '' ?>",
            site_id:       "<?= $_ENV['CINETPAY_SITE_ID'] ?? '' ?>",
            notify_url:    "https://wari.digiroys.com/academy/pdf_webhook_cinetpay.php",
            return_url:    "https://wari.digiroys.com/academy/pdf_achat_verify.php?provider=cinetpay&pdf_id=<?= $pdf_id ?>&ref=<?= $transactionRef ?>",
            mode:          "PRODUCTION",
            currency:      "XOF",
            amount:        <?= (int)$pdf['prix'] ?>,
            transaction_id:"<?= $transactionRef ?>",
            description:   "<?= addslashes($pdf['titre']) ?>",
            customer_name: "<?= addslashes($user['nom'] ?? 'Client') ?>",
            customer_email:"<?= addslashes($user['email']) ?>",
            metadata:      "pdf_id=<?= $pdf_id ?>&user_id=<?= $user_id ?>"
        });

        CinetPay.getCheckout({
            onSuccess: function(data) {
                window.location.href = '/academy/pdf_achat_verify.php'
                    + '?provider=cinetpay'
                    + '&transaction_id=' + data.transaction_id
                    + '&pdf_id=<?= $pdf_id ?>'
                    + '&ref=<?= $transactionRef ?>';
            },
            onError: function(data) {
                alert('Paiement échoué ou annulé. Réessaie.');
            }
        });
    }
</script>

</body>
</html>
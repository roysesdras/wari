<?php
// /var/www/wari.digiroys.com/paid/invoice.php
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);

require_once '../config/db.php';
require_once '../config/session_config.php';

$transaction_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$transaction_id) {
    die("ID de facture manquant.");
}

// Récupération du paiement par FedaPay reference, ID primaire ou clé de licence
$stmt = $pdo->prepare("SELECT * FROM wari_payments WHERE reference_fedapay = ? OR id = ? OR commande_id = ?");
$stmt->execute([$transaction_id, $transaction_id, $transaction_id]);
$payment = $stmt->fetch();

// Si le paiement est en attente, on tente de le mettre à jour en direct via FedaPay (Auto-healing)
if ($payment && $payment['statut'] === 'pending') {
    try {
        require_once '../vendor/autoload.php';
        \FedaPay\FedaPay::setApiKey("sk_live_-t3Pw_JoJ8VGBqP8eTZr-ar5");
        \FedaPay\FedaPay::setEnvironment('live');

        $transaction = \FedaPay\Transaction::retrieve($payment['reference_fedapay']);
        if ($transaction && $transaction->status === 'approved') {
            // Mise à jour de wari_payments
            $stmtUpdate = $pdo->prepare("UPDATE wari_payments SET statut = 'approved' WHERE id = ?");
            $stmtUpdate->execute([$payment['id']]);

            // Si recharge, prolonger la licence
            if ($payment['type_paiement'] === 'recharge') {
                $duree = intval($payment['duree_jours']);
                $commande_id = $payment['commande_id'];
                $stmtLic = $pdo->prepare("
                    UPDATE wari_licences 
                    SET date_expiration = DATE_ADD(GREATEST(COALESCE(date_expiration, NOW()), NOW()), INTERVAL ? DAY)
                    WHERE commande_id = ?
                ");
                $stmtLic->execute([$duree, $commande_id]);
            }
            
            // Recharger le statut localement
            $payment['statut'] = 'approved';
        }
    } catch (Exception $e) {
        // Silencieux
    }
}

if (!$payment || $payment['statut'] !== 'approved') {
    die("Facture non trouvée ou non approuvée.");
}

$ref = strtoupper(substr($transaction_id, 0, 8));
$date = date('d/m/Y à H:i', strtotime($payment['date_creation']));
$email = htmlspecialchars($payment['email_client']);
$price_formatted = number_format($payment['montant'], 0, '', ' ');
$duree = intval($payment['duree_jours']);
$duration_label = ($duree === 365) ? "365 jours (Annuel)" : "30 jours (Mensuel)";
$type = $payment['type_paiement'];
$desc = ($type === 'recharge') ? "Recharge d'accès Wari Finance Pro" : "Nouvel accès Wari Finance Pro (Licence)";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture WARI-INV-<?= htmlspecialchars($ref) ?> — WARI Finance</title>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #07090e;
            color: #eef0f6;
            font-family: 'Quicksand', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .no-print {
            margin-bottom: 24px;
            width: 100%;
            max-width: 600px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-action {
            background: #e8a923;
            color: #07090e;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-family: 'Quicksand', sans-serif;
            font-size: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: opacity 0.2s, transform 0.1s;
            text-decoration: none;
        }

        .btn-action:hover {
            opacity: 0.9;
        }

        .btn-action:active {
            transform: scale(0.98);
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.05);
            color: #eef0f6;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .invoice-card {
            background-color: #0c0f17;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            width: 100%;
            max-width: 600px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .header {
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding-bottom: 24px;
            margin-bottom: 24px;
        }

        .logo {
            color: #e8a923;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

        .logo span {
            color: #fff;
            font-size: 14px;
            font-weight: 400;
            letter-spacing: 3px;
        }

        .subtitle {
            color: #6b7491;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .section-title {
            font-size: 18px;
            color: #fff;
            margin-bottom: 16px;
            font-weight: 600;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            color: #6b7491;
            margin-bottom: 30px;
        }

        .details-table td {
            padding: 6px 0;
        }

        .details-table td.value {
            text-align: right;
            color: #fff;
            font-weight: 500;
        }

        .details-table td.value-highlight {
            color: #e8a923;
            font-weight: 600;
        }

        .items-box {
            background-color: #07090e;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 30px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .items-table th {
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            color: #6b7491;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-align: left;
            padding-bottom: 12px;
            font-weight: 600;
        }

        .items-table th.right, .items-table td.right {
            text-align: right;
        }

        .items-table td {
            padding: 16px 0 12px;
            color: #fff;
        }

        .total-row {
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            margin-top: 16px;
            padding-top: 16px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 12px;
        }

        .total-label {
            font-size: 13px;
            color: #6b7491;
        }

        .total-amount {
            font-size: 22px;
            font-weight: 700;
            color: #e8a923;
        }

        .footer {
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 24px;
            font-size: 11px;
            color: #6b7491;
            line-height: 1.6;
        }

        .footer p {
            margin-bottom: 6px;
        }

        .footer a {
            color: #e8a923;
            text-decoration: none;
        }

        .legal-block {
            margin-top: 12px;
            color: #525b75;
            font-size: 10px;
        }

        /* ─── OPTIMISATION IMPRESSION / PDF ─── */
        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
                padding: 0 !important;
            }

            .no-print {
                display: none !important;
            }

            .invoice-card {
                background: #ffffff !important;
                border: none !important;
                box-shadow: none !important;
                color: #000000 !important;
                max-width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .logo {
                color: #b87b00 !important;
            }

            .logo span {
                color: #000000 !important;
            }

            .subtitle {
                color: #555555 !important;
            }

            .section-title {
                color: #000000 !important;
                border-bottom: 1px solid #000000 !important;
                padding-bottom: 4px;
            }

            .details-table td {
                color: #444444 !important;
            }

            .details-table td.value {
                color: #000000 !important;
            }

            .details-table td.value-highlight {
                color: #b87b00 !important;
            }

            .items-box {
                background: #ffffff !important;
                border: 1px solid #000000 !important;
                border-radius: 8px;
                padding: 15px;
            }

            .items-table th {
                border-bottom: 1px solid #000000 !important;
                color: #000000 !important;
            }

            .items-table td {
                color: #000000 !important;
            }

            .total-row {
                border-top: 1px solid #000000 !important;
            }

            .total-label {
                color: #444444 !important;
            }

            .total-amount {
                color: #b87b00 !important;
            }

            .footer {
                border-top: 1px solid #000000 !important;
                color: #555555 !important;
            }

            .legal-block {
                color: #666666 !important;
            }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <a href="../index.php" class="btn-action btn-back">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"/>
                <polyline points="12 19 5 12 12 5"/>
            </svg>
            Retour
        </a>
        <button onclick="window.print()" class="btn-action">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 6 2 18 2 18 9"/>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                <rect x="6" y="14" width="12" height="8"/>
            </svg>
            Imprimer / PDF
        </button>
    </div>

    <div class="invoice-card">
        
        <div class="header">
            <div class="logo">WARI <span>FINANCE</span></div>
            <div class="subtitle">Discipline & Progrès</div>
        </div>

        <div class="section-title">Facture & Reçu de paiement</div>
        <table class="details-table">
            <tr>
                <td>Référence facture :</td>
                <td class="value" style="font-weight: 700;">WARI-INV-<?= htmlspecialchars($ref) ?></td>
            </tr>
            <tr>
                <td>Date de paiement :</td>
                <td class="value"><?= htmlspecialchars($date) ?></td>
            </tr>
            <tr>
                <td>Compte client :</td>
                <td class="value value-highlight"><?= $email ?></td>
            </tr>
            <tr>
                <td>Mode de règlement :</td>
                <td class="value">FedaPay (Mobile Money / Cartes)</td>
            </tr>
            <tr>
                <td>Identifiant transaction :</td>
                <td class="value" style="font-family: monospace; font-size: 12px;"><?= htmlspecialchars($transaction_id) ?></td>
            </tr>
        </table>

        <div class="items-box">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Désignation</th>
                        <th class="right">Durée</th>
                        <th class="right">Prix</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?= htmlspecialchars($desc) ?></td>
                        <td class="right"><?= htmlspecialchars($duration_label) ?></td>
                        <td class="right" style="font-weight: 600; color: #e8a923;"><?= htmlspecialchars($price_formatted) ?> F CFA</td>
                    </tr>
                </tbody>
            </table>

            <div class="total-row">
                <span class="total-label">Total payé :</span>
                <span class="total-amount"><?= htmlspecialchars($price_formatted) ?> F CFA</span>
            </div>
        </div>

        <div class="footer">
            <p>Cet e-mail tient lieu de reçu officiel de paiement.</p>
            <p>© WARI Finance by Digiroys — <a href="mailto:wari-finance@digiroys.com">Besoin d'aide ?</a></p>
            <div class="legal-block">
                Digiroys — RC: RB/COT/19 A 50622 — IFU: 0 2019 1088 5778<br>
                Contact : wari-finance@digiroys.com
            </div>
        </div>

    </div>

</body>
</html>

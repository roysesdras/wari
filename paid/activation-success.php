<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session_config.php';

// 1. Sécurité de base
$transaction_id = isset($_GET['id']) ? $_GET['id'] : ($_SESSION['payment_ref'] ?? null);

if (!$transaction_id) {
    header("Location: index.php");
    exit();
}

// Récupération immédiate du paiement (FedaPay requis)
$stmtPayment = $pdo->prepare("SELECT * FROM wari_payments WHERE reference_fedapay = ? AND reference_fedapay IS NOT NULL AND reference_fedapay != ''");
$stmtPayment->execute([$transaction_id]);
$payment = $stmtPayment->fetch();

// Si le paiement est en attente, on tente de le mettre à jour en direct via FedaPay (Auto-healing)
if ($payment && $payment['statut'] === 'pending') {
    try {
        \FedaPay\FedaPay::setApiKey("sk_live_-t3Pw_JoJ8VGBqP8eTZr-ar5");
        \FedaPay\FedaPay::setEnvironment('live');

        $transaction = \FedaPay\Transaction::retrieve($payment['reference_fedapay']);
        if ($transaction && $transaction->status === 'approved') {
            $stmtUpdate = $pdo->prepare("UPDATE wari_payments SET statut = 'approved' WHERE id = ?");
            $stmtUpdate->execute([$payment['id']]);
            $payment['statut'] = 'approved';
        }
    } catch (Exception $e) {
        // Silencieux
    }
}

if (!$payment || $payment['statut'] !== 'approved') {
    header("Location: index.php");
    exit();
}

$email = $payment['email_client'];
$duree_jours = intval($payment['duree_jours']);
$montant = intval($payment['montant']);

/**
 * FONCTION DE GÉNÉRATION
 */
function generateWariLicense()
{
    $chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $res = "";
    for ($i = 0; $i < 12; $i++) {
        $res .= $chars[mt_rand(0, strlen($chars) - 1)];
        if (($i + 1) % 4 == 0 && $i < 11) $res .= "-";
    }
    return $res;
}

// 2. LE VERROU : On vérifie si une licence a déjà été créée pour ce paiement
$new_license = null;
if (!empty($payment['commande_id'])) {
    $new_license = $payment['commande_id'];
    $_SESSION['active_license_key'] = $new_license;
} else {
    // Si non, c'est le TOUT PREMIER chargement après paiement
    $new_license = generateWariLicense();

    try {
        $pdo->beginTransaction();

        // A. Sauvegarde immédiate en base de données dans wari_licences
        $stmt = $pdo->prepare("INSERT INTO wari_licences (commande_id, statut, date_creation, duree_jours) VALUES (?, 'disponible', NOW(), ?)");
        $stmt->execute([$new_license, $duree_jours]);

        // B. Liaison du paiement à cette licence (commande_id)
        $stmtUpdatePayment = $pdo->prepare("UPDATE wari_payments SET commande_id = ? WHERE id = ?");
        $stmtUpdatePayment->execute([$new_license, $payment['id']]);

        $pdo->commit();

        // C. Envoi de l'email unique
        require_once __DIR__ . '/../classes/Mailer.php';
        $mailer = new Mailer();

        $ref = strtoupper(substr($transaction_id, 0, 8));
        $price_formatted = number_format($montant, 0, '', ' ');
        $duration_label = ($duree_jours === 365) ? "365 jours (Annuel)" : "30 jours (Mensuel)";
        $date = date('d/m/Y à H:i');
        $desc = "Nouvel accès Wari Finance Pro (Licence)";

        $body = "
        <div style='background-color: #07090e; color: #eef0f6; font-family: sans-serif; padding: 30px; border-radius: 16px; max-width: 600px; margin: 0 auto; border: 1px solid rgba(255, 255, 255, 0.05);'>
            <div style='text-align: center; margin-bottom: 30px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 20px;'>
                <h1 style='color: #e8a923; font-size: 26px; margin: 0; font-family: sans-serif;'>WARI <span style='font-size: 14px; color: #fff; font-weight: normal; letter-spacing: 2px;'>FINANCE</span></h1>
                <p style='color: #6b7491; font-size: 11px; text-transform: uppercase; letter-spacing: 2px; margin-top: 5px; margin-bottom: 0;'>Discipline & Progrès</p>
            </div>
            
            <div style='margin-bottom: 30px;'>
                <h2 style='font-size: 18px; color: #fff; margin-top: 0; margin-bottom: 15px;'>Facture & Clé de licence</h2>
                <table style='width: 100%; border-collapse: collapse; font-size: 13px; color: #6b7491;'>
                    <tr>
                        <td style='padding: 4px 0;'>Référence facture :</td>
                        <td style='padding: 4px 0; text-align: right; color: #fff; font-weight: bold;'>WARI-INV-$ref</td>
                    </tr>
                    <tr>
                        <td style='padding: 4px 0;'>Date de paiement :</td>
                        <td style='padding: 4px 0; text-align: right; color: #fff;'>$date</td>
                    </tr>
                    <tr>
                        <td style='padding: 4px 0;'>Compte client :</td>
                        <td style='padding: 4px 0; text-align: right; color: #e8a923; font-weight: bold;'>$email</td>
                    </tr>
                    <tr>
                        <td style='padding: 4px 0;'>Mode de règlement :</td>
                        <td style='padding: 4px 0; text-align: right; color: #fff;'>FedaPay (Mobile Money / Cartes)</td>
                    </tr>
                </table>
            </div>
            
            <div style='background-color: #0c0f17; border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 12px; padding: 20px; margin-bottom: 30px;'>
                <h3 style='font-size: 13px; text-transform: uppercase; color: #e8a923; margin-top: 0; margin-bottom: 15px; letter-spacing: 1px;'>Détails de la licence</h3>
                <table style='width: 100%; border-collapse: collapse; font-size: 14px;'>
                    <thead>
                        <tr style='border-bottom: 1px solid rgba(255,255,255,0.08); color: #6b7491; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;'>
                            <th style='text-align: left; padding-bottom: 10px;'>Désignation</th>
                            <th style='text-align: right; padding-bottom: 10px;'>Durée</th>
                            <th style='text-align: right; padding-bottom: 10px;'>Prix</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style='padding: 15px 0 10px; color: #fff;'>$desc</td>
                            <td style='padding: 15px 0 10px; text-align: right; color: #fff;'>$duration_label</td>
                            <td style='padding: 15px 0 10px; text-align: right; color: #e8a923; font-weight: bold;'>$price_formatted F CFA</td>
                        </tr>
                    </tbody>
                </table>
                
                <div style='border-top: 1px solid rgba(255,255,255,0.08); margin-top: 15px; padding-top: 15px; text-align: right;'>
                    <span style='font-size: 12px; color: #6b7491; margin-right: 10px;'>Total payé :</span>
                    <span style='font-size: 20px; font-weight: bold; color: #e8a923;'>$price_formatted F CFA</span>
                </div>
            </div>

            <!-- Clé de licence -->
            <div style='background-color: rgba(232, 169, 35, 0.05); border: 1px dashed rgba(232, 169, 35, 0.4); border-radius: 12px; padding: 20px; text-align: center; margin-bottom: 30px;'>
                <div style='color: #6b7491; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;'>Votre code de licence personnel</div>
                <div style='font-family: monospace; font-size: 22px; font-weight: bold; color: #e8a923; letter-spacing: 2px; padding: 10px; background-color: #0c0f17; border-radius: 8px; border: 1px solid rgba(255, 255, 255, 0.05); display: inline-block;'>
                    $new_license
                </div>
            </div>

            <!-- Instructions d'activation -->
            <div style='background-color: #0c0f17; border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 12px; padding: 20px; margin-bottom: 30px;'>
                <h3 style='font-size: 13px; text-transform: uppercase; color: #fff; margin-top: 0; margin-bottom: 15px; letter-spacing: 1px;'>Instructions d'activation</h3>
                <ol style='font-size: 13px; color: #eef0f6; padding-left: 20px; line-height: 1.8; margin: 0;'>
                    <li style='margin-bottom: 10px;'><strong>Copiez</strong> la clé de licence ci-dessus.</li>
                    <li style='margin-bottom: 10px;'>Rendez-vous sur la page d'activation : <br>
                        <a href='https://wari.digiroys.com/config/auth.php' style='color: #e8a923; text-decoration: none; font-weight: bold;'>https://wari.digiroys.com/config/auth.php</a>
                    </li>
                    <li style='margin-bottom: 10px;'>Choisissez l'onglet <strong>\"Activer\"</strong>.</li>
                    <li style='margin-bottom: 10px;'>Entrez votre <strong>adresse email</strong> et créez votre <strong>mot de passe</strong>.</li>
                    <li style='margin-bottom: 10px;'>Collez votre licence dans le champ : <strong>N° de Commande (Vérification Licence)</strong>.</li>
                    <li style='margin-bottom: 10px;'>Cliquez sur <strong>\"Vérifier et Activer\"</strong>.</li>
                    <li style='margin-bottom: 10px;'>Une fois l'activation terminée, connectez-vous avec votre e-mail et le mot de passe créé.</li>
                    <li style='margin-bottom: 0;'>Installez l'application en cliquant sur le bouton d'installation présent en bas de votre tableau de bord.</li>
                </ol>
            </div>

            <div style='text-align: center; margin-bottom: 30px;'>
                <a href='https://wari.digiroys.com/paid/invoice.php?id=$transaction_id' style='display: inline-block; background-color: #e8a923; color: #07090e; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: bold; font-family: sans-serif; font-size: 14px;'>
                    Télécharger ma facture en PDF
                </a>
            </div>
            
            <div style='text-align: center; margin-top: 30px; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 20px; font-size: 11px; color: #6b7491; line-height: 1.5;'>
                <p>Merci pour votre confiance. Cet e-mail tient lieu de reçu officiel de paiement et de licence.</p>
                <p>© WARI Finance by Digiroys — <a href='mailto:wari-finance@digiroys.com' style='color: #e8a923; text-decoration: none;'>Besoin d'aide ?</a></p>
                <p style='margin-top: 10px; color: #525b75; font-size: 10px;'>
                    Digiroys — RC: RB/COT/19 A 50622 — IFU: 0 2019 1088 5778<br>
                    Contact : <a href='mailto:wari-finance@digiroys.com' style='color: #e8a923; text-decoration: none;'>wari-finance@digiroys.com</a>
                </p>
            </div>
        </div>
        ";

        $res = $mailer->send($email, "Facture WARI Finance - Votre Licence [Ref: WARI-INV-$ref]", $body, true);
        if (!$res['success']) {
            throw new Exception($res['message']);
        }

        // D. VERROUILLAGE : On enregistre la licence en session
        $_SESSION['active_license_key'] = $new_license;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Une erreur technique est survenue. Veuillez rafraîchir la page.");
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement Réussi — WARI</title>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Geist:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --bg: #0e0f11;
            --surface: #161719;
            --border: #232428;
            --text: #f0efe8;
            --muted: #6b6a65;
            --accent: #f59e0b;
            --accent2: #2a2b2f;
        }

        body {
            background: var(--bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Geist', sans-serif;
            padding: 24px;
        }

        .bento {
            width: 100%;
            max-width: 480px;
            display: grid;
            gap: 10px;
            animation: fadeUp 0.5s ease both;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(18px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .cell {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
        }

        /* ─── Top badge row ─── */
        .cell-status {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            border-radius: 12px;
        }

        .status-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 500;
            color: var(--accent);
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--accent);
            box-shadow: 0 0 8px var(--accent);
            animation: pulse 2s ease infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.4;
            }
        }

        .brand {
            font-family: 'Instrument Serif', serif;
            font-size: 15px;
            color: var(--muted);
            letter-spacing: 0.5px;
        }

        /* ─── Hero ─── */
        .cell-hero {
            text-align: center;
            padding: 32px 24px 28px;
        }

        .check-ring {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            border: 1.5px solid var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .check-ring svg {
            width: 28px;
            height: 28px;
            stroke: var(--accent);
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .hero-title {
            font-family: 'Instrument Serif', serif;
            font-size: 28px;
            color: var(--text);
            line-height: 1.2;
            margin-bottom: 10px;
        }

        .hero-sub {
            font-size: 13px;
            color: var(--muted);
            line-height: 1.6;
        }

        .hero-sub strong {
            color: var(--text);
            font-weight: 500;
        }

        /* ─── License cell ─── */
        .cell-license {
            padding: 20px;
        }

        .cell-label {
            font-size: 10px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: var(--muted);
            margin-bottom: 12px;
        }

        .license-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .license-code {
            flex: 1;
            font-family: 'Geist', monospace;
            font-size: 15px;
            font-weight: 500;
            color: var(--accent);
            background: #0e0f11;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 13px 16px;
            letter-spacing: 1px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .btn-copy {
            flex-shrink: 0;
            background: var(--accent2);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 13px 16px;
            color: var(--text);
            font-size: 12px;
            font-weight: 500;
            font-family: 'Geist', sans-serif;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn-copy:hover {
            background: #2f3035;
        }

        .btn-copy.copied {
            background: rgba(244, 179, 50, 0.28);
            border-color: rgba(244, 179, 50, 0.53);
            color: var(--accent);
        }

        /* ─── Info row ─── */
        .cell-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            padding: 0;
            background: transparent;
            border: none;
        }

        .info-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 18px;
        }

        .info-card-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--muted);
            margin-bottom: 6px;
        }

        .info-card-value {
            font-size: 14px;
            font-weight: 500;
            color: var(--text);
        }

        /* ─── CTA ─── */
        .btn-activate {
            width: 100%;
            background: var(--accent);
            color: #0e0f11;
            border: none;
            border-radius: 12px;
            padding: 15px;
            font-family: 'Geist', sans-serif;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: opacity 0.15s, transform 0.1s;
            letter-spacing: 0.2px;
        }

        .btn-activate:hover {
            opacity: 0.88;
        }

        .btn-activate:active {
            transform: scale(0.98);
        }

        .arrow {
            font-size: 16px;
            transition: transform 0.2s;
        }

        .btn-activate:hover .arrow {
            transform: translateX(3px);
        }
    </style>
</head>

<body>

    <div class="bento">

        <!-- Badge status -->
        <div class="cell cell-status">
            <div class="status-pill">
                <span class="status-dot"></span>
                Paiement confirmé
            </div>
            <span class="brand">WARI - FINANCE</span>
        </div>

        <!-- Hero -->
        <div class="cell cell-hero">
            <div class="check-ring">
                <svg viewBox="0 0 24 24">
                    <polyline points="20 6 9 17 4 12" />
                </svg>
            </div>
            <h1 class="hero-title">Licence activée</h1>
            <p class="hero-sub">
                Votre licence a été envoyée à<br>
                <strong><?= htmlspecialchars($email) ?></strong>
            </p>
        </div>

        <!-- Licence -->
        <div class="cell cell-license">
            <div class="cell-label">Clé de licence</div>
            <div class="license-row">
                <div class="license-code" id="licenseCode"><?= htmlspecialchars($new_license) ?></div>
                <button class="btn-copy" id="copyBtn" onclick="copyLicense()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="9" y="9" width="13" height="13" rx="2" />
                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                    </svg>
                    Copier
                </button>
            </div>
        </div>

        <!-- Infos -->
        <div class="cell-info">
            <div class="info-card">
                <div class="info-card-label">Montant payé</div>
                <div class="info-card-value"><?= ($duree_jours === 365) ? '5 000' : '590' ?> F CFA</div>
            </div>
            <div class="info-card">
                <div class="info-card-label">Durée incluse</div>
                <div class="info-card-value"><?= ($duree_jours === 365) ? '12 mois (365 j)' : '1 mois (30 j)' ?></div>
            </div>
        </div>

        <!-- CTA -->
        <div class="cell" style="padding: 0; background: transparent; border: none;">
            <a href="../config/auth.php" class="btn-activate">
                Activer ma licence
                <span class="arrow">→</span>
            </a>
        </div>

    </div>

    <script>
        function copyLicense() {
            const text = document.getElementById('licenseCode').innerText;
            const btn = document.getElementById('copyBtn');
            navigator.clipboard.writeText(text).then(() => {
                btn.classList.add('copied');
                btn.innerHTML = `
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="20 6 9 17 4 12"/>
      </svg>
      Copié`;
                setTimeout(() => {
                    btn.classList.remove('copied');
                    btn.innerHTML = `
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
        </svg>
        Copier`;
                }, 2000);
            });
        }
    </script>

</body>

</html>
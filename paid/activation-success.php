<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';
require '../config/db.php';
session_start();

// 1. Sécurité de base
if (!isset($_SESSION['pending_activation_email'])) {
    header("Location: index.php");
    exit();
}

$email = $_SESSION['pending_activation_email'];

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

// 2. LE VERROU : On vérifie si une licence a déjà été créée pour cette session
if (isset($_SESSION['active_license_key'])) {
    // Si oui, on récupère simplement celle qui existe déjà
    $new_license = $_SESSION['active_license_key'];
} else {
    // Si non, c'est le TOUT PREMIER chargement après paiement
    $new_license = generateWariLicense();

    try {
        // A. Sauvegarde immédiate en base de données
        $stmt = $pdo->prepare("INSERT INTO wari_licences (commande_id, statut, date_creation) VALUES (?, 'disponible', NOW())");
        $stmt->execute([$new_license]);

        // B. Envoi de l'email unique
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'financewari1@gmail.com';
        $mail->Password   = 'ajjg mkex dyjk adyq';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('financewari1@gmail.com', 'WARI-Finance');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Votre Licence WARI - Finance 🚀';
        // Contenu de l'email avec instructions détaillées
        $mail->Body = "
                <div style='font-family: sans-serif; line-height: 1.6; color: #333;'>
                    <h2 style='color: #2a2b2f;'>Merci pour votre achat ! 🚀</h2>
                    <p>Félicitations, vous venez de débloquer votre accès à <strong>WARI - Finance</strong>.</p>
                    
                    <p>Voici votre code de licence personnel :</p>
                    <div style='background: #f4f4f4; padding: 15px; font-size: 22px; font-family: monospace; font-weight: bold; border: 2px dashed #f59e0b; display: inline-block; color: #000; letter-spacing: 2px;'>
                        $new_license
                    </div>

                    <hr style='border: 0; border-top: 1px solid #eee; margin: 30px 0;'>

                    <h3 style='color: #2a2b2f;'>Étape suivante pour l'activation :</h3>
                    <ol style='padding-left: 20px;'>
                        <li style='margin-bottom: 10px;'><strong>Copiez</strong> le code de licence ci-dessus.</li>
                        <li style='margin-bottom: 10px;'>Rendez-vous sur la page d'activation : <br>
                            <a href='https://wari.digiroys.com/config/auth.php' style='color: #007bff; font-weight: bold;'>https://wari.digiroys.com/config/auth.php</a></li>
                        <li style='margin-bottom: 10px;'>Choisissez l'onglet <strong>\"Activer\"</strong>.</li>
                        <li style='margin-bottom: 10px;'>Entrez votre <strong>adresse email</strong> et créez votre <strong>mot de passe</strong>.</li>
                        <li style='margin-bottom: 10px;'>Collez votre licence dans le champ : <strong>N° de Commande (Vérification Licence)</strong>.</li>
                        <li style='margin-bottom: 10px;'>Cliquez sur <strong>\"Vérifier et Activer\"</strong>.</li>
                        <li style='margin-bottom: 10px;'>Une fois activé, connectez-vous avec votre mail et le mot de passe que vous venez de créer.</li>
                        <li style='margin-bottom: 10px;'>Enfin, installez l'application en cliquant sur le bouton <strong>tout en bas</strong> de votre interface.</li>
                    </ol>

                    <p style='margin-top: 30px; font-size: 13px; color: #666;'>
                        Besoin d'aide ? Contactez-nous directement en répondant à ce mail.
                    </p>
                </div>
            ";

        $mail->send();

        // C. VERROUILLAGE : On enregistre la licence en session
        // Désormais, tant que la session existe, le code ci-dessus ne sera plus jamais exécuté.
        $_SESSION['active_license_key'] = $new_license;
    } catch (Exception $e) {
        // En cas d'erreur DB ou Email, on ne verrouille pas pour permettre une rétentative
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
                <div class="info-card-value">2 500 F CFA</div>
            </div>
            <div class="info-card">
                <div class="info-card-label">Produit</div>
                <div class="info-card-value">License Wari - Finance</div>
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
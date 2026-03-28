<?php
// /var/www/html/academy/pdf_achat_verify.php
// Appelé après retour de FedaPay ou CinetPay pour vérifier le paiement

ob_start();
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: https://wari.digiroys.com/login');
    exit;
}

// Chargement .env
$envFile = '/var/www/html/wari-admin/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0 || !strpos($line, '=')) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value, " \t\n\r\0\x0B\"'");
    }
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../classes/Academy.php';
require_once __DIR__ . '/../vendor/autoload.php';

$academy        = new Academy($pdo);
$user_id        = $_SESSION['user_id'];
$provider       = $_GET['provider']       ?? '';
$transaction_id = $_GET['transaction_id'] ?? '';
$pdf_id         = (int)($_GET['pdf_id']   ?? 0);
$ref            = $_GET['ref']            ?? '';

$verified = false;
$montant  = 0;
$error    = '';

// ════════════════════════════════════════════════════════
// VÉRIFICATION FEDAPAY
// ════════════════════════════════════════════════════════
if ($provider === 'fedapay' && $transaction_id) {
    try {
        \FedaPay\FedaPay::setApiKey($_ENV['FEDAPAY_SECRET_KEY'] ?? '');
        \FedaPay\FedaPay::setEnvironment($_ENV['FEDAPAY_ENV'] ?? 'live');

        $transaction = \FedaPay\Transaction::retrieve($transaction_id);

        if ($transaction && $transaction->status === 'approved') {
            $verified = true;
            $montant  = $transaction->amount;
        } else {
            $error = "Paiement non approuvé. Statut : " . ($transaction->status ?? 'inconnu');
        }
    } catch (Exception $e) {
        $error = "Erreur FedaPay : " . $e->getMessage();
    }
}

// ════════════════════════════════════════════════════════
// VÉRIFICATION CINETPAY
// ════════════════════════════════════════════════════════
if ($provider === 'cinetpay' && $transaction_id) {
    try {
        // Appel API CinetPay pour vérifier la transaction
        $apiKey  = $_ENV['CINETPAY_API_KEY']  ?? '';
        $siteId  = $_ENV['CINETPAY_SITE_ID']  ?? '';

        $url  = "https://api-checkout.cinetpay.com/v2/payment/check";
        $data = json_encode([
            'apikey'         => $apiKey,
            'site_id'        => $siteId,
            'transaction_id' => $transaction_id,
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($result && isset($result['data']['status']) && $result['data']['status'] === 'ACCEPTED') {
            $verified = true;
            $montant  = (float)($result['data']['amount'] ?? 0);
        } else {
            $error = "Paiement CinetPay non confirmé.";
        }
    } catch (Exception $e) {
        $error = "Erreur CinetPay : " . $e->getMessage();
    }
}

// ════════════════════════════════════════════════════════
// ENREGISTREMENT SI PAIEMENT VÉRIFIÉ
// ════════════════════════════════════════════════════════
if ($verified && $pdf_id) {
    // Vérifier qu'on n'enregistre pas deux fois
    if (!$academy->hasUserBoughtPdf($user_id, $pdf_id)) {
        $academy->savePdfAchat($user_id, $pdf_id, $montant, $ref);
    }
    // Redirection vers le téléchargement
    header('Location: /academy/pdf_download.php?id=' . $pdf_id . '&success=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification paiement — Wari Academy</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <link rel="icon" type="image/png" href="../assets/warifinance3d.png" />
    <link rel="apple-touch-icon" href="../assets/warifinance3d.png" />

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #0F0A02; color: #e2e8f0;
            min-height: 100vh; display: flex;
            align-items: center; justify-content: center;
            padding: 24px;
        }
        .card {
            max-width: 420px; width: 100%;
            background: rgba(255,255,255,.03);
            border: 1px solid rgba(201,168,76,.15);
            border-radius: 20px; padding: 40px 32px;
            text-align: center;
        }
        .icon { font-size: 3rem; margin-bottom: 16px; }
        h2 { font-size: 1.1rem; font-weight: 700; color: #fff; margin-bottom: 10px; }
        p  { font-size: .85rem; color: rgba(255,255,255,.4); margin-bottom: 20px; line-height: 1.6; }
        .error { color: #f87171; font-size: .82rem; background: rgba(239,68,68,.08);
                 border: 1px solid rgba(239,68,68,.2); border-radius: 10px; padding: 12px; margin-bottom: 16px; }
        .btn {
            display: inline-block; background: #C9A84C; color: #0F0A02;
            font-weight: 700; font-size: .85rem;
            padding: 12px 28px; border-radius: 999px;
            text-decoration: none; transition: all .2s;
        }
        .btn:hover { background: #F0D080; }
        .btn-ghost {
            display: inline-block; margin-top: 10px;
            font-size: .78rem; color: rgba(255,255,255,.3);
            text-decoration: none;
        }
        .btn-ghost:hover { color: rgba(255,255,255,.6); }
    </style>
</head>
<body>
<div class="card">
    <div class="icon">❌</div>
    <h2>Paiement non confirmé</h2>

    <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <p>Le paiement n'a pas pu être vérifié. Si tu as été débité, contacte-nous à <strong>support@wari.digiroys.com</strong> avec ta référence : <code><?= htmlspecialchars($ref) ?></code></p>

    <a href="/academy/pdf_achat.php?id=<?= $pdf_id ?>" class="btn">↩ Réessayer</a>
    <br>
    <a href="/academy/" class="btn-ghost">← Retour à Academy</a>
</div>
</body>
</html>
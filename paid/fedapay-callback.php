<?php
// 1. On coupe l'affichage des alertes de dépréciation pour PHP 8.2+
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);

require_once '../vendor/autoload.php';
require_once '../config/db.php';

session_start();

// Configuration FedaPay
\FedaPay\FedaPay::setApiKey("sk_live_-t3Pw_JoJ8VGBqP8eTZr-ar5");
\FedaPay\FedaPay::setEnvironment('live');

// 1. Récupération de l'ID de la transaction envoyé par FedaPay dans l'URL
$transaction_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$transaction_id) {
    die("ID de transaction manquant.");
}

try {
    // 2. On demande à FedaPay le statut réel de cette transaction
    $transaction = \FedaPay\Transaction::retrieve($transaction_id);
    $status = $transaction->status; // 'approved', 'declined', ou 'pending'

    if ($status === 'approved') {
        // A. On met à jour notre table wari_payments
        $stmt = $pdo->prepare("UPDATE wari_payments SET statut = 'approved' WHERE reference_fedapay = ?");
        $stmt->execute([$transaction_id]);

        // B. On récupère l'email du client associé à cette transaction
        $stmtEmail = $pdo->prepare("SELECT email_client FROM wari_payments WHERE reference_fedapay = ?");
        $stmtEmail->execute([$transaction_id]);
        $payment = $stmtEmail->fetch();

        if ($payment) {
            // C. On stocke l'email en session pour l'étape suivante
            $_SESSION['pending_activation_email'] = $payment['email_client'];
            $_SESSION['payment_ref'] = $transaction_id;

            // D. Direction : La génération de la licence !
            header("Location: activation-success.php");
            exit();
        }
    } else {
        // Si le paiement a échoué ou est annulé
        header("Location: index.php?error=payment_failed");
        exit();
    }
} catch (Exception $e) {
    echo "Erreur lors de la vérification : " . $e->getMessage();
}

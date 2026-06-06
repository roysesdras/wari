<?php
// 1. On coupe l'affichage des alertes de dépréciation pour PHP 8.2+
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);

require_once '../vendor/autoload.php';
require_once '../config/db.php';

require_once '../config/session_config.php';

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
        // A. On récupère les détails du paiement enregistré
        $stmtPayment = $pdo->prepare("SELECT * FROM wari_payments WHERE reference_fedapay = ?");
        $stmtPayment->execute([$transaction_id]);
        $payment = $stmtPayment->fetch();

        if ($payment) {
            // B. On met à jour notre table wari_payments
            $stmtUpdate = $pdo->prepare("UPDATE wari_payments SET statut = 'approved' WHERE reference_fedapay = ?");
            $stmtUpdate->execute([$transaction_id]);

            // C. Traitement différencié
            if ($payment['type_paiement'] === 'recharge') {
                $duree = intval($payment['duree_jours']);
                $commande_id = $payment['commande_id'];

                // Prolonge à partir de date_expiration si elle est dans le futur, sinon à partir de NOW()
                $stmtLic = $pdo->prepare("
                    UPDATE wari_licences 
                    SET date_expiration = DATE_ADD(GREATEST(COALESCE(date_expiration, NOW()), NOW()), INTERVAL ? DAY)
                    WHERE commande_id = ?
                ");
                $stmtLic->execute([$duree, $commande_id]);

                // On stocke le succès de la recharge en session
                $_SESSION['recharge_success'] = true;

                // Redirection directe vers le tableau de bord
                header("Location: ../index.php");
                exit();
            } else {
                // Achat d'une nouvelle licence
                $_SESSION['pending_activation_email'] = $payment['email_client'];
                $_SESSION['payment_ref'] = $transaction_id;
                $_SESSION['pending_duree_jours'] = $payment['duree_jours']; // Pour activation-success.php

                // Direction : La génération de la licence
                header("Location: activation-success.php");
                exit();
            }
        } else {
            die("Paiement non trouvé dans notre base de données.");
        }
    } else {
        // Si le paiement a échoué ou est annulé
        header("Location: index.php?error=payment_failed");
        exit();
    }
} catch (Exception $e) {
    echo "Erreur lors de la vérification : " . $e->getMessage();
}

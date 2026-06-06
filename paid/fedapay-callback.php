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

                // Récupérer la nouvelle date d'expiration
                $stmtNewExp = $pdo->prepare("SELECT date_expiration FROM wari_licences WHERE commande_id = ?");
                $stmtNewExp->execute([$commande_id]);
                $newExpiration = $stmtNewExp->fetchColumn();

                // Envoi de la facture par e-mail
                try {
                    require_once __DIR__ . '/../classes/Mailer.php';
                    $mailer = new Mailer();

                    $ref = strtoupper(substr($transaction_id, 0, 8));
                    $date = date('d/m/Y à H:i');
                    $email = $payment['email_client'];
                    $price_formatted = number_format($payment['montant'], 0, '', ' ');
                    $duration_label = ($duree === 365) ? "365 jours (Annuel)" : "30 jours (Mensuel)";
                    $desc = "Recharge d'accès Wari Finance Pro";
                    
                    $exp_formatted = date('d/m/Y à H:i', strtotime($newExpiration));
                    
                    $extension_block = "
                        <div style='background-color: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 12px; padding: 15px; text-align: center; margin-bottom: 30px;'>
                            <div style='color: #10b981; font-weight: bold; font-size: 15px; margin-bottom: 5px;'>Accès prolongé avec succès !</div>
                            <div style='font-size: 13px; color: #eef0f6;'>Votre abonnement est désormais valide jusqu'au <strong>$exp_formatted</strong>.</div>
                        </div>
                    ";

                    $body = "
                    <div style='background-color: #07090e; color: #eef0f6; font-family: sans-serif; padding: 30px; border-radius: 16px; max-width: 600px; margin: 0 auto; border: 1px solid rgba(255, 255, 255, 0.05);'>
                        <div style='text-align: center; margin-bottom: 30px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 20px;'>
                            <h1 style='color: #e8a923; font-size: 26px; margin: 0; font-family: sans-serif;'>WARI <span style='font-size: 14px; color: #fff; font-weight: normal; letter-spacing: 2px;'>FINANCE</span></h1>
                            <p style='color: #6b7491; font-size: 11px; text-transform: uppercase; letter-spacing: 2px; margin-top: 5px; margin-bottom: 0;'>Discipline & Progrès</p>
                        </div>
                        
                        <div style='margin-bottom: 30px;'>
                            <h2 style='font-size: 18px; color: #fff; margin-top: 0; margin-bottom: 15px;'>Facture & Reçu de paiement</h2>
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
                            <h3 style='font-size: 13px; text-transform: uppercase; color: #e8a923; margin-top: 0; margin-bottom: 15px; letter-spacing: 1px;'>Détails de l'abonnement</h3>
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
                        
                        $extension_block
                        
                        <div style='text-align: center; margin-top: 30px; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 20px; font-size: 11px; color: #6b7491; line-height: 1.5;'>
                            <p>Merci pour votre confiance. Cet e-mail tient lieu de reçu officiel de paiement.</p>
                            <p>© WARI Finance by Digiroys — <a href='mailto:wari-finance@digiroys.com' style='color: #e8a923; text-decoration: none;'>Besoin d'aide ?</a></p>
                            <p style='margin-top: 10px; color: #525b75; font-size: 10px;'>
                                Digiroys — RC: RB/COT/19 A 50622 — IFU: 0 2019 1088 5778<br>
                                Contact : <a href='mailto:wari-finance@digiroys.com' style='color: #e8a923; text-decoration: none;'>wari-finance@digiroys.com</a>
                            </p>
                        </div>
                    </div>
                    ";

                    $mailer->send($email, "Facture WARI Finance - Reçu de paiement [Ref: WARI-INV-$ref]", $body, true);
                } catch (Exception $mailEx) {
                    // Silencieux
                }

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
                $_SESSION['pending_montant'] = $payment['montant'];

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

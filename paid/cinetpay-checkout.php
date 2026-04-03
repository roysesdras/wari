<?php
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);

// On inclut ta connexion à la base de données
require_once '../config/db.php';

session_start();

// -------------------------------------------------------
// Configuration CinetPay
// -------------------------------------------------------
$cinetpay_apikey  = "sk_test_k3kvVZsEGJxXHpnROn719fhx";   // Remplace par ta clé API CinetPay
$cinetpay_site_id = "TON_SITE_ID";        // Disponible dans ton backoffice CinetPay
$cinetpay_mode    = "TEST";               // Passe en "PRODUCTION" quand tu es prêt

// Récupération de l'email du formulaire
$customer_email = filter_var($_POST['customer_email'] ?? '', FILTER_SANITIZE_EMAIL);
$amount = 2500;

if (!$customer_email) {
    die("L'adresse email est requise pour continuer.");
}

try {
    // --------------------------------------------------
    // A. Génération d'un identifiant unique de transaction
    // --------------------------------------------------
    $id_transaction = date("YmdHis") . rand(100, 999); // ex: 20250402143512847

    // --------------------------------------------------
    // B. Appel API CinetPay pour créer le lien de paiement
    // --------------------------------------------------
    $payload = json_encode([
        "apikey"           => $cinetpay_apikey,
        "site_id"          => $cinetpay_site_id,
        "transaction_id"   => $id_transaction,
        "amount"           => $amount,
        "currency"         => "XOF",
        "description"      => "Achat Licence WARI Pro",
        "channels"         => "ALL",           // Mobile Money + Carte bancaire
        "notify_url"       => "https://wari.digiroys.com/paid/cinetpay-notify.php",
        "return_url"       => "https://wari.digiroys.com/paid/cinetpay-return.php",
        // Infos client (optionnel, requis pour la carte bancaire)
        "customer_email"   => $customer_email,
    ]);

    $ch = curl_init("https://api-checkout.cinetpay.com/v2/payment");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST,           true);
    curl_setopt($ch, CURLOPT_POSTFIELDS,     $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER,     ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // À activer en production (true)

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$response) {
        throw new Exception("Impossible de contacter CinetPay. Vérifie ta connexion.");
    }

    $result = json_decode($response, true);

    // Code 201 = transaction créée avec succès
    if (!isset($result['code']) || $result['code'] !== '201') {
        $error_msg = $result['description'] ?? $result['message'] ?? 'Erreur inconnue';
        throw new Exception("CinetPay : " . $error_msg);
    }

    $payment_url = $result['data']['payment_url'];

    // --------------------------------------------------
    // C. Enregistrement dans la table wari_payments
    // --------------------------------------------------
    $stmt = $pdo->prepare(
        "INSERT INTO wari_payments (reference_cinetpay, email_client, montant, statut)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([
        $id_transaction,
        $customer_email,
        $amount,
        'pending'
    ]);

    // --------------------------------------------------
    // D. Redirection vers la page de paiement CinetPay
    // --------------------------------------------------
    header("Location: " . $payment_url);
    exit();

} catch (Exception $e) {
    echo "Désolé, une erreur est survenue : " . $e->getMessage();
}
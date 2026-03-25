<?php
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);
// On charge les dépendances installées via Composer
require_once '../vendor/autoload.php';

// On inclut ta connexion à la base de données
require_once '../config/db.php';

session_start();

// Configuration FedaPay
\FedaPay\FedaPay::setApiKey("sk_live_-t3Pw_JoJ8VGBqP8eTZr-ar5"); // Remplace par ta clé secrète
\FedaPay\FedaPay::setEnvironment('live'); // Passe en 'live' quand tu es prêt

// Récupération de l'email du formulaire
$customer_email = filter_var($_POST['customer_email'], FILTER_SANITIZE_EMAIL);
$amount = 2500;

if (!$customer_email) {
    die("L'adresse email est requise pour continuer.");
}

try {
    // A. Création de la transaction chez FedaPay
    $transaction = \FedaPay\Transaction::create([
        "description" => "Achat Licence WARI Pro",
        "amount" => $amount,
        "currency" => ["iso" => "XOF"],
        "callback_url" => "https://wari.digiroys.com/paid/fedapay-callback.php",
        "customer" => [
            "email" => $customer_email
        ]
    ]);

    // B. Récupération de l'URL de paiement
    $token = $transaction->generateToken();

    // C. Enregistrement dans ta table wari_payments
    // Note : On utilise $pdo car c'est le nom défini dans ton fichier db.php
    $stmt = $pdo->prepare("INSERT INTO wari_payments (reference_fedapay, email_client, montant, statut) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $transaction->id,
        $customer_email,
        $amount,
        'pending'
    ]);

    // D. Redirection vers FedaPay
    header("Location: " . $token->url);
    exit();
} catch (Exception $e) {
    echo "Désolé, une erreur est survenue : " . $e->getMessage();
}

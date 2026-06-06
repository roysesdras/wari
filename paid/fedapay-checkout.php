<?php
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);

// On charge les dépendances installées via Composer
require_once '../vendor/autoload.php';

// On inclut la connexion à la base de données
require_once '../config/db.php';

require_once '../config/session_config.php';

// Configuration FedaPay
\FedaPay\FedaPay::setApiKey("sk_live_-t3Pw_JoJ8VGBqP8eTZr-ar5"); // Remplace par ta clé secrète
\FedaPay\FedaPay::setEnvironment('live'); // Passe en 'live' quand tu es prêt

// 1. Détection Recharge vs Nouvel Achat
$user_id = $_SESSION['user_id'] ?? null;
$commande_id = null;
$type_paiement = 'achat'; // Par défaut

if ($user_id) {
    // Mode Recharge : Récupérer les détails depuis la session
    $stmt = $pdo->prepare("SELECT email, commande_id FROM wari_users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if ($user) {
        $customer_email = $user['email'];
        $commande_id = $user['commande_id'];
        $type_paiement = 'recharge';
    } else {
        die("Utilisateur de session non valide.");
    }
} else {
    // Mode Achat Licence
    $email_brut = $_POST['customer_email'] ?? null;
    $customer_email = filter_var($email_brut, FILTER_SANITIZE_EMAIL);
}

if (!$customer_email) {
    die("L'adresse email est requise pour continuer.");
}

// 2. Choix de la formule
$plan = $_POST['plan'] ?? 'mensuel';
$amount = ($plan === 'annuel') ? 5000 : 590;
$duree_jours = ($plan === 'annuel') ? 365 : 30;

$description = ($type_paiement === 'recharge')
    ? "Recharge Wari Finance - " . ($plan === 'annuel' ? '12 mois' : '1 mois')
    : "Achat Licence Wari Finance - " . ($plan === 'annuel' ? '12 mois' : '1 mois');

try {
    // A. Création de la transaction chez FedaPay
    $transaction = \FedaPay\Transaction::create([
        "description" => $description,
        "amount" => $amount,
        "currency" => ["iso" => "XOF"],
        "callback_url" => "https://wari.digiroys.com/paid/fedapay-callback.php",
        "customer" => [
            "email" => $customer_email
        ]
    ]);

    // B. Récupération de l'URL de paiement
    $token = $transaction->generateToken();

    // C. Enregistrement dans la table wari_payments avec les nouvelles colonnes
    $stmt = $pdo->prepare("
        INSERT INTO wari_payments 
        (reference_fedapay, email_client, montant, statut, type_paiement, commande_id, duree_jours, date_creation) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $transaction->id,
        $customer_email,
        $amount,
        'pending',
        $type_paiement,
        $commande_id,
        $duree_jours
    ]);

    // D. Redirection vers FedaPay
    header("Location: " . $token->url);
    exit();
} catch (Exception $e) {
    echo "Désolé, une erreur est survenue : " . $e->getMessage();
}

<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';
require '../config/db.php';
session_start();

$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    // 1. Vérifier si l'utilisateur existe
    $stmt = $pdo->prepare("SELECT id FROM wari_users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {

        // 2. Générer un token sécurisé
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour")); // Expire dans 1 heure

        // 3. Stocker le token
        $stmt = $pdo->prepare("INSERT INTO wari_password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$email, $token, $expires]);

        // 4. Envoyer l'email
        $reset_link = "https://wari.digiroys.com/config/reset-password.php?token=" . $token;

        try {
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
            $mail->Subject = 'Réinitialisation de votre mot de passe WARI';
            $mail->Body = "
                <div style='font-family: sans-serif; line-height: 1.6;'>
                    <h2>Demande de nouveau mot de passe</h2>
                    <p>Vous avez demandé la réinitialisation de votre mot de passe pour votre compte WARI.</p>
                    <p>Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe (Lien valable 1 heure) :</p>
                    <a href='$reset_link' style='background: #f59e0b; color: #000; padding: 12px 20px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;'>Réinitialiser mon mot de passe</a>
                    <p>Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.</p>
                </div>";

            $mail->send();
            $message = "Un lien de récupération a été envoyé à votre adresse email.";
        } catch (Exception $e) {
            $error = "Erreur lors de l'envoi de l'email.";
        }
    } else {
        // Pour la sécurité, on affiche le même message même si l'email n'existe pas
        $message = "Si cet email existe, un lien de récupération a été envoyé.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié — WARI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-[#0e0f11] min-h-screen flex items-center justify-center p-6">

    <div class="w-full max-w-md bg-slate-900/50 backdrop-blur-xl border border-slate-800 p-8 rounded-3xl shadow-2xl">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-white mb-2">Mot de passe oublié ?</h1>
            <p class="text-slate-500 text-sm">Entrez votre email pour recevoir un lien de réinitialisation.</p>
        </div>

        <?php if ($message): ?>
            <div class="bg-amber-500/10 border border-amber-500/50 text-amber-500 p-4 rounded-xl text-xs mb-6 text-center">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-6">
            <div>
                <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-bold mb-2 ml-1">Votre Email</label>
                <input type="email" name="email" placeholder="nom@exemple.com" required
                    class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder:text-slate-600 outline-none focus:border-amber-500/50 transition-all">
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-amber-500 to-orange-600 py-4 rounded-xl text-black font-black text-xs uppercase tracking-widest shadow-lg shadow-amber-500/20 active:scale-95 transition-all">
                Envoyer le lien
            </button>
        </form>

        <div class="mt-8 text-center">
            <a href="auth.php" class="text-xs text-slate-500 hover:text-white transition-colors">
                ← Retour à la connexion
            </a>
        </div>
    </div>

</body>

</html>
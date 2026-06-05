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
            require_once __DIR__ . '/../classes/Mailer.php';
            $mailer = new Mailer();
            $body = "
                <div style='font-family: sans-serif; line-height: 1.6;'>
                    <h2>Demande de nouveau mot de passe</h2>
                    <p>Vous avez demandé la réinitialisation de votre mot de passe pour votre compte WARI.</p>
                    <p>Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe (Lien valable 1 heure) :</p>
                    <a href='$reset_link' style='background: #f59e0b; color: #000; padding: 12px 20px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;'>Réinitialiser mon mot de passe</a>
                    <p>Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.</p>
                </div>";

            $res = $mailer->send($email, 'Réinitialisation de votre mot de passe WARI', $body, true);
            if ($res['success']) {
                $message = "Un lien de récupération a été envoyé à votre adresse email.";
            } else {
                throw new Exception($res['message']);
            }
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
    <link rel="stylesheet" href="../assets/styles.css?v=88">
    <meta id="metaThemeColor" name="theme-color" content="#000000">

    <script>
        // Initialisation immédiate du thème
        const savedTheme = localStorage.getItem('wari_theme') || 'dark';
        if (savedTheme === 'light') {
            document.documentElement.classList.add('light-mode');
            document.addEventListener('DOMContentLoaded', () => {
                document.body.classList.add('light-mode');
                const metaThemeColor = document.getElementById('metaThemeColor');
                if (metaThemeColor) metaThemeColor.setAttribute('content', '#f1f5f9');
            });
        }

        function toggleTheme() {
            const isLight = document.body.classList.toggle('light-mode');
            document.documentElement.classList.toggle('light-mode', isLight);
            localStorage.setItem('wari_theme', isLight ? 'light' : 'dark');
            const metaThemeColor = document.getElementById('metaThemeColor');
            if (metaThemeColor) {
                metaThemeColor.setAttribute('content', isLight ? '#f1f5f9' : '#000000');
            }
            updateThemeButton();
        }

        function updateThemeButton() {
            const isLight = document.documentElement.classList.contains('light-mode');
            const metaThemeColor = document.getElementById('metaThemeColor');
            if (metaThemeColor) {
                metaThemeColor.setAttribute('content', isLight ? '#f1f5f9' : '#000000');
            }
            const themeIcon = document.getElementById('themeIcon');
            const themeToggleBtn = document.getElementById('themeToggleBtn');
            if (themeIcon) {
                if (isLight) {
                    themeIcon.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>`;
                    if (themeToggleBtn) {
                        themeToggleBtn.className = "w-10 h-10 rounded-2xl flex items-center justify-center active:scale-95 transition-all duration-300 bg-slate-200 hover:bg-slate-300 border border-slate-300/50";
                        themeIcon.className = "text-slate-800";
                    }
                } else {
                    themeIcon.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>`;
                    if (themeToggleBtn) {
                        themeToggleBtn.className = "w-10 h-10 rounded-2xl flex items-center justify-center active:scale-95 transition-all duration-300 bg-white/5 hover:bg-white/10 border border-white/5";
                        themeIcon.className = "text-slate-400";
                    }
                }
            }
        }

        document.addEventListener('DOMContentLoaded', updateThemeButton);
    </script>
</head>

<body class="bg-[#0e0f11] min-h-screen flex items-center justify-center p-6">

    <!-- Bouton de bascule de thème flottant -->
    <div class="fixed top-4 right-4 z-50">
        <button id="themeToggleBtn" onclick="toggleTheme()" title="Changer le thème"
            class="w-10 h-10 rounded-2xl flex items-center justify-center active:scale-95 transition-all duration-300 bg-white/5 hover:bg-white/10 border border-white/5">
            <span id="themeIcon" class="text-slate-400"></span>
        </button>
    </div>

    <div class="w-full max-w-md glass p-8 rounded-3xl shadow-2xl">
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
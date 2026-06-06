<?php
require '../config/db.php';
session_start();

$message = "";
$error = "";
$token = $_GET['token'] ?? '';

// 1. Vérifier si le token est fourni et valide
if (empty($token)) {
    header("Location: auth.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM wari_password_resets WHERE token = ? AND expires_at > NOW() LIMIT 1");
$stmt->execute([$token]);
$reset_request = $stmt->fetch();

if (!$reset_request) {
    die("Ce lien de réinitialisation est invalide ou a expiré. <a href='forgot-password.php'>Recommencer</a>");
}

// 2. Traitement du nouveau mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 6) {
        $error = "Le mot de passe doit faire au moins 6 caractères.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $email = $reset_request['email'];

        try {
            $pdo->beginTransaction();

            // A. Mise à jour du mot de passe utilisateur
            $stmt = $pdo->prepare("UPDATE wari_users SET password = ? WHERE email = ?");
            $stmt->execute([$hashedPassword, $email]);

            // B. Suppression du token pour qu'il ne soit plus réutilisé
            $stmt = $pdo->prepare("DELETE FROM wari_password_resets WHERE email = ?");
            $stmt->execute([$email]);

            $pdo->commit();
            $message = "Mot de passe modifié avec succès ! Redirection...";
            header("Refresh: 3; url=auth.php");
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Une erreur est survenue lors de la mise à jour.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau mot de passe — WARI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/styles.css?v=96">
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

<body class="bg-[#0e0f11] min-h-screen flex items-center justify-center p-6 text-white">

    <!-- Bouton de bascule de thème flottant -->
    <div class="fixed top-4 right-4 z-50">
        <button id="themeToggleBtn" onclick="toggleTheme()" title="Changer le thème"
            class="w-10 h-10 rounded-2xl flex items-center justify-center active:scale-95 transition-all duration-300 bg-white/5 hover:bg-white/10 border border-white/5">
            <span id="themeIcon" class="text-slate-400"></span>
        </button>
    </div>

    <div class="w-full max-w-md glass p-8 rounded-3xl shadow-2xl">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold mb-2 uppercase tracking-tighter">Nouveau mot de passe</h1>
            <p class="text-slate-500 text-sm italic">Choisissez un mot de passe robuste pour votre coffre-fort WARI.</p>
        </div>

        <?php if ($message): ?>
            <div class="bg-green-500/10 border border-green-500/50 text-green-500 p-4 rounded-xl text-xs mb-6 text-center">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/50 text-red-500 p-4 rounded-xl text-xs mb-6 text-center">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-6">
            <div>
                <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-bold mb-2 ml-1">Nouveau mot de passe</label>
                <input type="password" name="password" placeholder="••••••••" required
                    class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder:text-slate-600 outline-none focus:border-amber-500/50 transition-all">
            </div>

            <div>
                <label class="block text-[10px] uppercase tracking-widest text-slate-500 font-bold mb-2 ml-1">Confirmer le mot de passe</label>
                <input type="password" name="confirm_password" placeholder="••••••••" required
                    class="w-full bg-slate-900/50 border border-slate-700 rounded-xl px-4 py-3 text-white placeholder:text-slate-600 outline-none focus:border-amber-500/50 transition-all">
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-amber-500 to-orange-600 py-4 rounded-xl text-black font-black text-xs uppercase tracking-widest shadow-lg shadow-amber-500/20 active:scale-95 transition-all">
                Mettre à jour
            </button>
        </form>
    </div>

</body>

</html>
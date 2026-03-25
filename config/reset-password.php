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
</head>

<body class="bg-[#0e0f11] min-h-screen flex items-center justify-center p-6 text-white">

    <div class="w-full max-w-md bg-slate-900/50 backdrop-blur-xl border border-slate-800 p-8 rounded-3xl shadow-2xl">
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
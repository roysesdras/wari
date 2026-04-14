<?php
session_start();

/**
 * CONFIGURATION SIMPLE ET SECURISEE
 * Change ces valeurs par tes propres identifiants
 */
$admin_user = "wari_admin"; // Ton nom d'utilisateur
$admin_pass = "WariImpact2026!"; // Ton mot de passe fort

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['user'] === $admin_user && $_POST['pass'] === $admin_pass) {
        $_SESSION['rapport_auth'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = "Accès refusé. Discipline requise.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentification | Rapport d'Impact</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Quicksand', sans-serif; }</style>
</head>
<body class="bg-slate-950 text-slate-200 flex items-center justify-center min-h-screen p-6">

    <div class="w-full max-w-sm">
        <div class="text-center mb-10">
            <h1 class="text-3xl font-black text-white uppercase italic tracking-tighter">Impact Log</h1>
            <p class="text-slate-500 text-[10px] uppercase tracking-[0.3em] mt-2">Section Stratégique • Wari-Finance</p>
        </div>

        <form method="POST" class="bg-slate-900/50 p-8 rounded-3xl border border-white/5 shadow-2xl backdrop-blur-xl">
            <?php if($error): ?>
                <div class="bg-red-500/10 border border-red-500/50 p-3 rounded-xl text-red-500 text-[10px] uppercase font-bold mb-6 text-center tracking-widest">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="space-y-5">
                <div>
                    <label class="block text-[10px] uppercase font-black text-slate-600 mb-2 ml-1">Opérateur</label>
                    <input type="text" name="user" required autofocus
                        class="w-full bg-slate-950 border border-white/10 rounded-2xl p-4 text-white outline-none focus:border-[#D4AF37] transition-all">
                </div>
                <div>
                    <label class="block text-[10px] uppercase font-black text-slate-600 mb-2 ml-1">Clé d'accès</label>
                    <input type="password" name="pass" required
                        class="w-full bg-slate-950 border border-white/10 rounded-2xl p-4 text-white outline-none focus:border-[#D4AF37] transition-all">
                </div>
                
                <button type="submit" 
                    class="w-full bg-[#D4AF37] text-slate-950 font-black py-4 rounded-2xl uppercase text-[10px] tracking-[0.4em] hover:bg-white transition-all shadow-xl shadow-[#D4AF37]/5 mt-4">
                    Entrer dans le Journal
                </button>
            </div>
        </form>
        
        <p class="text-center text-slate-700 text-[9px] uppercase tracking-widest mt-8">
            &copy; 2026 Wari-Finance • Cotonou, Benin
        </p>
    </div>

</body>
</html>
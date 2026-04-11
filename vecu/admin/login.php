<?php
session_start();
// Remplace par tes propres accès
$admin_user = "esdras";
$admin_pass = "@softiP24"; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['user'] === $admin_user && $_POST['pass'] === $admin_pass) {
        $_SESSION['admin_logged'] = true;
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
    <title>Connexion | Wari Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Quicksand', sans-serif; }
        .gold-glow:focus { box-shadow: 0 0 10px rgba(212, 175, 55, 0.2); }
    </style>
</head>
<body class="bg-slate-950 text-slate-200 min-h-screen flex items-center justify-center p-6">

    <div class="w-full max-w-md">
        <div class="text-center mb-10">
            <h1 class="text-[#D4AF37] text-3xl font-bold tracking-[0.2em] uppercase">Wari Vécu</h1>
            <p class="text-slate-500 text-xs mt-2 uppercase tracking-widest">Espace d'administration</p>
        </div>

        <div class="bg-slate-900 border border-[#D4AF37]/20 p-8 rounded-2xl shadow-2xl backdrop-blur-sm">
            <form method="POST" class="space-y-6">
                
                <?php if(isset($error)): ?>
                    <div class="bg-red-500/10 border border-red-500/50 text-red-500 text-xs p-3 rounded-lg text-center font-medium animate-pulse">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div>
                    <label class="block text-xs font-bold text-[#D4AF37] uppercase mb-2 tracking-tighter">Identifiant</label>
                    <input type="text" name="user" required 
                        class="w-full bg-slate-950 border border-slate-800 p-4 rounded-xl text-white outline-none focus:border-[#D4AF37] transition-all gold-glow"
                        placeholder="Esdras">
                </div>

                <div>
                    <label class="block text-xs font-bold text-[#D4AF37] uppercase mb-2 tracking-tighter">Mot de passe</label>
                    <input type="password" name="pass" required 
                        class="w-full bg-slate-950 border border-slate-800 p-4 rounded-xl text-white outline-none focus:border-[#D4AF37] transition-all gold-glow"
                        placeholder="••••••••">
                </div>

                <button type="submit" 
                    class="w-full bg-[#D4AF37] text-slate-950 font-bold py-4 rounded-xl uppercase text-xs tracking-[0.2em] hover:bg-[#bfa032] active:scale-[0.98] transition-all shadow-lg shadow-[#D4AF37]/10">
                    S'authentifier
                </button>
            </form>
        </div>

        <div class="text-center mt-8">
            <a href="../index.php" class="text-slate-600 hover:text-[#D4AF37] text-xs transition-colors uppercase tracking-widest font-medium">
                ← Retour au site public
            </a>
        </div>
    </div>

</body>
</html>
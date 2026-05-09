<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wari - Mode Hors Ligne</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="./assets/warifinance3d.png" />
    <link rel="stylesheet" href="./assets/styles.css">
    <style>
        body { background: #010203; color: #f8fafc; font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="p-4 flex items-center justify-center min-h-screen">
    <div class="max-w-md w-full glass-card p-8 text-center border border-slate-800 shadow-2xl">
        <div class="w-20 h-20 mx-auto mb-6 bg-slate-900 rounded-full flex items-center justify-center border border-slate-700 relative">
            <svg class="w-10 h-10 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
            </svg>
            <!-- Ligne barrée pour le mode hors ligne -->
            <svg class="w-12 h-12 text-amber-500 absolute" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <line x1="4" y1="4" x2="20" y2="20" />
            </svg>
        </div>
        
        <h1 class="text-2xl font-black mb-2 text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-yellow-600">Vous êtes hors ligne</h1>
        
        <p class="text-slate-400 text-sm mb-8 leading-relaxed">
            Pas de panique ! Vous pouvez toujours consulter les cours et les pages que vous avez déjà visités auparavant.
        </p>

        <button onclick="window.location.reload()" class="w-full py-4 bg-amber-500 hover:bg-amber-400 text-black font-black uppercase tracking-wider rounded-xl transition-all shadow-lg shadow-amber-500/20 active:scale-95 mb-4">
            Réessayer la connexion
        </button>

        <button onclick="window.history.back()" class="w-full py-4 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold uppercase tracking-wider rounded-xl transition-all active:scale-95">
            Retourner en arrière
        </button>
    </div>
</body>
</html>

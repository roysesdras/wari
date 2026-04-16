<?php 
require_once 'auth.php';
require_once __DIR__ . '/../../config/db.php'; // Vérifie bien le chemin vers ta config

// 1. Récupération des articles
$articles = $pdo->query("SELECT id, titre, mois_compteur FROM wari_articles ORDER BY date_publication DESC")->fetchAll();

// 2. Récupération du nombre d'abonnés WhatsApp
$count_subscribers = $pdo->query("SELECT COUNT(*) FROM wari_subscribers WHERE status = 'actif'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="icon" type="image/png" href="https://wari.digiroys.com/assets/warifinance3d.png" />
    <link rel="apple-touch-icon" href="https://wari.digiroys.com/assets/warifinance3d.png">
    <meta name="theme-color" content="#020617">

    <title>Wari Vécu Admin</title>
    <style>
        body { font-family: 'Quicksand', sans-serif; }
    </style>
</head>
<body class="bg-slate-950 text-slate-200 p-6 md:p-10">
    <div class="max-w-4xl mx-auto">
        
        <div class="flex justify-between items-center mb-12">
            <h1 class="text-2xl font-bold text-white uppercase tracking-tighter italic underline decoration-[#D4AF37]">Wari Vécu Admin</h1>
            <div class="flex gap-4">
                <a href="insert.php" class="bg-[#D4AF37] text-slate-950 px-4 py-2 rounded font-bold text-xs uppercase hover:bg-white transition-colors">+ Nouveau Récit</a>
                <a href="logout.php" class="text-slate-500 hover:text-white text-xs uppercase flex items-center">Déconnexion</a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">
            <div class="bg-slate-900 p-6 rounded-2xl border border-white/5 flex flex-col justify-between">
                <div>
                    <h3 class="text-slate-500 text-[10px] uppercase font-black tracking-widest mb-1">Abonnés WhatsApp</h3>
                    <p class="text-4xl font-bold text-white"><?php echo $count_subscribers; ?></p>
                </div>
                <div class="mt-6">
                    <a href="export_subscribers.php" class="inline-flex items-center gap-2 text-[#D4AF37] text-xs font-bold uppercase hover:underline">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12 a2 2 0 002-2v-1M7 10l5 5 5-5M12 15V3" />
                        </svg>
                        Télécharger la liste CSV
                    </a>
                </div>
            </div>
            
            <div class="bg-slate-900 p-6 rounded-2xl border border-white/5 flex flex-col justify-center items-center text-center opacity-50">
                <p class="text-[10px] text-slate-500 uppercase tracking-widest italic">Plus de stats à venir...</p>
            </div>
        </div>

        <h2 class="text-xs font-black uppercase tracking-widest text-[#D4AF37] mb-6 flex items-center gap-3">
            Journal de bord
            <span class="h-px flex-1 bg-white/5"></span>
        </h2>

        <div class="space-y-3">
            <?php foreach($articles as $a): ?>
            <div class="group bg-slate-900/50 p-5 rounded-xl border border-white/5 flex justify-between items-center hover:bg-slate-900 hover:border-[#D4AF37]/20 transition-all">
                <div class="flex items-center gap-4">
                    <span class="bg-slate-950 text-[#D4AF37] text-[10px] font-bold px-2 py-1 rounded border border-[#D4AF37]/20">
                        <?php echo $a['mois_compteur']; ?>
                    </span>
                    <span class="font-medium text-slate-300 group-hover:text-white transition-colors">
                        <?php echo $a['titre']; ?>
                    </span>
                </div>
                <div class="flex gap-4 text-[10px] font-bold uppercase tracking-widest">
                    <a href="edit.php?id=<?php echo $a['id']; ?>" class="text-slate-500 hover:text-blue-400 transition-colors">Modifier</a>
                    <a href="delete.php?id=<?php echo $a['id']; ?>" onclick="return confirm('Supprimer ce récit ?')" class="text-slate-500 hover:text-red-500 transition-colors">Supprimer</a>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if(empty($articles)): ?>
            <div class="text-center py-20 border-2 border-dashed border-white/5 rounded-2xl">
                <p class="text-slate-600 italic text-sm">Aucun récit publié pour le moment.</p>
            </div>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>
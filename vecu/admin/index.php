<?php 
require_once 'auth.php';
require_once __DIR__ . '/../../config/db.php';

$articles = $pdo->query("SELECT id, titre, mois_compteur FROM wari_articles ORDER BY date_publication DESC")->fetchAll();
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
<body class="bg-slate-950 text-slate-200 p-10 font-[Quicksand]">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-12">
            <h1 class="text-2xl font-bold text-white uppercase tracking-tighter italic underline decoration-[#D4AF37]">Wari Vécu Admin</h1>
            <div class="flex gap-4">
                <a href="insert.php" class="bg-[#D4AF37] text-slate-950 px-4 py-2 rounded font-bold text-xs uppercase">+ Nouveau</a>
                <a href="logout.php" class="text-slate-500 hover:text-white text-xs uppercase flex items-center">Déconnexion</a>
            </div>
        </div>

        <div class="space-y-4">
            <?php foreach($articles as $a): ?>
            <div class="bg-slate-900 p-4 rounded border border-slate-800 flex justify-between items-center">
                <div>
                    <span class="text-[#D4AF37] text-xs font-bold mr-4"><?php echo $a['mois_compteur']; ?></span>
                    <span class="font-medium"><?php echo $a['titre']; ?></span>
                </div>
                <div class="flex gap-6 text-xs font-bold uppercase tracking-widest">
                    <a href="edit.php?id=<?php echo $a['id']; ?>" class="text-blue-400 hover:text-blue-300">Modifier</a>
                    <a href="delete.php?id=<?php echo $a['id']; ?>" onclick="return confirm('Supprimer ce récit ?')" class="text-red-500 hover:text-red-400">Supprimer</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
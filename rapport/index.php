<?php
require_once __DIR__ . '/../config/db.php';

// 1. Récupération des statistiques globales pour rassurer l'investisseur
$stats = $pdo->query("SELECT 
    SUM(nb_participants) as total_p, 
    SUM(nb_nouveaux_abonnes) as total_a,
    COUNT(id) as total_actions
    FROM wari_rapports_impact")->fetch();

// 2. Récupération de la liste des rapports du plus ancien au plus récent
$query = $pdo->query("SELECT * FROM wari_rapports_impact ORDER BY date_evenement ASC");
$rapports = $query->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapports d'Impact | Wari-Finance</title>

    <meta name="description" content="Suivi et de documentation de l'impact social et financier de Wari-Finance. Suivez nos actions sur le terrain.">
    <meta name="author" content="Wari-Finance">
    <meta name="robots" content="noindex, nofollow"> <meta property="og:type" content="website">
    <meta property="og:url" content="https://wari.digiroys.com/rapport/">
    <meta property="og:title" content="Wari-Finance Rapport d'Impact">
    <meta property="og:description" content="Gestionnaire des rapports d'activités et indicateurs de performance.">
    <!-- <meta property="og:image" content="https://wari.digiroys.com/assets/img/logo-wari-meta.png"> -->

    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:title" content="Wari-Finance Rapport d'Impact">
    <meta property="twitter:description" content="Suivi financier et impact social des activités Wari-Finance.">

    <link rel="icon" type="image/png" href="https://wari.digiroys.com/assets/warifinance3d.png" />
    <link rel="apple-touch-icon" href="https://wari.digiroys.com/assets/warifinance3d.png">
    <meta name="theme-color" content="#1e293b">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Quicksand', sans-serif; background-color: #f3f4f5cd; }
        .glass-card { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.5); }
    </style>
</head>
<body class="p-3 md:p-12 text-slate-900">

    <div class="max-w-6xl mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-12 gap-6">
            <div>
                <h1 class="text-4xl font-black italic tracking-tighter uppercase text-slate-900">Impact <span class="text-[#D4AF37]">Archive</span></h1>
                <p class="text-slate-500 font-bold uppercase text-xs tracking-widest mt-1">Transparence et croissance de Wari-Finance</p>
            </div>
            <div class="flex gap-4">
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 text-center min-w-[120px]">
                    <span class="block text-2xl font-black text-[#D4AF37]"><?php echo number_format($stats['total_p'] ?? 0); ?></span>
                    <span class="text-[9px] uppercase font-black text-slate-400">Âmes Touchées</span>
                </div>
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 text-center min-w-[120px]">
                    <span class="block text-2xl font-black text-green-600">+<?php echo number_format($stats['total_a'] ?? 0); ?></span>
                    <span class="text-[9px] uppercase font-black text-slate-400">Abonnés WA</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach($rapports as $r): ?>
                <div class="group bg-white rounded-3xl border border-slate-100 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden flex flex-col">
                    <div class="p-4 pb-0 flex justify-between items-start">
                        <span class="px-3 py-1 bg-slate-100 text-slate-600 rounded-full text-[10px] font-black uppercase tracking-widest">
                            <?php echo $r['type_activite']; ?>
                        </span>
                        <span class="text-slate-400 text-xs font-bold"><?php echo date('d/m/Y', strtotime($r['date_evenement'])); ?></span>
                    </div>

                    <div class="p-4 flex-grow">
                        <h2 class="text-xl font-extrabold text-slate-900 mb-2 leading-tight group-hover:text-[#D4AF37] transition-colors">
                            <?php echo strip_tags($r['titre_rapport']); ?>
                        </h2>
                        <div class="flex items-center text-slate-500 text-sm mb-4">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <?php echo $r['lieu']; ?>, <?php echo $r['pays']; ?>
                        </div>

                        <div class="grid grid-cols-2 gap-2 mb-4">
                            <div class="bg-slate-50 p-2 rounded-xl text-center">
                                <span class="block text-sm font-black"><?php echo $r['nb_participants']; ?></span>
                                <span class="text-[8px] uppercase text-slate-400 font-bold">Part.</span>
                            </div>
                            <div class="bg-green-50 p-2 rounded-xl text-center">
                                <span class="block text-sm font-black text-green-700">+<?php echo $r['nb_nouveaux_abonnes']; ?></span>
                                <span class="text-[8px] uppercase text-green-600 font-bold">WhatsApp</span>
                            </div>
                        </div>

                        <p class="text-slate-600 text-sm line-clamp-3 mb-4 italic">
                            <?php echo strip_tags($r['bilan_texte']); ?>
                        </p>
                    </div>

                    <div class="p-4 pt-0 mt-auto">
                        <a href="view.php?id=<?php echo $r['id']; ?>" class="block w-full text-center bg-slate-900 text-white font-black py-3 rounded-2xl text-[10px] uppercase tracking-[0.2em] hover:bg-[#D4AF37] hover:text-slate-900 transition-all">
                            Lire le Rapport Complet
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if(empty($rapports)): ?>
                <div class="col-span-full py-20 text-center border-2 border-dashed border-slate-200 rounded-3xl">
                    <p class="text-slate-400 font-bold uppercase tracking-widest text-sm">Aucun impact scellé pour le moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-20 text-center">
        <p class="text-slate-400 text-[10px] uppercase font-black tracking-widest">
            Wari-Finance rapport Impact &copy; <span id="currentYear"></span>
        </p>

        <script>
            // Récupère l'année en cours et l'insère dans le span
            document.getElementById('currentYear').textContent = new Date().getFullYear();
        </script>
    </div>

</body>
</html>
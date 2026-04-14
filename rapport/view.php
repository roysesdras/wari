<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM wari_rapports_impact WHERE id = ?");
$stmt->execute([$id]);
$r = $stmt->fetch();

if (!$r) {
    die("Rapport introuvable.");
}

// Décodage des photos
$photos = json_encode([]);
if (!empty($r['photos_json'])) {
    $photos = json_decode($r['photos_json'], true);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($r['titre_rapport'] ?? 'Rapport sans titre') ?> | Rapport d'impact Wari</title>

    <meta name="description" content="Rapport d'impact Wari-Finance : <?= htmlspecialchars(substr($r['bilan_texte'], 0, 150)) ?>...">
    <meta name="author" content="Wari-Finance">

    <meta property="og:title" content="<?= htmlspecialchars($r['titre_rapport']) ?> - Rapport d'impact Wari">
    <meta property="og:description" content="Activité menée à <?= htmlspecialchars($r['lieu']) ?>, <?= htmlspecialchars($r['pays']) ?> le <?= $r['date_evenement'] ?>.">
    <meta property="og:type" content="article">

    <?php 
        // $photos = json_decode($r['photos_json'], true);
        // $og_image = (!empty($photos)) ? "https://wari.digiroys.com/rapport/admin/assets/uploads/" . $photos[0] : "https://wari.digiroys.com/rapport/admin/assets/img/default-share.png";
    ?>
    <!-- <meta property="og:image" content="<?= $og_image ?>"> -->

    <link rel="icon" type="image/png" href="https://wari.digiroys.com/assets/warifinance3d.png" />
    <link rel="apple-touch-icon" href="https://wari.digiroys.com/assets/warifinance3d.png">

    <meta name="theme-color" content="#1e293b">


    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700;800&display=swap" rel="stylesheet">
    <style>
         body { font-family: 'Quicksand', sans-serif; background-color: #e7e4e4ff; color: #1e293b; }
        .rich-content ul { list-style-type: disc; margin-left: 1.5rem; margin-bottom: 1rem; } 
        .rich-content p{margin-bottom:1rem;line-height:1.7}.stat-card{border-left:4px solid #d4af37}.prose .ql-editor div,.prose .ql-editor p,.prose div,.prose p{margin-top:0!important;margin-bottom:1.5rem;font-size:1.1rem}.prose div,.prose p{margin-top:0!important;margin-bottom:1.5rem;line-height:inherit!important}.prose div:has(> br:only-child),.prose p:has(> br:only-child){height:0!important;line-height:0!important;margin:0!important;padding:0!important;overflow:hidden!important}.prose div:empty,.prose p:empty{display:none!important}.prose h2,.prose h3{margin-bottom:0!important;margin-top:1em!important;line-height:1.2!important;padding-bottom:0!important}
    </style>
</head>
<body class="bg-slate-50/50">

    <nav class="p-3 max-w-5xl mx-auto flex justify-between items-center">
        <a href="index.php" class="text-slate-400 hover:text-slate-900 font-bold text-xs uppercase tracking-widest flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Retour aux archives
        </a>
        <div class="text-[10px] font-black uppercase bg-slate-900 text-white px-4 py-1 rounded-full tracking-tighter">
            Rapport Ref: #<?php echo $r['id']; ?>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto p-3 pb-10">
        
        <header class="mb-12">
            <div class="flex items-center space-x-2 mb-4">
                <span class="bg-[#D4AF37]/10 text-[#D4AF37] px-3 py-1 rounded-lg text-[10px] font-black uppercase italic">
                    <?php echo $r['type_activite']; ?>
                </span>
                <?php if($r['nom_organisation']): ?>
                    <span class="text-slate-400 text-xs font-bold"> : </span>
                    <span class="text-slate-900 text-xs font-black uppercase"><?php echo $r['nom_organisation']; ?></span>
                <?php endif; ?>
            </div>
            
            <h1 class="text-4xl md:text-5xl font-black text-slate-900 leading-tight mb-6">
                <?php echo $r['titre_rapport']; ?>
            </h1>

            <div class="flex flex-wrap items-center gap-6 text-slate-500 font-bold text-sm border-b border-slate-200 pb-8">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2 text-[#D4AF37]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-width="2"/></svg>
                    <?php echo date('d F Y', strtotime($r['date_evenement'])); ?>
                </div>
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2 text-[#D4AF37]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" stroke-width="2"/></svg>
                    <?php echo $r['lieu']; ?>, <?php echo $r['pays']; ?>
                </div>
            </div>
        </header>

        <section class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-12 border-b border-slate-200 pb-8">
            <div class="bg-white p-4 rounded-3xl shadow-sm stat-card">
                <span class="block text-3xl font-black text-slate-900"><?php echo $r['nb_participants']; ?></span>
                <span class="text-[10px] uppercase font-bold text-slate-400">Audience Totale</span>
            </div>
            <div class="bg-white p-4 rounded-3xl shadow-sm stat-card">
                <span class="block text-3xl font-black text-green-600">+<?php echo $r['nb_nouveaux_abonnes']; ?></span>
                <span class="text-[10px] uppercase font-bold text-slate-400">Nouv. Abonnés WA</span>
            </div>
            <div class="bg-white p-4 rounded-3xl shadow-sm stat-card">
                <div class="flex space-x-2">
                    <span class="text-blue-500 font-bold"><?php echo $r['nb_hommes']; ?>Hm</span>
                    <span class="text-pink-500 font-bold"><?php echo $r['nb_femmes']; ?>Fm</span>
                </div>
                <span class="text-[9px] uppercase font-bold text-slate-400 mt-1 block">Répartition Sexe</span>
            </div>
            <div class="bg-white p-4 rounded-3xl shadow-sm stat-card">
                <div class="text-[11px] font-bold text-slate-700 leading-tight">
                    <span class="text-blue-500 font-bold">J :</span> <?php echo $r['age_jeunes']; ?> | <span class="text-pink-500 font-bold">A :</span> <?php echo $r['age_adultes']; ?> | <span class="text-green-500 font-bold">S :</span> <?php echo $r['age_seniors']; ?>
                </div>
                <span class="text-[10px] uppercase font-bold text-slate-400 mt-1 block">Tranches d'âge</span>
            </div>
        </section>

        <!-- BILAN FINANCIER — après la section <section class="grid grid-cols-2 md:grid-cols-4 ..."> -->

        <section class="bg-slate-900 rounded-xl p-4 mb-12 grid grid-cols-3 gap-4 text-center border-b border-slate-200 pb-8">
            <div>
                <span class="block text-[10px] uppercase font-black text-slate-400 mb-2 tracking-widest">Dépenses</span>
                <span class="block text-2xl font-black text-red-400">
                    <?php echo number_format($r['budget_depense'], 0, ',', ' '); ?> XOF
                </span>
            </div>
            <div>
                <span class="block text-[10px] uppercase font-black text-slate-400 mb-2 tracking-widest">Recettes</span>
                <span class="block text-2xl font-black text-emerald-400">
                    <?php echo number_format($r['recettes_generees'], 0, ',', ' '); ?> XOF
                </span>
            </div>
            <div>
                <?php 
                $balance = $r['recettes_generees'] - $r['budget_depense'];
                $balanceColor = $balance >= 0 ? 'text-[#D4AF37]' : 'text-red-400';
                $balanceSign  = $balance >= 0 ? '+' : '';
                ?>
                <span class="block text-[10px] uppercase font-black text-slate-400 mb-2 tracking-widest">Bénéfice net</span>
                <span class="block text-2xl font-black <?php echo $balanceColor; ?>">
                    <?php echo $balanceSign . number_format($balance, 0, ',', ' '); ?> XOF
                </span>
            </div>
        </section>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
            
            <div class="md:col-span-2 space-y-12">
                <article>
                    <h3 class="text-xs font-black uppercase tracking-[0.2em] text-slate-400 mb-4 italic">Le Récit de l'Impact</h3>
                    <div class="prose prose-invert rich-content text-lg text-slate-700 prose-a:text-[#D4AF37] hover:prose-a:text-white prose-strong:text-[#D4AF37] prose-h2:text-[#D4AF37] prose-h3:text-[#D4AF37] prose-ul:my-2 prose-li:my-0 text-normal">
                        <?php echo $r['bilan_texte']; ?>
                    </div>
                </article>

                <?php if($r['citations_cles']): ?>
                    <div class="bg-[#D4AF37]/5 border-l-4 border-[#D4AF37] p-3 rounded-r-3xl">
                        <p class="text-[9px] uppercase font-black text-[#D4AF37] tracking-widest mb-3">
                            Ressenti
                        </p>
                        <p class="italic text-xl text-slate-800">
                            "<?php echo nl2br(html_entity_decode($r['citations_cles'], ENT_QUOTES, 'UTF-8')); ?>"
                        </p>
                        <p class="text-[10px] text-slate-400 font-bold mt-3 text-right">
                            — Synthèse de <?php echo $r['nb_participants']; ?> participants
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="space-y-8">
                <?php if($r['points_vigilance']): ?>
                <div class="bg-red-50 p-3 rounded-xl border border-red-100">
                    <h4 class="text-[10px] font-black uppercase text-red-400 mb-3 tracking-widest">Points de Vigilance</h4>
                    <p class="text-sm text-red-900/70 font-medium"><?php echo nl2br($r['points_vigilance']); ?></p>
                </div>
                <?php endif; ?>

                <?php if(!empty($photos)): ?>
                <div>
                    <h4 class="text-[10px] font-black uppercase text-slate-400 mb-4 tracking-widest">Preuves Terrain</h4>
                    <div class="grid grid-cols-1 gap-4">
                        <?php foreach($photos as $img): ?>
                            <img src="./assets/uploads/<?php echo $img; ?>" class="w-full h-48 object-cover rounded-2xl shadow-sm hover:scale-[1.02] transition-transform" alt="Photo terrain">
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if($r['dossier_media_path']): ?>
                <a href="<?php echo $r['dossier_media_path']; ?>" target="_blank" class="flex items-center justify-center space-x-2 bg-slate-900 text-white p-4 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-[#D4AF37] transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" stroke-width="2"/></svg>
                    <span>Voir toutes les vidéos</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="border-t border-slate-100 py-12 text-center">
        <p class="text-slate-400 text-[10px] uppercase font-black tracking-widest">Documenté par Wari-Finance</p>
    </footer>

</body>
</html>
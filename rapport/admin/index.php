<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/db.php';

// Gestion de la suppression
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM wari_rapports_impact WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: index.php?msg=supprime");
    exit;
}

// Récupération des rapports
$stmt = $pdo->query("SELECT * FROM wari_rapports_impact ORDER BY date_evenement DESC");
$rapports = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr" class="bg-gray-200">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Impact | Wari</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { wariGold: '#D4AF37', wariDark: '#1e293b' },
                    fontFamily: { sans: ['Quicksand', 'sans-serif'] },
                }
            }
        }
    </script>
</head>
<body class="p-4 sm:p-8 md:p-12 text-slate-800 antialiased">
    <div class="max-w-6xl mx-auto">
        
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6 mb-8">
            <div>
                <h1 class="text-3xl font-extrabold text-wariDark uppercase italic tracking-tighter">Impact Manager</h1>
                <p class="text-slate-500 text-xs tracking-widest uppercase font-bold">Administration des rapports</p>
            </div>
            <a href="insert.php" class="w-full sm:w-auto flex items-center justify-center gap-2 bg-wariDark text-white px-6 py-3 rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-wariGold transition-all shadow-lg active:scale-95">
                <span>+</span> Nouveau Rapport
            </a>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <div class="bg-emerald-100 border border-emerald-200 text-emerald-600 px-4 py-3 rounded-xl mb-6 text-center text-sm font-bold animate-pulse">
                Action effectuée avec succès.
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 gap-4 md:hidden">
            <?php foreach($rapports as $index => $r): ?>
                <div class="rapport-item bg-white p-5 rounded-xl border border-white shadow-sm <?= $index >= 10 ? 'hidden' : '' ?>">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-xs font-black text-wariGold uppercase tracking-tighter"><?= $r['date_evenement'] ?></span>
                        <span class="bg-slate-100 text-[10px] px-2 py-1 rounded-lg font-bold text-slate-800"><?= $r['type_activite'] ?></span>
                    </div>
                    <h3 class="text-base font-bold text-wariDark mb-1 leading-tight"><?= html_entity_decode($r['titre_rapport'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p class="text-xs text-slate-400 mb-4"><?= html_entity_decode($r['lieu'], ENT_QUOTES, 'UTF-8') ?>, <?= html_entity_decode($r['pays'], ENT_QUOTES, 'UTF-8') ?></p>
                    
                    <div class="flex gap-2 border-t border-slate-50 pt-4">
                        <a href="edit.php?id=<?= $r['id'] ?>" class="flex-1 text-center bg-slate-50 text-slate-600 py-2.5 rounded-xl text-xs font-bold border border-slate-100 uppercase">Modifier</a>
                        <a href="?delete=<?= $r['id'] ?>" onclick="return confirm('Supprimer définitivement ?')" class="flex-1 text-center bg-red-50 text-red-500 py-2.5 rounded-xl text-xs font-bold border border-red-100 uppercase">Supprimer</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="hidden md:block overflow-hidden bg-white rounded-[1rem] border border-white shadow-xl">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="px-6 py-5 text-xs uppercase font-black text-slate-400 italic">Date & Titre</th>
                        <th class="px-6 py-5 text-xs uppercase font-black text-slate-400 text-center">Type</th>
                        <th class="px-6 py-5 text-xs uppercase font-black text-slate-400 text-center">Stats</th>
                        <th class="px-6 py-5 text-xs uppercase font-black text-slate-400 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <?php foreach($rapports as $index => $r): ?>
                    <tr class="rapport-item hover:bg-slate-50/80 transition-colors <?= $index >= 10 ? 'hidden' : '' ?>">
                        <td class="px-6 py-3">
                            <div class="text-[15px] font-bold text-wariDark"><?= html_entity_decode($r['titre_rapport'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="text-[11px] text-slate-400 font-bold uppercase tracking-wide"><?= $r['date_evenement'] ?> • <?= html_entity_decode($r['lieu'], ENT_QUOTES, 'UTF-8') ?></div>
                        </td>
                        <td class="px-6 py-3 text-center">
                            <span class="inline-block bg-slate-100 text-slate-500 text-[10px] font-black px-3 py-1 rounded-lg uppercase tracking-tighter">
                                <?= $r['type_activite'] ?>
                            </span>
                        </td>
                        <td class="px-6 py-3">
                            <div class="flex justify-center gap-4">
                                <div class="text-center">
                                    <div class="text-sm font-black text-wariDark"><?= $r['nb_participants'] ?></div>
                                    <div class="text-[9px] font-bold uppercase text-slate-400">P.</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-sm font-black text-emerald-500"><?= $r['nb_nouveaux_abonnes'] ?></div>
                                    <div class="text-[9px] font-bold uppercase text-slate-400">WA</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <a href="edit.php?id=<?= $r['id'] ?>" class="p-2.5 bg-slate-50 hover:bg-wariDark hover:text-white rounded-xl transition-all border border-slate-100 shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </a>
                                <a href="?delete=<?= $r['id'] ?>" onclick="return confirm('Supprimer définitivement ?')" class="p-2.5 bg-red-50 hover:bg-red-500 hover:text-white text-red-400 rounded-xl transition-all border border-red-100 shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if(empty($rapports)): ?>
                <div class="p-20 text-center text-slate-300 font-bold uppercase tracking-widest text-xs">Aucun rapport scellé</div>
            <?php endif; ?>
        </div>

        <?php if(count($rapports) > 10): ?>
            <div class="mt-8 text-center">
                <button id="toggleBtn" onclick="toggleRapports()" class="bg-white hover:bg-wariDark hover:text-white text-wariDark font-black py-4 px-10 rounded-2xl text-[11px] uppercase tracking-[0.3em] transition-all shadow-lg border border-white">
                    Afficher tout le journal
                </button>
            </div>
        <?php endif; ?>

    </div>

    <script>
        let isExpanded = false;
        function toggleRapports() {
            const items = document.querySelectorAll('.rapport-item');
            const btn = document.getElementById('toggleBtn');
            isExpanded = !isExpanded;

            items.forEach((item, index) => {
                if (index >= 10) {
                    item.classList.toggle('hidden');
                }
            });

            btn.innerText = isExpanded ? "Réduire l'affichage" : "Afficher tout le journal";
        }
    </script>
</body>
</html>
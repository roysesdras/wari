<?php
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     echo "<pre>"; print_r($_POST); echo "</pre>"; die();
// }
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/db.php';

$message = "";
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header("Location: index.php");
    exit;
}

// 1. RÉCUPÉRATION DES DONNÉES ACTUELLES
$stmt = $pdo->prepare("SELECT * FROM wari_rapports_impact WHERE id = ?");
$stmt->execute([$id]);
$rapport = $stmt->fetch();

if (!$rapport) {
    die("Rapport introuvable.");
}

// 2. TRAITEMENT DE LA MISE À JOUR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_action'])) {
    
    // Gestion des photos
    $uploaded_images = json_decode($rapport['photos_json'], true) ?: [];
    if (!empty($_FILES['photos']['name'][0])) {
        $target_dir = __DIR__ . "/../assets/uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0775, true);

        foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
            if (count($uploaded_images) >= 5) break; 
            $file_name = time() . "_" . basename($_FILES['photos']['name'][$key]);
            if (move_uploaded_file($tmp_name, $target_dir . $file_name)) {
                $uploaded_images[] = $file_name;
            }
        }
    }
    $images_json = json_encode($uploaded_images);

    try {
        $sql = "UPDATE wari_rapports_impact SET 
                date_evenement = ?, lieu = ?, pays = ?, type_activite = ?, nom_organisation = ?, 
                titre_rapport = ?, nb_participants = ?, nb_hommes = ?, nb_femmes = ?, 
                age_jeunes = ?, age_adultes = ?, age_seniors = ?, nb_nouveaux_abonnes = ?, budget_depense = ?, recettes_generees = ?, 
                bilan_texte = ?, citations_cles = ?, points_vigilance = ?, 
                dossier_media_path = ?, photos_json = ?
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        
        // ✅ ORDRE CORRIGÉ — correspond exactement à l'ordre des colonnes dans le SQL
        $stmt->execute([
            $_POST['date_evenement'] ?? $rapport['date_evenement'], // 1. date_evenement
            htmlspecialchars($_POST['lieu'] ?? ''), // 2. lieu
            htmlspecialchars($_POST['pays'] ?? ''), // 3. pays
            $_POST['type_activite'] ?? 'Autre', // 4. type_activite
            htmlspecialchars($_POST['nom_organisation'] ?? ''), // 5. nom_organisation
            htmlspecialchars($_POST['titre_rapport'] ?? 'Sans titre'), // 6. titre_rapport
            (int)($_POST['nb_participants'] ?? 0), // 7. nb_participants
            (int)($_POST['nb_hommes'] ?? 0), // 8. nb_hommes
            (int)($_POST['nb_femmes'] ?? 0), // 9. nb_femmes
            (int)($_POST['age_jeunes'] ?? 0), // 10. age_jeunes
            (int)($_POST['age_adultes'] ?? 0), // 11. age_adultes
            (int)($_POST['age_seniors'] ?? 0), // 12. age_seniors
            (int)($_POST['nb_nouveaux_abonnes'] ?? 0), // 13. nb_nouveaux_abonnes
            (float)($_POST['budget_depense'] ?? 0), // 14. budget_depense
            (float)($_POST['recettes_generees'] ?? 0), // 15. recettes_generees
            $_POST['bilan_texte'] ?? '', // 16. bilan_texte
            htmlspecialchars($_POST['citations_cles'] ?? ''), // 17. citations_cles
            htmlspecialchars($_POST['points_vigilance'] ?? ''), // 18. points_vigilance
            htmlspecialchars($_POST['dossier_media_path'] ?? ''), // 19. dossier_media_path
            $images_json, // 20. photos_json
            $id // WHERE id = ?
        ]);
        // echo "<pre>"; print_r($_POST); echo "</pre>"; die();
        // Redirection immédiate pour éviter le double POST au rafraîchissement
        header("Location: index.php?msg=updated");
        exit;

    } catch (PDOException $e) {
        $message = "<div class='bg-red-100 text-red-700 p-4 rounded-xl mb-6'>Erreur : " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr" class="bg-gray-200">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Rapport | Wari</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
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
<body class="p-3 sm:p-4 md:p-12 text-slate-800 antialiased">
    <div class="max-w-4xl mx-auto">
        
        <div class="flex flex-row justify-between items-center gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-extrabold text-wariDark uppercase italic tracking-tighter">Editer Rapport</h1>
                <p class="text-slate-500 text-[10px] tracking-widest uppercase font-bold">Édition des données scellées</p>
            </div>
            <a href="index.php"  class="text-center bg-white hover:bg-slate-50 text-slate-600 px-6 py-2.5 rounded-xl text-xs font-bold transition-all border border-slate-300 shadow-sm whitespace-nowrap">
                Annuler
            </a>
        </div>

        <?php echo $message; ?>

        <form action="edit.php?id=<?= $id ?>" method="POST" enctype="multipart/form-data" id="impactForm" class="flex flex-col gap-6 sm:grid sm:grid-cols-2 sm:gap-8 bg-white p-3 sm:p-4 rounded-[1rem] shadow-xl border border-white">
    
        <input type="hidden" name="update_action" value="1">
            
            <div class="sm:col-span-2 space-y-4 border-b border-slate-100 pb-6">
                <div class="flex flex-col sm:grid sm:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-[11px] uppercase font-black text-slate-400 mb-2 ml-1">Date</label>
                        <input type="date" name="date_evenement" value="<?= $rapport['date_evenement'] ?>" required class="w-full bg-slate-50 border border-slate-400 rounded-xl p-3 outline-none focus:border-wariGold focus:ring-2 focus:ring-wariGold/20 text-slate-700">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-[11px] uppercase font-black text-slate-400 mb-2 ml-1">Titre</label>
                        <input type="text" name="titre_rapport" value="<?= htmlspecialchars($rapport['titre_rapport']) ?>" required class="w-full bg-slate-50 border border-slate-400 rounded-xl p-3 outline-none focus:border-wariGold text-slate-700">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-[10px] uppercase font-black text-slate-400 mb-2 ml-1">Pays</label>
                    <input type="text" name="pays" value="<?= html_entity_decode($rapport['pays'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="ex: Bénin" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 outline-none focus:border-wariGold text-slate-700">
                </div>
                <div>
                    <label class="block text-[10px] uppercase font-black text-slate-400 mb-2 ml-1">Ville / Lieu précis</label>
                    <input type="text" name="lieu" value="<?= html_entity_decode($rapport['lieu'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="ex: Cotonou" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 outline-none focus:border-wariGold text-slate-700">
                </div>
            </div>

            <div class="w-full">
                <label class="block text-[11px] uppercase font-black text-slate-400 mb-2 ml-1">Nature</label>
                <select name="type_activite" id="type_activite" onchange="toggleOrgField()" class="w-full bg-slate-50 border border-slate-400 rounded-xl p-3 outline-none focus:border-wariGold text-slate-700">
                    <option value="Micro-trottoir" <?= $rapport['type_activite'] == 'Micro-trottoir' ? 'selected' : '' ?>>Micro-trottoir</option>
                    <option value="Organisation" <?= $rapport['type_activite'] == 'Organisation' ? 'selected' : '' ?>>Organisation</option>
                    <option value="Formation" <?= $rapport['type_activite'] == 'Formation' ? 'selected' : '' ?>>Formation</option>
                    <option value="Autre" <?= $rapport['type_activite'] == 'Autre' ? 'selected' : '' ?>>Autre</option>
                </select>
            </div>

            <div id="org_field" class="<?= $rapport['type_activite'] == 'Organisation' ? '' : 'hidden' ?> w-full">
                <label class="block text-[11px] uppercase font-black text-slate-400 mb-2 ml-1">Nom de l'Organisation</label>
                <input type="text" name="nom_organisation" value="<?= htmlspecialchars($rapport['nom_organisation']) ?>" class="w-full bg-slate-50 border border-slate-400 rounded-xl p-3 outline-none focus:border-wariGold text-slate-700">
            </div>

            <div class="sm:col-span-2 bg-slate-50 p-4 rounded-[1rem] border border-slate-100">
                <div class="grid grid-cols-2 sm:grid-cols-6 gap-4 text-center">
                    <div class="col-span-2">
                        <label class="block text-[11px] uppercase font-bold text-slate-400 mb-1">Total participants</label>
                        <input type="number" name="nb_participants" value="<?= $rapport['nb_participants'] ?>" class="w-full bg-white border border-slate-400 rounded-xl p-3 font-bold text-center">
                    </div>
                    <div>
                        <label class="block text-[11px] uppercase font-bold text-blue-500 mb-1">Homme</label>
                        <input type="number" name="nb_hommes" value="<?= $rapport['nb_hommes'] ?>" class="w-full bg-white border border-slate-400 rounded-xl p-3 text-center">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-pink-500 mb-1">Femme</label>
                        <input type="number" name="nb_femmes" value="<?= $rapport['nb_femmes'] ?>" class="w-full bg-white border border-slate-400 rounded-xl p-3 text-center">
                    </div>
                    <div class="col-span-2 flex gap-1">
                        <input type="number" name="age_jeunes" value="<?= $rapport['age_jeunes'] ?>" class="w-1/3 bg-white border border-slate-400 rounded-xl p-2 text-[14px] text-center" title="-25 ans">
                        <input type="number" name="age_adultes" value="<?= $rapport['age_adultes'] ?>" class="w-1/3 bg-white border border-slate-400 rounded-xl p-2 text-[14px] text-center" title="25-45 ans">
                        <input type="number" name="age_seniors" value="<?= $rapport['age_seniors'] ?>" class="w-1/3 bg-white border border-slate-400 rounded-xl p-2 text-[14px] text-center" title="45+ ans">
                    </div>
                </div>
            </div>

            <div class="sm:col-span-2 bg-emerald-50 p-3 rounded-xl border border-emerald-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <label class="block text-[10px] uppercase font-black text-emerald-700 sm:w-1/2">Conversion WhatsApp (Importance stratégique)</label>
                <input type="number" name="nb_nouveaux_abonnes" value="<?= $rapport['nb_nouveaux_abonnes'] ?>" class="w-full sm:w-32 bg-white border border-emerald-200 rounded-xl p-3 text-emerald-600 font-black text-center focus:ring-2 focus:ring-emerald-200 outline-none">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-[11px] uppercase font-black text-slate-400 mb-2 ml-1">Bilan & Impact</label>
                <div class="rounded-xl border border-slate-200 overflow-hidden bg-white">
                    <div id="editor" class="min-h-[400px] text-slate-700 text-md text-[16px]"><?= $rapport['bilan_texte'] ?></div>
                </div>
                <input type="hidden" name="bilan_texte" id="bilan_texte">
            </div>  

            <div class="sm:col-span-2 mt-4">
                <label class="block text-[10px] uppercase font-black text-slate-500 mb-2 ml-1">Citations & Témoignages</label>
                <textarea name="citations_cles" rows="3" placeholder="Ce que les participants ont dit..." class="w-full bg-slate-50 border border-slate-400 rounded-xl p-3 outline-none focus:border-wariGold text-slate-700 placeholder:text-slate-300"><?= html_entity_decode($rapport['citations_cles'], ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div class="sm:col-span-2">
                <label class="block text-[10px] uppercase font-black text-red-500 mb-2 ml-1">Points de vigilance / Échecs</label>
                <textarea name="points_vigilance" rows="2" placeholder="Difficultés rencontrées..." class="w-full bg-red-50/30 border border-red-200 rounded-xl p-3 outline-none focus:border-red-400 text-slate-700 placeholder:text-red-200"><?= htmlspecialchars($rapport['points_vigilance']) ?></textarea>
            </div>

            <div class="sm:col-span-2 bg-slate-800 p-4 rounded-[1rem] shadow-inner text-white">
                <div class="flex items-center gap-2 mb-6 ml-2">
                    <div class="w-2 h-2 bg-wariGold rounded-full"></div>
                    <h2 class="text-[10px] uppercase font-black tracking-[0.2em] text-slate-400">Bilan Financier de l'Action</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div class="space-y-2">
                        <label class="block text-[9px] uppercase font-bold text-slate-400 ml-1">Total Dépenses</label>
                        <div class="relative">
                            <input type="number" name="budget_depense" step="0.01" 
                                value="<?= $rapport['budget_depense'] ?? 0 ?>" 
                                class="w-full bg-slate-700/50 border border-slate-600 rounded-xl p-3 outline-none focus:border-red-400 text-white font-bold">
                            <span class="absolute right-4 top-4 text-[10px] text-slate-500 font-black">XOF</span>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[9px] uppercase font-bold text-slate-400 ml-1">Recettes / Gains</label>
                        <div class="relative">
                            <input type="number" name="recettes_generees" step="0.01" 
                                value="<?= $rapport['recettes_generees'] ?? 0 ?>" 
                                class="w-full bg-slate-700/50 border border-slate-600 rounded-xl p-3 outline-none focus:border-emerald-400 text-white font-bold">
                            <span class="absolute right-4 top-4 text-[10px] text-slate-500 font-black">XOF</span>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[9px] uppercase font-bold text-slate-400 ml-1">Balance (Net)</label>
                        <div id="balance_display" class="w-full bg-slate-900 border border-slate-700 rounded-xl p-3 font-black text-center text-lg">
                            0
                        </div>
                    </div>
                </div>
            </div>

            <div class="sm:col-span-2 space-y-6 bg-slate-50 p-3 rounded-xl border border-slate-100">
    
                <?php 
                $photos = json_decode($rapport['photos_json'], true) ?: [];
                if (!empty($photos)): 
                ?>
                <div>
                    <label class="block text-[10px] uppercase font-black text-slate-400 mb-3 ml-1">Photos actuellement scellées</label>
                    <div class="flex flex-wrap gap-3">
                        <?php foreach ($photos as $img): ?>
                            <div class="relative group">
                                <img src="../assets/uploads/<?= $img ?>" class="w-20 h-20 object-cover rounded-xl border-2 border-white shadow-sm transition-transform group-hover:scale-105">
                                <span class="absolute -top-1 -right-1 bg-emerald-500 w-4 h-4 rounded-full border-2 border-white"></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-[10px] uppercase font-black text-slate-400 mb-2">Ajouter des photos (Max 5 total)</label>
                        <input type="file" name="photos[]" multiple accept="image/*" class="w-full text-xs text-slate-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:bg-wariDark file:text-white file:font-bold hover:file:bg-slate-700 cursor-pointer">
                        <p class="text-[9px] text-slate-400 mt-2 italic font-medium">L'ajout de nouvelles photos s'additionne aux anciennes.</p>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase font-black text-slate-400 mb-2">Lien Vidéo / Dossier Cloud</label>
                        <input type="text" name="dossier_media_path" 
                            value="<?= htmlspecialchars($rapport['dossier_media_path']) ?>" 
                            placeholder="https://drive.google.com/..." 
                            class="w-full bg-white border border-slate-200 rounded-xl p-3 outline-none focus:border-wariGold text-slate-700">
                    </div>
                </div>
            </div>

            <button type="submit" class="sm:col-span-2 bg-wariDark text-white font-black py-3 rounded-xl uppercase text-[10px] tracking-[0.3em] hover:bg-wariGold transition-all shadow-lg active:scale-95">
                Mettre à jour le rapport
            </button>
        </form>
    </div>

    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script>
        var quill = new Quill('#editor', {
            theme: 'snow',
            modules: { toolbar: [['bold', 'italic'], [{ 'list': 'bullet' }], ['clean']] }
        });

        function toggleOrgField() {
            const type = document.getElementById('type_activite').value;
            const orgField = document.getElementById('org_field');
            orgField.classList.toggle('hidden', type !== 'Organisation');
        }

        var form = document.getElementById('impactForm');
        form.onsubmit = function() {
            document.querySelector('#bilan_texte').value = quill.root.innerHTML;
        };
    </script>

    <script src="calcul.js"></script>
</body>
</html>
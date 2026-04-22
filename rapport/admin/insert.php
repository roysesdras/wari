<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../config/db.php';

// On utilise la session pour faire passer le message de succès après redirection
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$message = "";

// On récupère le message en session s'il existe
if (isset($_SESSION['success_msg'])) {
    $message = $_SESSION['success_msg'];
    unset($_SESSION['success_msg']); // On l'efface pour qu'il ne s'affiche qu'une fois
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. PROTECTION ANTI-DOUBLE SOUMISSION : On vérifie un jeton ou une action
    if (!isset($_POST['action_insert'])) {
        header("Location: index.php");
        exit;
    }

    $uploaded_images = [];
    $target_dir = __DIR__ . "/../assets/uploads/";

    if (!empty($_FILES['photos']['name'][0])) {
        if (!is_dir($target_dir)) mkdir($target_dir, 0775, true);

        foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
            if ($key >= 5) break; 

            $file_size = $_FILES['photos']['size'][$key];
            $original_name = $_FILES['photos']['name'][$key];
            $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
            
            // Sécurité : On vérifie l'extension
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($file_ext, $allowed_extensions) && $file_size <= 2 * 1024 * 1024) { 
                // On génère un nom unique pour éviter d'écraser des fichiers
                $file_name = bin2hex(random_bytes(8)) . "_" . time() . "." . $file_ext;
                
                if (move_uploaded_file($tmp_name, $target_dir . $file_name)) {
                    $uploaded_images[] = $file_name;
                }
            }
        }
    }
    $images_json = json_encode($uploaded_images);

    try {
        $sql = "INSERT INTO wari_rapports_impact 
                (date_evenement, lieu, pays, type_activite, nom_organisation, titre_rapport, 
                nb_participants, nb_hommes, nb_femmes, age_jeunes, age_adultes, age_seniors,
                nb_nouveaux_abonnes, bilan_texte, citations_cles, points_vigilance, 
                dossier_media_path, photos_json, budget_depense, recettes_generees) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['date_evenement'], 
            htmlspecialchars($_POST['lieu'] ?? ''), 
            htmlspecialchars($_POST['pays'] ?? ''), 
            $_POST['type_activite'], 
            htmlspecialchars($_POST['nom_organisation'] ?? ''), 
            htmlspecialchars($_POST['titre_rapport'] ?? ''),
            (int)$_POST['nb_participants'], 
            (int)$_POST['nb_hommes'], 
            (int)$_POST['nb_femmes'], 
            (int)$_POST['age_jeunes'], 
            (int)$_POST['age_adultes'], 
            (int)$_POST['age_seniors'],
            (int)$_POST['nb_nouveaux_abonnes'], 
            $_POST['bilan_texte'], 
            htmlspecialchars($_POST['citations_cles'] ?? ''), 
            htmlspecialchars($_POST['points_vigilance'] ?? ''), 
            htmlspecialchars($_POST['dossier_media_path'] ?? ''), 
            $images_json,
            (float)($_POST['budget_depense'] ?? 0),
            (float)($_POST['recettes_generees'] ?? 0),
            // (float)$_POST['balance_net'] ?? 0
        ]);

        // 2. LA CLÉ : Redirection après succès
        $_SESSION['success_msg'] = "<div class='bg-emerald-100 border border-emerald-500 text-emerald-700 px-4 py-3 rounded-xl mb-6 text-center font-bold'>Rapport scellé avec succès !</div>";
        
        // On redirige vers la même page (ou vers index.php)
        header("Location: insert.php"); 
        exit; // On arrête l'exécution du script ici

    } catch (PDOException $e) {
        $message = "<div class='bg-red-100 border border-red-500 text-red-700 px-4 py-3 rounded-xl mb-6 text-center font-bold'>Erreur : " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr" class="bg-gray-200">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saisie d'Impact | Wari</title>

    <link rel="icon" type="image/png" href="https://wari.digiroys.com/assets/warifinance3d.png" />
    <link rel="apple-touch-icon" href="https://wari.digiroys.com/assets/warifinance3d.png">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        wariGold: '#D4AF37',
                        wariDark: '#1e293b',
                    },
                    fontFamily: {
                        sans: ['Quicksand', 'sans-serif'],
                    },
                }
            }
        }
    </script>
</head>
<body class="p-3 sm:p-4 md:p-12 text-slate-800 antialiased">
    <div class="max-w-4xl mx-auto">
        
        <div class="flex flex-row justify-between items-center gap-4 mb-8">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-wariDark uppercase italic tracking-tighter">Rapport d'Impact</h1>
                <p class="text-slate-500 text-[10px] tracking-widest uppercase font-bold">Traçabilité & Transparence</p>
            </div>
            <a href="index.php" class="text-center bg-white hover:bg-slate-50 text-slate-600 px-6 py-2.5 rounded-xl text-xs font-bold transition-all border border-slate-300 shadow-sm whitespace-nowrap">
                Voir la liste
            </a>
        </div>

        <?php echo $message; ?>

        <form method="POST" enctype="multipart/form-data" id="impactForm" class="flex flex-col gap-6 sm:grid sm:grid-cols-2 sm:gap-8 bg-white p-3 sm:p-6 rounded-[1rem] shadow-xl border border-white">
            <input type="hidden" name="action_insert" value="1">
            
            <div class="sm:col-span-2 space-y-4 border-b border-slate-100 pb-6">
                <div class="flex flex-col sm:grid sm:grid-cols-3 gap-5">
                    <div class="order-2 sm:order-1">
                        <label class="block text-[10px] uppercase font-black text-slate-500 mb-2 ml-1">Date de l'action</label>
                        <input type="date" name="date_evenement" required class="w-full bg-slate-50 border border-slate-400 rounded-xl p-4 outline-none focus:border-wariGold focus:ring-2 focus:ring-wariGold/20 transition-all text-slate-700">
                    </div>
                    <div class="sm:col-span-2 order-1 sm:order-2">
                        <label class="block text-[10px] uppercase font-black text-slate-500 mb-2 ml-1">Titre de l'activité</label>
                        <input type="text" name="titre_rapport" required placeholder="ex: Sensibilisation Camp ECSI" class="w-full bg-slate-50 border border-slate-400 rounded-xl p-3 outline-none focus:border-wariGold focus:ring-2 focus:ring-wariGold/20 transition-all text-slate-700 placeholder:text-slate-300">
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-6 sm:contents">
                <div class="w-full">
                    <label class="block text-[10px] uppercase font-black text-slate-500 mb-2 ml-1">Nature de l'activité</label>
                    <select name="type_activite" id="type_activite" onchange="toggleOrgField()" class="w-full bg-slate-50 border border-slate-400 rounded-xl p-3 outline-none focus:border-wariGold appearance-none text-slate-700">
                        <option value="Micro-trottoir">Micro-trottoir</option>
                        <option value="Organisation">Formation / Atelier / Sensibilisation</option>
                        <!-- <option value="Formation">Formation / Atelier</option> -->
                        <option value="Autre">Autre</option>
                    </select>
                </div>

                <div id="org_field" class="hidden w-full">
                    <label class="block text-[10px] uppercase font-black text-slate-500 mb-2 ml-1">Nom de l'Organisation</label>
                    <input type="text" name="nom_organisation" placeholder="ex: Sterna Africa" class="w-full bg-slate-50 border border-slate-400 rounded-xl p-3 outline-none focus:border-wariGold text-slate-700 placeholder:text-slate-300">
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 sm:col-span-2">
                <div class="flex-1">
                    <label class="block text-[10px] uppercase font-black text-slate-500 mb-2 ml-1">Pays</label>
                    <input type="text" name="pays" placeholder="Bénin" class="w-full bg-slate-50 border border-slate-400 rounded-xl p-3 outline-none focus:border-wariGold text-slate-700">
                </div>
                <div class="flex-1">
                    <label class="block text-[10px] uppercase font-black text-slate-500 mb-2 ml-1">Ville / Lieu précis</label>
                    <input type="text" name="lieu" placeholder="Cotonou, Fidjrossè" class="w-full bg-slate-50 border border-slate-400 rounded-xl p-3 outline-none focus:border-wariGold text-slate-700">
                </div>
            </div>

            <div class="sm:col-span-2 bg-slate-100 p-4 rounded-[1rem] border border-slate-100 shadow-inner">
                <h3 class="text-[10px] font-black uppercase text-wariDark mb-6 tracking-[0.2em] flex items-center gap-2">
                    <span class="w-2 h-2 bg-wariGold rounded-full"></span> Données Démographiques
                </h3>
                <div class="grid grid-cols-2 sm:grid-cols-6 gap-4">
                    <div class="col-span-2">
                        <label class="block text-[10px] uppercase font-bold text-slate-500 mb-1">Total Participants</label>
                        <input type="number" name="nb_participants" value="0" class="w-full bg-white border border-slate-400 rounded-xl p-3 font-bold text-wariDark focus:ring-2 focus:ring-wariGold/20 outline-none">
                    </div>
                    <div class="col-span-1">
                        <label class="block text-[10px] uppercase font-bold text-blue-500 mb-1">Hommes</label>
                        <input type="number" name="nb_hommes" value="0" class="w-full bg-white border border-slate-400 rounded-xl p-3 focus:border-blue-300 outline-none">
                    </div>
                    <div class="col-span-1">
                        <label class="block text-[10px] uppercase font-bold text-pink-500 mb-1">Femmes</label>
                        <input type="number" name="nb_femmes" value="0" class="w-full bg-white border border-slate-400 rounded-xl p-3 focus:border-pink-300 outline-none">
                    </div>
                    <div class="col-span-2 flex gap-2">
                        <div class="flex-1">
                            <label class="block text-[10px] uppercase font-bold text-slate-500 mb-1">-25</label>
                            <input type="number" name="age_jeunes" value="0" class="w-full bg-white border border-slate-400 rounded-xl p-3 text-xs outline-none">
                        </div>
                        <div class="flex-1">
                            <label class="block text-[10px] uppercase font-bold text-slate-500 mb-1">25-45</label>
                            <input type="number" name="age_adultes" value="0" class="w-full bg-white border border-slate-400 rounded-xl p-3 text-xs outline-none">
                        </div>
                        <div class="flex-1">
                            <label class="block text-[10px] uppercase font-bold text-slate-500 mb-1">45+</label>
                            <input type="number" name="age_seniors" value="0" class="w-full bg-white border border-slate-400 rounded-xl p-3 text-xs outline-none">
                        </div>
                    </div>
                </div>
            </div>

            <div class="sm:col-span-2 bg-emerald-50 p-3 rounded-xl border border-emerald-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <label class="block text-[10px] uppercase font-black text-emerald-700 sm:w-1/2">Conversion WhatsApp (Importance stratégique)</label>
                <input type="number" name="nb_nouveaux_abonnes" value="0" class="w-full sm:w-32 bg-white border border-emerald-200 rounded-xl p-3 text-emerald-600 font-black text-center focus:ring-2 focus:ring-emerald-200 outline-none">
            </div>

            <div class="sm:col-span-2 mt-2">
                <label class="block text-[10px] uppercase font-black text-slate-500 mb-2 ml-1">Bilan & Impact (Récit)</label>
                <div class="rounded-xl border border-slate-400 overflow-hidden bg-white shadow-sm">
                    <div id="editor" class="min-h-[200px] text-slate-700"></div>
                </div>
                <input type="hidden" name="bilan_texte" id="bilan_texte">
            </div>

            <div class="sm:col-span-2 mt-4">
                <label class="block text-[10px] uppercase font-black text-slate-500 mb-2 ml-1">Citations & Témoignages</label>
                <textarea name="citations_cles" rows="3" placeholder="Ce que les participants ont dit..." class="w-full bg-slate-50 border border-slate-400 rounded-xl p-3 outline-none focus:border-wariGold text-slate-700 placeholder:text-slate-300"></textarea>
            </div>

            <div class="sm:col-span-2">
                <label class="block text-[10px] uppercase font-black text-red-500 mb-2 ml-1">Points de vigilance / Échecs</label>
                <textarea name="points_vigilance" rows="2" placeholder="Difficultés rencontrées..." class="w-full bg-red-50/30 border border-red-200 rounded-xl p-3 outline-none focus:border-red-400 text-slate-700 placeholder:text-red-200"></textarea>
            </div>

             <div class="sm:col-span-2 bg-slate-800 p-3 rounded-[1rem] shadow-inner text-white">
                <div class="flex items-center gap-2 mb-6 ml-2">
                    <div class="w-2 h-2 bg-wariGold rounded-full"></div>
                    <h2 class="text-[10px] uppercase font-black tracking-[0.2em] text-slate-400">Bilan Financier de l'Action</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div class="space-y-2">
                        <label class="block text-[9px] uppercase font-bold text-slate-400 ml-1">Total Dépenses</label>
                        <div class="relative">
                            <input type="number" name="budget_depense" step="0.01" 
                                value="0" 
                                class="w-full bg-slate-700/50 border border-slate-600 rounded-xl p-3 outline-none focus:border-red-400 text-white font-bold">
                            <span class="absolute right-4 top-4 text-[10px] text-slate-500 font-black">XOF</span>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[9px] uppercase font-bold text-slate-400 ml-1">Recettes / Gains</label>
                        <div class="relative">
                            <input type="number" name="recettes_generees" step="0.01" 
                                value="0" 
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

            <div class="sm:col-span-2 flex flex-col sm:grid sm:grid-cols-2 gap-6 bg-slate-50 p-3 rounded-xl border border-slate-100">
                <div>
                    <label class="block text-[10px] uppercase font-black text-slate-500 mb-2">
                        Photos (Max 5, 2Mo en max par photo)
                    </label>
                    <input 
                        type="file" 
                        id="photoInput"
                        name="photos[]" 
                        multiple 
                        required
                        accept="image/*" 
                        class="w-full text-xs text-slate-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:bg-wariDark file:text-white file:font-bold hover:file:bg-slate-700 cursor-pointer"
                    >
                    <p id="error-msg" class="text-[10px] text-red-500 mt-1 hidden font-bold">L'une des images dépasse 2 Mo.</p>
                </div>

                <script>
                document.getElementById('photoInput').addEventListener('change', function() {
                    const files = this.files;
                    const maxSize = 2 * 1024 * 1024; // 2 Mo en octets
                    const errorMsg = document.getElementById('error-msg');
                    
                    for (let i = 0; i < files.length; i++) {
                        if (files[i].size > maxSize) {
                            alert("Le fichier " + files[i].name + " est trop lourd (max 2 Mo).");
                            this.value = ""; // Réinitialise le champ pour forcer un nouveau choix
                            errorMsg.classList.remove('hidden');
                            return;
                        }
                    }
                    errorMsg.classList.add('hidden');
                    
                    // Limitation à 5 photos
                    if (files.length > 5) {
                        alert("Vous ne pouvez pas sélectionner plus de 5 photos.");
                        this.value = "";
                    }
                });
                </script>

                <div>
                    <label class="block text-[10px] uppercase font-black text-slate-500 mb-2">Lien Dossier Cloud</label>
                    <input type="text" name="dossier_media_path" placeholder="https://drive.google..." class="w-full bg-white border border-slate-400 rounded-xl p-3 outline-none focus:border-wariGold text-slate-700">
                </div>
            </div>

            <button type="submit" class="sm:col-span-2 bg-wariDark text-white font-black py-5 rounded-2xl uppercase text-[10px] tracking-[0.3em] hover:bg-wariGold hover:text-white transition-all shadow-lg active:scale-[0.98]">
                Sceller ce rapport d'impact
            </button>
        </form>
    </div>

    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script>
        var quill = new Quill('#editor', {
            theme: 'snow',
            placeholder: 'Décrivez le succès de cette action...',
            modules: { toolbar: [['bold', 'italic'], [{ 'list': 'bullet' }], ['clean']] }
        });

        function toggleOrgField() {
            const type = document.getElementById('type_activite').value;
            const orgField = document.getElementById('org_field');
            if (type === 'Organisation') {
                orgField.classList.remove('hidden');
                orgField.querySelector('input').setAttribute('required', 'required');
            } else {
                orgField.classList.add('hidden');
                orgField.querySelector('input').removeAttribute('required');
            }
        }

        var form = document.getElementById('impactForm');
        form.onsubmit = function() {
            var content = document.querySelector('input[name=bilan_texte]');
            content.value = quill.root.innerHTML;
        };
    </script>

    <script src="calcul.js"></script>
</body>
</html>
<?php 
require_once 'auth.php';
// Correction du chemin : on ne remonte que d'un cran
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = $_POST['titre'];
    
    // Génération du slug
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $titre)));
    
    // GESTION DE L'UPLOAD IMAGE
    $image_name = null;
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
        
        // Liste des extensions autorisées pour plus de sécurité
        $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($ext, $allowed_ext)) {
            $image_name = time() . '-' . $slug . '.' . $ext;
            $upload_path = __DIR__ . '/../uploads/' . $image_name;
            
            if (!move_uploaded_file($_FILES['image_file']['tmp_name'], $upload_path)) {
                $error = "Erreur lors du déplacement du fichier. Vérifie les permissions.";
            }
        } else {
            $error = "Format d'image non supporté (JPG, PNG, WEBP uniquement).";
        }
    }

    // Si aucune erreur d'image, on procède à l'insertion
    if (!isset($error)) {
        try {
            $query = $pdo->prepare("INSERT INTO wari_articles (slug, titre, date_publication, mois_compteur, image_url, resume, contenu) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $query->execute([
                $slug,
                $titre,
                $_POST['date_publication'],
                $_POST['mois_compteur'],
                $image_name,
                $_POST['resume'],
                $_POST['contenu_html'] // Récupéré depuis l'éditeur Quill
            ]);
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Un article avec ce titre existe déjà. La discipline demande de l'originalité !";
            } else {
                die("Erreur base de données : " . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau Récit | Wari Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

    <link rel="icon" type="image/png" href="https://wari.digiroys.com/assets/warifinance3d.png" />
    <link rel="apple-touch-icon" href="https://wari.digiroys.com/assets/warifinance3d.png">
    <meta name="theme-color" content="#020617">

    <style>
        body { font-family: 'Quicksand', sans-serif; }
        .ql-toolbar { background: #cbd5e1; border-radius: 0.5rem 0.5rem 0 0; }
        .ql-container { background: #020617; color: #e2e8f0; border-radius: 0 0 0.5rem 0.5rem; font-size: 1.1rem; }
        .ql-editor { min-height: 300px; }
    </style>
</head>
<body class="bg-slate-950 text-slate-200 p-4 md:p-10">

    <div class="max-w-3xl mx-auto">
        <header class="flex justify-between items-center mb-8">
            <h1 class="text-[#D4AF37] text-xl font-bold uppercase tracking-tighter">Nouvelle Chronique</h1>
            <a href="index.php" class="text-slate-500 hover:text-white text-xs uppercase underline">Annuler</a>
        </header>
        
        <form method="POST" enctype="multipart/form-data" id="wariForm" class="space-y-8 bg-slate-900 p-4 md:p-4 rounded-2xl border border-[#D4AF37]/10 shadow-2xl">
            
            <div>
                <label class="block text-xs font-bold text-[#D4AF37] uppercase mb-2 tracking-widest">Titre du récit</label>
                <input type="text" name="titre" required placeholder="Ex: La rigueur des 4 enveloppes" 
                    class="w-full bg-slate-950 border border-slate-800 p-4 rounded-xl outline-none focus:border-[#D4AF37] text-lg font-bold">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-[#D4AF37] uppercase mb-2 tracking-widest">Compteur</label>
                    <input type="text" name="mois_compteur" required placeholder="Mois 01" 
                        class="w-full bg-slate-950 border border-slate-800 p-4 rounded-xl outline-none focus:border-[#D4AF37]">
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#D4AF37] uppercase mb-2 tracking-widest">Date de l'événement</label>
                    <input type="date" name="date_publication" required 
                        class="w-full bg-slate-950 border border-slate-800 p-4 rounded-xl outline-none focus:border-[#D4AF37]">
                </div>
            </div>

            <div class="bg-slate-950 p-6 rounded-xl border-2 border-dashed border-slate-800 hover:border-[#D4AF37]/50 transition-colors">
                <label class="block text-xs font-bold text-[#D4AF37] uppercase mb-4 tracking-widest text-center">Image de preuve (Terrain)</label>
                <input type="file" name="image_file" accept="image/*" required class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-[#D4AF37] file:text-slate-950 hover:file:bg-[#bfa032] cursor-pointer">
            </div>

            <div>
                <label class="block text-xs font-bold text-[#D4AF37] uppercase mb-2 tracking-widest">Légende de l'image (Résumé court)</label>
                <textarea name="resume" rows="2" placeholder="Explique ce qu'on voit sur la photo..." 
                    class="w-full bg-slate-950 border border-slate-800 p-4 rounded-xl outline-none focus:border-[#D4AF37] italic"></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-[#D4AF37] uppercase mb-2 tracking-widest mb-4 italic">Rédige ton expérience ici</label>
                <div id="editor"></div>
                <input type="hidden" name="contenu_html" id="contenu_html">
            </div>

            <button type="submit" class="w-full bg-[#D4AF37] text-slate-950 font-bold py-2 rounded-xl uppercase tracking-[0.2em] hover:bg-[#bfa032] transition-all shadow-lg shadow-[#D4AF37]/10">
                Publier le récit
            </button>
        </form>
    </div>

    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script>
        var quill = new Quill('#editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['blockquote', 'link'],
                    ['clean']
                ]
            }
        });

        // Avant de soumettre, on copie le HTML de Quill dans le input caché
        var form = document.getElementById('wariForm');
        form.onsubmit = function() {
            var html = document.querySelector('.ql-editor').innerHTML;
            document.getElementById('contenu_html').value = html;
        };
    </script>
</body>
</html>
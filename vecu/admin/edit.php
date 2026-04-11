<?php 
require_once 'auth.php';
require_once __DIR__ . '/../../config/db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { header('Location: index.php'); exit; }

// Récupération de l'article actuel
$stmt = $pdo->prepare("SELECT * FROM wari_articles WHERE id = ?");
$stmt->execute([$id]);
$article = $stmt->fetch();
if (!$article) { header('Location: index.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = $_POST['titre'];
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $titre)));
    
    // Gestion de l'image (si une nouvelle est envoyée)
    $image_name = $article['image_url']; 
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === 0) {
        $ext = pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION);
        $image_name = time() . '-' . $slug . '.' . $ext;
        move_uploaded_file($_FILES['image_file']['tmp_name'], __DIR__ . '/../uploads/' . $image_name);
        
        // Optionnel : Supprimer l'ancienne image du serveur pour gagner de la place
        if ($article['image_url'] && file_exists(__DIR__ . '/../uploads/' . $article['image_url'])) {
            unlink(__DIR__ . '/../uploads/' . $article['image_url']);
        }
    }

    $query = $pdo->prepare("UPDATE wari_articles SET slug=?, titre=?, date_publication=?, mois_compteur=?, image_url=?, resume=?, contenu=? WHERE id=?");
    $query->execute([
        $slug, $titre, $_POST['date_publication'], $_POST['mois_compteur'], 
        $image_name, $_POST['resume'], $_POST['contenu_html'], $id
    ]);
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le récit | Wari Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;700&display=swap" rel="stylesheet">

    <link rel="icon" type="image/png" href="https://wari.digiroys.com/assets/warifinance3d.png" />
    <link rel="apple-touch-icon" href="https://wari.digiroys.com/assets/warifinance3d.png">
    <meta name="theme-color" content="#020617">

    <style>
        body { font-family: 'Quicksand', sans-serif; }
        .ql-toolbar { background: #cbd5e1; border-radius: 0.5rem 0.5rem 0 0; }
        .ql-container { background: #020617; color: #e2e8f0; border-radius: 0 0 0.5rem 0.5rem; }
        .ql-editor { min-height: 250px; }
    </style>
</head>
<body class="bg-slate-950 text-slate-200 p-4">
    <div class="max-w-3xl mx-auto">
        <header class="flex justify-between items-center mb-8">
            <h1 class="text-[#D4AF37] text-xl font-bold uppercase tracking-tighter text-center">Mise à jour du Vécu</h1>
            <a href="index.php" class="text-slate-500 hover:text-white text-xs uppercase underline">Annuler</a>
        </header>

        <form method="POST" enctype="multipart/form-data" id="editForm" class="space-y-6 bg-slate-900 p-4 rounded-2xl border border-[#D4AF37]/10">
            <div>
                <label class="block text-[10px] font-bold text-[#D4AF37] uppercase mb-2">Titre</label>
                <input type="text" name="titre" value="<?= htmlspecialchars($article['titre']) ?>" required class="w-full bg-slate-950 border border-slate-800 p-3 rounded-xl outline-none focus:border-[#D4AF37]">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-[#D4AF37] uppercase mb-2">Mois Compteur</label>
                    <input type="text" name="mois_compteur" value="<?= htmlspecialchars($article['mois_compteur']) ?>" required class="w-full bg-slate-950 border border-slate-800 p-3 rounded-xl outline-none focus:border-[#D4AF37]">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-[#D4AF37] uppercase mb-2">Date</label>
                    <input type="date" name="date_publication" value="<?= $article['date_publication'] ?>" required class="w-full bg-slate-950 border border-slate-800 p-3 rounded-xl outline-none focus:border-[#D4AF37]">
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-[#D4AF37] uppercase mb-2">Image (Laisser vide pour garder l'actuelle)</label>
                <input type="file" name="image_file" class="w-full bg-slate-950 border border-slate-800 p-3 rounded-xl text-xs text-slate-400">
            </div>

            <div>
                <label class="block text-[10px] font-bold text-[#D4AF37] uppercase mb-2">Résumé / Légende</label>
                <textarea name="resume" rows="2" class="w-full bg-slate-950 border border-slate-800 p-3 rounded-xl outline-none focus:border-[#D4AF37]"><?= htmlspecialchars($article['resume']) ?></textarea>
            </div>

            <div class="mb-10">
                <label class="block text-[10px] font-bold text-[#D4AF37] uppercase mb-2">Éditer le contenu</label>
                <div id="editor"><?= $article['contenu'] ?></div>
                <input type="hidden" name="contenu_html" id="contenu_html">
            </div>

            <button type="submit" class="w-full bg-[#D4AF37] text-slate-950 font-bold py-4 rounded-xl uppercase text-xs tracking-widest hover:bg-[#bfa032] transition-all">
                Mettre à jour le récit
            </button>
        </form>
    </div>

    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script>
        var quill = new Quill('#editor', { theme: 'snow' });
        var form = document.getElementById('editForm');
        form.onsubmit = function() {
            document.getElementById('contenu_html').value = document.querySelector('.ql-editor').innerHTML;
        };
    </script>
</body>
</html>
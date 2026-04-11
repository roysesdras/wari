<?php
require_once 'auth.php';
require_once __DIR__ . '/../../config/db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    // 1. On récupère le nom de l'image pour la supprimer du dossier uploads
    $stmt = $pdo->prepare("SELECT image_url FROM wari_articles WHERE id = ?");
    $stmt->execute([$id]);
    $article = $stmt->fetch();

    if ($article && $article['image_url']) {
        $path = __DIR__ . '/../uploads/' . $article['image_url'];
        if (file_exists($path)) {
            unlink($path); // Supprime le fichier physique
        }
    }

    // 2. On supprime l'entrée en base de données
    $delete = $pdo->prepare("DELETE FROM wari_articles WHERE id = ?");
    $delete->execute([$id]);
}

// 3. Retour au dashboard
header('Location: index.php');
exit;
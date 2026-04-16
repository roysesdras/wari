<?php
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['whatsapp'])) {
    // Le numéro arrive déjà avec le préfixe (ex: +22961000000)
    $phone = preg_replace('/[^0-9]/', '', $_POST['whatsapp']); 

    if (strlen($phone) >= 10) {
        try {
            $stmt = $pdo->prepare("INSERT INTO wari_subscribers (whatsapp_number) VALUES (?)");
            $stmt->execute([$phone]);
            
            // Redirection vers ton WhatsApp
            $mon_numero = "22996735000"; // Ton numéro admin
            $message = urlencode("Salut Esdras ! Je m'inscris à Wari-Vécu. Enregistre mon numéro.");
            
            header("Location: https://wa.me/$mon_numero?text=$message");
            exit;

        } catch (PDOException $e) {
            // Si déjà inscrit, on le renvoie quand même vers la discussion
            header("Location: https://wa.me/22996735000");
            exit;
        }
    }
}
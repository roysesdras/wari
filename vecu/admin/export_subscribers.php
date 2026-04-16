<?php
require_once '../../config/db.php';
require_once 'auth.php'; // Toujours protéger l'accès

// On définit le nom du fichier avec la date du jour
$filename = "wari_subscribers_" . date('d-m-Y') . ".csv";

// Headers pour forcer le téléchargement du fichier
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// Ouvrir le flux de sortie
$output = fopen('php://output', 'w');

// Titre des colonnes pour le CSV
fputcsv($output, ['ID', 'Numero_WhatsApp', 'Date_Inscription', 'Status']);

// Récupérer les abonnés actifs
$query = $pdo->query("SELECT id, whatsapp_number, date_inscription, status FROM wari_subscribers WHERE status = 'actif'");

while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, $row);
}

fclose($output);
exit;
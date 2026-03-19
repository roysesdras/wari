<?php
session_start();
require 'db.php';
require 'no_cache.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die(json_encode(['error' => 'Non autorisé']));
}

$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

if ($data) {
    try {
        // ✅ On ajoute le mois ET on réencode pour que ce soit bien sauvegardé
        $data['lastSavedMonth'] = date('Y-m');
        $jsonToSave = json_encode($data); // ✅ La vraie donnée à sauvegarder

        $stmt = $pdo->prepare("
            UPDATE wari_users 
            SET budget_data = ?, 
                project_capital = ?, 
                last_budget_at = NOW() 
            WHERE id = ?
        ");

        $stmt->execute([
            $jsonToSave,                   // ✅ Avec lastSavedMonth inclus
            $data['projectCapital'] ?? 0,
            $_SESSION['user_id']
        ]);

        echo json_encode(['success' => true, 'message' => 'Sauvegarde réussie']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur : ' . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Données invalides']);
}
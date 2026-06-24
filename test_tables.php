<?php
require 'config/db.php';
try {
    $stmt = $pdo->query("SELECT * FROM wari_payments");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo 'Erreur : ' . $e->getMessage();
}
?>

<?php
require __DIR__ . '/config/db.php';
try {
    $stmt = $pdo->query("SELECT email, name, license_expiration_date FROM wari_users WHERE license_expiration_date IS NOT NULL AND license_expiration_date >= NOW() AND email != '' AND email IS NOT NULL");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "COUNT: " . count($users) . "\n";
    foreach ($users as $u) {
        echo "- " . $u['email'] . " (" . $u['name'] . ") [Expire le " . $u['license_expiration_date'] . "]\n";
    }
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage();
}
?>

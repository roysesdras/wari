<?php
require '/var/www/wari.digiroys.com/config/db.php';
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM wari_audit");
    echo "OK: " . $stmt->fetchColumn();
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}

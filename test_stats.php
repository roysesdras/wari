<?php
require 'config/db.php';
try {
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM wari_users")->fetchColumn();
    $pushUsers = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM wari_subscriptions WHERE user_id IS NOT NULL")->fetchColumn();
    $pushTotal = $pdo->query("SELECT COUNT(*) FROM wari_subscriptions")->fetchColumn();
    echo 'TOTAL_USERS:' . $totalUsers . "\n";
    echo 'PUSH_UNIQUE_USERS:' . $pushUsers . "\n";
    echo 'PUSH_TOTAL_DEVICES:' . $pushTotal . "\n";
} catch (Exception $e) {
    echo 'Erreur : ' . $e->getMessage();
}
?>

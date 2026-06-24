<?php
require_once __DIR__ . '/config/db.php';
$stmt = $pdo->query("SELECT titre FROM academy_courses");
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach($courses as $c) {
    echo "- " . $c['titre'] . "\n";
}

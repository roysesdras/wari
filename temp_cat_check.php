<?php
require 'config/db.php';
$stmt = $pdo->query('SELECT titre, icone FROM academy_categories');
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($res);

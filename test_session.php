<?php
session_start();
echo '<h1>Test Session Wari</h1>';
echo '<pre>';
echo 'Session ID: ' . session_id() . PHP_EOL;
echo 'user_id: ' . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NON DÉFINI') . PHP_EOL;
echo 'user_email: ' . (isset($_SESSION['user_email']) ? $_SESSION['user_email'] : 'NON DÉFINI') . PHP_EOL;
echo 'COOKIE wari_remember: ' . (isset($_COOKIE['wari_remember']) ? 'OUI' : 'NON') . PHP_EOL;
echo 'Toutes les sessions: ' . PHP_EOL;
print_r($_SESSION);
echo '</pre>';
echo '<br><a href="index.php">Aller sur Index</a>';
echo '<br><a href="config/auth.php">Aller sur Auth</a>';
?>
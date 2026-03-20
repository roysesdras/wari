<?php
session_start();

echo '<h1>🔍 DEBUG SESSION COMPLET</h1>';
echo '<pre>';

// Configuration PHP
echo '=== CONFIGURATION PHP ===' . PHP_EOL;
echo 'Session ID: ' . session_id() . PHP_EOL;
echo 'Cookie Lifetime: ' . ini_get('session.cookie_lifetime') . ' secondes' . PHP_EOL;
echo 'Cookie Path: ' . ini_get('session.cookie_path') . PHP_EOL;
echo 'Cookie Domain: ' . ini_get('session.cookie_domain') . PHP_EOL;
echo 'Cookie Secure: ' . ini_get('session.cookie_secure') . PHP_EOL;
echo 'Cookie HttpOnly: ' . ini_get('session.cookie_httponly') . PHP_EOL;
echo 'Session Save Path: ' . ini_get('session.save_path') . PHP_EOL;
echo 'Session GC Maxlifetime: ' . ini_get('session.gc_maxlifetime') . ' secondes' . PHP_EOL;
echo 'Session GC Probability: ' . ini_get('session.gc_probability') . '/' . ini_get('session.gc_divisor') . PHP_EOL;

echo PHP_EOL . '=== SESSION ACTUELLE ===' . PHP_EOL;
echo 'user_id: ' . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NON DEFINI') . PHP_EOL;
echo 'user_email: ' . (isset($_SESSION['user_email']) ? $_SESSION['user_email'] : 'NON DEFINI') . PHP_EOL;

echo PHP_EOL . '=== COOKIES ===' . PHP_EOL;
echo 'wari_remember: ' . (isset($_COOKIE['wari_remember']) ? substr($_COOKIE['wari_remember'], 0, 10) . '...' : 'NON DEFINI') . PHP_EOL;

echo PHP_EOL . '=== SERVEUR ===' . PHP_EOL;
echo 'Heure serveur: ' . date('Y-m-d H:i:s') . PHP_EOL;
echo 'Timezone: ' . date_default_timezone_get() . PHP_EOL;

echo PHP_EOL . '=== TEST COOKIE ===' . PHP_EOL;
// Tester si le cookie est bien défini
setcookie('test_wari', 'valeur_test', [
    'expires' => time() + 7776000, // 90 jours
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);
echo 'Cookie test défini - rechargez dans 5 secondes' . PHP_EOL;

echo '</pre>';
echo '<p><a href="' . $_SERVER['PHP_SELF'] . '">Recharger</a> | <a href="index.php">Aller sur Index</a></p>';
?>
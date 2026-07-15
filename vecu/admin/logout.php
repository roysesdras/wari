<?php
session_start();

if (isset($_COOKIE['vecu_admin_remember'])) {
    setcookie('vecu_admin_remember', '', time() - 3600, "/");
}

session_destroy();
header('Location: login.php');
exit;
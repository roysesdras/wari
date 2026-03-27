<?php
// /var/www/html/academy-admin/logout.php

if (session_status() === PHP_SESSION_NONE) session_start();
unset($_SESSION['academy_user']);
unset($_SESSION['academy_login_at']);
session_destroy();
header('Location: /academy-admin/login.php');
exit;

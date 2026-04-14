<?php
session_start();
$_SESSION['rapport_auth'] = false;
session_destroy();
header("Location: login.php");
exit;
<?php
session_start();

if (!isset($_SESSION['rapport_auth']) || $_SESSION['rapport_auth'] !== true) {
    header('Location: login.php');
    exit;
}
?>
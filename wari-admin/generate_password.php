<?php
// Exécute ce script en CLI pour générer ton hash admin
// php generate_password.php

$password = readline("Nouveau mot de passe admin : ");
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

echo "\n=== HASH À COPIER DANS .env ===\n";
echo "ADMIN_PASSWORD_HASH=" . $hash . "\n";
echo "================================\n\n";

// Génère aussi une clé CSRF
$csrf = bin2hex(random_bytes(32));
echo "CSRF_SECRET=" . $csrf . "\n";

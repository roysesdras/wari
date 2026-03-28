<?php
// /var/www/wari.digiroys.com/tmp/test_key.php
require_once __DIR__ . '/../classes/AI.php';

$ai = new AI();

// Utilisation de la réflexion pour accéder à la propriété privée pour le test
$reflector = new ReflectionClass('AI');
$property = $reflector->getProperty('apiKey');
$property->setAccessible(true);
$apiKey = $property->getValue($ai);

echo "API Key loaded: " . ($apiKey ? substr($apiKey, 0, 8) . "..." : "EMPTY") . "\n";
echo "Model: " . $reflector->getProperty('model')->getValue($ai) . "\n";

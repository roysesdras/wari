<?php
$result = "";
$status_class = "";

if (isset($_POST['api_key'])) {
    $apiKey = trim($_POST['api_key']);
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $apiKey;

    $data = [
        "contents" => [
            ["parts" => [["text" => "Réponds par le mot 'FONCTIONNEL' uniquement."]]]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $resData = json_decode($response, true);

    if ($httpCode === 200) {
        $aiResponse = $resData['candidates'][0]['content']['parts'][0]['text'] ?? "Réponse vide";
        $result = "✅ SUCCÈS (200) : " . $aiResponse;
        $status_class = "success";
    } else {
        $errMsg = $resData['error']['message'] ?? "Erreur inconnue";
        $errStatus = $resData['error']['status'] ?? "Erreur";
        $result = "❌ ERREUR $httpCode ($errStatus) : $errMsg";
        $status_class = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Testeur API Gemini - RebOnly</title>
    <style>
        body { font-family: sans-serif; background: #f4f4f9; display: flex; justify-content: center; padding-top: 50px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 450px; }
        input { width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; border: 1px solid #ddd; }
        button { width: 100%; padding: 10px; background: #4285f4; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .res { margin-top: 20px; padding: 15px; border-radius: 4px; font-weight: bold; font-family: monospace; word-wrap: break-word; }
        .success { background: #e6ffed; color: #22863a; border: 1px solid #34d058; }
        .error { background: #ffeef0; color: #cb2431; border: 1px solid #f97583; }
    </style>
</head>
<body>
    <div class="card">
        <h3>Tester une Clé Gemini 2.0</h3>
        <form method="POST">
            <input type="text" name="api_key" placeholder="Colle ta clé API ici (AIza...)" required>
            <button type="submit">Vérifier le Quota</button>
        </form>

        <?php if ($result): ?>
            <div class="res <?php echo $status_class; ?>">
                <?php echo nl2br(htmlspecialchars($result)); ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
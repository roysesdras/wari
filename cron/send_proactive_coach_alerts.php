<?php
// /var/www/wari.digiroys.com/cron/send_proactive_coach_alerts.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🚀 [" . date('Y-m-d H:i:s') . "] Démarrage de l'analyse des alertes Coach IA...\n";

// 1. Chargement du .env pour $_ENV
$possiblePaths = [
    '/var/www/wari.digiroys.com/wari-admin/.env',
    '/var/www/html/wari-admin/.env',
    __DIR__ . '/../wari-admin/.env'
];

foreach ($possiblePaths as $envFile) {
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '#') === 0 || !strpos($line, '=')) continue;
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
        break;
    }
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../classes/AI.php';
require_once __DIR__ . '/../classes/Push.php';
require_once __DIR__ . '/../config/db.php';

// 2. Définition des variables de temps
$currentMonth = date('Y-m');
$currentDay = (int)date('d');
$daysInMonth = (int)date('t');

// Traduction des mois pour le prompt
$moisFr = [
    '01' => 'Janvier', '02' => 'Février', '03' => 'Mars', '04' => 'Avril',
    '05' => 'Mai', '06' => 'Juin', '07' => 'Juillet', '08' => 'Août',
    '09' => 'Septembre', '10' => 'Octobre', '11' => 'Novembre', '12' => 'Décembre'
];
$monthLabel = ($moisFr[date('m')] ?? '??') . ' ' . date('Y');

// 3. Récupérer tous les utilisateurs Premium abonnés aux notifications Push
$premiumUsers = $pdo->query("
    SELECT DISTINCT u.id, u.email, u.budget_data, u.budget_data_pro
    FROM wari_users u
    JOIN wari_subscriptions s ON s.user_id = u.id
    WHERE u.is_premium = 1
")->fetchAll(PDO::FETCH_ASSOC);

if (empty($premiumUsers)) {
    echo "ℹ️ Aucun utilisateur Premium avec abonnement push trouvé.\n";
    exit();
}

echo "👥 " . count($premiumUsers) . " utilisateurs Premium à analyser.\n";

$ai = new AI();

foreach ($premiumUsers as $user) {
    $userId = $user['id'];
    $email = $user['email'];
    
    echo "🔍 Analyse de {$email} (ID: {$userId}) : ";

    // 4. Rate Limiting : Vérifier si une alerte Coach a déjà été envoyée dans les dernières 24h
    $stmtLimit = $pdo->prepare("
        SELECT COUNT(*) FROM wari_push_logs 
        WHERE type = 'coach_proactive_alert' AND target_id = ? 
        AND sent_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $stmtLimit->execute([$userId]);
    $alreadyAlerted = (int)$stmtLimit->fetchColumn();

    if ($alreadyAlerted > 0) {
        echo "⏭️ Déjà alerté récemment (anti-spam 24h).\n";
        continue;
    }

    $alertTriggered = false;
    $triggerType = '';
    $triggerDetails = '';
    $selectedWallet = 'perso';

    // 5. Analyser les deux portefeuilles (Perso puis Pro)
    foreach (['perso', 'pro'] as $wallet) {
        $budgetRaw = ($wallet === 'pro') ? ($user['budget_data_pro'] ?? null) : ($user['budget_data'] ?? null);
        
        $categories = [];
        $currency = 'F';
        
        if ($budgetRaw && $budgetRaw !== 'null') {
            $budgetData = json_decode($budgetRaw, true);
            $categories = $budgetData['categories'] ?? [];
            $currency = $budgetData['currency'] ?? 'F';
        } else {
            // Initialisation par défaut si budget vide
            if ($wallet === 'pro') {
                $categories = [
                    ["id" => 101, "name" => "Stock & Matériel", "percent" => 40],
                    ["id" => 102, "name" => "Bénéfice Réinvesti", "percent" => 30],
                    ["id" => 103, "name" => "Frais de Fonctionnement", "percent" => 20],
                    ["id" => 104, "name" => "Marketing & Publicité", "percent" => 10]
                ];
            } else {
                $categories = [
                    ["id" => 3, "name" => "Projet", "percent" => 25],
                    ["id" => 1, "name" => "Épargne", "percent" => 15],
                    ["id" => 4, "name" => "Imprévu", "percent" => 10],
                    ["id" => 2, "name" => "Train de vie", "percent" => 50]
                ];
            }
        }

        // 5a. Récupérer les distributions (revenus) du mois pour ce portefeuille
        $stmtIncome = $pdo->prepare("
            SELECT SUM(amount) FROM wari_distributions 
            WHERE user_id = ? AND wallet_type = ? AND DATE_FORMAT(distributed_at, '%Y-%m') = ?
        ");
        $stmtIncome->execute([$userId, $wallet, $currentMonth]);
        $totalIncome = (int)($stmtIncome->fetchColumn() ?: 0);

        if ($totalIncome <= 0) {
            // Aucun revenu distribué ce mois-ci pour ce portefeuille, on passe au suivant
            continue;
        }

        // 5b. Récupérer les dépenses du mois pour ce portefeuille
        $stmtSpent = $pdo->prepare("
            SELECT SUM(amount) FROM wari_expenses 
            WHERE user_id = ? AND wallet_type = ? AND DATE_FORMAT(date_expense, '%Y-%m') = ?
        ");
        $stmtSpent->execute([$userId, $wallet, $currentMonth]);
        $totalSpent = (int)($stmtSpent->fetchColumn() ?: 0);

        // 5c. Récupérer les dépenses groupées par catégorie
        $stmtCatSpent = $pdo->prepare("
            SELECT category_id, SUM(amount) as spent 
            FROM wari_expenses 
            WHERE user_id = ? AND wallet_type = ? AND DATE_FORMAT(date_expense, '%Y-%m') = ?
            GROUP BY category_id
        ");
        $stmtCatSpent->execute([$userId, $wallet, $currentMonth]);
        $catExpenses = $stmtCatSpent->fetchAll(PDO::FETCH_KEY_PAIR);

        // 5d. Analyse des règles d'alertes
        
        // --- RÈGLE C: Risque d'overdraft global (Dépenses totales > 90% des revenus distribués, à plus de 3j du terme)
        if ($totalSpent > $totalIncome * 0.90 && $currentDay < $daysInMonth - 3) {
            $alertTriggered = true;
            $triggerType = 'overdraft';
            $triggerDetails = "Revenus totaux distribués : " . number_format($totalIncome, 0, '', ' ') . " $currency | Dépenses totales : " . number_format($totalSpent, 0, '', ' ') . " $currency. Dépassé 90% du budget total.";
            $selectedWallet = $wallet;
            break;
        }

        // Parcourir chaque catégorie pour vérifier les alertes
        foreach ($categories as $cat) {
            $catId = $cat['id'];
            $catName = $cat['name'];
            $percent = $cat['percent'];
            
            $targetBudget = $totalIncome * ($percent / 100);
            $spent = $catExpenses[$catId] ?? 0;
            $expectedSpent = $targetBudget * ($currentDay / $daysInMonth);

            // --- RÈGLE A: Catégorie presque ou complètement épuisée (consommé > 95% de sa cible)
            if ($spent >= $targetBudget * 0.95 && $targetBudget > 0) {
                $alertTriggered = true;
                $triggerType = 'exhausted';
                $triggerDetails = "Catégorie '$catName' | Cible : " . number_format($targetBudget, 0, '', ' ') . " $currency | Dépensé réel : " . number_format($spent, 0, '', ' ') . " $currency. Budget épuisé.";
                $selectedWallet = $wallet;
                break 2; // Sortir des deux boucles
            }

            // --- RÈGLE B: Rythme trop rapide (burn rate > 135% du prorata attendu, minimum 5000 dépensés)
            if ($spent > $expectedSpent * 1.35 && $spent > 5000 && $spent < $targetBudget * 0.95 && $targetBudget > 0) {
                $alertTriggered = true;
                $triggerType = 'burn_rate';
                $triggerDetails = "Catégorie '$catName' | Cible mensuelle : " . number_format($targetBudget, 0, '', ' ') . " $currency | Attendue à ce jour : " . number_format($expectedSpent, 0, '', ' ') . " $currency | Réelle : " . number_format($spent, 0, '', ' ') . " $currency. Consommation trop rapide.";
                $selectedWallet = $wallet;
                break 2; // Sortir des deux boucles
            }
        }

        // --- RÈGLE D: Félicitations pour bonne discipline (après le 15, dépenses totales < 70% du prorata attendu)
        $expectedTotalSpent = $totalIncome * ($currentDay / $daysInMonth);
        if ($currentDay >= 15 && $totalSpent < $expectedTotalSpent * 0.70 && $totalSpent > 1000) {
            $alertTriggered = true;
            $triggerType = 'discipline';
            $triggerDetails = "Dépenses totales réelles : " . number_format($totalSpent, 0, '', ' ') . " $currency | Budget prorata attendu : " . number_format($expectedTotalSpent, 0, '', ' ') . " $currency. Discipline exceptionnelle.";
            $selectedWallet = $wallet;
            break;
        }
    }

    // 6. Si déclenchement, générer l'alerte IA et envoyer
    if ($alertTriggered) {
        $walletLabel = ($selectedWallet === 'pro') ? 'Professionnel (Commercial)' : 'Personnel';
        
        $prompt = "Tu es le Coach Wari, le Grand Frère de la discipline financière en Afrique. Tu as détecté un comportement sur le portefeuille $walletLabel de l'utilisateur :
- Règle déclenchée : $triggerType
- Détails techniques : $triggerDetails

Rédige un court message de notification Web Push (maximum 15 mots) pour alerter ou encourager l'utilisateur. Ton ton est direct, expert, et bienveillant. N'utilise pas de salutation, pas de signature, et bannis le surnom 'Champion'. Va droit au but.

Exemples attendus :
- Alerte Train de vie : vous dépensez trop vite. Réduisez la cadence pour finir le mois serein.
- Bravo pour votre discipline ! Votre budget Projet est bien tenu ce mois-ci, gardez le cap.

Renvoie uniquement le texte brut du message sans JSON, sans guillemets, et sans fioritures.";

        $fallbackMessage = "Coach Wari : Votre budget $walletLabel nécessite votre attention. Consultez votre dashboard.";
        if ($triggerType === 'discipline') {
            $fallbackMessage = "Félicitations ! Votre discipline budgétaire sur votre portefeuille $walletLabel porte ses fruits.";
        }

        try {
            $aiResponse = $ai->generate($prompt, "Tu es le Coach Wari. Tu parles directement, sans fioritures et tu formules des messages de notification de 15 mots maximum.");
            $aiResponse = trim($aiResponse, " \t\n\r\0\x0B\"'");
            
            // Validation de base de la réponse
            $pushBody = (!empty($aiResponse) && strlen($aiResponse) > 10 && strlen($aiResponse) < 200) ? $aiResponse : $fallbackMessage;
        } catch (Exception $e) {
            $pushBody = $fallbackMessage;
        }

        // Envoi du push
        $title = ($triggerType === 'discipline') ? "🏆 Coach Wari - Bravo !" : "⚠️ Coach Wari - Alerte";
        $url = "https://wari.digiroys.com/?utm_source=push&utm_campaign=coach_proactive_alert";
        
        $sendResult = Push::sendToUser($pdo, $userId, $title, $pushBody, $url, 'coach_proactive_alert');
        
        if ($sendResult['success']) {
            echo "🔔 Alerte envoyée ! Type : {$triggerType} | Message : \"{$pushBody}\" | Destinataires : {$sendResult['recipients']}\n";
        } else {
            echo "❌ Échec de l'envoi du Push : " . $sendResult['message'] . "\n";
        }
    } else {
        echo "✅ RAS (Aucun comportement à risque ou remarquable détecté).\n";
    }
}

echo "🏁 Fin du traitement.\n";

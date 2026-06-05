<?php
require_once __DIR__ . '/../wari_monitoring.php';  // ← TOUJOURS EN PREMIER
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session_check.php';

$userId = $_SESSION['user_id'];

// 1. Récupérer le budget de l'utilisateur
$stmt = $pdo->prepare("SELECT budget_data FROM wari_users WHERE id = ?");
$stmt->execute([$userId]);
$userData = $stmt->fetch();
$budgetRaw = (!empty($userData['budget_data'])) ? $userData['budget_data'] : 'null';

$budgetData = json_decode($budgetRaw, true);
$categories = isset($budgetData['categories']) ? $budgetData['categories'] : [];
$projectCapital = isset($budgetData['projectCapital']) ? (float)$budgetData['projectCapital'] : 0.0;
$currency = isset($budgetData['currency']) ? $budgetData['currency'] : 'F';

// 2. Dépenses du mois actuel
$stmtExp = $pdo->prepare("
    SELECT category_id, SUM(amount) as total 
    FROM wari_expenses 
    WHERE user_id = ? 
    AND MONTH(date_expense) = MONTH(CURRENT_DATE()) 
    AND YEAR(date_expense) = YEAR(CURRENT_DATE())
    GROUP BY category_id
");
$stmtExp->execute([$userId]);
$expenses = $stmtExp->fetchAll(PDO::FETCH_KEY_PAIR);

// 3. Dettes actives
$stmtDebts = $pdo->prepare("
    SELECT id, person_name, amount, type 
    FROM wari_debts 
    WHERE user_id = ? AND status = 'pending' 
    ORDER BY created_at DESC
");
$stmtDebts->execute([$userId]);
$debts = $stmtDebts->fetchAll(PDO::FETCH_ASSOC);

// 4. Calcul de l'enveloppe Cash/Disponible
$cash = 0.0;
foreach ($categories as $cat) {
    $catId = (int)$cat['id'];
    $catName = (string)$cat['name'];
    $spent = isset($expenses[$catId]) ? (float)$expenses[$catId] : 0.0;
    
    $isProjet = (strpos(strtolower($catName), 'projet') !== false);
    $isEpargne = (strpos(strtolower($catName), 'épargne') !== false) || ($catId === 1);
    
    if (!$isEpargne && !$isProjet) {
        $balance = isset($cat['balance']) ? (float)$cat['balance'] : 0.0;
        $soldeReel = max(0.0, $balance - $spent);
        $cash += $soldeReel;
    }
}

// 5. Calcul des variables pour le contexte AI
$daysInMonth = (int)date('t');
$day = (int)date('j');
$daysLeft = $daysInMonth - $day + 1;

$totalDettes = 0;
foreach ($debts as $d) {
    $totalDettes += (int)$d['amount'];
}

$catSummaryLines = [];
foreach ($categories as $c) {
    $catId = (int)$c['id'];
    $catName = (string)$c['name'];
    $spent = isset($expenses[$catId]) ? (int)$expenses[$catId] : 0;
    $isProjet = (strpos(strtolower($catName), 'projet') !== false);
    $limit = $isProjet ? $projectCapital : (isset($c['balance']) ? $c['balance'] : 0);
    $catSummaryLines[] = "- {$catName}: {$spent} {$currency} dépensés sur {$limit} {$currency} prévus";
}
$catSummary = implode("\n", $catSummaryLines);

$debtsSummaryLines = [];
foreach ($debts as $d) {
    $typeLabel = ($d['type'] === 'loan') ? 'Prêt à' : 'Dette envers';
    $amountFormatted = number_format((int)$d['amount'], 0, '.', ' ');
    $debtsSummaryLines[] = "- {$typeLabel} {$d['person_name']} : {$amountFormatted} {$currency}";
}
$debtsSummary = empty($debtsSummaryLines) ? "Aucune dette active." : implode("\n", $debtsSummaryLines);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Wari Coach - Ton mentor financier</title>
    <meta name="description" content="Prends le contrôle de ton budget avec le Coach Wari, disponible 24h/24 pour te conseiller.">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <meta id="metaThemeColor" name="theme-color" content="#000000">

    <script>
        // Initialisation immédiate du thème
        const savedTheme = localStorage.getItem('wari_theme') || 'dark';
        if (savedTheme === 'light') {
            document.documentElement.classList.add('light-mode');
            document.addEventListener('DOMContentLoaded', () => {
                document.body.classList.add('light-mode');
                const metaThemeColor = document.getElementById('metaThemeColor');
                if (metaThemeColor) metaThemeColor.setAttribute('content', '#f1f5f9');
            });
        }
    </script>

    <style>
        /* Variables de couleurs personnalisées Wari */
        :root {
            --color-primary: #C9A84C; /* Or Wari */
            --color-primary-rgb: 201, 168, 76;
            --color-bg-app: #000000; /* Noir pur AMOLED */
            --color-bg-card: #0c0d0e; /* Gris/noir ultra sombre */
            --color-bg-card-rgb: 12, 13, 14;
            --color-border: rgba(255, 255, 255, 0.05); /* Bordure ultra fine et sombre */
            --color-text-main: #f8fafc;
            --color-text-muted: #64748b; /* Texte secondaire plus sombre et discret */
            --color-success: #10b981;

            --color-bg-chat: #020203;
            --color-bg-header: #000000;
            --color-bg-btn: #0a0a0a;
            --color-bg-btn-hover: rgba(255, 255, 255, 0.1);
            --color-bg-bubble-coach: #0c0d0e;
            --color-bg-chip: #0a0a0a;
            --color-bg-input: #0a0a0a;
            --color-bg-input-focus: #0d0d0d;
            --color-scrollbar-thumb: rgba(255, 255, 255, 0.1);
        }

        /* Mode Clair */
        body.light-mode {
            --color-bg-app: #f1f5f9;
            --color-bg-card: #ffffff;
            --color-border: rgba(15, 23, 42, 0.08);
            --color-text-main: #0f172a;
            --color-text-muted: #475569;

            --color-bg-chat: #ffffff;
            --color-bg-header: #ffffff;
            --color-bg-btn: #f1f5f9;
            --color-bg-btn-hover: rgba(15, 23, 42, 0.05);
            --color-bg-bubble-coach: #f1f5f9;
            --color-bg-chip: #f1f5f9;
            --color-bg-input: #f8fafc;
            --color-bg-input-focus: #ffffff;
            --color-scrollbar-thumb: rgba(15, 23, 42, 0.1);
        }

        * {
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            position: fixed;
            background-color: var(--color-bg-app);
            font-family: 'Quicksand', sans-serif;
            color: var(--color-text-main);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .chat-app {
            width: 100%;
            max-width: 600px;
            position: fixed;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            height: 100%;
            display: flex;
            flex-direction: column;
            background: var(--color-bg-chat); /* Arrière-plan chat dynamique */
            border-left: 1px solid var(--color-border);
            border-right: 1px solid var(--color-border);
            box-shadow: none; /* Retrait de l'ombre portée */
            overflow: hidden;
        }

        @media (max-width: 600px) {
            .chat-app {
                border-left: none;
                border-right: none;
            }
        }

        /* En-tête */
        .chat-header {
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--color-border);
            background: var(--color-bg-header); /* Fond dynamique */
            z-index: 10;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .back-btn {
            background: var(--color-bg-btn); /* Fond dynamique */
            border: 1px solid var(--color-border);
            color: var(--color-text-muted);
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
        }

        .back-btn:hover {
            background: var(--color-bg-btn-hover);
            color: var(--color-text-main);
            transform: translateX(-2px);
        }

        .back-btn:active {
            transform: scale(0.95);
        }

        .avatar-container {
            position: relative;
        }

        .avatar {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--color-primary), #d97706);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: none; /* Retrait de l'ombre */
        }

        .avatar svg {
            width: 1.25rem;
            height: 1.25rem;
            color: #020617;
        }

        .status-indicator {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 0.625rem;
            height: 0.625rem;
            background-color: var(--color-success);
            border: 2px solid var(--color-bg-header);
            border-radius: 50%;
            animation: pulse-status 2s infinite;
        }

        @keyframes pulse-status {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { box-shadow: 0 0 0 4px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .header-info h1 {
            font-size: 1.08rem;
            font-weight: 800;
            margin: 0;
            color: var(--color-text-main);
            letter-spacing: -0.01em;
        }

        .header-info p {
            font-size: 0.78rem;
            font-weight: 700;
            margin: 0.1rem 0 0 0;
            color: var(--color-primary);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        /* Messages */
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1.25rem 1rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            scrollbar-width: thin;
            scrollbar-color: var(--color-scrollbar-thumb) transparent;
        }

        .chat-messages::-webkit-scrollbar {
            width: 4px;
        }
        .chat-messages::-webkit-scrollbar-thumb {
            background: var(--color-scrollbar-thumb);
            border-radius: 2px;
        }

        /* Bulles de message */
        .message {
            max-width: 82%;
            padding: 0.8rem 1rem;
            font-size: 0.92rem;
            line-height: 1.5;
            animation: message-in 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes message-in {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message-user {
            background: var(--color-primary);
            color: #000000; /* Noir pur contrasté sur le doré */
            align-self: flex-end;
            border-radius: 1.25rem 1.25rem 0 1.25rem;
            font-weight: 600;
            box-shadow: none; /* Retrait de l'ombre */
        }

        .message-coach {
            background: var(--color-bg-bubble-coach); /* Fond dynamique */
            border: 1px solid var(--color-border);
            color: var(--color-text-main);
            align-self: flex-start;
            border-radius: 1.25rem 1.25rem 1.25rem 0;
            box-shadow: none; /* Retrait de l'ombre */
        }

        .message-coach strong {
            color: var(--color-primary);
            font-weight: 600;
        }

        /* Indicateur d'attente (Thinking) */
        .message-thinking {
            align-self: flex-start;
            background: var(--color-bg-bubble-coach); /* Fond dynamique */
            border: 1px solid var(--color-border);
            padding: 0.8rem 1.2rem;
            border-radius: 1.25rem 1.25rem 1.25rem 0;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            box-shadow: none;
        }

        .dot {
            width: 0.4rem;
            height: 0.4rem;
            background-color: var(--color-text-muted);
            border-radius: 50%;
            animation: bounce-dot 1.4s infinite ease-in-out both;
        }

        .dot:nth-child(1) { animation-delay: -0.32s; }
        .dot:nth-child(2) { animation-delay: -0.16s; }

        @keyframes bounce-dot {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1.0); }
        }

        /* Transitions pour la compression clavier */
        .chat-header, .avatar, .back-btn, .header-info h1, .header-info p, .suggestions-row {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Suggestions */
        .suggestions-row {
            display: flex;
            gap: 0.5rem;
            padding: 0.6rem 1rem;
            overflow-x: auto;
            white-space: nowrap;
            background: var(--color-bg-chat); /* Fond dynamique */
            border-top: 1px solid var(--color-border);
            scrollbar-width: none; /* Firefox */
            max-height: 50px;
        }

        /* Styles de compression quand le clavier virtuel est ouvert */
        .keyboard-active .suggestions-row {
            max-height: 0 !important;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            margin: 0 !important;
            border-top: none !important;
            opacity: 0;
            pointer-events: none;
        }

        .keyboard-active .chat-header {
            padding: 0.5rem 1rem;
        }

        .keyboard-active .avatar {
            width: 2rem;
            height: 2rem;
        }

        .keyboard-active .back-btn {
            width: 2rem;
            height: 2rem;
        }

        .keyboard-active .header-info h1 {
            font-size: 0.98rem;
        }

        .keyboard-active .header-info p {
            font-size: 0.72rem;
        }

        .suggestions-row::-webkit-scrollbar {
            display: none; /* Safari et Chrome */
        }

        .suggestion-chip {
            background: var(--color-bg-chip); /* Fond dynamique */
            border: 1px solid var(--color-border);
            color: var(--color-text-muted);
            padding: 0.4rem 0.8rem;
            border-radius: 100px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .suggestion-chip:hover {
            border-color: rgba(var(--color-primary-rgb), 0.4);
            color: var(--color-text-main);
            background: rgba(var(--color-primary-rgb), 0.05);
        }

        .suggestion-chip:active {
            transform: scale(0.95);
        }

        /* Zone de saisie */
        .input-bar {
            padding: 0.75rem 1rem;
            background: var(--color-bg-header); /* Fond dynamique */
            border-top: 1px solid var(--color-border);
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .chat-input {
            flex: 1;
            background: var(--color-bg-input); /* Fond dynamique */
            border: 1px solid var(--color-border);
            border-radius: 1rem;
            padding: 0.75rem 1rem;
            color: var(--color-text-main);
            font-size: 0.92rem;
            font-family: inherit;
            outline: none;
            transition: all 0.2s ease;
        }

        .chat-input:focus {
            border-color: rgba(var(--color-primary-rgb), 0.5);
            background: var(--color-bg-input-focus);
        }

        .chat-input::placeholder {
            color: var(--color-text-muted);
            opacity: 0.5;
        }

        .send-btn {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 1rem;
            background: linear-gradient(135deg, var(--color-primary) 0%, #d97706 100%);
            border: none;
            color: #020617;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: none; /* Retrait de l'ombre */
        }

        .send-btn:hover {
            transform: scale(1.05);
            box-shadow: none; /* Retrait de l'ombre */
        }

        .send-btn:active {
            transform: scale(0.95);
        }

        .send-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .send-btn svg {
            width: 1.25rem;
            height: 1.25rem;
        }

        /* Désactiver l'icône de suppression par défaut des inputs type search */
        input[type="search"]::-webkit-search-decoration,
        input[type="search"]::-webkit-search-cancel-button,
        input[type="search"]::-webkit-search-results-button,
        input[type="search"]::-webkit-search-results-decoration {
            -webkit-appearance: none;
            display: none;
        }

        .clear-btn {
            background: transparent;
            border: none;
            color: var(--color-text-muted);
            width: 2.25rem;
            height: 2.25rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .clear-btn:hover {
            background: var(--color-bg-btn-hover);
            color: #ef4444; /* Rouge au survol */
        }

        .clear-btn:active {
            transform: scale(0.95);
        }
    </style>
</head>
<body>
    <div class="chat-app" id="chatApp">
        <!-- Header -->
        <header class="chat-header">
            <div class="header-left">
                <a href="../" class="back-btn" title="Retour au tableau de bord">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                </a>
                <div class="avatar-container">
                    <div class="avatar">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 9h.01M16 9h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <span class="status-indicator"></span>
                </div>
                <div class="header-info">
                    <h1>Coach Wari</h1>
                    <p>Conseiller Financier</p>
                </div>
            </div>
            <button class="clear-btn" onclick="clearHistory()" title="Effacer l'historique de discussion">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
            </button>
        </header>

        <!-- Zone des Messages -->
        <main class="chat-messages" id="chatMessages">
            <!-- Rendu par JS -->
        </main>

        <!-- Puces de Suggestions -->
        <section class="suggestions-row" id="suggestionsRow">
            <button class="suggestion-chip" onclick="sendSuggestion('Comment puis-je optimiser mon budget ce mois-ci ?')">💡 Optimiser mon budget</button>
            <button class="suggestion-chip" onclick="sendSuggestion('Fais-moi une analyse complète de ma situation financière actuelle.')">📊 Analyse complète</button>
            <button class="suggestion-chip" onclick="sendSuggestion('Quel est ton meilleur conseil de discipline financière aujourd\'hui ?')">🥋 Conseil discipline</button>
            <button class="suggestion-chip" onclick="sendSuggestion('Comment gérer mes dettes et les rembourser plus vite ?')">💸 Gérer mes dettes</button>
        </section>

        <!-- Zone d'écriture -->
        <footer class="input-bar">
            <input type="search" class="chat-input" id="chatInput" placeholder="Pose ta question financière..." autocomplete="off" name="wari_chat_query" autocorrect="off" spellcheck="false">
            <button class="send-btn" id="sendBtn" onclick="submitChat()" aria-label="Envoyer le message">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </button>
        </footer>
    </div>

    <!-- Script de gestion du Chat Coach AI -->
    <script>
        var coachChatHistory = [];

        // Fonction pour formater en gras (**texte**) et ajouter des retours à la ligne de manière sécurisée (Sans XSS)
        function renderFormattedText(container, text) {
            var lines = text.split('\n');
            for (var i = 0; i < lines.length; i++) {
                if (i > 0) {
                    container.appendChild(document.createElement('br'));
                }
                
                var line = lines[i];
                var boldRegex = /\*\*(.*?)\*\*/g;
                var lastIdx = 0;
                var match;
                
                while ((match = boldRegex.exec(line)) !== null) {
                    var textBefore = line.substring(lastIdx, match.index);
                    if (textBefore) {
                        container.appendChild(document.createTextNode(textBefore));
                    }
                    
                    var strong = document.createElement('strong');
                    strong.textContent = match[1];
                    container.appendChild(strong);
                    
                    lastIdx = boldRegex.lastIndex;
                }
                
                var textAfter = line.substring(lastIdx);
                if (textAfter) {
                    container.appendChild(document.createTextNode(textAfter));
                }
            }
        }

        // Ajouter un message à la liste de manière sécurisée
        function appendChatMessage(text, sender) {
            var container = document.getElementById('chatMessages');
            if (!container) return;
            
            var msgDiv = document.createElement('div');
            if (sender === 'user') {
                msgDiv.className = "message message-user";
                msgDiv.textContent = text;
            } else {
                msgDiv.className = "message message-coach";
                renderFormattedText(msgDiv, text);
            }
            
            container.appendChild(msgDiv);
            container.scrollTop = container.scrollHeight;
        }

        // Ajouter un indicateur de réflexion
        function appendChatThinking(id) {
            var container = document.getElementById('chatMessages');
            if (!container) return;
            
            var msgDiv = document.createElement('div');
            msgDiv.id = id;
            msgDiv.className = "message-thinking";
            
            for (var i = 0; i < 3; i++) {
                var dot = document.createElement('span');
                dot.className = "dot";
                msgDiv.appendChild(dot);
            }
            
            container.appendChild(msgDiv);
            container.scrollTop = container.scrollHeight;
        }

        // Récupérer le contexte financier calculé par PHP et LocalStorage
        function getFinancialStatusContext() {
            var baseContext = <?php echo json_encode([
                'cash_restant' => $cash,
                'jours_restants' => $daysLeft,
                'budget_quotidien_conseille' => ($daysLeft > 0 ? (int)round($cash / $daysLeft) : 0),
                'categories_details' => $catSummary,
                'dettes_details' => $debtsSummary,
                'total_dettes' => $totalDettes,
                'capital_projet' => $projectCapital,
                'devise' => $currency
            ]); ?>;

            // Récupération de l'objectif d'épargne projet ciblé par l'utilisateur
            var goalStr = localStorage.getItem("wari_vault_goal");
            var goalAmount = 0;
            var goalLabel = "";
            if (goalStr) {
                try {
                    var goal = JSON.parse(goalStr);
                    if (goal) {
                        goalAmount = parseInt(goal.amount) || 0;
                        goalLabel = goal.label || goal.name || "";
                    }
                } catch(e) {}
            }

            baseContext.objectif_projet_montant = goalAmount;
            baseContext.objectif_projet_label = goalLabel;

            return baseContext;
        }

        // Action de soumission de la discussion
        async function submitChat() {
            var input = document.getElementById('chatInput');
            var sendBtn = document.getElementById('sendBtn');
            if (!input || !sendBtn) return;
            
            var text = input.value.trim();
            if (!text) return;
            
            input.value = '';
            input.disabled = true;
            sendBtn.disabled = true;
            
            // Fermer le clavier virtuel en enlevant le focus de la zone de saisie
            input.blur();
            
            appendChatMessage(text, 'user');
            
            var thinkingId = 'thinking_' + Date.now();
            appendChatThinking(thinkingId);
            
            var financialContext = getFinancialStatusContext();
            
            try {
                var formData = new FormData();
                formData.append('action', 'coach_chat');
                formData.append('message', text);
                formData.append('data', JSON.stringify(financialContext));
                formData.append('history', JSON.stringify(coachChatHistory));
                
                var res = await fetch('../academy-admin/ai_gateway.php', {
                    method: 'POST',
                    body: formData
                });
                
                var result = await res.json();
                
                var thinkingEl = document.getElementById(thinkingId);
                if (thinkingEl) {
                    thinkingEl.remove();
                }
                
                if (result && result.response) {
                    appendChatMessage(result.response, 'coach');
                    
                    coachChatHistory.push({ role: 'user', content: text });
                    coachChatHistory.push({ role: 'model', content: result.response });
                    if (coachChatHistory.length > 20) {
                        coachChatHistory.shift();
                        coachChatHistory.shift();
                    }
                    localStorage.setItem('wari_coach_history', JSON.stringify(coachChatHistory));
                } else {
                    appendChatMessage("Désolé, j'ai rencontré un petit problème réseau. Reste concentré sur tes objectifs !", 'coach');
                }
            } catch (err) {
                console.error("Coach chat error:", err);
                var thinkingEl = document.getElementById(thinkingId);
                if (thinkingEl) {
                    thinkingEl.remove();
                }
                appendChatMessage("Aïe, impossible de me connecter pour l'instant. Garde ta discipline budgétaire, c'est le plus important !", 'coach');
            } finally {
                input.disabled = false;
                sendBtn.disabled = false;
                
                setTimeout(adjustLayoutForKeyboard, 50);
            }
        }

        // Envoyer une suggestion pré-définie
        function sendSuggestion(text) {
            var input = document.getElementById('chatInput');
            if (input) {
                input.value = text;
                submitChat();
            }
        }

        // Ajuster la hauteur de la page selon le clavier virtuel
        function adjustLayoutForKeyboard() {
            if (window.visualViewport) {
                var vv = window.visualViewport;
                var height = vv.height;
                var topOffset = vv.offsetTop;
                
                var app = document.getElementById('chatApp');
                if (app) {
                    app.style.height = height + 'px';
                    app.style.top = topOffset + 'px';
                }
                
                window.scrollTo(0, 0);
                
                var messagesContainer = document.getElementById('chatMessages');
                if (messagesContainer) {
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }
            }
        }

        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', adjustLayoutForKeyboard);
            window.visualViewport.addEventListener('scroll', adjustLayoutForKeyboard);
        }

        // Effacer l'historique de discussion
        function clearHistory() {
            localStorage.removeItem('wari_coach_history');
            coachChatHistory = [];
            
            var container = document.getElementById('chatMessages');
            if (container) {
                container.replaceChildren(); // Nettoyage DOM sécurisé
            }
            
            appendChatMessage("Historique effacé. De quoi aimerais-tu parler maintenant ?", 'coach');
        }

        // Au chargement complet de la page
        window.addEventListener('DOMContentLoaded', function() {
            adjustLayoutForKeyboard();
            
            var input = document.getElementById('chatInput');
            var app = document.getElementById('chatApp');
            if (input) {
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        submitChat();
                    }
                });

                if (app) {
                    input.addEventListener('focus', function() {
                        app.classList.add('keyboard-active');
                        setTimeout(adjustLayoutForKeyboard, 100);
                    });
                    input.addEventListener('blur', function() {
                        app.classList.remove('keyboard-active');
                        setTimeout(adjustLayoutForKeyboard, 100);
                    });
                }
            }
            
            // Charger l'historique de discussion
            var savedHistory = localStorage.getItem('wari_coach_history');
            if (savedHistory) {
                try {
                    coachChatHistory = JSON.parse(savedHistory) || [];
                } catch(e) {
                    coachChatHistory = [];
                }
            }
            
            // Rendre les messages passés
            if (coachChatHistory && coachChatHistory.length > 0) {
                for (var i = 0; i < coachChatHistory.length; i++) {
                    var msg = coachChatHistory[i];
                    var sender = (msg.role === 'user') ? 'user' : 'coach';
                    appendChatMessage(msg.content, sender);
                }
            } else {
                // Premier message du Coach
                appendChatMessage("Salut ! Ravi de te retrouver pour t'accompagner dans ta discipline financière. De quoi aimerais-tu parler aujourd'hui ?", 'coach');
            }
        });
    </script>
</body>
</html>

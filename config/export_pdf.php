<?php
// /var/www/wari.digiroys.com/config/export_pdf.php
ob_start();
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);

require_once __DIR__ . '/../wari_monitoring.php';  // TOUJOURS EN PREMIER
session_start();
require_once 'session_config.php';
require_once 'db.php';
require_once 'session_check.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/AI.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// 1. Vérification d'accès
if (!isset($_SESSION['user_id'])) {
    header('Location: ../config/auth.php');
    exit();
}

if (!isset($_SESSION['is_premium']) || !$_SESSION['is_premium']) {
    header('Location: ../paid/index.php');
    exit();
}

$userId = $_SESSION['user_id'];
$userEmail = $_SESSION['user_email'] ?? 'Utilisateur Wari';
$walletType = $_GET['wallet_type'] ?? 'perso';
if (!in_array($walletType, ['perso', 'pro'])) {
    $walletType = 'perso';
}

$month = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    $month = date('Y-m');
}

// ✅ Traduction manuelle des mois en français
$moisFr = [
    '01' => 'Janvier',
    '02' => 'Février',
    '03' => 'Mars',
    '04' => 'Avril',
    '05' => 'Mai',
    '06' => 'Juin',
    '07' => 'Juillet',
    '08' => 'Août',
    '09' => 'Septembre',
    '10' => 'Octobre',
    '11' => 'Novembre',
    '12' => 'Décembre',
];

$monthParts = explode('-', $month);
$monthLabel = ($moisFr[$monthParts[1]] ?? '??') . ' ' . $monthParts[0];

try {
    // 2. Charger le budget de l'utilisateur
    $stmtUser = $pdo->prepare("SELECT budget_data, budget_data_pro FROM wari_users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

    $budgetRaw = ($walletType === 'pro') ? ($userData['budget_data_pro'] ?? null) : ($userData['budget_data'] ?? null);
    
    $categories = [];
    $currency = 'F';
    
    if ($budgetRaw && $budgetRaw !== 'null') {
        $budgetData = json_decode($budgetRaw, true);
        $categories = $budgetData['categories'] ?? [];
        $currency = $budgetData['currency'] ?? 'F';
    } else {
        // Budget par défaut
        if ($walletType === 'pro') {
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

    // 3. Récupérer les distributions (revenus) du mois
    $stmtDistrib = $pdo->prepare("
        SELECT SUM(amount) as total_income 
        FROM wari_distributions 
        WHERE user_id = ? AND wallet_type = ? AND DATE_FORMAT(distributed_at, '%Y-%m') = ?
    ");
    $stmtDistrib->execute([$userId, $walletType, $month]);
    $totalIncome = (int)($stmtDistrib->fetchColumn() ?: 0);

    // 4. Récupérer les dépenses du mois
    $stmtExpenses = $pdo->prepare("
        SELECT SUM(amount) as total_spent 
        FROM wari_expenses 
        WHERE user_id = ? AND wallet_type = ? AND DATE_FORMAT(date_expense, '%Y-%m') = ?
    ");
    $stmtExpenses->execute([$userId, $walletType, $month]);
    $totalSpent = (int)($stmtExpenses->fetchColumn() ?: 0);

    // 5. Récupérer les dépenses groupées par catégorie
    $stmtCatExpenses = $pdo->prepare("
        SELECT category_id, SUM(amount) as spent 
        FROM wari_expenses 
        WHERE user_id = ? AND wallet_type = ? AND DATE_FORMAT(date_expense, '%Y-%m') = ?
        GROUP BY category_id
    ");
    $stmtCatExpenses->execute([$userId, $walletType, $month]);
    $catExpenses = $stmtCatExpenses->fetchAll(PDO::FETCH_KEY_PAIR);

    // 6. Récupérer les dernières transactions
    $stmtTransactions = $pdo->prepare("
        SELECT DATE_FORMAT(date_expense, '%d/%m/%Y à %H:%i') as date_label, category_id, amount, description
        FROM wari_expenses
        WHERE user_id = ? AND wallet_type = ? AND DATE_FORMAT(date_expense, '%Y-%m') = ?
        ORDER BY date_expense DESC
        LIMIT 20
    ");
    $stmtTransactions->execute([$userId, $walletType, $month]);
    $transactions = $stmtTransactions->fetchAll(PDO::FETCH_ASSOC);

    // 7. Calculs globaux
    $savings = $totalIncome - $totalSpent;
    $savingsRate = ($totalIncome > 0) ? ($savings / $totalIncome) * 100 : 0;
    if ($savingsRate < 0) $savingsRate = 0;

    // 8. Préparer le prompt pour le Coach IA
    $categorySummaryText = "";
    foreach ($categories as $cat) {
        $catId = $cat['id'];
        $catName = $cat['name'];
        $percent = $cat['percent'];
        $targetBudget = $totalIncome * ($percent / 100);
        $spent = $catExpenses[$catId] ?? 0;
        $categorySummaryText .= "- Catégorie '$catName' (Cible : $percent%) : Budget Cible = " . number_format($targetBudget, 0, '', ' ') . " $currency | Dépensé Réel = " . number_format($spent, 0, '', ' ') . " $currency\n";
    }

    $prompt = "Voici les statistiques financières mensuelles de l'utilisateur pour le portefeuille " . ($walletType === 'pro' ? 'Professionnel (Commercial)' : 'Personnel') . " :
- Mois : $monthLabel
- Revenus (répartitions reçues) : " . number_format($totalIncome, 0, '', ' ') . " $currency
- Dépenses totales : " . number_format($totalSpent, 0, '', ' ') . " $currency
- Solde restant (épargne nette / profit) : " . number_format($savings, 0, '', ' ') . " $currency
- Taux d'épargne net : " . number_format($savingsRate, 1) . "%

Détails des dépenses par catégorie :
$categorySummaryText

En te basant sur ces chiffres, rédige une analyse constructive et motivante au format JSON. Tu es le Coach Wari, le Grand Frère de la discipline financière en Afrique. Ton ton est direct, expert, et bienveillant. Évite d'utiliser des salutations ou signatures, et n'utilise pas le terme 'Champion'. Va droit au but.
Tu dois retourner impérativement un objet JSON avec les clés suivantes :
{
  \"forces\": \"Analyse des points forts de l'utilisateur ce mois-ci (max 2 phrases).\",
  \"faiblesses\": \"Analyse des points faibles ou des opportunités d'amélioration pour le mois prochain (max 2 phrases).\",
  \"conseil_action\": \"Un conseil concret et directement applicable pour optimiser son budget (max 2 phrases).\"
}";

    $aiAdvice = [
        'forces' => "Excellente discipline budgétaire observée sur les postes clés.",
        'faiblesses' => "Certaines enveloppes budgétaires présentent de légers dépassements.",
        'conseil_action' => "Pensez à utiliser le mode lecture pour surveiller régulièrement vos dépenses de train de vie."
    ];

    try {
        $ai = new AI();
        $aiResponse = $ai->generate($prompt, "Tu es le Coach Wari, mentor financier en Afrique. Tu parles directement, sans fioritures et tu retournes uniquement du JSON.");
        
        $decoded = json_decode($aiResponse, true);
        if (is_array($decoded)) {
            if (isset($decoded['forces']) && $decoded['forces']) $aiAdvice['forces'] = $decoded['forces'];
            if (isset($decoded['faiblesses']) && $decoded['faiblesses']) $aiAdvice['faiblesses'] = $decoded['faiblesses'];
            if (isset($decoded['conseil_action']) && $decoded['conseil_action']) $aiAdvice['conseil_action'] = $decoded['conseil_action'];
        } else {
            // Si l'IA renvoie du texte libre brut
            $aiAdvice['forces'] = $aiResponse;
            $aiAdvice['faiblesses'] = "";
            $aiAdvice['conseil_action'] = "";
        }
    } catch (Exception $e) {
        // En cas d'erreur de l'API AI, on conserve les valeurs par défaut
    }

    // 9. Générer le contenu HTML du PDF
    $html = '
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <style>
            @page {
                margin: 15mm 20mm;
            }
            body {
                font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
                color: #1e293b;
                font-size: 11px;
                line-height: 1.5;
                background-color: #ffffff;
            }
            .header-table {
                width: 100%;
                border-collapse: collapse;
                border-bottom: 2px solid #e2e8f0;
                padding-bottom: 12px;
                margin-bottom: 20px;
            }
            .logo {
                font-size: 22px;
                font-weight: bold;
                color: #d97706;
            }
            .subtitle {
                font-size: 8px;
                text-transform: uppercase;
                letter-spacing: 2px;
                color: #64748b;
                margin-top: 2px;
            }
            .report-title {
                text-align: right;
                font-size: 14px;
                font-weight: bold;
                color: #0f172a;
                text-transform: uppercase;
            }
            .meta-text {
                text-align: right;
                font-size: 9px;
                color: #475569;
                margin-top: 4px;
            }
            .kpi-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            .kpi-card {
                background-color: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 6px;
                padding: 10px;
                text-align: center;
            }
            .kpi-card.green {
                border-left: 4px solid #10b981;
            }
            .kpi-card.red {
                border-left: 4px solid #ef4444;
            }
            .kpi-card.gold {
                border-left: 4px solid #d97706;
            }
            .kpi-label {
                font-size: 8px;
                text-transform: uppercase;
                color: #64748b;
                font-weight: bold;
                margin-bottom: 4px;
            }
            .kpi-value {
                font-size: 15px;
                font-weight: bold;
                color: #0f172a;
            }
            .section-title {
                font-size: 11px;
                font-weight: bold;
                text-transform: uppercase;
                color: #0f172a;
                border-bottom: 1px solid #cbd5e1;
                padding-bottom: 4px;
                margin-bottom: 10px;
                margin-top: 15px;
            }
            .table-data {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            .table-data th {
                background-color: #f1f5f9;
                color: #475569;
                font-size: 9px;
                font-weight: bold;
                text-transform: uppercase;
                padding: 6px 8px;
                text-align: left;
                border-bottom: 1px solid #cbd5e1;
            }
            .table-data td {
                padding: 6px 8px;
                border-bottom: 1px solid #e2e8f0;
                font-size: 10px;
            }
            .badge {
                padding: 2px 5px;
                border-radius: 3px;
                font-size: 8px;
                font-weight: bold;
                text-transform: uppercase;
            }
            .badge.success {
                background-color: #d1fae5;
                color: #065f46;
            }
            .badge.warning {
                background-color: #fee2e2;
                color: #991b1b;
            }
            .coach-container {
                background-color: #fffbeb;
                border: 1px solid #fef3c7;
                border-left: 4px solid #d97706;
                border-radius: 6px;
                padding: 12px;
                margin-top: 20px;
            }
            .coach-title {
                font-size: 10px;
                font-weight: bold;
                color: #b45309;
                text-transform: uppercase;
                margin-bottom: 6px;
            }
            .coach-content {
                font-size: 10px;
                color: #451a03;
            }
            .footer {
                text-align: center;
                font-size: 8px;
                color: #94a3b8;
                margin-top: 30px;
                border-top: 1px dashed #e2e8f0;
                padding-top: 10px;
            }
        </style>
    </head>
    <body>

        <!-- Header -->
        <table class="header-table">
            <tr>
                <td style="width: 50%;">
                    <div class="logo">WARI FINANCE</div>
                    <div class="subtitle">Discipline • Liberté • Suivis</div>
                </td>
                <td style="width: 50%; text-align: right;">
                    <div class="report-title">Bilan Financier Mensuel</div>
                    <div class="meta-text">
                        <strong>Mois :</strong> ' . htmlspecialchars($monthLabel) . '<br/>
                        <strong>Portefeuille :</strong> ' . ($walletType === 'pro' ? 'Professionnel (Commercial)' : 'Personnel') . '<br/>
                        <strong>Utilisateur :</strong> ' . htmlspecialchars($userEmail) . '
                    </div>
                </td>
            </tr>
        </table>

        <!-- KPIs -->
        <table class="kpi-table">
            <tr>
                <td style="width: 32%; padding-right: 2%;">
                    <div class="kpi-card green">
                        <div class="kpi-label">Revenus (Répartitions)</div>
                        <div class="kpi-value">' . number_format($totalIncome, 0, '', ' ') . ' ' . $currency . '</div>
                    </div>
                </td>
                <td style="width: 32%; padding-right: 2%;">
                    <div class="kpi-card red">
                        <div class="kpi-label">Dépenses Réelles</div>
                        <div class="kpi-value">' . number_format($totalSpent, 0, '', ' ') . ' ' . $currency . '</div>
                    </div>
                </td>
                <td style="width: 32%;">
                    <div class="kpi-card gold">
                        <div class="kpi-label">Solde Restant</div>
                        <div class="kpi-value">' . number_format($savings, 0, '', ' ') . ' ' . $currency . '</div>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Tableau Répartition -->
        <div class="section-title">Répartition Budgétaire par Catégorie</div>
        <table class="table-data">
            <thead>
                <tr>
                    <th>Catégorie</th>
                    <th style="text-align: center;">Pourcentage Cible</th>
                    <th style="text-align: right;">Budget Cible</th>
                    <th style="text-align: right;">Dépensé Réel</th>
                    <th style="text-align: right;">Solde Restant</th>
                    <th style="text-align: center;">Statut</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($categories as $cat) {
        $catId = $cat['id'];
        $catName = $cat['name'];
        $percent = $cat['percent'];
        
        $targetBudget = $totalIncome * ($percent / 100);
        $spent = $catExpenses[$catId] ?? 0;
        $remaining = $targetBudget - $spent;
        
        $statusClass = ($remaining >= 0) ? 'success' : 'warning';
        $statusText = ($remaining >= 0) ? 'Respecté' : 'Dépassement';

        $html .= '
                <tr>
                    <td><strong>' . htmlspecialchars($catName) . '</strong></td>
                    <td style="text-align: center;">' . $percent . '%</td>
                    <td style="text-align: right;">' . number_format($targetBudget, 0, '', ' ') . ' ' . $currency . '</td>
                    <td style="text-align: right;">' . number_format($spent, 0, '', ' ') . ' ' . $currency . '</td>
                    <td style="text-align: right; color: ' . ($remaining >= 0 ? '#065f46' : '#991b1b') . ';">' . number_format($remaining, 0, '', ' ') . ' ' . $currency . '</td>
                    <td style="text-align: center;">
                        <span class="badge ' . $statusClass . '">' . $statusText . '</span>
                    </td>
                </tr>';
    }

    $html .= '
            </tbody>
        </table>

        <!-- Coach IA -->
        <div class="coach-container">
            <div class="coach-title">💡 Conseils et Analyse du Coach Wari</div>
            <div class="coach-content">
                <p><strong>Forces :</strong> ' . htmlspecialchars($aiAdvice['forces']) . '</p>';
                if ($aiAdvice['faiblesses']) {
                    $html .= '<p><strong>Points d\'attention :</strong> ' . htmlspecialchars($aiAdvice['faiblesses']) . '</p>';
                }
                if ($aiAdvice['conseil_action']) {
                    $html .= '<p><strong>Action suggérée :</strong> ' . htmlspecialchars($aiAdvice['conseil_action']) . '</p>';
                }
    $html .= '
            </div>
        </div>';

    // Afficher les dernières transactions s'il y en a
    if (!empty($transactions)) {
        $html .= '
        <div class="section-title" style="margin-top: 25px;">Dernières dépenses du mois</div>
        <table class="table-data">
            <thead>
                <tr>
                    <th style="width: 25%;">Date & Heure</th>
                    <th style="width: 25%;">Catégorie</th>
                    <th style="width: 35%;">Description</th>
                    <th style="width: 15%; text-align: right;">Montant</th>
                </tr>
            </thead>
            <tbody>';
        
        // Mapper pour retrouver le nom de la catégorie depuis son ID
        $catMap = [];
        foreach ($categories as $cat) {
            $catMap[$cat['id']] = $cat['name'];
        }

        foreach ($transactions as $tx) {
            $catName = $catMap[$tx['category_id']] ?? 'Autre';
            $html .= '
                <tr>
                    <td>' . htmlspecialchars($tx['date_label']) . '</td>
                    <td>' . htmlspecialchars($catName) . '</td>
                    <td>' . htmlspecialchars($tx['description']) . '</td>
                    <td style="text-align: right; font-weight: bold;">' . number_format($tx['amount'], 0, '', ' ') . ' ' . $currency . '</td>
                </tr>';
        }

        $html .= '
            </tbody>
        </table>';
    }

    $html .= '
        <!-- Footer -->
        <div class="footer">
            Rapport généré automatiquement par Wari Finance Premium • RB/COT/19 A 50622 • IFU 0 2019 1088 5778 • wari-finance@digiroys.com
        </div>

    </body>
    </html>';

    // 10. Configurer et exécuter Dompdf
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', false); // Évite les requêtes HTTP externes non autorisées
    $options->set('defaultFont', 'Helvetica');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    
    // Nettoyer le tampon d'extraction pour éviter toute corruption du PDF
    ob_end_clean();
    
    $dompdf->render();
    
    // Streamer le fichier
    $filename = "WARI_Bilan_" . str_replace('-', '_', $month) . "_" . $walletType . ".pdf";
    $dompdf->stream($filename, ["Attachment" => true]);
    exit();

} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo "Une erreur s'est produite lors de la génération du rapport PDF : " . $e->getMessage();
}

<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require '../config/db.php';
require_once '../vendor/autoload.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

// ============================================
// ACTIONS AJAX (protégées)
// ============================================
if (isset($_GET['action'])) {
    requireAuth();
    header('Content-Type: application/json; charset=utf-8');

    try {
        $userId = isset($_GET['user_id']) ? validateUserId($_GET['user_id']) : null;
        $adminId = $_SESSION['admin_id'] ?? 'unknown';

        switch ($_GET['action']) {

            case 'reset_full':
                if (!$userId) throw new InvalidArgumentException('User ID requis');

                $pdo->beginTransaction();

                $stmt = $pdo->prepare("UPDATE wari_users SET 
                    budget_data = JSON_SET(budget_data,
                        '$.categories[0].balance', 0,
                        '$.categories[1].balance', 0,
                        '$.categories[2].balance', 0,
                        '$.categories[3].balance', 0,
                        '$.vaultTransactions', JSON_ARRAY(),
                        '$.projectCapital', 0,
                        '$.hasDepositedToday', false
                    ), project_capital = 0 WHERE id = ?");
                $stmt->execute([$userId]);

                $pdo->prepare("DELETE FROM wari_expenses WHERE user_id = ?")->execute([$userId]);
                $pdo->prepare("DELETE FROM wari_vault_history WHERE user_id = ?")->execute([$userId]);

                $pdo->commit();

                auditLog('RESET_FULL', ['target_user' => $userId, 'admin' => $adminId]);
                jsonResponse(true, ['msg' => 'Mémoire complète effacée']);

            case 'reset_expenses':
                if (!$userId) throw new InvalidArgumentException('User ID requis');

                $stmt = $pdo->prepare("DELETE FROM wari_expenses WHERE user_id = ?");
                $stmt->execute([$userId]);

                auditLog('RESET_EXPENSES', ['target_user' => $userId, 'admin' => $adminId]);
                jsonResponse(true, ['msg' => 'Dépenses effacées']);

            case 'reset_capital':
                if (!$userId) throw new InvalidArgumentException('User ID requis');

                $pdo->beginTransaction();

                $pdo->prepare("UPDATE wari_users SET project_capital = 0,
                    budget_data = JSON_SET(budget_data, '$.projectCapital', 0, '$.vaultTransactions', JSON_ARRAY())
                    WHERE id = ?")->execute([$userId]);
                $pdo->prepare("DELETE FROM wari_vault_history WHERE user_id = ?")->execute([$userId]);

                $pdo->commit();

                auditLog('RESET_CAPITAL', ['target_user' => $userId, 'admin' => $adminId]);
                jsonResponse(true, ['msg' => 'Capital & coffre remis à zéro']);

            case 'reset_balances':
                if (!$userId) throw new InvalidArgumentException('User ID requis');

                $stmt = $pdo->prepare("UPDATE wari_users SET
                    budget_data = JSON_SET(budget_data,
                        '$.categories[0].balance', 0,
                        '$.categories[1].balance', 0,
                        '$.categories[2].balance', 0,
                        '$.categories[3].balance', 0
                    ) WHERE id = ?");
                $stmt->execute([$userId]);

                auditLog('RESET_BALANCES', ['target_user' => $userId, 'admin' => $adminId]);
                jsonResponse(true, ['msg' => 'Soldes catégories remis à zéro']);

            case 'reset_debts':
                if (!$userId) throw new InvalidArgumentException('User ID requis');

                $stmt = $pdo->prepare("DELETE FROM wari_debts WHERE user_id = ?");
                $stmt->execute([$userId]);

                auditLog('RESET_DEBTS', ['target_user' => $userId, 'admin' => $adminId]);
                jsonResponse(true, ['msg' => 'Dettes effacées']);

            case 'toggle_block':
                if (!$userId) throw new InvalidArgumentException('User ID requis');

                $stmt = $pdo->prepare("SELECT commande_id FROM wari_users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user) {
                    jsonResponse(false, ['msg' => 'User introuvable'], 404);
                }

                $stmtLic = $pdo->prepare("SELECT statut FROM wari_licences WHERE commande_id = ?");
                $stmtLic->execute([$user['commande_id']]);
                $lic = $stmtLic->fetch(PDO::FETCH_ASSOC);

                if (!$lic) {
                    jsonResponse(false, ['msg' => 'Licence introuvable'], 404);
                }

                $newStatut = $lic['statut'] === 'suspendu' ? 'utilise' : 'suspendu';
                $pdo->prepare("UPDATE wari_licences SET statut = ? WHERE commande_id = ?")
                    ->execute([$newStatut, $user['commande_id']]);

                auditLog('TOGGLE_BLOCK', [
                    'target_user' => $userId,
                    'licence' => $user['commande_id'],
                    'new_status' => $newStatut,
                    'admin' => $adminId
                ]);

                $msg = $newStatut === 'suspendu' ? 'Compte suspendu' : 'Compte réactivé';
                jsonResponse(true, ['msg' => $msg, 'statut' => $newStatut]);

            case 'get_user_detail':
                if (!$userId) throw new InvalidArgumentException('User ID requis');

                $stmt = $pdo->prepare("SELECT u.*, l.statut as licence_statut,
                    (SELECT COUNT(*) FROM wari_expenses WHERE user_id = u.id) as nb_expenses,
                    (SELECT COALESCE(SUM(amount),0) FROM wari_expenses WHERE user_id = u.id) as total_spent,
                    (SELECT COUNT(*) FROM wari_debts WHERE user_id = u.id) as nb_debts,
                    (SELECT COUNT(*) FROM wari_vault_history WHERE user_id = u.id) as nb_vault_ops
                    FROM wari_users u
                    LEFT JOIN wari_licences l ON l.commande_id = u.commande_id
                    WHERE u.id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && $user['budget_data']) {
                    $user['budget_parsed'] = json_decode($user['budget_data'], true);
                    unset($user['budget_data'], $user['password']);
                }

                jsonResponse(true, ['user' => $user]);

            case 'get_stats':
                try {
                    $stats = [
                        'total_users'    => $pdo->query("SELECT COUNT(*) FROM wari_users")->fetchColumn(),
                        'active_today'   => $pdo->query("SELECT COUNT(*) FROM wari_users WHERE DATE(last_budget_at) = CURDATE()")->fetchColumn(),
                        'active_week'    => $pdo->query("SELECT COUNT(*) FROM wari_users WHERE last_budget_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn(),

                        'licences_dispo' => $pdo->query("SELECT COUNT(*) FROM wari_licences WHERE statut='disponible'")->fetchColumn(),
                        'licences_total' => $pdo->query("SELECT COUNT(*) FROM wari_licences")->fetchColumn(),
                        'push_subscribers' => $pdo->query("SELECT COUNT(DISTINCT user_id) FROM wari_subscriptions")->fetchColumn()
                    ];
                    jsonResponse(true, ['stats' => $stats]);
                } catch (Exception $e) {
                    error_log('get_stats error: ' . $e->getMessage());
                    jsonResponse(false, ['msg' => 'Erreur base de données'], 500);
                }

            case 'get_licences':
                $licences = $pdo->query("SELECT l.*, u.email FROM wari_licences l
                    LEFT JOIN wari_users u ON u.commande_id = l.commande_id
                    ORDER BY l.date_creation DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
                jsonResponse(true, ['licences' => $licences]);

            case 'add_licence':
                $prefix = preg_replace('/[^A-Z0-9\-]/i', '', $_GET['prefix'] ?? 'WARI-26');
                $prefix = substr($prefix, 0, 20);
                $suffix = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 4));
                $code = $prefix . '-' . $suffix;

                $pdo->prepare("INSERT INTO wari_licences (commande_id, statut, date_creation) VALUES (?, 'disponible', NOW())")
                    ->execute([$code]);

                auditLog('ADD_LICENCE', ['code' => $code, 'admin' => $adminId]);
                jsonResponse(true, ['code' => $code]);

            case 'push_all':
                // Récupération et nettoyage du message (sans double encodage)
                $message = isset($_GET['message']) ? trim($_GET['message']) : '';
                $message = strip_tags($message); // Retire les balises HTML
                $message = mb_substr($message, 0, 500); // Limite à 500 caractères UTF-8

                // URL avec validation
                $url = filter_var($_GET['url'] ?? '', FILTER_VALIDATE_URL) ?: 'https://wari.digiroys.com';

                if (empty($message)) {
                    jsonResponse(false, ['msg' => 'Message vide'], 400);
                }

                $webPush = new WebPush(VAPID_CONFIG);

                // Récupérer les abonnements avec info utilisateur pour le compteur
                $subs = $pdo->query("SELECT s.endpoint, s.p256dh, s.auth, s.user_id, u.email 
                    FROM wari_subscriptions s
                    LEFT JOIN wari_users u ON u.id = s.user_id")
                    ->fetchAll(PDO::FETCH_ASSOC);

                // Payload JSON avec accents préservés
                $payload = json_encode([
                    'title' => 'Wari Finance',
                    'body'  => $message,
                    'icon'  => 'https://i.postimg.cc/NFhtHvBK/wari-logos-sfnd.png',
                    'url'   => $url,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                // Tableau pour tracker les résultats par utilisateur
                $results = [
                    'success' => [],
                    'expired' => [],
                    'failed' => []
                ];

                foreach ($subs as $sub) {
                    $webPush->queueNotification(
                        Subscription::create([
                            'endpoint' => $sub['endpoint'],
                            'keys' => ['p256dh' => $sub['p256dh'], 'auth' => $sub['auth']],
                        ]),
                        $payload
                    );
                }

                $index = 0;
                foreach ($webPush->flush() as $report) {
                    $userEmail = $subs[$index]['email'] ?? 'Inconnu';
                    $userId = $subs[$index]['user_id'] ?? 0;

                    if ($report->isSuccess()) {
                        $results['success'][] = ['email' => $userEmail, 'user_id' => $userId];
                    } elseif ($report->isSubscriptionExpired()) {
                        $results['expired'][] = ['email' => $userEmail, 'user_id' => $userId];
                        // Supprimer l'abonnement expiré
                        $pdo->prepare("DELETE FROM wari_subscriptions WHERE endpoint = ?")
                            ->execute([$subs[$index]['endpoint']]);
                    } else {
                        $results['failed'][] = [
                            'email' => $userEmail,
                            'user_id' => $userId,
                            'reason' => $report->getReason()
                        ];
                    }
                    $index++;
                }

                $sent = count($results['success']);
                $expired = count($results['expired']);
                $failed = count($results['failed']);
                $total = count($subs);

                auditLog('PUSH_ALL', [
                    'recipients' => $total,
                    'sent' => $sent,
                    'expired' => $expired,
                    'failed' => $failed,
                    'admin' => $adminId,
                    'message_preview' => mb_substr($message, 0, 50)
                ]);

                // Message détaillé avec compteur
                $msgDetail = "📊 Résultat du push :\n";
                $msgDetail .= "✅ Reçus : {$sent}/{$total}\n";
                if ($expired > 0) $msgDetail .= "💀 Expirés : {$expired}\n";
                if ($failed > 0) $msgDetail .= "❌ Échecs : {$failed}";

                jsonResponse(true, [
                    'msg' => $msgDetail,
                    'details' => $results,
                    'stats' => [
                        'total' => $total,
                        'success' => $sent,
                        'expired' => $expired,
                        'failed' => $failed
                    ]
                ]);

            case 'export_csv':
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=wari_users_' . date('Y-m-d_H-i-s') . '.csv');
                header('X-Content-Type-Options: nosniff');

                $users_exp = $pdo->query("
                    SELECT u.id, u.email, u.commande_id, u.project_capital, u.date_inscription, u.last_budget_at,
                    l.statut as licence_statut,
                    (SELECT COUNT(*) FROM wari_expenses WHERE user_id = u.id) as nb_expenses,
                    (SELECT COUNT(*) FROM wari_debts WHERE user_id = u.id) as nb_debts
                    FROM wari_users u
                    LEFT JOIN wari_licences l ON l.commande_id = u.commande_id
                    ORDER BY u.date_inscription DESC
                ")->fetchAll(PDO::FETCH_ASSOC);

                $out = fopen('php://output', 'w');
                fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($out, ['ID', 'Email', 'Code Accès', 'Capital', 'Inscription', 'Dernière activité', 'Statut', 'Dépenses', 'Dettes'], ';');

                foreach ($users_exp as $row) {
                    fputcsv($out, [
                        $row['id'],
                        $row['email'],
                        $row['commande_id'],
                        $row['project_capital'],
                        $row['date_inscription'],
                        $row['last_budget_at'],
                        $row['licence_statut'],
                        $row['nb_expenses'],
                        $row['nb_debts']
                    ], ';');
                }
                fclose($out);
                auditLog('EXPORT_CSV', ['admin' => $adminId, 'records' => count($users_exp)]);
                exit;

            default:
                jsonResponse(false, ['msg' => 'Action inconnue'], 404);
        }
    } catch (InvalidArgumentException $e) {
        jsonResponse(false, ['msg' => $e->getMessage()], 400);
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        auditLog('ERROR', ['action' => $_GET['action'] ?? 'unknown', 'error' => $e->getMessage()]);
        jsonResponse(false, ['msg' => 'Erreur serveur'], 500);
    }
}

// ============================================
// DONNÉES POUR L'AFFICHAGE (protégé)
// ============================================
$users = [];
$csrfToken = csrfToken();

if ($_SESSION['is_admin'] ?? false) {
    requireAuth();
    $users = $pdo->query("
        SELECT u.id, u.email, u.commande_id, u.project_capital, u.date_inscription, u.last_budget_at,
        l.statut as licence_statut,
        (SELECT COUNT(*) FROM wari_expenses WHERE user_id = u.id) as nb_expenses,
        (SELECT COUNT(*) FROM wari_debts WHERE user_id = u.id) as nb_debts
        FROM wari_users u
        LEFT JOIN wari_licences l ON l.commande_id = u.commande_id
        ORDER BY u.last_budget_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
}
?>


<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <title>WARI — Admin Console (Sécurisé)</title>
    <style>
        /* [Votre CSS inchangé] */
        :root {
            --gold: #F5A623;
            --gold-dim: rgba(245, 166, 35, 0.12);
            --gold-border: rgba(245, 166, 35, 0.3);
            --bg: #080B10;
            --surface: #0D1117;
            --surface2: #161B24;
            --border: rgba(255, 255, 255, 0.06);
            --text: #E8EAF0;
            --muted: #556070;
            --red: #F56565;
            --red-dim: rgba(245, 101, 101, 0.12);
            --green: #48BB78;
            --green-dim: rgba(72, 187, 120, 0.12);
            --blue: #63B3ED;
            --blue-dim: rgba(99, 179, 237, 0.12);
            --orange: #ED8936;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'IBM Plex Mono', monospace;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            font-size: 13px;
            padding: 15px;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: linear-gradient(rgba(245, 166, 35, 0.025) 1px, transparent 1px), linear-gradient(90deg, rgba(245, 166, 35, 0.025) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
            z-index: 0;
        }

        .login-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 1;
        }

        .login-box {
            width: 360px;
            background: var(--surface);
            border: 1px solid var(--gold-border);
            border-radius: 20px;
            padding: 44px;
            box-shadow: 0 0 80px rgba(245, 166, 35, 0.07);
        }

        .login-logo {
            font-family: 'Rajdhani', sans-serif;
            font-size: 42px;
            font-weight: 800;
            color: var(--gold);
            letter-spacing: -1px;
        }

        .login-sub {
            font-size: 11px;
            letter-spacing: 4px;
            color: var(--muted);
            text-transform: uppercase;
            margin-bottom: 36px;
            margin-top: 2px;
        }

        .login-box input {
            width: 100%;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px 16px;
            color: var(--text);
            font-family: 'IBM Plex Mono', monospace;
            font-size: 13px;
            outline: none;
            margin-bottom: 12px;
            transition: border-color 0.2s;
        }

        .login-box input:focus {
            border-color: var(--gold-border);
        }

        .btn-gold {
            width: 100%;
            padding: 15px;
            background: var(--gold);
            color: #000;
            border: none;
            border-radius: 12px;
            font-family: 'Rajdhani', sans-serif;
            font-size: 15px;
            font-weight: 800;
            letter-spacing: 3px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-gold:hover {
            background: #ffbe3d;
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(245, 166, 35, 0.3);
        }

        .login-error {
            background: var(--red-dim);
            border: 1px solid rgba(245, 101, 101, 0.3);
            color: var(--red);
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 12px;
            margin-bottom: 14px;
        }

        .admin-wrap {
            position: relative;
            z-index: 1;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 28px;
            border-bottom: 1px solid var(--border);
            background: rgba(8, 11, 16, 0.95);
            backdrop-filter: blur(12px);
            position: sticky;
            top: 0;
            z-index: 200;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .topbar-logo {
            font-family: 'Rajdhani', sans-serif;
            font-size: 24px;
            font-weight: 800;
            color: var(--gold);
            letter-spacing: -1px;
        }

        .topbar-badge {
            font-size: 10px;
            letter-spacing: 3px;
            color: var(--muted);
            text-transform: uppercase;
            padding: 4px 10px;
            border: 1px solid var(--border);
            border-radius: 4px;
        }

        .topbar-right {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn-sm {
            padding: 7px 16px;
            border-radius: 8px;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 11px;
            letter-spacing: 1px;
            text-transform: uppercase;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.15s;
        }

        .btn-logout {
            background: var(--red-dim);
            border-color: rgba(245, 101, 101, 0.3);
            color: var(--red);
        }

        .btn-logout:hover {
            background: rgba(245, 101, 101, 0.22);
        }

        .btn-export {
            background: var(--green-dim);
            border-color: rgba(72, 187, 120, 0.3);
            color: var(--green);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        .btn-export:hover {
            background: rgba(72, 187, 120, 0.22);
        }

        .main-content {
            padding: 5px;
            max-width: 1280px;
            margin: 0 auto;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }

        .search-container {
            margin-bottom: 24px;
            position: relative;
        }

        .search-input {
            width: 100%;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px 20px 16px 48px;
            color: var(--text);
            font-family: 'IBM Plex Mono', monospace;
            font-size: 14px;
            outline: none;
            transition: all 0.2s;
        }

        .search-input:focus {
            border-color: var(--gold-border);
            box-shadow: 0 0 0 3px var(--gold-dim);
        }

        .search-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 16px;
        }

        .quick-filters {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 11px;
            letter-spacing: 1px;
            text-transform: uppercase;
            cursor: pointer;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--muted);
            transition: all 0.15s;
        }

        .filter-btn:hover,
        .filter-btn.active {
            border-color: var(--gold-border);
            color: var(--gold);
            background: var(--gold-dim);
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px 18px;
            transition: all 0.2s;
        }

        .stat-card:hover {
            border-color: var(--gold-border);
            transform: translateY(-2px);
        }

        .stat-label {
            font-size: 10px;
            letter-spacing: 2px;
            color: var(--muted);
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .stat-value {
            font-family: 'Rajdhani', sans-serif;
            font-size: 34px;
            font-weight: 800;
            color: var(--gold);
            line-height: 1;
        }

        .stat-value.green {
            color: var(--green);
        }

        .stat-value.blue {
            color: var(--blue);
        }

        .stat-value.red {
            color: var(--red);
        }

        .stat-value.orange {
            color: var(--orange);
        }

        .stat-value.purple {
            color: #9F7AEA;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .section-title {
            font-family: 'Rajdhani', sans-serif;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: var(--gold);
        }

        .section-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .table-wrap {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 36px;
        }

        .user-row {
            display: grid;
            grid-template-columns: 1.8fr 110px 60px 60px 130px 60px 230px;
            align-items: center;
            padding: 13px 20px;
            border-bottom: 1px solid var(--border);
            transition: background 0.15s;
            gap: 12px;
        }

        .user-row:last-child {
            border-bottom: none;
        }

        .user-row:hover {
            background: rgba(255, 255, 255, 0.015);
        }

        .user-row.header {
            background: var(--surface2);
            font-size: 10px;
            letter-spacing: 2px;
            color: var(--muted);
            text-transform: uppercase;
            padding: 10px 20px;
        }

        .user-email {
            font-size: 15px;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-id {
            font-size: 10px;
            color: var(--muted);
            margin-top: 2px;
        }

        .user-capital {
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700;
            font-size: 16px;
            color: var(--green);
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 13px;
            letter-spacing: 1px;
            text-transform: uppercase;
            font-weight: 700;
        }

        .badge-active {
            background: var(--green-dim);
            color: var(--green);
        }

        .badge-inactive {
            background: var(--surface2);
            color: var(--muted);
        }

        .badge-suspended {
            background: var(--red-dim);
            color: var(--red);
        }

        .last-seen {
            font-size: 11px;
            color: var(--muted);
            line-height: 1.6;
        }

        .actions-wrap {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 4px 9px;
            border-radius: 6px;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 13px;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.15s;
            white-space: nowrap;
        }

        .btn-red {
            background: var(--red-dim);
            border-color: rgba(245, 101, 101, 0.25);
            color: var(--red);
        }

        .btn-red:hover {
            background: rgba(245, 101, 101, 0.25);
        }

        .btn-blue {
            background: var(--blue-dim);
            border-color: rgba(99, 179, 237, 0.25);
            color: var(--blue);
        }

        .btn-blue:hover {
            background: rgba(99, 179, 237, 0.25);
        }

        .btn-amber {
            background: var(--gold-dim);
            border-color: var(--gold-border);
            color: var(--gold);
        }

        .btn-amber:hover {
            background: rgba(245, 166, 35, 0.22);
        }

        .btn-green {
            background: var(--green-dim);
            border-color: rgba(72, 187, 120, 0.25);
            color: var(--green);
        }

        .btn-green:hover {
            background: rgba(72, 187, 120, 0.22);
        }

        .btn-orange {
            background: rgba(237, 137, 54, 0.12);
            border-color: rgba(237, 137, 54, 0.3);
            color: var(--orange);
        }

        .btn-orange:hover {
            background: rgba(237, 137, 54, 0.22);
        }

        .licences-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 10px;
            margin-bottom: 36px;
        }

        .licence-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: border-color 0.2s;
        }

        .licence-card:hover {
            border-color: var(--gold-border);
        }

        .licence-code {
            font-family: 'Rajdhani', sans-serif;
            font-weight: 800;
            font-size: 13px;
            color: var(--gold);
            letter-spacing: 1px;
        }

        .licence-email {
            font-size: 10px;
            color: var(--muted);
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 140px;
        }

        .lic-status {
            font-size: 10px;
            padding: 3px 8px;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            white-space: nowrap;
        }

        .lic-disponible {
            background: var(--green-dim);
            color: var(--green);
        }

        .lic-utilise {
            background: var(--surface2);
            color: var(--muted);
        }

        .lic-suspendu {
            background: var(--red-dim);
            color: var(--red);
        }

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(6px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal-box {
            background: var(--surface);
            border: 1px solid var(--gold-border);
            border-radius: 20px;
            padding: 10px;
            max-width: 420px;
            width: 92%;
            box-shadow: 0 0 60px rgba(245, 166, 35, 0.08);
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-title {
            font-family: 'Rajdhani', sans-serif;
            font-size: 18px;
            font-weight: 800;
            color: var(--gold);
            margin-bottom: 6px;
        }

        .modal-sub {
            font-size: 11px;
            color: var(--muted);
            margin-bottom: 24px;
            line-height: 1.6;
        }

        .modal-input {
            width: 100%;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px;
            color: var(--text);
            font-family: 'IBM Plex Mono', monospace;
            font-size: 13px;
            outline: none;
            resize: vertical;
            min-height: 80px;
            margin-bottom: 16px;
            transition: border-color 0.2s;
        }

        .modal-input:focus {
            border-color: var(--gold-border);
        }

        .modal-actions {
            display: flex;
            gap: 10px;
        }

        .modal-actions button {
            flex: 1;
            padding: 13px;
            border-radius: 10px;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 11px;
            letter-spacing: 1px;
            text-transform: uppercase;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }

        .btn-cancel-m {
            background: var(--surface2);
            color: var(--muted);
        }

        .btn-confirm-m {
            background: var(--gold);
            color: #000;
            font-weight: 700;
        }

        .btn-confirm-m:hover {
            background: #ffbe3d;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 20px;
        }

        .detail-item {
            background: var(--surface2);
            border-radius: 10px;
            padding: 12px 14px;
        }

        .detail-label {
            font-size: 10px;
            color: var(--muted);
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .detail-value {
            font-family: 'Rajdhani', sans-serif;
            font-size: 18px;
            font-weight: 800;
            color: var(--text);
        }

        .detail-value.gold {
            color: var(--gold);
        }

        .detail-value.green {
            color: var(--green);
        }

        .cat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            background: var(--surface2);
            border-radius: 8px;
            margin-bottom: 6px;
        }

        .cat-name {
            font-size: 12px;
            color: var(--text);
        }

        .cat-balance {
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700;
            font-size: 14px;
            color: var(--green);
        }

        .cat-percent {
            font-size: 10px;
            color: var(--muted);
        }

        #confirmModal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(4px);
            z-index: 1100;
            align-items: center;
            justify-content: center;
        }

        #confirmModal.show {
            display: flex;
        }

        .confirm-box {
            background: var(--surface);
            border: 1px solid rgba(245, 101, 101, 0.3);
            border-radius: 18px;
            padding: 32px;
            max-width: 380px;
            width: 92%;
        }

        .confirm-title {
            font-family: 'Rajdhani', sans-serif;
            font-size: 18px;
            font-weight: 800;
            color: var(--red);
            margin-bottom: 10px;
        }

        .confirm-msg {
            font-size: 12px;
            color: var(--muted);
            line-height: 1.7;
            margin-bottom: 24px;
            white-space: pre-line;
        }

        .confirm-actions {
            display: flex;
            gap: 10px;
        }

        .confirm-actions button {
            flex: 1;
            padding: 13px;
            border-radius: 10px;
            font-family: 'IBM Plex Mono', monospace;
            font-size: 11px;
            letter-spacing: 1px;
            text-transform: uppercase;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }

        .btn-cancel-c {
            background: var(--surface2);
            color: var(--muted);
        }

        .btn-confirm-c {
            background: var(--red);
            color: #fff;
            font-weight: 700;
        }

        .btn-confirm-c:hover {
            background: #fc8181;
        }

        #toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            padding: 14px 22px;
            border-radius: 12px;
            font-size: 13px;
            z-index: 9999;
            transform: translateY(80px);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            pointer-events: none;
            max-width: 320px;
        }

        #toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        #toast.success {
            background: var(--green-dim);
            border: 1px solid rgba(72, 187, 120, 0.4);
            color: var(--green);
        }

        #toast.error {
            background: var(--red-dim);
            border: 1px solid rgba(245, 101, 101, 0.4);
            color: var(--red);
        }

        #toast.info {
            background: var(--gold-dim);
            border: 1px solid var(--gold-border);
            color: var(--gold);
        }



        /* NOUVEAU : Styles pour le rapport de push */
        .push-report {
            background: var(--surface2);
            border-radius: 12px;
            padding: 16px;
            margin-top: 12px;
            max-height: 300px;
            overflow-y: auto;
        }

        .push-report h4 {
            font-family: 'Rajdhani', sans-serif;
            font-size: 12px;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 10px;
            color: var(--gold);
        }

        .push-report ul {
            list-style: none;
            font-size: 11px;
        }

        .push-report li {
            padding: 4px 0;
            border-bottom: 1px solid var(--border);
        }

        .push-report li:last-child {
            border-bottom: none;
        }

        .push-success {
            color: var(--green);
        }

        .push-expired {
            color: var(--orange);
        }

        .push-failed {
            color: var(--red);
        }

        ::-webkit-scrollbar {
            width: 4px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 2px;
        }

        @media(max-width:900px) {
            .user-row {
                grid-template-columns: 1fr;
                gap: 8px;
                padding: 16px;
            }

            .user-row.header {
                display: none;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .topbar-right {
                gap: 6px;
            }

            .btn-sm {
                padding: 6px 10px;
                font-size: 10px;
            }
        }
    </style>
</head>

<body>
    <?php if (!($_SESSION['is_admin'] ?? false)): ?>
        <div class="login-wrap">
            <div class="login-box">
                <div class="login-logo">WARI</div>
                <div class="login-sub">Console Admin (Sécurisée)</div>
                <?php if (isset($loginError)): ?>
                    <div class="login-error"><?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="password" name="admin_pass" placeholder="Mot de passe admin" autofocus autocomplete="current-password">
                    <button type="submit" class="btn-gold">Accéder →</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="admin-wrap">
            <div class="topbar">
                <div class="topbar-left">
                    <span class="topbar-logo">WARI</span>
                    <span class="topbar-badge">Admin Console</span>
                    <span style="font-size:10px;color:var(--muted);padding:4px 10px;border:1px solid var(--border);border-radius:4px;">🔒 Sécurisé</span>
                </div>
                <div class="topbar-right">
                    <a href="?action=export_csv&csrf_token=<?= urlencode($csrfToken) ?>" class="btn-sm btn-export">⬇ CSV</a>
                    <button class="btn-sm" style="background:rgba(99,179,237,0.12);border-color:rgba(99,179,237,0.3);color:var(--blue);" onclick="openPushModal()">📣 Push</button>
                    <form method="POST" style="margin:0">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" name="admin_logout" class="btn-sm btn-logout">Quitter</button>
                    </form>
                </div>
            </div>
            <div class="main-content">
                <!-- STATS -->
                <div class="section-header" style="margin-bottom:14px;">
                    <div class="section-title">📊 Vue Globale</div>
                    <button class="btn-action btn-blue" onclick="refreshStats()">↻ Actualiser</button>
                </div>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Utilisateurs</div>
                        <div class="stat-value" id="stat-users"><?= count($users) ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Actifs aujourd'hui</div>
                        <div class="stat-value green" id="stat-today">—</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Actifs 7 jours</div>
                        <div class="stat-value blue" id="stat-week">—</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Abonnés Push</div>
                        <div class="stat-value purple" id="stat-push">—</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Licences libres</div>
                        <div class="stat-value" id="stat-licences">—</div>
                    </div>
                </div>
                <!-- USERS -->
                <div class="section-header">
                    <div class="section-title">👥 Utilisateurs</div>
                    <span style="font-size:11px;color:var(--muted)"><span id="user-count"><?= count($users) ?></span> comptes</span>
                </div>

                <!-- BARRE DE RECHERCHE -->
                <div class="search-container">
                    <div class="search-icon">🔍</div>
                    <input type="text" class="search-input" id="searchInput" placeholder="Rechercher par email, ID ou code d'accès... (ex: jean, WARI-26, 154)">
                </div>

                <!-- FILTRES RAPIDES -->
                <div class="quick-filters">
                    <button class="filter-btn" data-filter="all">Tous</button>
                    <button class="filter-btn" data-filter="active-today">Actifs aujourd'hui</button>
                    <button class="filter-btn" data-filter="inactive-7d">Inactifs 7j</button>
                    <button class="filter-btn" data-filter="suspended">Suspendus</button>
                    <button class="filter-btn" data-filter="capital-high">Capital >50K</button>
                </div>
                <div class="table-wrap">
                    <div class="user-row header">
                        <span>Email / Code</span><span>Capital</span><span>Dép.</span><span>Dettes</span><span>Activité</span><span>Statut</span><span>Actions</span>
                    </div>
                    <?php foreach ($users as $user):
                        $lastSeen = $user['last_budget_at'] ? new DateTime($user['last_budget_at']) : null;
                        $diff = $lastSeen ? (new DateTime())->diff($lastSeen) : null;
                        $isActive = $diff && $diff->days < 7;
                        $lastSeenStr = $lastSeen ? $lastSeen->format('d/m/y H:i') : 'Jamais';
                        $isSuspended = ($user['licence_statut'] ?? '') === 'suspendu';
                    ?>
                        <div class="user-row">
                            <div>
                                <div class="user-email"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="user-id">#<?= $user['id'] ?> · <?= htmlspecialchars($user['commande_id'] ?? '—', ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div class="user-capital"><?= number_format((float)$user['project_capital']) ?> F</div>
                            <div><span class="badge <?= $user['nb_expenses'] > 0 ? 'badge-active' : 'badge-inactive' ?>"><?= $user['nb_expenses'] ?></span></div>
                            <div><span class="badge <?= $user['nb_debts'] > 0 ? 'badge-active' : 'badge-inactive' ?>"><?= $user['nb_debts'] ?></span></div>
                            <div class="last-seen">
                                <span class="badge <?= $isActive ? 'badge-active' : 'badge-inactive' ?>" style="margin-bottom:3px;display:inline-block"><?= $isActive ? '● Actif' : '○ Inactif' ?></span>
                                <div><?= $lastSeenStr ?></div>
                            </div>
                            <div><span class="badge <?= $isSuspended ? 'badge-suspended' : 'badge-active' ?>"><?= $isSuspended ? '🔒' : '✓' ?></span></div>
                            <div class="actions-wrap">
                                <button class="btn-action btn-blue" onclick="openDetail(<?= $user['id'] ?>)">👁</button>
                                <button class="btn-action <?= $isSuspended ? 'btn-green' : 'btn-orange' ?>" onclick="toggleBlock(<?= $user['id'] ?>)"><?= $isSuspended ? '🔓' : '🔒' ?></button>
                                <button class="btn-action btn-red" onclick="confirmAction(<?= $user['id'] ?>, 'reset_full', '<?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?>')">🗑 Vider</button>
                                <button class="btn-action btn-amber" onclick="doAction(<?= $user['id'] ?>, 'reset_expenses')" title="Effacer dépenses">💸</button>
                                <button class="btn-action btn-blue" onclick="doAction(<?= $user['id'] ?>, 'reset_capital')" title="Reset capital">💎</button>
                                <button class="btn-action btn-green" onclick="doAction(<?= $user['id'] ?>, 'reset_balances')" title="Reset soldes">⚖</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <!-- LICENCES -->
                <div class="section-header">
                    <div class="section-title">🔑 Licences d'accès</div>
                    <div class="section-actions">
                        <button class="btn-action btn-green" onclick="generateLicence('WARI-26')">+ WARI-26</button>
                        <button class="btn-action btn-amber" onclick="generateLicence('GIFT-WARI')">+ GIFT</button>
                        <button class="btn-action btn-blue" onclick="loadLicences()">↻</button>
                    </div>
                </div>
                <div class="licences-grid" id="licencesGrid">
                    <div style="font-size:11px;color:var(--muted);padding:20px;">Chargement...</div>
                </div>

            </div>
        </div>
        <!-- CONFIRM -->
        <div id="confirmModal">
            <div class="confirm-box">
                <div class="confirm-title">⚠️ Confirmation requise</div>
                <div class="confirm-msg" id="confirmMsg"></div>
                <div class="confirm-actions">
                    <button class="btn-cancel-c" onclick="closeConfirm()">Annuler</button>
                    <button class="btn-confirm-c" id="confirmBtn">Confirmer</button>
                </div>
            </div>
        </div>
        <!-- PUSH MODAL -->
        <div class="modal-overlay" id="pushModal">
            <div class="modal-box" style="max-width: 520px;">
                <div class="flex items-center gap-3 mb-1">
                    <div style="width:40px;height:40px;border-radius:12px;background:rgba(245,166,35,0.1);border:1px solid rgba(245,166,35,0.2);display:flex;align-items:center;justify-content:center;font-size:18px;">📣</div>
                    <div>
                        <div class="modal-title">Notification Push</div>
                        <p style="font-size:10px;color:#556070;letter-spacing:3px;text-transform:uppercase;">Tous les abonnés Wari</p>
                    </div>
                </div>
                <div style="height:1px;background:rgba(255,255,255,0.05);margin:16px 0;"></div>
                <label style="display:block;font-size:9px;letter-spacing:3px;text-transform:uppercase;color:#556070;margin-bottom:8px;">✍️ Message</label>
                <textarea class="modal-input" id="pushMessage" placeholder="Bon mardi Leader ! 🔥..."></textarea>
                <label style="display:block;font-size:9px;letter-spacing:3px;text-transform:uppercase;color:#556070;margin-bottom:8px;">🔗 Lien (optionnel)</label>
                <div style="margin-bottom:6px;">
                    <input type="url" id="pushUrl" placeholder="https://wari.digiroys.com/avis.php" class="modal-input" style="min-height:unset;padding-top:12px;padding-bottom:12px;">
                </div>
                <p style="font-size:9px;color:#3a4555;margin-bottom:20px;margin-left:4px;">Laisse vide pour ouvrir l'app normalement</p>

                <!-- NOUVEAU : Zone d'affichage du rapport -->
                <div id="pushReportContainer" style="display:none;">
                    <div class="push-report" id="pushReportContent"></div>
                </div>

                <div class="modal-actions">
                    <button class="btn-cancel-m" onclick="closePushModal()">Annuler</button>
                    <button class="btn-confirm-m" onclick="sendPush()">Envoyer 🚀</button>
                </div>
            </div>
        </div>
        <!-- DETAIL MODAL -->
        <div class="modal-overlay" id="detailModal">
            <div class="modal-box" style="max-width:520px;">
                <div class="modal-title" id="detailTitle">Détail</div>
                <div class="modal-sub" id="detailEmail"></div>
                <div id="detailContent" style="max-height:60vh;overflow-y:auto;">Chargement...</div>
                <div style="margin-top:20px;">
                    <button onclick="closeDetail()" style="width:100%;padding:13px;border-radius:10px;font-family:'IBM Plex Mono',monospace;font-size:11px;letter-spacing:1px;text-transform:uppercase;cursor:pointer;border:none;background:var(--surface2);color:var(--muted);">Fermer</button>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <div id="toast"></div>
    <script>
        const BASE = window.location.pathname;
        const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';

        function showToast(msg, type = 'success') {
            const t = document.getElementById('toast');
            const styles = {
                success: 'bg-emerald-500/10 border border-emerald-500/30 text-emerald-400',
                error: 'bg-red-500/10 border border-red-500/30 text-red-400',
                info: 'bg-yellow-500/10 border border-yellow-500/30 text-yellow-400',
            };
            t.className = `fixed bottom-6 right-6 px-5 py-3.5 rounded-xl text-sm font-mono z-[9999] max-w-xs transition-all duration-300 ${styles[type]}`;
            t.textContent = msg;
            t.style.opacity = '1';
            t.style.transform = 'translateY(0)';
            setTimeout(() => {
                t.style.opacity = '0';
                t.style.transform = 'translateY(20px)';
            }, 3500);
        }

        function confirmAction(userId, action, email) {
            document.getElementById('confirmMsg').innerText = `Vider TOUTE la mémoire de :\n${email}\n\nBalances · Dépenses · Capital · Coffre\n\nIrréversible.`;
            document.getElementById('confirmModal').classList.add('show');
            document.getElementById('confirmBtn').onclick = () => {
                closeConfirm();
                doAction(userId, action);
            };
        }

        function closeConfirm() {
            document.getElementById('confirmModal').classList.remove('show');
        }

        async function doAction(userId, action) {
            try {
                const res = await fetch(`${BASE}?action=${action}&user_id=${userId}&csrf_token=${encodeURIComponent(CSRF_TOKEN)}`);
                const data = await res.json();
                showToast(data.success ? `✅ ${data.msg}` : `❌ ${data.msg}`, data.success ? 'success' : 'error');
                if (data.success) setTimeout(() => location.reload(), 1500);
            } catch (e) {
                showToast('❌ Erreur réseau', 'error');
            }
        }

        async function toggleBlock(userId) {
            try {
                const res = await fetch(`${BASE}?action=toggle_block&user_id=${userId}&csrf_token=${encodeURIComponent(CSRF_TOKEN)}`);
                const data = await res.json();
                showToast(data.success ? `✅ ${data.msg}` : `❌ ${data.msg}`, data.success ? 'success' : 'error');
                if (data.success) setTimeout(() => location.reload(), 1200);
            } catch (e) {
                showToast('❌ Erreur réseau', 'error');
            }
        }

        async function refreshStats() {
            showToast('↻ Actualisation...', 'info');
            try {
                const res = await fetch(`${BASE}?action=get_stats&csrf_token=${encodeURIComponent(CSRF_TOKEN)}`);
                const data = await res.json();
                if (data.success) {
                    const s = data.stats;
                    console.log('Stats reçues:', s); // DEBUG
                    document.getElementById('stat-users').innerText = s.total_users;
                    document.getElementById('stat-today').innerText = s.active_today;
                    document.getElementById('stat-week').innerText = s.active_week;
                    document.getElementById('stat-push').innerText = s.push_subscribers || '0';
                    document.getElementById('stat-licences').innerText = s.licences_dispo + ' / ' + s.licences_total;
                    showToast('✅ Stats mises à jour', 'success');
                }
            } catch (e) {
                showToast('❌ Erreur réseau', 'error');
            }
        }

        async function loadLicences() {
            try {
                const res = await fetch(`${BASE}?action=get_licences&csrf_token=${encodeURIComponent(CSRF_TOKEN)}`);
                const data = await res.json();
                const grid = document.getElementById('licencesGrid');
                if (!data.licences || data.licences.length === 0) {
                    grid.innerHTML = '<div style="font-size:11px;color:var(--muted);padding:20px;">Aucune licence.</div>';
                    return;
                }
                grid.innerHTML = data.licences.map(l => `
                    <div class="licence-card">
                        <div>
                            <div class="licence-code">${l.commande_id}</div>
                            <div class="licence-email">${l.email || '—'}</div>
                        </div>
                        <span class="lic-status lic-${l.statut}">${l.statut}</span>
                    </div>
                `).join('');
            } catch (e) {
                document.getElementById('licencesGrid').innerHTML = '<div style="font-size:11px;color:var(--muted);padding:20px;">Erreur.</div>';
            }
        }

        async function generateLicence(prefix) {
            try {
                const res = await fetch(`${BASE}?action=add_licence&prefix=${encodeURIComponent(prefix)}&csrf_token=${encodeURIComponent(CSRF_TOKEN)}`);
                const data = await res.json();
                if (data.success) {
                    showToast(`✅ ${data.code}`, 'success');
                    loadLicences();
                    refreshStats();
                } else showToast('❌ ' + (data.msg || 'Erreur'), 'error');
            } catch (e) {
                showToast('❌ Erreur réseau', 'error');
            }
        }

        function openPushModal() {
            document.getElementById('pushModal').classList.add('show');
            // Cacher le rapport précédent
            document.getElementById('pushReportContainer').style.display = 'none';
            document.getElementById('pushReportContent').innerHTML = '';
        }

        function closePushModal() {
            const btn = document.querySelector('#pushModal .btn-confirm-m');
            if (btn && btn.disabled) return;
            document.getElementById('pushModal').classList.remove('show');
        }

        async function sendPush() {
            const msg = document.getElementById('pushMessage').value.trim();
            const url = document.getElementById('pushUrl').value.trim();
            if (!msg) return showToast('⚠️ Message vide', 'error');

            const btn = document.querySelector('#pushModal .btn-confirm-m');
            btn.disabled = true;
            btn.textContent = '⏳ Envoi en cours...';
            btn.style.opacity = '0.6';
            btn.style.cursor = 'not-allowed';

            try {
                const res = await fetch(`${BASE}?action=push_all&message=${encodeURIComponent(msg)}&url=${encodeURIComponent(url)}&csrf_token=${encodeURIComponent(CSRF_TOKEN)}`);
                const data = await res.json();

                if (data.success) {
                    showToast('✅ Push envoyé !', 'success');

                    // Afficher le rapport détaillé
                    displayPushReport(data.details, data.stats);

                    // Vider les champs
                    document.getElementById('pushMessage').value = '';
                    document.getElementById('pushUrl').value = '';
                } else {
                    showToast(`❌ ${data.msg}`, 'error');
                }
            } catch (e) {
                showToast('❌ Erreur réseau', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Envoyer 🚀';
                btn.style.opacity = '1';
                btn.style.cursor = 'pointer';
            }
        }

        // NOUVEAU : Fonction d'affichage du rapport de push
        function displayPushReport(details, stats) {
            const container = document.getElementById('pushReportContainer');
            const content = document.getElementById('pushReportContent');

            let html = `
                <h4>📊 Résultats : ${stats.success}/${stats.total} reçus</h4>
            `;

            // Succès
            if (details.success && details.success.length > 0) {
                html += `<div style="margin-bottom:12px;"><strong style="color:var(--green);">✅ Reçus (${details.success.length})</strong><ul>`;
                details.success.forEach(u => {
                    html += `<li class="push-success">${u.email}</li>`;
                });
                html += '</ul></div>';
            }

            // Expirés
            if (details.expired && details.expired.length > 0) {
                html += `<div style="margin-bottom:12px;"><strong style="color:var(--orange);">💀 Expirés (${details.expired.length})</strong><ul>`;
                details.expired.forEach(u => {
                    html += `<li class="push-expired">${u.email}</li>`;
                });
                html += '</ul></div>';
            }

            // Échecs
            if (details.failed && details.failed.length > 0) {
                html += `<div><strong style="color:var(--red);">❌ Échecs (${details.failed.length})</strong><ul>`;
                details.failed.forEach(u => {
                    html += `<li class="push-failed">${u.email} <span style="color:var(--muted);">(${u.reason})</span></li>`;
                });
                html += '</ul></div>';
            }

            content.innerHTML = html;
            container.style.display = 'block';
        }

        async function openDetail(userId) {
            document.getElementById('detailModal').classList.add('show');
            document.getElementById('detailContent').innerHTML = '<div style="text-align:center;padding:30px;color:var(--muted)">Chargement...</div>';
            try {
                const res = await fetch(`${BASE}?action=get_user_detail&user_id=${userId}&csrf_token=${encodeURIComponent(CSRF_TOKEN)}`);
                const data = await res.json();
                if (!data.success) {
                    document.getElementById('detailContent').innerHTML = '<p style="color:var(--red)">Erreur</p>';
                    return;
                }
                const u = data.user;
                const cats = (u.budget_parsed?.categories) || [];
                document.getElementById('detailTitle').innerText = `#${u.id} — Profil`;
                document.getElementById('detailEmail').innerText = u.email;
                document.getElementById('detailContent').innerHTML = `
                    <div class="detail-grid">
                        <div class="detail-item"><div class="detail-label">Capital</div><div class="detail-value green">${parseInt(u.project_capital).toLocaleString('fr-FR')} F</div></div>
                        <div class="detail-item"><div class="detail-label">Total dépensé</div><div class="detail-value">${parseInt(u.total_spent||0).toLocaleString('fr-FR')} F</div></div>
                        <div class="detail-item"><div class="detail-label">Mouvements coffre</div><div class="detail-value gold">${u.nb_vault_ops}</div></div>
                        <div class="detail-item"><div class="detail-label">Dettes actives</div><div class="detail-value">${u.nb_debts}</div></div>
                    </div>
                    <div style="font-size:10px;color:var(--muted);letter-spacing:2px;text-transform:uppercase;margin-bottom:10px;margin-top:4px;">Catégories budgétaires</div>
                    ${cats.length ? cats.map(c => `
                        <div class="cat-row">
                            <span class="cat-name">${c.icon||''} ${c.name}</span>
                            <div style="display:flex;gap:12px;align-items:center">
                                <span class="cat-percent">${c.percent}%</span>
                                <span class="cat-balance">${parseInt(c.balance||0).toLocaleString('fr-FR')} F</span>
                            </div>
                        </div>
                    `).join('') : '<div style="font-size:11px;color:var(--muted);padding:10px">Aucune donnée budget.</div>'}
                    <div style="margin-top:16px;font-size:10px;color:var(--muted)">Licence : ${u.commande_id||'—'} · Statut : ${u.licence_statut||'—'}</div>
                    <div style="font-size:10px;color:var(--muted);margin-top:4px">Inscrit le : ${u.date_inscription||'—'} · Dernière activité : ${u.last_budget_at||'—'}</div>
                `;
            } catch (e) {
                document.getElementById('detailContent').innerHTML = '<p style="color:var(--red)">Erreur réseau</p>';
            }
        }

        function closeDetail() {
            document.getElementById('detailModal').classList.remove('show');
        }

        // INIT
        refreshStats();
        loadLicences();
    </script>
    <script>
        // 🔍 BARRE DE RECHERCHE & FILTRES
        (function() {
            const searchInput = document.getElementById('searchInput');
            const userRows = document.querySelectorAll('.user-row:not(.header)');
            const userCount = document.getElementById('user-count');
            const filterBtns = document.querySelectorAll('.filter-btn');

            let currentFilter = 'all';

            // 🔍 Recherche instantanée
            searchInput.addEventListener('input', function() {
                filterUsers();
            });

            // 🎯 Filtres rapides
            filterBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    filterBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentFilter = this.dataset.filter;
                    filterUsers();
                });
            });

            // 🧠 Fonction de filtrage principale
            function filterUsers() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                let visibleCount = 0;

                userRows.forEach(row => {
                    const email = row.querySelector('.user-email')?.textContent.toLowerCase() || '';
                    const userId = row.querySelector('.user-id')?.textContent.toLowerCase() || '';
                    const commandeId = row.querySelector('.user-email')?.nextElementSibling?.textContent.toLowerCase() || '';
                    const lastActivity = row.querySelector('.last-seen')?.textContent || '';
                    const statusBadge = row.querySelector('.badge')?.textContent.toLowerCase() || '';
                    const capitalText = row.querySelector('.user-capital')?.textContent || '';

                    // 🔍 Test recherche texte
                    const matchesSearch = !searchTerm ||
                        email.includes(searchTerm) ||
                        userId.includes(searchTerm) ||
                        commandeId.includes(searchTerm) ||
                        extractNumber(searchTerm) === extractNumber(commandeId);

                    // 🎯 Test filtre actif
                    const matchesFilter = checkFilter(row, lastActivity, statusBadge, capitalText);

                    // 👁️ Afficher/masquer
                    if (matchesSearch && matchesFilter) {
                        row.style.display = 'grid';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                // 📊 Mise à jour compteur
                userCount.textContent = visibleCount;
            }

            // 🎯 Vérification des filtres
            function checkFilter(row, lastActivity, statusBadge, capitalText) {
                switch (currentFilter) {
                    case 'all':
                        return true;
                    case 'active-today':
                        return lastActivity.includes('Aujourd\'hui') || lastActivity.includes('aujourd\'hui');
                    case 'inactive-7d':
                        return lastActivity.includes('Jamais') || lastActivity.includes('jours') || lastActivity.includes('semaine');
                    case 'suspended':
                        return statusBadge.includes('suspendu');
                    case 'capital-high':
                        const capital = parseInt(capitalText.replace(/[^0-9]/g, ''));
                        return capital > 50000;
                    default:
                        return true;
                }
            }

            // 🔢 Extracteur de numéros pour ID/commande
            function extractNumber(text) {
                const match = text.match(/\d+/);
                return match ? match[0] : '';
            }

            // 🚀 Initialisation
            filterBtns[0].classList.add('active'); // Activer "Tous" par défaut
        })();
    </script>

</body>

</html>
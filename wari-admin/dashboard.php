<?php
session_start();
require '../config/db.php';
require_once '../vendor/autoload.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

define('ADMIN_PASSWORD', 'wari@softiP24'); // Change ce mot de passe pour sécuriser l'accès à la consolesse admin !

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_pass'])) {
    if ($_POST['admin_pass'] === ADMIN_PASSWORD) {
        $_SESSION['is_admin'] = true;
    } else {
        $loginError = "Mot de passe incorrect.";
    }
}

if (isset($_POST['admin_logout'])) {
    unset($_SESSION['is_admin']);
}

// ============================================
// ACTIONS AJAX
// ============================================
if (isset($_GET['action']) && ($_SESSION['is_admin'] ?? false)) {
    header('Content-Type: application/json');
    $userId = intval($_GET['user_id'] ?? 0);

    switch ($_GET['action']) {

        case 'reset_full':
            try {
                $pdo->prepare("UPDATE wari_users SET
                    budget_data = JSON_SET(budget_data,
                        '$.categories[0].balance', 0,
                        '$.categories[1].balance', 0,
                        '$.categories[2].balance', 0,
                        '$.categories[3].balance', 0,
                        '$.vaultTransactions', JSON_ARRAY(),
                        '$.projectCapital', 0,
                        '$.hasDepositedToday', false
                    ), project_capital = 0 WHERE id = ?")->execute([$userId]);
                $pdo->prepare("DELETE FROM wari_expenses WHERE user_id = ?")->execute([$userId]);
                $pdo->prepare("DELETE FROM wari_vault_history WHERE user_id = ?")->execute([$userId]);
                echo json_encode(['success' => true, 'msg' => 'Mémoire complète effacée']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
            }
            exit;

        case 'reset_expenses':
            try {
                $pdo->prepare("DELETE FROM wari_expenses WHERE user_id = ?")->execute([$userId]);
                echo json_encode(['success' => true, 'msg' => 'Dépenses effacées']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
            }
            exit;

        case 'reset_capital':
            try {
                $pdo->prepare("UPDATE wari_users SET project_capital = 0,
                    budget_data = JSON_SET(budget_data, '$.projectCapital', 0, '$.vaultTransactions', JSON_ARRAY())
                    WHERE id = ?")->execute([$userId]);
                $pdo->prepare("DELETE FROM wari_vault_history WHERE user_id = ?")->execute([$userId]);
                echo json_encode(['success' => true, 'msg' => 'Capital & coffre remis à zéro']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
            }
            exit;

        case 'reset_balances':
            try {
                $pdo->prepare("UPDATE wari_users SET
                    budget_data = JSON_SET(budget_data,
                        '$.categories[0].balance', 0,
                        '$.categories[1].balance', 0,
                        '$.categories[2].balance', 0,
                        '$.categories[3].balance', 0
                    ) WHERE id = ?")->execute([$userId]);
                echo json_encode(['success' => true, 'msg' => 'Soldes catégories remis à zéro']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
            }
            exit;

        case 'reset_debts':
            try {
                $pdo->prepare("DELETE FROM wari_debts WHERE user_id = ?")->execute([$userId]);
                echo json_encode(['success' => true, 'msg' => 'Dettes effacées']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
            }
            exit;

        case 'toggle_block':
            try {
                $stmt = $pdo->prepare("SELECT commande_id FROM wari_users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$user) {
                    echo json_encode(['success' => false, 'msg' => 'User introuvable']);
                    exit;
                }

                $stmtLic = $pdo->prepare("SELECT statut FROM wari_licences WHERE commande_id = ?");
                $stmtLic->execute([$user['commande_id']]);
                $lic = $stmtLic->fetch(PDO::FETCH_ASSOC);
                if (!$lic) {
                    echo json_encode(['success' => false, 'msg' => 'Licence introuvable']);
                    exit;
                }

                $newStatut = $lic['statut'] === 'suspendu' ? 'utilise' : 'suspendu';
                $pdo->prepare("UPDATE wari_licences SET statut = ? WHERE commande_id = ?")
                    ->execute([$newStatut, $user['commande_id']]);

                $msg = $newStatut === 'suspendu' ? 'Compte suspendu' : 'Compte réactivé';
                echo json_encode(['success' => true, 'msg' => $msg, 'statut' => $newStatut]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
            }
            exit;

        case 'get_user_detail':
            try {
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
                echo json_encode(['success' => true, 'user' => $user]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
            }
            exit;

        case 'get_stats':
            try {
                $stats = [];
                $stats['total_users']    = $pdo->query("SELECT COUNT(*) FROM wari_users")->fetchColumn();
                $stats['active_today']   = $pdo->query("SELECT COUNT(*) FROM wari_users WHERE DATE(last_budget_at) = CURDATE()")->fetchColumn();
                $stats['active_week']    = $pdo->query("SELECT COUNT(*) FROM wari_users WHERE last_budget_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
                $stats['total_expenses'] = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM wari_expenses")->fetchColumn();
                $stats['total_capital']  = $pdo->query("SELECT COALESCE(SUM(project_capital),0) FROM wari_users")->fetchColumn();
                $stats['licences_dispo'] = $pdo->query("SELECT COUNT(*) FROM wari_licences WHERE statut='disponible'")->fetchColumn();
                $stats['licences_total'] = $pdo->query("SELECT COUNT(*) FROM wari_licences")->fetchColumn();
                echo json_encode(['success' => true, 'stats' => $stats]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
            }
            exit;

        case 'get_licences':
            try {
                $licences = $pdo->query("SELECT l.*, u.email FROM wari_licences l
                    LEFT JOIN wari_users u ON u.commande_id = l.commande_id
                    ORDER BY l.date_creation DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'licences' => $licences]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'licences' => []]);
            }
            exit;

        case 'add_licence':
            try {
                $prefix = $_GET['prefix'] ?? 'WARI-26';
                $suffix = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 4));
                $code = $prefix . '-' . $suffix;
                $pdo->prepare("INSERT INTO wari_licences (commande_id, statut, date_creation) VALUES (?, 'disponible', NOW())")
                    ->execute([$code]);
                echo json_encode(['success' => true, 'code' => $code]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
            }
            exit;

        case 'push_all':
            try {

                $message = urldecode($_GET['message'] ?? 'Message de Wari Finance');

                $auth = [
                    'VAPID' => [
                        'subject'    => 'mailto:info@rebonly.com',
                        'publicKey'  => 'BH9WpcuMhkSEOjnwf8KVZfDTv9Ps6nGaQ9RQ77e4D15ywgPmO7wNgTlldejjFjyWCp3PoBYareDXjlFBTdpzm40',
                        'privateKey' => '5RRIDWOg5l8uik2FAhvqvc-VXfcNupUB7JUGFOxox6c', // ← remplace ici
                    ],
                ];

                $webPush = new WebPush($auth);

                $subs = $pdo->query("SELECT endpoint, p256dh, auth FROM wari_subscriptions")
                    ->fetchAll(PDO::FETCH_ASSOC);

                $url = urldecode($_GET['url'] ?? 'https://wari.digiroys.com');

                $payload = json_encode([
                    'title' => 'Wari Finance',
                    'body'  => $message,
                    'icon'  => 'https://i.postimg.cc/NFhtHvBK/wari-logos-sfnd.png',
                    'url'   => $url, // ← ajouté
                ]);

                foreach ($subs as $sub) {
                    $webPush->queueNotification(
                        Subscription::create([
                            'endpoint'        => $sub['endpoint'],
                            'keys' => [
                                'p256dh' => $sub['p256dh'],
                                'auth'   => $sub['auth'],
                            ],
                        ]),
                        $payload
                    );
                }

                $sent = 0;
                foreach ($webPush->flush() as $report) {
                    if ($report->isSuccess()) $sent++;
                }

                echo json_encode(['success' => true, 'msg' => "Envoyé à {$sent} / " . count($subs) . " abonné(s)"]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
            }
            exit;

        case 'export_csv':
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=wari_users_' . date('Y-m-d') . '.csv');
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
            exit;
    }
}

// ============================================
// DONNÉES POUR L'AFFICHAGE
// ============================================
$users = [];
if ($_SESSION['is_admin'] ?? false) {
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
    <title>WARI — Admin Console</title>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;700&family=Rajdhani:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
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

        /* LOGIN */
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

        /* LAYOUT */
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

        /* STATS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            margin-bottom: 36px;
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

        /* SECTION */
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

        /* TABLE */
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

        /* ACTIONS */
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

        /* LICENCES */
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

        /* MODALS */
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

        /* DETAIL */
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

        /* CONFIRM */
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

        /* TOAST */
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

        .log-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 20px;
            border-bottom: 1px solid var(--border);
        }

        .log-row:last-child {
            border-bottom: none;
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
                <div class="login-sub">Console Admin</div>
                <?php if (isset($loginError)): ?>
                    <div class="login-error"><?= $loginError ?></div>
                <?php endif; ?>
                <form method="POST">
                    <input type="password" name="admin_pass" placeholder="Mot de passe admin" autofocus>
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
                </div>
                <div class="topbar-right">
                    <a href="?action=export_csv" class="btn-sm btn-export">⬇ CSV</a>
                    <button class="btn-sm" style="background:rgba(99,179,237,0.12);border-color:rgba(99,179,237,0.3);color:var(--blue);" onclick="openPushModal()">📣 Push</button>
                    <form method="POST" style="margin:0">
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
                        <div class="stat-label">Dépenses totales</div>
                        <div class="stat-value orange" id="stat-expenses">—</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Capital cumulé</div>
                        <div class="stat-value green" id="stat-capital">—</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Licences libres</div>
                        <div class="stat-value" id="stat-licences">—</div>
                    </div>
                </div>

                <!-- USERS -->
                <div class="section-header">
                    <div class="section-title">👥 Utilisateurs</div>
                    <span style="font-size:11px;color:var(--muted)"><?= count($users) ?> comptes</span>
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
                                <div class="user-email"><?= htmlspecialchars($user['email']) ?></div>
                                <div class="user-id">#<?= $user['id'] ?> · <?= htmlspecialchars($user['commande_id'] ?? '—') ?></div>
                            </div>
                            <div class="user-capital"><?= number_format($user['project_capital']) ?> F</div>
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
                                <button class="btn-action btn-red" onclick="confirmAction(<?= $user['id'] ?>, 'reset_full', '<?= htmlspecialchars($user['email']) ?>')">🗑 Vider</button>
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

                <!-- LOGS -->
                <div class="section-header">
                    <div class="section-title">📋 Dernières connexions</div>
                </div>
                <div class="table-wrap" style="margin-bottom:60px;">
                    <?php foreach (array_slice($users, 0, 15) as $user):
                        $t = $user['last_budget_at'] ? (new DateTime($user['last_budget_at']))->format('d/m/Y à H:i') : 'Jamais';
                    ?>
                        <div class="log-row">
                            <div>
                                <div style="font-size:13px;color:var(--text)"><?= htmlspecialchars($user['email']) ?></div>
                                <div style="font-size:10px;color:var(--muted)"><?= htmlspecialchars($user['commande_id'] ?? '') ?></div>
                            </div>
                            <span style="font-size:11px;color:var(--muted)"><?= $t ?></span>
                        </div>
                    <?php endforeach; ?>
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
            <div class="modal-box">

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
                    <input type="url" id="pushUrl" placeholder="https://wari.digiroys.com/avis.php"
                        class="modal-input" style="min-height:unset;padding-top:12px;padding-bottom:12px;">
                </div>
                <p style="font-size:9px;color:#3a4555;margin-bottom:20px;margin-left:4px;">Laisse vide pour ouvrir l'app normalement</p>

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
                const res = await fetch(`${BASE}?action=${action}&user_id=${userId}`);
                const data = await res.json();
                showToast(data.success ? `✅ ${data.msg}` : `❌ ${data.msg}`, data.success ? 'success' : 'error');
                if (data.success) setTimeout(() => location.reload(), 1500);
            } catch (e) {
                showToast('❌ Erreur réseau', 'error');
            }
        }

        async function toggleBlock(userId) {
            try {
                const res = await fetch(`${BASE}?action=toggle_block&user_id=${userId}`);
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
                const res = await fetch(`${BASE}?action=get_stats`);
                const data = await res.json();
                if (data.success) {
                    const s = data.stats;
                    document.getElementById('stat-users').innerText = s.total_users;
                    document.getElementById('stat-today').innerText = s.active_today;
                    document.getElementById('stat-week').innerText = s.active_week;
                    document.getElementById('stat-expenses').innerText = parseInt(s.total_expenses).toLocaleString('fr-FR') + ' F';
                    document.getElementById('stat-capital').innerText = parseInt(s.total_capital).toLocaleString('fr-FR') + ' F';
                    document.getElementById('stat-licences').innerText = s.licences_dispo + ' / ' + s.licences_total;
                    showToast('✅ Stats mises à jour', 'success');
                }
            } catch (e) {
                showToast('❌ Erreur réseau', 'error');
            }
        }

        async function loadLicences() {
            try {
                const res = await fetch(`${BASE}?action=get_licences`);
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
                const res = await fetch(`${BASE}?action=add_licence&prefix=${encodeURIComponent(prefix)}`);
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
        }

        function closePushModal() {
    const btn = document.querySelector('#pushModal .btn-confirm-m');
    // On n'autorise la fermeture que si l'envoi n'est pas en cours
    if (btn && btn.disabled) return;
    document.getElementById('pushModal').classList.remove('show');
}
        async function sendPush() {
    const msg = document.getElementById('pushMessage').value.trim();
    const url = document.getElementById('pushUrl').value.trim();
    if (!msg) return showToast('⚠️ Message vide', 'error');

    // ✅ Désactivation immédiate du bouton
    const btn = document.querySelector('#pushModal .btn-confirm-m');
    btn.disabled = true;
    btn.textContent = '⏳ Envoi en cours...';
    btn.style.opacity = '0.6';
    btn.style.cursor = 'not-allowed';

    try {
        const res = await fetch(`${BASE}?action=push_all&message=${encodeURIComponent(msg)}&url=${encodeURIComponent(url)}`);
        const data = await res.json();

        if (data.success) {
            showToast(`✅ ${data.msg}`, 'success');
            document.getElementById('pushMessage').value = '';
            document.getElementById('pushUrl').value = '';
            closePushModal();
        } else {
            showToast(`❌ ${data.msg}`, 'error');
        }

    } catch (e) {
        showToast('❌ Erreur réseau', 'error');
    } finally {
        // ✅ Réactivation du bouton dans tous les cas
        btn.disabled = false;
        btn.textContent = 'Envoyer 🚀';
        btn.style.opacity = '1';
        btn.style.cursor = 'pointer';
    }
}

        async function openDetail(userId) {
            document.getElementById('detailModal').classList.add('show');
            document.getElementById('detailContent').innerHTML = '<div style="text-align:center;padding:30px;color:var(--muted)">Chargement...</div>';
            try {
                const res = await fetch(`${BASE}?action=get_user_detail&user_id=${userId}`);
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
</body>

</html>
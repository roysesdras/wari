<?php
// /var/www/wari.digiroys.com/classes/Push.php

require_once __DIR__ . '/../vendor/autoload.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class Push
{
    private static function loadEnv()
    {
        if (isset($_ENV['VAPID_PUBLIC_KEY'])) return;

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
                    $name  = trim($name);
                    $value = trim($value, " \t\n\r\0\x0B\"'");
                    $_ENV[$name] = $value;
                    putenv("$name=$value");
                }
                break;
            }
        }
    }

    public static function sendToAll(PDO $pdo, string $title, string $body, string $url, string $type = null, string $target_id = null)
    {
        self::loadEnv();

        $logId = null;
        if ($type !== null && $target_id !== null) {
            try {
                $stmt = $pdo->prepare("INSERT INTO wari_push_logs (type, target_id, title, sent_count) VALUES (?, ?, ?, 0)");
                $stmt->execute([$type, $target_id, $title]);
                $logId = $pdo->lastInsertId();
            } catch (Exception $e) {
                error_log("Erreur d'insertion dans wari_push_logs : " . $e->getMessage());
            }
        }

        if ($logId) {
            $connector = (strpos($url, '?') === false) ? '?' : '&';
            $url .= $connector . 'push_log_id=' . $logId;
        }

        $vapidConfig = [
            'VAPID' => [
                'subject'    => $_ENV['VAPID_SUBJECT'] ?? 'mailto:info@rebonly.com',
                'publicKey'  => $_ENV['VAPID_PUBLIC_KEY'] ?? 'BH9WpcuMhkSEOjnwf8KVZfDTv9Ps6nGaQ9RQ77e4D15ywgPmO7wNgTlldejjFjyWCp3PoBYareDXjlFBTdpzm40',
                'privateKey' => $_ENV['VAPID_PRIVATE_KEY'] ?? '5RRIDWOg5l8uik2FAhvqvc-VXfcNupUB7JUGFOxox6c',
            ]
        ];

        try {
            $webPush = new WebPush($vapidConfig);

            // Récupérer tous les abonnements push
            $subs = $pdo->query("SELECT endpoint, p256dh, auth FROM wari_subscriptions")
                         ->fetchAll(PDO::FETCH_ASSOC);

            if (empty($subs)) {
                return ['success' => true, 'recipients' => 0];
            }

            $payload = json_encode([
                'title' => $title,
                'body'  => $body,
                'icon'  => 'https://i.postimg.cc/x80KpBqW/warifinance3d.png',
                'badge' => 'https://i.postimg.cc/x80KpBqW/warifinance3d.png',
                'url'   => $url,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            foreach ($subs as $sub) {
                if (empty($sub['endpoint']) || empty($sub['p256dh']) || empty($sub['auth'])) {
                    continue;
                }

                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $sub['endpoint'],
                        'keys' => ['p256dh' => $sub['p256dh'], 'auth' => $sub['auth']],
                    ]),
                    $payload
                );
            }

            $expiredEndpoints = [];
            $successCount = 0;
            $index = 0;

            foreach ($webPush->flush() as $report) {
                if ($report->isSuccess()) {
                    $successCount++;
                } elseif ($report->isSubscriptionExpired()) {
                    // Supprimer l'abonnement expiré
                    if (isset($subs[$index]['endpoint'])) {
                        $expiredEndpoints[] = $subs[$index]['endpoint'];
                    }
                }
                $index++;
            }

            if (!empty($expiredEndpoints)) {
                $expiredEndpoints = array_filter($expiredEndpoints);
                if (!empty($expiredEndpoints)) {
                    $placeholders = implode(',', array_fill(0, count($expiredEndpoints), '?'));
                    $pdo->prepare("DELETE FROM wari_subscriptions WHERE endpoint IN ($placeholders)")
                        ->execute($expiredEndpoints);
                }
            }

            if ($logId) {
                try {
                    $stmt = $pdo->prepare("UPDATE wari_push_logs SET sent_count = ? WHERE id = ?");
                    $stmt->execute([$successCount, $logId]);
                } catch (Exception $e) {
                    error_log("Erreur de mise à jour wari_push_logs : " . $e->getMessage());
                }
            }

            return ['success' => true, 'recipients' => $successCount];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function sendToUser(PDO $pdo, int $userId, string $title, string $body, string $url, string $type = 'coach_proactive_alert')
    {
        self::loadEnv();

        $logId = null;
        try {
            $stmt = $pdo->prepare("INSERT INTO wari_push_logs (type, target_id, title, sent_count) VALUES (?, ?, ?, 0)");
            $stmt->execute([$type, (string)$userId, $title]);
            $logId = $pdo->lastInsertId();
        } catch (Exception $e) {
            error_log("Erreur d'insertion dans wari_push_logs : " . $e->getMessage());
        }

        if ($logId) {
            $connector = (strpos($url, '?') === false) ? '?' : '&';
            $url .= $connector . 'push_log_id=' . $logId;
        }

        $vapidConfig = [
            'VAPID' => [
                'subject'    => $_ENV['VAPID_SUBJECT'] ?? 'mailto:info@rebonly.com',
                'publicKey'  => $_ENV['VAPID_PUBLIC_KEY'] ?? 'BH9WpcuMhkSEOjnwf8KVZfDTv9Ps6nGaQ9RQ77e4D15ywgPmO7wNgTlldejjFjyWCp3PoBYareDXjlFBTdpzm40',
                'privateKey' => $_ENV['VAPID_PRIVATE_KEY'] ?? '5RRIDWOg5l8uik2FAhvqvc-VXfcNupUB7JUGFOxox6c',
            ]
        ];

        try {
            $webPush = new WebPush($vapidConfig);

            // Récupérer les abonnements push de cet utilisateur
            $stmt = $pdo->prepare("SELECT endpoint, p256dh, auth FROM wari_subscriptions WHERE user_id = ?");
            $stmt->execute([$userId]);
            $subs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($subs)) {
                return ['success' => true, 'recipients' => 0];
            }

            $payload = json_encode([
                'title' => $title,
                'body'  => $body,
                'icon'  => 'https://i.postimg.cc/x80KpBqW/warifinance3d.png',
                'badge' => 'https://i.postimg.cc/x80KpBqW/warifinance3d.png',
                'url'   => $url,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            foreach ($subs as $sub) {
                if (empty($sub['endpoint']) || empty($sub['p256dh']) || empty($sub['auth'])) {
                    continue;
                }

                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $sub['endpoint'],
                        'keys' => ['p256dh' => $sub['p256dh'], 'auth' => $sub['auth']],
                    ]),
                    $payload
                );
            }

            $expiredEndpoints = [];
            $successCount = 0;
            $index = 0;

            foreach ($webPush->flush() as $report) {
                if ($report->isSuccess()) {
                    $successCount++;
                } elseif ($report->isSubscriptionExpired()) {
                    if (isset($subs[$index]['endpoint'])) {
                        $expiredEndpoints[] = $subs[$index]['endpoint'];
                    }
                }
                $index++;
            }

            if (!empty($expiredEndpoints)) {
                $expiredEndpoints = array_filter($expiredEndpoints);
                if (!empty($expiredEndpoints)) {
                    $placeholders = implode(',', array_fill(0, count($expiredEndpoints), '?'));
                    $pdo->prepare("DELETE FROM wari_subscriptions WHERE endpoint IN ($placeholders)")
                        ->execute($expiredEndpoints);
                }
            }

            if ($logId) {
                try {
                    $stmt = $pdo->prepare("UPDATE wari_push_logs SET sent_count = ? WHERE id = ?");
                    $stmt->execute([$successCount, $logId]);
                } catch (Exception $e) {
                    error_log("Erreur de mise à jour wari_push_logs : " . $e->getMessage());
                }
            }

            return ['success' => true, 'recipients' => $successCount];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

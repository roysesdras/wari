<?php
// /var/www/html/classes/Vecu.php

class Vecu
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->ensureTableExists();
    }

    private function ensureTableExists()
    {
        $sql = "CREATE TABLE IF NOT EXISTS wari_vecu_reads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            article_id INT NOT NULL,
            read_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY user_article (user_id, article_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            // Silently fail if we can't create the table
        }
    }

    public function getUnreadCount($user_id)
    {
        if (!$user_id) return 0;

        $sql = "SELECT COUNT(*) FROM wari_articles a
                LEFT JOIN wari_vecu_reads r ON r.article_id = a.id AND r.user_id = ?
                WHERE r.id IS NULL";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$user_id]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function markAsRead($user_id, $article_id)
    {
        if (!$user_id || !$article_id) return false;

        $sql = "INSERT IGNORE INTO wari_vecu_reads (user_id, article_id) VALUES (?, ?)";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$user_id, $article_id]);
        } catch (PDOException $e) {
            return false;
        }
    }
}

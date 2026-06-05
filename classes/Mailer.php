<?php
// /var/www/html/classes/Mailer.php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private $mail;

    public function __construct()
    {
        $this->loadEnv();
        $this->mail = new PHPMailer(true);

        // Configuration SMTP
        $this->mail->isSMTP();
        $this->mail->Host       = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
        $this->mail->SMTPAuth   = true;
        $this->mail->Username   = $_ENV['SMTP_USER'] ?? '';
        $this->mail->Password   = $_ENV['SMTP_PASS'] ?? '';
        
        $secure = strtolower($_ENV['SMTP_SECURE'] ?? 'tls');
        if ($secure === 'ssl') {
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
        
        $this->mail->Port       = $_ENV['SMTP_PORT'] ?? 587;
        $this->mail->CharSet    = 'UTF-8';

        // Expéditeur
        $this->mail->setFrom(
            $_ENV['SMTP_FROM'] ?? 'noreply@wari.digiroys.com',
            $_ENV['SMTP_FROM_NAME'] ?? 'Wari Finance'
        );
    }

    private function loadEnv()
    {
        if (isset($_ENV['SMTP_USER']) && $_ENV['SMTP_USER']) return;

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

    public function send($to, $subject, $body, $isHTML = true)
    {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($to);
            $this->mail->isHTML($isHTML);
            $this->mail->Subject = $subject;
            $this->mail->Body    = $body;
            $this->mail->AltBody = strip_tags($body);

            $this->mail->send();
            return ['success' => true, 'message' => 'Email envoyé'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

<?php
// Adjust these paths if you didn't use Composer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Remove this if you are not using Composer
require_once 'config/Database.php';

class Mailer {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function send($toEmail, $subject, $body) {
        // 1. Fetch SMTP settings from our new database table
        $stmt = $this->db->query("SELECT * FROM settings");
        $settings = [];
        foreach($stmt->fetchAll() as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        // 2. Setup PHPMailer
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = $settings['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $settings['smtp_user'];
            $mail->Password   = $settings['smtp_pass'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $settings['smtp_port'];
            $mail->CharSet    = 'UTF-8';

            // Recipients
            $mail->setFrom($settings['smtp_user'], $settings['smtp_from']);
            $mail->addAddress($toEmail);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            
            // Beautiful Enterprise HTML Wrapper
            $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;'>
                <div style='background: #1e293b; color: white; padding: 20px; text-align: center;'>
                    <h2 style='margin: 0;'>LAP PLV System</h2>
                </div>
                <div style='padding: 30px; background: #ffffff; color: #334155; line-height: 1.6;'>
                    {$body}
                </div>
                <div style='background: #f8fafc; color: #94a3b8; padding: 15px; text-align: center; font-size: 12px;'>
                    Ceci est un message automatique généré par le système LAP PLV. Merci de ne pas répondre.
                </div>
            </div>";

            $mail->send();
            return true;
        } catch (Exception $e) {
            // Silently fail but you can log this to your audit log if you want!
            return false; 
        }
    }
}
?>
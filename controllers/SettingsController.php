<?php
require_once 'models/Setting.php';
require_once 'models/Log.php';

class SettingsController {
    private $settingModel;
    private $logModel;

    public function __construct() {
        // STRICT SECURITY: Admins Only!
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header("Location: /plvsystem/dashboard");
            exit;
        }
        $db = (new Database())->getConnection();
        $this->settingModel = new Setting($db);
        $this->logModel = new Log($db);
    }

    public function mail() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            // Save standard settings
            $this->settingModel->update('smtp_host', trim($_POST['smtp_host']));
            $this->settingModel->update('smtp_port', trim($_POST['smtp_port']));
            $this->settingModel->update('smtp_user', trim($_POST['smtp_user']));
            $this->settingModel->update('smtp_from', trim($_POST['smtp_from']));

            // Only update the password if they actually typed a new one (so they don't accidentally erase it)
            if (!empty($_POST['smtp_pass'])) {
                $this->settingModel->update('smtp_pass', $_POST['smtp_pass']);
            }

            // 🕵️‍♂️ LOG ACTION: Track Mail Server Changes
            $this->logModel->logAction($_SESSION['user_id'], 'CONFIGURATION SYSTÈME', "L'administrateur a mis à jour les paramètres du serveur SMTP.");

            header("Location: /plvsystem/settings/mail?msg=success");
            exit;
        }

        // Fetch current settings to fill the form
        $settings = $this->settingModel->getAll();
        require 'views/settings/mail.php';
    }
}
?>
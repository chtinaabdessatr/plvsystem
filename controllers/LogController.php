<?php
require_once 'models/Log.php';
require_once 'config/Database.php';

class LogController {
    public function index() {
        // 1. Strict Security: Only Admins can view the audit trail
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            header("Location: /plvsystem/dashboard");
            exit;
        }

        // 2. Catch the search word if there is one
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';

        // 3. Fetch the logs
        $db = (new Database())->getConnection();
        $logModel = new Log($db);
        $logs = $logModel->getAllLogs(200, $search);

        // 4. Load the View
        require 'views/admin/logs.php';
    }
}
?>
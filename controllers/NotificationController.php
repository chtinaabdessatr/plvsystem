<?php
require_once 'models/Notification.php';
require_once 'models/Log.php'; // 🕵️‍♂️ ENTERPRISE LOGGING ADDED

class NotificationController {
    private $notifModel;
    private $logModel; // 🕵️‍♂️ Added Log property

    public function __construct() {
        if (!isset($_SESSION['user_id'])) header("Location: /plvsystem/auth/login");
        $db = (new Database())->getConnection();
        $this->notifModel = new Notification($db);
        $this->logModel = new Log($db); // 🕵️‍♂️ Initialize Logger
    }

    public function index() {
        // 1. FETCH DATA FIRST (So we have the content)
        $notifications = $this->notifModel->getAll($_SESSION['user_id']);

        // 2. MARK AS READ (So the red badge disappears next time)
        $this->notifModel->markAsRead($_SESSION['user_id']);

        // 🕵️‍♂️ LOG ACTION: Notifications Read (Only if they actually had notifications)
        if (!empty($notifications)) {
            $this->logModel->logAction($_SESSION['user_id'], 'CONSULTATION NOTIFICATIONS', "L'utilisateur a consulté la page des notifications et elles ont été marquées comme lues.");
        }

        require 'views/notifications/index.php';
    }
    
    // Add this method to your NotificationController class
    public function check() {
        // 1. Ensure user is logged in
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['count' => 0]);
            exit;
        }

        // 2. Fetch unread count
        $db = (new Database())->getConnection();
        $notifObj = new Notification($db);
        $unread = $notifObj->getUnread($_SESSION['user_id']);
        $count = count($unread);

        // 3. Return JSON 
        // 🛑 NOTE: We DO NOT log this action because it runs constantly in the background via AJAX!
        header('Content-Type: application/json');
        echo json_encode(['count' => $count]);
        exit;
    }
}
?>
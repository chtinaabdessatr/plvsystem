<?php
require_once 'models/Notification.php';

class NotificationController {
    
    public function index() {
        // 1. Security Check
        if (!isset($_SESSION['user_id'])) {
            header("Location: /plvsystem/auth/login");
            exit;
        }
        
        $db = (new Database())->getConnection();
        $notifModel = new Notification($db);
        
        // 2. Fetch Notifications (We display unread ones before marking them read)
        // Note: Ideally, you might want to fetch ALL recent notifications (read & unread)
        // But for now, let's show the ones that triggered the alert.
        $notifications = $notifModel->getUnread($_SESSION['user_id']);
        
        // 3. Load the View
        require 'views/notification/index.php';
        
        // 4. Mark them as read immediately after loading the page
        $notifModel->markAsRead($_SESSION['user_id']);
    }
}
?>
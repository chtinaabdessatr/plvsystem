<?php
require_once 'models/Notification.php';

class NotificationController {
    private $notifModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) header("Location: /plvsystem/auth/login");
        $db = (new Database())->getConnection();
        $this->notifModel = new Notification($db);
    }

    public function index() {
        // 1. FETCH DATA FIRST (So we have the content)
        $notifications = $this->notifModel->getAll($_SESSION['user_id']);

        // 2. MARK AS READ (So the red badge disappears next time)
        $this->notifModel->markAsRead($_SESSION['user_id']);

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
    header('Content-Type: application/json');
    echo json_encode(['count' => $count]);
    exit;
}
}
?>
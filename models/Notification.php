<?php
class Notification {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create a new notification
    public function create($userId, $message, $link = '#') {
        $stmt = $this->conn->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
        return $stmt->execute([$userId, $message, $link]);
    }

    // Get only unread notifications (for the header count)
    public function getUnread($userId) {
        $stmt = $this->conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    // --- THIS WAS MISSING ---
    public function markAsRead($userId) {
        $stmt = $this->conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }
}
?>
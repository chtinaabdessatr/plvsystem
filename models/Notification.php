<?php
class Notification {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. CREATE NOTIFICATION
    public function create($userId, $message, $link) {
        $stmt = $this->conn->prepare("INSERT INTO notifications (user_id, message, link, is_read) VALUES (?, ?, ?, 0)");
        return $stmt->execute([$userId, $message, $link]);
    }

    // 2. GET ALL (For the History Page)
    public function getAll($userId) {
        $sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    // 3. GET UNREAD (For the Header Dropdown - THIS WAS MISSING)
    public function getUnread($userId) {
        $sql = "SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    // 4. GET UNREAD COUNT (For the Red Badge)
    public function getUnreadCount($userId) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row['total'];
    }

    // 5. MARK ALL AS READ
    public function markAsRead($userId) {
        $stmt = $this->conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }
}
?>
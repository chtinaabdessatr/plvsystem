<?php
class Log {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // --- 📝 RECORD A NEW LOG ---
    public function logAction($user_id, $action, $details = '') {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        
        $stmt = $this->conn->prepare("INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$user_id, $action, $details, $ip]);
    }

    // --- 🔍 FETCH LOGS FOR ADMIN ---
    public function getAllLogs($limit = 100) {
        $stmt = $this->conn->prepare("
            SELECT l.*, u.name as user_name, u.role as user_role 
            FROM system_logs l 
            LEFT JOIN users u ON l.user_id = u.id 
            ORDER BY l.created_at DESC 
            LIMIT ?
        ");
        // Bind limit securely
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>
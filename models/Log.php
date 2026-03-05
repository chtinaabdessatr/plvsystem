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

    // --- 🔍 FETCH & SEARCH LOGS FOR ADMIN ---
    public function getAllLogs($limit = 200, $search = '') {
        $sql = "SELECT l.*, u.name as user_name, u.role as user_role 
                FROM system_logs l 
                LEFT JOIN users u ON l.user_id = u.id ";
        
        $params = [];
        
        // If the Admin typed something in the search bar
        if (!empty($search)) {
            $sql .= " WHERE l.action LIKE ? OR l.details LIKE ? OR u.name LIKE ? OR l.ip_address LIKE ? ";
            $searchTerm = "%" . trim($search) . "%";
            // We need 4 identical parameters for the 4 LIKE statements
            $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
        }
        
        $sql .= " ORDER BY l.created_at DESC LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        
        // Bind search params dynamically
        $paramIndex = 1;
        foreach ($params as $param) {
            $stmt->bindValue($paramIndex++, $param, PDO::PARAM_STR);
        }
        
        // Bind the limit securely at the very end
        $stmt->bindValue($paramIndex, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
?>
<?php
class Order {
    private $conn;
    public function __construct($db) { $this->conn = $db; }

    public function create($data) {
        $sql = "INSERT INTO orders (client_name, plv_type, description, deadline, priority, created_by, current_stage) 
                VALUES (?, ?, ?, ?, ?, ?, 'created')";
        $stmt = $this->conn->prepare($sql);
        
        if($stmt->execute([$data['client'], $data['type'], $data['desc'], $data['deadline'], $data['priority'], $data['creator']])) {
            // Return the ID of the newly created order
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function getAll() {
        // This query fetches the Order + The Creator's Name + The Assigned User's Name (if any)
        $sql = "SELECT o.*, 
                       u.name as creator_name,
                       (SELECT assigned_user.name 
                        FROM assignments a 
                        JOIN users assigned_user ON a.user_id = assigned_user.id 
                        WHERE a.order_id = o.id 
                        ORDER BY a.id DESC LIMIT 1) as assigned_to
                FROM orders o 
                JOIN users u ON o.created_by = u.id 
                ORDER BY o.created_at DESC";
                
        return $this->conn->query($sql)->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function assign($orderId, $userId, $stage) {
        $stmt = $this->conn->prepare("INSERT INTO assignments (order_id, user_id, stage, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$orderId, $userId, $stage]);
        
        $stmt = $this->conn->prepare("UPDATE orders SET current_stage = ?, status = 'assigned' WHERE id = ?");
        $stmt->execute([$stage, $orderId]);
    }

    public function getAssignment($orderId, $stage) {
        $stmt = $this->conn->prepare("SELECT a.*, u.name as user_name, u.id as user_id FROM assignments a JOIN users u ON a.user_id = u.id WHERE order_id = ? AND stage = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$orderId, $stage]);
        return $stmt->fetch();
    }

    public function updateAssignmentStatus($id, $status, $reason = null) {
        if ($status == 'accepted') {
            $sql = "UPDATE assignments SET status = ?, start_time = NOW() WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$status, $id]);
        }
        $sql = "UPDATE assignments SET status = ?, refusal_reason = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$status, $reason, $id]);
    }

    public function completeAssignment($id, $orderId, $nextStage) {
        $stmt = $this->conn->prepare("UPDATE assignments SET status = 'completed', end_time = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        
        $status = ($nextStage == 'completed') ? 'approved' : 'pending';
        $stmt = $this->conn->prepare("UPDATE orders SET current_stage = ?, status = ? WHERE id = ?");
        $stmt->execute([$nextStage, $status, $orderId]);
    }

    public function addFile($orderId, $userId, $path, $stage) {
        $stmt = $this->conn->prepare("INSERT INTO order_files (order_id, uploaded_by, file_path, stage) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$orderId, $userId, $path, $stage]);
    }

    public function getFiles($orderId) {
        $stmt = $this->conn->prepare("SELECT f.*, u.name as uploader FROM order_files f JOIN users u ON f.uploaded_by = u.id WHERE order_id = ?");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

public function update($id, $data) {
    $sql = "UPDATE orders SET 
            client_name = ?, 
            plv_type = ?, 
            description = ?, 
            deadline = ?, 
            priority = ? 
            WHERE id = ?";
    
    $stmt = $this->conn->prepare($sql);
    return $stmt->execute([
        $data['client'], 
        $data['type'], 
        $data['desc'], 
        $data['deadline'], 
        $data['priority'], 
        $id
    ]);
}
public function getAllRecentFiles() {
    // FIX: Changed 'uploaded_at' to 'created_at' to match database schema
    $sql = "SELECT f.*, o.client_name, o.id as order_id, u.name as uploader_name 
            FROM order_files f 
            JOIN orders o ON f.order_id = o.id 
            JOIN users u ON f.uploaded_by = u.id 
            ORDER BY f.created_at DESC 
            LIMIT 20";
    return $this->conn->query($sql)->fetchAll();
}
public function getUserPendingTasks($userId) {
    $sql = "SELECT o.*, a.id as assignment_id 
            FROM orders o 
            JOIN assignments a ON o.id = a.order_id 
            WHERE a.user_id = ? AND a.status = 'pending'
            ORDER BY a.id DESC"; // FIX: Changed 'assigned_at' to 'id'
    
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}
// Add this to models/Order.php
public function getUserAssignment($orderId, $userId) {
    $sql = "SELECT a.*, u.name as user_name 
            FROM assignments a 
            JOIN users u ON a.user_id = u.id 
            WHERE a.order_id = ? AND a.user_id = ? 
            ORDER BY a.id DESC LIMIT 1";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([$orderId, $userId]);
    return $stmt->fetch();
}
// Add this to the bottom of your Order class
public function updateOrderStatus($orderId, $status) {
    $stmt = $this->conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    return $stmt->execute([$status, $orderId]);
}
} // End of Class
?>
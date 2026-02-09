<?php
class Order {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. CREATE ORDER
    public function create($data) {
        // Added 'status' to the INSERT to ensure order appears as 'active' immediately
        $sql = "INSERT INTO orders (client_name, commercial_name, zone, plv_type, description, deadline, priority, created_by, current_stage, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'created', 'active')";
        
        $stmt = $this->conn->prepare($sql);
        
        if($stmt->execute([
            $data['client'], 
            $data['commercial'], 
            $data['zone'], 
            $data['type'], 
            $data['desc'], 
            $data['deadline'], 
            $data['priority'], 
            $data['creator']
        ])) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function getAll() {
        // Fetches Order + Creator Info + Current Assignee + Role
        $sql = "SELECT o.*, 
                       u.name as creator_name, 
                       u.role as creator_role,
                       (SELECT assigned_user.name 
                        FROM assignments a 
                        JOIN users assigned_user ON a.user_id = assigned_user.id 
                        WHERE a.order_id = o.id AND a.status IN ('pending', 'accepted')
                        ORDER BY a.id DESC LIMIT 1) as assigned_to
                FROM orders o 
                JOIN users u ON o.created_by = u.id 
                ORDER BY o.created_at DESC";
        return $this->conn->query($sql)->fetchAll();
    }

    // 3. GET SINGLE ORDER
    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // 4. ASSIGN USER (Renamed from 'assign' to match Controller)
    public function assignUser($orderId, $userId, $stage) {
        // Prevent duplicate assignment
        $check = $this->conn->prepare("SELECT id FROM assignments WHERE order_id = ? AND stage = ? AND status != 'refused'");
        $check->execute([$orderId, $stage]);
        if($check->rowCount() > 0) return false;

        $stmt = $this->conn->prepare("INSERT INTO assignments (order_id, user_id, stage, status) VALUES (?, ?, ?, 'pending')");
        if($stmt->execute([$orderId, $userId, $stage])) {
            // Update Order Status
            $this->updateOrderStatus($orderId, 'active');
            return true;
        }
        return false;
    }

    // 5. GET ASSIGNMENT (For Admin check)
    public function getAssignment($orderId, $stage) {
        $stmt = $this->conn->prepare("SELECT a.*, u.name as user_name, u.id as user_id FROM assignments a JOIN users u ON a.user_id = u.id WHERE order_id = ? AND stage = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$orderId, $stage]);
        return $stmt->fetch();
    }

    // 6. GET USER SPECIFIC ASSIGNMENT (For Worker check)
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

    // 7. UPDATE ASSIGNMENT STATUS (Accept/Refuse)
    public function updateAssignmentStatus($id, $status) {
        if ($status == 'accepted') {
            $sql = "UPDATE assignments SET status = ?, start_time = NOW() WHERE id = ?";
        } else {
            $sql = "UPDATE assignments SET status = ? WHERE id = ?";
        }
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$status, $id]);
    }

    // 8. COMPLETE ASSIGNMENT (Move to next stage)
    public function completeAssignment($assignmentId, $orderId, $nextStage, $orderStatus) {
        try {
            $this->conn->beginTransaction();

            // Mark assignment complete
            $stmt1 = $this->conn->prepare("UPDATE assignments SET status = 'completed', completed_at = NOW() WHERE id = ?");
            $stmt1->execute([$assignmentId]);

            // Move Order to next stage
            $stmt2 = $this->conn->prepare("UPDATE orders SET current_stage = ?, status = ? WHERE id = ?");
            $stmt2->execute([$nextStage, $orderStatus, $orderId]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    // 9. FILES (Using table 'files' not 'order_files')
    public function addFile($orderId, $userId, $path, $stage) {
        $stmt = $this->conn->prepare("INSERT INTO files (order_id, uploaded_by, file_path, stage) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$orderId, $userId, $path, $stage]);
    }

    public function getFiles($orderId) {
        $stmt = $this->conn->prepare("SELECT f.*, u.name as uploader FROM files f JOIN users u ON f.uploaded_by = u.id WHERE order_id = ?");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }
    
    public function getAllRecentFiles() {
        $sql = "SELECT f.*, o.client_name, o.id as order_id, u.name as uploader_name 
                FROM files f 
                JOIN orders o ON f.order_id = o.id 
                JOIN users u ON f.uploaded_by = u.id 
                ORDER BY f.created_at DESC 
                LIMIT 20";
        return $this->conn->query($sql)->fetchAll();
    }

    // 10. HELPER UPDATES
    public function updateOrderStatus($orderId, $status) {
        $stmt = $this->conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $orderId]);
    }

    public function update($id, $data) {
        $sql = "UPDATE orders SET client_name = ?, plv_type = ?, description = ?, deadline = ?, priority = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$data['client'], $data['type'], $data['desc'], $data['deadline'], $data['priority'], $id]);
    }

    public function getUserPendingTasks($userId) {
        $sql = "SELECT o.*, a.id as assignment_id 
                FROM orders o 
                JOIN assignments a ON o.id = a.order_id 
                WHERE a.user_id = ? AND a.status = 'pending'
                ORDER BY a.id DESC"; 
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    // 2. GET ORDER HISTORY (For the 'Flow Info' button)
    public function getOrderHistory($orderId) {
        $sql = "SELECT a.*, u.name as user_name, u.role as user_role
                FROM assignments a
                JOIN users u ON a.user_id = u.id
                WHERE a.order_id = ?
                ORDER BY a.created_at ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

    public function refuseAssignment($assignmentId, $reason) {
        $stmt = $this->conn->prepare("UPDATE assignments SET status = 'refused', refusal_reason = ? WHERE id = ?");
        $stmt->execute([$reason, $assignmentId]);
        
        // Reset order status to 'pending' so Admin sees it needs re-assignment
        // We need to find the order ID first
        $stmt = $this->conn->prepare("SELECT order_id FROM assignments WHERE id = ?");
        $stmt->execute([$assignmentId]);
        $row = $stmt->fetch();
        
        if ($row) {
            $stmt = $this->conn->prepare("UPDATE orders SET status = 'pending' WHERE id = ?");
            $stmt->execute([$row['order_id']]);
        }
}
// --- GET ORDERS FOR A SPECIFIC WORKER (Designer/Printer/Delivery) ---
// --- GET ORDERS FOR A SPECIFIC WORKER ---
public function getOrdersForUser($userId) {
    $sql = "SELECT o.*, 
                   u.name as creator_name, 
                   u.role as creator_role,
                   a.status as my_status,
                   a.refusal_reason,
                   a.id as assignment_id,
                   u_worker.name as assigned_to  -- Added this to fix the Warning
            FROM orders o
            JOIN assignments a ON o.id = a.order_id
            JOIN users u ON o.created_by = u.id
            JOIN users u_worker ON a.user_id = u_worker.id
            WHERE a.user_id = ?
            ORDER BY o.created_at DESC";
            
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}
}
?>
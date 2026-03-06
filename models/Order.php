<?php
class Order {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. CREATE ORDER
    public function create($data) {
        // 🔴 FIX: Added the 9th '?' to exactly match the 9 variables we pass in execute()
        $sql = "INSERT INTO orders (client_name, client_contact, commercial_name, zone, plv_type, description, deadline, priority, created_by, current_stage, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'created', 'active')";
        
        $stmt = $this->conn->prepare($sql);
        
        if($stmt->execute([
            $data['client'], 
            $data['client_contact'],
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

    public function getAll($days = 30) {
        // Fetches Order + Creator Info + Current Assignee + Role (Last 30 Days)
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
                WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                ORDER BY o.created_at DESC";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }

    public function getByCommercial($userId, $days = 30) {
        // Fetches same info, but only for this specific commercial (Last 30 Days)
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
                WHERE o.created_by = ? AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                ORDER BY o.created_at DESC";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userId, $days]);
        return $stmt->fetchAll();
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
    // Renamed to match the controller
    public function completeStage($assignment_id, $order_id, $old_stage, $new_stage) {
        try {
            $this->conn->beginTransaction();

            // 1. Mark Assignment as Completed
            // We use 'end_time' because your database screenshot showed that column name
            $sql1 = "UPDATE assignments SET status = 'completed', end_time = NOW() WHERE id = ?";
            $stmt1 = $this->conn->prepare($sql1);
            $stmt1->execute([$assignment_id]);

            // 2. Update Order Stage
            // If the new stage is 'completed', set order status to 'completed'. Otherwise 'pending'.
            $status = ($new_stage == 'completed') ? 'completed' : 'pending';
            
            $sql2 = "UPDATE orders SET current_stage = ?, status = ? WHERE id = ?";
            $stmt2 = $this->conn->prepare($sql2);
            $stmt2->execute([$new_stage, $status, $order_id]);

            // 3. (HISTORY INSERT REMOVED to prevent crash)

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            // Uncomment to see specific errors if it fails again
            // die($e->getMessage()); 
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
        $sql = "UPDATE orders SET client_name = ?, client_contact = ?, plv_type = ?, description = ?, deadline = ?, priority = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['client'], 
            $data['client_contact'], 
            $data['type'], 
            $data['desc'], 
            $data['deadline'], 
            $data['priority'], 
            $id
        ]);
    }

    // Add to models/Order.php
    public function setAssignmentStatus($assignment_id, $status) {
        $stmt = $this->conn->prepare("UPDATE assignments SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $assignment_id]);
    }
    
    // Also make sure you have updateStage from previous fix
    public function updateStage($order_id, $new_stage) {
        $stmt = $this->conn->prepare("UPDATE orders SET current_stage = ? WHERE id = ?");
        return $stmt->execute([$new_stage, $order_id]);
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
public function getAssignmentByStage($order_id, $stage) {
    $sql = "SELECT a.*, u.name as user_name, u.role as user_role 
            FROM assignments a 
            JOIN users u ON a.user_id = u.id 
            WHERE a.order_id = ? AND a.stage = ? AND a.status != 'refused'
            ORDER BY a.id DESC LIMIT 1";
    
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([$order_id, $stage]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
// --- CHAT SYSTEM: ADD MESSAGE ---
    public function addChatMessage($orderId, $userId, $message, $filePath = null) {
        $stmt = $this->conn->prepare("INSERT INTO order_messages (order_id, user_id, message, file_path) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$orderId, $userId, $message, $filePath]);
    }

    // --- CHAT SYSTEM: GET MESSAGES ---
    public function getChatMessages($orderId) {
        $sql = "SELECT m.*, u.name as user_name, u.role as user_role 
                FROM order_messages m 
                JOIN users u ON m.user_id = u.id 
                WHERE m.order_id = ? 
                ORDER BY m.created_at ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }
    
// --- TASK POOL: GET AVAILABLE UNASSIGNED ORDERS ---
    public function getAvailableOrders($role) {
        // 1. Force lowercase to prevent 'Designer' vs 'designer' bugs
        $role = strtolower(trim($role));
        
        // 2. Determine which stages this role is allowed to grab
        $stages = "('')"; // Default empty
        if ($role == 'designer') $stages = "('created', 'design')";
        if ($role == 'printer') $stages = "('printing')";
        if ($role == 'delivery') $stages = "('delivery')";

        // 3. Bulletproof SQL: 
        // - Order is in the correct stage
        // - Order is NOT completed
        // - Order has NO active worker (no 'pending' or 'accepted' assignments)
        $sql = "SELECT * FROM orders 
                WHERE current_stage IN $stages 
                AND status != 'completed' 
                AND NOT EXISTS (
                    SELECT 1 FROM assignments a 
                    WHERE a.order_id = orders.id 
                    AND a.status IN ('pending', 'accepted')
                )
                ORDER BY 
                    CASE WHEN priority = 'Urgent' THEN 1 
                         WHEN priority = 'High' THEN 2 
                         ELSE 3 END ASC, 
                    created_at ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    
    // --- TASK CLAIMING (ANTI-OVERLAP LOCK) ---
public function claimTask($orderId, $userId, $stage) {
        try {
            $this->conn->beginTransaction();
            // Lock the row
            $stmt = $this->conn->prepare("SELECT id FROM assignments WHERE order_id = ? AND stage = ? FOR UPDATE");
            $stmt->execute([$orderId, $stage]);
            
            if ($stmt->rowCount() > 0) {
                $this->conn->rollBack();
                return false; // Someone else grabbed it!
            }

            // Assign it to this designer
            $stmt2 = $this->conn->prepare("INSERT INTO assignments (order_id, user_id, stage, status) VALUES (?, ?, ?, 'accepted')");
            $stmt2->execute([$orderId, $userId, $stage]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }
    // --- 🗑️ DELETE ORDER ---
    public function deleteOrder($id) {
        // Delete the order (Assignments and files should ideally cascade or be deleted here too)
        $stmt = $this->conn->prepare("DELETE FROM orders WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // --- 🔍 SEARCH ORDERS ---
    public function searchOrders($keyword, $role, $userId) {
        $q = "%" . trim($keyword) . "%";
        
        // Admins search everything
        if ($role == 'admin') {
            $stmt = $this->conn->prepare("SELECT * FROM orders WHERE id LIKE ? OR client_name LIKE ? OR status LIKE ? ORDER BY created_at DESC");
            $stmt->execute([$q, $q, $q]);
        } else {
            // Workers search only their active tasks
            $stmt = $this->conn->prepare("
                SELECT o.* FROM orders o 
                JOIN assignments a ON o.id = a.order_id 
                WHERE a.user_id = ? AND (o.id LIKE ? OR o.client_name LIKE ?) 
                ORDER BY o.created_at DESC
            ");
            $stmt->execute([$userId, $q, $q]);
        }
        return $stmt->fetchAll();
    }
}
?>
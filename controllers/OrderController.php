<?php
require_once 'models/Order.php';
require_once 'models/User.php';

class OrderController {
    private $orderModel;
    private $userModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) header("Location: /plvsystem/auth/login");
        $db = (new Database())->getConnection();
        $this->orderModel = new Order($db);
        $this->userModel = new User($db);
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'client' => $_POST['client_name'],
                'type' => $_POST['plv_type'],
                'desc' => $_POST['description'],
                'deadline' => $_POST['deadline'],
                'priority' => $_POST['priority'],
                'creator' => $_SESSION['user_id']
            ];
            
            // 1. Create the Order and get ID
            $newOrderId = $this->orderModel->create($data);
            
            // 2. If Order Created & File exists, Upload it
            if ($newOrderId && isset($_FILES['ref_file']) && $_FILES['ref_file']['error'] == 0) {
                
                $uploadDir = 'public/uploads/';
                // Ensure directory exists
                if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $fileName = time() . '_ref_' . basename($_FILES['ref_file']['name']);
                $targetPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['ref_file']['tmp_name'], $targetPath)) {
                    // Save to database as "creation" stage file
                    $this->orderModel->addFile($newOrderId, $_SESSION['user_id'], $targetPath, 'creation');
                }
            }
            
            header("Location: /plvsystem/dashboard");
        } else {
            require 'views/orders/create.php';
        }
    }

    public function view($id) {
        $order = $this->orderModel->getById($id);
        
        if (!$order) {
            header("Location: /plvsystem/dashboard");
            exit;
        }

        $files = $this->orderModel->getFiles($id);
        $designers = $this->userModel->getDesigners();
        $printers = $this->userModel->getPrinters();
        $delivery = $this->userModel->getDelivery();
        
        // --- FIX STARTS HERE ---
        // Instead of strict stage matching, check if the CURRENT USER has a pending assignment
        if ($_SESSION['role'] !== 'admin') {
            // If I am a worker, find MY assignment for this order
            $assignment = $this->orderModel->getUserAssignment($id, $_SESSION['user_id']);
        } else {
            // If I am Admin, look for the current active assignment for the stage
            $assignment = $this->orderModel->getAssignment($id, $order['current_stage']);
        }
        // --- FIX ENDS HERE ---
        
        require 'views/orders/view.php';
    }

    public function assign() {
        if ($_SESSION['role'] != 'admin') die('Unauthorized');
        $this->orderModel->assign($_POST['order_id'], $_POST['user_id'], $_POST['stage']);
        header("Location: /plvsystem/order/view/" . $_POST['order_id']);
    }

    public function updateStatus() {
        $assignmentId = $_POST['assignment_id'];
        $status = $_POST['status']; // 'accepted' or 'refused'
        $orderId = $_POST['order_id'];
        
        // 1. Get current order details to find the Admin (Creator)
        $order = $this->orderModel->getById($orderId);
        $adminId = $order['created_by'];
        $workerName = $_SESSION['name'];

        if ($status == 'refused') {
            // --- LOGIC FOR REFUSAL ---
            
            // A. Mark assignment as refused
            $this->orderModel->updateAssignmentStatus($assignmentId, 'refused');
            
            // B. RESET ORDER STATUS to 'pending' so Admin can assign someone else
            // We reuse the updateStatus method or add a specific one
            $this->orderModel->updateOrderStatus($orderId, 'pending');
            
            // C. Notify Admin immediately
            $msg = "❌ ALERT: $workerName REFUSED Order #$orderId. Please re-assign it.";
            $this->notifModel->create($adminId, $msg, "/plvsystem/order/view/$orderId");
            
        } else {
            // --- LOGIC FOR ACCEPTANCE ---
            $this->orderModel->updateAssignmentStatus($assignmentId, 'accepted');
            
            // Optional: Notify Admin that work has started
            $msg = "✅ $workerName accepted Order #$orderId.";
            $this->notifModel->create($adminId, $msg, "/plvsystem/order/view/$orderId");
        }

        header("Location: /plvsystem/order/view/" . $orderId);
    }

    public function upload() {
        $orderId = $_POST['order_id'];
        if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
            $uploadDir = 'public/uploads/';
            if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
            $fileName = time() . '_' . basename($_FILES['file']['name']);
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
                $this->orderModel->addFile($orderId, $_SESSION['user_id'], $targetPath, $_POST['stage']);
            }
        }
        header("Location: /plvsystem/order/view/" . $orderId);
    }

    public function complete() {
        // 1. Get Data and FORCE LOWERCASE to avoid bugs (e.g. "Design" vs "design")
        $currentStage = strtolower(trim($_POST['current_stage'])); 
        $orderId = $_POST['order_id'];
        $assignmentId = $_POST['assignment_id'];

        // 2. Determine Next Stage strictly
        if ($currentStage == 'design' || $currentStage == 'created') {
            $nextStage = 'printing';
            $orderStatus = 'pending'; // Reset so Admin can assign Printer
        } 
        elseif ($currentStage == 'printing') {
            $nextStage = 'delivery';
            $orderStatus = 'pending'; // Reset so Admin can assign Delivery
        } 
        elseif ($currentStage == 'delivery') {
            $nextStage = 'completed';
            $orderStatus = 'completed';
        }
        else {
            // Fallback: If something weird happens, don't complete it automatically.
            // Keep it in the current stage to be safe.
            $nextStage = $currentStage; 
            $orderStatus = 'pending';
        }
        
        // 3. Update Database
        $this->orderModel->completeAssignment($assignmentId, $orderId, $nextStage, $orderStatus);
        
        // 4. Notification
        // Fetch creator ID properly to notify them
        $order = $this->orderModel->getById($orderId);
        $adminId = $order['created_by'];

        $msg = "✅ Stage '$currentStage' finished. Order #$orderId is now in '$nextStage'.";
        $this->notifModel->create($adminId, $msg, "/plvsystem/order/view/$orderId");

        header("Location: /plvsystem/order/view/" . $orderId);
    }
// ... existing code ...

public function edit($id) {
    // 1. Security Check: Only Admin or Commercial can edit
    if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'commercial') {
        die("Access Denied");
    }

    // 2. Handle the Form Submission (POST)
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $data = [
            'client' => $_POST['client_name'],
            'type' => $_POST['plv_type'],
            'desc' => $_POST['description'],
            'deadline' => $_POST['deadline'],
            'priority' => $_POST['priority']
        ];

        if ($this->orderModel->update($id, $data)) {
            // Success: Redirect back to the Order Details page
            header("Location: /plvsystem/order/view/" . $id);
            exit;
        }
    }

    // 3. Show the Edit Form (GET)
    $order = $this->orderModel->getById($id);
    if (!$order) {
        header("Location: /plvsystem/dashboard");
        exit;
    }
    
    require 'views/orders/edit.php';
}
// Add this to controllers/OrderController.php
public function recent() {
    $files = $this->orderModel->getAllRecentFiles();
    require 'views/orders/recent.php';
}

}
?>
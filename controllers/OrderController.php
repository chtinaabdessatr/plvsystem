<?php
require_once 'models/Order.php';
require_once 'models/User.php';
require_once 'models/Notification.php'; // Ensure this is included

class OrderController {
    private $orderModel;
    private $userModel;
    private $notifModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) header("Location: /plvsystem/auth/login");
        $db = (new Database())->getConnection();
        $this->orderModel = new Order($db);
        $this->userModel = new User($db);
        $this->notifModel = new Notification($db);
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            // 1. Start Description
            $desc = isset($_POST['description']) ? trim($_POST['description']) : '';
            
            // 2. IF PANNEAU: Format the 6 Panels Logic
            if ($_POST['plv_type'] == 'Panneau') {
                // Keep '===' because your View parser needs it to separate the General Notes
                $desc .= "\n\n=== SPÉCIFICATIONS TECHNIQUES ===";
                
                // Clean standard text (No emojis)
                $desc .= "\nNom du projet : " . $_POST['display_name'];
                $desc .= "\nInclusion Logo : " . $_POST['has_logo'];
                
                for ($i = 1; $i <= 6; $i++) {
                    $w = $_POST["p{$i}_w"] ?? '';
                    $h = $_POST["p{$i}_h"] ?? '';
                    $content = $_POST["p{$i}_content"] ?? [];
                    
                    if (!empty($w) || !empty($content)) {
                        // Keep '---' and '[]' because your View parser uses them to create cards
                        $desc .= "\n\n--- [ Panneau #$i ] ---";
                        
                        if($w && $h) $desc .= "\nDimensions : {$w}cm (L) x {$h}cm (H)";
                        
                        if (!empty($content)) {
                            $desc .= "\nÉléments : " . implode(', ', $content);
                            if(in_array('Other', $content) && !empty($_POST["p{$i}_other"])) {
                                $desc .= " (" . $_POST["p{$i}_other"] . ")";
                            }
                        }
                    }
                }
            }

            // 3. Prepare Data
            $data = [
                'client' => $_POST['client_name'],
                'commercial' => $_POST['commercial_name'],
                'zone' => $_POST['zone'],
                'type' => $_POST['plv_type'],
                'desc' => $desc,
                'deadline' => $_POST['deadline'],
                'priority' => $_POST['priority'],
                'creator' => $_SESSION['user_id']
            ];
            
            // 4. Save to DB
            $newOrderId = $this->orderModel->create($data);
            
            // 5. Upload Files
            if ($newOrderId) {
                // Check if file helpers exist before calling
                if(method_exists($this, 'uploadHelper')) {
                    $this->uploadHelper($newOrderId, 'ref_file_manuscript');
                    $this->uploadHelper($newOrderId, 'ref_file_facade');
                    $this->uploadHelper($newOrderId, 'ref_file_logo'); 
                }
                
                header("Location: /plvsystem/dashboard");
                exit;
            }
        } else {
            require 'views/orders/create.php';
        }
    }

    // --- 🚨 THIS IS THE FUNCTION YOU WERE MISSING 🚨 ---
    private function uploadHelper($orderId, $inputName) {
        if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] == 0) {
            $uploadDir = 'public/uploads/';
            if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $fileName = time() . '_' . $inputName . '_' . basename($_FILES[$inputName]['name']);
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES[$inputName]['tmp_name'], $targetPath)) {
                // 'creation' is the stage for initial files
                $this->orderModel->addFile($orderId, $_SESSION['user_id'], $targetPath, 'creation');
            }
        }
    }
    public function view($id) {
        // 1. Get Basic Order Details
        $order = $this->orderModel->getById($id);
        if (!$order) { header("Location: /plvsystem/dashboard"); exit; }

        $files = $this->orderModel->getFiles($id);
        $history = $this->orderModel->getOrderHistory($id);
        
        // 💬 ADD THIS NEW LINE FOR THE CHAT SYSTEM:
        $chatMessages = $this->orderModel->getChatMessages($id);

        // 2. FETCH THE ASSIGNMENT (The "Reading" Part)
        $assignment = [];

        // CASE A: YOU ARE ADMIN OR COMMERCIAL
        // You need to see WHOEVER is working on the current stage (Designer, Printer, etc.)
        if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'commercial') {
            // We use a special method to find "The latest assignment for this stage"
            $assignment = $this->orderModel->getAssignmentByStage($id, $order['current_stage']);
        } 
        
        // CASE B: YOU ARE A WORKER (Designer/Printer)
        // You only care about tasks assigned specifically to YOU
        else {
            $assignment = $this->orderModel->getUserAssignment($id, $_SESSION['user_id']);
        }

        require 'views/orders/view.php';
    }

    public function assign() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_id = $_POST['order_id'];
            $user_id = $_POST['user_id'];
            $current_stage = $_POST['stage'];

            // --- THE FIX IS HERE ---
            // Don't create a new User model. Use the one from the constructor.
            // OLD: $userModel = new User($this->db); 
            // OLD: $worker = $userModel->findById($user_id);
            
            // NEW:
            $worker = $this->userModel->findById($user_id);
            // -----------------------
            
            // Logic based on worker role
            $newStage = $current_stage;
            
            if ($worker['role'] == 'designer') {
                $newStage = 'design';
            } elseif ($worker['role'] == 'printer') {
                $newStage = 'printing';
            } elseif ($worker['role'] == 'delivery') {
                $newStage = 'delivery';
            }

            // Update Assignment
            $this->orderModel->assignUser($order_id, $user_id, $newStage);
            
            // Update Order Stage
            $this->orderModel->updateStage($order_id, $newStage);

            // Notification
            if(isset($this->notifModel)) {
                $this->notifModel->create($user_id, "👉 New Task Assigned: Order #$order_id", "/plvsystem/order/view/$order_id");
            }

            header("Location: /plvsystem/dashboard");
            exit;
        }
    }

    public function updateStatus() {
        $assignmentId = $_POST['assignment_id'];
        $status = $_POST['status']; 
        $orderId = $_POST['order_id'];
        
        $this->orderModel->updateAssignmentStatus($assignmentId, $status);

        // Notify Admin
        $order = $this->orderModel->getById($orderId);
        $adminId = $order['created_by'];
        $workerName = $_SESSION['name'];

        if ($status == 'refused') {
            $msg = "❌ ALERT: $workerName REFUSED Order #$orderId.";
            $this->orderModel->updateOrderStatus($orderId, 'pending'); 
            $this->notifModel->create($adminId, $msg, "/plvsystem/order/view/$orderId");
        } else {
            $msg = "✅ $workerName accepted Order #$orderId.";
            $this->notifModel->create($adminId, $msg, "/plvsystem/order/view/$orderId");
        }

        header("Location: /plvsystem/order/view/" . $orderId);
    }

    public function upload() {
        $orderId = $_POST['order_id'];
        // Use the helper for work files too
        $this->uploadHelper($orderId, 'file');
        
        // If it was a manual upload from the view page, we might need to manually set the stage
        // But for simplicity, the helper sets 'creation'. You might want to update addFile to take stage from POST.
        
        header("Location: /plvsystem/order/view/" . $orderId);
    }

    // 1. WORKER ACTION: "Mark as Done" (Request Review)
    public function requestReview() {
        $assignment_id = $_POST['assignment_id'];
        $order_id = $_POST['order_id'];
        
        // Update assignment status to 'review'
        $this->orderModel->setAssignmentStatus($assignment_id, 'review');
        
        // Notify Admin
        // Assuming Admin ID is 1 or finding admins via model. 
        // For simplicity, let's assume we notify the creator of the order:
        $order = $this->orderModel->getById($order_id);
        $this->notifModel->create($order['created_by'], "⚖️ Approval Needed: Order #$order_id", "/plvsystem/order/view/$order_id");

        header("Location: /plvsystem/order/view/" . $order_id);
        exit;
    }
// --- ADMIN ACTION: REJECT / REQUEST REVISION ---
public function rejectStage() {
    if($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'commercial') die('Unauthorized');

    $assignment_id = $_POST['assignment_id'];
    $order_id = $_POST['order_id'];
    $worker_id = $_POST['worker_id'];
    $remark = trim($_POST['remark']);

    // 1. Handle Admin File Upload (Optional)
    if (isset($_FILES['admin_file']) && $_FILES['admin_file']['error'] == 0) {
        $this->uploadHelper($order_id, 'admin_file'); // Uploads the correction file
    }

    // 2. Set Status to 'revision' (so the designer sees the buttons again)
    $this->orderModel->setAssignmentStatus($assignment_id, 'revision');

    // 3. Notify the Worker
    $msg = "❌ Revision Requested: " . ($remark ?: "Please check the order details.");
    $this->notifModel->create($worker_id, $msg, "/plvsystem/order/view/$order_id");

    // 4. Log in History (Optional but good)
    // You might want to add a method to log remarks, but for now notifications cover it.

    header("Location: /plvsystem/order/view/" . $order_id);
    exit;
}

// --- ADMIN ACTION: APPROVE ---
public function approveStage() {
    if($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'commercial') die('Unauthorized');

    $assignment_id = $_POST['assignment_id'];
    $order_id = $_POST['order_id'];
    $current_stage = strtolower(trim($_POST['current_stage']));
    
    // 1. Determine Next Stage
    if ($current_stage == 'created' || $current_stage == 'design') {
        $next_stage = 'printing';
    } elseif ($current_stage == 'printing') {
        $next_stage = 'delivery';
    } elseif ($current_stage == 'delivery') {
        $next_stage = 'completed';
    } else {
        $next_stage = $current_stage;
    }

    // 2. Upload Admin File (Optional)
    if (!empty($_FILES['admin_file']['name'])) {
        $this->uploadHelper($order_id, 'admin_file');
    }

    // 3. Finalize Stage
    $this->orderModel->completeStage($assignment_id, $order_id, $current_stage, $next_stage);

    // 4. Notify Worker
    $this->notifModel->create($_POST['worker_id'], "✅ Work Approved!", "/plvsystem/order/view/$order_id");

    // --- THE FIX: Add '?assign_needed=1' to the URL ---
    if ($next_stage != 'completed') {
        header("Location: /plvsystem/order/view/" . $order_id . "?assign_needed=1");
    } else {
        header("Location: /plvsystem/order/view/" . $order_id);
    }
    exit;
}
    public function edit($id) {
        if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'commercial') die("Access Denied");

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'client' => $_POST['client_name'],
                'type' => $_POST['plv_type'],
                'desc' => $_POST['description'],
                'deadline' => $_POST['deadline'],
                'priority' => $_POST['priority']
            ];

            if ($this->orderModel->update($id, $data)) {
                header("Location: /plvsystem/order/view/" . $id);
                exit;
            }
        }

        $order = $this->orderModel->getById($id);
        require 'views/orders/edit.php';
    }

    public function recent() {
        $files = $this->orderModel->getAllRecentFiles(); // Fixed variable name
        require 'views/orders/recent.php';
    }


    // Get Data for the Assignment Popup (called via AJAX)
    public function getAssignData() {
        if ($_SESSION['role'] != 'admin') die(json_encode(['error' => 'Unauthorized']));

        $currentStage = $_GET['current_stage'];
        
        // LOGIC: Determine who is next based on current stage
        // Flow: Created -> Design -> Printing -> Delivery
        
        if ($currentStage == 'created' || $currentStage == 'design') {
            $users = $this->userModel->getDesigners();
            $nextStage = 'design';
        } elseif ($currentStage == 'printing') {
            $users = $this->userModel->getPrinters();
            $nextStage = 'printing';
        } elseif ($currentStage == 'delivery') {
            $users = $this->userModel->getDelivery();
            $nextStage = 'delivery';
        } else {
            $users = [];
            $nextStage = '';
        }
        
        header('Content-Type: application/json');
        echo json_encode(['users' => $users, 'nextStage' => $nextStage]);
        exit;
    }
    // --- RECEIPT PAGE METHOD ---
    public function receipt($assignmentId) {
        // 1. Database Connection
        $db = (new Database())->getConnection();
        
        // 2. Fetch Assignment + Order + Worker Details
        $stmt = $db->prepare("
            SELECT a.*, o.client_name, o.created_at as order_date, u.name as worker_name, u.role as worker_role
            FROM assignments a
            JOIN orders o ON a.order_id = o.id
            JOIN users u ON a.user_id = u.id
            WHERE a.id = ?
        ");
        $stmt->execute([$assignmentId]);
        $data = $stmt->fetch();

        // 3. Security Checks
        if (!$data) {
            die("Error: Receipt not found.");
        }
        
        // Only allow the Worker who refused it OR an Admin
        if ($data['user_id'] != $_SESSION['user_id'] && $_SESSION['role'] != 'admin') {
            die("Access Denied: You do not have permission to view this receipt.");
        }

        // 4. Load the Receipt View
        if (file_exists('views/orders/receipt.php')) {
            require 'views/orders/receipt.php';
        } else {
            die("Error: The view file 'views/orders/receipt.php' is missing.");
        }
    }
    // --- CHAT SYSTEM: HANDLE SUBMISSION & NOTIFICATIONS ---
    public function addMessage() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $orderId = $_POST['order_id'];
            $userId = $_SESSION['user_id'];
            $message = trim($_POST['message']);
            $filePath = null;

            // 1. Handle File Upload if a file was attached
            if (isset($_FILES['chat_file']) && $_FILES['chat_file']['error'] == 0) {
                $uploadDir = 'public/uploads/chat/';
                // Create folder if it doesn't exist
                if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
                
                // Create a clean file name to prevent errors
                $fileName = time() . '_chat_' . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES['chat_file']['name']));
                $targetPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['chat_file']['tmp_name'], $targetPath)) {
                    $filePath = $targetPath;
                }
            }

            // 2. Save Message to DB (Only if there is text OR a file)
            if (!empty($message) || $filePath !== null) {
                $this->orderModel->addChatMessage($orderId, $userId, $message, $filePath);
                
                // ==========================================
                // 3. SMART NOTIFICATION LOGIC
                // ==========================================
                $order = $this->orderModel->getById($orderId);
                
                // Get the worker currently assigned to this order's stage
                $assignment = $this->orderModel->getAssignmentByStage($orderId, $order['current_stage']);
                
                $usersToNotify = [];
                
                // A. Add the Order Creator (Commercial/Admin) to the notification list
                if (!empty($order['created_by'])) {
                    $usersToNotify[] = $order['created_by'];
                }
                
                // B. Add the Current Assigned Worker to the notification list
                if (!empty($assignment) && !empty($assignment['user_id'])) {
                    $usersToNotify[] = $assignment['user_id'];
                }
                
                // C. Clean up the list: Remove duplicates and REMOVE THE SENDER
                $usersToNotify = array_unique($usersToNotify);
                $usersToNotify = array_diff($usersToNotify, [$userId]); // Don't notify yourself
                
                // D. Send the notifications
                $senderName = $_SESSION['name'];
                $notifMsg = "💬 New message from $senderName on Order #$orderId";
                $notifLink = "/plvsystem/order/view/$orderId";
                
                foreach ($usersToNotify as $notifyId) {
                    $this->notifModel->create($notifyId, $notifMsg, $notifLink);
                }
            }

            // 4. Redirect back to the order page
            header("Location: /plvsystem/order/view/" . $orderId);
            exit;
        }
    }
}
?>
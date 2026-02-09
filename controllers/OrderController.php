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
                $desc .= "\n\n=== 🏗️ DÉTAILS PANNEAUX PRÉSENTOIRS ===";
                $desc .= "\n🏷️ Nom affiché: " . $_POST['display_name'];
                $desc .= "\n🖼️ Logo: " . $_POST['has_logo'];
                
                for ($i = 1; $i <= 6; $i++) {
                    $w = $_POST["p{$i}_w"] ?? '';
                    $h = $_POST["p{$i}_h"] ?? '';
                    $content = $_POST["p{$i}_content"] ?? [];
                    
                    if (!empty($w) || !empty($content)) {
                        $desc .= "\n\n--- [ PANNEAU #$i ] ---";
                        if($w && $h) $desc .= "\n📏 Taille: {$w}cm (H) x {$h}cm (V)";
                        if (!empty($content)) {
                            $desc .= "\n📦 Contenu: " . implode(', ', $content);
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
            
            // 5. Upload Files (This was crashing because uploadHelper was missing)
            if ($newOrderId) {
                $this->uploadHelper($newOrderId, 'ref_file_manuscript');
                $this->uploadHelper($newOrderId, 'ref_file_facade');
                $this->uploadHelper($newOrderId, 'ref_file_logo'); 
                
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
        $order = $this->orderModel->getById($id);
        if (!$order) {
            header("Location: /plvsystem/dashboard");
            exit;
        }

        $files = $this->orderModel->getFiles($id);
        $designers = $this->userModel->getDesigners();
        $printers = $this->userModel->getPrinters();
        $delivery = $this->userModel->getDelivery();
        
        if ($_SESSION['role'] !== 'admin') {
            $assignment = $this->orderModel->getUserAssignment($id, $_SESSION['user_id']);
        } else {
            $assignment = $this->orderModel->getAssignment($id, $order['current_stage']);
        }
        
        require 'views/orders/view.php';
    }

    public function assign() {
        if ($_SESSION['role'] != 'admin') die('Unauthorized');
        $this->orderModel->assignUser($_POST['order_id'], $_POST['user_id'], $_POST['stage']);
        
        // Notify the user
        $this->notifModel->create($_POST['user_id'], "You have a new task!", "/plvsystem/order/view/".$_POST['order_id']);
        
        header("Location: /plvsystem/order/view/" . $_POST['order_id']);
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

    public function complete() {
        $currentStage = strtolower(trim($_POST['current_stage'])); 
        $orderId = $_POST['order_id'];
        $assignmentId = $_POST['assignment_id'];

        if ($currentStage == 'design' || $currentStage == 'created') {
            $nextStage = 'printing';
            $orderStatus = 'pending'; 
        } elseif ($currentStage == 'printing') {
            $nextStage = 'delivery';
            $orderStatus = 'pending'; 
        } elseif ($currentStage == 'delivery') {
            $nextStage = 'completed';
            $orderStatus = 'completed';
        } else {
            $nextStage = $currentStage; 
            $orderStatus = 'pending';
        }
        
        $this->orderModel->completeAssignment($assignmentId, $orderId, $nextStage, $orderStatus);
        
        $order = $this->orderModel->getById($orderId);
        $this->notifModel->create($order['created_by'], "✅ Stage '$currentStage' finished.", "/plvsystem/order/view/$orderId");

        header("Location: /plvsystem/order/view/" . $orderId);
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
        $recentFiles = $this->orderModel->getAllRecentFiles(); // Fixed variable name
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
}
?>
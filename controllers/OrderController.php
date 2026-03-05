<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
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
        if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'commercial') {
            $assignment = $this->orderModel->getAssignmentByStage($id, $order['current_stage']);
        } 
        // CASE B: YOU ARE A WORKER (Designer/Printer)
        else {
            $assignment = $this->orderModel->getUserAssignment($id, $_SESSION['user_id']);
        }

        // 👉 THE FIX FOR THE HOURGLASS / ARRAY WARNING:
        // If the database returns false (meaning no assignment exists), force it to be an empty array
        if ($assignment === false) {
            $assignment = [];
        }

        require 'views/orders/view.php';
    }


    public function assign() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $order_id = $_POST['order_id'];
            $user_id = $_POST['user_id'];
            $current_stage = $_POST['stage'];

            // --- 🔴 THE ADMIN LOCK (ANTI-OVERLAP) ---
            // If the stage is 'created', we check the 'design' stage since that's what gets claimed
            $checkStage = ($current_stage == 'created') ? 'design' : $current_stage;
            
            // Look to see if someone just claimed this!
            $existing = $this->orderModel->getAssignmentByStage($order_id, $checkStage);
            
            if (!empty($existing) && $existing['status'] != 'refused') {
                // Task is already taken! Stop the assignment and redirect with an error
                header("Location: /plvsystem/dashboard?error=already_claimed");
                exit;
            }
            // ----------------------------------------

            // Don't create a new User model. Use the one from the constructor.
            $worker = $this->userModel->findById($user_id);
            
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
            // We also make sure the status is active now!
            $this->orderModel->updateStage($order_id, $newStage);
            
            // (Optional safety: update main order status to active so it drops from available tasks)
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("UPDATE orders SET status = 'active' WHERE id = ?");
            $stmt->execute([$order_id]);

            // Notification
            if(isset($this->notifModel)) {
                $this->notifModel->create($user_id, "👉 Nouvelle tâche assignée: Commande #$order_id", "/plvsystem/order/view/$order_id");
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
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $assignment_id = $_POST['assignment_id'];
            $order_id = $_POST['order_id'];
            
            // 1. Get the order to see what stage we are currently in
            $order = $this->orderModel->getById($order_id);
            $current_stage = $order['current_stage'];

            // 2. 🤖 THE AUTOMATION BRAIN: Determine the next stage automatically
            $nextStage = 'completed';
            $orderStatus = 'completed'; // Default to finishing the whole order

            if ($current_stage == 'created' || $current_stage == 'design') {
                $nextStage = 'printing';
                $orderStatus = 'active'; // Keep active so it goes to the Printer's pool
            } elseif ($current_stage == 'printing') {
                $nextStage = 'delivery';
                $orderStatus = 'active'; // Keep active so it goes to the Delivery pool
            }

            // 3. Connect to Database directly for a bulletproof update
            $db = (new Database())->getConnection();
            
            // A. Mark this specific worker's task as 100% DONE
            $stmt = $db->prepare("UPDATE assignments SET status = 'completed' WHERE id = ?");
            $stmt->execute([$assignment_id]);

            // B. Move the main order to the NEXT STAGE
            $stmt2 = $db->prepare("UPDATE orders SET current_stage = ?, status = ? WHERE id = ?");
            $stmt2->execute([$nextStage, $orderStatus, $order_id]);

            // C. Send Notification to Admin/Creator
            if(isset($this->notifModel)) {
                $this->notifModel->create($order['created_by'], "✅ L'étape '$current_stage' est terminée. Commande #$order_id est passée à '$nextStage'", "/plvsystem/order/view/$order_id");
            }

            // Redirect back to dashboard since their part is done!
            header("Location: /plvsystem/dashboard");
            exit;
        }
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
    // --- CHAT SYSTEM: HANDLE SUBMISSION & NOTIFICATIONS (AJAX READY) ---
    public function addMessage() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $orderId = $_POST['order_id'];
            $userId = $_SESSION['user_id'];
            $message = trim($_POST['message']);
            $filePath = null;
            $dbFilePath = null;

            // 1. Handle File Upload
            if (isset($_FILES['chat_file']) && $_FILES['chat_file']['error'] == 0) {
                $uploadDir = 'public/uploads/chat/';
                if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $fileName = time() . '_chat_' . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES['chat_file']['name']));
                $targetPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['chat_file']['tmp_name'], $targetPath)) {
                    $filePath = $targetPath;
                    $dbFilePath = $targetPath; // What we save in the DB
                }
            }

            // 2. Save Message to DB
            if (!empty($message) || $filePath !== null) {
                $this->orderModel->addChatMessage($orderId, $userId, $message, $dbFilePath);
                
                // 3. Smart Notifications (Same as before)
                $order = $this->orderModel->getById($orderId);
                $assignment = $this->orderModel->getAssignmentByStage($orderId, $order['current_stage']);
                
                $usersToNotify = [];
                if (!empty($order['created_by'])) $usersToNotify[] = $order['created_by'];
                if (!empty($assignment) && !empty($assignment['user_id'])) $usersToNotify[] = $assignment['user_id'];
                
                $usersToNotify = array_unique($usersToNotify);
                $usersToNotify = array_diff($usersToNotify, [$userId]);
                
                $senderName = $_SESSION['name'];
                $notifMsg = "💬 New message from $senderName on Order #$orderId";
                $notifLink = "/plvsystem/order/view/$orderId";
                
                foreach ($usersToNotify as $notifyId) {
                    $this->notifModel->create($notifyId, $notifMsg, $notifLink);
                }
                
                // ==========================================
                // 4. AJAX JSON RESPONSE
                // ==========================================
                if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => htmlspecialchars($message), // Secure output
                        'file_path' => $dbFilePath,
                        'user_role' => ucfirst($_SESSION['role']),
                        'created_at' => date('H:i, M d')
                    ]);
                    exit;
                }
            }

            // Fallback for non-AJAX requests
            header("Location: /plvsystem/order/view/" . $orderId);
            exit;
        }
    }
public function claim() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $orderId = $_POST['order_id'];
            $userId = $_SESSION['user_id'];
            $role = strtolower($_SESSION['role']);

            // 1. Get the current order to see where it is at
            $order = $this->orderModel->getById($orderId);
            $newStage = $order['current_stage'];

            // 2. FORCE THE STAGE FORWARD: If a Designer claims a 'created' task, bump it to 'design'
            if ($role === 'designer' && $order['current_stage'] === 'created') {
                $newStage = 'design';
            }

            // 3. Try to claim the task using the newly calculated stage
            if ($this->orderModel->claimTask($orderId, $userId, $newStage)) {
                
                // Send Notification
                $workerName = $_SESSION['name'];
                if(isset($this->notifModel)) {
                    $this->notifModel->create($order['created_by'], "🖐️ La commande #$orderId a été récupérée par $workerName", "/plvsystem/order/view/$orderId");
                }
                
                // Redirect back to view page
                header("Location: /plvsystem/order/view/" . $orderId);
            } else {
                // Someone else grabbed it!
                header("Location: /plvsystem/dashboard?error=already_claimed");
            }
            exit;
        }
    }
    
    // --- 🗑️ DELETE ORDER ROUTE ---
    public function delete($id) {
        // Security check: Only Admins can delete
        if ($_SESSION['role'] !== 'admin') {
            header("Location: /plvsystem/dashboard");
            exit;
        }

        $this->orderModel->deleteOrder($id);
        header("Location: /plvsystem/dashboard?msg=deleted");
        exit;
    }

    // --- 📊 EXPORT REPORT ROUTE (WITH DATE RANGE) ---
    public function export() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SESSION['role'] == 'admin') {
            
            // Get dates from the modal
            $start = $_POST['start_date'] . ' 00:00:00';
            $end = $_POST['end_date'] . ' 23:59:59';
            
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("SELECT * FROM orders WHERE created_at BETWEEN ? AND ? ORDER BY created_at DESC");
            $stmt->execute([$start, $end]);
            $orders = $stmt->fetchAll();

            // Tell browser it's a CSV download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="LAP_Report_' . $_POST['start_date'] . '_to_' . $_POST['end_date'] . '.csv"');
            
            $output = fopen('php://output', 'w');
            fputs($output, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF))); // UTF-8 BOM for Excel
            
            // Headers
            fputcsv($output, ['Ref ID', 'Client', 'Commercial', 'Type', 'Priorite', 'Etape Actuelle', 'Statut', 'Date de Creation']);
            
            // Data
            foreach ($orders as $o) {
                fputcsv($output, [
                    '#' . $o['id'],
                    $o['client_name'],
                    $o['commercial_name'] ?? 'N/A',
                    $o['plv_type'],
                    $o['priority'],
                    strtoupper($o['current_stage']),
                    strtoupper($o['status']),
                    $o['created_at']
                ]);
            }
            fclose($output);
            exit;
        }
    }
}
?>
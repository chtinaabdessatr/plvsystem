<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'models/Order.php';
require_once 'models/User.php';
require_once 'models/Notification.php';
require_once 'models/Log.php'; // 🕵️‍♂️ ENTERPRISE LOGGING ADDED

class OrderController {
    private $orderModel;
    private $userModel;
    private $notifModel;
    private $logModel; // 🕵️‍♂️ Added Log property

    public function __construct() {
        if (!isset($_SESSION['user_id'])) header("Location: /plvsystem/auth/login");
        $db = (new Database())->getConnection();
        $this->orderModel = new Order($db);
        $this->userModel = new User($db);
        $this->notifModel = new Notification($db);
        $this->logModel = new Log($db); // 🕵️‍♂️ Initialize Logger
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // 1. Start Description
            $desc = isset($_POST['description']) ? trim($_POST['description']) : '';
            
            // 2. IF PANNEAU: Format the 6 Panels Logic
            if ($_POST['plv_type'] == 'Panneau') {
                $desc .= "\n\n=== SPÉCIFICATIONS TECHNIQUES ===";
                $desc .= "\nNom du projet : " . $_POST['display_name'];
                $desc .= "\nInclusion Logo : " . $_POST['has_logo'];
                
                for ($i = 1; $i <= 6; $i++) {
                    $w = $_POST["p{$i}_w"] ?? '';
                    $h = $_POST["p{$i}_h"] ?? '';
                    $content = $_POST["p{$i}_content"] ?? [];
                    
                    if (!empty($w) || !empty($content)) {
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
            
            // 5. Upload Files & LOG
            if ($newOrderId) {
                // 🕵️‍♂️ LOG ACTION: Order Creation
                $this->logModel->logAction($_SESSION['user_id'], 'CRÉATION COMMANDE', "Création de la commande #$newOrderId pour le client {$_POST['client_name']}");

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

    private function uploadHelper($orderId, $inputName) {
        if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] == 0) {
            $uploadDir = 'public/uploads/';
            if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $fileName = time() . '_' . $inputName . '_' . basename($_FILES[$inputName]['name']);
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES[$inputName]['tmp_name'], $targetPath)) {
                $this->orderModel->addFile($orderId, $_SESSION['user_id'], $targetPath, 'creation');
            }
        }
    }

    public function view($id) {
        $order = $this->orderModel->getById($id);
        if (!$order) { header("Location: /plvsystem/dashboard"); exit; }

        $files = $this->orderModel->getFiles($id);
        $history = $this->orderModel->getOrderHistory($id);
        $chatMessages = $this->orderModel->getChatMessages($id);

        $assignment = [];
        if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'commercial') {
            $assignment = $this->orderModel->getAssignmentByStage($id, $order['current_stage']);
        } else {
            $assignment = $this->orderModel->getUserAssignment($id, $_SESSION['user_id']);
        }

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

            $checkStage = ($current_stage == 'created') ? 'design' : $current_stage;
            $existing = $this->orderModel->getAssignmentByStage($order_id, $checkStage);
            
            if (!empty($existing) && $existing['status'] != 'refused') {
                header("Location: /plvsystem/dashboard?error=already_claimed");
                exit;
            }

            $worker = $this->userModel->findById($user_id);
            $newStage = $current_stage;
            
            if ($worker['role'] == 'designer') {
                $newStage = 'design';
            } elseif ($worker['role'] == 'printer') {
                $newStage = 'printing';
            } elseif ($worker['role'] == 'delivery') {
                $newStage = 'delivery';
            }

            $this->orderModel->assignUser($order_id, $user_id, $newStage);
            $this->orderModel->updateStage($order_id, $newStage);
            
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("UPDATE orders SET status = 'active' WHERE id = ?");
            $stmt->execute([$order_id]);

            // 🕵️‍♂️ LOG ACTION: Admin Assignment
            $this->logModel->logAction($_SESSION['user_id'], 'ATTRIBUTION TÂCHE', "Attribution manuelle de la commande #$order_id à {$worker['name']} pour l'étape: $newStage");

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

        $order = $this->orderModel->getById($orderId);
        $adminId = $order['created_by'];
        $workerName = $_SESSION['name'];

        if ($status == 'refused') {
            $msg = "❌ ALERT: $workerName REFUSED Order #$orderId.";
            $this->orderModel->updateOrderStatus($orderId, 'pending'); 
            $this->notifModel->create($adminId, $msg, "/plvsystem/order/view/$orderId");
            
            // 🕵️‍♂️ LOG ACTION: Task Refused
            $this->logModel->logAction($_SESSION['user_id'], 'REFUS TÂCHE', "L'utilisateur a refusé la commande #$orderId");
        } else {
            $msg = "✅ $workerName accepted Order #$orderId.";
            $this->notifModel->create($adminId, $msg, "/plvsystem/order/view/$orderId");
        }

        header("Location: /plvsystem/order/view/" . $orderId);
    }

    public function upload() {
        $orderId = $_POST['order_id'];
        $this->uploadHelper($orderId, 'file');
        
        // 🕵️‍♂️ LOG ACTION: File Upload
        $this->logModel->logAction($_SESSION['user_id'], 'UPLOAD FICHIER', "Nouveau fichier uploadé pour la commande #$orderId");

        header("Location: /plvsystem/order/view/" . $orderId);
    }

    public function requestReview() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $assignment_id = $_POST['assignment_id'];
            $order_id = $_POST['order_id'];
            
            $order = $this->orderModel->getById($order_id);
            $current_stage = $order['current_stage'];

            $nextStage = 'completed';
            $orderStatus = 'completed'; 

            if ($current_stage == 'created' || $current_stage == 'design') {
                $nextStage = 'printing';
                $orderStatus = 'active'; 
            } elseif ($current_stage == 'printing') {
                $nextStage = 'delivery';
                $orderStatus = 'active'; 
            }

            $db = (new Database())->getConnection();
            
            $stmt = $db->prepare("UPDATE assignments SET status = 'completed' WHERE id = ?");
            $stmt->execute([$assignment_id]);

            $stmt2 = $db->prepare("UPDATE orders SET current_stage = ?, status = ? WHERE id = ?");
            $stmt2->execute([$nextStage, $orderStatus, $order_id]);

            // 🕵️‍♂️ LOG ACTION: Stage Completed
            $this->logModel->logAction($_SESSION['user_id'], 'ÉTAPE TERMINÉE', "L'étape '$current_stage' est terminée. Commande #$order_id passée à '$nextStage'");

            if(isset($this->notifModel)) {
                $this->notifModel->create($order['created_by'], "✅ L'étape '$current_stage' est terminée. Commande #$order_id est passée à '$nextStage'", "/plvsystem/order/view/$order_id");
            }

            header("Location: /plvsystem/dashboard");
            exit;
        }
    }

    public function rejectStage() {
        if($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'commercial') die('Unauthorized');

        $assignment_id = $_POST['assignment_id'];
        $order_id = $_POST['order_id'];
        $worker_id = $_POST['worker_id'];
        $remark = trim($_POST['remark']);

        if (isset($_FILES['admin_file']) && $_FILES['admin_file']['error'] == 0) {
            $this->uploadHelper($order_id, 'admin_file'); 
        }

        $this->orderModel->setAssignmentStatus($assignment_id, 'revision');

        // 🕵️‍♂️ LOG ACTION: Revision Requested
        $this->logModel->logAction($_SESSION['user_id'], 'RÉVISION DEMANDÉE', "L'admin a demandé une révision pour la commande #$order_id. Motif: $remark");

        $msg = "❌ Revision Requested: " . ($remark ?: "Please check the order details.");
        $this->notifModel->create($worker_id, $msg, "/plvsystem/order/view/$order_id");

        header("Location: /plvsystem/order/view/" . $order_id);
        exit;
    }

    public function approveStage() {
        if($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'commercial') die('Unauthorized');

        $assignment_id = $_POST['assignment_id'];
        $order_id = $_POST['order_id'];
        $current_stage = strtolower(trim($_POST['current_stage']));
        
        if ($current_stage == 'created' || $current_stage == 'design') {
            $next_stage = 'printing';
        } elseif ($current_stage == 'printing') {
            $next_stage = 'delivery';
        } elseif ($current_stage == 'delivery') {
            $next_stage = 'completed';
        } else {
            $next_stage = $current_stage;
        }

        if (!empty($_FILES['admin_file']['name'])) {
            $this->uploadHelper($order_id, 'admin_file');
        }

        $this->orderModel->completeStage($assignment_id, $order_id, $current_stage, $next_stage);

        // 🕵️‍♂️ LOG ACTION: Admin Approval
        $this->logModel->logAction($_SESSION['user_id'], 'APPROBATION ADMIN', "L'admin a validé l'étape '$current_stage' de la commande #$order_id");

        $this->notifModel->create($_POST['worker_id'], "✅ Work Approved!", "/plvsystem/order/view/$order_id");

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
                // 🕵️‍♂️ LOG ACTION: Order Edited
                $this->logModel->logAction($_SESSION['user_id'], 'MODIFICATION COMMANDE', "La commande #$id a été modifiée.");
                
                header("Location: /plvsystem/order/view/" . $id);
                exit;
            }
        }

        $order = $this->orderModel->getById($id);
        require 'views/orders/edit.php';
    }

    public function recent() {
        $files = $this->orderModel->getAllRecentFiles(); 
        require 'views/orders/recent.php';
    }

    public function getAssignData() {
        if ($_SESSION['role'] != 'admin') die(json_encode(['error' => 'Unauthorized']));

        $currentStage = $_GET['current_stage'];
        
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

    public function receipt($assignmentId) {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("
            SELECT a.*, o.client_name, o.created_at as order_date, u.name as worker_name, u.role as worker_role
            FROM assignments a
            JOIN orders o ON a.order_id = o.id
            JOIN users u ON a.user_id = u.id
            WHERE a.id = ?
        ");
        $stmt->execute([$assignmentId]);
        $data = $stmt->fetch();

        if (!$data) {
            die("Error: Receipt not found.");
        }
        
        if ($data['user_id'] != $_SESSION['user_id'] && $_SESSION['role'] != 'admin') {
            die("Access Denied: You do not have permission to view this receipt.");
        }

        if (file_exists('views/orders/receipt.php')) {
            require 'views/orders/receipt.php';
        } else {
            die("Error: The view file 'views/orders/receipt.php' is missing.");
        }
    }

    public function addMessage() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $orderId = $_POST['order_id'];
            $userId = $_SESSION['user_id'];
            $message = trim($_POST['message']);
            $filePath = null;
            $dbFilePath = null;

            if (isset($_FILES['chat_file']) && $_FILES['chat_file']['error'] == 0) {
                $uploadDir = 'public/uploads/chat/';
                if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $fileName = time() . '_chat_' . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES['chat_file']['name']));
                $targetPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['chat_file']['tmp_name'], $targetPath)) {
                    $filePath = $targetPath;
                    $dbFilePath = $targetPath; 
                }
            }

            if (!empty($message) || $filePath !== null) {
                $this->orderModel->addChatMessage($orderId, $userId, $message, $dbFilePath);
                
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
                
                if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => htmlspecialchars($message),
                        'file_path' => $dbFilePath,
                        'user_role' => ucfirst($_SESSION['role']),
                        'created_at' => date('H:i, M d')
                    ]);
                    exit;
                }
            }

            header("Location: /plvsystem/order/view/" . $orderId);
            exit;
        }
    }

    public function claim() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $orderId = $_POST['order_id'];
            $userId = $_SESSION['user_id'];
            $role = strtolower($_SESSION['role']);

            $order = $this->orderModel->getById($orderId);
            $newStage = $order['current_stage'];

            if ($role === 'designer' && $order['current_stage'] === 'created') {
                $newStage = 'design';
            }

            if ($this->orderModel->claimTask($orderId, $userId, $newStage)) {
                
                // 🕵️‍♂️ LOG ACTION: Task Claimed
                $this->logModel->logAction($_SESSION['user_id'], 'RÉCUPÉRATION TÂCHE', "L'utilisateur a récupéré la commande #$orderId pour l'étape '$newStage'");

                $workerName = $_SESSION['name'];
                if(isset($this->notifModel)) {
                    $this->notifModel->create($order['created_by'], "🖐️ La commande #$orderId a été récupérée par $workerName", "/plvsystem/order/view/$orderId");
                }
                
                header("Location: /plvsystem/order/view/" . $orderId);
            } else {
                header("Location: /plvsystem/dashboard?error=already_claimed");
            }
            exit;
        }
    }
    
    // --- 🗑️ DELETE ORDER ROUTE ---
    public function delete($id) {
        if ($_SESSION['role'] !== 'admin') {
            header("Location: /plvsystem/dashboard");
            exit;
        }

        // 🕵️‍♂️ LOG ACTION: Order Deleted
        $this->logModel->logAction($_SESSION['user_id'], 'SUPPRESSION COMMANDE', "L'administrateur a définitivement supprimé la commande #$id");

        $this->orderModel->deleteOrder($id);
        header("Location: /plvsystem/dashboard?msg=deleted");
        exit;
    }

    // --- 📊 EXPORT REPORT ROUTE (WITH DATE RANGE) ---
    public function export() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SESSION['role'] == 'admin') {
            
            $start = $_POST['start_date'] . ' 00:00:00';
            $end = $_POST['end_date'] . ' 23:59:59';
            
            $db = (new Database())->getConnection();
            $stmt = $db->prepare("SELECT * FROM orders WHERE created_at BETWEEN ? AND ? ORDER BY created_at DESC");
            $stmt->execute([$start, $end]);
            $orders = $stmt->fetchAll();

            // 🕵️‍♂️ LOG ACTION: Data Exported
            $this->logModel->logAction($_SESSION['user_id'], 'EXPORT DONNÉES', "Exportation CSV de l'historique des commandes du {$_POST['start_date']} au {$_POST['end_date']}");

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="LAP_Report_' . $_POST['start_date'] . '_to_' . $_POST['end_date'] . '.csv"');
            
            $output = fopen('php://output', 'w');
            fputs($output, $bom =(chr(0xEF) . chr(0xBB) . chr(0xBF))); 
            
            fputcsv($output, ['Ref ID', 'Client', 'Commercial', 'Type', 'Priorite', 'Etape Actuelle', 'Statut', 'Date de Creation']);
            
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
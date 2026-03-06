<?php
require_once 'models/Order.php';
require_once 'models/Log.php';

class KanbanController {
    private $orderModel;
    private $logModel;

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header("Location: /plvsystem/auth/login");
            exit;
        }
        $db = (new Database())->getConnection();
        $this->orderModel = new Order($db);
        $this->logModel = new Log($db);
    }

    // --- 🗂️ LOAD THE KANBAN BOARD ---
    public function index() {
        // Fetch all orders from the last 30 days
        $allOrders = $this->orderModel->getAll(30);

        // Group the orders by their exact stage
        $board = [
            'created' => [],
            'design' => [],
            'printing' => [],
            'delivery' => [],
            'completed' => []
        ];

        foreach ($allOrders as $o) {
            $stage = strtolower($o['current_stage']);
            // Make sure the stage actually exists in our array to prevent errors
            if (array_key_exists($stage, $board)) {
                $board[$stage][] = $o;
            }
        }

        require 'views/kanban/index.php';
    }

    // --- 🔄 DRAG & DROP AJAX ENDPOINT ---
    public function updateStage() {
        // Only Admins can force-move cards via Drag & Drop
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SESSION['role'] == 'admin') {
            $order_id = $_POST['order_id'];
            $new_stage = $_POST['new_stage'];

            $db = (new Database())->getConnection();
            
            // Update the main order stage
            $stmt = $db->prepare("UPDATE orders SET current_stage = ? WHERE id = ?");
            if ($stmt->execute([$new_stage, $order_id])) {
                
                // 🕵️‍♂️ LOG ACTION: Track the Drag & Drop
                $this->logModel->logAction($_SESSION['user_id'], 'DÉPLACEMENT KANBAN', "L'admin a déplacé manuellement la commande #$order_id vers l'étape '$new_stage'");

                echo json_encode(['success' => true]);
                exit;
            }
        }
        
        // If it fails or is a normal worker trying to hack the system
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    }
}
?>
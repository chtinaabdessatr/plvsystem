<?php
require_once 'models/Order.php';
require_once 'models/Log.php'; // 🕵️‍♂️ ENTERPRISE LOGGING ADDED

class DashboardController {
    public function index() {
        // 1. CHECK LOGIN
        if (!isset($_SESSION['user_id'])) {
            header("Location: /plvsystem/auth/login");
            exit;
        }
        
        // 2. INITIALIZE DB & MODEL
        $db = (new Database())->getConnection();
        $orderModel = new Order($db);
        $logModel = new Log($db); // 🕵️‍♂️ Initialize Logger
        
        $role = $_SESSION['role'];
        $userId = $_SESSION['user_id'];
        
        $availableOrders = []; // Default empty
        $stats = []; // Default empty
        
        // 3. FETCH ORDERS BASED ON ROLE & SEARCH
        if (isset($_GET['search']) && trim($_GET['search']) !== '') {
            $searchTerm = trim($_GET['search']);
            
            // 🔍 IF SEARCHING: Use the search model we added
            $orders = $orderModel->searchOrders($searchTerm, $role, $userId);
            
            // 🕵️‍♂️ LOG ACTION: Track Searches
            $logModel->logAction($userId, 'RECHERCHE', "Recherche effectuée sur le tableau de bord avec le mot-clé : '$searchTerm'");
            
            // Calculate Dashboard Stats for Admin (based on search results)
            if ($role == 'admin') {
                $stats = [
                    'total' => count($orders),
                    'design' => 0, 'printing' => 0, 'delivery' => 0, 'completed' => 0
                ];
                foreach($orders as $o) {
                    if($o['current_stage'] == 'design') $stats['design']++;
                    elseif($o['current_stage'] == 'printing') $stats['printing']++;
                    elseif($o['current_stage'] == 'delivery') $stats['delivery']++;
                    elseif($o['current_stage'] == 'completed') $stats['completed']++;
                }
            }
            
        } else {
            // 📂 NORMAL DASHBOARD LOAD
            if ($role == 'admin') {
                // Admins see EVERYTHING
                $orders = $orderModel->getAll();
                
                // Calculate Dashboard Stats for Admin
                $stats = [
                    'total' => count($orders),
                    'design' => 0,
                    'printing' => 0,
                    'delivery' => 0,
                    'completed' => 0
                ];
                foreach($orders as $o) {
                    if($o['current_stage'] == 'design') $stats['design']++;
                    elseif($o['current_stage'] == 'printing') $stats['printing']++;
                    elseif($o['current_stage'] == 'delivery') $stats['delivery']++;
                    elseif($o['current_stage'] == 'completed') $stats['completed']++;
                }

            } elseif ($role == 'commercial') {
                // Commercials see their own orders
                $orders = $orderModel->getByCommercial($userId);
                
            } else {
                // WORKERS: Get their claimed active tasks AND the available pool
                if(method_exists($orderModel, 'getUserActiveOrders')){
                    $orders = $orderModel->getUserActiveOrders($userId);
                } else {
                    $orders = $orderModel->getOrdersForUser($userId);
                }
                
                // Get the unassigned orders for the "Available Tasks" green box
                $availableOrders = $orderModel->getAvailableOrders($role);
                
                // Keep your old pending tasks variable just in case your view still needs it
                $myTasks = $orderModel->getUserPendingTasks($userId);
            }
        }

        // 4. LOAD THE VIEW
        require 'views/dashboard/index.php';
    }
}
?>
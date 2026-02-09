<?php
require_once 'models/Order.php';

class DashboardController {
    public function index() {
        if (!isset($_SESSION['user_id'])) header("Location: /plvsystem/auth/login");
        
        $db = (new Database())->getConnection();
        $orderModel = new Order($db);
        
        // 1. FETCH ORDERS BASED ON ROLE
        if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'commercial') {
            // Admins see EVERYTHING
            $orders = $orderModel->getAll();

                    // 3. CALCULATE DASHBOARD STATS (New!)
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

        
        } else {
            // Workers see ONLY THEIR ASSIGNMENTS
            $orders = $orderModel->getOrdersForUser($_SESSION['user_id']);
            // 2. Get MY pending tasks (Action Required)
             $myTasks = $orderModel->getUserPendingTasks($_SESSION['user_id']);
        }
        
        

        
        require 'views/dashboard/index.php';
    }
}
?>
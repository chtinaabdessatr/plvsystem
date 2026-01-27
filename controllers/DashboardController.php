<?php
require_once 'models/Order.php';

class DashboardController {
    public function index() {
        if (!isset($_SESSION['user_id'])) header("Location: /plvsystem/auth/login");
        
        $db = (new Database())->getConnection();
        $orderModel = new Order($db);
        
        // 1. Get ALL orders (for the main table)
        $orders = $orderModel->getAll();
        
        // 2. Get MY pending tasks (New Feature)
        $myTasks = $orderModel->getUserPendingTasks($_SESSION['user_id']);
        
        require 'views/dashboard/index.php';
    }
}
?>
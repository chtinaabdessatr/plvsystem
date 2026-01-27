<?php
// Determine the current page for active link styling
$current_url = $_SERVER['REQUEST_URI'];

// FETCH NOTIFICATIONS LOGIC
$notif_count = 0;
if(isset($_SESSION['user_id'])) {
    // Check if classes are already loaded to avoid errors
    if(!class_exists('Database')) require_once __DIR__ . '/../../config/Database.php';
    if(!class_exists('Notification')) require_once __DIR__ . '/../../models/Notification.php';
    
    $db_notif = (new Database())->getConnection();
    $notifObj = new Notification($db_notif);
    // Fetch only unread notifications
    $my_notifs = $notifObj->getUnread($_SESSION['user_id']);
    $notif_count = count($my_notifs);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LAP PLV System</title>
    <link rel="stylesheet" href="/plvsystem/public/css/style.css">
    <style>
        /* Extra style for the notification banner */
        .alert-banner {
            background-color: #f39c12;
            color: white;
            padding: 15px 30px;
            text-align: center;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .alert-banner a {
            color: white;
            text-decoration: underline;
            margin-left: 10px;
        }
        .sidebar-badge {
            background: #e74c3c;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            float: right;
            margin-top: 2px;
        }
    </style>
</head>
<body>

<?php if(isset($_SESSION['user_id'])): ?>
    <div class="dashboard-container">
        
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="/plvsystem/dashboard" class="brand">LAP PLV</a>
            </div>
            <ul class="nav-links">
                <li>
                    <a href="/plvsystem/dashboard" class="<?= strpos($current_url, 'dashboard') !== false ? 'active' : '' ?>">
                        📊 Dashboard
                    </a>
                </li>
                
                <li>
                    <a href="/plvsystem/notification/index" class="<?= strpos($current_url, 'notification') !== false ? 'active' : '' ?>">
                        🔔 Notifications
                        <?php if($notif_count > 0): ?>
                            <span class="sidebar-badge"><?= $notif_count ?></span>
                        <?php endif; ?>
                    </a>
                </li>

                <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'commercial'): ?>
                <li>
                    <a href="/plvsystem/order/create" class="<?= strpos($current_url, 'order/create') !== false ? 'active' : '' ?>">
                        ➕ Add Order
                    </a>
                </li>
                <?php endif; ?>

                <?php if($_SESSION['role'] == 'admin'): ?>
                <li>
                    <a href="/plvsystem/user/index" class="<?= strpos($current_url, 'user') !== false ? 'active' : '' ?>">
                        👥 Users
                    </a>
                </li>
                <?php endif; ?>
                
                <li>
                    <a href="/plvsystem/order/recent" class="<?= strpos($current_url, 'order/recent') !== false ? 'active' : '' ?>">
                        📂 Recent Files
                    </a>
                </li>
            </ul>
        </aside>

        <div class="main-content-wrapper">
            
            <header class="top-bar">
                <div class="user-info">
                    Welcome, <strong><?= htmlspecialchars($_SESSION['name']) ?></strong> 
                    <span style="color:#aaa">|</span> 
                    <?= ucfirst($_SESSION['role']) ?>
                </div>
                <a href="/plvsystem/auth/logout" class="logout-btn">Logout ➔</a>
            </header>

            <?php if($notif_count > 0): ?>
                <div class="alert-banner">
                    <span>🔔 You have <?= $notif_count ?> unread notification(s).</span>
                    <a href="/plvsystem/notification/index">View Messages</a>
                </div>
            <?php endif; ?>

            <div class="page-content">

<?php else: ?>
    <div style="width: 100%; height: 100vh; display: flex; justify-content: center; align-items: center;">
<?php endif; ?>
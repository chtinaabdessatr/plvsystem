<?php
// Determine the current page for active link styling
$current_url = $_SERVER['REQUEST_URI'];

// INITIAL FETCH (Server Side) to show count immediately on load
$notif_count = 0;
if(isset($_SESSION['user_id'])) {
    if(!class_exists('Database')) require_once __DIR__ . '/../../config/Database.php';
    if(!class_exists('Notification')) require_once __DIR__ . '/../../models/Notification.php';
    
    $db_notif = (new Database())->getConnection();
    $notifObj = new Notification($db_notif);
    $my_notifs = $notifObj->getUnread($_SESSION['user_id']);
    $notif_count = count($my_notifs);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LAP PLV System</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="/plvsystem/public/css/app.css">
</head>
<body>

<?php if(isset($_SESSION['user_id'])): ?>
    
    <div class="app-wrapper">
        
        <aside class="sidebar">
            <div class="sidebar__brand">
                <i class="fa-solid fa-layer-group"></i> LAP PLV
            </div>
            
            <nav class="sidebar__nav">
                <ul>
                    <li>
                        <a href="/plvsystem/dashboard" class="<?= strpos($current_url, 'dashboard') !== false ? 'active' : '' ?>">
                            <i class="fa-solid fa-chart-pie"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    
                    <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'commercial'): ?>
                    <li class="nav-divider">Menu</li>
                    <li>
                        <a href="/plvsystem/order/create" class="<?= strpos($current_url, 'order/create') !== false ? 'active' : '' ?>">
                            <i class="fa-solid fa-circle-plus"></i>
                            <span>New Order</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if($_SESSION['role'] == 'admin'): ?>
                    <li>
                        <a href="/plvsystem/user/index" class="<?= strpos($current_url, 'user') !== false ? 'active' : '' ?>">
                            <i class="fa-solid fa-users"></i>
                            <span>Manage Users</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <li>
                        <a href="/plvsystem/order/recent" class="<?= strpos($current_url, 'order/recent') !== false ? 'active' : '' ?>">
                            <i class="fa-solid fa-folder-clock"></i>
                            <span>Recent Files</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <div class="sidebar__footer">
                 <small>System v1.0</small>
            </div>
        </aside>

        <div class="main-content">
            
            <header class="navbar">
                <div class="navbar__left">
                   <h2 class="navbar__title">Production Overview</h2>
                </div>

                <div class="navbar__right">
                    
                    <a href="/plvsystem/notification/index" class="nav-icon-btn" title="Notifications">
                        <i class="fa-solid fa-bell <?= $notif_count > 0 ? 'fa-shake' : '' ?>" style="--fa-animation-duration: 2s;"></i>
                        
                        <span id="nav-notif-badge" class="nav-badge-icon" style="<?= $notif_count == 0 ? 'display:none;' : '' ?>">
                            <?= $notif_count ?>
                        </span>
                    </a>

                    <div class="navbar__profile">
                        <div class="user-info">
                            <span class="user-name"><?= htmlspecialchars($_SESSION['name']) ?></span>
                            <span class="user-role badge badge--sm badge--neutral"><?= ucfirst($_SESSION['role']) ?></span>
                        </div>
                        <div class="avatar-circle">
                            <?= strtoupper(substr($_SESSION['name'], 0, 1)) ?>
                        </div>
                    </div>
                    <a href="/plvsystem/auth/logout" class="btn btn--sm btn--secondary">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </a>
                </div>
            </header>

            <div class="page-content">

<?php else: ?>
    <div class="auth-layout">
<?php endif; ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Only run if user is logged in (badge exists)
    const badge = document.getElementById('nav-notif-badge');
    const bellIcon = document.querySelector('.fa-bell');
    
    if(badge) {
        setInterval(() => {
            fetch('/plvsystem/notification/check')
                .then(response => response.json())
                .then(data => {
                    if(data.count > 0) {
                        // Update text
                        badge.innerText = data.count;
                        // Show badge
                        badge.style.display = 'flex';
                        // Add shake animation
                        bellIcon.classList.add('fa-shake');
                    } else {
                        // Hide badge if 0
                        badge.style.display = 'none';
                        bellIcon.classList.remove('fa-shake');
                    }
                })
                .catch(err => console.error('Notif check failed', err));
        }, 5000); // Checks every 5 seconds
    }
});
</script>
<?php include 'views/layouts/header.php'; ?>

<div class="app-container" style="max-width: 800px;">
    
    <div class="page-header">
        <h1 class="page-header__title">
            <span>Activity Log</span>
            Notifications History
        </h1>
        <a href="/plvsystem/dashboard" class="btn btn--secondary btn--sm">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <div class="card">
        <?php if (empty($notifications)): ?>
            <div style="text-align:center; padding:60px 20px; color:var(--text-light);">
                <i class="fa-regular fa-bell-slash" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.5;"></i>
                <p>You have no notifications yet.</p>
            </div>
        <?php else: ?>
            <ul class="notif-list">
                <?php foreach ($notifications as $n): ?>
                    <?php 
                        // Visual Logic
                        $isUnread = ($n['is_read'] == 0);
                        $itemClass = $isUnread ? 'notif-item--unread' : '';
                        $icon = $isUnread ? 'fa-envelope' : 'fa-envelope-open';
                    ?>
                    <li class="notif-item <?= $itemClass ?>">
                        <a href="<?= $n['link'] ?>" class="notif-link">
                            
                            <div class="notif-icon">
                                <i class="fa-solid <?= $icon ?>"></i>
                            </div>
                            
                            <div class="notif-content">
                                <div class="notif-message">
                                    <?= htmlspecialchars($n['message']) ?>
                                    <?php if($isUnread): ?>
                                        <span class="badge badge--sm" style="background:var(--danger); color:white; margin-left:8px;">NEW</span>
                                    <?php endif; ?>
                                </div>
                                <div class="notif-date">
                                    <i class="fa-regular fa-clock"></i> <?= date('M d, H:i', strtotime($n['created_at'])) ?>
                                </div>
                            </div>
                            
                            <div class="notif-arrow">
                                <i class="fa-solid fa-chevron-right"></i>
                            </div>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<?php include 'views/layouts/footer.php'; ?>
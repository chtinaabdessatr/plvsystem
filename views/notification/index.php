<?php include 'views/layouts/header.php'; ?>

<div class="header-flex">
    <h2>🔔 My Notifications</h2>
    <a href="/plvsystem/dashboard" class="btn-sm">Back</a>
</div>

<div class="card">
    <?php if (empty($notifications)): ?>
        <p style="text-align:center; color:#777; padding:20px;">You have no new notifications.</p>
    <?php else: ?>
        <table style="width:100%">
            <?php foreach($notifications as $n): ?>
            <tr style="border-bottom:1px solid #eee; <?= $n['is_read'] == 0 ? 'background:#f9f9f9; font-weight:bold;' : '' ?>">
                <td style="padding:15px;">
                    <?= htmlspecialchars($n['message']) ?>
                    <br>
                    <small style="color:#888; font-weight:normal;"><?= date('M d, H:i', strtotime($n['created_at'])) ?></small>
                </td>
                <td style="text-align:right;">
                    <?php if($n['link'] && $n['link'] != '#'): ?>
                        <a href="<?= $n['link'] ?>" class="btn-sm">View Order</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<?php include 'views/layouts/footer.php'; ?>
<?php include 'views/layouts/header.php'; ?>

<div class="header-flex">
    <h2>Dashboard</h2>
    <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'commercial'): ?>
        <a href="/plvsystem/order/create" class="btn">➕ Create New Order</a>
    <?php endif; ?>
</div>

<?php if (!empty($myTasks)): ?>
<div class="card" style="border-left: 5px solid #e74c3c; background: #fff5f5;">
    <h3 style="color: #c0392b; margin-top:0;">⚠️ Action Required</h3>
    <p>You have <strong><?= count($myTasks) ?></strong> new order(s) waiting for your acceptance.</p>
    
    <table style="margin-top:10px;">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Client</th>
                <th>Deadline</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($myTasks as $task): ?>
            <tr>
                <td>#<?= $task['id'] ?></td>
                <td><?= htmlspecialchars($task['client_name']) ?></td>
                <td><?= date('M d, H:i', strtotime($task['deadline'])) ?></td>
                <td>
                    <a href="/plvsystem/order/view/<?= $task['id'] ?>" class="btn-sm" style="background:#e74c3c;">
                        👉 Review & Accept
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<div class="card">
    <h3>All System Orders</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Client</th>
                <th>Stage</th>
                <th>Assigned To</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($orders as $o): ?>
            <tr>
                <td>#<?= $o['id'] ?></td>
                <td><?= htmlspecialchars($o['client_name']) ?></td>
                <td>
                    <?php 
                        $badgeClass = 'badge-created';
                        if($o['current_stage'] == 'design') $badgeClass = 'badge-design';
                        elseif($o['current_stage'] == 'printing') $badgeClass = 'badge-printing';
                        elseif($o['current_stage'] == 'delivery') $badgeClass = 'badge-delivery';
                        elseif($o['current_stage'] == 'completed') $badgeClass = 'badge-completed';
                    ?>
                    <span class="badge <?= $badgeClass ?>"><?= strtoupper($o['current_stage']) ?></span>
                </td>
                <td style="font-weight:bold; color: #555;">
                    <?= $o['assigned_to'] ? htmlspecialchars($o['assigned_to']) : '<span style="color:#ccc">Unassigned</span>' ?>
                </td>
                <td><?= ucfirst($o['status']) ?></td>
                <td><a href="/plvsystem/order/view/<?= $o['id'] ?>" class="btn-sm">View</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'views/layouts/footer.php'; ?>
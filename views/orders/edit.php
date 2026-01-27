<?php include 'views/layouts/header.php'; ?>

<div class="header-flex">
    <h2>Edit Order #<?= $order['id'] ?></h2>
    <a href="/plvsystem/order/view/<?= $order['id'] ?>" class="btn-sm">Cancel</a>
</div>

<form action="/plvsystem/order/edit/<?= $order['id'] ?>" method="POST" class="card form-layout">
    
    <div class="grid-2-col">
        <div>
            <label>Client Name</label>
            <input type="text" name="client_name" value="<?= htmlspecialchars($order['client_name']) ?>" required>
        </div>
        <div>
            <label>PLV Type</label>
            <input type="text" name="plv_type" value="<?= htmlspecialchars($order['plv_type']) ?>" required>
        </div>
    </div>

    <label>Description</label>
    <textarea name="description" rows="4"><?= htmlspecialchars($order['description']) ?></textarea>
    
    <div class="grid-2-col">
        <div>
            <label>Deadline</label>
            <input type="datetime-local" name="deadline" 
                   value="<?= date('Y-m-d\TH:i', strtotime($order['deadline'])) ?>">
        </div>
        <div>
            <label>Priority</label>
            <select name="priority">
                <option value="medium" <?= $order['priority'] == 'medium' ? 'selected' : '' ?>>Medium</option>
                <option value="high" <?= $order['priority'] == 'high' ? 'selected' : '' ?>>High</option>
                <option value="urgent" <?= $order['priority'] == 'urgent' ? 'selected' : '' ?>>Urgent</option>
            </select>
        </div>
    </div>

    <button type="submit" class="btn">Save Changes</button>
</form>

<?php include 'views/layouts/footer.php'; ?>
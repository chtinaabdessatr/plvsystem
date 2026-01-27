<?php include 'views/layouts/header.php'; ?>

<div style="background:black; color:lime; padding:15px; margin-bottom:20px; font-family:monospace;">
    <h3>🕵️ Debug Data</h3>
    <p><strong>My User ID:</strong> [<?= $_SESSION['user_id'] ?>] (Role: <?= $_SESSION['role'] ?>)</p>
    <p><strong>Assigned To ID:</strong> [<?= isset($assignment['user_id']) ? $assignment['user_id'] : 'NULL' ?>]</p>
    <p><strong>Current Status:</strong> [<?= isset($assignment['status']) ? $assignment['status'] : 'NULL' ?>]</p>
    <p><strong>Current Stage:</strong> [<?= $order['current_stage'] ?>]</p>
    
    <?php if(!isset($assignment['user_id'])): ?>
        <p style="color:red;">❌ No assignment found for this stage!</p>
    <?php elseif($assignment['user_id'] != $_SESSION['user_id']): ?>
        <p style="color:red;">❌ ID Mismatch! You are User <?= $_SESSION['user_id'] ?>, but task is for User <?= $assignment['user_id'] ?></p>
    <?php elseif($assignment['status'] != 'pending'): ?>
        <p style="color:red;">❌ Status Mismatch! Status is '<?= $assignment['status'] ?>' (Must be 'pending')</p>
    <?php else: ?>
        <p style="color:lime;">✅ ALL CHECKS PASS - Buttons should show!</p>
    <?php endif; ?>
</div>

<div class="container">
    
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:start;">
            <div>
                <h1 style="margin:0;">Order #<?= $order['id'] ?>: <?= htmlspecialchars($order['client_name']) ?></h1>
                <p style="margin-top:10px;">
                    <strong>Stage:</strong> <span class="badge"><?= strtoupper($order['current_stage']) ?></span> | 
                    <strong>Status:</strong> <?= ucfirst($order['status']) ?>
                </p>
            </div>

            <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'commercial'): ?>
                <a href="/plvsystem/order/edit/<?= $order['id'] ?>" class="btn-sm" style="background:#f39c12; color:white;">
                    ✏️ Edit Order
                </a>
            <?php endif; ?>
        </div>
        
        <hr style="border:0; border-top:1px solid #eee; margin:15px 0;">
        <p><strong>Description:</strong><br> <?= nl2br(htmlspecialchars($order['description'])) ?></p>
    </div>

    <div class="grid-2-col">
        
        <div class="card">
            <h3>Workflow Action</h3>
            
            <?php if($_SESSION['role'] == 'admin' && ($order['status'] == 'pending' || $order['status'] == 'created')): ?>
                <form action="/plvsystem/order/assign" method="POST">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <input type="hidden" name="stage" value="<?= ($order['current_stage'] == 'created') ? 'design' : $order['current_stage'] ?>">                    
                    <label>Assign User:</label>
                    <select name="user_id">
                        <?php 
                        // Logic to show correct list of users based on stage
                        if ($order['current_stage'] == 'created' || $order['current_stage'] == 'design') {
                            $list = $designers;
                        } elseif ($order['current_stage'] == 'printing') {
                            $list = $printers;
                        } else {
                            $list = $delivery; 
                        }

                        if(empty($list)) {
                            echo "<option disabled>No users found for this role</option>";
                        } else {
                            foreach($list as $u) echo "<option value='{$u['id']}'>{$u['name']}</option>";
                        }
                        ?>
                    </select>
                    <button type="submit" class="btn" style="margin-top:10px;">Assign</button>
                </form>

            <?php elseif($assignment && $assignment['user_id'] == $_SESSION['user_id'] && $assignment['status'] == 'pending'): ?>
                
                <div style="background: #fff3cd; padding: 15px; border-radius: 5px; border: 1px solid #ffeeba;">
                    <p style="margin-top:0; color:#856404;"><strong>Action Required:</strong> You have been assigned this task.</p>
                    
                    <form action="/plvsystem/order/updateStatus" method="POST" style="display:flex; gap:10px;">
                        <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?>">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        
                        <button name="status" value="accepted" class="btn" style="background:#28a745; flex:1; border:none; color:white; padding:10px; cursor:pointer;">
                            ✅ Accept
                        </button>
                        
                        <button name="status" value="refused" class="btn" style="background:#dc3545; flex:1; border:none; color:white; padding:10px; cursor:pointer;">
                            ❌ Refuse
                        </button>
                    </form>
                </div>

            <?php elseif($assignment && $assignment['user_id'] == $_SESSION['user_id'] && $assignment['status'] == 'accepted'): ?>
                
                <form action="/plvsystem/order/upload" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <input type="hidden" name="stage" value="<?= $order['current_stage'] ?>">
                    <label>Upload Work File:</label>
                    <input type="file" name="file" required>
                    <button type="submit" class="btn-sm" style="margin-top:5px;">Upload File</button>
                </form>
                
                <hr style="margin: 20px 0;">
                
                <form action="/plvsystem/order/complete" method="POST">
                    <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?>">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <input type="hidden" name="current_stage" value="<?= $order['current_stage'] ?>">
                    <button class="btn" style="background:green; width:100%;">Deliver & Complete Stage</button>
                </form>

            <?php else: ?>
                <p style="color:#777;"><em>Waiting for action...</em></p>
                <?php if($assignment): ?>
                    <small>Currently with: <strong><?= isset($assignment['user_name']) ? $assignment['user_name'] : 'Unknown' ?></strong></small>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Project Files</h3>
            <?php if(empty($files)): ?>
                <p style="color:#999;">No files uploaded yet.</p>
            <?php else: ?>
                <ul>
                    <?php foreach($files as $f): ?>
                        <li style="margin-bottom:10px;">
                            <a href="/plvsystem/<?= $f['file_path'] ?>" target="_blank" style="font-weight:bold;">Download File</a>
                            <br>
                            <small style="color:#666;">Uploaded by: <?= $f['uploader'] ?> (<?= ucfirst($f['stage']) ?>)</small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'views/layouts/footer.php'; ?>
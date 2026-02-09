<?php include 'views/layouts/header.php'; ?>

<?php 
    $stageColors = [
        'created' => '#95a5a6', 
        'design' => '#3498db', 
        'printing' => '#e67e22', 
        'delivery' => '#1abc9c', 
        'completed' => '#27ae60'
    ];
    $currentColor = $stageColors[$order['current_stage']] ?? '#7f8c8d';
?>

<div class="container">
    
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:start;">
            <div>
                <h1 style="margin:0; color:#2c3e50;">Order #<?= $order['id'] ?>: <?= htmlspecialchars($order['client_name']) ?></h1>
                
                <div style="margin-top:10px;">
                    <span class="badge" style="background:<?= $currentColor ?>; color:white; font-size:14px; padding:6px 12px;">
                        <?= strtoupper($order['current_stage']) ?>
                    </span>
                    <span style="color:#7f8c8d; margin-left:10px;">
                        Status: <strong><?= ucfirst($order['status']) ?></strong>
                    </span>
                    <?php if($order['status'] == 'pending'): ?>
                         <span style="color:#e67e22; font-size:13px;">(Waiting for Admin Assignment)</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'commercial'): ?>
                <a href="/plvsystem/order/edit/<?= $order['id'] ?>" class="btn-sm" style="background:#f39c12; color:white;">✏️ Edit Details</a>
            <?php endif; ?>
        </div>
        
        <hr style="border:0; border-top:1px solid #eee; margin:20px 0;">
        
        <div style="background:#f8f9fa; padding:15px; border-radius:6px; border-left:4px solid <?= $currentColor ?>;">
            <strong style="display:block; margin-bottom:5px; color:#555;">📋 Requirements:</strong>
            <div style="white-space: pre-wrap; font-family:inherit; color:#333;"><?= htmlspecialchars($order['description']) ?></div>
        </div>
    </div>

    <div class="grid-2-col">
        
        <div class="card">
            <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">Current Action</h3>

            <?php if($order['current_stage'] == 'completed'): ?>
                <div style="text-align:center; padding:30px; background:#eafaf1; border-radius:8px;">
                    <div style="font-size:40px;">🎉</div>
                    <h3 style="color:#27ae60; margin:10px 0;">Production Complete</h3>
                    <p style="color:#219150; margin:0;">This order has been delivered successfully.</p>
                </div>

            <?php elseif($_SESSION['role'] == 'admin' && $order['status'] == 'pending'): ?>
                <div style="background:#fff3cd; padding:15px; border-radius:6px; border-left:4px solid #f1c40f;">
                    <h4 style="margin:0 0 10px 0; color:#856404;">⚠️ Action Needed: Assign Task</h4>
                    <p style="font-size:13px; margin-bottom:15px;">
                        The order is in the <strong><?= strtoupper($order['current_stage']) ?></strong> stage. 
                        Please select a worker to proceed.
                    </p>
                    
                    <button onclick="openAssignModal(<?= $order['id'] ?>, '<?= $order['current_stage'] ?>')" class="btn" style="width:100%; background:#f39c12;">
                        👤 Assign to <?= ucfirst($order['current_stage'] == 'created' ? 'Designer' : $order['current_stage']) ?>
                    </button>
                </div>

            <?php elseif($assignment && $assignment['user_id'] == $_SESSION['user_id'] && $assignment['status'] == 'pending'): ?>
                <div style="background:#ebf5fb; padding:20px; border-radius:8px; text-align:center;">
                    <h3 style="margin-top:0; color:#2980b9;">New Task Assigned!</h3>
                    <p>Please accept to start working or refuse if unavailable.</p>
                    
                    <div style="display:flex; gap:10px; margin-top:20px;">
                        <form action="/plvsystem/order/updateStatus" method="POST" style="flex:1;">
                            <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?>">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <button name="status" value="accepted" class="btn" style="width:100%; background:#27ae60;">✅ Accept</button>
                        </form>
                        
                        <button onclick="openRefuseModal()" class="btn" style="flex:1; background:#c0392b;">❌ Refuse</button>
                    </div>
                </div>

            <?php elseif($assignment && $assignment['user_id'] == $_SESSION['user_id'] && $assignment['status'] == 'accepted'): ?>
                <div style="background:#f4f6f7; padding:15px; border-radius:6px; margin-bottom:20px;">
                    <strong style="color:#2c3e50;">🔨 Work in Progress</strong>
                    <p style="font-size:12px; margin:5px 0 0;">Upload your files below when ready.</p>
                </div>

                <form action="/plvsystem/order/upload" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <input type="hidden" name="stage" value="<?= $order['current_stage'] ?>">
                    
                    <div class="file-upload-box" style="padding:15px; margin-bottom:10px;">
                        <input type="file" name="file" required>
                    </div>
                    <button type="submit" class="btn-sm" style="width:100%;">Upload File</button>
                </form>
                
                <hr style="margin:20px 0; border-top:1px dashed #ccc;">
                
                <form action="/plvsystem/order/complete" method="POST">
                    <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?>">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <input type="hidden" name="current_stage" value="<?= $order['current_stage'] ?>">
                    <button class="btn" style="width:100%; background:#27ae60; padding:12px;">🚀 Mark Stage Complete</button>
                </form>

            <?php else: ?>
                <div style="text-align:center; color:#95a5a6; padding:30px;">
                    <div style="font-size:30px; margin-bottom:10px;">⏳</div>
                    <p>Waiting for workflow action...</p>
                    <?php if($assignment): ?>
                        <small>Currently assigned to: <strong><?= $assignment['user_name'] ?></strong></small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">Project Files</h3>
            <?php if(empty($files)): ?>
                <p style="color:#999; font-style:italic;">No files uploaded yet.</p>
            <?php else: ?>
                <ul style="list-style:none; padding:0;">
                    <?php foreach($files as $f): ?>
                    <li style="margin-bottom:12px; display:flex; gap:10px; align-items:center;">
                        <div style="width:36px; height:36px; background:#ecf0f1; border-radius:4px; display:flex; align-items:center; justify-content:center; font-size:18px;">📄</div>
                        <div style="flex:1;">
                            <a href="/plvsystem/<?= $f['file_path'] ?>" target="_blank" style="font-weight:600; color:#3498db; text-decoration:none;">Download File</a>
                            <div style="font-size:11px; color:#7f8c8d;">
                                <?= ucfirst($f['stage']) ?> • By <?= $f['uploader'] ?>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <div class="card" style="margin-top:20px;">
        <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;">🔄 Production History</h3>
        <table style="width:100%; border-collapse:collapse; font-size:13px;">
            <thead style="background:#f9f9f9;">
                <tr>
                    <th style="padding:10px; text-align:left;">Date/Time</th>
                    <th style="padding:10px; text-align:left;">User</th>
                    <th style="padding:10px; text-align:left;">Role</th>
                    <th style="padding:10px; text-align:left;">Action/Stage</th>
                    <th style="padding:10px; text-align:left;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($history)): foreach($history as $h): ?>
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:10px; color:#666;"><?= date('M d, H:i', strtotime($h['created_at'])) ?></td>
                    <td style="padding:10px;"><strong><?= htmlspecialchars($h['user_name']) ?></strong></td>
                    <td style="padding:10px;"><span class="badge" style="background:#eee; color:#555;"><?= ucfirst($h['user_role']) ?></span></td>
                    <td style="padding:10px;"><?= ucfirst($h['stage']) ?></td>
                    <td style="padding:10px;">
                        <?php if($h['status'] == 'completed'): ?>
                            <span style="color:#27ae60; font-weight:bold;">✅ Done</span>
                        <?php elseif($h['status'] == 'refused'): ?>
                            <span style="color:#c0392b; font-weight:bold;">❌ Refused</span>
                            <div style="font-size:11px; color:#c0392b; margin-top:2px;">"<?= htmlspecialchars($h['refusal_reason']) ?>"</div>
                        <?php elseif($h['status'] == 'accepted'): ?>
                            <span style="color:#2980b9;">▶️ Working</span>
                        <?php else: ?>
                            <span style="color:#f39c12;">⏳ Pending</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

</div>

<div id="refuseModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:2000;">
    <div style="background:white; width:400px; margin:100px auto; padding:25px; border-radius:8px; box-shadow:0 5px 20px rgba(0,0,0,0.2);">
        <h3 style="margin-top:0; color:#c0392b;">Refuse Assignment</h3>
        <p style="font-size:14px; color:#555;">Please provide a reason for refusing this task. It will be sent to the Admin.</p>
        
        <form action="/plvsystem/order/updateStatus" method="POST">
            <input type="hidden" name="assignment_id" value="<?= isset($assignment['id']) ? $assignment['id'] : '' ?>">
            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
            <input type="hidden" name="status" value="refused">
            
            <textarea name="refusal_reason" rows="3" placeholder="Reason (e.g., Too busy, Not my skill...)" required style="width:100%; padding:10px; border:1px solid #ddd; border-radius:4px; margin-bottom:15px;"></textarea>
            
            <div style="text-align:right; display:flex; gap:10px;">
                <button type="button" onclick="document.getElementById('refuseModal').style.display='none'" class="btn" style="background:#ccc; color:#333;">Cancel</button>
                <button type="submit" class="btn" style="background:#c0392b;">Confirm Refusal</button>
            </div>
        </form>
    </div>
</div>

<div id="assignModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
    <div style="background:white; width:400px; margin:100px auto; padding:25px; border-radius:8px;">
        <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
            <h3 style="margin:0;">Assign Task</h3>
            <span onclick="document.getElementById('assignModal').style.display='none'" style="cursor:pointer; font-size:24px;">&times;</span>
        </div>
        <form action="/plvsystem/order/assign" method="POST">
            <input type="hidden" name="order_id" id="modalOrderId">
            <input type="hidden" name="stage" id="modalStage">
            <label style="display:block; margin-bottom:8px;">Select User:</label>
            <select name="user_id" id="modalUserSelect" style="width:100%; padding:10px; margin-bottom:20px;" required></select>
            <div style="text-align:right;">
                <button type="button" onclick="document.getElementById('assignModal').style.display='none'" class="btn" style="background:#ccc;">Cancel</button>
                <button type="submit" class="btn">Confirm</button>
            </div>
        </form>
    </div>
</div>

<script>
function openRefuseModal() {
    document.getElementById('refuseModal').style.display = 'block';
}

function openAssignModal(orderId, currentStage) {
    document.getElementById('modalOrderId').value = orderId;
    document.getElementById('assignModal').style.display = 'block';
    
    let select = document.getElementById('modalUserSelect');
    select.innerHTML = '<option disabled selected>Loading...</option>';
    
    let fetchStage = currentStage === 'created' ? 'design' : currentStage;
    
    fetch('/plvsystem/order/getAssignData?current_stage=' + fetchStage)
        .then(response => response.json())
        .then(data => {
            select.innerHTML = '';
            if (data.users.length === 0) {
                select.innerHTML = '<option disabled>No users found</option>';
            } else {
                data.users.forEach(user => {
                    let option = document.createElement('option');
                    option.value = user.id;
                    option.text = user.name + ' (' + user.role + ')';
                    select.appendChild(option);
                });
            }
            document.getElementById('modalStage').value = data.nextStage;
        });
}
</script>

<?php include 'views/layouts/footer.php'; ?>
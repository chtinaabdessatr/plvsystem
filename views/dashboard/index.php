<?php include 'views/layouts/header.php'; ?>

<div class="dashboard-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <div>
        <h2 style="margin:0;">Production Dashboard</h2>
        <p style="margin:0; color:#777;">Monitor workflow: Design ➔ Print ➔ Delivery</p>
    </div>
    <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'commercial'): ?>
        <a href="/plvsystem/order/create" class="btn">＋ New Order</a>
    <?php endif; ?>
</div>

<div class="card">
    <table style="width:100%; border-collapse:collapse;">
        <thead>
            <tr style="background:#f8f9fa; text-align:left;">
                <th style="padding:15px;">Order Ref</th>
                <th style="padding:15px;">Creator</th>
                <th style="padding:15px;">Client</th>
                <th style="padding:15px;">Stage & Assignment</th>
                <th style="padding:15px;">Flow Progress</th>
                <th style="padding:15px;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($orders as $o): ?>
            
            <?php 
                // Determine Row Style based on "My Status" (for workers)
                $rowStyle = "border-bottom:1px solid #eee;";
                $isRefused = isset($o['my_status']) && $o['my_status'] == 'refused';
                
                if($isRefused) {
                    $rowStyle = "background-color: #ffebee; border-left: 4px solid #c0392b; border-bottom:1px solid #f5c6cb;";
                }
            ?>

            <tr style="<?= $rowStyle ?>">
                
                <td style="padding:15px;">
                    <strong>#<?= $o['id'] ?></strong><br>
                    <small style="color:#999;"><?= date('M d, H:i', strtotime($o['created_at'])) ?></small>
                </td>

                <td style="padding:15px;">
                    <?= htmlspecialchars($o['creator_name']) ?><br>
                    <span class="badge" style="background:<?= $o['creator_role']=='admin'?'#2c3e50':'#8e44ad' ?>; color:white; font-size:10px;">
                        <?= ucfirst($o['creator_role']) ?>
                    </span>
                </td>

                <td style="padding:15px;"><?= htmlspecialchars($o['client_name']) ?></td>

                <td style="padding:15px;">
                    <?php if($isRefused): ?>
                         <span style="color:#c0392b; font-weight:bold;">❌ REFUSED</span>
                    <?php else: ?>
                        <span class="badge" style="background:#ecf0f1; color:#2c3e50; border:1px solid #bdc3c7;">
                            <?= strtoupper($o['current_stage']) ?>
                        </span>
                    <?php endif; ?>

                    <div style="margin-top:5px; font-size:13px;">
                    <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'commercial'): ?>
                         <?php if(isset($o['assigned_to']) && $o['assigned_to']): ?>
                            <span style="color:#3498db;">👤 <?= $o['assigned_to'] ?></span>
                        <?php else: ?>
                            <span style="color:#e67e22; font-style:italic;">-- Waiting Assignment --</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if($o['my_status'] == 'pending'): ?>
                            <span style="color:#e67e22; font-weight:bold;">⚠️ Action Required</span>
                        <?php elseif($o['my_status'] == 'accepted'): ?>
                            <span style="color:#3498db; font-weight:bold;">▶️ In Progress</span>
                        <?php elseif($o['my_status'] == 'completed'): ?>
                            <span style="color:#27ae60; font-weight:bold;">✅ Done</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    </div>
                </td>

                <td style="padding:15px;">
                    <div style="display:flex; gap:4px; margin-bottom:5px;">
                        <div style="height:6px; flex:1; border-radius:3px; background:<?= in_array($o['current_stage'], ['design','printing','delivery','completed']) ? '#3498db' : '#eee' ?>;" title="Design"></div>
                        <div style="height:6px; flex:1; border-radius:3px; background:<?= in_array($o['current_stage'], ['printing','delivery','completed']) ? '#e67e22' : '#eee' ?>;" title="Print"></div>
                        <div style="height:6px; flex:1; border-radius:3px; background:<?= in_array($o['current_stage'], ['delivery','completed']) ? '#27ae60' : '#eee' ?>;" title="Delivery"></div>
                    </div>
                    <small style="color:#999;">
                        <?php 
                            if($o['current_stage']=='design') echo 'Phase 1: Design';
                            elseif($o['current_stage']=='printing') echo 'Phase 2: Print';
                            elseif($o['current_stage']=='delivery') echo 'Phase 3: Delivery';
                            elseif($o['current_stage']=='completed') echo 'Completed';
                            else echo 'New Order';
                        ?>
                    </small>
                </td>

                <td style="padding:15px;">
                    
                    <?php if($isRefused): ?>
                        <a href="/plvsystem/order/receipt/<?= $o['assignment_id'] ?>" target="_blank" class="btn-sm" style="background:#fff; color:#c0392b; border:1px solid #c0392b;">
                            📄 Receipt
                        </a>
                    <?php else: ?>
                        <a href="/plvsystem/order/view/<?= $o['id'] ?>" class="btn-sm" style="background:#95a5a6;">ℹ️ View</a>
                        
                        <?php 
                            $needsAssignment = empty($o['assigned_to']); // True if no one has it
                            if($_SESSION['role'] == 'admin' && $o['status'] != 'completed' && $needsAssignment): 
                        ?>
                            <button onclick="openAssignModal(<?= $o['id'] ?>, '<?= $o['current_stage'] ?>')" class="btn-sm" style="background:#f39c12; cursor:pointer;">
                                Assign 👤
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div id="assignModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
    <div style="background:white; width:400px; margin:100px auto; padding:25px; border-radius:8px; box-shadow:0 5px 15px rgba(0,0,0,0.3);">
        <div style="display:flex; justify-content:space-between; margin-bottom:20px;">
            <h3 style="margin:0;">Assign Task</h3>
            <span onclick="document.getElementById('assignModal').style.display='none'" style="cursor:pointer; font-size:20px;">&times;</span>
        </div>

        <form action="/plvsystem/order/assign" method="POST">
            <input type="hidden" name="order_id" id="modalOrderId">
            <input type="hidden" name="stage" id="modalStage">
            
            <p>Assign <strong>Order #<span id="displayOrderId"></span></strong> to:</p>
            
            <label style="display:block; margin-bottom:5px;">Select User:</label>
            <select name="user_id" id="modalUserSelect" style="width:100%; padding:10px; margin-bottom:20px;" required>
                <option disabled selected>Loading users...</option>
            </select>
            
            <div style="text-align:right;">
                <button type="button" onclick="document.getElementById('assignModal').style.display='none'" class="btn" style="background:#ccc;">Cancel</button>
                <button type="submit" class="btn">Confirm Assignment</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAssignModal(orderId, currentStage) {
    document.getElementById('modalOrderId').value = orderId;
    document.getElementById('displayOrderId').innerText = orderId;
    
    let select = document.getElementById('modalUserSelect');
    select.innerHTML = '<option disabled selected>Loading...</option>';
    
    document.getElementById('assignModal').style.display = 'block';

    let fetchStage = currentStage;
    if (currentStage == 'created') fetchStage = 'created'; 

    fetch('/plvsystem/order/getAssignData?current_stage=' + fetchStage)
        .then(response => response.json())
        .then(data => {
            select.innerHTML = ''; 
            if (data.users.length === 0) {
                select.innerHTML = '<option disabled>No suitable users found</option>';
            } else {
                data.users.forEach(user => {
                    let option = document.createElement('option');
                    option.value = user.id;
                    option.text = user.name + ' (' + user.role + ')';
                    select.appendChild(option);
                });
            }
            document.getElementById('modalStage').value = data.nextStage;
        })
        .catch(err => {
            console.error(err);
            select.innerHTML = '<option disabled>Error loading users</option>';
        });
}
</script>

<?php include 'views/layouts/footer.php'; ?>
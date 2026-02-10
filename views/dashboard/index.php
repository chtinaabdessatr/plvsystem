<?php include 'views/layouts/header.php'; ?>

<?php
    // Initialize counters
    $kpi = [
        'total' => 0,
        'active' => 0,
        'completed' => 0,
        'urgent' => 0
    ];

    // Helper to map roles to their specific stage (for "Active" count)
    $my_role = $_SESSION['role'];
    $my_stage_map = [
        'designer' => 'design',
        'printer'  => 'printing', // Adjust if your DB uses 'production'
        'delivery' => 'delivery'
    ];
    $my_target_stage = $my_stage_map[$my_role] ?? '';

    // Calculate based on role
    if (!empty($orders)) {
        foreach($orders as $o) {
            // ADMIN / COMMERCIAL: Count Everything
            if($my_role == 'admin' || $my_role == 'commercial') {
                $kpi['total']++;
                if($o['status'] == 'completed') $kpi['completed']++;
                else $kpi['active']++;
                
                // Count 'urgent' if unassigned and not done
                if(empty($o['assigned_to']) && $o['status'] != 'completed') $kpi['urgent']++;
            } 
            // WORKERS: Count only what is assigned to ME
            else {
                // Check if this order has an assignment for the current user
                if(isset($o['assigned_to']) && strpos($o['assigned_to'], $_SESSION['name']) !== false) {
                     $kpi['total']++;
                     
                     // FIX: Check if the order is completed or active
                     if($o['status'] == 'completed') {
                         $kpi['completed']++;
                     } else {
                         // It is active if it is NOT completed
                         $kpi['active']++;
                     }
                }
            }
        }
    }
?>

<div class="app-container">
    
    <div class="page-header">
        <div class="page-header__content">
            <h1 class="page-header__title">
                <span>Overview</span>
                Dashboard
            </h1>
        </div>
        
        <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'commercial'): ?>
            <a href="/plvsystem/order/create" class="btn btn--primary">
                <i class="fa-solid fa-plus"></i> New Order
            </a>
        <?php endif; ?>
    </div>

    <div class="kpi-grid">
        <div class="kpi-card blue">
            <div class="kpi-icon"><i class="fa-solid fa-clipboard-list"></i></div>
            <div class="kpi-info">
                <h3><?= ($_SESSION['role'] == 'admin') ? 'Total Orders' : 'My Tasks' ?></h3>
                <div class="number"><?= $kpi['total'] ?></div>
            </div>
        </div>

        <div class="kpi-card orange">
            <div class="kpi-icon"><i class="fa-solid fa-gears"></i></div>
            <div class="kpi-info">
                <h3>In Progress</h3>
                <div class="number"><?= $kpi['active'] ?></div>
            </div>
        </div>

        <div class="kpi-card green">
            <div class="kpi-icon"><i class="fa-solid fa-flag-checkered"></i></div>
            <div class="kpi-info">
                <h3>Completed</h3>
                <div class="number"><?= $kpi['completed'] ?></div>
            </div>
        </div>

        <?php if($_SESSION['role'] == 'admin'): ?>
        <div class="kpi-card red">
            <div class="kpi-icon"><i class="fa-solid fa-user-clock"></i></div>
            <div class="kpi-info">
                <h3>Needs Assign</h3>
                <div class="number"><?= $kpi['urgent'] ?></div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="card margin-top-lg">
        <div class="card__header">
            <h3><i class="fa-solid fa-table-list"></i> Recent Orders</h3>
        </div>
        <div class="card__body" style="padding: 0;">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 15%;">Reference</th>
                        <th style="width: 20%;">Client</th>
                        <th style="width: 15%;">Status</th>
                        <th style="width: 20%;">Assignment</th>
                        <th style="width: 15%;">Progress</th>
                        <th style="width: 15%; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($orders)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-light);">
                                <i class="fa-solid fa-folder-open" style="font-size: 2rem; margin-bottom: 10px;"></i><br>
                                No active orders found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($orders as $o): ?>
                        <?php 
                            $isRefused = isset($o['my_status']) && $o['my_status'] == 'refused';
                            $rowStyle = $isRefused ? 'background-color: #fef2f2;' : ''; 
                            
                            $stageClasses = ['created'=>'status-gray', 'design'=>'status-blue', 'printing'=>'status-orange', 'delivery'=>'status-teal', 'completed'=>'status-green'];
                            $badgeClass = $stageClasses[$o['current_stage']] ?? 'status-gray';
                        ?>
                        <tr style="<?= $rowStyle ?>">
                            <td>
                                <div style="font-weight: 700; color: var(--text-dark);">#<?= $o['id'] ?></div>
                                <div class="text-muted" style="font-size: 0.8rem;">
                                    <i class="fa-regular fa-clock"></i> <?= date('M d', strtotime($o['created_at'])) ?>
                                </div>
                            </td>
                            
                            <td style="font-weight: 500;"><?= htmlspecialchars($o['client_name']) ?></td>
                            
                            <td>
                                <?php if($isRefused): ?>
                                    <span class="badge badge--sm" style="background:var(--danger); color:white;"><i class="fa-solid fa-ban"></i> Refused</span>
                                <?php else: ?>
                                    <span class="badge badge--sm <?= $badgeClass ?>"><?= strtoupper($o['current_stage']) ?></span>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <?php if(isset($o['assigned_to']) && $o['assigned_to']): ?>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <div class="user-avatar-sm"><i class="fa-solid fa-user"></i></div>
                                        <span style="font-size: 0.9rem;"><?= htmlspecialchars($o['assigned_to']) ?></span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted" style="font-style:italic; font-size: 0.85rem;">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <?php 
                                    $s1 = in_array($o['current_stage'], ['design','printing','delivery','completed']) ? 'var(--primary)' : '#e2e8f0';
                                    $s2 = in_array($o['current_stage'], ['printing','delivery','completed']) ? 'var(--primary)' : '#e2e8f0';
                                    $s3 = in_array($o['current_stage'], ['delivery','completed']) ? 'var(--primary)' : '#e2e8f0';
                                ?>
                                <div class="progress-mini">
                                    <div style="background:<?= $s1 ?>"></div>
                                    <div style="background:<?= $s2 ?>"></div>
                                    <div style="background:<?= $s3 ?>"></div>
                                </div>
                            </td>
                            
                            <td style="text-align: right;">
                                <div class="action-buttons-wrapper">
                                    
                                    <a href="/plvsystem/order/view/<?= $o['id'] ?>" class="btn-icon-only" title="View Details">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>

                                    <?php 
                                        $needsAssignment = empty($o['assigned_to']);
                                        if($_SESSION['role'] == 'admin' && $o['status'] != 'completed' && $needsAssignment): 
                                    ?>
                                        <button onclick="openAssignModal(<?= $o['id'] ?>, '<?= $o['current_stage'] ?>')" class="btn btn--sm btn--primary">
                                            <i class="fa-solid fa-user-plus"></i> Assign
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="assignModal" class="modal">
    <div class="modal__content">
        <div class="modal__header">
            <h3>Assign Task</h3>
            <span onclick="document.getElementById('assignModal').classList.remove('active')" class="modal__close">&times;</span>
        </div>
        <form action="/plvsystem/order/assign" method="POST">
            <input type="hidden" name="order_id" id="modalOrderId">
            <input type="hidden" name="stage" id="modalStage">
            <div class="form-group">
                <label class="form-label">Select User:</label>
                <select name="user_id" id="modalUserSelect" class="form-control" required></select>
            </div>
            <div class="modal__actions">
                <button type="button" onclick="document.getElementById('assignModal').classList.remove('active')" class="btn btn--neutral">Cancel</button>
                <button type="submit" class="btn btn--primary">Confirm</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAssignModal(orderId, currentStage) {
    const modal = document.getElementById('assignModal');
    modal.classList.add('active');
    document.getElementById('modalOrderId').value = orderId;
    let select = document.getElementById('modalUserSelect');
    select.innerHTML = '<option disabled selected>Loading...</option>';
    let fetchStage = currentStage === 'created' ? 'design' : currentStage;

    fetch('/plvsystem/order/getAssignData?current_stage=' + fetchStage)
        .then(response => response.json())
        .then(data => {
            select.innerHTML = '';
            if (!data.users || data.users.length === 0) {
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
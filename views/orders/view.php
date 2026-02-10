<?php include 'views/layouts/header.php'; ?>

<?php
// SMART PARSER FUNCTION
function renderProDescription($text) {
    $parts = explode('===', $text, 2); 
    $generalDesc = trim($parts[0]);
    
    if(!empty($generalDesc)) {
        echo "<div class='pro-desc__general'>
                <div class='pro-desc__label'><i class='fa-solid fa-clipboard-list'></i> General Notes</div>
                <div class='pro-desc__text'>".nl2br(htmlspecialchars($generalDesc))."</div>
              </div>";
    }

    if(count($parts) > 1) {
        $techSection = explode("\n", trim($parts[1])); 
        $mainTitle = trim(array_shift($techSection)); 
        
        echo "<div class='pro-specs'>";
        echo "<div class='pro-specs__header'><i class='fa-solid fa-layer-group'></i> $mainTitle</div>";
        echo "<div class='pro-specs__body'>";

        $techContent = implode("\n", $techSection);
        $chunks = explode('---', $techContent);
        
        foreach($chunks as $chunk) {
            $chunk = trim($chunk);
            if(empty($chunk)) continue;

            if(strpos($chunk, '[') !== false && strpos($chunk, ']') !== false) {
                $lines = explode("\n", $chunk);
                $panelTitle = trim(array_shift($lines));
                $panelTitle = str_replace(['[', ']'], '', $panelTitle);
                
                echo "<div class='spec-card'>";
                echo "<h4 class='spec-card__title'>$panelTitle</h4>";
                echo "<div class='spec-card__content'>";
                foreach($lines as $line) {
                    if(trim($line) == '') continue;
                    if(strpos($line, ':') !== false) {
                        list($key, $val) = explode(':', $line, 2);
                        echo "<div class='spec-row'><span class='spec-row__label'>".trim($key)."</span><span class='spec-row__value'>".trim($val)."</span></div>";
                    } else {
                        echo "<div class='spec-row spec-row--text'>".trim($line)."</div>";
                    }
                }
                echo "</div></div>";
            } else {
                $lines = explode("\n", $chunk);
                echo "<div class='spec-info-box'>";
                foreach($lines as $line) {
                    if(trim($line) == '') continue;
                    if(strpos($line, ':') !== false) {
                        list($key, $val) = explode(':', $line, 2);
                        echo "<div class='spec-row'><span class='spec-row__label'>".trim($key)."</span><span class='spec-row__value'>".trim($val)."</span></div>";
                    } else {
                         echo "<div class='spec-row spec-row--text'>".trim($line)."</div>";
                    }
                }
                echo "</div>";
            }
        }
        echo "</div></div>";
    }
}
?>

<?php 
    $stageClasses = ['created'=>'status-gray', 'design'=>'status-blue', 'printing'=>'status-orange', 'delivery'=>'status-teal', 'completed'=>'status-green'];
    $currentStatusClass = $stageClasses[$order['current_stage']] ?? 'status-default';
?>

<div class="app-container">
    
    <div class="page-header">
        <div class="page-header__content">
            <h1 class="page-header__title">
                <span class="text-muted">Order #<?= $order['id'] ?></span>
                <?= htmlspecialchars($order['client_name']) ?>
            </h1>
            
            <div class="status-bar">
                <span class="status-text">Status: <strong><?= ucfirst($order['status']) ?></strong></span>
                <span class="badge badge--lg <?= $currentStatusClass ?>"><?= strtoupper($order['current_stage']) ?></span>
                <?php if($order['status'] == 'pending'): ?>
                     <span class="status-warning"><i class="fa-solid fa-clock"></i> Awaiting Assignment</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'commercial'): ?>
            <a href="/plvsystem/order/edit/<?= $order['id'] ?>" class="btn btn--secondary btn--icon">
                <i class="fa-solid fa-pen"></i> Edit Details
            </a>
        <?php endif; ?>
    </div>
    
    <div class="content-grid">
        
        <div class="content-grid__main">
            <div class="card card--padded">
                <?php renderProDescription($order['description']); ?>
            </div>

            <div class="card card--padded margin-top-lg">
                <div class="card__header">
                    <h3><i class="fa-solid fa-history"></i> Production History</h3>
                </div>
                <div class="history-table-wrapper">
                    <table class="table history-table">
                        <thead>
                            <tr><th>Date</th><th>User</th><th>Role</th><th>Stage</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($history)): foreach($history as $h): ?>
                            <tr>
                                <td class="text-muted"><?= date('M d, H:i', strtotime($h['created_at'])) ?></td>
                                <td class="font-medium"><?= htmlspecialchars($h['user_name']) ?></td>
                                <td><span class="badge badge--sm badge--neutral"><?= ucfirst($h['user_role']) ?></span></td>
                                <td><?= ucfirst($h['stage']) ?></td>
                                <td>
                                    <?php if($h['status'] == 'completed'): ?> <span class="text-success"><i class="fa-solid fa-check"></i> Done</span>
                                    <?php elseif($h['status'] == 'refused'): ?> 
                                        <span class="text-danger"><i class="fa-solid fa-xmark"></i> Refused</span>
                                        <div class="text-xs text-danger">"<?= htmlspecialchars($h['refusal_reason']) ?>"</div>
                                    <?php elseif($h['status'] == 'accepted'): ?> <span class="text-primary"><i class="fa-solid fa-play"></i> Working</span>
                                    <?php else: ?> <span class="text-warning"><i class="fa-solid fa-hourglass"></i> Pending</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="content-grid__sidebar">
            <div class="card">
                <div class="card__header"><h3>Current Action</h3></div>
                
                <div class="card__body">

    <?php if($order['current_stage'] == 'completed'): ?>
        <div class="state-box state-box--success">
            <div class="state-box__icon"><i class="fa-solid fa-circle-check"></i></div>
            <h3>Production Complete</h3>
            <p>This order has been delivered successfully.</p>
        </div>

    <?php elseif($_SESSION['role'] == 'admin' && $order['status'] == 'pending'): ?>
        <div class="state-box state-box--warning">
            <h4><i class="fa-solid fa-triangle-exclamation"></i> Action Needed</h4>
            <p>Order is in <strong><?= strtoupper($order['current_stage']) ?></strong> stage.</p>
            <button onclick="openAssignModal(<?= $order['id'] ?>, '<?= $order['current_stage'] ?>')" class="btn btn--warning btn--block">
                <i class="fa-solid fa-user-plus"></i> Assign User
            </button>
        </div>

    <?php elseif(($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'commercial') && !empty($assignment) && $assignment['status'] == 'pending'): ?>
        <div class="state-box state-box--neutral">
            <div class="state-box__icon"><i class="fa-solid fa-hourglass-start"></i></div>
            <h3>Waiting for Acceptance</h3>
            <p>Task assigned to <strong><?= htmlspecialchars($assignment['user_name']) ?></strong>.</p>
            <p class="text-xs text-muted">They need to log in and accept the task.</p>
        </div>

    <?php elseif(($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'commercial') && !empty($assignment) && $assignment['status'] == 'review'): ?>
        <div class="state-box state-box--info" style="border-left: 4px solid var(--primary);">
            <h4 style="margin-top:0;"><i class="fa-solid fa-glasses"></i> Approval Needed</h4>
            <p><strong><?= htmlspecialchars($assignment['user_name']) ?></strong> has finished this stage.</p>
            
            <form id="adminReviewForm" method="POST" enctype="multipart/form-data" style="margin-top:15px;">
                <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?>">
                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                <input type="hidden" name="current_stage" value="<?= $order['current_stage'] ?>">
                <input type="hidden" name="worker_id" value="<?= $assignment['user_id'] ?>">
                
                <textarea name="remark" class="form-control" rows="2" placeholder="Add remarks..." style="margin-bottom:10px; font-size:13px;"></textarea>
                <div style="margin-bottom:10px;">
                    <label style="font-size:11px; font-weight:bold;">Attach Correction (Optional):</label>
                    <input type="file" name="admin_file" class="form-control" style="font-size:12px;">
                </div>
            </form>

            <div class="btn-group">
                <button type="submit" form="adminReviewForm" formaction="/plvsystem/order/rejectStage" class="btn btn--danger flex-grow"><i class="fa-solid fa-xmark"></i> Reject</button>
                <button type="submit" form="adminReviewForm" formaction="/plvsystem/order/approveStage" class="btn btn--success flex-grow"><i class="fa-solid fa-check"></i> Approve</button>
            </div>
        </div>

    <?php elseif(!empty($assignment) && $assignment['user_id'] == $_SESSION['user_id'] && $assignment['status'] == 'refused'): ?>
        <div class="state-box" style="background: #fef2f2; border: 1px solid #fee2e2; color: #991b1b;">
            <div class="state-box__icon" style="background: #fee2e2; color: #dc2626;"><i class="fa-solid fa-ban"></i></div>
            <h3>Task Refused</h3>
            <a href="/plvsystem/order/receipt/<?= $assignment['id'] ?>" target="_blank" class="btn btn--block btn--white-outline" style="color: #dc2626; border-color: #dc2626; margin-top: 15px;">
                <i class="fa-solid fa-print"></i> Print Receipt
            </a>
        </div>

    <?php elseif(!empty($assignment) && $assignment['user_id'] == $_SESSION['user_id'] && $assignment['status'] == 'pending'): ?>
        <div class="state-box state-box--info">
            <h3>New Task Assigned!</h3>
            <p>Please accept to start working.</p>
            <div class="btn-group">
                <form action="/plvsystem/order/updateStatus" method="POST" class="flex-grow">
                    <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?>">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <button name="status" value="accepted" class="btn btn--success btn--block"><i class="fa-solid fa-check"></i> Accept</button>
                </form>
                <button onclick="openRefuseModal()" class="btn btn--danger flex-grow"><i class="fa-solid fa-xmark"></i> Refuse</button>
            </div>
        </div>

    <?php elseif(!empty($assignment) && $assignment['user_id'] == $_SESSION['user_id'] && $assignment['status'] == 'revision'): ?>
        <div class="state-box state-box--warning" style="border: 1px solid #f59e0b; background: #fffbeb;">
            <div style="color: #b45309; font-weight:bold; margin-bottom:5px;"><i class="fa-solid fa-rotate-left"></i> Revision Requested</div>
            <?php if(!empty($assignment['refusal_reason'])): ?>
                <div style="background: rgba(255,255,255,0.6); padding: 8px; border-radius: 4px; border: 1px dashed #d97706; margin-bottom: 8px; font-style: italic;">"<?= nl2br(htmlspecialchars($assignment['refusal_reason'])) ?>"</div>
            <?php endif; ?>
            <p class="text-xs">Check comments, upload new file, and resubmit.</p>
        </div>
        <form action="/plvsystem/order/upload" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
            <input type="hidden" name="stage" value="<?= $order['current_stage'] ?>">
            <div class="file-drop-area"><input type="file" name="file" required><span class="file-msg">Upload New File...</span></div>
            <button type="submit" class="btn btn--primary btn--sm btn--block">Upload Fix</button>
        </form>
        <form action="/plvsystem/order/requestReview" method="POST" style="margin-top:10px;">
            <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?>">
            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
            <button class="btn btn--success btn--block"><i class="fa-solid fa-paper-plane"></i> Resubmit</button>
        </form>

    <?php elseif(!empty($assignment) && $assignment['user_id'] == $_SESSION['user_id'] && $assignment['status'] == 'accepted'): ?>
        <div class="state-box state-box--active">
            <strong><i class="fa-solid fa-hammer"></i> Work in Progress</strong>
            <p class="text-xs">Upload files when ready.</p>
        </div>
        <form action="/plvsystem/order/upload" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
            <input type="hidden" name="stage" value="<?= $order['current_stage'] ?>">
            <div class="file-drop-area"><input type="file" name="file" required><span class="file-msg">Choose file...</span></div>
            <button type="submit" class="btn btn--primary btn--sm btn--block">Upload File</button>
        </form>
        <form action="/plvsystem/order/requestReview" method="POST" style="margin-top:10px;">
            <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?>">
            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
            <button class="btn btn--success btn--block"><i class="fa-solid fa-paper-plane"></i> Submit for Review</button>
        </form>

    <?php elseif(!empty($assignment) && $assignment['user_id'] == $_SESSION['user_id'] && $assignment['status'] == 'review'): ?>
        <div class="state-box state-box--warning">
            <div class="state-box__icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
            <h3>Under Review</h3>
            <p>Waiting for Admin approval.</p>
        </div>

    <?php else: ?>
        <div class="state-box state-box--neutral">
            <div class="state-box__icon"><i class="fa-solid fa-hourglass-half"></i></div>
            <p>Waiting for workflow action...</p>
            <?php if(!empty($assignment) && is_array($assignment)): ?>
                <small>Assigned to: <strong><?= isset($assignment['user_name']) ? $assignment['user_name'] : 'Unknown' ?></strong></small>
                <br><small>Status: <?= $assignment['status'] ? $assignment['status'] : 'EMPTY (Error)' ?></small>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>
            </div>

            <div class="card margin-top-md">
                <div class="card__header">
                    <h3><i class="fa-solid fa-folder-open"></i> Project Files</h3>
                </div>
                <div class="card__body">
                    <?php if(empty($files)): ?>
                        <p class="text-muted text-italic">No files uploaded yet.</p>
                    <?php else: ?>
                        <ul class="file-list">
                            <?php foreach($files as $f): ?>
                            <li class="file-item">
                                <div class="file-item__icon"><i class="fa-solid fa-file-pdf"></i></div>
                                <div class="file-item__details">
                                    <a href="/plvsystem/<?= $f['file_path'] ?>" target="_blank" class="file-link">Download File</a>
                                    <div class="file-meta"><?= ucfirst($f['stage']) ?> • <?= $f['uploader'] ?></div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<div id="refuseModal" class="modal">
    <div class="modal__content">
        <div class="modal__header">
            <h3 class="text-danger">Refuse Assignment</h3>
        </div>
        <form action="/plvsystem/order/updateStatus" method="POST">
            <input type="hidden" name="assignment_id" value="<?= isset($assignment['id']) ? $assignment['id'] : '' ?>">
            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
            <input type="hidden" name="status" value="refused">
            <p class="modal__desc">Please provide a reason for refusing this task.</p>
            <textarea name="refusal_reason" rows="3" class="form-control" placeholder="Reason..." required></textarea>
            <div class="modal__actions">
                <button type="button" onclick="document.getElementById('refuseModal').classList.remove('active')" class="btn btn--neutral">Cancel</button>
                <button type="submit" class="btn btn--danger">Confirm</button>
            </div>
        </form>
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
                <label>Select User:</label>
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
function openRefuseModal() {
    document.getElementById('refuseModal').classList.add('active');
}

function openAssignModal(orderId, currentStage) {
    const modal = document.getElementById('assignModal');
    modal.classList.add('active');
    
    document.getElementById('modalOrderId').value = orderId;
    let select = document.getElementById('modalUserSelect');
    select.innerHTML = '<option disabled selected>Loading users...</option>';
    
    let fetchStage = currentStage === 'created' ? 'design' : currentStage;

    fetch('/plvsystem/order/getAssignData?current_stage=' + fetchStage)
        .then(response => {
            if (!response.ok) { throw new Error("Network response was not ok"); }
            return response.json();
        })
        .then(data => {
            select.innerHTML = '';
            if (!data.users || data.users.length === 0) {
                select.innerHTML = '<option disabled>No users found for this role</option>';
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
        .catch(error => {
            console.error('Error:', error);
            select.innerHTML = '<option disabled>Error loading users</option>';
        });
}

// --- AUTO-OPEN ASSIGN MODAL (Add this to make the popup work!) ---
document.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('assign_needed')) {
        // Automatically open the modal for the CURRENT stage
        // We ensure orderId is valid
        <?php if(isset($order['id']) && isset($order['current_stage'])): ?>
            openAssignModal(<?= $order['id'] ?>, '<?= $order['current_stage'] ?>');
            // Clean URL
            window.history.replaceState(null, null, window.location.pathname);
        <?php endif; ?>
    }
});
</script>

<?php include 'views/layouts/footer.php'; ?>
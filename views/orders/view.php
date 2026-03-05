<?php include 'views/layouts/header.php'; ?>
<?php
// Put this at the VERY TOP of view.php
$canClaim = false;
$stage = $order['current_stage'] ?? '';
$role = strtolower($_SESSION['role'] ?? '');

if (empty($assignment) && $order['status'] != 'completed') {
    if ($role == 'designer' && ($stage == 'created' || $stage == 'design')) $canClaim = true;
    if ($role == 'printer' && $stage == 'printing') $canClaim = true;
    if ($role == 'delivery' && $stage == 'delivery') $canClaim = true;
}
?>

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
            <button type="submit" class="btn btn--success btn--block">
                <i class="fa-solid fa-check-double"></i> Complete Task & Forward
            </button>
        </form>

    <?php elseif(!empty($assignment) && $assignment['user_id'] == $_SESSION['user_id'] && $assignment['status'] == 'review'): ?>
        <div class="state-box state-box--warning">
            <div class="state-box__icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
            <h3>Under Review</h3>
            <p>Waiting for Admin approval.</p>
        </div>

    <?php elseif($canClaim): ?>
        <div class="state-box" style="background: #f0fdf4; border: 1px solid #bbf7d0;">
            <div class="state-box__icon" style="background: #dcfce7; color: #16a34a;">
                <i class="fa-solid fa-hand-sparkles"></i>
            </div>
            <h3 style="color: #166534;">Available Task</h3>
            <p class="text-xs" style="color: #15803d; margin-bottom: 15px;">
                This order is waiting for a <?= ucfirst($role) ?>. Grab it to start working!
            </p>
            
            <form action="/plvsystem/order/claim" method="POST">
                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                <input type="hidden" name="stage" value="<?= $order['current_stage'] == 'created' ? 'design' : $order['current_stage'] ?>">
                
                <button type="submit" class="btn btn--success btn--block" style="padding: 12px; font-size: 15px; box-shadow: 0 4px 6px -1px rgba(22, 163, 74, 0.2);">
                    <i class="fa-solid fa-hand-holding-hand"></i> Claim This Task
                </button>
            </form>
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
            
            <div class="card" style="display:flex; flex-direction:column; height:500px; padding:0; overflow:hidden; margin-top:20px;">
            <div style="background:#f8fafc; padding:15px; border-bottom:1px solid var(--border); font-weight:600; color:var(--dark);">
                <i class="fa-regular fa-comments"></i> Project Discussion
            </div>
            
            <div style="flex:1; padding:20px; overflow-y:auto; background:#fefefe; display:flex; flex-direction:column; gap:15px;" id="chatBox">
                <?php if(empty($chatMessages)): ?>
                    <div style="text-align:center; color:var(--secondary); margin-top:50px;">
                        <i class="fa-regular fa-message" style="font-size:30px; margin-bottom:10px;"></i>
                        <p>No messages yet. Start the conversation!</p>
                    </div>
                <?php else: ?>
                    <?php foreach($chatMessages as $msg): 
                        $isMe = ($msg['user_id'] == $_SESSION['user_id']);
                        $align = $isMe ? 'flex-end' : 'flex-start';
                        $bg = $isMe ? 'var(--primary)' : '#f1f5f9';
                        $color = $isMe ? 'white' : 'var(--dark)';
                        $radius = $isMe ? '15px 15px 0 15px' : '15px 15px 15px 0';
                    ?>
                        <div style="display:flex; flex-direction:column; align-items:<?= $align ?>; max-width:85%; align-self:<?= $align ?>;">
                            <div style="font-size:11px; color:var(--secondary); margin-bottom:4px;">
                                <strong><?= $isMe ? 'You' : htmlspecialchars($msg['user_name']) ?></strong> 
                                <span style="font-weight:normal;">(<?= ucfirst($msg['user_role']) ?>)</span> • <?= date('H:i, M d', strtotime($msg['created_at'])) ?>
                            </div>
                            
                            <div style="background:<?= $bg ?>; color:<?= $color ?>; padding:10px 15px; border-radius:<?= $radius ?>; font-size:13px; box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                                <?php if(!empty($msg['message'])): ?>
                                    <div style="line-height:1.5; white-space:pre-wrap;"><?= htmlspecialchars($msg['message']) ?></div>
                                <?php endif; ?>
                                
                                <?php if(!empty($msg['file_path'])): 
                                    $ext = strtolower(pathinfo($msg['file_path'], PATHINFO_EXTENSION));
                                    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                ?>
                                    <div style="margin-top: <?= empty($msg['message']) ? '0' : '10px' ?>; border-top: <?= empty($msg['message']) ? 'none' : '1px solid rgba(255,255,255,0.2)' ?>; padding-top: <?= empty($msg['message']) ? '0' : '10px' ?>;">
                                        <?php if($isImage): ?>
                                            <a href="/plvsystem/<?= $msg['file_path'] ?>" target="_blank">
                                                <img src="/plvsystem/<?= $msg['file_path'] ?>" style="max-width:100%; max-height:200px; border-radius:6px; border:2px solid rgba(0,0,0,0.1);">
                                            </a>
                                        <?php else: ?>
                                            <a href="/plvsystem/<?= $msg['file_path'] ?>" target="_blank" class="btn btn-sm" style="background:rgba(0,0,0,0.1); color:<?= $color ?>; border:1px solid rgba(0,0,0,0.1); text-decoration:none;">
                                                <i class="fa-solid fa-paperclip"></i> Download Attachment
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div style="padding:15px; background:white; border-top:1px solid var(--border);">
                <form id="chatForm" enctype="multipart/form-data" style="display:flex; gap:10px; align-items:flex-end;">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <input type="hidden" name="ajax" value="1"> 
                    
                    <label style="cursor:pointer; padding:10px; background:var(--light); border:1px solid var(--border); border-radius:6px; color:var(--secondary); transition:0.2s;" title="Attach File/Image">
                        <i class="fa-solid fa-paperclip"></i>
                        <input type="file" name="chat_file" id="chatFileInput" style="display:none;">
                    </label>

                    <div style="flex:1; position:relative;">
                        <textarea id="chatInputText" name="message" rows="1" placeholder="Type a message... (Press Enter to send, Shift+Enter for new line)" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:6px; resize:none; font-family:inherit; font-size:13px;" oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'"></textarea>
                        <span id="file-indicator" style="display:none; position:absolute; right:10px; top:-10px; background:var(--success); color:white; font-size:10px; padding:2px 6px; border-radius:10px;">File Attached</span>
                    </div>

                    <button type="submit" id="chatSubmitBtn" class="btn btn-primary" style="padding:10px 20px;">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>
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

// --- AUTO-OPEN ASSIGN MODAL ---
document.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('assign_needed')) {
        <?php if(isset($order['id']) && isset($order['current_stage'])): ?>
            openAssignModal(<?= $order['id'] ?>, '<?= $order['current_stage'] ?>');
            window.history.replaceState(null, null, window.location.pathname);
        <?php endif; ?>
    }
});
</script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const chatForm = document.getElementById('chatForm');
    const chatInputText = document.getElementById('chatInputText');
    const chatFileInput = document.getElementById('chatFileInput');
    const fileIndicator = document.getElementById('file-indicator');
    const chatBox = document.getElementById('chatBox');
    const chatSubmitBtn = document.getElementById('chatSubmitBtn');

    if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;

    chatFileInput.addEventListener('change', function() {
        if (this.files.length > 0) fileIndicator.style.display = 'inline-block';
        else fileIndicator.style.display = 'none';
    });

    chatInputText.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault(); 
            
            if (this.value.trim() !== '' || chatFileInput.files.length > 0) {
                chatForm.dispatchEvent(new Event('submit'));
            }
        }
    });

    chatForm.addEventListener('submit', function(e) {
        e.preventDefault(); 

        const formData = new FormData(chatForm);
        
        chatInputText.disabled = true;
        chatSubmitBtn.disabled = true;
        chatSubmitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

        fetch('/plvsystem/order/addMessage', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let fileHTML = '';
                if (data.file_path) {
                    const ext = data.file_path.split('.').pop().toLowerCase();
                    const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext);
                    const marginTop = data.message ? 'margin-top:10px; border-top:1px solid rgba(255,255,255,0.2); padding-top:10px;' : '';
                    
                    if (isImage) {
                        fileHTML = `<div style="${marginTop}"><a href="/plvsystem/${data.file_path}" target="_blank"><img src="/plvsystem/${data.file_path}" style="max-width:100%; max-height:200px; border-radius:6px; border:2px solid rgba(0,0,0,0.1);"></a></div>`;
                    } else {
                        fileHTML = `<div style="${marginTop}"><a href="/plvsystem/${data.file_path}" target="_blank" class="btn btn-sm" style="background:rgba(0,0,0,0.1); color:white; border:1px solid rgba(0,0,0,0.1); text-decoration:none;"><i class="fa-solid fa-paperclip"></i> Download File</a></div>`;
                    }
                }

                const msgHTML = `
                    <div style="display:flex; flex-direction:column; align-items:flex-end; max-width:85%; align-self:flex-end;">
                        <div style="font-size:11px; color:var(--secondary); margin-bottom:4px;">
                            <strong>You</strong> <span style="font-weight:normal;">(${data.user_role})</span> • ${data.created_at}
                        </div>
                        <div style="background:var(--primary); color:white; padding:10px 15px; border-radius:15px 15px 0 15px; font-size:13px; box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                            ${data.message ? `<div style="line-height:1.5; white-space:pre-wrap;">${data.message}</div>` : ''}
                            ${fileHTML}
                        </div>
                    </div>
                `;

                if (chatBox.innerHTML.includes('No messages yet')) {
                    chatBox.innerHTML = '';
                }

                chatBox.insertAdjacentHTML('beforeend', msgHTML);
                chatBox.scrollTop = chatBox.scrollHeight;

                chatForm.reset();
                chatInputText.style.height = ''; 
                fileIndicator.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert("Failed to send message. Please try again.");
        })
        .finally(() => {
            chatInputText.disabled = false;
            chatSubmitBtn.disabled = false;
            chatSubmitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i>';
            chatInputText.focus();
        });
    });
});
</script>

<?php include 'views/layouts/footer.php'; ?>
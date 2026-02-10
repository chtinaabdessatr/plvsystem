<?php include 'views/layouts/header.php'; ?>

<div class="app-container">
    
    <div class="page-header">
        <div class="page-header__content">
            <h1 class="page-header__title">
                <span>File Manager</span>
                Recent Files
            </h1>
        </div>
        <a href="/plvsystem/dashboard" class="btn btn--secondary btn--sm">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <div class="card">
        <div class="card__body" style="padding: 0;">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 30%;">File Name</th>
                        <th style="width: 25%;">Order Context</th>
                        <th style="width: 15%;">Stage</th>
                        <th style="width: 15%;">Uploaded By</th>
                        <th style="width: 15%; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($files)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 60px 20px; color: var(--text-light);">
                                <i class="fa-solid fa-file-circle-xmark" style="font-size: 3rem; margin-bottom: 16px; opacity: 0.5;"></i>
                                <p style="font-size: 1.1rem; margin: 0;">No files have been uploaded yet.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($files as $f): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 40px; height: 40px; background: #f1f5f9; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: var(--secondary); font-size: 1.2rem;">
                                        <i class="fa-solid fa-file-lines"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: var(--text-dark); word-break: break-all;">
                                            <?= htmlspecialchars(basename($f['file_path'])) ?>
                                        </div>
                                        <div class="text-muted" style="font-size: 0.8rem;">
                                            <i class="fa-regular fa-calendar"></i> <?= date('M d, Y H:i', strtotime($f['created_at'])) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <a href="/plvsystem/order/view/<?= $f['order_id'] ?>" style="text-decoration: none; color: inherit;">
                                    <div style="font-weight: 600; color: var(--primary);">
                                        #<?= $f['order_id'] ?> - <?= htmlspecialchars($f['client_name']) ?>
                                    </div>
                                    <small class="text-muted">View Order Details</small>
                                </a>
                            </td>

                            <td>
                                <?php 
                                    $stageColors = [
                                        'created' => 'status-gray', 
                                        'design' => 'status-blue', 
                                        'printing' => 'status-orange', 
                                        'delivery' => 'status-teal', 
                                        'completed' => 'status-green'
                                    ];
                                    $badgeClass = $stageColors[$f['stage']] ?? 'status-gray';
                                ?>
                                <span class="badge badge--sm <?= $badgeClass ?>">
                                    <?= strtoupper($f['stage']) ?>
                                </span>
                            </td>

                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div class="user-avatar-sm">
                                        <i class="fa-solid fa-user"></i>
                                    </div>
                                    <span style="font-size: 0.9rem;"><?= htmlspecialchars($f['uploader_name']) ?></span>
                                </div>
                            </td>

                            <td style="text-align: right;">
                                <a href="/plvsystem/<?= $f['file_path'] ?>" target="_blank" class="btn btn--sm btn--success" title="Download File">
                                    <i class="fa-solid fa-download"></i> Download
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'views/layouts/footer.php'; ?>
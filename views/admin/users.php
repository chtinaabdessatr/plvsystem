<?php include 'views/layouts/header.php'; ?>

<div class="app-container">
    
    <div class="page-header">
        <div class="page-header__content">
            <h1 class="page-header__title">
                <span>System Access</span>
                User Management
            </h1>
        </div>
        <a href="/plvsystem/user/create" class="btn btn--primary">
            <i class="fa-solid fa-user-plus"></i> Add New User
        </a>
    </div>

    <div class="card">
        <div class="card__body" style="padding: 0;">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 25%;">User</th>
                        <th style="width: 25%;">Email</th>
                        <th style="width: 15%;">Role</th>
                        <th style="width: 15%;">Status</th>
                        <th style="width: 20%; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $u): ?>
                    <?php 
                        // Visual Logic
                        $roleColors = [
                            'admin' => 'badge--neutral', // Gray
                            'commercial' => 'status-blue',
                            'designer' => 'status-orange',
                            'printer' => 'status-teal',
                            'delivery' => 'status-green'
                        ];
                        $roleBadge = $roleColors[$u['role']] ?? 'badge--neutral';
                        
                        $statusClass = $u['is_active'] ? 'text-success' : 'text-danger';
                        $statusIcon = $u['is_active'] ? 'fa-circle-check' : 'fa-circle-xmark';
                        $statusText = $u['is_active'] ? 'Active' : 'Inactive';
                        
                        // Initials for Avatar
                        $initials = strtoupper(substr($u['name'], 0, 1));
                    ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div class="user-avatar-sm" style="background: var(--primary); color: white; font-weight: bold;">
                                    <?= $initials ?>
                                </div>
                                <span style="font-weight: 600; color: var(--text-dark);"><?= htmlspecialchars($u['name']) ?></span>
                            </div>
                        </td>

                        <td class="text-muted">
                            <?= htmlspecialchars($u['email']) ?>
                        </td>

                        <td>
                            <span class="badge badge--sm <?= $roleBadge ?>">
                                <?= ucfirst($u['role']) ?>
                            </span>
                        </td>

                        <td>
                            <span class="<?= $statusClass ?>" style="font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                                <i class="fa-solid <?= $statusIcon ?>"></i> <?= $statusText ?>
                            </span>
                        </td>

                        <td style="text-align: right;">
                            <div class="action-buttons-wrapper">
                                <a href="/plvsystem/user/edit/<?= $u['id'] ?>" class="btn btn--sm btn--secondary" title="Edit User">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                
                                <?php if($u['id'] != $_SESSION['user_id']): // Prevent deactivating self ?>
                                    <a href="/plvsystem/user/toggleActive/<?= $u['id'] ?>" class="btn btn--sm <?= $u['is_active'] ? 'btn--danger' : 'btn--success' ?>" title="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                        <i class="fa-solid <?= $u['is_active'] ? 'fa-ban' : 'fa-check' ?>"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'views/layouts/footer.php'; ?>
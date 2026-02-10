<?php include 'views/layouts/header.php'; ?>

<div class="app-container">
    
    <div class="page-header">
        <div class="page-header__content">
            <h1 class="page-header__title">
                <span>Access Control</span>
                User Management
            </h1>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px; align-items: start;">
        
        <div class="card card--padded" style="position: sticky; top: 20px;">
            <div class="card__header" style="margin: -24px -24px 24px -24px; padding: 16px 24px;">
                <h3><i class="fa-solid fa-user-plus"></i> Add New User</h3>
            </div>
            
            <form action="/plvsystem/user/create" method="POST">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <div class="input-group">
                        <input type="text" name="name" class="form-control" required placeholder="John Doe">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" required placeholder="john@company.com">
                </div>

                <div class="form-group">
                    <label class="form-label">Role</label>
                    <div class="radio-grid">
                        <label class="selection-card">
                            <input type="radio" name="role" value="admin" required>
                            <span class="card-content" style="padding: 10px;">
                                <span class="card-title text-danger"><i class="fa-solid fa-shield-halved"></i> Admin</span>
                            </span>
                        </label>
                        <label class="selection-card">
                            <input type="radio" name="role" value="commercial" required>
                            <span class="card-content" style="padding: 10px;">
                                <span class="card-title text-primary"><i class="fa-solid fa-briefcase"></i> Sales</span>
                            </span>
                        </label>
                         <label class="selection-card">
                            <input type="radio" name="role" value="designer" required>
                            <span class="card-content" style="padding: 10px;">
                                <span class="card-title" style="color:#8b5cf6"><i class="fa-solid fa-pen-ruler"></i> Design</span>
                            </span>
                        </label>
                        <label class="selection-card">
                            <input type="radio" name="role" value="printer" required>
                            <span class="card-content" style="padding: 10px;">
                                <span class="card-title" style="color:#f59e0b"><i class="fa-solid fa-print"></i> Print</span>
                            </span>
                        </label>
                         <label class="selection-card">
                            <input type="radio" name="role" value="delivery" required>
                            <span class="card-content" style="padding: 10px;">
                                <span class="card-title text-success"><i class="fa-solid fa-truck"></i> Ship</span>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required placeholder="••••••••">
                </div>

                <button type="submit" class="btn btn--primary btn--block btn--lg margin-top-md">
                    <i class="fa-solid fa-check"></i> Create Account
                </button>
            </form>
        </div>

        <div class="card">
            <div class="card__header">
                <h3><i class="fa-solid fa-users"></i> System Users</h3>
            </div>
            <div class="card__body" style="padding: 0;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $u): ?>
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div class="user-avatar-sm">
                                        <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:600; font-size:0.9rem;"><?= htmlspecialchars($u['name']) ?></div>
                                        <div class="text-muted" style="font-size:0.8rem;"><?= htmlspecialchars($u['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            
                            <td>
                                <?php 
                                    $roleColor = match($u['role']) {
                                        'admin' => 'badge--neutral',
                                        'designer' => 'status-blue',
                                        'printer' => 'status-orange',
                                        'delivery' => 'status-teal',
                                        default => 'badge--neutral'
                                    };
                                ?>
                                <span class="badge badge--sm <?= $roleColor ?>">
                                    <?= ucfirst($u['role']) ?>
                                </span>
                            </td>

                            <td>
                                <?php if($u['is_active']): ?>
                                    <span class="text-success" style="font-size:0.8rem; font-weight:600;">
                                        <i class="fa-solid fa-circle"></i> Active
                                    </span>
                                <?php else: ?>
                                    <span class="text-danger" style="font-size:0.8rem; font-weight:600;">
                                        <i class="fa-regular fa-circle"></i> Inactive
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td style="text-align: right;">
                                <?php if($u['id'] != $_SESSION['user_id']): ?>
                                    <a href="/plvsystem/user/toggleActive/<?= $u['id'] ?>" 
                                       class="btn btn--sm <?= $u['is_active'] ? 'btn--white-outline' : 'btn--success' ?>" 
                                       style="<?= $u['is_active'] ? 'color:var(--danger); border-color:var(--danger);' : '' ?>"
                                       title="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                        <i class="fa-solid <?= $u['is_active'] ? 'fa-ban' : 'fa-power-off' ?>"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size:0.8rem;">(You)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'views/layouts/footer.php'; ?>
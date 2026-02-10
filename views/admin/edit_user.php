<?php include 'views/layouts/header.php'; ?>

<div class="app-container" style="max-width: 600px;">
    
    <div class="page-header">
        <h1 class="page-header__title">
            <span>System Access</span>
            Edit User
        </h1>
        <a href="/plvsystem/user/index" class="btn btn--secondary btn--sm">
            <i class="fa-solid fa-arrow-left"></i> Cancel
        </a>
    </div>

    <form action="/plvsystem/user/update/<?= $user['id'] ?>" method="POST" class="card card--padded">
        
        <div class="form-section">
            <h3 class="form-section-title"><i class="fa-solid fa-id-card"></i> Account Details</h3>
            
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-control">
                        <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="commercial" <?= $user['role'] == 'commercial' ? 'selected' : '' ?>>Commercial</option>
                        <option value="designer" <?= $user['role'] == 'designer' ? 'selected' : '' ?>>Designer</option>
                        <option value="printer" <?= $user['role'] == 'printer' ? 'selected' : '' ?>>Printer</option>
                        <option value="delivery" <?= $user['role'] == 'delivery' ? 'selected' : '' ?>>Delivery</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Account Status</label>
                    <div class="radio-grid-large" style="grid-template-columns: 1fr 1fr; gap: 10px;">
                        <label class="selection-card" style="min-height: 40px;">
                            <input type="radio" name="is_active" value="1" <?= $user['is_active'] ? 'checked' : '' ?>>
                            <span class="card-content" style="padding: 10px;">
                                <span class="card-title text-success"><i class="fa-solid fa-check"></i> Active</span>
                            </span>
                        </label>
                        <label class="selection-card" style="min-height: 40px;">
                            <input type="radio" name="is_active" value="0" <?= !$user['is_active'] ? 'checked' : '' ?>>
                            <span class="card-content" style="padding: 10px;">
                                <span class="card-title text-danger"><i class="fa-solid fa-ban"></i> Inactive</span>
                            </span>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="form-group margin-top-md">
                <label class="form-label">Reset Password (Optional)</label>
                <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
            </div>
        </div>

        <div class="form-actions margin-top-lg" style="text-align: right;">
            <button type="submit" class="btn btn--primary btn--lg">
                <i class="fa-solid fa-floppy-disk"></i> Update User
            </button>
        </div>

    </form>
</div>

<?php include 'views/layouts/footer.php'; ?>
<?php include 'views/layouts/header.php'; ?>

<div class="form-header">
    <h2 style="margin:0; color:#2c3e50;">User Management</h2>
</div>

<div class="grid-2-col" style="grid-template-columns: 1fr 2fr; align-items: start;">
    
    <div class="card" style="position:sticky; top:20px;">
        <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:15px;">Add New User</h3>
        
        <form action="/plvsystem/user/create" method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" required placeholder="John Doe">
            </div>
            
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="john@company.com">
            </div>

            <div class="form-group">
                <label>Role</label>
                <select name="role">
                    <option value="designer">Designer</option>
                    <option value="printer">Printer (Imprimeur)</option>
                    <option value="delivery">Delivery</option>
                    <option value="commercial">Commercial</option>
                    <option value="admin" style="color:red;">Admin</option>
                </select>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn" style="width:100%;">+ Create Account</button>
        </form>
    </div>

    <div class="card">
        <h3 style="margin-top:0; padding-bottom:15px;">System Users</h3>
        
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $u): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($u['name']) ?></strong><br>
                        <small style="color:#888;"><?= htmlspecialchars($u['email']) ?></small>
                    </td>
                    <td>
                        <span class="badge" style="background:#ecf0f1; color:#2c3e50;">
                            <?= ucfirst($u['role']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if($u['is_active']): ?>
                            <span style="color:#27ae60; font-weight:bold; font-size:12px;">● Active</span>
                        <?php else: ?>
                            <span style="color:#e74c3c; font-weight:bold; font-size:12px;">● Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form action="/plvsystem/user/toggleStatus" method="POST">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <?php if($u['is_active']): ?>
                                <button class="btn-sm" style="background:#e74c3c; cursor:pointer;">Deactivate</button>
                            <?php else: ?>
                                <button class="btn-sm" style="background:#27ae60; cursor:pointer;">Activate</button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'views/layouts/footer.php'; ?>
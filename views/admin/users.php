<?php include 'views/layouts/header.php'; ?>
<div class="header-flex"><h2>Users</h2><a href="/plvsystem/user/create" class="btn">Add User</a></div>
<div class="card">
    <table>
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Active</th><th>Action</th></tr></thead>
        <tbody>
            <?php foreach($users as $u): ?>
            <tr>
                <td><?= $u['name'] ?></td>
                <td><?= $u['email'] ?></td>
                <td><?= $u['role'] ?></td>
                <td><?= $u['is_active'] ? 'Yes' : 'No' ?></td>
                <td><a href="/plvsystem/user/toggleActive/<?= $u['id'] ?>" class="btn-sm">Toggle</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include 'views/layouts/footer.php'; ?>
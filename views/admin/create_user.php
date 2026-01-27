<?php include 'views/layouts/header.php'; ?>
<h2>Add User</h2>
<form action="/plvsystem/user/create" method="POST" class="card form-layout">
    <label>Name</label><input type="text" name="name" required>
    <label>Email</label><input type="email" name="email" required>
    <label>Password</label><input type="password" name="password" required>
    <label>Role</label>
    <select name="role">
        <option value="commercial">Commercial</option>
        <option value="designer">Designer</option>
        <option value="printer">Printer</option>
        <option value="delivery">Delivery</option>
    </select>
    <button type="submit" class="btn">Create</button>
</form>
<?php include 'views/layouts/footer.php'; ?>
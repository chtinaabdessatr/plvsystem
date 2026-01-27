<?php include 'views/layouts/header.php'; ?>

<div class="header-flex">
    <h2>Create New Order</h2>
    <a href="/plvsystem/dashboard" class="btn-sm">Back</a>
</div>

<form action="/plvsystem/order/create" method="POST" enctype="multipart/form-data" class="card form-layout">
    
    <div class="grid-2-col">
        <div>
            <label>Client Name</label>
            <input type="text" name="client_name" required placeholder="e.g. Marjane Holding">
        </div>
        <div>
            <label>PLV Type</label>
            <input type="text" name="plv_type" required placeholder="e.g. Totem, Banner">
        </div>
    </div>

    <label>Description / Instructions</label>
    <textarea name="description" rows="4" placeholder="Enter dimensions, colors, or specific instructions..."></textarea>
    
    <div class="grid-2-col">
        <div>
            <label>Deadline</label>
            <input type="datetime-local" name="deadline">
        </div>
        <div>
            <label>Priority</label>
            <select name="priority">
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="urgent" style="color:red; font-weight:bold;">Urgent</option>
            </select>
        </div>
    </div>

    <div style="background: #f9f9f9; padding: 15px; border: 1px dashed #ccc; margin-bottom: 20px;">
        <label>Reference File (Optional)</label>
        <input type="file" name="ref_file">
        <small style="color:#666">Upload a logo, previous design, or brief (PDF, JPG, PNG).</small>
    </div>
    
    <button type="submit" class="btn">Create Order</button>
</form>

<?php include 'views/layouts/footer.php'; ?>
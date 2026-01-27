<?php include 'views/layouts/header.php'; ?>

<div class="header-flex">
    <h2>📂 Recent Files</h2>
    <a href="/plvsystem/dashboard" class="btn-sm">Back to Dashboard</a>
</div>

<div class="card">
    <?php if (empty($files)): ?>
        <p style="text-align:center; color:#777; padding:20px;">No files have been uploaded yet.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>File Name</th>
                    <th>Order Client</th>
                    <th>Stage</th>
                    <th>Uploaded By</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($files as $f): ?>
                <tr>
                    <td>
                        📄 <?= htmlspecialchars(basename($f['file_path'])) ?>
                    </td>
                    <td>
                        <a href="/plvsystem/order/view/<?= $f['order_id'] ?>" style="font-weight:bold; color:#2c3e50;">
                            #<?= $f['order_id'] ?> - <?= htmlspecialchars($f['client_name']) ?>
                        </a>
                    </td>
                    <td><span class="badge"><?= strtoupper($f['stage']) ?></span></td>
                    <td><?= htmlspecialchars($f['uploader_name']) ?></td>
                    <td><?= date('M d, H:i', strtotime($f['created_at'])) ?></td>
                    <td>
                        <a href="/plvsystem/<?= $f['file_path'] ?>" target="_blank" class="btn-sm" style="background:#27ae60;">
                            ⬇ Download
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php include 'views/layouts/footer.php'; ?>
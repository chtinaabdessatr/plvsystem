<?php include 'views/layouts/header.php'; ?>

<div class="app-container">
    
    <div class="page-header">
        <div class="page-header__content">
            <h1 class="page-header__title">
                <span>Accès au système</span>
                Gestion des utilisateurs
            </h1>
        </div>
        <a href="/plvsystem/user/create" class="btn btn--primary">
            <i class="fa-solid fa-user-plus"></i> Ajouter un nouvel utilisateur
        </a>
    </div>

    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
        <div style="background: #fef2f2; border: 1px solid #f87171; color: #b91c1c; padding: 12px; border-radius: 6px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-trash-check"></i> Utilisateur supprimé avec succès.
        </div>
    <?php endif; ?>
    <?php if(isset($_GET['error']) && $_GET['error'] == 'self_delete'): ?>
        <div style="background: #fffbeb; border: 1px solid #f59e0b; color: #b45309; padding: 12px; border-radius: 6px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-triangle-exclamation"></i> Action refusée : Vous ne pouvez pas supprimer votre propre compte !
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card__body" style="padding: 0;">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 25%;">Utilisateur</th>
                        <th style="width: 25%;">E-mail</th>
                        <th style="width: 15%;">Rôle</th>
                        <th style="width: 15%;">Statut</th>
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
                        $statusText = $u['is_active'] ? 'Actif' : 'Inactif';
                        
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
                            <div class="action-buttons-wrapper" style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                                <a href="/plvsystem/user/edit/<?= $u['id'] ?>" class="btn btn--sm btn--secondary" title="Edit User">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                
                                <?php if($u['id'] != $_SESSION['user_id']): // Prevent deactivating/deleting self ?>
                                    <a href="/plvsystem/user/toggleActive/<?= $u['id'] ?>" class="btn btn--sm <?= $u['is_active'] ? 'btn--danger' : 'btn--success' ?>" title="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                        <i class="fa-solid <?= $u['is_active'] ? 'fa-ban' : 'fa-check' ?>"></i>
                                    </a>

                                    <a href="/plvsystem/user/delete/<?= $u['id'] ?>" class="btn-icon-only" style="color: #ef4444; margin-left: 4px; padding: 6px;" title="Supprimer l'utilisateur" onclick="return confirm('⚠️ Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.');">
                                        <i class="fa-solid fa-trash-can"></i>
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
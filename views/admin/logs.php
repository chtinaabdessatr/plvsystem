<?php include 'views/layouts/header.php'; ?>

<div class="app-container">
<div class="page-header" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: center; justify-content: space-between;">
        <div class="page-header__content">
            <h1 class="page-header__title">
                <span>Sécurité & Conformité</span>
                <i class="fa-solid fa-shield-halved" style="color: var(--primary);"></i> Journal d'Audit Système
            </h1>
            <p class="text-muted" style="margin-top: 5px; font-size: 0.9rem;">
                <?php if(isset($_GET['search']) && trim($_GET['search']) !== ''): ?>
                    Résultats de recherche pour "<strong><?= htmlspecialchars($_GET['search']) ?></strong>"
                <?php else: ?>
                    Traçabilité des 200 dernières actions effectuées sur la plateforme.
                <?php endif; ?>
            </p>
        </div>

        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <form action="/plvsystem/log" method="GET" style="display: flex; gap: 8px; height: 38px;">
                <div style="position: relative; height: 100%;">
                    <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                    <input type="text" name="search" placeholder="Action, Utilisateur, IP..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" class="form-control" style="padding-left: 35px; width: 250px; height: 100%; margin: 0; box-sizing: border-box;">
                </div>
                <button type="submit" class="btn btn--secondary" style="height: 100%; margin: 0;">Filtrer</button>
                <?php if(isset($_GET['search']) && trim($_GET['search']) !== ''): ?>
                    <a href="/plvsystem/log" class="btn btn--neutral" style="display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; padding: 0; box-sizing: border-box; border-radius: 6px; background: #f1f5f9; border: 1px solid #e2e8f0; color: #64748b;" title="Effacer"><i class="fa-solid fa-xmark"></i></a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card__body" style="padding: 0;">
            <table class="table" style="font-size: 0.9rem;">
                <thead style="background: #f8fafc;">
                    <tr>
                        <th style="width: 15%;">Date & Heure</th>
                        <th style="width: 20%;">Utilisateur</th>
                        <th style="width: 15%;">Action</th>
                        <th style="width: 35%;">Détails</th>
                        <th style="width: 15%;">Adresse IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($logs)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 20px;">Aucun journal disponible.</td></tr>
                    <?php else: ?>
                        <?php foreach($logs as $log): ?>
                        <tr>
                            <td class="text-muted">
                                <i class="fa-regular fa-calendar" style="font-size: 0.8rem;"></i> <?= date('d/m/Y', strtotime($log['created_at'])) ?><br>
                                <i class="fa-regular fa-clock" style="font-size: 0.8rem;"></i> <?= date('H:i:s', strtotime($log['created_at'])) ?>
                            </td>
                            <td>
                                <?php if($log['user_name']): ?>
                                    <strong><?= htmlspecialchars($log['user_name']) ?></strong><br>
                                    <span class="badge badge--sm" style="font-size: 0.7rem; background: #e2e8f0; color: #475569;"><?= strtoupper($log['user_role']) ?></span>
                                <?php else: ?>
                                    <span style="font-style: italic; color: #94a3b8;">Système / Anonyme</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                    // Color coding actions
                                    $actionColor = '#64748b'; // default gray
                                    $actionIcon = 'fa-bolt';
                                    $action = strtolower($log['action']);
                                    
                                    if(strpos($action, 'delete') !== false || strpos($action, 'suppression') !== false) { $actionColor = '#ef4444'; $actionIcon = 'fa-trash-can'; }
                                    elseif(strpos($action, 'login') !== false || strpos($action, 'connexion') !== false) { $actionColor = '#10b981'; $actionIcon = 'fa-right-to-bracket'; }
                                    elseif(strpos($action, 'update') !== false || strpos($action, 'modification') !== false) { $actionColor = '#f59e0b'; $actionIcon = 'fa-pen'; }
                                    elseif(strpos($action, 'create') !== false || strpos($action, 'création') !== false) { $actionColor = '#3b82f6'; $actionIcon = 'fa-plus'; }
                                ?>
                                <span style="color: <?= $actionColor ?>; font-weight: 600;">
                                    <i class="fa-solid <?= $actionIcon ?>"></i> <?= htmlspecialchars($log['action']) ?>
                                </span>
                            </td>
                            <td style="word-break: break-word; color: #334155;">
                                <?= htmlspecialchars($log['details']) ?>
                            </td>
                            <td style="font-family: monospace; color: #94a3b8;">
                                <?= htmlspecialchars($log['ip_address']) ?>
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
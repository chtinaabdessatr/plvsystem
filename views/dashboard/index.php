<?php include 'views/layouts/header.php'; ?>

<?php
    // The $kpi stats are already calculated in the Controller now!
    // (We just use the $stats array directly if available)
    if (!isset($stats) || empty($stats)) {
        $stats = ['total' => 0, 'active' => 0, 'completed' => 0, 'urgent' => 0];
        if (!empty($orders)) {
            foreach($orders as $o) {
                $stats['total']++;
                if($o['status'] == 'completed') $stats['completed']++;
                else $stats['active']++;
            }
        }
    } else {
        // If we have detailed stats from Admin search
        $stats['active'] = ($stats['design'] ?? 0) + ($stats['printing'] ?? 0) + ($stats['delivery'] ?? 0);
    }

    // --- PREPARE CHART DATA FOR ADMIN ---
    $chartDates = [];
    $chartVolumes = [];
    if ($_SESSION['role'] == 'admin' && !empty($orders)) {
        $dailyCounts = [];
        foreach($orders as $o) {
            $date = date('d/m', strtotime($o['created_at']));
            $dailyCounts[$date] = ($dailyCounts[$date] ?? 0) + 1;
        }
        // Reverse so the oldest dates are on the left, newest on the right
        $dailyCounts = array_reverse($dailyCounts, true);
        $chartDates = array_keys($dailyCounts);
        $chartVolumes = array_values($dailyCounts);
    }
?>

<div class="app-container">
    
    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
        <div style="background: #fef2f2; border: 1px solid #f87171; color: #b91c1c; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-trash-check" style="font-size: 20px;"></i>
            <div><strong>Succès !</strong> La commande a été définitivement supprimée.</div>
        </div>
    <?php endif; ?>
    <?php if(isset($_GET['error']) && $_GET['error'] == 'already_claimed'): ?>
        <div style="background: #fef2f2; border: 1px solid #f87171; color: #b91c1c; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-circle-exclamation" style="font-size: 20px;"></i>
            <div>
                <strong>Oups ! Trop tard.</strong><br>
                <span style="font-size: 13px;">Un autre collègue vient juste de récupérer cette mission.</span>
            </div>
        </div>
    <?php endif; ?>

    <div class="page-header" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: center; justify-content: space-between;">
        <div class="page-header__content">
            <h1 class="page-header__title">
            <?= __('dashboard') ?>
            </h1>
        </div>

        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            
            <form action="/plvsystem/dashboard" method="GET" style="display: flex; gap: 8px; height: 38px;">
                <div style="position: relative; height: 100%;">
                    <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                    <input type="text" name="search" placeholder="<?= __('search') ?>" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" class="form-control" style="padding-left: 35px; width: 220px; height: 100%; margin: 0; box-sizing: border-box;">
                </div>
                <button type="submit" class="btn btn--secondary" style="height: 100%; margin: 0;">Filtrer</button>
                <?php if(isset($_GET['search']) && trim($_GET['search']) !== ''): ?>
                    <a href="/plvsystem/dashboard" class="btn btn--neutral" style="display: flex; align-items: center; padding: 0 12px; height: 100%; box-sizing: border-box;" title="Effacer"><i class="fa-solid fa-xmark"></i></a>
                <?php endif; ?>
            </form>

            <?php if($_SESSION['role'] == 'admin'): ?>
                <button onclick="document.getElementById('exportModal').classList.add('active')" class="btn btn--secondary" style="background: #10b981; color: white; border-color: #10b981; height: 38px;">
                    <i class="fa-solid fa-file-csv"></i> Exporter
                </button>
            <?php endif; ?>

            <?php if($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'commercial'): ?>
                <a href="/plvsystem/order/create" class="btn btn--primary" style="height: 38px; display: inline-flex; align-items: center;">
                    <i class="fa-solid fa-plus"></i> Nouvelle commande
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="kpi-grid">

        <div class="kpi-card blue">
            <div class="kpi-icon"><i class="fa-solid fa-clipboard-list"></i></div>
            <div class="kpi-info">
                <h3><?= ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'commercial') ? 'Total Commandes' : 'Mes Missions' ?></h3>
                <div class="number"><?= $stats['total'] ?></div>
            </div>
        </div>

        <div class="kpi-card orange">
            <div class="kpi-icon"><i class="fa-solid fa-gears"></i></div>
            <div class="kpi-info">
                <h3>En cours</h3>
                <div class="number"><?= $stats['active'] ?></div>
            </div>
        </div>

        <div class="kpi-card green">
            <div class="kpi-icon"><i class="fa-solid fa-flag-checkered"></i></div>
            <div class="kpi-info">
                <h3>Terminé</h3>
                <div class="number"><?= $stats['completed'] ?></div>
            </div>
        </div>

        <?php if($_SESSION['role'] == 'admin'): ?>
        <div class="kpi-card red">
            <div class="kpi-icon"><i class="fa-solid fa-user-clock"></i></div>
            <div class="kpi-info">
                <h3>Besoins à attribuer</h3>
                <div class="number"><?= $stats['urgent'] ?? 0 ?></div>
            </div>
        </div>
        <?php endif; ?>
    </div>
      <!--THe chart-->

      <?php if($_SESSION['role'] == 'admin'): ?>
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 25px;">
        
        <div class="card" style="padding: 20px;">
            <h3 style="margin-top: 0; color: var(--text-dark); font-size: 1.1rem; border-bottom: 1px solid var(--border); padding-bottom: 10px; margin-bottom: 15px;">
                <i class="fa-solid fa-chart-column" style="color: var(--primary);"></i> Volume de Production (Commandes Récentes)
            </h3>
            <div style="position: relative; height: 250px; width: 100%;">
                <canvas id="volumeChart"></canvas>
            </div>
        </div>

        <div class="card" style="padding: 20px;">
            <h3 style="margin-top: 0; color: var(--text-dark); font-size: 1.1rem; border-bottom: 1px solid var(--border); padding-bottom: 10px; margin-bottom: 15px;">
                <i class="fa-solid fa-chart-pie" style="color: #f59e0b;"></i> Répartition par Étape
            </h3>
            <div style="position: relative; height: 250px; width: 100%;">
                <canvas id="stageChart"></canvas>
            </div>
        </div>

    </div>
    <?php endif; ?>

    <?php if(in_array(strtolower($_SESSION['role']), ['designer', 'printer', 'delivery']) && empty($_GET['search'])): ?>
        <div class="card margin-top-lg" style="border-left: 4px solid #10b981;">
            <div class="card__header" style="display:flex; justify-content:space-between; align-items:center;">
                <h3 style="color: #059669; margin:0;"><i class="fa-solid fa-hand-sparkles"></i> Missions disponibles</h3>
                <span class="badge badge--sm" style="background:#10b981; color:white; font-size:12px;">
                    <?= count($availableOrders ?? []) ?> en attente
                </span>
            </div>
            <div class="card__body" style="padding: 0;">
                <?php if(empty($availableOrders)): ?>
                    <div style="text-align: center; padding: 30px; color: var(--text-light);">
                        <i class="fa-regular fa-face-smile" style="font-size: 2rem; margin-bottom: 10px;"></i><br>
                        Aucune nouvelle mission disponible pour le moment.
                    </div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr style="background:#f0fdf4;">
                                <th style="width: 15%;">Référence</th>
                                <th style="width: 30%;">Client</th>
                                <th style="width: 25%;">Étape</th>
                                <th style="width: 30%; text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($availableOrders as $ao): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: var(--text-dark);">#<?= $ao['id'] ?></div>
                                    <?php if($ao['priority'] == 'Urgent'): ?>
                                        <span class="badge badge--sm" style="background:#fee2e2; color:#dc2626;">URGENT 🚨</span>
                                    <?php elseif($ao['priority'] == 'High'): ?>
                                        <span class="badge badge--sm" style="background:#ffedd5; color:#ea580c;">HIGH 🔥</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight: 500;"><?= htmlspecialchars($ao['client_name']) ?></td>
                                <td><span class="badge badge--sm status-blue"><?= strtoupper($ao['current_stage']) ?></span></td>
                                <td style="text-align: right;">
                                    <a href="/plvsystem/order/view/<?= $ao['id'] ?>" class="btn btn--sm btn--primary" style="background:#10b981; border-color:#10b981;">
                                        <i class="fa-solid fa-hand-holding-hand"></i> Voir & Récupérer
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="card margin-top-lg">
        <div class="card__header">
            <h3>
                <i class="fa-solid fa-table-list"></i> 
                <?php if(isset($_GET['search']) && trim($_GET['search']) !== ''): ?>
                    Résultats de recherche pour "<?= htmlspecialchars($_GET['search']) ?>"
                <?php else: ?>
                    <?= in_array(strtolower($_SESSION['role']), ['designer', 'printer', 'delivery']) ? 'Mes missions actives' : 'Commandes récentes' ?>
                <?php endif; ?>
            </h3>
        </div>
        <div class="card__body" style="padding: 0;">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 10%;">Référence</th>
                        <th style="width: 20%;">Client</th>
                        <th style="width: 15%;">Statut</th>
                        <th style="width: 20%;">Mission</th>
                        <th style="width: 15%;">Progression</th>
                        <th style="width: 20%; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($orders)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-light);">
                                <i class="fa-solid fa-folder-open" style="font-size: 2rem; margin-bottom: 10px;"></i><br>
                                Aucune commande trouvée.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($orders as $o): ?>
                        <?php 
                            $isRefused = isset($o['my_status']) && $o['my_status'] == 'refused';
                            $rowStyle = $isRefused ? 'background-color: #fef2f2;' : ''; 
                            $stageClasses = ['created'=>'status-gray', 'design'=>'status-blue', 'printing'=>'status-orange', 'delivery'=>'status-teal', 'completed'=>'status-green'];
                            $badgeClass = $stageClasses[strtolower($o['current_stage'])] ?? 'status-gray';
                        ?>
                        <tr style="<?= $rowStyle ?>">
                            <td>
                                <div style="font-weight: 700; color: var(--text-dark);">#<?= $o['id'] ?></div>
                                <div class="text-muted" style="font-size: 0.8rem;">
                                    <i class="fa-regular fa-clock"></i> <?= date('M d', strtotime($o['created_at'])) ?>
                                </div>
                            </td>
                            
                            <td style="font-weight: 500;"><?= htmlspecialchars($o['client_name']) ?></td>
                            
                            <td>
                                <?php if($isRefused): ?>
                                    <span class="badge badge--sm" style="background:var(--danger, #ef4444); color:white;"><i class="fa-solid fa-ban"></i> Refusé</span>
                                <?php else: ?>
                                    <span class="badge badge--sm <?= $badgeClass ?>"><?= strtoupper($o['current_stage']) ?></span>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <?php if(isset($o['assigned_to']) && $o['assigned_to']): ?>
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <div class="user-avatar-sm"><i class="fa-solid fa-user"></i></div>
                                        <span style="font-size: 0.9rem;"><?= htmlspecialchars($o['assigned_to']) ?></span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted" style="font-style:italic; font-size: 0.85rem;">Non attribué</span>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <?php 
                                    $stage = strtolower($o['current_stage']);
                                    $s1 = in_array($stage, ['design','printing','delivery','completed']) ? 'var(--primary, #2563eb)' : '#e2e8f0';
                                    $s2 = in_array($stage, ['printing','delivery','completed']) ? 'var(--primary, #2563eb)' : '#e2e8f0';
                                    $s3 = in_array($stage, ['delivery','completed']) ? 'var(--primary, #2563eb)' : '#e2e8f0';
                                ?>
                                <div class="progress-mini">
                                    <div style="background:<?= $s1 ?>"></div>
                                    <div style="background:<?= $s2 ?>"></div>
                                    <div style="background:<?= $s3 ?>"></div>
                                </div>
                            </td>
                            
                            <td style="text-align: right;">
                                <div class="action-buttons-wrapper" style="display: flex; gap: 8px; justify-content: flex-end;">
                                    
                                    <a href="/plvsystem/order/view/<?= $o['id'] ?>" class="btn-icon-only" title="Voir les détails">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>

                                    <?php if($_SESSION['role'] == 'admin'): ?>
                                        <a href="/plvsystem/order/delete/<?= $o['id'] ?>" class="btn-icon-only" style="color: #ef4444;" title="Supprimer la commande" onclick="return confirm('⚠️ Êtes-vous sûr de vouloir supprimer cette commande (ID: <?= $o['id'] ?>)? Cette action supprimera également tous les fichiers et l\'historique associés.');">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </a>
                                    <?php endif; ?>

                                    <?php 
                                        $needsAssignment = empty($o['assigned_to']);
                                        if($_SESSION['role'] == 'admin' && strtolower($o['status']) != 'completed' && $needsAssignment): 
                                    ?>
                                        <button onclick="openAssignModal(<?= $o['id'] ?>, '<?= $o['current_stage'] ?>')" class="btn btn--sm btn--primary">
                                            <i class="fa-solid fa-user-plus"></i> Attribuer
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="assignModal" class="modal">
    <div class="modal__content">
        <div class="modal__header">
            <h3>Attribuer une tâche</h3>
            <span onclick="document.getElementById('assignModal').classList.remove('active')" class="modal__close">&times;</span>
        </div>
        <form action="/plvsystem/order/assign" method="POST">
            <input type="hidden" name="order_id" id="modalOrderId">
            <input type="hidden" name="stage" id="modalStage">
            <div class="form-group">
                <label class="form-label">Sélectionner l'utilisateur :</label>
                <select name="user_id" id="modalUserSelect" class="form-control" required></select>
            </div>
            <div class="modal__actions">
                <button type="button" onclick="document.getElementById('assignModal').classList.remove('active')" class="btn btn--neutral">Annuler</button>
                <button type="submit" class="btn btn--primary">Confirmer</button>
            </div>
        </form>
    </div>
</div>

<div id="exportModal" class="modal">
    <div class="modal__content" style="max-width: 400px;">
        <div class="modal__header">
            <h3>Exporter le rapport</h3>
            <span onclick="document.getElementById('exportModal').classList.remove('active')" class="modal__close">&times;</span>
        </div>
        <form action="/plvsystem/order/export" method="POST">
            <div class="form-group">
                <label class="form-label">Date de début :</label>
                <input type="date" name="start_date" class="form-control" required value="<?= date('Y-m-d', strtotime('-30 days')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Date de fin :</label>
                <input type="date" name="end_date" class="form-control" required value="<?= date('Y-m-d') ?>">
            </div>
            <div class="modal__actions">
                <button type="button" onclick="document.getElementById('exportModal').classList.remove('active')" class="btn btn--neutral">Annuler</button>
                <button type="submit" class="btn" style="background: #10b981; color: white;"><i class="fa-solid fa-download"></i> Télécharger CSV</button>
            </div>
        </form>
    </div>
</div>
<script>
function openAssignModal(orderId, currentStage) {
    const modal = document.getElementById('assignModal');
    modal.classList.add('active');
    document.getElementById('modalOrderId').value = orderId;
    let select = document.getElementById('modalUserSelect');
    select.innerHTML = '<option disabled selected>Chargement...</option>';
    let fetchStage = currentStage.toLowerCase() === 'created' ? 'design' : currentStage;

    fetch('/plvsystem/order/getAssignData?current_stage=' + fetchStage)
        .then(response => response.json())
        .then(data => {
            select.innerHTML = '';
            if (!data.users || data.users.length === 0) {
                select.innerHTML = '<option disabled>Aucun utilisateur trouvé</option>';
            } else {
                data.users.forEach(user => {
                    let option = document.createElement('option');
                    option.value = user.id;
                    option.text = user.name + ' (' + user.role + ')';
                    select.appendChild(option);
                });
            }
            document.getElementById('modalStage').value = data.nextStage;
        });
}

document.addEventListener("DOMContentLoaded", function() {
    // 🔄 SMART AUTO-SYNC: Refresh dashboard every 30 seconds to prevent stale data
    // ONLY refresh if we are NOT searching and NOT using a modal
    const isSearching = new URLSearchParams(window.location.search).has('search');
    
    if (!isSearching) {
        setInterval(function() {
            let modalActive = document.querySelector('.modal.active');
            if (!modalActive) {
                window.location.reload();
            }
        }, 30000); // 30 seconds
    }
});
</script>


<?php include 'views/layouts/footer.php'; ?>
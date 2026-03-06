<?php include 'views/layouts/header.php'; ?>

<style>
    .kanban-board {
        display: flex;
        gap: 20px;
        overflow-x: auto;
        padding-bottom: 20px;
        align-items: flex-start;
        height: calc(100vh - 160px); /* Fill the screen */
    }
    .kanban-col {
        min-width: 320px;
        width: 320px;
        background: #f1f5f9;
        border-radius: 8px;
        display: flex;
        flex-direction: column;
        max-height: 100%;
        border: 1px solid #e2e8f0;
    }
    .kanban-col-header {
        padding: 15px;
        font-weight: 700;
        color: #334155;
        border-bottom: 2px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: white;
        border-radius: 8px 8px 0 0;
    }
    .kanban-col-body {
        padding: 15px;
        flex-grow: 1;
        overflow-y: auto;
        min-height: 150px;
    }
    .kanban-card {
        background: white;
        border-radius: 6px;
        padding: 15px;
        margin-bottom: 15px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border: 1px solid #e2e8f0;
        border-left: 4px solid var(--primary);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    /* Change border color based on priority */
    .kanban-card.prio-haute { border-left-color: #ef4444; }
    .kanban-card.prio-normale { border-left-color: #3b82f6; }
    
    .kanban-card:hover {
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    /* Ghost class for the drag shadow */
    .sortable-ghost { opacity: 0.4; background: #e2e8f0; }
    
    /* Hide scrollbar for a cleaner look */
    .kanban-board::-webkit-scrollbar { height: 8px; }
    .kanban-board::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
</style>

<div class="app-container" style="max-width: 100%; padding-right: 20px;">
    
    <div class="page-header" style="margin-bottom: 20px;">
        <div class="page-header__content">
            <h1 class="page-header__title">
                <i class="fa-solid fa-list-check" style="color: var(--primary);"></i> Tableau de Production
            </h1>
            <p class="text-muted" style="margin-top: 5px; font-size: 0.9rem;">
                Vue en temps réel du flux de travail. 
                <?php if($_SESSION['role'] == 'admin'): ?>
                    <strong>(Glissez et déposez pour forcer le déplacement).</strong>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <div class="kanban-board">
        
        <?php 
        // Define our columns visually
        $columns = [
            'created' => ['title' => 'Nouveau (Non assigné)', 'icon' => 'fa-inbox', 'color' => '#64748b'],
            'design' => ['title' => 'En Design', 'icon' => 'fa-pen-nib', 'color' => '#3b82f6'],
            'printing' => ['title' => 'En Impression', 'icon' => 'fa-print', 'color' => '#f59e0b'],
            'delivery' => ['title' => 'En Livraison', 'icon' => 'fa-truck-fast', 'color' => '#14b8a6'],
            'completed' => ['title' => 'Terminé', 'icon' => 'fa-check-double', 'color' => '#10b981']
        ];
        ?>

        <?php foreach($columns as $stageKey => $colDef): ?>
            <div class="kanban-col">
                <div class="kanban-col-header" style="border-bottom-color: <?= $colDef['color'] ?>;">
                    <span><i class="fa-solid <?= $colDef['icon'] ?>" style="color: <?= $colDef['color'] ?>;"></i> <?= $colDef['title'] ?></span>
                    <span class="badge badge--sm badge--neutral"><?= count($board[$stageKey]) ?></span>
                </div>
                
                <div class="kanban-col-body" data-stage="<?= $stageKey ?>">
                    
                    <?php foreach($board[$stageKey] as $o): ?>
                        <?php 
                            // Determine Priority Color
                            $prioClass = strtolower($o['priority']) == 'haute' ? 'prio-haute' : 'prio-normale';
                        ?>
                        <div class="kanban-card <?= $prioClass ?>" data-id="<?= $o['id'] ?>" <?= $_SESSION['role'] == 'admin' ? 'style="cursor: grab;"' : '' ?>>
                            
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                <span style="font-size: 0.8rem; font-weight: 700; color: #64748b;">#<?= $o['id'] ?></span>
                                <span class="badge badge--sm <?= $prioClass == 'prio-haute' ? 'badge--danger' : 'badge--primary' ?>">
                                    <?= ucfirst($o['priority']) ?>
                                </span>
                            </div>
                            
                            <h4 style="margin: 0 0 5px 0; font-size: 1rem; color: #0f172a;">
                                <a href="/plvsystem/order/view/<?= $o['id'] ?>" style="text-decoration: none; color: inherit;"><?= htmlspecialchars($o['client_name']) ?></a>
                            </h4>
                            <p style="margin: 0 0 12px 0; font-size: 0.85rem; color: #475569;">
                                <?= htmlspecialchars($o['plv_type']) ?>
                            </p>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 10px;">
                                <div style="font-size: 0.75rem; color: #94a3b8;">
                                    <i class="fa-regular fa-calendar"></i> <?= date('d M', strtotime($o['created_at'])) ?>
                                </div>
                                <a href="/plvsystem/order/view/<?= $o['id'] ?>" class="btn btn--sm btn--secondary" style="padding: 4px 8px; font-size: 0.75rem;">Voir</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                </div>
            </div>
        <?php endforeach; ?>
        
    </div>
</div>

<?php if($_SESSION['role'] == 'admin'): ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const columns = document.querySelectorAll('.kanban-col-body');
    
    columns.forEach(col => {
        new Sortable(col, {
            group: 'kanban', // Allows dragging between columns
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function (evt) {
                const itemEl = evt.item; 
                const newStage = evt.to.dataset.stage;
                const oldStage = evt.from.dataset.stage;
                const orderId = itemEl.dataset.id;
                
                // Only trigger the database if they actually moved it to a NEW column
                if (newStage !== oldStage) {
                    
                    const formData = new FormData();
                    formData.append('order_id', orderId);
                    formData.append('new_stage', newStage);

                    // Silently update the database via AJAX
                    fetch('/plvsystem/kanban/updateStage', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if(data.success) {
                            // Automatically update the counters at the top of the columns!
                            const oldHeaderBadge = evt.from.previousElementSibling.querySelector('.badge');
                            const newHeaderBadge = evt.to.previousElementSibling.querySelector('.badge');
                            
                            oldHeaderBadge.innerText = parseInt(oldHeaderBadge.innerText) - 1;
                            newHeaderBadge.innerText = parseInt(newHeaderBadge.innerText) + 1;
                        } else {
                            alert("Erreur de synchronisation !");
                            window.location.reload(); // Reset the board if it failed
                        }
                    });
                }
            },
        });
    });
});
</script>
<?php endif; ?>

<?php include 'views/layouts/footer.php'; ?>
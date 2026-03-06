<?php include 'views/layouts/header.php'; ?>

<div class="app-container" style="max-width: 900px;">
    
    <div class="page-header">
        <h1 class="page-header__title">
            <span>Update Order</span>
            Modification Commande #<?= $order['id'] ?>
        </h1>
        <a href="/plvsystem/order/view/<?= $order['id'] ?>" class="btn btn--secondary btn--sm">
            <i class="fa-solid fa-arrow-left"></i> Retour
        </a>
    </div>

    <form action="/plvsystem/order/edit/<?= $order['id'] ?>" method="POST" class="card card--padded">
        
        <div class="form-section">
            <h3 class="form-section-title"><i class="fa-solid fa-pen-to-square"></i> 1. Informations Générales</h3>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Client / Propriété</label>
                    <input type="text" name="client_name" class="form-control" value="<?= htmlspecialchars($order['client_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Contact du client (Optionnel)</label>
                    <input type="text" name="client_contact" class="form-control" value="<?= htmlspecialchars($order['client_contact'] ?? '') ?>" placeholder="Numéro de téléphone ou Email...">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Deadline (Échéance)</label>
                    <input type="datetime-local" name="deadline" class="form-control" 
                           value="<?= date('Y-m-d\TH:i', strtotime($order['deadline'])) ?>">
                </div>
            </div>
        </div>

        <div class="divider"></div>

        <div class="form-section">
            <h3 class="form-section-title"><i class="fa-solid fa-stopwatch"></i> 2. Priorité</h3>
            
            <div class="radio-grid">
                <label class="selection-card">
                    <input type="radio" name="priority" value="medium" <?= $order['priority'] == 'medium' ? 'checked' : '' ?>>
                    <span class="card-content" style="min-height: 50px; padding: 10px;">
                        <span class="card-title" style="color: #f59e0b;"><i class="fa-solid fa-layer-group"></i> Normal</span>
                    </span>
                </label>

                <label class="selection-card">
                    <input type="radio" name="priority" value="high" <?= $order['priority'] == 'high' ? 'checked' : '' ?>>
                    <span class="card-content" style="min-height: 50px; padding: 10px;">
                        <span class="card-title" style="color: #e67e22;"><i class="fa-solid fa-bolt"></i> Élevée</span>
                    </span>
                </label>

                <label class="selection-card">
                    <input type="radio" name="priority" value="urgent" <?= $order['priority'] == 'urgent' ? 'checked' : '' ?>>
                    <span class="card-content" style="min-height: 50px; padding: 10px;">
                        <span class="card-title" style="color: #ef4444;"><i class="fa-solid fa-fire"></i> Urgent</span>
                    </span>
                </label>
            </div>
        </div>

        <div class="divider"></div>

        <div class="form-section">
            <h3 class="form-section-title"><i class="fa-solid fa-shapes"></i> 3. Type de Commande</h3>
            
            <div class="radio-grid-large">
                <label class="selection-card">
                    <input type="radio" name="plv_type" value="OneWay" <?= $order['plv_type'] == 'OneWay' ? 'checked' : '' ?>>
                    <span class="card-content">
                        <i class="fa-solid fa-border-all icon-large"></i>
                        <span class="card-title">One Way</span>
                    </span>
                </label>

                <label class="selection-card">
                    <input type="radio" name="plv_type" value="Panneau" <?= $order['plv_type'] == 'Panneau' ? 'checked' : '' ?>>
                    <span class="card-content">
                        <i class="fa-solid fa-sign-hanging icon-large"></i>
                        <span class="card-title">Panneaux</span>
                    </span>
                </label>
            </div>
        </div>

        <div class="divider"></div>

        <div class="form-section">
            <h3 class="form-section-title"><i class="fa-solid fa-file-code"></i> 4. Détails Techniques</h3>
            
            <div class="state-box state-box--neutral" style="text-align: left; margin-bottom: 10px; padding: 15px;">
                <small class="text-muted"><i class="fa-solid fa-triangle-exclamation"></i> <strong>Note:</strong> Modifiez le texte ci-dessous avec précaution pour ne pas casser l'affichage automatique.</small>
            </div>

            <textarea name="description" class="form-control" rows="12" style="font-family: monospace; font-size: 0.9rem; background: #fafafa;"><?= htmlspecialchars($order['description']) ?></textarea>
        </div>

        <div class="form-actions margin-top-lg" style="text-align: right;">
            <button type="submit" class="btn btn--primary btn--lg">
                <i class="fa-solid fa-floppy-disk"></i> Enregistrer les Modifications
            </button>
        </div>

    </form>
</div>

<?php include 'views/layouts/footer.php'; ?>
<?php include 'views/layouts/header.php'; ?>

<div class="app-container">
    
    <div class="page-header">
        <div class="page-header__content">
            <h1 class="page-header__title">
                <span>Mon Espace</span>
                Paramètres du compte
            </h1>
        </div>
    </div>

    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
        <div style="background: #ecfdf5; border: 1px solid #10b981; color: #047857; padding: 12px; border-radius: 6px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-circle-check"></i> Vos informations ont été mises à jour avec succès.
        </div>
    <?php endif; ?>

    <div class="card" style="max-width: 600px;">
        <div class="card__body" style="padding: 25px;">
            
            <form action="/plvsystem/account/update" method="POST">
                
                <h3 style="margin-top: 0; margin-bottom: 15px; font-size: 1.1rem; color: var(--text-dark); border-bottom: 1px solid var(--border); padding-bottom: 10px;">
                    <i class="fa-solid fa-user-pen" style="color: var(--primary);"></i> Informations Personnelles
                </h3>

                <div class="form-group">
                    <label class="form-label">Nom complet</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Adresse E-mail</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>

                <div style="margin: 30px 0;"></div>

                <h3 style="margin-top: 0; margin-bottom: 5px; font-size: 1.1rem; color: var(--text-dark); border-bottom: 1px solid var(--border); padding-bottom: 10px;">
                    <i class="fa-solid fa-lock" style="color: #f59e0b;"></i> Sécurité
                </h3>
                <p style="font-size: 0.85rem; color: var(--text-light); margin-bottom: 20px;">Laissez ce champ vide si vous ne souhaitez pas modifier votre mot de passe actuel.</p>

                <div class="form-group">
                    <label class="form-label">Nouveau mot de passe</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" autocomplete="new-password">
                </div>

                <div style="margin-top: 30px; display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn btn--primary" style="padding: 10px 20px;">
                        <i class="fa-solid fa-floppy-disk"></i> Enregistrer les modifications
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<?php include 'views/layouts/footer.php'; ?>
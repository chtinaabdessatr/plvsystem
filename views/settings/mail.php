<?php include 'views/layouts/header.php'; ?>

<div class="app-container">
    
    <div class="page-header">
        <div class="page-header__content">
            <h1 class="page-header__title">
                <span>Administration</span>
                <i class="fa-solid fa-envelope-open-text" style="color: var(--primary);"></i> Configuration Serveur Mail (SMTP)
            </h1>
            <p class="text-muted" style="margin-top: 5px; font-size: 0.9rem;">Configurez les accès pour l'envoi des notifications automatiques.</p>
        </div>
    </div>

    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
        <div style="background: #ecfdf5; border: 1px solid #10b981; color: #047857; padding: 12px; border-radius: 6px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-circle-check"></i> Configuration SMTP enregistrée avec succès.
        </div>
    <?php endif; ?>

    <div class="card" style="max-width: 600px;">
        <div class="card__body" style="padding: 25px;">
            
            <form action="/plvsystem/settings/mail" method="POST">
                
                <h3 style="margin-top: 0; margin-bottom: 15px; font-size: 1.1rem; color: var(--text-dark); border-bottom: 1px solid var(--border); padding-bottom: 10px;">
                    <i class="fa-solid fa-server" style="color: #64748b;"></i> Paramètres du Serveur
                </h3>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">Hôte SMTP (ex: smtp.gmail.com)</label>
                        <input type="text" name="smtp_host" class="form-control" value="<?= htmlspecialchars($settings['smtp_host'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Port (ex: 587 ou 465)</label>
                        <input type="number" name="smtp_port" class="form-control" value="<?= htmlspecialchars($settings['smtp_port'] ?? '587') ?>" required>
                    </div>
                </div>

                <div style="margin: 30px 0;"></div>

                <h3 style="margin-top: 0; margin-bottom: 15px; font-size: 1.1rem; color: var(--text-dark); border-bottom: 1px solid var(--border); padding-bottom: 10px;">
                    <i class="fa-solid fa-shield-halved" style="color: #f59e0b;"></i> Authentification
                </h3>

                <div class="form-group">
                    <label class="form-label">Nom d'expéditeur (Affiché dans l'email)</label>
                    <input type="text" name="smtp_from" class="form-control" value="<?= htmlspecialchars($settings['smtp_from'] ?? 'LAP PLV System') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Email Utilisateur</label>
                    <input type="email" name="smtp_user" class="form-control" value="<?= htmlspecialchars($settings['smtp_user'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Mot de passe SMTP / Mot de passe d'application</label>
                    <input type="password" name="smtp_pass" class="form-control" placeholder="••••••••" autocomplete="new-password">
                    <small style="color: #94a3b8; display: block; margin-top: 5px;">Laissez vide pour conserver le mot de passe actuel.</small>
                </div>

                <div style="margin-top: 30px; display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn btn--primary" style="padding: 10px 20px;">
                        <i class="fa-solid fa-floppy-disk"></i> Enregistrer la configuration
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<?php include 'views/layouts/footer.php'; ?>
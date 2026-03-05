<?php include 'views/layouts/header.php'; ?>
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);?>

<div class="app-container" style="max-width: 900px;">
    
    <div class="page-header">
        <h1 class="page-header__title">
            <span>New Order</span>
            Nouvelle Commande
        </h1>
    </div>

    <form action="/plvsystem/order/create" method="POST" enctype="multipart/form-data" class="card card--padded">
        
        <div class="form-section">
            <h3 class="form-section-title"><i class="fa-solid fa-user-tag"></i> 1. Informations</h3>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Commercial</label>
                    <input type="text" name="commercial_name" class="form-control" value="<?= $_SESSION['name'] ?? '' ?>" readonly style="background:#f9fafb;">
                </div>
                <div class="form-group">
                    <label class="form-label">Nom de Propriété (Client)</label>
                    <input type="text" name="client_name" class="form-control" placeholder="Ex: Marjane, Pharmacie..." required>
                </div>
            </div>

            <div class="form-group margin-top-md">
                <label class="form-label">Zone Géographique</label>
                <div class="radio-grid">
                    <?php foreach(['R0', 'R1', 'R2', 'R3', 'R5', 'R6'] as $z): ?>
                    <label class="selection-card">
                        <input type="radio" name="zone" value="<?= $z ?>" required>
                        <span class="card-content">
                            <span class="card-title"><?= $z ?></span>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="divider"></div>

        <div class="form-section">
            <h3 class="form-section-title"><i class="fa-solid fa-shapes"></i> 2. Type de Commande</h3>
            
            <div class="radio-grid-large">
                <div class="selection-card">
                    <input type="radio" name="plv_type" id="type_oneway" value="OneWay" onclick="toggleForm('oneway')" required>
                    <label for="type_oneway" class="card-content" style="width:100%; display:block;">
                        <i class="fa-solid fa-border-all icon-large"></i>
                        <span class="card-title">One Way (Vitrine)</span>
                    </label>
                </div>

                <div class="selection-card">
                    <input type="radio" name="plv_type" id="type_panneau" value="Panneau" onclick="toggleForm('panneau')" required>
                    <label for="type_panneau" class="card-content" style="width:100%; display:block;">
                        <i class="fa-solid fa-sign-hanging icon-large"></i>
                        <span class="card-title">Panneaux Présentoirs</span>
                    </label>
                </div>
            </div>
        </div>

        <div id="form-oneway" class="margin-top-lg" style="display:none;">
            <div class="state-box state-box--info" style="text-align: left;">
                <h4 style="margin-top:0;"><i class="fa-solid fa-image"></i> Détails One Way</h4>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Manuscrit (Fichier de taille)</label>
                        <div class="file-drop-area">
                            <span class="file-msg"><i class="fa-solid fa-file-pdf"></i> Upload Manuscrit</span>
                            <input type="file" name="ref_file_manuscript">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Photo Façade</label>
                        <div class="file-drop-area">
                            <span class="file-msg"><i class="fa-solid fa-camera"></i> Upload Photo</span>
                            <input type="file" name="ref_file_facade">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="form-panneau" class="margin-top-lg" style="display:none;">
            <div class="card" style="border: 1px solid var(--secondary); box-shadow: none;">
                <div class="card__header" style="background: #eff6ff; color: var(--secondary);">
                    <h3><i class="fa-solid fa-layer-group"></i> Configuration Panneaux</h3>
                </div>
                <div class="card__body">
                    
                    <div class="panel-tabs">
                        <?php for($i=1; $i<=6; $i++): ?>
                            <button type="button" class="panel-tab <?= $i==1?'active':'' ?>" onclick="openTab(<?= $i ?>)">
                                Panel #<?= $i ?>
                            </button>
                        <?php endfor; ?>
                    </div>

                    <?php 
                    $products = ['Millenium', 'Galaxy', 'Aqua', 'Optimo', 'Orient', 'Lumi-lap', 'Platinium', 'Encastreasy', 'Azur', 'EasyFiche', 'Itri'];
                    for($i=1; $i<=6; $i++): 
                    ?>
                    <div id="panel-<?= $i ?>" class="panel-content <?= $i==1?'active':'' ?>">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Largeur (cm)</label>
                                <input type="number" name="p<?= $i ?>_w" class="form-control" placeholder="0">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Hauteur (cm)</label>
                                <input type="number" name="p<?= $i ?>_h" class="form-control" placeholder="0">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Modèle(s) Panneau <?= $i ?></label>
                            <div class="checkbox-grid">
                                <?php foreach($products as $prod): ?>
                                <div class="checkbox-card">
                                    <input type="checkbox" name="p<?= $i ?>_content[]" value="<?= $prod ?>" id="p<?= $i ?>_<?= $prod ?>">
                                    <label for="p<?= $i ?>_<?= $prod ?>"><?= $prod ?></label>
                                </div>
                                <?php endforeach; ?>
                                
                                <div class="checkbox-card">
                                    <input type="checkbox" name="p<?= $i ?>_content[]" value="Other" id="p<?= $i ?>_check_other"
                                           onclick="toggleOtherInput(<?= $i ?>, this.checked)">
                                    <label for="p<?= $i ?>_check_other">Autre...</label>
                                </div>
                            </div>
                            <input type="text" name="p<?= $i ?>_other" id="other_text_<?= $i ?>" class="form-control margin-top-md" placeholder="Précisez autre..." style="display:none;">
                        </div>
                    </div>
                    <?php endfor; ?>

                    <div class="divider"></div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Logo / Nom <span class="text-danger">*</span></label>
                            <div class="radio-grid-large">
                                <div class="selection-card">
                                    <input type="radio" name="has_logo" id="logo_yes" value="Avec" onclick="document.getElementById('logo_upload_box').style.display='block'" checked>
                                    <label for="logo_yes" class="card-content">
                                        <span class="card-title">Avec Logo</span>
                                    </label>
                                </div>
                                <div class="selection-card">
                                    <input type="radio" name="has_logo" id="logo_no" value="Sans" onclick="document.getElementById('logo_upload_box').style.display='none'">
                                    <label for="logo_no" class="card-content">
                                        <span class="card-title">Sans Logo</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Texte à afficher <span class="text-danger">*</span></label>
                            <input type="text" name="display_name" class="form-control" placeholder="Ex: Résidence Al Yassmine">
                        </div>
                    </div>

                    <div class="form-group margin-top-md" id="logo_upload_box">
                        <label class="form-label">Fichier Logo (AI, PDF, PNG)</label>
                        <div class="upload-zone">
                            <div class="upload-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                            <span class="text-muted">Glisser ou cliquer pour upload (Max 10MB)</span>
                            <input type="file" name="ref_file_logo">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="divider"></div>

        <div class="form-section">
            <div class="form-group">
                <label class="form-label">Autres Détails / Remarques</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Instructions spéciales..."></textarea>
            </div>

            <input type="hidden" name="deadline" value="<?= date('Y-m-d\TH:i', strtotime('+7 days')) ?>">
            <input type="hidden" name="priority" value="medium">

            <div class="form-actions" style="text-align: right; margin-top: 24px;">
                <button type="submit" class="btn btn--primary btn--lg">
                    <i class="fa-solid fa-paper-plane"></i> Valider la Commande
                </button>
            </div>
        </div>

    </form>
</div>

<script>
// FIXED: Case sensitivity issue resolved
function toggleForm(type) {
    // Hide both initially
    document.getElementById('form-oneway').style.display = 'none';
    document.getElementById('form-panneau').style.display = 'none';
    
    // Check lowercase 'oneway'
    if(type === 'oneway') {
        document.getElementById('form-oneway').style.display = 'block';
    } else {
        document.getElementById('form-panneau').style.display = 'block';
    }
}

// FIXED: Helper function for the Other input toggle
function toggleOtherInput(index, isChecked) {
    const input = document.getElementById('other_text_' + index);
    if(isChecked) {
        input.style.display = 'block';
        input.focus();
    } else {
        input.style.display = 'none';
        input.value = ''; // Clear it if unchecked
    }
}

function openTab(panelNum) {
    // 1. Remove 'active' from all content & tabs
    document.querySelectorAll('.panel-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.panel-tab').forEach(el => el.classList.remove('active'));
    
    // 2. Add 'active' to clicked tab & corresponding content
    const content = document.getElementById('panel-'+panelNum);
    if(content) content.classList.add('active');
    
    // Find the specific tab button we clicked (using index since we have a loop)
    const tabs = document.querySelectorAll('.panel-tab');
    if(tabs[panelNum-1]) {
        tabs[panelNum-1].classList.add('active');
    }
}
</script>

<?php include 'views/layouts/footer.php'; ?>
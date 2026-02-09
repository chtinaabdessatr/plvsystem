<?php include 'views/layouts/header.php'; ?>

<div class="form-container">
    
    <div class="form-header-pro">
        <h2 style="margin:0;">Nouvelle Commande</h2>
        <p style="margin:5px 0 0; opacity:0.9;">Panneaux Présentoirs & One Way</p>
    </div>

    <form action="/plvsystem/order/create" method="POST" enctype="multipart/form-data" class="form-body">
        
        <div class="section-title">1. Informations</div>
        <div class="grid-2-col">
            <div class="form-group">
                <label>Commercial</label>
                <input type="text" name="commercial_name" required>
            </div>
            <div class="form-group">
                <label>Nom de Propriété (Client)</label>
                <input type="text" name="client_name" required>
            </div>
        </div>

        <div class="form-group">
            <label>Zone Géographique</label>
            <div class="radio-grid">
                <?php foreach(['R0', 'R1', 'R2', 'R3', 'R5', 'R6'] as $z): ?>
                <div class="radio-card">
                    <input type="radio" name="zone" id="z_<?= $z ?>" value="<?= $z ?>" required>
                    <label for="z_<?= $z ?>"><?= $z ?></label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="section-title">2. Type de Commande</div>
        <div class="radio-grid" style="grid-template-columns: 1fr 1fr;">
            <div class="radio-card">
                <input type="radio" name="plv_type" id="t_oneway" value="OneWay" onclick="toggleForm('oneway')" required>
                <label for="t_oneway">🔘 One Way (Vitrine)</label>
            </div>
            <div class="radio-card">
                <input type="radio" name="plv_type" id="t_panneau" value="Panneau" onclick="toggleForm('panneau')" required>
                <label for="t_panneau">🔲 Panneaux Présentoirs</label>
            </div>
        </div>

        <div id="form-oneway" style="display:none; background:#f8f9fa; padding:20px; border-radius:8px; border-left:4px solid #3498db;">
            <h3 style="margin-top:0; color:#2c3e50;">One Way Details</h3>
            <div class="grid-2-col">
                <div class="form-group">
                    <label>Manuscrit (Fichier de taille)</label>
                    <input type="file" name="ref_file_manuscript">
                </div>
                <div class="form-group">
                    <label>Photo Façade</label>
                    <input type="file" name="ref_file_facade">
                </div>
            </div>
        </div>

        <div id="form-panneau" style="display:none; background:#fff; padding:20px; border-radius:8px; border:1px solid #ddd;">
            <h3 style="margin-top:0; color:#d35400;">Panneaux Présentoirs</h3>
            
            <div class="panel-tabs">
                <?php for($i=1; $i<=6; $i++): ?>
                    <div class="panel-tab <?= $i==1?'active':'' ?>" onclick="openTab(<?= $i ?>)">P<?= $i ?></div>
                <?php endfor; ?>
            </div>

            <?php 
            $products = ['Millenium', 'Galaxy', 'Aqua', 'Optimo', 'Orient', 'Lumi-lap', 'Platinium', 'Encastreasy', 'Azur', 'EasyFiche', 'Itri'];
            for($i=1; $i<=6; $i++): 
            ?>
            <div id="panel-<?= $i ?>" class="panel-content <?= $i==1?'active':'' ?>">
                <h4 style="margin:0 0 15px 0; color:#3498db;">Configuration Panneau #<?= $i ?></h4>
                
                <div class="grid-2-col">
                    <div class="form-group">
                        <label>Largeur (Horizontal) cm</label>
                        <input type="number" name="p<?= $i ?>_w" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label>Hauteur (Verticale) cm</label>
                        <input type="number" name="p<?= $i ?>_h" placeholder="0">
                    </div>
                </div>

                <div class="form-group">
                    <label>Contenu Panneau <?= $i ?></label>
                    <div class="checkbox-grid product-grid">
                        <?php foreach($products as $prod): ?>
                        <div class="checkbox-card product-checkbox">
                            <input type="checkbox" name="p<?= $i ?>_content[]" value="<?= $prod ?>">
                            <label><?= $prod ?></label>
                        </div>
                        <?php endforeach; ?>
                        <div class="checkbox-card product-checkbox">
                            <input type="checkbox" name="p<?= $i ?>_content[]" value="Other" id="other_check_<?= $i ?>" onclick="document.getElementById('other_text_<?= $i ?>').style.display = this.checked ? 'block' : 'none'">
                            <label>Autre...</label>
                        </div>
                    </div>
                    <input type="text" name="p<?= $i ?>_other" id="other_text_<?= $i ?>" placeholder="Précisez autre..." style="display:none; margin-top:5px;">
                </div>
            </div>
            <?php endfor; ?>

            <hr>

            <div class="grid-2-col" style="margin-top:20px;">
                <div class="form-group">
                    <label>Avec ou sans LOGO/Nom <span style="color:red">*</span></label>
                    <div class="radio-grid" style="grid-template-columns: 1fr 1fr;">
                        <div class="radio-card">
                            <input type="radio" name="has_logo" value="Avec" onclick="document.getElementById('logo_upload_box').style.display='block'" checked>
                            <label>Avec</label>
                        </div>
                        <div class="radio-card">
                            <input type="radio" name="has_logo" value="Sans" onclick="document.getElementById('logo_upload_box').style.display='none'">
                            <label>Sans</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Nom à afficher sur le panneau <span style="color:red">*</span></label>
                    <input type="text" name="display_name" placeholder="Ex: Résidence Al Yassmine">
                </div>
            </div>

            <div class="form-group" id="logo_upload_box">
                <label>Importer Logo</label>
                <div class="upload-zone">
                    <div class="upload-icon">📂</div>
                    <span>Glisser ou cliquer pour upload (Max 10MB)</span>
                    <input type="file" name="ref_file_logo">
                </div>
            </div>

        </div>

        <div class="form-group" style="margin-top:20px;">
            <label>Autres Détails</label>
            <textarea name="description" rows="3"></textarea>
        </div>

        <input type="hidden" name="deadline" value="<?= date('Y-m-d\TH:i', strtotime('+7 days')) ?>">
        <input type="hidden" name="priority" value="medium">

        <div style="text-align: right; margin-top:20px;">
            <button type="submit" class="btn" style="padding: 12px 40px;">🚀 Valider la Commande</button>
        </div>
    </form>
</div>

<script>
function toggleForm(type) {
    document.getElementById('form-oneway').style.display = 'none';
    document.getElementById('form-panneau').style.display = 'none';
    if(type === 'oneway') document.getElementById('form-oneway').style.display = 'block';
    else document.getElementById('form-panneau').style.display = 'block';
}

function openTab(panelNum) {
    // Hide all contents
    document.querySelectorAll('.panel-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.panel-tab').forEach(el => el.classList.remove('active'));
    
    // Show selected
    document.getElementById('panel-'+panelNum).classList.add('active');
    // Highlight tab
    const tabs = document.querySelectorAll('.panel-tab');
    tabs[panelNum-1].classList.add('active');
}
</script>

<?php include 'views/layouts/footer.php'; ?>
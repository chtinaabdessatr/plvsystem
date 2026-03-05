<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérifier l'OTP - LAP PLV</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="/plvsystem/public/css/app.css">
    
    <style>
        /* Reusing the Login Card styles for consistency */
        .login-card {
            background: white;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            border-radius: var(--radius);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .auth-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 16px;
            background: #eef2ff;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .login-subtitle {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .input-group {
            position: relative;
            margin-bottom: 16px;
        }

        .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            z-index: 2;
        }

        .login-input {
            padding-left: 38px !important; 
            letter-spacing: 2px;
            font-weight: 600;
            font-size: 1.1rem;
            text-align: center;
        }

        /* Special style for the Dev Code hint */
        .dev-hint {
            background: #fffbeb;
            border: 1px dashed #f59e0b;
            color: #b45309;
            padding: 8px;
            border-radius: 6px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            display: inline-block;
            width: 100%;
        }

        /* Error Alert Styles */
        .alert-error {
            background-color: #fef2f2;
            border: 1px solid #f87171;
            color: #b91c1c;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 13px;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>

    <div class="auth-layout">
        
        <div class="login-card">
            
            <div class="auth-icon">
                <i class="fa-solid fa-shield-halved"></i>
            </div>

            <h2 style="margin: 0 0 8px 0; color: var(--text-dark);">Contrôle de sécurité</h2>
            <p class="login-subtitle">Veuillez saisir le code de vérification envoyé sur votre appareil.</p>

            <?php if(isset($_GET['error'])): ?>
                <div class="alert-error">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size: 16px;"></i>
                    <div>
                        <?php 
                            if($_GET['error'] == 'otp') {
                                echo "<strong>Erreur:</strong> Code OTP invalide. Veuillez réessayer.";
                            } else {
                                echo "<strong>Erreur:</strong> Une erreur s'est produite.";
                            }
                        ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="dev-hint">
                <i class="fa-solid fa-bug"></i> Mode démo : utilisez le code <strong>123456</strong>
            </div>

            <form method="POST" action="/plvsystem/auth/verifyOtp">
                
                <div class="input-group">
                    <i class="fa-solid fa-key input-icon"></i>
                    <input type="text" name="otp_code" class="form-control login-input" placeholder="000000" maxlength="6" required autofocus autocomplete="off">
                </div>

                <button type="submit" class="btn btn--primary btn--block btn--lg">
                    Vérifier l'identité <i class="fa-solid fa-arrow-right" style="margin-left:8px;"></i>
                </button>

            </form>

            <div style="margin-top: 20px;">
                <a href="/plvsystem/auth/login" class="btn btn--sm btn--neutral" style="text-decoration: none;">
                    <i class="fa-solid fa-arrow-left"></i> Retour à la connexion
                </a>
            </div>

        </div>
    </div>

</body>
</html>
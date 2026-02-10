<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LAP PLV System</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="/plvsystem/public/css/app.css">
    
    <style>
        /* Special overrides just for the login card */
        .login-card {
            background: white;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            border-radius: var(--radius);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .brand-logo {
            font-size: 2rem;
            color: var(--primary);
            font-weight: 800;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .login-subtitle {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 30px;
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

        /* Adjust input padding to make room for icon */
        .login-input {
            padding-left: 38px !important; 
        }
    </style>
</head>
<body>

    <div class="auth-layout">
        
        <div class="login-card">
            
            <div class="brand-logo">
                <i class="fa-solid fa-layer-group"></i> LAP PLV
            </div>
            <p class="login-subtitle">Production Management System</p>

            <form method="POST" action="/plvsystem/auth/login">
                
                <div class="input-group">
                    <i class="fa-solid fa-envelope input-icon"></i>
                    <input type="email" name="email" class="form-control login-input" placeholder="Email Address" required autofocus>
                </div>

                <div class="input-group">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <input type="password" name="password" class="form-control login-input" placeholder="Password" required>
                </div>

                <button type="submit" class="btn btn--primary btn--block btn--lg">
                    Login <i class="fa-solid fa-arrow-right" style="margin-left:8px;"></i>
                </button>

            </form>

            <div style="margin-top: 24px; font-size: 0.8rem; color: #9ca3af;">
                &copy; <?= date('Y') ?> Lap Production System v1.0
            </div>

        </div>
    </div>

</body>
</html>
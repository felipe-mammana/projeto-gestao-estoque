<?php
/**
 * views/auth/login.php
 * Template de login (refatorado da página original)
 */
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Estoque - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-blue: #2563EB;
            --deep-blue: #1E40AF;
            --glass-bg: rgba(255, 255, 255, 0.15);
            --glass-border: rgba(255, 255, 255, 0.2);
            --text-main: #ffffff;
            --text-secondary: #e2e8f0;
            --input-bg: rgba(255, 255, 255, 0.9);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: radial-gradient(circle at top left, #3b82f6, transparent 40%),
                        radial-gradient(circle at bottom right, #1e3a8a, transparent 40%),
                        #0f172a;
            background-size: cover;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow: hidden;
        }

        .ambient-shape {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            z-index: -1;
            animation: float 10s infinite ease-in-out alternate;
        }

        .shape-1 {
            width: 300px;
            height: 300px;
            background: #60a5fa;
            top: -50px;
            left: -50px;
        }

        .shape-2 {
            width: 400px;
            height: 400px;
            background: #1d4ed8;
            bottom: -100px;
            right: -100px;
            animation-delay: -5s;
        }

        @keyframes float {
            0% { transform: translate(0, 0); }
            100% { transform: translate(30px, 50px); }
        }

        .login-container {
            position: relative;
            width: 100%;
            max-width: 400px;
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            padding: 40px 30px;
            margin-top: 40px;
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo-floating {
            position: absolute;
            top: -50px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 100px;
            background: #0f172a;
            border: 4px solid rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            z-index: 10;
        }

        .logo-floating svg {
            width: 50px;
            height: 50px;
        }

        .login-header {
            text-align: center;
            margin-top: 45px;
            margin-bottom: 30px;
            color: var(--text-main);
        }

        .login-header h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
            letter-spacing: -0.5px;
        }

        .login-header p {
            font-size: 14px;
            color: var(--text-secondary);
            font-weight: 400;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input {
            width: 100%;
            padding: 16px 45px 16px 20px;
            background: var(--input-bg);
            border: 2px solid transparent;
            border-radius: 12px;
            font-size: 15px;
            color: #1e293b;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .input-wrapper input::placeholder {
            color: #94a3b8;
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.2);
            transform: translateY(-1px);
        }

        .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            cursor: pointer;
            transition: color 0.2s;
        }

        .input-icon:hover {
            color: var(--primary-blue);
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            font-size: 13px;
            color: var(--text-secondary);
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            cursor: pointer;
            user-select: none;
        }

        .checkbox-container input {
            appearance: none;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.5);
            border-radius: 4px;
            margin-right: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .checkbox-container input:checked {
            background-color: var(--primary-blue);
            border-color: var(--primary-blue);
        }

        .forgot-link {
            color: var(--text-main);
            text-decoration: none;
            font-weight: 500;
            border-bottom: 1px solid transparent;
            transition: all 0.2s;
        }

        .forgot-link:hover {
            border-bottom-color: white;
        }

        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.4);
            letter-spacing: 0.5px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 20px -3px rgba(37, 99, 235, 0.5);
            filter: brightness(1.1);
        }

        .btn-login:active {
            transform: scale(0.98);
        }

        .alert {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
                margin-top: 60px;
            }
            .logo-floating {
                width: 80px;
                height: 80px;
                top: -40px;
            }
            .logo-floating svg {
                width: 40px;
                height: 40px;
            }
        }
    </style>
</head>
<body>
    <div class="ambient-shape shape-1"></div>
    <div class="ambient-shape shape-2"></div>

    <div class="login-container">
        <div class="logo-floating">
            <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
        </div>

        <div class="login-header">
            <h1>Bem-vindo</h1>
            <p>Insira seus dados para acessar o estoque</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert" role="alert">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form action="/estoquemh/public/index.php?page=login" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

            <div class="form-group">
                <div class="input-wrapper">
                    <input 
                        type="email" 
                        name="email" 
                        placeholder="Seu email corporativo"
                        value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                        autocomplete="username"
                        required
                    >
                    <span class="input-icon" title="Email">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                        </svg>
                    </span>
                </div>
            </div>

            <div class="form-group">
                <div class="input-wrapper">
                    <input 
                        type="password" 
                        id="senhaInput"
                        name="senha" 
                        placeholder="Sua senha"
                        autocomplete="current-password"
                        required
                    >
                    <span class="input-icon" onclick="togglePassword()" style="cursor: pointer;">
                        <svg id="eyeIcon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </span>
                </div>
            </div>

            <div class="form-options">
                <label class="checkbox-container">
                    <input type="checkbox" name="lembrar" value="1">
                    Lembrar de mim
                </label>
                <a href="#" class="forgot-link">Esqueceu a senha?</a>
            </div>

            <button type="submit" class="btn-login" id="btnSubmit">
                ENTRAR
            </button>
        </form>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('senhaInput');
            const icon = document.getElementById('eyeIcon');
            
            if (input.type === "password") {
                input.type = "text";
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />';
            } else {
                input.type = "password";
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>';
            }
        }

        document.querySelector('form').addEventListener('submit', function(e) {
            const btn = document.getElementById('btnSubmit');
            
            if (btn.disabled) {
                e.preventDefault();
                return;
            }
            
            btn.disabled = true;
            btn.style.opacity = '0.8';
            btn.style.cursor = 'wait';
            btn.innerHTML = 'AUTENTICANDO...';
        });
    </script>
</body>
</html>

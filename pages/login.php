<?php
/**
 * LOGIN PAGE
 * Autenticação de usuários do sistema de estoque
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/estoquemh/cone.php';

// ====================================
// Variáveis Iniciais
// ====================================
$erro = '';
$email = '';
$tentativas = 0;

// Limpar sessão anterior se logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: /estoquemh/pages/login.php");
    exit();
}

// ====================================
// Processamento do Formulário
// ====================================
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // 🔐 Validar CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $erro = "Erro de segurança. Tente novamente.";
    } else {

        // Sanitizar entrada
        $email = trim($_POST["email"] ?? '');
        $senha = $_POST["senha"] ?? '';

        // Validações básicas
        if (empty($email) || empty($senha)) {
            $erro = "Email e senha são obrigatórios.";
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = "Email inválido.";
        } else {

            // ====================================
            // Consultar Usuário no Banco
            // ====================================
            $sql = "
                SELECT 
                    u.id,
                    u.email,
                    u.senha,
                    u.nome,
                    u.cargo,
                    u.tipo,
                    u.foto,
                    u.setor_id,
                    s.nome AS setor_nome
                FROM usuarios u
                INNER JOIN setores s ON s.id = u.setor_id
                WHERE u.email = ?
                LIMIT 1
            ";

            $stmt = mysqli_prepare($cone, $sql);

            if ($stmt === false) {
                $erro = "Erro no sistema. Contate o administrador.";
            } else {

                mysqli_stmt_bind_param($stmt, "s", $email);
                mysqli_stmt_execute($stmt);
                $resultado = mysqli_stmt_get_result($stmt);

                if (mysqli_num_rows($resultado) === 1) {

                    $usuario = mysqli_fetch_assoc($resultado);

                    // 🔐 VERIFICA SENHA COM HASH BCRYPT
                    if (!password_verify($senha, $usuario['senha'])) {
                        $erro = "Email ou senha incorretos!";
                    } else {

                        // ✅ LOGIN BEM-SUCEDIDO
                        $_SESSION['logado'] = true;
                        $_SESSION['id_user'] = $usuario['id'];

                        $_SESSION['usuario'] = [
                            'id'         => $usuario['id'],
                            'nome'       => $usuario['nome'],
                            'email'      => $usuario['email'],
                            'cargo'      => $usuario['cargo'],
                            'tipo'       => $usuario['tipo'],
                            'setor_id'   => $usuario['setor_id'],
                            'setor_nome' => $usuario['setor_nome'],
                            'foto'       => $usuario['foto']
                        ];

                        header("Location: /estoquemh/pages/sistema.php");
                        exit();
                    }

                } else {
                    $erro = "Email ou senha incorretos!";
                }

                mysqli_stmt_close($stmt);
            }
        }
    }
}

// Gerar token CSRF se não existir
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
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
            /* Gradiente de fundo rico e profundo para destacar o vidro */
            background: radial-gradient(circle at top left, #3b82f6, transparent 40%),
                        radial-gradient(circle at bottom right, #1e3a8a, transparent 40%),
                        #0f172a; /* Fundo base escuro azulado */
            background-size: cover;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow: hidden; /* Evita scroll desnecessário nas animações */
        }

        /* Efeito de bolhas flutuantes no fundo para dar profundidade */
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

        /* Container Principal com Glassmorphism */
        .login-container {
            position: relative;
            width: 100%;
            max-width: 400px;
            background: var(--glass-bg);
            /* O segredo do vidro fosco: */
            backdrop-filter: blur(16px); 
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            padding: 40px 30px;
            margin-top: 40px; /* Espaço para a logo flutuante */
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Logo Flutuante (Circle Avatar) */
        .logo-floating {
            position: absolute;
            top: -50px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 100px;
            background: #0f172a; /* Cor escura para contraste */
            border: 4px solid rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            z-index: 10;
        }

        .logo-floating img {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }

        /* Texto de boas-vindas */
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

        /* Formulário */
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .input-wrapper {
            position: relative;
        }

        /* Inputs estilizados */
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

        /* Ícones dentro do input */
        .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 18px;
            cursor: pointer;
            transition: color 0.2s;
        }

        .input-icon:hover {
            color: var(--primary-blue);
        }

        /* Opções (Checkbox e Link) */
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
            position: relative;
            cursor: pointer;
            transition: all 0.2s;
        }

        .checkbox-container input:checked {
            background-color: var(--primary-blue);
            border-color: var(--primary-blue);
        }

        .checkbox-container input:checked::after {
            content: '✓';
            position: absolute;
            color: white;
            font-size: 12px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
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

        /* Botão de Login */
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

        /* Alertas de Erro (Estilizados) */
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

        /* Responsividade */
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
            .logo-floating img {
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
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
        </div>

        <div class="login-header">
            <h1>Bem-vindo</h1>
            <p>Insira seus dados para acessar o estoque</p>
        </div>

        <?php if (!empty($erro)): ?>
            <div class="alert" role="alert">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span><?php echo htmlspecialchars($erro); ?></span>
            </div>
        <?php endif; ?>

        <form action="/estoquemh/pages/login.php" method="POST">
            
            <input type="hidden" name="csrf_token" value="<?php echo isset($_SESSION['csrf_token']) ? htmlspecialchars($_SESSION['csrf_token']) : ''; ?>">

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
                        <svg width="20" height="20" fill="none" stroke="#64748b" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path></svg>
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
                        <svg id="eyeIcon" width="20" height="20" fill="none" stroke="#64748b" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
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
        // Função para mostrar/ocultar senha (UX Essencial)
        function togglePassword() {
            const input = document.getElementById('senhaInput');
            const icon = document.getElementById('eyeIcon');
            
            if (input.type === "password") {
                input.type = "text";
                // Muda ícone para 'cortado'
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />';
            } else {
                input.type = "password";
                // Retorna ícone normal
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>';
            }
        }

        // Feedback de Loading no Botão
        document.querySelector('form').addEventListener('submit', function(e) {
            const btn = document.getElementById('btnSubmit');
            const originalText = btn.innerText;
            
            // Só previne se já estiver enviando
            if (btn.disabled) {
                e.preventDefault();
                return;
            }
            
            btn.disabled = true;
            btn.style.opacity = '0.8';
            btn.style.cursor = 'wait';
            btn.innerHTML = 'AUTENTICANDO...';
            
            // Se for demo (sem backend), restaura após 2 seg
            // Remova este setTimeout em produção
            /* setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
                btn.style.opacity = '1';
                btn.style.cursor = 'pointer';
                alert("Login enviado! (Demo)");
            }, 2000);
            */
        });
    </script>
</body>
</html>
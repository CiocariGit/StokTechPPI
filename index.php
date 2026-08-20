<?php
session_start();
$erro    = $_SESSION['erro'] ?? '';
$sucesso = $_SESSION['sucesso'] ?? '';
unset($_SESSION['erro'], $_SESSION['sucesso']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="img/logo.png" type="image/png">
    <link rel="stylesheet" href="css/protecao.css">
    <title>StokTech — Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6b1fad;
            --primary-light: #b662ff;
            --bg: #0d0d0f;
            --surface: #131316;
            --surface-2: #1a1a1f;
            --border: #252530;
            --border-focus: #6b1fad;
            --text: #f0f0f4;
            --text-muted: #7a7a90;
            --text-subtle: #4a4a60;
            --success: #22c55e;
            --danger: #ef4444;
            --radius: 4px;
        }
        body.light-mode {
            --bg: #f4f4f8;
            --surface: #ffffff;
            --surface-2: #f0f0f5;
            --border: #dcdce8;
            --text: #0d0d1a;
            --text-muted: #55556a;
            --text-subtle: #aaaabc;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'IBM Plex Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: stretch;
            transition: background 0.3s, color 0.3s;
        }

        /* Layout split */
        .auth-split {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }
        .auth-brand {
            flex: 1;
            background: linear-gradient(160deg, #0d0012 0%, #1a0030 50%, #0d0020 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 4rem;
            position: relative;
            overflow: hidden;
        }
        .auth-brand::before {
            content: '';
            position: absolute;
            top: -20%;
            left: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(107,31,173,0.25) 0%, transparent 70%);
            pointer-events: none;
        }
        .auth-brand::after {
            content: '';
            position: absolute;
            bottom: -10%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(182,98,255,0.12) 0%, transparent 70%);
            pointer-events: none;
        }
        .brand-grid {
            position: absolute;
            inset: 0;
            background-image: linear-gradient(rgba(107,31,173,0.06) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(107,31,173,0.06) 1px, transparent 1px);
            background-size: 40px 40px;
        }
        .brand-content { position: relative; z-index: 1; }
        .brand-logo {
            width: 160px;
            margin-top: 1.6rem;
            margin-bottom: 2rem;
            opacity: 0.95;
        }
        .brand-tagline {
            font-size: 2.6rem;
            font-weight: 700;
            line-height: 1.2;
            color: #ffffff;
            letter-spacing: -0.03em;
            margin-bottom: 1.5rem;
        }
        .brand-tagline span {
            background: linear-gradient(135deg, #b662ff, #6b1fad);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .brand-desc {
            color: rgba(255,255,255,0.45);
            font-size: 0.95rem;
            line-height: 1.8;
            max-width: 360px;
            font-weight: 300;
        }
        .brand-badges {
            display: flex;
            gap: 0.75rem;
            margin-top: 3rem;
            flex-wrap: wrap;
        }
        .brand-badge {
            border: 1px solid rgba(182,98,255,0.25);
            color: rgba(255,255,255,0.5);
            padding: 0.4rem 0.9rem;
            font-size: 0.78rem;
            font-family: 'IBM Plex Mono', monospace;
            letter-spacing: 0.05em;
            border-radius: 2px;
        }

        /* Form panel */
        .auth-panel {
            width: 480px;
            flex-shrink: 0;
            background: var(--surface);
            border-left: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem 3.5rem;
            position: relative;
        }
        .panel-header { margin-bottom: 2.5rem; }
        .panel-header h1 {
            font-size: 1.6rem;
            font-weight: 600;
            letter-spacing: -0.02em;
            color: var(--text);
            margin-bottom: 0.4rem;
        }
        .panel-header p {
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 300;
        }
        .form-group { margin-bottom: 1.4rem; }
        .form-group label {
            display: block;
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }
        .form-group input {
            width: 100%;
            padding: 0.85rem 1rem;
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            color: var(--text);
            font-size: 0.95rem;
            font-family: 'IBM Plex Sans', sans-serif;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-group input:focus {
            border-color: var(--border-focus);
            box-shadow: 0 0 0 3px rgba(107,31,173,0.15);
        }
        .form-group input::placeholder { color: var(--text-subtle); }
        .forgot-link {
            display: block;
            text-align: right;
            font-size: 0.82rem;
            color: var(--text-muted);
            text-decoration: none;
            margin-top: -0.6rem;
            margin-bottom: 1.8rem;
            transition: color 0.2s;
        }
        .forgot-link:hover { color: var(--primary-light); }
        .btn-primary {
            width: 100%;
            padding: 0.95rem;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: var(--radius);
            font-size: 0.95rem;
            font-weight: 600;
            font-family: 'IBM Plex Sans', sans-serif;
            cursor: pointer;
            letter-spacing: 0.02em;
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
        }
        .btn-primary:hover {
            background: var(--primary-light);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(107,31,173,0.35);
        }
        .btn-primary:active { transform: translateY(0); }
        .divider {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1.8rem 0;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }
        .divider span { color: var(--text-subtle); font-size: 0.8rem; }
        .signup-link {
            text-align: center;
            font-size: 0.88rem;
            color: var(--text-muted);
        }
        .signup-link a {
            color: var(--primary-light);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }
        .signup-link a:hover { color: #fff; }

        /* Alerts */
        .alert {
            position: fixed;
            bottom: 1.5rem;
            left: 1.5rem;
            padding: 0.9rem 1.2rem;
            border-radius: var(--radius);
            font-size: 0.88rem;
            font-weight: 500;
            z-index: 9999;
            max-width: 320px;
            animation: slideUp 0.3s ease;
            border-left: 3px solid;
        }
        .alert-error { background: rgba(239,68,68,0.12); color: #f87171; border-color: #ef4444; }
        .alert-success { background: rgba(34,197,94,0.12); color: #4ade80; border-color: #22c55e; }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Theme toggle */
        .theme-toggle {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            width: 40px;
            height: 40px;
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            transition: all 0.2s;
            z-index: 100;
        }
        .theme-toggle:hover {
            border-color: var(--primary);
            background: var(--primary);
        }

        /* Responsive */
        @media (max-width: 900px) {
            .auth-brand { display: none; }
            .auth-panel {
                width: 100%;
                border-left: none;
                padding: 2.5rem 2rem;
                justify-content: center;
                min-height: 100vh;
            }
        }
        @media (max-width: 480px) {
            .auth-panel { padding: 2rem 1.5rem; }
            .panel-header h1 { font-size: 1.4rem; }
        }
    </style>
</head>
<body>
    <button class="theme-toggle" id="themeToggle" title="Alternar tema">☽</button>

    <div class="auth-split">
        <div class="auth-brand">
            <div class="brand-grid"></div>
            <div class="brand-content">
                <img class="brand-logo" src="img/logo_stoktech.png" alt="StokTech">
                <h2 class="brand-tagline">Gestão de<br><span>laboratório</span><br>inteligente.</h2>
                <p class="brand-desc">Controle de estoque, empréstimos e componentes eletrônicos em uma plataforma centralizada.</p>
                <div class="brand-badges">
                    <span class="brand-badge">ESTOQUE</span>
                    <span class="brand-badge">EMPRÉSTIMOS</span>
                    <span class="brand-badge">COMPONENTES</span>
                </div>
            </div>
        </div>

        <div class="auth-panel">
            <div class="panel-header">
                <h1>Acesse sua conta</h1>
                <p>Informe suas credenciais para continuar</p>
            </div>

            <?php if ($erro): ?>
                <div class="alert alert-error"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>
            <?php if ($sucesso): ?>
                <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
            <?php endif; ?>

            <form id="loginForm" action="verifica_usuario.php" method="POST">
                <div class="form-group">
                    <label for="cpf">CPF</label>
                    <input type="text" id="cpf" name="cpf" placeholder="000.000.000-00" required autocomplete="username">
                </div>

                <div class="form-group">
                    <label for="senha">Senha</label>
                    <div class="stoktech-senha-wrap">
                        <input type="password" id="senha" name="senha" placeholder="••••••••" required autocomplete="current-password">
                    <button type="button" class="stoktech-toggle-senha" data-alvo="senha" tabindex="-1" aria-label="Mostrar senha"><svg class="icone-olho-aberto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg><svg class="icone-olho-fechado" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a20.3 20.3 0 0 1 5.06-5.94M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a20.3 20.3 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg></button>
                    </div>
                </div>

                <a href="resetar_senha.php" class="forgot-link">Esqueceu a senha?</a>

                <button type="submit" class="btn-primary">Entrar</button>
            </form>

            <div class="divider"><span>ou</span></div>

            <div class="signup-link">
                Não tem uma conta? <a href="cadastro.php">Criar conta</a>
            </div>

            <a href="menu_principal.php" style="display:block; text-align:center; margin-top:1.1rem; font-size:0.82rem; color:var(--text-muted); text-decoration:none; border:1px solid var(--border); border-radius:4px; padding:0.7rem;">
                Ver catálogo sem fazer login
            </a>
        </div>
    </div>

    <script>
        // CPF mask
        document.getElementById('cpf').addEventListener('input', function(e) {
            let v = e.target.value.replace(/\D/g, '').slice(0, 11);
            if (v.length > 9)      v = v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
            else if (v.length > 6) v = v.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
            else if (v.length > 3) v = v.replace(/(\d{3})(\d{1,3})/, '$1.$2');
            e.target.value = v;
        });

        // Theme
        const toggle = document.getElementById('themeToggle');
        if (localStorage.getItem('theme') === 'light') {
            document.body.classList.add('light-mode');
            toggle.textContent = '☀';
        }
        toggle.addEventListener('click', () => {
            document.body.classList.toggle('light-mode');
            const isLight = document.body.classList.contains('light-mode');
            localStorage.setItem('theme', isLight ? 'light' : 'dark');
            toggle.textContent = isLight ? '☀' : '☽';
        });
    </script>
    <script src="js/protecao.js"></script>
</body>
</html>
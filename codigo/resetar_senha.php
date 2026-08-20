<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StokTech — Redefinir Senha</title>
    <link rel="icon" href="img/logo.png" type="image/png">
    <link rel="stylesheet" href="css/protecao.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6b1fad;
            --primary-light: #b662ff;
            --primary-subtle: rgba(107,31,173,0.12);
            --bg: #0d0d0f;
            --surface: #131316;
            --surface-2: #1a1a1f;
            --border: #252530;
            --text: #f0f0f4;
            --text-muted: #7a7a90;
            --text-subtle: #4a4a60;
            --success: #22c55e;
            --danger: #ef4444;
            --warning: #f59e0b;
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
        html, body { height: 100%; overflow: hidden; }
        body {
            font-family: 'IBM Plex Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: stretch;
            transition: background 0.3s, color 0.3s;
        }

        /* Split layout */
        .auth-split { display: flex; width: 100%; min-height: 100vh; max-height: 100vh; overflow: hidden; }
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
            top: -20%; left: -10%;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(107,31,173,0.25) 0%, transparent 70%);
        }
        .auth-brand::after {
            content: '';
            position: absolute;
            bottom: -10%; right: -10%;
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(182,98,255,0.12) 0%, transparent 70%);
        }
        .brand-grid {
            position: absolute; inset: 0;
            background-image: linear-gradient(rgba(107,31,173,0.06) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(107,31,173,0.06) 1px, transparent 1px);
            background-size: 40px 40px;
        }
        .brand-content { position: relative; z-index: 1; }
        .brand-logo { width: 160px; margin-bottom: 3rem; opacity: 0.95; }
        .brand-tagline {
            font-size: 2.6rem; font-weight: 700; line-height: 1.2;
            color: #ffffff; letter-spacing: -0.03em; margin-bottom: 1.5rem;
        }
        .brand-tagline span {
            background: linear-gradient(135deg, #b662ff, #6b1fad);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .brand-desc { color: rgba(255,255,255,0.45); font-size: 0.95rem; line-height: 1.8; max-width: 360px; font-weight: 300; }
        .brand-security { margin-top: 3rem; display: flex; flex-direction: column; gap: 1rem; }
        .brand-sec-item {
            display: flex; align-items: center; gap: 1rem;
            color: rgba(255,255,255,0.4); font-size: 0.85rem;
        }
        .brand-sec-icon {
            width: 28px; height: 28px; border: 1px solid rgba(182,98,255,0.3);
            border-radius: 2px; display: flex; align-items: center; justify-content: center;
            font-size: 0.8rem; color: rgba(182,98,255,0.6); flex-shrink: 0;
            font-family: 'IBM Plex Mono', monospace;
        }

        /* Panel */
        .auth-panel {
            width: 480px; flex-shrink: 0;
            background: var(--surface);
            border-left: 1px solid var(--border);
            display: flex; flex-direction: column; justify-content: center;
            padding: 3rem 3.5rem;
        }
        .panel-header { margin-bottom: 2rem; }
        .panel-header h1 { font-size: 1.5rem; font-weight: 600; letter-spacing: -0.02em; margin-bottom: 0.4rem; }
        .panel-header p { color: var(--text-muted); font-size: 0.88rem; font-weight: 300; line-height: 1.6; }

        /* Step indicator */
        .step-track {
            display: flex; align-items: center; gap: 0;
            margin-bottom: 2.5rem;
        }
        .step-dot {
            width: 28px; height: 28px; border-radius: 50%;
            border: 2px solid var(--border);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.72rem; font-family: 'IBM Plex Mono', monospace;
            color: var(--text-subtle); background: var(--surface-2);
            transition: all 0.3s; flex-shrink: 0;
        }
        .step-dot.active { border-color: var(--primary); color: var(--primary-light); background: var(--primary-subtle); }
        .step-dot.done { border-color: var(--success); color: var(--success); background: rgba(34,197,94,0.1); }
        .step-line { flex: 1; height: 1px; background: var(--border); transition: background 0.3s; }
        .step-line.done { background: var(--success); }
        .step-labels { display: flex; justify-content: space-between; margin-bottom: 2rem; margin-top: -1.5rem; }
        .step-label { font-size: 0.7rem; color: var(--text-subtle); font-family: 'IBM Plex Mono', monospace; text-transform: uppercase; letter-spacing: 0.04em; }
        .step-label.active { color: var(--primary-light); }

        /* Forms */
        .form-step { display: none; animation: stepIn 0.25s ease; }
        .form-step.active { display: block; }
        @keyframes stepIn { from { opacity: 0; transform: translateX(12px); } to { opacity: 1; transform: translateX(0); } }

        .form-group { margin-bottom: 1.4rem; }
        .form-group label {
            display: block; font-size: 0.78rem; font-weight: 500;
            color: var(--text-muted); text-transform: uppercase;
            letter-spacing: 0.06em; margin-bottom: 0.5rem;
        }
        .form-group input {
            width: 100%; padding: 0.85rem 1rem;
            background: var(--surface-2); border: 1px solid var(--border);
            border-radius: var(--radius); color: var(--text);
            font-size: 0.95rem; font-family: 'IBM Plex Sans', sans-serif;
            outline: none; transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-group input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(107,31,173,0.12); }
        .form-group input::placeholder { color: var(--text-subtle); }

        /* Code inputs */
        .code-inputs { display: flex; gap: 0.5rem; justify-content: center; }
        .code-input {
            width: 48px !important; height: 56px; text-align: center;
            font-size: 1.4rem; font-weight: 700;
            font-family: 'IBM Plex Mono', monospace;
            padding: 0 !important; letter-spacing: 0;
        }

        /* Password strength */
        .strength-bar-wrap {
            height: 3px; background: var(--border); border-radius: 2px;
            overflow: hidden; margin-top: 0.6rem;
        }
        .strength-bar { height: 100%; width: 0; transition: all 0.3s; border-radius: 2px; }
        .strength-text { font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem; font-family: 'IBM Plex Mono', monospace; }
        .pw-requirements {
            margin-top: 1rem; padding: 1rem;
            background: var(--surface-2); border: 1px solid var(--border);
            border-left: 3px solid var(--primary); border-radius: var(--radius);
        }
        .requirement {
            font-size: 0.8rem; color: var(--text-muted);
            margin-bottom: 0.4rem; display: flex; align-items: center; gap: 0.5rem;
        }
        .requirement:last-child { margin-bottom: 0; }
        .requirement::before { content: '○'; font-family: 'IBM Plex Mono', monospace; font-size: 0.7rem; opacity: 0.5; }
        .requirement.met { color: var(--success); }
        .requirement.met::before { content: '✓'; opacity: 1; }

        /* Buttons */
        .btn-primary {
            width: 100%; padding: 0.95rem;
            background: var(--primary); color: #fff; border: none;
            border-radius: var(--radius); font-size: 0.95rem; font-weight: 600;
            font-family: 'IBM Plex Sans', sans-serif; cursor: pointer;
            letter-spacing: 0.02em; margin-top: 0.5rem;
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
            position: relative;
        }
        .btn-primary:hover:not(:disabled) { background: var(--primary-light); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(107,31,173,0.3); }
        .btn-primary:active { transform: translateY(0); }
        .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .btn-primary.btn-loading::after {
            content: ''; position: absolute; right: 1rem; top: 50%; transform: translateY(-50%);
            width: 15px; height: 15px; border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff; border-radius: 50%; animation: spin 0.6s linear infinite;
        }
        @keyframes spin { to { transform: translateY(-50%) rotate(360deg); } }

        .btn-ghost {
            width: 100%; padding: 0.8rem;
            background: transparent; color: var(--text-muted);
            border: 1px solid var(--border); border-radius: var(--radius);
            font-size: 0.88rem; font-family: 'IBM Plex Sans', sans-serif;
            cursor: pointer; transition: all 0.2s; margin-top: 0.5rem;
        }
        .btn-ghost:hover { border-color: var(--primary); color: var(--primary-light); background: var(--primary-subtle); }
        .btn-ghost:disabled { opacity: 0.45; cursor: not-allowed; }

        .back-link { margin-top: 1.25rem; text-align: center; }
        .back-link a {
            font-size: 0.82rem; color: var(--text-muted); text-decoration: none;
            display: inline-flex; align-items: center; gap: 0.35rem;
            transition: color 0.2s;
        }
        .back-link a:hover { color: var(--primary-light); }

        .resend-area { text-align: center; margin-top: 1.25rem; }
        .resend-area p { font-size: 0.82rem; color: var(--text-muted); margin-bottom: 0.4rem; }
        .timer { font-family: 'IBM Plex Mono', monospace; color: var(--primary-light); font-weight: 600; }

        /* Email highlight */
        .email-highlight { color: var(--primary-light); font-weight: 600; }

        /* Success state */
        .success-check {
            width: 64px; height: 64px; border-radius: 50%;
            background: rgba(34,197,94,0.1); border: 2px solid var(--success);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.6rem; margin: 0 auto 1.5rem;
            animation: scaleIn 0.4s cubic-bezier(0.34,1.56,0.64,1);
        }
        @keyframes scaleIn { from { transform: scale(0); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .success-title { text-align: center; font-size: 1.3rem; font-weight: 600; margin-bottom: 0.6rem; }
        .success-desc { text-align: center; color: var(--text-muted); font-size: 0.88rem; line-height: 1.6; margin-bottom: 1.75rem; }

        /* Alerts */
        .alert {
            position: fixed; bottom: 1.5rem; left: 1.5rem;
            padding: 0.75rem 1rem; border-radius: var(--radius);
            font-size: 0.85rem; font-weight: 500; z-index: 9999;
            max-width: 300px; display: flex; align-items: center; gap: 0.5rem;
            animation: alertIn 0.2s ease; border-left: 3px solid;
        }
        .alert-error { background: rgba(239,68,68,0.12); color: #f87171; border-color: #ef4444; }
        .alert-success { background: rgba(34,197,94,0.12); color: #4ade80; border-color: #22c55e; }
        .alert-info { background: rgba(107,31,173,0.12); color: var(--primary-light); border-color: var(--primary); }
        @keyframes alertIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

        /* Theme toggle */
        .theme-toggle {
            position: fixed; top: 1.5rem; right: 1.5rem;
            width: 40px; height: 40px;
            background: var(--surface-2); border: 1px solid var(--border);
            border-radius: var(--radius); cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; transition: all 0.2s; z-index: 100;
        }
        .theme-toggle:hover { border-color: var(--primary); background: var(--primary); }

        /* Responsive */
        @media (max-width: 900px) {
            .auth-brand { display: none; }
            .auth-panel { width: 100%; border-left: none; padding: 2.5rem 2rem; min-height: 100vh; }
        }
        @media (max-width: 480px) {
            .auth-panel { padding: 2rem 1.5rem; }
            .code-input { width: 40px !important; height: 48px; font-size: 1.2rem; }
        }
    </style>
</head>
<body>
    <button class="theme-toggle" id="themeToggle" title="Alternar tema">&#9790;</button>

    <div class="auth-split">
        <div class="auth-brand">
            <div class="brand-grid"></div>
            <div class="brand-content">
                <img class="brand-logo" src="img/logo_stoktech.png" alt="StokTech">
                <h2 class="brand-tagline">Recupere<br>seu <span>acesso</span><br>com segurança.</h2>
                <p class="brand-desc">Siga as etapas para redefinir sua senha e voltar a acessar o laboratório.</p>
                <div class="brand-security">
                    <div class="brand-sec-item">
                        <div class="brand-sec-icon">&#9993;</div>
                        <span>Código enviado para seu email cadastrado</span>
                    </div>
                    <div class="brand-sec-item">
                        <div class="brand-sec-icon">&#9919;</div>
                        <span>Verificação em duas etapas com código único</span>
                    </div>
                    <div class="brand-sec-item">
                        <div class="brand-sec-icon">&#9775;</div>
                        <span>Senha criptografada e armazenada com segurança</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="auth-panel">
            <div class="panel-header" id="panelHeader">
                <h1>Redefinir Senha</h1>
                <p>Recupere o acesso à sua conta StokTech</p>
            </div>

            <!-- Step indicator -->
            <div class="step-track">
                <div class="step-dot active" id="sd1">1</div>
                <div class="step-line" id="sl1"></div>
                <div class="step-dot" id="sd2">2</div>
                <div class="step-line" id="sl2"></div>
                <div class="step-dot" id="sd3">3</div>
            </div>
            <div class="step-labels">
                <span class="step-label active" id="sl-label1">Email</span>
                <span class="step-label" id="sl-label2">Código</span>
                <span class="step-label" id="sl-label3">Nova Senha</span>
            </div>

            <!-- STEP 1: Email -->
            <div class="form-step active" id="step1">
                <form id="emailForm">
                    <div class="form-group">
                        <label for="email">Email cadastrado</label>
                        <input type="email" id="email" placeholder="seu@email.com" required>
                    </div>
                    <button type="submit" id="btnEnviar" class="btn-primary">Enviar Código</button>
                </form>
                <div class="back-link">
                    <a href="index.php">&#8592; Voltar para o login</a>
                </div>
            </div>

            <!-- STEP 2: Code -->
            <div class="form-step" id="step2">
                <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:1.5rem;text-align:center;line-height:1.6;">
                    Digite o código de 6 dígitos enviado para<br>
                    <span class="email-highlight" id="emailDisplay"></span>
                </p>
                <form id="codeForm">
                    <div class="form-group">
                        <div class="code-inputs">
                            <input type="text" class="code-input" maxlength="1" inputmode="numeric" pattern="[0-9]" required>
                            <input type="text" class="code-input" maxlength="1" inputmode="numeric" pattern="[0-9]" required>
                            <input type="text" class="code-input" maxlength="1" inputmode="numeric" pattern="[0-9]" required>
                            <input type="text" class="code-input" maxlength="1" inputmode="numeric" pattern="[0-9]" required>
                            <input type="text" class="code-input" maxlength="1" inputmode="numeric" pattern="[0-9]" required>
                            <input type="text" class="code-input" maxlength="1" inputmode="numeric" pattern="[0-9]" required>
                        </div>
                    </div>
                    <button type="submit" id="btnVerificar" class="btn-primary">Verificar Código</button>
                </form>
                <div class="resend-area">
                    <p>Não recebeu o código?</p>
                    <button id="resendBtn" class="btn-ghost" disabled>
                        Reenviar em <span class="timer" id="timer">60</span>s
                    </button>
                </div>
                <div class="back-link">
                    <a href="#" onclick="goToStep(1); return false;">&#8592; Voltar</a>
                </div>
            </div>

            <!-- STEP 3: New password -->
            <div class="form-step" id="step3">
                <form id="passwordForm">
                    <div class="form-group">
                        <label for="newPassword">Nova Senha</label>
                        <div class="stoktech-senha-wrap">
                            <input type="password" id="newPassword" placeholder="••••••••" required>
                        <button type="button" class="stoktech-toggle-senha" data-alvo="newPassword" tabindex="-1" aria-label="Mostrar senha"><svg class="icone-olho-aberto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg><svg class="icone-olho-fechado" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a20.3 20.3 0 0 1 5.06-5.94M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a20.3 20.3 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg></button>
                        </div>
                        <div class="strength-bar-wrap">
                            <div class="strength-bar" id="strengthBar"></div>
                        </div>
                        <div class="strength-text" id="strengthText"></div>
                        <div class="pw-requirements">
                            <div class="requirement" id="req1">Mínimo 8 caracteres</div>
                            <div class="requirement" id="req2">Letras maiúsculas e minúsculas</div>
                            <div class="requirement" id="req3">Pelo menos um número</div>
                            <div class="requirement" id="req4">Pelo menos um caractere especial</div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirmPassword">Confirmar Nova Senha</label>
                        <div class="stoktech-senha-wrap">
                            <input type="password" id="confirmPassword" placeholder="••••••••" required>
                        <button type="button" class="stoktech-toggle-senha" data-alvo="confirmPassword" tabindex="-1" aria-label="Mostrar senha"><svg class="icone-olho-aberto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg><svg class="icone-olho-fechado" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a20.3 20.3 0 0 1 5.06-5.94M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a20.3 20.3 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg></button>
                        </div>
                    </div>
                    <button type="submit" id="btnSalvar" class="btn-primary">Redefinir Senha</button>
                </form>
                <div class="back-link">
                    <a href="#" onclick="goToStep(2); return false;">&#8592; Voltar</a>
                </div>
            </div>

            <!-- STEP 4: Success -->
            <div class="form-step" id="step4">
                <div class="success-check">&#10003;</div>
                <p class="success-title">Senha Redefinida</p>
                <p class="success-desc">
                    Sua senha foi alterada com sucesso.<br>
                    Você já pode fazer login com as novas credenciais.
                </p>
                <button class="btn-primary" onclick="window.location.href='index.php'">Ir para o Login</button>
            </div>
        </div>
    </div>

    <script>
        let emailAtual = '';
        let resendTimer = 60;
        let timerInterval;

        // Theme
        const toggle = document.getElementById('themeToggle');
        if (localStorage.getItem('theme') === 'light') { document.body.classList.add('light-mode'); toggle.innerHTML = '&#9728;'; }
        toggle.addEventListener('click', () => {
            document.body.classList.toggle('light-mode');
            const isLight = document.body.classList.contains('light-mode');
            localStorage.setItem('theme', isLight ? 'light' : 'dark');
            toggle.innerHTML = isLight ? '&#9728;' : '&#9790;';
        });

        // ── STEP 1 ──
        document.getElementById('emailForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            emailAtual = document.getElementById('email').value.trim();
            const btn = document.getElementById('btnEnviar');
            btn.classList.add('btn-loading'); btn.disabled = true; btn.textContent = 'Enviando...';
            const fd = new FormData();
            fd.append('acao', 'enviar_codigo'); fd.append('email', emailAtual);
            try {
                const res = await fetch('processar_reset.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.sucesso) {
                    showNotification(data.mensagem, 'success');
                    document.getElementById('emailDisplay').textContent = emailAtual;
                    setTimeout(() => { goToStep(2); startResendTimer(); }, 800);
                } else { showNotification(data.mensagem, 'error'); }
            } catch { showNotification('Erro de conexão. Tente novamente.', 'error'); }
            btn.classList.remove('btn-loading'); btn.disabled = false; btn.textContent = 'Enviar Código';
        });

        // ── STEP 2 ──
        const codeInputs = document.querySelectorAll('.code-input');
        codeInputs.forEach((inp, i) => {
            inp.addEventListener('input', (e) => {
                e.target.value = e.target.value.replace(/\D/g, '');
                if (e.target.value.length === 1 && i < codeInputs.length - 1) codeInputs[i + 1].focus();
            });
            inp.addEventListener('keydown', (e) => { if (e.key === 'Backspace' && !e.target.value && i > 0) codeInputs[i - 1].focus(); });
            inp.addEventListener('paste', (e) => {
                e.preventDefault();
                const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
                if (paste.length === 6) { codeInputs.forEach((ci, idx) => ci.value = paste[idx] || ''); codeInputs[5].focus(); }
            });
        });

        document.getElementById('codeForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const code = Array.from(codeInputs).map(i => i.value).join('');
            if (code.length !== 6) { showNotification('Digite o código completo de 6 dígitos.', 'error'); return; }
            const btn = document.getElementById('btnVerificar');
            btn.classList.add('btn-loading'); btn.disabled = true; btn.textContent = 'Verificando...';
            const fd = new FormData();
            fd.append('acao', 'verificar_codigo'); fd.append('email', emailAtual); fd.append('code', code);
            try {
                const res = await fetch('processar_reset.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.sucesso) {
                    showNotification('Código verificado!', 'success');
                    setTimeout(() => goToStep(3), 800);
                } else {
                    showNotification(data.mensagem, 'error');
                    codeInputs.forEach(i => i.value = ''); codeInputs[0].focus();
                }
            } catch { showNotification('Erro de conexão. Tente novamente.', 'error'); }
            btn.classList.remove('btn-loading'); btn.disabled = false; btn.textContent = 'Verificar Código';
        });

        document.getElementById('resendBtn').addEventListener('click', async () => {
            const fd = new FormData();
            fd.append('acao', 'enviar_codigo'); fd.append('email', emailAtual);
            try {
                const res = await fetch('processar_reset.php', { method: 'POST', body: fd });
                const data = await res.json();
                showNotification(data.sucesso ? 'Novo código enviado!' : data.mensagem, data.sucesso ? 'info' : 'error');
            } catch { showNotification('Erro ao reenviar. Tente novamente.', 'error'); }
            codeInputs.forEach(i => i.value = ''); codeInputs[0].focus();
            startResendTimer();
        });

        function startResendTimer() {
            resendTimer = 60;
            const btn = document.getElementById('resendBtn');
            btn.disabled = true;
            btn.innerHTML = 'Reenviar em <span class="timer" id="timer">60</span>s';
            if (timerInterval) clearInterval(timerInterval);
            timerInterval = setInterval(() => {
                resendTimer--;
                const el = document.getElementById('timer');
                if (el) el.textContent = resendTimer;
                if (resendTimer <= 0) {
                    clearInterval(timerInterval); btn.disabled = false; btn.innerHTML = 'Reenviar Código';
                }
            }, 1000);
        }

        // ── STEP 3 ──
        document.getElementById('newPassword').addEventListener('input', (e) => checkPasswordStrength(e.target.value));

        function checkPasswordStrength(pw) {
            const reqs = {
                length: pw.length >= 8,
                uppercase: /[A-Z]/.test(pw) && /[a-z]/.test(pw),
                number: /[0-9]/.test(pw),
                special: /[^A-Za-z0-9]/.test(pw)
            };
            document.getElementById('req1').classList.toggle('met', reqs.length);
            document.getElementById('req2').classList.toggle('met', reqs.uppercase);
            document.getElementById('req3').classList.toggle('met', reqs.number);
            document.getElementById('req4').classList.toggle('met', reqs.special);
            const strength = Object.values(reqs).filter(Boolean).length * 25;
            const bar = document.getElementById('strengthBar');
            const text = document.getElementById('strengthText');
            bar.style.width = strength + '%';
            const colors = ['', '#ef4444', '#f59e0b', '#eab308', '#22c55e'];
            const labels = ['', 'Fraca', 'Média', 'Boa', 'Forte'];
            const level = strength / 25;
            bar.style.background = colors[level] || 'transparent';
            text.textContent = labels[level] || '';
            text.style.color = colors[level] || '';
        }

        document.getElementById('passwordForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const nova = document.getElementById('newPassword').value;
            const confirma = document.getElementById('confirmPassword').value;
            if (nova !== confirma) { showNotification('As senhas não coincidem.', 'error'); return; }
            if (nova.length < 8) { showNotification('A senha deve ter pelo menos 8 caracteres.', 'error'); return; }
            const btn = document.getElementById('btnSalvar');
            btn.classList.add('btn-loading'); btn.disabled = true; btn.textContent = 'Salvando...';
            const fd = new FormData();
            fd.append('acao', 'nova_senha'); fd.append('senha', nova);
            try {
                const res = await fetch('processar_reset.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.sucesso) {
                    showNotification('Senha redefinida com sucesso!', 'success');
                    setTimeout(() => goToStep(4), 800);
                } else { showNotification(data.mensagem, 'error'); }
            } catch { showNotification('Erro de conexão. Tente novamente.', 'error'); }
            btn.classList.remove('btn-loading'); btn.disabled = false; btn.textContent = 'Redefinir Senha';
        });

        // ── UTILS ──
        function goToStep(step) {
            document.querySelectorAll('.form-step').forEach(s => s.classList.remove('active'));
            document.getElementById('step' + step).classList.add('active');

            // Update dots
            ['sd1','sd2','sd3'].forEach((id, i) => {
                const dot = document.getElementById(id);
                dot.classList.remove('active', 'done');
                if (i + 1 < step) dot.classList.add('done');
                else if (i + 1 === step) dot.classList.add('active');
            });
            // Update lines
            ['sl1','sl2'].forEach((id, i) => {
                document.getElementById(id).classList.toggle('done', step > i + 2);
            });
            // Update labels
            ['sl-label1','sl-label2','sl-label3'].forEach((id, i) => {
                document.getElementById(id).classList.toggle('active', i + 1 === step);
            });

            // Update header
            const headers = [
                { title: 'Redefinir Senha', sub: 'Recupere o acesso à sua conta StokTech' },
                { title: 'Verificar Código', sub: 'Insira o código de 6 dígitos enviado ao seu email' },
                { title: 'Nova Senha', sub: 'Crie uma senha forte para a sua conta' },
                { title: 'Acesso Restaurado', sub: 'Tudo certo! Você já pode fazer login' }
            ];
            if (headers[step - 1]) {
                document.querySelector('#panelHeader h1').textContent = headers[step - 1].title;
                document.querySelector('#panelHeader p').textContent = headers[step - 1].sub;
            }
        }

        function showNotification(msg, type) {
            const el = document.createElement('div');
            el.className = `alert alert-${type}`;
            el.textContent = msg;
            document.body.appendChild(el);
            setTimeout(() => { el.style.opacity = '0'; el.style.transform = 'translateY(6px)'; el.style.transition = 'all 0.2s'; setTimeout(() => el.remove(), 200); }, 2800);
        }

        // Auto-focus code input
        const obs = new MutationObserver(() => {
            if (document.getElementById('step2').classList.contains('active')) codeInputs[0].focus();
        });
        obs.observe(document.getElementById('step2'), { attributes: true, attributeFilter: ['class'] });
    </script>
    <script src="js/protecao.js"></script>
</body>
</html>
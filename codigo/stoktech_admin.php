<?php
require_once 'auth.php';
require_once 'conexao.php';
verificar_admin();

if (empty($_SESSION['email']) || !isset($_SESSION['foto_perfil'])) {
    $stmt = $pdo->prepare("SELECT EMAIL, FOTO_PERFIL FROM USUARIO WHERE IDUSUARIO = ?");
    $stmt->execute([$_SESSION['idusuario']]);
    $row = $stmt->fetch();
    if ($row) {
        $_SESSION['email']       = $row['EMAIL'];
        $_SESSION['foto_perfil'] = $row['FOTO_PERFIL'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="img/logo.png" type="image/png">
    <link rel="stylesheet" href="css/protecao.css">
    <title>StokTech — Painel Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
:root {
    --primary: #6b1fad;
    --primary-light: #b662ff;
    --primary-subtle: rgba(107,31,173,0.10);
    --bg: #0d0d0f;
    --surface: #131316;
    --surface-2: #1a1a1f;
    --surface-3: #202028;
    --border: #252530;
    --text: #f0f0f4;
    --text-muted: #7a7a90;
    --text-subtle: #4a4a60;
    --success: #22c55e;
    --warning: #f59e0b;
    --danger: #ef4444;
    --radius: 4px;
    --sidebar-w: 240px;
    --header-h: 60px;
}
body.light-mode {
    --bg: #f0f0f5;
    --surface: #ffffff;
    --surface-2: #f6f6fb;
    --surface-3: #ebebf5;
    --border: #dcdce8;
    --text: #0d0d1a;
    --text-muted: #55556a;
    --text-subtle: #aaaabc;
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'IBM Plex Sans', sans-serif;
    background: var(--bg); color: var(--text);
    transition: background 0.3s, color 0.3s;
    overflow-x: hidden;
}

/* ── HEADER ── */
.header {
    height: var(--header-h); background: var(--surface);
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; padding: 0 1.5rem; gap: 1rem;
    position: sticky; top: 0; z-index: 200;
}
.logo-img { height: 26px; object-fit: contain; }
.header-sep { width: 1px; height: 20px; background: var(--border); margin: 0 0.25rem; }
.admin-badge {
    font-size: 0.68rem; font-family: 'IBM Plex Mono', monospace;
    text-transform: uppercase; letter-spacing: 0.1em;
    color: var(--primary-light); background: var(--primary-subtle);
    border: 1px solid rgba(107,31,173,0.2);
    padding: 0.2rem 0.5rem; border-radius: 2px;
}
.header-actions { margin-left: auto; display: flex; align-items: center; gap: 0.5rem; }
.hdr-btn {
    width: 36px; height: 36px; background: transparent;
    border: 1px solid var(--border); border-radius: var(--radius);
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem; color: var(--text-muted); transition: all 0.2s;
}
.hdr-btn:hover { border-color: var(--primary); color: var(--primary-light); background: var(--primary-subtle); }

/* Header user profile */
.hdr-user {
    display: flex; align-items: center; gap: 0.65rem;
    cursor: pointer; padding: 0.3rem 0.7rem;
    border-radius: var(--radius); border: 1px solid transparent;
    transition: all 0.2s; position: relative;
}
.hdr-user:hover { border-color: var(--border); background: var(--surface-2); }
.hdr-user:hover .edit-pencil { opacity: 1; }
.hdr-avatar {
    width: 32px; height: 32px; border-radius: 50%;
    background: var(--primary-subtle); border: 2px solid var(--primary);
    overflow: hidden; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; font-size: 0.85rem; font-weight: 700;
    color: var(--primary-light); font-family: 'IBM Plex Sans', sans-serif;
}
.hdr-avatar img { width: 100%; height: 100%; object-fit: cover; }
.hdr-user-info { display: flex; flex-direction: column; line-height: 1.25; }
.hdr-user-name { font-size: 0.82rem; font-weight: 600; white-space: nowrap; max-width: 130px; overflow: hidden; text-overflow: ellipsis; }
.hdr-user-email { font-size: 0.7rem; color: var(--text-muted); white-space: nowrap; max-width: 130px; overflow: hidden; text-overflow: ellipsis; }
.edit-pencil { opacity: 0; transition: opacity 0.2s; font-size: 0.72rem; color: var(--primary-light); }

/* ── LAYOUT ── */
.main-container { display: flex; }

/* ── SIDEBAR (fixed) ── */
.sidebar {
    width: var(--sidebar-w); flex-shrink: 0;
    background: var(--surface); border-right: 1px solid var(--border);
    min-height: calc(100vh - var(--header-h));
    position: sticky; top: var(--header-h);
    height: calc(100vh - var(--header-h)); overflow-y: auto;
}
.sidebar-inner { padding: 1.25rem 0; }
.sidebar-section-label {
    padding: 0 1.25rem 0.6rem;
    font-size: 0.68rem; font-weight: 600;
    text-transform: uppercase; letter-spacing: 0.1em;
    color: var(--text-subtle); font-family: 'IBM Plex Mono', monospace;
}
.nav-item {
    display: flex; align-items: center; gap: 0.75rem;
    padding: 0.7rem 1.25rem; cursor: pointer;
    color: var(--text-muted); font-size: 0.88rem; font-weight: 400;
    transition: all 0.15s; border-left: 2px solid transparent;
    position: relative; white-space: nowrap;
}
.nav-item:hover { background: var(--surface-2); color: var(--text); border-left-color: var(--border); }
.nav-item.active { background: var(--primary-subtle); color: var(--primary-light); border-left-color: var(--primary); font-weight: 500; }
.nav-icon {
    width: 16px; height: 16px; display: flex; align-items: center;
    justify-content: center; flex-shrink: 0; opacity: 0.6;
}
.nav-item.active .nav-icon, .nav-item:hover .nav-icon { opacity: 1; }
.notification-badge {
    margin-left: auto; background: var(--primary); color: white;
    font-size: 0.65rem; font-weight: 700; font-family: 'IBM Plex Mono', monospace;
    width: 18px; height: 18px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
}

/* ── CONTENT ── */
.content-area { flex: 1; padding: 2rem; min-width: 0; }
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
.section-title { font-size: 1.4rem; font-weight: 600; letter-spacing: -0.02em; }

/* ── STATS ── */
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
.stat-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 1.25rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.stat-card:hover { border-color: var(--primary); box-shadow: 0 4px 16px rgba(107,31,173,0.1); }
.stat-icon { font-size: 1.4rem; margin-bottom: 0.6rem; }
.stat-value { font-size: 2rem; font-weight: 700; color: var(--primary-light); font-family: 'IBM Plex Mono', monospace; margin-bottom: 0.2rem; }
.stat-label { font-size: 0.8rem; color: var(--text-muted); }

/* ── TABLES ── */
.table-container { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; overflow-x: auto; }
.table { width: 100%; border-collapse: collapse; min-width: 500px; }
.table thead { background: var(--surface-2); border-bottom: 1px solid var(--border); }
.table th {
    padding: 0.75rem 1rem; text-align: left;
    font-size: 0.75rem; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.07em; color: var(--text-muted); font-family: 'IBM Plex Mono', monospace;
    white-space: nowrap;
}
.table td { padding: 0.85rem 1rem; font-size: 0.88rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
.table tbody tr:last-child td { border-bottom: none; }
.table tbody tr:hover { background: var(--surface-2); }
.table-actions { display: flex; gap: 0.35rem; }
.btn-icon {
    width: 30px; height: 30px; background: transparent;
    border: 1px solid var(--border); border-radius: 2px;
    cursor: pointer; font-size: 0.85rem; display: flex; align-items: center;
    justify-content: center; transition: all 0.15s; color: var(--text-muted);
}
.btn-icon:hover { background: var(--surface-3); }
.btn-edit:hover { border-color: var(--primary); color: var(--primary-light); }
.btn-delete:hover { border-color: var(--danger); color: var(--danger); }
.btn-ban:hover { border-color: var(--warning); color: var(--warning); }

/* ── BADGES ── */
.badge {
    display: inline-block; padding: 0.2rem 0.6rem;
    font-size: 0.72rem; font-weight: 600; border-radius: 2px;
    font-family: 'IBM Plex Mono', monospace; text-transform: uppercase;
    letter-spacing: 0.04em;
}
.badge-success { background: rgba(34,197,94,0.12); color: var(--success); }
.badge-warning { background: rgba(245,158,11,0.12); color: var(--warning); }
.badge-danger { background: rgba(239,68,68,0.12); color: var(--danger); }

/* ── CHAT LIST ── */
.chat-list-admin { display: flex; flex-direction: column; gap: 0.75rem; }
.chat-item {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 1.25rem;
    cursor: pointer; transition: all 0.15s; display: flex; gap: 1rem; align-items: center;
}
.chat-item:hover { border-color: var(--primary); background: var(--primary-subtle); }
.chat-item.pending { border-left: 3px solid var(--warning); }
.chat-avatar-admin {
    width: 44px; height: 44px; border-radius: 50%;
    background: var(--surface-2); border: 1px solid var(--border);
    display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem; font-weight: 700; color: var(--text-muted);
    flex-shrink: 0; font-family: 'IBM Plex Sans', sans-serif;
}
.chat-info { flex: 1; min-width: 0; }
.chat-name { font-weight: 600; font-size: 0.9rem; margin-bottom: 0.2rem; }
.chat-last { color: var(--text-muted); font-size: 0.8rem; margin-bottom: 0.2rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.chat-date { color: var(--text-subtle); font-size: 0.75rem; font-family: 'IBM Plex Mono', monospace; }

/* ── EMPTY STATE ── */
.empty-state { text-align: center; padding: 3.5rem 2rem; color: var(--text-muted); }
.empty-state svg { margin-bottom: 1rem; opacity: 0.3; }
.empty-state p { font-size: 0.9rem; }

/* ── MODALS ── */
.modal {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);
    z-index: 1000; align-items: center; justify-content: center; padding: 1rem;
}
.modal.active { display: flex; animation: fadeModal 0.2s ease; }
@keyframes fadeModal { from { opacity: 0; } to { opacity: 1; } }
.modal-content {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius); width: 100%; max-width: 560px;
    max-height: 88vh; overflow: hidden; display: flex; flex-direction: column;
    animation: slideModal 0.25s ease;
}
.modal-large { max-width: 760px; }
.modal-chat { max-width: 500px; max-height: 580px; }
@keyframes slideModal { from { transform: translateY(14px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
.modal-header {
    padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border);
    display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-shrink: 0;
}
.modal-header h2 { font-size: 1rem; font-weight: 600; }
.close-modal {
    width: 28px; height: 28px; background: transparent; border: none;
    cursor: pointer; color: var(--text-muted); font-size: 1.3rem;
    display: flex; align-items: center; justify-content: center;
    border-radius: 2px; transition: all 0.15s; padding: 0; line-height: 1;
}
.close-modal:hover { color: var(--danger); background: rgba(239,68,68,0.1); }
.modal-body { padding: 1.5rem; overflow-y: auto; flex: 1; }

/* Forms in modals */
.form-group { margin-bottom: 1.25rem; }
.form-group label {
    display: block; font-size: 0.78rem; font-weight: 500;
    color: var(--text-muted); text-transform: uppercase;
    letter-spacing: 0.06em; margin-bottom: 0.45rem;
}
.form-group input,
.form-group select,
.form-group textarea {
    width: 100%; padding: 0.75rem 0.85rem;
    background: var(--surface-2); border: 1px solid var(--border);
    border-radius: var(--radius); color: var(--text);
    font-size: 0.9rem; font-family: 'IBM Plex Sans', sans-serif;
    outline: none; transition: border-color 0.2s;
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
    border-color: var(--primary); box-shadow: 0 0 0 3px rgba(107,31,173,0.1);
}
.form-group select { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%237a7a90' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 0.75rem center; appearance: none; padding-right: 2rem; }
.form-group textarea { resize: vertical; min-height: 90px; }
.spec-input { display: flex; gap: 0.5rem; margin-bottom: 0.5rem; }
.spec-input input { flex: 1; }
.remove-spec-btn {
    background: rgba(239,68,68,0.1); color: var(--danger);
    border: 1px solid rgba(239,68,68,0.2); border-radius: var(--radius);
    padding: 0 0.75rem; cursor: pointer; font-size: 1.1rem; transition: all 0.15s;
}
.remove-spec-btn:hover { background: rgba(239,68,68,0.2); }
.add-spec-btn {
    width: 100%; padding: 0.65rem; background: transparent; color: var(--text-muted);
    border: 1px dashed var(--border); border-radius: var(--radius); cursor: pointer;
    font-size: 0.85rem; font-family: 'IBM Plex Sans', sans-serif; transition: all 0.2s;
}
.add-spec-btn:hover { border-color: var(--primary); color: var(--primary-light); background: var(--primary-subtle); }
.form-actions { display: flex; gap: 0.75rem; margin-top: 1.5rem; }
.cancel-btn {
    flex: 1; padding: 0.85rem; background: transparent; color: var(--text-muted);
    border: 1px solid var(--border); border-radius: var(--radius);
    cursor: pointer; font-weight: 600; font-family: 'IBM Plex Sans', sans-serif;
    font-size: 0.88rem; transition: all 0.2s;
}
.cancel-btn:hover { border-color: var(--danger); color: var(--danger); background: rgba(239,68,68,0.08); }
.save-btn {
    flex: 1; padding: 0.85rem; background: var(--primary); color: white; border: none;
    border-radius: var(--radius); cursor: pointer; font-weight: 600;
    font-family: 'IBM Plex Sans', sans-serif; font-size: 0.88rem; transition: background 0.2s;
}
.save-btn:hover { background: var(--primary-light); }
.save-btn:disabled { opacity: 0.6; cursor: not-allowed; }

/* Action buttons */
.action-btn {
    background: var(--primary); color: white; border: none;
    padding: 0.65rem 1.2rem; border-radius: var(--radius);
    cursor: pointer; font-weight: 600; font-size: 0.85rem;
    font-family: 'IBM Plex Sans', sans-serif; transition: all 0.2s;
    display: flex; align-items: center; gap: 0.4rem;
}
.action-btn:hover { background: var(--primary-light); transform: translateY(-1px); }

/* Chat messages */
.chat-messages {
    padding: 1.25rem; overflow-y: auto; flex: 1;
    background: var(--bg); max-height: 360px;
}
.chat-message { padding: 0.75rem 1rem; margin-bottom: 0.6rem; border-radius: var(--radius); font-size: 0.85rem; line-height: 1.6; }
.chat-message.system { background: var(--primary-subtle); border: 1px solid rgba(107,31,173,0.2); }
.chat-message.user { background: var(--surface); border: 1px solid var(--border); margin-left: 15%; }
.chat-message.manager { background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.2); margin-right: 15%; }
.chat-input-container {
    padding: 1rem; border-top: 1px solid var(--border);
    display: flex; gap: 0.5rem; flex-shrink: 0;
}
.chat-input-container input {
    flex: 1; padding: 0.65rem 0.85rem; background: var(--surface-2);
    border: 1px solid var(--border); border-radius: var(--radius);
    color: var(--text); font-family: 'IBM Plex Sans', sans-serif;
    font-size: 0.88rem; outline: none; transition: border-color 0.2s;
}
.chat-input-container input:focus { border-color: var(--primary); }
.send-btn {
    padding: 0.65rem 1.25rem; background: var(--primary); color: white;
    border: none; border-radius: var(--radius); cursor: pointer;
    font-weight: 600; font-family: 'IBM Plex Sans', sans-serif; font-size: 0.85rem;
    transition: background 0.2s;
}
.send-btn:hover { background: var(--primary-light); }
.loan-request {
    background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.25);
    border-left: 3px solid var(--warning); padding: 1rem;
    border-radius: var(--radius); margin-bottom: 0.6rem;
}
.loan-request strong { color: var(--warning); font-size: 0.88rem; }
.loan-actions { display: flex; gap: 0.5rem; margin-top: 0.75rem; }
.loan-actions button {
    flex: 1; padding: 0.6rem; border: none; border-radius: var(--radius);
    cursor: pointer; font-weight: 600; font-family: 'IBM Plex Sans', sans-serif;
    font-size: 0.82rem; transition: all 0.2s;
}
.approve-btn { background: rgba(34,197,94,0.15); color: var(--success); border: 1px solid rgba(34,197,94,0.25) !important; }
.approve-btn:hover { background: rgba(34,197,94,0.25); }
.reject-btn { background: rgba(239,68,68,0.12); color: var(--danger); border: 1px solid rgba(239,68,68,0.2) !important; }
.reject-btn:hover { background: rgba(239,68,68,0.2); }

/* Toast */
.notification-toast {
    position: fixed; top: 72px; right: 1.25rem;
    background: var(--surface); border: 1px solid var(--primary);
    border-left: 3px solid var(--primary); color: var(--text);
    padding: 0.75rem 1rem; border-radius: var(--radius);
    font-size: 0.85rem; font-weight: 500; z-index: 9999; max-width: 280px;
    animation: toastIn 0.2s ease; box-shadow: 0 4px 16px rgba(0,0,0,0.2);
}
@keyframes toastIn { from { opacity: 0; transform: translateX(10px); } to { opacity: 1; transform: translateX(0); } }

/* Responsive */
@media (max-width: 900px) {
    :root { --sidebar-w: 200px; }
    .content-area { padding: 1.25rem; }
}
@media (max-width: 680px) {
    .sidebar { display: none; }
    .hdr-user-info { display: none; }
    .stats-grid { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 480px) {
    .stats-grid { grid-template-columns: 1fr; }
    .content-area { padding: 1rem; }
}
    </style>
</head>
<body>
    <!-- HEADER -->
    <header class="header">
        <img class="logo-img" src="img/logo_stoktech.png" alt="StokTech">
        <div class="header-sep"></div>
        <span class="admin-badge">Admin</span>
        <div class="header-actions">
            <a class="hdr-btn" href="menu_principal.php?visao=usuario" title="Ver como usuário" style="text-decoration:none; display:flex; align-items:center; justify-content:center;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
            </a>
            <button class="hdr-btn" id="themeToggle" title="Alternar tema">&#9790;</button>
            <div class="hdr-user" id="profileBtn" title="Editar Perfil">
                <div class="hdr-avatar" id="headerAvatar">A</div>
                <div class="hdr-user-info">
                    <span class="hdr-user-name" id="headerAdminName"><?= htmlspecialchars($_SESSION['nome'] ?? 'Administrador') ?></span>
                    <span class="hdr-user-email" id="headerAdminEmail"><?= htmlspecialchars($_SESSION['email'] ?? '') ?></span>
                </div>
                <span class="edit-pencil">&#9998;</span>
            </div>
            <button class="hdr-btn" id="logoutBtn" title="Sair">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            </button>
        </div>
    </header>

    <div class="main-container">
        <!-- SIDEBAR (fixed) -->
        <aside class="sidebar">
            <div class="sidebar-inner">
                <div class="sidebar-section-label">Navegação</div>
                <nav>
                    <div class="nav-item active" data-section="dashboard">
                        <span class="nav-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect></svg></span>
                        Dashboard
                    </div>
                    <div class="nav-item" data-section="components">
                        <span class="nav-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="6" height="6"></rect><path d="M3 9h2M3 12h2M3 15h2M19 9h2M19 12h2M19 15h2M9 3v2M12 3v2M15 3v2M9 19v2M12 19v2M15 19v2"></path><rect x="5" y="5" width="14" height="14" rx="1"></rect></svg></span>
                        Componentes
                    </div>
                    <div class="nav-item" data-section="categories">
                        <span class="nav-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg></span>
                        Categorias
                    </div>
                    <div class="nav-item" data-section="chats">
                        <span class="nav-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg></span>
                        Empréstimos
                        <span class="notification-badge" id="chatBadge">0</span>
                    </div>
                    <div class="nav-item" data-section="users">
                        <span class="nav-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg></span>
                        Usuários
                    </div>
                    <div class="nav-item" data-section="logs">
                        <span class="nav-icon"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg></span>
                        Logs
                    </div>
                </nav>
            </div>
        </aside>

        <!-- CONTENT -->
        <main class="content-area" id="contentArea"></main>
    </div>

    <!-- COMPONENT MODAL -->
    <div class="modal" id="componentModal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2 id="componentModalTitle">Adicionar Componente</h2>
                <button class="close-modal" id="closeComponentModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="componentForm" enctype="multipart/form-data">
                    <input type="hidden" id="compId">
                    <input type="hidden" id="compImagemAtual">
                    <div class="form-group">
                        <label>Imagem do Componente</label>
                        <div id="imagePreviewContainer" style="margin-bottom:0.5rem;">
                            <img id="imagePreview" src="" style="display:none;width:100px;height:100px;object-fit:cover;border-radius:var(--radius);border:1px solid var(--border);">
                        </div>
                        <input type="file" id="compImagem" accept="image/*" onchange="previewImage(this)" style="color:var(--text-muted);font-size:0.85rem;">
                    </div>
                    <div class="form-group">
                        <label>Nome do Componente *</label>
                        <input type="text" id="compName" required placeholder="Ex: Arduino Uno R3">
                    </div>
                    <div class="form-group">
                        <label>Categoria *</label>
                        <select id="compCategory" required>
                            <option value="">Selecione uma categoria</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Descrição Curta *</label>
                        <input type="text" id="compDescription" placeholder="Resumo em uma linha" required>
                    </div>
                    <div class="form-group">
                        <label>Descrição Completa *</label>
                        <textarea id="compFullDescription" rows="3" required placeholder="Descrição técnica completa"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Quantidade em Estoque *</label>
                        <input type="number" id="compStock" min="0" required placeholder="0">
                    </div>
                    <div class="form-actions">
                        <button type="button" class="cancel-btn" onclick="closeComponentModal()">Cancelar</button>
                        <button type="submit" class="save-btn">Salvar Componente</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- CATEGORY MODAL -->
    <div class="modal" id="categoryModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="categoryModalTitle">Adicionar Categoria</h2>
                <button class="close-modal" id="closeCategoryModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="categoryForm">
                    <input type="hidden" id="catId">
                    <div class="form-group">
                        <label>Ícone / Emoji *</label>
                        <input type="text" id="catIcon" placeholder="Ex: 🔌" maxlength="2" required>
                    </div>
                    <div class="form-group">
                        <label>Nome da Categoria *</label>
                        <input type="text" id="catName" required placeholder="Ex: Microcontroladores">
                    </div>
                    <div class="form-group">
                        <label>Descrição</label>
                        <input type="text" id="catDescricao" placeholder="Opcional">
                    </div>
                    <div class="form-actions">
                        <button type="button" class="cancel-btn" onclick="closeCategoryModal()">Cancelar</button>
                        <button type="submit" class="save-btn">Salvar Categoria</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- USER MODAL -->
    <div class="modal" id="userModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Editar Usuário</h2>
                <button class="close-modal" id="closeUserModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="userForm">
                    <input type="hidden" id="userId">
                    <div class="form-group">
                        <label>Nome *</label>
                        <input type="text" id="userName" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" id="userEmail" required>
                    </div>
                    <div class="form-group">
                        <label>CPF *</label>
                        <input type="text" id="userCpf" required>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="cancel-btn" onclick="closeUserModal()">Cancelar</button>
                        <button type="submit" class="save-btn">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- CHAT MODAL -->
    <div class="modal" id="chatModal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <div>
                    <h2 id="chatUserName">Chat com Usuário</h2>
                    <p id="chatUserEmail" style="color:var(--text-muted);font-size:0.78rem;margin-top:0.2rem;font-family:'IBM Plex Mono',monospace;"></p>
                </div>
                <div style="display:flex;gap:0.5rem;align-items:center;">
                    <button class="action-btn" onclick="saveChat()" style="padding:0.5rem 0.9rem;font-size:0.8rem;">Salvar Log</button>
                    <button class="close-modal" id="closeChatModal">&times;</button>
                </div>
            </div>
            <div class="chat-messages" id="chatMessagesAdmin"></div>
            <div class="chat-input-container">
                <input type="text" id="chatInputAdmin" placeholder="Responder...">
                <button class="send-btn" id="sendMessageAdmin">Enviar</button>
            </div>
        </div>
    </div>

    <!-- PROFILE MODAL -->
    <div class="modal" id="profileModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Editar Perfil</h2>
                <button class="close-modal" id="closeProfileModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="profileForm">
                    <div class="form-group">
                        <label>Nome *</label>
                        <input type="text" id="profileName" value="<?= htmlspecialchars($_SESSION['nome'] ?? 'Administrador') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" id="profileEmail" value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Nova Senha (deixe em branco para manter)</label>
                        <input type="password" id="profilePassword" placeholder="••••••••">
                    </div>
                    <div class="form-actions">
                        <button type="button" class="cancel-btn" onclick="closeProfileModal()">Cancelar</button>
                        <button type="submit" class="save-btn">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let categories = [];
        let components = [];
        let users = [];
        let chats = [];
        let emprestimos = [];
        let logs = [];
        let currentChatId = null;
        let adminProfile = {
            name: <?= json_encode($_SESSION['nome'] ?? 'Administrador') ?>,
            email: <?= json_encode($_SESSION['email'] ?? '') ?>
        };

        document.addEventListener('DOMContentLoaded', () => {
            loadTheme();
            updateHeaderProfile();
            setupEventListeners();
            carregarBadgeEmprestimos();
            loadSection('dashboard');
        });

        function setupEventListeners() {
            document.getElementById('themeToggle').addEventListener('click', toggleTheme);
            document.querySelectorAll('.nav-item').forEach(item => {
                item.addEventListener('click', function() {
                    document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                    loadSection(this.dataset.section);
                });
            });
            document.getElementById('logoutBtn').addEventListener('click', logout);
            document.getElementById('profileBtn').addEventListener('click', openProfileModal);
            document.getElementById('closeProfileModal').addEventListener('click', closeProfileModal);
            document.getElementById('profileForm').addEventListener('submit', saveProfile);
            document.getElementById('closeComponentModal').addEventListener('click', closeComponentModal);
            document.getElementById('componentForm').addEventListener('submit', saveComponent);
            document.getElementById('closeCategoryModal').addEventListener('click', closeCategoryModal);
            document.getElementById('categoryForm').addEventListener('submit', saveCategory);
            document.getElementById('closeUserModal').addEventListener('click', closeUserModal);
            document.getElementById('userForm').addEventListener('submit', saveUser);
            document.getElementById('closeChatModal').addEventListener('click', closeChatModal);
            document.getElementById('sendMessageAdmin').addEventListener('click', sendAdminMessage);
            document.getElementById('chatInputAdmin').addEventListener('keypress', e => { if(e.key === 'Enter') sendAdminMessage(); });
            document.querySelectorAll('.modal').forEach(modal => {
                modal.addEventListener('click', function(e) { if(e.target === this) this.classList.remove('active'); });
            });
        }

        // Theme
        function loadTheme() {
            if (localStorage.getItem('theme') === 'light') { document.body.classList.add('light-mode'); document.getElementById('themeToggle').innerHTML = '&#9728;'; }
        }
        function toggleTheme() {
            document.body.classList.toggle('light-mode');
            const isLight = document.body.classList.contains('light-mode');
            localStorage.setItem('theme', isLight ? 'light' : 'dark');
            document.getElementById('themeToggle').innerHTML = isLight ? '&#9728;' : '&#9790;';
        }

        async function logout() {
            const ok = await stoktechConfirm({ titulo: 'Sair do sistema', mensagem: 'Deseja realmente sair do sistema?', textoConfirmar: 'Sair' });
            if (ok) {
                showNotification('Saindo...');
                setTimeout(() => window.location.href = 'sairdacontasuperlegal.php', 800);
            }
        }

        // Header profile
        function updateHeaderProfile() {
            document.getElementById('headerAdminName').textContent = adminProfile.name;
            document.getElementById('headerAdminEmail').textContent = adminProfile.email;
            const av = document.getElementById('headerAvatar');
            av.textContent = adminProfile.name ? adminProfile.name.charAt(0).toUpperCase() : 'A';
        }

        // Section loader
        function loadSection(section) {
            const ca = document.getElementById('contentArea');
            switch(section) {
                case 'dashboard': ca.innerHTML = renderDashboard(); carregarDashboard(); break;
                case 'components': renderComponents(); break;
                case 'categories': renderCategories(); break;
                case 'chats': renderChats(); break;
                case 'users': renderUsers(); break;
                case 'logs': ca.innerHTML = renderLogs(); break;
            }
        }

        function renderDashboard() {
            return `
                <div class="section-header"><h1 class="section-title">Dashboard</h1></div>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">&#128230;</div>
                        <div class="stat-value" id="dashTotalComponentes">0</div>
                        <div class="stat-label">Tipos de Componentes</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">&#9826;</div>
                        <div class="stat-value" id="dashTotalEstoque">0</div>
                        <div class="stat-label">Itens em Estoque</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">&#9203;</div>
                        <div class="stat-value" id="dashPendentes">0</div>
                        <div class="stat-label">Solicitações Pendentes</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">&#128100;</div>
                        <div class="stat-value" id="dashUsuarios">0</div>
                        <div class="stat-label">Usuários Cadastrados</div>
                    </div>
                </div>
                <div class="section-header"><h2 style="font-size:1rem;font-weight:600;color:var(--text-muted);">Atividade Recente</h2></div>
                <div class="table-container">
                    <table class="table">
                        <thead><tr><th>Aluno</th><th>Ação</th><th>Data</th><th>Status</th></tr></thead>
                        <tbody id="dashAtividade">
                            <tr><td colspan="4" style="text-align:center;padding:2rem;color:var(--text-muted);font-size:0.85rem;">Carregando...</td></tr>
                        </tbody>
                    </table>
                </div>`;
        }

        async function carregarDashboard() {
            try {
                const fdComp = new FormData(); fdComp.append('acao', 'listar_componentes');
                const resComp = await fetch('gerenciar_componentes.php', { method: 'POST', body: fdComp });
                const dataComp = await resComp.json();
                if (dataComp.sucesso) components = dataComp.componentes;

                const fdUser = new FormData(); fdUser.append('acao', 'listar');
                const resUser = await fetch('gerenciar_usuarios.php', { method: 'POST', body: fdUser });
                const dataUser = await resUser.json();
                if (dataUser.sucesso) users = dataUser.usuarios;

                await carregarEmprestimos();

                const totalStock = components.reduce((s, c) => s + (parseInt(c.QUANTIDADE) || 0), 0);
                document.getElementById('dashTotalComponentes').textContent = components.length;
                document.getElementById('dashTotalEstoque').textContent = totalStock;
                document.getElementById('dashPendentes').textContent = emprestimos.filter(e => e.STATUS === 'PENDENTE').length;
                document.getElementById('dashUsuarios').textContent = users.length;

                const tbody = document.getElementById('dashAtividade');
                if (!emprestimos.length) {
                    tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;padding:2rem;color:var(--text-muted);font-size:0.85rem;">Nenhuma solicitação registrada.</td></tr>`;
                    return;
                }
                tbody.innerHTML = emprestimos.slice(0, 5).map(e => `
                    <tr>
                        <td>${e.ALUNO_NOME || '—'}</td>
                        <td>Solicitou empréstimo de ${e.ITENS.length} item${e.ITENS.length !== 1 ? 'ns' : ''}</td>
                        <td style="font-family:'IBM Plex Mono',monospace;font-size:0.8rem;">${formatarData(e.DATA_EMPRESTIMO)}</td>
                        <td>${statusBadge(e.STATUS)}</td>
                    </tr>`).join('');
            } catch(e) {
                const tbody = document.getElementById('dashAtividade');
                if (tbody) tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;padding:2rem;color:var(--danger);font-size:0.85rem;">Erro ao carregar dashboard.</td></tr>`;
            }
        }

        async function renderComponents() {
            const ca = document.getElementById('contentArea');
            ca.innerHTML = `<div class="section-header"><h1 class="section-title">Componentes</h1></div><div style="text-align:center;padding:3rem;color:var(--text-muted);font-size:0.88rem;">Carregando...</div>`;
            try {
                const fd = new FormData(); fd.append('acao', 'listar_componentes');
                const res = await fetch('gerenciar_componentes.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (!data.sucesso) { ca.innerHTML = `<div class="empty-state"><p>Erro ao carregar componentes.</p></div>`; return; }
                components = data.componentes;
                ca.innerHTML = `
                    <div class="section-header">
                        <h1 class="section-title">Componentes</h1>
                        <button class="action-btn" onclick="openAddComponent()">+ Adicionar</button>
                    </div>
                    <div class="table-container">
                        <table class="table">
                            <thead><tr><th>Imagem</th><th>Nome</th><th>Categoria</th><th>Estoque</th><th>Criado por</th><th>Ações</th></tr></thead>
                            <tbody>
                                ${components.length === 0 ? `<tr><td colspan="6" style="text-align:center;padding:2rem;color:var(--text-muted);font-size:0.85rem;">Nenhum componente cadastrado ainda.</td></tr>` : components.map(comp => `
                                    <tr>
                                        <td>${comp.IMAGEM ? `<img src="${comp.IMAGEM}" style="width:44px;height:44px;object-fit:cover;border-radius:var(--radius);border:1px solid var(--border);">` : `<div style="width:44px;height:44px;background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius);display:flex;align-items:center;justify-content:center;font-size:1.2rem;">&#128230;</div>`}</td>
                                        <td><strong style="font-size:0.88rem;">${comp.NOME}</strong><br><span style="color:var(--text-muted);font-size:0.78rem;">${comp.DESCRICAO_CURTA}</span></td>
                                        <td style="font-size:0.85rem;">${comp.CATEGORIA_ICONE || ''} ${comp.CATEGORIA_NOME || '—'}</td>
                                        <td><span class="badge ${comp.QUANTIDADE > 10 ? 'badge-success' : 'badge-warning'}">${comp.QUANTIDADE}</span></td>
                                        <td style="font-size:0.78rem;color:var(--text-muted);font-family:'IBM Plex Mono',monospace;">${comp.CRIADO_POR_NOME || '—'}</td>
                                        <td class="table-actions">
                                            <button class="btn-icon btn-edit" onclick="editComponent(${comp.IDCOMPONENTE})" title="Editar">&#9998;</button>
                                            <button class="btn-icon btn-delete" onclick="deleteComponent(${comp.IDCOMPONENTE})" title="Excluir">&#128465;</button>
                                        </td>
                                    </tr>`).join('')}
                            </tbody>
                        </table>
                    </div>`;
            } catch { ca.innerHTML = `<div class="empty-state"><p>Erro de conexão.</p></div>`; }
        }

        async function renderCategories() {
            const ca = document.getElementById('contentArea');
            ca.innerHTML = `<div class="section-header"><h1 class="section-title">Categorias</h1></div><div style="text-align:center;padding:3rem;color:var(--text-muted);font-size:0.88rem;">Carregando...</div>`;
            try {
                const fd = new FormData(); fd.append('acao', 'listar_categorias');
                const res = await fetch('gerenciar_componentes.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (!data.sucesso) { ca.innerHTML = `<div class="empty-state"><p>Erro ao carregar categorias.</p></div>`; return; }
                categories = data.categorias;
                ca.innerHTML = `
                    <div class="section-header">
                        <h1 class="section-title">Categorias</h1>
                        <button class="action-btn" onclick="openAddCategory()">+ Adicionar</button>
                    </div>
                    <div class="table-container">
                        <table class="table">
                            <thead><tr><th>Ícone</th><th>Nome</th><th>Descrição</th><th>Ações</th></tr></thead>
                            <tbody>
                                ${categories.length === 0 ? `<tr><td colspan="4" style="text-align:center;padding:2rem;color:var(--text-muted);font-size:0.85rem;">Nenhuma categoria cadastrada.</td></tr>` : categories.map(cat => `
                                    <tr>
                                        <td style="font-size:1.4rem;">${cat.ICONE}</td>
                                        <td><strong style="font-size:0.88rem;">${cat.NOME}</strong></td>
                                        <td style="color:var(--text-muted);font-size:0.85rem;">${cat.DESCRIÇÃO || '—'}</td>
                                        <td class="table-actions">
                                            <button class="btn-icon btn-edit" onclick="editCategory(${cat.IDCATEGORIA})" title="Editar">&#9998;</button>
                                            <button class="btn-icon btn-delete" onclick="deleteCategory(${cat.IDCATEGORIA})" title="Excluir">&#128465;</button>
                                        </td>
                                    </tr>`).join('')}
                            </tbody>
                        </table>
                    </div>`;
            } catch { ca.innerHTML = `<div class="empty-state"><p>Erro de conexão.</p></div>`; }
        }

        async function carregarEmprestimos() {
            const fd = new FormData(); fd.append('acao', 'listar');
            const res = await fetch('gerenciar_emprestimos.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.sucesso) {
                emprestimos = data.emprestimos;
                const badge = document.getElementById('chatBadge');
                if (badge) badge.textContent = emprestimos.filter(e => e.STATUS === 'PENDENTE').length;
            }
            return emprestimos;
        }

        async function carregarBadgeEmprestimos() {
            try { await carregarEmprestimos(); } catch(e) {}
        }

        function statusBadge(status) {
    const s = (status || 'PENDENTE').toUpperCase();

    if (s === 'PENDENTE') {
        return '<span class="badge badge-warning">Pendente</span>';
    }

    if (s === 'APROVADO') {
        return '<span class="badge badge-success">Aprovado</span>';
    }

    if (s === 'RETIRADO') {
        return '<span class="badge badge-success">Retirado</span>';
    }

    if (s === 'DEVOLVIDO') {
        return '<span class="badge badge-success">Devolvido</span>';
    }

    if (s === 'RECUSADO') {
        return '<span class="badge badge-danger">Recusado</span>';
    }

    if (s === 'CANCELADO') {
        return '<span class="badge badge-danger">Cancelado</span>';
    }

    return '<span class="badge badge-warning">' + s + '</span>';
}

        function formatarData(data) {
            if (!data) return '—';
            const partes = String(data).split('-');
            if (partes.length === 3) return `${partes[2]}/${partes[1]}/${partes[0]}`;
            return data;
        }

        async function renderChats() {
            const ca = document.getElementById('contentArea');
            ca.innerHTML = `<div class="section-header"><h1 class="section-title">Empréstimos</h1></div><div style="text-align:center;padding:3rem;color:var(--text-muted);font-size:0.88rem;">Carregando...</div>`;
            try {
                await carregarEmprestimos();
                if (!emprestimos.length) {
                    ca.innerHTML = `<div class="section-header"><h1 class="section-title">Empréstimos</h1></div><div class="empty-state"><p>Nenhuma solicitação de empréstimo ainda.</p></div>`;
                    return;
                }
                ca.innerHTML = `
                    <div class="section-header"><h1 class="section-title">Empréstimos</h1></div>
                    <div class="chat-list-admin">
                        ${emprestimos.map(e => `
                            <div class="chat-item ${e.STATUS === 'PENDENTE' ? 'pending' : ''}" onclick="openChatModal(${e.IDEMPRESTIMO})">
                                <div class="chat-avatar-admin">${(e.ALUNO_NOME || 'A').charAt(0).toUpperCase()}</div>
                                <div class="chat-info">
                                    <div class="chat-name">${e.ALUNO_NOME || 'Aluno'}</div>
                                    <div class="chat-last">${e.ITENS.length} item${e.ITENS.length !== 1 ? 'ns' : ''} solicitado${e.ITENS.length !== 1 ? 's' : ''}</div>
                                    <div class="chat-date">${formatarData(e.DATA_EMPRESTIMO)}</div>
                                </div>
                                ${statusBadge(e.STATUS)}
                            </div>`).join('')}
                    </div>`;
            } catch(e) {
                ca.innerHTML = `<div class="empty-state"><p>Erro ao carregar empréstimos.</p></div>`;
            }
        }

        async function renderUsers() {
            const ca = document.getElementById('contentArea');
            ca.innerHTML = `<div class="section-header"><h1 class="section-title">Usuários</h1></div><div style="text-align:center;padding:3rem;color:var(--text-muted);font-size:0.88rem;">Carregando...</div>`;
            try {
                const fd = new FormData(); fd.append('acao', 'listar');
                const res = await fetch('gerenciar_usuarios.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (!data.sucesso) { ca.innerHTML = `<div class="empty-state"><p>Erro ao carregar usuários.</p></div>`; return; }
                users = data.usuarios;
                ca.innerHTML = `
                    <div class="section-header"><h1 class="section-title">Usuários</h1></div>
                    <div class="table-container">
                        <table class="table">
                            <thead><tr><th>Nome</th><th>Email</th><th>CPF</th><th>Tipo</th><th>Ações</th></tr></thead>
                            <tbody>
                                ${users.map(u => `
                                    <tr id="user-row-${u.IDUSUARIO}">
                                        <td><strong style="font-size:0.88rem;">${u.NOME}</strong></td>
                                        <td style="font-family:'IBM Plex Mono',monospace;font-size:0.8rem;">${u.EMAIL}</td>
                                        <td style="font-family:'IBM Plex Mono',monospace;font-size:0.8rem;">${u.CPF}</td>
                                        <td><span class="badge ${u.TIPO === 'ADMINISTRADOR' ? 'badge-danger' : 'badge-success'}">${u.TIPO === 'ADMINISTRADOR' ? 'Admin' : 'Aluno'}</span></td>
                                        <td class="table-actions">
                                            <button class="btn-icon btn-edit" onclick="editUser(${u.IDUSUARIO})" title="Editar">&#9998;</button>
                                            <button class="btn-icon btn-ban" onclick="toggleAdmin(${u.IDUSUARIO})" title="${u.TIPO === 'ADMINISTRADOR' ? 'Remover Admin' : 'Tornar Admin'}">&#9733;</button>
                                            <button class="btn-icon btn-delete" onclick="deleteUser(${u.IDUSUARIO})" title="Excluir">&#128465;</button>
                                        </td>
                                    </tr>`).join('')}
                            </tbody>
                        </table>
                    </div>`;
            } catch { ca.innerHTML = `<div class="empty-state"><p>Erro de conexão.</p></div>`; }
        }

        function renderLogs() {
            if (!logs.length) return `<div class="section-header"><h1 class="section-title">Logs</h1></div><div class="empty-state"><p>Nenhum log salvo ainda.</p></div>`;
            return `
                <div class="section-header"><h1 class="section-title">Logs</h1></div>
                <div class="table-container">
                    <table class="table">
                        <thead><tr><th>Arquivo</th><th>Usuário</th><th>Data</th><th>Ações</th></tr></thead>
                        <tbody>
                            ${logs.map((log, i) => `
                                <tr>
                                    <td style="font-family:'IBM Plex Mono',monospace;font-size:0.8rem;"><strong>${log.filename}</strong></td>
                                    <td style="font-size:0.85rem;">${log.userName}</td>
                                    <td style="font-family:'IBM Plex Mono',monospace;font-size:0.78rem;color:var(--text-muted);">${log.date}</td>
                                    <td class="table-actions">
                                        <button class="btn-icon" onclick="downloadLog(${i})" title="Baixar">&#8595;</button>
                                        <button class="btn-icon btn-delete" onclick="deleteLog(${i})" title="Excluir">&#128465;</button>
                                    </td>
                                </tr>`).join('')}
                        </tbody>
                    </table>
                </div>`;
        }

        // Component CRUD
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
                reader.readAsDataURL(input.files[0]);
            }
        }
        async function openAddComponent() {
            document.getElementById('componentModalTitle').textContent = 'Adicionar Componente';
            document.getElementById('componentForm').reset();
            document.getElementById('compId').value = '';
            document.getElementById('compImagemAtual').value = '';
            document.getElementById('imagePreview').style.display = 'none';
            await populateCategorySelect();
            document.getElementById('componentModal').classList.add('active');
        }
        async function editComponent(id) {
            const comp = components.find(c => c.IDCOMPONENTE == id);
            if (!comp) return;
            document.getElementById('componentModalTitle').textContent = 'Editar Componente';
            document.getElementById('compId').value = comp.IDCOMPONENTE;
            document.getElementById('compImagemAtual').value = comp.IMAGEM || '';
            document.getElementById('compName').value = comp.NOME;
            document.getElementById('compDescription').value = comp.DESCRICAO_CURTA;
            document.getElementById('compFullDescription').value = comp.DESCRICAO_COMPLETA;
            document.getElementById('compStock').value = comp.QUANTIDADE;
            const preview = document.getElementById('imagePreview');
            if (comp.IMAGEM) { preview.src = comp.IMAGEM; preview.style.display = 'block'; } else { preview.style.display = 'none'; }
            await populateCategorySelect(comp.IDCATEGORIA);
            document.getElementById('componentModal').classList.add('active');
        }
        function closeComponentModal() { document.getElementById('componentModal').classList.remove('active'); }
        async function populateCategorySelect(selectedId = null) {
            const select = document.getElementById('compCategory');
            select.innerHTML = '<option value="">Selecione uma categoria</option>';
            try {
                const fd = new FormData(); fd.append('acao', 'listar_categorias');
                const res = await fetch('gerenciar_componentes.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.sucesso) {
                    data.categorias.forEach(cat => {
                        const opt = document.createElement('option');
                        opt.value = cat.IDCATEGORIA; opt.textContent = `${cat.ICONE} ${cat.NOME}`;
                        if (selectedId && cat.IDCATEGORIA == selectedId) opt.selected = true;
                        select.appendChild(opt);
                    });
                }
            } catch {}
        }
        async function saveComponent(e) {
    e.preventDefault();

    const btn = e.target.querySelector('[type="submit"]');
    btn.disabled = true;
    btn.textContent = 'Salvando...';

    const fd = new FormData();

    fd.append('acao', 'salvar_componente');
    fd.append('id', document.getElementById('compId').value);
    fd.append('imagem_atual', document.getElementById('compImagemAtual').value);
    fd.append('nome', document.getElementById('compName').value);
    fd.append('idcategoria', document.getElementById('compCategory').value);
    fd.append('descricao_curta', document.getElementById('compDescription').value);
    fd.append('descricao_completa', document.getElementById('compFullDescription').value);
    fd.append('quantidade', document.getElementById('compStock').value);

    const imgFile = document.getElementById('compImagem').files[0];

    if (imgFile) {
        fd.append('imagem', imgFile);
    }

    try {
        const res = await fetch('gerenciar_componentes.php', {
            method: 'POST',
            body: fd
        });

        const texto = await res.text();

        let data;

        try {
            data = JSON.parse(texto);
        } catch (erroJson) {
            stoktechToast('O PHP não retornou JSON. Resposta: ' + texto.substring(0, 300, 'erro'));
            console.error(texto);
            return;
        }

        if (data.sucesso) {
            showNotification(data.mensagem);
            closeComponentModal();
            renderComponents();
        } else {
            stoktechToast(data.mensagem, 'erro');
        }

    } catch (erro) {
        stoktechToast('Erro de conexão: ' + erro.message, 'erro');
        console.error(erro);

    } finally {
        btn.disabled = false;
        btn.textContent = 'Salvar Componente';
    }
}
        async function deleteComponent(id) {
            const comp = components.find(c => c.IDCOMPONENTE == id);

            if (!comp) return;

            const confirmar = await stoktechConfirm({
                titulo: 'Excluir componente',
                mensagem: `Você tem certeza que deseja excluir "${comp.NOME}"?`,
                textoConfirmar: 'Excluir'
            });

            if (!confirmar) return;

            const fd = new FormData();
            fd.append('acao', 'excluir_componente');
            fd.append('id', id);

            try {
                const res = await fetch('gerenciar_componentes.php', {
                    method: 'POST',
                    body: fd
                });

                const texto = await res.text();

                let data;

                try {
                    data = JSON.parse(texto);
                } catch (erroJson) {
                    stoktechToast('O PHP não retornou JSON. Resposta: ' + texto.substring(0, 300), 'erro');
                    console.error(texto);
                    return;
                }

                if (data.sucesso) {
                    showNotification(data.mensagem);
                    renderComponents();
                } else {
                    stoktechToast(data.mensagem, 'erro');
                }

            } catch (erro) {
                stoktechToast('Erro de conexão: ' + erro.message, 'erro');
                console.error(erro);
            }
        }

        // Category CRUD
        function openAddCategory() {
            document.getElementById('categoryModalTitle').textContent = 'Adicionar Categoria';
            document.getElementById('categoryForm').reset(); document.getElementById('catId').value = '';
            document.getElementById('categoryModal').classList.add('active');
        }
        function editCategory(id) {
            const cat = categories.find(c => c.IDCATEGORIA == id);
            if (!cat) return;
            document.getElementById('categoryModalTitle').textContent = 'Editar Categoria';
            document.getElementById('catId').value = cat.IDCATEGORIA;
            document.getElementById('catIcon').value = cat.ICONE;
            document.getElementById('catName').value = cat.NOME;
            document.getElementById('catDescricao').value = cat.DESCRIÇÃO || '';
            document.getElementById('categoryModal').classList.add('active');
        }
        function closeCategoryModal() { document.getElementById('categoryModal').classList.remove('active'); }
        async function saveCategory(e) {
    e.preventDefault();

    const btn = e.target.querySelector('[type="submit"]');
    btn.disabled = true;
    btn.textContent = 'Salvando...';

    const fd = new FormData();
    fd.append('acao', 'salvar_categoria');
    fd.append('id', document.getElementById('catId').value);
    fd.append('icone', document.getElementById('catIcon').value);
    fd.append('nome', document.getElementById('catName').value);
    fd.append('descricao', document.getElementById('catDescricao').value);

    try {
        const res = await fetch('gerenciar_componentes.php', {
            method: 'POST',
            body: fd
        });

        const texto = await res.text();

        let data;

        try {
            data = JSON.parse(texto);
        } catch (erroJson) {
            stoktechToast('O PHP não retornou JSON. Resposta: ' + texto.substring(0, 300, 'erro'));
            console.error(texto);
            return;
        }

        if (data.sucesso) {
            showNotification(data.mensagem);
            closeCategoryModal();
            renderCategories();
        } else {
            stoktechToast(data.mensagem, 'erro');
        }

    } catch (erro) {
        stoktechToast('Erro de conexão: ' + erro.message, 'erro');
        console.error(erro);

    } finally {
        btn.disabled = false;
        btn.textContent = 'Salvar Categoria';
    }
}
        async function deleteCategory(id) {
            const cat = categories.find(c => c.IDCATEGORIA == id);
            if (!cat) return;
            if (!(await stoktechConfirm({ titulo: 'Excluir categoria', mensagem: `Você tem certeza que deseja excluir "${cat.NOME}"?`, textoConfirmar: 'Excluir' }))) return;
            const fd = new FormData(); fd.append('acao', 'excluir_categoria'); fd.append('id', id);
            try {
                const res = await fetch('gerenciar_componentes.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.sucesso) { showNotification(data.mensagem); renderCategories(); } else { stoktechToast(data.mensagem, 'erro'); }
            } catch { stoktechToast('Erro de conexão.', 'erro'); }
        }

        // User CRUD
        function editUser(id) {
            const u = users.find(u => u.IDUSUARIO == id);
            if (!u) return;
            document.getElementById('userId').value = u.IDUSUARIO;
            document.getElementById('userName').value = u.NOME;
            document.getElementById('userEmail').value = u.EMAIL;
            document.getElementById('userCpf').value = u.CPF;
            document.getElementById('userModal').classList.add('active');
        }
        function closeUserModal() { document.getElementById('userModal').classList.remove('active'); }
        async function saveUser(e) {
            e.preventDefault();
            const btn = e.target.querySelector('[type="submit"]');
            btn.disabled = true; btn.textContent = 'Salvando...';
            const fd = new FormData();
            fd.append('acao', 'editar'); fd.append('id', document.getElementById('userId').value);
            fd.append('nome', document.getElementById('userName').value);
            fd.append('email', document.getElementById('userEmail').value);
            fd.append('cpf', document.getElementById('userCpf').value);
            try {
                const res = await fetch('gerenciar_usuarios.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.sucesso) { showNotification(data.mensagem); closeUserModal(); renderUsers(); } else { stoktechToast(data.mensagem, 'erro'); }
            } catch { stoktechToast('Erro de conexão.', 'erro'); }
            btn.disabled = false; btn.textContent = 'Salvar Alterações';
        }
        async function toggleAdmin(id) {
            const u = users.find(u => u.IDUSUARIO == id);
            if (!u) return;
            const acao = u.TIPO === 'ADMINISTRADOR' ? 'remover admin de' : 'tornar administrador';
            if (!(await stoktechConfirm({ titulo: 'Confirmar ação', mensagem: `Deseja realmente ${acao} ${u.NOME}?`, textoConfirmar: 'Confirmar' }))) return;
            const fd = new FormData(); fd.append('acao', 'toggle_admin'); fd.append('id', id);
            try {
                const res = await fetch('gerenciar_usuarios.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.sucesso) { showNotification(data.mensagem); renderUsers(); } else { stoktechToast(data.mensagem, 'erro'); }
            } catch { stoktechToast('Erro de conexão.', 'erro'); }
        }
        async function deleteUser(id) {
            const u = users.find(u => u.IDUSUARIO == id);
            if (!u) return;
            if (!(await stoktechConfirm({ titulo: 'Excluir usuário', mensagem: `Excluir o usuário ${u.NOME}? Esta ação não pode ser desfeita.`, textoConfirmar: 'Excluir' }))) return;
            const fd = new FormData(); fd.append('acao', 'excluir'); fd.append('id', id);
            try {
                const res = await fetch('gerenciar_usuarios.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.sucesso) { showNotification(data.mensagem); renderUsers(); } else { stoktechToast(data.mensagem, 'erro'); }
            } catch { stoktechToast('Erro de conexão.', 'erro'); }
        }

        // Chat / Empréstimos
        function openChatModal(idEmprestimo) {
            currentChatId = idEmprestimo;
            const emp = emprestimos.find(e => e.IDEMPRESTIMO == idEmprestimo);
            if (!emp) return;

            document.getElementById('chatUserName').textContent = `Empréstimo #${emp.IDEMPRESTIMO} — ${emp.ALUNO_NOME || 'Aluno'}`;
            document.getElementById('chatUserEmail').textContent = emp.ALUNO_EMAIL || '';

            const itens = emp.ITENS.map(item => `• ${item.COMPONENTE_NOME} — Quantidade: ${item.QUANTIDADE}`).join('<br>');
            const msgs = document.getElementById('chatMessagesAdmin');
            msgs.innerHTML = `
                <div class="chat-message system">
                    <strong>Solicitação de Empréstimo</strong><br><br>
                    <strong>Aluno:</strong> ${emp.ALUNO_NOME || '—'}<br>
                    <strong>Data:</strong> ${formatarData(emp.DATA_EMPRESTIMO)}<br>
                    <strong>Status:</strong> ${emp.STATUS || 'PENDENTE'}<br><br>
                    <strong>Itens solicitados:</strong><br>${itens || 'Nenhum item encontrado.'}
                </div>`;
            if (emp.MENSAGENS && emp.MENSAGENS.length > 0) {
                emp.MENSAGENS.forEach(m => {
                    const tipo = m.USUARIO_TIPO === 'ADMINISTRADOR' ? 'manager' : 'user';
                    const autor = m.USUARIO_TIPO === 'ADMINISTRADOR' ? 'Gerenciador' : 'Aluno';

                    msgs.innerHTML += `
                        <div class="chat-message ${tipo}">
                            <strong>${autor}:</strong><br>
                            ${escapeHtml(m.MENSAGEM)}
                            <br><small>${m.DATA_ENVIO}</small>
                        </div>
                    `;
                });
            }
            if (emp.FEEDBACK_DEVOLUCAO) {
            const f = emp.FEEDBACK_DEVOLUCAO;

            msgs.innerHTML += `
                <div class="loan-request">
                    <strong>Checklist de Devolução</strong><br><br>

                    <strong>Houve dano:</strong> ${Number(f.HOUVE_DANO) === 1 ? 'Sim' : 'Não'}<br>
                    <strong>Tipo do dano:</strong> ${escapeHtml(f.TIPO_DANO || '—')}<br>
                    <strong>Gravidade:</strong> ${escapeHtml(f.GRAVIDADE || '—')}<br>
                    <strong>Laudo:</strong><br>
                    ${escapeHtml(f.LAUDO || '—')}<br><br>
                    <strong>Multa:</strong> R$ ${Number(f.MULTA || 0).toFixed(2).replace('.', ',')}<br>
                    <strong>Registrado por:</strong> ${escapeHtml(f.ADM_NOME || 'Administrador')}<br>
                    <strong>Data do registro:</strong> ${f.DATA_REGISTRO || '—'}
                </div>
            `;
        }
            if (emp.STATUS === 'PENDENTE') {
                msgs.innerHTML += `<div class="loan-request"><strong>Solicitação Pendente</strong><div class="loan-actions"><button class="approve-btn" onclick="approveLoan()">Aprovar</button><button class="reject-btn" onclick="rejectLoan()">Recusar</button></div></div>`;
            }
            if (emp.STATUS === 'APROVADO') {
            msgs.innerHTML += `
            <div class="loan-request">
                <strong>Empréstimo Aprovado</strong><br>
                <small>O aluno tem até ${formatarData(emp.DATA_LIMITE_RETIRADA) || '—'} para retirar.</small>

                <div class="loan-actions">
                    <button class="approve-btn" onclick="markWithdrawn()">
                        Marcar como Retirado
                    </button>
                </div>
            </div>
        `;
    }

if (emp.STATUS === 'RETIRADO') {
    msgs.innerHTML += `
        <div class="loan-request">
            <strong>Componente Retirado</strong><br>
            <small>Prazo de devolução: ${formatarData(emp.PRAZO_DEVOLUCAO) || '—'}</small>

            <div class="loan-actions">
                <button class="approve-btn" onclick="markReturned()">
                Marcar como Devolvido
                </button>
            </div>
        </div>
    `;
}

            msgs.scrollTop = msgs.scrollHeight;
            document.getElementById('chatModal').classList.add('active');
        }
        function closeChatModal() { document.getElementById('chatModal').classList.remove('active'); }
        function escapeHtml(text) {
    return String(text ?? '').replace(/[&<>"']/g, function(char) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char];
    });
}
        async function sendAdminMessage() {
    if (currentChatId === null || currentChatId === undefined) return;

    const input = document.getElementById('chatInputAdmin');
    const msg = input.value.trim();

    if (!msg) return;

    const fd = new FormData();
    fd.append('acao', 'enviar_mensagem');
    fd.append('idemprestimo', currentChatId);
    fd.append('mensagem', msg);

    try {
        const res = await fetch('gerenciar_emprestimos.php', {
            method: 'POST',
            body: fd
        });

        const texto = await res.text();

        let data;
        try {
            data = JSON.parse(texto);
        } catch (erroJson) {
            stoktechToast('O PHP não retornou JSON. Resposta: ' + texto.substring(0, 300, 'erro'));
            console.error(texto);
            return;
        }

        if (!data.sucesso) {
            stoktechToast(data.mensagem || 'Erro ao enviar mensagem.', 'erro');
            return;
        }

        input.value = '';

        await carregarEmprestimos();

        openChatModal(currentChatId);

    } catch (e) {
        stoktechToast('Erro de conexão ao enviar mensagem: ' + e.message, 'erro');
        console.error(e);
    }
}
        async function atualizarStatusEmprestimo(status, extras = {}) {
    if (currentChatId === null || currentChatId === undefined) return;

    const fd = new FormData();
    fd.append('acao', 'atualizar_status');
    fd.append('idemprestimo', currentChatId);
    fd.append('status', status);

    Object.keys(extras).forEach(chave => {
        fd.append(chave, extras[chave]);
    });

    try {
        const res = await fetch('gerenciar_emprestimos.php', {
            method: 'POST',
            body: fd
        });

        const texto = await res.text();

        let data;

        try {
            data = JSON.parse(texto);
        } catch (erroJson) {
            stoktechToast('O PHP não retornou JSON. Resposta: ' + texto.substring(0, 300, 'erro'));
            console.error(texto);
            return;
        }

        if (data.sucesso) {
            showNotification(data.mensagem);
            closeChatModal();
            renderChats();
            carregarBadgeEmprestimos();
        } else {
            stoktechToast(data.mensagem, 'erro');
        }

    } catch(e) {
        stoktechToast('Erro de conexão: ' + e.message, 'erro');
        console.error(e);
    }
}
        async function approveLoan() {
            if (!(await stoktechConfirm({ titulo: 'Aprovar empréstimo', mensagem: 'Aprovar esta solicitação de empréstimo?', textoConfirmar: 'Aprovar', neutro: true }))) return;
            atualizarStatusEmprestimo('APROVADO');
        }
        async function rejectLoan() {
            if (!(await stoktechConfirm({ titulo: 'Recusar empréstimo', mensagem: 'Recusar esta solicitação de empréstimo?', textoConfirmar: 'Recusar' }))) return;
            atualizarStatusEmprestimo('RECUSADO');
        }
        async function markWithdrawn() {
    const hoje = new Date();
    hoje.setDate(hoje.getDate() + 7);

    const ano = hoje.getFullYear();
    const mes = String(hoje.getMonth() + 1).padStart(2, '0');
    const dia = String(hoje.getDate()).padStart(2, '0');

    const sugestao = `${ano}-${mes}-${dia}`;

    const prazo = prompt('Informe o prazo de devolução no formato AAAA-MM-DD:', sugestao);

    if (!prazo) return;

    if (!/^\d{4}-\d{2}-\d{2}$/.test(prazo)) {
        stoktechToast('Data inválida. Use o formato AAAA-MM-DD.', 'erro');
        return;
    }

    if (!(await stoktechConfirm({ titulo: 'Confirmar retirada', mensagem: 'Confirmar retirada com prazo de devolução em ' + prazo + '?', textoConfirmar: 'Confirmar', neutro: true }))) {
        return;
    }

    atualizarStatusEmprestimo('RETIRADO', {
        prazo_devolucao: prazo
    });
}
        async function markReturned() {
    if (!(await stoktechConfirm({ titulo: 'Devolução', mensagem: 'Iniciar checklist de devolução?', textoConfirmar: 'Iniciar', neutro: true }))) return;

    const houveDanoTxt = prompt('Houve dano no item? Digite SIM ou NAO:', 'NAO');

    if (houveDanoTxt === null) return;

    const houveDanoNormalizado = houveDanoTxt.trim().toUpperCase();
    const houveDano = houveDanoNormalizado === 'SIM' ? 1 : 0;

    let tipoDano = '';
    let gravidade = '';
    let laudo = '';
    let multa = '0';

    if (houveDano === 1) {
        tipoDano = prompt('Tipo do dano. Ex: cabo quebrado, placa queimada, peça ausente:', '');

        if (tipoDano === null || tipoDano.trim() === '') {
            stoktechToast('Tipo do dano é obrigatório quando há dano.', 'erro');
            return;
        }

        gravidade = prompt('Gravidade do dano. Ex: leve, media ou grave:', 'leve');

        if (gravidade === null || gravidade.trim() === '') {
            stoktechToast('Gravidade é obrigatória quando há dano.', 'erro');
            return;
        }

        laudo = prompt('Laudo/observação da devolução:', '');

        if (laudo === null || laudo.trim() === '') {
            stoktechToast('Laudo é obrigatório quando há dano.', 'erro');
            return;
        }

        multa = prompt('Valor da multa. Use 0 se não houver multa:', '0');

        if (multa === null || multa.trim() === '') {
            multa = '0';
        }

        multa = multa.replace(',', '.');

        if (isNaN(Number(multa))) {
            stoktechToast('Valor da multa inválido.', 'erro');
            return;
        }
    } else {
        laudo = prompt('Observação da devolução. Ex: item devolvido em bom estado:', 'Item devolvido em bom estado.');

        if (laudo === null) return;

        multa = '0';
    }

    if (!(await stoktechConfirm({ titulo: 'Confirmar devolução', mensagem: 'Confirmar devolução e registrar checklist? O estoque será devolvido.', textoConfirmar: 'Confirmar', neutro: true }))) {
        return;
    }

    atualizarStatusEmprestimo('DEVOLVIDO', {
        houve_dano: houveDano,
        tipo_dano: tipoDano.trim(),
        gravidade: gravidade.trim(),
        laudo: laudo.trim(),
        multa: multa
    });
}
        function saveChat() {
            if (currentChatId === null || currentChatId === undefined) return;
            const emp = emprestimos.find(e => e.IDEMPRESTIMO == currentChatId);
            if (!emp) return;
            const now = new Date();
            const filename = `emprestimo_${emp.IDEMPRESTIMO}_${now.toLocaleDateString('pt-BR').replace(/\//g,'-')}.txt`;
            let content = `STOKTECH — LOG DE EMPRÉSTIMO\n${'='.repeat(40)}\nAluno: ${emp.ALUNO_NOME || ''}\nEmail: ${emp.ALUNO_EMAIL || ''}\nStatus: ${emp.STATUS || 'PENDENTE'}\nData: ${formatarData(emp.DATA_EMPRESTIMO)}\n${'='.repeat(40)}\n\nItens:\n`;
            emp.ITENS.forEach(item => { content += `- ${item.COMPONENTE_NOME} — Quantidade: ${item.QUANTIDADE}\n`; });
            content += `\nChecklist de Devolução:\n`;

            if (emp.FEEDBACK_DEVOLUCAO) {
                const f = emp.FEEDBACK_DEVOLUCAO;

                content += `Houve dano: ${Number(f.HOUVE_DANO) === 1 ? 'Sim' : 'Não'}\n`;
                content += `Tipo do dano: ${f.TIPO_DANO || '—'}\n`;
                content += `Gravidade: ${f.GRAVIDADE || '—'}\n`;
                content += `Laudo: ${f.LAUDO || '—'}\n`;
                content += `Multa: R$ ${Number(f.MULTA || 0).toFixed(2).replace('.', ',')}\n`;
                content += `Registrado por: ${f.ADM_NOME || 'Administrador'}\n`;
                content += `Data do registro: ${f.DATA_REGISTRO || '—'}\n`;
            } else {
                content += `Nenhum checklist de devolução registrado.\n`;
            }
            content += `\nMensagens:\n`;
            if (emp.MENSAGENS && emp.MENSAGENS.length > 0) {
                emp.MENSAGENS.forEach(m => {
                    content += `[${m.data}] ${m.autor}: ${m.texto}\n`;
                });
            } else {
                content += `Nenhuma mensagem registrada.\n`;
            }
            logs.push({ filename, userName: emp.ALUNO_NOME || 'Aluno', date: now.toLocaleString('pt-BR'), content });
            const blob = new Blob([content], { type: 'text/plain' });
            const url = URL.createObjectURL(blob); const a = document.createElement('a');
            a.href = url; a.download = filename; a.click(); URL.revokeObjectURL(url);
            showNotification('Log salvo com sucesso');
        }
        function downloadLog(i) {
            const log = logs[i];
            const blob = new Blob([log.content], { type: 'text/plain' });
            const url = URL.createObjectURL(blob); const a = document.createElement('a');
            a.href = url; a.download = log.filename; a.click(); URL.revokeObjectURL(url);
        }
        async function deleteLog(i) {
            if (!(await stoktechConfirm({ titulo: 'Excluir log', mensagem: 'Excluir este log?', textoConfirmar: 'Excluir' }))) return;
            logs.splice(i, 1); showNotification('Log excluído'); loadSection('logs');
        }

        // Profile
        function openProfileModal() {
            document.getElementById('profileName').value = adminProfile.name;
            document.getElementById('profileEmail').value = adminProfile.email;
            document.getElementById('profilePassword').value = '';
            document.getElementById('profileModal').classList.add('active');
        }
        function closeProfileModal() { document.getElementById('profileModal').classList.remove('active'); }
        function saveProfile(e) {
            e.preventDefault();
            adminProfile.name = document.getElementById('profileName').value;
            adminProfile.email = document.getElementById('profileEmail').value;
            updateHeaderProfile();
            showNotification('Perfil atualizado com sucesso');
            closeProfileModal();
        }

        function showNotification(msg) {
            stoktechToast(msg, 'sucesso');
        }
    </script>
    <script src="js/protecao.js"></script>
</body>
</html>
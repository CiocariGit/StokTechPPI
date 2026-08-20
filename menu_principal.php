<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Permite visualizar o catálogo sem estar logado. As ações de escrita
// (solicitar empréstimo, listar meus empréstimos, enviar mensagem) já
// verificam $_SESSION['idusuario'] individualmente e recusam convidados.
$modoConvidado = empty($_SESSION['idusuario']);

if (!$modoConvidado) {
    require_once 'auth.php';
}
require_once 'conexao.php';

$acao = $_GET['acao'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $acao === 'teste_ajax') {
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Conexão com menu_principal.php funcionando.'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $acao === 'listar_meus_emprestimos') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        if (!isset($_SESSION['idusuario'])) {
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Sessão expirada. Faça login novamente.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $pdo->prepare("
        SELECT 
            e.IDEMPRESTIMO,
            e.IDALUNO,
            e.IDADM,
            e.STATUS,
            e.DATA_EMPRESTIMO,
            e.DATA_APROVACAO,
            e.DATA_LIMITE_RETIRADA,
            e.DATA_RETIRADA,
            e.PRAZO_DEVOLUCAO,
            e.DATA_DEVOLUCAO
        FROM EMPRESTIMO e
        WHERE e.IDALUNO = ?
        ORDER BY e.IDEMPRESTIMO DESC
    ");

        $stmt->execute([$_SESSION['idusuario']]);
        $emprestimos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtItens = $pdo->prepare("
            SELECT 
                c.IDEMPRESTIMO,
                c.IDCOMPONENTE,
                c.QUANTIDADE,
                comp.NOME AS COMPONENTE_NOME
            FROM CARRINHO c
            LEFT JOIN COMPONENTE comp ON comp.IDCOMPONENTE = c.IDCOMPONENTE
            WHERE c.IDEMPRESTIMO = ?
        ");
        $stmtMensagens = $pdo->prepare("
        SELECT 
            m.IDMENSAGEM,
            m.IDEMPRESTIMO,
            m.IDUSUARIO,
            m.MENSAGEM,
            m.DATA_ENVIO,
            u.NOME AS USUARIO_NOME,
            u.TIPO AS USUARIO_TIPO
        FROM MENSAGEM_EMPRESTIMO m
        INNER JOIN USUARIO u ON u.IDUSUARIO = m.IDUSUARIO
        WHERE m.IDEMPRESTIMO = ?
        ORDER BY m.DATA_ENVIO ASC, m.IDMENSAGEM ASC
    ");

        foreach ($emprestimos as &$emp) {
    if (!empty($emp['STATUS'])) {
        $emp['STATUS'] = strtoupper($emp['STATUS']);
    } else if (!empty($emp['DATA_DEVOLUCAO'])) {
        $emp['STATUS'] = 'DEVOLVIDO';
    } else if (!empty($emp['DATA_RETIRADA'])) {
        $emp['STATUS'] = 'RETIRADO';
    } else if (!empty($emp['IDADM'])) {
        $emp['STATUS'] = 'RECUSADO';
    } else {
        $emp['STATUS'] = 'PENDENTE';
    }

    $stmtItens->execute([$emp['IDEMPRESTIMO']]);
    $emp['ITENS'] = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

    $stmtMensagens->execute([$emp['IDEMPRESTIMO']]);
    $emp['MENSAGENS'] = $stmtMensagens->fetchAll(PDO::FETCH_ASSOC);
}

        unset($emp);

        echo json_encode([
            'sucesso' => true,
            'emprestimos' => $emprestimos
        ], JSON_UNESCAPED_UNICODE);

        exit;

    } catch (Exception $e) {
        echo json_encode([
            'sucesso' => false,
            'mensagem' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $acao === 'solicitar_emprestimo') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $dados = json_decode(file_get_contents('php://input'), true);

        if (!$dados || !isset($dados['itens']) || count($dados['itens']) === 0) {
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Carrinho vazio.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (!isset($_SESSION['idusuario'])) {
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Sessão expirada. Faça login novamente.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $pdo->beginTransaction();

        $stmtProximoId = $pdo->query("SELECT COALESCE(MAX(IDEMPRESTIMO), -1) + 1 AS PROXIMO_ID FROM EMPRESTIMO");
        $proximoId = (int)$stmtProximoId->fetch(PDO::FETCH_ASSOC)['PROXIMO_ID'];

        $stmtEmprestimo = $pdo->prepare("
            INSERT INTO EMPRESTIMO 
            (IDEMPRESTIMO, IDALUNO, IDADM, DATA_EMPRESTIMO, DATA_RETIRADA, DATA_DEVOLUCAO)
            VALUES (?, ?, NULL, CURDATE(), NULL, NULL)
        ");

        $stmtEmprestimo->execute([
            $proximoId,
            $_SESSION['idusuario']
        ]);

        $stmtCarrinho = $pdo->prepare("
            INSERT INTO CARRINHO 
            (IDEMPRESTIMO, IDCOMPONENTE, QUANTIDADE)
            VALUES (?, ?, ?)
        ");

        foreach ($dados['itens'] as $item) {
            $idComponente = (int)($item['id'] ?? -1);
            $quantidade = (int)($item['quantidade'] ?? 0);

            if ($idComponente < 0) {
                throw new Exception('Componente inválido.');
            }

            if ($quantidade <= 0) {
                $quantidade = 1;
            }

            $stmtCarrinho->execute([
                $proximoId,
                $idComponente,
                $quantidade
            ]);
        }

        $pdo->commit();

        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Solicitação enviada com sucesso.',
            'idemprestimo' => $proximoId
        ], JSON_UNESCAPED_UNICODE);

        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Erro ao salvar empréstimo: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $acao === 'enviar_mensagem_emprestimo') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        if (!isset($_SESSION['idusuario'])) {
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Sessão expirada. Faça login novamente.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $dados = json_decode(file_get_contents('php://input'), true);

        $idEmprestimo = isset($dados['idemprestimo']) ? (int)$dados['idemprestimo'] : -1;
        $mensagem = trim($dados['mensagem'] ?? '');

        if ($idEmprestimo < 0 || $mensagem === '') {
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Dados inválidos para enviar mensagem.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmtVerifica = $pdo->prepare("
            SELECT IDEMPRESTIMO 
            FROM EMPRESTIMO 
            WHERE IDEMPRESTIMO = ? AND IDALUNO = ?
        ");

        $stmtVerifica->execute([
            $idEmprestimo,
            $_SESSION['idusuario']
        ]);

        if (!$stmtVerifica->fetch()) {
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Empréstimo não encontrado para este usuário.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO MENSAGEM_EMPRESTIMO
            (IDEMPRESTIMO, IDUSUARIO, MENSAGEM)
            VALUES (?, ?, ?)
        ");

        $stmt->execute([
            $idEmprestimo,
            $_SESSION['idusuario'],
            $mensagem
        ]);

        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Mensagem enviada com sucesso.'
        ], JSON_UNESCAPED_UNICODE);

        exit;

    } catch (Exception $e) {
        echo json_encode([
            'sucesso' => false,
            'mensagem' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}


if (!$modoConvidado && (empty($_SESSION['email']) || !isset($_SESSION['foto_perfil']))) {
    $stmt = $pdo->prepare("SELECT EMAIL, FOTO_PERFIL FROM USUARIO WHERE IDUSUARIO = ?");
    $stmt->execute([$_SESSION['idusuario']]);
    $row = $stmt->fetch();
    if ($row) {
        $_SESSION['email']       = $row['EMAIL'];
        $_SESSION['foto_perfil'] = $row['FOTO_PERFIL'];
    }
}

$categoriasAluno = [];
$produtosAluno   = [];

try {
    $stmtCategorias = $pdo->query("SELECT IDCATEGORIA, NOME, ICONE FROM CATEGORIA ORDER BY NOME");
    $categoriasAluno = $stmtCategorias->fetchAll();

    $stmtComponentes = $pdo->query("
        SELECT c.IDCOMPONENTE, c.NOME, c.IMAGEM, c.DESCRICAO_CURTA,
               c.DESCRICAO_COMPLETA, c.ESPECIFICACOES, c.QUANTIDADE,
               c.IDCATEGORIA, cat.NOME AS CATEGORIA_NOME, cat.ICONE AS CATEGORIA_ICONE
        FROM COMPONENTE c
        LEFT JOIN CATEGORIA cat ON c.IDCATEGORIA = cat.IDCATEGORIA
        ORDER BY c.NOME
    ");

    foreach ($stmtComponentes->fetchAll() as $comp) {
        $specs = [];
        if (!empty($comp['ESPECIFICACOES'])) {
            $json = json_decode($comp['ESPECIFICACOES'], true);
            if (is_array($json)) {
                $specs = $json;
            }
        }

        $produtosAluno[] = [
            'id'              => (int)$comp['IDCOMPONENTE'],
            'name'            => $comp['NOME'] ?? 'Componente',
            'category'        => (string)($comp['IDCATEGORIA'] ?? ''),
            'categoryName'    => $comp['CATEGORIA_NOME'] ?? 'Sem categoria',
            'icon'            => $comp['CATEGORIA_ICONE'] ?: '📦',
            'image'           => $comp['IMAGEM'] ?: '',
            'description'     => $comp['DESCRICAO_CURTA'] ?? '',
            'fullDescription' => $comp['DESCRICAO_COMPLETA'] ?? '',
            'stock'           => (int)($comp['QUANTIDADE'] ?? 0),
            'specs'           => $specs
        ];
    }
} catch (Throwable $e) {
    $categoriasAluno = [];
    $produtosAluno   = [];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StokTech — Componentes</title>
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
    --surface-3: #202028;
    --border: #252530;
    --text: #f0f0f4;
    --text-muted: #7a7a90;
    --text-subtle: #4a4a60;
    --success: #22c55e;
    --warning: #f59e0b;
    --danger: #ef4444;
    --radius: 4px;
    --sidebar-w: 260px;
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
    background: var(--bg);
    color: var(--text);
    transition: background 0.3s, color 0.3s;
    overflow-x: hidden;
}

/* ── HEADER ── */
.header {
    height: var(--header-h);
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    padding: 0 1.25rem;
    gap: 1rem;
    position: sticky;
    top: 0;
    z-index: 200;
}
.sidebar-toggle {
    width: 36px;
    height: 36px;
    background: transparent;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    cursor: pointer;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 5px;
    flex-shrink: 0;
    transition: border-color 0.2s, background 0.2s;
}
.sidebar-toggle:hover { border-color: var(--primary); background: var(--primary-subtle); }
.sidebar-toggle span {
    display: block;
    width: 16px;
    height: 1.5px;
    background: var(--text-muted);
    transition: all 0.3s;
    border-radius: 1px;
}
.logo-img { height: 28px; object-fit: contain; flex-shrink: 0; }
.search-bar {
    flex: 1;
    max-width: 420px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 0 0.75rem;
    transition: border-color 0.2s;
}
.search-bar:focus-within { border-color: var(--primary); }
.search-icon { color: var(--text-subtle); font-size: 0.85rem; flex-shrink: 0; }
.search-bar input {
    flex: 1;
    background: transparent;
    border: none;
    outline: none;
    color: var(--text);
    font-size: 0.88rem;
    font-family: 'IBM Plex Sans', sans-serif;
    padding: 0.55rem 0;
}
.search-bar input::placeholder { color: var(--text-subtle); }
.header-actions { margin-left: auto; display: flex; align-items: center; gap: 0.5rem; }
.hdr-btn {
    width: 36px;
    height: 36px;
    background: transparent;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    color: var(--text-muted);
    transition: all 0.2s;
    position: relative;
}
.hdr-btn:hover { border-color: var(--primary); color: var(--primary-light); background: var(--primary-subtle); }
.cart-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: var(--primary);
    color: white;
    font-size: 0.65rem;
    font-weight: 700;
    font-family: 'IBM Plex Mono', monospace;
    width: 17px;
    height: 17px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.logout-form { margin: 0; }

/* User info in header */
.hdr-user {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    cursor: pointer;
    padding: 0.3rem 0.6rem;
    border-radius: var(--radius);
    border: 1px solid transparent;
    transition: all 0.2s;
    position: relative;
}
.hdr-user:hover { border-color: var(--border); background: var(--surface-2); }
.hdr-user:hover .edit-pencil { opacity: 1; }
.hdr-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--primary-subtle);
    border: 2px solid var(--primary);
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 0.9rem;
    color: var(--primary-light);
}
.hdr-avatar img { width: 100%; height: 100%; object-fit: cover; }
.hdr-user-info { display: flex; flex-direction: column; line-height: 1.2; }
.hdr-user-name {
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--text);
    white-space: nowrap;
    max-width: 130px;
    overflow: hidden;
    text-overflow: ellipsis;
}
.hdr-user-email {
    font-size: 0.72rem;
    color: var(--text-muted);
    white-space: nowrap;
    max-width: 130px;
    overflow: hidden;
    text-overflow: ellipsis;
}
.edit-pencil {
    opacity: 0;
    transition: opacity 0.2s;
    font-size: 0.75rem;
    color: var(--primary-light);
    margin-left: 0.2rem;
}

/* ── LAYOUT ── */
.main-container { display: flex; }

/* ── SIDEBAR ── */
.sidebar {
    width: var(--sidebar-w);
    background: var(--surface);
    border-right: 1px solid var(--border);
    min-height: calc(100vh - var(--header-h));
    display: flex;
    flex-direction: column;
    transition: width 0.3s ease, transform 0.3s ease;
    overflow: hidden;
    flex-shrink: 0;
}
.sidebar.collapsed { width: 0; border-right: none; }
.sidebar-inner { width: var(--sidebar-w); padding: 1.5rem 0; overflow: hidden; }
.sidebar-section-label {
    padding: 0 1.25rem 0.6rem;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--text-subtle);
    font-family: 'IBM Plex Mono', monospace;
}
.category-list { list-style: none; padding: 0; margin: 0 0 1.5rem 0; }
.category-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.65rem 1.25rem;
    cursor: pointer;
    color: var(--text-muted);
    font-size: 0.88rem;
    font-weight: 400;
    transition: all 0.15s;
    border-left: 2px solid transparent;
    white-space: nowrap;
}
.category-item svg { flex-shrink: 0; opacity: 0.6; }
.category-item:hover { background: var(--surface-2); color: var(--text); border-left-color: var(--border); }
.category-item:hover svg { opacity: 1; }
.category-item.active {
    background: var(--primary-subtle);
    color: var(--primary-light);
    border-left-color: var(--primary);
    font-weight: 500;
}
.category-item.active svg { opacity: 1; color: var(--primary-light); }

/* ── CONTENT ── */
.content-area { flex: 1; padding: 2rem; min-width: 0; }
.section-header {
    display: flex;
    align-items: baseline;
    gap: 0.75rem;
    margin-bottom: 1.75rem;
}
.section-title { font-size: 1.4rem; font-weight: 600; letter-spacing: -0.02em; }
.section-count {
    font-size: 0.8rem;
    color: var(--text-muted);
    font-family: 'IBM Plex Mono', monospace;
    background: var(--surface-2);
    border: 1px solid var(--border);
    padding: 0.2rem 0.5rem;
    border-radius: 2px;
}

/* ── PRODUCTS GRID ── */
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 1.25rem;
}
.product-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    transition: border-color 0.2s, box-shadow 0.2s, transform 0.15s;
}
.product-card:hover {
    border-color: var(--primary);
    box-shadow: 0 4px 20px rgba(107,31,173,0.15);
    transform: translateY(-2px);
}
.product-image {
    width: 100%;
    height: 160px;
    background: var(--surface-2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3.5rem;
    border-bottom: 1px solid var(--border);
    overflow: hidden;
}
.product-image img,
.cart-item-icon img,
.details-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.product-info { padding: 1.25rem; }
.product-name { font-size: 0.95rem; font-weight: 600; margin-bottom: 0.35rem; }
.product-description { color: var(--text-muted); font-size: 0.82rem; margin-bottom: 0.85rem; line-height: 1.5; }
.product-stock {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.85rem;
    font-size: 0.8rem;
    color: var(--text-muted);
}
.stock-ok { color: var(--success); font-weight: 600; font-family: 'IBM Plex Mono', monospace; }
.stock-low { color: var(--warning); font-weight: 600; font-family: 'IBM Plex Mono', monospace; }
.stock-out { color: var(--danger); font-weight: 600; font-family: 'IBM Plex Mono', monospace; }
.product-actions { display: flex; flex-direction: column; gap: 0.5rem; }
.quantity-control {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    background: var(--surface-2);
    padding: 0.35rem;
    border-radius: var(--radius);
    border: 1px solid var(--border);
}
.qty-btn {
    width: 28px;
    height: 28px;
    background: var(--surface-3);
    border: 1px solid var(--border);
    border-radius: 2px;
    cursor: pointer;
    font-size: 1rem;
    color: var(--text);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s;
}
.qty-btn:hover:not(:disabled) { background: var(--primary); border-color: var(--primary); color: white; }
.qty-btn:disabled { opacity: 0.3; cursor: not-allowed; }
.cart-item-remove { margin-left: 0.4rem; color: var(--text-muted); }
.cart-item-remove:hover { background: #ef4444; border-color: #ef4444; color: #fff; }
.qty-input {
    flex: 1;
    text-align: center;
    font-weight: 600;
    font-size: 0.9rem;
    font-family: 'IBM Plex Mono', monospace;
    background: transparent;
    border: none;
    color: var(--text);
    outline: none;
    min-width: 0;
}
.details-btn {
    width: 100%;
    padding: 0.6rem;
    background: transparent;
    color: var(--text-muted);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    font-size: 0.82rem;
    font-family: 'IBM Plex Sans', sans-serif;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    letter-spacing: 0.02em;
}
.details-btn:hover { border-color: var(--primary); color: var(--primary-light); background: var(--primary-subtle); }

/* ── MODALS ── */
.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(4px);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.modal.active { display: flex; animation: fadeModal 0.2s ease; }
@keyframes fadeModal { from { opacity: 0; } to { opacity: 1; } }
.modal-content {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    width: 100%;
    max-width: 580px;
    max-height: 85vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    animation: slideModal 0.25s ease;
}
.modal-large { max-width: 760px; }
.modal-chat { max-width: 480px; max-height: 560px; }
@keyframes slideModal {
    from { transform: translateY(16px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
.modal-header {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    flex-shrink: 0;
}
.modal-header h2 { font-size: 1.05rem; font-weight: 600; }
.close-modal {
    background: transparent;
    border: none;
    font-size: 1.3rem;
    cursor: pointer;
    color: var(--text-muted);
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 2px;
    transition: all 0.15s;
    line-height: 1;
    padding: 0;
}
.close-modal:hover { color: var(--danger); background: rgba(239,68,68,0.1); }

/* Cart */
.cart-items { padding: 1.25rem 1.5rem; overflow-y: auto; flex: 1; }
.cart-item {
    display: flex;
    gap: 0.85rem;
    padding: 0.85rem;
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    margin-bottom: 0.75rem;
    align-items: center;
}
.cart-item-icon { font-size: 2rem; flex-shrink: 0; width: 42px; height: 42px; display: flex; align-items: center; justify-content: center; overflow: hidden; border-radius: var(--radius); }
.cart-item-icon img { width: 100%; height: 100%; object-fit: cover; }
.cart-item-info { flex: 1; min-width: 0; }
.cart-item-name { font-weight: 600; font-size: 0.88rem; margin-bottom: 0.2rem; }
.cart-item-quantity { color: var(--text-muted); font-size: 0.8rem; font-family: 'IBM Plex Mono', monospace; }
.cart-item-controls { display: flex; align-items: center; gap: 0.4rem; }
.empty-cart {
    text-align: center;
    padding: 3rem;
    color: var(--text-muted);
    font-size: 0.9rem;
}
.cart-footer {
    padding: 1.25rem 1.5rem;
    border-top: 1px solid var(--border);
    display: flex;
    gap: 0.75rem;
    flex-shrink: 0;
}
.cart-footer button {
    flex: 1;
    padding: 0.75rem;
    border-radius: var(--radius);
    font-weight: 600;
    cursor: pointer;
    font-family: 'IBM Plex Sans', sans-serif;
    font-size: 0.88rem;
    transition: all 0.2s;
}
.clear-cart-btn {
    background: transparent;
    color: var(--text-muted);
    border: 1px solid var(--border);
}
.clear-cart-btn:hover { border-color: var(--danger); color: var(--danger); background: rgba(239,68,68,0.08); }
.request-btn { background: var(--primary); color: white; border: none; }
.request-btn:hover { background: var(--primary-light); }

/* Details */
.details-content { padding: 1.5rem; overflow-y: auto; flex: 1; }
.details-header { display: flex; gap: 1.5rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
.details-image {
    font-size: 5rem;
    background: var(--surface-2);
    padding: 1.5rem;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.details-image img { width: 130px; height: 130px; object-fit: cover; border-radius: var(--radius); }
.details-info { flex: 1; min-width: 200px; }
.details-info h2 { font-size: 1.3rem; margin-bottom: 0.75rem; }
.details-info p { color: var(--text-muted); line-height: 1.7; font-size: 0.88rem; }
.details-specs { margin-top: 1.5rem; }
.details-specs h3 { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted); margin-bottom: 0.75rem; }
.spec-item {
    display: flex;
    justify-content: space-between;
    padding: 0.65rem 0.75rem;
    background: var(--surface-2);
    border: 1px solid var(--border);
    margin-bottom: 2px;
    font-size: 0.85rem;
}
.spec-item strong { color: var(--text-muted); font-weight: 500; }
.spec-item span { font-family: 'IBM Plex Mono', monospace; font-size: 0.82rem; }

/* Chat */
.chat-messages { padding: 1.25rem; overflow-y: auto; flex: 1; background: var(--bg); }
.chat-message { padding: 0.75rem 1rem; margin-bottom: 0.6rem; border-radius: var(--radius); font-size: 0.85rem; line-height: 1.6; }
.chat-message.system { background: var(--primary-subtle); border: 1px solid rgba(107,31,173,0.2); color: var(--text); }
.chat-message.user { background: var(--surface); border: 1px solid var(--border); margin-left: 15%; }
.chat-message.manager { background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.2); margin-right: 15%; }
.chat-input-container {
    padding: 1rem;
    border-top: 1px solid var(--border);
    display: flex;
    gap: 0.5rem;
    flex-shrink: 0;
}
.chat-input-container input {
    flex: 1;
    padding: 0.65rem 0.85rem;
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    color: var(--text);
    font-family: 'IBM Plex Sans', sans-serif;
    font-size: 0.88rem;
    outline: none;
    transition: border-color 0.2s;
}
.chat-input-container input:focus { border-color: var(--primary); }
.chat-input-container button {
    padding: 0.65rem 1.25rem;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: var(--radius);
    cursor: pointer;
    font-weight: 600;
    font-family: 'IBM Plex Sans', sans-serif;
    font-size: 0.85rem;
    transition: background 0.2s;
}
.chat-input-container button:hover { background: var(--primary-light); }

/* Chat list */
.chat-list { padding: 1.25rem; overflow-y: auto; flex: 1; }
.chat-list-item {
    background: var(--surface-2);
    padding: 1rem;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    margin-bottom: 0.6rem;
    cursor: pointer;
    transition: all 0.15s;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.chat-list-item:hover { border-color: var(--primary); background: var(--primary-subtle); }
.chat-list-title { font-weight: 600; font-size: 0.88rem; margin-bottom: 0.25rem; }
.chat-list-preview { color: var(--text-muted); font-size: 0.8rem; }
.chat-status {
    padding: 0.25rem 0.6rem;
    border-radius: 2px;
    font-size: 0.75rem;
    font-weight: 600;
    font-family: 'IBM Plex Mono', monospace;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.status-pendente { background: rgba(245,158,11,0.15); color: var(--warning); }
.status-aceito { background: rgba(34,197,94,0.12); color: var(--success); }
.status-recusado { background: rgba(239,68,68,0.12); color: var(--danger); }
.status-finalizado { background: var(--surface-3); color: var(--text-muted); }
.empty-chat-list { text-align: center; padding: 3rem; color: var(--text-muted); font-size: 0.88rem; }
.back-btn {
    background: transparent;
    color: var(--text-muted);
    border: 1px solid var(--border);
    padding: 0.4rem 0.75rem;
    border-radius: var(--radius);
    cursor: pointer;
    font-size: 0.82rem;
    font-family: 'IBM Plex Sans', sans-serif;
    transition: all 0.2s;
}
.back-btn:hover { border-color: var(--primary); color: var(--primary-light); }

/* Edit Profile Modal */
.edit-profile-content { padding: 1.5rem; overflow-y: auto; }
.profile-image-edit { display: flex; flex-direction: column; align-items: center; margin-bottom: 1.75rem; gap: 0.75rem; }
.profile-preview {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: var(--surface-2);
    border: 2px solid var(--primary);
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: var(--primary-light);
}
.upload-btn {
    background: transparent;
    border: 1px solid var(--border);
    color: var(--text-muted);
    padding: 0.55rem 1.25rem;
    border-radius: var(--radius);
    cursor: pointer;
    font-size: 0.82rem;
    font-family: 'IBM Plex Sans', sans-serif;
    transition: all 0.2s;
    display: inline-block;
    white-space: nowrap;
}
.upload-btn:hover { border-color: var(--primary); color: var(--primary-light); background: var(--primary-subtle); }
.form-group { margin-bottom: 1.25rem; }
.form-group label {
    display: block;
    font-size: 0.78rem;
    font-weight: 500;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 0.45rem;
}
.form-input {
    width: 100%;
    padding: 0.75rem 0.85rem;
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    color: var(--text);
    font-size: 0.9rem;
    font-family: 'IBM Plex Sans', sans-serif;
    outline: none;
    transition: border-color 0.2s;
}
.form-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(107,31,173,0.12); }
.profile-actions { display: flex; gap: 0.75rem; margin-top: 1.5rem; }
.profile-actions button {
    flex: 1;
    padding: 0.8rem;
    border-radius: var(--radius);
    font-weight: 600;
    cursor: pointer;
    font-family: 'IBM Plex Sans', sans-serif;
    font-size: 0.88rem;
    transition: all 0.2s;
}
.cancel-btn {
    background: transparent;
    color: var(--text-muted);
    border: 1px solid var(--border);
}
.cancel-btn:hover { border-color: var(--danger); color: var(--danger); background: rgba(239,68,68,0.08); }
.save-btn { background: var(--primary); color: white; border: none; }
.save-btn:hover { background: var(--primary-light); }

/* Notification */
.notification-toast {
    position: fixed;
    top: 72px;
    right: 1.25rem;
    background: var(--surface);
    border: 1px solid var(--primary);
    border-left: 3px solid var(--primary);
    color: var(--text);
    padding: 0.75rem 1rem;
    border-radius: var(--radius);
    font-size: 0.85rem;
    font-weight: 500;
    z-index: 9999;
    max-width: 280px;
    animation: toastIn 0.2s ease;
    box-shadow: 0 4px 16px rgba(0,0,0,0.2);
}
.status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-badge.pendente {
    color: #f59e0b;
    background: rgba(245, 158, 11, 0.12);
    border: 1px solid rgba(245, 158, 11, 0.35);
}

.status-badge.aprovado {
    color: #22c55e;
    background: rgba(34, 197, 94, 0.12);
    border: 1px solid rgba(34, 197, 94, 0.35);
}

.status-badge.recusado {
    color: #ef4444;
    background: rgba(239, 68, 68, 0.12);
    border: 1px solid rgba(239, 68, 68, 0.35);
}

.status-badge.devolvido {
    color: #60a5fa;
    background: rgba(96, 165, 250, 0.12);
    border: 1px solid rgba(96, 165, 250, 0.35);
}
.status-badge.retirado {
    color: #22c55e;
    background: rgba(34, 197, 94, 0.12);
    border: 1px solid rgba(34, 197, 94, 0.35);
}

.status-badge.cancelado {
    color: #9ca3af;
    background: rgba(156, 163, 175, 0.12);
    border: 1px solid rgba(156, 163, 175, 0.35);
}
.chat-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 18px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--bg-card);
    cursor: pointer;
    transition: 0.2s ease;
}

.chat-item:hover {
    border-color: var(--primary);
    background: rgba(139, 92, 246, 0.08);
}

.chat-info strong {
    display: block;
    color: var(--text);
    font-size: 15px;
    margin-bottom: 4px;
}

.chat-info p {
    margin: 0;
    color: var(--text-secondary);
    font-size: 13px;
}

@keyframes toastIn { from { opacity: 0; transform: translateX(10px); } to { opacity: 1; transform: translateX(0); } }

/* Mobile overlay */
.sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 150;
}
.sidebar-overlay.active { display: block; }

/* Responsive */
@media (max-width: 768px) {
    :root { --sidebar-w: 240px; }
    .sidebar {
        position: fixed;
        left: 0;
        top: var(--header-h);
        height: calc(100vh - var(--header-h));
        z-index: 160;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
        width: var(--sidebar-w) !important;
    }
    .sidebar.mobile-open { transform: translateX(0); }
    .sidebar.collapsed { transform: translateX(-100%); width: var(--sidebar-w) !important; border-right: 1px solid var(--border); }
    .content-area { padding: 1.25rem; }
    .products-grid { grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 0.85rem; }
    .hdr-user-info { display: none; }
    .search-bar { max-width: 200px; }
}
@media (max-width: 480px) {
    .products-grid { grid-template-columns: 1fr; }
    .search-bar { max-width: 140px; }
}
    </style>
</head>
<body>
    <!-- HEADER -->
    <header class="header">
        <button class="sidebar-toggle" id="sidebarToggle" title="Menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <img class="logo-img" src="img/logo_stoktech.png" alt="StokTech">
        <div class="search-bar">
            <span class="search-icon">&#9906;</span>
            <input type="text" id="searchInput" placeholder="Buscar componentes...">
        </div>
        <div class="header-actions">
            <?php if ($modoConvidado): ?>
            <span class="hdr-btn" style="width:auto; padding:0 0.75rem; font-size:0.78rem; color:var(--text-muted); cursor:default;">Modo visitante</span>
            <button class="hdr-btn" id="themeToggle" title="Alternar tema">&#9790;</button>
            <a class="hdr-btn" href="index.php" title="Fazer login" style="width:auto; padding:0 0.9rem; text-decoration:none; font-size:0.82rem; font-weight:600; color:var(--primary-light);">Entrar</a>
            <?php else: ?>
            <?php if (($_SESSION['tipo'] ?? '') === 'ADMINISTRADOR'): ?>
            <a class="hdr-btn" href="stoktech_admin.php" title="Voltar para visão de administrador" style="text-decoration:none; display:flex; align-items:center; justify-content:center;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 3 7v6c0 5 3.8 8.7 9 9 5.2-.3 9-4 9-9V7l-9-5Z"/><path d="M9.5 12.5 11 14l3.5-3.5"/></svg>
            </a>
            <?php endif; ?>
            <button class="hdr-btn" id="themeToggle" title="Alternar tema">&#9790;</button>
            <button class="hdr-btn" id="chatBtn" title="Chats">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
            </button>
            <button class="hdr-btn" id="cartBtn" title="Carrinho">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                <span class="cart-badge" id="cartCount">0</span>
            </button>
            <div class="hdr-user" id="hdrUser" title="Editar perfil">
                <div class="hdr-avatar" id="headerAvatar">&#9751;</div>
                <div class="hdr-user-info">
                    <span class="hdr-user-name" id="headerUserName">Usuário</span>
                    <span class="hdr-user-email" id="headerUserEmail"><?= htmlspecialchars($_SESSION['email'] ?? '') ?></span>
                </div>
                <span class="edit-pencil">&#9998;</span>
            </div>
            <form class="logout-form" action="sairdacontasuperlegal.php" method="post">
                <button class="hdr-btn" title="Sair">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </header>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="main-container">
        <!-- SIDEBAR -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-inner">
                <div class="sidebar-section-label">Categorias</div>
                <ul class="category-list">
                    <li class="category-item active" data-category="todos">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="7" height="7"></rect><rect x="15" y="3" width="7" height="7"></rect><rect x="2" y="14" width="7" height="7"></rect><rect x="15" y="14" width="7" height="7"></rect></svg>
                        Todos os Itens
                    </li>
                    <?php foreach ($categoriasAluno as $cat): ?>
                        <li class="category-item" data-category="<?= htmlspecialchars((string)$cat['IDCATEGORIA']) ?>">
                            <span style="width:14px;height:14px;display:flex;align-items:center;justify-content:center;opacity:0.8;font-size:0.9rem;">
                                <?= htmlspecialchars($cat['ICONE'] ?: '📦') ?>
                            </span>
                            <?= htmlspecialchars($cat['NOME'] ?? 'Categoria') ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="content-area">
            <div class="section-header">
                <h2 class="section-title">Componentes</h2>
                <span class="section-count" id="productCount">0 itens</span>
            </div>
            <div class="products-grid" id="productsGrid"></div>
        </main>
    </div>

    <!-- CART MODAL -->
    <div class="modal" id="cartModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Carrinho de Empréstimo</h2>
                <button class="close-modal" id="closeCart">&times;</button>
            </div>
            <div class="cart-items" id="cartItems"></div>
            <div class="cart-footer">
                <button class="clear-cart-btn" id="clearCart">Limpar</button>
                <button class="request-btn" id="requestItems">Solicitar Empréstimo</button>
            </div>
        </div>
    </div>

    <!-- DETAILS MODAL -->
    <div class="modal" id="detailsModal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h2>Detalhes do Componente</h2>
                <button class="close-modal" id="closeDetails">&times;</button>
            </div>
            <div class="details-content" id="detailsContent"></div>
        </div>
    </div>

    <!-- CHAT LIST MODAL -->
    <div class="modal" id="chatListModal">
        <div class="modal-content modal-chat">
            <div class="modal-header">
                <h2>Meus Empréstimos</h2>
                <button class="close-modal" id="closeChatList">&times;</button>
            </div>
            <div class="chat-list" id="chatList"></div>
        </div>
    </div>

    <!-- CHAT MODAL -->
    <div class="modal" id="chatModal">
        <div class="modal-content modal-chat">
            <div class="modal-header">
                <button class="back-btn" id="backToList">&#8592; Voltar</button>
                <h2 id="chatTitle">Empréstimo</h2>
                <button class="close-modal" id="closeChat">&times;</button>
            </div>
            <div class="chat-messages" id="chatMessages"></div>
            <div class="chat-input-container">
                <input type="text" id="chatInput" placeholder="Mensagem...">
                <button id="sendMessage">Enviar</button>
            </div>
        </div>
    </div>

    <!-- EDIT PROFILE MODAL -->
    <div class="modal" id="editProfileModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Editar Perfil</h2>
                <button class="close-modal" id="closeEditProfile">&times;</button>
            </div>
            <div class="edit-profile-content">
                <div class="profile-image-edit">
                    <div class="profile-preview" id="profilePreview">&#9751;</div>
                    <label for="profileImageInput" class="upload-btn">Alterar Foto</label>
                    <input type="file" id="profileImageInput" accept="image/*" style="display:none;">
                </div>
                <div class="form-group">
                    <label>Nome</label>
                    <input type="text" id="editName" class="form-input" placeholder="Seu nome">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="editEmail" class="form-input" placeholder="seu@email.com" value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Nova Senha (deixe em branco para manter)</label>
                    <input type="password" id="editPassword" class="form-input" placeholder="••••••••">
                </div>
                <div class="form-group">
                    <label>Confirmar Nova Senha</label>
                    <input type="password" id="confirmPassword" class="form-input" placeholder="••••••••">
                </div>
                <div class="profile-actions">
                    <button class="cancel-btn" id="cancelEdit">Cancelar</button>
                    <button class="save-btn" id="saveProfile">Salvar Alterações</button>
                </div>
            </div>
        </div>
    </div>

<script>
const products = <?= json_encode($produtosAluno, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

let cart = [];
let currentCategory = 'todos';
let productQuantities = {};
let chats = [];
let currentChatId = null;
let userProfile = {
    name: <?= json_encode($_SESSION['nome'] ?? 'Usuário') ?>,
    email: <?= json_encode($_SESSION['email'] ?? '') ?>,
    profileImage: <?= json_encode($_SESSION['foto_perfil'] ?? null) ?>
};
const CART_STORAGE_KEY = 'stokCart_' + <?= json_encode($_SESSION['idusuario'] ?? 0) ?>;
const MODO_CONVIDADO = <?= json_encode($modoConvidado) ?>;
products.forEach(p => productQuantities[p.id] = 0);

// ── INIT ──
document.addEventListener('DOMContentLoaded', () => {
    loadTheme();
    updateProfileDisplay();
    loadChats();
    loadCart();
    renderProducts();
    setupEventListeners();
});

function setupEventListeners() {
    document.getElementById('sidebarToggle').addEventListener('click', toggleSidebar);
    document.getElementById('sidebarOverlay').addEventListener('click', closeSidebarMobile);
    document.getElementById('themeToggle').addEventListener('click', toggleTheme);
    document.querySelectorAll('.category-item').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelectorAll('.category-item').forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            currentCategory = this.dataset.category;
            renderProducts();
            if (window.innerWidth <= 768) closeSidebarMobile();
        });
    });
    document.getElementById('cartBtn')?.addEventListener('click', openCart);
    document.getElementById('closeCart').addEventListener('click', closeCart);
    document.getElementById('clearCart').addEventListener('click', clearCart);
    document.getElementById('requestItems').addEventListener('click', requestItems);
    document.getElementById('chatBtn')?.addEventListener('click', openChatList);
    document.getElementById('closeChatList').addEventListener('click', closeChatList);
    document.getElementById('closeChat').addEventListener('click', closeChat);
    document.getElementById('backToList').addEventListener('click', backToChatList);
    document.getElementById('sendMessage').addEventListener('click', sendChatMessage);
    document.getElementById('chatInput').addEventListener('keypress', e => { if(e.key === 'Enter') sendChatMessage(); });
    document.getElementById('hdrUser')?.addEventListener('click', openEditProfile);
    document.getElementById('closeEditProfile').addEventListener('click', closeEditProfile);
    document.getElementById('cancelEdit').addEventListener('click', closeEditProfile);
    document.getElementById('saveProfile').addEventListener('click', saveProfile);
    document.getElementById('profileImageInput').addEventListener('change', handleProfileImageUpload);
    document.getElementById('closeDetails').addEventListener('click', closeDetails);
    document.getElementById('searchInput').addEventListener('input', e => filterProducts(e.target.value));
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) { if(e.target === this) this.classList.remove('active'); });
    });

    // Trava o scroll da página de fundo enquanto qualquer painel/modal estiver aberto
    const observadorModais = new MutationObserver(function () {
        const algumAberto = !!document.querySelector('.modal.active');
        document.body.classList.toggle('stoktech-sem-scroll', algumAberto);
    });
    document.querySelectorAll('.modal').forEach(modal => {
        observadorModais.observe(modal, { attributes: true, attributeFilter: ['class'] });
    });

    // Impede que o scroll do mouse sobre a sidebar role a página
    const sidebarEl = document.getElementById('sidebar');
    if (sidebarEl) {
        sidebarEl.addEventListener('wheel', function (e) { e.preventDefault(); }, { passive: false });
        sidebarEl.addEventListener('touchmove', function (e) { e.preventDefault(); }, { passive: false });
    }
}

// ── SIDEBAR ──
function toggleSidebar() {
    if (window.innerWidth <= 768) {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        sidebar.classList.toggle('mobile-open');
        overlay.classList.toggle('active');
    } else {
        document.getElementById('sidebar').classList.toggle('collapsed');
    }
}
function closeSidebarMobile() {
    document.getElementById('sidebar').classList.remove('mobile-open');
    document.getElementById('sidebarOverlay').classList.remove('active');
}

// ── THEME ──
function loadTheme() {
    const t = localStorage.getItem('theme');
    if (t === 'light') {
        document.body.classList.add('light-mode');
        document.getElementById('themeToggle').innerHTML = '&#9728;';
    }
}
function toggleTheme() {
    document.body.classList.toggle('light-mode');
    const isLight = document.body.classList.contains('light-mode');
    localStorage.setItem('theme', isLight ? 'light' : 'dark');
    document.getElementById('themeToggle').innerHTML = isLight ? '&#9728;' : '&#9790;';
}

// ── PRODUCTS ──
function escapeHtml(text) {
    return String(text ?? '').replace(/[&<>"']/g, function(char) {
        return ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;' })[char];
    });
}

function formatarDataEmprestimo(data) {
    if (!data) return '';
    return String(data).split('-').reverse().join('/');
}

function productImage(product) {
    if (product.image && String(product.image).trim() !== '') {
        return `<img src="${escapeHtml(product.image)}" alt="${escapeHtml(product.name)}">`;
    }
    return escapeHtml(product.icon || '📦');
}

function saveCart() {
    const dados = cart.map(item => ({ id: item.id, quantity: item.quantity }));
    localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(dados));
}

function loadCart() {
    const salvo = localStorage.getItem(CART_STORAGE_KEY);
    if (!salvo) return;
    try {
        const dados = JSON.parse(salvo);
        cart = [];
        products.forEach(p => productQuantities[p.id] = 0);
        dados.forEach(itemSalvo => {
            const product = products.find(p => p.id == itemSalvo.id);
            if (!product) return;
            const qty = Math.max(0, Math.min(parseInt(itemSalvo.quantity) || 0, product.stock));
            if (qty > 0) {
                productQuantities[product.id] = qty;
                cart.push({ ...product, quantity: qty });
            }
        });
        updateCartCount();
    } catch(e) {
        cart = [];
        products.forEach(p => productQuantities[p.id] = 0);
        localStorage.removeItem(CART_STORAGE_KEY);
    }
}

function resetCart() {
    cart = [];
    products.forEach(p => productQuantities[p.id] = 0);
    localStorage.removeItem(CART_STORAGE_KEY);
}

function renderProducts() {
    const grid = document.getElementById('productsGrid');
    const filtered = currentCategory === 'todos' ? products : products.filter(p => p.category === currentCategory);
    document.getElementById('productCount').textContent = filtered.length + ' ' + (filtered.length === 1 ? 'item' : 'itens');
    if (!filtered.length) {
        grid.innerHTML = '<p style="grid-column:1/-1;text-align:center;color:var(--text-muted);padding:3rem 0;font-size:0.9rem;">Nenhum componente cadastrado nesta categoria.</p>';
        return;
    }
    grid.innerHTML = filtered.map(product => buildProductCard(product)).join('');
}

function buildProductCard(product) {
    const available = product.stock - productQuantities[product.id];
    const qty = productQuantities[product.id];
    const stockClass = available === 0 ? 'stock-out' : available <= 5 ? 'stock-low' : 'stock-ok';
    return `
        <div class="product-card">
            <div class="product-image">${productImage(product)}</div>
            <div class="product-info">
                <h3 class="product-name">${escapeHtml(product.name)}</h3>
                <p class="product-description">${escapeHtml(product.description)}</p>
                <div class="product-stock">
                    <span>Disponível</span>
                    <span class="${stockClass}">${available} un</span>
                </div>
                <div class="product-actions">
                    <div class="quantity-control">
                        <button class="qty-btn" onclick="decreaseQuantity(${product.id})" ${qty === 0 ? 'disabled' : ''}>&#8722;</button>
                        <input type="number" class="qty-input" value="${qty}" min="0" max="${available + qty}" onchange="setQuantity(${product.id}, this.value)" onclick="this.select()">
                        <button class="qty-btn" onclick="increaseQuantity(${product.id})" ${available === 0 ? 'disabled' : ''}>+</button>
                    </div>
                    <button class="details-btn" onclick="showDetails(${product.id})">Ver Especificações</button>
                </div>
            </div>
        </div>`;
}

function increaseQuantity(productId) {
    const product = products.find(p => p.id === productId);
    if (!product) return;
    if (product.stock - productQuantities[productId] > 0) {
        productQuantities[productId]++;
        const item = cart.find(i => i.id === productId);
        if (item) item.quantity++; else cart.push({ ...product, quantity: 1 });
        saveCart(); updateCartCount(); renderProducts();
        showNotification('Adicionado ao carrinho');
    }
}
function decreaseQuantity(productId) {
    if (productQuantities[productId] > 0) {
        productQuantities[productId]--;
        const item = cart.find(i => i.id === productId);
        if (item) { item.quantity--; if (item.quantity === 0) cart = cart.filter(i => i.id !== productId); }
        saveCart(); updateCartCount(); renderProducts();
    }
}
function setQuantity(productId, value) {
    const product = products.find(p => p.id === productId);
    if (!product) return;
    let qty = Math.max(0, Math.min(parseInt(value) || 0, product.stock));
    productQuantities[productId] = qty;
    if (qty === 0) { cart = cart.filter(i => i.id !== productId); }
    else { const item = cart.find(i => i.id === productId); if (item) item.quantity = qty; else cart.push({ ...product, quantity: qty }); }
    saveCart(); updateCartCount(); renderProducts();
}
function filterProducts(term) {
    const grid = document.getElementById('productsGrid');
    const base = currentCategory === 'todos' ? products : products.filter(p => p.category === currentCategory);
    const filtered = base.filter(p => p.name.toLowerCase().includes(term.toLowerCase()) || p.description.toLowerCase().includes(term.toLowerCase()));
    document.getElementById('productCount').textContent = filtered.length + ' itens';
    if (!filtered.length) { grid.innerHTML = '<p style="grid-column:1/-1;text-align:center;color:var(--text-muted);padding:3rem 0;font-size:0.9rem;">Nenhum componente encontrado</p>'; return; }
    grid.innerHTML = filtered.map(p => buildProductCard(p)).join('');
}
function updateCartCount() {
    document.getElementById('cartCount').textContent = cart.reduce((s, i) => s + i.quantity, 0);
}

// ── CART ──
function openCart() { document.getElementById('cartModal').classList.add('active'); renderCart(); }
function closeCart() { document.getElementById('cartModal').classList.remove('active'); }
function renderCart() {
    const el = document.getElementById('cartItems');
    if (!cart.length) { el.innerHTML = '<div class="empty-cart">Carrinho vazio</div>'; return; }
    el.innerHTML = cart.map(item => `
        <div class="cart-item">
            <div class="cart-item-icon">${productImage(item)}</div>
            <div class="cart-item-info">
                <div class="cart-item-name">${escapeHtml(item.name)}</div>
                <div class="cart-item-quantity">${item.quantity} unidade${item.quantity > 1 ? 's' : ''}</div>
            </div>
            <div class="cart-item-controls">
                <button class="qty-btn" onclick="decreaseFromCart(${item.id})">&#8722;</button>
                <span style="font-family:'IBM Plex Mono',monospace;font-size:0.85rem;min-width:20px;text-align:center;">${item.quantity}</span>
                <button class="qty-btn" onclick="increaseFromCart(${item.id})">+</button>
                <button class="qty-btn cart-item-remove" title="Remover todas as unidades" onclick="removeItemFromCart(${item.id})">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                </button>
            </div>
        </div>`).join('');
}
function increaseFromCart(id) {
    const p = products.find(p => p.id === id);
    if (p && p.stock - productQuantities[id] > 0) {
        productQuantities[id]++; cart.find(i => i.id === id).quantity++;
        saveCart(); updateCartCount(); renderCart(); renderProducts();
    }
    else stoktechToast('Estoque esgotado para este item.', 'aviso');
}
function decreaseFromCart(id) {
    if (productQuantities[id] > 0) {
        productQuantities[id]--;
        const item = cart.find(i => i.id === id);
        item.quantity--;
        if (!item.quantity) cart = cart.filter(i => i.id !== id);
        saveCart(); updateCartCount(); renderCart(); renderProducts();
    }
}
async function removeItemFromCart(id) {
    const item = cart.find(i => i.id === id);
    if (!item) return;
    const ok = await stoktechConfirm({
        titulo: 'Remover componente',
        mensagem: `Tem certeza que deseja remover todas as ${item.quantity} unidade(s) de "${item.name}" do carrinho?`,
        textoConfirmar: 'Remover'
    });
    if (!ok) return;
    productQuantities[id] = 0;
    cart = cart.filter(i => i.id !== id);
    saveCart(); updateCartCount(); renderCart(); renderProducts();
    stoktechToast('Componente removido do carrinho.', 'sucesso');
}
async function clearCart() {
    const ok = await stoktechConfirm({
        titulo: 'Limpar carrinho',
        mensagem: 'Você tem certeza que deseja limpar todos os itens do carrinho?',
        textoConfirmar: 'Limpar'
    });
    if (!ok) return;
    resetCart(); updateCartCount(); renderCart(); renderProducts();
}
async function requestItems() {
    if (!cart.length) { stoktechToast('Adicione itens antes de solicitar.', 'aviso'); return; }

    const btn = document.getElementById('requestItems');
    const textoOriginal = btn.textContent;
    btn.disabled = true; btn.textContent = 'Enviando...';

    try {
        const res = await fetch('menu_principal.php?acao=solicitar_emprestimo', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ itens: cart.map(i => ({ id: i.id, quantidade: i.quantity })) })
        });
        const data = await res.json();
        if (!data.sucesso) { stoktechToast(data.mensagem, 'erro'); return; }

        const now = new Date();
        const chatId = data.idemprestimo || Date.now();
        const newChat = {
            id: chatId,
            date: now.toLocaleDateString('pt-BR'),
            time: now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }),
            status: 'Pendente',
            items: cart.map(i => ({ ...i })),
            messages: [{ type: 'system', text: `<strong>Solicitação de Empréstimo #${chatId}</strong><br><br>${cart.map(i => `${escapeHtml(i.name)} — ${i.quantity} un`).join('<br>')}<br><br>Aguardando aprovação...`, timestamp: now }]
        };
        chats.unshift(newChat); saveChats();
        resetCart(); updateCartCount(); renderProducts();
        showNotification('Solicitação enviada com sucesso');
        closeCart();
        setTimeout(() => openChatList(), 500);
    } catch(e) {
        stoktechToast('Erro de conexão.', 'erro');
    }

    btn.disabled = false; btn.textContent = textoOriginal;
}

// ── DETAILS ──
function showDetails(id) {
    const p = products.find(p => p.id === id);
    if (!p) return;
    const specs = Object.keys(p.specs || {}).length ? Object.entries(p.specs).map(([k, v]) => `<div class="spec-item"><strong>${escapeHtml(k)}</strong><span>${escapeHtml(v)}</span></div>`).join('') : '<p style="color:var(--text-muted);font-size:0.88rem;">Nenhuma especificação cadastrada.</p>';
    document.getElementById('detailsContent').innerHTML = `
        <div class="details-header">
            <div class="details-image">${productImage(p)}</div>
            <div class="details-info">
                <h2>${escapeHtml(p.name)}</h2>
                <p>${escapeHtml(p.fullDescription)}</p>
            </div>
        </div>
        <div class="details-specs">
            <h3>Especificações Técnicas</h3>
            ${specs}
        </div>`;
    document.getElementById('detailsModal').classList.add('active');
}
function closeDetails() { document.getElementById('detailsModal').classList.remove('active'); }

// ── PROFILE ──
function updateProfileDisplay() {
    document.getElementById('headerUserName').textContent = userProfile.name;
    document.getElementById('headerUserEmail').textContent = userProfile.email;
    const av = document.getElementById('headerAvatar');
    if (userProfile.profileImage) {
        av.innerHTML = `<img src="${userProfile.profileImage}" alt="">`;
    } else {
        av.innerHTML = userProfile.name ? userProfile.name.charAt(0).toUpperCase() : '&#9751;';
        av.style.fontFamily = 'IBM Plex Sans, sans-serif';
        av.style.fontSize = '0.9rem';
        av.style.fontWeight = '700';
    }
}
function openEditProfile() {
    document.getElementById('editName').value = userProfile.name;
    document.getElementById('editEmail').value = userProfile.email;
    document.getElementById('editPassword').value = '';
    document.getElementById('confirmPassword').value = '';
    const preview = document.getElementById('profilePreview');
    preview.innerHTML = userProfile.profileImage ? `<img src="${userProfile.profileImage}" style="width:100%;height:100%;object-fit:cover;">` : '&#9751;';
    document.getElementById('editProfileModal').classList.add('active');
}
function closeEditProfile() { document.getElementById('editProfileModal').classList.remove('active'); }
function handleProfileImageUpload(e) {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = ev => { document.getElementById('profilePreview').innerHTML = `<img src="${ev.target.result}" style="width:100%;height:100%;object-fit:cover;">`; };
    reader.readAsDataURL(file);
}
async function saveProfile() {
    const name = document.getElementById('editName').value.trim();
    const email = document.getElementById('editEmail').value.trim();
    const pw = document.getElementById('editPassword').value;
    const cpw = document.getElementById('confirmPassword').value;
    if (!name || !email) { stoktechToast('Nome e email são obrigatórios.', 'aviso'); return; }
    if (pw && pw !== cpw) { stoktechToast('As senhas não coincidem.', 'aviso'); return; }
    const btn = document.getElementById('saveProfile');
    btn.disabled = true; btn.textContent = 'Salvando...';
    const fd = new FormData();
    fd.append('nome', name); fd.append('email', email);
    if (pw) fd.append('senha', pw);
    const fotoInput = document.getElementById('profileImageInput');
    if (fotoInput.files[0]) fd.append('foto', fotoInput.files[0]);
    try {
        const res = await fetch('atualizar_perfil.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.sucesso) {
            userProfile.name = name; userProfile.email = email;
            if (data.foto_perfil) userProfile.profileImage = data.foto_perfil;
            updateProfileDisplay(); closeEditProfile(); fotoInput.value = '';
            showNotification(data.mensagem);
        } else { stoktechToast(data.mensagem, 'erro'); }
    } catch { stoktechToast('Erro de conexão.', 'erro'); }
    btn.disabled = false; btn.textContent = 'Salvar Alterações';
}

// ── CHATS ──
function saveChats() { localStorage.setItem('stokChats', JSON.stringify(chats)); }
function loadChats() { const s = localStorage.getItem('stokChats'); if (s) chats = JSON.parse(s); }
async function openChatList() {
    const modal = document.getElementById('chatListModal');
    const list = document.getElementById('chatList');

    modal.classList.add('active');
    list.innerHTML = '<p style="color: var(--text-secondary);">Carregando empréstimos...</p>';

    try {
        const resposta = await fetch('menu_principal.php?acao=listar_meus_emprestimos', {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store'
        });

        const dados = await resposta.json();

        if (!dados.sucesso) {
            list.innerHTML = '<p style="color: #ef4444;">Erro ao carregar empréstimos.</p>';
            return;
        }

        if (!dados.emprestimos || dados.emprestimos.length === 0) {
            list.innerHTML = '<p style="color: var(--text-secondary);">Nenhum empréstimo encontrado.</p>';
            return;
        }

        list.innerHTML = dados.emprestimos.map(emp => {
        const data = emp.DATA_EMPRESTIMO 
        ? emp.DATA_EMPRESTIMO.split('-').reverse().join('/')
        : '';

        const qtdItens = emp.ITENS ? emp.ITENS.length : 0;

        return `
            <div class="chat-item" onclick="openChat(${emp.IDEMPRESTIMO})">
                <div class="chat-info">
                    <strong>Empréstimo — ${data}</strong>
                    <p>${qtdItens} item${qtdItens > 1 ? 's' : ''} solicitado${qtdItens > 1 ? 's' : ''}</p>
                </div>

                <span class="status-badge ${String(emp.STATUS).toLowerCase()}">
                    ${emp.STATUS}
                </span>
            </div>
        `;
    }).join('');

        window.meusEmprestimos = dados.emprestimos;

    } catch (e) {
        list.innerHTML = '<p style="color: #ef4444;">Erro de conexão ao carregar empréstimos.</p>';
        console.error(e);
    }
}
function closeChatList() { document.getElementById('chatListModal').classList.remove('active'); }
function renderChatList() {
    const el = document.getElementById('chatList');
    if (!chats.length) { el.innerHTML = '<div class="empty-chat-list">Nenhum empréstimo registrado ainda.</div>'; return; }
    el.innerHTML = chats.map(c => `
        <div class="chat-list-item" onclick="openSpecificChat(${c.id})">
            <div>
                <div class="chat-list-title">Empréstimo — ${c.date} ${c.time}</div>
                <div class="chat-list-preview">${c.items.length} item${c.items.length !== 1 ? 'ns' : ''} solicitado${c.items.length !== 1 ? 's' : ''}</div>
            </div>
            <span class="chat-status status-${c.status.toLowerCase()}">${c.status}</span>
        </div>`).join('');
}
function openSpecificChat(id) {
    currentChatId = id;
    const c = chats.find(c => c.id === id);
    if (!c) return;
    document.getElementById('chatTitle').textContent = `Empréstimo — ${c.date}`;
    const msgs = document.getElementById('chatMessages');
    msgs.innerHTML = c.messages.map(m => `<div class="chat-message ${m.type}">${m.text}</div>`).join('');
    msgs.scrollTop = msgs.scrollHeight;
    document.getElementById('chatListModal').classList.remove('active');
    document.getElementById('chatModal').classList.add('active');
}
function backToChatList() { document.getElementById('chatModal').classList.remove('active'); openChatList(); }
function closeChat() { document.getElementById('chatModal').classList.remove('active'); currentChatId = null; }
function openChat(id) {
    currentChatId = id;

    const emp = window.meusEmprestimos.find(e => e.IDEMPRESTIMO == id);

    if (!emp) {
        stoktechToast('Empréstimo não encontrado.', 'erro');
        return;
    }

    document.getElementById('chatTitle').textContent = `Empréstimo — ${formatarDataEmprestimo(emp.DATA_EMPRESTIMO)}`;

    const msgs = document.getElementById('chatMessages');

    const itens = emp.ITENS && emp.ITENS.length > 0
        ? emp.ITENS.map(item => {
            return `• ${escapeHtml(item.COMPONENTE_NOME)} — Quantidade: ${item.QUANTIDADE}`;
        }).join('<br>')
        : 'Nenhum item encontrado.';

    msgs.innerHTML = `
    <div class="chat-message system">
        <strong>Solicitação de Empréstimo</strong><br><br>
        <strong>Status:</strong> ${emp.STATUS}<br>
        <strong>Data da solicitação:</strong> ${formatarDataEmprestimo(emp.DATA_EMPRESTIMO)}<br>
        <strong>Limite para retirada:</strong> ${formatarDataEmprestimo(emp.DATA_LIMITE_RETIRADA) || '—'}<br>
        <strong>Prazo de devolução:</strong> ${formatarDataEmprestimo(emp.PRAZO_DEVOLUCAO) || '—'}<br><br>
        <strong>Itens:</strong><br>
        ${itens}
    </div>
`;

    if (emp.MENSAGENS && emp.MENSAGENS.length > 0) {
        emp.MENSAGENS.forEach(m => {
            const tipo = m.USUARIO_TIPO === 'ADMINISTRADOR' ? 'manager' : 'user';
            const autor = m.USUARIO_TIPO === 'ADMINISTRADOR' ? 'Gerenciador' : 'Você';

            msgs.innerHTML += `
                <div class="chat-message ${tipo}">
                    <strong>${autor}:</strong><br>
                    ${escapeHtml(m.MENSAGEM)}
                    <br><small>${m.DATA_ENVIO}</small>
                </div>
            `;
        });
    }

    msgs.scrollTop = msgs.scrollHeight;

    document.getElementById('chatListModal').classList.remove('active');
    document.getElementById('chatModal').classList.add('active');
}
async function sendChatMessage() {
    const input = document.getElementById('chatInput');
    const msg = input.value.trim();

    if (!msg || currentChatId === null || currentChatId === undefined) return;

    try {
        const resposta = await fetch('menu_principal.php?acao=enviar_mensagem_emprestimo', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                idemprestimo: currentChatId,
                mensagem: msg
            })
        });

        const texto = await resposta.text();

        let dados;

        try {
            dados = JSON.parse(texto);
        } catch (erroJson) {
            stoktechToast('O PHP não retornou JSON. Resposta: ' + texto.substring(0, 300), 'erro');
            console.error(texto);
            return;
        }

        if (!dados.sucesso) {
            stoktechToast(dados.mensagem || 'Erro ao enviar mensagem.', 'erro');
            return;
        }

        input.value = '';

        await openChatList();

        const emp = window.meusEmprestimos.find(e => e.IDEMPRESTIMO == currentChatId);

        if (emp) {
            openChat(currentChatId);
        }

    } catch (e) {
        stoktechToast('Erro de conexão ao enviar mensagem: ' + e.message, 'erro');
        console.error(e);
    }
}

// ── NOTIFICATION ──
function showNotification(msg) {
    stoktechToast(msg, 'sucesso');
}
</script>
    <script src="js/protecao.js"></script>
</body>
</html>
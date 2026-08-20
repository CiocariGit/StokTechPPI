<?php
require_once 'conexao.php';
require_once 'auth.php';
verificar_admin();

header('Content-Type: application/json');

$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

// ============================================================
// CATEGORIAS — LISTAR
// ============================================================
if ($acao === 'listar_categorias') {
    $stmt = $pdo->query("SELECT * FROM CATEGORIA ORDER BY NOME");
    echo json_encode(['sucesso' => true, 'categorias' => $stmt->fetchAll()]);
    exit;
}

// ============================================================
// CATEGORIAS — SALVAR (criar ou editar)
// ============================================================
if ($acao === 'salvar_categoria') {
    $id        = $_POST['id'] ?? '';
    $nome      = trim($_POST['nome'] ?? '');
    $icone     = trim($_POST['icone'] ?? '📦');
    $descricao = trim($_POST['descricao'] ?? '');

    if (!$nome) {
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Nome é obrigatório.'
        ]);
        exit;
    }

    try {
        if ($id !== '' && $id !== null) {
            $pdo->prepare("
                UPDATE CATEGORIA 
                SET NOME = ?, ICONE = ?, DESCRIÇÃO = ? 
                WHERE IDCATEGORIA = ?
            ")->execute([
                $nome,
                $icone,
                $descricao,
                $id
            ]);

            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Categoria atualizada com sucesso!'
            ]);
            exit;

        } else {
            $stmtProximoId = $pdo->query("
                SELECT COALESCE(MAX(IDCATEGORIA), -1) + 1 AS PROXIMO_ID 
                FROM CATEGORIA
            ");

            $proximoId = (int)$stmtProximoId->fetch()['PROXIMO_ID'];

            $pdo->prepare("
                INSERT INTO CATEGORIA 
                (IDCATEGORIA, NOME, ICONE, DESCRIÇÃO) 
                VALUES (?, ?, ?, ?)
            ")->execute([
                $proximoId,
                $nome,
                $icone,
                $descricao
            ]);

            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Categoria criada com sucesso!'
            ]);
            exit;
        }

    } catch (Exception $e) {
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Erro ao salvar categoria: ' . $e->getMessage()
        ]);
        exit;
    }
}

// ============================================================
// CATEGORIAS — EXCLUIR
// ============================================================
if ($acao === 'excluir_categoria') {
    $id = $_POST['id'] ?? '';

    if ($id === '' || $id === null) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'ID inválido.']);
        exit;
    }

    // Verifica se tem componentes
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM COMPONENTE WHERE IDCATEGORIA = ?");
    $stmt->execute([$id]);
    if ($stmt->fetch()['total'] > 0) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Não é possível excluir uma categoria que possui componentes.']);
        exit;
    }

    $pdo->prepare("DELETE FROM CATEGORIA WHERE IDCATEGORIA = ?")->execute([$id]);
    echo json_encode(['sucesso' => true, 'mensagem' => 'Categoria excluída com sucesso!']);
    exit;
}

// ============================================================
// COMPONENTES — LISTAR
// ============================================================
if ($acao === 'listar_componentes') {
    $stmt = $pdo->query("
        SELECT c.*, cat.NOME as CATEGORIA_NOME, cat.ICONE as CATEGORIA_ICONE,
               u.NOME as CRIADO_POR_NOME
        FROM COMPONENTE c
        LEFT JOIN CATEGORIA cat ON c.IDCATEGORIA = cat.IDCATEGORIA
        LEFT JOIN USUARIO u ON c.CRIADO_POR = u.IDUSUARIO
        ORDER BY c.NOME
    ");
    echo json_encode(['sucesso' => true, 'componentes' => $stmt->fetchAll()]);
    exit;
}

// ============================================================
// COMPONENTES — SALVAR (criar ou editar)
// ============================================================
if ($acao === 'salvar_componente') {
    $id                 = $_POST['id'] ?? '';
    $nome               = trim($_POST['nome'] ?? '');
    $descricao_curta    = trim($_POST['descricao_curta'] ?? '');
    $descricao_completa = trim($_POST['descricao_completa'] ?? '');
    $idcategoria        = $_POST['idcategoria'] ?? '';
    $quantidade         = (int)($_POST['quantidade'] ?? 0);

    if (!$nome || !$descricao_curta || !$descricao_completa || ($idcategoria === '' || $idcategoria === null)) {
        echo json_encode([
            'sucesso'  => false,
            'mensagem' => 'Preencha todos os campos obrigatórios.',
            'recebido' => [
                'nome'               => $nome,
                'descricao_curta'    => $descricao_curta,
                'descricao_completa' => $descricao_completa,
                'idcategoria'        => $idcategoria
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $imagem = $_POST['imagem_atual'] ?? null;

        if (!empty($_FILES['imagem']['name'])) {
            $pasta = 'img/componentes/';

            if (!is_dir($pasta)) {
                mkdir($pasta, 0755, true);
            }

            $ext = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (!in_array(strtolower($ext), $allowed)) {
                echo json_encode([
                    'sucesso' => false,
                    'mensagem' => 'Formato de imagem inválido. Use JPG, PNG, GIF ou WEBP.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $filename = 'comp_' . time() . '_' . uniqid() . '.' . $ext;

            if (!move_uploaded_file($_FILES['imagem']['tmp_name'], $pasta . $filename)) {
                echo json_encode([
                    'sucesso' => false,
                    'mensagem' => 'Erro ao fazer upload da imagem.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($imagem && file_exists($imagem)) {
                unlink($imagem);
            }

            $imagem = $pasta . $filename;
        }

        if ($id !== '' && $id !== null) {
            $pdo->prepare("
                UPDATE COMPONENTE SET
                    NOME = ?,
                    IMAGEM = ?,
                    DESCRICAO_CURTA = ?,
                    DESCRICAO_COMPLETA = ?,
                    IDCATEGORIA = ?,
                    QUANTIDADE = ?
                WHERE IDCOMPONENTE = ?
            ")->execute([
                $nome,
                $imagem,
                $descricao_curta,
                $descricao_completa,
                $idcategoria,
                $quantidade,
                $id
            ]);

            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Componente atualizado com sucesso!'
            ], JSON_UNESCAPED_UNICODE);
            exit;

        } else {
            $stmtProximoId = $pdo->query("
                SELECT COALESCE(MAX(IDCOMPONENTE), -1) + 1 AS PROXIMO_ID 
                FROM COMPONENTE
            ");

            $proximoId = (int)$stmtProximoId->fetch(PDO::FETCH_ASSOC)['PROXIMO_ID'];

            $pdo->prepare("
                INSERT INTO COMPONENTE 
                (IDCOMPONENTE, NOME, IMAGEM, DESCRICAO_CURTA, DESCRICAO_COMPLETA, IDCATEGORIA, QUANTIDADE, CRIADO_POR, CRIADO_EM)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ")->execute([
                $proximoId,
                $nome,
                $imagem,
                $descricao_curta,
                $descricao_completa,
                $idcategoria,
                $quantidade,
                $_SESSION['idusuario']
            ]);

            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Componente cadastrado com sucesso!'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

    } catch (Exception $e) {
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Erro ao salvar componente: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ============================================================
// COMPONENTES — EXCLUIR
// ============================================================
if ($acao === 'excluir_componente') {
    $id = $_POST['id'] ?? '';

    if ($id === '' || $id === null) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'ID inválido.']);
        exit;
    }

    // Busca imagem para deletar
    $stmt = $pdo->prepare("SELECT IMAGEM FROM COMPONENTE WHERE IDCOMPONENTE = ?");
    $stmt->execute([$id]);
    $comp = $stmt->fetch();

    if ($comp && $comp['IMAGEM'] && file_exists($comp['IMAGEM'])) {
        unlink($comp['IMAGEM']);
    }

    $pdo->prepare("DELETE FROM COMPONENTE WHERE IDCOMPONENTE = ?")->execute([$id]);
    echo json_encode(['sucesso' => true, 'mensagem' => 'Componente excluído com sucesso!']);
    exit;
}

echo json_encode(['sucesso' => false, 'mensagem' => 'Ação inválida.']);
<?php
session_start();
require_once 'conexao.php';
require_once 'auth.php';
verificar_admin();

header('Content-Type: application/json');

$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';

// ============================================================
// LISTAR USUARIOS
// ============================================================
if ($acao === 'listar') {
    $stmt = $pdo->query("SELECT IDUSUARIO, NOME, EMAIL, CPF, TIPO FROM USUARIO ORDER BY NOME");
    $usuarios = $stmt->fetchAll();
    echo json_encode(['sucesso' => true, 'usuarios' => $usuarios]);
    exit;
}

// ============================================================
// EDITAR USUARIO
// ============================================================
if ($acao === 'editar') {
    $id    = $_POST['id']    ?? '';
    $nome  = trim($_POST['nome']  ?? '');
    $email = trim($_POST['email'] ?? '');
    $cpf   = trim($_POST['cpf']   ?? '');

    if (!$id || !$nome || !$email || !$cpf) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Preencha todos os campos.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Email inválido.']);
        exit;
    }

    // Verifica email duplicado
    $stmt = $pdo->prepare("SELECT IDUSUARIO FROM USUARIO WHERE EMAIL = ? AND IDUSUARIO != ?");
    $stmt->execute([$email, $id]);
    if ($stmt->fetch()) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Este email já está em uso.']);
        exit;
    }

    // Verifica CPF duplicado
    $stmt = $pdo->prepare("SELECT IDUSUARIO FROM USUARIO WHERE CPF = ? AND IDUSUARIO != ?");
    $stmt->execute([$cpf, $id]);
    if ($stmt->fetch()) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Este CPF já está em uso.']);
        exit;
    }

    $pdo->prepare("UPDATE USUARIO SET NOME = ?, EMAIL = ?, CPF = ? WHERE IDUSUARIO = ?")
        ->execute([$nome, $email, $cpf, $id]);

    echo json_encode(['sucesso' => true, 'mensagem' => 'Usuário atualizado com sucesso!']);
    exit;
}

// ============================================================
// TORNAR ADMIN / REMOVER ADMIN
// ============================================================
if ($acao === 'toggle_admin') {
    $id = $_POST['id'] ?? '';

    if (!$id) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'ID inválido.']);
        exit;
    }

    // Não deixa remover o próprio admin logado
    if ($id == $_SESSION['idusuario']) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Você não pode alterar seu próprio tipo.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT TIPO FROM USUARIO WHERE IDUSUARIO = ?");
    $stmt->execute([$id]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não encontrado.']);
        exit;
    }

    $novoTipo = $usuario['TIPO'] === 'ADMINISTRADOR' ? 'ALUNO' : 'ADMINISTRADOR';

    $pdo->prepare("UPDATE USUARIO SET TIPO = ? WHERE IDUSUARIO = ?")
        ->execute([$novoTipo, $id]);

    $msg = $novoTipo === 'ADMINISTRADOR' ? 'Usuário promovido a Administrador!' : 'Usuário removido de Administrador!';
    echo json_encode(['sucesso' => true, 'mensagem' => $msg, 'novoTipo' => $novoTipo]);
    exit;
}

// ============================================================
// EXCLUIR USUARIO
// ============================================================
if ($acao === 'excluir') {
    $id = $_POST['id'] ?? '';

    if (!$id) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'ID inválido.']);
        exit;
    }

    // Não deixa excluir o próprio admin logado
    if ($id == $_SESSION['idusuario']) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Você não pode excluir sua própria conta.']);
        exit;
    }

    $pdo->prepare("DELETE FROM USUARIO WHERE IDUSUARIO = ?")->execute([$id]);

    echo json_encode(['sucesso' => true, 'mensagem' => 'Usuário excluído com sucesso!']);
    exit;
}

echo json_encode(['sucesso' => false, 'mensagem' => 'Ação inválida.']);
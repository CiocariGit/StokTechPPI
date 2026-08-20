<?php
session_start();
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cadastro.php');
    exit;
}

$nome             = trim($_POST['nome_completo'] ?? '');
$cpf              = preg_replace('/\D/', '', trim($_POST['cpf'] ?? ''));
$email            = trim($_POST['email'] ?? '');
$senha            = $_POST['senha'] ?? '';
$confirmar_senha  = $_POST['confirmar_senha'] ?? '';

// Validação dos campos
if (empty($nome) || empty($cpf) || empty($email) || empty($senha) || empty($confirmar_senha)) {
    $_SESSION['erro'] = 'Preencha todos os campos.';
    header('Location: cadastro.php');
    exit;
}

if ($senha !== $confirmar_senha) {
    $_SESSION['erro'] = 'As senhas não coincidem.';
    header('Location: cadastro.php');
    exit;
}

if (strlen($senha) < 6) {
    $_SESSION['erro'] = 'A senha deve ter pelo menos 6 caracteres.';
    header('Location: cadastro.php');
    exit;
}

// Verifica se CPF já está cadastrado
$stmt = $pdo->prepare("SELECT IDUSUARIO FROM USUARIO WHERE CPF = ?");
$stmt->execute([$cpf]);
if ($stmt->fetch()) {
    $_SESSION['erro'] = 'CPF já cadastrado.';
    header('Location: cadastro.php');
    exit;
}

// Verifica se e-mail já está cadastrado
$stmt = $pdo->prepare("SELECT IDUSUARIO FROM USUARIO WHERE EMAIL = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    $_SESSION['erro'] = 'E-mail já cadastrado.';
    header('Location: cadastro.php');
    exit;
}

// Gera o próximo ID manualmente (já que não há AUTO_INCREMENT definido no schema)
$stmt = $pdo->query("SELECT COALESCE(MAX(IDUSUARIO), 0) + 1 AS proximo_id FROM USUARIO");
$proximo_id = $stmt->fetch()['proximo_id'];

// Hash da senha
$senha_hash = password_hash($senha, PASSWORD_DEFAULT);

// Insere o usuário — tipo padrão: ALUNO
$stmt = $pdo->prepare("
    INSERT INTO USUARIO (IDUSUARIO, NOME, CPF, EMAIL, SENHA, TIPO)
    VALUES (?, ?, ?, ?, ?, 'ALUNO')
");
$stmt->execute([$proximo_id, $nome, $cpf, $email, $senha_hash]);

$_SESSION['sucesso'] = 'Cadastro realizado com sucesso! Faça login.';
header('Location: index.php');
exit;
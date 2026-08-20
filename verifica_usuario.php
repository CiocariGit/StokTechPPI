<?php
session_start();
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$cpf   = preg_replace('/\D/', '', trim($_POST['cpf'] ?? ''));
$senha = $_POST['senha'] ?? '';

// Validação básica
if (empty($cpf) || empty($senha)) {
    $_SESSION['erro'] = 'Preencha todos os campos.';
    header('Location: index.php');
    exit;
}

// Busca o usuário pelo CPF
$stmt = $pdo->prepare("SELECT * FROM USUARIO WHERE CPF = ?");
$stmt->execute([$cpf]);
$usuario = $stmt->fetch();

if (!$usuario || !password_verify($senha, $usuario['SENHA'])) {
    $_SESSION['erro'] = 'CPF ou senha incorretos.';
    header('Location: index.php');
    exit;
}

// Salva dados na sessão
$_SESSION['idusuario'] = $usuario['IDUSUARIO'];
$_SESSION['nome']      = $usuario['NOME'];
$_SESSION['tipo']      = $usuario['TIPO'];

// Redireciona conforme o tipo de usuário
if ($usuario['TIPO'] === 'ADMINISTRADOR') {
    header('Location: stoktech_admin.php');
} else {
    header('Location: menu_principal.php');
}
exit;
<?php
session_start();
require_once 'conexao.php';
require_once 'auth.php';

header('Content-Type: application/json');

$nome  = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';

if (!$nome || !$email) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Nome e email são obrigatórios.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Email inválido.']);
    exit;
}

$id = $_SESSION['idusuario'];

// Verifica se o email já está em uso por outro usuário
$stmt = $pdo->prepare("SELECT IDUSUARIO FROM USUARIO WHERE EMAIL = ? AND IDUSUARIO != ?");
$stmt->execute([$email, $id]);
if ($stmt->fetch()) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Este email já está em uso por outro usuário.']);
    exit;
}

// Upload de foto de perfil
$foto_perfil = null;
if (!empty($_FILES['foto']['name'])) {
    $pasta = 'img/perfis/';
    if (!is_dir($pasta)) mkdir($pasta, 0755, true);

    $ext     = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($ext, $allowed)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Formato de imagem inválido. Use JPG, PNG, GIF ou WEBP.']);
        exit;
    }

    if ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'A imagem deve ter no máximo 2MB.']);
        exit;
    }

    $filename = 'perfil_' . $id . '_' . time() . '.' . $ext;

    if (!move_uploaded_file($_FILES['foto']['tmp_name'], $pasta . $filename)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao fazer upload da imagem.']);
        exit;
    }

    // Deleta foto antiga se existir
    $stmt_old = $pdo->prepare("SELECT FOTO_PERFIL FROM USUARIO WHERE IDUSUARIO = ?");
    $stmt_old->execute([$id]);
    $old = $stmt_old->fetch();
    if ($old && $old['FOTO_PERFIL'] && file_exists($old['FOTO_PERFIL'])) {
        unlink($old['FOTO_PERFIL']);
    }

    $foto_perfil = $pasta . $filename;
}

// Monta a query dinamicamente
if (!empty($senha)) {
    if (strlen($senha) < 6) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'A senha deve ter pelo menos 6 caracteres.']);
        exit;
    }
    $hash = password_hash($senha, PASSWORD_BCRYPT);

    if ($foto_perfil) {
        $pdo->prepare("UPDATE USUARIO SET NOME = ?, EMAIL = ?, SENHA = ?, FOTO_PERFIL = ? WHERE IDUSUARIO = ?")
            ->execute([$nome, $email, $hash, $foto_perfil, $id]);
    } else {
        $pdo->prepare("UPDATE USUARIO SET NOME = ?, EMAIL = ?, SENHA = ? WHERE IDUSUARIO = ?")
            ->execute([$nome, $email, $hash, $id]);
    }
} else {
    if ($foto_perfil) {
        $pdo->prepare("UPDATE USUARIO SET NOME = ?, EMAIL = ?, FOTO_PERFIL = ? WHERE IDUSUARIO = ?")
            ->execute([$nome, $email, $foto_perfil, $id]);
    } else {
        $pdo->prepare("UPDATE USUARIO SET NOME = ?, EMAIL = ? WHERE IDUSUARIO = ?")
            ->execute([$nome, $email, $id]);
    }
}

// Atualiza a sessão
$_SESSION['nome']  = $nome;
$_SESSION['email'] = $email;
if ($foto_perfil) {
    $_SESSION['foto_perfil'] = $foto_perfil;
}

echo json_encode([
    'sucesso'     => true,
    'mensagem'    => 'Perfil atualizado com sucesso!',
    'foto_perfil' => $foto_perfil ? $foto_perfil : null,
]);
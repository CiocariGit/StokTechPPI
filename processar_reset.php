<?php
session_start();
require_once 'conexao.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

$acao = $_POST['acao'] ?? '';

// ============================================================
// ETAPA 1 — Enviar código para o email
// ============================================================
if ($acao === 'enviar_codigo') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Email inválido.']);
        exit;
    }

    // Verifica se o email existe na tabela USUARIO
    $stmt = $pdo->prepare("SELECT IDUSUARIO FROM USUARIO WHERE EMAIL = ?");
    $stmt->execute([$email]);
    if (!$stmt->fetch()) {
        echo json_encode(['sucesso' => true, 'mensagem' => 'Se esse email estiver cadastrado, você receberá o código.']);
        exit;
    }

    // Gera código de 6 dígitos seguro
    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    // Expira em 10 minutos
    $expira = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    // Remove códigos anteriores do mesmo email
    $pdo->prepare("DELETE FROM RESETAR_SENHA WHERE EMAIL = ?")->execute([$email]);

    // Salva o novo código
    $stmt = $pdo->prepare("INSERT INTO RESETAR_SENHA (EMAIL, CODE, EXPIRA) VALUES (?, ?, ?)");
    $stmt->execute([$email, $code, $expira]);

    // Envia o email via SMTP
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp-stoktech.alwaysdata.net';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'stoktech@alwaysdata.net';
        $mail->Password   = 'StokTech2026@';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('stoktech@alwaysdata.net', 'StokTech');
        $mail->addAddress($email);

        // Anexa a logo do site como imagem embutida (usada como "foto de perfil" do email)
        $caminhoLogo = __DIR__ . '/img/logo_stoktech.png';
        $logoCid = 'stoktechlogo';
        if (file_exists($caminhoLogo)) {
            $mail->addEmbeddedImage($caminhoLogo, $logoCid);
        }

        $mail->Subject = 'Seu código de verificação - StokTech';
        $mail->isHTML(true);
        $logoHtml = file_exists($caminhoLogo)
            ? "<img src='cid:$logoCid' alt='StokTech' style='width:64px; height:64px; border-radius:50%; object-fit:cover; background:#ffffff; padding:6px; display:block; margin:0 auto 16px;'>"
            : '';
        $mail->Body = "
            <div style='font-family: \"IBM Plex Sans\", Segoe UI, sans-serif; background: #0d0d0f; padding: 40px; border-radius: 12px; max-width: 420px; margin: auto; border: 1px solid #252530;'>
                $logoHtml
                <h2 style='color: #ffffff; text-align: center; margin: 0 0 4px; font-weight: 700;'>Stok<span style='color:#b662ff;'>Tech</span></h2>
                <p style='color: #a0a0b4; text-align: center; margin: 0 0 20px;'>Seu código de verificação é:</p>
                <div style='background: #1a1a1f; border: 2px solid #6b1fad; border-radius: 10px; padding: 20px; text-align: center; margin: 20px 0;'>
                    <span style='font-size: 36px; font-weight: bold; color: #b662ff; letter-spacing: 10px;'>$code</span>
                </div>
                <p style='color: #7a7a90; text-align: center; font-size: 13px; margin: 0;'>Válido por 10 minutos. Não compartilhe este código.</p>
            </div>
        ";
        $mail->AltBody = "Seu código de verificação StokTech: $code (válido por 10 minutos)";
        $mail->send();

        echo json_encode(['sucesso' => true, 'mensagem' => 'Código enviado com sucesso!']);
    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao enviar email: ' . $mail->ErrorInfo]);
    }
    exit;
}

// ============================================================
// ETAPA 2 — Verificar o código digitado
// ============================================================
if ($acao === 'verificar_codigo') {
    $email = trim($_POST['email'] ?? '');
    $code  = trim($_POST['code'] ?? '');

    if (!$email || !$code) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos.']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT * FROM RESETAR_SENHA
        WHERE EMAIL = ? AND CODE = ? AND USADO = 0 AND EXPIRA > NOW()
    ");
    $stmt->execute([$email, $code]);
    $reset = $stmt->fetch();

    if (!$reset) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Código inválido ou expirado.']);
        exit;
    }

    // Marca como usado
    $pdo->prepare("UPDATE RESETAR_SENHA SET USADO = 1 WHERE ID = ?")
        ->execute([$reset['ID']]);

    // Libera a troca de senha na sessão
    $_SESSION['pode_trocar_senha'] = true;
    $_SESSION['reset_email']       = $email;

    echo json_encode(['sucesso' => true, 'mensagem' => 'Código verificado!']);
    exit;
}

// ============================================================
// ETAPA 3 — Salvar a nova senha
// ============================================================
if ($acao === 'nova_senha') {
    if (!($_SESSION['pode_trocar_senha'] ?? false)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão inválida. Recomece o processo.']);
        exit;
    }

    $email = $_SESSION['reset_email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if (strlen($senha) < 8) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'A senha deve ter pelo menos 8 caracteres.']);
        exit;
    }

    $hash = password_hash($senha, PASSWORD_BCRYPT);
    $pdo->prepare("UPDATE USUARIO SET SENHA = ? WHERE EMAIL = ?")
        ->execute([$hash, $email]);

    // Limpa sessão de reset
    unset($_SESSION['pode_trocar_senha'], $_SESSION['reset_email']);

    echo json_encode(['sucesso' => true, 'mensagem' => 'Senha redefinida com sucesso!']);
    exit;
}

echo json_encode(['sucesso' => false, 'mensagem' => 'Ação inválida.']);
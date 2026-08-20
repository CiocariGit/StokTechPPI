<?php
session_start();
require_once 'conexao.php';
require_once 'auth.php';
verificar_admin();

header('Content-Type: application/json');

$acao = $_POST['acao'] ?? 'nenhuma';
echo json_encode(['acao_recebida' => $acao, 'session' => $_SESSION]);
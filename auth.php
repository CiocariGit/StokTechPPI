<?php
// Inclua este arquivo no topo de qualquer página protegida:
// require_once 'auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['idusuario'])) {
    header('Location: index.php');
    exit;
}

// Para páginas exclusivas de administrador, use:
// require_once 'auth.php';
// verificar_admin();

function verificar_admin() {
    if ($_SESSION['tipo'] !== 'ADMINISTRADOR') {
        header('Location: menu_principal.php');
        exit;
    }
}

<?php
session_start();

require __DIR__ . '/../controllers/user.php';
require __DIR__ . '/../config/config.php';

verificacao_L();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: perfil.php');
    exit();
}

$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email_novo'] ?? '');
$senhaAtual = $_POST['senha_atual'] ?? null;
$novaSenha = $_POST['nova_senha'] ?? null;
$confirmarSenha = $_POST['confirmar_senha'] ?? null;

if ($novaSenha !== null && $novaSenha !== '' && $novaSenha !== $confirmarSenha) {
    header('Location: perfil.php?erro=' . urlencode('As senhas novas não conferem'));
    exit();
} else {

    $resultado = alterar($nome, $email, $senhaAtual, $novaSenha);

    if ($resultado['sucesso']) {
        header('Location: perfil.php?sucesso=1');
    } else {
        header('Location: perfil.php?erro=' . urlencode($resultado['erro']));
    }
    exit();
}
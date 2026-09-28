<?php
session_start();

require __DIR__ . '/../controllers/user.php';
require __DIR__ . '/../controllers/contact.php';
require __DIR__ . '/../controllers/analises.php';
require __DIR__ . '/../config/config.php';

verificacao_L();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: perfil.php');
    exit();
}

$id   = $_SESSION['id'];
$tipo = $_POST['tipo'] ?? '';

switch ($tipo) {
    case 'chat':
        $ok = apagarAnalises($id);
        $mensagemSucesso = 'Histórico do chat de análise apagado.';
        $mensagemErro    = 'Não foi possível apagar o histórico do chat.';
        break;

    case 'conversa':
        $ok = apagar_conversa($id);
        $mensagemSucesso = 'Conversa com o suporte apagada.';
        $mensagemErro    = 'Não foi possível apagar a conversa.';
        break;

    default:
        header('Location: perfil.php?aba=privacidade&erro=' . urlencode('Ação inválida.'));
        exit();
}

if ($ok) {
    header('Location: perfil.php?aba=privacidade&sucesso=' . urlencode($mensagemSucesso));
} else {
    header('Location: perfil.php?aba=privacidade&erro=' . urlencode($mensagemErro));
}
exit();
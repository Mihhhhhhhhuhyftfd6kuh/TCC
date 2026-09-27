<?php
session_start();

require __DIR__ . '/../controllers/user.php';
require __DIR__ . '/../controllers/contact.php';
require __DIR__ . '/../config/config.php';

verificacao_L();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: perfil.php');
    exit();
}

$id = $_SESSION['id'];

if (apagarConversa($id)) {
    header('Location: perfil.php?sucesso=' . urlencode('Conversa com o suporte apagada.'));
} else {
    header('Location: perfil.php?erro=' . urlencode('Não foi possível apagar a conversa.'));
}
exit();
<?php
session_start();

require __DIR__ . '/../controllers/user.php';
require __DIR__ . '/../controllers/analises.php';
require __DIR__ . '/../config/config.php';

verificacao_L();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: perfil.php');
    exit();
}

$id = $_SESSION['id'];

if (apagarAnalises($id)) {
    header('Location: perfil.php?sucesso=' . urlencode('Histórico do chat de análise apagado.'));
} else {
    header('Location: perfil.php?erro=' . urlencode('Não foi possível apagar o histórico do chat.'));
}
exit();
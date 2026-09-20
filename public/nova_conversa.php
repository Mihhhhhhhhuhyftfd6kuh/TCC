<?php
session_start();

require __DIR__ . '/../controllers/conversas.php';
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../controllers/user.php';

verificacao_L();

if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
    header('Location: login.php');
    exit();
}

$novaId = criarConversa((int) $_SESSION['id']);

if ($novaId === null) {
    header('Location: painel_api.php');
    exit();
}

header('Location: painel_api.php?conversa=' . $novaId);
exit();
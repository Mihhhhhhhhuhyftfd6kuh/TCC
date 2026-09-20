<?php
session_start();

require __DIR__ . '/../controllers/user.php';
require __DIR__ . '/../config/config.php';

verificacao_L();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: perfil.php');
    exit();
}

if (!isset($_POST['confirmar']) || $_POST['confirmar'] !== 'sim') {
    header('Location: perfil.php');
    exit();
}

$ok = deletar();

if ($ok) {
    header('Location: home.php');
} else {
    header('Location: perfil.php?erro=nao_foi_possivel_deletar');
}
exit();
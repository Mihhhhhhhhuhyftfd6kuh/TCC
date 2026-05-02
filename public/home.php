<?php
session_start();
require "../config/config.php";
require "../controllers/user.php";





?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <header>
        <?php if (!isset($_SESSION['id']) || $_SESSION['id'] === null): ?>
            <a class="aba-link" href="login.php">Logar</a>
            
        <?php else: ?>
                <a href="logout.php">deslogar</a>
            
        <?php endif; ?>
        <?php if(!isset($_SESSION['id']) ||$_SESSION['id'] != 1): ?>
        
            <a href="contato/conversa.php">Conversa</a>
         <?php else: ?>
            <a href="contato/admin.php">Lista de usuarios</a>
        <?php endif; ?>

    </header>
    

</body>
</html>
<?php
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../controllers/auth.php';

    if($_SERVER['REQUEST_METHOD'] === 'POST'){
        $nome = $_POST['nome'] ?? null ;
        $email = $_POST['email'] ?? null ;
        $senha = password_hash($_POST['senha'],PASSWORD_DEFAULT );

        cadastrar($nome, $email, $senha);
        exit();
        }





    





?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>

<div class="formulario">
    <form action="" method="post">

    <input type="text" id="nome" name="nome" class="formulario" required>
    <input type="email" id="email" name="email" class="formulario" required>
    <input type="password"id="senha" name="senha"class="formulario" required>
    <button type="submit">Entrar</button>

    </form>
</div> 


</body>
</html>
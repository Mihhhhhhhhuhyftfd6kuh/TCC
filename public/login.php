<?php
require '../config/config.php';
require '../controllers\auth.php';

if($_SERVER['REQUEST_METHOD']){
    $email =  $_POST['email'] ?? null;
    $senha = $_POST['senha']  ?? null;

login($email,$senha);


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
    <form action="" method="post">

        <input type="email" name="email" id="email">
        <input type="password" name="senha" id="senha">
        <button type="submit">Entrar</button>


    </form>
</body>
</html>
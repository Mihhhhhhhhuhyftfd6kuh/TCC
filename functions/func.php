<?php

function login($email,$senha){

require '../config/config.php';




    $sql = "SELECT * FROM usuarios WHERE email=:email";
    $stmt = $pdo->prepare($sql);
    $stmt -> bindParam(':email', $email);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        $_SESSION['id'] = $usuario['id']; // ou 'id' se for esse o nome do campo
        header("Location:../Index.php");
        exit;
    } else {
        echo "<div style='color:#776472;'>Nome, email ou senha incorretos.</div>";
    }


}

function cadastrar($nome,$email,$senha){
    require '../config/config.php';


 if($nome == (preg_match('/[^a-zA-Z0-9]/', $nome))){
            echo "nao pode caractere";
            exit();

        }else{

        $sql = "INSERT INTO usuarios (nome, email, senha) VALUES (:nome, :email, :senha)";
        $stmt = $pdo->prepare($sql);
        $stmt ->bindParam(':nome',$nome);
        $stmt ->bindParam(':email',$email);
        $stmt ->bindParam(':senha',$senha);
        $stmt ->execute();
        exit();



}}
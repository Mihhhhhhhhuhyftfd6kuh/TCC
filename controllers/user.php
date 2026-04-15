<?php
session_start();

function alterar($nome, $email, $senha){
    require __DIR__ . '/../config/config.php';

    $id = $_SESSION['id'] ?? null;
    if (!$id) {
        echo "Loga la o filho da puta";
        return false; // usuário não autenticado
    }

    $sql = "UPDATE usuarios SET nome = :nome, email = :email, senha = :senha WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':nome', $nome);
    $stmt->bindValue(':email', $email);
    $stmt->bindValue(':senha', $senha);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);

    return $stmt->execute();

}
function logout(){   
    session_start(); 
    session_destroy();
    header("location:../public/home.php");
    exit();
    
}
function verificacao_L(){
    require __DIR__ . '/../config/config.php';

    $id = $_SESSION['id'];
    if($id == null){
        echo "ta logado nao paizao";
    }
}


?>

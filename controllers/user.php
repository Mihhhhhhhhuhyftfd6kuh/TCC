<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function alterar(string $nome, string $email, string $senha){
    require __DIR__ . '/../config/config.php';

    $id = $_SESSION['id'] ?? null;
    

    $sql = "UPDATE usuarios SET nome = :nome, email = :email, senha = :senha WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':nome', $nome);
    $stmt->bindValue(':email', $email);
    $stmt->bindValue(':senha', $senha);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);

    return $stmt->execute();

}
function logout(){   
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_unset();
        session_destroy();
    }
    // redireciona para a página pública de home
    header("Location: ../public/home.php");
    exit();
}
    

function verificacao_L(){
    require __DIR__ . '/../config/config.php';

    $id = $_SESSION['id'];
    if($id == null){
        header("location:../public/login.php");
        exit();
    }
}


?>

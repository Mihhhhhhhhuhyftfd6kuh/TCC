<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
function login( $email, $senha){

require __DIR__ . '/../config/config.php';




    $sql = "SELECT * FROM usuarios WHERE email=:email";
    $stmt = $pdo->prepare($sql);
    $stmt -> bindParam(':email', $email);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        $_SESSION['id'] = $usuario['id']; // ou 'id' se for esse o nome do campo
        header("Location:../Index.php");
        exit;
    } elseif($senha && $email == !NULL) {
        echo "<div style='color:#776472;'>Nome, email ou senha incorretos.</div>";
    }


}

function cadastrar(string $nome,string $email,string $senha){
    require __DIR__ . '/../config/config.php';


 if($nome == (preg_match('/[^a-zA-Z0-9]/', $nome))){
            echo "nao pode caractere";
            exit();

        }else{
            $sql ="SELECT COUNT(*) FROM usuarios WHERE email=:email";
            $stmt = $pdo->prepare($sql);
            $stmt->bindvalue(':email',$email);
            $stmt-> execute();

            if($stmt->fetchcolumn() >0){
                echo "email ja cadastrado";
                header("location:../public/cadastrar.php");
                
            }else{
               
        $sql = "INSERT INTO usuarios (nome, email, senha) VALUES (:nome, :email, :senha)";
        $stmt = $pdo->prepare($sql);
        $stmt ->bindParam(':nome',$nome);
        $stmt ->bindParam(':email',$email);
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
$stmt->bindParam(':senha', $senhaHash);
        $stmt ->execute();
        exit();
            }



}}

?>
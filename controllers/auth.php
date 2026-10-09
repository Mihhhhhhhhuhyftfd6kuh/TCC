<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function ver_caractere_especial($nome){
    return preg_match('/[^a-zA-Z0-9]/', $nome) > 0;

}
function login( $email, $senha){
    $email = trim((string) $email);
    $senha = (string) $senha;

    if ($email === '' && $senha === '') {
        return 'Preencha o e-mail e a senha para entrar.';
    }
    if ($email === '') {
        return 'Preencha o campo de e-mail.';
    }
    if ($senha === '') {
        return 'Preencha o campo de senha.';
    }

require __DIR__ . '/../config/config.php';




    $sql = "SELECT * FROM usuarios WHERE email=:email";
    $stmt = $pdo->prepare($sql);
    $stmt -> bindParam(':email', $email);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        // novo ID de sessão a cada login: nunca reaproveita a sessão de outra pessoa
        session_regenerate_id(true);
        $_SESSION = [];
        $_SESSION['id'] = $usuario['id'];
        header("Location:../public/home.php");
        exit;
    } elseif($senha && $email == !NULL) {
        // Em vez de ecoar HTML cru (sem estilo, fora do <body>), devolve a
        // mensagem pra tela de login mostrar dentro do próprio layout.
        return 'Nome, email ou senha incorretos.';
    }

    return null;
}

function cadastrar( $nome, $email, $senha){
    require __DIR__ . '/../config/config.php';

    $nome  = trim((string) $nome);
    $email = trim((string) $email);
    $senha = (string) $senha;

    if ($nome === '' && $email === '' && $senha === '') {
        return 'Preencha nome, e-mail e senha para se cadastrar.';
    }
    if ($nome === '') {
        return 'Preencha o campo de nome.';
    }
    if ($email === '') {
        return 'Preencha o campo de e-mail.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Digite um e-mail válido.';
    }
    if ($senha === '') {
        return 'Preencha o campo de senha.';
    }


 
    

        
        
            $sql ="SELECT COUNT(*) FROM usuarios WHERE email=:email";
            $stmt = $pdo->prepare($sql);
            $stmt->bindvalue(':email',$email);
            $stmt-> execute();

            if($stmt->fetchcolumn() >0){
                // Devolve a mensagem pra tela de cadastro mostrar com CSS,
                // em vez de ecoar texto cru antes do <!DOCTYPE>.
                return 'E-mail já cadastrado.';
            }else{
               
        $sql = "INSERT INTO usuarios (nome,email,senha) VALUES (:nome,:email,:senha)";
        $stmt = $pdo->prepare($sql);
        $stmt ->bindParam(':nome',$nome);
        $stmt ->bindParam(':email',$email);
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt->bindParam(':senha', $senhaHash);
        $stmt ->execute();
        // ?cadastrado=1 faz o login.php mostrar o aviso "Você foi cadastrado"
        header("location:../public/login.php?cadastrado=1");
        exit();

    
            }



}

?>
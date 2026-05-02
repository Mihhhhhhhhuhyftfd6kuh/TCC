<?php
    session_start();
    
    require '../../controllers/contact.php';
    require '../../config/config.php';
    require '../../controllers/user.php';

    verificacao_L();
    
    if(!isset($_SESSION['id']) || empty($_SESSION['id'])){
        header("Location: ../login.php");
        exit();
    }

    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

    
    if($_SESSION['id'] == 1){
        if($id === null){
            echo "ID de usuário não fornecido!";
            exit();
        }
        $id_usuario = $id;
    } else {
       
        $id_usuario = $_SESSION['id'];
    }


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mensagem = trim($_POST['mensagem'] ?? '');

    if ($mensagem === '') {
        $_SESSION['flash_error'] = 'Mensagem vazia.';
        header("Location: ?id={$id_usuario}");
        exit();
    }

    if ($_SESSION['id'] == 1) {
        // Admin envia para o usuário selecionado
        $id_remetente = $_SESSION['id'];
        $id_destinatario = $id_usuario;
        $success = criar($mensagem, $id_remetente, $id_destinatario);
    } else {
        // Usuário comum envia para admin (1)
        $id_remetente = $_SESSION['id'];
        $success = criar($mensagem, $id_remetente);
    }

    if (!empty($success)) {
        $_SESSION['flash_success'] = 'Mensagem enviada.';
    } else {
        $_SESSION['flash_error'] = 'Erro ao enviar.';
    }

    // PRG: redirect para evitar reenvio
    header("Location: ?id={$id_usuario}");
    exit();
}

// buscar conversa
$conversa = imprimir_m($id_usuario);

// mostrar flashes
$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conversa</title>
</head>
<body>
    <header>
        <a href="../home.php">home</a>
         <a href="../perfil.php">perfil</a>
    </header>
    <h1>Conversa com usuário ID: <?php echo $id_usuario; ?></h1>

    <div class="messages">
        <?php 
            if($conversa && count($conversa) > 0):
                foreach($conversa as $msg):
        ?>
            <div class="message" >
                <strong><?= htmlspecialchars($msg['nome']) ?></strong>
                <p><?= htmlspecialchars($msg['mensagem']) ?></p>
                <small ><?= $msg['created_at'] ?></small>
            </div>
        <?php
        
                endforeach;
            endif;
        ?>
           
        
           
        </div>


        <div class="c_mensagem">

            <?php if($flash_success): ?>
                <div style="color:green; margin:8px 0"><?php echo $flash_success; ?></div>
            <?php endif; ?>
            <?php if($flash_error): ?>
                <div style="color:red; margin:8px 0"><?php echo $flash_error; ?></div>
            <?php endif; ?>

            <form action="?id=<?php echo $id_usuario; ?>" method="post">

                <textarea name="mensagem" id="mensagem" placeholder="Digite sua mensagem..."></textarea>
                <button type="submit">Enviar</button>
            </form>


        </div>

    <a href="../logout.php" style="display:inline-block;padding:8px 12px;background:#c00;color:#fff;text-decoration:none;border-radius:4px;">SAIR</a>

</body>
</html>
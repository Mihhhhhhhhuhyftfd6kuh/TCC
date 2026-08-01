<?php
    session_start();

    require '../../controllers/contact.php';
    require '../../config/config.php';
    require '../../controllers/user.php';

    verificacao_L();

    if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
        header("Location: ../login.php");
        exit();
    }

    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

    if ($_SESSION['id'] == 1) {
        if ($id === null) {
            echo "ID de usuário não fornecido!";
            exit();
        }
        $id_usuario = $id;
    } else {
        $id_usuario = $_SESSION['id'];
    }

    /**
     * CORREÇÃO 8 — Buscar o nome do usuário logado para enviar via WebSocket.
     *
     * Antes: a página não sabia o nome de quem estava logado, então o
     * JavaScript não conseguia incluir o nome correto na mensagem enviada
     * ao servidor WebSocket. O destinatário receberia mensagens sem
     * identificação de quem as enviou.
     */
    $sqlNome = "SELECT nome FROM usuarios WHERE id = :id";
    $stmtNome = $pdo->prepare($sqlNome);
    $stmtNome->bindParam(':id', $_SESSION['id'], PDO::PARAM_INT);
    $stmtNome->execute();
    $usuarioAtual = $stmtNome->fetch(PDO::FETCH_ASSOC);
    $nomeAtual = $usuarioAtual['nome'] ?? 'Usuário';

    // Buscar histórico de mensagens do banco ao carregar a página
    $conversa = imprimir_m($id_usuario);

    $flash_success = $_SESSION['flash_success'] ?? null;
    $flash_error   = $_SESSION['flash_error']   ?? null;
    unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conversa</title>
    <style>
        .messages   { height: 400px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; margin-bottom: 10px; }
        .message    { margin-bottom: 12px; }
        .message strong { display: block; font-size: 0.85em; color: #555; }
        .message p  { margin: 2px 0; }
        .message small  { color: #999; font-size: 0.75em; }
        #ws-status  { font-size: 0.8em; padding: 4px 8px; border-radius: 4px; display: inline-block; margin-bottom: 8px; }
        .online  { background: #d4edda; color: #155724; }
        .offline { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <header>
        <a href="../home.php">home</a>
        <a href="../perfil.php">perfil</a>
    </header>

    <h1>Conversa com usuário ID: <?php echo $id_usuario; ?></h1>

    <span id="ws-status" class="offline">⚫ Desconectado</span>

    <div class="messages" id="messages-container">
        <?php if ($conversa && count($conversa) > 0): ?>
            <?php foreach ($conversa as $msg): ?>
                <div class="message">
                    <strong><?= htmlspecialchars($msg['nome']) ?></strong>
                    <p><?= htmlspecialchars($msg['mensagem']) ?></p>
                    <small><?= htmlspecialchars($msg['created_at']) ?></small>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="c_mensagem">
        <?php if ($flash_success): ?>
            <div style="color:green; margin:8px 0"><?php echo $flash_success; ?></div>
        <?php endif; ?>
        <?php if ($flash_error): ?>
            <div style="color:red; margin:8px 0"><?php echo $flash_error; ?></div>
        <?php endif; ?>

        <!--
            CORREÇÃO 9 — O formulário agora é interceptado pelo JavaScript.
            Se o WebSocket estiver conectado, a mensagem é enviada em tempo real.
            Se não estiver (servidor parado), o form faz POST normal como fallback.
        -->
        <form id="form-mensagem" action="?id=<?php echo $id_usuario; ?>" method="post">
            <textarea name="mensagem" id="mensagem" placeholder="Digite sua mensagem..." rows="3" style="width:100%"></textarea>
            <button type="submit">Enviar</button>
        </form>
    </div>

    <a href="../logout.php" style="display:inline-block;padding:8px 12px;background:#c00;color:#fff;text-decoration:none;border-radius:4px;margin-top:10px;">SAIR</a>

    



    
</body>
</html>

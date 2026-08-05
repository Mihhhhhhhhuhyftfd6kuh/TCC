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

    <span id="chat-status" class="offline">⚫ Atualizando...</span>

    <div class="messages" id="messages-container" data-id-usuario="<?= (int) $id_usuario ?>" data-meu-id="<?= (int) $_SESSION['id'] ?>">
        <?php if ($conversa && count($conversa) > 0): ?>
            <?php foreach ($conversa as $msg): ?>
                <div class="message" data-mensagem-id="<?= (int) $msg['id'] ?>">
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
            CORREÇÃO 9 — O formulário agora é enviado via AJAX (fetch),
            sem recarregar a página. Enquanto isso, o chat também busca
            mensagens novas periodicamente (polling) para mostrar as
            respostas do outro lado em tempo quase real.
        -->
        <form id="form-mensagem">
            <textarea name="mensagem" id="mensagem" placeholder="Digite sua mensagem..." rows="3" style="width:100%"></textarea>
            <button type="submit">Enviar</button>
        </form>
    </div>

    <a href="../logout.php" style="display:inline-block;padding:8px 12px;background:#c00;color:#fff;text-decoration:none;border-radius:4px;margin-top:10px;">SAIR</a>

    <script>
    (() => {
        const container   = document.getElementById('messages-container');
        const form         = document.getElementById('form-mensagem');
        const textarea      = document.getElementById('mensagem');
        const statusEl       = document.getElementById('chat-status');

        const idUsuario = container.dataset.idUsuario;
        const meuId     = container.dataset.meuId;

        const INTERVALO_ATUALIZACAO = 3000; // 3 segundos

        // Rola o container de mensagens até o final
        function irParaFinal() {
            container.scrollTop = container.scrollHeight;
        }

        // Redesenha todas as mensagens recebidas do servidor.
        // Simples e seguro: sempre reflete exatamente o que está no banco.
        function renderizarMensagens(mensagens) {
            const idsAtuais = Array.from(container.querySelectorAll('.message'))
                .map(el => el.dataset.mensagemId);

            const idsNovos = mensagens.map(m => String(m.id));

            // Se nada mudou (mesma quantidade/mesmos ids), não redesenha
            const igual = idsAtuais.length === idsNovos.length &&
                          idsAtuais.every((id, i) => id === idsNovos[i]);
            if (igual) return;

            container.innerHTML = mensagens.map(m => `
                <div class="message" data-mensagem-id="${m.id}">
                    <strong>${m.nome}</strong>
                    <p>${m.mensagem}</p>
                    <small>${m.created_at}</small>
                </div>
            `).join('');

            irParaFinal();
        }

        // Busca as mensagens no servidor (AJAX / fetch) e atualiza a tela
        async function buscarMensagens() {
            try {
                const resp = await fetch(`buscar_mensagens.php?id=${idUsuario}`, {
                    method: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (!resp.ok) throw new Error('Falha ao buscar mensagens');

                const dados = await resp.json();

                if (dados.sucesso) {
                    renderizarMensagens(dados.mensagens);
                    statusEl.textContent = '🟢 Atualizado';
                    statusEl.className = 'online';
                } else {
                    throw new Error(dados.erro || 'Erro desconhecido');
                }
            } catch (erro) {
                statusEl.textContent = '⚫ Sem conexão';
                statusEl.className = 'offline';
                console.error('Erro ao buscar mensagens:', erro);
            }
        }

        // Envia a mensagem via AJAX, sem recarregar a página
        async function enviarMensagem(mensagem) {
            const corpo = new URLSearchParams();
            corpo.append('mensagem', mensagem);
            corpo.append('id', idUsuario);

            const resp = await fetch('enviar_mensagem.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: corpo.toString()
            });

            const dados = await resp.json();

            if (!dados.sucesso) {
                throw new Error(dados.erro || 'Não foi possível enviar a mensagem');
            }
        }

        form.addEventListener('submit', async (evento) => {
            evento.preventDefault();

            const texto = textarea.value.trim();
            if (texto === '') return;

            try {
                await enviarMensagem(texto);
                textarea.value = '';
                // Atualiza o chat imediatamente após enviar,
                // sem esperar o próximo ciclo do polling
                await buscarMensagens();
            } catch (erro) {
                alert('Erro ao enviar mensagem: ' + erro.message);
            }
        });

        // Primeira busca imediata + polling contínuo
        buscarMensagens();
        setInterval(buscarMensagens, INTERVALO_ATUALIZACAO);
    })();
    </script>
</body>
</html>
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
    $ehAdmin = ($_SESSION['id'] == 1);

    if ($ehAdmin) {
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

    // Histórico lateral: só o admin tem várias conversas (uma por usuário)
    $listaUsuarios = $ehAdmin ? imprimir_com_conversa() : [];

    $flash_success = $_SESSION['flash_success'] ?? null;
    $flash_error   = $_SESSION['flash_error']   ?? null;
    unset($_SESSION['flash_success'], $_SESSION['flash_error']);

    if(isset($_GET['acao']) && $_GET['acao'] === 'apagar'){
    if(apagar_conversa($id_usuario)){
        $_SESSION['flash_success'] = 'Conversa apagada com sucesso.';
    } else {
        $_SESSION['flash_error'] = 'Erro ao apagar conversa.';
    }
    header("Location: ?id={$id_usuario}");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contato - Crypher.IA</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/contato.css">
</head>
<style>
    /* =========================================
   CRYPHER.IA - CONTATO (CHAT EM TELA CHEIA)
   Histórico à esquerda (só aparece para o admin)
========================================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

:root {
    --azul: #4348D9;
    --azul-escuro: #3539b8;
    --amarelo: #F3BE27;
    --texto: #202020;
    --borda: #e5e5ee;
}


/* =========================================
   PÁGINA (100% da janela, sem rolagem)
========================================= */

html,
body {
    width: 100%;
    height: 100%;
    overflow: hidden;
}

body {
    height: 100dvh;
    background: var(--azul);
    color: var(--texto);

    display: flex;
    flex-direction: column;
}


/* =========================================
   HEADER
========================================= */

header {
    width: 100%;
    height: 78px;
    flex-shrink: 0;

    padding: 0 55px;

    background: var(--azul);

    display: flex;
    align-items: center;

    position: relative;
    z-index: 10;

    box-shadow: 0 5px 20px rgba(0, 0, 0, .12);
}

.logo {
    color: #fff;

    font-size: 2rem;
    font-weight: 700;

    letter-spacing: -.5px;

    text-decoration: none;

    flex-shrink: 0;
}

header nav {
    position: absolute;

    left: 50%;
    top: 50%;

    transform: translate(-50%, -50%);

    display: flex;
    align-items: center;

    gap: 12px;
}

header nav a,
header > a[href*="perfil"] {
    color: rgba(255, 255, 255, .9);

    text-decoration: none;

    font-size: .9rem;
    font-weight: 600;

    padding: 10px 17px;

    border-radius: 999px;

    transition:
        background .25s ease,
        color .25s ease,
        transform .25s ease;
}

header nav a:hover {
    background: var(--);
    color: white;

    
}

nav a:hover{
    opacity:.7;
}

header > a[href*="perfil"] {
    position: absolute;

    right: 55px;
    top: 50%;

    transform: translateY(-50%);
}

header > a[href*="perfil"]:hover {
    background: var(--amarelo);
    color: #222;

    transform: translateY(-50%) translateY(-2px);
}


/* =========================================
   MAIN + LAYOUT (histórico | chat)
========================================= */

main {
    flex: 1;
    min-height: 0;

    background: #fff;
}

.layout {
    height: 100%;

    display: grid;

    grid-template-columns: 0 minmax(0, 1fr);        /* histórico fechado */
    grid-template-rows: minmax(0, 1fr);

    transition: grid-template-columns .3s ease;
}

.layout.historico-aberto {
    grid-template-columns: 320px minmax(0, 1fr);    /* histórico aberto */
}

/* usuário comum: não existe histórico, só o chat */
.layout.sem-historico {
    grid-template-columns: minmax(0, 1fr);
}


/* =========================================
   HISTÓRICO (só admin)
========================================= */

.sidebar {
    height: 100%;
    min-width: 0;

    background: #fafaff;

    border-right: 1px solid var(--borda);

    overflow: hidden;

    visibility: hidden;
}

.layout.historico-aberto .sidebar {
    padding: 18px;

    overflow-y: auto;
    overflow-x: hidden;

    visibility: visible;
}

.historico-topo {
    display: flex;
    align-items: center;
    justify-content: space-between;

    margin-bottom: 16px;
}

.historico-topo h3 {
    color: #333;

    font-size: 1rem;
    font-weight: 700;
}

.btn-fechar-historico {
    width: 32px;
    height: 32px;

    border: none;
    border-radius: 9px;

    background: transparent;

    color: #999;

    cursor: pointer;

    transition:
        background .2s ease,
        color .2s ease;
}

.btn-fechar-historico:hover {
    background: var(--amarelo);
    color: #222;
}

.sidebar-vazia {
    padding: 25px 10px;

    text-align: center;

    color: #999;

    font-size: .82rem;
}

.conversa-item {
    display: flex;
    align-items: center;

    gap: 11px;

    margin-bottom: 7px;
    padding: 11px;

    border-radius: 13px;

    text-decoration: none;
    color: inherit;

    transition: background .2s ease;
}

.conversa-item:hover {
    background: #f0f0fb;
}

.conversa-item.ativa {
    background: rgba(67, 72, 217, .1);
}

.conversa-icone {
    flex-shrink: 0;

    width: 36px;
    height: 36px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;

    background: #eeeeff;
    color: var(--azul);

    font-size: .85rem;
    font-weight: 700;

    text-transform: uppercase;
}

.conversa-item.ativa .conversa-icone {
    background: var(--azul);
    color: #fff;
}

.conversa-texto {
    min-width: 0;

    display: flex;
    flex-direction: column;

    gap: 2px;
}

.conversa-titulo,
.conversa-data {
    display: block;

    overflow: hidden;

    white-space: nowrap;
    text-overflow: ellipsis;
}

.conversa-titulo {
    color: #333;

    font-size: .84rem;
    font-weight: 600;
}

.conversa-data {
    color: #999;

    font-size: .7rem;
}


/* =========================================
   CARD DO CHAT
========================================= */

.chat-card {
    height: 100%;
    min-width: 0;
    min-height: 0;

    background: #fff;

    display: flex;
    flex-direction: column;
}

/* barra do topo: [Histórico] título ........ status */

.chat-topo {
    flex-shrink: 0;

    padding: 14px 55px;

    display: flex;
    align-items: center;

    gap: 14px;

    border-bottom: 1px solid #ededf4;
}

.chat-topo h1 {
    flex: 1;
    min-width: 0;

    overflow: hidden;

    white-space: nowrap;
    text-overflow: ellipsis;

    color: #333;

    font-size: 1.05rem;
    font-weight: 600;
}

.btn-historico {
    flex-shrink: 0;

    height: 38px;

    padding: 0 15px;

    display: flex;
    align-items: center;
    gap: 8px;

    border: 1px solid var(--borda);
    border-radius: 999px;

    background: #fff;

    color: var(--azul);

    font-family: inherit;
    font-size: .78rem;
    font-weight: 600;

    cursor: pointer;

    transition:
        background .2s ease,
        color .2s ease,
        transform .2s ease;
}

.btn-historico:hover {
    background: var(--amarelo);
    color: #222;

    transform: translateY(-1px);
}

/* com o histórico aberto no desktop existe o X dentro do painel */
@media (min-width: 769px) {
    .layout.historico-aberto .btn-historico {
        display: none;
    }
}

/* status (o JS troca a classe entre online/offline) */

#chat-status {
    flex-shrink: 0;

    padding: 5px 13px;

    border-radius: 999px;

    font-size: .7rem;
    font-weight: 600;
}

.online {
    background: #d4edda;
    color: #155724;
}

.offline {
    background: #f8d7da;
    color: #721c24;
}


/* =========================================
   MENSAGENS (só esta área rola)
========================================= */

.messages {
    flex: 1;
    min-height: 0;

    padding: 25px 55px;

    overflow-y: auto;
    overflow-x: hidden;

    scroll-behavior: smooth;

    display: flex;
    flex-direction: column;

    gap: 14px;
}

.messages::-webkit-scrollbar,
.sidebar::-webkit-scrollbar {
    width: 6px;
}

.messages::-webkit-scrollbar-thumb,
.sidebar::-webkit-scrollbar-thumb {
    background: #d1d2e8;

    border-radius: 999px;
}

.messages-vazio {
    margin: auto;

    color: #999;

    font-size: .85rem;
    text-align: center;
}

.message {
    align-self: flex-start;

    width: fit-content;
    max-width: 70%;

    padding: 13px 17px;

    background: #f5f5fa;

    border: 1px solid #ededf4;

    border-radius: 17px 17px 17px 5px;

    box-shadow: 0 4px 12px rgba(0, 0, 0, .05);

    animation: aparecerMensagem .25s ease;
}

/* minhas mensagens ficam à direita */
.message.minha {
    align-self: flex-end;

    background: var(--azul);

    border-color: var(--azul);

    border-radius: 17px 17px 5px 17px;
}

.message strong {
    display: block;

    margin-bottom: 4px;

    color: var(--azul);

    font-size: .8rem;
    font-weight: 700;
}

.message p {
    margin: 3px 0;

    color: #222;

    font-size: .88rem;
    line-height: 1.5;

    white-space: pre-wrap;
    overflow-wrap: anywhere;
}

.message small {
    display: block;

    margin-top: 7px;

    color: #999;

    font-size: .67rem;
}

.message.minha strong,
.message.minha p {
    color: #fff;
}

.message.minha small {
    color: rgba(255, 255, 255, .75);
}

.message a {
    display: inline-block;

    margin-top: 5px;

    padding: 6px 10px;

    background: #eeeef8;

    border-radius: 8px;

    color: var(--azul);

    text-decoration: none;

    font-size: .75rem;
    font-weight: 500;

    transition: .25s ease;
}

.message a:hover {
    background: var(--amarelo);
    color: #222;
}

@keyframes aparecerMensagem {
    from {
        opacity: 0;
        transform: translateY(8px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}


/* =========================================
   ALERTAS + BARRA DE ENVIO (fixa embaixo)
========================================= */

.c_mensagem {
    flex-shrink: 0;

    background: #fff;

    box-shadow: 0 -2px 10px rgba(0, 0, 0, .06);
}

.alerta {
    margin: 10px 55px 0;

    padding: 8px 13px;

    border-radius: 9px;

    font-size: .78rem;
}

.alerta.sucesso {
    background: #d4edda;
    color: #155724;
}

.alerta.erro {
    background: #f8d7da;
    color: #721c24;
}

#form-mensagem {
    padding: 12px 55px;

    display: flex;
    align-items: center;

    gap: 12px;
}

/* [ texto .......... ] [Arquivo] [Enviar] */

#mensagem {
    order: 1;
    flex: 1;
    min-width: 0;

    height: 48px;
    min-height: 48px;
    max-height: 110px;

    padding: 12px 16px;

    border: 2px solid #e5e5ee;
    border-radius: 14px;

    outline: none;

    resize: none;

    background: #fafaff;

    color: #222;

    font-size: .85rem;
    line-height: 1.4;

    transition:
        border-color .2s ease,
        box-shadow .2s ease;
}

#mensagem:focus {
    border-color: var(--azul);

    box-shadow: 0 0 0 3px rgba(67, 72, 217, .08);

    background: #fff;
}

#mensagem::placeholder {
    color: #999;
}

.btn-anexo {
    order: 2;
    flex-shrink: 0;

    height: 48px;

    padding: 0 16px;

    display: flex;
    align-items: center;
    gap: 8px;

    border-radius: 13px;

    background: #f1f1fa;

    color: var(--azul);

    font-size: .8rem;
    font-weight: 600;

    cursor: pointer;

    transition:
        background .2s ease,
        color .2s ease,
        transform .2s ease;
}

.btn-anexo:hover,
.btn-anexo.tem-arquivo {
    background: var(--amarelo);
    color: #222;

    transform: translateY(-1px);
}

.btn-anexo input {
    display: none;
}

.nome-arquivo {
    order: 2;

    max-width: 150px;

    color: #777;

    font-size: .7rem;

    overflow: hidden;

    white-space: nowrap;
    text-overflow: ellipsis;
}

#form-mensagem button[type="submit"] {
    order: 3;
    flex-shrink: 0;

    height: 48px;

    padding: 0 25px;

    border: none;
    border-radius: 999px;

    background: var(--amarelo);
    color: #222;

    font-family: inherit;
    font-size: .85rem;
    font-weight: 700;

    cursor: pointer;

    transition:
        transform .25s ease,
        box-shadow .25s ease;
}

#form-mensagem button[type="submit"]:hover {
    transform: translateY(-2px);

    box-shadow: 0 8px 18px rgba(243, 190, 39, .3);
}

#form-mensagem button[type="submit"]:active {
    transform: scale(.98);
}


/* =========================================
   TABLET
========================================= */

@media (max-width: 1050px) {

    header {
        padding: 0 30px;
    }

    header > a[href*="perfil"] {
        right: 30px;
    }

    .chat-topo,
    .messages {
        padding-left: 30px;
        padding-right: 30px;
    }

    #form-mensagem {
        padding: 10px 30px;
    }

    .alerta {
        margin-left: 30px;
        margin-right: 30px;
    }
}


/* =========================================
   MOBILE (histórico vira gaveta pela esquerda)
========================================= */

@media (max-width: 768px) {

    header {
        height: auto;
        min-height: 70px;

        padding: 12px 18px;

        gap: 12px;

        justify-content: space-between;
    }

    .logo {
        font-size: 1.6rem;
    }

    header nav,
    header > a[href*="perfil"] {
        position: static;
        transform: none;
    }

    header > a[href*="perfil"]:hover {
        transform: translateY(-2px);
    }

    header nav {
        gap: 3px;
    }

    header nav a,
    header > a[href*="perfil"] {
        padding: 8px 10px;

        font-size: .75rem;
    }

    .layout,
    .layout.historico-aberto {
        display: block;
    }

    .sidebar {
        position: fixed;

        top: 0;
        left: 0;

        z-index: 50;

        width: min(320px, 85vw);
        height: 100dvh;

        padding: 18px;

        overflow-y: auto;

        visibility: visible;

        transform: translateX(-100%);

        transition: transform .3s ease;
    }

    .layout.historico-aberto .sidebar {
        transform: translateX(0);

        box-shadow: 10px 0 30px rgba(0, 0, 0, .25);
    }

    .chat-topo {
        padding: 10px 12px;
    }

    .messages {
        padding: 18px 12px;
    }

    .message {
        max-width: 88%;
    }

    .alerta {
        margin: 8px 12px 0;
    }

    #form-mensagem {
        padding: 9px 12px;

        flex-wrap: wrap;

        gap: 7px;
    }

    #mensagem {
        flex: 1 1 100%;
    }

    .btn-anexo {
        flex: 1;

        justify-content: center;

        height: 44px;
    }

    #form-mensagem button[type="submit"] {
        height: 44px;

        padding: 0 20px;
    }
}


/* =========================================
   CELULAR PEQUENO
========================================= */

@media (max-width: 480px) {

    header {
        flex-direction: column;

        align-items: stretch;
    }

    .logo {
        text-align: center;
    }

    header nav {
        width: 100%;

        justify-content: center;
    }

    header nav a {
        flex: 1;

        text-align: center;

        font-size: .72rem;
    }

    .nome-arquivo {
        display: none;
    }
}
</style>
<body>
    <header>
        <a href="../home.php" class="logo">Crypher.IA</a>

        <nav>
            <a href="../home.php">Home</a>
            <a href="../painel_api.php">IA</a>
        </nav>

        <a href="../perfil.php">Perfil</a>
    </header>

    <main>
        <div class="layout <?= $ehAdmin ? '' : 'sem-historico' ?>">

            <?php if ($ehAdmin): ?>
                <aside class="sidebar">
                    <div class="historico-topo">
                        <h3>Conversas</h3>
                        <button type="button" class="btn-fechar-historico" data-toggle-historico title="Fechar histórico">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <?php if (count($listaUsuarios) === 0): ?>
                        <div class="sidebar-vazia">Nenhuma conversa ainda.</div>
                    <?php endif; ?>

                    <?php foreach ($listaUsuarios as $u): ?>
                        <a href="conversa.php?id=<?= (int) $u['id'] ?>" class="conversa-item <?= ((int) $u['id'] === (int) $id_usuario) ? 'ativa' : '' ?>">
                            <span class="conversa-icone"><?= htmlspecialchars(mb_substr($u['nome'], 0, 1)) ?></span>
                            <span class="conversa-texto">
                                <span class="conversa-titulo"><?= htmlspecialchars($u['nome']) ?></span>
                                <span class="conversa-data"><?= htmlspecialchars($u['email']) ?></span>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </aside>
            <?php endif; ?>

            <div class="chat-card">
                <div class="chat-topo">
                    <?php if ($ehAdmin): ?>
                        <button type="button" class="btn-historico" data-toggle-historico>
                            <i class="fa-solid fa-clock-rotate-left"></i> Histórico
                        </button>
                    <?php endif; ?>

                    <h1><?= $ehAdmin ? 'Conversa com usuário ID: ' . (int) $id_usuario : 'Contato' ?></h1>

                    
                </div>

                <div class="messages" id="messages-container" data-id-usuario="<?= (int) $id_usuario ?>" data-meu-id="<?= (int) $_SESSION['id'] ?>">
                    <?php if ($conversa && count($conversa) > 0): ?>
                        <?php foreach ($conversa as $msg): ?>
                            <div class="message <?= ((int) $msg['id_remetente'] === (int) $_SESSION['id']) ? 'minha' : '' ?>" data-mensagem-id="<?= (int) $msg['id'] ?>">
                                <strong><?= htmlspecialchars($msg['nome']) ?></strong>
                                <p><?= htmlspecialchars($msg['mensagem']) ?></p>
                                <?php if (!empty($msg['arquivo_url'])): ?>
                                    <p><a href="<?= htmlspecialchars($msg['arquivo_url']) ?>" target="_blank">📎 <?= htmlspecialchars($msg['arquivo_nome']) ?></a></p>
                                <?php endif; ?>
                                <small><?= htmlspecialchars($msg['created_at']) ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="messages-vazio">Nenhuma mensagem ainda. Envie a primeira!</div>
                    <?php endif; ?>
                </div>

                <div class="c_mensagem">
                    <?php if ($flash_success): ?>
                        <div class="alerta sucesso"><?= htmlspecialchars($flash_success) ?></div>
                    <?php endif; ?>
                    <?php if ($flash_error): ?>
                        <div class="alerta erro"><?= htmlspecialchars($flash_error) ?></div>
                    <?php endif; ?>

                    <!--
                        CORREÇÃO 9 — O formulário agora é enviado via AJAX (fetch),
                        sem recarregar a página. Enquanto isso, o chat também busca
                        mensagens novas periodicamente (polling) para mostrar as
                        respostas do outro lado em tempo quase real.

                        CORREÇÃO 10 — Adicionado suporte a anexos: enctype
                        multipart/form-data e um input de arquivo, enviados junto
                        com a mensagem via FormData (em vez de URLSearchParams, que
                        não consegue carregar arquivos).
                    -->
                    <form id="form-mensagem" enctype="multipart/form-data">
                        <textarea name="mensagem" id="mensagem" placeholder="Digite sua mensagem..." rows="1"></textarea>

                        <label class="btn-anexo" id="btn-anexo" title="Anexar arquivo">
                            <i class="fa-solid fa-upload"></i> Arquivo
                            <input type="file" name="arquivo" id="arquivo-mensagem">
                        </label>

                        <span class="nome-arquivo" id="nome-arquivo"></span>

                        <button type="submit">Enviar</button>
                    </form>
                </div>
            </div>

        </div>
    </main>

    <script>
    (() => {
        const container    = document.getElementById('messages-container');
        const form          = document.getElementById('form-mensagem');
        const textarea       = document.getElementById('mensagem');
        const inputArquivo    = document.getElementById('arquivo-mensagem');
        const statusEl        = document.getElementById('chat-status');
        const btnAnexo        = document.getElementById('btn-anexo');
        const nomeArquivoEl   = document.getElementById('nome-arquivo');

        const idUsuario = container.dataset.idUsuario;
        const meuId     = container.dataset.meuId;

        const INTERVALO_ATUALIZACAO = 3000; // 3 segundos

        // Histórico lateral (só existe para o admin)
        const layout = document.querySelector('.layout');
        if (document.querySelector('.sidebar')) {
            // no computador começa aberto; no celular começa fechado
            if (window.innerWidth > 768) layout.classList.add('historico-aberto');

            document.querySelectorAll('[data-toggle-historico]').forEach(btn => {
                btn.addEventListener('click', () => layout.classList.toggle('historico-aberto'));
            });
        }

        // Mostra o nome do arquivo escolhido e destaca o botão
        inputArquivo.addEventListener('change', () => {
            const arquivo = inputArquivo.files[0];
            nomeArquivoEl.textContent = arquivo ? arquivo.name : '';
            btnAnexo.classList.toggle('tem-arquivo', !!arquivo);
        });

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
                <div class="message ${m.minha ? 'minha' : ''}" data-mensagem-id="${m.id}">
                    <strong>${m.nome}</strong>
                    <p>${m.mensagem}</p>
                    ${m.arquivo_url ? `<p><a href="${m.arquivo_url}" target="_blank">📎 ${m.arquivo_nome}</a></p>` : ''}
                    <small>${m.created_at}</small>
                </div>
            `).join('');

            irParaFinal();
        }

        // Busca as mensagens no servidor (AJAX / fetch) e atualiza a tela
        async function buscarMensagens() {
            try {
                const resp = await fetch(`buscar_mensagem.php?id=${idUsuario}`, {
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

        // Envia a mensagem (e o anexo, se houver) via AJAX, sem recarregar a página
        async function enviarMensagem(mensagem, arquivo) {
            const corpo = new FormData();
            corpo.append('mensagem', mensagem);
            corpo.append('id', idUsuario);
            if (arquivo) corpo.append('arquivo', arquivo);

            const resp = await fetch('enviar_mensagem.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                // sem Content-Type manual: o navegador define o boundary certo sozinho
                body: corpo
            });

            const dados = await resp.json();

            if (!dados.sucesso) {
                throw new Error(dados.erro || 'Não foi possível enviar a mensagem');
            }
        }

        form.addEventListener('submit', async (evento) => {
            evento.preventDefault();

            const texto = textarea.value.trim();
            const arquivo = inputArquivo.files[0];
            if (texto === '' && !arquivo) return;

            try {
                await enviarMensagem(texto, arquivo);
                textarea.value = '';
                inputArquivo.value = '';
                nomeArquivoEl.textContent = '';
                btnAnexo.classList.remove('tem-arquivo');
                // Atualiza o chat imediatamente após enviar,
                // sem esperar o próximo ciclo do polling
                await buscarMensagens();
            } catch (erro) {
                alert('Erro ao enviar mensagem: ' + erro.message);
            }
        });

        // Primeira busca imediata + polling contínuo
        irParaFinal();
        buscarMensagens();
        setInterval(buscarMensagens, INTERVALO_ATUALIZACAO);
    })();
    </script>
</body>
</html>
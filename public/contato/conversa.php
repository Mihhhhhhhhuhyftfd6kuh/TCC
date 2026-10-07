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
     * Buscar o nome do usuário logado (usado na bolinha do perfil).
     */
    $sqlNome = "SELECT nome FROM usuarios WHERE id = :id";
    $stmtNome = $pdo->prepare($sqlNome);
    $stmtNome->bindParam(':id', $_SESSION['id'], PDO::PARAM_INT);
    $stmtNome->execute();
    $usuarioAtual = $stmtNome->fetch(PDO::FETCH_ASSOC);
    $nomeAtual = $usuarioAtual['nome'] ?? 'Usuário';

    // Inicial exibida na bolinha do perfil
    $inicialUsuario = mb_strtoupper(mb_substr($nomeAtual, 0, 1));

    // Buscar histórico de mensagens do banco ao carregar a página
    $conversa = imprimir_m($id_usuario);

    // Lista lateral: só o admin tem várias conversas (uma por usuário)
    $listaUsuarios = $ehAdmin ? imprimir_com_conversa() : [];

    // Título do chat
    $tituloChat = 'Suporte Crypher';
    if ($ehAdmin) {
        $tituloChat = 'Usuário #' . (int) $id_usuario;
        foreach ($listaUsuarios as $u) {
            if ((int) $u['id'] === (int) $id_usuario) {
                $tituloChat = $u['nome'];
                break;
            }
        }
    }

    // Link da aba "Contato" na barra inferior do celular
    $linkContato = $ehAdmin ? 'admin.php' : 'conversa.php';

    $flash_success = $_SESSION['flash_success'] ?? null;
    $flash_error   = $_SESSION['flash_error']   ?? null;
    unset($_SESSION['flash_success'], $_SESSION['flash_error']);

    if (isset($_GET['acao']) && $_GET['acao'] === 'apagar') {
        if (apagar_conversa($id_usuario)) {
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, interactive-widget=resizes-content">
    <meta name="theme-color" content="#4348D9">
    <title>Contato - Crypher.IA</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../assets/img/mascote_s_fundo.png">



    <style>
/* =========================================
   CRYPHER.IA - CONTATO
   Desktop: banner + coluna lateral + thread
   Mobile: app de chat em tela cheia com
   barra de navegação inferior
========================================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;

    -webkit-tap-highlight-color: transparent;
}

:root {
    --azul: #4348D9;
    --azul-escuro: #3539b8;
    --azul-claro: #eeeeff;
    --amarelo: #F3BE27;
    --fundo: #f3f3fa;
    --texto: #202020;
    --borda: #e5e5ee;
}

a {
    text-decoration: none;
    color: inherit;
}


/* =========================================
   PÁGINA (sem rolagem)
========================================= */

html,
body {
    width: 100%;
    height: 100%;
    overflow: hidden;
}

body {
    height: 100dvh;
    background: var(--fundo);
    color: var(--texto);

    display: flex;
    flex-direction: column;
}


/* =========================================
   HEADER (igual ao da Home)
========================================= */

header {
    width: 100%;
    height: 78px;
    flex-shrink: 0;

    padding: 0 55px;

    background: var(--azul);

    display: flex;
    align-items: center;
    justify-content: space-between;

    position: relative;
    z-index: 10;
}

.logo {
    color: #fff;

    font-size: 2rem;
    font-weight: 700;

    letter-spacing: -.5px;

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

header nav a {
    color: rgba(255, 255, 255, .9);

    font-size: .9rem;
    font-weight: 600;

    padding: 10px 17px;

    border-radius: 999px;

    transition:
        background .25s ease,
        color .25s ease;
}

header nav a:hover {
    background: rgba(255, 255, 255, .15);
    color: #fff;
}

/* bolinha da conta (mesmo estilo da Home) */

.conta-menu {
    position: relative;

    flex-shrink: 0;
}

.conta-botao {
    width: 46px;
    height: 46px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #fff;
    color: #4348D9;

    border: none;
    border-radius: 50%;

    font-family: inherit;
    font-weight: 700;
    font-size: 1.15rem;
    line-height: 1;

    text-transform: uppercase;

    cursor: pointer;

    box-shadow: 0 4px 12px rgba(0, 0, 0, .18);

    transition: .3s ease;
}

.conta-botao:hover {
    transform: translateY(-2px);

    box-shadow: 0 8px 18px rgba(0, 0, 0, .25);
}

.conta-dropdown {
    display: none;

    position: absolute;

    top: calc(100% + 10px);
    right: 0;

    min-width: 190px;

    background: #fff;

    border-radius: 12px;

    overflow: hidden;

    box-shadow: 0 15px 35px rgba(0, 0, 0, .22);

    z-index: 200;
}

.conta-dropdown.ativo {
    display: block;
}

.conta-dropdown a {
    display: flex;
    align-items: center;

    gap: 10px;

    padding: 13px 20px;

    color: #222;

    font-size: .9rem;
    font-weight: 500;

    transition: .2s ease;
}

.conta-dropdown a i {
    width: 16px;

    text-align: center;

    color: var(--azul);
}

.conta-dropdown a:hover {
    background: #f2f2f2;
}


/* barra de navegação inferior: só existe no celular */

.nav-mobile {
    display: none;
}


/* =========================================
   BANNER (título da página)
========================================= */

.hero {
    flex-shrink: 0;

    padding: 4px 55px 72px;

    background: var(--azul);

    color: #fff;
}

.hero-conteudo {
    max-width: 1250px;
    margin: 0 auto;
}

.hero h2 {
    font-size: 1.6rem;
    font-weight: 700;

    letter-spacing: -.3px;
}

.hero h2 span {
    color: var(--amarelo);
}

.hero p {
    margin-top: 2px;

    color: rgba(255, 255, 255, .8);

    font-size: .85rem;
}


/* =========================================
   MAIN + LAYOUT (cartões sobre o banner)
========================================= */

main {
    flex: 1;
    min-height: 0;

    margin-top: -46px;
    padding: 0 55px 24px;

    position: relative;
    z-index: 5;
}

.layout {
    height: 100%;
    max-width: 1250px;
    margin: 0 auto;

    display: grid;

    grid-template-columns: 300px minmax(0, 1fr);
    grid-template-rows: minmax(0, 1fr);

    gap: 20px;
}

/* fundo escuro atrás da gaveta (só aparece no celular) */
.overlay {
    display: none;
}


/* =========================================
   COLUNA LATERAL
========================================= */

.lateral {
    min-width: 0;
    min-height: 0;

    display: flex;
    flex-direction: column;

    gap: 16px;

    overflow-y: auto;
    overflow-x: hidden;
}

.lateral::-webkit-scrollbar,
.messages::-webkit-scrollbar {
    width: 6px;
}

.lateral::-webkit-scrollbar-thumb,
.messages::-webkit-scrollbar-thumb {
    background: #d1d2e8;

    border-radius: 999px;
}

.card {
    padding: 20px;

    background: #fff;

    border-radius: 20px;

    box-shadow: 0 10px 30px rgba(67, 72, 217, .10);
}

.card-icone {
    width: 42px;
    height: 42px;

    margin-bottom: 12px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 13px;

    background: var(--amarelo);
    color: #222;

    font-size: 1.05rem;
}

.card h3 {
    margin-bottom: 6px;

    color: #222;

    font-size: .98rem;
    font-weight: 700;
}

.card p {
    color: #777;

    font-size: .8rem;
    line-height: 1.55;
}

.dicas {
    margin-top: 4px;

    list-style: none;
}

.dicas li {
    display: flex;
    align-items: flex-start;

    gap: 9px;

    padding: 7px 0;

    color: #666;

    font-size: .8rem;
    line-height: 1.45;
}

.dicas li i {
    margin-top: 3px;

    color: var(--azul);

    font-size: .72rem;
}

/* lista de conversas (admin) */

.card-conversas {
    flex: 1;
    min-height: 0;

    display: flex;
    flex-direction: column;

    padding: 18px 12px;
}

.conversas-topo {
    display: flex;
    align-items: center;
    justify-content: space-between;

    margin-bottom: 10px;
    padding: 0 8px;
}

.conversas-topo h3 {
    margin: 0;
}

.btn-fechar-lateral {
    display: none;

    width: 40px;
    height: 40px;

    align-items: center;
    justify-content: center;

    border: none;
    border-radius: 50%;

    background: var(--azul-claro);

    color: var(--azul);

    cursor: pointer;
}

.conversas-lista {
    flex: 1;
    min-height: 0;

    overflow-y: auto;
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

    margin-bottom: 4px;
    padding: 10px;

    border-left: 4px solid transparent;
    border-radius: 6px 14px 14px 6px;

    transition: background .2s ease;
}

.conversa-item:hover {
    background: #f6f6fd;
}

.conversa-item.ativa {
    background: var(--azul-claro);

    border-left-color: var(--azul);
}

.conversa-icone {
    flex-shrink: 0;

    width: 38px;
    height: 38px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;

    background: var(--azul-claro);
    color: var(--azul);

    font-size: .9rem;
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

    font-size: .85rem;
    font-weight: 600;
}

.conversa-data {
    color: #999;

    font-size: .7rem;
}


/* =========================================
   CARTÃO DO CHAT
========================================= */

.chat-card {
    min-width: 0;
    min-height: 0;

    background: #fff;

    border-radius: 22px;

    box-shadow: 0 10px 30px rgba(67, 72, 217, .10);

    display: flex;
    flex-direction: column;

    overflow: hidden;
}

.chat-topo {
    flex-shrink: 0;

    padding: 16px 26px;

    display: flex;
    align-items: center;

    gap: 12px;

    border-bottom: 3px solid var(--amarelo);
}

.chat-topo-icone {
    flex-shrink: 0;

    width: 38px;
    height: 38px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 11px;

    background: var(--azul);
    color: #fff;

    font-size: .95rem;
}

.chat-topo h1 {
    flex: 1;
    min-width: 0;

    overflow: hidden;

    white-space: nowrap;
    text-overflow: ellipsis;

    color: #222;

    font-size: 1rem;
    font-weight: 700;
}

.btn-lateral {
    display: none;

    flex-shrink: 0;

    height: 40px;

    padding: 0 14px;

    align-items: center;
    gap: 7px;

    border: none;
    border-radius: 999px;

    background: var(--azul-claro);

    color: var(--azul);

    font-family: inherit;
    font-size: .76rem;
    font-weight: 600;

    cursor: pointer;
}

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
   MENSAGENS (formato de thread)
========================================= */

.messages {
    flex: 1;
    min-height: 0;

    padding: 10px 14px;

    overflow-y: auto;
    overflow-x: hidden;

    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;

    scroll-behavior: smooth;

    display: flex;
    flex-direction: column;
}

.messages-vazio {
    margin: auto;

    text-align: center;

    color: #999;

    font-size: .85rem;
}

.message {
    display: flex;

    gap: 13px;

    padding: 12px 14px;

    border-left: 4px solid transparent;
    border-radius: 4px 14px 14px 4px;

    transition: background .2s ease;

    animation: aparecerMensagem .25s ease;
}

.message:hover {
    background: #fafaff;
}

.message.minha {
    border-left-color: var(--amarelo);
}

.msg-avatar {
    flex-shrink: 0;

    width: 40px;
    height: 40px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 12px;

    background: var(--azul-claro);
    color: var(--azul);

    font-size: .95rem;
    font-weight: 700;

    text-transform: uppercase;
}

.message.minha .msg-avatar {
    background: var(--amarelo);
    color: #222;
}

.msg-corpo {
    flex: 1;
    min-width: 0;
}

.msg-topo {
    display: flex;
    align-items: baseline;
    flex-wrap: wrap;

    gap: 3px 10px;
}

.msg-topo strong {
    color: #222;

    font-size: .88rem;
    font-weight: 700;
}

.msg-topo small {
    color: #aaa;

    font-size: .7rem;
}

.message p {
    margin-top: 3px;

    color: #444;

    font-size: .9rem;
    line-height: 1.55;

    white-space: pre-wrap;
    overflow-wrap: anywhere;
}

.message a {
    display: inline-flex;
    align-items: center;

    max-width: 100%;

    margin-top: 5px;

    padding: 7px 12px;

    background: var(--azul-claro);

    border-radius: 10px;

    color: var(--azul);

    font-size: .76rem;
    font-weight: 600;

    overflow-wrap: anywhere;

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
   ALERTAS + CAIXA DE ENVIO
========================================= */

.c_mensagem {
    flex-shrink: 0;

    padding: 0 20px 18px;

    background: #fff;
}

.alerta {
    margin-bottom: 10px;

    padding: 8px 13px;

    border-radius: 10px;

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
    border: 2px solid var(--borda);
    border-radius: 16px;

    background: #fafaff;

    overflow: hidden;

    transition:
        border-color .2s ease,
        box-shadow .2s ease,
        background .2s ease;
}

#form-mensagem:focus-within {
    border-color: var(--azul);

    background: #fff;

    box-shadow: 0 0 0 3px rgba(67, 72, 217, .08);
}

#mensagem {
    display: block;

    width: 100%;
    min-height: 62px;
    max-height: 130px;

    padding: 14px 16px 6px;

    border: none;
    outline: none;

    resize: none;

    background: transparent;

    color: #222;

    font-size: .88rem;
    line-height: 1.5;
}

#mensagem::placeholder {
    color: #a0a0b0;
}

.form-rodape {
    display: flex;
    align-items: center;

    gap: 12px;

    padding: 8px 10px 10px 12px;
}

.btn-anexo {
    flex-shrink: 0;

    height: 38px;

    padding: 0 14px;

    display: flex;
    align-items: center;
    gap: 7px;

    border-radius: 10px;

    background: #fff;
    border: 1px solid var(--borda);

    color: var(--azul);

    font-size: .78rem;
    font-weight: 600;

    cursor: pointer;

    transition:
        background .2s ease,
        color .2s ease,
        border-color .2s ease;
}

.btn-anexo:hover,
.btn-anexo.tem-arquivo {
    background: var(--amarelo);
    border-color: var(--amarelo);
    color: #222;
}

.btn-anexo input {
    display: none;
}

.nome-arquivo {
    max-width: 180px;

    color: #777;

    font-size: .72rem;

    overflow: hidden;

    white-space: nowrap;
    text-overflow: ellipsis;
}

.dica-envio {
    flex: 1;

    text-align: right;

    color: #b0b0c0;

    font-size: .7rem;
}

#form-mensagem button[type="submit"] {
    flex-shrink: 0;

    height: 40px;

    padding: 0 22px;

    display: flex;
    align-items: center;
    gap: 8px;

    border: none;
    border-radius: 11px;

    background: var(--amarelo);
    color: #222;

    font-family: inherit;
    font-size: .84rem;
    font-weight: 700;

    cursor: pointer;

    transition:
        transform .25s ease,
        box-shadow .25s ease;
}

#form-mensagem button[type="submit"]:hover {
    transform: translateY(-2px);

    box-shadow: 0 8px 18px rgba(243, 190, 39, .35);
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

    .hero {
        padding-left: 30px;
        padding-right: 30px;
    }

    main {
        padding-left: 30px;
        padding-right: 30px;
    }

    .layout {
        grid-template-columns: 250px minmax(0, 1fr);

        gap: 16px;
    }
}


/* =========================================
   MOBILE (app de chat em tela cheia)
========================================= */

@media (max-width: 768px) {

    /* ---- header compacto: logo + bolinha da conta ---- */

    header {
        height: calc(58px + env(safe-area-inset-top));

        padding: env(safe-area-inset-top) 18px 0;
    }

    .logo {
        font-size: 1.4rem;
    }

    header nav {
        display: none;
    }

    .conta-botao {
        width: 40px;
        height: 40px;

        font-size: 1rem;
    }

    .conta-botao:hover {
        transform: none;
    }

    .conta-dropdown {
        top: calc(100% + 8px);

        min-width: 180px;
    }

    /* ---- banner some: o título fica no topo do chat ---- */

    .hero {
        display: none;
    }

    /* ---- chat ocupa todo o espaço ---- */

    main {
        margin-top: 0;
        padding: 0;
    }

    .layout {
        display: block;

        max-width: none;
    }

    .chat-card {
        height: 100%;

        border-radius: 0;

        box-shadow: none;
    }

    .chat-topo {
        padding: 9px 12px;

        gap: 10px;

        border-bottom-width: 2px;
    }

    .chat-topo-icone {
        display: none;
    }

    .chat-topo h1 {
        font-size: .95rem;
    }

    #chat-status {
        padding: 4px 10px;

        font-size: .66rem;
    }

    .btn-lateral {
        display: flex;
    }

    /* ---- usuário comum: sem coluna lateral ---- */

    .layout.sem-lateral .lateral {
        display: none;
    }

    /* ---- admin: lista de conversas vira gaveta ---- */

    .lateral {
        position: fixed;

        top: 0;
        left: 0;

        z-index: 50;

        width: min(340px, 88vw);
        height: 100dvh;

        padding: calc(12px + env(safe-area-inset-top)) 12px calc(12px + env(safe-area-inset-bottom));

        background: var(--fundo);

        transform: translateX(-100%);

        transition: transform .3s ease;
    }

    .layout.lateral-aberta .lateral {
        transform: translateX(0);

        box-shadow: 10px 0 30px rgba(0, 0, 0, .25);
    }

    .overlay {
        position: fixed;
        inset: 0;

        z-index: 40;

        display: block;

        background: rgba(20, 20, 60, .5);

        opacity: 0;
        pointer-events: none;

        transition: opacity .3s ease;
    }

    .layout.lateral-aberta .overlay {
        opacity: 1;
        pointer-events: auto;
    }

    .btn-fechar-lateral {
        display: flex;
    }

    .conversa-item {
        padding: 12px 10px;
    }

    /* ---- mensagens ---- */

    .messages {
        padding: 6px 6px;
    }

    .message {
        gap: 10px;

        padding: 10px 8px;
    }

    .msg-avatar {
        width: 34px;
        height: 34px;

        border-radius: 10px;

        font-size: .85rem;
    }

    .msg-topo strong {
        font-size: .84rem;
    }

    .message p {
        font-size: .92rem;
    }

    /* ---- barra de envio: uma linha só ---- */

    .c_mensagem {
        padding: 8px 10px 10px;

        border-top: 1px solid var(--borda);
    }

    .alerta {
        margin-bottom: 8px;
    }

    #form-mensagem {
        display: flex;
        align-items: flex-end;

        gap: 6px;

        padding: 5px;

        border-radius: 26px;
    }

    .form-rodape {
        display: contents;
    }

    .btn-anexo {
        order: 1;

        width: 44px;
        height: 44px;

        padding: 0;

        justify-content: center;

        border: none;
        border-radius: 50%;

        background: #fff;

        font-size: 1.05rem;
    }

    .btn-anexo span {
        display: none;
    }

    #mensagem {
        order: 2;
        flex: 1;
        min-width: 0;

        width: auto;
        min-height: 44px;
        max-height: 120px;

        padding: 11px 4px;

        /* 16px evita o zoom automático do iPhone ao focar */
        font-size: 16px;
    }

    #form-mensagem button[type="submit"] {
        order: 3;

        width: 44px;
        height: 44px;

        padding: 0;

        justify-content: center;

        border-radius: 50%;

        font-size: 1rem;
    }

    #form-mensagem button[type="submit"] span {
        display: none;
    }

    .nome-arquivo,
    .dica-envio {
        display: none;
    }

    /* ---- barra de navegação inferior ---- */

    .nav-mobile {
        flex-shrink: 0;

        display: flex;

        padding-bottom: env(safe-area-inset-bottom);

        background: #fff;

        border-top: 1px solid var(--borda);
    }

    .nav-mobile a {
        flex: 1;

        display: flex;
        flex-direction: column;
        align-items: center;

        gap: 3px;

        padding: 8px 0 7px;

        color: #9a9ab0;

        font-size: .66rem;
        font-weight: 600;

        transition: color .2s ease;
    }

    .nav-mobile a i {
        font-size: 1.12rem;
    }

    .nav-mobile a.ativo {
        color: var(--azul);
    }

    /* bolinha com a inicial na aba Perfil */
    .nav-avatar {
        width: 22px;
        height: 22px;

        display: flex;
        align-items: center;
        justify-content: center;

        border-radius: 50%;

        background: var(--azul);
        color: #fff;

        font-size: .68rem;
        font-weight: 700;

        text-transform: uppercase;
    }

    /* teclado aberto: esconde a barra inferior para sobrar espaço */
    body.teclado .nav-mobile {
        display: none;
    }
}
    </style>
</head>
<body>
    <header>
        <a href="../home.php" class="logo">Crypher.IA</a>

        <nav>
            <a href="../home.php">Home</a>
            <a href="../painel_api.php">IA</a>
        </nav>

        <div class="conta-menu">
            <button type="button" class="conta-botao" id="conta-botao" title="Minha conta" aria-haspopup="true" aria-expanded="false">
                <?= htmlspecialchars($inicialUsuario) ?>
            </button>

            <div class="conta-dropdown" id="conta-dropdown">
                <a href="../perfil.php"><i class="fa-regular fa-user"></i> Meu perfil</a>
                <a href="../logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sair</a>
            </div>
        </div>
    </header>

    <section class="hero">
        <div class="hero-conteudo">
            <?php if ($ehAdmin): ?>
                <h2>Central de <span>atendimento</span></h2>
                <p>Acompanhe e responda as conversas dos usuários.</p>
            <?php else: ?>
                <h2>Fale com a <span>gente</span></h2>
                <p>Tire dúvidas, envie sugestões ou peça ajuda para a nossa equipe.</p>
            <?php endif; ?>
        </div>
    </section>

    <main>
        <div class="layout <?= $ehAdmin ? '' : 'sem-lateral' ?>">

            <?php if ($ehAdmin): ?>
                <div class="overlay" data-toggle-lateral></div>
            <?php endif; ?>

            <aside class="lateral">
                <?php if ($ehAdmin): ?>
                    <div class="card card-conversas">
                        <div class="conversas-topo">
                            <h3>Conversas</h3>
                            <button type="button" class="btn-fechar-lateral" data-toggle-lateral title="Fechar">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <div class="conversas-lista">
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
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-icone"><i class="fa-solid fa-headset"></i></div>
                        <h3>Atendimento direto</h3>
                        <p>Converse com a nossa equipe. Suas mensagens ficam salvas, então você pode voltar aqui quando quiser.</p>
                    </div>

                    <div class="card">
                        <h3>Dicas rápidas</h3>
                        <ul class="dicas">
                            <li><i class="fa-solid fa-circle"></i> Explique o problema com detalhes.</li>
                            <li><i class="fa-solid fa-circle"></i> Anexe prints ou arquivos se ajudar.</li>
                            <li><i class="fa-solid fa-circle"></i> Enter envia, Shift + Enter quebra a linha.</li>
                        </ul>
                    </div>
                <?php endif; ?>
            </aside>

            <div class="chat-card">
                <div class="chat-topo">
                    <?php if ($ehAdmin): ?>
                        <button type="button" class="btn-lateral" data-toggle-lateral>
                            <i class="fa-solid fa-list"></i> Conversas
                        </button>
                    <?php endif; ?>

                    <div class="chat-topo-icone"><i class="fa-regular fa-comments"></i></div>

                    <h1><?= htmlspecialchars($tituloChat) ?></h1>

                    <span id="chat-status" class="online">Conectando...</span>
                </div>

                <div class="messages" id="messages-container" data-id-usuario="<?= (int) $id_usuario ?>" data-meu-id="<?= (int) $_SESSION['id'] ?>">
                    <?php if ($conversa && count($conversa) > 0): ?>
                        <?php foreach ($conversa as $msg): ?>
                            <div class="message <?= ((int) $msg['id_remetente'] === (int) $_SESSION['id']) ? 'minha' : '' ?>" data-mensagem-id="<?= (int) $msg['id'] ?>">
                                <div class="msg-avatar"><?= htmlspecialchars(mb_substr($msg['nome'], 0, 1)) ?></div>
                                <div class="msg-corpo">
                                    <div class="msg-topo">
                                        <strong><?= htmlspecialchars($msg['nome']) ?></strong>
                                        <small><?= htmlspecialchars($msg['created_at']) ?></small>
                                    </div>
                                    <p><?= htmlspecialchars($msg['mensagem']) ?></p>
                                    <?php if (!empty($msg['arquivo_url'])): ?>
                                        <a href="<?= htmlspecialchars($msg['arquivo_url']) ?>" target="_blank">📎 <?= htmlspecialchars($msg['arquivo_nome']) ?></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="messages-vazio">Nenhuma mensagem ainda. Envie a primeira! 👋</div>
                    <?php endif; ?>
                </div>

                <div class="c_mensagem">
                    <?php if ($flash_success): ?>
                        <div class="alerta sucesso"><?= htmlspecialchars($flash_success) ?></div>
                    <?php endif; ?>
                    <?php if ($flash_error): ?>
                        <div class="alerta erro"><?= htmlspecialchars($flash_error) ?></div>
                    <?php endif; ?>

                    <form id="form-mensagem" enctype="multipart/form-data">
                        <textarea name="mensagem" id="mensagem" placeholder="Escreva sua mensagem..." rows="2"></textarea>

                        <div class="form-rodape">
                            <label class="btn-anexo" id="btn-anexo" title="Anexar arquivo">
                                <i class="fa-solid fa-paperclip"></i> <span>Anexar</span>
                                <input type="file" name="arquivo" id="arquivo-mensagem">
                            </label>

                            <span class="nome-arquivo" id="nome-arquivo"></span>

                            <span class="dica-envio">Enter envia · Shift + Enter quebra linha</span>

                            <button type="submit" title="Enviar">
                                <i class="fa-solid fa-paper-plane"></i> <span>Enviar</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </main>

    <!-- Barra de navegação inferior (só aparece no celular) -->
    <nav class="nav-mobile">
        <a href="../home.php">
            <i class="fa-solid fa-house"></i>
            <span>Home</span>
        </a>
        <a href="../painel_api.php">
            <i class="fa-solid fa-robot"></i>
            <span>IA</span>
        </a>
        <a href="<?= $linkContato ?>" class="ativo">
            <i class="fa-regular fa-comments"></i>
            <span>Contato</span>
        </a>
        <a href="../perfil.php">
            <span class="nav-avatar"><?= htmlspecialchars($inicialUsuario) ?></span>
            <span>Perfil</span>
        </a>
    </nav>

    <script>
    (() => {
        const container      = document.getElementById('messages-container');
        const form           = document.getElementById('form-mensagem');
        const textarea       = document.getElementById('mensagem');
        const inputArquivo   = document.getElementById('arquivo-mensagem');
        const statusEl       = document.getElementById('chat-status');
        const btnAnexo       = document.getElementById('btn-anexo');
        const nomeArquivoEl  = document.getElementById('nome-arquivo');
        const contaBotao     = document.getElementById('conta-botao');
        const contaDropdown  = document.getElementById('conta-dropdown');

        const idUsuario = container.dataset.idUsuario;
        const meuId     = container.dataset.meuId;

        const INTERVALO_ATUALIZACAO = 3000; // 3 segundos
        const ehMobile = () => window.innerWidth <= 768;

        // Evita injeção de HTML (XSS) ao desenhar mensagens vindas do servidor
        function escaparHtml(valor) {
            return String(valor ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        // Rola o container de mensagens até o final
        function irParaFinal() {
            container.scrollTop = container.scrollHeight;
        }

        // Menu da bolinha (conta): abre ao clicar, fecha ao clicar fora ou com Esc
        function fecharConta() {
            contaDropdown.classList.remove('ativo');
            contaBotao.setAttribute('aria-expanded', 'false');
        }

        contaBotao.addEventListener('click', (e) => {
            e.stopPropagation();
            const aberto = contaDropdown.classList.toggle('ativo');
            contaBotao.setAttribute('aria-expanded', aberto ? 'true' : 'false');
        });

        document.addEventListener('click', (e) => {
            if (!contaDropdown.contains(e.target)) fecharConta();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') fecharConta();
        });

        // Lista de conversas (admin): no celular abre como gaveta
        const layout = document.querySelector('.layout');
        document.querySelectorAll('[data-toggle-lateral]').forEach(btn => {
            btn.addEventListener('click', () => layout.classList.toggle('lateral-aberta'));
        });

        // Teclado do celular: acompanha a altura visível da tela (corrige o iPhone)
        function ajustarAltura() {
            if (window.visualViewport && ehMobile()) {
                document.body.style.height = window.visualViewport.height + 'px';
                window.scrollTo(0, 0);
                irParaFinal();
            } else {
                document.body.style.height = '';
            }
        }

        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', ajustarAltura);
        }
        window.addEventListener('resize', ajustarAltura);
        ajustarAltura();

        // Esconde a barra inferior enquanto o teclado está aberto
        textarea.addEventListener('focus', () => {
            if (ehMobile()) document.body.classList.add('teclado');
        });
        textarea.addEventListener('blur', () => {
            document.body.classList.remove('teclado');
        });

        // Campo de texto cresce conforme a pessoa digita
        function ajustarTextarea() {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 130) + 'px';
        }
        textarea.addEventListener('input', ajustarTextarea);

        // Mostra o nome do arquivo escolhido e destaca o botão
        inputArquivo.addEventListener('change', () => {
            const arquivo = inputArquivo.files[0];
            nomeArquivoEl.textContent = arquivo ? arquivo.name : '';
            btnAnexo.classList.toggle('tem-arquivo', !!arquivo);
        });

        // Enter envia no computador; no celular o Enter quebra a linha
        // (o envio é pelo botão). Shift+Enter sempre quebra linha.
        textarea.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey && !ehMobile()) {
                e.preventDefault();
                form.requestSubmit();
            }
        });

        // Redesenha as mensagens recebidas do servidor
        function renderizarMensagens(mensagens) {
            const idsAtuais = Array.from(container.querySelectorAll('.message'))
                .map(el => el.dataset.mensagemId);

            const idsNovos = mensagens.map(m => String(m.id));

            // Se nada mudou, não redesenha
            const igual = idsAtuais.length === idsNovos.length &&
                          idsAtuais.every((id, i) => id === idsNovos[i]);
            if (igual) return;

            if (mensagens.length === 0) {
                container.innerHTML = '<div class="messages-vazio">Nenhuma mensagem ainda. Envie a primeira! 👋</div>';
                return;
            }

            container.innerHTML = mensagens.map(m => `
                <div class="message ${m.minha ? 'minha' : ''}" data-mensagem-id="${escaparHtml(m.id)}">
                    <div class="msg-avatar">${escaparHtml(String(m.nome ?? '?').charAt(0))}</div>
                    <div class="msg-corpo">
                        <div class="msg-topo">
                            <strong>${escaparHtml(m.nome)}</strong>
                            <small>${escaparHtml(m.created_at)}</small>
                        </div>
                        <p>${escaparHtml(m.mensagem)}</p>
                        ${m.arquivo_url ? `<a href="${escaparHtml(m.arquivo_url)}" target="_blank">📎 ${escaparHtml(m.arquivo_nome)}</a>` : ''}
                    </div>
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
                    statusEl.textContent = '● Online';
                    statusEl.className = 'online';
                } else {
                    throw new Error(dados.erro || 'Erro desconhecido');
                }
            } catch (erro) {
                statusEl.textContent = '● Sem conexão';
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
                ajustarTextarea();
                inputArquivo.value = '';
                nomeArquivoEl.textContent = '';
                btnAnexo.classList.remove('tem-arquivo');
                await buscarMensagens();
                irParaFinal();
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
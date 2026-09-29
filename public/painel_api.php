<?php
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../controllers/auth.php';
require __DIR__ . '/../controllers/user.php';
require __DIR__ . '/../controllers/analises.php';
require __DIR__ . '/../controllers/conversas.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

verificacao_L();

$usuarioId = (int) $_SESSION['id'];
$conversas = buscarConversas($usuarioId);

$conversaId = isset($_GET['conversa']) ? (int) $_GET['conversa'] : null;

if ($conversaId === null || !conversaPertenceAoUsuario($conversaId, $usuarioId)) {
    if (count($conversas) > 0) {
        $conversaId = (int) $conversas[0]['id'];
    } else {
        $conversaId = criarConversa($usuarioId);
        $conversas = buscarConversas($usuarioId);
    }
}

$historico = buscarAnalisesPorConversa($conversaId);

// Resumo de cada conversa pra mostrar como prévia na sidebar (1 -- sidebar mais rica)
function resumoConversa(array $c): string {
    $titulo = trim($c['titulo'] ?? '');
    if ($titulo === '' || $titulo === 'Nova conversa') {
        return 'Sem mensagens ainda';
    }
    return $titulo;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisar código - Crypher.IA</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/analisador.css">
</head>
<style>
    /* =========================================
   CRYPHER.IA - ANALISADOR DE CÓDIGO
   CHAT EM TELA CHEIA
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
    --branco: #ffffff;
    --fundo: #f5f6ff;
    --texto: #202020;
    --cinza: #777;
    --borda: #e5e5ee;
}


/* =========================================
   PÁGINA (ocupa 100% da janela, sem rolagem)
========================================= */

html,
body {
    width: 100%;
    height: 100%;
    overflow: hidden;
}

body {
    height: 100dvh;              /* evita problema com a barra do navegador no celular */
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


/* menu central */

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


/* perfil (canto direito) */

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
   MAIN (preenche tudo que sobra abaixo do header)
========================================= */

main {
    flex: 1;
    min-height: 0;               /* essencial: os filhos rolam em vez de estourar */

    width: 100%;
    max-width: none;

    margin: 0;
    padding: 20px 25px;

    display: flex;
    flex-direction: column;
}


/* =========================================
   TÍTULO (opcional)
========================================= */

.titulo-pagina {
    flex-shrink: 0;

    margin-bottom: 15px;
}

.titulo-pagina h1 {
    color: #fff;

    font-size: 2rem;
    font-weight: 600;

    line-height: 1.15;

    margin-bottom: 6px;
}

.titulo-pagina p {
    max-width: 750px;

    color: rgba(255, 255, 255, .8);

    font-size: .9rem;
    line-height: 1.6;
}


/* =========================================
   LAYOUT (sidebar + chat)
========================================= */

.layout {
    flex: 1;
    min-height: 0;

    width: 100%;

    display: grid;

    grid-template-columns: 285px minmax(0, 1fr);
    grid-template-rows: minmax(0, 1fr);   /* a linha não passa da altura disponível */

    gap: 20px;
}


/* =========================================
   SIDEBAR
========================================= */

.sidebar {
    width: 100%;
    height: 100%;
    min-height: 0;

    background: #fff;

    border: 1px solid var(--borda);
    border-radius: 20px;

    padding: 18px;

    box-shadow: 0 8px 25px rgba(45, 45, 100, .07);

    overflow-y: auto;            /* só a lista de conversas rola */
}


/* nova conversa */

.btn-nova-conversa {
    width: 100%;
    height: 48px;

    border: none;
    border-radius: 999px;

    background: var(--amarelo);
    color: #222;

    font-family: inherit;
    font-size: .9rem;
    font-weight: 700;

    cursor: pointer;

    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;

    margin-bottom: 18px;

    box-shadow: 0 7px 18px rgba(243, 190, 39, .2);

    transition:
        transform .25s ease,
        box-shadow .25s ease;
}

.btn-nova-conversa:hover {
    transform: translateY(-2px);

    box-shadow: 0 10px 22px rgba(243, 190, 39, .3);
}


/* sidebar vazia */

.sidebar-vazia {
    padding: 25px 10px;

    text-align: center;

    color: #999;

    font-size: .82rem;
}


/* =========================================
   CONVERSAS
========================================= */

.conversa-item {
    position: relative;

    width: 100%;

    display: flex;
    align-items: center;

    margin-bottom: 7px;

    border-radius: 13px;

    transition: background .2s ease;
}

.conversa-item:hover {
    background: #f4f4fc;
}

.conversa-item.ativa {
    background: rgba(67, 72, 217, .1);
}

.conversa-link {
    flex: 1;
    min-width: 0;

    display: flex;
    align-items: center;

    gap: 11px;

    padding: 11px;

    text-decoration: none;

    color: inherit;
}

.conversa-icone {
    flex-shrink: 0;

    width: 34px;
    height: 34px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 10px;

    background: #eeeeff;

    color: var(--azul);

    font-size: .8rem;
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

.conversa-titulo {
    display: block;

    max-width: 165px;

    overflow: hidden;

    white-space: nowrap;
    text-overflow: ellipsis;

    color: #333;

    font-size: .82rem;
    font-weight: 600;
}

.conversa-data {
    color: #999;

    font-size: .68rem;
}


/* renomear */

.btn-renomear {
    width: 32px;
    height: 32px;

    margin-right: 7px;

    border: none;
    border-radius: 9px;

    background: transparent;

    color: #999;

    cursor: pointer;

    transition:
        background .2s ease,
        color .2s ease;
}

.btn-renomear:hover {
    background: var(--amarelo);
    color: #222;
}

.input-renomear {
    flex: 1;
    min-width: 0;

    height: 38px;

    border: 2px solid var(--azul);
    border-radius: 9px;

    outline: none;

    padding: 0 10px;

    font-family: inherit;
    font-size: .78rem;
}


/* =========================================
   CARD DO CHAT
========================================= */

.chat-card {
    position: relative;

    height: 100%;
    min-width: 0;
    min-height: 0;

    background: #fff;

    border: 1px solid var(--borda);
    border-radius: 20px;

    box-shadow: 0 8px 25px rgba(45, 45, 100, .07);

    display: flex;
    flex-direction: column;

    overflow: hidden;
}

.chat-marca-dagua {
    position: absolute;

    width: 280px;
    height: 280px;

    right: -100px;
    top: -100px;

    border-radius: 50%;

    background: rgba(67, 72, 217, .035);

    pointer-events: none;
}


/* chat vazio */

.chat-vazio {
    flex: 1;
    min-height: 0;

    display: flex;
    flex-direction: column;

    justify-content: center;
    align-items: center;

    text-align: center;

    padding: 40px;
}

.chat-vazio > i {
    width: 72px;
    height: 72px;

    display: flex;
    align-items: center;
    justify-content: center;

    margin-bottom: 18px;

    border-radius: 22px;

    background: rgba(67, 72, 217, .1);

    color: var(--azul);

    font-size: 2rem;
}

.chat-vazio h3 {
    margin-bottom: 8px;

    color: #333;

    font-size: 1.1rem;
    font-weight: 600;
}

.chat-vazio p {
    max-width: 480px;

    color: #888;

    font-size: .85rem;
    line-height: 1.6;
}


/* área de mensagens (só ela rola) */

.chat {
    flex: 1;
    min-height: 0;

    overflow-y: auto;

    padding: 30px;

    display: flex;
    flex-direction: column;

    gap: 18px;
}

.chat::-webkit-scrollbar,
.sidebar::-webkit-scrollbar {
    width: 6px;
}

.chat::-webkit-scrollbar-thumb,
.sidebar::-webkit-scrollbar-thumb {
    background: #d1d2e8;

    border-radius: 999px;
}


/* =========================================
   BALÕES
========================================= */

.bubble-user {
    align-self: flex-end;

    max-width: 75%;

    padding: 13px 17px;

    background: var(--azul);
    color: #fff;

    border-radius: 17px 17px 5px 17px;

    font-size: .86rem;
    line-height: 1.55;

    white-space: pre-wrap;
    overflow-wrap: anywhere;

    box-shadow: 0 5px 15px rgba(67, 72, 217, .15);
}

.bubble-ia {
    align-self: flex-start;

    width: min(90%, 800px);

    padding: 20px;

    background: #f7f7fc;

    border: 1px solid #e6e6f2;

    border-radius: 17px 17px 17px 5px;

    color: #333;

    box-shadow: 0 4px 14px rgba(0, 0, 0, .04);
}

.bubble-ia .resumo {
    margin: 12px 0 16px;

    font-size: .88rem;
    line-height: 1.65;

    color: #555;
}

.badge-linguagem {
    display: inline-flex;
    align-items: center;

    padding: 5px 11px;

    border-radius: 999px;

    background: var(--azul);
    color: #fff;

    font-size: .7rem;
    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: .4px;
}


/* =========================================
   CARDS DE VULNERABILIDADE
========================================= */

.card-vuln {
    position: relative;

    margin-top: 12px;

    padding: 15px 17px;

    border-radius: 13px;

    background: #fff;

    border: 1px solid #e5e5e5;
    border-left: 4px solid #aaa;
}

.card-vuln h4 {
    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 10px;

    margin-bottom: 8px;

    color: #292929;

    font-size: .88rem;
    font-weight: 700;
}

.card-vuln p {
    color: #666;

    font-size: .8rem;
    line-height: 1.55;

    margin-bottom: 7px;
}

.card-vuln p:last-child {
    margin-bottom: 0;
}

.card-vuln strong {
    color: #333;
}

.tag-sev {
    flex-shrink: 0;

    padding: 4px 9px;

    border-radius: 999px;

    font-size: .63rem;
    font-weight: 700;

    text-transform: uppercase;
}

/* baixa */
.card-vuln.sev-baixa { border-left-color: green; }
.tag-baixa { background: #e7f6eb; color: green; }

/* média */
.card-vuln.sev-media { border-left-color: orangered; }
.tag-media { background: #fff4d5; color: orangered; }

/* alta */
.card-vuln.sev-alta { border-left-color: red; }
.tag-alta { background: #fff0df; color: red; }

/* crítica */
.card-vuln.sev-critica { border-left-color: #d9534f; }
.tag-critica { background: #ffe6e6; color: #a52c28; }


/* =========================================
   LOADING
========================================= */

.loading-status {
    display: none;

    align-items: center;
    justify-content: center;

    gap: 9px;

    padding: 10px;

    color: #777;

    font-size: .78rem;
}

.spinner {
    width: 15px;
    height: 15px;

    border: 2px solid #ddd;
    border-top-color: var(--azul);

    border-radius: 50%;

    animation: girar .7s linear infinite;
}

@keyframes girar {
    to {
        transform: rotate(360deg);
    }
}


/* =========================================
   BARRA DE ENTRADA (fixa embaixo do card)
========================================= */

.input-bar {
    width: 100%;
    min-height: 76px;
    flex-shrink: 0;

    padding: 13px 16px;

    display: flex;
    align-items: flex-end;

    gap: 9px;

    background: #fff;

    border-top: 1px solid #ededf4;
}

.btn-anexo {
    flex-shrink: 0;

    width: 45px;
    height: 45px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 13px;

    background: #f1f1fa;

    color: var(--azul);

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
    max-width: 150px;

    color: #777;

    font-size: .7rem;

    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.input-bar textarea {
    flex: 1;
    min-width: 0;

    min-height: 45px;
    max-height: 110px;

    resize: none;

    padding: 12px 14px;

    border: 2px solid #e5e5ee;
    border-radius: 14px;

    outline: none;

    background: #fafaff;

    color: #222;

    font-family: inherit;
    font-size: .85rem;
    line-height: 1.4;

    transition:
        border-color .2s ease,
        box-shadow .2s ease;
}

.input-bar textarea:focus {
    border-color: var(--azul);

    box-shadow: 0 0 0 3px rgba(67, 72, 217, .08);

    background: #fff;
}

.input-bar textarea::placeholder {
    color: #999;
}

.input-bar > button {
    flex-shrink: 0;

    height: 45px;

    padding: 0 22px;

    border: none;
    border-radius: 999px;

    background: var(--amarelo);
    color: #222;

    font-family: inherit;
    font-size: .85rem;
    font-weight: 700;

    cursor: pointer;

    box-shadow: 0 6px 15px rgba(243, 190, 39, .2);

    transition:
        transform .2s ease,
        box-shadow .2s ease;
}

.input-bar > button:hover {
    transform: translateY(-2px);

    box-shadow: 0 9px 20px rgba(243, 190, 39, .3);
}


/* =========================================
   STATUS / ERRO
========================================= */

#status {
    color: #d9534f;

    font-size: .8rem;

    padding: 0 5px;
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

    main {
        padding: 18px 20px;
    }

    .layout {
        grid-template-columns: 240px minmax(0, 1fr);
    }

    .bubble-user {
        max-width: 82%;
    }

    .bubble-ia {
        width: 92%;
    }
}


/* =========================================
   MOBILE
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

    /* no mobile o menu volta pro fluxo normal */
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

    main {
        padding: 12px;
    }

    .titulo-pagina h1 {
        font-size: 1.6rem;
    }

    .titulo-pagina p {
        font-size: .8rem;
    }

    /* sidebar vira uma faixa no topo e o chat preenche o resto */
    .layout {
        display: flex;
        flex-direction: column;

        gap: 12px;
    }

    .sidebar {
        height: auto;
        max-height: 180px;
        flex-shrink: 0;

        padding: 12px;

        overflow-x: auto;
        overflow-y: hidden;

        white-space: nowrap;
    }

    .sidebar > form {
        display: inline-block;

        width: 180px;

        vertical-align: middle;

        margin-right: 5px;
    }

    .btn-nova-conversa {
        height: 43px;

        margin-bottom: 10px;
    }

    .sidebar .btn-nova-conversa {
        width: 180px;
    }

    .conversa-item {
        display: inline-flex;

        width: auto;
        min-width: 180px;

        margin-right: 5px;
    }

    .chat-card {
        flex: 1;
        height: auto;
        min-height: 0;

        border-radius: 17px;
    }

    .chat {
        padding: 20px 14px;
    }

    .bubble-user {
        max-width: 88%;

        font-size: .82rem;
    }

    .bubble-ia {
        width: 96%;

        padding: 16px;
    }

    .card-vuln h4 {
        align-items: flex-start;

        flex-direction: column;
    }

    .input-bar {
        padding: 10px;

        gap: 6px;
    }

    .btn-anexo {
        width: 42px;
        height: 42px;
    }

    .input-bar textarea {
        min-height: 42px;

        padding: 10px 11px;
    }

    .input-bar > button {
        height: 42px;

        padding: 0 16px;
    }
}


/* =========================================
   CELULAR PEQUENO
========================================= */

@media (max-width: 480px) {

    header {
        flex-direction: column;

        align-items: stretch;

        padding: 12px 15px;
    }

    .logo {
        text-align: center;

        font-size: 1.65rem;
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

    main {
        padding: 10px;
    }

    .sidebar {
        max-height: 155px;
    }

    .chat-card {
        border-radius: 15px;
    }

    .chat {
        padding: 17px 10px;
    }

    .bubble-user {
        max-width: 92%;

        padding: 11px 13px;

        font-size: .78rem;
    }

    .bubble-ia {
        width: 98%;

        padding: 14px;

        border-radius: 14px;
    }

    .bubble-ia .resumo {
        font-size: .78rem;
    }

    .card-vuln {
        padding: 13px;
    }

    .card-vuln p {
        font-size: .74rem;
    }

    .input-bar {
        min-height: 66px;

        padding: 8px;
    }

    .btn-anexo {
        width: 39px;
        height: 39px;

        border-radius: 11px;
    }

    .input-bar textarea {
        min-height: 39px;

        font-size: .76rem;

        border-radius: 11px;
    }

    .input-bar > button {
        height: 39px;

        padding: 0 13px;

        font-size: .75rem;
    }

    .nome-arquivo {
        display: none;
    }
}


/* =========================================================
   LAYOUT FINAL (conforme esboço)
   Header no topo, chat ocupando todo o resto da tela e
   barra de envio fixa embaixo. Sem sidebar, sem margens.
   Fica no fim do arquivo para sobrescrever as regras acima.
========================================================= */

main {
    padding: 0;
    background: #fff;
}

.titulo-pagina {
    display: none;
}

.layout {
    display: block;
    flex: 1;
    min-height: 0;
    gap: 0;
}

.chat-card {
    height: 100%;
    border: none;
    border-radius: 0;
    box-shadow: none;
}

.chat-marca-dagua {
    display: none;
}

.chat {
    padding: 30px 55px;
}

/* barra de envio: [ texto .......... ] [Arquivo] [Enviar] */
.input-bar {
    align-items: center;
    padding: 12px 55px;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, .06);
}

.input-bar textarea   { order: 1; flex: 1; }
.input-bar .btn-anexo { order: 2; }
.input-bar .nome-arquivo { order: 2; }
.input-bar > button   { order: 3; }

/* botão "Arquivo" com largura automática (ícone + texto) */
.btn-anexo {
    width: auto;
    padding: 0 16px;
    gap: 8px;
    font-size: .8rem;
    font-weight: 600;
}

@media (max-width: 1050px) {
    .chat      { padding: 25px 30px; }
    .input-bar { padding: 10px 30px; }
}

@media (max-width: 768px) {
    main       { padding: 0; }
    .chat      { padding: 20px 14px; }
    .input-bar { padding: 10px 12px; }
    .chat-card { border-radius: 0; }
}


/* =========================================================
   HISTÓRICO DE CONVERSAS (aba do lado esquerdo)
   Desktop: painel lateral que abre/fecha empurrando o chat.
   Mobile: gaveta que desliza por cima da tela.
   Estado controlado pela classe .historico-aberto no .layout
========================================================= */

.layout {
    display: grid;
    grid-template-columns: 0 minmax(0, 1fr);      /* fechado */
    grid-template-rows: minmax(0, 1fr);
    gap: 0;

    transition: grid-template-columns .3s ease;
}

.layout.historico-aberto {
    grid-template-columns: 320px minmax(0, 1fr);  /* aberto */
}

.sidebar {
    display: block;

    width: 100%;
    height: 100%;
    min-width: 0;
    max-height: none;

    padding: 0;

    background: #fafaff;

    border: none;
    border-right: 1px solid var(--borda);
    border-radius: 0;
    box-shadow: none;

    overflow: hidden;
    white-space: normal;

    visibility: hidden;                            /* fechado */
}

.layout.historico-aberto .sidebar {
    padding: 18px;

    overflow-y: auto;
    overflow-x: hidden;

    visibility: visible;
}


/* topo do painel */

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


/* botão que abre o histórico (canto superior esquerdo do chat) */

.btn-historico {
    position: absolute;

    top: 14px;
    left: 20px;

    z-index: 5;

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

    box-shadow: 0 4px 12px rgba(45, 45, 100, .08);

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

/* com o painel aberto no desktop, o botão de abrir some (existe o X no painel) */
@media (min-width: 769px) {
    .layout.historico-aberto .btn-historico {
        display: none;
    }
}


/* itens dentro do painel: sempre em lista vertical */

.sidebar > form {
    display: block;
    width: 100%;
    margin: 0;
}

.sidebar .btn-nova-conversa {
    width: 100%;
}

.sidebar .conversa-item {
    display: flex;
    width: 100%;
    min-width: 0;
    margin-right: 0;
}


/* =========================================
   MOBILE: histórico vira gaveta
========================================= */

@media (max-width: 768px) {

    .layout,
    .layout.historico-aberto {
        display: block;
    }

    .chat-card {
        height: 100%;
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
        overflow-x: hidden;

        visibility: visible;

        transform: translateX(-100%);

        transition: transform .3s ease;
    }

    .layout.historico-aberto .sidebar {
        transform: translateX(0);

        box-shadow: 10px 0 30px rgba(0, 0, 0, .25);
    }

    .layout.historico-aberto .btn-historico {
        display: flex;
    }

    .btn-historico {
        top: 10px;
        left: 12px;
    }
}
</style>
<body>
    <header>
        <a href="home.php" class="logo">Crypher.IA</a>

        <nav>
            <a href="home.php">Home</a>
            <a href="contato/conversa.php">Contato</a>
        </nav>

        <a href="perfil.php">Perfil</a>
    </header>

    <main>

        <div class="layout">

            <aside class="sidebar">
                <div class="historico-topo">
                    <h3>Histórico</h3>
                    <button type="button" class="btn-fechar-historico" data-toggle-historico title="Fechar histórico">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <form method="POST" action="nova_conversa.php">
                    <button type="submit" class="btn-nova-conversa">
                        <i class="fa-solid fa-plus"></i> Nova conversa
                    </button>
                </form>

                <?php if (count($conversas) === 0): ?>
                    <div class="sidebar-vazia">Nenhuma conversa ainda.</div>
                <?php endif; ?>

                <?php foreach ($conversas as $c): ?>
                    <div class="conversa-item <?= ((int)$c['id'] === $conversaId) ? 'ativa' : '' ?>">
                        <a href="?conversa=<?= (int) $c['id'] ?>" class="conversa-link">
                            <span class="conversa-icone"><i class="fa-solid fa-message"></i></span>
                            <span class="conversa-texto">
                                <span class="conversa-titulo"><?= htmlspecialchars(resumoConversa($c)) ?></span>
                                <span class="conversa-data"><?= htmlspecialchars($c['created_at']) ?></span>
                            </span>
                        </a>
                    </div>
                <?php endforeach; ?>
            </aside>

            <div class="chat-card">
                <div class="chat-marca-dagua"></div>

                <button type="button" class="btn-historico" data-toggle-historico>
                    <i class="fa-solid fa-clock-rotate-left"></i> Histórico
                </button>

                <?php if (count($historico) === 0): ?>
                    <div class="chat-vazio">
                        <i class="fa-solid fa-shield-halved"></i>
                        <h3>Nenhuma análise por aqui ainda</h3>
                        <p>Cole um trecho de código ou envie um arquivo abaixo para a IA começar a procurar vulnerabilidades.</p>
                    </div>
                <?php else: ?>
                    <div class="chat" id="chat">
                        <?php foreach ($historico as $item): ?>
                            <?php $r = json_decode($item['resultado_json'], true) ?: ['linguagem' => null, 'resumo' => '', 'vulnerabilidades' => []]; ?>

                            <div class="bubble-user"><?= htmlspecialchars($item['entrada_texto']) ?></div>

                            <div class="bubble-ia">
                                <?php if (!empty($r['linguagem'])): ?>
                                    <span class="badge-linguagem"><?= htmlspecialchars($r['linguagem']) ?></span>
                                <?php endif; ?>
                                <p class="resumo"><?= htmlspecialchars($r['resumo'] ?? '') ?></p>

                                <?php foreach (($r['vulnerabilidades'] ?? []) as $v): ?>
                                    <?php $sev = strtolower($v['severidade'] ?? 'baixa'); ?>
                                    <div class="card-vuln sev-<?= htmlspecialchars($sev) ?>">
                                        <h4><?= htmlspecialchars($v['titulo'] ?? '') ?> <span class="tag-sev tag-<?= htmlspecialchars($sev) ?>"><?= htmlspecialchars($sev) ?></span></h4>
                                        <p><?= htmlspecialchars($v['explicacao'] ?? '') ?></p>
                                        <p><strong>Sugestão:</strong> <?= htmlspecialchars($v['sugestao'] ?? '') ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div id="status"></div>

                <div class="loading-status" id="loading-status">
                    <span class="spinner"></span>
                    <span>Analisando código...</span>
                </div>

                <form id="form-analise" class="input-bar" enctype="multipart/form-data">
                    <input type="hidden" name="conversa_id" value="<?= $conversaId ?>">

                    <label class="btn-anexo" id="btn-anexo" title="Anexar arquivo">
                        <i class="fa-solid fa-upload" id="icone-anexo"></i> Arquivo
                        <input type="file" name="arquivo" id="arquivo" accept=".php,.js,.py,.sql,.html,.css,.txt,.json,.zip">
                    </label>

                    <span class="nome-arquivo" id="nome-arquivo"></span>

                    <textarea id="texto" name="texto" placeholder="Cole seu código aqui..." rows="1"></textarea>
                    <button type="submit">Enviar</button>
                </form>
            </div>

        </div>
    </main>

    <script>
    (() => {
        const chatCard = document.querySelector('.chat-card');
        let chat = document.getElementById('chat');
        const form = document.getElementById('form-analise');
        const textarea = document.getElementById('texto');
        const inputArquivo = document.getElementById('arquivo');
        const btnAnexo = document.getElementById('btn-anexo');
        const nomeArquivoEl = document.getElementById('nome-arquivo');
        const statusEl = document.getElementById('status');
        const loadingStatus = document.getElementById('loading-status');

        textarea.addEventListener('input', () => {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 110) + 'px';
        });

        // 4 -- mostra o nome do arquivo escolhido e destaca o ícone
        inputArquivo.addEventListener('change', () => {
            const arquivo = inputArquivo.files[0];
            if (arquivo) {
                nomeArquivoEl.textContent = arquivo.name;
                btnAnexo.classList.add('tem-arquivo');
            } else {
                nomeArquivoEl.textContent = '';
                btnAnexo.classList.remove('tem-arquivo');
            }
        });

        function escapar(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function garantirChat() {
            // Se ainda estava no estado vazio, cria o container de mensagens agora
            if (!chat) {
                const vazio = chatCard.querySelector('.chat-vazio');
                if (vazio) vazio.remove();

                chat = document.createElement('div');
                chat.className = 'chat';
                chat.id = 'chat';
                chatCard.insertBefore(chat, loadingStatus);
            }
        }

        function irParaFinal() {
            if (chat) chat.scrollTop = chat.scrollHeight;
        }

        function renderizarBubbleUser(texto) {
            garantirChat();
            const div = document.createElement('div');
            div.className = 'bubble-user';
            div.textContent = texto;
            chat.appendChild(div);
        }

        function renderizarBubbleIA(resultado) {
            garantirChat();
            const div = document.createElement('div');
            div.className = 'bubble-ia';

            let html = '';
            if (resultado.linguagem) {
                html += `<span class="badge-linguagem">${escapar(resultado.linguagem)}</span>`;
            }
            html += `<p class="resumo">${escapar(resultado.resumo || '')}</p>`;

            (resultado.vulnerabilidades || []).forEach(v => {
                const sev = (v.severidade || 'baixa').toLowerCase();
                html += `
                    <div class="card-vuln sev-${sev}">
                        <h4>${escapar(v.titulo || '')} <span class="tag-sev tag-${sev}">${sev}</span></h4>
                        <p>${escapar(v.explicacao || '')}</p>
                        <p><strong>Sugestão:</strong> ${escapar(v.sugestao || '')}</p>
                    </div>
                `;
            });

            div.innerHTML = html;
            chat.appendChild(div);
        }

        form.addEventListener('submit', async (evento) => {
            evento.preventDefault();

            const codigo = textarea.value.trim();
            const arquivo = inputArquivo.files[0];
            if (codigo === '' && !arquivo) return;

            statusEl.textContent = '';
            loadingStatus.style.display = 'flex'; // 5 -- feedback visível durante a análise

            const entradaExibida = codigo !== '' ? codigo : ('Arquivo enviado: ' + arquivo.name);
            renderizarBubbleUser(entradaExibida);
            irParaFinal();

            try {
                const corpo = new FormData(form);

                const resp = await fetch('analisar_codigo.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: corpo
                });

                const dados = await resp.json();

                if (dados.sucesso) {
                    renderizarBubbleIA(dados.resultado);
                    textarea.value = '';
                    textarea.style.height = 'auto';
                    inputArquivo.value = '';
                    nomeArquivoEl.textContent = '';
                    btnAnexo.classList.remove('tem-arquivo');
                } else {
                    statusEl.textContent = 'Erro: ' + dados.erro;
                }
            } catch (erro) {
                statusEl.textContent = 'Erro de conexão: ' + erro.message;
            } finally {
                loadingStatus.style.display = 'none';
                irParaFinal();
            }
        });

        irParaFinal();
    })();
    </script>
    <script>
    (() => {
        const layout = document.querySelector('.layout');

        // no computador começa aberto; no celular começa fechado
        if (window.innerWidth > 768) layout.classList.add('historico-aberto');

        document.querySelectorAll('[data-toggle-historico]').forEach(btn => {
            btn.addEventListener('click', () => layout.classList.toggle('historico-aberto'));
        });
    })();
    </script>
</body>
</html>
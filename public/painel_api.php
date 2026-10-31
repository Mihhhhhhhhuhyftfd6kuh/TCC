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

// Nome do usuário logado (usado na bolinha do perfil)
$sqlNome = "SELECT nome FROM usuarios WHERE id = :id";
$stmtNome = $pdo->prepare($sqlNome);
$stmtNome->bindParam(':id', $usuarioId, PDO::PARAM_INT);
$stmtNome->execute();
$usuarioAtual = $stmtNome->fetch(PDO::FETCH_ASSOC);
$nomeAtual = $usuarioAtual['nome'] ?? 'Usuário';
$inicialUsuario = mb_strtoupper(mb_substr($nomeAtual, 0, 1));

// Admin vai para a lista de usuários; usuário comum vai direto para o chat de contato
$linkContato = ($usuarioId === 1) ? 'contato/admin.php' : 'contato/conversa.php';

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

// Título da conversa atual (aparece na barra do chat)
$tituloAtual = 'Nova conversa';
foreach ($conversas as $c) {
    if ((int) $c['id'] === $conversaId) {
        $t = trim($c['titulo'] ?? '');
        if ($t !== '') {
            $tituloAtual = $t;
        }
        break;
    }
}

// Ícone (Font Awesome) do anexo conforme a extensão do arquivo
function iconeAnexo(string $nome): string {
    $ext = strtolower(pathinfo($nome, PATHINFO_EXTENSION));
    if ($ext === 'zip') return 'fa-file-zipper';
    if ($ext === 'sql') return 'fa-database';
    if ($ext === 'txt') return 'fa-file-lines';
    return 'fa-file-code';
}

// Resumo de cada conversa pra mostrar como prévia na sidebar
function resumoConversa(array $c): string {
    $titulo = trim($c['titulo'] ?? '');
    if ($titulo === '' || $titulo === 'Nova conversa') {
        return 'Sem mensagens ainda';
    }
    return $titulo;
}

// Rótulo de cada gravidade
function rotuloSev(string $s): string {
    $mapa = ['critica' => 'Crítica', 'alta' => 'Alta', 'media' => 'Média', 'baixa' => 'Baixa'];
    return $mapa[$s] ?? ucfirst($s);
}

// Nota de segurança (0 a 100): cada falha desconta pontos conforme a gravidade
function pontuacaoSeguranca(array $vulns): int {
    $peso = ['critica' => 35, 'alta' => 22, 'media' => 12, 'baixa' => 5];
    $p = 100;
    foreach ($vulns as $v) {
        $s = strtolower($v['severidade'] ?? 'baixa');
        $p -= $peso[$s] ?? 5;
    }
    return max(0, $p);
}

function corPontuacao(int $p): string {
    if ($p >= 80) return '#1f9d55';
    if ($p >= 50) return '#e08a00';
    return '#d93a3f';
}

// Medidor circular da nota de segurança
function gaugeHtml(int $p): string {
    $c = 163.36;
    $dash = round($p / 100 * $c, 2);
    $cor = corPontuacao($p);
    return '<div class="gauge" style="--cor:' . $cor . '" title="Nota de segurança: ' . $p . ' de 100">'
        . '<svg viewBox="0 0 64 64" aria-hidden="true">'
        . '<circle class="gauge-fundo" cx="32" cy="32" r="26"></circle>'
        . '<circle class="gauge-valor" cx="32" cy="32" r="26" style="stroke-dasharray:' . $dash . ' ' . $c . '"></circle>'
        . '</svg>'
        . '<span class="gauge-num">' . $p . '</span>'
        . '</div>';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, interactive-widget=resizes-content">
    <meta name="theme-color" content="#4348D9">
    <title>Analisar código - Crypher.IA</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/analisador.css">
    <link rel="icon" type="image/x-icon" href="../assets/img/mascote_s_fundo.png">

<style>
/* =========================================
   CRYPHER.IA - ANALISADOR DE CÓDIGO
   Visual limpo e profissional
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
    --azul-suave: #F0F1FF;
    --amarelo: #F3BE27;
    --fundo: #F6F7FB;
    --branco: #ffffff;
    --texto: #1b1d2e;
    --texto-suave: #6b6d85;
    --borda: #E6E7F1;
    --largura-chat: 860px;
}

html,
body {
    width: 100%;
    height: 100%;
    overflow: hidden;
}

body {
    height: 100dvh;              /* evita problema com a barra do navegador no celular */

    display: flex;
    flex-direction: column;

    background: var(--fundo);
    color: var(--texto);
}

a {
    color: inherit;
}


/* =========================================
   HEADER
========================================= */

header {
    position: relative;
    z-index: 30;

    width: 100%;
    height: 64px;
    flex-shrink: 0;

    padding: 0 32px;

    display: flex;
    align-items: center;

    background: var(--azul);
}

.logo {
    flex-shrink: 0;

    color: #fff;

    font-size: 1.7rem;
    font-weight: 700;
    letter-spacing: -.5px;

    text-decoration: none;
}

/* menu central */

header nav {
    position: absolute;

    left: 50%;
    top: 50%;

    transform: translate(-50%, -50%);

    display: flex;
    align-items: center;
    gap: 6px;
}

header nav a {
    padding: 8px 18px;

    border-radius: 999px;

    color: rgba(255, 255, 255, .9);

    font-size: .86rem;
    font-weight: 500;

    text-decoration: none;

    transition: background .2s ease;
}

header nav a:hover {
    background: rgba(255, 255, 255, .15);
}

/* lado direito: hambúrguer (celular) + bolinha da conta */

.header-direita {
    position: relative;

    margin-left: auto;

    display: flex;
    align-items: center;
    gap: 12px;
}

.conta-menu {
    position: relative;
    flex-shrink: 0;
}

.conta-botao {
    width: 40px;
    height: 40px;

    display: flex;
    align-items: center;
    justify-content: center;

    border: none;
    border-radius: 50%;

    background: #fff;
    color: var(--azul);

    font-family: inherit;
    font-size: 1rem;
    font-weight: 700;
    line-height: 1;

    text-transform: uppercase;

    cursor: pointer;

    box-shadow: 0 2px 8px rgba(0, 0, 0, .15);

    transition: transform .2s ease, box-shadow .2s ease;
}

.conta-botao:hover {
    transform: translateY(-1px);

    box-shadow: 0 6px 14px rgba(0, 0, 0, .2);
}

.conta-dropdown,
.hamb-dropdown {
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

.conta-dropdown.ativo,
.hamb-dropdown.ativo {
    display: block;
}

.conta-dropdown a,
.hamb-dropdown a {
    display: flex;
    align-items: center;
    gap: 10px;

    padding: 13px 20px;

    color: #222;

    font-size: .9rem;
    font-weight: 500;

    text-decoration: none;

    transition: background .2s ease;
}

.conta-dropdown a i,
.hamb-dropdown a i {
    width: 16px;

    text-align: center;

    color: var(--azul);
}

.conta-dropdown a:hover,
.hamb-dropdown a:hover {
    background: #f2f2f2;
}

/* hambúrguer: só no celular */

.menu-hamb {
    position: relative;

    display: none;
    flex-shrink: 0;
}

.hamb-botao {
    width: 40px;
    height: 40px;

    display: flex;
    align-items: center;
    justify-content: center;

    border: none;
    border-radius: 12px;

    background: rgba(255, 255, 255, .18);
    color: #fff;

    font-size: 1.05rem;
    line-height: 1;

    cursor: pointer;

    transition: background .2s ease;
}

.hamb-botao[aria-expanded="true"] {
    background: rgba(255, 255, 255, .32);
}


/* =========================================
   MAIN + LAYOUT (histórico + chat)
========================================= */

main {
    flex: 1;
    min-height: 0;

    width: 100%;

    display: flex;
    flex-direction: column;
}

.layout {
    position: relative;

    flex: 1;
    min-height: 0;

    display: grid;

    grid-template-columns: 0 minmax(0, 1fr);        /* histórico fechado */
    grid-template-rows: minmax(0, 1fr);

    transition: grid-template-columns .3s ease;
}

.layout.historico-aberto {
    grid-template-columns: 290px minmax(0, 1fr);    /* histórico aberto */
}

.historico-overlay {
    display: none;
}


/* =========================================
   HISTÓRICO (sidebar)
========================================= */

.sidebar {
    width: 100%;
    height: 100%;
    min-width: 0;

    background: #fff;

    border-right: 1px solid var(--borda);

    overflow: hidden;

    visibility: hidden;                              /* fechado */
}

.layout.historico-aberto .sidebar {
    padding: 18px 14px;

    overflow-y: auto;
    overflow-x: hidden;

    visibility: visible;
}

.historico-topo {
    display: flex;
    align-items: center;
    justify-content: space-between;

    margin-bottom: 14px;
    padding: 0 6px;
}

.historico-topo h3 {
    color: var(--texto);

    font-size: .8rem;
    font-weight: 600;

    letter-spacing: .6px;
    text-transform: uppercase;
}

.btn-fechar-historico {
    width: 30px;
    height: 30px;

    border: none;
    border-radius: 8px;

    background: transparent;
    color: #9a9cb2;

    cursor: pointer;

    transition: background .2s ease, color .2s ease;
}

.btn-fechar-historico:hover {
    background: var(--fundo);
    color: var(--texto);
}

.sidebar > form {
    display: block;
    width: 100%;
}

.btn-nova-conversa {
    width: 100%;
    height: 44px;

    margin-bottom: 16px;

    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;

    border: none;
    border-radius: 12px;

    background: var(--amarelo);
    color: #222;

    font-family: inherit;
    font-size: .86rem;
    font-weight: 600;

    cursor: pointer;

    transition: background .2s ease, transform .2s ease;
}

.btn-nova-conversa:hover {
    background: #f6c944;

    transform: translateY(-1px);
}

.sidebar-vazia {
    padding: 25px 10px;

    text-align: center;

    color: #9a9cb2;

    font-size: .82rem;
}

.conversa-item {
    position: relative;

    width: 100%;

    display: flex;
    align-items: center;

    margin-bottom: 3px;

    border-radius: 10px;

    transition: background .2s ease;
}

.conversa-item:hover {
    background: var(--fundo);
}

.conversa-item.ativa {
    background: var(--azul-suave);
}

.conversa-link {
    flex: 1;
    min-width: 0;

    display: flex;
    align-items: center;
    gap: 11px;

    padding: 10px 12px;

    text-decoration: none;
    color: inherit;
}

.conversa-icone {
    flex-shrink: 0;

    color: #a3a5bb;

    font-size: .85rem;
}

.conversa-item.ativa .conversa-icone {
    color: var(--azul);
}

.conversa-texto {
    min-width: 0;

    display: flex;
    flex-direction: column;
    gap: 1px;
}

.conversa-titulo {
    display: block;

    max-width: 200px;

    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;

    color: var(--texto);

    font-size: .82rem;
    font-weight: 500;
}

.conversa-item.ativa .conversa-titulo {
    color: var(--azul);
    font-weight: 600;
}

.conversa-data {
    color: #a3a5bb;

    font-size: .68rem;
}


/* =========================================
   CARD DO CHAT
========================================= */

.chat-card {
    position: relative;

    height: 100%;
    min-width: 0;
    min-height: 0;

    display: flex;
    flex-direction: column;

    overflow: hidden;

    background: var(--fundo);
}

/* barra da conversa */

.chat-topo {
    flex-shrink: 0;

    height: 54px;

    padding: 0 24px;

    display: flex;
    align-items: center;
    gap: 14px;

    background: #fff;

    border-bottom: 1px solid var(--borda);
}

.btn-historico {
    flex-shrink: 0;

    height: 34px;

    padding: 0 14px;

    display: flex;
    align-items: center;
    gap: 8px;

    border: 1px solid var(--borda);
    border-radius: 9px;

    background: #fff;
    color: var(--texto);

    font-family: inherit;
    font-size: .76rem;
    font-weight: 500;

    cursor: pointer;

    transition: background .2s ease, border-color .2s ease;
}

.btn-historico i {
    color: var(--azul);
}

.btn-historico:hover {
    background: var(--fundo);
}

/* com o histórico aberto no computador, o botão some (existe o X no painel) */
@media (min-width: 769px) {
    .layout.historico-aberto .btn-historico {
        display: none;
    }
}

.chat-titulo {
    min-width: 0;

    color: var(--texto);

    font-size: .88rem;
    font-weight: 600;
}

.chat-titulo span {
    display: block;

    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.status-ia {
    margin-left: auto;
    flex-shrink: 0;

    display: flex;
    align-items: center;
    gap: 7px;

    color: var(--texto-suave);

    font-size: .72rem;
    font-weight: 500;
}

.status-ia i {
    width: 7px;
    height: 7px;

    border-radius: 50%;

    background: #1f9d55;
}


/* =========================================
   TELA VAZIA
========================================= */

.chat-vazio {
    flex: 1;
    min-height: 0;

    overflow-y: auto;

    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;

    text-align: center;

    padding: 24px;
}

.vazio-mascote {
    width: 84px;
    height: 84px;

    margin-bottom: 18px;

    display: flex;
    align-items: center;
    justify-content: center;

    border: 1px solid var(--borda);
    border-radius: 24px;

    background: #fff;

    box-shadow: 0 8px 24px rgba(67, 72, 217, .10);
}

.vazio-mascote img {
    width: 58px;
    height: 58px;

    object-fit: contain;
}

.chat-vazio h3 {
    margin-bottom: 8px;

    color: var(--texto);

    font-size: 1.6rem;
    font-weight: 700;
    letter-spacing: -.3px;
}

.chat-vazio > p {
    max-width: 460px;

    color: var(--texto-suave);

    font-size: .9rem;
    line-height: 1.65;
}

.sugestoes {
    max-width: 620px;

    margin-top: 24px;

    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 10px;
}

.chip-sugestao {
    display: inline-flex;
    align-items: center;
    gap: 8px;

    padding: 10px 16px;

    border: 1px solid var(--borda);
    border-radius: 12px;

    background: #fff;
    color: var(--texto);

    font-family: inherit;
    font-size: .78rem;
    font-weight: 500;

    cursor: pointer;

    transition: border-color .2s ease, background .2s ease, transform .2s ease;
}

.chip-sugestao i {
    color: var(--azul);
}

.chip-sugestao:hover {
    transform: translateY(-1px);

    border-color: var(--azul);
    background: var(--azul-suave);
}

.formatos {
    margin-top: 28px;

    color: #a3a5bb;

    font-size: .72rem;
}


/* =========================================
   MENSAGENS (só essa área rola)
========================================= */

.chat {
    flex: 1;
    min-height: 0;

    overflow-y: auto;

    /* centraliza a coluna de leitura */
    padding: 28px max(20px, calc((100% - var(--largura-chat)) / 2)) 20px;

    display: flex;
    flex-direction: column;
    gap: 22px;
}

.chat::-webkit-scrollbar,
.sidebar::-webkit-scrollbar,
.chat-vazio::-webkit-scrollbar {
    width: 6px;
}

.chat::-webkit-scrollbar-thumb,
.sidebar::-webkit-scrollbar-thumb,
.chat-vazio::-webkit-scrollbar-thumb {
    background: #d6d7e6;

    border-radius: 999px;
}

.msg {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.msg-user {
    justify-content: flex-end;
}

.avatar-ia {
    flex-shrink: 0;

    width: 36px;
    height: 36px;

    display: flex;
    align-items: center;
    justify-content: center;

    border: 1px solid var(--borda);
    border-radius: 12px;

    background: #fff;
}

.avatar-ia img {
    width: 24px;
    height: 24px;

    object-fit: contain;
}

/* balão do usuário */

.bubble-user {
    max-width: min(78%, 640px);

    padding: 12px 17px;

    display: flex;
    flex-direction: column;
    gap: 8px;

    background: var(--azul);
    color: #fff;

    border-radius: 16px 16px 4px 16px;

    font-size: .88rem;
    line-height: 1.6;
}

.bubble-user .bubble-texto {
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}

/* cartão da IA */

.bubble-ia {
    flex: 1;
    min-width: 0;

    max-width: 780px;

    padding: 20px 22px;

    background: #fff;

    border: 1px solid var(--borda);
    border-radius: 4px 16px 16px 16px;

    color: #333;

    box-shadow: 0 1px 2px rgba(27, 29, 46, .04), 0 10px 28px rgba(27, 29, 46, .05);
}


/* =========================================
   RELATÓRIO (dentro do cartão da IA)
========================================= */

.relatorio-topo {
    display: flex;
    align-items: center;
    gap: 16px;

    padding-bottom: 18px;
    margin-bottom: 16px;

    border-bottom: 1px solid #eef0f6;
}

.gauge {
    position: relative;

    flex-shrink: 0;

    width: 64px;
    height: 64px;
}

.gauge svg {
    width: 100%;
    height: 100%;

    transform: rotate(-90deg);
}

.gauge circle {
    fill: none;

    stroke-width: 6;
    stroke-linecap: round;
}

.gauge-fundo {
    stroke: #eceef6;
}

.gauge-valor {
    stroke: var(--cor);
}

.gauge-num {
    position: absolute;
    inset: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    color: var(--cor);

    font-size: 1.1rem;
    font-weight: 700;
}

.relatorio-info {
    min-width: 0;
}

.relatorio-titulo {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;

    margin-bottom: 9px;

    color: var(--texto);

    font-size: .95rem;
    font-weight: 600;
}

.badge-linguagem {
    display: inline-flex;
    align-items: center;

    padding: 3px 10px;

    border-radius: 6px;

    background: var(--azul-suave);
    color: var(--azul);

    font-size: .65rem;
    font-weight: 700;
    letter-spacing: .4px;

    text-transform: uppercase;
}

.chips-sev {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.chip-sev {
    display: inline-flex;
    align-items: center;
    gap: 6px;

    padding: 4px 10px;

    border-radius: 6px;

    background: #f1f2f8;
    color: #666;

    font-size: .7rem;
    font-weight: 600;
}

.chip-sev::before {
    content: "";

    width: 7px;
    height: 7px;

    border-radius: 50%;

    background: currentColor;
}

.chip-critica { background: #fdecec; color: #c0282d; }
.chip-alta    { background: #fff0e0; color: #c2570c; }
.chip-media   { background: #fff6dc; color: #a96a00; }
.chip-baixa   { background: #e9f7ee; color: #1a8348; }
.chip-ok      { background: #e9f7ee; color: #1a8348; }

.bubble-ia .resumo {
    color: #4a4c60;

    font-size: .88rem;
    line-height: 1.75;
}

.secao-titulo {
    margin: 20px 0 4px;

    color: var(--texto-suave);

    font-size: .7rem;
    font-weight: 600;

    letter-spacing: .7px;
    text-transform: uppercase;
}


/* =========================================
   FALHAS (itens expansíveis)
========================================= */

.card-vuln {
    margin-top: 10px;

    border: 1px solid var(--borda);
    border-left: 3px solid #aaa;
    border-radius: 12px;

    background: #fff;

    overflow: hidden;
}

.card-vuln summary {
    display: flex;
    align-items: center;
    gap: 12px;

    padding: 14px 16px;

    list-style: none;

    cursor: pointer;

    transition: background .2s ease;
}

.card-vuln summary::-webkit-details-marker {
    display: none;
}

.card-vuln summary:hover {
    background: #fafbfe;
}

.vuln-titulo {
    flex: 1;
    min-width: 0;

    color: var(--texto);

    font-size: .86rem;
    font-weight: 600;
    line-height: 1.4;
}

.tag-sev {
    flex-shrink: 0;

    padding: 3px 10px;

    border-radius: 6px;

    background: #eee;
    color: #666;

    font-size: .62rem;
    font-weight: 700;

    text-transform: uppercase;
    letter-spacing: .3px;
}

.card-vuln summary > i {
    flex-shrink: 0;

    color: #a3a5bb;
    font-size: .7rem;

    transition: transform .2s ease;
}

.card-vuln[open] summary > i {
    transform: rotate(180deg);
}

.vuln-corpo {
    padding: 0 16px 16px;
}

.vuln-corpo p {
    margin-bottom: 12px;

    color: #5a5c72;

    font-size: .82rem;
    line-height: 1.7;
}

.vuln-sugestao {
    display: flex;
    gap: 11px;

    padding: 12px 14px;

    border-radius: 10px;

    background: var(--azul-suave);
}

.vuln-sugestao > i {
    margin-top: 2px;

    color: var(--azul);
    font-size: .85rem;
}

.vuln-sugestao strong {
    display: block;

    margin-bottom: 2px;

    color: var(--azul);

    font-size: .68rem;
    font-weight: 700;

    letter-spacing: .5px;
    text-transform: uppercase;
}

.vuln-sugestao span {
    color: #3e4160;

    font-size: .8rem;
    line-height: 1.65;
}

.card-vuln.sev-baixa   { border-left-color: #1f9d55; }
.tag-baixa             { background: #e9f7ee; color: #1a8348; }

.card-vuln.sev-media   { border-left-color: #e0a100; }
.tag-media             { background: #fff6dc; color: #a96a00; }

.card-vuln.sev-alta    { border-left-color: #f2791f; }
.tag-alta              { background: #fff0e0; color: #c2570c; }

.card-vuln.sev-critica { border-left-color: #d93a3f; }
.tag-critica           { background: #fdecec; color: #c0282d; }


/* =========================================
   CÓDIGO CORRIGIDO
========================================= */

.codigo-corrigido {
    margin-top: 20px;

    border-radius: 12px;

    background: #14162a;

    overflow: hidden;
}

.codigo-corrigido-topo {
    display: flex;
    align-items: center;
    justify-content: space-between;

    padding: 10px 14px;

    background: #1d2038;
    color: #c9cbe0;

    font-size: .74rem;
    font-weight: 600;
}

.codigo-corrigido-topo span {
    display: flex;
    align-items: center;
    gap: 8px;
}

.codigo-corrigido-topo span i {
    color: var(--amarelo);
}

.btn-copiar-codigo {
    display: inline-flex;
    align-items: center;
    gap: 6px;

    padding: 5px 12px;

    border: 1px solid rgba(255, 255, 255, .18);
    border-radius: 7px;

    background: transparent;
    color: #fff;

    font-family: inherit;
    font-size: .7rem;
    font-weight: 600;

    cursor: pointer;

    transition: background .2s ease, color .2s ease, border-color .2s ease;
}

.btn-copiar-codigo:hover {
    background: var(--amarelo);
    color: #222;

    border-color: var(--amarelo);
}

.codigo-corrigido pre {
    margin: 0;
    padding: 16px;

    max-height: 420px;

    overflow: auto;

    color: #e8e8f0;

    font-family: monospace;
    font-size: .8rem;
    line-height: 1.65;

    white-space: pre;
}

.codigo-corrigido pre code {
    font-family: monospace;
}


/* =========================================
   STATUS / LOADING / ANEXO PENDENTE
========================================= */

#status,
.loading-status,
.anexo-pendente,
.input-bar,
.input-dica {
    width: min(var(--largura-chat), calc(100% - 32px));

    margin-left: auto;
    margin-right: auto;
}

#status:empty {
    display: none;
}

#status:not(:empty) {
    margin-bottom: 10px;
    padding: 11px 16px;

    border: 1px solid #f3c0c3;
    border-radius: 10px;

    background: #fdecec;
    color: #b02a37;

    font-size: .8rem;
}

.loading-status {
    display: none;

    align-items: center;
    gap: 10px;

    margin-bottom: 10px;
    padding: 0 6px;

    color: var(--texto-suave);

    font-size: .8rem;
}

.spinner {
    width: 16px;
    height: 16px;

    border: 2px solid #d9daee;
    border-top-color: var(--azul);
    border-radius: 50%;

    animation: girar .7s linear infinite;
}

@keyframes girar {
    to {
        transform: rotate(360deg);
    }
}

.anexo-pendente {
    padding: 0 2px 10px;
}

.anexo-pendente[hidden] {
    display: none;
}

.anexo-chip {
    display: inline-flex;
    align-items: center;
    gap: 9px;

    max-width: 100%;

    padding: 8px 12px;

    border-radius: 10px;

    font-size: .78rem;
    font-weight: 600;
    line-height: 1.2;
}

.anexo-chip i {
    flex-shrink: 0;

    font-size: 1rem;
}

.anexo-chip .anexo-nome {
    min-width: 0;

    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.anexo-chip .anexo-tamanho {
    flex-shrink: 0;

    font-size: .68rem;
    font-weight: 500;
    opacity: .75;
}

/* dentro do balão azul */
.bubble-user .anexo-chip {
    align-self: flex-start;

    background: rgba(255, 255, 255, .17);
    color: #fff;
}

/* antes de enviar */
.anexo-pendente .anexo-chip {
    background: #fff;
    color: var(--azul);

    border: 1px solid var(--borda);
}

.anexo-remover {
    flex-shrink: 0;

    width: 22px;
    height: 22px;
    padding: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    border: none;
    border-radius: 50%;

    background: var(--azul-suave);
    color: var(--azul);

    cursor: pointer;

    transition: background .2s ease, color .2s ease;
}

.anexo-remover:hover {
    background: var(--azul);
    color: #fff;
}

.anexo-remover i {
    font-size: .68rem;
}


/* =========================================
   BARRA DE ENVIO
========================================= */

.input-bar {
    flex-shrink: 0;

    padding: 8px;

    display: flex;
    align-items: center;
    gap: 8px;

    background: #fff;

    border: 1px solid var(--borda);
    border-radius: 16px;

    box-shadow: 0 6px 22px rgba(27, 29, 46, .07);

    transition: border-color .2s ease, box-shadow .2s ease;
}

.input-bar:focus-within {
    border-color: var(--azul);

    box-shadow: 0 6px 22px rgba(67, 72, 217, .12), 0 0 0 3px rgba(67, 72, 217, .08);
}

.input-bar textarea {
    flex: 1;
    min-width: 0;

    min-height: 42px;
    max-height: 110px;

    padding: 10px 12px;

    border: none;
    outline: none;

    resize: none;

    background: transparent;
    color: var(--texto);

    font-family: inherit;
    font-size: .88rem;
    line-height: 1.5;
}

.input-bar textarea::placeholder {
    color: #a3a5bb;
}

.btn-anexo {
    flex-shrink: 0;

    height: 42px;

    padding: 0 14px;

    display: flex;
    align-items: center;
    gap: 8px;

    border-radius: 11px;

    background: var(--fundo);
    color: var(--texto-suave);

    font-size: .8rem;
    font-weight: 500;

    cursor: pointer;

    transition: background .2s ease, color .2s ease;
}

.btn-anexo:hover {
    background: var(--azul-suave);
    color: var(--azul);
}

.btn-anexo.tem-arquivo {
    background: var(--azul-suave);
    color: var(--azul);
}

.btn-anexo input {
    display: none;
}

.btn-enviar {
    flex-shrink: 0;

    height: 42px;

    padding: 0 20px;

    display: flex;
    align-items: center;
    gap: 9px;

    border: none;
    border-radius: 11px;

    background: var(--amarelo);
    color: #222;

    font-family: inherit;
    font-size: .84rem;
    font-weight: 600;

    cursor: pointer;

    transition: background .2s ease, transform .2s ease;
}

.btn-enviar:hover {
    background: #f6c944;

    transform: translateY(-1px);
}

.input-dica {
    margin-top: 8px;
    margin-bottom: 12px;

    text-align: center;

    color: #a3a5bb;

    font-size: .68rem;
}

.input-dica kbd {
    padding: 1px 6px;

    border: 1px solid var(--borda);
    border-radius: 5px;

    background: #fff;

    font-family: inherit;
    font-size: .64rem;
    font-weight: 600;
}


/* =========================================
   ARRASTAR E SOLTAR ARQUIVO
========================================= */

.chat-card > .drop-overlay {
    position: absolute;
    inset: 0;

    z-index: 20;

    display: flex;
    align-items: center;
    justify-content: center;

    background: rgba(67, 72, 217, .9);

    opacity: 0;
    pointer-events: none;

    transition: opacity .2s ease;
}

.drop-overlay.ativo {
    opacity: 1;
}

.drop-conteudo {
    padding: 34px 44px;

    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;

    border: 2px dashed rgba(255, 255, 255, .7);
    border-radius: 20px;

    color: #fff;

    text-align: center;
}

.drop-conteudo i {
    margin-bottom: 6px;

    color: var(--amarelo);
    font-size: 2.4rem;
}

.drop-conteudo strong {
    font-size: 1.15rem;
    font-weight: 600;
}

.drop-conteudo span {
    font-size: .8rem;
    opacity: .85;
}


/* =========================================
   TABLET
========================================= */

@media (max-width: 1050px) {

    header {
        padding: 0 24px;
    }

    .layout.historico-aberto {
        grid-template-columns: 250px minmax(0, 1fr);
    }

    .chat-topo {
        padding: 0 20px;
    }

    .chat {
        padding-left: 20px;
        padding-right: 20px;
    }
}


/* =========================================
   MOBILE
========================================= */

@media (max-width: 768px) {

    /* ---- header compacto: logo + hambúrguer + bolinha ---- */

    header {
        height: calc(58px + env(safe-area-inset-top));

        padding: env(safe-area-inset-top) 18px 0;
    }

    .logo {
        font-size: 1.4rem;
    }

    header > nav {
        display: none;
    }

    .menu-hamb {
        display: block;
    }

    .conta-botao:hover {
        transform: none;
    }

    .conta-dropdown,
    .hamb-dropdown {
        top: calc(100% + 8px);

        min-width: 180px;
    }

    /* ---- histórico vira gaveta ---- */

    .layout,
    .layout.historico-aberto {
        display: block;
    }

    .sidebar {
        position: fixed;

        top: 0;
        left: 0;

        z-index: 50;

        width: min(310px, 85vw);
        height: 100dvh;

        padding: 18px 14px;

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

    .layout.historico-aberto .historico-overlay {
        display: block;

        position: fixed;
        inset: 0;

        z-index: 40;

        background: rgba(20, 22, 60, .45);
    }

    /* ---- barra da conversa ---- */

    .chat-topo {
        padding: 0 12px;
        gap: 10px;
    }

    .status-ia b {
        display: none;
    }

    /* ---- chat ---- */

    .chat {
        padding: 18px 12px 14px;
        gap: 18px;
    }

    .msg {
        gap: 8px;
    }

    .avatar-ia {
        width: 30px;
        height: 30px;

        border-radius: 10px;
    }

    .avatar-ia img {
        width: 20px;
        height: 20px;
    }

    .bubble-user {
        max-width: 88%;

        font-size: .84rem;
    }

    .bubble-ia {
        padding: 16px;
    }

    .relatorio-topo {
        gap: 13px;
    }

    .card-vuln summary {
        padding: 12px 13px;
        gap: 9px;
    }

    .vuln-corpo {
        padding: 0 13px 13px;
    }

    .chat-vazio {
        padding: 16px;
    }

    .chat-vazio h3 {
        font-size: 1.3rem;
    }

    .formatos {
        display: none;
    }

    /* ---- barra de envio ---- */

    #status,
    .loading-status,
    .anexo-pendente,
    .input-bar {
        width: calc(100% - 20px);
    }

    .input-bar {
        margin-bottom: 10px;
        padding: 6px;

        border-radius: 14px;
    }

    .btn-anexo,
    .btn-enviar {
        width: 40px;
        height: 40px;

        padding: 0;

        justify-content: center;
    }

    .texto-anexo,
    .texto-enviar {
        display: none;
    }

    .input-bar textarea {
        min-height: 40px;

        padding: 9px 8px;

        font-size: 16px;      /* evita o zoom automático do iPhone */
    }

    .input-dica {
        display: none;
    }
}
</style>
</head>
<body>
    <header>
        <a href="home.php" class="logo">Crypher.IA</a>

        <nav>
            <a href="home.php">Home</a>
            <a href="<?= $linkContato ?>">Contato</a>
        </nav>

        <div class="header-direita">
            <div class="menu-hamb">
                <button type="button" class="hamb-botao" id="hamb-botao" title="Menu" aria-label="Menu" aria-haspopup="true" aria-expanded="false">
                    <i class="fa-solid fa-bars"></i>
                </button>

                <div class="hamb-dropdown" id="hamb-dropdown">
                    <a href="home.php"><i class="fa-solid fa-house"></i> Home</a>
                    <a href="<?= $linkContato ?>"><i class="fa-regular fa-comments"></i> Contato</a>
                </div>
            </div>

            <div class="conta-menu">
                <button type="button" class="conta-botao" id="conta-botao" title="Minha conta" aria-haspopup="true" aria-expanded="false">
                    <?= htmlspecialchars($inicialUsuario) ?>
                </button>

                <div class="conta-dropdown" id="conta-dropdown">
                    <a href="perfil.php"><i class="fa-regular fa-user"></i> Meu perfil</a>
                    <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sair</a>
                </div>
            </div>
        </div>
    </header>

    <main>

        <div class="layout">

            <div class="historico-overlay" data-toggle-historico></div>

            <aside class="sidebar">
                <div class="historico-topo">
                    <h3>Conversas</h3>
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
                            <span class="conversa-icone"><i class="fa-regular fa-message"></i></span>
                            <span class="conversa-texto">
                                <span class="conversa-titulo"><?= htmlspecialchars(resumoConversa($c)) ?></span>
                                <span class="conversa-data"><?= htmlspecialchars($c['created_at']) ?></span>
                            </span>
                        </a>
                    </div>
                <?php endforeach; ?>
            </aside>

            <div class="chat-card" id="chat-card">

                <!-- barra da conversa -->
                <div class="chat-topo">
                    <button type="button" class="btn-historico" data-toggle-historico>
                        <i class="fa-solid fa-clock-rotate-left"></i> Histórico
                    </button>

                    <div class="chat-titulo">
                        <span><?= htmlspecialchars($tituloAtual) ?></span>
                    </div>

                    <div class="status-ia">
                        <i></i> <b>IA disponível</b>
                    </div>
                </div>

                <?php if (count($historico) === 0): ?>
                    <div class="chat-vazio">
                        <div class="vazio-mascote">
                            <img src="../assets/img/mascote_s_fundo.png" alt="Mascote Crypher.IA">
                        </div>

                        <h3>Analise a segurança do seu código</h3>
                        <p>Cole um trecho ou envie um arquivo e a IA vai procurar vulnerabilidades e sugerir as correções.</p>

                        <div class="sugestoes">
                            <button type="button" class="chip-sugestao" data-prompt="Analise este código procurando SQL Injection:&#10;&#10;">
                                <i class="fa-solid fa-database"></i> SQL Injection
                            </button>
                            <button type="button" class="chip-sugestao" data-prompt="Verifique este código em busca de XSS (Cross-Site Scripting):&#10;&#10;">
                                <i class="fa-solid fa-code"></i> XSS
                            </button>
                            <button type="button" class="chip-sugestao" data-prompt="Revise a segurança deste sistema de login e senhas:&#10;&#10;">
                                <i class="fa-solid fa-key"></i> Login e senhas
                            </button>
                            <button type="button" class="chip-sugestao" data-prompt="Analise a segurança deste upload de arquivos:&#10;&#10;">
                                <i class="fa-solid fa-file-arrow-up"></i> Upload de arquivos
                            </button>
                        </div>

                        <p class="formatos">Formatos aceitos: PHP, JS, Python, SQL, HTML, CSS, JSON, TXT e ZIP</p>
                    </div>
                <?php else: ?>
                    <div class="chat" id="chat">
                        <?php foreach ($historico as $item): ?>
                            <?php $r = json_decode($item['resultado_json'], true) ?: ['linguagem' => null, 'resumo' => '', 'vulnerabilidades' => []]; ?>

                            <?php
                                $arqNome = $item['arquivo_nome'] ?? null;
                                $textoBolha = (string) $item['entrada_texto'];
                                // Quando só mandou arquivo, o texto salvo é "Arquivo enviado: nome" — o chip já diz isso
                                if ($arqNome && $textoBolha === 'Arquivo enviado: ' . $arqNome) {
                                    $textoBolha = '';
                                }

                                $vulns = $r['vulnerabilidades'] ?? [];
                                $pontuacao = pontuacaoSeguranca($vulns);
                                $contagem = ['critica' => 0, 'alta' => 0, 'media' => 0, 'baixa' => 0];
                                foreach ($vulns as $v) {
                                    $s = strtolower($v['severidade'] ?? 'baixa');
                                    if (isset($contagem[$s])) {
                                        $contagem[$s]++;
                                    }
                                }
                            ?>

                            <div class="msg msg-user">
                                <div class="bubble-user"><?php if ($arqNome): ?><div class="anexo-chip"><i class="fa-solid <?= iconeAnexo($arqNome) ?>"></i><span class="anexo-nome"><?= htmlspecialchars($arqNome) ?></span></div><?php endif; ?><?php if ($textoBolha !== ''): ?><div class="bubble-texto"><?= htmlspecialchars($textoBolha) ?></div><?php endif; ?></div>
                            </div>

                            <div class="msg msg-ia">
                                <div class="avatar-ia"><img src="../assets/img/mascote_s_fundo.png" alt=""></div>

                                <div class="bubble-ia">

                                    <div class="relatorio-topo">
                                        <?= gaugeHtml($pontuacao) ?>
                                        <div class="relatorio-info">
                                            <div class="relatorio-titulo">
                                                Relatório de segurança
                                                <?php if (!empty($r['linguagem'])): ?>
                                                    <span class="badge-linguagem"><?= htmlspecialchars($r['linguagem']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="chips-sev">
                                                <?php foreach ($contagem as $s => $n): ?>
                                                    <?php if ($n > 0): ?>
                                                        <span class="chip-sev chip-<?= $s ?>"><?= $n ?> <?= rotuloSev($s) ?></span>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                                <?php if (count($vulns) === 0): ?>
                                                    <span class="chip-sev chip-ok">Nenhum problema encontrado</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <p class="resumo"><?= htmlspecialchars($r['resumo'] ?? '') ?></p>

                                    <?php if (count($vulns) > 0): ?>
                                        <div class="secao-titulo">Problemas encontrados</div>
                                    <?php endif; ?>

                                    <?php foreach ($vulns as $v): ?>
                                        <?php $sev = strtolower($v['severidade'] ?? 'baixa'); ?>
                                        <details class="card-vuln sev-<?= htmlspecialchars($sev) ?>" <?= in_array($sev, ['critica', 'alta'], true) ? 'open' : '' ?>>
                                            <summary>
                                                <span class="vuln-titulo"><?= htmlspecialchars($v['titulo'] ?? '') ?></span>
                                                <span class="tag-sev tag-<?= htmlspecialchars($sev) ?>"><?= htmlspecialchars(rotuloSev($sev)) ?></span>
                                                <i class="fa-solid fa-chevron-down"></i>
                                            </summary>
                                            <div class="vuln-corpo">
                                                <p><?= htmlspecialchars($v['explicacao'] ?? '') ?></p>
                                                <div class="vuln-sugestao">
                                                    <i class="fa-solid fa-lightbulb"></i>
                                                    <div>
                                                        <strong>Como corrigir</strong>
                                                        <span><?= htmlspecialchars($v['sugestao'] ?? '') ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </details>
                                    <?php endforeach; ?>

                                    <?php if (!empty($r['codigo_corrigido'])): ?>
                                        <div class="codigo-corrigido">
                                            <div class="codigo-corrigido-topo">
                                                <span><i class="fa-solid fa-code"></i> Código corrigido</span>
                                                <button type="button" class="btn-copiar-codigo"><i class="fa-regular fa-copy"></i> Copiar</button>
                                            </div>
                                            <pre><code><?= htmlspecialchars($r['codigo_corrigido']) ?></code></pre>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div id="status"></div>

                <div class="loading-status" id="loading-status">
                    <span class="spinner"></span>
                    <span>Analisando código...</span>
                </div>

                <div class="anexo-pendente" id="anexo-pendente" hidden>
                    <div class="anexo-chip">
                        <i class="fa-solid fa-file-code" id="anexo-pendente-icone"></i>
                        <span class="anexo-nome" id="anexo-pendente-nome"></span>
                        <span class="anexo-tamanho" id="anexo-pendente-tamanho"></span>
                        <button type="button" class="anexo-remover" id="anexo-remover" title="Remover arquivo" aria-label="Remover arquivo"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                </div>

                <form id="form-analise" class="input-bar" enctype="multipart/form-data">
                    <input type="hidden" name="conversa_id" value="<?= $conversaId ?>">

                    <textarea id="texto" name="texto" placeholder="Cole seu código aqui..." rows="1"></textarea>

                    <label class="btn-anexo" id="btn-anexo" title="Anexar arquivo">
                        <i class="fa-solid fa-paperclip" id="icone-anexo"></i> <span class="texto-anexo">Arquivo</span>
                        <input type="file" name="arquivo" id="arquivo" accept=".php,.js,.py,.sql,.html,.css,.txt,.json,.zip">
                    </label>

                    <button type="submit" class="btn-enviar" title="Enviar">
                        <i class="fa-solid fa-paper-plane"></i> <span class="texto-enviar">Enviar</span>
                    </button>
                </form>

                <p class="input-dica"><kbd>Enter</kbd> envia &nbsp;•&nbsp; <kbd>Shift</kbd> + <kbd>Enter</kbd> quebra a linha</p>

                <!-- aviso ao arrastar um arquivo para o chat -->
                <div class="drop-overlay" id="drop-overlay">
                    <div class="drop-conteudo">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <strong>Solte o arquivo aqui</strong>
                        <span>PHP, JS, Python, SQL, HTML, CSS, JSON, TXT ou ZIP</span>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script>
    (() => {
        const chatCard = document.getElementById('chat-card');
        let chat = document.getElementById('chat');
        const form = document.getElementById('form-analise');
        const textarea = document.getElementById('texto');
        const inputArquivo = document.getElementById('arquivo');
        const btnAnexo = document.getElementById('btn-anexo');
        const anexoPendente = document.getElementById('anexo-pendente');
        const anexoPendenteIcone = document.getElementById('anexo-pendente-icone');
        const anexoPendenteNome = document.getElementById('anexo-pendente-nome');
        const anexoPendenteTamanho = document.getElementById('anexo-pendente-tamanho');
        const btnRemoverAnexo = document.getElementById('anexo-remover');
        const statusEl = document.getElementById('status');
        const loadingStatus = document.getElementById('loading-status');
        const dropOverlay = document.getElementById('drop-overlay');

        const MASCOTE = '../assets/img/mascote_s_fundo.png';
        const botaoCopiarHtml = '<i class="fa-regular fa-copy"></i> Copiar';

        /* ---------- campo de texto ---------- */

        function ajustarAlturaTexto() {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 110) + 'px';
        }

        textarea.addEventListener('input', ajustarAlturaTexto);

        // Enter envia no computador; Shift+Enter quebra a linha (no celular Enter sempre quebra)
        textarea.addEventListener('keydown', (e) => {
            if (e.key !== 'Enter' || e.shiftKey || e.isComposing) return;
            if (window.innerWidth <= 768) return;

            e.preventDefault();

            if (form.requestSubmit) {
                form.requestSubmit();
            } else {
                form.dispatchEvent(new Event('submit', { cancelable: true }));
            }
        });

        // Sugestões da tela vazia preenchem o campo
        document.addEventListener('click', (e) => {
            const chip = e.target.closest('.chip-sugestao');
            if (!chip) return;

            textarea.value = chip.dataset.prompt || '';
            ajustarAlturaTexto();
            textarea.focus();
            textarea.setSelectionRange(textarea.value.length, textarea.value.length);
        });

        /* ---------- anexos ---------- */

        function iconeDoArquivo(nome) {
            const ext = (nome.split('.').pop() || '').toLowerCase();
            if (ext === 'zip') return 'fa-file-zipper';
            if (ext === 'sql') return 'fa-database';
            if (ext === 'txt') return 'fa-file-lines';
            return 'fa-file-code';
        }

        function formatarTamanho(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1).replace('.', ',') + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1).replace('.', ',') + ' MB';
        }

        // Mostra o arquivo escolhido como um anexo acima da barra de envio
        function atualizarAnexoPendente() {
            const arquivo = inputArquivo.files[0];
            if (arquivo) {
                anexoPendenteIcone.className = 'fa-solid ' + iconeDoArquivo(arquivo.name);
                anexoPendenteNome.textContent = arquivo.name;
                anexoPendenteTamanho.textContent = formatarTamanho(arquivo.size);
                anexoPendente.hidden = false;
                btnAnexo.classList.add('tem-arquivo');
            } else {
                anexoPendente.hidden = true;
                btnAnexo.classList.remove('tem-arquivo');
            }
            irParaFinal();
        }

        function limparAnexo() {
            inputArquivo.value = '';
            atualizarAnexoPendente();
        }

        inputArquivo.addEventListener('change', atualizarAnexoPendente);
        btnRemoverAnexo.addEventListener('click', limparAnexo);

        /* ---------- arrastar e soltar ---------- */

        let contadorArraste = 0;

        const temArquivo = (e) => e.dataTransfer && Array.from(e.dataTransfer.types || []).includes('Files');

        chatCard.addEventListener('dragenter', (e) => {
            if (!temArquivo(e)) return;
            e.preventDefault();
            contadorArraste++;
            dropOverlay.classList.add('ativo');
        });

        chatCard.addEventListener('dragover', (e) => {
            if (!temArquivo(e)) return;
            e.preventDefault();
        });

        chatCard.addEventListener('dragleave', (e) => {
            if (!temArquivo(e)) return;
            contadorArraste = Math.max(0, contadorArraste - 1);
            if (contadorArraste === 0) dropOverlay.classList.remove('ativo');
        });

        chatCard.addEventListener('drop', (e) => {
            if (!temArquivo(e)) return;
            e.preventDefault();

            contadorArraste = 0;
            dropOverlay.classList.remove('ativo');

            if (e.dataTransfer.files.length > 0) {
                try {
                    inputArquivo.files = e.dataTransfer.files;
                } catch (erro) {
                    return;
                }
                atualizarAnexoPendente();
            }
        });

        /* ---------- renderização das mensagens ---------- */

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
                chatCard.insertBefore(chat, statusEl);
            }
        }

        function irParaFinal() {
            if (chat) chat.scrollTop = chat.scrollHeight;
        }

        const ROTULOS_SEV = { critica: 'Crítica', alta: 'Alta', media: 'Média', baixa: 'Baixa' };

        // Mesma regra do PHP: cada falha desconta pontos conforme a gravidade
        function pontuacao(vulns) {
            const peso = { critica: 35, alta: 22, media: 12, baixa: 5 };
            let p = 100;
            vulns.forEach(v => {
                const s = (v.severidade || 'baixa').toLowerCase();
                p -= peso[s] ?? 5;
            });
            return Math.max(0, p);
        }

        function corPontuacao(p) {
            if (p >= 80) return '#1f9d55';
            if (p >= 50) return '#e08a00';
            return '#d93a3f';
        }

        function gaugeHtml(p) {
            const c = 163.36;
            const dash = (p / 100 * c).toFixed(2);
            return `
                <div class="gauge" style="--cor:${corPontuacao(p)}" title="Nota de segurança: ${p} de 100">
                    <svg viewBox="0 0 64 64" aria-hidden="true">
                        <circle class="gauge-fundo" cx="32" cy="32" r="26"></circle>
                        <circle class="gauge-valor" cx="32" cy="32" r="26" style="stroke-dasharray:${dash} ${c}"></circle>
                    </svg>
                    <span class="gauge-num">${p}</span>
                </div>
            `;
        }

        // Balão do usuário: arquivo anexado (chip) + texto digitado, se houver
        function renderizarBubbleUser(texto, arquivo) {
            garantirChat();

            const msg = document.createElement('div');
            msg.className = 'msg msg-user';

            const bolha = document.createElement('div');
            bolha.className = 'bubble-user';

            if (arquivo) {
                const chip = document.createElement('div');
                chip.className = 'anexo-chip';

                const icone = document.createElement('i');
                icone.className = 'fa-solid ' + iconeDoArquivo(arquivo.name);

                const nomeArq = document.createElement('span');
                nomeArq.className = 'anexo-nome';
                nomeArq.textContent = arquivo.name;

                const tamanho = document.createElement('span');
                tamanho.className = 'anexo-tamanho';
                tamanho.textContent = formatarTamanho(arquivo.size);

                chip.append(icone, nomeArq, tamanho);
                bolha.appendChild(chip);
            }

            if (texto) {
                const t = document.createElement('div');
                t.className = 'bubble-texto';
                t.textContent = texto;
                bolha.appendChild(t);
            }

            msg.appendChild(bolha);
            chat.appendChild(msg);

            return msg;   // quem chama remove o conjunto inteiro se der erro
        }

        function renderizarBubbleIA(resultado) {
            garantirChat();

            const vulns = resultado.vulnerabilidades || [];
            const p = pontuacao(vulns);

            const contagem = { critica: 0, alta: 0, media: 0, baixa: 0 };
            vulns.forEach(v => {
                const s = (v.severidade || 'baixa').toLowerCase();
                if (s in contagem) contagem[s]++;
            });

            let chips = '';
            Object.keys(contagem).forEach(s => {
                if (contagem[s] > 0) {
                    chips += `<span class="chip-sev chip-${s}">${contagem[s]} ${ROTULOS_SEV[s]}</span>`;
                }
            });
            if (vulns.length === 0) {
                chips = '<span class="chip-sev chip-ok">Nenhum problema encontrado</span>';
            }

            let html = `
                <div class="relatorio-topo">
                    ${gaugeHtml(p)}
                    <div class="relatorio-info">
                        <div class="relatorio-titulo">
                            Relatório de segurança
                            ${resultado.linguagem ? `<span class="badge-linguagem">${escapar(resultado.linguagem)}</span>` : ''}
                        </div>
                        <div class="chips-sev">${chips}</div>
                    </div>
                </div>
                <p class="resumo">${escapar(resultado.resumo || '')}</p>
            `;

            if (vulns.length > 0) {
                html += '<div class="secao-titulo">Problemas encontrados</div>';
            }

            vulns.forEach(v => {
                const sev = escapar((v.severidade || 'baixa').toLowerCase());
                const rotulo = ROTULOS_SEV[sev] || sev;
                const aberto = (sev === 'critica' || sev === 'alta') ? 'open' : '';

                html += `
                    <details class="card-vuln sev-${sev}" ${aberto}>
                        <summary>
                            <span class="vuln-titulo">${escapar(v.titulo || '')}</span>
                            <span class="tag-sev tag-${sev}">${escapar(rotulo)}</span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </summary>
                        <div class="vuln-corpo">
                            <p>${escapar(v.explicacao || '')}</p>
                            <div class="vuln-sugestao">
                                <i class="fa-solid fa-lightbulb"></i>
                                <div>
                                    <strong>Como corrigir</strong>
                                    <span>${escapar(v.sugestao || '')}</span>
                                </div>
                            </div>
                        </div>
                    </details>
                `;
            });

            if (resultado.codigo_corrigido) {
                html += `
                    <div class="codigo-corrigido">
                        <div class="codigo-corrigido-topo">
                            <span><i class="fa-solid fa-code"></i> Código corrigido</span>
                            <button type="button" class="btn-copiar-codigo">${botaoCopiarHtml}</button>
                        </div>
                        <pre><code>${escapar(resultado.codigo_corrigido)}</code></pre>
                    </div>
                `;
            }

            const msg = document.createElement('div');
            msg.className = 'msg msg-ia';
            msg.innerHTML = `
                <div class="avatar-ia"><img src="${MASCOTE}" alt=""></div>
                <div class="bubble-ia">${html}</div>
            `;

            chat.appendChild(msg);
        }

        // Botão "Copiar" do código corrigido (funciona nas mensagens do histórico e nas novas)
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.btn-copiar-codigo');
            if (!btn) return;

            const codigo = btn.closest('.codigo-corrigido').querySelector('code').textContent;

            navigator.clipboard.writeText(codigo).then(() => {
                btn.innerHTML = '<i class="fa-solid fa-check"></i> Copiado!';
                setTimeout(() => btn.innerHTML = botaoCopiarHtml, 1500);
            });
        });

        /* ---------- envio ---------- */

        form.addEventListener('submit', async (evento) => {
            evento.preventDefault();

            const codigo = textarea.value.trim();
            const arquivo = inputArquivo.files[0];
            if (codigo === '' && !arquivo) return;

            statusEl.textContent = '';
            loadingStatus.style.display = 'flex'; // feedback visível durante a análise

            const bolhaUser = renderizarBubbleUser(codigo, arquivo);
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
                    limparAnexo();
                } else {
                    // Falhou: tira o balão e mantém texto/arquivo pra tentar de novo sem duplicar
                    bolhaUser.remove();
                    statusEl.textContent = 'Erro: ' + dados.erro;
                }
            } catch (erro) {
                bolhaUser.remove();
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
<script>
/* ===== Bolinha da conta ===== */
(() => {
    const contaBotao = document.getElementById('conta-botao');
    const contaDropdown = document.getElementById('conta-dropdown');
    if (!contaBotao || !contaDropdown) return;

    const fecharConta = () => {
        contaDropdown.classList.remove('ativo');
        contaBotao.setAttribute('aria-expanded', 'false');
    };

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
})();
</script>
<script>
/* ===== Teclado do celular ===== */
(() => {
    const textarea = document.getElementById('texto');
    if (!textarea) return;

    const ehMobile = () => window.innerWidth <= 768;

    // Acompanha a altura visível da tela (corrige o iPhone)
    function ajustarAltura() {
        if (window.visualViewport && ehMobile()) {
            document.body.style.height = window.visualViewport.height + 'px';
            window.scrollTo(0, 0);
            const chat = document.getElementById('chat');
            if (chat) chat.scrollTop = chat.scrollHeight;
        } else {
            document.body.style.height = '';
        }
    }

    if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', ajustarAltura);
    }
    window.addEventListener('resize', ajustarAltura);
    ajustarAltura();

})();
</script>
<script>
/* ===== Menu hambúrguer (celular) ===== */
(() => {
    const botao = document.getElementById('hamb-botao');
    const menu = document.getElementById('hamb-dropdown');
    if (!botao || !menu) return;

    const icone = botao.querySelector('i');

    const fechar = () => {
        menu.classList.remove('ativo');
        botao.setAttribute('aria-expanded', 'false');
        icone.className = 'fa-solid fa-bars';
    };

    botao.addEventListener('click', (e) => {
        e.stopPropagation();

        // fecha o menu da bolinha se estiver aberto
        document.querySelectorAll('.conta-dropdown.ativo').forEach(d => d.classList.remove('ativo'));
        document.querySelectorAll('.conta-botao').forEach(b => b.setAttribute('aria-expanded', 'false'));

        const aberto = menu.classList.toggle('ativo');
        botao.setAttribute('aria-expanded', aberto ? 'true' : 'false');
        icone.className = aberto ? 'fa-solid fa-xmark' : 'fa-solid fa-bars';
    });

    // abrir o menu da bolinha fecha o hambúrguer
    document.querySelectorAll('.conta-botao').forEach(b => b.addEventListener('click', fechar));

    document.addEventListener('click', (e) => {
        if (!menu.contains(e.target)) fechar();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') fechar();
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) fechar();
    });
})();
</script>
</body>
</html> 
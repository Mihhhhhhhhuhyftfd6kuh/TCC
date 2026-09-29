<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$usuarioLogado = isset($_SESSION['id']) && $_SESSION['id'] !== null;

// Letra que aparece na bolinha da conta (primeira letra do nome)
$nomeUsuario = '';
$inicialUsuario = '?';

if ($usuarioLogado) {
    try {
        require __DIR__ . '/../config/config.php';

        $stmtNome = $pdo->prepare("SELECT nome FROM usuarios WHERE id = :id");
        $stmtNome->bindValue(':id', (int) $_SESSION['id'], PDO::PARAM_INT);
        $stmtNome->execute();

        $nomeUsuario = trim((string) $stmtNome->fetchColumn());

        if ($nomeUsuario !== '') {
            $inicialUsuario = mb_strtoupper(mb_substr($nomeUsuario, 0, 1));
        }
    } catch (Throwable $e) {
        // se der erro no banco, a home continua funcionando com "?"
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chypher.IA</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
body {
    background: #4348D9;
}

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
}

body{
    background:#4348D9;
    min-height:100vh;
   
}

/*chuvinha*/
#rainCanvas {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 0;
    pointer-events: none;
}

header{
    position:absolute;
    top:0;
    left:0;
    width:100%;
    padding:25px 80px;

    display:flex;
    justify-content:space-between;
    align-items:center;

    z-index:100;
}

.logo{
    color:white;
    font-size:2.5rem;
    font-weight:700;
}

nav{
    display:flex;
    gap:40px;
}

nav a{
    text-decoration:none;
    color:white;
    font-weight:500;
    transition:.3s;
}

nav a:hover{
    opacity:.7;
}

.btn-cadastro{
    display:inline-flex;
    align-items:center;
    justify-content:center;

    height:52px;
    padding:0 28px;

    border:none;
    border-radius:999px;

    background:#F3BE27;
    color:#222;

    font-size:1rem;
    font-weight:700;

    cursor:pointer;

    transition:all .3s ease;

    box-shadow:0 8px 18px rgba(243,190,39,.30);
}

.btn-cadastro:hover{
    transform:translateY(-3px) scale(1.03);

    box-shadow:0 12px 24px rgba(243,190,39,.45);
}

.conta-menu{
    position:relative;
}

.conta-botao{
    width:46px;
    height:46px;

    display:flex;
    align-items:center;
    justify-content:center;

    background:#fff;
    color:#4348D9;
    border:none;
    border-radius:50%;

    font-weight:700;
    font-size:1.15rem;
    line-height:1;

    cursor:pointer;

    box-shadow:0 4px 12px rgba(0,0,0,.18);
    transition:.3s ease;
}

.conta-botao:hover{
    transform:translateY(-2px);
    box-shadow:0 8px 18px rgba(0,0,0,.25);
}

.conta-dropdown{
    display:none;
    position:absolute;
    top:calc(100% + 10px);
    right:0;

    background:#fff;
    border-radius:12px;
    overflow:hidden;
    min-width:190px;

    box-shadow:0 15px 35px rgba(0,0,0,.22);
    z-index:200;
}

.conta-dropdown.ativo{
    display:block;
}

.conta-dropdown a{
    display:block;
    padding:13px 20px;

    color:#222;
    font-size:.9rem;
    font-weight:500;

    transition:.2s ease;
}

.conta-dropdown a:hover{
    background:#f2f2f2;
}

.hero {
    position: relative;
    z-index: 2;
    min-height: 100vh;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 120px 80px;
    overflow: hidden;
}

.hero-text{
    max-width:650px;
}

.subtitle{
    color:white;
    font-size:1.4rem;
}

.hero-text h1{
    color:white;
    font-size:6rem;
    line-height:1.1;
    margin:15px 0;
}

.hero-text p{
    color:white;
    font-size:1.3rem;
    max-width:500px;
    line-height:1.7;
}

.hero-buttons{
    display:flex;
    gap:20px;
    margin-top:40px;
}

.btn-primary{
    width:190px;
    height:55px;

    border:none;
    border-radius:999px;

    background:#F3BE27;
    color:#222;

    font-size:1.05rem;
    font-weight:700;

    cursor:pointer;

    transition:all .3s ease;

    box-shadow:0 8px 18px rgba(243,190,39,.30);
}

.btn-primary:hover{
    transform:translateY(-3px) scale(1.03);

    box-shadow:0 12px 24px rgba(243,190,39,.45);
}

.btn-secondary{
    width:190px;
    height:55px;

    background:rgba(255,255,255,0.12);

    border:1px solid rgba(255,255,255,.35);
    border-radius:999px;

    color:white;

    font-size:1.05rem;
    font-weight:700;

    cursor:pointer;

    backdrop-filter:blur(10px);

    transition:all .3s ease;
}


.hero-img{
    width:450px;     
    height:auto;       
    max-width:100%;

    border-radius:9px;

    box-shadow:0 20px 40px rgba(0,0,0,.20);
}



.hero-image{
    width:400px;
    height:400px;

    background:#000000;

    border-radius:8px;

    display:flex;
    justify-content:center;
    align-items:center;

    font-size:5rem;
    font-weight:700;
    color:#333;

    animation:float 5s ease-in-out infinite;

    box-shadow:
        0 20px 40px rgba(0,0,0,.15);
}

@keyframes float{ /* engloba todas as animações*/

    0%{
        transform:translateY(0);
    }

    50%{
        transform:translateY(-15px);
    }

    100%{
        transform:translateY(0);
    }

}

@media(max-width:1200px){ /*é usado pra mudar o CSS dependendo do tamanho da tela*/

    .hero{
        flex-direction:column;
        justify-content:center;
        text-align:center;
        gap:50px;
    }

    .hero-buttons{
        justify-content:center;
    }

}

@media(max-width:768px){

    header{
        flex-direction:column;
        gap:20px;
        padding:20px;
    }

    nav{
        gap:20px;
        flex-wrap:wrap;
        justify-content:center;
    }

    .hero{
        padding:180px 20px 50px;
    }

    .hero-text h1{
        font-size:3.5rem;
    }

    .image-placeholder{
        width:320px;
        height:320px;
        font-size:3rem;
    }

}

.sobre{
    background:#fff;
    padding:70px 80px 90px;
    text-align:center;
}

.sobre h2{
    font-size:3rem;
    margin-bottom:20px;
    text-align:center;
    position:relative;
    z-index:2;
}
.sobre-texto{
    background:#f2f2f2;
    max-width:1100px;
    margin:40px auto 0;
    padding:50px 60px;
    border-radius:20px;

    box-shadow:
        0 15px 35px rgba(0,0,0,0.18);

    position:relative;
    z-index:2;
}


.sobre-texto p{
    font-size:1.2rem;
    line-height:1.8;
    color:#222222;
    text-align:left;
}

.intro{
    max-width:900px;
    margin:70px auto 40px;
    font-size:2rem;
    font-weight:600;
    text-align:center;
}

.cards-vulnerabilidades{
    display:flex;
    justify-content:center;
    gap:35px;
    flex-wrap:wrap;

    margin-top:50px;
    margin-bottom:60px;
}

.card-vulnerabilidade{

    width:320px;
    min-height:320px;

    background:#F3BE27;

    border-radius:20px;

    padding:35px;

    color:rgb(0, 0, 0);

    text-align:left;

    box-shadow:0 15px 30px rgba(0,0,0,.15);

    transition:.35s ease;
}

.card-vulnerabilidade:hover{

    transform:translateY(-10px);

    box-shadow:0 25px 45px rgba(0, 0, 0, 0.22);
}

.icone-vulnerabilidade{

    width:70px;
    height:70px;

    border-radius:50%;

    background:#ffffff;

    display:flex;
    justify-content:center;
    align-items:center;

    font-size:2rem;

    margin-bottom:25px;
}

.card-vulnerabilidade h3{

    font-size:1.6rem;

    margin-bottom:18px;
}

.card-vulnerabilidade p{

    line-height:1.8;

    color:#000000;

    font-size:1rem;
}

.final{
    text-align:center;
    font-size:1.5rem;
}

@media(max-width:900px){

    .sobre{
        padding:60px 20px;
    }

    .sobre-top{
        flex-direction:column;
        text-align:center;
        gap:30px;
    }

    .sobre-texto p{
        font-size:1.3rem;
    }

    .intro{
        text-align:center;
        font-size:1.2rem;
    }

    .lista-vulnerabilidades{
        flex-direction:column;
        gap:15px;
        align-items:center;
    }

    .lista-vulnerabilidades ul{
        font-size:1.2rem;
    }

}

.equipe{
    background:#ffffff;
    padding:100px 70px;
    text-align:center;
}

.equipe h2{
    font-size:3rem;
    color:#222;
    margin-bottom:15px;
}

.equipe-subtitulo{
    font-size:1.2rem;
    color:#666;
    margin-bottom:60px;
}

.cards-equipe{
    display:flex;
    justify-content:center;
    gap:45px;
    flex-wrap:wrap;
}

.membro{
    width:300px;
    background:#F3BE27;
    border-radius:20px;
    padding:30px 25px;
    box-shadow:0 10px 30px rgba(0,0,0,.08);
    transition:.35s ease;
}

.membro:hover{
    transform:translateY(-8px);
    box-shadow:0 20px 40px rgba(0,0,0,.15);
}

.foto-membro{
    width:190px;
    height:190px;

    margin:0 auto 25px;

    border-radius:50%;
    overflow:hidden;

    border:5px solid #ffffff;

    box-shadow:0 10px 25px rgba(0,0,0,.15);
}

.foto-membro img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
}

.membro h3{
    font-size:1.35rem;
    color:#222;
    margin-bottom:12px;
}

.membro p{
    color:#000000;
    line-height:1.6;
    font-size:1rem;
}

@media(max-width:900px){

    .equipe{
        padding:70px 25px;
    }

    .cards-equipe{
        gap:30px;
    }

    .membro{
        width:100%;
        max-width:340px;
    }

}

@media(max-width:900px){

    .equipe{
        padding:40px 20px 80px;
    }

    .equipe h2{
        font-size:2.2rem;
    }

    .cards-equipe{
        gap:30px;
    }

    .foto-membro{
        width:180px;
        height:180px;
    }

}


.missao{
    background:white;

    display:flex;
    justify-content:center;
    align-items:center;
    gap:80px;

    padding:80px 60px;
    position:relative;
    z-index:2;
}

.missao-imagens{
    position:relative;
    width:450px;
    height:450px;
}

.estrela{
    position:absolute;

    width: 600px;
    height:600px;

    background:#f3be27;

    clip-path:polygon(
        50% 0%,
        61% 35%,
        98% 35%,
        68% 57%,
        79% 91%,
        50% 70%,
        21% 91%,
        32% 57%,
        2% 35%,
        39% 35%
    );

    top:-70px;
    left:-90px;
}

.imagem-grande{
    position:absolute;

    width:170px;
    height:190px;

    top:0;
    left:190px;

    border-radius:15px;
    overflow:hidden;

    box-shadow:0 15px 30px rgba(0,0,0,.20);
}

.imagem-grande img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
}

.imagem-pequena{
    position:absolute;

    width:150px;
    height:150px;

    bottom:80px;
    left:60px;

    border-radius:15px;
    overflow:hidden;

    box-shadow:0 15px 30px rgba(0,0,0,.20);
}

.imagem-pequena img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
}

.imagem-grande{
    animation:flutuar1 5s ease-in-out infinite;
}

.imagem-pequena{
    animation:flutuar2 4s ease-in-out infinite;
}

@keyframes flutuar1{

    0%{
        transform:translateY(0);
    }

    50%{
        transform:translateY(-12px);
    }

    100%{
        transform:translateY(0);
    }

}

@keyframes flutuar2{

    0%{
        transform:translateY(0);
    }

    50%{
        transform:translateY(12px);
    }

    100%{
        transform:translateY(0);
    }

}



.missao-texto{
    max-width:450px;
}

.missao-texto span{
    font-size:1.3rem;
    font-weight:600;
}

.missao-texto h2{
    font-size:4rem;
    line-height:1.15;
    margin:20px 0 40px;
}

footer{
    background:#4348D9;
    padding:40px 60px;
    position:relative;
    z-index:2;
}

.linha-footer{
    width:100%;
    height:1px;
    background:white;
    opacity:.6;
    margin-bottom:25px;
}

.footer-links{
    display:flex;
    justify-content:flex-end;
    gap:50px;
}

.footer-links a{
    color:white;
    text-decoration:none;
    font-size:1.2rem;
}

@media(max-width:1000px){

    .missao{
        flex-direction:column;
        text-align:center;
        gap:40px;
    }

    .missao-texto h2{
        font-size:2.5rem;
    }

    .footer-links{
        justify-content:center;
        flex-wrap:wrap;
    }

}

.como-funciona{
    background:#fff;
    padding:80px 60px;
    text-align:center;
}

.como-funciona h2{
    font-size:3rem;
    color:#000;
    margin-bottom:15px;
}

.como-subtitulo{
    font-size:1.2rem;
    color:#666;
    margin-bottom:60px;
}

.cards-funcao{
    display:flex;
    justify-content:center;
    gap:30px;
    flex-wrap:wrap;
}

.card-funcao{

    width:260px;

    background:#F3BE27;

    color:#000;

    padding:30px;

    border-radius:20px;

    text-align:left;

    box-shadow:0 15px 30px rgba(0,0,0,.12);

    transition:.35s ease;
}

.card-funcao:hover{
    transform:translateY(-8px);
}

.numero-card{

    width:60px;
    height:60px;

    background:#fff;

    border-radius:50%;

    display:flex;
    justify-content:center;
    align-items:center;

    font-size:1.5rem;
    font-weight:700;

    margin-bottom:20px;
}

.card-funcao h3{
    font-size:1.5rem;
    margin-bottom:15px;
}

.card-funcao p{
    line-height:1.7;
}

.deteccoes{

    background:#ffffff;

    padding:90px 70px;

    text-align:center;
}

.deteccoes h2{

    font-size:3rem;

    margin-bottom:15px;
}

.deteccoes-subtitulo{

    font-size:1.2rem;

    color:#000000;

    margin-bottom:60px;
}

.cards-deteccoes{

    display:grid;

    grid-template-columns:repeat(auto-fit,minmax(300px,1fr));

    gap:30px;

    max-width:1200px;

    margin:auto;
}

.card-deteccao{

    background:#F3BE27;

    padding:35px;

    border-radius:20px;

    text-align:left;

    box-shadow:0 12px 25px rgba(0, 0, 0, 0.08);

    transition:.3s ease;
}

.card-deteccao:hover{

    transform:translateY(-8px);

    box-shadow:0 20px 40px rgba(0,0,0,.15);
}

.icone-deteccao{
    width:70px;
    height:70px;

    background: #F3BE27;
    color:#000000;

    border-radius:18px;

    display:flex;
    justify-content:center;
    align-items:center;

    font-size:2rem;

    margin-bottom:25px;

    transition:.3s;
}

.card-deteccao:hover .icone-deteccao{
    transform:rotate(-8deg) scale(1.1);
}

.card-deteccao h3{

    font-size:1.4rem;

    margin-bottom:15px;

    color:#000000;
}

.card-deteccao p{

    line-height:1.8;

    color:#000000;
}

.deteccoes-carrossel{
    width:100%;
    max-width:1200px;
    margin:0 auto;
    position:relative;
}

.deteccoes-carrossel .cards-deteccoes{
    width:100%;
    max-width:none;
    margin:0;
    display:block;
    overflow:hidden;
}

.deteccoes-track{
    display:flex;
    gap:30px;
    transition:transform .45s ease;
    will-change:transform;
}

.deteccoes-track .card-deteccao{
    flex:0 0 calc((100% - 60px) / 3);
    width:auto;
    min-width:0;
}

.carrossel-seta{
    position:absolute;

    top:50%;
    transform:translateY(-50%);

    width:48px;
    height:48px;

    border:none;
    border-radius:50%;

    background:#4348D9;
    color:#F3BE27;

    display:flex;
    align-items:center;
    justify-content:center;

    font-size:1.4rem;
    font-weight:700;

    cursor:pointer;

    z-index:5;

    box-shadow:0 8px 20px rgba(0,0,0,.15);

    transition:.3s ease;
}

.carrossel-seta:hover{
    transform:translateY(-50%) scale(1.08);
}

.carrossel-seta:disabled{
    opacity:.35;
    cursor:not-allowed;
    transform:translateY(-50%);
}

.carrossel-prev{
    left:-24px;
}

.carrossel-next{
    right:-24px;
}

.carrossel-indicadores{
    display:flex;
    justify-content:center;
    align-items:center;
    gap:10px;

    margin-top:30px;
}

.carrossel-indicador{
    width:9px;
    height:9px;

    border:none;
    border-radius:50%;

    background:#D5D5D5;

    padding:0;
    cursor:pointer;

    transition:.3s ease;
}

.carrossel-indicador.ativo{
    width:25px;
    border-radius:999px;
    background:#4348D9;
}

/* =========================================================
   AJUSTES FINAIS — RESPONSIVIDADE E CARROSSEL
   ========================================================= */

/* Evita que elementos largos criem rolagem horizontal */
html,
body {
    overflow-x: hidden;
}

/* Esconde a barra de rolagem visual, sem tirar a rolagem em si */
html{
    scrollbar-width: none;       /* Firefox */
    -ms-overflow-style: none;    /* Edge antigo/IE */
    scroll-behavior: smooth;     /* rolagem suave até as âncoras (#sobre, etc.) */
}

html::-webkit-scrollbar{
    display: none;               /* Chrome, Safari, Opera */
}

/* O canvas fica preso à viewport */
#rainCanvas {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 0;
    pointer-events: none;
}

/* Imagem principal do Hero */
.hero-image {
    max-width: 100%;
}

.hero-image .hero-img {
    display: block;
}

/* =========================================================
   CARROSSEL "O QUE NOSSA IA DETECTA?"
   ========================================================= */

.deteccoes-carrossel {
    width: min(100%, 1200px);
    margin: 0 auto;
    padding: 0 55px;
    position: relative;
}

.deteccoes-carrossel .cards-deteccoes {
    width: 100%;
    max-width: none;
    margin: 0;
    display: block;
    overflow: hidden;
}

.deteccoes-track {
    display: flex;
    gap: 30px;
    transition: transform .45s ease;
    will-change: transform;
}

.deteccoes-track .card-deteccao {
    flex: 0 0 calc((100% - 60px) / 3);
    width: auto;
    min-width: 0;
}

.card-deteccao {
    min-height: 290px;
}

.carrossel-seta {
    width: 46px;
    height: 46px;
    top: 50%;
}

.carrossel-prev {
    left: 0;
}

.carrossel-next {
    right: 0;
}

.carrossel-indicadores {
    margin-top: 28px;
}

/* =========================================================
   TABLET
   ========================================================= */

@media (max-width: 1000px) {

    .header {
        padding: 20px 30px;
    }

    header {
        padding: 20px 30px;
    }

    .logo {
        font-size: 2rem;
    }

    .hero {
        padding: 150px 40px 70px;
    }

    .hero-text h1 {
        font-size: 4.8rem;
    }

    .hero-image {
        width: min(400px, 85vw);
        height: auto;
        min-height: 0;
        background: transparent;
    }

    .hero-img {
        width: 100%;
    }

    .deteccoes {
        padding: 70px 30px;
    }

    .deteccoes-carrossel {
        padding: 0 50px;
    }

    .deteccoes-track .card-deteccao {
        flex-basis: calc((100% - 30px) / 2);
    }

    .missao-imagens {
        transform: scale(.85);
        margin: -30px 0;
    }

    .missao-texto h2 {
        font-size: 3rem;
    }
}

/* =========================================================
   CELULAR
   ========================================================= */

@media (max-width: 768px) {

    header {
        position: relative;
        padding: 18px 20px;
        gap: 16px;
    }

    .logo {
        font-size: 1.8rem;
    }

    nav {
        gap: 14px 18px;
        width: 100%;
    }

    nav a {
        font-size: .9rem;
    }

    .btn-cadastro {
        height: 46px;
        padding: 0 22px;
        font-size: .9rem;
    }

    .hero {
        min-height: auto;
        padding: 70px 20px 60px;
        gap: 45px;
    }

    .hero-text {
        width: 100%;
        max-width: 600px;
    }

    .subtitle {
        font-size: 1.05rem;
    }

    .hero-text h1 {
        font-size: clamp(2.7rem, 12vw, 3.7rem);
        line-height: 1.05;
    }

    .hero-text p {
        font-size: 1rem;
        line-height: 1.6;
        margin: 0 auto;
    }

    .hero-buttons {
        margin-top: 28px;
    }

    .btn-primary,
    .btn-secondary {
        width: min(190px, 100%);
        height: 50px;
        font-size: .95rem;
    }

    .hero-image {
        width: min(340px, 90vw);
        height: auto;
        background: transparent;
        box-shadow: none;
    }

    .hero-img {
        width: 100%;
        border-radius: 16px;
    }

    .como-funciona {
        padding: 60px 20px;
    }

    .como-funciona h2,
    .deteccoes h2 {
        font-size: 2.2rem;
    }

    .como-subtitulo,
    .deteccoes-subtitulo {
        font-size: 1rem;
        line-height: 1.6;
        margin-bottom: 40px;
    }

    .cards-funcao {
        gap: 20px;
    }

    .card-funcao {
        width: 100%;
        max-width: 360px;
    }

    /* CARROSSEL: 1 card por vez */
    .deteccoes {
        padding: 60px 15px;
    }

    .deteccoes-carrossel {
        padding: 0 42px;
    }

    .deteccoes-track {
        gap: 0;
    }

    .deteccoes-track .card-deteccao {
        flex: 0 0 100%;
        width: 100%;
    }

    .card-deteccao {
        min-height: 270px;
        padding: 28px;
    }

    .card-deteccao h3 {
        font-size: 1.25rem;
    }

    .card-deteccao p {
        font-size: .95rem;
        line-height: 1.7;
    }

    .carrossel-seta {
        width: 38px;
        height: 38px;
        font-size: 1.1rem;
    }

    .carrossel-prev {
        left: 0;
    }

    .carrossel-next {
        right: 0;
    }

    .carrossel-indicadores {
        gap: 8px;
        margin-top: 22px;
    }

    .sobre {
        padding: 60px 20px;
    }

    .sobre h2 {
        font-size: 2.2rem;
    }

    .sobre-texto p {
        font-size: 1rem;
        line-height: 1.75;
    }

    .equipe {
        padding: 60px 20px 70px;
    }

    .equipe h2 {
        font-size: 2.2rem;
    }

    .equipe-subtitulo {
        font-size: 1rem;
        line-height: 1.6;
        margin-bottom: 40px;
    }

    .membro {
        width: 100%;
        max-width: 330px;
    }

    .missao {
        padding: 60px 20px;
        gap: 20px;
        overflow: hidden;
    }

    .missao-imagens {
        width: 300px;
        height: 300px;
        transform: scale(.8);
        margin: -35px 0;
    }

    .estrela {
        width: 420px;
        height: 420px;
        top: -60px;
        left: -60px;
    }

    .imagem-grande {
        width: 145px;
        height: 165px;
        top: 0;
        left: 125px;
    }

    .imagem-pequena {
        width: 125px;
        height: 125px;
        bottom: 50px;
        left: 25px;
    }

    .missao-texto {
        width: 100%;
        max-width: 450px;
    }

    .missao-texto span {
        font-size: 1.05rem;
    }

    .missao-texto h2 {
        font-size: 2.25rem;
        line-height: 1.2;
        margin: 15px 0 30px;
    }

    footer {
        padding: 30px 20px;
    }

    .footer-links {
        gap: 20px;
        font-size: .9rem;
    }

    .footer-links a {
        font-size: .95rem;
    }
}

/* Celulares bem estreitos */
@media (max-width: 430px) {

    .hero {
        padding-left: 15px;
        padding-right: 15px;
    }

    nav {
        gap: 10px 14px;
    }

    nav a {
        font-size: .82rem;
    }

    .btn-cadastro {
        padding: 0 18px;
    }

    .hero-text h1 {
        font-size: 2.6rem;
    }

    .hero-buttons {
        flex-direction: column;
        align-items: center;
    }

    .btn-primary,
    .btn-secondary {
        width: 100%;
        max-width: 280px;
    }

    .deteccoes-carrossel {
        padding: 0 36px;
    }

    .card-deteccao {
        padding: 24px;
    }

    .icone-deteccao {
        width: 60px;
        height: 60px;
        font-size: 1.6rem;
        margin-bottom: 20px;
    }

    .sobre-texto p {
        font-size: .95rem;
    }

    .missao-imagens {
        transform: scale(.7);
        margin: -55px 0;
    }

    .missao-texto h2 {
        font-size: 2rem;
    }
}

/* Reduz animações para quem prefere menos movimento */
@media (prefers-reduced-motion: reduce) {

    *,
    *::before,
    *::after {
        animation-duration: .01ms !important;
        animation-iteration-count: 1 !important;
        scroll-behavior: auto !important;
        transition-duration: .01ms !important;
    }
}


button {
    border: none;
    outline: none;
    background: transparent;
}

    a {
  text-decoration: none;
  color: inherit;
}

/* Modal de aviso de login */
.modal-overlay{
    display:none;
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.6);
    z-index:1000;

    align-items:center;
    justify-content:center;
}

.modal-overlay.ativo{
    display:flex;
}

.modal-caixa{
    background:#fff;
    padding:34px 40px;
    border-radius:16px;
    text-align:center;
    max-width:360px;
    width:90%;

    box-shadow:0 20px 50px rgba(0,0,0,.3);
}

.modal-caixa h3{
    color:#222;
    font-size:1.3rem;
    margin-bottom:10px;
}

.modal-caixa p{
    color:#555;
    font-size:.95rem;
    line-height:1.5;
}
</style>
</head>
<body>



<header>

     <div class="logo">
        Crypher.IA
    </div>

    <nav>
        <button class="buttao_L" type="button" onclick="Verlogar('contato/conversa.php');">

        <a href="#">Contato</a>

        </button>

        <button class="buttao_L"  type="button" onclick="Verlogar('painel_api.php');">

        <a href="#">IA</a>

        </button>

        <button type="button">


        <a href="#sobre">Sobre nós</a>
        
        </button>

    </nav>
    <?php if ($usuarioLogado): ?>

        <div class="conta-menu">
            <button type="button" class="conta-botao" onclick="toggleContaMenu();" title="<?= htmlspecialchars($nomeUsuario) ?>" aria-label="Minha conta"><?= htmlspecialchars($inicialUsuario) ?></button>

            <div class="conta-dropdown" id="contaDropdown">
                <a href="perfil.php">Configurações</a>
                <a href="logout.php">Sair</a>
            </div>
        </div>
    <?php else: ?>
        <a href="cadastrar.php" class="btn-cadastro">Cadastre-se</a>
    <?php endif; ?>

</header>

<div class="modal-overlay" id="modalLogin">
    <div class="modal-caixa">
        <h3>Você precisa estar logado</h3>
        <p>Essa área é exclusiva para usuários cadastrados. Te levando para o login...</p>
    </div>
</div>

<main class="hero">

    <canvas id="rainCanvas"></canvas>

    <div class="hero-text">

        <span class="subtitle">
            Proteja seu futuro
        </span>

        <h1>
            Segurança<br>
            Crypher IA
        </h1>

        <p>
            Verificação de IA para proteger seus ativos digitais instantâneos.
        </p>

        <div class="hero-buttons">

            <button class="btn-primary" type="button" onclick="Verlogar('painel_api.php');">
                Começar Agora
            </button>

        </div>

    </div>

    <div class="hero-image">
        <img src="https://e-safer.com.br/wp-content/uploads/2023/06/seguranca-cibernetica-de-ia-protecao-contra-virus-scaled.jpg" 
             alt="cibersegurança"
             class="hero-img">
        </div>

</main>

<section class="como-funciona">

    <h2>Como funciona?</h2>

    <p class="como-subtitulo">
        Proteja seu site em apenas quatro etapas.
    </p>

    <div class="cards-funcao">

        <div class="card-funcao">

            <div class="numero-card">01</div>

            <h3>Informe seu site</h3>

            <p>
                Mande o seu código ou o projeto do seu site para 
                que IA possa começar a verificação.
            </p>

        </div>

        <div class="card-funcao">

            <div class="numero-card">02</div>

            <h3>Análise por IA</h3>

            <p>
                A IA examina seu site procurando vulnerabilidades,
                configurações inseguras e possíveis riscos.
            </p>

        </div>

        <div class="card-funcao">

            <div class="numero-card">03</div>

            <h3>Relatório completo</h3>

            <p>
                Um relatório é gerado mostrando cada problema encontrado
                e o nível de risco correspondente.
            </p>

        </div>

        <div class="card-funcao">

            <div class="numero-card">04</div>

            <h3>Corrija as falhas</h3>

            <p>
                Receba orientações para corrigir as vulnerabilidades e
                aumentar a segurança do seu sistema.
            </p>

        </div>

    </div>

</section>

    <section class="deteccoes" id="deteccoes">

    <h2>O que nossa IA detecta?</h2>

    <p class="deteccoes-subtitulo">
        Nossa IA identifica diferentes tipos de vulnerabilidades
        e riscos que podem comprometer a segurança do seu sistema.
    </p>

    <div class="deteccoes-carrossel">

        <button
            class="carrossel-seta carrossel-prev"
            id="prevDeteccao"
            type="button"
            aria-label="Detecção anterior">
            &#10094;
        </button>

        <div class="cards-deteccoes">

            <div class="deteccoes-track" id="deteccoesTrack">

                <article class="card-deteccao">
                    <div class="icone-deteccao">
                        <i class="fa-solid fa-database"></i>
                    </div>

                    <h3>SQL Injection</h3>

                    <p>
                        Identifica falhas que podem permitir a inserção
                        de comandos maliciosos em bancos de dados.
                    </p>
                </article>

                <article class="card-deteccao">
                    <div class="icone-deteccao">
                        <i class="fa-solid fa-code"></i>
                    </div>

                    <h3>Cross-Site Scripting</h3>

                    <p>
                        Detecta vulnerabilidades que podem permitir
                        a execução de scripts maliciosos no navegador.
                    </p>
                </article>

                <article class="card-deteccao">
                    <div class="icone-deteccao">
                        <i class="fa-solid fa-lock"></i>
                    </div>

                    <h3>Falhas de autenticação</h3>

                    <p>
                        Analisa mecanismos de login e identifica
                        possíveis problemas no controle de acesso.
                    </p>
                </article>

                <article class="card-deteccao">
                    <div class="icone-deteccao">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>

                    <h3>Configurações inseguras</h3>

                    <p>
                        Encontra configurações que podem deixar
                        o sistema mais exposto a ataques.
                    </p>
                </article>

                <article class="card-deteccao">
                    <div class="icone-deteccao">
                        <i class="fa-solid fa-folder-open"></i>
                    </div>

                    <h3>Exposição de dados</h3>

                    <p>
                        Identifica informações que podem estar
                        sendo expostas de forma indevida.
                    </p>
                </article>

                <article class="card-deteccao">
                    <div class="icone-deteccao">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>

                    <h3>Boas práticas OWASP</h3>

                    <p>
                        Verifica pontos importantes de segurança
                        com base em boas práticas da OWASP.
                    </p>
                </article>

            </div>

        </div>

        <button
            class="carrossel-seta carrossel-next"
            id="nextDeteccao"
            type="button"
            aria-label="Próxima detecção">
            &#10095;
        </button>

        <div
            class="carrossel-indicadores"
            id="indicadoresDeteccao"
            aria-label="Navegação do carrossel">
        </div>


    </div>

</section>

<section class="sobre" id="sobre">

    <h2>Sobre nós</h2>

    <div class="sobre-top">

        <div class="sobre-texto">

            <p>
            O Crypher.IA é uma plataforma desenvolvida para tornar a
            cibersegurança mais acessível e eficiente. Utilizando
            inteligência artificial, nossa ferramenta analisa sites em
            busca de vulnerabilidades, falhas de configuração e possíveis
            ameaças que podem comprometer dados e sistemas.

            Além de identificar riscos, o sistema apresenta relatórios
            detalhados e recomendações práticas para correção dos
            problemas encontrados. Dessa forma, auxiliamos estudantes,
            desenvolvedores e empresas a fortalecerem a segurança de seus
            projetos digitais de maneira rápida, simples e confiável.
        </p>

        </div>

    </div>
</section>

<section class="equipe">

    <h2>Nossa equipe</h2>

    <p class="equipe-subtitulo">
        Conheça os responsáveis pelo desenvolvimento do Crypher.IA
    </p>

    <div class="cards-equipe">

        <!-- Heittor -->
        <div class="membro">

            <div class="foto-membro">
                <img src="..\assets/img/Heittor.png" alt="">
            </div>

            <h3>Heittor Moreira Rodrigues</h3>
            <p>Desenvolvedor Backend e Líder do Projeto</p>

        </div>

        <!-- Miriã -->
        <div class="membro">

            <div class="foto-membro">
                <img src="..\assets\img\Miria.jpeg" alt="">
            </div>

            <h3>Miriã Marques de Oliveira</h3>
            <p>Designer e Desenvolvedora Frontend</p>

        </div>

        <!-- Giovana -->
        <div class="membro">

            <div class="foto-membro">
                <img src="..\assets\img\Giovana.jpeg" alt="">
            </div>

            <h3>Giovana Akemi Hirayama Botelho</h3>
            <p>Responsável pela Documentação</p>

        </div>

    </div>
</section>

<section class="missao">

    <div class="missao-imagens">

        <div class="estrela"></div>

        <div class="imagem-grande">
            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRUZ8BpLyN4DCBn-fg2EGqoR7kEEFWfv1HCo3JMFq2uXXK4OR6Cp--1ToeS&s=10" 
                 alt="Cibersegurança">
        </div>

        <div class="imagem-pequena">
            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRyztum6FY8GZcahvQEQj-gD6wxov1mfPGRYFnbvgV6U8LWKqS32CpjwjHQ&s=10" 
                 alt="">
        </div>

    </div>

    <div class="missao-texto">

        <span>Nossa missão</span>

        <h2>
            Soluções pioneiras de cibersegurança orientadas por IA
        </h2>

    </div>

</section>

<footer>

    <div class="linha-footer"></div>

    <div class="footer-links">
        <a href="#">Termos de Uso</a>
        <a href="#">Política de Privacidade</a>
    </div>

</footer>

<script>
    // Vem do PHP: true se a sessão tiver um usuário logado, false se não
    const usuarioLogado = <?= $usuarioLogado ? 'true' : 'false' ?>;

    // Chamada pelos botões da nav que exigem login (Contato, IA)
    function Verlogar(destino) {
        if (usuarioLogado) {
            window.location.href = destino;
        } else {
            mostrarModalLogin();
        }
    }

    function mostrarModalLogin() {
        const modal = document.getElementById('modalLogin');
        modal.classList.add('ativo');

        setTimeout(() => {
            window.location.href = 'login.php';
        }, 2500);
    }

    // Abre/fecha o menu "Minha conta" (Configurações / Sair)
    function toggleContaMenu() {
        document.getElementById('contaDropdown').classList.toggle('ativo');
    }

    // Fecha o menu se a pessoa clicar em qualquer lugar fora dele
    document.addEventListener('click', (evento) => {
        const menu = document.querySelector('.conta-menu');
        const dropdown = document.getElementById('contaDropdown');

        if (menu && dropdown && !menu.contains(evento.target)) {
            dropdown.classList.remove('ativo');
        }
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", () => {
/* =========================
       CHUVINHA
   ========================= */

const canvas = document.getElementById("rainCanvas");

if (canvas) {

    const ctx = canvas.getContext("2d");

    function resizeCanvas() {
        canvas.width = canvas.clientWidth;
        canvas.height = canvas.clientHeight;
    }

    resizeCanvas();

    const shapes = [
        "◦", "▪", "□", "△",
        "✦", "◇", "●", "</>",
        "{ }", "01", "#", "AI"
    ];

    const drops = [];

    function createDrop() {

        return {
            x: Math.random() * canvas.width,
            y: Math.random() * canvas.height,
            speed: 0.5 + Math.random() * 1.5,
            size: 12 + Math.random() * 18,
            shape: shapes[Math.floor(Math.random() * shapes.length)]
        };

    }

    for (let i = 0; i < 70; i++) {
        drops.push(createDrop());
    }

    function draw() {

        ctx.clearRect(
            0,
            0,
            canvas.width,
            canvas.height
        );

        drops.forEach((drop, index) => {

            ctx.fillStyle = "#F3BE27";
            ctx.font = `${drop.size}px Poppins`;

            ctx.fillText(
                drop.shape,
                drop.x,
                drop.y
            );

            drop.y += drop.speed;

            if (drop.y > canvas.height + 50) {

                drops[index] = {
                    ...createDrop(),
                    y: -50
                };

            }

        });

        requestAnimationFrame(draw);
    }

    draw();

    window.addEventListener("resize", resizeCanvas);
}


    /* =========================
       CARROSSEL DE DETECÇÕES
    ========================= */

    const track = document.getElementById("deteccoesTrack");
    const prevButton = document.getElementById("prevDeteccao");
    const nextButton = document.getElementById("nextDeteccao");
    const indicadores = document.getElementById("indicadoresDeteccao");

    // Se o carrossel não existir na página, não executa essa parte
    if (!track || !prevButton || !nextButton || !indicadores) {
        return;
    }

    const cards = Array.from(
        track.querySelectorAll(".card-deteccao")
    );

    let paginaAtual = 0;
    let cardsPorPagina = 3;


    /* =========================
       QUANTIDADE DE CARDS
    ========================= */

    function quantidadePorPagina() {

        const largura = window.innerWidth;

        // Celular
        if (largura <= 768) {
            return 1;
        }

        // Tablet
        if (largura <= 1000) {
            return 2;
        }

        // Computador
        return 3;
    }


    /* =========================
       TOTAL DE PÁGINAS
    ========================= */

    function totalPaginas() {

        return Math.ceil(
            cards.length / cardsPorPagina
        );

    }


    /* =========================
       ATUALIZAR INDICADORES
    ========================= */

    function criarIndicadores() {

        indicadores.innerHTML = "";

        const total = totalPaginas();

        for (let i = 0; i < total; i++) {

            const indicador =
                document.createElement("button");

            indicador.type = "button";

            indicador.classList.add(
                "carrossel-indicador"
            );

            indicador.setAttribute(
                "aria-label",
                `Ir para a página ${i + 1}`
            );

            indicador.addEventListener(
                "click",
                () => {

                    paginaAtual = i;

                    atualizarCarrossel();

                }
            );

            indicadores.appendChild(indicador);
        }
    }


    /* =========================
       ATUALIZAR CARROSSEL
    ========================= */

    function atualizarCarrossel() {

        cardsPorPagina =
            quantidadePorPagina();

        const total = totalPaginas();

        // Impede que a página atual fique inválida
        if (paginaAtual >= total) {
            paginaAtual = total - 1;
        }

        if (paginaAtual < 0) {
            paginaAtual = 0;
        }


        /* =========================
           LARGURA DOS CARDS
        ========================= */

        const container =
            track.parentElement;

        const larguraContainer =
            container.clientWidth;

        const gap = 30;

        const larguraCard =
            (
                larguraContainer -
                gap * (cardsPorPagina - 1)
            ) / cardsPorPagina;


        cards.forEach(card => {

            card.style.flex =
                `0 0 ${larguraCard}px`;

        });


        /* =========================
           MOVIMENTO DO CARROSSEL
        ========================= */

        const deslocamento =
            paginaAtual *
            (
                larguraCard *
                cardsPorPagina +
                gap * cardsPorPagina
            );

        track.style.transform =
            `translateX(-${deslocamento}px)`;


        /* =========================
           SETAS
        ========================= */

        prevButton.disabled =
            paginaAtual === 0;

        nextButton.disabled =
            paginaAtual >= total - 1;


        /* =========================
           INDICADORES
        ========================= */

        const botoesIndicadores =
            indicadores.querySelectorAll(
                ".carrossel-indicador"
            );

        botoesIndicadores.forEach(
            (indicador, index) => {

                indicador.classList.toggle(
                    "ativo",
                    index === paginaAtual
                );

                indicador.setAttribute(
                    "aria-current",
                    index === paginaAtual
                        ? "true"
                        : "false"
                );

            }
        );
    }


    /* =========================
       BOTÃO ANTERIOR
    ========================= */

    prevButton.addEventListener(
        "click",
        () => {

            if (paginaAtual > 0) {

                paginaAtual--;

                atualizarCarrossel();

            }

        }
    );


    /* =========================
       BOTÃO PRÓXIMO
    ========================= */

    nextButton.addEventListener(
        "click",
        () => {

            if (
                paginaAtual <
                totalPaginas() - 1
            ) {

                paginaAtual++;

                atualizarCarrossel();

            }

        }
    );


    /* =========================
       SWIPE NO CELULAR
    ========================= */

    let toqueInicial = 0;
    let toqueFinal = 0;

    track.addEventListener(
        "touchstart",
        (evento) => {

            toqueInicial =
                evento.touches[0].clientX;

        },
        { passive: true }
    );


    track.addEventListener(
        "touchend",
        (evento) => {

            toqueFinal =
                evento.changedTouches[0].clientX;

            const distancia =
                toqueInicial - toqueFinal;

            // Arrastar para a esquerda
            if (distancia > 50) {

                if (
                    paginaAtual <
                    totalPaginas() - 1
                ) {

                    paginaAtual++;

                    atualizarCarrossel();

                }

            }

            // Arrastar para a direita
            else if (distancia < -50) {

                if (paginaAtual > 0) {

                    paginaAtual--;

                    atualizarCarrossel();

                }

            }

        },
        { passive: true }
    );


    /* =========================
       REDIMENSIONAMENTO
    ========================= */

    let quantidadeAnterior =
        quantidadePorPagina();

    window.addEventListener(
        "resize",
        () => {

            const novaQuantidade =
                quantidadePorPagina();

            // Se mudou de celular → tablet → PC
            if (
                novaQuantidade !==
                quantidadeAnterior
            ) {

                quantidadeAnterior =
                    novaQuantidade;

                paginaAtual = 0;

                criarIndicadores();

            }

            atualizarCarrossel();

        }
    );


    /* =========================
       INICIALIZAÇÃO
    ========================= */

    criarIndicadores();

    atualizarCarrossel();



});
</script>

</body>
</html>
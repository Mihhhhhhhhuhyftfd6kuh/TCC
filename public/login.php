<?php
session_start();

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../controllers/auth.php';


$erroLogin = null;

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $email =  $_POST['email'] ?? null;
    $senha = $_POST['senha']  ?? null;

    $erroLogin = login( $email,$senha);
}

// Vem do cadastrar.php depois de criar a conta com sucesso
$cadastroOk = isset($_GET['cadastrado']) && $_GET['cadastrado'] === '1';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
   <title>Cadastro - Crypher.IA</title>
    <link rel="icon" type="image/x-icon" href="../assets/img/mascote_s_fundo.png">


<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

html {
    width: 100%;
    min-height: 100%;
}

body {
    width: 100%;
    min-height: 100vh;
    min-height: 100svh;

    overflow-x: hidden;
    overflow-y: auto;

    background: #fff;
}


/* =========================================
   CHUVINHA
========================================= */

#rainCanvas {
    position: absolute;
    inset: 0;

    width: 100%;
    height: 100%;

    z-index: 1;
    pointer-events: none;
}


/* =========================================
   CONTAINER
========================================= */

.container {
    position: relative;
    z-index: 2;

    display: flex;

    width: 100%;
    min-height: 100vh;
    min-height: 100svh;
}


/* =========================================
   PAINEL AZUL
========================================= */

.left-panel {
    position: relative;

    width: 40%;
    min-height: 100vh;

    background: #4348D9;
    color: #fff;

    display: flex;
    flex-direction: column;

    padding: 30px;

    overflow: hidden;
}


/* conteúdo acima da chuvinha */

.logo,
.welcome,
.btn-login,
.btn-cadastrar {
    position: relative;
    z-index: 2;
}


/* =========================================
   LOGO
========================================= */

.logo {
    font-size: 2.8rem;
    font-weight: 700;

    line-height: 1;

    white-space: nowrap;
}

.logo-sub {
    font-size: 1.1rem;

    margin-left: 130px;
    margin-top: -10px;
}


/* =========================================
   ÁREA DE BOAS-VINDAS
========================================= */

.welcome {
    flex: 1;

    display: flex;
    flex-direction: column;

    justify-content: center;
    align-items: flex-start;
}

.welcome h2 {
    font-size: 2.2rem;
    line-height: 1.2;

    margin-bottom: 20px;
}

.welcome p {
    font-size: 1.1rem;

    margin-bottom: 30px;
}


/* =========================================
   BOTÕES
========================================= */

.btn-login,
.btn-cadastrar {
    width: 180px;
    height: 52px;

    border: none;
    border-radius: 999px;

    background: #F3BE27;
    color: #222;

    font-size: 1.1rem;
    font-weight: 700;

    cursor: pointer;

    transition:
        transform .3s ease,
        box-shadow .3s ease,
        background .3s ease;

    box-shadow:
        0 8px 18px rgba(243, 190, 39, .30);
}

.btn-login:hover,
.btn-cadastrar:hover {
    transform: translateY(-3px) scale(1.03);

    box-shadow:
        0 12px 24px rgba(243, 190, 39, .45);
}

.btn-cadastrar {
    margin-left: 90px;
}


/* =========================================
   PAINEL DO FORMULÁRIO
========================================= */

.right-panel {
    width: 60%;
    min-height: 100vh;

    background: #fff;

    display: flex;
    flex-direction: column;

    justify-content: center;
    align-items: center;

    padding: 40px;
}


/* =========================================
   TÍTULO
========================================= */

.right-panel h1 {
    width: 100%;
    max-width: 600px;

    font-size: 3rem;
    line-height: 1.1;

    margin-bottom: 8px;

    color: #111;

    text-align: center;
}

.right-panel p {
    color: #555;

    margin-bottom: 25px;

    text-align: center;
}


/* =========================================
   CARTÃO DO FORMULÁRIO
========================================= */

.form-card {
    position: relative;
    z-index: 2;

    width: min(420px, 100%);

    padding: 30px;

    border-radius: 16px;

    background: #4348D9;

    box-shadow:
        0 10px 25px rgba(0, 0, 0, .15);
}


/* =========================================
   FORM
========================================= */

.form-card form {
    display: flex;
    flex-direction: column;

    width: 100%;
}


/* =========================================
   LABEL
========================================= */

.form-card label {
    color: #000;

    font-weight: 600;

    margin-bottom: 8px;
}


/* =========================================
   INPUT
========================================= */

.form-card input {
    width: 100%;
    height: 45px;

    border: none;
    outline: none;

    border-radius: 10px;

    padding: 0 15px;

    margin-bottom: 18px;

    font-size: 1rem;

    background: #fff;

    transition:
        box-shadow .2s ease,
        transform .2s ease;
}

.form-card input:focus {
    box-shadow:
        0 0 0 3px rgba(243, 190, 39, .55);
}


/* =========================================
   BOTÃO DO FORMULÁRIO
========================================= */

.form-card .btn-cadastrar {
    align-self: center;

    margin-left: 0;
    margin-top: 5px;
}


/* =========================================
   AVISOS (erro de login / cadastro concluído)
========================================= */

.aviso {
    display: flex;
    align-items: flex-start;
    gap: 10px;

    width: 100%;

    padding: 12px 16px;
    margin-bottom: 18px;

    border-radius: 10px;

    font-size: .9rem;
    line-height: 1.4;
}

.aviso-erro {
    background: rgba(220, 53, 69, .12);
    color: #ffd7da;
    border: 1px solid rgba(220, 53, 69, .55);
}

.aviso-sucesso {
    background: rgba(40, 167, 69, .15);
    color: #d7f5df;
    border: 1px solid rgba(40, 167, 69, .55);
}


/* =========================================
   LINKS
========================================= */

a {
    text-decoration: none;
    color: inherit;
}


/* =========================================
   TABLET
========================================= */

@media (max-width: 900px) {

    .container {
        flex-direction: column;

        min-height: 100svh;
    }

    /* AZUL */

    .left-panel {
        width: 100%;
        min-height: 42svh;
        height: auto;

        padding: 30px 40px;
    }

    .logo {
        font-size: 2.5rem;
    }

    .logo-sub {
        margin-left: 0;
    }

    .welcome {
        justify-content: center;
    }

    .welcome h2 {
        font-size: 2.4rem;
    }


    /* BRANCO */

    .right-panel {
        width: 100%;

        min-height: 58svh;
        height: auto;

        padding: 45px 30px;
    }

    .right-panel h1 {
        font-size: 2.7rem;
    }

    .form-card {
        width: min(420px, 100%);
    }

    .btn-cadastrar {
        margin-left: 0;
    }
}


/* =========================================
   CELULAR
========================================= */

@media (max-width: 600px) {

    body {
        overflow-x: hidden;
    }

    .container {
        width: 100%;

        display: flex;
        flex-direction: column;
    }


    /* ================================
       PAINEL AZUL
    ================================= */

    .left-panel {
        width: 100%;

        min-height: 43svh;

        padding:
            28px
            24px
            35px;

        justify-content: flex-start;
    }


    /* LOGO */

    .logo {
        font-size: 2.8rem;

        text-align: left;

        line-height: 1;
    }


    /* BOAS-VINDAS */

    .welcome {
        width: 100%;

        align-items: flex-start;

        justify-content: center;
    }

    .welcome h2 {
        width: 100%;

        font-size: clamp(2rem, 8vw, 2.6rem);

        line-height: 1.15;

        margin-bottom: 18px;
    }

    .welcome p {
        font-size: 1.05rem;

        margin-bottom: 25px;
    }


    /* BOTÃO AZUL */

    .left-panel .btn-login,
    .left-panel .btn-cadastrar {
        width: 180px;

        margin-left: 0;

        height: 52px;
    }


    /* ================================
       PAINEL BRANCO
    ================================= */

    .right-panel {
        width: 100%;

        min-height: 57svh;

        padding:
            38px
            20px
            45px;

        justify-content: flex-start;
    }


    /* TÍTULO */

   .right-panel h1 {
    width: 100%;
    max-width: 600px;

    font-size: 2.5rem;
    line-height: 1.2;

    font-weight: 600;

    margin-bottom: 8px;

    color: #222;

    text-align: center;
}

    .right-panel p {
        width: 100%;

        font-size: .95rem;

        margin-bottom: 20px;
    }


    /* FORMULÁRIO */

    .form-card {
        width: 100%;

        max-width: 420px;

        padding: 25px 20px;

        border-radius: 16px;
    }


    .form-card label {
        font-size: .95rem;

        margin-bottom: 7px;
    }


    .form-card input {
        width: 100%;

        height: 48px;

        margin-bottom: 17px;

        font-size: .95rem;
    }


    /* BOTÃO FORMULÁRIO */

    .form-card .btn-cadastrar {
        width: 180px;

        margin-left: auto;
        margin-right: auto;

        height: 52px;
    }
}


/* =========================================
   CELULAR PEQUENO
========================================= */

@media (max-width: 400px) {

    .left-panel {
        min-height: 45svh;

        padding:
            25px
            20px
            30px;
    }

    .logo {
        font-size: 2.45rem;
    }

    .welcome h2 {
        font-size: 2rem;
    }

    .welcome p {
        font-size: .95rem;
    }

    .left-panel .btn-login,
    .left-panel .btn-cadastrar {
        width: 165px;
        height: 50px;

        font-size: 1rem;
    }


    .right-panel {
        min-height: 60svh;

        padding:
            32px
            15px
            40px;
    }

    .right-panel h1 {
        font-size: 1.95rem;
    }

    .right-panel p {
        font-size: .9rem;
    }

    .form-card {
        padding: 22px 16px;
    }

    .form-card input {
        height: 46px;
    }

    .form-card .btn-cadastrar {
        width: 165px;
    }
}


/* =========================================
   TELAS MUITO BAIXAS
========================================= */

@media (max-height: 650px) and (max-width: 600px) {

    .left-panel {
        min-height: 380px;
    }

    .right-panel {
        min-height: 430px;
    }

    .welcome h2 {
        font-size: 1.9rem;
        margin-bottom: 12px;
    }

    .welcome p {
        margin-bottom: 18px;
    }

    .right-panel {
        padding-top: 30px;
        padding-bottom: 35px;
    }
}

/* botão de troca de tela no topo (só aparece no celular) */
.logo-link {
    color: inherit;
    text-decoration: none;
}

.topo-acao {
    display: none;
}

/* =========================================================
   LOGIN / CADASTRO — MOBILE
   Painel azul vira uma barra no topo (logo + botão) e
   o formulário ocupa o resto da tela.
   Fica no fim do <style> para sobrescrever as regras acima.
========================================================= */

@media (max-width: 900px) {

    /* botão amarelo no canto direito da barra do topo */
    .topo-acao {
        display: inline-flex;
        align-items: center;
        justify-content: center;

        height: 42px;
        padding: 0 20px;

        border-radius: 999px;

        background: #F3BE27;
        color: #222;

        text-decoration: none;

        font-size: .9rem;
        font-weight: 700;

        box-shadow: 0 6px 14px rgba(243, 190, 39, .30);
    }

    .container {
        flex-direction: column;
        min-height: 100svh;
    }

    /* barra azul do topo */

    .left-panel {
        width: 100%;
        height: 68px;
        min-height: 68px;

        padding: 0 20px;

        flex-direction: row;
        align-items: center;
        justify-content: space-between;
    }

    .logo {
        font-size: 1.7rem;
    }

    /* o botão grande de trocar de tela agora está no menu */
    .welcome {
        display: none;
    }

    /* formulário */

    .right-panel {
        width: 100%;
        min-height: calc(100svh - 68px);

        padding: 32px 20px 40px;

        justify-content: center;
    }

    .right-panel h1 {
        font-size: clamp(1.8rem, 8vw, 2.4rem);
        line-height: 1.15;

        overflow-wrap: break-word;
    }

    .right-panel p {
        font-size: .95rem;
        margin-bottom: 20px;
    }

    .form-card {
        width: 100%;
        max-width: 420px;

        padding: 24px 20px;
    }

    .form-card label {
        font-size: .95rem;
        color: #fff;
    }

    /* 16px evita o zoom automático do iPhone ao focar no campo */
    .form-card input {
        height: 48px;
        font-size: 16px;
    }

    .form-card .btn-cadastrar {
        width: 100%;
        max-width: 260px;
        height: 52px;
    }
}

@media (max-width: 400px) {

    .left-panel {
        padding: 0 16px;
    }

    .logo {
        font-size: 1.5rem;
    }

    .right-panel {
        padding: 26px 14px 36px;
    }

    .form-card {
        padding: 22px 16px;
    }
}
</style>
</head>
<body>
          <div class="container">

 

    <!-- lado branco -->

    <section class="left-panel">

         <canvas id="rainCanvas"></canvas>

        <div>
            <a href="home.php" class="logo-link"><div class="logo">Crypher.IA</div></a>
        </div>

        <!-- troca de tela (só aparece no celular) -->
        <a href="cadastrar.php" class="topo-acao">Cadastrar</a>

        <div class="welcome">

            <h2>
                Seja bem-vindo ao<br>
                Crypher IA
            </h2>

            <p>
                Acesse sua conta agora!
            </p>
                <a href="cadastrar.php" >

            <button class="btn-login">
                
                Cadastrar
              
            </button>
                </a>

        </div>

    </section>

    <!-- lado roxo-azulado -->

    <section class="right-panel">


        <h1>Faça login</h1>

        <p>Entre na sua conta</p>
           


        <div class="form-card">

            <?php if ($cadastroOk): ?>
                <div class="aviso aviso-sucesso">Você foi cadastrado! Faça login para continuar.</div>
            <?php endif; ?>

            <?php if ($erroLogin): ?>
                <div class="aviso aviso-erro"><?= htmlspecialchars($erroLogin) ?></div>
            <?php endif; ?>

            <form method="post">

              

                <label>E-mail:</label>
                <input type="email" name="email">

                <label>Senha:</label>
                <input type="password" name="senha">

                <button type="submit" class="btn-cadastrar" >
                    Login
                </button>

            </form>

        </div>

    </section>

</div>

<script>

const canvas = document.getElementById("rainCanvas");
const ctx = canvas.getContext("2d");

function resizeCanvas(){

    canvas.width = canvas.offsetWidth;
    canvas.height = canvas.offsetHeight;

}

resizeCanvas();

const shapes = [
    "◦",
    "▪",
    "□",
    "△",
    "✦",
    "◇",
    "●",
    "</>",
    "{ }",
    "01",
    "#",
    "AI"
];

const drops = [];

function createDrop(){

    return{
        x: Math.random() * canvas.width,
        y: Math.random() * canvas.height,
        speed: 0.3 + Math.random(),
        size:10 + Math.random()*15,
        shape:shapes[Math.floor(Math.random()*shapes.length)]
    };

}

for(let i = 0; i < 45; i++){
    drops.push(createDrop());
}

function draw(){

    ctx.clearRect(
        0,
        0,
        canvas.width,
        canvas.height
    );

        drops.forEach((drop,index)=>{

        ctx.fillStyle = "#F3BE27";
        ctx.font = `${drop.size}px Poppins`;

        ctx.fillText(
            drop.shape,
            drop.x,
            drop.y
        );

        drop.y += drop.speed;

        if(drop.y > canvas.height + 50){

            drops[index] = {
                ...createDrop(),
                y: -50
            };

        }

    });

    requestAnimationFrame(draw);
}

draw();

window.addEventListener(
    "resize",
    resizeCanvas
);

</script>
</body>
</html>
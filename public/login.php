<?php
session_start();

// Página de login nunca pode ser guardada em cache (nem pelo navegador, nem por proxy do Render)
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../controllers/auth.php';

// Abrir a tela de login começa sempre uma sessão limpa. Sem isso, uma sessão
// antiga de outra pessoa (ex.: a amiga que testou no mesmo navegador/aparelho
// e não clicou em "Sair") continuava ativa e o site aparecia logado na conta dela.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !empty($_SESSION['id'])) {
    $_SESSION = [];
    session_destroy();
    session_start();
}

$erroLogin = null;

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $email =  $_POST['email'] ?? null;
    $senha = $_POST['senha']  ?? null;

    $erroLogin = login( $email,$senha);
}

// Vem do cadastrar.php depois de criar a conta com sucesso.
// Se o login for tentado e der erro, essa mensagem some de vez
// (o parâmetro ?cadastrado=1 continua na URL porque o form reenvia
// pra ela mesma, então sem essa checagem ela voltaria a aparecer
// em toda tentativa errada).
$cadastroOk = isset($_GET['cadastrado']) && $_GET['cadastrado'] === '1' && !$erroLogin;

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Crypher.IA</title>
    <link rel="icon" type="image/x-icon" href="../assets/img/mascote_s_fundo.png">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap');

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

:root {
    --azul: #4348D9;
    --azul-escuro: #3a3fc4;
    --amarelo: #F3BE27;
    --texto: #1f2033;
    --texto-suave: #6b6d85;
    --borda: #E2E3F0;
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

    background: #fff;
    color: var(--texto);
}

a {
    text-decoration: none;
    color: inherit;
}


/* =========================================
   CONTAINER
========================================= */

.container {
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

    width: 45%;
    min-height: 100vh;

    display: flex;
    flex-direction: column;

    padding: 40px 50px;

    background: linear-gradient(160deg, var(--azul) 0%, var(--azul-escuro) 100%);
    color: #fff;

    overflow: hidden;
}

/* brilho amarelo decorativo */

.left-panel::before {
    content: "";

    position: absolute;

    width: 380px;
    height: 380px;

    right: -140px;
    bottom: -140px;

    border-radius: 50%;

    background: radial-gradient(circle, rgba(243, 190, 39, .38) 0%, rgba(243, 190, 39, 0) 70%);

    pointer-events: none;
}

/* círculo de contorno decorativo */

.left-panel::after {
    content: "";

    position: absolute;

    width: 260px;
    height: 260px;

    left: -110px;
    top: 38%;

    border-radius: 50%;

    border: 2px solid rgba(255, 255, 255, .12);

    pointer-events: none;
}

#rainCanvas {
    position: absolute;
    inset: 0;

    width: 100%;
    height: 100%;

    z-index: 1;
    pointer-events: none;
}

.logo,
.welcome,
.header-direita {
    position: relative;
    z-index: 2;
}

.logo {
    font-size: 2.2rem;
    font-weight: 700;
    line-height: 1;
}

.header-direita {
    display: none;
}


/* =========================================
   BOAS-VINDAS
========================================= */

.welcome {
    flex: 1;

    display: flex;
    flex-direction: column;

    justify-content: center;
    align-items: flex-start;

    max-width: 440px;
}

.selo {
    display: inline-flex;
    align-items: center;
    gap: 8px;

    padding: 7px 16px;
    margin-bottom: 22px;

    border-radius: 999px;

    background: rgba(255, 255, 255, .14);
    border: 1px solid rgba(255, 255, 255, .22);

    font-size: .8rem;
    font-weight: 500;
    letter-spacing: .3px;
}

.selo i {
    color: var(--amarelo);
}

.welcome h2 {
    font-size: 2.7rem;
    font-weight: 700;
    line-height: 1.15;

    margin-bottom: 16px;
}

.welcome h2 span {
    color: var(--amarelo);
}

.welcome > p {
    font-size: 1.02rem;
    font-weight: 300;
    line-height: 1.6;

    opacity: .9;

    margin-bottom: 30px;
}

.destaques {
    list-style: none;

    display: flex;
    flex-direction: column;
    gap: 14px;

    margin-bottom: 36px;
}

.destaques li {
    display: flex;
    align-items: center;
    gap: 14px;

    font-size: .95rem;
    font-weight: 400;
}

.destaques li i {
    width: 38px;
    height: 38px;

    flex-shrink: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 12px;

    background: rgba(255, 255, 255, .14);
    color: var(--amarelo);

    font-size: .95rem;
}

.btn-login {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;

    height: 52px;
    padding: 0 34px;

    border-radius: 999px;

    background: var(--amarelo);
    color: #222;

    font-size: 1.02rem;
    font-weight: 700;

    box-shadow: 0 8px 18px rgba(243, 190, 39, .30);

    transition:
        transform .3s ease,
        box-shadow .3s ease;
}

.btn-login:hover {
    transform: translateY(-3px) scale(1.03);

    box-shadow: 0 12px 24px rgba(243, 190, 39, .45);
}


/* =========================================
   PAINEL DO FORMULÁRIO
========================================= */

.right-panel {
    width: 55%;
    min-height: 100vh;

    display: flex;
    align-items: center;
    justify-content: center;

    padding: 40px;

    background:
        radial-gradient(circle at 100% 0%, rgba(67, 72, 217, .07) 0%, rgba(67, 72, 217, 0) 45%),
        #fff;
}

.form-area {
    width: 100%;
    max-width: 420px;
}

.form-topo {
    margin-bottom: 30px;
}

.form-topo h1 {
    font-size: 2.3rem;
    font-weight: 700;
    line-height: 1.15;

    color: var(--texto);

    margin-bottom: 8px;
}

.form-topo h1::after {
    content: "";

    display: block;

    width: 54px;
    height: 5px;

    margin-top: 12px;

    border-radius: 999px;

    background: var(--amarelo);
}

.form-topo p {
    margin-top: 14px;

    font-size: .98rem;
    color: var(--texto-suave);
}


/* =========================================
   AVISOS (erro de login / cadastro concluído)
========================================= */

.aviso {
    display: flex;
    align-items: flex-start;
    gap: 10px;

    padding: 13px 16px;
    margin-bottom: 22px;

    border-radius: 12px;

    font-size: .9rem;
    line-height: 1.4;

    opacity: 1;
    max-height: 200px;
    overflow: hidden;

    transition: opacity .5s ease, margin .5s ease, padding .5s ease, max-height .5s ease;
}

.aviso i {
    margin-top: 2px;
}

.aviso-erro {
    background: #fdecee;
    color: #b02a37;
    border: 1px solid #f5c2c7;
}

.aviso-sucesso {
    background: #e8f6ed;
    color: #1d7a42;
    border: 1px solid #b7e2c5;
}


/* =========================================
   CAMPOS
========================================= */

.campo {
    margin-bottom: 20px;
}

.campo label {
    display: block;

    margin-bottom: 8px;

    font-size: .85rem;
    font-weight: 600;

    color: var(--texto);
}

.campo-input {
    position: relative;
}

.campo-input > i.icone {
    position: absolute;

    top: 50%;
    left: 17px;

    transform: translateY(-50%);

    color: #9a9cb5;
    font-size: .95rem;

    pointer-events: none;

    transition: color .2s ease;
}

.campo-input input {
    width: 100%;
    height: 52px;

    padding: 0 16px 0 46px;

    border: 1.5px solid var(--borda);
    border-radius: 14px;

    outline: none;

    background: #F8F8FC;
    color: var(--texto);

    /* 16px evita o zoom automático do iPhone ao focar no campo */
    font-size: 16px;

    transition:
        border-color .2s ease,
        box-shadow .2s ease,
        background .2s ease;
}

.campo-input input::placeholder {
    color: #a9abc0;
}

.campo-input input:focus {
    background: #fff;
    border-color: var(--azul);
    box-shadow: 0 0 0 4px rgba(67, 72, 217, .12);
}

.campo-input:focus-within > i.icone {
    color: var(--azul);
}

.campo-senha input {
    padding-right: 52px;
}

.ver-senha {
    position: absolute;

    top: 50%;
    right: 7px;

    transform: translateY(-50%);

    width: 38px;
    height: 38px;

    display: flex;
    align-items: center;
    justify-content: center;

    border: none;
    border-radius: 11px;

    background: transparent;
    color: #9a9cb5;

    cursor: pointer;

    transition: color .2s ease, background .2s ease;
}

.ver-senha:hover {
    color: var(--azul);
    background: rgba(67, 72, 217, .08);
}


/* =========================================
   BOTÃO
========================================= */

.btn-entrar {
    width: 100%;
    height: 54px;

    margin-top: 8px;

    border: none;
    border-radius: 999px;

    background: var(--amarelo);
    color: #222;

    font-size: 1.08rem;
    font-weight: 700;

    cursor: pointer;

    box-shadow: 0 8px 18px rgba(243, 190, 39, .30);

    transition:
        transform .3s ease,
        box-shadow .3s ease;
}

.btn-entrar:hover {
    transform: translateY(-3px);

    box-shadow: 0 12px 24px rgba(243, 190, 39, .45);
}

.btn-entrar:active {
    transform: translateY(0);
}

.form-rodape {
    margin-top: 24px;

    text-align: center;

    font-size: .9rem;
    color: var(--texto-suave);
}

.form-rodape a {
    color: var(--azul);
    font-weight: 600;
}

.form-rodape a:hover {
    text-decoration: underline;
}


/* =========================================
   MENU HAMBÚRGUER (só no celular)
========================================= */

.menu-hamb {
    position: relative;
    flex-shrink: 0;
}

.hamb-botao {
    width: 40px;
    height: 40px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: rgba(255, 255, 255, .18);
    color: #fff;

    border: none;
    border-radius: 12px;

    font-size: 1.05rem;
    line-height: 1;

    cursor: pointer;

    transition: background .2s ease;
}

.hamb-botao[aria-expanded="true"] {
    background: rgba(255, 255, 255, .32);
}

.hamb-dropdown {
    display: none;

    position: absolute;

    top: calc(100% + 8px);
    right: 0;

    min-width: 180px;

    background: #fff;

    border-radius: 12px;

    overflow: hidden;

    box-shadow: 0 15px 35px rgba(0, 0, 0, .22);

    z-index: 200;
}

.hamb-dropdown.ativo {
    display: block;
}

.hamb-dropdown a {
    display: flex;
    align-items: center;
    gap: 10px;

    padding: 13px 20px;

    color: #222;

    font-size: .9rem;
    font-weight: 500;

    transition: background .2s ease;
}

.hamb-dropdown a i {
    width: 16px;

    text-align: center;

    color: var(--azul);
}

.hamb-dropdown a:hover {
    background: #f2f2f2;
}

.topo-acao {
    display: inline-flex;
    align-items: center;
    justify-content: center;

    height: 40px;
    padding: 0 20px;

    border-radius: 999px;

    background: var(--amarelo);
    color: #222;

    font-size: .9rem;
    font-weight: 700;

    box-shadow: 0 6px 14px rgba(243, 190, 39, .30);
}


/* =========================================
   TABLET / CELULAR (barra azul no topo)
========================================= */

@media (max-width: 900px) {

    .container {
        flex-direction: column;
    }

    .left-panel {
        width: 100%;
        height: 68px;
        min-height: 68px;

        padding: 0 20px;

        flex-direction: row;
        align-items: center;
        justify-content: space-between;

        /* o menu aberto não pode ser cortado */
        overflow: visible;
        z-index: 20;

        background: var(--azul);
    }

    .left-panel::before,
    .left-panel::after {
        display: none;
    }

    .logo {
        font-size: 1.7rem;
    }

    .welcome {
        display: none;
    }

    .header-direita {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .right-panel {
        width: 100%;
        min-height: calc(100svh - 68px);

        padding: 34px 22px 44px;

        align-items: flex-start;
    }

    .form-area {
        margin: 0 auto;
    }

    .form-topo h1 {
        font-size: clamp(1.9rem, 8vw, 2.4rem);
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
        padding: 28px 16px 38px;
    }
}
</style>
</head>
<body>

<div class="container">

    <!-- lado azul -->

    <section class="left-panel">

        <canvas id="rainCanvas"></canvas>

        <a href="home.php" class="logo">Crypher.IA</a>

        <!-- troca de tela + menu hambúrguer (só aparecem no celular) -->
        <div class="header-direita">

            <a href="cadastrar.php" class="topo-acao">Cadastrar</a>

            <div class="menu-hamb">
                <button type="button" class="hamb-botao" id="hamb-botao" title="Menu" aria-label="Menu" aria-haspopup="true" aria-expanded="false">
                    <i class="fa-solid fa-bars"></i>
                </button>

                <div class="hamb-dropdown" id="hamb-dropdown">
                    <a href="home.php"><i class="fa-solid fa-house"></i> Home</a>
                    <a href="cadastrar.php"><i class="fa-solid fa-user-plus"></i> Cadastrar</a>
                </div>
            </div>

        </div>

        <div class="welcome">

            <span class="selo">
                <i class="fa-solid fa-wand-magic-sparkles"></i> Inteligência Artificial
            </span>

            <h2>
                Que bom te ver<br>
                no <span>Crypher IA</span>
            </h2>

            <p>
                Acesse sua conta agora e continue de onde parou.
            </p>

            <ul class="destaques">
                <li><i class="fa-solid fa-bolt"></i> Acesso rápido à sua conta</li>
                <li><i class="fa-solid fa-shield-halved"></i> Seus dados protegidos</li>
                <li><i class="fa-solid fa-comments"></i> Suporte direto pela conversa</li>
            </ul>

            <a href="cadastrar.php" class="btn-login">
                Criar conta <i class="fa-solid fa-arrow-right"></i>
            </a>

        </div>

    </section>

    <!-- lado branco -->

    <section class="right-panel">

        <div class="form-area">

            <div class="form-topo">
                <h1>Faça login</h1>
                <p>Entre na sua conta para continuar.</p>
            </div>

            <?php if ($cadastroOk): ?>
                <div class="aviso aviso-sucesso">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>Você foi cadastrado! Faça login para continuar.</span>
                </div>
            <?php endif; ?>

            <?php if ($erroLogin): ?>
                <div class="aviso aviso-erro">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?= htmlspecialchars($erroLogin) ?></span>
                </div>
            <?php endif; ?>

            <form method="post" autocomplete="off" novalidate>

                <div class="campo">
                    <label for="email">E-mail</label>
                    <div class="campo-input">
                        <i class="fa-solid fa-envelope icone"></i>
                        <input type="email" id="email" name="email" autocomplete="off" placeholder="seu@email.com">
                    </div>
                </div>

                <div class="campo">
                    <label for="senha">Senha</label>
                    <div class="campo-input campo-senha">
                        <i class="fa-solid fa-lock icone"></i>
                        <input type="password" id="senha" name="senha" autocomplete="new-password" placeholder="Sua senha">
                        <button type="button" class="ver-senha" id="ver-senha" aria-label="Mostrar senha" title="Mostrar senha">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-entrar">
                    Entrar
                </button>

            </form>

        </div>

    </section>

</div>


<script>
/* ===== Chuvinha (painel azul) ===== */
const canvas = document.getElementById("rainCanvas");
const ctx = canvas.getContext("2d");

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

let drops = [];

function createDrop(){

    return{
        x: Math.random() * canvas.width,
        y: Math.random() * canvas.height,
        speed: 0.3 + Math.random(),
        size:10 + Math.random()*15,
        shape:shapes[Math.floor(Math.random()*shapes.length)]
    };

}

function resizeCanvas(){

    canvas.width = canvas.offsetWidth;
    canvas.height = canvas.offsetHeight;

    // quantidade proporcional ao tamanho do painel
    const quantidade = Math.max(14, Math.min(60, Math.round((canvas.width * canvas.height) / 14000)));

    drops = [];

    for(let i = 0; i < quantidade; i++){
        drops.push(createDrop());
    }

}

resizeCanvas();

function draw(){

    ctx.clearRect(
        0,
        0,
        canvas.width,
        canvas.height
    );

    drops.forEach((drop,index)=>{

        ctx.fillStyle = "#F3BE27";
        ctx.globalAlpha = 0.9;
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

    ctx.globalAlpha = 1;

    requestAnimationFrame(draw);
}

draw();

window.addEventListener(
    "resize",
    resizeCanvas
);

// Faz as mensagens de aviso (erro/sucesso) do login sumirem sozinhas após 7s
document.querySelectorAll('.aviso').forEach(function (aviso) {
    setTimeout(function () {
        aviso.style.opacity = '0';
        aviso.style.maxHeight = '0';
        aviso.style.marginBottom = '0';
        aviso.style.paddingTop = '0';
        aviso.style.paddingBottom = '0';

        aviso.addEventListener('transitionend', function () {
            aviso.remove();
        }, { once: true });
    }, 7000);
});
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

        const aberto = menu.classList.toggle('ativo');
        botao.setAttribute('aria-expanded', aberto ? 'true' : 'false');
        icone.className = aberto ? 'fa-solid fa-xmark' : 'fa-solid fa-bars';
    });

    document.addEventListener('click', (e) => {
        if (!menu.contains(e.target)) fechar();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') fechar();
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 900) fechar();
    });
})();

/* ===== Mostrar / ocultar senha ===== */
(() => {
    const botao = document.getElementById('ver-senha');
    const campo = document.getElementById('senha');
    if (!botao || !campo) return;

    botao.addEventListener('click', () => {
        const mostrar = campo.type === 'password';
        campo.type = mostrar ? 'text' : 'password';
        botao.querySelector('i').className = mostrar ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
        botao.setAttribute('aria-label', mostrar ? 'Ocultar senha' : 'Mostrar senha');
    });
})();
</script>
</body>
</html>
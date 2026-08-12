<?php
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../controllers/auth.php';


?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chypher.IA</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<link rel="stylesheet" href="../assets/css/home.css">

<style>
    a {
  text-decoration: none;
  color: inherit;
}
</style>
</head>
<body>

<canvas id="rainCanvas"></canvas>

<header>
    <?php if (!isset($_SESSION['id']) || $_SESSION['id'] === null): ?>
            <a class="aba-link" href="login.php">Logar</a>
            
        <?php else: ?>
                <a href="logout.php">deslogar</a>
            
        <?php endif; ?>
        <?php if(!isset($_SESSION['id']) ||$_SESSION['id'] != 1): ?>
        
            <a href="contato/conversa.php" >Conversa</a>
         <?php else: ?>
            <a href="contato/admin.php">Lista de usuarios</a>
        <?php endif; ?>
</header>

<header>

     <div class="logo">
        Crypher.IA
    </div>

    <nav>
        <a href="#">Contato</a>
        <a href="#">IA</a>
        <a href="#">Sobre nós</a>
    
        
    </nav>
    <?php if($_SESSION['id'] == null): ?>
    <button class="btn-cadastro">
        <a href="cadastrar.php">
        Cadastre-se
        <a/>
    </button>
    <?php endif; ?>

</header>

<main class="hero">

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

            <button class="btn-primary">
                Começar Agora
            </button>

        </div>

    </div>

    <div class="hero-image">
        <img src="https://e-safer.com.br/wp-content/uploads/2023/06/seguranca-cibernetica-de-ia-protecao-contra-virus-scaled.jpg" 
             alt="cibersegurança"
             class="hero-img">
        </div>

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
                Digite a URL do seu site para que a inteligência artificial
                possa iniciar a análise de segurança.
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

    <section class="deteccoes">

    <h2>O que nossa IA detecta?</h2>

    <p class="deteccoes-subtitulo">
        Nossa inteligência artificial analisa seu site em busca das principais
        vulnerabilidades de segurança.
    </p>

    <div class="cards-deteccoes">

        <div class="card-deteccao">

            <div class="icone-deteccao">
                <i class="fa-solid fa-database"></i>
            </div>

            <h3>SQL Injection</h3>

            <p>
                Detecta comandos maliciosos que podem comprometer o banco de
                dados da aplicação.
            </p>

        </div>

        <div class="card-deteccao">

            <div class="icone-deteccao">
                <i class="fa-solid fa-code"></i>
            </div>

            <h3>Cross-Site Scripting</h3>

            <p>
                Identifica scripts maliciosos capazes de roubar informações
                e sessões de usuários.
            </p>

        </div>

        <div class="card-deteccao">

            <div class="icone-deteccao">
                <i class="fa-solid fa-lock"></i>
            </div>

            <h3>Falhas de autenticação</h3>

            <p>
                Analisa problemas de login, senhas fracas e gerenciamento
                incorreto de sessões.
            </p>

        </div>

        <div class="card-deteccao">

            <div class="icone-deteccao">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>

            <h3>Configurações inseguras</h3>

            <p>
                Verifica configurações que podem facilitar ataques ou expor
                informações importantes.
            </p>

        </div>

        <div class="card-deteccao">

            <div class="icone-deteccao">
                <i class="fa-solid fa-folder-open"></i>
            </div>

            <h3>Exposição de dados</h3>

            <p>
                Identifica arquivos e informações sensíveis que estão públicos
                sem necessidade.
            </p>

        </div>

        <div class="card-deteccao">

            <div class="icone-deteccao">
                <i class="fa-solid fa-shield-halved"></i>
            </div>

            <h3>Boas práticas OWASP</h3>

            <p>
                Compara seu site com os principais padrões internacionais de
                segurança para aplicações web.
            </p>

        </div>

    </div>

</section>

<section class="sobre">

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
                <img src="img/heittor.jpeg" alt="">
            </div>

            <h3>Heittor Moreira Rodrigues</h3>
            <p>Desenvolvedor Backend e Líder do Projeto</p>

        </div>

        <!-- Miriã -->
        <div class="membro">

            <div class="foto-membro">
                <img src="img/miria.jpeg" alt="">
            </div>

            <h3>Miriã Marques de Oliveira</h3>
            <p>Designer e Desenvolvedora Frontend</p>

        </div>

        <!-- Giovana -->
        <div class="membro">

            <div class="foto-membro">
                <img src="img/giovana.jpeg" alt="">
            </div>

            <h3>Giovana Akemi Hirayama Botelho</h3>
            <p>Responsável pela Documentação</p>

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

        <button class="btn-missao">
            Saiba mais agora
        </button>

    </div>

</section>

<footer>

    <div class="linha-footer"></div>

    <div class="footer-links">
        <a href="#">Termos de Uso</a>
        <a href="#">Política de Privacidade</a>
    </div>

</footer>

<script >const canvas = document.getElementById("rainCanvas");
const ctx = canvas.getContext("2d");

function resizeCanvas() {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
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

function createDrop() {
    return {
        x: Math.random() * canvas.width,
        y: Math.random() * canvas.height,
        speed: 0.5 + Math.random() * 1.5,
        size: 12 + Math.random() * 18,
        shape: shapes[Math.floor(Math.random() * shapes.length)]
    };
}

for(let i = 0; i < 70; i++){
    drops.push(createDrop());
}

function draw(){

    ctx.clearRect(0,0,canvas.width,canvas.height);

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
                y:-50
            };

        }

    });

    requestAnimationFrame(draw);
}

draw();

window.addEventListener("resize",resizeCanvas);</script>



</body>
</html>
<?php
session_start();

require '../config/config.php';
require '../controllers\auth.php';

if($_SERVER['REQUEST_METHOD']){
    $email =  $_POST['email'] ?? null;
    $senha = $_POST['senha']  ?? null;

login( $email,$senha);


}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
   <title>Cadastro - Crypher.IA</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Poppins',sans-serif;
}

body{
    width:100%;
    height:100vh;
    overflow:hidden;
    background:#ffffff;
}

/* chuvinha */

#rainCanvas{
    position:absolute;
    inset:0;

    width:100%;
    height:100%;

    z-index:1;
    pointer-events:none;
}

.right-panel h1,
.right-panel p,
.form-card{
    position:relative;
    z-index:2;
}

.container{
    position:relative;
    z-index:2;

    display:flex;
    width:100%;
    height:100vh;
}

.left-panel{
    width:40%;
    background:#4348D9;

    color:#fff;

    display:flex;
    flex-direction:column;

    padding:30px;

    position:relative;
    overflow:hidden;
}

.logo,
.welcome,
.btn-login{
    position:relative;
    z-index:2;
}

.logo{
    font-size:2.8rem;
    font-weight:700;
}

.logo-sub{
    font-size:1.1rem;
    margin-left:130px;
    margin-top:-10px;
}

.welcome{
    flex:1;

    display:flex;
    flex-direction:column;
    justify-content:center;
}

.welcome h2{
    font-size:2.2rem;
    line-height:1.2;
    margin-bottom:20px;
}

.welcome p{
    font-size:1.1rem;
    margin-bottom:30px;
}

.btn-login,
.btn-cadastrar{
    width:180px;
    height:52px;

    border:none;
    border-radius:999px;

    background:#F3BE27;
    color:#222;

    font-size:1.1rem;
    font-weight:700;

    cursor:pointer;

    transition:all .3s ease;

    box-shadow:0 8px 18px rgba(243,190,39,.30);
}

.btn-login:hover,
.btn-cadastrar:hover{
    transform:translateY(-3px) scale(1.03);

    box-shadow:0 12px 24px rgba(243,190,39,.45);
}

.btn-cadastrar{
    margin-left:90px;
}

.right-panel{
    width:60%;
    background:#ffffff;

    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
}

.right-panel h1{
    font-size:3rem;
    margin-bottom:5px;
}

.right-panel p{
    color:#555;
    margin-bottom:25px;
}

.form-card{
    background:#4348D9;

    width:420px;

    padding:30px;

    border-radius:12px;

    box-shadow:0 10px 25px rgba(0,0,0,.15);
}

.form-card form{
    display:flex;
    flex-direction:column;
}

.form-card label{
    color:black;
    font-weight:600;
    margin-bottom:8px;
}

.form-card input{
    width:100%;
    height:45px;

    border:none;
    outline:none;

    border-radius:10px;

    padding:0 15px;

    margin-bottom:18px;

    font-size:1rem;
}


@media(max-width:900px){

    .container{
        flex-direction:column;
    }

    .left-panel{
        width:100%;
        height:35vh;
    }

    .right-panel{
        width:60%;
        background:#ECECEC;

        position:relative;
        overflow:hidden;

        display:flex;
        flex-direction:column;
        justify-content:center;
        align-items:center;
    }

    .form-card{
        width:90%;
        max-width:420px;
    }

    .logo-sub{
        margin-left:0;
    }

}
a {
  text-decoration: none;
  color: inherit;
}

</style>
</head>
<body>
          <div class="container">

 

    <!-- lado branco -->

    <section class="left-panel">

         <canvas id="rainCanvas"></canvas>

        <div>
            <div class="logo">Crypher.IA</div>
        </div>

        <div class="welcome">

            <h2>
                Seja bem-vindo ao<br>
                Crypher IA
            </h2>

            <p>
                Acesse sua conta agora!
            </p>

            <button class="btn-login">
                <a href="cadastrar.php" >
                cadastrar
                </a>
            </button>

        </div>

    </section>

    <!-- lado roxo-azulado -->

    <section class="right-panel">


        <h1>Entre na sua conta</h1>


        <div class="form-card">

            <form method="post">

              

                <label>E-mail:</label>
                <input type="email" name="email">

                <label>Senha:</label>
                <input type="senha" name="senha">

                <button type="submit" class="btn-cadastrar">
                    logar
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
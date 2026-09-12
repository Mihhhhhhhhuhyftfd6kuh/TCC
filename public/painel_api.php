<?php
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../controllers/auth.php';
require __DIR__ . '/../controllers/user.php';

if (session_status() === PHP_SESSION_NONE){
session_start();
}

verificacao_L();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisar código - Crypher.IA</title>
    <style>
        
    </style>
</head>
<body>
    <header>
        <a href="home.php">home</a>
        <a href="perfil.php">perfil</a>
    </header>

    <h1>Analisar código</h1>
    <p>Cole abaixo um trecho de código PHP para a IA analisar em busca de vulnerabilidades.</p>

    <form id="form-analise">
        <textarea id="texto" name="texto" placeholder="Cole seu código aqui..."></textarea>
        <br>
        <input type="file" name="arquivo" id="arquivo">
        <button type="submit">Analisar</button>
    </form>

    <div id="status"></div>
    <div id="resultado"></div>

    <script>
    (() => {
        const form = document.getElementById('form-analise');
        const textarea = document.getElementById('codigo');
        const statusEl = document.getElementById('status');
        const resultadoEl = document.getElementById('resultado');

        form.addEventListener('submit', async (evento) => {
            evento.preventDefault();

            const codigo = textarea.value.trim();
            if (codigo === '') return;

            statusEl.textContent = 'Analisando...';
            resultadoEl.style.display = 'none';

            try {
                const corpo = new URLSearchParams();
                corpo.append('codigo', codigo);

                const resp = await fetch('analisar_codigo.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: corpo.toString()
                });

                const dados = await resp.json();

                if (dados.sucesso) {
                    resultadoEl.textContent = dados.resultado;
                    resultadoEl.style.display = 'block';
                    statusEl.textContent = '';
                } else {
                    statusEl.textContent = 'Erro: ' + dados.erro;
                }
            } catch (erro) {
                statusEl.textContent = 'Erro de conexão: ' + erro.message;
            }
        });
    })();
    </script>
</body>
</html>
<?php
// Página pública — não exige login.
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Política de Privacidade - Crypher.IA</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

<style>
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
    --fundo: #f3f3fa;
    --texto: #2a2a38;
    --borda: #e5e5ee;
}

a { color: var(--azul); text-decoration: none; }
a:hover { text-decoration: underline; }

body {
    background: var(--fundo);
    color: var(--texto);
    min-height: 100vh;
}

header {
    background: var(--azul);
    padding: 20px 55px;

    display: flex;
    align-items: center;
    justify-content: space-between;
}

.logo {
    color: #fff;
    font-size: 1.7rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 9px;
}

header a.voltar {
    color: rgba(255,255,255,.9);
    font-size: .88rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 7px;
}

header a.voltar:hover { color: #fff; text-decoration: none; }

main {
    max-width: 880px;
    margin: 0 auto;
    padding: 50px 24px 90px;
}

.doc-card {
    background: #fff;
    border-radius: 20px;
    padding: 46px 50px;
    box-shadow: 0 10px 30px rgba(45,45,100,.08);
}

.doc-card h1 {
    font-size: 2rem;
    font-weight: 800;
    color: #222;
    margin-bottom: 8px;
}

.doc-card > p.atualizado {
    color: #888;
    font-size: .82rem;
    margin-bottom: 30px;
}

.aviso {
    background: #fff8e4;
    border: 1px solid #f3e0a0;
    border-radius: 12px;
    padding: 14px 18px;
    margin-bottom: 34px;
    font-size: .82rem;
    line-height: 1.6;
    color: #6b5a12;
}

.doc-card section {
    margin-bottom: 30px;
}

.doc-card h2 {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--azul);
    margin-bottom: 10px;

    display: flex;
    align-items: center;
    gap: 9px;
}

.doc-card h2 span.num {
    width: 26px;
    height: 26px;
    flex-shrink: 0;
    border-radius: 8px;
    background: #eeeeff;
    color: var(--azul);
    font-size: .8rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
}

.doc-card p,
.doc-card li {
    font-size: .92rem;
    line-height: 1.75;
    color: #444;
}

.doc-card ul {
    margin: 8px 0 0 20px;
}

.doc-card li { margin-bottom: 6px; }

.doc-card strong { color: #222; }

table.dados {
    width: 100%;
    border-collapse: collapse;
    margin-top: 14px;
    font-size: .85rem;
}

table.dados th,
table.dados td {
    text-align: left;
    padding: 10px 12px;
    border-bottom: 1px solid var(--borda);
    vertical-align: top;
}

table.dados th {
    color: #222;
    font-weight: 700;
    background: #f7f7fd;
}

.selo-cripto {
    display: inline-flex;
    align-items: center;
    gap: 8px;

    margin-top: 10px;
    padding: 10px 16px;

    background: #eefaf0;
    border: 1px solid #c9ecd0;
    border-radius: 12px;

    font-size: .85rem;
    color: #1f7a3a;
    font-weight: 600;
}

.selo-cripto i { font-size: 1rem; }

footer {
    text-align: center;
    padding: 24px;
    color: #999;
    font-size: .78rem;
}

@media (max-width: 768px) {
    header { padding: 16px 20px; }
    .logo { font-size: 1.3rem; }
    main { padding: 30px 14px 60px; }
    .doc-card { padding: 28px 22px; }
    .doc-card h1 { font-size: 1.5rem; }
    table.dados { font-size: .78rem; }
}
</style>
</head>
<body>

<header>
    <a href="home.php" class="logo">Crypher.IA</a>
    <a href="home.php" class="voltar"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
</header>

<main>
    <div class="doc-card">
        <h1>Política de Privacidade</h1>
        <p class="atualizado">Última atualização: <?= date('d/m/Y') ?></p>

        <div class="aviso">
            <strong>Aviso:</strong> este documento é um modelo de referência, escrito para o projeto acadêmico
            (TCC) Crypher.IA, em conformidade com os princípios gerais da LGPD (Lei nº 13.709/2018). Ele não
            substitui orientação jurídica profissional.
        </div>

        <section>
            <h2><span class="num">1</span> Quais dados coletamos</h2>
            <table class="dados">
                <tr><th>Dado</th><th>Quando é coletado</th></tr>
                <tr><td>Nome e e-mail</td><td>No cadastro da conta</td></tr>
                <tr><td>Senha</td><td>No cadastro — armazenada apenas como hash (nunca em texto puro)</td></tr>
                <tr><td>Mensagens do chat</td><td>Ao conversar pelo canal de Contato</td></tr>
                <tr><td>Código / arquivos enviados</td><td>Ao usar o analisador de código com IA</td></tr>
                <tr><td>Dados técnicos básicos</td><td>Automaticamente, como logs de erro do servidor</td></tr>
            </table>
        </section>

        <section>
            <h2><span class="num">2</span> Para que usamos esses dados</h2>
            <ul>
                <li><strong>Nome e e-mail:</strong> identificar sua conta e permitir o login.</li>
                <li><strong>Senha (hash):</strong> autenticar o acesso à plataforma com segurança.</li>
                <li><strong>Mensagens do chat:</strong> viabilizar o atendimento entre você e a equipe de suporte.</li>
                <li><strong>Código/arquivos enviados:</strong> gerar a análise de vulnerabilidades solicitada.</li>
            </ul>
        </section>

        <section>
            <h2><span class="num">3</span> Com quem compartilhamos</h2>
            <p>Não vendemos seus dados. Compartilhamos informações apenas com:</p>
            <ul>
                <li><strong>Provedor de banco de dados (Neon/PostgreSQL):</strong> onde seus dados ficam armazenados.</li>
                <li><strong>Provedor de hospedagem (Render):</strong> onde a aplicação roda.</li>
                <li><strong>Serviço de IA de terceiros:</strong> o texto/código enviado ao analisador é repassado a um
                    modelo de IA externo para gerar o resultado da análise de vulnerabilidades.</li>
            </ul>
        </section>

        <section>
            <h2><span class="num">4</span> Como protegemos seus dados</h2>
            <ul>
                <li>Senhas nunca são guardadas em texto puro — usamos hash (<code>password_hash</code>).</li>
                <li>A conexão entre a aplicação e o banco de dados usa criptografia em trânsito (SSL/TLS).</li>
                <li>As mensagens do chat são criptografadas em repouso no banco de dados (AES-256-GCM) — ou
                    seja, mesmo quem tiver acesso direto ao banco não consegue ler o conteúdo das mensagens
                    sem a chave de descriptografia, que fica apenas nas variáveis de ambiente do servidor.</li>
            </ul>
            <div class="selo-cripto">
                <i class="fa-solid fa-lock"></i>
                Mensagens de chat protegidas com criptografia em repouso
            </div>
            <p style="margin-top:10px;">
                Importante: essa criptografia protege contra acesso direto e não autorizado ao banco de dados.
                Ela não impede que a própria aplicação — que precisa exibir a conversa na tela do chat — decifre
                as mensagens para mostrá-las a você e à equipe de suporte dentro da plataforma.
            </p>
        </section>

        <section>
            <h2><span class="num">5</span> Cookies e sessão</h2>
            <p>
                Usamos um cookie de sessão (padrão do PHP) apenas para manter você conectado enquanto usa a
                plataforma. Ele é removido quando você faz logout ou fecha a sessão.
            </p>
        </section>

        <section>
            <h2><span class="num">6</span> Seus direitos</h2>
            <p>De acordo com a LGPD, você pode solicitar a qualquer momento:</p>
            <ul>
                <li>Confirmação de que tratamos seus dados e acesso a eles;</li>
                <li>Correção de dados incompletos, inexatos ou desatualizados;</li>
                <li>Exclusão da sua conta e dos dados associados a ela;</li>
                <li>Informação sobre com quem compartilhamos seus dados.</li>
            </ul>
            <p>Para exercer esses direitos, use o canal de <a href="contato/conversa.php">Contato</a>.</p>
        </section>

        <section>
            <h2><span class="num">7</span> Retenção dos dados</h2>
            <p>
                Mantemos seus dados enquanto sua conta estiver ativa. Caso solicite a exclusão da conta,
                removemos seus dados pessoais, exceto quando a lei exigir retenção por período maior.
            </p>
        </section>

        <section>
            <h2><span class="num">8</span> Público-alvo</h2>
            <p>
                O Crypher.IA é destinado a maiores de 18 anos ou a quem tenha capacidade legal para aceitar
                estes termos. Não coletamos intencionalmente dados de menores de idade.
            </p>
        </section>

        <section>
            <h2><span class="num">9</span> Alterações nesta política</h2>
            <p>
                Podemos atualizar este documento conforme o projeto evolui. A data no topo da página indica a
                versão mais recente.
            </p>
        </section>

        <section>
            <h2><span class="num">10</span> Contato</h2>
            <p>
                Dúvidas sobre esta Política de Privacidade podem ser enviadas pelo nosso canal de
                <a href="contato/conversa.php">Contato</a> dentro da plataforma.
            </p>
        </section>
    </div>
</main>

<footer>
    &copy; <?= date('Y') ?> Crypher.IA — Projeto acadêmico (TCC)
</footer>

</body>
</html>
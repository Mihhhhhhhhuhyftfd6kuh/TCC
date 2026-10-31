<?php
// Página pública — não exige login.
// Se quiser mostrar a bolinha/hambúrguer quando o usuário estiver logado,
// dá pra incluir aqui a mesma lógica de sessão usada nas outras páginas.
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Termos de Uso - Crypher.IA</title>

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
    background: var(--azul-claro, #eeeeff);
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
        <h1>Termos de Uso</h1>
        <p class="atualizado">Última atualização: <?= date('d/m/Y') ?></p>

        <div class="aviso">
            <strong>Aviso:</strong> este documento é um modelo de referência, escrito para o projeto acadêmico
            (TCC) Crypher.IA. Ele não substitui orientação jurídica profissional
        </div>

        <section>
            <h2><span class="num">1</span> Aceitação dos termos</h2>
            <p>
                Ao criar uma conta ou utilizar o Crypher.IA, você concorda com estes Termos de Uso e com a
                nossa <a href="privacidade.php">Política de Privacidade</a>. Se você não concordar com algum
                ponto, pedimos que não utilize a plataforma.
            </p>
        </section>

        <section>
            <h2><span class="num">2</span> O que é o Crypher.IA</h2>
            <p>
                O Crypher.IA é uma plataforma que utiliza inteligência artificial para analisar trechos de
                código ou arquivos enviados pelo usuário, em busca de vulnerabilidades de segurança (como SQL
                Injection, XSS, falhas de autenticação e configurações inseguras), além de oferecer um canal
                de chat de suporte entre usuários e a equipe. O projeto foi desenvolvido como Trabalho de
                Conclusão de Curso (TCC) e pode estar sujeito a instabilidades típicas de um ambiente de
                testes.
            </p>
        </section>

        <section>
            <h2><span class="num">3</span> Cadastro e conta</h2>
            <ul>
                <li>Você deve fornecer informações verdadeiras ao se cadastrar (nome, e-mail e senha).</li>
                <li>Você é responsável por manter sua senha em sigilo e por tudo o que acontecer na sua conta.</li>
                <li>Caso identifique uso não autorizado da sua conta, avise a equipe pelo canal de contato.</li>
            </ul>
        </section>

        <section>
            <h2><span class="num">4</span> Uso aceitável</h2>
            <p>Ao usar a plataforma, você concorda em <strong>não</strong>:</p>
            <ul>
                <li>Enviar código, arquivos ou mensagens com conteúdo ilegal, malicioso ou que viole direitos de terceiros;</li>
                <li>Enviar código de propriedade de terceiros sem autorização para analisá-lo;</li>
                <li>Tentar sobrecarregar, invadir ou comprometer a infraestrutura da plataforma;</li>
                <li>Usar os resultados da análise de IA para causar dano a sistemas de terceiros.</li>
            </ul>
        </section>

        <section>
            <h2><span class="num">5</span> Propriedade do conteúdo enviado</h2>
            <p>
                O código e os arquivos que você envia para análise continuam sendo seus. Usamos esse conteúdo
                apenas para processá-lo e devolver o resultado da análise — não reivindicamos propriedade sobre
                ele. Partes do código podem ser enviadas a um serviço de IA de terceiros para gerar a análise
                (veja a <a href="privacidade.php">Política de Privacidade</a> para mais detalhes).
            </p>
        </section>

        <section>
            <h2><span class="num">6</span> Limitação de responsabilidade</h2>
            <p>
                A análise feita pela IA é um auxílio e <strong>não substitui</strong> uma auditoria de
                segurança profissional. Não garantimos que a ferramenta identifique 100% das vulnerabilidades
                existentes em um código, nem que o código aprovado esteja livre de falhas. Por ser um projeto
                acadêmico, o serviço é fornecido "como está", sem garantias de disponibilidade contínua.
            </p>
        </section>

        <section>
            <h2><span class="num">7</span> Encerramento de conta</h2>
            <p>
                Você pode solicitar a exclusão da sua conta e dos seus dados a qualquer momento pelo canal de
                contato. Também podemos suspender ou encerrar contas que violem estes termos.
            </p>
        </section>

        <section>
            <h2><span class="num">8</span> Alterações nestes termos</h2>
            <p>
                Podemos atualizar este documento conforme o projeto evolui. A data no topo da página indica a
                versão mais recente. O uso continuado da plataforma após uma alteração significa que você
                concorda com os novos termos.
            </p>
        </section>

        <section>
            <h2><span class="num">9</span> Contato</h2>
            <p>
                Dúvidas sobre estes Termos de Uso podem ser enviadas pelo nosso canal de
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
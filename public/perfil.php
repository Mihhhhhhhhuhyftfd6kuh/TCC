<?php
session_start();

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../controllers/user.php';

verificacao_L();

$id = $_SESSION['id'];

// Busca os dados atuais do usuário logado
$sqlUsuario = "SELECT nome, email FROM usuarios WHERE id = :id";
$stmtUsuario = $pdo->prepare($sqlUsuario);
$stmtUsuario->bindParam(':id', $id, PDO::PARAM_INT);
$stmtUsuario->execute();
$usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

// Inicial exibida na bolinha da conta
$nomeAtual = trim((string) ($usuario['nome'] ?? ''));
$inicialUsuario = $nomeAtual !== '' ? mb_strtoupper(mb_substr($nomeAtual, 0, 1)) : '?';

// Admin vai para a lista de usuários; usuário comum vai direto para o chat de contato
$linkContato = ((int) $id === 1) ? 'contato/admin.php' : 'contato/conversa.php';

// atualizar_perfil.php manda ?sucesso=1 ou ?sucesso=mensagem; os arquivos de
// apagar mandam ?sucesso=mensagem. Os dois casos são tratados aqui.
$sucesso = $_GET['sucesso'] ?? null;
$erro    = $_GET['erro'] ?? null;

if ($sucesso === '1') {
    $sucesso = 'Dados atualizados com sucesso.';
}

// Qual aba deve abrir já selecionada (dados | privacidade)
$abaAtiva = $_GET['aba'] ?? 'dados';
if (!in_array($abaAtiva, ['dados', 'privacidade'], true)) {
    $abaAtiva = 'dados';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#4348D9">
    <title>Meu perfil - Crypher.IA</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../assets/img/mascote_s_fundo.png">

    <style>
/* =========================================
   CRYPHER.IA - MEU PERFIL
   Mesmo padrão da página da IA: header azul com
   bolinha da conta e hambúrguer no celular
========================================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;

    -webkit-tap-highlight-color: transparent;
}

:root {
    --azul: #4348D9;
    --azul-escuro: #3539b8;
    --azul-claro: #eeeeff;
    --amarelo: #F3BE27;
    --fundo: #f5f6ff;
    --texto: #202020;
    --borda: #e5e5ee;
}


/* =========================================
   PÁGINA (100% da janela; só o conteúdo rola)
========================================= */

html,
body {
    width: 100%;
    height: 100%;
    overflow: hidden;
}

body {
    height: 100dvh;
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
    height: 120px;
    min-height: 120px;
    flex-shrink: 0;

    padding: 0 80px;

    background: var(--azul);

    display: flex;
    align-items: center;
    justify-content: space-between;

    position: relative;
    z-index: 10;

    box-shadow: 0 5px 20px rgba(0, 0, 0, .12);
}

.logo {
    color: #fff;

    font-size: 2.5rem;
    font-weight: 700;

    letter-spacing: -.5px;

    text-decoration: none;

    flex-shrink: 0;
}

header nav {
    position: absolute;

    left: 50%;
    top: 50%;

    transform: translate(-50%, -50%);

    display: flex;
    align-items: center;

    gap: 16px;
}

header nav a {
    color: rgba(255, 255, 255, .9);

    text-decoration: none;

    font-size: 1rem;
    font-weight: 600;

    padding: 12px 20px;

    border-radius: 999px;

    transition:
        background .25s ease,
        color .25s ease;
}

header nav a:hover {
    background: rgba(255, 255, 255, .15);
    color: #fff;
}


/* =========================================
   BOLINHA DA CONTA (mesmo estilo da Home e da IA)
========================================= */

.conta-menu {
    position: relative;

    flex-shrink: 0;
}

.conta-botao {
    width: 52px;
    height: 52px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #fff;
    color: #4348D9;

    border: none;
    border-radius: 50%;

    font-family: inherit;
    font-weight: 700;
    font-size: 1.15rem;
    line-height: 1;

    text-transform: uppercase;

    cursor: pointer;

    box-shadow: 0 4px 12px rgba(0, 0, 0, .18);

    transition: .3s ease;
}

.conta-botao:hover {
    transform: translateY(-2px);

    box-shadow: 0 8px 18px rgba(0, 0, 0, .25);
}

.conta-dropdown {
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

.conta-dropdown.ativo {
    display: block;
}

.conta-dropdown a {
    display: flex;
    align-items: center;

    gap: 10px;

    padding: 13px 20px;

    color: #222;

    text-decoration: none;

    font-size: .9rem;
    font-weight: 500;

    transition: .2s ease;
}

.conta-dropdown a i {
    width: 16px;

    text-align: center;

    color: var(--azul);
}

.conta-dropdown a:hover {
    background: #f2f2f2;
}


/* =========================================
   BLOCO DIREITO (hambúrguer + bolinha da conta)
   Mesmo padrão da Home. O hambúrguer só aparece no celular.
========================================= */

.header-direita {
    position: relative;

    display: flex;
    align-items: center;

    gap: 10px;

    margin-left: auto;
}

.menu-hamb {
    display: none;

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

    text-decoration: none;

    transition: .2s ease;
}

.hamb-dropdown a i {
    width: 16px;

    text-align: center;

    color: var(--azul);
}

.hamb-dropdown a:hover {
    background: #f2f2f2;
}


/* =========================================
   CONTEÚDO (área que rola)
========================================= */

main {
    flex: 1;
    min-height: 0;

    width: 100%;

    background: var(--fundo);

    overflow-y: auto;
    overflow-x: hidden;

    -webkit-overflow-scrolling: touch;
}

main::-webkit-scrollbar {
    width: 6px;
}

main::-webkit-scrollbar-thumb {
    background: #d1d2e8;

    border-radius: 999px;
}

.conteudo {
    max-width: 700px;

    margin: 0 auto;
    padding: 40px 20px 60px;
}

h1 {
    margin-bottom: 25px;

    color: #222;

    font-size: 1.8rem;
    font-weight: 700;
}


/* =========================================
   AVISOS
========================================= */

.flash-success,
.flash-error {
    padding: 12px 16px;

    border-radius: 12px;

    margin-bottom: 20px;

    font-size: .9rem;
}

.flash-success {
    background: #d4edda;
    color: #155724;
}

.flash-error {
    background: #f8d7da;
    color: #721c24;
}


/* =========================================
   ABAS
========================================= */

.abas {
    display: flex;
    gap: 8px;

    margin-bottom: 20px;

    border-bottom: 2px solid var(--borda);

    overflow-x: auto;

    scrollbar-width: none;
}

.abas::-webkit-scrollbar {
    display: none;
}

.aba-botao {
    flex-shrink: 0;

    border: none;
    border-bottom: 3px solid transparent;
    border-radius: 0;

    background: transparent;

    padding: 12px 20px;
    margin-bottom: -2px;

    font-family: inherit;
    font-size: .95rem;
    font-weight: 600;

    color: #666;

    cursor: pointer;

    transition: .2s ease;
}

.aba-botao:hover {
    color: #222;
}

.aba-botao.ativa {
    color: var(--azul);

    border-bottom-color: var(--azul);
}

.aba-conteudo {
    display: none;
}

.aba-conteudo.ativa {
    display: block;
}


/* =========================================
   CARTÕES E FORMULÁRIOS
========================================= */

.card {
    background: #fff;

    border: 1px solid var(--borda);
    border-radius: 20px;

    padding: 30px;
    margin-bottom: 25px;

    box-shadow: 0 8px 25px rgba(45, 45, 100, .07);
}

.card h2 {
    font-size: 1.2rem;

    margin-bottom: 6px;

    color: #222;
}

.card p.desc {
    color: #666;

    font-size: .9rem;
    line-height: 1.55;

    margin: 6px 0 18px;
}

label {
    display: block;

    font-weight: 600;

    margin-bottom: 6px;

    color: #333;
}

input[type=text],
input[type=email],
input[type=password] {
    width: 100%;
    height: 46px;

    border: 2px solid var(--borda);
    border-radius: 12px;

    outline: none;

    background: #fafaff;

    padding: 0 14px;
    margin-bottom: 18px;

    font-family: inherit;
    font-size: 1rem;

    transition:
        border-color .2s ease,
        box-shadow .2s ease,
        background .2s ease;
}

input[type=text]:focus,
input[type=email]:focus,
input[type=password]:focus {
    border-color: var(--azul);

    background: #fff;

    box-shadow: 0 0 0 3px rgba(67, 72, 217, .08);
}

.campo-senha-nova {
    border-top: 1px solid #eee;

    margin-top: 6px;
    padding-top: 18px;
}

.campo-senha-nova p.desc {
    margin-top: 0;
}

button {
    border: none;
    border-radius: 999px;

    padding: 12px 26px;

    font-family: inherit;
    font-weight: 700;
    font-size: .95rem;

    cursor: pointer;

    transition: .25s ease;
}

.btn-salvar {
    background: var(--amarelo);
    color: #222;
}

.btn-salvar:hover {
    transform: translateY(-2px);

    box-shadow: 0 8px 16px rgba(243, 190, 39, .35);
}

.zona-perigo h2 {
    color: #c00;
}

.btn-apagar {
    background: #fff;
    color: #c00;

    border: 2px solid #c00;
}

.btn-apagar:hover {
    background: #c00;
    color: #fff;
}


/* =========================================
   TABLET
========================================= */

@media (max-width: 1050px) {

    header {
        padding: 0 30px;
    }

    .logo {
        font-size: 2.2rem;
    }
}


/* =========================================
   MOBILE
========================================= */

@media (max-width: 768px) {

    /* ---- header compacto: logo + bolinha da conta ---- */

    header {
        height: calc(84px + env(safe-area-inset-top));
        min-height: calc(84px + env(safe-area-inset-top));

        padding: env(safe-area-inset-top) 20px 0;

        box-shadow: 0 4px 14px rgba(0, 0, 0, .12);
    }

    .logo {
        font-size: 1.9rem;
    }

    .hamb-botao {
        width: 46px;
        height: 46px;

        font-size: 1.15rem;
    }

    header nav {
        display: none;
    }

    .menu-hamb {
        display: block;
    }

    .conta-botao {
        width: 46px;
        height: 46px;

        font-size: 1.15rem;
    }

    .conta-botao:hover {
        transform: none;
    }

    .conta-dropdown {
        top: calc(100% + 8px);

        min-width: 180px;
    }

    /* ---- conteúdo ---- */

    .conteudo {
        padding: 22px 14px 30px;
    }

    h1 {
        margin-bottom: 18px;

        font-size: 1.5rem;
    }

    .card {
        padding: 22px 18px;

        border-radius: 17px;
    }

    .aba-botao {
        padding: 11px 14px;

        font-size: .88rem;
    }

    /* 16px evita o zoom automático do iPhone ao focar no campo */
    input[type=text],
    input[type=email],
    input[type=password] {
        height: 48px;

        font-size: 16px;
    }

    .btn-salvar,
    .btn-apagar {
        width: 100%;
    }

}
    </style>
</head>
<body>
    <header>
        <a href="home.php" class="logo">Crypher.IA</a>

        <nav>
            <a href="home.php">Home</a>
            <a href="painel_api.php">IA</a>
            <a href="<?= $linkContato ?>">Contato</a>
        </nav>

        <div class="header-direita">

            <!-- Menu hambúrguer (só no celular, igual ao da Home) -->
            <div class="menu-hamb">
                <button type="button" class="hamb-botao" id="hamb-botao" title="Menu" aria-label="Menu" aria-haspopup="true" aria-expanded="false">
                    <i class="fa-solid fa-bars"></i>
                </button>

                <div class="hamb-dropdown" id="hamb-dropdown">
                    <a href="home.php"><i class="fa-solid fa-house"></i> Home</a>
                    <a href="painel_api.php"><i class="fa-solid fa-robot"></i> IA</a>
                    <a href="<?= $linkContato ?>"><i class="fa-regular fa-comments"></i> Contato</a>
                </div>
            </div>

            <!-- Bolinha da conta (mesmo estilo da Home e da IA) -->
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
        <div class="conteudo">
            <h1>Meu perfil</h1>

            <?php if ($sucesso): ?>
                <div class="flash-success"><?php echo htmlspecialchars($sucesso); ?></div>
            <?php endif; ?>
            <?php if ($erro): ?>
                <div class="flash-error"><?php echo htmlspecialchars($erro); ?></div>
            <?php endif; ?>

            <div class="abas">
                <button type="button" class="aba-botao <?= $abaAtiva === 'dados' ? 'ativa' : '' ?>" data-aba="dados" onclick="mostrarAba('dados')">
                    Dados da conta
                </button>
                <button type="button" class="aba-botao <?= $abaAtiva === 'privacidade' ? 'ativa' : '' ?>" data-aba="privacidade" onclick="mostrarAba('privacidade')">
                    Privacidade e conversas
                </button>
            </div>

            <!-- ===== Aba: Dados da conta ===== -->
            <div id="aba-dados" class="aba-conteudo <?= $abaAtiva === 'dados' ? 'ativa' : '' ?>">
                <div class="card">
                    <h2>Dados da conta</h2>
                    <p class="desc">Altere seu nome, e-mail ou senha. Informe sua senha atual pra confirmar.</p>

                    <form method="post" action="atulizar_perfil.php">
                        <label for="nome">Nome</label>
                        <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nome'] ?? ''); ?>" required>

                        <label for="email_novo">E-mail</label>
                        <input type="email" id="email_novo" name="email_novo" value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>" required>

                        <label for="senha_atual">Senha atual</label>
                        <input type="password" id="senha_atual" name="senha_atual" placeholder="Necessária para salvar qualquer alteração" required>

                        <div class="campo-senha-nova">
                            <p class="desc">Quer trocar de senha? Preencha os dois campos abaixo (deixe em branco pra manter a senha atual).</p>

                            <label for="nova_senha">Nova senha</label>
                            <input type="password" id="nova_senha" name="nova_senha" placeholder="Deixe em branco para não alterar">

                            <label for="confirmar_senha">Confirmar nova senha</label>
                            <input type="password" id="confirmar_senha" name="confirmar_senha" placeholder="Repita a nova senha">
                        </div>

                        <button type="submit" class="btn-salvar">Salvar alterações</button>
                    </form>
                </div>
            </div>

            <!-- ===== Aba: Privacidade e conversas ===== -->
            <div id="aba-privacidade" class="aba-conteudo <?= $abaAtiva === 'privacidade' ? 'ativa' : '' ?>">
                <div class="card zona-perigo">
                    <h2>Apagar histórico do chat de análise</h2>
                    <p class="desc">Remove permanentemente todas as suas conversas com a IA na página de análise de código.</p>

                    <form method="post" action="apagar_dados.php" onsubmit="return confirm('Tem certeza que deseja apagar todo o histórico do chat de análise? Essa ação não pode ser desfeita.');">
                        <input type="hidden" name="tipo" value="chat">
                        <button type="submit" class="btn-apagar">Apagar histórico do chat</button>
                    </form>
                </div>

                <div class="card zona-perigo">
                    <h2>Apagar conversa com o suporte</h2>
                    <p class="desc">Remove permanentemente sua conversa na aba de contato com a equipe.</p>

                    <form method="post" action="apagar_dados.php" onsubmit="return confirm('Tem certeza que deseja apagar toda a conversa com o suporte? Essa ação não pode ser desfeita.');">
                        <input type="hidden" name="tipo" value="conversa">
                        <button type="submit" class="btn-apagar">Apagar conversa</button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        /* ===== Bolinha da conta: abre ao clicar, fecha ao clicar fora ou com Esc ===== */
        (() => {
            const botao    = document.getElementById('conta-botao');
            const dropdown = document.getElementById('conta-dropdown');
            if (!botao || !dropdown) return;

            const fechar = () => {
                dropdown.classList.remove('ativo');
                botao.setAttribute('aria-expanded', 'false');
            };

            botao.addEventListener('click', (e) => {
                e.stopPropagation();
                const aberto = dropdown.classList.toggle('ativo');
                botao.setAttribute('aria-expanded', aberto ? 'true' : 'false');
            });

            document.addEventListener('click', (e) => {
                if (!dropdown.contains(e.target)) fechar();
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') fechar();
            });
        })();

        /* ===== Menu hambúrguer (celular): o ícone vira X quando está aberto ===== */
        (() => {
            const botao = document.getElementById('hamb-botao');
            const menu  = document.getElementById('hamb-dropdown');
            if (!botao || !menu) return;

            const icone = botao.querySelector('i');
            const conta = document.getElementById('conta-dropdown');
            const contaBotao = document.getElementById('conta-botao');

            const fechar = () => {
                menu.classList.remove('ativo');
                botao.setAttribute('aria-expanded', 'false');
                icone.className = 'fa-solid fa-bars';
            };

            botao.addEventListener('click', (e) => {
                e.stopPropagation();

                // fecha o menu da bolinha se estiver aberto
                if (conta) conta.classList.remove('ativo');
                if (contaBotao) contaBotao.setAttribute('aria-expanded', 'false');

                const aberto = menu.classList.toggle('ativo');
                botao.setAttribute('aria-expanded', aberto ? 'true' : 'false');
                icone.className = aberto ? 'fa-solid fa-xmark' : 'fa-solid fa-bars';
            });

            // abrir o menu da bolinha fecha o hambúrguer
            if (contaBotao) contaBotao.addEventListener('click', fechar);

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

        /* ===== Teclado do celular: acompanha a altura visível e esconde a barra inferior ===== */
        (() => {
            const ehMobile = () => window.innerWidth <= 768;

            function ajustarAltura() {
                if (window.visualViewport && ehMobile()) {
                    document.body.style.height = window.visualViewport.height + 'px';
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

        /* ===== Abas ===== */
        function mostrarAba(nome) {
            document.querySelectorAll('.aba-conteudo').forEach(el => {
                el.classList.toggle('ativa', el.id === 'aba-' + nome);
            });
            document.querySelectorAll('.aba-botao').forEach(el => {
                el.classList.toggle('ativa', el.dataset.aba === nome);
            });

            // guarda a aba na URL, sem recarregar a página,
            // pra sobreviver a um F5 ou a um link compartilhado
            const url = new URL(window.location);
            url.searchParams.set('aba', nome);
            window.history.replaceState({}, '', url);
        }
    </script>
</body>
</html>
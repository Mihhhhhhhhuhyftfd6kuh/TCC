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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu perfil - Crypher.IA</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }

        body { background:#ECECEC; min-height:100vh; padding-bottom:60px; }

        header {
            background:#4348D9;
            padding:20px 40px;
            display:flex;
            justify-content:space-between;
            align-items:center;
        }

        header .logo { color:#fff; font-size:1.6rem; font-weight:700; }

        header nav a {
            color:#fff;
            text-decoration:none;
            font-weight:500;
            margin-left:25px;
        }

        header nav a:hover { opacity:.8; }

        main { max-width:700px; margin:40px auto; padding:0 20px; }

        h1 { margin-bottom:25px; color:#222; }

        .flash-success, .flash-error {
            padding:12px 16px;
            border-radius:8px;
            margin-bottom:20px;
            font-size:.9rem;
        }
        .flash-success { background:#d4edda; color:#155724; }
        .flash-error   { background:#f8d7da; color:#721c24; }

        /* ===== Abas ===== */
        .abas {
            display:flex;
            gap:8px;
            margin-bottom:20px;
            border-bottom:2px solid #ddd;
        }

        .aba-botao {
            border:none;
            background:transparent;
            padding:12px 20px;
            font-size:.95rem;
            font-weight:600;
            color:#666;
            cursor:pointer;
            border-bottom:3px solid transparent;
            margin-bottom:-2px;
            transition:.2s ease;
        }

        .aba-botao:hover { color:#222; }

        .aba-botao.ativa {
            color:#4348D9;
            border-bottom-color:#4348D9;
        }

        .aba-conteudo { display:none; }
        .aba-conteudo.ativa { display:block; }

        .card {
            background:#fff;
            border-radius:14px;
            padding:30px;
            margin-bottom:25px;
            box-shadow:0 8px 20px rgba(0,0,0,.08);
        }

        .card h2 { font-size:1.2rem; margin-bottom:6px; color:#222; }
        .card p.desc { color:#666; font-size:.9rem; margin:6px 0 18px; }

        label { display:block; font-weight:600; margin-bottom:6px; color:#333; }

        input[type=text], input[type=email], input[type=password] {
            width:100%;
            height:44px;
            border:1px solid #ccc;
            border-radius:8px;
            padding:0 14px;
            margin-bottom:18px;
            font-size:1rem;
        }

        .campo-senha-nova {
            border-top: 1px solid #eee;
            margin-top: 6px;
            padding-top: 18px;
        }

        .campo-senha-nova p.desc { margin-top: 0; }

        button {
            border:none;
            border-radius:999px;
            padding:12px 26px;
            font-weight:700;
            font-size:.95rem;
            cursor:pointer;
            transition:.25s ease;
        }

        .btn-salvar { background:#F3BE27; color:#222; }
        .btn-salvar:hover { transform:translateY(-2px); box-shadow:0 8px 16px rgba(243,190,39,.35); }

        .zona-perigo h2 { color:#c00; }

        .btn-apagar { background:#fff; color:#c00; border:2px solid #c00; }
        .btn-apagar:hover { background:#c00; color:#fff; }
    </style>
</head>
<body>
    <header>
        <div class="logo">Crypher.IA</div>
        <nav>
            <a href="home.php">home</a>
            <a href="logout.php">sair</a>
        </nav>
    </header>

    <main>
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
    </main>

    <script>
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
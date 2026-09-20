<?php
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../controllers/auth.php';
require __DIR__ . '/../controllers/user.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

verificacao_L();

$sql = "SELECT nome, email FROM usuarios WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':id', $_SESSION['id'], PDO::PARAM_INT);
$stmt->execute();
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - Crypher.IA</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { max-width: 700px; margin: 40px auto; padding: 0 20px; }
        header a { margin-right: 20px; text-decoration: none; color: #4348D9; font-weight: 600; }

        .abas { display: flex; gap: 10px; margin: 24px 0 16px; border-bottom: 1px solid #ddd; }
        .aba-btn { padding: 10px 18px; border: none; background: none; cursor: pointer; font-weight: 600; color: #888; border-bottom: 3px solid transparent; font-size: 1em; }
        .aba-btn.ativa { color: #222; border-bottom-color: #F3BE27; }

        .painel-aba { display: none; }
        .painel-aba.ativa { display: block; }

        .campo { margin-bottom: 14px; }
        .campo label { display: block; font-weight: 600; margin-bottom: 4px; font-size: 0.9em; }
        .campo input { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc; font-family: inherit; font-size: 0.95em; }
        .campo input:disabled { background: #f7f7f7; color: #666; }
        .campo small { display: block; margin-top: 4px; color: #888; font-size: 0.78em; }

        .secao { margin-bottom: 26px; }
        .secao h3 { margin-bottom: 12px; font-size: 1.05em; }

        .btn-salvar { background: #F3BE27; border: none; padding: 10px 24px; border-radius: 999px; font-weight: 700; cursor: pointer; }
        .btn-salvar:hover { opacity: 0.9; }

        .zona-perigo { margin-top: 10px; padding: 16px; border: 1px solid #d9534f; border-radius: 10px; background: #fff5f5; }
        .zona-perigo h3 { color: #d9534f; margin-bottom: 8px; }
        .zona-perigo p { font-size: 0.9em; margin-bottom: 12px; color: #555; }
        .btn-deletar { background: #d9534f; color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 700; cursor: pointer; }
        .btn-deletar:hover { background: #c9302c; }

        .msg-erro { color: #d9534f; font-size: 0.9em; margin-bottom: 16px; padding: 10px; background: #fff5f5; border-radius: 8px; }
        .msg-sucesso { color: #2e7d32; font-size: 0.9em; margin-bottom: 16px; padding: 10px; background: #f1f8f2; border-radius: 8px; }

        hr { border: none; border-top: 1px solid #eee; margin: 20px 0; }
    </style>
</head>
<body>
    <header>
        <a href="home.php">home</a>
        <a href="painel_api.php">analisar código</a>
    </header>

    <h1>Minha conta</h1>

    <?php if (isset($_GET['sucesso'])): ?>
        <p class="msg-sucesso">Dados atualizados com sucesso!</p>
    <?php endif; ?>
    <?php if (isset($_GET['erro'])): ?>
        <p class="msg-erro"><?= htmlspecialchars($_GET['erro']) ?></p>
    <?php endif; ?>

    <div class="abas">
        <button class="aba-btn ativa" data-aba="perfil">Perfil</button>
        <button class="aba-btn" data-aba="config">Configurações</button>
    </div>

    <div class="painel-aba ativa" id="aba-perfil">
        <div class="campo">
            <label>Nome</label>
            <input type="text" value="<?= htmlspecialchars($usuario['nome'] ?? '') ?>" disabled>
        </div>
        <div class="campo">
            <label>E-mail</label>
            <input type="email" value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" disabled>
        </div>
    </div>

    <div class="painel-aba" id="aba-config">
        <form method="POST" action="atualizar_perfil.php">
            <div class="secao">
                <h3>Dados da conta</h3>

                <div class="campo">
                    <label for="nome">Nome</label>
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($usuario['nome'] ?? '') ?>" required>
                </div>

                <div class="campo">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($usuario['email'] ?? '') ?>" required>
                </div>
            </div>

            <hr>

            <div class="secao">
                <h3>Alterar senha</h3>
                <p style="font-size:0.85em; color:#888; margin-bottom:12px;">Deixe os campos abaixo em branco se não quiser trocar a senha agora.</p>

                <div class="campo">
                    <label for="senha_atual">Senha atual</label>
                    <input type="password" id="senha_atual" name="senha_atual" autocomplete="current-password">
                    <small>Obrigatória apenas se você for definir uma nova senha.</small>
                </div>

                <div class="campo">
                    <label for="nova_senha">Nova senha</label>
                    <input type="password" id="nova_senha" name="nova_senha" autocomplete="new-password" minlength="6">
                </div>

                <div class="campo">
                    <label for="confirmar_senha">Confirmar nova senha</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" autocomplete="new-password" minlength="6">
                </div>
            </div>

            <button type="submit" class="btn-salvar">Salvar alterações</button>
        </form>

        <hr>

        <div class="zona-perigo">
            <h3>Excluir conta</h3>
            <p>Essa ação apaga seu usuário e todo o histórico de conversas e análises permanentemente. Não tem como desfazer.</p>
            <form method="POST" action="deletar_conta.php" onsubmit="return confirm('Tem certeza que quer excluir sua conta? Essa ação não pode ser desfeita.');">
                <input type="hidden" name="confirmar" value="sim">
                <button type="submit" class="btn-deletar">Excluir minha conta</button>
            </form>
        </div>
    </div>

    <script>
    document.querySelectorAll('.aba-btn').forEach(botao => {
        botao.addEventListener('click', () => {
            document.querySelectorAll('.aba-btn').forEach(b => b.classList.remove('ativa'));
            document.querySelectorAll('.painel-aba').forEach(p => p.classList.remove('ativa'));

            botao.classList.add('ativa');
            document.getElementById('aba-' + botao.dataset.aba).classList.add('ativa');
        });
    });

    // Se a URL veio com ?erro= ou ?sucesso= (ex: após salvar), já abre direto na aba Configurações
    if (window.location.search.includes('erro=') || window.location.search.includes('sucesso=')) {
        document.querySelector('.aba-btn[data-aba="config"]').click();
    }
    </script>
</body>
</html>
<?php
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../controllers/auth.php';
require __DIR__ . '/../controllers/user.php';
require __DIR__ . '/../controllers/analises.php';
require __DIR__ . '/../controllers/conversas.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

verificacao_L();

$usuarioId = (int) $_SESSION['id'];
$conversas = buscarConversas($usuarioId);

$conversaId = isset($_GET['conversa']) ? (int) $_GET['conversa'] : null;

if ($conversaId === null || !conversaPertenceAoUsuario($conversaId, $usuarioId)) {
    if (count($conversas) > 0) {
        $conversaId = (int) $conversas[0]['id'];
    } else {
        $conversaId = criarConversa($usuarioId);
        $conversas = buscarConversas($usuarioId);
    }
}

$historico = buscarAnalisesPorConversa($conversaId);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisar código - Crypher.IA</title>
    <style>
        body { font-family: 'Poppins', sans-serif; max-width: 1100px; margin: 40px auto; padding: 0 20px; }

        .layout { display: flex; gap: 16px; height: 78vh; }

        .sidebar { width: 240px; flex-shrink: 0; border: 1px solid #ddd; border-radius: 14px; overflow-y: auto; background: #fff; padding: 10px; display: flex; flex-direction: column; gap: 6px; }
        .btn-nova-conversa { width: 100%; padding: 10px; border-radius: 8px; border: none; background: #F3BE27; font-weight: 700; cursor: pointer; margin-bottom: 4px; }
        .conversa-item { display: block; padding: 10px; border-radius: 8px; text-decoration: none; color: #222; }
        .conversa-item:hover { background: #f2f2f2; }
        .conversa-item.ativa { background: #EDE7DE; font-weight: 600; }
        .conversa-titulo { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 0.88em; }
        .conversa-data { display: block; font-size: 0.72em; color: #888; margin-top: 2px; }

        .chat-card { flex: 1; display: flex; flex-direction: column; border: 1px solid #ddd; border-radius: 14px; overflow: hidden; background: #EDE7DE; min-width: 0; }

        .chat { flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 12px; }

        .bubble-user { align-self: flex-end; background: #F3BE27; color: #222; padding: 10px 14px; border-radius: 14px 14px 2px 14px; max-width: 78%; white-space: pre-wrap; font-family: monospace; font-size: 13px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
        .bubble-ia   { align-self: flex-start; background: #fff; padding: 12px 14px; border-radius: 14px 14px 14px 2px; max-width: 88%; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }

        .badge-linguagem { display: inline-block; background: #333; color: #fff; font-size: 0.75em; padding: 2px 8px; border-radius: 999px; margin-bottom: 6px; }
        .resumo { margin: 6px 0 10px; font-size: 0.9em; }

        .card-vuln { border-left: 4px solid #ccc; background: #fafafa; padding: 8px 12px; border-radius: 6px; margin-bottom: 8px; }
        .card-vuln h4 { margin: 0 0 4px; font-size: 0.9em; }
        .card-vuln p { margin: 2px 0; font-size: 0.82em; }
        .sev-alta   { border-left-color: #d9534f; }
        .sev-media  { border-left-color: #f0ad4e; }
        .sev-baixa  { border-left-color: #5cb85c; }
        .tag-sev { font-size: 0.68em; text-transform: uppercase; font-weight: 700; padding: 1px 6px; border-radius: 4px; color: #fff; }
        .tag-alta  { background: #d9534f; }
        .tag-media { background: #f0ad4e; }
        .tag-baixa { background: #5cb85c; }

        .loading-bar { height: 3px; width: 100%; background: linear-gradient(90deg, #F3BE27 0%, #fff 50%, #F3BE27 100%); background-size: 200% 100%; animation: carregando 1.2s linear infinite; display: none; }
        @keyframes carregando { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }

        .input-bar { display: flex; align-items: flex-end; gap: 8px; padding: 10px; background: #fff; border-top: 1px solid #ddd; }
        .input-bar textarea { flex: 1; resize: none; min-height: 22px; max-height: 110px; padding: 10px 14px; border-radius: 20px; border: 1px solid #ccc; font-family: inherit; font-size: 14px; line-height: 1.3; }
        .input-bar input[type=file] { max-width: 120px; font-size: 0.72em; }
        .input-bar button { padding: 10px 20px; border-radius: 999px; border: none; background: #F3BE27; font-weight: 700; cursor: pointer; white-space: nowrap; }

        #status { font-size: 0.85em; margin: 6px 0; color: #c00; }
    </style>
</head>
<body>
    <header>
        <a href="home.php">home</a>
        <a href="perfil.php">perfil</a>
    </header>

    <h1>Analisar código</h1>

    <div class="layout">
        <aside class="sidebar">
            <form method="POST" action="nova_conversa.php">
                <button type="submit" class="btn-nova-conversa">+ Nova conversa</button>
            </form>

            <?php foreach ($conversas as $c): ?>
                <a href="?conversa=<?= (int) $c['id'] ?>" class="conversa-item <?= ((int)$c['id'] === $conversaId) ? 'ativa' : '' ?>">
                    <span class="conversa-titulo"><?= htmlspecialchars($c['titulo']) ?></span>
                    <span class="conversa-data"><?= htmlspecialchars($c['created_at']) ?></span>
                </a>
            <?php endforeach; ?>
        </aside>

        <div class="chat-card">
            <div class="chat" id="chat">
                <?php foreach ($historico as $item): ?>
                    <?php $r = json_decode($item['resultado_json'], true) ?: ['linguagem' => null, 'resumo' => '', 'vulnerabilidades' => []]; ?>

                    <div class="bubble-user"><?= htmlspecialchars($item['entrada_texto']) ?></div>

                    <div class="bubble-ia">
                        <?php if (!empty($r['linguagem'])): ?>
                            <span class="badge-linguagem"><?= htmlspecialchars($r['linguagem']) ?></span>
                        <?php endif; ?>
                        <p class="resumo"><?= htmlspecialchars($r['resumo'] ?? '') ?></p>

                        <?php foreach (($r['vulnerabilidades'] ?? []) as $v): ?>
                            <?php $sev = strtolower($v['severidade'] ?? 'baixa'); ?>
                            <div class="card-vuln sev-<?= htmlspecialchars($sev) ?>">
                                <h4><?= htmlspecialchars($v['titulo'] ?? '') ?> <span class="tag-sev tag-<?= htmlspecialchars($sev) ?>"><?= htmlspecialchars($sev) ?></span></h4>
                                <p><?= htmlspecialchars($v['explicacao'] ?? '') ?></p>
                                <p><strong>Sugestão:</strong> <?= htmlspecialchars($v['sugestao'] ?? '') ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="loading-bar" id="loading-bar"></div>

            <form id="form-analise" class="input-bar" enctype="multipart/form-data">
                <input type="hidden" name="conversa_id" value="<?= $conversaId ?>">
                <textarea id="texto" name="texto" placeholder="Cole seu código aqui..." rows="1"></textarea>
                <input type="file" name="arquivo" id="arquivo" accept=".php,.js,.py,.sql,.html,.css,.txt,.json,.zip">
                <button type="submit">Enviar</button>
            </form>
        </div>
    </div>

    <div id="status"></div>

    <script>
    (() => {
        const chat = document.getElementById('chat');
        const form = document.getElementById('form-analise');
        const textarea = document.getElementById('texto');
        const inputArquivo = document.getElementById('arquivo');
        const statusEl = document.getElementById('status');
        const loadingBar = document.getElementById('loading-bar');

        textarea.addEventListener('input', () => {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 110) + 'px';
        });

        function escapar(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function irParaFinal() {
            chat.scrollTop = chat.scrollHeight;
        }

        function renderizarBubbleUser(texto) {
            const div = document.createElement('div');
            div.className = 'bubble-user';
            div.textContent = texto;
            chat.appendChild(div);
        }

        function renderizarBubbleIA(resultado) {
            const div = document.createElement('div');
            div.className = 'bubble-ia';

            let html = '';
            if (resultado.linguagem) {
                html += `<span class="badge-linguagem">${escapar(resultado.linguagem)}</span>`;
            }
            html += `<p class="resumo">${escapar(resultado.resumo || '')}</p>`;

            (resultado.vulnerabilidades || []).forEach(v => {
                const sev = (v.severidade || 'baixa').toLowerCase();
                html += `
                    <div class="card-vuln sev-${sev}">
                        <h4>${escapar(v.titulo || '')} <span class="tag-sev tag-${sev}">${sev}</span></h4>
                        <p>${escapar(v.explicacao || '')}</p>
                        <p><strong>Sugestão:</strong> ${escapar(v.sugestao || '')}</p>
                    </div>
                `;
            });

            div.innerHTML = html;
            chat.appendChild(div);
        }

        form.addEventListener('submit', async (evento) => {
            evento.preventDefault();

            const codigo = textarea.value.trim();
            const arquivo = inputArquivo.files[0];
            if (codigo === '' && !arquivo) return;

            statusEl.textContent = '';
            loadingBar.style.display = 'block';

            const entradaExibida = codigo !== '' ? codigo : ('Arquivo enviado: ' + arquivo.name);
            renderizarBubbleUser(entradaExibida);
            irParaFinal();

            try {
                const corpo = new FormData(form); // já inclui texto, arquivo e conversa_id automaticamente

                const resp = await fetch('analisar_codigo.php', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body: corpo
                });

                const dados = await resp.json();

                if (dados.sucesso) {
                    renderizarBubbleIA(dados.resultado);
                    textarea.value = '';
                    textarea.style.height = 'auto';
                    inputArquivo.value = '';
                } else {
                    statusEl.textContent = 'Erro: ' + dados.erro;
                }
            } catch (erro) {
                statusEl.textContent = 'Erro de conexão: ' + erro.message;
            } finally {
                loadingBar.style.display = 'none';
                irParaFinal();
            }
        });

        irParaFinal();
    })();
    </script>
</body>
</html>
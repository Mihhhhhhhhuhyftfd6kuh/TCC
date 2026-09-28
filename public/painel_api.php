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

// Resumo de cada conversa pra mostrar como prévia na sidebar (1 -- sidebar mais rica)
function resumoConversa(array $c): string {
    $titulo = trim($c['titulo'] ?? '');
    if ($titulo === '' || $titulo === 'Nova conversa') {
        return 'Sem mensagens ainda';
    }
    return $titulo;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisar código - Crypher.IA</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }

        body { background:#ECECEC; min-height:100vh; }

        /* ===== 1) Header consistente com o resto do site ===== */
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

        main { max-width:1140px; margin:0 auto; padding:35px 20px; }

        .titulo-pagina h1 { color:#222; margin-bottom:6px; }
        .titulo-pagina p  { color:#666; font-size:.95rem; margin-bottom:25px; }

        .layout { display: flex; gap: 16px; height: 74vh; }

        /* ===== 3) Sidebar mais rica ===== */
        .sidebar { width: 260px; flex-shrink: 0; border: 1px solid #ddd; border-radius: 14px; overflow-y: auto; background: #fff; padding: 10px; display: flex; flex-direction: column; gap: 6px; }

        .btn-nova-conversa {
            width: 100%; padding: 12px; border-radius: 10px; border: none;
            background: #F3BE27; font-weight: 700; cursor: pointer; margin-bottom: 6px;
            display:flex; align-items:center; justify-content:center; gap:8px;
        }
        .btn-nova-conversa:hover { filter: brightness(0.97); }

        .conversa-item {
            display: flex;
            align-items:flex-start;
            gap:10px;
            padding: 10px;
            border-radius: 10px;
            text-decoration: none;
            color: #222;
            border-left: 3px solid transparent;
        }
        .conversa-item:hover { background: #f2f2f2; }
        .conversa-item.ativa {
            background: #EDE7DE;
            border-left: 3px solid #4348D9;
        }

        .conversa-icone {
            width:30px; height:30px; border-radius:50%;
            background:#4348D9; color:#fff;
            display:flex; align-items:center; justify-content:center;
            font-size:.8rem; flex-shrink:0;
        }

        .conversa-texto { min-width:0; }
        .conversa-titulo { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 0.88em; font-weight:600; }
        .conversa-data   { display: block; font-size: 0.72em; color: #888; margin-top: 2px; }

        .sidebar-vazia { padding:20px 10px; text-align:center; color:#999; font-size:.85em; }

        /* ===== card do chat + marca d'água (5) ===== */
        .chat-card {
            flex: 1; display: flex; flex-direction: column;
            border: 1px solid #ddd; border-radius: 14px; overflow: hidden;
            background: #EDE7DE; min-width: 0;
            position: relative;
        }

        .chat {
            flex: 1; overflow-y: auto; padding: 16px;
            display: flex; flex-direction: column; gap: 12px;
            position: relative;
            z-index: 1;
        }

        /* Marca d'água do mascote, atrás das mensagens */
        .chat-marca-dagua {
    position: absolute;
    inset: 0;
    background-image: url('../assets/img/mascote.jpeg');
    background-repeat: no-repeat;
    background-position: center;
    background-size: 260px auto;
    opacity: 0.25;
    pointer-events: none;
    z-index: 0;
}

        /* ===== 2) Estado vazio ===== */
        .chat-vazio {
            position: relative;
            z-index: 1;
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 12px;
            color: #777;
            padding: 20px;
        }
        .chat-vazio i { font-size: 2.6rem; color: #4348D9; opacity:.6; }
        .chat-vazio h3 { color:#444; font-size:1.05rem; }
        .chat-vazio p { font-size:.88rem; max-width:320px; }

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

        /* ===== 5) Feedback de carregamento mais visível ===== */
        .loading-status {
            display:none;
            align-items:center;
            gap:10px;
            padding:8px 16px;
            background:#fff;
            border-top:1px solid #ddd;
            font-size:.85em;
            color:#555;
            position: relative;
            z-index: 1;
        }
        .loading-status .spinner {
            width:16px; height:16px;
            border:2px solid #ddd;
            border-top-color:#4348D9;
            border-radius:50%;
            animation: girar .7s linear infinite;
        }
        @keyframes girar { to { transform: rotate(360deg); } }

        .input-bar { display: flex; align-items: flex-end; gap: 8px; padding: 10px; background: #fff; border-top: 1px solid #ddd; position: relative; z-index: 1; }
        .input-bar textarea { flex: 1; resize: none; min-height: 22px; max-height: 110px; padding: 10px 14px; border-radius: 20px; border: 1px solid #ccc; font-family: inherit; font-size: 14px; line-height: 1.3; }

        /* ===== 4) Input de arquivo estilizado com ícone de upload ===== */
        .input-bar input[type=file] {
            position: absolute;
            width: 1px; height: 1px;
            opacity: 0;
            overflow: hidden;
        }

        .btn-anexo {
            width: 42px; height: 42px;
            flex-shrink: 0;
            border-radius: 50%;
            background: #fff;
            border: 1px solid #ccc;
            color: #4348D9;
            font-size: 1.1rem;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            transition: .2s ease;
            position: relative;
        }
        .btn-anexo:hover { background:#f2f2f2; }
        .btn-anexo.tem-arquivo { background:#4348D9; color:#fff; border-color:#4348D9; }

        .nome-arquivo {
            font-size: .72em;
            color: #666;
            max-width: 110px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            align-self: center;
        }

        .input-bar button[type=submit] { padding: 10px 20px; border-radius: 999px; border: none; background: #F3BE27; font-weight: 700; cursor: pointer; white-space: nowrap; }

        #status { font-size: 0.85em; margin: 6px 0; color: #c00; }
    </style>
</head>
<body>
    <header>
        <div class="logo">Crypher.IA</div>
        <nav>
            <a href="home.php">home</a>
            <a href="perfil.php">perfil</a>
            <a href="logout.php">sair</a>
        </nav>
    </header>

    <main>
        <div class="titulo-pagina">
            <h1>Analisar código</h1>
            <p>Cole seu código ou envie um arquivo e a IA aponta vulnerabilidades e sugestões de correção.</p>
        </div>

        <div class="layout">
            <aside class="sidebar">
                <form method="POST" action="nova_conversa.php">
                    <button type="submit" class="btn-nova-conversa">
                        <i class="fa-solid fa-plus"></i> Nova conversa
                    </button>
                </form>

                <?php if (count($conversas) === 0): ?>
                    <div class="sidebar-vazia">Nenhuma conversa ainda.</div>
                <?php endif; ?>

                <?php foreach ($conversas as $c): ?>
                    <a href="?conversa=<?= (int) $c['id'] ?>" class="conversa-item <?= ((int)$c['id'] === $conversaId) ? 'ativa' : '' ?>">
                        <span class="conversa-icone"><i class="fa-solid fa-message"></i></span>
                        <span class="conversa-texto">
                            <span class="conversa-titulo"><?= htmlspecialchars(resumoConversa($c)) ?></span>
                            <span class="conversa-data"><?= htmlspecialchars($c['created_at']) ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </aside>

            <div class="chat-card">
                <div class="chat-marca-dagua"></div>

                <?php if (count($historico) === 0): ?>
                    <div class="chat-vazio">
                        <i class="fa-solid fa-shield-halved"></i>
                        <h3>Nenhuma análise por aqui ainda</h3>
                        <p>Cole um trecho de código ou envie um arquivo abaixo para a IA começar a procurar vulnerabilidades.</p>
                    </div>
                <?php else: ?>
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
                <?php endif; ?>

                <div class="loading-status" id="loading-status">
                    <span class="spinner"></span>
                    <span>Analisando código...</span>
                </div>

                <form id="form-analise" class="input-bar" enctype="multipart/form-data">
                    <input type="hidden" name="conversa_id" value="<?= $conversaId ?>">

                    <label class="btn-anexo" id="btn-anexo" title="Anexar arquivo">
                        <i class="fa-solid fa-upload" id="icone-anexo"></i>
                        <input type="file" name="arquivo" id="arquivo" accept=".php,.js,.py,.sql,.html,.css,.txt,.json,.zip">
                    </label>

                    <span class="nome-arquivo" id="nome-arquivo"></span>

                    <textarea id="texto" name="texto" placeholder="Cole seu código aqui..." rows="1"></textarea>
                    <button type="submit">Enviar</button>
                </form>
            </div>
        </div>

        <div id="status"></div>
    </main>

    <script>
    (() => {
        const chatCard = document.querySelector('.chat-card');
        let chat = document.getElementById('chat');
        const form = document.getElementById('form-analise');
        const textarea = document.getElementById('texto');
        const inputArquivo = document.getElementById('arquivo');
        const btnAnexo = document.getElementById('btn-anexo');
        const nomeArquivoEl = document.getElementById('nome-arquivo');
        const statusEl = document.getElementById('status');
        const loadingStatus = document.getElementById('loading-status');

        textarea.addEventListener('input', () => {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 110) + 'px';
        });

        // 4 -- mostra o nome do arquivo escolhido e destaca o ícone
        inputArquivo.addEventListener('change', () => {
            const arquivo = inputArquivo.files[0];
            if (arquivo) {
                nomeArquivoEl.textContent = arquivo.name;
                btnAnexo.classList.add('tem-arquivo');
            } else {
                nomeArquivoEl.textContent = '';
                btnAnexo.classList.remove('tem-arquivo');
            }
        });

        function escapar(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function garantirChat() {
            // Se ainda estava no estado vazio, cria o container de mensagens agora
            if (!chat) {
                const vazio = chatCard.querySelector('.chat-vazio');
                if (vazio) vazio.remove();

                chat = document.createElement('div');
                chat.className = 'chat';
                chat.id = 'chat';
                chatCard.insertBefore(chat, loadingStatus);
            }
        }

        function irParaFinal() {
            if (chat) chat.scrollTop = chat.scrollHeight;
        }

        function renderizarBubbleUser(texto) {
            garantirChat();
            const div = document.createElement('div');
            div.className = 'bubble-user';
            div.textContent = texto;
            chat.appendChild(div);
        }

        function renderizarBubbleIA(resultado) {
            garantirChat();
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
            loadingStatus.style.display = 'flex'; // 5 -- feedback visível durante a análise

            const entradaExibida = codigo !== '' ? codigo : ('Arquivo enviado: ' + arquivo.name);
            renderizarBubbleUser(entradaExibida);
            irParaFinal();

            try {
                const corpo = new FormData(form);

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
                    nomeArquivoEl.textContent = '';
                    btnAnexo.classList.remove('tem-arquivo');
                } else {
                    statusEl.textContent = 'Erro: ' + dados.erro;
                }
            } catch (erro) {
                statusEl.textContent = 'Erro de conexão: ' + erro.message;
            } finally {
                loadingStatus.style.display = 'none';
                irParaFinal();
            }
        });

        irParaFinal();
    })();
    </script>
</body>
</html>
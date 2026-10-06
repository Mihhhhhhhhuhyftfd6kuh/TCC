<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Lê uma variável de ambiente. O phpdotenv só preenche $_ENV/$_SERVER (não
 * usa putenv), então getenv() sozinho não enxerga valores vindos do .env.
 * Aqui olhamos $_ENV primeiro e caímos pro getenv() (ex: ENV do Docker/Render).
 */
function lerEnv(string $nome): ?string {
    $valor = $_ENV[$nome] ?? $_SERVER[$nome] ?? getenv($nome);
    return ($valor === false || $valor === '') ? null : (string) $valor;
}

function chamarIA(string $texto, ?string $arquivoConteudo = null, ?string $arquivoNome = null, array $historico = []): array {
    // Modo de teste: com IA_MOCK=true no .env, devolve uma resposta de
    // exemplo na hora, sem chamar a FastAPI nem a API da Anthropic.
    // Útil pra testar upload/histórico/UI sem gastar token nenhum.
    if (filter_var(lerEnv('IA_MOCK'), FILTER_VALIDATE_BOOLEAN)) {
        return ['sucesso' => true, 'resultado' => mockResultadoIA($texto, $arquivoNome)];
    }

    $urlApi = lerEnv('IA_API_URL') ?: 'http://localhost:8000/analisar';

    $payload = [
        'texto'            => $texto,
        'arquivo_conteudo' => $arquivoConteudo,
        'arquivo_nome'     => $arquivoNome,
        'historico'        => $historico,
    ];

    $ch = curl_init($urlApi);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);

    $resposta = curl_exec($ch);
    $erroCurl = curl_error($ch);
    curl_close($ch);

    if ($erroCurl) {
        return ['sucesso' => false, 'erro' => 'Não foi possível conectar ao serviço de IA: ' . $erroCurl];
    }

    $dados = json_decode($resposta, true);

    if (!isset($dados['resultado'])) {
        return ['sucesso' => false, 'erro' => 'Resposta inesperada do serviço de IA'];
    }

    return ['sucesso' => true, 'resultado' => $dados['resultado']];
}

/**
 * Resposta de exemplo usada quando IA_MOCK=true. Mantém o mesmo formato
 * que a IA de verdade devolve (linguagem, resumo, vulnerabilidades),
 * pra não quebrar nada na hora de renderizar no chat.
 */
function mockResultadoIA(string $texto, ?string $arquivoNome): array {
    $resultado = [
        'linguagem' => $arquivoNome ? strtoupper(pathinfo($arquivoNome, PATHINFO_EXTENSION)) : 'PHP',
        'resumo' => '[MODO TESTE] Essa é uma resposta de exemplo — IA_MOCK está ativo, então a API da Anthropic não foi chamada e nenhum token foi gasto.',
        'vulnerabilidades' => [
            [
                'titulo'     => 'SQL Injection (exemplo)',
                'severidade' => 'alta',
                'explicacao' => 'Vulnerabilidade de exemplo, só pra testar como um card de severidade alta aparece na tela.',
                'sugestao'   => 'Use prepared statements com parâmetros nomeados.',
            ],
            [
                'titulo'     => 'Validação de entrada (exemplo)',
                'severidade' => 'media',
                'explicacao' => 'Outro item de exemplo, pra testar o card de severidade média.',
                'sugestao'   => 'Valide e sanitize tudo que vier do usuário antes de usar.',
            ],
        ],
    ];

    // Se o usuário pediu o código corrigido, simula também esse campo
    if (preg_match('/corrig/i', $texto)) {
        $resultado['codigo_corrigido'] = "<?php\n// [MODO TESTE] código corrigido de exemplo\n\$stmt = \$pdo->prepare('SELECT * FROM usuarios WHERE id = :id');\n\$stmt->bindValue(':id', \$id, PDO::PARAM_INT);\n\$stmt->execute();\n";
    }

    return $resultado;
}
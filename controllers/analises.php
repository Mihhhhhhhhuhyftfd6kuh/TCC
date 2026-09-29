<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function salvarAnalise(int $usuarioId, int $conversaId, string $entradaTexto, ?string $arquivoNome, ?string $linguagem, string $resultadoJson): bool {
    require __DIR__ . "/../config/config.php";

    $sql = "INSERT INTO analises (usuario_id, conversa_id, entrada_texto, arquivo_nome, linguagem, resultado_json, created_at)
            VALUES (:usuario_id, :conversa_id, :entrada_texto, :arquivo_nome, :linguagem, :resultado_json, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->bindParam(':conversa_id', $conversaId, PDO::PARAM_INT);
    $stmt->bindParam(':entrada_texto', $entradaTexto);
    $stmt->bindParam(':arquivo_nome', $arquivoNome);
    $stmt->bindParam(':linguagem', $linguagem);
    $stmt->bindParam(':resultado_json', $resultadoJson);

    try {
        $stmt->execute();
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao salvar análise: " . $e->getMessage());
        return false;
    }
}

function buscarAnalisesPorConversa(int $conversaId): array {
    require __DIR__ . "/../config/config.php";

    $sql = "SELECT * FROM analises WHERE conversa_id = :conversa_id ORDER BY created_at ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':conversa_id', $conversaId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Converte as análises já salvas em mensagens (user/assistant) para a IA lembrar da conversa.
// $limite = quantas trocas (pergunta + resposta) mais recentes enviar.
function montarHistoricoParaIA(array $analises, int $limite = 10): array {
    $historico = [];

    foreach (array_slice($analises, -$limite) as $a) {
        $pergunta = trim((string) ($a['entrada_texto'] ?? ''));
        $resposta = trim((string) ($a['resultado_json'] ?? ''));

        // A API rejeita mensagens vazias, então pares incompletos são ignorados
        if ($pergunta === '' || $resposta === '') {
            continue;
        }

        $historico[] = ['role' => 'user',      'content' => mb_substr($pergunta, 0, 8000)];
        $historico[] = ['role' => 'assistant', 'content' => $resposta];
    }

    return $historico;
}

function apagarAnalises(int $usuarioId): bool {
    require __DIR__ . "/../config/config.php";

    try {
        $pdo->beginTransaction();

        $stmt1 = $pdo->prepare("DELETE FROM analises WHERE usuario_id = :usuario_id");
        $stmt1->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt1->execute();

        $stmt2 = $pdo->prepare("DELETE FROM conversas WHERE usuario_id = :usuario_id");
        $stmt2->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt2->execute();

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Erro ao apagar histórico de análises: " . $e->getMessage());
        return false;
    }
}
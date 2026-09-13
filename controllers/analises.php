<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function salvarAnalise(int $usuarioId, string $entradaTexto, ?string $arquivoNome, ?string $linguagem, string $resultadoJson): bool {
    require __DIR__ . "/../config/config.php";

    $sql = "INSERT INTO analises (usuario_id, entrada_texto, arquivo_nome, linguagem, resultado_json, created_at)
            VALUES (:usuario_id, :entrada_texto, :arquivo_nome, :linguagem, :resultado_json, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
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

function buscarAnalises(int $usuarioId): array {
    require __DIR__ . "/../config/config.php";

    $sql = "SELECT * FROM analises WHERE usuario_id = :usuario_id ORDER BY created_at ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
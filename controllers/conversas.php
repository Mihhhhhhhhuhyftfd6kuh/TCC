<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function criarConversa(int $usuarioId, string $titulo = 'Nova conversa'): ?int {
    require __DIR__ . "/../config/config.php";

    $sql = "INSERT INTO conversas (usuario_id, titulo, created_at) VALUES (:usuario_id, :titulo, NOW()) RETURNING id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->bindParam(':titulo', $titulo);

    try {
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Erro ao criar conversa: " . $e->getMessage());
        return null;
    }
}

function buscarConversas(int $usuarioId): array {
    require __DIR__ . "/../config/config.php";

    $sql = "SELECT * FROM conversas WHERE usuario_id = :usuario_id ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function conversaPertenceAoUsuario(int $conversaId, int $usuarioId): bool {
    require __DIR__ . "/../config/config.php";

    $sql = "SELECT COUNT(*) FROM conversas WHERE id = :id AND usuario_id = :usuario_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $conversaId, PDO::PARAM_INT);
    $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchColumn() > 0;
}

function atualizarTituloConversa(int $conversaId, string $titulo): void {
    require __DIR__ . "/../config/config.php";

    $sql = "UPDATE conversas SET titulo = :titulo WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':titulo', $titulo);
    $stmt->bindParam(':id', $conversaId, PDO::PARAM_INT);
    $stmt->execute();
}
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function alterar(string $nome, string $email, ?string $senhaAtual = null, ?string $novaSenha = null): array {
    require __DIR__ . '/../config/config.php';

    $id = $_SESSION['id'] ?? null;

    if ($id === null) {
        return ['sucesso' => false, 'erro' => 'Sessão inválida'];
    }

    $nome = trim($nome);
    $email = trim($email);

    if ($nome === '' || $email === '') {
        return ['sucesso' => false, 'erro' => 'Nome e e-mail não podem ficar em branco'];
    }

    // Verifica se o e-mail já está em uso por outro usuário
    $sqlCheck = "SELECT COUNT(*) FROM usuarios WHERE email = :email AND id != :id";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->bindValue(':email', $email);
    $stmtCheck->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtCheck->execute();

    if ($stmtCheck->fetchColumn() > 0) {
        return ['sucesso' => false, 'erro' => 'Esse e-mail já está sendo usado por outra conta'];
    }

    $campos = "nome = :nome, email = :email";
    $params = [':nome' => $nome, ':email' => $email, ':id' => $id];

    // Só mexe na senha se o usuário realmente preencheu uma nova
    if ($novaSenha !== null && $novaSenha !== '') {
        $sqlSenha = "SELECT senha FROM usuarios WHERE id = :id";
        $stmtSenha = $pdo->prepare($sqlSenha);
        $stmtSenha->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtSenha->execute();
        $usuario = $stmtSenha->fetch(PDO::FETCH_ASSOC);

        if (!$usuario || !password_verify((string) $senhaAtual, $usuario['senha'])) {
            return ['sucesso' => false, 'erro' => 'Senha atual incorreta'];
        }

        if (strlen($novaSenha) < 6) {
            return ['sucesso' => false, 'erro' => 'A nova senha precisa ter pelo menos 6 caracteres'];
        }

        $campos .= ", senha = :senha";
        $params[':senha'] = password_hash($novaSenha, PASSWORD_DEFAULT);
    }

    $sql = "UPDATE usuarios SET {$campos} WHERE id = :id";
    $stmt = $pdo->prepare($sql);

    try {
        $stmt->execute($params);
        return ['sucesso' => true];
    } catch (PDOException $e) {
        error_log("Erro ao atualizar usuário: " . $e->getMessage());
        return ['sucesso' => false, 'erro' => 'Não foi possível salvar as alterações'];
    }
}

function deletar(): bool {
    require __DIR__ . '/../config/config.php';

    $id = $_SESSION['id'] ?? null;

    if ($id === null) {
        return false;
    }

    $sql = "DELETE FROM usuarios WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);

    try {
        $stmt->execute();
        session_unset();
        session_destroy();
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao deletar usuário: " . $e->getMessage());
        return false;
    }
}

function logout(){
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_unset();
        session_destroy();
    }
    // redireciona para a página pública de home
    header("Location: ../public/home.php");
    exit();
}


function verificacao_L(){
    require __DIR__ . '/../config/config.php';

    $id = $_SESSION['id'];
    if($id == null){
        header("location:../public/login.php");
        exit();
    }
}


?>
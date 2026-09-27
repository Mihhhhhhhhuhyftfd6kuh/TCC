<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Atualiza nome, e-mail e (opcionalmente) senha do usuário logado.
 * Exige a senha atual pra confirmar QUALQUER alteração (medida de segurança).
 *
 * Retorna sempre um array: ['sucesso' => bool, 'erro' => string|null]
 */
function alterar($nome, $email, $senhaAtual, $novaSenha = null) {
    require __DIR__ . '/../config/config.php';

    $id = $_SESSION['id'] ?? null;

    if ($id === null) {
        return ['sucesso' => false, 'erro' => 'Sessão expirada. Faça login novamente.'];
    }

    $nome  = trim($nome ?? '');
    $email = trim($email ?? '');

    if ($nome === '' || $email === '') {
        return ['sucesso' => false, 'erro' => 'Nome e e-mail não podem ficar vazios.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['sucesso' => false, 'erro' => 'Digite um e-mail válido.'];
    }

    // Busca o hash da senha atual pra conferir antes de alterar qualquer coisa
    $sql = "SELECT senha FROM usuarios WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        return ['sucesso' => false, 'erro' => 'Usuário não encontrado.'];
    }

    if (empty($senhaAtual) || !password_verify($senhaAtual, $usuario['senha'])) {
        return ['sucesso' => false, 'erro' => 'Senha atual incorreta.'];
    }

    // Não deixa usar um e-mail que já pertence a outra conta
    $sqlEmail = "SELECT COUNT(*) FROM usuarios WHERE email = :email AND id != :id";
    $stmtEmail = $pdo->prepare($sqlEmail);
    $stmtEmail->bindValue(':email', $email);
    $stmtEmail->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtEmail->execute();

    if ($stmtEmail->fetchColumn() > 0) {
        return ['sucesso' => false, 'erro' => 'Esse e-mail já está sendo usado por outra conta.'];
    }

    // Se veio uma nova senha, troca ela também (a checagem de confirmação
    // já é feita antes, em atualizar_perfil.php)
    if (!empty($novaSenha)) {
        if (strlen($novaSenha) < 6) {
            return ['sucesso' => false, 'erro' => 'A nova senha deve ter pelo menos 6 caracteres.'];
        }

        $sql  = "UPDATE usuarios SET nome = :nome, email = :email, senha = :senha WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':senha', password_hash($novaSenha, PASSWORD_DEFAULT));
    } else {
        $sql  = "UPDATE usuarios SET nome = :nome, email = :email WHERE id = :id";
        $stmt = $pdo->prepare($sql);
    }

    $stmt->bindValue(':nome', $nome);
    $stmt->bindValue(':email', $email);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);

    try {
        $stmt->execute();
        return ['sucesso' => true, 'erro' => null];
    } catch (PDOException $e) {
        error_log("Erro ao atualizar perfil: " . $e->getMessage());
        return ['sucesso' => false, 'erro' => 'Não foi possível salvar suas alterações.'];
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
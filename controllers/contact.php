<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}



   function imprimir_com_conversa(){
            require __DIR__ . "/../config/config.php";

    $sql = "SELECT DISTINCT u.id, u.nome, u.img, u.email
            FROM usuarios u
            INNER JOIN mensagens m ON (
                (m.id_remetente = 1 AND m.id_destinatario = u.id) OR
                (m.id_destinatario = 1 AND m.id_remetente = u.id)
            )
            WHERE u.id != 1
            ORDER BY u.nome ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
    
   }

    function imprimir_m(INT $id_usuario){
        require __DIR__ . "/../config/config.php";
        $admin = 1;
        $usuario = $id_usuario;
        if($id_usuario== NULL){
            Echo "USUARIO NAO ACHADO";
            return;
        }else{


        if($_SESSION['id'] == 1){
            $_SESSION['is_admin']= true;

        }else{
            $_SESSION['is_admin'] = false;
        }

        $sql = "SELECT m.*, u.nome
        FROM mensagens m
        JOIN usuarios u ON m.id_remetente = u.id
        WHERE 
        (m.id_remetente = :user AND m.id_destinatario = :admin)
        OR
        (m.id_remetente = :admin AND m.id_destinatario = :user)
        ORDER BY m.created_at ASC";
        $stmt =$pdo->prepare($sql);
        $stmt->bindparam(':user', $usuario);
        $stmt->bindparam(':admin', $admin);
        $stmt->execute();
        return $conversa = $stmt->fetchAll(PDO::FETCH_ASSOC); 

    }}



  function criar($mensagem, $id_remetente, $id_destinatario = null, $arquivoUrl = null, $arquivoNome = null){
    require __DIR__ . "/../config/config.php";

    if(empty(trim($mensagem)) && $arquivoUrl === null){
        return false;
    }

    if($id_destinatario === null){
        if($_SESSION['id'] != 1){
            $id_destinatario = 1;
        } else {
            return false;
        }
    }

    $sql = "INSERT INTO mensagens(mensagem, id_remetente, id_destinatario, arquivo_url, arquivo_nome, created_at) 
            VALUES (:mensagem, :id_remetente, :id_destinatario, :arquivo_url, :arquivo_nome, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":mensagem", $mensagem);
    $stmt->bindParam(":id_remetente", $id_remetente, PDO::PARAM_INT);
    $stmt->bindParam(":id_destinatario", $id_destinatario, PDO::PARAM_INT);
    $stmt->bindParam(":arquivo_url", $arquivoUrl);
    $stmt->bindParam(":arquivo_nome", $arquivoNome);

    try {
        $stmt->execute();
        return true;
    } catch(PDOException $e) {
        error_log("Erro ao inserir mensagem: " . $e->getMessage());
        return false;
    }
}
   
?>

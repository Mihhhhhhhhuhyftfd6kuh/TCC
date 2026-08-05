<?php
/**
 * ENDPOINT AJAX — Busca as mensagens da conversa em formato JSON.
 *
 * O JavaScript de conversa.php chama este arquivo a cada X segundos
 * (polling) para saber se chegaram mensagens novas, sem precisar
 * recarregar a página inteira.
 */

session_start();

require '../../controllers/contact.php';
require '../../config/config.php';
require '../../controllers/user.php';

header('Content-Type: application/json; charset=utf-8');

verificacao_L();

if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autenticado']);
    exit();
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

if ($_SESSION['id'] == 1) {
    if ($id === null) {
        http_response_code(400);
        echo json_encode(['erro' => 'ID de usuário não fornecido']);
        exit();
    }
    $id_usuario = $id;
} else {
    $id_usuario = $_SESSION['id'];
}

$conversa = imprimir_m($id_usuario);

$mensagens = [];
foreach ($conversa as $msg) {
    $mensagens[] = [
        'id'          => $msg['id'],
        'nome'        => htmlspecialchars($msg['nome']),
        'mensagem'    => htmlspecialchars($msg['mensagem']),
        'created_at'  => $msg['created_at'],
        'id_remetente' => (int) $msg['id_remetente'],
        'minha'       => ((int) $msg['id_remetente'] === (int) $_SESSION['id']),
    ];
}

echo json_encode(['sucesso' => true, 'mensagens' => $mensagens]);
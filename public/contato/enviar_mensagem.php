<?php
/**
 * ENDPOINT AJAX — Recebe uma nova mensagem via POST (fetch/AJAX) e a
 * grava no banco usando a função criar() já existente em contact.php.
 *
 * Retorna JSON para que o JavaScript de conversa.php possa atualizar
 * o chat sem recarregar a página.
 */

session_start();

require '../../controllers/contact.php';
require '../../config/config.php';
require '../../controllers/user.php';

header('Content-Type: application/json; charset=utf-8');

verificacao_L();

if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'erro' => 'Não autenticado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'erro' => 'Método não permitido']);
    exit();
}

$mensagem = isset($_POST['mensagem']) ? trim($_POST['mensagem']) : '';
$id_param = isset($_POST['id']) ? (int) $_POST['id'] : null;

if ($mensagem === '') {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'Mensagem não pode estar vazia']);
    exit();
}

$id_remetente = $_SESSION['id'];

if ($id_remetente == 1) {
    // Admin (id 1) precisa informar para qual usuário está respondendo
    if ($id_param === null) {
        http_response_code(400);
        echo json_encode(['sucesso' => false, 'erro' => 'ID do destinatário é obrigatório para o admin']);
        exit();
    }
    $id_destinatario = $id_param;
} else {
    // Usuário comum sempre envia para o admin (id 1)
    $id_destinatario = 1;
}

$ok = criar($mensagem, $id_remetente, $id_destinatario);

if ($ok) {
    echo json_encode(['sucesso' => true]);
} else {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => 'Não foi possível salvar a mensagem']);
}
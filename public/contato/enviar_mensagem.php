<?php
session_start();

require '../../controllers/contact.php';
require '../../controllers/storage.php';
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

$arquivoUrl = null;
$arquivoNome = null;

if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
    if ($_FILES['arquivo']['size'] > 5 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['sucesso' => false, 'erro' => 'Arquivo maior que 5MB']);
        exit();
    }
    $arquivoNome = basename($_FILES['arquivo']['name']);
    $arquivoUrl = uploadArquivo($_FILES['arquivo']['tmp_name'], $arquivoNome);
}

if ($mensagem === '' && $arquivoUrl === null) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'Mensagem não pode estar vazia']);
    exit();
}

$id_remetente = $_SESSION['id'];

if ($id_remetente == 1) {
    if ($id_param === null) {
        http_response_code(400);
        echo json_encode(['sucesso' => false, 'erro' => 'ID do destinatário é obrigatório para o admin']);
        exit();
    }
    $id_destinatario = $id_param;
} else {
    $id_destinatario = 1;
}

$ok = criar($mensagem, $id_remetente, $id_destinatario, $arquivoUrl, $arquivoNome);

if ($ok) {
    echo json_encode(['sucesso' => true]);
} else {
    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => 'Não foi possível salvar a mensagem']);
}
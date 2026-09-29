<?php
session_start();

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../controllers/user.php';
require __DIR__ . '/../controllers/conversas.php';

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

$conversaId = isset($_POST['conversa_id']) ? (int) $_POST['conversa_id'] : 0;
$titulo = trim(preg_replace('/\s+/', ' ', $_POST['titulo'] ?? ''));

if ($conversaId <= 0) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'Conversa inválida']);
    exit();
}

if ($titulo === '') {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'O nome não pode ficar vazio']);
    exit();
}

if (mb_strlen($titulo) > 60) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'O nome pode ter no máximo 60 caracteres']);
    exit();
}

if (!renomearConversa($conversaId, (int) $_SESSION['id'], $titulo)) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'Não foi possível renomear a conversa']);
    exit();
}

echo json_encode(['sucesso' => true, 'titulo' => $titulo]);
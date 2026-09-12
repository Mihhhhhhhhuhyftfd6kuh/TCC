<?php
session_start();

require __DIR__ . '/../controllers/ia.php';
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../controllers/user.php';

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

$texto = isset($_POST['texto']) ? trim($_POST['texto']) : '';

if ($texto === '') {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'Cole algum código antes de enviar']);
    exit();
}

$resultado = chamarIA($texto,$arquivo);

if ($resultado['sucesso']) {
    echo json_encode(['sucesso' => true, 'resultado' => $resultado['resultado']]);
} else {
    http_response_code(502);
    echo json_encode(['sucesso' => false, 'erro' => $resultado['erro']]);
}
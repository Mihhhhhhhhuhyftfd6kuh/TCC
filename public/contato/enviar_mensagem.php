<?php
/**
 * ENDPOINT AJAX — Envia uma mensagem (e, se houver, um arquivo) no chat de contato.
 *
 * Recebe por POST (FormData):
 *   - mensagem : texto (opcional se houver arquivo)
 *   - arquivo  : arquivo anexado (opcional, até 5MB)
 *   - id       : id do usuário destinatário (obrigatório só para o admin)
 *
 * Sempre responde em JSON: {"sucesso": true} ou {"sucesso": false, "erro": "..."}.
 */

session_start();

require '../../controllers/contact.php';
require '../../controllers/storage.php';
require '../../config/config.php';
require '../../controllers/user.php';

header('Content-Type: application/json; charset=utf-8');

const TAMANHO_MAXIMO_ARQUIVO = 5 * 1024 * 1024; // 5MB

/**
 * Envia a resposta em JSON com o status HTTP indicado e encerra o script.
 */
function responder(int $status, array $dados): void
{
    http_response_code($status);
    echo json_encode($dados);
    exit();
}

verificacao_L();

try {

    // ---------------------------------------------------------
    // 1) Quem está enviando e por qual método
    // ---------------------------------------------------------
    if (!isset($_SESSION['id']) || empty($_SESSION['id'])) {
        responder(401, ['sucesso' => false, 'erro' => 'Não autenticado']);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        responder(405, ['sucesso' => false, 'erro' => 'Método não permitido']);
    }

    $id_remetente = (int) $_SESSION['id'];

    // ---------------------------------------------------------
    // 2) Para quem vai a mensagem
    //    - admin (id 1) escolhe o destinatário
    //    - qualquer outro usuário sempre fala com o admin
    // ---------------------------------------------------------
    if ($id_remetente === 1) {
        $id_destinatario = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if ($id_destinatario <= 0) {
            responder(400, ['sucesso' => false, 'erro' => 'ID do destinatário é obrigatório para o admin']);
        }
    } else {
        $id_destinatario = 1;
    }

    // ---------------------------------------------------------
    // 3) Texto da mensagem
    // ---------------------------------------------------------
    $mensagem = isset($_POST['mensagem']) ? trim($_POST['mensagem']) : '';

    // ---------------------------------------------------------
    // 4) Arquivo anexado (se houver)
    // ---------------------------------------------------------
    $arquivoUrl  = null;
    $arquivoNome = null;

    $enviouArquivo = isset($_FILES['arquivo'])
        && $_FILES['arquivo']['error'] !== UPLOAD_ERR_NO_FILE;

    if ($enviouArquivo) {
        $erroUpload = $_FILES['arquivo']['error'];

        // O PHP recusou o arquivo antes de chegar aqui (limite do php.ini)
        if ($erroUpload === UPLOAD_ERR_INI_SIZE || $erroUpload === UPLOAD_ERR_FORM_SIZE) {
            responder(413, ['sucesso' => false, 'erro' => 'Arquivo maior que o limite permitido pelo servidor']);
        }

        if ($erroUpload !== UPLOAD_ERR_OK) {
            responder(400, ['sucesso' => false, 'erro' => 'Falha ao receber o arquivo. Tente novamente.']);
        }

        if ($_FILES['arquivo']['size'] > TAMANHO_MAXIMO_ARQUIVO) {
            responder(400, ['sucesso' => false, 'erro' => 'Arquivo maior que 5MB']);
        }

        // No Render o .env não existe (está no .gitignore): as chaves precisam estar em Environment
        if (!storageConfigurado()) {
            error_log('Contato: variáveis CLOUDINARY_CLOUD_NAME / CLOUDINARY_API_KEY / CLOUDINARY_API_SECRET ausentes.');
            responder(503, [
                'sucesso' => false,
                'erro'    => 'O envio de arquivos não está configurado no servidor (faltam as variáveis CLOUDINARY_* no Render).',
            ]);
        }

        $arquivoNome = basename($_FILES['arquivo']['name']);
        $arquivoUrl  = uploadArquivo($_FILES['arquivo']['tmp_name'], $arquivoNome);

        // Se o upload falhou, avisa em vez de mandar a mensagem sem o arquivo
        if ($arquivoUrl === null) {
            responder(502, ['sucesso' => false, 'erro' => 'Não foi possível enviar o arquivo. Tente novamente em instantes.']);
        }
    }

    // ---------------------------------------------------------
    // 5) Precisa ter texto OU arquivo
    // ---------------------------------------------------------
    if ($mensagem === '' && $arquivoUrl === null) {
        responder(400, ['sucesso' => false, 'erro' => 'Mensagem não pode estar vazia']);
    }

    // ---------------------------------------------------------
    // 6) Salva no banco
    // ---------------------------------------------------------
    $ok = criar($mensagem, $id_remetente, $id_destinatario, $arquivoUrl, $arquivoNome);

    if (!$ok) {
        responder(500, ['sucesso' => false, 'erro' => 'Não foi possível salvar a mensagem']);
    }

    responder(200, ['sucesso' => true]);

} catch (Throwable $e) {
    // Qualquer erro inesperado vira JSON (em vez de uma página de erro em HTML)
    error_log('Erro em enviar_mensagem.php: ' . $e->getMessage());
    responder(500, ['sucesso' => false, 'erro' => 'Erro interno ao enviar a mensagem.']);
}
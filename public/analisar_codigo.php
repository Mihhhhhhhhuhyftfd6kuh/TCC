<?php
session_start();

require __DIR__ . '/../controllers/ia.php';
require __DIR__ . '/../controllers/storage.php';
require __DIR__ . '/../controllers/analises.php';
require __DIR__ . '/../controllers/conversas.php';
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
$arquivoConteudo = null;
$arquivoNome = null;

$extensoesPermitidas = ['php', 'js', 'py', 'sql', 'html', 'css', 'txt', 'json', 'zip'];
$extensoesCodigo     = ['php', 'js', 'py', 'sql', 'html', 'css', 'txt', 'json']; // válidas dentro do zip
$tamanhoMaximoArquivo = 2 * 1024 * 1024;  // 2MB — arquivo solto
$tamanhoMaximoZip     = 5 * 1024 * 1024;  // 5MB — pacote .zip
$limiteArquivosZip    = 15;               // no máx. 15 arquivos lidos de dentro do zip
$limitePorArquivoZip  = 200 * 1024;       // 200KB por arquivo dentro do zip

if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
    $arquivoNome = basename($_FILES['arquivo']['name']);
    $extensao = strtolower(pathinfo($arquivoNome, PATHINFO_EXTENSION));

    if (!in_array($extensao, $extensoesPermitidas)) {
        http_response_code(400);
        echo json_encode(['sucesso' => false, 'erro' => 'Tipo de arquivo não permitido']);
        exit();
    }

    if ($extensao === 'zip') {
        if ($_FILES['arquivo']['size'] > $tamanhoMaximoZip) {
            http_response_code(400);
            echo json_encode(['sucesso' => false, 'erro' => 'Arquivo .zip maior que 5MB']);
            exit();
        }

        if (!class_exists('ZipArchive')) {
            http_response_code(500);
            echo json_encode(['sucesso' => false, 'erro' => 'Suporte a .zip não está habilitado no servidor']);
            exit();
        }

        $zip = new ZipArchive();

        if ($zip->open($_FILES['arquivo']['tmp_name']) !== true) {
            http_response_code(400);
            echo json_encode(['sucesso' => false, 'erro' => 'Não foi possível abrir o arquivo .zip']);
            exit();
        }

        $conteudoConcatenado = '';
        $arquivosLidos = 0;

        for ($i = 0; $i < $zip->numFiles && $arquivosLidos < $limiteArquivosZip; $i++) {
            $nomeInterno = $zip->getNameIndex($i);
            $extInterna = strtolower(pathinfo($nomeInterno, PATHINFO_EXTENSION));

            if (!in_array($extInterna, $extensoesCodigo)) {
                continue; // ignora imagens, binários, pastas, etc. dentro do zip
            }

            $stat = $zip->statIndex($i);
            if ($stat === false || $stat['size'] > $limitePorArquivoZip) {
                continue; // ignora arquivos individuais grandes demais
            }

            $conteudo = $zip->getFromIndex($i);
            if ($conteudo === false) {
                continue;
            }

            $conteudoConcatenado .= "\n\n// ==== Arquivo: {$nomeInterno} ====\n" . $conteudo;
            $arquivosLidos++;
        }

        $zip->close();

        if ($arquivosLidos === 0) {
            http_response_code(400);
            echo json_encode(['sucesso' => false, 'erro' => 'Nenhum arquivo de código reconhecido dentro do .zip']);
            exit();
        }

        $arquivoConteudo = $conteudoConcatenado;

        // Guarda o .zip original no Cloudinary como histórico
        uploadArquivo($_FILES['arquivo']['tmp_name'], $arquivoNome);
    } else {
        if ($_FILES['arquivo']['size'] > $tamanhoMaximoArquivo) {
            http_response_code(400);
            echo json_encode(['sucesso' => false, 'erro' => 'Arquivo maior que 2MB']);
            exit();
        }

        $arquivoConteudo = file_get_contents($_FILES['arquivo']['tmp_name']);
        uploadArquivo($_FILES['arquivo']['tmp_name'], $arquivoNome);
    }
}

if ($texto === '' && $arquivoConteudo === null) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'Cole um código ou envie um arquivo']);
    exit();
}

$conversaId = isset($_POST['conversa_id']) ? (int) $_POST['conversa_id'] : null;

if ($conversaId === null || !conversaPertenceAoUsuario($conversaId, $_SESSION['id'])) {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'Conversa inválida']);
    exit();
}

// Busca as análises anteriores UMA vez: serve para montar a memória da IA
// e para saber se esta é a primeira mensagem da conversa.
$analisesAnteriores = buscarAnalisesPorConversa($conversaId);
$ehPrimeiraMensagem = count($analisesAnteriores) === 0;
$historico = montarHistoricoParaIA($analisesAnteriores);

$resultado = chamarIA($texto, $arquivoConteudo, $arquivoNome, $historico);

if (!$resultado['sucesso']) {
    http_response_code(502);
    echo json_encode(['sucesso' => false, 'erro' => $resultado['erro']]);
    exit();
}

$estruturado = $resultado['resultado']; // array: linguagem, resumo, vulnerabilidades

$entradaExibida = $texto !== '' ? $texto : ('Arquivo enviado: ' . $arquivoNome);

salvarAnalise(
    $_SESSION['id'],
    $conversaId,
    $entradaExibida,
    $arquivoNome,
    $estruturado['linguagem'] ?? null,
    json_encode($estruturado)
);

// Título automático só na primeira mensagem E se o usuário ainda não renomeou a conversa
if ($ehPrimeiraMensagem && buscarTituloConversa($conversaId) === 'Nova conversa') {
    $tituloGerado = mb_substr(preg_replace('/\s+/', ' ', trim($entradaExibida)), 0, 40);
    if (mb_strlen($entradaExibida) > 40) {
        $tituloGerado .= '...';
    }
    atualizarTituloConversa($conversaId, $tituloGerado);
}

echo json_encode(['sucesso' => true, 'resultado' => $estruturado, 'entrada' => $entradaExibida]);
<?php

/**
 * Lê uma variável de ambiente. O phpdotenv (createImmutable) só preenche
 * $_ENV/$_SERVER e NÃO usa putenv, então getenv() sozinho não enxerga o .env
 * (funciona no Render porque lá as variáveis vêm do próprio painel).
 */
function envStorage(string $nome): ?string {
    $valor = $_ENV[$nome] ?? $_SERVER[$nome] ?? getenv($nome);
    return ($valor === false || $valor === null || $valor === '') ? null : (string) $valor;
}

function uploadArquivo(string $tmpPath, string $nomeOriginal): ?string {
    $cloudName = envStorage('CLOUDINARY_CLOUD_NAME');
    $apiKey    = envStorage('CLOUDINARY_API_KEY');
    $apiSecret = envStorage('CLOUDINARY_API_SECRET');

    if ($cloudName === null || $apiKey === null || $apiSecret === null) {
        error_log("Upload Cloudinary: CLOUDINARY_CLOUD_NAME / API_KEY / API_SECRET não configuradas.");
        return null;
    }

    $timestamp = time();
    $assinatura = sha1("timestamp={$timestamp}{$apiSecret}");
    $mime = mime_content_type($tmpPath) ?: 'application/octet-stream';

    $ch = curl_init("https://api.cloudinary.com/v1_1/{$cloudName}/auto/upload");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'file'      => new CURLFile($tmpPath, $mime, $nomeOriginal),
        'api_key'   => $apiKey,
        'timestamp' => $timestamp,
        'signature' => $assinatura,
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $resposta = curl_exec($ch);
    $erro = curl_error($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($erro) {
        error_log("Erro no upload pro Cloudinary: " . $erro);
        return null;
    }

    $dados = json_decode((string) $resposta, true);

    if (!is_array($dados) || empty($dados['secure_url'])) {
        $motivo = $dados['error']['message'] ?? (string) $resposta;
        error_log("Cloudinary respondeu HTTP {$http} sem secure_url: " . $motivo);
        return null;
    }

    return $dados['secure_url'];
}
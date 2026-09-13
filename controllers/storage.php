<?php

function uploadArquivo(string $tmpPath, string $nomeOriginal): ?string {
    $cloudName = getenv('CLOUDINARY_CLOUD_NAME');
    $apiKey    = getenv('CLOUDINARY_API_KEY');
    $apiSecret = getenv('CLOUDINARY_API_SECRET');

    $timestamp = time();
    $assinatura = sha1("timestamp={$timestamp}{$apiSecret}");

    $ch = curl_init("https://api.cloudinary.com/v1_1/{$cloudName}/auto/upload");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'file'      => new CURLFile($tmpPath, mime_content_type($tmpPath), $nomeOriginal),
        'api_key'   => $apiKey,
        'timestamp' => $timestamp,
        'signature' => $assinatura,
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $resposta = curl_exec($ch);
    $erro = curl_error($ch);
    curl_close($ch);

    if ($erro) {
        error_log("Erro no upload pro Cloudinary: " . $erro);
        return null;
    }

    $dados = json_decode($resposta, true);
    return $dados['secure_url'] ?? null;
}
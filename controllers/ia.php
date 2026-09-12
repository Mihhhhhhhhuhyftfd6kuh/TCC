<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function chamarIA(string $texto, string $arquivo): array {
    // Local usa localhost, em produção troque pela URL pública do serviço no Render
    $urlApi = getenv('IA_API_URL') ?: 'http://localhost:8000/analisar';

    $ch = curl_init($urlApi);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['texto' => $texto]));
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['arquivo' =>$arquivo]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // a chamada ao Claude leva alguns segundos

    $resposta = curl_exec($ch);
    $erroCurl = curl_error($ch);
    curl_close($ch);

    if ($erroCurl) {
        return ['sucesso' => false, 'erro' => 'Não foi possível conectar ao serviço de IA: ' . $erroCurl];
    }

    $dados = json_decode($resposta, true);

    if (!isset($dados['resultado'])) {
        return ['sucesso' => false, 'erro' => 'Resposta inesperada do serviço de IA'];
    }

    return ['sucesso' => true, 'resultado' => $dados['resultado']];
}
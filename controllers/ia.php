<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function chamarIA(string $texto, ?string $arquivoConteudo = null, ?string $arquivoNome = null, array $historico = []): array {
    $urlApi = getenv('IA_API_URL') ?: 'http://localhost:8000/analisar';

    // Junta texto digitado + conteúdo do arquivo em um único campo "codigo",
    // que é o nome que a API Python espera.
    $partes = [];
    if ($texto !== '') {
        $partes[] = $texto;
    }
    if ($arquivoConteudo !== null && $arquivoConteudo !== '') {
        $partes[] = "Arquivo enviado (" . ($arquivoNome ?? 'sem nome') . "):\n" . $arquivoConteudo;
    }
    $codigo = implode("\n\n", $partes);

    $payload = [
        'codigo'    => $codigo,
        'historico' => $historico,
    ];

    // JSON_INVALID_UTF8_SUBSTITUTE evita que um arquivo com bytes inválidos quebre o json_encode
    $corpo = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

    $ch = curl_init($urlApi);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $corpo);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 90);

    $resposta = curl_exec($ch);
    $erroCurl = curl_error($ch);
    curl_close($ch);

    if ($erroCurl) {
        return ['sucesso' => false, 'erro' => 'Não foi possível conectar ao serviço de IA: ' . $erroCurl];
    }

    $dados = json_decode($resposta, true);

    if (!isset($dados['resultado'])) {
        // Se a API mandou o motivo do erro (campo "detail"), mostra ele em vez de uma mensagem genérica
        $detalhe = $dados['detail'] ?? 'Resposta inesperada do serviço de IA';
        if (is_array($detalhe)) {
            $detalhe = json_encode($detalhe, JSON_UNESCAPED_UNICODE);
        }
        return ['sucesso' => false, 'erro' => (string) $detalhe];
    }

    return ['sucesso' => true, 'resultado' => $dados['resultado']];
}
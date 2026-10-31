<?php
/**
 * controllers/crypto.php
 *
 * Criptografia simétrica (AES-256-GCM) para o conteúdo das mensagens
 * do chat antes de salvar no banco.
 *
 * A chave fica SOMENTE nas variáveis de ambiente do servidor
 * (MSG_ENCRYPTION_KEY) — nunca é gravada no banco de dados. Assim,
 * quem olhar a tabela "mensagens" direto no Neon (ou em um backup
 * vazado) vê apenas texto cifrado, sem conseguir ler o conteúdo.
 *
 * Importante: isso protege contra acesso direto e não autorizado ao
 * banco de dados. NÃO é criptografia ponta-a-ponta: a própria
 * aplicação, que tem a chave disponível via variável de ambiente,
 * ainda decifra as mensagens para exibi-las na tela do chat.
 *
 * Como gerar a chave (rode uma vez, localmente):
 *   openssl rand -base64 32
 *
 * Depois, salve o valor gerado:
 *   - Local: no arquivo .env -> MSG_ENCRYPTION_KEY=valor_gerado
 *   - Render: em Settings > Environment > adicionar MSG_ENCRYPTION_KEY
 *
 * NUNCA faça commit da chave no git. Se a chave for perdida, as
 * mensagens antigas cifradas com ela não poderão mais ser lidas —
 * guarde uma cópia seguindo o mesmo cuidado usado para outras
 * credenciais do projeto (ex.: senha do banco).
 */

/**
 * Lê e valida a chave de criptografia das variáveis de ambiente.
 */
function chaveMensagem(): string
{
    $chaveBase64 = $_ENV['MSG_ENCRYPTION_KEY'] ?? getenv('MSG_ENCRYPTION_KEY');

    if (!$chaveBase64) {
        throw new RuntimeException('MSG_ENCRYPTION_KEY não definida nas variáveis de ambiente.');
    }

    $chave = base64_decode($chaveBase64, true);

    if ($chave === false || strlen($chave) !== 32) {
        throw new RuntimeException('MSG_ENCRYPTION_KEY inválida: precisa ser uma chave AES-256 de 32 bytes em base64 (gere com "openssl rand -base64 32").');
    }

    return $chave;
}

/**
 * Criptografa um texto (ex.: o conteúdo de uma mensagem) antes de
 * salvar no banco. Retorna uma string pronta para ser gravada na
 * coluna "mensagem".
 */
function criptografarMensagem(string $textoPlano): string
{
    $chave = chaveMensagem();

    $iv  = random_bytes(12); // 96 bits — tamanho recomendado para GCM
    $tag = '';

    $cifrado = openssl_encrypt($textoPlano, 'aes-256-gcm', $chave, OPENSSL_RAW_DATA, $iv, $tag);

    if ($cifrado === false) {
        throw new RuntimeException('Falha ao criptografar a mensagem.');
    }

    // Formato salvo no banco: enc:v1:<iv base64>:<tag base64>:<cifrado base64>
    // O prefixo "enc:v1:" permite identificar mensagens cifradas e,
    // no futuro, trocar de algoritmo sem quebrar mensagens antigas.
    return 'enc:v1:' . base64_encode($iv) . ':' . base64_encode($tag) . ':' . base64_encode($cifrado);
}

/**
 * Descriptografa um valor vindo do banco. Mensagens gravadas ANTES
 * de a criptografia entrar em uso (sem o prefixo "enc:v1:") são
 * devolvidas como estão, sem precisar de migração manual.
 */
function descriptografarMensagem(string $valorSalvo): string
{
    if (strpos($valorSalvo, 'enc:v1:') !== 0) {
        // Mensagem antiga, gravada antes da criptografia existir.
        return $valorSalvo;
    }

    $partes = explode(':', $valorSalvo, 5);

    if (count($partes) !== 5) {
        return '[mensagem corrompida]';
    }

    [, , $ivB64, $tagB64, $cifradoB64] = $partes;

    $iv      = base64_decode($ivB64, true);
    $tag     = base64_decode($tagB64, true);
    $cifrado = base64_decode($cifradoB64, true);

    if ($iv === false || $tag === false || $cifrado === false) {
        return '[mensagem corrompida]';
    }

    try {
        $chave = chaveMensagem();
    } catch (RuntimeException $e) {
        error_log('Erro ao obter chave de criptografia: ' . $e->getMessage());
        return '[não foi possível descriptografar]';
    }

    $textoPlano = openssl_decrypt($cifrado, 'aes-256-gcm', $chave, OPENSSL_RAW_DATA, $iv, $tag);

    if ($textoPlano === false) {
        return '[não foi possível descriptografar]';
    }

    return $textoPlano;
}
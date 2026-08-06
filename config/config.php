<?php

require __DIR__ . '/../vendor/autoload.php';

// Só carrega o .env se o arquivo existir (ambiente local)
// No Render, as variáveis já vêm do painel, sem precisar de .env
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

// Detecta se está rodando no Render
$isRender = (getenv('RENDER') !== false) || (($_ENV['RENDER'] ?? false) !== false);

if ($isRender) {

    // ===== AMBIENTE RENDER =====
    $databaseUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');

    if (!$databaseUrl) {
        die("Variável de ambiente DATABASE_URL não definida no Render.");
    }

    $parts = parse_url($databaseUrl);
    if ($parts === false || !isset($parts['host'])) {
        die("DATABASE_URL inválida ou incompleta. Verifique a variável de ambiente.");
    }

    $host     = $parts['host'];
    $port     = $parts['port'] ?? 5432;
    $user     = $parts['user'] ?? null;
    $password = $parts['pass'] ?? null;
    $dbname   = isset($parts['path']) ? ltrim($parts['path'], '/') : null;

    $endpoint_id = (strpos($host, '-pooler') !== false)
        ? substr($host, 0, strpos($host, '-pooler'))
        : explode('.', $host)[0];

    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require;options='endpoint={$endpoint_id}'";

} elseif ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1') {

    // ===== AMBIENTE LOCAL =====
    $host        = $_ENV['DB_HOST_LOCAL'] ?? null;
    $user        = $_ENV['DB_USER_LOCAL'] ?? null;
    $password    = $_ENV['DB_PASSWORD_LOCAL'] ?? null;
    $dbname      = $_ENV['DB_NAME_LOCAL'] ?? null;
    $endpoint_id = $_ENV['DB_ENDPOINT_LOCAL'] ?? null;

    if (!$host || !$user || !$dbname) {
        die("Variáveis de ambiente locais não configuradas (DB_HOST_LOCAL, DB_USER_LOCAL, DB_NAME_LOCAL...).");
    }

    $dsn = "pgsql:host={$host};dbname={$dbname};sslmode=require;options='endpoint={$endpoint_id}'";

} else {
    die('Ambiente não reconhecido. Configure a conexão manualmente.');
}

// ===== CONEXÃO (comum aos dois ambientes) =====
try {
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
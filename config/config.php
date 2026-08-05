<?php
// getenv('X') ?: 'valor_padrao'  -> se a variável de ambiente não existir
// (como no seu localhost), usa o valor padrão logo em seguida.

$host        = getenv('DB_HOST')        ?: '127.0.0.1';
$user        = getenv('DB_USER')        ?: 'postgres';
$dbname      = getenv('DB_NAME')        ?: 'TCC';
$password    = getenv('DB_PASSWORD')    ?: 'sua_senha_local';
$port        = getenv('DB_PORT')        ?: '5432';
$endpoint_id = getenv('DB_ENDPOINT_ID') ?: null; // só existe no Neon/Render

if ($endpoint_id) {
    // Neon (Render) exige sslmode e o parâmetro de endpoint
    $dsn = "pgsql:host=$host;dbname=$dbname;user=$user;password=$password;sslmode=require;options='endpoint=$endpoint_id'";
} else {
    // Postgres local, sem SSL
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;user=$user;password=$password";
}

try {
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("ERRO na conexao com o banco: " . $e->getMessage());
}
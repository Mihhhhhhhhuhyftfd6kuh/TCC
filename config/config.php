<?php
$host = "ep-flat-pine-ahnzeu09-pooler.c-3.us-east-1.aws.neon.tech";
$user = "neondb_owner";
$dbname = "TCC";
$password = "npg_EHhbSqnw75dx";

$dsn = "pgsql:host=$host;dbname=$dbname;sslmode=require";

$endpoint = "p-flat-pine-ahnzeu";

$dsn = "pgsql:host=$host;dbname=$dbname;user=$user;password=$password;sslmode=require";

try{
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e){
    die("ERRO na conexao com o banco". $e->getMessage());
}
?>
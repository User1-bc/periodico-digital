<?php
$url = parse_url("postgresql://periodico_7d5x_user:W4IQqdXFVaFOC12NqwtWH61JM1h7PTzn@dpg-dama3o6legvs738n12g0-a.virginia-postgres.render.com:5432/periodico_7d5x");

$host = $url["host"];
$port = $url["port"] ?? "5432";
$db   = ltrim($url["path"], "/");
$user = $url["user"];
$pass = $url["pass"];

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (\PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
<?php
$url = parse_url(getenv("DATABASE_URL") ?: "postgresql://neondb_owner:npg_95UtNN0f2OvYtycdJ@ep-soft-wave-b5wklrr4-pooler.c-7.us-east-2.aws.neon.tech/neondb?sslmode=require");

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
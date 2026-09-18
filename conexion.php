<?php
$host = 'dpg-dama3o6legvs738n12g0-a.virginia-postgres.render.com';
$db   = 'periodico_7d5x';
$user = 'periodico_7d5x_user';
$pass = 'W4IQqdXFVaFOC12NqwtWH61JM1h7PTzn';

try {
    $dsn = "pgsql:host=$host;dbname=$db;sslmode=require";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
}
?>
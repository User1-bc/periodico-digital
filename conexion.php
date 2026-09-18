<?php
$host = 'dpg-dama3o6legvs738n12g0-a.virginia-postgres.render.com';
$db   = 'periodico'; // O el nombre exacto de tu base de datos en Render
$user = 'periodico_7d6x_user';
$pass = 'PEGA_AQUI_TU_CONTRASENA'; // Reemplaza esto con tu contraseña exacta de Render

try {
    $dsn = "pgsql:host=$host;dbname=$db;sslmode=require";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
}
?>
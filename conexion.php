<?php
$host = "dpg-dama3o61egvs738n12g0-a.virginia-postgres.render.com";
$port = "5432";
$dbname = "periodico_7d6x";
$user = "periodico_7d6x_user";
$password = "W4iGqcXFVsfOC12NsmwH81JM1h7pTzn";

try {
    $conexion = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
    exit();
}
?>
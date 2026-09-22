<?php
require_once 'conexion.php';
$pdo->exec("TRUNCATE TABLE suscripciones_push RESTART IDENTITY");
echo "Tabla suscripciones_push limpiada";
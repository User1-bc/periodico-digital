<?php
require_once 'conexion.php';

$stmt = $pdo->query("SELECT * FROM suscripciones_push");
$suscripciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode([
    'count' => count($suscripciones),
    'data' => $suscripciones
]);
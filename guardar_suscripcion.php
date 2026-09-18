<?php
require_once 'conexion.php';

header('Content-Type: application/json');

// Obtener el cuerpo de la petición JSON enviada por JavaScript
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['endpoint']) || !isset($data['keys']['p256dh']) || !isset($data['keys']['auth'])) {
    echo json_encode(['success' => false, 'message' => 'Datos de suscripción incompletos.']);
    exit;
}

$endpoint = $data['endpoint'];
$p256dh   = $data['keys']['p256dh'];
$auth     = $data['keys']['auth'];

try {
    // Verificar si el endpoint ya existe en la base de datos para no duplicarlo
    $stmt = $conexion->prepare("SELECT id FROM suscripciones_push WHERE endpoint = ?");
    $stmt->execute([$endpoint]);
    $existe = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existe) {
        // Insertar la nueva suscripción en PostgreSQL
        $stmt_insert = $conexion->prepare("INSERT INTO suscripciones_push (endpoint, p256dh, auth) VALUES (?, ?, ?)");
        $stmt_insert->execute([$endpoint, $p256dh, $auth]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
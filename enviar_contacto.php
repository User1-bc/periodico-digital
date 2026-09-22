<?php
require_once 'conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$nombre = trim($_POST['nombre'] ?? '');
$email = trim($_POST['email'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$asunto = trim($_POST['asunto'] ?? '');
$mensaje = trim($_POST['mensaje'] ?? '');

if (empty($nombre) || empty($email) || empty($asunto) || empty($mensaje)) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos obligatorios deben completarse']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email inválido']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO contactos (nombre, email, telefono, asunto, mensaje, fecha) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$nombre, $email, $telefono, $asunto, $mensaje]);
    
    // Enviar notificación push a admin (opcional)
    // enviarPushNotificacion($pdo, "📩 Nuevo contacto: $asunto", "$nombre ($email): " . mb_substr($mensaje, 0, 100) . "...", '/admin.php');
    
    echo json_encode(['success' => true, 'message' => 'Mensaje enviado correctamente. Te responderemos pronto.']);
} catch (Exception $e) {
    error_log("Error guardando contacto: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al guardar. Intenta de nuevo.']);
}
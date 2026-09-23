<?php
require_once 'conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$id = (int)($data['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE noticias SET vistas = vistas + 1 WHERE id = ?");
    $stmt->execute([$id]);
    
    // Obtener el nuevo conteo
    $stmt = $pdo->prepare("SELECT vistas FROM noticias WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'vistas' => (int)$row['vistas']]);
} catch (Exception $e) {
    error_log("Error incrementando vista: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno']);
}
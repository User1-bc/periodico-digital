<?php
session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'conexion.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM podcasts WHERE id = ?");
        $stmt->execute([$id]);
    } catch (Exception $e) {
        // Manejo silencioso o error si es necesario
    }
}

header("Location: admin.php");
exit();
?>



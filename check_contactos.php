<?php
require_once 'conexion.php';

try {
    $stmt = $pdo->query("SELECT * FROM contactos LIMIT 1");
    echo "Table exists, columns: ";
    print_r($stmt->fetch(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
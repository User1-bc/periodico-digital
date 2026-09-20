<?php
require_once 'conexion.php';

$sql = file_get_contents(__DIR__ . '/schema.sql');

try {
    $pdo->exec($sql);
    echo "✅ Esquema creado correctamente\n";
    
    $tables = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema='public'")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tablas: " . implode(', ', $tables) . "\n";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
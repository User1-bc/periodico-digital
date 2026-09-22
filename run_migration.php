<?php
require_once 'conexion.php';

try {
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    $pdo->exec($sql);
    echo "✅ Migración ejecutada correctamente. Tablas creadas/actualizadas.\n";
} catch (PDOException $e) {
    echo "❌ Error en migración: " . $e->getMessage() . "\n";
}
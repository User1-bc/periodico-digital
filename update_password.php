<?php
require_once 'conexion.php';

$newPassHash = '1ba48470a26df68125647c55b6ce4457'; // md5('166738@')

try {
    $stmt = $pdo->prepare("UPDATE admin_users SET password = ? WHERE usuario = 'admin'");
    $stmt->execute([$newPassHash]);
    $count = $stmt->rowCount();
    echo "Rows updated: $count\n";
    
    // Verify
    $stmt = $pdo->prepare("SELECT password FROM admin_users WHERE usuario = 'admin'");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Current hash: " . $row['password'] . "\n";
    echo "Expected hash: $newPassHash\n";
    echo "Match: " . ($row['password'] === $newPassHash ? 'YES' : 'NO') . "\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
<?php
/**
 * Ejecuta el robot de noticias via AJAX desde el panel admin
 * Devuelve JSON con resultado
 */

require_once 'conexion.php';

header('Content-Type: application/json');

try {
    // Incluir la lógica del robot (mismo código que robot_noticias.php pero sin output HTML)
    // Para simplicidad, requerimos el archivo principal y capturamos output
    ob_start();
    require_once 'robot_noticias.php';
    $output = ob_get_clean();
    
    // Parsear el log generado
    $logFile = 'robot_log.txt';
    $lastLine = '';
    if (file_exists($logFile)) {
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lastLine = end($lines) ?: '';
    }
    
    // Extraer números del log
    preg_match('/Nuevas: (\d+)/', $lastLine, $m1);
    preg_match('/Breaking: (\d+)/', $lastLine, $m2);
    preg_match('/Duplicadas: (\d+)/', $lastLine, $m3);
    preg_match('/Errores: (\d+)/', $lastLine, $m4);
    preg_match('/Total: (\d+)/', $lastLine, $m5);
    
    echo json_encode([
        'ok' => true,
        'nuevas' => (int)($m1[1] ?? 0),
        'breaking' => (int)($m2[1] ?? 0),
        'duplicadas' => (int)($m3[1] ?? 0),
        'errores' => (int)($m4[1] ?? 0),
        'procesadas' => (int)($m5[1] ?? 0),
        'output' => $output
    ]);
    
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
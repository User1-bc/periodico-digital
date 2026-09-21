<?php
$file = __DIR__ . '/login.php';
$content = file_get_contents($file);
$bytes = unpack('C*', substr($content, 0, 20));
echo "First 20 bytes: " . implode(' ', $bytes) . "\n";
echo "File size: " . filesize($file) . "\n";
?>
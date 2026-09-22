<?php
header('Content-Type: text/plain; charset=utf-8');
echo "CLOUDINARY_URL: " . (getenv('CLOUDINARY_URL') ?: 'NO ESTÁ DEFINIDA') . "\n";
echo "DATABASE_URL: " . (getenv('DATABASE_URL') ? 'DEFINIDA' : 'NO DEFINIDA') . "\n";
echo "PHP_VERSION: " . PHP_VERSION . "\n";
echo "SERVER_SOFTWARE: " . $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' . "\n";
print_r($_ENV);
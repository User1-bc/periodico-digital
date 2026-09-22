<?php
$vendorPath = __DIR__ . '/vendor/autoload.php';
echo "vendor/autoload.php exists: " . (file_exists($vendorPath) ? 'YES' : 'NO') . "\n";

if (file_exists($vendorPath)) {
    require_once $vendorPath;
    echo "autoload loaded OK\n";
    if (class_exists('Minishlink\WebPush\WebPush')) {
        echo "Minishlink\WebPush\WebPush class EXISTS\n";
    } else {
        echo "Minishlink\WebPush\WebPush class NOT FOUND\n";
    }
} else {
    echo "Checking directory structure:\n";
    echo "vendor dir exists: " . (is_dir(__DIR__ . '/vendor') ? 'YES' : 'NO') . "\n";
    if (is_dir(__DIR__ . '/vendor')) {
        $files = scandir(__DIR__ . '/vendor');
        echo "vendor contents: " . implode(', ', $files) . "\n";
    }
}
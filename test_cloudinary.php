<?php
header('Content-Type: text/plain; charset=utf-8');

$cloudinaryUrl = getenv('CLOUDINARY_URL') ?: '';
echo "CLOUDINARY_URL: $cloudinaryUrl\n";

if (!preg_match('/^cloudinary:\/\/([^:]+):([^@]+)@(.+)$/', $cloudinaryUrl, $m)) {
    echo "FORMATO INVÁLIDO\n";
    exit;
}
$apiKey = $m[1]; $apiSecret = $m[2]; $cloudName = $m[3];
echo "API Key: $apiKey\n";
echo "API Secret: $apiSecret\n";
echo "Cloud Name: $cloudName\n";

// Create a test image
$testFile = sys_get_temp_dir() . '/test_cloudinary_' . time() . '.png';
$img = imagecreatetruecolor(100, 100);
$white = imagecolorallocate($img, 255, 255, 255);
$red = imagecolorallocate($img, 255, 0, 0);
imagefilledrectangle($img, 0, 0, 99, 99, $white);
imagestring($img, 5, 20, 40, 'TEST', $red);
imagepng($img, $testFile);
imagedestroy($img);

echo "Test file created: $testFile\n";
echo "File size: " . filesize($testFile) . " bytes\n";

// Upload to Cloudinary
$timestamp = time();
$publicId = 'periodico/test_' . $timestamp;
$paramsToSign = "public_id={$publicId}&timestamp={$timestamp}{$apiSecret}";
$signature = sha1($paramsToSign);

$postFields = [
    'file' => new CURLFile($testFile),
    'api_key' => $apiKey,
    'timestamp' => $timestamp,
    'public_id' => $publicId,
    'signature' => $signature,
    'folder' => 'periodico-digital',
    'resource_type' => 'image'
];

$ch = curl_init("https://api.cloudinary.com/v1_1/{$cloudName}/upload");
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postFields,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 60,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "\n=== RESULTADO ===\n";
echo "HTTP Code: $httpCode\n";
echo "Curl Error: $curlError\n";
echo "Response: $response\n";

if ($httpCode === 200) {
    $result = json_decode($response, true);
    echo "SECURE URL: " . ($result['secure_url'] ?? 'N/A') . "\n";
    echo "✅ SUBIDA EXITOSA\n";
} else {
    echo "❌ ERROR EN SUBIDA\n";
}

// Cleanup
@unlink($testFile);
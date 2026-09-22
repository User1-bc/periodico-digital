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

// Create a simple test file (1x1 pixel PNG)
$testFile = sys_get_temp_dir() . '/test_cloudinary_' . time() . '.png';
// 1x1 transparent PNG (base64 decoded)
$pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
file_put_contents($testFile, $pngData);

echo "Test file created: $testFile\n";
echo "File size: " . filesize($testFile) . " bytes\n";

// Upload to Cloudinary
$timestamp = time();
$publicId = 'periodico/test_' . $timestamp;
$resourceType = 'image';

// Cloudinary signature: all params except file, api_key, signature - sorted alphabetically
$paramsToSign = [
    'folder' => 'periodico-digital',
    'public_id' => $publicId,
    'resource_type' => $resourceType,
    'timestamp' => $timestamp
];
ksort($paramsToSign);
$signatureString = http_build_query($paramsToSign, '', '&') . $apiSecret;
$signature = sha1($signatureString);

echo "DEBUG - Params to sign: " . print_r($paramsToSign, true) . "\n";
echo "DEBUG - Signature string: $signatureString\n";
echo "DEBUG - Signature: $signature\n";

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
<?php
// Test VAPID key format
$publicKey = getenv('VAPID_PUBLIC_KEY') ?: 'BDNidqse1xgK0WW5rCgFJx7jxeDeEB6fFH_FQZ2JPMplderSAXF8Tl3eqOZM0OW-Oe6GVJqbKb2XIqLGtwV3iQ4';
$privateKey = getenv('VAPID_PRIVATE_KEY') ?: 'LBYoE-Sppj7JGPOneXuHBg3NN3IwEYv95bPaH8jO8T0';

echo "Public Key (raw): $publicKey\n";
echo "Private Key (raw): $privateKey\n\n";

// Decode base64url
function base64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}

$pubDecoded = base64url_decode($publicKey);
$privDecoded = base64url_decode($privateKey);

echo "Public Key decoded length: " . strlen($pubDecoded) . " bytes\n";
echo "Private Key decoded length: " . strlen($privDecoded) . " bytes\n\n";

echo "Public Key first byte (hex): " . bin2hex($pubDecoded[0]) . "\n";
echo "Public Key should start with 0x04 (uncompressed): " . ($pubDecoded[0] === "\x04" ? "YES" : "NO") . "\n\n";

echo "Public Key (hex): " . bin2hex($pubDecoded) . "\n";
echo "Private Key (hex): " . bin2hex($privDecoded) . "\n";
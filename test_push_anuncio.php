<?php
// Test push notification for anuncios specifically
require_once 'conexion.php';

header('Content-Type: application/json');

$vapidPublicKey = getenv('VAPID_PUBLIC_KEY') ?: '';
$vapidPrivateKey = getenv('VAPID_PRIVATE_KEY') ?: '';
$vapidSubject = getenv('VAPID_SUBJECT') ?: 'mailto:admin@periodicodigitalrd.online';

echo "VAPID_PUBLIC_KEY: " . (empty($vapidPublicKey) ? 'VACIO' : 'OK (' . substr($vapidPublicKey, 0, 20) . '...)') . "\n";
echo "VAPID_PRIVATE_KEY: " . (empty($vapidPrivateKey) ? 'VACIO' : 'OK (' . substr($vapidPrivateKey, 0, 20) . '...)') . "\n";

$stmt = $pdo->query("SELECT endpoint, p256dh, auth FROM suscripciones_push");
$suscripciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Suscripciones en BD: " . count($suscripciones) . "\n";

if (empty($suscripciones)) {
    echo json_encode(['success' => false, 'message' => 'No hay suscripciones']);
    exit;
}

$payload = json_encode([
    "title" => "📢 Test Anuncio",
    "body"  => "Test push desde test_push_anuncio.php",
    "url"   => "/"
]);

if (empty($vapidPublicKey) || empty($vapidPrivateKey)) {
    echo json_encode(['success' => false, 'message' => 'VAPID keys no configuradas']);
    exit;
}

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    echo json_encode(['success' => false, 'message' => 'Vendor no instalado en ' . $autoloadPath]);
    exit;
}

require_once $autoloadPath;

try {
    $webPush = new \Minishlink\WebPush\WebPush([
        'VAPID' => [
            'subject' => $vapidSubject,
            'publicKey' => $vapidPublicKey,
            'privateKey' => $vapidPrivateKey,
        ],
    ]);

    foreach ($suscripciones as $sub) {
        $subscription = \Minishlink\WebPush\Subscription::create([
            'endpoint' => $sub['endpoint'],
            'keys' => [
                'p256dh' => $sub['p256dh'],
                'auth' => $sub['auth'],
            ],
        ]);
        $webPush->queueNotification($subscription, $payload);
    }

    $results = [];
    foreach ($webPush->flush() as $report) {
        $endpoint = $report->getRequest()->getUri()->__toString();
        if ($report->getResponse()) {
            $status = $report->getResponse()->getStatusCode();
            error_log("Push Anuncio enviado a $endpoint: $status");
            $results[] = ['endpoint' => $endpoint, 'status' => $status, 'success' => true];
        } else {
            $reason = $report->getReason();
            error_log("Push Anuncio falló a $endpoint: $reason");
            $results[] = ['endpoint' => $endpoint, 'reason' => $reason, 'success' => false];
        }
    }
    echo json_encode(['success' => true, 'results' => $results]);
} catch (Throwable $e) {
    error_log("Error en test_push_anuncio: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
}
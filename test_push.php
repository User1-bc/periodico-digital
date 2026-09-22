<?php
// Test endpoint para debug push notifications
require_once 'conexion.php';

header('Content-Type: application/json');

function enviarPushNotificacion($pdo, $titulo, $mensaje, $url = '/') {
    try {
        $stmt = $pdo->query("SELECT endpoint, p256dh, auth FROM suscripciones_push");
        $suscripciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($suscripciones)) {
            error_log("Push: No hay suscripciones en BD");
            return ['success' => false, 'message' => 'No hay suscripciones'];
        }
        
        $payload = json_encode([
            "title" => $titulo,
            "body"  => $mensaje,
            "url"   => $url
        ]);
        
        $vapidPublicKey = getenv('VAPID_PUBLIC_KEY') ?: '';
        $vapidPrivateKey = getenv('VAPID_PRIVATE_KEY') ?: '';
        $vapidSubject = getenv('VAPID_SUBJECT') ?: 'mailto:admin@periodicodigitalrd.online';
        
        error_log("Push: Intentando enviar a " . count($suscripciones) . " suscriptores. VAPID_PUBLIC_KEY=" . (empty($vapidPublicKey) ? 'VACIO' : 'OK') . ", VAPID_PRIVATE_KEY=" . (empty($vapidPrivateKey) ? 'VACIO' : 'OK'));
        
        if (empty($vapidPublicKey) || empty($vapidPrivateKey)) {
            return ['success' => false, 'message' => 'VAPID keys no configuradas'];
        }
        
        $autoloadPath = __DIR__ . '/vendor/autoload.php';
        if (file_exists($autoloadPath)) {
            error_log("Push: vendor/autoload.php ENCONTRADO");
            require_once $autoloadPath;
            
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
                    error_log("Push enviado a $endpoint: $status");
                    $results[] = ['endpoint' => $endpoint, 'status' => $status, 'success' => true];
                } else {
                    $reason = $report->getReason();
                    error_log("Push falló a $endpoint: $reason");
                    $results[] = ['endpoint' => $endpoint, 'reason' => $reason, 'success' => false];
                    if ($reason === '410 Gone' || $reason === '404 Not Found') {
                        $pdo->prepare("DELETE FROM suscripciones_push WHERE endpoint = ?")->execute([$endpoint]);
                    }
                }
            }
            return ['success' => true, 'results' => $results];
        } else {
            error_log("Push: minishlink/web-push NO instalado");
            return ['success' => false, 'message' => 'Vendor no instalado'];
        }
    } catch (Exception $e) {
        error_log("Error enviando push: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// Test
try {
    $result = enviarPushNotificacion($pdo, 'Test Push', 'Notificación de prueba desde test_push.php', '/');
    echo json_encode($result);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
}
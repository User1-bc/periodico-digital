<?php
session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) { 
    header("Location: login.php"); 
    exit(); 
}
require_once 'conexion.php';

// Función para enviar notificaciones push
function enviarPushNotificacion($pdo, $titulo, $mensaje, $url = '/') {
    try {
        $stmt = $pdo->query("SELECT endpoint, p256dh, auth FROM suscripciones_push");
        $suscripciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($suscripciones)) {
            error_log("Push: No hay suscripciones en BD");
            return;
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
        
        if (empty($vapidPublicKey) || empty($vapidPrivateKey)) return;
        
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
            
            foreach ($webPush->flush() as $report) {
                $endpoint = $report->getRequest()->getUri()->__toString();
                if ($report->getResponse()) {
                    $status = $report->getResponse()->getStatusCode();
                    error_log("Push Anuncio enviado a $endpoint: $status");
                } else {
                    $reason = $report->getReason();
                    error_log("Push Anuncio falló a $endpoint: $reason");
                    if ($reason === '410 Gone' || $reason === '404 Not Found') {
                        $pdo->prepare("DELETE FROM suscripciones_push WHERE endpoint = ?")->execute([$endpoint]);
                    }
                }
            }
        } else {
            error_log("Push: minishlink/web-push NO instalado (vendor/autoload.php no existe en " . $autoloadPath . ")");
        }
    } catch (Exception $e) {
        error_log("Error enviando push: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
    }
}

// Subir archivo a Cloudinary
function subirMediaCloudinary($tmpPath, $originalName) {
    $cloudinaryUrl = getenv('CLOUDINARY_URL') ?: '';
    if (empty($cloudinaryUrl)) return null;
    
    if (!preg_match('/^cloudinary:\/\/([^:]+):([^@]+)@(.+)$/', $cloudinaryUrl, $m)) return null;
    $apiKey = $m[1]; $apiSecret = $m[2]; $cloudName = $m[3];
    
    $timestamp = time();
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $publicId = 'periodico/ad_' . preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($originalName, PATHINFO_FILENAME)) . '_' . $timestamp;
    $resourceType = in_array($ext, ['mp4','webm','ogg','mov','avi','mkv','m4v']) ? 'video' : 'image';
    
    // Cloudinary signature: all params except file, api_key, signature - sorted alphabetically, RAW values
    // Note: resource_type is NOT included in signature per Cloudinary's validation
    $paramsToSign = [
        'folder' => 'periodico-digital',
        'public_id' => $publicId,
        'timestamp' => $timestamp
    ];
    ksort($paramsToSign);
    $signatureParts = [];
    foreach ($paramsToSign as $k => $v) {
        $signatureParts[] = "$k=$v";
    }
    $signatureString = implode('&', $signatureParts) . $apiSecret;
    $signature = sha1($signatureString);
    
    $postFields = [
        'file' => new CURLFile($tmpPath),
        'api_key' => $apiKey,
        'timestamp' => $timestamp,
        'public_id' => $publicId,
        'signature' => $signature,
        'folder' => 'periodico-digital',
        'resource_type' => $resourceType
    ];
    
    $ch = curl_init("https://api.cloudinary.com/v1_1/{$cloudName}/upload");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 180,
        CURLOPT_CONNECTTIMEOUT => 30,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) return null;
    $result = json_decode($response, true);
    return $result['secure_url'] ?? null;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cliente_nombre = trim($_POST['cliente_nombre']);
    $enlace_destino = trim($_POST['enlace_destino']);
    $posicion = $_POST['posicion'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    $stmt = $pdo->prepare("INSERT INTO anuncios (titulo, enlace_destino, posicion, activo, imagen_banner) VALUES (?, ?, ?, ?, '')");
    if ($stmt->execute([$cliente_nombre, $enlace_destino, $posicion, $activo])) {
        $anuncio_id = $pdo->lastInsertId();

        // Enviar notificación push
        $tituloPush = "📢 Nuevo Anuncio: " . $cliente_nombre;
        $mensajePush = "Nuevo anuncio disponible en la sección de publicidad";
        enviarPushNotificacion($pdo, $tituloPush, $mensajePush, '/');

        if (isset($_FILES['multimedia']) && !empty($_FILES['multimedia']['name'][0])) {
            $permitidas_img = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
            $permitidas_vid = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', 'm4v'];

            $total_archivos = count($_FILES['multimedia']['name']);

            for ($i = 0; $i < $total_archivos; $i++) {
                if ($_FILES['multimedia']['error'][$i] == 0) {
                    $tmpPath = $_FILES['multimedia']['tmp_name'][$i];
                    $nombre_original = $_FILES['multimedia']['name'][$i];
                    $ext = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));

                    $tipo_archivo = in_array($ext, $permitidas_vid) ? 'video' : 'imagen';

                    $urlCloudinary = subirMediaCloudinary($tmpPath, $nombre_original);
                    if (!$urlCloudinary) {
                        throw new Exception("Error subiendo multimedia a Cloudinary. Verifica CLOUDINARY_URL en variables de entorno.");
                    }
                    $ruta_final = $urlCloudinary;

                    $stmtMedia = $pdo->prepare("INSERT INTO anuncios_multimedia (anuncio_id, archivo, tipo) VALUES (?, ?, ?)");
                    $stmtMedia->execute([$anuncio_id, $ruta_final, $tipo_archivo]);
                }
            }
        }

        header("Location: admin.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Anuncio / Catálogo</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; padding: 30px; }
        .form-container { background: white; max-width: 600px; margin: 0 auto; padding: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h2 { margin-top: 0; color: #333; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        .form-group input[type="text"], .form-group select, .form-group input[type="file"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn-submit { background: #28a745; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn-back { background: #6c757d; color: white; text-decoration: none; padding: 10px 15px; border-radius: 4px; display: inline-block; margin-right: 10px; }
        .helper-text { font-size: 12px; color: #666; margin-top: 4px; display: block; }
        .btn-submit.loading { opacity: 0.7; cursor: wait; pointer-events: none; }
        .btn-submit.loading::after { content: " ⏳"; animation: pulse 1s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>📢 Publicar Anuncio / Catálogo</h2>
        <form action="" method="POST" enctype="multipart/form-data" id="form-anuncio">
            <div class="form-group">
                <label>Nombre del Cliente / Marca:</label>
                <input type="text" name="cliente_nombre" required>
            </div>
            
            <div class="form-group">
                <label>Enlace de Destino (Opcional - WhatsApp, Web, Redes):</label>
                <input type="text" name="enlace_destino" placeholder="https://... (opcional)">
                <span class="helper-text">Deja vacío si solo quieres mostrar la imagen/video sin redirigir.</span>
            </div>

            <div class="form-group">
                <label>Posición en el Periódico:</label>
                <select name="posicion" required>
                    <option value="izquierda">Columna Izquierda</option>
                    <option value="carrete_superior">Carrete Superior (Banner Fijo)</option>
                </select>
            </div>

            <div class="form-group">
                <label>Artículos (Fotos o Videos para el Carrete):</label>
                <input type="file" name="multimedia[]" multiple required>
                <span class="helper-text">Selecciona múltiples imágenes de los productos o artículos en venta.</span>
            </div>

            <div class="form-group" style="display: flex; align-items: center; gap: 10px;">
                <input type="checkbox" name="activo" id="activo" value="1" checked style="width: auto;">
                <label for="activo" style="margin: 0; cursor: pointer;">Anuncio Activo / Visible</label>
            </div>

            <div style="margin-top: 20px;">
                <a href="admin.php" class="btn-back">Cancelar</a>
                <button type="submit" class="btn-submit" id="btn-submit-anuncio">Publicar Anuncio</button>
            </div>
        </form>
    </div>
    <script>
        document.getElementById('form-anuncio').addEventListener('submit', function() {
            const btn = document.getElementById('btn-submit-anuncio');
            btn.classList.add('loading');
            btn.innerHTML = 'Publicando... ⏳';
        });
    </script>
</body>
</html>



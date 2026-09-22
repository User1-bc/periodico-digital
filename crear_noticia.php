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
            $webPush->flush();
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
    $publicId = 'periodico/' . preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($originalName, PATHINFO_FILENAME)) . '_' . $timestamp;
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
        CURLOPT_TIMEOUT => 60,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) return null;
    $result = json_decode($response, true);
    return $result['secure_url'] ?? null;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    
    $fechaRD = new DateTime('now', new DateTimeZone('America/Santo_Domingo'));
    $fechaPub = $fechaRD->format('Y-m-d H:i:s');
    
    $stmt = $pdo->prepare("INSERT INTO noticias (titulo, contenido, descripcion, fecha_publicacion) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$titulo, $descripcion, $descripcion, $fechaPub])) {
        $noticia_id = $pdo->lastInsertId();

        // Enviar notificación push
        $tituloPush = "📰 Nueva Noticia: " . $titulo;
        $mensajePush = mb_substr($descripcion, 0, 100) . "...";
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

                    if (in_array($ext, $permitidas_vid)) $tipo_archivo = 'video';
                    else $tipo_archivo = 'imagen';

                    // Subir a Cloudinary (OBLIGATORIO - sin fallback local)
                    $urlCloudinary = subirMediaCloudinary($tmpPath, $nombre_original);
                    if (!$urlCloudinary) {
                        throw new Exception("Error subiendo multimedia a Cloudinary. Verifica CLOUDINARY_URL en variables de entorno.");
                    }
                    $ruta_final = $urlCloudinary;

                    $stmtMedia = $pdo->prepare("INSERT INTO noticias_multimedia (noticia_id, archivo, tipo) VALUES (?, ?, ?)");
                    $stmtMedia->execute([$noticia_id, $ruta_final, $tipo_archivo]);
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
    <title>Publicar Noticia</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; padding: 30px; }
        .form-container { background: white; max-width: 650px; margin: 0 auto; padding: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h2 { margin-top: 0; color: #333; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        .form-group input[type="text"], .form-group textarea, .form-group input[type="file"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .form-group textarea { height: 150px; }
        .btn-submit { background: #28a745; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn-back { background: #6c757d; color: white; text-decoration: none; padding: 10px 15px; border-radius: 4px; display: inline-block; margin-right: 10px; }
        .helper-text { font-size: 12px; color: #666; margin-top: 4px; display: block; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>📰 Publicar Noticia con Carrete Multimedia</h2>
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Título de la Noticia:</label>
                <input type="text" name="titulo" required>
            </div>
            
            <div class="form-group">
                <label>Contenido / Descripción:</label>
                <textarea name="descripcion" required></textarea>
            </div>

            <div class="form-group">
                <label>Archivos Multimedia (Puedes seleccionar varios a la vez):</label>
                <input type="file" name="multimedia[]" multiple>
                <span class="helper-text">Mantén presionado Ctrl (o Cmd en Mac) para seleccionar múltiples imágenes o videos.</span>
            </div>

            <div style="margin-top: 20px;">
                <a href="admin.php" class="btn-back">Cancelar</a>
                <button type="submit" class="btn-submit">Publicar Noticia</button>
            </div>
        </form>
    </div>
</body>
</html>



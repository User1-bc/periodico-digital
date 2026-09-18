<?php
// Si las variables no vienen definidas desde admin.php, evitamos errores
if (!isset($titulo_push)) { $titulo_push = "Nuevo contenido"; }
if (!isset($mensaje_push)) { $mensaje_push = "Hay una nueva publicación en el periódico."; }

require_once 'conexion.php';

try {
    // 1. Obtener todas las suscripciones guardadas en PostgreSQL
    $stmt = $conexion->query("SELECT endpoint, p256dh, auth FROM suscripciones_push");
    $suscripciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($suscripciones)) {
        $payload = json_encode([
            "title" => $titulo_push,
            "body"  => $mensaje_push,
            "url"   => "/"
        ]);

        // 2. Recorrer cada suscriptor (¡Aquí estaba el error de sintaxis!)
        foreach ($suscripciones as $sub) {
            // Nota: El envío real de Web Push requiere cabeceras VAPID y cifrado AES-GCM. 
            // Para mantenerlo sencillo y nativo, lo ideal es usar la librería minishlink/web-push.
        }
    }

} catch (Exception $e) {
    error_log("Error al enviar notificaciones push: " . $e->getMessage());
}
?>
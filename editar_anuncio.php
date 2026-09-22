<?php
session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) { 
    header("Location: login.php"); 
    exit(); 
}
require_once 'conexion.php';

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
        CURLOPT_TIMEOUT => 60,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) return null;
    $result = json_decode($response, true);
    return $result['secure_url'] ?? null;
}

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: admin.php"); exit(); }

$stmt = $pdo->prepare("SELECT * FROM anuncios WHERE id = ?");
$stmt->execute([$id]);
$anuncio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$anuncio) { header("Location: admin.php"); exit(); }

if (isset($_GET['eliminar_media'])) {
    $media_id = $_GET['eliminar_media'];
    $stmt_del = $pdo->prepare("SELECT archivo FROM anuncios_multimedia WHERE id = ? AND anuncio_id = ?");
    $stmt_del->execute([$media_id, $id]);
    $archivo_info = $stmt_del->fetch(PDO::FETCH_ASSOC);

    if ($archivo_info) {
        if (str_starts_with($archivo_info['archivo'], 'uploads/') && file_exists($archivo_info['archivo'])) {
            unlink($archivo_info['archivo']);
        }
        $stmt_drop = $pdo->prepare("DELETE FROM anuncios_multimedia WHERE id = ?");
        $stmt_drop->execute([$media_id]);
    }
    header("Location: editar_anuncio.php?id=" . $id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cliente_nombre = trim($_POST['cliente_nombre']);
    $enlace_destino = trim($_POST['enlace_destino']);
    $posicion = $_POST['posicion'];
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    $update = $pdo->prepare("UPDATE anuncios SET titulo = ?, enlace_destino = ?, posicion = ?, activo = ? WHERE id = ?");
    $update->execute([$cliente_nombre, $enlace_destino, $posicion, $activo, $id]);

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
                $stmtMedia->execute([$id, $ruta_final, $tipo_archivo]);
            }
        }
    }
    
    header("Location: admin.php");
    exit();
}

// Obtener multimedia actual del anuncio
$stmt_media = $pdo->prepare("SELECT * FROM anuncios_multimedia WHERE anuncio_id = ?");
$stmt_media->execute([$id]);
$archivos_actuales = $stmt_media->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Anuncio</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; padding: 30px; }
        .form-container { background: white; max-width: 650px; margin: 0 auto; padding: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h2 { margin-top: 0; color: #333; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        .form-group input[type="text"], .form-group input[type="url"], .form-group select, .form-group input[type="file"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn-submit { background: #28a745; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn-back { background: #6c757d; color: white; text-decoration: none; padding: 10px 15px; border-radius: 4px; display: inline-block; margin-right: 10px; }
        .grid-multimedia-admin { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; background: #f8f9fa; padding: 10px; border-radius: 6px; }
        .item-admin { position: relative; width: 90px; height: 90px; border: 1px solid #ddd; border-radius: 4px; overflow: hidden; background: #000; display: flex; align-items: center; justify-content: center; }
        .item-admin img, .item-admin video { width: 100%; height: 100%; object-fit: cover; }
        .btn-eliminar-item { position: absolute; top: 2px; right: 2px; background: rgba(220, 53, 69, 0.85); color: white; border: none; border-radius: 50%; width: 22px; height: 22px; font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; text-decoration: none; }
        .helper-text { font-size: 12px; color: #666; margin-top: 4px; display: block; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>✏️ Editar Anuncio / Catálogo</h2>
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Nombre del Cliente / Título:</label>
                <input type="text" name="cliente_nombre" value="<?php echo htmlspecialchars($anuncio['titulo']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Enlace de Destino (URL):</label>
                <input type="url" name="enlace_destino" value="<?php echo htmlspecialchars($anuncio['enlace_destino']); ?>" required placeholder="https://ejemplo.com">
            </div>

            <div class="form-group">
                <label>Posición:</label>
                <select name="posicion" required>
                    <option value="izquierda" <?php echo ($anuncio['posicion'] == 'izquierda') ? 'selected' : ''; ?>>Columna Izquierda</option>
                    <option value="carrete_superior" <?php echo ($anuncio['posicion'] == 'carrete_superior') ? 'selected' : ''; ?>>Carrete Superior (Banner Fijo)</option>
                </select>
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="activo" value="1" <?php echo $anuncio['activo'] ? 'checked' : ''; ?> style="width: auto;">
                    Anuncio Activo / Visible
                </label>
            </div>

            <div class="form-group">
                <label>Multimedia actual en el carrete:</label>
                <?php if (empty($archivos_actuales)): ?>
                    <p style="font-size: 13px; color: #777;">No hay archivos multimedia en este anuncio.</p>
                <?php else: ?>
                    <div class="grid-multimedia-admin">
                        <?php foreach ($archivos_actuales as $media): ?>
                            <div class="item-admin">
                                <?php if ($media['tipo'] == 'video'): ?>
                                    <video src="<?php echo htmlspecialchars($media['archivo']); ?>" controls></video>
                                <?php else: ?>
                                    <img src="<?php echo htmlspecialchars($media['archivo']); ?>" alt="Media">
                                <?php endif; ?>
                                <a href="editar_anuncio.php?id=<?php echo $id; ?>&eliminar_media=<?php echo $media['id']; ?>" class="btn-eliminar-item" onclick="return confirm('¿Seguro que deseas eliminar este archivo?');" title="Eliminar archivo">×</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label style="margin-top: 15px;">Añadir más archivos al carrete (Opcional):</label>
                <input type="file" name="multimedia[]" multiple>
                <span class="helper-text">Puedes seleccionar múltiples imágenes o videos manteniendo presionado Ctrl (o Cmd en Mac).</span>
            </div>

            <div style="margin-top: 20px;">
                <a href="admin.php" class="btn-back">Cancelar</a>
                <button type="submit" class="btn-submit">Guardar Cambios</button>
            </div>
        </form>
    </div>
</body>
</html>
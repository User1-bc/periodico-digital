<?php
session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) { 
    header("Location: login.php"); 
    exit(); 
}
require_once 'conexion.php';

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: admin.php"); exit(); }

// Obtener la noticia principal
$stmt = \$pdo->prepare("SELECT * FROM noticias WHERE id = ?");
$stmt->execute([$id]);
$noticia = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$noticia) { header("Location: admin.php"); exit(); }

// Procesar eliminación de un archivo multimedia específico del carrete
if (isset($_GET['eliminar_media'])) {
    $media_id = $_GET['eliminar_media'];
    $stmt_del = \$pdo->prepare("SELECT archivo FROM noticias_multimedia WHERE id = ? AND noticia_id = ?");
    $stmt_del->execute([$media_id, $id]);
    $archivo_info = $stmt_del->fetch(PDO::FETCH_ASSOC);

    if ($archivo_info) {
        if (file_exists($archivo_info['archivo'])) {
            unlink($archivo_info['archivo']);
        }
        $stmt_drop = \$pdo->prepare("DELETE FROM noticias_multimedia WHERE id = ?");
        $stmt_drop->execute([$media_id]);
    }
    header("Location: editar_noticia.php?id=" . $id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    
    // Actualizar datos de texto de la noticia principal
    $update = \$pdo->prepare("UPDATE noticias SET titulo = ?, descripcion = ? WHERE id = ?");
    $update->execute([$titulo, $descripcion, $id]);

    // Procesar nuevos archivos múltiples subidos en la edición
    if (isset($_FILES['multimedia']) && !empty($_FILES['multimedia']['name'][0])) {
        $permitidas_img = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
        $permitidas_vid = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', 'm4v'];

        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }

        $total_archivos = count($_FILES['multimedia']['name']);

        for ($i = 0; $i < $total_archivos; $i++) {
            if ($_FILES['multimedia']['error'][$i] == 0) {
                $nombre_original = preg_replace("/[^a-zA-Z0-9.\-_]/", "_", $_FILES['multimedia']['name'][$i]);
                $nombre_archivo = time() . "_" . $i . "_" . $nombre_original;
                $ruta_destino = "uploads/" . $nombre_archivo;
                $ext = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));

                if (move_uploaded_file($_FILES['multimedia']['tmp_name'][$i], $ruta_destino)) {
                    if (in_array($ext, $permitidas_vid)) {
                        $tipo_archivo = 'video';
                    } else {
                        $tipo_archivo = 'imagen';
                    }

                    $stmtMedia = \$pdo->prepare("INSERT INTO noticias_multimedia (noticia_id, archivo, tipo) VALUES (?, ?, ?)");
                    $stmtMedia->execute([$id, $ruta_destino, $tipo_archivo]);
                }
            }
        }
    }
    
    header("Location: admin.php");
    exit();
}

// Obtener los archivos multimedia actuales del carrete
$stmt_media = \$pdo->prepare("SELECT * FROM noticias_multimedia WHERE noticia_id = ?");
$stmt_media->execute([$id]);
$archivos_actuales = $stmt_media->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Noticia</title>
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
        .grid-multimedia-admin { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; background: #f8f9fa; padding: 10px; border-radius: 6px; }
        .item-admin { position: relative; width: 90px; height: 90px; border: 1px solid #ddd; border-radius: 4px; overflow: hidden; background: #000; display: flex; align-items: center; justify-content: center; }
        .item-admin img, .item-admin video { width: 100%; height: 100%; object-fit: cover; }
        .btn-eliminar-item { position: absolute; top: 2px; right: 2px; background: rgba(220, 53, 69, 0.85); color: white; border: none; border-radius: 50%; width: 22px; height: 22px; font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; text-decoration: none; }
        .helper-text { font-size: 12px; color: #666; margin-top: 4px; display: block; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>✏️ Editar Noticia y Carrete</h2>
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Título:</label>
                <input type="text" name="titulo" value="<?php echo htmlspecialchars($noticia['titulo']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Descripción / Contenido:</label>
                <textarea name="descripcion" required><?php echo htmlspecialchars($noticia['descripcion']); ?></textarea>
            </div>

            <div class="form-group">
                <label>Multimedia actual en el carrete:</label>
                <?php if (empty($archivos_actuales)): ?>
                    <p style="font-size: 13px; color: #777;">No hay archivos multimedia en esta noticia.</p>
                <?php else: ?>
                    <div class="grid-multimedia-admin">
                        <?php foreach ($archivos_actuales as $media): ?>
                            <div class="item-admin">
                                <?php if ($media['tipo'] == 'video'): ?>
                                    <video src="<?php echo htmlspecialchars($media['archivo']); ?>"></video>
                                <?php else: ?>
                                    <img src="<?php echo htmlspecialchars($media['archivo']); ?>" alt="Media">
                                <?php endif; ?>
                                <a href="editar_noticia.php?id=<?php echo $id; ?>&eliminar_media=<?php echo $media['id']; ?>" class="btn-eliminar-item" onclick="return confirm('¿Seguro que deseas eliminar este archivo?');" title="Eliminar archivo">×</a>
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

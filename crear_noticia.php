<?php
session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) { 
    header("Location: login.php"); 
    exit(); 
}
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    
    // 1. Insertar primero la noticia principal
    $stmt = $pdo->prepare("INSERT INTO noticias (titulo, descripcion) VALUES (?, ?)");
    if ($stmt->execute([$titulo, $descripcion])) {
        $noticia_id = $pdo->lastInsertId();

        // 2. Procesar la subida múltiple de archivos multimedia
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

                        // Guardar cada archivo en la tabla secundaria del carrete
                        $stmtMedia = $pdo->prepare("INSERT INTO noticias_multimedia (noticia_id, archivo, tipo) VALUES (?, ?, ?)");
                        $stmtMedia->execute([$noticia_id, $ruta_destino, $tipo_archivo]);
                    }
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



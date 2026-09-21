<?php
session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) { 
    header("Location: login.php"); 
    exit(); 
}
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cliente_nombre = trim($_POST['cliente_nombre']);
    $enlace_destino = trim($_POST['enlace_destino']);
    $posicion = $_POST['posicion']; // 'izquierda' o 'derecha'
    $activo = isset($_POST['activo']) ? 1 : 0;
    
    // 1. Insertar el anuncio principal (columna es 'titulo' en schema)
    $stmt = $pdo->prepare("INSERT INTO anuncios (titulo, enlace_destino, posicion, activo, imagen_banner) VALUES (?, ?, ?, ?, '')");
    if ($stmt->execute([$cliente_nombre, $enlace_destino, $posicion, $activo])) {
        $anuncio_id = $pdo->lastInsertId();

        // 2. Procesar la subida múltiple de archivos para el carrete del anuncio
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
                    $nombre_archivo = time() . "_ad_" . $i . "_" . $nombre_original;
                    $ruta_destino = "uploads/" . $nombre_archivo;
                    $ext = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));

                    if (move_uploaded_file($_FILES['multimedia']['tmp_name'][$i], $ruta_destino)) {
                        $tipo_archivo = in_array($ext, $permitidas_vid) ? 'video' : 'imagen';

                        $stmtMedia = $pdo->prepare("INSERT INTO anuncios_multimedia (anuncio_id, archivo, tipo) VALUES (?, ?, ?)");
                        $stmtMedia->execute([$anuncio_id, $ruta_destino, $tipo_archivo]);
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
    </style>
</head>
<body>
    <div class="form-container">
        <h2>📢 Publicar Anuncio / Catálogo</h2>
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Nombre del Cliente / Marca:</label>
                <input type="text" name="cliente_nombre" required>
            </div>
            
            <div class="form-group">
                <label>Enlace de Destino (WhatsApp, Web, Redes):</label>
                <input type="text" name="enlace_destino" placeholder="https://..." required>
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
                <button type="submit" class="btn-submit">Publicar Anuncio</button>
            </div>
        </form>
    </div>
</body>
</html>



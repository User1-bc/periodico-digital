<?php
session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'conexion.php';

$mensaje_exito = "";
$mensaje_error = "";

// Lógica para guardar el podcast en PostgreSQL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_podcast'])) {
    $titulo = $_POST['titulo_podcast'] ?? '';
    $descripcion = $_POST['descripcion_podcast'] ?? '';
    $url_youtube = $_POST['url_youtube'] ?? '';

    // Extraer el ID del video de YouTube de forma segura
    preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i', $url_youtube, $matches);
    $video_id = isset($matches[1]) ? $matches[1] : '';

    if (!empty($video_id)) {
        $embed_url = "https://www.youtube.com/embed/" . $video_id;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO podcasts (titulo, descripcion, url_youtube) VALUES (?, ?, ?)");
            $stmt->execute([$titulo, $descripcion, $embed_url]);
            
            // 🚀 INTEGRACIÓN DE NOTIFICACIÓN PUSH
            $titulo_push = "Nuevo Podcast: " . $titulo;
            $mensaje_push = $descripcion;
            if (file_exists('enviar_push.php')) {
                include 'enviar_push.php';
            }

            // ⭐ PATRÓN PRG: Redirigimos para evitar duplicados al recargar (F5)
            header("Location: admin.php?exito=1");
            exit();
        } catch (Exception $e) {
            $mensaje_error = "Error al guardar en la base de datos: " . $e->getMessage();
        }
    } else {
        $mensaje_error = "El enlace de YouTube no es válido. Por favor verifica.";
    }
}

// Capturar mensaje de éxito mediante parámetro GET tras la redirección
if (isset($_GET['exito']) && $_GET['exito'] == 1) {
    $mensaje_exito = "¡Podcast publicado y notificación enviada con éxito!";
}

// Obtener todas las noticias
$stmt_noticias = $pdo->query("SELECT * FROM noticias ORDER BY fecha_publicacion DESC");
$noticias = $stmt_noticias->fetchAll(PDO::FETCH_ASSOC);

// Obtener todos los anuncios
$stmt_anuncios = $pdo->query("SELECT * FROM anuncios ORDER BY id DESC");
$anuncios = $stmt_anuncios->fetchAll(PDO::FETCH_ASSOC);

// Obtener todos los podcasts
$stmt_podcasts = $pdo->query("SELECT * FROM podcasts ORDER BY id DESC");
$podcasts = $stmt_podcasts->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Periódico Digital</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 0; }
        header { background: #1b263b; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        header h1 { margin: 0; font-size: 20px; }
        .nav-links a { color: white; text-decoration: none; margin-left: 15px; font-size: 14px; background: #007bff; padding: 8px 12px; border-radius: 4px; }
        .nav-links a.logout { background: #dc3545; }
        .container { max-width: 1100px; margin: 30px auto; padding: 0 15px; }
        
        .section-box { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .section-box h2 { margin-top: 0; color: #333; border-bottom: 2px solid #007bff; padding-bottom: 8px; display: flex; justify-content: space-between; align-items: center; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; font-size: 14px; }
        th { background-color: #f8f9fa; color: #333; }
        
        .btn { padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: bold; display: inline-block; }
        .btn-edit { background: #ffc107; color: #333; margin-right: 5px; }
        .btn-delete { background: #dc3545; color: white; }
        .btn-add { background: #28a745; color: white; font-size: 13px; padding: 6px 12px; border: none; cursor: pointer; }
        
        .acciones-td { white-space: nowrap; }

        /* Estilos para formularios integrados en el panel */
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #333; font-size: 14px; }
        .form-group input[type="text"], .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; box-sizing: border-box; }
    </style>
</head>
<body>

    <header>
        <h1>🛠️ Panel de Administración</h1>
        <div class="nav-links">
            <a href="index.php" target="_blank">Ver Periódico</a>
            <a href="logout.php" class="logout">Cerrar Sesión</a>
        </div>
    </header>

    <div class="container">

        <!-- MENSAJES DE ALERTA -->
        <?php if (!empty($mensaje_exito)): ?>
            <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                <?php echo $mensaje_exito; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($mensaje_error)): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                <?php echo $mensaje_error; ?>
            </div>
        <?php endif; ?>

        <!-- SECCIÓN DE NOTICIAS -->
        <div class="section-box">
            <h2>
                <span>📰 Noticias Publicadas</span>
                <a href="crear_noticia.php" class="btn btn-add">+ Nueva Noticia</a>
            </h2>
            <?php if (empty($noticias)): ?>
                <p style="color: #666; font-size: 14px;">No hay noticias publicadas.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($noticias as $noticia): ?>
                            <tr>
                                <td><?php echo $noticia['id']; ?></td>
                                <td><?php echo htmlspecialchars($noticia['titulo']); ?></td>
                                <td><?php echo $noticia['fecha_publicacion']; ?></td>
                                <td class="acciones-td">
                                    <a href="editar_noticia.php?id=<?php echo $noticia['id']; ?>" class="btn btn-edit">Editar</a>
                                    <a href="eliminar_noticia.php?id=<?php echo $noticia['id']; ?>" class="btn btn-delete" onclick="return confirm('¿Estás seguro de eliminar esta noticia?');">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- SECCIÓN DE ANUNCIOS -->
        <div class="section-box">
            <h2>
                <span>📢 Anuncios Publicitarios</span>
                <a href="crear_anuncio.php" class="btn btn-add">+ Nuevo Anuncio</a>
            </h2>
            <?php if (empty($anuncios)): ?>
                <p style="color: #666; font-size: 14px;">No hay anuncios registrados.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Posición</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($anuncios as $anuncio): ?>
                            <tr>
                                <td><?php echo $anuncio['id']; ?></td>
                                <td><?php echo htmlspecialchars($anuncio['cliente_nombre']); ?></td>
                                <td style="text-transform: capitalize;"><?php echo $anuncio['posicion']; ?></td>
                                <td>
                                    <?php if ($anuncio['activo'] == 1): ?>
                                        <span style="color: green; font-weight: bold;">Activo</span>
                                    <?php else: ?>
                                        <span style="color: red; font-weight: bold;">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="acciones-td">
                                    <a href="editar_anuncio.php?id=<?php echo $anuncio['id']; ?>" class="btn btn-edit">Editar</a>
                                    <a href="eliminar_anuncio.php?id=<?php echo $anuncio['id']; ?>" class="btn btn-delete" onclick="return confirm('¿Estás seguro de eliminar este anuncio?');">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- SECCIÓN DE PODCASTS -->
        <div class="section-box">
            <h2>
                <span>🎙️ Gestión de Podcasts</span>
            </h2>
            
            <!-- Formulario interno para publicar podcast rápidamente -->
            <form action="admin.php" method="POST" style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #e9ecef;">
                <h3 style="margin-top: 0; font-size: 15px; color: #333; margin-bottom: 12px;">Publicar Nuevo Episodio</h3>
                
                <div class="form-group">
                    <label>Título del Episodio:</label>
                    <input type="text" name="titulo_podcast" required placeholder="Ej. Entrevista exclusiva con...">
                </div>
                
                <div class="form-group">
                    <label>Enlace de YouTube:</label>
                    <input type="text" name="url_youtube" required placeholder="https://www.youtube.com/watch?v=...">
                </div>

                <div class="form-group">
                    <label>Descripción breve:</label>
                    <textarea name="descripcion_podcast" rows="3" required placeholder="Breve resumen del episodio..."></textarea>
                </div>

                <button type="submit" name="guardar_podcast" class="btn btn-add">Publicar Podcast</button>
            </form>

            <!-- Tabla de podcasts existentes -->
            <?php if (empty($podcasts)): ?>
                <p style="color: #666; font-size: 14px;">No hay podcasts publicados.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($podcasts as $podcast): ?>
                            <tr>
                                <td><?php echo $podcast['id']; ?></td>
                                <td><?php echo htmlspecialchars($podcast['titulo']); ?></td>
                                <td><?php echo $podcast['fecha_publicacion']; ?></td>
                                <td class="acciones-td">
                                    <a href="eliminar_podcast.php?id=<?php echo $podcast['id']; ?>" class="btn btn-delete" onclick="return confirm('¿Estás seguro de eliminar este podcast?');">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>



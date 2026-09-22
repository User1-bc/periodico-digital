<?php
session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'conexion.php';

// Función para generar imagen con DALL-E y subir a Cloudinary
function generarImagenNoticia($titulo, $contenido) {
    $apiKey = getenv('OPENAI_API_KEY') ?: '';
    if (empty($apiKey)) return null;
    
    $parrafos = explode("\n\n", $contenido);
    $resumenVisual = $titulo . '. ' . implode(' ', array_slice($parrafos, 0, 2));
    $resumenVisual = mb_substr($resumenVisual, 0, 400);
    
    $prompt = "Ilustración periodística profesional para noticia dominicana: {$resumenVisual}. Estilo fotorrealista, composición limpia, colores vibrantes, adecuada para portada de periódico digital, sin texto, sin marcas de agua, aspecto 16:9.";
    
    try {
        $data = [
            'model' => 'dall-e-3',
            'prompt' => $prompt,
            'n' => 1,
            'size' => '1792x1024',
            'quality' => 'standard',
            'response_format' => 'url'
        ];
        
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAuthorization: Bearer {$apiKey}\r\n",
                'content' => json_encode($data),
                'timeout' => 60
            ]
        ]);
        
        $response = @file_get_contents('https://api.openai.com/v1/images/generations', false, $ctx);
        if (!$response) return null;
        
        $result = json_decode($response, true);
        $imageUrl = $result['data'][0]['url'] ?? null;
        if (!$imageUrl) return null;
        
        // Descargar imagen temporal
        $imgData = @file_get_contents($imageUrl);
        if (!$imgData) return $imageUrl;
        
        $tmpFile = sys_get_temp_dir() . '/ia_' . preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '_', mb_substr($titulo, 0, 40))) . '_' . time() . '.png';
        file_put_contents($tmpFile, $imgData);
        
        // Subir a Cloudinary (persistente)
        $cloudinaryUrl = subirACloudinary($tmpFile, $titulo);
        
        // Limpiar temp
        @unlink($tmpFile);
        
        return $cloudinaryUrl ?: $imageUrl;
        
    } catch (Exception $e) {
        error_log("Error generando imagen IA: " . $e->getMessage());
        return null;
    }
}

// Subir imagen a Cloudinary (persistente, CDN global)
function subirACloudinary($filepath, $titulo) {
    $cloudinaryUrl = getenv('CLOUDINARY_URL') ?: '';
    if (empty($cloudinaryUrl)) return null;
    
    // Parsear cloudinary://api_key:api_secret@cloud_name
    if (!preg_match('/^cloudinary:\/\/([^:]+):([^@]+)@(.+)$/', $cloudinaryUrl, $m)) {
        error_log("CLOUDINARY_URL formato inválido");
        return null;
    }
    $apiKey = $m[1];
    $apiSecret = $m[2];
    $cloudName = $m[3];
    
    try {
        $timestamp = time();
        $publicId = 'periodico/' . preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '_', mb_substr($titulo, 0, 40))) . '_' . $timestamp;
        
        // Firmar según spec de Cloudinary - all params except file, api_key, signature - sorted alphabetically, RAW values
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
            'file' => new CURLFile($filepath),
            'api_key' => $apiKey,
            'timestamp' => $timestamp,
            'public_id' => $publicId,
            'signature' => $signature,
            'folder' => 'periodico-digital',
            'resource_type' => 'auto'
        ];
        
        $ch = curl_init("https://api.cloudinary.com/v1_1/{$cloudName}/upload");
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("Cloudinary upload failed: $response");
            return null;
        }
        
        $result = json_decode($response, true);
        return $result['secure_url'] ?? null;
        
    } catch (Exception $e) {
        error_log("Error subiendo a Cloudinary: " . $e->getMessage());
        return null;
    }
}

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

// Obtener noticias del robot (pendientes de revisión)
$stmt_robot = $pdo->query("SELECT * FROM noticias_robot WHERE estado IN ('pendiente','editando') ORDER BY fecha_creacion DESC");
$noticias_robot = $stmt_robot->fetchAll(PDO::FETCH_ASSOC);

// Manejar acciones del robot
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['robot_publicar'])) {
        $id = (int)$_POST['robot_id'];
        $stmt = $pdo->prepare("UPDATE noticias_robot SET estado='publicado', fecha_publicacion=NOW(), admin_id=? WHERE id=?");
        $stmt->execute([$_SESSION['admin_id'] ?? 1, $id]);
        // También crear la noticia real
        $nr = $pdo->prepare("SELECT * FROM noticias_robot WHERE id=?");
        $nr->execute([$id]);
        $robot = $nr->fetch(PDO::FETCH_ASSOC);
        if ($robot) {
            try {
                // Usar fecha en zona horaria RD para que coincida con el filtro de index.php
                $fechaRD = new DateTime('now', new DateTimeZone('America/Santo_Domingo'));
                $fechaPub = $fechaRD->format('Y-m-d H:i:s');
                
                // Generar imagen con IA basada en el contenido
                $imagenGenerada = null;
                if (!empty($robot['contenido_generado'])) {
                    $imagenGenerada = generarImagenNoticia($robot['titulo_generado'], $robot['contenido_generado']);
                }
                $imagenFinal = $imagenGenerada ?: $robot['imagen_url'];
                
                // Usar contenido COMPLETO como descripción para mayor alcance SEO
                $descripcionCompleta = $robot['contenido_generado'];
                
                $stmt2 = $pdo->prepare("INSERT INTO noticias (titulo, contenido, descripcion, fecha_publicacion, autor, categoria, imagen) VALUES (?, ?, ?, ?, 'Robot IA', ?, ?)");
                $stmt2->execute([$robot['titulo_generado'], $robot['contenido_generado'], $descripcionCompleta, $fechaPub, $robot['categoria'], $imagenFinal]);
            } catch (Exception $e) {
                error_log("Error publicando noticia robot: " . $e->getMessage());
                $mensaje_error = "Error al publicar en portada: " . $e->getMessage();
            }
        }
        header("Location: admin.php?robot_ok=1");
        exit();
    }
    if (isset($_POST['robot_editar'])) {
        $id = (int)$_POST['robot_id'];
        $stmt = $pdo->prepare("UPDATE noticias_robot SET estado='editando', titulo_generado=?, contenido_generado=?, descripcion_generada=?, categoria=? WHERE id=?");
        $stmt->execute([$_POST['titulo'], $_POST['contenido'], $_POST['descripcion'], $_POST['categoria'], $id]);
        header("Location: admin.php?robot_edit_ok=1");
        exit();
    }
    if (isset($_POST['robot_rechazar'])) {
        $id = (int)$_POST['robot_id'];
        $stmt = $pdo->prepare("UPDATE noticias_robot SET estado='rechazado' WHERE id=?");
        $stmt->execute([$id]);
        header("Location: admin.php?robot_rechazado=1");
        exit();
    }
}

// Capturar mensaje de éxito mediante parámetro GET tras la redirección
if (isset($_GET['exito']) && $_GET['exito'] == 1) {
    $mensaje_exito = "¡Podcast publicado y notificación enviada con éxito!";
}
if (isset($_GET['robot_ok'])) {
    $mensaje_exito = "✅ Noticia del robot publicada correctamente en la portada.";
}
if (isset($_GET['robot_edit_ok'])) {
    $mensaje_exito = "✏️ Noticia editada y guardada como pendiente.";
}
if (isset($_GET['robot_rechazado'])) {
    $mensaje_exito = "🗑️ Noticia rechazada y removida de la cola.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Periódico Digital</title>
<style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 0; }
        header { background: #1b263b; color: white; padding: 14px 18px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; }
        header h1 { margin: 0; font-size: 19px; }
        .nav-links { display: flex; gap: 8px; flex-wrap: wrap; }
        .nav-links a { color: white; text-decoration: none; font-size: 13px; background: #007bff; padding: 10px 16px; border-radius: 6px; min-height: 40px; display: inline-flex; align-items: center; justify-content: center; }
        .nav-links a.logout { background: #dc3545; }
        .nav-links a:active { transform: scale(0.98); }
        .container { max-width: 1100px; margin: 18px auto; padding: 0 12px; }
        
        .section-box { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 24px; }
        .section-box h2 { margin-top: 0; color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; min-width: 360px; table-layout: fixed; }
        th, td { padding: 6px 6px; text-align: left; border-bottom: 1px solid #eee; font-size: 12px; }
        th { background-color: #f8f9fa; color: #333; font-weight: 600; }
        tr:hover td { background: #fafafa; }
        td { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        td:first-child { white-space: normal; width: 40px; }
        td:nth-child(2) { width: 35%; max-width: 220px; }
        td:nth-child(3) { width: 18%; max-width: 120px; }
        td.acciones-td { width: 140px; white-space: nowrap; }
        
        .btn { padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; border: none; cursor: pointer; min-height: 36px; transition: all 0.12s ease; }
        .btn-edit { background: #ffc107; color: #333; }
        .btn-edit:hover { background: #e0a800; }
        .btn-delete { background: #dc3545; color: white; }
        .btn-delete:hover { background: #c82333; }
        .btn-add { background: #28a745; color: white; font-size: 12px; padding: 8px 14px; border: none; cursor: pointer; }
        .btn-add:hover { background: #218838; }
        .btn:active { transform: scale(0.96); }
        
        .acciones-td { white-space: nowrap; }
        .acciones-td .btn { margin-right: 4px; margin-bottom: 0; padding: 4px 8px; font-size: 11px; min-height: 28px; }
        
        /* Estilos para formularios integrados en el panel */
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; color: #333; font-size: 14px; }
        .form-group input[type="text"], .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; box-sizing: border-box; }
        
        /* Mobile responsive */
        @media (max-width: 768px) {
            header { padding: 12px; }
            header h1 { font-size: 17px; }
            .nav-links { width: 100%; justify-content: center; margin-top: 8px; gap: 8px; }
            .nav-links a { padding: 10px 16px; font-size: 13px; flex: 1; text-align: center; min-height: 40px; }
            .container { padding: 0 10px; margin: 12px auto; }
            .section-box { padding: 14px; border-radius: 8px; }
            .section-box h2 { font-size: 15px; }
            .btn { padding: 10px 14px; font-size: 12px; min-height: 36px; }
            .btn-add { width: 100%; justify-content: center; padding: 10px 14px; }
            .acciones-td { display: inline-flex; gap: 4px; }
            .acciones-td .btn { width: auto; min-width: 60px; padding: 6px 10px; font-size: 11px; }
            .form-group input, .form-group textarea, .form-group select { font-size: 16px; }
            table { font-size: 12px; }
            th, td { padding: 8px 6px; }
            .nav-links a { padding: 10px 16px; font-size: 12px; min-height: 40px; }
        }
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
                <div class="table-wrapper">
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
                </div>
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
                <div class="table-wrapper">
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
                                <td><?php echo htmlspecialchars($anuncio['titulo']); ?></td>
                                <td style="text-transform: capitalize;"><?php echo $anuncio['posicion']; ?></td>
                                <td>
                                    <?php if ($anuncio['activo']): ?>
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
                </div>
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

        <!-- SECCIÓN: NOTICIAS DEL ROBOT -->
        <div class="section-box">
            <h2>
                <span>🤖 Noticias del Robot (Cola de Revisión)</span>
                <button type="button" class="btn btn-add" onclick="ejecutarRobot()">🔄 Ejecutar Robot Ahora</button>
            </h2>
            <p style="font-size: 13px; color: #666; margin-bottom: 15px;">
                El robot busca noticias en fuentes RSS de RD y Google News, genera artículos completos (5+ párrafos) con IA (OpenAI/Claude) o plantilla profesional, detecta 🔴 <strong>Breaking News</strong>, descarga imágenes y prioriza fuentes confiables.
                <strong>Meta: 50+ noticias/día</strong> de última hora, política, economía, deportes, sociedad, cultura, tecnología.
                <br><small>⚙️ Para activar IA: configurar <code>OPENAI_API_KEY</code> o <code>CLAUDE_API_KEY</code> en Variables de Entorno de Render.</small>
            </p>
            
            <?php if (empty($noticias_robot)): ?>
                <p style="color: #666; font-size: 14px;">No hay noticias pendientes. Ejecuta el robot para llenar la cola.</p>
            <?php else: ?>
                <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título Generado</th>
                            <th>Fuente / Score</th>
                            <th>Cat.</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($noticias_robot as $nr): ?>
                            <tr style="<?php echo $nr['es_breaking'] ? 'background:#fff8f8;' : ''; ?>">
                                <td><?php echo $nr['id']; ?></td>
                                <td style="max-width: 300px;">
                                    <?php if ($nr['es_breaking']): ?>
                                        <span style="color:#dc3545;font-weight:bold;">🔴 </span>
                                    <?php endif; ?>
                                    <strong><?php echo htmlspecialchars($nr['titulo_generado'] ?? $nr['titulo_original']); ?></strong>
                                    <br><small style="color: #888;">Original: <?php echo htmlspecialchars(mb_substr($nr['titulo_original'], 0, 80)); ?>...</small>
                                </td>
                                <td style="font-size:11px;">
                                    <?php echo htmlspecialchars($nr['fuente_nombre']); ?>
                                    <br><span style="color:#007bff;">Score: <?php echo (int)($nr['fuente_score'] ?? 0); ?>/10</span>
                                </td>
                                <td>
                                    <span class="badge" style="background: #e9ecef; padding: 2px 6px; border-radius: 3px; font-size: 10px;"><?php echo htmlspecialchars($nr['categoria']); ?></span>
                                </td>
                                <td><?php echo date('d/m H:i', strtotime($nr['fecha_creacion'])); ?></td>
                                <td>
                                    <span class="badge" style="background: <?php echo $nr['estado']==='pendiente'?'#fff3cd':($nr['estado']==='editando'?'#cce5ff':'#d4edda'); ?>; color: #333; padding: 2px 8px; border-radius: 12px; font-size: 11px;">
                                        <?php echo ucfirst($nr['estado']); ?>
                                    </span>
                                </td>
                                <td class="acciones-td">
                                    <!-- Botón Ver/Editar -->
                                    <button type="button" class="btn btn-edit" onclick="abrirModalEditar(<?php echo htmlspecialchars(json_encode($nr), ENT_QUOTES, 'UTF-8'); ?>)">✏️ Editar</button>
                                    <!-- Botón Publicar -->
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Publicar esta noticia en la portada?');">
                                        <input type="hidden" name="robot_id" value="<?php echo $nr['id']; ?>">
                                        <button type="submit" name="robot_publicar" class="btn" style="background:#28a745;color:white;padding:4px 10px;font-size:11px;min-height:28px;">🚀 Publicar</button>
                                    </form>
                                    <!-- Botón Rechazar -->
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Rechazar y eliminar de la cola?');">
                                        <input type="hidden" name="robot_id" value="<?php echo $nr['id']; ?>">
                                        <button type="submit" name="robot_rechazar" class="btn" style="background:#6c757d;color:white;padding:4px 10px;font-size:11px;min-height:28px;">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Modal para Editar Noticia del Robot -->
    <div id="modalEditar" class="modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;overflow-y:auto;">
        <div style="background:white;max-width:800px;margin:50px auto;padding:30px;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,0.2);position:relative;">
            <button onclick="cerrarModal()" style="position:absolute;top:10px;right:15px;background:none;border:none;font-size:24px;cursor:pointer;color:#666;">&times;</button>
            <h3 style="margin-top:0;color:#333;">✏️ Editar Noticia del Robot</h3>
            <form id="formEditarRobot" method="POST">
                <input type="hidden" name="robot_id" id="edit_id">
                <div class="form-group">
                    <label>Título:</label>
                    <input type="text" name="titulo" id="edit_titulo" required style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;font-size:14px;box-sizing:border-box;">
                </div>
                <div class="form-group">
                    <label>Categoría:</label>
                    <select name="categoria" id="edit_categoria" style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;font-size:14px;box-sizing:border-box;">
                        <option value="Política">🏛️ Política</option>
                        <option value="Economía">💰 Economía</option>
                        <option value="Deportes">⚽ Deportes</option>
                        <option value="Sociedad">👥 Sociedad</option>
                        <option value="Internacional">🌍 Internacional</option>
                        <option value="General">📄 General</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Descripción (resumen para listados):</label>
                    <textarea name="descripcion" id="edit_descripcion" rows="3" required style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;font-size:14px;box-sizing:border-box;"></textarea>
                </div>
                <div class="form-group">
                    <label>Contenido completo (mín. 4 párrafos separados por línea en blanco):</label>
                    <textarea name="contenido" id="edit_contenido" rows="12" required style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;font-size:14px;box-sizing:border-box;font-family:inherit;"></textarea>
                </div>
                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                    <button type="button" class="btn" onclick="cerrarModal()" style="background:#6c757d;color:white;">Cancelar</button>
                    <button type="submit" name="robot_editar" class="btn btn-edit">💾 Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModalEditar(nr) {
            document.getElementById('edit_id').value = nr.id;
            document.getElementById('edit_titulo').value = nr.titulo_generado || nr.titulo_original;
            document.getElementById('edit_categoria').value = nr.categoria || 'General';
            document.getElementById('edit_descripcion').value = nr.descripcion_generada || '';
            document.getElementById('edit_contenido').value = nr.contenido_generado || '';
            document.getElementById('modalEditar').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        function cerrarModal() {
            document.getElementById('modalEditar').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        function ejecutarRobot() {
            if (!confirm('¿Ejecutar robot ahora? Puede tardar 30-60 segundos.')) return;
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = '⏳ Ejecutando...';
            
            fetch('robot_ejecutar.php', {method:'POST'})
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    btn.textContent = '🔄 Ejecutar Robot Ahora';
                    if (data.ok) {
                        alert('✅ Robot completado: ' + data.nuevas + ' nuevas, ' + data.duplicadas + ' duplicadas');
                        location.reload();
                    } else {
                        alert('❌ Error: ' + (data.error || 'Desconocido'));
                    }
                })
                .catch(e => {
                    btn.disabled = false;
                    btn.textContent = '🔄 Ejecutar Robot Ahora';
                    alert('❌ Error de conexión: ' + e.message);
                });
        }
        // Cerrar modal con ESC
        document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarModal(); });
        // Cerrar modal click fuera
        document.getElementById('modalEditar').addEventListener('click', e => { if (e.target.id === 'modalEditar') cerrarModal(); });
    </script>

</body>
</html>



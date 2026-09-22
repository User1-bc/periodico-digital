<?php
// Forzar la zona horaria local de Rep+�blica Dominicana para evitar desfases de fecha
date_default_timezone_set('America/Santo_Domingo');

require_once 'conexion.php';

// Auto-migraci�n: crear tablas si no existen
try {
    $tableCheck = $pdo->query("SELECT 1 FROM information_schema.tables WHERE table_schema='public' AND table_name='noticias'")->fetchColumn();
    $mediaCheck = $pdo->query("SELECT 1 FROM information_schema.tables WHERE table_schema='public' AND table_name='anuncios_multimedia'")->fetchColumn();
    $adminCheck = $pdo->query("SELECT 1 FROM information_schema.tables WHERE table_schema='public' AND table_name='admin_users'")->fetchColumn();
    $robotCheck = $pdo->query("SELECT 1 FROM information_schema.tables WHERE table_schema='public' AND table_name='noticias_robot'")->fetchColumn();
    
    $colBreaking = $pdo->query("SELECT 1 FROM information_schema.columns WHERE table_schema='public' AND table_name='noticias_robot' AND column_name='es_breaking'")->fetchColumn();
    $colScore = $pdo->query("SELECT 1 FROM information_schema.columns WHERE table_schema='public' AND table_name='noticias_robot' AND column_name='fuente_score'")->fetchColumn();
    
    if (!$tableCheck || !$mediaCheck || !$adminCheck || !$robotCheck || !$colBreaking || !$colScore) {
        $sql = file_get_contents(__DIR__ . '/schema.sql');
        $pdo->exec($sql);
        error_log("Migraci�n ejecutada: tablas/columnas creadas/actualizadas");
    }
} catch (PDOException $e) {
    error_log("Error en auto-migraci�n: " . $e->getMessage());
    // Forzar recreaci�n si falla
    try {
        $pdo->exec("DROP TABLE IF EXISTS noticias_robot, noticias_multimedia, anuncios_multimedia, podcasts, anuncios, noticias CASCADE;");
        $sql = file_get_contents(__DIR__ . '/schema.sql');
        $pdo->exec($sql);
        error_log("Migraci�n forzada completada");
    } catch (PDOException $e2) {
        error_log("Error en migraci�n forzada: " . $e2->getMessage());
    }
}

// 1. Determinar la fecha a consultar (si el usuario seleccion+� una fecha, usamos esa; si no, usamos la fecha de hoy local)
$fecha_hoy = date('Y-m-d');
$fecha_seleccionada = isset($_GET['fecha']) && !empty($_GET['fecha']) ? $_GET['fecha'] : $fecha_hoy;

// 2. Obtener noticias filtradas por la fecha seleccionada
$stmt_noticias = $pdo->prepare("SELECT * FROM noticias WHERE DATE(fecha_publicacion) = ? ORDER BY fecha_publicacion DESC");
$stmt_noticias->execute([$fecha_seleccionada]);
$noticias = $stmt_noticias->fetchAll(PDO::FETCH_ASSOC);

// Obtener anuncios activos para la columna izquierda
$stmt_izq = $pdo->query("SELECT * FROM anuncios WHERE posicion = 'izquierda' AND activo = true");
$anuncios_izq = $stmt_izq->fetchAll(PDO::FETCH_ASSOC);

// Obtener anuncios activos para el carrete superior (banner fijo)
$stmt_top = $pdo->query("SELECT a.*, am.archivo, am.tipo FROM anuncios a LEFT JOIN anuncios_multimedia am ON a.id = am.anuncio_id WHERE a.posicion = 'carrete_superior' AND a.activo = true ORDER BY a.id, am.orden");
$anuncios_top_raw = $stmt_top->fetchAll(PDO::FETCH_ASSOC);

// Agrupar multimedia por anuncio para el carrete superior
$anuncios_top = [];
foreach ($anuncios_top_raw as $row) {
    $id = $row['id'];
    if (!isset($anuncios_top[$id])) {
        $anuncios_top[$id] = [
            'id' => $row['id'],
            'titulo' => $row['titulo'],
            'enlace_destino' => $row['enlace_destino'],
            'imagen_banner' => $row['imagen_banner'],
            'multimedia' => []
        ];
    }
    if ($row['archivo']) {
        $anuncios_top[$id]['multimedia'][] = ['archivo' => $row['archivo'], 'tipo' => $row['tipo']];
    }
}
$anuncios_top = array_values($anuncios_top);

// Obtener los podcasts m+�s recientes
$stmt_podcasts = $pdo->query("SELECT * FROM podcasts ORDER BY id DESC LIMIT 3");
$podcasts = $stmt_podcasts->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Periodico Digital RD</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 0; }
        
header { 
            background: #1b263b; 
            color: white; 
            padding: 12px 16px; 
            display: grid; 
            grid-template-columns: auto 1fr auto; 
            align-items: center; 
            gap: 10px; 
            border-bottom: 3px solid #007bff; 
            position: relative;
        }
        
        .header-left { justify-self: start; }
        
        .header-title { text-align: center; justify-self: center; min-width: 0; }
        .header-title h1 { margin: 0; font-size: 20px; font-weight: bold; letter-spacing: 0.3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .header-title p { margin: 2px 0 0; color: #adb5bd; font-size: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        
        .header-right { justify-self: end; display: flex; align-items: center; gap: 8px; flex-wrap: nowrap; }

.btn-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        .btn-icon:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(0,0,0,0.3);
        }
        .btn-icon:active {
            transform: scale(0.95);
        }
        
        .btn-whatsapp-top { 
            background: #25d366;
            color: white;
        }
        .btn-whatsapp-top:hover { background: #20ba5a; }
        
        .btn-notifications {
            background: #ffc107;
            color: #212529;
        }
        .btn-notifications:hover { background: #e0a800; }
        .btn-notifications.active {
            background: #28a745;
            color: white;
        }
        
        .btn-icon svg {
            width: 22px;
            height: 22px;
            stroke-width: 2.5;
        }
            color: white;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }
        
        .date-bar {
            background: #ffffff;
            max-width: 1200px;
            margin: 20px auto 0 auto;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .date-bar form {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .date-bar input[type="date"] {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            outline: none;
        }
        .btn-filter {
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            text-decoration: none;
            transition: background 0.2s;
            display: inline-block;
        }
        .btn-filter:hover { background: #0056b3; }
        .btn-today {
            background: #6c757d;
            color: white;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
        }
        .btn-today:hover { background: #5a6268; }
        .current-view-text {
            font-weight: bold;
            color: #333;
            font-size: 15px;
        }

.main-container { display: flex; flex-wrap: wrap; max-width: 1200px; margin: 20px auto; gap: 20px; padding: 0 10px; }
        
        aside.col-sidebar-left { flex: 1; min-width: 250px; display: flex; flex-direction: column; gap: 15px; order: 1; }
        main.col-center { flex: 2; min-width: 300px; order: 2; }
        aside.col-sidebar-right { flex: 1; min-width: 250px; display: flex; flex-direction: column; gap: 15px; order: 3; }
        
        .card { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); text-align: center; }
        .card img, .card video { max-width: 100%; height: auto; border-radius: 4px; display: block; margin: 0 auto; }
        
        .widget-title { font-size: 16px; font-weight: bold; color: #333; margin-bottom: 10px; border-bottom: 2px solid #007bff; padding-bottom: 5px; text-align: left; }
        .crypto-item, .fx-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; font-size: 14px; }
        .crypto-item:last-child, .fx-item:last-child { border-bottom: none; }
        .price { font-weight: bold; color: #28a745; }

        .noticia-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .noticia-card h2 { margin-top: 0; color: #222; font-size: 22px; }
        .fecha { font-size: 12px; color: #888; margin-bottom: 10px; }
        
.carrete-multimedia { display: flex; overflow-x: auto; gap: 10px; margin: 15px 0; padding-bottom: 5px; scroll-snap-type: x mandatory; }
        .carrete-multimedia img, .carrete-multimedia video { flex: 0 0 auto; max-height: 380px; width: 100%; object-fit: contain; border-radius: 6px; background: #000; scroll-snap-align: center; }
        
        .carrete-anuncio { display: flex; overflow-x: auto; gap: 8px; margin: 5px 0; padding-bottom: 5px; scroll-snap-type: x mandatory; }
        .carrete-anuncio img, .carrete-anuncio video { flex: 0 0 auto; height: 180px; width: 100%; object-fit: cover; border-radius: 4px; background: #000; scroll-snap-align: center; }
        
        /* Fix for sidebar ads - single image/video without carousel */
        .sidebar-ad-media { display: block; width: 100%; max-width: 100%; }
        .sidebar-ad-media img,
        .sidebar-ad-media video { 
            width: 100%; 
            height: auto; 
            max-height: 250px; 
            object-fit: cover; 
            border-radius: 4px; 
            background: #000; 
            display: block;
        }

.descripcion { color: #444; line-height: 1.6; white-space: pre-line; text-align: left; }

/* TOP CAROUSEL - Banner Superior Fijo (ancho = contenedor principal) */
.top-carousel-container {
    position: sticky;
    top: 0;
    z-index: 1000;
    margin: 0 auto;
    max-width: 1200px;
    padding: 0 10px; /* coincide con padding del main-container */
    overflow: hidden;
    border-radius: 0 0 12px 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    background: #000;
}
.top-carousel-container {
    position: sticky;
    top: 0;
    z-index: 1000;
    width: 100vw;
    left: 50%;
    right: 50%;
    margin-left: -50vw;
    margin-right: -50vw;
    overflow: hidden;
    border-radius: 0;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    background: #000;
    aspect-ratio: 16/9;
    max-height: 200px;
}
.top-carousel-track {
    display: flex;
    transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    width: 100%;
    height: 100%;
}
.top-carousel-slide {
    flex: 0 0 100%;
    width: 100%;
    height: 100%;
    position: relative;
    overflow: hidden;
}
.top-carousel-slide img,
.top-carousel-slide video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.top-carousel-link {
    position: absolute;
    inset: 0;
    z-index: 2;
}

/* Flechas modernas: solo SVG, sin c�rculo */
.top-carousel-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 44px;
    height: 44px;
    border: none;
    border-radius: 50%;
    background: rgba(0,0,0,0.35);
    color: #fff;
    font-size: 0; /* ocultar texto, solo SVG */
    cursor: pointer;
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    opacity: 0.85;
}
.top-carousel-btn:hover,
.top-carousel-btn:focus {
    background: rgba(0,0,0,0.55);
    opacity: 1;
    transform: translateY(-50%) scale(1.08);
}
.top-carousel-btn::before {
    content: '';
    display: block;
    width: 22px;
    height: 22px;
    background: currentColor;
    mask: var(--arrow-svg) center / contain no-repeat;
    -webkit-mask: var(--arrow-svg) center / contain no-repeat;
}
.top-carousel-prev { left: 12px; --arrow-svg: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M15 18l-6-6 6-6'/%3E%3C/svg%3E"); }
.top-carousel-next { right: 12px; --arrow-svg: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M9 18l6-6-6-6'/%3E%3C/svg%3E"); }

/* Puntos modernos: barras finas elegantes */
.top-carousel-dots {
    position: absolute;
    bottom: 16px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 6px;
    z-index: 10;
}
.top-carousel-dot {
    width: 28px;
    height: 4px;
    border-radius: 2px;
    background: rgba(255,255,255,0.35);
    cursor: pointer;
    transition: all 0.25s ease;
    border: none;
    padding: 0;
}
.top-carousel-dot:hover { background: rgba(255,255,255,0.6); }
.top-carousel-dot.active {
    background: #fff;
    width: 40px;
    box-shadow: 0 0 8px rgba(255,255,255,0.4);
}
@media (max-width: 768px) {
    .top-carousel-container { max-height: 180px; }
    .top-carousel-btn { width: 40px; height: 40px; }
    .top-carousel-prev { left: 8px; }
    .top-carousel-next { right: 8px; }
    .top-carousel-dots { bottom: 12px; gap: 5px; }
    .top-carousel-dot { width: 22px; height: 3px; }
    .top-carousel-dot.active { width: 32px; }
}
@media (max-width: 480px) {
    .top-carousel-container { max-height: 160px; }
    .top-carousel-btn { width: 36px; height: 36px; }
}

@media (max-width: 768px) {
    .main-container { flex-direction: column; }
    header { 
        grid-template-columns: auto 1fr auto; 
        padding: 10px 12px;
        gap: 8px;
    }
    .header-left { justify-self: start; }
    .header-title { 
        justify-self: center; 
        text-align: center; 
        min-width: 0;
    }
    .header-title h1 { font-size: 17px; }
    .header-title p { font-size: 10px; }
    .header-right { 
        justify-self: end; 
        gap: 6px;
    }
    .btn-whatsapp-top { 
        padding: 8px 14px; 
        font-size: 12px; 
        gap: 6px;
        min-height: 36px;
    }
    .btn-notifications {
        padding: 8px 12px;
        font-size: 12px;
        gap: 6px;
        min-height: 36px;
    }
    .btn-whatsapp-top svg, .btn-notifications svg { width: 16px; height: 16px; }
    .header-left img { height: 40px; }
    .date-bar { flex-direction: column; align-items: stretch; text-align: center; }
    .date-bar form { justify-content: center; }
}

@media (max-width: 480px) {
    header { padding: 8px 10px; gap: 6px; }
    .header-left img { height: 36px; }
    .header-title h1 { font-size: 15px; }
.header-title p { font-size: 9px; }
    .btn-icon { width: 38px; height: 38px; }
    .btn-icon svg { width: 20px; height: 20px; }
}
</style>
</head>
<body>

<header>
        <div class="header-left">
            <img src="uploads/125688.png" alt="Periodico Digital RD" style="height: 50px; width: auto; max-width: 100%;">
        </div>

        <div class="header-title">
            <h1>Periodico Digital RD</h1>
            <p>La fuente mas confiable de Republica Dominicana</p>
        </div>

<div class="header-right">
            <button id="btn-suscripcion" class="btn-icon btn-notifications" onclick="toggleSuscripcion()" aria-label="Suscribirse a notificaciones">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
            </button>

            <a href="https://wa.me/18295482901?text=Hola,%20deseo%20enviar%20una%20informacion%20o%20consultar%20sobre%20espacios%20publicitarios." target="_blank" class="btn-icon btn-whatsapp-top" aria-label="Contactar por teléfono">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                </svg>
            </a>
        </div>
    </header>

    <div class="date-bar">
<div class="current-view-text">
            Mostrando Noticias del: <span style="color: #007bff;"><?php echo date('d/m/Y', strtotime($fecha_seleccionada)); ?></span>
        </div>
        <form method="GET" action="index.php">
            <input type="date" name="fecha" value="<?php echo htmlspecialchars($fecha_seleccionada); ?>">
            <button type="submit" class="btn-filter">Ver Fecha</button>
            <?php if ($fecha_seleccionada !== $fecha_hoy): ?>
                <a href="index.php?fecha=<?php echo $fecha_hoy; ?>" class="btn-today">Ver Hoy</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- CARRETE SUPERIOR - Banner Fijo Superior -->
    <?php if (!empty($anuncios_top)): ?>
    <div class="top-carousel-container" id="topCarousel">
        <div class="top-carousel-track" id="topCarouselTrack">
            <?php foreach ($anuncios_top as $ad): 
                $items = $ad['multimedia'];
                if (empty($items) && !empty($ad['imagen_banner'])) {
                    $items = [['archivo' => $ad['imagen_banner'], 'tipo' => 'imagen']];
                }
                if (!empty($items)): ?>
                    <div class="top-carousel-slide" data-ad-id="<?php echo $ad['id']; ?>">
                        <?php foreach ($items as $idx => $item): ?>
                            <?php if ($item['tipo'] == 'video'): ?>
                                <video src="<?php echo htmlspecialchars($item['archivo']); ?>" autoplay muted loop playsinline preload="metadata" class="sidebar-ad-media"></video>
                            <?php else: ?>
                                <img src="<?php echo htmlspecialchars($item['archivo']); ?>" alt="<?php echo htmlspecialchars($ad['titulo']); ?>">
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if (!empty($ad['enlace_destino']) && $ad['enlace_destino'] !== '#'): ?>
                            <a href="<?php echo htmlspecialchars($ad['enlace_destino']); ?>" target="_blank" class="top-carousel-link"></a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php if (count($anuncios_top) > 1 || (count($anuncios_top) === 1 && count($anuncios_top[0]['multimedia']) > 1)): ?>
            <button class="top-carousel-btn top-carousel-prev" id="topCarouselPrev" aria-label="Anterior">&#10094;</button>
            <button class="top-carousel-btn top-carousel-next" id="topCarouselNext" aria-label="Siguiente">&#10095;</button>
            <div class="top-carousel-dots" id="topCarouselDots"></div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="main-container">
        
<aside class="col-sidebar col-sidebar-left">
            <div class="card">
                <div class="widget-title">Espacio Publicitario</div>
                <?php if (empty($anuncios_izq)): ?>
                    <p style="font-size: 13px; color: #777;">Espacio publicitario disponible</p>
                <?php else: ?>
<?php foreach ($anuncios_izq as $ad): ?>
                        <div style="margin-bottom: 20px; border-bottom: 1px dashed #ddd; padding-bottom: 15px;">
                            <?php 
                                $stmt_ad_media = $pdo->prepare("SELECT * FROM anuncios_multimedia WHERE anuncio_id = ?");
                                $stmt_ad_media->execute([$ad['id']]);
                                $ad_archivos = $stmt_ad_media->fetchAll(PDO::FETCH_ASSOC);
                            ?>

<?php if (!empty($ad_archivos)): ?>
                                <?php $hasLink = !empty($ad['enlace_destino']) && $ad['enlace_destino'] !== '#'; ?>
                                <?php if ($hasLink): ?><a href="<?php echo htmlspecialchars($ad['enlace_destino']); ?>" target="_blank" style="text-decoration: none;"><?php endif; ?>
                                    <div class="carrete-anuncio sidebar-carousel" id="sidebar-carousel-<?php echo $ad['id']; ?>">
                                        <?php foreach ($ad_archivos as $item): ?>
                                            <?php if ($item['tipo'] == 'video'): ?>
                                                <video src="<?php echo htmlspecialchars($item['archivo']); ?>" autoplay muted loop playsinline preload="metadata" class="sidebar-ad-media"></video>
                                            <?php else: ?>
                                                <img src="<?php echo htmlspecialchars($item['archivo']); ?>" alt="Art�culo" class="sidebar-ad-media">
                                            <?php endif; ?>
<?php endforeach; ?>
</div>
                                <?php if ($hasLink): ?></a><?php endif; ?>
                            <?php elseif (!empty($ad['imagen_banner'])): ?>
                                <?php $hasLink = !empty($ad['enlace_destino']) && $ad['enlace_destino'] !== '#'; ?>
                                <?php if ($hasLink): ?><a href="<?php echo htmlspecialchars($ad['enlace_destino']); ?>" target="_blank"><?php endif; ?>
                                    <?php 
                                        $extension = strtolower(pathinfo($ad['imagen_banner'], PATHINFO_EXTENSION));
                                        $es_video = in_array($extension, ['mp4', 'webm', 'ogg', 'mov']);
                                    ?>
                                    <?php if ($es_video): ?>
                                        <video src="<?php echo htmlspecialchars($ad['imagen_banner']); ?>" autoplay muted loop playsinline preload="metadata" class="sidebar-ad-media"></video>
                                    <?php else: ?>
                                        <img src="<?php echo htmlspecialchars($ad['imagen_banner']); ?>" alt="Anuncio" class="sidebar-ad-media">
<?php endif; ?>
                                <?php if ($hasLink): ?></a><?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
<?php endif; ?>
            </div>
        </aside>

        <main class="col-center">
            <?php if (empty($noticias)): ?>
                <div class="noticia-card" style="text-align: center; padding: 40px 20px;">
                    <h3 style="color: #555;">No hay noticias publicadas para el día <?php echo date('d/m/Y', strtotime($fecha_seleccionada)); ?>.</h3>
                    <p style="color: #777; font-size: 14px;">Intenta seleccionando otra fecha en el calendario superior o regresa a las noticias de hoy.</p>
                    <a href="index.php?fecha=<?php echo $fecha_hoy; ?>" class="btn-filter" style="display: inline-block; text-decoration: none; margin-top: 10px;">Ver noticias de Hoy</a>
                </div>
            <?php else: ?>
                <?php foreach ($noticias as $noticia): ?>
                    <article class="noticia-card">
                        <h2><?php echo htmlspecialchars($noticia['titulo']); ?></h2>
                        <div class="fecha">Publicado el: <?php echo $noticia['fecha_publicacion']; ?></div>
                        
                        <?php 
                            $stmt_media = $pdo->prepare("SELECT * FROM noticias_multimedia WHERE noticia_id = ?");
                            $stmt_media->execute([$noticia['id']]);
                            $archivos_multimedia = $stmt_media->fetchAll(PDO::FETCH_ASSOC);
                        ?>

<?php if (!empty($archivos_multimedia)): ?>
                            <div class="carrete-multimedia">
                                <?php foreach ($archivos_multimedia as $item): ?>
                                    <?php if ($item['tipo'] == 'video'): ?>
                                        <video controls preload="metadata" playsinline class="sidebar-ad-media">
                                            <source src="<?php echo htmlspecialchars($item['archivo']); ?>">
                                            Tu navegador no soporta la reproducci�n de videos.
                                        </video>
                                    <?php else: ?>
                                        <img src="<?php echo htmlspecialchars($item['archivo']); ?>" alt="Multimedia de la noticia" class="sidebar-ad-media">
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
<?php endif; ?>

                        <div class="descripcion"><?php echo nl2br(htmlspecialchars($noticia['descripcion'])); ?></div>
                    </article>
                <?php endforeach; ?>
<?php endif; ?>
        </main>

        <aside class="col-sidebar col-sidebar-right">
            <div class="card">
                <div class="widget-title">Economia</div>
                <div class="crypto-item">
                    <span>Bitcoin (BTC):</span>
                    <span class="price" id="btc-price">Cargando...</span>
                </div>
                <div class="crypto-item">
                    <span>Ethereum (ETH):</span>
                    <span class="price" id="eth-price">Cargando...</span>
                </div>
                <div class="fx-item">
                    <span>Oro (XAU/USD):</span>
                    <span class="price" id="gold-price">Cargando...</span>
                </div>
                <div class="fx-item">
                    <span>USD / DOP:</span>
                    <span class="price" id="usd-dop">Cargando...</span>
                </div>
                <div class="fx-item">
                    <span>EUR / DOP:</span>
                    <span class="price" id="eur-dop">Cargando...</span>
                </div>
            </div>

            <?php if (!empty($podcasts)): ?>
                <div class="card" style="text-align: left;">
                    <div class="widget-title">Últimos Envivos</div>
                    
                    <?php foreach ($podcasts as $pod): ?>
                        <div style="margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
                            <h4 style="margin: 0 0 8px 0; color: #333; font-size: 15px;"><?php echo htmlspecialchars($pod["titulo"]); ?></h4>
                            
                            <div style="position: relative; width: 100%; padding-bottom: 56.25%; height: 0; margin-bottom: 10px;">
                                <iframe src="<?php echo htmlspecialchars($pod["url_youtube"]); ?>" 
                                        style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border-radius: 6px; border: none;" 
                                        allowfullscreen>
                                </iframe>
                            </div>
                            
                            <p style="font-size: 12px; color: #555; line-height: 1.4; margin: 0;"><?php echo nl2br(htmlspecialchars($pod["descripcion"])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </aside>

    </div>

    <script>
        // Registrar Service Worker al cargar la p+�gina
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then(reg => console.log('Service Worker registrado con +�xito:', reg))
                    .catch(err => console.error('Error al registrar el Service Worker:', err));
            });
        }

        async function fetchMarketData() {
            try {
                let responseCrypto = await fetch('https://api.coingecko.com/api/v3/simple/price?ids=bitcoin,ethereum,pax-gold&vs_currencies=usd');
                let dataCrypto = await responseCrypto.json();
                
                if(dataCrypto.bitcoin) {
                    document.getElementById('btc-price').innerText = "$" + dataCrypto.bitcoin.usd.toLocaleString() + " USD";
                }
                if(dataCrypto.ethereum) {
                    document.getElementById('eth-price').innerText = "$" + dataCrypto.ethereum.usd.toLocaleString() + " USD";
                }
                if(dataCrypto['pax-gold']) {
                    document.getElementById('gold-price').innerText = "$" + dataCrypto['pax-gold'].usd.toLocaleString() + " USD";
                } else {
                    document.getElementById('gold-price').innerText = "No disponible";
                }

                let responseFx = await fetch('https://open.er-api.com/v6/latest/USD');
                let dataFx = await responseFx.json();
                
                if(dataFx && dataFx.rates) {
                    let rateUSD_DOP = dataFx.rates.DOP; 
                    let rateEUR_USD = dataFx.rates.EUR; 
                    
                    if(rateUSD_DOP) {
                        document.getElementById('usd-dop').innerText = "~ " + rateUSD_DOP.toFixed(2) + " RD$";
                        
                        if(rateEUR_USD) {
                            let rateEUR_DOP = rateUSD_DOP / rateEUR_USD;
                            document.getElementById('eur-dop').innerText = "~ " + rateEUR_DOP.toFixed(2) + " RD$";
                        }
                    }
                }
} catch (error) {
                console.log("Error al actualizar indicadores:", error);
                document.querySelectorAll('.price').forEach(el => {
                    if (el.innerText === 'Cargando...') el.innerText = 'Error';
                });
            }
        }
        
        fetchMarketData();
        setInterval(fetchMarketData, 60000);

document.addEventListener("DOMContentLoaded", () => {
            if (!("Notification" in window) || !('serviceWorker' in navigator)) {
                const btn = document.getElementById("btn-suscripcion");
                if(btn) btn.style.display = "none";
                return;
            }

            if (Notification.permission === "granted") {
                updateSuscripcionButtonState(true);
            }
        });

        // Clave VAPID p+�blica integrada para el env+�o de notificaciones
        const publicVapidKey = 'BDNidqse1xgK0WW5rCgFJx7jxeDeEB6fFH_FQZ2JPMplderSAXF8Tl3eqOZM0OW-Oe6GVJqbKb2XIqLGtwV3iQ4';

        function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);
            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }

        async function toggleSuscripcion() {
            if (!("Notification" in window)) {
                alert("Tu navegador no soporta notificaciones de escritorio.");
                return;
            }

            if (Notification.permission === "granted") {
                alert("Ya estás suscrito a las notificaciones.");
            } else if (Notification.permission !== "denied") {
                let permission = await Notification.requestPermission();
                if (permission === "granted") {
                    updateSuscripcionButtonState(true);
                    await suscribirUsuarioPush();
                }
            } else {
                alert("Las notificaciones están bloqueadas en la configuración de tu navegador.");
            }
        }

        async function suscribirUsuarioPush() {
            try {
                const registration = await navigator.serviceWorker.ready;
                const subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(publicVapidKey)
                });

                await fetch('guardar_suscripcion.php', {
                    method: 'POST',
                    body: JSON.stringify(subscription),
                    headers: {
                        'content-type': 'application/json'
                    }
                });

                console.log("Usuario suscrito a las push notifications correctamente.");
            } catch (error) {
                console.error("Error al suscribir al usuario:", error);
            }
        }

function updateSuscripcionButtonState(active) {
            const btn = document.getElementById("btn-suscripcion");
            if (btn) {
                if (active) {
                    btn.classList.add("active");
                    btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
                    btn.setAttribute('aria-label', 'Suscrito - Click para ver estado');
                } else {
                    btn.classList.remove("active");
                    btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>';
                    btn.setAttribute('aria-label', 'Suscribirse a notificaciones');
                }
            }
        }

// TOP CAROUSEL FUNCTIONALITY
        (function() {
            const track = document.getElementById('topCarouselTrack');
            const prevBtn = document.getElementById('topCarouselPrev');
            const nextBtn = document.getElementById('topCarouselNext');
            const dotsContainer = document.getElementById('topCarouselDots');
            
            if (!track) return; // No carousel on this page
            
            const slides = track.querySelectorAll('.top-carousel-slide');
            const slideCount = slides.length;
            if (slideCount <= 1) return;
            
            let currentIndex = 0;
            let slideTimeout;
            let currentVideo = null;
            
            // Create dots
            if (dotsContainer) {
                for (let i = 0; i < slideCount; i++) {
                    const dot = document.createElement('button');
                    dot.className = 'top-carousel-dot' + (i === 0 ? ' active' : '');
                    dot.setAttribute('aria-label', 'Slide ' + (i + 1));
                    dot.addEventListener('click', () => goToSlide(i));
                    dotsContainer.appendChild(dot);
                }
            }
            
            function updateDots() {
                if (!dotsContainer) return;
                const dots = dotsContainer.querySelectorAll('.top-carousel-dot');
                dots.forEach((dot, i) => {
                    dot.classList.toggle('active', i === currentIndex);
                });
            }
            
            function playCurrentSlideVideo() {
                // Clear any previous video handler
                if (currentVideo) {
                    currentVideo.onended = null;
                    currentVideo.pause();
                }
                
                const currentSlide = slides[currentIndex];
                const videos = currentSlide.querySelectorAll('video');
                
                if (videos.length > 0) {
                    currentVideo = videos[0];
                    currentVideo.currentTime = 0;
                    currentVideo.play().catch(() => {});
                    
                    currentVideo.onended = () => {
                        nextSlide();
                    };
                } else {
                    currentVideo = null;
                    // Images only - 5 seconds
                    slideTimeout = setTimeout(nextSlide, 5000);
                }
            }
            
            function goToSlide(index) {
                if (index < 0) index = slideCount - 1;
                if (index >= slideCount) index = 0;
                currentIndex = index;
                const translateX = -currentIndex * 100;
                track.style.transform = 'translateX(' + translateX + '%)';
                updateDots();
                playCurrentSlideVideo();
            }
            
            function nextSlide() {
                goToSlide(currentIndex + 1);
            }
            
            function prevSlide() {
                goToSlide(currentIndex - 1);
            }
            
            function stopAutoSlide() {
                clearTimeout(slideTimeout);
                if (currentVideo) {
                    currentVideo.onended = null;
                    currentVideo.pause();
                }
            }
            
            function startAutoSlide() {
                playCurrentSlideVideo();
            }
            
            // Event listeners
            if (prevBtn) {
                prevBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    prevSlide();
                });
            }
            
            if (nextBtn) {
                nextBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    nextSlide();
                });
            }
            
            // Touch/swipe support for mobile
            let touchStartX = 0;
            container.addEventListener('touchstart', (e) => {
                touchStartX = e.touches[0].clientX;
            }, { passive: true });
            
            container.addEventListener('touchend', (e) => {
                const touchEndX = e.changedTouches[0].clientX;
                const diff = touchStartX - touchEndX;
                if (Math.abs(diff) > 50) {
                    if (diff > 0) nextSlide();
                    else prevSlide();
                }
            }, { passive: true });
            
            // Keyboard navigation
            document.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowLeft') {
                    prevSlide();
                } else if (e.key === 'ArrowRight') {
                    nextSlide();
                }
            });
            
// Start auto-slide
            startAutoSlide();
            
            // Pause auto-slide when page is not visible
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    stopAutoSlide();
                } else {
                    startAutoSlide();
                }
            });
            
            // Sidebar carousels auto-rotation (same logic as top carousel)
            document.querySelectorAll('.sidebar-carousel').forEach(carousel => {
                const slides = carousel.querySelectorAll('video, img');
                if (slides.length <= 1) return;
                
                let currentIndex = 0;
                let slideTimeout;
                
                // Hide all slides except first
                slides.forEach((slide, i) => {
                    if (i !== 0) slide.style.display = 'none';
                });
                
                function showNextSlide() {
                    slides[currentIndex].style.display = 'none';
                    currentIndex = (currentIndex + 1) % slides.length;
                    slides[currentIndex].style.display = 'block';
                    
                    const currentSlide = slides[currentIndex];
                    if (currentSlide.tagName === 'VIDEO') {
                        // For video: play and wait for ended
                        currentSlide.currentTime = 0;
                        currentSlide.play().catch(() => {});
                        currentSlide.onended = () => {
                            scheduleNextSlide();
                        };
                    } else {
                        // For image: 5 seconds
                        scheduleNextSlide();
                    }
                }
                
                function scheduleNextSlide() {
                    clearTimeout(slideTimeout);
                    const currentSlide = slides[currentIndex];
                    const delay = currentSlide.tagName === 'VIDEO' ? 100 : 5000; // Video uses onended, but fallback
                    slideTimeout = setTimeout(showNextSlide, delay);
                }
                
                // Start first slide
                const firstSlide = slides[0];
                if (firstSlide.tagName === 'VIDEO') {
                    firstSlide.onended = () => {
                        scheduleNextSlide();
                    };
                    firstSlide.play().catch(() => {});
                } else {
                    scheduleNextSlide();
                }
                
                // Pause on hover
                carousel.addEventListener('mouseenter', () => clearTimeout(slideTimeout));
                carousel.addEventListener('mouseleave', () => {
                    const currentSlide = slides[currentIndex];
                    if (currentSlide.tagName === 'VIDEO') {
                        // Video will continue via onended
                    } else {
                        scheduleNextSlide();
                    }
                });
            });
        })();
    </script>
</body>
</html>


















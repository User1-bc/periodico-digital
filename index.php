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

// 2. Categoría opcional para filtrar noticias
$categoria_seleccionada = isset($_GET['categoria']) && !empty($_GET['categoria']) ? $_GET['categoria'] : '';

// 3. Obtener noticias filtradas por fecha y opcionalmente por categoría
$where = "WHERE DATE(fecha_publicacion) = ?";
$params = [$fecha_seleccionada];

if ($categoria_seleccionada !== '') {
    $where .= " AND categoria = ?";
    $params[] = $categoria_seleccionada;
}

$stmt_noticias = $pdo->prepare("SELECT * FROM noticias $where ORDER BY fecha_publicacion DESC");
$stmt_noticias->execute($params);
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
        html, body { overflow-x: hidden; }
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
.descripcion.collapsed { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; }
.btn-ver-mas { background: linear-gradient(135deg, #007bff, #0056b3); color: white; border: none; border-radius: 20px; cursor: pointer; font-size: 13px; font-weight: 600; padding: 8px 16px; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; box-shadow: 0 2px 6px rgba(0,123,255,0.3); margin-top: 8px; }
.btn-ver-mas:hover { background: linear-gradient(135deg, #0056b3, #004099); transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,123,255,0.4); }
.btn-ver-mas:active { transform: translateY(0); }
.btn-ver-mas svg { width: 14px; height: 14px; transition: transform 0.2s; flex-shrink: 0; }
.btn-ver-mas.expanded svg { transform: rotate(180deg); }

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
    max-width: 100vw;
    margin-left: calc(50% - 50vw);
    margin-right: calc(50% - 50vw);
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
@media (max-width: 768px) {
    .mobile-economia { display: block !important; }
    .col-sidebar-right .card:first-child { display: none; }
}

footer { background: #1b263b; color: white; padding: 40px 20px 20px; margin-top: 50px; }
.footer-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px; max-width: 1200px; margin: 0 auto 30px; }
.footer-col h4 { color: #ffc107; margin-bottom: 15px; font-size: 16px; }
.footer-col ul { list-style: none; padding: 0; }
.footer-col li { margin-bottom: 10px; }
.footer-col a { color: #adb5bd; text-decoration: none; transition: color 0.2s; font-size: 14px; }
.footer-col a:hover { color: #ffc107; }
.footer-bottom { text-align: center; padding-top: 20px; border-top: 1px solid #34495e; color: #8898aa; font-size: 13px; }

@media (max-width: 768px) {
    .footer-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 480px) {
    .footer-grid { grid-template-columns: 1fr; }
}

/* Dark Mode */
body.dark-mode { background: #121212; color: #e0e0e0; }
body.dark-mode header { background: #1e1e1e; border-bottom-color: #333; }
body.dark-mode .noticia-card { background: #1e1e1e; color: #e0e0e0; box-shadow: 0 2px 8px rgba(0,0,0,0.3); }
body.dark-mode .descripcion { color: #ccc; }
body.dark-mode .fecha { color: #888; }
body.dark-mode .card { background: #1e1e1e; box-shadow: 0 2px 8px rgba(0,0,0,0.3); }
body.dark-mode .widget-title { color: #ffc107; border-bottom-color: #333; }
body.dark-mode .crypto-item, body.dark-mode .fx-item { border-bottom-color: #333; color: #e0e0e0; }
body.dark-mode .price { color: #4ade80; }
body.dark-mode .sidebar-ad-media { background: #000; }
body.dark-mode .carousel-anuncio { background: #1e1e1e; }
body.dark-mode .sidebar-carousel { background: #1e1e1e; }
body.dark-mode .btn-whatsapp-top { background: #25d366; }
body.dark-mode .btn-notifications { background: #ffc107; color: #121212; }
body.dark-mode .btn-notifications.active { background: #28a745; color: white; }
body.dark-mode .btn-filter { background: #007bff; }
body.dark-mode .btn-filter:hover { background: #0056b3; }
body.dark-mode .btn-today { background: #6c757d; }
body.dark-mode .btn-today:hover { background: #5a6268; }
body.dark-mode .date-bar { background: #1e1e1e; box-shadow: 0 2px 8px rgba(0,0,0,0.3); }
body.dark-mode .date-bar input[type="date"] { background: #1e1e1e; border-color: #333; color: #e0e0e0; }
body.dark-mode .btn-ver-mas { background: linear-gradient(135deg, #007bff, #0056b3); }
body.dark-mode .btn-ver-mas:hover { background: linear-gradient(135deg, #0056b3, #004099); }
body.dark-mode .articulos-relacionados { border-top-color: #333; }
body.dark-mode .relacionado-card { background: #2a2a2a; border-color: #333; }
body.dark-mode .relacionado-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.4); }
body.dark-mode .relacionado-card h4 { color: #fff; }
body.dark-mode .relacionado-card span { color: #aaa; }
body.dark-mode .vistas-badge { background: #1a237e; color: #8c9eff; }
body.dark-mode .lectura-badge { background: #311b3f; color: #ce93d8; }
body.dark-mode .mobile-economia .card { background: #1e1e1e; }
body.dark-mode .mobile-economia .widget-title { color: #ffc107; }
body.dark-mode .mobile-economia .crypto-item, body.dark-mode .mobile-economia .fx-item { border-bottom-color: #333; }
body.dark-mode .col-sidebar-right .card { background: #1e1e1e; }
body.dark-mode footer { background: #0d0d0d; }
body.dark-mode .footer-col a { color: #aaa; }
body.dark-mode .footer-col a:hover { color: #ffc107; }
body.dark-mode .footer-bottom { border-top-color: #333; color: #888; }
body.dark-mode .share-btn { box-shadow: 0 2px 6px rgba(0,0,0,0.4); }
body.dark-mode .share-btn:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.5); }
body.dark-mode .btn-ad { background: #007bff; }
body.dark-mode .btn-ad:hover { background: #0056b3; }
body.dark-mode .ad-space-card { background: #2a2a2a; border-color: #333; }
body.dark-mode .ad-space-info h3 { color: #fff; }
body.dark-mode .benefit-card { background: #2a2a2a; border-color: #333; }
body.dark-mode .cta-section { background: linear-gradient(135deg, #1e1e1e 0%, #007bff 100%); }
body.dark-mode .legal-notice { background: #3e2723; border-left-color: #f57c00; }
body.dark-mode .legal-notice h4 { color: #ffb74d; }
body.dark-mode .legal-notice p { color: #ffcc80; }
body.dark-mode .clause-card { background: #2a2a2a; border-color: #333; }
body.dark-mode .clause-card h3 { color: #fff; }
body.dark-mode .clause-card p { color: #ccc; }
body.dark-mode .contact-section { background: #1e1e1e; }
body.dark-mode .form-group input, body.dark-mode .form-group select, body.dark-mode .form-group textarea { background: #1e1e1e; border-color: #333; color: #e0e0e0; }
body.dark-mode .form-group input:focus, body.dark-mode .form-group select:focus, body.dark-mode .form-group textarea:focus { border-color: #007bff; box-shadow: 0 0 0 3px rgba(0,123,255,0.3); }
body.dark-mode .btn-submit { background: #007bff; }
body.dark-mode .btn-submit:hover { background: #0056b3; }
body.dark-mode .btn-back { background: #6c757d; }
body.dark-mode .btn-back:hover { background: #5a6268; }
body.dark-mode .page-header { border-bottom-color: #333; }
body.dark-mode .page-header h1 { color: #fff; }
body.dark-mode .page-header .subtitle { color: #aaa; }
body.dark-mode .highlight-box { background: #1a237e; border-left-color: #3f51b5; }
body.dark-mode .highlight-box h4 { color: #8c9eff; }
body.dark-mode .highlight-box p { color: #c5cae9; }
body.dark-mode .top-carousel-container { background: #000; }
body.dark-mode .top-carousel-btn { background: rgba(255,255,255,0.2); color: #fff; }
body.dark-mode .top-carousel-btn:hover { background: rgba(255,255,255,0.3); }
body.dark-mode .top-carousel-dot { background: rgba(255,255,255,0.3); }
body.dark-mode .top-carousel-dot.active { background: #fff; }
body.dark-mode .top-carousel-dot:hover { background: rgba(255,255,255,0.5); }

<?php
// Open Graph / Twitter Card data for home
$og_title = "Periódico Digital RD - La fuente más confiable de República Dominicana";
$og_desc = "Noticias de última hora, política, economía, deportes y sociedad en República Dominicana. Periodismo independiente y veraz.";
$og_image = "https://periodicodigitalrd.online/uploads/125688.png";
$og_url = "https://periodicodigitalrd.online/";
$canonical_url = "https://periodicodigitalrd.online/";

// Detectar si estamos en categoría o fecha
if (isset($_GET['categoria']) && !empty($_GET['categoria'])) {
    $cat = htmlspecialchars($_GET['categoria']);
    $og_title = "Sección $cat - Periódico Digital RD";
    $og_desc = "Todas las noticias de $cat en República Dominicana. Últimas novedades y análisis.";
    $canonical_url = "https://periodicodigitalrd.online/index.php?categoria=" . urlencode($_GET['categoria']);
} elseif (isset($_GET['fecha']) && !empty($_GET['fecha'])) {
    $fecha = htmlspecialchars($_GET['fecha']);
    $og_title = "Noticias del $fecha - Periódico Digital RD";
    $og_desc = "Todas las noticias publicadas el $fecha en Periódico Digital RD.";
    $canonical_url = "https://periodicodigitalrd.online/index.php?fecha=" . urlencode($_GET['fecha']);
}
?>
<!-- SEO & Social Meta Tags -->
<meta name="description" content="<?php echo $og_desc; ?>">
<meta name="robots" content="index, follow">
<link rel="canonical" href="<?php echo $canonical_url; ?>">

<!-- Open Graph -->
<meta property="og:type" content="website">
<meta property="og:title" content="<?php echo $og_title; ?>">
<meta property="og:description" content="<?php echo $og_desc; ?>">
<meta property="og:image" content="<?php echo $og_image; ?>">
<meta property="og:url" content="<?php echo $canonical_url; ?>">
<meta property="og:site_name" content="Periódico Digital RD">
<meta property="og:locale" content="es_DO">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo $og_title; ?>">
<meta name="twitter:description" content="<?php echo $og_desc; ?>">
<meta name="twitter:image" content="<?php echo $og_image; ?>">
<meta name="twitter:site" content="@PeriodicoDigitalRD">

<!-- RSS Feed -->
<link rel="alternate" type="application/rss+xml" title="Periódico Digital RD - RSS" href="/rss.php">

<!-- Preconnect para performance -->
<link rel="preconnect" href="https://res.cloudinary.com">
<link rel="preconnect" href="https://api.cloudinary.com">
<link rel="dns-prefetch" href="https://fonts.googleapis.com">

</style>
</head>
<body>

<header>
        <div class="header-left">
            <a href="index.php" style="text-decoration: none;">
                <img src="uploads/125688.png" alt="Periodico Digital RD" style="height: 50px; width: auto; max-width: 100%;">
            </a>
        </div>

        <div class="header-title">
            <h1>Periodico Digital RD</h1>
            <p>La fuente mas confiable de Republica Dominicana</p>
        </div>

<div class="header-right">
            <button id="btn-darkmode" class="btn-icon" onclick="toggleDarkMode()" aria-label="Alternar modo oscuro" style="background: #6c757d; color: white;">
                <svg id="darkmode-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="22" height="22">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                </svg>
            </button>

            <button id="btn-fontsize" class="btn-icon" onclick="changeFontSize(1)" aria-label="Aumentar tamaño de fuente" style="background: #17a2b8; color: white;" title="A+ Tamaño">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="22" height="22">
                    <text x="50%" y="55%" dominant-baseline="middle" text-anchor="middle" font-size="14" font-weight="bold">A+</text>
                </svg>
            </button>
            <button id="btn-fontsize-down" class="btn-icon" onclick="changeFontSize(-1)" aria-label="Disminuir tamaño de fuente" style="background: #6c757d; color: white;" title="A- Tamaño">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="22" height="22">
                    <text x="50%" y="55%" dominant-baseline="middle" text-anchor="middle" font-size="14" font-weight="bold">A-</text>
                </svg>
            </button>

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

    <!-- ECONOMÍA MÓVIL - Solo visible en móvil debajo del carousel -->
    <div class="mobile-economia section-box" style="max-width: 1200px; margin: 20px auto 0 auto; padding: 0 10px; display: none;">
        <div class="card">
            <div class="widget-title">Economía</div>
            <div class="crypto-item">
                <span>Bitcoin (BTC):</span>
                <span class="price" id="btc-price-mobile">Cargando...</span>
            </div>
            <div class="crypto-item">
                <span>Ethereum (ETH):</span>
                <span class="price" id="eth-price-mobile">Cargando...</span>
            </div>
            <div class="fx-item">
                <span>Oro (XAU/USD):</span>
                <span class="price" id="gold-price-mobile">Cargando...</span>
            </div>
            <div class="fx-item">
                <span>USD / DOP:</span>
                <span class="price" id="usd-dop-mobile">Cargando...</span>
            </div>
            <div class="fx-item">
                <span>EUR / DOP:</span>
                <span class="price" id="eur-dop-mobile">Cargando...</span>
            </div>
        </div>
    </div>

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
<article class="noticia-card" id="noticia-<?php echo $noticia['id']; ?>">
                        <h2><?php echo htmlspecialchars($noticia['titulo']); ?></h2>
                        <div class="fecha">
                            Publicado el: <?php echo $noticia['fecha_publicacion']; ?>
                            <?php if (!empty($noticia['vistas'])): ?>
                            <span class="vistas-badge" style="margin-left: 12px; padding: 2px 8px; background: #e3f2fd; color: #1976d2; border-radius: 12px; font-size: 11px; font-weight: 600;">
                                👁 <?php echo number_format($noticia['vistas']); ?>
                            </span>
                            <?php endif; ?>
                            <?php 
                                $palabras = str_word_count($noticia['descripcion'] ?? '');
                                $minutos = max(1, ceil($palabras / 200));
                            ?>
                            <span class="lectura-badge" style="margin-left: 8px; padding: 2px 8px; background: #f3e5f5; color: #7b1fa2; border-radius: 12px; font-size: 11px; font-weight: 600;">
                                📖 <?php echo $minutos; ?> min
                            </span>
                        </div>
                        
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

                        <?php 
$descripcion = $noticia['descripcion'];
$paragraphs = explode("\n\n", trim($descripcion));
$first_paragraph = $paragraphs[0];
$rest = isset($paragraphs[1]) ? implode("\n\n", array_slice($paragraphs, 1)) : '';
// Show button if explicit paragraphs OR if content is long (>200 chars)
$has_more = !empty($rest) || (strlen($descripcion) > 200);
?>
<div class="descripcion collapsed" data-full="<?php echo htmlspecialchars($descripcion); ?>" data-preview="<?php echo htmlspecialchars($first_paragraph); ?>">
    <?php echo nl2br(htmlspecialchars($first_paragraph)); ?>
</div>
<?php if ($has_more): ?>
<button class="btn-ver-mas" onclick="toggleDescripcion(this)" aria-expanded="false" aria-label="Ver más">
    Ver más <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
</button>
<?php endif; ?>

<?php
// Botones de compartir
$share_url = 'https://periodicodigitalrd.online/index.php#noticia-' . $noticia['id'];
$share_titulo = $noticia['titulo'];
$share_desc = $noticia['descripcion'];
$share_img = $noticia['imagen'] ?? 'https://periodicodigitalrd.online/uploads/125688.png';
include __DIR__ . '/compartir.php';
echo getShareButtons($share_titulo, $share_url, $share_desc, $share_img);
?>

<?php
// Artículos relacionados (misma categoría, excluyendo el actual, máximo 3)
$stmt_rel = $pdo->prepare("SELECT id, titulo, imagen, fecha_publicacion FROM noticias WHERE categoria = ? AND id != ? ORDER BY fecha_publicacion DESC LIMIT 3");
$stmt_rel->execute([$noticia['categoria'], $noticia['id']]);
$relacionadas = $stmt_rel->fetchAll(PDO::FETCH_ASSOC);
if (!empty($relacionadas)):
?>
<div class="articulos-relacionados" style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #eee;">
    <h3 style="font-size: 16px; font-weight: 700; color: #1b263b; margin-bottom: 12px;">📖 Artículos relacionados</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 12px;">
        <?php foreach ($relacionadas as $rel): ?>
        <a href="index.php#noticia-<?php echo $rel['id']; ?>" class="relacionado-card" style="text-decoration: none; color: inherit; background: #f8f9fa; border-radius: 8px; padding: 12px; border: 1px solid #eee; transition: transform 0.2s, box-shadow 0.2s; display: block;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
            <?php if (!empty($rel['imagen'])): ?>
            <img src="<?php echo htmlspecialchars($rel['imagen']); ?>" alt="" style="width: 100%; height: 120px; object-fit: cover; border-radius: 6px; margin-bottom: 8px;">
            <?php endif; ?>
            <h4 style="font-size: 14px; font-weight: 600; color: #1b263b; margin: 0 0 4px; line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?php echo htmlspecialchars($rel['titulo']); ?></h4>
            <span style="font-size: 11px; color: #888;"><?php echo date('d/m/Y', strtotime($rel['fecha_publicacion'])); ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

                    </article>
                <?php endforeach; ?>
        </main>

        <aside class="col-sidebar col-sidebar-right">
            <?php
            // Más leídos widget
            $stmt_popular = $pdo->prepare("SELECT id, titulo, imagen, vistas, fecha_publicacion FROM noticias WHERE estado = 'publicado' ORDER BY vistas DESC LIMIT 5");
            $stmt_popular->execute();
            $populares = $stmt_popular->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($populares)):
            ?>
            <div class="card" style="margin-bottom: 20px;">
                <div class="widget-title">🔥 Más leídos</div>
                <?php foreach ($populares as $i => $pop): ?>
                <a href="index.php#noticia-<?php echo $pop['id']; ?>" style="text-decoration: none; color: inherit; display: flex; gap: 10px; padding: 10px 0; border-bottom: 1px solid #f0f0f0;" onmouseover="this.style.background='#f8f9fa'" onmouseout="this.style.background='transparent'">
                    <span style="font-weight: 700; color: #ffc107; font-size: 14px; min-width: 24px;"><?php echo $i + 1; ?></span>
                    <?php if (!empty($pop['imagen'])): ?>
                    <img src="<?php echo htmlspecialchars($pop['imagen']); ?>" alt="" style="width: 60px; height: 45px; object-fit: cover; border-radius: 4px; flex-shrink: 0;">
                    <?php endif; ?>
                    <div style="flex: 1; min-width: 0;">
                        <h5 style="margin: 0 0 2px; font-size: 12px; font-weight: 600; color: #1b263b; line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?php echo htmlspecialchars($pop['titulo']); ?></h5>
                        <span style="font-size: 10px; color: #888;">👁 <?php echo number_format($pop['vistas']); ?> · <?php echo date('d/m', strtotime($pop['fecha_publicacion'])); ?></span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

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
                
                const btcText = dataCrypto.bitcoin ? "$" + dataCrypto.bitcoin.usd.toLocaleString() + " USD" : "Error";
                const ethText = dataCrypto.ethereum ? "$" + dataCrypto.ethereum.usd.toLocaleString() + " USD" : "Error";
                const goldText = dataCrypto['pax-gold'] ? "$" + dataCrypto['pax-gold'].usd.toLocaleString() + " USD" : "No disponible";
                
                ['btc-price', 'btc-price-mobile'].forEach(id => { const el = document.getElementById(id); if(el) el.innerText = btcText; });
                ['eth-price', 'eth-price-mobile'].forEach(id => { const el = document.getElementById(id); if(el) el.innerText = ethText; });
                ['gold-price', 'gold-price-mobile'].forEach(id => { const el = document.getElementById(id); if(el) el.innerText = goldText; });

                let responseFx = await fetch('https://open.er-api.com/v6/latest/USD');
                let dataFx = await responseFx.json();
                
                if(dataFx && dataFx.rates) {
                    let rateUSD_DOP = dataFx.rates.DOP; 
                    let rateEUR_USD = dataFx.rates.EUR; 
                    
                    if(rateUSD_DOP) {
                        const usdText = "~ " + rateUSD_DOP.toFixed(2) + " RD$";
                        ['usd-dop', 'usd-dop-mobile'].forEach(id => { const el = document.getElementById(id); if(el) el.innerText = usdText; });
                        
                        if(rateEUR_USD) {
                            let rateEUR_DOP = rateUSD_DOP / rateEUR_USD;
                            const eurText = "~ " + rateEUR_DOP.toFixed(2) + " RD$";
                            ['eur-dop', 'eur-dop-mobile'].forEach(id => { const el = document.getElementById(id); if(el) el.innerText = eurText; });
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

        function toggleDescripcion(btn) {
            const container = btn.closest('article').querySelector('.descripcion');
            const isExpanded = btn.classList.toggle('expanded');
            btn.setAttribute('aria-expanded', isExpanded);
            if (isExpanded) {
                container.classList.remove('collapsed');
                btn.innerHTML = 'Ver menos <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>';
            } else {
                container.classList.add('collapsed');
                btn.innerHTML = 'Ver más <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>';
            }
        }

        // Initialize descriptions on load
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.descripcion[data-full]').forEach(el => {
                el.classList.add('collapsed');
            });

            // View counter - increment when article enters viewport
            const viewTracked = new Set();
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !viewTracked.has(entry.target.id)) {
                        viewTracked.add(entry.target.id);
                        const id = entry.target.id.replace('noticia-', '');
                        fetch('incrementar_vista.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id: id })
                        }).catch(() => {});
                    }
                });
            }, { threshold: 0.5, rootMargin: '0px 0px -100px 0px' });

            document.querySelectorAll('article[id^="noticia-"]').forEach(article => {
                observer.observe(article);
            });

// Dark mode
            const darkBtn = document.getElementById('btn-darkmode');
            const darkIcon = document.getElementById('darkmode-icon');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const savedDark = localStorage.getItem('darkMode') === 'true';
            const isDark = savedDark || (!localStorage.getItem('darkMode') && prefersDark);
            
            if (isDark) {
                document.body.classList.add('dark-mode');
                darkIcon.innerHTML = '<circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="4.22" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>';
                darkBtn.setAttribute('aria-label', 'Cambiar a modo claro');
            } else {
                darkIcon.innerHTML = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>';
                darkBtn.setAttribute('aria-label', 'Cambiar a modo oscuro');
            }
            
            darkBtn.addEventListener('click', () => {
                const isDark = document.body.classList.toggle('dark-mode');
                localStorage.setItem('darkMode', isDark);
                if (isDark) {
                    darkIcon.innerHTML = '<circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="4.22" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>';
                    darkBtn.setAttribute('aria-label', 'Cambiar a modo claro');
                } else {
                    darkIcon.innerHTML = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>';
                    darkBtn.setAttribute('aria-label', 'Cambiar a modo oscuro');
                }
            });

            // Font size
            const fontBtnUp = document.getElementById('btn-fontsize');
            const fontBtnDown = document.getElementById('btn-fontsize-down');
            let fontScale = parseFloat(localStorage.getItem('fontScale') || '1');
            document.body.style.fontSize = fontScale + 'rem';
            
            fontBtnUp.addEventListener('click', () => {
                fontScale = Math.min(fontScale + 0.1, 1.5);
                document.body.style.fontSize = fontScale + 'rem';
                localStorage.setItem('fontScale', fontScale.toString());
            });
            
            fontBtnDown.addEventListener('click', () => {
                fontScale = Math.max(fontScale - 0.1, 0.8);
                document.body.style.fontSize = fontScale + 'rem';
                localStorage.setItem('fontScale', fontScale.toString());
            });

            // View counter - increment when article enters viewport
            const viewTracked = new Set();
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !viewTracked.has(entry.target.id)) {
                        viewTracked.add(entry.target.id);
                        const id = entry.target.id.replace('noticia-', '');
                        fetch('incrementar_vista.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ id: id })
                        }).catch(() => {});
                    }
                });
            }, { threshold: 0.5, rootMargin: '0px 0px -100px 0px' });

            document.querySelectorAll('article[id^="noticia-"]').forEach(article => {
                observer.observe(article);
            });
        });

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

<footer>
    <div class="footer-grid">
        <div class="footer-col">
            <h4>Periódico Digital RD</h4>
            <p style="color: #adb5bd; font-size: 14px; line-height: 1.6;">Tu fuente confiable de noticias en República Dominicana. Periodismo independiente, veraz y accesible.</p>
        </div>
        <div class="footer-col">
            <h4>Secciones</h4>
            <ul>
                <li><a href="index.php">Inicio</a></li>
                <li><a href="index.php?categoria=Política">Política</a></li>
                <li><a href="index.php?categoria=Economía">Economía</a></li>
                <li><a href="index.php?categoria=Deportes">Deportes</a></li>
                <li><a href="index.php?categoria=Sociedad">Sociedad</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Enlaces Legales</h4>
            <ul>
                <li><a href="quienes_somos.php">Quiénes Somos</a></li>
                <li><a href="contactos.php">Contactos</a></li>
                <li><a href="publicidad.php">Publicidad</a></li>
                <li><a href="politica_privacidad.php">Política de Privacidad</a></li>
                <li><a href="derechos_reservados.php">Derechos Reservados</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Contacto</h4>
            <ul>
                <li>📍 Santo Domingo, RD</li>
                <li>✉️ redaccion@periodicodigitalrd.online</li>
                <li>📱 <a href="https://wa.me/18295482901" style="color: #25d366;">WhatsApp: +1 829 548 2901</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; 2024 Periódico Digital RD. Todos los derechos reservados.
    </div>
</footer>

</body>
</html>
<?php /* cache-buster: 2024-09-23-03 */ ?>
<?php // End of file ?>


















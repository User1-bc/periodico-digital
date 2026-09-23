<?php
require_once 'conexion.php';

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml"
        xmlns:mobile="http://www.google.com/schemas/sitemap-mobile/1.0"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
        xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">
    
    <!-- Homepage -->
    <url>
        <loc>https://periodicodigitalrd.online/</loc>
        <changefreq>hourly</changefreq>
        <priority>1.0</priority>
        <lastmod><?php echo date('Y-m-d\TH:i:sP'); ?></lastmod>
    </url>
    
    <!-- Páginas estáticas -->
    <url>
        <loc>https://periodicodigitalrd.online/quienes_somos.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc>https://periodicodigitalrd.online/contactos.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc>https://periodicodigitalrd.online/publicidad.php</loc>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <url>
        <loc>https://periodicodigitalrd.online/politica_privacidad.php</loc>
        <changefreq>yearly</changefreq>
        <priority>0.5</priority>
    </url>
    <url>
        <loc>https://periodicodigitalrd.online/derechos_reservados.php</loc>
        <changefreq>yearly</changefreq>
        <priority>0.5</priority>
    </url>
    
    <!-- Categorías -->
    <?php
    require_once 'conexion.php';
    $cats = ['Política', 'Economía', 'Deportes', 'Sociedad', 'Internacional', 'General'];
    foreach ($cats as $cat) {
        $catUrl = urlencode($cat);
        echo "    <url>\n";
        echo "        <loc>https://periodicodigitalrd.online/index.php?categoria=$catUrl</loc>\n";
        echo "        <changefreq>hourly</changefreq>\n";
        echo "        <priority>0.9</priority>\n";
        echo "    </url>\n";
    }
    ?>
    
    <!-- Noticias recientes (últimos 30 días) -->
    <?php
    require_once 'conexion.php';
    $stmt = $pdo->prepare("SELECT id, fecha_publicacion, titulo FROM noticias WHERE fecha_publicacion >= DATE_SUB(NOW(), INTERVAL 30 DAY) ORDER BY fecha_publicacion DESC LIMIT 500");
    $stmt->execute();
    $noticias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($noticias as $n) {
        $lastmod = date('Y-m-d\TH:i:sP', strtotime($n['fecha_publicacion']));
        echo "    <url>\n";
        echo "        <loc>https://periodicodigitalrd.online/index.php#noticia-{$n['id']}</loc>\n";
        echo "        <lastmod>$lastmod</lastmod>\n";
        echo "        <changefreq>daily</changefreq>\n";
        echo "        <priority>0.7</priority>\n";
        echo "        <news:news>\n";
        echo "            <news:publication><news:name>Periódico Digital RD</news:name><news:language>es</news:language></news:publication>\n";
        echo "            <news:publication_date>$lastmod</news:publication_date>\n";
        echo "            <news:title>" . htmlspecialchars($n['titulo']) . "</news:title>\n";
        echo "        </news:news>\n";
        echo "    </url>\n";
    }
    ?>
    
    <!-- Podcasts -->
    <?php
    $stmt = $pdo->query("SELECT id, fecha_publicacion, titulo FROM podcasts ORDER BY fecha_publicacion DESC LIMIT 50");
    $podcasts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($podcasts as $p) {
        $lastmod = date('Y-m-d\TH:i:sP', strtotime($p['fecha_publicacion']));
        echo "    <url>\n";
        echo "        <loc>https://periodicodigitalrd.online/index.php#podcast-{$p['id']}</loc>\n";
        echo "        <lastmod>$lastmod</lastmod>\n";
        echo "        <changefreq>monthly</changefreq>\n";
        echo "        <priority>0.6</priority>\n";
        echo "    </url>\n";
    }
    ?>
    
</urlset>
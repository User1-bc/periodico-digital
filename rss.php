<?php
require_once 'conexion.php';

header('Content-Type: application/rss+xml; charset=utf-8');

$base_url = 'https://periodicodigitalrd.online';

// Últimas 50 noticias
$stmt = $pdo->prepare("SELECT * FROM noticias ORDER BY fecha_publicacion DESC LIMIT 50");
$stmt->execute();
$noticias = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0"
     xmlns:content="http://purl.org/rss/1.0/modules/content/"
     xmlns:dc="http://purl.org/dc/elements/1.1/"
     xmlns:atom="http://www.w3.org/2005/Atom"
     xmlns:media="http://search.yahoo.com/mrss/">
    <channel>
        <title>Periódico Digital RD</title>
        <link><?php echo $base_url; ?></link>
        <description>Noticias de última hora, política, economía, deportes y sociedad en República Dominicana. Periodismo independiente y veraz.</description>
        <language>es-DO</language>
        <lastBuildDate><?php echo date('r'); ?></lastBuildDate>
        <generator>Periódico Digital RD</generator>
        <atom:link href="<?php echo $base_url; ?>/rss.php" rel="self" type="application/rss+xml" />
        <image>
            <url><?php echo $base_url; ?>/uploads/125688.png</url>
            <title>Periódico Digital RD</title>
            <link><?php echo $base_url; ?></link>
        </image>
        
        <?php foreach ($noticias as $n): 
            $item_url = $base_url . '/index.php#noticia-' . $n['id'];
            $pubDate = date('r', strtotime($n['fecha_publicacion']));
            $description = htmlspecialchars(mb_substr($n['descripcion'] ?? $n['contenido'], 0, 300));
            if (strlen($n['descripcion'] ?? $n['contenido']) > 300) $description .= '...';
        ?>
        <item>
            <title><![CDATA[<?php echo htmlspecialchars($n['titulo']); ?>]]></title>
            <link><?php echo $item_url; ?></link>
            <guid isPermaLink="false"><?php echo $item_url; ?></guid>
            <description><![CDATA[<?php echo $description; ?>]]></description>
            <content:encoded><![CDATA[<?php echo htmlspecialchars($n['descripcion'] ?? $n['contenido']); ?>]]></content:encoded>
            <dc:creator>Periódico Digital RD</dc:creator>
            <dc:date><?php echo $pubDate; ?></dc:date>
            <category><?php echo htmlspecialchars($n['categoria'] ?? 'General'); ?></category>
            <pubDate><?php echo $pubDate; ?></pubDate>
            <?php if (!empty($n['imagen'])): ?>
            <media:content url="<?php echo htmlspecialchars($n['imagen']); ?>" type="image/jpeg" />
            <?php endif; ?>
        </item>
        <?php endforeach; ?>
    </channel>
</rss>